/*
 * Đăng nhập bằng Google (28/10/2026): Google Identity Services.
 * Nút chính thức của Google được vẽ trong .au-gbtn (trong suốt) phủ lên lớp hiển thị .au-gface theo font trang;
 * Google trả ID token -> form admin-post (có nonce).
 */
(function () {
	'use strict';
	var boxes = document.querySelectorAll('[data-cvc-google]');
	if (!boxes.length) { return; }
	function fail(msg) {
		boxes.forEach(function (b) {
			var w = b.querySelector('.au-gwrap');
			if (w) { w.classList.remove('is-ready'); }
			var n = b.querySelector('.au-gnote');
			if (n) { n.textContent = msg; }
		});
	}
	function init() {
		if (!window.google || !google.accounts || !google.accounts.id) { fail('Chưa tải được đăng nhập Google. Bạn vẫn có thể dùng email bên dưới.'); return; }
		boxes.forEach(function (box) {
			var form = box.querySelector('form');
			var wrap = box.querySelector('.au-gwrap');
			var slot = box.querySelector('.cvc-google__btn');
			google.accounts.id.initialize({
				client_id: box.getAttribute('data-client-id'),
				ux_mode: 'popup',
				auto_select: false,
				context: box.getAttribute('data-context') || 'signin',
				callback: function (r) {
					if (!r || !r.credential) { return; }
					form.querySelector('[name="credential"]').value = r.credential;
					var n = box.querySelector('.au-gnote');
					if (n) { n.textContent = 'Đang đăng nhập bằng Google…'; n.className = 'au-gwait'; }
					wrap.classList.remove('is-ready');
					form.submit();
				}
			});
			wrap.classList.add('is-ready');
			google.accounts.id.renderButton(slot, {
				type: 'standard', theme: 'outline', size: 'large', shape: 'pill', logo_alignment: 'left',
				text: box.getAttribute('data-context') === 'signup' ? 'signup_with' : 'continue_with',
				width: Math.min(400, Math.max(200, Math.round(wrap.clientWidth || 360))), locale: 'vi'
			});
		});
	}
	// Bấm vào lớp hiển thị khi nút Google chưa sẵn sàng.
	boxes.forEach(function (box) {
		var face = box.querySelector('.au-gface');
		if (face) { face.addEventListener('click', function () { if (!box.querySelector('.au-gwrap.is-ready')) { fail('Đang tải đăng nhập Google, vui lòng thử lại sau giây lát.'); } }); }
	});
	var s = document.createElement('script');
	s.src = 'https://accounts.google.com/gsi/client';
	s.async = true; s.defer = true;
	s.onload = init;
	s.onerror = function () { fail('Chưa tải được đăng nhập Google. Bạn vẫn có thể dùng email bên dưới.'); };
	document.head.appendChild(s);
})();
