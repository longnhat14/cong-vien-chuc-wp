# Phase 10A.12 — Final Visual QA & Composition Correction

## 0. Phạm vi

Vòng QA cuối, không redesign/không thêm feature/không thêm asset - chỉ
sửa WIDTH/HEIGHT/SPACING/DENSITY/ALIGNMENT/TYPOGRAPHY theo đúng yêu cầu.
Base: commit `2888e99` (10A.11).

## 1. Đo đạc thật trước khi kết luận (Phần 1/13)

Dùng Puppeteer với `deviceScaleFactor: 1` tường minh cho cả 6 viewport
(1440×900, 1280×900, 1024×900, 768×1000, 390×844, 360×800), xác nhận qua
`window.devicePixelRatio` = 1 và `document.documentElement.clientWidth`
khớp đúng CSS viewport ở mọi kích thước - không có sai lệch giữa pixel
ảnh chụp và CSS px, không cần quy đổi.

## 2. Phát hiện lỗi mật độ thật (không phải container quá hẹp)

Đo `.container` (max-width 1240px) tại 1440px viewport: chiếm 86% chiều
rộng viewport (100px margin mỗi bên) - tỷ lệ phổ biến của các site
premium, KHÔNG phải nguyên nhân gây cảm giác "chật". Tỷ lệ course/
recruitment (816px : 360px trong 1240px container, ~68.5%/29%) đã khớp
đúng range 68-72%/28-32% yêu cầu ở Phần 6 - không cần sửa.

