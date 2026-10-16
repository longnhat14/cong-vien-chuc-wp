<?php
/**
 * Panel: chi tiết đơn hàng (chỉ xem).
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item = (array) ( $args['item'] ?? array() );
?>
<section class="dz-card space-y-4" aria-labelledby="cvc-order-detail">
	<h2 id="cvc-order-detail" class="text-base font-black text-ink">Chi tiết đơn hàng</h2>
	<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
		<div class="flex justify-between gap-3 border-b border-slate-300 py-1"><dt class="text-ink-muted">Người mua</dt><dd class="text-ink"><?php echo esc_html( trim( (string) ( $item['user']['name'] ?? '' ) . ' · ' . (string) ( $item['user']['email'] ?? '' ), ' ·' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-300 py-1"><dt class="text-ink-muted">Số tiền</dt><dd class="text-ink tabular-nums"><?php echo esc_html( cvc_admin_format( $item['total_amount'] ?? null, 'money' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-300 py-1"><dt class="text-ink-muted">Phương thức</dt><dd class="text-ink"><?php echo esc_html( cvc_admin_format( $item['payment_method'] ?? null ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-300 py-1"><dt class="text-ink-muted">Mã giao dịch</dt><dd class="text-ink text-xs"><?php echo esc_html( cvc_admin_format( $item['provider_txn_ref'] ?? null ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-300 py-1"><dt class="text-ink-muted">Ngày tạo</dt><dd class="text-ink"><?php echo esc_html( cvc_admin_format( $item['created_at'] ?? null, 'datetime' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-300 py-1"><dt class="text-ink-muted">Ngày thanh toán</dt><dd class="text-ink"><?php echo esc_html( cvc_admin_format( $item['paid_at'] ?? null, 'datetime' ) ); ?></dd></div>
	</dl>
	<h3 class="text-sm font-black text-ink">Sản phẩm</h3>
	<ul class="divide-y divide-slate-200 text-sm">
		<?php foreach ( (array) ( $item['items'] ?? array() ) as $line ) : ?>
			<li class="py-2 flex justify-between gap-3">
				<span class="text-ink"><?php echo esc_html( (string) ( $line['title'] ?? '' ) ); ?></span>
				<span class="text-ink tabular-nums"><?php echo esc_html( cvc_admin_format( $line['price'] ?? null, 'money' ) ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
