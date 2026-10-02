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

<!-- 1. TOP EXECUTIVE ANNOUNCEMENT TICKER -->
<div class="bg-navy-950 text-slate-300 text-xs py-2 px-4 border-b border-navy-700/60 overflow-hidden relative z-50">
<div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
<div class="flex items-center space-x-3 text-nowrap">
<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-gold-500/20 text-gold-400 font-bold border border-gold-500/30 text-[10px] uppercase tracking-wider">
<span class="w-2 h-2 rounded-full bg-gold-400 animate-ping"></span> EXECUTIVE BULLETIN
</span>
<span class="text-slate-300 font-medium text-[11px]">
<i class="fa-solid fa-bullhorn text-gold-400 mr-1.5"></i> Mở cổng đăng ký Kỳ thi Nâng ngạch Chuyên viên chính 2026.
</span>
</div>
<div class="hidden md:flex items-center space-x-6 text-[11px] text-slate-400 font-medium">
<span class="flex items-center gap-1.5"><i class="fa-solid fa-phone-volume text-azure-400"></i> Hotline Doanh nghiệp: <strong class="text-white">1900 888 999</strong></span>

<a href="#tro-ly-ai" class="text-gold-400 hover:text-gold-300 font-bold transition-colors"><i class="fa-solid fa-robot mr-1"></i> Trợ lý AI Tư vấn</a>
</div>
</div>
</div>
<!-- 2. NAVIGATION HEADER -->
<header class="sticky top-0 z-40 glass-header border-b border-slate-200/80 shadow-sm transition-all duration-300">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="flex items-center justify-between h-20">
<!-- Logo & Brand Header -->
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center space-x-3.5 group">
<div class="relative">
<div class="w-12 h-12 bg-gradient-to-tr from-navy-950 via-navy-900 to-navy-700 text-white rounded-2xl flex items-center justify-center text-2xl shadow-xl shadow-navy-950/20 group-hover:scale-105 transition-transform border border-slate-700/50">
<i class="fa-solid fa-landmark-dome text-gold-400"></i>
</div>
<span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full" title="Hệ thống trực tuyến 24/7"></span>
</div>
<div class="flex flex-col justify-center py-1">
<span class="text-xl sm:text-2xl font-black text-navy-950 tracking-normal leading-snug group-hover:text-azure-600 transition-colors block">
CÔNG VIÊN CHỨC
</span>
<span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mt-0.5 block">
Học viện Đào tạo & Phát triển Công vụ
</span>
</div>
</a>
<!-- Desktop Menu -->
<nav class="hidden lg:flex items-center space-x-1 xl:space-x-1.5 text-xs font-bold uppercase tracking-wider text-slate-700">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="px-3 py-2 text-navy-900 bg-slate-100 rounded-xl border-b-2 border-gold-500 transition-all">Trang chủ</a>
<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="px-3 py-2 hover:text-azure-600 hover:bg-slate-100 rounded-xl transition-all">Khóa Học Enterprise</a>
<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="px-3 py-2 hover:text-azure-600 hover:bg-slate-100 rounded-xl transition-all">Lộ Trình Thăng Tiến</a>
<a href="<?php echo esc_url( cvc_recruitments_url() ); ?>" class="px-3 py-2 hover:text-azure-600 hover:bg-slate-100 rounded-xl transition-all">Tuyển Dụng & Bổ Nhiệm</a>
<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="px-3 py-2 hover:text-azure-600 hover:bg-slate-100 rounded-xl transition-all">Thi Trắc Nghiệm AI</a>
<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="px-3 py-2 hover:text-azure-600 hover:bg-slate-100 rounded-xl transition-all">Kho Tài Liệu</a>
<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="px-3 py-2 hover:text-azure-600 hover:bg-slate-100 rounded-xl transition-all">Văn Bản Pháp Luật</a>
<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="px-3 py-2 hover:text-azure-600 hover:bg-slate-100 rounded-xl transition-all">Kiến Thức Công Vụ</a>
</nav>
<!-- Header Controls & User Portal -->
<div class="flex items-center space-x-3">
<!-- Search Modal Trigger -->
<button onclick="toggleSearchModal()" class="w-10 h-10 text-slate-600 hover:text-azure-600 hover:bg-slate-100 rounded-xl flex items-center justify-center transition-colors">
<i class="fa-solid fa-magnifying-glass text-base"></i>
</button>
<!-- Notifications Button -->
<div class="relative">
<button onclick="toggleNotifications()" class="w-10 h-10 text-slate-600 hover:text-azure-600 hover:bg-slate-100 rounded-xl flex items-center justify-center transition-colors relative">
<i class="fa-regular fa-bell text-base"></i>
<span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full animate-ping"></span>
<span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
</button>
<!-- Notification Dropdown -->
<div id="notif-dropdown" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 p-4 z-50 animate-fadeIn">
<div class="flex items-center justify-between pb-3 border-b border-slate-100">
<h4 class="text-xs font-bold text-slate-900 uppercase">Thông báo từ Cổng Công vụ</h4>
<span class="text-[10px] bg-azure-50 text-azure-600 font-bold px-2 py-0.5 rounded-full">3 Mới</span>
</div>
<div class="space-y-2 mt-3 max-h-60 overflow-y-auto">
<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="block p-2.5 bg-slate-50 hover:bg-azure-50/50 rounded-xl transition-colors text-xs">
<p class="font-bold text-navy-950">Đã cập nhật Nghị định 2026</p>
<p class="text-[11px] text-slate-500 mt-0.5">Quy định mới về bảng lương và nâng ngạch công chức.</p>
<span class="text-[9px] text-slate-400 mt-1 block">10 phút trước</span>
</a>
<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block p-2.5 bg-slate-50 hover:bg-azure-50/50 rounded-xl transition-colors text-xs">
<p class="font-bold text-navy-950">Lịch thi thử vòng 1</p>
<p class="text-[11px] text-slate-500 mt-0.5">Phòng thi AI trực tuyến sẽ mở vào lúc 20:00 hôm nay.</p>
<span class="text-[9px] text-slate-400 mt-1 block">1 giờ trước</span>
</a>
</div>
</div>
</div>
<!-- Auth Portal Action Buttons -->
<div class="hidden sm:flex items-center space-x-2">
<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition-all">
Học Thử Demo
</a>
<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="px-5 py-2.5 bg-gradient-to-r from-navy-950 to-navy-800 hover:from-navy-900 hover:to-navy-700 text-white text-xs font-bold rounded-xl shadow-md shadow-navy-950/20 hover:shadow-xl hover:shadow-navy-950/30 transition-all flex items-center gap-2 border border-slate-700/40">
<i class="fa-solid fa-user-shield text-gold-400"></i>
<span>Cổng Học Viên</span>
</a>
</div>
<!-- Mobile Drawer Toggle Button -->
<button onclick="toggleMobileMenu()" class="lg:hidden w-10 h-10 text-slate-700 hover:bg-slate-100 rounded-xl flex items-center justify-center">
<i class="fa-solid fa-bars text-xl"></i>
</button>
</div>
</div>
</div>
<!-- Mobile Drawer Menu -->
<div id="mobile-menu" class="hidden lg:hidden bg-white border-b border-slate-200 px-4 py-5 space-y-3 shadow-xl">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block px-4 py-2.5 bg-navy-950 text-white font-bold rounded-xl text-xs">Trang chủ</a>
<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-xs">Khóa Học Enterprise</a>
<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-xs">Lộ Trình Thăng Tiến</a>
<a href="<?php echo esc_url( cvc_recruitments_url() ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-xs">Tuyển Dụng & Bổ Nhiệm</a>
<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-xs">Thi Trắc Nghiệm AI</a>
<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-xs">Kho Tài Liệu</a>
<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-xs">Văn Bản Pháp Luật</a>
<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50 font-semibold rounded-xl text-xs">Kiến Thức Công Vụ</a>
<div class="pt-3 border-t border-slate-100 flex gap-2">
<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex-1 py-2.5 bg-slate-100 text-slate-800 font-bold rounded-xl text-xs text-center">Học Thử</a>
<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="flex-1 py-2.5 bg-navy-950 text-white font-bold rounded-xl text-xs text-center">Đăng Nhập</a>
</div>
</div>
</header>