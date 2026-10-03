<?php
/**
 * CÔNG VIÊN CHỨC — BÀI HỌC TRONG KHÓA HỌC
 * URL: /khoa-hoc/{slug}/bai-hoc/{lessonId}/
 *
 * Viết lại Phase 12: chỉ dùng dữ liệu thật (trước đây bài không xem được thì
 * hiện bài mẫu với video YouTube và PDF tuyển dụng không liên quan). Học viên
 * đã ghi danh được lưu tiến độ thật (PUT /api/course-lessons/{id}/progress) -
 * học xong mọi bài thì backend tự cấp chứng chỉ.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_slug = sanitize_title( (string) get_query_var( 'cvc_course_slug' ) );
$lesson_id   = absint( get_query_var( 'cvc_lesson_id' ) );

$token   = cvc_auth_token();
$service = new CVC_Course_Service();

$course = null;
$lesson = null;

$course_result = $service->find( $course_slug, $token );
if ( $course_result['ok'] && is_array( $course_result['data']['data'] ?? null ) ) {
	$course = $course_result['data']['data'];
}

if ( $course && $lesson_id ) {
	$lesson_result = $service->lesson( $course_slug, $lesson_id, $token );
	if ( $lesson_result['ok'] && is_array( $lesson_result['data']['data'] ?? null ) ) {
		$lesson = $lesson_result['data']['data'];
	}
}

$is_found = null !== $course && null !== $lesson;

if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
	cvc_seo_set_title( 'Không tìm thấy bài học' );
	get_header();
	?>
	<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-12">
		<div class="max-w-3xl mx-auto px-4 space-y-4">
			<?php cvc_render_notfound_state( 'Không tìm thấy bài học này. Bài học có thể chưa được công bố hoặc đường dẫn không đúng.' ); ?>
			<p>
				<a class="text-cyan-300 font-bold" href="<?php echo esc_url( $course ? cvc_course_url( $course_slug ) : cvc_courses_url() ); ?>">&larr; <?php echo $course ? 'Về trang khóa học' : 'Danh sách khóa học'; ?></a>
			</p>
		</div>
	</main>
	<?php
	get_footer();
	return;
}

// Danh sách bài phẳng (bài cha + bài con) theo thứ tự hiển thị.
$flat_lessons = array();
foreach ( (array) ( $course['lessons'] ?? array() ) as $parent_lesson ) {
	if ( ! empty( $parent_lesson['id'] ) ) {
		$flat_lessons[] = $parent_lesson;
	}
	foreach ( (array) ( $parent_lesson['children'] ?? array() ) as $child_lesson ) {
		if ( ! empty( $child_lesson['id'] ) ) {
			$flat_lessons[] = $child_lesson;
		}
	}
}

$prev_lesson = null;
$next_lesson = null;
$position    = 0;
foreach ( $flat_lessons as $index => $item ) {
	if ( (int) $item['id'] === $lesson_id ) {
		$prev_lesson = $flat_lessons[ $index - 1 ] ?? null;
		$next_lesson = $flat_lessons[ $index + 1 ] ?? null;
		$position    = $index + 1;
		break;
	}
}

$lesson_progress = is_array( $course['lesson_progress'] ?? null ) ? $course['lesson_progress'] : array();
$enrollment      = is_array( $course['enrollment'] ?? null ) ? $course['enrollment'] : null;
$can_access      = ! empty( $lesson['can_access'] );
$is_enrolled     = ! empty( $lesson['enrolled'] );
$progress        = is_array( $lesson['progress'] ?? null ) ? $lesson['progress'] : null;
$progress_status = (string) ( $progress['status'] ?? '' );
$logged_in       = cvc_is_logged_in();

/*
 * Mở bài lần đầu (đã ghi danh) -> đánh dấu "đang học" để "Học tiếp" và
 * chuỗi ngày học phản ánh đúng. Không bao giờ hạ bài đã xong về đang học.
 */
if ( $token && $can_access && $is_enrolled && in_array( $progress_status, array( '', 'not_started' ), true ) ) {
	$mark = $service->update_lesson_progress( $lesson_id, array( 'status' => 'in_progress', 'progress_percent' => 1 ), $token );
	if ( $mark['ok'] ) {
		$progress_status                       = 'in_progress';
		$lesson_progress[ (string) $lesson_id ] = 'in_progress';
	}
}

