# Phase 10 — Mega Frontend / Full Product Experience

Tài liệu này mô tả những gì Phase 10 đã hoàn thiện trên WordPress frontend:
authentication, user dashboard, learning path, exam-taking, recruitment
matching, recommendations, notifications, engagement tracking — tất cả kết
nối API thật của Laravel (`~/projects/congvienchuc`), không mock/fake data.

## 1. Kiến trúc

Không đổi so với `FRONTEND_ARCHITECTURE.md` — WordPress vẫn chỉ là
presentation layer, Laravel vẫn là source of truth. Phase 10 chỉ **mở rộng**
theo 2 hướng mới:

1. **Authenticated area** (`inc/auth.php`, `inc/actions.php`,
   `inc/account-sections/*.php`) — toàn bộ auth/dashboard/exam-taking là
   user-scoped, gọi Laravel Sanctum API bằng Bearer token.
2. **Client-side interactivity có kiểm soát** (`assets/js/exam-attempt.js`,
   `assets/js/engagement.js`) — chỉ 2 vùng thực sự cần AJAX (làm bài thi,
   tracking); mọi mutation khác (goal/bookmark/profile/notification/match)
   vẫn dùng Post/Redirect/Get qua `admin-post.php`, giữ nguyên pattern đơn
   giản đã có từ Phase 1-4B.

## 2. Authentication

- Token Sanctum lưu trong 1 cookie `cvc_auth_token` — **HttpOnly, Secure khi
  HTTPS, SameSite=Lax**. JS không bao giờ đọc được token.
- Trạng thái đăng nhập luôn xác thực lại qua `GET /api/auth/me` (memoized
  1 lần/request) — không tin cookie nào khác ngoài chính token.
- `cvc_require_login()` / `cvc_require_token_or_die()` bảo vệ mọi trang/
  action user-scoped, redirect kèm `redirect_to` (intended destination,
  chỉ chấp nhận path nội bộ — chống open redirect).
- Route: `/dang-nhap/`, `/dang-ky/`, logout qua `admin-post.php?action=cvc_logout`.

### Bug đã fix trong phiên này: `admin_post_nopriv_*` bị thiếu

WordPress core chỉ fire `admin_post_{action}` cho user **đã đăng nhập
WordPress**; `admin_post_nopriv_{action}` mới fire cho khách. Vì auth của
platform hoàn toàn qua Laravel (không có WP user thật nào từng login WP
core), **mọi visitor luôn là "khách" theo con mắt của WordPress** — kể cả
sau khi đã đăng nhập Laravel thành công.

11 handler mutation trong `inc/actions.php` (goal save/transition, learning
path generate, bookmark add/remove, match transition, notification read/
read-all, profile update/change-password, exam start) trước đó **chỉ đăng
ký `admin_post_{action}`**, thiếu hẳn nhánh `nopriv`. Hậu quả: gọi action
nào cũng nhận `wp_die('', 400)` trống — toàn bộ dashboard mutation **không
bao giờ hoạt động được** cho user thật, dù `check_admin_referer()`/service
layer phía sau hoàn toàn đúng. Đã fix bằng cách đăng ký cả 2 biến thể cho
từng action, đúng pattern đã có sẵn ở `inc/auth.php`. Đã verify lại bằng
HTTP E2E thật (login → bookmark → goal → exam start đều trả 302 thành công
sau fix, đều 400 trước fix).

## 3. User Dashboard — `/tai-khoan/{section}/`

1 template (`template-account.php`) + 9 section file trong
`inc/account-sections/`, section hợp lệ khai báo tập trung ở
`cvc_account_sections()` (routes.php):

