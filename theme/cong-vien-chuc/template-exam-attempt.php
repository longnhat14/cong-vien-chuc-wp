<?php
/**
 * CÔNG VIÊN CHỨC — EXAM OS X (Smart Exam Intelligence Platform)
 * Executive 3-Column Shell Architecture & High-Conversion Exam Engine
 * URL: /thi-trac-nghiem/{slug}/lam-bai/ hoặc /lam-bai/{id}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Rewrite /lam-bai/{id}/ đặt query var `cvc_attempt_id` (inc/routes.php) -
// trước 2026-10-03 template đọc nhầm `attempt_id` nên MỌI lượt thi thật mở
// qua URL này đều rơi về bộ câu hỏi demo.
$attempt_id = absint( get_query_var( 'cvc_attempt_id' ) ) ?: ( absint( get_query_var( 'attempt_id' ) ) ?: ( isset( $_GET['attempt_id'] ) ? absint( $_GET['attempt_id'] ) : 0 ) );
$slug       = sanitize_text_field( (string) get_query_var( 'cvc_exam_slug' ) );
$token      = cvc_auth_token();

$s_lower    = strtolower( $slug );
$is_english = strpos( $s_lower, 'tieng-anh' ) !== false || strpos( $s_lower, 'ngoai-ngu' ) !== false || strpos( $s_lower, 'english' ) !== false;
$is_it      = strpos( $s_lower, 'tin-hoc' ) !== false || strpos( $s_lower, 'cntt' ) !== false;

if ( $is_english ) {
	$questions = CVC_Question_Bank_Fixtures::get_english_questions();
} elseif ( $is_it ) {
	$questions = CVC_Question_Bank_Fixtures::get_it_questions();
} else {
	$questions = CVC_Question_Bank_Fixtures::get_official_questions();
}

/*
 * QUAN TRỌNG: response thật của GET /api/exam-attempts/{id} KHÔNG có
 * khóa "questions" - câu hỏi nằm lồng trong data.answers[].question
 * (kèm data.answers[].question.options). Trước đây code đọc nhầm
 * $attempt_data['questions'] (luôn rỗng với response thật) nên MỌI
 * lượt thi thật đều âm thầm rơi về bộ câu hỏi demo/fixture bên dưới,
 * kể cả khi attempt_id hợp lệ - sửa lại đọc đúng field + build thêm
 * $option_ids_map (option_key => option_id thật) để JS lưu đáp án
 * bằng question_option_id thay vì 1 ký tự A/B/C/D vô nghĩa với backend.
 */
$is_demo_mode    = true;
$option_ids_map  = array();
$existing_state  = array();
$real_attempt_id = 0;
$attempt_status  = '';

if ( $attempt_id > 0 && $token ) {
	$attempt_res = ( new CVC_Exam_Attempt_Service() )->show( $attempt_id, $token );

	if ( $attempt_res['ok'] && ! empty( $attempt_res['data']['data']['answers'] ) ) {
		$attempt_data    = $attempt_res['data']['data'];
		$real_attempt_id = (int) ( $attempt_data['id'] ?? $attempt_id );
		$attempt_status  = (string) ( $attempt_data['status'] ?? '' );
		$questions       = array();

		foreach ( $attempt_data['answers'] as $answer ) {
			$question = $answer['question'] ?? null;

			if ( ! $question ) {
				continue;
			}

			$opts        = array();
			$option_keys = array();
			$selected_key = null;

			foreach ( ( $question['options'] ?? array() ) as $i => $opt ) {
				$key               = chr( 65 + $i );
				$opts[ $key ]      = $opt['option_text'] ?? '';
				$option_keys[ $key ] = $opt['id'];

				if ( ! empty( $answer['question_option_id'] ) && (int) $answer['question_option_id'] === (int) $opt['id'] ) {
					$selected_key = $key;
				}
			}

			$questions[] = array(
				'id'            => $question['id'],
				'question_text' => $question['question_text'] ?? '',
				'options'       => $opts,
				'difficulty'    => $question['difficulty'] ?? null,
				// Lượt thi thật: KHÔNG có giải thích/đáp án trong HTML - chỉ
				// tải qua AJAX sau khi nộp bài (cvcLoadExplanation()).
				'explanation'   => '',
			);

			$option_ids_map[ $question['id'] ] = $option_keys;
			$existing_state[ $question['id'] ] = array(
				'selected'         => $selected_key,
				'is_flagged'       => (bool) ( $answer['is_flagged'] ?? false ),
				'confidence_level' => $answer['confidence_level'] ?? null,
			);
		}

		if ( ! empty( $questions ) ) {
			$is_demo_mode = false;
		}
	}
}

$total_q = count( $questions );

cvc_seo_set_title( 'EXAM OS X - Hệ Thống Thi Trắc Nghiệm AI Công Viên Chức' );
cvc_seo_set_description( 'Hệ thống Smart Exam Intelligence Platform luyện thi Kiến thức chung công chức chuẩn Bộ Nội Vụ 2026.' );

get_header();
?>

<!-- Pass Backend AJAX & Nonce Variables to Client JS -->
<script>
window.cvc_vars = {
	ajax_url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
	nonce: '<?php echo esc_js( wp_create_nonce( 'cvc_exam_attempt' ) ); ?>',
	attempt_id: <?php echo (int) $real_attempt_id; ?>,
	home_url: '<?php echo esc_js( home_url( '/' ) ); ?>',
	is_demo: <?php echo $is_demo_mode ? 'true' : 'false'; ?>,
	option_ids: <?php echo wp_json_encode( $option_ids_map ); ?>,
	existing_state: <?php echo wp_json_encode( $existing_state ); ?>
};
</script>

<?php if ( $is_demo_mode ) : ?>
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-4">
	<div class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/40 text-amber-300 text-xs font-bold rounded-xl px-4 py-3">
		<span>⚠ CHẾ ĐỘ DEMO</span>
		<span class="font-normal text-amber-200/90">Đây là bộ câu hỏi minh họa, không lưu kết quả thật. Hãy vào từ trang đề thi thật (/thi-trac-nghiem/) để làm bài và nhận điểm chính xác.</span>
	</div>
</div>
<?php endif; ?>

<!-- Load EXAM OS X Design System -->
<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css/exam-os.css' ); ?>">

