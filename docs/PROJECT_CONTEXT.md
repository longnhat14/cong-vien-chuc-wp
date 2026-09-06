# PROJECT_CONTEXT.md — Công Viên Chức (congvienchuc.com)

> Tài liệu nguồn chuẩn (single source of truth) cho dự án Công Viên Chức, dùng làm ngữ cảnh khi làm việc với Claude Code / trong repository GitHub.
> Tổng hợp từ toàn bộ lịch sử thiết kế + audit thực tế (tính đến 2026-09-05). Thông tin chưa có bằng chứng trực tiếp được đánh dấu **CHƯA XÁC NHẬN**.

---

## 1. Mục tiêu và phạm vi dự án

**Tên dự án**: Công Viên Chức
**Domain**: congvienchuc.com — đã mua
**Định vị sản phẩm**: không phải "website đăng tin tuyển dụng", mà là **Vertical Platform về sự nghiệp công chức – viên chức** tại Việt Nam, kết hợp 4 trụ cột:
1. Tìm tin tuyển dụng (công chức, viên chức, theo tỉnh/xã/phường/cơ quan/vị trí)
2. Tìm hiểu kỳ thi (điều kiện, hồ sơ, quy trình, nội dung thi, văn bản pháp luật liên quan)
3. Ôn thi (ngân hàng câu hỏi, đề thi thử, flashcard, thi thử online, theo dõi tiến bộ)
4. Theo dõi cơ hội việc làm (theo tỉnh, xã/phường, ngành, vị trí, trình độ, hạn nộp)

**Điểm khác biệt cốt lõi (moat)**: không chỉ đăng lại thông báo tuyển dụng, mà liên kết chuỗi dữ liệu **Recruitment → Position → Kiến thức → Câu hỏi → Đề thi → Khóa học**, biến một tin tuyển dụng thành entry point vào toàn bộ hệ sinh thái học và thi. Đây là "Entity Graph" — tài sản dữ liệu mà đối thủ (congchuc247.vn, onthicongchuc.vn) không có.

**Chiến lược MVP**: không làm toàn quốc ngay. Ưu tiên xây database + website cho 1–3 tỉnh trước (định hướng ban đầu: Quảng Ninh → Hải Phòng → Hà Nội), nhóm nghề ưu tiên Công chức → Viên chức → Giáo viên → Văn thư → Kế toán, mục tiêu ban đầu 5.000–10.000 câu hỏi chất lượng.

**Thứ tự ưu tiên phát triển tổng thể**: Data → Search → Content → Quiz → AI → Monetization. Không bắt đầu bằng AI.

**Mô hình kiếm tiền (định hướng, LATER HAVE — chưa triển khai)**: Free (tin tuyển dụng, kiến thức cơ bản) → Premium subscription (49k–99k/tháng: ngân hàng câu hỏi đầy đủ, đề thi thử, AI Tutor) → bán khóa/bộ đề riêng lẻ → Affiliate.

---

## 2. Kiến trúc hệ thống hiện tại

### 2.1 Kiến trúc hạ tầng ĐÃ CHỐT
```
congvienchuc.com
        │
        ▼
     HOSTINGER (hosting duy nhất, không VPS/server riêng)
        │
 ┌──────┴─────────┐
 │                │
WordPress       Laravel Backend
SEO/CMS         API (tại api.congvienchuc.com)
 │                │
 │                ├── MySQL/MariaDB
 │                ├── Local File Storage (không dùng S3)
 │                ├── Database Queue (không dùng Redis)
 │                └── Hostinger Cron
 │
 └────── API ─────┘
                   │
                   ▼
             External AI APIs (GPT/Gemini/Claude/Qwen — gọi qua HTTPS, key chỉ ở server)
```

- **WordPress** phụ trách: SEO, landing page, blog, content trình bày công khai — KHÔNG sở hữu dữ liệu nghiệp vụ.
- **Laravel backend** là **source of truth** cho toàn bộ dữ liệu nghiệp vụ (Recruitment, Question, Exam, Course, User...).
- WordPress không được ghi trực tiếp vào database backend — mọi trao đổi qua REST API.
- AI **không phải nguồn sự thật**: chỉ diễn giải/tổng hợp/đề xuất, không tự ghi đè dữ liệu tuyển dụng/pháp lý đã publish.

### 2.2 Domain/thư mục trên Hostinger (đã chốt)
```
congvienchuc.com
│
├── WordPress               → public_html/
│   └── Public website / SEO / bài viết
│
└── api.congvienchuc.com    → apps/congvienchuc/public (document root)
    └── Laravel
        ├── API
        ├── Admin Backend
        ├── Auth / RBAC
        ├── Recruitment
        ├── Question Bank
        ├── Exam
        ├── Course
        ├── AI Jobs
        └── Queue / Cron
```

