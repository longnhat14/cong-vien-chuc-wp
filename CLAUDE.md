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
- `template-exam-attempt.php` + `assets/css/exam-smart.css` + `assets/js/exam-smart.js` (giao diện văn bản pháp luật: Times New Roman, nền trắng chữ đen). Template này KHÔNG còn nạp `exam-os.css/js`.
- Dữ liệu đưa xuống JS qua `window.CVC_SMART_EXAM`; đáp án đúng/is_correct chỉ có khi lượt thi đã nộp. Câu bỏ trống sau khi nộp: `is_correct = null` (không tô đỏ).
- AJAX: `cvc_exam_answer` (không gửi option = bỏ chọn), `cvc_exam_meta`, `cvc_exam_submit`, `cvc_exam_ai_explain` (chỉ sau khi nộp).
- Thanh trái giữ các liên kết người dùng; cột phải: thời gian & nộp bài, bản đồ câu hỏi, thông tin đề, phân tích năng lực.