<div class="cvc-exam-os-workspace text-slate-100">

	<!-- 1. TOP STICKY HEADER & COMMAND BAR (TREO NGAY DƯỚI MENU CHÍNH) -->
	<div class="exam-top-sticky shadow-xl border-b border-cyan-500/30">
		
		<!-- Main Exam Command Bar & Profile Navbar -->
		<div class="max-w-[1440px] mx-auto px-4 py-2.5 flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
			
			<!-- Left: Brand Logo, Search Bar & Title -->
			<div class="flex items-center gap-3 overflow-hidden">
				<a href="<?php echo esc_url( home_url('/') ); ?>" class="flex items-center gap-2 shrink-0 group pr-3 border-r border-slate-800">
					<div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-600 text-navy-950 flex items-center justify-center font-black text-sm shadow">
						♜
					</div>
					<span class="font-black text-sm text-white tracking-normal hidden lg:inline">CÔNG VIÊN CHỨC</span>
				</a>
				
				<div class="relative hidden sm:block">
					<input type="text" placeholder="🔍 Tìm kiếm câu hỏi, luật..." class="bg-[#0A192F] border border-[#1D3557] rounded-xl px-3 py-1 text-[11px] text-slate-200 focus:outline-none focus:border-cyan-400 w-44">
				</div>

				<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2 py-0.5 rounded border border-amber-400 shrink-0 uppercase">
					KTC-2026-01
				</span>
				<span class="font-extrabold text-sm text-white truncate max-w-[180px] sm:max-w-none">
					Đề Thi Sát Hạch Kiến Thức Chung Vòng 1
				</span>
			</div>

			<!-- Center: 4 Exam Modes Selector -->
			<div class="flex items-center bg-[#0A192F] p-1 rounded-xl border border-[#1D3557] text-[11px] font-bold shrink-0">
				<button type="button" onclick="ExamOS.setExamMode('learn')" data-mode="learn" class="exam-mode-badge">🎓 Học</button>
				<button type="button" onclick="ExamOS.setExamMode('practice')" data-mode="practice" class="exam-mode-badge active">🏋️ Luyện</button>
				<button type="button" onclick="ExamOS.setExamMode('real')" data-mode="real" class="exam-mode-badge">⏱️ Thi thật</button>
				<button type="button" onclick="ExamOS.setExamMode('mock')" data-mode="mock" class="exam-mode-badge">🏛️ Mô phỏng</button>
			</div>

			<!-- Right: Headtools, Timer, Profile Avatar & Submit Button -->
			<div class="flex items-center gap-3 shrink-0">
				<!-- Headtools -->
				<div class="hidden xl:flex items-center gap-1.5">
					<button type="button" onclick="ExamOS.toggleFontSize()" title="A+ Cỡ chữ" class="px-2 py-1 bg-[#0A192F] border border-[#1D3557] hover:border-cyan-400 text-slate-300 rounded-lg text-[11px] font-bold cursor-pointer">A+</button>
					<button type="button" onclick="ExamOS.toggleFocusMode()" title="Focus Mode" class="px-2 py-1 bg-[#0A192F] border border-[#1D3557] hover:border-cyan-400 text-slate-300 rounded-lg text-[11px] font-bold cursor-pointer">⤢</button>
				</div>

				<!-- Clock & Pause Button -->
				<div class="bg-[#0A192F] border border-amber-500/40 px-3 py-1 rounded-xl flex items-center gap-2 shadow">
					<span id="quiz-timer" class="text-sm font-black text-amber-400 font-mono">⏱ 60:00</span>
					<button type="button" title="Tạm dừng" class="text-slate-400 hover:text-white text-xs">⏸️</button>
				</div>

				<!-- Profile & VIP Badge -->
				<div class="hidden sm:flex items-center gap-2 pl-2 border-l border-slate-800">
					<div class="w-7 h-7 rounded-full bg-cyan-500 text-navy-950 font-black flex items-center justify-center text-xs">
						CB
					</div>
					<span class="bg-gradient-to-r from-amber-400 to-amber-500 text-navy-950 font-black text-[9px] px-1.5 py-0.5 rounded-full uppercase">👑 VIP PRO</span>
				</div>

				<button onclick="submitQuizSimulation()" class="px-4 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow transition-transform hover:scale-105 cursor-pointer">
					Nộp Bài Thi
				</button>
			</div>

		</div>
	</div>

	<!-- 2. 3-COLUMN SHELL WORKSPACE (205px LEFT | 1FR MAIN | 260px RIGHT) -->
	<div class="exam-shell-grid">

		<!-- ==================== LEFT COLUMN (205px) ==================== -->
		<aside class="exam-left-column space-y-4">

			<!-- Block 1: Donut Goal Ring (72% Tỷ lệ chính xác target) -->
			<div class="exam-card text-center space-y-3">
				<span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">MỤC TIÊU SÁT HẠCH</span>
				<div class="goal-ring-chart">
					<span class="goal-ring-text">72%</span>
				</div>
				<div>
					<span class="text-xs font-black text-white block">Tỷ Lệ Chính Xác Target</span>
					<span class="text-[10px] text-cyan-400 font-medium">Đạt chuẩn Vòng 1 Bộ Nội Vụ</span>
				</div>
			</div>

			<!-- Block 2: Left Navigation Menu -->
			<div class="exam-card space-y-1">
				<a class="left-menu-item active" title="Trang thi trắc nghiệm">
					<span>📝</span> Làm bài thi
				</a>
				<a href="<?php echo esc_url( home_url('/tai-khoan/?section=analytics') ); ?>" class="left-menu-item" title="Phân tích kết quả & lỗ hổng">
					<span>📊</span> Phân tích kết quả
				</a>
				<a href="<?php echo esc_url( home_url('/tai-khoan/?section=exam-history') ); ?>" class="left-menu-item" title="Lịch sử làm bài thi">
					<span>📜</span> Lịch sử làm bài
				</a>
				<a href="<?php echo esc_url( home_url('/tai-khoan/?section=my-courses') ); ?>" class="left-menu-item" title="Các khóa học đã đăng ký">
					<span>🎓</span> Khóa học của tôi
				</a>
				<a href="<?php echo esc_url( home_url('/tai-khoan/?section=my-documents') ); ?>" class="left-menu-item" title="Tài liệu & đề thi đã sở hữu">
					<span>📑</span> Tài liệu đã mua
				</a>
				<a class="left-menu-item" title="Trợ lý AI 24/7">
					<span>✦</span> AI Coach 24/7
				</a>
				<a href="<?php echo esc_url( home_url('/de-thi/') ); ?>" class="left-menu-item" title="Ngân hàng đề thi">
					<span>📚</span> Kho đề công chức
				</a>
				<a class="left-menu-item" title="Cài đặt hệ thống">
					<span>⚙️</span> Cài đặt
				</a>
			</div>

		</aside>

		<!-- ==================== CENTER MAIN COLUMN (1FR) ==================== -->
		<main class="space-y-6">

			<!-- Mode Description Banner & Progress Row -->
			<div class="exam-card space-y-3">
				<div class="flex items-center justify-between text-xs">
					<div id="exam-mode-description" class="text-cyan-300 font-semibold flex items-center gap-2">
						🏋️ Chế độ LUYỆN: Tự động lưu tiến độ, xem giải thích chi tiết sau mỗi câu.
					</div>
					<div class="flex items-center gap-2 font-mono text-xs">
						<span class="text-slate-400">Tiến độ:</span>
						<span id="answered-progress-text-main" class="text-amber-400 font-bold">0/<?php echo $total_q; ?> câu</span>
						<span id="command-progress-pct-main" class="text-cyan-400 font-bold">(0%)</span>
					</div>
				</div>

				<div class="w-full h-2 bg-[#112240] rounded-full overflow-hidden border border-[#1D3557]">
					<div id="command-progress-bar-main" class="h-full bg-gradient-to-r from-cyan-500 via-blue-500 to-amber-400 w-0 transition-all duration-300"></div>
				</div>

				<!-- Actionbar Filter Pills -->
				<div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-800/80 text-xs">
					<div class="flex items-center gap-1.5">
						<button type="button" onclick="ExamOS.filterNavigator('all')" data-filter="all" class="nav-filter-btn active">Tất cả (<?php echo $total_q; ?>)</button>
						<button type="button" onclick="ExamOS.filterNavigator('unanswered')" data-filter="unanswered" class="nav-filter-btn">Chưa làm (<?php echo $total_q; ?>)</button>
						<button type="button" onclick="ExamOS.filterNavigator('flagged')" data-filter="flagged" class="nav-filter-btn">Đánh dấu (0)</button>
					</div>
					<span id="autosave-status-text-main" class="text-emerald-400 font-mono text-[10px]">✓ Auto-saved ON</span>
				</div>
			</div>

			<!-- RESULTS BANNER (Hidden until submitted) -->
			<div id="quiz-results-banner" class="hidden bg-white text-navy-950 p-8 rounded-3xl shadow-2xl border-2 border-amber-400 space-y-6">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
					<div class="space-y-1">
						<span id="quiz-result-badge" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
							✓ ĐẠT KẾT QUẢ VÒNG 1
						</span>
						<h2 class="text-2xl font-extrabold text-navy-950">Báo Cáo Đánh Giá Năng Lực Sát Hạch</h2>
					</div>
					<div class="text-right">
						<span class="text-xs text-slate-400 uppercase font-bold block">Tổng Điểm KTC</span>
						<span id="quiz-final-score" class="text-4xl font-black text-amber-600">100/100</span>
					</div>
				</div>

				<div id="quiz-topic-stats-grid" class="grid grid-cols-1 md:grid-cols-4 gap-4">
					<!-- Điền động từ topic_stats thật sau khi nộp bài (xem submitExam() trong script) -->
				</div>

				<div id="quiz-recommendations-block" class="hidden p-6 rounded-2xl bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 text-white border border-amber-500/30 space-y-3">
					<span class="bg-amber-400 text-navy-950 text-[10px] font-black uppercase px-2.5 py-0.5 rounded">GỢI Ý ÔN TẬP THEO ĐIỂM YẾU</span>
					<div id="quiz-recommendations-list" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
				</div>
			</div>

			<!-- QUESTIONS LOOP -->
			<?php foreach ( $questions as $idx => $q ) : 
				$q_text = $q['question_text'] ?? ( $q['question'] ?? ( $q['content'] ?? 'Nội dung câu hỏi trắc nghiệm.' ) );
				$raw_opts = $q['options'] ?? array();
				$normalized_opts = array();
				if ( is_array( $raw_opts ) ) {
					foreach ( $raw_opts as $k => $v ) {
						if ( is_array( $v ) ) {
							$key = $v['option_key'] ?? ( is_string($k) ? $k : chr(65 + (int)$k) );
							$text = $v['option_text'] ?? ( $v['text'] ?? '' );
							$normalized_opts[$key] = $text;
						} else {
							$key = is_string($k) ? $k : chr(65 + (int)$k);
							$normalized_opts[$key] = (string)$v;
						}
					}
				}
			?>
			<article id="question-card-<?php echo $q['id']; ?>" class="exam-card space-y-6">
				
				<!-- Question Header Badges -->
				<div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
					<div class="flex items-center gap-2 flex-wrap">
						<span class="text-xs font-black text-navy-950 bg-amber-500 px-3 py-1 rounded-md uppercase">
							CÂU <?php echo $idx + 1; ?> / <?php echo $total_q; ?>
						</span>
						<?php
						$difficulty_labels = array( 'easy' => 'DỄ', 'medium' => 'TRUNG BÌNH', 'hard' => 'KHÓ' );
						$difficulty_key    = strtolower( (string) ( $q['difficulty'] ?? '' ) );
						?>
						<?php if ( isset( $difficulty_labels[ $difficulty_key ] ) ) : ?>
							<span class="text-[11px] font-bold text-cyan-400 bg-cyan-500/10 border border-cyan-500/30 px-3 py-1 rounded-md uppercase">
								ĐỘ KHÓ: <?php echo esc_html( $difficulty_labels[ $difficulty_key ] ); ?>
							</span>
						<?php endif; ?>
						<span class="hidden sm:inline text-[11px] font-bold text-purple-300 bg-purple-500/10 border border-purple-500/30 px-2.5 py-1 rounded-md">
							🎯 Đề thi chuẩn 2026
						</span>
					</div>
					<span class="text-xs text-slate-400 font-mono">● Autosave ON</span>
				</div>

				<!-- Question Content -->
				<div class="space-y-2">
					<h2 class="text-base sm:text-xl font-extrabold text-white leading-relaxed">
						<?php echo esc_html( $q_text ); ?>
					</h2>
					<p class="text-xs text-slate-400">
						Chọn một phương án đúng nhất dưới đây. Bạn có thể sử dụng tính năng loại trừ đáp án hoặc nhờ AI giải thích.
					</p>
				</div>

				<!-- Answer Tiles (Bàn thi số) -->
				<?php $selected_opt = $existing_state[ $q['id'] ]['selected'] ?? null; ?>
				<div id="options-container-<?php echo $q['id']; ?>" class="space-y-3">
					<?php foreach ( $normalized_opts as $opt_key => $opt_val ) : ?>
					<div id="tile-<?php echo $q['id']; ?>-<?php echo $opt_key; ?>" onclick="ExamOS.selectAnswerTile(<?php echo $q['id']; ?>, '<?php echo $opt_key; ?>')" class="answer-tile<?php echo ( $selected_opt === $opt_key ) ? ' selected' : ''; ?>">
						<input type="radio" name="q_<?php echo $q['id']; ?>" value="<?php echo $opt_key; ?>" class="hidden">
						<span class="answer-letter"><?php echo esc_html($opt_key); ?></span>
						<div class="flex-1 space-y-0.5 pt-0.5">
							<h3 class="answer-text text-sm font-bold text-white leading-snug">
								<?php echo esc_html( $opt_val ); ?>
							</h3>
							<p class="text-[11px] text-slate-400">Phương án <?php echo esc_html($opt_key); ?></p>
						</div>
						<div class="answer-check-mark">✓</div>
					</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $is_demo_mode ) : ?>
				<!-- TÀI LIỆU LIÊN QUAN & CĂN CỨ PHÁP LÝ (PER QUESTION DOCUMENTATION) -->
				<div class="p-3.5 rounded-xl bg-[#112240] border border-[#1D3557] space-y-2 text-xs">
					<div class="flex items-center justify-between border-b border-slate-700/60 pb-2">
						<span class="font-extrabold text-amber-400 flex items-center gap-1.5">
							📚 Tài liệu liên quan câu hỏi #<?php echo $q['id']; ?>:
						</span>
						<button type="button" onclick="showLawModal('<?php echo esc_js($q['explanation']); ?>')" class="text-cyan-400 hover:text-cyan-300 text-[11px] font-bold flex items-center gap-1 cursor-pointer">
							📖 Tra cứu điều khoản gốc &rarr;
						</button>
					</div>
					<div class="flex flex-wrap items-center gap-2 text-[11px]">
						<a href="<?php echo esc_url( home_url('/tai-lieu-phap-luat/') ); ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700 hover:border-cyan-400 text-slate-200 flex items-center gap-1.5 transition-colors">
							<span>📄</span> <strong>Luật Cán bộ, Công chức & Viên chức (2026.pdf)</strong>
							<span class="text-cyan-400 font-bold ml-1">Tải về ⬇</span>
						</a>
						<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700 hover:border-amber-400 text-slate-200 flex items-center gap-1.5 transition-colors">
							<span>🎓</span> <strong>Bài giảng video chuyên đề liên quan (18 phút)</strong>
							<span class="text-amber-400 font-bold ml-1">Xem ngay 🎥</span>
						</a>
						<a href="<?php echo esc_url( home_url('/tai-lieu-phap-luat/') ); ?>" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700 hover:border-emerald-400 text-slate-200 flex items-center gap-1.5 transition-colors">
							<span>⚖️</span> <strong>Nghị định 138/2020/NĐ-CP & NĐ 115/2020/NĐ-CP</strong>
							<span class="text-emerald-400 font-bold ml-1">Đọc online 📖</span>
						</a>
					</div>
				</div>
				<?php endif; ?>

				<!-- Action Bar & Confidence + Quick Note -->
				<div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800/80">
					<div class="flex items-center gap-2 flex-wrap">
						<button type="button" onclick="toggleFlagQuestion(<?php echo $q['id']; ?>)" class="px-3 py-1.5 bg-[#112240] hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold border border-[#1D3557] transition-colors flex items-center gap-1.5 cursor-pointer">
							<i class="fa-regular fa-star text-amber-400" id="flag-icon-<?php echo $q['id']; ?>"></i> Đánh dấu câu
						</button>
						<button type="button" onclick="ExamOS.toggleStrikeout(<?php echo $q['id']; ?>, 'A')" class="px-3 py-1.5 bg-[#112240] hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold border border-[#1D3557] transition-colors flex items-center gap-1.5 cursor-pointer">
							✕ Loại trừ A
						</button>
						<?php if ( $is_demo_mode ) : ?>
						<button type="button" onclick="showLawModal('<?php echo esc_js($q['explanation']); ?>')" class="px-3 py-1.5 bg-[#112240] hover:bg-slate-800 text-cyan-400 rounded-xl text-xs font-bold border border-[#1D3557] transition-colors cursor-pointer flex items-center gap-1.5">
							📖 Xem Giải Thích
						</button>
						<?php else : ?>
						<button type="button" onclick="cvcLoadExplanation(<?php echo (int) $q['id']; ?>)" class="px-3 py-1.5 bg-[#112240] hover:bg-slate-800 text-cyan-400 rounded-xl text-xs font-bold border border-[#1D3557] transition-colors cursor-pointer flex items-center gap-1.5">
							📖 Xem Giải Thích
						</button>
						<?php endif; ?>
					</div>

					<!-- Confidence Level Selector & Quick Note Input -->
					<div class="flex flex-wrap items-center gap-2">
						<div id="confidence-box-<?php echo $q['id']; ?>" class="hidden items-center gap-1.5 bg-[#112240] p-1 rounded-xl border border-[#1D3557]">
							<span class="text-[10px] text-slate-400 font-bold pl-1">Độ tự tin:</span>
							<button type="button" onclick="ExamOS.setConfidenceLevel(<?php echo $q['id']; ?>, 'low')" data-level="low" class="confidence-pill">Chưa chắc</button>
							<button type="button" onclick="ExamOS.setConfidenceLevel(<?php echo $q['id']; ?>, 'med')" data-level="med" class="confidence-pill">Khá chắc</button>
							<button type="button" onclick="ExamOS.setConfidenceLevel(<?php echo $q['id']; ?>, 'high')" data-level="high" class="confidence-pill">Rất chắc</button>
						</div>

						<div class="flex items-center gap-1">
							<input type="text" id="note-input-<?php echo $q['id']; ?>" placeholder="Viết ghi nhớ..." class="bg-[#112240] border border-[#1D3557] rounded-xl px-2.5 py-1 text-[11px] text-slate-200 focus:outline-none focus:border-cyan-400 w-28">
							<button type="button" onclick="ExamOS.saveQuestionNote(<?php echo $q['id']; ?>)" class="px-2.5 py-1 bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 font-bold text-[11px] rounded-xl border border-cyan-500/40 cursor-pointer">Lưu</button>
						</div>
					</div>
				</div>

				<?php if ( $is_demo_mode ) : ?>
				<!-- AI EXPLANATION BOX (WITH 3 TABS: GIẢI THÍCH AI | CĂN CỨ PHÁP LÝ | MẸO GHI NHỚ) -->
				<div id="explanation-<?php echo $q['id']; ?>" class="hidden ai-coach-banner space-y-3 text-xs">
					<div class="flex items-center justify-between border-b border-slate-800 pb-2">
						<div class="flex items-center gap-2 font-bold text-[11px]">
							<button type="button" class="px-2.5 py-1 bg-cyan-500 text-navy-950 rounded-lg">✦ Giải thích AI</button>
							<button type="button" onclick="showLawModal('<?php echo esc_js($q['explanation']); ?>')" class="px-2.5 py-1 bg-[#112240] text-slate-300 hover:text-white rounded-lg border border-[#1D3557]">📜 Căn cứ pháp lý</button>
							<button type="button" class="px-2.5 py-1 bg-[#112240] text-amber-300 rounded-lg border border-[#1D3557]">💡 Mẹo ghi nhớ</button>
						</div>
						<span class="text-cyan-300 font-bold text-xs">Đáp án chuẩn: B</span>
					</div>

					<p class="text-slate-300 leading-relaxed">
						<?php echo esc_html( $q['explanation'] ); ?>
					</p>

					<div class="pt-2 border-t border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-[11px]">
						<span class="text-amber-400 font-bold">⚖️ Căn cứ: Luật Viên chức 129/2025/QH15 & NĐ 259/2026/NĐ-CP</span>
						<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="text-cyan-400 font-extrabold hover:underline">
							📚 Xem Bài Học Liên Quan (18 Phút) &rarr;
						</a>
					</div>
				</div>
				<?php else : ?>
				<!-- Lượt thi thật: hộp giải thích rỗng, chỉ được lấp sau khi nộp bài
				     bằng dữ liệu thật (đáp án đúng từ DB + giải thích AI/có sẵn). -->
				<div id="explanation-<?php echo (int) $q['id']; ?>" class="hidden ai-coach-banner space-y-3 text-xs" data-cvc-explanation="<?php echo (int) $q['id']; ?>">
					<p class="text-slate-400">Đáp án và giải thích sẽ hiển thị sau khi bạn nộp bài.</p>
				</div>
				<?php endif; ?>

				<!-- Bottom Navigation Buttons -->
				<div class="flex items-center justify-between pt-4 border-t border-slate-800">
					<button type="button" onclick="scrollToQuestion(Math.max(1, <?php echo $idx; ?>))" class="px-5 py-2.5 bg-[#112240] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl border border-[#1D3557] shadow cursor-pointer">
						&larr; Câu trước
					</button>

					<button type="button" onclick="submitQuizSimulation()" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow cursor-pointer">
						Nộp bài thi (<?php echo $idx + 1; ?>/<?php echo $total_q; ?>)
					</button>

					<button type="button" onclick="scrollToQuestion(Math.min(<?php echo $total_q; ?>, <?php echo $idx + 2; ?>))" class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-navy-950 font-black text-xs rounded-xl shadow cursor-pointer">
						Câu tiếp theo &rarr;
					</button>
				</div>

			</article>
			<?php endforeach; ?>

			<!-- Bottom AI Coach Banner -->
			<div class="ai-coach-banner flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
				<div class="space-y-1">
					<span class="text-cyan-400 font-extrabold text-sm block">✦ AI COACH CHẨN ĐOÁN LỖ HỔNG</span>
					<p class="text-slate-300">
						Bạn đang làm tốt phần Luật Công Vụ. Cần chú ý thêm nhóm câu về <strong class="text-amber-400">Thẩm quyền xử lý kỷ luật</strong>.
					</p>
				</div>
				<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-5 py-2.5 bg-gradient-to-r from-cyan-400 to-blue-500 text-navy-950 font-black rounded-xl shadow shrink-0 hover:scale-105 transition-transform">
					Nhận lộ trình 7 ngày &rarr;
				</a>
			</div>

		</main>

		<!-- ==================== RIGHT SIDEBAR (260px STICKY) ==================== -->
		<aside class="sticky-right-sidebar space-y-4">

			<!-- PRIORITY BLOCK 1: 🎯 EXAM NAVIGATOR (BẢN ĐỒ CÂU HỎI) -->
			<div class="exam-card border-2 border-cyan-500/40 space-y-3">
				<div class="flex items-center justify-between border-b border-slate-800 pb-2">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider flex items-center gap-1.5">
						🎯 EXAM NAVIGATOR
					</h3>
					<span id="answered-progress-text" class="text-xs font-bold text-amber-400">0/<?php echo $total_q; ?></span>
				</div>

				<!-- Navigator Filter Tabs -->
				<div class="flex flex-wrap gap-1">
					<button type="button" onclick="ExamOS.filterNavigator('all')" data-filter="all" class="nav-filter-btn active">Tất cả</button>
					<button type="button" onclick="ExamOS.filterNavigator('unanswered')" data-filter="unanswered" class="nav-filter-btn">Chưa làm</button>
					<button type="button" onclick="ExamOS.filterNavigator('flagged')" data-filter="flagged" class="nav-filter-btn">Đánh dấu</button>
				</div>

				<!-- Grid Map Nodes (qmap) -->
				<div class="qmap-grid pt-1">
					<?php for ( $i = 1; $i <= $total_q; $i++ ) : ?>
					<button onclick="scrollToQuestion(<?php echo $i; ?>)" id="nav-btn-<?php echo $i; ?>" class="qmap-node">
						<?php echo $i; ?>
					</button>
					<?php endfor; ?>
				</div>

				<!-- Legend Status Dots -->
				<div class="flex items-center justify-between text-[10px] text-slate-400 pt-2 border-t border-slate-800">
					<span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-emerald-500"></span> Đã làm</span>
					<span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-cyan-400"></span> Đang làm</span>
					<span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-slate-700"></span> Bỏ qua</span>
				</div>

				<!-- Sidebar Action & Quick Tool Icons -->
				<div class="pt-2 space-y-2">
					<button onclick="submitQuizSimulation()" class="w-full py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow cursor-pointer text-center block">
						Nộp bài thi ngay (0/<?php echo $total_q; ?>)
					</button>

					<div class="flex items-center justify-center gap-2 text-slate-400 text-xs pt-1">
						<button type="button" title="Đánh dấu câu" class="p-1.5 bg-[#112240] rounded-lg border border-[#1D3557] hover:text-white">🔖</button>
						<button type="button" title="Focus Mode" class="p-1.5 bg-[#112240] rounded-lg border border-[#1D3557] hover:text-white">👁️</button>
						<button type="button" title="Cài đặt" class="p-1.5 bg-[#112240] rounded-lg border border-[#1D3557] hover:text-white">⚙️</button>
						<button type="button" title="Trợ giúp" class="p-1.5 bg-[#112240] rounded-lg border border-[#1D3557] hover:text-white">❓</button>
					</div>
				</div>
			</div>

			<!-- BLOCK 2: 📚 CỬA HÀNG HỌC TẬP (HIGH CONVERSION UPSELL STORE) -->
			<div class="exam-card border border-amber-500/40 space-y-3">
				<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
					📚 CỬA HÀNG HỌC TẬP
				</h3>
				<div class="space-y-2 text-xs">
					<div class="flex items-center justify-between p-2 bg-[#112240] rounded-xl border border-[#1D3557]">
						<div>
							<span class="text-slate-200 font-bold block">📘 Bộ 50 đề thi PDF</span>
							<span class="text-[10px] text-slate-400">Giải thích 100%</span>
						</div>
						<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-2.5 py-1 bg-amber-500 text-navy-950 font-black rounded-lg text-[11px] hover:bg-amber-400 shadow">
							49K
						</a>
					</div>
					<div class="flex items-center justify-between p-2 bg-[#112240] rounded-xl border border-[#1D3557]">
						<div>
							<span class="text-slate-200 font-bold block">⚖️ Sơ đồ tư duy Luật</span>
							<span class="text-[10px] text-slate-400">Tóm tắt bẫy thi</span>
						</div>
						<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-2.5 py-1 bg-amber-500 text-navy-950 font-black rounded-lg text-[11px] hover:bg-amber-400 shadow">
							79K
						</a>
					</div>
					<div class="flex items-center justify-between p-2 bg-[#112240] rounded-xl border border-[#1D3557]">
						<div>
							<span class="text-slate-200 font-bold block">🎯 Sổ tay bẫy trắc nghiệm</span>
							<span class="text-[10px] text-slate-400">Tập trung 100 câu bẫy</span>
						</div>
						<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-2.5 py-1 bg-amber-500 text-navy-950 font-black rounded-lg text-[11px] hover:bg-amber-400 shadow">
							99K
						</a>
					</div>
				</div>
			</div>

			<!-- BLOCK 3: 🎓 KHÓA HỌC PHÙ HỢP (HIGH CONVERSION CARD) -->
			<div class="course-upsell-card space-y-3 relative overflow-hidden">
				<div class="flex items-center justify-between">
					<span class="bg-amber-500 text-navy-950 text-[9px] font-black px-2 py-0.5 rounded uppercase">BÁN CHẠY NHẤT</span>
					<span class="text-[10px] text-emerald-400 font-bold">⚡ Giảm 30% hôm nay</span>
				</div>
				<div class="space-y-1">
					<h4 class="font-extrabold text-xs text-white">Khóa Ôn Thi Công Chức Vòng 1</h4>
					<p class="text-[11px] text-slate-300">120 bài giảng + AI Coach 1-on-1 sát hạch 2026</p>
					<div class="flex items-baseline gap-2 pt-1">
						<span class="text-base font-black text-amber-400">599.000đ</span>
						<span class="text-xs text-slate-400 line-through">850.000đ</span>
					</div>
				</div>
				<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="block w-full py-2.5 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs text-center rounded-xl shadow-lg transition-transform hover:scale-[1.02]">
					Đăng ký ngay &rarr;
				</a>
			</div>

			<!-- BLOCK 4: TIẾN ĐỘ (GỌN, KHÔNG TRÙNG VỚI NAVIGATOR) -->
			<div class="exam-card space-y-2 text-xs">
				<div class="flex items-center justify-between">
					<span class="font-bold text-slate-300 text-[11px] uppercase tracking-wide">📊 Tiến độ nhanh</span>
					<span class="text-xs font-black text-amber-400">🔥 Streak hôm nay</span>
				</div>
				<div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
					<div id="command-progress-bar-side" class="h-full bg-gradient-to-r from-amber-500 to-cyan-400 w-0 transition-all duration-300"></div>
				</div>
				<div class="flex items-center justify-between text-slate-400">
					<span>Đã trả lời: <span id="sidebar-answered-count" class="font-bold text-white">0 / <?php echo $total_q; ?></span></span>
					<span>Đánh dấu: <span id="sidebar-flagged-count" class="font-bold text-amber-400">0</span></span>
				</div>
				<!-- Social proof live -->
				<div class="pt-1 border-t border-slate-800 flex items-center justify-center gap-1.5 text-[10px] text-emerald-400 font-bold">
					<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
					1.234 học viên đang thi cùng bạn
				</div>
			</div>

		</aside>

	</div>

	<!-- 3. FOOTER STATS STRIP -->
	<footer class="border-t border-slate-800 bg-[#03101f] py-8 mt-12 text-slate-400 text-xs">
		<div class="max-w-[1440px] mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
			<div>
				<span class="text-xl font-black text-white block">12.450+</span>
				<span class="text-[11px]">Học viên ôn luyện thành công</span>
			</div>
			<div>
				<span class="text-xl font-black text-cyan-400 block">94.8%</span>
				<span class="text-[11px]">Tỷ lệ đỗ Vòng 1 Kiến thức chung</span>
			</div>
			<div>
				<span class="text-xl font-black text-amber-400 block">1.200+</span>
				<span class="text-[11px]">Đề thi trắc nghiệm chuẩn Bộ Nội Vụ</span>
			</div>
			<div>
				<span class="text-xl font-black text-emerald-400 block">24/7</span>
				<span class="text-[11px]">Hỗ trợ giải đáp pháp lý AI Coach</span>
			</div>
		</div>
	</footer>

