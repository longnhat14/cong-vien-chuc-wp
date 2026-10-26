/* Tải đề trắc nghiệm PDF (26/10/2026): xem trước + tải về. */
(function () {
	'use strict';
	var cfg = window.CVC_PDF || {};
	var modal = document.getElementById('cvcPdfModal');
	if (!modal || !cfg.ajax) { return; }
	var body = document.getElementById('cvcPdfBody');
	var title = document.getElementById('cvcPdfTitle');
	var sub = document.getElementById('cvcPdfSub');
	var variantsBox = document.getElementById('cvcPdfVariants');
	var note = document.getElementById('cvcPdfNote');
	var go = document.getElementById('cvcPdfGo');
	var state = null;
	var lastFocus = null;

	function post(action, data) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', cfg.nonce);
		Object.keys(data).forEach(function (k) { fd.append(k, data[k] == null ? '' : data[k]); });
		return fetch(cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
			return r.json().catch(function () { return { success: false, data: { message: 'Lỗi máy chủ (' + r.status + ').' } }; });
		});
	}

	function setNote(text, isError) {
		note.textContent = text;
		note.classList.toggle('is-error', !!isError);
	}

	function renderVariants(variants) {
		variantsBox.innerHTML = '';
		Object.keys(variants || {}).forEach(function (key) {
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'cvc-pdf-variant' + (key === state.variant ? ' is-on' : '');
			b.setAttribute('role', 'radio');
			b.setAttribute('aria-checked', key === state.variant ? 'true' : 'false');
			b.textContent = variants[key];
			b.addEventListener('click', function () {
				if (state.variant === key) { return; }
				state.variant = key;
				load();
			});
			variantsBox.appendChild(b);
		});
	}

	function load() {
		body.innerHTML = '<p class="cvc-pdf-loading">Đang tải bản xem trước…</p>';
		go.disabled = true;
		post('cvc_pdf_preview', { kind: state.kind, id: state.id, group: state.group, variant: state.variant }).then(function (res) {
			if (!res.success) {
				body.innerHTML = '';
				setNote((res.data && res.data.message) || 'Không mở được bản xem trước.', true);
				return;
			}
			var d = res.data;
			state.variant = d.variant || state.variant;
			title.textContent = d.title || 'Xem trước';
			sub.textContent = (d.exams > 1 ? d.exams + ' đề · ' : '') + d.questions + ' câu' + (d.subtitle ? ' · ' + d.subtitle : '');
			renderVariants(d.variants);
			body.innerHTML = d.html || '';
			body.scrollTop = 0;
			go.disabled = false;
			if (!cfg.logged) {
				go.textContent = 'Đăng nhập để tải về';
				setNote('Xem trước miễn phí. Đăng nhập (miễn phí) để tải file PDF đầy đủ.', false);
			} else if (d.locked) {
				go.textContent = 'Mua gói để tải';
				setNote('Tải đề PDF cần gói ôn thi.', false);
			} else {
				go.textContent = 'Tải về (PDF)';
				setNote('File có logo Công Viên Chức, chữ chìm congvienchuc.com và mã QR làm bài trực tuyến.' + (d.remaining_today != null ? ' Còn ' + d.remaining_today + ' lượt tải hôm nay.' : ''), false);
			}
		}).catch(function () { setNote('Mất kết nối, vui lòng thử lại.', true); });
	}

	function open(btn) {
		lastFocus = btn;
		state = { kind: btn.getAttribute('data-cvc-pdf'), id: btn.getAttribute('data-id'), group: btn.getAttribute('data-group') || '', variant: btn.getAttribute('data-variant') || 'de' };
		modal.hidden = false;
		document.documentElement.classList.add('cvc-pdf-open');
		setNote('', false);
		load();
		setTimeout(function () { go.focus(); }, 30);
	}

	function close() {
		modal.hidden = true;
		document.documentElement.classList.remove('cvc-pdf-open');
		if (lastFocus) { lastFocus.focus(); }
	}

	go.addEventListener('click', function () {
		if (!state) { return; }
		if (!cfg.logged) { window.location.href = cfg.login; return; }
		go.disabled = true;
		go.textContent = 'Đang tạo file…';
		post('cvc_pdf_link', { kind: state.kind, id: state.id, group: state.group, variant: state.variant }).then(function (res) {
			go.disabled = false;
			if (res.success && res.data && res.data.url) {
				go.textContent = 'Tải lại';
				setNote('Đang tải file (mã ' + res.data.code + '). Đề 60 câu mất vài giây.' + (res.data.remaining != null ? ' Còn ' + res.data.remaining + ' lượt hôm nay.' : ''), false);
				window.location.href = res.data.url;
				return;
			}
			if (res.data && res.data.login) { window.location.href = cfg.login; return; }
			go.textContent = 'Tải về (PDF)';
			setNote((res.data && res.data.message) || 'Không tạo được file.', true);
		}).catch(function () { go.disabled = false; go.textContent = 'Tải về (PDF)'; setNote('Mất kết nối, vui lòng thử lại.', true); });
	});

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-cvc-pdf]');
		if (btn) { e.preventDefault(); open(btn); return; }
		if (!modal.hidden && e.target.closest('[data-close]')) { close(); }
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { close(); } });
})();
