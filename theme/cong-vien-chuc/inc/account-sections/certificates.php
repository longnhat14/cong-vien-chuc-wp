<?php
/**
 * Chứng chỉ của tôi (Phase 11) - GET /api/my-certificates thật, cấp tự
 * động khi hoàn thành 100% khóa học (xem CertificateService::issueIfEligible,
 * gọi từ CourseLessonProgressController).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result = ( new CVC_Certificate_Service() )->my_certificates( $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$certificates = $result['data']['data'] ?? array();
?>

<div class="space-y-4">
	<p class="text-xs text-slate-400">Chứng chỉ được cấp tự động ngay khi bạn hoàn thành 100% nội dung một khóa học. Mỗi chứng chỉ có mã xác thực công khai để nhà tuyển dụng kiểm tra.</p>

	<?php if ( empty( $certificates ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa có chứng chỉ nào. Hoàn thành một khóa học để nhận chứng chỉ đầu tiên.' ); ?>
	<?php else : ?>
		<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
			<?php foreach ( $certificates as $cert ) : ?>
				<div class="bg-[#0A192F] rounded-2xl border border-slate-800 p-4 space-y-3 shadow-sm">
					<div class="flex items-center gap-3">
						<div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-lg shrink-0">
							<i class="fa-solid fa-award"></i>
						</div>
						<div>
							<span class="text-[10px] font-bold text-emerald-600 uppercase block">Đã hoàn thành</span>
							<h3 class="font-extrabold text-xs text-white leading-snug"><?php echo esc_html( $cert['course']['title'] ?? '' ); ?></h3>
						</div>
					</div>
					<p class="text-[11px] text-slate-400 font-mono">Mã: <?php echo esc_html( $cert['certificate_code'] ?? '' ); ?></p>
					<p class="text-[11px] text-slate-400">Cấp ngày: <?php echo esc_html( isset( $cert['issued_at'] ) ? date_i18n( 'd/m/Y', strtotime( (string) $cert['issued_at'] ) ) : '' ); ?></p>
					<a href="<?php echo esc_url( cvc_certificate_verify_url( $cert['certificate_code'] ?? '' ) ); ?>" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-cyan-600 hover:underline">
						<i class="fa-solid fa-arrow-up-right-from-square"></i> Xem trang xác thực công khai
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
