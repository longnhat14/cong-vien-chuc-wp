<?php
/**
 * Authentication (Phase 10, Phần VIII) - đăng ký/đăng nhập/đăng xuất
 * qua Laravel Sanctum thật (POST /api/auth/register, /api/auth/login,
 * /api/auth/logout, GET /api/auth/me).
 *
 * Token lưu trong 1 cookie HttpOnly (JS KHÔNG đọc được - chống XSS đánh
 * cắp token), Secure khi site chạy HTTPS, SameSite=Lax. WordPress đóng
 * vai trò "confidential client" - đọc token phía server, tự đính kèm
 * header Authorization khi gọi API user-scoped, KHÔNG BAO GIỜ expose
 * token ra HTML/JS.
 *
 * Trạng thái đăng nhập LUÔN xác thực lại qua GET /api/auth/me (memoized
 * 1 lần/request) - không tin bất kỳ cookie/session nào khác ngoài chính
 * token, tránh hiển thị sai trạng thái nếu token đã bị revoke phía
 * backend.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_AUTH_COOKIE = 'cvc_auth_token';

function cvc_auth_token(): ?string {
	$token = $_COOKIE[ CVC_AUTH_COOKIE ] ?? null;

	return is_string( $token ) && '' !== $token ? $token : null;
}

function cvc_set_auth_cookie( string $token ): void {
	setcookie(
		CVC_AUTH_COOKIE,
		$token,
		array(
			'expires'  => time() + ( 30 * DAY_IN_SECONDS ),
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	// Cho chính request hiện tại (setcookie chỉ có hiệu lực từ request sau)
	// dùng ngay được token vừa set - cần cho luồng login -> redirect ->
	// trang tiếp theo đọc lại $_COOKIE trong CÙNG lần chuyển hướng nếu có.
	$_COOKIE[ CVC_AUTH_COOKIE ] = $token;
}

function cvc_clear_auth_cookie(): void {
	setcookie(
		CVC_AUTH_COOKIE,
		'',
		array(
			'expires'  => time() - HOUR_IN_SECONDS,
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	unset( $_COOKIE[ CVC_AUTH_COOKIE ] );
}

/**
 * User hiện tại (GET /api/auth/me thật) - memoized trong request, trả
 * null nếu chưa đăng nhập HOẶC token không còn hợp lệ (backend trả 401 -
 * tự dọn cookie chết luôn để lần sau khỏi gọi API vô ích).
 *
 * @return array<string, mixed>|null
 */
function cvc_current_user(): ?array {
	static $memo = null;
	static $resolved = false;

	if ( $resolved ) {
		return $memo;
	}

	$resolved = true;
	$token    = cvc_auth_token();

	if ( null === $token ) {
		return null;
	}

	$result = ( new CVC_Api_Client() )->get( '/api/auth/me', array(), $token );

	if ( ! $result['ok'] ) {
		if ( 401 === $result['status'] ) {
			cvc_clear_auth_cookie();
		}
		return null;
	}

	$memo = $result['data']['user'] ?? $result['data'] ?? null;

	return $memo;
}

function cvc_is_logged_in(): bool {
	return null !== cvc_current_user();
}

/**
 * URL nội bộ an toàn để redirect sau login (Phần VIII "preserve intended
 * destination") - CHỈ chấp nhận path nội bộ (bắt đầu bằng '/', không
 * phải '//' - tránh protocol-relative URL trỏ ra ngoài site), tuyệt đối
 * không redirect tới URL do người dùng cung cấp tùy ý (open redirect).
 */
function cvc_safe_redirect_target( ?string $raw ): string {
	if ( empty( $raw ) ) {
		return home_url( '/tai-khoan/' );
	}

	if ( ! str_starts_with( $raw, '/' ) || str_starts_with( $raw, '//' ) ) {
		return home_url( '/tai-khoan/' );
	}

	return home_url( $raw );
}