Cấu trúc thư mục Hostinger:
```
/home/account/
├── public_html/
│   └── WordPress
├── apps/
│   └── congvienchuc/     (Laravel app)
│       ├── app/
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── resources/
│       ├── routes/
│       ├── storage/
│       └── ...
└── backups/
```

**Nguyên tắc bắt buộc**: Laravel không đặt toàn bộ source code vào `public_html`. Chỉ thư mục `public` của Laravel được expose ra web.

**CHƯA XÁC NHẬN**: code Laravel hiện đang chạy ở môi trường nào cụ thể — local dev, VPS tạm, hay đã thực sự lên đúng `api.congvienchuc.com` trên Hostinger như kiến trúc đã chốt. Cần xác nhận trực tiếp trước khi coi production đã sẵn sàng.

### 2.3 Kiến trúc domain nghiệp vụ (6 Bounded Domains — theo rà soát code thực tế 2026-09-05)

| Domain | Thành phần |
|---|---|
| 1. Identity & Governance | User, Auth, Sanctum, Role, Permission, Audit |
| 2. Recruitment | Province, AdminUnit, Agency, Recruitment, Position |
| 3. Knowledge | LegalDocument, KnowledgeItem, ExamSubject, Topic, Question, QuestionOption, QuestionTag |
| 4. Examination | Exam, ExamSubject, ExamQuestion, ExamAttempt, ExamAttemptAnswer, UserExamProgress, UserQuestionProgress |
| 5. Learning | Course, CourseLesson, CourseEnrollment, CourseLessonProgress, LearningDashboard |
| 6. Platform Services (CHƯA HOÀN THIỆN) | Search, Notification, AI, Source, Settings, Payment, Certificate |

### 2.4 Luồng dữ liệu nghiệp vụ trung tâm
```
Legal Docs → Knowledge
Geography → Agency → Recruitment → Position → Exam Subject
    Exam Subject ↙ Topic → Question ← Attempt
                 ↘ Exam ← Attempt
    Topic → Course → Lesson → Learning
```
Một thông báo tuyển dụng có thể trở thành entry point vào toàn bộ hệ sinh thái học và thi — đây là kiến trúc dữ liệu cốt lõi cần bảo toàn khi phát triển thêm.

### 2.5 Data Pipeline tuyển dụng (thiết kế nghiệp vụ — mức triển khai thực tế CHƯA XÁC NHẬN, xem mục 9 "Source module")
```
SOURCE → INGEST → RAW → EXTRACT (AI, JSON schema cố định) → VALIDATE (rule engine độc lập)
→ DEDUP (duplicate score) → QA (human review) → PUBLISH → EXPIRE / UPDATE
```
Nguyên tắc: không cho AI lấy dữ liệu rồi xuất bản thẳng. Luôn lưu bản gốc trước khi AI xử lý. Chỉ nguồn TIER 1/2 (cơ quan nhà nước / cổng thông tin chính thức) được dùng làm nguồn chính để publish.

---

## 3. Database/schema và các module quan trọng

### 3.1 Dữ liệu địa lý (đã có, đã nạp dữ liệu thật)
- **Province**: 34 tỉnh/thành
- **AdminUnit**: 3.321 đơn vị cấp xã (2.621 Xã, 687 Phường, 13 Đặc khu), cấu trúc cây qua `parent_id` + `type` (không hard-code "tỉnh → huyện → xã" vì cấu trúc hành chính có thể thay đổi)

### 3.2 Recruitment domain (entity trung tâm — ĐÃ CODE, có E2E)
- **Agency**: `Province → AdminUnit → Agency`, có geography integrity check
- **Recruitment**: quan hệ `Agency, Province, AdminUnit, Positions, Exams, Courses`. Workflow trạng thái: `draft → review → published → expired`, action `submit/publish/expire`. Khi publish kiểm tra: agency, geography, position, total quantity, deadline.
- **Position**: thuộc `Recruitment`, có `quantity, job_description, requirements, education_level, major_requirements, experience_requirements, employment_type`; quan hệ với Exam Subjects và Courses. Position ↔ Exam Subject có pivot riêng + validation.

### 3.3 Knowledge domain (ĐÃ CODE — đã sửa nhận định sai trước đó rằng "chưa làm")
- **LegalDocument** → **KnowledgeItem** → **Topic** → **ExamSubject**
- Admin CRUD API đã có cho Legal Documents, Knowledge Items, Topics.
- Public API đã có: `GET /api/legal-documents`, `/{slug}`; `GET /api/knowledge-items`, `/{slug}`; `GET /api/topics`, `/{slug}`.

### 3.4 Question Bank (ĐÃ CODE, đã test — theo yêu cầu người dùng: KHÔNG test thêm ở thời điểm hiện tại)
- **Question**: quan hệ `Exam Subject, Topic, Knowledge Item, Options, Tags, Exams, User Question Progress`.
- Hierarchy enforced bắt buộc: `Exam Subject → Topic → Knowledge → Question`. Question không thể bị gắn sai Subject/Topic/Knowledge.

