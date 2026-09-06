<?php
/**
 * Homepage kiểu portal - mỗi domain public (Courses, Topics, Recruitment,
 * Knowledge, Exams, Legal Documents) có đúng 1 section, dùng dữ liệu thật
 * từ Laravel API. Đúng 6 lệnh gọi API (1 lần list() mỗi domain, không
 * lặp trong loop) - domain đang rỗng vẫn hiển thị empty state nhẹ, không
 * tạo dữ liệu giả để lấp chỗ trống.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_result      = ( new CVC_Course_Service() )->list( array( 'per_page' => 3 ) );
$topic_result       = ( new CVC_Topic_Service() )->list( array( 'per_page' => 6 ) );
$recruitment_result = ( new CVC_Recruitment_Service() )->list( array( 'per_page' => 3 ) );
$knowledge_result   = ( new CVC_Knowledge_Service() )->list( array( 'per_page' => 3 ) );
$exam_result        = ( new CVC_Exam_Service() )->list( array( 'per_page' => 3 ) );
$legal_result       = ( new CVC_Legal_Document_Service() )->list( array( 'per_page' => 3 ) );

/**
 * Rút gọn payload list() -> mảng item, giữ nguyên nếu API lỗi.
 */
function cvc_homepage_items( array $result ): array {
	if ( ! $result['ok'] ) {
		return array();
	}

	return $result['data']['data']['data'] ?? array();
}

$courses      = cvc_homepage_items( $course_result );
$topics       = cvc_homepage_items( $topic_result );
$recruitments = cvc_homepage_items( $recruitment_result );
$knowledge    = cvc_homepage_items( $knowledge_result );
$exams        = cvc_homepage_items( $exam_result );
$legal        = cvc_homepage_items( $legal_result );

cvc_seo_set_title( 'Ôn thi công chức, viên chức online' );
cvc_seo_set_description( 'Nền tảng ôn thi công chức, viên chức: khóa học theo lộ trình, chủ đề kiến thức, tin tuyển dụng, đề thi trắc nghiệm và văn bản pháp luật liên quan.' );
cvc_seo_set_canonical( home_url( '/' ) );

get_header();
?>

<main id="main">
	<section class="cvc-hero">
		<div class="container cvc-hero__inner">
			<h1>Ôn thi công chức, viên chức cùng Công Viên Chức</h1>
			<p class="cvc-hero__lead">
				Khóa học theo lộ trình, kiến thức hệ thống theo từng chủ đề và tin tuyển dụng
				mới nhất - giúp bạn ôn tập đúng trọng tâm.
			</p>
			<?php cvc_render_search_form( '', 'cvc-hero-search-q' ); ?>
			<div class="cvc-hero__actions">
				<a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( cvc_courses_url() ); ?>">Khám phá khóa học</a>
				<a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xem tuyển dụng</a>
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

	<section class="cvc-section">
		<div class="container">
			<?php cvc_render_section_header( 'Tuyển dụng', 'Xem tất cả tin tuyển dụng', cvc_recruitments_url() ); ?>

			<?php if ( ! $recruitment_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $recruitments ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có tin tuyển dụng nào.' ); ?>
			<?php else : ?>
				<div class="cvc-card-grid">
					<?php foreach ( $recruitments as $recruitment ) : ?>
						<?php cvc_render_recruitment_card( $recruitment, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="cvc-section cvc-section--alt">
		<div class="container">
			<?php cvc_render_section_header( 'Kiến thức', 'Xem tất cả kiến thức', cvc_knowledge_url() ); ?>

			<?php if ( ! $knowledge_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $knowledge ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có nội dung kiến thức nào.' ); ?>
			<?php else : ?>
				<div class="cvc-card-grid">
					<?php foreach ( $knowledge as $item ) : ?>
						<?php cvc_render_knowledge_card( $item, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="cvc-section">
		<div class="container">
			<?php cvc_render_section_header( 'Thi trắc nghiệm', 'Xem tất cả đề thi', cvc_exams_url() ); ?>

			<?php if ( ! $exam_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $exams ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có đề thi nào.' ); ?>
			<?php else : ?>
				<div class="cvc-card-grid">
					<?php foreach ( $exams as $exam ) : ?>
						<?php cvc_render_exam_card( $exam, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="cvc-section cvc-section--alt">
		<div class="container">
			<?php cvc_render_section_header( 'Văn bản pháp luật', 'Xem tất cả văn bản', cvc_legal_documents_url() ); ?>

			<?php if ( ! $legal_result['ok'] ) : ?>
				<?php cvc_render_error_state(); ?>
			<?php elseif ( empty( $legal ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có văn bản pháp luật nào.' ); ?>
			<?php else : ?>
				<div class="cvc-card-grid">
					<?php foreach ( $legal as $document ) : ?>
						<?php cvc_render_legal_document_card( $document, 3 ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
