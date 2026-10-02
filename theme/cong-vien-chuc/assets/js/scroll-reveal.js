/**
 * Hiệu ứng xuất hiện khi cuộn trang (scroll reveal) - áp dụng tự động
 * cho các khối section/card nằm dưới màn hình đầu tiên, trên toàn bộ
 * trang chủ và các trang con (bài viết dùng chung .cvc-section/.cvc-card).
 *
 * An toàn: chỉ phần tử NẰM DƯỚI khung nhìn ban đầu mới được gắn class
 * .cvc-reveal (ẩn + dịch nhẹ), nên nếu JS lỗi hoặc bị chặn, toàn bộ nội
 * dung vẫn hiển thị đầy đủ bình thường - không có rủi ro "mất nội dung".
 * Tôn trọng prefers-reduced-motion và tự bỏ qua nếu trình duyệt không
 * hỗ trợ IntersectionObserver.
 */
(function () {
	'use strict';

	if (!('IntersectionObserver' in window)) {
		return;
	}

	var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (prefersReduced) {
		return;
	}

	function init() {
		var selectors = [
			'main > section',
			'.cvc-section',
			'.cvc-card',
			'.cvc-roadmap-card'
		];

		var candidates = document.querySelectorAll(selectors.join(','));
		if (!candidates.length) {
			return;
		}

		var viewportH = window.innerHeight || document.documentElement.clientHeight;

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('cvc-reveal--visible');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

		var staggerIndex = 0;
		candidates.forEach(function (el) {
			var rect = el.getBoundingClientRect();
			// Bỏ qua phần tử đã nằm trong (hoặc gần) khung nhìn đầu tiên,
			// tránh hiệu ứng "chớp ẩn" nội dung ngay phía trên màn hình gập.
			if (rect.top < viewportH * 0.92) {
				return;
			}
			el.classList.add('cvc-reveal');
			el.style.transitionDelay = (staggerIndex % 4) * 0.08 + 's';
			staggerIndex++;
			observer.observe(el);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
