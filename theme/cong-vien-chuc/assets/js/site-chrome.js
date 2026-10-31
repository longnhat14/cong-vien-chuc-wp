/* Header dùng chung (18/10/2026): menu di động, menu "Ôn thi" trên màn hình cảm ứng, nút tìm kiếm. */
(function () {
	'use strict';
	var toggle = document.getElementById('chMenuToggle');
	var nav = document.getElementById('chNavLinks');

	function setOpen(open) {
		if (!toggle || !nav) { return; }
		nav.classList.toggle('open', open);
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		toggle.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
	}

	if (toggle && nav) {
		toggle.addEventListener('click', function (e) { e.stopPropagation(); setOpen(!nav.classList.contains('open')); });
		nav.querySelectorAll('a').forEach(function (a) {
			// Mục cha có menu lớn (menu v3): chạm để mở/đóng menu con, không đóng cả menu.
			if (a.classList.contains('ch-nav-parent') && a.parentElement.classList.contains('has-mg')) { return; }
			a.addEventListener('click', function () { setOpen(false); });
		});
	}

	// Màn hình cảm ứng (không có hover), rộng hơn di động: chạm lần đầu vào "Ôn thi" mở menu con.
	document.querySelectorAll('.ch-nav-group>.ch-nav-parent').forEach(function (p) {
		p.addEventListener('click', function (e) {
			var g = p.parentElement;
			if (g.classList.contains('has-mg') && window.matchMedia('(max-width:1100px)').matches) {
				e.preventDefault();
				var op = !g.classList.contains('open');
				g.classList.toggle('open', op); p.setAttribute('aria-expanded', op ? 'true' : 'false');
				return;
			}
			if (window.matchMedia('(max-width:760px)').matches) { return; }
			if (window.matchMedia('(hover:none)').matches && !g.classList.contains('open')) {
				e.preventDefault(); e.stopPropagation();
				g.classList.add('open'); p.setAttribute('aria-expanded', 'true');
			}
		});
	});

	// Menu lớn v3 trên máy tính: đặt mũi nhọn của khung đúng dưới mục cha.
	document.querySelectorAll('.ch-nav-group.has-mg').forEach(function (g) {
		function place() {
			var mg = g.querySelector('.ch-mg'), panel = g.querySelector('.ch-mg-panel'), p = g.querySelector('.ch-nav-parent');
			if (!mg || !panel || window.matchMedia('(max-width:1100px)').matches) { return; }
			window.requestAnimationFrame(function () {
				var pr = panel.getBoundingClientRect(), ar = p.getBoundingClientRect();
				if (!pr.width) { return; }
				var x = Math.max(22, Math.min(pr.width - 30, ar.left + ar.width / 2 - pr.left - 7));
				panel.style.setProperty('--mg-caret', x + 'px'); mg.classList.add('has-caret');
			});
		}
		g.addEventListener('mouseenter', place); g.addEventListener('focusin', place);
	});

	document.addEventListener('click', function (e) {
		document.querySelectorAll('.ch-nav-group.open').forEach(function (g) {
			if (!g.contains(e.target)) { g.classList.remove('open'); g.querySelector('.ch-nav-parent').setAttribute('aria-expanded', 'false'); }
		});
		if (nav && toggle && nav.classList.contains('open') && !nav.contains(e.target) && !toggle.contains(e.target)) { setOpen(false); }
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { setOpen(false); } });

	// Nút tìm kiếm: mở hộp tìm kiếm nếu trang có (footer.php), không thì chuyển tới /tim-kiem/.
	document.querySelectorAll('[data-ch-search]').forEach(function (a) {
		a.addEventListener('click', function (e) {
			if (document.getElementById('search-modal') && typeof window.toggleSearchModal === 'function') {
				e.preventDefault(); window.toggleSearchModal();
			}
		});
	});
}());
