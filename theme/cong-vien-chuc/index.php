<?php
/**
 * Homepage (Phase 10A.4 - rebuild theo ảnh master mới
 * docs/ui-benchmark/homepage-reference.png, phân tích trực tiếp bằng
 * Read). Kiến trúc: Hero (search + quick-search tags + floating feature
 * card) -> Value strip (panel viền) -> [2 cột: Khóa học nổi bật + Tài
 * nguyên ôn tập | Tuyển dụng mới nhất] -> Goal direction section -> CTA
 * banner. KHÔNG có Statistics strip (ảnh có nhưng số liệu trong ảnh chỉ
 * là ví dụ minh họa - dữ liệu DEV thật hiện quá nhỏ để hiển thị như "số
 * liệu quy mô" mà không gây hiểu lầm, xem docs/PHASE_10A.4_VISUAL_REBUILD.md).
 * KHÔNG có Community section (ảnh có "Cộng đồng học tập" nhưng backend
 * chưa có tính năng hỏi đáp/thảo luận thật - Phần 20 "không fake discussion").
 * Vẫn đúng 6 lệnh gọi API domain, không tạo dữ liệu giả (Phần 31/32).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
			</div>
			<div class="cvc-hero__visual">
				<div class="cvc-hero__visual-inner" aria-hidden="true">
					<?php cvc_render_hero_illustration(); ?>
				</div>
				<?php cvc_render_hero_feature_card(); ?>
			</div>
		</div>
	</section>

	<!-- ============ VALUE STRIP ============ -->
	<section class="cvc-section cvc-section--tight">
		<div class="container">
			<div class="cvc-value-panel">
				<?php cvc_render_value_strip(); ?>
			</div>
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
				<?php elseif ( 1 === count( $courses ) ) : ?>
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
						'&#128218;',
						'Chủ đề ôn tập',
						'Kiến thức hệ thống theo từng chủ đề, môn thi cụ thể.',
						cvc_topics_url(),
						$topics_total,
						'chủ đề',
						'blue'
					);
					cvc_render_resource_hub_card(
						'&#128161;',
						'Cẩm nang kiến thức',
						'Bài viết, kinh nghiệm giúp ích cho công việc và ôn thi.',
						cvc_knowledge_url(),
						$knowledge_total,
						'bài viết',
						'purple'
					);
					cvc_render_resource_hub_card(
						'&#128220;',
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

	<!-- ============ GOAL DIRECTION ============ -->
	<?php cvc_render_goal_direction_section(); ?>

	<?php cvc_render_homepage_cta_banner(); ?>

</main>

<?php get_footer(); ?>
