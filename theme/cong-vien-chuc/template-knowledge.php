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
$q     = cvc_listing_query();

$service = new CVC_Knowledge_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
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

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Kiến thức ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Bài viết kiến thức ôn thi công chức, viên chức theo chủ đề, gắn với văn bản pháp luật liên quan.' );
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
				array( 'label' => 'Kiến thức' ),
			)
		);
		?>

		<?php
		$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : ( isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null );
		cvc_render_listing_hero(
			'Kiến thức',
			'Kiến thức ôn thi công vụ',
			'Bài viết hệ thống kiến thức theo chủ đề, gắn với văn bản pháp luật liên quan.',
			null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'bài viết' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
		);
		?>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — CATEGORY TREE & FILTERS) -->
			<aside class="lg:col-span-3 space-y-4">
				<?php cvc_render_listing_search( cvc_knowledge_url(), $q, 'Từ khóa...', 'Tìm bài kiến thức' ); ?>
				<?php cvc_render_listing_explore_nav( 'knowledge' ); ?>
			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — KNOWLEDGE CARDS GRID) -->
			<div class="lg:col-span-6 space-y-4">
				
				<div class="flex items-center justify-between bg-[#0A192F] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Hiển thị <strong class="text-cyan-400"><?php echo count($items); ?></strong> bài viết chuyên đề</span>
					<div class="flex items-center gap-2">
						<span class="text-slate-400">Sắp xếp:</span>
					</div>
				</div>

				<?php if ( ! $ok ) : ?>
					<?php cvc_render_error_state( 'Không tải được danh sách bài viết kiến thức, vui lòng thử lại sau.' ); ?>
				<?php elseif ( empty( $items ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có bài viết kiến thức nào.' ); ?>
				<?php endif; ?>
				<!-- GRID LESSONS -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<?php foreach ( $items as $k_item ) : ?>
						<?php 
						$k_slug  = (string) ( $k_item['slug'] ?? '' );
						if ( '' === $k_slug ) {
							continue;
						}
						$k_title = (string) ( $k_item['title'] ?? '' );
						$k_sum   = wp_strip_all_tags( (string) ( $k_item['summary'] ?? ( $k_item['content'] ?? '' ) ) );
						$k_topic = (string) ( $k_item['topic']['name'] ?? '' );
						$k_words = str_word_count( wp_strip_all_tags( (string) ( $k_item['content'] ?? '' ) ) );
						$k_time  = $k_words > 0 ? max( 1, (int) ceil( $k_words / 200 ) ) . ' phút đọc' : '';
						?>
						<article class="bg-[#0A192F] hover:bg-[#112338] border border-slate-800 hover:border-cyan-500/40 p-5 rounded-2xl space-y-3 shadow-lg transition-all flex flex-col justify-between group">
							<div class="space-y-2">
								<div class="flex items-center justify-between text-[10px]">
									<?php if ( '' !== $k_topic ) : ?>
										<span class="bg-cyan-500/10 text-cyan-300 font-extrabold px-2.5 py-0.5 rounded-full border border-cyan-500/30"><?php echo esc_html( $k_topic ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $k_time ) : ?>
										<span class="text-slate-400 flex items-center gap-1"><i class="fa-regular fa-clock text-[9px]" aria-hidden="true"></i> <?php echo esc_html( $k_time ); ?></span>
									<?php endif; ?>
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
								<span></span>
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
						<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_knowledge_url', $q ) ); ?>
					</div>
				<?php endif; ?>

			</div>

			<!-- RIGHT COLUMN (3 COLS — MONETIZATION & DOWNLOADS) -->
			<aside class="lg:col-span-3 space-y-4">
				<?php cvc_render_study_sidebar(); ?>
			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
