<?php
/**
 * User Engagement Tracking phía WordPress (Phase 10) - nhận sự kiện từ JS
 * (assets/js/engagement.js) qua admin-ajax, chuyển tiếp NGUYÊN VẸN tới
 * POST /api/engagement/events (Phase 9). WP không tự lưu event nào -
 * chỉ là proxy có nonce, bản thân Laravel vẫn là nơi validate/whitelist
 * type-target_type-target_id thật sự (Phần XXII - "backend luôn là
 * authority").
 *
 * Fail-silent theo đúng yêu cầu (Phần XI Engagement): lỗi ở đây (chưa đăng
 * nhập, network lỗi, 422...) không được làm hỏng trải nghiệm - luôn trả
 * response bình thường, JS phía client cũng cố tình không hiện lỗi này
 * cho người dùng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_cvc_track_event', 'cvc_handle_track_event' );
add_action( 'wp_ajax_nopriv_cvc_track_event', 'cvc_handle_track_event' );

function cvc_handle_track_event(): void {
	check_ajax_referer( 'cvc_track_event', 'nonce' );

	$token = cvc_auth_token();

	// Chưa đăng nhập - engagement API yêu cầu auth:sanctum, không có gì để
	// gửi. Trả 200 "no-op" thay vì lỗi, JS không cần phân biệt.
	if ( null === $token ) {
		wp_send_json_success( array( 'skipped' => true ) );
	}

	$type        = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
	$target_type = isset( $_POST['target_type'] ) ? sanitize_key( wp_unslash( $_POST['target_type'] ) ) : '';
	$target_id   = isset( $_POST['target_id'] ) ? absint( $_POST['target_id'] ) : 0;

	if ( '' === $type || '' === $target_type || 0 === $target_id ) {
		wp_send_json_success( array( 'skipped' => true ) );
	}

	$payload = array(
		'type'        => $type,
		'target_type' => $target_type,
		'target_id'   => $target_id,
	);

	if ( isset( $_POST['source'] ) ) {
		$payload['metadata']['source'] = sanitize_text_field( wp_unslash( $_POST['source'] ) );
	}

	// Backend tự whitelist/validate type-target_type-target_id-ownership
	// (EngagementController) - WP không lặp lại logic đó, chỉ forward.
	( new CVC_Api_Client() )->post( '/api/engagement/events', $payload, $token );

	wp_send_json_success();
}
