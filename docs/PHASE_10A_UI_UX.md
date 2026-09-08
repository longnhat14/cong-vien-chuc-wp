# Phase 10A.2/10A.3 — Premium Product UI / Visual Benchmark

Nâng cấp thị giác toàn bộ frontend từ "sơ sài, giống bản demo API" lên
"sản phẩm chuyên nghiệp". Không đổi kiến trúc, không đổi API contract,
không thêm dữ liệu giả.

**10A.2** (đợt 1) làm theo benchmark mô tả bằng chữ (chưa có file ảnh).
**10A.3** (đợt 2, §11) nhận được file ảnh thật tại
`docs/ui-benchmark/homepage-reference.png` và đối chiếu lại trực tiếp -
phát hiện vài khác biệt cấu trúc so với suy đoán ở 10A.2 (quan trọng nhất:
Tuyển dụng là 1 sidebar dọc chạy song song với Khóa học, KHÔNG phải section
riêng; không có section "Thi trắc nghiệm" riêng biệt) và đã sửa lại đúng
theo ảnh. Phần §1-10 dưới đây là tài liệu gốc của 10A.2 (vẫn đúng phần lớn
- design tokens, component nâng cấp toàn site, accessibility fix... không
đổi); §11 ghi lại riêng những gì 10A.3 sửa lại sau khi có ảnh thật.

## 1. Benchmark & design direction

Benchmark cung cấp là mô tả chi tiết bằng chữ (không phải file ảnh) cho
homepage: header trắng có logo + tagline, hero 2 cột (text+search trái,
minh họa phải), value strip ngang, course card có ảnh, recruitment module
compact, exam accent đậm, resource hub gộp Chủ đề/Kiến thức/Văn bản, CTA
banner xanh đậm cuối trang. Đã bám theo đúng cấu trúc này, tự quyết định
phần thị giác cụ thể (màu sắc chính xác, minh họa SVG, tỷ lệ) vì không có
ảnh để đối chiếu pixel.

Tinh thần: tin cậy, chuyên nghiệp, tri thức, phát triển nghề nghiệp - màu
chủ đạo xanh dương (đã có sẵn từ Phase 1), mở rộng thêm 1 tông xanh đậm
(`--cvc-color-primary-deep`) làm accent mạnh cho section Thi trắc nghiệm
và CTA banner.

## 2. Design tokens (mở rộng, không tạo hệ mới)

`style.css` `:root` giữ nguyên toàn bộ token Phase 1-4B, chỉ **thêm**:

- `--cvc-color-primary-deep`, `--cvc-color-primary-tint-strong`, các
  accent màu (`green`/`amber`/`red`/`purple`) cho category/status - dùng
  tiết chế, không biến trang thành nhiều màu hỗn loạn (Phần 7).
- `--cvc-text-h1/h2/h3/lead` bằng `clamp()` - typography tự responsive
  theo viewport, không cần override riêng ở mỗi breakpoint (Phần 11).
- `--cvc-space-16/20`, `--cvc-section-py(-lg)` - nhịp section nhất quán
  (Phần 12).
- `--cvc-radius-lg`, `--cvc-shadow-md/lg` - card/panel cao cấp hơn.
- `--cvc-container-width` 1140px → 1240px (khớp benchmark "1200-1280px").

`h1/h2/h3` toàn site tự động lớn hơn (đây là fix quan trọng nhất cho vấn
đề "typography nhỏ" - áp dụng cho MỌI trang cùng lúc, không cần sửa từng
template).

## 3. Component nâng cấp (áp dụng toàn site, không riêng homepage)

Vì các trang domain (recruitment/course/exam detail, listing...) đều tái
dùng chung `.cvc-btn`, `.cvc-card`, `.cvc-badge`, `.cvc-state--*` từ
`style.css`, nâng cấp các class nền tảng này giúp **toàn bộ site đạt luôn
phần lớn chất lượng homepage** mà không cần viết lại từng template (đúng
tinh thần Phần 43 "85-90% chất lượng homepage" mà không phải làm 30 trang
riêng lẻ):

- **Buttons** (`.cvc-btn`): cao tối thiểu 44px (36px cho `--small`), có
  hover/active state, shadow nhẹ cho primary. `button.cvc-btn` giờ có
  transition mượt.
- **Cards** (`.cvc-card`): shadow mặc định + hover elevation (translateY
  + shadow lớn hơn), radius 14px (từ 8px), padding rộng hơn (1.25rem).
