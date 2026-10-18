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
		nav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
	}

	// Màn hình cảm ứng (không có hover), rộng hơn di động: chạm lần đầu vào "Ôn thi" mở menu con.
	document.querySelectorAll('.ch-nav-group>.ch-nav-parent').forEach(function (p) {
		p.addEventListener('click', function (e) {
			var g = p.parentElement;
			if (window.matchMedia('(max-width:760px)').matches) { return; }
			if (window.matchMedia('(hover:none)').matches && !g.classList.contains('open')) {
				e.preventDefault(); e.stopPropagation();
				g.classList.add('open'); p.setAttribute('aria-expanded', 'true');
			}
		});
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
