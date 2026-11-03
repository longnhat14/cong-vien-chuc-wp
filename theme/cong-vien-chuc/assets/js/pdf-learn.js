/* Học trọn bộ đề PDF ngay trên trang (03/11/2026) - template-pdf-set-detail.php #hoc-bo-de. */
(function () {
	var root = document.getElementById('hoc-bo-de');
	if (!root) { return; }
	var slug = root.getAttribute('data-slug'), logged = root.getAttribute('data-logged') === '1';
	var PER = 20, cur = 1, page = {}, state = {};
	var $ = function (s, el) { return (el || root).querySelector(s); };
	var $$ = function (s, el) { return Array.prototype.slice.call((el || root).querySelectorAll(s)); };
	var key = function (n) { return 'cvc-pql:' + slug + ':' + n; };
	function load(n) { if (state[n]) { return state[n]; } try { state[n] = JSON.parse(localStorage.getItem(key(n)) || '{}') || {}; } catch (e) { state[n] = {}; } return state[n]; }
	function save(n) { try { localStorage.setItem(key(n), JSON.stringify(state[n] || {})); } catch (e) {} }
	function panel(n) { return $('.pql-panel[data-n="' + n + '"]'); }
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

	function mark(q, k, anim) {
		var ans = q.getAttribute('data-ans') || '', ok = ans.indexOf(k) > -1;
		q.classList.add('is-done', ok ? 'is-right' : 'is-wrong');
		$$('.pql-opt', q).forEach(function (b) {
			var bk = b.getAttribute('data-k');
			b.disabled = true;
			if (ans.indexOf(bk) > -1) { b.classList.add('right'); }
			if (bk === k && !ok) { b.classList.add('wrong'); }
			if (bk === k) { b.classList.add('sel'); if (anim) { b.classList.add(ok ? 'is-pop' : 'is-shake'); } }
		});
		var d = $('.pql-ans', q); if (d) { d.open = true; }
		return ok;
	}
	function apply(n) {
		var st = load(n), p = panel(n);
		$$('.pql-q', p).forEach(function (q) { var k = st[q.getAttribute('data-i')]; if (k) { mark(q, k, false); } });
	}
	function stats(n) {
		var st = load(n), p = panel(n), qs = $$('.pql-q', p), done = 0, right = 0;
		qs.forEach(function (q) { var k = st[q.getAttribute('data-i')]; if (k) { done++; if ((q.getAttribute('data-ans') || '').indexOf(k) > -1) { right++; } } });
		return { done: done, right: right, total: qs.length };
	}
	function prog() {
		var s = stats(cur);
		$('[data-pq="done"]').textContent = s.done; $('[data-pq="total"]').textContent = s.total; $('[data-pq="right"]').textContent = s.right;
		$('[data-pq="bar"]').style.width = (s.total ? Math.round(s.done / s.total * 100) : 0) + '%';
	}
	function pager() {
		var qs = $$('.pql-q', panel(cur)), pages = Math.ceil(qs.length / PER), pg = page[cur] || 0;
		qs.forEach(function (q, i) { q.hidden = Math.floor(i / PER) !== pg; });
		$$('.pql-pager').forEach(function (nav) {
			nav.innerHTML = '';
			if (pages < 2) { return; }
			for (var k = 0; k < pages; k++) {
				var b = document.createElement('button'); b.type = 'button'; b.className = 'pql-pg' + (k === pg ? ' is-on' : '');
				b.textContent = 'Câu ' + (k * PER + 1) + '–' + Math.min(qs.length, (k + 1) * PER);
				(function (k) { b.onclick = function () { page[cur] = k; pager(); var top = root.getBoundingClientRect().top + window.scrollY - 70; if (nav.classList.contains('is-bottom') || window.scrollY > top) { window.scrollTo({ top: top, behavior: 'smooth' }); } }; })(k);
				nav.appendChild(b);
			}
		});
	}
	function cta(force) {
		var box = $('.pql-cta'), s = stats(cur), exam = panel(cur).getAttribute('data-exam') || '';
		if (!force && (s.done === 0 || (s.done % PER !== 0 && s.done !== s.total))) { return; }
		var pct = s.done ? Math.round(s.right / s.done * 100) : 0;
		var head = s.done === s.total ? 'Xong đề ' + cur + ': đúng ' + s.right + '/' + s.total + ' câu (' + pct + '%)' : 'Đã làm ' + s.done + ' câu · đúng ' + s.right + ' (' + pct + '%)';
		var html = '<b>' + esc(head) + '</b><p>' + (logged
			? 'Làm đề này trên trang luyện để câu sai tự vào Sổ câu sai và quay lại ôn đúng hạn, hoặc thi thử có tính giờ như thi thật.'
			: 'Đăng nhập miễn phí để lưu kết quả, đưa câu sai vào Sổ câu sai và nhận Bài ôn hôm nay 10 câu mỗi ngày. Bạn vẫn học tiếp được mà không cần đăng nhập.') + '</p><div class="pql-cta-acts">';
		if (!logged) { html += '<a class="pql-btn is-primary" rel="nofollow" href="' + esc(root.getAttribute('data-login')) + '">Đăng nhập miễn phí</a>'; }
		if (exam) {
			html += '<a class="pql-btn' + (logged ? ' is-primary' : '') + '" rel="nofollow" href="' + esc(exam + '?che-do=thi-that') + '">Thi thử đề ' + cur + ' có tính giờ</a>';
			if (logged) { html += '<a class="pql-btn" rel="nofollow" href="' + esc(exam + '?che-do=on-tap') + '">Luyện đề này online</a>'; }
		}
		html += '<button type="button" class="pql-btn is-ghost" data-pq="close">Học tiếp</button></div>';
		box.innerHTML = html; box.hidden = false;
		var c = $('[data-pq="close"]', box); if (c) { c.onclick = function () { box.hidden = true; }; }
	}
	function bind(p) {
		$$('.pql-opt', p).forEach(function (b) {
			b.addEventListener('click', function () {
				var q = b.closest('.pql-q'); if (!q || q.classList.contains('is-done')) { return; }
				var n = Number(p.getAttribute('data-n')), k = b.getAttribute('data-k');
				load(n)[q.getAttribute('data-i')] = k; save(n);
				var ok = mark(q, k, true);
				try { if (navigator.vibrate && window.matchMedia('(pointer:coarse)').matches) { navigator.vibrate(ok ? 15 : [40, 40, 40]); } } catch (e) {}
				prog(); cta(false);
			});
		});
	}
	function renderExam(p, data) {
		var n = Number(p.getAttribute('data-n')), h = '';
		(data.questions || []).forEach(function (qq, i) {
			h += '<article class="pql-q" data-i="' + i + '" data-ans="' + esc(qq.answer) + '" id="d' + n + '-cau-' + (i + 1) + '"><p class="pql-text"><b>Câu ' + esc(qq.n) + '.</b> ' + esc(qq.text) + '</p><div class="pql-opts">';
			(qq.options || []).forEach(function (o) { h += '<button type="button" class="pql-opt" data-k="' + esc(o.key) + '"><span class="pql-k">' + esc(o.key) + '</span><span class="pql-t">' + esc(o.text) + '</span></button>'; });
			h += '</div><details class="pds-ans pql-ans"><summary>Xem đáp án</summary><div><b>Đáp án: ' + esc(qq.answer) + '</b>' + (qq.explanation ? ' – ' + esc(qq.explanation) : '') + (qq.ref ? '<br><i>Căn cứ: ' + esc(qq.ref) + '</i>' : '') + '</div></details></article>';
		});
		p.innerHTML = h || '<p class="pql-loading">Đề này chưa có câu hỏi.</p>';
		p.setAttribute('data-loaded', '1');
		bind(p); apply(n);
	}
	function money(v) { return Number(v || 0).toLocaleString('vi-VN') + ' ₫'; }
	// 03/11/2026: đề cần gói (Quản trị → Thu phí 'pdf_learn') - hộp mở khóa thay cho câu hỏi.
	function renderLock(p, d) {
		var n = Number(p.getAttribute('data-n')), pr = d.prices || {}, from = d.pack && pr.pack ? pr.pack : pr.pass;
		var h = '<div class="pql-lock"><b><i class="fa-solid fa-lock" aria-hidden="true"></i> Đề ' + n + ' cần gói ôn thi</b><p>Bạn đã học miễn phí ' + esc(d.free || 1) + ' đề đầu của bộ. '
			+ (d.pack ? 'Mua ' + esc(d.pack.title) + ' (' + esc(d.pack.exams) + ' đề, dùng mãi) hoặc gói tháng để học trên trang, luyện và thi thử online toàn bộ đề.' : 'Mua gói tháng để học trên trang, luyện và thi thử online toàn bộ đề.')
			+ '</p><div class="pql-cta-acts"><a class="pql-btn is-primary" rel="nofollow" href="' + esc(d.unlock || root.getAttribute('data-unlock')) + '">Mở khóa' + (from ? ' · từ ' + esc(money(from)) : '') + '</a>';
		if (!d.logged) { h += '<a class="pql-btn" rel="nofollow" href="' + esc(root.getAttribute('data-login')) + '">Đã mua? Đăng nhập</a>'; }
		p.innerHTML = h + '<button type="button" class="pql-btn is-ghost" data-pq="back1">Học lại đề 1</button></div></div>';
		var b = $('[data-pq="back1"]', p); if (b) { b.onclick = function () { show(1); }; }
	}
	function show(n) {
		cur = n;
		$$('.pql-tab').forEach(function (t) { var on = Number(t.getAttribute('data-n')) === n; t.classList.toggle('is-on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
		$$('.pql-panel').forEach(function (p) { p.hidden = Number(p.getAttribute('data-n')) !== n; });
		$('.pql-cta').hidden = true;
		var p = panel(n);
		if (p.getAttribute('data-loaded') !== '1') {
			var fd = new FormData(); fd.append('action', 'cvc_pdf_exam'); fd.append('slug', slug); fd.append('n', String(n)); fd.append('back', location.href.split('#')[0] + '#hoc-bo-de');
			fetch(root.getAttribute('data-ajax'), { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
				if (r && r.success && r.data) { renderExam(p, r.data); } else if (r && r.locked && r.data) { renderLock(p, r.data); } else { p.innerHTML = '<p class="pql-loading">Không tải được đề, vui lòng thử lại.</p>'; }
				prog(); pager();
			}).catch(function () { p.innerHTML = '<p class="pql-loading">Lỗi kết nối, vui lòng thử lại.</p>'; });
		}
		prog(); pager();
	}
	$$('.pql-tab').forEach(function (t) { t.addEventListener('click', function () { show(Number(t.getAttribute('data-n'))); }); });
	$('[data-pq="reset"]').addEventListener('click', function () {
		if (!window.confirm('Xóa các đáp án đã chọn của đề ' + cur + ' trên trình duyệt này?')) { return; }
		state[cur] = {}; save(cur);
		var p = panel(cur);
		$$('.pql-q', p).forEach(function (q) { q.classList.remove('is-done', 'is-right', 'is-wrong'); var d = $('.pql-ans', q); if (d) { d.open = false; } });
		$$('.pql-opt', p).forEach(function (b) { b.disabled = false; b.classList.remove('right', 'wrong', 'sel', 'is-pop', 'is-shake'); });
		$('.pql-cta').hidden = true; prog();
	});
	root.classList.add('is-js');
	bind(panel(1)); apply(1); show(1);
	// Mở thẳng tới câu khi URL có #d1-cau-n.
	var m = /^#d(\d+)-cau-(\d+)$/.exec(location.hash || '');
	if (m && Number(m[1]) === 1) { page[1] = Math.floor((Number(m[2]) - 1) / PER); pager(); var el = document.getElementById(location.hash.slice(1)); if (el) { el.scrollIntoView(); } }
})();
