# Phase 10A.13 — Homepage V2 Final Integration & QA

## 0. Nguồn

Hai package cung cấp sẵn tại `_homepage-v2/` (gitignored, xem `.gitignore`):
`cong-vien-chuc-homepage-v2-implemented.zip` (implementation đã chuẩn bị
sẵn, 91 file - gần như bản sao toàn bộ theme) và
`cong-vien-chuc-homepage-v2-assets-complete.zip` (12 ảnh production đã
cắt sẵn + 3 ảnh reference). Đã giải nén cả hai và diff trực tiếp với
source WSL hiện tại (`~/projects/cong-vien-chuc-wp`) trước khi đụng vào
bất kỳ file nào - **không** copy đè nguyên khối.

## 1. Kiểm tra package trước khi dùng

`diff -rq` giữa `theme/cong-vien-chuc/` của package và source hiện tại
cho thấy package thực chất được tạo ra TỪ chính source hiện tại (87/91
file byte-identical) - chỉ 4 file thực sự khác: `functions.php`,
`index.php`, `inc/template-tags.php`, `style.css`, cộng thư mục ảnh mới
`assets/images/homepage-v2/`. Điều này giảm rủi ro merge đáng kể - không
phải "viết lại từ đầu", chỉ cần review 4 diff cụ thể.

Mở từng ảnh trong `PRODUCTION/` (Read tool, không đoán qua tên file) -
xác nhận cả 12 ảnh đều sạch, không có chữ/UI baked-in, đúng như README
package cam kết ("đã cắt sẵn"). Khác hẳn 2 bộ asset trước (10A.10/10A.11)
vốn phải tự crop/blur từ board collage.

## 2. Quyết định merge (không copy đè mù quáng)

Rà kỹ 4 diff, phát hiện 1 vấn đề tích hợp thật cần sửa trước khi dùng
(không phải "thay đổi concept", mà là 1 cách làm sai kỹ thuật):

**Vấn đề**: package gán artwork course theo **vị trí trong mảng kết quả
API** (`$courses_index => course-exam.jpg, 2 => course-skill.jpg, ...`),
không theo `course_type` thật. Nghĩa là 1 course "Pháp luật" đứng thứ 2
trong danh sách sẽ nhận nhầm ảnh `course-skill.jpg` (3 người họp) thay vì
ảnh đúng chủ đề pháp luật, tuỳ hoàn toàn vào thứ tự API trả về - sai với
mục tiêu "Course images có được dùng đúng không?" ở mục self-review.

**Sửa**: bỏ hẳn cơ chế gán theo vị trí (foreach + index trong
`index.php`), đưa mapping vào đúng chỗ đã có sẵn từ 10A.10/10A.11 -
`cvc_render_course_thumbnail_placeholder()` - map theo `course_type`
thật (`exam_prep→course-exam`, `skill→course-skill`,
`professional→course-office`, `orientation→course-admin`,
`default→course-law`, xác định bằng cách MỞ TỪNG ẢNH xem nội dung khớp
category nào, không đoán qua tên file). Cùng cơ chế `thumbnail_url` API
thật → luôn thắng như trước, không đổi.

**Vấn đề thứ 2**: package đổi `per_page` course từ 3→5, đồng thời
`.cvc-card-grid--3` bị ép cứng `repeat(3, ...)` - 5 course sẽ tạo layout
3+2 lệch hàng, và (quan trọng hơn) phá vỡ cân bằng chiều cao course-column
vs recruitment-column đã đo đạc và sửa cẩn thận ở Phase 10A.12
(`array_slice` tuyển dụng còn 3 tin, dựa trên giả định course column có
đúng 3 card). **Giữ nguyên `per_page => 3`** - không có yêu cầu rõ ràng
nào trong Homepage V2 concept đòi hỏi 5 course, còn rủi ro phá layout đã
tested là có thật và cụ thể.

**Áp dụng nguyên trạng** (merge sạch, không sửa gì thêm vì đã đúng):
- `functions.php`: hook preload ảnh hero.
- `index.php`: class `cvc-home-v2` trên `<main>` (cần thiết để toàn bộ
  CSS scoping bên dưới có tác dụng).
- `style.css`: nguyên khối `.cvc-home-v2` (~380 dòng, append cuối file,
  không đụng rule nào có sẵn, không `!important`) - well-scoped, chất
  lượng tốt, giữ lại toàn bộ.

## 3. Sửa dimension sai (width/height mismatch)

Package khai `width`/`height` trên các thẻ `<img>` KHÔNG khớp kích thước
file thật (kiểm tra bằng `identify`):

| Ảnh | Khai trong package | Thật |
|---|---|---|
| hero-main.jpg | 1920×1080 | **1672×941** |
| recruitment.jpg | 800×600 | **412×210** |
| cta-background.jpg | 1920×800 | **377×210** |

