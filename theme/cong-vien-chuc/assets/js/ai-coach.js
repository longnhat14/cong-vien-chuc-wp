/*
 * AI Coach - khung chat nổi trên mọi trang (19/10/2026). Hỏi đáp thật qua admin-ajax cvc_ai_coach
 * -> /api/ai/coach. Cuộc trò chuyện giữ trong sessionStorage (cvc-aic-v1) để không mất khi chuyển trang;
 * đóng tab trình duyệt là xóa. Bất kỳ phần tử nào có data-open-ai sẽ mở khung chat.
 */
(function () {
	'use strict';
	var root = document.getElementById('cvcAic');
	var CFG = window.CVC_AIC || {};
	if (!root || !CFG.ajax) { return; }
	var launcher = document.getElementById('aiChatLauncher');
	var panel = document.getElementById('aiChatPanel');
	var closeBtn = document.getElementById('aiChatClose');
	var resetBtn = document.getElementById('aiChatReset');
	var box = document.getElementById('aiChatMessages');
	var form = document.getElementById('aiChatForm');
	var input = document.getElementById('aiChatInput');
	var send = form.querySelector('.aic-send');
	var KEY = 'cvc-aic-v1';
	var greeting = box.innerHTML;
	var state = { msgs: [], hist: [], open: false };
	var busy = false;

	function load() {
		try { var s = JSON.parse(sessionStorage.getItem(KEY) || 'null'); if (s && Array.isArray(s.msgs)) { state = { msgs: s.msgs.slice(-30), hist: Array.isArray(s.hist) ? s.hist.slice(-6) : [], open: !!s.open }; } } catch (e) {}
	}
	function save() {
		try { sessionStorage.setItem(KEY, JSON.stringify({ msgs: state.msgs.slice(-30), hist: state.hist.slice(-6), open: state.open })); } catch (e) {}
	}
	function linkUrl(u) {
		u = String(u || '');
		if (/^https?:\/\//i.test(u)) { return u; }
		if (u.charAt(0) === '/' && u.charAt(1) !== '/') { return String(CFG.home || '/').replace(/\/$/, '') + u; }
		return '';
	}
	function bubble(text, who, links) {
		var b = document.createElement('div');
		b.className = 'aic-msg ' + (who || 'bot');
		b.textContent = text;
		if (links && links.length) {
			var wrap = document.createElement('div');
			wrap.className = 'aic-links';
			links.forEach(function (l) {
				var href = linkUrl(l.url);
				if (!href || !l.label) { return; }
				var a = document.createElement('a');
				a.className = 'aic-link'; a.href = href; a.textContent = l.label + ' →';
				wrap.appendChild(a);
			});
			b.appendChild(wrap);
		}
		box.appendChild(b);
		box.scrollTop = box.scrollHeight;
		return b;
	}
	function remember(text, who, links) {
		state.msgs.push({ w: who, t: String(text).slice(0, 4000), l: links || [] });
		resetBtn.hidden = false;
		save();
	}
	function setOpen(open, focus) {
		state.open = open;
		panel.hidden = !open;
		launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
		save();
		if (open) { box.scrollTop = box.scrollHeight; if (focus !== false) { input.focus({ preventScroll: true }); } }
	}
	function ask(text) {
		var value = String(text || '').trim();
		if (!value || busy) { return; }
		if (panel.hidden) { setOpen(true); }
		bubble(value, 'user'); remember(value, 'user');
		input.value = ''; busy = true; send.disabled = true;
		var typing = bubble('AI Coach đang soạn câu trả lời…', 'bot typing');
		var fd = new FormData();
		fd.append('action', 'cvc_ai_coach');
		fd.append('message', value);
		fd.append('history', JSON.stringify(state.hist.slice(-4)));
		fetch(CFG.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				typing.remove();
				if (!res || !res.success) {
					bubble((res && res.data && res.data.message) || 'Chưa trả lời được lúc này, vui lòng thử lại sau.', 'bot');
					return;
				}
				var d = res.data || {};
				var answer = String(d.answer || 'Chưa có câu trả lời phù hợp, bạn thử hỏi cụ thể hơn nhé.');
				var links = Array.isArray(d.links) ? d.links.slice(0, 4) : [];
				bubble(answer, 'bot', links); remember(answer, 'bot', links);
				state.hist.push({ role: 'user', text: value });
				state.hist.push({ role: 'bot', text: answer.slice(0, 600) });
				save();
			})
			.catch(function () { typing.remove(); bubble('Lỗi kết nối, vui lòng thử lại.', 'bot'); })
			.then(function () { busy = false; send.disabled = false; });
	}

	load();
	state.msgs.forEach(function (m) { bubble(m.t, m.w === 'user' ? 'user' : 'bot', m.l); });
	resetBtn.hidden = !state.msgs.length;
	if (state.open && window.matchMedia && window.matchMedia('(min-width: 761px)').matches) { setOpen(true, false); } else { state.open = false; }

	launcher.addEventListener('click', function () { setOpen(panel.hidden); });
	closeBtn.addEventListener('click', function () { setOpen(false); launcher.focus(); });
	resetBtn.addEventListener('click', function () {
		state = { msgs: [], hist: [], open: true };
		box.innerHTML = greeting; resetBtn.hidden = true; save(); input.focus();
	});
	form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
	root.querySelectorAll('[data-aic-prompt]').forEach(function (b) { b.addEventListener('click', function () { ask(b.getAttribute('data-aic-prompt')); }); });
	document.addEventListener('click', function (e) {
		var t = e.target && e.target.closest ? e.target.closest('[data-open-ai]') : null;
		if (!t) { return; }
		e.preventDefault();
		setOpen(true);
		var q = t.getAttribute('data-ai-prompt');
		if (q) { ask(q); }
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !panel.hidden && panel.contains(document.activeElement)) { setOpen(false); launcher.focus(); }
	});
})();
