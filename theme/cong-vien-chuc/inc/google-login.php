<?php
/**
 * Đăng nhập / đăng ký bằng Google (28/10/2026) - Google Identity Services.
 *  - Client ID do quản trị nhập ở Quản trị → Thu thập → Email (lưu ở backend, setting auth.google_client_id).
 *  - Nút Google trên /dang-nhap/ và /dang-ky/; Google trả ID token → form admin-post cvc_google (có nonce)
 *    → POST /api/auth/google → đặt cookie đăng nhập như đăng nhập thường.
 *  - Email Google đã được Google xác minh → tài khoản không phải nhập mã xác minh.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Client ID (cache 10 phút); null = chưa bật. */
function cvc_google_client_id(): ?string {
	$cached = get_transient( 'cvc_google_cfg' );
	if ( false === $cached ) {
		$res    = ( new CVC_Api_Client( null, 5 ) )->get( '/api/auth/google/config' );
		$cached = ! empty( $res['ok'] ) ? (string) ( $res['data']['data']['client_id'] ?? '' ) : '';
		set_transient( 'cvc_google_cfg', $cached, ! empty( $res['ok'] ) ? 10 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS );
	}

	return '' !== $cached ? $cached : null;
}

/**
 * Khối nút Google + vạch "hoặc" (gọi trước form đăng nhập / đăng ký).
 * 28/10/2026: lớp hiển thị theo font trang (.au-gface); nút chính thức của Google (iframe) phủ trong suốt lên trên
 * để nhận cú bấm - Google vẫn xử lý toàn bộ việc đăng nhập.
 */
function cvc_google_button( string $redirect_to = '', string $context = 'signin' ): void {
	$cid = cvc_google_client_id();
	if ( null === $cid ) {
		return;
	}
	$signup = 'signup' === $context;
	$label  = $signup ? 'Đăng ký bằng Google' : 'Tiếp tục với Google';
	?>
	<div class="au-google" data-cvc-google data-client-id="<?php echo esc_attr( $cid ); ?>" data-context="<?php echo esc_attr( $signup ? 'signup' : 'signin' ); ?>">
		<div class="au-gwrap">
			<span class="au-gface" aria-hidden="true"><?php echo function_exists( 'cvc_auth_google_mark' ) ? cvc_auth_google_mark() : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="au-gface__t"><?php echo esc_html( $label ); ?></span><?php echo function_exists( 'cvc_auth_icon' ) ? cvc_auth_icon( 'arrow' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<div class="au-gbtn cvc-google__btn"></div>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
			<?php wp_nonce_field( 'cvc_google' ); ?>
			<input type="hidden" name="action" value="cvc_google">
			<input type="hidden" name="credential" value="">
			<?php if ( '' !== $redirect_to ) : ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
			<?php endif; ?>
		</form>
		<p class="au-gnote"><?php echo $signup ? 'Đăng ký bằng Google: không cần mật khẩu, không cần nhập mã xác minh, email được xác minh ngay. Tiếp tục nghĩa là bạn đồng ý với Điều khoản sử dụng và Chính sách bảo mật.' : 'Dùng tài khoản Google: không cần mật khẩu, email được xác minh ngay.'; ?></p>
		<noscript><p class="au-gnote">Bật JavaScript để dùng đăng nhập bằng Google.</p></noscript>
	</div>
	<div class="au-or"><span><?php echo $signup ? 'hoặc đăng ký với email' : 'hoặc đăng nhập bằng email'; ?></span></div>
	<?php
	wp_enqueue_script( 'cvc-google-login', get_theme_file_uri( '/assets/js/google-login.js' ), array(), (string) filemtime( get_theme_file_path( '/assets/js/google-login.js' ) ), true );
}

add_action( 'admin_post_nopriv_cvc_google', 'cvc_handle_google' );
add_action( 'admin_post_cvc_google', 'cvc_handle_google' );

function cvc_handle_google(): void {
	check_admin_referer( 'cvc_google' );
	$redirect   = cvc_safe_redirect_target( isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : null );
	$credential = preg_replace( '/[^A-Za-z0-9._-]/', '', (string) wp_unslash( $_POST['credential'] ?? '' ) );
	if ( '' === $credential ) {
		cvc_redirect_with_notice( cvc_login_url(), 'error', 'Chưa nhận được phản hồi từ Google, vui lòng thử lại.', $redirect );
		return;
	}
	$result = ( new CVC_Api_Client( null, 20 ) )->post( '/api/auth/google', array( 'credential' => $credential ) );
	$token  = (string) ( $result['data']['token'] ?? '' );
	if ( empty( $result['ok'] ) || '' === $token ) {
		$msg = (string) ( $result['data']['message'] ?? '' );
		cvc_redirect_with_notice( cvc_login_url(), 'error', '' !== $msg ? $msg : 'Đăng nhập bằng Google chưa thành công, vui lòng thử lại.', $redirect );
		return;
	}
	cvc_set_auth_cookie( $token, true );
	if ( empty( $_POST['redirect_to'] ) && ! empty( $result['data']['user']['employer'] ) ) {
		$redirect = home_url( '/don-vi/' );
	}
	wp_safe_redirect( $redirect );
	exit;
}
