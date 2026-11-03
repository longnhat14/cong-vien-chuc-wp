<?php
/**
 * THÓI QUEN ÔN THI (02/11/2026, claude/ke-hoach-tang-luyen-thi.md - anh Nhật chốt: khách làm thử 5 câu có thống kê,
 * nhắc bằng thông báo trình duyệt + email, mục tiêu 5/7 ngày, BXH ẩn danh theo tin, thêm mảng tư duy / xã hội).
 *
 *  - Khối "Ôn hôm nay" (cvc_study_card): Bài ôn hôm nay 10 câu, tuần 5/7 ngày, đếm ngược, đồng hồ ngưỡng vòng 1,
 *    sổ câu sai, bài đang dở (chặng 10 câu), bật nhắc ôn.
 *  - /on-hom-nay/: đã đăng nhập -> mở Bài ôn hôm nay; khách -> làm thử 5 câu.
 *  - Khách làm thử 5 câu (cvc_trial_widget + assets/js/trial.js): đáp án chấm ngay trên trình duyệt, phiên lưu ở
 *    /api/trial/* để đo phễu; đăng nhập xong phiên được gắn vào tài khoản (cookie cvc_trial_sid).
 *  - Thông báo trình duyệt: service worker /sw-cvc.js + AJAX cvc_study (đăng ký / hủy / cài đặt giờ, email).
 * API: /api/me/study, /api/me/study/daily, /api/me/study/settings, /api/me/push/*, /api/trial/*, /api/me/trial/claim.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tổng hợp thói quen ôn của người đang đăng nhập (1 lần / request). */
function cvc_study_summary(): ?array {
	static $cache = false;
	if ( false !== $cache ) {
		return $cache;
	}
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	if ( ! $token ) {
		$cache = null;
		return null;
	}
	$res   = ( new CVC_Api_Client( null, 8 ) )->get( '/api/me/study', array(), $token );
	$cache = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : null;

	return $cache;
}

function cvc_study_url( bool $more = false ): string {
	return home_url( '/on-hom-nay/' . ( $more ? '?them=1' : '' ) );
}

function cvc_study_assets(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$v = static fn ( string $f ): string => (string) filemtime( get_theme_file_path( $f ) );
	wp_enqueue_style( 'cvc-study', get_theme_file_uri( '/assets/css/study.css' ), array(), $v( '/assets/css/study.css' ) );
	wp_enqueue_script( 'cvc-study', get_theme_file_uri( '/assets/js/study.js' ), array(), $v( '/assets/js/study.js' ), true );
	wp_localize_script(
		'cvc-study',
		'CVC_STUDY',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cvc_study' ),
			'api'   => rtrim( (string) cvc_api_base_url(), '/' ),
			'sw'    => home_url( '/sw-cvc.js' ),
		)
	);
}

/** 7 chấm của tuần (T2 - CN). */
function cvc_study_week_html( array $w ): string {
	$h = '<ol class="st-days" aria-label="Ngày ôn trong tuần">';
	foreach ( (array) ( $w['days'] ?? array() ) as $d ) {
		$cls = ! empty( $d['done'] ) ? 'is-done' : ( ! empty( $d['future'] ) ? 'is-future' : 'is-miss' );
		$h  .= '<li class="' . esc_attr( $cls . ( ! empty( $d['today'] ) ? ' is-today' : '' ) ) . '" title="' . esc_attr( $d['label'] . ': ' . ( ! empty( $d['done'] ) ? 'đã ôn' : ( ! empty( $d['future'] ) ? 'chưa tới' : 'chưa ôn' ) ) . ( (int) $d['answered'] ? ' (' . (int) $d['answered'] . ' câu)' : '' ) ) . '"><i>' . ( ! empty( $d['done'] ) ? '✓' : '' ) . '</i><span>' . esc_html( (string) $d['label'] ) . '</span></li>';
	}

	return $h . '</ol>';
}

