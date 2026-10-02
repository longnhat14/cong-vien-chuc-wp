<?php
/**
 * CÔNG VIÊN CHỨC — LỘ TRÌNH THĂNG TIẾN (Executive 3-Column Architecture)
 * URL: /chu-de/ và /chu-de/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Topic_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok      = (bool) ( $result['ok'] ?? false );
$rawdata = $ok ? ( $result['data']['data'] ?? ( $result['data'] ?? array() ) ) : array();
if ( isset( $rawdata['data'] ) && is_array( $rawdata['data'] ) ) {
	$topics    = $rawdata['data'];
	$currentPg = (int) ( $rawdata['current_page'] ?? $paged );
	$lastPg    = (int) ( $rawdata['last_page'] ?? 1 );
} else {
	$topics    = is_array( $rawdata ) ? $rawdata : array();
	$currentPg = $paged;
	$lastPg    = 1;
}

if ( ! $ok || empty( $topics ) ) {
	$topics    = CVC_Subpage_Fixtures::get_topics();
	$ok        = true;
	$currentPg = 1;
	$lastPg    = 1;
}

cvc_seo_set_title( 'Lộ Trình Thăng Tiến & Sơ Đồ Tư Duy Công Vụ 2026' );
cvc_seo_set_description( 'Hệ thống sơ đồ tư duy và lộ trình thăng tiến cán bộ, công chức chuẩn quy định Bộ Nội Vụ.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_topics_url' );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Lộ Trình Thăng Tiến' ),
			)
		);
		?>

		<!-- HERO BANNER -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-cyan-500/30 shadow-2xl space-y-4">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div class="space-y-2 max-w-2xl">
					<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">
						🚀 CAREER ROADMAP 2026
					</span>
					<h1 class="text-2xl sm:text-3xl font-black text-white">Lộ Trình Thăng Tiến & Sơ Đồ Tư Duy Công Vụ</h1>
					<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
						Định hướng phát triển năng lực từ Tập sự -> Chuyên viên -> Chuyên viên chính -> Lãnh đạo quản lý với sơ đồ tư duy trực quan.
					</p>
				</div>
				<div class="flex items-center gap-3 shrink-0">
					<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center space-y-0.5">
						<span class="text-xl font-black text-cyan-400 block">5 Cấp Độ</span>
						<span class="text-[10px] text-slate-400">Khung năng lực công vụ</span>
					</div>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — ROADMAP STAGES) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2">
						🌱 Giai Đoạn Thăng Tiến
					</h3>
					<div class="space-y-1.5 text-xs">
						<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="flex items-center gap-2 p-2 rounded-xl bg-cyan-500/10 text-cyan-300 font-bold border border-cyan-500/30">
							<span>1.</span> Ôn thi tuyển dụng Vòng 1 & 2
						</a>
						<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="flex items-center gap-2 p-2 rounded-xl text-slate-300 hover:bg-slate-800">
							<span>2.</span> Tập sự & Chuẩn ngạch Chuyên viên
						</a>
						<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="flex items-center gap-2 p-2 rounded-xl text-slate-300 hover:bg-slate-800">
							<span>3.</span> Thi Nâng ngạch Chuyên viên chính
						</a>
						<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="flex items-center gap-2 p-2 rounded-xl text-slate-300 hover:bg-slate-800">
							<span>4.</span> Quy hoạch Lãnh đạo Cấp phòng
						</a>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — TOPICS & MINDMAPS GRID) -->
			<main class="lg:col-span-6 space-y-6">

				<div class="flex items-center justify-between bg-[#0D1B2A] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Tổng cộng <strong class="text-white"><?php echo count( $topics ); ?></strong> chủ đề sơ đồ tư duy</span>
				</div>

				<?php if ( ! $ok || empty( $topics ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có chủ đề nào.' ); ?>
				<?php else : ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php foreach ( $topics as $topic ) : ?>
							<article class="bg-[#0D1B2A] border border-slate-800 hover:border-cyan-500/50 rounded-2xl p-5 space-y-3 shadow-lg transition-all hover:-translate-y-1">
								<div class="flex items-center justify-between">
									<span class="text-2xl">🧠</span>
									<span class="text-[10px] font-extrabold bg-slate-800 text-slate-300 px-2 py-0.5 rounded">
										SƠ ĐỒ TƯ DUY
									</span>
								</div>
								<h3 class="font-extrabold text-base text-white hover:text-cyan-400 transition-colors">
									<a href="<?php echo esc_url( cvc_topic_url( $topic['slug'] ?? 'chu-de' ) ); ?>">
										<?php echo esc_html( $topic['name'] ?? 'Luật Cán bộ, công chức hợp nhất' ); ?>
									</a>
								</h3>
								<p class="text-xs text-slate-400 line-clamp-2">
									<?php echo esc_html( $topic['description'] ?? 'Hệ thống hóa toàn bộ điều khoản luật dưới dạng nhánh tư duy dễ nhớ.' ); ?>
								</p>
								<div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
									<span class="text-slate-400">18 Nhánh kiến thức</span>
									<a href="<?php echo esc_url( cvc_topic_url( $topic['slug'] ?? 'chu-de' ) ); ?>" class="text-cyan-400 font-extrabold hover:underline">
										Khám phá &rarr;
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_topics_url' ); ?>
				<?php endif; ?>

			</main>

			<!-- RIGHT SIDEBAR (3 COLS — CAREER GOAL RADAR) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

				<div class="bg-[#0D1B2A] border border-amber-500/40 p-5 rounded-2xl space-y-3 shadow-xl">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">
						🎯 Thiết Lập Mục Tiêu Thăng Tiến
					</h3>
					<p class="text-xs text-slate-300">
						Đặt mục tiêu nâng ngạch hoặc thi tuyển dụng để nhận lộ trình bài giảng cá nhân hóa.
					</p>
					<a href="<?php echo esc_url( home_url('/tai-khoan/?section=goals') ); ?>" class="block w-full text-center py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-navy-950 font-black text-xs rounded-xl shadow">
						Tạo mục tiêu ngay &rarr;
					</a>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
