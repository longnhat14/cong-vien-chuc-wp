<?php
/**
 * Đo hoạt động (28/10/2026, Người dùng & hoạt động - giai đoạn 2).
 *  - assets/js/track.js gửi navigator.sendBeacon thẳng tới {API}/api/t: 1 lượt xem mỗi trang + nhịp thời gian
 *    (chỉ khi tab đang mở và có thao tác trong 3 phút gần nhất). Không qua WordPress -> không tốn tài nguyên WP.
 *  - Người đã đăng nhập: cookie cvc_ta = "u:{id}.{chữ ký HMAC}" (chữ ký bằng CVC_PROXY_KEY, backend kiểm tra).
 *    Khách: mã ngẫu nhiên cvc_vid (ẩn danh). Nguồn đến lần đầu: cookie cvc_src (utm / trang giới thiệu) -> gửi kèm khi đăng ký.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_TRACK_COOKIE = 'cvc_ta';

function cvc_track_actor_for( int $user_id ): string {
	$key = defined( 'CVC_PROXY_KEY' ) && is_string( CVC_PROXY_KEY ) ? CVC_PROXY_KEY : '';

	return '' === $key ? '' : 'u:' . $user_id . '.' . substr( hash_hmac( 'sha256', 'u:' . $user_id, $key ), 0, 24 );
}

function cvc_track_set_cookie( string $value, int $expires ): void {
	setcookie( CVC_TRACK_COOKIE, $value, array( 'expires' => $expires, 'path' => '/', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ) );
	$_COOKIE[ CVC_TRACK_COOKIE ] = $value;
}

add_action( 'template_redirect', 'cvc_track_sync_cookie', 1 );
function cvc_track_sync_cookie(): void {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}
	$has = isset( $_COOKIE[ CVC_TRACK_COOKIE ] ) && '' !== $_COOKIE[ CVC_TRACK_COOKIE ];
	if ( null === cvc_auth_token() ) {
		if ( $has ) {
			cvc_track_set_cookie( '', time() - HOUR_IN_SECONDS );
		}
		return;
	}
	$user = cvc_current_user();
	$id   = is_array( $user ) ? (int) ( $user['id'] ?? 0 ) : 0;
	if ( $id <= 0 ) {
		return;
	}
	$want = cvc_track_actor_for( $id );
	if ( '' !== $want && ( ! $has || (string) $_COOKIE[ CVC_TRACK_COOKIE ] !== $want ) ) {
		cvc_track_set_cookie( $want, time() + 30 * DAY_IN_SECONDS );
	}
}

add_action( 'wp_enqueue_scripts', 'cvc_track_assets', 90 );
function cvc_track_assets(): void {
	if ( 'admin' === (string) get_query_var( 'cvc_page' ) || ! function_exists( 'cvc_public_api_url' ) ) {
		return;
	}
	$f = get_theme_file_path( '/assets/js/track.js' );
	wp_enqueue_script( 'cvc-track', get_theme_file_uri( '/assets/js/track.js' ), array(), (string) filemtime( $f ), true );
	wp_add_inline_script( 'cvc-track', 'window.CVC_T=' . wp_json_encode( array( 'u' => cvc_public_api_url() . '/api/t' ) ) . ';', 'before' );
}

/** Nguồn đến lần đầu (cookie cvc_src do track.js ghi) -> trường gửi kèm khi đăng ký. */
function cvc_track_signup_fields(): array {
	$raw = isset( $_COOKIE['cvc_src'] ) ? json_decode( wp_unslash( (string) $_COOKIE['cvc_src'] ), true ) : null;
	if ( ! is_array( $raw ) ) {
		return array();
	}

	return array_filter(
		array(
			'signup_source'  => mb_substr( sanitize_text_field( (string) ( $raw['s'] ?? '' ) ), 0, 120 ),
			'signup_landing' => mb_substr( sanitize_text_field( (string) ( $raw['l'] ?? '' ) ), 0, 191 ),
		)
	);
}
