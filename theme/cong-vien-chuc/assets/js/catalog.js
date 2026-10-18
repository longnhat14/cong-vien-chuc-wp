/* Danh mục Ôn thi (18/10/2026): lọc / sắp xếp / phân trang qua AJAX, đổi kiểu hiển thị, lưu vào tài khoản. */
(function () {
	'use strict';
	var C = window.CVC_CAT || {};
	var root = document.querySelector('main.ec');
	var form = document.getElementById('ec-form');
	var box = document.getElementById('ec-results');
	if (!root || !form || !box || !C.ajax) { return; }

	var VKEY = 'cvc_cat_view_' + C.kind;
	var timer = null, seq = 0, toastTimer = null;
	var toastEl = root.querySelector('[data-ec-toast]');

	function toast(msg, link) {
		if (!toastEl) { return; }
		toastEl.textContent = msg;
		if (link) {
			var a = document.createElement('a');
			a.href = link.href; a.textContent = link.label;
			toastEl.appendChild(a);
		}
		toastEl.classList.add('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, link ? 4200 : 2600);
	}
	function loginToast(msg) { toast(msg || 'Vui lòng đăng nhập để dùng chức năng này.', { href: C.login, label: 'Đăng nhập' }); }

	/* ---------- Kiểu hiển thị ---------- */
	function getView() { try { return localStorage.getItem(VKEY) || 'list'; } catch (e) { return 'list'; } }
	function applyView() {
		var v = getView();
		var list = box.querySelector('[data-ec-list]');
		if (list) {
			list.classList.toggle('is-compact', v === 'compact');
			list.classList.toggle('is-grid', v === 'grid');
		}
		box.querySelectorAll('.ec-views button').forEach(function (b) {
			var on = b.getAttribute('data-view') === v;
			b.classList.toggle('on', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
	}

	/* ---------- Bộ lọc ---------- */
	function params(page) {
		var fd = new FormData(form);
		var sort = box.querySelector('[data-ec-sort]');
		if (sort) { fd.set('sort', sort.value); }
		var p = new URLSearchParams();
		fd.forEach(function (v, k) { if (String(v).trim() !== '') { p.append(k, v); } });
		if (p.get('sort') === 'recommended') { p.delete('sort'); }
		p.set('page', String(page || 1));
		return p;
	}
	function filterCount() {
		var n = 0;
		form.querySelectorAll('input[type=checkbox]:checked').forEach(function () { n++; });
		form.querySelectorAll('select').forEach(function (s) { if (s.value) { n++; } });
		var q = form.querySelector('[name=q]');
		if (q && q.value.trim()) { n++; }
		return n;
	}
	function syncSavedBtn() {
		var cb = form.querySelector('input[name=saved]');
		var btn = root.querySelector('.ec-hero [data-ec-toggle=saved]');
		if (cb && btn) { btn.classList.toggle('is-on', cb.checked); }
	}

	function load(page, opts) {
		opts = opts || {};
		var p = params(page);
		var my = ++seq;
		var list = box.querySelector('[data-ec-list]');
		if (list) { list.classList.add('is-busy'); }
		var fc = root.querySelector('[data-ec-fcount]');
		if (fc) { fc.textContent = filterCount() + ' bộ lọc'; }
		syncSavedBtn();
		p.set('action', 'cvc_cat_view');
		p.set('kind', C.kind);
		return fetch(C.ajax + '?' + p.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (my !== seq) { return; }
				if (!j || !j.success) { throw new Error('fail'); }
				box.innerHTML = j.data.html;
				applyView();
				if (fc) { fc.textContent = j.data.fcount + ' bộ lọc'; }
				if (!opts.noPush && j.data.url && history.pushState) {
					history.pushState({ ec: 1 }, '', j.data.url);
				}
				if (opts.scroll) {
					var top = box.getBoundingClientRect().top + window.pageYOffset - 110;
					if (window.pageYOffset > top) { window.scrollTo({ top: top, behavior: 'smooth' }); }
				}
			})
			.catch(function () {
				if (my !== seq) { return; }
				var l = box.querySelector('[data-ec-list]');
				if (l) { l.classList.remove('is-busy'); }
				toast('Không tải được danh sách. Vui lòng thử lại.');
			});
	}

	function guardAuth(input) {
		if (input && input.hasAttribute('data-ec-auth') && input.checked && !C.loggedIn) {
			input.checked = false;
			loginToast('Đăng nhập để dùng bộ lọc này.');
			return false;
		}
		return true;
	}

	form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); load(1, { scroll: true }); });
	form.addEventListener('change', function (e) {
		var t = e.target;
		if (t.name === 'q') { return; }
		if (t.type === 'checkbox' && !guardAuth(t)) { return; }
		load(1);
	});
	var qInput = form.querySelector('[name=q]');
	if (qInput) {
		qInput.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(function () { load(1); }, 450);
		});
	}

	function reset() {
		form.querySelectorAll('input[type=checkbox]').forEach(function (c) { c.checked = false; });
		form.querySelectorAll('select').forEach(function (s) { s.value = ''; });
		if (qInput) { qInput.value = ''; }
		load(1);
	}

	function clearOne(key) {
		var parts = key.split(':');
		if (parts[0] === 'type') {
			var c = form.querySelector('input[name="type[]"][value="' + parts[1] + '"]');
			if (c) { c.checked = false; }
		} else {
			var el = form.querySelector('[name="' + parts[0] + '"]');
			if (el) {
				if (el.type === 'checkbox') { el.checked = false; } else { el.value = ''; }
			}
		}
		load(1);
	}

	function toggleFilter(name) {
		var cb = form.querySelector('input[name="' + name + '"]');
		if (!cb) { return; }
		if (!C.loggedIn && cb.hasAttribute('data-ec-auth')) { loginToast('Đăng nhập để xem nội dung đã lưu trong tài khoản.'); return; }
		cb.checked = !cb.checked;
		load(1, { scroll: true });
	}

	/* ---------- Lưu ---------- */
	function save(btn) {
		if (!C.loggedIn) { loginToast('Đăng nhập để lưu vào tài khoản.'); return; }
		var on = btn.getAttribute('aria-pressed') !== 'true';
		var body = new URLSearchParams({ action: 'cvc_cat_save', nonce: C.nonce, type: btn.getAttribute('data-type'), id: btn.getAttribute('data-ec-save'), saved: on ? '1' : '0' });
		btn.disabled = true;
		fetch(C.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
			.then(function (x) {
				btn.disabled = false;
				if (!x.ok || !x.j || !x.j.success) {
					var d = (x.j && x.j.data) || {};
					if (d.login) { loginToast(d.message); } else { toast(d.message || 'Không lưu được, vui lòng thử lại.'); }
					return;
				}
				var unit = btn.getAttribute('data-type') === 'exam' ? 'đề' : (btn.getAttribute('data-type') === 'topic' ? 'chủ đề' : 'mục');
				btn.setAttribute('aria-pressed', on ? 'true' : 'false');
				btn.classList.toggle('is-saved', on);
				btn.textContent = on ? '♥ Đã lưu' : '♡ Lưu ' + unit;
				root.querySelectorAll('[data-ec-saved-count]').forEach(function (c) {
					c.textContent = String(Math.max(0, (parseInt(c.textContent, 10) || 0) + (on ? 1 : -1)));
				});
				toast(x.j.data.message || (on ? 'Đã lưu vào tài khoản.' : 'Đã bỏ lưu.'));
			})
			.catch(function () { btn.disabled = false; toast('Không lưu được, vui lòng thử lại.'); });
	}

	/* ---------- Gắn đề vào Hồ sơ chinh phục ---------- */
	function attach(btn) {
		if (!C.loggedIn) { loginToast('Đăng nhập để gắn đề vào hồ sơ chinh phục kỳ thi.'); return; }
		btn.disabled = true;
		fetch(C.ajax, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ action: 'cvc_cat_attach', nonce: C.nonce, id: btn.getAttribute('data-ec-attach') }) })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				btn.disabled = false;
				var d = (j && j.data) || {};
				if (!j || !j.success) {
					if (d.profile) { toast(d.message || 'Bạn chưa có hồ sơ chinh phục.', { href: d.profile, label: 'Tạo hồ sơ' }); } else { toast(d.message || 'Không gắn được đề, vui lòng thử lại.'); }
					return;
				}
				btn.textContent = '✓ Đã gắn mục tiêu';
				toast(d.message, d.url ? { href: d.url, label: 'Xem hồ sơ' } : null);
			})
			.catch(function () { btn.disabled = false; toast('Không gắn được đề, vui lòng thử lại.'); });
	}

	/* ---------- Sự kiện chung ---------- */
	root.addEventListener('click', function (e) {
		var ft = e.target.closest('[data-ec-ftoggle]');
		if (ft) { var o = form.classList.toggle('is-open'); ft.setAttribute('aria-expanded', o ? 'true' : 'false'); ft.textContent = o ? 'Thu gọn ▴' : 'Mở bộ lọc ▾'; return; }
		var t = e.target.closest('[data-page],[data-ec-reset],[data-ec-clear],[data-ec-toggle],[data-ec-save],[data-ec-attach],[data-view],[data-ec-focus]');
		if (!t || !root.contains(t)) { return; }
		if (t.hasAttribute('data-page')) {
			if (e.ctrlKey || e.metaKey || e.shiftKey) { return; }
			e.preventDefault(); load(parseInt(t.getAttribute('data-page'), 10) || 1, { scroll: true });
		} else if (t.hasAttribute('data-ec-reset')) {
			e.preventDefault(); reset();
		} else if (t.hasAttribute('data-ec-clear')) {
			clearOne(t.getAttribute('data-ec-clear'));
		} else if (t.hasAttribute('data-ec-toggle')) {
			toggleFilter(t.getAttribute('data-ec-toggle'));
		} else if (t.hasAttribute('data-ec-attach')) {
			attach(t);
		} else if (t.hasAttribute('data-ec-save')) {
			save(t);
		} else if (t.hasAttribute('data-view')) {
			try { localStorage.setItem(VKEY, t.getAttribute('data-view')); } catch (err) { /* bỏ qua */ }
			applyView();
		} else if (t.hasAttribute('data-ec-focus')) {
			e.preventDefault();
			if (qInput) { qInput.focus(); qInput.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
		}
	});
	box.addEventListener('change', function (e) {
		if (e.target.matches('[data-ec-sort]')) { e.stopPropagation(); load(1); }
	});

	window.addEventListener('popstate', function () { window.location.reload(); });

	applyView();
}());
