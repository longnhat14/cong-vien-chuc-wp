<?php
/**
 * Chi tiết kiến thức - /kien-thuc/{slug}/
 *
 * Chỉ hiển thị field thực sự có trong response của
 * GET /api/knowledge-items/{slug} (xem KnowledgeController::show()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_knowledge_slug' ) );

$service = new CVC_Knowledge_Service();
$result  = $service->find( $slug );

$item     = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$item     = is_array( $data ) ? $data : null;
	$is_found = null !== $item;
}

if ( ! $is_found ) {
	if ( 404 === (int) $result['status'] ) {
		status_header( 404 );
		cvc_seo_set_noindex();
	} else {
		status_header( 503 );
	}
}

cvc_seo_set_title( $is_found ? (string) $item['title'] : 'Không tìm thấy nội dung kiến thức' );

$topic = $is_found && is_array( $item['topic'] ?? null ) ? $item['topic'] : null;

$breadcrumb_items = array(
	array(
		'label' => 'Trang chủ',
		'url'   => home_url( '/' ),
	),
	array(
		'label' => 'Kiến thức',
		'url'   => cvc_knowledge_url(),
	),
);

if ( $topic && ! empty( $topic['slug'] ) ) {
	$breadcrumb_items[] = array(
		'label' => (string) ( $topic['name'] ?? '' ),
		'url'   => cvc_topic_url( $topic['slug'] ),
	);
}

$breadcrumb_items[] = array( 'label' => $is_found ? (string) $item['title'] : 'Không tìm thấy' );

if ( $is_found ) {
	if ( ! empty( $item['summary'] ) ) {
		cvc_seo_set_description( (string) $item['summary'] );
	}
	cvc_seo_set_canonical( cvc_knowledge_item_url( $slug ) );
	cvc_seo_set_og( array( 'type' => 'article' ) );

	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );

	$article_schema = cvc_build_knowledge_article_jsonld( $item );
	if ( null !== $article_schema ) {
		cvc_seo_add_json_ld( $article_schema );
	}
}

get_header();
?>

<main id="main" class="container cvc-page">
	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<?php if ( ! $is_found ) : ?>
		<h1><?php echo esc_html( 404 === (int) $result['status'] ? 'Không tìm thấy nội dung kiến thức' : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( 404 === (int) $result['status'] ) {
			cvc_render_notfound_state( 'Nội dung kiến thức bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_knowledge_url() ); ?>">&larr; Xem tất cả kiến thức</a></p>
	<?php else : ?>
		<?php $legalDocument = is_array( $item['legal_document'] ?? null ) ? $item['legal_document'] : null; ?>
		<header class="cvc-page-header">
			<?php if ( $topic && ! empty( $topic['name'] ) ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $topic['name'] ); ?></span>
			<?php endif; ?>
			<h1><?php echo esc_html( $item['title'] ); ?></h1>
			<?php if ( ! empty( $item['summary'] ) ) : ?>
				<p class="cvc-page-header__lead"><?php echo esc_html( $item['summary'] ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $item['content'] ) ) : ?>
			<div class="cvc-prose"><?php echo nl2br( esc_html( $item['content'] ) ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $item['key_points'] ) && is_array( $item['key_points'] ) ) : ?>
			<section class="cvc-related-section">
				<h2>Điểm chính</h2>
				<ul class="cvc-related-list">
					<?php foreach ( $item['key_points'] as $point ) : ?>
						<?php if ( ! is_scalar( $point ) ) : continue; endif; ?>
						<li><?php echo esc_html( (string) $point ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $item['source_reference'] ) ) : ?>
			<p class="cvc-page-header__meta"><?php echo esc_html( sprintf( 'Nguồn: %s', $item['source_reference'] ) ); ?></p>
		<?php endif; ?>

		<?php if ( $legalDocument && ! empty( $legalDocument['slug'] ) ) : ?>
			<section class="cvc-related-section">
				<h2>Căn cứ pháp lý</h2>
				<p class="cvc-prose">
					<a href="<?php echo esc_url( cvc_legal_document_url( $legalDocument['slug'] ) ); ?>">
						<?php echo esc_html( $legalDocument['title'] ?? '' ); ?>
					</a>
					<?php if ( ! empty( $legalDocument['document_number'] ) ) : ?>
						(<?php echo esc_html( $legalDocument['document_number'] ); ?>)
					<?php endif; ?>
				</p>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
