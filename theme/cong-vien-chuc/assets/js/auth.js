/* Trang tài khoản (28/10/2026): hiện / ẩn mật khẩu. */
(function () {
	'use strict';
	document.querySelectorAll('[data-au-eye]').forEach(function (btn) {
		var input = document.getElementById(btn.getAttribute('data-au-eye'));
		if (!input) { return; }
		btn.addEventListener('click', function () {
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			btn.setAttribute('aria-pressed', show ? 'true' : 'false');
			btn.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
		});
	});
	// Gửi form: khóa nút để tránh bấm 2 lần, đổi mật khẩu về dạng ẩn.
	document.querySelectorAll('form.au-form').forEach(function (f) {
		f.addEventListener('submit', function () {
			f.querySelectorAll('[data-au-eye]').forEach(function (b) { var i = document.getElementById(b.getAttribute('data-au-eye')); if (i) { i.type = 'password'; } });
			var s = f.querySelector('.au-submit');
			if (s) { setTimeout(function () { s.disabled = true; }, 0); }
		});
	});
})();
