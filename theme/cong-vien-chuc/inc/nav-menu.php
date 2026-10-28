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
 *  - Chưa gán menu nào cho vị trí Primary -> dùng menu mặc định trong code (cvc_main_nav_items_default).
 * Màn hình Giao diện → Menu tự hiện các ô Mô tả / Lớp CSS / Thuộc tính tiêu đề.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_NAV_GROUPS = array( 'nhom-luyen-tap' => 'practice', 'nhom-danh-gia' => 'assess', 'nhom-hoc-them' => 'more' );

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
			$children[ (int) $it->menu_item_parent ][] = $row + array( 'group' => $group, 'sym' => trim( (string) $it->attr_title ) );
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
			. 'Mục cấp 2: <i>Lớp CSS</i> = <code>nhom-luyen-tap</code> / <code>nhom-danh-gia</code> / <code>nhom-hoc-them</code> (menu lớn 2 cột), <i>Thuộc tính tiêu đề</i> = ký hiệu (✎ ◷ ▤), '
			. 'nhãn dạng <code>Tên | Nhãn nhỏ</code> để hiện huy hiệu. Bỏ gán vị trí Primary → web dùng menu mặc định.</p></div>';
	}
);
