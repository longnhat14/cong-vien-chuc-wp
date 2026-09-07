/**
 * User Engagement Tracking client-side (Phase 10) - fire-and-forget, KHÔNG
 * bao giờ chặn UI hay hiện lỗi cho người dùng nếu request thất bại (Phần XI
 * Engagement - "tracking phải fail-silent"). Chỉ gửi khi đã đăng nhập
 * (cvcEngagement.loggedIn) - tránh gọi API vô ích khi biết chắc sẽ bị 401.
 *
 * Cách dùng: các template detail render 1 phần tử với
 * data-cvc-track="recruitment_viewed" data-cvc-target-type="recruitment"
 * data-cvc-target-id="123" - script này tự đọc và gửi 1 lần khi trang tải
 * xong (không lặp lại khi cùng 1 lượt xem trang, tránh spam API).
 */
(function () {
	'use strict';

	if (typeof window.cvcEngagement === 'undefined' || !window.cvcEngagement.loggedIn) {
		return;
	}

	function track(type, targetType, targetId, source) {
		try {
			var body = new URLSearchParams();
			body.set('action', 'cvc_track_event');
			body.set('nonce', window.cvcEngagement.nonce);
			body.set('type', type);
			body.set('target_type', targetType);
			body.set('target_id', String(targetId));
			if (source) {
				body.set('source', source);
			}

			if (navigator.sendBeacon) {
				navigator.sendBeacon(window.cvcEngagement.ajaxUrl, body);
				return;
			}

			fetch(window.cvcEngagement.ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				keepalive: true,
			}).catch(function () {
				/* fail-silent theo yêu cầu - không hiện lỗi cho người dùng */
			});
		} catch (e) {
			/* fail-silent */
		}
	}

	window.cvcTrackEvent = track;

	document.addEventListener('DOMContentLoaded', function () {
		var nodes = document.querySelectorAll('[data-cvc-track]');
		nodes.forEach(function (node) {
			track(
				node.getAttribute('data-cvc-track'),
				node.getAttribute('data-cvc-target-type'),
				node.getAttribute('data-cvc-target-id'),
				node.getAttribute('data-cvc-source') || 'page_view'
			);
		});

		document.querySelectorAll('[data-cvc-click-track]').forEach(function (node) {
			node.addEventListener('click', function () {
				track(
					node.getAttribute('data-cvc-click-track'),
					node.getAttribute('data-cvc-target-type'),
					node.getAttribute('data-cvc-target-id'),
					node.getAttribute('data-cvc-source') || 'click'
				);
			});
		});
	});
})();
