<?php
/**
 * Khu quản trị — Thu thập tin tuyển dụng (Phase 14).
 * URL: /quan-tri/thu-thap/ (tổng quan + hàng chờ), /quan-tri/thu-thap/{id}/ (chi tiết 1 tin).
 * Mọi thao tác đi qua admin-post → Laravel /api/admin/ingestion/* (backend kiểm tra quyền).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_ingestion_state_class( string $state ): string {
	return array(
		'qa_pending'        => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
		'published'         => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
		'validation_failed' => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
		'failed'            => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
		'duplicate'         => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
		'qa_rejected'       => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
		'ignored'           => 'bg-slate-500/20 text-slate-400 border-slate-500/30',
	)[ $state ] ?? 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30';
}

function cvc_ingestion_badge( string $state, string $label ): string {
	return '<span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[11px] font-bold whitespace-nowrap ' . esc_attr( cvc_ingestion_state_class( $state ) ) . '">' . esc_html( $label ) . '</span>';
}

function cvc_ingestion_date( $value ): string {
	if ( empty( $value ) ) {
		return '—';
	}
	$ts = strtotime( (string) $value );

	return $ts ? wp_date( 'd/m/Y', $ts ) : '—';
}

/**
 * Thanh tab của khu Thu thập tin (Phase 15).
 */
function cvc_ingestion_tabs( string $active ): void {
	$tabs = array(
		'queue'  => array( 'Hàng chờ', cvc_admin_url( 'thu-thap' ) ),
		'nguon'  => array( 'Nguồn & chỉ số', cvc_admin_url( 'thu-thap', null, array( 'xem' => 'nguon' ) ) ),
		'kiem-tra' => array( 'Kiểm tra mẫu', cvc_admin_url( 'thu-thap', null, array( 'xem' => 'kiem-tra' ) ) ),
		'on-tap'   => array( 'Ôn tập & văn bản', cvc_admin_url( 'thu-thap', null, array( 'xem' => 'on-tap' ) ) ),
	);
	echo '<nav class="flex flex-wrap gap-1 border-b border-slate-800" aria-label="Thu thập tin">';
	foreach ( $tabs as $key => $tab ) {
		$is = $key === $active;
		echo '<a href="' . esc_url( $tab[1] ) . '" class="px-3 py-2 text-sm font-bold border-b-2 -mb-px ' . ( $is ? 'border-cyan-400 text-cyan-200' : 'border-transparent text-slate-400 hover:text-slate-200' ) . '"' . ( $is ? ' aria-current="page"' : '' ) . '>' . esc_html( $tab[0] ) . '</a>';
	}
	echo '</nav>';
}

function cvc_ingestion_op_form( string $op, int $id, string $label, string $class, string $confirm = '', array $extra = array() ): string {
	$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="inline"' . ( '' !== $confirm ? ' data-cvc-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>';
	$html .= wp_nonce_field( 'cvc_admin_ingestion', '_wpnonce', true, false );
	$html .= '<input type="hidden" name="action" value="cvc_admin_ingestion"><input type="hidden" name="op" value="' . esc_attr( $op ) . '"><input type="hidden" name="id" value="' . esc_attr( (string) $id ) . '">';
	foreach ( $extra as $k => $v ) {
		$html .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '">';
	}
	$html .= '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';

	return $html;
}

/**
 * Trang tổng quan + hàng chờ.
 */
