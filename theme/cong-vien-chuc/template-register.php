<?php
/**
 * Đăng ký - /dang-ky/ (giao diện 28/10/2026, inc/auth-ui.php; chọn loại tài khoản từ 21/10/2026).
 *
 * Hai loại: "Thí sinh / học viên" và "Đơn vị tuyển dụng" (?loai=don-vi chọn sẵn).
 * Tài khoản đơn vị = tài khoản thường + đơn vị, tạo trong 1 lần gọi POST /api/auth/register
 * (account_type=employer, employer[...]). Email công vụ (.gov.vn/.edu.vn) → backend gửi ngay mã 6 số để xác minh đơn vị.
 * Bắt buộc đồng ý Chính sách bảo mật + Điều khoản sử dụng (terms). Validation thật ở Laravel.
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
$oe     = (array) ( $old['employer'] ?? array() );
$sector = ( $priv_ok && 'private' === ( $oe['sector'] ?? '' ) ) ? 'private' : 'public';
$ov     = static function ( string $k ) use ( $oe ): string {
	return (string) ( $oe[ $k ] ?? '' );
};
$hid    = static fn ( string $for ): string => $for !== $type ? ' hidden' : '';

cvc_seo_set_title( 'employer' === $type ? 'Đăng ký tài khoản đơn vị tuyển dụng' : 'Đăng ký' );
cvc_seo_set_noindex();

get_header();

cvc_auth_start(
	array(
		'mode'   => 'register',
		'crumbs' => array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Đăng ký' ),
		),
		'title'  => 'Đăng ký tài khoản',
		'sub'    => 'Tham gia cộng đồng Công Viên Chức',
		'desc'   => 'Tạo tài khoản miễn phí để tra cứu văn bản, ôn thi, làm bài trắc nghiệm và nhận thông báo tuyển dụng mới nhất.',
	)
);
?>
<div class="au-panel">
	<?php if ( function_exists( 'cvc_google_button' ) ) { cvc_google_button( isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : '', 'signup' ); } ?>

	<form class="au-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="cvc-register-form">
		<?php wp_nonce_field( 'cvc_register' ); ?>
		<input type="hidden" name="action" value="cvc_register">

		<?php if ( $emp_open ) : ?>
			<fieldset class="au-types">
				<legend>Bạn đăng ký với tư cách</legend>
				<div class="au-types__grid">
					<label class="au-type">
						<input type="radio" name="account_type" value="learner" <?php checked( 'learner', $type ); ?>>
						<span class="au-type__ico"><?php echo cvc_auth_icon( 'cap' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><b>Thí sinh / học viên</b><small>Ôn thi, làm đề, theo dõi tin tuyển dụng, nhận học liệu.</small></span>
					</label>
					<label class="au-type au-type--emp">
						<input type="radio" name="account_type" value="employer" <?php checked( 'employer', $type ); ?>>
						<span class="au-type__ico"><?php echo cvc_auth_icon( 'bank' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><b>Đơn vị tuyển dụng</b><small>Đăng thông tin tuyển dụng miễn phí, cập nhật đợt tuyển, đính chính.</small></span>
					</label>
				</div>
			</fieldset>
		<?php endif; ?>

		<?php
		cvc_auth_field(
			array(
				'id'         => 'cvc-register-name',
				'name'       => 'name',
				'icon'       => 'user',
				'row'        => true,
				'label_html' => '<span data-for="learner"' . $hid( 'learner' ) . '>Họ và tên</span><span data-for="employer"' . $hid( 'employer' ) . '>Họ tên người phụ trách</span>',
				'attrs'      => array( 'required' => true, 'autocomplete' => 'name', 'placeholder' => 'Nhập họ và tên của bạn', 'value' => (string) ( $old['name'] ?? '' ) ),
			)
		);
		cvc_auth_field(
			array(
				'id'    => 'cvc-register-email',
				'name'  => 'email',
				'type'  => 'email',
				'icon'  => 'mail',
				'row'   => true,
				'label' => 'Email',
				'attrs' => array( 'required' => true, 'autocomplete' => 'email', 'inputmode' => 'email', 'placeholder' => 'Nhập email của bạn', 'value' => (string) ( $old['email'] ?? '' ), 'aria-describedby' => 'cvc-email-hint' ),
				'after' => '<small id="cvc-email-hint" class="au-hint" aria-live="polite"></small>',
			)
		);
		?>

		<?php if ( $emp_open ) : ?>
			<fieldset class="au-emp" id="cvc-emp-fields" <?php disabled( 'employer' !== $type ); ?><?php echo 'employer' !== $type ? ' hidden' : ''; ?>>
				<legend>Thông tin đơn vị</legend>
				<?php
				cvc_auth_field(
					array(
						'id'    => 'cvc-emp-name',
						'name'  => 'employer[name]',
						'icon'  => 'bank',
						'label' => 'Tên đơn vị (theo con dấu)',
						'attrs' => array( 'required' => true, 'minlength' => 5, 'maxlength' => 255, 'placeholder' => 'VD: Bệnh viện Đa khoa tỉnh …', 'value' => $ov( 'name' ) ),
					)
				);
				?>
				<?php if ( $priv_ok ) : ?>
					<div class="au-field">
						<span class="au-field__label" style="display:block;margin:0 0 4px;font-size:16px;font-weight:700;color:#0f2a4a">Loại hình</span>
						<label class="au-radio"><input type="radio" name="employer[sector]" value="public" <?php checked( 'public', $sector ); ?>> Cơ quan nhà nước / đơn vị sự nghiệp công lập</label>
						<label class="au-radio"><input type="radio" name="employer[sector]" value="private" <?php checked( 'private', $sector ); ?>> Đơn vị ngoài công lập</label>
					</div>
				<?php else : ?>
					<input type="hidden" name="employer[sector]" value="public">
				<?php endif; ?>
				<div class="au-field">
					<label for="cvc-emp-prov">Tỉnh/thành</label>
					<select id="cvc-emp-prov" name="employer[province_id]" required>
						<option value="">— Chọn —</option>
						<?php foreach ( $provs as $pv ) : ?>
							<option value="<?php echo (int) $pv['id']; ?>" <?php selected( (int) ( $oe['province_id'] ?? 0 ), (int) $pv['id'] ); ?>><?php echo esc_html( (string) $pv['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php
				cvc_auth_field(
					array(
						'id'    => 'cvc-emp-phone',
						'name'  => 'employer[phone]',
						'type'  => 'tel',
						'icon'  => 'support',
						'label' => 'Điện thoại cơ quan',
						'attrs' => array( 'required' => true, 'maxlength' => 30, 'placeholder' => 'Số của phòng tổ chức / văn phòng', 'value' => $ov( 'phone' ) ),
					)
				);
				cvc_auth_field(
					array(
						'id'         => 'cvc-emp-tax',
						'name'       => 'employer[tax_code]',
						'icon'       => 'tag',
						'label'      => 'Mã số thuế / mã số doanh nghiệp',
						'wrap_attrs' => 'id="cvc-emp-tax-wrap"' . ( 'private' === $sector ? '' : ' hidden' ),
						'attrs'      => array( 'maxlength' => 20, 'value' => $ov( 'tax_code' ) ) + ( 'private' !== $sector ? array( 'disabled' => true ) : array() ),
					)
				);
				cvc_auth_field(
					array(
						'id'         => 'cvc-emp-web',
						'name'       => 'employer[website]',
						'type'       => 'url',
						'icon'       => 'devices',
						'label_html' => 'Website đơn vị <small style="font-weight:400;color:#56606d">(không bắt buộc)</small>',
						'attrs'      => array( 'maxlength' => 255, 'placeholder' => 'https://…gov.vn', 'value' => $ov( 'website' ) ),
					)
				);
				?>
			</fieldset>
		<?php endif; ?>

		<?php
		cvc_auth_field(
			array(
				'id'    => 'cvc-register-password',
				'name'  => 'password',
				'type'  => 'password',
				'icon'  => 'lock',
				'row'   => true,
				'label' => 'Mật khẩu',
				'attrs' => array( 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password', 'placeholder' => 'Ít nhất 8 ký tự, gồm cả chữ và số' ),
			)
		);
		cvc_auth_field(
			array(
				'id'    => 'cvc-register-password-confirm',
				'name'  => 'password_confirmation',
				'type'  => 'password',
				'icon'  => 'lock',
				'row'   => true,
				'label' => 'Nhập lại mật khẩu',
				'attrs' => array( 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password', 'placeholder' => 'Nhập lại mật khẩu' ),
			)
		);
		?>

		<label class="au-check"><input type="checkbox" name="agree_terms" value="1" required <?php checked( ! empty( $old['agree_terms'] ) ); ?>> <span>Tôi đồng ý với <a href="<?php echo esc_url( home_url( '/chinh-sach-bao-mat/' ) ); ?>" target="_blank" rel="noopener">Chính sách bảo mật</a> và <a href="<?php echo esc_url( home_url( '/dieu-khoan-su-dung/' ) ); ?>" target="_blank" rel="noopener">Điều khoản sử dụng</a> của Công Viên Chức.</span></label>

		<button type="submit" class="au-submit"><span data-for="learner"<?php echo $hid( 'learner' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>Đăng ký</span><span data-for="employer"<?php echo $hid( 'employer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>Đăng ký đơn vị</span> <?php echo cvc_auth_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
	</form>

	<p class="au-switch">
		Đã có tài khoản? <a href="<?php echo esc_url( cvc_login_url() ); ?>">Đăng nhập</a>
		<?php if ( $emp_open ) : ?> · <a href="<?php echo esc_url( home_url( '/don-vi-tuyen-dung/' ) ); ?>">Tìm hiểu về đăng tin cho đơn vị</a><?php endif; ?>
	</p>
</div>
<?php cvc_auth_end( false ); ?>

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
	var google = document.querySelector('.au-google');
	var gor = google ? google.nextElementSibling : null;
	var official = /@([a-z0-9-]+\.)*(gov|edu)\.vn$/i;

	function type() { var r = form.querySelector('input[name="account_type"]:checked'); return r ? r.value : 'learner'; }
	function sector() { var r = form.querySelector('input[name="employer[sector]"]:checked'); return r ? r.value : 'public'; }

	function render() {
		var t = type(), s = sector();
		box.disabled = t !== 'employer';
		box.hidden = t !== 'employer';
		// Google chỉ tạo tài khoản thí sinh / học viên.
		if (google) { google.hidden = t === 'employer'; if (gor) { gor.hidden = t === 'employer'; } }
		document.querySelectorAll('#cvc-register-form [data-for], .au-card [data-for]').forEach(function (el) { el.hidden = el.getAttribute('data-for') !== t; });
		if (taxWrap) { taxWrap.hidden = s !== 'private'; tax.disabled = s !== 'private'; tax.required = s === 'private'; }

		var v = (email.value || '').trim(), isOff = official.test(v);
		hint.innerHTML = '';
		if (t === 'learner' && isOff) {
			hint.innerHTML = 'Đây là email công vụ. Bạn đăng ký cho đơn vị tuyển dụng? <button type="button" class="au-linkbtn" id="cvc-to-emp">Chuyển sang tài khoản đơn vị</button>';
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
