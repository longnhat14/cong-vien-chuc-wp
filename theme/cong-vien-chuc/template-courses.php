<?php
/**
 * CÔNG VIÊN CHỨC — KHÓA HỌC ENTERPRISE (Executive 3-Column Architecture)
 * URL: /khoa-hoc/ và /khoa-hoc/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Course_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$courses    = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok || empty( $courses ) ) {
	$courses   = CVC_Subpage_Fixtures::get_courses();
	$ok        = true;
	$currentPg = 1;
	$lastPg    = 1;
}

cvc_seo_set_title( 'Khóa Học Enterprise — Học Viện Đào Tạo & Phát Triển Công Vụ' );
cvc_seo_set_description( 'Chương trình đào tạo công chức, viên chức cao cấp chuẩn Bộ Nội Vụ 2026. Lộ trình khoanh vùng trọng tâm & AI Coach 24/7.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_courses_url' );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Khóa Học Enterprise' ),
			)
		);
		?>

		<!-- HERO ACADEMY BANNER -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/30 shadow-2xl space-y-4">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div class="space-y-2 max-w-2xl">
					<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">
						🎓 HỌC VIỆN CÔNG VIÊN CHỨC 2026
					</span>
					<h1 class="text-2xl sm:text-3xl font-black text-white">Khóa Học Enterprise & Lộ Trình Sát Hạch Công Vụ</h1>
					<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
						Tổng hợp 120+ khóa học khoanh vùng trọng tâm Kiến thức chung, Quản lý nhà nước, Văn bản hành chính & Ngoại ngữ chuẩn Bộ Nội Vụ.
					</p>
				</div>
				<div class="flex items-center gap-3 shrink-0">
					<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center space-y-0.5">
						<span class="text-xl font-black text-amber-400 block">12.450+</span>
						<span class="text-[10px] text-slate-400">Học viên đỗ Vòng 1</span>
					</div>
					<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center space-y-0.5">
						<span class="text-xl font-black text-cyan-400 block">94.8%</span>
						<span class="text-[10px] text-slate-400">Tỷ lệ đạt sát hạch</span>
					</div>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — 250px FILTERS) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<!-- Filter Box 1: Danh mục khóa học -->
				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-800 pb-2">
						🔍 Bộ Lọc Danh Mục
					</h3>
					<div class="space-y-1 text-xs">
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl bg-cyan-500/10 text-cyan-300 font-bold border border-cyan-500/30">
							<span>📚 Tất cả khóa học</span>
							<span class="text-[10px] bg-cyan-500 text-navy-950 font-black px-1.5 py-0.5 rounded">120</span>
						</a>
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>🏛️ Kiến thức chung</span>
							<span class="text-[10px] text-slate-400">45</span>
						</a>
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>⚖️ Pháp luật công vụ</span>
							<span class="text-[10px] text-slate-400">32</span>
						</a>
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>💻 Tin học công vụ</span>
							<span class="text-[10px] text-slate-400">18</span>
						</a>
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>🌐 Tiếng Anh công chức</span>
							<span class="text-[10px] text-slate-400">25</span>
						</a>
					</div>
				</div>

				<!-- Filter Box 2: Trình độ sát hạch -->
				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs">
					<h3 class="font-extrabold text-xs text-white uppercase tracking-wider border-b border-slate-800 pb-2">
						🎯 Ngạch Công Chức
					</h3>
					<div class="space-y-2">
						<label class="flex items-center gap-2 text-slate-300 cursor-pointer">
							<input type="checkbox" checked class="rounded accent-amber-500">
							<span>Ngạch Chuyên viên (Loại C)</span>
						</label>
						<label class="flex items-center gap-2 text-slate-300 cursor-pointer">
							<input type="checkbox" class="rounded accent-amber-500">
							<span>Ngạch Chuyên viên chính (Loại B)</span>
						</label>
						<label class="flex items-center gap-2 text-slate-300 cursor-pointer">
							<input type="checkbox" class="rounded accent-amber-500">
							<span>Ngạch Chuyên viên cao cấp</span>
						</label>
					</div>
				</div>

				<!-- Motivational Quote Panel -->
				<div class="p-4 bg-gradient-to-r from-amber-500/10 to-indigo-500/10 rounded-2xl border border-amber-500/30 text-center space-y-1">
					<span class="text-amber-400 text-sm">💡</span>
					<p class="text-[11px] text-slate-300 italic">"Tri thức công vụ là chìa khóa mở đường thăng tiến vững bền."</p>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — COURSES GRID) -->
			<main class="lg:col-span-6 space-y-6">

				<!-- Toolbar Header -->
				<div class="flex items-center justify-between bg-[#0A192F] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Hiển thị <strong class="text-white"><?php echo count( $courses ); ?></strong> khóa học chất lượng cao</span>
					<select class="bg-slate-900 border border-slate-700 text-slate-200 rounded-xl px-3 py-1 focus:outline-none text-xs">
						<option>🔥 Mới nhất 2026</option>
						<option>⭐ Bán chạy nhất</option>
						<option>⚡ Giảm giá nhiều nhất</option>
					</select>
				</div>

				<!-- COURSES GRID -->
				<?php if ( ! $ok || empty( $courses ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có khóa học nào.' ); ?>
				<?php else : ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php foreach ( $courses as $course ) : ?>
							<article class="bg-[#0A192F] border border-slate-800 hover:border-cyan-500/50 rounded-2xl overflow-hidden shadow-lg transition-all hover:-translate-y-1 flex flex-col justify-between">
								<div class="p-5 space-y-3">
									<div class="flex items-center justify-between">
										<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-cyan-500/40 uppercase">
											CHUẨN 2026
										</span>
										<span class="text-amber-400 text-xs font-bold">★ 4.9 (1.280 đánh giá)</span>
									</div>

									<h3 class="font-extrabold text-sm sm:text-base text-white leading-snug line-clamp-2 hover:text-cyan-400 transition-colors">
										<a href="<?php echo esc_url( cvc_course_url( $course['slug'] ?? 'khoa-hoc-on-thi' ) ); ?>">
											<?php echo esc_html( $course['title'] ?? 'Khóa Ôn Thi Công Chức Vòng 1' ); ?>
										</a>
									</h3>

									<p class="text-xs text-slate-400 line-clamp-2">
										<?php echo esc_html( $course['summary'] ?? 'Khoanh vùng trọng tâm Luật Cán bộ công chức, Nghị định 138/2020 và Nghị định 30/2020.' ); ?>
									</p>

									<div class="flex items-center gap-3 text-[11px] text-slate-400 pt-2 border-t border-slate-800">
										<span>📹 24 Bài giảng HD</span>
										<span>⏱️ 18 Giờ video</span>
									</div>
								</div>

								<div class="p-4 bg-[#091726] border-t border-slate-800 flex items-center justify-between">
									<div>
										<span class="text-xs text-slate-400 line-through block">850.000đ</span>
										<span class="text-base font-black text-amber-400 block">599.000đ</span>
									</div>
									<a href="<?php echo esc_url( cvc_course_url( $course['slug'] ?? 'khoa-hoc-on-thi' ) ); ?>" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow transition-transform hover:scale-105">
										Vào Học Ngay &rarr;
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_courses_url' ); ?>
				<?php endif; ?>

			</main>

			<!-- RIGHT SIDEBAR (3 COLS — STICKY STORE & AI DIAGNOSIS) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

				<!-- Priority Store Card -->
				<div class="bg-gradient-to-r from-amber-500/10 via-slate-900 to-indigo-950 border-2 border-amber-500/40 p-5 rounded-2xl space-y-3 shadow-xl">
					<span class="bg-amber-500 text-navy-950 text-[9px] font-black px-2 py-0.5 rounded uppercase">GÓI TRỌN BỘ VIP</span>
					<h3 class="font-extrabold text-sm text-white">Combo Ôn Thi Công Chức Vòng 1 Full 2026</h3>
					<p class="text-xs text-slate-300">Bao gồm 120 bài giảng + 50 đề thi thử PDF + AI Coach 24/7 đồng hành.</p>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="block w-full py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-navy-950 font-black text-xs text-center rounded-xl shadow hover:scale-105 transition-transform">
						Đăng ký Combo — 699K &rarr;
					</a>
				</div>

				<!-- AI Coach Diagnostic Banner -->
				<div class="bg-[#0A192F] border border-cyan-500/40 p-5 rounded-2xl space-y-3 shadow-lg">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider flex items-center gap-1.5">
						✦ AI COURSE RECOMMENDER
					</h3>
					<p class="text-xs text-slate-300">
						AI đề xuất bạn nên ưu tiên khóa <strong class="text-amber-400">Văn bản hành chính Nghị định 30/2020</strong> để tối ưu điểm số.
					</p>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="block text-center py-2 bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 font-bold text-xs rounded-xl hover:bg-cyan-500/30">
						Xem khóa học gợi ý &rarr;
					</a>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
