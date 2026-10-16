/**
 * Trang làm bài - giao diện văn bản (16/10/2026). Thi tập trung / Danh sách câu / Bản đồ đề,
 * bản đồ câu hỏi, đồng hồ, nộp bài, xem lại đáp án + giải thích (sau khi nộp).
 * Dữ liệu: window.CVC_SMART_EXAM (lượt thi thật). Đáp án lưu lên máy chủ ngay khi chọn
 * (cvc_exam_answer, theo question_option_id); đánh dấu: cvc_exam_meta; nộp: cvc_exam_submit;
 * giải thích: cvc_exam_ai_explain. Ghi chú cá nhân + cỡ chữ chỉ lưu trên trình duyệt.
 */
(function () {
  'use strict';
  var D = window.CVC_SMART_EXAM;
  var root = document.getElementById('cvc-smart-exam');
  if (!D || !root) return;

  var Q = D.questions || [];
  var TOTAL = Q.length;
  var $ = function (s) { return root.querySelector(s) || document.querySelector(s); };
  var $$ = function (s) { return Array.prototype.slice.call(root.querySelectorAll(s)); };
  var noteKey = 'cvc-sx-notes-' + D.attempt_id;
  var fsKey = 'cvc-sx-fontsize';
  var resultKey = 'cvc-sx-result-' + D.attempt_id;

  var current = 0, mode = 'focus', filter = 'all', submitted = !!D.submitted, busy = false;
  var notes = {};
  try { notes = JSON.parse(localStorage.getItem(noteKey) || '{}') || {}; } catch (e) { notes = {}; }

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function count() { return Q.filter(function (q) { return !!q.selected; }).length; }
  function toast(s) { var t = $('#sx-toast'); t.textContent = s; t.classList.add('show'); clearTimeout(toast.id); toast.id = setTimeout(function () { t.classList.remove('show'); }, 2600); }
  function saveStatus(s) { var el = $('#sx-saveStatus'); if (el) el.textContent = s; }
  function post(action, fields) {
    var fd = new FormData();
    fd.append('action', action); fd.append('nonce', D.nonce); fd.append('attempt_id', D.attempt_id);
    Object.keys(fields || {}).forEach(function (k) { if (fields[k] !== undefined && fields[k] !== null) fd.append(k, fields[k]); });
    return fetch(D.ajax_url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  function hidden(i) {
    var q = Q[i];
    if (filter === 'answered') return !q.selected;
    if (filter === 'unanswered') return !!q.selected;
    if (filter === 'flagged') return !q.flagged;
    if (filter === 'wrong') return q.is_correct !== false;
    return false;
  }
  function correctKey(q) { var o = (q.options || []).filter(function (x) { return x.correct === true; })[0]; return o ? o.key : null; }

  /* --- Đáp án --- */
  function choose(i, key) {
    if (submitted || busy) return;
    var q = Q[i];
    if (q.selected === key) return;
    var prev = q.selected;
    var opt = (q.options || []).filter(function (o) { return o.key === key; })[0];
    if (!opt) return;
    q.selected = key;
    render();
    saveStatus('Đang lưu…');
    post('cvc_exam_answer', { question_id: q.id, question_option_id: opt.id }).then(function (res) {
      if (res && res.success) { saveStatus('Đã lưu ' + new Date().toLocaleTimeString('vi-VN')); return; }
      q.selected = prev; render();
      toast((res && res.data && res.data.message) || 'Chưa lưu được đáp án, vui lòng chọn lại.');
      saveStatus('Chưa lưu được');
    }).catch(function () { q.selected = prev; render(); toast('Mất kết nối - đáp án chưa được lưu.'); saveStatus('Chưa lưu được'); });
  }
  function clearAnswer(i) {
    if (submitted) return;
    var q = Q[i]; if (!q.selected) return;
    var prev = q.selected; q.selected = null; render();
    post('cvc_exam_answer', { question_id: q.id }).then(function (res) {
      if (res && res.success) { saveStatus('Đã bỏ chọn'); return; }
      q.selected = prev; render(); toast('Chưa bỏ chọn được, vui lòng thử lại.');
    }).catch(function () { q.selected = prev; render(); toast('Mất kết nối.'); });
  }
  function toggleFlag(i, val) {
    var q = Q[i];
    q.flagged = typeof val === 'boolean' ? val : !q.flagged;
    render();
    if (!submitted) post('cvc_exam_meta', { question_id: q.id, is_flagged: q.flagged ? '1' : '0' }).catch(function () {});
  }

  function answerButtons(el, i) {
    var q = Q[i]; el.innerHTML = '';
    var ck = submitted ? correctKey(q) : null;
    (q.options || []).forEach(function (o) {
      var b = document.createElement('button');
      b.type = 'button';
      var cls = 'sx-answer';
      if (q.selected === o.key) cls += ' sel';
      var tag = '';
      if (submitted && ck) {
        if (o.key === ck) { cls += ' right'; tag = q.selected === o.key ? 'Bạn chọn · Đúng' : 'Đáp án đúng'; }
        else if (q.selected === o.key) { cls += ' wrong'; tag = 'Bạn chọn · Sai'; }
      }
      b.className = cls;
      b.setAttribute('aria-pressed', q.selected === o.key ? 'true' : 'false');
      b.innerHTML = '<span class="sx-letter">' + esc(o.key) + '</span><span class="sx-copy"></span>' + (tag ? '<span class="sx-tag">' + esc(tag) + '</span>' : '<span class="sx-radio"></span>');
      b.querySelector('.sx-copy').textContent = o.text;
      if (submitted) b.disabled = false;
      b.onclick = function () { if (submitted) { openExplain(i); return; } choose(i, o.key); };
      el.appendChild(b);
    });
  }

  function grid(el, large) {
    el.innerHTML = '';
    Q.forEach(function (q, i) {
      var b = document.createElement('button');
      b.type = 'button';
      var cls = 'sx-num';
      if (submitted && q.is_correct === true) cls += ' right';
      else if (submitted && q.is_correct === false) cls += ' wrong';
      else if (q.selected) cls += ' done';
      if (i === current) cls += ' now';
      if (q.flagged) cls += ' flag';
      b.className = cls;
      b.textContent = i + 1;
      b.title = 'Câu ' + (i + 1) + ': ' + (q.selected ? 'đã trả lời' : 'chưa trả lời') + (q.flagged ? ', đánh dấu xem lại' : '') + (submitted && q.is_correct === true ? ', đúng' : (submitted && q.is_correct === false ? ', sai' : ''));
      b.onclick = function () { current = i; if (mode !== 'focus') setMode('focus'); else render(); };
      if (hidden(i)) b.classList.add('sx-hidden');
      el.appendChild(b);
    });
  }

  function list() {
    var el = $('#sx-all'); el.innerHTML = '';
    var shown = 0;
    Q.forEach(function (q, i) {
      if (hidden(i)) return;
      shown++;
      var c = document.createElement('article'); c.className = 'sx-mini'; c.id = 'sx-mini-' + i;
      var h = document.createElement('h3'); h.textContent = 'Câu ' + (i + 1) + '. ' + q.text; c.appendChild(h);
      var a = document.createElement('div'); a.className = 'sx-answers'; answerButtons(a, i); c.appendChild(a);
      var f = document.createElement('div'); f.className = 'sx-minifoot';
      var l = document.createElement('label'); l.className = 'sx-check';
      var cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = !!q.flagged; cb.onchange = function () { toggleFlag(i, cb.checked); };
      l.appendChild(cb); l.appendChild(document.createTextNode(' Đánh dấu xem lại'));
      var g = document.createElement('div'); g.className = 'sx-group';
      if (submitted) { var ex = document.createElement('button'); ex.type = 'button'; ex.className = 'sx-btn'; ex.textContent = 'Đáp án & căn cứ'; ex.onclick = function () { openExplain(i); }; g.appendChild(ex); }
      var go = document.createElement('button'); go.type = 'button'; go.className = 'sx-btn sx-primary'; go.textContent = 'Mở câu'; go.onclick = function () { current = i; setMode('focus'); };
      g.appendChild(go); f.appendChild(l); f.appendChild(g); c.appendChild(f); el.appendChild(c);
    });
    if (!shown) el.innerHTML = '<p class="sx-muted">Không có câu nào khớp bộ lọc.</p>';
  }

  function feedback() {
    var fb = $('#sx-feedback'), q = Q[current];
    fb.className = 'sx-feedback';
    if (!submitted) { fb.innerHTML = ''; return; }
    var ck = correctKey(q);
    var ok = q.is_correct === true, no = q.is_correct === false;
    fb.classList.add('show', ok ? 'ok' : (no ? 'no' : 'x'));
    fb.innerHTML = '<h3>' + (!q.selected ? 'Bạn bỏ trống câu này' : (ok ? 'Trả lời đúng' : (no ? 'Trả lời chưa đúng' : 'Đã trả lời'))) + '</h3>' +
      '<p>' + (ck ? 'Đáp án đúng: <strong>' + esc(ck) + '</strong>. ' : '') + 'Xem giải thích và căn cứ pháp lý để nắm chắc kiến thức.</p>' +
      '<div class="sx-group"><button type="button" class="sx-btn" data-act="explain">▤ Giải thích &amp; căn cứ pháp lý</button></div>';
    fb.querySelector('[data-act="explain"]').onclick = function () { openExplain(current); };
  }

  function render() {
    if (!TOTAL) return;
    var q = Q[current];
    $('#sx-counter').textContent = 'Câu ' + (current + 1) + '/' + TOTAL;
    $('#sx-topic').textContent = q.topic ? q.topic : '';
    $('#sx-qtext').textContent = q.text;
    $('#sx-hintline').textContent = submitted ? 'Bài đã nộp - chọn một phương án để xem giải thích.' : 'Chọn một đáp án đúng nhất.';
    $('#sx-flag').checked = !!q.flagged;
    $('#sx-note').value = notes[q.id] || '';
    answerButtons($('#sx-answers'), current);
    feedback();
    $('#sx-prev').disabled = current === 0;
    $('#sx-next').disabled = current === TOTAL - 1;
    $('#sx-clear').disabled = submitted || !q.selected;
    $('#sx-flagBtn').textContent = q.flagged ? '⚑ Bỏ đánh dấu' : '⚑ Đánh dấu';
    grid($('#sx-grid')); grid($('#sx-largeGrid'), true);
    if (mode === 'list') list();
    var n = count(), pct = TOTAL ? Math.round(n / TOTAL * 100) : 0;
    $('#sx-answered').textContent = n; $('#sx-sideAnswered').textContent = n;
    $('#sx-mapcount').textContent = n + '/' + TOTAL;
    $('#sx-progress').style.width = pct + '%'; $('#sx-percent').textContent = pct + '%';
    $('#sx-sidePercent').textContent = pct + '% hoàn thành';
    $$('[data-filter]').forEach(function (b) { b.classList.toggle('active', b.dataset.filter === filter); });
  }

  function setMode(m) {
    mode = m;
    var page = $('#sx-page');
    page.classList.toggle('sx-list-mode', m === 'list');
    page.classList.toggle('sx-map-mode', m === 'map');
    $$('[data-mode]').forEach(function (b) { b.classList.toggle('active', b.dataset.mode === m); });
    render();
    var target = m === 'list' ? $('#sx-all') : (m === 'map' ? $('#sx-maponly') : $('#sx-mainpanel'));
    if (target && target.getBoundingClientRect().top < 0) target.scrollIntoView({ block: 'start', behavior: 'smooth' });
    if (m === 'list') { var el = document.getElementById('sx-mini-' + current); if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
  }

  /* --- Giải thích (chỉ sau khi nộp) --- */
  function openExplain(i) {
    var q = Q[i];
    var box = $('#sx-supportContent');
    $('#sx-kicker').textContent = 'CÂU ' + (i + 1) + ' · ĐÁP ÁN VÀ CĂN CỨ';
    $('#sx-supportTitle').textContent = 'Giải thích đáp án';
    $('#sx-support').classList.add('open');
    if (!submitted) {
      box.innerHTML = '<p>Đáp án, giải thích và căn cứ pháp lý hiển thị sau khi bạn nộp bài - để kết quả thi thử phản ánh đúng năng lực.</p>';
      return;
    }
    box.innerHTML = '<p class="sx-muted">Đang tải giải thích…</p>';
    post('cvc_exam_ai_explain', { question_id: q.id }).then(function (res) {
      if (!res || !res.success) { box.innerHTML = '<p>' + esc((res && res.data && res.data.message) || 'Không tải được giải thích.') + '</p>'; return; }
      var d = (res.data && res.data.data) || {};
      var base = D.home_url || '/';
      var verdict = d.is_correct === true ? 'Bạn trả lời đúng.' : (d.is_correct === false ? 'Bạn trả lời chưa đúng.' : 'Bạn chưa trả lời câu này.');
      var html = '<p><strong>' + esc(verdict) + '</strong>' + (d.correct_option_key ? ' Đáp án đúng: <strong>' + esc(d.correct_option_key) + '</strong>.' : '') + '</p>';
      html += d.explanation ? '<p>' + esc(d.explanation).replace(/\n/g, '<br>') + '</p>' : '<p class="sx-muted">Câu hỏi này chưa có phần giải thích.</p>';
      if (d.legal_reference && d.legal_reference.slug) {
        html += '<div class="sx-citation"><strong>Căn cứ pháp lý</strong><p><a href="' + base + 'van-ban-phap-luat/' + encodeURIComponent(d.legal_reference.slug) + '/" target="_blank" rel="noopener">' + esc((d.legal_reference.document_number ? d.legal_reference.document_number + ' - ' : '') + d.legal_reference.title) + '</a></p></div>';
      }
      var more = [];
      if (d.knowledge_item && d.knowledge_item.slug) more.push('<a href="' + base + 'kien-thuc/' + encodeURIComponent(d.knowledge_item.slug) + '/" target="_blank" rel="noopener">Kiến thức: ' + esc(d.knowledge_item.title) + '</a>');
      if (d.topic && d.topic.slug) more.push('<a href="' + base + 'chu-de/' + encodeURIComponent(d.topic.slug) + '/" target="_blank" rel="noopener">Ôn chủ đề: ' + esc(d.topic.name) + '</a>');
      if (more.length) html += '<div class="sx-citation"><strong>Học tiếp</strong><p>' + more.join('<br>') + '</p></div>';
      html += '<p class="sx-source">' + (d.source === 'ai' ? 'Phần diễn giải do AI viết dựa trên đáp án chuẩn; căn cứ theo văn bản gốc.' : 'Giải thích có sẵn trong ngân hàng câu hỏi.') + '</p>';
      box.innerHTML = html;
    }).catch(function () { box.innerHTML = '<p>Lỗi kết nối, vui lòng thử lại.</p>'; });
  }

  /* --- Nộp bài --- */
  function submitDialog(lead) {
    if (submitted || busy) return;
    var n = count(), fl = Q.filter(function (q) { return q.flagged; }).length;
    $('#sx-submitMessage').textContent = (lead || '') + 'Đã trả lời ' + n + '/' + TOTAL + ' câu. Còn ' + (TOTAL - n) + ' câu chưa trả lời và ' + fl + ' câu đánh dấu xem lại. Sau khi nộp sẽ không sửa được đáp án. Bạn có muốn nộp bài không?';
    $('#sx-submitDialog').classList.add('show');
    $('#sx-confirm').focus();
  }
  function submit(auto) {
    if (submitted || busy) return;
    busy = true;
    $('#sx-submitDialog').classList.remove('show');
    $('#sx-submitTop').disabled = true;
    $('#sx-submitTop').textContent = 'Đang nộp bài…';
    post('cvc_exam_submit', {}).then(function (res) {
      busy = false;
      if (!res || !res.success) {
        if (res && res.data && res.data.already_submitted) { location.reload(); return; }
        $('#sx-submitTop').disabled = false; $('#sx-submitTop').textContent = '▤ Nộp bài';
        toast((res && res.data && res.data.message) || 'Nộp bài thất bại, vui lòng thử lại.');
        return;
      }
      var p = res.data || {};
      try { sessionStorage.setItem(resultKey, JSON.stringify({ topic_stats: p.topic_stats || [], recommendations: p.recommendations || [] })); } catch (e) {}
      showResult(p.data || {}, true);
    }).catch(function () {
      busy = false; $('#sx-submitTop').disabled = false; $('#sx-submitTop').textContent = '▤ Nộp bài';
      toast('Lỗi kết nối khi nộp bài. Vui lòng kiểm tra mạng và thử lại.');
    });
  }
  function fmt(x) { if (x === null || x === undefined || x === '') return '--'; var n = Number(x); return isNaN(n) ? String(x) : (Math.round(n * 100) / 100).toLocaleString('vi-VN'); }
  function scoreHtml(a) {
    var total = D.exam && D.exam.total_score ? D.exam.total_score : null;
    var verdict = a.passed === true ? '<span class="sx-verdict pass">Đạt</span>' : (a.passed === false ? '<span class="sx-verdict fail">Chưa đạt</span>' : '<span class="sx-verdict none">Đã hoàn thành</span>');
    return '<div class="sx-score">' + fmt(a.score) + (total ? '/' + fmt(total) : ' điểm') + '</div>' + verdict +
      '<p>Trả lời đúng ' + (a.correct_count != null ? a.correct_count : '--') + '/' + TOTAL + ' câu' + (a.percentage != null ? ' (' + fmt(a.percentage) + '%)' : '') + '.' +
      (D.exam && D.exam.passing_score && total ? ' Điểm đạt: ' + fmt(D.exam.passing_score) + '/' + fmt(total) + '.' : '') + '</p>';
  }
  function showResult(a, fresh) {
    $('#sx-resultBody').innerHTML = scoreHtml(a) + (fresh ? '<p class="sx-muted">Bấm "Xem lại bài làm" để xem đúng/sai từng câu, đáp án và căn cứ pháp lý.</p>' : '');
    $('#sx-resultDialog').classList.add('show');
    $('#sx-closeResult').onclick = function () { if (fresh) { location.reload(); return; } $('#sx-resultDialog').classList.remove('show'); };
    $('#sx-showAnalysis').onclick = function () { if (fresh) { location.hash = 'phan-tich'; location.reload(); return; } $('#sx-resultDialog').classList.remove('show'); $('#sx-analysis').scrollIntoView({ behavior: 'smooth', block: 'center' }); };
  }
  function renderAnalysis() {
    if (!submitted) return;
    var a = D.result || {};
    $('#sx-scorebox').innerHTML = scoreHtml(a);
    var extra = null;
    try { extra = JSON.parse(sessionStorage.getItem(resultKey) || 'null'); } catch (e) {}
    var stats = (extra && extra.topic_stats) || [];
    var w = $('#sx-weakness');
    if (stats.length) {
      w.innerHTML = '<p class="sx-muted">Tỷ lệ đúng theo chủ đề:</p>' + stats.map(function (s) {
        return '<div class="sx-weak"><strong><span>' + esc(s.topic) + '</span><span>' + esc(String(s.accuracy)) + '%</span></strong><div class="sx-minibar"><span style="width:' + Math.max(0, Math.min(100, Number(s.accuracy) || 0)) + '%"></span></div><small class="sx-muted">' + esc(s.label || '') + '</small></div>';
      }).join('');
    } else {
      // Tự tính theo chủ đề từ bài làm khi không còn số liệu lúc nộp.
      var by = {};
      Q.forEach(function (q) { if (!q.topic) return; by[q.topic] = by[q.topic] || { t: 0, c: 0 }; by[q.topic].t++; if (q.is_correct === true) by[q.topic].c++; });
      var keys = Object.keys(by);
      w.innerHTML = keys.length ? '<p class="sx-muted">Tỷ lệ đúng theo chủ đề:</p>' + keys.map(function (k) { var p = Math.round(by[k].c / by[k].t * 100); return '<div class="sx-weak"><strong><span>' + esc(k) + '</span><span>' + p + '%</span></strong><div class="sx-minibar"><span style="width:' + p + '%"></span></div><small class="sx-muted">' + by[k].c + '/' + by[k].t + ' câu đúng</small></div>'; }).join('') : '<p class="sx-muted">Đề chưa gắn chủ đề cho câu hỏi.</p>';
    }
    var recs = (extra && extra.recommendations) || [];
    if (recs.length) {
      var paths = { course: 'khoa-hoc/', document: 'tai-lieu/', legal_document: 'van-ban-phap-luat/', topic: 'chu-de/', exam: 'thi-trac-nghiem/' };
      var labels = { course: 'Khóa học', document: 'Tài liệu', legal_document: 'Văn bản', topic: 'Chủ đề ôn tập', exam: 'Đề thi' };
      $('#sx-recs').innerHTML = '<p class="sx-muted" style="margin-top:10px">Gợi ý ôn tập:</p>' + recs.slice(0, 5).map(function (r) {
        return '<a class="sx-rec" href="' + (D.home_url || '/') + (paths[r.type] || 'tai-lieu/') + encodeURIComponent(r.slug) + '/"><small>' + esc(labels[r.type] || 'Gợi ý') + '</small>' + esc(r.title) + '</a>';
      }).join('');
    }
  }

  /* --- Đồng hồ --- */
  var remaining = (D.timer && D.timer.seconds) || 0, tmode = (D.timer && D.timer.mode) || 'done', duration = (D.timer && D.timer.duration) || 0, timer = null;
  function clock(t) { t = Math.max(0, t); var h = Math.floor(t / 3600), m = Math.floor(t % 3600 / 60), s = t % 60; return (h ? h + ':' : '') + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0'); }
  function paintClock() {
    var el = $('#sx-timer');
    if (tmode === 'done') { el.textContent = 'Đã nộp'; $('#sx-sideProgress').style.width = '0%'; return; }
    if (tmode === 'over') { el.textContent = 'Hết giờ'; el.classList.add('warn'); $('#sx-sideProgress').style.width = '0%'; return; }
    el.textContent = clock(remaining);
    el.classList.toggle('warn', tmode === 'countdown' && remaining <= 300);
    $('#sx-sideProgress').style.width = (tmode === 'countdown' && duration ? Math.max(0, remaining / duration * 100) : 100) + '%';
  }
  function tick() {
    if (submitted) { clearInterval(timer); return; }
    remaining += tmode === 'countdown' ? -1 : 1;
    paintClock();
    if (tmode === 'countdown' && remaining <= 0) { clearInterval(timer); toast('Đã hết giờ - hệ thống tự nộp bài.'); submit(true); }
  }

  /* --- Sự kiện --- */
  $('#sx-prev').onclick = function () { current = Math.max(0, current - 1); render(); };
  $('#sx-next').onclick = function () { current = Math.min(TOTAL - 1, current + 1); render(); };
  $('#sx-clear').onclick = function () { clearAnswer(current); };
  $('#sx-flagBtn').onclick = function () { toggleFlag(current); };
  $('#sx-flag').onchange = function (e) { toggleFlag(current, e.target.checked); };
  $('#sx-noteToggle').onclick = function () { $('#sx-notes').classList.toggle('sx-hidden'); };
  $('#sx-note').oninput = function (e) { notes[Q[current].id] = e.target.value; try { localStorage.setItem(noteKey, JSON.stringify(notes)); } catch (x) {} };
  $('#sx-explainbtn').onclick = function () { openExplain(current); };
  $('#sx-submitTop').onclick = submitDialog;
  $('#sx-cancel').onclick = function () { $('#sx-submitDialog').classList.remove('show'); };
  $('#sx-confirm').onclick = function () { submit(false); };
  $('#sx-closeSupport').onclick = function () { $('#sx-support').classList.remove('open'); };
  $$('[data-mode]').forEach(function (b) { b.onclick = function () { setMode(b.dataset.mode); }; });
  $$('[data-filter]').forEach(function (b) { b.onclick = function () { filter = b.dataset.filter; render(); if (mode === 'focus' && hidden(current)) { for (var i = 0; i < TOTAL; i++) { if (!hidden(i)) { current = i; render(); break; } } } }; });
  var fs = 1; try { fs = Number(localStorage.getItem(fsKey)) || 1; } catch (e) {}
  function paintFs() { root.classList.remove('fs-2', 'fs-3'); if (fs > 1) root.classList.add('fs-' + fs); }
  $('#sx-font').onclick = function () { fs = fs % 3 + 1; paintFs(); try { localStorage.setItem(fsKey, String(fs)); } catch (e) {} toast('Cỡ chữ: ' + ['', 'thường', 'lớn', 'rất lớn'][fs]); };
  paintFs();

  document.addEventListener('keydown', function (e) {
    if ($('#sx-support').classList.contains('open') && e.key === 'Escape') { $('#sx-support').classList.remove('open'); return; }
    if ($('#sx-submitDialog').classList.contains('show') || $('#sx-resultDialog').classList.contains('show')) { if (e.key === 'Escape') { $('#sx-submitDialog').classList.remove('show'); } return; }
    var tag = document.activeElement && document.activeElement.tagName;
    if (['INPUT', 'TEXTAREA', 'SELECT'].indexOf(tag) > -1 || e.ctrlKey || e.metaKey || e.altKey) return;
    var k = (e.key || '').toLowerCase();
    var keys = ['1', '2', '3', '4', '5', '6', 'a', 'b', 'c', 'd', 'e', 'f'];
    if (mode === 'focus' && !submitted && /^[1-6a-e]$/.test(k)) {
      var j = /[1-6]/.test(k) ? Number(k) - 1 : k.charCodeAt(0) - 97;
      var o = (Q[current].options || [])[j];
      if (o) { e.preventDefault(); choose(current, o.key); }
    } else if (e.key === 'ArrowRight' && mode === 'focus') { current = Math.min(TOTAL - 1, current + 1); render(); }
    else if (e.key === 'ArrowLeft' && mode === 'focus') { current = Math.max(0, current - 1); render(); }
    else if (k === 'f' && keys) { toggleFlag(current); }
  });

  /* --- Khởi động --- */
  if (!TOTAL) { $('#sx-qtext').textContent = 'Đề thi chưa có câu hỏi.'; return; }
  var firstOpen = Q.findIndex(function (q) { return !q.selected; });
  current = submitted ? 0 : (firstOpen > -1 ? firstOpen : 0);
  render();
  paintClock();
  /* Lượt thi đã quá giờ từ trước khi mở trang (ví dụ bỏ dở rồi quay lại): KHÔNG tự nộp
     ngay khi tải trang - báo hết giờ, cho người dùng chọn nộp bài hoặc luyện tiếp. */
  if (!submitted && tmode === 'countdown' && remaining <= 0) {
    tmode = 'over';
    paintClock();
    setTimeout(function () {
      submitDialog('Lượt thi này đã hết thời gian làm bài (' + Math.round(duration / 60) + ' phút). Bạn có thể nộp bài để xem kết quả, hoặc tiếp tục luyện tập không tính giờ. ');
      $('#sx-submitTitle').textContent = 'Đã hết giờ làm bài';
      $('#sx-cancel').textContent = 'Luyện tập tiếp';
    }, 300);
  }
  if (!submitted && (tmode === 'countdown' || tmode === 'elapsed')) timer = setInterval(tick, 1000);
  if (submitted) {
    renderAnalysis();
    if (location.hash === '#phan-tich') setTimeout(function () { $('#sx-analysis').scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 200);
  }
})();
