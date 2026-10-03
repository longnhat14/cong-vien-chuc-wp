<?php
/**
 * CÔNG VIÊN CHỨC — CỔNG TÌM KIẾM THÔNG MINH AI (Executive 3-Column Architecture)
 * URL: /tim-kiem/?q={keyword}&type={type}&page={page}
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$raw_q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$q     = trim( preg_replace( '/\s+/', ' ', $raw_q ) );

$valid_types = cvc_search_valid_types();
$type        = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'all';
if ( ! in_array( $type, $valid_types, true ) ) {
	$type = 'all';
}

$page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
$page = $page > 0 ? $page : 1;

$has_query = '' !== $q;

$result = null;
if ( $has_query ) {
	$result = ( new CVC_Search_Service() )->search( $q, $type, $page, 12 );
}

$search_data = $result['ok'] ? ($result['data']['data'] ?? array()) : array();
$items       = $search_data['data'] ?? array();
$total_items = (int) ($search_data['total'] ?? count($items));
$currentPg   = (int) ($search_data['current_page'] ?? $page);
$lastPg      = (int) ($search_data['last_page'] ?? 1);

cvc_seo_set_title( $has_query ? sprintf( 'Kết quả tìm kiếm cho "%s"', $q ) : 'Công Cụ Tìm Kiếm Thông Minh AI' );

if ( $has_query ) {
	cvc_seo_set_noindex();
} else {
	cvc_seo_set_description( 'Tìm kiếm khóa học, đề thi trắc nghiệm, thông tin tuyển dụng, kiến thức công vụ và văn bản pháp luật.' );
	cvc_seo_set_canonical( cvc_search_url() );
}

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Tìm kiếm thông minh' ),
			)
		);
		?>

		<!-- SEARCH HEADER HERO BOX -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-cyan-500/40 shadow-2xl space-y-4">
			<div class="max-w-3xl space-y-3">
				<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">
					🔍 AI SEARCH ENGINE 2026
				</span>
				<h1 class="text-2xl sm:text-3xl font-black text-white">
					<?php echo $has_query ? esc_html( sprintf( 'Kết quả cho: "%s"', $q ) ) : 'Tìm Kiếm Toàn Bộ Dữ Liệu Công Vụ'; ?>
				</h1>
				
				<!-- SEARCH INPUT FORM -->
				<form action="<?php echo esc_url( cvc_search_url() ); ?>" method="get" class="flex items-center gap-2 pt-2">
					<input type="hidden" name="type" value="<?php echo esc_attr($type); ?>">
					<div class="relative flex-1">
						<input 
							type="text" 
							name="q" 
							value="<?php echo esc_attr($q); ?>" 
							placeholder="Nhập số hiệu văn bản, từ khóa tuyển dụng, chủ đề thi tuyển..." 
							class="w-full bg-slate-950 border-2 border-cyan-500/50 focus:border-cyan-400 text-white rounded-2xl px-4 py-3 text-xs sm:text-sm font-semibold focus:outline-none shadow-inner"
						>
					</div>
					<button type="submit" class="px-6 py-3 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs sm:text-sm rounded-2xl shadow transition-transform hover:scale-105 shrink-0 flex items-center gap-2">
						<i class="fa-solid fa-magnifying-glass"></i> Tìm Kiếm
					</button>
				</form>
			</div>

			<!-- SEARCH DOMAIN FILTER TABS -->
			<div class="flex items-center gap-2 overflow-x-auto pt-3 border-t border-slate-800/80 text-xs">
				<a href="<?php echo esc_url( cvc_search_url( $q, 'all' ) ); ?>" class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 <?php echo 'all' === $type ? 'bg-cyan-500 text-navy-950 shadow' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'; ?>">
					Tất Cả
				</a>
				<?php foreach ( cvc_search_domains() as $domain_key => $domain ) : ?>
					<a href="<?php echo esc_url( cvc_search_url( $q, $domain_key ) ); ?>" class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 <?php echo $type === $domain_key ? 'bg-cyan-500 text-navy-950 shadow' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'; ?>">
						<?php echo esc_html( $domain['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — SEARCH FILTERS & TIPS) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						<i class="fa-solid fa-lightbulb"></i> Mẹo Tìm Kiếm Chuẩn
					</h3>
					<div class="space-y-2 text-slate-300 leading-relaxed text-[11px]">
						<p>• Nhập chính xác <strong>Số hiệu văn bản</strong> (Ví dụ: <em>138/2020/NĐ-CP</em>) để trích xuất toàn văn nhanh nhất.</p>
						<p>• Nhập tên <strong>Đơn vị tuyển dụng</strong> (Ví dụ: <em>UBND TP.HCM</em>) để xem chỉ tiêu mới nhất.</p>
						<p>• Chọn từng Tab chuyên biệt để thu hẹp phạm vi kết quả.</p>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — RESULTS LISTING) -->
			<main class="lg:col-span-6 space-y-4">

				<?php if ( ! $has_query ) : ?>
					
					<div class="bg-[#0A192F] border border-slate-800 p-8 rounded-3xl text-center space-y-4 shadow-xl">
						<div class="w-16 h-16 mx-auto rounded-full bg-cyan-500/20 text-cyan-300 flex items-center justify-center font-black text-2xl border border-cyan-500/30">
							🔍
						</div>
						<h2 class="text-xl font-black text-white">Bắt Đầu Tìm Kiếm Dữ Liệu Công Vụ</h2>
						<p class="text-xs text-slate-400 max-w-md mx-auto">
							Vui lòng nhập từ khóa tìm kiếm ở khung trên để khám phá 1.000+ đề thi trắc nghiệm, khóa học bồi dưỡng & tin tuyển dụng công chức 2026.
						</p>
					</div>

				<?php elseif ( empty( $items ) ) : ?>

					<div class="bg-[#0A192F] border border-slate-800 p-8 rounded-3xl text-center space-y-4 shadow-xl">
						<div class="w-16 h-16 mx-auto rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-black text-2xl border border-amber-500/30">
							⚠️
						</div>
						<h2 class="text-xl font-black text-white">Không Tìm Thấy Kết Quả Quá Trình Tìm Kiếm</h2>
						<p class="text-xs text-slate-400 max-w-md mx-auto">
							Không tìm thấy dữ liệu phù hợp cho từ khóa "<strong class="text-amber-300"><?php echo esc_html($q); ?></strong>". Thử tìm kiếm với từ khóa ngắn hơn hoặc kiểm tra lỗi chính tả.
						</p>
					</div>

				<?php else : ?>

					<div class="bg-[#0A192F] p-4 rounded-2xl border border-slate-800 flex items-center justify-between text-xs">
						<span class="text-slate-300">Tìm thấy <strong class="text-cyan-400"><?php echo $total_items; ?></strong> kết quả phù hợp</span>
						<span class="text-slate-400">Trang <?php echo $currentPg; ?> / <?php echo $lastPg; ?></span>
					</div>

					<div class="space-y-3">
						<?php foreach ( $items as $s_item ) : ?>
							<?php
							$s_title = $s_item['title'] ?? ($s_item['name'] ?? 'Kết quả tìm kiếm');
							$s_url   = $s_item['url'] ?? '#';
							$s_type  = $s_item['type_label'] ?? 'Dữ liệu';
							$s_desc  = $s_item['summary'] ?? ($s_item['description'] ?? '');
							?>
							<article class="bg-[#0A192F] hover:bg-[#112338] border border-slate-800 hover:border-cyan-500/40 p-5 rounded-2xl space-y-2 shadow-lg transition-all group">
								<div class="flex items-center justify-between text-[10px]">
									<span class="bg-cyan-500/20 text-cyan-300 font-extrabold px-2.5 py-0.5 rounded-full border border-cyan-500/30">
										📌 <?php echo esc_html($s_type); ?>
									</span>
									<span class="text-slate-500">Cập nhật 2026</span>
								</div>

								<h3 class="font-extrabold text-sm text-white group-hover:text-cyan-300 leading-snug">
									<a href="<?php echo esc_url($s_url); ?>">
										<?php echo esc_html($s_title); ?>
									</a>
								</h3>

								<?php if ( ! empty($s_desc) ) : ?>
									<p class="text-xs text-slate-400 leading-relaxed line-clamp-2">
										<?php echo esc_html($s_desc); ?>
									</p>
								<?php endif; ?>

								<div class="pt-2 flex items-center justify-end text-xs">
									<a href="<?php echo esc_url($s_url); ?>" class="text-amber-400 font-bold text-[11px] flex items-center gap-1 group-hover:translate-x-1 transition-transform">
										Xem chi tiết &rarr;
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

				<?php endif; ?>

			</main>

			<!-- RIGHT COLUMN (3 COLS — HOT SEARCHES & RECOMMENDATIONS) -->
			<aside class="lg:col-span-3 space-y-4">

				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						🔥 Từ Khóa Hot Nhất
					</h3>
					<div class="flex flex-wrap gap-1.5">
						<a href="<?php echo esc_url( cvc_search_url('Nghị định 138') ); ?>" class="px-2.5 py-1 bg-slate-900 hover:bg-cyan-500/20 text-slate-300 hover:text-cyan-300 rounded-lg border border-slate-800">Nghị định 138</a>
						<a href="<?php echo esc_url( cvc_search_url('Luật Cán bộ công chức') ); ?>" class="px-2.5 py-1 bg-slate-900 hover:bg-cyan-500/20 text-slate-300 hover:text-cyan-300 rounded-lg border border-slate-800">Luật CBCC</a>
						<a href="<?php echo esc_url( cvc_search_url('Đề thi Kiến thức chung') ); ?>" class="px-2.5 py-1 bg-slate-900 hover:bg-cyan-500/20 text-slate-300 hover:text-cyan-300 rounded-lg border border-slate-800">Đề thi KTC</a>
						<a href="<?php echo esc_url( cvc_search_url('Tuyển dụng UBND') ); ?>" class="px-2.5 py-1 bg-slate-900 hover:bg-cyan-500/20 text-slate-300 hover:text-cyan-300 rounded-lg border border-slate-800">Tuyển dụng 2026</a>
					</div>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
