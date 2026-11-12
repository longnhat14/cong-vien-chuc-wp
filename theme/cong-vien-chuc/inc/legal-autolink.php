<?php
/**
 * Tự động gắn liên kết nội bộ từ số hiệu / tên văn bản pháp luật về trang văn bản gốc.
 *
 * - Nguồn dữ liệu: /api/legal-documents/link-index (cache transient 1 giờ).
 * - Chỉ link khi khớp DUY NHẤT một văn bản công khai; không khớp thì để nguyên chữ.
 * - Làm ở lớp hiển thị (không ghi vào database), nên văn bản mới nhập sẽ tự có link ở mọi nơi.
 * - Mỗi văn bản chỉ link một lần trong mỗi đoạn/mục; không link trong tiêu đề, link có sẵn, code, nút, form.
 *
 * Dùng:
 *   cvc_ll_html( $html )  : nội dung HTML (bài viết...).
 *   cvc_ll_text( $plain ) : văn bản thuần -> HTML đã escape + link (có tuỳ chọn nl2br).
 *   cvc_ll_set_self( $slug ) : bỏ link trỏ về chính trang văn bản đang xem.
 *
 * @package cong-vien-chuc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_LL_INDEX_KEY = 'cvc_ll_index_v1';

/** @var array<string,mixed> Trạng thái trong một lần render. */
$GLOBALS['cvc_ll'] = array( 'self' => '', 'emitted' => 0, 'missing' => array(), 'maps' => null );

function cvc_ll_set_self( string $slug ): void {
	$GLOBALS['cvc_ll']['self'] = $slug;
}

/** Chuẩn hoá số hiệu để so khớp: hoa, bỏ khoảng trắng, thống nhất gạch, bỏ số 0 đầu. */
function cvc_ll_norm( string $n ): string {
	$n = mb_strtoupper( trim( html_entity_decode( $n, ENT_QUOTES, 'UTF-8' ) ), 'UTF-8' );
	$n = str_replace( array( '–', '—', '‑', '−' ), '-', $n );
	$n = preg_replace( '/\s+/u', '', $n );
	$n = preg_replace( '/^0+(?=\d)/', '', (string) $n );
	return (string) $n;
}

/** Tên Luật rút gọn từ tiêu đề: "Luật Viên chức năm 2010 (sửa đổi…)" => "Viên chức". */
function cvc_ll_law_name( string $title ): string {
	$t = trim( preg_replace( '/\s+/u', ' ', $title ) );
	if ( ! preg_match( '/^(Bộ luật|Luật)\s+(.+)$/u', $t, $m ) ) {
		return '';
	}
	$rest = $m[2];
	$rest = preg_replace( '/\s*\(.*$/u', '', $rest );
	$rest = preg_replace( '/\s+(số|năm)\s+.*$/ui', '', (string) $rest );
	$rest = preg_replace( '/\s+\d{4}.*$/u', '', (string) $rest );
	$rest = trim( (string) $rest, " \t,;.-" );
	return mb_strlen( $rest ) >= 4 ? $m[1] . ' ' . $rest : '';
}

/**
 * @return array{full:array<string,array>,short:array<string,array<int,array>>,laws:array<string,array<int,array>>,law_re:string}
 */
