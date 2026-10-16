<?php
/**
 * CÔNG VIÊN CHỨC — Trang làm bài (giao diện văn bản, 16/10/2026)
 * Sidebar trái: liên kết tài khoản · giữa: nội dung thi · phải: thời gian, bản đồ câu, thông tin đề, phân tích.
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

	// Lần gọi đầu có thể chậm (cache nguội) - thử lại 1 lần với timeout dài hơn,
	// trừ khi API trả lời dứt khoát 401/403/404.
	if ( ! $attempt_res['ok'] && ! in_array( (int) ( $attempt_res['status'] ?? 0 ), array( 401, 403, 404 ), true ) ) {
		$attempt_res = ( new CVC_Api_Client( null, 20 ) )->get( '/api/exam-attempts/' . $attempt_id, array(), $token );
	}

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

/*
 * Không còn "chế độ demo" với bộ câu hỏi mẫu không liên quan tới đề (trước
 * đây mọi nút "Vào thi" ở danh sách/chi tiết đề đều mở chế độ này nên người
 * dùng làm bộ câu hỏi giả, không lưu kết quả). Lượt thi thật luôn bắt đầu từ
 * nút "Bắt đầu làm bài" ở trang chi tiết đề (POST cvc_exam_start).
 */
if ( $is_demo_mode ) {
	if ( '' !== $slug ) {
		wp_safe_redirect( cvc_exam_url( $slug ) );
		exit;
	}

	if ( ! $token ) {
		wp_safe_redirect( cvc_login_url( home_url( add_query_arg( null, null ) ) ) );
		exit;
	}

	status_header( 404 );
	cvc_seo_set_noindex();
	cvc_seo_set_title( 'Không tìm thấy lượt thi' );
	get_header();
	?>
	<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-12">
		<div class="max-w-3xl mx-auto px-4 space-y-4">
			<?php cvc_render_notfound_state( 'Không tìm thấy lượt thi này trong tài khoản của bạn.' ); ?>
			<p class="flex gap-4 flex-wrap">
				<a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">Xem lịch sử làm bài</a>
				<a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_exams_url() ); ?>">Danh sách đề thi</a>
			</p>
		</div>
	</main>
	<?php
	get_footer();
	return;
}

$total_q = count( $questions );

/*
 * Thông tin đề và đồng hồ lấy từ lượt thi thật (trước đây mọi đề đều hiện
 * "Đề Thi Sát Hạch Kiến Thức Chung Vòng 1", mã "KTC-2026-01" và đồng hồ 60
 * phút đếm lại từ đầu mỗi lần tải trang).
 */
$exam_info      = is_array( $attempt_data['exam'] ?? null ) ? $attempt_data['exam'] : array();
$exam_title     = (string) ( $exam_info['title'] ?? 'Bài thi trắc nghiệm' );
$exam_code      = (string) ( $exam_info['code'] ?? '' );
$exam_duration  = (int) ( $exam_info['duration_minutes'] ?? 0 );
$started_ts     = ! empty( $attempt_data['started_at'] ) ? strtotime( (string) $attempt_data['started_at'] ) : false;
$is_submitted   = '' !== $attempt_status && 'in_progress' !== $attempt_status;
$timer_mode     = $is_submitted ? 'done' : ( $exam_duration > 0 && $started_ts ? 'countdown' : 'elapsed' );
$timer_seconds  = 0;
if ( 'countdown' === $timer_mode ) {
	$timer_seconds = max( 0, $exam_duration * 60 - ( time() - (int) $started_ts ) );
} elseif ( 'elapsed' === $timer_mode && $started_ts ) {
	$timer_seconds = max( 0, time() - (int) $started_ts );
}

/*
 * 16/10/2026 - giao diện "Văn bản" cho trang làm bài: nền trắng, chữ đen, toàn bộ Times New Roman;
 * cấu trúc nội dung thi + sidebar phải theo bản thiết kế congvienchuc-smart-exam (Thi tập trung /
 * Danh sách câu / Bản đồ đề; Thời gian & nộp bài; Bản đồ câu hỏi; Thông tin đề thi; Phân tích năng lực).
 * Sidebar trái (liên kết tài khoản) giữ nguyên nội dung. Câu hỏi dựng từ dữ liệu lượt thi thật;
 * đáp án đúng chỉ có sau khi nộp bài (API che đáp án khi đang làm).
 */
