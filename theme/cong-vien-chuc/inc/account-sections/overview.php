<?php
/**
 * Tổng quan (Phase 10) - chỉ 1 API call (unread-count), còn lại dùng
 * $user đã có sẵn từ template-account.php (tránh gọi API thừa - Phần
 * Performance). Link nhanh sang các section khác.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$unread_result = ( new CVC_Notification_Service() )->unreadCount( $token );
$unread_count  = $unread_result['ok'] ? (int) ( $unread_result['data']['data']['unread_count'] ?? 0 ) : null;
?>

<div class="cvc-overview-grid">
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'goals' ) ); ?>">
		<span class="cvc-overview-tile__label">Mục tiêu ôn thi</span>
		<span class="cvc-overview-tile__hint">Xem &amp; quản lý mục tiêu</span>
	</a>
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'learning-path' ) ); ?>">
		<span class="cvc-overview-tile__label">Lộ trình học tập</span>
		<span class="cvc-overview-tile__hint">Theo dõi tiến độ</span>
	</a>
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'recruitment-matches' ) ); ?>">
		<span class="cvc-overview-tile__label">Việc làm phù hợp</span>
		<span class="cvc-overview-tile__hint">Tin tuyển dụng khớp mục tiêu</span>
	</a>
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'recommendations' ) ); ?>">
		<span class="cvc-overview-tile__label">Gợi ý cho bạn</span>
		<span class="cvc-overview-tile__hint">Khóa học, đề thi, chủ đề</span>
	</a>
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">
		<span class="cvc-overview-tile__label">Lịch sử làm bài</span>
		<span class="cvc-overview-tile__hint">Kết quả các lần thi</span>
	</a>
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'bookmarks' ) ); ?>">
		<span class="cvc-overview-tile__label">Đã đánh dấu</span>
		<span class="cvc-overview-tile__hint">Nội dung đã lưu</span>
	</a>
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'notifications' ) ); ?>">
		<span class="cvc-overview-tile__label">Thông báo</span>
		<span class="cvc-overview-tile__hint">
			<?php echo null !== $unread_count && $unread_count > 0 ? esc_html( sprintf( '%d chưa đọc', $unread_count ) ) : 'Không có thông báo mới'; ?>
		</span>
	</a>
	<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( 'profile' ) ); ?>">
		<span class="cvc-overview-tile__label">Hồ sơ cá nhân</span>
		<span class="cvc-overview-tile__hint">Cập nhật thông tin</span>
	</a>
</div>
