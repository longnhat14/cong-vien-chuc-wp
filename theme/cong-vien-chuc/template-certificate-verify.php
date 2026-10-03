<?php
/**
 * CÔNG VIÊN CHỨC — XÁC THỰC CHỨNG CHỈ (Phase 11, công khai)
 * URL: /xac-thuc-chung-chi/ (form nhập mã) và /xac-thuc-chung-chi/{code}/ (kết quả).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$code = sanitize_text_field( (string) get_query_var( 'cvc_certificate_code' ) );
if ( '' === $code && isset( $_GET['code'] ) ) {
	$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );
}

$result = '' !== $code ? ( new CVC_Certificate_Service() )->verify( $code ) : null;
$data   = ( $result && $result['ok'] ) ? ( $result['data']['data'] ?? array() ) : array();

cvc_seo_set_title( 'Xác Thực Chứng Chỉ — Công Viên Chức' );
cvc_seo_set_description( 'Tra cứu tính hợp lệ của chứng chỉ hoàn thành khóa học do Công Viên Chức cấp.' );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-10">
	<div class="max-w-lg mx-auto px-4 sm:px-6 space-y-6">

		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Xác thực chứng chỉ' ),
			)
		);
		?>

		<div class="bg-[#0A192F] border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
			<div class="text-center space-y-1.5">
				<div class="w-12 h-12 mx-auto rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl border border-amber-500/30">
					<i class="fa-solid fa-award"></i>
				</div>
				<h1 class="text-lg font-black text-white">Xác Thực Chứng Chỉ</h1>
				<p class="text-xs text-slate-400">Nhập mã chứng chỉ in trên văn bằng để kiểm tra tính hợp lệ.</p>
			</div>

			<form method="get" action="<?php echo esc_url( cvc_certificate_verify_url() ); ?>" class="flex gap-2">
				<input type="text" name="code" value="<?php echo esc_attr( $code ); ?>" placeholder="VD: CVC-CERT-A1B2C3D4E5"
					class="flex-1 px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-sm text-white font-mono placeholder:text-slate-600 focus:border-amber-400 focus:outline-none">
				<button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow">
					Tra Cứu
				</button>
			</form>

			<?php if ( '' !== $code ) : ?>
				<?php if ( $result && $result['ok'] && ! empty( $data ) ) : ?>
					<div class="bg-emerald-500/10 border border-emerald-500/40 rounded-2xl p-5 space-y-3">
						<span class="inline-flex items-center gap-1.5 text-emerald-400 font-black text-xs uppercase">
							<i class="fa-solid fa-circle-check"></i> Chứng chỉ hợp lệ
						</span>
						<div class="text-sm text-white space-y-1.5">
							<p><span class="text-slate-400">Người được cấp:</span> <strong><?php echo esc_html( $data['holder_name'] ?? '' ); ?></strong></p>
							<p><span class="text-slate-400">Khóa học:</span> <strong><?php echo esc_html( $data['course_title'] ?? '' ); ?></strong></p>
							<p><span class="text-slate-400">Ngày cấp:</span> <strong><?php echo esc_html( $data['issued_at'] ?? '' ); ?></strong></p>
							<p><span class="text-slate-400">Mã chứng chỉ:</span> <strong class="font-mono text-amber-400"><?php echo esc_html( $data['certificate_code'] ?? '' ); ?></strong></p>
						</div>
					</div>
				<?php else : ?>
					<div class="bg-rose-500/10 border border-rose-500/40 rounded-2xl p-5">
						<span class="inline-flex items-center gap-1.5 text-rose-400 font-black text-xs uppercase">
							<i class="fa-solid fa-circle-xmark"></i> Không tìm thấy chứng chỉ với mã này
						</span>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

	</div>
</main>

<?php get_footer(); ?>