/**
 * Nhãn lỗi tiếng Việt tự nhiên theo status code (Phần XXI) - KHÔNG hiện
 * raw exception/message kỹ thuật từ backend cho user, trừ lỗi 422 (message
 * validation của Laravel vốn đã viết cho người dùng cuối, tiếng Việt).
 */
function cvc_api_error_message( array $result ): string {
	switch ( $result['status'] ) {
		case 401:
			return 'Email hoặc mật khẩu không đúng, hoặc phiên đăng nhập đã hết hạn.';
		case 403:
			return 'Bạn không có quyền thực hiện thao tác này.';
		case 404:
			return 'Không tìm thấy nội dung yêu cầu.';
		case 422:
			return cvc_extract_validation_message( $result );
		case 429:
			return 'Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút.';
		case 0:
			return 'Không thể kết nối tới máy chủ, vui lòng thử lại sau.';
		default:
			return 'Đã có lỗi xảy ra, vui lòng thử lại sau.';
	}
}

function cvc_extract_validation_message( array $result ): string {
	$data = $result['data'] ?? null;

	if ( is_array( $data ) && ! empty( $data['errors'] ) && is_array( $data['errors'] ) ) {
		foreach ( $data['errors'] as $messages ) {
			if ( is_array( $messages ) && ! empty( $messages[0] ) ) {
				return (string) $messages[0];
			}
		}
	}

	if ( is_array( $data ) && ! empty( $data['message'] ) ) {
		return (string) $data['message'];
	}

	return 'Dữ liệu chưa hợp lệ, vui lòng kiểm tra lại.';
}

/*
 * ============================================================
 * FORM HANDLERS - admin-post.php, có nonce, hoạt động cho cả user đã
 * đăng nhập WP lẫn khách (nopriv) vì đây là auth CỦA LARAVEL, không phải
 * WP user.
 * ============================================================
 */

add_action( 'admin_post_nopriv_cvc_login', 'cvc_handle_login' );
add_action( 'admin_post_cvc_login', 'cvc_handle_login' );

function cvc_handle_login(): void {
	check_admin_referer( 'cvc_login' );

	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$password = (string) ( $_POST['password'] ?? '' );
	$redirect = cvc_safe_redirect_target( isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : null );

	if ( '' === $email || '' === $password ) {
		cvc_redirect_with_notice( cvc_login_url(), 'error', 'Vui lòng nhập đầy đủ email và mật khẩu.', $redirect );
		return;
	}

	$result = ( new CVC_Api_Client() )->post(
		'/api/auth/login',
		array(
			'email'    => $email,
			'password' => $password,
		)
	);

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_login_url(), 'error', cvc_api_error_message( $result ), $redirect );
		return;
	}

	$token = $result['data']['token'] ?? null;

	if ( empty( $token ) ) {
		cvc_redirect_with_notice( cvc_login_url(), 'error', 'Đăng nhập thất bại, vui lòng thử lại.', $redirect );
		return;
	}

	cvc_set_auth_cookie( (string) $token );
	wp_safe_redirect( $redirect );
	exit;
}

add_action( 'admin_post_nopriv_cvc_register', 'cvc_handle_register' );
add_action( 'admin_post_cvc_register', 'cvc_handle_register' );

function cvc_handle_register(): void {
	check_admin_referer( 'cvc_register' );

	$name                 = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email                = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$password             = (string) ( $_POST['password'] ?? '' );
	$password_confirmation = (string) ( $_POST['password_confirmation'] ?? '' );

	$result = ( new CVC_Api_Client() )->post(
		'/api/auth/register',
		array(
			'name'                  => $name,
			'email'                 => $email,
			'password'              => $password,
			'password_confirmation' => $password_confirmation,
		)
	);

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_register_url(), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	$token = $result['data']['token'] ?? null;

	if ( empty( $token ) ) {
		// Backend đăng ký thành công nhưng không cấp token luôn (thiết kế
		// khác) - đưa người dùng sang trang đăng nhập thay vì coi là lỗi.
		cvc_redirect_with_notice( cvc_login_url(), 'success', 'Đăng ký thành công, vui lòng đăng nhập.', null );
		return;
	}

	cvc_set_auth_cookie( (string) $token );
	wp_safe_redirect( home_url( '/tai-khoan/' ) );
	exit;
}