- **Badges trạng thái**: tái dùng nguyên bộ `.cvc-badge--status-*` đã có
  từ Phase 10 (dashboard) cho card "Đang tuyển" ở homepage - không tạo
  bảng màu status thứ 2.
- **Empty state** (`.cvc-state--empty`): đổi từ nền vàng cảnh báo (giống
  lỗi validation) sang nền trung tính + icon 📂 - áp dụng cho **mọi**
  danh sách trống trên toàn site (tuyển dụng/kiến thức/văn bản/khóa
  học...) chỉ bằng 1 chỗ sửa CSS (Phần 25).
- **Header**: logo mark SVG (huy hiệu xanh, hình mái nhà công sở), tagline
  in hoa nhỏ, nav có icon SVG inline + active pill (nền xanh nhạt), auth
  area đổi đúng thứ tự (Đăng ký = outline, Đăng nhập = primary, theo
  benchmark). Header `position: sticky`.
- **Footer**: nền xanh đậm (khớp CTA banner), thay vì nền trắng nhạt cũ -
  tạo điểm kết thúc trang rõ ràng thay vì "trôi dần" hết nội dung.

## 4. Homepage - kiến trúc (bản hiện tại, sau 10A.3 - xem §11 cho lịch sử)

1. **Hero** (gradient xanh rất nhạt, 2 cột) - eyebrow, H1, subtitle,
   search box lớn (focal point, shadow riêng), 2 CTA, minh họa SVG bên
   phải (tòa nhà công sở + mũ tốt nghiệp + tài liệu, brand blue - KHÔNG
   dùng ảnh chụp generic, xem Phần 10 + §9).
2. **Value strip** (trắng) - 5 tính năng ngang, mỗi icon 1 màu riêng, chỉ
   mô tả SẢN PHẨM THẬT đang có - không phải số liệu nên không vi phạm
   "không fake data".
3. **2 cột `.cvc-home-split`**:
   - Cột trái `.cvc-home-main`: "Khóa học nổi bật" (`cvc-card-grid--4`) +
     "Tài nguyên ôn tập" (3 resource card: Cẩm nang, Thi nâng ngạch, Văn
     bản & chính sách).
   - Cột phải `.cvc-recruitment-panel`: sidebar "Tuyển dụng mới nhất" -
     panel bo tròn, list compact tối đa 5 dòng
     (`cvc_render_recruitment_list_item()`), badge "Đang tuyển" (đúng sự
     thật: `GET /api/recruitments` chỉ trả `status=published`, xem
     `RecruitmentController::index()`).
4. **CTA banner** (xanh đậm, 3 vùng ngang: icon+heading | quote | nút,
   skyline SVG mờ) - CTA trỏ `/dang-ky/` (khách) hoặc `/tai-khoan/muc-tieu/`
   (đã đăng nhập).

Vẫn đúng 6 lệnh gọi API (mỗi domain 1 lần `list()`, `per_page=1` cho
topics/knowledge/exam/legal vì chỉ cần `total` từ paginator để hiện số
lượng thật trên resource card, không cần render item).

## 5. Card render helper - nâng cấp field thật, không thêm field giả

- `cvc_render_course_card()`: thêm `cvc_course_type_label()` (format lại
  `course_type` - free text ở backend, không enum - cho dễ đọc, KHÔNG map
  category giả); placeholder SVG khi `thumbnail_url` null (không để
  trống/vỡ ảnh); free/paid badge từ `price` thật.
- `cvc_render_recruitment_card()`: thêm badge "Đang tuyển" (giải thích ở
  §4.4), gộp agency+location 1 dòng, deadline tách riêng nổi bật ở footer.
- `cvc_render_exam_card()`: **fix** - trước đây hiện "0 câu hỏi" khi
  `questions_count=0` (dữ liệu THẬT nhưng vô nghĩa với người dùng vì đề
  dùng blueprint chọn câu động, con số tĩnh này không phản ánh đúng số
  câu thực tế khi làm bài) - giờ chỉ hiện khi > 0.

Không có rating/learner count/view count nào được thêm ở bất kỳ card nào -
`GET /api/courses` không có field này (đã xác nhận qua response thật),
nên không hiển thị (Phần 31).

## 6. Responsive

Breakpoint theo đúng yêu cầu (Phần 5/35): kiểm tra logic tại 1440/1280/
1024/768/390/360 qua CSS (xem hạn chế ở §9 - không có browser thật để
chụp ảnh).

- ≥1025px: hero 2 cột đầy đủ, value strip 1 hàng ngang.
- 641-1024px: hero vẫn 2 cột nhưng minh họa co lại lên trên (order:-1,
  max-width 280px) tránh chật search box; value strip wrap 2 cột.
