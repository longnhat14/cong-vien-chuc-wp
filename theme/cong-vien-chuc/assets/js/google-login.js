/* Đăng nhập bằng Google (28/10/2026): nút Google Identity Services -> gửi ID token qua form admin-post (có nonce). */
(function () {
	'use strict';
	var boxes = document.querySelectorAll('[data-cvc-google]');
	if (!boxes.length) { return; }
	function init() {
		if (!window.google || !google.accounts || !google.accounts.id) { return; }
		boxes.forEach(function (box) {
			var form = box.querySelector('form');
			var slot = box.querySelector('.cvc-google__btn');
			google.accounts.id.initialize({
				client_id: box.getAttribute('data-client-id'),
				ux_mode: 'popup',
				auto_select: false,
				context: box.getAttribute('data-context') || 'signin',
				callback: function (r) {
					if (!r || !r.credential) { return; }
					form.querySelector('[name="credential"]').value = r.credential;
					slot.innerHTML = '<p class="cvc-google__wait">Đang đăng nhập bằng Google…</p>';
					form.submit();
				}
			});
			google.accounts.id.renderButton(slot, {
				type: 'standard', theme: 'outline', size: 'large', shape: 'pill', logo_alignment: 'left',
				text: box.getAttribute('data-context') === 'signup' ? 'signup_with' : 'continue_with',
				width: Math.min(400, Math.max(240, slot.clientWidth || 320)), locale: 'vi'
			});
		});
	}
	var s = document.createElement('script');
	s.src = 'https://accounts.google.com/gsi/client';
	s.async = true; s.defer = true;
	s.onload = init;
	s.onerror = function () { boxes.forEach(function (b) { var x = b.querySelector('.cvc-google__btn'); if (x) { x.innerHTML = '<p class="cvc-google__wait">Chưa tải được nút Google. Bạn vẫn có thể đăng nhập bằng email bên dưới.</p>'; } }); };
	document.head.appendChild(s);
})();
