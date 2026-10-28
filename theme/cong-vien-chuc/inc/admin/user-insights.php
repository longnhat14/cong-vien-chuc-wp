<?php
/**
 * Quản trị → Người dùng & hoạt động (28/10/2026, giai đoạn 1).
 * /quan-tri/nguoi-dung/ (tổng quan), ?xem=danh-sach (danh sách), /quan-tri/nguoi-dung/{id}/ (hồ sơ 360°).
 * API: GET /api/admin/user-insights/{overview,users,users/{id}} (quyền user.view). Mở hồ sơ được ghi audit log.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_UI_STATUS = array(
	'moi'           => array( 'Mới', 'bg-sky-100 text-sky-900' ),
	'dang-hoc'      => array( 'Đang học', 'bg-emerald-100 text-emerald-900' ),
	'ngu-dong'      => array( 'Ngủ đông', 'bg-amber-100 text-amber-900' ),
	'roi-bo'        => array( 'Rời bỏ', 'bg-slate-200 text-slate-700' ),
	'chua-xac-minh' => array( 'Chưa xác minh email', 'bg-rose-100 text-rose-900' ),
	'nhan-su'       => array( 'Nhân sự', 'bg-indigo-100 text-indigo-900' ),
	'thu-nghiem'    => array( 'Tài khoản thử', 'bg-slate-100 text-slate-600' ),
);

function cvc_ui_n( $n ): string {
	return null === $n ? '—' : number_format( (float) $n, 0, ',', '.' );
}

/** Ngày giờ VN từ chuỗi UTC của API. */
function cvc_ui_dt( $iso, string $fmt = 'd/m/Y H:i' ): string {
	if ( empty( $iso ) ) {
		return '—';
	}
	$ts = strtotime( (string) $iso . ( preg_match( '/[zZ]|[+-]\d\d:?\d\d$/', (string) $iso ) ? '' : ' UTC' ) );

	return $ts ? wp_date( $fmt, $ts, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) : '—';
}

function cvc_ui_ago( $iso ): string {
	if ( empty( $iso ) ) {
		return 'chưa có';
	}
	$ts = strtotime( (string) $iso . ( preg_match( '/[zZ]|[+-]\d\d:?\d\d$/', (string) $iso ) ? '' : ' UTC' ) );
	if ( ! $ts ) {
		return '—';
	}
	$d = time() - $ts;
	if ( $d < 3600 ) {
		return max( 1, (int) floor( $d / 60 ) ) . ' phút trước';
	}
	if ( $d < 86400 ) {
		return (int) floor( $d / 3600 ) . ' giờ trước';
	}

	return (int) floor( $d / 86400 ) . ' ngày trước';
}

function cvc_ui_tabs( string $cur ): void {
	$tabs = array(
		'tong-quan' => array( 'Tổng quan', cvc_admin_url( 'nguoi-dung' ) ),
		'danh-sach' => array( 'Danh sách người dùng', cvc_admin_url( 'nguoi-dung', null, array( 'xem' => 'danh-sach' ) ) ),
		'goi-y'     => array( 'Gợi ý & cảnh báo', cvc_admin_url( 'nguoi-dung', null, array( 'xem' => 'goi-y' ) ) ),
	);
	echo '<nav class="flex flex-wrap gap-2 text-sm" aria-label="Người dùng">';
	foreach ( $tabs as $k => $t ) {
		echo '<a href="' . esc_url( $t[1] ) . '" class="px-3 py-1.5 rounded-lg border ' . ( $k === $cur ? 'bg-acc text-white border-acc font-bold' : 'bg-white border-slate-300 text-ink-2 hover:bg-acc-soft' ) . '"' . ( $k === $cur ? ' aria-current="page"' : '' ) . '>' . esc_html( $t[0] ) . '</a>';
	}
	echo '</nav>';
}

