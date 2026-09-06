<?php
/**
 * Chi tiết chủ đề - /chu-de/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_topic_slug' ) );

$service = new CVC_Topic_Service();
$result  = $service->find( $slug );

$topic    = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$topic    = is_array( $data ) ? $data : null;
	$is_found = null !== $topic;
}

if ( ! $is_found ) {
	if ( 404 === (int) $result['status'] ) {
		status_header( 404 );
		cvc_seo_set_noindex();
	} else {
		status_header( 503 );
	}
}

cvc_seo_set_title( $is_found ? (string) $topic['name'] : 'Không tìm thấy chủ đề' );

if ( $is_found ) {
	if ( ! empty( $topic['description'] ) ) {
		cvc_seo_set_description( (string) $topic['description'] );
	}
	cvc_seo_set_canonical( cvc_topic_url( $slug ) );
}

get_header();
?>

<main class="container cvc-page">
	<?php
	$breadcrumb_items = array(
		array(
			'label' => 'Trang chủ',
			'url'   => home_url( '/' ),
		),
		array(
			'label' => 'Chủ đề',
			'url'   => cvc_topics_url(),
		),
	);

	if ( $is_found && is_array( $topic['parent'] ?? null ) && ! empty( $topic['parent']['slug'] ) ) {
		$breadcrumb_items[] = array(
			'label' => (string) $topic['parent']['name'],
			'url'   => cvc_topic_url( (string) $topic['parent']['slug'] ),
		);
	}

	$breadcrumb_items[] = array( 'label' => $is_found ? (string) $topic['name'] : 'Không tìm thấy' );

	cvc_render_breadcrumbs( $breadcrumb_items );
	?>

	<?php if ( ! $is_found ) : ?>
		<h1><?php echo esc_html( 404 === (int) $result['status'] ? 'Không tìm thấy chủ đề' : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( 404 === (int) $result['status'] ) {
			cvc_render_notfound_state( 'Chủ đề bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a href="<?php echo esc_url( cvc_topics_url() ); ?>">&larr; Xem tất cả chủ đề</a></p>
	<?php else : ?>
		<header class="cvc-page-header">
			<h1><?php echo esc_html( $topic['name'] ); ?></h1>
			<?php if ( ! empty( $topic['exam_subject']['name'] ) ) : ?>
				<p class="cvc-page-header__meta"><?php echo esc_html( $topic['exam_subject']['name'] ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $topic['description'] ) ) : ?>
			<div class="cvc-prose"><?php echo nl2br( esc_html( $topic['description'] ) ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $topic['children'] ) && is_array( $topic['children'] ) ) : ?>
			<section class="cvc-related-section">
				<h2>Chủ đề con</h2>
				<ul class="cvc-related-list">
					<?php foreach ( $topic['children'] as $child ) : ?>
						<?php if ( empty( $child['slug'] ) ) : continue; endif; ?>
						<li>
							<a href="<?php echo esc_url( cvc_topic_url( $child['slug'] ) ); ?>"><?php echo esc_html( $child['name'] ?? '' ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $topic['knowledge_items'] ) && is_array( $topic['knowledge_items'] ) ) : ?>
			<section class="cvc-related-section">
				<h2>Kiến thức liên quan</h2>
				<ul class="cvc-related-list">
					<?php foreach ( $topic['knowledge_items'] as $item ) : ?>
						<li><?php echo esc_html( $item['title'] ?? '' ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
