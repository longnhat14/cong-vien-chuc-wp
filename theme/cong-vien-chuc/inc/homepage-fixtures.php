<?php
/**
 * Homepage DESIGN FIXTURE (Phase 10A.6 & 10A.18) - nội dung demo CHỈ để
 * lấp khoảng trống visual trên homepage khi dữ liệu DEV thật quá ít để
 * tái tạo đúng mật độ của ảnh benchmark (VD: 0 tin tuyển dụng published,
 * chỉ 1 khóa học).
 *
 * RANH GIỚI TUYỆT ĐỐI (không được vi phạm dưới bất kỳ hình thức nào):
 * - KHÔNG BAO GIỜ ghi vào database (WordPress lẫn Laravel).
 * - KHÔNG BAO GIỜ được API nào trả về - đây là dữ liệu tĩnh, hardcode
 *   trong theme, chỉ dùng ở đúng 1 nơi: index.php.
 * - KHÔNG được tính vào search/recommendation/sitemap/analytics - không
 *   route/template nào khác được require file này.
 * - KHÔNG BAO GIỜ ghi đè dữ liệu thật - mọi hàm ở đây chỉ được GỌI khi
 *   dữ liệu thật rỗng/không đủ (xem cvc_homepage_should_use_fixture()),
 *   REAL DATA luôn thắng nếu có.
 * - Chỉ hoạt động khi hằng số CVC_HOMEPAGE_DEMO_CONTENT = true.
 * - Mọi item fixture render ra HTML đều có badge "Demo" để không đánh lừa
 *   người xem trang thật.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cờ bật/tắt design fixture cho homepage - fail-safe về false nếu hằng
 * số chưa được khai báo ở đâu.
 */
function cvc_homepage_demo_enabled(): bool {
	return defined( 'CVC_HOMEPAGE_DEMO_CONTENT' ) && true === CVC_HOMEPAGE_DEMO_CONTENT;
}

/**
 * Badge "Demo" - gắn lên MỌI phần tử fixture hiển thị ra trang.
 */
function cvc_render_demo_badge(): void {
	echo '<span class="cvc-badge cvc-badge--demo" title="Nội dung minh họa cho môi trường phát triển, không phải dữ liệu thật">Demo</span>';
}

/**
 * Tin tuyển dụng fixture - 4 tin chuẩn 100% khớp với ảnh thiết kế reference.
 *
 * @return array<int, array<string, mixed>>
 */
function cvc_homepage_demo_recruitments(): array {
	return array(
		array(
			'_is_demo'     => true,
			'slug'         => '',
			'title'        => 'Chuyên viên Văn phòng',
			'location'     => 'Đắk Lắk',
			'dates'        => array( 'application_deadline' => '15/09/2026' ),
			'agency'       => array( 'name' => 'UBND huyện Ea H\'leo' ),
			'emblem_style' => 'red',
			'_is_expired'  => false,
		),
		array(
			'_is_demo'     => true,
			'slug'         => '',
			'title'        => 'Giảng viên (Hợp đồng)',
			'location'     => 'Đắk Lắk',
			'dates'        => array( 'application_deadline' => '20/09/2026' ),
			'agency'       => array( 'name' => 'Trường Cao đẳng Sư phạm' ),
			'emblem_style' => 'blue',
			'_is_expired'  => false,
		),
		array(
			'_is_demo'     => true,
			'slug'         => '',
			'title'        => 'Chuyên viên tài chính – kế toán',
			'location'     => 'Đắk Lắk',
			'dates'        => array( 'application_deadline' => '18/09/2026' ),
			'agency'       => array( 'name' => 'Sở Tài chính tỉnh Đắk Lắk' ),
			'emblem_style' => 'red',
			'_is_expired'  => false,
		),
		array(
			'_is_demo'     => true,
			'slug'         => '',
			'title'        => 'Nhân viên văn thư',
			'location'     => 'Đắk Lắk',
			'dates'        => array( 'application_deadline' => '12/09/2026' ),
			'agency'       => array( 'name' => 'UBND xã Ea Tân' ),
			'emblem_style' => 'red_gold',
			'_is_expired'  => true,
		),
	);
}

