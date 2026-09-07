<?php
/**
 * Chi tiết đề thi - /thi-trac-nghiem/{slug}/
 *
 * SECURITY: GET /api/exams/{slug} (ExamController::show()) đã tự giới
 * hạn cột trả về cho questions (id, exam_subject_id, topic_id,
 * knowledge_item_id, question_text, question_type, difficulty,
 * estimated_seconds) và options (id, question_id, option_key,
 * option_text, sort_order) - KHÔNG có is_correct/explanation/answer key
 * nào trong response. Trang này chỉ render đúng những field đó, tuyệt
 * đối không tự suy diễn đáp án đúng, không thêm chấm điểm client-side.
 * Đây chỉ là trang xem trước đề thi (câu hỏi + phương án), không phải
 * chức năng làm bài.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_exam_slug' ) );

$service = new CVC_Exam_Service();
$result  = $service->find( $slug );

$exam     = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$exam     = is_array( $data ) ? $data : null;
	$is_found = null !== $exam;
}

if ( ! $is_found ) {
	if ( 404 === (int) $result['status'] ) {
		status_header( 404 );
		cvc_seo_set_noindex();
	} else {
		status_header( 503 );
	}
}

cvc_seo_set_title( $is_found ? (string) $exam['title'] : 'Không tìm thấy đề thi' );

$breadcrumb_items = array(
	array(
		'label' => 'Trang chủ',
		'url'   => home_url( '/' ),
	),
	array(
		'label' => 'Thi trắc nghiệm',
		'url'   => cvc_exams_url(),
	),
	array( 'label' => $is_found ? (string) $exam['title'] : 'Không tìm thấy' ),
);

if ( $is_found ) {
	if ( ! empty( $exam['description'] ) ) {
		cvc_seo_set_description( (string) $exam['description'] );
	}
	cvc_seo_set_canonical( cvc_exam_url( $slug ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();
?>

<main id="main" class="container cvc-page">
	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<?php if ( ! $is_found ) : ?>
		<h1><?php echo esc_html( 404 === (int) $result['status'] ? 'Không tìm thấy đề thi' : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( 404 === (int) $result['status'] ) {
			cvc_render_notfound_state( 'Đề thi bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_exams_url() ); ?>">&larr; Xem tất cả đề thi</a></p>
	<?php else : ?>
		<?php
		$examSubjects = is_array( $exam['exam_subjects'] ?? null ) ? $exam['exam_subjects'] : array();
		$questions    = is_array( $exam['questions'] ?? null ) ? $exam['questions'] : array();
		?>
		<header class="cvc-page-header">
			<h1><?php echo esc_html( $exam['title'] ); ?></h1>
			<p class="cvc-page-header__meta">
				<?php
				$meta_parts = array();
				if ( ! empty( $exam['duration_minutes'] ) ) {
					$meta_parts[] = sprintf( '%d phút', (int) $exam['duration_minutes'] );
				}
				if ( isset( $exam['questions_count'] ) ) {
					$meta_parts[] = sprintf( '%d câu hỏi', (int) $exam['questions_count'] );
				} elseif ( ! empty( $exam['total_questions'] ) ) {
					$meta_parts[] = sprintf( '%d câu hỏi', (int) $exam['total_questions'] );
				}
				if ( isset( $exam['passing_score'] ) && null !== $exam['passing_score'] ) {
					$meta_parts[] = sprintf( 'Điểm đạt: %s', $exam['passing_score'] );
				}
				echo esc_html( implode( ' · ', $meta_parts ) );
				?>
			</p>
		</header>

		<?php if ( ! empty( $exam['description'] ) ) : ?>
			<div class="cvc-prose"><?php echo nl2br( esc_html( $exam['description'] ) ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $examSubjects ) ) : ?>
			<section class="cvc-related-section">
				<h2>Môn thi</h2>
				<div class="cvc-card-grid">
					<?php foreach ( $examSubjects as $subject ) : ?>
						<article class="cvc-card">
							<div class="cvc-card__body">
								<h3 class="cvc-card__title"><?php echo esc_html( $subject['name'] ?? '' ); ?></h3>
								<?php if ( isset( $subject['question_count'] ) ) : ?>
									<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d câu hỏi', (int) $subject['question_count'] ) ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $questions ) ) : ?>
			<section class="cvc-lesson-list-section">
				<h2>Xem trước câu hỏi</h2>
				<p class="cvc-page-header__meta">Đây là bản xem trước nội dung đề thi, không phải chức năng làm bài.</p>
				<ol class="cvc-question-list">
					<?php foreach ( $questions as $index => $question ) : ?>
						<li class="cvc-question-list__item">
							<p class="cvc-question-list__text">
								<?php echo esc_html( sprintf( 'Câu %d: %s', $index + 1, $question['question_text'] ?? '' ) ); ?>
							</p>
							<?php if ( ! empty( $question['options'] ) && is_array( $question['options'] ) ) : ?>
								<ul class="cvc-question-list__options">
									<?php foreach ( $question['options'] as $option ) : ?>
										<li>
											<?php if ( ! empty( $option['option_key'] ) ) : ?>
												<strong><?php echo esc_html( $option['option_key'] ); ?>.</strong>
											<?php endif; ?>
											<?php echo esc_html( $option['option_text'] ?? '' ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
