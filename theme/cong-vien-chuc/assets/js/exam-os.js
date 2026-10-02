/**
 * CÔNG VIÊN CHỨC — EXAM OS X (Smart Exam Intelligence Platform)
 * Client-side Operating System Engine with Backend API Sync & Interactive Tools
 */

window.ExamOS = (function() {
  'use strict';

  let config = {
    examId: 'default_exam',
    attemptId: 0,
    totalQuestions: 60,
    storageKey: 'cvc_exam_os_attempt_v6',
    fontSizeLevel: 1 // 1: normal, 2: large, 3: extra large
  };

  let state = {
    answers: {},           // { qId: 'A' }
    struckOut: {},         // { qId: ['B', 'C'] }
    confidence: {},        // { qId: 'low' | 'med' | 'high' }
    flagged: {},           // { qId: true }
    notes: {},             // { qId: 'User note' }
    mode: 'practice',      // 'learn' | 'practice' | 'real' | 'mock'
    activeFilter: 'all',   // 'all' | 'unanswered' | 'flagged' | 'uncertain'
    lastSaved: null
  };

  function init(options) {
    if (options) {
      config = Object.assign({}, config, options);
    }
    state.storageKey = 'cvc_exam_os_attempt_' + config.examId;
    
    restoreStateFromStorage();

    // Server là nguồn sự thật (đồng bộ nhiều thiết bị) - ghi đè
    // localStorage nếu có dữ liệu thật từ attempt đang resume.
    if (window.cvc_vars && window.cvc_vars.existing_state) {
      Object.keys(window.cvc_vars.existing_state).forEach(function (qId) {
        let s = window.cvc_vars.existing_state[qId];
        if (s.selected) state.answers[qId] = s.selected;
        if (s.is_flagged) state.flagged[qId] = true;
        if (s.confidence_level) state.confidence[qId] = s.confidence_level;
      });
    }

    setupVisibilityMonitor();
    updateMetricsUI();
    filterNavigator(state.activeFilter || 'all');

    // Check if there is an unfinished attempt
    if (Object.keys(state.answers).length > 0) {
      showResumePrompt();
    }
  }

  /* --- STRIKE OUT / LOẠI TRỪ ĐÁP ÁN --- */
  function toggleStrikeout(qId, optionKey, evt) {
    if (evt) evt.stopPropagation();

    if (!state.struckOut[qId]) {
      state.struckOut[qId] = [];
    }

    let list = state.struckOut[qId];
    let idx = list.indexOf(optionKey);
    if (idx > -1) {
      list.splice(idx, 1);
    } else {
      list.push(optionKey);
    }

    let tile = document.getElementById(`tile-${qId}-${optionKey}`);
    if (tile) {
      tile.classList.toggle('struck-out', list.indexOf(optionKey) > -1);
    }

    persistState();
  }

  /* --- SELECT ANSWER TILE & BACKEND AJAX SYNC --- */
  function selectAnswerTile(qId, optionKey) {
    let container = document.getElementById(`options-container-${qId}`);
    if (container) {
      container.querySelectorAll('.answer-tile').forEach(tile => {
        tile.classList.remove('selected');
        let radio = tile.querySelector('input[type="radio"]');
        if (radio) radio.checked = false;
      });
    }

    let selectedTile = document.getElementById(`tile-${qId}-${optionKey}`);
    if (selectedTile) {
      selectedTile.classList.add('selected');
      let radio = selectedTile.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    }

    state.answers[qId] = optionKey;

    // Show confidence selector if hidden
    let confBox = document.getElementById(`confidence-box-${qId}`);
    if (confBox) {
      confBox.classList.remove('hidden');
      confBox.classList.add('flex');
    }

    // Update Mode specific reveal
    if (state.mode === 'learn' || state.mode === 'practice') {
      let exp = document.getElementById(`explanation-${qId}`);
      if (exp) exp.classList.remove('hidden');
    }

    updateMetricsUI();
    updateNavigatorNode(qId);
    syncAnswerToBackend(qId, optionKey);
    persistState();
  }

  /* --- BACKEND REST / AJAX SYNC --- */
  function syncAnswerToBackend(qId, optionKey) {
    if (!window.cvc_vars || !window.cvc_vars.ajax_url || !window.cvc_vars.attempt_id) return;

    let formData = new FormData();
    formData.append('action', 'cvc_exam_answer');
    formData.append('nonce', window.cvc_vars.nonce);
    formData.append('attempt_id', window.cvc_vars.attempt_id);
    formData.append('question_id', qId);

    // Backend chấm điểm dựa trên question_option_id (ID thật trong DB),
    // KHÔNG dựa trên ký tự A/B/C/D - trước đây chỉ gửi answer_value là
    // chữ cái nên mọi câu đều bị chấm sai dù chọn đúng. option_ids do
    // PHP localize sẵn (window.cvc_vars.option_ids[qId][optionKey]).
    let optionId = window.cvc_vars.option_ids &&
      window.cvc_vars.option_ids[qId] &&
      window.cvc_vars.option_ids[qId][optionKey];

    if (optionId) {
      formData.append('question_option_id', optionId);
    } else {
      formData.append('answer_value', optionKey);
    }

    let confidence = state.confidence[qId];
    if (confidence) formData.append('confidence_level', confidence);

    fetch(window.cvc_vars.ajax_url, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      let saveEl = document.getElementById('autosave-status-text');
      if (saveEl && data.success) {
        let now = new Date();
        let timeStr = now.getHours().toString().padStart(2, '0') + ':' +
                      now.getMinutes().toString().padStart(2, '0') + ':' +
                      now.getSeconds().toString().padStart(2, '0');
        saveEl.innerText = `✓ Synced backend ${timeStr}`;
      }
    })
    .catch(err => console.warn('Backend sync warning:', err));
  }

  /* --- SYNC CONFIDENCE/FLAG RIÊNG (không đụng question_option_id) --- */
  function syncMeta(qId, meta) {
    if (!window.cvc_vars || window.cvc_vars.is_demo || !window.cvc_vars.attempt_id) return;

    let formData = new FormData();
    formData.append('action', 'cvc_exam_meta');
    formData.append('nonce', window.cvc_vars.nonce);
    formData.append('attempt_id', window.cvc_vars.attempt_id);
    formData.append('question_id', qId);
    if (meta.confidence_level) formData.append('confidence_level', meta.confidence_level);
    if (typeof meta.is_flagged === 'boolean') formData.append('is_flagged', meta.is_flagged ? '1' : '0');

    fetch(window.cvc_vars.ajax_url, { method: 'POST', body: formData }).catch(function (err) {
      console.warn('syncMeta warning:', err);
    });
  }

  /* --- CONFIDENCE LEVEL SELECTOR --- */
  function setConfidenceLevel(qId, level) {
    state.confidence[qId] = level;

    let box = document.getElementById(`confidence-box-${qId}`);
    if (box) {
      box.querySelectorAll('.confidence-pill').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.level === level);
      });
    }

    syncMeta(qId, { confidence_level: level });
    persistState();
  }

  function toggleFlag(qId) {
    state.flagged[qId] = !state.flagged[qId];

    let icon = document.getElementById('flag-icon-' + qId);
    if (icon) {
      icon.className = state.flagged[qId]
        ? 'fa-solid fa-star text-amber-400'
        : 'fa-regular fa-star text-slate-400';
    }

    let countEl = document.getElementById('sidebar-flagged-count');
    if (countEl) {
      let count = Object.values(state.flagged).filter(Boolean).length;
      countEl.innerText = count;
    }

    syncMeta(qId, { is_flagged: state.flagged[qId] });
    persistState();
  }

  /* --- SAVE QUESTION PERSONAL NOTE --- */
  function saveQuestionNote(qId) {
    let input = document.getElementById(`note-input-${qId}`);
    if (input) {
      let val = input.value.trim();
      state.notes[qId] = val;
      persistState();
      alert(`✓ Đã lưu ghi nhớ cá nhân cho câu #${qId}`);
    }
  }

  /* --- FONT SIZE TOGGLE (A+) --- */
  function toggleFontSize() {
    config.fontSizeLevel = (config.fontSizeLevel % 3) + 1;
    let mainWorkspace = document.querySelector('main');
    if (mainWorkspace) {
      mainWorkspace.classList.remove('text-sm', 'text-base', 'text-lg');
      if (config.fontSizeLevel === 1) mainWorkspace.classList.add('text-sm');
      if (config.fontSizeLevel === 2) mainWorkspace.classList.add('text-base');
      if (config.fontSizeLevel === 3) mainWorkspace.classList.add('text-lg');
    }
  }

  /* --- FOCUS MODE TOGGLE --- */
  function toggleFocusMode() {
    let leftCol = document.querySelector('.exam-left-column');
    let rightCol = document.querySelector('.sticky-right-sidebar');
    if (leftCol && rightCol) {
      let isHidden = leftCol.style.display === 'none';
      leftCol.style.display = isHidden ? 'block' : 'none';
      rightCol.style.display = isHidden ? 'block' : 'none';
    }
  }

  /* --- EXAM NAVIGATOR FILTERING --- */
  function filterNavigator(filterType) {
    state.activeFilter = filterType;

    document.querySelectorAll('.nav-filter-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.filter === filterType);
    });

    let totalQ = config.totalQuestions;
    let visibleCount = 0;

    for (let i = 1; i <= totalQ; i++) {
      let qId = i;
      let navBtn = document.getElementById(`nav-btn-${qId}`);
      let card = document.getElementById(`question-card-${qId}`);

      let isAnswered = !!state.answers[qId];
      let isFlagged = !!state.flagged[qId];
      let isUncertain = state.confidence[qId] === 'low';

      let match = true;
      if (filterType === 'unanswered') match = !isAnswered;
      if (filterType === 'flagged') match = isFlagged;
      if (filterType === 'uncertain') match = isUncertain;

      if (navBtn) {
        navBtn.style.display = match ? 'flex' : 'none';
      }
      if (card) {
        card.style.display = match ? 'block' : 'none';
      }

      if (match) visibleCount++;
    }

    let filterCountBadge = document.getElementById('filter-matching-count');
    if (filterCountBadge) {
      filterCountBadge.innerText = `${visibleCount}/${totalQ} câu`;
    }
  }

  /* --- EXAM MODE SELECTOR --- */
  function setExamMode(mode) {
    state.mode = mode;

    document.querySelectorAll('.exam-mode-badge').forEach(badge => {
      badge.classList.toggle('active', badge.dataset.mode === mode);
    });

    let descEl = document.getElementById('exam-mode-description');
    if (descEl) {
      let descs = {
        learn: '🎓 Chế độ HỌC: Hiển thị giải thích & căn cứ pháp lý ngay dưới câu hỏi.',
        practice: '🏋️ Chế độ LUYỆN: Hệ thống tự động lưu tiến độ, xem giải thích chi tiết sau mỗi câu.',
        real: '⏱️ Chế độ THI THẬT: Tính thời gian đếm ngược 60 phút, không hiển thị trước đáp án.',
        mock: '🏛️ Chế độ MÔ PHỎNG: Quy chuẩn 100% thời gian, điểm đạt & giám sát sát hạch Bộ Nội Vụ.'
      };
      descEl.innerText = descs[mode] || '';
    }

    persistState();
  }

  /* --- AUTOSAVE & PERSISTENCE --- */
  function persistState() {
    try {
      state.lastSaved = new Date().toISOString();
      localStorage.setItem(state.storageKey, JSON.stringify(state));

      let saveEl = document.getElementById('autosave-status-text');
      if (saveEl) {
        let now = new Date();
        let timeStr = now.getHours().toString().padStart(2, '0') + ':' +
                      now.getMinutes().toString().padStart(2, '0') + ':' +
                      now.getSeconds().toString().padStart(2, '0');
        saveEl.innerText = `✓ Auto-saved ${timeStr}`;
      }
    } catch (e) {
      console.warn('LocalStorage save failed:', e);
    }
  }

  function restoreStateFromStorage() {
    try {
      let raw = localStorage.getItem(state.storageKey);
      if (raw) {
        let parsed = JSON.parse(raw);
        state = Object.assign({}, state, parsed);
        applyStateToUI();
      }
    } catch (e) {
      console.warn('Failed to restore Exam OS state:', e);
    }
  }

  function applyStateToUI() {
    for (let qId in state.answers) {
      selectAnswerTile(qId, state.answers[qId]);
    }
    for (let qId in state.struckOut) {
      state.struckOut[qId].forEach(opt => toggleStrikeout(qId, opt));
    }
    for (let qId in state.confidence) {
      setConfidenceLevel(qId, state.confidence[qId]);
    }
    for (let qId in state.notes) {
      let input = document.getElementById(`note-input-${qId}`);
      if (input) input.value = state.notes[qId];
    }
    if (state.mode) {
      setExamMode(state.mode);
    }
  }

  function showResumePrompt() {
    let banner = document.getElementById('resume-exam-modal');
    if (banner) {
      banner.classList.remove('hidden');
      banner.classList.add('flex');
    }
  }

  function dismissResumePrompt() {
    let banner = document.getElementById('resume-exam-modal');
    if (banner) {
      banner.classList.add('hidden');
      banner.classList.remove('flex');
    }
  }

  /* --- METRICS & NAVIGATOR UPDATES --- */
  function updateMetricsUI() {
    let answeredCount = Object.keys(state.answers).length;
    let total = config.totalQuestions;
    let pct = Math.round((answeredCount / total) * 100);

    let progressText = document.getElementById('answered-progress-text');
    if (progressText) progressText.innerText = `${answeredCount}/${total}`;

    let progressTextMain = document.getElementById('answered-progress-text-main');
    if (progressTextMain) progressTextMain.innerText = `${answeredCount}/${total} câu`;

    let progressPctMain = document.getElementById('command-progress-pct-main');
    if (progressPctMain) progressPctMain.innerText = `(${pct}%)`;

    let progressBarMain = document.getElementById('command-progress-bar-main');
    if (progressBarMain) progressBarMain.style.width = `${pct}%`;

    let sideAns = document.getElementById('sidebar-answered-count');
    if (sideAns) sideAns.innerText = `${answeredCount} / ${total}`;

    let progressBar = document.getElementById('command-progress-bar');
    if (progressBar) progressBar.style.width = `${pct}%`;

    let pctText = document.getElementById('command-progress-pct');
    if (pctText) pctText.innerText = `${pct}%`;
  }

  function updateNavigatorNode(qId) {
    let btn = document.getElementById(`nav-btn-${qId}`);
    if (btn) {
      btn.classList.add('answered');
    }
  }

  function setupVisibilityMonitor() {
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        let warnEl = document.getElementById('tab-switch-warning');
        if (warnEl) warnEl.classList.remove('hidden');
      }
    });
  }

  return {
    init,
    selectAnswerTile,
    toggleStrikeout,
    setConfidenceLevel,
    toggleFlag,
    saveQuestionNote,
    toggleFontSize,
    toggleFocusMode,
    filterNavigator,
    setExamMode,
    dismissResumePrompt
  };
})();