/**
 * Khối "Ôn hôm nay".
 *
 * @param array{compact?:bool, heading?:string} $o
 */
function cvc_study_card( array $o = array() ): void {
	$s = cvc_study_summary();
	if ( ! $s ) {
		return;
	}
	cvc_study_assets();
	$w       = (array) $s['week'];
	$book    = (array) $s['book'];
	$daily   = is_array( $s['daily'] ?? null ) ? $s['daily'] : null;
	$today   = (array) $s['today'];
	$thr     = is_array( $s['threshold'] ?? null ) ? $s['threshold'] : null;
	$cd      = is_array( $s['countdown'] ?? null ) ? $s['countdown'] : null;
	$rem     = (array) $s['remind'];
	$compact = ! empty( $o['compact'] );
	$done    = ! empty( $today['daily_done'] ) || ( $daily && 'in_progress' !== $daily['status'] );
	if ( $done ) {
		$cta = array( 'Ôn thêm 10 câu', cvc_study_url( true ), 'Đã xong bài ôn hôm nay ✓' );
	} elseif ( $daily && 'in_progress' === $daily['status'] && (int) $daily['answered'] > 0 ) {
		$cta = array( 'Làm tiếp câu ' . ( (int) $daily['answered'] + 1 ) . '/' . (int) $daily['total'], cvc_exam_attempt_url( (int) $daily['attempt_id'] ), '' );
	} else {
		$cta = array( 'Bắt đầu bài ôn hôm nay', cvc_study_url(), '' );
	}
	$parts = array();
	if ( (int) $book['due'] > 0 ) {
		$parts[] = (int) $book['due'] . ' câu từng sai đến hạn ôn lại';
	}
	$parts[] = ! empty( $s['target_label'] ) ? 'câu mới theo đề của tin bạn đang ôn' : 'câu mới Kiến thức chung';
	$parts[] = '1 câu tư duy / tình huống';
	$week_txt = ! empty( $w['met'] ) ? 'Đạt mục tiêu tuần ✓' : ( (int) $w['need'] . ' ngày nữa để đạt 5/7' . ( empty( $w['possible'] ) ? ' (tuần này khó kịp, tuần sau làm lại nhé)' : '' ) );
	?>
	<section class="st-card<?php echo $compact ? ' is-compact' : ''; ?>" id="on-hom-nay" aria-labelledby="st-h">
		<div class="st-top">
			<div class="st-main">
				<span class="st-kicker"><?php echo esc_html( (string) ( $o['heading'] ?? 'ÔN HÔM NAY' ) ); ?></span>
				<h2 id="st-h"><?php echo $done ? 'Bạn đã xong bài ôn hôm nay' : 'Bài ôn hôm nay · 10 câu · khoảng 7 phút'; ?></h2>
				<?php if ( ! $done ) : ?><p class="st-sub"><?php $st_t = implode( ' · ', $parts ); echo esc_html( mb_strtoupper( mb_substr( $st_t, 0, 1 ) ) . mb_substr( $st_t, 1 ) ); ?>.</p><?php else : ?><p class="st-sub">Hôm nay: <?php echo (int) $today['answered']; ?> câu, đúng <?php echo (int) $today['correct']; ?>. Câu sai đã vào Sổ câu sai và sẽ tự quay lại đúng hạn.</p><?php endif; ?>
				<div class="st-actions">
					<a class="st-btn is-primary" href="<?php echo esc_url( $cta[1] ); ?>" rel="nofollow"><?php echo esc_html( $cta[0] ); ?> →</a>
					<?php foreach ( array_slice( array_filter( (array) $s['unfinished'], static fn ( $x ) => empty( $x['daily'] ) ), 0, $compact ? 1 : 2 ) as $x ) : ?>
						<a class="st-btn" href="<?php echo esc_url( cvc_exam_attempt_url( (int) $x['attempt_id'] ) ); ?>" rel="nofollow" title="<?php echo esc_attr( (string) $x['title'] ); ?>">Tiếp tục chặng <?php echo (int) $x['stage']; ?>/<?php echo (int) $x['stages']; ?> · <?php echo esc_html( wp_trim_words( (string) $x['title'], 6 ) ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="st-weekbox">
				<?php echo cvc_study_week_html( $w ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="st-weektxt"><b><?php echo (int) $w['done']; ?>/<?php echo (int) $w['goal']; ?> ngày</b> tuần này · <?php echo esc_html( $week_txt ); ?></p>
				<?php if ( (int) $w['weeks_streak'] > 0 ) : ?><p class="st-streak">Chuỗi <?php echo (int) $w['weeks_streak']; ?> tuần ôn đều</p><?php endif; ?>
				<p class="st-hint">Ngày ôn = làm từ 10 câu hoặc xong bài ôn hôm nay. Nghỉ 2 ngày/tuần vẫn giữ chuỗi.</p>
			</div>
		</div>
		<?php if ( ! $compact || $thr || $cd ) : ?>
		<div class="st-grid">
			<?php if ( $cd ) : ?>
				<div class="st-box"><span class="st-lbl"><?php echo esc_html( (string) $cd['label'] ); ?></span><b class="st-big"><?php echo (int) $cd['days']; ?> <small>ngày</small></b>
					<span class="st-note"><?php echo esc_html( wp_date( 'd/m/Y', strtotime( (string) $cd['date'] ) ) ); ?><?php echo ! empty( $cd['title'] ) ? ' · ' . esc_html( wp_trim_words( (string) $cd['title'], 9 ) ) : ''; ?></span>
					<?php if ( ! empty( $cd['note'] ) ) : ?><span class="st-note"><?php echo esc_html( (string) $cd['note'] ); ?></span><?php endif; ?></div>
			<?php elseif ( ! $compact ) : ?>
				<div class="st-box"><span class="st-lbl">Đếm ngược ngày thi</span><span class="st-note">Lưu một tin tuyển dụng hoặc tạo <a href="<?php echo esc_url( cvc_account_url( 'ho-so-chinh-phuc' ) ); ?>">Hồ sơ chinh phục</a> (đặt ngày thi) để bài ôn bám đúng đề và có đếm ngược.</span></div>
			<?php endif; ?>
			<?php if ( $thr ) : ?>
				<?php $pct = max( 0, min( 100, (int) round( (int) $thr['avg'] / 60 * 100 ) ) ); ?>
				<div class="st-box"><span class="st-lbl">Đồng hồ ngưỡng vòng 1</span><b class="st-big"><?php echo (int) $thr['avg']; ?><small>/60 câu</small></b>
					<span class="st-meter" aria-hidden="true"><i style="width:<?php echo (int) $pct; ?>%" class="<?php echo ! empty( $thr['passed'] ) ? 'is-ok' : ''; ?>"></i><em style="left:50%"></em></span>
					<span class="st-note"><?php echo ! empty( $thr['passed'] ) ? 'Đã qua ngưỡng 30/60' : 'Còn thiếu ' . (int) $thr['gap'] . ' câu để qua ngưỡng 30/60'; ?> · <?php echo esc_html( (string) $thr['note'] ); ?></span></div>
			<?php elseif ( ! $compact ) : ?>
				<div class="st-box"><span class="st-lbl">Đồng hồ ngưỡng vòng 1</span><span class="st-note">Làm 1 đề Thi thử (từ 20 câu) để biết bạn đang ở mức bao nhiêu/60 câu so với ngưỡng 30 câu.</span>
					<a class="st-link" href="<?php echo esc_url( function_exists( 'cvc_exams_mode_url' ) ? cvc_exams_mode_url( 'thi-that' ) : home_url( '/thi-thu-trac-nghiem/' ) ); ?>">Chọn đề thi thử →</a></div>
			<?php endif; ?>
			<?php if ( ! $compact ) : ?>
				<div class="st-box"><span class="st-lbl">Sổ câu sai</span><b class="st-big"><?php echo (int) $book['due']; ?> <small>câu đến hạn</small></b>
					<span class="st-note">Đang ôn <?php echo (int) $book['learning']; ?> · Đã thuộc <?php echo (int) $book['mastered']; ?>. Câu sai quay lại sau 1 → 3 → 7 → 14 ngày; đúng 3 lần cách ngày là thuộc.</span>
					<a class="st-link" href="<?php echo esc_url( function_exists( 'cvc_saved_questions_url' ) ? cvc_saved_questions_url( 'wrong' ) : home_url( '/cau-hoi-da-luu/' ) ); ?>">Xem câu từng sai →</a></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<?php cvc_study_remind_html( $rem ); ?>
	</section>
	<?php
}

/** Dòng bật nhắc ôn (thông báo trình duyệt + email). */
function cvc_study_remind_html( array $rem ): void {
	$on   = ! empty( $rem['push'] ) || ! empty( $rem['email'] );
	$hour = (int) ( $rem['hour'] ?? 20 );
	?>
	<div class="st-remind" data-st-remind data-on="<?php echo $on ? '1' : '0'; ?>">
		<?php if ( $on ) : ?>
			<p class="st-remind-on">Đang nhắc ôn lúc <b><?php echo (int) $hour; ?>:00</b> qua <?php echo esc_html( implode( ' và ', array_filter( array( ! empty( $rem['push'] ) ? 'thông báo trình duyệt' : '', ! empty( $rem['email'] ) ? 'email' : '' ) ) ) ); ?>. <a href="<?php echo esc_url( cvc_account_url( 'thong-bao' ) . '#nhac-on' ); ?>">Đổi</a></p>
		<?php else : ?>
			<div class="st-remind-form">
				<span class="st-remind-t">Nhắc tôi ôn mỗi ngày lúc</span>
				<label class="screen-reader-text" for="st-hour">Giờ nhắc</label>
				<select id="st-hour" data-st-hour><?php for ( $h = 5; $h <= 22; $h++ ) : ?><option value="<?php echo (int) $h; ?>" <?php selected( $hour, $h ); ?>><?php echo (int) $h; ?>:00</option><?php endfor; ?></select>
				<label class="st-chk"><input type="checkbox" data-st-email checked> kèm email</label>
				<button type="button" class="st-btn is-primary" data-st-enable>Bật nhắc ôn</button>
			</div>
			<p class="st-remind-msg" data-st-msg aria-live="polite"></p>
		<?php endif; ?>
	</div>
	<?php
}

/** Cài đặt nhắc ôn (trang Tài khoản → Thông báo). */
function cvc_study_settings_section(): void {
	$s = cvc_study_summary();
	if ( ! $s ) {
		return;
	}
	cvc_study_assets();
	$rem = (array) $s['remind'];
	?>
	<section class="cvc-account-section st-settings" id="nhac-on">
		<h2>Nhắc ôn hằng ngày</h2>
		<p class="st-note">Nhắc làm Bài ôn hôm nay (10 câu, khoảng 7 phút) vào giờ bạn chọn. Hôm nào bạn đã ôn đủ thì không nhắc.</p>
		<div class="st-remind-form" data-st-settings>
			<label>Giờ nhắc <select data-st-hour><?php for ( $h = 5; $h <= 22; $h++ ) : ?><option value="<?php echo (int) $h; ?>" <?php selected( (int) $rem['hour'], $h ); ?>><?php echo (int) $h; ?>:00</option><?php endfor; ?></select></label>
			<label class="st-chk"><input type="checkbox" data-st-email <?php checked( ! empty( $rem['email'] ) ); ?>> Email</label>
			<label class="st-chk"><input type="checkbox" data-st-push <?php checked( ! empty( $rem['push'] ) ); ?>> Thông báo trên trình duyệt / điện thoại này</label>
			<button type="button" class="st-btn is-primary" data-st-save>Lưu</button>
		</div>
		<p class="st-remind-msg" data-st-msg aria-live="polite"></p>
		<p class="st-note">iPhone / iPad: thông báo chỉ chạy khi thêm congvienchuc.com vào Màn hình chính (Safari → Chia sẻ → Thêm vào MH chính) rồi mở từ biểu tượng đó.</p>
	</section>
	<?php
}

/* Nạp CSS trong <head> (file nhỏ) để khối Ôn hôm nay / làm thử không bị nháy khi tải trang. */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style( 'cvc-study', get_theme_file_uri( '/assets/css/study.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/study.css' ) ) );
	},
	20
);

