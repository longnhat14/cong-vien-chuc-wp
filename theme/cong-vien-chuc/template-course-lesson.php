<?php
/**
 * Chi tiết bài học - /khoa-hoc/{slug}/bai-hoc/{lessonId}/
 *
 * Nội dung/video/file chỉ hiển thị đúng những gì GET
 * /api/courses/{slug}/lessons/{id} trả về. Backend đã tự null hóa
 * content/video_url/file_url khi is_free=false - frontend không tự suy
 * diễn hay tìm cách hiển thị thêm.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_slug = sanitize_text_field( (string) get_query_var( 'cvc_course_slug' ) );
$lesson_id   = absint( get_query_var( 'cvc_lesson_id' ) );

$service        = new CVC_Course_Service();
$course_result  = $service->find( $course_slug );

$course = null;
if ( $course_result['ok'] && is_array( $course_result['data']['data'] ?? null ) ) {
	$course = $course_result['data']['data'];
}

$lesson        = null;
$lesson_result = null;

if ( $course && $lesson_id ) {
	$lesson_result = $service->lesson( $course_slug, $lesson_id );

	if ( $lesson_result['ok'] && is_array( $lesson_result['data']['data'] ?? null ) ) {
		$lesson = $lesson_result['data']['data'];
	}
}

$is_found = null !== $course && null !== $lesson;

/*
 * "Not found" thật (404) chỉ khi course/lesson được API xác nhận không
 * tồn tại. Mọi trường hợp khác khi chưa tìm thấy (WP_Error, timeout,
 * lesson id không hợp lệ nên chưa kịp gọi API...) đều coi là lỗi tạm thời.
 */
$is_404 = false;

if ( ! $is_found ) {
	if ( null === $course ) {
		$is_404 = 404 === (int) $course_result['status'];
	} else {
		$is_404 = null === $lesson_result || 404 === (int) $lesson_result['status'];
	}

	if ( $is_404 ) {
		status_header( 404 );
		cvc_seo_set_noindex();
	} else {
		status_header( 503 );
	}
}

/**
 * Danh sách lesson top-level của course (chỉ id/title/is_free) dùng để
 * tính điều hướng bài trước/bài sau - không dùng để hiển thị nội dung.
 */
$siblings = array();
if ( $course && is_array( $course['lessons'] ?? null ) ) {
	foreach ( $course['lessons'] as $item ) {
		if ( isset( $item['id'] ) ) {
			$siblings[] = array(
				'id'    => (int) $item['id'],
				'title' => (string) ( $item['title'] ?? '' ),
			);
		}
	}
}

$prev_lesson = null;
$next_lesson = null;

if ( $is_found ) {
	foreach ( $siblings as $index => $item ) {
		if ( $item['id'] === $lesson_id ) {
			$prev_lesson = $siblings[ $index - 1 ] ?? null;
			$next_lesson = $siblings[ $index + 1 ] ?? null;
			break;
		}
	}
}

cvc_seo_set_title( $is_found ? (string) $lesson['title'] : 'Không tìm thấy bài học' );

$breadcrumb_items = array(
	array(
		'label' => 'Trang chủ',
		'url'   => home_url( '/' ),
	),
	array(
		'label' => 'Khóa học',
		'url'   => cvc_courses_url(),
	),
	array(
		'label' => $course ? (string) $course['title'] : 'Khóa học',
		'url'   => $course ? cvc_course_url( $course_slug ) : '',
	),
	array( 'label' => $is_found ? (string) $lesson['title'] : 'Không tìm thấy' ),
);

if ( $is_found ) {
	if ( ! empty( $lesson['short_description'] ) ) {
		cvc_seo_set_description( (string) $lesson['short_description'] );
	}
	cvc_seo_set_canonical( cvc_course_lesson_url( $course_slug, $lesson_id ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();
?>

<main id="main" class="container cvc-page">
	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<?php if ( ! $is_found ) : ?>
		<?php $not_found_heading = null === $course ? 'Không tìm thấy khóa học' : 'Không tìm thấy bài học'; ?>
		<h1><?php echo esc_html( $is_404 ? $not_found_heading : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( $is_404 ) {
			cvc_render_notfound_state( 'Bài học bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_courses_url() ); ?>">&larr; Xem tất cả khóa học</a></p>
	<?php else : ?>
		<header class="cvc-page-header">
			<h1><?php echo esc_html( $lesson['title'] ); ?></h1>
			<p class="cvc-page-header__meta">
				<?php cvc_render_free_badge( ! empty( $lesson['is_free'] ) ); ?>
				<?php if ( ! empty( $lesson['duration_minutes'] ) ) : ?>
					&middot; <?php echo esc_html( sprintf( '%d phút', (int) $lesson['duration_minutes'] ) ); ?>
				<?php endif; ?>
			</p>
			<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_course_url( $course_slug ) ); ?>">&larr; Quay lại khóa học</a></p>
		</header>

		<?php
		$has_content = ! empty( $lesson['content'] ) || ! empty( $lesson['video_url'] ) || ! empty( $lesson['file_url'] );
		?>

		<?php if ( ! $has_content ) : ?>
			<div class="cvc-state cvc-state--locked">
				<p>Bài học này chỉ dành cho học viên đã đăng ký khóa học trả phí.</p>
			</div>
		<?php else : ?>
			<?php if ( ! empty( $lesson['video_url'] ) ) : ?>
				<p class="cvc-lesson-video">
					<a href="<?php echo esc_url( $lesson['video_url'] ); ?>" target="_blank" rel="noopener noreferrer">Xem video bài học</a>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $lesson['content'] ) ) : ?>
				<div class="cvc-prose"><?php echo nl2br( esc_html( $lesson['content'] ) ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $lesson['file_url'] ) ) : ?>
				<p><a href="<?php echo esc_url( $lesson['file_url'] ); ?>" target="_blank" rel="noopener noreferrer">Tải tài liệu đính kèm</a></p>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( ! empty( $lesson['children'] ) && is_array( $lesson['children'] ) ) : ?>
			<section class="cvc-lesson-list-section">
				<h2>Bài học con</h2>
				<ol class="cvc-lesson-list">
					<?php foreach ( $lesson['children'] as $child ) : ?>
						<?php $child_id = (int) ( $child['id'] ?? 0 ); ?>
						<?php if ( ! $child_id ) : continue; endif; ?>
						<li class="cvc-lesson-list__item">
							<a class="cvc-lesson-list__title" href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, $child_id ) ); ?>">
								<?php echo esc_html( $child['title'] ?? '' ); ?>
							</a>
							<?php cvc_render_free_badge( ! empty( $child['is_free'] ) ); ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>

		<?php if ( $prev_lesson || $next_lesson ) : ?>
			<nav class="cvc-lesson-nav" aria-label="<?php esc_attr_e( 'Điều hướng bài học', 'cong-vien-chuc' ); ?>">
				<?php if ( $prev_lesson ) : ?>
					<a class="cvc-lesson-nav__prev" href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, $prev_lesson['id'] ) ); ?>">
						&laquo; <?php echo esc_html( $prev_lesson['title'] ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $next_lesson ) : ?>
					<a class="cvc-lesson-nav__next" href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, $next_lesson['id'] ) ); ?>">
						<?php echo esc_html( $next_lesson['title'] ); ?> &raquo;
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
