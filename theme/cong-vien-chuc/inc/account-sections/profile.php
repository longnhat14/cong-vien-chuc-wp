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

// /api/profile trả { success, data: { user, profile, avatar, leaderboard_visibility } } - client trả nguyên JSON trong ['data']
// (11/11/2026: sửa lỗi đọc sai 1 cấp làm trang hồ sơ trống tên, email, ảnh).
$payload      = is_array( $result['data']['data'] ?? null ) ? $result['data']['data'] : (array) ( $result['data'] ?? array() );
$profile_user = is_array( $payload['user'] ?? null ) ? $payload['user'] : array();
$profile      = is_array( $payload['profile'] ?? null ) ? $payload['profile'] : array();
$avatar       = isset( $payload['avatar'] ) ? (string) $payload['avatar'] : '';
$lb_vis       = (string) ( $payload['leaderboard_visibility'] ?? 'full' );
?>

<?php
// 11/11/2026: ảnh minh họa có sẵn / ảnh Google / tải ảnh lên + đồng ý vinh danh.
$av_opt  = ( new CVC_Api_Client() )->get( '/api/profile/avatar/options', array(), $token );
$av      = $av_opt['ok'] ? (array) ( $av_opt['data']['data'] ?? array() ) : array();
$consent = ! empty( $av['fame_consent'] );
$cur_key = preg_match( '#/preset-(a\d{2})\.svg$#', $avatar, $mk ) ? $mk[1] : '';
$chk_msg = array( 'pending' => 'Ảnh đang được kiểm duyệt (vài phút) - trong lúc chờ, ảnh vẫn hiện trên bảng xếp hạng nhưng chưa hiện trên trang Vinh danh.', 'rejected' => 'Ảnh trước đã bị gỡ vì chưa phù hợp. Hãy chọn ảnh khác.', 'reported' => 'Ảnh đang được quản trị viên xem lại sau khi có người báo.' );
$consent_box = static function ( string $id ) use ( $consent ): void {
	echo '<label class="cvc-fame-consent" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="fame_consent" value="1"' . checked( $consent, true, false ) . '> Đồng ý hiện ảnh và tên của tôi trên <a href="' . esc_url( home_url( '/vinh-danh/' ) ) . '" target="_blank" rel="noopener">trang Vinh danh</a> khi tôi vào top 10</label>';
};
?>
<section class="cvc-account-section" id="anh-dai-dien">
	<h2>Ảnh đại diện</h2>
	<div class="cvc-avatar-row">
		<?php echo cvc_avatar_html( (string) ( $profile_user['name'] ?? '' ), '' !== $avatar ? $avatar : null, 96, 'is-lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="cvc-avatar-forms">
			<p class="cvc-form__hint">Ảnh hiện ở đầu trang, trên bảng xếp hạng và trên <b>Bảng vinh danh</b> khi bạn vào top 10. Chọn một trong 3 cách:</p>
			<?php if ( isset( $chk_msg[ (string) ( $av['check'] ?? '' ) ] ) ) : ?><p class="cvc-avatar-check"><?php echo esc_html( $chk_msg[ (string) $av['check'] ] ); ?></p><?php endif; ?>
			<?php if ( '' !== $avatar ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_avatar' ); ?>
				<input type="hidden" name="action" value="cvc_avatar"><input type="hidden" name="op" value="remove">
				<button type="submit" class="cvc-linkbtn">Xóa ảnh, dùng chữ cái đầu</button>
			</form>
			<?php endif; ?>
		</div>
	</div>

	<div class="cvc-avatar-ways">
		<details class="cvc-avatar-way" <?php echo '' === $avatar || '' !== $cur_key ? 'open' : ''; ?>>
			<summary><b>1. Ảnh minh họa có sẵn</b> <small>không cần ảnh thật</small></summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_avatar' ); ?>
				<input type="hidden" name="action" value="cvc_avatar"><input type="hidden" name="op" value="preset">
				<fieldset class="cvc-preset-grid"><legend class="sr-only">Chọn ảnh minh họa</legend>
					<?php foreach ( (array) ( $av['presets'] ?? array() ) as $i => $pr ) : ?>
						<label class="cvc-preset" for="pv-<?php echo esc_attr( (string) $pr['key'] ); ?>">
							<input type="radio" id="pv-<?php echo esc_attr( (string) $pr['key'] ); ?>" name="key" value="<?php echo esc_attr( (string) $pr['key'] ); ?>" <?php checked( $cur_key ? $cur_key : '', (string) $pr['key'] ); ?> required>
							<img src="<?php echo esc_url( (string) $pr['url'] ); ?>" width="64" height="64" alt="<?php echo esc_attr( 'Ảnh minh họa ' . ( $i + 1 ) ); ?>" loading="lazy">
						</label>
					<?php endforeach; ?>
				</fieldset>
				<?php $consent_box( 'fc-preset' ); ?>
				<button type="submit" class="cvc-btn cvc-btn--primary">Dùng ảnh minh họa này</button>
			</form>
		</details>
		<?php if ( ! empty( $av['google'] ) ) : ?>
		<details class="cvc-avatar-way" <?php echo 'google' === ( $av['source'] ?? '' ) ? 'open' : ''; ?>>
			<summary><b>2. Dùng ảnh tài khoản Google</b> <small>1 chạm</small></summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_avatar' ); ?>
				<input type="hidden" name="action" value="cvc_avatar"><input type="hidden" name="op" value="google">
				<p class="cvc-form__hint">Lấy ảnh đại diện Google của tài khoản bạn vừa đăng nhập.</p>
				<?php $consent_box( 'fc-google' ); ?>
				<button type="submit" class="cvc-btn cvc-btn--primary">Dùng ảnh Google</button>
			</form>
		</details>
		<?php endif; ?>
		<details class="cvc-avatar-way" <?php echo 'upload' === ( $av['source'] ?? '' ) ? 'open' : ''; ?>>
			<summary><b><?php echo ! empty( $av['google'] ) ? '3' : '2'; ?>. Tải ảnh lên</b> <small>cắt vuông</small></summary>
			<form class="cvc-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_avatar' ); ?>
				<input type="hidden" name="action" value="cvc_avatar"><input type="hidden" name="op" value="upload">
				<?php cvc_render_image_picker( 'file', 'cover' ); ?>
				<?php $consent_box( 'fc-upload' ); ?>
				<button type="submit" class="cvc-btn cvc-btn--primary">Lưu ảnh đại diện</button>
				<p class="cvc-form__hint">Ảnh tải lên được kiểm duyệt tự động; ảnh có nội dung không phù hợp, quảng cáo, số điện thoại, mã QR sẽ bị gỡ.</p>
			</form>
		</details>
	</div>
</section>

<section class="cvc-account-section" id="hien-thi">
	<h2>Bảng xếp hạng &amp; vinh danh</h2>
	<form class="cvc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_profile_update' ); ?>
		<input type="hidden" name="action" value="cvc_profile_update"><input type="hidden" name="fame_form" value="1">
		<fieldset class="cvc-form__field"><legend>Cách hiện tên trên bảng xếp hạng</legend>
			<?php foreach ( array( 'full' => 'Tên hiển thị, ảnh đại diện và tỉnh', 'initials' => 'Chỉ chữ cái đầu (ví dụ N.V.A.), không ảnh', 'hidden' => 'Ẩn tôi ("Thí sinh ẩn danh"; tôi vẫn xem được hạng của mình)' ) as $k => $l ) : ?>
				<label class="cvc-radio" for="lbv-<?php echo esc_attr( $k ); ?>"><input type="radio" id="lbv-<?php echo esc_attr( $k ); ?>" name="leaderboard_visibility" value="<?php echo esc_attr( $k ); ?>" <?php checked( $lb_vis, $k ); ?>> <?php echo esc_html( $l ); ?></label>
			<?php endforeach; ?>
		</fieldset>
		<?php $provs = function_exists( 'cvc_employer_public_settings' ) ? (array) ( cvc_employer_public_settings()['provinces'] ?? array() ) : array(); ?>
		<?php if ( $provs ) : ?>
		<div class="cvc-form__field">
			<label for="cvc-profile-province">Tỉnh / thành (hiện cạnh tên, dùng cho bảng theo tỉnh)</label>
			<select id="cvc-profile-province" name="province_id">
				<option value="">Chưa chọn</option>
				<?php foreach ( $provs as $pv ) : ?><option value="<?php echo (int) $pv['id']; ?>" <?php selected( (int) ( $profile['province_id'] ?? 0 ), (int) $pv['id'] ); ?>><?php echo esc_html( (string) $pv['name'] ); ?></option><?php endforeach; ?>
			</select>
		</div>
		<?php endif; ?>
		<?php $consent_box( 'fc-main' ); ?>
		<p class="cvc-form__hint">Ảnh minh họa có sẵn luôn được hiện. Ảnh thật (tải lên, ảnh Google) chỉ hiện trên trang Vinh danh khi bạn đánh dấu ô trên. Có thể bỏ đồng ý bất cứ lúc nào.</p>
		<button type="submit" class="cvc-btn cvc-btn--primary">Lưu</button>
	</form>
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


		<button type="submit" class="cvc-btn cvc-btn--primary">Lưu thay đổi</button>
	</form>
</section>

<section class="cvc-account-section">
	<h2>Mật khẩu & đăng nhập</h2>
	<p>Đổi mật khẩu và xem phiên đăng nhập tại trang <a href="<?php echo esc_url( cvc_account_url( 'security' ) ); ?>">Bảo mật &amp; đăng nhập</a>.</p>
</section>