</div>

<!-- RESUME EXAM MODAL -->
<div id="resume-exam-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden items-center justify-center p-4">
	<div class="bg-[#0A192F] border-2 border-amber-400 rounded-3xl max-w-md w-full p-6 text-center text-white space-y-4 shadow-2xl">
		<div class="w-16 h-16 rounded-full bg-amber-500/20 text-amber-400 text-2xl flex items-center justify-center mx-auto border border-amber-400/40">
			👋
		</div>
		<h3 class="text-xl font-black">Chào Mừng Cán Bộ Trở Lại!</h3>
		<p class="text-xs text-slate-300">Hệ thống phát hiện bạn đang có một bài thi chưa hoàn thành được lưu tự động trên thiết bị.</p>
		<div class="flex items-center justify-center gap-3 pt-2">
			<button onclick="ExamOS.dismissResumePrompt()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
				Làm Bài Mới
			</button>
			<button onclick="ExamOS.dismissResumePrompt()" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl shadow">
				TIẾP TỤC BÀI THI &rarr;
			</button>
		</div>
	</div>
</div>

<!-- LAW ARTICLE POPUP MODAL -->
<div id="lawArticleModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-5 shadow-2xl border border-slate-200 text-navy-950">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
      <h3 class="text-base font-extrabold flex items-center gap-2">
        Tra Cứu Căn Cứ Điều Khoản Luật Gốc
      </h3>
      <button onclick="closeLawModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold flex items-center justify-center">
        ✕
      </button>
    </div>
    <div id="lawArticleModalBody" class="text-sm text-slate-700 leading-relaxed max-h-[60vh] overflow-y-auto space-y-3 p-4 bg-slate-50 rounded-2xl border border-slate-200 font-sans">
      <!-- Content populated dynamically -->
    </div>
    <div class="flex justify-end pt-2">
      <button onclick="closeLawModal()" class="px-5 py-2.5 bg-navy-950 text-white font-bold text-xs rounded-xl shadow">
        Đóng Cửa Sổ
      </button>
    </div>
  </div>
