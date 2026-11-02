/* Thói quen ôn thi (02/11/2026): bật nhắc ôn - thông báo trình duyệt (Web Push) + email, chọn giờ. */
(function () {
  'use strict';
  var C = window.CVC_STUDY || {};

  function post(op, data) {
    var fd = new FormData();
    fd.append('action', 'cvc_study'); fd.append('nonce', C.nonce || ''); fd.append('op', op);
    Object.keys(data || {}).forEach(function (k) { if (data[k] !== null && data[k] !== undefined) fd.append(k, data[k]); });
    return fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j || !j.success) throw new Error((j && j.data && j.data.message) || 'Chưa lưu được.');
      return j.data;
    });
  }
  function u8(b64) {
    var pad = '='.repeat((4 - b64.length % 4) % 4), s = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/'), raw = atob(s), out = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
  }
  function device() { return /iPhone|iPad|iPod/.test(navigator.userAgent) ? 'ios' : (/Android/.test(navigator.userAgent) ? 'android' : 'desktop'); }
  function pushSupported() { return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window; }

  /** Đăng ký thông báo trình duyệt (xin quyền, lấy khóa VAPID, lưu đăng ký). */
  function enablePush(hour, test) {
    if (!pushSupported()) {
      return Promise.reject(new Error(device() === 'ios' ? 'iPhone / iPad: hãy thêm congvienchuc.com vào Màn hình chính rồi mở từ biểu tượng đó để nhận thông báo. Email vẫn được bật.' : 'Trình duyệt này chưa hỗ trợ thông báo. Email vẫn được bật.'));
    }
    return navigator.serviceWorker.register(C.sw, { scope: '/' }).then(function (reg) {
      return Notification.requestPermission().then(function (perm) {
        if (perm !== 'granted') throw new Error('Bạn chưa cho phép thông báo (bấm biểu tượng ổ khóa cạnh địa chỉ trang để bật lại).');
        return fetch(C.api + '/api/push/vapid').then(function (r) { return r.json(); }).then(function (j) {
          return reg.pushManager.getSubscription().then(function (old) {
            return old || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: u8(j.data.public_key) });
          });
        });
      });
    }).then(function (sub) {
      return post('push_sub', { sub: JSON.stringify(sub), device: device(), hour: hour, test: test ? 1 : 0 });
    });
  }
  function disablePush() {
    if (!pushSupported()) return post('settings', { remind_push: 0 });
    return navigator.serviceWorker.getRegistration('/').then(function (reg) {
      return reg ? reg.pushManager.getSubscription() : null;
    }).then(function (sub) {
      var ep = sub ? sub.endpoint : '';
      return (sub ? sub.unsubscribe() : Promise.resolve()).then(function () { return post('push_unsub', { endpoint: ep }); });
    });
  }

  // Khối "Ôn hôm nay": nút Bật nhắc ôn.
  document.querySelectorAll('[data-st-remind]').forEach(function (box) {
    var btn = box.querySelector('[data-st-enable]'); if (!btn) return;
    var msg = box.querySelector('[data-st-msg]');
    btn.addEventListener('click', function () {
      var hour = box.querySelector('[data-st-hour]').value, email = box.querySelector('[data-st-email]').checked;
      btn.disabled = true; msg.textContent = 'Đang bật…';
      post('settings', { hour: hour, remind_email: email ? 1 : 0 }).then(function () {
        return enablePush(hour, true).then(function () { return 'Đã bật nhắc ôn lúc ' + hour + ':00 qua thông báo trình duyệt' + (email ? ' và email' : '') + '. Đã gửi 1 thông báo thử.'; },
          function (e) { return (email ? 'Đã bật nhắc qua email lúc ' + hour + ':00. ' : '') + e.message; });
      }).then(function (t) { msg.textContent = t; btn.textContent = 'Đã bật'; }).catch(function (e) { msg.textContent = e.message; btn.disabled = false; });
    });
  });

  // Trang Tài khoản → Thông báo: cài đặt nhắc ôn.
  document.querySelectorAll('[data-st-settings]').forEach(function (box) {
    var msg = box.parentNode.querySelector('[data-st-msg]');
    box.querySelector('[data-st-save]').addEventListener('click', function () {
      var hour = box.querySelector('[data-st-hour]').value, email = box.querySelector('[data-st-email]').checked, push = box.querySelector('[data-st-push]').checked;
      msg.textContent = 'Đang lưu…';
      post('settings', { hour: hour, remind_email: email ? 1 : 0 }).then(function () {
        return push ? enablePush(hour, false).then(function () { return ''; }, function (e) { return ' ' + e.message; }) : disablePush().then(function () { return ''; }, function () { return ''; });
      }).then(function (extra) { msg.textContent = 'Đã lưu.' + extra; }).catch(function (e) { msg.textContent = e.message; });
    });
  });
}());
