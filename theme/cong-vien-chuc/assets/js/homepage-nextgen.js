/**
 * Homepage Next-Gen (Phase 11) - roadmap tabs, mini quiz, tính lương,
 * modal văn bản. Không phụ thuộc thư viện; mọi dữ liệu lấy từ API thật
 * (qua admin-ajax) hoặc từ tham số có nguồn trong window.cvcNextGen.
 */
(function () {
	'use strict';

	var cfg = window.cvcNextGen || {};

	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}

	function money(v) {
		return Math.round(v).toLocaleString('vi-VN') + 'đ';
	}

	/* ---------- Career roadmap tabs ---------- */
	function initRoadmap() {
		var root = document.querySelector('[data-cvc-roadmap]');
		if (!root) return;
		var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));

		function select(tab) {
			tabs.forEach(function (t) {
				var on = t === tab;
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.tabIndex = on ? 0 : -1;
				var panel = document.getElementById(t.getAttribute('aria-controls'));
				if (panel) panel.hidden = !on;
			});
		}

		tabs.forEach(function (tab, i) {
			tab.addEventListener('click', function () { select(tab); });
			tab.addEventListener('keydown', function (e) {
				var next = null;
				if (e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
				if (e.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
				if (next) { e.preventDefault(); next.focus(); select(next); }
			});
		});
	}

	/* ---------- Mini quiz ---------- */
	function initMiniQuiz() {
		var root = document.querySelector('[data-cvc-mini-quiz]');
		if (!root || !cfg.ajaxUrl) return;

		var body = root.querySelector('[data-quiz-body]');
		var progress = root.querySelector('[data-quiz-progress]');
		var scoreEl = root.querySelector('[data-quiz-score]');
		var nextBtn = root.querySelector('[data-quiz-next]');
		var reloadBtn = root.querySelector('[data-quiz-reload]');
		var state = { token: '', questions: [], index: 0, correct: 0, answered: false };

		function post(action, data) {
			var fd = new FormData();
			fd.append('action', action);
			fd.append('nonce', cfg.nonce);
			Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
			return fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
		}

		function load() {
			progress.textContent = 'Đang tải câu hỏi…';
			scoreEl.textContent = '';
			body.innerHTML = '';
			nextBtn.classList.add('hidden');
			reloadBtn.classList.add('hidden');
			post('cvc_mini_quiz_load').then(function (res) {
				if (!res.success || !res.data || !res.data.questions || !res.data.questions.length) {
					progress.textContent = 'Chưa tải được câu hỏi';
					body.innerHTML = '<p class="text-sm text-slate-400">Hãy thử lại sau hoặc vào trang đề thi.</p>';
					reloadBtn.classList.remove('hidden');
					return;
				}
				state = { token: res.data.quiz_token, questions: res.data.questions, index: 0, correct: 0, answered: false };
				render();
			}).catch(function () {
				progress.textContent = 'Lỗi kết nối';
				reloadBtn.classList.remove('hidden');
			});
		}

		function render() {
			var q = state.questions[state.index];
			state.answered = false;
			progress.textContent = 'Câu ' + (state.index + 1) + '/' + state.questions.length + (q.exam_subject ? ' · ' + q.exam_subject : '');
			scoreEl.textContent = 'Đúng ' + state.correct + '/' + state.index;
			nextBtn.classList.add('hidden');
			body.innerHTML =
				'<p class="text-base font-bold text-white leading-relaxed">' + esc(q.question_text) + '</p>' +
				'<div class="grid gap-2" role="group" aria-label="Các phương án">' +
				q.options.map(function (o) {
					return '<button type="button" class="cvc-quiz-opt text-left px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 hover:border-amber-400 text-sm text-slate-200" data-option="' + o.id + '"><strong class="text-amber-300 mr-2">' + esc(o.key) + '.</strong>' + esc(o.option_text) + '</button>';
				}).join('') +
				'</div><div data-quiz-feedback></div>';

			Array.prototype.forEach.call(body.querySelectorAll('[data-option]'), function (btn) {
				btn.addEventListener('click', function () { answer(q, btn); });
			});
		}

		function answer(q, btn) {
			if (state.answered) return;
			state.answered = true;
			var buttons = body.querySelectorAll('[data-option]');
			Array.prototype.forEach.call(buttons, function (b) { b.disabled = true; });
			post('cvc_mini_quiz_check', { quiz_token: state.token, question_id: q.id, option_id: btn.getAttribute('data-option') })
				.then(function (res) {
					var fb = body.querySelector('[data-quiz-feedback]');
					if (!res.success) {
						fb.innerHTML = '<p class="text-rose-300 text-sm mt-2">' + esc((res.data && res.data.message) || 'Không chấm được câu này.') + '</p>';
						reloadBtn.classList.remove('hidden');
						return;
					}
					var d = res.data;
					if (d.is_correct) state.correct++;
					Array.prototype.forEach.call(buttons, function (b) {
						var id = parseInt(b.getAttribute('data-option'), 10);
						if (id === d.correct_option_id) b.classList.add('is-correct');
						else if (b === btn) b.classList.add('is-wrong');
					});
					var links = [];
					if (d.legal_reference && d.legal_reference.slug) links.push('<a class="text-amber-300 hover:underline" href="' + cfg.homeUrl + 'van-ban-phap-luat/' + encodeURIComponent(d.legal_reference.slug) + '/">Căn cứ: ' + esc((d.legal_reference.document_number ? d.legal_reference.document_number + ' - ' : '') + d.legal_reference.title) + '</a>');
					if (d.topic && d.topic.slug) links.push('<a class="text-cyan-300 hover:underline" href="' + cfg.homeUrl + 'chu-de/' + encodeURIComponent(d.topic.slug) + '/">Ôn chủ đề: ' + esc(d.topic.name) + '</a>');
					if (d.exam && d.exam.slug) links.push('<a class="text-cyan-300 hover:underline" href="' + cfg.homeUrl + 'thi-trac-nghiem/' + encodeURIComponent(d.exam.slug) + '/">Làm cả đề: ' + esc(d.exam.title) + '</a>');
					fb.innerHTML = '<div class="mt-3 p-4 rounded-xl ' + (d.is_correct ? 'bg-emerald-500/10 border border-emerald-500/40' : 'bg-rose-500/10 border border-rose-500/40') + ' text-sm space-y-2">' +
						'<p class="font-black ' + (d.is_correct ? 'text-emerald-300' : 'text-rose-300') + '">' + (d.is_correct ? '✓ Chính xác!' : '✗ Chưa đúng - đáp án đúng là ' + esc(d.correct_option_key)) + '</p>' +
						(d.explanation ? '<p class="text-slate-300">' + esc(d.explanation) + '</p>' : '') +
						(links.length ? '<p class="flex flex-wrap gap-3 text-xs">' + links.join('') + '</p>' : '') + '</div>';
					scoreEl.textContent = 'Đúng ' + state.correct + '/' + (state.index + 1);
					if (state.index < state.questions.length - 1) {
						nextBtn.classList.remove('hidden');
					} else {
						progress.textContent = 'Hoàn thành: đúng ' + state.correct + '/' + state.questions.length + ' câu';
						reloadBtn.classList.remove('hidden');
					}
				});
		}

		nextBtn.addEventListener('click', function () { state.index++; render(); });
		reloadBtn.addEventListener('click', load);

		// Chỉ tải khi khối sắp vào màn hình - không tốn request nếu không cuộn tới.
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				if (entries.some(function (e) { return e.isIntersecting; })) { io.disconnect(); load(); }
			}, { rootMargin: '300px' });
			io.observe(root);
		} else {
			load();
		}
	}

	/* ---------- Tính lương & lương hưu ---------- */
	function initSalary() {
		var form = document.querySelector('[data-cvc-salary]');
		if (!form || !cfg.salary) return;
		var p = cfg.salary;
		var num = function (id) { var v = parseFloat(document.getElementById(id).value); return isNaN(v) || v < 0 ? 0 : v; };
		var out = function (key, val) { var el = form.querySelector('[data-out="' + key + '"]'); if (el) el.textContent = val; };

		function calc() {
			var base = p.base_salary;
			var salary = num('sal-coef') * base;
			var position = num('sal-position') * base;
			var tnvk = salary * num('sal-tnvk') / 100;
			var tnn = (salary + position + tnvk) * num('sal-tnn') / 100;
			var region = num('sal-region') * base;
			var resp = num('sal-resp') * base;
			var allowances = position + tnvk + tnn + region + resp;
			var gross = salary + allowances;

			// Căn cứ đóng BHXH khu vực nhà nước: lương ngạch bậc + PC chức vụ + TNVK + thâm niên nghề.
			var insBase = Math.min(salary + position + tnvk + tnn, p.cap_multiplier * base);
			var rate = p.rates.bhxh + p.rates.bhyt + (document.getElementById('sal-type').value === 'vien_chuc' ? p.rates.bhtn : 0);
			var insurance = insBase * rate;

			out('salary', money(salary));
			out('allowances', money(allowances));
			out('gross', money(gross));
			out('insurance', '-' + money(insurance) + ' (' + (rate * 100).toFixed(1).replace('.', ',') + '%)');
			out('net', money(gross - insurance));

			var years = Math.floor(num('sal-years'));
			var female = document.getElementById('sal-gender').value === 'nu';
			var pensionRate = null;
			if (years >= p.pension.min_years) {
				if (female) pensionRate = 0.45 + 0.02 * (years - 15);
				else if (years < 20) pensionRate = 0.40 + 0.01 * (years - 15);
				else pensionRate = 0.45 + 0.02 * (years - 20);
				pensionRate = Math.min(pensionRate, p.pension.max_rate);
			}
			if (pensionRate === null) {
				out('pension', 'Chưa đủ điều kiện');
				out('pension-note', 'Cần tối thiểu ' + p.pension.min_years + ' năm đóng BHXH để hưởng lương hưu hằng tháng.');
			} else {
				out('pension', money(insBase * pensionRate) + '/tháng');
				out('pension-note', 'Tỷ lệ hưởng ' + Math.round(pensionRate * 100) + '% với ' + years + ' năm đóng (' + (female ? 'nữ' : 'nam') + ').');
			}
		}

		form.addEventListener('input', calc);
		form.addEventListener('change', calc);
		calc();
	}

	/* ---------- Modal văn bản ---------- */
	function initLegalModal() {
		var dialog = document.getElementById('cvc-legal-dialog');
		if (!dialog) return;
		Array.prototype.forEach.call(document.querySelectorAll('[data-cvc-legal-modal]'), function (btn) {
			btn.addEventListener('click', function () {
				var url = btn.getAttribute('data-url');
				if (typeof dialog.showModal !== 'function') { window.location.href = url; return; }
				dialog.querySelector('[data-dialog-title]').textContent = btn.getAttribute('data-title');
				dialog.querySelector('[data-dialog-meta]').textContent = btn.getAttribute('data-meta');
				dialog.querySelector('[data-dialog-url]').setAttribute('href', url);
				dialog.showModal();
			});
		});
		dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
	}

	function init() {
		initRoadmap();
		initMiniQuiz();
		initSalary();
		initLegalModal();
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
