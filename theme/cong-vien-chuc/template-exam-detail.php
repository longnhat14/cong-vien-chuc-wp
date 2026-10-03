<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT ĐỀ THI TRẮC NGHIỆM AI (Executive 3-Column Architecture)
 * URL: /thi-trac-nghiem/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_exam_slug' ) );

$service = new CVC_Exam_Service();
$result  = $service->find( $slug );

$exam     = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$exam     = is_array( $data ) ? $data : null;
	$is_found = null !== $exam;
}

// Slug không tồn tại -> 404 thật (trước đây hiện nội dung mẫu/fixture: soft-404, sai nội dung).
if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
}

cvc_seo_set_title( $is_found ? (string) $exam['title'] : 'Chi tiết đề thi trắc nghiệm' );

$breadcrumb_items = array(
	array(
		'label' => 'Trang chủ',
		'url'   => home_url( '/' ),
	),
	array(
		'label' => 'Thi trắc nghiệm',
		'url'   => cvc_exams_url(),
	),
	array( 'label' => $is_found ? (string) $exam['title'] : 'Chi tiết đề thi' ),
);

if ( $is_found ) {
	if ( ! empty( $exam['description'] ) ) {
		cvc_seo_set_description( (string) $exam['description'] );
	}
	cvc_seo_set_canonical( cvc_exam_url( $slug ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="bg-[#0A192F] border border-slate-800 p-8 rounded-3xl text-center space-y-4">
				<h1 class="text-2xl font-black text-white">Không tìm thấy đề thi</h1>
				<p class="text-xs text-slate-400">Đề thi bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">
					&larr; Xem tất cả đề thi
				</a>
			</div>
		<?php else : ?>
			<?php
			// Chỉ dùng câu hỏi thật đã công bố của đề - không còn bộ câu hỏi mẫu.
			$questions  = is_array( $exam['questions'] ?? null ) ? $exam['questions'] : array();
			$duration   = (int) ( $exam['duration_minutes'] ?? 0 );
			$total_q    = (int) ( $exam['questions_count'] ?? count( $questions ) );
			$pass_score = null;
			if ( isset( $exam['passing_score'] ) && is_numeric( $exam['passing_score'] ) && (float) $exam['passing_score'] > 0 ) {
				$pass_score = rtrim( rtrim( number_format( (float) $exam['passing_score'], 2, ',', '.' ), '0' ), ',' );
				if ( isset( $exam['total_score'] ) && is_numeric( $exam['total_score'] ) && (float) $exam['total_score'] > 0 ) {
					$pass_score .= '/' . rtrim( rtrim( number_format( (float) $exam['total_score'], 2, ',', '.' ), '0' ), ',' ) . ' điểm';
				}
			}
			$exam_subject_names = array();
			foreach ( (array) ( $exam['exam_subjects'] ?? array() ) as $subject ) {
				if ( is_array( $subject ) && ! empty( $subject['name'] ) ) {
					$exam_subject_names[] = (string) $subject['name'];
				}
			}
			?>
			<?php cvc_render_track_marker( 'exam_viewed', 'exam', (int) ( $exam['id'] ?? 0 ) ); ?>

			<!-- HERO EXAM HEADER BANNER -->
			<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/40 shadow-2xl space-y-4">
				<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
					<div class="space-y-3 max-w-3xl">
						<div class="flex items-center gap-2 flex-wrap text-xs">
							<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">
								Đề thi trắc nghiệm
							</span>
							<?php if ( ! empty( $exam['code'] ) ) : ?>
								<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-cyan-500/40">
									Mã đề: <?php echo esc_html( (string) $exam['code'] ); ?>
								</span>
							<?php endif; ?>
							<?php foreach ( $exam_subject_names as $subject_name ) : ?>
								<span class="bg-slate-800 text-slate-200 text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-slate-700">
									<?php echo esc_html( $subject_name ); ?>
								</span>
							<?php endforeach; ?>
						</div>

						<h1 class="text-2xl sm:text-4xl font-black text-white leading-snug">
							<?php echo esc_html( $exam['title'] ); ?>
						</h1>

						<?php if ( ! empty( $exam['description'] ) ) : ?>
							<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
								<?php echo esc_html( (string) $exam['description'] ); ?>
							</p>
						<?php endif; ?>

						<div class="flex items-center gap-4 text-xs text-slate-400 pt-1 flex-wrap">
							<?php if ( $duration > 0 ) : ?><span><i class="fa-regular fa-clock" aria-hidden="true"></i> <?php echo esc_html( $duration ); ?> phút</span><?php endif; ?>
							<span><i class="fa-regular fa-file-lines" aria-hidden="true"></i> <?php echo esc_html( $total_q ); ?> câu hỏi</span>
							<?php if ( null !== $pass_score ) : ?><span class="text-emerald-400 font-bold">Điểm đạt: <?php echo esc_html( $pass_score ); ?></span><?php endif; ?>
						</div>
					</div>

					<div class="shrink-0 flex items-center gap-3">
						<?php cvc_render_bookmark_button( 'exam', (int) ( $exam['id'] ?? 0 ) ); ?>
					</div>
				</div>
			</section>

			<!-- 3-COLUMN SHELL GRID -->
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

				<!-- LEFT COLUMN (3 COLS — EXAM METADATA & SPECS) -->
				<aside class="lg:col-span-3 space-y-4">
					
					<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-4 shadow-lg text-xs">
						<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
							<i class="fa-solid fa-sliders"></i> Thông Số Đề Thi
						</h3>

						<div class="space-y-2.5 text-slate-300">
							<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
								<span class="text-slate-400">Thời gian:</span>
								<strong class="text-white font-mono"><?php echo $duration > 0 ? esc_html( $duration . ' phút' ) : 'Không giới hạn'; ?></strong>
							</div>
							<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
								<span class="text-slate-400">Số câu trắc nghiệm:</span>
								<strong class="text-cyan-300 font-mono"><?php echo esc_html( $total_q ); ?> câu</strong>
							</div>
							<?php if ( null !== $pass_score ) : ?>
								<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
									<span class="text-slate-400">Điểm đạt:</span>
									<strong class="text-emerald-400"><?php echo esc_html( $pass_score ); ?></strong>
								</div>
							<?php endif; ?>
							<div class="flex justify-between">
								<span class="text-slate-400">Hình thức:</span>
								<span class="text-emerald-400 font-bold">Trắc nghiệm, chấm tự động</span>
							</div>
						</div>
					</div>

				</aside>

				<!-- CENTER MAIN COLUMN (6 COLS — QUESTION PREVIEW) -->
				<div class="lg:col-span-6 space-y-4">

					<div class="bg-[#0A192F] border border-slate-800 rounded-3xl p-6 space-y-5 shadow-xl">
						<div class="flex items-center justify-between border-b border-slate-800 pb-3">
							<h2 class="text-base font-black text-white flex items-center gap-2">
								<i class="fa-solid fa-eye text-cyan-400"></i> Xem trước câu hỏi (<?php echo esc_html( min( 5, count( $questions ) ) ); ?>/<?php echo esc_html( $total_q ); ?> câu)
							</h2>
						</div>

						<?php if ( empty( $questions ) ) : ?>
							<?php cvc_render_empty_state( 'Đề thi này chưa có câu hỏi được công bố.' ); ?>
						<?php endif; ?>

						<div class="space-y-4">
							<?php
							$preview_questions = array_slice( $questions, 0, 5 );
							foreach ( $preview_questions as $q_idx => $q_item ) :
								$q_text          = $q_item['question_text'] ?? ( $q_item['question'] ?? ( $q_item['content'] ?? 'Nội dung câu hỏi trắc nghiệm.' ) );
								$raw_opts        = $q_item['options'] ?? array();
								$normalized_opts = array();
								if ( is_array( $raw_opts ) ) {
									foreach ( $raw_opts as $k => $v ) {
										if ( is_array( $v ) ) {
											$normalized_opts[] = array(
												'option_key'  => $v['option_key'] ?? $k,
												'option_text' => $v['option_text'] ?? ( $v['text'] ?? '' ),
											);
										} else {
											$normalized_opts[] = array(
												'option_key'  => is_string( $k ) ? $k : chr( 65 + (int) $k ),
												'option_text' => (string) $v,
											);
										}
									}
								}
								?>
								<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 space-y-3 text-xs">
									<h4 class="font-extrabold text-white leading-relaxed flex items-start gap-2">
										<span class="text-amber-400 font-mono shrink-0">Câu <?php echo $q_idx + 1; ?>:</span>
										<span><?php echo esc_html( $q_text ); ?></span>
									</h4>

									<div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-300 pt-1">
										<?php foreach ( $normalized_opts as $opt ) : ?>
											<div class="p-2 bg-[#081726] border border-slate-800 rounded-xl flex items-center gap-2">
												<span class="w-5 h-5 rounded-lg bg-slate-800 text-cyan-400 font-bold flex items-center justify-center text-[10px] shrink-0">
													<?php echo esc_html( $opt['option_key'] ); ?>
												</span>
												<span class="truncate"><?php echo esc_html( $opt['option_text'] ); ?></span>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

				</div>

				<!-- RIGHT SIDEBAR (3 COLS — LAUNCH EXAM OS X CTA + UPSELL) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

				<!-- PRIMARY CTA: BẮT ĐẦU THI -->
				<div class="bg-gradient-to-r from-amber-500/10 via-slate-900 to-indigo-950 border-2 border-amber-500/50 p-6 rounded-3xl space-y-4 shadow-2xl text-center">
					<div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500 text-navy-950 flex items-center justify-center font-black text-2xl shadow-xl">
						⚡
					</div>

					<div class="space-y-1">
						<h3 class="text-lg font-black text-white">Sẵn Sàng Làm Bài Thi?</h3>
						<p class="text-xs text-slate-300">
							Có đồng hồ đếm giờ, đánh dấu câu hỏi và chấm điểm ngay khi nộp bài.
						</p>
					</div>

					<?php if ( empty( $questions ) ) : ?>
						<p class="text-xs text-slate-400">Đề chưa có câu hỏi nên chưa thể làm bài.</p>
					<?php elseif ( ! cvc_is_logged_in() ) : ?>
						<a href="<?php echo esc_url( cvc_login_url( cvc_exam_url( $slug ) ) ); ?>" class="block w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-2xl shadow-xl">
							Đăng nhập để làm bài &rarr;
						</a>
						<p class="text-[10px] text-slate-400">Miễn phí. Cần tài khoản để lưu kết quả và xem giải thích từng câu.</p>
					<?php else : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'cvc_exam_start' ); ?>
							<input type="hidden" name="action" value="cvc_exam_start">
							<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) (int) ( $exam['id'] ?? 0 ) ); ?>">
							<input type="hidden" name="mode" value="mock">
							<button type="submit" class="block w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-2xl shadow-xl">
								Bắt đầu làm bài &rarr;
							</button>
						</form>
						<p class="text-[10px] text-slate-400">Bài làm được lưu tự động; nộp bài để xem điểm và giải thích.</p>
					<?php endif; ?>
				</div>

				<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-2 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider">Ôn thêm trước khi thi</h3>
					<p class="text-slate-400 text-[11px]">Xem khóa học và tài liệu ôn thi đang có trên hệ thống.</p>
					<div class="flex gap-2">
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex-1 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold rounded-xl text-center">Khóa học</a>
						<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="flex-1 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold rounded-xl text-center">Tài liệu</a>
					</div>
				</div>

			</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
