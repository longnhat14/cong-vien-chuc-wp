/* Sách 3D (01/11/2026): lật bìa xem mục lục, nghiêng nhẹ theo con trỏ. */
(function () {
	'use strict';
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	document.querySelectorAll('[data-bk]').forEach(function (bk) {
		var btn = bk.querySelector('[data-bk-toggle]');
		var label = bk.querySelector('[data-bk-label]');
		var links = bk.querySelectorAll('[data-bk-link]');
		var stage = bk.querySelector('.bk-stage');
		function set(open) {
			bk.classList.toggle('is-open', open);
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			label.textContent = open ? 'Gập bìa lại' : (label.getAttribute('data-closed') || 'Lật bìa xem mục lục');
			links.forEach(function (a) { a.tabIndex = open ? 0 : -1; });
			bk.style.removeProperty('--bk-ry');
		}
		if (btn) {
			btn.addEventListener('click', function () { set(!bk.classList.contains('is-open')); });
		}
		if (stage) {
			stage.addEventListener('click', function (e) {
				if (!e.target.closest('a')) { set(!bk.classList.contains('is-open')); }
			});
			if (!reduce) {
				stage.addEventListener('pointermove', function (e) {
					if (bk.classList.contains('is-open') || e.pointerType !== 'mouse') { return; }
					var r = stage.getBoundingClientRect();
					var x = (e.clientX - r.left) / r.width - 0.5;
					bk.style.setProperty('--bk-ry', (24 + x * 16).toFixed(1) + 'deg');
				});
				stage.addEventListener('pointerleave', function () { bk.style.removeProperty('--bk-ry'); });
			}
		}
	});
}());
