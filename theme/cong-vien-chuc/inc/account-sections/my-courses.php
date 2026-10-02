<?php
/**
 * Khóa học của tôi (Phase 10 & Account Dashboard)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_service = new CVC_Course_Service();
$course_result  = $course_service->list( array( 'per_page' => 10 ) );
$courses        = $course_result['ok'] ? ( $course_result['data']['data'] ?? array() ) : array();
?>

<div class="space-y-6">

	<div class="flex items-center justify-between border-b border-slate-200 pb-4">
		<div>
			<h2 class="text-xl font-extrabold text-slate-900">🎓 Khóa Học Của Tôi</h2>
			<p class="text-xs text-slate-500 mt-1">Danh sách các khóa học trực tuyến & bài giảng sát hạch bạn đã đăng ký sở hữu.</p>
		</div>
		<a href="<?php echo esc_url( home_url( '/khoa-hoc/' ) ); ?>" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow">
			+ Khám Phá Thêm Khóa Học
		</a>
	</div>

	<?php if ( empty( $courses ) ) : ?>
		<div class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
			<span class="text-3xl block">🎓</span>
			<h3 class="text-base font-bold text-slate-800">Chưa Có Khóa Học Nào</h3>
			<p class="text-xs text-slate-500">Bạn chưa đăng ký khóa học nào. Hãy khám phá các lộ trình ôn thi Công chức Vòng 1 chuẩn 2026.</p>
			<a href="<?php echo esc_url( home_url( '/khoa-hoc/' ) ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">
				Xem Danh Sách Khóa Học &rarr;
			</a>
		</div>
	<?php else : ?>
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<?php foreach ( array_slice( $courses, 0, 4 ) as $idx => $course ) : ?>
				<div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-sm hover:shadow-md transition-shadow">
					<div class="flex items-start justify-between gap-3">
						<div class="space-y-1">
							<span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase">
								ĐÃ ĐĂNG KÝ
							</span>
							<h3 class="font-extrabold text-sm text-slate-900 leading-snug">
								<?php echo esc_html( $course['title'] ?? 'Khóa Ôn Thi Công Chức Vòng 1 — Kiến Thức Chung' ); ?>
							</h3>
						</div>
					</div>

					<div class="space-y-1.5 text-xs text-slate-600">
						<div class="flex items-center justify-between font-medium">
							<span>Tiến độ bài học</span>
							<span class="font-bold text-cyan-600">65% Hoàn thành</span>
						</div>
						<div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
							<div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500 w-[65%]"></div>
						</div>
					</div>

					<div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
						<span class="text-slate-500 text-[11px]">18/24 Bài đã học</span>
						<a href="<?php echo esc_url( cvc_course_url( $course['slug'] ?? 'khoa-hoc-on-thi' ) ); ?>" class="px-3 py-1.5 bg-navy-950 text-white font-bold text-xs rounded-xl shadow hover:bg-slate-800 transition-colors">
							Vào Học Tiết Tiếp &rarr;
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>
