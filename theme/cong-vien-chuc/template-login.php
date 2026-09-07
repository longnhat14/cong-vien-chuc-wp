<?php
/**
 * Đăng nhập - /dang-nhap/
 *
 * Form POST tới admin-post.php?action=cvc_login (Post/Redirect/Get,
 * inc/auth.php::cvc_handle_login()). Trang này luôn noindex (Phần XII SEO -
 * không có giá trị index, chỉ là cổng vào).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Đã đăng nhập rồi thì không cần thấy form nữa.
if ( cvc_is_logged_in() ) {
	wp_safe_redirect( cvc_account_url() );
	exit;
}

$redirect_to = isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : '';

cvc_seo_set_title( 'Đăng nhập' );
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
			array( 'label' => 'Đăng nhập' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Đăng nhập</h1>
	</header>

	<?php cvc_render_notice(); ?>

	<form class="cvc-form cvc-form--auth" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_login' ); ?>
		<input type="hidden" name="action" value="cvc_login">
		<?php if ( '' !== $redirect_to ) : ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
		<?php endif; ?>

		<div class="cvc-form__field">
			<label for="cvc-login-email">Email</label>
			<input type="email" id="cvc-login-email" name="email" required autocomplete="email">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-login-password">Mật khẩu</label>
			<input type="password" id="cvc-login-password" name="password" required autocomplete="current-password">
		</div>

		<button type="submit" class="cvc-btn cvc-btn--primary">Đăng nhập</button>
	</form>

	<p class="cvc-auth-switch">
		Chưa có tài khoản?
		<a href="<?php echo esc_url( cvc_register_url() ); ?>">Đăng ký ngay</a>
	</p>
</main>

<?php get_footer(); ?>
