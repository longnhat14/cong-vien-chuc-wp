<?php
/**
 * Danh sách kiến thức - /kien-thuc/ và /kien-thuc/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Knowledge_Service();
$result  = $service->list(
	array(
		'per_page' => 15,
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

cvc_seo_set_title( 'Kiến thức' );
cvc_seo_set_description( 'Nội dung kiến thức hệ thống theo từng chủ đề, giúp ôn tập đúng trọng tâm.' );
cvc_seo_set_canonical( cvc_knowledge_url( $currentPg ) );
cvc_seo_set_pagination_links(
	$currentPg > 1 ? cvc_knowledge_url( $currentPg - 1 ) : null,
	$currentPg < $lastPg ? cvc_knowledge_url( $currentPg + 1 ) : null
);

get_header();
?>

<main class="container cvc-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array( 'label' => 'Kiến thức' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Kiến thức</h1>
	</header>

	<?php if ( ! $ok ) : ?>
		<?php cvc_render_error_state(); ?>
	<?php elseif ( empty( $items ) ) : ?>
		<?php cvc_render_empty_state( 'Chưa có nội dung kiến thức nào.' ); ?>
	<?php else : ?>
		<div class="cvc-card-grid">
			<?php foreach ( $items as $item ) : ?>
				<?php cvc_render_knowledge_card( $item ); ?>
			<?php endforeach; ?>
		</div>

		<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_knowledge_url' ); ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
