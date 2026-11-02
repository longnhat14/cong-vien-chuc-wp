<?php
/**
 * KHỐI TỰ CẬP NHẬT TRONG BÀI VIẾT (02/11/2026, claude/ke-hoach-seo-tu-khoa-goc.md):
 *  - [cvc_khoi_tuyen_dung so="5" loai="civil|public" tinh="<id>" nhom="education" thong_ke="1"]
 *  - [cvc_khoi_on_thi so="3" lam_thu="1"]
 *  - [cvc_khoi_sach so="3"]
 * Plugin PCTech AI SEO (Khối chèn tự động) đặt các shortcode này vào bài lúc hiển thị; cũng dùng tay được.
 * Không ghi thuộc tính → tự nhận ngữ cảnh từ tiêu đề + từ khóa chính (Rank Math) + chuyên mục của bài:
 * loại (công chức / viên chức), nhóm ngành, tỉnh / thành. Không có tin khớp → nới dần (bỏ ngành → bỏ tỉnh → bỏ loại).
 * HTML render sẵn phía máy chủ (bot AI không chạy JS), dữ liệu lưu đệm 30 phút. Tiêu đề khối dùng h3 (mục lục bài chỉ lấy h2).
 * Không gắn JobPosting schema (Google chỉ cho trang chi tiết tin) và không đổi ngày cập nhật của bài.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_PB_CACHE = 1800;

/** Gọi API công khai có lưu đệm (không token: khối giống nhau cho mọi người, trang được LiteSpeed lưu đệm). */
function cvc_pb_api( string $path, array $query ): array {
	if ( function_exists( 'cvc_ci_api' ) ) {
		$r = cvc_ci_api( $path, $query, null, 8, CVC_PB_CACHE );
	} else {
		$r = ( new CVC_Api_Client( null, 8 ) )->get( $path, $query );
	}
	return $r['ok'] ? (array) ( $r['data']['data'] ?? array() ) : array();
}

function cvc_pb_ascii( string $s ): string {
	return trim( (string) preg_replace( '/\s+/', ' ', strtolower( remove_accents( $s ) ) ) );
}

/** Danh sách tỉnh / thành + số tin đang mở (api/career/overview). */
function cvc_pb_overview(): array {
	static $ov = null;
	if ( null === $ov ) {
		$ov = cvc_pb_api( '/api/career/overview', array() );
	}
	return $ov;
}

/**
 * Ngữ cảnh của bài: kind (civil|public|''), family, province_id, province, study (bài về ôn thi).
 *
 * @return array{kind:string, family:string, province_id:int, province:string, study:bool}
 */
function cvc_pb_context( int $post_id ): array {
	static $memo = array();
	if ( isset( $memo[ $post_id ] ) ) {
		return $memo[ $post_id ];
	}
	$parts = array( (string) get_the_title( $post_id ), (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) );
	foreach ( (array) get_the_category( $post_id ) as $c ) {
		$parts[] = (string) $c->name;
	}
	$t = ' ' . cvc_pb_ascii( implode( ' | ', $parts ) ) . ' ';

	$civil  = str_contains( $t, 'cong chuc' );
	$public = str_contains( $t, 'vien chuc' );
	$kind   = $civil && ! $public ? 'civil' : ( $public && ! $civil ? 'public' : '' );

	$families = array(
		'education'   => array( 'giao vien', 'giao duc', 'mam non', 'tieu hoc', 'thcs', 'thpt', 'nha giao', 'truong hoc' ),
		'health'      => array( 'y te', 'bac si', 'dieu duong', 'duoc si', 'benh vien', 'ho sinh', 'ky thuat vien y' ),
		'finance'     => array( 'ke toan', 'tai chinh', 'thue', 'kho bac', 'ngan sach' ),
		'justice'     => array( 'tu phap', 'phap che', 'thi hanh an' ),
		'it'          => array( 'cong nghe thong tin', 'cntt', 'chuyen doi so' ),
		'culture'     => array( 'van hoa', 'the thao', 'du lich' ),
		'agri_forest' => array( 'nong nghiep', 'kiem lam', 'moi truong', 'tai nguyen', 'thuy loi' ),
		'admin'       => array( 'van thu', 'hanh chinh', 'van phong' ),
	);
	$family = '';
	foreach ( $families as $code => $words ) {
		foreach ( $words as $w ) {
			if ( preg_match( '/(?<![a-z])' . preg_quote( $w, '/' ) . '(?![a-z])/', $t ) ) {
				$family = $code;
				break 2;
			}
		}
	}

	$pid   = 0;
	$pname = '';
	$alias = array( 'tp hcm' => 'ho chi minh', 'tphcm' => 'ho chi minh', 'sai gon' => 'ho chi minh', 'hcm' => 'ho chi minh' );
	$tt    = $t;
	foreach ( $alias as $a => $b ) {
		$tt = str_replace( ' ' . $a . ' ', ' ' . $b . ' ', $tt );
	}
	foreach ( (array) ( cvc_pb_overview()['provinces'] ?? array() ) as $p ) {
		$short = cvc_pb_ascii( (string) preg_replace( '/^(Tỉnh|Thành phố)\s+/u', '', (string) ( $p['name'] ?? '' ) ) );
		if ( '' !== $short && preg_match( '/(?<![a-z])' . preg_quote( $short, '/' ) . '(?![a-z])/', $tt ) ) {
			$pid   = (int) $p['id'];
			$pname = (string) $p['name'];
			break;
		}
	}

	$study = (bool) preg_match( '/on thi|de thi|thi thu|trac nghiem|cau hoi|tai lieu|meo thi|kien thuc chung|vong 1|vong 2/', $t );

	return $memo[ $post_id ] = array( 'kind' => $kind, 'family' => $family, 'province_id' => $pid, 'province' => $pname, 'study' => $study );
}

