<?php
/**
 * Đăng ký - /dang-ky/ (cập nhật 21/10/2026: chọn loại tài khoản).
 *
 * Hai loại: "Thí sinh / học viên" và "Đơn vị tuyển dụng" (?loai=don-vi chọn sẵn).
 * Tài khoản đơn vị = tài khoản thường + đơn vị, tạo trong 1 lần gọi POST /api/auth/register
 * (account_type=employer, employer[...]). Email đăng ký là email công vụ (.gov.vn/.edu.vn)
 * → backend gửi ngay mã 6 số tới chính email đó để xác minh đơn vị.
 * Validation thật ở Laravel; form chỉ ràng buộc HTML tối thiểu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( cvc_is_logged_in() ) {
	$emp_now = function_exists( 'cvc_employer_of_current_user' ) ? cvc_employer_of_current_user() : null;
	wp_safe_redirect( $emp_now ? home_url( '/don-vi/' ) : cvc_account_url() );
	exit;
}

$old      = cvc_consume_old_input();
$set      = function_exists( 'cvc_employer_public_settings' ) ? cvc_employer_public_settings() : array();
$emp_open = ! isset( $set['enabled'] ) || ! empty( $set['enabled'] );
$priv_ok  = ! empty( $set['private_enabled'] );
$provs    = (array) ( $set['provinces'] ?? array() );
$type     = (string) ( $old['account_type'] ?? ( ( isset( $_GET['loai'] ) && 'don-vi' === $_GET['loai'] ) ? 'employer' : 'learner' ) );
if ( ! $emp_open ) {
	$type = 'learner';
}
$oe       = (array) ( $old['employer'] ?? array() );
$sector   = ( $priv_ok && 'private' === ( $oe['sector'] ?? '' ) ) ? 'private' : 'public';
$ov       = static function ( string $k ) use ( $oe ): string {
	return esc_attr( (string) ( $oe[ $k ] ?? '' ) );
};

cvc_seo_set_title( 'employer' === $type ? 'Đăng ký tài khoản đơn vị tuyển dụng' : 'Đăng ký' );
cvc_seo_set_noindex();

get_header();
?>

<main id="main" class="dz-main container cvc-auth-page">
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

	<form class="cvc-form cvc-form--auth" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="cvc-register-form">
		<?php wp_nonce_field( 'cvc_register' ); ?>
		<input type="hidden" name="action" value="cvc_register">

		<?php if ( $emp_open ) : ?>
		<fieldset class="cvc-acct-type">
			<legend>Bạn đăng ký với tư cách</legend>
			<label class="cvc-acct-opt">
				<input type="radio" name="account_type" value="learner" <?php checked( 'learner', $type ); ?>>
				<span><b>Thí sinh / học viên</b><small>Ôn thi, làm đề, theo dõi tin tuyển dụng, nhắc hạn nộp hồ sơ.</small></span>
			</label>
			<label class="cvc-acct-opt">
				<input type="radio" name="account_type" value="employer" <?php checked( 'employer', $type ); ?>>
				<span><b>Đơn vị tuyển dụng</b><small>Đăng thông báo tuyển dụng miễn phí, cập nhật đợt tuyển, đính chính.</small></span>
			</label>
		</fieldset>
		<?php endif; ?>

		<div class="cvc-form__field">
			<label for="cvc-register-name"><span data-for="learner"<?php echo 'learner' !== $type ? ' hidden' : ''; ?>>Họ và tên</span><span data-for="employer"<?php echo 'employer' !== $type ? ' hidden' : ''; ?>>Họ tên người phụ trách</span></label>
			<input type="text" id="cvc-register-name" name="name" required autocomplete="name" value="<?php echo esc_attr( (string) ( $old['name'] ?? '' ) ); ?>">
		</div>

		<div class="cvc-form__field">
			<label for="cvc-register-email">Email</label>
			<input type="email" id="cvc-register-email" name="email" required autocomplete="email" value="<?php echo esc_attr( (string) ( $old['email'] ?? '' ) ); ?>" aria-describedby="cvc-email-hint">
			<small id="cvc-email-hint" class="cvc-form__hint" aria-live="polite"></small>
		</div>

		<?php if ( $emp_open ) : ?>
		<fieldset class="cvc-emp-fields" id="cvc-emp-fields" <?php disabled( 'employer' !== $type ); ?><?php echo 'employer' !== $type ? ' hidden' : ''; ?>>
			<legend>Thông tin đơn vị</legend>
			<div class="cvc-form__field">
				<label for="cvc-emp-name">Tên đơn vị (theo con dấu)</label>
				<input type="text" id="cvc-emp-name" name="employer[name]" required minlength="5" maxlength="255" placeholder="VD: Bệnh viện Đa khoa tỉnh …" value="<?php echo $ov( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
			</div>
			<?php if ( $priv_ok ) : ?>
			<div class="cvc-form__field">
				<span class="cvc-form__label">Loại hình</span>
				<label class="cvc-inline"><input type="radio" name="employer[sector]" value="public" <?php checked( 'public', $sector ); ?>> Cơ quan nhà nước / đơn vị sự nghiệp công lập</label>
				<label class="cvc-inline"><input type="radio" name="employer[sector]" value="private" <?php checked( 'private', $sector ); ?>> Đơn vị ngoài công lập</label>
			</div>
			<?php else : ?>
			<input type="hidden" name="employer[sector]" value="public">
			<?php endif; ?>
			<div class="cvc-form__field">
				<label for="cvc-emp-prov">Tỉnh/thành</label>
				<select id="cvc-emp-prov" name="employer[province_id]" required>
					<option value="">— Chọn —</option>
					<?php foreach ( $provs as $pv ) : ?>
						<option value="<?php echo (int) $pv['id']; ?>" <?php selected( (int) ( $oe['province_id'] ?? 0 ), (int) $pv['id'] ); ?>><?php echo esc_html( (string) $pv['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="cvc-form__field">
				<label for="cvc-emp-phone">Điện thoại cơ quan</label>
				<input type="tel" id="cvc-emp-phone" name="employer[phone]" required maxlength="30" placeholder="Số của phòng tổ chức / văn phòng" value="<?php echo $ov( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
			</div>
			<div class="cvc-form__field" id="cvc-emp-tax-wrap" <?php echo 'private' === $sector ? '' : 'hidden'; ?>>
				<label for="cvc-emp-tax">Mã số thuế / mã số doanh nghiệp</label>
				<input type="text" id="cvc-emp-tax" name="employer[tax_code]" maxlength="20" value="<?php echo $ov( 'tax_code' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>" <?php disabled( 'private' !== $sector ); ?>>
			</div>
			<div class="cvc-form__field">
				<label for="cvc-emp-web">Website đơn vị <small class="cvc-form__hint">(không bắt buộc)</small></label>
				<input type="url" id="cvc-emp-web" name="employer[website]" maxlength="255" placeholder="https://…gov.vn" value="<?php echo $ov( 'website' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>">
			</div>
		</fieldset>
		<?php endif; ?>

		<div class="cvc-form__field">
			<label for="cvc-register-password">Mật khẩu</label>
			<input type="password" id="cvc-register-password" name="password" required minlength="8" autocomplete="new-password" aria-describedby="cvc-register-password-hint">
			<small id="cvc-register-password-hint" class="cvc-form__hint">Ít nhất 8 ký tự, gồm cả chữ và số.</small>
		</div>

		<div class="cvc-form__field">
			<label for="cvc-register-password-confirm">Nhập lại mật khẩu</label>
			<input type="password" id="cvc-register-password-confirm" name="password_confirmation" required minlength="8" autocomplete="new-password">
		</div>

		<p class="cvc-form-note" style="font-size:14px;color:#55657a;margin:0 0 10px">Bằng việc đăng ký, bạn đồng ý với <a href="<?php echo esc_url( home_url( '/chinh-sach-bao-mat/' ) ); ?>" target="_blank" rel="noopener">Chính sách bảo mật</a> của Công Viên Chức.</p>
		<button type="submit" class="cvc-btn cvc-btn--primary"><span data-for="learner"<?php echo 'learner' !== $type ? ' hidden' : ''; ?>>Đăng ký</span><span data-for="employer"<?php echo 'employer' !== $type ? ' hidden' : ''; ?>>Đăng ký đơn vị</span></button>
	</form>

	<p class="cvc-auth-switch">
		Đã có tài khoản?
		<a href="<?php echo esc_url( cvc_login_url() ); ?>">Đăng nhập</a>
		<?php if ( $emp_open ) : ?> · <a href="<?php echo esc_url( home_url( '/don-vi-tuyen-dung/' ) ); ?>">Tìm hiểu về đăng tin cho đơn vị</a><?php endif; ?>
	</p>
</main>

<?php if ( $emp_open ) : ?>
<script>
(function () {
	var form = document.getElementById('cvc-register-form');
	if (!form) { return; }
	var box = document.getElementById('cvc-emp-fields');
	var email = document.getElementById('cvc-register-email');
	var hint = document.getElementById('cvc-email-hint');
	var taxWrap = document.getElementById('cvc-emp-tax-wrap');
	var tax = document.getElementById('cvc-emp-tax');
	var official = /@([a-z0-9-]+\.)*(gov|edu)\.vn$/i;

	function type() { var r = form.querySelector('input[name="account_type"]:checked'); return r ? r.value : 'learner'; }
	function sector() { var r = form.querySelector('input[name="employer[sector]"]:checked'); return r ? r.value : 'public'; }

	function render() {
		var t = type(), s = sector();
		box.disabled = t !== 'employer';
		box.hidden = t !== 'employer';
		form.querySelectorAll('[data-for]').forEach(function (el) { el.hidden = el.getAttribute('data-for') !== t; });
		if (taxWrap) { taxWrap.hidden = s !== 'private'; tax.disabled = s !== 'private'; tax.required = s === 'private'; }

		var v = (email.value || '').trim(), isOff = official.test(v);
		hint.innerHTML = '';
		if (t === 'learner' && isOff) {
			hint.innerHTML = 'Đây là email công vụ. Bạn đăng ký cho đơn vị tuyển dụng? <button type="button" class="cvc-linkbtn" id="cvc-to-emp">Chuyển sang tài khoản đơn vị</button>';
			document.getElementById('cvc-to-emp').addEventListener('click', function () {
				form.querySelector('input[name="account_type"][value="employer"]').checked = true; render();
				document.getElementById('cvc-emp-name').focus();
			});
		} else if (t === 'employer' && s === 'public' && v) {
			hint.textContent = isOff
				? 'Email công vụ: hệ thống gửi mã 6 số tới email này ngay khi đăng ký — nhập mã là đơn vị được xác minh.'
				: 'Email không thuộc .gov.vn/.edu.vn: sau khi đăng ký, xác minh bằng email công vụ khác hoặc giấy giới thiệu có dấu.';
		} else if (t === 'employer' && s === 'private') {
			hint.textContent = 'Đơn vị ngoài công lập xác minh bằng giấy phép hoạt động / giấy giới thiệu có dấu sau khi đăng ký.';
		}
	}
	form.addEventListener('change', render);
	email.addEventListener('input', render);
	render();
})();
</script>
<?php endif; ?>

<?php get_footer(); ?>
