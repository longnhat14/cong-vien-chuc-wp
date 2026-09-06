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
