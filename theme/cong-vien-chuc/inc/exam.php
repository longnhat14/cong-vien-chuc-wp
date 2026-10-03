<?php
/**
 * Exam-taking AJAX handlers (Phase 10) - answer/submit/recommendations/bookmark.
 * Backend (ExamAttemptController) là nơi DUY NHẤT chấm điểm/khóa trạng thái.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/services/class-cvc-exam-attempt-service.php';
require_once __DIR__ . '/services/class-cvc-bookmark-service.php';

/* --- AJAX: LƯU ĐÁP ÁN BÀI THI --- */
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

/* --- AJAX: NỘP BÀI THI & CHẤM ĐIỂM --- */
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
		wp_send_json_error(
			array(
				'message'           => cvc_api_error_message( $result ),
				'already_submitted' => 422 === $result['status'],
			),
			$result['status'] ?: 500
		);
	}

	wp_send_json_success( $result['data'] );
}

/* --- AJAX: GIẢI THÍCH 1 CÂU SAU KHI NỘP BÀI (AI thật nếu đã cấu hình) --- */
add_action( 'wp_ajax_cvc_exam_ai_explain', 'cvc_handle_exam_ai_explain_ajax' );
add_action( 'wp_ajax_nopriv_cvc_exam_ai_explain', 'cvc_handle_exam_ai_explain_ajax' );

function cvc_handle_exam_ai_explain_ajax(): void {
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

	$result = ( new CVC_Exam_Attempt_Service() )->aiExplain( $attemptId, $questionId, $token );

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), $result['status'] ?: 500 );
	}

	wp_send_json_success( $result['data'] );
}

/* --- AJAX: LẤY GỢI Ý AI SAU KHI NỘP BÀI --- */
add_action( 'wp_ajax_cvc_exam_recommendations', 'cvc_handle_exam_recommendations_ajax' );
add_action( 'wp_ajax_nopriv_cvc_exam_recommendations', 'cvc_handle_exam_recommendations_ajax' );

function cvc_handle_exam_recommendations_ajax(): void {
	check_ajax_referer( 'cvc_exam_attempt', 'nonce' );

	$token = cvc_auth_token();

	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn.' ), 401 );
	}

	$attemptId = absint( $_POST['attempt_id'] ?? 0 );

	if ( 0 === $attemptId ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}

	$result = ( new CVC_Exam_Attempt_Service() )->recommendations( $attemptId, $token );

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), $result['status'] ?: 500 );
	}

	wp_send_json_success( $result['data'] );
}

/* --- AJAX: CẬP NHẬT CONFIDENCE/FLAG RIÊNG CHO 1 CÂU --- */
add_action( 'wp_ajax_cvc_exam_meta', 'cvc_handle_exam_meta_ajax' );
add_action( 'wp_ajax_nopriv_cvc_exam_meta', 'cvc_handle_exam_meta_ajax' );

function cvc_handle_exam_meta_ajax(): void {
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

	$payload = array();

	if ( isset( $_POST['confidence_level'] ) && '' !== $_POST['confidence_level'] ) {
		$payload['confidence_level'] = sanitize_key( wp_unslash( $_POST['confidence_level'] ) );
	}

	if ( isset( $_POST['is_flagged'] ) ) {
		$payload['is_flagged'] = '1' === $_POST['is_flagged'];
	}

	$result = ( new CVC_Exam_Attempt_Service() )->updateMeta( $attemptId, $questionId, $payload, $token );

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), $result['status'] ?: 500 );
	}

	wp_send_json_success( $result['data'] );
}

/* --- AJAX: ĐÁNH DẤU BÀI THI / BOOKMARK --- */
add_action( 'wp_ajax_cvc_exam_bookmark', 'cvc_handle_exam_bookmark_ajax' );
add_action( 'wp_ajax_nopriv_cvc_exam_bookmark', 'cvc_handle_exam_bookmark_ajax' );

function cvc_handle_exam_bookmark_ajax(): void {
	check_ajax_referer( 'cvc_exam_attempt', 'nonce' );

	$token = cvc_auth_token();

	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn.' ), 401 );
	}

	$examId = absint( $_POST['exam_id'] ?? 0 );

	if ( 0 === $examId ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}

	$result = ( new CVC_Bookmark_Service() )->store( 'exam', $examId, $token );

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), $result['status'] ?: 500 );
	}

	wp_send_json_success( $result['data'] );
}
