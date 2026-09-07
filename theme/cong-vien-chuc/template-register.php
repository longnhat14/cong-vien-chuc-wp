<?php
/**
 * Đăng ký - /dang-ky/
 *
 * Form POST tới admin-post.php?action=cvc_register (inc/auth.php::
 * cvc_handle_register()). Validation thật (email trùng, độ mạnh mật
 * khẩu...) hoàn toàn ở Laravel (AuthController::register) - form ở đây
 * chỉ ràng buộc HTML tối thiểu (required/minlength) để trải nghiệm tốt
 * hơn, KHÔNG thay thế validation backend.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( cvc_is_logged_in() ) {
	wp_safe_redirect( cvc_account_url() );
	exit;
}

cvc_seo_set_title( 'Đăng ký' );
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
			array( 'label' => 'Đăng ký' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Đăng ký tài khoản</h1>
	</header>

	<?php cvc_render_notice(); ?>

	<form class="cvc-form cvc-form--auth" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_register' ); ?>
		<input type="hidden" name="action" value="cvc_register">

		<div class="cvc-form__field">
			<label for="cvc-register-name">Họ và tên</label>
			<input type="text" id="cvc-register-name" name="name" required autocomplete="name">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-register-email">Email</label>
			<input type="email" id="cvc-register-email" name="email" required autocomplete="email">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-register-password">Mật khẩu</label>
			<input type="password" id="cvc-register-password" name="password" required minlength="8" autocomplete="new-password">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-register-password-confirm">Nhập lại mật khẩu</label>
			<input type="password" id="cvc-register-password-confirm" name="password_confirmation" required minlength="8" autocomplete="new-password">
		</div>

		<button type="submit" class="cvc-btn cvc-btn--primary">Đăng ký</button>
	</form>

	<p class="cvc-auth-switch">
		Đã có tài khoản?
		<a href="<?php echo esc_url( cvc_login_url() ); ?>">Đăng nhập</a>
	</p>
</main>

<?php get_footer(); ?>
