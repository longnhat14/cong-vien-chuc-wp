<?php
/**
 * Khóa học của tôi - GET /api/my-courses (Phase 12).
 *
 * Trước đây trang này liệt kê MỌI khóa học công khai với nhãn "ĐÃ ĐĂNG KÝ"
 * và tiến độ cứng "65% - 18/24 bài". Nay chỉ hiện khóa người dùng đã ghi
 * danh, tiến độ tính từ bài đã học xong.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page   = isset( $_GET['cpage'] ) ? max( 1, absint( $_GET['cpage'] ) ) : 1;
$result = ( new CVC_Course_Service() )->my_courses( array( 'per_page' => 12, 'page' => $page ), $token );

$pagination  = $result['ok'] ? ( $result['data']['data'] ?? array() ) : array();
$enrollments = is_array( $pagination['data'] ?? null ) ? $pagination['data'] : array();
?>

<div class="space-y-6 text-slate-100">

	<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
		<div>
			<h2 class="text-xl font-extrabold text-white">Khóa học của tôi</h2>
			<p class="text-xs text-slate-400 mt-1">Các khóa học bạn đã ghi danh và tiến độ học.</p>
		</div>
		<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-xs rounded-xl shadow self-start">
			Khám phá khóa học
		</a>
	</div>

	<?php if ( ! $result['ok'] ) : ?>
		<?php cvc_render_error_state( 'Không tải được danh sách khóa học của bạn, vui lòng thử lại sau.' ); ?>
	<?php elseif ( empty( $enrollments ) ) : ?>
		<div class="p-8 text-center bg-slate-900 rounded-2xl border border-slate-800 space-y-3">
			<h3 class="text-base font-bold text-white">Bạn chưa ghi danh khóa học nào</h3>
			<p class="text-xs text-slate-400">Khóa miễn phí ghi danh ngay; khóa trả phí ghi danh sau khi thanh toán.</p>
			<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">Xem danh sách khóa học &rarr;</a>
		</div>
	<?php else : ?>
		<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
			<?php foreach ( $enrollments as $enrollment ) : ?>
				<?php
				$course = is_array( $enrollment['course'] ?? null ) ? $enrollment['course'] : array();
				if ( empty( $course['slug'] ) ) {
					continue;
				}
				$pct          = max( 0, min( 100, (int) ( $enrollment['progress_percent'] ?? 0 ) ) );
				$done         = (int) ( $enrollment['completed_lessons'] ?? 0 );
				$total        = (int) ( $course['lessons_count'] ?? 0 );
				$is_completed = 'completed' === ( $enrollment['status'] ?? '' );
				?>
				<article class="bg-[#0A192F] rounded-2xl border border-slate-800 p-5 space-y-4">
					<div class="space-y-1.5">
						<span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase <?php echo $is_completed ? 'bg-emerald-500/20 text-emerald-300' : 'bg-cyan-500/20 text-cyan-300'; ?>">
							<?php echo $is_completed ? 'Đã hoàn thành' : 'Đang học'; ?>
						</span>
						<h3 class="font-extrabold text-sm text-white leading-snug"><?php echo esc_html( (string) ( $course['title'] ?? '' ) ); ?></h3>
					</div>

					<div class="space-y-1.5 text-xs text-slate-300">
						<div class="flex items-center justify-between font-medium">
							<span>Tiến độ</span>
							<span class="font-bold text-cyan-300"><?php echo esc_html( $pct ); ?>%</span>
						</div>
						<div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden" role="progressbar" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-valuemin="0" aria-valuemax="100">
							<div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500" style="width: <?php echo esc_attr( $pct ); ?>%"></div>
						</div>
					</div>

					<div class="pt-2 border-t border-slate-800 flex items-center justify-between gap-3 text-xs">
						<span class="text-slate-400 text-[11px]">
							<?php echo esc_html( $total > 0 ? sprintf( '%d/%d bài đã học xong', $done, $total ) : 'Chưa có bài học công bố' ); ?>
							<?php if ( ! empty( $enrollment['last_accessed_at'] ) ) : ?>
								<br>Học gần nhất: <?php echo esc_html( cvc_format_date_vn( (string) $enrollment['last_accessed_at'] ) ); ?>
							<?php endif; ?>
						</span>
						<a href="<?php echo esc_url( cvc_course_url( (string) $course['slug'] ) ); ?>" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-xs rounded-xl shrink-0">
							<?php echo $is_completed ? 'Ôn lại' : 'Học tiếp'; ?> &rarr;
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<?php
		$last = (int) ( $pagination['last_page'] ?? 1 );
		if ( $last > 1 ) :
			?>
			<nav class="flex gap-2 text-xs" aria-label="Phân trang khóa học">
				<?php for ( $p = 1; $p <= $last; $p++ ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'cpage', $p, cvc_account_url( 'my-courses' ) ) ); ?>" class="px-3 py-1.5 rounded-lg border <?php echo $p === $page ? 'bg-amber-500 text-navy-950 border-amber-500 font-black' : 'border-slate-700 text-slate-300'; ?>" <?php echo $p === $page ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $p ); ?></a>
				<?php endfor; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

</div>
