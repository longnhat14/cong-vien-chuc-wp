<?php
/**
 * CÔNG VIÊN CHỨC — THƯ VIỆN KIẾN THỨC CÔNG VỤ (Executive 3-Column Architecture)
 * URL: /kien-thuc/ và /kien-thuc/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Knowledge_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok      = (bool) ( $result['ok'] ?? false );
$rawdata = $ok ? ( $result['data']['data'] ?? ( $result['data'] ?? array() ) ) : array();
if ( isset( $rawdata['data'] ) && is_array( $rawdata['data'] ) ) {
	$items     = $rawdata['data'];
	$currentPg = (int) ( $rawdata['current_page'] ?? $paged );
	$lastPg    = (int) ( $rawdata['last_page'] ?? 1 );
} else {
	$items     = is_array( $rawdata ) ? $rawdata : array();
	$currentPg = $paged;
	$lastPg    = 1;
}

if ( ! $ok || empty( $items ) ) {
	$items     = CVC_Subpage_Fixtures::get_knowledge_items();
	$ok        = true;
	$currentPg = 1;
	$lastPg    = 1;
}

cvc_seo_set_title( 'Thư Viện Kiến Thức Công Vụ & Chuyên Đề Bồi Dưỡng 2026' );
cvc_seo_set_description( 'Hệ thống chuyên đề kiến thức quản lý nhà nước, pháp luật công chức, văn bản hành chính khoanh vùng thi tuyển Bộ Nội Vụ.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_knowledge_url' );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Thư Viện Kiến Thức' ),
			)
		);
		?>

		<!-- HERO KNOWLEDGE BANNER -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-cyan-500/30 shadow-2xl space-y-4">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div class="space-y-2 max-w-2xl">
					<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">
						📖 KNOWLEDGE BASE 2026
					</span>
					<h1 class="text-2xl sm:text-3xl font-black text-white">Thư Viện Kiến Thức & Chuyên Đề Công Vụ</h1>
					<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
						Tổng hợp 500+ chuyên đề tổng quan pháp luật, hệ thống cơ quan nhà nước, văn thư lưu trữ và kỹ năng thi trắc nghiệm khoanh vùng chuẩn Bộ Nội Vụ.
					</p>
				</div>
				<div class="flex items-center gap-3 shrink-0">
					<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center space-y-0.5">
						<span class="text-xl font-black text-cyan-400 block">500+</span>
						<span class="text-[10px] text-slate-400">Chuyên đề bài viết</span>
					</div>
					<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center space-y-0.5">
						<span class="text-xl font-black text-amber-400 block">100%</span>
						<span class="text-[10px] text-slate-400">Trích dẫn Luật gốc</span>
					</div>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — CATEGORY TREE & FILTERS) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<!-- Filter Card 1: Chuyên đề môn học -->
				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						<i class="fa-solid fa-layer-group"></i> Phân Loại Chuyên Đề
					</h3>
					<div class="space-y-1 text-xs">
						<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl bg-cyan-500/10 text-cyan-300 font-bold border border-cyan-500/30">
							<span>📚 Tất cả chuyên đề</span>
							<span class="text-[10px] bg-cyan-500 text-navy-950 font-black px-1.5 py-0.5 rounded">500</span>
						</a>
						<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>🏛️ Luật Cán bộ, công chức</span>
							<span class="text-[10px] text-slate-400">142</span>
						</a>
						<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>📜 Quản lý nhà nước</span>
							<span class="text-[10px] text-slate-400">98</span>
						</a>
						<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>✍️ Kỹ thuật soạn thảo văn bản</span>
							<span class="text-[10px] text-slate-400">76</span>
						</a>
						<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="flex items-center justify-between p-2 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>🌐 Anh văn công vụ B1/B2</span>
							<span class="text-[10px] text-slate-400">110</span>
						</a>
					</div>
				</div>

				<!-- Filter Card 2: Căn cứ pháp lý gốc -->
				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						<i class="fa-solid fa-gavel"></i> Căn Cứ Pháp Lý Trọng Tâm
					</h3>
					<div class="space-y-2 text-xs">
						<div class="p-2.5 bg-slate-900 rounded-xl border border-slate-800 space-y-1">
							<span class="text-[10px] text-amber-400 font-bold uppercase block">Luật chính thức</span>
							<h4 class="font-bold text-white leading-tight">Luật Cán bộ, Công chức 2008 (Sửa đổi 2019)</h4>
						</div>
						<div class="p-2.5 bg-slate-900 rounded-xl border border-slate-800 space-y-1">
							<span class="text-[10px] text-cyan-400 font-bold uppercase block">Nghị định thi tuyển</span>
							<h4 class="font-bold text-white leading-tight">Nghị định 138/2020/NĐ-CP & NĐ 06/2023</h4>
						</div>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — KNOWLEDGE CARDS GRID) -->
			<main class="lg:col-span-6 space-y-4">
				
				<div class="flex items-center justify-between bg-[#0A192F] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Hiển thị <strong class="text-cyan-400"><?php echo count($items); ?></strong> bài viết chuyên đề</span>
					<div class="flex items-center gap-2">
						<span class="text-slate-400">Sắp xếp:</span>
						<select class="bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:border-cyan-400">
							<option>Mới nhất</option>
							<option>Xem nhiều nhất</option>
							<option>Trọng tâm thi</option>
						</select>
					</div>
				</div>

				<!-- GRID LESSONS -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<?php foreach ( $items as $k_item ) : ?>
						<?php 
						$k_slug  = $k_item['slug'] ?? 'chuyen-de-cong-vu';
						$k_title = $k_item['title'] ?? 'Chuyên đề ôn thi công chức';
						$k_sum   = $k_item['summary'] ?? ($k_item['content'] ?? 'Nội dung kiến thức cốt lõi phân tích chi tiết quy định pháp luật.');
						$k_topic = $k_item['topic']['name'] ?? 'Kiến Thức Chung';
						$k_time  = $k_item['read_time'] ?? '7 phút đọc';
						?>
						<article class="bg-[#0A192F] hover:bg-[#112338] border border-slate-800 hover:border-cyan-500/40 p-5 rounded-2xl space-y-3 shadow-lg transition-all flex flex-col justify-between group">
							<div class="space-y-2">
								<div class="flex items-center justify-between text-[10px]">
									<span class="bg-cyan-500/10 text-cyan-300 font-extrabold px-2.5 py-0.5 rounded-full border border-cyan-500/30">
										📌 <?php echo esc_html($k_topic); ?>
									</span>
									<span class="text-slate-400 flex items-center gap-1">
										<i class="fa-regular fa-clock text-[9px]"></i> <?php echo esc_html($k_time); ?>
									</span>
								</div>

								<h3 class="font-extrabold text-sm text-white group-hover:text-cyan-300 leading-snug line-clamp-2">
									<a href="<?php echo esc_url( cvc_knowledge_item_url( $k_slug ) ); ?>">
										<?php echo esc_html($k_title); ?>
									</a>
								</h3>

								<p class="text-xs text-slate-400 leading-relaxed line-clamp-3">
									<?php echo esc_html( wp_trim_words( $k_sum, 20 ) ); ?>
								</p>
							</div>

							<div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
								<span class="text-amber-400 font-bold text-[11px] flex items-center gap-1">
									<i class="fa-solid fa-star text-[9px]"></i> Trọng tâm Vòng 1
								</span>
								<a href="<?php echo esc_url( cvc_knowledge_item_url( $k_slug ) ); ?>" class="text-cyan-400 group-hover:text-cyan-300 font-black text-xs flex items-center gap-1">
									Đọc tiếp &rarr;
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<!-- Pagination -->
				<?php if ( $lastPg > 1 ) : ?>
					<div class="pt-4">
						<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_knowledge_url' ); ?>
					</div>
				<?php endif; ?>

			</main>

			<!-- RIGHT COLUMN (3 COLS — MONETIZATION & DOWNLOADS) -->
			<aside class="lg:col-span-3 space-y-4">

				<!-- CARD 1: TẢI CẨM NANG KHOANH VÙNG TRỌNG TÂM PDF -->
				<div class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-5 rounded-2xl border border-amber-500/40 space-y-3 shadow-xl text-xs">
					<h3 class="font-black text-amber-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-800 pb-2">
						<i class="fa-solid fa-file-pdf text-amber-400 text-sm"></i> Cẩm Nang Ôn Thi 2026
					</h3>
					<p class="text-slate-300 text-[11px] leading-relaxed">
						Tổng hợp 100 bảng sơ đồ tư duy & mẹo khoanh vùng 60 câu trắc nghiệm Kiến thức chung.
					</p>

					<a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-ubnd-huyen-ea-hleo-2026.pdf' ); ?>" download="Cam-nang-on-thi-cong-chuc-2026.pdf" class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow flex items-center justify-center gap-1.5 transition-transform hover:scale-105">
						<i class="fa-solid fa-download"></i> Tải Cẩm Nang PDF (Miễn Phí)
					</a>
				</div>

				<!-- CARD 2: CTA KHÓA HỌC BỨT PHÁ -->
				<div class="bg-[#0A192F] border border-cyan-500/30 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
					<div class="w-12 h-12 mx-auto rounded-2xl bg-cyan-500/20 text-cyan-300 flex items-center justify-center font-black text-xl border border-cyan-500/40">
						🎓
					</div>
					<h4 class="font-extrabold text-sm text-white">Khóa Học Vòng 1 Cấp Tốc</h4>
					<p class="text-slate-400 text-[11px]">
						Khoanh vùng 100% dạng bài thi thật. Cam kết đậu Vòng 1 hoặc hoàn tiền.
					</p>
					<div class="text-amber-400 font-black text-lg">599.000đ <span class="text-slate-500 text-xs line-through font-normal">890.000đ</span></div>
					<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black text-xs rounded-xl shadow transition-colors">
						Đăng Ký Học Ngay
					</a>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
