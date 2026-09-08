<?php
/**
 * Homepage (Phase 10A.6 - 99% visual fidelity so với
 * docs/ui-benchmark/homepage-reference.png). Kiến trúc: Hero -> Value
 * strip -> Statistics strip (chỉ DEV, xem dưới) -> Learning Journey ->
 * [2 cột: Khóa học nổi bật + Tài nguyên ôn tập | Tuyển dụng mới nhất] ->
 * Goal direction section -> CTA banner.
 *
 * REAL DATA luôn ưu tiên (Phần 31). Khi DEV data quá ít để tái tạo đúng
 * mật độ ảnh benchmark, CHO PHÉP lấp bằng DESIGN FIXTURE
 * (inc/homepage-fixtures.php) - CHỈ khi CVC_HOMEPAGE_DEMO_CONTENT=true
 * (mặc định false, production luôn an toàn - xem cvc_homepage_demo_enabled()).
 * Fixture không bao giờ THAY THẾ real data đã có, chỉ ĐIỀN THÊM cho đủ,
 * và luôn gắn badge "Demo" khi hiển thị (xem cvc_render_demo_badge()).
 *
 * KHÔNG có Community section (ảnh có "Cộng đồng học tập" nhưng đây là
 * ranh giới KHÔNG được vượt qua dù đã nới lỏng fixture - Phần 22/43 cấm
 * rõ "fake testimonials/community trình bày như thật", khác với design
 * fixture cho course/recruitment/statistics vốn không giả lập người dùng
 * thật). Vẫn đúng 6 lệnh gọi API domain.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/inc/homepage-fixtures.php';

$course_result      = ( new CVC_Course_Service() )->list( array( 'per_page' => 3 ) );
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
$legal_total     = cvc_homepage_total( $legal_result );

/*
 * Fallback strategy (Phần 5/31): REAL API -> DESIGN FIXTURE (chỉ khi bật
 * demo mode) -> premium empty state. Fixture CHỈ ĐIỀN THÊM cho đủ mật độ
 * 3 card, KHÔNG BAO GIỜ thay thế course/tin thật đã có - course/tin thật
 * luôn hiện TRƯỚC.
 */
$courses_is_padded = false;
if ( $course_result['ok'] && count( $courses ) < 3 && cvc_homepage_demo_enabled() ) {
	$needed             = 3 - count( $courses );
	$courses            = array_merge( $courses, array_slice( cvc_homepage_demo_courses(), 0, $needed ) );
	$courses_is_padded  = true;
}

if ( $recruitment_result['ok'] && empty( $recruitments ) && cvc_homepage_demo_enabled() ) {
	$recruitments = cvc_homepage_demo_recruitments();
}

cvc_seo_set_title( 'Ôn thi công chức, viên chức online' );
cvc_seo_set_description( 'Nền tảng hỗ trợ công chức, viên chức: khóa học theo lộ trình, tin tuyển dụng uy tín, đề thi trắc nghiệm và tài liệu hữu ích giúp bạn vững vàng trên con đường sự nghiệp.' );
cvc_seo_set_canonical( home_url( '/' ) );

get_header();
?>

