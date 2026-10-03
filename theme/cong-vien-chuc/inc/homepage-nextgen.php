<?php
/**
 * Homepage Next-Gen (Phase 11, spec mục 14) - các khối dùng DỮ LIỆU THẬT:
 * Live Pulse, công cụ nhanh, Career Roadmap, mini quiz (Smart Exam),
 * tính lương & lương hưu, Legal Matrix. KHÔNG làm khối Community/Hỏi đáp
 * (đã chốt bỏ ngày 2026-09-19 - xem de-xuat-trang-chu-v2.md).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tham số tính lương/BHXH - MỘT nơi duy nhất, có nguồn. Cập nhật khi nhà
 * nước điều chỉnh.
 *
 * - Lương cơ sở 2.530.000đ/tháng từ 01/7/2026 (Nghị định 161/2026/NĐ-CP).
 * - Người lao động đóng: BHXH 8%, BHYT 1,5%, BHTN 1% (công chức không thuộc
 *   đối tượng BHTN; viên chức có).
 * - Tiền lương làm căn cứ đóng tối đa 20 lần mức tham chiếu (= lương cơ sở).
 * - Lương hưu (Luật BHXH 2024, từ 01/7/2025): nữ 45% cho 15 năm, +2%/năm;
 *   nam 45% cho 20 năm, +2%/năm; nam 15-<20 năm: 40% cho 15 năm, +1%/năm;
 *   tối đa 75%.
 *
 * @return array<string, mixed>
 */
function cvc_salary_params(): array {
	return array(
		'base_salary'        => 2530000,
		'base_salary_note'   => 'Lương cơ sở 2.530.000đ/tháng từ 01/7/2026 (Nghị định 161/2026/NĐ-CP)',
		'rates'              => array(
			'bhxh' => 0.08,
			'bhyt' => 0.015,
			'bhtn' => 0.01,
		),
		'cap_multiplier'     => 20,
		'pension'            => array(
			'min_years'   => 15,
			'max_rate'    => 0.75,
		),
		'sources'            => array(
			array( 'label' => 'Cổng TTĐT Chính phủ - tăng lương cơ sở từ 01/7/2026', 'url' => 'https://xaydungchinhsach.chinhphu.vn/tu-01-7-chinh-phu-tang-luong-co-so-119260517100243881.htm' ),
			array( 'label' => 'Cổng TTĐT Chính phủ - tỷ lệ hưởng lương hưu theo số năm đóng', 'url' => 'https://xaydungchinhsach.chinhphu.vn/tra-cuu-ty-le-huong-luong-huu-hang-thang-tinh-theo-so-nam-dong-bhxh-tu-1-7-11925040914175139.htm' ),
		),
	);
}

add_action( 'wp_enqueue_scripts', 'cvc_enqueue_homepage_nextgen' );

function cvc_enqueue_homepage_nextgen(): void {
	if ( ! is_front_page() ) {
		return;
	}

	wp_enqueue_script(
		'cvc-homepage-nextgen',
		get_theme_file_uri( '/assets/js/homepage-nextgen.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_localize_script(
		'cvc-homepage-nextgen',
		'cvcNextGen',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'cvc_mini_quiz' ),
			'homeUrl' => home_url( '/' ),
			'salary'  => cvc_salary_params(),
		)
	);
}

/* --- AJAX proxy mini quiz (không cache trang, token quiz luôn mới) --- */
add_action( 'wp_ajax_cvc_mini_quiz_load', 'cvc_handle_mini_quiz_load' );
add_action( 'wp_ajax_nopriv_cvc_mini_quiz_load', 'cvc_handle_mini_quiz_load' );

function cvc_handle_mini_quiz_load(): void {
	check_ajax_referer( 'cvc_mini_quiz', 'nonce' );

	$result = ( new CVC_Homepage_Service() )->miniQuiz( 5 );

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), $result['status'] ?: 500 );
	}

	wp_send_json_success( $result['data']['data'] ?? array() );
}

add_action( 'wp_ajax_cvc_mini_quiz_check', 'cvc_handle_mini_quiz_check' );
add_action( 'wp_ajax_nopriv_cvc_mini_quiz_check', 'cvc_handle_mini_quiz_check' );

