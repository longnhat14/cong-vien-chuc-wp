<?php
/**
 * Đặt lại mật khẩu - /dat-lai-mat-khau/?token=...&email=... (Phase 12)
 *
 * Link do backend gửi qua email. Token chỉ được kiểm tra ở backend
 * (POST /api/auth/reset-password) - trang này không tự đánh giá token.
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
?>

<main id="main" class="container cvc-page cvc-auth-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array( 'label' => 'Đặt lại mật khẩu' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Đặt lại mật khẩu</h1>
	</header>

	<?php cvc_render_notice(); ?>

	<?php if ( ! $has_link ) : ?>
		<p>Liên kết đặt lại mật khẩu không đầy đủ. Hãy mở đúng liên kết trong email, hoặc yêu cầu liên kết mới.</p>
		<p><a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( cvc_forgot_password_url() ); ?>">Yêu cầu liên kết mới</a></p>
	<?php else : ?>
		<form class="cvc-form cvc-form--auth" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cvc_reset_password' ); ?>
			<input type="hidden" name="action" value="cvc_reset_password">
			<input type="hidden" name="token" value="<?php echo esc_attr( $reset_token ); ?>">
			<input type="hidden" name="email" value="<?php echo esc_attr( $reset_email ); ?>">

			<div class="cvc-form__field">
				<label for="cvc-reset-email-display">Email</label>
				<input type="email" id="cvc-reset-email-display" value="<?php echo esc_attr( $reset_email ); ?>" disabled>
			</div>

			<div class="cvc-form__field">
				<label for="cvc-reset-password">Mật khẩu mới</label>
				<input type="password" id="cvc-reset-password" name="password" required minlength="8" autocomplete="new-password" aria-describedby="cvc-reset-password-hint">
				<small id="cvc-reset-password-hint" class="cvc-form__hint">Ít nhất 8 ký tự, gồm cả chữ và số.</small>
			</div>

			<div class="cvc-form__field">
				<label for="cvc-reset-password-confirm">Nhập lại mật khẩu mới</label>
				<input type="password" id="cvc-reset-password-confirm" name="password_confirmation" required minlength="8" autocomplete="new-password">
			</div>

			<button type="submit" class="cvc-btn cvc-btn--primary">Đặt lại mật khẩu</button>
		</form>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
