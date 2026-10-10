<?php
/**
 * CÔNG VIÊN CHỨC — CỔNG TUYỂN DỤNG CÔNG VỤ & 3.000+ XÃ PHƯỜNG TOÀN QUỐC (Executive 3-Column Architecture)
 * URL: /tuyen-dung/ và /tuyen-dung/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;
$q     = cvc_listing_query();

$service = new CVC_Recruitment_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
	)
);

$ok           = (bool) $result['ok'];
$pagination   = $ok ? ( $result['data']['data'] ?? array() ) : array();
$recruitments = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg    = (int) ( $pagination['current_page'] ?? $paged );
$lastPg       = (int) ( $pagination['last_page'] ?? 1 );

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

// Dot da het han nop nhung dang to chuc thi / vua co ket qua - thi sinh theo doi (chi trang 1, khong tim kiem).
$cvc_examining = array();
if ( 1 === $paged && '' === $q ) {
	$cvc_ex_res    = $service->list( array( 'stage' => 'examining', 'per_page' => 10 ) );
	$cvc_examining = ! empty( $cvc_ex_res['ok'] ) ? (array) ( $cvc_ex_res['data']['data']['data'] ?? array() ) : array();
}

cvc_seo_set_title( 'Tin tuyển dụng công chức, viên chức' );
cvc_seo_set_description( 'Tin tuyển dụng công chức, viên chức kèm vị trí, chỉ tiêu, hạn nộp hồ sơ và tài liệu ôn thi theo từng đợt.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_recruitments_url' );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Tuyển dụng' ),
			)
		);
		?>

		<?php
		$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : ( isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null );
		cvc_render_listing_hero(
			'Tuyển dụng',
			'Tin tuyển dụng công chức, viên chức',
			'Thông báo tuyển dụng kèm vị trí, chỉ tiêu, hạn nộp hồ sơ và tài liệu ôn thi theo từng đợt.',
			null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'tin tuyển dụng' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
		);
		?>

		<!-- 3-COLUMN EXECUTIVE SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — PROVINCE & AGENCY FILTERS) -->
			<aside class="lg:col-span-3 space-y-5">
				<?php cvc_render_listing_search( cvc_recruitments_url(), $q, 'Tên cơ quan, địa phương...', 'Tìm tin tuyển dụng' ); ?>
				<?php cvc_render_listing_explore_nav( 'recruitments' ); ?>
			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — RECRUITMENTS CARDS) -->
			<div class="lg:col-span-6 space-y-4">

				<div class="flex items-center justify-between bg-navy-950 p-4 rounded-2xl border border-slate-800 text-xs shadow-md">
					<span class="text-slate-300">Đang hiển thị <strong class="text-emerald-400 font-extrabold" id="countDisplay"><?php echo count( $recruitments ); ?></strong> tin tuyển dụng<?php echo isset( $pagination['total'] ) ? ' / ' . esc_html( number_format_i18n( (int) $pagination['total'] ) ) . ' tin' : ''; ?></span>
				</div>

				<?php if ( ! $ok ) : ?>
					<?php cvc_render_error_state( 'Không tải được danh sách tin tuyển dụng, vui lòng thử lại sau.' ); ?>
				<?php elseif ( empty( $recruitments ) ) : ?>
					<?php cvc_render_empty_state( empty( $cvc_examining ) ? 'Chưa có tin tuyển dụng nào.' : 'Hiện chưa có đợt tuyển dụng nào đang nhận hồ sơ. Các đợt đang tổ chức thi ở bên dưới.' ); ?>
				<?php endif; ?>
				<div id="recruitmentListContainer" class="space-y-4">
					<?php foreach ( $recruitments as $rec ) : ?>
						<?php
						$slug         = is_string( $rec['slug'] ?? null ) ? $rec['slug'] : '';
						$title        = is_string( $rec['title'] ?? null ) ? $rec['title'] : 'Thông báo tuyển dụng';
						
						$agency_raw   = $rec['agency'] ?? null;
						$agency_name  = is_array( $agency_raw ) ? (string) ( $agency_raw['name'] ?? '' ) : ( is_string( $agency_raw ) ? $agency_raw : '' );

						$type_labels  = array( 'civil_servant' => 'Công chức', 'public_employee' => 'Viên chức' );
						$rec_type     = $type_labels[ (string) ( $rec['recruitment_type'] ?? '' ) ] ?? 'Tuyển dụng';
						$quota        = ! empty( $rec['total_positions'] ) ? ( (int) $rec['total_positions'] . ' chỉ tiêu' ) : 'Chưa công bố';

						$dates_raw    = is_array( $rec['dates'] ?? null ) ? $rec['dates'] : array();
						$deadline_raw = (string) ( $dates_raw['application_deadline'] ?? '' );
						$deadline     = '' !== $deadline_raw ? cvc_format_date_vn( $deadline_raw ) : 'Chưa công bố';
						$is_open      = '' === $deadline_raw || strtotime( $deadline_raw . ' 23:59:59' ) >= current_time( 'timestamp' );
						$status       = $is_open ? 'Đang nhận hồ sơ' : 'Đã hết hạn nộp';

						$summary      = is_string( $rec['summary'] ?? null ) ? $rec['summary'] : '';
						$positions    = is_array( $rec['positions'] ?? null ) ? $rec['positions'] : array();
						$attachments  = is_array( $rec['attachments'] ?? null ) ? $rec['attachments'] : array();
						$province_raw = $rec['province'] ?? null;
						$province_tag = is_array( $province_raw ) ? (string) ( $province_raw['name'] ?? '' ) : ( is_string( $province_raw ) ? $province_raw : '' );
						?>
						<article class="bg-navy-950 border border-slate-800 hover:border-emerald-500/50 p-5 rounded-3xl space-y-4 shadow-xl transition-all duration-300 group hover:shadow-2xl hover:shadow-emerald-500/5 item-recruitment-card" data-title="<?php echo esc_attr(mb_strtolower((string)$title)); ?>" data-agency="<?php echo esc_attr(mb_strtolower((string)$agency_name)); ?>" data-province="<?php echo esc_attr(mb_strtolower((string)$province_tag)); ?>">
							
							<div class="flex items-start justify-between gap-3 border-b border-slate-800/80 pb-3">
								<div class="space-y-1">
									<div class="flex items-center gap-2 flex-wrap text-[11px]">
										<span class="bg-emerald-500/20 text-emerald-300 font-bold px-2.5 py-0.5 rounded-full border border-emerald-500/30">
											📌 <?php echo esc_html( $rec_type ); ?>
										</span>
										<span class="bg-slate-800 text-slate-300 font-semibold px-2 py-0.5 rounded-full text-[10px]">
											📍 <?php echo esc_html( $agency_name ); ?>
										</span>
									</div>
									<h2 class="text-base font-extrabold text-white group-hover:text-emerald-300 leading-snug transition-colors">
										<a href="<?php echo esc_url( cvc_recruitment_url( $slug ) ); ?>">
											<?php echo esc_html( $title ); ?>
										</a>
									</h2>
								</div>
								<span class="bg-amber-500/20 text-amber-300 border border-amber-400/40 text-[10px] font-black px-2.5 py-1 rounded-xl shrink-0 uppercase shadow-sm">
									<?php echo esc_html( $status ); ?>
								</span>
							</div>

							<p class="text-xs text-slate-300 leading-relaxed font-light line-clamp-3">
								<?php echo esc_html( wp_trim_words( $summary, 30 ) ); ?>
							</p>

							<div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 border-t border-slate-800/80 text-xs">
								<div class="flex items-center gap-4 text-slate-400 text-[11px]">
									<span>🎯 Chỉ tiêu: <strong class="text-emerald-400 font-bold"><?php echo esc_html( $quota ); ?></strong></span>
									<span>⏳ Hạn nộp: <strong class="text-amber-400 font-mono font-bold"><?php echo esc_html( $deadline ); ?></strong></span>
								</div>

								<div class="flex items-center gap-2">
									<a href="<?php echo esc_url( cvc_recruitment_url( $slug ) ); ?>" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-navy-950 font-black text-xs rounded-xl shadow-lg transition-transform hover:scale-105">
										Xem chi tiết &rarr;
									</a>
								</div>
							</div>

						</article>
					<?php endforeach; ?>
				</div>

				<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_recruitments_url', $q ) ); ?>

				<?php if ( ! empty( $cvc_examining ) ) : ?>
					<section class="space-y-3 pt-2" aria-labelledby="cvc-examining">
						<h2 id="cvc-examining" class="text-sm font-black text-amber-300 uppercase tracking-wider"><i class="fa-solid fa-hourglass-half mr-1.5" aria-hidden="true"></i>Đợt tuyển dụng đang tổ chức thi</h2>
						<p class="text-xs text-slate-400">Đã hết hạn nhận phiếu - dành cho thí sinh đã nộp theo dõi danh sách, triệu tập, tài liệu ôn tập, lịch thi và kết quả.</p>
						<?php foreach ( $cvc_examining as $rec ) : ?>
							<a href="<?php echo esc_url( cvc_recruitment_url( (string) ( $rec['slug'] ?? '' ) ) ); ?>" class="block bg-navy-950 border border-slate-800 hover:border-amber-400/50 p-4 rounded-2xl space-y-1.5">
								<span class="flex flex-wrap items-center gap-2 text-[10px]">
									<span class="font-black px-2 py-0.5 rounded-full bg-amber-400 text-navy-950 uppercase"><?php echo esc_html( (string) ( $rec['stage_label'] ?? 'Đang tổ chức thi' ) ); ?></span>
									<?php if ( ! empty( $rec['doc_number'] ) ) : ?><span class="font-mono text-slate-400"><?php echo esc_html( (string) $rec['doc_number'] ); ?></span><?php endif; ?>
									<span class="text-slate-400"><?php echo esc_html( (string) ( $rec['agency']['name'] ?? '' ) ); ?></span>
								</span>
								<span class="block text-sm font-extrabold text-white leading-snug"><?php echo esc_html( (string) ( $rec['title'] ?? '' ) ); ?></span>
								<span class="block text-[11px] text-slate-400"><?php echo ! empty( $rec['total_positions'] ) ? esc_html( (int) $rec['total_positions'] . ' chỉ tiêu · ' ) : ''; ?>Hết hạn nộp <?php echo esc_html( ! empty( $rec['dates']['application_deadline'] ) ? cvc_format_date_vn( (string) $rec['dates']['application_deadline'] ) : '—' ); ?></span>
							</a>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

			</div>

				<!-- RIGHT SIDEBAR (3 COLS — MONETIZATION & DOWNLOAD HANDBOOK) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">
				<?php cvc_render_study_sidebar(); ?>
			</aside>

		</div>

	</div>

</main>



<?php get_footer(); ?>
