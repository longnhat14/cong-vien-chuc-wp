<?php
/**
 * Chi tiết tin tuyển dụng - /tuyen-dung/{slug}/
 *
 * Chỉ hiển thị field thực sự có trong response của
 * GET /api/recruitments/{slug} (xem RecruitmentController::show()) -
 * không suy diễn thêm field nào khác.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_recruitment_slug' ) );

$service = new CVC_Recruitment_Service();
$result  = $service->find( $slug );

$recruitment = null;
$is_found    = false;

if ( $result['ok'] ) {
	$data        = $result['data']['data'] ?? null;
	$recruitment = is_array( $data ) ? $data : null;
	$is_found    = null !== $recruitment;
}

if ( ! $is_found ) {
	if ( 404 === (int) $result['status'] ) {
		status_header( 404 );
		cvc_seo_set_noindex();
	} else {
		status_header( 503 );
	}
}

cvc_seo_set_title( $is_found ? (string) $recruitment['title'] : 'Không tìm thấy tin tuyển dụng' );

if ( $is_found ) {
	if ( ! empty( $recruitment['summary'] ) ) {
		cvc_seo_set_description( (string) $recruitment['summary'] );
	}
	cvc_seo_set_canonical( cvc_recruitment_url( $slug ) );
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
				'label' => 'Tuyển dụng',
				'url'   => cvc_recruitments_url(),
			),
			array( 'label' => $is_found ? (string) $recruitment['title'] : 'Không tìm thấy' ),
		)
	);
	?>

	<?php if ( ! $is_found ) : ?>
		<h1><?php echo esc_html( 404 === (int) $result['status'] ? 'Không tìm thấy tin tuyển dụng' : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( 404 === (int) $result['status'] ) {
			cvc_render_notfound_state( 'Tin tuyển dụng bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">&larr; Xem tất cả tin tuyển dụng</a></p>
	<?php else : ?>
		<?php
		$dates      = is_array( $recruitment['dates'] ?? null ) ? $recruitment['dates'] : array();
		$agency     = is_array( $recruitment['agency'] ?? null ) ? $recruitment['agency'] : null;
		$province   = is_array( $recruitment['province'] ?? null ) ? $recruitment['province'] : null;
		$adminUnit  = is_array( $recruitment['admin_unit'] ?? null ) ? $recruitment['admin_unit'] : null;
		$positions  = is_array( $recruitment['positions'] ?? null ) ? $recruitment['positions'] : array();
		$exams      = is_array( $recruitment['exams'] ?? null ) ? $recruitment['exams'] : array();
		$rCourses   = is_array( $recruitment['courses'] ?? null ) ? $recruitment['courses'] : array();
		?>
		<header class="cvc-page-header">
			<?php if ( ! empty( $recruitment['recruitment_type'] ) ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( cvc_recruitment_type_label( $recruitment['recruitment_type'] ) ); ?></span>
			<?php endif; ?>
			<h1><?php echo esc_html( $recruitment['title'] ); ?></h1>
			<p class="cvc-page-header__meta">
				<?php
				$meta_parts = array();
				if ( ! empty( $recruitment['location'] ) ) {
					$meta_parts[] = $recruitment['location'];
				}
				if ( ! empty( $recruitment['total_positions'] ) ) {
					$meta_parts[] = sprintf( '%d chỉ tiêu', (int) $recruitment['total_positions'] );
				}
				if ( ! empty( $dates['application_deadline'] ) ) {
					$meta_parts[] = sprintf( 'Hạn nộp hồ sơ: %s', cvc_format_date_vn( $dates['application_deadline'] ) );
				}
				echo esc_html( implode( ' · ', $meta_parts ) );
				?>
			</p>
		</header>

		<?php if ( ! empty( $recruitment['summary'] ) ) : ?>
			<div class="cvc-prose"><?php echo nl2br( esc_html( $recruitment['summary'] ) ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $dates['announcement_date'] ) || ! empty( $dates['application_start_date'] ) || ! empty( $dates['application_deadline'] ) ) : ?>
			<section class="cvc-related-section">
				<h2>Thời gian</h2>
				<ul class="cvc-related-list">
					<?php if ( ! empty( $dates['announcement_date'] ) ) : ?>
						<li><?php echo esc_html( sprintf( 'Ngày thông báo: %s', cvc_format_date_vn( $dates['announcement_date'] ) ) ); ?></li>
					<?php endif; ?>
					<?php if ( ! empty( $dates['application_start_date'] ) ) : ?>
						<li><?php echo esc_html( sprintf( 'Bắt đầu nhận hồ sơ: %s', cvc_format_date_vn( $dates['application_start_date'] ) ) ); ?></li>
					<?php endif; ?>
					<?php if ( ! empty( $dates['application_deadline'] ) ) : ?>
						<li><?php echo esc_html( sprintf( 'Hạn nộp hồ sơ: %s', cvc_format_date_vn( $dates['application_deadline'] ) ) ); ?></li>
					<?php endif; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $agency ) : ?>
			<section class="cvc-related-section">
				<h2>Đơn vị tuyển dụng</h2>
				<p class="cvc-prose">
					<strong><?php echo esc_html( $agency['name'] ?? '' ); ?></strong>
					<?php if ( ! empty( $agency['address'] ) ) : ?>
						<br><?php echo esc_html( $agency['address'] ); ?>
					<?php endif; ?>
					<?php if ( ! empty( $agency['phone'] ) ) : ?>
						<br>Điện thoại: <a href="<?php echo esc_url( 'tel:' . $agency['phone'] ); ?>"><?php echo esc_html( $agency['phone'] ); ?></a>
					<?php endif; ?>
					<?php if ( ! empty( $agency['email'] ) ) : ?>
						<br>Email: <a href="<?php echo esc_url( 'mailto:' . $agency['email'] ); ?>"><?php echo esc_html( $agency['email'] ); ?></a>
					<?php endif; ?>
					<?php if ( ! empty( $agency['website'] ) ) : ?>
						<br><a href="<?php echo esc_url( $agency['website'] ); ?>" target="_blank" rel="noopener noreferrer">Website đơn vị</a>
					<?php endif; ?>
				</p>
			</section>
		<?php endif; ?>

		<?php if ( $province || $adminUnit ) : ?>
			<p class="cvc-page-header__meta">
				<?php
				$area_parts = array();
				if ( $province && ! empty( $province['name'] ) ) {
					$area_parts[] = $province['name'];
				}
				if ( $adminUnit && ! empty( $adminUnit['name'] ) ) {
					$area_parts[] = $adminUnit['name'];
				}
				echo esc_html( implode( ' - ', $area_parts ) );
				?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $positions ) ) : ?>
			<section class="cvc-lesson-list-section">
				<h2>Vị trí tuyển dụng</h2>
				<?php foreach ( $positions as $position ) : ?>
					<article class="cvc-card cvc-position-card">
						<div class="cvc-card__body">
							<h3 class="cvc-card__title"><?php echo esc_html( $position['name'] ?? '' ); ?></h3>
							<?php if ( ! empty( $position['quantity'] ) ) : ?>
								<p class="cvc-card__meta"><?php echo esc_html( sprintf( 'Chỉ tiêu: %d', (int) $position['quantity'] ) ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $position['education_level'] ) ) : ?>
								<p class="cvc-card__meta"><?php echo esc_html( $position['education_level'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $position['job_description'] ) ) : ?>
								<p class="cvc-card__excerpt"><?php echo nl2br( esc_html( $position['job_description'] ) ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $position['requirements'] ) ) : ?>
								<p class="cvc-card__excerpt"><strong>Yêu cầu:</strong> <?php echo nl2br( esc_html( $position['requirements'] ) ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $position['courses'] ) && is_array( $position['courses'] ) ) : ?>
								<p class="cvc-card__meta">
									Khóa học liên quan:
									<?php foreach ( $position['courses'] as $i => $pc ) : ?>
										<?php if ( empty( $pc['slug'] ) ) : continue; endif; ?>
										<?php echo $i > 0 ? ', ' : ''; ?><a href="<?php echo esc_url( cvc_course_url( $pc['slug'] ) ); ?>"><?php echo esc_html( $pc['title'] ?? '' ); ?></a>
									<?php endforeach; ?>
								</p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $exams ) ) : ?>
			<section class="cvc-related-section">
				<h2>Đề thi liên quan</h2>
				<ul class="cvc-related-list">
					<?php foreach ( $exams as $exam ) : ?>
						<?php if ( empty( $exam['slug'] ) ) : continue; endif; ?>
						<li><a href="<?php echo esc_url( cvc_exam_url( $exam['slug'] ) ); ?>"><?php echo esc_html( $exam['title'] ?? '' ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $rCourses ) ) : ?>
			<section class="cvc-related-section">
				<h2>Khóa học liên quan</h2>
				<ul class="cvc-related-list">
					<?php foreach ( $rCourses as $course ) : ?>
						<?php if ( empty( $course['slug'] ) ) : continue; endif; ?>
						<li><a href="<?php echo esc_url( cvc_course_url( $course['slug'] ) ); ?>"><?php echo esc_html( $course['title'] ?? '' ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
