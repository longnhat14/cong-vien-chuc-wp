<?php
/**
 * CÔNG VIÊN CHỨC — EXECUTIVE ACCOUNT DASHBOARD (3-Column Architecture)
 * URL: /tai-khoan/{section}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();
$token = cvc_auth_token();
$user  = cvc_current_user();

$sections     = cvc_account_sections();
$query_sec    = get_query_var( 'cvc_account_section' ) ?: ( $_GET['section'] ?? '' );
$section_slug = sanitize_key( (string) $query_sec );

if ( 'my-courses' === $section_slug ) {
	$section_slug = 'khoa-hoc-cua-toi';
}
if ( 'my-documents' === $section_slug ) {
	$section_slug = 'tai-lieu-da-mua';
}

$section = $sections[ $section_slug ] ?? null;

if ( null === $section ) {
	$section_slug = 'tong-quan';
	$section      = 'overview';
}

cvc_seo_set_noindex();

$section_titles = array(
	'overview'             => 'Tổng quan',
	'profile'              => 'Hồ sơ cá nhân',
	'goals'                => 'Mục tiêu ôn thi',
	'learning-path'        => 'Lộ trình học tập',
	'bookmarks'            => 'Đã đánh dấu',
	'exam-history'         => 'Lịch sử làm bài',
	'my-courses'           => 'Khóa học của tôi',
	'my-documents'         => 'Tài liệu đã mua',
	'recommendations'      => 'Gợi ý cho bạn',
	'recruitment-matches'  => 'Việc làm phù hợp',
	'notifications'        => 'Thông báo',
	'certificates'         => 'Chứng chỉ của tôi',
	'security'             => 'Bảo mật & đăng nhập',
);

$section_fa_icons = array(
	'overview'             => 'fa-chart-pie',
	'profile'              => 'fa-user-gear',
	'goals'                => 'fa-bullseye',
	'learning-path'        => 'fa-route',
	'bookmarks'            => 'fa-bookmark',
	'exam-history'         => 'fa-clock-rotate-left',
	'my-courses'           => 'fa-graduation-cap',
	'my-documents'         => 'fa-folder-open',
	'recommendations'      => 'fa-wand-magic-sparkles',
	'recruitment-matches'  => 'fa-briefcase',
	'notifications'        => 'fa-bell',
	'certificates'         => 'fa-award',
	'security'             => 'fa-shield-halved',
);

cvc_seo_set_title( $section_titles[ $section ] . ' — Dashboard Công Viên Chức' );

function cvc_status_label( string $status ): string {
	$map = array(
		'draft'       => 'Nháp',
		'active'      => 'Đang theo đuổi',
		'paused'      => 'Tạm dừng',
		'completed'   => 'Hoàn thành',
		'archived'    => 'Đã lưu trữ',
		'new'         => 'Mới',
		'seen'        => 'Đã xem',
		'dismissed'   => 'Đã bỏ qua',
		'interested'  => 'Quan tâm',
	);

	return $map[ $status ] ?? $status;
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
				array( 'label' => 'Tài khoản cá nhân' ),
				array( 'label' => $section_titles[ $section ] ),
			)
		);
		?>

		<!-- EXECUTIVE USER DASHBOARD BANNER -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/40 shadow-2xl space-y-4">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div class="flex items-center gap-4">
					<div class="w-14 h-14 rounded-2xl bg-amber-500 text-navy-950 font-black text-xl flex items-center justify-center shadow-xl border-2 border-amber-400 shrink-0">
						<?php echo esc_html( mb_substr( $user['name'] ?? 'U', 0, 1 ) ); ?>
					</div>
					<div class="space-y-1">
						<div class="flex items-center gap-2">
							<h1 class="text-xl sm:text-2xl font-black text-white">
								Xin chào, <?php echo esc_html( $user['name'] ?? $user['email'] ?? 'Học viên' ); ?>
							</h1>
						</div>
						<p class="text-xs text-slate-300">
							Mã học viên: <strong class="text-amber-400 font-mono">CVC-<?php echo sprintf('%05d', (int)($user['id'] ?? 1)); ?></strong> &middot; Email: <?php echo esc_html($user['email'] ?? ''); ?>
						</p>
					</div>
				</div>

				<div class="flex items-center gap-3 shrink-0">
					<a href="<?php echo esc_url( cvc_logout_url() ); ?>" class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/40 font-bold text-xs rounded-xl shadow transition-colors flex items-center gap-1.5">
						<i class="fa-solid fa-right-from-bracket"></i> Đăng Xuất
					</a>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN DASHBOARD LAYOUT -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT SIDEBAR (3 COLS — DASHBOARD MENU) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<div class="bg-[#0A192F] border border-slate-800 p-4 rounded-2xl space-y-2 shadow-lg text-xs">
					<h3 class="font-extrabold text-[11px] text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						<i class="fa-solid fa-bars"></i> Danh Mục Quản Lý
					</h3>
					<nav class="space-y-1">
						<?php foreach ( $section_titles as $key => $label ) : 
							$fa_icon = $section_fa_icons[$key] ?? 'fa-circle';
							$is_active = $key === $section;
							$link_class = $is_active 
								? 'bg-amber-500/10 text-amber-400 font-extrabold border border-amber-500/40 shadow-sm' 
								: 'text-slate-300 hover:bg-slate-800/80 hover:text-white';
						?>
							<a href="<?php echo esc_url( cvc_account_url( $key ) ); ?>" class="flex items-center gap-2.5 p-2.5 rounded-xl transition-all <?php echo $link_class; ?>">
								<i class="fa-solid <?php echo $fa_icon; ?> w-4 text-center text-xs opacity-90"></i>
								<span><?php echo esc_html( $label ); ?></span>
							</a>
						<?php endforeach; ?>
					</nav>
				</div>

			</aside>

			<!-- CENTER MAIN CONTENT (6 COLS — SECTION DYNAMIC RENDER) -->
			<main class="lg:col-span-6 space-y-4">
				
				<?php cvc_render_notice(); ?>

				<div class="bg-[#0A192F] border border-slate-800 p-6 rounded-3xl space-y-4 shadow-xl">
					<div class="flex items-center justify-between border-b border-slate-800 pb-3">
						<h2 class="text-base font-black text-white flex items-center gap-2">
							<i class="fa-solid <?php echo $section_fa_icons[$section] ?? 'fa-circle'; ?> text-amber-400"></i>
							<?php echo esc_html( $section_titles[ $section ] ); ?>
						</h2>
					</div>

					<div class="cvc-account-section-wrapper text-xs space-y-4">
						<?php require get_theme_file_path( '/inc/account-sections/' . $section . '.php' ); ?>
					</div>
				</div>

			</main>

			<!-- RIGHT SIDEBAR (3 COLS — STATS & SUPPORT) -->
			<aside class="lg:col-span-3 space-y-4">

				<?php
				/*
				 * Chỉ số học tập THẬT từ GET /api/exam-progress (trước đây là
				 * số cứng 85% / 48/60 cho mọi tài khoản).
				 */
				$progress_result = null !== $token ? ( new CVC_Exam_Attempt_Service() )->progress( $token ) : array( 'ok' => false );
				$progress_rows   = $progress_result['ok'] ? ( $progress_result['data']['data']['data'] ?? array() ) : array();
				$attempted_exams = count( $progress_rows );
				$completed_rows  = array_filter( $progress_rows, fn ( $r ) => (int) ( $r['completed_count'] ?? 0 ) > 0 );
				$completed_exams = count( $completed_rows );
				$best_values     = array_filter( array_map( fn ( $r ) => isset( $r['best_percentage'] ) ? (float) $r['best_percentage'] : null, $completed_rows ), fn ( $v ) => null !== $v );
				$avg_best        = ! empty( $best_values ) ? (int) round( array_sum( $best_values ) / count( $best_values ) ) : null;
				$completion_pct  = $attempted_exams > 0 ? (int) round( $completed_exams * 100 / $attempted_exams ) : 0;
				?>
				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						<i class="fa-solid fa-chart-line"></i> Chỉ Số Học Tập
					</h3>
					<?php if ( ! $progress_result['ok'] ) : ?>
						<p class="text-slate-400">Chưa tải được chỉ số học tập.</p>
					<?php elseif ( 0 === $attempted_exams ) : ?>
						<p class="text-slate-400">Bạn chưa làm đề thi nào. <a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_exams_url() ); ?>">Làm đề đầu tiên</a></p>
					<?php else : ?>
						<div class="space-y-2 text-slate-300">
							<div class="flex justify-between">
								<span>Đề đã nộp / đã mở:</span>
								<strong class="text-emerald-400"><?php echo esc_html( $completed_exams . '/' . $attempted_exams ); ?></strong>
							</div>
							<div class="w-full bg-slate-900 rounded-full h-2 border border-slate-800 overflow-hidden">
								<div class="bg-emerald-400 h-2 rounded-full" style="width: <?php echo esc_attr( $completion_pct ); ?>%"></div>
							</div>
							<div class="flex justify-between pt-2">
								<span>Điểm cao nhất trung bình:</span>
								<strong class="text-amber-400"><?php echo null !== $avg_best ? esc_html( $avg_best . '%' ) : '—'; ?></strong>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 border border-cyan-500/30 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
					<h4 class="font-extrabold text-white text-xs">Gợi ý ôn tập cho bạn</h4>
					<p class="text-slate-400 text-[11px]">
						Gợi ý dựa trên chủ đề bạn làm sai nhiều, khóa đang học và mục tiêu ôn thi.
					</p>
					<a href="<?php echo esc_url( cvc_account_url( 'recommendations' ) ); ?>" class="block w-full py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black text-xs rounded-xl shadow transition-colors">
						Xem gợi ý
					</a>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
