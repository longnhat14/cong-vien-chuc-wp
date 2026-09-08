# Phase 10A.11 — Homepage Master Visual Implementation

## 0. Bối cảnh

Sau 10A.10 (tích hợp ảnh thật lần đầu), người dùng phản hồi trực tiếp:
**"bạn thiết kế chẳng ra sao"**. Tự soi lại bằng screenshot thật (không tự
biện hộ), 3 vấn đề cụ thể được xác nhận trước khi bắt đầu phase này:

1. Hero rời rạc - ảnh chỉ là 1 khối chữ nhật nhỏ nổi giữa mảng nền xanh
   nhạt trống trải, đọc như sticker dán vào chứ không phải hero visual
   liền mạch.
2. "Wall of cards" - value strip, learning journey, goal section, resource
   hub đều dùng chung công thức khung trắng bo góc + icon tròn + tiêu đề,
   lặp lại liên tục xuống hết trang, không có nhịp điệu thị giác.
3. Ảnh nhỏ, cạnh crop thô - thiếu sức nặng thị giác của 1 sản phẩm premium.

Phase 10A.11 giải quyết cả 3, dùng bộ asset mới
`homepage-assets-incoming/cvc-final/` làm nguồn, với benchmark mới
(`00_REFERENCE/benchmark-homepage.png`) làm SOURCE OF TRUTH về art
direction - benchmark này khác bản dùng ở 10A.2-10A.10: ảnh hero bleed
full-width/full-height thay vì đóng khung nhỏ.

## 1. Kiểm tra asset package - phát hiện lỗi hệ thống

`find` liệt kê 22 file. Mở từng file bằng Read tool (không đoán qua tên
file) phát hiện: **toàn bộ 13 file "crop riêng"** trong
`02_COURSE_ARTWORK/`, `03_SECTION_ARTWORK/`, `04_BRAND/`,
`05_ICON_REFERENCE/04-icon-reference.png`,
`06_BACKGROUND_REFERENCE/05-*-reference.png` và `06-*-reference.png`
đều bị lỗi crop hệ thống (lệch offset cố định, luôn cắt trúng dòng
caption tên file + nội dung của Ô KẾ BÊN thay vì đúng ô artwork). Ví dụ
`01-on-thi-artwork.png` chứa mảnh của dòng trust-signal + banner tuyển
dụng, hoàn toàn không phải artwork Ôn thi.

Ngược lại, `00_REFERENCE/visual-master-board-v2.png` (board tổng, đã xác
nhận **khác** file board cũ qua md5sum - đây là bản v2 revise thật, không
phải file cũ đổi tên) mở ra **nguyên vẹn, đúng bố cục**, và
`05_ICON_REFERENCE/icon-set.png`, `06_BACKGROUND_REFERENCE/
background-pattern.png`, `06_BACKGROUND_REFERENCE/footer-bg.png` (3 file
đặt tên không theo pattern "0X-...-reference") cũng crop đúng.

**Quyết định**: tự cắt lại toàn bộ artwork cần dùng trực tiếp từ
`visual-master-board-v2.png` bằng Pillow (grid-assist, cùng kỹ thuật đã
dùng ở 10A.10), thay vì tin các file crop riêng bị lỗi. Đối chiếu
`hero-full-composition-reference.png` với `01-homepage-hero.png` (bộ cũ)
qua md5sum xác nhận **byte-identical** - ảnh hero nguồn không đổi so với
10A.10, chỉ có board chứa course/section artwork là bản v2 mới.

## 2. Asset mapping (tự cắt từ visual-master-board-v2.png)

| Vùng | Toạ độ cắt (trong board 1536×1024) | Xử lý |
|---|---|---|
| Hero photo | (giữ nguyên từ 10A.10 - ảnh nguồn không đổi) | không đổi |
| course-exam-prep | (858,8)-(1058,262) | Blur dải badge+tagline cao 108px |
| course-skill | (1078,8)-(1280,262) | nt |
| course-professional | (1298,8)-(1500,262) | nt |
| course-orientation | (858,288)-(1190,508) | nt |
| course-default | (1190,288)-(1500,508) | nt |
| recruitment-photo | (8,545)-(480,748), crop tiếp x≥272 | Bỏ hẳn nửa trái (headline/CTA baked-in) |
| cta-skyline | (1025,545)-(1500,748), crop tiếp x≥280 | Blur quote text baked-in |
| icon-set, background-pattern, footer-bg | dùng trực tiếp file crop sẵn (đã đúng) | không cần xử lý |