/** Thuộc tính shortcode ghi đè ngữ cảnh tự nhận. */
function cvc_pb_ctx_from_atts( array $a ): array {
	$ctx = cvc_pb_context( (int) get_the_ID() );
	if ( isset( $a['loai'] ) && in_array( $a['loai'], array( 'civil', 'public', 'all' ), true ) ) {
		$ctx['kind'] = 'all' === $a['loai'] ? '' : $a['loai'];
	}
	if ( isset( $a['nhom'] ) && '' !== $a['nhom'] ) {
		$ctx['family'] = 'all' === $a['nhom'] ? '' : sanitize_key( $a['nhom'] );
	}
	if ( isset( $a['tinh'] ) && '' !== $a['tinh'] ) {
		$ctx['province_id'] = absint( $a['tinh'] );
		$ctx['province']    = '';
		foreach ( (array) ( cvc_pb_overview()['provinces'] ?? array() ) as $p ) {
			if ( (int) $p['id'] === $ctx['province_id'] ) {
				$ctx['province'] = (string) $p['name'];
			}
		}
	}
	return $ctx;
}

function cvc_pb_noun( string $kind ): string {
	return 'civil' === $kind ? 'công chức' : ( 'public' === $kind ? 'viên chức' : 'công chức, viên chức' );
}

function cvc_pb_family_label( string $family ): string {
	foreach ( (array) ( cvc_pb_overview()['families'] ?? array() ) as $f ) {
		if ( ( $f['code'] ?? '' ) === $family ) {
			return (string) ( $f['label'] ?? $f['name'] ?? '' );
		}
	}
	return '';
}

function cvc_pb_stamp(): string {
	return '<p class="cvc-pb-stamp">Cập nhật tự động lúc ' . esc_html( wp_date( 'H:i d/m/Y' ) ) . ' từ dữ liệu Công Viên Chức.</p>';
}

/** Lượt bấm trong khối: data-pb để đo (GA / log), không dùng UTM nội bộ. */
function cvc_pb_open( string $kind, string $title, string $sub = '' ): string {
	return '<aside class="cvc-pb cvc-pb--' . esc_attr( $kind ) . '" data-pb="' . esc_attr( $kind ) . '"><h3 class="cvc-pb-h">' . esc_html( $title ) . '</h3>'
		. ( '' !== $sub ? '<p class="cvc-pb-sub">' . esc_html( $sub ) . '</p>' : '' );
}

/* ------------------------------------------------------------------ Tuyển dụng */

