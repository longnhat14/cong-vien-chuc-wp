<?php
/**
 * Form action handlers cho dashboard /tai-khoan/ (Phase 10) - tất cả theo
 * Post/Redirect/Get qua admin-post.php, giống pattern login/register/logout
 * ở inc/auth.php: nonce riêng từng action, KHÔNG BAO GIỜ tự chế ownership -
 * chỉ forward id + payload, để chính Laravel 403/422 khi sai (Phần XV -
 * "backend luôn là authority", không lặp lại business rule ở WP).
 *
 * Mọi handler bắt đầu bằng cvc_require_login_or_die() - hành động dashboard
 * không có nhánh "khách" (khác login/register/logout ở auth.php vốn có
 * nopriv vì chính là hành động ĐĂNG NHẬP).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trả về token hiện tại hoặc chặn luôn (dùng ở đầu mỗi handler dashboard -
 * KHÔNG có phiên bản nopriv cho các action này, nên về lý thuyết WP sẽ tự
 * trả "-1"/403 nếu gọi khi chưa đăng nhập WP; nhưng auth ở đây là của
 * LARAVEL - token cookie hết hạn vẫn có thể gọi được URL nếu ai đó biết -
 * nên vẫn phải tự kiểm tra token thật, không dựa vào is_user_logged_in()
 * của WordPress).
 */
function cvc_require_token_or_die(): string {
	$token = cvc_auth_token();

	if ( null === $token || ! cvc_is_logged_in() ) {
		wp_safe_redirect( cvc_login_url() );
		exit;
	}

	return $token;
}

/*
 * ============================================================
 * GOALS
 * ============================================================
 */

/*
 * admin_post_{action} CHỈ fire cho WP user đã đăng nhập; admin_post_nopriv_
 * {action} mới fire cho khách. User thật của platform KHÔNG BAO GIỜ đăng
 * nhập WordPress core (auth hoàn toàn qua Laravel, xem inc/auth.php) - với
 * WP, họ luôn là "khách". Thiếu nhánh nopriv nghĩa là handler không bao giờ
 * chạy được cho user thật (chỉ wp_die('', 400) rỗng) - PHẢI đăng ký cả 2 ở
 * MỌI action trong file này, giống pattern đã dùng đúng ở inc/auth.php.
 */
add_action( 'admin_post_nopriv_cvc_goal_save', 'cvc_handle_goal_save' );
add_action( 'admin_post_cvc_goal_save', 'cvc_handle_goal_save' );

