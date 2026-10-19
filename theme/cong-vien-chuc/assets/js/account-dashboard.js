/*
 * Tổng quan tài khoản v1 (19/10/2026): vẽ biểu đồ SVG từ số liệu thật (cột = phút làm bài, đường = điểm
 * trung bình %), đổi khoảng 7 ngày / 30 ngày / 3 tháng qua AJAX cvc_ad_chart. Có tóm tắt dạng chữ.
 */
(function () {
	'use strict';
	var root = document.querySelector('main.ad');
	var D = window.CVC_AD;
	if (!root || !D) { return; }
	var box = root.querySelector('[data-ad-chart]');
	var sr = root.querySelector('[data-ad-chart-sr]');
	var LABELS = { '7d': '7 ngày gần nhất', '30d': '30 ngày gần nhất', '90d': '3 tháng gần nhất' };

	function toast(s) {
		var t = document.getElementById('ad-toast'); if (!t) return;
		t.textContent = s; t.classList.add('is-show'); clearTimeout(toast.id);
		toast.id = setTimeout(function () { t.classList.remove('is-show'); }, 2600);
	}
	function fmtMin(m) { m = Math.round(m || 0); if (m < 60) return m + ' phút'; return Math.floor(m / 60) + ' giờ' + (m % 60 ? ' ' + (m % 60) + ' phút' : ''); }
	function fmtPct(p) { return p === null || p === undefined ? '—' : (Math.round(p * 10) / 10).toLocaleString('vi-VN') + '%'; }
	function trend(cur, prev, unit) {
		if (!prev) return cur > 0 ? 'Mới trong kỳ này' : 'Chưa có hoạt động';
		var d = cur - prev; if (!d) return 'Bằng kỳ trước';
		return (d > 0 ? '+' : '−') + (unit === 'min' ? fmtMin(Math.abs(d)) : Math.abs(d) + ' lượt') + ' so với kỳ trước';
	}
	function niceMax(v) { if (v <= 30) return 30; if (v <= 60) return 60; var s = v <= 240 ? 30 : 60; return Math.ceil(v / s) * s; }
	function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

	function draw(chart) {
		if (!box) return;
		var b = (chart && chart.buckets) || [];
		var any = b.some(function (x) { return x.minutes > 0 || x.exams > 0; });
		if (!b.length || !any) {
			box.innerHTML = '<div class="ad-chartempty">Chưa có lượt thi nào được nộp trong khoảng này.<br>Làm một đề hoặc một phiên ôn tập để bắt đầu theo dõi nhịp học.</div>';
			if (sr) sr.textContent = 'Không có dữ liệu làm bài trong khoảng thời gian đã chọn.';
			return;
		}
		var W = 650, H = 230, L = 46, R = 40, T = 18, B = 34, iw = W - L - R, ih = H - T - B;
		var maxM = niceMax(Math.max.apply(null, b.map(function (x) { return x.minutes; })));
		var n = b.length, slot = iw / n, bw = Math.max(4, Math.min(34, slot * 0.56));
		var y = function (m) { return T + ih - (m / maxM) * ih; };
		var yp = function (p) { return T + ih - (p / 100) * ih; };
		var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-hidden="true">';
		for (var g = 0; g <= 4; g++) {
			var gy = T + ih * g / 4, mv = Math.round(maxM * (1 - g / 4));
			svg += '<line class="gl" x1="' + L + '" y1="' + gy + '" x2="' + (W - R) + '" y2="' + gy + '"/>';
			svg += '<text x="' + (L - 6) + '" y="' + (gy + 4) + '" text-anchor="end">' + (mv >= 60 && mv % 60 === 0 ? (mv / 60) + 'h' : mv + "'") + '</text>';
			svg += '<text x="' + (W - R + 6) + '" y="' + (gy + 4) + '">' + Math.round(100 * (1 - g / 4)) + '%</text>';
		}
		var pts = [];
		var every = n > 14 ? Math.ceil(n / 8) : 1;
		b.forEach(function (x, i) {
			var cx = L + slot * i + slot / 2;
			var h = Math.max(x.minutes > 0 ? 2 : 0, T + ih - y(x.minutes));
			svg += '<rect class="bar" x="' + (cx - bw / 2) + '" y="' + (T + ih - h) + '" width="' + bw + '" height="' + h + '" rx="3"><title>' + esc(x.title + ': ' + fmtMin(x.minutes) + ', ' + x.exams + ' lượt' + (x.avg_pct !== null ? ', điểm TB ' + fmtPct(x.avg_pct) : '')) + '</title></rect>';
			if (i % every === 0 || i === n - 1) svg += '<text x="' + cx + '" y="' + (H - 12) + '" text-anchor="middle">' + esc(x.label) + '</text>';
			if (x.avg_pct !== null && x.avg_pct !== undefined) pts.push([cx, yp(x.avg_pct)]);
		});
		if (pts.length > 1) svg += '<path class="ln" d="' + pts.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ') + '"/>';
		pts.forEach(function (p) { svg += '<circle class="pt" cx="' + p[0].toFixed(1) + '" cy="' + p[1].toFixed(1) + '" r="3.5"/>'; });
		svg += '</svg>';
		box.innerHTML = svg;
		if (sr) {
			var best = b.reduce(function (a, x) { return x.minutes > a.minutes ? x : a; }, b[0]);
			sr.textContent = 'Tổng ' + fmtMin(chart.total_minutes) + ' làm bài, ' + chart.exams + ' lượt đã nộp, điểm trung bình ' + fmtPct(chart.avg_pct) + '. Nhiều nhất vào ' + best.title + ' với ' + fmtMin(best.minutes) + '.';
		}
	}

	function set(sel, v) { var el = root.querySelector(sel); if (el) el.textContent = v; }
	function update(d) {
		draw(d.chart);
		set('[data-ad-c-time]', fmtMin(d.chart.total_minutes));
		set('[data-ad-c-score]', fmtPct(d.chart.avg_pct));
		set('[data-ad-c-days]', String(d.chart.active_days || 0));
		var k = d.kpis || {};
		if (k.study) { set('[data-ad-kpi-study]', fmtMin(k.study.minutes)); set('[data-ad-trend-study]', trend(k.study.minutes, k.study.prev_minutes, 'min')); }
		if (k.exams) {
			set('[data-ad-kpi-exams]', String(k.exams.count));
			set('[data-ad-trend-exams]', trend(k.exams.count, k.exams.prev_count));
			set('[data-ad-kpi-avg]', k.exams.avg_pct !== null && k.exams.avg_pct !== undefined ? 'Điểm trung bình ' + fmtPct(k.exams.avg_pct) + ' trong kỳ' : 'Chưa có lượt nộp trong kỳ');
		}
		set('[data-ad-period-label]', (LABELS[d.period] || '') + ' · tính từ các lượt đã nộp');
	}

	draw(D.chart);
	var sel = root.querySelector('[data-ad-period]');
	if (sel) {
		sel.addEventListener('change', function () {
			var p = sel.value;
			if (box) box.classList.add('is-loading');
			fetch(D.ajax + '?action=cvc_ad_chart&nonce=' + encodeURIComponent(D.nonce) + '&period=' + encodeURIComponent(p), { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (box) box.classList.remove('is-loading');
					if (!res || !res.success) { toast((res && res.data && res.data.message) || 'Chưa tải được số liệu, vui lòng thử lại.'); return; }
					update(res.data);
				})
				.catch(function () { if (box) box.classList.remove('is-loading'); toast('Mất kết nối - chưa đổi được khoảng thời gian.'); });
		});
	}
})();
