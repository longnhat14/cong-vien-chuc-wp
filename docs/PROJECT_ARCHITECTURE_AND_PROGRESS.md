# CÔNG VIÊN CHỨC — KIẾN TRÚC HỆ THỐNG LARAVEL BACKEND & TIẾN ĐỘ FRONTEND

Tài liệu này tổng hợp **toàn bộ kiến trúc Backend Laravel** từ repository gốc (`D:\Setup\Claude\Workspace\Account1\Production\congvienchuc`), **danh sách API Contract chính thức**, **đồ thị thực thể** và **nhật ký tiến độ tích hợp Frontend WordPress**.

---

## 🏛️ 1. Tổng Quan Cấu Trúc Backend Laravel Repository (`congvienchuc`)

- **Vị trí Repository Backend**: `D:\Setup\Claude\Workspace\Account1\Production\congvienchuc`
- **Môi trường khởi chạy Backend**: WSL (Windows Subsystem for Linux) tại `http://127.0.0.1:8000`.
- **Framework & Package**: Laravel 11.x, Laravel Sanctum (Authentication), Spatie Permission (Phân quyền Admin/User).
- **Frontend WordPress Container**: Docker `cvc-wp` (`http://localhost:8080`) kết nối tới Backend Laravel thông qua `CVC_API_BASE_URL` (`http://host.docker.internal:8000`).

---

## 🔌 2. Danh Sách Endpoint REST API Chính Thức (Backend Verified)

### A. Authentication API (`/api/auth`)
- `POST /api/auth/login`: Đăng nhập cán bộ / công chức.
- `POST /api/auth/logout`: Đăng xuất (Sanctum Auth Bearer).
- `GET /api/auth/me`: Lấy thông tin tài khoản & quyền hạn.

### B. Public API (Không yêu cầu Token)
- **Khóa học (Courses)**:
  - `GET /api/courses`: Danh sách khóa học & phân trang.
  - `GET /api/courses/{slug}`: Chi tiết khóa học.
  - `GET /api/courses/{slug}/lessons/{courseLesson}`: Nội dung bài học.
- **Tuyển dụng (Recruitments)**:
  - `GET /api/recruitments`: Danh sách tin tuyển dụng công chức/viên chức.
  - `GET /api/recruitments/{slug}`: Chi tiết tin tuyển dụng & chỉ tiêu.
- **Chủ đề Ngạch (Topics)**:
  - `GET /api/topics`: Danh sách tiêu chuẩn ngạch công vụ.
  - `GET /api/topics/{slug}`: Chi tiết ngạch & tiêu chuẩn.
- **Kiến thức công vụ (Knowledge Items)**:
  - `GET /api/knowledge-items`: Danh sách chuyên đề bồi dưỡng.
  - `GET /api/knowledge-items/{slug}`: Chi tiết chuyên đề.
- **Thư viện Đề thi AI (Exams)**:
  - `GET /api/exams`: Danh sách bộ đề thi trắc nghiệm.
  - `GET /api/exams/{slug}`: Chi tiết bộ đề thi.
- **Văn bản pháp luật (Legal Documents)**:
  - `GET /api/legal-documents`: Tra cứu văn bản hợp nhất.
  - `GET /api/legal-documents/{slug}`: Chi tiết văn bản quy phạm.

### C. Authenticated Learning & Exam API (Cần Sanctum Token)
- **Đăng ký & Tiến độ học**: `GET /api/course-enrollments`, `POST /api/course-enrollments`, `GET /api/my-courses`, `GET /api/learning-dashboard`.
- **Thi sát hạch trực tuyến**: `POST /api/exams/{exam}/attempts` (Bắt đầu làm bài), `PUT /api/exam-attempts/{attempt}/answer` (Lưu câu trả lời), `POST /api/exam-attempts/{attempt}/submit` (Nộp bài thi & tính điểm).

---

## 🗺️ 3. Đồ Thị Thực Thể Dữ Liệu Backend (Domain Graph)

```
Đơn Vị Hành Chính (Tỉnh/Huyện/Xã) ──> Tuyển Dụng ──> Vị Trí ──> Kiến Thức ──> Câu Hỏi ──> Kỳ Thi ──> Khóa Học
```

---

## 📋 4. Tiến Độ Thực Hiện Frontend WordPress (`theme/cong-vien-chuc`)

- [x] **Trang chủ Prototype 100% Parity**: Hoàn thiện `header.php`, `index.php`, `footer.php` khớp 100% DOM IDs (25), JS Functions (17), FontAwesome Icons (41), Headings (30).
- [x] **Cơ chế Cô lập CSS & Antialiased**: Nhúng `wp_head()` chuẩn, bảo vệ phông chữ `Plus Jakarta Sans`, `Dancing Script` và FontAwesome 6.
- [x] **Sửa khoảng cách & Hiệu ứng**: Khắc phục dính chữ nhãn tiêu đề (`inline-block mb-3`), loại bỏ `tracking-tight` cho chữ tiếng Việt, sửa chữ logo `CÔNG VIÊN CHỨC` không bị che mép, bỏ nhãn `PRO` và ISO references.
- [x] **Nổi bật tiêu đề & Giá khóa học**: In đậm các tiêu đề khóa học/tài liệu (`font-extrabold`), làm nổi bật giá học phí (`text-2xl font-black text-amber-600 drop-shadow-xs`), sửa chữ Hero Banner `text-white` không bị trùng nền tối.
- [x] **Hoàn thiện trọn bộ 18 Trang con (Sub-pages)**:
  - Danh sách & Chi tiết Khóa học (`/khoa-hoc/`, `/khoa-hoc/{slug}/`) — `200 OK`
  - Danh sách & Chi tiết Tuyển dụng (`/tuyen-dung/`, `/tuyen-dung/{slug}/`) — `200 OK`
  - Tiêu chuẩn Ngạch (`/chu-de/`) — `200 OK`
  - Kiến thức Bồi dưỡng (`/kien-thuc/`) — `200 OK`
  - Đề thi & Simulator Thi Trắc Nghiệm AI 60 câu (`/thi-trac-nghiem/`, `/thi-trac-nghiem/{slug}/`) — `200 OK`
  - Văn bản Pháp luật Hợp nhất (`/van-ban-phap-luat/`, `/van-ban-phap-luat/{slug}/`) — `200 OK`
  - Bảng điều khiển Cán bộ (`/tai-khoan/`) — `200 OK`
  - Trang Đăng nhập & Đăng ký (`/dang-nhap/`, `/dang-ky/`) — `200 OK`
  - Trang Tìm kiếm (`/tim-kiem/`) — `200 OK`

---

## ⚙️ 5. Trạng Thái Kiểm Tra Kỹ Thuật (Health Check)

- **Cú pháp PHP (`php -l`)**: `0 errors` trên toàn bộ 18 file template theme.
- **Phản hồi HTTP Server**: Tất cả các đường dẫn trang con đều trả về `HTTP/1.1 200 OK`.
- **Deep Audit Automated Check**: 100% khớp các ID, Hàm JS, Icon FA và Headings.
