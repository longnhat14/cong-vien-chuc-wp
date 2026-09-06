# Công Viên Chức — Frontend Architecture

## 1. Vai trò

WordPress là public frontend, CMS/SEO và presentation layer của Công Viên Chức.

Laravel là backend/API và source of truth cho business data.

## 2. Local development

- WordPress: http://localhost:8080
- Laravel API: http://127.0.0.1:8000
- Theme source: `theme/cong-vien-chuc`
- WordPress chạy bằng Docker Compose.
- Theme source được mount trực tiếp vào container.

## 3. Data boundary

- Không truy cập trực tiếp database Laravel từ WordPress.
- Không để WordPress trở thành source of truth cho Recruitment, Position, Knowledge, Question, Exam, Course hoặc các business entity thuộc Laravel.
- Frontend lấy dữ liệu qua REST API.
- Không tự ý thay đổi API contract.
- Khi API chưa đủ cho một UI requirement, kiểm tra backend/API trước khi tạo workaround bằng dữ liệu giả.

## 4. Product entity graph

Frontend phải phản ánh quan hệ:

Recruitment → Position → Knowledge → Question → Exam → Course

Các khu vực frontend dự kiến:

- Trang chủ
- Tuyển dụng
- Chi tiết tin tuyển dụng
- Vị trí/chức danh
- Kiến thức
- Câu hỏi/ôn luyện
- Kỳ thi
- Khóa học
- Tài khoản/học tập

Đây là định hướng sản phẩm, không phải cam kết rằng mọi API đã hoàn thiện. Kiểm tra route/controller thực tế trước khi triển khai từng khu vực.

## 5. SEO & UX

Ưu tiên:

- SEO technical tốt
- HTML semantic
- URL rõ ràng
- metadata và structured data phù hợp khi có đủ dữ liệu
- responsive/mobile-first
- accessibility
- tốc độ tải
- giao diện tiếng Việt
- internal linking giữa các entity liên quan

## 6. API integration

Public API đã xác nhận hoạt động:

- `GET /api/courses`
- `GET /api/courses/{slug}`
- `GET /api/courses/{slug}/lessons/{courseLesson}`

Authentication đã xác nhận:

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`

Không hardcode dữ liệu production vào theme để thay thế API.

## 7. Code rules

- Source theme nằm trong `theme/cong-vien-chuc`.
- Không sửa file trực tiếp trong Docker container khi có thể sửa source mount.
- Giữ code modular, dễ bảo trì.
- Không rebuild backend modules từ frontend.
- Trước mỗi feature lớn: đọc context, kiểm tra API, xác định data contract, rồi mới code.
- Không tự động publish business content.
