<!DOCTYPE html>
<html lang="vi">
<head>
<?php wp_head(); ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CÔNG VIÊN CHỨC - Ôn thi công chức, viên chức</title>
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script>
/*
 * 16/10/2026 - Giao diện "văn bản" toàn site (đồng bộ trang làm bài thi):
 * Times New Roman, nền trắng chữ đen, điểm nhấn xanh hải quân, bo góc 2px,
 * không bóng đổ/gradient. Bảng màu cũ (navy/gold/azure) được ÁNH XẠ LẠI
 * sang tông xanh hải quân để class cũ còn sót vẫn đồng bộ.
 */
tailwind.config = {
theme: {
fontFamily: {
sans: ['"Times New Roman"', 'Times', '"Liberation Serif"', 'Tinos', 'serif'],
serif: ['"Times New Roman"', 'Times', '"Liberation Serif"', 'Tinos', 'serif'],
mono: ['ui-monospace', 'Consolas', 'monospace'],
handwriting: ['"Times New Roman"', 'Times', 'serif'],
},
borderRadius: {
none: '0', sm: '2px', DEFAULT: '2px', md: '2px', lg: '2px', xl: '3px', '2xl': '3px', '3xl': '4px', full: '9999px',
},
boxShadow: {
none: 'none', sm: 'none', DEFAULT: 'none', md: 'none', lg: 'none', xl: 'none', '2xl': 'none', inner: 'none',
'glow-gold': 'none', 'glow-azure': 'none', glass: 'none', executive: 'none',
},
extend: {
colors: {
ink: { DEFAULT: '#111111', 2: '#333333', muted: '#5c5c5c' },
acc: { DEFAULT: '#123a63', 2: '#1767aa', soft: '#eaf3fc', line: '#8fb4da', deep: '#0b2744' },
navy: { 950: '#0b2744', 900: '#123a63', 800: '#1a4775', 700: '#1f5288', 600: '#2a6db0', 500: '#4a7fb8' },
gold: { 300: '#bcd5ee', 400: '#8fb4da', 500: '#1767aa', 600: '#123a63', 700: '#0b2744' },
azure: { 400: '#3d7fc0', 500: '#1767aa', 600: '#123a63', glow: '#1767aa' },
},
fontSize: {
'2xs': ['13px', '1.4'],
},
}
}
}
</script>
<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css/cvc-doc.css?ver=' . (string) filemtime( get_theme_file_path( '/assets/css/cvc-doc.css' ) ) ); ?>">
</head>
<?php
if ( ! defined( "ABSPATH" ) ) {
	exit;
}
?>
<body <?php body_class( 'cvc-doc' ); ?>>
<?php wp_body_open(); ?>
<?php cvc_render_notice(); ?>

<?php
/*
 * Header (viết lại Phase 12; giao diện văn bản 16/10/2026): bỏ nội dung giả - bản tin "EXECUTIVE BULLETIN"
 * bịa, hotline 1900 888 999 không có thật, link "Trợ lý AI" chết, chuông
 * thông báo luôn có chấm đỏ và 2 thông báo mẫu, nút "Đăng Nhập" hiện cả khi
 * đã đăng nhập. Menu thu về nút 3 gạch dưới 1280px để không bị chật.
 */
