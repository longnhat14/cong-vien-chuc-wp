/**
 * Trang kết quả & phân tích bài thi (18/10/2026) - /ket-qua-thi/{id}/
 * Tương tác theo thiết kế smart-path-v6, nối dữ liệu thật qua admin-ajax (inc/exam-result.php):
 * lưu tiến độ nhiệm vụ / tài liệu đã học, tạo bài luyện cá nhân, bắt đầu đề, lưu tin tuyển dụng,
 * giải thích câu sai (AI Coach). Kế hoạch 7 ngày dựng tại chỗ từ dữ liệu báo cáo.
 */
(function () {
  'use strict';
  var C = window.CVC_RESULT;
  var root = document.getElementById('cvc-result');
  var toastEl = document.getElementById('toast');
  var timer;
  function note(s) { if (!toastEl) return; toastEl.textContent = s; toastEl.classList.add('show'); clearTimeout(timer); timer = setTimeout(function () { toastEl.classList.remove('show'); }, 3600); }
  function $(s, el) { return (el || document).querySelector(s); }
  function $$(s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  $$('#print').forEach(function (b) { b.onclick = function () { window.print(); }; });
  if (!C || !root) return;

  function post(action, fields) {
    var fd = new FormData();
    fd.append('action', action); fd.append('nonce', C.nonce); fd.append('attempt_id', C.attempt);
    Object.keys(fields || {}).forEach(function (k) {
      var v = fields[k];
      if (Array.isArray(v)) { if (!v.length) fd.append(k + '[]', ''); v.forEach(function (x) { fd.append(k + '[]', x); }); } else if (v !== undefined && v !== null) fd.append(k, v);
    });
    return fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
      return r.json().catch(function () { return { success: false, data: { message: 'Máy chủ phản hồi không hợp lệ (' + r.status + ').' } }; }).then(function (j) { j._status = r.status; return j; });
    });
  }
  function fail(res, fallback) {
    if (res && res._status === 401) { note('Phiên đăng nhập đã hết hạn — đang chuyển tới trang đăng nhập…'); setTimeout(function () { location.href = C.login; }, 1200); return; }
    note((res && res.data && res.data.message) || fallback || 'Có lỗi xảy ra, vui lòng thử lại.');
  }

  /* --- Xem lại câu trả lời: lọc + xem thêm --- */
  var qs = $$('#questions .question');
  var shown = 10, filter = 'all';
  var more = $('#more');
  function applyQ() {
    var idx = 0;
    qs.forEach(function (q) {
      var vis = filter === 'all' ? idx < shown : q.dataset.status === filter;
      q.hidden = !vis; idx++;
    });
    if (more) more.hidden = !(filter === 'all' && shown < qs.length);
  }
  function setTab(f) {
    filter = f;
    $$('.tab').forEach(function (t) { var on = t.dataset.filter === f; t.classList.toggle('active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
    applyQ();
  }
  $$('.tab').forEach(function (b) { b.onclick = function () { setTab(b.dataset.filter); }; });
  if (more) more.onclick = function () { shown += 20; applyQ(); };

  var improve = $('#improve');
  if (improve) improve.onclick = function () { $('#progress-center').scrollIntoView({ behavior: 'smooth' }); note('Bắt đầu kế hoạch tiến bộ từ các nhiệm vụ hôm nay — tiến độ được lưu vào tài khoản.'); };

  /* --- Nhiệm vụ hôm nay + tài liệu đã học (lưu theo tài khoản) --- */
  var boxes = $$('[data-task]');
  var resDone = (C.resources || []).slice();
  var saveTimer = null;
  function updateTasks() {
    var n = boxes.filter(function (x) { return x.checked; }).length;
    var dc = $('#done-count'); if (dc) dc.textContent = n;
    var fill = $('#task-progress-fill'); if (fill) fill.style.width = (boxes.length ? n / boxes.length * 100 : 0) + '%';
    boxes.forEach(function (x) { x.closest('.task').classList.toggle('done', x.checked); });
  }
  function save(silent) {
    clearTimeout(saveTimer);
    var done = boxes.filter(function (x) { return x.checked; }).map(function (x) { return x.dataset.task; });
    var btn = $('#save-progress'); if (btn) btn.disabled = true;
    return post('cvc_result_tasks', { done: done, resources: resDone }).then(function (res) {
      if (btn) btn.disabled = false;
      if (!res || !res.success) { fail(res, 'Chưa lưu được tiến độ.'); return; }
      var at = $('#saved-at');
      if (at) { var d = new Date(); at.textContent = 'Đã lưu lúc ' + ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
      if (!silent) note('Đã lưu tiến độ vào tài khoản của bạn.');
    }).catch(function () { if (btn) btn.disabled = false; note('Lỗi kết nối — tiến độ chưa được lưu.'); });
  }
  function saveSoon() { clearTimeout(saveTimer); saveTimer = setTimeout(function () { save(true); }, 1200); }
  boxes.forEach(function (x) { x.addEventListener('change', function () { updateTasks(); saveSoon(); }); });
  var sp = $('#save-progress'); if (sp) sp.onclick = function () { save(false); };
  var rp = $('#reset-progress'); if (rp) rp.onclick = function () { boxes.forEach(function (x) { x.checked = false; }); updateTasks(); save(false); };
  var ep = $('#export-plan'); if (ep) ep.onclick = function () { window.print(); };
  updateTasks();

  /* --- Bộ lọc bài thi --- */
  function filterQuizzes(f) {
    $$('#quiz-filters button').forEach(function (b) { b.classList.toggle('active', b.dataset.filter === f); });
    var n = 0;
    $$('#quiz-list .quiz-item').forEach(function (card) {
      var k = card.dataset.kind;
      var vis = f === 'all' || (f === 'recommended' && k === 'recommended') || (f === 'wrong' && k === 'wrong');
      card.hidden = !vis; if (vis) { n++; var ic = $('.item-icon', card); if (ic) ic.textContent = (n < 10 ? '0' : '') + n; }
    });
    var em = $('#quiz-empty'); if (em) em.hidden = n > 0;
  }
  $$('#quiz-filters button').forEach(function (b) { b.onclick = function () { filterQuizzes(b.dataset.filter); }; });
  filterQuizzes('recommended');

  /* --- Mục tiêu + bộ lọc tuyển dụng --- */
  var goal = 'all', jobF = 'all';
  var goalNames = { all: 'Tất cả cơ hội', civil: 'Thi tuyển công chức', 'public': 'Thi tuyển viên chức', teacher: 'Giáo viên', health: 'Y tế' };
  var reasonEl = $('#personal-reason');
  var reasonBase = reasonEl ? reasonEl.textContent : '';
  function filterJobs() {
    $$('#job-filters button').forEach(function (b) { b.classList.toggle('active', b.dataset.jobFilter === jobF); });
    var n = 0;
    $$('#job-list .job-item').forEach(function (card) {
      var t = card.dataset.jobType, s = card.dataset.sector;
      var okF = jobF === 'all' || t === jobF;
      var okG = goal === 'all' || (goal === 'civil' && t === 'civil') || (goal === 'public' && t === 'public') || (goal === 'teacher' && s === 'teacher') || (goal === 'health' && s === 'health');
      card.hidden = !(okF && okG); if (okF && okG) n++;
    });
    var em = $('#job-empty'); if (em) em.hidden = n > 0;
  }
  $$('#job-filters button').forEach(function (b) { b.onclick = function () { jobF = b.dataset.jobFilter; filterJobs(); }; });
  var gs = $('#goal-select');
  if (gs) {
    try { var g0 = localStorage.getItem('cvc-result-goal'); if (g0 && goalNames[g0]) gs.value = g0; } catch (e) {}
    var onGoal = function (user) {
      goal = gs.value;
      if (goal === 'civil' || goal === 'public') jobF = goal; else if (user) jobF = 'all';
      filterJobs();
      if (reasonEl) reasonEl.textContent = goal === 'all' ? reasonBase : 'Mục tiêu đã chọn: ' + goalNames[goal] + '. Mục "Cơ hội tuyển dụng" chỉ hiển thị tin còn hạn khớp mục tiêu này. ' + reasonBase;
      try { localStorage.setItem('cvc-result-goal', goal); } catch (e) {}
    };
    gs.addEventListener('change', function () { onGoal(true); });
    onGoal(false);
  }

  /* --- Nút hành động (ủy quyền sự kiện) --- */
  function busy(btn, on, label) {
    if (!btn) return;
    if (on) { btn.dataset.label = btn.innerHTML; btn.classList.add('busy'); btn.disabled = true; if (label) btn.textContent = label; }
    else { btn.classList.remove('busy'); btn.disabled = false; if (btn.dataset.label) btn.innerHTML = btn.dataset.label; }
  }
  function startPractice(kind, btn) {
    busy(btn, true, btn && btn.classList.contains('side-btn') ? null : 'Đang tạo bài luyện…');
    note('Đang chọn câu hỏi từ ngân hàng đã duyệt…');
    post('cvc_result_practice', { kind: kind }).then(function (res) {
      if (res && res.success && res.data && res.data.url) { location.href = res.data.url; return; }
      busy(btn, false); fail(res, 'Chưa tạo được bài luyện.');
    }).catch(function () { busy(btn, false); note('Lỗi kết nối, vui lòng thử lại.'); });
  }
  function startExam(id, btn) {
    busy(btn, true, 'Đang mở đề…');
    post('cvc_result_start', { exam_id: id }).then(function (res) {
      if (res && res.success && res.data && res.data.url) { location.href = res.data.url; return; }
      busy(btn, false); fail(res, 'Chưa bắt đầu được đề thi.');
    }).catch(function () { busy(btn, false); note('Lỗi kết nối, vui lòng thử lại.'); });
  }
  function toggleResource(btn) {
    var card = btn.closest('.resource-item'); var key = card.dataset.key; var pill = $('.state-pill', card);
    if (!pill.dataset.orig) { pill.dataset.orig = pill.textContent; pill.dataset.cls = pill.className; }
    var i = resDone.indexOf(key);
    if (i >= 0) { resDone.splice(i, 1); } else { resDone.push(key); }
    var on = resDone.indexOf(key) >= 0;
    if (on) { pill.textContent = 'Đã học'; pill.className = 'state-pill'; btn.textContent = 'Bỏ đánh dấu'; btn.classList.add('is-on'); }
    else { pill.textContent = pill.dataset.orig === 'Đã học' ? 'Ôn lại' : pill.dataset.orig; pill.className = pill.dataset.orig === 'Đã học' ? 'state-pill neutral' : pill.dataset.cls; btn.textContent = 'Đánh dấu đã học'; btn.classList.remove('is-on'); }
    save(true).then(function () { note(on ? 'Đã đánh dấu tài liệu là đã học.' : 'Đã bỏ đánh dấu tài liệu.'); });
  }
  function saveJob(btn) {
    var card = btn.closest('.job-item');
    busy(btn, true, 'Đang lưu…');
    post('cvc_result_save_job', { recruitment_id: card.dataset.id }).then(function (res) {
      if (res && res.success) { btn.classList.remove('busy'); btn.textContent = 'Đã lưu'; btn.disabled = true; note('Đã lưu tin vào mục Đánh dấu của tài khoản.'); return; }
      busy(btn, false); fail(res, 'Chưa lưu được tin.');
    }).catch(function () { busy(btn, false); note('Lỗi kết nối, vui lòng thử lại.'); });
  }
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-action]');
    if (!btn || btn.tagName === 'A') return;
    var act = btn.dataset.action;
    if (act === 'practice') startPractice(btn.dataset.kind, btn);
    else if (act === 'start-exam') startExam(btn.dataset.exam, btn);
    else if (act === 'mark-resource') toggleResource(btn);
    else if (act === 'save-job') saveJob(btn);
    else if (act === 'goto') { var t = document.getElementById(btn.dataset.target); if (t) t.scrollIntoView({ behavior: 'smooth' }); }
  });

  /* --- AI Coach: giải thích câu sai --- */
  function renderExplain(d) {
    var verdict = d.is_correct === true ? 'Bạn trả lời đúng.' : (d.your_option_id ? 'Bạn trả lời chưa đúng.' : 'Bạn chưa trả lời câu này.');
    var h = '<p><strong>' + esc(verdict) + '</strong>' + (d.correct_option_key ? ' Đáp án đúng: <strong>' + esc(d.correct_option_key) + '</strong>.' : '') + '</p>';
    h += d.explanation ? '<p>' + esc(d.explanation).replace(/\n/g, '<br>') + '</p>' : '<p>Câu hỏi này chưa có phần giải thích.</p>';
    var base = (window.location.origin || '') + '/';
    if (d.legal_reference && d.legal_reference.slug) h += '<p>Căn cứ: <a href="' + base + 'van-ban-phap-luat/' + encodeURIComponent(d.legal_reference.slug) + '/" target="_blank" rel="noopener">' + esc((d.legal_reference.document_number ? d.legal_reference.document_number + ' — ' : '') + (d.legal_reference.title || '')) + '</a></p>';
    if (d.knowledge_item && d.knowledge_item.slug) h += '<p>Học tiếp: <a href="' + base + 'kien-thuc/' + encodeURIComponent(d.knowledge_item.slug) + '/" target="_blank" rel="noopener">' + esc(d.knowledge_item.title) + '</a></p>';
    h += '<p class="ai-src">' + (d.source === 'ai' ? 'Phần diễn giải do AI viết dựa trên đáp án chuẩn; căn cứ theo văn bản gốc.' : 'Giải thích có sẵn trong ngân hàng câu hỏi.') + '</p>';
    return h;
  }
  function ask(btn) {
    var out = btn.parentNode.querySelector('.ai-out');
    if (out && out.dataset.loaded) { out.hidden = !out.hidden; return; }
    busy(btn, true, 'AI Coach đang phân tích…');
    post('cvc_result_explain', { question_id: btn.dataset.ask }).then(function (res) {
      busy(btn, false);
      if (!res || !res.success) { fail(res, 'Chưa tải được giải thích.'); return; }
      out.innerHTML = renderExplain(res.data || {}); out.hidden = false; out.dataset.loaded = '1';
    }).catch(function () { busy(btn, false); note('Lỗi kết nối, vui lòng thử lại.'); });
  }
  $$('.ask-ai').forEach(function (b) { b.onclick = function () { ask(b); }; });

  /* --- Báo câu sai (25/10/2026) --- */
  $$('.qreport').forEach(function (box) {
    var btn = $('.qreport-btn', box), form = $('.qreport-form', box);
    if (!btn || !form) return;
    btn.onclick = function () { form.hidden = !form.hidden; btn.setAttribute('aria-expanded', form.hidden ? 'false' : 'true'); };
    $('.qreport-cancel', form).onclick = function () { form.hidden = true; btn.setAttribute('aria-expanded', 'false'); };
    form.onsubmit = function (ev) {
      ev.preventDefault();
      var reason = (form.querySelector('input[name="reason"]:checked') || {}).value;
      if (!reason) { note('Chọn lý do báo câu sai.'); return; }
      var send = $('.qreport-send', form);
      busy(send, true, 'Đang gửi…');
      post('cvc_result_report', { question_id: btn.dataset.report, reason: reason, note: form.note.value }).then(function (res) {
        busy(send, false);
        if (!res || !res.success) { fail(res, 'Chưa gửi được báo cáo.'); return; }
        form.hidden = true; btn.disabled = true; btn.textContent = '✓ Đã báo câu sai';
        note((res.data && res.data.message) || 'Cảm ơn bạn! Báo cáo đã được ghi nhận.');
      }).catch(function () { busy(send, false); note('Lỗi kết nối, vui lòng thử lại.'); });
    };
  });
  var why = $('#why');
  if (why) why.onclick = function () {
    var first = qs.filter(function (q) { return q.dataset.status === 'wrong'; })[0] || qs.filter(function (q) { return q.dataset.status === 'blank'; })[0];
    if (!first) { note('Bài này không có câu sai.'); return; }
    setTab(first.dataset.status);
    first.scrollIntoView({ behavior: 'smooth', block: 'start' });
    var b = $('.ask-ai', first); if (b) ask(b);
  };

  /* --- Kế hoạch ôn 7 ngày (dựng từ kết quả bài này) --- */
  var seven = $('#seven');
  if (seven) seven.onclick = function () {
    var box = $('#week-plan');
    if (!box.hidden) { box.hidden = true; return; }
    var c = C.counts || {}, miss = (c.wrong || 0) + (c.blank || 0);
    var res = C.resourcesL || [], qz = C.quizzesL || [], tp = C.topics || [];
    var practice = function (kind) { for (var i = 0; i < qz.length; i++) { if (qz[i].action && qz[i].action.type === 'practice' && qz[i].action.kind === kind) return qz[i]; } return null; };
    var link = function (r) { return '<a href="' + esc(r.url) + '" target="_blank" rel="noopener">' + esc(r.title) + '</a>'; };
    var btn = function (q, label) { return q ? ' <button type="button" class="ask-ai" data-action="practice" data-kind="' + esc(q.action.kind) + '">' + esc(label) + '</button>' : ''; };
    var weak = C.weakest, second = tp[1] && tp[1].level !== 'strong' ? tp[1].name : null;
    var days = [
      ['Rà soát bài làm', miss > 0 ? 'Đọc giải thích và căn cứ của ' + miss + ' câu sai/bỏ trống; ghi lại quy tắc hay nhầm.' : 'Đọc lại giải thích các câu đã làm.'],
      ['Học tài liệu ưu tiên', res.length ? res.slice(0, 2).map(link).join('<br>') : 'Ôn kiến thức nền của ' + esc(weak || 'môn thi') + '.', true],
      ['Củng cố chủ đề yếu', 'Ôn ' + esc(weak || 'chủ đề có tỷ lệ đúng thấp nhất') + ', rồi làm bài củng cố.' + btn(practice('weak'), 'Làm bài củng cố'), true],
      ['Mở rộng', (res.length > 2 ? res.slice(2, 4).map(link).join('<br>') : '') + (second ? (res.length > 2 ? '<br>' : '') + 'Ôn thêm: ' + esc(second) + '.' : (res.length > 2 ? '' : 'Ôn lại ghi chú ngày 1–3.')), true],
      ['Ôn câu sai (nhắc lại sau 48 giờ)', 'Làm lại câu sai để ghi nhớ lâu hơn.' + btn(practice('wrong'), 'Ôn câu sai'), true],
      ['Ôn nhanh', 'Xem lại ghi chú và 3 kiến thức quan trọng đã lưu; đọc lại điều luật của câu còn sai.'],
      ['Đo tiến bộ', 'Làm bài kiểm tra lại và so sánh với ' + (c.total ? Math.round((c.correct || 0) / c.total * 100) : 0) + '% của lượt này.' + btn(practice('recheck'), 'Kiểm tra lại'), true]
    ];
    box.innerHTML = '<b>Kế hoạch ôn 7 ngày</b><ol>' + days.map(function (d, i) { return '<li><strong>Ngày ' + (i + 1) + ': ' + esc(d[0]) + '</strong><span>' + (d[2] ? d[1] : esc(d[1])) + '</span></li>'; }).join('') + '</ol><p class="wp-note">Kế hoạch dựng tự động từ kết quả bài này (chủ đề yếu, tài liệu và bài luyện đề xuất ở trên).</p>';
    box.hidden = false;
  };
})();
