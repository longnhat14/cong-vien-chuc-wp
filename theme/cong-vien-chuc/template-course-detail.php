<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT KHÓA HỌC ENTERPRISE (Executive 3-Column Architecture)
 * URL: /khoa-hoc/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_course_slug' ) );

$token   = cvc_auth_token();
$service = new CVC_Course_Service();
$result  = $service->find( $slug, $token );

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
		<?php cvc_render_notice(); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="bg-[#0A192F] border border-slate-800 p-8 rounded-3xl text-center space-y-4">
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
								🎓 Khóa Học Ôn Thi 2026
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

						<?php
						$course_type_labels = array(
							'online_video' => '🎬 Video bài giảng HD',
							'live_zoom'    => '🔴 Học trực tiếp qua Zoom',
						);
						$course_type_label = $course_type_labels[ $course['course_type'] ?? '' ] ?? '🎓 Khóa học trực tuyến';
						?>
						<div class="flex items-center gap-4 text-xs text-slate-400 pt-1 flex-wrap">
							<span>📚 <?php echo esc_html( sprintf( '%d bài học', (int) ( $course['published_lessons_count'] ?? count( $lessons ) ) ) ); ?></span>
							<?php if ( ! empty( $course['duration_minutes'] ) ) : ?>
								<span>⏱️ <?php echo esc_html( sprintf( '%d phút', (int) $course['duration_minutes'] ) ); ?></span>
							<?php endif; ?>
							<span class="text-cyan-400 font-bold"><?php echo esc_html( $course_type_label ); ?></span>
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
					
					<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg">
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
									<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, $lesson_id ) ); ?>" class="block p-2.5 bg-[#112240] hover:bg-cyan-500/10 hover:border-cyan-500/40 border border-[#1D3557] rounded-xl transition-all space-y-1 group">
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
					<div class="bg-[#0A192F] border border-slate-800 rounded-3xl p-4 sm:p-6 space-y-4 shadow-xl">
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
					<?php
					$course_price = (float) ( $course['price'] ?? 0 );
					$course_sale  = isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : $course_price;
					$is_owned     = ! empty( $course['owned'] );
					$is_free      = $course_price <= 0;
					?>
					<div class="bg-gradient-to-r from-amber-500/10 via-slate-900 to-indigo-950 border-2 border-amber-500/40 p-5 rounded-2xl space-y-4 shadow-2xl">

						<?php if ( $is_owned ) : ?>
							<div class="flex items-center gap-2 text-emerald-400 font-black text-xs border-b border-slate-800 pb-3">
								<i class="fa-solid fa-circle-check"></i> Bạn đã sở hữu khóa học này
							</div>
						<?php elseif ( $is_free ) : ?>
							<div class="flex items-center gap-2 text-emerald-400 font-black text-sm border-b border-slate-800 pb-3">
								<i class="fa-solid fa-gift"></i> Khóa học miễn phí
							</div>
						<?php else : ?>
							<div class="flex items-baseline justify-between border-b border-slate-800 pb-3">
								<span class="text-xs text-slate-400">Học phí:</span>
								<div class="text-right">
									<?php if ( $course_sale < $course_price ) : ?>
										<span class="text-xs text-slate-400 line-through block"><?php echo esc_html( number_format( $course_price, 0, ',', '.' ) ); ?>đ</span>
									<?php endif; ?>
									<span class="text-2xl font-black text-amber-400 block"><?php echo esc_html( number_format( $course_sale, 0, ',', '.' ) ); ?>đ</span>
								</div>
							</div>
						<?php endif; ?>

						<div class="space-y-2 text-xs text-slate-300">
							<span class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Truy cập trọn đời <?php echo esc_html( (int) ( $course['published_lessons_count'] ?? count( $lessons ) ) ); ?> bài học</span>
							<?php if ( ! empty( $course['duration_minutes'] ) ) : ?>
								<span class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Tổng thời lượng <?php echo esc_html( (int) $course['duration_minutes'] ); ?> phút</span>
							<?php endif; ?>
							<span class="flex items-center gap-2"><span class="text-emerald-400">✓</span> Xem lại không giới hạn số lần</span>
						</div>

						<?php if ( ( $is_owned || $is_free ) && ! empty( $lessons[0]['id'] ) ) : ?>
							<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, (int) $lessons[0]['id'] ) ); ?>" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs text-center rounded-xl shadow-lg transition-transform hover:scale-105">
								Vào Học Ngay &rarr;
							</a>
						<?php elseif ( $is_owned || $is_free ) : ?>
							<p class="text-[11px] text-slate-400 text-center">Khóa học chưa có bài học nào được công bố.</p>
						<?php elseif ( cvc_is_logged_in() ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'cvc_course_buy' ); ?>
								<input type="hidden" name="action" value="cvc_course_buy">
								<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) ( $course['id'] ?? 0 ) ); ?>">
								<input type="hidden" name="course_slug" value="<?php echo esc_attr( $slug ); ?>">
								<button type="submit" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs text-center rounded-xl shadow-lg transition-transform hover:scale-105">
									<i class="fa-solid fa-cart-shopping mr-1"></i> Mua Khóa Học — Thanh Toán VNPay
								</button>
							</form>
						<?php else : ?>
							<a href="<?php echo esc_url( cvc_login_url( cvc_course_url( $slug ) ) ); ?>" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs text-center rounded-xl shadow-lg transition-transform hover:scale-105">
								Đăng Nhập Để Mua Khóa Học
							</a>
						<?php endif; ?>
					</div>

				</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