function cvc_handle_mini_quiz_check(): void {
	check_ajax_referer( 'cvc_mini_quiz', 'nonce' );

	$quiz_token  = sanitize_text_field( wp_unslash( $_POST['quiz_token'] ?? '' ) );
	$question_id = absint( $_POST['question_id'] ?? 0 );
	$option_id   = absint( $_POST['option_id'] ?? 0 );

	if ( '' === $quiz_token || 0 === $question_id || 0 === $option_id ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}

	$result = ( new CVC_Homepage_Service() )->miniQuizCheck( $quiz_token, $question_id, $option_id );

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), $result['status'] ?: 500 );
	}

	wp_send_json_success( $result['data']['data'] ?? array() );
}

/**
 * 14.1 Top Live Pulse - thanh trạng thái sống ngay dưới header.
 *
 * @param array<string, mixed>|null $pulse
 */
function cvc_render_live_pulse( ?array $pulse ): void {
	if ( empty( $pulse ) ) {
		return;
	}

	$live      = is_array( $pulse['live'] ?? null ) ? $pulse['live'] : array();
	$deadlines = array_filter( (array) ( $pulse['upcoming_deadlines'] ?? array() ), 'is_array' );
	$legal     = array_filter( (array) ( $pulse['new_legal_documents'] ?? array() ), 'is_array' );
	$items     = array();

	foreach ( $deadlines as $d ) {
		$days    = (int) ( $d['days_left'] ?? 0 );
		$items[] = array(
			'label' => 0 === $days ? 'Hết hạn hôm nay' : ( 'Còn ' . $days . ' ngày' ),
			'text'  => (string) ( $d['title'] ?? '' ),
			'url'   => cvc_recruitment_url( (string) ( $d['slug'] ?? '' ) ),
			'tone'  => $days <= 3 ? 'text-rose-300' : 'text-amber-300',
		);
	}

	foreach ( array_slice( $legal, 0, 3 ) as $doc ) {
		$items[] = array(
			'label' => 'Văn bản mới',
			'text'  => trim( ( $doc['document_number'] ?? '' ) . ' ' . ( $doc['title'] ?? '' ) ),
			'url'   => cvc_legal_document_url( (string) ( $doc['slug'] ?? '' ) ),
			'tone'  => 'text-cyan-300',
		);
	}
	?>
	<section id="live-pulse" class="bg-navy-950 border-b border-cyan-500/20 text-xs text-slate-200" aria-label="Cập nhật tuyển dụng và văn bản mới">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex flex-col md:flex-row md:items-center gap-2 md:gap-4">
			<div class="flex flex-wrap items-center gap-x-4 gap-y-1 shrink-0">
				<span class="inline-flex items-center gap-1.5 font-black text-emerald-300"><span class="cvc-pulse-dot" aria-hidden="true"></span> TRỰC TIẾP</span>
				<span><strong class="text-white"><?php echo esc_html( number_format( (int) ( $live['open_recruitments'] ?? 0 ), 0, ',', '.' ) ); ?></strong> đợt đang nhận hồ sơ</span>
				<span><strong class="text-white"><?php echo esc_html( number_format( (int) ( $live['open_positions_quota'] ?? 0 ), 0, ',', '.' ) ); ?></strong> chỉ tiêu</span>
				<?php if ( ! empty( $live['closing_within_7_days'] ) ) : ?>
					<span class="text-rose-300 font-bold"><?php echo (int) $live['closing_within_7_days']; ?> đợt hết hạn trong 7 ngày</span>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $items ) ) : ?>
				<div class="cvc-ticker min-w-0 flex-1 overflow-hidden" tabindex="0" aria-label="Tin cập nhật">
					<ul class="cvc-ticker__track">
						<?php foreach ( array_merge( $items, $items ) as $i => $item ) : ?>
							<li <?php echo $i >= count( $items ) ? 'aria-hidden="true"' : ''; ?>>
								<span class="font-bold <?php echo esc_attr( $item['tone'] ); ?>"><?php echo esc_html( $item['label'] ); ?>:</span>
								<a href="<?php echo esc_url( $item['url'] ); ?>" class="hover:text-white" <?php echo $i >= count( $items ) ? 'tabindex="-1"' : ''; ?>><?php echo esc_html( wp_trim_words( $item['text'], 14 ) ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * 14.3 Hero Interactive Suite - công cụ nhanh có thật (không có "tra cứu
 * điểm" vì hệ thống không có dữ liệu điểm thi chính thức).
 */
function cvc_render_hero_tools(): void {
	$tools = array(
		array( 'icon' => 'fa-calendar-days', 'label' => 'Hạn nộp hồ sơ sắp tới', 'url' => '#live-pulse', 'tone' => 'text-amber-300' ),
		array( 'icon' => 'fa-file-signature', 'label' => 'Mẫu hồ sơ dự tuyển', 'url' => '#legal-matrix', 'tone' => 'text-cyan-300' ),
		array( 'icon' => 'fa-calculator', 'label' => 'Tính lương & lương hưu', 'url' => '#tinh-luong', 'tone' => 'text-emerald-300' ),
		array( 'icon' => 'fa-route', 'label' => 'Lộ trình thăng tiến', 'url' => '#lo-trinh-su-nghiep', 'tone' => 'text-gold-400' ),
	);
	?>
	<div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
		<?php foreach ( $tools as $tool ) : ?>
			<a href="<?php echo esc_url( $tool['url'] ); ?>" class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-slate-200 transition-colors">
				<i class="fa-solid <?php echo esc_attr( $tool['icon'] . ' ' . $tool['tone'] ); ?>"></i><span><?php echo esc_html( $tool['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * 14.4 Career Roadmap - bậc nghề nghiệp (career_ranks) + đề/khoá khớp tên.
 *
 * @param array<int, array<string, mixed>> $ranks
 */
function cvc_render_career_roadmap( array $ranks ): void {
	// Phòng thủ: dữ liệu API lạ không bao giờ được làm hỏng trang chủ.
	$ranks = array_values( array_filter( $ranks, fn ( $r ) => is_array( $r ) && ! empty( $r['code'] ) && ! empty( $r['name'] ) ) );

	if ( empty( $ranks ) ) {
		return;
	}
	$goal_url = cvc_is_logged_in() ? cvc_account_url( 'goals' ) : cvc_login_url();
	?>
	<section id="lo-trinh-su-nghiep" class="py-20 bg-slate-950 border-t border-slate-800">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
			<div class="text-center max-w-3xl mx-auto space-y-3">
				<span class="inline-block text-xs font-black uppercase tracking-widest text-gold-400 bg-gold-500/10 px-3.5 py-1.5 rounded-full border border-gold-500/30">Lộ trình sự nghiệp</span>
				<h2 class="text-2xl sm:text-4xl font-extrabold text-white">Bạn đang ở bậc nào - và cần ôn gì tiếp?</h2>
				<p class="text-xs sm:text-sm text-slate-400">Chọn bậc mục tiêu để xem đề thi và khóa học hiện có trên hệ thống phù hợp với bậc đó.</p>
			</div>

			<div class="cvc-roadmap" data-cvc-roadmap>
				<div class="flex flex-wrap justify-center gap-2" role="tablist" aria-label="Bậc nghề nghiệp">
					<?php foreach ( $ranks as $i => $rank ) : ?>
						<button type="button" role="tab" id="roadmap-tab-<?php echo esc_attr( $rank['code'] ); ?>" aria-controls="roadmap-panel-<?php echo esc_attr( $rank['code'] ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>" class="cvc-roadmap__tab px-4 py-2.5 rounded-xl border text-sm font-bold transition-colors">
							<span class="text-[10px] block opacity-70">Bậc <?php echo (int) ( $rank['level'] ?? $i + 1 ); ?></span><?php echo esc_html( (string) $rank['name'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>

				<?php foreach ( $ranks as $i => $rank ) : ?>
					<div role="tabpanel" id="roadmap-panel-<?php echo esc_attr( $rank['code'] ); ?>" aria-labelledby="roadmap-tab-<?php echo esc_attr( $rank['code'] ); ?>" class="mt-6" <?php echo 0 === $i ? '' : 'hidden'; ?>>
						<?php if ( empty( $rank['exams'] ) && empty( $rank['courses'] ) ) : ?>
							<p class="text-center text-sm text-slate-400">Chưa có đề thi hay khóa học dành riêng cho bậc này trên hệ thống. Bạn có thể đặt mục tiêu để nhận gợi ý khi có nội dung mới.</p>
						<?php else : ?>
							<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
								<?php foreach ( (array) ( $rank['courses'] ?? array() ) as $course ) : ?>
									<a href="<?php echo esc_url( cvc_course_url( (string) $course['slug'] ) ); ?>" class="block p-5 bg-navy-950 rounded-2xl border border-slate-800 hover:border-gold-500/60 space-y-2">
										<span class="text-[10px] font-black uppercase text-gold-400">Khóa học</span>
										<span class="block font-bold text-white leading-snug"><?php echo esc_html( (string) $course['title'] ); ?></span>
									</a>
								<?php endforeach; ?>
								<?php foreach ( (array) ( $rank['exams'] ?? array() ) as $exam ) : ?>
									<a href="<?php echo esc_url( cvc_exam_url( (string) $exam['slug'] ) ); ?>" class="block p-5 bg-navy-950 rounded-2xl border border-slate-800 hover:border-cyan-500/60 space-y-2">
										<span class="text-[10px] font-black uppercase text-cyan-300">Đề thi · <?php echo (int) ( $exam['total_questions'] ?? 0 ); ?> câu · <?php echo (int) ( $exam['duration_minutes'] ?? 0 ); ?> phút</span>
										<span class="block font-bold text-white leading-snug"><?php echo esc_html( (string) $exam['title'] ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

				<p class="text-center mt-8">
					<a href="<?php echo esc_url( $goal_url ); ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gold-500 to-gold-600 text-navy-950 font-black text-sm rounded-xl shadow-glow-gold">
						<i class="fa-solid fa-bullseye"></i> Đặt mục tiêu &amp; nhận lộ trình học
					</a>
				</p>
				<p class="text-center text-[11px] text-slate-500 mt-2">Nội dung ghép theo tên đề/khóa học, không phải danh mục ngạch bậc pháp lý.</p>
			</div>
		</div>
	</section>
	<?php
}

/**
 * 14.5 Smart Exam Simulator - mini quiz (câu hỏi thật, chấm ở server) +
 * đề thi mới nhất thật.
 *
 * @param array<int, array<string, mixed>> $exams
 */
function cvc_render_smart_exam_section( array $exams ): void {
	$exams = array_values( array_filter( $exams, 'is_array' ) );
	?>
	<section id="thi-thu-ai" class="py-20 bg-slate-950 border-t border-slate-800">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
			<div class="text-center max-w-3xl mx-auto space-y-3">
				<span class="inline-block text-xs font-black uppercase tracking-widest text-amber-400 bg-amber-500/10 px-3.5 py-1.5 rounded-full border border-amber-500/30">Thi thử nhanh</span>
				<h2 class="text-2xl sm:text-4xl font-extrabold text-white">Làm thử 5 câu trắc nghiệm ngay</h2>
				<p class="text-xs sm:text-sm text-slate-400">Câu hỏi lấy ngẫu nhiên từ các đề thi đang mở trên hệ thống, chấm điểm và giải thích ngay sau mỗi câu.</p>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
				<div class="lg:col-span-7 bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-4" data-cvc-mini-quiz>
					<div class="flex items-center justify-between text-xs">
						<span class="font-black text-amber-400" data-quiz-progress>Đang tải câu hỏi…</span>
						<span class="text-slate-400" data-quiz-score></span>
					</div>
					<div data-quiz-body class="space-y-3 min-h-[12rem]" aria-live="polite">
						<noscript><p class="text-slate-400 text-sm">Cần bật JavaScript để làm thử. Bạn có thể vào <a class="text-amber-300" href="<?php echo esc_url( cvc_exams_url() ); ?>">trang đề thi</a>.</p></noscript>
					</div>
					<div class="flex flex-wrap gap-2 justify-between">
						<button type="button" data-quiz-next class="hidden px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl">Câu tiếp theo &rarr;</button>
						<button type="button" data-quiz-reload class="hidden px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl">Làm bộ khác</button>
					</div>
				</div>

				<div class="lg:col-span-5 space-y-3">
					<h3 class="text-sm font-black text-white">Đề thi mới trên hệ thống</h3>
					<?php if ( empty( $exams ) ) : ?>
						<p class="text-sm text-slate-400">Chưa có đề thi.</p>
					<?php endif; ?>
					<?php foreach ( $exams as $exam ) : ?>
						<a href="<?php echo esc_url( cvc_exam_url( (string) ( $exam['slug'] ?? '' ) ) ); ?>" class="block p-4 bg-navy-950 rounded-2xl border border-slate-800 hover:border-amber-500/60">
							<span class="block text-sm font-bold text-white leading-snug"><?php echo esc_html( (string) ( $exam['title'] ?? '' ) ); ?></span>
							<span class="block text-[11px] text-slate-400 mt-1">
								<?php echo esc_html( implode( ' · ', array_filter( array(
									! empty( $exam['total_questions'] ) ? (int) $exam['total_questions'] . ' câu' : null,
									! empty( $exam['duration_minutes'] ) ? (int) $exam['duration_minutes'] . ' phút' : null,
									( isset( $exam['passing_score'], $exam['total_score'] ) && (float) $exam['total_score'] > 0 ) ? 'Đạt từ ' . rtrim( rtrim( (string) $exam['passing_score'], '0' ), '.' ) . '/' . rtrim( rtrim( (string) $exam['total_score'], '0' ), '.' ) . ' điểm' : null,
								) ) ) ); ?>
							</span>
						</a>
					<?php endforeach; ?>
					<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="inline-block text-xs font-bold text-amber-400 hover:underline">Xem tất cả đề thi &rarr;</a>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * 14.6 Salary & Pension - công cụ ước tính chạy hoàn toàn ở trình duyệt,
 * tham số từ cvc_salary_params().
 */
function cvc_render_salary_calculator(): void {
	$params = cvc_salary_params();
	?>
	<section id="tinh-luong" class="py-20 bg-slate-900 border-t border-slate-800">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
			<div class="text-center max-w-3xl mx-auto space-y-3">
				<span class="inline-block text-xs font-black uppercase tracking-widest text-emerald-300 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/30">Công cụ</span>
				<h2 class="text-2xl sm:text-4xl font-extrabold text-white">Ước tính lương &amp; lương hưu công chức, viên chức</h2>
				<p class="text-xs sm:text-sm text-slate-400"><?php echo esc_html( $params['base_salary_note'] ); ?>.</p>
			</div>

			<form class="grid grid-cols-1 lg:grid-cols-12 gap-6" data-cvc-salary onsubmit="return false;">
				<div class="lg:col-span-7 bg-navy-950 p-6 rounded-3xl border border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
					<label class="space-y-1"><span class="block font-bold text-slate-300">Đối tượng</span>
						<select id="sal-type" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
							<option value="cong_chuc">Công chức (không đóng BHTN)</option>
							<option value="vien_chuc">Viên chức (có đóng BHTN)</option>
						</select>
					</label>
					<label class="space-y-1"><span class="block font-bold text-slate-300">Hệ số lương</span>
						<input id="sal-coef" type="number" step="0.01" min="1" max="10" value="2.34" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
					</label>
					<label class="space-y-1"><span class="block font-bold text-slate-300">Hệ số phụ cấp chức vụ</span>
						<input id="sal-position" type="number" step="0.05" min="0" max="2" value="0" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
					</label>
					<label class="space-y-1"><span class="block font-bold text-slate-300">Thâm niên vượt khung (%)</span>
						<input id="sal-tnvk" type="number" step="1" min="0" max="50" value="0" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
					</label>
					<label class="space-y-1"><span class="block font-bold text-slate-300">Phụ cấp thâm niên nghề (%)</span>
						<input id="sal-tnn" type="number" step="1" min="0" max="50" value="0" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
					</label>
					<label class="space-y-1"><span class="block font-bold text-slate-300">Hệ số phụ cấp khu vực</span>
						<input id="sal-region" type="number" step="0.1" min="0" max="1" value="0" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
					</label>
					<label class="space-y-1"><span class="block font-bold text-slate-300">Hệ số phụ cấp trách nhiệm</span>
						<input id="sal-resp" type="number" step="0.1" min="0" max="1" value="0" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
					</label>
					<div class="sm:col-span-2 border-t border-slate-800 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
						<label class="space-y-1"><span class="block font-bold text-slate-300">Giới tính (tính lương hưu)</span>
							<select id="sal-gender" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
								<option value="nam">Nam</option>
								<option value="nu">Nữ</option>
							</select>
						</label>
						<label class="space-y-1"><span class="block font-bold text-slate-300">Số năm đóng BHXH</span>
							<input id="sal-years" type="number" step="1" min="0" max="45" value="20" class="w-full px-3 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white">
						</label>
					</div>
				</div>

				<div class="lg:col-span-5 bg-gradient-to-br from-navy-950 to-navy-900 p-6 rounded-3xl border border-emerald-500/30 space-y-3 text-sm" aria-live="polite">
					<dl class="space-y-2" data-sal-out>
						<div class="flex justify-between gap-3"><dt class="text-slate-400">Lương theo hệ số</dt><dd class="font-bold text-white" data-out="salary">—</dd></div>
						<div class="flex justify-between gap-3"><dt class="text-slate-400">Các khoản phụ cấp</dt><dd class="font-bold text-white" data-out="allowances">—</dd></div>
						<div class="flex justify-between gap-3"><dt class="text-slate-400">Tổng thu nhập</dt><dd class="font-bold text-white" data-out="gross">—</dd></div>
						<div class="flex justify-between gap-3"><dt class="text-slate-400">BHXH, BHYT, BHTN phải đóng</dt><dd class="font-bold text-rose-300" data-out="insurance">—</dd></div>
						<div class="flex justify-between gap-3 border-t border-slate-800 pt-2"><dt class="text-emerald-300 font-bold">Thực nhận (trước thuế TNCN)</dt><dd class="font-black text-emerald-300 text-lg" data-out="net">—</dd></div>
						<div class="flex justify-between gap-3 border-t border-slate-800 pt-2"><dt class="text-amber-300 font-bold">Lương hưu ước tính</dt><dd class="font-black text-amber-300" data-out="pension">—</dd></div>
					</dl>
					<p class="text-[11px] text-slate-500" data-out="pension-note"></p>
					<p class="text-[11px] text-slate-500">Ước tính tham khảo: lương hưu giả định bình quân tiền lương đóng BHXH bằng mức hiện tại và nghỉ hưu đúng tuổi; không trừ thuế thu nhập cá nhân. Nguồn:
						<?php foreach ( $params['sources'] as $i => $src ) : ?>
							<a class="underline hover:text-slate-300" href="<?php echo esc_url( $src['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $src['label'] ); ?></a><?php echo $i < count( $params['sources'] ) - 1 ? '; ' : '.'; ?>
						<?php endforeach; ?>
					</p>
				</div>
			</form>
		</div>
	</section>
	<?php
}

/**
 * 14.7 Legal Matrix - văn bản pháp luật mới + mẫu hồ sơ có thật trong kho
 * tài liệu, xem nhanh qua modal.
 *
 * @param array<int, array<string, mixed>> $legal
 * @param array<int, array<string, mixed>> $forms
 */
function cvc_render_legal_matrix( array $legal, array $forms ): void {
	$legal = array_values( array_filter( $legal, 'is_array' ) );
	$forms = array_values( array_filter( $forms, 'is_array' ) );
	?>
	<section id="legal-matrix" class="py-20 bg-slate-900 border-t border-slate-800">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
				<div class="space-y-2">
					<span class="inline-block text-xs font-extrabold uppercase tracking-widest text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/30">Pháp lý &amp; hồ sơ</span>
					<h2 class="text-2xl sm:text-4xl font-extrabold text-white">Văn bản mới &amp; mẫu hồ sơ dự tuyển</h2>
				</div>
				<div class="flex gap-4 text-xs font-extrabold">
					<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="text-amber-400 hover:text-amber-300">Thư viện pháp luật &rarr;</a>
					<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="text-cyan-300 hover:text-cyan-200">Kho tài liệu &rarr;</a>
				</div>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
				<div class="space-y-3">
					<h3 class="text-sm font-black text-white">Văn bản pháp luật mới cập nhật</h3>
					<?php if ( empty( $legal ) ) : ?>
						<p class="text-sm text-slate-400">Chưa có văn bản.</p>
					<?php endif; ?>
					<?php foreach ( $legal as $doc ) : ?>
						<button type="button" class="w-full text-left p-4 bg-navy-950 rounded-2xl border border-slate-800 hover:border-amber-400/60 space-y-1"
							data-cvc-legal-modal
							data-title="<?php echo esc_attr( (string) ( $doc['title'] ?? '' ) ); ?>"
							data-meta="<?php echo esc_attr( implode( ' · ', array_filter( array( $doc['document_number'] ?? null, $doc['document_type'] ?? null, ! empty( $doc['issued_date'] ) ? 'Ban hành ' . cvc_format_date_vn( $doc['issued_date'] ) : null, ! empty( $doc['effective_date'] ) ? 'Hiệu lực ' . cvc_format_date_vn( $doc['effective_date'] ) : null ) ) ) ); ?>"
							data-url="<?php echo esc_url( cvc_legal_document_url( (string) ( $doc['slug'] ?? '' ) ) ); ?>">
							<span class="text-[10px] font-bold text-amber-300 uppercase"><?php echo esc_html( (string) ( $doc['document_number'] ?? ( $doc['document_type'] ?? 'Văn bản' ) ) ); ?></span>
							<span class="block text-sm font-bold text-white leading-snug"><?php echo esc_html( (string) ( $doc['title'] ?? '' ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
				<div class="space-y-3">
					<h3 class="text-sm font-black text-white">Mẫu hồ sơ &amp; biểu mẫu</h3>
					<?php if ( empty( $forms ) ) : ?>
						<p class="text-sm text-slate-400">Chưa có mẫu hồ sơ trong kho tài liệu.</p>
					<?php endif; ?>
					<?php foreach ( $forms as $form ) : ?>
						<div class="p-4 bg-navy-950 rounded-2xl border border-slate-800 flex items-center justify-between gap-3">
							<div class="min-w-0">
								<span class="block text-sm font-bold text-white leading-snug"><?php echo esc_html( (string) ( $form['title'] ?? '' ) ); ?></span>
								<span class="text-[11px] <?php echo ! empty( $form['is_free'] ) ? 'text-emerald-300' : 'text-amber-300'; ?>"><?php echo ! empty( $form['is_free'] ) ? 'Miễn phí' : esc_html( number_format( (float) ( $form['effective_price'] ?? $form['price'] ?? 0 ), 0, ',', '.' ) . 'đ' ); ?></span>
							</div>
							<a href="<?php echo esc_url( cvc_document_url( (string) ( $form['slug'] ?? '' ) ) ); ?>" class="shrink-0 px-3 py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black text-xs rounded-xl">Xem &amp; tải</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<dialog id="cvc-legal-dialog" class="cvc-legal-dialog" aria-labelledby="cvc-legal-dialog-title">
			<form method="dialog" class="space-y-3">
				<p class="text-[11px] font-bold text-amber-300" data-dialog-meta></p>
				<h3 id="cvc-legal-dialog-title" class="text-lg font-black text-white" data-dialog-title></h3>
				<div class="flex flex-wrap gap-2 pt-2">
					<a data-dialog-url href="#" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl">Đọc toàn văn</a>
					<button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl">Đóng</button>
				</div>
			</form>
		</dialog>
	</section>
	<?php
}
