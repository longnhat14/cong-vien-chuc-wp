<?php
/**
 * Tổng quan tài khoản v1 (19/10/2026) - /tai-khoan/tong-quan/, thiết kế congvienchuc-account-dashboard-v1.
 * Sidebar quản lý bên trái + dashboard. Toàn bộ số liệu thật từ GET /api/me/dashboard (người đang đăng nhập);
 * lỗi API hiện trạng thái lỗi, không thay bằng số 0.
 *
 * @var array  $section_titles Nhãn các mục tài khoản (template-account.php).
 * @var array  $user           Người dùng hiện tại.
 * @var string $token          Token API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

nocache_headers();
do_action( 'litespeed_control_set_nocache', 'cvc: tổng quan tài khoản' );

$ad_res = ( new CVC_Api_Client( null, 15 ) )->get( '/api/me/dashboard', array( 'period' => '7d' ), $token );
$ad     = $ad_res['ok'] ? (array) ( $ad_res['data']['data'] ?? array() ) : array();
$ad_ok  = ! empty( $ad );
$u      = (array) ( $ad['user'] ?? array() );
$name   = (string) ( $u['name'] ?? ( $user['name'] ?? '' ) ) ?: 'bạn';
$compl  = (array) ( $u['completion'] ?? array() );
$pct    = (int) ( $compl['percent'] ?? 0 );
$k      = (array) ( $ad['kpis'] ?? array() );
$goal   = is_array( $ad['goal'] ?? null ) ? $ad['goal'] : null;
$cont   = (array) ( $ad['continue'] ?? array() );
$open   = (array) ( $cont['attempts'] ?? array() );
$saved  = (array) ( $ad['saved_recruitments'] ?? array() );
$weak   = (array) ( $ad['weak_topics'] ?? array() );
$qs     = (array) ( $ad['questions'] ?? array() );
$acts   = (array) ( $ad['activity'] ?? array() );
$streak = is_array( $ad['streak'] ?? null ) ? $ad['streak'] : null;
$unread = function_exists( 'cvc_chrome_unread' ) ? cvc_chrome_unread() : 0;

$ad_fmt_min = static function ( int $m ): string {
	if ( $m < 60 ) {
		return $m . ' phút';
	}
	return intdiv( $m, 60 ) . ' giờ' . ( $m % 60 ? ' ' . ( $m % 60 ) . ' phút' : '' );
};
// So với kỳ trước bằng chênh lệch tuyệt đối (phần trăm dễ thành con số vô nghĩa khi kỳ trước rất nhỏ).
$ad_trend = static function ( int $cur, int $prev, string $unit = '' ) use ( &$ad_fmt_min ): string {
	if ( 0 === $prev ) {
		return $cur > 0 ? 'Mới trong kỳ này' : 'Chưa có hoạt động';
	}
	$d = $cur - $prev;
	if ( 0 === $d ) {
		return 'Bằng kỳ trước';
	}
	$abs = 'min' === $unit ? $ad_fmt_min( abs( $d ) ) : abs( $d ) . ' lượt';
	return ( $d > 0 ? '+' : '−' ) . $abs . ' so với kỳ trước';
};
$ad_when = static function ( ?string $iso ): string {
	if ( ! $iso ) {
		return '';
	}
	$ts   = strtotime( $iso );
	$diff = time() - $ts;
	if ( $diff < 3600 ) {
		return max( 1, (int) floor( $diff / 60 ) ) . ' phút trước';
	}
	if ( $diff < 86400 ) {
		return (int) floor( $diff / 3600 ) . ' giờ trước';
	}
	if ( $diff < 7 * 86400 ) {
		return (int) floor( $diff / 86400 ) . ' ngày trước';
	}
	return wp_date( 'd/m/Y', $ts );
};
$ad_date = static fn ( ?string $d ): string => $d ? wp_date( 'd/m/Y', strtotime( $d . ' 12:00:00' ) ) : '';

// Sidebar theo mẫu: mục tài khoản hiện có + Câu hỏi đã lưu.
$side = array(
	array( '▦', 'Tổng quan cá nhân', cvc_account_url( 'overview' ), true ),
	array( '⚑', 'Hồ sơ chinh phục kỳ thi', cvc_account_url( 'conquest' ), false ),
	array( '▤', 'Tài liệu của tôi', cvc_account_url( 'my-documents' ), false ),
	array( '☆', 'Tin & nội dung đã lưu', cvc_account_url( 'bookmarks' ), false ),
	array( '◷', 'Lịch sử luyện thi & kết quả', cvc_account_url( 'exam-history' ), false ),
	array( '?', 'Câu hỏi đã lưu', cvc_saved_questions_url(), false ),
	array( '▧', 'Kế hoạch & lộ trình học tập', cvc_account_url( 'learning-path' ), false ),
	array( '⌕', 'Việc làm phù hợp', cvc_account_url( 'recruitment-matches' ), false ),
	array( '✦', 'Gợi ý ôn tập', cvc_account_url( 'recommendations' ), false ),
	array( '♙', 'Khóa học của tôi', cvc_account_url( 'my-courses' ), false ),
	array( '♧', 'Thông báo', cvc_account_url( 'notifications' ), false, $unread ),
	array( '✓', 'Chứng chỉ của tôi', cvc_account_url( 'certificates' ), false ),
	array( '▣', 'Hồ sơ cá nhân', cvc_account_url( 'profile' ), false ),
	array( '⚙', 'Bảo mật & đăng nhập', cvc_account_url( 'security' ), false ),
);
$resume_url = $open ? cvc_exam_attempt_url( (int) $open[0]['attempt_id'] ) : cvc_question_bank_url();
$missing    = array_values( array_filter( (array) ( $compl['checks'] ?? array() ), static fn ( $c ) => empty( $c['done'] ) ) );

$GLOBALS['cvc_ad_data'] = array(
	'ajax'   => admin_url( 'admin-ajax.php' ),
	'nonce'  => wp_create_nonce( 'cvc_ad' ),
	'chart'  => $ad['chart'] ?? null,
	'streak' => $streak ? (int) ( $streak['current_streak'] ?? 0 ) : null,
);

cvc_seo_set_title( 'Tổng quan tài khoản' );
get_header();
?>
<main id="main" class="ad">
<div class="ad-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Tài khoản', 'url' => cvc_account_url() ),
			array( 'label' => 'Tổng quan' ),
		)
	);
	?>
	<div class="ad-shell">
		<aside class="ad-side" aria-label="Danh mục quản lý tài khoản">
			<div class="ad-side-title">☷ DANH MỤC QUẢN LÝ</div>
			<nav class="ad-side-nav">
				<?php foreach ( $side as $s ) : ?>
					<a class="ad-side-link<?php echo $s[3] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $s[2] ); ?>"<?php echo $s[3] ? ' aria-current="page"' : ''; ?>><span aria-hidden="true"><?php echo esc_html( $s[0] ); ?></span><?php echo $s[3] ? '<b>' . esc_html( $s[1] ) . '</b>' : esc_html( $s[1] ); ?><?php if ( ! empty( $s[4] ) ) : ?><em class="ad-badge"><?php echo (int) $s[4] > 9 ? '9+' : (int) $s[4]; ?></em><?php endif; ?></a>
				<?php endforeach; ?>
				<a class="ad-side-link is-out" href="<?php echo esc_url( cvc_logout_url() ); ?>"><span aria-hidden="true">⇥</span>Đăng xuất</a>
			</nav>
			<?php if ( $ad_ok && $missing ) : ?>
				<div class="ad-side-help">
					<strong>✦ Gợi ý cho bạn</strong>
					<p>Hồ sơ còn thiếu: <?php echo esc_html( implode( ', ', array_map( static fn ( $c ) => mb_strtolower( (string) $c['label'] ), array_slice( $missing, 0, 3 ) ) ) ); ?>. Hoàn thiện để nhận gợi ý tuyển dụng và lộ trình phù hợp hơn.</p>
					<a href="<?php echo esc_url( 'goal' === ( $missing[0]['key'] ?? '' ) ? cvc_account_url( 'conquest' ) : cvc_account_url( 'profile' ) ); ?>">Hoàn thiện hồ sơ →</a>
				</div>
			<?php endif; ?>
		</aside>

		<div class="ad-content">
			<?php cvc_render_notice(); ?>
			<section class="ad-hero">
				<div class="ad-hero-row">
					<div>
						<div class="ad-eyebrow"><i class="ad-dot"></i>KHÔNG GIAN HỌC TẬP CỦA BẠN</div>
						<h1>Xin chào, <?php echo esc_html( $name ); ?>!</h1>
						<p>Mỗi ngày một bước tiến. Theo dõi hành trình ôn thi, quản lý cơ hội tuyển dụng đã lưu và tập trung vào kiến thức giúp bạn sẵn sàng hơn cho kỳ thi công chức, viên chức.</p>
						<div class="ad-actions">
							<a class="ad-btn is-primary" href="<?php echo esc_url( $resume_url ); ?>">▶ <?php echo $open ? 'Tiếp tục bài đang làm' : 'Bắt đầu ôn tập'; ?></a>
							<a class="ad-btn is-teal" href="<?php echo esc_url( cvc_account_url( 'conquest' ) ); ?>">◎ <?php echo $goal ? 'Mục tiêu của tôi' : 'Đặt mục tiêu ôn thi'; ?></a>
							<a class="ad-btn" href="<?php echo esc_url( cvc_account_url( 'profile' ) ); ?>">✎ Hồ sơ cá nhân</a>
						</div>
					</div>
					<?php if ( $ad_ok ) : ?>
						<div class="ad-ring" style="--p:<?php echo (int) $pct; ?>" role="img" aria-label="<?php echo esc_attr( 'Hồ sơ hoàn thiện ' . $pct . '%' ); ?>"><div><strong><?php echo (int) $pct; ?>%</strong><small>Hồ sơ hoàn thiện</small></div></div>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( ! $ad_ok ) : ?>
				<div class="ad-card ad-error"><b>Chưa tải được số liệu tài khoản</b><p>Máy chủ đang bận hoặc phiên đăng nhập đã hết hạn. Vui lòng tải lại trang sau ít phút.</p><a class="ad-btn" href="<?php echo esc_url( cvc_account_url() ); ?>">Tải lại</a></div>
			<?php else : ?>

			<section class="ad-stats" aria-label="Chỉ số chính">
				<article class="ad-stat" style="--wash:#e8f4f0;--accent:#126f68">
					<div class="ad-stathead"><span class="ad-ico">◷</span><span class="ad-trend" data-ad-trend-study><?php echo esc_html( $ad_trend( (int) ( $k['study']['minutes'] ?? 0 ), (int) ( $k['study']['prev_minutes'] ?? 0 ), 'min' ) ); ?></span></div>
					<strong data-ad-kpi-study><?php echo esc_html( $ad_fmt_min( (int) ( $k['study']['minutes'] ?? 0 ) ) ); ?></strong>
					<label>Thời gian làm bài</label>
					<small data-ad-period-label>7 ngày gần nhất · tính từ các lượt đã nộp</small>
				</article>
				<article class="ad-stat" style="--wash:#edf3f9;--accent:#285b82">
					<div class="ad-stathead"><span class="ad-ico">✓</span><span class="ad-trend" data-ad-trend-exams><?php echo esc_html( $ad_trend( (int) ( $k['exams']['count'] ?? 0 ), (int) ( $k['exams']['prev_count'] ?? 0 ) ) ); ?></span></div>
					<strong data-ad-kpi-exams><?php echo (int) ( $k['exams']['count'] ?? 0 ); ?></strong>
					<label>Lượt thi đã hoàn thành</label>
					<small data-ad-kpi-avg><?php echo null !== ( $k['exams']['avg_pct'] ?? null ) ? esc_html( 'Điểm trung bình ' . rtrim( rtrim( number_format( (float) $k['exams']['avg_pct'], 1, ',', '' ), '0' ), ',' ) . '% · tổng ' . (int) ( $k['exams']['total'] ?? 0 ) . ' lượt' ) : esc_html( 'Tổng ' . (int) ( $k['exams']['total'] ?? 0 ) . ' lượt đã nộp' ); ?></small>
				</article>
				<article class="ad-stat" style="--wash:#fff4df;--accent:#7f5d1c">
					<div class="ad-stathead"><span class="ad-ico">▣</span><span class="ad-trend"><?php echo (int) ( $k['saved']['new_7d'] ?? 0 ) > 0 ? esc_html( (int) $k['saved']['new_7d'] . ' mới trong 7 ngày' ) : 'Đã lưu'; ?></span></div>
					<strong><?php echo (int) ( $k['saved']['count'] ?? 0 ); ?></strong>
					<label>Cơ hội tuyển dụng đã lưu</label>
					<small><?php echo (int) ( $k['saved']['due_soon'] ?? 0 ) > 0 ? esc_html( (int) $k['saved']['due_soon'] . ' tin hết hạn nộp trong 7 ngày' ) : 'Không có tin sắp hết hạn'; ?></small>
				</article>
				<article class="ad-stat" style="--wash:#f8eaea;--accent:#963f3f">
					<div class="ad-stathead"><span class="ad-ico">✦</span><?php if ( (int) ( $k['weak']['count'] ?? 0 ) > 0 ) : ?><span class="ad-pill is-red">Cần củng cố</span><?php else : ?><span class="ad-trend">Ổn định</span><?php endif; ?></div>
					<strong><?php echo (int) ( $k['weak']['count'] ?? 0 ); ?> chủ đề</strong>
					<label>Kiến thức cần ôn lại</label>
					<small>Tỷ lệ đúng dưới 70% (từ 5 câu trở lên)</small>
				</article>
			</section>

			<div class="ad-sect"><div><h2>Tổng quan tiến độ</h2><p>Nhịp làm bài và điểm số theo thời gian.</p></div>
				<label class="ad-sr" for="ad-period">Khoảng thời gian</label>
				<select class="ad-select" id="ad-period" data-ad-period><option value="7d">7 ngày gần nhất</option><option value="30d">30 ngày gần nhất</option><option value="90d">3 tháng gần nhất</option></select>
			</div>
			<section class="ad-grid2">
				<article class="ad-card">
					<div class="ad-chead"><div><h3>Nhịp ôn tập của bạn</h3><small>Thời gian làm bài (cột) và điểm trung bình (đường)</small></div></div>
					<div class="ad-cbody">
						<div class="ad-legend"><span><i></i>Thời gian làm bài (phút)</span><span><i class="is-gold"></i>Điểm trung bình (%)</span></div>
						<div class="ad-chartbox" data-ad-chart aria-hidden="true"></div>
						<p class="ad-sr" data-ad-chart-sr></p>
						<div class="ad-chartnote">
							<span>Thời gian: <strong data-ad-c-time><?php echo esc_html( $ad_fmt_min( (int) ( $ad['chart']['total_minutes'] ?? 0 ) ) ); ?></strong></span>
							<span>Điểm trung bình: <strong data-ad-c-score><?php echo null !== ( $ad['chart']['avg_pct'] ?? null ) ? esc_html( rtrim( rtrim( number_format( (float) $ad['chart']['avg_pct'], 1, ',', '' ), '0' ), ',' ) . '%' ) : '—'; ?></strong></span>
							<span>Ngày có làm bài: <strong data-ad-c-days><?php echo (int) ( $ad['chart']['active_days'] ?? 0 ); ?></strong></span>
							<span>Chuỗi học: <strong><?php echo null !== $streak ? (int) $streak['current_streak'] . ' ngày' : '—'; ?></strong></span>
						</div>
					</div>
				</article>

				<article class="ad-card" id="muc-tieu">
					<div class="ad-chead"><div><h3>Mục tiêu đang theo đuổi</h3><small>Từ Hồ sơ chinh phục kỳ thi</small></div><a class="ad-btn is-sm" href="<?php echo esc_url( cvc_account_url( 'conquest' ) ); ?>">✎ Quản lý</a></div>
					<div class="ad-cbody">
						<?php if ( $goal ) : ?>
							<div class="ad-goal">
								<div class="ad-goal-k">MỤC TIÊU<?php echo 'paused' === $goal['status'] ? ' · ĐANG TẠM DỪNG' : ''; ?></div>
								<h3><?php echo esc_html( (string) $goal['title'] ); ?></h3>
								<p><?php echo null !== $goal['days_left'] ? ( $goal['days_left'] >= 0 ? esc_html( 'Còn ' . (int) $goal['days_left'] . ' ngày tới mốc ' . $ad_date( $goal['target_date'] ) ) : 'Đã qua mốc mục tiêu ' . esc_html( $ad_date( $goal['target_date'] ) ) ) : 'Chưa đặt ngày mục tiêu'; ?><?php echo ! empty( $goal['next']['title'] ) ? ' · Việc tiếp theo: ' . esc_html( (string) $goal['next']['title'] ) : ''; ?></p>
								<div class="ad-goalbar"><span style="width:<?php echo (int) $goal['path_percent']; ?>%"></span></div>
								<div class="ad-goalfoot"><span><?php echo (int) $goal['path_total'] > 0 ? esc_html( 'Lộ trình: ' . (int) $goal['path_done'] . '/' . (int) $goal['path_total'] . ' việc' ) : 'Chưa tạo lộ trình học tập'; ?></span><strong><?php echo (int) $goal['path_percent']; ?>%</strong></div>
								<a class="ad-btn" href="<?php echo esc_url( cvc_account_url( 'conquest' ) ); ?>">Xem lộ trình →</a>
							</div>
						<?php else : ?>
							<div class="ad-goal is-empty">
								<div class="ad-goal-k">CHƯA CÓ MỤC TIÊU</div>
								<h3>Chọn kỳ thi bạn muốn chinh phục</h3>
								<p>Gắn một tin tuyển dụng hoặc nhóm vị trí để nhận lộ trình ôn tập, tài liệu và đề thi phù hợp.</p>
								<a class="ad-btn" href="<?php echo esc_url( cvc_account_url( 'conquest' ) ); ?>">Tạo hồ sơ chinh phục →</a>
							</div>
						<?php endif; ?>
						<?php if ( $streak ) : ?>
							<ol class="ad-week" aria-label="Hoạt động 7 ngày gần nhất">
								<?php foreach ( (array) $streak['weekly_activity'] as $day ) : ?>
									<li class="<?php echo ! empty( $day['is_today'] ) ? 'is-today' : ( ! empty( $day['active'] ) ? 'is-done' : '' ); ?><?php echo ! empty( $day['is_today'] ) && ! empty( $day['active'] ) ? ' is-done' : ''; ?>" title="<?php echo esc_attr( ( $day['day'] ?? '' ) . ' ' . $ad_date( $day['date'] ?? null ) . ': ' . ( ! empty( $day['active'] ) ? (int) $day['activities'] . ' hoạt động' : 'chưa học' ) ); ?>"><small><?php echo esc_html( ! empty( $day['is_today'] ) ? 'Nay' : (string) ( $day['day'] ?? '' ) ); ?></small><b><?php echo esc_html( wp_date( 'd', strtotime( ( $day['date'] ?? 'now' ) . ' 12:00:00' ) ) ); ?></b><i></i></li>
								<?php endforeach; ?>
							</ol>
							<p class="ad-note">Chuỗi hiện tại <?php echo (int) $streak['current_streak']; ?> ngày · dài nhất <?php echo (int) $streak['longest_streak']; ?> ngày · <?php echo (int) $streak['active_days_last_30']; ?>/30 ngày có học.</p>
						<?php endif; ?>
					</div>
				</article>
			</section>

			<div class="ad-sect"><div><h2>Việc cần làm tiếp theo</h2><p>Ưu tiên các việc có tác động lớn đến mục tiêu của bạn.</p></div></div>
			<section class="ad-grid3">
				<article class="ad-card">
					<div class="ad-chead"><div><h3>Tiếp tục học</h3><small>Bài làm dở và nội dung gần nhất</small></div><a href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">Lịch sử →</a></div>
					<div class="ad-cbody ad-list">
						<?php foreach ( array_slice( $open, 0, 2 ) as $o ) : $p = $o['total'] ? (int) round( $o['answered'] * 100 / $o['total'] ) : 0; ?>
							<div class="ad-item"><div class="ad-itemico">▤</div><div class="ad-itemtext"><strong><?php echo esc_html( wp_trim_words( (string) $o['title'], 14 ) ); ?></strong><p><?php echo esc_html( ( 'practice' === $o['mode'] ? 'Ôn tập' : 'Thi thật' ) . ' · đã làm ' . (int) $o['answered'] . '/' . (int) $o['total'] . ' câu' ); ?></p><div class="ad-bar"><span style="width:<?php echo (int) $p; ?>%"></span></div></div><a class="ad-btn is-sm is-primary" href="<?php echo esc_url( cvc_exam_attempt_url( (int) $o['attempt_id'] ) ); ?>">Tiếp tục</a></div>
						<?php endforeach; ?>
						<?php if ( ! empty( $cont['knowledge'] ) ) : $kn = $cont['knowledge']; ?>
							<div class="ad-item"><div class="ad-itemico">§</div><div class="ad-itemtext"><strong><?php echo esc_html( (string) $kn['title'] ); ?></strong><p>Bài kiến thức xem gần nhất · <?php echo esc_html( $ad_when( $kn['viewed_at'] ?? null ) ); ?></p></div><a class="ad-btn is-sm" href="<?php echo esc_url( cvc_knowledge_item_url( (string) $kn['slug'] ) ); ?>">Học tiếp</a></div>
						<?php endif; ?>
						<?php if ( (int) ( $qs['wrong'] ?? 0 ) > 0 || (int) ( $qs['flagged'] ?? 0 ) > 0 ) : ?>
							<div class="ad-item"><div class="ad-itemico">✦</div><div class="ad-itemtext"><strong><?php echo esc_html( (int) $qs['wrong'] . ' câu làm sai · ' . (int) $qs['flagged'] . ' câu đánh dấu' ); ?></strong><p>Ôn lại đúng những câu chưa chắc chắn.</p></div><a class="ad-btn is-sm" href="<?php echo esc_url( cvc_saved_questions_url( (int) $qs['wrong'] > 0 ? 'wrong' : 'flagged' ) ); ?>">Ôn lại</a></div>
						<?php endif; ?>
						<?php if ( ! $open && empty( $cont['knowledge'] ) && empty( $qs['wrong'] ) && empty( $qs['flagged'] ) ) : ?>
							<p class="ad-empty">Bạn chưa có bài làm dở. <a href="<?php echo esc_url( cvc_question_bank_url() ); ?>">Bắt đầu với Ngân hàng câu hỏi →</a></p>
						<?php endif; ?>
					</div>
				</article>

				<article class="ad-card">
					<div class="ad-chead"><div><h3>Cơ hội tuyển dụng đã lưu</h3><small>Hạn nộp và nguồn tin</small></div><a href="<?php echo esc_url( cvc_account_url( 'bookmarks' ) ); ?>">Quản lý →</a></div>
					<div class="ad-cbody ad-list">
						<?php if ( ! $saved ) : ?>
							<p class="ad-empty">Chưa lưu tin tuyển dụng nào. <a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tìm cơ hội phù hợp →</a></p>
						<?php endif; ?>
						<?php foreach ( $saved as $r ) : ?>
							<?php
							$pill = array(
								'soon'    => array( 'is-gold', 0 === (int) $r['days_left'] ? 'Hết hạn hôm nay' : 'Còn ' . (int) $r['days_left'] . ' ngày' ),
								'open'    => array( '', 'Còn hạn' ),
								'unknown' => array( 'is-grey', 'Chưa rõ hạn nộp' ),
								'expired' => array( 'is-red', 'Đã hết hạn' ),
							)[ $r['state'] ] ?? array( '', '' );
							?>
							<div class="ad-item"><div class="ad-itemico">▣</div><div class="ad-itemtext"><strong><a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $r['title'], 16 ) ); ?></a></strong><p><?php echo $r['deadline'] ? 'Hạn nộp: ' . esc_html( $ad_date( $r['deadline'] ) ) : 'Hạn nộp: chưa rõ'; ?> · <?php echo $r['official'] ? 'Nguồn chính thức' : 'Cần đối chiếu thông báo gốc'; ?></p><span class="ad-pill <?php echo esc_attr( $pill[0] ); ?>"><?php echo esc_html( $pill[1] ); ?></span></div></div>
						<?php endforeach; ?>
						<p class="ad-hint">Chỉ báo "sắp hết hạn" khi hạn nộp lấy từ tin đang đăng. Luôn đối chiếu thông báo gốc trước khi nộp hồ sơ.</p>
					</div>
				</article>

				<article class="ad-card">
					<div class="ad-chead"><div><h3>Kiến thức cần củng cố</h3><small>Từ kết quả các câu đã làm</small></div><?php if ( $weak ) : ?><span class="ad-pill is-red"><?php echo (int) ( $k['weak']['count'] ?? count( $weak ) ); ?> chủ đề</span><?php endif; ?></div>
					<div class="ad-cbody">
						<?php if ( ! $weak ) : ?>
							<p class="ad-empty">Chưa có chủ đề nào dưới 70% (cần ít nhất 5 câu đã làm trong chủ đề). Làm thêm đề để hệ thống nhận ra điểm yếu.</p>
						<?php endif; ?>
						<?php foreach ( $weak as $t ) : ?>
							<div class="ad-topic"><div class="ad-topichead"><a href="<?php echo esc_url( cvc_topic_url( (string) $t['slug'] ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $t['name'], 12 ) ); ?></a><b><?php echo (int) $t['accuracy']; ?>%</b></div><span><?php echo esc_html( (int) $t['answered'] . ' lượt trả lời · ' . (int) $t['questions'] . ' câu' ); ?></span><div class="ad-bar"><span style="width:<?php echo max( 3, (int) $t['accuracy'] ); ?>%"></span></div></div>
						<?php endforeach; ?>
						<?php if ( (int) ( $qs['wrong'] ?? 0 ) > 0 && function_exists( 'cvc_qb_start_form' ) ) : ?>
							<p class="ad-sub">Tạo phiên ôn tập từ <?php echo (int) $qs['wrong']; ?> câu làm sai:</p>
							<?php cvc_qb_start_form( array( 'mon' => 0, 'chu-de' => 0, 'van-ban' => 0, 'dieu' => 0, 'nguon' => 'wrong', 'so-cau' => (int) $qs['wrong'] > 10 ? 20 : 10 ), cvc_account_url(), (int) $qs['wrong'], 'is-compact' ); ?>
						<?php endif; ?>
					</div>
				</article>
			</section>

			<div class="ad-sect"><div><h2>Lối tắt quản lý</h2><p>Truy cập nhanh các khu vực thường dùng.</p></div></div>
			<section class="ad-quick">
				<a class="ad-tile" href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>"><span class="ad-itemico">▤</span><span><strong>Đề thi của tôi</strong><small>Tiếp tục và xem lịch sử</small></span></a>
				<a class="ad-tile" href="<?php echo esc_url( cvc_account_url( 'bookmarks' ) ); ?>"><span class="ad-itemico">▣</span><span><strong>Tin & văn bản đã lưu</strong><small>Hạn nộp, điều khoản cần nhớ</small></span></a>
				<a class="ad-tile" href="<?php echo esc_url( cvc_saved_questions_url() ); ?>"><span class="ad-itemico">?</span><span><strong>Câu hỏi đã lưu</strong><small>Câu đánh dấu, câu sai</small></span></a>
				<a class="ad-tile" href="<?php echo esc_url( cvc_account_url( 'my-documents' ) ); ?>"><span class="ad-itemico">▧</span><span><strong>Tài liệu của tôi</strong><small>Tài liệu đã mua, đã tải</small></span></a>
			</section>

			<section class="ad-card ad-activity">
				<div class="ad-chead"><div><h3>Hoạt động gần đây</h3><small>Bài làm, nội dung đã lưu và đã xem</small></div><a class="ad-btn is-sm" href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">Xem lịch sử →</a></div>
				<div class="ad-cbody ad-list">
					<?php if ( ! $acts ) : ?><p class="ad-empty">Chưa có hoạt động nào.</p><?php endif; ?>
					<?php foreach ( $acts as $a ) : ?>
						<div class="ad-item"><div class="ad-itemico"><?php echo esc_html( array( 'exam_submitted' => '✓', 'exam_started' => '▶', 'bookmark' => '☆', 'view' => '◉' )[ $a['type'] ] ?? '•' ); ?></div><div class="ad-itemtext"><strong><?php if ( ! empty( $a['url'] ) ) : ?><a href="<?php echo esc_url( home_url( $a['url'] ) ); ?>"><?php echo esc_html( (string) $a['title'] ); ?></a><?php else : ?><?php echo esc_html( (string) $a['title'] ); ?><?php endif; ?></strong><p><?php echo esc_html( (string) $a['meta'] ); ?></p></div><time class="ad-when" datetime="<?php echo esc_attr( (string) $a['at'] ); ?>"><?php echo esc_html( $ad_when( $a['at'] ) ); ?></time></div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>
		</div>
	</div>
</div>
<div class="ad-toast" id="ad-toast" role="status" aria-live="polite"></div>
</main>
<?php
get_footer();