- ≤640px: hero minh họa ẩn hẳn (ưu tiên text+search - đúng Phần 16 "không
  để desktop grid bị ép xuống mobile"); value strip chuyển hàng dọc có
  icon+text cùng hàng; card-grid 4/3 cột → 1 cột; CTA banner giảm padding.
- Header: nav vẫn dùng cơ chế collapse có sẵn từ Phase 1-4B (`nav.js`,
  progressive enhancement - không JS thì nav luôn hiện đầy đủ), auth area
  giờ xuống hàng riêng có border-top trong menu mobile.

## 7. Accessibility

- Giữ nguyên toàn bộ pattern có sẵn (skip link, focus-visible, aria-current,
  screen-reader-text, breadcrumbs semantic).
- **Fix mới**: outline focus mặc định (xanh) gần như vô hình trên nền
  xanh đậm mới (footer, section `--deep`, CTA banner) - thêm override
  outline trắng riêng cho 3 vùng này.
- Card course: link ảnh (decorative, trùng nội dung với link tiêu đề)
  đánh dấu `aria-hidden="true" tabindex="-1"`, `alt=""` - tránh đọc 2 lần
  cùng 1 link cho screen reader.
- Icon nav/value-strip/resource-card đều `aria-hidden="true"` (label text
  đã có sẵn cạnh icon).
- Chỉ 1 `<h1>` trên homepage (đã verify qua HTTP thật).

## 8. Real data audit (Phần 31) — kết quả kiểm tra

| Field benchmark có nhắc | Có ở backend? | Xử lý |
|---|---|---|
| Course rating (4.8...) | Không | Không hiển thị |
| Course learner count | Không | Không hiển thị |
| Course price/free | Có (`price`, `sale_price`) | Hiển thị badge Miễn phí/Trả phí |
| Recruitment status | Có (`status`, list() luôn `published`) | Badge "Đang tuyển" |
| Exam questions_count | Có, nhưng = 0 trên DEV (blueprint động) | Chỉ hiện khi > 0 |
| Topics/Knowledge/Legal count | Có (`total` paginator) | Hiện khi > 0 (hiện tại 3/0/0) |

Không có số liệu nào trong 5 mục "Value strip"/copy hero là thống kê -
toàn bộ là mô tả tính năng sản phẩm có thật (đối chiếu với domain routes
đang hoạt động).

## 9. Known limitations

- **Hero vẫn dùng minh họa SVG, không phải ảnh thật**: ảnh benchmark thật
  (§11) có 1 ảnh chụp thật (nhân vật + tòa nhà công sở + cờ Việt Nam) ở
  hero. Theme không có quyền/tài sản ảnh thật phù hợp - dùng ảnh stock
  ngẫu nhiên từ internet sẽ vi phạm chính yêu cầu "KHÔNG dùng ảnh generic"
  của spec (Phần 10 gốc) vì không kiểm chứng được nguồn/bản quyền. Giữ
  nguyên minh họa SVG thương hiệu (đã tinh chỉnh tông màu/bố cục sát ảnh
  hơn ở 10A.3) - đây là khoảng cách còn lại lớn nhất so với ảnh benchmark.
  Khi có ảnh thật hợp lệ (đã mua bản quyền/tự chụp), thay trực tiếp bằng
  `<img>` trong `cvc_render_hero_illustration()` hoặc gọi hàm mới.
- **Không có visual QA bằng browser thật**: đã thử cài Playwright +
  Chromium để chụp ảnh thật ở 1440/1280/1024/768/390/360 (Phần 35) nhưng
  môi trường sandbox không có quyền cài system dependencies
  (`libnss3`/`libasound2`...) cho Chromium headless và không có `sudo`.
  Đã bù lại bằng review kỹ HTML/CSS đã render qua `curl` thật + kiểm tra
  logic responsive/contrast thủ công trong code, nhưng đây KHÔNG thay thế
  hoàn toàn việc mở trình duyệt thật kiểm tra. Khuyến nghị: người dùng tự
  mở `http://localhost:8080` ở các breakpoint trên trước khi coi Phase
  10A hoàn tất 100%.
- **Course/recruitment chưa có ảnh thật**: `thumbnail_url` hiện null cho
  course DEV duy nhất - đang dùng placeholder SVG thương hiệu. Khi CMS/
  Laravel có ảnh thật, card sẽ tự động dùng ảnh đó (đã có nhánh
  `if ($thumbnail)`), không cần sửa gì thêm.
- Các trang detail (recruitment/course/exam) chưa được viết lại layout
  riêng theo benchmark (hero 2 cột riêng, sidebar curriculum...) - chúng
  thừa hưởng nâng cấp component nền (typography/button/card/badge) nên
  đã tốt hơn đáng kể, nhưng CHƯA đạt kiến trúc bố cục mới như homepage.
  Đây là phạm vi hợp lý cho 1 lần tiếp theo nếu cần đẩy tiếp lên 100%
  đồng bộ.

## 10. QA đã thực hiện (local HTTP thật)

- PHP lint toàn bộ theme: sạch.
- `git diff --check`: sạch.
- CSS: brace-balanced, không duplicate rule mới (đã xoá `.cvc-hero .cvc-search-form`
  override cũ xung đột khi viết lại hero).
- HTTP thật qua `curl` (không mock): `/`, `/tuyen-dung/`, `/tuyen-dung/{slug}/`,
  `/thi-trac-nghiem/`, `/thi-trac-nghiem/{slug}/`, `/khoa-hoc/`,
  `/khoa-hoc/{slug}/`, `/kien-thuc/`, `/chu-de/`, `/van-ban-phap-luat/`,
  `/tim-kiem/`, `/dang-nhap/`, `/dang-ky/`, `/tai-khoan/` (+ 2 section) -
  toàn bộ 200, 0 PHP warning/fatal.
- Xác nhận qua HTML thật: đúng 1 `<h1>` trang chủ, badge "Đang tuyển" chỉ
  xuất hiện khi có recruitment thật, empty state tuyển dụng render đúng
  panel cao cấp (không phải box vàng cũ), exam card không còn hiện
  "0 câu hỏi", resource hub chỉ hiện count khi > 0.

## 11. Phase 10A.3 — Đối chiếu trực tiếp ảnh benchmark thật

File ảnh nhận được sau 10A.2: `docs/ui-benchmark/homepage-reference.png`
(mở và phân tích trực tiếp bằng Read - không suy diễn qua mô tả chữ thêm
nữa). So với bản 10A.2 (dựa hoàn toàn trên mô tả chữ), ảnh thật lộ ra 2
khác biệt cấu trúc quan trọng mà mô tả chữ trước đó không nói rõ:

1. **Tuyển dụng KHÔNG phải 1 section riêng nằm dưới Khóa học** - trong
   ảnh, "Tuyển dụng mới nhất" là 1 sidebar dọc compact (list 5 dòng, có
   avatar chữ cái + trạng thái + địa điểm + hạn nộp), nằm **bên phải**,
   chạy song song về chiều cao với toàn bộ cột trái (Khóa học nổi bật +
   3 resource card), không phải xếp chồng theo chiều dọc.
2. **Không có section "Thi trắc nghiệm" riêng với nền xanh đậm** - ảnh
   benchmark thật không có section này. Đề thi được thể hiện qua: (a)
   badge category "Ôn thi" màu hồng trên 1 course card, và (b) 1 trong 3
   resource card ("Thi nâng ngạch, thăng hạng"). Đã bỏ hẳn section
   `--cvc-section--deep` khỏi homepage (class CSS vẫn giữ trong design
   system làm tiện ích dùng chung, không phải dead code - chỉ không dùng
   ở homepage nữa).

### Thay đổi cụ thể đã áp dụng

- **`index.php`**: viết lại thành `.cvc-home-split` (CSS grid 2 cột:
  `minmax(0,1fr) minmax(300px,360px)`) - cột trái `.cvc-home-main` (course
  grid + resource grid), cột phải `.cvc-recruitment-panel` (card viền
  bo tròn, có header riêng + `cvc_render_recruitment_list_item()` cho
  từng dòng). Bỏ hẳn 6→còn 1 lệnh gọi `list()` cho exam (chỉ cần `total`
  cho resource card, không render card thi riêng nữa) - vẫn đúng 6 lệnh
  API tổng cộng.
- **Resource hub đổi từ 3 domain riêng biệt (Chủ đề/Kiến thức/Văn bản)
  sang 3 "cụm" khớp đúng ảnh**: "Cẩm nang công chức, viên chức" (gộp
  Kiến thức + Chủ đề, đếm gộp `$knowledge_total + $topics_total`), "Thi
  nâng ngạch, thăng hạng" (Thi trắc nghiệm - **thay cho section đã bỏ**),
  "Văn bản & chính sách" (Văn bản pháp luật). Mỗi card 1 màu icon riêng
  (blue/purple/green, khớp ảnh).
- **`cvc_render_recruitment_list_item()`** (mới, `template-tags.php`) -
  dòng compact cho sidebar: avatar là **chữ cái đầu tên cơ quan** (Agency
  model không có field logo thật - dùng initials là trình bày trung thực,
  không phải logo giả), tiêu đề, badge "Đang tuyển", agency, địa điểm
  (icon 📍), hạn nộp (icon 📅). `cvc_render_recruitment_card()` (card đầy
  đủ, dùng ở trang `/tuyen-dung/`) giữ nguyên, không đổi.
- **Course card**: đối chiếu ảnh thấy category badge nằm **đè lên ảnh**
  (không phải trong phần thân card) và mỗi category 1 màu riêng (Pháp
  luật/Kỹ năng xanh, Tin học xanh lá, Ôn thi hồng) - thêm
  `cvc_course_type_color()` (map `course_type` → 1 trong 4 màu cố định,
  ổn định theo hash cho type lạ vì field này free text ở backend) áp dụng
  cho cả nền placeholder lẫn màu badge. CTA cuối card đổi từ text-link
  sang **nút đặc full-width** (`.cvc-btn--primary.cvc-btn--block`) khớp
  "Đăng ký học →" trong ảnh (label giữ "Xem khóa học" vì flow thật là xem
  chi tiết trước, không phải đăng ký 1 chạm từ homepage - chỉ đổi hình
  thức nút, không đổi hành vi).
- **Value strip**: ảnh có 5 icon tròn **5 màu khác nhau** (xanh dương/xanh
  lá/cam/tím/hồng), bản 10A.2 trước đó lỡ để tất cả cùng 1 màu xanh -
  thêm biến thể màu `cvc-value-strip__icon--{color}`.
- **CTA banner**: ảnh là layout **ngang 3 vùng** (icon tròn viền trắng +
  heading bên trái | quote in nghiêng ở giữa, có border trái/phải mảnh |
  nút trắng bên phải), không phải 1 cột căn giữa như bản 10A.2. Viết lại
  `cvc_render_homepage_cta_banner()` + CSS flex row (stack dọc ở mobile).
  Thêm câu quote tĩnh (brand copy, không phải số liệu nên không vi phạm
  Phần 31) khớp đúng nội dung trong ảnh.
- **Section header có icon**: ảnh có icon nhỏ ngay trước tiêu đề "Khóa học
  nổi bật"/"Tuyển dụng mới nhất" - thêm tham số `$icon` (optional, không
  phá call site cũ) cho `cvc_render_section_header()`.

