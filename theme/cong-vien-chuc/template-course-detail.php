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

$breadcrumb_items = array(
	array(
		'label' => 'Trang chủ',
		'url'   => home_url( '/' ),
	),
	array(
		'label' => 'Khóa học',
		'url'   => cvc_courses_url(),
	),
	array( 'label' => $is_found ? (string) $course['title'] : 'Không tìm thấy' ),
);

if ( $is_found ) {
	$description = $course['short_description'] ?? $course['description'] ?? '';
	if ( $description ) {
		cvc_seo_set_description( (string) $description );
	}
	cvc_seo_set_canonical( cvc_course_url( $slug ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );

	$course_schema = cvc_build_course_jsonld( $course );
	if ( null !== $course_schema ) {
		cvc_seo_add_json_ld( $course_schema );
	}
}

get_header();
?>

<main id="main" class="container cvc-page">
	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<?php if ( ! $is_found ) : ?>
		<h1><?php echo esc_html( 404 === (int) $result['status'] ? 'Không tìm thấy khóa học' : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( 404 === (int) $result['status'] ) {
			cvc_render_notfound_state( 'Khóa học bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_courses_url() ); ?>">&larr; Xem tất cả khóa học</a></p>
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

		<div class="cvc-detail-actions">
			<?php cvc_render_bookmark_button( 'course', (int) ( $course['id'] ?? 0 ) ); ?>
		</div>

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

		<?php
		/*
		 * Cross-domain linking (Phase 4A, Phần 9) - GET /api/courses/{slug}
		 * đã eager-load recruitments/positions/exams/exam_subjects/topics
		 * (xem CourseController::show()) nhưng trước đây WP chưa render.
		 * Chỉ hiển thị field cần thiết (title/name + slug) - không in
		 * nguyên object dù API có thể trả thêm field khác (VD source_url
		 * trong recruitments) để tránh rò rỉ field chưa được chốt công khai.
		 */
		$related_recruitments = is_array( $course['recruitments'] ?? null ) ? $course['recruitments'] : array();
		$related_positions    = is_array( $course['positions'] ?? null ) ? $course['positions'] : array();
		$related_exams        = is_array( $course['exams'] ?? null ) ? $course['exams'] : array();
		$related_subjects     = is_array( $course['exam_subjects'] ?? null ) ? $course['exam_subjects'] : array();
		$related_topics       = is_array( $course['topics'] ?? null ) ? $course['topics'] : array();
		?>

		<?php if ( ! empty( $related_recruitments ) ) : ?>
			<section class="cvc-related-section">
				<h2>Tuyển dụng liên quan</h2>
				<?php cvc_render_related_link_list( $related_recruitments, 'cvc_recruitment_url', 'title' ); ?>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $related_exams ) ) : ?>
			<section class="cvc-related-section">
				<h2>Kỳ thi liên quan</h2>
				<?php cvc_render_related_link_list( $related_exams, 'cvc_exam_url', 'title' ); ?>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $related_topics ) ) : ?>
			<section class="cvc-related-section">
				<h2>Chủ đề liên quan</h2>
				<?php cvc_render_related_link_list( $related_topics, 'cvc_topic_url', 'name' ); ?>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $related_positions ) || ! empty( $related_subjects ) ) : ?>
			<section class="cvc-related-section">
				<h2>Chuẩn bị cho vị trí / môn thi</h2>
				<?php
				/*
				 * Position/ExamSubject chưa có route chi tiết riêng trong
				 * theme (chỉ xem lồng trong Recruitment/Exam detail) - hiển
				 * thị tên để cung cấp thông tin, không tạo link vì chưa có
				 * trang đích hợp lệ (tránh link gãy).
				 */
				$names = array_filter(
					array_merge(
						array_map( fn( $p ) => (string) ( $p['name'] ?? '' ), $related_positions ),
						array_map( fn( $s ) => (string) ( $s['name'] ?? '' ), $related_subjects )
					)
				);
				?>
				<?php if ( ! empty( $names ) ) : ?>
					<p class="cvc-prose"><?php echo esc_html( implode( ', ', $names ) ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
