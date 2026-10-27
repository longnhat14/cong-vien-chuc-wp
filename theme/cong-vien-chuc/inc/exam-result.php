<?php
/**
 * Trang KẾT QUẢ & PHÂN TÍCH BÀI THI (18/10/2026) - /ket-qua-thi/{attempt_id}/
 *
 * Giao diện theo thiết kế congvienchuc-smart-path-v6 (trang độc lập như trang chủ v17:
 * không dùng style.css chung, chỉ nạp exam-result.css/js). Dữ liệu thật từ
 * GET /api/exam-attempts/{id}/report (chỉ chính chủ, lượt đã nộp).
 *
 * AJAX (nonce cvc_exam_result, cần đăng nhập):
 *  - cvc_result_tasks    lưu tiến độ nhiệm vụ học + tài liệu đã học (PUT .../tasks)
 *  - cvc_result_practice tạo bài luyện cá nhân wrong|weak|recheck và bắt đầu (POST .../practice)
 *  - cvc_result_start    bắt đầu 1 đề đã phát hành (POST /api/exams/{id}/attempts)
 *  - cvc_result_save_job lưu tin tuyển dụng (POST /api/bookmarks)
 *  - cvc_result_explain  giải thích 1 câu (POST .../questions/{qid}/ai-explain)
 *
 * @package CongVienChuc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_exam_result_url( int $attempt_id ): string {
	return home_url( '/ket-qua-thi/' . $attempt_id . '/' );
}

function cvc_is_exam_result_page(): bool {
	return 'exam-result' === (string) get_query_var( 'cvc_page' );
}

/** Số kiểu Việt Nam: 8,5 / 12 (bỏ ,0). */
function cvc_result_num( $n, int $dec = 1 ): string {
	if ( null === $n || '' === $n ) {
		return '--';
	}
	$s = number_format( (float) $n, $dec, ',', '.' );

	return false !== strpos( $s, ',' ) ? rtrim( rtrim( $s, '0' ), ',' ) : $s;
}

function cvc_result_duration( ?int $sec ): string {
	if ( null === $sec ) {
		return '';
	}
	$h = intdiv( $sec, 3600 );
	$m = intdiv( $sec % 3600, 60 );
	$s = $sec % 60;
	if ( $h > 0 ) {
		return $h . ' giờ ' . $m . ' phút';
	}

	return ( $m > 0 ? $m . ' phút ' : '' ) . $s . ' giây';
}

/* ------------------------------------------------------------------
 * Tài nguyên: chỉ CSS/JS của thiết kế.
 * ------------------------------------------------------------------ */
add_action( 'wp_enqueue_scripts', 'cvc_exam_result_assets', 100 );

function cvc_exam_result_assets(): void {
	if ( ! cvc_is_exam_result_page() ) {
		return;
	}
	wp_dequeue_style( 'cvc-style' );
	wp_dequeue_script( 'cvc-nav' );
	wp_dequeue_script( 'cvc-scroll-reveal' );
	wp_dequeue_script( 'cvc-homepage-nextgen' );

	$css = get_theme_file_path( '/assets/css/exam-result.css' );
	$js  = get_theme_file_path( '/assets/js/exam-result.js' );
	wp_enqueue_style( 'cvc-exam-result', get_theme_file_uri( '/assets/css/exam-result.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-exam-result', get_theme_file_uri( '/assets/js/exam-result.js' ), array(), (string) filemtime( $js ), true );
	if ( ! empty( $GLOBALS['cvc_exam_result_cfg'] ) ) {
		wp_add_inline_script( 'cvc-exam-result', 'window.CVC_RESULT=' . wp_json_encode( $GLOBALS['cvc_exam_result_cfg'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
	}
}

/* ------------------------------------------------------------------
 * AJAX
 * ------------------------------------------------------------------ */
function cvc_result_ajax_token(): string {
	check_ajax_referer( 'cvc_exam_result', 'nonce' );
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.' ), 401 );
	}

	return $token;
}

function cvc_result_ajax_fail( array $result ): void {
	cvc_verify_ajax_fail_if_required( $result );
	wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), (int) ( $result['status'] ?? 0 ) ?: 500 );
}

/** Báo câu sai (25/10/2026) -> POST /api/questions/{id}/reports. */
function cvc_result_ajax_report(): void {
	$token    = cvc_result_ajax_token();
	$question = absint( $_POST['question_id'] ?? 0 );
	$reason   = sanitize_key( (string) ( $_POST['reason'] ?? '' ) );
	if ( ! $question || '' === $reason ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}
	$result = ( new CVC_Api_Client( null, 20 ) )->post(
		'/api/questions/' . $question . '/reports',
		array(
			'reason'          => $reason,
			'note'            => sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) ),
			'exam_attempt_id' => absint( $_POST['attempt_id'] ?? 0 ) ?: null,
		),
		$token
	);
	if ( ! $result['ok'] ) {
		cvc_result_ajax_fail( $result );
	}
	wp_send_json_success( array( 'message' => (string) ( $result['data']['message'] ?? 'Cảm ơn bạn! Báo cáo đã được ghi nhận.' ) ) );
}