| Slug URL | Section | API chính |
|---|---|---|
| `tong-quan` | overview | (dùng $user đã có + unread-count) |
| `ho-so` | profile | `GET/PUT /api/profile` |
| `muc-tieu` | goals | `GET/POST /api/goals`, transitions |
| `lo-trinh` | learning-path | `GET /api/learning-paths(+/{id})` |
| `dau-trang` | bookmarks | `GET/POST/DELETE /api/bookmarks` |
| `lich-su-thi` | exam-history | `GET /api/my-exam-attempts` |
| `goi-y` | recommendations | `GET /api/recommendations` |
| `viec-lam-phu-hop` | recruitment-matches | `GET /api/recruitment-matches` |
| `thong-bao` | notifications | `GET /api/notifications` |

Mỗi section chỉ gọi đúng API cần thiết cho chính nó — không N+1, không gọi
thừa. Toàn bộ dashboard `noindex` (nội dung riêng tư).

## 4. Exam-taking flow — `/lam-bai/{attemptId}/`

Flow đầy đủ: **EXAM DETAIL → start (Post/Redirect/Get) → in-progress
(AJAX autosave) → submit (AJAX, chống double-submit) → result**.

- `cvc_render_exam_start_cta()` (template-tags.php) — CTA "Bắt đầu làm bài"
  (đăng nhập) / "Đăng nhập để làm bài" (khách, giữ intended destination) —
  gắn vào `template-exam-detail.php`.
- `template-exam-attempt.php` — 1 template cho cả 2 trạng thái (in_progress
  / đã kết thúc), dựa hoàn toàn vào field API trả (không tự suy diễn
  pass/fail, không tự tính giờ).
- `assets/js/exam-attempt.js` — timer đếm ngược (chỉ hiển thị, server mới
  authoritative), autosave mỗi lần đổi đáp án, khoá nút ngay khi bấm nộp.
- Server-authoritative đã verify thật: hết giờ → gọi answer()/submit() bị
  Laravel tự động finalize (status `expired`) và trả lỗi đúng, WP hiển thị
  lại đúng trạng thái khi tải lại trang — không có logic đếm giờ nào ở
  WP quyết định kết quả.

### Security fix trong phiên này: leak `is_correct`/`explanation` khi đang làm bài

Phát hiện khi test AJAX `PUT /api/exam-attempts/{id}/answer` thật: response
trả nguyên `$answer->fresh()->load(['question','questionOption','selections'])`
— lộ `question.explanation` (giải thích đáp án) và `question_option.is_correct`
+ `explanation` của lựa chọn hiện tại, **ngay khi đang làm bài, trước khi
nộp**. `presentAttempt()` (dùng cho `start()`/`show()`) cũng có lỗ hổng
tương tự: chỉ che `question.options[].is_correct/explanation` (danh sách),
QUÊN che `question.explanation` (cấp câu hỏi) và `question_option` (đáp án
đã chọn — quan hệ riêng, không nằm trong mảng options) — nghĩa là **resume**
1 lượt thi đang làm dở cũng lộ đúng/sai của đáp án đã chọn.

Đã fix trong `~/projects/congvienchuc` (`ExamAttemptController`):
- `presentAttempt()`: che thêm `question.explanation` + `question_option.*`.
- `answer()`: trả response qua `sanitizeInProgressAnswer()` mới (cùng logic
  che), thay vì trả thẳng model.
- Bug gốc: dùng `isset()` để guard trước khi `unset()` — `isset(null)` luôn
  `false`, nên field có giá trị `null` (rất phổ biến — explanation thường
  chưa điền) **không bao giờ được coi là "cần che"**, dù key vẫn tồn tại
  trong response với giá trị `null`. Sửa thành `unset()` vô điều kiện
  (an toàn kể cả khi key chưa tồn tại).
- Test mới: `ExamScoringTest::test_selected_option_correctness_is_never_leaked_on_resume`,
  `test_answer_endpoint_response_never_leaks_correctness`. Full suite
  Laravel: **402/402 pass** sau fix.
- Commit riêng bên Laravel (không đổi API contract — chỉ bớt field lộ
  không nên có, response shape/status code không đổi).

## 5. Recruitment / Course / Knowledge / Topic / Exam — CTA mới

