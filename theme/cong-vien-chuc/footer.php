<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
 * Chân trang dùng chung (18/10/2026): giống trang chủ v17 - cvc_chrome_footer() trong inc/chrome.php.
 * Giữ hộp tìm kiếm (nút kính lúp ở đầu trang mở hộp này).
 */
?>
<?php if ( empty( $GLOBALS['cvc_hide_chrome_footer'] ) ) { cvc_chrome_footer(); } // inc/chrome.php - chân trang dùng chung; trang tài khoản ẩn (28/10/2026). ?>
<!-- Hộp tìm kiếm -->
<div id="search-modal" class="hidden dz-modal" role="dialog" aria-modal="true" aria-label="Tìm kiếm">
<div class="dz-modal-box">
<button type="button" onclick="toggleSearchModal()" class="dz-iconbtn dz-modal-close" aria-label="Đóng"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
<p class="dz-kicker">Tra cứu</p>
<form method="get" action="<?php echo esc_url( home_url( '/tim-kiem/' ) ); ?>" class="dz-searchform">
<label class="sr-only" for="dz-search-q">Từ khóa</label>
<input id="dz-search-q" type="search" name="q" data-cvc-suggest placeholder="Luật Cán bộ, công chức; đề thi chuyên viên; tuyển dụng giáo viên…">
<button type="submit" class="dz-btn dz-btn-primary">Tìm</button>
</form>
<p class="dz-muted">Gợi ý:</p>
<div class="dz-chips">
<a href="<?php echo esc_url( cvc_search_url( 'Luật Viên chức' ) ); ?>">Luật Viên chức</a>
<a href="<?php echo esc_url( cvc_search_url( 'Đề thi kiến thức chung' ) ); ?>">Đề thi kiến thức chung</a>
<a href="<?php echo esc_url( cvc_search_url( 'Tuyển dụng giáo viên' ) ); ?>">Tuyển dụng giáo viên</a>
<a href="<?php echo esc_url( cvc_search_url( 'Nghị định 170/2025/NĐ-CP' ) ); ?>">Nghị định 170/2025/NĐ-CP</a>
</div>
</div>
</div>
<script>
function toggleNotifications() {
var el = document.getElementById('notif-dropdown'); if (el) el.classList.toggle('hidden');
}
function toggleMobileMenu() {
var m = document.getElementById('mobile-menu'); if (m) m.classList.toggle('hidden');
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
