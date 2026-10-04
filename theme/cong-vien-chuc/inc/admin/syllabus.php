<?php
/**
 * Phase 16a - Khu quản trị "Ôn tập": danh mục tài liệu ôn tập → văn bản
 * pháp luật → bài học & câu hỏi (trong /quan-tri/thu-thap/).
 *
 *  ?xem=on-tap            danh sách danh mục + nhập URL/file
 *  ?xem=on-tap&id=N       ma trận phủ + điểm sẵn sàng của 1 kỳ tuyển dụng
 *  ?xem=van-ban&id=N      1 văn bản: nguồn, toàn văn, Điều, quan hệ, sinh nội dung
 *  ?xem=duyet-ai          hàng chờ duyệt câu hỏi / bài học AI chưa tự đăng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_syl_url( string $view, ?int $id = null, array $args = array() ): string {
	$q = array( 'xem' => $view );
	if ( null !== $id ) {
		$q['id'] = $id;
	}

	return cvc_admin_url( 'thu-thap', null, array_merge( $q, $args ) );
}

function cvc_syl_state( string $state ): array {
	return array(
		'verified'   => array( 'bg-emerald-500/15 text-emerald-300 border-emerald-500/40', 'Chính thức' ),
		'reference'  => array( 'bg-amber-500/15 text-amber-300 border-amber-500/40', 'Tham khảo' ),
		'missing'    => array( 'bg-rose-500/15 text-rose-300 border-rose-500/40', 'Không tìm thấy' ),
		'unresolved' => array( 'bg-slate-700/40 text-slate-300 border-slate-600', 'Chưa tìm' ),
	)[ $state ] ?? array( 'bg-slate-700/40 text-slate-300 border-slate-600', $state );
}

function cvc_syl_chip( string $state, string $text = '' ): string {
	[ $cls, $label ] = cvc_syl_state( $state );

	return '<span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded-full border text-[11px] font-bold ' . esc_attr( $cls ) . '">' . esc_html( '' !== $text ? $text : $label ) . '</span>';
}

function cvc_syl_status( array $s ): string {
	$p = (array) ( $s['progress'] ?? array() );
	switch ( (string) ( $s['status'] ?? '' ) ) {
		case 'pending':
			return '<span class="text-xs font-bold text-slate-300">Chờ trích</span>';
		case 'extracting':
			return '<span class="text-xs font-bold text-cyan-300">Đang trích ' . esc_html( (int) ( $p['chunk'] ?? 0 ) . '/' . (int) ( $p['total'] ?? 0 ) ) . '</span>';
		case 'extracted':
			return '<span class="text-xs font-bold text-emerald-300">Đã trích</span>';
		default:
			return ! empty( $s['stats'] ) || false === strpos( (string) ( $s['error'] ?? '' ), 'Không có file danh mục' )
				? '<span class="text-xs font-bold text-rose-300">Lỗi</span>'
				: '<span class="text-xs font-bold text-slate-400">Không có danh mục dạng chữ</span>';
	}
}

function cvc_syl_op( string $op, array $fields, string $label, string $class, string $confirm = '' ): string {
	$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="inline"' . ( '' !== $confirm ? ' data-cvc-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>';
	$html .= wp_nonce_field( 'cvc_admin_syllabus', '_wpnonce', true, false );
	$html .= '<input type="hidden" name="action" value="cvc_admin_syllabus"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
	foreach ( $fields as $k => $v ) {
		$html .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '">';
	}
	$html .= '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';

	return $html;
}

function cvc_syl_bar( int $percent, string $tone = 'bg-cyan-400' ): string {
	$percent = max( 0, min( 100, $percent ) );

	return '<span class="block h-1.5 w-full rounded-full bg-slate-800 overflow-hidden" aria-hidden="true"><span class="block h-full ' . esc_attr( $tone ) . '" style="width:' . esc_attr( (string) $percent ) . '%"></span></span>';
}

/* ------------------------------------------------------------------ */
/* Danh sách                                                           */
/* ------------------------------------------------------------------ */

