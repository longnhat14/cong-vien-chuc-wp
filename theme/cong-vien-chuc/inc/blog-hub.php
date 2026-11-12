<?php
/**
 * Liên kết nội bộ cho bài viết blog (12/11/2026, claude/ke-hoach-blog-index-cham.md - phần A).
 * Google chỉ index 34/187 bài vì gần như không có trang nào trỏ tới bài. File này:
 *  - cvc_blog_index_groups(): mục lục toàn bộ bài theo chuyên mục (trang /bai-viet/, theme/pcseo/blog-archive.php).
 *  - cvc_blog_related(): bài liên quan theo ngữ cảnh (tỉnh, ngành, công chức / viên chức, chủ đề) cho trang tin,
 *    trang tỉnh, trang đề, chủ đề, văn bản; cvc_render_blog_related() in khối "Bài hướng dẫn liên quan".
 *  - cvc_render_home_blog(): khối "Cẩm nang mới" ở trang chủ.
 * Dữ liệu bài lấy từ WordPress (không gọi API), lưu đệm 1 giờ, xóa khi đăng / sửa bài.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Danh sách bài đã đăng gọn nhẹ (id, tiêu đề, link, ngày, chuyên mục, chữ không dấu để so khớp). */
function cvc_blog_posts_index(): array {
	$c = get_transient( 'cvc_blog_idx_v1' );
	if ( is_array( $c ) ) {
		return $c;
	}
	$ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1000, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'suppress_filters' => false ) );
	$out = array();
	foreach ( $ids as $id ) {
		$title = html_entity_decode( (string) get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
		$kw    = (string) get_post_meta( $id, 'rank_math_focus_keyword', true );
		$cats  = wp_get_post_categories( $id );
		$out[] = array(
			'id'    => (int) $id,
			'title' => $title,
			'url'   => (string) get_permalink( $id ),
			'date'  => (string) get_the_date( 'Y-m-d', $id ),
			'cats'  => array_map( 'intval', (array) $cats ),
			'text'  => cvc_blog_ascii( $title . ' ' . str_replace( ',', ' ', $kw ) ),
		);
	}
	set_transient( 'cvc_blog_idx_v1', $out, HOUR_IN_SECONDS );

	return $out;
}

function cvc_blog_ascii( string $s ): string {
	return trim( (string) preg_replace( '/[^a-z0-9 ]+/', ' ', strtolower( remove_accents( $s ) ) ) );
}

add_action( 'save_post_post', static fn () => delete_transient( 'cvc_blog_idx_v1' ) );
add_action( 'deleted_post', static fn () => delete_transient( 'cvc_blog_idx_v1' ) );