function cvc_pb_recruitments( array $ctx, int $n ): array {
	$try = array(
		array( $ctx['kind'], $ctx['family'], $ctx['province_id'] ),
		array( $ctx['kind'], '', $ctx['province_id'] ),
		array( '', '', $ctx['province_id'] ), // bài theo tỉnh: ưu tiên tin cùng tỉnh (mọi loại) hơn tin cùng loại toàn quốc.
		array( $ctx['kind'], $ctx['family'], 0 ),
		array( $ctx['kind'], '', 0 ),
		array( '', '', 0 ),
	);
	$seen = array();
	foreach ( $try as [ $kind, $family, $pid ] ) {
		$k = $kind . '|' . $family . '|' . $pid;
		if ( isset( $seen[ $k ] ) ) {
			continue;
		}
		$seen[ $k ] = true;
		$q          = array_filter(
			array(
				'kind'        => $kind ? array( $kind ) : null,
				'job_family'  => $family ?: null,
				'province_id' => $pid ?: null,
				'status'      => 'open',
				'sort'        => 'newest',
				'per_page'    => $n,
			)
		);
		$d     = cvc_pb_api( '/api/career/recruitments', $q );
		$items = array_values( (array) ( $d['items'] ?? array() ) );
		if ( $items ) {
			return array( 'items' => $items, 'total' => (int) ( $d['pagination']['total'] ?? count( $items ) ), 'kind' => $kind, 'family' => $family, 'province_id' => $pid );
		}
	}
	return array( 'items' => array(), 'total' => 0, 'kind' => '', 'family' => '', 'province_id' => 0 );
}

function cvc_pb_deadline( array $it ): string {
	$st = (array) ( $it['status'] ?? array() );
	if ( ! empty( $st['deadline'] ) ) {
		$d = wp_date( 'd/m/Y', strtotime( (string) $st['deadline'] ) );
		return 'Hạn nộp ' . $d . ( isset( $st['days_left'] ) && null !== $st['days_left'] && (int) $st['days_left'] <= 7 ? ' (còn ' . (int) $st['days_left'] . ' ngày)' : '' );
	}
	return (string) ( $st['label'] ?? '' );
}

function cvc_pb_recruitment_list_url( string $kind, string $family, int $pid ): string {
	if ( ! function_exists( 'cvc_ci_url' ) ) {
		return home_url( '/tuyen-dung/' );
	}
	if ( ! $family && ! $pid && $kind && function_exists( 'cvc_rec_preset_url' ) ) {
		return cvc_rec_preset_url( 'civil' === $kind ? 'cong-chuc' : 'vien-chuc' );
	}
	return cvc_ci_url( array( 'loai' => $kind ? array( $kind ) : null, 'nhom' => $family ?: null, 'tinh' => $pid ?: null ) );
}

function cvc_pb_tuyen_dung( $atts ): string {
	$a   = shortcode_atts( array( 'so' => 5, 'loai' => null, 'tinh' => null, 'nhom' => null, 'thong_ke' => 0, 'tieu_de' => '' ), (array) $atts );
	$ctx = cvc_pb_ctx_from_atts( array_filter( $a, static fn ( $v ) => null !== $v ) );
	$n   = max( 1, min( 12, (int) $a['so'] ) );
	$r   = cvc_pb_recruitments( $ctx, $n );
	if ( ! $r['items'] ) {
		return '';
	}
	cvc_pb_assets();
	$noun  = cvc_pb_noun( $r['kind'] );
	$where = $r['province_id'] && $ctx['province'] ? ' tại ' . $ctx['province'] : '';
	$fam   = $r['family'] ? cvc_pb_family_label( $r['family'] ) : '';
	$title = '' !== $a['tieu_de'] ? (string) $a['tieu_de'] : 'Tin tuyển ' . $noun . ( $fam ? ' ngành ' . mb_strtolower( $fam ) : '' ) . $where . ' đang nhận hồ sơ';
	$sub   = number_format( $r['total'], 0, ',', '.' ) . ' thông báo đang mở. Tin mới nhất:';
	$h     = cvc_pb_open( 'tuyen-dung', $title, $sub ) . '<ol class="cvc-pb-list">';
	foreach ( $r['items'] as $it ) {
		$meta = array_filter(
			array(
				(string) ( $it['agency']['name'] ?? '' ),
				(int) ( $it['total_positions'] ?? 0 ) > 0 ? (int) $it['total_positions'] . ' chỉ tiêu' : '',
				cvc_pb_deadline( (array) $it ),
			)
		);
		$h .= '<li><a href="' . esc_url( cvc_recruitment_url( (string) $it['slug'] ) ) . '">' . esc_html( (string) $it['title'] ) . '</a>'
			. ( $meta ? '<span class="cvc-pb-meta">' . esc_html( implode( ' · ', $meta ) ) . '</span>' : '' ) . '</li>';
	}
	$h .= '</ol>';
	if ( ! empty( $a['thong_ke'] ) ) {
		$prov = array_filter( (array) ( cvc_pb_overview()['provinces'] ?? array() ), static fn ( $p ) => (int) ( $p['open_count'] ?? 0 ) > 0 );
		usort( $prov, static fn ( $x, $y ) => (int) $y['open_count'] <=> (int) $x['open_count'] );
		if ( $prov ) {
			$h .= '<p class="cvc-pb-sub">Số thông báo đang mở theo địa phương (mọi loại):</p><ul class="cvc-pb-chips">';
			foreach ( array_slice( $prov, 0, 12 ) as $p ) {
				$h .= '<li><a href="' . esc_url( cvc_pb_recruitment_list_url( '', '', (int) $p['id'] ) ) . '">' . esc_html( preg_replace( '/^(Tỉnh|Thành phố)\s+/u', '', (string) $p['name'] ) ) . ' <b>' . (int) $p['open_count'] . '</b></a></li>';
			}
			$h .= '</ul>';
		}
	}
	$h .= '<p class="cvc-pb-more"><a href="' . esc_url( cvc_pb_recruitment_list_url( $r['kind'], $r['family'], $r['province_id'] ) ) . '">Xem tất cả ' . number_format( $r['total'], 0, ',', '.' ) . ' tin tuyển ' . esc_html( $noun ) . esc_html( $where ) . ' →</a></p>';

	return $h . cvc_pb_stamp() . '</aside>';
}

