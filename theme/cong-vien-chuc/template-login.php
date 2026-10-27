<?php
/**
 * Đăng nhập - /dang-nhap/ (giao diện 28/10/2026, inc/auth-ui.php).
 *
 * Form POST tới admin-post.php?action=cvc_login (inc/auth.php::cvc_handle_login()). Trang luôn noindex.
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
$cvc_to_emp  = str_starts_with( $redirect_to, '/don-vi' );

cvc_seo_set_title( 'Đăng nhập' );
cvc_seo_set_noindex();

get_header();

cvc_auth_start(
	array(
		'mode'   => 'login',
		'crumbs' => array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Đăng nhập' ),
		),
		'title'  => 'Đăng nhập',
		'sub'    => 'Truy cập tài khoản Công Viên Chức',
		'desc'   => 'Đăng nhập để tra cứu văn bản, ôn thi, làm bài trắc nghiệm và sử dụng đầy đủ các tiện ích dành cho cán bộ, công chức.',
	)
);
?>
<div class="au-panel">
	<?php if ( function_exists( 'cvc_google_button' ) ) { cvc_google_button( $redirect_to, 'signin' ); } ?>

	<form class="au-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_login' ); ?>
		<input type="hidden" name="action" value="cvc_login">
		<?php if ( '' !== $redirect_to ) : ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
		<?php endif; ?>

		<?php
		cvc_auth_field(
			array(
				'id'    => 'cvc-login-email',
				'name'  => 'email',
				'type'  => 'email',
				'icon'  => 'mail',
				'label' => 'Email',
				'attrs' => array( 'required' => true, 'autocomplete' => 'email', 'placeholder' => 'Nhập email của bạn', 'inputmode' => 'email' ),
			)
		);
		cvc_auth_field(
			array(
				'id'    => 'cvc-login-password',
				'name'  => 'password',
				'type'  => 'password',
				'icon'  => 'lock',
				'label' => 'Mật khẩu',
				'attrs' => array( 'required' => true, 'autocomplete' => 'current-password', 'placeholder' => 'Nhập mật khẩu của bạn' ),
			)
		);
		?>

		<div class="au-row2">
			<label class="au-check"><input type="checkbox" name="remember" value="1" checked> Ghi nhớ đăng nhập trên thiết bị này</label>
			<a href="<?php echo esc_url( cvc_forgot_password_url() ); ?>">Quên mật khẩu?</a>
		</div>

		<button type="submit" class="au-submit"><?php echo cvc_auth_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Đăng nhập</button>
	</form>

	<p class="au-switch">
		Chưa có tài khoản?
		<a href="<?php echo esc_url( $cvc_to_emp ? add_query_arg( 'loai', 'don-vi', cvc_register_url() ) : cvc_register_url() ); ?>"><?php echo $cvc_to_emp ? 'Đăng ký tài khoản đơn vị' : 'Đăng ký ngay'; ?></a>
	</p>
</div>
<?php
cvc_auth_end( true );
get_footer();
