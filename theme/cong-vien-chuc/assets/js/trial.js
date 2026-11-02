/* Khách làm thử 5 câu không cần đăng nhập (02/11/2026). Chấm ngay trên trình duyệt; phiên + từng câu + rời trang
   được ghi ở /api/trial/* để đo phễu (làm tiếp / rời đi). Đăng nhập xong phiên được gắn vào tài khoản (cookie cvc_trial_sid). */
(function () {
  'use strict';
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function visitor() {
    try { var v = localStorage.getItem('cvc-v'); if (!v) { v = Math.random().toString(36).slice(2) + Date.now().toString(36); localStorage.setItem('cvc-v', v); } return v; } catch (e) { return ''; }
  }
  function setCookie(sid) { document.cookie = 'cvc_trial_sid=' + sid + ';path=/;max-age=' + (3 * 86400) + ';SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : ''); }

  document.querySelectorAll('[data-trial]').forEach(function (box) {
    var api = box.getAttribute('data-api');
    var el = function (s) { return box.querySelector(s); };
    var st = { sid: '', qs: [], i: 0, right: 0, done: false, started: false, answers: [] };

    function event(type, extra) {
      if (!st.sid) return;
      var body = 'sid=' + encodeURIComponent(st.sid) + '&type=' + type + (extra || '');
      if (type === 'left' && navigator.sendBeacon) {
        navigator.sendBeacon(api + '/api/trial/event', new Blob([body], { type: 'application/x-www-form-urlencoded' }));
        return;
      }
      fetch(api + '/api/trial/event', { method: 'POST', body: body, keepalive: true, headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' } }).catch(function () {});
    }
    function err(t) { var e = el('[data-tr-err]'); e.textContent = t; e.hidden = !t; }

    function start() {
      err('');
      var b = el('[data-tr-start]'); if (b) { b.disabled = true; b.textContent = 'Đang lấy câu hỏi…'; }
      fetch(api + '/api/trial/start', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ source: box.dataset.source, ref: box.dataset.ref || location.pathname, exam: box.dataset.exam || null,
          recruitment_id: parseInt(box.dataset.rec || '0', 10) || null, referrer: document.referrer || null, visitor: visitor(),
          device: window.matchMedia && window.matchMedia('(max-width:760px)').matches ? 'mobile' : 'desktop' })
      }).then(function (r) { return r.json(); }).then(function (j) {
        if (!j || !j.success || !j.data.questions.length) throw new Error((j && j.message) || 'Chưa lấy được câu hỏi.');
        st = { sid: j.data.sid, qs: j.data.questions, i: 0, right: 0, done: false, started: true, answers: [] };
        setCookie(st.sid);
        el('[data-tr-intro]').hidden = true; el('[data-tr-end]').hidden = true; el('[data-tr-play]').hidden = false;
        show();
      }).catch(function (e) { err(e.message + ' Thử lại sau ít phút.'); if (b) { b.disabled = false; b.textContent = 'Bắt đầu 5 câu →'; } });
    }

    function show() {
      var q = st.qs[st.i], n = st.qs.length;
      el('[data-tr-pos]').textContent = 'Câu ' + (st.i + 1) + '/' + n;
      el('[data-tr-prog]').style.width = Math.round(st.i / n * 100) + '%';
      el('[data-tr-score]').textContent = st.i ? 'Đúng ' + st.right + '/' + st.i : '';
      el('[data-tr-q]').textContent = q.text;
      var opts = el('[data-tr-opts]'); opts.innerHTML = '';
      q.options.forEach(function (o) {
        var b = document.createElement('button'); b.type = 'button'; b.className = 'tr-opt'; b.dataset.id = o.id;
        b.innerHTML = '<b>' + esc(o.key) + '</b><span>' + esc(o.text) + '</span>';
        b.addEventListener('click', function () { pick(o.id); });
        opts.appendChild(b);
      });
      var fb = el('[data-tr-fb]'); fb.hidden = true; fb.innerHTML = '';
      el('[data-tr-next]').hidden = true;
      if (st.i > 0) { var top = box.getBoundingClientRect().top; if (top < 0) box.scrollIntoView({ block: 'start', behavior: 'smooth' }); }
    }

    function pick(id) {
      var q = st.qs[st.i]; if (q.picked) return;
      q.picked = id;
      var ok = q.correct_option_id ? id === q.correct_option_id : false;
      if (ok) st.right++;
      st.answers.push(ok);
      box.querySelectorAll('.tr-opt').forEach(function (b) {
        var bid = parseInt(b.dataset.id, 10); b.disabled = true;
        if (bid === q.correct_option_id) b.classList.add('is-right');
        else if (bid === id) b.classList.add('is-wrong');
      });
      var fb = el('[data-tr-fb]');
      var art = q.article ? '<p class="tr-art">Căn cứ: ' + esc((q.article.label || '') + (q.article.document ? ' - ' + q.article.document : '')) + '</p>' : '';
      fb.innerHTML = '<p class="tr-verdict ' + (ok ? 'is-ok' : 'is-no') + '">' + (ok ? '✓ Chính xác' : '✗ Chưa đúng - đáp án đúng: ' + esc(q.correct_key || '')) + '</p>'
        + (q.explanation ? '<p class="tr-exp">' + esc(q.explanation) + '</p>' : '') + art;
      fb.hidden = false;
      event('answer', '&q=' + q.id + '&option=' + id);
      var last = st.i >= st.qs.length - 1, nx = el('[data-tr-next]');
      nx.textContent = last ? 'Xem kết quả →' : 'Câu tiếp →'; nx.hidden = false; nx.focus({ preventScroll: true });
      el('[data-tr-score]').textContent = 'Đúng ' + st.right + '/' + (st.i + 1);
    }

    function finish() {
      st.done = true;
      var n = st.qs.length;
      el('[data-tr-play]').hidden = true; el('[data-tr-end]').hidden = false;
      el('[data-tr-end-h]').textContent = 'Bạn đúng ' + st.right + '/' + n + ' câu';
      var pct = st.right / n;
      el('[data-tr-end-sub]').textContent = pct >= 0.8 ? 'Rất tốt! Vòng 1 cần đúng từ 30/60 câu (50%). Giữ nhịp 10 câu mỗi ngày để chắc chắn qua ngưỡng.'
        : (pct >= 0.5 ? 'Đang ở quanh ngưỡng qua vòng 1 (đúng từ 50%). Ôn đều 10 câu mỗi ngày, câu sai sẽ tự quay lại để bạn nhớ.'
          : 'Chưa tới ngưỡng 50% của vòng 1 - đừng lo, câu sai sẽ được lưu và tự quay lại ôn cho tới khi thuộc.');
      var dots = el('[data-tr-dots]'); dots.innerHTML = '';
      st.answers.forEach(function (ok, k) { var li = document.createElement('li'); li.className = ok ? 'is-ok' : 'is-no'; li.textContent = (k + 1); li.title = 'Câu ' + (k + 1) + (ok ? ': đúng' : ': sai'); dots.appendChild(li); });
      box.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    var sb = el('[data-tr-start]'); if (sb) sb.addEventListener('click', start);
    el('[data-tr-next]').addEventListener('click', function () { if (st.i >= st.qs.length - 1) finish(); else { st.i++; show(); } });
    el('[data-tr-again]').addEventListener('click', function () { el('[data-tr-end]').hidden = true; el('[data-tr-intro]').hidden = false; var b = el('[data-tr-start]'); b.disabled = false; b.textContent = 'Bắt đầu 5 câu →'; start(); });
    box.querySelectorAll('[data-tr-cta]').forEach(function (a) {
      a.addEventListener('click', function () {
        event('cta');
        if (a.hasAttribute('data-tr-gate')) {
          var g = document.querySelector(box.dataset.gate);
          if (g) { g.hidden = false; g.classList.add('is-hl'); g.scrollIntoView({ block: 'start', behavior: 'smooth' }); var h = g.querySelector('h2'); if (h) h.textContent = 'Lưu kết quả và làm tiếp cả đề'; }
        }
      });
    });
    var left = function () { if (st.started && !st.done && st.sid) { event('left'); st.started = false; } };
    window.addEventListener('pagehide', left);
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') left(); else if (st.sid && !st.done) st.started = true; });
    if (box.dataset.autostart === '1') start();
  });
}());