/* ------------------------------------------------------------------ Ôn thi */

/**
 * Đề cho khối Ôn thi: tìm theo tỉnh của bài → "kiến thức chung" → theo ngành; ưu tiên đề có đúng chữ công chức / viên chức,
 * mỗi bộ đề chỉ lấy 1 (bỏ "Đề 1/2/3" cùng bộ) để khối đa dạng. @return array{0:array, 1:int}
 */
function cvc_pb_exams( array $ctx, int $n ): array {
	$cands = array();
	if ( $ctx['province'] ) {
		$cands[] = (string) preg_replace( '/^(Tỉnh|Thành phố)\s+/u', '', $ctx['province'] );
	}
	if ( ! $ctx['family'] ) {
		$cands[] = 'kiến thức chung';
	}
	$cands[] = '';
	$noun    = 'civil' === $ctx['kind'] ? 'cong chuc' : ( 'public' === $ctx['kind'] ? 'vien chuc' : '' );
	foreach ( $cands as $kw ) {
		$q = array_filter( array( 'q' => $kw ?: null, 'job_family' => $ctx['family'] ?: null, 'sort' => 'relevance', 'per_page' => 20 ) );
		$d = cvc_pb_api( '/api/career/exams', $q );
		$rows = array_values( (array) ( $d['items'] ?? array() ) );
		if ( ! $rows ) {
			continue;
		}
		usort( $rows, static fn ( $a, $b ) => (int) ( '' !== $noun && str_contains( cvc_pb_ascii( (string) $b['title'] ), $noun ) ) <=> (int) ( '' !== $noun && str_contains( cvc_pb_ascii( (string) $a['title'] ), $noun ) ) );
		$out  = array();
		$sets = array();
		foreach ( $rows as $r ) {
			$base = cvc_pb_ascii( (string) preg_replace( '/[\s(–-]*Đề\s*\d+\)?/u', '', (string) $r['title'] ) );
			if ( isset( $sets[ $base ] ) ) {
				continue;
			}
			$sets[ $base ] = true;
			$out[]         = $r;
			if ( count( $out ) >= $n ) {
				break;
			}
		}
		return array( $out, (int) ( $d['pagination']['total'] ?? count( $out ) ) );
	}
	return array( array(), 0 );
}