<main id="main">

	<!-- ============ HERO ============ -->
	<section class="cvc-hero">
		<!-- Phase 10A.11: minh họa hero là con trực tiếp của <section>, KHÔNG
		     nằm trong .container - để ảnh bleed hết mép phải viewport và hết
		     chiều cao hero band (đúng benchmark-homepage.png mới: ảnh full-
		     bleed, không phải 1 card nhỏ nổi giữa khoảng trống). Đứng TRƯỚC
		     .container trong DOM để ở tablet/mobile (khi chuyển position:static)
		     nó tự nhiên render phía trên nội dung chữ mà không cần CSS order. -->
		<div class="cvc-hero__visual">
			<div class="cvc-hero__visual-inner" aria-hidden="true">
				<?php cvc_render_hero_illustration(); ?>
			</div>
			<?php cvc_render_hero_feature_card(); ?>
		</div>
		<div class="container">
			<div class="cvc-hero__content">
				<p class="cvc-hero__eyebrow">Nền tảng học tập và phát triển sự nghiệp công</p>
				<h1>Công chức, viên chức<br><span class="cvc-hero__accent">Học đúng &ndash; Thi tốt &ndash; Vươn xa</span></h1>
				<p class="cvc-hero__lead">
					Cung cấp khóa học chất lượng, tài liệu cập nhật, thông tin tuyển dụng uy tín
					và công cụ luyện thi hiện đại, giúp bạn vững bước trên con đường phục vụ nhân dân.
				</p>
				<?php cvc_render_search_form( '', 'cvc-hero-search-q' ); ?>
				<?php cvc_render_hero_quick_search_tags(); ?>
				<div class="cvc-hero__actions">
					<a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( cvc_exams_url() ); ?>">Bắt đầu ôn thi</a>
					<a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Khám phá tuyển dụng</a>
				</div>
				<?php cvc_render_hero_trust_signals(); ?>
			</div>
		</div>
	</section>

	<!-- ============ VALUE STRIP + STATISTICS (chỉ DEV) ============ -->
	<section class="cvc-section cvc-section--tight">
		<div class="container">
			<div class="cvc-value-panel">
				<?php cvc_render_value_strip(); ?>
				<?php cvc_render_homepage_statistics_strip(); ?>
			</div>
		</div>
	</section>

	<!-- ============ LEARNING JOURNEY ============ -->
	<?php cvc_render_learning_journey_section(); ?>

	<!-- ============ COURSES + RESOURCES (trái) | TUYỂN DỤNG (phải) ============ -->
	<section class="cvc-section" id="khoa-hoc">
		<div class="container cvc-home-split">

			<div class="cvc-home-main">
				<?php cvc_render_section_header( 'Khóa học nổi bật', 'Xem tất cả khóa học', cvc_courses_url(), 'courses' ); ?>

				<?php if ( ! $course_result['ok'] ) : ?>
					<?php cvc_render_error_state(); ?>
				<?php elseif ( empty( $courses ) ) : ?>
					<?php cvc_render_premium_empty_state( 'courses', 'Chưa có khóa học nào', 'Khóa học mới sẽ sớm được cập nhật tại đây.', 'Xem tất cả khóa học', cvc_courses_url() ); ?>
				<?php elseif ( 1 === count( $courses ) && ! $courses_is_padded ) : ?>
					<?php cvc_render_course_card_featured( $courses[0] ); ?>
				<?php else : ?>
					<div class="cvc-card-grid cvc-card-grid--3">
						<?php foreach ( $courses as $course ) : ?>
							<?php cvc_render_course_card( $course, 3 ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="cvc-resource-grid cvc-resource-grid--footer" id="tai-nguyen">
					<?php
					cvc_render_resource_hub_card(
						'topics',
						'Chủ đề ôn tập',
						'Kiến thức hệ thống theo từng chủ đề, môn thi cụ thể.',
						cvc_topics_url(),
						$topics_total,
						'chủ đề',
						'blue'
					);
					cvc_render_resource_hub_card(
						'lightbulb',
						'Cẩm nang kiến thức',
						'Bài viết, kinh nghiệm giúp ích cho công việc và ôn thi.',
						cvc_knowledge_url(),
						$knowledge_total,
						'bài viết',
						'purple'
					);
					cvc_render_resource_hub_card(
						'legal',
						'Văn bản pháp luật',
						'Văn bản, quy định liên quan trực tiếp tới công vụ.',
						cvc_legal_documents_url(),
						$legal_total,
						'văn bản',
						'green'
					);
					?>
				</div>
			</div>

			<aside class="cvc-recruitment-panel" id="tuyen-dung" aria-label="Tuyển dụng mới nhất">
				<?php cvc_render_recruitment_panel_banner(); ?>
				<div class="cvc-recruitment-panel__header">
					<h2><span class="cvc-recruitment-panel__icon"><?php cvc_render_icon( 'briefcase', 20 ); ?></span> Tuyển dụng mới nhất</h2>
					<a class="cvc-section__more" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xem tất cả &rarr;</a>
				</div>

				<?php if ( ! $recruitment_result['ok'] ) : ?>
					<?php cvc_render_error_state(); ?>
				<?php elseif ( empty( $recruitments ) ) : ?>
					<?php cvc_render_premium_empty_state( 'briefcase', 'Chưa có tin phù hợp', 'Tin tuyển dụng mới sẽ được cập nhật liên tục.', 'Xem tất cả tin tuyển dụng', cvc_recruitments_url() ); ?>
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

	<!-- ============ GOAL DIRECTION ============ -->
	<?php cvc_render_goal_direction_section(); ?>

	<?php cvc_render_homepage_cta_banner(); ?>

</main>

<?php get_footer(); ?>
