# Phase 10A.5 — Premium UX + Conversion + Trust + Visual QA

Nâng cấp homepage từ "website đẹp" (10A.4) thành "premium product + trust
+ conversion", vẫn giữ nguyên toàn bộ visual quality/kiến trúc 10A.4,
không redesign lại. Điểm khác biệt lớn nhất phiên này: **giải quyết được
visual QA bằng screenshot trình duyệt thật** - việc đã bị chặn (không có
`sudo`) suốt 3 phiên trước.

## 1. Giải quyết Screenshot Tooling (Phần 3)

3 phiên trước (`PHASE_10A_UI_UX.md`, `PHASE_10A.4_VISUAL_REBUILD.md`) đều
kết luận không thể chụp ảnh vì Playwright/Chromium cần system dependencies
(`libnss3`, `libasound2`...) mà sandbox không cấp `sudo`. Phiên này thử
hướng khác: **chạy Chromium bên trong 1 container Docker đã có sẵn đầy đủ
dependencies**, thay vì cài trực tiếp lên host.

```bash
docker pull ghcr.io/puppeteer/puppeteer:23.0.0
docker run --rm --network host \
  -v /path/to/shot.js:/home/pptruser/shot.js:ro \
  -v /path/to/shots:/home/pptruser/shots \
  ghcr.io/puppeteer/puppeteer:23.0.0 \
  node /home/pptruser/shot.js "http://localhost:8080/" 1440 1200 /home/pptruser/shots/home.png
```

Lưu ý kỹ thuật (để lần sau không mất thời gian debug lại):
- Script mount phải nằm dưới `/home/pptruser/` (nơi image cài `node_modules`
  sẵn) - mount ở đường dẫn khác (VD `/tmp/shot.js`) sẽ lỗi
  `Cannot find module 'puppeteer'` vì Node module resolution đi lên từ vị
  trí file, không tìm thấy `node_modules` ở `/tmp`.
- Thư mục output mount vào container cần quyền ghi cho user `pptruser`
  (không phải root) - `chmod 777` trên thư mục host trước khi mount.
- `--network host` để container gọi thẳng `http://localhost:8080` (WordPress
  chạy trên host qua Docker Compose khác) mà không cần biết IP nội bộ.

**Kết quả**: chụp được ảnh thật ở 1440/1024/390px, dùng để so sánh trực
tiếp với `docs/ui-benchmark/homepage-reference.png` và tự phát hiện lỗi
thị giác thật (xem §2).

## 2. Lỗi phát hiện được NHỜ có screenshot thật (quan trọng nhất phiên này)

Ảnh chụp đầu tiên (trước khi sửa gì) lộ ra: **toàn bộ icon emoji trên
trang (value strip, resource hub, goal section, hero feature card, nav,
search icon...) hiện ra là Ô TRỐNG (tofu box)** - môi trường headless
Chrome trong Docker image không có font color-emoji. Đây không phải lỗi
hiếm - bất kỳ máy nào thiếu font emoji màu (nhiều bản Linux desktop tối
giản, một số trình duyệt cũ) sẽ gặp y hệt.

Đây chính xác là điều Phần 27 (ICONOGRAPHY, đã có từ 10A.4) cảnh báo
trước: *"Không dùng emoji làm icon chính. Ưu tiên SVG"* - nhưng các phiên
trước đã dùng emoji cho hầu hết icon trang trí vì không có cách kiểm
chứng thị giác thật. Lỗi này chỉ lộ ra khi có ảnh chụp thật.

**Đã sửa triệt để**: xây `cvc_render_icon( string $key, int $size, string $class )`
- 1 thư viện ~22 icon SVG stroke-based (`currentColor`, đồng nhất
stroke-width 1.7) dùng chung toàn theme, thay thế **toàn bộ** icon emoji:
value strip (5), hero feature card (4), resource hub (3), goal direction
(5 + 1 target), section header icon, empty state icon, nav icon (đã có
sẵn từ trước, giữ nguyên), search icon header, bookmark/goal quick-action
button icon, recruitment row (pin/calendar), notification bell mới thêm.

Đã verify bằng chính screenshot: chụp lại sau khi sửa - toàn bộ icon hiện
đúng, không còn ô trống nào (xem `docs/ui-benchmark/` local hoặc tự chạy
lại lệnh ở §1).

## 3. Trust micro-signals (Phần 10) - thay số liệu giả bằng năng lực thật

KHÔNG thêm "10.000 học viên"/"98% hài lòng" (không có dữ liệu thật).
Thay bằng 1 dòng checklist dưới CTA hero, liệt kê đúng năng lực **đang
chạy thật** trên hệ thống - mỗi dòng kiểm chứng được bằng chính tính năng:

- "Nội dung theo chủ đề, môn thi cụ thể" - Topic taxonomy có thật.
- "Bài thi chấm điểm tự động" - `ExamScoringService` (Phase 6) có thật.
- "Theo dõi tiến độ học tập" - Learning Path + Exam History (Phase 10) có thật.
- "Nguồn tuyển dụng ghi rõ cơ quan" - `Recruitment.agency` field có thật.

## 4. Learning Journey - "Con đường của bạn" (Phần 12, section mới)

Timeline 5 bước giải thích product value trong ~10 giây, ngang ở desktop
(có đường nối giữa các bước), dọc ở mobile/tablet (≤900px). Mỗi bước trỏ
**route thật đã tồn tại**, không phải minh họa suông:

