/*
 * Ngân hàng câu hỏi (19/10/2026): đổi bộ lọc -> tải lại danh sách Chủ đề / Văn bản / Điều và số câu khớp
 * (AJAX cvc_qb_facets), cập nhật URL và form bắt đầu Ôn tập / Thi thử. Không có JS: form GET vẫn chạy.
 */
(function () {
	'use strict';
	var root = document.querySelector('main.qb');
	var form = document.querySelector('[data-qb-form]');
	if (!root || !form || !window.CVC_QB) { return; }
	root.classList.add('has-js');
	var sum = root.querySelector('.qb-summary');
	var seq = 0;

	function val(name) {
		var el = form.querySelector('[name="' + name + '"]:checked') || form.querySelector('select[name="' + name + '"]');
		return el ? el.value : '';
	}
	function fmt(n) { return Number(n || 0).toLocaleString('vi-VN'); }
	function fill(sel, rows, empty, cur, disabled) {
		var html = '<option value="">' + empty + '</option>';
		(rows || []).forEach(function (r) {
			html += '<option value="' + r.id + '"' + (String(r.id) === String(cur) ? ' selected' : '') + '>' + String(r.name).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }) + ' (' + fmt(r.count) + ')</option>';
		});
		sel.innerHTML = html;
		sel.disabled = !!disabled;
	}
	function syncStart(total) {
		var n = Math.min(Number(val('so-cau') || 20), total || 0);
		var src = val('nguon') || 'all';
		root.querySelectorAll('[data-qb-sync]').forEach(function (h) { var k = h.getAttribute('data-qb-sync'); h.value = val(k) || (k === 'nguon' ? 'all' : (k === 'so-cau' ? '20' : '0')); });
		root.querySelectorAll('[data-qb-start] [data-qb-n]').forEach(function (e) { e.textContent = src === 'all' ? n : Number(val('so-cau') || 20); });
		root.querySelectorAll('[data-qb-start] [data-qb-min]').forEach(function (e) { e.textContent = Math.max(5, src === 'all' ? n : Number(val('so-cau') || 20)); });
		root.querySelectorAll('[data-qb-start] button').forEach(function (b) { b.disabled = src === 'all' && (total || 0) < 5; });
	}
	function load(changed) {
		if (changed === 'mon') { form.querySelector('[name="chu-de"]').value = ''; form.querySelector('[name="van-ban"]').value = ''; }
		if (changed === 'chu-de') { form.querySelector('[name="van-ban"]').value = ''; }
		if (changed === 'mon' || changed === 'chu-de' || changed === 'van-ban') { form.querySelector('[name="dieu"]').value = ''; }
		var q = new URLSearchParams();
		q.set('action', 'cvc_qb_facets');
		['mon', 'chu-de', 'van-ban', 'dieu', 'nguon', 'so-cau'].forEach(function (k) { var v = val(k); if (v) q.set(k, v); });
		var my = ++seq;
		if (sum) sum.classList.add('is-loading');
		fetch(CVC_QB.ajax + '?' + q.toString(), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (my !== seq) return;
				if (sum) sum.classList.remove('is-loading');
				if (!res || !res.success) return;
				var d = res.data.facets || {};
				fill(form.querySelector('[name="chu-de"]'), d.topics, 'Tất cả chủ đề', val('chu-de'));
				fill(form.querySelector('[name="van-ban"]'), d.documents, 'Tất cả văn bản', val('van-ban'));
				var doc = val('van-ban');
				fill(form.querySelector('[name="dieu"]'), d.articles, doc ? 'Tất cả các Điều' : 'Chọn văn bản trước', val('dieu'), !doc);
				var t = root.querySelector('[data-qb-total]'); if (t) t.textContent = fmt(d.total);
				var note = root.querySelector('[data-qb-note]');
				if (note) note.textContent = (val('nguon') || 'all') === 'all'
					? ((d.total || 0) < 5 ? 'Nhóm này chưa đủ 5 câu - hãy mở rộng bộ lọc.' : 'Mỗi lượt lấy ngẫu nhiên số câu đã chọn từ nhóm này.')
					: 'Nguồn câu theo tài khoản được lọc thêm khi bắt đầu (cần đăng nhập).';
				syncStart(d.total || 0);
				if (res.data.url && window.history && history.replaceState) history.replaceState(null, '', res.data.url);
			})
			.catch(function () { if (sum) sum.classList.remove('is-loading'); });
	}
	form.addEventListener('change', function (e) {
		var name = e.target && e.target.getAttribute('data-qb-f');
		if (!name) return;
		if (name === 'nguon' || name === 'so-cau') { syncStart(Number(String((root.querySelector('[data-qb-total]') || {}).textContent || '0').replace(/\D/g, ''))); if (name === 'nguon') load(name); return; }
		load(name);
	});
	form.addEventListener('submit', function (e) { e.preventDefault(); load(''); });
})();
