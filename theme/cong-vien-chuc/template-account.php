<?php
/**
 * Dashboard người dùng - /tai-khoan/{section}/ (Phase 10, Phần IX-XVI).
 *
 * 1 template DUY NHẤT cho toàn bộ dashboard, chia theo cvc_account_section
 * (query var từ inc/routes.php) - tránh 9 file template gần như giống hệt
 * nhau (chung sidebar/notice/auth-guard). Mỗi section chỉ gọi ĐÚNG 1-2 API
 * cần thiết cho chính nó (Phần Performance - "không gọi API thừa"), KHÔNG
 * tự tính toán gì thêm ngoài những gì API đã trả (điểm/trạng thái/counts
 * đều nguyên từ Laravel).
 *
 * Toàn bộ dashboard luôn noindex (Phần XII SEO) - nội dung riêng tư của
 * từng user, không có giá trị index công khai.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();
$token = cvc_auth_token();
$user  = cvc_current_user();

$sections     = cvc_account_sections();
$section_slug = sanitize_key( (string) get_query_var( 'cvc_account_section' ) );
$section      = $sections[ $section_slug ] ?? null;

if ( null === $section ) {
	status_header( 404 );
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
	'recommendations'      => 'Gợi ý cho bạn',
	'recruitment-matches'  => 'Việc làm phù hợp',
	'notifications'        => 'Thông báo',
);

/*
 * Phase 10A.17: icon menu tài khoản (CVC icon library) - trước đây nav
 * này hoàn toàn không có icon (chỉ text link). 'overview' không có icon
 * khớp nghĩa trong bộ dashboard/analytics/recruitment - để trống, không
 * ép. 'settings'/'security' trong bộ icon KHÔNG có section tương ứng ở
 * đây (dashboard hiện chưa có trang cài đặt/bảo mật riêng) - không dùng.
 */
$section_icons = array(
	'profile'              => 'dashboard/profile',
	'goals'                => 'dashboard/goal',
	'learning-path'        => 'dashboard/roadmap',
	'bookmarks'            => 'dashboard/favorites',
	'exam-history'         => 'dashboard/history',
	'recommendations'      => 'analytics/insight',
	'recruitment-matches'  => 'recruitment/position',
	'notifications'        => 'dashboard/notification',
);

cvc_seo_set_title( $section_titles[ $section ] . ' - Tài khoản' );

/**
 * Nhãn trạng thái tiếng Việt - dùng chung cho goals/matches.
 */
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

<main id="main" class="container cvc-page cvc-account-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array( 'label' => 'Tài khoản' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1><?php echo esc_html( $section_titles[ $section ] ); ?></h1>
		<?php if ( $user ) : ?>
			<p class="cvc-page-header__meta">Xin chào, <?php echo esc_html( $user['name'] ?? $user['email'] ?? '' ); ?></p>
		<?php endif; ?>
	</header>

	<?php cvc_render_notice(); ?>

	<div class="cvc-account-layout">
		<nav class="cvc-account-nav" aria-label="Điều hướng tài khoản">
			<ul>
				<?php foreach ( $section_titles as $key => $label ) : ?>
					<li>
						<a
							href="<?php echo esc_url( cvc_account_url( $key ) ); ?>"
							<?php echo $key === $section ? ' class="is-active" aria-current="page"' : ''; ?>
						>
							<?php if ( isset( $section_icons[ $key ] ) ) : ?>
								<span class="cvc-account-nav__icon" aria-hidden="true"><?php cvc_render_cvc_icon( $section_icons[ $key ], 20 ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( $label ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<a class="cvc-account-nav__logout" href="<?php echo esc_url( cvc_logout_url() ); ?>">Đăng xuất</a>
		</nav>

		<div class="cvc-account-content">
			<?php require get_theme_file_path( '/inc/account-sections/' . $section . '.php' ); ?>
		</div>
	</div>
</main>

<?php get_footer(); ?>
