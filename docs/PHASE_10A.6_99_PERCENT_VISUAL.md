# Phase 10A.6 — 99% Visual Fidelity Homepage

Mục tiêu phiên này khác các phiên trước: thay vì chỉ "cùng phong cách"
với `docs/ui-benchmark/homepage-reference.png`, chủ động lấp đầy khoảng
cách mật độ thị giác còn lại (statistics strip, course showcase 3 card,
recruitment sidebar đầy) bằng **design fixture có kiểm soát chặt** - điều
mà 4 phiên trước (10A.2-10A.5) cố tình KHÔNG làm vì ưu tiên tuyệt đối
"không data giả". Phiên này người dùng đã tường minh ủy quyền dùng
fixture, với ranh giới rất rõ (xem §1).

## 1. Ranh giới REAL DATA / DESIGN FIXTURE / VISUAL ASSET

Đây là quyết định kiến trúc quan trọng nhất phiên này. Đã dựng thành 1
file riêng biệt, không lẫn với code xử lý API thật:

**`theme/cong-vien-chuc/inc/homepage-fixtures.php`** - toàn bộ design
fixture nằm ở đây, với các ràng buộc cứng ghi ngay trong docblock đầu
file:

- Không bao giờ ghi vào database (WP lẫn Laravel).
- Không route/template nào khác được `require` file này ngoài `index.php`.
- Không được tính vào search/recommendation/sitemap/analytics.
- REAL DATA luôn thắng - fixture chỉ ĐIỀN THÊM khi dữ liệu thật thiếu,
  không bao giờ ghi đè.
- Chỉ hoạt động khi `CVC_HOMEPAGE_DEMO_CONTENT === true`.
- Mọi phần tử fixture hiển thị ra HTML đều có badge "Demo".

## 2. Feature flag - Production Safety (Phần 4/33)

```php
function cvc_homepage_demo_enabled(): bool {
    return defined( 'CVC_HOMEPAGE_DEMO_CONTENT' ) && true === CVC_HOMEPAGE_DEMO_CONTENT;
}
```