/**
 * Chỉ nhúng video từ YouTube/Vimeo (đổi link xem thường sang link embed);
 * nguồn khác hiện nút mở video ở tab mới.
 */
$video_url   = (string) ( $lesson['video_url'] ?? '' );
$video_embed = '';
if ( '' !== $video_url ) {
	if ( preg_match( '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $video_url, $m ) ) {
		$video_embed = 'https://www.youtube-nocookie.com/embed/' . $m[1];
	} elseif ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $video_url, $m ) ) {
		$video_embed = 'https://player.vimeo.com/video/' . $m[1];
	}
}

cvc_seo_set_title( (string) $lesson['title'] . ' — ' . (string) ( $course['title'] ?? 'Khóa học' ) );
if ( ! empty( $lesson['short_description'] ) ) {
	cvc_seo_set_description( wp_trim_words( (string) $lesson['short_description'], 25 ) );
}

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Khóa học', 'url' => cvc_courses_url() ),
	array( 'label' => (string) ( $course['title'] ?? 'Khóa học' ), 'url' => cvc_course_url( $course_slug ) ),
	array( 'label' => (string) $lesson['title'] ),
);

cvc_seo_set_canonical( cvc_course_lesson_url( $course_slug, $lesson_id ) );
cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );

$status_meta = array(
	'completed'   => array( 'fa-circle-check text-emerald-400', 'Đã học xong' ),
	'in_progress' => array( 'fa-circle-half-stroke text-amber-400', 'Đang học' ),
);

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">
	<?php cvc_render_track_marker( 'lesson_viewed', 'course_lesson', $lesson_id ); ?>

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>
		<?php cvc_render_notice(); ?>

		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/30 shadow-2xl space-y-3">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
				<div class="space-y-2 max-w-3xl">
					<div class="flex items-center gap-2 flex-wrap text-xs">
						<?php if ( $position ) : ?>
							<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">
								Bài <?php echo esc_html( $position ); ?>/<?php echo esc_html( count( $flat_lessons ) ); ?>
							</span>
						<?php endif; ?>
						<?php cvc_render_free_badge( ! empty( $lesson['is_free'] ) ); ?>
						<?php if ( isset( $status_meta[ $progress_status ] ) ) : ?>
							<span class="text-[11px] font-bold text-slate-200 flex items-center gap-1"><i class="fa-solid <?php echo esc_attr( $status_meta[ $progress_status ][0] ); ?>" aria-hidden="true"></i> <?php echo esc_html( $status_meta[ $progress_status ][1] ); ?></span>
						<?php endif; ?>
					</div>

					<h1 class="text-xl sm:text-3xl font-black text-white leading-snug"><?php echo esc_html( $lesson['title'] ); ?></h1>

					<p class="text-xs text-slate-300">
						Khóa học: <a href="<?php echo esc_url( cvc_course_url( $course_slug ) ); ?>" class="text-amber-400 font-bold hover:underline"><?php echo esc_html( $course['title'] ?? '' ); ?></a>
						<?php if ( ! empty( $lesson['duration_minutes'] ) ) : ?>
							&middot; <?php echo esc_html( (int) $lesson['duration_minutes'] ); ?> phút
						<?php endif; ?>
					</p>
				</div>

				<?php if ( $enrollment ) : ?>
					<?php $pct = max( 0, min( 100, (int) ( $enrollment['progress_percent'] ?? 0 ) ) ); ?>
					<div class="w-full md:w-56 shrink-0 text-xs space-y-1">
						<div class="flex justify-between font-bold text-slate-300"><span>Tiến độ khóa học</span><span class="text-cyan-300"><?php echo esc_html( $pct ); ?>%</span></div>
						<div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden" role="progressbar" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-valuemin="0" aria-valuemax="100">
							<div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500" style="width: <?php echo esc_attr( $pct ); ?>%"></div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<aside class="lg:col-span-3 order-2 lg:order-1">
				<nav class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs" aria-label="Giáo trình khóa học">
					<h2 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">Giáo trình (<?php echo esc_html( count( $flat_lessons ) ); ?> bài)</h2>
					<ol class="space-y-2 max-h-[550px] overflow-y-auto pr-1">
						<?php foreach ( $flat_lessons as $l_idx => $l_item ) : ?>
							<?php
							$l_id     = (int) $l_item['id'];
							$is_curr  = $l_id === $lesson_id;
							$l_status = $status_meta[ $lesson_progress[ (string) $l_id ] ?? $lesson_progress[ $l_id ] ?? '' ] ?? null;
							?>
							<li>
								<a href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, $l_id ) ); ?>" class="block p-3 rounded-xl border transition-colors space-y-1 <?php echo $is_curr ? 'bg-amber-500/10 border-amber-500/50 text-amber-300 font-extrabold' : 'bg-slate-900 hover:bg-slate-800 border-slate-800 text-slate-300'; ?>" <?php echo $is_curr ? 'aria-current="page"' : ''; ?>>
									<span class="flex items-center justify-between text-[10px]">
										<span>Bài <?php echo esc_html( $l_idx + 1 ); ?></span>
										<span class="flex items-center gap-1.5">
											<?php if ( $l_status ) : ?><i class="fa-solid <?php echo esc_attr( $l_status[0] ); ?>" title="<?php echo esc_attr( $l_status[1] ); ?>" aria-label="<?php echo esc_attr( $l_status[1] ); ?>"></i><?php endif; ?>
											<?php cvc_render_free_badge( ! empty( $l_item['is_free'] ) ); ?>
										</span>
									</span>
									<span class="block text-xs leading-snug line-clamp-2"><?php echo esc_html( $l_item['title'] ?? '' ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>
				</nav>
			</aside>

			<div class="lg:col-span-6 space-y-4 order-1 lg:order-2">

				<?php if ( ! $can_access ) : ?>

					<div class="bg-[#0A192F] border border-amber-500/40 p-8 rounded-3xl text-center space-y-4 shadow-xl">
						<div class="w-16 h-16 mx-auto rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl border border-amber-500/40" aria-hidden="true">
							<i class="fa-solid fa-lock"></i>
						</div>
						<h2 class="text-xl font-black text-white">Bài học dành cho học viên của khóa</h2>
						<p class="text-xs text-slate-300 max-w-md mx-auto">Mua khóa học để mở toàn bộ bài học, lưu tiến độ và nhận chứng chỉ khi hoàn thành.</p>
						<?php if ( $logged_in ) : ?>
							<a href="<?php echo esc_url( cvc_course_url( $course_slug ) ); ?>" class="inline-block px-6 py-3 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow">Xem học phí &amp; mua khóa học</a>
						<?php else : ?>
							<a href="<?php echo esc_url( cvc_login_url( cvc_course_lesson_url( $course_slug, $lesson_id ) ) ); ?>" class="inline-block px-6 py-3 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow">Đăng nhập</a>
							<p class="text-[11px] text-slate-400">Đã mua khóa học? Đăng nhập để mở bài.</p>
						<?php endif; ?>
					</div>

				<?php else : ?>

					<?php if ( $logged_in && ! $is_enrolled && ! empty( $course['can_enroll'] ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 bg-cyan-500/10 border border-cyan-500/30 rounded-2xl text-xs text-cyan-100">
							<span>Ghi danh khóa học để lưu tiến độ từng bài và nhận chứng chỉ khi học xong.</span>
							<?php wp_nonce_field( 'cvc_course_enroll' ); ?>
							<input type="hidden" name="action" value="cvc_course_enroll">
							<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) (int) ( $course['id'] ?? 0 ) ); ?>">
							<input type="hidden" name="course_slug" value="<?php echo esc_attr( $course_slug ); ?>">
							<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $lesson_id ); ?>">
							<button type="submit" class="px-4 py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black rounded-xl shrink-0">Ghi danh</button>
						</form>
					<?php elseif ( ! $logged_in ) : ?>
						<p class="p-4 bg-slate-800/60 border border-slate-700 rounded-2xl text-xs text-slate-300">
							Bạn đang học thử. <a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_login_url( cvc_course_lesson_url( $course_slug, $lesson_id ) ) ); ?>">Đăng nhập</a> để lưu tiến độ.
						</p>
					<?php endif; ?>

					<article class="bg-[#0A192F] border border-slate-800 rounded-3xl p-4 sm:p-5 space-y-4 shadow-xl">
						<?php if ( '' !== $video_embed ) : ?>
							<div class="aspect-video bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden max-w-full">
								<iframe src="<?php echo esc_url( $video_embed ); ?>" title="<?php echo esc_attr( 'Video: ' . $lesson['title'] ); ?>" class="w-full h-full" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
							</div>
						<?php elseif ( '' !== $video_url ) : ?>
							<a href="<?php echo esc_url( $video_url ); ?>" target="_blank" rel="noopener" class="flex items-center gap-3 p-4 bg-slate-950 rounded-2xl border border-slate-800 text-cyan-300 font-bold text-xs">
								<i class="fa-solid fa-circle-play text-2xl text-amber-400" aria-hidden="true"></i> Mở video bài giảng
							</a>
						<?php endif; ?>

						<?php if ( ! empty( $lesson['short_description'] ) ) : ?>
							<p class="text-sm text-slate-200 font-semibold"><?php echo esc_html( (string) $lesson['short_description'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $lesson['content'] ) ) : ?>
							<div class="cvc-prose text-xs sm:text-sm text-slate-300 leading-relaxed space-y-3">
								<?php echo wp_kses_post( wpautop( (string) $lesson['content'] ) ); ?>
							</div>
						<?php elseif ( '' === $video_url && empty( $lesson['file_url'] ) ) : ?>
							<?php cvc_render_empty_state( 'Bài học này chưa có nội dung.' ); ?>
						<?php endif; ?>

						<?php if ( ! empty( $lesson['file_url'] ) ) : ?>
							<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 flex items-center justify-between gap-3 text-xs">
								<span class="flex items-center gap-3 text-white font-bold">
									<i class="fa-solid fa-paperclip text-cyan-400" aria-hidden="true"></i> Tài liệu đính kèm bài học
								</span>
								<a href="<?php echo esc_url( (string) $lesson['file_url'] ); ?>" target="_blank" rel="noopener" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow flex items-center gap-1">
									<i class="fa-solid fa-download" aria-hidden="true"></i> Tải về
								</a>
							</div>
						<?php endif; ?>

						<?php foreach ( (array) ( $lesson['children'] ?? array() ) as $child ) : ?>
							<?php if ( ! empty( $child['id'] ) ) : ?>
								<a href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, (int) $child['id'] ) ); ?>" class="block p-3 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 text-xs text-slate-200">
									<i class="fa-solid fa-angle-right text-amber-400" aria-hidden="true"></i> <?php echo esc_html( (string) ( $child['title'] ?? '' ) ); ?>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</article>

				<?php endif; ?>
			</div>

			<aside class="lg:col-span-3 space-y-4 order-3">

				<?php if ( $can_access && $is_enrolled ) : ?>
					<div class="bg-[#0A192F] border border-emerald-500/30 p-4 rounded-2xl space-y-2 shadow-xl text-xs">
						<?php if ( 'completed' === $progress_status ) : ?>
							<p class="text-emerald-400 font-black flex items-center gap-2"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Bạn đã học xong bài này</p>
						<?php else : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'cvc_lesson_progress' ); ?>
								<input type="hidden" name="action" value="cvc_lesson_progress">
								<input type="hidden" name="status" value="completed">
								<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $lesson_id ); ?>">
								<input type="hidden" name="course_slug" value="<?php echo esc_attr( $course_slug ); ?>">
								<input type="hidden" name="next_lesson_id" value="<?php echo esc_attr( (string) (int) ( $next_lesson['id'] ?? 0 ) ); ?>">
								<button type="submit" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-400 text-navy-950 font-black rounded-xl shadow">
									<?php echo $next_lesson ? 'Hoàn thành &amp; học bài tiếp' : 'Đánh dấu hoàn thành'; ?>
								</button>
							</form>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<nav class="bg-[#0A192F] border border-slate-800 p-4 rounded-2xl space-y-2 shadow-xl text-xs" aria-label="Chuyển bài">
					<h2 class="font-extrabold text-[11px] text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2">Chuyển bài</h2>
					<?php if ( $prev_lesson ) : ?>
						<a href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, (int) $prev_lesson['id'] ) ); ?>" class="block p-2.5 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 text-slate-300 font-bold">
							&laquo; Bài trước: <?php echo esc_html( wp_trim_words( (string) $prev_lesson['title'], 6 ) ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $next_lesson ) : ?>
						<a href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, (int) $next_lesson['id'] ) ); ?>" class="block p-2.5 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 text-slate-300 font-bold">
							Bài tiếp: <?php echo esc_html( wp_trim_words( (string) $next_lesson['title'], 6 ) ); ?> &raquo;
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( cvc_course_url( $course_slug ) ); ?>" class="block p-2.5 text-cyan-300 font-bold">&larr; Tổng quan khóa học</a>
				</nav>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
