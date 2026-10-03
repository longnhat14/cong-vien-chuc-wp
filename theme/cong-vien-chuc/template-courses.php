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
$q     = cvc_listing_query();

$service = new CVC_Course_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$courses    = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Khóa học ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Khóa học ôn thi công chức, viên chức có lộ trình bài giảng, theo dõi tiến độ và chứng chỉ khi hoàn thành.' );
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
				array( 'label' => 'Khóa học' ),
			)
		);
		?>

		<?php
		$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : ( isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null );
		cvc_render_listing_hero(
			'Khóa học',
			'Khóa học ôn thi công chức, viên chức',
			'Khóa học có lộ trình bài giảng, theo dõi tiến độ và chứng chỉ khi hoàn thành.',
			null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'khóa học' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
		);
		?>

		<!-- 3-COLUMN SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — 250px FILTERS) -->
			<aside class="lg:col-span-3 space-y-4">
				<?php cvc_render_listing_search( cvc_courses_url(), $q, 'Tên khóa học...', 'Tìm khóa học' ); ?>
				<?php cvc_render_listing_explore_nav( 'courses' ); ?>
			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — COURSES GRID) -->
			<div class="lg:col-span-6 space-y-6">

				<!-- Toolbar Header -->
				<div class="flex items-center justify-between bg-[#0A192F] p-4 rounded-2xl border border-slate-800 text-xs">
					<span class="text-slate-300">Hiển thị <strong class="text-white"><?php echo count( $courses ); ?></strong> khóa học</span>
				</div>

				<!-- COURSES GRID -->
				<?php if ( ! $ok ) : ?>
					<?php cvc_render_error_state( 'Không tải được danh sách khóa học, vui lòng thử lại sau.' ); ?>
				<?php elseif ( empty( $courses ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có khóa học nào.' ); ?>
				<?php else : ?>
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<?php foreach ( $courses as $course ) : ?>
							<?php
							if ( empty( $course['slug'] ) ) {
								continue;
							}
							$c_price   = (float) ( $course['price'] ?? 0 );
							$c_sale    = isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : null;
							$c_final   = null !== $c_sale ? $c_sale : $c_price;
							$c_lessons = (int) ( $course['published_lessons_count'] ?? 0 );
							$c_minutes = (int) ( $course['duration_minutes'] ?? 0 );
							$c_summary = (string) ( $course['short_description'] ?? ( $course['summary'] ?? '' ) );
							?>
							<article class="bg-[#0A192F] border border-slate-800 hover:border-cyan-500/50 rounded-2xl overflow-hidden shadow-lg transition-colors flex flex-col justify-between">
								<div class="p-5 space-y-3">
									<?php if ( ! empty( $course['is_featured'] ) ) : ?>
										<span class="inline-block bg-amber-500/20 text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-500/40">Nổi bật</span>
									<?php endif; ?>

									<h3 class="font-extrabold text-sm sm:text-base text-white leading-snug line-clamp-2 hover:text-cyan-400 transition-colors">
										<a href="<?php echo esc_url( cvc_course_url( (string) $course['slug'] ) ); ?>"><?php echo esc_html( (string) ( $course['title'] ?? '' ) ); ?></a>
									</h3>

									<?php if ( '' !== $c_summary ) : ?>
										<p class="text-xs text-slate-400 line-clamp-2"><?php echo esc_html( $c_summary ); ?></p>
									<?php endif; ?>

									<div class="flex items-center gap-3 text-[11px] text-slate-400 pt-2 border-t border-slate-800">
										<span><?php echo esc_html( $c_lessons > 0 ? $c_lessons . ' bài học' : 'Đang cập nhật bài học' ); ?></span>
										<?php if ( $c_minutes > 0 ) : ?>
											<span><?php echo esc_html( $c_minutes >= 60 ? round( $c_minutes / 60, 1 ) . ' giờ' : $c_minutes . ' phút' ); ?></span>
										<?php endif; ?>
									</div>
								</div>

								<div class="p-4 bg-[#091726] border-t border-slate-800 flex items-center justify-between">
									<div>
										<?php if ( $c_final <= 0 ) : ?>
											<span class="text-base font-black text-emerald-400 block">Miễn phí</span>
										<?php else : ?>
											<?php if ( null !== $c_sale && $c_sale < $c_price ) : ?>
												<span class="text-xs text-slate-400 line-through block"><?php echo esc_html( cvc_format_vnd( $c_price ) ); ?></span>
											<?php endif; ?>
											<span class="text-base font-black text-amber-400 block"><?php echo esc_html( cvc_format_vnd( $c_final ) ); ?></span>
										<?php endif; ?>
									</div>
									<a href="<?php echo esc_url( cvc_course_url( (string) $course['slug'] ) ); ?>" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow">
										Xem khóa học &rarr;
									</a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_courses_url', $q ) ); ?>
				<?php endif; ?>

			</div>

			<!-- RIGHT SIDEBAR (3 COLS — STICKY STORE & AI DIAGNOSIS) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">
				<?php cvc_render_study_sidebar(); ?>
			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
