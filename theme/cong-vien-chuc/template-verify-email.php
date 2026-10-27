<?php
/**
 * /xac-minh-email/ - Xác minh email tài khoản (27/10/2026). Xem inc/email-verify.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification
$cvc_ve_result = isset( $_GET['ket-qua'] ) ? sanitize_key( wp_unslash( $_GET['ket-qua'] ) ) : '';
$cvc_ve_return = isset( $_GET['quay-lai'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['quay-lai'] ) ) ) : '';
// phpcs:enable
if ( '' !== $cvc_ve_return && ( ! str_starts_with( $cvc_ve_return, '/' ) || str_starts_with( $cvc_ve_return, '//' ) ) ) {
	$cvc_ve_return = '';
}
$cvc_ve_token  = cvc_auth_token();
$cvc_ve_status = array();
if ( $cvc_ve_token ) {
	$cvc_ve_r      = ( new CVC_Api_Client( null, 10 ) )->get( '/api/auth/email/status', array(), $cvc_ve_token );
	$cvc_ve_status = ! empty( $cvc_ve_r['ok'] ) ? (array) ( $cvc_ve_r['data']['data'] ?? array() ) : array();
}
$cvc_ve_verified = ! empty( $cvc_ve_status['verified'] ) || 'thanh-cong' === $cvc_ve_result;

cvc_seo_set_title( 'Xác minh email' );
cvc_seo_set_noindex();
nocache_headers();
get_header();
?>
<style>
.ve{background:#f6f5f1;font-family:"Times New Roman",Tinos,Times,serif;color:#1d2733}
.ve-wrap{max-width:620px;margin:0 auto;padding:18px 16px 56px}
.ve-card{background:#fff;border:1px solid #e8dfc9;border-radius:18px;padding:24px;box-shadow:0 10px 30px #0f2a4a10}
.ve h1{margin:0 0 8px;color:#0f2a4a;font-size:27px}
.ve p{font-size:17px;line-height:1.6;margin:0 0 12px}
.ve-code{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:14px 0}
.ve-code input{font:700 28px/1 "Courier New",monospace;letter-spacing:8px;width:min(260px,100%);padding:10px 12px;border:2px solid #c9a052;border-radius:12px;text-align:center;color:#0f2a4a}
.ve-btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:10px 20px;border:0;border-radius:12px;background:#0f2a4a;color:#fff!important;font:700 16px "Times New Roman",serif;cursor:pointer;text-decoration:none}
.ve-btn.is-ghost{background:#f8f2e4;color:#0f2a4a!important;border:1px solid #c9a052}
.ve-row{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:10px 0}
.ve details{margin-top:16px;padding:12px 14px;border:1px solid #e8dfc9;border-radius:12px;background:#fffdf8}
.ve summary{cursor:pointer;font-weight:700;color:#0f2a4a}
.ve label{display:block;margin:10px 0 4px;font-weight:700}
.ve details input{width:100%;padding:10px 12px;border:1px solid #cfd9e3;border-radius:10px;font:inherit;font-size:16px}
.ve-ok{padding:14px 16px;border-radius:12px;background:#e6f5ec;color:#11683a;font-weight:700}
.ve-err{padding:14px 16px;border-radius:12px;background:#fdecea;color:#9b1c1c}
.ve-note{font-size:15px!important;color:#55657a}
</style>
<main id="main" class="ve">
	<div class="ve-wrap">
		<?php cvc_render_notice(); ?>
		<article class="ve-card">
			<?php if ( $cvc_ve_verified ) : ?>
				<h1>Email đã được xác minh</h1>
				<p class="ve-ok">Cảm ơn bạn! Tài khoản đã xác minh email<?php echo ! empty( $cvc_ve_status['email'] ) ? ' ' . esc_html( (string) $cvc_ve_status['email'] ) : ''; ?>.</p>
				<p>Bây giờ bạn có thể tải đề PDF có đáp án và nhận bản tin tuyển dụng theo tỉnh, ngành mỗi tuần.</p>
				<div class="ve-row">
					<a class="ve-btn" href="<?php echo esc_url( '' !== $cvc_ve_return ? home_url( $cvc_ve_return ) : home_url( '/bo-de-pdf/' ) ); ?>"><?php echo '' !== $cvc_ve_return ? 'Quay lại trang trước' : 'Xem bộ đề PDF'; ?></a>
					<a class="ve-btn is-ghost" href="<?php echo esc_url( home_url( '/tai-khoan/muc-tieu/' ) ); ?>">Đặt mục tiêu ôn thi</a>
				</div>
			<?php elseif ( ! $cvc_ve_token ) : ?>
				<h1>Xác minh email</h1>
				<?php if ( 'het-han' === $cvc_ve_result ) : ?><p class="ve-err">Liên kết xác minh đã hết hạn hoặc đã được thay bằng mã mới.</p><?php endif; ?>
				<p>Đăng nhập để nhập mã xác minh hoặc nhận mã mới.</p>
				<a class="ve-btn" href="<?php echo esc_url( cvc_login_url( '/xac-minh-email/' ) ); ?>">Đăng nhập</a>
			<?php else : ?>
				<h1>Xác minh email</h1>
				<?php if ( 'het-han' === $cvc_ve_result ) : ?><p class="ve-err">Liên kết xác minh đã hết hạn hoặc đã được thay bằng mã mới. Hãy nhập mã mới nhất hoặc bấm "Gửi lại mã".</p><?php endif; ?>
				<p>Nhập mã 6 số đã gửi tới <b><?php echo esc_html( (string) ( $cvc_ve_status['email'] ?? '' ) ); ?></b>. Không thấy email? Kiểm tra thư mục <b>Spam / Quảng cáo</b>.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'cvc_email_verify' ); ?>
					<input type="hidden" name="action" value="cvc_email_verify"><input type="hidden" name="op" value="verify"><input type="hidden" name="quay_lai" value="<?php echo esc_attr( $cvc_ve_return ); ?>">
					<div class="ve-code">
						<label for="ve-code" class="screen-reader-text">Mã xác minh</label>
						<input id="ve-code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" placeholder="••••••" required>
						<button type="submit" class="ve-btn">Xác minh</button>
					</div>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ve-row">
					<?php wp_nonce_field( 'cvc_email_verify' ); ?>
					<input type="hidden" name="action" value="cvc_email_verify"><input type="hidden" name="op" value="resend"><input type="hidden" name="quay_lai" value="<?php echo esc_attr( $cvc_ve_return ); ?>">
					<?php $cvc_ve_wait = (int) ( $cvc_ve_status['resend_in'] ?? 0 ); ?>
					<button type="submit" class="ve-btn is-ghost" id="ve-resend" data-wait="<?php echo (int) $cvc_ve_wait; ?>"<?php disabled( $cvc_ve_wait > 0 ); ?>>Gửi lại mã<?php echo $cvc_ve_wait > 0 ? ' (' . (int) $cvc_ve_wait . ' giây)' : ''; ?></button>
					<span class="ve-note">Mã có hiệu lực 30 phút; nút trong email có hiệu lực 48 giờ.</span>
				</form>
				<details>
					<summary>Nhập sai email? Sửa email</summary>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'cvc_email_verify' ); ?>
						<input type="hidden" name="action" value="cvc_email_verify"><input type="hidden" name="op" value="change"><input type="hidden" name="quay_lai" value="<?php echo esc_attr( $cvc_ve_return ); ?>">
						<label for="ve-email">Email đúng</label><input id="ve-email" type="email" name="email" required autocomplete="email" placeholder="ten@gmail.com">
						<label for="ve-pass">Mật khẩu hiện tại</label><input id="ve-pass" type="password" name="password" required autocomplete="current-password">
						<p class="ve-note" style="margin-top:8px">Sau khi đổi, mã mới được gửi tới email mới; bạn đăng nhập bằng email mới.</p>
						<button type="submit" class="ve-btn">Đổi email và gửi mã</button>
					</form>
				</details>
				<p class="ve-note" style="margin-top:14px">Chưa xác minh bạn vẫn làm bài, dùng ngân hàng câu hỏi và AI Coach bình thường. Cần xác minh để tải đề PDF và nhận bản tin tuyển dụng qua email.</p>
			<?php endif; ?>
		</article>
	</div>
</main>
<script>
(function(){var b=document.getElementById('ve-resend');if(!b)return;var w=parseInt(b.getAttribute('data-wait')||'0',10);if(w<=0)return;var t=setInterval(function(){w--;if(w<=0){clearInterval(t);b.disabled=false;b.textContent='Gửi lại mã';}else{b.textContent='Gửi lại mã ('+w+' giây)';}},1000);})();
</script>
<?php
get_footer();
