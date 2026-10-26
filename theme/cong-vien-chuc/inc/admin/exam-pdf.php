<?php
/**
 * Quản trị → Thu thập → Tải đề PDF (26/10/2026): bật/tắt, bản có đáp án, giới hạn/ngày, thu phí; thống kê lượt tải.
 * API: GET/PUT /api/admin/exam-pdf.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_admin_render_exam_pdf(): void {
	$res   = cvc_admin_api_get( '/api/admin/exam-pdf' );
	$data  = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$s     = (array) ( $data['settings'] ?? array() );
	$t     = (array) ( $data['totals'] ?? array() );
	$daily = (array) ( $data['daily'] ?? array() );
	$top   = (array) ( $data['top'] ?? array() );
	$can   = cvc_admin_can( 'settings.update' );
	$max   = max( 1, ...array_map( static fn ( $d ) => (int) ( $d['c'] ?? 0 ), $daily ?: array( array( 'c' => 1 ) ) ) );
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Tải đề PDF</h1>
		<p class="text-sm text-ink-muted">Người dùng xem trước miễn phí, đăng nhập để tải file PDF có logo Công Viên Chức, chữ chìm congvienchuc.com, mã QR làm bài online và tên người tải (che bớt) ở chân trang.</p>
	</header>
	<?php cvc_ingestion_tabs( 'tai-pdf' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<section class="grid grid-cols-2 sm:grid-cols-4 gap-3">
		<?php
		foreach ( array(
			array( 'Tổng lượt tải', $t['all'] ?? 0 ),
			array( 'Lượt tải 30 ngày', $t['days30'] ?? 0 ),
			array( 'Người tải 30 ngày', $t['users30'] ?? 0 ),
			array( 'Bản có đáp án (30 ngày)', $t['answers30'] ?? 0 ),
		) as $kpi ) :
			?>
			<div class="border border-slate-300 rounded-xl p-3 bg-white"><p class="text-xs text-ink-muted"><?php echo esc_html( $kpi[0] ); ?></p><p class="text-xl font-black text-ink tabular-nums"><?php echo esc_html( number_format( (int) $kpi[1], 0, ',', '.' ) ); ?></p></div>
		<?php endforeach; ?>
	</section>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="border border-slate-300 rounded-xl p-4 bg-white space-y-3">
		<?php wp_nonce_field( 'cvc_admin_exam_pdf' ); ?>
		<input type="hidden" name="action" value="cvc_admin_exam_pdf">
		<h2 class="font-black text-ink">Cài đặt</h2>
		<fieldset class="grid sm:grid-cols-2 gap-2 text-sm" <?php disabled( ! $can ); ?>>
			<label class="flex items-center gap-2"><input type="checkbox" name="enabled" value="1" class="accent-acc-2" <?php checked( ! empty( $s['enabled'] ) ); ?>> Bật tính năng tải đề PDF</label>
			<label class="flex items-center gap-2"><input type="checkbox" name="answers" value="1" class="accent-acc-2" <?php checked( ! empty( $s['answers'] ) ); ?>> Cho tải bản có đáp án và giải thích</label>
			<label class="flex items-center gap-2"><input type="checkbox" name="paywall" value="1" class="accent-acc-2" <?php checked( ! empty( $s['paywall'] ) ); ?>> <b>Thu phí</b> (cần gói 30 ngày hoặc gói theo bộ đề; xem trước vẫn miễn phí)</label>
			<label class="flex items-center gap-2"><input type="checkbox" name="protect" value="1" class="accent-acc-2" <?php checked( ! isset( $s['protect'] ) || ! empty( $s['protect'] ) ); ?>> Khóa sao chép, trích xuất và chỉnh sửa nội dung PDF</label>
			<label class="flex items-center gap-2"><input type="checkbox" name="allow_print" value="1" class="accent-acc-2" <?php checked( ! isset( $s['allow_print'] ) || ! empty( $s['allow_print'] ) ); ?>> Cho phép in file PDF (khi đang khóa)</label>
			<label class="flex items-center gap-2">Giới hạn mỗi người <input type="number" name="daily_limit" min="1" max="500" value="<?php echo esc_attr( (string) (int) ( $s['daily_limit'] ?? 20 ) ); ?>" class="w-20 border border-slate-300 rounded px-2 py-1"> file/ngày</label>
		</fieldset>
		<p class="text-xs <?php echo ! empty( $s['paywall'] ) ? 'text-amber-800' : 'text-emerald-800'; ?>"><?php echo esc_html( ! empty( $s['paywall'] ) ? 'Đang thu phí: người chưa có gói chỉ xem trước được. Giá gói lấy theo cấu hình đề cá nhân hóa (gói theo bộ đề / gói 30 ngày).' : 'Đang miễn phí: mọi tài khoản đã đăng nhập đều tải được (trong giới hạn mỗi ngày).' ); ?></p>
		<?php if ( $can ) : ?><button type="submit" class="px-4 py-2 rounded-lg bg-acc text-white font-bold text-sm">Lưu cài đặt</button><?php endif; ?>
	</form>

	<section class="grid lg:grid-cols-2 gap-4">
		<div class="border border-slate-300 rounded-xl p-4 bg-white">
			<h2 class="font-black text-ink mb-2">Lượt tải 14 ngày</h2>
			<?php if ( ! $daily ) : ?>
				<p class="text-sm text-ink-muted">Chưa có lượt tải.</p>
			<?php else : ?>
				<table class="w-full text-sm tabular-nums">
					<?php foreach ( $daily as $d ) : ?>
						<?php $w = (int) round( 100 * (int) $d['c'] / $max ); ?>
						<tr><td class="py-1 pr-2 w-24 text-ink-muted"><?php echo esc_html( wp_date( 'd/m', strtotime( (string) $d['d'] ) ) ); ?></td><td class="py-1"><span class="inline-block h-3 rounded bg-acc-2 align-middle" style="width:<?php echo (int) max( 2, $w ); ?>%"></span> <b><?php echo (int) $d['c']; ?></b> <span class="text-xs text-ink-muted">(<?php echo (int) $d['u']; ?> người)</span></td></tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>
		</div>
		<div class="border border-slate-300 rounded-xl p-4 bg-white">
			<h2 class="font-black text-ink mb-2">Tải nhiều nhất 30 ngày</h2>
			<?php if ( ! $top ) : ?>
				<p class="text-sm text-ink-muted">Chưa có lượt tải.</p>
			<?php else : ?>
				<ol class="text-sm space-y-1 list-decimal pl-5">
					<?php foreach ( $top as $row ) : ?>
						<li><?php echo esc_html( ( ! empty( $row['recruitment_id'] ) ? 'Bộ đề tin #' . (int) $row['recruitment_id'] . ': ' : '' ) . (string) ( $row['title'] ?? '' ) ); ?> — <b class="tabular-nums"><?php echo (int) ( $row['c'] ?? 0 ); ?></b></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
	</section>
	<?php cvc_admin_render_exam_pdf_returns( (array) ( $data['returns'] ?? array() ) ); ?>
	<?php
}

/** Lượt quay lại site từ file PDF / ảnh bìa (27/10/2026) - đo bằng utm_source=pdf|cover + mã tải (utm_content). */
function cvc_admin_render_exam_pdf_returns( array $r ): void {
	if ( ! $r ) {
		return;
	}
	$fmt   = static fn ( $n ): string => number_format( (int) $n, 0, ',', '.' );
	$daily = (array) ( $r['daily'] ?? array() );
	$max   = max( 1, ...array_map( static fn ( $d ) => (int) ( $d['v'] ?? 0 ) + (int) ( $d['o'] ?? 0 ), $daily ?: array( array( 'v' => 1 ) ) ) );
	$med   = array( 'de-thi' => 'Ô / biểu tượng trong file', 'qr' => 'Quét mã QR', 'cta' => 'Khung cuối file', 'ai-coach' => 'Nút AI Coach', '-' => 'Không rõ' );
	?>
	<section class="border border-slate-300 rounded-xl p-4 bg-white space-y-3">
		<div>
			<h2 class="font-black text-ink">Quay lại site từ file PDF (30 ngày)</h2>
			<p class="text-xs text-ink-muted">Mỗi liên kết và mã QR trong file mang mã tải của file đó, nên biết được bao nhiêu file đã đưa người đọc quay lại. Một người mở lại cùng trang trong 30 phút chỉ tính 1 lần.</p>
		</div>
		<div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
			<?php
			foreach ( array(
				array( 'Lượt vào site từ PDF', $r['visits_pdf'] ?? 0, '' ),
				array( 'Lượt vào từ ảnh bìa', $r['visits_cover'] ?? 0, '' ),
				array( 'File có người quay lại', $r['files_returned'] ?? 0, null !== ( $r['return_rate'] ?? null ) ? $r['return_rate'] . '% số file tải 30 ngày' : '' ),
				array( 'Mở AI Coach từ PDF', $r['ai_open'] ?? 0, '' ),
				array( 'Đặt câu hỏi AI Coach', $r['ai_ask'] ?? 0, 'người đến từ PDF / ảnh bìa' ),
			) as $kpi ) :
				?>
				<div class="border border-slate-200 rounded-xl p-3"><p class="text-xs text-ink-muted"><?php echo esc_html( $kpi[0] ); ?></p><p class="text-xl font-black text-ink tabular-nums"><?php echo esc_html( $fmt( $kpi[1] ) ); ?></p><?php if ( '' !== $kpi[2] ) : ?><p class="text-xs text-ink-muted"><?php echo esc_html( (string) $kpi[2] ); ?></p><?php endif; ?></div>
			<?php endforeach; ?>
		</div>
		<div class="grid lg:grid-cols-3 gap-4 text-sm">
			<div>
				<h3 class="font-bold text-ink mb-1">14 ngày (lượt vào / mở AI Coach)</h3>
				<?php if ( ! $daily ) : ?><p class="text-ink-muted">Chưa có lượt nào.</p><?php else : ?>
					<table class="w-full tabular-nums"><?php foreach ( $daily as $d ) : ?><?php $w = (int) round( 100 * ( (int) $d['v'] + (int) $d['o'] ) / $max ); ?><tr><td class="py-1 pr-2 w-14 text-ink-muted"><?php echo esc_html( wp_date( 'd/m', strtotime( (string) $d['d'] ) ) ); ?></td><td class="py-1"><span class="inline-block h-3 rounded bg-acc-2 align-middle" style="width:<?php echo (int) max( 2, $w ); ?>%"></span> <b><?php echo (int) $d['v']; ?></b> / <?php echo (int) $d['o']; ?> <span class="text-xs text-ink-muted">(<?php echo (int) $d['a']; ?> hỏi)</span></td></tr><?php endforeach; ?></table>
				<?php endif; ?>
			</div>
			<div>
				<h3 class="font-bold text-ink mb-1">Theo vị trí bấm trong file</h3>
				<ul class="space-y-1"><?php foreach ( (array) ( $r['by_medium'] ?? array() ) as $m ) : ?><li><?php echo esc_html( $med[ (string) $m['m'] ] ?? (string) $m['m'] ); ?> — <b class="tabular-nums"><?php echo (int) $m['c']; ?></b></li><?php endforeach; ?><?php if ( empty( $r['by_medium'] ) ) : ?><li class="text-ink-muted">Chưa có.</li><?php endif; ?></ul>
			</div>
			<div>
				<h3 class="font-bold text-ink mb-1">Trang được mở nhiều</h3>
				<ol class="list-decimal pl-5 space-y-1"><?php foreach ( (array) ( $r['top_pages'] ?? array() ) as $pg ) : ?><li class="break-all"><?php echo esc_html( (string) $pg['path'] ); ?> — <b class="tabular-nums"><?php echo (int) $pg['c']; ?></b></li><?php endforeach; ?><?php if ( empty( $r['top_pages'] ) ) : ?><li class="text-ink-muted list-none">Chưa có.</li><?php endif; ?></ol>
			</div>
		</div>
	</section>
	<?php
}

