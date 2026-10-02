<?php
/**
 * CÔNG VIÊN CHỨC — KẾT QUẢ ĐƠN HÀNG (Phase 11)
 * URL: /don-hang/ket-qua/?status=success|failed|invalid&order=CODE
 * Đích đến sau khi PaymentController::vnpayReturn() redirect về.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status = sanitize_key( (string) ( $_GET['status'] ?? '' ) );
$order  = sanitize_text_field( (string) ( $_GET['order'] ?? '' ) );

$is_success = 'success' === $status;

cvc_seo_set_title( $is_success ? 'Thanh toán thành công' : 'Kết quả thanh toán' );
cvc_seo_set_noindex();

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-16">
	<div class="max-w-lg mx-auto px-4 sm:px-6">
		<div class="bg-[#0D1B2A] border border-slate-800 rounded-3xl p-8 shadow-2xl text-center space-y-5">

			<?php if ( $is_success ) : ?>
				<div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/15 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-3xl">
					<i class="fa-solid fa-circle-check"></i>
				</div>
				<h1 class="text-xl font-black text-white">Thanh Toán Thành Công!</h1>
				<p class="text-sm text-slate-300">Đơn hàng của bạn đã được xác nhận. Nội dung đã mua được kích hoạt ngay trong tài khoản của bạn.</p>
			<?php else : ?>
				<div class="w-16 h-16 mx-auto rounded-full bg-rose-500/15 border border-rose-500/40 text-rose-400 flex items-center justify-center text-3xl">
					<i class="fa-solid fa-circle-xmark"></i>
				</div>
				<h1 class="text-xl font-black text-white">Thanh Toán Chưa Thành Công</h1>
				<p class="text-sm text-slate-300">Giao dịch đã bị huỷ hoặc gặp lỗi trong quá trình thanh toán. Bạn chưa bị trừ tiền cho đơn hàng này — vui lòng thử lại hoặc chọn phương thức khác.</p>
			<?php endif; ?>

			<?php if ( '' !== $order ) : ?>
				<p class="text-[11px] text-slate-500 font-mono">Mã đơn hàng: <strong class="text-slate-300"><?php echo esc_html( $order ); ?></strong></p>
			<?php endif; ?>

			<div class="flex flex-col sm:flex-row gap-3 pt-3">
				<?php if ( $is_success ) : ?>
					<a href="<?php echo esc_url( cvc_account_url( 'my-documents' ) ); ?>" class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl shadow-lg text-center">
						Vào Tài Liệu Đã Mua
					</a>
					<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-sm rounded-xl text-center">
						Tiếp Tục Mua Sắm
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl shadow-lg text-center">
						Thử Lại
					</a>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-sm rounded-xl text-center">
						Về Trang Chủ
					</a>
				<?php endif; ?>
			</div>

		</div>
	</div>
</main>

<?php get_footer(); ?>