function cvc_pb_on_thi( $atts ): string {
	$a   = shortcode_atts( array( 'so' => 3, 'loai' => null, 'nhom' => null, 'lam_thu' => 1, 'tieu_de' => '' ), (array) $atts );
	$ctx = cvc_pb_ctx_from_atts( array_filter( $a, static fn ( $v ) => null !== $v ) );
	$n   = max( 1, min( 8, (int) $a['so'] ) );
	[ $items, $total ] = cvc_pb_exams( $ctx, $n );
	$logged = function_exists( 'cvc_auth_token' ) && cvc_auth_token();
	if ( ! $items && $logged ) {
		return '';
	}
	cvc_pb_assets();
	$noun  = cvc_pb_noun( $ctx['kind'] );
	$title = '' !== $a['tieu_de'] ? (string) $a['tieu_de'] : 'Ôn thi ' . $noun . ': làm đề có chấm điểm ngay';
	$h     = cvc_pb_open( 'on-thi', $title, $total > 0 ? number_format( $total, 0, ',', '.' ) . ' đề thi thử / luyện thi, có giải thích và căn cứ pháp luật.' : '' );
	if ( $items ) {
		$h .= '<ol class="cvc-pb-list">';
		foreach ( $items as $it ) {
			$meta = array_filter( array( (string) ( $it['type_label'] ?? '' ), (int) ( $it['total_questions'] ?? 0 ) ? (int) $it['total_questions'] . ' câu' : '', (int) ( $it['duration_minutes'] ?? 0 ) ? (int) $it['duration_minutes'] . ' phút' : '' ) );
			$h   .= '<li><a href="' . esc_url( cvc_exam_url( (string) $it['slug'] ) ) . '">' . esc_html( (string) $it['title'] ) . '</a>'
				. ( $meta ? '<span class="cvc-pb-meta">' . esc_html( implode( ' · ', $meta ) ) . '</span>' : '' ) . '</li>';
		}
		$h .= '</ol>';
	}
	if ( $logged && function_exists( 'cvc_study_url' ) ) {
		$h .= '<p class="cvc-pb-more"><a href="' . esc_url( cvc_study_url() ) . '" rel="nofollow">Làm Bài ôn hôm nay (10 câu, khoảng 7 phút) →</a></p>';
	} elseif ( ! empty( $a['lam_thu'] ) && function_exists( 'cvc_trial_widget' ) ) {
		ob_start();
		cvc_trial_widget(
			array(
				'source' => 'blog',
				'ref'    => (string) get_post_field( 'post_name', (int) get_the_ID() ),
				'title'  => 'Thử 5 câu trắc nghiệm ngay, không cần đăng nhập',
				'htag'   => 'h4',
			)
		);
		$h .= (string) ob_get_clean();
	}
	$h .= '<p class="cvc-pb-more"><a href="' . esc_url( function_exists( 'cvc_exams_mode_url' ) ? cvc_exams_mode_url( 'thi-that' ) : home_url( '/thi-thu-trac-nghiem/' ) ) . '">Xem tất cả đề thi thử →</a></p>';

	return $h . cvc_pb_stamp() . '</aside>';
}

/* ------------------------------------------------------------------ Sách / cẩm nang */

function cvc_pb_sach( $atts ): string {
	if ( ! function_exists( 'cvc_books' ) ) {
		return '';
	}
	$a    = shortcode_atts( array( 'so' => 3, 'loai' => null, 'nhom' => null, 'tieu_de' => '' ), (array) $atts );
	$ctx  = cvc_pb_ctx_from_atts( array_filter( $a, static fn ( $v ) => null !== $v ) );
	$n    = max( 1, min( 6, (int) $a['so'] ) );
	$view = 'civil' === $ctx['kind'] ? 'cong-chuc' : ( 'public' === $ctx['kind'] ? 'vien-chuc' : 'all' );
	$q    = array_filter( array( 'family' => $ctx['family'] ?: null, 'sort' => 'doc' ) );
	$res  = cvc_books( $view, $q, $n );
	if ( ! $res['items'] && $q ) {
		$res = cvc_books( $view, array( 'sort' => 'doc' ), $n );
	}
	if ( ! $res['items'] ) {
		$res = cvc_books( 'all', array( 'sort' => 'doc' ), $n );
	}
	if ( ! $res['items'] ) {
		return '';
	}
	cvc_pb_assets();
	$title = '' !== $a['tieu_de'] ? (string) $a['tieu_de'] : 'Sách & cẩm nang ôn thi ' . cvc_pb_noun( $ctx['kind'] );
	$h     = cvc_pb_open( 'sach', $title, 'Đọc trực tuyến, nhiều cuốn miễn phí.' ) . '<ul class="cvc-pb-books">';
	foreach ( $res['items'] as $it ) {
		$img  = (string) ( $it['card'] ?? $it['cover'] ?? '' );
		$meta = function_exists( 'cvc_book_meta' ) ? cvc_book_meta( (array) $it ) : '';
		$href = function_exists( 'cvc_book_href' ) ? cvc_book_href( (array) $it ) : cvc_document_url( (string) $it['slug'] );
		$h   .= '<li>' . ( '' !== $img ? '<a class="cvc-pb-cover" href="' . esc_url( $href ) . '" tabindex="-1" aria-hidden="true"><img src="' . esc_url( $img ) . '" alt="" loading="lazy" decoding="async" width="96" height="128"></a>' : '' )
			. '<div><a class="cvc-pb-bt" href="' . esc_url( $href ) . '">' . esc_html( (string) $it['title'] ) . '</a>'
			. ( '' !== $meta ? '<span class="cvc-pb-meta">' . esc_html( $meta ) . '</span>' : '' )
			. ( ! empty( $it['description'] ) ? '<span class="cvc-pb-desc">' . esc_html( wp_trim_words( (string) $it['description'], 22 ) ) . '</span>' : '' )
			. '<span class="cvc-pb-cta">' . esc_html( function_exists( 'cvc_book_cta' ) ? cvc_book_cta( (array) $it ) : 'Xem sách' ) . ' →</span></div></li>';
	}
	$h .= '</ul><p class="cvc-pb-more"><a href="' . esc_url( function_exists( 'cvc_book_tu_sach_url' ) ? cvc_book_tu_sach_url() : home_url( '/tai-lieu/' ) ) . '">Xem cả tủ sách →</a></p>';

	return $h . '</aside>';
}

