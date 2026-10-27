<?php
/**
 * Quản trị → Thu thập → Email (27/10/2026): email giữ chân người dùng (chào mừng, bản tin tuần, nhắc ôn).
 * Bật/tắt, giới hạn/ngày, gửi thử tới chính tài khoản quản trị, thống kê gửi + lượt quay lại từ email.
 * API: GET/PUT /api/admin/email-retention, POST /api/admin/email-retention/test.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_admin_render_email_retention(): void {
	$res  = cvc_admin_api_get( '/api/admin/email-retention' );
	$data = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$s    = (array) ( $data['settings'] ?? array() );
	$kind = (array) ( $data['kinds'] ?? array() );
	$by   = (array) ( $data['by_kind'] ?? array() );
	$aud  = (array) ( $data['audience'] ?? array() );
	$pend = (array) ( $data['pending'] ?? array() );
	$can  = cvc_admin_can( 'settings.update' );
	$fmt  = static fn ( $n ): string => number_format( (int) $n, 0, ',', '.' );
	$desc = array(
		'welcome'  => 'Từ 27/10 email chào mừng được gửi NGAY khi đăng ký, kèm mã xác minh email (luôn gửi, không phụ thuộc công tắc này). Công tắc này chỉ gửi bù cho người đăng ký 48 giờ qua chưa nhận.',
		'digest'   => 'Sáng thứ Hai 07:35: tin mới 7 ngày + bộ đề PDF mới theo tỉnh/ngành quan tâm, kết quả ôn tuần qua. Không có tin mới thì không gửi.',
		'reminder' => '19:05 hằng ngày cho người đã 3 ngày không vào: bài đang làm dở, đề làm lại, tin mới. Tối đa 1 lần / 10 ngày.',
	);
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Email giữ chân người dùng</h1>
		<p class="text-sm text-ink-muted">Email tự động gửi từ info@congvienchuc.com. Mỗi email có liên kết hủy nhận 1 chạm; người dùng tự chọn loại email ở Tài khoản → Thông báo.</p>
	</header>
	<?php cvc_ingestion_tabs( 'email' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( empty( $s['enabled'] ) ) : ?>
		<p class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900"><b>Đang tắt.</b> Bấm "Gửi thử" để xem từng email trong hộp thư của bạn, sau đó bật "Tự động gửi" để bắt đầu.</p>
	<?php endif; ?>

	<section class="grid grid-cols-2 sm:grid-cols-4 gap-3">
		<?php
		foreach ( array(
			array( 'Tài khoản', $aud['users'] ?? 0, (int) ( $aud['verified'] ?? 0 ) . ' đã xác minh email' ),
			array( 'Đã gửi hôm nay', $data['sent_today'] ?? 0, 'giới hạn ' . (int) ( $s['daily_cap'] ?? 200 ) . '/ngày' ),
			array( 'Hủy nhận mọi email', $aud['unsubscribed'] ?? 0, '' ),
			array( 'Tắt bản tin / nhắc ôn', ( (int) ( $aud['digest_off'] ?? 0 ) ) . ' / ' . ( (int) ( $aud['reminder_off'] ?? 0 ) ), '' ),
		) as $kpi ) :
			?>
			<div class="border border-slate-300 rounded-xl p-3 bg-white"><p class="text-xs text-ink-muted"><?php echo esc_html( $kpi[0] ); ?></p><p class="text-xl font-black text-ink tabular-nums"><?php echo esc_html( is_numeric( $kpi[1] ) ? $fmt( $kpi[1] ) : (string) $kpi[1] ); ?></p><?php if ( '' !== $kpi[2] ) : ?><p class="text-xs text-ink-muted"><?php echo esc_html( $kpi[2] ); ?></p><?php endif; ?></div>
		<?php endforeach; ?>
	</section>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="border border-slate-300 rounded-xl p-4 bg-white space-y-3">
		<?php wp_nonce_field( 'cvc_admin_email_retention' ); ?>
		<input type="hidden" name="action" value="cvc_admin_email_retention">
		<input type="hidden" name="op" value="save">
		<h2 class="font-black text-ink">Cài đặt</h2>
		<fieldset class="space-y-2 text-sm" <?php disabled( ! $can ); ?>>
			<label class="flex items-center gap-2 font-bold"><input type="checkbox" name="enabled" value="1" class="accent-acc-2" <?php checked( ! empty( $s['enabled'] ) ); ?>> Tự động gửi email</label>
			<?php foreach ( $kind as $k => $lbl ) : ?>
				<label class="flex items-start gap-2"><input type="checkbox" name="<?php echo esc_attr( $k ); ?>" value="1" class="accent-acc-2 mt-1" <?php checked( ! empty( $s[ $k ] ) ); ?>><span><b><?php echo esc_html( (string) $lbl ); ?></b> — <?php echo esc_html( $desc[ $k ] ?? '' ); ?> <span class="text-xs text-ink-muted">(đang chờ: <?php echo (int) ( $pend[ $k ] ?? 0 ); ?> người)</span></span></label>
			<?php endforeach; ?>
			<label class="flex items-center gap-2">Tối đa <input type="number" name="daily_cap" min="10" max="2000" value="<?php echo esc_attr( (string) (int) ( $s['daily_cap'] ?? 200 ) ); ?>" class="w-24 border border-slate-300 rounded px-2 py-1"> email/ngày <span class="text-xs text-ink-muted">(Hostinger giới hạn số email gửi mỗi ngày theo gói)</span></label>
		</fieldset>
		<?php if ( $can ) : ?><button type="submit" class="px-4 py-2 rounded-lg bg-acc text-white font-bold text-sm">Lưu cài đặt</button><?php endif; ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="border border-slate-300 rounded-xl p-4 bg-white space-y-3">
		<?php wp_nonce_field( 'cvc_admin_email_retention' ); ?>
		<input type="hidden" name="action" value="cvc_admin_email_retention">
		<input type="hidden" name="op" value="google">
		<h2 class="font-black text-ink">Đăng nhập bằng Google <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo ! empty( $data['google_client_id'] ) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'; ?>"><?php echo ! empty( $data['google_client_id'] ) ? 'Đang bật' : 'Chưa bật'; ?></span></h2>
		<p class="text-xs text-ink-muted">Tạo <b>OAuth Client ID</b> loại "Web application" tại Google Cloud Console → APIs &amp; Services → Credentials; mục <b>Authorized JavaScript origins</b> thêm <code>https://congvienchuc.com</code>. Chỉ cần Client ID (không cần Client secret). Để trống rồi lưu = tắt nút Google.</p>
		<fieldset class="text-sm" <?php disabled( ! $can ); ?>>
			<label class="block">Client ID <input type="text" name="google_client_id" value="<?php echo esc_attr( (string) ( $data['google_client_id'] ?? '' ) ); ?>" placeholder="123456789012-xxxxxxxx.apps.googleusercontent.com" class="mt-1 w-full border border-slate-300 rounded px-2 py-1 font-mono text-xs" autocomplete="off" spellcheck="false"></label>
		</fieldset>
		<?php if ( $can ) : ?><button type="submit" class="px-4 py-2 rounded-lg bg-acc text-white font-bold text-sm">Lưu Client ID</button><?php endif; ?>
	</form>

	<?php if ( $can ) : ?>
		<section class="border border-slate-300 rounded-xl p-4 bg-white space-y-2">
			<h2 class="font-black text-ink">Gửi thử tới hộp thư của bạn</h2>
			<p class="text-xs text-ink-muted">Email thử dùng dữ liệu của chính tài khoản quản trị đang đăng nhập (<?php echo esc_html( (string) ( ( cvc_current_user() ?? array() )['email'] ?? '' ) ); ?>), không tính vào giới hạn ngày.</p>
			<div class="flex flex-wrap gap-2">
				<?php foreach ( $kind as $k => $lbl ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'cvc_admin_email_retention' ); ?>
						<input type="hidden" name="action" value="cvc_admin_email_retention"><input type="hidden" name="op" value="test"><input type="hidden" name="kind" value="<?php echo esc_attr( $k ); ?>">
						<button type="submit" class="px-3 py-2 rounded-lg border border-slate-300 bg-white font-bold text-sm text-ink hover:border-acc-line">Gửi thử: <?php echo esc_html( (string) $lbl ); ?></button>
					</form>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="grid lg:grid-cols-2 gap-4">
		<div class="border border-slate-300 rounded-xl p-4 bg-white">
			<h2 class="font-black text-ink mb-2">30 ngày qua</h2>
			<table class="w-full text-sm tabular-nums">
				<tr class="text-left text-ink-muted"><th class="py-1">Loại</th><th>Đã gửi</th><th>Lỗi</th><th>Bỏ qua</th></tr>
				<?php foreach ( array_merge( $kind, array( 'test' => 'Gửi thử' ) ) as $k => $lbl ) : $row = (array) ( $by[ $k ] ?? array() ); ?>
					<tr class="border-t border-slate-200"><td class="py-1"><?php echo esc_html( (string) $lbl ); ?></td><td><b><?php echo (int) ( $row['sent'] ?? 0 ); ?></b></td><td class="<?php echo (int) ( $row['failed'] ?? 0 ) > 0 ? 'text-red-700 font-bold' : ''; ?>"><?php echo (int) ( $row['failed'] ?? 0 ); ?></td><td><?php echo (int) ( $row['skipped'] ?? 0 ); ?></td></tr>
				<?php endforeach; ?>
			</table>
			<h3 class="font-bold text-ink mt-3 mb-1 text-sm">Quay lại site từ email</h3>
			<ul class="text-sm space-y-1">
				<?php foreach ( (array) ( $data['visits'] ?? array() ) as $v ) : ?><li><?php echo esc_html( $kind[ (string) $v['m'] ] ?? (string) $v['m'] ); ?> — <b class="tabular-nums"><?php echo (int) $v['v']; ?></b> lượt vào<?php echo (int) $v['a'] > 0 ? ', ' . (int) $v['a'] . ' hỏi AI Coach' : ''; ?></li><?php endforeach; ?>
				<?php if ( empty( $data['visits'] ) ) : ?><li class="text-ink-muted">Chưa có.</li><?php endif; ?>
			</ul>
		</div>
		<div class="border border-slate-300 rounded-xl p-4 bg-white">
			<h2 class="font-black text-ink mb-2">Gửi gần đây</h2>
			<?php if ( empty( $data['recent'] ) ) : ?><p class="text-sm text-ink-muted">Chưa gửi email nào.</p><?php else : ?>
				<ul class="text-xs space-y-1">
					<?php foreach ( (array) $data['recent'] as $r ) : ?>
						<li><span class="text-ink-muted"><?php echo esc_html( wp_date( 'd/m H:i', strtotime( (string) $r['created_at'] . ' UTC' ) ) ); ?></span> · <?php echo esc_html( ( $kind[ (string) $r['kind'] ] ?? (string) $r['kind'] ) . ' → ' . preg_replace( '/^(.{2}).*(@.*)$/', '$1***$2', (string) $r['email'] ) ); ?> · <b class="<?php echo 'failed' === $r['status'] ? 'text-red-700' : ''; ?>"><?php echo esc_html( array( 'sent' => 'đã gửi', 'failed' => 'lỗi', 'skipped' => 'bỏ qua' )[ (string) $r['status'] ] ?? (string) $r['status'] ); ?></b><?php echo ! empty( $r['error'] ) ? ' — ' . esc_html( (string) $r['error'] ) : ''; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

add_action( 'admin_post_cvc_admin_email_retention', 'cvc_admin_handle_email_retention' );
add_action( 'admin_post_nopriv_cvc_admin_email_retention', 'cvc_admin_handle_email_retention' );

function cvc_admin_handle_email_retention(): void {
	check_admin_referer( 'cvc_admin_email_retention' );
	$token = cvc_admin_require_staff();
	$back  = cvc_admin_url( 'thu-thap', null, array( 'xem' => 'email' ) );
	if ( ! cvc_admin_can( 'settings.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền đổi cấu hình.' );
		return;
	}
	$op = sanitize_key( wp_unslash( $_POST['op'] ?? 'save' ) );
	if ( 'google' === $op ) {
		$gid    = sanitize_text_field( wp_unslash( $_POST['google_client_id'] ?? '' ) );
		$result = ( new CVC_Api_Client( null, 20 ) )->put( '/api/admin/email-retention', array( 'google_client_id' => $gid ), $token );
		delete_transient( 'cvc_google_cfg' );
		if ( ! $result['ok'] ) {
			cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
			return;
		}
		cvc_admin_redirect( $back, 'success', '' !== $gid ? 'Đã bật đăng nhập bằng Google.' : 'Đã tắt đăng nhập bằng Google.' );
		return;
	}
	if ( 'test' === $op ) {
		$kind   = sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) );
		$result = ( new CVC_Api_Client( null, 60 ) )->post( '/api/admin/email-retention/test', array( 'kind' => $kind ), $token );
		if ( ! $result['ok'] ) {
			cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
			return;
		}
		cvc_admin_redirect( $back, 'success', 'Đã gửi thử: ' . (string) ( $result['data']['data']['subject'] ?? '' ) . '. Kiểm tra hộp thư (và thư mục Spam/Quảng cáo).' );
		return;
	}
	$body   = array(
		'enabled'   => ! empty( $_POST['enabled'] ),
		'welcome'   => ! empty( $_POST['welcome'] ),
		'digest'    => ! empty( $_POST['digest'] ),
		'reminder'  => ! empty( $_POST['reminder'] ),
		'daily_cap' => max( 10, min( 2000, absint( $_POST['daily_cap'] ?? 200 ) ) ),
	);
	$result = ( new CVC_Api_Client( null, 30 ) )->put( '/api/admin/email-retention', $body, $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}
	cvc_admin_redirect( $back, 'success', 'Đã lưu cài đặt email.' );
}
