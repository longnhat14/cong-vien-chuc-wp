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
$q     = cvc_listing_query();

$service = new CVC_Topic_Service();
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
	$topics    = $rawdata['data'];
	$currentPg = (int) ( $rawdata['current_page'] ?? $paged );
	$lastPg    = (int) ( $rawdata['last_page'] ?? 1 );
} else {
	$topics    = is_array( $rawdata ) ? $rawdata : array();
	$currentPg = $paged;
	$lastPg    = 1;
}

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Chủ đề ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Chủ đề ôn thi theo từng môn, liên kết tới bài kiến thức và câu hỏi luyện tập.' );
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
				array( 'label' => 'Chủ đề ôn thi' ),
			)
		);
		?>

		<?php
		$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : ( isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null );
		cvc_render_listing_hero(
			'Chủ đề ôn thi',
			'Chủ đề ôn thi theo môn',
			'Hệ thống chủ đề theo từng môn thi, liên kết tới bài kiến thức và câu hỏi luyện tập.',
			null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'chủ đề' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
		);
		?>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — ROADMAP STAGES) -->
			<aside class="lg:col-span-3 space-y-4">
				<?php cvc_render_listing_search( cvc_topics_url(), $q, 'Tên chủ đề...', 'Tìm chủ đề' ); ?>
				<?php cvc_render_listing_explore_nav( 'topics' ); ?>
			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — TOPICS & MINDMAPS GRID) -->
			<div class="lg:col-span-6 space-y-6">

				<div class="flex items-center justify-between bg-[#0A192F] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Tổng cộng <strong class="text-white"><?php echo count( $topics ); ?></strong> chủ đề sơ đồ tư duy</span>
				</div>

				<?php if ( ! $ok ) : ?>
					<?php cvc_render_error_state( 'Không tải được danh sách chủ đề, vui lòng thử lại sau.' ); ?>
				<?php elseif ( empty( $topics ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có chủ đề nào.' ); ?>
				<?php else : ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php foreach ( $topics as $topic ) : ?>
							<article class="bg-[#0A192F] border border-slate-800 hover:border-cyan-500/50 rounded-2xl p-5 space-y-3 shadow-lg transition-all hover:-translate-y-1">
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

					<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_topics_url', $q ) ); ?>
				<?php endif; ?>

			</div>

			<!-- RIGHT SIDEBAR (3 COLS — CAREER GOAL RADAR) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

				<div class="bg-[#0A192F] border border-amber-500/40 p-5 rounded-2xl space-y-3 shadow-xl">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">
						🎯 Thiết Lập Mục Tiêu Thăng Tiến
					</h3>
					<p class="text-xs text-slate-300">
						Đặt mục tiêu nâng ngạch hoặc thi tuyển dụng để nhận lộ trình bài giảng cá nhân hóa.
					</p>
					<a href="<?php echo esc_url( cvc_account_url( 'goals' ) ); ?>" class="block w-full text-center py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-navy-950 font-black text-xs rounded-xl shadow">
						Tạo mục tiêu ngay &rarr;
					</a>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