/* ------------------------------------------------------------------ Khách làm thử 5 câu */

/**
 * @param array{source:string, ref?:string, exam?:string, recruitment_id?:int, title?:string, sub?:string, continue?:string, gate?:string, autostart?:bool, htag?:string} $o
 * htag: thẻ tiêu đề (mặc định h2; khối trong bài viết dùng h4 để không lọt vào mục lục).
 */
function cvc_trial_widget( array $o ): void {
	if ( function_exists( 'cvc_auth_token' ) && cvc_auth_token() ) {
		return;
	}
	$v = static fn ( string $f ): string => (string) filemtime( get_theme_file_path( $f ) );
	wp_enqueue_style( 'cvc-study', get_theme_file_uri( '/assets/css/study.css' ), array(), $v( '/assets/css/study.css' ) );
	wp_enqueue_script( 'cvc-trial', get_theme_file_uri( '/assets/js/trial.js' ), array(), $v( '/assets/js/trial.js' ), true );
	$continue = (string) ( $o['continue'] ?? cvc_study_url() );
	$rel      = wp_make_link_relative( $continue );
	$htag     = in_array( $o['htag'] ?? 'h2', array( 'h2', 'h3', 'h4' ), true ) ? (string) ( $o['htag'] ?? 'h2' ) : 'h2';
	?>
	<section class="tr-card" data-trial data-api="<?php echo esc_attr( rtrim( (string) cvc_api_base_url(), '/' ) ); ?>" data-source="<?php echo esc_attr( (string) $o['source'] ); ?>"
		data-ref="<?php echo esc_attr( (string) ( $o['ref'] ?? '' ) ); ?>" data-exam="<?php echo esc_attr( (string) ( $o['exam'] ?? '' ) ); ?>" data-rec="<?php echo (int) ( $o['recruitment_id'] ?? 0 ); ?>"
		data-gate="<?php echo esc_attr( (string) ( $o['gate'] ?? '' ) ); ?>" data-autostart="<?php echo ! empty( $o['autostart'] ) ? '1' : '0'; ?>" aria-label="Làm thử 5 câu">
		<div class="tr-intro" data-tr-intro>
			<span class="st-kicker">LÀM THỬ · KHÔNG CẦN ĐĂNG NHẬP</span>
			<<?php echo $htag; ?>><?php echo esc_html( (string) ( $o['title'] ?? 'Thử 5 câu trắc nghiệm ngay' ) ); ?></<?php echo $htag; ?>>
			<p class="st-sub"><?php echo esc_html( (string) ( $o['sub'] ?? 'Chọn đáp án là thấy ngay đúng / sai, giải thích và điều luật căn cứ. Khoảng 3 phút.' ) ); ?></p>
			<button type="button" class="st-btn is-primary" data-tr-start>Bắt đầu 5 câu →</button>
		</div>
		<div class="tr-play" data-tr-play hidden>
			<div class="tr-bar"><span data-tr-pos>Câu 1/5</span><span class="tr-prog"><i data-tr-prog style="width:0"></i></span><span data-tr-score></span></div>
			<p class="tr-q" data-tr-q></p>
			<div class="tr-opts" data-tr-opts role="group" aria-label="Các đáp án"></div>
			<div class="tr-fb" data-tr-fb hidden aria-live="polite"></div>
			<div class="tr-nav"><button type="button" class="st-btn is-primary" data-tr-next hidden>Câu tiếp →</button></div>
		</div>
		<div class="tr-end" data-tr-end hidden>
			<span class="st-kicker">KẾT QUẢ LÀM THỬ</span>
			<<?php echo $htag; ?> data-tr-end-h></<?php echo $htag; ?>>
			<p class="st-sub" data-tr-end-sub></p>
			<ol class="tr-dots" data-tr-dots></ol>
			<div class="tr-cta">
				<p class="tr-cta-h">Lưu kết quả, nhận Bài ôn hôm nay (10 câu) và để câu sai tự quay lại ôn đúng hạn</p>
				<?php if ( ! empty( $o['gate'] ) ) : ?>
					<button type="button" class="st-btn is-primary" data-tr-cta data-tr-gate>Lưu kết quả &amp; làm tiếp →</button>
				<?php else : ?>
					<a class="st-btn is-primary" data-tr-cta href="<?php echo esc_url( cvc_login_url( $rel ) ); ?>" rel="nofollow">Tiếp tục với Google / email →</a>
					<a class="st-btn" data-tr-cta href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( $rel ), cvc_register_url() ) ); ?>" rel="nofollow">Tạo tài khoản miễn phí</a>
				<?php endif; ?>
				<button type="button" class="st-btn is-ghost" data-tr-again>Làm 5 câu khác</button>
			</div>
			<p class="st-hint">Miễn phí. 5 câu vừa làm được lưu vào tài khoản ngay khi bạn đăng nhập.</p>
		</div>
		<p class="tr-err" data-tr-err hidden></p>
	</section>
	<?php
}

