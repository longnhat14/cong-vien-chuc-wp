<?php
/**
 * Homepage (Phase 10A.2 - Premium Product UI). Kiến trúc mới thay thế 6
 * section lặp lại gần giống nhau (Phase 4A): Hero -> Value strip -> Khóa
 * học -> Tuyển dụng -> Thi trắc nghiệm -> Resource hub (Chủ đề/Kiến thức/
 * Văn bản gộp 1 hàng) -> CTA. Vẫn đúng 6 lệnh gọi API (1 lần list() mỗi
 * domain, không lặp trong loop) - domain đang rỗng có empty state riêng,
 * không tạo dữ liệu giả để lấp chỗ trống (Phần 31/32).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_result      = ( new CVC_Course_Service() )->list( array( 'per_page' => 4 ) );
$topic_result       = ( new CVC_Topic_Service() )->list( array( 'per_page' => 1 ) );
$recruitment_result = ( new CVC_Recruitment_Service() )->list( array( 'per_page' => 4 ) );
$knowledge_result   = ( new CVC_Knowledge_Service() )->list( array( 'per_page' => 1 ) );
$exam_result        = ( new CVC_Exam_Service() )->list( array( 'per_page' => 3 ) );
$legal_result       = ( new CVC_Legal_Document_Service() )->list( array( 'per_page' => 1 ) );

/**
 * Rút gọn payload list() -> mảng item, giữ nguyên nếu API lỗi.
 */
function cvc_homepage_items( array $result ): array {
	if ( ! $result['ok'] ) {
		return array();
	}

	return $result['data']['data']['data'] ?? array();
}

/**
 * Tổng số bản ghi thật (total) từ payload paginator - dùng cho resource
 * hub card (chỉ hiển thị khi > 0, xem cvc_render_resource_hub_card()).
 */
function cvc_homepage_total( array $result ): int {
	if ( ! $result['ok'] ) {
		return 0;
	}

	return (int) ( $result['data']['data']['total'] ?? 0 );
}

$courses          = cvc_homepage_items( $course_result );
$recruitments     = cvc_homepage_items( $recruitment_result );
$exams            = cvc_homepage_items( $exam_result );
$topics_total     = cvc_homepage_total( $topic_result );
$knowledge_total  = cvc_homepage_total( $knowledge_result );
$legal_total      = cvc_homepage_total( $legal_result );

cvc_seo_set_title( 'Ôn thi công chức, viên chức online' );
cvc_seo_set_description( 'Nền tảng hỗ trợ công chức, viên chức: khóa học theo lộ trình, tin tuyển dụng uy tín, đề thi trắc nghiệm và tài liệu hữu ích giúp bạn vững vàng trên con đường sự nghiệp.' );
cvc_seo_set_canonical( home_url( '/' ) );

get_header();
?>

