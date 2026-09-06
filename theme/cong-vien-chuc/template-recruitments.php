<?php
/**
 * Danh sách tin tuyển dụng - /tuyen-dung/ và /tuyen-dung/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Recruitment_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$items      = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok ) {
	status_header( 503 );
}

cvc_seo_set_title( 'Tuyển dụng' );
cvc_seo_set_description( 'Thông tin tuyển dụng công chức, viên chức mới nhất từ các cơ quan, đơn vị.' );
cvc_seo_set_canonical( cvc_recruitments_url( $currentPg ) );
cvc_seo_set_pagination_links(
	$currentPg > 1 ? cvc_recruitments_url( $currentPg - 1 ) : null,
	$currentPg < $lastPg ? cvc_recruitments_url( $currentPg + 1 ) : null
);

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
			array( 'label' => 'Tuyển dụng' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Tuyển dụng</h1>
	</header>

	<?php if ( ! $ok ) : ?>
		<?php cvc_render_error_state(); ?>
	<?php elseif ( empty( $items ) ) : ?>
		<?php cvc_render_empty_state( 'Chưa có tin tuyển dụng nào.' ); ?>
	<?php else : ?>
		<div class="cvc-card-grid">
			<?php foreach ( $items as $item ) : ?>
				<?php cvc_render_recruitment_card( $item ); ?>
			<?php endforeach; ?>
		</div>

		<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_recruitments_url' ); ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
