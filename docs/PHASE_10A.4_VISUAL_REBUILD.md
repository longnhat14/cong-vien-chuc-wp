# Phase 10A.4 — Pixel-Accurate Premium Homepage Rebuild

Rebuild trực tiếp từ ảnh master `docs/ui-benchmark/homepage-reference.png`
(mở bằng Read, phân tích từng vùng, không suy diễn qua mô tả chữ). Ảnh
này đã được cập nhật giữa phiên 10A.3 và 10A.4 - nội dung hiện tại **chi
tiết và đầy đủ hơn hẳn** bản dùng ở 10A.3 (thêm Statistics strip, floating
hero card, Goal section, Community section) - tài liệu này thay thế phần
kiến trúc homepage đã mô tả ở `PHASE_10A_UI_UX.md`/`PHASE_10A_UI_UX.md §11`.

> Lưu ý: prompt yêu cầu mở file `docs/ui-benchmark/home-page-congvienchuc1.png`
> - file này không tồn tại. File thực tế tại `docs/ui-benchmark/homepage-reference.png`
> đã được cập nhật đúng lúc với nội dung khớp mô tả trong prompt (Statistics
> strip, Goal section, Community section...) nên đã dùng file này làm
> reference, xác nhận qua đối chiếu nội dung.

## 1. Phân tích ảnh reference (từng vùng)

| Vùng | Mô tả |
|---|---|
| A. Header | Trắng, logo mark xanh (tòa nhà) + "CÔNG VIÊN CHỨC" + tagline nhỏ. Nav giữa (không icon rõ). Active = pill nền xanh nhạt + chữ xanh đậm + underline. Phải: search icon, chuông thông báo (có badge đỏ), Đăng nhập (primary), Đăng ký (outline). |
| B. Hero | 2 cột. Trái: eyebrow pill, H1 2 dòng (dòng 2 màu xanh), subtitle, search box lớn + "Tìm kiếm" (button liền), hàng "Phổ biến:" + 4 tag pill. Phải: ảnh thật (nhân vật + tòa nhà công sở + cờ VN) + quote nhỏ + **card nổi góc dưới-phải** (4 dòng feature mini, mỗi dòng icon tròn màu riêng). |
| C/D. Value + Stats | 1 khối bo viền, 2 hàng: hàng 1 = 5 feature (icon+title+desc, không số); hàng 2 = 5 **số liệu** (10.000+, 500+, 200+, 50.000+, 98%). |
| E. Featured Courses | Icon+"Khóa học nổi bật"+subtitle, "Xem tất cả". 3 card lớn, badge flag (Bán chạy/Mới/Miễn phí) **góc trên** ảnh, title, desc, meta (🕐 bài học · ⏱ tuần), nút đặc full-width "Đăng ký học →"/"Học miễn phí →". |
| F. Recruitment | Sidebar phải, list compact 5 dòng: icon cơ quan, title, agency, 📍 địa điểm, 📅 hạn nộp, badge trạng thái. |
| G. Goal section | "Bạn đang hướng đến điều gì?" nền xanh nhạt. 5 card nhỏ (icon+label). Bên phải: card CTA "Chưa biết bắt đầu từ đâu?" + nút "Tạo mục tiêu ngay" + minh họa target/dart. |
| H. Resource hub | "Tài nguyên hữu ích" - 3 card (Chủ đề ôn tập/Cẩm nang kiến thức/Văn bản pháp luật), icon+title+desc+"Khám phá →". |
| I. Community | "Cộng đồng học tập" sidebar phải cạnh Resource hub - list 3 "thảo luận" có avatar + tiêu đề + "X trả lời · Y trước". |
| J. Final CTA | Nền ảnh + overlay xanh đậm. Eyebrow pill, H2 2 dòng (chữ cuối màu xanh sáng), đoạn mô tả, 2 nút (đặc trắng + outline trắng), quote bên phải. |
| K. Footer | Xanh navy đậm, 4 cột: Brand, "Khám phá" (nav thật), "Hỗ trợ" (Hỏi đáp/Hướng dẫn/Chính sách/Điều khoản/Liên hệ), "Kết nối" (social icon + form newsletter email). |

## 2. Nguyên tắc tách Visual / Data (Phần 1)

Đã rebuild toàn bộ bằng PHP/HTML/CSS/SVG thật - **không** nhúng ảnh
reference vào trang, không dùng ảnh làm background giả lập. Ảnh chỉ dùng
để đọc/đối chiếu trong quá trình phát triển.

Một số vùng trong ảnh chứa **data không có thật ở backend** - xử lý theo
đúng Phần 14/20/29 (KHÔNG COPY, ẩn hẳn hoặc thay bằng nội dung thật):

### 2.1 Statistics strip (10.000+, 500+, 200+, 50.000+, 98%) → **ĐÃ ẨN**

