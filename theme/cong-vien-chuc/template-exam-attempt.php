<?php
/**
 * Làm bài / kết quả thi - /lam-bai/{attemptId}/ (Phase 10, Phần X).
 *
 * 1 template cho CẢ 2 trạng thái (in_progress -> giao diện làm bài,
 * submitted/expired/abandoned -> giao diện kết quả) - GET /api/exam-
 * attempts/{id} (ExamAttemptController::presentAttempt()) đã tự đảm bảo
 * KHÔNG BAO GIỜ trả is_correct/score/explanation khi còn in_progress
 * (Phần 6/28 backend) - template này KHÔNG cần tự che giấu gì thêm, chỉ
 * việc render đúng những gì API trả.
 *
 * Ownership: 403 nếu attempt không phải của user hiện tại (backend tự
 * check qua token) - WP không tự suy đoán quyền.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();
$token      = cvc_auth_token();
$attempt_id = absint( get_query_var( 'cvc_attempt_id' ) );

$result = ( new CVC_Exam_Attempt_Service() )->show( $attempt_id, $token );

cvc_seo_set_noindex();
cvc_seo_set_title( 'Làm bài thi' );

$status_code = (int) $result['status'];

if ( ! $result['ok'] ) {
	get_header();
	?>
	<main id="main" class="container cvc-page">
		<?php
		if ( 403 === $status_code ) {
			status_header( 403 );
			cvc_render_error_state( 'Bạn không có quyền xem lượt thi này.' );
		} elseif ( 404 === $status_code ) {
			status_header( 404 );
			cvc_render_notfound_state( 'Không tìm thấy lượt thi này.' );
		} else {
			cvc_render_error_state( cvc_api_error_message( $result ) );
		}
		?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">&larr; Về lịch sử làm bài</a></p>
	</main>
	<?php
	get_footer();
	return;
}

$attempt     = $result['data']['data'] ?? array();
$exam        = is_array( $attempt['exam'] ?? null ) ? $attempt['exam'] : array();
$answers     = $attempt['answers'] ?? array();
$in_progress = 'in_progress' === ( $attempt['status'] ?? '' );
$progress    = is_array( $attempt['progress'] ?? null ) ? $attempt['progress'] : array();

wp_enqueue_script(
	'cvc-exam-attempt',
	get_theme_file_uri( '/assets/js/exam-attempt.js' ),
	array(),
	wp_get_theme()->get( 'Version' ),
	true
);
wp_localize_script(
	'cvc-exam-attempt',
	'cvcExamAttempt',
	array(
		'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
		'nonce'           => wp_create_nonce( 'cvc_exam_attempt' ),
		'attemptId'       => $attempt['id'] ?? $attempt_id,
		'remainingSeconds'=> $attempt['remaining_seconds'] ?? null,
		'inProgress'      => $in_progress,
	)
);

get_header();
?>

<main id="main" class="container cvc-page cvc-exam-attempt-page">
	<header class="cvc-page-header">
		<h1><?php echo esc_html( $exam['title'] ?? 'Bài thi' ); ?></h1>
	</header>

	<?php if ( $in_progress ) : ?>

		<div class="cvc-exam-status-bar" role="status">
			<div class="cvc-exam-timer" id="cvc-exam-timer" aria-live="polite">
				<?php if ( null !== ( $attempt['remaining_seconds'] ?? null ) ) : ?>
					Thời gian còn lại: <span id="cvc-exam-timer-value">--:--</span>
				<?php else : ?>
					Không giới hạn thời gian
				<?php endif; ?>
			</div>
			<div class="cvc-exam-progress" id="cvc-exam-progress">
				Đã trả lời: <span id="cvc-exam-answered-count"><?php echo esc_html( (string) ( $progress['answered'] ?? 0 ) ); ?></span>/<?php echo esc_html( (string) ( $progress['total'] ?? count( $answers ) ) ); ?>
			</div>
			<div class="cvc-exam-autosave" id="cvc-exam-autosave" aria-live="polite"></div>
		</div>

		<nav class="cvc-exam-question-nav" aria-label="Danh sách câu hỏi">
			<?php foreach ( $answers as $index => $answer ) : ?>
				<a href="#cvc-question-<?php echo esc_attr( $answer['question_id'] ); ?>" class="cvc-exam-question-nav__item" data-question-id="<?php echo esc_attr( $answer['question_id'] ); ?>">
					<?php echo esc_html( (string) ( $index + 1 ) ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form id="cvc-exam-form" class="cvc-exam-form">
			<?php foreach ( $answers as $index => $answer ) : ?>
				<?php
				$question    = is_array( $answer['question'] ?? null ) ? $answer['question'] : array();
				$options     = is_array( $question['options'] ?? null ) ? $question['options'] : array();
				$is_multi    = 'multi_select' === ( $question['question_type'] ?? '' );
				$selected_id = $answer['question_option_id'] ?? null;
				$selected_ids = wp_list_pluck( is_array( $answer['selections'] ?? null ) ? $answer['selections'] : array(), 'question_option_id' );
				?>
				<fieldset class="cvc-exam-question" id="cvc-question-<?php echo esc_attr( $answer['question_id'] ); ?>" data-question-id="<?php echo esc_attr( $answer['question_id'] ); ?>" data-multi="<?php echo $is_multi ? '1' : '0'; ?>">
					<legend>
						<span class="cvc-exam-question__number">Câu <?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
						<?php echo esc_html( $question['question_text'] ?? '' ); ?>
						<?php if ( $is_multi ) : ?>
							<span class="cvc-form__hint">(Chọn nhiều đáp án)</span>
						<?php endif; ?>
					</legend>

					<div class="cvc-exam-options">
						<?php foreach ( $options as $option ) : ?>
							<label class="cvc-exam-option">
								<?php if ( $is_multi ) : ?>
									<input
										type="checkbox"
										name="option_ids"
										value="<?php echo esc_attr( $option['id'] ); ?>"
										<?php checked( in_array( (int) $option['id'], array_map( 'intval', $selected_ids ), true ) ); ?>
									>
								<?php else : ?>
									<input
										type="radio"
										name="question_option_id"
										value="<?php echo esc_attr( $option['id'] ); ?>"
										<?php checked( (int) $option['id'] === (int) $selected_id ); ?>
									>
								<?php endif; ?>
								<span><?php echo esc_html( $option['option_text'] ?? '' ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>
		</form>

		<div class="cvc-exam-submit-bar">
			<p class="cvc-form__hint">Bạn có thể để trống câu chưa chắc chắn - đáp án đã chọn tự động được lưu.</p>
			<button type="button" id="cvc-exam-submit-btn" class="cvc-btn cvc-btn--primary">Nộp bài</button>
		</div>

	<?php else : ?>

		<?php
		$percentage = $attempt['percentage'] ?? null;
		$passed     = $attempt['passed'] ?? null;
		$status_vn  = array(
			'submitted' => 'Đã nộp bài',
			'expired'   => 'Đã hết giờ, hệ thống tự động nộp bài',
			'abandoned' => 'Lượt thi đã bị huỷ',
		);
		?>
		<section class="cvc-exam-result">
			<p class="cvc-exam-result__status"><?php echo esc_html( $status_vn[ $attempt['status'] ?? '' ] ?? '' ); ?></p>

			<?php if ( null !== $percentage ) : ?>
				<p class="cvc-exam-result__score">
					<?php echo esc_html( sprintf( '%s%%', rtrim( rtrim( number_format( (float) $percentage, 1 ), '0' ), '.' ) ) ); ?>
					<?php if ( null !== $passed ) : ?>
						<span class="cvc-badge cvc-badge--status-<?php echo $passed ? 'active' : 'dismissed'; ?>"><?php echo $passed ? 'Đạt' : 'Chưa đạt'; ?></span>
					<?php endif; ?>
				</p>
				<p class="cvc-card__meta">
					<?php echo esc_html( sprintf( 'Đúng %d/%d câu', (int) ( $attempt['correct_count'] ?? 0 ), (int) ( $progress['total'] ?? count( $answers ) ) ) ); ?>
				</p>
			<?php else : ?>
				<?php cvc_render_empty_state( 'Bài thi chưa được chấm điểm.' ); ?>
			<?php endif; ?>
		</section>

		<section class="cvc-exam-review">
			<h2>Chi tiết bài làm</h2>
			<?php foreach ( $answers as $index => $answer ) : ?>
				<?php
				$question     = is_array( $answer['question'] ?? null ) ? $answer['question'] : array();
				$options      = is_array( $question['options'] ?? null ) ? $question['options'] : array();
				$selected_id  = $answer['question_option_id'] ?? null;
				$selected_ids = array_map( 'intval', wp_list_pluck( is_array( $answer['selections'] ?? null ) ? $answer['selections'] : array(), 'question_option_id' ) );
				$is_correct   = $answer['is_correct'] ?? null;
				?>
				<article class="cvc-exam-question cvc-exam-question--review<?php echo ( true === $is_correct ) ? ' is-correct' : ( ( false === $is_correct ) ? ' is-incorrect' : '' ); ?>">
					<h3>
						<span class="cvc-exam-question__number">Câu <?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
						<?php echo esc_html( $question['question_text'] ?? '' ); ?>
					</h3>
					<div class="cvc-exam-options cvc-exam-options--review">
						<?php foreach ( $options as $option ) : ?>
							<?php
							$oid          = (int) $option['id'];
							$was_chosen   = $oid === (int) $selected_id || in_array( $oid, $selected_ids, true );
							$option_class = 'cvc-exam-option';
							if ( ! empty( $option['is_correct'] ) ) {
								$option_class .= ' is-correct-answer';
							}
							if ( $was_chosen && empty( $option['is_correct'] ) ) {
								$option_class .= ' is-wrong-choice';
							}
							?>
							<div class="<?php echo esc_attr( $option_class ); ?>">
								<?php echo $was_chosen ? '&#9679; ' : '&#9675; '; ?>
								<?php echo esc_html( $option['option_text'] ?? '' ); ?>
								<?php if ( ! empty( $option['is_correct'] ) ) : ?>
									<span class="cvc-badge cvc-badge--status-active">Đáp án đúng</span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php if ( ! empty( $question['explanation'] ) ) : ?>
						<p class="cvc-exam-question__explanation"><strong>Giải thích:</strong> <?php echo esc_html( $question['explanation'] ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</section>

		<p>
			<a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">&larr; Về lịch sử làm bài</a>
			<?php if ( ! empty( $exam['slug'] ) ) : ?>
				<a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_exam_url( $exam['slug'] ) ); ?>">Xem lại đề thi</a>
			<?php endif; ?>
		</p>

	<?php endif; ?>
</main>

<?php get_footer(); ?>