function cvc_admin_render_ingestion_index(): void {
	$state    = sanitize_text_field( wp_unslash( $_GET['state'] ?? 'qa_pending' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$page     = max( 1, absint( $_GET['trang'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$q        = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$overview = cvc_admin_api_get( '/api/admin/ingestion/overview' );
	$ov       = $overview['ok'] ? ( $overview['data']['data'] ?? array() ) : array();
	$list     = cvc_admin_api_get( '/api/admin/ingestion/items', array( 'state' => 'all' === $state ? '' : $state, 'page' => $page, 'per_page' => 25, 'q' => $q ) );
	$rows     = $list['ok'] ? (array) ( $list['data']['data'] ?? array() ) : array();
	$meta     = $list['ok'] ? (array) ( $list['data']['meta'] ?? array() ) : array();
	$can_pub  = cvc_admin_can( 'recruitment.publish' );
	$can_upd  = cvc_admin_can( 'recruitment.update' );
	$auto     = ! empty( $ov['auto_publish'] );
	$ai_ok    = ! empty( $ov['ai']['configured'] );
	$btn      = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	?>
	<header class="flex flex-wrap items-end justify-between gap-3">
		<div>
			<h1 class="text-2xl font-black text-white">Thu thập tin tuyển dụng</h1>
			<p class="text-sm text-slate-400">Tự động quét cổng thông tin nhà nước, tải công văn gốc, phân tích và đăng tin kèm nguồn trích dẫn.</p>
		</div>
		<div class="flex flex-wrap gap-2">
			<?php if ( cvc_admin_can( 'recruitment.view' ) ) : ?>
				<a class="<?php echo esc_attr( $btn ); ?> bg-slate-800 text-slate-200 hover:bg-slate-700" href="<?php echo esc_url( cvc_admin_url( 'sources' ) ); ?>"><i class="fa-solid fa-satellite-dish mr-1" aria-hidden="true"></i>Nguồn tin</a>
			<?php endif; ?>
			<?php if ( $can_upd ) : ?>
				<?php echo cvc_ingestion_op_form( 'run', 0, 'Chạy 1 lượt ngay', $btn . ' bg-cyan-500 text-slate-950 hover:bg-cyan-400' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</div>
	</header>
	<?php cvc_ingestion_tabs( 'queue' ); ?>

	<?php if ( ! $overview['ok'] ) : ?>
		<?php cvc_render_error_state( cvc_admin_error_message( $overview ) ); ?>
		<?php return; ?>
	<?php endif; ?>

	<section class="grid grid-cols-1 xl:grid-cols-[1.2fr_1fr] gap-4">
		<div class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-3">
			<div class="flex items-center justify-between gap-3">
				<h2 class="text-sm font-black text-white">Chế độ đăng tin</h2>
				<?php echo $auto ? '<span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">Đang tự động đăng</span>' : '<span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">Đang duyệt 1 click</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<p class="text-sm text-slate-300">
				<?php if ( $auto ) : ?>
					Tin đạt đủ điều kiện được <strong>đăng ngay</strong>: đúng là thông báo tuyển dụng, nguồn tin cậy, còn hạn nộp, xác định được tỉnh, cơ quan và chỉ tiêu, không trùng tin cũ. Tin chưa đạt nằm ở hàng chờ kèm lý do để bạn duyệt 1 click.
				<?php else : ?>
					Mọi tin thu thập được nằm ở hàng chờ. Bạn bấm <strong>Duyệt &amp; đăng</strong> để đăng ngay (kèm nguồn và công văn gốc).
				<?php endif; ?>
			</p>
			<?php if ( $can_pub ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-center gap-2">
					<?php wp_nonce_field( 'cvc_admin_ingestion' ); ?>
					<input type="hidden" name="action" value="cvc_admin_ingestion">
					<input type="hidden" name="op" value="settings">
					<input type="hidden" name="auto_publish" value="<?php echo $auto ? '0' : '1'; ?>">
					<button type="submit" class="<?php echo esc_attr( $btn ); ?> <?php echo $auto ? 'bg-slate-800 text-slate-200 hover:bg-slate-700' : 'bg-emerald-500 text-slate-950 hover:bg-emerald-400'; ?>">
						<?php echo $auto ? 'Chuyển sang duyệt 1 click' : 'Bật tự động đăng'; ?>
					</button>
				</form>
			<?php endif; ?>
			<p class="text-xs <?php echo $ai_ok ? 'text-slate-400' : 'text-amber-300'; ?>">
				<?php if ( $ai_ok ) : ?>
					<i class="fa-solid fa-robot mr-1" aria-hidden="true"></i>AI phân tích: <?php echo esc_html( (string) ( $ov['ai']['provider'] ?? '' ) . ' · ' . (string) ( $ov['ai']['model'] ?? '' ) ); ?> — đọc cả công văn scan (PDF).
				<?php else : ?>
					<i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>Chưa cấu hình AI. Hệ thống chỉ trích được thông tin có sẵn dạng chữ trên trang; công văn scan sẽ nằm ở hàng chờ.
					<a class="underline font-bold" href="<?php echo esc_url( home_url( '/quan-tri/cau-hinh-ai/' ) ); ?>">Cấu hình AI</a>
				<?php endif; ?>
			</p>
		</div>

		<dl class="grid grid-cols-2 sm:grid-cols-3 gap-2">
			<?php
			$tiles = array( 'qa_pending', 'published', 'event_pending', 'validation_failed', 'duplicate', 'failed', 'ignored', 'expired' );
			foreach ( $tiles as $t ) :
				$info = $ov['states'][ $t ] ?? array( 'label' => $t, 'total' => 0 );
				?>
				<a href="<?php echo esc_url( cvc_admin_url( 'thu-thap', null, array( 'state' => $t ) ) ); ?>" class="block bg-[#0A192F] border <?php echo $state === $t ? 'border-cyan-500/60' : 'border-slate-800'; ?> rounded-xl p-3 hover:border-slate-600">
					<dt class="text-[11px] font-bold text-slate-400"><?php echo esc_html( (string) $info['label'] ); ?></dt>
					<dd class="text-xl font-black text-white tabular-nums"><?php echo esc_html( number_format_i18n( (int) $info['total'] ) ); ?></dd>
				</a>
			<?php endforeach; ?>
			<div class="col-span-2 sm:col-span-3 text-xs text-slate-400 flex flex-wrap gap-x-4 gap-y-1 px-1">
				<span><?php echo esc_html( (int) ( $ov['sources']['active'] ?? 0 ) ); ?> nguồn đang chạy</span>
				<span><?php echo esc_html( (int) ( $ov['links_waiting'] ?? 0 ) ); ?> link chờ tải</span>
				<span><?php echo esc_html( (int) ( $ov['attachments'] ?? 0 ) ); ?> công văn đã lưu</span>
				<span><?php echo esc_html( (int) ( $ov['published_last_7_days'] ?? 0 ) ); ?> tin đăng 7 ngày qua</span>
				<span>Lượt gần nhất: <?php echo esc_html( cvc_admin_format( $ov['last_tick_at'] ?? null, 'datetime' ) ); ?></span>
			</div>
		</dl>
	</section>

	<?php $cvc_open = (array) ( $ov['open_unpublished'] ?? array() ); ?>
	<?php if ( ! empty( $cvc_open ) ) : ?>
		<section class="bg-rose-500/10 border border-rose-500/40 rounded-2xl p-5 space-y-3" aria-labelledby="cvc-open-unpub">
			<h2 id="cvc-open-unpub" class="text-sm font-black text-rose-200"><i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>Tin còn hạn nộp hồ sơ nhưng chưa đăng (<?php echo esc_html( (string) count( $cvc_open ) ); ?>)</h2>
			<p class="text-xs text-rose-100/80">Duyệt hoặc sửa sớm để không lỡ đợt tuyển. Sắp xếp theo hạn nộp gần nhất.</p>
			<ul class="space-y-2">
				<?php foreach ( $cvc_open as $o ) : ?>
					<li class="flex flex-wrap items-center gap-2 text-xs">
						<span class="px-2 py-0.5 rounded-full font-black <?php echo (int) $o['days_left'] <= 3 ? 'bg-rose-500 text-white' : 'bg-amber-500/20 text-amber-200'; ?>"><?php echo esc_html( 0 === (int) $o['days_left'] ? 'Hết hạn hôm nay' : 'Còn ' . (int) $o['days_left'] . ' ngày' ); ?></span>
						<a class="font-bold text-white hover:underline" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', (int) $o['id'] ) ); ?>">#<?php echo esc_html( (string) $o['id'] ); ?> <?php echo esc_html( (string) $o['title'] ); ?></a>
						<span class="text-slate-400">hạn <?php echo esc_html( cvc_admin_format( $o['deadline'] ?? null, 'date' ) ); ?></span>
						<?php if ( ! empty( $o['reason'] ) ) : ?><span class="text-amber-300/90">· <?php echo esc_html( (string) $o['reason'] ); ?></span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php cvc_admin_render_prefilter_panel( (array) ( $ov['prefilter'] ?? array() ), $can_pub ); ?>

	<?php if ( cvc_admin_can( 'recruitment.create' ) ) : ?>
		<details class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5">
			<summary class="cursor-pointer text-sm font-black text-white"><i class="fa-solid fa-file-arrow-up mr-1.5 text-cyan-300" aria-hidden="true"></i>Nhập tin từ file công văn (PDF/DOCX)</summary>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
				<?php wp_nonce_field( 'cvc_admin_ingestion' ); ?>
				<input type="hidden" name="action" value="cvc_admin_ingestion">
				<input type="hidden" name="op" value="import">
				<label class="block text-sm"><span class="text-slate-300 font-bold">File công văn <span class="text-rose-400">*</span></span>
					<input type="file" name="file" accept=".pdf,.doc,.docx" required class="mt-1 block w-full text-sm text-slate-300 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-slate-700 file:text-slate-100">
				</label>
				<label class="block text-sm"><span class="text-slate-300 font-bold">Link nguồn chính thức <span class="text-rose-400">*</span></span>
					<input type="url" name="source_url" required placeholder="https://sonoivu....gov.vn/..." class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
					<span class="text-xs text-slate-500">Trang đăng công văn gốc — dùng làm trích dẫn trên tin.</span>
				</label>
				<label class="block text-sm"><span class="text-slate-300 font-bold">Tỉnh/thành</span>
					<select name="province_id" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
						<option value="">— Để hệ thống tự nhận diện —</option>
						<?php foreach ( cvc_admin_provinces() as $pid => $pname ) : ?>
							<option value="<?php echo esc_attr( (string) $pid ); ?>"><?php echo esc_html( (string) $pname ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="block text-sm"><span class="text-slate-300 font-bold">Tiêu đề (không bắt buộc)</span>
					<input type="text" name="title" maxlength="500" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
				</label>
				<div class="md:col-span-2"><button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-cyan-500 text-slate-950 hover:bg-cyan-400">Tải lên &amp; phân tích</button></div>
			</form>
		</details>
	<?php endif; ?>

	<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-4" aria-labelledby="cvc-ing-queue">
		<div class="flex flex-wrap items-center justify-between gap-3">
			<h2 id="cvc-ing-queue" class="text-sm font-black text-white">
				<?php echo esc_html( 'all' === $state ? 'Tất cả tin thu thập' : ( (string) ( $ov['states'][ $state ]['label'] ?? 'Hàng chờ' ) ) ); ?>
				<span class="text-slate-500 font-bold">(<?php echo esc_html( (string) ( $meta['total'] ?? 0 ) ); ?>)</span>
			</h2>
			<form method="get" action="<?php echo esc_url( cvc_admin_url( 'thu-thap' ) ); ?>" class="flex gap-2">
				<input type="hidden" name="state" value="<?php echo esc_attr( $state ); ?>">
				<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Tìm tiêu đề, cơ quan, link…" class="<?php echo esc_attr( cvc_admin_input_class() ); ?> !mt-0 w-64">
				<button class="<?php echo esc_attr( $btn ); ?> bg-slate-800 text-slate-200">Tìm</button>
				<a class="<?php echo esc_attr( $btn ); ?> bg-slate-800 text-slate-300" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', null, array( 'state' => 'all' ) ) ); ?>">Tất cả</a>
			</form>
		</div>

		<?php if ( ! $list['ok'] ) : ?>
			<?php cvc_render_error_state( cvc_admin_error_message( $list ) ); ?>
		<?php elseif ( empty( $rows ) ) : ?>
			<p class="text-sm text-slate-400">Không có tin nào ở mục này.</p>
		<?php else : ?>
			<div class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="text-[11px] uppercase tracking-wider text-slate-400 text-left">
						<tr>
							<th class="py-2 pr-3">Tin</th>
							<th class="py-2 pr-3">Tỉnh · Hạn nộp</th>
							<th class="py-2 pr-3">Trạng thái</th>
							<th class="py-2 text-right">Thao tác</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-800">
						<?php foreach ( $rows as $row ) : ?>
							<tr class="align-top">
								<td class="py-3 pr-3 min-w-[320px]">
									<a class="font-bold text-slate-100 hover:text-cyan-300" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', (int) $row['id'] ) ); ?>"><?php echo esc_html( (string) ( $row['title'] ?? '(chưa có tiêu đề)' ) ); ?></a>
									<div class="text-xs text-slate-400 mt-0.5">
										<?php echo esc_html( (string) ( $row['agency_name'] ?? '' ) ); ?>
										<?php echo esc_html( ( ! empty( $row['agency_name'] ) ? ' · ' : '' ) . (string) ( $row['source']['name'] ?? '' ) ); ?>
										<?php if ( ! empty( $row['attachments_count'] ) ) : ?>
											· <i class="fa-solid fa-paperclip" aria-hidden="true"></i> <?php echo esc_html( (string) $row['attachments_count'] ); ?> công văn
										<?php endif; ?>
									</div>
									<?php if ( ! empty( $row['publish_block_reason'] ) && 'published' !== $row['state'] ) : ?>
										<div class="text-xs text-amber-300/90 mt-1">Chưa tự đăng: <?php echo esc_html( (string) $row['publish_block_reason'] ); ?></div>
									<?php endif; ?>
								</td>
								<td class="py-3 pr-3 text-xs text-slate-300 whitespace-nowrap">
									<?php echo esc_html( (string) ( $row['province_name'] ?? '—' ) ); ?><br>
									<span class="text-slate-400">Hạn: <?php echo esc_html( cvc_ingestion_date( $row['application_deadline'] ?? null ) ); ?></span>
									<?php if ( ! empty( $row['total_positions'] ) ) : ?>
										<br><span class="text-slate-400"><?php echo esc_html( (string) $row['total_positions'] ); ?> chỉ tiêu</span>
									<?php endif; ?>
								</td>
								<td class="py-3 pr-3"><?php echo cvc_ingestion_badge( (string) $row['state'], (string) $row['state_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
								<td class="py-3 text-right whitespace-nowrap space-x-1">
									<?php if ( 'published' === $row['state'] && ! empty( $row['recruitment']['slug'] ) ) : ?>
										<a class="text-xs font-bold text-emerald-300" href="<?php echo esc_url( cvc_recruitment_url( (string) $row['recruitment']['slug'] ) ); ?>" target="_blank" rel="noopener">Xem tin</a>
									<?php elseif ( $can_pub && in_array( $row['state'], array( 'qa_pending', 'validation_failed', 'duplicate', 'ignored' ), true ) ) : ?>
										<?php echo cvc_ingestion_op_form( 'approve', (int) $row['id'], 'Duyệt & đăng', $btn . ' bg-emerald-500 text-slate-950 hover:bg-emerald-400' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<?php if ( 'qa_pending' === $row['state'] ) : ?>
											<?php echo cvc_ingestion_op_form( 'reject', (int) $row['id'], 'Loại', $btn . ' bg-slate-800 text-slate-300 hover:bg-slate-700', 'Loại tin này khỏi hàng chờ?' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<?php endif; ?>
									<?php endif; ?>
									<a class="text-xs font-bold text-cyan-300 ml-1" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', (int) $row['id'] ) ); ?>">Chi tiết</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ( (int) ( $meta['last_page'] ?? 1 ) > 1 ) : ?>
				<nav class="flex gap-2 text-sm" aria-label="Phân trang">
					<?php for ( $p = 1; $p <= min( 30, (int) $meta['last_page'] ); $p++ ) : ?>
						<a class="px-2.5 py-1 rounded-lg <?php echo $p === $page ? 'bg-cyan-500 text-slate-950 font-bold' : 'bg-slate-800 text-slate-300'; ?>" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', null, array_filter( array( 'state' => $state, 'q' => $q, 'trang' => $p ) ) ) ); ?>"><?php echo esc_html( (string) $p ); ?></a>
					<?php endfor; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	</section>

	<?php if ( ! empty( $ov['last_runs'] ) ) : ?>
		<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5" aria-labelledby="cvc-ing-runs">
			<h2 id="cvc-ing-runs" class="text-sm font-black text-white mb-3">Lượt thu thập gần đây</h2>
			<ul class="text-xs text-slate-300 space-y-1.5">
				<?php foreach ( (array) $ov['last_runs'] as $run ) : ?>
					<li class="flex flex-wrap justify-between gap-2">
						<span><strong class="text-slate-100"><?php echo esc_html( (string) ( $run['source'] ?? '' ) ); ?></strong> · <?php echo esc_html( sprintf( '%d trang, %d lỗi, %d tin đăng', (int) $run['items_processed'], (int) $run['items_failed'], (int) $run['items_published'] ) ); ?></span>
						<span class="text-slate-500"><?php echo esc_html( cvc_admin_format( $run['started_at'] ?? null, 'datetime' ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>
	<?php
}

/**
 * Chi tiết 1 tin thu thập.
 */
function cvc_admin_render_ingestion_detail( int $id ): void {
	$result = cvc_admin_api_get( '/api/admin/ingestion/items/' . $id );
	$back   = cvc_admin_url( 'thu-thap' );
	echo '<p><a class="text-sm text-cyan-300 font-bold" href="' . esc_url( $back ) . '">&larr; Hàng chờ thu thập</a></p>';

	if ( ! $result['ok'] ) {
		cvc_render_error_state( 404 === (int) $result['status'] ? 'Không tìm thấy tin này.' : cvc_admin_error_message( $result ) );
		return;
	}

	$it       = (array) ( $result['data']['data'] ?? array() );
	$r        = (array) ( $it['resolved'] ?? array() );
	$ready    = (array) ( $it['readiness'] ?? array() );
	$can_pub  = cvc_admin_can( 'recruitment.publish' );
	$can_upd  = cvc_admin_can( 'recruitment.update' );
	$btn      = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$editable = 'published' !== ( $it['state'] ?? '' );
	$input    = cvc_admin_input_class();
	$pos_text = '';
	foreach ( (array) ( $r['positions'] ?? array() ) as $p ) {
		$pos_text .= trim( (string) ( $p['name'] ?? '' ) ) . ' | ' . (int) ( $p['quantity'] ?? 0 ) . ( ! empty( $p['education_level'] ) ? ' | ' . $p['education_level'] : '' ) . "\n";
	}
	$date_val = static fn ( $v ) => ! empty( $v ) ? substr( (string) $v, 0, 10 ) : '';
	?>
	<header class="space-y-2">
		<div class="flex flex-wrap items-center gap-2">
			<?php echo cvc_ingestion_badge( (string) $it['state'], (string) $it['state_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( ! empty( $it['notice_type_label'] ) ) : ?>
				<span class="text-xs text-slate-400"><?php echo esc_html( (string) $it['notice_type_label'] ); ?></span>
			<?php endif; ?>
			<?php if ( 'done' === ( $it['analysis_status'] ?? '' ) ) : ?>
				<span class="text-xs text-slate-400"><i class="fa-solid fa-robot" aria-hidden="true"></i> AI <?php echo isset( $it['confidence'] ) ? esc_html( round( (float) $it['confidence'] * 100 ) . '%' ) : ''; ?></span>
			<?php endif; ?>
		</div>
		<h1 class="text-xl font-black text-white"><?php echo esc_html( (string) ( $it['title'] ?? '(chưa có tiêu đề)' ) ); ?></h1>
		<p class="text-xs text-slate-400 break-all">
			Nguồn: <?php echo esc_html( (string) ( $it['source']['name'] ?? '' ) ); ?> ·
			<a class="text-cyan-300 underline" href="<?php echo esc_url( (string) $it['source_url'] ); ?>" target="_blank" rel="noopener nofollow">Mở trang gốc</a>
			<?php if ( ! empty( $it['recruitment']['slug'] ) ) : ?>
				· <a class="text-emerald-300 font-bold" href="<?php echo esc_url( cvc_recruitment_url( (string) $it['recruitment']['slug'] ) ); ?>" target="_blank" rel="noopener">Xem tin đã đăng</a>
				· <a class="text-cyan-300" href="<?php echo esc_url( cvc_admin_url( 'recruitments', (int) $it['recruitment']['id'] ) ); ?>">Sửa tin</a>
			<?php endif; ?>
		</p>
	</header>

	<?php if ( $editable && ! empty( $ready ) ) : ?>
		<section class="rounded-2xl p-4 border <?php echo ! empty( $ready['ready'] ) ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-amber-500/30 bg-amber-500/5'; ?>">
			<div class="flex flex-wrap items-center justify-between gap-3">
				<div class="text-sm">
					<?php if ( ! empty( $ready['ready'] ) ) : ?>
						<p class="font-bold text-emerald-300">Đủ điều kiện đăng.</p>
					<?php else : ?>
						<p class="font-bold text-amber-300">Chưa đăng được: <?php echo esc_html( implode( '; ', (array) $ready['errors'] ) ); ?>.</p>
					<?php endif; ?>
					<p class="text-xs text-slate-400 mt-1">
						Tỉnh: <?php echo esc_html( (string) ( $ready['province'] ?? '—' ) ); ?> ·
						Cơ quan: <?php echo esc_html( (string) ( $ready['agency'] ?? '—' ) ); ?><?php echo ! empty( $ready['agency'] ) && empty( $ready['agency_exists'] ) ? esc_html( ' (sẽ tạo mới)' ) : ''; ?> ·
						<?php echo esc_html( count( (array) ( $ready['positions'] ?? array() ) ) . ' vị trí' ); ?>
					</p>
					<?php if ( ! empty( $ready['auto_gate_failures'] ) ) : ?>
						<p class="text-xs text-slate-400 mt-1">Không tự đăng vì: <?php echo esc_html( implode( '; ', (array) $ready['auto_gate_failures'] ) ); ?>.</p>
					<?php endif; ?>
				</div>
				<div class="flex gap-2">
					<?php if ( $can_pub ) : ?>
						<?php echo cvc_ingestion_op_form( 'approve', $id, 'Duyệt & đăng', $btn . ' bg-emerald-500 text-slate-950 hover:bg-emerald-400' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo cvc_ingestion_op_form( 'reject', $id, 'Loại', $btn . ' bg-slate-800 text-slate-300 hover:bg-slate-700', 'Loại tin này khỏi hàng chờ?' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endif; ?>
					<?php if ( $can_upd ) : ?>
						<?php echo cvc_ingestion_op_form( 'reprocess', $id, 'Phân tích lại', $btn . ' bg-slate-800 text-slate-300 hover:bg-slate-700' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="grid grid-cols-1 xl:grid-cols-[1.3fr_1fr] gap-4 items-start">
		<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5" aria-labelledby="cvc-ing-edit">
			<h2 id="cvc-ing-edit" class="text-sm font-black text-white mb-3"><?php echo $editable ? 'Thông tin trích xuất (sửa được trước khi đăng)' : 'Thông tin đã đăng'; ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
				<?php wp_nonce_field( 'cvc_admin_ingestion' ); ?>
				<input type="hidden" name="action" value="cvc_admin_ingestion">
				<input type="hidden" name="op" value="curate">
				<input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>">
				<?php $dis = $editable && $can_upd ? '' : ' disabled'; ?>
				<?php
				// GD1: bang chung - doan van ban goc chua gia tri da trich.
				$ev_labels = array( 'application_deadline' => 'Hạn nộp', 'application_start_date' => 'Bắt đầu nhận', 'announcement_date' => 'Ngày thông báo', 'total_positions' => 'Tổng chỉ tiêu', 'agency_name' => 'Cơ quan', 'positions' => 'Biểu vị trí' );
				$ev_all    = is_array( $r['_evidence'] ?? null ) ? $r['_evidence'] : array();
				?>
				<?php if ( ! empty( $ev_all ) ) : ?>
					<div class="md:col-span-2 rounded-xl border border-slate-700 bg-slate-900/60 p-3 space-y-1.5 text-xs">
						<p class="font-bold text-slate-200">Đối chiếu văn bản gốc</p>
						<?php foreach ( $ev_labels as $ek => $el ) : ?>
							<?php if ( ! array_key_exists( $ek, $ev_all ) ) { continue; } $ev = $ev_all[ $ek ]; ?>
							<p class="<?php echo $ev ? 'text-slate-300' : 'text-amber-300'; ?>">
								<strong><?php echo esc_html( $el ); ?>:</strong>
								<?php if ( $ev ) : ?>
									“<?php echo esc_html( (string) ( $ev['snippet'] ?? '' ) ); ?>” <span class="text-slate-500">- <?php echo esc_html( (string) ( $ev['source'] ?? '' ) ); ?></span>
								<?php else : ?>
									chưa tìm thấy trong trang tin / file - kiểm tra lại trước khi đăng
								<?php endif; ?>
							</p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<label class="md:col-span-2 text-sm"><span class="font-bold text-slate-300">Tiêu đề</span><input name="title" class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( (string) ( $r['title'] ?? '' ) ); ?>"<?php echo $dis; // phpcs:ignore ?>></label>
				<label class="md:col-span-2 text-sm"><span class="font-bold text-slate-300">Cơ quan tuyển dụng</span><input name="agency_name" class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( (string) ( $r['agency_name'] ?? '' ) ); ?>"<?php echo $dis; // phpcs:ignore ?>><span class="text-xs text-slate-500">Ghi đúng tên đầy đủ; chưa có trong hệ thống sẽ được tạo mới theo tỉnh.</span></label>
				<label class="text-sm"><span class="font-bold text-slate-300">Tỉnh/thành</span>
					<select name="province_name" class="<?php echo esc_attr( $input ); ?>"<?php echo $dis; // phpcs:ignore ?>>
						<option value="">— Chưa xác định —</option>
						<?php foreach ( cvc_admin_provinces() as $pname ) : ?>
							<option value="<?php echo esc_attr( (string) $pname ); ?>" <?php selected( (string) ( $r['province_name'] ?? '' ), (string) $pname ); ?>><?php echo esc_html( (string) $pname ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="text-sm"><span class="font-bold text-slate-300">Loại</span>
					<select name="recruitment_type" class="<?php echo esc_attr( $input ); ?>"<?php echo $dis; // phpcs:ignore ?>>
						<?php foreach ( array( '' => '— Chưa rõ —', 'civil_servant' => 'Công chức', 'public_employee' => 'Viên chức', 'other' => 'Khác' ) as $k => $v ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) ( $r['recruitment_type'] ?? '' ), $k ); ?>><?php echo esc_html( $v ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="text-sm"><span class="font-bold text-slate-300">Ngày thông báo</span><input type="date" name="announcement_date" class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( $date_val( $r['announcement_date'] ?? '' ) ); ?>"<?php echo $dis; // phpcs:ignore ?>></label>
				<label class="text-sm"><span class="font-bold text-slate-300">Bắt đầu nhận hồ sơ</span><input type="date" name="application_start_date" class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( $date_val( $r['application_start_date'] ?? '' ) ); ?>"<?php echo $dis; // phpcs:ignore ?>></label>
				<label class="text-sm"><span class="font-bold text-slate-300">Hạn nộp hồ sơ</span><input type="date" name="application_deadline" class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( $date_val( $r['application_deadline'] ?? '' ) ); ?>"<?php echo $dis; // phpcs:ignore ?>></label>
				<label class="text-sm"><span class="font-bold text-slate-300">Tổng chỉ tiêu</span><input type="number" min="1" name="total_positions" class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( (string) ( $r['total_positions'] ?? '' ) ); ?>"<?php echo $dis; // phpcs:ignore ?>></label>
				<label class="md:col-span-2 text-sm"><span class="font-bold text-slate-300">Địa điểm / nơi nộp hồ sơ</span><input name="location" class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( (string) ( $r['location'] ?? '' ) ); ?>"<?php echo $dis; // phpcs:ignore ?>></label>
				<label class="md:col-span-2 text-sm"><span class="font-bold text-slate-300">Vị trí tuyển dụng</span>
					<textarea name="positions" rows="5" class="<?php echo esc_attr( $input ); ?> font-mono text-xs" placeholder="Mỗi dòng: Tên vị trí | số chỉ tiêu | trình độ"<?php echo $dis; // phpcs:ignore ?>><?php echo esc_textarea( $pos_text ); ?></textarea>
					<span class="text-xs text-slate-500">Mỗi dòng 1 vị trí: <code>Tên vị trí | số chỉ tiêu | trình độ</code>. Để trống sẽ dùng 1 dòng "Các vị trí theo thông báo" với tổng chỉ tiêu.</span>
				</label>
				<label class="md:col-span-2 text-sm"><span class="font-bold text-slate-300">Tóm tắt</span><textarea name="summary" rows="4" class="<?php echo esc_attr( $input ); ?>"<?php echo $dis; // phpcs:ignore ?>><?php echo esc_textarea( (string) ( $r['summary'] ?? '' ) ); ?></textarea></label>
				<?php if ( '' === $dis ) : ?>
					<div class="md:col-span-2"><button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-cyan-500 text-slate-950 hover:bg-cyan-400">Lưu chỉnh sửa</button></div>
				<?php endif; ?>
			</form>
		</section>

		<div class="space-y-4">
			<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5" aria-labelledby="cvc-ing-files">
				<h2 id="cvc-ing-files" class="text-sm font-black text-white mb-3">Công văn đính kèm</h2>
				<?php if ( empty( $it['attachments'] ) ) : ?>
					<p class="text-sm text-slate-400">Trang gốc không có file đính kèm.</p>
				<?php else : ?>
					<ul class="space-y-2 text-sm">
						<?php foreach ( (array) $it['attachments'] as $a ) : ?>
							<li class="border border-slate-800 rounded-xl p-3">
								<div class="font-bold text-slate-100 break-all"><?php echo esc_html( (string) ( $a['title'] ?: $a['filename'] ) ); ?></div>
								<div class="text-xs text-slate-400 mt-0.5">
									<?php echo esc_html( strtoupper( (string) $a['extension'] ) . ' · ' . size_format( (int) $a['size'] ) ); ?>
									<?php if ( 'downloaded' === $a['status'] ) : ?>
										· <?php echo (int) $a['text_length'] > 0 ? esc_html( number_format_i18n( (int) $a['text_length'] ) . ' ký tự chữ' ) : esc_html( 'bản scan (không có lớp chữ)' ); ?>
									<?php else : ?>
										· <span class="text-rose-300">Không tải được: <?php echo esc_html( (string) $a['error'] ); ?></span>
									<?php endif; ?>
								</div>
								<div class="flex gap-3 mt-1 text-xs">
									<?php if ( 'downloaded' === $a['status'] ) : ?>
										<a class="text-cyan-300 font-bold" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cvc_admin_ingestion_file&id=' . (int) $a['id'] ), 'cvc_admin_ingestion_file' ) ); ?>">Tải bản lưu</a>
									<?php endif; ?>
									<?php if ( ! empty( $a['url'] ) ) : ?>
										<a class="text-slate-300 underline" href="<?php echo esc_url( (string) $a['url'] ); ?>" target="_blank" rel="noopener nofollow">Link gốc</a>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $it['content_excerpt'] ) ) : ?>
				<details class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5">
					<summary class="cursor-pointer text-sm font-black text-white">Nội dung trang gốc</summary>
					<div class="mt-3 max-h-96 overflow-y-auto whitespace-pre-line text-xs text-slate-300 leading-relaxed"><?php echo esc_html( (string) $it['content_excerpt'] ); ?></div>
				</details>
			<?php endif; ?>

			<details class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5">
				<summary class="cursor-pointer text-sm font-black text-white">Nhật ký xử lý</summary>
				<ul class="mt-3 space-y-1.5 text-xs text-slate-300">
					<?php foreach ( (array) ( $it['logs'] ?? array() ) as $log ) : ?>
						<li class="flex justify-between gap-3">
							<span><strong class="text-slate-100"><?php echo esc_html( (string) $log['event'] ); ?></strong> <?php echo esc_html( (string) ( $log['message'] ?? '' ) ); ?> <span class="text-slate-500"><?php echo esc_html( (string) ( $log['actor'] ?? '' ) ); ?></span></span>
							<span class="text-slate-500 shrink-0"><?php echo esc_html( cvc_admin_format( $log['created_at'] ?? null, 'datetime' ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		</div>
	</div>
	<?php
}

/**
 * Bo loc truoc khi tai: che do (tat / chay thu / ap dung), nguong, thong ke 14 ngay.
 *
 * @param array<string, mixed> $pf Du lieu overview.prefilter.
 */
function cvc_admin_render_prefilter_panel( array $pf, bool $can_edit ): void {
	if ( empty( $pf ) ) {
		return;
	}
	$modes  = array(
		'off'     => 'Tắt',
		'shadow'  => 'Chạy thử (chỉ ghi lại, vẫn tải như cũ)',
		'enforce' => 'Áp dụng (bỏ qua thật)',
	);
	$mode   = (string) ( $pf['mode'] ?? 'shadow' );
	$badge  = array(
		'off'     => 'bg-slate-700/40 text-slate-300 border-slate-600',
		'shadow'  => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
		'enforce' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
	);
	$stages = array(
		'listing'      => 'Trang danh sách',
		'page'         => 'Trang chi tiết',
		'attachment'   => 'Đính kèm trùng văn bản',
		'legal_expiry' => 'Văn bản hết hiệu lực',
		'ocr_budget'   => 'Vượt ngân sách OCR',
	);
	$counts = array();
	foreach ( (array) ( $pf['stats_14d'] ?? array() ) as $r ) {
		$counts[ (string) $r['stage'] ][ (string) $r['decision'] ] = (int) $r['total'];
	}
	$btn = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	?>
	<details class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5" <?php echo 'shadow' === $mode ? 'open' : ''; ?>>
		<summary class="cursor-pointer text-sm font-black text-white flex flex-wrap items-center gap-2">
			<i class="fa-solid fa-filter text-cyan-300" aria-hidden="true"></i>Bộ lọc trước khi tải
			<span class="px-2 py-0.5 rounded-full text-[11px] font-bold border <?php echo esc_attr( $badge[ $mode ] ?? $badge['shadow'] ); ?>"><?php echo esc_html( $modes[ $mode ] ?? $mode ); ?></span>
		</summary>
		<p class="text-sm text-slate-400 mt-3">Tin hết hạn nộp / quá cũ, văn bản hết hiệu lực và file trùng văn bản đã có sẽ không tải đính kèm, không OCR, không gọi AI. Nên để <strong>Chạy thử</strong> 1–2 tuần, xem bảng dưới (cột “Bỏ qua”) rồi mới bật <strong>Áp dụng</strong>.</p>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
			<div class="overflow-x-auto">
				<table class="w-full text-xs text-slate-300">
					<caption class="text-left text-[11px] font-bold text-slate-400 mb-1">Quyết định 14 ngày qua</caption>
					<thead><tr class="text-slate-500"><th class="text-left py-1">Tầng</th><th class="text-right">Bỏ qua</th><th class="text-right">Tải</th><th class="text-right">Chưa rõ</th></tr></thead>
					<tbody>
					<?php foreach ( $stages as $k => $label ) : ?>
						<tr class="border-t border-slate-800">
							<td class="py-1"><?php echo esc_html( $label ); ?></td>
							<td class="text-right tabular-nums"><?php echo esc_html( (string) ( $counts[ $k ]['skip'] ?? 0 ) ); ?></td>
							<td class="text-right tabular-nums"><?php echo esc_html( (string) ( $counts[ $k ]['fetch'] ?? 0 ) ); ?></td>
							<td class="text-right tabular-nums"><?php echo esc_html( (string) ( $counts[ $k ]['unknown'] ?? 0 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="text-[11px] text-slate-500 mt-2">
					Tin hết hạn không tải: <?php echo esc_html( (string) (int) ( $pf['expired_items'] ?? 0 ) ); ?> ·
					Đính kèm dùng văn bản có sẵn: <?php echo esc_html( (string) (int) ( $pf['linked_attachments'] ?? 0 ) ); ?> ·
					File dùng lại (không tải lại): <?php echo esc_html( (string) (int) ( $pf['reused_attachments'] ?? 0 ) ); ?>
				</p>
			</div>
			<?php if ( $can_edit ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="grid grid-cols-2 gap-3">
					<?php wp_nonce_field( 'cvc_admin_ingestion' ); ?>
					<input type="hidden" name="action" value="cvc_admin_ingestion">
					<input type="hidden" name="op" value="prefilter">
					<label class="col-span-2 block text-xs font-bold text-slate-300" for="cvc-pf-mode">Chế độ
						<select id="cvc-pf-mode" name="prefilter_mode" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
							<?php foreach ( $modes as $mv => $ml ) : ?>
								<option value="<?php echo esc_attr( $mv ); ?>" <?php selected( $mode, $mv ); ?>><?php echo esc_html( $ml ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="block text-xs font-bold text-slate-300" for="cvc-pf-age">Tin quá (tháng)
						<input id="cvc-pf-age" type="number" min="1" max="60" name="max_age_months" value="<?php echo esc_attr( (string) (int) ( $pf['max_age_months'] ?? 6 ) ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
					</label>
					<label class="block text-xs font-bold text-slate-300" for="cvc-pf-event">Lịch thi/kết quả quá (tháng)
						<input id="cvc-pf-event" type="number" min="1" max="60" name="event_max_age_months" value="<?php echo esc_attr( (string) (int) ( $pf['event_max_age_months'] ?? 12 ) ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
					</label>
					<label class="block text-xs font-bold text-slate-300" for="cvc-pf-grace">Dự phòng sau hạn nộp (ngày)
						<input id="cvc-pf-grace" type="number" min="0" max="60" name="grace_days" value="<?php echo esc_attr( (string) (int) ( $pf['grace_days'] ?? 3 ) ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
					</label>
					<label class="block text-xs font-bold text-slate-300" for="cvc-pf-ocr">Ngân sách OCR / văn bản (trang)
						<input id="cvc-pf-ocr" type="number" min="5" max="2000" name="ocr_max_pages" value="<?php echo esc_attr( (string) (int) ( $pf['ocr_max_pages'] ?? 80 ) ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
					</label>
					<div class="col-span-2"><button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-cyan-500 text-slate-950 hover:bg-cyan-400">Lưu bộ lọc</button></div>
				</form>
			<?php endif; ?>
		</div>
	</details>
	<?php
}

/* ------------------------------------------------------------------ */
/* Handlers                                                            */
/* ------------------------------------------------------------------ */

add_action( 'admin_post_cvc_admin_ingestion', 'cvc_admin_handle_ingestion' );
add_action( 'admin_post_nopriv_cvc_admin_ingestion', 'cvc_admin_handle_ingestion' );

function cvc_admin_handle_ingestion(): void {
	check_admin_referer( 'cvc_admin_ingestion' );
	$token  = cvc_admin_require_staff();
	$op     = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );
	$id     = absint( $_POST['id'] ?? 0 );
	$index  = cvc_admin_url( 'thu-thap' );
	$detail = $id ? cvc_admin_url( 'thu-thap', $id ) : $index;
	$back   = wp_get_referer() ?: $detail;
	$client = cvc_admin_client();

	switch ( $op ) {
		case 'settings':
			$on     = '1' === (string) ( $_POST['auto_publish'] ?? '0' );
			$result = $client->put( '/api/admin/ingestion/settings', array( 'auto_publish' => $on ), $token );
			cvc_admin_redirect( $index, $result['ok'] ? 'success' : 'error', $result['ok'] ? (string) ( $result['data']['message'] ?? 'Đã lưu.' ) : cvc_admin_error_message( $result ) );
			return;

		case 'prefilter':
			$payload = array( 'prefilter_mode' => sanitize_key( wp_unslash( $_POST['prefilter_mode'] ?? 'shadow' ) ) );
			foreach ( array( 'max_age_months', 'event_max_age_months', 'grace_days', 'ocr_max_pages' ) as $f ) {
				if ( isset( $_POST[ $f ] ) && '' !== $_POST[ $f ] ) {
					$payload[ $f ] = absint( $_POST[ $f ] );
				}
			}
			$result = $client->put( '/api/admin/ingestion/settings', $payload, $token );
			cvc_admin_redirect( $index, $result['ok'] ? 'success' : 'error', $result['ok'] ? (string) ( $result['data']['message'] ?? 'Đã lưu.' ) : cvc_admin_error_message( $result ) );
			return;

		case 'approve':
			$result = $client->post( '/api/admin/ingestion/items/' . $id . '/approve', array(), $token );
			if ( $result['ok'] ) {
				cvc_admin_redirect( $back, 'success', 'Đã duyệt và đăng tin.' );
			} else {
				cvc_admin_redirect( $detail, 'error', (string) ( $result['data']['message'] ?? cvc_admin_error_message( $result ) ) );
			}
			return;

		case 'reject':
			$result = $client->post( '/api/admin/ingestion/items/' . $id . '/reject', array( 'notes' => sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) ) ), $token );
			cvc_admin_redirect( $id && false !== strpos( $back, '/thu-thap/' . $id ) ? $index : $back, $result['ok'] ? 'success' : 'error', $result['ok'] ? 'Đã loại tin.' : cvc_admin_error_message( $result ) );
			return;

		case 'reprocess':
			$client = new CVC_Api_Client( null, 170 );
			$result = $client->post( '/api/admin/ingestion/items/' . $id . '/reprocess', array(), $token );
			cvc_admin_redirect( $detail, $result['ok'] ? 'success' : 'error', $result['ok'] ? 'Đã phân tích lại.' : cvc_admin_error_message( $result ) );
			return;

		case 'curate':
			$fields = array();
			foreach ( array( 'title', 'agency_name', 'province_name', 'recruitment_type', 'location' ) as $f ) {
				$fields[ $f ] = sanitize_text_field( wp_unslash( $_POST[ $f ] ?? '' ) );
			}
			foreach ( array( 'announcement_date', 'application_start_date', 'application_deadline' ) as $f ) {
				$v            = sanitize_text_field( wp_unslash( $_POST[ $f ] ?? '' ) );
				$fields[ $f ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
			}
			$fields['summary']         = sanitize_textarea_field( wp_unslash( $_POST['summary'] ?? '' ) );
			$total                     = absint( $_POST['total_positions'] ?? 0 );
			$fields['total_positions'] = $total > 0 ? $total : null;

			$positions = array();
			foreach ( preg_split( '/\r\n|\r|\n/', (string) wp_unslash( $_POST['positions'] ?? '' ) ) as $line ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$parts = array_map( 'trim', explode( '|', sanitize_text_field( $line ) ) );
				if ( '' === ( $parts[0] ?? '' ) ) {
					continue;
				}
				$qty = absint( $parts[1] ?? 0 );
				if ( $qty < 1 ) {
					continue;
				}
				$positions[] = array( 'name' => $parts[0], 'quantity' => $qty, 'education_level' => ( $parts[2] ?? '' ) !== '' ? $parts[2] : null );
			}
			if ( ! empty( $positions ) ) {
				$fields['positions'] = array_slice( $positions, 0, 60 );
			}

			$fields = array_filter( $fields, static fn ( $v ) => null !== $v && '' !== $v );
			$result = $client->put( '/api/admin/ingestion/items/' . $id, $fields, $token );
			cvc_admin_redirect( $detail, $result['ok'] ? 'success' : 'error', $result['ok'] ? 'Đã lưu chỉnh sửa.' : cvc_admin_error_message( $result ) );
			return;

		case 'import':
			if ( empty( $_FILES['file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				cvc_admin_redirect( $index, 'error', 'Hãy chọn file công văn (PDF/DOCX).' );
				return;
			}
			$file   = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$name   = sanitize_file_name( (string) $file['name'] );
			$mime   = (string) ( wp_check_filetype( $name )['type'] ?: 'application/octet-stream' );
			$client = new CVC_Api_Client( null, 170 );
			$result = $client->post_multipart(
				'/api/admin/ingestion/import',
				array_filter(
					array(
						'source_url'  => esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) ),
						'province_id' => absint( $_POST['province_id'] ?? 0 ) ?: null,
						'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
					),
					static fn ( $v ) => null !== $v && '' !== $v
				),
				array( 'file' => array( (string) $file['tmp_name'], $name, $mime ) ),
				$token
			);
			if ( ! $result['ok'] ) {
				cvc_admin_redirect( $index, 'error', cvc_admin_error_message( $result ) );
				return;
			}
			$new_id = (int) ( $result['data']['data']['id'] ?? 0 );
			cvc_admin_redirect( $new_id ? cvc_admin_url( 'thu-thap', $new_id ) : $index, 'success', (string) ( $result['data']['message'] ?? 'Đã nhập công văn.' ) );
			return;

		case 'recheck':
			$client = new CVC_Api_Client( null, 70 );
			$result = $client->post( '/api/admin/sources/' . $id . '/recheck', array(), $token );
			cvc_admin_redirect( cvc_admin_url( 'thu-thap', null, array( 'xem' => 'nguon' ) ), $result['ok'] ? 'success' : 'error', $result['ok'] ? (string) ( $result['data']['message'] ?? 'Đã kiểm tra.' ) : cvc_admin_error_message( $result ) );
			return;

		case 'review':
			$result = $client->post(
				'/api/admin/ingestion/items/' . $id . '/review',
				array(
					'result' => sanitize_key( wp_unslash( $_POST['result'] ?? '' ) ),
					'notes'  => sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) ),
				),
				$token
			);
			cvc_admin_redirect( cvc_admin_url( 'thu-thap', null, array( 'xem' => 'kiem-tra' ) ), $result['ok'] ? 'success' : 'error', $result['ok'] ? (string) ( $result['data']['message'] ?? 'Đã ghi nhận.' ) : cvc_admin_error_message( $result ) );
			return;

		case 'run':
			$client = new CVC_Api_Client( null, 170 );
			$result = $client->post( '/api/admin/ingestion/run', array( 'limit' => 5 ), $token );
			if ( ! $result['ok'] ) {
				cvc_admin_redirect( $index, 'error', cvc_admin_error_message( $result ) );
				return;
			}
			$s = (array) ( $result['data']['data'] ?? array() );
			cvc_admin_redirect(
				$index,
				'success',
				sprintf( 'Đã chạy 1 lượt: %d link mới, %d trang đã xử lý, %d tin đăng, %d lỗi.', (int) ( $s['new_links'] ?? 0 ), (int) ( $s['fetched'] ?? 0 ), (int) ( $s['published'] ?? 0 ), (int) ( $s['failed'] ?? 0 ) )
					. ( ! empty( $s['errors'] ) ? ' ' . implode( ' ', array_slice( (array) $s['errors'], 0, 2 ) ) : '' )
			);
			return;
	}

	cvc_admin_redirect( $index, 'error', 'Thao tác không hợp lệ.' );
}

add_action( 'admin_post_cvc_admin_ingestion_file', 'cvc_admin_handle_ingestion_file' );
add_action( 'admin_post_nopriv_cvc_admin_ingestion_file', 'cvc_admin_handle_ingestion_file' );

/**
 * Tải bản lưu công văn (qua token của quản trị viên) - file chưa gắn với tin
 * đã đăng nên không có link công khai.
 */
function cvc_admin_handle_ingestion_file(): void {
	check_admin_referer( 'cvc_admin_ingestion_file' );
	$token = cvc_admin_require_staff();
	$id    = absint( $_GET['id'] ?? 0 );

	$response = wp_remote_get(
		cvc_api_base_url() . '/api/admin/ingestion/attachments/' . $id . '/download',
		array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => '*/*',
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		wp_die( esc_html__( 'Không tải được file.', 'cvc' ), '', array( 'response' => 404 ) );
	}

	nocache_headers();
	header( 'Content-Type: ' . ( wp_remote_retrieve_header( $response, 'content-type' ) ?: 'application/octet-stream' ) );
	$disposition = wp_remote_retrieve_header( $response, 'content-disposition' );
	if ( $disposition ) {
		header( 'Content-Disposition: ' . $disposition );
	}
	echo wp_remote_retrieve_body( $response ); // phpcs:ignore WordPress.Security.EscapeOutput -- file nhị phân.
	exit;
}

/* ------------------------------------------------------------------ */
/* Phase 15 - Nguồn & chỉ số                                           */
/* ------------------------------------------------------------------ */

function cvc_ingestion_cell( string $status ): array {
	return array(
		'ok'         => array( 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40', 'Đang chạy' ),
		'no_listing' => array( 'bg-amber-500/20 text-amber-300 border-amber-500/40', 'Thiếu trang danh mục' ),
		'inactive'   => array( 'bg-slate-600/30 text-slate-300 border-slate-500/40', 'Tạm dừng' ),
		'down'       => array( 'bg-rose-500/20 text-rose-300 border-rose-500/40', 'Không truy cập được' ),
		'not_found'  => array( 'bg-rose-500/20 text-rose-300 border-rose-500/40', 'Chưa xác minh được' ),
		'pending'    => array( 'bg-slate-800 text-slate-500 border-slate-700', 'Chưa dò' ),
	)[ $status ] ?? array( 'bg-slate-800 text-slate-500 border-slate-700', $status );
}

function cvc_admin_render_ingestion_sources(): void {
	$days   = in_array( absint( $_GET['ngay'] ?? 30 ), array( 7, 30, 90 ), true ) ? absint( $_GET['ngay'] ?? 30 ) : 30; // phpcs:ignore WordPress.Security.NonceVerification
	$result = cvc_admin_api_get( '/api/admin/ingestion/sources-report', array( 'days' => $days ) );
	$btn    = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	?>
	<header class="flex flex-wrap items-end justify-between gap-3">
		<div>
			<h1 class="text-2xl font-black text-white">Nguồn & chỉ số thu thập</h1>
			<p class="text-sm text-slate-400">Danh bạ nguồn đã xác minh, độ phủ 34 tỉnh và 7 chỉ số đo chất lượng.</p>
		</div>
		<div class="flex flex-wrap gap-2 items-center">
			<?php foreach ( array( 7, 30, 90 ) as $d ) : ?>
				<a class="<?php echo esc_attr( $btn ); ?> <?php echo $d === $days ? 'bg-cyan-500 text-slate-950' : 'bg-slate-800 text-slate-300'; ?>" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', null, array( 'xem' => 'nguon', 'ngay' => $d ) ) ); ?>"><?php echo esc_html( $d . ' ngày' ); ?></a>
			<?php endforeach; ?>
			<a class="<?php echo esc_attr( $btn ); ?> bg-slate-800 text-slate-200" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cvc_admin_ingestion_csv&days=' . $days ), 'cvc_admin_ingestion_csv' ) ); ?>"><i class="fa-solid fa-file-csv mr-1" aria-hidden="true"></i>Xuất CSV</a>
			<a class="<?php echo esc_attr( $btn ); ?> bg-slate-800 text-slate-200" href="<?php echo esc_url( cvc_admin_url( 'sources', 'new' ) ); ?>">+ Thêm nguồn</a>
		</div>
	</header>
	<?php cvc_ingestion_tabs( 'nguon' ); ?>
	<?php
	if ( ! $result['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $result ) );
		return;
	}
	$d     = (array) ( $result['data']['data'] ?? array() );
	$k     = (array) ( $d['kpi'] ?? array() );
	$types = (array) ( $k['types'] ?? array() );
	$type_labels = array( 'recruitment' => 'tuyển dụng', 'exam_schedule' => 'lịch thi', 'result' => 'kết quả', 'study_material' => 'tài liệu', 'other' => 'khác' );
	$type_text   = array();
	foreach ( $types as $type => $count ) {
		$type_text[] = ( $type_labels[ $type ] ?? ( $type ?: 'chưa phân loại' ) ) . ' ' . (int) $count;
	}
	$kinds  = (array) ( $k['content_kinds'] ?? array() );
	$sample = (array) ( $k['sample'] ?? array() );
	$fmt    = static fn ( $v, string $suffix = '' ) => null === $v ? '—' : number_format_i18n( (float) $v, is_float( $v ) && floor( (float) $v ) !== (float) $v ? 1 : 0 ) . $suffix;
	$kpis   = array(
		array( 'Tin mới / tháng', $fmt( $k['notices_per_month'] ?? null ), 'tuyển dụng + sự kiện, quy đổi 30 ngày' ),
		array( 'Nguồn hoạt động', $fmt( $k['active_rate'] ?? null, '%' ), (int) ( $k['active_sources'] ?? 0 ) . ' nguồn bật · có lượt truy cập được trong 7 ngày' ),
		array( 'Tần suất cập nhật', null === ( $k['median_update_gap_days'] ?? null ) ? '—' : $fmt( $k['median_update_gap_days'] ) . ' ngày', 'trung vị giữa 2 tin mới của cùng nguồn' ),
		array( 'Cơ cấu loại tin', empty( $type_text ) ? '—' : (string) array_sum( array_map( 'intval', $types ) ), empty( $type_text ) ? 'chưa có dữ liệu' : implode( ' · ', $type_text ) ),
		array( 'Chất lượng dữ liệu', $fmt( $k['avg_completeness'] ?? null, '/100' ), 'điểm đầy đủ TB · ' . ( null === ( $k['manual_edit_rate'] ?? null ) ? 'chưa có tin đăng' : $fmt( $k['manual_edit_rate'], '%' ) . ' tin phải sửa tay' ) ),
		array( 'PDF / HTML', $fmt( $kinds['pdf_scan'] ?? null, '%' ) . ' scan', 'HTML ' . $fmt( $kinds['html'] ?? null, '%' ) . ' · PDF chữ ' . $fmt( $kinds['pdf_text'] ?? null, '%' ) ),
		array( 'Ổn định URL', (string) (int) ( $k['url_changes'] ?? 0 ), 'lần đổi địa chỉ · ' . (int) ( $k['attachments_failed'] ?? 0 ) . ' công văn không tải được' ),
		array( 'Kiểm tra mẫu', (int) ( $sample['reviewed'] ?? 0 ) . ' tin', 'lỗi nặng ' . $fmt( isset( $sample['major_rate'] ) ? $sample['major_rate'] * 100 : null, '%' ) . ' · chờ kiểm ' . (int) ( $sample['pending'] ?? 0 ) ),
	);
	?>
	<dl class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
		<?php foreach ( $kpis as $kpi ) : ?>
			<div class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4">
				<dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400"><?php echo esc_html( $kpi[0] ); ?></dt>
				<dd class="text-2xl font-black text-white tabular-nums mt-1"><?php echo esc_html( (string) $kpi[1] ); ?></dd>
				<dd class="text-xs text-slate-400 mt-0.5"><?php echo esc_html( $kpi[2] ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>

	<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-3" aria-labelledby="cvc-cov">
		<div class="flex flex-wrap items-center justify-between gap-2">
			<h2 id="cvc-cov" class="text-sm font-black text-white">Độ phủ nhóm A — 34 tỉnh/thành</h2>
			<div class="flex flex-wrap gap-2 text-[11px]">
				<?php foreach ( array( 'ok', 'no_listing', 'down', 'not_found', 'inactive', 'pending' ) as $st ) : ?>
					<?php $c = cvc_ingestion_cell( $st ); ?>
					<span class="px-2 py-0.5 rounded border <?php echo esc_attr( $c[0] ); ?>"><?php echo esc_html( $c[1] ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="overflow-x-auto">
			<table class="w-full text-xs">
				<thead class="text-[11px] uppercase tracking-wider text-slate-400 text-left">
					<tr><th class="py-2 pr-3">Tỉnh/thành</th><th class="py-2 pr-3">Sở Nội vụ</th><th class="py-2 pr-3">Sở GD&amp;ĐT</th><th class="py-2">Sở Y tế</th></tr>
				</thead>
				<tbody class="divide-y divide-slate-800">
					<?php foreach ( (array) ( $d['coverage'] ?? array() ) as $row ) : ?>
						<tr>
							<td class="py-1.5 pr-3 text-slate-200 whitespace-nowrap"><?php echo esc_html( (string) $row['province'] ); ?></td>
							<?php foreach ( array( 'snv', 'sgddt', 'syt' ) as $cat ) : ?>
								<?php
								$cell = (array) ( $row['cells'][ $cat ] ?? array( 'status' => 'pending' ) );
								$c    = cvc_ingestion_cell( (string) $cell['status'] );
								?>
								<td class="py-1.5 pr-3">
									<?php if ( ! empty( $cell['source_id'] ) ) : ?>
										<a class="inline-block px-2 py-0.5 rounded border <?php echo esc_attr( $c[0] ); ?>" href="<?php echo esc_url( cvc_admin_url( 'sources', (int) $cell['source_id'] ) ); ?>"><?php echo esc_html( $c[1] ); ?></a>
									<?php else : ?>
										<span class="inline-block px-2 py-0.5 rounded border <?php echo esc_attr( $c[0] ); ?>"><?php echo esc_html( $c[1] ); ?></span>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="text-xs text-slate-500">Hệ thống tự dò mỗi lượt thu thập (Sở Nội vụ trước, rồi Sở GD&amp;ĐT, Sở Y tế, Bộ/ngành). Nguồn xác minh được (HTTP 200 + tiêu đề khớp cơ quan và tỉnh) tự bật thu thập. Ô đỏ: đã thử các địa chỉ ứng viên nhưng không khớp — khai báo tay qua "Thêm nguồn".</p>
	</section>

	<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-3" aria-labelledby="cvc-src">
		<h2 id="cvc-src" class="text-sm font-black text-white">Từng nguồn (<?php echo esc_html( (string) $days ); ?> ngày)</h2>
		<div class="overflow-x-auto">
			<table class="w-full text-xs">
				<thead class="text-[11px] uppercase tracking-wider text-slate-400 text-left">
					<tr>
						<th class="py-2 pr-3">Nguồn</th><th class="py-2 pr-3">Nhóm</th><th class="py-2 pr-3">Kiểm tra gần nhất</th>
						<th class="py-2 pr-3 text-right">Tin</th><th class="py-2 pr-3 text-right">Đã đăng</th><th class="py-2 pr-3 text-right">Đầy đủ</th>
						<th class="py-2 pr-3 text-right">Tin cậy</th><th class="py-2 text-right">Thao tác</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-slate-800">
					<?php foreach ( (array) ( $d['sources'] ?? array() ) as $src ) : ?>
						<?php if ( 'manual_upload' === ( $src['category'] ?? '' ) ) { continue; } ?>
						<tr class="align-top <?php echo empty( $src['is_active'] ) ? 'opacity-60' : ''; ?>">
							<td class="py-2 pr-3 min-w-[220px]">
								<a class="font-bold text-slate-100 hover:text-cyan-300" href="<?php echo esc_url( cvc_admin_url( 'sources', (int) $src['id'] ) ); ?>"><?php echo esc_html( (string) $src['name'] ); ?></a>
								<div class="text-slate-500 break-all"><?php echo esc_html( (string) $src['url'] ); ?></div>
								<div class="text-slate-500">
									<?php echo esc_html( (string) ( $src['province'] ?? '' ) ); ?>
									<?php echo empty( $src['has_listing'] ) ? '<span class="text-amber-300"> · chưa có trang danh mục</span>' : ''; ?>
									<?php echo ! empty( $src['wildcard_dns'] ) ? '<span class="text-slate-400"> · wildcard DNS</span>' : ''; ?>
									<?php echo empty( $src['is_active'] ) ? '<span class="text-slate-400"> · tạm dừng</span>' : ''; ?>
								</div>
							</td>
							<td class="py-2 pr-3 text-slate-300"><?php echo esc_html( (string) $src['category_label'] ); ?></td>
							<td class="py-2 pr-3 whitespace-nowrap">
								<span class="<?php echo ! empty( $src['active_7d'] ) ? 'text-emerald-300' : 'text-rose-300'; ?>"><?php echo esc_html( null === $src['last_http_status'] ? '—' : 'HTTP ' . $src['last_http_status'] ); ?></span><br>
								<span class="text-slate-500"><?php echo esc_html( cvc_admin_format( $src['last_checked_at'] ?? null, 'datetime' ) ); ?></span>
							</td>
							<td class="py-2 pr-3 text-right tabular-nums"><?php echo esc_html( (string) (int) $src['notices'] ); ?></td>
							<td class="py-2 pr-3 text-right tabular-nums"><?php echo esc_html( (string) (int) $src['published'] ); ?></td>
							<td class="py-2 pr-3 text-right tabular-nums"><?php echo esc_html( null === $src['avg_completeness'] ? '—' : (string) $src['avg_completeness'] ); ?></td>
							<td class="py-2 pr-3 text-right tabular-nums"><?php echo esc_html( null === $src['reliability_score'] ? '—' : (string) round( (float) $src['reliability_score'] ) ); ?></td>
							<td class="py-2 text-right whitespace-nowrap">
								<?php if ( cvc_admin_can( 'recruitment.update' ) ) : ?>
									<?php echo cvc_ingestion_op_form( 'recheck', (int) $src['id'], 'Kiểm tra lại', $btn . ' bg-slate-800 text-slate-300 hover:bg-slate-700' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>

	<?php if ( ! empty( $d['probes'] ) ) : ?>
		<details class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5">
			<summary class="cursor-pointer text-sm font-black text-white">Lượt dò nguồn gần đây</summary>
			<ul class="mt-3 space-y-2 text-xs">
				<?php foreach ( (array) $d['probes'] as $probe ) : ?>
					<li class="border border-slate-800 rounded-xl p-3">
						<div class="flex flex-wrap justify-between gap-2">
							<span class="text-slate-100 font-bold"><?php echo esc_html( (string) $probe['province'] . ' · ' . (string) $probe['category'] ); ?></span>
							<span class="<?php echo in_array( $probe['result'], array( 'found', 'exists' ), true ) ? 'text-emerald-300' : 'text-rose-300'; ?>"><?php echo esc_html( array( 'found' => 'Tìm thấy', 'exists' => 'Đã có', 'not_found' => 'Không xác minh được', 'error' => 'Lỗi' )[ $probe['result'] ] ?? (string) $probe['result'] ); ?> · <?php echo esc_html( cvc_admin_format( $probe['probed_at'] ?? null, 'datetime' ) ); ?></span>
						</div>
						<?php if ( ! empty( $probe['notes'] ) ) : ?>
							<pre class="mt-1 whitespace-pre-wrap text-slate-400 font-sans"><?php echo esc_html( (string) $probe['notes'] ); ?></pre>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
	<?php
}

/* ------------------------------------------------------------------ */
/* Phase 15 - Kiểm tra mẫu tin tự đăng                                 */
/* ------------------------------------------------------------------ */

function cvc_admin_render_ingestion_review(): void {
	$result  = cvc_admin_api_get( '/api/admin/ingestion/review' );
	$can_pub = cvc_admin_can( 'recruitment.publish' );
	$btn     = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$labels  = array( 'pending' => 'Chờ kiểm', 'correct' => 'Đúng', 'minor_error' => 'Lỗi nhỏ', 'major_error' => 'Lỗi nặng' );
	?>
	<header>
		<h1 class="text-2xl font-black text-white">Kiểm tra mẫu tin tự đăng</h1>
		<p class="text-sm text-slate-400">Mỗi tuần hệ thống chọn tối đa 20 tin tự đăng trong 7 ngày. Đối chiếu từng tin với công văn gốc: cơ quan, tỉnh, hạn nộp, vị trí, chỉ tiêu. Nếu tỷ lệ lỗi nặng vượt 5% (khi đã kiểm từ 10 tin), hệ thống tự chuyển về duyệt 1 click.</p>
	</header>
	<?php cvc_ingestion_tabs( 'kiem-tra' ); ?>
	<?php
	if ( ! $result['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $result ) );
		return;
	}
	$d     = (array) ( $result['data']['data'] ?? array() );
	$stats = (array) ( $d['stats'] ?? array() );
	?>
	<dl class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-sm">
		<?php foreach ( array( 'pending' => 'Chờ kiểm', 'correct' => 'Đúng', 'minor_error' => 'Lỗi nhỏ', 'major_error' => 'Lỗi nặng' ) as $key => $label ) : ?>
			<div class="bg-[#0A192F] border border-slate-800 rounded-xl p-3"><dt class="text-[11px] font-bold text-slate-400"><?php echo esc_html( $label ); ?></dt><dd class="text-xl font-black text-white tabular-nums"><?php echo esc_html( (string) (int) ( $stats[ $key ] ?? 0 ) ); ?></dd></div>
		<?php endforeach; ?>
		<div class="bg-[#0A192F] border border-slate-800 rounded-xl p-3"><dt class="text-[11px] font-bold text-slate-400">Tỷ lệ lỗi nặng</dt><dd class="text-xl font-black <?php echo (float) ( $stats['major_rate'] ?? 0 ) > 0.05 ? 'text-rose-300' : 'text-white'; ?> tabular-nums"><?php echo esc_html( round( (float) ( $stats['major_rate'] ?? 0 ) * 100, 1 ) . '%' ); ?></dd></div>
	</dl>

	<?php if ( empty( $d['items'] ) ) : ?>
		<p class="text-sm text-slate-400">Chưa có tin nào trong mẫu kiểm tra. Mẫu được chọn mỗi thứ Hai từ các tin tự đăng của tuần trước.</p>
		<?php return; ?>
	<?php endif; ?>

	<ul class="space-y-3">
		<?php foreach ( (array) $d['items'] as $it ) : ?>
			<li class="bg-[#0A192F] border <?php echo 'pending' === $it['review_sample'] ? 'border-amber-500/40' : 'border-slate-800'; ?> rounded-2xl p-4 space-y-2">
				<div class="flex flex-wrap justify-between gap-2">
					<a class="font-bold text-slate-100 hover:text-cyan-300" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', (int) $it['id'] ) ); ?>"><?php echo esc_html( (string) ( $it['title'] ?? '(chưa có tiêu đề)' ) ); ?></a>
					<span class="text-xs font-bold <?php echo 'major_error' === $it['review_sample'] ? 'text-rose-300' : ( 'correct' === $it['review_sample'] ? 'text-emerald-300' : 'text-amber-300' ); ?>"><?php echo esc_html( $labels[ $it['review_sample'] ] ?? (string) $it['review_sample'] ); ?></span>
				</div>
				<p class="text-xs text-slate-400">
					<?php echo esc_html( (string) ( $it['source'] ?? '' ) ); ?> · đăng <?php echo esc_html( cvc_admin_format( $it['published_at'] ?? null, 'datetime' ) ); ?> · điểm đầy đủ <?php echo esc_html( null === $it['completeness_score'] ? '—' : (string) $it['completeness_score'] ); ?>
					· <a class="text-cyan-300 underline" href="<?php echo esc_url( (string) $it['source_url'] ); ?>" target="_blank" rel="noopener nofollow">Trang gốc</a>
					<?php if ( ! empty( $it['recruitment']['slug'] ) ) : ?>
						· <a class="text-emerald-300 underline" href="<?php echo esc_url( cvc_recruitment_url( (string) $it['recruitment']['slug'] ) ); ?>" target="_blank" rel="noopener">Tin đã đăng</a>
					<?php endif; ?>
				</p>
				<?php if ( ! empty( $it['review_notes'] ) ) : ?>
					<p class="text-xs text-slate-300">Ghi chú: <?php echo esc_html( (string) $it['review_notes'] ); ?> <span class="text-slate-500">— <?php echo esc_html( (string) $it['reviewed_by'] ); ?></span></p>
				<?php endif; ?>
				<?php if ( $can_pub ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-center gap-2">
						<?php wp_nonce_field( 'cvc_admin_ingestion' ); ?>
						<input type="hidden" name="action" value="cvc_admin_ingestion">
						<input type="hidden" name="op" value="review">
						<input type="hidden" name="id" value="<?php echo esc_attr( (string) $it['id'] ); ?>">
						<input type="text" name="notes" maxlength="1000" placeholder="Ghi chú (sai trường nào…)" class="<?php echo esc_attr( cvc_admin_input_class() ); ?> !mt-0 w-72">
						<button name="result" value="correct" class="<?php echo esc_attr( $btn ); ?> bg-emerald-500 text-slate-950">Đúng</button>
						<button name="result" value="minor_error" class="<?php echo esc_attr( $btn ); ?> bg-amber-500 text-slate-950">Lỗi nhỏ</button>
						<button name="result" value="major_error" class="<?php echo esc_attr( $btn ); ?> bg-rose-500 text-white">Lỗi nặng</button>
					</form>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="text-xs text-slate-500">Lỗi nặng: sai cơ quan, sai tỉnh, sai hạn nộp, sai chỉ tiêu, hoặc không phải tin tuyển dụng. Lỗi nhỏ: sai chính tả, tóm tắt thiếu, vị trí gộp chưa tách.</p>
	<?php
}

add_action( 'admin_post_cvc_admin_ingestion_csv', 'cvc_admin_handle_ingestion_csv' );
add_action( 'admin_post_nopriv_cvc_admin_ingestion_csv', 'cvc_admin_handle_ingestion_csv' );

function cvc_admin_handle_ingestion_csv(): void {
	check_admin_referer( 'cvc_admin_ingestion_csv' );
	$token = cvc_admin_require_staff();
	$days  = in_array( absint( $_GET['days'] ?? 30 ), array( 7, 30, 90 ), true ) ? absint( $_GET['days'] ) : 30;

	$response = wp_remote_get(
		cvc_api_base_url() . '/api/admin/ingestion/sources-report?format=csv&days=' . $days,
		array(
			'timeout' => 60,
			'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Accept' => 'text/csv' ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		wp_die( esc_html__( 'Không xuất được báo cáo.', 'cvc' ), '', array( 'response' => 502 ) );
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="nguon-tuyen-dung-' . $days . '-ngay.csv"' );
	echo wp_remote_retrieve_body( $response ); // phpcs:ignore WordPress.Security.EscapeOutput -- CSV.
	exit;
}
