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
// /api/profile trả { data: { user, profile, avatar, leaderboard_visibility } } (profile cùng cấp với user).
$profile      = is_array( $result['data']['profile'] ?? null ) ? $result['data']['profile'] : ( is_array( $profile_user['profile'] ?? null ) ? $profile_user['profile'] : array() );
$avatar       = isset( $result['data']['avatar'] ) ? (string) $result['data']['avatar'] : '';
$lb_vis       = (string) ( $result['data']['leaderboard_visibility'] ?? 'full' );
?>

<section class="cvc-account-section">
	<h2>Ảnh đại diện</h2>
	<div class="cvc-avatar-row">
		<?php echo cvc_avatar_html( (string) ( $profile_user['name'] ?? '' ), '' !== $avatar ? $avatar : null, 96, 'is-lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="cvc-avatar-forms">
			<form class="cvc-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_avatar' ); ?>
				<input type="hidden" name="action" value="cvc_avatar"><input type="hidden" name="op" value="upload">
				<?php cvc_render_image_picker( 'file', 'cover' ); ?>
				<button type="submit" class="cvc-btn cvc-btn--primary">Lưu ảnh đại diện</button>
			</form>
			<?php if ( '' !== $avatar ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_avatar' ); ?>
				<input type="hidden" name="action" value="cvc_avatar"><input type="hidden" name="op" value="remove">
				<button type="submit" class="cvc-linkbtn">Xóa ảnh, dùng chữ cái đầu</button>
			</form>
			<?php endif; ?>
		</div>
	</div>
	<p class="cvc-form__hint">Ảnh hiển thị ở đầu trang và trên bảng xếp hạng thi thử.</p>
</section>

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

		<div class="cvc-form__field">
			<label for="cvc-profile-lb">Bảng xếp hạng thi thử</label>
			<select id="cvc-profile-lb" name="leaderboard_visibility">
				<option value="full" <?php selected( 'full', $lb_vis ); ?>>Hiện họ tên đầy đủ, ảnh đại diện và tỉnh</option>
				<option value="hidden" <?php selected( 'hidden', $lb_vis ); ?>>Ẩn tôi (hiện "Thí sinh ẩn danh"; tôi vẫn xem được hạng của mình)</option>
			</select>
		</div>

		<button type="submit" class="cvc-btn cvc-btn--primary">Lưu thay đổi</button>
	</form>
</section>

<section class="cvc-account-section">
	<h2>Mật khẩu & đăng nhập</h2>
	<p>Đổi mật khẩu và xem phiên đăng nhập tại trang <a href="<?php echo esc_url( cvc_account_url( 'security' ) ); ?>">Bảo mật &amp; đăng nhập</a>.</p>
</section>
