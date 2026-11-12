<?php
/**
 * Tình trạng index Google - /quan-tri/thu-thap/?xem=index-google (12/11/2026).
 * Số liệu từ Search Console (URL Inspection API + lượt hiển thị 28 ngày), không dùng Indexing API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_idxcov_url( array $args = array() ): string {
	return cvc_admin_url( 'thu-thap', null, array( 'xem' => 'index-google' ) + $args );
}

/** Ngày (VN) từ thời điểm UTC. */
function cvc_idxcov_day( ?string $utc, string $fmt = 'd/m/Y' ): string {
	if ( ! $utc ) {
		return '—';
	}
	$ts = strtotime( $utc . ( preg_match( '/[zZ]|[+-]\d\d:?\d\d$/', $utc ) ? '' : ' UTC' ) );

	return $ts ? wp_date( $fmt, $ts, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) : '—';
}

function cvc_idxcov_pct( int $a, int $b ): string {
	return $b > 0 ? number_format_i18n( $a * 100 / $b, $a * 100 / $b < 10 ? 1 : 0 ) . '%' : '—';
}

function cvc_admin_render_index_coverage(): void {
	$res = cvc_admin_api_get( '/api/admin/index-coverage' );
	$d   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$btn = 'px-3 py-2 rounded-xl text-sm font-bold';
	$num = static fn ( $n ) => number_format_i18n( (int) $n );
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-ink">Tình trạng index Google</h1>
		<p class="text-sm text-ink-muted max-w-3xl">Đọc trực tiếp từ Search Console: mỗi 30 phút kiểm tra khoảng 20 trang bằng công cụ Kiểm tra URL (tối đa <?php echo esc_html( $num( $d['cap'] ?? 1200 ) ); ?> lượt / ngày), mỗi ngày đọc lại sitemap và lượt hiển thị 28 ngày. Bài blog được kiểm tra hết; các loại trang khác lấy mẫu ngẫu nhiên <?php echo (int) ( $d['sample'] ?? 80 ); ?> trang nên tỉ lệ là ước tính. Trang đã có lượt hiển thị trên Google chắc chắn đã được index.</p>
	</header>
	<?php cvc_ingestion_tabs( 'index-google' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<div class="dz-card text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></div>
		<?php return; ?>
	<?php endif; ?>
	<?php if ( empty( $d['configured'] ) ) : ?>
		<div class="dz-card text-sm text-red-700">Chưa có khóa tài khoản dịch vụ Google (dùng chung với Google Indexing).</div>
		<?php return; ?>
	<?php endif; ?>
	<div class="dz-card flex flex-wrap items-end gap-3 text-sm">
		<span>Thuộc tính <b><?php echo esc_html( (string) $d['property'] ); ?></b> · đồng bộ sitemap lúc <?php echo esc_html( cvc_idxcov_day( $d['synced_at'] ?? null, 'H:i d/m' ) ); ?> · đã dùng <?php echo esc_html( $num( $d['used_today'] ?? 0 ) . '/' . $num( $d['cap'] ?? 1200 ) ); ?> lượt hôm nay</span>
		<?php if ( ! empty( $d['paused_until'] ) ) : ?><span class="text-red-700">Tạm dừng tới <?php echo esc_html( cvc_idxcov_day( (string) $d['paused_until'], 'H:i d/m' ) ); ?></span><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-end gap-2 flex-1 min-w-[260px]">
			<?php wp_nonce_field( 'cvc_admin_idxcov' ); ?><input type="hidden" name="action" value="cvc_admin_idxcov">
			<label class="flex-1 min-w-[220px]" for="idxcov-url"><span class="block text-xs text-ink-muted">Kiểm tra ngay 1 trang</span>
				<input id="idxcov-url" type="url" name="url" required placeholder="https://congvienchuc.com/bai-viet/..." class="<?php echo esc_attr( cvc_admin_input_class() ); ?>"></label>
			<button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-acc text-white">Kiểm tra</button>
		</form>
	</div>

	<section class="dz-card overflow-x-auto">
		<h2 class="font-black text-ink mb-2">Theo loại trang</h2>
		<table class="w-full text-sm">
			<thead><tr class="text-left text-xs text-ink-muted">
				<th class="py-1 pr-3">Loại trang</th><th class="pr-3 text-right">Trong sitemap</th><th class="pr-3 text-right">Có hiển thị (28 ngày)</th>
				<th class="pr-3 text-right">Đã kiểm tra</th><th class="pr-3 text-right">Tỉ lệ index</th><th class="pr-3 text-right">Lượt nhấp 28 ngày</th>
			</tr></thead>
			<tbody class="tabular-nums">
			<?php foreach ( (array) ( $d['types'] ?? array() ) as $t ) : ?>
				<?php
				$rate = null === $t['rate'] ? null : (float) $t['rate'];
				$tone = null === $rate ? 'text-ink-muted' : ( $rate >= 70 ? 'text-emerald-700' : ( $rate >= 35 ? 'text-amber-700' : 'text-red-700' ) );
				?>
				<tr class="border-t border-slate-100">
					<td class="py-1 pr-3 font-bold"><?php echo esc_html( (string) $t['label'] ); ?></td>
					<td class="pr-3 text-right"><?php echo esc_html( $num( $t['total'] ) ); ?></td>
					<td class="pr-3 text-right"><?php echo esc_html( $num( $t['with_impr'] ) ); ?> <span class="text-xs text-ink-muted">(<?php echo esc_html( cvc_idxcov_pct( (int) $t['with_impr'], (int) $t['total'] ) ); ?>)</span></td>
					<td class="pr-3 text-right"><?php echo esc_html( $num( $t['checked'] ) ); ?><?php echo empty( $t['full'] ) ? ' <span class="text-xs text-ink-muted">mẫu</span>' : ''; ?></td>
					<td class="pr-3 text-right font-bold <?php echo esc_attr( $tone ); ?>"><?php echo null === $rate ? '—' : esc_html( number_format_i18n( $rate, 1 ) . '%' ); ?><?php echo null !== $rate && $t['checked'] < 30 ? ' <span class="text-xs font-normal text-ink-muted">ít mẫu</span>' : ''; ?></td>
					<td class="pr-3 text-right"><?php echo esc_html( $num( $t['clicks'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</section>

	<?php
	$b    = (array) ( $d['blog'] ?? array() );
	$bt   = current( array_filter( (array) ( $d['types'] ?? array() ), static fn ( $t ) => 'blog' === $t['type'] ) ) ?: array();
	$cov  = (array) ( $b['coverage'] ?? array() );
	$covn = array_sum( array_map( 'intval', $cov ) );
	?>
	<section class="dz-card space-y-3">
		<h2 class="font-black text-ink">Bài viết blog</h2>
		<div class="grid gap-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
			<?php
			foreach ( array(
				array( 'Đã index', $num( $bt['indexed'] ?? 0 ) . '/' . $num( $bt['checked'] ?? 0 ), 'trong số bài đã kiểm tra · ' . $num( $bt['total'] ?? 0 ) . ' bài' ),
				array( 'Kẹt quá 14 ngày', $num( $b['stuck_14d'] ?? 0 ), 'đăng hơn 14 ngày vẫn chưa index' ),
				array( 'Có hiển thị Google', $num( $bt['with_impr'] ?? 0 ), '28 ngày gần nhất' ),
			) as $k ) :
				?>
				<div class="rounded-xl border border-slate-200 p-3"><p class="text-xs text-ink-muted"><?php echo esc_html( $k[0] ); ?></p><p class="text-2xl font-black tabular-nums"><?php echo esc_html( $k[1] ); ?></p><p class="text-xs text-ink-muted"><?php echo esc_html( $k[2] ); ?></p></div>
			<?php endforeach; ?>
		</div>
		<?php if ( $cov ) : ?>
			<div class="space-y-1 text-sm">
				<p class="text-xs font-bold text-ink-muted">Trạng thái Google báo</p>
				<?php foreach ( $cov as $label => $n ) : ?>
					<div class="flex items-center gap-2"><span class="w-72 max-w-[55%] truncate" title="<?php echo esc_attr( (string) $label ); ?>"><?php echo esc_html( '' === (string) $label ? 'Không rõ' : (string) $label ); ?></span>
						<span class="flex-1 h-2 rounded bg-slate-100"><span class="block h-2 rounded bg-acc" style="width:<?php echo esc_attr( (string) round( (int) $n * 100 / max( 1, $covn ) ) ); ?>%"></span></span>
						<span class="w-10 text-right tabular-nums"><?php echo esc_html( $num( $n ) ); ?></span></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $b['cohorts'] ) ) : ?>
			<div class="overflow-x-auto">
				<p class="text-xs font-bold text-ink-muted mb-1">Theo tuần đăng bài (đã kiểm tra)</p>
				<table class="text-sm tabular-nums"><thead><tr class="text-left text-xs text-ink-muted"><th class="pr-4">Tuần</th><th class="pr-4 text-right">Bài</th><th class="pr-4 text-right">Đã index</th><th class="text-right">Tỉ lệ</th></tr></thead><tbody>
				<?php foreach ( (array) $b['cohorts'] as $c ) : ?>
					<tr class="border-t border-slate-100"><td class="pr-4"><?php echo esc_html( (string) $c['wk'] ); ?></td><td class="pr-4 text-right"><?php echo esc_html( $num( $c['n'] ) ); ?></td><td class="pr-4 text-right"><?php echo esc_html( $num( $c['idx'] ) ); ?></td><td class="text-right"><?php echo esc_html( cvc_idxcov_pct( (int) $c['idx'], (int) $c['n'] ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody></table>
			</div>
		<?php endif; ?>
		<div class="overflow-x-auto">
			<p class="text-xs font-bold text-ink-muted mb-1">Bài chưa được index (cũ nhất trước)</p>
			<table class="w-full text-sm">
				<thead><tr class="text-left text-xs text-ink-muted"><th class="py-1 pr-2">Bài</th><th class="pr-2">Đăng</th><th class="pr-2">Trạng thái</th><th class="pr-2">Google ghé lần cuối</th><th class="pr-2 text-right">Trang dẫn tới</th></tr></thead>
				<tbody>
				<?php if ( empty( $b['not_indexed'] ) ) : ?><tr><td colspan="5" class="py-2 text-ink-muted">Chưa có bài nào được xác nhận là chưa index.</td></tr><?php endif; ?>
				<?php foreach ( (array) ( $b['not_indexed'] ?? array() ) as $x ) : ?>
					<?php $age = ! empty( $x['published_at'] ) ? (int) floor( ( time() - strtotime( $x['published_at'] . ' UTC' ) ) / DAY_IN_SECONDS ) : null; ?>
					<tr class="border-t border-slate-100 align-top">
						<td class="py-1 pr-2 break-all"><a class="text-acc" href="<?php echo esc_url( (string) $x['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://[^/]+#', '', (string) $x['url'] ) ); ?></a></td>
						<td class="pr-2 whitespace-nowrap text-xs"><?php echo esc_html( cvc_idxcov_day( $x['published_at'] ?? null ) . ( null !== $age ? ' · ' . $age . ' ngày' : '' ) ); ?></td>
						<td class="pr-2 text-xs"><?php echo esc_html( (string) ( $x['coverage'] ?? '—' ) ); ?></td>
						<td class="pr-2 whitespace-nowrap text-xs"><?php echo esc_html( empty( $x['last_crawl_at'] ) ? 'chưa ghé' : cvc_idxcov_day( (string) $x['last_crawl_at'] ) ); ?></td>
						<td class="pr-2 text-right tabular-nums"><?php echo esc_html( (string) (int) ( $x['referring'] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>

	<?php
	$trend = array();
	foreach ( (array) ( $d['trend'] ?? array() ) as $r ) {
		$day                         = (string) $r['day'];
		$trend[ $day ]             ??= array( 'impr' => 0, 'total' => 0, 'b_idx' => 0, 'b_chk' => 0 );
		$trend[ $day ]['impr']      += (int) $r['with_impr'];
		$trend[ $day ]['total']     += (int) $r['total'];
		if ( 'blog' === $r['type'] ) {
			$trend[ $day ]['b_idx'] = (int) $r['indexed'];
			$trend[ $day ]['b_chk'] = (int) $r['checked'];
		}
	}
	krsort( $trend );
	?>
	<?php if ( $trend ) : ?>
		<section class="dz-card overflow-x-auto">
			<h2 class="font-black text-ink mb-2">Xu hướng theo ngày</h2>
			<table class="text-sm tabular-nums"><thead><tr class="text-left text-xs text-ink-muted"><th class="pr-4">Ngày</th><th class="pr-4 text-right">URL trong sitemap</th><th class="pr-4 text-right">Trang có hiển thị</th><th class="text-right">Blog đã index</th></tr></thead><tbody>
			<?php foreach ( array_slice( $trend, 0, 30, true ) as $day => $r ) : ?>
				<tr class="border-t border-slate-100"><td class="pr-4"><?php echo esc_html( wp_date( 'd/m', strtotime( $day ) ) ); ?></td><td class="pr-4 text-right"><?php echo esc_html( $num( $r['total'] ) ); ?></td><td class="pr-4 text-right"><?php echo esc_html( $num( $r['impr'] ) ); ?></td><td class="text-right"><?php echo esc_html( $num( $r['b_idx'] ) . '/' . $num( $r['b_chk'] ) ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</section>
	<?php endif; ?>

	<?php
	$lk    = (array) ( $d['links'] ?? array() );
	$slots = (array) ( $lk['slots'] ?? array() );
	$sl    = array( 'kt-inline' => 'Dòng gợi ý trong bài', 'kt-side' => 'Khối cột phải', 'kt-quiz' => 'Sau luyện nhanh 5 câu' );
	$kv    = (int) ( $lk['kt_views'] ?? 0 );
	?>
	<section class="dz-card space-y-3">
		<h2 class="font-black text-ink">Trang Điều luật → bài viết, đề luyện (30 ngày)</h2>
		<p class="text-xs text-ink-muted">Lượt xem trang Điều luật: <?php echo esc_html( $num( $kv ) ); ?>. Lượt bấm đếm từ 12/11/2026.</p>
		<div class="grid gap-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
			<?php foreach ( $sl as $k => $label ) : ?>
				<?php $c = (int) ( $slots[ $k ] ?? 0 ); ?>
				<div class="rounded-xl border border-slate-200 p-3"><p class="text-xs text-ink-muted"><?php echo esc_html( $label ); ?></p><p class="text-2xl font-black tabular-nums"><?php echo esc_html( $num( $c ) ); ?></p><p class="text-xs text-ink-muted"><?php echo esc_html( cvc_idxcov_pct( $c, $kv ) ); ?> lượt xem</p></div>
			<?php endforeach; ?>
		</div>
		<?php if ( ! empty( $lk['top'] ) ) : ?>
			<table class="w-full text-sm"><thead><tr class="text-left text-xs text-ink-muted"><th class="py-1 pr-2">Trang đích</th><th class="text-right">Lượt bấm</th></tr></thead><tbody>
			<?php foreach ( (array) $lk['top'] as $x ) : ?>
				<tr class="border-t border-slate-100"><td class="py-1 pr-2 break-all"><?php echo esc_html( (string) $x['dest'] ); ?></td><td class="text-right tabular-nums"><?php echo esc_html( $num( $x['clicks'] ) ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
	</section>

	<section class="dz-card overflow-x-auto">
		<h2 class="font-black text-ink mb-2">Lượt kiểm tra gần đây</h2>
		<table class="w-full text-sm">
			<thead><tr class="text-left text-xs text-ink-muted"><th class="py-1 pr-2">Trang</th><th class="pr-2">Loại</th><th class="pr-2">Kết quả</th><th class="pr-2">Lúc</th></tr></thead>
			<tbody>
			<?php foreach ( (array) ( $d['recent'] ?? array() ) as $x ) : ?>
				<tr class="border-t border-slate-100 align-top">
					<td class="py-1 pr-2 break-all"><a class="text-acc" href="<?php echo esc_url( (string) $x['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://[^/]+#', '', (string) $x['url'] ) ); ?></a></td>
					<td class="pr-2 text-xs whitespace-nowrap"><?php echo esc_html( (string) $x['type'] ); ?></td>
					<td class="pr-2 text-xs <?php echo ! empty( $x['indexed'] ) ? 'text-emerald-700' : 'text-red-700'; ?>"><?php echo esc_html( (string) ( $x['error'] ?: ( $x['coverage'] ?? '—' ) ) ); ?></td>
					<td class="pr-2 text-xs whitespace-nowrap"><?php echo esc_html( cvc_idxcov_day( (string) $x['checked_at'], 'H:i d/m' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</section>
	<?php
}

add_action( 'admin_post_cvc_admin_idxcov', 'cvc_admin_handle_idxcov' );
add_action( 'admin_post_nopriv_cvc_admin_idxcov', 'cvc_admin_handle_idxcov' );

function cvc_admin_handle_idxcov(): void {
	check_admin_referer( 'cvc_admin_idxcov' );
	$token = cvc_admin_require_staff();
	$api   = new CVC_Api_Client( null, 90 );
	$r     = $api->post( '/api/admin/index-coverage/inspect', array( 'url' => esc_url_raw( wp_unslash( (string) ( $_POST['url'] ?? '' ) ) ) ), $token );
	cvc_admin_redirect( cvc_idxcov_url(), $r['ok'] ? 'success' : 'error', (string) ( $r['data']['message'] ?? cvc_admin_error_message( $r ) ) );
}