$smart_questions = array();
foreach ( (array) ( $attempt_data['answers'] ?? array() ) as $answer ) {
	$question = $answer['question'] ?? null;
	if ( ! is_array( $question ) || empty( $question['id'] ) ) {
		continue;
	}
	$opts = array();
	foreach ( (array) ( $question['options'] ?? array() ) as $i => $opt ) {
		$row = array(
			'key'  => chr( 65 + (int) $i ),
			'id'   => (int) ( $opt['id'] ?? 0 ),
			'text' => (string) ( $opt['option_text'] ?? '' ),
		);
		if ( $is_submitted && array_key_exists( 'is_correct', $opt ) && null !== $opt['is_correct'] ) {
			$row['correct'] = (bool) $opt['is_correct'];
		}
		$opts[] = $row;
	}
	$qid     = (int) $question['id'];
	$state   = $existing_state[ $qid ] ?? array();
	$entry   = array(
		'id'         => $qid,
		'text'       => (string) ( $question['question_text'] ?? '' ),
		'topic'      => (string) ( $question['topic']['name'] ?? '' ),
		'difficulty' => (string) ( $question['difficulty'] ?? '' ),
		'options'    => $opts,
		'selected'   => $state['selected'] ?? null,
		'flagged'    => ! empty( $state['is_flagged'] ),
	);
	if ( $is_submitted && array_key_exists( 'is_correct', $answer ) ) {
		// Câu bỏ trống: không tính là "sai" trên bản đồ câu hỏi.
		$entry['is_correct'] = ( null === $answer['is_correct'] || empty( $entry['selected'] ) ) ? null : (bool) $answer['is_correct'];
	}
	$smart_questions[] = $entry;
}

$exam_total_score   = isset( $exam_info['total_score'] ) ? (float) $exam_info['total_score'] : null;
$exam_passing_score = isset( $exam_info['passing_score'] ) ? (float) $exam_info['passing_score'] : null;
$exam_type_labels   = array( 'practice' => 'Luyện tập', 'mock' => 'Thi thử', 'official' => 'Đề chính thức' );
$exam_type_label    = $exam_type_labels[ (string) ( $exam_info['exam_type'] ?? '' ) ] ?? 'Thi thử';

$smart_payload = array(
	'ajax_url'   => admin_url( 'admin-ajax.php' ),
	'nonce'      => wp_create_nonce( 'cvc_exam_attempt' ),
	'attempt_id' => (int) $real_attempt_id,
	'home_url'   => home_url( '/' ),
	'submitted'  => (bool) $is_submitted,
	'timer'      => array( 'mode' => $timer_mode, 'seconds' => (int) $timer_seconds, 'duration' => $exam_duration * 60 ),
	'exam'       => array(
		'title'         => $exam_title,
		'code'          => $exam_code,
		'duration'      => $exam_duration,
		'total_score'   => $exam_total_score,
		'passing_score' => $exam_passing_score,
		'type_label'    => $exam_type_label,
	),
	'result'     => $is_submitted ? array(
		'score'         => $attempt_data['score'] ?? null,
		'percentage'    => $attempt_data['percentage'] ?? null,
		'passed'        => $attempt_data['passed'] ?? null,
		'correct_count' => $attempt_data['correct_count'] ?? null,
	) : null,
	'questions'  => $smart_questions,
);

add_filter( 'body_class', static fn ( $c ) => array_merge( $c, array( 'cvc-exam-legal' ) ) );
cvc_seo_set_title( 'Làm bài: ' . $exam_title );
cvc_seo_set_noindex();

get_header();
$total_q = count( $smart_questions );
$doc_icon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M15 3v5h4M9 12h7M9 16h7"/></svg>';
?>
<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/css/exam-smart.css?ver=' . (string) filemtime( get_theme_file_path( '/assets/css/exam-smart.css' ) ) ); ?>">