/** Mục lục theo chuyên mục. @return array<int, array{name:string,url:string,posts:array}> */
function cvc_blog_index_groups( int $cat = 0 ): array {
	$posts  = cvc_blog_posts_index();
	$groups = array();
	$terms  = $cat ? array_filter( array( get_term( $cat, 'category' ) ) ) : get_categories( array( 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );
	$seen   = array();
	foreach ( $terms as $t ) {
		if ( ! $t instanceof WP_Term ) {
			continue;
		}
		$list = array();
		foreach ( $posts as $p ) {
			if ( in_array( (int) $t->term_id, $p['cats'], true ) && ( $cat || ! isset( $seen[ $p['id'] ] ) ) ) {
				$list[]            = $p;
				$seen[ $p['id'] ] = true;
			}
		}
		if ( $list ) {
			$groups[] = array( 'name' => $t->name, 'url' => (string) get_term_link( $t ), 'posts' => $list );
		}
	}

	return $groups;
}

/**
 * Bài liên quan theo ngữ cảnh. $ctx = chuỗi mô tả trang (tên tỉnh, cơ quan, ngành, loại tuyển dụng, tên đề...).
 * Chấm điểm theo cụm từ trùng (tỉnh / bộ ngành nặng hơn), bỏ từ quá chung; thiếu thì lấy bài gốc / mới.
 */
function cvc_blog_related( string $ctx, int $n = 3, array $exclude = array() ): array {
	$posts = cvc_blog_posts_index();
	if ( ! $posts ) {
		return array();
	}
	$c    = ' ' . cvc_blog_ascii( $ctx ) . ' ';
	$stop = array( 'cong', 'chuc', 'vien', 'tuyen', 'dung', 'thi', 'nam', 'va', 'cua', 'cho', 'cac', 'nhung', 'tai', 'theo', 'nhu', 'the', 'nao', 'gi', 'la', 'co', 'khong', 'mot', 've', 'trong', 'voi', 'can', 'biet', 'cach', 'hoc', 'on', 'ho', 'so', 'dieu', 'kien', 'lich', 'moi', 'nhat', 'huong', 'dan', 'chi', 'tiet', 'thong', 'tin', 'de', 'ban' );
	// Cụm đặc trưng nặng điểm: tỉnh / thành, bộ ngành, nhóm ngành.
	$strong = array( 'ha noi', 'tphcm', 'ho chi minh', 'da nang', 'hai phong', 'can tho', 'hai quan', 'thue', 'kho bac', 'giao vien', 'y te', 'bac si', 'dieu duong', 'ke toan', 'cong an', 'quan doi', 'kiem lam', 'thong ke', 'bao hiem', 'ngan hang', 'toa an', 'vien kiem sat', 'cap xa', 'mam non', 'tieu hoc', 'thcs', 'thpt' );
	$scores = array();
	foreach ( $posts as $i => $p ) {
		if ( in_array( $p['id'], $exclude, true ) ) {
			continue;
		}
		$s = 0;
		foreach ( $strong as $k ) {
			if ( str_contains( $c, ' ' . $k . ' ' ) && str_contains( ' ' . $p['text'] . ' ', ' ' . $k . ' ' ) ) {
				$s += 6;
			}
		}
		foreach ( array_unique( explode( ' ', $p['text'] ) ) as $w ) {
			if ( strlen( $w ) >= 3 && ! in_array( $w, $stop, true ) && str_contains( $c, ' ' . $w . ' ' ) ) {
				++$s;
			}
		}
		// Đúng loại công chức / viên chức.
		$civil = str_contains( $c, ' cong chuc ' );
		$pub   = str_contains( $c, ' vien chuc ' );
		if ( ( $civil && str_contains( $p['text'], 'cong chuc' ) ) || ( $pub && str_contains( $p['text'], 'vien chuc' ) ) ) {
			$s += 2;
		}
		if ( $s > 0 ) {
			$scores[ $i ] = $s + ( (int) strtotime( $p['date'] ) / 1e10 ); // bằng điểm → bài mới hơn
		}
	}
	arsort( $scores );
	$out = array();
	foreach ( $scores as $i => $sc ) {
		if ( $sc < 3 ) {
			break; // khớp quá yếu (chỉ 1-2 từ chung) → không gợi ý bừa.
		}
		$out[] = $posts[ $i ];
		if ( count( $out ) >= $n ) {
			return $out;
		}
	}
	// Bù bằng bài gốc (trụ cột) phù hợp loại công chức / viên chức.
	$pillars = str_contains( $c, ' vien chuc ' ) && ! str_contains( $c, ' cong chuc ' ) ? array( 'on-thi-vien-chuc', 'tuyen-vien-chuc', 'on-thi-cong-chuc' ) : array( 'on-thi-cong-chuc', 'tuyen-cong-chuc', 'on-thi-vien-chuc', 'tuyen-vien-chuc' );
	foreach ( $pillars as $slug ) {
		foreach ( $posts as $p ) {
			if ( str_ends_with( rtrim( $p['url'], '/' ), '/' . $slug ) && ! in_array( $p, $out, true ) && ! in_array( $p['id'], $exclude, true ) ) {
				$out[] = $p;
				break;
			}
		}
		if ( count( $out ) >= $n ) {
			break;
		}
	}

	return $out;
}

/** Khối "Bài hướng dẫn liên quan" (render phía máy chủ, không cần JS). */
function cvc_render_blog_related( string $ctx, string $heading = 'Bài hướng dẫn liên quan', int $n = 3, string $class = '' ): void {
	$items = cvc_blog_related( $ctx, $n );
	if ( ! $items ) {
		return;
	}
	cvc_blog_css();
	?>
	<section class="cvc-blog-rel <?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( $heading ); ?>">
		<p class="cvc-blog-rel-h"><?php echo esc_html( $heading ); ?></p>
		<ul>
			<?php foreach ( $items as $p ) : ?>
				<li><a href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<a class="cvc-blog-rel-all" href="<?php echo esc_url( home_url( '/bai-viet/' ) ); ?>">Tất cả bài hướng dẫn →</a>
	</section>
	<?php
}

/** Trang chủ: 8 bài mới nhất (khác chuyên mục xen kẽ). */
function cvc_render_home_blog(): void {
	$posts = array_slice( cvc_blog_posts_index(), 0, 8 );
	if ( ! $posts ) {
		return;
	}
	cvc_blog_css();
	?>
	<section class="cvc-home-blog" aria-labelledby="cvc-home-blog-h">
		<div class="cvc-home-blog-head"><h2 id="cvc-home-blog-h">Cẩm nang thi tuyển mới</h2><a href="<?php echo esc_url( home_url( '/bai-viet/' ) ); ?>">Xem tất cả →</a></div>
		<ul class="cvc-home-blog-list">
			<?php foreach ( $posts as $p ) : ?>
				<li><a href="<?php echo esc_url( $p['url'] ); ?>"><span><?php echo esc_html( $p['title'] ); ?></span><small><?php echo esc_html( wp_date( 'd/m/Y', strtotime( $p['date'] ) ) ); ?></small></a></li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}

function cvc_blog_css(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	echo '<style>.cvc-blog-rel{margin:16px 0;padding:14px 16px;border:1px solid #dfe4ea;border-radius:12px;background:#fff}.cvc-blog-rel-h{margin:0 0 8px;font-weight:800;color:#123a63;font-size:16px}.cvc-blog-rel ul{margin:0;padding:0 0 0 18px;display:grid;gap:6px}.cvc-blog-rel li{font-size:15px;line-height:1.45}.cvc-blog-rel a{color:#1767aa}.cvc-blog-rel-all{display:inline-block;margin-top:8px;font-size:13.5px;font-weight:700}'
		. '.cvc-home-blog{margin:24px auto;max-width:1200px;padding:0 16px}.cvc-home-blog-head{display:flex;justify-content:space-between;align-items:baseline;gap:12px;margin-bottom:10px}.cvc-home-blog-head h2{margin:0;font-size:22px;color:#123a63}.cvc-home-blog-head a{font-weight:700;color:#1767aa;white-space:nowrap}.cvc-home-blog-list{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px}.cvc-home-blog-list a{display:flex;flex-direction:column;gap:4px;height:100%;padding:12px 14px;border:1px solid #dfe4ea;border-radius:12px;background:#fff;color:#14263b;text-decoration:none}.cvc-home-blog-list a:hover{border-color:#1767aa}.cvc-home-blog-list span{font-weight:700;line-height:1.4}.cvc-home-blog-list small{color:#5c5c5c}'
		. '.cvc-blog-index{margin:28px 0 8px;padding-top:16px;border-top:1px solid #e3e8ef}.cvc-blog-index h2{font-size:22px;margin:0 0 12px}.cvc-blog-index-group{margin:0 0 16px}.cvc-blog-index-group h3{font-size:17px;margin:0 0 6px}.cvc-blog-index-group h3 small{font-weight:400;color:#5c5c5c}.cvc-blog-index ul{margin:0;padding:0 0 0 18px;columns:2 320px;column-gap:28px}.cvc-blog-index li{break-inside:avoid;margin:0 0 6px;line-height:1.45}</style>';
}
