<?php
/**
 * CÔNG VIÊN CHỨC — EXAM OS X (Smart Exam Intelligence Platform)
 * Executive 3-Column Shell Architecture & High-Conversion Exam Engine
 * URL: /thi-trac-nghiem/{slug}/lam-bai/ hoặc /lam-bai/{id}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attempt_id = get_query_var( 'attempt_id' ) ?: ( isset( $_GET['attempt_id'] ) ? absint( $_GET['attempt_id'] ) : 0 );
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

if ( $attempt_id > 0 && $token ) {
	$attempt_res = ( new CVC_Exam_Attempt_Service() )->show( $attempt_id, $token );
	if ( $attempt_res['ok'] && ! empty( $attempt_res['data']['data'] ) ) {
		$attempt_data = $attempt_res['data']['data'];
		if ( ! empty( $attempt_data['questions'] ) ) {
			$questions = $attempt_data['questions'];
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
	attempt_id: <?php echo (int) $attempt_id; ?>,
	home_url: '<?php echo esc_js( home_url( '/' ) ); ?>'
};
</script>

<!-- Load EXAM OS X Design System -->
<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css/exam-os.css' ); ?>">

<div class="cvc-exam-os-workspace text-slate-100">

	<!-- 1. TOP STICKY HEADER & COMMAND BAR (TREO NGAY DƯỚI MENU CHÍNH) -->
	<div class="exam-top-sticky shadow-xl border-b border-cyan-500/30">
		
		<!-- Promo Offer Banner Strip -->
		<div class="bg-gradient-to-r from-amber-500/20 via-cyan-500/20 to-blue-500/20 border-b border-slate-800/80 py-1.5 px-4 text-xs">
			<div class="max-w-[1440px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
				<div class="flex items-center gap-2">
					<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2 py-0.5 rounded uppercase">🔥 ƯU ĐÃI NÂNG HẠNG CHỨC DANH 2026</span>
					<span class="text-slate-200 font-semibold hidden sm:inline">Giảm 30% gói Ôn thi Cấp tốc + AI Assistant 24/7. Hạn chót:</span>
					<span id="promo-timer" class="font-mono text-amber-400 font-bold">03:21:44</span>
				</div>
				<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="text-cyan-400 hover:text-cyan-300 font-extrabold flex items-center gap-1 text-[11px] transition-colors">
					Nhận ưu đãi ngay &rarr;
				</a>
			</div>
		</div>

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
					<input type="text" placeholder="🔍 Tìm kiếm câu hỏi, luật..." class="bg-[#071c31] border border-[#12415d] rounded-xl px-3 py-1 text-[11px] text-slate-200 focus:outline-none focus:border-cyan-400 w-44">
				</div>

				<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-2 py-0.5 rounded border border-amber-400 shrink-0 uppercase">
					KTC-2026-01
				</span>
				<span class="font-extrabold text-sm text-white truncate max-w-[180px] sm:max-w-none">
					Đề Thi Sát Hạch Kiến Thức Chung Vòng 1
				</span>
			</div>

			<!-- Center: 4 Exam Modes Selector -->
			<div class="flex items-center bg-[#071c31] p-1 rounded-xl border border-[#12415d] text-[11px] font-bold shrink-0">
				<button type="button" onclick="ExamOS.setExamMode('learn')" data-mode="learn" class="exam-mode-badge">🎓 Học</button>
				<button type="button" onclick="ExamOS.setExamMode('practice')" data-mode="practice" class="exam-mode-badge active">🏋️ Luyện</button>
				<button type="button" onclick="ExamOS.setExamMode('real')" data-mode="real" class="exam-mode-badge">⏱️ Thi thật</button>
				<button type="button" onclick="ExamOS.setExamMode('mock')" data-mode="mock" class="exam-mode-badge">🏛️ Mô phỏng</button>
			</div>

			<!-- Right: Headtools, Timer, Profile Avatar & Submit Button -->
			<div class="flex items-center gap-3 shrink-0">
				<!-- Headtools -->
				<div class="hidden xl:flex items-center gap-1.5">
					<button type="button" onclick="ExamOS.toggleFontSize()" title="A+ Cỡ chữ" class="px-2 py-1 bg-[#071c31] border border-[#12415d] hover:border-cyan-400 text-slate-300 rounded-lg text-[11px] font-bold cursor-pointer">A+</button>
					<button type="button" onclick="ExamOS.toggleFocusMode()" title="Focus Mode" class="px-2 py-1 bg-[#071c31] border border-[#12415d] hover:border-cyan-400 text-slate-300 rounded-lg text-[11px] font-bold cursor-pointer">⤢</button>
				</div>

				<!-- Clock & Pause Button -->
				<div class="bg-[#071c31] border border-amber-500/40 px-3 py-1 rounded-xl flex items-center gap-2 shadow">
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
						🏋️ Chế độ LUYỆN: Tự động lưu tiến độ, hiển thị AI Assist giải thích chuyên sâu.
					</div>
					<div class="flex items-center gap-2 font-mono text-xs">
						<span class="text-slate-400">Tiến độ:</span>
						<span id="answered-progress-text-main" class="text-amber-400 font-bold">0/<?php echo $total_q; ?> câu</span>
						<span id="command-progress-pct-main" class="text-cyan-400 font-bold">(0%)</span>
					</div>
				</div>

				<div class="w-full h-2 bg-[#09243a] rounded-full overflow-hidden border border-[#12415d]">
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

				<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
					<div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-center space-y-1">
						<span class="text-[10px] font-bold text-amber-800 uppercase block">Pháp Luật Công Vụ</span>
						<span class="text-xs font-black text-amber-900 block mt-1">82% · Vững Vàng</span>
					</div>
					<div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-center space-y-1">
						<span class="text-[10px] font-bold text-blue-800 uppercase block">Quản Lý Nhà Nước</span>
						<span class="text-xs font-black text-blue-900 block mt-1">61% · Cần Ôn Thêm</span>
					</div>
					<div class="p-4 rounded-2xl bg-purple-50 border border-purple-200 text-center space-y-1">
						<span class="text-[10px] font-bold text-purple-800 uppercase block">Văn Bản Hành Chính</span>
						<span class="text-xs font-black text-purple-900 block mt-1">73% · Khá Good</span>
					</div>
					<div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-1">
						<span class="text-[10px] font-bold text-emerald-800 uppercase block">Xác Suất Trúng Tuyển</span>
						<span class="text-xl font-black text-emerald-700 block">88.5%</span>
					</div>
				</div>

				<div class="p-6 rounded-2xl bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border border-amber-500/30">
					<div class="space-y-1">
						<span class="bg-amber-400 text-navy-950 text-[10px] font-black uppercase px-2.5 py-0.5 rounded">AI COACH ĐỀ XUẤT NÂNG HẠNG</span>
						<h3 class="text-base font-bold text-white">Tạo Lộ Trình Ôn Thi Cá Nhân Hóa 7 Ngày (Giảm 30%)</h3>
						<p class="text-xs text-slate-300">Nhập mã coupon <strong class="text-amber-300 font-extrabold">PASSER30</strong> để nhận trọn bộ bài giảng trọng tâm 2026.</p>
					</div>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-5 py-2.5 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-500 hover:to-amber-600 text-navy-950 font-black text-xs rounded-xl shadow shrink-0 transition-transform hover:scale-105">
						Nhận Lộ Trình 7 Ngày &rarr;
					</a>
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
						<span class="text-[11px] font-bold text-cyan-400 bg-cyan-500/10 border border-cyan-500/30 px-3 py-1 rounded-md uppercase">
							ĐỘ KHÓ: TRUNG BÌNH
						</span>
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
				<div id="options-container-<?php echo $q['id']; ?>" class="space-y-3">
					<?php foreach ( $normalized_opts as $opt_key => $opt_val ) : ?>
					<div id="tile-<?php echo $q['id']; ?>-<?php echo $opt_key; ?>" onclick="ExamOS.selectAnswerTile(<?php echo $q['id']; ?>, '<?php echo $opt_key; ?>')" class="answer-tile">
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

				<!-- TÀI LIỆU LIÊN QUAN & CĂN CỨ PHÁP LÝ (PER QUESTION DOCUMENTATION) -->
				<div class="p-3.5 rounded-xl bg-[#09243a] border border-[#12415d] space-y-2 text-xs">
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

				<!-- Action Bar & Confidence + Quick Note -->
				<div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800/80">
					<div class="flex items-center gap-2 flex-wrap">
						<button type="button" onclick="toggleFlagQuestion(<?php echo $q['id']; ?>)" class="px-3 py-1.5 bg-[#09243a] hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold border border-[#12415d] transition-colors flex items-center gap-1.5 cursor-pointer">
							<i class="fa-regular fa-star text-amber-400" id="flag-icon-<?php echo $q['id']; ?>"></i> Đánh dấu câu
						</button>
						<button type="button" onclick="ExamOS.toggleStrikeout(<?php echo $q['id']; ?>, 'A')" class="px-3 py-1.5 bg-[#09243a] hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold border border-[#12415d] transition-colors flex items-center gap-1.5 cursor-pointer">
							✕ Loại trừ A
						</button>
						<button type="button" onclick="showLawModal('<?php echo esc_js($q['explanation']); ?>')" class="px-3 py-1.5 bg-[#09243a] hover:bg-slate-800 text-cyan-400 rounded-xl text-xs font-bold border border-[#12415d] transition-colors cursor-pointer flex items-center gap-1.5">
							✦ AI Assist
						</button>
					</div>

					<!-- Confidence Level Selector & Quick Note Input -->
					<div class="flex flex-wrap items-center gap-2">
						<div id="confidence-box-<?php echo $q['id']; ?>" class="hidden items-center gap-1.5 bg-[#09243a] p-1 rounded-xl border border-[#12415d]">
							<span class="text-[10px] text-slate-400 font-bold pl-1">Độ tự tin:</span>
							<button type="button" onclick="ExamOS.setConfidenceLevel(<?php echo $q['id']; ?>, 'low')" data-level="low" class="confidence-pill">Chưa chắc</button>
							<button type="button" onclick="ExamOS.setConfidenceLevel(<?php echo $q['id']; ?>, 'med')" data-level="med" class="confidence-pill">Khá chắc</button>
							<button type="button" onclick="ExamOS.setConfidenceLevel(<?php echo $q['id']; ?>, 'high')" data-level="high" class="confidence-pill">Rất chắc</button>
						</div>

						<div class="flex items-center gap-1">
							<input type="text" id="note-input-<?php echo $q['id']; ?>" placeholder="Viết ghi nhớ..." class="bg-[#09243a] border border-[#12415d] rounded-xl px-2.5 py-1 text-[11px] text-slate-200 focus:outline-none focus:border-cyan-400 w-28">
							<button type="button" onclick="ExamOS.saveQuestionNote(<?php echo $q['id']; ?>)" class="px-2.5 py-1 bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 font-bold text-[11px] rounded-xl border border-cyan-500/40 cursor-pointer">Lưu</button>
						</div>
					</div>
				</div>

				<!-- AI EXPLANATION BOX (WITH 3 TABS: GIẢI THÍCH AI | CĂN CỨ PHÁP LÝ | MẸO GHI NHỚ) -->
				<div id="explanation-<?php echo $q['id']; ?>" class="hidden ai-coach-banner space-y-3 text-xs">
					<div class="flex items-center justify-between border-b border-slate-800 pb-2">
						<div class="flex items-center gap-2 font-bold text-[11px]">
							<button type="button" class="px-2.5 py-1 bg-cyan-500 text-navy-950 rounded-lg">✦ Giải thích AI</button>
							<button type="button" onclick="showLawModal('<?php echo esc_js($q['explanation']); ?>')" class="px-2.5 py-1 bg-[#09243a] text-slate-300 hover:text-white rounded-lg border border-[#12415d]">📜 Căn cứ pháp lý</button>
							<button type="button" class="px-2.5 py-1 bg-[#09243a] text-amber-300 rounded-lg border border-[#12415d]">💡 Mẹo ghi nhớ</button>
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

				<!-- Bottom Navigation Buttons -->
				<div class="flex items-center justify-between pt-4 border-t border-slate-800">
					<button type="button" onclick="scrollToQuestion(Math.max(1, <?php echo $idx; ?>))" class="px-5 py-2.5 bg-[#09243a] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl border border-[#12415d] shadow cursor-pointer">
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
						<button type="button" title="Đánh dấu câu" class="p-1.5 bg-[#09243a] rounded-lg border border-[#12415d] hover:text-white">🔖</button>
						<button type="button" title="Focus Mode" class="p-1.5 bg-[#09243a] rounded-lg border border-[#12415d] hover:text-white">👁️</button>
						<button type="button" title="Cài đặt" class="p-1.5 bg-[#09243a] rounded-lg border border-[#12415d] hover:text-white">⚙️</button>
						<button type="button" title="Trợ giúp" class="p-1.5 bg-[#09243a] rounded-lg border border-[#12415d] hover:text-white">❓</button>
					</div>
				</div>
			</div>

			<!-- BLOCK 2: 📚 CỬA HÀNG HỌC TẬP (HIGH CONVERSION UPSELL STORE) -->
			<div class="exam-card border border-amber-500/40 space-y-3">
				<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
					📚 CỬA HÀNG HỌC TẬP
				</h3>
				<div class="space-y-2 text-xs">
					<div class="flex items-center justify-between p-2 bg-[#09243a] rounded-xl border border-[#12415d]">
						<div>
							<span class="text-slate-200 font-bold block">📘 Bộ 50 đề thi PDF</span>
							<span class="text-[10px] text-slate-400">Giải thích 100%</span>
						</div>
						<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-2.5 py-1 bg-amber-500 text-navy-950 font-black rounded-lg text-[11px] hover:bg-amber-400 shadow">
							49K
						</a>
					</div>
					<div class="flex items-center justify-between p-2 bg-[#09243a] rounded-xl border border-[#12415d]">
						<div>
							<span class="text-slate-200 font-bold block">⚖️ Sơ đồ tư duy Luật</span>
							<span class="text-[10px] text-slate-400">Tóm tắt bẫy thi</span>
						</div>
						<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-2.5 py-1 bg-amber-500 text-navy-950 font-black rounded-lg text-[11px] hover:bg-amber-400 shadow">
							79K
						</a>
					</div>
					<div class="flex items-center justify-between p-2 bg-[#09243a] rounded-xl border border-[#12415d]">
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
	<div class="bg-[#071c31] border-2 border-amber-400 rounded-3xl max-w-md w-full p-6 text-center text-white space-y-4 shadow-2xl">
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
  flaggedQuestions[qId] = !flaggedQuestions[qId];
  let icon = document.getElementById('flag-icon-' + qId);
  if (icon) {
    icon.className = flaggedQuestions[qId] ? "fa-solid fa-star text-amber-400" : "fa-regular fa-star text-slate-400";
  }
  let countEl = document.getElementById('sidebar-flagged-count');
  if (countEl) {
    let count = Object.values(flaggedQuestions).filter(Boolean).length;
    countEl.innerText = count;
  }
}

function scrollToQuestion(qId) {
  let el = document.getElementById('question-card-' + qId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function submitQuizSimulation() {
  clearInterval(timerInterval);
  let scoreBanner = document.getElementById('quiz-results-banner');
  if (scoreBanner) {
    scoreBanner.classList.remove('hidden');
    scoreBanner.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
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