function cvc_ll_maps(): array {
	if ( is_array( $GLOBALS['cvc_ll']['maps'] ) ) {
		return $GLOBALS['cvc_ll']['maps'];
	}
	$rows = get_transient( CVC_LL_INDEX_KEY );
	if ( ! is_array( $rows ) ) {
		$res  = function_exists( 'cvc_ci_api' ) ? cvc_ci_api( '/api/legal-documents/link-index', array(), null, 6, 0 ) : array( 'ok' => false );
		$rows = ( ! empty( $res['ok'] ) && is_array( $res['data']['data'] ?? null ) ) ? $res['data']['data'] : null;
		if ( null === $rows ) {
			$rows = get_option( CVC_LL_INDEX_KEY . '_last', array() );
			set_transient( CVC_LL_INDEX_KEY, is_array( $rows ) ? $rows : array(), 120 );
		} else {
			set_transient( CVC_LL_INDEX_KEY, $rows, HOUR_IN_SECONDS );
			update_option( CVC_LL_INDEX_KEY . '_last', $rows, false );
		}
	}
	$full  = array();
	$short = array();
	$laws  = array();
	foreach ( (array) $rows as $r ) {
		if ( ! is_array( $r ) || empty( $r['n'] ) || empty( $r['s'] ) ) {
			continue;
		}
		$key = cvc_ll_norm( (string) $r['n'] );
		if ( isset( $full[ $key ] ) ) {
			$full[ $key ]['dup'] = true; // trùng số hiệu: không link để tránh nhầm.
			continue;
		}
		$full[ $key ] = $r;
		// Khoá rút gọn "số/năm" (không phần ký hiệu) cho cách viết "Nghị định 232/2026".
		if ( preg_match( '#^(\d{1,4})/(\d{4})/#', $key, $m ) ) {
			$short[ $m[1] . '/' . $m[2] ][] = $r;
		}
		$name = cvc_ll_law_name( (string) ( $r['t'] ?? '' ) );
		if ( '' !== $name && preg_match( '#/(\d{4})/#', $key, $my ) ) {
			$laws[ mb_strtolower( $name, 'UTF-8' ) ][] = array( 'year' => $my[1], 'row' => $r, 'name' => $name );
		}
	}
	// Mẫu tên Luật (dài trước ngắn).
	$names = array();
	foreach ( $laws as $k => $list ) {
		$names[ $list[0]['name'] ] = mb_strlen( $list[0]['name'] );
	}
	arsort( $names );
	$alt = array();
	foreach ( array_keys( $names ) as $nm ) {
		$alt[] = preg_replace( '/\\\\ /', '\s+', preg_quote( $nm, '/' ) );
	}
	$maps = array(
		'full'   => $full,
		'short'  => $short,
		'laws'   => $laws,
		'law_re' => $alt ? '(?:' . implode( '|', $alt ) . ')' : '',
	);
	$GLOBALS['cvc_ll']['maps'] = $maps;
	return $maps;
}

/** Tìm văn bản theo số hiệu đầy đủ; trả về hàng hoặc null. */
function cvc_ll_find_full( string $num ): ?array {
	$maps = cvc_ll_maps();
	$key  = cvc_ll_norm( $num );
	if ( isset( $maps['full'][ $key ] ) && empty( $maps['full'][ $key ]['dup'] ) ) {
		return $maps['full'][ $key ];
	}
	return null;
}

/** Tìm theo "số/năm" + loại văn bản (NĐ/TT/QĐ/NQ/Luật); chỉ khi khớp duy nhất. */
function cvc_ll_find_short( string $sn, string $kind ): ?array {
	$maps = cvc_ll_maps();
	$sn   = preg_replace( '/^0+(?=\d)/', '', $sn );
	$list = $maps['short'][ $sn ] ?? array();
	$want = cvc_ll_kind_code( $kind );
	$hit  = array();
	foreach ( $list as $r ) {
		if ( '' === $want || preg_match( '#/' . preg_quote( $want, '#' ) . '#u', cvc_ll_norm( (string) $r['n'] ) ) ) {
			$hit[] = $r;
		}
	}
	return 1 === count( $hit ) ? $hit[0] : null;
}

function cvc_ll_kind_code( string $kind ): string {
	$k = preg_replace( '/\s+/u', ' ', mb_strtolower( trim( $kind ), 'UTF-8' ) );
	$map = array(
		'nghị định' => 'NĐ', 'nđ' => 'NĐ', 'thông tư' => 'TT', 'thông tư liên tịch' => 'TT', 'tt' => 'TT',
		'quyết định' => 'QĐ', 'qđ' => 'QĐ', 'nghị quyết' => 'NQ', 'nq' => 'NQ',
		'luật' => 'QH', 'bộ luật' => 'QH',
	);
	return $map[ $k ] ?? '';
}

