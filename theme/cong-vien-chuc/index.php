<?php
/**
 * Homepage (Phase 10A.3 - đối chiếu trực tiếp ảnh benchmark thật tại
 * docs/ui-benchmark/homepage-reference.png). Kiến trúc: Hero -> Value
 * strip -> [2 cột: (Khóa học nổi bật + Tài nguyên ôn tập) | (Tuyển dụng
 * mới nhất, sidebar dọc)] -> CTA banner. KHÔNG còn section "Thi trắc
 * nghiệm" riêng (ảnh benchmark thật không có - đề thi được gộp vào card
 * "Thi trắc nghiệm" trong Tài nguyên ôn tập + badge category "Ôn thi"
 * trên course card). Vẫn đúng 6 lệnh gọi API (1 lần list() mỗi domain),
 * không tạo dữ liệu giả để lấp chỗ trống (Phần 31/32).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_result      = ( new CVC_Course_Service() )->list( array( 'per_page' => 4 ) );
$topic_result       = ( new CVC_Topic_Service() )->list( array( 'per_page' => 1 ) );
$recruitment_result = ( new CVC_Recruitment_Service() )->list( array( 'per_page' => 5 ) );
$knowledge_result   = ( new CVC_Knowledge_Service() )->list( array( 'per_page' => 1 ) );
$exam_result        = ( new CVC_Exam_Service() )->list( array( 'per_page' => 1 ) );
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

$courses         = cvc_homepage_items( $course_result );
$recruitments    = cvc_homepage_items( $recruitment_result );
$topics_total    = cvc_homepage_total( $topic_result );
$knowledge_total = cvc_homepage_total( $knowledge_result );
$exams_total     = cvc_homepage_total( $exam_result );
$legal_total     = cvc_homepage_total( $legal_result );

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

	<!-- ============ COURSES + RESOURCES (trái) | TUYỂN DỤNG (phải) ============ -->
	<section class="cvc-section" id="khoa-hoc">
		<div class="container cvc-home-split">

			<div class="cvc-home-main">
				<?php cvc_render_section_header( 'Khóa học nổi bật', 'Xem tất cả khóa học', cvc_courses_url(), '&#127891;' ); ?>

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

				<div class="cvc-resource-grid cvc-resource-grid--footer" id="tai-nguyen">
					<?php
					cvc_render_resource_hub_card(
						'&#128218;',
						'Cẩm nang công chức, viên chức',
						'Chủ đề ôn tập và kiến thức chuyên môn hệ thống theo môn thi.',
						cvc_knowledge_url(),
						$knowledge_total + $topics_total,
						'nội dung',
						'blue'
					);
					cvc_render_resource_hub_card(
						'&#127942;',
						'Thi nâng ngạch, thăng hạng',
						'Đề thi trắc nghiệm theo môn thi, bám sát cấu trúc thi thật.',
						cvc_exams_url(),
						$exams_total,
						'đề thi',
						'purple'
					);
					cvc_render_resource_hub_card(
						'&#128220;',
						'Văn bản & chính sách',
						'Văn bản pháp luật, quy định liên quan trực tiếp tới công vụ.',
						cvc_legal_documents_url(),
						$legal_total,
						'văn bản',
						'green'
					);
					?>
				</div>
			</div>

			<aside class="cvc-recruitment-panel" id="tuyen-dung" aria-label="Tuyển dụng mới nhất">
				<div class="cvc-recruitment-panel__header">
					<h2><span class="cvc-recruitment-panel__icon" aria-hidden="true">&#128188;</span> Tuyển dụng mới nhất</h2>
					<a class="cvc-section__more" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xem tất cả &rarr;</a>
				</div>

				<?php if ( ! $recruitment_result['ok'] ) : ?>
					<?php cvc_render_error_state(); ?>
				<?php elseif ( empty( $recruitments ) ) : ?>
					<?php cvc_render_premium_empty_state( '&#128188;', 'Chưa có tin phù hợp', 'Tin tuyển dụng mới sẽ được cập nhật liên tục.', 'Xem tất cả tin tuyển dụng', cvc_recruitments_url() ); ?>
				<?php else : ?>
					<div class="cvc-recruitment-list">
						<?php foreach ( $recruitments as $recruitment ) : ?>
							<?php cvc_render_recruitment_list_item( $recruitment ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</aside>

		</div>
	</section>

	<?php cvc_render_homepage_cta_banner(); ?>

</main>

<?php get_footer(); ?>
