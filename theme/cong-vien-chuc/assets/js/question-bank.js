/*
 * Ngân hàng câu hỏi v1 (19/10/2026): tự lọc khi đổi bộ lọc, bộ lọc nâng cao, dạng thẻ / danh sách,
 * luyện nhanh 1 câu (AJAX cvc_qb_q + cvc_qb_check), lưu câu (cvc_qb_bm). Không có JS: form GET + link vẫn chạy.
 */
(function () {
	'use strict';
	var root = document.querySelector('main.qb');
	if (!root || !window.CVC_QB) { return; }
	var CFG = window.CVC_QB;
	var form = root.querySelector('[data-qb-form]');
	var toastEl = root.querySelector('[data-qb-toast]');
	var t0;

	function toast(msg) {
		if (!toastEl) { return; }
		toastEl.textContent = msg;
		toastEl.classList.add('is-show');
		clearTimeout(t0);
		t0 = setTimeout(function () { toastEl.classList.remove('is-show'); }, 2800);
	}
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function ajax(action, data, method) {
		var p = new URLSearchParams();
		p.set('action', action);
		p.set('nonce', CFG.nonce);
		Object.keys(data || {}).forEach(function (k) { p.set(k, data[k]); });
		var opt = { method: method || 'GET', credentials: 'same-origin' };
		var url = CFG.ajax;
		if (opt.method === 'GET') { url += '?' + p.toString(); } else { opt.body = p; }
		return fetch(url, opt).then(function (r) { return r.json().catch(function () { return { success: false }; }); });
	}

	/* ---- Bộ lọc: đổi là lọc ngay; đổi văn bản thì bỏ Điều ---- */
	if (form) {
		root.querySelectorAll('[data-qb-auto]').forEach(function (el) {
			el.addEventListener('change', function () {
				if (el.name === 'van-ban') { var a = form.querySelector('[name="dieu"]'); if (a) { a.value = ''; } }
				if (el.name === 'mon') { ['chu-de', 'van-ban', 'dieu'].forEach(function (n) { var x = form.querySelector('[name="' + n + '"]'); if (x) { x.value = ''; } }); }
				if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
			});
		});
		// Bỏ tham số rỗng khỏi URL cho gọn.
		form.addEventListener('submit', function () {
			form.querySelectorAll('input[name],select[name]').forEach(function (el) { if (!el.value) { el.disabled = true; } });
			var s = document.getElementById('qb-sort');
			if (s && !s.value) { s.disabled = true; }
		});
	}
	// Quay lại trang (bfcache): bật lại các ô đã tắt khi gửi form.
	window.addEventListener('pageshow', function () { root.querySelectorAll('input:disabled,select:disabled').forEach(function (el) { if (!(el.name === 'dieu' && !(form && form.querySelector('[name="van-ban"]').value))) { el.disabled = false; } }); });
	var adv = root.querySelector('[data-qb-adv]');
	var advBox = document.getElementById('qb-adv');
	if (adv && advBox) {
		adv.addEventListener('click', function () {
			var open = advBox.hidden;
			advBox.hidden = !open;
			adv.setAttribute('aria-expanded', open ? 'true' : 'false');
			var s = adv.querySelector('span'); if (s) { s.textContent = open ? '−' : '＋'; }
		});
	}
	root.querySelectorAll('[data-qb-focus]').forEach(function (a) {
		a.addEventListener('click', function () { setTimeout(function () { var q = document.getElementById('qb-q'); if (q) { q.focus({ preventScroll: true }); } }, 250); });
	});
	root.querySelectorAll('[data-qb-count]').forEach(function (sel) {
		sel.addEventListener('change', function () {
			var m = sel.closest('form').querySelector('[data-qb-min]');
			if (m) { m.textContent = Math.max(5, Number(sel.value || 20)); }
		});
	});

	/* ---- Dạng thẻ / danh sách (nhớ trên trình duyệt) ---- */
	var list = root.querySelector('[data-qb-list]');
	function setView(v) {
		if (list) { list.classList.toggle('is-list', v === 'list'); }
		root.querySelectorAll('[data-qb-view]').forEach(function (b) {
			var on = b.getAttribute('data-qb-view') === v;
			b.classList.toggle('is-active', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		try { localStorage.setItem('cvc-qb-view', v); } catch (e) {}
	}
	root.querySelectorAll('[data-qb-view]').forEach(function (b) { b.addEventListener('click', function () { setView(b.getAttribute('data-qb-view')); }); });
	try { if (localStorage.getItem('cvc-qb-view') === 'list') { setView('list'); } } catch (e) {}

	/* ---- Lưu câu ---- */
	root.querySelectorAll('[data-qb-bm]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!CFG.loggedIn) { toast('Đăng nhập để lưu câu hỏi cần xem lại.'); setTimeout(function () { location.href = CFG.login; }, 900); return; }
			var on = !btn.classList.contains('is-saved');
			btn.disabled = true;
			ajax('cvc_qb_bm', { id: btn.getAttribute('data-qb-bm'), on: on ? 1 : 0 }, 'POST').then(function (res) {
				btn.disabled = false;
				if (!res || !res.success) {
					toast((res && res.data && res.data.message) || 'Chưa lưu được, vui lòng thử lại.');
					if (res && res.data && res.data.login) { setTimeout(function () { location.href = CFG.login; }, 900); }
					return;
				}
				btn.classList.toggle('is-saved', on);
				btn.setAttribute('aria-pressed', on ? 'true' : 'false');
				btn.textContent = on ? '★ Đã lưu' : '☆ Lưu';
				var c = root.querySelector('[data-qb-saved-count]');
				if (c) { c.textContent = Math.max(0, Number(String(c.textContent).replace(/\D/g, '') || 0) + (on ? 1 : -1)).toLocaleString('vi-VN'); }
				toast(on ? 'Đã lưu câu hỏi vào danh sách cần ôn lại.' : 'Đã bỏ lưu câu hỏi.');
			}).catch(function () { btn.disabled = false; toast('Lỗi kết nối, vui lòng thử lại.'); });
		});
	});

	/* ---- Luyện nhanh 1 câu ---- */
	function renderResult(box, d) {
		box.querySelectorAll('.qb-opt').forEach(function (o) {
			o.disabled = true;
			var k = o.getAttribute('data-key');
			if (k === d.correct_key) { o.classList.add('is-right'); }
			if (o.classList.contains('is-picked') && k !== d.correct_key) { o.classList.add('is-wrong'); }
		});
		var html = '<p class="qb-verdict ' + (d.is_correct ? 'is-ok">✓ Chính xác' : 'is-no">✕ Chưa đúng') + ' - đáp án đúng: ' + esc(d.correct_key || '?') + '</p>';
		if (d.explanation) { html += '<div class="qb-expl">' + esc(d.explanation) + '</div>'; }
		if (d.excerpt || d.article) {
			html += '<div class="qb-cite"><b>Căn cứ</b>' + (d.excerpt ? '“' + esc(d.excerpt) + '”' : '') +
				(d.article ? '<br><a href="' + esc(d.article.url) + '" target="_blank" rel="noopener">§ ' + esc(d.article.label) + ' — ' + esc(d.article.document) + ' ↗</a>' : '') + '</div>';
		}
		html += d.recorded ? '<p class="qb-pnote">Đã ghi vào tiến độ ôn tập của bạn.</p>'
			: '<p class="qb-pnote"><a href="' + esc(CFG.login) + '">Đăng nhập</a> để lưu kết quả và xem lại câu sai.</p>';
		box.insertAdjacentHTML('beforeend', html);
		var card = box.closest('.qb-q');
		var st = card && card.querySelector('[data-qb-status]');
		if (st && d.recorded) { st.textContent = d.is_correct ? 'Đã trả lời đúng' : 'Đã trả lời sai'; st.className = 'qb-tag ' + (d.is_correct ? 'is-t' : 'is-r'); }
	}
	function check(box, id, key) {
		box.querySelectorAll('.qb-opt').forEach(function (o) { o.disabled = true; if (o.getAttribute('data-key') === key) { o.classList.add('is-picked'); } });
		ajax('cvc_qb_check', { id: id, key: key }, 'POST').then(function (res) {
			if (!res || !res.success) {
				toast((res && res.data && res.data.message) || 'Chưa chấm được, vui lòng thử lại.');
				box.querySelectorAll('.qb-opt').forEach(function (o) { o.disabled = false; o.classList.remove('is-picked'); });
				return;
			}
			renderResult(box, res.data || {});
		}).catch(function () { toast('Lỗi kết nối, vui lòng thử lại.'); box.querySelectorAll('.qb-opt').forEach(function (o) { o.disabled = false; }); });
	}
	root.querySelectorAll('[data-qb-try]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var card = btn.closest('.qb-q');
			var box = card && card.querySelector('[data-qb-box]');
			if (!box) { return; }
			var id = btn.getAttribute('data-qb-try');
			if (!box.hidden && box.getAttribute('data-loaded')) {
				box.hidden = true; btn.setAttribute('aria-expanded', 'false'); btn.textContent = 'Luyện câu này →'; return;
			}
			box.hidden = false;
			btn.setAttribute('aria-expanded', 'true');
			btn.textContent = 'Thu gọn ↑';
			if (box.getAttribute('data-loaded')) { return; }
			box.innerHTML = '<p class="qb-loading">Đang tải phương án…</p>';
			ajax('cvc_qb_q', { id: id }).then(function (res) {
				if (!res || !res.success) { box.innerHTML = '<p class="qb-loading">' + esc((res && res.data && res.data.message) || 'Chưa tải được câu hỏi.') + '</p>'; return; }
				var opts = (res.data && res.data.options) || [];
				box.setAttribute('data-loaded', '1');
				box.innerHTML = '<p class="qb-pnote" style="margin:0 0 8px">Chọn 1 phương án để xem đáp án, giải thích và căn cứ:</p><ul class="qb-opts">' +
					opts.map(function (o) { return '<li><button type="button" class="qb-opt" data-key="' + esc(o.key) + '"><b>' + esc(o.key) + '</b><span>' + esc(o.text) + '</span></button></li>'; }).join('') + '</ul>';
				box.querySelectorAll('.qb-opt').forEach(function (o) { o.addEventListener('click', function () { check(box, id, o.getAttribute('data-key')); }); });
				var first = box.querySelector('.qb-opt'); if (first) { first.focus({ preventScroll: true }); }
			}).catch(function () { box.innerHTML = '<p class="qb-loading">Lỗi kết nối, vui lòng thử lại.</p>'; });
		});
	});
})();
