# Phase 10A.7 — Visual Master Final

Vòng refinement thị giác tiếp theo trên nền 10A.6 (đã tốt) - **không
redesign lại**, chỉ inspect + đối chiếu ảnh reference thật + sửa đúng 2
điểm yếu cụ thể còn lại mà các phiên trước chưa chạm tới, xác nhận bằng
screenshot thật qua nhiều vòng lặp (không dừng sau iteration 1).

## 1. Phương pháp

1. Mở lại `docs/ui-benchmark/homepage-reference.png` (Read trực tiếp).
2. Chụp ảnh baseline hiện tại (Docker Puppeteer, giải pháp đã có từ
   10A.5) - so sánh cạnh nhau.
3. Xác định ĐÚNG 2 điểm yếu cụ thể còn lại (không sửa tràn lan những gì
   đã tốt):
   - Course card artwork: cả 3 card (kể cả 2 fixture mới từ 10A.6) dùng
     chung **1 icon mũ tốt nghiệp lặp lại**, chỉ khác màu nền - không
     phản ánh đúng nội dung từng loại khóa học.
   - Hero illustration: vẫn là 1 composition khá phẳng (nhà + mũ + hình
     tròn cộng + hình chữ nhật tối) - thiếu "con người" mà ảnh benchmark
     có (nhân vật chuyên nghiệp chiếm nửa hero).
4. Sửa, chụp lại (iteration 2), phát hiện lỗi mới (silhouette bị floating
   card che khuất), sửa tiếp (iteration 3), chụp lại xác nhận.

## 2. Course Artwork - "scene" riêng theo course_type

`cvc_render_course_thumbnail_placeholder()` trước đó nhận `$color`,
render đúng 1 icon mũ tốt nghiệp cho mọi loại khóa học. Đổi chữ ký nhận
thêm `$course_type`, chọn 1 trong 5 "scene" SVG nhiều lớp (không chỉ 1
icon glyph đơn):

| course_type | Scene | Ý nghĩa |
|---|---|---|
| `exam_prep` | Bìa đề thi + dấu tick tròn | Ôn thi |
| `skill` | 2 bong bóng chat | Kỹ năng giao tiếp |
| `professional` | Màn hình laptop + biểu đồ | Nghiệp vụ/dữ liệu |
| `orientation` | La bàn | Định hướng nghề nghiệp |
| (khác/`null`) | Sách + mũ tốt nghiệp | Mặc định, giữ tương thích |

Mỗi scene có 1 hình khối nền mờ (depth) + glyph chính, dùng
`currentColor`/`#fff` phối với nền gradient theo `cvc_course_type_color()`
đã có từ trước (không tạo hệ màu mới).

## 3. Hero Illustration - thêm nhân vật, sửa lỗi bị che

**Vòng 1**: thêm silhouette 1 người chuyên nghiệp (bán thân, cách điệu -
không dùng ảnh chụp vì không có asset ảnh rõ bản quyền, xem
`PHASE_10A_UI_UX.md §9`) cầm tài liệu/tablet, đặt bên phải tòa nhà, cùng
2-3 vòng tròn trang trí tạo chiều sâu, cờ Tổ quốc, bóng đổ mặt đất.

**Lỗi phát hiện qua screenshot thật**: đặt nhân vật ở x≈300-430 (viewBox
480) khiến phần đầu bị `.cvc-hero-feature-card` (floating card, CSS
`position:absolute; right:0; bottom:-1.5rem; width:15rem`) che gần hết -
chỉ còn thấy 1 chỏm tóc. Chụp ảnh crop riêng vùng `.cvc-hero__visual`
(dùng `page.$(selector).screenshot()` của Puppeteer) để xác nhận lỗi rõ
ràng trước khi sửa.

