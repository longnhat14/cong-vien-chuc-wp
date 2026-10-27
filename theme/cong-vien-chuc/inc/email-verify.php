<?php
/**
 * Xác minh email người dùng (27/10/2026).
 *  - Dải nhắc dưới đầu trang cho tài khoản chưa xác minh (trừ trang /xac-minh-email/).
 *  - Trang /xac-minh-email/ (template-verify-email.php): nhập mã 6 số, gửi lại mã, sửa email; kết quả từ liên kết trong email.
 *  - Chưa xác minh: không tải đề PDF, không làm bài thi / ôn tập, không dùng ngân hàng câu hỏi, không nhận bản tin tuần (backend kiểm tra).
 * API: GET /api/auth/email/status, POST /api/auth/email/verification/send, POST /api/auth/email/verify, PUT /api/auth/email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_verify_email_url( string $return = '' ): string {
	$url = home_url( '/xac-minh-email/' );

	return '' !== $return && str_starts_with( $return, '/' ) && ! str_starts_with( $return, '//' ) ? add_query_arg( 'quay-lai', rawurlencode( $return ), $url ) : $url;
}

/** Tài khoản đang đăng nhập chưa xác minh email (null = không rõ / chưa đăng nhập). */
function cvc_email_unverified(): bool {
	$u = function_exists( 'cvc_current_user' ) ? cvc_current_user() : null;

	return is_array( $u ) && array_key_exists( 'email_verified', $u ) && false === $u['email_verified'] && empty( $u['is_staff'] );
}

/** API trả 403 verify_required (chưa xác minh email -> chưa được làm bài / dùng ngân hàng câu hỏi). */
function cvc_api_verify_required( $res ): bool {
	return is_array( $res ) && 403 === (int) ( $res['status'] ?? 0 ) && ! empty( $res['data']['verify_required'] );
}

/** Đường dẫn tương đối (path + query) để quay lại sau khi xác minh. */
function cvc_verify_return_path( string $url = '' ): string {
	$url  = '' !== $url ? $url : (string) wp_get_referer();
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	$qs   = (string) wp_parse_url( $url, PHP_URL_QUERY );

	return '' !== $path ? $path . ( '' !== $qs ? '?' . $qs : '' ) : '/';
}

/** Form/trang: chuyển tới /xac-minh-email/ khi API yêu cầu xác minh. */
function cvc_verify_redirect_if_required( $res, string $return = '' ): void {
	if ( cvc_api_verify_required( $res ) ) {
		wp_safe_redirect( cvc_verify_email_url( cvc_verify_return_path( $return ) ) );
		exit;
	}
}

/** AJAX: trả lỗi kèm đường dẫn xác minh khi API yêu cầu xác minh. */
function cvc_verify_ajax_fail_if_required( $res, string $return = '' ): void {
	if ( cvc_api_verify_required( $res ) ) {
		wp_send_json_error(
			array(
				'message'  => 'Xác minh email để làm bài và dùng ngân hàng câu hỏi — đang chuyển tới trang nhập mã…',
				'verify'   => true,
				'redirect' => cvc_verify_email_url( cvc_verify_return_path( $return ) ),
			),
			403
		);
	}
}

/** Dải nhắc xác minh (gọi ngay sau header chung). */
function cvc_email_verify_banner(): void {
	if ( ! cvc_email_unverified() || 'verify-email' === (string) get_query_var( 'cvc_page' ) ) {
		return;
	}
	$u    = cvc_current_user();
	$here = (string) wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH );
	?>
	<div class="cvc-verify-bar" role="status" style="background:#fff7e6;border-bottom:1px solid #f1d9a6;font-family:'Times New Roman',Times,serif">
		<div style="max-width:1160px;margin:0 auto;padding:9px 16px;display:flex;flex-wrap:wrap;gap:6px 14px;align-items:center;justify-content:space-between;font-size:15.5px;color:#5a4410">
			<span>✉ Xác nhận email <b style="color:#0f2a4a"><?php echo esc_html( (string) ( $u['email'] ?? '' ) ); ?></b> để làm bài thi, luyện ngân hàng câu hỏi, tải đề PDF, nhận tin tuyển dụng theo tỉnh/ngành và khôi phục mật khẩu khi cần.</span>
			<a href="<?php echo esc_url( cvc_verify_email_url( $here ) ); ?>" style="padding:6px 14px;border-radius:999px;background:#0f2a4a;color:#fff;font-weight:bold;text-decoration:none;white-space:nowrap">Nhập mã xác minh ›</a>
		</div>
	</div>
	<?php
}

/* ------------------------------------------------------------------
 * Xử lý form
 * ------------------------------------------------------------------ */

add_action( 'admin_post_cvc_email_verify', 'cvc_handle_email_verify' );
add_action( 'admin_post_nopriv_cvc_email_verify', 'cvc_handle_email_verify' );

function cvc_handle_email_verify(): void {
	check_admin_referer( 'cvc_email_verify' );
	$token  = cvc_require_token_or_die();
	$op     = sanitize_key( wp_unslash( $_POST['op'] ?? 'verify' ) );
	$return = sanitize_text_field( wp_unslash( $_POST['quay_lai'] ?? '' ) );
	$back   = cvc_verify_email_url( $return );
	$api    = new CVC_Api_Client( null, 20 );

	if ( 'resend' === $op ) {
		$r = $api->post( '/api/auth/email/verification/send', array(), $token );
	} elseif ( 'change' === $op ) {
		$r = $api->put(
			'/api/auth/email',
			array(
				'email'    => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
				'password' => (string) wp_unslash( $_POST['password'] ?? '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			),
			$token
		);
	} else {
		$r = $api->post( '/api/auth/email/verify', array( 'code' => preg_replace( '/\D/', '', (string) wp_unslash( $_POST['code'] ?? '' ) ) ), $token );
	}
	$msg = (string) ( $r['data']['message'] ?? '' );
	if ( empty( $r['ok'] ) ) {
		cvc_redirect_with_notice( $back, 'error', '' !== $msg ? $msg : cvc_api_error_message( $r ), null );
	}
	if ( 'verify' === $op ) {
		$dest = '' !== $return && str_starts_with( $return, '/' ) && ! str_starts_with( $return, '//' ) ? home_url( $return ) : add_query_arg( 'ket-qua', 'thanh-cong', home_url( '/xac-minh-email/' ) );
		cvc_redirect_with_notice( $dest, 'success', 'Đã xác minh email. Bạn có thể tải đề PDF và nhận tin tuyển dụng qua email.', null );
	}
	cvc_redirect_with_notice( $back, 'success', '' !== $msg ? $msg : 'Đã gửi mã mới.', null );
}