<div class="cvc-smart-exam" id="cvc-smart-exam" data-total="<?php echo (int) $total_q; ?>">

	<header class="sx-top">
		<div class="sx-examtitle"><?php echo $doc_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?><span title="<?php echo esc_attr( $exam_title ); ?>">Đề thi: <?php echo esc_html( $exam_title ); ?></span></div>
		<div class="sx-stats">
			<div class="sx-stat"><small>Đã trả lời</small><strong><span id="sx-answered">0</span>/<?php echo (int) $total_q; ?></strong></div>
			<div class="sx-stat sx-progressstat"><small>Tiến độ</small><div class="sx-statbar"><div class="sx-bar"><span id="sx-progress"></span></div><strong id="sx-percent">0%</strong></div></div>
		</div>
	</header>

	<nav class="sx-toolbar" aria-label="Chế độ làm bài">
		<div class="sx-meta">
			<?php if ( '' !== $exam_code ) : ?><span>Mã đề: <?php echo esc_html( $exam_code ); ?></span><?php endif; ?>
			<span>☷ <?php echo (int) $total_q; ?> câu</span>
			<span>◷ <?php echo $exam_duration > 0 ? (int) $exam_duration . ' phút' : 'Không giới hạn'; ?></span>
		</div>
		<div class="sx-switch">
			<button type="button" class="sx-mode active" data-mode="focus"><span class="sx-ic" aria-hidden="true">▣</span> Thi tập trung</button>
			<button type="button" class="sx-mode" data-mode="list"><span class="sx-ic" aria-hidden="true">☷</span> Danh sách câu</button>
			<button type="button" class="sx-mode" data-mode="map"><span class="sx-ic" aria-hidden="true">▦</span> Bản đồ đề</button>
			<button type="button" class="sx-mode sx-fontbtn" id="sx-font" title="Đổi cỡ chữ (A / A+ / A++)" aria-label="Đổi cỡ chữ">A<sup>+</sup></button>
		</div>
	</nav>

	<div class="sx-page" id="sx-page">

		<!-- Sidebar trái: liên kết tài khoản (giữ nguyên nội dung) -->
		<aside class="sx-left" aria-label="Liên kết nhanh">
			<nav class="sx-panel sx-leftnav">
				<span class="sx-leftitem active"><span aria-hidden="true">📝</span> Làm bài thi</span>
				<a href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>" class="sx-leftitem"><span aria-hidden="true">📜</span> Lịch sử làm bài</a>
				<a href="<?php echo esc_url( cvc_account_url( 'recommendations' ) ); ?>" class="sx-leftitem"><span aria-hidden="true">💡</span> Gợi ý ôn tập</a>
				<a href="<?php echo esc_url( cvc_account_url( 'my-courses' ) ); ?>" class="sx-leftitem"><span aria-hidden="true">🎓</span> Khóa học của tôi</a>
				<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="sx-leftitem"><span aria-hidden="true">📚</span> Danh sách đề thi</a>
			</nav>
		</aside>

		<main class="sx-panel sx-mainpanel" id="sx-mainpanel">
			<!-- Đầu văn bản -->
			<div class="sx-dochead">
				<p class="sx-dockind"><?php echo esc_html( mb_strtoupper( $exam_type_label, 'UTF-8' ) ); ?> TRẮC NGHIỆM</p>
				<h1 class="sx-doctitle"><?php echo esc_html( $exam_title ); ?></h1>
				<p class="sx-docsub"><em>Thời gian làm bài: <?php echo $exam_duration > 0 ? (int) $exam_duration . ' phút' : 'không giới hạn'; ?> · <?php echo (int) $total_q; ?> câu hỏi<?php echo null !== $exam_passing_score && $exam_total_score ? ' · Điểm đạt: ' . esc_html( rtrim( rtrim( number_format( $exam_passing_score, 2, ',', '' ), '0' ), ',' ) ) . '/' . esc_html( rtrim( rtrim( number_format( $exam_total_score, 2, ',', '' ), '0' ), ',' ) ) : ''; ?></em></p>
				<span class="sx-docrule" aria-hidden="true"></span>
			</div>

			<section class="sx-main sx-single-only" id="sx-single">
				<div class="sx-qtop">
					<span class="sx-qbadge" id="sx-counter">Câu 1/<?php echo (int) $total_q; ?></span>
					<div class="sx-tools">
						<span class="sx-muted" id="sx-topic"></span>
						<label class="sx-check"><input id="sx-flag" type="checkbox"> Đánh dấu xem lại</label>
					</div>
				</div>
				<h2 class="sx-qtext" id="sx-qtext"></h2>
				<p class="sx-hintline" id="sx-hintline">Chọn một đáp án đúng nhất.</p>
				<div class="sx-answers" id="sx-answers"></div>

				<div class="sx-feedback" id="sx-feedback" aria-live="polite"></div>

				<div class="sx-actions">
					<div class="sx-group">
						<button type="button" class="sx-btn" data-support="explain" id="sx-explainbtn">▤ Đáp án &amp; căn cứ pháp lý</button>
						<button type="button" class="sx-btn" id="sx-noteToggle">✎ Ghi chú</button>
					</div>
					<span class="sx-muted" id="sx-saveStatus">Bài làm được lưu tự động</span>
				</div>
				<div class="sx-notes sx-hidden" id="sx-notes">
					<div class="sx-notes-head"><strong>Ghi chú cá nhân</strong><span class="sx-muted">Chỉ lưu trên thiết bị này</span></div>
					<textarea id="sx-note" placeholder="Ghi lại điều cần nhớ hoặc xem lại sau..."></textarea>
				</div>
				<div class="sx-nav">
					<button type="button" class="sx-btn" id="sx-prev">← Câu trước</button>
					<div class="sx-group">
						<button type="button" class="sx-btn" id="sx-clear">× Bỏ chọn</button>
						<button type="button" class="sx-btn" id="sx-flagBtn">⚑ Đánh dấu</button>
					</div>
					<button type="button" class="sx-btn sx-primary" id="sx-next">Câu tiếp theo →</button>
				</div>
			</section>

			<section class="sx-all" id="sx-all" aria-label="Danh sách câu hỏi"></section>
			<section class="sx-maponly" id="sx-maponly">
				<h2>Bản đồ toàn đề</h2>
				<p>Chọn số câu để đi đến câu đó. Trạng thái được cập nhật ngay khi trả lời.</p>
				<div class="sx-grid sx-grid-large" id="sx-largeGrid"></div>
			</section>
		</main>

		<aside class="sx-side" id="sx-side">
			<section class="sx-panel sx-exam-control">
				<div class="sx-head"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><h2>Thời gian &amp; nộp bài</h2></div>
				<div class="sx-body">
					<div class="sx-timer-row">
						<div><span class="sx-muted" id="sx-timerlabel"><?php echo 'countdown' === $timer_mode ? 'Thời gian còn lại' : ( 'elapsed' === $timer_mode ? 'Thời gian đã làm' : 'Trạng thái' ); ?></span><div class="sx-timer-value" id="sx-timer"><?php echo 'done' === $timer_mode ? 'Đã nộp' : '--:--'; ?></div></div>
						<div class="sx-timer-progress"><span id="sx-sideProgress"></span></div>
					</div>
					<div class="sx-side-submit-meta"><span>Đã trả lời <strong><span id="sx-sideAnswered">0</span>/<?php echo (int) $total_q; ?></strong></span><span id="sx-sidePercent">0% hoàn thành</span></div>
					<button type="button" class="sx-btn sx-danger sx-submit-wide" id="sx-submitTop"<?php echo $is_submitted ? ' disabled' : ''; ?>>▤ <?php echo $is_submitted ? 'Đã nộp bài' : 'Nộp bài'; ?></button>
					<p class="sx-muted sx-submit-note"><?php echo $is_submitted ? 'Bài đã nộp - mở từng câu để xem đáp án và giải thích.' : 'Bạn có thể nộp bài bất cứ lúc nào, kể cả khi đang ở câu cuối.'; ?></p>
				</div>
			</section>

			<section class="sx-panel">
				<div class="sx-head"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5h12M9 12h12M9 19h12M3 5h.01M3 12h.01M3 19h.01"/></svg><h2>Bản đồ câu hỏi</h2><span class="sx-muted sx-headcount" id="sx-mapcount">0/<?php echo (int) $total_q; ?></span></div>
				<div class="sx-body">
					<div class="sx-legend" id="sx-legend">
						<span><i class="sx-swatch done"></i>Đã trả lời</span><span><i class="sx-swatch now"></i>Đang xem</span><span><i class="sx-swatch"></i>Chưa làm</span><span><i class="sx-swatch flag"></i>Đánh dấu</span>
						<?php if ( $is_submitted ) : ?><span><i class="sx-swatch right"></i>Đúng</span><span><i class="sx-swatch wrong"></i>Sai</span><?php endif; ?>
					</div>
					<div class="sx-grid" id="sx-grid"></div>
					<div class="sx-filters">
						<button type="button" class="sx-btn active" data-filter="all">Tất cả</button>
						<button type="button" class="sx-btn" data-filter="unanswered">Chưa làm</button>
						<button type="button" class="sx-btn" data-filter="answered">Đã làm</button>
						<button type="button" class="sx-btn" data-filter="flagged">Xem lại</button>
						<?php if ( $is_submitted ) : ?><button type="button" class="sx-btn" data-filter="wrong">Làm sai</button><?php endif; ?>
					</div>
				</div>
			</section>

			<section class="sx-panel">
				<div class="sx-head"><?php echo $doc_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?><h2>Thông tin đề thi</h2></div>
				<div class="sx-body sx-body-tight">
					<table class="sx-details">
						<tr><td>Đề thi</td><td><?php echo esc_html( $exam_title ); ?></td></tr>
						<?php if ( '' !== $exam_code ) : ?><tr><td>Mã đề</td><td><?php echo esc_html( $exam_code ); ?></td></tr><?php endif; ?>
						<tr><td>Số câu</td><td><?php echo (int) $total_q; ?> câu</td></tr>
						<tr><td>Thời gian</td><td><?php echo $exam_duration > 0 ? (int) $exam_duration . ' phút' : 'Không giới hạn'; ?></td></tr>
						<tr><td>Hình thức</td><td>Trắc nghiệm, chọn một đáp án</td></tr>
						<tr><td>Trạng thái</td><td class="sx-status" id="sx-status"><?php echo $is_submitted ? '● Đã nộp bài' : '● Đang làm bài'; ?></td></tr>
					</table>
					<div class="sx-tips">
						<h3>♧ Thao tác nhanh</h3>
						<ul>
							<li>Phím 1–4 hoặc A–D: chọn đáp án.</li>
							<li>Phím ← / →: chuyển câu.</li>
							<li>Phím F: đánh dấu xem lại.</li>
							<li>Đáp án được lưu tự động vào tài khoản.</li>
						</ul>
					</div>
				</div>
			</section>

			<section class="sx-panel sx-analysis<?php echo $is_submitted ? ' show' : ''; ?>" id="sx-analysis">
				<div class="sx-head"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5m0 14h17M7 15l4-4 3 2 5-7"/></svg><h2>Phân tích năng lực</h2></div>
				<div class="sx-body">
					<div id="sx-scorebox"></div>
					<div id="sx-weakness"><p class="sx-muted">Kết quả theo chủ đề hiển thị ngay sau khi nộp bài.</p></div>
					<div id="sx-recs"></div>
					<a class="sx-btn sx-wide" href="<?php echo esc_url( cvc_account_url( 'recommendations' ) ); ?>">Xem gợi ý ôn tập</a>
				</div>
			</section>
		</aside>
	</div>

	<aside class="sx-support" id="sx-support" role="dialog" aria-modal="true" aria-labelledby="sx-supportTitle">
		<div class="sx-supporthead"><div><span class="sx-kicker" id="sx-kicker">ĐÁP ÁN VÀ CĂN CỨ</span><h2 id="sx-supportTitle">Giải thích đáp án</h2></div><button type="button" class="sx-btn" id="sx-closeSupport" aria-label="Đóng">✕</button></div>
		<div class="sx-supportcontent" id="sx-supportContent"></div>
	</aside>

	<div class="sx-overlay" id="sx-submitDialog"><div class="sx-dialog" role="alertdialog" aria-labelledby="sx-submitTitle"><h2 id="sx-submitTitle">Xác nhận nộp bài</h2><p id="sx-submitMessage"></p><div class="sx-dialogactions"><button type="button" class="sx-btn" id="sx-cancel">Tiếp tục làm bài</button><button type="button" class="sx-btn sx-danger" id="sx-confirm">Nộp bài</button></div></div></div>
	<div class="sx-overlay" id="sx-resultDialog"><div class="sx-dialog" role="dialog" aria-labelledby="sx-resultTitle"><h2 id="sx-resultTitle">Kết quả bài thi</h2><div id="sx-resultBody"></div><div class="sx-dialogactions"><button type="button" class="sx-btn" id="sx-closeResult">Xem lại bài làm</button><button type="button" class="sx-btn sx-primary" id="sx-showAnalysis">Xem phân tích</button></div></div></div>
	<div class="sx-toast" id="sx-toast" role="status" aria-live="polite"></div>
</div>

<script>window.CVC_SMART_EXAM = <?php echo wp_json_encode( $smart_payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ); ?>;</script>
<script src="<?php echo esc_url( get_template_directory_uri() . '/assets/js/exam-smart.js?ver=' . (string) filemtime( get_theme_file_path( '/assets/js/exam-smart.js' ) ) ); ?>"></script>

<?php get_footer(); ?>
