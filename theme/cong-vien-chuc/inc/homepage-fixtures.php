<?php
/**
 * Homepage DESIGN FIXTURE (Phase 10A.6, Phần 2-5/13/17/31-33) - nội dung
 * demo CHỈ để lấp khoảng trống visual trên homepage khi dữ liệu DEV thật
 * quá ít để tái tạo đúng mật độ của ảnh benchmark (VD: 0 tin tuyển dụng
 * published, chỉ 1 khóa học).
 *
 * RANH GIỚI TUYỆT ĐỐI (không được vi phạm dưới bất kỳ hình thức nào):
 * - KHÔNG BAO GIỜ ghi vào database (WordPress lẫn Laravel).
 * - KHÔNG BAO GIỜ được API nào trả về - đây là dữ liệu tĩnh, hardcode
 *   trong theme, chỉ dùng ở đúng 1 nơi: index.php.
 * - KHÔNG được tính vào search/recommendation/sitemap/analytics - không
 *   route/template nào khác được require file này.
 * - KHÔNG BAO GIỜ ghi đè dữ liệu thật - mọi hàm ở đây chỉ được GỌI khi
 *   dữ liệu thật rỗng/không đủ (xem cvc_homepage_should_use_fixture()),
 *   REAL DATA luôn thắng nếu có (Phần 31 - "REAL luôn ưu tiên").
 * - Chỉ hoạt động khi hằng số CVC_HOMEPAGE_DEMO_CONTENT = true (khai báo
 *   ở wp-config.php/docker-compose.yml cho local dev - xem
 *   cvc_homepage_demo_enabled()). Production PHẢI để false hoặc không
 *   khai báo hằng số này (fail-safe, Phần 33).
 * - Mọi item fixture render ra HTML đều có badge "Demo" (Phần 17 - "phải
 *   gắn rõ demo/design fixture") để không đánh lừa người xem trang thật.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cờ bật/tắt design fixture cho homepage - fail-safe về false nếu hằng
 * số chưa được khai báo ở đâu (Phần 33 "PRODUCTION GUARANTEE").
 */
function cvc_homepage_demo_enabled(): bool {
	return defined( 'CVC_HOMEPAGE_DEMO_CONTENT' ) && true === CVC_HOMEPAGE_DEMO_CONTENT;
}

/**
 * Badge "Demo" - gắn lên MỌI phần tử fixture hiển thị ra trang (Phần 17).
 */
function cvc_render_demo_badge(): void {
	echo '<span class="cvc-badge cvc-badge--demo" title="Nội dung minh họa cho môi trường phát triển, không phải dữ liệu thật">Demo</span>';
}

/**
 * Tin tuyển dụng fixture - dùng khi GET /api/recruitments thật trả về
 * rỗng (DEV hiện có 0 bản ghi status=published) VÀ demo mode bật. Shape
 * cố tình giống HỆT response thật của RecruitmentController::index() để
 * `cvc_render_recruitment_list_item()` dùng lại nguyên vẹn, không tạo
 * renderer thứ 2 - chỉ khác ở field nội bộ `_is_demo` đánh dấu để
 * template gắn badge "Demo".
 *
 * @return array<int, array<string, mixed>>
 */
function cvc_homepage_demo_recruitments(): array {
	return array(
		array(
			'_is_demo' => true,
			'slug'     => '',
			'title'    => 'Chuyên viên Văn phòng',
			'location' => 'Đắk Lắk',
			'dates'    => array( 'application_deadline' => gmdate( 'Y-m-d', strtotime( '+12 days' ) ) ),
			'agency'   => array( 'name' => 'UBND huyện Ea H\'leo' ),
		),
		array(
			'_is_demo' => true,
			'slug'     => '',
			'title'    => 'Giảng viên (Hợp đồng)',
			'location' => 'Đắk Lắk',
			'dates'    => array( 'application_deadline' => gmdate( 'Y-m-d', strtotime( '+17 days' ) ) ),
			'agency'   => array( 'name' => 'Trường Cao đẳng Sư phạm' ),
		),
		array(
			'_is_demo' => true,
			'slug'     => '',
			'title'    => 'Chuyên viên tài chính - kế toán',
			'location' => 'Đắk Lắk',
			'dates'    => array( 'application_deadline' => gmdate( 'Y-m-d', strtotime( '+15 days' ) ) ),
			'agency'   => array( 'name' => 'Sở Tài chính tỉnh Đắk Lắk' ),
		),
		array(
			'_is_demo' => true,
			'slug'     => '',
			'title'    => 'Chuyên viên công nghệ thông tin',
			'location' => 'Đắk Lắk',
			'dates'    => array( 'application_deadline' => gmdate( 'Y-m-d', strtotime( '+22 days' ) ) ),
			'agency'   => array( 'name' => 'Sở Thông tin và Truyền thông' ),
		),
	);
}

/**
 * Course fixture "hỗ trợ" - chỉ dùng để đủ mật độ 3-card khi DEV chỉ có
 * ĐÚNG 1 course thật (Phần 14 "Featured Course Showcase" + "supporting
 * cards"). Course thật LUÔN hiện trước, ở vị trí "featured" - 2 fixture
 * này chỉ lấp thêm, không bao giờ thay thế course thật.
 *
 * @return array<int, array<string, mixed>>
 */
function cvc_homepage_demo_courses(): array {
	return array(
		array(
			'_is_demo'           => true,
			'slug'               => '',
			'title'              => 'Kỹ năng giao tiếp và xử lý tình huống trong công vụ',
			'short_description'  => 'Nâng cao kỹ năng mềm, xây dựng hình ảnh chuyên nghiệp.',
			'course_type'        => 'skill',
			'thumbnail_url'      => null,
			'published_lessons_count' => 8,
			'duration_minutes'   => null,
			'price'              => '0.00',
			'is_featured'        => false,
			'published_at'       => gmdate( 'c', strtotime( '-10 days' ) ),
		),
		array(
			'_is_demo'           => true,
			'slug'               => '',
			'title'              => 'Ứng dụng Excel trong công việc hành chính nhà nước',
			'short_description'  => 'Tối ưu hiệu suất với các kỹ năng Excel thực tiễn.',
			'course_type'        => 'professional',
			'thumbnail_url'      => null,
			'published_lessons_count' => 10,
			'duration_minutes'   => null,
			'price'              => '0.00',
			'is_featured'        => false,
			'published_at'       => gmdate( 'c', strtotime( '-40 days' ) ),
		),
	);
}

/**
 * Số liệu thống kê fixture (Phần 13 STATISTICS - "nếu chưa có backend
 * aggregate thật, được dùng fixture ở DEV, PHẢI gắn rõ + feature flag").
 * KHÔNG map bất kỳ số nào ở đây với API thật - đây thuần là minh họa mật
 * độ "social proof" mà hệ thống hiện chưa có aggregate endpoint để tính
 * thật. Toàn bộ section này CHỈ render khi cvc_homepage_demo_enabled().
 */
function cvc_homepage_demo_statistics(): array {
	return array(
		array(
			'icon'  => 'graduation',
			'value' => '10.000+',
			'label' => 'Tài khoản học tập',
		),
		array(
			'icon'  => 'book',
			'value' => '500+',
			'label' => 'khóa học & chuyên đề',
		),
		array(
			'icon'  => 'building',
			'value' => '200+',
			'label' => 'đơn vị tuyển dụng',
		),
		array(
			'icon'  => 'document',
			'value' => '50.000+',
			'label' => 'câu hỏi trắc nghiệm',
		),
		array(
			'icon'  => 'check',
			'value' => '98%',
			'label' => 'học viên hài lòng',
		),
	);
}