`04_BRAND/logo-reference.png` hỏng (trống, chỉ còn mảnh chữ "go.png") -
không dùng, giữ nguyên logo SVG hiện có (đã quyết định từ 10A.10, vẫn
đúng). `03_SECTION_ARTWORK/02-goal-visual.png` (dù bị lỗi crop) khi đối
chiếu với tile "08-goal-illustration.png" trên board vẫn cho thấy đây là
**1 mockup UI đầy đủ** (heading + 5 card) trùng lặp hoàn toàn với
`cvc_render_goal_direction_section()` thật - giữ quyết định KHÔNG dùng từ
10A.10.

## 3. HERO - ưu tiên số 1, tái cấu trúc thật (không phải thay ảnh)

**Vấn đề gốc**: `.cvc-hero__visual` trước đây là 1 grid item bên trong
`.container` (max-width giới hạn), ảnh chỉ chiếm phần nhỏ trong ô đó với
padding/margin xung quanh - đúng như phản hồi "sticker dán vào khoảng
trống". Chỉ đổi ảnh (10A.10) không sửa được vấn đề bố cục này.

**Tái cấu trúc** (`index.php` + `style.css`):
- `.cvc-hero__visual` chuyển thành **con trực tiếp của `<section
  class="cvc-hero">`**, đứng TRƯỚC `.container` trong DOM (không còn bên
  trong grid 2 cột) - để ở tablet/mobile khi chuyển `position:static` nó
  tự nhiên render phía trên nội dung chữ, không cần CSS `order`.
- Desktop (`min-width:1025px`): `.cvc-hero__visual` chuyển
  `position:absolute; top:0; right:0; bottom:0; width:min(48vw,760px)`
  - bleed thật tới mép phải viewport (vì `.cvc-hero` không có max-width)
  và phủ kín toàn bộ chiều cao hero band (không còn khoảng trắng trên/
  dưới ảnh). Ảnh dùng `object-fit:cover` lấp đầy khối này, bỏ bo góc/
  shadow (không còn đọc như "card ảnh" mà như 1 phần nền thật).
- Dải gradient mờ bên trái ảnh (`::before`, 18% width) hoà mép ảnh vào
  nền sáng của hero thay vì có 1 đường cắt cứng.
- `.cvc-hero__content` được cấp `max-width: 38rem` riêng (trước đây dựa
  vào grid track) để chữ luôn nằm gọn bên trái, không bao giờ chạm ảnh.
- Tablet/mobile (≤1024px): `.cvc-hero__visual` giữ hành vi cũ
  (position tĩnh, ảnh nhỏ căn giữa, bo góc+shadow, ẩn hẳn dưới 640px) -
  không đổi, vẫn đúng.

**Bug phát hiện qua QA (trước khi coi là xong)**: `.cvc-hero-feature-card`
có `bottom: -1.5rem` (cố tình tràn xuống dưới khối ảnh 1 chút để tạo hiệu
ứng nổi, hợp lý khi ảnh chỉ là 1 card nhỏ có nhiều khoảng trắng xung
quanh). Sau khi ảnh bleed tới đúng mép `.cvc-hero` (có `overflow:hidden`),
phần tràn -1.5rem đó bị **cắt cụt** (crop test xác nhận đáy card mất hẳn
bo góc/shadow). Sửa: đổi `bottom: -1.5rem` → `bottom: var(--cvc-space-8)`
(dương, nằm hẳn trong khối ảnh) - vì ảnh giờ đã đủ cao, card không cần
tràn ra ngoài để tạo chiều sâu nữa.

## 4. Course artwork

`cvc_render_course_thumbnail_placeholder()` (không đổi logic từ 10A.10 -
vẫn map theo `course_type`, vẫn fallback SVG) - chỉ thay nội dung ảnh
bằng bản v2 giàu chi tiết hơn (background phối cảnh sâu hơn, ánh sáng tự
nhiên hơn bản 10A.10). Course card component, aspect-ratio 4:3, badge
category thật đè lên - không đổi, vẫn đúng.

