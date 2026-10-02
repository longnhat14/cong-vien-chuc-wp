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

if ( ! $is_found ) {
	$fallback_list = CVC_Subpage_Fixtures::get_exams();
	$s_lower       = strtolower( $slug );
	$is_english    = strpos( $s_lower, 'tieng-anh' ) !== false || strpos( $s_lower, 'ngoai-ngu' ) !== false || strpos( $s_lower, 'english' ) !== false;
	$is_it         = strpos( $s_lower, 'tin-hoc' ) !== false || strpos( $s_lower, 'cntt' ) !== false;

	foreach ( $fallback_list as $item ) {
		if ( isset( $item['slug'] ) && $item['slug'] === $slug ) {
			$exam     = $item;
			$is_found = true;
			break;
		}
	}

	if ( ! $is_found ) {
		foreach ( $fallback_list as $item ) {
			$item_slug = strtolower( $item['slug'] ?? '' );
			if ( $is_english && ( strpos( $item_slug, 'tieng-anh' ) !== false || strpos( $item_slug, 'ngoai-ngu' ) !== false ) ) {
				$exam     = $item;
				$is_found = true;
				break;
			}
			if ( $is_it && strpos( $item_slug, 'tin-hoc' ) !== false ) {
				$exam     = $item;
				$is_found = true;
				break;
			}
		}
	}

	if ( ! $is_found ) {
		$clean_title = ucwords( str_replace( '-', ' ', $slug ) );
		if ( $is_english ) {
			$clean_title = 'Đề Thi Thử Ngoại Ngữ Tiếng Anh Tuyển Dụng Công Chức Vòng 1 (Đề 01 - B1/B2)';
		} elseif ( $is_it ) {
			$clean_title = 'Đề Thi Trắc Nghiệm Tin Học Văn Phòng Chuẩn CNTT Công Chức';
		}
		$exam     = array(
			'id'               => 399,
			'slug'             => $slug,
			'title'            => $clean_title,
			'duration_minutes' => $is_english ? 30 : ( $is_it ? 30 : 60 ),
			'passing_score'    => $is_english ? '15/30 câu' : ( $is_it ? '15/30 câu' : '30/60 câu' ),
			'category'         => $is_english ? 'Ngoại Ngữ Công Vụ' : ( $is_it ? 'Tin Học Công Vụ' : 'Kiến Thức Chung' ),
			'description'      => $is_english ? 'Ngân hàng đề thi trắc nghiệm Ngoại ngữ Tiếng Anh B1/B2 tuyển dụng công chức Vòng 1 khoanh vùng ngữ pháp, từ vựng hành chính và đọc hiểu.' : ( $is_it ? 'Đề thi trắc nghiệm Tin học văn phòng chuẩn kỹ năng CNTT cơ bản.' : 'Ngân hàng đề thi trắc nghiệm Kiến thức chung khoanh vùng trọng tâm Luật Cán bộ, công chức, Nghị định 138/2020/NĐ-CP và các quy định sửa đổi mới nhất.' ),
		);
		$is_found = true;
	}
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
			<div class="bg-[#0D1B2A] border border-slate-800 p-8 rounded-3xl text-center space-y-4">
				<h1 class="text-2xl font-black text-white">Không tìm thấy đề thi</h1>
				<p class="text-xs text-slate-400">Đề thi bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">
					&larr; Xem tất cả đề thi
				</a>
			</div>
		<?php else : ?>
			<?php
			$s_lower    = strtolower( $slug );
			$is_english = strpos( $s_lower, 'tieng-anh' ) !== false || strpos( $s_lower, 'ngoai-ngu' ) !== false || strpos( $s_lower, 'english' ) !== false;
			$is_it      = strpos( $s_lower, 'tin-hoc' ) !== false || strpos( $s_lower, 'cntt' ) !== false;

			if ( ! empty( $exam['questions'] ) && is_array( $exam['questions'] ) ) {
				$questions = $exam['questions'];
			} elseif ( $is_english ) {
				$questions = CVC_Question_Bank_Fixtures::get_english_questions();
			} elseif ( $is_it ) {
				$questions = CVC_Question_Bank_Fixtures::get_it_questions();
			} else {
				$questions = CVC_Question_Bank_Fixtures::get_official_questions();
			}

			$duration   = (int) ( $exam['duration_minutes'] ?? ( $is_english ? 30 : 60 ) );
			$total_q    = count( $questions );
			$pass_score = $exam['passing_score'] ?? ( $is_english ? '15/30 câu' : '30/60 câu' );
			?>

			<!-- HERO EXAM HEADER BANNER -->
			<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/40 shadow-2xl space-y-4">
				<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
					<div class="space-y-3 max-w-3xl">
						<div class="flex items-center gap-2 flex-wrap text-xs">
							<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">
								🏛️ <?php echo esc_html( $exam['category'] ?? 'DE THI CHUAN NĐ 138/2020' ); ?>
							</span>
							<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-cyan-500/40">
								<?php echo $is_english ? 'ENGLISH-B1-EXAM' : ( $is_it ? 'IT-OFFICE-EXAM' : 'KTC-2026-EXAM' ); ?>
							</span>
						</div>

						<h1 class="text-2xl sm:text-4xl font-black text-white leading-snug">
							<?php echo esc_html( $exam['title'] ); ?>
						</h1>

						<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
							<?php echo esc_html( $exam['description'] ?? 'Ngân hàng đề thi trắc nghiệm khoanh vùng trọng tâm theo quy định của Bộ Nội vụ.' ); ?>
						</p>

						<div class="flex items-center gap-4 text-xs text-slate-400 pt-1">
							<span>⏱️ <?php echo $duration; ?> Phút</span>
							<span>📝 <?php echo $total_q; ?> Câu hỏi</span>
							<span class="text-emerald-400 font-bold">✓ Điểm đạt: <?php echo esc_html( $pass_score ); ?></span>
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
					
					<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-4 shadow-lg text-xs">
						<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
							<i class="fa-solid fa-sliders"></i> Thông Số Đề Thi
						</h3>

						<div class="space-y-2.5 text-slate-300">
							<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
								<span class="text-slate-400">Thời gian:</span>
								<strong class="text-white font-mono"><?php echo $duration; ?> Phút</strong>
							</div>
							<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
								<span class="text-slate-400">Số câu trắc nghiệm:</span>
								<strong class="text-cyan-300 font-mono"><?php echo $total_q; ?> Câu</strong>
							</div>
							<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
								<span class="text-slate-400">Hình thức:</span>
								<span class="text-emerald-400 font-bold">Trắc nghiệm 4 lựa chọn</span>
							</div>
							<div class="flex justify-between">
								<span class="text-slate-400">Chất lượng:</span>
								<span class="text-amber-400 font-bold">★ AI Verified 2026</span>
							</div>
						</div>
					</div>

					<!-- PDF DOWNLOAD CARD -->
					<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs">
						<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
							<i class="fa-solid fa-file-pdf"></i> Tải Đề Thi PDF
						</h3>
						<p class="text-slate-400 text-[11px]">
							Tải bản in PDF kèm đáp án chi tiết phục vụ luyện thi offline.
						</p>
						<a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/' . ( $is_english ? 'Tai-lieu-on-thi-Ngoai-ngu-Tieng-Anh-B1-Cong-Chuc.pdf' : 'Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf' ) ); ?>" download="<?php echo esc_attr( sanitize_title( $exam['title'] ) . '-2026.pdf' ); ?>" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold rounded-xl text-center text-xs transition-colors flex items-center justify-center gap-1.5">
							<i class="fa-solid fa-download"></i> Tải Bộ Đề PDF Chính Thức
						</a>
					</div>

				</aside>

				<!-- CENTER MAIN COLUMN (6 COLS — QUESTION PREVIEW) -->
				<main class="lg:col-span-6 space-y-4">

					<div class="bg-[#0D1B2A] border border-slate-800 rounded-3xl p-6 space-y-5 shadow-xl">
						<div class="flex items-center justify-between border-b border-slate-800 pb-3">
							<h2 class="text-base font-black text-white flex items-center gap-2">
								<i class="fa-solid fa-eye text-cyan-400"></i> Xem Trước Cấu Trúc Câu Hỏi (<?php echo min( 5, $total_q ); ?>/<?php echo $total_q; ?> câu)
							</h2>
							<span class="text-[10px] bg-cyan-500/20 text-cyan-300 font-bold px-2 py-0.5 rounded border border-cyan-500/30">
								SECURITY SAFE PREVIEW
							</span>
						</div>

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

				</main>

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
							Kích hoạt giao diện thi chuẩn <strong>EXAM OS X</strong> với bộ đếm giờ tự động & AI chấm điểm tức thì.
						</p>
					</div>

					<?php $attempt_url = cvc_exam_attempt_url( $slug ); ?>
					<a href="<?php echo esc_url( $attempt_url ); ?>" class="block w-full py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-2xl shadow-xl transition-transform hover:scale-105">
						🚀 Bắt Đầu Làm Bài Thi &rarr;
					</a>
					<p class="text-[10px] text-slate-400">✓ Miễn phí · Không cần đăng nhập</p>
				</div>

				<!-- UPSELL: TÀI LIỆU ÔN THI LIÊN QUAN -->
				<div class="bg-[#0D1B2A] border border-cyan-500/30 p-5 rounded-2xl space-y-3 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						📄 Cẩm Nang Ôn Thi Liên Quan
					</h3>
					<p class="text-slate-400 text-[11px]">Tải bộ tài liệu khoanh vùng trọng tâm theo đề thi này.</p>
					<a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/' . ( $is_english ? 'Tai-lieu-on-thi-Ngoai-ngu-Tieng-Anh-B1-Cong-Chuc.pdf' : 'Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf' ) ); ?>" download class="w-full py-2 bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-500/40 font-bold rounded-xl text-center transition-colors flex items-center justify-center gap-1.5">
						⬇ Tải PDF Miễn Phí
					</a>
				</div>

				<!-- UPSELL: KHÓA HỌC VIDEO -->
				<div class="bg-gradient-to-br from-amber-500/10 to-slate-900 border border-amber-500/30 p-5 rounded-2xl space-y-3 shadow-xl text-xs">
					<span class="bg-amber-500 text-navy-950 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">🔥 ĐỀ XUẤT</span>
					<div class="space-y-1 pt-1">
						<h3 class="text-sm font-black text-white leading-snug">Khóa Học Video<br>Chuyên Đề Vòng 1</h3>
						<p class="text-[11px] text-slate-300">120 bài giảng + AI Coach luyện riêng chuẩn sát hạch 2026.</p>
						<div class="flex items-baseline gap-2 pt-1">
							<span class="text-base font-black text-amber-400">599.000đ</span>
							<span class="text-xs text-slate-400 line-through">850.000đ</span>
						</div>
					</div>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="block w-full py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow-lg text-center transition-transform hover:scale-[1.02]">
						Đăng Ký Khóa Học &rarr;
					</a>
				</div>

			</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