Không có bất kỳ API/aggregate nào ở Laravel cung cấp các số liệu quy mô
kiểu này, và số liệu THẬT trên DEV hiện quá nhỏ (1 course, 3 topics, 0
recruitment published, 1 exam, 0 knowledge, 0 legal) để trình bày dưới
dạng "stat card" without gây hiểu lầm nghiêm trọng hơn cả việc ẩn hẳn -
hiển thị "1+" trong 1 card thiết kế để truyền tải quy mô ấn tượng sẽ tự
mâu thuẫn về mặt UX. Phần 14 cho phép rõ: "ẩn statistics row HOẶC thay
bằng value proposition thật" - đã chọn **ẩn hẳn**, giữ nguyên Value strip
(đặc điểm sản phẩm, không phải số liệu) trong 1 panel viền bo tròn.

### 2.2 Community section ("Cộng đồng học tập", thảo luận + avatar) → **ĐÃ BỎ**

Backend hiện KHÔNG có tính năng hỏi đáp/thảo luận/forum (đã kiểm tra toàn
bộ route API - chỉ có courses/exams/recruitments/knowledge/topics/legal/
goals/bookmarks/notifications/recommendations/matching). Phần 20 quy định
rõ: "Nếu chưa có API thật: KHÔNG fake discussion... hoặc bỏ section."
Đã bỏ hẳn - Resource hub trở thành 1 hàng full-width 3 card thay vì
2 cột (Resource + Community) như ảnh.

### 2.3 Course card flag "Bán chạy" (bestseller) → **ĐÃ THAY BẰNG "Mới" (derived thật)**

Không có field enrollment/sales count để tính "bán chạy" thật. Thay bằng
"Mới" - tính THẬT từ `published_at` (course publish trong 30 ngày gần
nhất, xem `cvc_course_is_new()`), không phải flag admin tự gõ tay và
không phải suy đoán.

### 2.4 Footer cột "Hỗ trợ" + "Kết nối" (social, newsletter) → **ĐÃ BỎ**

Không có trang Hỏi đáp/Hướng dẫn sử dụng/Chính sách bảo mật/Điều khoản/
Liên hệ nào tồn tại thật trong WordPress (chưa có Page nào được tạo cho
các nội dung này) - tạo link tới sẽ là **dead link** (Phần 18 cấm rõ).
Không có tài khoản social media hay endpoint newsletter thật (Phần 22
cấm rõ "không tạo newsletter form nếu backend không xử lý"). Footer rút
còn 2 cột thật: Brand (logo+tagline+mô tả) và "Khám phá" (nav domain thật:
Khóa học/Chủ đề/Tuyển dụng/Kiến thức/Thi trắc nghiệm/Văn bản pháp luật).

### 2.5 Goal section - 5 hướng mục tiêu → **giữ, nhưng trỏ route thật**

Không có taxonomy "goal type" khớp chính xác 5 nhãn (Thi công chức/Thi
viên chức/Thi thăng hạng/Bồi dưỡng nghiệp vụ/Nâng cao kỹ năng) ở backend.
Thay vì bỏ cả section hoặc bịa taxonomy giả, mỗi thẻ trỏ tới **route/filter
thật gần nghĩa nhất đã tồn tại**:

| Thẻ | Đích | Vì sao là "thật" |
|---|---|---|
| Thi công chức | `/tuyen-dung/?recruitment_type=civil_servant` | `recruitment_type` là enum thật, validate ở `RecruitmentController::index()` |
| Thi viên chức | `/tuyen-dung/?recruitment_type=public_employee` | như trên |
| Thi thăng hạng | `/thi-trac-nghiem/` | Trang danh sách đề thi thật |
| Bồi dưỡng nghiệp vụ | `/khoa-hoc/` | Trang danh sách khóa học thật |
| Nâng cao kỹ năng | `/kien-thuc/` | Trang kiến thức thật |

CTA "Tạo mục tiêu ngay" trỏ `/tai-khoan/muc-tieu/` (flow Goal thật, Phase
10) - redirect sang đăng nhập kèm intended destination nếu chưa đăng nhập
(`cvc_require_login()` có sẵn).

### 2.6 Popular search tags ("Phổ biến: ...") → giữ, đổi nhãn

Không có analytics "tìm kiếm phổ biến" thật. Đổi nhãn từ "Phổ biến:"
sang **"Tìm kiếm nhanh:"** - shortcut soạn sẵn trỏ `/tim-kiem/?q=...`
(API search thật), không ngụ ý đây là số liệu thống kê (Phần 12/31).

## 3. Component mới (`inc/template-tags.php`)

- `cvc_render_hero_quick_search_tags()` - 4 tag "Tìm kiếm nhanh".
- `cvc_render_hero_feature_card()` - card nổi 4 dòng feature trong hero.
- `cvc_course_is_new()` + badge "Mới" - derived thật từ `published_at`.
- `cvc_render_course_card_featured()` - bố cục ngang lớn khi **đúng 1**
  course thật (Phần 15/29 "ONE ITEM → featured composition") - tránh 1
  card nhỏ lọt thỏm trong lưới 3 cột.
