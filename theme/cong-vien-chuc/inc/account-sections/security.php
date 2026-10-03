<?php
/**
 * Bảo mật & đăng nhập (Phase 11) - đổi mật khẩu (POST /api/profile/
 * change-password, backend thu hồi mọi token sau khi đổi) + thông tin
 * phiên hiện tại (GET /api/auth/session). Backend chỉ giữ 1 phiên đăng
 * nhập cho mỗi tài khoản (đăng nhập nơi khác -> phiên cũ hết hiệu lực),
 * nên không có danh sách thiết bị để quản lý - trang nói đúng như vậy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$session_result = ( new CVC_Profile_Service() )->session( $token );
$session        = $session_result['ok'] ? ( $session_result['data']['data'] ?? array() ) : array();

$fmt = static function ( ?string $iso ): string {
	if ( empty( $iso ) ) {
		return '—';
	}
	$ts = strtotime( $iso );
	return false === $ts ? '—' : wp_date( 'H:i d/m/Y', $ts );
};
?>

<section class="cvc-account-section">
	<h2>Phiên đăng nhập hiện tại</h2>
	<?php if ( ! $session_result['ok'] ) : ?>
		<?php cvc_render_error_state( cvc_api_error_message( $session_result ) ); ?>
	<?php else : ?>
		<dl class="cvc-security-list">
			<div><dt>Đăng nhập lúc</dt><dd><?php echo esc_html( $fmt( $session['created_at'] ?? null ) ); ?></dd></div>
			<div><dt>Hoạt động gần nhất</dt><dd><?php echo esc_html( $fmt( $session['last_used_at'] ?? null ) ); ?></dd></div>
			<div><dt>Hết hạn</dt><dd><?php echo empty( $session['expires_at'] ) ? 'Không tự hết hạn - đến khi bạn đăng xuất' : esc_html( $fmt( $session['expires_at'] ) ); ?></dd></div>
		</dl>
		<p class="cvc-form__hint">Mỗi tài khoản chỉ có 1 phiên đăng nhập: khi bạn đăng nhập ở thiết bị khác, phiên ở thiết bị cũ tự kết thúc. Nếu nghi ngờ ai đó dùng tài khoản của bạn, hãy đổi mật khẩu ngay - mọi phiên sẽ bị đăng xuất.</p>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_logout_url() ); ?>">Đăng xuất khỏi thiết bị này</a></p>
	<?php endif; ?>
</section>

<section class="cvc-account-section">
	<h2>Đổi mật khẩu</h2>
	<form class="cvc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_profile_change_password' ); ?>
		<input type="hidden" name="action" value="cvc_profile_change_password">

		<div class="cvc-form__field">
			<label for="cvc-current-password">Mật khẩu hiện tại</label>
			<input type="password" id="cvc-current-password" name="current_password" required autocomplete="current-password">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-new-password">Mật khẩu mới (tối thiểu 8 ký tự)</label>
			<input type="password" id="cvc-new-password" name="new_password" required minlength="8" autocomplete="new-password">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-new-password-confirm">Nhập lại mật khẩu mới</label>
			<input type="password" id="cvc-new-password-confirm" name="new_password_confirmation" required minlength="8" autocomplete="new-password">
		</div>

		<p class="cvc-form__hint">Sau khi đổi mật khẩu, mọi phiên đăng nhập bị thu hồi và bạn cần đăng nhập lại.</p>
		<button type="submit" class="cvc-btn cvc-btn--primary">Đổi mật khẩu</button>
	</form>
</section>