### 3.5 Examination domain (ĐÃ CODE, có E2E)
- **Exam**: quan hệ `Exam Subjects, Questions, Recruitments, Courses, Attempts`; pivot tables: `exam_subjects, exam_question, exam_recruitment`.
- Integrity quan trọng: Question đưa vào Exam phải thuộc một Subject mà Exam đã cấu hình.
- **ExamAttempt**: flow `Published Exam → Start Attempt → Attempt Answers → Answer → Submit → Calculate Score → UserExamProgress`. Có kiểm tra ownership và integrity.
- **UserExamProgress**: theo dõi tiến độ thi riêng.

### 3.6 Learning domain (ĐÃ CODE, có E2E — mốc quan trọng đã xác nhận)
- **Course**: quan hệ `Recruitment, Position, Exam, Exam Subject, Topic, Lesson, Enrollment, Lesson Progress`. Enforce chuỗi `Recruitment → Position → Exam → Subject → Topic` — không cho tạo quan hệ Course vô nghĩa.
- **CourseLesson**: hierarchy, publish status, `lesson_count` sync.
- **CourseEnrollment**: enroll, duplicate handling, preserve completed state, có race-condition handling.
- **CourseLessonProgress**: update progress, completion, enrollment roll-up, composite FK integrity.
- Course workflow: `submit/publish/unpublish`; publish yêu cầu có ít nhất 1 lesson đã published.
- **Mốc E2E đã xác nhận thực tế**: Course #25 → Lesson #15 → Publish → User #10 Enroll → Enrollment #9 → Start → Progress #8 → 100% → Enrollment completed.

### 3.7 Bảng nền tảng khác (ĐÃ CODE)
- **audit_logs** + `AuditLogService` — mọi module CRUD ghi audit.
- Permission/Role tables cho RBAC (xem mục 4.3).

### 3.8 Module CHƯA XÁC NHẬN implementation đầy đủ (permission namespace tồn tại, chưa rõ code)
User Management, Notification, AI module, Source module, Settings module, Admin Audit UI/API, Search/Discovery.

### 3.9 Module CHƯA LÀM (không có bằng chứng)
Payments/Orders (Course có `price`, `sale_price` nhưng chưa có `Order/Payment/Transaction/Entitlement/Refund/Invoice`), Certificates, generic File/Attachment module, SEO metadata fields riêng.

### 3.10 Khoảng trống nghiệp vụ lớn nhất: **Recruitment Dossier**
Các mảnh ghép (Recruitment, Position, Exam Subject, Question, Course, Legal Document) đều đã có, nhưng **chưa có một lớp Dossier thực sự gom lại** thành:
```
Recruitment
 ├── thông tin tuyển dụng / cơ quan / địa phương
 ├── vị trí (chỉ tiêu, yêu cầu, môn thi)
 ├── văn bản pháp lý
 ├── kiến thức
 ├── câu hỏi
 ├── đề thi
 └── khóa học
```
Đây là tính năng tạo khác biệt sản phẩm quan trọng nhất còn thiếu.

---

## 4. Công nghệ/framework đang sử dụng

| Thành phần | Công nghệ ĐÃ CHỐT |
|---|---|
| Hosting | Hostinger (Web Hosting/Unlimited — gói cụ thể CHƯA XÁC NHẬN) |
| Backend framework | Laravel (bản Laravel 12-style theo audit) |
| PHP version | PHP 8.3 |
| Database | MySQL/MariaDB |
| Auth | Laravel Sanctum (`HasApiTokens`, middleware `auth:sanctum`) |
| Queue | Database Queue (bảng `jobs`: pending/processing/completed/failed) — KHÔNG dùng Redis/Horizon ở giai đoạn hiện tại |
| Scheduler | Hostinger Cron → Laravel Scheduler |
| Storage | Local Filesystem trên Hostinger (KHÔNG dùng Amazon S3 / object storage riêng) |
| CMS/SEO | WordPress (ở `public_html`) |
| API | REST, `auth:sanctum` cho route bảo vệ |
| AI | External AI APIs (GPT/Gemini/Claude/Qwen) gọi qua AI Gateway — KHÔNG chạy model AI trên hosting |
| PHP extensions xác nhận có sẵn | PDO MySQL, mbstring, bcmath, curl, fileinfo, intl, OPcache |

**KHÔNG sử dụng** (đã loại khỏi kiến trúc, chỉ thêm lại khi quy mô bắt buộc): AWS S3, VPS/server riêng, Kubernetes, Microservices phức tạp, Docker (ở MVP hiện tại), Next.js (ở MVP hiện tại — để phase sau).