add_shortcode( 'cvc_khoi_tuyen_dung', 'cvc_pb_tuyen_dung' );
add_shortcode( 'cvc_khoi_on_thi', 'cvc_pb_on_thi' );
add_shortcode( 'cvc_khoi_sach', 'cvc_pb_sach' );

/* ------------------------------------------------------------------ Giao diện (kiểu văn bản, CLAUDE.md 16/10/2026) */

/** CSS khối: nạp trong <head> ở trang bài viết (tránh nháy); gọi trong nội dung chỉ để chắc chắn đã đăng ký. */
function cvc_pb_css(): string {
	return '.cvc-pb{margin:28px 0;padding:16px 18px;border:1px solid #8fb4da;border-left:4px solid #123a63;border-radius:3px;background:#fff;color:#111}'
		. '.cvc-pb-h{margin:0 0 4px!important;font-size:19px!important;line-height:1.35;color:#123a63}'
		. '.cvc-pb-sub{margin:0 0 8px!important;font-size:14.5px;color:#5c5c5c}'
		. '.cvc-pb-list{margin:0!important;padding-left:22px!important}.cvc-pb-list li{margin:0 0 8px!important;line-height:1.45}'
		. '.cvc-pb-list a,.cvc-pb-bt{font-weight:700;color:#123a63;text-decoration:underline;text-underline-offset:3px}'
		. '.cvc-pb-meta,.cvc-pb-desc{display:block;font-size:13.5px;color:#5c5c5c;line-height:1.45}'
		. '.cvc-pb-more{margin:10px 0 0!important;font-weight:700}.cvc-pb-more a{color:#1767aa}'
		. '.cvc-pb-stamp{margin:8px 0 0!important;font-size:12.5px;color:#5c5c5c}'
		. '.cvc-pb-chips{display:flex;flex-wrap:wrap;gap:6px;margin:0!important;padding:0!important;list-style:none}.cvc-pb-chips li{margin:0!important}'
		. '.cvc-pb-chips a{display:inline-block;padding:4px 8px;border:1px solid #8fb4da;border-radius:3px;background:#eaf3fc;font-size:13.5px;color:#123a63;text-decoration:none}'
		. '.cvc-pb-books{display:grid;gap:12px;margin:0!important;padding:0!important;list-style:none}.cvc-pb-books li{display:flex;gap:12px;margin:0!important}'
		. '.cvc-pb-cover img{display:block;width:72px;height:auto;border:1px solid #ddd;border-radius:2px}.cvc-pb-cta{display:inline-block;margin-top:4px;font-size:14px;font-weight:700;color:#1767aa}'
		. '.cvc-pb .tr-card{margin:12px 0 0}';
}

function cvc_pb_assets(): void {
	if ( ! wp_style_is( 'cvc-pb', 'enqueued' ) ) {
		wp_register_style( 'cvc-pb', false, array(), '1' );
		wp_enqueue_style( 'cvc-pb' );
		wp_add_inline_style( 'cvc-pb', cvc_pb_css() );
	}
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_singular( 'post' ) ) {
			cvc_pb_assets();
		}
	},
	20
);