Fail-safe về `false` nếu hằng số chưa từng được khai báo ở đâu (production
không set biến này → luôn tắt, đúng yêu cầu Phần 33 "nếu biến không tồn
tại: false"). Local dev bật qua `docker-compose.yml`
(`WORDPRESS_CONFIG_EXTRA`), theo đúng pattern đã có sẵn cho `CVC_DEV_TOOLS`/
`CVC_API_BASE_URL` - không phát minh cơ chế mới.

Mỗi hàm render chịu ảnh hưởng (`cvc_render_homepage_statistics_strip()`)
tự kiểm tra lại cờ **ngay trong thân hàm** (phòng thủ kép) - kể cả khi bị
gọi nhầm từ đâu đó, vẫn không bao giờ render fixture nếu cờ tắt.

## 3. Fallback strategy (Phần 5/31) - triển khai thật trong `index.php`

```
REAL API DATA (list() thật)
    ↓ (nếu rỗng/thiếu VÀ demo mode bật)
DESIGN FIXTURE (điền thêm cho đủ, KHÔNG thay thế)
    ↓ (nếu demo mode tắt)
Premium empty state / featured-single-card (đã có từ 10A.4/10A.5)
```

Áp dụng cụ thể:

- **Course**: nếu real course < 3 VÀ demo bật → điền thêm fixture cho đủ
  3 (real luôn đứng trước). Nếu demo tắt và chỉ có 1 course thật → vẫn
  dùng layout "featured" ngang đã xây ở 10A.5 (production-safe, không
  cần fixture).
- **Recruitment**: nếu real rỗng VÀ demo bật → dùng 4 tin fixture. Nếu
  có dù chỉ 1 tin thật → real luôn thắng, không bao giờ trộn với fixture.
- **Statistics**: không có real aggregate API nào - toàn bộ strip chỉ
  render khi demo bật, không có nhánh "real" (đã ghi rõ trong code).

## 4. Nhãn "Demo" (Phần 17) - áp dụng nhất quán

`cvc_render_demo_badge()` - 1 hàm dùng chung, gắn vào MỌI nơi fixture
xuất hiện: course card fixture (góc ảnh), recruitment fixture (cạnh badge
trạng thái), toàn bộ statistics strip (nhãn ngay đầu block, tách bằng
đường viền đứt).

Course/recruitment fixture **không có slug thật** - `cvc_render_course_card()`
và `cvc_render_recruitment_list_item()` đã sửa để khi phát hiện
`$item['_is_demo']`, CTA trỏ về đúng trang danh sách thật
(`/khoa-hoc/`, `/tuyen-dung/`) thay vì suy đoán 1 URL chi tiết không tồn
tại - **không tạo dead link dù là nội dung demo**.

## 5. Xác nhận ranh giới fixture-vs-database (đã test thật)

```
curl /khoa-hoc/  → không có badge "Demo", không có 2 course fixture
curl /tuyen-dung/ → không có badge "Demo", không có 4 tin fixture
```

Xác nhận fixture chỉ tồn tại trên response HTML của `/`, không rò rỉ sang
bất kỳ trang thật nào khác - đúng đảm bảo Phần 32 "Demo Fixture Boundary".

## 6. Vẫn giữ nguyên ranh giới KHÔNG được vượt qua (Phần 22/43)

Dù được nới lỏng fixture, **Community/testimonial section vẫn KHÔNG được
tái tạo** - Phần 22/43 của chính prompt này vẫn cấm rõ "fake testimonials/
avatar/review trình bày như thật", tách biệt hẳn với design fixture cho
course/recruitment/statistics (vốn là nội dung sản phẩm trung tính, không
giả lập người dùng thật). Tiếp tục bỏ section "Cộng đồng học tập" như đã
quyết định từ 10A.5.

## 7. Icon system - hoàn thiện danh sách tối thiểu (Phần 7)

Thêm vào thư viện `cvc_render_icon()` (đã có ~22 icon từ 10A.5): `search`,
`bell` (mới dùng ở header). Rà lại danh sách tối thiểu yêu cầu - đã có đủ:
home, courses, recruitments, document, knowledge, exams, target(goal),
briefcase(career), route(learning/progress), users(community), search,
star(bookmark), check, calendar, pin(location), book, courses(graduation
dùng chung glyph mũ tốt nghiệp), building(agency/civil-service), trending
(chart).

## 8. Screenshot QA (Docker + Puppeteer, kế thừa từ 10A.5)

Dùng lại nguyên giải pháp đã xác lập ở Phase 10A.5 (`docs/PHASE_10A.5_CONVERSION_UX.md §1`).
Đã chụp lại **trước và sau** khi thêm fixture ở 1440px và 390px:

- **Trước fixture**: course section chỉ 1 card (Phase 10A.5 baseline),
  recruitment sidebar trống ("Chưa có tin phù hợp"), không có statistics.
- **Sau fixture**: course section đủ 3 card (1 thật + 2 demo rõ nhãn),
  recruitment sidebar đủ 4 dòng (demo rõ nhãn), statistics strip xuất
  hiện với nhãn "Demo · Số liệu minh họa" - mật độ thị giác khớp ảnh
  benchmark rõ rệt hơn hẳn, không còn cảm giác "trống" ở 2 khu vực này.
- Mobile (390px, nhiều vị trí cuộn): value strip + statistics wrap đúng,
  course card 1 cột, không tràn ngang ở bất kỳ vị trí nào đã kiểm tra.

## 9. QA khác

- PHP lint toàn bộ theme, CSS brace-balance, `git diff --check` sạch.
- HTTP thật (`curl`) toàn bộ trang public + dashboard đã đăng nhập - 200,
  0 PHP warning/fatal (kể cả sau khi container `cvc-wp` được recreate để
  nạp biến môi trường mới - đã xác nhận lại bind-mount nạp đúng file mới
  trước khi test, tránh lặp lại sự cố bind-mount rỗng đã gặp ở phiên
  trước đó trong dự án).
- Regression: `/khoa-hoc/`, `/tuyen-dung/`, `/thi-trac-nghiem/` +slug,
  `/kien-thuc/`, `/chu-de/`, `/van-ban-phap-luat/`, `/tim-kiem/`,
  `/dang-nhap/`, `/dang-ky/`, `/tai-khoan/` + `muc-tieu` - toàn bộ 200.

## 10. Known limitations

- Course artwork vẫn là SVG placeholder theo màu category, không phải
  ảnh chụp thật (không có asset ảnh có bản quyền rõ ràng - Phần 35/36 cấm
  dùng ảnh không rõ nguồn gốc bản quyền).
- Hero vẫn dùng minh họa SVG, không phải ảnh chụp thật, cùng lý do trên.
- Statistics strip hoàn toàn là fixture (không có aggregate API thật) -
  nếu sau này Laravel có endpoint tổng hợp thật (tổng user/course/exam...),
  nên thay fixture bằng lời gọi API thật tại đúng vị trí này.
- Footer vẫn 2 cột thật (không thêm cột "Hỗ trợ"/social) - đây là NAVIGATION
  tới trang không tồn tại, khác bản chất với design fixture nội dung, nên
  không nới lỏng theo yêu cầu chung của phiên này (dead link luôn bị cấm
  bất kể fixture).
