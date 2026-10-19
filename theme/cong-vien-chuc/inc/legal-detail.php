<?php
/**
 * Chi tiết văn bản pháp luật v2 (19/10/2026): CSS/JS riêng cho /van-ban-phap-luat/{slug}/ và AJAX lưu đánh dấu.
 *
 * Template đặt dữ liệu vào $GLOBALS['cvc_ld_data'] trước get_header(); hàm enqueue (chạy trong wp_head)
 * nạp script rồi wp_localize_script → window.CVC_LD.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'cvc_legal_detail_assets', 55 );

function cvc_legal_detail_assets(): void {
	if ( 'legal-document-detail' !== (string) get_query_var( 'cvc_page' ) ) {
		return;
	}
	$css = get_theme_file_path( '/assets/css/legal-detail.css' );
	$js  = get_theme_file_path( '/assets/js/legal-detail.js' );
	wp_enqueue_style( 'cvc-legal-detail', get_theme_file_uri( '/assets/css/legal-detail.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-legal-detail', get_theme_file_uri( '/assets/js/legal-detail.js' ), array(), (string) filemtime( $js ), true );
	if ( ! empty( $GLOBALS['cvc_ld_data'] ) && is_array( $GLOBALS['cvc_ld_data'] ) ) {
		wp_localize_script( 'cvc-legal-detail', 'CVC_LD', $GLOBALS['cvc_ld_data'] );
	}
}

/**
 * Lưu / bỏ lưu văn bản hoặc từng Điều. Chỉ cho người đã đăng nhập (đánh dấu gắn với tài khoản, đồng bộ mọi thiết bị).
 * POST: nonce, type (legal_document|legal_article), id, on (1|0).
 */
add_action( 'wp_ajax_cvc_ld_bm', 'cvc_legal_detail_ajax_bookmark' );
add_action( 'wp_ajax_nopriv_cvc_ld_bm', 'cvc_legal_detail_ajax_bookmark' );

function cvc_legal_detail_ajax_bookmark(): void {
	nocache_headers();
	if ( ! check_ajax_referer( 'cvc_ld_bm', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.' ), 403 );
	}
	$token = cvc_is_logged_in() ? cvc_auth_token() : null;
	if ( ! $token ) {
		wp_send_json_error( array( 'message' => 'Đăng nhập để lưu đánh dấu.', 'login' => true ), 401 );
	}
	$type = sanitize_key( (string) wp_unslash( $_POST['type'] ?? '' ) );
	$id   = absint( $_POST['id'] ?? 0 );
	$on   = ! empty( $_POST['on'] ) && '0' !== (string) $_POST['on'];
	if ( ! in_array( $type, array( 'legal_document', 'legal_article' ), true ) || $id < 1 ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}

	$client = new CVC_Api_Client( null, 8 );
	$res    = $on
		? $client->post( '/api/bookmarks', array( 'type' => $type, 'id' => $id ), $token )
		: $client->delete( '/api/bookmarks?' . http_build_query( array( 'type' => $type, 'id' => $id ) ), $token );

	$status = (int) ( $res['status'] ?? 0 );
	if ( ! empty( $res['ok'] ) || ( ! $on && 404 === $status ) ) {
		wp_send_json_success( array( 'saved' => $on ) );
	}
	if ( 401 === $status ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn — đăng nhập lại để lưu.', 'login' => true ), 401 );
	}
	wp_send_json_error( array( 'message' => 'Chưa lưu được, vui lòng thử lại.' ), $status >= 400 ? $status : 502 );
}
