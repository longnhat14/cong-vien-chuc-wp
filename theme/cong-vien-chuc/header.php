<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
<?php wp_head(); ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CÔNG VIÊN CHỨC - Nền tảng Đào tạo & Phát triển Sự nghiệp Công cao cấp</title>
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- FontAwesome 6 Pro/Free Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts: Plus Jakarta Sans & Dancing Script -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
<script>
tailwind.config = {
theme: {
extend: {
colors: {
navy: {
950: '#020C1B',
900: '#0A192F',
800: '#0F2027',
700: '#112240',
600: '#1D3557',
500: '#2A4365',
},
gold: {
300: '#FDE047',
400: '#FBBF24',
500: '#F59E0B',
600: '#D97706',
700: '#B45309',
},
azure: {
400: '#38BDF8',
500: '#0EA5E9',
600: '#0284C7',
glow: '#06B6D4',
}
},
fontFamily: {
sans: ['Plus Jakarta Sans', 'sans-serif'],
handwriting: ['Dancing Script', 'cursive'],
},
boxShadow: {
'glow-gold': '0 0 25px -5px rgba(245, 158, 11, 0.35)',
'glow-azure': '0 0 25px -5px rgba(6, 182, 212, 0.35)',
'glass': '0 8px 32px 0 rgba(0, 0, 0, 0.37)',
'executive': '0 20px 40px -15px rgba(2, 12, 27, 0.12)',
},
keyframes: {
marquee: {
'0%': { transform: 'translateX(0%)' },
'100%': { transform: 'translateX(-50%)' },
},
pulseGlow: {
'0%, 100%': { opacity: '0.4', transform: 'scale(1)' },
'50%': { opacity: '0.8', transform: 'scale(1.05)' },
},
shimmer: {
'100%': { transform: 'translateX(100%)' }
}
},
animation: {
'marquee': 'marquee 30s linear infinite',
'pulse-glow': 'pulseGlow 6s infinite ease-in-out',
'shimmer': 'shimmer 2.5s infinite'
}
}
}
}
</script>
<style>
body {
font-family: 'Plus Jakarta Sans', sans-serif;
background-color: #f8fafc;
color: #0f172a;
}
/* Glassmorphism utility */
.glass-card {
background: rgba(255, 255, 255, 0.85);
backdrop-filter: blur(16px);
-webkit-backdrop-filter: blur(16px);
border: 1px solid rgba(255, 255, 255, 0.6);
}
.glass-dark {
background: rgba(15, 32, 39, 0.75);
backdrop-filter: blur(20px);
-webkit-backdrop-filter: blur(20px);
border: 1px solid rgba(255, 255, 255, 0.1);
}
.glass-header {
background: rgba(255, 255, 255, 0.92);
backdrop-filter: blur(20px);
-webkit-backdrop-filter: blur(20px);
}
/* Metallic Gold Gradient Text */
.text-gold-gradient {
background: linear-gradient(135deg, #FFE082 0%, #F59E0B 50%, #D97706 100%);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
}
.text-azure-gradient {
background: linear-gradient(135deg, #38BDF8 0%, #0EA5E9 50%, #0284C7 100%);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;
}
.hero-gradient {
background: radial-gradient(circle at 80% 20%, #112240 0%, #0A192F 50%, #020C1B 100%);
}
/* Custom Scrollbar */
::-webkit-scrollbar {
width: 8px;
}
::-webkit-scrollbar-track {
background: #020C1B;
}
::-webkit-scrollbar-thumb {
background: #1D3557;
border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
background: #F59E0B;
}
</style>

<style>
/* CSS Resets & Spacing Enhancements */
#main.cvc-homepage-prototype-100 a,
header a,
footer a,
#search-modal a, #action-modal a, #login-modal a {
    color: inherit;
    text-decoration: none;
}

#main.cvc-homepage-prototype-100 h1,
#main.cvc-homepage-prototype-100 h2,
#main.cvc-homepage-prototype-100 h3,
#main.cvc-homepage-prototype-100 h4,
#main.cvc-homepage-prototype-100 h5,
#main.cvc-homepage-prototype-100 h6,
header h1, header h2, header h3, header h4, header h5, header h6,
footer h1, footer h2, footer h3, footer h4, footer h5, footer h6 {
    margin-top: 0.35rem;
    margin-bottom: 0.5rem;
    line-height: 1.35;
    letter-spacing: normal;
}

#main.cvc-homepage-prototype-100 p,
header p,
footer p,
#search-modal p, #action-modal p, #login-modal p {
    margin-top: 0.25rem;
    margin-bottom: 0.5rem;
    line-height: 1.65;
}

html, body, button, input, select, textarea,
#main.cvc-homepage-prototype-100,
header, footer, #search-modal, #action-modal, #login-modal {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

#main.cvc-homepage-prototype-100 *:not(i):not([class*="fa-"]),
header *:not(i):not([class*="fa-"]),
footer *:not(i):not([class*="fa-"]),
#search-modal *:not(i):not([class*="fa-"]),
#action-modal *:not(i):not([class*="fa-"]),
#login-modal *:not(i):not([class*="fa-"]) {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Explicitly protect FontAwesome icon font family and weight */
i.fa, i.fas, i.far, i.fal, i.fab, i.fa-solid, i.fa-regular, i.fa-brands, [class*="fa-"] {
    font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
}
.fa-solid, .fas {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
}
.fa-regular, .far {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 400 !important;
}
.fa-brands, .fab {
    font-family: "Font Awesome 6 Brands" !important;
    font-weight: 400 !important;
}
.font-handwriting {
    font-family: 'Dancing Script', cursive !important;
}
</style>
</head>
<?php
if ( ! defined( "ABSPATH" ) ) {
	exit;
}
?>
<body <?php body_class( "bg-slate-50 text-slate-900 antialiased selection:bg-gold-500 selection:text-white" ); ?>>
<?php wp_body_open(); ?>
<?php cvc_render_notice(); ?>

<?php
/*
 * Header (viết lại Phase 12): bỏ nội dung giả - bản tin "EXECUTIVE BULLETIN"
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
<!-- 1. THANH THÔNG TIN -->
<div class="bg-navy-950 text-slate-300 text-xs py-2 px-4 border-b border-navy-700/60 relative z-50">
<div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
<a href="<?php echo esc_url( cvc_recruitments_url() ); ?>" class="text-[11px] text-slate-300 hover:text-white truncate">
<i class="fa-solid fa-bullhorn text-gold-400 mr-1.5" aria-hidden="true"></i> Tin tuyển dụng công chức, viên chức mới cập nhật — xem hạn nộp hồ sơ
</a>
<a href="<?php echo esc_url( cvc_search_url() ); ?>" class="hidden sm:inline text-[11px] text-gold-400 hover:text-gold-300 font-bold shrink-0"><i class="fa-solid fa-magnifying-glass mr-1" aria-hidden="true"></i> Tìm kiếm</a>
</div>
</div>
<!-- 2. NAVIGATION HEADER -->
<header class="sticky top-0 z-40 glass-header border-b border-slate-200/80 shadow-sm transition-all duration-300">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="flex items-center justify-between gap-4 h-20">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-3 group shrink-0">
<span class="w-11 h-11 bg-gradient-to-tr from-navy-950 via-navy-900 to-navy-700 text-white rounded-2xl flex items-center justify-center text-xl shadow-xl shadow-navy-950/20 border border-slate-700/50" aria-hidden="true">
<i class="fa-solid fa-landmark-dome text-gold-400"></i>
</span>
<span class="flex flex-col justify-center">
<span class="text-lg sm:text-xl font-black text-navy-950 leading-tight whitespace-nowrap group-hover:text-azure-600 transition-colors">CÔNG VIÊN CHỨC</span>
<span class="hidden sm:block text-[10px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Ôn thi công chức, viên chức</span>
</span>
</a>
<nav class="hidden xl:flex items-center gap-0.5 text-[13px] font-bold text-slate-700" aria-label="Menu chính">
<?php foreach ( $cvc_nav_items as $cvc_nav ) : ?>
<?php
$cvc_urls   = array_merge( array( $cvc_nav['url'] ), array_column( (array) ( $cvc_nav['children'] ?? array() ), 'url' ) );
$cvc_active = (bool) array_filter( $cvc_urls, fn ( $u ) => 0 === strpos( $cvc_current_url, $u ) );
$cvc_cls    = 'px-2.5 py-2 rounded-xl whitespace-nowrap transition-colors ' . ( $cvc_active ? 'text-navy-900 bg-slate-100 border-b-2 border-gold-500' : 'hover:text-azure-600 hover:bg-slate-100' );
?>
<?php if ( ! empty( $cvc_nav['children'] ) ) : ?>
<div class="relative group">
<a href="<?php echo esc_url( $cvc_nav['url'] ); ?>" class="<?php echo esc_attr( $cvc_cls ); ?> inline-flex items-center gap-1" aria-haspopup="true" <?php echo $cvc_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $cvc_nav['label'] ); ?><i class="fa-solid fa-chevron-down text-[9px] opacity-60" aria-hidden="true"></i></a>
<div class="absolute left-0 top-full pt-2 hidden group-hover:block group-focus-within:block z-50">
<ul class="min-w-[220px] bg-white border border-slate-200 rounded-2xl shadow-xl p-2 space-y-0.5">
<?php foreach ( $cvc_nav['children'] as $cvc_child ) : ?>
<li><a href="<?php echo esc_url( $cvc_child['url'] ); ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 hover:text-azure-600"><i class="fa-solid <?php echo esc_attr( $cvc_child['icon'] ?? 'fa-angle-right' ); ?> w-4 text-slate-400" aria-hidden="true"></i><span class="flex-1"><?php echo esc_html( $cvc_child['label'] ); ?></span><?php if ( ! empty( $cvc_child['badge'] ) ) : ?><span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700"><?php echo esc_html( $cvc_child['badge'] ); ?></span><?php endif; ?></a></li>
<?php endforeach; ?>
</ul>
</div>
</div>
<?php else : ?>
<a href="<?php echo esc_url( $cvc_nav['url'] ); ?>" class="<?php echo esc_attr( $cvc_cls ); ?>" <?php echo $cvc_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $cvc_nav['label'] ); ?></a>
<?php endif; ?>
<?php endforeach; ?>
</nav>
<div class="flex items-center gap-2 shrink-0">
<button type="button" onclick="toggleSearchModal()" class="w-10 h-10 text-slate-600 hover:text-azure-600 hover:bg-slate-100 rounded-xl flex items-center justify-center transition-colors" aria-label="Tìm kiếm">
<i class="fa-solid fa-magnifying-glass text-base" aria-hidden="true"></i>
</button>
<?php if ( $cvc_header_logged_in ) : ?>
<a href="<?php echo esc_url( cvc_account_url( 'notifications' ) ); ?>" class="w-10 h-10 text-slate-600 hover:text-azure-600 hover:bg-slate-100 rounded-xl flex items-center justify-center transition-colors relative" aria-label="<?php echo esc_attr( $cvc_header_unread > 0 ? sprintf( 'Thông báo: %d chưa đọc', $cvc_header_unread ) : 'Thông báo' ); ?>">
<i class="fa-regular fa-bell text-base" aria-hidden="true"></i>
<?php if ( $cvc_header_unread > 0 ) : ?>
<span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center"><?php echo esc_html( $cvc_header_unread > 9 ? '9+' : (string) $cvc_header_unread ); ?></span>
<?php endif; ?>
</a>
<?php if ( function_exists( 'cvc_admin_is_staff' ) && cvc_admin_is_staff() ) : ?>
<a href="<?php echo esc_url( cvc_admin_url() ); ?>" class="hidden sm:flex px-3 py-2.5 bg-amber-500 hover:bg-amber-400 text-navy-950 text-xs font-black rounded-xl items-center gap-1.5"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>Quản trị</span></a>
<?php endif; ?>
<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="hidden sm:flex px-4 py-2.5 bg-gradient-to-r from-navy-950 to-navy-800 hover:from-navy-900 hover:to-navy-700 text-white text-xs font-bold rounded-xl shadow-md items-center gap-2 border border-slate-700/40">
<i class="fa-solid fa-user-shield text-gold-400" aria-hidden="true"></i><span>Tài khoản</span>
</a>
<?php else : ?>
<a href="<?php echo esc_url( cvc_login_url( $cvc_current_url ) ); ?>" class="hidden sm:inline-flex px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl">Đăng nhập</a>
<a href="<?php echo esc_url( cvc_register_url() ); ?>" class="hidden sm:inline-flex px-4 py-2.5 bg-gradient-to-r from-navy-950 to-navy-800 text-white text-xs font-bold rounded-xl shadow-md border border-slate-700/40">Đăng ký</a>
<?php endif; ?>
<button type="button" onclick="toggleMobileMenu()" class="xl:hidden w-10 h-10 text-slate-700 hover:bg-slate-100 rounded-xl flex items-center justify-center" aria-label="Mở menu" aria-controls="mobile-menu">
<i class="fa-solid fa-bars text-xl" aria-hidden="true"></i>
</button>
</div>
</div>
</div>
<div id="mobile-menu" class="hidden xl:hidden bg-white border-b border-slate-200 px-4 py-5 space-y-2 shadow-xl">
<?php foreach ( $cvc_nav_items as $cvc_nav ) : ?>
<?php if ( ! empty( $cvc_nav['children'] ) ) : ?>
<p class="px-4 pt-1 text-[11px] font-black uppercase tracking-wider text-slate-400"><?php echo esc_html( $cvc_nav['label'] ); ?></p>
<?php foreach ( $cvc_nav['children'] as $cvc_child ) : ?>
<a href="<?php echo esc_url( $cvc_child['url'] ); ?>" class="flex items-center gap-2 px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-sm"><span class="flex-1"><?php echo esc_html( $cvc_child['label'] ); ?></span><?php if ( ! empty( $cvc_child['badge'] ) ) : ?><span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700"><?php echo esc_html( $cvc_child['badge'] ); ?></span><?php endif; ?></a>
<?php endforeach; ?>
<?php else : ?>
<a href="<?php echo esc_url( $cvc_nav['url'] ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-sm"><?php echo esc_html( $cvc_nav['label'] ); ?></a>
<?php endif; ?>
<?php endforeach; ?>
<div class="pt-3 border-t border-slate-100 flex gap-2">
<?php if ( $cvc_header_logged_in ) : ?>
<?php if ( function_exists( 'cvc_admin_is_staff' ) && cvc_admin_is_staff() ) : ?>
<a href="<?php echo esc_url( cvc_admin_url() ); ?>" class="flex-1 py-2.5 bg-amber-500 text-navy-950 font-black rounded-xl text-xs text-center">Quản trị</a>
<?php endif; ?>
<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="flex-1 py-2.5 bg-navy-950 text-white font-bold rounded-xl text-xs text-center">Tài khoản</a>
<a href="<?php echo esc_url( cvc_logout_url() ); ?>" class="flex-1 py-2.5 bg-slate-100 text-slate-800 font-bold rounded-xl text-xs text-center">Đăng xuất</a>
<?php else : ?>
<a href="<?php echo esc_url( cvc_login_url( $cvc_current_url ) ); ?>" class="flex-1 py-2.5 bg-slate-100 text-slate-800 font-bold rounded-xl text-xs text-center">Đăng nhập</a>
<a href="<?php echo esc_url( cvc_register_url() ); ?>" class="flex-1 py-2.5 bg-navy-950 text-white font-bold rounded-xl text-xs text-center">Đăng ký</a>
<?php endif; ?>
</div>
</div>
</header>
