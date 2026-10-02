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
<div class="relative max-w-sm">
<input type="email" id="newsletter-email" placeholder="Nhập email cán bộ / cá nhân..." class="w-full pl-4 pr-12 py-3.5 rounded-xl bg-navy-900 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-gold-500">
<button onclick="subscribeNewsletter()" class="absolute right-1.5 top-1.5 bottom-1.5 px-4 bg-gold-500 hover:bg-gold-600 text-navy-950 font-bold rounded-lg text-xs transition-colors">
<i class="fa-solid fa-paper-plane"></i>
</button>
</div>
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
<button onclick="toggleSearchModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
<div class="flex items-center border-b border-slate-200 pb-3">
<i class="fa-solid fa-magnifying-glass text-gold-500 text-xl mr-3"></i>
<input type="text" placeholder="Gõ từ khóa cần tìm: Luật công chức, Đề thi chuyên viên..." class="w-full text-base font-bold text-slate-800 placeholder-slate-400 focus:outline-none">
</div>
<div class="mt-4 space-y-2 text-xs">
<p class="font-bold text-slate-400 uppercase">Gợi ý tìm kiếm phổ biến:</p>
<div class="flex flex-wrap gap-2">
<span class="px-3 py-1 bg-slate-100 hover:bg-gold-100 rounded-lg cursor-pointer">Bảng lương công chức 2026</span>
<span class="px-3 py-1 bg-slate-100 hover:bg-gold-100 rounded-lg cursor-pointer">Thi sát hạch Chuyên viên chính</span>
<span class="px-3 py-1 bg-slate-100 hover:bg-gold-100 rounded-lg cursor-pointer">Nghị định 138/2020/NĐ-CP</span>
</div>
</div>
</div>
</div>
<!-- GENERAL ACTION MODAL -->
<div id="action-modal" class="hidden fixed inset-0 bg-navy-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl relative">
<button onclick="closeActionModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
<div class="text-center space-y-3">
<div class="w-12 h-12 bg-gold-100 text-gold-600 rounded-2xl mx-auto flex items-center justify-center text-xl">
<i class="fa-solid fa-circle-check"></i>
</div>
<h3 id="modal-title" class="text-xl font-extrabold text-navy-950">Yêu cầu thành công</h3>
<p id="modal-desc" class="text-xs text-slate-500 leading-relaxed">Bộ phận hỗ trợ đào tạo của Công Viên Chức sẽ liên hệ với đồng chí trong vòng 15 phút làm việc.</p>
<button onclick="closeActionModal()" class="w-full py-3 bg-navy-950 text-white font-bold rounded-xl text-xs mt-2">
Hoàn Tất
</button>
</div>
</div>
</div>
<!-- LOGIN MODAL -->
<div id="login-modal" class="hidden fixed inset-0 bg-navy-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
<div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl relative">
<button onclick="closeLoginModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-xl"></i></button>
<div class="text-center mb-6 space-y-1">
<div class="w-12 h-12 bg-navy-950 text-gold-400 rounded-2xl mx-auto flex items-center justify-center text-xl mb-2">
<i class="fa-solid fa-user-shield"></i>
</div>
<h3 class="text-xl font-extrabold text-navy-950">Cổng Đăng Nhập Cán Bộ</h3>
<p class="text-xs text-slate-500">Hệ thống Đào tạo & Thi Sát hạch Công vụ Số</p>
</div>
<div class="space-y-3 text-xs">
<div>
<label class="block font-bold mb-1 text-slate-700">Mã Cán Bộ / Username / Email công vụ</label>
<input type="text" id="login-user" value="admin" placeholder="admin hoặc admin@congvienchuc.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold">
</div>
<div>
<label class="block font-bold mb-1 text-slate-700">Mật khẩu bảo mật</label>
<input type="password" id="login-pass" value="123" placeholder="123" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold">
</div>
<button onclick="simulateLogin()" class="w-full py-3.5 bg-gradient-to-r from-navy-950 to-navy-800 hover:from-navy-900 hover:to-navy-700 text-white font-bold rounded-xl shadow-md text-xs mt-2">
Đăng Nhập Cổng Công Vụ
</button>
</div>