/* ------------------------------------------------------------------ Định tuyến nhỏ */

/** /sw-cvc.js (service worker - phạm vi cả trang) + /on-hom-nay/. */
add_action(
	'init',
	static function (): void {
		$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore
		if ( '/sw-cvc.js' === $path ) {
			header( 'Content-Type: application/javascript; charset=utf-8' );
			header( 'Service-Worker-Allowed: /' );
			header( 'Cache-Control: no-cache' );
			$icon = get_theme_file_uri( '/assets/brand/icon-192.png' );
			echo "/* Công Viên Chức - thông báo nhắc ôn (02/11/2026) */\n"; // phpcs:ignore
			echo 'self.addEventListener("install",function(){self.skipWaiting();});self.addEventListener("activate",function(e){e.waitUntil(self.clients.claim());});'; // phpcs:ignore
			echo 'self.addEventListener("push",function(e){var d={};try{d=e.data?e.data.json():{};}catch(x){d={body:e.data?e.data.text():""};}e.waitUntil(self.registration.showNotification(d.title||"Công Viên Chức",{body:d.body||"",icon:' . wp_json_encode( $icon ) . ',badge:' . wp_json_encode( $icon ) . ',tag:d.tag||"cvc",renotify:true,data:{url:d.url||"/on-hom-nay/"}}));});'; // phpcs:ignore
			echo 'self.addEventListener("notificationclick",function(e){e.notification.close();var u=new URL((e.notification.data&&e.notification.data.url)||"/on-hom-nay/",self.location.origin).href;e.waitUntil(self.clients.matchAll({type:"window",includeUncontrolled:true}).then(function(ws){for(var i=0;i<ws.length;i++){if(ws[i].url===u&&"focus" in ws[i]){return ws[i].focus();}}return self.clients.openWindow(u);}));});'; // phpcs:ignore
			exit;
		}
		// Đăng nhập xong: gắn phiên làm thử vào tài khoản (1 lần).
		$sid = isset( $_COOKIE['cvc_trial_sid'] ) ? sanitize_key( (string) $_COOKIE['cvc_trial_sid'] ) : '';
		if ( 32 === strlen( $sid ) && ! wp_doing_ajax() && function_exists( 'cvc_auth_token' ) && cvc_auth_token() ) {
			( new CVC_Api_Client( null, 6 ) )->post( '/api/me/trial/claim', array( 'sid' => $sid ), cvc_auth_token() );
			setcookie( 'cvc_trial_sid', '', time() - 3600, '/', '', is_ssl(), false );
			unset( $_COOKIE['cvc_trial_sid'] );
		}
	},
	1
);