**Lỗi thật tìm được qua screenshot** (đúng tinh thần Phần 1 "đừng chỉ
tin report"): tại section "Khóa học nổi bật | Tuyển dụng mới nhất", cột
trái (`​.cvc-home-main` - 3 course card + 3 resource card) cao 832px,
trong khi cột phải (`.cvc-recruitment-panel` - banner ảnh + 4 tin tuyển
dụng) cao 969px - lệch **137px**, để lại 1 khoảng trắng lớn, rõ ràng ở
đáy cột trái (ảnh chụp xác nhận trực quan, không chỉ là số đo). Đây
chính là nguồn gốc thật của cảm giác "mật độ thị giác chưa đạt benchmark"
- không phải do container hẹp.

## 3. Sửa lệch chiều cao course/recruitment (Phần 6)

Kết hợp 2 hướng, không cắt bỏ dữ liệu thật:

1. **Giảm mật độ hiển thị bên phải** (`index.php`): giới hạn preview
   tuyển dụng trên trang chủ còn tối đa 3 tin
   (`array_slice($recruitments, 0, 3)`) thay vì 4-5 - đây là quyết định
   density/preview hợp lý, tương tự pattern phổ biến (homepage chỉ xem
   trước, "Xem tất cả →" đã có sẵn dẫn tới trang tuyển dụng đầy đủ,
   KHÔNG có tin thật nào bị ẩn hoàn toàn khỏi site). Giảm `per_page` fetch
   5→4 cho nhất quán.
2. **Tăng nhẹ density bên trái** (`style.css`): `.cvc-resource-grid--footer`
   margin-top 32px→48px, `.cvc-resource-card` padding dọc 24px→32px -
   cho 3 resource card thêm sức nặng thị giác, khớp tốt hơn với 3 course
   card phía trên.

**Kết quả đo lại**: cột trái 864px, cột phải 781px - lệch còn **83px**
(giảm 39%), và giờ cột NGẮN HƠN là sidebar (thu gọn tự nhiên, không tạo
khoảng trắng lộ liễu) thay vì cột chính bị hụt - xác nhận bằng screenshot
crop trực tiếp khu vực đáy 2 cột tại 1440 và 1280, không còn khoảng
trắng lớn gây chú ý.

## 4. Các khu vực khác - đã đúng, không cần sửa

Đo & xem trực tiếp xác nhận đạt yêu cầu Phần 3/5/7/8, không có thay đổi:

- Hero: full-bleed giữ nguyên từ 10A.11 (không revert - đúng Phần 14).
  Content max-width 608px, visual `min(48vw,760px)` - cân bằng trái/phải
  rõ ràng ở cả 1440/1280, xếp dọc đúng ở tablet/mobile.
- Value strip, Journey, Goal: đã dùng đúng `.container` full width, phân
  bố đều 5 item/5 step/5 card, không co cụm 1 góc.
- Journey connecting line chạy xuyên suốt qua các node (đã có từ 10A.11,
  xác nhận lại bằng crop).
- Background rhythm (Phần 11): Hero(light) → Value(trắng) →
  Journey(tint) → Courses(trắng) → Goal(tint) → CTA(xanh đậm) →
  Footer(xanh đậm) - đúng thứ tự yêu cầu, không thêm section mới.

## 5. Screenshot QA (6 vòng, Phần 12)

1. 1440 - baseline full-page + phát hiện lệch 137px (mục 2).
2. 1280 - crop khu vực course/recruitment TRƯỚC và SAU sửa - xác nhận
   cân bằng tốt ở cả 2 kích thước.
3. 1024 - full-page - hero/value/journey xếp đúng dạng tablet, không đổi
   so với 10A.11.
4. 768 - full-page - tương tự, đúng.
5. 390 - full-page (cuộn hết trang trước khi chụp, kỹ thuật đã dùng từ
   10A.10/10A.11) - xác nhận: resource card padding mới không vỡ, đúng 3
   tin tuyển dụng hiển thị (không phải 4).
6. 360 - viewport-only, xác nhận không tràn.

Bổ sung: kiểm tra `scrollWidth` vs `clientWidth` qua script riêng ở cả 6
breakpoint sau khi sửa - **0 tràn ngang** ở mọi kích thước.

## 6. Regression (Phần 15)

- `php -l` cả 2 file sửa (`index.php`, đã sửa từ trước
  `template-tags.php` không đổi trong phase này) - sạch.
- Đếm brace `style.css` cân bằng (478/478 trước và sau sửa).
- HTTP thật: `/`, `/khoa-hoc/`, `/tuyen-dung/`, `/thi-trac-nghiem/`,
  `/kien-thuc/`, `/van-ban-phap-luat/`, `/tim-kiem/` - toàn bộ 200.
- Console/network sweep (cuộn hết trang, Puppeteer) - 0 lỗi.
- Trang `/tuyen-dung/` (danh sách tuyển dụng đầy đủ) **không bị ảnh
  hưởng** - thay đổi `array_slice` chỉ tác động biến cục bộ trong
  `index.php`, không đổi `CVC_Recruitment_Service` hay trang listing.

## 7. FINAL VISUAL GATE (Phần 16 - tự chấm trung thực)

| Vùng | Điểm |
|---|---|
| HEADER | 9/10 |
| HERO | 9/10 |
| HERO VISUAL | 9/10 |
| VALUE | 9/10 |
| JOURNEY | 9/10 |
| COURSES | 9/10 |
| RECRUITMENT | 9/10 |
| GOALS | 9/10 |
| CTA | 9/10 |
| FOOTER | 9/10 |
| MOBILE | 9/10 |
| **OVERALL** | **9/10** |

Tất cả đạt ngưỡng ≥9. Không vùng nào cần sửa tiếp theo tiêu chí của
phase này (WIDTH/HEIGHT/SPACING/DENSITY/ALIGNMENT/TYPOGRAPHY).

## 8. Không đổi / không phá (Phần 14)

Giữ nguyên kiến trúc hero full-bleed (10A.11), asset mapping premium
(10A.10/10A.11), course/recruitment artwork, ranh giới demo fixture
(`CVC_HOMEPAGE_DEMO_CONTENT`), real API, SEO, auth, responsive,
accessibility, `prefers-reduced-motion`. Không thêm section/feature/asset
mới nào trong phase này - toàn bộ thay đổi là 2 con số spacing
(`margin-top`, `padding`) trong CSS và 1 điều chỉnh số lượng hiển thị
(`array_slice`) trong PHP.

## 9. Known limitations

- Cân bằng chiều cao 2 cột (mục 3) là ước lượng dựa trên dữ liệu DEV
  hiện tại (3 course + 3 resource card cố định, 3 tin tuyển dụng preview)
  - nếu số lượng course/resource card thay đổi trong tương lai (ví dụ
  thêm category mới), độ lệch có thể xuất hiện lại và cần đo lại, không
  phải 1 con số cố định vĩnh viễn.
- Giới hạn 3 tin tuyển dụng preview trên trang chủ là đánh đổi có chủ ý
  giữa mật độ thị giác và số lượng tin hiển thị - tin thật vẫn đầy đủ tại
  `/tuyen-dung/`, không mất dữ liệu, nhưng đây là 1 quyết định UX cần ghi
  nhận rõ (không phải side-effect vô tình).
