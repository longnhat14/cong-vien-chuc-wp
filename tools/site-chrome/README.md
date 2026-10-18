# site-chrome.css

Header/footer dùng chung toàn site (giao diện trang chủ v17), class tiền tố `ch-`.

Sinh lại sau khi sửa header/footer trong `home-v17.css`:

```
pip install tinycss2
T=theme/cong-vien-chuc/assets/css
python3 tools/site-chrome/genchrome.py $T/home-v17.css > /tmp/gen.css
{ head -2 /tmp/gen.css; cat tools/site-chrome/chrome-base.css; tail -n +3 /tmp/gen.css; cat tools/site-chrome/chrome-extra.css; } > $T/site-chrome.css
```

- `chrome-base.css`: nền tảng (font, box-sizing, reset liên kết) - đứng trước phần sinh.
- `chrome-extra.css`: tìm kiếm, thông báo, mục đang xem, tài khoản trong menu di động, bố cục 1101-1260px, in.
- Markup: `theme/cong-vien-chuc/inc/chrome.php` (`cvc_chrome_header()`, `cvc_chrome_footer()`).
