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
 * 14.1 Live Pulse - dong tom tat tinh hinh (giao dien van ban 16/10/2026):
 * khong con bang chay; liet ke han nop sap toi + van ban moi dang muc luc.
 *
 * @param array<string, mixed>|null $pulse
 */
function cvc_render_live_pulse( ?array $pulse ): void {
	if ( empty( $pulse ) ) {
		return;
	}
	$live      = is_array( $pulse['live'] ?? null ) ? $pulse['live'] : array();
	$deadlines = array_values( array_filter( (array) ( $pulse['upcoming_deadlines'] ?? array() ), 'is_array' ) );
	?>
	<section id="live-pulse" class="dz-box dz-pulse" aria-labelledby="live-pulse-title">
		<h2 id="live-pulse-title" class="dz-box-head"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Hạn nộp hồ sơ sắp tới</h2>
		<div class="dz-box-body">
			<?php if ( (int) ( $live['open_recruitments'] ?? 0 ) > 0 ) : ?>
				<p class="dz-pulse-sum">
					<strong><?php echo esc_html( number_format( (int) $live['open_recruitments'], 0, ',', '.' ) ); ?></strong> đợt đang nhận hồ sơ<?php if ( (int) ( $live['open_positions_quota'] ?? 0 ) > 0 ) : ?> · <strong><?php echo esc_html( number_format( (int) $live['open_positions_quota'], 0, ',', '.' ) ); ?></strong> chỉ tiêu<?php endif; ?><?php if ( ! empty( $live['closing_within_7_days'] ) ) : ?> · <span class="dz-warn"><?php echo (int) $live['closing_within_7_days']; ?> đợt hết hạn trong 7 ngày</span><?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( empty( $deadlines ) ) : ?>
				<p class="dz-muted" style="margin:0">Chưa có đợt nào sắp hết hạn.</p>
			<?php else : ?>
				<ul class="dz-list dz-list-sm">
					<?php foreach ( array_slice( $deadlines, 0, 6 ) as $d ) : $days = (int) ( $d['days_left'] ?? 0 ); ?>
						<li>
							<a class="dz-sidelink" href="<?php echo esc_url( cvc_recruitment_url( (string) ( $d['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( wp_trim_words( (string) ( $d['title'] ?? '' ), 22 ) ); ?></a>
							<div class="dz-item-meta"><span class="dz-tag <?php echo $days <= 3 ? 'is-warn' : ''; ?>"><?php echo esc_html( 0 === $days ? 'Hết hạn hôm nay' : 'Còn ' . $days . ' ngày' ); ?></span></div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * 14.3 Cong cu nhanh - lien ket toi cac muc co that tren trang chu.
 */
function cvc_render_hero_tools(): void {
	$tools = array(
		array( 'label' => 'Hạn nộp hồ sơ sắp tới', 'url' => '#live-pulse' ),
		array( 'label' => 'Mẫu hồ sơ dự tuyển', 'url' => '#legal-matrix' ),
		array( 'label' => 'Tính lương & lương hưu', 'url' => '#tinh-luong' ),
		array( 'label' => 'Lộ trình thăng tiến', 'url' => '#lo-trinh-su-nghiep' ),
	);
	?>
	<nav class="dz-quicklinks" aria-label="Công cụ nhanh">
		<?php foreach ( $tools as $tool ) : ?>
			<a href="<?php echo esc_url( $tool['url'] ); ?>"><?php echo esc_html( $tool['label'] ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * 14.4 Lo trinh nghe nghiep - bac nghe nghiep (career_ranks) + de/khoa khop ten.
 *
 * @param array<int, array<string, mixed>> $ranks
 */
function cvc_render_career_roadmap( array $ranks ): void {
	$ranks = array_values( array_filter( $ranks, fn ( $r ) => is_array( $r ) && ! empty( $r['code'] ) && ! empty( $r['name'] ) ) );
	if ( empty( $ranks ) ) {
		return;
	}
	$goal_url = cvc_is_logged_in() ? cvc_account_url( 'goals' ) : cvc_login_url();
	?>
	<section id="lo-trinh-su-nghiep" class="dz-section" aria-labelledby="lo-trinh-title">
		<div class="dz-h2"><h2 id="lo-trinh-title">Lộ trình theo bậc nghề nghiệp</h2><a href="<?php echo esc_url( $goal_url ); ?>">Đặt mục tiêu ôn tập →</a></div>
		<p class="dz-muted" style="margin:-4px 0 10px;font-style:italic">Chọn bậc mục tiêu để xem đề thi và khóa học hiện có phù hợp. Nội dung ghép theo tên đề/khóa học, không phải danh mục ngạch bậc pháp lý.</p>
		<div class="cvc-roadmap" data-cvc-roadmap>
			<div class="dz-tabs" role="tablist" aria-label="Bậc nghề nghiệp">
				<?php foreach ( $ranks as $i => $rank ) : ?>
					<button type="button" role="tab" id="roadmap-tab-<?php echo esc_attr( $rank['code'] ); ?>" aria-controls="roadmap-panel-<?php echo esc_attr( $rank['code'] ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>" class="cvc-roadmap__tab dz-tab">
						<small>Bậc <?php echo (int) ( $rank['level'] ?? $i + 1 ); ?></small> <?php echo esc_html( (string) $rank['name'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
			<?php foreach ( $ranks as $i => $rank ) : ?>
				<div role="tabpanel" id="roadmap-panel-<?php echo esc_attr( $rank['code'] ); ?>" aria-labelledby="roadmap-tab-<?php echo esc_attr( $rank['code'] ); ?>" class="dz-tabpanel" <?php echo 0 === $i ? '' : 'hidden'; ?>>
					<?php if ( empty( $rank['exams'] ) && empty( $rank['courses'] ) ) : ?>
						<p class="dz-empty">Chưa có đề thi hay khóa học dành riêng cho bậc này. Bạn có thể đặt mục tiêu để nhận gợi ý khi có nội dung mới.</p>
					<?php else : ?>
						<ul class="dz-list">
							<?php foreach ( (array) ( $rank['courses'] ?? array() ) as $course ) : ?>
								<li><h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_course_url( (string) $course['slug'] ) ); ?>"><?php echo esc_html( (string) $course['title'] ); ?></a></h3><div class="dz-item-meta"><span class="dz-tag">Khóa học</span></div></li>
							<?php endforeach; ?>
							<?php foreach ( (array) ( $rank['exams'] ?? array() ) as $exam ) : ?>
								<li><h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_exam_url( (string) $exam['slug'] ) ); ?>"><?php echo esc_html( (string) $exam['title'] ); ?></a></h3><div class="dz-item-meta"><span class="dz-tag">Đề thi</span><span><?php echo (int) ( $exam['total_questions'] ?? 0 ); ?> câu</span><span><?php echo (int) ( $exam['duration_minutes'] ?? 0 ); ?> phút</span></div></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * 14.5 Thi thu nhanh - mini quiz (cau hoi that, cham o server) + de thi moi.
 *
 * @param array<int, array<string, mixed>> $exams
 */
function cvc_render_smart_exam_section( array $exams ): void {
	$exams = array_values( array_filter( $exams, 'is_array' ) );
	?>
	<section id="thi-thu-ai" class="dz-section" aria-labelledby="thi-thu-title">
		<div class="dz-h2"><h2 id="thi-thu-title">Làm thử 5 câu trắc nghiệm</h2><a href="<?php echo esc_url( cvc_exams_url() ); ?>">Tất cả đề thi →</a></div>
		<div class="dz-quiz" data-cvc-mini-quiz>
			<div class="dz-quiz-top"><strong data-quiz-progress>Đang tải câu hỏi…</strong><span class="dz-muted" data-quiz-score></span></div>
			<div data-quiz-body class="dz-quiz-body" aria-live="polite">
				<noscript><p class="dz-muted">Cần bật JavaScript để làm thử. Bạn có thể vào <a class="dz-link" href="<?php echo esc_url( cvc_exams_url() ); ?>">trang đề thi</a>.</p></noscript>
			</div>
			<div class="dz-quiz-actions">
				<button type="button" data-quiz-next class="hidden dz-btn dz-btn-primary">Câu tiếp theo →</button>
				<button type="button" data-quiz-reload class="hidden dz-btn">Làm bộ khác</button>
			</div>
		</div>
		<?php if ( ! empty( $exams ) ) : ?>
			<h3 class="dz-subhead">Đề thi mới trên hệ thống</h3>
			<ul class="dz-list">
				<?php foreach ( $exams as $exam ) : ?>
					<li>
						<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_exam_url( (string) ( $exam['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $exam['title'] ?? '' ) ); ?></a></h3>
						<div class="dz-item-meta">
							<?php if ( ! empty( $exam['total_questions'] ) ) : ?><span><?php echo (int) $exam['total_questions']; ?> câu</span><?php endif; ?>
							<?php if ( ! empty( $exam['duration_minutes'] ) ) : ?><span><?php echo (int) $exam['duration_minutes']; ?> phút</span><?php endif; ?>
							<?php if ( isset( $exam['passing_score'], $exam['total_score'] ) && (float) $exam['total_score'] > 0 ) : ?><span>Đạt từ <?php echo esc_html( rtrim( rtrim( (string) $exam['passing_score'], '0' ), '.' ) . '/' . rtrim( rtrim( (string) $exam['total_score'], '0' ), '.' ) ); ?> điểm</span><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * 14.6 Uoc tinh luong & luong huu - chay o trinh duyet, tham so tu cvc_salary_params().
 */
function cvc_render_salary_calculator(): void {
	$params = cvc_salary_params();
	$fields = array(
		array( 'sal-coef', 'Hệ số lương', '0.01', '1', '10', '2.34' ),
		array( 'sal-position', 'Hệ số phụ cấp chức vụ', '0.05', '0', '2', '0' ),
		array( 'sal-tnvk', 'Thâm niên vượt khung (%)', '1', '0', '50', '0' ),
		array( 'sal-tnn', 'Phụ cấp thâm niên nghề (%)', '1', '0', '50', '0' ),
		array( 'sal-region', 'Hệ số phụ cấp khu vực', '0.1', '0', '1', '0' ),
		array( 'sal-resp', 'Hệ số phụ cấp trách nhiệm', '0.1', '0', '1', '0' ),
	);
	?>
	<section id="tinh-luong" class="dz-section" aria-labelledby="tinh-luong-title">
		<div class="dz-h2"><h2 id="tinh-luong-title">Ước tính lương &amp; lương hưu</h2></div>
		<p class="dz-muted" style="margin:-4px 0 10px;font-style:italic"><?php echo esc_html( $params['base_salary_note'] ); ?>.</p>
		<form class="dz-salary" data-cvc-salary onsubmit="return false;">
			<div class="dz-salary-in">
				<div class="dz-field"><label for="sal-type">Đối tượng</label>
					<select id="sal-type" class="dz-select"><option value="cong_chuc">Công chức (không đóng BHTN)</option><option value="vien_chuc">Viên chức (có đóng BHTN)</option></select></div>
				<?php foreach ( $fields as $f ) : ?>
					<div class="dz-field"><label for="<?php echo esc_attr( $f[0] ); ?>"><?php echo esc_html( $f[1] ); ?></label>
						<input id="<?php echo esc_attr( $f[0] ); ?>" class="dz-input" type="number" step="<?php echo esc_attr( $f[2] ); ?>" min="<?php echo esc_attr( $f[3] ); ?>" max="<?php echo esc_attr( $f[4] ); ?>" value="<?php echo esc_attr( $f[5] ); ?>"></div>
				<?php endforeach; ?>
				<div class="dz-field"><label for="sal-gender">Giới tính (tính lương hưu)</label>
					<select id="sal-gender" class="dz-select"><option value="nam">Nam</option><option value="nu">Nữ</option></select></div>
				<div class="dz-field"><label for="sal-years">Số năm đóng BHXH</label>
					<input id="sal-years" class="dz-input" type="number" step="1" min="0" max="45" value="20"></div>
			</div>
			<div class="dz-salary-out" aria-live="polite">
				<table class="dz-kv" data-sal-out>
					<tr><td>Lương theo hệ số</td><td class="num" data-out="salary">—</td></tr>
					<tr><td>Các khoản phụ cấp</td><td class="num" data-out="allowances">—</td></tr>
					<tr><td>Tổng thu nhập</td><td class="num" data-out="gross">—</td></tr>
					<tr><td>BHXH, BHYT, BHTN phải đóng</td><td class="num" data-out="insurance">—</td></tr>
					<tr class="is-strong"><td>Thực nhận (trước thuế TNCN)</td><td class="num" data-out="net">—</td></tr>
					<tr class="is-strong"><td>Lương hưu ước tính</td><td class="num" data-out="pension">—</td></tr>
				</table>
				<p class="dz-note" data-out="pension-note"></p>
				<p class="dz-note">Ước tính tham khảo: lương hưu giả định bình quân tiền lương đóng BHXH bằng mức hiện tại và nghỉ hưu đúng tuổi; không trừ thuế thu nhập cá nhân. Nguồn:
					<?php foreach ( $params['sources'] as $i => $src ) : ?>
						<a class="dz-link" href="<?php echo esc_url( $src['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $src['label'] ); ?></a><?php echo $i < count( $params['sources'] ) - 1 ? '; ' : '.'; ?>
					<?php endforeach; ?>
				</p>
			</div>
		</form>
	</section>
	<?php
}

/**
 * 14.7 Van ban moi + mau ho so co that trong kho tai lieu.
 *
 * @param array<int, array<string, mixed>> $legal
 * @param array<int, array<string, mixed>> $forms
 */
function cvc_render_legal_matrix( array $legal, array $forms ): void {
	$legal = array_values( array_filter( $legal, 'is_array' ) );
	$forms = array_values( array_filter( $forms, 'is_array' ) );
	?>
	<section id="legal-matrix" class="dz-section" aria-labelledby="legal-matrix-title">
		<div class="dz-h2"><h2 id="legal-matrix-title">Văn bản pháp luật mới</h2><a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Thư viện văn bản →</a></div>
		<?php if ( empty( $legal ) ) : ?>
			<p class="dz-empty">Chưa có văn bản.</p>
		<?php else : ?>
			<div class="dz-tablewrap">
				<table class="dz-table">
					<thead><tr><th>Số, ký hiệu</th><th>Trích yếu</th><th>Ban hành</th></tr></thead>
					<tbody>
					<?php foreach ( $legal as $doc ) : ?>
						<tr>
							<td style="white-space:nowrap"><?php echo esc_html( (string) ( $doc['document_number'] ?? ( $doc['document_type'] ?? '' ) ) ); ?></td>
							<td><a class="dz-rowlink" href="<?php echo esc_url( cvc_legal_document_url( (string) ( $doc['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $doc['title'] ?? '' ) ); ?></a></td>
							<td style="white-space:nowrap"><?php echo ! empty( $doc['issued_date'] ) ? esc_html( cvc_format_date_vn( $doc['issued_date'] ) ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $forms ) ) : ?>
			<div class="dz-h2" style="margin-top:22px"><h2>Mẫu hồ sơ dự tuyển</h2><a href="<?php echo esc_url( cvc_documents_url() ); ?>">Kho tài liệu →</a></div>
			<ul class="dz-list">
				<?php foreach ( $forms as $form ) : ?>
					<li class="dz-row">
						<div><h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_document_url( (string) ( $form['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $form['title'] ?? '' ) ); ?></a></h3>
						<div class="dz-item-meta"><span class="dz-tag <?php echo ! empty( $form['is_free'] ) ? 'is-ok' : ''; ?>"><?php echo ! empty( $form['is_free'] ) ? 'Miễn phí' : esc_html( number_format( (float) ( $form['effective_price'] ?? $form['price'] ?? 0 ), 0, ',', '.' ) . 'đ' ); ?></span></div></div>
						<a class="dz-btn dz-btn-sm" href="<?php echo esc_url( cvc_document_url( (string) ( $form['slug'] ?? '' ) ) ); ?>">Xem &amp; tải</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
}