**Vòng 2 (sửa)**: bố cục lại toàn bộ - nhân vật chuyển hẳn sang bên
**trái** (x tối đa ~155/480, đảm bảo về mặt hình học không bao giờ chạm
vùng floating card dù ở chiều cao nào, vì card chỉ chiếm ~50% chiều rộng
bên phải), tòa nhà dịch sang phải (x170-430), cờ + mũ tốt nghiệp đặt phía
trên/giữa làm điểm nhấn nổi. Bỏ 2 icon trang trí ý nghĩa không rõ ràng
(hình tròn xanh lá dấu cộng, hình tròn tài liệu) để tránh chồng lấn thêm -
ưu tiên 4 yếu tố rõ nghĩa (người, tòa nhà, cờ, mũ tốt nghiệp) hơn nhiều
yếu tố chồng chéo.

Xác nhận lại bằng screenshot crop riêng `.cvc-hero__visual` sau khi sửa -
nhân vật hiển thị đầy đủ, không bị che, cân đối với tòa nhà.

## 4. Recruitment avatar - thêm biến thể màu

Tương tự tinh thần "richer visual" nhưng vẫn trung thực (Phần 21 - "có
thể dùng visual agency placeholder nhưng KHÔNG giả làm logo chính thức"):
avatar chữ cái đầu tên cơ quan giờ xoay vòng qua 4 màu (blue/teal/amber/
purple) dựa trên hash tên cơ quan - chỉ tạo nhịp thị giác giữa các dòng,
không mang ý nghĩa phân loại, không giả làm logo thật.

## 5. Screenshot QA - các vòng lặp thực tế đã chạy

Dùng nguyên giải pháp Docker + Puppeteer từ Phase 10A.5
(`docs/PHASE_10A.5_CONVERSION_UX.md §1`), bổ sung 1 kỹ thuật mới: chụp
**crop đúng 1 phần tử DOM** (`page.$(selector).screenshot()`) thay vì
toàn trang - hữu ích để soi kỹ 1 khu vực nhỏ (hero visual) mà không phải
đọc lại toàn bộ ảnh full-page.

- Iteration 1: sửa course artwork → chụp 1440px → xác nhận 3 card có
  scene khác nhau.
- Iteration 2: thêm nhân vật vào hero → chụp full-page 1440px → phát
  hiện nhân vật gần như biến mất sau floating card.
- Iteration 3: bố cục lại hero (người sang trái) → chụp crop riêng
  `.cvc-hero__visual` → xác nhận nhân vật hiển thị đủ, không bị che →
  chụp lại full-page 1440px để xem tổng thể → chụp tablet 768px (viewport,
  không cuộn) → xác nhận minh họa co lại đúng, không tràn, không còn
  floating card ở breakpoint này (đã ẩn từ trước, không đổi).

## 6. QA khác

- PHP lint toàn bộ theme, CSS brace-balance, `git diff --check` sạch.
- HTTP thật cho toàn bộ trang public + dashboard đã đăng nhập
  (`/`, `/khoa-hoc/` +slug, `/tuyen-dung/` +filter, `/thi-trac-nghiem/`
  +slug, `/kien-thuc/`, `/chu-de/`, `/van-ban-phap-luat/`, `/tim-kiem/`,
  `/dang-nhap/`, `/dang-ky/`, `/tai-khoan/` + `muc-tieu`) - toàn bộ 200,
  0 PHP warning/fatal.
- Xác nhận qua HTML: 3 course card render 3 SVG scene khác nhau (grep
  path data riêng của từng scene), 4 dòng recruitment có 3/4 màu avatar
  khác nhau (teal/purple/amber/purple - phân bố tự nhiên theo hash tên
  cơ quan thật, không cưỡng ép đều 4 màu).

## 7. Known limitations (không đổi từ các phiên trước)

- Vẫn không có ảnh chụp thật (photography) cho hero/course - toàn bộ là
  SVG minh họa, do không có asset ảnh rõ bản quyền hợp lệ để dùng (Phần 6
  của chính prompt 10A.7 cũng cấm "ảnh không rõ nguồn gốc bản quyền").
  Đây là lựa chọn nhất quán xuyên suốt từ 10A.2, không phải bỏ sót.
- Statistics strip vẫn hoàn toàn là design fixture (từ 10A.6), gated bởi
  `CVC_HOMEPAGE_DEMO_CONTENT`.
- Footer vẫn 2 cột thật (không thêm cột "Hỗ trợ"/social không có đích
  thật) - nhất quán với quyết định ở 10A.6.
