<?php
/**
 * Homepage - giới thiệu nền tảng + khu vực Khóa học/Chủ đề dùng dữ liệu
 * thật từ Laravel API. Chỉ gọi đúng 2 API (courses, topics) - không lặp
 * request trong loop.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_service = new CVC_Course_Service();
$course_result  = $course_service->list( array( 'per_page' => 3 ) );

$topic_service = new CVC_Topic_Service();
$topic_result  = $topic_service->list( array( 'per_page' => 6 ) );

$courses = array();
if ( $course_result['ok'] ) {
	$courses = $course_result['data']['data']['data'] ?? array();
}

$topics = array();
if ( $topic_result['ok'] ) {
	$topics = $topic_result['data']['data']['data'] ?? array();
}

cvc_seo_set_title( 'Ôn thi công chức, viên chức online' );
cvc_seo_set_description( 'Nền tảng ôn thi công chức, viên chức: khóa học theo lộ trình và chủ đề kiến thức được hệ thống hóa theo từng lĩnh vực thi tuyển.' );
cvc_seo_set_canonical( home_url( '/' ) );

get_header();
?>

<main>
	<section class="cvc-hero">
		<div class="container cvc-hero__inner">
			<h1>Ôn thi công chức, viên chức cùng Công Viên Chức</h1>
			<p class="cvc-hero__lead">
				Khóa học theo lộ trình và kiến thức hệ thống theo từng chủ đề thi tuyển,
				giúp bạn ôn tập đúng trọng tâm.
			</p>
			<div class="cvc-hero__actions">
				<a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( cvc_courses_url() ); ?>">Khám phá khóa học</a>
				<a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_topics_url() ); ?>">Xem chủ đề</a>
			</div>
		</div>
	</section>

	<section class="cvc-section">
		<div class="container">
			<?php cvc_render_section_header( 'Khóa học', 'Xem tất cả khóa học', cvc_courses_url() ); ?>

			<?php if ( ! $course_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $courses ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có khóa học nào.' ); ?>
			<?php else : ?>
				<div class="cvc-card-grid">
					<?php foreach ( $courses as $course ) : ?>
						<?php cvc_render_course_card( $course, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="cvc-section cvc-section--alt">
		<div class="container">
			<?php cvc_render_section_header( 'Chủ đề', 'Xem tất cả chủ đề', cvc_topics_url() ); ?>

			<?php if ( ! $topic_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $topics ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có chủ đề nào.' ); ?>
			<?php else : ?>
				<div class="cvc-card-grid">
					<?php foreach ( $topics as $topic ) : ?>
						<?php cvc_render_topic_card( $topic, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
