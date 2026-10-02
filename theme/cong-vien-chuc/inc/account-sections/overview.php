<?php
/**
 * Tổng quan (Phase 10 & Phase 3 Roadmap: Learning Streaks & Affiliate Network)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$unread_result = ( new CVC_Notification_Service() )->unreadCount( $token );
$unread_count  = $unread_result['ok'] ? (int) ( $unread_result['data']['data']['unread_count'] ?? 0 ) : null;

$tiles = array(
	array( 'section' => 'my-courses', 'icon' => 'courses/course', 'label' => 'Khóa học của tôi', 'hint' => 'Các khóa học đã đăng ký' ),
	array( 'section' => 'my-documents', 'icon' => 'knowledge-legal/document', 'label' => 'Tài liệu đã mua', 'hint' => 'Tài liệu & đề thi đã sở hữu' ),
	array( 'section' => 'goals', 'icon' => 'dashboard/goal', 'label' => 'Mục tiêu ôn thi', 'hint' => 'Xem & quản lý mục tiêu' ),
	array( 'section' => 'learning-path', 'icon' => 'dashboard/roadmap', 'label' => 'Lộ trình học tập', 'hint' => 'Theo dõi tiến độ' ),
	array( 'section' => 'recruitment-matches', 'icon' => 'recruitment/position', 'label' => 'Việc làm phù hợp', 'hint' => 'Tin tuyển dụng khớp mục tiêu' ),
	array( 'section' => 'recommendations', 'icon' => 'analytics/insight', 'label' => 'Gợi ý cho bạn', 'hint' => 'Khóa học, đề thi, chủ đề' ),
	array( 'section' => 'exam-history', 'icon' => 'dashboard/history', 'label' => 'Lịch sử làm bài', 'hint' => 'Kết quả các lần thi' ),
	array( 'section' => 'bookmarks', 'icon' => 'dashboard/favorites', 'label' => 'Đã đánh dấu', 'hint' => 'Nội dung đã lưu' ),
	array(
		'section' => 'notifications',
		'icon'    => 'dashboard/notification',
		'label'   => 'Thông báo',
		'hint'    => null !== $unread_count && $unread_count > 0 ? sprintf( '%d chưa đọc', $unread_count ) : 'Không có thông báo mới',
	),
	array( 'section' => 'profile', 'icon' => 'dashboard/profile', 'label' => 'Hồ sơ cá nhân', 'hint' => 'Cập nhật thông tin' ),
);
?>

<div class="space-y-6">

	<!-- KHỐI LEARNING STREAKS (DUOLINGO STYLE) & XU CÔNG VIÊN CHỨC -->
	<div class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 rounded-3xl border-2 border-amber-500/40 shadow-xl space-y-4 text-white">
		<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-3">
			<div class="flex items-center gap-3">
				<div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-black text-xl shrink-0 border border-amber-500/30">
					🔥
				</div>
				<div>
					<h3 class="text-base font-extrabold text-white flex items-center gap-2">
						Learning Streak: Chuỗi 7 Ngày Ôn Thi Liên Tiếp!
					</h3>
					<p class="text-xs text-slate-300">Điểm danh & giải trắc nghiệm mỗi ngày để nhận **Xu Công Viên Chức** đổi Voucher khóa học.</p>
				</div>
			</div>
			
			<div class="bg-slate-900/80 px-4 py-2 rounded-2xl border border-slate-700 flex items-center gap-2 shrink-0">
				<i class="fa-solid fa-coins text-amber-400 text-lg"></i>
				<div>
					<span class="text-[10px] text-slate-400 block font-bold uppercase">Ví Xu Thưởng</span>
					<strong class="text-base font-black text-amber-400">350 Xu</strong>
				</div>
			</div>
		</div>

		<!-- LƯỚI TÍCH ĐIỂM HOẠT ĐỘNG TUẦN -->
		<div id="streakWeeklyWidget" class="grid grid-cols-7 gap-2 text-center text-xs pt-1">
			<!-- Rendered by JS -->
		</div>
	</div>

	<!-- KHỐI TIẾP THỊ LIÊN KẾT & RỦ BẠN CÙNG HỌC (AFFILIATE REFERRAL) -->
	<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-4 shadow-xl text-white">
		<div class="flex items-center justify-between border-b border-slate-800 pb-3">
			<h3 class="text-sm font-extrabold text-azure-400 flex items-center gap-2 uppercase tracking-wider">
				<i class="fa-solid fa-users-rays"></i> Mạng Lưới Rủ Bạn Cùng Học (Affiliate 15%)
			</h3>
			<span class="bg-azure-500/20 text-azure-300 text-[10px] font-bold px-2.5 py-0.5 rounded border border-azure-400/30">
				Mã cá nhân: CVC8899
			</span>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 space-y-1">
				<span class="text-[10px] text-slate-400 font-bold uppercase block">Đồng Đội Đã Giới Thiệu</span>
				<strong class="text-2xl font-black text-white">4 Bạn</strong>
			</div>
			<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 space-y-1">
				<span class="text-[10px] text-slate-400 font-bold uppercase block">Tỷ Lệ Hoa Hồng</span>
				<strong class="text-2xl font-black text-amber-400">15% Trực Tiếp</strong>
			</div>
			<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 space-y-1">
				<span class="text-[10px] text-slate-400 font-bold uppercase block">Tổng Thu Nhập Tích Lũy</span>
				<strong class="text-2xl font-black text-emerald-400">450.000đ</strong>
			</div>
		</div>

		<div class="p-4 bg-slate-900/60 rounded-2xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
			<div class="space-y-1 text-slate-300 w-full sm:w-auto">
				<span class="font-bold block text-white">Link giới thiệu cá nhân của bạn:</span>
				<code class="px-3 py-1.5 bg-black/50 text-amber-300 rounded-lg border border-slate-700 font-mono text-[11px] block sm:inline-block">http://congvienchuc.com/?ref=CVC8899</code>
			</div>
			<button type="button" onclick="navigator.clipboard.writeText('http://congvienchuc.com/?ref=CVC8899'); alert('Đã sao chép link giới thiệu!');" class="w-full sm:w-auto px-5 py-2.5 bg-azure-500 hover:bg-azure-600 text-white font-extrabold rounded-xl shadow shrink-0 cursor-pointer">
				<i class="fa-solid fa-copy mr-1"></i> Sao Chép Link
			</button>
		</div>
	</div>

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

<script>
const apiBase = "<?php echo esc_js( rtrim( cvc_api_base_url(), '/' ) ); ?>";
fetch(apiBase + '/api/user/learning-streak')
.then(res => res.json())
.then(res => {
	const data = res.data || {};
	const days = data.weekly_activity || [];
	const container = document.getElementById('streakWeeklyWidget');
	if (container && days.length) {
		container.innerHTML = days.map(d => `
			<div class="p-2.5 rounded-2xl ${d.active ? 'bg-amber-500/20 border border-amber-400/50 text-amber-300' : 'bg-slate-900 border border-slate-800 text-slate-500'} flex flex-col items-center gap-1">
				<span class="font-extrabold text-[11px]">${d.day}</span>
				<i class="fa-solid fa-fire ${d.active ? 'text-amber-400 animate-pulse' : 'text-slate-600'}"></i>
				<span class="text-[9px] font-bold">+${d.coins} Xu</span>
			</div>
		`).join('');
	}
});
</script>
