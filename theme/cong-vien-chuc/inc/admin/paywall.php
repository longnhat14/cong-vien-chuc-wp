<?php
/**
 * Quản trị → Cấu hình → Thu phí (03/11/2026): 1 bảng bật/tắt thu phí cho từng tính năng,
 * mức miễn phí, thống kê 30 ngày (lượt chạm khóa, đơn & doanh thu theo tính năng) và nhật ký thay đổi.
 * API: GET/PUT /api/admin/paywall.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_post_cvc_admin_paywall', 'cvc_admin_handle_paywall' );
add_action( 'admin_post_nopriv_cvc_admin_paywall', 'cvc_admin_handle_paywall' );

function cvc_admin_handle_paywall(): void {
	check_admin_referer( 'cvc_admin_paywall' );
	$token = cvc_admin_require_staff();
	$back  = cvc_admin_url( 'thu-phi' );
	if ( ! cvc_admin_can( 'settings.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền đổi cấu hình thu phí.' );
		return;
	}
	$on    = array_map( 'sanitize_key', (array) wp_unslash( $_POST['on'] ?? array() ) );
	$test  = array_map( 'sanitize_key', (array) wp_unslash( $_POST['test'] ?? array() ) );
	$free  = (array) wp_unslash( $_POST['free'] ?? array() );
	$keys  = array_map( 'sanitize_key', (array) wp_unslash( $_POST['keys'] ?? array() ) );
	$body  = array();
	foreach ( $keys as $k ) {
		$row = array( 'mode' => in_array( $k, $on, true ) ? ( in_array( $k, $test, true ) ? 'force' : '1' ) : '0' );
		if ( isset( $free[ $k ] ) && '' !== $free[ $k ] ) {
			$row['free'] = absint( $free[ $k ] );
		}
		$body[ $k ] = $row;
	}
	$result = ( new CVC_Api_Client( null, 30 ) )->put( '/api/admin/paywall', array( 'features' => $body ), $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}
	if ( function_exists( 'cvc_paywall_flush' ) ) {
		cvc_paywall_flush();
	}
	cvc_admin_redirect( $back, 'success', (string) ( $result['data']['message'] ?? 'Đã lưu.' ) );
}

function cvc_admin_render_paywall(): void {
	$res   = cvc_admin_api_get( '/api/admin/paywall' );
	$d     = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$can   = cvc_admin_can( 'settings.update' );
	$money = static fn ( $n ) => number_format( (float) $n, 0, ',', '.' ) . ' ₫';
	$ready = ! empty( $d['payment_ready'] );
	$plans = array( 'pack' => 'Bộ đề theo tin', 'pass' => 'Gói ' . (int) ( $d['pass_days'] ?? 30 ) . ' ngày' );
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Thu phí theo tính năng</h1>
		<p class="text-sm text-ink-muted">Đánh dấu ô <b>Thu phí</b> để bắt đầu khóa tính năng đó (vẫn cho dùng thử phần miễn phí). Người đã mua giữ quyền đến hết hạn, tài khoản quản trị luôn được mở.</p>
	</header>
	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<div class="rounded-xl border p-3 text-sm <?php echo $ready ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'; ?>">
		<?php if ( $ready ) : ?>
			<i class="fa-solid fa-circle-check" aria-hidden="true"></i> Cổng thanh toán đã sẵn sàng: tính năng được đánh dấu Thu phí sẽ khóa ngay sau khi lưu.
		<?php else : ?>
			<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Chưa cấu hình cổng thanh toán (SePay / VNPay) nên <b>chưa khóa tính năng nào</b>, kể cả khi đã đánh dấu Thu phí (để người dùng không bị khóa mà không có cách mua). Cấu hình tại <a class="font-bold underline" href="<?php echo esc_url( cvc_admin_url( 'thanh-toan' ) ); ?>">Thanh toán</a>; muốn thử giao diện khóa trước thì đánh dấu thêm ô <b>Kiểm thử</b>.
		<?php endif; ?>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="border border-slate-300 rounded-xl bg-white overflow-x-auto">
		<?php wp_nonce_field( 'cvc_admin_paywall' ); ?>
		<input type="hidden" name="action" value="cvc_admin_paywall">
		<table class="w-full text-sm min-w-[760px]">
			<thead class="bg-slate-50 text-left text-xs text-ink-muted">
				<tr><th class="p-3">Tính năng</th><th class="p-3 w-32">Thu phí</th><th class="p-3 w-44">Phần miễn phí</th><th class="p-3 w-36">Mở khóa bằng</th><th class="p-3 w-40">30 ngày qua</th></tr>
			</thead>
			<tbody>
			<?php foreach ( (array) ( $d['features'] ?? array() ) as $f ) : ?>
				<?php
				$k    = (string) $f['key'];
				$mode = (string) ( $f['mode'] ?? '0' );
				$st   = ! empty( $f['active'] ) ? array( 'force' === $mode ? 'Đang khóa (kiểm thử)' : 'Đang thu phí', 'text-emerald-800' ) : ( '0' === $mode ? array( 'Miễn phí', 'text-ink-muted' ) : array( 'Chờ cổng thanh toán', 'text-amber-800' ) );
				?>
				<tr class="border-t border-slate-200 align-top">
					<td class="p-3">
						<input type="hidden" name="keys[]" value="<?php echo esc_attr( $k ); ?>">
						<p class="font-bold text-ink"><?php echo esc_html( (string) $f['label'] ); ?></p>
						<p class="text-xs text-ink-muted"><?php echo esc_html( (string) $f['desc'] ); ?></p>
						<p class="text-xs font-bold <?php echo esc_attr( $st[1] ); ?>"><?php echo esc_html( $st[0] ); ?></p>
					</td>
					<td class="p-3">
						<label class="flex items-center gap-2 font-bold whitespace-nowrap"><input type="checkbox" name="on[]" value="<?php echo esc_attr( $k ); ?>" class="accent-acc-2 w-4 h-4" <?php checked( '0' !== $mode ); ?> <?php disabled( ! $can ); ?>> Thu phí</label>
						<label class="flex items-center gap-2 text-xs text-ink-muted mt-1 whitespace-nowrap"><input type="checkbox" name="test[]" value="<?php echo esc_attr( $k ); ?>" class="accent-acc-2" <?php checked( 'force' === $mode ); ?> <?php disabled( ! $can ); ?>> Kiểm thử</label>
					</td>
					<td class="p-3">
						<?php if ( is_array( $f['free'] ?? null ) ) : ?>
							<label class="flex items-center gap-2 text-xs"><input type="number" name="free[<?php echo esc_attr( $k ); ?>]" min="<?php echo (int) $f['free']['min']; ?>" max="<?php echo (int) $f['free']['max']; ?>" value="<?php echo (int) $f['free']['value']; ?>" class="w-16 px-2 py-1 rounded-lg border border-slate-300" <?php disabled( ! $can ); ?>> <?php echo esc_html( (string) $f['free']['unit'] ); ?></label>
						<?php else : ?>
							<span class="text-xs text-ink-muted">Xem trước miễn phí</span>
						<?php endif; ?>
					</td>
					<td class="p-3 text-xs text-ink-2"><?php echo esc_html( implode( ' / ', array_map( static fn ( $u ) => $plans[ $u ] ?? $u, (array) $f['unlock'] ) ) ); ?></td>
					<td class="p-3 text-xs text-ink-2 tabular-nums">
						<?php echo esc_html( number_format( (int) $f['hits30'], 0, ',', '.' ) . ' lượt chạm khóa' ); ?><?php if ( (int) $f['guest_hits30'] > 0 ) : ?><span class="text-ink-muted"><?php echo esc_html( ' (' . (int) $f['guest_hits30'] . ' khách)' ); ?></span><?php endif; ?><br>
						<?php echo esc_html( (int) $f['orders30'] . ' đơn · ' . $money( $f['revenue30'] ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div class="flex flex-wrap items-center justify-between gap-3 p-3 border-t border-slate-200">
			<p class="text-xs text-ink-muted"><?php echo esc_html( 'Giá: bộ đề theo tin ' . $money( $d['prices']['pack'] ?? 0 ) . ' · gói ' . (int) ( $d['pass_days'] ?? 30 ) . ' ngày ' . $money( $d['prices']['pass'] ?? 0 ) . ' (đổi giá ở trang tin → Đề cá nhân hóa). Luôn miễn phí: ' . implode( ', ', (array) ( $d['always_free'] ?? array() ) ) . '.' ); ?></p>
			<?php if ( $can ) : ?><button type="submit" class="px-4 py-2 rounded-xl bg-acc hover:bg-acc-deep text-white font-black text-sm">Lưu cấu hình thu phí</button><?php endif; ?>
		</div>
	</form>

	<section class="grid sm:grid-cols-3 gap-3">
		<div class="border border-slate-300 rounded-xl p-3 bg-white"><p class="text-xs text-ink-muted">Gói tháng đang còn hạn</p><p class="text-xl font-black text-ink tabular-nums"><?php echo (int) ( $d['active_passes'] ?? 0 ); ?></p></div>
		<div class="border border-slate-300 rounded-xl p-3 bg-white"><p class="text-xs text-ink-muted">Đơn khác / chưa rõ nguồn (30 ngày)</p><p class="text-xl font-black text-ink tabular-nums"><?php echo esc_html( (int) ( $d['other_orders30']['orders'] ?? 0 ) . ' · ' . $money( $d['other_orders30']['revenue'] ?? 0 ) ); ?></p></div>
		<div class="border border-slate-300 rounded-xl p-3 bg-white"><p class="text-xs text-ink-muted">Đơn hàng</p><p class="text-sm font-bold"><a class="text-acc" href="<?php echo esc_url( cvc_admin_url( 'orders' ) ); ?>">Xem tất cả đơn →</a></p></div>
	</section>

	<?php if ( ! empty( $d['audit'] ) ) : ?>
		<details class="border border-slate-300 rounded-xl p-3 bg-white text-sm">
			<summary class="font-bold cursor-pointer">Nhật ký thay đổi</summary>
			<ul class="mt-2 space-y-1 text-xs text-ink-2">
				<?php foreach ( (array) $d['audit'] as $a ) : ?>
					<li><b><?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( (string) ( $a['at'] ?? '' ) ) ) . ' · ' . (string) ( $a['user'] ?? ( '#' . ( $a['user_id'] ?? '?' ) ) ) ); ?>:</b> <?php echo esc_html( implode( '; ', (array) ( $a['changes'] ?? array() ) ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
	<?php
}
