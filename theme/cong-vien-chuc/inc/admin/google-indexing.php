<?php
/**
 * Google Indexing API - /quan-tri/thu-thap/?xem=google-index (12/11/2026).
 * Báo Google các trang việc làm (JobPosting) mới / cập nhật / hết hạn / bị gỡ; 15 phút chạy 1 lần.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_gidx_url( array $args = array() ): string {
	return cvc_admin_url( 'thu-thap', null, array( 'xem' => 'google-index' ) + $args );
}

function cvc_admin_render_google_indexing(): void {
	$tt  = sanitize_key( (string) ( $_GET['tt'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$res = cvc_admin_api_get( '/api/admin/google-indexing', array_filter( array( 'tt' => $tt ) ) );
	$d   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$can = cvc_admin_can( 'recruitment.publish' );
	$c   = (array) ( $d['counts'] ?? array() );
	$rq  = (array) ( $d['queued_by_reason'] ?? array() );
	$rl  = array( 'new' => 'Tin mới', 'updated' => 'Cập nhật', 'expired' => 'Hết hạn', 'removed' => 'Gỡ', 'backfill' => 'Nạp tin cũ', 'manual' => 'Thủ công' );
	$sl  = array( 'queued' => 'Chờ gửi', 'sent' => 'Đã gửi', 'failed' => 'Lỗi', 'skipped' => 'Bỏ qua' );
	$btn = 'px-3 py-2 rounded-xl text-sm font-bold';
	$op  = static function ( string $op, string $label, string $cls, array $extra = array() ): string {
		$h = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="inline">' . wp_nonce_field( 'cvc_admin_gidx', '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="cvc_admin_gidx"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
		foreach ( $extra as $k => $v ) {
			$h .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
		}

		return $h . '<button type="submit" class="' . esc_attr( $cls ) . '">' . esc_html( $label ) . '</button></form>';
	};
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-ink">Google Indexing API</h1>
		<p class="text-sm text-ink-muted max-w-3xl">Tự báo Google mỗi 15 phút khi trang việc làm được đăng mới, cập nhật, hết hạn hoặc bị gỡ, để Google thu thập lại sớm. Google chỉ cho dùng API này với trang có dữ liệu <b>JobPosting</b> (trang tin có 1 vị trí cụ thể và trang từng vị trí), nên các trang khác (đề thi, văn bản, tài liệu) không gửi. Hạn mức mặc định 200 lượt / ngày; hệ thống dùng tối đa <?php echo (int) ( $d['cap'] ?? 190 ); ?>, ưu tiên tin mới.</p>
	</header>
	<?php cvc_ingestion_tabs( 'google-index' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<div class="dz-card text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></div>
		<?php return; ?>
	<?php endif; ?>
	<?php if ( empty( $d['configured'] ) ) : ?>
		<div class="dz-card space-y-2 text-sm">
			<p class="font-bold text-red-700">Chưa kết nối: chưa có khóa tài khoản dịch vụ Google.</p>
			<p>Các thay đổi vẫn được xếp hàng và sẽ gửi khi có khóa. Cần làm (chủ tài khoản Google):</p>
			<ol class="list-decimal pl-5 space-y-1">
				<li>Google Cloud Console → tạo / chọn dự án → bật <b>Web Search Indexing API</b>.</li>
				<li>IAM → Tài khoản dịch vụ → tạo tài khoản → Khóa → Thêm khóa → JSON (tải tệp .json).</li>
				<li>Search Console → thuộc tính congvienchuc.com → Cài đặt → Người dùng và quyền → thêm email tài khoản dịch vụ với quyền <b>Chủ sở hữu</b>.</li>
				<li>Gửi tệp .json cho kỹ thuật để đặt vào máy chủ (storage/app/google-indexing.json, không đưa vào git).</li>
			</ol>
		</div>
	<?php else : ?>
		<div class="dz-card flex flex-wrap items-center gap-3 text-sm">
			<span>Tài khoản dịch vụ: <b><?php echo esc_html( (string) $d['client_email'] ); ?></b></span>
			<?php if ( $can ) : ?>
				<?php echo $op( 'test', 'Kiểm tra kết nối', $btn . ' border border-slate-300 bg-white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo $op( 'send', 'Gửi ngay', $btn . ' bg-acc text-white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( ! empty( $d['paused_until'] ) ) : ?><?php echo $op( 'resume', 'Bỏ tạm dừng', $btn . ' border border-red-300 text-red-700 bg-white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $d['paused_until'] ) ) : ?><p class="text-sm text-red-700">Đang tạm dừng tới <?php echo esc_html( cvc_admin_dt( (string) $d['paused_until'] ) ); ?> (hết hạn mức hoặc thiếu quyền Search Console).</p><?php endif; ?>
	<div class="grid gap-3" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
		<?php
		foreach ( array(
			array( 'Đã dùng hôm nay', (int) ( $d['used_today'] ?? 0 ) . '/' . (int) ( $d['cap'] ?? 190 ), 'theo ngày giờ Mỹ (Google tính)' ),
			array( 'Chờ gửi', (int) ( $c['queued'] ?? 0 ), implode( ' · ', array_map( static fn ( $k, $v ) => ( $rl[ $k ] ?? $k ) . ' ' . (int) $v, array_keys( $rq ), $rq ) ) ),
			array( 'Đã gửi', (int) ( $c['sent'] ?? 0 ), 'tổng' ),
			array( 'Lỗi', (int) ( $c['failed'] ?? 0 ), 'tổng' ),
		) as $k ) :
			?>
			<div class="dz-card"><p class="text-xs text-ink-muted"><?php echo esc_html( $k[0] ); ?></p><p class="text-2xl font-black tabular-nums"><?php echo esc_html( (string) $k[1] ); ?></p><p class="text-xs text-ink-muted"><?php echo esc_html( (string) $k[2] ); ?></p></div>
		<?php endforeach; ?>
	</div>
	<?php if ( $can ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dz-card flex flex-wrap items-end gap-2 text-sm">
			<?php wp_nonce_field( 'cvc_admin_gidx' ); ?><input type="hidden" name="action" value="cvc_admin_gidx"><input type="hidden" name="op" value="queue">
			<label class="flex-1 min-w-[260px]" for="gidx-url"><span class="block text-xs text-ink-muted">Báo thủ công 1 trang việc làm</span>
				<input id="gidx-url" type="url" name="url" required placeholder="https://congvienchuc.com/tuyen-dung/..." class="<?php echo esc_attr( cvc_admin_input_class() ); ?>"></label>
			<label for="gidx-type"><span class="block text-xs text-ink-muted">Loại</span><select id="gidx-type" name="type" class="<?php echo esc_attr( cvc_admin_input_class() ); ?> w-auto"><option value="URL_UPDATED">Thêm / cập nhật</option><option value="URL_DELETED">Gỡ (trang đã 404)</option></select></label>
			<button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-acc text-white">Xếp hàng</button>
		</form>
	<?php endif; ?>
	<nav class="flex flex-wrap gap-2 text-sm">
		<a href="<?php echo esc_url( cvc_gidx_url() ); ?>" class="px-3 py-1 rounded-lg border <?php echo '' === $tt ? 'bg-acc-soft text-acc border-acc-line font-bold' : 'bg-white border-slate-300'; ?>">Tất cả</a>
		<?php foreach ( $sl as $k => $l ) : ?><a href="<?php echo esc_url( cvc_gidx_url( array( 'tt' => $k ) ) ); ?>" class="px-3 py-1 rounded-lg border <?php echo $k === $tt ? 'bg-acc-soft text-acc border-acc-line font-bold' : 'bg-white border-slate-300'; ?>"><?php echo esc_html( $l . ' (' . (int) ( $c[ $k ] ?? 0 ) . ')' ); ?></a><?php endforeach; ?>
	</nav>
	<div class="dz-card overflow-x-auto">
		<table class="w-full text-sm">
			<thead><tr class="text-left text-xs text-ink-muted"><th class="py-1 pr-2">Trang</th><th class="pr-2">Loại</th><th class="pr-2">Lý do</th><th class="pr-2">Trạng thái</th><th class="pr-2">Lúc</th></tr></thead>
			<tbody>
				<?php if ( empty( $d['items'] ) ) : ?><tr><td colspan="5" class="py-3 text-ink-muted">Chưa có lượt nào.</td></tr><?php endif; ?>
				<?php foreach ( (array) ( $d['items'] ?? array() ) as $it ) : ?>
					<tr class="border-t border-slate-100 align-top">
						<td class="py-1 pr-2 break-all"><a class="text-acc" href="<?php echo esc_url( (string) $it['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://[^/]+#', '', (string) $it['url'] ) ); ?></a><?php echo ! empty( $it['error'] ) ? '<br><span class="text-xs text-red-700">' . esc_html( (string) $it['error'] ) . '</span>' : ''; ?></td>
						<td class="pr-2 whitespace-nowrap"><?php echo 'URL_DELETED' === $it['type'] ? 'Gỡ' : 'Cập nhật'; ?></td>
						<td class="pr-2 whitespace-nowrap"><?php echo esc_html( $rl[ $it['reason'] ] ?? (string) $it['reason'] ); ?></td>
						<td class="pr-2 whitespace-nowrap"><?php echo esc_html( ( $sl[ $it['status'] ] ?? (string) $it['status'] ) . ( ! empty( $it['http_code'] ) ? ' · ' . $it['http_code'] : '' ) ); ?></td>
						<td class="pr-2 whitespace-nowrap text-xs"><?php echo esc_html( cvc_admin_dt( (string) ( $it['sent_at'] ?: $it['created_at'] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/** Giờ Việt Nam từ thời điểm UTC của API. */