**Giới hạn hosting đã biết**: `artisan about` không chạy được do hosting disable `proc_open`/Symfony Process — không phải lỗi Laravel core, nhưng cần ghi nhận khi làm production hardening/health-check.

---

## 5. Các quy tắc coding

> **CHƯA XÁC NHẬN** — dự án chưa có bộ coding convention/style guide chính thức được ghi lại trong lịch sử thiết kế (PSR standard, naming convention, cấu trúc thư mục Laravel chi tiết, quy tắc test, v.v.). Các mục dưới đây là những ràng buộc kỹ thuật **đã được nêu rõ và cần tuân thủ**, không phải một style guide đầy đủ:

- **Không cho WordPress ghi trực tiếp vào database backend** — mọi thao tác qua REST API.
- **Không cho AI trả dữ liệu tự do** khi extraction — bắt buộc JSON schema cố định.
- **AI không được tự ý sửa/ghi dữ liệu production** (Recruitment, Legal Document đã publish) nếu chưa qua rule validation + QA con người.
- **Không import câu hỏi/dữ liệu thẳng vào trạng thái Published** — luôn qua `Import → Preview → Validate → Duplicate Detection → QA → Publish`.
- **Không copy câu hỏi vào từng đề thi** — dùng bảng pivot (`exam_question`) để một câu hỏi dùng lại được ở nhiều đề.
- **Không ghi đè lịch sử thay đổi dữ liệu quan trọng** (đặc biệt Recruitment) — cần giữ audit trail qua `audit_logs`, không update trực tiếp mất version cũ.
- **Không hard-code cấu trúc hành chính "tỉnh → huyện → xã"** — dùng `parent_id` + `type` linh hoạt.
- **API route organization**: lịch sử `routes/api.php` từng bị append thủ công nhiều lần, có file backup (`routes/api.php.bak`, `routes/api.php.before-...`), từng có duplicate `QuestionController` route block. **Khuyến nghị đã ghi nhận**: refactor route organization sau khi domain ổn định, tránh tiếp tục append route thủ công không kiểm soát.
- **Resource Guard bắt buộc cho job AI/crawler** (vì chạy trên hosting chia sẻ): mỗi job cần giới hạn `max_execution_time, max_file_size, max_pages, max_tokens, max_retries`; lỗi phải vào `FAILED → Retry → Dead Letter`, không được treo vô hạn.

---

## 6. Các quy tắc nghiệp vụ

- **Recruitment không có nguồn (source) → không được publish.** Mọi tin tuyển dụng phải biết "lấy từ đâu" (`sources` với `trust_level`: TIER 1 = cơ quan nhà nước, TIER 2 = cổng thông tin chính thức, TIER 3 = tham khảo). Chỉ TIER 1/2 dùng làm nguồn chính để publish.
- **Recruitment workflow bắt buộc theo trình tự**: `draft → review → published → expired`, không nhảy bước.
- **Khi publish Recruitment phải kiểm tra**: agency, geography, position, total quantity, deadline, dữ liệu liên quan hợp lệ.
- **Question phải nằm đúng hierarchy**: Exam Subject → Topic → Knowledge → Question. Không được gắn sai.
- **Question đưa vào Exam phải thuộc một Subject mà Exam đã cấu hình** (integrity constraint đã code).
- **Course phải enforce chuỗi quan hệ nghiệp vụ**: Recruitment → Position → Exam → Subject → Topic — không tạo quan hệ Course vô nghĩa (không liên kết ngẫu nhiên).
- **Course publish yêu cầu có ít nhất 1 lesson đã published.**
- **Tự động hết hạn Recruitment**: khi qua `application_deadline` → chuyển `PUBLISHED → EXPIRED`. **Không xóa** — giữ trang để phục vụ SEO/lịch sử/tham khảo, hiển thị cảnh báo "đã hết hạn nhận hồ sơ".
- **Update Detection**: khi nguồn chính thức đăng thông báo sửa đổi (VD: đổi hạn nộp), hệ thống phải phát hiện thay đổi, đưa qua QA rồi mới cập nhật — không tự động ghi đè.
- **Duplicate Detection**: một thông báo có thể xuất hiện ở nhiều nguồn (UBND, Sở Nội vụ, Cổng thông tin tỉnh...) nhưng chỉ là một đợt tuyển dụng — cần tính duplicate score (Title similarity + Agency + Deadline + Position + Document number) trước khi tạo recruitment mới.
- **Phân quyền theo permission, không chỉ theo role cứng**: 6 role (`SUPER_ADMIN, ADMIN, EDITOR, REVIEWER, INSTRUCTOR, USER`) + 72 permissions theo namespace, để có thể phân công người A chỉ duyệt câu hỏi, người B chỉ quản lý tuyển dụng, v.v.
- **Question module đã chốt trạng thái**: không test thêm ở thời điểm hiện tại theo yêu cầu người dùng — giữ nguyên, không tự ý thay đổi hoặc viết thêm test cho module này trừ khi được yêu cầu lại.

