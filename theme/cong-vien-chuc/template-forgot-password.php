<?php
/**
 * Quên mật khẩu - /quen-mat-khau/ (Phase 12)
 *
 * Gửi email đặt lại mật khẩu qua POST /api/auth/forgot-password. Backend
 * luôn trả cùng 1 thông báo dù email có tồn tại hay không.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( cvc_is_logged_in() ) {
	wp_safe_redirect( cvc_account_url( 'security' ) );
	exit;
}

cvc_seo_set_title( 'Quên mật khẩu' );
cvc_seo_set_noindex();

get_header();
?>

<main id="main" class="container cvc-page cvc-auth-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array(
				'label' => 'Đăng nhập',
				'url'   => cvc_login_url(),
			),
			array( 'label' => 'Quên mật khẩu' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Quên mật khẩu</h1>
		<p>Nhập email bạn đã dùng để đăng ký. Chúng tôi sẽ gửi liên kết đặt lại mật khẩu (hiệu lực 60 phút).</p>
	</header>

	<?php cvc_render_notice(); ?>

	<form class="cvc-form cvc-form--auth" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_forgot_password' ); ?>
		<input type="hidden" name="action" value="cvc_forgot_password">

		<div class="cvc-form__field">
			<label for="cvc-forgot-email">Email</label>
			<input type="email" id="cvc-forgot-email" name="email" required autocomplete="email">
		</div>

		<button type="submit" class="cvc-btn cvc-btn--primary">Gửi liên kết đặt lại</button>
	</form>

	<p class="cvc-auth-switch">
		Nhớ ra mật khẩu?
		<a href="<?php echo esc_url( cvc_login_url() ); ?>">Đăng nhập</a>
	</p>
</main>

<?php get_footer(); ?>
