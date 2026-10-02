<?php
/**
 * Mua khóa học trả phí — cùng pattern cvc_handle_document_buy() (POST ->
 * tạo Order qua CVC_Order_Service -> redirect VNPay thật). Trước đây
 * template-course-detail.php không có luồng mua nào: nút "Đăng Ký Học
 * Ngay" trỏ thẳng vào bài học đầu tiên (hoặc alert() giả nếu course
 * chưa có bài học nào) — bỏ qua thanh toán hoàn toàn.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_handle_course_buy(): void {
	check_admin_referer( 'cvc_course_buy' );
	$token = cvc_require_token_or_die();

	$courseId = absint( $_POST['course_id'] ?? 0 );
	$slug     = sanitize_text_field( wp_unslash( $_POST['course_slug'] ?? '' ) );
	$fallback = '' !== $slug ? cvc_course_url( $slug ) : cvc_courses_url();

	if ( 0 === $courseId ) {
		cvc_redirect_with_notice( $fallback, 'error', 'Khóa học không hợp lệ.', null );
		return;
	}

	$result = ( new CVC_Order_Service() )->create(
		array( array( 'type' => 'course', 'id' => $courseId ) ),
		$token
	);

	if ( ! $result['ok'] ) {
		// Laravel trả message tiếng Việt rõ ràng (đã sở hữu, giá không hợp
		// lệ, VNPay chưa cấu hình...) - ưu tiên nguyên văn.
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
add_action( 'admin_post_cvc_course_buy', 'cvc_handle_course_buy' );
add_action( 'admin_post_nopriv_cvc_course_buy', 'cvc_handle_course_buy' );