</div>
</div>
<!-- JAVASCRIPT LOGIC & INTERACTIONS -->
<script>
// Path Tab Data Dictionary
const pathData = {
'cong-chuc': {
title: "Lộ Trình Thi Tuyển Công Chức Mới",
desc: "Dành cho người thi tuyển mới vào hệ thống cơ quan nhà nước, UBND các cấp.",
steps: [
{ step: "Bước 1", name: "Ôn thi Vòng 1: Kiến thức chung (60 câu / 60 phút) + Ngoại ngữ" },
{ step: "Bước 2", name: "Ôn thi Vòng 2: Môn nghiệp vụ chuyên ngành (Tự luận hoặc Phỏng vấn)" },
{ step: "Bước 3", name: "Thực tập công vụ & Hoàn thiện hồ sơ ngạch Chuyên viên" }
]
},
'vien-chuc': {
title: "Lộ Trình Thi Tuyển Viên Chức Sự Nghiệp",
desc: "Dành cho cán bộ ngành Giáo dục, Y tế, Văn hóa & Đơn vị sự nghiệp công lập.",
steps: [
{ step: "Bước 1", name: "Sát hạch Kiến thức chung về Luật Viên chức & Quy chế đơn vị" },
{ step: "Bước 2", name: "Thi thực hành giảng dạy / Thực hành tay nghề chuyên môn" },
{ step: "Bước 3", name: "Xét tuyển & Ký hợp đồng làm việc chức danh nghề nghiệp" }
]
},
'thang-ngach': {
title: "Lộ Trình Thi Thăng Ngạch / Nâng Ngạch",
desc: "Từ Chuyên viên lên Chuyên viên chính & Chuyên viên cao cấp.",
steps: [
{ step: "Bước 1", name: "Cấp chứng chỉ Bồi dưỡng Kiến thức Quản lý Nhà nước tương ứng ngạch" },
{ step: "Bước 2", name: "Ôn tập Đề thi Kiến thức chung Nâng ngạch & Đề án Chuyên môn" },
{ step: "Bước 3", name: "Bảo vệ Đề án trước Hội đồng chấm thi Quốc gia" }
]
},
'lanh-dao': {
title: "Chương Trình Bồi Dưỡng Lãnh Đạo Cấp Phòng/Sở",
desc: "Dành cho cán bộ thuộc quy hoạch Lãnh đạo, Quản lý cơ quan.",
steps: [
{ step: "Bước 1", name: "Đào tạo Kỹ năng Ra quyết định & Quản trị Nhân sự Công" },
{ step: "Bước 2", name: "Huấn luyện Kỹ năng Giải quyết Khủng hoảng Truyền thông" },
{ step: "Bước 3", name: "Cấp Chứng chỉ Đủ điều kiện Bổ nhiệm Lãnh đạo" }
]
}
};
// Render Path Content Function
function renderPathContent(key) {
const data = pathData[key];
const container = document.getElementById('path-content');
let stepsHtml = data.steps.map(s => `
<div class="bg-navy-900/90 p-4 rounded-2xl border border-slate-700/60 flex items-center space-x-4">
<span class="px-3 py-1 bg-gold-500/20 text-gold-400 border border-gold-500/30 font-black text-xs rounded-lg flex-shrink-0">${s.step}</span>
<p class="text-xs font-bold text-slate-200">${s.name}</p>
</div>
`).join('');
container.innerHTML = `
<div class="space-y-2">
<h3 class="text-lg font-black text-gold-400">${data.title}</h3>
<p class="text-xs text-slate-400">${data.desc}</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
${stepsHtml}
</div>
<div class="pt-4 flex justify-end">
<button onclick="openModal('Tư vấn ${data.title}')" class="px-6 py-3 bg-gold-500 hover:bg-gold-600 text-navy-950 font-extrabold text-xs rounded-xl shadow-glow-gold transition-all">
Nhận Đề Đề Xuất Chi Tiết <i class="fa-solid fa-arrow-right ml-1"></i>
</button>
</div>
`;
}
// Switch Path Tab Function
function switchPathTab(key) {
document.querySelectorAll('.path-tab').forEach(btn => {
btn.classList.remove('bg-gold-500', 'text-navy-950', 'font-black', 'shadow-lg');
btn.classList.add('text-slate-300');
});
const activeBtn = document.getElementById(`tab-${key}`);
activeBtn.classList.add('bg-gold-500', 'text-navy-950', 'font-black', 'shadow-lg');
activeBtn.classList.remove('text-slate-300');
renderPathContent(key);
}
// Quiz Answer Logic
function checkQuizAnswer(button, isCorrect) {
const feedback = document.getElementById('quiz-feedback');
feedback.classList.remove('hidden', 'bg-emerald-100', 'text-emerald-800', 'bg-red-100', 'text-red-800');
// Reset all options styling
const options = document.querySelectorAll('#quiz-options button');
options.forEach(opt => {
opt.classList.remove('border-emerald-500', 'bg-emerald-50', 'border-red-500', 'bg-red-50');
});
if (isCorrect) {
button.classList.add('border-emerald-500', 'bg-emerald-50');
feedback.classList.add('bg-emerald-100', 'text-emerald-900', 'border', 'border-emerald-300');
feedback.innerHTML = `
<p class="font-bold"><i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> Chính xác! Đáp án đúng là B (Hằng năm).</p>
<p class="text-[11px] text-slate-700">Giải thích: Theo Khoản 1 Điều 56 Luật Cán bộ, công chức, việc đánh giá công chức được thực hiện hằng năm để làm căn cứ xếp loại chất lượng và quy hoạch, bổ nhiệm.</p>
`;
} else {
button.classList.add('border-red-500', 'bg-red-50');
feedback.classList.add('bg-red-100', 'text-red-900', 'border', 'border-red-300');
feedback.innerHTML = `
<p class="font-bold"><i class="fa-solid fa-circle-xmark text-red-600 mr-1"></i> Chưa chính xác.</p>
<p class="text-[11px] text-slate-700">Đáp án chuẩn pháp luật là <strong>B. Hằng năm</strong> theo Luật Cán bộ, công chức.</p>
`;
}
}
function resetQuiz() {
document.getElementById('quiz-feedback').classList.add('hidden');
const options = document.querySelectorAll('#quiz-options button');
options.forEach(opt => {
opt.classList.remove('border-emerald-500', 'bg-emerald-50', 'border-red-500', 'bg-red-50');
});
}
// Toggle Modals Logic
function toggleNotifications() {
document.getElementById('notif-dropdown').classList.toggle('hidden');
}
function toggleMobileMenu() {
document.getElementById('mobile-menu').classList.toggle('hidden');
}
function toggleSearchModal() {
document.getElementById('search-modal').classList.toggle('hidden');
}
function openModal(title) {
document.getElementById('modal-title').innerText = title;
document.getElementById('action-modal').classList.remove('hidden');
}
function closeActionModal() {
document.getElementById('action-modal').classList.add('hidden');
}
function openLoginModal() {
document.getElementById('login-modal').classList.remove('hidden');
}
function closeLoginModal() {
document.getElementById('login-modal').classList.add('hidden');
}
function simulateLogin() {
const u = document.getElementById('login-user') ? document.getElementById('login-user').value.trim() : 'admin';
const p = document.getElementById('login-pass') ? document.getElementById('login-pass').value.trim() : '123';

if ((u === 'admin' || u === 'admin@congvienchuc.com') && p === '123') {
    alert('✅ Đăng nhập Cổng Học Viên thành công với tài khoản Admin!\n\nChào mừng Quản trị viên! Đang chuyển hướng bạn trực tiếp vào Phòng Thi Thử Trắc Nghiệm AI (36.035+ câu hỏi)...');
    closeLoginModal();
    window.location.href = '<?php echo esc_url( home_url( "/thi-trac-nghiem/" ) ); ?>';
} else {
    alert('❌ Đăng nhập thất bại. Vui lòng sử dụng tài khoản:\nUser: admin (hoặc admin@congvienchuc.com)\nPassword: 123');
}
}

// Search Tag Filler
function setSearchTag(tag) {
document.getElementById('hero-search-query').value = tag;
}
function handleHeroSearch() {
const query = document.getElementById('hero-search-query').value.trim();
if (query) {
openModal('Kết quả tìm kiếm: ' + query);
} else {
alert('Vui lòng nhập từ khóa tìm kiếm khóa học hoặc văn bản!');
}
}
function downloadResource(name) {
openModal('Tải về tài liệu: ' + name);
}
function enrollCourse(courseName) {
openModal('Đăng ký khóa học: ' + courseName);
}
function subscribeNewsletter() {
const email = document.getElementById('newsletter-email').value;
if (email) {
openModal('Đăng ký nhận bản tin thành công cho email: ' + email);
} else {
alert('Vui lòng nhập email hợp lệ!');
}
}
// Initialize Default Tab Content on Page Load
window.addEventListener('DOMContentLoaded', () => {
renderPathContent('cong-chuc');
});
</script>
<?php wp_footer(); ?>
</body>
</html>