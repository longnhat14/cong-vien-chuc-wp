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

// 27/10/2026: chưa nhập từ khóa thì $result = null (bấm kính lúp ở đầu trang) - tránh cảnh báo PHP.
$search_data = ( is_array( $result ) && ! empty( $result['ok'] ) ) ? (array) ( $result['data']['data'] ?? array() ) : array();
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

<main id="main" class="dz-main">

	<div class="dz-wrap space-y-6">

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
		<section class="dz-herohead">
			<div class="max-w-3xl space-y-3">
				<span class="bg-acc text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">
					Tìm kiếm
				</span>
				<h1 class="text-2xl sm:text-3xl font-black text-ink">
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
							class="w-full border-2 border-acc-line focus:border-acc-2 text-ink rounded-2xl px-4 py-3 text-xs sm:text-sm font-semibold focus:outline-none border-slate-400 bg-white"
						>
					</div>
					<button type="submit" class="px-6 py-3 bg-acc hover:bg-acc-deep text-white font-black text-xs sm:text-sm rounded-2xl transition-transform hover:scale-105 shrink-0 flex items-center gap-2">
						<i class="fa-solid fa-magnifying-glass"></i> Tìm Kiếm
					</button>
				</form>
			</div>

			<!-- SEARCH DOMAIN FILTER TABS -->
			<div class="flex items-center gap-2 overflow-x-auto pt-3 border-t border-slate-300 text-xs">
				<a href="<?php echo esc_url( cvc_search_url( $q, 'all' ) ); ?>" class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 <?php echo 'all' === $type ? 'bg-acc-soft text-acc font-bold border-acc-line' : 'bg-white text-ink-2 hover:bg-acc-soft'; ?>">
					Tất Cả
				</a>
				<?php foreach ( cvc_search_domains() as $domain_key => $domain ) : ?>
					<a href="<?php echo esc_url( cvc_search_url( $q, $domain_key ) ); ?>" class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 <?php echo $type === $domain_key ? 'bg-acc-soft text-acc font-bold border-acc-line' : 'bg-white text-ink-2 hover:bg-acc-soft'; ?>">
						<?php echo esc_html( $domain['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — SEARCH FILTERS & TIPS) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<div class="dz-card space-y-3 text-xs">
					<h3 class="dz-sideh2">
						<i class="fa-solid fa-lightbulb"></i> Mẹo Tìm Kiếm Chuẩn
					</h3>
					<div class="space-y-2 text-ink-2 leading-relaxed text-[11px]">
						<p>• Nhập chính xác <strong>Số hiệu văn bản</strong> (Ví dụ: <em>170/2025/NĐ-CP</em>) để trích xuất toàn văn nhanh nhất.</p>
						<p>• Nhập tên <strong>Đơn vị tuyển dụng</strong> (Ví dụ: <em>UBND TP.HCM</em>) để xem chỉ tiêu mới nhất.</p>
						<p>• Chọn từng Tab chuyên biệt để thu hẹp phạm vi kết quả.</p>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — RESULTS LISTING) -->
			<div class="lg:col-span-6 space-y-4">

				<?php if ( ! $has_query ) : ?>
					
					<div class="dz-panel text-center space-y-4">
						<div class="w-16 h-16 mx-auto rounded-full bg-acc-soft text-acc flex items-center justify-center font-black text-2xl border border-acc-line">
							🔍
						</div>
						<h2 class="text-xl font-black text-ink">Bắt Đầu Tìm Kiếm Dữ Liệu Công Vụ</h2>
						<p class="text-xs text-ink-muted max-w-md mx-auto">
							Vui lòng nhập từ khóa tìm kiếm ở khung trên để tìm đề thi, khóa học, văn bản pháp luật, kiến thức và tin tuyển dụng.
						</p>
					</div>

				<?php elseif ( empty( $items ) ) : ?>

					<div class="dz-panel text-center space-y-4">
						<div class="w-16 h-16 mx-auto rounded-full bg-acc-soft text-acc flex items-center justify-center font-black text-2xl border border-acc-line">
							⚠️
						</div>
						<?php if ( mb_strlen( $q ) < 2 ) : ?>
							<h2 class="text-xl font-black text-ink">Từ khóa quá ngắn</h2>
							<p class="text-sm text-ink-muted max-w-md mx-auto">Nhập ít nhất 2 ký tự, ví dụ "luật viên chức", "73/2025/QH15" hoặc "tuyển dụng giáo viên Bắc Ninh".</p>
						<?php else : ?>
							<h2 class="text-xl font-black text-ink">Không tìm thấy kết quả</h2>
							<p class="text-sm text-ink-muted max-w-md mx-auto">
								Không tìm thấy dữ liệu phù hợp cho từ khóa "<strong class="text-acc"><?php echo esc_html( $q ); ?></strong>". Thử bỏ bớt từ, dùng số hiệu văn bản (ví dụ 172/2025) hoặc kiểm tra chính tả.
							</p>
						<?php endif; ?>
					</div>

				<?php else : ?>

					<div class="dz-card flex items-center justify-between text-xs">
						<span class="text-ink-2">Tìm thấy <strong class="text-acc"><?php echo $total_items; ?></strong> kết quả phù hợp</span>
						<span class="text-ink-muted">Trang <?php echo $currentPg; ?> / <?php echo $lastPg; ?></span>
					</div>

					<div class="space-y-3">
						<?php foreach ( $items as $s_item ) : ?>
							<?php
							$s_link  = cvc_search_result_link( (array) $s_item );
							$s_title = $s_item['title'] ?? ($s_item['name'] ?? 'Kết quả tìm kiếm');
							$s_url   = $s_link['url'];
							$s_type  = $s_link['label'];
							$s_desc  = $s_item['excerpt'] ?? ($s_item['summary'] ?? ($s_item['description'] ?? ''));
							if ( '' === $s_url ) {
								continue; // khong dung duoc duong dan -> khong hien ket qua "chet"
							}
							if ( $s_desc === $s_title || ( '' !== $s_desc && false !== mb_strpos( (string) $s_title, (string) $s_desc ) ) ) {
								$s_desc = ''; // trich yeu da nam trong tieu de
							}
							?>
							<article class="dz-card hover:bg-acc-soft hover:border-acc-2 space-y-2 transition-all group">
								<div class="flex items-center justify-between text-[10px]">
									<span class="bg-acc-soft text-acc font-extrabold px-2.5 py-0.5 rounded-full border border-acc-line">
										📌 <?php echo esc_html($s_type); ?>
									</span>
									<?php if ( $s_link['meta'] ) : ?>
										<span class="text-ink-muted text-right"><?php echo esc_html( implode( ' · ', $s_link['meta'] ) ); ?></span>
									<?php endif; ?>
								</div>

								<h3 class="font-extrabold text-sm text-ink group-hover:text-cyan-300 leading-snug">
									<a href="<?php echo esc_url($s_url); ?>">
										<?php echo esc_html($s_title); ?>
									</a>
								</h3>

								<?php if ( ! empty($s_desc) ) : ?>
									<p class="text-xs text-ink-muted leading-relaxed line-clamp-2">
										<?php echo esc_html($s_desc); ?>
									</p>
								<?php endif; ?>

								<div class="pt-2 flex items-center justify-end text-xs">
									<a href="<?php echo esc_url($s_url); ?>" class="text-acc font-bold text-[11px] flex items-center gap-1 group-hover:translate-x-1 transition-transform">
										Xem chi tiết &rarr;
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php if ( $lastPg > 1 ) : ?>
						<nav class="dz-card flex items-center justify-between text-xs" aria-label="Phân trang kết quả">
							<?php if ( $currentPg > 1 ) : ?>
								<a class="text-acc font-bold" href="<?php echo esc_url( cvc_search_url( $q, $type, $currentPg - 1 ) ); ?>">&larr; Trang trước</a>
							<?php else : ?>
								<span></span>
							<?php endif; ?>
							<span class="text-ink-muted">Trang <?php echo (int) $currentPg; ?> / <?php echo (int) $lastPg; ?></span>
							<?php if ( $currentPg < $lastPg ) : ?>
								<a class="text-acc font-bold" href="<?php echo esc_url( cvc_search_url( $q, $type, $currentPg + 1 ) ); ?>">Trang sau &rarr;</a>
							<?php else : ?>
								<span></span>
							<?php endif; ?>
						</nav>
					<?php endif; ?>

				<?php endif; ?>

			</div>

			<!-- RIGHT COLUMN (3 COLS — HOT SEARCHES & RECOMMENDATIONS) -->
			<aside class="lg:col-span-3 space-y-4">

				<div class="dz-card space-y-3 text-xs">
					<h3 class="dz-sideh2">
						🔥 Từ Khóa Hot Nhất
					</h3>
					<div class="flex flex-wrap gap-1.5">
						<a href="<?php echo esc_url( cvc_search_url('Nghị định 170') ); ?>" class="px-2.5 py-1 bg-white hover:bg-acc-soft text-ink-2 hover:text-acc-2 rounded-lg border border-slate-300">Nghị định 170</a>
						<a href="<?php echo esc_url( cvc_search_url('Luật Cán bộ công chức') ); ?>" class="px-2.5 py-1 bg-white hover:bg-acc-soft text-ink-2 hover:text-acc-2 rounded-lg border border-slate-300">Luật CBCC</a>
						<a href="<?php echo esc_url( cvc_search_url('Đề thi Kiến thức chung') ); ?>" class="px-2.5 py-1 bg-white hover:bg-acc-soft text-ink-2 hover:text-acc-2 rounded-lg border border-slate-300">Đề thi KTC</a>
						<a href="<?php echo esc_url( cvc_search_url('Tuyển dụng UBND') ); ?>" class="px-2.5 py-1 bg-white hover:bg-acc-soft text-ink-2 hover:text-acc-2 rounded-lg border border-slate-300">Tuyển dụng 2026</a>
					</div>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
