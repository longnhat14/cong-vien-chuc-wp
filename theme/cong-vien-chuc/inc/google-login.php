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

/** Khối nút Google + vạch "hoặc" (gọi trước form đăng nhập / đăng ký). */
function cvc_google_button( string $redirect_to = '', string $context = 'signin' ): void {
	$cid = cvc_google_client_id();
	if ( null === $cid ) {
		return;
	}
	?>
	<div class="cvc-google" data-cvc-google data-client-id="<?php echo esc_attr( $cid ); ?>" data-context="<?php echo esc_attr( 'signup' === $context ? 'signup' : 'signin' ); ?>">
		<div class="cvc-google__btn" aria-live="polite"><noscript>Bật JavaScript để dùng đăng nhập bằng Google.</noscript></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
			<?php wp_nonce_field( 'cvc_google' ); ?>
			<input type="hidden" name="action" value="cvc_google">
			<input type="hidden" name="credential" value="">
			<?php if ( '' !== $redirect_to ) : ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
			<?php endif; ?>
		</form>
		<p class="cvc-google__note"><?php echo 'signup' === $context ? 'Đăng ký bằng Google: không cần mật khẩu, không cần nhập mã xác minh email.' : 'Dùng tài khoản Google: không cần mật khẩu, email được xác minh ngay.'; ?></p>
		<div class="cvc-google__or"><span>hoặc dùng email</span></div>
	</div>
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
	cvc_set_auth_cookie( $token );
	if ( empty( $_POST['redirect_to'] ) && ! empty( $result['data']['user']['employer'] ) ) {
		$redirect = home_url( '/don-vi/' );
	}
	wp_safe_redirect( $redirect );
	exit;
}