add_action(
	'template_redirect',
	static function (): void {
		$path = untrailingslashit( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ) ); // phpcs:ignore
		if ( '/on-hom-nay' !== $path ) {
			return;
		}
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'cvc: bài ôn hôm nay' );
		$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
		if ( $token ) {
			$more = ! empty( $_GET['them'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$res  = ( new CVC_Api_Client( null, 40 ) )->post( '/api/me/study/daily', array( 'more' => $more ? '1' : '0' ), $token );
			if ( 401 === (int) ( $res['status'] ?? 0 ) ) {
				wp_safe_redirect( cvc_login_url( cvc_study_url() ) );
				exit;
			}
			if ( function_exists( 'cvc_verify_redirect_if_required' ) ) {
				cvc_verify_redirect_if_required( $res, '/on-hom-nay/' );
			}
			// 03/11/2026: hết lượt "Ôn thêm" miễn phí (Quản trị → Thu phí 'exam_review').
			if ( 402 === (int) ( $res['status'] ?? 0 ) && function_exists( 'cvc_paywall_url' ) ) {
				cvc_redirect_with_notice( cvc_paywall_url( 'exam_review', cvc_account_url() ), 'info', (string) ( $res['data']['message'] ?? 'Hôm nay bạn đã dùng hết lượt ôn thêm miễn phí.' ), null );
				exit;
			}
			$id = (int) ( $res['data']['data']['id'] ?? 0 );
			if ( $res['ok'] && $id > 0 ) {
				wp_safe_redirect( ! empty( $res['data']['done'] ) ? cvc_exam_result_url( $id ) : cvc_exam_attempt_url( $id ) );
				exit;
			}
			cvc_redirect_with_notice( cvc_account_url(), 'error', function_exists( 'cvc_api_error_message' ) ? cvc_api_error_message( $res ) : 'Chưa mở được bài ôn hôm nay.', null );
			exit;
		}
		// Khách: trang làm thử.
		status_header( 200 );
		global $wp_query;
		$wp_query->is_404 = false;
		if ( function_exists( 'cvc_seo_set_title' ) ) {
			cvc_seo_set_title( 'Bài ôn hôm nay - 10 câu mỗi ngày' );
			cvc_seo_set_noindex();
		}
		get_header();
		echo '<main id="main" class="dz-main"><div class="dz-wrap" style="max-width:820px;padding-top:24px;padding-bottom:40px">';
		echo '<h1 style="font-size:28px;margin:0 0 6px">Bài ôn hôm nay</h1><p class="dz-muted" style="margin:0 0 18px">Mỗi ngày 10 câu, khoảng 7 phút: câu từng sai quay lại đúng hạn, câu mới theo đúng đề của tin bạn đang nộp. Mục tiêu 5/7 ngày mỗi tuần.</p>';
		cvc_trial_widget( array( 'source' => 'on-hom-nay', 'title' => 'Làm thử 5 câu trước', 'continue' => cvc_study_url(), 'autostart' => false ) );
		echo '</div></main>';
		get_footer();
		exit;
	},
	1
);

