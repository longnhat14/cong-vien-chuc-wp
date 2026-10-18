<?php
/**
 * KẾT QUẢ & PHÂN TÍCH BÀI THI (18/10/2026) - URL /ket-qua-thi/{attempt_id}/
 *
 * Dựng theo thiết kế congvienchuc-smart-path-v6 (thứ tự: điểm → kế hoạch → trung tâm tiến bộ →
 * bản đồ năng lực → xem lại câu trả lời → lộ trình: tài liệu, bài thi, tuyển dụng; cột phải:
 * bước tiếp theo, AI Coach, kết quả gần đây). Mọi số liệu lấy từ GET /api/exam-attempts/{id}/report
 * (inc/exam-result.php) - không có dữ liệu minh họa.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

nocache_headers();
do_action( 'litespeed_control_set_nocache', 'cvc: trang kết quả thi cá nhân' );

$attempt_id = absint( get_query_var( 'cvc_attempt_id' ) );
$token      = cvc_auth_token();
if ( ! $token ) {
	wp_safe_redirect( cvc_login_url( cvc_exam_result_url( $attempt_id ) ) );
	exit;
}

$res = ( new CVC_Api_Client( null, 20 ) )->get( '/api/exam-attempts/' . $attempt_id . '/report', array(), $token );
if ( ! $res['ok'] && ! in_array( (int) ( $res['status'] ?? 0 ), array( 401, 403, 404, 422 ), true ) ) {
	$res = ( new CVC_Api_Client( null, 30 ) )->get( '/api/exam-attempts/' . $attempt_id . '/report', array(), $token );
}
$status = (int) ( $res['status'] ?? 0 );
if ( 401 === $status ) {
	wp_safe_redirect( cvc_login_url( cvc_exam_result_url( $attempt_id ) ) );
	exit;
}
if ( 422 === $status && ! empty( $res['data']['in_progress'] ) ) {
	wp_safe_redirect( cvc_exam_attempt_url( $attempt_id ) );
	exit;
}
$r = $res['ok'] && is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : null;

$e = static function ( $s ) {
	echo esc_html( (string) $s );
};
$a = static function ( $u ) {
	echo esc_url( (string) $u );
};
$trim = static function ( $s, int $n ): string {
	$s = trim( preg_replace( '/\s+/u', ' ', (string) $s ) );

	return mb_strlen( $s ) > $n ? rtrim( mb_substr( $s, 0, $n - 1 ) ) . '…' : $s;
};
$rel = static fn ( string $path ): string => 0 === strpos( $path, '/' ) ? home_url( $path ) : $path;

if ( null === $r ) {
	status_header( in_array( $status, array( 403, 404 ), true ) ? $status : 503 );
}
cvc_seo_set_noindex();
cvc_seo_set_title( null === $r ? 'Không tìm thấy kết quả bài thi' : 'Kết quả & phân tích: ' . (string) ( $r['exam']['title'] ?? 'bài thi' ) );

if ( null !== $r ) {
	$GLOBALS['cvc_exam_result_cfg'] = array(
		'ajax'       => admin_url( 'admin-ajax.php' ),
		'nonce'      => wp_create_nonce( 'cvc_exam_result' ),
		'attempt'    => (int) $r['attempt']['id'],
		'tasks'      => array_values( array_map( static fn ( $t ) => (string) $t['key'], (array) $r['tasks'] ) ),
		'done'       => (array) $r['tasks_done'],
		'resources'  => (array) $r['resources_done'],
		'weakest'    => $r['weakest']['name'] ?? null,
		'topics'     => array_map( static fn ( $t ) => array( 'name' => $t['name'], 'accuracy' => $t['accuracy'], 'level' => $t['level'] ), array_slice( (array) $r['topics'], 0, 6 ) ),
		'counts'     => $r['counts'],
		'resourcesL' => array_map( static fn ( $x ) => array( 'title' => $x['title'], 'url' => $rel( (string) $x['url'] ) ), (array) $r['resources'] ),
		'quizzesL'   => array_map( static fn ( $x ) => array( 'title' => $x['title'], 'action' => $x['action'] ), (array) $r['quizzes'] ),
		'login'      => cvc_login_url( cvc_exam_result_url( $attempt_id ) ),
	);
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#17334f">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'cvc-exam-result' ); ?>>
<?php wp_body_open(); ?>
<header class="header"><div class="wrap header-in"><a class="brand" href="<?php $a( home_url( '/' ) ); ?>" aria-label="Công Viên Chức - Trang chủ"><span class="brand-logo">CV</span><span><span class="brand-title">CÔNG VIÊN CHỨC</span><span class="brand-sub">Kiến thức vững vàng · Sự nghiệp bền vững</span></span></a><div class="header-right"><?php if ( null !== $r ) : ?><span class="header-pill">● Bài thi đã hoàn thành</span><?php endif; ?><a class="print" href="<?php $a( cvc_account_url( 'exam-history' ) ); ?>">Lịch sử thi</a><?php if ( null !== $r ) : ?><button class="print" id="print" type="button">In / Lưu PDF</button><?php endif; ?></div></div></header>

<?php if ( null === $r ) : ?>
<main class="wrap">
	<div class="crumb"><a href="<?php $a( home_url( '/' ) ); ?>">Trang chủ</a>　›　<a href="<?php $a( cvc_exams_url() ); ?>">Thi thử</a>　›　Kết quả &amp; Phân tích</div>
	<section class="card"><div class="pad empty-page">
		<h1>Không mở được kết quả bài thi</h1>
		<p><?php echo esc_html( 403 === $status ? 'Lượt thi này thuộc một tài khoản khác. Chỉ người làm bài mới xem được kết quả và phân tích.' : ( 404 === $status ? 'Không tìm thấy lượt thi này.' : 'Hệ thống đang bận, chưa tải được kết quả. Vui lòng tải lại trang sau ít phút.' ) ); ?></p>
		<p><a class="secondary inline" href="<?php $a( cvc_account_url( 'exam-history' ) ); ?>">Xem lịch sử làm bài</a> <a class="secondary inline" href="<?php $a( cvc_exams_url() ); ?>">Chọn đề thi thử</a></p>
	</div></section>
</main>
<?php
else :
	$at      = (array) $r['attempt'];
	$ex      = (array) ( $r['exam'] ?? array() );
	$c       = (array) $r['counts'];
	$topics  = (array) $r['topics'];
	$weakest = $r['weakest'] ?? null;
	$pct     = max( 0, min( 100, (float) $at['percentage'] ) );
	$miss    = (int) $c['wrong'] + (int) $c['blank'];
	$offset  = round( 326.7 * ( 1 - $pct / 100 ), 2 );
	$passed  = $at['passed'] ?? null;
	$types   = array( 'practice' => 'Luyện tập', 'mock' => 'Thi thử', 'mock_exam' => 'Thi thử', 'official' => 'Đề chính thức' );
	$type_l  = ! empty( $ex['personal'] ) ? 'Bài luyện cá nhân' : ( $types[ (string) ( $ex['exam_type'] ?? '' ) ] ?? 'Thi thử' );
	if ( true === $passed ) {
		$headline = $pct >= 80 ? 'Kết quả rất tốt — bạn đã đạt!' : 'Bạn đã đạt điểm yêu cầu';
	} elseif ( $pct >= 80 ) {
		$headline = 'Kết quả rất tốt!';
	} elseif ( $pct >= 50 ) {
		$headline = 'Bạn đang tiến bộ!';
	} else {
		$headline = 'Cần củng cố thêm kiến thức';
	}
	$weak_name  = $weakest ? (string) $weakest['name'] : '';
	$weak_short = '' !== $weak_name ? $trim( $weak_name, 70 ) : '';
	$score_lbl  = null !== $at['score'] && null !== $at['total_score'] ? cvc_result_num( $at['score'] ) . '/' . cvc_result_num( $at['total_score'] ) : cvc_result_num( $at['score10'] ) . '/10';
	$ready      = (array) $r['readiness'];
	$tasks      = (array) $r['tasks'];
	$done       = (array) $r['tasks_done'];
	$questions  = (array) $r['questions'];
	$resources  = (array) $r['resources'];
	$res_done   = (array) $r['resources_done'];
	$quizzes    = (array) $r['quizzes'];
	$jobs       = (array) $r['jobs'];
	$recent     = (array) $r['recent'];
	$weak_count = count( array_filter( $topics, static fn ( $t ) => 'weak' === ( $t['level'] ?? '' ) ) );
	$has_quiz   = static function ( string $kind ) use ( $quizzes ): bool {
		foreach ( $quizzes as $q ) {
			if ( 'practice' === ( $q['action']['type'] ?? '' ) && $kind === ( $q['action']['kind'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	};
	$job_type = static function ( array $j ): string {
		$t = (string) ( $j['employment_type'] ?? '' );

		return 'civil_servant' === $t ? 'civil' : ( 'public_employee' === $t ? 'public' : ( '' !== $t ? 'contract' : 'other' ) );
	};
	$fmt_date = static function ( ?string $iso, bool $time = false ): string {
		if ( empty( $iso ) ) {
			return '';
		}
		$ts = strtotime( $iso );

		// Site đặt múi giờ UTC - hiển thị giờ Việt Nam cho thí sinh.
		return $ts ? wp_date( $time ? 'H:i d/m/Y' : 'd/m/Y', $ts, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) : '';
	};
	?>
<main class="wrap" id="cvc-result" data-attempt="<?php echo (int) $at['id']; ?>">
	<div class="crumb"><a href="<?php $a( home_url( '/' ) ); ?>">Trang chủ</a>　›　<a href="<?php $a( cvc_exams_url() ); ?>">Thi thử</a>　›　<?php if ( ! empty( $ex['slug'] ) && empty( $ex['personal'] ) ) : ?><a href="<?php $a( cvc_exam_url( (string) $ex['slug'] ) ); ?>"><?php $e( $trim( $ex['title'] ?? '', 60 ) ); ?></a>　›　<?php endif; ?>Kết quả &amp; Phân tích</div>
	<div class="intro"><div><div class="eyebrow">BÁO CÁO NĂNG LỰC CÁ NHÂN</div><h1>Kết quả &amp; Phân tích bài thi</h1><p>Không chỉ xem điểm — hiểu phần cần củng cố và chọn bước ôn tập tiếp theo.</p></div><div class="complete">● Đã chấm điểm</div></div>

	<div class="grid"><div class="left">

	<section class="card score"><div class="pad">
		<div class="score-kicker">KẾT QUẢ BÀI THI · <?php $e( mb_strtoupper( $type_l, 'UTF-8' ) ); ?></div>
		<div class="score-top">
			<div class="ring"><svg viewBox="0 0 120 120" aria-hidden="true"><circle class="track" cx="60" cy="60" r="52"/><circle class="value" cx="60" cy="60" r="52" style="stroke-dashoffset:<?php echo esc_attr( (string) $offset ); ?>"/></svg><div class="ring-copy"><strong><?php $e( cvc_result_num( $pct, 0 ) ); ?>%</strong><span>Tỷ lệ đúng</span></div></div>
			<div class="score-text">
				<h2><?php $e( $headline ); ?></h2>
				<p>Bạn đã hoàn thành <?php echo empty( $ex['personal'] ) ? 'đề' : 'bài'; ?> <strong><?php $e( $trim( $ex['title'] ?? '', 150 ) ); ?></strong>.<?php if ( null !== $passed && null !== $at['passing_score'] ) : ?> Điểm đạt: <?php $e( cvc_result_num( $at['passing_score'] ) . '/' . cvc_result_num( $at['total_score'] ) ); ?> — <?php echo $passed ? 'bạn đã <strong>đạt</strong>.' : 'bạn <strong>chưa đạt</strong>.'; ?><?php endif; ?> Hãy dùng kết quả này để xác định bước học tiếp theo.</p>
				<div class="score-meta"><?php if ( null !== $at['duration_seconds'] ) : ?><span>◷ <?php $e( cvc_result_duration( (int) $at['duration_seconds'] ) ); ?></span><?php endif; ?><span><?php $e( (int) $c['total'] ); ?> câu hỏi</span><span>Mã lượt thi: #<?php $e( (int) $at['id'] ); ?></span><?php if ( ! empty( $at['submitted_at'] ) ) : ?><span>Nộp lúc <?php $e( $fmt_date( $at['submitted_at'], true ) ); ?></span><?php endif; ?></div>
			</div>
		</div>
		<div class="stats"><div class="stat good"><span>Câu trả lời đúng</span><b><?php $e( (int) $c['correct'] ); ?></b></div><div class="stat bad"><span>Câu trả lời sai</span><b><?php $e( (int) $c['wrong'] ); ?></b></div><div class="stat skip"><span>Câu bỏ trống</span><b><?php $e( (int) $c['blank'] ); ?></b></div><div class="stat"><span><?php echo null !== $at['total_score'] ? 'Điểm đạt được' : 'Điểm quy đổi'; ?></span><b><?php $e( $score_lbl ); ?></b></div></div>
	</div></section>

	<section class="card plan"><div class="pad">
		<div class="plan-head"><span class="plan-icon">✦</span><div><h2>Một lần thi — một kế hoạch tiến bộ</h2><p>Ba bước tiếp theo dựa trên phần kiến thức cần củng cố.</p></div></div>
		<div class="steps">
			<div class="step"><div class="step-no">BƯỚC 01</div><b><?php echo $miss > 0 ? esc_html( 'Rà soát ' . $miss . ' câu sai và bỏ trống' ) : 'Rà soát lại bài làm'; ?></b><span>Đọc giải thích và đối chiếu căn cứ văn bản.</span></div>
			<div class="step"><div class="step-no">BƯỚC 02</div><b>Ôn chủ đề yếu nhất</b><span><?php echo '' !== $weak_short ? esc_html( 'Ưu tiên ' . $weak_short . ' (' . cvc_result_num( $weakest['accuracy'] ) . '% đúng).' ) : 'Các chủ đề đều đạt từ 80% — duy trì bằng đề mới.'; ?></span></div>
			<div class="step"><div class="step-no">BƯỚC 03</div><b>Làm bài luyện ngắn</b><span>Kiểm tra lại phần vừa học trước khi làm đề mới.</span></div>
		</div>
		<button class="primary" id="improve" type="button">✦　Tạo kế hoạch cải thiện điểm số</button>
	</div></section>

	<section class="card"><div class="pad progress-center" id="progress-center">
		<div class="card-head"><div><h2>Trung tâm tiến bộ cá nhân</h2><p>Theo dõi tiến bộ và biến điểm số thành kế hoạch học tập hằng ngày.</p></div><span class="small-tag">Lưu theo tài khoản</span></div>
		<div class="progress-overview">
			<div class="metric"><div class="metric-label">Điểm bài này</div><div class="metric-value"><?php $e( cvc_result_num( $at['score10'] ) ); ?>/10</div><div class="metric-note">Quy đổi từ <?php $e( cvc_result_num( $pct ) ); ?>% câu đúng</div></div>
			<div class="metric"><div class="metric-label">Câu sai cần ôn</div><div class="metric-value"><?php $e( $miss ); ?> câu</div><div class="metric-note"><?php echo $miss > 0 ? 'Gồm ' . (int) $c['wrong'] . ' sai, ' . (int) $c['blank'] . ' bỏ trống' : 'Không có câu sai'; ?></div></div>
			<div class="metric"><div class="metric-label">Nhiệm vụ hôm nay</div><div class="metric-value"><span id="done-count"><?php $e( count( $done ) ); ?></span>/<?php $e( count( $tasks ) ); ?></div><div class="metric-note">Nhiệm vụ đã hoàn thành</div></div>
		</div>
		<div class="streak">
			<div class="streak-icon">↗</div>
			<div><b>Tiến bộ bắt đầu từ những phiên học ngắn</b><small>Hoàn thành nhiệm vụ hôm nay để xây dựng thói quen ôn tập.</small></div>
			<div class="readiness"><strong id="readiness-score"><?php echo null !== $ready['score'] ? esc_html( (int) $ready['score'] . '/100' ) : '--'; ?></strong><small>Chỉ số sẵn sàng<?php echo (int) $ready['basis'] > 0 ? ' · ' . (int) $ready['basis'] . ' lượt gần nhất' : ''; ?></small></div>
		</div>
		<div class="card-head" style="margin:3px 0 0"><div><h2 style="font-size:19px">Kế hoạch học hôm nay</h2><p>Được ưu tiên theo chủ đề có tỷ lệ đúng thấp nhất.</p></div></div>
		<div class="task-list" id="study-tasks">
			<?php foreach ( $tasks as $t ) : ?>
				<?php $is_done = in_array( $t['key'], $done, true ); ?>
				<label class="task<?php echo $is_done ? ' done' : ''; ?>"><input type="checkbox" id="task-<?php echo esc_attr( $t['key'] ); ?>" data-task="<?php echo esc_attr( $t['key'] ); ?>"<?php checked( $is_done ); ?>><span><b><?php $e( $trim( $t['title'], 120 ) ); ?></b><small><?php $e( $t['desc'] ); ?></small></span><span class="task-time"><?php $e( (int) $t['minutes'] ); ?> phút</span></label>
			<?php endforeach; ?>
		</div>
		<div class="task-progress"><div class="task-progress-fill" id="task-progress-fill" style="width:<?php echo esc_attr( (string) ( count( $tasks ) ? round( count( $done ) / count( $tasks ) * 100 ) : 0 ) ); ?>%"></div></div>
		<div class="plan-controls"><button class="strong" id="save-progress" type="button">Lưu tiến độ</button><button id="reset-progress" type="button">Đặt lại checklist</button><button id="export-plan" type="button">In kế hoạch</button><span class="saved-at" id="saved-at"><?php echo ! empty( $r['tasks_saved_at'] ) ? esc_html( 'Đã lưu lúc ' . $fmt_date( $r['tasks_saved_at'], true ) ) : ''; ?></span></div>
		<div class="next-action" style="margin-top:4px"><b><?php echo '' !== $weak_short ? esc_html( 'Đề xuất tiếp theo: củng cố ' . $weak_short ) : 'Đề xuất tiếp theo: làm một đề mới để duy trì phong độ'; ?></b><p>Hãy học phần yếu nhất trước, sau đó làm bài luyện ngắn. Chỉ số sẵn sàng là trung bình có trọng số tỷ lệ đúng của <?php $e( max( 1, (int) $ready['basis'] ) ); ?> lượt thi gần nhất (lượt mới nhất nặng nhất) — không phải xác suất trúng tuyển.</p></div>
	</div></section>

	<section class="card"><div class="pad">
		<div class="card-head"><div><h2>Bản đồ năng lực</h2><p>Tỷ lệ đúng theo chủ đề của các câu trong bài thi này.</p></div><span class="small-tag"><?php $e( count( $topics ) ); ?> nhóm kiến thức</span></div>
		<div class="topic">
			<?php foreach ( array_slice( $topics, 0, 10 ) as $t ) : ?>
				<?php $cls = 'weak' === $t['level'] ? ' low' : ( 'ok' === $t['level'] ? ' mid' : '' ); ?>
				<div class="topic-row"><div><div class="topic-name"><?php if ( ! empty( $t['slug'] ) ) : ?><a href="<?php $a( cvc_topic_url( (string) $t['slug'] ) ); ?>"><?php $e( $trim( $t['name'], 90 ) ); ?></a><?php else : ?><?php $e( $trim( $t['name'], 90 ) ); ?><?php endif; ?></div><div class="topic-desc"><?php $e( (int) $t['correct'] . '/' . (int) $t['total'] . ' câu đúng · ' . $t['label'] ); ?></div></div><div class="bar"><div class="fill<?php echo esc_attr( $cls ); ?>" style="width:<?php echo esc_attr( (string) max( 0, min( 100, (float) $t['accuracy'] ) ) ); ?>%"></div></div><div class="percent"><?php $e( cvc_result_num( $t['accuracy'], 0 ) ); ?>%</div></div>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $topics ) > 10 ) : ?><p class="personal-note" style="margin:10px 0 0">Hiển thị 10 chủ đề yếu nhất trên tổng <?php $e( count( $topics ) ); ?> chủ đề.</p><?php endif; ?>
		<div class="priority"><span>✦</span><span><strong>Ưu tiên hôm nay:</strong> <?php echo '' !== $weak_short ? esc_html( 'bắt đầu từ ' . $weak_short . ', xem lại câu sai rồi làm một bộ câu hỏi tương tự.' ) : esc_html( 'các chủ đề đều vững — xem lại câu sai (nếu có) và thử một đề mới.' ); ?></span></div>
	</div></section>

	<section class="card"><div class="pad" id="review">
		<div class="card-head"><div><h2>Xem lại câu trả lời</h2><p>Đáp án bạn chọn, đáp án đúng, giải thích và căn cứ đi kèm.</p></div><span class="small-tag"><?php $e( count( $questions ) ); ?> câu</span></div>
		<div class="tabs" role="tablist" aria-label="Lọc câu trả lời">
			<button class="tab active" type="button" data-filter="all" aria-selected="true">Tất cả</button>
			<button class="tab" type="button" data-filter="wrong" aria-selected="false">Câu sai (<?php $e( (int) $c['wrong'] ); ?>)</button>
			<button class="tab" type="button" data-filter="blank" aria-selected="false">Bỏ trống (<?php $e( (int) $c['blank'] ); ?>)</button>
			<button class="tab" type="button" data-filter="correct" aria-selected="false">Câu đúng (<?php $e( (int) $c['correct'] ); ?>)</button>
		</div>
		<div id="questions">
			<?php foreach ( $questions as $i => $q ) : ?>
				<?php
				$st     = (string) $q['status'];
				$labels = array( 'wrong' => 'Trả lời sai', 'correct' => 'Trả lời đúng', 'blank' => 'Bỏ trống' );
				$keys   = array_map( static fn ( $o ) => (string) $o['key'], (array) $q['correct'] );
				?>
				<article class="question" data-status="<?php echo esc_attr( $st ); ?>" data-qid="<?php echo (int) $q['question_id']; ?>" data-no="<?php echo (int) $q['no']; ?>"<?php echo $i >= 10 ? ' hidden data-more="1"' : ''; ?>>
					<div class="qtop"><span class="qnum">Câu <?php $e( str_pad( (string) $q['no'], 2, '0', STR_PAD_LEFT ) ); ?><?php if ( ! empty( $q['topic'] ) && count( $topics ) > 1 ) : ?> <small><?php $e( $trim( $q['topic'], 60 ) ); ?></small><?php endif; ?></span><span class="state <?php echo esc_attr( $st ); ?>"><?php $e( $labels[ $st ] ?? '' ); ?></span></div>
					<h3><?php $e( $q['text'] ); ?></h3>
					<?php if ( 'correct' === $st ) : ?>
						<?php foreach ( (array) $q['correct'] as $o ) : ?><div class="answer key"><span class="letter"><?php $e( $o['key'] ); ?></span><span><b>Bạn chọn và đáp án đúng:</b> <?php $e( $o['text'] ); ?></span></div><?php endforeach; ?>
					<?php else : ?>
						<?php if ( ! empty( $q['your'] ) ) : ?><div class="answer chosen"><span class="letter"><?php $e( $q['your']['key'] ); ?></span><span><b>Bạn chọn:</b> <?php $e( $q['your']['text'] ); ?></span></div><?php else : ?><div class="answer"><span class="letter">—</span><span><b>Bạn chọn:</b> Chưa trả lời câu này.</span></div><?php endif; ?>
						<?php foreach ( (array) $q['correct'] as $o ) : ?><div class="answer key"><span class="letter"><?php $e( $o['key'] ); ?></span><span><b>Đáp án đúng:</b> <?php $e( $o['text'] ); ?></span></div><?php endforeach; ?>
					<?php endif; ?>
					<div class="explain">
						<?php if ( ! empty( $q['explanation'] ) ) : ?><b>Giải thích:</b> <?php echo nl2br( esc_html( (string) $q['explanation'] ) ); ?><?php else : ?><b>Giải thích:</b> câu hỏi này chưa có phần giải thích trong ngân hàng.<?php endif; ?>
						<?php if ( ! empty( $q['source']['url'] ) ) : ?><br><a href="<?php $a( $rel( (string) $q['source']['url'] ) ); ?>" class="source" target="_blank" rel="noopener">Căn cứ: <?php $e( $q['source']['label'] ); ?> →</a><?php elseif ( ! empty( $q['source_reference'] ) ) : ?><br><span class="source-ref">Nguồn: <?php $e( $q['source_reference'] ); ?></span><?php endif; ?>
						<?php if ( 'correct' !== $st ) : ?><br><button class="ask-ai" type="button" data-ask="<?php echo (int) $q['question_id']; ?>">✦ Hỏi AI Coach vì sao<?php echo 'blank' === $st ? '' : ' tôi sai'; ?></button><div class="ai-out" hidden></div><?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $questions ) > 10 ) : ?><button class="secondary" id="more" type="button">Xem thêm câu hỏi ↓</button><?php endif; ?>
	</div></section>

	<section class="card" id="personal-path"><div class="pad">
		<div class="module-intro">
			<div class="module-icon"><span>↗</span></div>
			<div style="flex:1"><h2>Lộ trình chinh phục kỳ thi</h2><p>Tài liệu cần học và bài thi được ưu tiên theo nội dung bạn còn yếu.</p><div class="goal-row"><label for="goal-select">Mục tiêu:</label><select class="goal-select" id="goal-select"><option value="all">Tất cả cơ hội</option><option value="civil">Thi tuyển công chức</option><option value="public">Thi tuyển viên chức</option><option value="teacher">Giáo viên</option><option value="health">Y tế</option></select></div></div>
			<span class="small-tag">Cá nhân hóa</span>
		</div>
		<div class="path-summary">
			<div><b id="weak-count"><?php $e( $weak_count ); ?></b><span>Chủ đề cần ưu tiên</span></div>
			<div><b id="resource-count"><?php $e( count( $resources ) ); ?></b><span>Tài liệu đề xuất</span></div>
			<div><b id="quiz-count"><?php $e( count( $quizzes ) ); ?></b><span>Bài thi phù hợp</span></div>
		</div>
		<div class="recommendation-reason" id="personal-reason">Lý do đề xuất: <?php echo '' !== $weak_short ? esc_html( 'chủ đề "' . $weak_short . '" có tỷ lệ đúng thấp nhất (' . cvc_result_num( $weakest['accuracy'] ) . '%). Tài liệu lấy từ điều luật gắn với câu sai và bài kiến thức của chủ đề; bài luyện lấy từ ngân hàng câu hỏi đã duyệt.' ) : esc_html( 'các chủ đề đều đạt từ 80%. Đề xuất tập trung vào câu sai còn lại và đề mới cùng môn.' ); ?></div>
	</div></section>

	<section class="card" id="study-resources"><div class="pad">
		<div class="card-head"><div><h2>1. Tài liệu cần học</h2><p>Ưu tiên tài liệu liên quan trực tiếp đến câu sai và chủ đề yếu.</p></div><span class="small-tag"><?php $e( count( $resources ) ); ?> đề xuất</span></div>
		<?php if ( empty( $resources ) ) : ?>
			<div class="empty-state">Chưa có tài liệu gắn với các câu trong bài này. Xem <a href="<?php $a( cvc_knowledge_url() ); ?>">kho kiến thức</a> hoặc <a href="<?php $a( cvc_documents_url() ); ?>">tài liệu ôn thi</a>.</div>
		<?php else : ?>
		<div class="resource-list" id="resource-list">
			<?php foreach ( $resources as $x ) : ?>
				<?php $learned = in_array( (string) $x['key'], $res_done, true ); ?>
				<article class="resource-item" data-key="<?php echo esc_attr( $x['key'] ); ?>">
					<div class="resource-top">
						<div class="item-icon"><?php $e( $x['icon'] ); ?></div>
						<div class="item-main"><div class="item-title"><?php $e( $x['title'] ); ?></div><div class="item-meta"><?php foreach ( (array) $x['meta'] as $m ) : ?><span><?php $e( $trim( $m, 110 ) ); ?></span><?php endforeach; ?></div></div>
						<span class="state-pill <?php echo esc_attr( $learned ? '' : (string) $x['pill_kind'] ); ?>"><?php $e( $learned ? 'Đã học' : $x['pill'] ); ?></span>
					</div>
					<div class="recommendation-reason"><?php $e( $x['reason'] ); ?></div>
					<div class="item-actions"><a class="primary-action" href="<?php $a( $rel( (string) $x['url'] ) ); ?>" target="_blank" rel="noopener"><?php echo 'legal_article' === $x['type'] ? 'Xem điều luật' : 'Mở tài liệu'; ?></a><button type="button" data-action="mark-resource"<?php echo $learned ? ' class="is-on"' : ''; ?>><?php echo $learned ? 'Bỏ đánh dấu' : 'Đánh dấu đã học'; ?></button></div>
				</article>
			<?php endforeach; ?>
		</div>
		<p class="personal-note" style="margin:10px 0 0">Điều luật chỉ hiển thị khi văn bản đã công khai trên hệ thống; nhãn "Cần xác minh" / "Hết hiệu lực" lấy từ trạng thái văn bản. Luôn đối chiếu văn bản gốc trước khi áp dụng.</p>
		<?php endif; ?>
	</div></section>

	<section class="card" id="personal-quizzes"><div class="pad">
		<div class="card-head"><div><h2>2. Bài thi trắc nghiệm dành cho bạn</h2><p>Bài luyện được tạo riêng cho tài khoản từ chủ đề yếu, câu sai lặp lại và đề cùng môn.</p></div><span class="small-tag"><?php $e( count( $quizzes ) ); ?> đề xuất</span></div>
		<div class="filter-row" id="quiz-filters"><button class="active" type="button" data-filter="recommended">Đề xuất cho tôi</button><button type="button" data-filter="wrong">Câu sai lặp lại</button><button type="button" data-filter="all">Tất cả đề xuất</button></div>
		<div class="quiz-list" id="quiz-list">
			<?php foreach ( $quizzes as $i => $qz ) : ?>
				<?php $act = (array) $qz['action']; ?>
				<article class="quiz-item" data-kind="<?php echo esc_attr( $qz['kind'] ); ?>"<?php echo 'all' === $qz['kind'] ? ' hidden' : ''; ?>>
					<div class="quiz-top">
						<div class="item-icon"><?php $e( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></div>
						<div class="item-main"><div class="item-title"><?php $e( $qz['title'] ); ?></div><div class="item-meta"><?php foreach ( (array) $qz['meta'] as $m ) : ?><span><?php $e( $m ); ?></span><?php endforeach; ?></div></div>
						<span class="state-pill <?php echo esc_attr( (string) $qz['pill_kind'] ); ?>"><?php $e( $qz['pill'] ); ?></span>
					</div>
					<div class="recommendation-reason"><?php $e( $qz['reason'] ); ?></div>
					<div class="item-actions">
						<?php if ( 'practice' === ( $act['type'] ?? '' ) ) : ?>
							<button class="primary-action" type="button" data-action="practice" data-kind="<?php echo esc_attr( (string) $act['kind'] ); ?>">Bắt đầu làm bài</button>
						<?php else : ?>
							<button class="primary-action" type="button" data-action="start-exam" data-exam="<?php echo (int) ( $act['exam_id'] ?? 0 ); ?>">Bắt đầu làm bài</button>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
			<div class="empty-state" id="quiz-empty"<?php echo empty( $quizzes ) ? '' : ' hidden'; ?>>Chưa có bài phù hợp trong nhóm này. <a href="<?php $a( cvc_exams_url() ); ?>">Xem danh sách đề thi thử</a>.</div>
		</div>
		<p class="personal-note" style="margin:10px 0 0">Bài luyện cá nhân chỉ dùng câu hỏi đã duyệt trong ngân hàng, chấm điểm như đề thường và lưu vào lịch sử thi của bạn; không hiển thị công khai.</p>
	</div></section>

	<section class="card" id="job-opportunities"><div class="pad">
		<div class="module-intro">
			<div class="module-icon"><span>⌂</span></div>
			<div style="flex:1"><h2>3. Cơ hội tuyển dụng dành cho bạn</h2><p>Tin còn hạn nộp hồ sơ — ưu tiên đợt có đề bạn vừa làm và việc làm khớp mục tiêu ôn thi.</p></div>
			<span class="small-tag"><?php $e( count( $jobs ) ); ?> tin còn hạn</span>
		</div>
		<div class="filter-row" id="job-filters"><button class="active" type="button" data-job-filter="all">Tất cả</button><button type="button" data-job-filter="civil">Công chức</button><button type="button" data-job-filter="public">Viên chức</button></div>
		<div class="job-list" id="job-list">
			<?php foreach ( $jobs as $j ) : ?>
				<?php
				$days = null;
				if ( ! empty( $j['deadline'] ) ) {
					$days = (int) floor( ( strtotime( $j['deadline'] . ' 23:59:59' ) - time() ) / DAY_IN_SECONDS );
				}
				$tl = array( 'civil' => 'Công chức', 'public' => 'Viên chức', 'contract' => 'Hợp đồng' );
				$jt = $job_type( $j );
				?>
				<article class="job-item" data-job-type="<?php echo esc_attr( $jt ); ?>" data-sector="<?php echo esc_attr( (string) $j['sector'] ); ?>" data-id="<?php echo (int) $j['id']; ?>">
					<div class="job-top">
						<div class="item-icon">⌂</div>
						<div class="item-main"><div class="item-title"><?php $e( $j['title'] ); ?></div><div class="item-meta"><?php if ( ! empty( $j['agency'] ) ) : ?><span>Cơ quan: <?php $e( $j['agency'] ); ?></span><?php endif; ?><?php if ( ! empty( $j['province'] ) ) : ?><span><?php $e( $j['province'] ); ?></span><?php endif; ?><?php if ( ! empty( $j['total_positions'] ) ) : ?><span><?php $e( (int) $j['total_positions'] ); ?> chỉ tiêu</span><?php endif; ?><?php if ( isset( $tl[ $jt ] ) ) : ?><span><?php $e( $tl[ $jt ] ); ?></span><?php endif; ?></div></div>
						<span class="state-pill <?php echo null !== $days && $days <= 7 ? 'warn' : 'neutral'; ?>"><?php echo null === $days ? 'Chưa rõ hạn' : esc_html( $days <= 0 ? 'Hết hạn hôm nay' : 'Còn ' . $days . ' ngày' ); ?></span>
					</div>
					<?php if ( ! empty( $j['deadline'] ) ) : ?><span class="job-deadline">Hạn nộp hồ sơ: <?php $e( cvc_format_date_vn( (string) $j['deadline'] ) ); ?></span><?php endif; ?>
					<div class="recommendation-reason"><?php $e( (string) $j['reason'] ); ?> Đối chiếu điều kiện dự tuyển trong thông báo gốc.</div>
					<div class="item-actions"><a class="primary-action" href="<?php $a( cvc_recruitment_url( (string) $j['slug'] ) ); ?>">Xem thông báo</a><button type="button" data-action="save-job"<?php echo ! empty( $j['saved'] ) ? ' disabled' : ''; ?>><?php echo ! empty( $j['saved'] ) ? 'Đã lưu' : 'Lưu cơ hội'; ?></button></div>
				</article>
			<?php endforeach; ?>
			<div class="empty-state" id="job-empty"<?php echo empty( $jobs ) ? '' : ' hidden'; ?>>Không có tin còn hạn khớp lựa chọn này. <a href="<?php $a( cvc_recruitments_url() ); ?>">Xem tất cả tin tuyển dụng</a>.</div>
		</div>
		<p class="personal-note" style="margin:10px 0 0">Chỉ gợi ý tin còn hạn nộp hồ sơ. Hệ thống không khẳng định bạn đủ điều kiện dự tuyển — hãy kiểm tra trình độ, chuyên ngành và đối tượng trong thông báo của cơ quan tuyển dụng.</p>
	</div></section>

	</div>
	<aside class="right"><div class="right-grid">
		<section class="card"><div class="pad"><h2 class="side-title">Bước tiếp theo</h2>
			<?php if ( $has_quiz( 'wrong' ) ) : ?><button class="side-btn" id="retry" type="button" data-action="practice" data-kind="wrong"><span class="ico">↻</span><span><b>Luyện lại câu sai</b><small>Tạo bài luyện từ <?php $e( $miss ); ?> câu chưa đúng</small></span></button><?php endif; ?>
			<?php if ( $has_quiz( 'weak' ) ) : ?><button class="side-btn" id="weak" type="button" data-action="practice" data-kind="weak"><span class="ico">◎</span><span><b>Ôn chủ đề còn yếu</b><small><?php $e( $trim( $weak_name, 48 ) ); ?></small></span></button><?php else : ?><a class="side-btn" id="weak" href="#study-resources"><span class="ico">◎</span><span><b>Ôn chủ đề còn yếu</b><small>Bắt đầu từ nhóm điểm thấp nhất</small></span></a><?php endif; ?>
			<a class="side-btn" id="next" href="<?php $a( ! empty( $r['next_exam']['slug'] ) ? cvc_exam_url( (string) $r['next_exam']['slug'] ) : cvc_exams_url() ); ?>"><span class="ico">→</span><span><b>Làm đề tiếp theo</b><small><?php $e( ! empty( $r['next_exam']['title'] ) ? $trim( $r['next_exam']['title'], 56 ) : 'Tiếp tục lộ trình luyện thi' ); ?></small></span></a>
			<a class="side-btn" id="history" href="<?php $a( cvc_account_url( 'exam-history' ) ); ?>"><span class="ico">◷</span><span><b>Lịch sử kết quả</b><small>Theo dõi tiến bộ qua các lượt thi</small></span></a>
		</div></section>
		<section class="ai">
			<div class="ai-title"><span class="ai-orb">✦</span><span><b>AI Coach</b><small>Huấn luyện viên ôn thi cá nhân</small></span></div>
			<p>Biến kết quả bài thi thành hành động cụ thể. AI Coach giải thích câu sai dựa trên đáp án chuẩn và căn cứ văn bản.</p>
			<div class="ai-plan"><b>Kế hoạch gợi ý</b>1. Rà soát <?php echo $miss > 0 ? esc_html( $miss . ' câu sai/bỏ trống' ) : 'bài làm'; ?>.<br>2. Ôn <?php echo '' !== $weak_short ? esc_html( $trim( $weak_name, 50 ) ) : 'kiến thức liên quan'; ?>.<br>3. Làm bài luyện ngắn để kiểm tra lại.</div>
			<?php if ( $miss > 0 ) : ?><button class="ai-action" id="why" type="button">✦ Vì sao tôi sai câu này?</button><?php endif; ?>
			<button class="ai-action" id="seven" type="button">▤ Lập kế hoạch ôn 7 ngày</button>
			<div class="week-plan" id="week-plan" hidden></div>
		</section>
		<section class="card"><div class="pad"><h2 class="side-title">Kết quả gần đây</h2><div class="history">
			<?php foreach ( $recent as $h ) : ?>
				<a class="history-row<?php echo ! empty( $h['current'] ) ? ' is-current' : ''; ?>" href="<?php $a( cvc_exam_result_url( (int) $h['id'] ) ); ?>"><span class="history-icon"><?php echo ! empty( $h['current'] ) ? '✓' : '↗'; ?></span><span><b><?php $e( $trim( $h['title'] ?? 'Lượt thi #' . $h['id'], 60 ) ); ?></b><small><?php $e( ( ! empty( $h['current'] ) ? 'Lượt này · ' : '' ) . $fmt_date( $h['submitted_at'] ?? null, true ) ); ?></small></span><span class="history-score"><?php echo null !== $h['percentage'] ? esc_html( (int) $h['percentage'] . '%' ) : '--'; ?></span></a>
			<?php endforeach; ?>
		</div></div></section>
	</div></aside></div>
</main>
<?php endif; ?>
<footer><div class="wrap foot"><span>© Công Viên Chức · Kiến thức vững vàng, sự nghiệp bền vững</span><span><a href="<?php $a( cvc_exams_url() ); ?>">Đề thi thử</a> · <a href="<?php $a( cvc_legal_documents_url() ); ?>">Văn bản pháp luật</a> · <a href="<?php $a( cvc_recruitments_url() ); ?>">Tuyển dụng</a></span></div></footer>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<?php wp_footer(); ?>
</body>
</html>
