# Công Viên Chức — WordPress Frontend

## Vai trò
Đây là frontend WordPress của nền tảng Công Viên Chức.

## Kiến trúc
- WordPress = frontend, CMS/SEO và presentation layer.
- Laravel = backend/API và source of truth cho business data.
- Laravel local API: http://127.0.0.1:8000
- WordPress local: http://localhost:8080
- Không truy cập trực tiếp database Laravel từ WordPress.
- WordPress giao tiếp với Laravel thông qua REST API.

## Backend API
Các API public hiện có nằm dưới `/api`, ví dụ:
- GET /api/courses
- GET /api/courses/{slug}
- GET /api/courses/{slug}/lessons/{courseLesson}

Authentication:
- POST /api/auth/login
- POST /api/auth/logout
- GET /api/auth/me

## Nguyên tắc
- Không tự tạo business data trong WordPress nếu dữ liệu đó thuộc Laravel.
- Không sửa database Laravel trực tiếp từ WordPress.
- Không tự ý thay đổi API contract.
- Ưu tiên SEO, tốc độ, accessibility, responsive và UX tiếng Việt.
- Khi chưa rõ API hoặc business rule, kiểm tra code/API trước khi triển khai.
- Không phá vỡ các module/backend đã hoàn thành.

## Frontend
Theme chính:
`theme/cong-vien-chuc`

Mọi source theme phải nằm trong:
`theme/cong-vien-chuc`

Không sửa file trực tiếp bên trong Docker container nếu có thể sửa source mount.

## Quy tắc làm việc
Trước khi code:
1. Đọc CLAUDE.md.
2. Kiểm tra cấu trúc hiện tại.
3. Kiểm tra API liên quan.
4. Đề xuất thay đổi nhỏ, có thể kiểm tra được.
5. Không tự ý xây lại những phần đã hoạt động.

