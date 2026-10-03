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
$q     = cvc_listing_query();

$service = new CVC_Exam_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$exams      = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Đề thi thử trắc nghiệm công chức, viên chức' );
cvc_seo_set_description( 'Làm đề thi thử trắc nghiệm công chức, viên chức: chấm điểm ngay khi nộp, xem đáp án và giải thích từng câu.' );
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
				array( 'label' => 'Đề thi thử' ),
			)
		);
		?>

		<?php
		$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : ( isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null );
		cvc_render_listing_hero(
			'Đề thi thử',
			'Đề thi trắc nghiệm ôn thi công chức, viên chức',
			'Làm đề có đồng hồ, chấm điểm ngay khi nộp và xem giải thích từng câu.',
			null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'đề thi' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
		);
		?>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — EXAM SUBJECT FILTERS) -->
			<aside class="lg:col-span-3 space-y-4">
				<?php cvc_render_listing_search( cvc_exams_url(), $q, 'Tên đề thi...', 'Tìm đề thi' ); ?>
				<?php cvc_render_listing_explore_nav( 'exams' ); ?>
			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — EXAMS GRID) -->
			<div class="lg:col-span-6 space-y-6">

				<div class="flex items-center justify-between bg-[#0A192F] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Tổng cộng <strong class="text-white"><?php echo count( $exams ); ?></strong> bộ đề thi chuẩn sát hạch</span>
				</div>

				<?php if ( ! $ok ) : ?>
					<?php cvc_render_error_state( 'Không tải được danh sách đề thi, vui lòng thử lại sau.' ); ?>
				<?php elseif ( empty( $exams ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có đề thi nào.' ); ?>
				<?php else : ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php foreach ( $exams as $exam ) : ?>
							<article class="bg-[#0A192F] border border-slate-800 hover:border-amber-500/50 rounded-2xl p-5 space-y-4 shadow-lg transition-all hover:-translate-y-1">
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
									<?php echo esc_html( (string) ( $exam['description'] ?? '' ) ); ?>
								</p>

								<div class="pt-3 border-t border-slate-800 flex items-center justify-between">
									<span class="text-xs text-slate-400"><?php echo ! empty( $exam['questions_count'] ) ? esc_html( (int) $exam['questions_count'] . ' câu' ) : ''; ?><?php echo ! empty( $exam['duration_minutes'] ) ? esc_html( ' · ' . (int) $exam['duration_minutes'] . ' phút' ) : ''; ?></span>
									<a href="<?php echo esc_url( cvc_exam_url( (string) ( $exam['slug'] ?? '' ) ) ); ?>" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-navy-950 font-black rounded-xl text-xs shadow hover:scale-105 transition-transform">
										Vào Thi Ngay &rarr;
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_exams_url', $q ) ); ?>
				<?php endif; ?>

			</div>

			<!-- RIGHT SIDEBAR (3 COLS — HIGH-CONVERSION CTA) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">
				<?php cvc_render_study_sidebar(); ?>
			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