- `cvc_render_goal_direction_section()` - toàn bộ section G (5 card +
  CTA card), tự chứa (giống pattern `cvc_render_value_strip()`).
- `cvc_render_homepage_cta_banner()` - viết lại: eyebrow, H2 2 tông màu,
  đoạn mô tả, 2 nút (đặc + outline), quote bên phải.
- Badge overlay course card chuyển từ **góc dưới** (10A.3) sang **góc
  trên** ảnh (đối chiếu ảnh benchmark thật §E).

## 4. Kiến trúc homepage (bản hiện tại)

1. Hero (search + quick-search tags + floating feature card).
2. Value panel (bo viền, không có statistics row - xem §2.1).
3. 2 cột: Khóa học nổi bật (featured/grid tùy số lượng thật) + Resource
   hub (3 card, full-width) | Tuyển dụng mới nhất (sidebar).
4. Goal direction section (nền tint xanh).
5. CTA banner.

Vẫn đúng 6 lệnh gọi API domain (`per_page=1` cho topic/knowledge/legal vì
chỉ cần `total`), không tăng số request so với 10A.3.

## 5. Responsive

- ≥1025px: hero 2 cột đầy đủ + card nổi hiển thị.
- 641-1024px: hero 1 cột, minh họa co lại lên trên; **card nổi ẩn** (15rem
  rộng hơn minh họa đã co 280px, tránh tràn).
- ≤640px: hero minh họa ẩn hẳn (ưu tiên text+search); goal-direction-grid
  2 cột; resource/value-strip xuống hàng dọc; CTA banner + footer stack.
- Course featured card: ngang ở desktop, dọc (ảnh trên, nội dung dưới) ở
  ≤720px.

## 6. Accessibility

- Card nổi hero (`cvc-hero-feature-card`) **không** đánh dấu
  `aria-hidden` toàn bộ (có text thật, không phải thuần trang trí) - chỉ
  icon con đánh dấu `aria-hidden`.
- Goal direction card, resource card, tag: đều là `<a>` thật (không phải
  `<div>` giả link).
- 1 `<h1>` duy nhất trên homepage (đã verify qua HTTP thật).

## 7. QA đã thực hiện

**Không có screenshot pixel thật** - đã thử lại `npx playwright install
chromium` (tải được binary) nhưng launch thất bại vì thiếu system
dependencies (`libnss3`, `libasound2`...); `sudo apt-get install`/
`playwright install --with-deps` đều bị chặn vì môi trường sandbox không
cấp quyền `sudo` (không có mật khẩu, và policy chặn hẳn lệnh `sudo`).
Đây là giới hạn môi trường, không phải bỏ qua bước QA - đã bù bằng:

- Đối chiếu TỪNG vùng ảnh reference với HTML thực tế trả về qua `curl`
  (kiểm tra đúng class, đúng nội dung, đúng thứ tự section).
- Regression toàn bộ trang public (`/`, `/tuyen-dung/` +filter, `/thi-trac-nghiem/`
  +slug, `/khoa-hoc/` +slug, `/kien-thuc/`, `/chu-de/`, `/van-ban-phap-luat/`,
  `/tim-kiem/`, `/dang-nhap/`, `/dang-ky/`) và dashboard đã đăng nhập
  (`/tai-khoan/`, `/tai-khoan/muc-tieu/`, `/tai-khoan/dau-trang/`) - toàn
  bộ 200, 0 PHP warning/fatal, kể cả sau khi thêm filter
  `?recruitment_type=civil_servant` mới dùng ở Goal section.
- PHP lint toàn bộ theme + CSS brace-balance sau mỗi lần sửa lớn.
- `git diff --check` sạch.

**Khuyến nghị bắt buộc trước khi coi Phase 10A hoàn tất 100%**: người
dùng tự mở `http://localhost:8080` bằng trình duyệt thật ở 1440/1280/
1024/768/390/360, so trực tiếp với `docs/ui-benchmark/homepage-reference.png`,
vì đây là bước QA quan trọng nhất mà môi trường này không thể tự thực
hiện được.

## 8. Known limitations

- Không có screenshot browser thật (xem §7).
- Hero vẫn dùng minh họa SVG cho phần bên trong khung, không phải ảnh
  chụp thật như trong reference (không có ảnh bản quyền hợp lệ để dùng -
  xem `PHASE_10A_UI_UX.md §9`).
- Statistics strip và Community section bị bỏ hoàn toàn (xem §2.1, §2.2) -
  nếu sau này backend có real aggregate stats API hoặc tính năng Q&A
  thật, có thể thêm lại đúng vị trí này.
- Footer rút còn 2 cột (thay vì 4 trong ảnh) vì 2 cột còn lại không có
  trang/tài khoản thật để trỏ tới (xem §2.4).
