<?php
/**
 * Menu chính quản lý trong WordPress (28/10/2026): Giao diện → Menu → vị trí "Primary Menu".
 *
 * Quy ước khi sửa menu (không cần sửa code):
 *  - Mục cấp 1: Nhãn = tên mục; "Mô tả" = dòng chú thích nhỏ; "Lớp CSS" = icon-exams | icon-rec | icon-legal | icon-docs | icon-posts.
 *    Thêm lớp "tu-dong-tuyen-dung" để mục tự sinh menu con Tuyển dụng (tin ngoài công lập, Đăng tin theo tài khoản đơn vị)
 *    khi mục đó chưa có menu con.
 *  - Mục cấp 2: "Lớp CSS" = nhom-luyen-tap | nhom-danh-gia | nhom-hoc-them -> menu lớn 2 cột + hàng "Học thêm";
 *    "Mô tả" = dòng mô tả; "Thuộc tính tiêu đề" (title attribute) = ký hiệu 1 ký tự (✎ ◷ ▤ ...).
 *    Nhãn dạng "Tên | Nhãn nhỏ" -> hiện nhãn nhỏ (vd. "Đăng tin tuyển dụng | Miễn phí").
 *    Nhóm "nhom-doi-tuong" = hàng "Theo đối tượng" trong menu lớn (như hàng "Học thêm").
 *    Lớp "tu-dong-dang-tin": đường dẫn tự theo tài khoản (đơn vị -> màn hình đăng tin), nhãn "Miễn phí" khi chưa thu phí,
 *    ẩn khi tắt tính năng đơn vị. Lớp "an-khi-tat-ngoai-cong-lap": ẩn khi chưa bật tin ngoài công lập.
 *  - Menu con v3 (31/10/2026): thêm nhóm "nhom-theo-loai" / "nhom-theo-nganh" (cột menu lớn Tuyển dụng), "nhom-doi-tuong"
 *    thành cột thẻ; lớp "ico-<tên>" chọn icon (xem cvc_nav_icon); "dem-tin" hiện số tin đang mở theo URL;
 *    "the-noi-bat" = thẻ điểm nhấn bên phải (Nhãn "Dòng nhỏ | Chữ nút", Thuộc tính tiêu đề = dòng lớn, Mô tả = đoạn ngắn;
 *    "mau-dong" = nền đồng); "chan-trang" = liên kết ở chân menu (Mô tả = câu dẫn). Dòng lớn / mô tả nhận
 *    {tin-mo} {sap-het-han} {vi-tri} {tin-de-rieng} {de-rieng} = số liệu thật (cvc_nav_stats).
 *  - Chưa gán menu nào cho vị trí Primary -> dùng menu mặc định trong code (cvc_main_nav_items_default).
 * Màn hình Giao diện → Menu tự hiện các ô Mô tả / Lớp CSS / Thuộc tính tiêu đề.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_NAV_GROUPS = array(
	'nhom-luyen-tap'  => 'practice',
	'nhom-danh-gia'   => 'assess',
	'nhom-doi-tuong'  => 'audience',
	'nhom-theo-loai'  => 'type',
	'nhom-theo-nganh' => 'field',
	'nhom-hoc-them'   => 'more',
	'the-noi-bat'     => 'card',
	'chan-trang'      => 'foot',
);

/** Tách "Nhãn | Huy hiệu". @return array{0: string, 1: string} */
function cvc_nav_split_badge( string $title ): array {
	$parts = array_map( 'trim', explode( '|', html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 2 ) );

	return array( $parts[0], $parts[1] ?? '' );
}

