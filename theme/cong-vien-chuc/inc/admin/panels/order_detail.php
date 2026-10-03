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
<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-4" aria-labelledby="cvc-order-detail">
	<h2 id="cvc-order-detail" class="text-base font-black text-white">Chi tiết đơn hàng</h2>
	<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Người mua</dt><dd class="text-white"><?php echo esc_html( trim( (string) ( $item['user']['name'] ?? '' ) . ' · ' . (string) ( $item['user']['email'] ?? '' ), ' ·' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Số tiền</dt><dd class="text-white tabular-nums"><?php echo esc_html( cvc_admin_format( $item['total_amount'] ?? null, 'money' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Phương thức</dt><dd class="text-white"><?php echo esc_html( cvc_admin_format( $item['payment_method'] ?? null ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Mã giao dịch</dt><dd class="text-white font-mono text-xs"><?php echo esc_html( cvc_admin_format( $item['provider_txn_ref'] ?? null ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Ngày tạo</dt><dd class="text-white"><?php echo esc_html( cvc_admin_format( $item['created_at'] ?? null, 'datetime' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Ngày thanh toán</dt><dd class="text-white"><?php echo esc_html( cvc_admin_format( $item['paid_at'] ?? null, 'datetime' ) ); ?></dd></div>
	</dl>
	<h3 class="text-sm font-black text-white">Sản phẩm</h3>
	<ul class="divide-y divide-slate-800 text-sm">
		<?php foreach ( (array) ( $item['items'] ?? array() ) as $line ) : ?>
			<li class="py-2 flex justify-between gap-3">
				<span class="text-slate-200"><?php echo esc_html( (string) ( $line['title'] ?? '' ) ); ?></span>
				<span class="text-white tabular-nums"><?php echo esc_html( cvc_admin_format( $line['price'] ?? null, 'money' ) ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