<main id="main">

	<!-- ============ HERO ============ -->
	<section class="cvc-hero">
		<div class="container cvc-hero__grid">
			<div class="cvc-hero__content">
				<p class="cvc-hero__eyebrow">Nền tảng hỗ trợ công chức, viên chức</p>
				<h1>Công chức, viên chức<br>học tập &ndash; phát triển &ndash; thăng tiến</h1>
				<p class="cvc-hero__lead">
					Cung cấp khóa học chất lượng, thông tin tuyển dụng uy tín và tài liệu hữu ích
					giúp bạn vững vàng trên con đường sự nghiệp.
				</p>
				<?php cvc_render_search_form( '', 'cvc-hero-search-q' ); ?>
				<div class="cvc-hero__actions">
					<a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( cvc_exams_url() ); ?>">Bắt đầu ôn thi</a>
					<a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Khám phá tuyển dụng</a>
				</div>
			</div>
			<div class="cvc-hero__visual" aria-hidden="true">
				<?php cvc_render_hero_illustration(); ?>
			</div>
		</div>
	</section>

	<!-- ============ VALUE STRIP ============ -->
	<section class="cvc-section cvc-section--tight">
		<div class="container">
			<?php cvc_render_value_strip(); ?>
		</div>
	</section>

	<!-- ============ COURSES ============ -->
	<section class="cvc-section" id="khoa-hoc">
		<div class="container">
			<?php cvc_render_section_header( 'Khóa học nổi bật', 'Xem tất cả khóa học', cvc_courses_url() ); ?>

			<?php if ( ! $course_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $courses ) ) : ?>
				<?php cvc_render_premium_empty_state( '&#127891;', 'Chưa có khóa học nào', 'Khóa học mới sẽ sớm được cập nhật tại đây.', 'Xem tất cả khóa học', cvc_courses_url() ); ?>
			<?php else : ?>
				<div class="cvc-card-grid cvc-card-grid--4">
					<?php foreach ( $courses as $course ) : ?>
						<?php cvc_render_course_card( $course, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- ============ RECRUITMENT (tint) ============ -->
	<section class="cvc-section cvc-section--tint" id="tuyen-dung">
		<div class="container">
			<?php cvc_render_section_header( 'Tuyển dụng mới nhất', 'Xem tất cả tin tuyển dụng', cvc_recruitments_url() ); ?>

			<?php if ( ! $recruitment_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $recruitments ) ) : ?>
				<?php cvc_render_premium_empty_state( '&#128188;', 'Hiện chưa có tin tuyển dụng phù hợp', 'Tin tuyển dụng công chức, viên chức mới sẽ được cập nhật liên tục - hãy quay lại sau hoặc đặt mục tiêu để nhận gợi ý phù hợp.', 'Xem tất cả tin tuyển dụng', cvc_recruitments_url() ); ?>
			<?php else : ?>
				<div class="cvc-card-grid cvc-card-grid--4">
					<?php foreach ( $recruitments as $recruitment ) : ?>
						<?php cvc_render_recruitment_card( $recruitment, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- ============ EXAMS (deep accent) ============ -->
	<section class="cvc-section cvc-section--deep" id="on-thi">
		<div class="container">
			<?php cvc_render_section_header( 'Luyện thi trắc nghiệm', 'Xem tất cả đề thi', cvc_exams_url() ); ?>

			<?php if ( ! $exam_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $exams ) ) : ?>
				<?php cvc_render_premium_empty_state( '&#9989;', 'Chưa có đề thi nào', 'Đề thi trắc nghiệm theo môn thi, chủ đề sẽ sớm được cập nhật.', 'Xem tất cả đề thi', cvc_exams_url() ); ?>
			<?php else : ?>
				<div class="cvc-card-grid cvc-card-grid--3">
					<?php foreach ( $exams as $exam ) : ?>
						<?php cvc_render_exam_card( $exam, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- ============ RESOURCE HUB (Chủ đề/Kiến thức/Văn bản) ============ -->
	<section class="cvc-section">
		<div class="container">
			<?php cvc_render_section_header( 'Tài nguyên ôn tập', 'Xem trang tìm kiếm', cvc_search_url() ); ?>

			<div class="cvc-resource-grid">
				<?php
				cvc_render_resource_hub_card(
					'&#128218;',
					'Chủ đề ôn tập',
					'Kiến thức hệ thống theo từng chủ đề, gắn với môn thi cụ thể.',
					cvc_topics_url(),
					$topics_total,
					'chủ đề'
				);
				cvc_render_resource_hub_card(
					'&#128161;',
					'Cẩm nang kiến thức',
					'Bài viết, kiến thức chuyên môn phục vụ công việc và ôn thi.',
					cvc_knowledge_url(),
					$knowledge_total,
					'bài viết'
				);
				cvc_render_resource_hub_card(
					'&#128220;',
					'Văn bản pháp luật',
					'Văn bản, quy định liên quan trực tiếp tới công vụ.',
					cvc_legal_documents_url(),
					$legal_total,
					'văn bản'
				);
				?>
			</div>
		</div>
	</section>

	<?php cvc_render_homepage_cta_banner(); ?>

</main>

<?php get_footer(); ?>
