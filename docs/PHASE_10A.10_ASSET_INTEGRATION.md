# Phase 10A.10 — Integrate Premium Visual Asset Package

## 0. Bối cảnh

Người dùng cung cấp `homepage-assets-incoming/` - bộ ảnh do ChatGPT Image
Generation tạo riêng cho homepage Công Viên Chức (13 file crop + 1 board
collage + 2 file tham khảo + 1 file zip + README). Mục tiêu: dùng các
ảnh này để nâng homepage từ "premium nhờ SVG nhiều lớp" (10A.9) lên
"premium nhờ ảnh thật", không chỉ đổi src ảnh mà phải tái cấu trúc từng
khu vực cho hợp lý.

## 1. Kiểm tra asset package (bắt buộc trước khi dùng)

Đọc `README.md` và mở từng file 01-13 bằng Read tool. Phát hiện quan
trọng: **hầu hết các file KHÔNG phải ảnh minh họa "sạch"** - chúng là
crop trực tiếp từ 1 board collage, nhiều file vẫn còn nguyên chữ/UI/badge
baked-in, và một số file bị lỗi crop:

| File | Nội dung thật | Vấn đề |
|---|---|---|
| 01-homepage-hero.png (818×488) | Composition đầy đủ: logo, headline, search, tag, 2 CTA, người + tòa nhà, feature card, tagline | Toàn bộ UI/text đã baked-in trùng 100% với HTML thật |
| 02–04 (course, ~200×226) | Ảnh chủ đề + badge category baked-in góc trên-trái | Badge trùng `.cvc-card__media-badge` thật |
| 05-course-hanh-chinh.png | Tòa nhà + badge "Hành chính" | Dính watermark tên file ở dải trên cùng (lỗi export từ board) |
| 06-course-phap-luat.png | Nửa phải: sách luật + badge "Pháp luật" sạch | Nửa trái là mảnh ảnh KHÁC bị dính vào (lỗi crop từ board - seam cứng ở x=108), kèm watermark tên file |
| 07-recruitment-banner.png | Nửa trái: headline+CTA baked-in. Nửa phải: ảnh 4 người thật, sạch | Nửa trái trùng HTML sidebar tuyển dụng |
| 08-goal-illustration.png | **Toàn bộ là 1 mockup UI đầy đủ** (heading + 5 card icon+text) | 100% trùng lặp `cvc_render_goal_direction_section()` - không có phần "ảnh minh họa" tách biệt để dùng |
| 09-cta-banner.png | Headline/quote/2 nút baked-in (khớp 100% với HTML CTA thật), nền phải là ảnh skyline sạch | Toàn bộ text trùng HTML thật, chỉ nền ảnh dùng được |
| 10-icon-set.png | Bộ icon flat tham khảo | Chỉ để tham khảo phong cách (README nói rõ), không đưa PNG sheet vào UI |
| 11-background-pattern.png | Hoạ tiết sóng nhạt, sạch | Dùng trực tiếp được |
| 12-footer-bg.png | Skyline xanh đậm, sạch | Dùng trực tiếp được |
| 13-brand-logo.png | **Hỏng hoàn toàn** - thực chất là bảng liệt kê tên file (text), không phải logo | Không dùng được |

