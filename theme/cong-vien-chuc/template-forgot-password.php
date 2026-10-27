<?php
/**
 * Quên mật khẩu - /quen-mat-khau/ (giao diện 28/10/2026, inc/auth-ui.php).
 * Form POST admin-post.php?action=cvc_forgot_password (inc/auth.php). Trang noindex.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( cvc_is_logged_in() ) {
	wp_safe_redirect( cvc_account_url() );
	exit;
}

cvc_seo_set_title( 'Quên mật khẩu' );
cvc_seo_set_noindex();

get_header();

cvc_auth_start(
	array(
		'mode'   => 'login',
		'crumbs' => array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Đăng nhập', 'url' => cvc_login_url() ),
			array( 'label' => 'Quên mật khẩu' ),
		),
		'title'  => 'Quên mật khẩu',
		'sub'    => 'Lấy lại quyền truy cập tài khoản',
		'desc'   => 'Nhập email bạn đã dùng để đăng ký. Chúng tôi sẽ gửi liên kết đặt lại mật khẩu (hiệu lực 60 phút). Nếu bạn đăng ký bằng Google, hãy dùng nút "Tiếp tục với Google" ở trang đăng nhập.',
	)
);
?>
<div class="au-panel">
	<form class="au-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_forgot_password' ); ?>
		<input type="hidden" name="action" value="cvc_forgot_password">
		<?php
		cvc_auth_field(
			array(
				'id'    => 'cvc-forgot-email',
				'name'  => 'email',
				'type'  => 'email',
				'icon'  => 'mail',
				'label' => 'Email',
				'attrs' => array( 'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email', 'placeholder' => 'Nhập email của bạn' ),
			)
		);
		?>
		<button type="submit" class="au-submit"><?php echo cvc_auth_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Gửi liên kết đặt lại</button>
	</form>
	<p class="au-switch">Nhớ ra mật khẩu? <a href="<?php echo esc_url( cvc_login_url() ); ?>">Đăng nhập</a></p>
</div>
<?php
cvc_auth_end( true );
get_footer();
