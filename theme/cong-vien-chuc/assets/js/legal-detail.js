/*
 * Văn bản pháp luật - trang chi tiết v2 (19/10/2026).
 * Tra cứu trong văn bản, cỡ chữ, thu gọn, mục lục, nội dung liên quan theo Điều, xem tại PDF,
 * sao chép / chia sẻ / in, lưu đánh dấu (tài khoản). Dữ liệu: window.CVC_LD (chỉ dữ liệu thật từ API).
 */
(function () {
	'use strict';
	var root = document.querySelector('main.ld');
	if (!root) { return; }
	var D = window.CVC_LD || {};
	var $ = function (s, c) { return (c || root).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); };

	/* ---------- Thông báo ---------- */
	var toastEl = document.getElementById('ld-toast'), toastT;
	function toast(msg) {
		if (!toastEl) { return; }
		toastEl.textContent = msg;
		toastEl.classList.add('is-show');
		clearTimeout(toastT);
		toastT = setTimeout(function () { toastEl.classList.remove('is-show'); }, 2600);
	}
	function copyText(text, okMsg) {
		var done = function () { toast(okMsg || 'Đã sao chép.'); };
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done, function () { fallback(); });
		} else { fallback(); }
		function fallback() {
			var ta = document.createElement('textarea');
			ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.opacity = '0';
			document.body.appendChild(ta); ta.select();
			try { document.execCommand('copy'); done(); } catch (e) { toast('Trình duyệt chặn sao chép — hãy chọn và sao chép thủ công.'); }
			document.body.removeChild(ta);
		}
	}
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

	/* ---------- Sao chép, chia sẻ, in ---------- */
	$$('[data-ld-copy]').forEach(function (b) {
		b.addEventListener('click', function () { copyText(b.getAttribute('data-ld-copy'), 'Đã sao chép: ' + b.getAttribute('data-ld-copy').slice(0, 60)); });
	});
	var printBtn = $('[data-ld-print]');
	if (printBtn) { printBtn.addEventListener('click', function () { window.print(); }); }
	var shareBtn = $('[data-ld-share]');
	if (shareBtn) {
		shareBtn.addEventListener('click', function () {
			var url = D.url || location.href.split('#')[0];
			if (navigator.share) {
				navigator.share({ title: document.title, url: url }).catch(function () {});
			} else { copyText(url, 'Đã sao chép liên kết văn bản.'); }
		});
	}

	/* ---------- Cỡ chữ & thu gọn ---------- */
	var fs = 15;
	try { fs = parseInt(localStorage.getItem('cvc_ld_fs') || '15', 10) || 15; } catch (e) {}
	function applyFs() { root.style.setProperty('--fs', fs + 'px'); }
	applyFs();
	$$('[data-ld-font]').forEach(function (b) {
		b.addEventListener('click', function () {
			fs = Math.max(13, Math.min(22, fs + parseInt(b.getAttribute('data-ld-font'), 10)));
			applyFs();
			try { localStorage.setItem('cvc_ld_fs', String(fs)); } catch (e) {}
			toast('Cỡ chữ: ' + fs + 'px');
		});
	});
	var compactBtn = $('[data-ld-compact]');
	if (compactBtn) {
		compactBtn.addEventListener('click', function () {
			var on = !root.classList.contains('is-compact');
			root.classList.toggle('is-compact', on);
			compactBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
			compactBtn.textContent = on ? '↕ Mở rộng' : '↕ Thu gọn';
		});
	}

	/* ---------- Mục lục ---------- */
	var tocPanel = $('.ld-tocpanel'), tocBtn = $('[data-ld-toc]');
	if (tocPanel && tocBtn) {
		if (window.matchMedia('(max-width:760px)').matches) {
			tocPanel.classList.add('is-closed'); tocBtn.setAttribute('aria-expanded', 'false');
		}
		tocBtn.addEventListener('click', function () {
			var closed = tocPanel.classList.toggle('is-closed');
			tocBtn.setAttribute('aria-expanded', closed ? 'false' : 'true');
		});
	}
	// Đánh dấu Điều đang đọc trong mục lục + mục điều hướng nhanh.
	var tocLinks = {};
	$$('#ld-toc a').forEach(function (a) { tocLinks[a.getAttribute('href').slice(1)] = a; });
	var jumpLinks = {};
	$$('.ld-jumpnav a').forEach(function (a) { jumpLinks[a.getAttribute('href').slice(1)] = a; });
	if ('IntersectionObserver' in window) {
		var curToc = null;
		var io = new IntersectionObserver(function (ents) {
			ents.forEach(function (en) {
				if (!en.isIntersecting) { return; }
				var a = tocLinks[en.target.id];
				if (a && a !== curToc) {
					if (curToc) { curToc.classList.remove('is-active'); }
					a.classList.add('is-active'); curToc = a;
					var list = document.getElementById('ld-toc');
					if (list && list.scrollHeight > list.clientHeight) {
						var top = a.offsetTop - list.offsetTop;
						if (top < list.scrollTop || top > list.scrollTop + list.clientHeight - 30) { list.scrollTop = top - 40; }
					}
				}
			});
		}, { rootMargin: '-110px 0px -65% 0px' });
		$$('.ld-article[id]').forEach(function (el) { io.observe(el); });
		var curJump = null;
		var io2 = new IntersectionObserver(function (ents) {
			ents.forEach(function (en) {
				if (!en.isIntersecting) { return; }
				var a = jumpLinks[en.target.id];
				if (a) { if (curJump) { curJump.classList.remove('is-active'); } a.classList.add('is-active'); curJump = a; }
			});
		}, { rootMargin: '-120px 0px -70% 0px' });
		Object.keys(jumpLinks).forEach(function (id) { var el = document.getElementById(id); if (el) { io2.observe(el); } });
	}

	/* ---------- Tra cứu trong văn bản ---------- */
	var findInput = document.getElementById('ld-find');
	var findCount = document.getElementById('ld-findcount');
	var findEmpty = document.getElementById('ld-find-empty');
	var nextBtn = $('[data-ld-find-next]');
	var articles = $$('#ld-articles .ld-article');
	var countDefault = findCount ? findCount.textContent : '';
	var hits = [], hitIdx = -1;
	function fold(s) { return s.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase(); }
	function clearMarks() {
		$$('mark.ld-hl').forEach(function (m) {
			var p = m.parentNode; p.replaceChild(document.createTextNode(m.textContent), m); p.normalize();
		});
		articles.forEach(function (a) { a.classList.remove('is-hit', 'is-hidden'); });
		hits = []; hitIdx = -1;
	}
	function markIn(el, q) {
		// Đánh dấu không phân biệt dấu: so khớp trên chuỗi đã bỏ dấu (độ dài giữ nguyên với NFC tiếng Việt).
		var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null), nodes = [], n;
		while ((n = walker.nextNode())) { nodes.push(n); }
		var fq = fold(q), found = 0;
		nodes.forEach(function (node) {
			var text = node.nodeValue, ft = fold(text);
			if (ft.length !== text.length) { return; }
			var i = ft.indexOf(fq);
			if (i < 0) { return; }
			var frag = document.createDocumentFragment(), last = 0;
			while (i >= 0) {
				frag.appendChild(document.createTextNode(text.slice(last, i)));
				var m = document.createElement('mark'); m.className = 'ld-hl'; m.textContent = text.slice(i, i + fq.length);
				frag.appendChild(m); found++;
				last = i + fq.length; i = ft.indexOf(fq, last);
			}
			frag.appendChild(document.createTextNode(text.slice(last)));
			node.parentNode.replaceChild(frag, node);
		});
		return found;
	}
	function goHit(i) {
		if (!hits.length) { return; }
		if (hitIdx >= 0 && hits[hitIdx]) { hits[hitIdx].classList.remove('is-cur'); }
		hitIdx = (i + hits.length) % hits.length;
		var m = hits[hitIdx]; m.classList.add('is-cur');
		m.scrollIntoView({ block: 'center', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
		if (findCount) { findCount.textContent = 'Kết quả ' + (hitIdx + 1) + '/' + hits.length + ' · ' + root.querySelectorAll('.ld-article.is-hit').length + ' Điều'; }
	}
	function runFind() {
		if (!findInput) { return; }
		var q = findInput.value.trim();
		clearMarks();
		if (findEmpty) { findEmpty.hidden = true; }
		if (q.length < 2) {
			if (findCount) { findCount.textContent = q.length ? 'Nhập ít nhất 2 ký tự.' : countDefault; }
			if (nextBtn) { nextBtn.hidden = true; }
			return;
		}
		// "Điều 5" / "điều 5" → nhảy thẳng đến Điều.
		var mNum = /^(?:đi?ều|dieu)\s*(\d+[a-zđ]?)$/i.exec(q);
		if (mNum) {
			var target = document.getElementById('dieu-' + mNum[1].toLowerCase());
			if (target) { target.classList.add('is-hit'); target.scrollIntoView({ block: 'start' }); if (findCount) { findCount.textContent = 'Đến Điều ' + mNum[1]; } return; }
		}
		var hitArts = 0;
		articles.forEach(function (a) {
			var body = a.querySelector('.ld-art-body'), h = a.querySelector('h3'), c = 0;
			if (h) { c += markIn(h, q); }
			if (body) { c += markIn(body, q); }
			if (c) { a.classList.add('is-hit'); hitArts++; } else { a.classList.add('is-hidden'); }
		});
		hits = $$('mark.ld-hl');
		if (!hits.length) {
			articles.forEach(function (a) { a.classList.remove('is-hidden'); });
			if (findEmpty) { findEmpty.hidden = false; }
			if (findCount) { findCount.textContent = 'Không tìm thấy "' + q + '".'; }
			if (nextBtn) { nextBtn.hidden = true; }
			return;
		}
		if (nextBtn) { nextBtn.hidden = hits.length < 2; }
		goHit(0);
	}
	if (findInput) {
		var findBtn = $('[data-ld-find]');
		if (findBtn) { findBtn.addEventListener('click', runFind); }
		findInput.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); if (hits.length && findInput.dataset.last === findInput.value) { goHit(hitIdx + 1); } else { findInput.dataset.last = findInput.value; runFind(); } }
			if (e.key === 'Escape') { findInput.value = ''; runFind(); }
		});
		findInput.addEventListener('search', function () { if (!findInput.value) { runFind(); } });
		if (nextBtn) { nextBtn.addEventListener('click', function () { goHit(hitIdx + 1); }); }
		var clr = $('[data-ld-find-clear]');
		if (clr) { clr.addEventListener('click', function () { findInput.value = ''; findInput.dataset.last = ''; runFind(); findInput.focus(); }); }
		// Phím tắt "/" để tìm.
		document.addEventListener('keydown', function (e) {
			if (e.key === '/' && !/input|textarea|select/i.test((document.activeElement || {}).tagName || '')) { e.preventDefault(); findInput.focus(); }
		});
	}

	/* ---------- Xem tại PDF ---------- */
	var pdf = document.getElementById('ld-pdf');
	function loadPdf(page) {
		if (!pdf) { return false; }
		var base = pdf.getAttribute('data-src') || D.pdf;
		if (!base) { return false; }
		var src = base + (page ? '#page=' + page : '');
		if (pdf.getAttribute('src') !== src) {
			// Đổi hash trên cùng URL không luôn khiến trình xem PDF nhảy trang → nạp lại.
			pdf.setAttribute('src', 'about:blank');
			setTimeout(function () { pdf.setAttribute('src', src); }, 30);
		}
		return true;
	}
	if (pdf && 'IntersectionObserver' in window) {
		var pio = new IntersectionObserver(function (ents) {
			if (ents[0].isIntersecting && !pdf.getAttribute('src')) { loadPdf(0); pio.disconnect(); }
		}, { rootMargin: '300px' });
		pio.observe(pdf);
	} else if (pdf) { loadPdf(0); }
	$$('[data-ld-pdf]').forEach(function (b) {
		b.addEventListener('click', function () {
			var page = parseInt(b.getAttribute('data-ld-pdf'), 10) || 0;
			if (!loadPdf(page)) { return; }
			var sec = document.getElementById('ban-goc');
			if (sec) { sec.scrollIntoView({ block: 'start' }); }
			toast(page ? 'Mở văn bản gốc tại trang ' + page + '.' : 'Chưa xác định trang của Điều này — mở văn bản gốc từ đầu.');
		});
	});

	/* ---------- Nội dung liên quan theo Điều ---------- */
	var rel = document.getElementById('ld-related');
	function artInfo(btn) {
		var a = btn.closest('.ld-article');
		var h = a ? a.querySelector('h3') : null;
		return { el: a, id: a ? a.getAttribute('data-id') : '', num: a ? a.getAttribute('data-num') : '', label: h ? h.textContent.replace(/\s+/g, ' ').trim() : '' };
	}
	function openRelated(kind, info) {
		if (!rel) { return; }
		var title = document.getElementById('ld-related-title'), sub = document.getElementById('ld-related-sub'), body = document.getElementById('ld-related-body');
		var html = '', short = /^\d/.test(info.num) ? 'Điều ' + info.num : 'Phụ lục';
		if (kind === 'q') {
			var kis = (D.kis && D.kis[info.id]) || [], qn = (D.qcount && D.qcount[info.id]) || 0;
			title.textContent = 'Câu hỏi & bài học · ' + short;
			sub.textContent = info.label;
			if (kis.length) {
				html += '<ul class="ld-relitems">' + kis.map(function (k) {
					return '<li><a href="' + esc(k.url) + '">' + esc(k.title) + '</a>' + (k.summary ? '<p>' + esc(k.summary.length > 220 ? k.summary.slice(0, 218) + '…' : k.summary) + '</p>' : '') + '</li>';
				}).join('') + '</ul>';
			}
			html += '<div class="ld-relfoot">' + (qn ? '<span>' + qn + ' câu hỏi trong ngân hàng đề dẫn chiếu ' + esc(short) + '.</span>' : '') +
				(D.practice ? '<a class="ld-btn is-primary is-sm" href="' + esc(D.practice.url) + '">Luyện ' + D.practice.total + ' câu theo văn bản ↗</a>' : '') +
				(kis.length ? '<a class="ld-btn is-sm" href="' + esc(kis[0].url) + '">Học ' + esc(short) + ' ↗</a>' : '') + '</div>';
		} else {
			var recs = (D.recs && D.recs[info.num]) || [];
			title.textContent = 'Tuyển dụng dẫn chiếu ' + short;
			sub.textContent = 'Tin tuyển dụng ghi rõ ' + short + ' của văn bản này trong căn cứ pháp lý';
			html = '<ul class="ld-relitems">' + recs.map(function (r) {
				return '<li><a href="' + esc(r.url) + '">' + esc(r.title) + '</a><small>' + (r.status === 'published' ? 'Đang đăng' : 'Đã hết hạn') + '</small></li>';
			}).join('') + '</ul>';
		}
		body.innerHTML = html;
		rel.hidden = false;
		rel.dataset.from = info.el ? info.el.id : '';
		rel.scrollIntoView({ block: 'start' });
	}
	$$('[data-ld-rel]').forEach(function (b) {
		b.addEventListener('click', function () { openRelated(b.getAttribute('data-ld-rel'), artInfo(b)); });
	});
	var relClose = $('[data-ld-related-close]');
	if (relClose) {
		relClose.addEventListener('click', function () {
			rel.hidden = true;
			var back = rel.dataset.from && document.getElementById(rel.dataset.from);
			if (back) { back.scrollIntoView({ block: 'start' }); }
		});
	}

	/* ---------- Sao chép Điều ---------- */
	$$('[data-ld-copyart]').forEach(function (b) {
		b.addEventListener('click', function () {
			var info = artInfo(b), body = info.el ? info.el.querySelector('.ld-art-body') : null;
			var cite = (D.number ? D.number : document.title) + (info.el ? ' — ' + (D.url || location.href.split('#')[0]) + '#' + info.el.id : '');
			copyText(info.label + '\n' + (body ? body.textContent.trim() : '') + '\n\n(Nguồn: ' + cite + ')', 'Đã sao chép ' + (/^\d/.test(info.num) ? 'Điều ' + info.num : 'nội dung') + ' kèm dẫn chiếu.');
		});
	});

	/* ---------- Lưu đánh dấu (theo tài khoản) ---------- */
	function setSaved(b, on) {
		var isDoc = b.getAttribute('data-ld-save') === 'legal_document';
		b.classList.toggle('is-saved', on);
		b.setAttribute('aria-pressed', on ? 'true' : 'false');
		b.textContent = isDoc ? (on ? '★ Đã lưu văn bản' : '☆ Lưu văn bản') : (on ? '★ Đã lưu' : '☆ Lưu đánh dấu');
	}
	$$('[data-ld-save]').forEach(function (b) {
		b.addEventListener('click', function () {
			if (!D.logged) {
				toast('Đăng nhập để lưu đánh dấu — đánh dấu gắn với tài khoản và đồng bộ trên mọi thiết bị.');
				if (D.login) { setTimeout(function () { location.href = D.login; }, 1300); }
				return;
			}
			var on = b.getAttribute('aria-pressed') !== 'true';
			b.disabled = true;
			var fd = new FormData();
			fd.append('action', 'cvc_ld_bm'); fd.append('nonce', D.nonce || '');
			fd.append('type', b.getAttribute('data-ld-save')); fd.append('id', b.getAttribute('data-id')); fd.append('on', on ? '1' : '0');
			fetch(D.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json().catch(function () { return { success: false }; }); })
				.then(function (j) {
					if (j && j.success) {
						setSaved(b, on);
						toast(on ? 'Đã lưu vào mục đánh dấu của bạn.' : 'Đã bỏ lưu.');
					} else {
						toast((j && j.data && j.data.message) || 'Chưa lưu được, vui lòng thử lại.');
						if (j && j.data && j.data.login && D.login) { setTimeout(function () { location.href = D.login; }, 1300); }
					}
				})
				.catch(function () { toast('Mất kết nối — chưa lưu được.'); })
				.then(function () { b.disabled = false; });
		});
	});
})();
