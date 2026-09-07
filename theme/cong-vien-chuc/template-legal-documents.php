<?php
/**
 * Danh sách văn bản pháp luật - /van-ban-phap-luat/ và
 * /van-ban-phap-luat/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Legal_Document_Service();
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

cvc_seo_set_title( 'Văn bản pháp luật' );
cvc_seo_set_description( 'Văn bản pháp luật liên quan đến tuyển dụng, thi tuyển công chức, viên chức.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_legal_documents_url' );
cvc_seo_set_pagination_links(
	$currentPg > 1 ? cvc_legal_documents_url( $currentPg - 1 ) : null,
	$currentPg < $lastPg ? cvc_legal_documents_url( $currentPg + 1 ) : null
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
			array( 'label' => 'Văn bản pháp luật' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Văn bản pháp luật</h1>
	</header>

	<?php if ( ! $ok ) : ?>
		<?php cvc_render_error_state(); ?>
	<?php elseif ( empty( $items ) ) : ?>
		<?php cvc_render_empty_state( 'Chưa có văn bản pháp luật nào.' ); ?>
	<?php else : ?>
		<div class="cvc-card-grid">
			<?php foreach ( $items as $item ) : ?>
				<?php cvc_render_legal_document_card( $item ); ?>
			<?php endforeach; ?>
		</div>

		<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_legal_documents_url' ); ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
