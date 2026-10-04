<?php
/**
 * Trang Cấu hình AI (Phase 11) - nhập tay provider + API key, LƯU QUA
 * Laravel (Setting model, mã hoá tại chỗ) - không bao giờ hard-code hay
 * nhận key qua chat/commit. Chỉ SUPER_ADMIN/ADMIN mới xem/sửa được
 * (role đọc lại từ chính Sanctum user - KHÔNG tạo tầng quyền riêng ở WP).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_user_is_admin(): bool {
	$user = cvc_current_user();

	if ( null === $user ) {
		return false;
	}

	$roles = $user['roles'] ?? array();

	return is_array( $roles )
		&& ( in_array( 'SUPER_ADMIN', $roles, true ) || in_array( 'ADMIN', $roles, true ) );
}

/*
 * Platform user KHÔNG BAO GIỜ đăng nhập WordPress core (xem inc/actions.php)
 * nên phải đăng ký cả admin_post_ và admin_post_nopriv_ để handler chạy
 * được cho user thật - giống mọi action khác trong theme.
 */
add_action( 'admin_post_nopriv_cvc_ai_settings_save', 'cvc_handle_ai_settings_save' );
add_action( 'admin_post_cvc_ai_settings_save', 'cvc_handle_ai_settings_save' );

function cvc_handle_ai_settings_save(): void {
	check_admin_referer( 'cvc_ai_settings_save' );
	$token = cvc_require_token_or_die();

	if ( ! cvc_user_is_admin() ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'cvc' ), '', array( 'response' => 403 ) );
	}

	$values = array();

	if ( isset( $_POST['ai_provider'] ) ) {
		$values['ai.provider'] = sanitize_key( wp_unslash( $_POST['ai_provider'] ) );
	}

	// Để trống ô API Key trên form nghĩa là "giữ nguyên key đang lưu" -
	// không gửi key rỗng đè lên key thật đã có.
	if ( isset( $_POST['ai_api_key'] ) && '' !== trim( wp_unslash( $_POST['ai_api_key'] ) ) ) {
		$values['ai.api_key'] = sanitize_text_field( wp_unslash( $_POST['ai_api_key'] ) );
	}

	if ( isset( $_POST['ai_model'] ) ) {
		$values['ai.model'] = sanitize_text_field( wp_unslash( $_POST['ai_model'] ) );
	}

	// Phase 15: nhà cung cấp đọc công văn scan (PDF) - dùng khi nhà cung cấp chính không đọc được file.
	if ( isset( $_POST['ai_doc_provider'] ) ) {
		$values['ai.doc_provider'] = sanitize_key( wp_unslash( $_POST['ai_doc_provider'] ) );
	}
	if ( isset( $_POST['ai_doc_api_key'] ) && '' !== trim( wp_unslash( $_POST['ai_doc_api_key'] ) ) ) {
		$values['ai.doc_api_key'] = sanitize_text_field( wp_unslash( $_POST['ai_doc_api_key'] ) );
	}
	if ( isset( $_POST['ai_doc_model'] ) ) {
		$values['ai.doc_model'] = sanitize_text_field( wp_unslash( $_POST['ai_doc_model'] ) );
	}

	$result = ( new CVC_Setting_Service() )->update( 'ai', $values, $token );

	if ( ! $result['ok'] ) {
		$message = ! empty( $result['error'] ) ? (string) $result['error'] : cvc_api_error_message( $result );
		cvc_redirect_with_notice( cvc_admin_ai_settings_url(), 'error', $message, null );
		return;
	}

	cvc_redirect_with_notice( cvc_admin_ai_settings_url(), 'success', 'Đã lưu cấu hình AI.', null );
}