Đã sửa cả 3 về đúng kích thước thật trong `inc/template-tags.php` -
width/height sai lệch tỷ lệ khiến trình duyệt tính sai aspect-ratio
placeholder trước khi ảnh tải xong, gây layout shift (CLS) thật, không
phải chi tiết vặt.

## 4. Tối ưu ảnh (không crop)

Tạo bản `.webp` cho toàn bộ 11 ảnh `.jpg` mới bằng Pillow (re-encode
cùng kích thước pixel, quality=85) - **không phải crop**, chỉ đổi định
dạng nén, giảm dung lượng ~60-70% (ví dụ hero-main.jpg 428KB → webp
160KB). Dùng qua `<picture><source type="image/webp"><img jpg></picture>`
- đúng tinh thần "Ưu tiên WebP nếu available" mà không vi phạm "không
crop lại asset production" (pixel gốc giữ nguyên 100%).

`functions.php`: thêm `<link rel="preload">` cho bản `.webp` (không phải
`.jpg`) - preload đúng resource mà `<picture>` sẽ chọn ở trình duyệt hỗ
trợ WebP, tránh lãng phí băng thông tải nhầm định dạng không dùng tới.

## 5. Dọn asset cũ (Phần 11 cleanup)

Xoá 8 cặp file `.png/.webp` cũ trong `assets/images/homepage/`
(hero-photo, recruitment-photo, cta-skyline, course-exam-prep,
course-skill, course-professional, course-orientation, course-default) -
không còn được code nào reference sau khi chuyển hết sang
`assets/images/homepage-v2/`. Giữ lại `bg-pattern.*` và `footer-bg.*`
(vẫn dùng cho background hero/footer, package V2 không đụng tới 2 phần
này). Xác nhận bằng `grep` không còn reference nào tới path cũ trước khi
xoá.

`hero-building.jpg`, `hero-city.jpg`, `decorative-elements.png` (3 asset
production có sẵn nhưng package không wire vào) - giữ nguyên trên đĩa
(không xoá, không phải rác - chỉ là asset dự phòng chưa dùng), không đưa
vào code vì `hero-main.jpg` đã là 1 composition hoàn chỉnh (người + trụ
sở + cờ + skyline), không cần ghép thêm; decorative-elements có vai trò
tương tự `bg-pattern.png` đã dùng, thêm cả 2 sẽ dư thừa/rối mắt hơn là
cao cấp hơn.

## 6. Browser QA thật - phát hiện 2 bug tích hợp

Render thật qua Docker Puppeteer tại 1440×1000, 1024×1000, 768×1000,
390×844 (`deviceScaleFactor:1`, xác nhận `window.innerWidth` =
`document.documentElement.clientWidth` = đúng viewport yêu cầu ở cả 4
kích thước - không có sai lệch pixel ảnh chụp vs CSS viewport).

**Bug 1 - card nổi đè lên H1 ở tablet (1024px)**: crop screenshot cho
thấy `.cvc-hero-feature-card` (4 dòng "Học tập linh hoạt...") tràn xuống
đè lên eyebrow badge + H1 bên dưới hero. Nguyên nhân: rule tablet cũ từ
10A.10 (`@media 641-1024px { .cvc-hero-feature-card { position:static }
}`) được viết cho kiến trúc CŨ (ảnh minh họa cao tự động theo nội dung).
Ở Homepage V2, `.cvc-hero__visual` tại các breakpoint ≤1024px có
`height` CỐ ĐỊNH (330px/270px/235px) - card ở `position:static` tràn ra
ngoài khối cố định đó thay vì được khối tự giãn theo. Sửa: khai báo lại
`position:absolute` tường minh trong rule `.cvc-home-v2
.cvc-hero-feature-card` (specificity cao hơn rule cũ, thắng cascade) -
card quay lại đúng vị trí nổi trong ảnh ở mọi breakpoint ≥481px.