/** Mục menu chính từ menu WordPress gán vào vị trí "primary"; null nếu chưa gán. */
function cvc_nav_from_wp_menu(): ?array {
	$locations = get_nav_menu_locations();
	$menu_id   = (int) ( $locations['primary'] ?? 0 );
	if ( $menu_id <= 0 ) {
		return null;
	}
	$items = wp_get_nav_menu_items( $menu_id, array( 'update_post_term_cache' => false ) );
	if ( ! is_array( $items ) || ! $items ) {
		return null;
	}
	$has_courses = function_exists( 'cvc_has_sellable_courses' ) && cvc_has_sellable_courses();
	$courses_url = function_exists( 'cvc_courses_url' ) ? untrailingslashit( cvc_courses_url() ) : '';
	$top         = array();
	$children    = array();
	foreach ( $items as $it ) {
		$classes          = array_filter( (array) $it->classes );
		list( $label, $b ) = cvc_nav_split_badge( (string) $it->title );
		$row              = array(
			'label'   => $label,
			'url'     => (string) $it->url,
			'desc'    => trim( html_entity_decode( wp_strip_all_tags( (string) $it->description ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ),
			'badge'   => $b,
			'classes' => $classes,
			'target'  => (string) $it->target,
		);
		if ( 0 === (int) $it->menu_item_parent ) {
			$key = 'link';
			foreach ( $classes as $c ) {
				if ( 0 === strpos( $c, 'icon-' ) ) {
					$key = substr( $c, 5 );
				}
			}
			$top[ (int) $it->ID ] = $row + array( 'key' => $key, 'hint' => $row['desc'], 'children' => array() );
		} else {
			$group = '';
			foreach ( $classes as $c ) {
				if ( isset( CVC_NAV_GROUPS[ $c ] ) ) {
					$group = CVC_NAV_GROUPS[ $c ];
				}
			}
			if ( '' === $row['badge'] && '' !== $courses_url && untrailingslashit( $row['url'] ) === $courses_url && ! $has_courses ) {
				$row['badge'] = 'Sắp mở';
			}
			if ( in_array( 'tu-dong-dang-tin', $classes, true ) || in_array( 'an-khi-tat-ngoai-cong-lap', $classes, true ) ) {
				$set = function_exists( 'cvc_employer_public_settings' ) ? cvc_employer_public_settings() : array();
				if ( in_array( 'an-khi-tat-ngoai-cong-lap', $classes, true ) && empty( $set['private_enabled'] ) ) {
					continue;
				}
				if ( in_array( 'tu-dong-dang-tin', $classes, true ) ) {
					if ( isset( $set['enabled'] ) && empty( $set['enabled'] ) ) {
						continue;
					}
					$emp        = ( function_exists( 'cvc_employer_of_current_user' ) && function_exists( 'cvc_is_logged_in' ) && cvc_is_logged_in() ) ? cvc_employer_of_current_user() : null;
					$row['url'] = $emp ? home_url( '/don-vi/dang-tin/' ) : home_url( '/don-vi-tuyen-dung/' );
					if ( in_array( 'the-noi-bat', $classes, true ) ) {
						// Thẻ nổi bật: phần sau "|" là chữ trên nút, giữ nguyên.
						$row['badge'] = '' !== $row['badge'] ? $row['badge'] : 'Đăng tin ngay';
					} elseif ( ! empty( $set['paid'] ) ) {
						$row['badge'] = '';
					} elseif ( '' === $row['badge'] ) {
						$row['badge'] = 'Miễn phí';
					}
				}
			}
			$icon = '';
			foreach ( $classes as $c ) {
				if ( 0 === strpos( $c, 'ico-' ) ) {
					$icon = substr( $c, 4 );
				}
			}
			$children[ (int) $it->menu_item_parent ][] = $row + array(
				'group'   => $group,
				'sym'     => trim( (string) $it->attr_title ),
				'icon'    => $icon,
				'count'   => in_array( 'dem-tin', $classes, true ),
				'variant' => in_array( 'mau-dong', $classes, true ) ? 'bronze' : '',
			);
		}
	}
	$out = array();
	foreach ( $top as $id => $nav ) {
		$nav['children'] = $children[ $id ] ?? array();
		if ( ! $nav['children'] && in_array( 'tu-dong-tuyen-dung', $nav['classes'], true ) && function_exists( 'cvc_rec_nav_item' ) ) {
			$nav['children'] = (array) ( cvc_rec_nav_item()['children'] ?? array() );
		}
		// Menu lớn 2 cột chỉ khi mọi mục con đều có nhóm.
		if ( $nav['children'] && array_filter( $nav['children'], static fn ( $k ) => '' === ( $k['group'] ?? '' ) ) ) {
			foreach ( $nav['children'] as &$k ) {
				unset( $k['group'] );
			}
			unset( $k );
		}
		$out[] = $nav;
	}

	return $out;
}

/** Hiện sẵn các ô Mô tả, Lớp CSS, Thuộc tính tiêu đề ở Giao diện → Menu. */
add_filter(
	'hidden_columns',
	static function ( $hidden, $screen ) {
		if ( is_object( $screen ) && 'nav-menus' === $screen->id ) {
			return array_values( array_diff( (array) $hidden, array( 'description', 'css-classes', 'title-attribute' ) ) );
		}
		return $hidden;
	},
	10,
	2
);

/** Gợi ý quy ước ngay trên màn hình Menu. */
add_action(
	'admin_notices',
	static function () {
		$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $s || 'nav-menus' !== $s->id ) {
			return;
		}
		echo '<div class="notice notice-info"><p><b>Menu chính Công Viên Chức</b> (vị trí "Primary Menu"). '
			. 'Mục cấp 1: <i>Mô tả</i> = dòng chú thích; <i>Lớp CSS</i> = <code>icon-exams</code> / <code>icon-rec</code> / <code>icon-legal</code> / <code>icon-docs</code> / <code>icon-posts</code>; '
			. 'thêm <code>tu-dong-tuyen-dung</code> để tự sinh menu con Tuyển dụng. '
			. 'Mục cấp 2: <i>Lớp CSS</i> = <code>nhom-luyen-tap</code> / <code>nhom-danh-gia</code> (2 cột menu lớn), <code>nhom-doi-tuong</code> (hàng "Theo đối tượng"), <code>nhom-hoc-them</code> (hàng "Học thêm"); <i>Thuộc tính tiêu đề</i> = ký hiệu (✎ ◷ ▤); '
			. '<code>tu-dong-dang-tin</code> = nút Đăng tin tự theo tài khoản; <code>an-khi-tat-ngoai-cong-lap</code> = ẩn khi chưa bật tin ngoài công lập; '
			. 'nhãn dạng <code>Tên | Nhãn nhỏ</code> để hiện huy hiệu. Bỏ gán vị trí Primary → web dùng menu mặc định.</p>'
			. '<p><b>Menu con v3:</b> <code>nhom-theo-loai</code> / <code>nhom-theo-nganh</code> = 2 cột menu Tuyển dụng; <code>ico-&lt;tên&gt;</code> = icon '
			. '(luyen-thi, thi-thu, ngan-hang, da-luu, pdf, xep-hang, tien-do, cong-chuc, vien-chuc, giao-vien, y-te, hop-dong, tat-ca, tim-vi-tri, ngoai-cong-lap, loa, sao, chu-de, van-ban, tai-lieu); '
			. '<code>dem-tin</code> = hiện số tin đang mở; <code>the-noi-bat</code> = thẻ điểm nhấn (Nhãn <code>Dòng nhỏ | Chữ nút</code>, Thuộc tính tiêu đề = dòng lớn, Mô tả = đoạn ngắn, thêm <code>mau-dong</code> = nền đồng); '
			. '<code>chan-trang</code> = liên kết ở chân menu (Mô tả = câu dẫn). Mã số liệu dùng được trong dòng lớn / mô tả: <code>{tin-mo}</code> <code>{sap-het-han}</code> <code>{vi-tri}</code> <code>{tin-de-rieng}</code> <code>{de-rieng}</code>.</p></div>';
	}
);

/* ------------------------------------------------------------------
 * Số liệu sống cho menu (31/10/2026): số tin đang mở theo trang, đề riêng theo tin.
 * Đọc từ transient (20 phút); hết hạn thì dùng bản cũ (option) và làm mới sau khi trả trang (shutdown).
 * ------------------------------------------------------------------ */
function cvc_nav_stats(): array {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}
	$fresh = get_transient( 'cvc_nav_stats_v1' );
	if ( is_array( $fresh ) ) {
		return $memo = $fresh;
	}
	$memo = (array) get_option( 'cvc_nav_stats_last', array() );
	if ( ! get_transient( 'cvc_nav_stats_lock' ) ) {
		set_transient( 'cvc_nav_stats_lock', 1, 120 );
		add_action( 'shutdown', 'cvc_nav_stats_refresh', 99 );
	}

	return $memo;
}