Trước Phase 10 (phiên này), các trang detail công khai (tuyển dụng, khóa
học, kiến thức, chủ đề, đề thi) **không có bất kỳ entry point nào** vào
dashboard actions dù backend + form handler đã có sẵn. Đã bổ sung, dùng
chung 3 helper trong `template-tags.php`:

- `cvc_render_bookmark_button( $type, $id )` — "Lưu vào đánh dấu", chỉ hiện
  khi đăng nhập. `POST /api/bookmarks` là `firstOrCreate()` (idempotent) ở
  backend nên không cần biết trước trạng thái đã bookmark hay chưa (API
  không trả `is_bookmarked`) — bấm nhiều lần an toàn. Gắn ở: recruitment,
  course, topic, knowledge, exam detail.
- `cvc_render_goal_quick_action( $title, $entityFields )` — "Đặt làm mục
  tiêu", tạo Goal gắn thẳng `recruitment_id`/`exam_id` của trang đang xem.
  Gắn ở: recruitment, exam detail (course/topic không có field goal tương
  ứng ở backend).
- `cvc_render_exam_start_cta( $examId )` — xem mục 4.

Engagement tracking (`data-cvc-track`) cũng được gắn đúng 3 type client
được backend whitelist (`LearningActivity::clientEngagementTypes()`):
`recruitment_viewed` (recruitment detail), `topic_viewed` (topic detail),
`knowledge_viewed` (knowledge detail). Không gắn `exam_viewed`/`course_viewed`
vì 2 type này **không nằm trong whitelist** — gửi lên sẽ bị 422 vô ích
(engagement API vẫn fail-silent với client nên không lộ ra ngoài, nhưng
gọi API vô ích thì không nên làm — Phần Performance).

## 6. Engagement tracking

`assets/js/engagement.js` đọc `[data-cvc-track]`/`[data-cvc-click-track]`
trên DOM, gửi qua `admin-ajax.php` → `inc/engagement.php` (proxy thuần,
forward nguyên payload tới `POST /api/engagement/events`) → Laravel tự
whitelist `type`/`target_type`/`target_id` (không lặp lại business rule ở
WP). Dùng `navigator.sendBeacon` khi có, fail-silent hoàn toàn — không bao
giờ hiện lỗi cho người dùng, không bao giờ chặn UI.

## 7. Design system mở rộng (CSS)

`style.css` trước phiên này chỉ có design system Phase 1-4B (card/badge/
button/pagination/search) — **không có bất kỳ CSS nào** cho form, notice,
account dashboard, header auth area, hay exam-taking UI, dù toàn bộ PHP/JS
của các màn hình đó đã viết xong. Mọi trang auth/dashboard/exam-attempt khi
mở thực tế đều **render không có style** (unstyled). Đã bổ sung 1 block
CSS mới (~700 dòng) mở rộng đúng token/component hiện có (không tạo design
system thứ 2):

- Forms (`cvc-form`, `cvc-form__field`, `cvc-form--auth`, `cvc-form--inline`)
- Notices (`cvc-notice--success/error`)
- Header auth area (`site-header__auth`, `cvc-notification-badge`)
- Account dashboard (`cvc-account-layout` 2 cột → 1 cột mobile, nav sticky,
  overview grid, goal/match/bookmark card list, notification list, table)
- Badge trạng thái (`cvc-badge--status-*` cho từng giá trị status thật của
  Goal/Match — draft/active/paused/completed/archived/new/seen/dismissed/
  interested — không tự bịa màu cho giá trị không tồn tại)
- Exam-taking UI (status bar sticky, timer, question nav, options, submit
  bar, kết quả + review với is-correct/is-incorrect)
- `button.cvc-btn` reset (design system cũ chỉ áp style cho `<a>`, Phase 10
  dùng `<button type="submit">` nhiều — cần reset font/cursor riêng)
- Responsive: account layout stack ở ≤800px, exam status bar/submit bar
  stack ở ≤640px, mobile nav auth area xuống hàng riêng trong menu.

## 8. HTTP E2E đã verify thật (local)

