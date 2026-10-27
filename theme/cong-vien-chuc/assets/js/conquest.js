/* Hồ sơ chinh phục kỳ thi (18/10/2026): hộp thoại, thao tác AJAX (cvc_cq) và render lại vùng nội dung. */
(function () {
	'use strict';
	var C = window.CVC_CQ || {};
	var root = document.getElementById('cq-root');
	var page = document.querySelector('main.cq');
	if (!root || !page || !C.ajax) { return; }

	var modal = document.getElementById('cq-modal');
	var mTitle = document.getElementById('cq-mtitle');
	var mBody = document.getElementById('cq-mbody');
	var mFoot = document.getElementById('cq-mfoot');
	var mSave = document.getElementById('cq-msave');
	var toastEl = document.getElementById('cq-toast');
	var toastTimer = null, lastFocus = null, onSave = null;

	function goalId() { var d = root.querySelector('.cq-data'); return d ? parseInt(d.getAttribute('data-goal'), 10) || 0 : 0; }

	function toast(msg, link) {
		if (!toastEl) { return; }
		toastEl.textContent = msg || '';
		if (link) { var a = document.createElement('a'); a.href = link.href; a.textContent = link.label; toastEl.appendChild(a); }
		toastEl.classList.add('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, link ? 4500 : 2800);
	}

	function call(act, data) {
		var body = new URLSearchParams({ action: 'cvc_cq', nonce: C.nonce, act: act, goal: String(goalId()) });
		Object.keys(data || {}).forEach(function (k) { if (data[k] !== undefined && data[k] !== null) { body.set(k, data[k]); } });
		return fetch(C.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Máy chủ trả về dữ liệu không hợp lệ.' } }; }); })
			.then(function (j) {
				if (!j || !j.success) {
					var d = (j && j.data) || {};
					if (d.verify && d.redirect) { toast(d.message); setTimeout(function () { location.href = d.redirect; }, 1200); throw new Error('cq'); }
					if (d.login) { toast(d.message, { href: d.login, label: 'Đăng nhập' }); } else { toast(d.message || 'Không thực hiện được, vui lòng thử lại.'); }
					throw new Error('cq');
				}
				return j.data || {};
			});
	}

	function reload(goal, anchor) {
		root.classList.add('is-busy');
		return call('view', { goal: String(goal || goalId()) }).then(function (d) {
			root.innerHTML = d.html || '';
			root.classList.remove('is-busy');
			var g = goal || goalId();
			if (history.replaceState && C.base) {
				var u = new URL(C.base, location.href);
				if (g) { u.searchParams.set('ho-so', g); }
				history.replaceState(null, '', u.toString() + (anchor ? '#' + anchor : ''));
			}
			if (anchor) { var el = document.getElementById(anchor); if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); } }
		}).catch(function () { root.classList.remove('is-busy'); });
	}

	/* ---------- Hộp thoại ---------- */
	function openModal(title, html, saveLabel, fn) {
		lastFocus = document.activeElement;
		mTitle.textContent = title;
		mBody.innerHTML = html;
		onSave = fn || null;
		mFoot.hidden = !fn;
		mSave.textContent = saveLabel || 'Lưu';
		mSave.disabled = false;
		modal.classList.add('open');
		modal.setAttribute('aria-hidden', 'false');
		var f = mBody.querySelector('input,select,button,a');
		(f || mSave).focus();
	}
	function closeModal() {
		modal.classList.remove('open');
		modal.setAttribute('aria-hidden', 'true');
		onSave = null;
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}
	function tpl(id) { var t = document.getElementById(id); return t ? t.innerHTML : ''; }
	function val(sel) { var el = mBody.querySelector(sel); return el ? el.value : ''; }
	function busy(btn, on) { if (btn) { btn.disabled = on; btn.classList.toggle('is-busy', on); } }

	mSave.addEventListener('click', function () { if (onSave) { onSave(); } });
	modal.addEventListener('click', function (e) { if (e.target === modal || e.target.closest('[data-cq-close]')) { closeModal(); } });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) { closeModal(); } });

	function openAdd() {
		openModal('Thêm hồ sơ chinh phục', tpl('cq-tpl-add'), 'Tạo hồ sơ', function () {
			var pane = (mBody.querySelector('.cq-tabs .on') || {}).getAttribute ? mBody.querySelector('.cq-tabs .on').getAttribute('data-cq-tab') : 'saved';
			var data = { target_date: val('#cq-date') };
			if (pane === 'family') {
				data.job_family = val('#cq-fam');
				if (!data.job_family) { toast('Chọn nhóm vị trí cho hồ sơ.'); return; }
				data.province_id = val('#cq-prov');
				data.employment_type = val('#cq-et');
			} else {
				var r = mBody.querySelector('.cq-tabpane:not([hidden]) input[name=cq_rec]:checked');
				if (!r) { toast('Chọn một tin tuyển dụng trong danh sách.'); return; }
				data.id = r.value;
			}
			busy(mSave, true);
			call('create', data).then(function (d) { closeModal(); toast(d.message); reload(d.goal || 0); }).catch(function () { busy(mSave, false); });
		});
		// Chưa lưu tin nào -> mở sẵn thẻ tìm tin.
		if (!mBody.querySelector('[data-pane=saved] input[name=cq_rec]')) {
			var st = mBody.querySelector('[data-cq-tab=search]');
			if (st) { st.click(); }
		}
	}

	function openCustom() {
		var html = tpl('cq-tpl-custom');
		if (!html) { return; }
		openModal('Tùy chỉnh hồ sơ và lộ trình', html, 'Lưu thiết lập', function () {
			busy(mSave, true);
			var regen = mBody.querySelector('#cq-c-regen');
			call('update', { id: String(goalId()), target_date: val('#cq-c-date'), job_family: val('#cq-c-fam'), province_id: val('#cq-c-prov'), regenerate: regen && regen.checked ? '1' : '0' })
				.then(function (d) { closeModal(); toast(d.message); reload(goalId(), 'lo-trinh'); })
				.catch(function () { busy(mSave, false); });
		});
	}

	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

	function coach(btn) {
		busy(btn, true);
		openModal('AI Study Coach — kế hoạch 7 ngày', '<p class="cq-note">Đang lập kế hoạch từ chủ đề yếu, tài liệu và đề trong hồ sơ…</p>', '', null);
		call('coach', { id: String(goalId()) }).then(function (d) {
			busy(btn, false);
			var html = '<p>' + esc(d.summary) + '</p>';
			if (d.steps && d.steps.length) {
				html += '<div class="cq-plan">' + d.steps.map(function (s, i) {
					var t = s.url ? '<a href="' + esc(s.url) + '">' + esc(s.title) + '</a>' : esc(s.title);
					return '<div><b>' + (i + 1) + '. ' + t + '</b><small>' + esc(s.why) + '</small>' + (s.minutes ? '<em>≈ ' + s.minutes + ' phút</em>' : '') + '</div>';
				}).join('') + '</div>';
			}
			html += '<p class="cq-note" style="margin-top:12px">' + (d.source === 'ai' ? 'Gợi ý của AI chỉ dựa trên dữ liệu trong hồ sơ; không thay đổi điểm thi hay điều kiện tuyển dụng.' : 'Kế hoạch sắp theo thứ tự ưu tiên của hệ thống.') + '</p>';
			mBody.innerHTML = html;
		}).catch(function () { busy(btn, false); closeModal(); });
	}

	function exportJson(btn) {
		busy(btn, true);
		call('export', {}).then(function (d) {
			busy(btn, false);
			var blob = new Blob([JSON.stringify(d.data, null, 2)], { type: 'application/json' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url; a.download = d.filename || 'ho-so-chinh-phuc.json';
			document.body.appendChild(a); a.click(); a.remove();
			setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
			toast('Đã xuất tóm tắt hồ sơ.');
		}).catch(function () { busy(btn, false); });
	}

	/* ---------- Sự kiện ---------- */
	page.addEventListener('click', function (e) {
		var t = e.target.closest('[data-cq-open],[data-cq],[data-cq-tab],[data-cq-go],[data-cq-scroll]');
		if (!t || !page.contains(t)) { return; }
		if (t.matches('input[type=checkbox]')) { return; }
		if (t.hasAttribute('data-cq-scroll')) {
			var target = document.querySelector(t.getAttribute('href'));
			if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
			return;
		}
		if (t.hasAttribute('data-cq-tab')) {
			var name = t.getAttribute('data-cq-tab');
			mBody.querySelectorAll('[data-cq-tab]').forEach(function (b) { b.classList.toggle('on', b === t); });
			mBody.querySelectorAll('.cq-tabpane').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== name; });
			return;
		}
		if (t.hasAttribute('data-cq-go')) {
			e.preventDefault(); closeModal(); reload(parseInt(t.getAttribute('data-cq-go'), 10)); return;
		}
		if (t.hasAttribute('data-cq-open')) {
			var w = t.getAttribute('data-cq-open');
			if (w === 'add') { openAdd(); } else if (w === 'custom') { openCustom(); } else if (w === 'switch') { openModal('Chọn hồ sơ chinh phục', tpl('cq-tpl-switch'), '', null); }
			return;
		}
		var act = t.getAttribute('data-cq');
		var id = t.getAttribute('data-id') || '';
		if (act === 'export') { exportJson(t); return; }
		if (act === 'coach') { coach(t); return; }
		if (act === 'search') {
			var q = val('#cq-q').trim();
			if (q.length < 2) { toast('Nhập ít nhất 2 ký tự.'); return; }
			var box = mBody.querySelector('[data-cq-results]');
			busy(t, true);
			call('search', { q: q }).then(function (d) { busy(t, false); box.innerHTML = d.html; }).catch(function () { busy(t, false); });
			return;
		}
		if (act === 'practice') {
			e.preventDefault(); busy(t, true);
			call('practice', { kind: t.getAttribute('data-kind') || 'weak' }).then(function (d) { if (d.url) { location.href = d.url; } }).catch(function () { busy(t, false); });
			return;
		}
		if (act === 'plan') {
			busy(t, true);
			call('plan', { id: String(goalId()) }).then(function (d) { toast(d.message); reload(goalId(), 'viec-can-lam'); }).catch(function () { busy(t, false); });
			return;
		}
		if (act === 'primary' || act === 'archive') {
			if (act === 'archive' && !window.confirm('Lưu trữ hồ sơ này? Lịch sử làm bài vẫn được giữ; hồ sơ khác không bị ảnh hưởng.')) { return; }
			busy(t, true);
			call(act, { id: id }).then(function (d) { closeModal(); toast(d.message); reload(act === 'archive' ? (d.goal || 0) : parseInt(id, 10)); }).catch(function () { busy(t, false); });
			return;
		}
		if (act === 'save' || act === 'reminder') {
			var on = t.getAttribute('data-on') !== '1';
			busy(t, true);
			call(act, { id: id, on: on ? '1' : '0' }).then(function (d) { toast(d.message); reload(goalId()); }).catch(function () { busy(t, false); });
			return;
		}
		if (act === 'item' && t.tagName === 'BUTTON') {
			busy(t, true);
			call('item', { id: id, status: t.getAttribute('data-status') || 'completed' }).then(function () { toast('Đã cập nhật tiến độ.'); reload(goalId()); }).catch(function () { busy(t, false); });
		}
	});

	// Ô đánh dấu việc trong lộ trình.
	page.addEventListener('change', function (e) {
		var c = e.target;
		if (!c.matches('input[type=checkbox][data-cq=item]')) { return; }
		var row = c.closest('.cq-task');
		if (row) { row.classList.toggle('is-done', c.checked); }
		c.disabled = true;
		call('item', { id: c.getAttribute('data-id'), status: c.checked ? 'completed' : 'pending' })
			.then(function () { toast(c.checked ? 'Đã đánh dấu hoàn thành.' : 'Đã đưa việc về chưa hoàn thành.'); reload(goalId()); })
			.catch(function () { c.checked = !c.checked; c.disabled = false; if (row) { row.classList.toggle('is-done', c.checked); } });
	});
	// Enter trong ô tìm tin.
	modal.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && e.target.id === 'cq-q') { e.preventDefault(); var b = mBody.querySelector('[data-cq=search]'); if (b) { b.click(); } }
	});
}());
