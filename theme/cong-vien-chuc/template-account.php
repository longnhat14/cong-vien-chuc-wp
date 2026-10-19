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

if ( 'muc-tieu' === $section_slug || 'goals' === $section_slug ) {
	// 18/10/2026: mục tiêu ôn thi quản lý trong Hồ sơ chinh phục kỳ thi (mỗi mục tiêu = 1 hồ sơ).
	wp_safe_redirect( cvc_account_url( 'conquest' ) );
	exit;
}

$section = $sections[ $section_slug ] ?? null;

if ( null === $section ) {
	$section_slug = 'tong-quan';
	$section      = 'overview';
}

cvc_seo_set_noindex();

$section_titles = array(
	'overview'             => 'Tổng quan cá nhân',
	'conquest'             => 'Hồ sơ chinh phục kỳ thi',
	'bookmarks'            => 'Tin & nội dung đã lưu',
	'my-documents'         => 'Tài liệu của tôi',
	'exam-history'         => 'Lịch sử luyện thi & kết quả',
	'learning-path'        => 'Kế hoạch & nhắc lịch',
	'recruitment-matches'  => 'Việc làm phù hợp',
	'recommendations'      => 'Gợi ý cho bạn',
	'my-courses'           => 'Khóa học của tôi',
	'notifications'        => 'Thông báo',
	'certificates'         => 'Chứng chỉ của tôi',
	'profile'              => 'Hồ sơ cá nhân',
	'security'             => 'Bảo mật & đăng nhập',
);

$section_fa_icons = array(
	'overview'             => 'fa-chart-pie',
	'conquest'             => 'fa-flag-checkered',
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

cvc_seo_set_title( ( $section_titles[ $section ] ?? 'Tài khoản' ) . ' — Công Viên Chức' );

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

if ( 'conquest' === $section ) {
	require get_theme_file_path( '/inc/account-sections/conquest-page.php' );
	return;
}

// Tổng quan tài khoản v1 (19/10/2026): trang riêng toàn chiều rộng theo thiết kế congvienchuc-account-dashboard-v1.
if ( 'overview' === $section ) {
	require get_theme_file_path( '/inc/account-sections/overview-page.php' );
	return;
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
				array( 'label' => 'Tài khoản cá nhân' ),
				array( 'label' => $section_titles[ $section ] ),
			)
		);
		?>

		<!-- EXECUTIVE USER DASHBOARD BANNER -->
		<section class="dz-herohead">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
				<div class="flex items-center gap-4">
					<div class="w-14 h-14 rounded-2xl bg-acc text-white font-black text-xl flex items-center justify-center border-2 border-acc-line shrink-0">
						<?php echo esc_html( mb_substr( $user['name'] ?? 'U', 0, 1 ) ); ?>
					</div>
					<div class="space-y-1">
						<div class="flex items-center gap-2">
							<h1 class="text-xl sm:text-2xl font-black text-ink">
								Xin chào, <?php echo esc_html( $user['name'] ?? $user['email'] ?? 'Học viên' ); ?>
							</h1>
						</div>
						<p class="text-xs text-ink-2">
							Mã học viên: <strong class="text-acc">CVC-<?php echo sprintf('%05d', (int)($user['id'] ?? 1)); ?></strong> &middot; Email: <?php echo esc_html($user['email'] ?? ''); ?>
						</p>
					</div>
				</div>

				<div class="flex items-center gap-3 shrink-0">
					<a href="<?php echo esc_url( cvc_logout_url() ); ?>" class="px-4 py-2 bg-acc-soft hover:bg-acc-soft text-red-700 border border-acc-line font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5">
						<i class="fa-solid fa-right-from-bracket"></i> Đăng Xuất
					</a>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN DASHBOARD LAYOUT -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT SIDEBAR (3 COLS — DASHBOARD MENU) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<div class="dz-card space-y-2 text-xs">
					<h3 class="dz-sideh2">
						<i class="fa-solid fa-bars"></i> Danh Mục Quản Lý
					</h3>
					<nav class="space-y-1">
						<?php foreach ( $section_titles as $key => $label ) : 
							$fa_icon = $section_fa_icons[$key] ?? 'fa-circle';
							$is_active = $key === $section;
							$link_class = $is_active 
								? 'bg-acc-soft text-acc font-extrabold border border-acc-line' 
								: 'text-ink-2 hover:bg-acc-soft hover:text-acc-2';
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
			<div class="lg:col-span-6 space-y-4">
				
				<?php cvc_render_notice(); ?>

				<div class="dz-panel space-y-4">
					<div class="flex items-center justify-between border-b border-slate-300 pb-3">
						<h2 class="text-base font-black text-ink flex items-center gap-2">
							<i class="fa-solid <?php echo $section_fa_icons[$section] ?? 'fa-circle'; ?> text-acc"></i>
							<?php echo esc_html( $section_titles[ $section ] ); ?>
						</h2>
					</div>

					<div class="cvc-account-section-wrapper text-xs space-y-4">
						<?php require get_theme_file_path( '/inc/account-sections/' . $section . '.php' ); ?>
					</div>
				</div>

			</div>

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
				<div class="dz-card space-y-3 text-xs">
					<h3 class="dz-sideh2">
						<i class="fa-solid fa-chart-line"></i> Chỉ Số Học Tập
					</h3>
					<?php if ( ! $progress_result['ok'] ) : ?>
						<p class="text-ink-muted">Chưa tải được chỉ số học tập.</p>
					<?php elseif ( 0 === $attempted_exams ) : ?>
						<p class="text-ink-muted">Bạn chưa làm đề thi nào. <a class="text-acc font-bold" href="<?php echo esc_url( cvc_exams_url() ); ?>">Làm đề đầu tiên</a></p>
					<?php else : ?>
						<div class="space-y-2 text-ink-2">
							<div class="flex justify-between">
								<span>Đề đã nộp / đã mở:</span>
								<strong class="text-emerald-800"><?php echo esc_html( $completed_exams . '/' . $attempted_exams ); ?></strong>
							</div>
							<div class="w-full bg-white rounded-full h-2 border border-slate-300 overflow-hidden">
								<div class="bg-acc h-2 rounded-full text-white" style="width: <?php echo esc_attr( $completion_pct ); ?>%"></div>
							</div>
							<div class="flex justify-between pt-2">
								<span>Điểm cao nhất trung bình:</span>
								<strong class="text-acc"><?php echo null !== $avg_best ? esc_html( $avg_best . '%' ) : '—'; ?></strong>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<div class="border border-acc-line p-5 rounded-2xl space-y-3 text-xs text-center">
					<h4 class="font-extrabold text-ink text-xs">Gợi ý ôn tập cho bạn</h4>
					<p class="text-ink-muted text-[11px]">
						Gợi ý dựa trên chủ đề bạn làm sai nhiều, khóa đang học và mục tiêu ôn thi.
					</p>
					<a href="<?php echo esc_url( cvc_account_url( 'recommendations' ) ); ?>" class="block w-full py-2 bg-acc hover:bg-acc-deep text-white font-black text-xs rounded-xl transition-colors">
						Xem gợi ý
					</a>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