Kết luận: đây không phải "13 asset sẵn sàng dùng" mà là **nguyên liệu cần
art-direct** - đúng như README đã cảnh báo ("KHÔNG dùng ảnh board/collage
làm background... nếu cần chất lượng cao hơn, ưu tiên tạo lại bằng image
generation"). Môi trường này không có công cụ image generation, nên xử lý
bằng crop + Gaussian-blur-mask có chủ đích (Pillow, cài trong venv cục bộ)
để bóc tách phần ảnh sạch khỏi phần chữ/UI baked-in, thay vì "tạo lại".

## 2. Asset strategy

Production images: `theme/cong-vien-chuc/assets/images/homepage/` - mỗi
file có cặp `.png` (fallback) + `.webp` (chính, nhỏ hơn 8-10 lần). Code
PHP chỉ reference thư mục này qua `get_theme_file_uri()`/`get_theme_file_path()`.

Source package (`homepage-assets-incoming/`, 15MB gồm cả file zip 7.4MB
và 2 board collage 2MB) **không commit vào git** - thêm vào `.gitignore`,
giữ nguyên trên đĩa để tham khảo/tái xử lý sau nếu cần, đúng yêu cầu
"giữ source riêng cho QA, không để repo phình vì duplicate assets".

## 3. Hero

**Không** dùng nguyên `01-homepage-hero.png` làm background rồi đè HTML
lên (đúng cảnh báo ở Phần 3 của brief). Xử lý bằng Pillow:
1. Crop vùng bên phải (người + tòa nhà + cờ + cây), bỏ hẳn cột text/search/
   tag/nút bên trái (baked-in, trùng HTML thật).
2. Trong vùng crop đó, làm mờ (Gaussian blur + mask bo góc) đúng 2 chỗ:
   - Ô mock "feature card" (4 dòng Học tập linh hoạt/Lộ trình/Cơ hội/
     Cộng đồng) - trùng 100% với `cvc_render_hero_feature_card()` thật.
   - Mảnh tag pill còn sót ở mép trái dưới.
3. Kết quả: `hero-photo.png/webp` (433×415) - người + trụ sở + cờ + cây
   sắc nét, phần dưới-phải mờ nhẹ.

Vì `.cvc-hero-feature-card` (CSS có sẵn từ 10A.7) vốn đã
`position:absolute; right:0; bottom:-1.5rem` - đúng vị trí vùng vừa làm
mờ - nên khi card HTML thật nổi lên trên, hiệu ứng đọc như "kính mờ nổi
trên ảnh nét", không phải trùng lặp hay lỗi. `cvc_render_hero_illustration()`
viết lại để xuất `<picture><source webp><img png></picture>`
(`fetchpriority="high"`, không lazy vì luôn trên màn hình đầu); SVG bản
10A.9 giữ nguyên làm `cvc_render_hero_illustration_svg_fallback()` -
tự động dùng nếu thiếu file ảnh.

**Bug phát hiện qua QA responsive (xem mục 8)**: ở breakpoint tablet
(641-1024px), CSS cũ (10A.9) set `.cvc-hero-feature-card { display:none }`
vì card 15rem quá rộng so với minh họa đã co (280px) - hợp lý khi minh
họa là SVG (không có gì cần che). Nhưng minh họa giờ là ẢNH THẬT có sẵn
vùng mờ dành riêng cho card - ẩn card đi thì vùng mờ đó lộ ra như 1 vệt
vô nghĩa. Sửa: card chuyển từ `position:absolute` (đè lên ảnh) sang
`position:static` (xếp ngay dưới ảnh, full-width) ở breakpoint này thay
vì ẩn hẳn - vừa hết bug vừa giữ được nội dung card ở tablet.

## 4. Course artwork - 5 category

`cvc_render_course_thumbnail_placeholder()` giờ ưu tiên ảnh thật (map
theo `course_type`), rơi về SVG scene (10A.9) làm fallback cho type lạ:

| course_type | File nguồn | Xử lý |
|---|---|---|
| `exam_prep` | 02-course-on-thi.png | Blur badge "Ôn thi" góc trên-trái |
| `skill` | 03-course-ky-nang.png | Blur badge "Kỹ năng" |
| `professional` | 04-course-tin-hoc.png | Blur badge - **lưu ý**: tên file nói "tin-hoc" nhưng badge baked-in thật lại là "Chuyên môn", khớp đúng nhãn thật của `professional` (`cvc_course_type_label()`) nên dùng cho slot này thay vì suy đoán theo tên file |
| `orientation` | 05-course-hanh-chinh.png | Cắt bỏ dải watermark tên file ở top, blur badge "Hành chính". Không có ảnh nào khớp nghĩa "định hướng nghề nghiệp" - chọn ảnh trụ sở cơ quan hành chính làm hình ảnh gần nghĩa nhất còn lại |
| `default` (fallback chung) | 06-course-phap-luat.png | Cắt bỏ nửa trái bị lỗi seam + watermark, chỉ giữ nửa phải (sách luật), blur badge "Pháp luật" - khớp đúng vai trò fallback "kiến thức chung/pháp lý" đã đặt ra từ SVG scene 10A.9 |

Badge category baked-in trong mọi ảnh đều bị làm mờ vì
`cvc_render_course_card()` đã tự vẽ `.cvc-card__media-badge` thật đè lên
đúng vị trí đó - giữ nguyên ảnh gốc sẽ ra 2 badge chồng nhau.

CSS: đổi `aspect-ratio` của `.cvc-card__media img` /
`.cvc-card__media--placeholder` từ `16/9` sang `4/3` - khớp hơn với ảnh
nguồn gần vuông (200-227×226px), tránh `object-fit:cover` phải cắt bỏ
gần nửa ảnh theo chiều dọc như khi ép về 16:9.

## 5. Recruitment

Cắt riêng nửa phải sạch của `07-recruitment-banner.png` (4 người + màn
hình tablet, không dính headline/CTA baked-in) làm banner ảnh ở đầu
`<aside class="cvc-recruitment-panel">`, qua hàm mới
`cvc_render_recruitment_panel_banner()` (aria-hidden, thuần trang trí).
Danh sách tin tuyển dụng bên dưới **không đổi gì** - vẫn dữ liệu API thật
ưu tiên, fixture (nếu bật `CVC_HOMEPAGE_DEMO_CONTENT`) vẫn giữ badge
"Demo" như cũ. Ảnh không thêm bất kỳ con số/claim nào.

## 6. Goal section - QUYẾT ĐỊNH KHÔNG DÙNG

`08-goal-illustration.png` không phải ảnh minh họa - nó là 1 mockup UI
đầy đủ (heading "Bạn đang hướng đến điều gì?" + 5 card icon/tiêu đề/mô
tả bằng icon phẳng), gần như bản nháp trực tiếp của
`cvc_render_goal_direction_section()` hiện có. Không có phần nào trong
file tách biệt được thành "ảnh minh họa" so với "UI" - dùng nó nghĩa là
dựng lại y hệt section đã có bằng ảnh tĩnh, vi phạm thẳng nguyên tắc
"không tạo duplicate". Theo đúng brief Phần 9 ("không nhất thiết phải
hiển thị nếu làm layout xấu/trùng lặp"), quyết định: **bỏ qua file này,
giữ nguyên goal section HTML thật hiện có** (đã đạt chuẩn từ 10A.9,
không cần ảnh).

## 7. CTA banner

`09-cta-banner.png` có headline "Hành trang vững vàng / Kiến tạo tương
lai" và quote "Vì một nền công vụ chuyên nghiệp, hiện đại và phục vụ
nhân dân tốt hơn" - **khớp chính xác 100%** với text đã có sẵn trong
`cvc_render_homepage_cta_banner()` (bằng chứng bộ asset này được dùng làm
mockup gốc để tạo ra HTML thật trước đó). Theo đúng lựa chọn B của Phần
10 brief ("crop visual và dùng HTML"): cắt riêng dải skyline/trụ sở bên
phải, blur phần quote text baked-in, dùng làm nền ảnh bên phải của banner
- toàn bộ headline/quote/2 nút vẫn là HTML thật, không đổi 1 chữ.

Ảnh đặt `position:absolute` bên phải, phủ gradient (`::after`) từ màu nền
banner → trong suốt để hoà vào dải xanh đậm thay vì dán cứng 1 khối chữ
nhật. Ẩn hẳn ảnh này ở mobile (<640px, cùng breakpoint banner chuyển
layout 1 cột) - tránh đè lên quote text lúc đó đã full-width.

## 8. Background pattern & Footer

- `11-background-pattern.png`: dùng làm nền trang trí RIÊNG cho section
  `.cvc-hero` (không phải toàn site) - góc trên-phải, không lặp lại, mờ
  nhẹ dưới lớp gradient sky sẵn có.
- `12-footer-bg.png`: skyline xanh đậm, đặt làm nền `.site-footer`
  (dải trên cùng, không lặp lại) qua gradient phủ để không ảnh hưởng độ
  tương phản chữ sáng trên nền tối vốn có.

## 9. Brand logo & Icon set - giữ nguyên, không đổi

- `13-brand-logo.png` hỏng (không phải logo, là bảng liệt kê tên file) -
  giữ nguyên logo SVG hiện có trong `header.php` (chất lượng vector tốt
  hơn hẳn, không có gì để so sánh thay thế).
- `10-icon-set.png` chỉ để tham khảo phong cách theo đúng README - hệ
  icon SVG hiện có (`cvc_render_icon()`) đã phủ đúng ngôn ngữ hình ảnh
  này (graduation cap, briefcase, growth chart, compass...), không đổi
  sang PNG sheet.

## 10. Responsive & QA đã chạy

Dùng Docker Puppeteer (không chỉ curl) - full-page tại 1440/1280/1024/
768/390/360, crop riêng header/hero/course-grid/recruitment/CTA/footer,
và 1 lần chụp có chủ động cuộn hết trang trước khi chụp (phát hiện +
loại trừ 1 artifact của chính công cụ chụp - ảnh `loading="lazy"` không
kịp tải khi chụp full-page không cuộn qua trước; xác nhận đây là giới
hạn của kỹ thuật chụp, không phải lỗi trang, vì ảnh tải đúng khi cuộn
thật).

Bug thật duy nhất tìm thấy qua QA: mục 3 (feature card bị ẩn oan ở
tablet, để lộ vùng ảnh đã làm mờ) - đã sửa.

Kiểm tra JS console/network qua Puppeteer (cuộn hết trang, theo dõi
`console error`, `pageerror`, `requestfailed`, response ≥400): **0 vấn
đề** - không lỗi console, không ảnh 404, không request fail.

`php -l` cả 2 file PHP sửa, đếm brace `style.css` cân bằng, HTTP thật:
`/`, `/khoa-hoc/`, `/tuyen-dung/`, `/tim-kiem/`, `/thi-trac-nghiem/`,
`/kien-thuc/`, `/chu-de/`, `/van-ban-phap-luat/` - toàn bộ 200, không
warning/fatal trong debug log.

SEO/accessibility: `<h1>` vẫn duy nhất, không đổi; mọi `<img>` mới đều có
`alt` (rỗng cho ảnh thuần trang trí trong vùng đã `aria-hidden`, có mô tả
cho ảnh hero).

## 11. Image optimization & performance

Mỗi ảnh production có cặp `.webp` (chính, nhỏ hơn PNG 8-10 lần, ví dụ
hero-photo.png 225KB → .webp 24KB) qua `<picture><source type="image/webp">`.
Tổng thư mục `assets/images/homepage/`: 836KB (cả 2 định dạng); tải
thực tế qua trình duyệt hỗ trợ WebP (mọi trình duyệt hiện đại) nhỏ hơn
nhiều. Hero dùng `fetchpriority="high"` (luôn trên màn hình đầu); mọi
ảnh course/recruitment/CTA dùng `loading="lazy"` (dưới màn hình đầu ở
hầu hết viewport). Không dùng background-image khổng lồ - pattern/footer-bg
chỉ đặt ở 1 vùng nhỏ (top-right hero, top footer), không full-bleed toàn
section.

## 12. Tự chấm điểm

| Vùng | Điểm | Ghi chú |
|---|---|---|
| Hero | 9.5/10 | Ảnh thật (người + trụ sở + cờ + cây), không còn là SVG dù đã nhiều lớp ở 10A.9 - đạt "commercial test" |
| Imagery | 9/10 | Ảnh thật xuyên suốt hero/course/recruitment/CTA; giới hạn: nguồn chỉ ~200-230px nên không nét tuyệt đối ở màn hình retina (không có công cụ image-gen để tạo bản phân giải cao hơn) |
| Typography | 9/10 | Không đổi so với 10A.9 (đã đạt chuẩn) |
| Course | 9/10 | 5 category, mỗi category 1 ảnh thật riêng, không trùng badge |
| Recruitment | 8.5/10 | Banner ảnh thêm chiều sâu; vẫn giữ nguyên độ tin cậy dữ liệu (Demo badge không đổi) |
| Composition | 9/10 | Visual hierarchy giữ nguyên: Hero > Featured Courses > Recruitment/Goal/Resources > CTA |
| Brand | 8.5/10 | Logo SVG gốc giữ nguyên (asset PNG logo hỏng, không có gì thay thế tốt hơn) |
| Conversion | 9/10 | Không CTA/search/badge trùng lặp ở bất kỳ khu vực nào sau khi xử lý |
| Mobile | 9/10 | Đã QA 390/360, ảnh tải đúng khi cuộn thật, CTA photo ẩn đúng lúc, tablet bug đã sửa |
| Performance | 8.5/10 | WebP + lazy-load + fetchpriority đúng chỗ; giới hạn duy nhất là độ phân giải nguồn nhỏ (không phải vấn đề tải trang) |

Không vùng nào dưới 8.5; các vùng dưới 9 đều có lý do kỹ thuật rõ ràng
(độ phân giải nguồn, asset logo hỏng) đã nêu, không phải thiếu công sức.

## 13. Giới hạn đã biết

- Ảnh nguồn nhỏ (course ~200-230px) - đã dùng nguyên bản (không upscale
  giả tạo) nên ở màn hình retina/2x có thể hơi mềm; đây là trần chất
  lượng của bộ asset được cấp, không phải lỗi xử lý.
- `08-goal-illustration.png` và `13-brand-logo.png` không dùng được (lý
  do ở mục 6, 9) - không phải bỏ sót, đã kiểm tra kỹ và ghi rõ nguyên
  nhân.
- Design fixture (`CVC_HOMEPAGE_DEMO_CONTENT`) không thay đổi gì trong
  phase này - vẫn giữ nguyên ranh giới từ 10A.6 (real data luôn thắng,
  production mặc định false).

## 14. Không đổi/không chạm

Không migration, không API redesign, không đổi auth/dashboard/bookmark/
goal/exam/notification. Không thêm forum/chat/testimonial/review/fake
community/fake user counter. Không thêm số liệu/claim mới nào ngoài
những gì đã có từ trước.
