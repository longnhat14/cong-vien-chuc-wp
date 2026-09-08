# Phase 10A.9 — Visual Reconstruction (Hero Illustration & Course Artwork)

## 0. Bối cảnh và lý do làm lại

Sau 10A.8 (thêm gradient/shadow lên các hình khối phẳng), người dùng phản
hồi trực tiếp và dứt khoát: cách tiếp cận "homepage đã đẹp, chỉ cần polish
CSS" **không đủ**. Vấn đề gốc không phải border/shadow/spacing mà là art
direction: hero vẫn đọc như "building icon + person silhouette + flat
SVG" (nguyên văn bị cấm dùng lại), course artwork vẫn là "icon glyph đổi
màu" chứ chưa phải visual storytelling riêng cho từng category.

Phase này KHÔNG redesign layout, KHÔNG thêm section, KHÔNG chạm backend/
API/auth - chỉ tái tạo lại 2 phần nội dung trực quan cụ thể: hero
illustration và course category artwork, bằng kỹ thuật SVG nhiều lớp thật
sự (không chỉ thêm gradient lên hình cũ).

**Giới hạn kỹ thuật không đổi**: môi trường này không có công cụ tạo ảnh
(image generation). Lựa chọn khả thi duy nhất là đẩy kỹ thuật SVG/vector
composition lên mức cao hơn hẳn - nhiều lớp (layer), phối cảnh 3 mặt,
ánh sáng/bóng đổ nhất quán, bối cảnh môi trường (sky/cloud/tree/plaza) -
chứ không phải ảnh chụp/illustration editorial thật.

## 1. Phương pháp

1. Chụp baseline crop `.cvc-hero__visual` (1440px) TRƯỚC khi sửa gì -
   `hero-109-before.png` - để có căn cứ so sánh trước/sau khách quan.
2. Đọc lại toàn bộ mã nguồn `cvc_render_hero_illustration()` và
   `cvc_render_course_thumbnail_placeholder()` để nắm chính xác cấu trúc
   và các ràng buộc bố cục đã có từ 10A.7/10A.8 (đặc biệt: nhân vật phải
   nằm trong "vùng an toàn" x≤~160/480 để không bao giờ bị
   `.cvc-hero-feature-card` (position absolute, right/bottom) che khuất,
   dù ở chiều cao viewport nào).
3. Viết lại hoàn toàn 2 hàm trên với composition nhiều lớp hơn hẳn.
4. Chụp lại crop `.cvc-hero__visual` và `.cvc-card-grid` sau khi sửa, đối
   chiếu trực tiếp với ảnh trước và với `docs/ui-benchmark/homepage-reference.png`.
5. Chụp full-page 1440px và 390px (mobile) để xác nhận không có gì vỡ ở
   các section còn lại (statistics, journey, goal, CTA, footer).
6. QA kỹ thuật: `php -l`, đếm brace CSS, `git diff --check`, HTTP thật
   toàn bộ trang public.

## 2. Hero illustration - tái tạo hoàn toàn

Bản 10A.8 (đã bị từ chối): 1 khối nhà hình tam giác + chữ nhật fill
gradient phẳng, 1 silhouette người, 1 lá cờ, 1 mũ tốt nghiệp - tổng cộng
đúng như người dùng mô tả "5 rectangle + 3 circle + 1 person silhouette".

Bản 10A.9 - `cvc_render_hero_illustration()` viết lại với các lớp mới:

- **Bầu trời + ánh sáng**: `<rect>` nền gradient sky (`cvcSkyGradient`,
  xanh nhạt → trắng), 1 vùng sáng mặt trời góc trên phải
  (`cvcSunGlow`, radial gradient vàng nhạt mờ dần), 2 hình mây bo tròn.
- **Bối cảnh môi trường**: nền quảng trường phối cảnh (hình thang, 2 vạch
  lát sáng) thay vì chỉ 1 ellipse bóng đổ; 2 cụm cây cách điệu (thân +
  3 tán lá chồng lớp, 2 tông xanh) đặt hai bên tòa nhà - tạo bối cảnh
  thật thay vì hình nổi trên nền trắng.
- **Tòa nhà - khối 3 mặt thật (không phải 1 icon nhà)**: thêm hẳn 1 mặt
  hông (`cvcSideGradient`) và 1 mái hông (`cvcRoofSideGradient`, tối màu
  hơn mặt trước) tạo phối cảnh isometric-nhẹ có chiều sâu thật, thay vì
  1 mặt phẳng nhìn thẳng. Mặt tiền có hàng 5 cột (mỗi cột 2 lớp
  sáng/tối để tạo khối trụ), dải mái phân cách, cửa chính hình khối, bậc
  thềm 2 lớp. Mái trước vẫn giữ gradient sáng→đậm của 10A.8 nhưng giờ có
  mặt mái hông đối lập màu để mắt đọc được đúng là 1 khối 3D chứ không
  phải 1 tam giác dán phẳng.
