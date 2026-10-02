<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT KHÓA HỌC ENTERPRISE (Executive 3-Column Architecture)
 * URL: /khoa-hoc/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_course_slug' ) );

$service = new CVC_Course_Service();
$result  = $service->find( $slug );

$course   = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$course   = is_array( $data ) ? $data : null;
	$is_found = null !== $course;
}

if ( ! $is_found ) {
	$fallback_list = CVC_Subpage_Fixtures::get_courses();
	$matched       = null;
	foreach ( $fallback_list as $item ) {
		if ( isset( $item['slug'] ) && $item['slug'] === $slug ) {
			$matched = $item;
			break;
		}
	}
	$course   = $matched ?? ( $fallback_list[0] ?? null );
	$is_found = null !== $course;
}

cvc_seo_set_title( $is_found ? (string) $course['title'] : 'Không tìm thấy khóa học' );

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Khóa học', 'url' => cvc_courses_url() ),
	array( 'label' => $is_found ? (string) $course['title'] : 'Không tìm thấy' ),
);

if ( $is_found ) {
	$description = $course['short_description'] ?? $course['description'] ?? '';
	if ( $description ) {
		cvc_seo_set_description( (string) $description );
	}
	cvc_seo_set_canonical( cvc_course_url( $slug ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );

	$course_schema = cvc_build_course_jsonld( $course );
	if ( null !== $course_schema ) {
		cvc_seo_add_json_ld( $course_schema );
	}
}

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="bg-[#0D1B2A] border border-slate-800 p-8 rounded-3xl text-center space-y-4">
				<h1 class="text-2xl font-black text-white">Không tìm thấy khóa học</h1>
				<p class="text-xs text-slate-400">Khóa học bạn tìm không tồn tại hoặc đã được chuyển hướng.</p>
				<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">
					&larr; Xem tất cả khóa học
				</a>
			</div>
		<?php else : ?>
			<?php $lessons = is_array( $course['lessons'] ?? null ) ? $course['lessons'] : array(); ?>

			<!-- HERO COURSE DETAIL HEADER -->
			<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/40 shadow-2xl space-y-4">
				<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
					<div class="space-y-3 max-w-3xl">
						<div class="flex items-center gap-2 flex-wrap">
							<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">
								🎓 HỌC VIỆN ENTERPRISE
							</span>
							<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-cyan-500/40">
								CHUẨN BỘ NỘI VỤ 2026
							</span>
						</div>

						<h1 class="text-2xl sm:text-4xl font-black text-white leading-snug">
							<?php echo esc_html( $course['title'] ); ?>
						</h1>

						<?php if ( ! empty( $course['short_description'] ) ) : ?>
							<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
								<?php echo esc_html( $course['short_description'] ); ?>
							</p>
						<?php endif; ?>

						<div class="flex items-center gap-4 text-xs text-slate-400 pt-1">
							<span>📚 <?php echo esc_html( sprintf( '%d bài học', (int) ( $course['published_lessons_count'] ?? count( $lessons ) ) ) ); ?></span>
							<span>⏱️ <?php echo esc_html( sprintf( '%d phút video HD', (int) ( $course['duration_minutes'] ?? 180 ) ) ); ?></span>
							<span class="text-amber-400 font-bold">★ 4.9/5.0 Rating</span>
						</div>
					</div>

					<div class="shrink-0 flex items-center gap-3">
						<?php cvc_render_bookmark_button( 'course', (int) ( $course['id'] ?? 0 ) ); ?>
					</div>
				</div>
			</section>

			<!-- 3-COLUMN SHELL GRID -->
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

				<!-- LEFT COLUMN (3 COLS — SYLLABUS LESSON LIST) -->
				<aside class="lg:col-span-3 space-y-4">
					
					<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg">
						<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">
							📜 Giáo Trình Bài Học (<?php echo count( $lessons ); ?>)
						</h3>

						<?php if ( empty( $lessons ) ) : ?>
							<p class="text-xs text-slate-400">Chưa có danh mục bài học.</p>
						<?php else : ?>
							<div class="space-y-2 max-h-[600px] overflow-y-auto pr-1 text-xs">
								<?php foreach ( $lessons as $l_idx => $lesson ) : ?>
									<?php $lesson_id = (int) ( $lesson['id'] ?? 0 ); ?>
									<?php if ( ! $lesson_id ) continue; ?>
									<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, $lesson_id ) ); ?>" class="block p-2.5 bg-[#09243a] hover:bg-cyan-500/10 hover:border-cyan-500/40 border border-[#12415d] rounded-xl transition-all space-y-1 group">
										<div class="flex items-center justify-between text-[11px]">
											<span class="text-amber-400 font-bold">Bài <?php echo $l_idx + 1; ?></span>
											<?php cvc_render_free_badge( ! empty( $lesson['is_free'] ) ); ?>
										</div>
										<h4 class="font-extrabold text-slate-200 group-hover:text-cyan-300 leading-snug line-clamp-1">
											<?php echo esc_html( $lesson['title'] ?? '' ); ?>
										</h4>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

				</aside>

				<!-- CENTER MAIN COLUMN (6 COLS — COURSE OVERVIEW & PROSE) -->
				<main class="lg:col-span-6 space-y-6">

					<!-- Video Player Preview Box -->
					<div class="bg-[#0D1B2A] border border-slate-800 rounded-3xl p-4 sm:p-6 space-y-4 shadow-xl">
						<div class="aspect-video bg-slate-950 rounded-2xl border border-slate-800 relative overflow-hidden flex items-center justify-center group cursor-pointer">
							<div class="w-16 h-16 rounded-full bg-amber-500 text-navy-950 font-black text-2xl flex items-center justify-center shadow-2xl group-hover:scale-110 transition-transform">
								▶
							</div>
							<span class="absolute bottom-3 left-3 bg-navy-950/80 text-cyan-300 text-[10px] font-bold px-3 py-1 rounded-full border border-cyan-500/30">
								Xem thử Bài 1 (Miễn phí 15 phút)
							</span>
						</div>

						<h2 class="text-lg font-black text-white border-b border-slate-800 pb-2">
							Mô Tả Chi Tiết Khóa Học
						</h2>

						<?php if ( ! empty( $course['description'] ) ) : ?>
							<div class="text-xs sm:text-sm text-slate-300 leading-relaxed space-y-3">
								<?php echo nl2br( esc_html( $course['description'] ) ); ?>
							</div>
						<?php else : ?>
							<p class="text-xs text-slate-400 leading-relaxed">
								Khóa học cung cấp hệ thống lý thuyết chuẩn hóa, khoanh vùng kiến thức trọng tâm Luật Cán bộ công chức, Nghị định 138/2020 và các Nghị định sửa đổi mới nhất năm 2026.
							</p>
						<?php endif; ?>
					</div>

				</main>

				<!-- RIGHT SIDEBAR (3 COLS — ENROLLMENT BOX & AI ASSISTANT) -->
				<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

					<!-- ENROLLMENT PRICING CARD -->
					<div class="bg-gradient-to-r from-amber-500/10 via-slate-900 to-indigo-950 border-2 border-amber-500/40 p-5 rounded-2xl space-y-4 shadow-2xl">
						<div class="flex items-baseline justify-between border-b border-slate-800 pb-2">
							<span class="text-xs text-slate-400">Học phí ưu đãi:</span>
							<div class="text-right">
								<span class="text-xs text-slate-400 line-through block">850.000đ</span>
								<span class="text-2xl font-black text-amber-400 block">599.000đ</span>
							</div>
						</div>

						<div class="space-y-2 text-xs text-slate-300">
							<span class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Sở hữu trọn đời 120 bài giảng HD</span>
							<span class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Đã bao gồm trọn bộ 50 đề thi thử PDF</span>
							<span class="flex items-center gap-2"><span class="text-emerald-400">✓</span> AI Coach chẩn đoán câu sai 24/7</span>
						</div>

						<?php if ( ! empty( $lessons[0]['id'] ) ) : ?>
							<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, (int) $lessons[0]['id'] ) ); ?>" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs text-center rounded-xl shadow-lg transition-transform hover:scale-105">
								Đăng Ký Học Ngay &rarr;
							</a>
						<?php else : ?>
							<button onclick="alert('Đã gửi yêu cầu đăng ký khóa học!')" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs text-center rounded-xl shadow-lg transition-transform hover:scale-105">
								Đăng Ký Học Ngay &rarr;
							</button>
						<?php endif; ?>
					</div>

				</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
