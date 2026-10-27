/*
 * Gợi ý tìm kiếm khi đang gõ (28/10/2026) - ô input[data-cvc-suggest].
 * Chỉ gọi API khi: >= 2 ký tự VÀ (vừa gõ dấu cách = xong 1 chữ, hoặc ngừng gõ 0,6 giây).
 * Hủy yêu cầu cũ khi gõ tiếp; nhớ kết quả trong trang; cụm đã không có kết quả thì gõ thêm chữ cũng không gọi lại.
 */
(function () {
	'use strict';
	var C = window.CVC_SUGGEST;
	if (!C || !window.fetch || !C.api) { return; }
	var memo = {};
	var uid = 0;

	function clean(s) { return String(s || '').replace(/\s+/g, ' ').trim(); }
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function reEsc(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
	function mark(text, q) {
		// Có nguyên cụm thì chỉ tô cụm; không thì tô từng chữ.
		var words = String(text).toLowerCase().indexOf(q.toLowerCase()) >= 0 ? [q] : q.toLowerCase().split(' ').filter(function (w) { return w.length >= 2; });
		if (!words.length) { return esc(text); }
		try {
			var re = new RegExp('(' + words.map(reEsc).join('|') + ')', 'giu');
			return String(text).split(re).map(function (p, i) { return i % 2 ? '<mark>' + esc(p) + '</mark>' : esc(p); }).join('');
		} catch (e) { return esc(text); }
	}
	function urlOf(it) {
		var t = C.urls && C.urls[it.type];
		return t && it.slug ? t.replace('__SLUG__', encodeURIComponent(it.slug)) : '';
	}
	function allUrl(q) { return C.search + (C.search.indexOf('?') < 0 ? '?' : '&') + 'q=' + encodeURIComponent(q); }

	function attach(input) {
		var host = input.form || input.parentNode;
		if (!host) { return; }
		if (getComputedStyle(host).position === 'static') { host.style.position = 'relative'; }
		var box = document.createElement('div');
		box.className = 'cvc-sg';
		box.id = 'cvc-sg-' + (++uid);
		box.hidden = true;
		box.setAttribute('role', 'listbox');
		host.appendChild(box);
		input.setAttribute('autocomplete', 'off');
		input.setAttribute('role', 'combobox');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-controls', box.id);
		input.setAttribute('aria-expanded', 'false');

		var timer = 0, ctrl = null, last = '', active = -1;

		function show(on) { box.hidden = !on; input.setAttribute('aria-expanded', on ? 'true' : 'false'); }
		function links() { return box.querySelectorAll('a.cvc-sg-i, a.cvc-sg-all'); }
		function setActive(i) {
			var l = links();
			if (!l.length) { return; }
			active = (i + l.length) % l.length;
			l.forEach(function (a, k) { a.classList.toggle('is-on', k === active); a.setAttribute('aria-selected', k === active ? 'true' : 'false'); });
			l[active].scrollIntoView({ block: 'nearest' });
		}
		function render(q, d) {
			active = -1;
			var items = (d && d.items) || [];
			var html = '';
			if (!items.length) {
				html = '<div class="cvc-sg-empty">Chưa có tiêu đề nào khớp “' + esc(q) + '”. Thử bỏ bớt từ hoặc dùng số hiệu văn bản (ví dụ 172/2025).</div>';
			} else {
				html = items.map(function (it) {
					var u = urlOf(it);
					if (!u) { return ''; }
					var meta = (it.meta || []).slice(0, 2).join(' · ');
					return '<a class="cvc-sg-i" role="option" href="' + esc(u) + '"><span class="cvc-sg-k">' + esc((C.labels && C.labels[it.type]) || 'Kết quả') + '</span>' +
						'<span class="cvc-sg-t">' + mark(it.title, q) + '</span>' + (meta ? '<span class="cvc-sg-m">' + esc(meta) + '</span>' : '') + '</a>';
				}).join('');
				html += '<a class="cvc-sg-all" role="option" href="' + esc(allUrl(q)) + '">Xem tất cả ' + (d.total > items.length ? Number(d.total).toLocaleString('vi-VN') + ' ' : '') + 'kết quả cho “' + esc(q) + '” →</a>';
			}
			box.innerHTML = html;
			show(true);
		}
		function deadEnd(key) {
			// Cụm trước (đã gõ trọn chữ) không có kết quả -> thêm chữ cũng không có (mọi chữ đều phải khớp).
			for (var k in memo) {
				if (memo[k] && memo[k].total === 0 && k.charAt(k.length - 1) === ' ' && key.indexOf(k) === 0) { return true; }
			}
			return false;
		}
		function run(raw) {
			var q = clean(raw);
			var done = /\s$/.test(raw);
			var key = q.toLowerCase() + (done ? ' ' : '');
			if (q.length < 2) { show(false); last = ''; return; }
			if (key === last && box.innerHTML) { show(true); return; }
			last = key;
			if (memo[key]) { render(q, memo[key]); return; }
			if (deadEnd(key)) { render(q, { items: [], total: 0 }); return; }
			if (ctrl) { ctrl.abort(); }
			ctrl = window.AbortController ? new AbortController() : null;
			box.classList.add('is-busy');
			fetch(C.api + '?q=' + encodeURIComponent(q) + (done ? '' : '&prefix=1'), { credentials: 'omit', signal: ctrl ? ctrl.signal : undefined })
				.then(function (r) { return r.ok ? r.json() : null; })
				.then(function (j) {
					box.classList.remove('is-busy');
					if (!j || !j.success) { return; }
					memo[key] = j.data;
					if (last === key && document.activeElement === input) { render(q, j.data); }
				})
				.catch(function () { box.classList.remove('is-busy'); });
		}

		input.addEventListener('input', function () {
			clearTimeout(timer);
			var v = input.value;
			if (clean(v).length < 2) { show(false); last = ''; if (ctrl) { ctrl.abort(); } return; }
			timer = setTimeout(function () { run(v); }, /\s$/.test(v) ? 0 : 600);
		});
		input.addEventListener('keydown', function (e) {
			if (box.hidden) { return; }
			if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
			else if (e.key === 'Escape') { show(false); }
			else if (e.key === 'Enter' && active >= 0) { var l = links(); if (l[active]) { e.preventDefault(); location.href = l[active].href; } }
		});
		input.addEventListener('focus', function () { if (box.innerHTML && clean(input.value).length >= 2) { show(true); } });
		document.addEventListener('click', function (e) { if (!host.contains(e.target)) { show(false); } });
	}

	document.querySelectorAll('input[data-cvc-suggest]').forEach(attach);
})();
