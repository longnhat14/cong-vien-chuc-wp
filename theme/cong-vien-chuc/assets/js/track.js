/* Đo hoạt động (28/10/2026): 1 lượt xem / trang + nhịp thời gian khi tab đang mở và có thao tác. Xem inc/track.php. */
(function () {
  'use strict';
  var C = window.CVC_T;
  if (!C || !C.u || !navigator.sendBeacon || /bot|crawl|spider|headless/i.test(navigator.userAgent)) return;
  function ck(n) { var m = document.cookie.match('(?:^|; )' + n + '=([^;]*)'); return m ? decodeURIComponent(m[1]) : ''; }
  function setCk(n, v, days) { document.cookie = n + '=' + encodeURIComponent(v) + '; path=/; max-age=' + (days * 86400) + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : ''); }

  // Nguồn đến lần đầu (gửi kèm khi đăng ký).
  if (!ck('cvc_src')) {
    var src = '';
    try {
      var u = new URLSearchParams(location.search);
      if (u.get('utm_source')) src = [u.get('utm_source'), u.get('utm_medium'), u.get('utm_campaign')].filter(Boolean).join(' / ');
      else if (document.referrer) {
        var h = new URL(document.referrer).hostname.replace(/^www\./, '');
        if (h && h !== location.hostname.replace(/^www\./, '')) src = h + ' / giới thiệu';
      }
    } catch (e) {}
    setCk('cvc_src', JSON.stringify({ s: (src || '(trực tiếp)').slice(0, 110), l: location.pathname.slice(0, 180) }), 90);
  }

  var actor = ck('cvc_ta');
  if (!actor) {
    var v = ck('cvc_vid');
    if (!/^[a-z0-9]{12,32}$/.test(v)) { v = (Date.now().toString(36) + Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2)).replace(/[^a-z0-9]/g, '').slice(0, 24); setCk('cvc_vid', v, 365); }
    actor = 'v:' + v;
  }
  function send(o) { o.a = actor; try { navigator.sendBeacon(C.u, new Blob([JSON.stringify(o)], { type: 'text/plain' })); } catch (e) {} }

  send({ e: 'view', p: location.pathname });

  var IDLE = 180000, acc = 0, last = Date.now(), act = Date.now();
  ['pointermove', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (t) { window.addEventListener(t, function () { act = Date.now(); }, { passive: true }); });
  function tick(force) {
    var n = Date.now();
    if (document.visibilityState === 'visible' || force) { if (n - act < IDLE) acc += Math.round((n - last) / 1000); }
    last = n;
    if (acc >= (force ? 5 : 60)) { send({ e: 'beat', s: Math.min(90, acc) }); acc = 0; }
  }
  setInterval(function () { tick(false); }, 15000);
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') tick(true); else last = Date.now(); });
  window.addEventListener('pagehide', function () { tick(true); });
})();