function cvc_admin_render_syllabus_index(): void {
	$result  = cvc_admin_api_get( '/api/admin/syllabi' );
	$can     = cvc_admin_can( 'recruitment.publish' );
	$input   = cvc_admin_input_class();
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-white">Ôn tập theo kỳ tuyển dụng</h1>
		<p class="text-sm text-slate-400 max-w-3xl">Từ thông báo "Danh mục tài liệu ôn tập": hệ thống tải file (kể cả RAR/ZIP), trích từng vòng/vị trí, tìm toàn văn chính thức của từng văn bản (Công báo, vanban.chinhphu.vn), tách Điều, rồi sinh bài học và câu hỏi có trích dẫn. Tin "Tài liệu ôn tập" thu thập tự động cũng được đưa vào đây.</p>
	</header>
	<?php cvc_ingestion_tabs( 'on-tap' ); ?>
	<?php
	if ( ! $result['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $result ) );
		return;
	}
	$rows   = (array) ( $result['data']['data'] ?? array() );
	$worker = (array) ( $result['data']['worker'] ?? array() );
	$review = (int) ( $result['data']['review_pending'] ?? 0 );
	?>
	<p class="text-xs text-slate-400 flex flex-wrap gap-x-4 gap-y-1">
		<span><i class="fa-solid fa-gears text-cyan-400" aria-hidden="true"></i> Hệ thống xử lý nền mỗi phút.</span>
		<span>Văn bản chờ tìm nguồn: <strong class="text-slate-200 tabular-nums"><?php echo esc_html( (string) (int) ( $worker['unresolved'] ?? 0 ) ); ?></strong></span>
		<span>Đang OCR: <strong class="text-slate-200 tabular-nums"><?php echo esc_html( (string) (int) ( $worker['ocr_pending'] ?? 0 ) ); ?></strong></span>
		<span>Lượt sinh nội dung: <strong class="text-slate-200 tabular-nums"><?php echo esc_html( (string) (int) ( $worker['runs_active'] ?? 0 ) ); ?></strong></span>
		<a class="text-amber-300 underline" href="<?php echo esc_url( cvc_syl_url( 'duyet-ai' ) ); ?>">Chờ duyệt: <?php echo esc_html( (string) $review ); ?> câu hỏi</a>
	</p>

	<?php if ( $can ) : ?>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4 grid gap-3 md:grid-cols-[1fr_auto_auto] md:items-end">
			<?php wp_nonce_field( 'cvc_admin_syllabus' ); ?>
			<input type="hidden" name="action" value="cvc_admin_syllabus">
			<input type="hidden" name="op" value="create">
			<label class="text-xs font-bold text-slate-300">URL thông báo danh mục ôn tập
				<input type="url" name="url" id="syl-url" placeholder="https://sonv.laichau.gov.vn/Vanbanchitiet?did=28838" class="<?php echo esc_attr( $input ); ?> mt-1 font-mono">
			</label>
			<label class="text-xs font-bold text-slate-300">hoặc file PDF / RAR / ZIP
				<input type="file" name="file" id="syl-file" accept=".pdf,.rar,.zip" class="block mt-1 text-xs text-slate-300 file:mr-2 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-slate-700 file:text-slate-100">
			</label>
			<button type="submit" class="px-4 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-sm font-black">Phân tích danh mục</button>
		</form>
	<?php endif; ?>

	<?php if ( empty( $rows ) ) : ?>
		<p class="text-sm text-slate-400">Chưa có danh mục nào. Dán URL thông báo danh mục tài liệu ôn tập ở trên để bắt đầu.</p>
		<?php return; ?>
	<?php endif; ?>

	<div class="overflow-x-auto bg-[#0A192F] border border-slate-800 rounded-2xl">
		<table class="w-full text-sm">
			<thead class="text-[11px] uppercase tracking-wider text-slate-400 text-left">
				<tr><th class="p-3">Kỳ tuyển dụng / thông báo</th><th class="p-3">Tỉnh</th><th class="p-3">Trạng thái</th><th class="p-3 text-right">Mục · văn bản</th><th class="p-3 w-44">Sẵn sàng nội dung</th></tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $s ) : ?>
					<?php $ready = $s['readiness'] ?? null; ?>
					<tr class="border-t border-slate-800 align-top">
						<td class="p-3">
							<a class="font-bold text-slate-100 hover:text-cyan-300" href="<?php echo esc_url( cvc_syl_url( 'on-tap', (int) $s['id'] ) ); ?>"><?php echo esc_html( (string) ( $s['title'] ?: 'Danh mục #' . $s['id'] ) ); ?></a>
							<div class="text-xs text-slate-500"><?php echo esc_html( trim( (string) ( $s['notice_number'] ?? '' ) . ( ! empty( $s['issued_date'] ) ? ' · ' . cvc_admin_format( $s['issued_date'], 'date' ) : '' ) ) ); ?></div>
							<?php if ( ! empty( $s['error'] ) && 'failed' === $s['status'] ) : ?>
								<div class="text-xs text-rose-300"><?php echo esc_html( (string) $s['error'] ); ?></div>
							<?php endif; ?>
						</td>
						<td class="p-3 text-slate-300"><?php echo esc_html( (string) ( $s['province'] ?? '—' ) ); ?></td>
						<td class="p-3"><?php echo cvc_syl_status( $s ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td class="p-3 text-right tabular-nums text-slate-300"><?php echo esc_html( (int) ( $s['stats']['items'] ?? 0 ) . ' · ' . (int) ( $s['stats']['documents'] ?? 0 ) ); ?></td>
						<td class="p-3">
							<?php if ( is_array( $ready ) ) : ?>
								<div class="flex items-center gap-2"><?php echo cvc_syl_bar( (int) $ready['score'], (int) $ready['score'] >= 70 ? 'bg-emerald-400' : 'bg-cyan-400' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="text-xs font-black text-white tabular-nums w-9 text-right"><?php echo esc_html( (int) $ready['score'] . '%' ); ?></span></div>
							<?php else : ?>
								<span class="text-xs text-slate-500">—</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/* ------------------------------------------------------------------ */
/* Chi tiết 1 danh mục                                                 */
/* ------------------------------------------------------------------ */

function cvc_admin_render_syllabus_detail( int $id ): void {
	$result = cvc_admin_api_get( '/api/admin/syllabi/' . $id );
	$can    = cvc_admin_can( 'recruitment.publish' );
	$btn    = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$filter = sanitize_key( wp_unslash( $_GET['loc'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<a class="text-sm text-cyan-300 hover:underline" href="<?php echo esc_url( cvc_syl_url( 'on-tap' ) ); ?>">← Danh mục ôn tập</a>
	<?php
	if ( ! $result['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $result ) );
		return;
	}
	$d     = (array) ( $result['data']['data'] ?? array() );
	$s     = (array) ( $d['syllabus'] ?? array() );
	$cov   = $d['coverage'] ?? null;
	$stats = (array) ( $s['stats'] ?? array() );
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-white"><?php echo esc_html( (string) ( $s['title'] ?: 'Danh mục #' . $id ) ); ?></h1>
		<p class="text-sm text-slate-400">
			<?php echo esc_html( implode( ' · ', array_filter( array( $s['notice_number'] ?? '', ! empty( $s['issued_date'] ) ? cvc_admin_format( $s['issued_date'], 'date' ) : '', $s['issuer'] ?? '', $s['province'] ?? '' ) ) ) ); ?>
			<?php if ( ! empty( $s['source_url'] ) && 0 === strpos( (string) $s['source_url'], 'http' ) ) : ?>
				· <a class="text-cyan-300 underline" href="<?php echo esc_url( (string) $s['source_url'] ); ?>" target="_blank" rel="noopener nofollow">Thông báo gốc</a>
			<?php endif; ?>
		</p>
		<p><?php echo cvc_syl_status( $s ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php if ( ! empty( $s['ai_model'] ) ) : ?><span class="text-xs text-slate-500">· trích bằng <?php echo esc_html( (string) $s['ai_model'] ); ?></span><?php endif; ?></p>
		<?php if ( ! empty( $s['error'] ) ) : ?>
			<p class="text-sm text-rose-300"><?php echo esc_html( (string) $s['error'] ); ?></p>
		<?php endif; ?>
	</header>

	<?php if ( ! is_array( $cov ) ) : ?>
		<p class="text-sm text-slate-400">Danh mục đang được trích (mỗi phút 1–2 đoạn ~12.000 ký tự). Tải lại trang sau ít phút.</p>
		<?php if ( $can ) : ?>
			<div><?php echo cvc_syl_op( 'reprocess', array( 'id' => $id ), 'Trích lại từ đầu', $btn . ' border border-slate-600 text-slate-200' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<?php endif; ?>
		<?php return; ?>
	<?php endif; ?>

	<?php $ready = (array) $cov['readiness']; ?>
	<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 grid gap-5 md:grid-cols-[180px_1fr]" aria-label="Điểm sẵn sàng nội dung">
		<div>
			<p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sẵn sàng nội dung</p>
			<p class="text-5xl font-black text-white tabular-nums"><?php echo esc_html( (int) $ready['score'] . '%' ); ?></p>
			<p class="text-xs text-slate-400"><?php echo esc_html( (int) $ready['documents'] ); ?> văn bản · <?php echo esc_html( (int) ( $stats['items'] ?? 0 ) ); ?> mục · <?php echo esc_html( (int) ( $stats['sections'] ?? 0 ) ); ?> môn/vị trí</p>
		</div>
		<dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3">
			<?php foreach ( (array) $ready['parts'] as $p ) : ?>
				<div>
					<div class="flex justify-between text-xs"><dt class="text-slate-300"><?php echo esc_html( (string) $p['label'] ); ?> <span class="text-slate-500">(trọng số <?php echo esc_html( (string) (int) $p['weight'] ); ?>)</span></dt><dd class="font-black text-white tabular-nums"><?php echo esc_html( (int) $p['percent'] . '%' ); ?></dd></div>
					<?php echo cvc_syl_bar( (int) $p['percent'], (int) $p['percent'] >= 80 ? 'bg-emerald-400' : ( (int) $p['percent'] >= 40 ? 'bg-cyan-400' : 'bg-amber-400' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			<?php endforeach; ?>
		</dl>
	</section>

	<?php if ( ! empty( $stats['items_unverified'] ) || ! empty( $stats['missed_refs'] ) || ! empty( $stats['errors'] ) ) : ?>
		<div class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 text-xs text-amber-100 space-y-1">
			<?php if ( ! empty( $stats['items_unverified'] ) ) : ?>
				<p><strong><?php echo esc_html( (string) (int) $stats['items_unverified'] ); ?> mục</strong> không đối chiếu được số hiệu với file gốc (AI có thể chép sai hoặc mục không ghi số hiệu) — xem các mục có viền vàng bên dưới.</p>
			<?php endif; ?>
			<?php if ( ! empty( $stats['missed_refs'] ) ) : ?>
				<p><strong>Số hiệu có trong file nhưng không nằm trong mục nào:</strong> <span class="font-mono"><?php echo esc_html( implode( ', ', array_slice( (array) $stats['missed_refs'], 0, 25 ) ) ); ?></span></p>
			<?php endif; ?>
			<?php if ( ! empty( $stats['errors'] ) ) : ?>
				<p><strong>Lỗi khi trích:</strong> <?php echo esc_html( implode( '; ', array_slice( (array) $stats['errors'], 0, 5 ) ) ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $can ) : ?>
		<div class="flex flex-wrap gap-2">
			<?php echo cvc_syl_op( 'pilot', array( 'id' => $id, 'limit' => 5 ), 'Sinh bài học & câu hỏi cho 5 văn bản dùng nhiều nhất', $btn . ' bg-cyan-500 text-slate-950', 'Xếp hàng sinh nội dung cho 5 văn bản chính thức được nhắc nhiều nhất?' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo cvc_syl_op( 'reprocess', array( 'id' => $id ), 'Trích lại danh mục', $btn . ' border border-slate-600 text-slate-200', 'Trích lại toàn bộ danh mục bằng AI?' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	<?php endif; ?>

	<?php
	$docs    = (array) $cov['documents'];
	$filters = array(
		''         => array( 'Tất cả', static fn ( $x ) => true ),
		'thieu'    => array( 'Chưa có nguồn', static fn ( $x ) => in_array( $x['state'], array( 'missing', 'unresolved' ), true ) ),
		'tham-khao' => array( 'Nguồn tham khảo', static fn ( $x ) => 'reference' === $x['state'] ),
		'vong-1'   => array( 'Vòng 1', static fn ( $x ) => in_array( 1, (array) $x['rounds'], true ) ),
		'co-cau-hoi' => array( 'Đã có câu hỏi', static fn ( $x ) => (int) $x['questions_published'] > 0 ),
	);
	$filter  = isset( $filters[ $filter ] ) ? $filter : '';
	$shown   = array_values( array_filter( $docs, $filters[ $filter ][1] ) );
	?>
	<section class="space-y-2" id="van-ban">
		<div class="flex flex-wrap items-end justify-between gap-2">
			<h2 class="text-lg font-black text-white">Văn bản cần ôn (<?php echo esc_html( (string) count( $shown ) ); ?>)</h2>
			<nav class="flex flex-wrap gap-1" aria-label="Lọc văn bản">
				<?php foreach ( $filters as $key => $f ) : ?>
					<a href="<?php echo esc_url( cvc_syl_url( 'on-tap', $id, '' !== $key ? array( 'loc' => $key ) : array() ) . '#van-ban' ); ?>" class="px-2.5 py-1 rounded-lg text-xs font-bold <?php echo $key === $filter ? 'bg-cyan-500 text-slate-950' : 'border border-slate-700 text-slate-300'; ?>"><?php echo esc_html( $f[0] ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
		<div class="overflow-x-auto bg-[#0A192F] border border-slate-800 rounded-2xl">
			<table class="w-full text-sm">
				<thead class="text-[11px] uppercase tracking-wider text-slate-400 text-left">
					<tr><th class="p-3">Văn bản</th><th class="p-3">Vòng</th><th class="p-3 text-right" title="Số mục trong danh mục này / mọi danh mục">Nhắc</th><th class="p-3">Nguồn</th><th class="p-3">Toàn văn</th><th class="p-3 text-right">Điều</th><th class="p-3 text-right">Bài học</th><th class="p-3 text-right">Câu hỏi</th><th class="p-3"></th></tr>
				</thead>
				<tbody>
					<?php foreach ( $shown as $doc ) : ?>
						<tr class="border-t border-slate-800 align-top">
							<td class="p-3 max-w-md">
								<a class="font-bold text-slate-100 hover:text-cyan-300 font-mono text-xs" href="<?php echo esc_url( cvc_syl_url( 'van-ban', (int) $doc['id'] ) ); ?>"><?php echo esc_html( (string) $doc['number'] ); ?></a>
								<div class="text-xs text-slate-400 line-clamp-2"><?php echo esc_html( (string) $doc['title'] ); ?></div>
								<?php if ( ! empty( $doc['expired'] ) ) : ?><div class="text-[11px] font-bold text-rose-300">Đã hết hiệu lực</div><?php endif; ?>
							</td>
							<td class="p-3 text-xs text-slate-300 whitespace-nowrap"><?php echo esc_html( implode( ', ', array_map( static fn ( $r ) => 'V' . $r, (array) $doc['rounds'] ) ) ); ?></td>
							<td class="p-3 text-right tabular-nums text-slate-300"><?php echo esc_html( (int) $doc['mentions'] . ' / ' . (int) $doc['mentions_total'] ); ?></td>
							<td class="p-3"><?php echo cvc_syl_chip( (string) $doc['state'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td class="p-3 text-xs text-slate-300 whitespace-nowrap">
								<?php
								if ( (int) $doc['text_length'] > 0 ) {
									echo esc_html( array( 'pdf_text' => 'PDF chữ', 'docx' => 'DOCX', 'ocr' => 'OCR', 'html' => 'Trang web' )[ $doc['text_source'] ] ?? (string) $doc['text_source'] );
								} elseif ( ! empty( $doc['ocr_progress'] ) ) {
									echo esc_html( 'OCR ' . (int) $doc['ocr_progress'][0] . '/' . (int) $doc['ocr_progress'][1] . ' trang' );
								} else {
									echo '—';
								}
								?>
							</td>
							<td class="p-3 text-right tabular-nums text-slate-300"><?php echo esc_html( (string) (int) $doc['articles'] ); ?></td>
							<td class="p-3 text-right tabular-nums text-slate-300"><?php echo esc_html( (int) $doc['knowledge_published'] . ( (int) $doc['knowledge_draft'] ? ' +' . (int) $doc['knowledge_draft'] : '' ) ); ?></td>
							<td class="p-3 text-right tabular-nums">
								<span class="<?php echo (int) $doc['questions_published'] >= 20 ? 'text-emerald-300 font-black' : 'text-slate-300'; ?>"><?php echo esc_html( (string) (int) $doc['questions_published'] ); ?></span><?php if ( (int) $doc['questions_draft'] ) : ?><span class="text-amber-300 text-xs"> +<?php echo esc_html( (string) (int) $doc['questions_draft'] ); ?></span><?php endif; ?>
								<?php if ( ! empty( $doc['run'] ) && in_array( $doc['run']['status'], array( 'queued', 'running' ), true ) ) : ?>
									<div class="text-[11px] text-cyan-300">đang sinh <?php echo esc_html( (int) $doc['run']['groups_done'] . '/' . (int) $doc['run']['groups_total'] ); ?></div>
								<?php endif; ?>
							</td>
							<td class="p-3 whitespace-nowrap text-right">
								<?php if ( $can && in_array( $doc['state'], array( 'missing', 'unresolved' ), true ) ) : ?>
									<?php echo cvc_syl_op( 'resolve', array( 'doc' => (int) $doc['id'], 'back' => $id ), 'Tìm lại', $btn . ' border border-slate-600 text-slate-200' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php elseif ( $can && (int) $doc['articles'] > 0 && empty( $doc['run'] ) ) : ?>
									<?php echo cvc_syl_op( 'generate', array( 'doc' => (int) $doc['id'], 'back' => $id ), 'Sinh nội dung', $btn . ' bg-cyan-500/90 text-slate-950' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>

	<section class="space-y-2">
		<h2 class="text-lg font-black text-white">Danh mục theo vòng / vị trí</h2>
		<?php foreach ( (array) $cov['sections'] as $sec ) : ?>
			<details class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4">
				<summary class="cursor-pointer text-sm font-bold text-slate-100"><span class="text-cyan-300">Vòng <?php echo esc_html( (string) (int) $sec['round'] ); ?></span> · <?php echo esc_html( trim( ( $sec['code'] ? $sec['code'] . '. ' : '' ) . $sec['title'] ) ); ?> <span class="text-slate-500 font-normal">(<?php echo esc_html( (string) count( (array) $sec['items'] ) ); ?> mục)</span></summary>
				<ol class="mt-3 space-y-2 text-sm list-decimal pl-5">
					<?php foreach ( (array) $sec['items'] as $it ) : ?>
						<li class="<?php echo empty( $it['verified_in_source'] ) ? 'border-l-2 border-amber-400 pl-2' : ''; ?>">
							<span class="text-slate-200"><?php echo esc_html( (string) $it['text'] ); ?></span>
							<?php if ( ! empty( $it['includes_amendments'] ) ) : ?><span class="text-[11px] text-amber-300"> (kèm văn bản sửa đổi)</span><?php endif; ?>
							<span class="flex flex-wrap gap-1 mt-1">
								<?php foreach ( (array) $it['documents'] as $ld ) : ?>
									<a href="<?php echo esc_url( cvc_syl_url( 'van-ban', (int) $ld['id'] ) ); ?>"><?php echo cvc_syl_chip( (string) $ld['state'], (string) $ld['number'] . ( 'amendment' === $ld['role'] ? ' (sửa đổi)' : '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
								<?php endforeach; ?>
								<?php if ( empty( $it['documents'] ) ) : ?><span class="text-[11px] text-slate-500">không nhận ra số hiệu</span><?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ol>
			</details>
		<?php endforeach; ?>
	</section>
	<?php
}

/* ------------------------------------------------------------------ */
/* 1 văn bản                                                           */
/* ------------------------------------------------------------------ */

function cvc_admin_render_legal_document( int $id ): void {
	$result = cvc_admin_api_get( '/api/admin/legal-pipeline/documents/' . $id );
	$can    = cvc_admin_can( 'recruitment.publish' );
	$btn    = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$input  = cvc_admin_input_class();
	?>
	<a class="text-sm text-cyan-300 hover:underline" href="javascript:history.back()">← Quay lại</a>
	<?php
	if ( ! $result['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $result ) );
		return;
	}
	$d    = (array) ( $result['data']['data'] ?? array() );
	$doc  = (array) ( $d['document'] ?? array() );
	$src  = array( 'congbao' => 'Công báo Chính phủ', 'vanban_chinhphu' => 'vanban.chinhphu.vn', 'official_url' => 'URL chính thức', 'reference' => 'Nguồn tham khảo', 'upload' => 'File tải lên' );
	?>
	<header class="space-y-2">
		<p class="text-xs text-slate-400 font-mono"><?php echo esc_html( (string) $doc['document_number'] ); ?> · <?php echo esc_html( (string) ( $doc['kind_label'] ?? $doc['document_type'] ) ); ?></p>
		<h1 class="text-xl font-black text-white"><?php echo esc_html( (string) $doc['title'] ); ?></h1>
		<p class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
			<?php echo cvc_syl_chip( (string) $doc['verification_status'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( ! empty( $doc['source_kind'] ) ) : ?><span><?php echo esc_html( $src[ $doc['source_kind'] ] ?? (string) $doc['source_kind'] ); ?></span><?php endif; ?>
			<?php if ( ! empty( $doc['source_url'] ) ) : ?><a class="text-cyan-300 underline" href="<?php echo esc_url( (string) $doc['source_url'] ); ?>" target="_blank" rel="noopener nofollow">File gốc</a><?php endif; ?>
			<span>Ban hành <?php echo esc_html( cvc_admin_format( $doc['issued_date'] ?? null, 'date' ) ); ?></span>
			<span>Hiệu lực <?php echo esc_html( cvc_admin_format( $doc['effective_date'] ?? null, 'date' ) ); ?></span>
			<?php if ( ! empty( $doc['expiry_date'] ) ) : ?><span class="text-rose-300">Hết hiệu lực <?php echo esc_html( cvc_admin_format( $doc['expiry_date'], 'date' ) ); ?></span><?php endif; ?>
			<span>Nhắc trong <?php echo esc_html( (string) (int) $doc['mention_count'] ); ?> mục danh mục</span>
		</p>
		<?php if ( ! empty( $doc['resolve_error'] ) && 'verified' !== $doc['verification_status'] ) : ?>
			<p class="text-xs text-rose-300"><?php echo esc_html( (string) $doc['resolve_error'] ); ?></p>
		<?php endif; ?>
		<p class="text-xs text-slate-300">
			Toàn văn: <?php echo (int) $doc['text_length'] > 0 ? esc_html( number_format( (int) $doc['text_length'], 0, ',', '.' ) . ' ký tự (' . $doc['text_source'] . ')' ) : '—'; ?>
			<?php if ( (int) $doc['page_count'] > 0 && 'done' !== $doc['ocr_status'] && (int) $doc['text_length'] === 0 ) : ?>
				· OCR <?php echo esc_html( (int) $doc['ocr_pages_done'] . '/' . (int) $doc['page_count'] ); ?> trang (bản scan — đang đọc dần mỗi phút)
			<?php endif; ?>
			· <?php echo esc_html( (string) (int) $doc['articles_count'] ); ?> Điều/phần
		</p>
	</header>

	<?php if ( $can ) : ?>
		<section class="grid gap-3 md:grid-cols-3">
			<div class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4 space-y-2">
				<h2 class="text-sm font-black text-white">Tìm nguồn tự động</h2>
				<p class="text-xs text-slate-400">Công báo Chính phủ → vanban.chinhphu.vn. Văn bản Đảng, văn bản địa phương cần nhập tay.</p>
				<?php echo cvc_syl_op( 'resolve', array( 'doc' => $id ), 'Tìm lại nguồn', $btn . ' border border-slate-600 text-slate-200' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( (int) $doc['articles_count'] > 0 ) : ?>
					<?php echo cvc_syl_op( 'generate', array( 'doc' => $id ), 'Sinh bài học & câu hỏi', $btn . ' bg-cyan-500 text-slate-950', 'Sinh bài học và câu hỏi cho văn bản này? Câu đạt kiểm tra sẽ tự đăng.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4 space-y-2">
				<?php wp_nonce_field( 'cvc_admin_syllabus' ); ?>
				<input type="hidden" name="action" value="cvc_admin_syllabus"><input type="hidden" name="op" value="import"><input type="hidden" name="doc" value="<?php echo esc_attr( (string) $id ); ?>">
				<h2 class="text-sm font-black text-white">Nhập từ URL</h2>
				<p class="text-xs text-slate-400">Trang .gov.vn / dangcongsan.vn = chính thức; nguồn khác = tham khảo (không tự đăng câu hỏi).</p>
				<label class="sr-only" for="imp-url">URL văn bản</label>
				<input type="url" id="imp-url" name="url" required placeholder="https://… (PDF, DOCX hoặc trang toàn văn)" class="<?php echo esc_attr( $input ); ?> font-mono text-xs">
				<button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-slate-200 text-slate-950">Nhập</button>
			</form>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4 space-y-2">
				<?php wp_nonce_field( 'cvc_admin_syllabus' ); ?>
				<input type="hidden" name="action" value="cvc_admin_syllabus"><input type="hidden" name="op" value="import"><input type="hidden" name="doc" value="<?php echo esc_attr( (string) $id ); ?>">
				<h2 class="text-sm font-black text-white">Tải file lên</h2>
				<input type="file" name="file" accept=".pdf,.docx" required class="block text-xs text-slate-300 file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-slate-700 file:text-slate-100">
				<label class="flex items-center gap-2 text-xs text-slate-300"><input type="checkbox" name="official" value="1" class="accent-emerald-500"> File lấy từ nguồn chính thức</label>
				<button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-slate-200 text-slate-950">Tải lên</button>
			</form>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['relations'] ) ) : ?>
		<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4">
			<h2 class="text-sm font-black text-white mb-2">Quan hệ văn bản</h2>
			<ul class="space-y-1 text-sm">
				<?php foreach ( (array) $d['relations'] as $r ) : ?>
					<li class="text-slate-300">
						<?php if ( 'out' === $r['direction'] ) : ?>Văn bản này <?php echo esc_html( (string) $r['label'] ); ?><?php else : ?>Được <?php echo esc_html( (string) $r['label'] ); ?> bởi<?php endif; ?>
						<a class="font-mono text-cyan-300 hover:underline" href="<?php echo esc_url( cvc_syl_url( 'van-ban', (int) ( $r['other']['id'] ?? 0 ) ) ); ?>"><?php echo esc_html( (string) ( $r['other']['document_number'] ?? '' ) ); ?></a>
						<?php echo cvc_syl_chip( (string) ( $r['other']['verification_status'] ?? 'unresolved' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['runs'] ) ) : ?>
		<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4">
			<h2 class="text-sm font-black text-white mb-2">Lượt sinh nội dung</h2>
			<ul class="space-y-1 text-xs text-slate-300">
				<?php foreach ( (array) $d['runs'] as $run ) : ?>
					<li>#<?php echo esc_html( (string) $run['id'] ); ?> · <?php echo esc_html( array( 'queued' => 'Chờ chạy', 'running' => 'Đang chạy', 'done' => 'Xong', 'failed' => 'Lỗi' )[ $run['status'] ] ?? (string) $run['status'] ); ?> · nhóm <?php echo esc_html( (int) $run['groups_done'] . '/' . (int) $run['groups_total'] ); ?> · bài học <?php echo esc_html( (int) $run['knowledge_published'] . '/' . (int) $run['knowledge_created'] ); ?> đăng · câu hỏi: <?php echo esc_html( (int) $run['questions_published'] ); ?> tự đăng, <?php echo esc_html( (string) (int) $run['questions_pending'] ); ?> chờ duyệt, <?php echo esc_html( (string) (int) $run['questions_rejected'] ); ?> loại<?php echo ! empty( $run['error'] ) ? ' · ' . esc_html( (string) $run['error'] ) : ''; ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $d['articles'] ) ) : ?>
		<details class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4">
			<summary class="cursor-pointer text-sm font-black text-white">Cấu trúc (<?php echo esc_html( (string) count( (array) $d['articles'] ) ); ?> Điều/phần)</summary>
			<ol class="mt-2 space-y-0.5 text-xs text-slate-300">
				<?php $chapter = null; ?>
				<?php foreach ( (array) $d['articles'] as $a ) : ?>
					<?php if ( ! empty( $a['chapter'] ) && $a['chapter'] !== $chapter ) : $chapter = $a['chapter']; ?>
						<li class="pt-2 font-bold text-cyan-300"><?php echo esc_html( (string) $chapter ); ?></li>
					<?php endif; ?>
					<li><?php echo esc_html( ( 'P' === substr( (string) $a['number'], 0, 1 ) ? '' : 'Điều ' . $a['number'] . '. ' ) . ( $a['title'] ?? '' ) ); ?></li>
				<?php endforeach; ?>
			</ol>
		</details>
	<?php endif; ?>
	<?php
}

/* ------------------------------------------------------------------ */
/* Hàng chờ duyệt nội dung AI                                          */
/* ------------------------------------------------------------------ */

function cvc_admin_render_content_review(): void {
	$result = cvc_admin_api_get( '/api/admin/content-review' );
	$can    = cvc_admin_can( 'recruitment.publish' );
	$btn    = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$fmt    = array( 'single' => 'Một đáp án', 'true_false' => 'Đúng/Sai', 'fill_blank' => 'Điền từ', 'scenario' => 'Tình huống' );
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-white">Duyệt câu hỏi & bài học AI</h1>
		<p class="text-sm text-slate-400 max-w-3xl">Chỉ những nội dung KHÔNG tự đăng mới nằm ở đây: model kiểm tra (deepseek-v4-pro) chọn đáp án khác, cho là mơ hồ/không đủ căn cứ, có cảnh báo, hoặc văn bản chỉ có nguồn tham khảo. Câu nào cũng có đoạn trích nguyên văn đã được máy đối chiếu.</p>
	</header>
	<?php cvc_ingestion_tabs( 'on-tap' ); ?>
	<?php
	if ( ! $result['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $result ) );
		return;
	}
	$d = (array) ( $result['data']['data'] ?? array() );
	?>
	<p class="text-sm text-slate-300">Câu hỏi chờ duyệt: <strong class="tabular-nums"><?php echo esc_html( (string) (int) ( $d['total'] ?? 0 ) ); ?></strong> (hiện 20 câu mới nhất)</p>
	<ul class="space-y-3">
		<?php foreach ( (array) ( $d['questions'] ?? array() ) as $q ) : ?>
			<?php $v = (array) ( $q['validation'] ?? array() ); ?>
			<li class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4 space-y-2">
				<p class="text-xs text-slate-400"><span class="font-mono text-cyan-300"><?php echo esc_html( (string) $q['document'] ); ?></span> · <?php echo esc_html( (string) $q['source_reference'] ); ?> · <?php echo esc_html( $fmt[ $q['format'] ] ?? (string) $q['format'] ); ?> · <?php echo esc_html( (string) $q['difficulty'] ); ?></p>
				<p class="font-bold text-slate-100"><?php echo esc_html( (string) $q['text'] ); ?></p>
				<ul class="grid sm:grid-cols-2 gap-1 text-sm">
					<?php foreach ( (array) $q['options'] as $o ) : ?>
						<li class="px-2 py-1 rounded-lg <?php echo ! empty( $o['correct'] ) ? 'bg-emerald-500/15 text-emerald-200 font-bold' : ( ( $v['validator_answer'] ?? '' ) === $o['key'] ? 'bg-rose-500/15 text-rose-200' : 'text-slate-300' ); ?>"><?php echo esc_html( $o['key'] . '. ' . $o['text'] ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p class="text-xs text-slate-400"><strong class="text-slate-300">Trích nguyên văn:</strong> “<?php echo esc_html( (string) $q['source_excerpt'] ); ?>”</p>
				<p class="text-xs text-slate-400"><strong class="text-slate-300">Giải thích:</strong> <?php echo esc_html( (string) $q['explanation'] ); ?></p>
				<p class="text-xs text-amber-200">
					Model kiểm tra chọn: <strong><?php echo esc_html( (string) ( $v['validator_answer'] ?? '—' ) ); ?></strong>
					<?php echo isset( $v['answerable'] ) && ! $v['answerable'] ? ' · không đủ căn cứ' : ''; ?>
					<?php echo ! empty( $v['ambiguous'] ) ? ' · mơ hồ' : ''; ?>
					<?php echo ! empty( $v['issues'] ) ? ' · ' . esc_html( (string) $v['issues'] ) : ''; ?>
					<?php echo ! empty( $v['warnings'] ) ? ' · ' . esc_html( implode( '; ', (array) $v['warnings'] ) ) : ''; ?>
				</p>
				<?php if ( $can ) : ?>
					<div class="flex gap-2">
						<?php echo cvc_syl_op( 'review_q', array( 'qid' => (int) $q['id'], 'decision' => 'approve' ), 'Duyệt & đăng', $btn . ' bg-emerald-500 text-slate-950' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo cvc_syl_op( 'review_q', array( 'qid' => (int) $q['id'], 'decision' => 'reject' ), 'Loại', $btn . ' bg-rose-500 text-white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( ! empty( $d['knowledge'] ) ) : ?>
		<h2 class="text-lg font-black text-white">Bài học chờ duyệt</h2>
		<ul class="space-y-3">
			<?php foreach ( (array) $d['knowledge'] as $k ) : ?>
				<li class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4 space-y-1">
					<p class="font-bold text-slate-100"><?php echo esc_html( (string) $k['title'] ); ?> <span class="text-xs text-slate-500 font-normal"><?php echo esc_html( (string) $k['source_reference'] ); ?></span></p>
					<p class="text-sm text-slate-300"><?php echo esc_html( (string) $k['summary'] ); ?></p>
					<?php if ( ! empty( $k['validation']['issues'] ) ) : ?><p class="text-xs text-amber-200">Kiểm tra: <?php echo esc_html( (string) $k['validation']['issues'] ); ?></p><?php endif; ?>
					<?php if ( $can ) : ?>
						<div class="flex gap-2 pt-1">
							<?php echo cvc_syl_op( 'review_k', array( 'kid' => (int) $k['id'], 'decision' => 'approve' ), 'Duyệt & đăng', $btn . ' bg-emerald-500 text-slate-950' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php echo cvc_syl_op( 'review_k', array( 'kid' => (int) $k['id'], 'decision' => 'reject' ), 'Loại', $btn . ' bg-rose-500 text-white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php
}

/* ------------------------------------------------------------------ */
/* Thao tác                                                            */
/* ------------------------------------------------------------------ */

add_action( 'admin_post_cvc_admin_syllabus', 'cvc_admin_handle_syllabus' );
add_action( 'admin_post_nopriv_cvc_admin_syllabus', 'cvc_admin_handle_syllabus' );

function cvc_admin_handle_syllabus(): void {
	check_admin_referer( 'cvc_admin_syllabus' );
	$token = cvc_admin_require_staff();
	$op    = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );
	$id    = absint( $_POST['id'] ?? 0 );
	$doc   = absint( $_POST['doc'] ?? 0 );
	$back  = wp_get_referer() ?: cvc_syl_url( 'on-tap' );
	$msg   = static fn ( array $r, string $ok ) => $r['ok'] ? (string) ( $r['data']['message'] ?? $ok ) : (string) ( $r['data']['message'] ?? cvc_admin_error_message( $r ) );

	switch ( $op ) {
		case 'create':
			$client = new CVC_Api_Client( null, 170 );
			if ( ! empty( $_FILES['file']['tmp_name'] ) && is_uploaded_file( $_FILES['file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$file   = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$name   = sanitize_file_name( (string) $file['name'] );
				$result = $client->post_multipart( '/api/admin/syllabi', array(), array( 'file' => array( (string) $file['tmp_name'], $name, 'application/octet-stream' ) ), $token );
			} else {
				$url = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
				if ( '' === $url ) {
					cvc_admin_redirect( $back, 'error', 'Nhập URL thông báo hoặc chọn file danh mục.' );
					return;
				}
				$result = $client->post( '/api/admin/syllabi', array( 'url' => $url ), $token );
			}
			$ids = (array) ( $result['data']['data']['ids'] ?? array() );
			cvc_admin_redirect( $result['ok'] && 1 === count( $ids ) ? cvc_syl_url( 'on-tap', (int) $ids[0] ) : cvc_syl_url( 'on-tap' ), $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã nhận danh mục.' ) );
			return;

		case 'reprocess':
			$result = cvc_admin_client()->post( '/api/admin/syllabi/' . $id . '/reprocess', array(), $token );
			cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã xếp hàng.' ) );
			return;

		case 'pilot':
			$result = cvc_admin_client()->post( '/api/admin/syllabi/' . $id . '/pilot', array( 'limit' => absint( $_POST['limit'] ?? 5 ) ), $token );
			cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã xếp hàng.' ) );
			return;

		case 'resolve':
			$result = ( new CVC_Api_Client( null, 170 ) )->post( '/api/admin/legal-pipeline/documents/' . $doc . '/resolve', array(), $token );
			cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã tìm nguồn.' ) );
			return;

		case 'import':
			$client = new CVC_Api_Client( null, 170 );
			if ( ! empty( $_FILES['file']['tmp_name'] ) && is_uploaded_file( $_FILES['file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$file   = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$name   = sanitize_file_name( (string) $file['name'] );
				$result = $client->post_multipart( '/api/admin/legal-pipeline/documents/' . $doc . '/import', array( 'official' => ! empty( $_POST['official'] ) ? '1' : '0' ), array( 'file' => array( (string) $file['tmp_name'], $name, 'application/octet-stream' ) ), $token );
			} else {
				$result = $client->post( '/api/admin/legal-pipeline/documents/' . $doc . '/import', array( 'url' => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ) ), $token );
			}
			cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã nhập văn bản.' ) );
			return;

		case 'generate':
			$result = cvc_admin_client()->post( '/api/admin/legal-pipeline/documents/' . $doc . '/generate', array(), $token );
			cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã xếp hàng.' ) );
			return;

		case 'review_q':
			$result = cvc_admin_client()->post( '/api/admin/content-review/questions/' . absint( $_POST['qid'] ?? 0 ), array( 'action' => 'approve' === ( $_POST['decision'] ?? '' ) ? 'approve' : 'reject' ), $token );
			cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã cập nhật.' ) );
			return;

		case 'review_k':
			$result = cvc_admin_client()->post( '/api/admin/content-review/knowledge/' . absint( $_POST['kid'] ?? 0 ), array( 'action' => 'approve' === ( $_POST['decision'] ?? '' ) ? 'approve' : 'reject' ), $token );
			cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $msg( $result, 'Đã cập nhật.' ) );
			return;
	}

	cvc_admin_redirect( $back, 'error', 'Thao tác không hợp lệ.' );
}
