<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
// 27/10/2026: bỏ thẻ <title> viết cứng (trùng với title-tag của WordPress/SEO - mỗi trang có 2 thẻ title);
// đưa charset/viewport lên trước wp_head để trình duyệt nhận mã hóa sớm.
wp_head();
?>
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

<a class="cvc-skip-link" href="#main">Bỏ qua, tới nội dung chính</a>
<?php
/*
 * Đầu trang dùng chung toàn site (18/10/2026): cùng giao diện trang chủ v17 - xem inc/chrome.php
 * (cvc_chrome_header: menu chính cvc_main_nav_items, tìm kiếm, thông báo, tài khoản, menu di động).
 */
cvc_chrome_header();
?>
