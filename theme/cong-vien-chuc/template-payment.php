<?php
/**
 * CÔNG VIÊN CHỨC — THANH TOÁN CHUYỂN KHOẢN VIETQR (SePay, 20/10/2026)
 * URL: /thanh-toan/{mã đơn}/
 * Quét mã QR bằng app ngân hàng → SePay báo biến động số dư → hệ thống tự mở khóa → trang tự chuyển về.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();
nocache_headers();
do_action( 'litespeed_control_set_nocache', 'cvc: trang thanh toán theo người dùng' );
cvc_seo_set_noindex();
cvc_seo_set_title( 'Thanh toán chuyển khoản' );

$code  = strtoupper( preg_replace( '/[^0-9A-Za-z]/', '', (string) get_query_var( 'cvc_order_code' ) ) );
$token = cvc_auth_token();
$res   = ( new CVC_Api_Client( null, 10 ) )->get( '/api/orders/' . rawurlencode( $code ), array(), $token );
$order = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
$tr    = is_array( $order['transfer'] ?? null ) ? $order['transfer'] : array();
$paid  = 'paid' === ( $order['status'] ?? '' );
$back  = function_exists( 'cvc_pe_buy_back_url' ) ? cvc_pe_buy_back_url() : '';
$done  = '' !== $back ? $back : cvc_order_result_url( 'success', $code );
$money = static fn ( $n ) => number_format( (float) $n, 0, ',', '.' ) . ' ₫';

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5" style="max-width:980px">
	<?php cvc_render_notice(); ?>

	<?php if ( ! $res['ok'] ) : ?>
		<div class="dz-panel text-center space-y-3">
			<h1 class="text-xl font-black text-ink">Không tìm thấy đơn hàng</h1>
			<p class="text-sm text-ink-2"><?php echo esc_html( 404 === (int) $res['status'] ? 'Đơn hàng không tồn tại hoặc không thuộc tài khoản của bạn.' : cvc_api_error_message( $res ) ); ?></p>
			<a class="inline-block px-5 py-2.5 bg-acc text-white font-black text-sm rounded-xl" href="<?php echo esc_url( home_url( '/' ) ); ?>">Về trang chủ</a>
		</div>
	<?php else : ?>
		<header class="dz-pagehead">
			<p class="dz-kicker">Đơn hàng <?php echo esc_html( $code ); ?></p>
			<h1>Thanh toán chuyển khoản</h1>
			<p>Quét mã QR bằng ứng dụng ngân hàng hoặc ví (VietQR). Hệ thống tự mở khóa ngay khi nhận được tiền — không cần gửi ảnh chụp.</p>
			<span class="dz-rule" aria-hidden="true"></span>
		</header>

		<section class="cvp-status <?php echo $paid ? 'is-paid' : ''; ?>" data-cvp-status aria-live="polite">
			<?php if ( $paid ) : ?>
				<b>✓ Đã thanh toán thành công</b><span>Nội dung đã được mở khóa. <a href="<?php echo esc_url( $done ); ?>">Tiếp tục →</a></span>
			<?php elseif ( 'pending' !== ( $order['status'] ?? '' ) ) : ?>
				<b>Đơn hàng không còn chờ thanh toán</b><span>Trạng thái: <?php echo esc_html( (string) ( $order['status'] ?? '' ) ); ?></span>
			<?php else : ?>
				<i class="cvp-spin" aria-hidden="true"></i><b>Đang chờ bạn chuyển khoản…</b><span data-cvp-sub>Trang tự cập nhật; thường mất 5–30 giây sau khi chuyển.</span>
			<?php endif; ?>
		</section>

		<?php if ( ! $paid && 'pending' === ( $order['status'] ?? '' ) ) : ?>
			<?php if ( $tr ) : ?>
				<div class="cvp-grid">
					<section class="dz-card cvp-qr">
						<img src="<?php echo esc_url( (string) $tr['qr_url'] ); ?>" alt="Mã VietQR chuyển <?php echo esc_attr( $money( $tr['amount'] ) ); ?> tới <?php echo esc_attr( (string) $tr['bank'] ); ?>" width="300" height="300" loading="eager">
						<p>Mở app ngân hàng → <b>Quét QR</b>. Số tiền và nội dung đã điền sẵn.</p>
						<a class="cvp-dl" href="<?php echo esc_url( add_query_arg( 'download', 'true', (string) $tr['qr_url'] ) ); ?>" rel="nofollow noopener" target="_blank">Tải ảnh QR</a>
					</section>
					<section class="dz-card cvp-info">
						<h2>Hoặc chuyển khoản thủ công</h2>
						<dl>
							<?php
							$rows = array(
								array( 'Ngân hàng', (string) $tr['bank'], false ),
								array( 'Số tài khoản', (string) $tr['account_number'], true ),
								array( 'Chủ tài khoản', (string) $tr['account_name'], false ),
								array( 'Số tiền', $money( $tr['amount'] ), (string) (int) $tr['amount'] ),
								array( 'Nội dung', (string) $tr['content'], true ),
							);
							foreach ( $rows as $r ) :
								if ( '' === $r[1] ) {
									continue;
								}
								$copy = true === $r[2] ? $r[1] : ( is_string( $r[2] ) ? $r[2] : '' );
								?>
								<div class="<?php echo 'Nội dung' === $r[0] ? 'is-key' : ''; ?>">
									<dt><?php echo esc_html( $r[0] ); ?></dt>
									<dd><span><?php echo esc_html( $r[1] ); ?></span><?php if ( '' !== $copy ) : ?><button type="button" data-cvp-copy="<?php echo esc_attr( $copy ); ?>">Sao chép</button><?php endif; ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
						<p class="cvp-warn">Ghi <b>đúng nội dung <?php echo esc_html( (string) $tr['content'] ); ?></b> và <b>đúng số tiền</b> để hệ thống tự nhận. Chuyển thiếu sẽ chưa mở khóa — chuyển bổ sung phần còn lại với cùng nội dung.</p>
						<?php if ( ! empty( $order['received'] ) && (int) $order['received'] < (int) $tr['amount'] ) : ?>
							<p class="cvp-warn">Đã nhận <?php echo esc_html( $money( $order['received'] ) ); ?> — còn thiếu <?php echo esc_html( $money( (int) $tr['amount'] - (int) $order['received'] ) ); ?>.</p>
						<?php endif; ?>
					</section>
				</div>
			<?php else : ?>
				<div class="dz-panel"><p class="text-sm text-ink-2">Chuyển khoản VietQR đang tạm ngưng.</p></div>
			<?php endif; ?>

			<section class="dz-card cvp-order">
				<h2>Đơn hàng</h2>
				<ul>
					<?php foreach ( (array) ( $order['items'] ?? array() ) as $it ) : ?>
						<li><span><?php echo esc_html( (string) $it['title'] ); ?></span><b><?php echo esc_html( $money( $it['price'] ) ); ?></b></li>
					<?php endforeach; ?>
					<li class="is-total"><span>Tổng cộng</span><b><?php echo esc_html( $money( $order['total_amount'] ?? 0 ) ); ?></b></li>
				</ul>
				<?php if ( ! empty( $order['vnpay_available'] ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'cvc_order_vnpay' ); ?>
						<input type="hidden" name="action" value="cvc_order_vnpay">
						<input type="hidden" name="code" value="<?php echo esc_attr( $code ); ?>">
						<button type="submit" class="cvp-alt">Thanh toán bằng thẻ / VNPay thay thế</button>
					</form>
				<?php endif; ?>
				<p class="cvp-note">Gặp sự cố? Giữ lại biên lai chuyển khoản và liên hệ hỗ trợ kèm mã đơn <b><?php echo esc_html( $code ); ?></b>.</p>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</div>
</main>

<style>
.cvp-status{display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px;padding:14px 18px;border-radius:14px;border:1px solid #f0d9a6;background:#fff8e8;color:#5c4210}
.cvp-status b{font-size:16px}.cvp-status span{font-size:14px}
.cvp-status.is-paid{border-color:#bfe5d9;background:#ecf8f4;color:#0e645d}
.cvp-status a{font-weight:bold;text-decoration:underline}
.cvp-spin{width:16px;height:16px;border-radius:50%;border:2px solid currentColor;border-right-color:transparent;animation:cvpspin .9s linear infinite}
@keyframes cvpspin{to{transform:rotate(360deg)}}
@media (prefers-reduced-motion:reduce){.cvp-spin{animation:none}}
.cvp-grid{display:grid;grid-template-columns:minmax(0,340px) minmax(0,1fr);gap:16px}
.cvp-qr{text-align:center}.cvp-qr img{width:100%;max-width:300px;height:auto;margin:0 auto;background:#fff;border-radius:10px}
.cvp-qr p{font-size:14px;margin:10px 0 6px}.cvp-dl{font-size:13px;font-weight:bold;color:var(--acc,#0e645d)}
.cvp-info h2,.cvp-order h2{font-size:16px;font-weight:900;margin:0 0 10px}
.cvp-info dl{display:grid;gap:0}.cvp-info dl>div{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #e3e7e9}
.cvp-info dt{color:#5d6870;font-size:14px;flex-shrink:0}.cvp-info dd{display:flex;align-items:center;gap:8px;font-weight:bold;text-align:right;word-break:break-all;margin:0}
.cvp-info .is-key dd span{font-size:18px;letter-spacing:.5px;color:#a3360f}
.cvp-info button{font-size:12px;font-weight:bold;padding:4px 9px;border-radius:8px;border:1px solid #cfd6da;background:#fff;cursor:pointer;white-space:nowrap}
.cvp-info button:focus-visible,.cvp-alt:focus-visible{outline:2px solid #0e645d;outline-offset:2px}
.cvp-warn{margin:12px 0 0;font-size:13px;padding:9px 11px;border-radius:9px;background:#fff4ef;color:#7a2d0c}
.cvp-order ul{list-style:none;margin:0;padding:0}.cvp-order li{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px dashed #e3e7e9;font-size:14px}
.cvp-order li b{white-space:nowrap}.cvp-order li.is-total{border-bottom:0;font-size:16px}.cvp-alt{margin-top:12px;font-size:13px;font-weight:bold;padding:8px 12px;border-radius:10px;border:1px solid #cfd6da;background:#fff;cursor:pointer}
.cvp-note{font-size:12px;color:#5d6870;margin-top:10px}
@media (max-width:760px){.cvp-grid{grid-template-columns:1fr}}
</style>
<?php if ( $res['ok'] && ! $paid && 'pending' === ( $order['status'] ?? '' ) ) : ?>
<script>
(function () {
	'use strict';
	var url = <?php echo wp_json_encode( add_query_arg( array( 'action' => 'cvc_order_status', 'code' => $code, 'nonce' => wp_create_nonce( 'cvc_order_status' ) ), admin_url( 'admin-ajax.php' ) ) ); ?>;
	var done = <?php echo wp_json_encode( $done ); ?>;
	var box = document.querySelector('[data-cvp-status]');
	var started = Date.now(), timer;
	document.querySelectorAll('[data-cvp-copy]').forEach(function (b) {
		b.addEventListener('click', function () {
			var v = b.getAttribute('data-cvp-copy');
			var ok = function () { var t = b.textContent; b.textContent = 'Đã chép'; setTimeout(function () { b.textContent = t; }, 1500); };
			if (navigator.clipboard) { navigator.clipboard.writeText(v).then(ok, function () {}); } else { var i = document.createElement('input'); i.value = v; document.body.appendChild(i); i.select(); try { document.execCommand('copy'); ok(); } catch (e) {} i.remove(); }
		});
	});
	function paid() {
		box.className = 'cvp-status is-paid';
		box.innerHTML = '<b>✓ Đã nhận tiền — nội dung đã được mở khóa</b><span>Đang chuyển bạn về trang trước… <a href="' + done.replace(/"/g, '&quot;') + '">Tiếp tục ngay →</a></span>';
		document.querySelectorAll('.cvp-grid,.cvp-order').forEach(function (el) { el.hidden = true; });
		setTimeout(function () { location.href = done; }, 2500);
	}
	function tick() {
		fetch(url, { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (res) {
			if (res && res.success && res.data) {
				if (res.data.status === 'paid') { paid(); return; }
				var sub = box.querySelector('[data-cvp-sub]');
				if (sub && res.data.received > 0 && res.data.received < res.data.total) {
					sub.textContent = 'Đã nhận ' + res.data.received.toLocaleString('vi-VN') + ' ₫ / ' + res.data.total.toLocaleString('vi-VN') + ' ₫ — chuyển bổ sung phần còn thiếu với cùng nội dung.';
				}
			}
			schedule();
		}).catch(schedule);
	}
	function schedule() {
		var age = Date.now() - started;
		timer = setTimeout(tick, age < 15 * 60 * 1000 ? 4000 : 15000);
	}
	document.addEventListener('visibilitychange', function () { if (!document.hidden) { clearTimeout(timer); tick(); } });
	schedule();
})();
</script>
<?php endif; ?>
<?php get_footer(); ?>
