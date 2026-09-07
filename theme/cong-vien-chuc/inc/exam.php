<?php
/**
 * Exam-taking AJAX handlers (Phase 10) - answer/submit cần phản hồi tức
 * thì (autosave khi chọn đáp án, timer đếm ngược phía client) nên dùng
 * admin-ajax thay vì Post/Redirect/Get như các action khác trong
 * inc/actions.php. Backend (ExamAttemptController) vẫn là nơi DUY NHẤT
 * chấm điểm/khóa trạng thái - handler ở đây chỉ forward + ownership do
 * chính token Sanctum quyết định (403 nếu attempt không phải của user).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/services/class-cvc-exam-attempt-service.php';

add_action( 'wp_ajax_cvc_exam_answer', 'cvc_handle_exam_answer_ajax' );
add_action( 'wp_ajax_nopriv_cvc_exam_answer', 'cvc_handle_exam_answer_ajax' );

function cvc_handle_exam_answer_ajax(): void {
	check_ajax_referer( 'cvc_exam_attempt', 'nonce' );

	$token = cvc_auth_token();

	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn.' ), 401 );
	}

	$attemptId  = absint( $_POST['attempt_id'] ?? 0 );
	$questionId = absint( $_POST['question_id'] ?? 0 );

	if ( 0 === $attemptId || 0 === $questionId ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}

	$payload = array( 'question_id' => $questionId );

	if ( isset( $_POST['question_option_id'] ) && '' !== $_POST['question_option_id'] ) {
		$payload['question_option_id'] = absint( $_POST['question_option_id'] );
	}

	if ( isset( $_POST['option_ids'] ) && is_array( $_POST['option_ids'] ) ) {
		$payload['option_ids'] = array_map( 'absint', wp_unslash( $_POST['option_ids'] ) );
	}

	if ( isset( $_POST['answer_value'] ) && '' !== $_POST['answer_value'] ) {
		$payload['answer_value'] = sanitize_text_field( wp_unslash( $_POST['answer_value'] ) );
	}

	$result = ( new CVC_Exam_Attempt_Service() )->answer( $attemptId, $payload, $token );

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), $result['status'] ?: 500 );
	}

	wp_send_json_success( $result['data'] );
}

add_action( 'wp_ajax_cvc_exam_submit', 'cvc_handle_exam_submit_ajax' );
add_action( 'wp_ajax_nopriv_cvc_exam_submit', 'cvc_handle_exam_submit_ajax' );

function cvc_handle_exam_submit_ajax(): void {
	check_ajax_referer( 'cvc_exam_attempt', 'nonce' );

	$token = cvc_auth_token();

	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn.' ), 401 );
	}

	$attemptId = absint( $_POST['attempt_id'] ?? 0 );

	if ( 0 === $attemptId ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}

	$result = ( new CVC_Exam_Attempt_Service() )->submit( $attemptId, $token );

	if ( ! $result['ok'] ) {
		// 422 "đã nộp" (double-submit) - trả kèm cờ riêng để JS biết chỉ
		// cần load lại trang kết quả, không phải lỗi thật (Phần XVIII -
		// "chống nộp bài trùng").
		wp_send_json_error(
			array(
				'message'          => cvc_api_error_message( $result ),
				'already_submitted' => 422 === $result['status'],
			),
			$result['status'] ?: 500
		);
	}

	wp_send_json_success( $result['data'] );
}