---

## 7. Những quyết định kiến trúc đã chốt

| # | Quyết định | Trạng thái |
|---|---|---|
| 1 | Domain: **congvienchuc.com** | ✅ Đã mua |
| 2 | Backend riêng, tách khỏi WordPress (WordPress không sở hữu dữ liệu nghiệp vụ) | ✅ Chốt |
| 3 | Hosting: **Hostinger**, một hosting duy nhất (không VPS/server riêng ở MVP) | ✅ Chốt |
| 4 | Backend framework: **Laravel** (thay FastAPI ban đầu) | ✅ Chốt |
| 5 | Database: **MySQL/MariaDB** (thay PostgreSQL ban đầu) | ✅ Chốt |
| 6 | Storage: **Local Filesystem Hostinger** (không dùng Amazon S3) | ✅ Chốt |
| 7 | Queue: **Database Queue** (không dùng Redis/Celery/Horizon ở MVP) | ✅ Chốt |
| 8 | Scheduler: **Hostinger Cron** | ✅ Chốt |
| 9 | Frontend MVP: chỉ **WordPress**, chưa xây Next.js riêng | ✅ Chốt (Next.js để phase sau) |
| 10 | AI: gọi **External AI API**, không chạy model trên hosting; AI key chỉ lưu server-side | ✅ Chốt |
| 11 | AI **không phải nguồn sự thật** — chỉ là lớp diễn giải, không tự ghi dữ liệu production | ✅ Chốt |
| 12 | `StorageService` và Queue phải tách lớp trừu tượng để sau này đổi implementation (Local→CDN, DB Queue→Redis) mà không viết lại hệ thống | ✅ Chốt (nguyên tắc kiến trúc) |
| 13 | Điều kiện nâng cấp lên VPS: chỉ khi AI processing hàng loạt liên tục / hàng nghìn job/ngày / crawler chạy thường xuyên / cần Redis-Horizon / video traffic lớn / CPU-RAM quá tải — KHÔNG phải khi chỉ có nhiều người dùng | ✅ Chốt |
| 14 | Đội ngũ triển khai: dùng Claude tự viết code (không phải đội intern Cursor/Flutter theo mô hình thông thường của team) | ✅ Đã xác nhận |

---

## 8. Những việc đã hoàn thành

**Đã code + có bằng chứng test/E2E (27 module, theo audit 2026-09-05 — KHÔNG CẦN XÂY LẠI):**

Hạ tầng Laravel/API, Auth + Sanctum, RBAC (6 role/72 permission), Audit Log, Province/AdminUnit (34 tỉnh, 3.321 đơn vị), Agency, Recruitment (CRUD + workflow), Position, Exam Subject, Position↔Exam Subject pivot, Topic (hierarchical), Question (options/tags/hierarchy — đã chốt không test thêm), Exam (workflow + integrity), Exam Attempt, Course (CRUD + relation integrity), Course Lesson, Course Workflow, Course Enrollment, Course Lesson Progress, Public Course API.

**Mốc E2E xác nhận**: Course #25 → Lesson #15 → User #10 → Enrollment #9 → Progress #8 → completed 100%.

**Chốt rõ (không nên xây lại)**: ❌ Knowledge, ❌ Legal Document, ❌ Question, ❌ Exam core, ❌ Course, ❌ Enrollment, ❌ Lesson Progress, ❌ tiếp tục test Question.

**API hiện có**: 96 routes, chia 3 nhóm: Public (`/api/courses, /api/course-lessons, /api/recruitments, /api/topics, /api/knowledge-items, /api/legal-documents, /api/exams`), User authenticated (`/api/course-enrollments, /api/course-lesson-progress, /api/my-courses, /api/my-learning-dashboard, /api/exam-attempts, /api/exam-progress`), Admin authenticated (`/api/admin/recruitments, /api/admin/positions, /api/admin/exam-subjects, /api/admin/topics, /api/admin/knowledge-items, /api/admin/legal-documents, /api/admin/questions, /api/admin/exams, /api/admin/courses, /api/admin/course-lessons`).

---

## 9. Những việc đang làm / cần làm tiếp theo (theo thứ tự ưu tiên đã chốt)