foreach ( array( 'tasks', 'practice', 'start', 'save_job', 'explain', 'report' ) as $cvc_result_action ) {
	add_action( 'wp_ajax_cvc_result_' . $cvc_result_action, 'cvc_result_ajax_' . $cvc_result_action );
	add_action( 'wp_ajax_nopriv_cvc_result_' . $cvc_result_action, 'cvc_result_ajax_' . $cvc_result_action );
}
unset( $cvc_result_action );

function cvc_result_ajax_tasks(): void {
	$token   = cvc_result_ajax_token();
	$attempt = absint( $_POST['attempt_id'] ?? 0 );
	$clean   = static fn ( $list, string $re ) => array_values( array_filter( array_map( 'sanitize_key', array_map( 'strval', (array) $list ) ), static fn ( $v ) => (bool) preg_match( $re, $v ) ) );
	$body    = array( 'done' => $clean( wp_unslash( $_POST['done'] ?? array() ), '/^[a-z_0-9]{1,30}$/' ) );
	if ( isset( $_POST['resources'] ) ) {
		$body['resources'] = $clean( wp_unslash( $_POST['resources'] ), '/^(art|kn|doc)[0-9]{1,10}$/' );
	}
	$result = ( new CVC_Api_Client( null, 15 ) )->put( '/api/exam-attempts/' . $attempt . '/tasks', $body, $token );
	if ( ! $result['ok'] ) {
		cvc_result_ajax_fail( $result );
	}
	wp_send_json_success( $result['data']['data'] ?? array() );
}

function cvc_result_ajax_practice(): void {
	$token   = cvc_result_ajax_token();
	$attempt = absint( $_POST['attempt_id'] ?? 0 );
	$kind    = sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) );
	$result  = ( new CVC_Api_Client( null, 40 ) )->post( '/api/exam-attempts/' . $attempt . '/practice', array( 'kind' => $kind ), $token );
	$id      = (int) ( $result['data']['data']['id'] ?? 0 );
	if ( ! $result['ok'] || $id <= 0 ) {
		cvc_result_ajax_fail( $result );
	}
	wp_send_json_success( array( 'url' => cvc_exam_attempt_url( $id ) ) );
}

function cvc_result_ajax_start(): void {
	$token  = cvc_result_ajax_token();
	$exam   = absint( $_POST['exam_id'] ?? 0 );
	$result = ( new CVC_Exam_Attempt_Service() )->start( $exam, 'mock', $token );
	$id     = (int) ( $result['data']['data']['id'] ?? 0 );
	if ( ! $result['ok'] || $id <= 0 ) {
		cvc_result_ajax_fail( $result );
	}
	wp_send_json_success( array( 'url' => cvc_exam_attempt_url( $id ) ) );
}

function cvc_result_ajax_save_job(): void {
	$token  = cvc_result_ajax_token();
	$id     = absint( $_POST['recruitment_id'] ?? 0 );
	$result = ( new CVC_Bookmark_Service() )->store( 'recruitment', $id, $token );
	if ( ! $result['ok'] && 409 !== (int) ( $result['status'] ?? 0 ) ) {
		cvc_result_ajax_fail( $result );
	}
	wp_send_json_success( array( 'saved' => true ) );
}

function cvc_result_ajax_explain(): void {
	$token    = cvc_result_ajax_token();
	$attempt  = absint( $_POST['attempt_id'] ?? 0 );
	$question = absint( $_POST['question_id'] ?? 0 );
	$result   = ( new CVC_Exam_Attempt_Service() )->aiExplain( $attempt, $question, $token );
	if ( ! $result['ok'] ) {
		cvc_result_ajax_fail( $result );
	}
	wp_send_json_success( $result['data']['data'] ?? array() );
}