$cvc_header_logged_in = cvc_is_logged_in();
$cvc_header_unread    = 0;
if ( $cvc_header_logged_in && cvc_auth_token() ) {
	$cvc_unread_result = ( new CVC_Notification_Service() )->unreadCount( (string) cvc_auth_token() );
	$cvc_header_unread = ! empty( $cvc_unread_result['ok'] ) ? (int) ( $cvc_unread_result['data']['data']['unread_count'] ?? 0 ) : 0;
}
// Menu "On thi" gom de thi, chu de, kien thuc, khoa hoc (khoa hoc chua mo -> nhan "Sap mo").
$cvc_has_courses = function_exists( 'cvc_has_sellable_courses' ) && cvc_has_sellable_courses();
$cvc_nav_items   = array(
	array(
		'label'    => 'Ôn thi',
		'url'      => cvc_exams_url(),
		'children' => array(
			array( 'label' => 'Đề thi thử miễn phí', 'url' => cvc_exams_url(), 'icon' => 'fa-pen-to-square' ),
			array( 'label' => 'Chủ đề ôn thi', 'url' => cvc_topics_url(), 'icon' => 'fa-layer-group' ),
			array( 'label' => 'Kiến thức trọng tâm', 'url' => cvc_knowledge_url(), 'icon' => 'fa-lightbulb' ),
			array( 'label' => 'Khóa học', 'url' => cvc_courses_url(), 'icon' => 'fa-graduation-cap', 'badge' => $cvc_has_courses ? '' : 'Sắp mở' ),
		),
	),
	array( 'label' => 'Tuyển dụng', 'url' => cvc_recruitments_url() ),
	array( 'label' => 'Văn bản pháp luật', 'url' => cvc_legal_documents_url() ),
	array( 'label' => 'Tài liệu', 'url' => cvc_documents_url() ),
);
$cvc_current_url = home_url( add_query_arg( null, null ) );
?>
<a class="cvc-skip-link" href="#main">Bỏ qua, tới nội dung chính</a>
<!-- Thanh trên: dòng tin ngắn + tìm kiếm -->
<div class="dz-topbar">
<div class="dz-wrap dz-topbar-in">
<a href="<?php echo esc_url( cvc_recruitments_url() ); ?>" class="dz-topbar-link"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Tin tuyển dụng công chức, viên chức cập nhật hằng ngày — xem hạn nộp hồ sơ</a>
<a href="<?php echo esc_url( cvc_search_url() ); ?>" class="dz-topbar-link dz-hide-sm"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Tra cứu</a>
</div>
</div>
<!-- Đầu trang -->
<header class="dz-masthead">
<div class="dz-wrap dz-masthead-in">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dz-brand">
<span class="dz-emblem" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 9h18L12 3 3 9Z"/><path d="M5 10v8m4-8v8m6-8v8m4-8v8M3 20h18"/></svg></span>
<span class="dz-brandtext"><span class="dz-org">CÔNG VIÊN CHỨC</span><span class="dz-motto">Ôn thi công chức, viên chức · Kiến thức vững vàng, sự nghiệp bền vững</span></span>
</a>
<nav class="dz-nav" aria-label="Menu chính">
<?php foreach ( $cvc_nav_items as $cvc_nav ) : ?>
<?php
$cvc_urls   = array_merge( array( $cvc_nav['url'] ), array_column( (array) ( $cvc_nav['children'] ?? array() ), 'url' ) );
$cvc_active = (bool) array_filter( $cvc_urls, fn ( $u ) => 0 === strpos( $cvc_current_url, $u ) );
?>
<?php if ( ! empty( $cvc_nav['children'] ) ) : ?>
<div class="dz-navgroup">
<a href="<?php echo esc_url( $cvc_nav['url'] ); ?>" class="dz-navlink<?php echo $cvc_active ? ' is-active' : ''; ?>" aria-haspopup="true" <?php echo $cvc_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $cvc_nav['label'] ); ?> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i></a>
<ul class="dz-dropdown">
<?php foreach ( $cvc_nav['children'] as $cvc_child ) : ?>
<li><a href="<?php echo esc_url( $cvc_child['url'] ); ?>"><span><?php echo esc_html( $cvc_child['label'] ); ?></span><?php if ( ! empty( $cvc_child['badge'] ) ) : ?><em class="dz-tag"><?php echo esc_html( $cvc_child['badge'] ); ?></em><?php endif; ?></a></li>
<?php endforeach; ?>
</ul>
</div>
<?php else : ?>
<a href="<?php echo esc_url( $cvc_nav['url'] ); ?>" class="dz-navlink<?php echo $cvc_active ? ' is-active' : ''; ?>" <?php echo $cvc_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $cvc_nav['label'] ); ?></a>
<?php endif; ?>
<?php endforeach; ?>
</nav>
<div class="dz-actions">
<button type="button" onclick="toggleSearchModal()" class="dz-iconbtn" aria-label="Tìm kiếm"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
<?php if ( $cvc_header_logged_in ) : ?>
<a href="<?php echo esc_url( cvc_account_url( 'notifications' ) ); ?>" class="dz-iconbtn" aria-label="<?php echo esc_attr( $cvc_header_unread > 0 ? sprintf( 'Thông báo: %d chưa đọc', $cvc_header_unread ) : 'Thông báo' ); ?>">
<i class="fa-regular fa-bell" aria-hidden="true"></i>
<?php if ( $cvc_header_unread > 0 ) : ?><span class="dz-count"><?php echo esc_html( $cvc_header_unread > 9 ? '9+' : (string) $cvc_header_unread ); ?></span><?php endif; ?>
</a>
<?php if ( function_exists( 'cvc_admin_is_staff' ) && cvc_admin_is_staff() ) : ?>
<a href="<?php echo esc_url( cvc_admin_url() ); ?>" class="dz-btn dz-hide-sm">Quản trị</a>
<?php endif; ?>
<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="dz-btn dz-btn-primary dz-hide-sm"><i class="fa-regular fa-user" aria-hidden="true"></i> Tài khoản</a>
<?php else : ?>
<a href="<?php echo esc_url( cvc_login_url( $cvc_current_url ) ); ?>" class="dz-btn dz-hide-sm">Đăng nhập</a>
<a href="<?php echo esc_url( cvc_register_url() ); ?>" class="dz-btn dz-btn-primary dz-hide-sm">Đăng ký</a>
<?php endif; ?>
<button type="button" onclick="toggleMobileMenu()" class="dz-iconbtn dz-menubtn" aria-label="Mở menu" aria-controls="mobile-menu"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
</div>
</div>
<div id="mobile-menu" class="hidden dz-mobile">
<?php foreach ( $cvc_nav_items as $cvc_nav ) : ?>
<?php if ( ! empty( $cvc_nav['children'] ) ) : ?>
<p class="dz-mobile-label"><?php echo esc_html( $cvc_nav['label'] ); ?></p>
<?php foreach ( $cvc_nav['children'] as $cvc_child ) : ?>
<a href="<?php echo esc_url( $cvc_child['url'] ); ?>" class="dz-mobile-link"><span><?php echo esc_html( $cvc_child['label'] ); ?></span><?php if ( ! empty( $cvc_child['badge'] ) ) : ?><em class="dz-tag"><?php echo esc_html( $cvc_child['badge'] ); ?></em><?php endif; ?></a>
<?php endforeach; ?>
<?php else : ?>
<a href="<?php echo esc_url( $cvc_nav['url'] ); ?>" class="dz-mobile-link"><?php echo esc_html( $cvc_nav['label'] ); ?></a>
<?php endif; ?>
<?php endforeach; ?>
<div class="dz-mobile-actions">
<?php if ( $cvc_header_logged_in ) : ?>
<?php if ( function_exists( 'cvc_admin_is_staff' ) && cvc_admin_is_staff() ) : ?>
<a href="<?php echo esc_url( cvc_admin_url() ); ?>" class="dz-btn">Quản trị</a>
<?php endif; ?>
<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="dz-btn dz-btn-primary">Tài khoản</a>
<a href="<?php echo esc_url( cvc_logout_url() ); ?>" class="dz-btn">Đăng xuất</a>
<?php else : ?>
<a href="<?php echo esc_url( cvc_login_url( $cvc_current_url ) ); ?>" class="dz-btn">Đăng nhập</a>
<a href="<?php echo esc_url( cvc_register_url() ); ?>" class="dz-btn dz-btn-primary">Đăng ký</a>
<?php endif; ?>
</div>
</div>
</header>
