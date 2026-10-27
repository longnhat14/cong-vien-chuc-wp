<?php
/**
 * Đặt lại mật khẩu - /dat-lai-mat-khau/?token=...&email=... (giao diện 28/10/2026, inc/auth-ui.php).
 * Token chỉ được kiểm tra ở backend (POST /api/auth/reset-password).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reset_token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
$reset_email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
$has_link    = '' !== $reset_token && '' !== $reset_email;

cvc_seo_set_title( 'Đặt lại mật khẩu' );
cvc_seo_set_noindex();

get_header();

cvc_auth_start(
	array(
		'mode'   => 'login',
		'crumbs' => array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Đặt lại mật khẩu' ),
		),
		'title'  => 'Đặt lại mật khẩu',
		'sub'    => 'Tạo mật khẩu mới cho tài khoản',
		'desc'   => $has_link ? 'Mật khẩu mới cần ít nhất 8 ký tự, gồm cả chữ và số.' : '',
	)
);
?>
<div class="au-panel">
	<?php if ( ! $has_link ) : ?>
		<p class="au-desc" style="margin-top:0">Liên kết đặt lại mật khẩu không đầy đủ. Hãy mở đúng liên kết trong email, hoặc yêu cầu liên kết mới.</p>
		<p style="margin:16px 0 0"><a class="au-submit" style="text-decoration:none" href="<?php echo esc_url( cvc_forgot_password_url() ); ?>">Yêu cầu liên kết mới <?php echo cvc_auth_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></p>
	<?php else : ?>
		<form class="au-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cvc_reset_password' ); ?>
			<input type="hidden" name="action" value="cvc_reset_password">
			<input type="hidden" name="token" value="<?php echo esc_attr( $reset_token ); ?>">
			<input type="hidden" name="email" value="<?php echo esc_attr( $reset_email ); ?>">
			<?php
			cvc_auth_field(
				array(
					'id'    => 'cvc-reset-email-display',
					'type'  => 'email',
					'icon'  => 'mail',
					'label' => 'Email',
					'attrs' => array( 'value' => $reset_email, 'disabled' => true ),
				)
			);
			cvc_auth_field(
				array(
					'id'    => 'cvc-reset-password',
					'name'  => 'password',
					'type'  => 'password',
					'icon'  => 'lock',
					'label' => 'Mật khẩu mới',
					'attrs' => array( 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password', 'placeholder' => 'Ít nhất 8 ký tự, gồm cả chữ và số' ),
				)
			);
			cvc_auth_field(
				array(
					'id'    => 'cvc-reset-password-confirm',
					'name'  => 'password_confirmation',
					'type'  => 'password',
					'icon'  => 'lock',
					'label' => 'Nhập lại mật khẩu mới',
					'attrs' => array( 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password', 'placeholder' => 'Nhập lại mật khẩu mới' ),
				)
			);
			?>
			<button type="submit" class="au-submit"><?php echo cvc_auth_icon( 'key' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Đặt lại mật khẩu</button>
		</form>
	<?php endif; ?>
</div>
<?php
cvc_auth_end( true );
get_footer();