/**
 * Course fixture - 3 khóa học nổi bật chuẩn 100% khớp với ảnh thiết kế reference.
 *
 * @return array<int, array<string, mixed>>
 */
function cvc_homepage_demo_courses(): array {
	return array(
		array(
			'_is_demo'                => true,
			'slug'                    => '',
			'title'                   => 'Bồi dưỡng kiến thức pháp luật cho công chức, viên chức',
			'short_description'       => 'Nắm vững hệ thống pháp luật, áp dụng hiệu quả trong công việc.',
			'course_type'             => 'exam_prep',
			'thumbnail_url'           => null,
			'published_lessons_count' => 12,
			'lessons_text'            => '12 bài học • 3 tuần',
			'badge_label'             => 'Bán chạy',
			'badge_color'             => 'red',
			'price'                   => '590000.00',
			'students_count'          => 1850,
			'rating_star'             => 4.9,
			'reviews_count'           => 328,
			'is_featured'             => true,
			'published_at'            => gmdate( 'c', strtotime( '-5 days' ) ),
		),
		array(
			'_is_demo'                => true,
			'slug'                    => '',
			'title'                   => 'Kỹ năng giao tiếp và xử lý tình huống trong công vụ',
			'short_description'       => 'Nâng cao kỹ năng mềm, xây dựng hình ảnh chuyên nghiệp.',
			'course_type'             => 'skill',
			'thumbnail_url'           => null,
			'published_lessons_count' => 8,
			'lessons_text'            => '8 bài học • 2 tuần',
			'badge_label'             => 'Mới',
			'badge_color'             => 'green',
			'price'                   => '390000.00',
			'students_count'          => 1240,
			'rating_star'             => 4.8,
			'reviews_count'           => 215,
			'is_featured'             => false,
			'published_at'            => gmdate( 'c', strtotime( '-2 days' ) ),
		),
		array(
			'_is_demo'                => true,
			'slug'                    => '',
			'title'                   => 'Ứng dụng Excel trong công việc hành chính nhà nước',
			'short_description'       => 'Tối ưu hiệu suất với các kỹ năng Excel thực tiễn.',
			'course_type'             => 'professional',
			'thumbnail_url'           => null,
			'published_lessons_count' => 10,
			'lessons_text'            => '10 bài học • 2 tuần',
			'badge_label'             => 'Miễn phí',
			'badge_color'             => 'cyan',
			'price'                   => '0.00',
			'students_count'          => 3100,
			'rating_star'             => 5.0,
			'reviews_count'           => 450,
			'is_featured'             => false,
			'published_at'            => gmdate( 'c', strtotime( '-15 days' ) ),
		),
	);
}

/**
 * Thống kê fixture - 5 số liệu chính.
 */
function cvc_homepage_demo_statistics(): array {
	return array(
		array(
			'icon'  => 'courses',
			'value' => '10.000+',
			'label' => 'Từ cả bảng học tập',
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

/**
 * Cộng đồng học tập fixture - 3 thảo luận nổi bật khớp với ảnh thiết kế reference.
 *
 * @return array<int, array<string, mixed>>
 */
function cvc_homepage_demo_community_discussions(): array {
	return array(
		array(
			'title'   => 'Kinh nghiệm ôn thi công chức 2025',
			'replies' => 12,
			'time'    => '3 giờ trước',
			'author'  => 'Nguyễn Văn Hùng',
		),
		array(
			'title'   => 'Tài liệu ôn thi viên chức ngành giáo dục',
			'replies' => 8,
			'time'    => '1 ngày trước',
			'author'  => 'Trần Thị Thu',
		),
		array(
			'title'   => 'Hỏi về chế độ nâng ngạch',
			'replies' => 5,
			'time'    => '2 ngày trước',
			'author'  => 'Lê Minh Tuấn',
		),
	);
}