function cvc_nav_stats_refresh(): void {
	if ( function_exists( 'litespeed_finish_request' ) ) {
		litespeed_finish_request();
	} elseif ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}
	if ( ! class_exists( 'CVC_Api_Client' ) ) {
		delete_transient( 'cvc_nav_stats_lock' );
		return;
	}
	$get = static function ( string $path, array $q = array() ): ?array {
		$r = ( new CVC_Api_Client( null, 8 ) )->get( $path, $q, null );
		return ! empty( $r['ok'] ) ? (array) ( $r['data']['data'] ?? array() ) : null;
	};
	$ok = true;
	$s  = array( 'by_path' => array() );
	$ov = $get( '/api/career/overview' );
	if ( $ov ) {
		$s['tin-mo']      = (int) ( $ov['counts']['open_recruitments'] ?? 0 );
		$s['sap-het-han'] = (int) ( $ov['counts']['closing_soon'] ?? 0 );
		$s['vi-tri']      = (int) ( $ov['counts']['open_positions'] ?? 0 );
		$s['by_path']['/tuyen-dung/'] = $s['tin-mo'];
	} else {
		$ok = false;
	}
	foreach ( function_exists( 'cvc_rec_presets' ) ? cvc_rec_presets() : array() as $p ) {
		$h = $get( '/api/career/hub', (array) $p['api'] + array( 'limit' => 1 ) );
		if ( $h ) {
			$s['by_path'][ '/' . $p['path'] . '/' ] = (int) ( $h['counts']['open_recruitments'] ?? 0 );
		} else {
			$ok = false;
		}
	}
	// Đề riêng: chỉ tính tin đang nhận hồ sơ (cộng exams_count từng tin).
	$tin  = 0;
	$de   = 0;
	$page = 1;
	$last = 1;
	do {
		$r = $get( '/api/catalog/recruitment-exams', array( 'page' => $page, 'per_page' => 12 ) );
		if ( ! $r ) {
			$ok = false;
			break;
		}
		$tin  = (int) ( $r['pagination']['total'] ?? 0 );
		$last = (int) ( $r['pagination']['last_page'] ?? 1 );
		foreach ( (array) ( $r['items'] ?? array() ) as $it ) {
			$de += (int) ( $it['exams_count'] ?? 0 );
		}
		++$page;
	} while ( $page <= min( $last, 15 ) );
	if ( $ok ) {
		$s['tin-de-rieng'] = $tin;
		$s['de-rieng']     = $de;
	}
	$s['at'] = time();
	if ( $ok ) {
		set_transient( 'cvc_nav_stats_v1', $s, 20 * MINUTE_IN_SECONDS );
		update_option( 'cvc_nav_stats_last', $s, false );
	} else {
		// Lỗi API: giữ bản cũ thêm 5 phút rồi thử lại.
		set_transient( 'cvc_nav_stats_v1', (array) get_option( 'cvc_nav_stats_last', array() ), 5 * MINUTE_IN_SECONDS );
	}
	delete_transient( 'cvc_nav_stats_lock' );
}