function cvc_ui_styles(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	?>
	<style>
		.ui-card{border:1px solid #cbd5e1;border-radius:12px;background:#fff;padding:14px}
		.ui-card h2{font-weight:900;color:#111;font-size:16px;margin:0 0 8px}
		.ui-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
		.ui-kpi{border:1px solid #cbd5e1;border-radius:12px;background:#fff;padding:10px 12px}
		.ui-kpi small{display:block;font-size:12px;color:#475569}.ui-kpi b{display:block;font-size:22px;font-weight:900;color:#0f172a;font-variant-numeric:tabular-nums;line-height:1.2}
		.ui-kpi em{display:block;font-style:normal;font-size:12px;color:#64748b}
		.ui-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
		@media(max-width:900px){.ui-grid2{grid-template-columns:minmax(0,1fr)}}
		.ui-tbl{width:100%;border-collapse:collapse;font-size:13.5px}
		.ui-tbl th{text-align:left;font-size:12px;color:#475569;font-weight:700;padding:6px 8px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
		.ui-tbl td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top}
		.ui-tbl td.n,.ui-tbl th.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
		.ui-tbl a{color:#123a63;font-weight:700}.ui-tbl a:hover{text-decoration:underline}
		.ui-scroll{overflow-x:auto}
		.ui-bar{height:22px;border-radius:6px;background:#dbe7f3;position:relative;overflow:hidden}
		.ui-bar span{position:absolute;inset:0 auto 0 0;background:#123a63;border-radius:6px}
		.ui-heat{display:grid;grid-template-columns:34px repeat(24,minmax(12px,1fr));gap:2px;font-size:10.5px;color:#64748b;min-width:560px}
		.ui-heat i{display:block;height:16px;border-radius:3px;background:#eef2f6}
		.ui-heat .l{font-style:normal;align-self:center}
		.ui-pill{display:inline-block;font-size:11.5px;font-weight:700;padding:1px 8px;border-radius:99px;white-space:nowrap}
		.ui-tl{list-style:none;margin:0;padding:0;display:grid;gap:0}
		.ui-tl li{display:grid;grid-template-columns:112px 26px minmax(0,1fr);gap:8px;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:13.5px}
		.ui-tl time{color:#64748b;font-size:12px;font-variant-numeric:tabular-nums}
		.ui-tl .ic{width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:12px;background:#eef2f6;color:#123a63}
		.ui-tl small{display:block;color:#64748b;font-size:12px}
		.ui-cohort td.c{text-align:center;font-weight:700;font-variant-numeric:tabular-nums}
		.ui-note{font-size:12.5px;color:#64748b}
	</style>
	<?php
}

/** Ô màu theo tỷ lệ (0..1) - xanh đậm dần. */
function cvc_ui_shade( float $r ): string {
	$r = max( 0, min( 1, $r ) );
	if ( $r <= 0 ) {
		return '#eef2f6';
	}
	$l = (int) round( 92 - $r * 62 );

	return 'hsl(210 55% ' . $l . '%)';
}

function cvc_ui_heatmap( array $heat, string $unit ): void {
	$max = 0;
	foreach ( $heat as $row ) {
		$max = max( $max, max( array_map( 'intval', (array) $row ) ) );
	}
	$days = array( 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN' );
	echo '<div class="ui-scroll"><div class="ui-heat" role="img" aria-label="Hoạt động theo thứ và giờ">';
	echo '<i class="l"></i>';
	for ( $h = 0; $h < 24; $h++ ) {
		echo '<i class="l" style="background:none;height:auto;text-align:center">' . ( 0 === $h % 3 ? (int) $h : '' ) . '</i>';
	}
	foreach ( $days as $wd => $lbl ) {
		echo '<i class="l" style="background:none;height:auto">' . esc_html( $lbl ) . '</i>';
		for ( $h = 0; $h < 24; $h++ ) {
			$v = (int) ( $heat[ $wd ][ $h ] ?? 0 );
			echo '<i style="background:' . esc_attr( cvc_ui_shade( $max ? $v / $max : 0 ) ) . '" title="' . esc_attr( $lbl . ' ' . $h . 'h: ' . $v . ' ' . $unit ) . '"></i>';
		}
	}
	echo '</div></div>';
}

/** Biểu đồ cột (hoạt động) + đường (người mới) theo ngày. */
function cvc_ui_series_chart( array $series, string $bar = 'active', string $line = 'new_users', string $bar_l = 'Người hoạt động trong ngày', string $line_l = 'Người đăng ký mới' ): void {
	$n = count( $series );
	if ( ! $n ) {
		return;
	}
	$max = max( 1, max( array_map( static fn ( $d ) => max( (int) ( $d[ $bar ] ?? 0 ), (int) ( $d[ $line ] ?? 0 ) ), $series ) ) );
	$w   = 760;
	$h   = 200;
	$pl  = 34;
	$pb  = 22;
	$ch  = $h - $pb - 8;
	$bw  = ( $w - $pl - 6 ) / $n;
	$y   = static fn ( $v ) => 8 + $ch - ( $v / $max ) * $ch;
	$pts = array();
	echo '<div class="ui-scroll"><svg viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '" style="width:100%;min-width:520px;height:auto" role="img" aria-label="Người hoạt động và người mới theo ngày">';
	foreach ( array( 0, 0.5, 1 ) as $f ) {
		$v  = (int) round( $max * $f );
		$yy = $y( $v );
		echo '<line x1="' . (int) $pl . '" x2="' . (int) $w . '" y1="' . esc_attr( (string) $yy ) . '" y2="' . esc_attr( (string) $yy ) . '" stroke="#e2e8f0"/><text x="' . (int) ( $pl - 6 ) . '" y="' . esc_attr( (string) ( $yy + 4 ) ) . '" font-size="11" text-anchor="end" fill="#64748b">' . (int) $v . '</text>';
	}
	foreach ( $series as $i => $d ) {
		$x  = $pl + $i * $bw;
		$a  = (int) ( $d[ $bar ] ?? 0 );
		$yy = $y( $a );
		echo '<rect x="' . esc_attr( (string) ( $x + 1 ) ) . '" y="' . esc_attr( (string) $yy ) . '" width="' . esc_attr( (string) max( 1, $bw - 2 ) ) . '" height="' . esc_attr( (string) ( 8 + $ch - $yy ) ) . '" fill="#9cc0e3"><title>' . esc_html( wp_date( 'd/m', strtotime( $d['date'] ) ) . ': ' . $bar_l . ' ' . $a . ' · ' . $line_l . ' ' . (int) ( $d[ $line ] ?? 0 ) ) . '</title></rect>';
		$pts[] = round( $x + $bw / 2, 1 ) . ',' . round( $y( (int) ( $d[ $line ] ?? 0 ) ), 1 );
		if ( 0 === $i % max( 1, (int) ceil( $n / 8 ) ) ) {
			echo '<text x="' . esc_attr( (string) ( $x + $bw / 2 ) ) . '" y="' . (int) ( $h - 6 ) . '" font-size="11" text-anchor="middle" fill="#64748b">' . esc_html( wp_date( 'd/m', strtotime( $d['date'] ) ) ) . '</text>';
		}
	}
	echo '<polyline points="' . esc_attr( implode( ' ', $pts ) ) . '" fill="none" stroke="#c2410c" stroke-width="2"/>';
	echo '</svg></div><p class="ui-note"><span style="display:inline-block;width:10px;height:10px;background:#9cc0e3;border-radius:2px"></span> ' . esc_html( $bar_l ) . ' &nbsp; <span style="display:inline-block;width:14px;height:2px;background:#c2410c;vertical-align:middle"></span> ' . esc_html( $line_l ) . '</p>';
}

/* ================================================================== */
function cvc_admin_render_user_insights( string $item_param ): void {
	cvc_ui_styles();
	if ( ctype_digit( $item_param ) ) {
		cvc_ui_render_profile( (int) $item_param );
		return;
	}
	$xem = sanitize_key( wp_unslash( $_GET['xem'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( 'danh-sach' === $xem ) {
		cvc_ui_render_list();
		return;
	}
	if ( 'goi-y' === $xem ) {
		cvc_ui_render_advice();
		return;
	}
	cvc_ui_render_overview();
}

function cvc_ui_render_overview(): void {
	$days = absint( $_GET['ky'] ?? 30 ); // phpcs:ignore WordPress.Security.NonceVerification
	$days = in_array( $days, array( 7, 30, 90 ), true ) ? $days : 30;
	$res  = cvc_admin_api_get( '/api/admin/user-insights/overview', array( 'days' => $days ) );
	$d    = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$k    = (array) ( $d['kpi'] ?? array() );
	$pct  = static fn ( $a, $b ) => $b > 0 ? (int) round( $a / $b * 100 ) . '%' : '—';
	?>
	<header class="flex flex-wrap items-end justify-between gap-3">
		<div>
			<h1 class="text-2xl font-black text-ink">Người dùng &amp; hoạt động</h1>
			<p class="text-sm text-ink-muted">Không tính tài khoản nhân sự quản trị và tài khoản thử. Giờ theo giờ Việt Nam; số liệu làm mới mỗi 10 phút.</p>
		</div>
		<nav class="flex gap-1 text-sm" aria-label="Khoảng thời gian">
			<?php foreach ( array( 7, 30, 90 ) as $p ) : ?>
				<a href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', null, array( 'ky' => $p ) ) ); ?>" class="px-3 py-1.5 rounded-lg border <?php echo $p === $days ? 'bg-acc text-white border-acc font-bold' : 'bg-white border-slate-300'; ?>"><?php echo (int) $p; ?> ngày</a>
			<?php endforeach; ?>
		</nav>
	</header>
	<?php cvc_ui_tabs( 'tong-quan' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<section class="ui-kpis" aria-label="Chỉ số chính">
		<?php
		foreach ( array(
			array( 'Tài khoản', cvc_ui_n( $k['users_total'] ?? 0 ), '+' . cvc_ui_n( $k['users_new'] ?? 0 ) . ' trong ' . $days . ' ngày' ),
			array( 'Hoạt động trong kỳ', cvc_ui_n( $k['active_period'] ?? 0 ), 'hôm nay ' . cvc_ui_n( $k['active_today'] ?? 0 ) . ' · 7 ngày ' . cvc_ui_n( $k['active_7d'] ?? 0 ) ),
			array( 'Đã xác minh email', $pct( (int) ( $k['verified'] ?? 0 ), (int) ( $k['users_total'] ?? 0 ) ), cvc_ui_n( $k['verified'] ?? 0 ) . ' tài khoản' ),
			array( 'Đăng ký bằng Google', $pct( (int) ( $k['google'] ?? 0 ), (int) ( $k['users_total'] ?? 0 ) ), cvc_ui_n( $k['google'] ?? 0 ) . ' tài khoản' ),
			array( 'Lượt làm bài', cvc_ui_n( $k['attempts'] ?? 0 ), cvc_ui_n( $k['submitted'] ?? 0 ) . ' đã nộp · điểm TB ' . ( null !== ( $k['avg_percentage'] ?? null ) ? (int) $k['avg_percentage'] . '%' : '—' ) ),
			array( 'Tải đề PDF', cvc_ui_n( $k['pdf'] ?? 0 ), 'trong ' . $days . ' ngày' ),
			array( 'Lượt đăng nhập', cvc_ui_n( $k['logins'] ?? 0 ), ! empty( $k['login_tracking_since'] ) ? 'ghi từ ' . cvc_ui_dt( $k['login_tracking_since'], 'd/m/Y' ) : 'bắt đầu ghi từ 28/10/2026' ),
		) as $c ) :
			?>
			<div class="ui-kpi"><small><?php echo esc_html( $c[0] ); ?></small><b><?php echo esc_html( $c[1] ); ?></b><em><?php echo esc_html( $c[2] ); ?></em></div>
		<?php endforeach; ?>
	</section>

	<section class="ui-card"><h2>Hoạt động theo ngày</h2><?php cvc_ui_series_chart( (array) ( $d['series'] ?? array() ) ); ?></section>

	<?php $tm = (array) ( $k['time'] ?? array() ); ?>
	<section class="ui-card">
		<h2>Thời gian ở lại &amp; khách chưa đăng nhập</h2>
		<?php if ( empty( $tm['since'] ) ) : ?>
			<p class="ui-note">Bắt đầu đo từ 28/10/2026 (chỉ tính khi tab đang mở và có thao tác trong 3 phút gần nhất). Số liệu xuất hiện sau vài giờ.</p>
		<?php else : ?>
			<div class="ui-kpis">
				<?php
				foreach ( array(
					array( 'Thời gian học (thành viên)', cvc_ui_n( $tm['member_minutes'] ?? 0 ) . ' phút', cvc_ui_n( $tm['member_users'] ?? 0 ) . ' người' ),
					array( 'TB mỗi người mỗi ngày', null !== ( $tm['avg_minutes_per_day'] ?? null ) ? str_replace( '.', ',', (string) $tm['avg_minutes_per_day'] ) . ' phút' : '—', 'ngày có mở web' ),
					array( 'Khách (ẩn danh)', cvc_ui_n( $tm['guest_visitors'] ?? 0 ), cvc_ui_n( $tm['guest_pageviews'] ?? 0 ) . ' lượt xem trang' ),
					array( 'TB thời gian mỗi khách', null !== ( $tm['guest_avg_minutes'] ?? null ) ? str_replace( '.', ',', (string) $tm['guest_avg_minutes'] ) . ' phút' : '—', 'trong kỳ' ),
				) as $c ) :
					?>
					<div class="ui-kpi"><small><?php echo esc_html( $c[0] ); ?></small><b><?php echo esc_html( $c[1] ); ?></b><em><?php echo esc_html( $c[2] ); ?></em></div>
				<?php endforeach; ?>
			</div>
			<?php cvc_ui_series_chart( (array) ( $d['series'] ?? array() ), 'minutes', 'visitors', 'Phút học của thành viên', 'Khách ẩn danh' ); ?>
			<p class="ui-note">Đo từ <?php echo esc_html( cvc_ui_dt( (string) $tm['since'] . ' 00:00:00', 'd/m/Y' ) ); ?>. Khách được đếm theo mã ngẫu nhiên của trình duyệt, không gắn danh tính.</p>
		<?php endif; ?>
	</section>

	<div class="ui-grid2">
		<section class="ui-card">
			<h2>Phễu người đăng ký trong <?php echo (int) $days; ?> ngày</h2>
			<?php
			$f    = (array) ( $d['funnel'] ?? array() );
			$base = max( 1, (int) ( $f['registered'] ?? 0 ) );
			foreach ( array( 'registered' => 'Đăng ký', 'verified' => 'Xác minh email', 'first_attempt' => 'Làm bài đầu tiên', 'three_attempts' => 'Làm từ 3 bài', 'returned_7d' => 'Quay lại sau 7 ngày' ) as $key => $lbl ) :
				$v = (int) ( $f[ $key ] ?? 0 );
				?>
				<div class="mb-2"><div class="flex justify-between text-sm"><span><?php echo esc_html( $lbl ); ?></span><b class="tabular-nums"><?php echo esc_html( cvc_ui_n( $v ) . ' · ' . $pct( $v, $base ) ); ?></b></div><div class="ui-bar"><span style="width:<?php echo (int) round( $v / $base * 100 ); ?>%"></span></div></div>
			<?php endforeach; ?>
			<p class="ui-note">"Quay lại sau 7 ngày": có hoạt động từ ngày thứ 7 sau khi đăng ký - người mới đăng ký chưa đủ 7 ngày nên tỷ lệ này tăng dần.</p>
		</section>
		<section class="ui-card">
			<h2>Giữ chân theo tuần đăng ký</h2>
			<div class="ui-scroll"><table class="ui-tbl ui-cohort"><thead><tr><th>Tuần đăng ký</th><th class="n">Số người</th><th class="n">Tuần +1</th><th class="n">Tuần +2</th><th class="n">Tuần +4</th></tr></thead><tbody>
				<?php foreach ( array_reverse( (array) ( $d['cohorts'] ?? array() ) ) as $c ) : ?>
					<tr><td><?php echo esc_html( wp_date( 'd/m', strtotime( (string) $c['week'] ) ) ); ?></td><td class="n"><?php echo (int) $c['size']; ?></td>
						<?php foreach ( array( 'w1', 'w2', 'w4' ) as $wk ) : $v = $c[ $wk ]; ?>
							<td class="c" style="background:<?php echo esc_attr( null === $v ? '#fff' : cvc_ui_shade( (int) $v / 100 ) ); ?>;color:<?php echo null !== $v && $v > 55 ? '#fff' : '#0f172a'; ?>"><?php echo null === $v ? '·' : (int) $v . '%'; ?></td>
						<?php endforeach; ?></tr>
				<?php endforeach; ?>
			</tbody></table></div>
			<p class="ui-note">% người của tuần đó còn hoạt động ở tuần thứ 1, 2, 4 sau khi đăng ký. "·" = chưa tới tuần đó.</p>
		</section>
	</div>

	<section class="ui-card">
		<h2>Đề được làm nhiều nhất</h2>
		<div class="ui-scroll"><table class="ui-tbl"><thead><tr><th>Đề</th><th class="n">Lượt</th><th class="n">Người</th><th class="n">Luyện thi</th><th class="n">Đã nộp</th><th class="n">Bỏ dở</th><th class="n">Điểm TB</th></tr></thead><tbody>
			<?php foreach ( (array) ( $d['top']['exams'] ?? array() ) as $e ) : $ab = (int) $e['attempts'] > 0 ? $e['abandoned'] / $e['attempts'] : 0; ?>
				<tr><td><a href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) $e['title'] ); ?></a> <small class="text-ink-muted"><?php echo esc_html( (string) $e['code'] ); ?></small></td>
					<td class="n"><?php echo (int) $e['attempts']; ?></td><td class="n"><?php echo (int) $e['users']; ?></td><td class="n"><?php echo (int) $e['practice']; ?></td><td class="n"><?php echo (int) $e['submitted']; ?></td>
					<td class="n" style="<?php echo $ab >= 0.3 ? 'color:#b42318;font-weight:700' : ''; ?>"><?php echo (int) $e['abandoned']; ?></td><td class="n"><?php echo null !== $e['avg_pct'] ? (int) $e['avg_pct'] . '%' : '—'; ?></td></tr>
			<?php endforeach; ?>
		</tbody></table></div>
		<p class="ui-note">Bỏ dở = lượt chưa nộp và không có thao tác hơn 1 ngày. Tỷ lệ bỏ dở ≥ 30% tô đỏ: nên xem lại độ dài / độ khó của đề.</p>
	</section>

	<div class="ui-grid2">
		<?php
		$lists = array(
			array( 'Tin tuyển dụng được xem nhiều', 'recruitments', 'visitors', 'người xem (gồm khách)', static fn ( $r ) => cvc_recruitment_url( (string) $r['slug'] ) ),
			array( 'Đề thi tải PDF nhiều', 'pdf', 'downloads', 'lượt tải', static fn ( $r ) => 'exam' === $r['kind'] ? cvc_exam_url( (string) $r['slug'] ) : cvc_recruitment_url( (string) $r['slug'] ) ),
			array( 'Văn bản được xem nhiều', 'legal', 'views', 'lượt xem', static fn ( $r ) => cvc_legal_document_url( (string) $r['slug'] ) ),
			array( 'Trang đề được xem nhiều', 'exam_views', 'views', 'lượt xem', static fn ( $r ) => cvc_exam_url( (string) $r['slug'] ) ),
		);
		foreach ( $lists as $l ) :
			$rows = (array) ( $d['top'][ $l[1] ] ?? array() );
			?>
			<section class="ui-card">
				<h2><?php echo esc_html( $l[0] ); ?></h2>
				<?php if ( ! $rows ) : ?><p class="ui-note">Chưa có dữ liệu trong kỳ.</p><?php else : ?>
					<table class="ui-tbl"><tbody>
						<?php foreach ( $rows as $r ) : ?>
							<tr><td><a href="<?php echo esc_url( $l[4]( $r ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_trim_words( (string) $r['title'], 16 ) ); ?></a></td><td class="n" title="<?php echo esc_attr( $l[3] ); ?>"><?php echo (int) $r[ $l[2] ]; ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
					<p class="ui-note">Cột số: <?php echo esc_html( $l[3] ); ?>.</p>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>

	<div class="ui-grid2">
		<section class="ui-card">
			<h2>Trang được xem nhiều (gồm khách)</h2>
			<?php $pages = (array) ( $d['pages'] ?? array() ); ?>
			<?php if ( $pages ) : ?>
				<table class="ui-tbl"><thead><tr><th>Trang</th><th class="n">Lượt xem</th><th class="n">Thành viên</th></tr></thead><tbody>
					<?php foreach ( $pages as $pgx ) : ?><tr><td><a href="<?php echo esc_url( home_url( (string) $pgx['path'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( urldecode( (string) $pgx['path'] ) ); ?></a></td><td class="n"><?php echo (int) $pgx['views']; ?></td><td class="n"><?php echo (int) $pgx['member_views']; ?></td></tr><?php endforeach; ?>
				</tbody></table>
			<?php else : ?><p class="ui-note">Bắt đầu đo từ 28/10/2026.</p><?php endif; ?>
		</section>
		<section class="ui-card">
			<h2>Từ khóa tìm kiếm</h2>
			<?php $sq = (array) ( $d['searches'] ?? array() ); $zq = (array) ( $d['zero_searches'] ?? array() ); ?>
			<?php if ( $sq ) : ?>
				<table class="ui-tbl"><thead><tr><th>Từ khóa</th><th class="n">Lượt</th><th class="n">Kết quả</th></tr></thead><tbody>
					<?php foreach ( $sq as $x ) : ?><tr><td><a href="<?php echo esc_url( add_query_arg( 's', rawurlencode( (string) $x['q'] ), home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) $x['q'] ); ?></a></td><td class="n"><?php echo (int) $x['n']; ?></td><td class="n" style="<?php echo 0 === (int) $x['results'] ? 'color:#b42318;font-weight:700' : ''; ?>"><?php echo (int) $x['results']; ?></td></tr><?php endforeach; ?>
				</tbody></table>
			<?php else : ?><p class="ui-note">Bắt đầu ghi từ 28/10/2026 (trang kết quả tìm kiếm, không tính gợi ý khi đang gõ).</p><?php endif; ?>
			<?php if ( $zq ) : ?>
				<p class="text-sm font-bold mt-3" style="color:#b42318">Tìm mà không ra kết quả - nội dung còn thiếu</p>
				<p class="text-sm"><?php echo esc_html( implode( ' · ', array_map( static fn ( $x ) => $x['q'] . ' (' . $x['n'] . ')', $zq ) ) ); ?></p>
			<?php endif; ?>
		</section>
	</div>

	<section class="ui-card">
		<h2>Nguồn đến của người đăng ký trong kỳ</h2>
		<?php $srcs = (array) ( $d['sources'] ?? array() ); ?>
		<?php if ( $srcs ) : ?><p class="text-sm"><?php echo esc_html( implode( ' · ', array_map( static fn ( $k2, $v2 ) => $k2 . ': ' . $v2, array_keys( $srcs ), $srcs ) ) ); ?></p><?php endif; ?>
		<p class="ui-note">Ghi lần đầu người dùng vào web (utm_source / trang giới thiệu như google.com, zalo.me, facebook.com…), áp dụng cho tài khoản đăng ký từ 28/10/2026.</p>
	</section>

	<section class="ui-card">
		<h2>Người dùng học vào lúc nào</h2>
		<?php cvc_ui_heatmap( (array) ( $d['heatmap'] ?? array() ), 'người-ngày' ); ?>
		<p class="ui-note">Mỗi ô = số lượt "người · ngày" có hoạt động trong giờ đó (đăng nhập, xem, làm bài, tải, lưu). Ô càng đậm càng đông - nên gửi email nhắc ôn và đăng tin vào khung giờ đậm.</p>
	</section>

	<div class="ui-grid2">
		<section class="ui-card">
			<h2>Tỉnh, thành</h2>
			<?php $pl = (array) ( $d['provinces']['login'] ?? array() ); $pg = (array) ( $d['provinces']['goal'] ?? array() ); ?>
			<p class="text-sm font-bold">Theo nơi đăng nhập (IP)</p>
			<?php if ( $pl ) : ?><ul class="text-sm"><?php foreach ( $pl as $name => $n ) : ?><li class="flex justify-between border-b border-slate-100 py-1"><span><?php echo esc_html( (string) $name ); ?></span><b><?php echo (int) $n; ?></b></li><?php endforeach; ?></ul><?php else : ?><p class="ui-note">Bắt đầu ghi từ 28/10/2026 - sẽ có số liệu sau các lần đăng nhập tới.</p><?php endif; ?>
			<p class="text-sm font-bold mt-3">Theo tỉnh mục tiêu dự tuyển</p>
			<?php if ( $pg ) : ?><ul class="text-sm"><?php foreach ( $pg as $name => $n ) : ?><li class="flex justify-between border-b border-slate-100 py-1"><span><?php echo esc_html( (string) $name ); ?></span><b><?php echo (int) $n; ?></b></li><?php endforeach; ?></ul><?php else : ?><p class="ui-note">Chưa có người dùng đặt mục tiêu.</p><?php endif; ?>
		</section>
		<section class="ui-card">
			<h2>Nhóm nghề mục tiêu &amp; cách đăng nhập</h2>
			<?php foreach ( (array) ( $d['families'] ?? array() ) as $fm ) : ?><div class="flex justify-between text-sm border-b border-slate-100 py-1"><span><?php echo esc_html( (string) $fm['label'] ); ?></span><b><?php echo (int) $fm['n']; ?></b></div><?php endforeach; ?>
			<?php
			$lab = array( 'password' => 'Email + mật khẩu', 'google' => 'Google', 'register' => 'Đăng ký (email)', 'google_register' => 'Đăng ký (Google)', 'mobile' => 'Điện thoại', 'tablet' => 'Máy tính bảng', 'desktop' => 'Máy tính' );
			foreach ( array( 'method' => 'Phương thức', 'device' => 'Thiết bị', 'browser' => 'Trình duyệt' ) as $key => $ttl ) :
				$rows = (array) ( $d['logins'][ $key ] ?? array() );
				?>
				<p class="text-sm font-bold mt-3"><?php echo esc_html( $ttl ); ?></p>
				<?php if ( $rows ) : ?><p class="text-sm"><?php echo esc_html( implode( ' · ', array_map( static fn ( $kk, $vv ) => ( $lab[ $kk ] ?? $kk ) . ' ' . $vv, array_keys( $rows ), $rows ) ) ); ?></p><?php else : ?><p class="ui-note">Đang thu thập (từ 28/10/2026).</p><?php endif; ?>
			<?php endforeach; ?>
		</section>
	</div>
	<?php
}

/* ================================================================== */
function cvc_ui_render_list(): void {
	$st   = sanitize_key( wp_unslash( $_GET['trang-thai'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$q    = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$sort = sanitize_key( wp_unslash( $_GET['sap-xep'] ?? 'last_seen' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$pg   = max( 1, absint( $_GET['trang'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$res  = cvc_admin_api_get( '/api/admin/user-insights/users', array( 'status' => isset( CVC_UI_STATUS[ $st ] ) ? $st : null, 'q' => $q, 'sort' => $sort, 'page' => $pg, 'per_page' => 30 ) );
	$d    = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$cnt  = (array) ( $d['counts'] ?? array() );
	$pgn  = (array) ( $d['pagination'] ?? array() );
	$url  = static fn ( array $o = array() ): string => cvc_admin_url( 'nguoi-dung', null, array_filter( array_merge( array( 'xem' => 'danh-sach', 'trang-thai' => $st, 'q' => $q, 'sap-xep' => 'last_seen' !== $sort ? $sort : '' ), $o ), static fn ( $v, $k ) => '' !== (string) $v && ! ( 'trang' === $k && 1 === (int) $v ), ARRAY_FILTER_USE_BOTH ) );
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Người dùng &amp; hoạt động</h1>
		<p class="text-sm text-ink-muted">Trạng thái: Mới (đăng ký ≤ 7 ngày) · Đang học (hoạt động ≤ 14 ngày) · Ngủ đông (15–30 ngày) · Rời bỏ (&gt; 30 ngày).</p>
	</header>
	<?php cvc_ui_tabs( 'danh-sach' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>
	<nav class="flex flex-wrap gap-2 text-sm" aria-label="Lọc trạng thái">
		<a href="<?php echo esc_url( $url( array( 'trang-thai' => '' ) ) ); ?>" class="px-3 py-1 rounded-full border <?php echo '' === $st ? 'bg-acc text-white border-acc' : 'bg-white border-slate-300'; ?>">Tất cả <b><?php echo (int) ( $cnt['all'] ?? 0 ); ?></b></a>
		<?php foreach ( CVC_UI_STATUS as $k => $s ) : ?>
			<a href="<?php echo esc_url( $url( array( 'trang-thai' => $k ) ) ); ?>" class="px-3 py-1 rounded-full border <?php echo $k === $st ? 'bg-acc text-white border-acc' : 'bg-white border-slate-300'; ?>"><?php echo esc_html( $s[0] ); ?> <b><?php echo (int) ( $cnt[ $k ] ?? 0 ); ?></b></a>
		<?php endforeach; ?>
	</nav>
	<form method="get" action="<?php echo esc_url( cvc_admin_url( 'nguoi-dung' ) ); ?>" class="flex flex-wrap gap-2 items-center text-sm">
		<input type="hidden" name="xem" value="danh-sach"><?php if ( '' !== $st ) : ?><input type="hidden" name="trang-thai" value="<?php echo esc_attr( $st ); ?>"><?php endif; ?>
		<label class="sr-only" for="ui-q">Tìm tên hoặc email</label>
		<input id="ui-q" type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Tìm tên hoặc email" class="border border-slate-300 rounded-lg px-3 py-1.5 w-64 max-w-full">
		<label for="ui-sort">Sắp xếp</label>
		<select id="ui-sort" name="sap-xep" class="border border-slate-300 rounded-lg px-2 py-1.5">
			<?php foreach ( array( 'last_seen' => 'Hoạt động gần nhất', 'registered' => 'Đăng ký mới nhất', 'attempts' => 'Làm bài nhiều nhất', 'logins' => 'Đăng nhập nhiều nhất', 'score' => 'Điểm TB cao nhất', 'minutes' => 'Thời gian 30 ngày nhiều nhất' ) as $k => $lbl ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $sort, $k ); ?>><?php echo esc_html( $lbl ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="px-3 py-1.5 rounded-lg bg-acc text-white font-bold" type="submit">Lọc</button>
	</form>
	<section class="ui-card ui-scroll">
		<table class="ui-tbl">
			<thead><tr><th>Người dùng</th><th>Trạng thái</th><th>Hoạt động gần nhất</th><th class="n">Đăng nhập</th><th class="n">Phút (30 ngày)</th><th class="n">Bài làm</th><th class="n">Điểm TB</th><th class="n">Xem</th><th class="n">Tải PDF</th><th>Tỉnh / mục tiêu</th><th>Đăng ký</th></tr></thead>
			<tbody>
				<?php foreach ( (array) ( $d['items'] ?? array() ) as $u ) : $s = CVC_UI_STATUS[ $u['staff'] ? 'nhan-su' : ( ! empty( $u['test'] ) ? 'thu-nghiem' : $u['status'] ) ] ?? array( '', '' ); ?>
					<tr>
						<td><a href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', (int) $u['id'] ) ); ?>"><?php echo esc_html( '' !== trim( (string) $u['name'] ) ? (string) $u['name'] : '(chưa đặt tên)' ); ?></a><small class="block text-ink-muted"><?php echo esc_html( (string) $u['email'] ); ?><?php echo $u['google'] ? ' · Google' : ''; ?><?php echo $u['verified'] ? '' : ' · chưa xác minh'; ?></small></td>
						<td><span class="ui-pill <?php echo esc_attr( $s[1] ); ?>"><?php echo esc_html( $s[0] ); ?></span></td>
						<td title="<?php echo esc_attr( cvc_ui_dt( $u['last_seen'] ) ); ?>"><?php echo esc_html( cvc_ui_ago( $u['last_seen'] ) ); ?></td>
						<td class="n"><?php echo (int) $u['logins']; ?></td>
						<td class="n"><?php echo null !== ( $u['minutes_30d'] ?? null ) ? (int) $u['minutes_30d'] : '—'; ?></td>
						<td class="n"><?php echo (int) $u['attempts']; ?><?php echo (int) $u['attempts'] > (int) $u['submitted'] ? '<small class="text-ink-muted"> (' . (int) $u['submitted'] . ' nộp)</small>' : ''; ?></td>
						<td class="n"><?php echo null !== $u['avg_pct'] ? (int) $u['avg_pct'] . '%' : '—'; ?></td>
						<td class="n"><?php echo (int) $u['views']; ?></td>
						<td class="n"><?php echo (int) $u['downloads']; ?></td>
						<td><?php echo esc_html( (string) ( $u['goal'] ?: ( $u['province'] ?: '—' ) ) ); ?></td>
						<td><?php echo esc_html( cvc_ui_dt( $u['created_at'], 'd/m/Y' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $d['items'] ) ) : ?><tr><td colspan="11" class="ui-note">Không có người dùng khớp bộ lọc.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</section>
	<?php $last = (int) ( $pgn['last_page'] ?? 1 ); if ( $last > 1 ) : ?>
		<nav class="flex items-center gap-3 text-sm" aria-label="Phân trang">
			<?php if ( $pg > 1 ) : ?><a class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white" href="<?php echo esc_url( $url( array( 'trang' => $pg - 1 ) ) ); ?>">← Trước</a><?php endif; ?>
			<span>Trang <?php echo (int) $pg; ?>/<?php echo (int) $last; ?> · <?php echo (int) ( $pgn['total'] ?? 0 ); ?> người</span>
			<?php if ( $pg < $last ) : ?><a class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white" href="<?php echo esc_url( $url( array( 'trang' => $pg + 1 ) ) ); ?>">Sau →</a><?php endif; ?>
		</nav>
	<?php endif; ?>
	<?php
}

/* ================================================================== */
function cvc_ui_render_profile( int $id ): void {
	$res = cvc_admin_api_get( '/api/admin/user-insights/users/' . $id );
	$d   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$u   = (array) ( $d['user'] ?? array() );
	$s   = (array) ( $d['stats'] ?? array() );
	?>
	<p class="text-sm"><a class="text-acc font-bold" href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', null, array( 'xem' => 'danh-sach' ) ) ); ?>">← Danh sách người dùng</a></p>
	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( 404 === (int) $res['status'] ? 'Không tìm thấy người dùng.' : cvc_admin_error_message( $res ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>
	<header class="ui-card">
		<h1 class="text-2xl font-black text-ink"><?php echo esc_html( '' !== trim( (string) ( $u['name'] ?? '' ) ) ? (string) $u['name'] : '(chưa đặt tên)' ); ?></h1>
		<p class="text-sm text-ink-2"><?php echo esc_html( (string) ( $u['email'] ?? '' ) ); ?></p>
		<p class="flex flex-wrap gap-2 mt-2">
			<?php if ( ! empty( $u['google'] ) ) : ?><span class="ui-pill bg-sky-100 text-sky-900">Google</span><?php endif; ?>
			<span class="ui-pill <?php echo ! empty( $u['verified_at'] ) ? 'bg-emerald-100 text-emerald-900' : 'bg-rose-100 text-rose-900'; ?>"><?php echo ! empty( $u['verified_at'] ) ? 'Đã xác minh email' : 'Chưa xác minh email'; ?></span>
			<?php foreach ( (array) ( $u['roles'] ?? array() ) as $r ) : ?><?php if ( 'USER' !== $r ) : ?><span class="ui-pill bg-indigo-100 text-indigo-900"><?php echo esc_html( (string) $r ); ?></span><?php endif; ?><?php endforeach; ?>
		</p>
		<p class="ui-note mt-2">Đăng ký <?php echo esc_html( cvc_ui_dt( $u['created_at'] ?? '' ) ); ?> · phiên đăng nhập dùng lần cuối <?php echo esc_html( cvc_ui_ago( $u['last_used_at'] ?? '' ) ); ?><?php if ( ! empty( $d['goal'] ) ) : $g = (array) $d['goal']; ?> · Mục tiêu: <?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $g['title'] ?? '' ), (string) ( $g['family'] ?? '' ), (string) ( $g['province'] ?? '' ) ) ) ) ); ?><?php endif; ?></p>
		<?php $sg = (array) ( $d['signup'] ?? array() ); if ( ! empty( $sg['source'] ) || ! empty( $sg['landing'] ) ) : ?><p class="ui-note">Nguồn đến khi đăng ký: <?php echo esc_html( (string) ( $sg['source'] ?? '—' ) ); ?><?php echo ! empty( $sg['landing'] ) ? ' · trang đầu tiên ' . esc_html( (string) $sg['landing'] ) : ''; ?></p><?php endif; ?>
		<p class="ui-note">Lượt xem hồ sơ này đã được ghi vào nhật ký quản trị.</p>
	</header>

	<section class="ui-kpis" aria-label="Thống kê">
		<?php
		foreach ( array(
			array( 'Lượt đăng nhập', cvc_ui_n( $s['logins'] ?? 0 ), cvc_ui_n( $s['logins_30d'] ?? 0 ) . ' trong 30 ngày (ghi từ 28/10)' ),
			array( 'Ngày có hoạt động', cvc_ui_n( $s['active_days_90'] ?? 0 ), 'trong 90 ngày' ),
			array( 'Bài đã làm', cvc_ui_n( $s['attempts'] ?? 0 ), cvc_ui_n( $s['practice'] ?? 0 ) . ' luyện thi · ' . cvc_ui_n( $s['submitted'] ?? 0 ) . ' đã nộp' ),
			array( 'Điểm trung bình', null !== ( $s['avg_pct'] ?? null ) ? (int) $s['avg_pct'] . '%' : '—', 'cao nhất ' . ( null !== ( $s['best_pct'] ?? null ) ? (int) $s['best_pct'] . '%' : '—' ) ),
			array( 'Câu đã trả lời', cvc_ui_n( $s['answers'] ?? 0 ), ( (int) ( $s['answers'] ?? 0 ) > 0 ? (int) round( (int) $s['correct'] / (int) $s['answers'] * 100 ) . '% đúng' : '' ) ),
			array( 'Thời gian làm bài', cvc_ui_n( $s['study_minutes'] ?? 0 ) . ' phút', 'ước tính từ các lượt làm bài' ),
			array( 'Thời gian trên web', cvc_ui_n( $s['site_minutes_90'] ?? 0 ) . ' phút', cvc_ui_n( $s['site_days_90'] ?? 0 ) . ' ngày · ' . cvc_ui_n( $s['pageviews_90'] ?? 0 ) . ' trang (90 ngày, đo từ 28/10)' ),
			array( 'Xem / Tải PDF / Lưu', cvc_ui_n( $s['views'] ?? 0 ) . ' / ' . cvc_ui_n( $s['downloads'] ?? 0 ) . ' / ' . cvc_ui_n( $s['bookmarks'] ?? 0 ), cvc_ui_n( $s['emails'] ?? 0 ) . ' email đã nhận' ),
		) as $c ) :
			?>
			<div class="ui-kpi"><small><?php echo esc_html( $c[0] ); ?></small><b><?php echo esc_html( (string) $c[1] ); ?></b><em><?php echo esc_html( (string) $c[2] ); ?></em></div>
		<?php endforeach; ?>
	</section>

	<?php
	$dm = (array) ( $d['daily_minutes'] ?? array() );
	if ( $dm && array_sum( $dm ) > 0 ) {
		echo '<section class="ui-card"><h2>Phút trên web theo ngày (30 ngày)</h2>';
		cvc_ui_series_chart( array_map( static fn ( $day, $m ) => array( 'date' => $day, 'm' => $m, 'z' => 0 ), array_keys( $dm ), $dm ), 'm', 'z', 'Phút trên web', '—' );
		echo '</section>';
	}
	?>
	<div class="ui-grid2">
		<section class="ui-card">
			<h2>Học vào lúc nào (90 ngày)</h2>
			<?php cvc_ui_heatmap( (array) ( $d['heatmap'] ?? array() ), 'ngày' ); ?>
			<?php $dev = (array) ( $d['devices'] ?? array() ); ?>
			<h2 class="mt-3">Thiết bị đăng nhập</h2>
			<?php if ( $dev ) : ?>
				<ul class="text-sm"><?php foreach ( $dev as $x ) : $x = (array) $x; ?><li class="flex justify-between border-b border-slate-100 py-1"><span><?php echo esc_html( implode( ' · ', array_filter( array( array( 'mobile' => 'Điện thoại', 'tablet' => 'Máy tính bảng', 'desktop' => 'Máy tính' )[ $x['device'] ] ?? $x['device'], $x['os'], $x['browser'] ) ) ) ); ?></span><b><?php echo (int) $x['n']; ?></b></li><?php endforeach; ?></ul>
			<?php else : ?><p class="ui-note">Chưa có (bắt đầu ghi từ 28/10/2026).</p><?php endif; ?>
		</section>
		<section class="ui-card">
			<h2>Chủ đề còn yếu</h2>
			<?php $weak = (array) ( $d['weak_topics'] ?? array() ); ?>
			<?php if ( $weak ) : ?>
				<?php foreach ( $weak as $w ) : ?><div class="mb-2"><div class="flex justify-between gap-2 text-sm"><span><?php echo esc_html( (string) $w['name'] ); ?></span><b class="tabular-nums"><?php echo (int) $w['accuracy']; ?>% · <?php echo (int) $w['answered']; ?> câu</b></div><div class="ui-bar"><span style="width:<?php echo (int) $w['accuracy']; ?>%;background:#c2410c"></span></div></div><?php endforeach; ?>
			<?php else : ?><p class="ui-note">Chưa đủ dữ liệu để xác định chủ đề yếu.</p><?php endif; ?>
		</section>
	</div>

	<section class="ui-card ui-scroll">
		<h2>Bài đã làm</h2>
		<table class="ui-tbl">
			<thead><tr><th>Đề</th><th>Chế độ</th><th>Bắt đầu</th><th class="n">Thời gian</th><th class="n">Kết quả</th></tr></thead>
			<tbody>
				<?php foreach ( (array) ( $d['attempts'] ?? array() ) as $a ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( cvc_exam_url( (string) $a['exam']['slug'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) $a['exam']['title'] ); ?></a></td>
						<td><?php echo 'practice' === $a['mode'] ? 'Luyện thi' : 'Thi thử'; ?><?php echo ! empty( $a['preview'] ) ? ' · làm thử' : ''; ?></td>
						<td><?php echo esc_html( cvc_ui_dt( $a['started_at'] ) ); ?></td>
						<td class="n"><?php echo null !== $a['minutes'] ? (int) $a['minutes'] . ' phút' : '—'; ?></td>
						<td class="n"><?php echo 'in_progress' === $a['status'] ? '<span class="text-amber-700">Đang làm (' . (int) $a['answered'] . '/' . (int) $a['total'] . ')</span>' : esc_html( (int) $a['correct'] . '/' . (int) $a['total'] . ( null !== $a['pct'] ? ' · ' . (int) $a['pct'] . '%' : '' ) . ( 'timeout' === $a['by'] ? ' · hết giờ' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $d['attempts'] ) ) : ?><tr><td colspan="5" class="ui-note">Chưa làm bài nào.</td></tr><?php endif; ?>
			</tbody>
		</table>
	</section>

	<section class="ui-card">
		<h2>Dòng thời gian hoạt động</h2>
		<?php $icons = array( 'login' => '⇥', 'view' => '◉', 'attempt' => '✎', 'submit' => '✓', 'download' => '⇩', 'save' => '♥', 'email' => '✉' ); ?>
		<ol class="ui-tl">
			<?php foreach ( (array) ( $d['timeline'] ?? array() ) as $e ) : ?>
				<li>
					<time datetime="<?php echo esc_attr( (string) $e['at'] ); ?>"><?php echo esc_html( cvc_ui_dt( $e['at'] ) ); ?></time>
					<span class="ic" aria-hidden="true"><?php echo esc_html( $icons[ $e['kind'] ] ?? '•' ); ?></span>
					<span><?php if ( ! empty( $e['url'] ) ) : ?><a class="text-acc font-bold" href="<?php echo esc_url( home_url( (string) $e['url'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( (string) $e['text'] ); ?></a><?php else : ?><?php echo esc_html( (string) $e['text'] ); ?><?php endif; ?><?php if ( ! empty( $e['meta'] ) ) : ?><small><?php echo esc_html( (string) $e['meta'] ); ?></small><?php endif; ?></span>
				</li>
			<?php endforeach; ?>
			<?php if ( empty( $d['timeline'] ) ) : ?><li><span></span><span></span><span class="ui-note">Chưa có hoạt động.</span></li><?php endif; ?>
		</ol>
	</section>
	<?php
}

/* ================================================================== */
/* Gợi ý & cảnh báo (giai đoạn 3)                                      */
/* ================================================================== */
add_action( 'admin_post_cvc_admin_insights', 'cvc_admin_handle_insights' );
add_action( 'admin_post_nopriv_cvc_admin_insights', 'cvc_admin_handle_insights' );
function cvc_admin_handle_insights(): void {
	check_admin_referer( 'cvc_admin_insights' );
	$token = cvc_admin_require_staff();
	$back  = cvc_admin_url( 'nguoi-dung', null, array( 'xem' => 'goi-y' ) );
	if ( ! cvc_admin_can( 'user.view' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền.' );
		return;
	}
	$res = ( new CVC_Api_Client( null, 60 ) )->post( '/api/admin/user-insights/weekly-report/test', array(), $token );
	cvc_admin_redirect( $back, $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Đã gửi.' ) : cvc_admin_error_message( $res ) );
}

function cvc_ui_section_head( string $title, string $why ): void {
	echo '<h2>' . esc_html( $title ) . '</h2><p class="ui-note" style="margin:-4px 0 8px">' . esc_html( $why ) . '</p>';
}

function cvc_ui_render_advice(): void {
	$days = absint( $_GET['ky'] ?? 30 ); // phpcs:ignore WordPress.Security.NonceVerification
	$days = in_array( $days, array( 7, 30, 90 ), true ) ? $days : 30;
	$res  = cvc_admin_api_get( '/api/admin/user-insights/advice', array( 'days' => $days ) );
	$d    = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$g    = (array) ( $d['gaps'] ?? array() );
	$q    = (array) ( $d['quality'] ?? array() );
	$c    = (array) ( $d['churn'] ?? array() );
	$an   = (array) ( $d['anomalies'] ?? array() );
	$sum  = (array) ( $c['summary'] ?? array() );
	$here = cvc_admin_url( 'nguoi-dung', null, array( 'xem' => 'goi-y', 'ky' => $days ) );
	$can_rec = cvc_admin_can( 'recruitment.update' );
	?>
	<header class="flex flex-wrap items-end justify-between gap-3">
		<div>
			<h1 class="text-2xl font-black text-ink">Người dùng &amp; hoạt động</h1>
			<p class="text-sm text-ink-muted">Gợi ý việc nên làm rút ra từ số liệu thật. Không tính nhân sự và tài khoản thử; làm mới mỗi 10 phút.</p>
		</div>
		<div class="flex flex-wrap gap-2 items-center text-sm">
			<?php foreach ( array( 7, 30, 90 ) as $p ) : ?>
				<a href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', null, array( 'xem' => 'goi-y', 'ky' => $p ) ) ); ?>" class="px-3 py-1.5 rounded-lg border <?php echo $p === $days ? 'bg-acc text-white border-acc font-bold' : 'bg-white border-slate-300'; ?>"><?php echo (int) $p; ?> ngày</a>
			<?php endforeach; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_admin_insights' ); ?><input type="hidden" name="action" value="cvc_admin_insights">
				<button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white font-bold">✉ Gửi thử báo cáo tuần cho tôi</button>
			</form>
		</div>
	</header>
	<?php cvc_ui_tabs( 'goi-y' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>
	<p class="ui-note">Báo cáo tuần tự gửi email cho quản trị viên (SUPER_ADMIN, ADMIN) lúc 07:47 sáng thứ Hai.</p>

	<section class="ui-kpis" aria-label="Mức tương tác 14 ngày">
		<?php
		foreach ( array(
			array( 'Hoạt động 7 ngày', $sum['active_7d'] ?? 0, 'người' ),
			array( 'Tương tác cao (≥ 50 điểm)', $sum['engaged'] ?? 0, 'học đều trong 14 ngày' ),
			array( 'Tương tác vừa (20–49)', $sum['casual'] ?? 0, '' ),
			array( 'Tương tác thấp (< 20)', $sum['light'] ?? 0, 'ghé qua 1–2 lần' ),
			array( 'Sắp rời bỏ', $sum['at_risk'] ?? 0, 'học đều nhưng 7 ngày nay không vào' ),
			array( 'Đăng ký chưa làm bài', $sum['no_attempt'] ?? 0, '14 ngày gần đây' ),
		) as $k ) :
			?>
			<div class="ui-kpi"><small><?php echo esc_html( $k[0] ); ?></small><b><?php echo esc_html( cvc_ui_n( $k[1] ) ); ?></b><em><?php echo esc_html( $k[2] ); ?></em></div>
		<?php endforeach; ?>
	</section>
	<p class="ui-note">Điểm tương tác 0–100 trong 14 ngày = số ngày có hoạt động × 9 + số lượt làm bài (tối đa 10) × 4 + phút trên web ÷ 10 (tối đa 30).</p>

	<section class="ui-card">
		<?php cvc_ui_section_head( '1. Khoảng trống nội dung: tin xem nhiều nhưng chưa có đề riêng', 'Bật "Tạo đề riêng" để tin có ngay đề trắc nghiệm theo vị trí (hệ thống dựng trong khoảng 30 phút). Tin xét tuyển thường không thi trắc nghiệm nên xếp sau.' ); ?>
		<?php $recs = (array) ( $g['recruitments'] ?? array() ); ?>
		<?php if ( ! $recs ) : ?><p class="ui-note">Không có tin đang nhận hồ sơ nào thiếu đề riêng mà được xem nhiều.</p><?php else : ?>
			<div class="ui-scroll"><table class="ui-tbl"><thead><tr><th>Tin tuyển dụng</th><th class="n">Người xem</th><th>Hạn nộp</th><th>Trạng thái đề</th><th></th></tr></thead><tbody>
				<?php foreach ( $recs as $r ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_trim_words( (string) $r['title'], 22 ) ); ?></a><?php if ( ! empty( $r['selection'] ) ) : ?> <span class="ui-pill bg-slate-100 text-slate-600">Xét tuyển</span><?php endif; ?></td>
						<td class="n"><?php echo (int) $r['visitors']; ?></td>
						<td><?php echo esc_html( ! empty( $r['deadline'] ) ? wp_date( 'd/m/Y', strtotime( (string) $r['deadline'] ) ) : '—' ); ?></td>
						<td><?php echo 'on' === $r['mode'] ? '<span class="ui-pill bg-amber-100 text-amber-900">Đang tạo</span>' : '<span class="ui-pill bg-slate-100 text-slate-600">Chưa tạo</span>'; ?></td>
						<td>
							<?php if ( $can_rec && 'on' !== $r['mode'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'cvc_admin_personal_exams' ); ?>
									<input type="hidden" name="action" value="cvc_admin_personal_exams"><input type="hidden" name="op" value="mode"><input type="hidden" name="mode" value="on">
									<input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>"><input type="hidden" name="back" value="<?php echo esc_url( $here ); ?>">
									<button type="submit" class="px-2.5 py-1 rounded-lg bg-acc text-white text-xs font-bold whitespace-nowrap">Tạo đề riêng</button>
								</form>
							<?php else : ?><a class="text-xs" href="<?php echo esc_url( cvc_admin_url( 'recruitments', (int) $r['id'] ) . '#de-ca-nhan-hoa' ); ?>">Mở tin</a><?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody></table></div>
		<?php endif; ?>
		<?php $zs = (array) ( $g['zero_searches'] ?? array() ); $fs = (array) ( $g['few_searches'] ?? array() ); ?>
		<h2 class="mt-4">Từ khóa tìm không ra (hoặc rất ít) kết quả</h2>
		<?php if ( $zs || $fs ) : ?>
			<?php if ( $zs ) : ?><p class="text-sm"><b style="color:#b42318">0 kết quả:</b> <?php echo esc_html( implode( ' · ', array_map( static fn ( $x ) => $x['q'] . ' (' . $x['n'] . ')', $zs ) ) ); ?></p><?php endif; ?>
			<?php if ( $fs ) : ?><p class="text-sm"><b>1–2 kết quả:</b> <?php echo esc_html( implode( ' · ', array_map( static fn ( $x ) => $x['q'] . ' (' . $x['n'] . ' lượt)', $fs ) ) ); ?></p><?php endif; ?>
			<p class="ui-note">Nạp văn bản / tin còn thiếu, hoặc thêm từ viết tắt vào bộ tìm kiếm cho những từ khóa này.</p>
		<?php else : ?><p class="ui-note">Chưa có (ghi từ 28/10/2026).</p><?php endif; ?>
	</section>

	<section class="ui-card">
		<?php cvc_ui_section_head( '2. Chất lượng đề thi', 'Đề có ít nhất 3 lượt làm trong kỳ. Đề bị bỏ dở nhiều thường quá dài hoặc khó ngay từ đầu; đề quá dễ / quá khó nên trộn lại độ khó.' ); ?>
		<?php
		$blocks = array(
			array( 'Bỏ dở nhiều (≥ 30%)', (array) ( $q['abandon'] ?? array() ), static fn ( $e ) => $e['abandon_rate'] . '% bỏ dở · ' . $e['attempts'] . ' lượt' ),
			array( 'Quá khó (điểm TB ≤ 45%)', (array) ( $q['too_hard'] ?? array() ), static fn ( $e ) => 'điểm TB ' . $e['avg_pct'] . '% · ' . $e['submitted'] . ' bài nộp' ),
			array( 'Quá dễ (điểm TB ≥ 92%)', (array) ( $q['too_easy'] ?? array() ), static fn ( $e ) => 'điểm TB ' . $e['avg_pct'] . '% · ' . $e['submitted'] . ' bài nộp' ),
		);
		foreach ( $blocks as $b ) :
			?>
			<p class="text-sm font-bold mt-2"><?php echo esc_html( $b[0] ); ?></p>
			<?php if ( ! $b[1] ) : ?><p class="ui-note">Không có.</p><?php else : ?>
				<ul class="text-sm"><?php foreach ( $b[1] as $e ) : ?><li class="flex justify-between gap-3 border-b border-slate-100 py-1"><a class="text-acc font-bold" href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_trim_words( (string) $e['title'], 18 ) ); ?></a><span class="whitespace-nowrap text-ink-muted"><?php echo esc_html( $b[2]( $e ) ); ?></span></li><?php endforeach; ?></ul>
			<?php endif; ?>
		<?php endforeach; ?>
		<p class="text-sm font-bold mt-3">Câu bị trả lời sai nhiều nhất (≥ 6 lượt, sai ≥ 60%)</p>
		<?php $hq = (array) ( $q['hard_questions'] ?? array() ); ?>
		<?php if ( ! $hq ) : ?><p class="ui-note">Không có.</p><?php else : ?>
			<table class="ui-tbl"><thead><tr><th>Câu hỏi</th><th class="n">Lượt</th><th class="n">Sai</th><th class="n">Báo sai</th></tr></thead><tbody>
				<?php foreach ( $hq as $x ) : ?><tr><td><a href="<?php echo esc_url( cvc_admin_url( 'questions', (int) $x['id'] ) ); ?>"><?php echo esc_html( (string) $x['text'] ); ?></a></td><td class="n"><?php echo (int) $x['answered']; ?></td><td class="n" style="color:#b42318;font-weight:700"><?php echo (int) $x['wrong_rate']; ?>%</td><td class="n"><?php echo (int) $x['reports']; ?></td></tr><?php endforeach; ?>
			</tbody></table>
			<p class="ui-note">Sai nhiều có thể do câu khó thật hoặc đáp án / đề bài có vấn đề - mở câu để rà lại. Đang có <?php echo (int) ( $q['open_reports'] ?? 0 ); ?> báo câu sai chờ xử lý (Thu thập → Báo câu sai).</p>
		<?php endif; ?>
	</section>

	<div class="ui-grid2">
		<section class="ui-card">
			<?php cvc_ui_section_head( '3. Sắp rời bỏ', 'Có hoạt động ≥ 2 ngày trong khoảng 8–28 ngày trước nhưng 7 ngày nay không vào. Email nhắc ôn tự gửi cho người 3 ngày không vào (tối đa 1 lần / 10 ngày).' ); ?>
			<?php $risk = (array) ( $c['at_risk'] ?? array() ); ?>
			<?php if ( ! $risk ) : ?><p class="ui-note">Không có ai.</p><?php else : ?>
				<table class="ui-tbl"><thead><tr><th>Người dùng</th><th class="n">Điểm trước</th><th>Nhắc ôn</th></tr></thead><tbody>
					<?php foreach ( $risk as $u ) : ?><tr><td><a href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', (int) $u['id'] ) ); ?>"><?php echo esc_html( (string) $u['name'] ?: (string) $u['email'] ); ?></a><small class="block text-ink-muted"><?php echo (int) $u['prev_days']; ?> ngày học · <?php echo (int) $u['prev_attempts']; ?> bài<?php echo $u['verified'] ? '' : ' · chưa xác minh email'; ?></small></td><td class="n"><?php echo (int) $u['score_before']; ?></td><td><?php echo ! empty( $u['reminded_at'] ) ? esc_html( 'đã gửi ' . cvc_ui_dt( $u['reminded_at'], 'd/m' ) ) : '<span class="text-amber-700">chưa gửi</span>'; ?></td></tr><?php endforeach; ?>
				</tbody></table>
			<?php endif; ?>
		</section>
		<section class="ui-card">
			<?php cvc_ui_section_head( 'Đăng ký nhưng chưa làm bài nào', 'Đăng ký 1–14 ngày trước, chưa làm đề nào. Nên gợi ý 1 đề ngắn đúng tỉnh / nghề ngay sau khi đăng ký.' ); ?>
			<?php $na = (array) ( $c['no_attempt'] ?? array() ); ?>
			<?php if ( ! $na ) : ?><p class="ui-note">Không có ai.</p><?php else : ?>
				<table class="ui-tbl"><tbody>
					<?php foreach ( $na as $u ) : ?><tr><td><a href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', (int) $u['id'] ) ); ?>"><?php echo esc_html( (string) $u['name'] ?: (string) $u['email'] ); ?></a><small class="block text-ink-muted"><?php echo esc_html( 'đăng ký ' . cvc_ui_dt( $u['created_at'], 'd/m' ) . ( $u['verified'] ? '' : ' · chưa xác minh email' ) . ( ! empty( $u['source'] ) ? ' · ' . $u['source'] : '' ) ); ?></small></td></tr><?php endforeach; ?>
				</tbody></table>
			<?php endif; ?>
			<h2 class="mt-4">Học chăm nhất 14 ngày</h2>
			<?php foreach ( (array) ( $c['champions'] ?? array() ) as $u ) : ?><div class="flex justify-between text-sm border-b border-slate-100 py-1"><a class="text-acc font-bold" href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', (int) $u['id'] ) ); ?>"><?php echo esc_html( (string) $u['name'] ?: '#' . $u['id'] ); ?></a><span class="text-ink-muted"><?php echo esc_html( $u['score'] . ' điểm · ' . $u['days'] . ' ngày · ' . $u['attempts'] . ' bài' ); ?></span></div><?php endforeach; ?>
		</section>
	</div>

	<section class="ui-card">
		<?php cvc_ui_section_head( '4. Dấu hiệu bất thường', 'Chỉ là tín hiệu để xem xét, không tự động xử lý tài khoản.' ); ?>
		<?php $fast = (array) ( $an['fast'] ?? array() ); $sh = (array) ( $an['shared_ip'] ?? array() ); $bk = (array) ( $an['bulk_pdf'] ?? array() ); ?>
		<p class="text-sm font-bold">Thi thử quá nhanh mà điểm cao (dưới 6 giây / câu, ≥ 80%)</p>
		<?php if ( ! $fast ) : ?><p class="ui-note">Không có.</p><?php else : ?>
			<ul class="text-sm"><?php foreach ( $fast as $x ) : ?><li class="border-b border-slate-100 py-1"><a class="text-acc font-bold" href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', (int) $x['user_id'] ) ); ?>"><?php echo esc_html( (string) $x['name'] ); ?></a> · <?php echo esc_html( wp_trim_words( (string) $x['exam'], 12 ) . ' · ' . $x['questions'] . ' câu trong ' . $x['seconds'] . ' giây · ' . $x['pct'] . '%' ); ?></li><?php endforeach; ?></ul>
		<?php endif; ?>
		<p class="text-sm font-bold mt-2">Nhiều tài khoản đăng nhập cùng một IP (≥ 3 tài khoản)</p>
		<?php if ( ! $sh ) : ?><p class="ui-note">Không có (dựa trên nhật ký đăng nhập từ 28/10/2026; mạng trường học, cơ quan dùng chung IP là bình thường).</p><?php else : ?>
			<ul class="text-sm"><?php foreach ( $sh as $x ) : ?><li class="border-b border-slate-100 py-1"><?php echo esc_html( $x['ip'] . ' · ' . $x['users'] . ' tài khoản · ' ); ?><?php foreach ( array_slice( (array) $x['ids'], 0, 8 ) as $uid ) : ?><a class="text-acc" href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', (int) $uid ) ); ?>">#<?php echo (int) $uid; ?></a> <?php endforeach; ?></li><?php endforeach; ?></ul>
		<?php endif; ?>
		<p class="text-sm font-bold mt-2">Tải PDF hàng loạt (≥ 15 file / ngày)</p>
		<?php if ( ! $bk ) : ?><p class="ui-note">Không có.</p><?php else : ?>
			<ul class="text-sm"><?php foreach ( $bk as $x ) : ?><li class="border-b border-slate-100 py-1"><a class="text-acc font-bold" href="<?php echo esc_url( cvc_admin_url( 'nguoi-dung', (int) $x['user_id'] ) ); ?>"><?php echo esc_html( (string) $x['name'] ); ?></a> · <?php echo esc_html( $x['n'] . ' file ngày ' . wp_date( 'd/m', strtotime( (string) $x['day'] ) ) ); ?></li><?php endforeach; ?></ul>
		<?php endif; ?>
	</section>
	<?php
}
