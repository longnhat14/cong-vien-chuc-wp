<?php
/**
 * Ảnh đại diện + logo đơn vị (21/10/2026).
 *  - cvc_avatar_html(): ảnh tròn hoặc chữ cái đầu trên nền màu cố định theo tên.
 *  - cvc_logo_html(): logo vuông nền trắng (đơn vị).
 *  - admin-post cvc_avatar: op=upload|remove → POST/DELETE /api/profile/avatar.
 *  - cvc_render_image_picker(): ô chọn ảnh + khung cắt vuông (assets/js/cvc-avatar-crop.js).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_avatar_initials( string $name ): string {
	$parts = preg_split( '/\s+/u', trim( $name ) ) ?: array();
	$parts = array_values( array_filter( $parts, static fn ( $p ) => '' !== $p ) );
	if ( ! $parts ) {
		return '?';
	}
	$last  = mb_strtoupper( mb_substr( (string) end( $parts ), 0, 1 ) );
	$first = count( $parts ) > 1 ? mb_strtoupper( mb_substr( (string) $parts[0], 0, 1 ) ) : '';

	return $first . $last;
}

function cvc_avatar_html( string $name, ?string $url, int $size = 40, string $class = '' ): string {
	$style = 'width:' . $size . 'px;height:' . $size . 'px;';
	if ( $url && preg_match( '#^https://#i', $url ) ) {
		return '<img class="cvc-avatar ' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="" width="' . $size . '" height="' . $size . '" loading="lazy" decoding="async" style="' . esc_attr( $style ) . '">';
	}
	$palette = array( '#123a63', '#1767aa', '#0f6b5c', '#7a4b12', '#6b2f6b', '#8a2f2f', '#3d5a1e', '#2f4f6f' );
	$bg      = $palette[ abs( crc32( $name ) ) % count( $palette ) ];

	return '<span class="cvc-avatar is-initials ' . esc_attr( $class ) . '" aria-hidden="true" style="' . esc_attr( $style . 'background:' . $bg . ';font-size:' . max( 10, (int) round( $size * 0.4 ) ) . 'px' ) . '">' . esc_html( cvc_avatar_initials( $name ) ) . '</span>';
}

function cvc_logo_html( ?string $url, string $name, int $size = 48, string $class = '' ): string {
	if ( ! $url || ! preg_match( '#^https://#i', $url ) ) {
		return '';
	}

	return '<img class="cvc-logo ' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="Logo ' . esc_attr( $name ) . '" width="' . $size . '" height="' . $size . '" loading="lazy" decoding="async">';
}

/** Ảnh đại diện của người đang đăng nhập (từ /api/auth/me). */
function cvc_current_avatar_html( int $size = 28, string $class = '' ): string {
	$u = function_exists( 'cvc_current_user' ) ? cvc_current_user() : null;
	if ( ! is_array( $u ) ) {
		return '';
	}

	return cvc_avatar_html( (string) ( $u['name'] ?? '' ), isset( $u['avatar'] ) ? (string) $u['avatar'] : null, $size, $class );
}

/**
 * Ô chọn ảnh có khung cắt. $mode = cover (ảnh đại diện, cắt vuông) | contain (logo, chỉ xem trước).
 * Không có JavaScript: tải nguyên ảnh, máy chủ tự cắt giữa.
 */
function cvc_render_image_picker( string $field, string $mode = 'cover' ): void {
	static $loaded = false;
	?>
	<div class="cvc-crop" data-cvc-crop="<?php echo esc_attr( $mode ); ?>">
		<input type="file" name="<?php echo esc_attr( $field ); ?>" accept="image/jpeg,image/png,image/webp" required data-crop-input>
		<div class="cvc-crop-stage" hidden>
			<canvas width="240" height="240" aria-label="Kéo để chọn vùng ảnh"></canvas>
			<?php if ( 'cover' === $mode ) : ?>
				<label class="cvc-crop-zoom">Phóng to <input type="range" min="1" max="4" step="0.01" value="1" data-crop-zoom></label>
				<p class="cvc-form__hint">Kéo ảnh để chọn vùng hiển thị.</p>
			<?php endif; ?>
		</div>
		<p class="cvc-form__hint">JPG, PNG hoặc WebP, tối đa 2 MB.</p>
	</div>
	<?php
	if ( ! $loaded ) {
		$loaded = true;
		$file   = get_theme_file_path( '/assets/js/cvc-avatar-crop.js' );
		echo '<script src="' . esc_url( get_template_directory_uri() . '/assets/js/cvc-avatar-crop.js?ver=' . (string) ( file_exists( $file ) ? filemtime( $file ) : '1' ) ) . '" defer></script>';
	}
}

add_action( 'admin_post_cvc_avatar', 'cvc_handle_avatar' );
add_action( 'admin_post_nopriv_cvc_avatar', 'cvc_handle_avatar' );

function cvc_handle_avatar(): void {
	check_admin_referer( 'cvc_avatar' );
	$token = cvc_require_token_or_die();
	$back  = cvc_account_url( 'profile' );
	$api   = new CVC_Api_Client();

	if ( 'remove' === ( $_POST['op'] ?? '' ) ) {
		$res = $api->delete( '/api/profile/avatar', $token );
	} else {
		$files = function_exists( 'cvc_employer_files_from_request' ) ? cvc_employer_files_from_request( 'file' ) : array();
		if ( empty( $files ) ) {
			cvc_redirect_with_notice( $back, 'error', 'Hãy chọn ảnh (JPG, PNG hoặc WebP, tối đa 2 MB).', null );
			return;
		}
		$res = $api->post_multipart( '/api/profile/avatar', array(), $files, $token );
	}
	if ( function_exists( 'cvc_auth_refresh_user_cache' ) ) {
		cvc_auth_refresh_user_cache();
	}
	if ( ! $res['ok'] ) {
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $res ), null );
		return;
	}
	cvc_redirect_with_notice( $back, 'success', (string) ( $res['data']['message'] ?? 'Đã cập nhật ảnh đại diện.' ), null );
}
