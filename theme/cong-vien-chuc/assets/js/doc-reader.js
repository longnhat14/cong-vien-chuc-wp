/**
 * Trình đọc bản gốc (01/11/2026, claude/ke-hoach-doc-thu-dep.md đợt 2): Bản gốc / Bản chữ, số trang + thanh kéo,
 * mục lục, ảnh thu nhỏ, tìm trong sách, sách mở 2 trang, giảm sáng, toàn màn hình, phóng to, "Đọc tiếp từ trang N".
 */
(function () {
	'use strict';
	var root = document.getElementById('doc-read');
	if (!root || !root.classList.contains('cvc-rd')) { return; }
	var cfg = {};
	try { cfg = JSON.parse(root.querySelector('.cvc-rd-cfg').textContent) || {}; } catch (e) { cfg = {}; }
	var store = {
		get: function (k) { try { return JSON.parse(window.localStorage.getItem(k)); } catch (e) { return null; } },
		set: function (k, v) { try { window.localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* trình duyệt chặn lưu */ } }
	};
	var KEY = 'cvc-rd:' + cfg.slug;
	var prefs = store.get('cvc-rd-prefs') || {};
	var q = function (s, el) { return (el || root).querySelector(s); };
	var qa = function (s, el) { return Array.prototype.slice.call((el || root).querySelectorAll(s)); };
	var bar = q('.cvc-rd-sticky');
	var pagesView = q('.cvc-rd-pages');
	var textView = q('.cvc-rd-text');
	var figs = qa('.cvc-rd-page');
	var input = q('#cvc-rd-page');
	var range = q('#cvc-rd-range');
	var toast = q('.cvc-rd-toast');
	var total = cfg.total || figs.length;
	var ready = cfg.ready || figs.length;
	var cur = 1;
	var smooth = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

	function say(msg) {
		toast.textContent = msg;
		toast.classList.add('is-on');
		clearTimeout(say.t);
		say.t = setTimeout(function () { toast.classList.remove('is-on'); }, 2400);
	}
	function mode() { return root.getAttribute('data-mode'); }
	function isFull() { return root.classList.contains('is-full') || document.fullscreenElement === root; }
	function hdrH() {
		if (isFull()) { return 0; }
		var h = document.querySelector('header');
		if (!h) { return 0; }
		var cs = window.getComputedStyle(h);
		return (cs.position === 'fixed' || cs.position === 'sticky') ? Math.max(0, h.getBoundingClientRect().bottom) : 0;
	}
	function syncTop() { root.style.setProperty('--rd-top', hdrH() + 'px'); }
	function topOffset() { return hdrH() + bar.offsetHeight + 10; }
	function scrollToEl(el, how) {
		if (!el) { return; }
		var r = el.getBoundingClientRect();
		if (isFull()) {
			root.scrollTo({ top: root.scrollTop + r.top - root.getBoundingClientRect().top - bar.offsetHeight - 10, behavior: how || smooth });
		} else {
			window.scrollTo({ top: window.pageYOffset + r.top - topOffset(), behavior: how || smooth });
		}
	}
	function wide() { return window.innerWidth >= 900; }
	function step() { return pagesView.classList.contains('is-spread') && wide() ? 2 : 1; }

	/* ---------- Trang ---------- */
	function goPage(n, how) {
		n = Math.max(1, Math.min(total, parseInt(n, 10) || 1));
		if (mode() === 'text') {
			var a = document.getElementById('trang-' + n);
			if (a) { scrollToEl(a, how); return; }
			setMode('pages', true);
		}
		if (n > ready) {
			if (cfg.preview) {
				scrollToEl(q('.cvc-rd-next'), how);
				say('Trang ' + n + ' có trong bản đầy đủ.');
			} else {
				say('Trang ' + n + ' đang được chuẩn bị - xem tạm ở Bản chữ.');
			}
			return;
		}
		scrollToEl(document.getElementById('pg-' + n), how);
		setCur(n);
	}
	function setCur(n) {
		if (n === cur) { return; }
		cur = n;
		if (input && document.activeElement !== input) { input.value = n; }
		if (range) { range.value = Math.min(n, ready); }
		save(n);
	}
	var ticking = false;
	function detect() {
		ticking = false;
		var mid = topOffset() + (window.innerHeight - topOffset()) * 0.3;
		if (isFull()) { mid = root.getBoundingClientRect().top + bar.offsetHeight + (window.innerHeight - bar.offsetHeight) * 0.3; }
		if (mode() === 'pages') {
			for (var i = 0; i < figs.length; i++) {
				var r = figs[i].getBoundingClientRect();
				if (r.top <= mid && r.bottom > mid) { setCur(parseInt(figs[i].getAttribute('data-page'), 10)); return; }
			}
		} else {
			var marks = qa('.cvc-rd-text .pg[id^="trang-"]');
			var found = 0;
			for (var j = 0; j < marks.length; j++) {
				if (marks[j].getBoundingClientRect().top <= mid) { found = parseInt(marks[j].id.slice(6), 10); } else { break; }
			}
			if (found) { setCur(found); }
		}
	}
	function onScroll() { if (!ticking) { ticking = true; window.requestAnimationFrame(detect); } }
	window.addEventListener('scroll', onScroll, { passive: true });
	root.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', function () { syncTop(); onScroll(); });

	/* ---------- Đọc tiếp ---------- */
	var sent = 0;
	function save(n) {
		store.set(KEY, { page: n, t: Date.now() });
		if (!cfg.logged || !cfg.nonce || !window.fetch) { return; }
		clearTimeout(save.t);
		save.t = setTimeout(function () {
			if (cur === sent || cur < 1) { return; }
			sent = cur;
			var fd = new window.FormData();
			fd.append('action', 'cvc_doc_progress');
			fd.append('nonce', cfg.nonce);
			fd.append('slug', cfg.slug);
			fd.append('page', String(cur));
			fd.append('total', String(total));
			fd.append('mode', mode());
			window.fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () { sent = 0; });
		}, 4000);
	}
	(function resume() {
		var local = store.get(KEY);
		var n = Math.max(cfg.resume || 0, cfg.logged ? 0 : ((local && local.page) || 0));
		if (cfg.logged && !cfg.resume && local && local.page) { n = local.page; }
		if (n > 1 && n <= total) {
			var box = q('.cvc-rd-resume');
			q('.cvc-rd-resume-n').textContent = n;
			box.hidden = false;
			box.setAttribute('data-page', n);
		}
	})();

	/* ---------- Chế độ ---------- */
	function setMode(m, quiet) {
		if (m === mode()) { return; }
		var keep = cur;
		root.setAttribute('data-mode', m);
		qa('[data-act="mode"]', q('.cvc-rd-seg')).forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-v') === m ? 'true' : 'false'); });
		prefs.mode = m;
		store.set('cvc-rd-prefs', prefs);
		closePanels(true);
		if (!quiet) {
			if (m === 'text') {
				var a = document.getElementById('trang-' + keep);
				scrollToEl(a || textView, 'auto');
			} else {
				goPage(keep, 'auto');
			}
		}
	}

	/* ---------- Bảng: mục lục / ảnh thu nhỏ / tìm ---------- */
	function closePanels(all) {
		qa('.cvc-rd-panel').forEach(function (p) { p.hidden = true; });
		qa('[data-act="panel"]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
		if (all) { /* giữ chỗ */ }
	}
	function openPanel(v, btn) {
		var p = q('[data-panel="' + v + '"]');
		var was = p && !p.hidden;
		closePanels();
		if (!p || was) { return; }
		p.hidden = false;
		btn.setAttribute('aria-expanded', 'true');
		if (v === 'thumbs') {
			qa('img[data-src]', p).forEach(function (im) { im.src = im.getAttribute('data-src'); im.removeAttribute('data-src'); });
			var curBtn = q('[data-page="' + cur + '"]', p);
			qa('button', p).forEach(function (b) { b.classList.toggle('is-cur', b === curBtn); });
			if (curBtn) { curBtn.scrollIntoView({ block: 'nearest' }); }
		}
		if (v === 'find') { var f = q('#cvc-rd-q'); if (f) { f.focus(); } }
	}
	function tocJump(n, label) {
		closePanels();
		if (mode() === 'text') {
			var a = document.getElementById('trang-' + n);
			if (a) { scrollToEl(a); return; }
			var want = fold(label || '');
			var hs = qa('h2, h3, h4', textView);
			for (var i = 0; i < hs.length; i++) {
				if (fold(hs[i].textContent).indexOf(want) !== -1 || want.indexOf(fold(hs[i].textContent)) !== -1) { scrollToEl(hs[i]); return; }
			}
			setMode('pages', true);
		}
		goPage(n);
	}

	/* ---------- Tìm trong sách (trên bản chữ) ---------- */
	function fold(s) {
		s = String(s || '').normalize ? String(s || '').normalize('NFC') : String(s || '');
		var out = '';
		for (var i = 0; i < s.length; i++) {
			var c = s.charAt(i).toLowerCase();
			if (c === 'đ') { c = 'd'; } else if (c.normalize) { c = c.normalize('NFD').charAt(0); }
			out += c;
		}
		return out;
	}
	var index = null;
	function buildIndex() {
		index = [];
		var page = 0;
		var w = document.createTreeWalker(textView, window.NodeFilter.SHOW_ELEMENT, null);
		var el;
		while ((el = w.nextNode())) {
			if (el.classList && el.classList.contains('pg') && /^trang-\d+$/.test(el.id)) { page = parseInt(el.id.slice(6), 10); continue; }
			if (el.matches('p, li, h2, h3, h4, td, th, blockquote, dd, dt') && !el.querySelector('p, li, td, th') && !el.closest('.cvc-gen-toc, .cvc-locked')) {
				var t = (el.textContent || '').replace(/\s+/g, ' ').trim();
				if (t.length > 1) { t = t.normalize ? t.normalize('NFC') : t; index.push({ el: el, page: page, text: t, f: fold(t) }); }
			}
		}
	}
	function runFind(term) {
		var list = q('.cvc-rd-find-list');
		var meta = q('.cvc-rd-find-meta');
		list.textContent = '';
		var f = fold(term.trim());
		if (f.length < 2) { meta.textContent = ''; return; }
		if (!index) { buildIndex(); }
		var hits = 0;
		for (var i = 0; i < index.length && hits < 60; i++) {
			var at = index[i].f.indexOf(f);
			if (at === -1) { continue; }
			hits++;
			var it = index[i];
			var s0 = Math.max(0, at - 50);
			var li = document.createElement('li');
			var b = document.createElement('button');
			b.type = 'button';
			if (it.page) { var pg = document.createElement('span'); pg.className = 'pgn'; pg.textContent = 'Trang ' + it.page; b.appendChild(pg); }
			var sn = document.createElement('span');
			sn.className = 'sn';
			sn.appendChild(document.createTextNode((s0 > 0 ? '…' : '') + it.text.slice(s0, at)));
			var mk = document.createElement('mark');
			mk.textContent = it.text.slice(at, at + f.length);
			sn.appendChild(mk);
			sn.appendChild(document.createTextNode(it.text.slice(at + f.length, at + f.length + 70) + (at + f.length + 70 < it.text.length ? '…' : '')));
			b.appendChild(sn);
			b.addEventListener('click', (function (x) { return function () { findGo(x); }; })(it));
			li.appendChild(b);
			list.appendChild(li);
		}
		meta.textContent = hits ? (hits >= 60 ? 'Hơn 60 kết quả - gõ cụ thể hơn để thu hẹp.' : hits + ' kết quả') + (cfg.preview ? ' trong phần đọc thử.' : '.') : 'Không tìm thấy' + (cfg.preview ? ' trong phần đọc thử.' : '.');
	}
	function findGo(it) {
		closePanels();
		if (mode() === 'pages' && it.page && it.page <= ready) { goPage(it.page); return; }
		if (mode() === 'pages') { setMode('text', true); }
		scrollToEl(it.el);
		it.el.classList.add('cvc-rd-flash');
		setTimeout(function () { it.el.classList.remove('cvc-rd-flash'); }, 2200);
	}
	var fq = q('#cvc-rd-q');
	if (fq) {
		fq.addEventListener('input', function () { clearTimeout(runFind.t); runFind.t = setTimeout(function () { runFind(fq.value); }, 220); });
		fq.addEventListener('keydown', function (e) { if (e.key === 'Enter') { var first = q('.cvc-rd-find-list button'); if (first) { first.click(); } } });
	}

	/* ---------- Toàn màn hình / giảm sáng / 2 trang ---------- */
	function setFull(on) {
		var keep = cur;
		if (on) {
			if (root.requestFullscreen && !/iP(hone|od)/.test(navigator.userAgent)) {
				root.requestFullscreen().catch(function () { root.classList.add('is-full'); document.documentElement.classList.add('cvc-rd-lock'); });
			} else {
				root.classList.add('is-full');
				document.documentElement.classList.add('cvc-rd-lock');
			}
		} else if (document.fullscreenElement === root) {
			document.exitFullscreen();
		} else {
			root.classList.remove('is-full');
			document.documentElement.classList.remove('cvc-rd-lock');
		}
		setTimeout(function () { syncFullBtn(); syncTop(); goPage(keep, 'auto'); }, 120);
	}
	function syncFullBtn() {
		var b = q('[data-act="full"]');
		var on = isFull();
		b.setAttribute('aria-pressed', on ? 'true' : 'false');
		q('i', b).className = 'fa-solid ' + (on ? 'fa-compress' : 'fa-expand');
		q('span', b).textContent = on ? 'Thoát' : 'Toàn màn hình';
	}
	document.addEventListener('fullscreenchange', function () { syncFullBtn(); syncTop(); });
	function setDim(on) {
		root.classList.toggle('is-dim', on);
		q('[data-act="dim"]').setAttribute('aria-pressed', on ? 'true' : 'false');
		prefs.dim = on;
		store.set('cvc-rd-prefs', prefs);
	}
	function setSpread(on) {
		var keep = cur;
		pagesView.classList.toggle('is-spread', on);
		q('[data-act="spread"]').setAttribute('aria-pressed', on ? 'true' : 'false');
		prefs.spread = on;
		store.set('cvc-rd-prefs', prefs);
		goPage(keep, 'auto');
	}

	/* ---------- Phóng to ---------- */
	var dlg = q('#cvc-rd-dlg');
	function zoom(src, label) {
		if (!dlg || typeof dlg.showModal !== 'function') { window.open(src, '_blank', 'noopener'); return; }
		var im = q('img', dlg);
		im.src = src;
		im.alt = label || '';
		dlg.showModal();
	}
	if (dlg) {
		q('.cvc-rd-close', dlg).addEventListener('click', function () { dlg.close(); });
		dlg.addEventListener('click', function (e) { if (e.target === dlg) { dlg.close(); } });
	}

	/* ---------- Sự kiện ---------- */
	root.addEventListener('click', function (e) {
		var t = e.target.closest('[data-act], .cvc-rd-zoom, .cvc-rd-toc a, .cvc-rd-thumbs button');
		if (!t || !root.contains(t)) { return; }
		if (t.classList.contains('cvc-rd-zoom')) { zoom(t.getAttribute('data-src'), t.getAttribute('aria-label')); return; }
		if (t.matches('.cvc-rd-toc a')) { e.preventDefault(); tocJump(parseInt(t.getAttribute('data-page'), 10), q('.t', t).textContent); return; }
		if (t.matches('.cvc-rd-thumbs button')) { closePanels(); goPage(parseInt(t.getAttribute('data-page'), 10)); return; }
		var act = t.getAttribute('data-act');
		var v = t.getAttribute('data-v');
		if (act === 'mode') { setMode(v); } else if (act === 'prev') { goPage(cur - step()); } else if (act === 'next') { goPage(cur + step()); } else if (act === 'panel') { openPanel(v, t); } else if (act === 'full') { setFull(!isFull()); } else if (act === 'dim') { setDim(!root.classList.contains('is-dim')); } else if (act === 'spread') { setSpread(!pagesView.classList.contains('is-spread')); } else if (act === 'resume') {
			var box = q('.cvc-rd-resume');
			box.hidden = true;
			goPage(parseInt(box.getAttribute('data-page'), 10));
		} else if (act === 'resume-hide') { q('.cvc-rd-resume').hidden = true; }
	});
	if (input) {
		input.addEventListener('change', function () { goPage(input.value); });
		input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); goPage(input.value); } });
	}
	if (range) {
		range.addEventListener('input', function () { say('Trang ' + range.value + ' / ' + total); });
		range.addEventListener('change', function () { goPage(range.value); });
	}
	document.addEventListener('keydown', function (e) {
		if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || mode() !== 'pages' || (dlg && dlg.open)) { return; }
		var tag = (e.target && e.target.tagName) || '';
		if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (e.target && e.target.isContentEditable)) { return; }
		if (e.key === 'Escape' && root.classList.contains('is-full')) { setFull(false); return; }
		var r = root.getBoundingClientRect();
		if (!isFull() && (r.bottom < 0 || r.top > window.innerHeight)) { return; }
		if (e.key === 'ArrowRight') { e.preventDefault(); goPage(cur + step()); } else if (e.key === 'ArrowLeft') { e.preventDefault(); goPage(cur - step()); }
	});
	document.addEventListener('click', function (e) { if (!root.contains(e.target)) { closePanels(); } });
	var warned = false;
	figs.forEach(function (f) {
		var im = q('img', f);
		im.addEventListener('error', function () { if (!warned) { warned = true; say('Phiên xem trang đã hết hạn - tải lại trang để xem tiếp.'); } });
	});

	/* ---------- Khởi tạo ---------- */
	syncTop();
	if (prefs.dim) { setDim(true); }
	if (prefs.spread && wide()) { pagesView.classList.add('is-spread'); q('[data-act="spread"]').setAttribute('aria-pressed', 'true'); }
	if (prefs.mode === 'text') { setMode('text', true); }
	detect();
})();
