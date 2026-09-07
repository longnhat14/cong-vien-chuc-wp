<?php
/**
 * Danh sách chủ đề - /chu-de/ và /chu-de/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Topic_Service();
$result  = $service->list(
	array(
		'per_page' => 20,
		'page'     => $paged,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$topics     = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok ) {
	status_header( 503 );
}

cvc_seo_set_title( 'Chủ đề' );
cvc_seo_set_description( 'Danh sách chủ đề kiến thức ôn thi công chức, viên chức tại Công Viên Chức.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_topics_url' );
cvc_seo_set_pagination_links(
	$currentPg > 1 ? cvc_topics_url( $currentPg - 1 ) : null,
	$currentPg < $lastPg ? cvc_topics_url( $currentPg + 1 ) : null
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
			array( 'label' => 'Chủ đề' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Chủ đề</h1>
	</header>

	<?php if ( ! $ok ) : ?>
		<?php cvc_render_error_state(); ?>
	<?php elseif ( empty( $topics ) ) : ?>
		<?php cvc_render_empty_state( 'Chưa có chủ đề nào.' ); ?>
	<?php else : ?>
		<div class="cvc-card-grid">
			<?php foreach ( $topics as $topic ) : ?>
				<?php cvc_render_topic_card( $topic ); ?>
			<?php endforeach; ?>
		</div>

		<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_topics_url' ); ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
