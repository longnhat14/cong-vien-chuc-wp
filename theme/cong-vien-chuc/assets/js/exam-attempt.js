/**
 * Làm bài thi (Phase 10) - timer đếm ngược (chỉ hiển thị, server mới là
 * nơi thực thi hết giờ thật qua isExpired() ở mỗi lần gọi answer()/
 * submit()/show() - Phần XVIII "server-authoritative"), autosave mỗi khi
 * chọn đáp án, chống nộp bài trùng (khoá nút ngay khi bấm + backend tự
 * trả 422 nếu đã nộp).
 */
(function () {
	'use strict';

	if (typeof window.cvcExamAttempt === 'undefined' || !window.cvcExamAttempt.inProgress) {
		return;
	}

	var cfg = window.cvcExamAttempt;
	var form = document.getElementById('cvc-exam-form');
	var autosaveEl = document.getElementById('cvc-exam-autosave');
	var answeredCountEl = document.getElementById('cvc-exam-answered-count');
	var submitBtn = document.getElementById('cvc-exam-submit-btn');
	var timerValueEl = document.getElementById('cvc-exam-timer-value');

	function post(action, extraParams) {
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', cfg.nonce);
		body.set('attempt_id', String(cfg.attemptId));
		Object.keys(extraParams || {}).forEach(function (key) {
			var value = extraParams[key];
			if (Array.isArray(value)) {
				value.forEach(function (v) {
					body.append(key + '[]', v);
				});
			} else if (value !== null && value !== undefined) {
				body.set(key, value);
			}
		});

		return fetch(cfg.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
		}).then(function (res) {
			return res.json();
		});
	}

	function setAutosaveState(state) {
		if (!autosaveEl) {
			return;
		}
		var labels = { saving: 'Đang lưu…', saved: 'Đã lưu', error: 'Không thể lưu, thử lại' };
		autosaveEl.textContent = labels[state] || '';
	}

	function updateAnsweredCount(progress) {
		if (answeredCountEl && progress && typeof progress.answered !== 'undefined') {
			answeredCountEl.textContent = String(progress.answered);
		}
	}

	function markQuestionAnswered(questionId, answered) {
		var navItem = form ? document.querySelector('.cvc-exam-question-nav__item[data-question-id="' + questionId + '"]') : null;
		if (navItem) {
			navItem.classList.toggle('is-answered', !!answered);
		}
	}

	if (form) {
		form.addEventListener('change', function (event) {
			var target = event.target;
			var fieldset = target.closest('.cvc-exam-question');
			if (!fieldset) {
				return;
			}

			var questionId = fieldset.getAttribute('data-question-id');
			var isMulti = fieldset.getAttribute('data-multi') === '1';
			var payload = { question_id: questionId };

			if (isMulti) {
				var checked = fieldset.querySelectorAll('input[type="checkbox"]:checked');
				payload.option_ids = Array.prototype.map.call(checked, function (el) {
					return el.value;
				});
				if (payload.option_ids.length === 0) {
					payload.option_ids = [''];
				}
			} else {
				var selected = fieldset.querySelector('input[type="radio"]:checked');
				payload.question_option_id = selected ? selected.value : '';
			}

			setAutosaveState('saving');

			post('cvc_exam_answer', payload)
				.then(function (json) {
					if (json && json.success) {
						setAutosaveState('saved');
						updateAnsweredCount(json.data ? json.data.progress : null);
						markQuestionAnswered(questionId, true);
					} else {
						setAutosaveState('error');
						if (json && json.data && json.data.message && /Đã hết giờ/.test(json.data.message)) {
							window.location.reload();
						}
					}
				})
				.catch(function () {
					setAutosaveState('error');
				});
		});
	}

	function doSubmit() {
		submitBtn.disabled = true;
		submitBtn.textContent = 'Đang nộp bài…';

		post('cvc_exam_submit', {})
			.then(function () {
				window.location.reload();
			})
			.catch(function () {
				window.location.reload();
			});
	}

	if (submitBtn) {
		submitBtn.addEventListener('click', function () {
			if (submitBtn.disabled) {
				return;
			}
			if (!window.confirm('Bạn có chắc chắn muốn nộp bài? Sau khi nộp sẽ không thể chỉnh sửa đáp án.')) {
				return;
			}
			doSubmit();
		});
	}

	if (cfg.remainingSeconds !== null && typeof cfg.remainingSeconds !== 'undefined' && timerValueEl) {
		var remaining = parseInt(cfg.remainingSeconds, 10);

		function render() {
			var m = Math.max(0, Math.floor(remaining / 60));
			var s = Math.max(0, remaining % 60);
			timerValueEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
		}

		render();

		var interval = setInterval(function () {
			remaining -= 1;
			render();
			if (remaining <= 0) {
				clearInterval(interval);
				if (submitBtn && !submitBtn.disabled) {
					doSubmit();
				}
			}
		}, 1000);
	}
})();