function cvc_admin_dt( string $utc ): string {
	$ts = strtotime( $utc . ( preg_match( '/[zZ]|[+-]\d\d:?\d\d$/', $utc ) ? '' : ' UTC' ) );

	return $ts ? wp_date( 'H:i d/m', $ts, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) : '—';
}

add_action( 'admin_post_cvc_admin_gidx', 'cvc_admin_handle_gidx' );
add_action( 'admin_post_nopriv_cvc_admin_gidx', 'cvc_admin_handle_gidx' );

function cvc_admin_handle_gidx(): void {
	check_admin_referer( 'cvc_admin_gidx' );
	$token = cvc_admin_require_staff();
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$api   = new CVC_Api_Client( null, 60 );
	if ( 'test' === $op ) {
		$r = $api->post( '/api/admin/google-indexing/test', array(), $token );
	} elseif ( in_array( $op, array( 'send', 'resume', 'queue' ), true ) ) {
		$r = $api->post( '/api/admin/google-indexing/action', array_filter( array(
			'op'   => $op,
			'url'  => 'queue' === $op ? esc_url_raw( wp_unslash( (string) ( $_POST['url'] ?? '' ) ) ) : null,
			'type' => 'queue' === $op && 'URL_DELETED' === ( $_POST['type'] ?? '' ) ? 'URL_DELETED' : null,
		) ), $token );
	} else {
		cvc_admin_redirect( cvc_gidx_url(), 'error', 'Thao tác không hợp lệ.' );
		return;
	}
	cvc_admin_redirect( cvc_gidx_url(), $r['ok'] ? 'success' : 'error', (string) ( $r['data']['message'] ?? cvc_admin_error_message( $r ) ) );
}
