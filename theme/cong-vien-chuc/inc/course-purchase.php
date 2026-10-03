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

/**
 * Ghi danh khóa học (Phase 12) - khóa miễn phí hoặc khóa đã mua. Thành công
 * -> chuyển thẳng vào bài cần học tiếp (lesson_id do form gửi kèm).
 */
add_action( 'admin_post_cvc_course_enroll', 'cvc_handle_course_enroll' );
add_action( 'admin_post_nopriv_cvc_course_enroll', 'cvc_handle_course_enroll' );

function cvc_handle_course_enroll(): void {
	check_admin_referer( 'cvc_course_enroll' );
	$token = cvc_require_token_or_die();

	$course_id = absint( $_POST['course_id'] ?? 0 );
	$slug      = sanitize_title( wp_unslash( $_POST['course_slug'] ?? '' ) );
	$lesson_id = absint( $_POST['lesson_id'] ?? 0 );
	$fallback  = '' !== $slug ? cvc_course_url( $slug ) : cvc_courses_url();

	if ( 0 === $course_id ) {
		cvc_redirect_with_notice( $fallback, 'error', 'Khóa học không hợp lệ.', null );
		return;
	}

	$result = ( new CVC_Course_Service() )->enroll( $course_id, $token );

	if ( ! $result['ok'] ) {
		$message = ! empty( $result['data']['message'] ) ? (string) $result['data']['message'] : cvc_api_error_message( $result );
		cvc_redirect_with_notice( $fallback, 'error', $message, null );
		return;
	}

	$target = ( '' !== $slug && $lesson_id > 0 ) ? cvc_course_lesson_url( $slug, $lesson_id ) : $fallback;
	cvc_redirect_with_notice( $target, 'success', 'Đã ghi danh khóa học. Chúc bạn học tốt!', null );
}

/**
 * Đánh dấu hoàn thành / học lại 1 bài (Phase 12).
 */
add_action( 'admin_post_cvc_lesson_progress', 'cvc_handle_lesson_progress' );
add_action( 'admin_post_nopriv_cvc_lesson_progress', 'cvc_handle_lesson_progress' );

function cvc_handle_lesson_progress(): void {
	check_admin_referer( 'cvc_lesson_progress' );
	$token = cvc_require_token_or_die();

	$lesson_id = absint( $_POST['lesson_id'] ?? 0 );
	$slug      = sanitize_title( wp_unslash( $_POST['course_slug'] ?? '' ) );
	$next_id   = absint( $_POST['next_lesson_id'] ?? 0 );
	$status    = sanitize_key( wp_unslash( $_POST['status'] ?? 'completed' ) );
	$back      = ( '' !== $slug && $lesson_id > 0 ) ? cvc_course_lesson_url( $slug, $lesson_id ) : cvc_courses_url();

	if ( 0 === $lesson_id || ! in_array( $status, array( 'completed', 'in_progress' ), true ) ) {
		cvc_redirect_with_notice( $back, 'error', 'Yêu cầu không hợp lệ.', null );
		return;
	}

	$payload = 'completed' === $status
		? array( 'status' => 'completed', 'progress_percent' => 100 )
		: array( 'status' => 'in_progress', 'progress_percent' => 1 );

	$result = ( new CVC_Course_Service() )->update_lesson_progress( $lesson_id, $payload, $token );

	if ( ! $result['ok'] ) {
		$message = ! empty( $result['data']['message'] ) ? (string) $result['data']['message'] : cvc_api_error_message( $result );
		cvc_redirect_with_notice( $back, 'error', $message, null );
		return;
	}

	if ( 'completed' === $status && $next_id > 0 && '' !== $slug ) {
		cvc_redirect_with_notice( cvc_course_lesson_url( $slug, $next_id ), 'success', 'Đã hoàn thành bài trước. Tiếp tục bài này nhé.', null );
		return;
	}

	$message = 'completed' === $status ? 'Đã đánh dấu hoàn thành bài học.' : 'Đã chuyển bài học về trạng thái đang học.';
	cvc_redirect_with_notice( $back, 'success', $message, null );
}

