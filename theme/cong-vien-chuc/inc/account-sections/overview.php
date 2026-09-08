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

/*
 * Phase 10A.18: icon "CVC Icon Library V2" (PNG tile 144x128, cắt sẵn) -
 * trước đây 8 tile này hoàn toàn không có icon, chỉ tiêu đề/mô tả text.
 * Đây đúng use case README của bộ V2 gợi ý ("feature blocks"), khác bộ
 * SVG core V1 (dùng cho icon inline nhỏ). 'Gợi ý cho bạn' không có icon
 * "gợi ý/insight" nào sạch trong bộ V2 (xem báo cáo audit) - dùng tạm
 * misc/hot (lửa - "nổi bật/đề xuất") thay vì bỏ trống, đủ gần nghĩa.
 */
$tiles = array(
	array( 'section' => 'goals', 'icon' => 'dashboard/goal', 'label' => 'Mục tiêu ôn thi', 'hint' => 'Xem & quản lý mục tiêu' ),
	array( 'section' => 'learning-path', 'icon' => 'dashboard/roadmap', 'label' => 'Lộ trình học tập', 'hint' => 'Theo dõi tiến độ' ),
	array( 'section' => 'recruitment-matches', 'icon' => 'recruitment/position', 'label' => 'Việc làm phù hợp', 'hint' => 'Tin tuyển dụng khớp mục tiêu' ),
	array( 'section' => 'recommendations', 'icon' => 'misc/hot', 'label' => 'Gợi ý cho bạn', 'hint' => 'Khóa học, đề thi, chủ đề' ),
	array( 'section' => 'exam-history', 'icon' => null, 'label' => 'Lịch sử làm bài', 'hint' => 'Kết quả các lần thi' ),
	array( 'section' => 'bookmarks', 'icon' => 'dashboard/favorite', 'label' => 'Đã đánh dấu', 'hint' => 'Nội dung đã lưu' ),
	array(
		'section' => 'notifications',
		'icon'    => 'dashboard/notification',
		'label'   => 'Thông báo',
		'hint'    => null !== $unread_count && $unread_count > 0 ? sprintf( '%d chưa đọc', $unread_count ) : 'Không có thông báo mới',
	),
	array( 'section' => 'profile', 'icon' => null, 'label' => 'Hồ sơ cá nhân', 'hint' => 'Cập nhật thông tin' ),
);
?>

<div class="cvc-overview-grid">
	<?php foreach ( $tiles as $tile ) : ?>
		<a class="cvc-overview-tile" href="<?php echo esc_url( cvc_account_url( $tile['section'] ) ); ?>">
			<?php if ( $tile['icon'] ) : ?>
				<span class="cvc-overview-tile__icon" aria-hidden="true">
					<?php cvc_render_icon_library_v2( $tile['icon'], 40, 40 ); ?>
				</span>
			<?php endif; ?>
			<span class="cvc-overview-tile__label"><?php echo esc_html( $tile['label'] ); ?></span>
			<span class="cvc-overview-tile__hint"><?php echo esc_html( $tile['hint'] ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
