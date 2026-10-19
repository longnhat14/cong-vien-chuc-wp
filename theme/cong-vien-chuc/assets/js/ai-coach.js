/*
 * AI Coach - khung chat nổi trên mọi trang (19/10/2026). Hỏi đáp thật qua admin-ajax cvc_ai_coach
 * -> /api/ai/coach. Cuộc trò chuyện giữ trong sessionStorage (cvc-aic-v1) để không mất khi chuyển trang;
 * đóng tab trình duyệt là xóa. Bất kỳ phần tử nào có data-open-ai sẽ mở khung chat.
 * Chọn AI trả lời (CVC_AIC.engines, từ 2 AI trở lên): đổi bất cứ lúc nào, nhớ lựa chọn
 * (localStorage cvc-aic-engine); mỗi câu trả lời ghi rõ AI nào trả lời.
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
	var engBox = document.getElementById('aiChatEngines');
	var engines = Array.isArray(CFG.engines) ? CFG.engines : [];
	var engine = '';
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
	function label(code) {
		for (var i = 0; i < engines.length; i++) { if (engines[i].code === code) { return engines[i].label; } }
		return '';
	}
	function bubble(text, who, links, by, fb) {
		var b = document.createElement('div');
		b.className = 'aic-msg ' + (who || 'bot');
		if (who === 'note') { b.setAttribute('role', 'status'); }
		if (by) {
			var tag = document.createElement('span');
			tag.className = 'aic-by';
			tag.textContent = fb ? (fb + ' chưa trả lời được · ' + by + ' trả lời thay') : by;
			b.appendChild(tag);
		}
		b.appendChild(document.createTextNode(text));
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
	function remember(text, who, links, by, fb) {
		state.msgs.push({ w: who, t: String(text).slice(0, 4000), l: links || [], by: by || '', fb: fb || '' });
		resetBtn.hidden = false;
		syncChips();
		save();
	}
	// Gợi ý nhanh chỉ hiện khi chưa hỏi câu nào (nhường chỗ cho hội thoại).
	var chips = document.getElementById('aiChatChips');
	function syncChips() { chips.hidden = state.msgs.some(function (m) { return m.w === 'user'; }); }
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
		if (engine) { fd.append('engine', engine); }
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
				var by = engines.length > 1 && d.engine && d.engine.label ? String(d.engine.label) : '';
				var fb = by && d.fallback_from ? String(d.fallback_from) : '';
				bubble(answer, 'bot', links, by, fb); remember(answer, 'bot', links, by, fb);
				state.hist.push({ role: 'user', text: value });
				state.hist.push({ role: 'bot', text: answer.slice(0, 600) });
				save();
			})
			.catch(function () { typing.remove(); bubble('Lỗi kết nối, vui lòng thử lại.', 'bot'); })
			.then(function () { busy = false; send.disabled = false; });
	}

	/* ---------- Chọn AI trả lời ---------- */
	function paintEngines() {
		engBox.querySelectorAll('button').forEach(function (b) {
			var on = b.getAttribute('data-engine') === engine;
			b.setAttribute('aria-checked', on ? 'true' : 'false');
			b.tabIndex = on ? 0 : -1;
		});
	}
	function pickEngine(code, announce) {
		if (!label(code) || code === engine) { return; }
		engine = code;
		try { localStorage.setItem('cvc-aic-engine', code); } catch (e) {}
		paintEngines();
		if (announce) {
			var t = 'Đã chuyển sang ' + label(code) + ' - câu hỏi tiếp theo sẽ do ' + label(code) + ' trả lời.';
			bubble(t, 'note'); remember(t, 'note');
		}
	}
	if (engines.length > 1) {
		var saved = '';
		try { saved = localStorage.getItem('cvc-aic-engine') || ''; } catch (e) {}
		var def = engines.filter(function (e) { return e.default; })[0] || engines[0];
		engine = label(saved) ? saved : def.code;
		var cap = document.createElement('span');
		cap.className = 'aic-engines-cap'; cap.textContent = 'AI trả lời:';
		engBox.appendChild(cap);
		engines.forEach(function (e) {
			var b = document.createElement('button');
			b.type = 'button'; b.className = 'aic-engine'; b.setAttribute('role', 'radio');
			b.setAttribute('data-engine', e.code); b.textContent = e.label;
			b.addEventListener('click', function () { pickEngine(e.code, true); });
			engBox.appendChild(b);
		});
		engBox.addEventListener('keydown', function (e) {
			if (['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp'].indexOf(e.key) < 0) { return; }
			e.preventDefault();
			var codes = engines.map(function (x) { return x.code; });
			var i = codes.indexOf(engine) + (e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : -1);
			pickEngine(codes[(i + codes.length) % codes.length], true);
			var cur = engBox.querySelector('[aria-checked="true"]'); if (cur) { cur.focus(); }
		});
		engBox.hidden = false;
		paintEngines();
	}

	load();
	state.msgs.forEach(function (m) { bubble(m.t, m.w === 'user' ? 'user' : (m.w === 'note' ? 'note' : 'bot'), m.l, m.by, m.fb); });
	resetBtn.hidden = !state.msgs.length;
	syncChips();
	if (state.open && window.matchMedia && window.matchMedia('(min-width: 761px)').matches) { setOpen(true, false); } else { state.open = false; }

	launcher.addEventListener('click', function () { setOpen(panel.hidden); });
	closeBtn.addEventListener('click', function () { setOpen(false); launcher.focus(); });
	resetBtn.addEventListener('click', function () {
		state = { msgs: [], hist: [], open: true };
		box.innerHTML = greeting; resetBtn.hidden = true; syncChips(); save(); input.focus();
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
