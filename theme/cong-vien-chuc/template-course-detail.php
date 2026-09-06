<?php
/**
 * Chi tiết khóa học - /khoa-hoc/{slug}/
 *
 * Quan trọng: lessons trả về trong GET /api/courses/{slug} KHÔNG được
 * backend lọc content/video_url/file_url theo is_free (khác với endpoint
 * GET /api/courses/{slug}/lessons/{id} có lọc). Vì vậy trang này chỉ hiển
 * thị metadata của lesson (title, duration, is_free) - không bao giờ in
 * content/video_url/file_url từ payload course detail.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_course_slug' ) );

$service = new CVC_Course_Service();
$result  = $service->find( $slug );

$course   = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$course   = is_array( $data ) ? $data : null;
	$is_found = null !== $course;
}

if ( ! $is_found ) {
	if ( 404 === (int) $result['status'] ) {
		status_header( 404 );
		cvc_seo_set_noindex();
	} else {
		status_header( 503 );
	}
}

cvc_seo_set_title( $is_found ? (string) $course['title'] : 'Không tìm thấy khóa học' );

if ( $is_found ) {
	$description = $course['short_description'] ?? $course['description'] ?? '';
	if ( $description ) {
		cvc_seo_set_description( (string) $description );
	}
	cvc_seo_set_canonical( cvc_course_url( $slug ) );
}

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
			array(
				'label' => 'Khóa học',
				'url'   => cvc_courses_url(),
			),
			array( 'label' => $is_found ? (string) $course['title'] : 'Không tìm thấy' ),
		)
	);
	?>

	<?php if ( ! $is_found ) : ?>
		<h1><?php echo esc_html( 404 === (int) $result['status'] ? 'Không tìm thấy khóa học' : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( 404 === (int) $result['status'] ) {
			cvc_render_notfound_state( 'Khóa học bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a href="<?php echo esc_url( cvc_courses_url() ); ?>">&larr; Xem tất cả khóa học</a></p>
	<?php else : ?>
		<?php
		$lessons = is_array( $course['lessons'] ?? null ) ? $course['lessons'] : array();
		?>
		<header class="cvc-page-header">
			<h1><?php echo esc_html( $course['title'] ); ?></h1>
			<?php if ( ! empty( $course['short_description'] ) ) : ?>
				<p class="cvc-page-header__lead"><?php echo esc_html( $course['short_description'] ); ?></p>
			<?php endif; ?>
			<p class="cvc-page-header__meta">
				<?php echo esc_html( sprintf( '%d bài học', (int) ( $course['published_lessons_count'] ?? count( $lessons ) ) ) ); ?>
				<?php if ( ! empty( $course['duration_minutes'] ) ) : ?>
					&middot; <?php echo esc_html( sprintf( '%d phút', (int) $course['duration_minutes'] ) ); ?>
				<?php endif; ?>
			</p>
		</header>

		<?php if ( ! empty( $course['description'] ) ) : ?>
			<div class="cvc-prose"><?php echo nl2br( esc_html( $course['description'] ) ); ?></div>
		<?php endif; ?>

		<section class="cvc-lesson-list-section">
			<h2>Nội dung khóa học</h2>

			<?php if ( empty( $lessons ) ) : ?>
				<?php cvc_render_empty_state( 'Khóa học này chưa có bài học.' ); ?>
			<?php else : ?>
				<ol class="cvc-lesson-list">
					<?php foreach ( $lessons as $lesson ) : ?>
						<?php
						$lesson_id = (int) ( $lesson['id'] ?? 0 );
						if ( ! $lesson_id ) {
							continue;
						}
						?>
						<li class="cvc-lesson-list__item">
							<a class="cvc-lesson-list__title" href="<?php echo esc_url( cvc_course_lesson_url( $slug, $lesson_id ) ); ?>">
								<?php echo esc_html( $lesson['title'] ?? '' ); ?>
							</a>
							<?php cvc_render_free_badge( ! empty( $lesson['is_free'] ) ); ?>
							<?php if ( ! empty( $lesson['duration_minutes'] ) ) : ?>
								<span class="cvc-lesson-list__duration"><?php echo esc_html( sprintf( '%d phút', (int) $lesson['duration_minutes'] ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
