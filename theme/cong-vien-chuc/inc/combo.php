<?php
/**
 * Combo Ôn Thi Công Chức (Vòng 1 + Vòng 2) — mua gộp 2 khóa học thật
 * trong 1 đơn hàng, dùng lại /api/orders (đã hỗ trợ nhiều item sẵn).
 *
 * Trước đây khối "Combo" ở trang chủ chỉ là banner tĩnh: nút bấm gọi
 * enrollComboCourse() hiện alert() giả vờ đã áp mã giảm giá
 * "TUYENDUNG2026" — không tạo đơn hàng thật, không trừ tiền, không
 * cấp quyền truy cập gì cả. Thay bằng luồng mua thật: tạo Order với 2
 * course_id thật, redirect sang VNPay thật, giá hiển thị lấy nguyên
 * từ sale_price thật của từng khóa học (không có hệ thống mã giảm giá
 * riêng nên không tự bịa % giảm giá bổ sung).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slug 2 khóa học cấu thành combo Vòng 1 + Vòng 2. Cố định ở đây vì
 * đây là 1 gói marketing cụ thể (không phải danh sách động) — nếu
 * khóa học nào bị gỡ (unpublish), cvc_get_combo_courses() tự ẩn toàn
 * bộ khối combo thay vì hiển thị dữ liệu thiếu.
 */
const CVC_COMBO_COURSE_SLUGS = array(
	'khoa-hoc-on-thi-cong-chuc-vong-1-kien-thuc-chung-cap-toc-2026',
	'khoa-hoc-chien-luoc-on-thi-vong-2-nghiep-vu-chuyen-nganh-ky-nang-phong-van',
);

/**
 * Lấy dữ liệu thật của combo (2 khóa học + tổng giá gốc/giá bán thật).
 * Trả về null nếu bất kỳ khóa học nào không tồn tại/không published,
 * để nơi gọi ẩn hẳn khối combo thay vì hiện dữ liệu rỗng/sai.
 *
 * @return array{courses: array<int, array<string, mixed>>, total_price: float, total_sale_price: float, discount_percent: int}|null
 */
function cvc_get_combo_courses(): ?array {
	static $cache = null;

	if ( null !== $cache ) {
		return false === $cache ? null : $cache;
	}

	$service = new CVC_Course_Service();
	$courses = array();
	$total_price = 0.0;
	$total_sale  = 0.0;

	foreach ( CVC_COMBO_COURSE_SLUGS as $slug ) {
		$result = $service->find( $slug );
		$course = $result['ok'] ? ( $result['data']['data'] ?? null ) : null;

		if ( ! is_array( $course ) || empty( $course['id'] ) ) {
			$cache = false;
			return null;
		}

		$price = (float) ( $course['price'] ?? 0 );
		$sale  = (float) ( $course['sale_price'] ?? $course['price'] ?? 0 );

		$courses[]    = $course;
		$total_price += $price;
		$total_sale  += $sale;
	}

	$discount_percent = $total_price > 0
		? (int) round( ( 1 - ( $total_sale / $total_price ) ) * 100 )
		: 0;

	$cache = array(
		'courses'           => $courses,
		'total_price'       => $total_price,
		'total_sale_price'  => $total_sale,
		'discount_percent'  => $discount_percent,
	);

	return $cache;
}

function cvc_handle_combo_buy(): void {
	check_admin_referer( 'cvc_combo_buy' );
	$token = cvc_require_token_or_die();

	$fallback = home_url( '/#combo-hot' );
	$combo    = cvc_get_combo_courses();

	if ( null === $combo ) {
		cvc_redirect_with_notice( $fallback, 'error', 'Gói combo hiện không khả dụng.', null );
		return;
	}

	$items = array_map(
		static fn ( array $course ) => array( 'type' => 'course', 'id' => (int) $course['id'] ),
		$combo['courses']
	);

	$result = ( new CVC_Order_Service() )->create( $items, $token );

	if ( ! $result['ok'] ) {
		// Laravel trả message tiếng Việt rõ ràng (đã sở hữu 1 trong 2 khóa,
		// VNPay chưa cấu hình...) - ưu tiên nguyên văn thay vì nhãn chung.
		$message = ! empty( $result['error'] ) ? (string) $result['error'] : cvc_api_error_message( $result );
		cvc_redirect_with_notice( $fallback, 'error', $message, null );
		return;
	}

	$payment_url = $result['data']['data']['payment_url'] ?? null;

	if ( ! $payment_url ) {
		cvc_redirect_with_notice( $fallback, 'error', 'Không khởi tạo được thanh toán. Vui lòng thử lại sau.', null );
		return;
	}

	// phpcs:ignore WordPress.Security.SafeRedirect -- đích đến là cổng VNPay (domain ngoài), không phải nội bộ.
	wp_redirect( $payment_url );
	exit;
}
add_action( 'admin_post_cvc_combo_buy', 'cvc_handle_combo_buy' );
add_action( 'admin_post_nopriv_cvc_combo_buy', 'cvc_handle_combo_buy' );