| Bước | Đích | Auth-aware? |
|---|---|---|
| 01 Chọn mục tiêu | `/tai-khoan/muc-tieu/` | Có - `cvc_login_url()` nếu chưa đăng nhập |
| 02 Học theo lộ trình | `/tai-khoan/lo-trinh/` | Có |
| 03 Luyện thi | `/thi-trac-nghiem/` | Không cần (public) |
| 04 Theo dõi tiến độ | `/tai-khoan/lich-su-thi/` | Có |
| 05 Sẵn sàng cho cơ hội | `/tuyen-dung/` | Không cần (public) |

Dùng đúng pattern `cvc_login_url( cvc_account_url(...) )` đã có sẵn từ
Phase 10 (preserve intended destination sau khi đăng nhập).

## 5. Goal section - bổ sung mô tả ngắn mỗi card (Phần 13)

Trước đó (10A.4) mỗi goal-direction card chỉ có icon + label. Thêm 1 dòng
mô tả ngắn mỗi card (VD "Thi công chức" → "Tin tuyển dụng khối công chức")
để trả lời rõ hơn "bấm vào sẽ tới đâu" trước khi click - không đổi đích
URL (vẫn đúng các route/filter thật đã audit ở `PHASE_10A.4_VISUAL_REBUILD.md §2.5`).

## 6. Header - thêm chuông thông báo (Phần 8)

Trước đó chỉ có link text "Tài khoản" + số badge. Thêm icon chuông riêng
(`cvc_render_icon('bell')`) trỏ thẳng `/tai-khoan/thong-bao/`, badge đỏ
hiện số thật (`min(9, $unread)` + dấu "+" nếu >9, tránh badge quá dài) -
chỉ hiện khi đã đăng nhập, dùng đúng API `unreadCount()` đã có.

## 7. Value strip - tinh chỉnh microcopy (Phần 11)

Đổi mô tả dài dòng thành cụm ngắn dạng "→ giá trị", đúng ví dụ Phần 11:
"Khóa học đa dạng" → "Học theo từng chủ đề", "Ôn thi hệ thống" → "Luyện đề
và theo dõi kết quả"... Không đổi cấu trúc/màu sắc đã có từ 10A.4.

## 8. Không làm (và vì sao) - tôn trọng Phần 40 "không nhồi section"

- **Featured course "Bạn sẽ học gì?" với lesson titles thật** (Phần 16):
  cần thêm 1 API call riêng (course detail) chỉ để lấy danh sách lesson -
  cân nhắc chi phí/lợi ích, đã ưu tiên các hạng mục có tác động lớn hơn
  (screenshot tooling, icon fix) trong thời gian phiên này. Course
  featured card hiện tại vẫn dùng đúng field thật (title/description/
  lesson_count/duration/price), không fake.
- **Section "Luyện thi thực chiến" riêng** (Phần 20/21): không thêm section
  riêng - đã tích hợp đủ vào (a) CTA "Bắt đầu ôn thi" ngay trong hero,
  (b) bước 03 "Luyện thi" trong Learning Journey, (c) card "Thi thăng hạng"
  trong Goal section. Thêm 1 section riêng nữa sẽ vi phạm Phần 40 ("nếu
  hai section có cùng mục đích → hợp nhất") vì đã có 3 điểm chạm exam rồi.
- **Course CTA state-aware (đã đăng ký/hoàn thành)** (Phần 17): backend
  hiện KHÔNG có enrollment API được wire vào WP (ghi nhận từ Phase 10 -
  "Course enrollment" là known limitation, thuộc phạm vi Phase 10B) - CTA
  hiện chỉ phân biệt đúng 2 trạng thái có thật: miễn phí ("Học miễn phí")
  / trả phí ("Xem khóa học"), không fake "Đã đăng ký"/"Tiếp tục học".

## 9. QA đã thực hiện

- **Screenshot thật** (giải quyết được, xem §1): 1440px, 1024px, 390px -
  so sánh trực tiếp bằng mắt với ảnh benchmark + với chính bản 10A.4.
  Phát hiện + sửa lỗi icon emoji (§2). Sau khi sửa, chụp lại xác nhận
  toàn bộ icon hiển thị đúng ở cả 3 kích thước, không tràn ngang, Learning
  Journey timeline hiển thị đúng (ngang có nối ở 1440/1024, dọc có nối ở
  390).
- HTTP thật (`curl`) cho toàn bộ trang public + dashboard đã đăng nhập
  (bao gồm `/tuyen-dung/?recruitment_type=civil_servant` mới dùng ở Goal
  section) - toàn bộ 200, 0 PHP warning/fatal.
- PHP lint toàn bộ theme, CSS brace-balance, `git diff --check` sạch.
- Kiểm tra thủ công 3 "user test" (Phần 45): User A (muốn thi công chức) -
  thấy "Bắt đầu ôn thi" ngay trong hero + Goal section bên dưới; User B
  (muốn học khóa học) - "Khóa học nổi bật" hiện ngay sau Learning Journey,
  card đầy đủ title/mô tả/giá/CTA; User C (muốn tìm việc) - "Khám phá
  tuyển dụng" trong hero + sidebar "Tuyển dụng mới nhất" luôn hiện song
  song với Khóa học. Cả 3 đều trả lời được trong lần cuộn đầu tiên.

## 10. Known limitations (không đổi từ 10A.4, nhắc lại)

- Hero vẫn dùng minh họa SVG, không phải ảnh chụp thật (không có ảnh bản
  quyền hợp lệ).
- Enrollment/progress course chưa wire (Phase 10B).
- Footer chỉ 2 cột thật (không có trang Hỗ trợ/social thật để trỏ tới).
