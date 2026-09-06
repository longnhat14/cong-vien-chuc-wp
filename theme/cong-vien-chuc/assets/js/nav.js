/**
 * Mobile navigation toggle - vanilla JS, không dependency.
 * CSS chỉ hiện nút toggle và collapse nav khi <html> có class "js"
 * (thêm bởi script inline trong header.php) - không JS thì nav luôn
 * hiện đầy đủ, không bao giờ bị ẩn hoàn toàn.
 */
(function () {
	'use strict';

	var toggle = document.querySelector('.site-nav-toggle');
	var nav = document.getElementById('site-nav-menu');

	if (!toggle || !nav) {
		return;
	}

	function closeNav() {
		nav.classList.remove('is-open');
		toggle.setAttribute('aria-expanded', 'false');
	}

	function openNav() {
		nav.classList.add('is-open');
		toggle.setAttribute('aria-expanded', 'true');
	}

	toggle.addEventListener('click', function () {
		if (nav.classList.contains('is-open')) {
			closeNav();
		} else {
			openNav();
		}
	});

	nav.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && nav.classList.contains('is-open')) {
			closeNav();
			toggle.focus();
		}
	});

	// Đóng menu khi người dùng chọn 1 link (điều hướng sang trang khác).
	nav.addEventListener('click', function (event) {
		if (event.target.closest('a')) {
			closeNav();
		}
	});
})();