1. **Live Architecture Audit trực tiếp trên server** — việc ưu tiên TRƯỚC CẢ Recruitment Dossier. Lý do: nhiều module (User Management, Notification, AI, Source, Settings, Admin Audit UI/API, Search) mới chỉ thấy permission/route trong snapshot cũ, chưa có bằng chứng khẳng định implementation hoàn chỉnh. Sau bước này mới có "Architecture Baseline v1.0" chính thức và tránh xây trùng.
2. **Hoàn thiện Recruitment Dossier** (xem mục 3.10) — khoảng trống nghiệp vụ lớn nhất, biến Công Viên Chức thành nền tảng dữ liệu thật thay vì website đăng tin.
3. Rà lại RBAC topic permission — có lịch sử lỗi trong snapshot cũ, cần xác nhận đã sửa dứt điểm.
4. Quyết định có cần Payments/Orders, Notification, Certificates ở giai đoạn MVP hiện tại hay để sau (theo phân loại MUST/SHOULD/LATER HAVE đã chốt).
5. Xác nhận môi trường code hiện tại đang chạy ở đâu (local/VPS tạm/Hostinger thật) — đối chiếu với kiến trúc hạ tầng đã chốt.
6. Bổ sung automated Feature tests (hiện chủ yếu manual curl/tinker regression).
7. Hoàn tất security/performance pass cho production hardening.
8. Refactor `routes/api.php` sau khi domain ổn định (tránh append thủ công tiếp).

**Thứ tự phát triển tổng thể đã chốt**:
```
Backend Core (đã khá hoàn chỉnh)
   │
   ├─→ Recruitment Dossier ─→ Search ─┐
   │                                   ├─→ Admin CMS ─→ AI Pipeline ─→ Payment/Certificate
   └─→ Audit domain phụ (User/Notification/Source/Settings) ─┘
```

---

## 10. Những lỗi/vấn đề đang tồn tại

| Vấn đề | Mức độ | Ghi chú |
|---|---|---|
| `artisan about` không chạy | Hạ tầng | Do hosting disable `proc_open`/Symfony Process — không phải lỗi Laravel core |
| RBAC topic permission | CHƯA XÁC NHẬN đã sửa dứt điểm | Có lịch sử lỗi trong snapshot cũ, cần live-check |
| `routes/api.php` append thủ công lặp lại | Kỹ thuật (nợ kỹ thuật) | Từng có duplicate `QuestionController` route block; có file backup `.bak`, `.before-...` còn tồn tại — cần dọn dẹp/refactor |
| Automated test coverage thấp | Kỹ thuật | Chủ yếu dựa vào manual curl/tinker regression, chưa có Feature test tự động đầy đủ |
| Môi trường chạy code thực tế | CHƯA XÁC NHẬN | Chưa rõ code đang chạy ở đâu (local/VPS tạm/Hostinger thật `api.congvienchuc.com`) |
| Thông số gói Hostinger cụ thể | CHƯA XÁC NHẬN | CPU, RAM, dung lượng, số database, PHP workers, SSH, giới hạn process/file — cần xác nhận trước khi hoàn tất production hardening |
| Rủi ro pháp lý crawl dữ liệu nhà nước | Chưa xử lý | Chưa rõ điều khoản sử dụng của các cổng thông tin nguồn, chưa có disclaimer pháp lý thiết kế cho từng trang tuyển dụng |
| Ai đảm nhiệm vai trò Reviewer thật | Chưa xác định | Cho dữ liệu tuyển dụng/câu hỏi trước khi publish ở quy mô vận hành thật |

---

## 11. Những điều Claude KHÔNG được tự ý thay đổi

