<?php
/**
 * Hồ sơ cá nhân (Phase 10) - GET /api/profile thật + 2 form (update
 * thông tin, đổi mật khẩu) POST qua admin-post.php (inc/actions.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result = ( new CVC_Profile_Service() )->show( $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$profile_user = $result['data']['user'] ?? $result['data'] ?? array();
$profile      = is_array( $profile_user['profile'] ?? null ) ? $profile_user['profile'] : array();
?>

<section class="cvc-account-section">
	<h2>Thông tin cơ bản</h2>
	<form class="cvc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_profile_update' ); ?>
		<input type="hidden" name="action" value="cvc_profile_update">

		<div class="cvc-form__field">
			<label for="cvc-profile-name">Họ và tên</label>
			<input type="text" id="cvc-profile-name" name="name" value="<?php echo esc_attr( $profile_user['name'] ?? '' ); ?>" required>
		</div>

		<div class="cvc-form__field">
			<label>Email</label>
			<input type="email" value="<?php echo esc_attr( $profile_user['email'] ?? '' ); ?>" disabled>
			<p class="cvc-form__hint">Email không thể thay đổi.</p>
		</div>

		<div class="cvc-form__field">
			<label for="cvc-profile-display-name">Tên hiển thị</label>
			<input type="text" id="cvc-profile-display-name" name="display_name" value="<?php echo esc_attr( $profile['display_name'] ?? '' ); ?>">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-profile-phone">Số điện thoại</label>
			<input type="tel" id="cvc-profile-phone" name="phone" value="<?php echo esc_attr( $profile['phone'] ?? '' ); ?>">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-profile-bio">Giới thiệu</label>
			<textarea id="cvc-profile-bio" name="bio" rows="4"><?php echo esc_textarea( $profile['bio'] ?? '' ); ?></textarea>
		</div>

		<button type="submit" class="cvc-btn cvc-btn--primary">Lưu thay đổi</button>
	</form>
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
			<label for="cvc-new-password">Mật khẩu mới</label>
			<input type="password" id="cvc-new-password" name="new_password" required minlength="8" autocomplete="new-password">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-new-password-confirm">Nhập lại mật khẩu mới</label>
			<input type="password" id="cvc-new-password-confirm" name="new_password_confirmation" required minlength="8" autocomplete="new-password">
		</div>

		<p class="cvc-form__hint">Sau khi đổi mật khẩu, bạn sẽ cần đăng nhập lại.</p>
		<button type="submit" class="cvc-btn cvc-btn--secondary">Đổi mật khẩu</button>
	</form>
</section>