## 5. "Wall of cards" - tạo nhịp điệu thị giác

Vấn đề: Hero → Value strip (trắng) → Journey (trắng) → Courses (trắng) -
3 section trắng liên tiếp cùng công thức card khiến mắt "trôi" qua không
phân biệt được ranh giới section.

**Sửa** (`inc/template-tags.php`):
- `cvc_render_learning_journey_section()`: thêm class `cvc-section--tint`
  (nền xanh nhạt có sẵn từ design system, trước đây chỉ dùng cho goal
  section) → chuỗi nền giờ là: Hero(gradient) → Value strip(trắng) →
  Journey(**tint**) → Courses(trắng) → Goal(**tint**) → CTA(xanh đậm) →
  Footer(xanh đậm) - luân phiên rõ ràng thay vì 3 mảng trắng liền nhau.
- Journey tự thân cũng tăng sức nặng: icon circle 56px→64px, nền trắng +
  shadow nhẹ (nổi khối trên nền tint thay vì nền `--primary-tint` cũ dễ
  chìm), đường nối đổi từ 1 line xám mảnh sang dashed line màu primary
  (opacity 0.35) - "connecting line phải rõ, node phải đủ lớn" (Phần 13).

## 6. Recruitment, CTA

Không đổi kiến trúc từ 10A.10 (banner ảnh đầu sidebar, skyline blend sau
CTA banner thật) - chỉ ảnh nguồn được thay bằng bản v2 (cùng tên file nên
tự động áp dụng, không cần sửa code). Thêm hover state cho
`.cvc-recruitment-row` (nền nhạt khi hover, trước đây chưa có feedback
tương tác nào ở đây).

## 7. Micro-interactions (Phần 20)

- Course card artwork: `transform: scale(1.04)` khi hover `.cvc-card`
  (trước đây chỉ có lift+shadow ở card, ảnh bên trong đứng yên).
- Recruitment row: thêm background hover.
- Journey icon: thêm `translateY(-2px)` khi hover (gộp vào rule hover có
  sẵn thay vì tạo rule trùng).
- Toàn site: thêm `@media (prefers-reduced-motion: reduce)` (Phần 20 bắt
  buộc, **chưa từng có** trong toàn bộ codebase trước phase này) - tắt
  animation/transition/scroll-behavior mượt cho người dùng đã bật cài đặt
  hệ thống này.

## 8. Screenshot QA đã chạy (6+ vòng theo Phần 24)

1. Baseline - full-page 1440 + crop hero trước khi sửa gì.
2. Hero/Header - dựng lại `.cvc-hero__visual`, crop `.cvc-hero` full-
   section → phát hiện bug card bị cắt cụt (mục 3) → sửa → crop lại xác
   nhận card nguyên vẹn.
3. Course/Recruitment - crop `.cvc-card-grid`, `.cvc-recruitment-panel`,
   `.cvc-cta-banner` xác nhận ảnh v2 lên đúng, không lệch category,
   không còn text baked-in.
4. Journey/Goal - crop `.cvc-journey-section` xác nhận nền tint + dashed
   line + icon lớn hơn.
5. Full-page 1440 (cuộn hết trang trước khi chụp - đã biết kỹ thuật này
   từ 10A.10 để tránh ảnh `loading="lazy"` chưa kịp tải khi chụp).
6. Responsive: crop `.cvc-hero` tại 1280/1024/768/390/360 - xác nhận
   full-bleed đúng ở desktop, xếp dọc đúng ở tablet/mobile, ảnh ẩn đúng
   dưới 640px (không đổi hành vi cũ).
7. Kiểm tra tràn ngang (`scrollWidth` vs `clientWidth`) tại cả 6 breakpoint
   qua Puppeteer script riêng - **0 tràn** ở mọi breakpoint (rủi ro thật
   khi dùng `vw` cho full-bleed absolute element).
8. Full-page mobile 390 (cuộn hết) - xác nhận journey tint, course/
   recruitment ảnh tải đúng, không vỡ.
