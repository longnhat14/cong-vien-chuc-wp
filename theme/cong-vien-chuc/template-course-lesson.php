<?php
/**
 * CÔNG VIÊN CHỨC — EXECUTIVE SMART CLASSROOM (Course Lesson Detail)
 * URL: /khoa-hoc/{slug}/bai-hoc/{lessonId}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_slug = sanitize_text_field( (string) get_query_var( 'cvc_course_slug' ) );
$lesson_id   = absint( get_query_var( 'cvc_lesson_id' ) );

$service       = new CVC_Course_Service();
$course_result = $service->find( $course_slug );

$course = null;
if ( $course_result['ok'] && is_array( $course_result['data']['data'] ?? null ) ) {
	$course = $course_result['data']['data'];
}

$lesson        = null;
$lesson_result = null;

if ( $course && $lesson_id ) {
	$lesson_result = $service->lesson( $course_slug, $lesson_id );

	if ( $lesson_result['ok'] && is_array( $lesson_result['data']['data'] ?? null ) ) {
		$lesson = $lesson_result['data']['data'];
	}
}

$is_found = null !== $course && null !== $lesson;

// Fallback logic for demo preview if API server offline
if ( ! $is_found ) {
	$fallback_courses = CVC_Subpage_Fixtures::get_courses();
	$course = $fallback_courses[0];
	$lessons_list = $course['lessons'] ?? array();
	$lesson = $lessons_list[0] ?? array(
		'id' => 1,
		'title' => 'Bài 1: Tổng Quan Luật Cán Bộ, Công Chức 2008 (Sửa Đổi 2019)',
		'is_free' => true,
		'duration_minutes' => 25,
		'content' => "Trong bài học này, chúng ta sẽ phân tích toàn bộ khung pháp lý cốt lõi của Luật Cán bộ, công chức năm 2008 và Luật sửa đổi, bổ sung năm 2019.\n\nNội dung bài học bao gồm:\n1. Phân biệt Cán bộ, Công chức và Viên chức.\n2. Các nguyên tắc quản lý cán bộ, công chức.\n3. Nghĩa vụ và quyền của công chức đối với Nhà nước và Nhân dân.",
		'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
		'file_url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-ubnd-huyen-ea-hleo-2026.pdf',
	);
	$is_found = true;
}

$siblings = array();
if ( $course && is_array( $course['lessons'] ?? null ) ) {
	foreach ( $course['lessons'] as $item ) {
		if ( isset( $item['id'] ) ) {
			$siblings[] = array(
				'id'    => (int) $item['id'],
				'title' => (string) ( $item['title'] ?? '' ),
			);
		}
	}
}

$prev_lesson = null;
$next_lesson = null;

if ( $is_found && ! empty( $siblings ) ) {
	foreach ( $siblings as $index => $item ) {
		if ( $item['id'] === $lesson_id ) {
			$prev_lesson = $siblings[ $index - 1 ] ?? null;
			$next_lesson = $siblings[ $index + 1 ] ?? null;
			break;
		}
	}
}

cvc_seo_set_title( $is_found ? (string) $lesson['title'] : 'Bài học khóa đào tạo' );

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Khóa học', 'url' => cvc_courses_url() ),
	array( 'label' => $course ? (string) $course['title'] : 'Khóa học', 'url' => $course ? cvc_course_url( $course_slug ) : '' ),
	array( 'label' => $is_found ? (string) $lesson['title'] : 'Bài học' ),
);

if ( $is_found ) {
	cvc_seo_set_canonical( cvc_course_lesson_url( $course_slug, $lesson_id ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<!-- HERO LESSON HEADER BANNER -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/40 shadow-2xl space-y-3">
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
				<div class="space-y-2 max-w-3xl">
					<div class="flex items-center gap-2 flex-wrap text-xs">
						<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">
							🎓 SMART CLASSROOM
						</span>
						<?php cvc_render_free_badge( ! empty( $lesson['is_free'] ) ); ?>
					</div>

					<h1 class="text-xl sm:text-3xl font-black text-white leading-snug">
						<?php echo esc_html( $lesson['title'] ); ?>
					</h1>

					<p class="text-xs text-slate-300">
						Khóa học: <a href="<?php echo esc_url( cvc_course_url( $course_slug ) ); ?>" class="text-amber-400 font-bold hover:underline"><?php echo esc_html( $course['title'] ?? '' ); ?></a>
						<?php if ( ! empty( $lesson['duration_minutes'] ) ) : ?>
							&middot; ⏱️ <?php echo (int) $lesson['duration_minutes']; ?> phút giảng dạy
						<?php endif; ?>
					</p>
				</div>

				<div class="flex items-center gap-2 shrink-0 text-xs">
					<a href="<?php echo esc_url( cvc_course_url( $course_slug ) ); ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold rounded-xl shadow transition-colors">
						&larr; Tổng Quan Khóa Học
					</a>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN SMART CLASSROOM LAYOUT -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — SYLLABUS DRAWER) -->
			<aside class="lg:col-span-3 space-y-4">
				
				<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						<i class="fa-solid fa-list-ol"></i> Giáo Trình Bài Học
					</h3>

					<div class="space-y-2 max-h-[550px] overflow-y-auto pr-1">
						<?php 
						$all_lessons = $course['lessons'] ?? array($lesson);
						foreach ( $all_lessons as $l_idx => $l_item ) :
							$l_id = (int) ($l_item['id'] ?? 0);
							$is_curr = $l_id === $lesson_id;
							$l_bg = $is_curr ? 'bg-amber-500/10 border-amber-500/50 text-amber-300 font-extrabold' : 'bg-slate-900 hover:bg-slate-800 border-slate-800 text-slate-300';
						?>
							<a href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, $l_id ) ); ?>" class="block p-3 rounded-xl border transition-all space-y-1 <?php echo $l_bg; ?>">
								<div class="flex items-center justify-between text-[10px]">
									<span>Bài <?php echo $l_idx + 1; ?></span>
									<?php cvc_render_free_badge( ! empty( $l_item['is_free'] ) ); ?>
								</div>
								<h4 class="text-xs leading-snug line-clamp-1">
									<?php echo esc_html( $l_item['title'] ?? '' ); ?>
								</h4>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — VIDEO PLAYER & PROSE) -->
			<main class="lg:col-span-6 space-y-4">

				<?php
				$has_content = ! empty( $lesson['content'] ) || ! empty( $lesson['video_url'] ) || ! empty( $lesson['file_url'] );
				?>

				<?php if ( ! $has_content && empty( $lesson['is_free'] ) ) : ?>

					<div class="bg-[#0D1B2A] border border-amber-500/40 p-8 rounded-3xl text-center space-y-4 shadow-xl">
						<div class="w-16 h-16 mx-auto rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-black text-2xl border border-amber-500/40">
							🔒
						</div>
						<h2 class="text-xl font-black text-white">Bài Học Khóa Trả Phí</h2>
						<p class="text-xs text-slate-300 max-w-md mx-auto">
							Bài học này chỉ dành riêng cho học viên đã đăng ký khóa học. Đăng ký ngay để mở khóa toàn bộ bài giảng HD & tài liệu ôn thi đính kèm.
						</p>
						<a href="<?php echo esc_url( cvc_course_url( $course_slug ) ); ?>" class="inline-block px-6 py-3 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow transition-transform hover:scale-105">
							Đăng Ký Mở Khóa Bài Học
						</a>
					</div>

				<?php else : ?>

					<!-- VIDEO PLAYER CONTAINER -->
					<div class="bg-[#0D1B2A] border border-slate-800 rounded-3xl p-4 sm:p-5 space-y-4 shadow-xl">
						<div class="aspect-video bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden relative flex items-center justify-center">
							<?php if ( ! empty( $lesson['video_url'] ) && str_contains($lesson['video_url'], 'embed') ) : ?>
								<iframe src="<?php echo esc_url( $lesson['video_url'] ); ?>" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
							<?php else : ?>
								<div class="text-center space-y-3 p-6">
									<div class="w-16 h-16 mx-auto rounded-full bg-amber-500 text-navy-950 font-black text-2xl flex items-center justify-center shadow-2xl">
										▶
									</div>
									<h3 class="font-black text-white text-sm">Video Bài Giảng HD (Chế độ xem trước)</h3>
									<span class="inline-block bg-cyan-500/20 text-cyan-300 text-[10px] font-bold px-3 py-1 rounded-full border border-cyan-500/30">
										Độ phân giải Full HD 1080p
									</span>
								</div>
							<?php endif; ?>
						</div>

						<h2 class="text-base font-black text-white border-b border-slate-800 pb-2 flex items-center gap-2">
							<i class="fa-solid fa-file-lines text-amber-400"></i> Nội Dung Bài Giảng Chi Tiết
						</h2>

						<?php if ( ! empty( $lesson['content'] ) ) : ?>
							<div class="text-xs sm:text-sm text-slate-300 leading-relaxed space-y-3">
								<?php echo wp_kses_post( $lesson['content'] ); ?>
							</div>
						<?php endif; ?>

						<!-- ATTACHED FILE DOWNLOAD BOX -->
						<?php if ( ! empty( $lesson['file_url'] ) ) : ?>
							<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 flex items-center justify-between text-xs">
								<div class="flex items-center gap-3">
									<div class="w-10 h-10 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-base">
										<i class="fa-solid fa-paperclip"></i>
									</div>
									<div>
										<h4 class="font-bold text-white">Tài Liệu Đính Kèm Bài Học</h4>
										<span class="text-[10px] text-slate-400 uppercase">PDF / DOCX &middot; 2.5 MB</span>
									</div>
								</div>
								<a href="<?php echo esc_url( $lesson['file_url'] ); ?>" download target="_blank" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow transition-colors flex items-center gap-1">
									<i class="fa-solid fa-download"></i> Tải Về
								</a>
							</div>
						<?php endif; ?>
					</div>

				<?php endif; ?>

			</main>

			<!-- RIGHT SIDEBAR (3 COLS — AI TUTOR & NAV) -->
			<aside class="lg:col-span-3 space-y-4">

				<!-- PREV / NEXT NAVIGATION BUTTONS -->
				<div class="bg-[#0D1B2A] border border-slate-800 p-4 rounded-2xl space-y-2 shadow-xl text-xs">
					<h3 class="font-extrabold text-[11px] text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2">
						Điều Hướng Bài Học
					</h3>
					<div class="space-y-2">
						<?php if ( $prev_lesson ) : ?>
							<a href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, $prev_lesson['id'] ) ); ?>" class="block p-2.5 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 text-slate-300 font-bold transition-colors">
								&laquo; Bài trước: <?php echo esc_html( wp_trim_words($prev_lesson['title'], 4) ); ?>
							</a>
						<?php endif; ?>
						<?php if ( $next_lesson ) : ?>
							<a href="<?php echo esc_url( cvc_course_lesson_url( $course_slug, $next_lesson['id'] ) ); ?>" class="block p-2.5 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black rounded-xl shadow transition-colors text-center">
								Bài tiếp theo: <?php echo esc_html( wp_trim_words($next_lesson['title'], 4) ); ?> &raquo;
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- AI LESSON TUTOR WIDGET -->
				<div class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 border border-cyan-500/30 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
					<div class="w-10 h-10 mx-auto rounded-full bg-cyan-500/20 text-cyan-300 flex items-center justify-center font-black text-base border border-cyan-500/40">
						🤖
					</div>
					<h4 class="font-extrabold text-white text-xs">Hỏi Đáp AI Bài Học</h4>
					<p class="text-slate-400 text-[11px]">
						Thắc mắc về nội dung bài giảng? Trợ lý AI sẵn sàng giải đáp 24/7.
					</p>
					<button type="button" onclick="alert('Trợ lý AI Lesson Tutor đang lắng nghe câu hỏi của bạn!')" class="w-full py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black text-xs rounded-xl shadow transition-colors">
						Đặt Câu Hỏi Cho AI
					</button>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
