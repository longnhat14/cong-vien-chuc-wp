/**
 * Trang làm bài - giao diện văn bản (16/10/2026; chế độ + lưu bài + rà soát 19/10/2026).
 * Hai chế độ: Thi thật (ẩn đáp án tới khi nộp, đồng hồ do máy chủ kiểm soát) và Ôn tập (đáp án,
 * giải thích, căn cứ pháp luật hiện ngay sau mỗi câu; câu đã chấm khóa lại).
 * Lưu bài: mọi thay đổi vào hàng đợi (localStorage) rồi gửi lên máy chủ theo thứ tự; mất mạng thì tự
 * gửi lại khi có kết nối, tải lại trang vẫn khôi phục. Đồng hồ hiển thị theo số giây máy chủ trả về
 * (đồng bộ mỗi phút), không phụ thuộc giờ của máy người dùng.
 * AJAX: cvc_exam_answer, cvc_exam_meta, cvc_exam_clock, cvc_exam_submit, cvc_exam_ai_explain.
 * Ghi chú cá nhân + cỡ chữ chỉ lưu trên trình duyệt.
 */
(function () {
  'use strict';
  var D = window.CVC_SMART_EXAM;
  var root = document.getElementById('cvc-smart-exam');
  if (!D || !root) return;

  var Q = D.questions || [];
  var TOTAL = Q.length;
  var PRACTICE = D.mode === 'practice';
  var $ = function (s) { return root.querySelector(s) || document.querySelector(s); };
  var $$ = function (s) { return Array.prototype.slice.call(root.querySelectorAll(s)); };
  var noteKey = 'cvc-sx-notes-' + D.attempt_id;
  var fsKey = 'cvc-sx-fontsize';
  var resultKey = 'cvc-sx-result-' + D.attempt_id;
  var pendKey = 'cvc-sx-pending-' + D.attempt_id;

  var current = 0, mode = 'focus', filter = 'all', submitted = !!D.submitted, busy = false, ended = false;
  var notes = {};
  try { notes = JSON.parse(localStorage.getItem(noteKey) || '{}') || {}; } catch (e) { notes = {}; }
  var byId = {};
  Q.forEach(function (q) { byId[q.id] = q; q.saved = q.selected || null; });

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function count() { return Q.filter(function (q) { return !!q.selected; }).length; }
  function toast(s) { var t = $('#sx-toast'); t.textContent = s; t.classList.add('show'); clearTimeout(toast.id); toast.id = setTimeout(function () { t.classList.remove('show'); }, 3200); }
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
    if (filter === 'wrong') return q.is_correct !== false && !(PRACTICE && q.feedback && q.feedback.is_correct === false);
    return false;
  }
  /* Đáp án đúng: sau khi nộp lấy từ options; chế độ Ôn tập lấy từ phản hồi của câu đã chấm. */
  function correctKey(q) {
    if (PRACTICE && q.feedback) {
      var f = (q.options || []).filter(function (x) { return x.id === q.feedback.correct_option_id; })[0];
      return f ? f.key : (q.feedback.correct_option_key || null);
    }
    var o = (q.options || []).filter(function (x) { return x.correct === true; })[0]; return o ? o.key : null;
  }
  /* 28/10/2026: chấm ngay trên trình duyệt từ khóa đáp án tải sẵn (q.key) - không chờ máy chủ. */
  function localFeedback(q, optId) {
    var k = q.key; if (!k || !optId) return null;
    return { question_id: q.id, your_option_id: optId, correct_option_id: k.correct_option_id, correct_option_key: k.correct_option_key,
      is_correct: k.correct_option_id ? optId === k.correct_option_id : null, explanation: k.explanation, article: k.article, knowledge: k.knowledge, local: true };
  }
  function optIdOf(q, key) { var o = (q.options || []).filter(function (x) { return x.key === key; })[0]; return o ? o.id : null; }
  function localScore() {
    var right = 0, keyed = 0;
    Q.forEach(function (q) { if (!q.key || !q.key.correct_option_id) return; keyed++; if (q.selected && optIdOf(q, q.selected) === q.key.correct_option_id) right++; });
    return keyed ? { right: right, total: TOTAL } : null;
  }
  function revealed(q) { return submitted || (PRACTICE && !!q.feedback); }
  function verdictOf(q) {
    if (submitted) return q.is_correct;
    if (PRACTICE && q.feedback) return q.feedback.is_correct;
    return null;
  }

  /* --- Lưu bài: hàng đợi gửi lên máy chủ, khôi phục khi tải lại / mất mạng --- */
  var pending = {};
  try { pending = JSON.parse(localStorage.getItem(pendKey) || '{}') || {}; } catch (e) { pending = {}; }
  var lastSaved = D.last_saved_at ? new Date(D.last_saved_at) : null;
  var offline = false, flushing = false, retryT = null, retryDelay = 4000;
  function persistPending() { try { if (Object.keys(pending).length) localStorage.setItem(pendKey, JSON.stringify(pending)); else localStorage.removeItem(pendKey); } catch (e) {} }
  function pendingCount(kind) { return Object.keys(pending).filter(function (k) { return !kind || k.charAt(0) === kind; }).length; }
  function hhmmss(d) { try { return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); } catch (e) { return ''; } }
  function paintSave() {
    if (submitted) return;
    var n = pendingCount(), txt, cls = '';
    if (n && offline) { txt = 'Mất kết nối - ' + n + ' thay đổi đang chờ, sẽ tự gửi lại khi có mạng'; cls = 'is-warn'; }
    else if (n) { txt = 'Đang lưu ' + n + ' thay đổi lên máy chủ…'; cls = 'is-busy'; }
    else { txt = lastSaved ? 'Đã lưu lên máy chủ lúc ' + hhmmss(lastSaved) : 'Đáp án được lưu lên máy chủ ngay khi chọn'; cls = lastSaved ? 'is-ok' : ''; }
    saveStatus(txt);
    var side = $('#sx-sideSave'); if (side) side.textContent = txt;
    var bar = $('#sx-savebar'); if (bar) { bar.className = 'sx-savebar ' + cls; }
  }
  function queue(key, item) {
    item.t = Date.now();
    pending[key] = item;
    persistPending(); paintSave(); flush();
  }
  function flush() {
    if (flushing || ended) return Promise.resolve();
    var keys = Object.keys(pending);
    if (!keys.length) { paintSave(); return Promise.resolve(); }
    flushing = true; clearTimeout(retryT); paintSave();
    var key = keys[0], it = pending[key], isAns = key.charAt(0) === 'a';
    var req = isAns
      ? post('cvc_exam_answer', it.opt ? { question_id: it.qid, question_option_id: it.opt } : { question_id: it.qid })
      : post('cvc_exam_meta', { question_id: it.qid, is_flagged: it.flag ? '1' : '0' });
    return req.then(function (res) {
      flushing = false; offline = false; retryDelay = 4000;
      var changed = !pending[key] || pending[key].t !== it.t;
      if (!changed) delete pending[key];
      var q = byId[it.qid];
      if (res && res.success) {
        var d = res.data || {};
        lastSaved = d.saved_at ? new Date(d.saved_at) : new Date();
        if (d.clock) syncClock(d.clock);
        if (isAns && q) { q.saved = it.key || null; if (PRACTICE && d.feedback) { q.feedback = d.feedback; } }
      } else {
        var e = (res && res.data) || {};
        if (e.expired || e.ended) { persistPending(); onEnded(e.message); return; }
        if (isAns && q && !changed) { q.selected = q.saved; if (PRACTICE && q.feedback && q.feedback.local && !e.locked) q.feedback = null; }
        toast(e.message || 'Chưa lưu được thay đổi, vui lòng thử lại.');
      }
      persistPending(); render();
      return flush();
    }).catch(function () {
      flushing = false; offline = true; paintSave();
      clearTimeout(retryT); retryT = setTimeout(flush, retryDelay); retryDelay = Math.min(60000, retryDelay * 2);
    });
  }
  /* Khôi phục thay đổi chưa kịp lưu từ lần mở trước (mất mạng / đóng tab). */
  if (!submitted) {
    Object.keys(pending).forEach(function (k) {
      var it = pending[k], q = byId[it && it.qid];
      if (!q) { delete pending[k]; return; }
      if (k.charAt(0) === 'a') { if (PRACTICE && q.feedback) { delete pending[k]; return; } q.selected = it.key || null; if (PRACTICE && q.selected) { q.feedback = localFeedback(q, it.opt) || null; } }
      else { q.flagged = !!it.flag; }
    });
    persistPending();
  } else { pending = {}; persistPending(); }
  window.addEventListener('online', function () { offline = false; flush(); });
  window.addEventListener('beforeunload', function (e) { if (!submitted && pendingCount()) { flush(); e.preventDefault(); e.returnValue = ''; } });

  /* --- Đáp án --- */
  function choose(i, key) {
    if (submitted || busy || ended) return;
    var q = Q[i];
    if (PRACTICE && (q.feedback || pending['a' + q.id])) { if (q.feedback) toast('Câu này đã chấm - xem đáp án và giải thích bên dưới.'); return; }
    if (q.selected === key) return;
    var opt = (q.options || []).filter(function (o) { return o.key === key; })[0];
    if (!opt) return;
    q.selected = key;
    if (PRACTICE) { q.feedback = localFeedback(q, opt.id) || null; }
    render();
    queue('a' + q.id, { qid: q.id, opt: opt.id, key: key });
  }
  function clearAnswer(i) {
    if (submitted || ended || PRACTICE) return;
    var q = Q[i]; if (!q.selected) return;
    q.selected = null; render();
    queue('a' + q.id, { qid: q.id, opt: null, key: null });
  }
  function toggleFlag(i, val) {
    var q = Q[i];
    q.flagged = typeof val === 'boolean' ? val : !q.flagged;
    render();
    if (!submitted && !ended) queue('f' + q.id, { qid: q.id, flag: q.flagged });
  }
  function onEnded(msg) {
    if (ended) return;
    ended = true; clearInterval(timer);
    toast(msg || 'Lượt thi đã kết thúc - đang mở kết quả…');
    setTimeout(function () { if (D.result_url) location.href = D.result_url; else location.reload(); }, 1800);
  }

  function answerButtons(el, i) {
    var q = Q[i]; el.innerHTML = '';
    var show = revealed(q), ck = show ? correctKey(q) : null;
    (q.options || []).forEach(function (o) {
      var b = document.createElement('button');
      b.type = 'button';
      var cls = 'sx-answer';
      if (q.selected === o.key) cls += ' sel';
      var tag = '';
      if (show && ck) {
        if (o.key === ck) { cls += ' right'; tag = q.selected === o.key ? 'Bạn chọn · Đúng' : 'Đáp án đúng'; }
        else if (q.selected === o.key) { cls += ' wrong'; tag = 'Bạn chọn · Sai'; }
      }
      b.className = cls;
      b.setAttribute('aria-pressed', q.selected === o.key ? 'true' : 'false');
      b.innerHTML = '<span class="sx-letter">' + esc(o.key) + '</span><span class="sx-copy"></span>' + (tag ? '<span class="sx-tag">' + esc(tag) + '</span>' : '<span class="sx-radio"></span>');
      b.querySelector('.sx-copy').textContent = o.text;
      if (PRACTICE && !submitted && !q.feedback && pending['a' + q.id] && q.selected === o.key) { tag = offline ? 'Chờ kết nối để chấm' : 'Đang chấm…'; b.innerHTML = '<span class="sx-letter">' + esc(o.key) + '</span><span class="sx-copy"></span><span class="sx-tag">' + esc(tag) + '</span>'; b.querySelector('.sx-copy').textContent = o.text; }
      b.onclick = function () { if (show) { if (submitted) { openExplain(i); } else { toast('Câu này đã chấm - xem đáp án và giải thích bên dưới.'); } return; } choose(i, o.key); };
      el.appendChild(b);
    });
  }

  function grid(el, large) {
    el.innerHTML = '';
    Q.forEach(function (q, i) {
      var b = document.createElement('button');
      b.type = 'button';
      var cls = 'sx-num';
      var v = verdictOf(q);
      if (v === true) cls += ' right';
      else if (v === false) cls += ' wrong';
      else if (q.selected) cls += ' done';
      if (!submitted && pending['a' + q.id]) cls += ' unsaved';
      if (i === current) cls += ' now';
      if (q.flagged) cls += ' flag';
      b.className = cls;
      b.textContent = i + 1;
      b.title = 'Câu ' + (i + 1) + ': ' + (q.selected ? 'đã trả lời' : 'chưa trả lời') + (q.flagged ? ', đánh dấu xem lại' : '') + (v === true ? ', đúng' : (v === false ? ', sai' : '')) + (!submitted && pending['a' + q.id] ? ', chưa lưu lên máy chủ' : '');
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
      if (revealed(q)) { var ex = document.createElement('button'); ex.type = 'button'; ex.className = 'sx-btn'; ex.textContent = 'Đáp án & căn cứ'; ex.onclick = function () { openExplain(i); }; g.appendChild(ex); }
      var go = document.createElement('button'); go.type = 'button'; go.className = 'sx-btn sx-primary'; go.textContent = 'Mở câu'; go.onclick = function () { current = i; setMode('focus'); };
      g.appendChild(go); f.appendChild(l); f.appendChild(g); c.appendChild(f); el.appendChild(c);
    });
    if (!shown) el.innerHTML = '<p class="sx-muted">Không có câu nào khớp bộ lọc.</p>';
  }

  function linkTo(u) { return (D.home_url || '/').replace(/\/$/, '') + u; }
  function practiceFeedback(fb, q) {
    var f = q.feedback, ok = f.is_correct === true;
    fb.classList.add('show', ok ? 'ok' : 'no');
    var html = '<h3>' + (ok ? 'Chính xác' : 'Chưa đúng') + '</h3>' +
      '<p>' + (f.correct_option_key || correctKey(q) ? 'Đáp án đúng: <strong>' + esc(correctKey(q) || f.correct_option_key) + '</strong>. ' : '') + (ok ? '' : 'Bạn chọn ' + esc(q.selected || '') + '.') + '</p>';
    if (f.explanation) html += '<div class="sx-explain">' + esc(f.explanation).replace(/\n/g, '<br>') + '</div>';
    if (f.article) html += '<div class="sx-citation"><strong>Căn cứ pháp luật</strong><p><a href="' + esc(linkTo(f.article.url)) + '" target="_blank" rel="noopener">' + esc(f.article.label) + '</a><br><small>' + esc(f.article.document) + '</small></p></div>';
    if (f.knowledge) html += '<p class="sx-learn">Học lại: <a href="' + esc(linkTo(f.knowledge.url)) + '" target="_blank" rel="noopener">' + esc(f.knowledge.title) + '</a></p>';
    if (!f.explanation && !f.article) html += '<p class="sx-muted">Câu hỏi này chưa có giải thích sẵn - bấm "Giải thích chi tiết" để xem diễn giải.</p>';
    html += '<div class="sx-group"><button type="button" class="sx-btn" data-act="explain">▤ Giải thích chi tiết</button>' + (current < TOTAL - 1 ? '<button type="button" class="sx-btn sx-primary" data-act="next">Câu tiếp theo →</button>' : '') + '</div>';
    fb.innerHTML = html;
    fb.querySelector('[data-act="explain"]').onclick = function () { openExplain(current); };
    var nx = fb.querySelector('[data-act="next"]'); if (nx) nx.onclick = function () { current = Math.min(TOTAL - 1, current + 1); render(); };
  }
  function feedback() {
    var fb = $('#sx-feedback'), q = Q[current];
    fb.className = 'sx-feedback';
    if (!submitted && PRACTICE && q.feedback) { practiceFeedback(fb, q); return; }
    if (!submitted && PRACTICE && pending['a' + q.id]) { fb.classList.add('show', 'x'); fb.innerHTML = '<p>' + (offline ? 'Mất kết nối - đáp án sẽ được chấm ngay khi có mạng trở lại.' : 'Đang chấm đáp án…') + '</p>'; return; }
    if (!submitted) { fb.innerHTML = ''; return; }
    var ck = correctKey(q);
    var ok = q.is_correct === true, no = q.is_correct === false;
    fb.classList.add('show', ok ? 'ok' : (no ? 'no' : 'x'));
    fb.innerHTML = '<h3>' + (!q.selected ? 'Bạn bỏ trống câu này' : (ok ? 'Trả lời đúng' : (no ? 'Trả lời chưa đúng' : 'Đã trả lời'))) + '</h3>' +
      '<p>' + (ck ? 'Đáp án đúng: <strong>' + esc(ck) + '</strong>. ' : '') + 'Xem giải thích và căn cứ pháp lý để nắm chắc kiến thức.</p>' +
      '<div class="sx-group"><button type="button" class="sx-btn" data-act="explain">▤ Giải thích &amp; căn cứ pháp lý</button></div>';
    fb.querySelector('[data-act="explain"]').onclick = function () { openExplain(current); };
  }

  /* Chủ đề câu hỏi: ẩn khi chỉ lặp lại tên đề (đề luyện tập theo 1 văn bản). */
  function norm(t) { return String(t || '').toLowerCase().replace(/[^\p{L}\p{N}]+/gu, ' ').trim(); }
  var examNorm = norm(D.exam && D.exam.title);
  function topicLabel(t) {
    var n = norm(t);
    if (!n) return '';
    var head = n.slice(0, 40);
    if (examNorm.indexOf(head) > -1 || n.indexOf(examNorm.slice(0, 40)) > -1) return '';
    return t;
  }
  function render() {
    if (!TOTAL) return;
    var q = Q[current];
    $('#sx-counter').textContent = 'Câu ' + (current + 1) + '/' + TOTAL;
    $('#sx-topic').textContent = topicLabel(q.topic);
    $('#sx-qtext').textContent = q.text;
    $('#sx-hintline').textContent = submitted ? 'Bài đã nộp - chọn một phương án để xem giải thích.' : (PRACTICE ? (q.feedback ? 'Câu đã chấm - đáp án đã khóa.' : 'Chọn một đáp án - đáp án đúng, giải thích và căn cứ pháp luật hiện ngay (câu đã chọn không đổi được).') : 'Chọn một đáp án đúng nhất.');
    $('#sx-flag').checked = !!q.flagged;
    $('#sx-note').value = notes[q.id] || '';
    answerButtons($('#sx-answers'), current);
    feedback();
    $('#sx-prev').disabled = current === 0;
    $('#sx-next').disabled = current === TOTAL - 1;
    $('#sx-clear').disabled = submitted || PRACTICE || !q.selected;
    if (PRACTICE) $('#sx-clear').title = 'Chế độ ôn tập: câu đã chọn không đổi được';
    $('#sx-flagBtn').textContent = q.flagged ? '⚑ Bỏ đánh dấu' : '⚑ Đánh dấu';
    grid($('#sx-grid')); grid($('#sx-largeGrid'), true);
    if (mode === 'list') list();
    var n = count(), pct = TOTAL ? Math.round(n / TOTAL * 100) : 0;
    $('#sx-answered').textContent = n; $('#sx-sideAnswered').textContent = n;
    $('#sx-mapcount').textContent = n + '/' + TOTAL;
    $('#sx-progress').style.width = pct + '%'; $('#sx-percent').textContent = pct + '%';
    var right = PRACTICE ? Q.filter(function (x) { return x.feedback && x.feedback.is_correct; }).length : 0;
    $('#sx-sidePercent').textContent = pct + '% hoàn thành' + (PRACTICE && n ? ' · đúng ' + right + '/' + n : '');
    var un = TOTAL - n, fl = Q.filter(function (x) { return x.flagged; }).length, rh = $('#sx-reviewHint');
    if (rh) rh.textContent = '(' + un + ' bỏ trống · ' + fl + ' đánh dấu)';
    paintSave();
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
    if (!revealed(q)) {
      box.innerHTML = PRACTICE
        ? '<p>Chọn một đáp án cho câu này - đáp án đúng, giải thích và căn cứ pháp luật sẽ hiện ngay.</p>'
        : '<p>Chế độ <strong>Thi thật</strong>: đáp án, giải thích và căn cứ pháp lý hiển thị sau khi bạn nộp bài - để kết quả phản ánh đúng năng lực. Muốn xem đáp án ngay sau mỗi câu, hãy chọn chế độ <strong>Ôn tập</strong> ở trang đề thi.</p>';
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

  /* --- Rà soát & nộp bài --- */
  function chips(list) {
    return '<div class="sx-chips">' + list.map(function (i) { return '<button type="button" class="sx-chip" data-go="' + i + '">' + (i + 1) + '</button>'; }).join('') + '</div>';
  }
  function reviewDialog() {
    if (submitted || busy || ended) return;
    var un = [], fl = [];
    Q.forEach(function (q, i) { if (!q.selected) un.push(i); if (q.flagged) fl.push(i); });
    var n = TOTAL - un.length, pend = pendingCount();
    var right = PRACTICE ? Q.filter(function (x) { return x.feedback && x.feedback.is_correct; }).length : 0;
    var html = '<div class="sx-revstats">' +
      '<div><strong>' + n + '/' + TOTAL + '</strong><span>Đã trả lời</span></div>' +
      '<div class="' + (un.length ? 'is-warn' : 'is-ok') + '"><strong>' + un.length + '</strong><span>Bỏ trống</span></div>' +
      '<div class="' + (fl.length ? 'is-flag' : '') + '"><strong>' + fl.length + '</strong><span>Đánh dấu xem lại</span></div>' +
      (PRACTICE ? '<div class="is-ok"><strong>' + right + '/' + n + '</strong><span>Đúng (ôn tập)</span></div>' : '') +
      (pend ? '<div class="is-warn"><strong>' + pend + '</strong><span>Chưa lưu lên máy chủ</span></div>' : '') +
      '</div>';
    html += '<h3>Câu bỏ trống</h3>' + (un.length ? chips(un) : '<p class="sx-muted">Không còn câu bỏ trống.</p>');
    html += '<h3>Câu đánh dấu xem lại</h3>' + (fl.length ? chips(fl) : '<p class="sx-muted">Không có câu đánh dấu.</p>');
    html += '<p class="sx-muted sx-revhint">Bấm số câu để mở thẳng câu đó.</p>';
    $('#sx-reviewBody').innerHTML = html;
    $$('#sx-reviewBody [data-go]').forEach(function (c) {
      c.onclick = function () { $('#sx-submitDialog').classList.remove('show'); current = Number(c.getAttribute('data-go')); if (mode !== 'focus') setMode('focus'); else render(); $('#sx-mainpanel').scrollIntoView({ block: 'start', behavior: 'smooth' }); };
    });
    var msg = $('#sx-submitMessage');
    msg.className = un.length || pend ? 'sx-revwarn' : 'sx-revok';
    msg.textContent = (pend ? 'Còn ' + pend + ' thay đổi chưa lưu lên máy chủ (đang ' + (offline ? 'mất kết nối' : 'gửi') + ') - nộp bây giờ thì các thay đổi này không được tính. ' : '') +
      (un.length ? 'Còn ' + un.length + ' câu chưa trả lời. Nếu nộp bây giờ, các câu này được tính 0 điểm và bạn không sửa được nữa.' : 'Bạn đã trả lời tất cả ' + TOTAL + ' câu. Sau khi nộp sẽ không sửa được đáp án.');
    $('#sx-confirm').textContent = un.length ? 'Vẫn nộp bài (' + un.length + ' câu bỏ trống)' : 'Nộp bài';
    $('#sx-submitDialog').classList.add('show');
    (un.length || pend ? $('#sx-cancel') : $('#sx-confirm')).focus();
  }
  function submitDialog() { flush(); reviewDialog(); }
  function submit(auto) {
    if (submitted || busy) return;
    busy = true;
    $('#sx-submitDialog').classList.remove('show');
    $('#sx-submitTop').disabled = true;
    $('#sx-submitTop').textContent = auto ? 'Hết giờ - đang nộp bài…' : 'Đang nộp bài…';
    var sc = !PRACTICE ? localScore() : null;
    if (sc) { toast('Kết quả sơ bộ: đúng ' + sc.right + '/' + sc.total + ' câu - đang chấm chính thức và mở phân tích…'); }
    // Gửi nốt các thay đổi đang chờ trước khi chấm (tối đa 6 giây).
    var wait = pendingCount() && !offline ? Promise.race([flush(), new Promise(function (r) { setTimeout(r, 6000); })]) : Promise.resolve();
    wait.then(function () { return post('cvc_exam_submit', {}); }).then(function (res) {
      busy = false;
      if (!res || !res.success) {
        if (res && res.data && res.data.already_submitted) { ended = true; pending = {}; persistPending(); if (D.result_url) { location.href = D.result_url; return; } location.reload(); return; }
        $('#sx-submitTop').disabled = false; $('#sx-submitTop').textContent = '▤ Nộp bài';
        toast((res && res.data && res.data.message) || 'Nộp bài thất bại, vui lòng thử lại.');
        return;
      }
      ended = true; pending = {}; persistPending();
      var p = res.data || {};
      try { sessionStorage.setItem(resultKey, JSON.stringify({ topic_stats: p.topic_stats || [], recommendations: p.recommendations || [] })); } catch (e) {}
      // Nộp xong -> mở thẳng trang Kết quả & Phân tích (/ket-qua-thi/{id}/).
      if (D.result_url) { submitted = true; $('#sx-submitTop').textContent = 'Đã nộp — đang mở kết quả…'; if (!sc) toast('Đã nộp bài. Đang mở trang kết quả và phân tích…'); location.href = D.result_url; return; }
      showResult(p.data || {}, true);
    }).catch(function () {
      busy = false; $('#sx-submitTop').disabled = false; $('#sx-submitTop').textContent = '▤ Nộp bài';
      toast(auto ? 'Mất kết nối khi tự nộp - máy chủ vẫn tự nộp bài với các đáp án đã lưu khi hết giờ.' : 'Lỗi kết nối khi nộp bài. Vui lòng kiểm tra mạng và thử lại.');
      if (auto) { setTimeout(pollClock, 15000); }
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
    $('#sx-showAnalysis').onclick = function () { if (D.result_url) { location.href = D.result_url; return; } if (fresh) { location.hash = 'phan-tich'; location.reload(); return; } $('#sx-resultDialog').classList.remove('show'); $('#sx-analysis').scrollIntoView({ behavior: 'smooth', block: 'center' }); };
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

  /* --- Đồng hồ: số giây do máy chủ tính, trình duyệt chỉ đếm theo performance.now() và đồng bộ mỗi phút --- */
  var tmode = (D.timer && D.timer.mode) || 'done', duration = (D.timer && D.timer.duration) || 0, timer = null;
  var clockBase = (D.timer && D.timer.seconds) || 0, clockAt = (window.performance && performance.now) ? performance.now() : Date.now();
  var autoSubmitting = false;
  function nowMs() { return (window.performance && performance.now) ? performance.now() : Date.now(); }
  function clockValue() { var dt = Math.floor((nowMs() - clockAt) / 1000); return tmode === 'countdown' ? Math.max(0, clockBase - dt) : clockBase + dt; }
  function syncClock(c) {
    if (!c || submitted) return;
    if (c.status && c.status !== 'in_progress') { onEnded('Lượt thi đã kết thúc - đang mở kết quả…'); return; }
    if (c.mode === 'countdown' && c.remaining_seconds !== null && c.remaining_seconds !== undefined) { tmode = 'countdown'; clockBase = Number(c.remaining_seconds) || 0; clockAt = nowMs(); }
    else if (c.mode === 'elapsed') { tmode = 'elapsed'; clockBase = Number(c.elapsed_seconds) || 0; clockAt = nowMs(); }
    paintClock();
  }
  function pollClock() { if (submitted || ended) return; post('cvc_exam_clock', {}).then(function (r) { if (r && r.success) syncClock(r.data); }).catch(function () {}); }
  function clock(t) { t = Math.max(0, t); var h = Math.floor(t / 3600), m = Math.floor(t % 3600 / 60), s = t % 60; return (h ? h + ':' : '') + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0'); }
  function paintClock() {
    var el = $('#sx-timer');
    if (tmode === 'done') { el.textContent = 'Đã nộp'; $('#sx-sideProgress').style.width = '0%'; return; }
    var v = clockValue();
    el.textContent = tmode === 'countdown' && v <= 0 ? 'Hết giờ' : clock(v);
    el.classList.toggle('warn', tmode === 'countdown' && v <= 300);
    $('#sx-sideProgress').style.width = (tmode === 'countdown' && duration ? Math.max(0, v / duration * 100) : 100) + '%';
  }
  var warned5 = false;
  function tick() {
    if (submitted || ended) { clearInterval(timer); return; }
    paintClock();
    if (tmode !== 'countdown') return;
    var v = clockValue();
    if (v <= 300 && v > 0 && !warned5) { warned5 = true; toast('Còn 5 phút - hãy rà soát các câu bỏ trống.'); }
    if (v <= 0 && !autoSubmitting) { autoSubmitting = true; clearInterval(timer); toast('Hết giờ - hệ thống tự nộp bài với các đáp án đã lưu.'); submit(true); }
  }
  setInterval(pollClock, 60000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { pollClock(); flush(); } });

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
  if ($('#sx-reviewTop')) $('#sx-reviewTop').onclick = submitDialog;
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
    else if (k === 'n' && mode === 'focus') { e.preventDefault(); var nb = $('#sx-notes'); nb.classList.toggle('sx-hidden'); if (!nb.classList.contains('sx-hidden')) { $('#sx-note').focus(); } }
    else if (e.key === 'Enter' && mode === 'focus' && ['BUTTON', 'A'].indexOf(tag) === -1) {
      e.preventDefault();
      if (current < TOTAL - 1) { current++; render(); } else { toast('Đây là câu cuối - bấm "Nộp bài" khi đã rà soát xong.'); }
    }
  });

  /* --- Liên kết kiến thức: mở/đóng danh sách theo nhóm --- */
  Array.prototype.forEach.call(document.querySelectorAll('[data-links]'), function (b) {
    b.addEventListener('click', function () {
      var open = b.getAttribute('aria-expanded') !== 'true';
      Array.prototype.forEach.call(document.querySelectorAll('[data-links]'), function (o) {
        o.setAttribute('aria-expanded', 'false');
        var l = document.getElementById('sx-links-' + o.getAttribute('data-links')); if (l) { l.hidden = true; }
      });
      if (open) { b.setAttribute('aria-expanded', 'true'); var list = document.getElementById('sx-links-' + b.getAttribute('data-links')); if (list) { list.hidden = false; } }
    });
  });

  /* --- Khởi động --- */
  if (!TOTAL) { $('#sx-qtext').textContent = 'Đề thi chưa có câu hỏi.'; return; }
  var firstOpen = Q.findIndex(function (q) { return !q.selected; });
  current = submitted ? 0 : (firstOpen > -1 ? firstOpen : 0);
  render();
  paintClock();
  // Hết giờ trong khoảng gia hạn khi vừa mở trang: tự nộp (máy chủ đã tự nộp nếu quá gia hạn).
  if (!submitted && tmode === 'countdown' && clockValue() <= 0) { autoSubmitting = true; setTimeout(function () { submit(true); }, 400); }
  if (!submitted) { paintSave(); flush(); }
  if (!submitted && (tmode === 'countdown' || tmode === 'elapsed')) timer = setInterval(tick, 1000);
  if (submitted) {
    renderAnalysis();
    if (location.hash === '#phan-tich') setTimeout(function () { $('#sx-analysis').scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 200);
  }
})();