## Trang làm bài thi (`/lam-bai/{id}/`, cập nhật 16/10/2026)
- `template-exam-attempt.php` + `assets/css/exam-smart.css` + `assets/js/exam-smart.js` (giao diện văn bản pháp luật: Times New Roman, nền trắng chữ đen; điểm nhấn xanh hải quân --acc #123a63 / --acc2 #1767aa theo file mẫu, KHÔNG dùng khối đen/nút đỏ; đỏ chỉ cho câu sai, cam cho câu đang xem, vạch đỏ nhạt cho đánh dấu). Template này KHÔNG còn nạp `exam-os.css/js`.
- Dữ liệu đưa xuống JS qua `window.CVC_SMART_EXAM`; đáp án đúng/is_correct chỉ có khi lượt thi đã nộp. Câu bỏ trống sau khi nộp: `is_correct = null` (không tô đỏ).
- AJAX: `cvc_exam_answer` (không gửi option = bỏ chọn), `cvc_exam_meta`, `cvc_exam_submit`, `cvc_exam_ai_explain` (chỉ sau khi nộp).
- Thanh trái giữ các liên kết người dùng; cột phải: thời gian & nộp bài, bản đồ câu hỏi, thông tin đề, phân tích năng lực.
- DEPLOY lên Hostinger bằng tar PHẢI đặt quyền khi đóng gói: `tar --mode='u=rw,go=r' -cf - <files> | ssh ... 'tar xf -'`. File tạo trên Windows mang quyền 700 → LiteSpeed trả 403 cho CSS/JS/tải về (sự cố 16/10/2026: trang làm bài mất toàn bộ giao diện). Sau deploy kiểm tra `curl -o /dev/null -w '%{http_code}'` từng file tĩnh.
- Lượt thi đã quá giờ khi mở lại trang: không tự nộp, hiện hộp "Đã hết giờ làm bài" (nộp bài / luyện tập tiếp). Chỉ tự nộp khi đồng hồ về 0 trong lúc đang làm.

## Giao diện "văn bản" toàn site (16/10/2026)
- Nguồn chung: `assets/css/cvc-doc.css` (nạp trong `header.php`) + `tailwind.config` trong `header.php`: font sans/serif = Times New Roman; bo góc tối đa 2-4px; mọi `shadow-*` = none; bảng màu cũ `navy/gold/azure` được ánh xạ sang xanh hải quân. Màu mới: `ink` (#111, `ink-2` #333, `ink-muted` #5c5c5c), `acc` (#123a63), `acc-2` (#1767aa), `acc-soft` (#eaf3fc), `acc-line` (#8fb4da), `acc-deep` (#0b2744).
- KHÔNG dùng lại nền tối (bg-navy-9xx/bg-slate-9xx), gradient, glass, emoji trang trí, chữ trắng trên nền tối. Đỏ chỉ cho cảnh báo/hết hạn/sai; xanh lá cho đạt/đúng/miễn phí.
- Thành phần dz-*: `dz-wrap`, `dz-main`, `dz-pagehead` (đầu trang căn giữa; thêm `is-list` để tiêu đề in hoa), `dz-noticehead` + `dz-facts` (đầu thông báo/văn bản), `dz-cols` (`is-3`, `is-left`), `dz-colside`, `dz-h2`, `dz-section`, `dz-box`/`dz-box-head`/`dz-box-body`, `dz-panel`, `dz-card`, `dz-list` (+ `dz-item-title`, `dz-item-meta`, `dz-item-sum`, `dz-row`), `dz-table`/`dz-tablewrap`, `dz-kv`, `dz-tag` (`is-ok`, `is-warn`, `is-off`), `dz-btn` (`dz-btn-primary`, `dz-btn-sm`), `dz-toc`, `dz-chips`, `dz-notice`, `dz-empty`.
- Danh sách dùng chung (`inc/listing-components.php`): `cvc_render_listing_hero/search/explore_nav/study_sidebar` đã viết lại theo dz-*. `cvc_render_slot_card()` in ra `<li>` - nơi gọi phải bọc `<ul class="dz-list">`.
- Không còn hiệu ứng scroll-reveal (bỏ enqueue). `style.css` được nạp với version = filemtime.
- Văn bản pháp luật / tài liệu hiển thị dạng bảng tra cứu; tuyển dụng, đề thi, kiến thức, chủ đề, khóa học dạng mục lục (dz-list). Không hiển thị số liệu bịa (đã bỏ "MÃ ĐỀ: KTC-2026", "60 câu · 60 phút", "18 nhánh kiến thức" gắn cứng).

## Trang chủ v17 (16/10/2026)
- Dựng theo file thiết kế `congvienchuc-homepage-redesign-v17` — giữ nguyên bố cục, hiệu ứng, tông màu (navy #102846 + cam). `index.php` là trang ĐỘC LẬP: tự in `<head>`/header/footer của thiết kế, không gọi `get_header()`/`get_footer()`.
- Tài nguyên: `assets/css/home-v17.css` (CSS thiết kế + phần thêm: khu khóa học, sidebar tin, link AI), `assets/js/home-v17.js`, ảnh `assets/images/home-v17/`. `inc/home-v17.php` nạp chúng và dequeue `cvc-style`, `cvc-nav`, `cvc-homepage-nextgen` CHỈ trên trang chủ.
- **Bẫy**: các route `cvc_page` không gắn post nên WP coi là "home" → `is_front_page()` trả true trên MỌI trang domain. Luôn dùng `cvc_is_home17()` để nhận đúng trang chủ.
- Dữ liệu: `cvc_home17_data()` ← `GET /api/homepage/overview` (cache 5 phút ở backend). AJAX `cvc_home_jobs` (tin còn hạn theo tỉnh, transient 10 phút) và `cvc_ai_coach` (→ `POST /api/ai/coach`, backend giới hạn 10/phút + 40/ngày theo IP thật). Cấu hình JS `window.CVC_HOME` đặt qua `$GLOBALS['cvc_home17_cfg']` (in trong wp_head, không gọi wp_add_inline_script trước khi script được enqueue).
- Khu `#khoa-hoc`: có khóa bán được → thẻ khóa học; chưa có → form "Báo tôi khi mở" (newsletter `source=courses_coming_soon`) + danh sách khóa "Sắp mở".
- Liên quan: `/tinh-luong/` (`template-salary.php`, công cụ lương chuyển từ trang chủ cũ), `/tuyen-dung/?tinh=<province_id>`, `/tai-lieu/?dinh-dang=word|pdf|excel|ppt`. SEO: Rank Math bị ghi đè title/description/canonical bằng giá trị `cvc_seo_set_*()` (inc/seo.php).

## Đăng ký 2 loại tài khoản, ảnh đại diện, bảng xếp hạng (21/10/2026)
- `/dang-ky/` chọn "Thí sinh / học viên" hoặc "Đơn vị tuyển dụng" (`?loai=don-vi`); lỗi giữ lại dữ liệu đã nhập (`cvc_flash_old_input`). Tài khoản đơn vị: đăng nhập → `/don-vi/`, nút đầu trang "Đơn vị" + "Học tập". Menu "Tuyển dụng" (`cvc_rec_nav_item`): Tin tuyển dụng · Tin ngoài công lập (khi bật) · Đăng tin tuyển dụng.
- Ảnh: `inc/avatars.php` (`cvc_avatar_html` ảnh/chữ cái đầu, `cvc_logo_html`, `cvc_render_image_picker` + `assets/js/cvc-avatar-crop.js` khung cắt), hồ sơ `/tai-khoan/ho-so/` (ảnh + công tắc hiện tên trên bảng xếp hạng), logo ở `/don-vi/thanh-vien/`.
- Bảng xếp hạng: `/xep-hang/` (`template-leaderboard.php`, `inc/leaderboard.php`), Top 5 trên trang đề và trang tin, khung "Xếp hạng" trên trang làm bài (đã nộp) và trang kết quả, huy hiệu ở Tổng quan tài khoản. Menu Đề thi → Đánh giá → "Bảng xếp hạng".
