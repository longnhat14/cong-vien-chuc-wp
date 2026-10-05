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

// Slug không tồn tại -> 404 thật (trước đây hiện nội dung mẫu/fixture: soft-404, sai nội dung).
if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
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
			<?php cvc_render_track_marker( 'course_viewed', 'course', (int) ( $course['id'] ?? 0 ) ); ?>
			<?php
			/*
			 * Trạng thái học thật của người dùng (backend trả enrollment +
			 * lesson_progress khi có token). Danh sách bài phẳng (bài cha +
			 * bài con) theo đúng thứ tự để tìm "bài học tiếp".
			 */
			$enrollment      = is_array( $course['enrollment'] ?? null ) ? $course['enrollment'] : null;
			$lesson_progress = is_array( $course['lesson_progress'] ?? null ) ? $course['lesson_progress'] : array();
			$can_enroll      = ! empty( $course['can_enroll'] );
			$flat_lessons    = array();
			foreach ( $lessons as $parent_lesson ) {
				if ( ! empty( $parent_lesson['id'] ) ) {
					$flat_lessons[] = $parent_lesson;
				}
				foreach ( (array) ( $parent_lesson['children'] ?? array() ) as $child_lesson ) {
					if ( ! empty( $child_lesson['id'] ) ) {
						$flat_lessons[] = $child_lesson;
					}
				}
			}
			$next_lesson_id  = 0;
			foreach ( $flat_lessons as $flat_lesson ) {
				if ( 'completed' !== ( $lesson_progress[ (string) $flat_lesson['id'] ] ?? $lesson_progress[ (int) $flat_lesson['id'] ] ?? '' ) ) {
					$next_lesson_id = (int) $flat_lesson['id'];
					break;
				}
			}
			$first_lesson_id = ! empty( $flat_lessons[0]['id'] ) ? (int) $flat_lessons[0]['id'] : 0;
			$free_lesson_id  = 0;
			foreach ( $flat_lessons as $flat_lesson ) {
				if ( ! empty( $flat_lesson['is_free'] ) ) {
					$free_lesson_id = (int) $flat_lesson['id'];
					break;
				}
			}
			$status_labels = array(
				'completed'   => array( 'fa-circle-check text-emerald-400', 'Đã học xong' ),
				'in_progress' => array( 'fa-circle-half-stroke text-amber-400', 'Đang học' ),
			);
			?>

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
									<?php $l_status = $status_labels[ $lesson_progress[ (string) $lesson_id ] ?? $lesson_progress[ $lesson_id ] ?? '' ] ?? null; ?>
									<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, $lesson_id ) ); ?>" class="block p-2.5 bg-[#112240] hover:bg-cyan-500/10 hover:border-cyan-500/40 border border-[#1D3557] rounded-xl transition-all space-y-1 group">
										<div class="flex items-center justify-between text-[11px]">
											<span class="text-amber-400 font-bold">Bài <?php echo esc_html( $l_idx + 1 ); ?></span>
											<span class="flex items-center gap-1.5">
												<?php if ( $l_status ) : ?>
													<i class="fa-solid <?php echo esc_attr( $l_status[0] ); ?>" title="<?php echo esc_attr( $l_status[1] ); ?>" aria-label="<?php echo esc_attr( $l_status[1] ); ?>"></i>
												<?php endif; ?>
												<?php cvc_render_free_badge( ! empty( $lesson['is_free'] ) ); ?>
											</span>
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
				<div class="lg:col-span-6 space-y-6">

					<div class="bg-[#0A192F] border border-slate-800 rounded-3xl p-4 sm:p-6 space-y-4 shadow-xl">
						<?php if ( $free_lesson_id && ! $enrollment ) : ?>
							<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, $free_lesson_id ) ); ?>" class="flex items-center gap-3 p-4 bg-slate-950 rounded-2xl border border-cyan-500/30 hover:border-cyan-400 text-cyan-300 font-bold text-xs">
								<i class="fa-solid fa-circle-play text-2xl text-amber-400" aria-hidden="true"></i>
								Học thử bài miễn phí
							</a>
						<?php endif; ?>

						<h2 class="text-lg font-black text-white border-b border-slate-800 pb-2">
							Mô Tả Chi Tiết Khóa Học
						</h2>

						<?php if ( ! empty( $course['description'] ) ) : ?>
							<div class="text-xs sm:text-sm text-slate-300 leading-relaxed space-y-3">
								<?php echo nl2br( esc_html( $course['description'] ) ); ?>
							</div>
						<?php elseif ( ! empty( $course['short_description'] ) ) : ?>
							<p class="text-xs sm:text-sm text-slate-300 leading-relaxed"><?php echo esc_html( (string) $course['short_description'] ); ?></p>
						<?php else : ?>
							<p class="text-xs text-slate-400">Khóa học chưa có mô tả chi tiết.</p>
						<?php endif; ?>
					</div>

				</div>

				<!-- RIGHT SIDEBAR (3 COLS — ENROLLMENT BOX & AI ASSISTANT) -->
				<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

					<!-- ENROLLMENT PRICING CARD -->
					<?php
					$course_price = (float) ( $course['price'] ?? 0 );
					$course_sale  = isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : $course_price;
					$is_owned     = ! empty( $course['owned'] );
					$is_free      = $course_price <= 0;
					$is_soon      = ! empty( $course['is_coming_soon'] ) && ! $is_owned;
					?>
					<div class="bg-gradient-to-r from-amber-500/10 via-slate-900 to-indigo-950 border-2 border-amber-500/40 p-5 rounded-2xl space-y-4 shadow-2xl">

						<?php if ( $is_owned ) : ?>
							<div class="flex items-center gap-2 text-emerald-400 font-black text-xs border-b border-slate-800 pb-3">
								<i class="fa-solid fa-circle-check"></i> Bạn đã sở hữu khóa học này
							</div>
						<?php elseif ( $is_soon ) : ?>
							<div class="space-y-1 border-b border-slate-800 pb-3">
								<p class="flex items-center gap-2 text-cyan-300 font-black text-sm"><i class="fa-solid fa-hourglass-half"></i> Sắp mở</p>
								<p class="text-[11px] text-slate-400">Khóa học đang biên soạn bài giảng, chưa nhận đăng ký. Trong lúc chờ, bạn có thể ôn bằng đề thi thử và bài học theo văn bản miễn phí.</p>
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

						<?php $published_count = (int) ( $course['published_lessons_count'] ?? count( $flat_lessons ) ); ?>
						<?php if ( $enrollment ) : ?>
							<?php $pct = max( 0, min( 100, (int) ( $enrollment['progress_percent'] ?? 0 ) ) ); ?>
							<div class="space-y-1.5 text-xs text-slate-300">
								<div class="flex justify-between font-bold">
									<span><?php echo 'completed' === ( $enrollment['status'] ?? '' ) ? 'Đã hoàn thành khóa học' : 'Tiến độ của bạn'; ?></span>
									<span class="text-cyan-300"><?php echo esc_html( $pct ); ?>%</span>
								</div>
								<div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden" role="progressbar" aria-valuenow="<?php echo esc_attr( $pct ); ?>" aria-valuemin="0" aria-valuemax="100">
									<div class="h-full bg-gradient-to-r from-cyan-500 to-emerald-500" style="width: <?php echo esc_attr( $pct ); ?>%"></div>
								</div>
								<p class="text-[11px] text-slate-400"><?php echo esc_html( (int) ( $enrollment['completed_lessons'] ?? 0 ) . '/' . $published_count ); ?> bài đã học xong</p>
							</div>
						<?php elseif ( ! $is_soon ) : ?>
							<ul class="space-y-2 text-xs text-slate-300">
								<li class="flex items-center gap-2"><span class="text-emerald-400" aria-hidden="true">✓</span> <?php echo esc_html( $published_count ); ?> bài học đã công bố</li>
								<?php if ( ! empty( $course['duration_minutes'] ) ) : ?>
									<li class="flex items-center gap-2"><span class="text-emerald-400" aria-hidden="true">✓</span> Tổng thời lượng <?php echo esc_html( (int) $course['duration_minutes'] ); ?> phút</li>
								<?php endif; ?>
								<li class="flex items-center gap-2"><span class="text-emerald-400" aria-hidden="true">✓</span> Theo dõi tiến độ, cấp chứng chỉ khi học xong</li>
							</ul>
						<?php endif; ?>

						<?php $cta_class = 'block w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs text-center rounded-xl shadow-lg'; ?>
						<?php if ( $is_soon ) : ?>
							<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="<?php echo esc_attr( $cta_class ); ?>">Làm đề thi thử miễn phí &rarr;</a>
							<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="block text-center text-[11px] text-cyan-300 font-bold">Học bài theo văn bản pháp luật</a>
						<?php elseif ( empty( $flat_lessons ) && ( $is_owned || $is_free ) ) : ?>
							<p class="text-[11px] text-slate-400 text-center">Khóa học chưa có bài học nào được công bố.</p>
						<?php elseif ( $enrollment ) : ?>
							<?php if ( 'completed' === ( $enrollment['status'] ?? '' ) ) : ?>
								<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, $first_lesson_id ) ); ?>" class="<?php echo esc_attr( $cta_class ); ?>">Ôn lại khóa học &rarr;</a>
								<a href="<?php echo esc_url( cvc_account_url( 'certificates' ) ); ?>" class="block text-center text-[11px] text-cyan-300 font-bold">Xem chứng chỉ của bạn</a>
							<?php else : ?>
								<a href="<?php echo esc_url( cvc_course_lesson_url( $slug, $next_lesson_id ?: $first_lesson_id ) ); ?>" class="<?php echo esc_attr( $cta_class ); ?>">Học tiếp &rarr;</a>
							<?php endif; ?>
						<?php elseif ( $can_enroll || $is_free ) : ?>
							<?php if ( cvc_is_logged_in() ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'cvc_course_enroll' ); ?>
									<input type="hidden" name="action" value="cvc_course_enroll">
									<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) (int) ( $course['id'] ?? 0 ) ); ?>">
									<input type="hidden" name="course_slug" value="<?php echo esc_attr( $slug ); ?>">
									<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $first_lesson_id ); ?>">
									<button type="submit" class="<?php echo esc_attr( $cta_class ); ?>"><?php echo $is_owned ? 'Bắt đầu học' : 'Ghi danh miễn phí &amp; vào học'; ?> &rarr;</button>
								</form>
							<?php else : ?>
								<a href="<?php echo esc_url( cvc_login_url( cvc_course_url( $slug ) ) ); ?>" class="<?php echo esc_attr( $cta_class ); ?>">Đăng nhập để ghi danh</a>
							<?php endif; ?>
						<?php elseif ( cvc_is_logged_in() ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'cvc_course_buy' ); ?>
								<input type="hidden" name="action" value="cvc_course_buy">
								<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) ( $course['id'] ?? 0 ) ); ?>">
								<input type="hidden" name="course_slug" value="<?php echo esc_attr( $slug ); ?>">
								<button type="submit" class="<?php echo esc_attr( $cta_class ); ?>">
									<i class="fa-solid fa-cart-shopping mr-1" aria-hidden="true"></i> Mua khóa học — thanh toán VNPay
								</button>
							</form>
						<?php else : ?>
							<a href="<?php echo esc_url( cvc_login_url( cvc_course_url( $slug ) ) ); ?>" class="<?php echo esc_attr( $cta_class ); ?>">Đăng nhập để mua khóa học</a>
						<?php endif; ?>
					</div>

				</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
