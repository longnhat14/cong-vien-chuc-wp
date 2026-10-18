/**
 * Career Intelligence (18/10/2026) - /tuyen-dung/ và khối "liên quan" ở trang tin.
 * HTML luôn do máy chủ render (AJAX cvc_ci_view) - JS chỉ gom bộ lọc, đổi tab, phân trang,
 * và gửi thao tác ghi (cvc_ci_act): lưu, nhắc hạn, mục tiêu, tùy chọn, lộ trình, bài luyện.
 */
(function () {
  'use strict';
  var C = window.CVC_CI;
  var root = document.querySelector('.ci');
  if (!C || !root) return;

  function $(s, el) { return (el || document).querySelector(s); }
  function $$(s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); }
  var toastEl = $('#ci-toast');
  var tt;
  function toast(msg, html) {
    if (!toastEl) return;
    if (html) toastEl.innerHTML = msg; else toastEl.textContent = msg;
    toastEl.classList.add('is-show');
    clearTimeout(tt); tt = setTimeout(function () { toastEl.classList.remove('is-show'); }, 4200);
  }

  /* ---------- Bộ lọc ---------- */
  var form = $('#ci-filters');
  var state = { tab: root.getAttribute('data-tab') || 'jobs', tin: 0, mode: 'all' };

  function filters() {
    var p = {};
    if (form) {
      var fam = $('#ci-f-family', form), prov = $('#ci-f-province', form), st = $('#ci-f-status', form), sort = $('#ci-f-sort', form), m = $('#ci-f-method', form), luu = form.querySelector('[name=luu]');
      if (fam && fam.value) p.nhom = fam.value;
      if (prov && prov.value) p.tinh = prov.value;
      if (st && st.value) p['trang-thai'] = st.value;
      if (sort && sort.value) p['sap-xep'] = sort.value;
      if (m && m.value) p['phuong-thuc'] = m.value;
      if (luu && luu.checked) p.luu = 1;
      var kinds = $$('[name="loai[]"]:checked', form).map(function (x) { return x.value; });
      if (kinds.length) p.loai = kinds.join(',');
    }
    var q = $('#ci-q');
    if (q && q.value.trim()) p.q = q.value.trim();
    var et = $('#ci-e-type'), ed = $('#ci-e-done'), es = $('#ci-e-sort');
    if (et && et.value) p['loai-de'] = et.value;
    if (ed && ed.value) p['da-lam'] = ed.value;
    if (es && es.value) p['de-sap-xep'] = es.value;
    if (state.tin) p.tin = state.tin;
    return p;
  }
  function countFilters(p) {
    var n = 0;
    ['nhom', 'tinh', 'trang-thai', 'phuong-thuc', 'luu'].forEach(function (k) { if (p[k]) n++; });
    if (p.loai) n += String(p.loai).split(',').length;
    var el = $('#ci-filter-count'); if (el) el.textContent = n + ' lọc';
    return n;
  }
  function pageUrl(p, page) {
    var u = new URL(page > 1 ? C.base + 'page/' + page + '/' : C.base, location.origin);
    Object.keys(p).forEach(function (k) { if (['loai-de', 'da-lam', 'de-sap-xep', 'tin'].indexOf(k) < 0) u.searchParams.set(k, p[k]); });
    if (state.tab !== 'jobs') u.searchParams.set('tab', state.tab);
    return u.pathname + u.search;
  }

  /* ---------- Tải HTML ---------- */
  var seq = {};
  function load(view, page, extra) {
    var target = { jobs: '#ci-jobs', exams: '#ci-exams', resources: '#ci-resources', path: '#ci-path', personal: '#ci-personal-wrap', reminders: '#ci-reminders-wrap', related: '#ci-related' }[view];
    var box = $(target);
    if (!box) return Promise.resolve();
    var p = filters();
    var body = new URLSearchParams();
    body.set('action', 'cvc_ci_view'); body.set('view', view); body.set('page', page || 1);
    Object.keys(p).forEach(function (k) { body.set(k, p[k]); });
    Object.keys(extra || {}).forEach(function (k) { body.set(k, extra[k]); });
    var my = seq[view] = (seq[view] || 0) + 1;
    box.classList.add('is-loading'); box.setAttribute('aria-busy', 'true');
    return fetch(C.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (my !== seq[view]) return;
        box.classList.remove('is-loading'); box.removeAttribute('aria-busy');
        if (!res || !res.success) { box.innerHTML = '<div class="ci-empty is-error"><b>Không tải được dữ liệu</b><button type="button" class="ci-btn" data-ci="retry" data-view="' + view + '">Thử lại</button></div>'; return; }
        box.innerHTML = res.data.html;
        box.setAttribute('data-loaded', '1');
        if (view === 'jobs') history.replaceState(null, '', pageUrl(p, page || 1));
      })
      .catch(function () {
        if (my !== seq[view]) return;
        box.classList.remove('is-loading');
        box.innerHTML = '<div class="ci-empty is-error"><b>Lỗi kết nối</b>Kiểm tra mạng và thử lại. <button type="button" class="ci-btn" data-ci="retry" data-view="' + view + '">Thử lại</button></div>';
      });
  }
  function refreshAll() {
    countFilters(filters());
    showRelated(false);
    load('jobs', 1);
    ['exams', 'resources'].forEach(function (v) { var b = $('#ci-' + v); if (b) b.setAttribute('data-loaded', '0'); });
    if (state.tab === 'exams' || state.tab === 'resources') load(state.tab, 1);
  }

  /* ---------- Tabs ---------- */
  function setTab(tab, push) {
    if (!$('#ci-tab-' + tab)) return;
    state.tab = tab;
    $$('#ci-tabs [data-ci-tab]').forEach(function (a) { var on = a.getAttribute('data-ci-tab') === tab; a.classList.toggle('is-active', on); a.setAttribute('aria-selected', on ? 'true' : 'false'); });
    $$('.ci-pane').forEach(function (p) { p.classList.toggle('is-active', p.id === 'ci-tab-' + tab); });
    var box = $('#ci-' + tab);
    if (box && box.getAttribute('data-loaded') === '0') load(tab, 1);
    if (push !== false) history.replaceState(null, '', pageUrl(filters(), 1));
  }
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-ci-tab]');
    if (!t) return;
    e.preventDefault();
    setTab(t.getAttribute('data-ci-tab'));
    var tabs = $('#ci-tabs'); if (tabs) tabs.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  /* ---------- Chế độ tìm kiếm ---------- */
  var hint = $('#ci-search-hint');
  var hints = {
    all: 'Tìm đồng thời: tin tuyển dụng (tab Tuyển dụng), đề thi và văn bản (các tab tương ứng) theo cùng từ khóa và bộ lọc.',
    jobs: 'Tìm theo tin tuyển dụng: tên tin, cơ quan, vị trí, số hiệu thông báo. Bấm “Xem đề & tài liệu” trên mỗi tin để xem nội dung cần chuẩn bị.',
    exams: 'Tìm theo đề thi / văn bản: tên đề, mã đề, số hiệu hoặc tên văn bản.',
    personal: 'Theo mục tiêu cá nhân: ưu tiên tin, đề và văn bản khớp nhóm vị trí và địa phương trong mục tiêu bạn đã lưu.'
  };
  $$('.ci-mode').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('.ci-mode').forEach(function (x) { x.classList.toggle('is-active', x === b); });
      state.mode = b.getAttribute('data-mode');
      if (hint) hint.textContent = hints[state.mode] || '';
      if (state.mode === 'jobs') setTab('jobs');
      if (state.mode === 'exams') setTab('exams');
      if (state.mode === 'personal') {
        if (!C.loggedIn) { toast('Đăng nhập và đặt mục tiêu để dùng chế độ này. <a href="' + C.login + '">Đăng nhập</a>', true); return; }
        var s = $('#ci-f-sort'); if (s) s.value = '';
        var fam = $('#ci-goal-family'), prov = $('#ci-goal-province');
        if (fam && fam.value) { $('#ci-f-family').value = fam.value; $('#ci-q-family').value = fam.value; }
        if (prov && prov.value) { $('#ci-f-province').value = prov.value; $('#ci-q-province').value = prov.value; }
        if (!(fam && fam.value)) { openFilters(true); toast('Chọn nhóm vị trí trong “Mục tiêu dự tuyển” rồi lưu để cá nhân hóa kết quả.'); }
        refreshAll();
      }
    });
  });

  /* ---------- Bộ lọc nâng cao ---------- */
  var tog = $('#ci-toggle-filters');
  function openFilters(open) {
    if (!form) return;
    form.classList.toggle('is-open', open);
    if (tog) { tog.setAttribute('aria-expanded', open ? 'true' : 'false'); tog.firstChild.textContent = open ? '× Đóng bộ lọc ' : '☷ Bộ lọc nâng cao '; }
  }
  if (tog) tog.addEventListener('click', function () { openFilters(!form.classList.contains('is-open')); });
  if (form) {
    form.addEventListener('change', function (e) {
      if (e.target.closest('#ci-goal-form')) return;
      var sync = { 'ci-f-family': 'ci-q-family', 'ci-f-province': 'ci-q-province' }[e.target.id];
      if (sync && $('#' + sync)) $('#' + sync).value = e.target.value;
      refreshAll();
    });
    form.addEventListener('submit', function (e) { if (!e.target.closest('#ci-goal-form')) { e.preventDefault(); refreshAll(); } });
  }
  var sf = $('#ci-search-form');
  if (sf) {
    sf.addEventListener('submit', function (e) {
      e.preventDefault();
      if ($('#ci-f-family')) $('#ci-f-family').value = $('#ci-q-family').value;
      if ($('#ci-f-province')) $('#ci-f-province').value = $('#ci-q-province').value;
      state.tin = 0;
      refreshAll();
      if (state.mode === 'all' || state.mode === 'exams') { ['exams', 'resources'].forEach(function (v) { load(v, 1); }); }
      var target = state.mode === 'exams' ? 'exams' : 'jobs';
      setTab(target);
    });
    $$('select', sf).forEach(function (s) { s.addEventListener('change', function () { var t = $('#' + s.getAttribute('data-sync')); if (t) t.value = s.value; }); });
  }
  /* 18/10/2026: 2 nút nổi bật "Tuyển công chức" / "Tuyển viên chức" = bật đúng 1 loại trong bộ lọc */
  function syncKindChips() {
    var kinds = form ? $$('[name="loai[]"]:checked', form).map(function (x) { return x.value; }) : [];
    $$('[data-ci-kind]').forEach(function (x) {
      var act = kinds.length === 1 && kinds[0] === x.getAttribute('data-ci-kind');
      x.classList.toggle('is-on', act); x.setAttribute('aria-pressed', act ? 'true' : 'false');
    });
  }
  $$('[data-ci-kind]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (!form) return;
      e.preventDefault();
      var k = a.getAttribute('data-ci-kind'), on = a.classList.contains('is-on');
      $$('[name="loai[]"]', form).forEach(function (b) { b.checked = !on && b.value === k; });
      state.tin = 0;
      syncKindChips();
      refreshAll();
      setTab('jobs');
      var j = $('#ci-tab-jobs'); if (j) j.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
  if (form) form.addEventListener('change', syncKindChips);
  ['#ci-e-type', '#ci-e-done', '#ci-e-sort'].forEach(function (id) { var el = $(id); if (el) el.addEventListener('change', function () { load('exams', 1); }); });

  /* ---------- Tin đang chọn -> đề & tài liệu ---------- */
  function showRelated(on) {
    var rel = $('#ci-related'), jobs = $('#ci-jobs');
    if (!rel || !jobs) return;
    rel.hidden = !on; jobs.hidden = !!on;
    if (!on) { state.tin = 0; $$('.ci-job.is-selected').forEach(function (x) { x.classList.remove('is-selected'); }); }
  }
  function selectJob(id) {
    state.tin = parseInt(id, 10) || 0;
    var rel = $('#ci-related');
    rel.innerHTML = '<div class="ci-loading">Đang ghép nối đề thi và văn bản…</div>';
    showRelated(true);
    state.tin = parseInt(id, 10) || 0;
    load('related', 1, { id: id }).then(function () {
      var back = document.createElement('button');
      back.type = 'button'; back.className = 'ci-btn'; back.setAttribute('data-ci', 'back-jobs'); back.textContent = '← Quay lại danh sách tin';
      rel.insertBefore(back, rel.firstChild);
      var actions = document.createElement('div'); actions.className = 'ci-actions'; actions.style.margin = '10px 0';
      actions.innerHTML = '<a class="ci-btn" href="#ci-tab-exams" data-ci-tab="exams">Mọi đề liên quan tin này</a><a class="ci-btn" href="#ci-tab-resources" data-ci-tab="resources">Mọi văn bản liên quan</a>' + (C.loggedIn ? '<button type="button" class="ci-btn is-teal" data-ci="goal-rec" data-id="' + id + '">◎ Đặt làm mục tiêu &amp; tạo lộ trình</button>' : '');
      rel.insertBefore(actions, back.nextSibling);
      ['exams', 'resources'].forEach(function (v) { var b = $('#ci-' + v); if (b) b.setAttribute('data-loaded', '0'); });
      rel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  /* ---------- Thao tác ghi ---------- */
  function act(fields) {
    var body = new URLSearchParams();
    body.set('action', 'cvc_ci_act'); body.set('nonce', C.nonce);
    Object.keys(fields).forEach(function (k) { body.set(k, fields[k]); });
    return fetch(C.ajax, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  function fail(res, fb) {
    if (res && res.data && res.data.login) { toast('Phiên đăng nhập đã hết hạn. <a href="' + C.login + '">Đăng nhập lại</a>', true); return; }
    toast((res && res.data && res.data.message) || fb || 'Có lỗi xảy ra, vui lòng thử lại.');
  }
  function busy(b, on) { if (!b) return; b.disabled = !!on; b.classList.toggle('is-busy', !!on); }

  document.addEventListener('click', function (e) {
    var pg = e.target.closest('[data-ci-page]');
    if (pg) {
      e.preventDefault();
      var view = pg.getAttribute('data-view');
      load(view, parseInt(pg.getAttribute('data-ci-page'), 10)).then(function () { var b = $('#ci-' + view); if (b) b.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
      return;
    }
    var b = e.target.closest('[data-ci]');
    if (!b) return;
    var a = b.getAttribute('data-ci');
    if (a === 'reset') {
      e.preventDefault();
      if (form) { form.reset(); $$('select', form).forEach(function (s) { if (!s.closest('#ci-goal-form')) s.value = ''; }); $$('input[type=checkbox]', form).forEach(function (c) { if (!c.closest('#ci-goal-form')) c.checked = false; }); }
      ['#ci-q', '#ci-q-family', '#ci-q-province'].forEach(function (id) { var el = $(id); if (el) el.value = ''; });
      state.tin = 0; refreshAll(); toast('Đã xóa các bộ lọc.');
    } else if (a === 'retry') {
      load(b.getAttribute('data-view'), 1);
    } else if (a === 'related') {
      $$('.ci-job.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
      var card = b.closest('.ci-job'); if (card) card.classList.add('is-selected');
      selectJob(b.getAttribute('data-id'));
    } else if (a === 'back-jobs') {
      showRelated(false);
      ['exams', 'resources'].forEach(function (v) { var bx = $('#ci-' + v); if (bx) bx.setAttribute('data-loaded', '0'); });
      $('#ci-jobs').scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else if (a === 'clear-job') {
      state.tin = 0; showRelated(false); load(state.tab, 1);
    } else if (a === 'save') {
      var saved = b.getAttribute('aria-pressed') !== 'true';
      busy(b, true);
      act({ act: 'save', type: b.getAttribute('data-type'), id: b.getAttribute('data-id'), saved: saved ? 1 : 0 }).then(function (res) {
        busy(b, false);
        if (!res || !res.success) { fail(res); return; }
        $$('[data-ci="save"][data-type="' + b.getAttribute('data-type') + '"][data-id="' + b.getAttribute('data-id') + '"]').forEach(function (x) {
          x.setAttribute('aria-pressed', saved ? 'true' : 'false'); x.classList.toggle('is-teal', saved);
          x.textContent = saved ? '♥ Đã lưu' : (b.getAttribute('data-type') === 'exam' ? '♡ Lưu đề' : '♡ Lưu cơ hội');
        });
        if (!saved) $$('[data-ci="reminder"][data-id="' + b.getAttribute('data-id') + '"]').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); x.classList.remove('is-teal'); x.textContent = '◷ Nhắc hạn'; });
        toast(res.data.message); load('personal');
      }).catch(function () { busy(b, false); toast('Lỗi kết nối.'); });
    } else if (a === 'reminder') {
      var en = b.getAttribute('aria-pressed') !== 'true';
      busy(b, true);
      act({ act: 'reminder', id: b.getAttribute('data-id'), enabled: en ? 1 : 0 }).then(function (res) {
        busy(b, false);
        if (!res || !res.success) { fail(res); return; }
        $$('[data-ci="reminder"][data-id="' + b.getAttribute('data-id') + '"]').forEach(function (x) { x.setAttribute('aria-pressed', en ? 'true' : 'false'); x.classList.toggle('is-teal', en); x.textContent = en ? '◷ Đang nhắc hạn' : '◷ Nhắc hạn'; });
        if (en) $$('[data-ci="save"][data-type="recruitment"][data-id="' + b.getAttribute('data-id') + '"]').forEach(function (x) { x.setAttribute('aria-pressed', 'true'); x.classList.add('is-teal'); x.textContent = '♥ Đã lưu'; });
        toast(res.data.message + (res.data.data && res.data.data.exam_reminders_on === false ? ' Lưu ý: bạn đang tắt “Nhắc hạn nộp hồ sơ” trong tùy chọn.' : ''));
        load('personal');
      }).catch(function () { busy(b, false); toast('Lỗi kết nối.'); });
    } else if (a === 'path-generate') {
      busy(b, true); toast('Đang dựng lộ trình từ mục tiêu và kết quả làm bài…');
      act({ act: 'path' }).then(function (res) {
        busy(b, false);
        if (!res || !res.success) { fail(res); return; }
        toast(res.data.message); load('path'); load('personal');
      }).catch(function () { busy(b, false); toast('Lỗi kết nối.'); });
    } else if (a === 'practice') {
      busy(b, true); toast('Đang chọn câu hỏi từ ngân hàng đã duyệt…');
      act({ act: 'practice', kind: b.getAttribute('data-kind') }).then(function (res) {
        if (res && res.success && res.data.url) { location.href = res.data.url; return; }
        busy(b, false); fail(res, 'Chưa tạo được bài luyện.');
      }).catch(function () { busy(b, false); toast('Lỗi kết nối.'); });
    } else if (a === 'goal-rec') {
      // Chi gui tin muc tieu - cac truong khac cua muc tieu giu nguyen (backend chi cap nhat truong duoc gui).
      var fd = { act: 'goal', recruitment_id: b.getAttribute('data-id') };
      busy(b, true);
      act(fd).then(function (res) {
        if (!res || !res.success) { busy(b, false); fail(res); return; }
        return act({ act: 'path' }).then(function (r2) {
          busy(b, false);
          toast(r2 && r2.success ? 'Đã đặt tin làm mục tiêu. ' + r2.data.message : 'Đã đặt mục tiêu.');
          if (!$('#ci-tab-path')) { setTimeout(function () { location.href = C.base + '?tab=path'; }, 900); return; }
          load('path'); load('personal'); setTab('path');
        });
      }).catch(function () { busy(b, false); toast('Lỗi kết nối.'); });
    } else if (a === 'goal-clear-rec') {
      var h = b.closest('form').querySelector('[name=recruitment_id]'); if (h) h.value = '';
      b.closest('p').remove();
      toast('Bấm “Lưu mục tiêu” để bỏ tin mục tiêu.');
    }
  });

  /* Đánh dấu bước lộ trình */
  document.addEventListener('change', function (e) {
    var cb = e.target.closest('[data-ci="item"]');
    if (cb) {
      cb.disabled = true;
      act({ act: 'item', id: cb.getAttribute('data-id'), status: cb.checked ? 'completed' : 'pending' }).then(function (res) {
        cb.disabled = false;
        if (!res || !res.success) { cb.checked = !cb.checked; fail(res); return; }
        load('path'); load('personal');
      }).catch(function () { cb.disabled = false; cb.checked = !cb.checked; toast('Lỗi kết nối.'); });
      return;
    }
    var pf = e.target.closest('#ci-prefs');
    if (pf) {
      var d = { act: 'prefs', channel: pf.querySelector('[name=channel]').value };
      ['exam_reminders', 'learning_reminders', 'recruitment_alerts'].forEach(function (k) { d[k] = pf.querySelector('[name=' + k + ']').checked ? 1 : 0; });
      act(d).then(function (res) { if (!res || !res.success) { fail(res); return; } toast(res.data.message); }).catch(function () { toast('Lỗi kết nối.'); });
    }
  });

  /* Lưu mục tiêu */
  document.addEventListener('submit', function (e) {
    var gf = e.target.closest('#ci-goal-form');
    if (!gf) return;
    e.preventDefault();
    var fd = Object.fromEntries(new FormData(gf).entries()); fd.act = 'goal';
    var btn = gf.querySelector('[type=submit]'); busy(btn, true);
    act(fd).then(function (res) {
      busy(btn, false);
      if (!res || !res.success) { fail(res); return; }
      toast(res.data.message + ' Kết quả được ưu tiên theo mục tiêu; tạo lộ trình ở tab “Lộ trình cá nhân”.');
      var s = $('#ci-f-sort'); if (s) s.value = '';
      refreshAll(); load('personal');
    }).catch(function () { busy(btn, false); toast('Lỗi kết nối.'); });
  });

  // Mở tab theo hash cũ (#de-thi...) hoặc ?tab=.
  var hashTab = { '#de-thi': 'exams', '#tai-lieu': 'resources', '#lo-trinh': 'path', '#ghep-noi': 'matching' }[location.hash];
  if (hashTab) setTab(hashTab, false);
  countFilters(filters());
})();
