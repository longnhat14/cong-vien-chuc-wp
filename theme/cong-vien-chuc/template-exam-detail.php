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

/*
 * Vào thi ngay (18/10/2026): người đã đăng nhập mở đề là bắt đầu làm bài luôn - không còn bước
 * "xem trước câu hỏi". API tự mở lại lượt đang làm dở của đề nếu có (không tạo lượt trùng).
 * Khách / bot không có token -> vẫn thấy trang giới thiệu đề + nút đăng nhập (đăng nhập xong
 * quay lại đúng URL này và vào thi luôn).
 */
$start_error = '';
$token       = cvc_auth_token();
/*
 * 19/10/2026 - hai chế độ Thi thật / Ôn tập: đồng hồ thi thật do máy chủ đếm ngay khi bắt đầu, nên KHÔNG
 * tự mở lượt mới khi chỉ xem đề. Có lượt đang làm dở -> mở lại ngay; có ?che-do=thi-that|on-tap -> bắt đầu
 * đúng chế độ đó; còn lại hiện trang đề với 2 nút chọn chế độ.
 */
$start_mode = array( 'thi-that' => 'exam', 'on-tap' => 'practice' )[ sanitize_key( (string) ( $_GET['che-do'] ?? '' ) ) ] ?? '';
if ( $is_found && null !== $token && '' === $start_mode ) {
	nocache_headers();
	$open = ( new CVC_Api_Client( null, 8 ) )->get( '/api/my-exam-attempts', array( 'status' => 'in_progress', 'exam_id' => (int) ( $exam['id'] ?? 0 ), 'per_page' => 1 ), $token );
	$open_id = (int) ( $open['data']['data']['data'][0]['id'] ?? ( $open['data']['data'][0]['id'] ?? 0 ) );
	if ( $open['ok'] && $open_id > 0 ) {
		wp_safe_redirect( cvc_exam_attempt_url( $open_id ) );
		exit;
	}
	if ( 401 === (int) ( $open['status'] ?? 0 ) ) {
		$token = null;
	}
}
if ( $is_found && null !== $token && '' !== $start_mode && (int) ( $exam['questions_count'] ?? count( (array) ( $exam['questions'] ?? array() ) ) ) > 0 ) {
	nocache_headers();
	$started = ( new CVC_Exam_Attempt_Service() )->start( (int) ( $exam['id'] ?? 0 ), $start_mode, $token );
	$att_id  = (int) ( $started['data']['data']['id'] ?? 0 );
	if ( $started['ok'] && $att_id > 0 ) {
		wp_safe_redirect( cvc_exam_attempt_url( $att_id ) );
		exit;
	}
	cvc_verify_redirect_if_required( $started, remove_query_arg( 'che-do', (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );
	if ( 401 === (int) ( $started['status'] ?? 0 ) ) {
		$token = null; // token hết hạn -> hiện nút đăng nhập.
	} else {
		$start_error = cvc_api_error_message( $started );
	}
}

// Đề cá nhân hóa theo tin (RP-, có phí - 20/10/2026): quyền làm đề / gói bán.
$pe_acc = $is_found ? cvc_pe_access( $exam, $token ) : null;
if ( is_array( $pe_acc ) && null !== $token ) {
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'cvc: quyền làm đề / kết quả thanh toán theo người dùng' );
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

<main id="main" class="dz-main">

	<div class="dz-wrap space-y-6">

		<!-- Breadcrumbs -->
		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="dz-panel text-center space-y-4">
				<h1 class="text-2xl font-black text-ink">Không tìm thấy đề thi</h1>
				<p class="text-xs text-ink-muted">Đề thi bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="inline-block px-5 py-2.5 bg-acc text-white font-black text-xs rounded-xl">
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

			<header class="dz-pagehead">
				<p class="dz-kicker">Đề thi trắc nghiệm<?php echo ! empty( $exam['code'] ) ? ' · Mã đề ' . esc_html( (string) $exam['code'] ) : ''; ?></p>
				<h1><?php echo esc_html( $exam['title'] ); ?></h1>
				<?php if ( ! empty( $exam['description'] ) ) : ?><p><?php echo esc_html( (string) $exam['description'] ); ?></p><?php endif; ?>
				<p class="dz-pagehead-stats">
					<?php echo esc_html( implode( ' · ', array_filter( array( $duration > 0 ? $duration . ' phút' : null, $total_q . ' câu hỏi', null !== $pass_score ? 'Điểm đạt ' . $pass_score : null, $exam_subject_names ? implode( ', ', $exam_subject_names ) : null ) ) ) ); ?>
				</p>
				<span class="dz-rule" aria-hidden="true"></span>
				<div style="margin-top:12px;display:flex;justify-content:center;flex-wrap:wrap;gap:8px"><?php cvc_render_bookmark_button( 'exam', (int) ( $exam['id'] ?? 0 ) ); ?><?php if ( 'published' === ( $exam['status'] ?? 'published' ) ) { echo cvc_pdf_button( 'exam', (int) ( $exam['id'] ?? 0 ), array( 'label' => 'Tải đề PDF (có đáp án)', 'variant' => 'dap-an' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</header>

			<!-- 3-COLUMN SHELL GRID -->
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

				<!-- LEFT COLUMN (3 COLS — EXAM METADATA & SPECS) -->
				<aside class="lg:col-span-3 space-y-4">
					
					<div class="dz-card space-y-4 text-xs">
						<h3 class="dz-sideh2">
							<i class="fa-solid fa-sliders"></i> Thông Số Đề Thi
						</h3>

						<div class="space-y-2.5 text-ink-2">
							<div class="flex justify-between border-b border-slate-300 pb-1.5">
								<span class="text-ink-muted">Thời gian:</span>
								<strong class="text-ink"><?php echo $duration > 0 ? esc_html( $duration . ' phút' ) : 'Không giới hạn'; ?></strong>
							</div>
							<div class="flex justify-between border-b border-slate-300 pb-1.5">
								<span class="text-ink-muted">Số câu trắc nghiệm:</span>
								<strong class="text-acc"><?php echo esc_html( $total_q ); ?> câu</strong>
							</div>
							<?php if ( null !== $pass_score ) : ?>
								<div class="flex justify-between border-b border-slate-300 pb-1.5">
									<span class="text-ink-muted">Điểm đạt:</span>
									<strong class="text-emerald-800"><?php echo esc_html( $pass_score ); ?></strong>
								</div>
							<?php endif; ?>
							<div class="flex justify-between">
								<span class="text-ink-muted">Hình thức:</span>
								<span class="text-emerald-800 font-bold">Trắc nghiệm, chấm tự động</span>
							</div>
						</div>
					</div>

				</aside>

				<!-- CENTER MAIN COLUMN (6 COLS — HƯỚNG DẪN; đã bỏ phần xem trước câu hỏi 18/10/2026) -->
				<div class="lg:col-span-6 space-y-4">
					<div class="dz-panel space-y-4 text-sm text-ink-2">
						<h2 class="text-base font-black text-ink flex items-center gap-2"><i class="fa-solid fa-circle-play text-acc"></i> Chọn chế độ làm bài</h2>
						<?php if ( '' !== $start_error ) : ?>
							<p class="text-red-700 font-bold"><?php echo esc_html( $start_error ); ?></p>
						<?php endif; ?>
						<?php if ( cvc_email_unverified() ) : ?>
							<p class="border border-amber-300 bg-amber-50 text-amber-900 p-3">✉ Tài khoản cần <b>xác minh email</b> trước khi làm bài. <a class="font-bold underline text-ink" href="<?php echo esc_url( cvc_verify_email_url( cvc_verify_return_path( (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) ); ?>">Nhập mã xác minh →</a></p>
						<?php endif; ?>
						<?php if ( empty( $questions ) && 0 === $total_q ) : ?>
							<?php cvc_render_empty_state( 'Đề thi này chưa có câu hỏi được công bố.' ); ?>
						<?php else : ?>
							<p>Đề gồm <strong><?php echo esc_html( (string) $total_q ); ?> câu</strong><?php echo $duration > 0 ? ', thời gian <strong>' . esc_html( (string) $duration ) . ' phút</strong>' : ''; ?>. Câu hỏi chỉ hiện khi bạn bắt đầu làm bài.</p>
							<div class="grid sm:grid-cols-2 gap-3 my-3">
								<div class="border border-line p-3">
									<p class="font-black text-ink">Thi thật</p>
									<p class="text-ink-2">Mô phỏng phòng thi: đồng hồ <?php echo $duration > 0 ? esc_html( (string) $duration ) . ' phút ' : ''; ?>do máy chủ kiểm soát, hết giờ tự nộp. Đáp án, giải thích và căn cứ pháp luật chỉ hiện sau khi nộp bài.</p>
								</div>
								<div class="border border-line p-3">
									<p class="font-black text-ink">Ôn tập</p>
									<p class="text-ink-2">Không tính giờ. Trả lời xong từng câu là thấy ngay đáp án đúng, giải thích và Điều luật làm căn cứ; câu đã chấm không đổi được.</p>
								</div>
							</div>
							<?php if ( null !== $token ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap gap-2">
									<?php wp_nonce_field( 'cvc_exam_start' ); ?>
									<input type="hidden" name="action" value="cvc_exam_start">
									<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) (int) ( $exam['id'] ?? 0 ) ); ?>">
									<button type="submit" name="mode" value="exam" class="px-5 py-2.5 text-white font-black rounded-xl bg-acc hover:bg-acc-deep">Thi thật &rarr;</button>
									<button type="submit" name="mode" value="practice" class="px-5 py-2.5 font-black rounded-xl bg-white text-acc border border-acc-line hover:bg-acc-soft">Ôn tập &rarr;</button>
								</form>
							<?php endif; ?>
							<ul class="list-disc pl-5 space-y-1 mt-3">
								<li>Mỗi đáp án được lưu lên máy chủ ngay khi chọn; mất mạng hay tải lại trang vẫn khôi phục được bài làm.</li>
								<li>Trước khi nộp có bảng rà soát câu bỏ trống, câu đánh dấu - bấm số câu để mở lại.</li>
								<li>Nộp bài xong mở ngay trang Kết quả &amp; Phân tích: điểm, câu sai, căn cứ văn bản và bài luyện đề xuất.</li>
							</ul>
						<?php endif; ?>
					</div>
				</div>

				<!-- RIGHT SIDEBAR (3 COLS — LAUNCH EXAM OS X CTA + UPSELL) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

				<?php if ( is_array( $pe_acc ) ) { cvc_pe_render_paybox( $exam, $pe_acc, $token, cvc_exam_url( $slug ) ); } ?>

				<!-- PRIMARY CTA: BẮT ĐẦU THI -->
				<div class="border-2 border-acc-line p-6 rounded-3xl space-y-4 text-center">
					<div class="w-14 h-14 mx-auto rounded-2xl bg-acc text-white flex items-center justify-center font-black text-2xl">
						⚡
					</div>

					<div class="space-y-1">
						<h3 class="text-lg font-black text-ink">Sẵn Sàng Làm Bài Thi?</h3>
						<p class="text-xs text-ink-2">
							Thi thật tính giờ như phòng thi; Ôn tập xem đáp án ngay sau mỗi câu.
						</p>
					</div>

					<?php if ( empty( $questions ) && 0 === $total_q ) : ?>
						<p class="text-xs text-ink-muted">Đề chưa có câu hỏi nên chưa thể làm bài.</p>
					<?php elseif ( null === $token ) : ?>
						<a href="<?php echo esc_url( cvc_login_url( cvc_exam_url( $slug ) ) ); ?>" rel="nofollow" class="block w-full py-3.5 text-white font-black text-sm rounded-2xl bg-acc hover:bg-acc-deep">
							Đăng nhập để vào thi &rarr;
						</a>
						<p class="text-[10px] text-ink-muted"><?php echo is_array( $pe_acc ) && ! empty( $pe_acc['locked'] ) ? esc_html( 'Làm thử ' . (int) ( $pe_acc['preview'] ?? 10 ) . ' câu đầu miễn phí; cần tài khoản để lưu kết quả.' ) : 'Miễn phí. Cần tài khoản để lưu kết quả và xem giải thích từng câu.'; ?></p>
					<?php else : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cvc-startmodes">
							<?php wp_nonce_field( 'cvc_exam_start' ); ?>
							<input type="hidden" name="action" value="cvc_exam_start">
							<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) (int) ( $exam['id'] ?? 0 ) ); ?>">
							<button type="submit" name="mode" value="exam" class="block w-full py-3 text-white font-black text-sm rounded-2xl bg-acc hover:bg-acc-deep">
								Thi thật &rarr;
								<span class="block text-[11px] font-normal opacity-90">Tính giờ, đáp án và giải thích hiện sau khi nộp bài</span>
							</button>
							<button type="submit" name="mode" value="practice" class="block w-full mt-2 py-3 font-black text-sm rounded-2xl bg-white text-acc border border-acc-line hover:bg-acc-soft">
								Ôn tập &rarr;
								<span class="block text-[11px] font-normal">Không tính giờ, xem đáp án, giải thích và căn cứ pháp luật ngay sau mỗi câu</span>
							</button>
						</form>
						<p class="text-[10px] text-ink-muted"><?php echo is_array( $pe_acc ) && ! empty( $pe_acc['locked'] ) ? esc_html( 'Chưa mua: mở lượt làm thử ' . (int) ( $pe_acc['preview'] ?? 10 ) . ' câu đầu.' ) : 'Đáp án được lưu lên máy chủ ngay khi chọn và khôi phục khi tải lại trang.'; ?></p>
					<?php endif; ?>
				</div>

				<?php if ( function_exists( 'cvc_ci_render_exam_jobs' ) ) { cvc_ci_render_exam_jobs( (int) ( $exam['id'] ?? 0 ) ); } // Career Intelligence: đề -> tin tuyển dụng ?>
				<?php if ( function_exists( 'cvc_render_leaderboard_mini' ) && 'published' === ( $exam['status'] ?? 'published' ) ) { cvc_render_leaderboard_mini( array( 'de' => (int) ( $exam['id'] ?? 0 ) ), 'Top thí sinh đề này' ); } // Bảng xếp hạng (21/10/2026) ?>

				<div class="dz-card space-y-2 text-xs">
					<h3 class="font-extrabold text-xs text-acc uppercase tracking-wider">Ôn thêm trước khi thi</h3>
					<p class="text-ink-muted text-[11px]">Xem khóa học và tài liệu ôn thi đang có trên hệ thống.</p>
					<div class="flex gap-2">
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex-1 py-2 bg-white hover:bg-acc-soft text-acc border border-acc-line font-bold rounded-xl text-center">Khóa học</a>
						<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="flex-1 py-2 bg-white hover:bg-acc-soft text-acc border border-acc-line font-bold rounded-xl text-center">Tài liệu</a>
					</div>
				</div>

			</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
