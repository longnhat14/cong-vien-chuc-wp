/* Khung cắt ảnh đại diện / xem trước logo (21/10/2026) - không dùng thư viện ngoài.
 * cover: kéo để chọn vùng, thanh trượt phóng to; khi gửi form, vẽ vùng đã chọn ra ảnh 512×512 JPEG
 * rồi thay vào ô tệp (máy chủ vẫn vẽ lại lần nữa). contain: chỉ xem trước logo. */
(function () {
	'use strict';
	document.querySelectorAll('[data-cvc-crop]').forEach(function (box) {
		var mode = box.getAttribute('data-cvc-crop');
		var input = box.querySelector('[data-crop-input]');
		var stage = box.querySelector('.cvc-crop-stage');
		var canvas = stage.querySelector('canvas');
		var ctx = canvas.getContext('2d');
		var zoom = box.querySelector('[data-crop-zoom]');
		var form = input.form;
		var img = null, scale = 1, base = 1, ox = 0, oy = 0, drag = null, ready = false;
		var S = canvas.width;

		function clamp() {
			var w = img.naturalWidth * base * scale, h = img.naturalHeight * base * scale;
			ox = Math.min(0, Math.max(S - w, ox));
			oy = Math.min(0, Math.max(S - h, oy));
		}
		function draw() {
			ctx.clearRect(0, 0, S, S);
			if (!img) { return; }
			if (mode === 'contain') {
				ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, S, S);
				var k = Math.min((S * 0.88) / img.naturalWidth, (S * 0.88) / img.naturalHeight);
				var w = img.naturalWidth * k, h = img.naturalHeight * k;
				ctx.drawImage(img, (S - w) / 2, (S - h) / 2, w, h);
				return;
			}
			clamp();
			ctx.drawImage(img, ox, oy, img.naturalWidth * base * scale, img.naturalHeight * base * scale);
			ctx.save();
			ctx.globalCompositeOperation = 'destination-in';
			ctx.beginPath(); ctx.arc(S / 2, S / 2, S / 2, 0, Math.PI * 2); ctx.fill();
			ctx.restore();
		}
		input.addEventListener('change', function () {
			ready = false;
			var f = input.files && input.files[0];
			if (!f) { stage.hidden = true; return; }
			if (f.size > 2 * 1024 * 1024) { alertBox('Ảnh tối đa 2 MB.'); input.value = ''; stage.hidden = true; return; }
			var url = URL.createObjectURL(f);
			var im = new Image();
			im.onload = function () {
				img = im; base = Math.max(S / im.naturalWidth, S / im.naturalHeight); scale = 1;
				ox = (S - im.naturalWidth * base) / 2; oy = (S - im.naturalHeight * base) / 2;
				if (zoom) { zoom.value = 1; }
				stage.hidden = false; ready = true; draw();
			};
			im.onerror = function () { alertBox('Không đọc được ảnh. Hãy thử ảnh khác.'); };
			im.src = url;
		});
		function alertBox(msg) {
			var p = box.querySelector('.cvc-crop-err');
			if (!p) { p = document.createElement('p'); p.className = 'cvc-crop-err'; p.setAttribute('role', 'alert'); box.appendChild(p); }
			p.textContent = msg;
		}
		if (zoom) {
			zoom.addEventListener('input', function () {
				if (!img) { return; }
				var c = S / 2, old = scale;
				scale = parseFloat(zoom.value) || 1;
				ox = c - (c - ox) * (scale / old); oy = c - (c - oy) * (scale / old);
				draw();
			});
		}
		if (mode === 'cover') {
			canvas.addEventListener('pointerdown', function (e) { if (!img) { return; } drag = { x: e.clientX, y: e.clientY, ox: ox, oy: oy }; canvas.setPointerCapture(e.pointerId); });
			canvas.addEventListener('pointermove', function (e) { if (!drag) { return; } ox = drag.ox + (e.clientX - drag.x); oy = drag.oy + (e.clientY - drag.y); draw(); });
			canvas.addEventListener('pointerup', function () { drag = null; });
			canvas.addEventListener('pointercancel', function () { drag = null; });
		}
		if (form && mode === 'cover') {
			form.addEventListener('submit', function (e) {
				if (!ready || !img || !window.DataTransfer || !canvas.toBlob) { return; }
				e.preventDefault();
				var out = document.createElement('canvas'); out.width = out.height = 512;
				var k = 512 / S, c2 = out.getContext('2d');
				c2.fillStyle = '#fff'; c2.fillRect(0, 0, 512, 512);
				c2.drawImage(img, ox * k, oy * k, img.naturalWidth * base * scale * k, img.naturalHeight * base * scale * k);
				out.toBlob(function (blob) {
					if (blob) {
						var dt = new DataTransfer();
						dt.items.add(new File([blob], 'avatar.jpg', { type: 'image/jpeg' }));
						input.files = dt.files;
					}
					ready = false;
					form.submit();
				}, 'image/jpeg', 0.92);
			});
		}
	});
})();