- **Người công chức/viên chức - refine, vẫn trong vùng an toàn bên trái**:
  thêm ve áo vest 2 tông (`cvcSuitLapelGradient` sáng hơn thân áo) tạo
  khối rõ trên ngực, thêm thẻ công chức đeo ngực (rectangle vàng viền nâu
  + chấm sáng - gợi ảnh thẻ/badge thật), tóc chỉnh lại hình dáng tự nhiên
  hơn + 2 nét tai nhỏ. Toạ độ tuyệt đối của người vẫn ≤ ~162 (kiểm tra lại
  bằng crop screenshot - xem mục 4), không đổi ràng buộc đã xác lập từ
  10A.7.
- **Mũ tốt nghiệp**: giữ nguyên vị trí/gradient từ 10A.8, thêm tua dây +
  hạt tua (tassel) nhỏ cho chi tiết, không đổi bố cục.
- **Filter**: `cvcSoftShadow` (feDropShadow) tăng nhẹ độ lan tỏa
  (stdDeviation 8→10) cho phù hợp với khối nhà lớn hơn.

## 3. Course artwork - viết lại cả 5 scene

`cvc_render_course_thumbnail_placeholder()` (dùng theo `course_type` thật
từ API, không phải chọn ngẫu nhiên) - mỗi scene giờ kể 1 câu chuyện hình
ảnh riêng, nhiều lớp hơn hẳn bản 10A.7 (vốn chỉ 1 icon glyph + 1-2 vòng
tròn nền mờ):

- **`exam_prep` (Ôn thi)**: tờ đề thi có tiêu đề + 3 dòng câu hỏi, ĐI KÈM
  1 đồng hồ bấm giờ có kim + tâm - kể đúng câu chuyện "luyện đề có giới
  hạn thời gian", không chỉ 1 dấu tick đơn lẻ như bản cũ.
- **`skill` (Kỹ năng)**: 2 người cách điệu đối diện nhau (khác kích thước/
  độ đậm để tạo chiều sâu), 1 mũi tên trao đổi ở giữa, 1 đường biểu đồ
  tăng trưởng nhỏ phía dưới - kể câu chuyện "trao đổi giúp tiến bộ" thay
  vì chỉ 1 bong bóng chat.
- **`professional` (Chuyên môn)**: laptop với màn hình có biểu đồ cột +
  đường xu hướng + con trỏ, một bảng dữ liệu nhỏ nổi phía sau (như 1 cửa
  sổ spreadsheet khác) - rõ ràng là "làm việc với số liệu" chứ không phải
  1 màn hình chung chung.
- **`orientation` (Định hướng)**: la bàn (2 vòng kim + kim chỉ hướng) ĐI
  KÈM 1 con đường chấm nét có cột mốc và lá cờ đích ở cuối - kể câu
  chuyện "có lộ trình rõ ràng để đi tới", không chỉ 1 la bàn tĩnh.
- **`default` (kiến thức chung/nền tảng)**: sách mở 2 trang có dòng kẻ,
  ĐI KÈM 1 búa gavel nghiêng + dải ruy băng nhỏ - gợi "kiến thức, quy
  định, văn bản" thay vì chỉ 1 mũ tốt nghiệp đơn lẻ.

Mỗi scene giữ nguyên cơ chế `currentColor` (ăn theo màu category có sẵn từ
`cvc_course_type_color()`) nên vẫn tự động đổi tông theo đúng 4 màu design
system, không cần thêm code riêng ở nơi gọi.

## 4. Screenshot QA đã chạy (bằng chứng trước/sau)

1. `hero-109-before.png` - crop `.cvc-hero__visual`, bản 10A.8 (flat).
2. Viết lại `cvc_render_hero_illustration()`.
3. `hero-109-round1.png` - crop lại cùng selector - xác nhận: tòa nhà có
   mặt sáng/tối phân biệt rõ (đọc đúng là khối 3D), có mây/cây/quảng
   trường, người có thẻ + ve áo 2 tông. Không còn đọc như "1 icon nhà +
   1 silhouette".
4. `home-109-hero-context.png` - viewport 1440x1000 (không cắt) - xác
   nhận hero mới ăn khớp với text/CTA/floating card xung quanh, không vỡ
   layout, floating card vẫn hiển thị đủ 4 dòng trust-signal như trước.
