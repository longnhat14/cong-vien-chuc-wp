<?php
/**
 * Tổng quan tài khoản (Phase 10, viết lại Phase 12)
 *
 * Chuỗi ngày học lấy từ GET /api/user/learning-streak (hoạt động thật: làm
 * bài thi, học bài, cập nhật lộ trình, xem nội dung). Đã bỏ khối "Ví Xu" và
 * "Affiliate 15%" vì hệ thống không có xu thưởng hay chương trình giới thiệu
 * - các con số cũ (350 Xu, 4 bạn, 450.000đ, mã CVC8899) là số cứng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$unread_result = ( new CVC_Notification_Service() )->unreadCount( $token );
$unread_count  = $unread_result['ok'] ? (int) ( $unread_result['data']['data']['unread_count'] ?? 0 ) : null;

$streak_result = ( new CVC_Api_Client() )->get( '/api/user/learning-streak', array(), $token );
$streak        = $streak_result['ok'] && is_array( $streak_result['data']['data'] ?? null ) ? $streak_result['data']['data'] : null;

$history_result = ( new CVC_Exam_Attempt_Service() )->history( array( 'per_page' => 1 ), $token );
$attempt_total  = $history_result['ok'] ? (int) ( $history_result['data']['data']['total'] ?? 0 ) : null;

$my_courses_result = ( new CVC_Api_Client() )->get( '/api/my-courses', array( 'per_page' => 1 ), $token );
$course_total      = $my_courses_result['ok'] ? (int) ( $my_courses_result['data']['data']['total'] ?? 0 ) : null;

$tiles = array(
	array( 'section' => 'my-courses', 'icon' => 'courses/course', 'label' => 'Khóa học của tôi', 'hint' => 'Các khóa học đã đăng ký' ),
	array( 'section' => 'my-documents', 'icon' => 'devices/pdf', 'label' => 'Tài liệu đã mua', 'hint' => 'Tài liệu & đề thi đã sở hữu' ),
	array( 'section' => 'goals', 'icon' => 'dashboard/goal', 'label' => 'Mục tiêu ôn thi', 'hint' => 'Xem & quản lý mục tiêu' ),
	array( 'section' => 'learning-path', 'icon' => 'dashboard/roadmap', 'label' => 'Lộ trình học tập', 'hint' => 'Theo dõi tiến độ' ),
	array( 'section' => 'recruitment-matches', 'icon' => 'recruitment/position', 'label' => 'Việc làm phù hợp', 'hint' => 'Tin tuyển dụng khớp mục tiêu' ),
	array( 'section' => 'recommendations', 'icon' => 'dashboard/progress', 'label' => 'Gợi ý cho bạn', 'hint' => 'Khóa học, đề thi, chủ đề' ),
	array( 'section' => 'exam-history', 'icon' => 'courses/duration', 'label' => 'Lịch sử làm bài', 'hint' => 'Kết quả các lần thi' ),
	array( 'section' => 'bookmarks', 'icon' => 'dashboard/favorite', 'label' => 'Đã đánh dấu', 'hint' => 'Nội dung đã lưu' ),
	array(
		'section' => 'notifications',
		'icon'    => 'dashboard/notification',
		'label'   => 'Thông báo',
		'hint'    => null !== $unread_count && $unread_count > 0 ? sprintf( '%d chưa đọc', $unread_count ) : 'Không có thông báo mới',
	),
	array( 'section' => 'profile', 'icon' => 'dashboard/security', 'label' => 'Hồ sơ cá nhân', 'hint' => 'Cập nhật thông tin' ),
);
?>

<div class="space-y-6">

	<!-- CHUỖI NGÀY HỌC (dữ liệu thật) -->
	<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 rounded-3xl border border-amber-500/30 shadow-xl space-y-4 text-white" aria-labelledby="cvc-streak-title">
		<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-3">
			<div class="flex items-center gap-3">
				<div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl shrink-0 border border-amber-500/30" aria-hidden="true">
					<i class="fa-solid fa-fire"></i>
				</div>
				<div>
					<h3 id="cvc-streak-title" class="text-base font-extrabold text-white">
						<?php if ( null === $streak ) : ?>
							Chuỗi ngày học
						<?php elseif ( (int) $streak['current_streak'] > 0 ) : ?>
							Chuỗi <?php echo esc_html( (int) $streak['current_streak'] ); ?> ngày học liên tiếp
						<?php else : ?>
							Bắt đầu chuỗi ngày học hôm nay
						<?php endif; ?>
					</h3>
					<p class="text-xs text-slate-300">
						Một ngày được tính khi bạn làm bài thi, học bài, cập nhật lộ trình hoặc xem nội dung ôn thi.
						<?php if ( null !== $streak && ! $streak['active_today'] && (int) $streak['current_streak'] > 0 ) : ?>
							Học một chút hôm nay để giữ chuỗi.
						<?php endif; ?>
					</p>
				</div>
			</div>

			<?php if ( null !== $streak ) : ?>
				<dl class="flex gap-3 shrink-0 text-center">
					<div class="bg-slate-900/80 px-4 py-2 rounded-2xl border border-slate-700">
						<dt class="text-[10px] text-slate-400 font-bold uppercase">Dài nhất</dt>
						<dd class="text-base font-black text-amber-400"><?php echo esc_html( (int) $streak['longest_streak'] ); ?> ngày</dd>
					</div>
					<div class="bg-slate-900/80 px-4 py-2 rounded-2xl border border-slate-700">
						<dt class="text-[10px] text-slate-400 font-bold uppercase">30 ngày qua</dt>
						<dd class="text-base font-black text-cyan-400"><?php echo esc_html( (int) $streak['active_days_last_30'] ); ?> ngày</dd>
					</div>
				</dl>
			<?php endif; ?>
		</div>

		<?php if ( null === $streak ) : ?>
			<p class="text-xs text-slate-400">Chưa tải được dữ liệu chuỗi ngày học. Vui lòng tải lại trang sau.</p>
		<?php else : ?>
			<ol class="grid grid-cols-7 gap-2 text-center text-xs pt-1" aria-label="Hoạt động 7 ngày gần nhất">
				<?php foreach ( (array) $streak['weekly_activity'] as $day ) : ?>
					<?php
					$active = ! empty( $day['active'] );
					$label  = sprintf(
						'%s %s: %s',
						$day['day'] ?? '',
						cvc_format_date_vn( $day['date'] ?? null ),
						$active ? sprintf( '%d hoạt động', (int) ( $day['activities'] ?? 0 ) ) : 'chưa học'
					);
					?>
					<li class="p-2.5 rounded-2xl flex flex-col items-center gap-1 <?php echo $active ? 'bg-amber-500/20 border border-amber-400/50 text-amber-300' : 'bg-slate-900 border border-slate-800 text-slate-500'; ?> <?php echo ! empty( $day['is_today'] ) ? 'ring-1 ring-cyan-400/60' : ''; ?>" title="<?php echo esc_attr( $label ); ?>">
						<span class="font-extrabold text-[11px]"><?php echo esc_html( ! empty( $day['is_today'] ) ? 'Nay' : ( $day['day'] ?? '' ) ); ?></span>
						<i class="fa-solid <?php echo $active ? 'fa-fire text-amber-400' : 'fa-circle text-slate-700 text-[8px] my-1'; ?>" aria-hidden="true"></i>
						<span class="sr-only"><?php echo esc_html( $label ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</section>

	<!-- SỐ LIỆU NHANH -->
	<dl class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-white">
		<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800">
			<dt class="text-[10px] text-slate-400 font-bold uppercase">Lượt làm bài</dt>
			<dd class="text-2xl font-black"><?php echo null === $attempt_total ? '—' : esc_html( number_format_i18n( $attempt_total ) ); ?></dd>
		</div>
		<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800">
			<dt class="text-[10px] text-slate-400 font-bold uppercase">Khóa học đã ghi danh</dt>
			<dd class="text-2xl font-black"><?php echo null === $course_total ? '—' : esc_html( number_format_i18n( $course_total ) ); ?></dd>
		</div>
		<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800">
			<dt class="text-[10px] text-slate-400 font-bold uppercase">Thông báo chưa đọc</dt>
			<dd class="text-2xl font-black"><?php echo null === $unread_count ? '—' : esc_html( number_format_i18n( $unread_count ) ); ?></dd>
		</div>
	</dl>

	<!-- TILES CHỨC NĂNG CŨ -->
	<div class="cvc-overview-grid pt-2">
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

</div>
