<?php
/**
 * Tổng quan tài khoản v1 (19/10/2026): CSS/JS riêng + AJAX đổi khoảng thời gian biểu đồ.
 * Trang: inc/account-sections/overview-page.php (đặt $GLOBALS['cvc_ad_data'] trước get_header()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'cvc_ad_assets', 55 );

function cvc_ad_assets(): void {
	if ( empty( $GLOBALS['cvc_ad_data'] ) || ! is_array( $GLOBALS['cvc_ad_data'] ) ) {
		return;
	}
	$css = get_theme_file_path( '/assets/css/account-dashboard.css' );
	$js  = get_theme_file_path( '/assets/js/account-dashboard.js' );
	wp_enqueue_style( 'cvc-ad', get_theme_file_uri( '/assets/css/account-dashboard.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-ad', get_theme_file_uri( '/assets/js/account-dashboard.js' ), array(), (string) filemtime( $js ), true );
	wp_add_inline_script( 'cvc-ad', 'window.CVC_AD=' . wp_json_encode( $GLOBALS['cvc_ad_data'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
}

add_action( 'wp_ajax_cvc_ad_chart', 'cvc_ad_ajax_chart' );
add_action( 'wp_ajax_nopriv_cvc_ad_chart', 'cvc_ad_ajax_chart' );

function cvc_ad_ajax_chart(): void {
	nocache_headers();
	if ( ! check_ajax_referer( 'cvc_ad', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.' ), 403 );
	}
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn.' ), 401 );
	}
	$period = sanitize_key( wp_unslash( (string) ( $_GET['period'] ?? '7d' ) ) );
	$period = in_array( $period, array( '7d', '30d', '90d' ), true ) ? $period : '7d';
	$res    = ( new CVC_Api_Client( null, 15 ) )->get( '/api/me/dashboard', array( 'period' => $period, 'only' => 'chart' ), $token );
	if ( ! $res['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $res ) ), $res['status'] ?: 502 );
	}
	wp_send_json_success( $res['data']['data'] ?? array() );
}