- **Không xây lại các module đã chốt "không cần xây lại"**: Knowledge, Legal Document, Question, Exam core, Course, Enrollment, Lesson Progress (xem mục 8).
- **Không tiếp tục viết thêm test cho module Question** — người dùng đã yêu cầu dừng test module này ở thời điểm hiện tại.
- **Không cho AI (bao gồm chính Claude khi vận hành AI pipeline) tự ý ghi/sửa dữ liệu Recruitment hoặc Legal Document đã ở trạng thái published** mà không qua rule validation + QA con người.
- **Không import dữ liệu (câu hỏi, tuyển dụng...) thẳng vào trạng thái Published** — luôn phải qua luồng Preview → Validate → Duplicate Detection → QA → Publish.
- **Không đổi lại stack hạ tầng đã chốt** (Hostinger/Laravel/MySQL/Local Storage/Database Queue) nếu không có yêu cầu rõ ràng và xác nhận lại — đây là quyết định đã chốt sau nhiều lần điều chỉnh từ bản dự thảo ban đầu (FastAPI/PostgreSQL/Redis/S3/Docker).
- **Không tự ý nâng cấp lên VPS/Redis/Docker** trừ khi rơi vào các điều kiện đã liệt kê ở mục 7 (#13).
- **Không cho WordPress ghi trực tiếp vào database backend.**
- **Không hard-code cấu trúc hành chính** ("tỉnh → huyện → xã") — luôn dùng `parent_id` + `type`.
- **Không xóa Recruitment khi hết hạn** — chỉ chuyển trạng thái `EXPIRED`, giữ lại để phục vụ SEO/lịch sử.
- **Không ghi đè lịch sử thay đổi dữ liệu quan trọng** mà không lưu audit trail.
- **Không tự ý publish thông tin pháp lý/điều kiện tuyển dụng do AI trích xuất mà chưa qua kiểm duyệt con người** — sai sót ở đây có thể ảnh hưởng trực tiếp người nộp hồ sơ và uy tín toàn hệ thống.
- **CHƯA XÁC NHẬN** thêm về style/convention code cụ thể (PSR, naming, cấu trúc thư mục Laravel chi tiết) — vì chưa từng được ghi nhận chính thức, Claude nên hỏi lại hoặc theo pattern đã có trong codebase hiện tại thay vì tự đặt chuẩn mới.

---

## 12. Quy trình làm việc với Git/GitHub

> **CHƯA XÁC NHẬN — TOÀN BỘ MỤC NÀY.** Lịch sử dự án cho đến nay chưa từng thiết lập quy trình Git/GitHub chính thức (chưa có thông tin về: tên repository, branch strategy, quy tắc commit message, quy trình pull request/review, CI/CD, môi trường staging/production tách biệt qua Git). Đây là lý do người dùng đang chuyển sang Claude Code + GitHub — quy trình cụ thể cần được thiết lập và ghi lại bổ sung vào tài liệu này sau khi quyết định.

Việc cần làm khi thiết lập: xác định repo GitHub (mới hay đã có), branch chính (`main`/`master`), quy tắc đặt tên branch, commit convention, có dùng PR review không, CI pipeline (chạy test tự động?), cách deploy từ Git lên Hostinger (deploy thủ công qua FTP/SSH hay có webhook/action?).

---

## 13. Các lệnh thường dùng

**Đã xác nhận có sử dụng** (từ audit production hardening):
```bash
php artisan config:cache
php artisan route:list
php artisan migrate
php artisan optimize:clear
```

**CHƯA XÁC NHẬN**: `php artisan about` được ghi nhận là **không chạy được** trên hosting hiện tại (do `proc_open` bị disable) — không dùng lệnh này để kiểm tra môi trường.

**CHƯA XÁC NHẬN**: các lệnh test cụ thể, lệnh seed database, lệnh deploy — chưa có trong lịch sử tài liệu, cần bổ sung khi thiết lập quy trình Git/CI.

---

## 14. Các tài liệu/file quan trọng cần biết

### Tài liệu thiết kế/kiến trúc (nguồn: Claude Project "CongVienChuc")
| File | Nội dung |
|---|---|
| `y-tuong-tong-quan.md` | Ý tưởng tổng thể, moat, roadmap 4 phase |
| `thuong-hieu-ten-mien.md` | Thương hiệu, domain, phân tích đối thủ |
| `blueprint-v1-backend-architecture.md` | Sitemap, quyết định backend riêng (bản dự thảo ban đầu) |
| `database-schema-v1.md` | Database schema chi tiết (bản thiết kế ban đầu, PostgreSQL) |
| `api-contract-v1.md` | API contract v1 (endpoint design ban đầu) |
| `admin-dashboard-v1.md` | Thiết kế Admin Dashboard |
| `data-pipeline-tuyendung.md` | Data pipeline tuyển dụng (thiết kế nghiệp vụ) |
| `ux-trang-tuyendung.md` | UX trang tuyển dụng chi tiết |
| `blueprint-mvp-tong-hop.md` | Tổng hợp 15 module MVP, Entity Graph, MUST/SHOULD/LATER HAVE |
| `thiet-ke-ky-thuat-trien-khai.md` | Thiết kế kỹ thuật (stack ban đầu FastAPI — đã thay bằng Laravel) |
| `ghi-chu-rang-buoc-ha-tang.md` | Ràng buộc hạ tầng: không S3, không server riêng |
| `kien-truc-hosting-thuc-te.md` | Kiến trúc ĐÃ CHỐT: Hostinger + Laravel + MySQL |
| `trien-khai-hostinger-cac-buoc.md` | Các bước triển khai thực tế trên Hostinger |
| `tong-hop-danh-gia-du-an.md` | Tổng hợp & nhận xét độc lập (viết trước audit backend) |
| `tinh-trang-backend-hien-tai.md` | Audit 44 module dạng bảng (nguồn: file Excel `CONG_VIEN_CHUC_BACKEND_AUDIT_20260905.xlsx`) |
| `kien-truc-thuc-te-chi-tiet.md` | **Tài liệu mới nhất** — rà soát chi tiết theo code/model/controller/96 API routes thực tế |

### File hệ thống Laravel đã biết đến (đường dẫn tương đối trong app Laravel)
- `routes/api.php` — có lịch sử append thủ công nhiều lần, có file backup `routes/api.php.bak`, `routes/api.php.before-...` (CHƯA XÁC NHẬN các file backup này còn tồn tại trong repo hiện tại hay đã dọn).
- Cấu trúc chuẩn Laravel: `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/` (đã xác nhận qua cấu trúc thư mục Hostinger).

### Nguồn dữ liệu audit
- `CONG_VIEN_CHUC_BACKEND_AUDIT_20260905.xlsx` — do người dùng cung cấp, audit date 2026-09-05, dựa trên code/log/route snapshot và các E2E đã thực hiện.

**CHƯA XÁC NHẬN**: đường dẫn file cụ thể trong máy chủ thật ngoài những gì đã liệt kê ở mục 2.2 (cấu trúc thư mục Hostinger), tên các Model/Controller class cụ thể trong code (chỉ biết tên entity nghiệp vụ, chưa chắc trùng khớp 100% tên class PHP thực tế).

---

## 15. Những context quan trọng khác Claude cần biết để tiếp tục dự án

- **Người phụ trách dự án (Nhat Quang) là Product Owner, không phải developer.** Không tự viết code — toàn bộ code do Claude viết. Điều này có nghĩa: (1) không có review code độc lập bởi một lập trình viên con người trước khi lên production trừ khi được thiết lập riêng; (2) các giải thích kỹ thuật nên đủ rõ ràng để một người không chuyên sâu về code vẫn nắm được quyết định quan trọng; (3) các module nhạy cảm (Auth, RBAC, Payment sau này, File upload, AI extraction ghi vào DB) nên có một bước tự-kiểm-tra/review kỹ hơn bình thường vì không có "tuyến phòng thủ" thứ hai từ con người.
- **Đội ngũ công ty nói chung (theo hồ sơ người dùng) thường dùng Cursor AI để generate code Flutter** cho các dự án khác — nhưng dự án Công Viên Chức cụ thể này đi theo hướng khác: Claude code Laravel/PHP trực tiếp. Không nhầm lẫn hai mô hình làm việc này.
- **Ngôn ngữ làm việc chính**: Tiếng Việt. Toàn bộ thuật ngữ nghiệp vụ, tên bảng, tên trạng thái trong tài liệu này giữ nguyên như đã thống nhất, không tự dịch hay đổi tên.
- **Liên quan đến hệ sinh thái PCTech**: dự án này được thiết kế để có thể dùng chung tư duy AI/Content/Automation với hệ thống PCTech Platform (SEO Engine, AI Content, Automation) đã có sẵn — đặc biệt là PCTech Keyword Admin/Content Studio cho phần content/SEO tự động. Content Studio và AI Content Pipeline (Context/Entity/Internal Link→AI Content→FAQ→Schema→Publisher) là **roadmap riêng của PCTech**, KHÔNG được tính là đã hoàn thành trong backend Công Viên Chức nếu chưa có code sống ở đây — tránh nhầm lẫn hai hệ thống khi báo cáo tiến độ.
- **Rủi ro chưa xử lý cần Claude lưu ý khi làm tiếp**: (1) pháp lý khi crawl/republish dữ liệu từ cổng thông tin nhà nước — chưa có disclaimer thiết kế; (2) chi phí AI API vận hành production (khác với chi phí dùng Claude để phát triển) chưa được ước tính; (3) gánh nặng vận hành QA thủ công cho quy mô 5.000–10.000 câu hỏi/1-3 tỉnh chưa rõ ai đảm nhiệm.
- **Nguyên tắc làm việc đã áp dụng trong giai đoạn triển khai hạ tầng**: mỗi bước cấu hình hosting (subdomain, PHP, SSH...) cần xác nhận qua ảnh chụp màn hình thực tế trước khi tiến tiếp, không tự ý đoán/chọn cấu hình khi giao diện Hostinger không rõ ràng. Nguyên tắc thận trọng tương tự nên áp dụng khi thao tác trực tiếp trên môi trường production sau này.
- **Quyết định về việc "sửa nhận định"**: Trong lịch sử audit, có lúc Knowledge/Legal Document bị đánh giá nhầm là "chưa làm" rồi sau đó xác nhận lại là "đã có". Bài học: khi đánh giá tiến độ, ưu tiên bằng chứng trực tiếp từ code/route/migration hơn là suy đoán từ tài liệu thiết kế hoặc từ việc "chưa thấy test" — thiếu test không đồng nghĩa thiếu implementation.
- **File Excel audit gốc** (`CONG_VIEN_CHUC_BACKEND_AUDIT_20260905.xlsx`) có 3 sheet: `Backend Audit` (44 dòng chi tiết), `Summary` (tổng số theo nhóm), `Notes` (ghi chú audit, bao gồm dòng "Người dùng đã yêu cầu không test thêm Question").

---

*Tài liệu được tổng hợp bởi Claude từ toàn bộ lịch sử thiết kế + audit dự án Công Viên Chức, tính đến 2026-09-05. Không có thông tin nào trong tài liệu này được suy đoán ngoài những gì đã được nêu rõ trong các trao đổi trước đó — mọi điểm chưa có bằng chứng trực tiếp đã được đánh dấu CHƯA XÁC NHẬN.*