### Xác minh sau khi sửa (không dùng screenshot - xem giới hạn ở §9)

Vì môi trường vẫn không cài được Chromium headless (đã thử lại, vẫn lỗi
thiếu thư viện hệ thống + không có `sudo`), verify bằng:

- Gọi trực tiếp `cvc_render_recruitment_list_item()` với dữ liệu mẫu qua
  `docker exec cvc-wp php -r ...` (không qua HTTP, không đụng DB thật) để
  kiểm tra markup đúng cấu trúc trước khi tin tưởng đưa vào trang thật.
- `curl` thật `http://localhost:8080/` sau mỗi thay đổi, kiểm tra bằng
  cách tìm đúng class/nội dung mong đợi trong HTML trả về (course card có
  `cvc-badge--category-pink`, resource card có đúng 3 màu, value-strip có
  đủ 5 màu, CTA banner có `cvc-cta-banner__quote`...).
- Regression toàn bộ trang public (`/`, `/tuyen-dung/`, `/thi-trac-nghiem/`
  +slug, `/khoa-hoc/` +slug, `/kien-thuc/`, `/chu-de/`, `/van-ban-phap-luat/`,
  `/tim-kiem/`, `/dang-nhap/`, `/dang-ky/`) và dashboard đã đăng nhập
  (`/tai-khoan/`, `/tai-khoan/muc-tieu/`, `/tai-khoan/dau-trang/`,
  `/tai-khoan/ho-so/`) - toàn bộ 200, 0 PHP warning/fatal.
- PHP lint + `git diff --check` sạch, CSS brace-balanced sau mỗi lần sửa.

### Vẫn còn khác biệt so với ảnh (chấp nhận được / có lý do)

- Hero dùng SVG thay vì ảnh chụp thật (xem §9).
- Rating/số học viên trong ảnh KHÔNG được sao chép - đây là yêu cầu tường
  minh của người dùng ("Không fake rating, số học viên..."), không phải
  thiếu sót.
- Card "Xem khóa học" thay vì "Đăng ký học" (giải thích ở trên - flow
  thật khác, chỉ đổi hình thức nút).
