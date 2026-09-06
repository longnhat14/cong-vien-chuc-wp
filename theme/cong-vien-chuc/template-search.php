<?php
/**
 * Trang tìm kiếm - /tim-kiem/
 *
 * State (q/type/page) đi qua query string GET thường ($_GET), không qua
 * rewrite capture group - route chỉ là 1 path cố định (xem inc/routes.php).
 * Không dùng session để giữ search state - mọi thứ nằm trong URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$raw_q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$q     = trim( preg_replace( '/\s+/', ' ', $raw_q ) );

$valid_types = cvc_search_valid_types();
$type        = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'all';
if ( ! in_array( $type, $valid_types, true ) ) {
	// Type lạ/tự chỉnh URL tay - fallback an toàn về "all", không gọi API
	// với type sai chỉ để nhận 422 rồi phải xử lý thêm 1 loại lỗi cho UI.
	$type = 'all';
}

$page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
$page = $page > 0 ? $page : 1;

$has_query = '' !== $q;

$result = null;
if ( $has_query ) {
	$result = ( new CVC_Search_Service() )->search( $q, $type, $page, 12 );
}

cvc_seo_set_title( $has_query ? sprintf( 'Kết quả tìm kiếm cho "%s"', $q ) : 'Tìm kiếm' );

if ( $has_query ) {
	// Trang có query/filter/pagination - không nên bị index hàng loạt.
	cvc_seo_set_noindex();
} else {
	cvc_seo_set_description( 'Tìm kiếm khóa học, chủ đề, tin tuyển dụng, kiến thức, đề thi trắc nghiệm và văn bản pháp luật trên Công Viên Chức.' );
	cvc_seo_set_canonical( cvc_search_url() );
}

get_header();
?>

<main id="main" class="container cvc-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array( 'label' => 'Tìm kiếm' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1><?php echo $has_query ? esc_html( sprintf( 'Kết quả tìm kiếm cho "%s"', $q ) ) : 'Tìm kiếm'; ?></h1>
	</header>

	<?php cvc_render_search_form( $q ); ?>

	<?php if ( ! $has_query ) : ?>

		<p class="cvc-page-header__lead">Nhập từ khóa để tìm trong toàn bộ nội dung public của Công Viên Chức.</p>

		<section class="cvc-related-section">
			<h2>Bạn có thể tìm</h2>
			<ul class="cvc-related-list">
				<?php foreach ( cvc_get_primary_nav_items() as $section => $item ) : ?>
					<?php if ( 'home' === $section ) : continue; endif; ?>
					<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>

	<?php else : ?>

		<?php
		// Filter tab - luôn hiện, active state theo $type hiện tại. Click
		// filter reset về trang 1 (cvc_search_url không nhận $page -> mặc định 1).
		?>
		<nav class="cvc-search-filters" aria-label="Lọc theo loại nội dung">
			<a
				class="cvc-search-filters__item<?php echo 'all' === $type ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( cvc_search_url( $q, 'all' ) ); ?>"
				<?php echo 'all' === $type ? ' aria-current="page"' : ''; ?>
			>Tất cả</a>
			<?php foreach ( cvc_search_domains() as $domain_key => $domain ) : ?>
				<a
					class="cvc-search-filters__item<?php echo $type === $domain_key ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( cvc_search_url( $q, $domain_key ) ); ?>"
					<?php echo $type === $domain_key ? ' aria-current="page"' : ''; ?>
				><?php echo esc_html( $domain['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( ! $result['ok'] ) : ?>

			<?php
			$status = (int) $result['status'];
			if ( 422 === $status ) {
				cvc_render_error_state( 'Từ khóa tìm kiếm không hợp lệ. Vui lòng nhập ít nhất 2 ký tự.' );
			} elseif ( 429 === $status ) {
				cvc_render_error_state( 'Bạn đang gửi quá nhiều yêu cầu. Vui lòng thử lại sau.' );
			} else {
				cvc_render_error_state( 'Không thể tải kết quả tìm kiếm. Vui lòng thử lại.' );
			}
			?>

		<?php else :
			$pagination  = $result['data']['data'] ?? array();
			$items       = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
			$total       = (int) ( $pagination['total'] ?? 0 );
			$currentPg   = (int) ( $pagination['current_page'] ?? $page );
			$lastPg      = (int) ( $pagination['last_page'] ?? 1 );
			?>

			<?php if ( empty( $items ) ) : ?>

				<?php cvc_render_empty_state( 'Không tìm thấy kết quả phù hợp.' ); ?>

				<p>
					Hãy thử từ khóa khác, hoặc
					<a href="<?php echo esc_url( cvc_search_url( '', 'all' ) ); ?>">xem tất cả nội dung</a>
					/ <a href="<?php echo esc_url( home_url( '/' ) ); ?>">về trang chủ</a>.
				</p>

			<?php else : ?>

				<p class="cvc-page-header__meta">
					<?php echo esc_html( sprintf( 'Tìm thấy %d kết quả.', $total ) ); ?>
				</p>

				<div class="cvc-card-grid">
					<?php foreach ( $items as $item ) : ?>
						<?php cvc_render_search_result( $item ); ?>
					<?php endforeach; ?>
				</div>

				<?php
				cvc_render_pagination(
					$currentPg,
					$lastPg,
					fn( int $target_page ): string => cvc_search_url( $q, $type, $target_page )
				);
				?>

			<?php endif; ?>

		<?php endif; ?>

	<?php endif; ?>
</main>

<?php get_footer(); ?>