5. Viết lại `cvc_render_course_thumbnail_placeholder()`.
6. `course-grid-109.png` - crop `.cvc-card-grid` - xác nhận cả 3 course
   card thật đang hiển thị trên trang chủ (Ôn thi/Kỹ năng/Chuyên môn) đều
   có scene riêng biệt, rõ ràng khác nhau về câu chuyện hình ảnh, không
   phải cùng 1 hình đổi màu.
7. `home-109-full.png` - full-page 1440px - xác nhận statistics, learning
   journey, goal direction, resource hub, recruitment sidebar, CTA banner,
   footer đều không bị ảnh hưởng, không có gì vỡ.
8. `home-109-mobile.png` - full-page 390px - xác nhận mobile layout không
   vỡ. Ghi nhận: `.cvc-hero__visual` bị ẩn dưới 640px
   (`style.css` dòng ~3002, `display: none`) - đây là hành vi có chủ đích
   từ phase trước (ưu tiên text/CTA trên màn hình nhỏ), không phải do
   thay đổi lần này, giữ nguyên không đổi.

## 5. Tự chấm điểm (theo yêu cầu Phần checklist)

| Vùng | Trước (10A.8) | Sau (10A.9) | Ghi chú |
|---|---|---|---|
| Hero / Imagery | ~6/10 | ~8.5/10 | Không còn "building+person icon phẳng" - có khối 3 mặt, môi trường, chi tiết người. Chưa đạt ảnh chụp thật (giới hạn kỹ thuật, không có image-gen). |
| Course artwork | ~6/10 | ~9/10 | Mỗi category có visual story riêng, không trộn phong cách, nhất quán tông màu category. |
| Header/Typography | 9/10 | 9/10 | Không đổi trong phase này (đã đạt từ 10A.8, không phải trọng tâm). |
| Goal/Journey/Resource/CTA | 8.5/10 | 8.5/10 | Không đổi - đã xác nhận không vỡ khi hero/course card đổi. |
| Mobile | 8/10 | 8/10 | Không đổi hành vi ẩn hero trên mobile; course card/goal/journey vẫn responsive tốt. |

Vùng dưới 9 (Hero/Imagery) là giới hạn kỹ thuật đã nêu rõ ở mục 0, không
phải thiếu nỗ lực - đã đẩy SVG lên mức nhiều lớp/phối cảnh/môi trường thật
sự, mức trần khả thi khi không có image-generation.

## 6. Ranh giới fixture/real-data (không đổi)

Không thêm/sửa bất kỳ fixture nào trong `inc/homepage-fixtures.php` -
phase này chỉ đổi cách VẼ artwork trang trí (hero + course placeholder),
không đổi dữ liệu gì. Course card thật vẫn ưu tiên `thumbnail_url` thật
nếu API trả về; artwork SVG chỉ dùng khi không có ảnh thật, giữ đúng logic
đã có từ 10A.7.

## 7. QA kỹ thuật khác

- `php -l theme/cong-vien-chuc/inc/template-tags.php` - không lỗi.
- Đếm brace `style.css` - cân bằng (không sửa file này trong phase này).
- `git diff --check` - sạch, không trailing whitespace/conflict marker.
- HTTP thật: `/`, `/khoa-hoc/`, `/tuyen-dung/`, `/tim-kiem/`, `/kien-thuc/`,
  `/van-ban-phap-luat/` - tất cả 200, không có PHP warning/fatal trong log.

## 8. Không đổi / không chạm (theo đúng ràng buộc phase)

- Không migration, không API redesign, không đổi auth.
- Không thêm forum/chat/testimonial/review/fake community/fake user
  counter.
- Không đổi vị trí/kích thước tổng thể của bất kỳ section nào - chỉ đổi
  nội dung vẽ bên trong hero illustration và course placeholder.
- `CVC_HOMEPAGE_DEMO_CONTENT` giữ nguyên logic/ranh giới từ 10A.6.

## 9. Known limitations

- Vẫn không có ảnh chụp thật/illustration editorial thật - môi trường
  không có công cụ image-generation. Đã bù bằng SVG nhiều lớp/phối cảnh/
  bối cảnh môi trường thay vì chỉ thêm gradient lên hình cũ (khác biệt
  thực chất so với 10A.8, không phải polish thêm). Chưa từng dùng ảnh
  benchmark để "chép" pixel-by-pixel vì đó là ảnh tham khảo phong cách,
  không phải asset được cấp quyền dùng trực tiếp.
- Các known limitations khác (statistics fixture, footer 2 cột, không có
  Community section) giữ nguyên từ 10A.6-10A.8.
