<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="bg-navy-950 text-slate-400 pt-16 pb-8 border-t border-navy-800 text-xs">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8">
<!-- Col 1: Brand info (4 cols) -->
<div class="lg:col-span-4 space-y-4">
<div class="flex items-center space-x-3 text-white">
<div class="w-11 h-11 bg-gradient-to-br from-gold-500 to-gold-600 rounded-xl flex items-center justify-center text-navy-950 font-black text-xl shadow-lg">
<i class="fa-solid fa-landmark-dome"></i>
</div>
<div>
<p class="font-black text-lg text-white tracking-tight">CÔNG VIÊN CHỨC PRO</p>
<p class="text-[10px] text-gold-400 uppercase tracking-widest font-bold">Học viện Đào tạo & Phát triển Công vụ</p>
</div>
</div>
<p class="text-slate-400 leading-relaxed text-xs">
Nền tảng số 1 tại Việt Nam về bồi dưỡng kiến thức quản lý nhà nước, thi trắc nghiệm AI công chức - viên chức và kết nối cơ hội bổ nhiệm sự nghiệp công.
</p>
<div class="flex items-center space-x-3 text-slate-300 pt-1">
<span class="text-emerald-400 font-bold"><i class="fa-solid fa-shield-check mr-1"></i> </span>
</div>
</div>
<!-- Col 2: Chương trình học (2 cols) -->
<div class="lg:col-span-2 space-y-3">
<h4 class="text-white font-extrabold text-xs uppercase tracking-wider">Chương Trình Đào Tạo</h4>
<ul class="space-y-2">
<li><a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="hover:text-gold-400 transition-colors">Ngạch Chuyên Viên 2026</a></li>
<li><a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="hover:text-gold-400 transition-colors">Ngạch Chuyên Viên Chính</a></li>
<li><a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="hover:text-gold-400 transition-colors">Chuyên Viên Cao Cấp</a></li>
<li><a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="hover:text-gold-400 transition-colors">Lộ Trình Thăng Tiến</a></li>
<li><a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="hover:text-gold-400 transition-colors">Thi Trắc Nghiệm EXAM OS X</a></li>
</ul>
</div>
<!-- Col 3: Thư viện & Tuyển dụng (2 cols) -->
<div class="lg:col-span-2 space-y-3">
<h4 class="text-white font-extrabold text-xs uppercase tracking-wider">Tài Nguyên & Tuyển Dụng</h4>
<ul class="space-y-2">
<li><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>" class="hover:text-gold-400 transition-colors">Tuyển Dụng 3.240 Xã Phường</a></li>
<li><a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="hover:text-gold-400 transition-colors">Thư Viện Văn Bản Pháp Luật</a></li>
<li><a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="hover:text-gold-400 transition-colors">Chuyên Đề Kiến Thức Công Vụ</a></li>
<li><a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx' ); ?>" download class="hover:text-gold-400 transition-colors">Tải Mẫu 01 (NĐ 138/2020)</a></li>
<li><a href="<?php echo esc_url( cvc_account_url() ); ?>" class="hover:text-gold-400 transition-colors">Cổng Hồ Sơ Học Viên</a></li>
</ul>
</div>
<!-- Col 4: Newsletter & Contact (4 cols) -->
<div class="lg:col-span-4 space-y-4">
<h4 class="text-white font-extrabold text-xs uppercase tracking-wider">Đăng Ký Nhận Bản Tin Công Vụ</h4>
<p class="text-xs text-slate-400">Nhận ngay thông báo đợt thi nâng ngạch và văn bản pháp luật mới nhất tuần này.</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="relative max-w-sm">
<input type="hidden" name="action" value="cvc_newsletter_subscribe">
<?php wp_nonce_field( 'cvc_newsletter_subscribe' ); ?>
<input type="email" name="email" required placeholder="Nhập email cán bộ / cá nhân..." class="w-full pl-4 pr-12 py-3.5 rounded-xl bg-navy-900 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-gold-500">
<button type="submit" class="absolute right-1.5 top-1.5 bottom-1.5 px-4 bg-gold-500 hover:bg-gold-600 text-navy-950 font-bold rounded-lg text-xs transition-colors">
<i class="fa-solid fa-paper-plane"></i>
</button>
</form>
<div class="flex items-center space-x-3 pt-2">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="w-9 h-9 rounded-xl bg-navy-900 border border-slate-700 hover:border-gold-500 text-slate-300 hover:text-gold-400 flex items-center justify-center transition-all"><i class="fa-brands fa-facebook-f"></i></a>
<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="w-9 h-9 rounded-xl bg-navy-900 border border-slate-700 hover:border-gold-500 text-slate-300 hover:text-gold-400 flex items-center justify-center transition-all"><i class="fa-brands fa-youtube"></i></a>
<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="w-9 h-9 rounded-xl bg-navy-900 border border-slate-700 hover:border-gold-500 text-slate-300 hover:text-gold-400 flex items-center justify-center transition-all"><i class="fa-solid fa-comment-dots"></i></a>
</div>
</div>
</div>
<!-- Bottom Copyright Bar -->
<div class="pt-8 border-t border-navy-800 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-500 gap-2">
<p>© 2026 CÔNG VIÊN CHỨC PRO. Tất cả quyền được bảo lưu.</p>
<div class="flex space-x-4">
<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="hover:text-slate-300">Bảo Mật Thông Tin</a>
<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="hover:text-slate-300">Điều Khoản Sử Dụng</a>
<a href="<?php echo esc_url( cvc_search_url() ); ?>" class="hover:text-slate-300">Sơ Đồ Trang Site</a>
</div>
</div>
</div>
</footer>
<!-- SEARCH MODAL -->
<div id="search-modal" class="hidden fixed inset-0 bg-navy-950/80 backdrop-blur-md z-50 flex items-start justify-center pt-20 px-4">
<div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 relative animate-fadeIn">
<button type="button" onclick="toggleSearchModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
<form method="get" action="<?php echo esc_url( home_url( '/tim-kiem/' ) ); ?>" class="flex items-center border-b border-slate-200 pb-3">
<i class="fa-solid fa-magnifying-glass text-gold-500 text-xl mr-3"></i>
<input type="search" name="q" placeholder="Gõ từ khóa cần tìm: Luật công chức, Đề thi chuyên viên..." class="w-full text-base font-bold text-slate-800 placeholder-slate-400 focus:outline-none" autofocus>
</form>
<div class="mt-4 space-y-2 text-xs">
<p class="font-bold text-slate-400 uppercase">Gợi ý tìm kiếm phổ biến:</p>
<div class="flex flex-wrap gap-2">
<a href="<?php echo esc_url( cvc_search_url( 'Bảng lương công chức 2026' ) ); ?>" class="px-3 py-1 bg-slate-100 hover:bg-gold-100 rounded-lg cursor-pointer">Bảng lương công chức 2026</a>
<a href="<?php echo esc_url( cvc_search_url( 'Thi sát hạch Chuyên viên chính' ) ); ?>" class="px-3 py-1 bg-slate-100 hover:bg-gold-100 rounded-lg cursor-pointer">Thi sát hạch Chuyên viên chính</a>
<a href="<?php echo esc_url( cvc_search_url( 'Nghị định 138/2020/NĐ-CP' ) ); ?>" class="px-3 py-1 bg-slate-100 hover:bg-gold-100 rounded-lg cursor-pointer">Nghị định 138/2020/NĐ-CP</a>
</div>
</div>
</div>
</div>
<!-- JAVASCRIPT LOGIC & INTERACTIONS -->
<script>
function toggleNotifications() {
document.getElementById('notif-dropdown').classList.toggle('hidden');
}
function toggleMobileMenu() {
document.getElementById('mobile-menu').classList.toggle('hidden');
}
function toggleSearchModal() {
document.getElementById('search-modal').classList.toggle('hidden');
}
</script>
<?php wp_footer(); ?>
</body>
</html>