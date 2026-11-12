<?php
/**
 * Trang danh sách bài viết & chuyên mục - bản của theme (thay templates/blog-archive.php của PCTech AI SEO 0.13.0).
 * 12/11/2026 (claude/ke-hoach-blog-index-cham.md): thêm "Mục lục bài viết" ở trang 1 - mọi bài cách trang Bài viết
 * đúng 1 lượt bấm (trước đây 12 bài/trang, bài cũ nằm sâu 16 trang nên Google chậm phát hiện).
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$pcseo_term   = is_category() ? get_queried_object() : null;
$pcseo_title  = $pcseo_term instanceof WP_Term ? $pcseo_term->name : ( (string) PCSEO_Blog::s( 'blog_title' ) ?: 'Bài viết' );
$pcseo_desc   = $pcseo_term instanceof WP_Term ? term_description( $pcseo_term ) : (string) PCSEO_Blog::s( 'blog_desc' );
$pcseo_search = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); // phpcs:ignore
$pcseo_cats   = PCSEO_Blog::categories();
$pcseo_crumbs = array( array( (string) PCSEO_Blog::s( 'blog_title' ) ?: 'Bài viết', $pcseo_term ? PCSEO_Blog::url() : '' ) );
if ( $pcseo_term instanceof WP_Term ) {
	$pcseo_crumbs[] = array( $pcseo_term->name, '' );
}
$pcseo_action = $pcseo_term instanceof WP_Term ? get_term_link( $pcseo_term ) : ( PCSEO_Blog::enabled() ? PCSEO_Blog::url() : home_url( '/' ) );

// Plugin chỉ nạp blog.css khi dùng template của nó → bản của theme phải tự nạp (cùng biến màu / thụt lề như plugin).
wp_enqueue_style( 'pcseo-blog', plugins_url( 'assets/blog.css', PCSEO_FILE ), array(), PCSEO_VERSION );
wp_add_inline_style(
	'pcseo-blog',
	'.pcseo-blog{--pcseo-accent:' . ( sanitize_hex_color( (string) PCSEO_Blog::s( 'blog_accent' ) ) ?: '#0b5ed7' )
	. ';--pcseo-indent:' . max( 0, min( 4, (float) PCSEO_Blog::s( 'blog_indent' ) ) )
	. 'em;--pcseo-gap:' . max( 0.3, min( 3, (float) PCSEO_Blog::s( 'blog_para_gap' ) ) ) . 'em}'
);

PCSEO_Blog::header();
?>
<main id="primary" class="pcseo-blog pcseo-archive">
	<div class="pcseo-wrap">
		<?php if ( ! PCSEO_Blog::enabled() ) : ?>
			<div class="pcseo-preview-note">Xem thử giao diện mục Bài viết — khách chưa thấy trang này (chưa bật trong PCTech AI SEO → Mục Bài viết).</div>
		<?php endif; ?>
		<?php echo PCSEO_Blog::breadcrumb( $pcseo_crumbs ); // phpcs:ignore ?>

		<header class="pcseo-archive-head">
			<h1 class="pcseo-title"><?php echo esc_html( $pcseo_title ); ?></h1>
			<?php if ( $pcseo_desc ) : ?><div class="pcseo-lead"><?php echo wp_kses_post( wpautop( $pcseo_desc ) ); ?></div><?php endif; ?>
			<form class="pcseo-search" method="get" action="<?php echo esc_url( $pcseo_action ); ?>" role="search">
				<?php if ( ! PCSEO_Blog::enabled() ) : ?><input type="hidden" name="pcseo_blog" value="1"><?php endif; ?>
				<input type="search" name="q" value="<?php echo esc_attr( $pcseo_search ); ?>" placeholder="Tìm bài viết…" aria-label="Tìm bài viết">
				<button type="submit">Tìm</button>
			</form>
			<p class="cvc-blog-data">Số liệu độc quyền: <a href="<?php echo esc_url( home_url( '/bao-cao-tuyen-dung/' ) ); ?>">Báo cáo tuyển dụng hằng tháng</a> · <a href="<?php echo esc_url( home_url( '/cau-hay-sai-nhat/' ) ); ?>">Câu hay sai nhất</a></p>
			<?php if ( $pcseo_cats ) : ?>
				<nav class="pcseo-chips" aria-label="Chuyên mục">
					<a class="pcseo-chip<?php echo $pcseo_term ? '' : ' is-active'; ?>" href="<?php echo esc_url( PCSEO_Blog::url() ); ?>">Tất cả</a>
					<?php foreach ( $pcseo_cats as $c ) : ?>
						<a class="pcseo-chip<?php echo $pcseo_term instanceof WP_Term && $pcseo_term->term_id === $c->term_id ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="pcseo-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					$p   = get_post();
					$cat = PCSEO_Blog::primary_category( $p );
					?>
					<article class="pcseo-card">
						<a class="pcseo-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php echo PCSEO_Blog::thumb( $p ); // phpcs:ignore ?></a>
						<div class="pcseo-card-body">
							<?php if ( $cat ) : ?><span class="pcseo-card-cat"><?php echo esc_html( $cat->name ); ?></span><?php endif; ?>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p><?php echo esc_html( PCSEO_Blog::excerpt( $p ) ); ?></p>
							<span class="pcseo-card-meta"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?> · <?php echo (int) PCSEO_Blog::reading_minutes( $p ); ?> phút đọc</span>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php
			$pcseo_links = paginate_links(
				array(
					'total'     => (int) $wp_query->max_num_pages,
					'current'   => max( 1, (int) get_query_var( 'paged' ) ),
					'prev_text' => '‹ Trước',
					'next_text' => 'Sau ›',
					'add_args'  => '' !== $pcseo_search ? array( 'q' => $pcseo_search ) : false,
				)
			);
			if ( $pcseo_links ) {
				echo '<nav class="pcseo-pages" aria-label="Phân trang">' . wp_kses_post( $pcseo_links ) . '</nav>';
			}
			?>
			<?php
			// Mục lục: trang 1, không tìm kiếm → liệt kê toàn bộ bài (theo chuyên mục; trang chuyên mục: bài của chuyên mục).
			if ( '' === $pcseo_search && max( 1, (int) get_query_var( 'paged' ) ) === 1 && function_exists( 'cvc_blog_index_groups' ) ) {
				$cvc_groups = cvc_blog_index_groups( $pcseo_term instanceof WP_Term ? (int) $pcseo_term->term_id : 0 );
				if ( $cvc_groups ) {
					cvc_blog_css();
					echo '<section class="cvc-blog-index" aria-labelledby="cvc-blog-index-h"><h2 id="cvc-blog-index-h">Mục lục ' . esc_html( $pcseo_term instanceof WP_Term ? $pcseo_term->name : 'tất cả bài viết' ) . '</h2>';
					foreach ( $cvc_groups as $g ) {
						echo '<div class="cvc-blog-index-group">' . ( count( $cvc_groups ) > 1 ? '<h3><a href="' . esc_url( $g['url'] ) . '">' . esc_html( $g['name'] ) . '</a> <small>' . (int) count( $g['posts'] ) . ' bài</small></h3>' : '' ) . '<ul>';
						foreach ( $g['posts'] as $p ) {
							echo '<li><a href="' . esc_url( $p['url'] ) . '">' . esc_html( $p['title'] ) . '</a></li>';
						}
						echo '</ul></div>';
					}
					echo '</section>';
				}
			}
			?>
		<?php else : ?>
			<p class="pcseo-empty"><?php echo '' !== $pcseo_search ? 'Không tìm thấy bài viết phù hợp.' : 'Chưa có bài viết nào trong mục này.'; ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
PCSEO_Blog::footer();