/* ------------------------------------------------------------------ AJAX */

add_action( 'wp_ajax_cvc_study', 'cvc_study_ajax' );
add_action( 'wp_ajax_nopriv_cvc_study', 'cvc_study_ajax' );
function cvc_study_ajax(): void {
	check_ajax_referer( 'cvc_study', 'nonce' );
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	if ( ! $token ) {
		wp_send_json_error( array( 'message' => 'Bạn cần đăng nhập.' ), 401 );
	}
	$op  = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$cli = new CVC_Api_Client( null, 15 );
	$res = array( 'ok' => false );
	if ( 'settings' === $op ) {
		$body = array();
		foreach ( array( 'remind_email', 'remind_push' ) as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				$body[ $k ] = empty( $_POST[ $k ] ) || 'false' === $_POST[ $k ] ? '0' : '1';
			}
		}
		if ( isset( $_POST['hour'] ) ) {
			$body['remind_hour'] = (string) max( 5, min( 22, absint( $_POST['hour'] ) ) );
		}
		$res = $cli->put( '/api/me/study/settings', $body, $token );
	} elseif ( 'push_sub' === $op ) {
		$sub = json_decode( wp_unslash( (string) ( $_POST['sub'] ?? '' ) ), true );
		if ( ! is_array( $sub ) || empty( $sub['endpoint'] ) ) {
			wp_send_json_error( array( 'message' => 'Đăng ký thông báo không hợp lệ.' ) );
		}
		$res = $cli->post(
			'/api/me/push/subscribe',
			array(
				'endpoint' => (string) $sub['endpoint'],
				'keys'     => array( 'p256dh' => (string) ( $sub['keys']['p256dh'] ?? '' ), 'auth' => (string) ( $sub['keys']['auth'] ?? '' ) ),
				'device'   => sanitize_key( (string) ( $_POST['device'] ?? '' ) ),
				'hour'     => isset( $_POST['hour'] ) ? (string) max( 5, min( 22, absint( $_POST['hour'] ) ) ) : null,
				'test'     => empty( $_POST['test'] ) ? '0' : '1',
			),
			$token
		);
	} elseif ( 'push_unsub' === $op ) {
		$res = $cli->post( '/api/me/push/unsubscribe', array( 'endpoint' => esc_url_raw( wp_unslash( (string) ( $_POST['endpoint'] ?? '' ) ) ) ), $token );
	}
	if ( ! $res['ok'] ) {
		wp_send_json_error( array( 'message' => function_exists( 'cvc_api_error_message' ) ? cvc_api_error_message( $res ) : 'Lỗi.' ) );
	}
	wp_send_json_success( array( 'message' => (string) ( $res['data']['message'] ?? 'Đã lưu.' ), 'data' => $res['data']['data'] ?? null ) );
}