function cvc_handle_goal_save(): void {
	check_admin_referer( 'cvc_goal_save' );
	$token = cvc_require_token_or_die();

	$goalId = isset( $_POST['goal_id'] ) ? absint( $_POST['goal_id'] ) : 0;

	$payload = array();
	foreach ( array( 'title', 'notes' ) as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$payload[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
		}
	}
	foreach ( array( 'province_id', 'agency_id', 'position_id', 'exam_id', 'recruitment_id' ) as $field ) {
		if ( isset( $_POST[ $field ] ) && '' !== $_POST[ $field ] ) {
			$payload[ $field ] = absint( $_POST[ $field ] );
		}
	}

	$service = new CVC_Goal_Service();
	$result  = $goalId > 0 ? $service->update( $goalId, $payload, $token ) : $service->create( $payload, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_account_url( 'goals' ), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	cvc_redirect_with_notice( cvc_account_url( 'goals' ), 'success', $goalId > 0 ? 'Đã cập nhật mục tiêu.' : 'Đã tạo mục tiêu mới.', null );
}

add_action( 'admin_post_nopriv_cvc_goal_transition', 'cvc_handle_goal_transition' );
add_action( 'admin_post_cvc_goal_transition', 'cvc_handle_goal_transition' );

function cvc_handle_goal_transition(): void {
	check_admin_referer( 'cvc_goal_transition' );
	$token = cvc_require_token_or_die();

	$goalId     = absint( $_POST['goal_id'] ?? 0 );
	$transition = sanitize_key( wp_unslash( $_POST['transition'] ?? '' ) );
	$allowed    = array( 'activate', 'pause', 'resume', 'complete', 'archive' );

	if ( 0 === $goalId || ! in_array( $transition, $allowed, true ) ) {
		cvc_redirect_with_notice( cvc_account_url( 'goals' ), 'error', 'Thao tác không hợp lệ.', null );
		return;
	}

	$service = new CVC_Goal_Service();
	$result  = $service->$transition( $goalId, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_account_url( 'goals' ), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	cvc_redirect_with_notice( cvc_account_url( 'goals' ), 'success', 'Đã cập nhật trạng thái mục tiêu.', null );
}

/*
 * ============================================================
 * LEARNING PATH
 * ============================================================
 */

add_action( 'admin_post_nopriv_cvc_learning_path_generate', 'cvc_handle_learning_path_generate' );
add_action( 'admin_post_cvc_learning_path_generate', 'cvc_handle_learning_path_generate' );

function cvc_handle_learning_path_generate(): void {
	check_admin_referer( 'cvc_learning_path_generate' );
	$token = cvc_require_token_or_die();

	$goalId = absint( $_POST['exam_goal_id'] ?? 0 );

	if ( 0 === $goalId ) {
		cvc_redirect_with_notice( cvc_account_url( 'learning-path' ), 'error', 'Vui lòng chọn mục tiêu.', null );
		return;
	}

	$result = ( new CVC_Learning_Path_Service() )->generate( $goalId, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_account_url( 'learning-path' ), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	cvc_redirect_with_notice( cvc_account_url( 'learning-path' ), 'success', $result['data']['message'] ?? 'Đã sinh lộ trình học tập.', null );
}

/*
 * ============================================================
 * BOOKMARKS
 * ============================================================
 */

add_action( 'admin_post_nopriv_cvc_bookmark_add', 'cvc_handle_bookmark_add' );
add_action( 'admin_post_cvc_bookmark_add', 'cvc_handle_bookmark_add' );

function cvc_handle_bookmark_add(): void {
	check_admin_referer( 'cvc_bookmark_add' );
	$token = cvc_require_token_or_die();

	$type = sanitize_key( wp_unslash( $_POST['type'] ?? '' ) );
	$id   = absint( $_POST['id'] ?? 0 );
	$back = cvc_safe_redirect_target( isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : null );

	if ( ! in_array( $type, CVC_Bookmark_Service::VALID_TYPES, true ) || 0 === $id ) {
		cvc_redirect_with_notice( $back, 'error', 'Không thể lưu mục này.', null );
		return;
	}

	$result = ( new CVC_Bookmark_Service() )->store( $type, $id, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $result ), null );
		return;
	}

	cvc_redirect_with_notice( $back, 'success', 'Đã lưu vào danh sách đánh dấu.', null );
}

add_action( 'admin_post_nopriv_cvc_bookmark_remove', 'cvc_handle_bookmark_remove' );
add_action( 'admin_post_cvc_bookmark_remove', 'cvc_handle_bookmark_remove' );

function cvc_handle_bookmark_remove(): void {
	check_admin_referer( 'cvc_bookmark_remove' );
	$token = cvc_require_token_or_die();

	$bookmarkId = absint( $_POST['bookmark_id'] ?? 0 );
	$back       = cvc_safe_redirect_target( isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : null );

	if ( 0 === $bookmarkId ) {
		cvc_redirect_with_notice( $back, 'error', 'Không tìm thấy mục đánh dấu.', null );
		return;
	}

	$result = ( new CVC_Bookmark_Service() )->destroy( $bookmarkId, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $result ), null );
		return;
	}

	cvc_redirect_with_notice( $back, 'success', 'Đã bỏ đánh dấu.', null );
}

/*
 * ============================================================
 * RECRUITMENT MATCH
 * ============================================================
 */

add_action( 'admin_post_nopriv_cvc_match_transition', 'cvc_handle_match_transition' );
add_action( 'admin_post_cvc_match_transition', 'cvc_handle_match_transition' );