add_action( 'admin_post_nopriv_cvc_logout', 'cvc_handle_logout' );
add_action( 'admin_post_cvc_logout', 'cvc_handle_logout' );

function cvc_handle_logout(): void {
	check_admin_referer( 'cvc_logout' );

	$token = cvc_auth_token();

	if ( null !== $token ) {
		// Best-effort - kể cả khi API logout lỗi (token đã hết hạn từ
		// trước chẳng hạn), vẫn phải xóa cookie phía WordPress để không
		// kẹt trạng thái "tưởng đã đăng nhập".
		( new CVC_Api_Client() )->post( '/api/auth/logout', array(), $token );
	}

	cvc_clear_auth_cookie();
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

/**
 * Lưu tạm thông báo (lỗi/thành công) qua 1 cookie ngắn hạn (session-style,
 * KHÔNG dùng PHP native session - WordPress không bật session mặc định và
 * bật thêm dễ xung đột plugin cache) để hiện lại sau redirect (Post/Redirect/
 * Get - tránh resubmit form khi refresh). Xóa ngay sau khi đọc 1 lần.
 */
function cvc_redirect_with_notice( string $base_url, string $type, string $message, ?string $redirect_to ): void {
	setcookie(
		'cvc_notice',
		wp_json_encode( array( 'type' => $type, 'message' => $message ) ),
		array(
			'expires'  => time() + MINUTE_IN_SECONDS,
			'path'     => '/',
			'secure'   => is_ssl(),
			'httponly' => false, // Chỉ đọc lại bằng PHP ngay request sau, nhưng không chứa gì nhạy cảm (chỉ message hiển thị).
			'samesite' => 'Lax',
		)
	);

	$url = $base_url;
	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( wp_make_link_relative( $redirect_to ) ), $url );
	}

	wp_safe_redirect( $url );
	exit;
}

/**
 * @return array{type: string, message: string}|null
 */
function cvc_consume_notice(): ?array {
	if ( empty( $_COOKIE['cvc_notice'] ) ) {
		return null;
	}

	$decoded = json_decode( wp_unslash( $_COOKIE['cvc_notice'] ), true );
	setcookie( 'cvc_notice', '', array( 'expires' => time() - HOUR_IN_SECONDS, 'path' => '/' ) );
	unset( $_COOKIE['cvc_notice'] );

	if ( ! is_array( $decoded ) || empty( $decoded['message'] ) ) {
		return null;
	}

	return array(
		'type'    => (string) ( $decoded['type'] ?? 'error' ),
		'message' => (string) $decoded['message'],
	);
}

function cvc_login_url( ?string $redirect_to = null ): string {
	$url = home_url( '/dang-nhap/' );

	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( wp_make_link_relative( $redirect_to ) ), $url );
	}

	return $url;
}

function cvc_register_url(): string {
	return home_url( '/dang-ky/' );
}

function cvc_logout_url(): string {
	return wp_nonce_url( admin_url( 'admin-post.php?action=cvc_logout' ), 'cvc_logout' );
}

/**
 * Bắt buộc đăng nhập cho 1 trang - gọi ở đầu template trước get_header().
 * Redirect kèm intended destination (URL hiện tại) để quay lại đúng chỗ
 * sau khi login thành công (Phần VIII).
 */
function cvc_require_login(): void {
	if ( cvc_is_logged_in() ) {
		return;
	}

	$current = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '/' );
	wp_safe_redirect( cvc_login_url( $current ) );
	exit;
}
