<?php
/**
 * CÔNG VIÊN CHỨC — THI TRẮC NGHIỆM AI (Executive 3-Column Architecture)
 * URL: /de-thi/ và /de-thi/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Exam_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$exams      = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok || empty( $exams ) ) {
	$exams     = CVC_Subpage_Fixtures::get_exams();
	$ok        = true;
	$currentPg = 1;
	$lastPg    = 1;
}

cvc_seo_set_title( 'Ngân Hàng Đề Thi Trắc Nghiệm AI Công Viên Chức 2026' );
cvc_seo_set_description( 'Hệ thống thi thử trắc nghiệm AI Kiến thức chung, Ngoại ngữ, Tin học chuẩn sát hạch Bộ Nội Vụ.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_exams_url' );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Thi Trắc Nghiệm AI' ),
			)
		);
		?>

		<!-- HERO BANNER -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/40 shadow-2xl space-y-4">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div class="space-y-2 max-w-2xl">
					<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">
						🏛️ SMART EXAM PLATFORM 2026
					</span>
					<h1 class="text-2xl sm:text-3xl font-black text-white">Ngân Hàng Đề Thi Trắc Nghiệm AI Công Viên Chức</h1>
					<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
						36.000+ câu hỏi trắc nghiệm khoanh vùng chuẩn Luật Cán bộ công chức, Nghị định 138/2020 & NĐ 30/2020.
					</p>
				</div>
				<div class="flex items-center gap-3 shrink-0">
					<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center space-y-0.5">
						<span class="text-xl font-black text-amber-400 block">36.000+</span>
						<span class="text-[10px] text-slate-400">Câu hỏi sát hạch</span>
					</div>
					<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center space-y-0.5">
						<span class="text-xl font-black text-cyan-400 block">4 Chế Độ</span>
						<span class="text-[10px] text-slate-400">Học - Luyện - Thật - Mô phỏng</span>
					</div>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — EXAM SUBJECT FILTERS) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">
						📚 Môn Thi Sát Hạch
					</h3>
					<div class="space-y-1">
						<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl bg-amber-500/10 text-amber-300 font-bold border border-amber-500/30">
							<span>Tất cả môn thi</span>
							<span class="text-[10px] bg-amber-500 text-navy-950 font-black px-1.5 py-0.5 rounded">1.200</span>
						</a>
						<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800">
							<span>Kiến thức chung Vòng 1</span>
							<span class="text-[10px] text-slate-400">650</span>
						</a>
						<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800">
							<span>Tiếng Anh Vòng 1</span>
							<span class="text-[10px] text-slate-400">320</span>
						</a>
						<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800">
							<span>Tin học công vụ</span>
							<span class="text-[10px] text-slate-400">230</span>
						</a>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — EXAMS GRID) -->
			<main class="lg:col-span-6 space-y-6">

				<div class="flex items-center justify-between bg-[#0D1B2A] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Tổng cộng <strong class="text-white"><?php echo count( $exams ); ?></strong> bộ đề thi chuẩn sát hạch</span>
				</div>

				<?php if ( ! $ok || empty( $exams ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có bộ đề thi nào.' ); ?>
				<?php else : ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php foreach ( $exams as $exam ) : ?>
							<article class="bg-[#0D1B2A] border border-slate-800 hover:border-amber-500/50 rounded-2xl p-5 space-y-4 shadow-lg transition-all hover:-translate-y-1">
								<div class="flex items-center justify-between border-b border-slate-800 pb-2">
									<span class="bg-amber-500/20 text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded uppercase border border-amber-500/30">
										MÃ ĐỀ: KTC-2026
									</span>
									<span class="text-xs text-slate-400 font-mono">60 Câu · 60 Phút</span>
								</div>

								<h3 class="font-extrabold text-base text-white hover:text-amber-400 transition-colors">
									<a href="<?php echo esc_url( cvc_exam_url( $exam['slug'] ?? 'de-thi' ) ); ?>">
										<?php echo esc_html( $exam['title'] ?? 'Đề Thi Sát Hạch Kiến Thức Chung Số 01' ); ?>
									</a>
								</h3>

								<p class="text-xs text-slate-400 line-clamp-2">
									<?php echo esc_html( $exam['description'] ?? 'Khoanh vùng Luật Cán bộ công chức, Nghị định 138/2020 và Nghị định 30/2020 chuẩn Vòng 1.' ); ?>
								</p>

								<div class="pt-3 border-t border-slate-800 flex items-center justify-between">
									<span class="text-xs text-emerald-400 font-bold">✓ Thách thức AI</span>
									<a href="<?php echo esc_url( cvc_exam_attempt_url( $exam['slug'] ?? 'de-thi' ) ); ?>" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-navy-950 font-black rounded-xl text-xs shadow hover:scale-105 transition-transform">
										Vào Thi Ngay &rarr;
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_exams_url' ); ?>
				<?php endif; ?>

			</main>

			<!-- RIGHT SIDEBAR (3 COLS — HIGH-CONVERSION CTA) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

				<!-- CTA 1: MUA GÓI ÔN THI (PRIMARY CONVERSION) -->
				<div class="bg-gradient-to-br from-amber-500/15 via-slate-900 to-indigo-950 border-2 border-amber-500/60 p-5 rounded-2xl space-y-3 shadow-2xl text-center">
					<span class="bg-amber-500 text-navy-950 text-[9px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">🔥 BÁN CHẠY NHẤT 2026</span>
					<div class="space-y-1">
						<h3 class="text-sm font-black text-white leading-snug">Gói Ôn Thi Cấp Tốc<br>Công Chức Vòng 1</h3>
						<p class="text-[11px] text-slate-300 leading-relaxed">120 bài giảng + 1.200 đề trắc nghiệm + AI Coach 24/7 sát hạch chuẩn 2026</p>
						<div class="flex items-baseline justify-center gap-2 pt-1">
							<span class="text-xl font-black text-amber-400">599.000đ</span>
							<span class="text-xs text-slate-400 line-through">850.000đ</span>
							<span class="text-[10px] text-emerald-400 font-bold">-30%</span>
						</div>
					</div>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow-lg transition-transform hover:scale-[1.03]">
						🚀 Đăng Ký Học Ngay &rarr;
					</a>
					<p class="text-[10px] text-slate-400">✓ Hoàn tiền 100% nếu không đỗ Vòng 1</p>
				</div>

				<!-- CTA 2: MUA BỘ TÀI LIỆU PDF -->
				<div class="bg-[#0D1B2A] border border-cyan-500/40 p-5 rounded-2xl space-y-3 shadow-xl">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						📘 TÀI LIỆU ÔN THI PDF
					</h3>
					<div class="space-y-2 text-xs">
						<div class="flex items-center justify-between p-2.5 bg-[#09243a] rounded-xl border border-[#12415d]">
							<div>
								<span class="text-slate-200 font-bold block">Bộ 50 Đề Thi + Đáp Án</span>
								<span class="text-[10px] text-slate-400">KTC · Ngoại ngữ · Tin học</span>
							</div>
							<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-2.5 py-1.5 bg-amber-500 text-navy-950 font-black rounded-lg text-[11px] hover:bg-amber-400 shadow shrink-0">99K</a>
						</div>
						<div class="flex items-center justify-between p-2.5 bg-[#09243a] rounded-xl border border-[#12415d]">
							<div>
								<span class="text-slate-200 font-bold block">Sơ Đồ Tư Duy Luật CB-CC</span>
								<span class="text-[10px] text-slate-400">Khoanh vùng 100% bẫy thi</span>
							</div>
							<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-2.5 py-1.5 bg-cyan-500 text-navy-950 font-black rounded-lg text-[11px] hover:bg-cyan-400 shadow shrink-0">79K</a>
						</div>
					</div>
					<a href="<?php echo esc_url( home_url('/tai-lieu-phap-luat/') ); ?>" class="block w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl text-center transition-colors">
						Xem toàn bộ tài liệu &rarr;
					</a>
				</div>

				<!-- SOCIAL PROOF STRIP -->
				<div class="bg-[#0D1B2A] border border-slate-800 p-4 rounded-2xl space-y-2 text-xs text-center">
					<div class="grid grid-cols-2 gap-3">
						<div>
							<span class="text-lg font-black text-white block">12.450+</span>
							<span class="text-[10px] text-slate-400">Học viên thành công</span>
						</div>
						<div>
							<span class="text-lg font-black text-cyan-400 block">94.8%</span>
							<span class="text-[10px] text-slate-400">Tỷ lệ đỗ Vòng 1</span>
						</div>
					</div>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
