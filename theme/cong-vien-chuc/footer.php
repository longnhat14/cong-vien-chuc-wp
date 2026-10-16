<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Chân trang - giao diện "văn bản" (16/10/2026): nền trắng, kẻ đôi phía
 * trên, chữ Times New Roman, liên kết dạng mục lục.
 */
?>
<footer class="dz-footer">
<div class="dz-wrap">
<div class="dz-footer-grid">
<div class="dz-footer-brand">
<p class="dz-org">CÔNG VIÊN CHỨC</p>
<p class="dz-motto">Ôn thi công chức, viên chức</p>
<p class="dz-footer-note">Nền tảng ôn thi công chức, viên chức: đề thi trắc nghiệm có chấm điểm, văn bản pháp luật, mẫu hồ sơ và tin tuyển dụng tổng hợp từ cổng thông tin chính thức của cơ quan nhà nước.</p>
</div>
<nav class="dz-footer-col" aria-label="Ôn thi">
<h2>Ôn thi</h2>
<ul>
<li><a href="<?php echo esc_url( cvc_exams_url() ); ?>">Đề thi thử</a></li>
<li><a href="<?php echo esc_url( cvc_topics_url() ); ?>">Chủ đề ôn thi</a></li>
<li><a href="<?php echo esc_url( cvc_knowledge_url() ); ?>">Kiến thức trọng tâm</a></li>
<li><a href="<?php echo esc_url( cvc_courses_url() ); ?>">Khóa học</a></li>
</ul>
</nav>
<nav class="dz-footer-col" aria-label="Tuyển dụng và pháp luật">
<h2>Tuyển dụng &amp; pháp luật</h2>
<ul>
<li><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tin tuyển dụng</a></li>
<li><a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Văn bản pháp luật</a></li>
<li><a href="<?php echo esc_url( cvc_documents_url() ); ?>">Mẫu hồ sơ &amp; tài liệu</a></li>
<li><a href="<?php echo esc_url( cvc_account_url() ); ?>">Tài khoản học viên</a></li>
</ul>
</nav>
<div class="dz-footer-col">
<h2>Nhận bản tin qua email</h2>
<p class="dz-footer-note">Thông tin tuyển dụng và văn bản pháp luật mới, gửi định kỳ.</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dz-inline-form">
<input type="hidden" name="action" value="cvc_newsletter_subscribe">
<?php wp_nonce_field( 'cvc_newsletter_subscribe' ); ?>
<label class="sr-only" for="dz-newsletter-email">Email</label>
<input id="dz-newsletter-email" type="email" name="email" required placeholder="Địa chỉ email">
<button type="submit" class="dz-btn dz-btn-primary">Đăng ký</button>
</form>
</div>
</div>
<div class="dz-footer-bottom">
<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Công Viên Chức. Thông tin tuyển dụng, văn bản chỉ mang tính tham khảo — đối chiếu bản gốc tại cơ quan ban hành.</p>
<a href="<?php echo esc_url( cvc_search_url() ); ?>">Tra cứu</a>
</div>
</div>
</footer>
<!-- Hộp tìm kiếm -->
<div id="search-modal" class="hidden dz-modal" role="dialog" aria-modal="true" aria-label="Tìm kiếm">
<div class="dz-modal-box">
<button type="button" onclick="toggleSearchModal()" class="dz-iconbtn dz-modal-close" aria-label="Đóng"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
<p class="dz-kicker">Tra cứu</p>
<form method="get" action="<?php echo esc_url( home_url( '/tim-kiem/' ) ); ?>" class="dz-searchform">
<label class="sr-only" for="dz-search-q">Từ khóa</label>
<input id="dz-search-q" type="search" name="q" placeholder="Luật Cán bộ, công chức; đề thi chuyên viên; tuyển dụng giáo viên…">
<button type="submit" class="dz-btn dz-btn-primary">Tìm</button>
</form>
<p class="dz-muted">Gợi ý:</p>
<div class="dz-chips">
<a href="<?php echo esc_url( cvc_search_url( 'Bảng lương công chức 2026' ) ); ?>">Bảng lương công chức 2026</a>
<a href="<?php echo esc_url( cvc_search_url( 'Thi sát hạch Chuyên viên chính' ) ); ?>">Thi sát hạch Chuyên viên chính</a>
<a href="<?php echo esc_url( cvc_search_url( 'Nghị định 170/2025/NĐ-CP' ) ); ?>">Nghị định 170/2025/NĐ-CP</a>
</div>
</div>
</div>
<script>
function toggleNotifications() {
var el = document.getElementById('notif-dropdown'); if (el) el.classList.toggle('hidden');
}
function toggleMobileMenu() {
document.getElementById('mobile-menu').classList.toggle('hidden');
}
function toggleSearchModal() {
var m = document.getElementById('search-modal');
m.classList.toggle('hidden');
if (!m.classList.contains('hidden')) { var q = document.getElementById('dz-search-q'); if (q) q.focus(); }
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var m = document.getElementById('search-modal'); if (m && !m.classList.contains('hidden')) m.classList.add('hidden'); } });
</script>
<?php wp_footer(); ?>
</body>
</html>
