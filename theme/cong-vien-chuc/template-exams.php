<?php
/**
 * Danh sách đề thi - /thi-trac-nghiem/ và /thi-trac-nghiem/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Exam_Service();
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

cvc_seo_set_title( 'Thi trắc nghiệm' );
cvc_seo_set_description( 'Đề thi trắc nghiệm ôn tập theo từng môn thi, chủ đề.' );
cvc_seo_set_canonical( cvc_exams_url( $currentPg ) );
cvc_seo_set_pagination_links(
	$currentPg > 1 ? cvc_exams_url( $currentPg - 1 ) : null,
	$currentPg < $lastPg ? cvc_exams_url( $currentPg + 1 ) : null
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
			array( 'label' => 'Thi trắc nghiệm' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Thi trắc nghiệm</h1>
	</header>

	<?php if ( ! $ok ) : ?>
		<?php cvc_render_error_state(); ?>
	<?php elseif ( empty( $items ) ) : ?>
		<?php cvc_render_empty_state( 'Chưa có đề thi nào.' ); ?>
	<?php else : ?>
		<div class="cvc-card-grid">
			<?php foreach ( $items as $item ) : ?>
				<?php cvc_render_exam_card( $item ); ?>
			<?php endforeach; ?>
		</div>

		<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_exams_url' ); ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