/**
 * Giao diện làm bài v2 (03/11/2026, anh Nhật duyệt đề xuất "bo góc, hiệu ứng khi chọn đáp án"): thẻ đáp án bo tròn,
 * chữ cái tròn có ✓/✕, hiệu ứng chọn, thanh 10 vạch, màn "Hoàn thành chặng". Xem trước: ?giao-dien=moi (ghi nhớ bằng
 * cookie cvc_exam_ui 7 ngày vì lượt làm bài mở qua chuyển hướng); ?giao-dien=cu để quay lại. Bật cho mọi người:
 * tùy chọn cvc_exam_ui_v2 = 1.
 */
function cvc_exam_ui_v2_active(): bool {
	$q = isset( $_GET['giao-dien'] ) ? sanitize_key( wp_unslash( $_GET['giao-dien'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$c = isset( $_COOKIE['cvc_exam_ui'] ) ? sanitize_key( wp_unslash( $_COOKIE['cvc_exam_ui'] ) ) : '';
	$v = '' !== $q ? $q : $c;
	if ( 'cu' === $v ) {
		return false;
	}
	return 'moi' === $v || '1' === (string) get_option( 'cvc_exam_ui_v2', '0' );
}
add_action(
	'init',
	static function (): void {
		$q = isset( $_GET['giao-dien'] ) ? sanitize_key( wp_unslash( $_GET['giao-dien'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( in_array( $q, array( 'moi', 'cu' ), true ) && ! headers_sent() ) {
			setcookie( 'cvc_exam_ui', $q, array( 'expires' => time() + 7 * DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
			$_COOKIE['cvc_exam_ui'] = $q;
		}
	}
);