/** Slug bài học /kien-thuc/ của Điều $art trong văn bản $doc_slug ('' nếu chưa có). Cache 12 giờ / văn bản. */
function cvc_ll_lesson_slug( string $doc_slug, string $art ): string {
	static $mem = array();
	if ( ! function_exists( 'cvc_knowledge_item_url' ) ) {
		return '';
	}
	if ( ! isset( $mem[ $doc_slug ] ) ) {
		$key = 'cvc_ll_les_' . md5( $doc_slug );
		$map = get_transient( $key );
		if ( ! is_array( $map ) ) {
			$res = ( new CVC_Api_Client( null, 5 ) )->get( '/api/legal-documents/' . rawurlencode( $doc_slug ) . '/lessons' );
			$map = ! empty( $res['ok'] ) && is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : null;
			set_transient( $key, is_array( $map ) ? $map : array(), is_array( $map ) ? 12 * HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
			$map = is_array( $map ) ? $map : array();
		}
		$mem[ $doc_slug ] = $map;
	}

	return (string) ( $mem[ $doc_slug ][ mb_strtolower( $art, 'UTF-8' ) ] ?? '' );
}

function cvc_ll_url( array $row, string $anchor = '' ): string {
	return function_exists( 'cvc_legal_document_url' ) ? cvc_legal_document_url( (string) $row['s'] ) . $anchor : home_url( '/van-ban-phap-luat/' . rawurlencode( (string) $row['s'] ) . '/' ) . $anchor;
}

/** Ghi nhận số hiệu chưa có trong thư viện (đếm gần đúng, ghi khi tắt PHP). */
function cvc_ll_note_missing( string $num ): void {
	$k = cvc_ll_norm( $num );
	if ( mb_strlen( $k ) > 40 ) {
		return;
	}
	$GLOBALS['cvc_ll']['missing'][ $k ] = ( $GLOBALS['cvc_ll']['missing'][ $k ] ?? 0 ) + 1;
}

add_action(
	'shutdown',
	static function () {
		$m = $GLOBALS['cvc_ll']['missing'] ?? array();
		if ( ! $m || is_admin() || wp_doing_cron() ) {
			return;
		}
		$store = get_option( 'cvc_ll_missing', array() );
		$store = is_array( $store ) ? $store : array();
		$dirty = false;
		foreach ( $m as $k => $c ) {
			if ( ! isset( $store[ $k ] ) ) {
				$store[ $k ] = array( 'c' => $c, 'first' => time() );
				$dirty       = true;
			} elseif ( 1 === wp_rand( 1, 10 ) ) { // lấy mẫu 10% để không ghi DB liên tục.
				$store[ $k ]['c'] += 10 * $c;
				$dirty             = true;
			}
		}
		if ( $dirty ) {
			if ( count( $store ) > 600 ) {
				uasort( $store, static fn ( $a, $b ) => $b['c'] <=> $a['c'] );
				$store = array_slice( $store, 0, 400, true );
			}
			update_option( 'cvc_ll_missing', $store, false );
		}
	}
);

/**
 * Bộ regex chính. Thứ tự lựa chọn quan trọng (khớp từ trái qua phải, ưu tiên dạng dài).
 */
function cvc_ll_pattern(): string {
	$maps = cvc_ll_maps();
	$sp   = '(?:\s|&nbsp;|&#160;)+';
	$full = '\d{1,4}\/\d{4}\/\p{Lu}[\p{Lu}\d\-Đ]*(?:\/[\p{Lu}\d\-Đ]+)?|\d{1,4}-\p{Lu}{1,5}\/\p{Lu}{2,6}|\d{1,5}\/(?:QĐ|NQ)-[\p{L}\-]{2,12}';
	$kind = '(?i:Nghị\s+định|Thông\s+tư\s+liên\s+tịch|Thông\s+tư|Quyết\s+định|Nghị\s+quyết|Bộ\s+luật|Luật|NĐ|TT|QĐ|NQ)';
	$sub  = '(?:,?' . $sp . '(?:khoản|điểm)' . $sp . '[\dA-Za-zđ]+){0,2}';
	$p    = '~(?<![\p{L}\d\/\-])(?:';
	if ( '' !== $maps['law_re'] ) {
		// 0) "Điều 9 [khoản..] (của) Luật Khoa học, công nghệ và đổi mới sáng tạo [năm 2025] [số 93/2025/QH15]" (12/11/2026).
		$p .= '(?<d0>Điều' . $sp . '(?<dn0>\d{1,3}[a-zđ]?)' . $sub . $sp . '(?:của' . $sp . ')?(?<ln0>' . $maps['law_re'] . ')(?:' . $sp . '(?:năm' . $sp . ')?(?<ly0>(?:19|20)\d{2}))?(?:' . $sp . 'số' . $sp . '(?<f0>' . $full . '))?)|';
	}
	$p   .= ''
		// 1) "Điều 12 [khoản..] (của) Nghị định 232/2026[/NĐ-CP]" hoặc với số hiệu đầy đủ.
		. '(?<d1>Điều' . $sp . '(?<dn>\d{1,3}[a-zđ]?)' . $sub . $sp . '(?:của' . $sp . ')?(?:(?<k1>' . $kind . ')' . $sp . '(?:số' . $sp . ')?)?(?:(?<f1>' . $full . ')|(?<s1>\d{1,4}\/\d{4}(?![\/\d])))' . ')'
		// 2) "Nghị định (số) 232/2026[/NĐ-CP]"
		. '|(?<d2>(?<k2>' . $kind . ')' . $sp . '(?:số' . $sp . ')?(?:(?<f2>' . $full . ')|(?<s2>\d{1,4}\/\d{4}(?![\/\d]))))'
		// 3) số hiệu đầy đủ đứng một mình
		. '|(?<f3>' . $full . ')';
	if ( '' !== $maps['law_re'] ) {
		// 4) tên Luật (kèm năm nếu có)
		$p .= '|(?<l4>(?<ln>' . $maps['law_re'] . ')(?:' . $sp . '(?:năm' . $sp . ')?(?<ly>(?:19|20)\d{2}))?)';
	}
	// 5) Hiến pháp 2013
	$p .= '|(?<h5>Hiến' . $sp . 'pháp(?:' . $sp . 'nước[^.,;()<>]{0,60}?)?(?:' . $sp . 'năm)?' . $sp . '2013)';
	return $p . ')(?![\p{L}\d])~u';
}

/** Xử lý một đoạn văn bản (đã ở dạng HTML, không chứa thẻ). */
function cvc_ll_chunk( string $s, array &$seen ): string {
	if ( '' === trim( $s ) || ! preg_match( '/\d|Luật|luật/u', $s ) ) {
		return $s;
	}
	$maps = cvc_ll_maps();
	if ( ! $maps['full'] ) {
		return $s;
	}
	$self = (string) $GLOBALS['cvc_ll']['self'];
	return (string) preg_replace_callback(
		cvc_ll_pattern(),
		static function ( $m ) use ( &$seen, $self, $maps ) {
			$row    = null;
			$anchor = '';
			$text   = $m[0];
			$kind   = '';
			$art = '';
			if ( ! empty( $m['d0'] ) ) {
				$art = (string) $m['dn0'];
				if ( ! empty( $m['f0'] ) ) {
					$row = cvc_ll_find_full( $m['f0'] );
				} else {
					$key0  = mb_strtolower( preg_replace( '/\s+/u', ' ', $m['ln0'] ), 'UTF-8' );
					$cands = $maps['laws'][ $key0 ] ?? array();
					$all0  = $cands;
					if ( '' !== ( $m['ly0'] ?? '' ) ) {
						$cands = array_values( array_filter( $cands, static fn ( $c ) => $c['year'] === $m['ly0'] ) );
					}
					if ( 1 === count( $cands ) && ( '' !== ( $m['ly0'] ?? '' ) || 1 === count( $all0 ) ) ) {
						$row = $cands[0]['row'];
					}
				}
			} elseif ( ! empty( $m['f3'] ) ) {
				$row = cvc_ll_find_full( $m['f3'] );
				if ( ! $row ) {
					cvc_ll_note_missing( $m['f3'] );
				}
			} elseif ( ! empty( $m['d2'] ) ) {
				if ( ! empty( $m['f2'] ) ) {
					$row = cvc_ll_find_full( $m['f2'] );
					if ( ! $row ) {
						cvc_ll_note_missing( $m['f2'] );
					}
				} elseif ( ! empty( $m['s2'] ) ) {
					$row = cvc_ll_find_short( $m['s2'], $m['k2'] );
				}
			} elseif ( ! empty( $m['d1'] ) ) {
				if ( ! empty( $m['f1'] ) ) {
					$row = cvc_ll_find_full( $m['f1'] );
					if ( ! $row ) {
						cvc_ll_note_missing( $m['f1'] );
					}
				} elseif ( ! empty( $m['s1'] ) ) {
					$row = cvc_ll_find_short( $m['s1'], (string) ( $m['k1'] ?? '' ) );
				}
				if ( $row && ! empty( $m['dn'] ) ) {
					$art = (string) $m['dn'];
					if ( ! empty( $row['a'] ) ) {
						$anchor = '#dieu-' . sanitize_title( $m['dn'] );
					}
				}
			} elseif ( ! empty( $m['h5'] ) ) {
				$row = cvc_ll_find_full( 'Hiến pháp 2013' );
			} elseif ( ! empty( $m['l4'] ) ) {
				$cands = $maps['laws'][ mb_strtolower( preg_replace( '/\s+/u', ' ', $m['ln'] ), 'UTF-8' ) ] ?? array();
				$year  = $m['ly'] ?? '';
				if ( '' !== $year ) {
					$cands = array_values( array_filter( $cands, static fn ( $c ) => $c['year'] === $year ) );
				}
				if ( 1 === count( $cands ) && ( '' !== $year || 1 === count( $maps['laws'][ mb_strtolower( preg_replace( '/\s+/u', ' ', $m['ln'] ), 'UTF-8' ) ] ) ) ) {
					$row = $cands[0]['row'];
				}
			}
			if ( ! $row || ! empty( $row['dup'] ) ) {
				return $text;
			}
			// 12/11/2026: "Điều N <văn bản>" có bài học /kien-thuc/ → trỏ thẳng tới bài học của Điều đó (link hai chiều blog ↔ Điều luật).
			$lesson = '' !== $art ? cvc_ll_lesson_slug( (string) $row['s'], $art ) : '';
			if ( '' !== $lesson && $lesson !== (string) get_query_var( 'cvc_knowledge_slug' ) && ! isset( $seen[ $row['s'] . '#' . $lesson ] ) ) {
				$seen[ $row['s'] . '#' . $lesson ] = true;
				++$GLOBALS['cvc_ll']['emitted'];
				$title = 'Điều ' . $art . ' ' . trim( (string) ( $row['n'] ?? '' ) . ' — ' . (string) ( $row['t'] ?? '' ), ' —' );
				return '<a class="ll" href="' . esc_url( cvc_knowledge_item_url( $lesson ) ) . '" data-ll="' . esc_attr( (string) $row['s'] ) . '" title="' . esc_attr( $title ) . '">' . $text . '</a>';
			}
			if ( '' !== $self && $row['s'] === $self ) {
				return $text;
			}
			$sig = $row['s'] . $anchor;
			if ( isset( $seen[ $row['s'] ] ) ) {
				return $text;
			}
			$seen[ $row['s'] ] = true;
			++$GLOBALS['cvc_ll']['emitted'];
			$title = trim( (string) ( $row['n'] ?? '' ) . ' — ' . (string) ( $row['t'] ?? '' ), ' —' );
			$out   = '<a class="ll" href="' . esc_url( cvc_ll_url( $row, $anchor ) ) . '" data-ll="' . esc_attr( (string) $row['s'] ) . '" title="' . esc_attr( $title ) . '">' . $text . '</a>';
			if ( ! empty( $row['r'] ) && ! empty( $row['rn'] ) ) {
				$out .= '<small class="ll-rep"> (đã được thay thế bởi ' . esc_html( (string) $row['rn'] ) . ')</small>';
			}
			return $out;
		},
		$s
	);
}

/** Gắn link trong chuỗi HTML. */
function cvc_ll_html( string $html ): string {
	if ( '' === $html || ! preg_match( '/\d|Luật/u', $html ) ) {
		return $html;
	}
	$parts = preg_split( '/(<!--.*?-->|<[^>]+>)/us', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $parts ) ) {
		return $html;
	}
	$skip_tags = array( 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'script', 'style', 'code', 'pre', 'button', 'textarea', 'select', 'option', 'nav', 'figcaption', 'noscript', 'svg', 'summary', 'label' );
	$block     = array( 'p', 'li', 'td', 'th', 'div', 'tr', 'section', 'article', 'blockquote', 'dd', 'dt', 'ul', 'ol', 'table', 'br' );
	$depth     = array_fill_keys( $skip_tags, 0 );
	$no_depth  = 0;
	$stack     = array();
	$seen      = array();
	$out       = '';
	foreach ( $parts as $i => $part ) {
		if ( 1 === $i % 2 ) { // thẻ hoặc chú thích
			if ( preg_match( '#^<(/?)([a-z0-9]+)([^>]*)>$#i', $part, $t ) ) {
				$close = '/' === $t[1];
				$name  = strtolower( $t[2] );
				$self_close = '/' === substr( rtrim( $part, '> ' ), -1 ) || in_array( $name, array( 'br', 'img', 'hr', 'input', 'meta', 'link' ), true );
				if ( in_array( $name, $block, true ) ) {
					$seen = array();
				}
				if ( isset( $depth[ $name ] ) && ! $self_close ) {
					$depth[ $name ] += $close ? ( $depth[ $name ] > 0 ? -1 : 0 ) : 1;
				}
				if ( ! $close && ! $self_close && false !== stripos( $t[3], 'no-autolink' ) ) {
					$stack[] = $name;
					++$no_depth;
				} elseif ( $close && $no_depth && $stack && end( $stack ) === $name ) {
					array_pop( $stack );
					--$no_depth;
				}
			}
			$out .= $part;
			continue;
		}
		$in_skip = $no_depth > 0 || array_sum( $depth ) > 0;
		$out    .= $in_skip ? $part : cvc_ll_chunk( $part, $seen );
	}
	return $out;
}

