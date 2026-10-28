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

function cvc_set_auth_cookie( string $token, bool $remember = true ): void {
	setcookie(
		CVC_AUTH_COOKIE,
		$token,
		array(
			// 28/10/2026: "Ghi nhớ đăng nhập" bỏ chọn -> cookie phiên (đóng trình duyệt là đăng xuất).
			'expires'  => $remember ? time() + ( 30 * DAY_IN_SECONDS ) : 0,
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
	if ( function_exists( 'cvc_track_set_cookie' ) && ! empty( $_COOKIE['cvc_ta'] ) ) {
		cvc_track_set_cookie( '', time() - HOUR_IN_SECONDS ); // 28/10/2026: bỏ định danh đo hoạt động khi đăng xuất.
	}
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

	cvc_set_auth_cookie( (string) $token, ! empty( $_POST['remember'] ) );
	if ( empty( $_POST['redirect_to'] ) && ! empty( $result['data']['user']['employer'] ) ) {
		$redirect = home_url( '/don-vi/' );
	}
	wp_safe_redirect( $redirect );
	exit;
}

add_action( 'admin_post_nopriv_cvc_register', 'cvc_handle_register' );
add_action( 'admin_post_cvc_register', 'cvc_handle_register' );

function cvc_handle_register(): void {
	check_admin_referer( 'cvc_register' );

	$name                  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email                 = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$password              = (string) ( $_POST['password'] ?? '' );
	$password_confirmation = (string) ( $_POST['password_confirmation'] ?? '' );
	$type                  = 'employer' === ( $_POST['account_type'] ?? '' ) ? 'employer' : 'learner';
	$agree                 = ! empty( $_POST['agree_terms'] );

	$body = array(
		'name'                  => $name,
		'email'                 => $email,
		'password'              => $password,
		'password_confirmation' => $password_confirmation,
		'account_type'          => $type,
		'terms'                 => $agree ? '1' : '0',
	);
	$old  = array( 'name' => $name, 'email' => $email, 'account_type' => $type, 'agree_terms' => $agree );

	// 28/10/2026: bắt buộc đồng ý Chính sách bảo mật + Điều khoản sử dụng.
	if ( ! $agree ) {
		cvc_flash_old_input( $old );
		cvc_redirect_with_notice( 'employer' === $type ? add_query_arg( 'loai', 'don-vi', cvc_register_url() ) : cvc_register_url(), 'error', 'Vui lòng đồng ý với Chính sách bảo mật và Điều khoản sử dụng để đăng ký.', null );
		return;
	}

	if ( 'employer' === $type ) {
		$raw = isset( $_POST['employer'] ) && is_array( $_POST['employer'] ) ? wp_unslash( $_POST['employer'] ) : array();
		$emp = array();
		foreach ( array( 'name', 'sector', 'province_id', 'phone', 'website', 'address', 'tax_code' ) as $k ) {
			$v = sanitize_text_field( (string) ( $raw[ $k ] ?? '' ) );
			if ( '' !== $v ) {
				$emp[ $k ] = 'province_id' === $k ? (int) $v : ( 'website' === $k ? esc_url_raw( $v ) : $v );
			}
		}
		$emp['sector']    = ( $emp['sector'] ?? '' ) === 'private' ? 'private' : 'public';
		$body['employer'] = $emp;
		$old['employer']  = $emp;
	}

	if ( function_exists( 'cvc_track_signup_fields' ) ) {
		$body += cvc_track_signup_fields(); // 28/10/2026: nguồn đến lần đầu.
	}
	$result = ( new CVC_Api_Client() )->post( '/api/auth/register', $body );

	if ( ! $result['ok'] ) {
		cvc_flash_old_input( $old );
		$back = 'employer' === $type ? add_query_arg( 'loai', 'don-vi', cvc_register_url() ) : cvc_register_url();
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $result ), null );
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

	if ( 'employer' === $type ) {
		delete_transient( 'cvc_employer_public_settings' );
		$sent = (string) ( $result['data']['verify_code_sent_to'] ?? '' );
		$msg  = '' !== $sent
			? 'Đã tạo tài khoản đơn vị. Mã xác minh 6 số vừa gửi tới ' . $sent . ' — nhập mã bên dưới để xác minh đơn vị ngay.'
			: 'Đã tạo tài khoản đơn vị. Bước cuối: xác minh đơn vị bằng email công vụ hoặc giấy giới thiệu có dấu.';
		cvc_redirect_with_notice( home_url( '/don-vi/xac-minh/' ), 'success', $msg, null );
		return;
	}

	wp_safe_redirect( home_url( '/tai-khoan/' ) );
	exit;
}

/**
 * Giữ lại dữ liệu form đăng ký (không gồm mật khẩu) qua 1 lần chuyển trang khi lỗi.
 * Lưu transient theo khóa ngẫu nhiên trong cookie ngắn hạn - không đặt dữ liệu cá nhân vào cookie.
 */
function cvc_flash_old_input( array $data ): void {
	$key = wp_generate_password( 20, false );
	set_transient( 'cvc_old_' . $key, $data, 5 * MINUTE_IN_SECONDS );
	setcookie( 'cvc_old', $key, array( 'expires' => time() + 5 * MINUTE_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
}

/** Đọc (1 lần) dữ liệu form đã lưu bởi cvc_flash_old_input(). */
function cvc_consume_old_input(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	$key   = isset( $_COOKIE['cvc_old'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) $_COOKIE['cvc_old'] ) : '';
	if ( '' !== $key ) {
		$val   = get_transient( 'cvc_old_' . $key );
		$cache = is_array( $val ) ? $val : array();
		delete_transient( 'cvc_old_' . $key );
		setcookie( 'cvc_old', '', array( 'expires' => time() - HOUR_IN_SECONDS, 'path' => '/' ) );
	}

	return $cache;
}

/*
 * Quên / đặt lại mật khẩu (Phase 12) - backend gửi email chứa link về
 * /dat-lai-mat-khau/?token=...&email=... Nếu backend báo chưa cấu hình mail
 * (503) thì hiện đúng thông báo đó, không giả vờ "đã gửi".
 */
add_action( 'admin_post_nopriv_cvc_forgot_password', 'cvc_handle_forgot_password' );
add_action( 'admin_post_cvc_forgot_password', 'cvc_handle_forgot_password' );

function cvc_handle_forgot_password(): void {
	check_admin_referer( 'cvc_forgot_password' );

	$email  = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$result = ( new CVC_Api_Client() )->post( '/api/auth/forgot-password', array( 'email' => $email ) );

	if ( ! $result['ok'] ) {
		$message = 503 === (int) $result['status'] && ! empty( $result['data']['message'] )
			? (string) $result['data']['message']
			: cvc_api_error_message( $result );
		cvc_redirect_with_notice( cvc_forgot_password_url(), 'error', $message, null );
		return;
	}

	cvc_redirect_with_notice(
		cvc_forgot_password_url(),
		'success',
		(string) ( $result['data']['message'] ?? 'Nếu email này đã đăng ký, bạn sẽ nhận được thư hướng dẫn đặt lại mật khẩu.' ),
		null
	);
}

add_action( 'admin_post_nopriv_cvc_reset_password', 'cvc_handle_reset_password' );
add_action( 'admin_post_cvc_reset_password', 'cvc_handle_reset_password' );

function cvc_handle_reset_password(): void {
	check_admin_referer( 'cvc_reset_password' );

	$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

	$result = ( new CVC_Api_Client() )->post(
		'/api/auth/reset-password',
		array(
			'token'                 => $token,
			'email'                 => $email,
			'password'              => (string) ( $_POST['password'] ?? '' ),
			'password_confirmation' => (string) ( $_POST['password_confirmation'] ?? '' ),
		)
	);

	if ( ! $result['ok'] ) {
		$back = add_query_arg(
			array(
				'token' => rawurlencode( $token ),
				'email' => rawurlencode( $email ),
			),
			cvc_reset_password_url()
		);
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $result ), null );
		return;
	}

	// Backend đã thu hồi mọi phiên cũ - xóa luôn cookie phía WordPress.
	cvc_clear_auth_cookie();
	cvc_redirect_with_notice( cvc_login_url(), 'success', (string) ( $result['data']['message'] ?? 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.' ), null );
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

function cvc_forgot_password_url(): string {
	return home_url( '/quen-mat-khau/' );
}

function cvc_reset_password_url(): string {
	return home_url( '/dat-lai-mat-khau/' );
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