add_action( 'admin_post_cvc_admin_exam_pdf', 'cvc_admin_handle_exam_pdf' );
add_action( 'admin_post_nopriv_cvc_admin_exam_pdf', 'cvc_admin_handle_exam_pdf' );

function cvc_admin_handle_exam_pdf(): void {
	check_admin_referer( 'cvc_admin_exam_pdf' );
	$token = cvc_admin_require_staff();
	$back  = cvc_admin_url( 'thu-thap', null, array( 'xem' => 'tai-pdf' ) );
	if ( ! cvc_admin_can( 'settings.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền đổi cấu hình.' );
		return;
	}
	$body   = array(
		'enabled'     => ! empty( $_POST['enabled'] ),
		'answers'     => ! empty( $_POST['answers'] ),
		'paywall'     => ! empty( $_POST['paywall'] ),
		'protect'     => ! empty( $_POST['protect'] ),
		'allow_print' => ! empty( $_POST['allow_print'] ),
		'daily_limit' => max( 1, min( 500, absint( $_POST['daily_limit'] ?? 20 ) ) ),
	);
	$result = ( new CVC_Api_Client( null, 30 ) )->put( '/api/admin/exam-pdf', $body, $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}
	cvc_admin_redirect( $back, 'success', 'Đã lưu cài đặt tải đề PDF.' );
}