/** Số tin đang mở của trang có URL này (chỉ trang tuyển dụng tổng / theo loại); null nếu không biết. */
function cvc_nav_count_for( string $url ): ?int {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( '' !== (string) wp_parse_url( $url, PHP_URL_QUERY ) ) {
		return null;
	}
	$map = (array) ( cvc_nav_stats()['by_path'] ?? array() );

	return isset( $map[ trailingslashit( $path ) ] ) ? (int) $map[ trailingslashit( $path ) ] : null;
}

/**
 * Thay {tin-mo} {sap-het-han} {vi-tri} {tin-de-rieng} {de-rieng} bằng số thật (bọc <b>). Trả HTML đã escape;
 * null nếu còn mã chưa có số liệu (khi đó không hiện dòng này để tránh số sai).
 */
function cvc_nav_fill( string $text ): ?string {
	$st  = cvc_nav_stats();
	$out = esc_html( $text );
	$bad = false;
	$out = preg_replace_callback(
		'/\{([a-z-]+)\}/',
		static function ( $m ) use ( $st, &$bad ) {
			if ( ! isset( $st[ $m[1] ] ) || ! is_numeric( $st[ $m[1] ] ) ) {
				$bad = true;
				return '';
			}
			return '<b>' . esc_html( number_format_i18n( (int) $st[ $m[1] ] ) ) . '</b>';
		},
		$out
	);

	return $bad ? null : $out;
}