**Bug 2 - tràn ngang 293px tại đúng 768px**: script đo
`scrollWidth`/`clientWidth` phát hiện `768px -> scrollWidth=1061
clientWidth=768 OVERFLOW!`. Truy vết bằng cách duyệt DOM đo `scrollWidth`
từng phần tử: `.cvc-journey` (5 bước "Con đường của bạn") là thủ phạm -
`grid-template-columns` thực tế là `"736px"` (1 cột duy nhất) thay vì
`repeat(5, minmax(0,1fr))`, khiến cả 5 `<li>` xếp chồng lên nhau trong 1
ô 736px, và các đường nối `::after` (absolute, width:84% nối sang bước
kế) đẩy `scrollWidth` lên 1045px. Nguyên nhân: 1 rule
`@media(max-width:900px){.cvc-journey{grid-template-columns:1fr}}` từ
trước Homepage V2 (thiết kế cho layout dọc cũ) vẫn còn trong file, cùng
specificity với rule gốc nhưng đứng sau nên thắng - đúng lúc 768px lọt
vào khoảng trống giữa 2 bộ breakpoint (rule cũ dùng ngưỡng 900px, V2
dùng ngưỡng 767px, để hở khoảng 768-900px không ai kiểm soát đúng). Đã
xoá hẳn rule 900px cũ (xác nhận `.cvc-journey` chỉ dùng ở
`inc/template-tags.php`/homepage, không nơi nào khác - an toàn xoá, đúng
tinh thần Phần 11 cleanup) vì Homepage V2 đã có bộ breakpoint đầy đủ
riêng cho component này.

Cả 2 bug đều được re-test bằng screenshot + đo `scrollWidth` lại sau khi
sửa - xác nhận hết.

## 7. Kết quả QA cuối

- `scrollWidth === clientWidth` ở cả 4 breakpoint (1440/1024/768/390) -
  không còn tràn ngang.
- Console/network sweep (cuộn hết trang, theo dõi console error/
  pageerror/requestfailed/response≥400) - 0 vấn đề.
- `php -l` toàn bộ file PHP trong theme - sạch.
- HTTP thật: `/`, `/khoa-hoc/`, `/tuyen-dung/`, `/thi-trac-nghiem/`,
  `/kien-thuc/`, `/van-ban-phap-luat/`, `/tim-kiem/`,
  `/dang-nhap/`, `/dang-ky/`, `/tai-khoan/` (302 redirect - đúng hành vi
  chưa đăng nhập) - tất cả phản hồi đúng.
- SEO: `<title>`, meta description, canonical, `<h1>` duy nhất - xác
  nhận không đổi so với trước phase này.
- Alt text: ảnh hero có alt mô tả, ảnh course/recruitment/CTA trang trí
  có `alt=""` (đúng, đã nằm trong vùng `aria-hidden` hoặc cạnh link có
  text thật).
- `prefers-reduced-motion`: rule toàn site từ 10A.11 vẫn còn nguyên, v2
  bổ sung thêm rule riêng cho hover course-card (dự phòng, không xung
  đột).
- Mobile: course card + journey chuyển thành carousel cuộn ngang có chủ
  đích (scroll-snap) ở ≤767px thay vì ép desktop xuống 1 cột dọc - đúng
  yêu cầu "mobile phải được thiết kế có chủ đích".

## 8. Self-review (mục 12 của brief)

1. Khác biệt rõ so với bản cũ? **Có** - ảnh hero thật 1672×941 full-bleed
   (trước là crop nhỏ 433×415 tự xử lý), card nổi kính mờ (backdrop-blur),
   course artwork chi tiết hơn và map đúng category, recruitment/CTA
   dùng ảnh production thật.
2. Hero đủ mạnh? Có - full-bleed, H1 lớn (`clamp` responsive), CTA rõ.
3. Course images đúng? Có, sau khi sửa bug mapping-theo-vị-trí (mục 2).
4. Có crop lại asset production? Không - dùng nguyên kích thước gốc,
   chỉ re-encode WebP (không đổi pixel).
5. Fake data production? Không - ranh giới real API/fixture giữ nguyên.
6. Regression? Tìm thấy 2 (mục 6), đã sửa và re-verify.
7. Horizontal overflow? Không còn, xác nhận đo lại ở cả 4 breakpoint.
8. Mobile thiết kế riêng? Có - carousel cuộn ngang có chủ đích.
9. SEO còn nguyên? Có, xác nhận title/description/canonical/H1.
10. Console/network sạch? Có, 0 vấn đề.
11. Git diff chỉ chứa thay đổi cần thiết? Có - 4 file PHP/CSS + hoán đổi
    thư mục ảnh, không file nào khác bị đụng.

## 9. Không đổi / không chạm

Không migration, không API contract, không đổi login/register/account/
dashboard/exam/recruitment/bookmark/engagement. Không thêm fake
testimonial/community/statistics. `CVC_HOMEPAGE_DEMO_CONTENT` giữ nguyên
ranh giới từ 10A.6.

## 10. Known limitations

- `hero-building.jpg`, `hero-city.jpg`, `decorative-elements.png` không
  được dùng (lý do ở mục 5) - quyết định có chủ đích, không phải bỏ sót.
- Course artwork vẫn ở độ phân giải khiêm tốn (283-290×201px) - đây là
  toàn bộ những gì package cung cấp cho course, không có gì lớn hơn để
  dùng; hero-main.jpg (1672×941) là ảnh production duy nhất có độ phân
  giải cao trong bộ này.