Laravel chạy `php artisan serve --port=8001`, WordPress qua Docker
(`cvc-wp`, port 8080, `host.docker.internal:8001` → Laravel). User test:
`phase10.qa@example.com` (tạo qua tinker cho phiên làm việc này).

- Public: `/`, `/tuyen-dung/`, `/thi-trac-nghiem/`, `/thi-trac-nghiem/{slug}/`,
  `/khoa-hoc/`, `/kien-thuc/`, `/chu-de/`, `/van-ban-phap-luat/`,
  `/tim-kiem/?q=...` — tất cả 200, không PHP warning/fatal (regression pass).
- Auth: đăng nhập (302 + cookie HttpOnly) → dashboard (`/tai-khoan/`) → tất
  cả 9 section (200, không lỗi) → đăng xuất (cookie xoá, dashboard sau đó
  redirect về `/dang-nhap/?redirect_to=...`).
- Bookmark: bấm "Lưu vào đánh dấu" ở recruitment detail → xuất hiện đúng ở
  `/tai-khoan/dau-trang/`.
- Goal: bấm "Đặt làm mục tiêu" → xuất hiện đúng ở `/tai-khoan/muc-tieu/`
  với đúng title + recruitment liên kết.
- Exam: Bắt đầu làm bài → `/lam-bai/{id}/` render đúng câu hỏi → autosave
  đáp án qua AJAX (200, lưu đúng) → hết giờ tự động finalize phía server
  (không phải WP tự tính) → nộp bài trùng bị chặn đúng (`already_submitted`)
  → trang kết quả hiện đúng review, đáp án đúng/sai chỉ lộ ra SAU khi kết
  thúc.

## 9. Known limitations (chưa làm trong phiên này)

- **Course enrollment / lesson progress**: Laravel đã có sẵn API đầy đủ
  (`POST /api/course-enrollments`, `PUT /api/course-lessons/{id}/progress`,
  `GET /api/my-courses`, `GET /api/learning-dashboard`) nhưng **chưa được
  wire vào WordPress** — course detail/lesson page hiện chưa có nút "Đăng
  ký khóa học" hay "Đánh dấu hoàn thành bài học". Đây là backend có sẵn từ
  trước Phase 10 (Phase 5), không phải gap do phiên này tạo ra, nhưng vẫn
  là phần còn thiếu để "Course UX" trong Phần VII/IX của spec coi là hoàn
  chỉnh. Đề xuất: `CVC_Course_Enrollment_Service` + `CVC_Lesson_Progress_Service`
  theo đúng pattern các service khác, 2 action handler mới trong
  `inc/actions.php` (nhớ đăng ký cả nopriv!), nút enroll chỉ hiện khi
  `price == 0` (đăng ký khóa trả phí bị backend chặn 402 vì chưa có
  payment thật).
- **"Khóa học của tôi" dashboard section**: phụ thuộc mục trên, chưa thêm
  section thứ 10 vào `cvc_account_sections()`.
- Dev data hiện tại: chỉ 1 đề thi published, 0 tin tuyển dụng `published`
  (2 bản ghi đều `expired`) — nhiều listing sẽ hiện empty state đúng như
  thiết kế, không phải bug.

## 10. Manual QA checklist (rút gọn)

- [ ] Đăng ký → đăng nhập → xem đúng "Tài khoản" + badge unread ở header.
- [ ] Đăng xuất → header trở lại "Đăng nhập/Đăng ký".
- [ ] Mỗi 9 section dashboard: loading/empty/error đều có, không màn trắng.
- [ ] Bookmark/goal quick action trên recruitment & exam detail hoạt động,
      phản ánh đúng trong dashboard tương ứng.
- [ ] Exam: start → answer (autosave) → timer chạy đúng → submit (hoặc hết
      giờ tự nộp) → result hiện đúng, không lộ đáp án khi đang làm.
- [ ] Resize ≤390px: header, dashboard nav, exam question nav/timer đều
      dùng được, không tràn ngang.
- [ ] Tab bằng bàn phím qua form login/register/dashboard — focus visible.