function cvc_handle_match_transition(): void {
	check_admin_referer( 'cvc_match_transition' );
	$token = cvc_require_token_or_die();

	$matchId    = absint( $_POST['match_id'] ?? 0 );
	$transition = sanitize_key( wp_unslash( $_POST['transition'] ?? '' ) );
	$allowed    = array(
		'seen'       => 'markSeen',
		'dismiss'    => 'dismiss',
		'interested' => 'interested',
	);

	if ( 0 === $matchId || ! isset( $allowed[ $transition ] ) ) {
		cvc_redirect_with_notice( cvc_account_url( 'recruitment-matches' ), 'error', 'Thao tác không hợp lệ.', null );
		return;
	}

	$method = $allowed[ $transition ];
	$result = ( new CVC_Recruitment_Match_Service() )->$method( $matchId, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_account_url( 'recruitment-matches' ), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	cvc_redirect_with_notice( cvc_account_url( 'recruitment-matches' ), 'success', 'Đã cập nhật.', null );
}

/*
 * ============================================================
 * NOTIFICATIONS
 * ============================================================
 */

add_action( 'admin_post_nopriv_cvc_notification_read', 'cvc_handle_notification_read' );
add_action( 'admin_post_cvc_notification_read', 'cvc_handle_notification_read' );

function cvc_handle_notification_read(): void {
	check_admin_referer( 'cvc_notification_read' );
	$token = cvc_require_token_or_die();

	$id = sanitize_text_field( wp_unslash( $_POST['notification_id'] ?? '' ) );

	if ( '' !== $id ) {
		( new CVC_Notification_Service() )->markRead( $id, $token );
	}

	wp_safe_redirect( cvc_account_url( 'notifications' ) );
	exit;
}

add_action( 'admin_post_nopriv_cvc_notification_read_all', 'cvc_handle_notification_read_all' );
add_action( 'admin_post_cvc_notification_read_all', 'cvc_handle_notification_read_all' );

function cvc_handle_notification_read_all(): void {
	check_admin_referer( 'cvc_notification_read_all' );
	$token = cvc_require_token_or_die();

	( new CVC_Notification_Service() )->markAllRead( $token );

	cvc_redirect_with_notice( cvc_account_url( 'notifications' ), 'success', 'Đã đánh dấu tất cả đã đọc.', null );
}

/*
 * ============================================================
 * PROFILE
 * ============================================================
 */

add_action( 'admin_post_nopriv_cvc_profile_update', 'cvc_handle_profile_update' );
add_action( 'admin_post_cvc_profile_update', 'cvc_handle_profile_update' );

function cvc_handle_profile_update(): void {
	check_admin_referer( 'cvc_profile_update' );
	$token = cvc_require_token_or_die();

	$payload = array();
	foreach ( array( 'name', 'display_name', 'avatar_url', 'phone', 'bio' ) as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$payload[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
		}
	}
	if ( isset( $_POST['province_id'] ) && '' !== $_POST['province_id'] ) {
		$payload['province_id'] = absint( $_POST['province_id'] );
	}

	$result = ( new CVC_Profile_Service() )->update( $payload, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_account_url( 'profile' ), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	cvc_redirect_with_notice( cvc_account_url( 'profile' ), 'success', 'Đã cập nhật hồ sơ.', null );
}

add_action( 'admin_post_nopriv_cvc_profile_change_password', 'cvc_handle_profile_change_password' );
add_action( 'admin_post_cvc_profile_change_password', 'cvc_handle_profile_change_password' );

function cvc_handle_profile_change_password(): void {
	check_admin_referer( 'cvc_profile_change_password' );
	$token = cvc_require_token_or_die();

	$current      = (string) ( $_POST['current_password'] ?? '' );
	$new          = (string) ( $_POST['new_password'] ?? '' );
	$confirmation = (string) ( $_POST['new_password_confirmation'] ?? '' );

	$result = ( new CVC_Profile_Service() )->changePassword( $current, $new, $confirmation, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_account_url( 'profile' ), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	// Backend thu hồi toàn bộ token sau đổi mật khẩu (Phần V ProfileController)
	// - cookie hiện tại đã chết, phải xóa để không kẹt trạng thái tưởng còn
	// đăng nhập, buộc đăng nhập lại bằng mật khẩu mới.
	cvc_clear_auth_cookie();
	cvc_redirect_with_notice( cvc_login_url(), 'success', 'Đã đổi mật khẩu, vui lòng đăng nhập lại.', null );
}

/*
 * ============================================================
 * EXAM ATTEMPT - start (answer/submit là AJAX, xem inc/exam.php)
 * ============================================================
 */

add_action( 'admin_post_nopriv_cvc_exam_start', 'cvc_handle_exam_start' );
add_action( 'admin_post_cvc_exam_start', 'cvc_handle_exam_start' );

function cvc_handle_exam_start(): void {
	check_admin_referer( 'cvc_exam_start' );
	$token = cvc_require_token_or_die();

	$examId = absint( $_POST['exam_id'] ?? 0 );
	$mode   = sanitize_key( wp_unslash( $_POST['mode'] ?? 'mock' ) );

	if ( 0 === $examId ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	$result = ( new CVC_Exam_Attempt_Service() )->start( $examId, $mode, $token );

	if ( ! $result['ok'] ) {
		cvc_redirect_with_notice( cvc_exam_url_from_id( $examId ), 'error', cvc_api_error_message( $result ), null );
		return;
	}

	$attemptId = $result['data']['data']['id'] ?? null;

	if ( null === $attemptId ) {
		cvc_redirect_with_notice( home_url( '/' ), 'error', 'Không thể bắt đầu làm bài, vui lòng thử lại.', null );
		return;
	}

	wp_safe_redirect( cvc_exam_attempt_url( (int) $attemptId ) );
	exit;
}

/**
 * exam_id thô không đủ để build URL đẹp (cần slug) - lỗi start() hiếm khi
 * xảy ra (chỉ khi đề chưa publish/chưa có câu hỏi), nên fallback về trang
 * chủ kèm thông báo là đủ, không cần tra cứu thêm slug chỉ để redirect lỗi.
 */
function cvc_exam_url_from_id( int $examId ): string {
	return home_url( '/' );
}