/** Văn bản thuần -> HTML escape + link. $nl2br: giữ xuống dòng. */
function cvc_ll_text( string $plain, bool $nl2br = false ): string {
	$html = esc_html( $plain );
	if ( $nl2br ) {
		$html = nl2br( $html );
	}
	return cvc_ll_html( $html );
}

/* ------------------------------ Bộ lọc nội dung WordPress ------------------------------ */
add_filter(
	'the_content',
	static function ( $content ) {
		if ( is_admin() || is_feed() || ! is_singular( array( 'post', 'page' ) ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return cvc_ll_html( (string) $content );
	},
	30
);

/* ------------------------------ CSS + sự kiện GA4 ------------------------------ */
add_action(
	'wp_footer',
	static function () {
		if ( empty( $GLOBALS['cvc_ll']['emitted'] ) ) {
			return;
		}
		?>
<style id="cvc-ll-css">a.ll{color:inherit;text-decoration:underline dotted;text-decoration-color:#2563eb;text-underline-offset:3px}a.ll:hover{color:#1d4ed8;text-decoration-style:solid}.ll-rep{color:#6b7280;font-size:.85em}</style>
<script>
(function(){document.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('a.ll');if(!a)return;
window.dataLayer=window.dataLayer||[];function g(){window.dataLayer.push(arguments)}
g('event','legal_autolink_click',{doc_slug:a.getAttribute('data-ll')||'',from_path:location.pathname});},true);})();
</script>
		<?php
	},
	50
);

/* ------------------------------ Báo cáo số hiệu chưa có trong thư viện ------------------------------ */
add_action(
	'admin_menu',
	static function () {
		add_management_page(
			'Số hiệu chưa có trong thư viện',
			'Số hiệu thiếu (autolink)',
			'manage_options',
			'cvc-ll-missing',
			static function () {
				$store = get_option( 'cvc_ll_missing', array() );
				$store = is_array( $store ) ? $store : array();
				uasort( $store, static fn ( $a, $b ) => $b['c'] <=> $a['c'] );
				echo '<div class="wrap"><h1>Số hiệu văn bản được nhắc nhưng chưa có trong thư viện</h1><p>Số lượt là ước lượng (lấy mẫu). Ưu tiên nhập các văn bản đứng đầu danh sách.</p><table class="widefat striped" style="max-width:640px"><thead><tr><th>Số hiệu</th><th>Lượt nhắc (ước lượng)</th><th>Thấy lần đầu</th></tr></thead><tbody>';
				foreach ( array_slice( $store, 0, 200, true ) as $k => $v ) {
					echo '<tr><td>' . esc_html( $k ) . '</td><td>' . (int) $v['c'] . '</td><td>' . esc_html( wp_date( 'd/m/Y', (int) $v['first'] ) ) . '</td></tr>';
				}
				echo '</tbody></table></div>';
			}
		);
	}
);
