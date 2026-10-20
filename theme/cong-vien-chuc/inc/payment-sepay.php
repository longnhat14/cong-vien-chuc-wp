<?php
/**
 * Thanh toán chuyển khoản VietQR qua SePay (20/10/2026).
 * - Mọi luồng mua (tài liệu, khóa học, combo, đề cá nhân hóa) gọi cvc_order_pay_url(): VNPay -> URL cổng,
 *   SePay -> trang /thanh-toan/{mã đơn}/ có mã QR; trang tự hỏi trạng thái và tự chuyển về khi đã mở khóa.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CVC_PE_BACK_COOKIE' ) ) {
	define( 'CVC_PE_BACK_COOKIE', 'cvc_buy_back' );
}

function cvc_payment_url( string $code ): string {
	return home_url( '/thanh-toan/' . rawurlencode( $code ) . '/' );
}

/** Ghi nhớ trang để quay lại sau khi thanh toán (chỉ URL nội bộ). */
function cvc_remember_buy_back( string $back ): void {
	$back = (string) wp_validate_redirect( $back, '' );
	if ( '' !== $back ) {
		setcookie( CVC_PE_BACK_COOKIE, $back, array( 'expires' => time() + 7200, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
	}
}

/**
 * URL tiếp theo sau khi tạo đơn: cổng VNPay hoặc trang QR SePay. Rỗng nếu API không trả gì dùng được.
 */
function cvc_order_pay_url( array $result, string $back = '' ): string {
	$data = (array) ( $result['data']['data'] ?? array() );
	if ( '' !== $back ) {
		cvc_remember_buy_back( $back );
	}
	if ( ! empty( $data['payment_url'] ) ) {
		return (string) $data['payment_url'];
	}
	if ( ! empty( $data['order_code'] ) && 'sepay' === ( $data['method'] ?? '' ) ) {
		return cvc_payment_url( (string) $data['order_code'] );
	}

	return '';
}

/* AJAX: trạng thái đơn (trang QR hỏi mỗi vài giây). */
add_action( 'wp_ajax_cvc_order_status', 'cvc_ajax_order_status' );
add_action( 'wp_ajax_nopriv_cvc_order_status', 'cvc_ajax_order_status' );

function cvc_ajax_order_status(): void {
	check_ajax_referer( 'cvc_order_status', 'nonce' );
	nocache_headers();
	$token = cvc_auth_token();
	$code  = strtoupper( preg_replace( '/[^0-9A-Za-z]/', '', (string) ( $_GET['code'] ?? '' ) ) );
	if ( null === $token || '' === $code ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn.' ), 401 );
	}
	$res = ( new CVC_Api_Client( null, 8 ) )->get( '/api/orders/' . rawurlencode( $code ), array(), $token );
	if ( ! $res['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $res ) ), (int) ( $res['status'] ?: 500 ) );
	}
	$d = (array) ( $res['data']['data'] ?? array() );
	wp_send_json_success( array( 'status' => (string) ( $d['status'] ?? '' ), 'received' => (int) ( $d['received'] ?? 0 ), 'total' => (int) ( $d['total_amount'] ?? 0 ) ) );
}

/* Đổi sang VNPay từ trang QR. */
add_action( 'admin_post_cvc_order_vnpay', 'cvc_handle_order_vnpay' );
add_action( 'admin_post_nopriv_cvc_order_vnpay', 'cvc_handle_order_vnpay' );

function cvc_handle_order_vnpay(): void {
	check_admin_referer( 'cvc_order_vnpay' );
	$token = cvc_require_token_or_die();
	$code  = strtoupper( preg_replace( '/[^0-9A-Za-z]/', '', (string) ( $_POST['code'] ?? '' ) ) );
	$res   = ( new CVC_Api_Client( null, 15 ) )->post( '/api/orders/' . rawurlencode( $code ) . '/vnpay', array(), $token );
	$url   = (string) ( $res['data']['data']['payment_url'] ?? '' );
	if ( ! $res['ok'] || '' === $url ) {
		cvc_redirect_with_notice( cvc_payment_url( $code ), 'error', $res['ok'] ? 'Không khởi tạo được VNPay.' : cvc_api_error_message( $res ), null );
		return;
	}
	// phpcs:ignore WordPress.Security.SafeRedirect -- cổng VNPay (domain ngoài).
	wp_redirect( $url );
	exit;
}