/** Bộ icon nét (24×24) cho menu con; lớp CSS "ico-<tên>" trên mục menu. */
function cvc_nav_icon( string $name ): string {
	$p = array(
		'luyen-thi'      => '<path d="M9 3.5h6v3H9z"/><path d="M15 5h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2"/><path d="M8.8 13.6l2.2 2.2 4.3-4.6"/>',
		'thi-thu'        => '<circle cx="12" cy="13.5" r="7"/><path d="M12 10v3.6l2.6 1.6M10 2.8h4M12 2.8v3.7M18.4 6.6l1.3-1.3"/>',
		'ngan-hang'      => '<path d="M12 4l8 4-8 4-8-4z"/><path d="M4 12l8 4 8-4M4 16l8 4 8-4"/>',
		'da-luu'         => '<path d="M7 3.8h10a1 1 0 0 1 1 1v15.4l-6-4.1-6 4.1V4.8a1 1 0 0 1 1-1z"/><path d="M10 9.5h4"/>',
		'pdf'            => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 10.8v6.2M9.4 14.5L12 17.1l2.6-2.6"/>',
		'xep-hang'       => '<path d="M8 3.8h8V9a4 4 0 0 1-8 0z"/><path d="M8 5.8H5.2A3 3 0 0 0 8 10M16 5.8h2.8A3 3 0 0 1 16 10M12 13v3.6M8.8 20.2h6.4M10 16.6h4l.6 3.6H9.4z"/>',
		'tien-do'        => '<path d="M4 20h16M4 20V4"/><path d="M7.5 15.5l3.7-4.6 3 2.8 4.8-6.4"/><path d="M15.6 7.3H19v3.4"/>',
		'cong-chuc'      => '<path d="M3 9.3L12 4l9 5.3z"/><path d="M5.2 10.3v7.4M9.7 10.3v7.4M14.3 10.3v7.4M18.8 10.3v7.4M3 20.2h18M4 17.7h16"/>',
		'vien-chuc'      => '<rect x="4" y="5" width="16" height="15" rx="2.2"/><path d="M9.5 3v3.6M14.5 3v3.6"/><circle cx="12" cy="11.4" r="2.3"/><path d="M8 17.2c.8-1.8 2.2-2.7 4-2.7s3.2.9 4 2.7"/>',
		'giao-vien'      => '<path d="M2.5 9.4L12 5l9.5 4.4L12 13.8z"/><path d="M6.5 11.5v4.6c1.5 1.3 3.4 2 5.5 2s4-.7 5.5-2v-4.6M21.5 9.4v5"/>',
		'y-te'           => '<path d="M12 3.4l7 2.9v5.1c0 4.3-2.9 7.7-7 9.2-4.1-1.5-7-4.9-7-9.2V6.3z"/><path d="M12 8.8v6.4M8.8 12h6.4"/>',
		'hop-dong'       => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M8.5 12.5h7M8.5 17c1-.9 1.8-.9 2.5 0s1.6.9 2.6 0"/>',
		'tat-ca'         => '<rect x="4" y="4" width="6.6" height="6.6" rx="1.6"/><rect x="13.4" y="4" width="6.6" height="6.6" rx="1.6"/><rect x="4" y="13.4" width="6.6" height="6.6" rx="1.6"/><rect x="13.4" y="13.4" width="6.6" height="6.6" rx="1.6"/>',
		'tim-vi-tri'     => '<circle cx="10.8" cy="10.8" r="6.3"/><path d="M15.6 15.6l4.6 4.6"/><circle cx="10.8" cy="9.4" r="1.8"/><path d="M7.7 13.8c.7-1.2 1.8-1.8 3.1-1.8s2.4.6 3.1 1.8"/>',
		'ngoai-cong-lap' => '<path d="M5 20.2V4.8a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v15.4M15 9h3a1 1 0 0 1 1 1v10.2M3 20.2h18M8.2 7.5h1.4M11 7.5h1.4M8.2 11h1.4M11 11h1.4M8.2 14.5h1.4M11 14.5h1.4"/>',
		'loa'            => '<path d="M4 10v4a1 1 0 0 0 1 1h2.2l6.3 4V5L7.2 9H5a1 1 0 0 0-1 1z"/><path d="M17 9a4 4 0 0 1 0 6M19.3 6.6a7.4 7.4 0 0 1 0 10.8"/>',
		'sao'            => '<path d="M12 3l1.9 5.6L19.6 10l-5.7 1.6L12 17.3l-1.9-5.7L4.4 10l5.7-1.4z"/>',
		'chu-de'         => '<path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H4z"/><path d="M20 5.5A1.5 1.5 0 0 0 18.5 4H14a2 2 0 0 0-2 2v14a2 2 0 0 1 2-2h6z"/>',
		'van-ban'        => '<path d="M12 4v16M7 20h10M5 7h14M7.5 7l-3 6.2a3 3 0 0 0 6 0zM16.5 7l-3 6.2a3 3 0 0 0 6 0z"/>',
		'tai-lieu'       => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M8.5 12.5h7M8.5 16h5"/>',
		'mui-ten'        => '<path d="M5 12h14M13.5 6.5L19 12l-5.5 5.5"/>',
	);
	$d = $p[ $name ] ?? '<circle cx="12" cy="12" r="3.2"/>';

	return '<svg class="ch-mg-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