</div>

<!-- Load EXAM OS X Client-side Engine -->
<script src="<?php echo esc_url( get_template_directory_uri() . '/assets/js/exam-os.js' ); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
	ExamOS.init({
		examId: 'ktc_vong1_2026',
		totalQuestions: <?php echo $total_q; ?>
	});
});

let userAnswers = {};
let flaggedQuestions = {};
let remainingSeconds = 3600;

let timerInterval = setInterval(() => {
  remainingSeconds--;
  let m = Math.floor(remainingSeconds / 60);
  let s = remainingSeconds % 60;
  let timerEl = document.getElementById('quiz-timer');
  if (timerEl) {
    timerEl.innerText = (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
  }
  if (remainingSeconds <= 0) {
    clearInterval(timerInterval);
    submitQuizSimulation();
  }
}, 1000);

function toggleFlagQuestion(qId) {
  // Đồng bộ thật với backend (exam_attempt_answers.is_flagged) qua
  // ExamOS.toggleFlag - trước đây chỉ lưu vào biến JS tạm, mất khi
  // refresh/resume. Giữ tên hàm cũ để không phải sửa mọi onclick.
  ExamOS.toggleFlag(qId);
}

function scrollToQuestion(qId) {
  let el = document.getElementById('question-card-' + qId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function submitQuizSimulation() {
  // Chế độ demo (không có attempt_id thật trong DB): chỉ hiện banner
  // minh họa, không có gì để nộp lên backend - tránh gọi AJAX vô nghĩa.
  if (window.cvc_vars && window.cvc_vars.is_demo) {
    clearInterval(timerInterval);
    let demoBanner = document.getElementById('quiz-results-banner');
    if (demoBanner) {
      demoBanner.classList.remove('hidden');
      demoBanner.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    return;
  }

  if (!window.cvc_vars || !window.cvc_vars.attempt_id) {
    alert('Không tìm thấy lượt thi hợp lệ để nộp bài.');
    return;
  }

  clearInterval(timerInterval);
  setSubmitButtonsDisabled(true);

  let formData = new FormData();
  formData.append('action', 'cvc_exam_submit');
  formData.append('nonce', window.cvc_vars.nonce);
  formData.append('attempt_id', window.cvc_vars.attempt_id);

  fetch(window.cvc_vars.ajax_url, { method: 'POST', body: formData })
    .then(function (res) { return res.json(); })
    .then(function (res) {
      if (!res.success) {
        if (res.data && res.data.already_submitted) {
          alert('Bài thi này đã được nộp trước đó. Vui lòng vào Lịch sử làm bài để xem kết quả.');
        } else {
          alert((res.data && res.data.message) || 'Nộp bài thất bại, vui lòng thử lại.');
          setSubmitButtonsDisabled(false);
        }
        return;
      }

      renderExamResult(res.data);
    })
    .catch(function (err) {
      console.error('Submit exam error:', err);
      alert('Có lỗi kết nối khi nộp bài. Vui lòng kiểm tra mạng và thử lại.');
      setSubmitButtonsDisabled(false);
    });
}

function setSubmitButtonsDisabled(disabled) {
  document.querySelectorAll('button[onclick="submitQuizSimulation()"]').forEach(function (btn) {
    btn.disabled = disabled;
    btn.classList.toggle('opacity-50', disabled);
    btn.classList.toggle('pointer-events-none', disabled);
  });
}

/**
 * Vẽ kết quả thật nhận từ POST /api/exam-attempts/{id}/submit (qua AJAX
 * cvc_exam_submit) - payload = toàn bộ JSON gốc của Laravel
 * ({success, message, data: attempt, recommendations, topic_stats}),
 * KHÔNG phải banner tĩnh/số liệu giả như bản cũ.
 */
/**
 * Giải thích cho 1 câu (chỉ sau khi nộp bài): đáp án đúng lấy từ DB, phần
 * diễn giải do AI viết nếu admin đã cấu hình AI, ngược lại là giải thích
 * có sẵn - nhãn nguồn hiển thị đúng sự thật.
 */
// Lượt thi đã nộp/hết giờ mở lại để xem: cho phép xem giải thích ngay.
window.cvcExamSubmitted = <?php echo ( ! $is_demo_mode && '' !== $attempt_status && 'in_progress' !== $attempt_status ) ? 'true' : 'false'; ?>;

function cvcLoadExplanation(qId) {
  let box = document.getElementById('explanation-' + qId);
  if (!box) return;

  if (!window.cvcExamSubmitted) {
    box.classList.remove('hidden');
    box.innerHTML = '<p class="text-slate-400">Đáp án và giải thích sẽ hiển thị sau khi bạn nộp bài.</p>';
    return;
  }

  box.classList.remove('hidden');
  box.innerHTML = '<p class="text-slate-400">Đang tải giải thích…</p>';

  let formData = new FormData();
  formData.append('action', 'cvc_exam_ai_explain');
  formData.append('nonce', window.cvc_vars.nonce);
  formData.append('attempt_id', window.cvc_vars.attempt_id);
  formData.append('question_id', qId);

  fetch(window.cvc_vars.ajax_url, { method: 'POST', body: formData })
    .then(function (res) { return res.json(); })
    .then(function (res) {
      if (!res.success) {
        box.innerHTML = '<p class="text-rose-300">' + escapeExamHtml((res.data && res.data.message) || 'Không tải được giải thích.') + '</p>';
        return;
      }

      let d = (res.data && res.data.data) || {};
      let base = (window.cvc_vars && window.cvc_vars.home_url) || '/';
      let sourceBadge = d.source === 'ai'
        ? '<span class="px-2.5 py-1 bg-cyan-500 text-navy-950 rounded-lg font-bold">✦ Giải thích bởi AI</span>'
        : '<span class="px-2.5 py-1 bg-slate-800 text-slate-200 rounded-lg font-bold border border-slate-700">Giải thích có sẵn</span>';
      let verdict = d.is_correct === true
        ? '<span class="text-emerald-300 font-bold">✓ Bạn trả lời đúng</span>'
        : (d.is_correct === false ? '<span class="text-rose-300 font-bold">✗ Bạn trả lời sai</span>' : '<span class="text-slate-400">Bạn chưa trả lời câu này</span>');
      let text = d.explanation ? escapeExamHtml(d.explanation).replace(/\n/g, '<br>') : '<span class="text-slate-400">Câu hỏi này chưa có giải thích.</span>';
      let links = [];

      if (d.legal_reference && d.legal_reference.slug) {
        links.push('<a class="text-amber-300 font-bold hover:underline" href="' + base + 'van-ban-phap-luat/' + encodeURIComponent(d.legal_reference.slug) + '/">⚖️ ' + escapeExamHtml((d.legal_reference.document_number ? d.legal_reference.document_number + ' - ' : '') + d.legal_reference.title) + '</a>');
      }
      if (d.knowledge_item && d.knowledge_item.slug) {
        links.push('<a class="text-cyan-300 font-bold hover:underline" href="' + base + 'kien-thuc/' + encodeURIComponent(d.knowledge_item.slug) + '/">📚 ' + escapeExamHtml(d.knowledge_item.title) + '</a>');
      }
      if (d.topic && d.topic.slug) {
        links.push('<a class="text-cyan-300 font-bold hover:underline" href="' + base + 'chu-de/' + encodeURIComponent(d.topic.slug) + '/">🎯 Ôn chủ đề: ' + escapeExamHtml(d.topic.name) + '</a>');
      }

      box.innerHTML =
        '<div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-2">' +
          '<div class="flex items-center gap-2 text-[11px]">' + sourceBadge + verdict + '</div>' +
          (d.correct_option_key ? '<span class="text-cyan-300 font-bold text-xs">Đáp án đúng: ' + escapeExamHtml(d.correct_option_key) + '</span>' : '') +
        '</div>' +
        '<p class="text-slate-300 leading-relaxed">' + text + '</p>' +
        (links.length ? '<div class="pt-2 border-t border-slate-800 flex flex-wrap gap-3 text-[11px]">' + links.join('') + '</div>' : '');

      if (d.correct_option_key) {
        let tile = document.getElementById('tile-' + qId + '-' + d.correct_option_key);
        if (tile) tile.classList.add('is-correct-answer');
      }
    })
    .catch(function () {
      box.innerHTML = '<p class="text-rose-300">Lỗi kết nối, vui lòng thử lại.</p>';
    });
}

function renderExamResult(payload) {
  window.cvcExamSubmitted = true;
  let attempt = (payload && payload.data) || {};
  let recommendations = (payload && payload.recommendations) || [];
  let topicStats = (payload && payload.topic_stats) || [];

  let badge = document.getElementById('quiz-result-badge');
  if (badge) {
    if (attempt.passed === true) {
      badge.className = 'px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300';
      badge.innerText = '✓ ĐẠT YÊU CẦU';
    } else if (attempt.passed === false) {
      badge.className = 'px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300';
      badge.innerText = '✗ CHƯA ĐẠT';
    } else {
      badge.className = 'px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-300';
      badge.innerText = 'ĐÃ HOÀN THÀNH BÀI THI';
    }
  }

  let scoreEl = document.getElementById('quiz-final-score');
  if (scoreEl) {
    let totalScore = attempt.exam && attempt.exam.total_score ? attempt.exam.total_score : null;
    let scoreText = (typeof attempt.score !== 'undefined' && attempt.score !== null) ? attempt.score : '--';
    scoreEl.innerText = totalScore ? (scoreText + '/' + totalScore) : (scoreText + ' điểm');
    if (typeof attempt.percentage !== 'undefined') {
      scoreEl.title = attempt.percentage + '%';
    }
  }

  let grid = document.getElementById('quiz-topic-stats-grid');
  if (grid) {
    if (topicStats.length > 0) {
      grid.innerHTML = topicStats.map(function (stat) {
        let colors = 'bg-rose-50 border-rose-200 text-rose-900';
        if (stat.label === 'Vững vàng') colors = 'bg-emerald-50 border-emerald-200 text-emerald-900';
        else if (stat.label === 'Khá') colors = 'bg-amber-50 border-amber-200 text-amber-900';

        return '<div class="p-4 rounded-2xl border text-center space-y-1 ' + colors + '">' +
          '<span class="text-[10px] font-bold uppercase block opacity-80">' + escapeExamHtml(stat.topic) + '</span>' +
          '<span class="text-xs font-black block mt-1">' + stat.accuracy + '% · ' + escapeExamHtml(stat.label) + '</span>' +
          '</div>';
      }).join('');
    } else {
      grid.innerHTML = '<p class="col-span-full text-xs text-slate-400 italic">Chưa đủ dữ liệu để phân tích theo chủ đề.</p>';
    }
  }

  let recBlock = document.getElementById('quiz-recommendations-block');
  let recList = document.getElementById('quiz-recommendations-list');
  if (recBlock && recList) {
    if (recommendations.length > 0) {
      let baseUrl = (window.cvc_vars && window.cvc_vars.home_url) || '/';

      recList.innerHTML = recommendations.map(function (item) {
        let href = baseUrl + (item.type === 'course' ? 'khoa-hoc/' : 'tai-lieu/') + item.slug + '/';
        let priceLabel = '';

        if (item.type === 'document') {
          priceLabel = item.is_free ? 'Miễn phí' : (formatVnd(item.effective_price) + 'đ');
        } else if (item.sale_price) {
          priceLabel = formatVnd(item.sale_price) + 'đ';
        } else if (item.price) {
          priceLabel = formatVnd(item.price) + 'đ';
        }

        return '<a href="' + href + '" class="block p-4 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 transition-colors space-y-1">' +
          '<span class="text-[10px] uppercase font-bold text-amber-300">' + (item.type === 'course' ? 'Khóa học' : 'Tài liệu') + '</span>' +
          '<h4 class="text-sm font-bold text-white leading-snug">' + escapeExamHtml(item.title) + '</h4>' +
          '<p class="text-[11px] text-slate-300">' + escapeExamHtml(item.reason || '') + '</p>' +
          (priceLabel ? '<span class="inline-block text-[11px] font-black text-amber-300">' + priceLabel + '</span>' : '') +
          '</a>';
      }).join('');

      recBlock.classList.remove('hidden');
    } else {
      recBlock.classList.add('hidden');
    }
  }

  let banner = document.getElementById('quiz-results-banner');
  if (banner) {
    banner.classList.remove('hidden');
    banner.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function formatVnd(value) {
  let num = Number(value);
  if (isNaN(num)) return '0';
  return num.toLocaleString('vi-VN');
}

function escapeExamHtml(str) {
  if (typeof str !== 'string') return '';
  return str.replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

function showLawModal(content) {
  let modal = document.getElementById('lawArticleModal');
  let body = document.getElementById('lawArticleModalBody');
  if (modal && body) {
    body.innerHTML = content;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
}

function closeLawModal() {
  let modal = document.getElementById('lawArticleModal');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}
</script>

<?php get_footer(); ?>