9. Console/network sweep (cuộn hết trang, theo dõi console error/
   pageerror/requestfailed/response≥400) - **0 vấn đề**.

## 9. Tự chấm điểm (Phần 25)

| Vùng | Điểm | Ghi chú |
|---|---|---|
| Header | 9/10 | Không đổi (đã đạt từ 10A.8) |
| Hero | 9.5/10 | Tái cấu trúc bố cục thật (full-bleed), không chỉ đổi ảnh - giải quyết đúng gốc rễ phản hồi "chẳng ra sao" |
| Hero artwork | 9.5/10 | Ảnh thật full-bleed, chiều sâu rõ, không còn đọc như sticker |
| Search | 9/10 | Không đổi |
| Value strip | 9/10 | Không đổi (đã đủ nhẹ nhàng, không phải "card" boxy) |
| Journey | 9/10 | Tint band + dashed line + icon lớn hơn - hết đơn điệu |
| Courses | 9.5/10 | Artwork v2 giàu chi tiết hơn hẳn, hover scale nhẹ |
| Recruitment | 9/10 | Banner ảnh + hover feedback mới |
| Goal | 9/10 | Đã có tint từ trước, giờ tương phản rõ hơn nhờ Journey cũng tint (luân phiên đúng nhịp) |
| CTA | 9/10 | Không đổi kiến trúc, ảnh v2 |
| Footer | 9/10 | Không đổi |
| Mobile | 9/10 | Xác nhận sạch qua cuộn thật + kiểm tra tràn ngang |

Không vùng nào dưới 9; Hero và Hero artwork (ngưỡng bắt buộc 9.5) đạt nhờ
tái cấu trúc bố cục thật (full-bleed), không phải chỉ tăng chất lượng ảnh.

## 10. Regression / SEO / Accessibility

- `php -l` cả 2 file sửa, đếm brace `style.css` cân bằng.
- HTTP thật: `/`, `/khoa-hoc/`, `/tuyen-dung/`, `/thi-trac-nghiem/`,
  `/kien-thuc/`, `/van-ban-phap-luat/`, `/tim-kiem/`, `/chu-de/` - toàn
  bộ 200, không warning/fatal.
- `<h1>` vẫn duy nhất; alt text ảnh hero/course/recruitment/CTA đều có
  (rỗng cho ảnh trang trí trong vùng `aria-hidden`, có mô tả cho ảnh
  hero).
- Không tràn ngang ở bất kỳ breakpoint nào (đo `scrollWidth`).
- `prefers-reduced-motion` được tôn trọng toàn site (mới thêm).

## 11. Real data / fixture boundary (không đổi)

Không sửa `inc/homepage-fixtures.php`. `CVC_HOMEPAGE_DEMO_CONTENT` giữ
nguyên ranh giới từ 10A.6 - real data luôn thắng, fixture chỉ bổ sung mật
độ hiển thị ở DEV, luôn có badge "Demo". Không thêm số liệu/testimonial/
review giả nào.

## 12. Git

Nguồn `homepage-assets-incoming/` (đã gitignore từ 10A.10) tiếp tục không
commit. Chỉ commit: `theme/cong-vien-chuc/{index.php,style.css,
inc/template-tags.php}`, `theme/cong-vien-chuc/assets/images/homepage/*`
(ghi đè ảnh course/recruitment/cta bằng bản v2, hero-photo giữ nguyên vì
byte-identical với nguồn cũ), doc này.

## 13. Known limitations

- Ảnh nguồn vẫn nhỏ (course tile ~200-330px từ board 1536px) - đã dùng
  bản v2 giàu chi tiết hơn nhưng vẫn là giới hạn cứng của bộ asset được
  cấp, không phải image chụp thật độ phân giải cao.
- 13/22 file trong `cvc-final/` bị lỗi crop hệ thống (không phải lỗi từ
  phía xử lý của tôi) - không dùng được, đã tự cắt lại từ board tổng thay
  thế, không có gì bị bỏ sót do lỗi này.
- Goal illustration asset tiếp tục không dùng được (mockup UI trùng lặp
  hoàn toàn) - quyết định giữ nguyên từ 10A.10, vẫn đúng ở đây.
