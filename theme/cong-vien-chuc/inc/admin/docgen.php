<?php
/**
 * Quản trị → Tài liệu tự sinh (31/10/2026, kế hoạch claude/ke-hoach-tai-lieu-tu-sinh.md GĐ1).
 * /quan-tri/tai-lieu-ai/ (danh sách + tạo mới), /quan-tri/tai-lieu-ai/{id}/ (nghiên cứu, dàn ý, duyệt từng phần, dựng PDF, xuất bản).
 * API: /api/admin/docgen/* (quyền document.view / document.create).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_dg_badge( string $status, string $label ): string {
	$cls = array(
		'outline_review' => 'bg-amber-100 text-amber-900', 'writing' => 'bg-sky-100 text-sky-900', 'verifying' => 'bg-sky-100 text-sky-900',
		'review' => 'bg-indigo-100 text-indigo-900', 'rendered' => 'bg-emerald-100 text-emerald-900', 'published' => 'bg-emerald-600 text-white',
		'failed' => 'bg-rose-100 text-rose-900', 'pending' => 'bg-slate-100 text-slate-700', 'drafted' => 'bg-sky-100 text-sky-900',
		'verified' => 'bg-emerald-100 text-emerald-900', 'flagged' => 'bg-rose-100 text-rose-900', 'approved' => 'bg-emerald-600 text-white', 'rejected' => 'bg-amber-100 text-amber-900',
	)[ $status ] ?? 'bg-slate-100 text-slate-700';

	return '<span class="inline-block px-2 py-0.5 rounded-md text-xs font-bold ' . esc_attr( $cls ) . '">' . esc_html( $label ) . '</span>';
}

function cvc_dg_styles(): void {
	?>
	<style>
	.dg-card{background:#fff;border:1px solid #dfe5eb;border-radius:14px;padding:16px 18px}
	.dg-tbl{width:100%;border-collapse:collapse;font-size:14px}.dg-tbl th,.dg-tbl td{padding:8px 10px;border-bottom:1px solid #edf0f3;text-align:left;vertical-align:top}.dg-tbl th{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#5b6470}
	.dg-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;border:1px solid #cbd5df;background:#fff;font-weight:bold;font-size:14px;cursor:pointer}
	.dg-btn.is-primary{background:#0f2a4a;border-color:#0f2a4a;color:#fff}.dg-btn.is-ok{background:#1f7a5c;border-color:#1f7a5c;color:#fff}.dg-btn.is-warn{border-color:#d9a978;color:#9a4612}
	.dg-btn[disabled]{opacity:.45;cursor:not-allowed}
	.dg-sec{border:1px solid #e3e8ee;border-radius:12px;margin:0 0 10px;background:#fff}
	.dg-sec>summary{display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;list-style:none}
	.dg-sec>summary::-webkit-details-marker{display:none}
	.dg-body{padding:0 14px 14px}
	.dg-preview{border:1px solid #edf0f3;border-radius:10px;padding:12px 16px;background:#fcfcfa;max-height:520px;overflow:auto;font-size:15px;line-height:1.55}
	.dg-preview h3{font-size:16px;font-weight:bold;margin:12px 0 6px;color:#0f2a4a}.dg-preview p{margin:0 0 8px}.dg-preview ul,.dg-preview ol{margin:0 0 8px 20px;list-style:disc}.dg-preview ol{list-style:decimal}
	.dg-preview table{border-collapse:collapse;margin:6px 0 10px;font-size:14px}.dg-preview th,.dg-preview td{border:1px solid #d5dce3;padding:4px 8px}
	.dg-preview blockquote{margin:8px 0;padding:6px 12px;background:#fbf5e8;border-left:4px solid #c9a052}
	.dg-issue{font-size:13.5px;padding:6px 10px;border-left:3px solid #c0392b;background:#fdf3f2;margin:4px 0;border-radius:4px}
	.dg-muted{color:#5b6470;font-size:13px}
	.dg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}
	.dg-kpi{border:1px solid #e3e8ee;border-radius:10px;padding:10px 12px}.dg-kpi small{display:block;color:#5b6470;font-size:12px}.dg-kpi b{font-size:20px;color:#0f2a4a}
	textarea.dg-ta{width:100%;min-height:260px;font-family:ui-monospace,monospace;font-size:13px;border:1px solid #cbd5df;border-radius:8px;padding:8px}
	</style>
	<?php
}

function cvc_admin_render_docgen( string $item_param ): void {
	cvc_dg_styles();
	if ( ctype_digit( $item_param ) ) {
		cvc_dg_render_detail( (int) $item_param );
		return;
	}
	$res = cvc_admin_api_get( '/api/admin/docgen' );
	$d   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Tài liệu tự sinh</h1>
		<p class="text-sm text-ink-muted">Cẩm nang, kỹ năng, lộ trình mang thương hiệu Công Viên Chức. AI viết từng phần có căn cứ điều luật, AI thứ hai kiểm chứng, biên tập viên duyệt rồi mới dựng PDF và xuất bản.</p>
	</header>
	<?php if ( ! $res['ok'] ) : ?><p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p><?php return; endif; ?>
	<section class="dg-card">
		<h2 class="font-black text-ink mb-2">Tạo tài liệu mới</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap gap-2 items-end">
			<?php wp_nonce_field( 'cvc_admin_docgen' ); ?><input type="hidden" name="action" value="cvc_admin_docgen"><input type="hidden" name="op" value="create">
			<label class="text-sm">Khung tài liệu<br>
				<select name="blueprint" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm">
					<?php foreach ( (array) ( $d['blueprints'] ?? array() ) as $b ) : ?>
						<option value="<?php echo esc_attr( (string) $b['key'] ); ?>"><?php echo esc_html( $b['title'] . ' (' . (int) $b['sections'] . ' phần · ' . (int) $b['notes'] . ' ghi chú nghiên cứu)' ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<button type="submit" class="dg-btn is-primary">＋ Tạo bản biên soạn</button>
		</form>
		<p class="dg-muted mt-2">Tạo xong sẽ có dàn ý để sửa và duyệt. Sau khi duyệt dàn ý, hệ thống tự viết từng phần (mỗi 5 phút hoặc bấm "Chạy bước tiếp").</p>
	</section>
	<section class="dg-card">
		<h2 class="font-black text-ink mb-2">Bản biên soạn</h2>
		<?php $gens = (array) ( $d['generations'] ?? array() ); ?>
		<?php if ( ! $gens ) : ?><p class="dg-muted">Chưa có bản biên soạn nào.</p><?php else : ?>
		<div style="overflow-x:auto"><table class="dg-tbl"><thead><tr><th>Tài liệu</th><th>Trạng thái</th><th>Tiến độ</th><th>Số từ</th><th>Chi phí AI</th><th>Cập nhật</th></tr></thead><tbody>
			<?php foreach ( $gens as $g ) : ?>
				<?php $c = (array) $g['counts']; ?>
				<tr>
					<td><a class="font-bold text-acc" href="<?php echo esc_url( cvc_admin_url( 'tai-lieu-ai', (int) $g['id'] ) ); ?>"><?php echo esc_html( $g['title'] ); ?></a> <span class="dg-muted">v<?php echo (int) $g['version']; ?></span></td>
					<td><?php echo cvc_dg_badge( (string) $g['status'], (string) $g['status_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td class="dg-muted"><?php echo esc_html( (int) ( $c['approved'] ?? 0 ) . '/' . (int) $g['total'] . ' đã duyệt · ' . ( (int) $g['total'] - (int) ( $c['pending'] ?? 0 ) ) . ' đã viết' ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $g['words'] ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( (float) $g['cost_vnd'] ) ); ?> ₫</td>
					<td class="dg-muted"><?php echo esc_html( function_exists( 'cvc_ui_dt' ) ? cvc_ui_dt( $g['updated_at'] ) : (string) $g['updated_at'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody></table></div>
		<?php endif; ?>
	</section>
	<?php
}

function cvc_dg_form_open( int $id, string $op, array $hidden = array() ): void {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
	wp_nonce_field( 'cvc_admin_docgen' );
	echo '<input type="hidden" name="action" value="cvc_admin_docgen"><input type="hidden" name="op" value="' . esc_attr( $op ) . '"><input type="hidden" name="id" value="' . (int) $id . '">';
	foreach ( $hidden as $k => $v ) {
		echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '">';
	}
}

function cvc_dg_render_detail( int $id ): void {
	$res = cvc_admin_api_get( '/api/admin/docgen/' . $id );
	if ( ! $res['ok'] ) {
		echo '<p class="text-sm text-red-700">' . esc_html( cvc_admin_error_message( $res ) ) . '</p>';
		return;
	}
	$g     = (array) $res['data']['data'];
	$secs  = (array) ( $g['sections'] ?? array() );
	$c     = (array) $g['counts'];
	$st    = (string) $g['status'];
	$all_ok = $secs && count( array_filter( $secs, static fn ( $s ) => 'approved' === $s['status'] ) ) === count( $secs );
	$ss    = (array) ( $g['section_statuses'] ?? array() );
	$all_written = $secs && count( array_filter( $secs, static fn ( $s ) => '' !== trim( (string) ( $s['content'] ?? '' ) ) ) ) === count( $secs );
	$pv_url = wp_nonce_url( admin_url( 'admin-post.php?action=cvc_admin_docgen_pdf&preview=1&id=' . $id ), 'cvc_admin_docgen_pdf' );
	?>
	<p class="text-sm"><a class="text-acc" href="<?php echo esc_url( cvc_admin_url( 'tai-lieu-ai' ) ); ?>">← Tài liệu tự sinh</a></p>
	<header class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-black text-ink"><?php echo esc_html( $g['title'] ); ?> <span class="dg-muted">v<?php echo (int) $g['version']; ?></span></h1>
			<p class="text-sm text-ink-muted"><?php echo esc_html( (string) $g['subtitle'] ); ?></p>
			<p class="mt-1"><?php echo cvc_dg_badge( $st, (string) $g['status_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		</div>
		<div class="flex flex-wrap gap-2">
			<?php if ( 'outline_review' === $st ) : ?>
				<?php cvc_dg_form_open( $id, 'approve_outline' ); ?><button class="dg-btn is-primary">✓ Duyệt dàn ý &amp; bắt đầu viết</button></form>
			<?php endif; ?>
			<?php if ( in_array( $st, array( 'writing', 'verifying' ), true ) ) : ?>
				<?php cvc_dg_form_open( $id, 'step' ); ?><button class="dg-btn">▶ Chạy bước tiếp ngay</button></form>
			<?php endif; ?>
			<?php if ( in_array( $st, array( 'review', 'rendered' ), true ) && (int) ( $c['verified'] ?? 0 ) > 0 ) : ?>
				<?php cvc_dg_form_open( $id, 'approve_verified' ); ?><button class="dg-btn" onclick="return confirm('Duyệt <?php echo (int) $c['verified']; ?> phần đã qua kiểm chứng? Hãy chắc bạn đã đọc các phần này.')">✓ Duyệt <?php echo (int) $c['verified']; ?> phần đã kiểm chứng</button></form>
			<?php endif; ?>
			<?php if ( in_array( $st, array( 'review', 'rendered' ), true ) ) : ?>
				<?php cvc_dg_form_open( $id, 'render' ); ?><button class="dg-btn is-primary"<?php echo $all_ok ? '' : ' disabled title="Duyệt hết các phần trước"'; ?>>⎙ Dựng PDF<?php echo 'rendered' === $st ? ' lại' : ''; ?></button></form>
			<?php endif; ?>
			<?php if ( $all_written && 'published' !== $st ) : ?>
				<?php cvc_dg_form_open( $id, 'preview' ); ?><button class="dg-btn" title="Dựng PDF từ nội dung hiện có, có chữ BẢN NHÁP, không đưa lên thư viện">⎙ Dựng bản xem thử</button></form>
			<?php endif; ?>
			<?php if ( ! empty( $g['meta']['preview_at'] ) ) : ?>
				<a class="dg-btn" href="<?php echo esc_url( $pv_url ); ?>" target="_blank" rel="noopener">👁 Bản xem thử (<?php echo (int) ( $g['meta']['preview_pages'] ?? 0 ); ?> tr.)</a>
			<?php endif; ?>
			<?php if ( ! empty( $g['document_id'] ) ) : ?>
				<a class="dg-btn" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cvc_admin_docgen_pdf&id=' . $id ), 'cvc_admin_docgen_pdf' ) ); ?>" target="_blank" rel="noopener">👁 Xem PDF</a>
			<?php endif; ?>
			<?php if ( 'rendered' === $st ) : ?>
				<?php cvc_dg_form_open( $id, 'publish' ); ?><button class="dg-btn is-ok" onclick="return confirm('Xuất bản tài liệu lên thư viện?')">⇪ Xuất bản</button></form>
			<?php endif; ?>
			<?php if ( 'published' === $st && ! empty( $g['document_slug'] ) ) : ?>
				<a class="dg-btn is-ok" href="<?php echo esc_url( cvc_document_url( (string) $g['document_slug'] ) ); ?>" target="_blank" rel="noopener">Xem trên thư viện ↗</a>
			<?php endif; ?>
		</div>
	</header>
	<?php if ( ! empty( $g['error'] ) ) : ?><p class="text-sm text-red-700 mt-2"><?php echo esc_html( (string) $g['error'] ); ?></p><?php endif; ?>
	<section class="dg-grid">
		<div class="dg-kpi"><small>Phần đã viết</small><b><?php echo (int) $g['total'] - (int) ( $c['pending'] ?? 0 ); ?>/<?php echo (int) $g['total']; ?></b></div>
		<div class="dg-kpi"><small>Đã duyệt</small><b><?php echo (int) ( $c['approved'] ?? 0 ); ?></b></div>
		<div class="dg-kpi"><small>Có vấn đề cần xem</small><b><?php echo (int) ( $c['flagged'] ?? 0 ); ?></b></div>
		<div class="dg-kpi"><small>Số từ</small><b><?php echo esc_html( number_format_i18n( (int) $g['words'] ) ); ?></b></div>
		<div class="dg-kpi"><small>Chi phí AI</small><b><?php echo esc_html( number_format_i18n( (float) $g['cost_vnd'] ) ); ?> ₫</b></div>
		<?php if ( ! empty( $g['meta']['pages'] ) ) : ?><div class="dg-kpi"><small>Số trang PDF</small><b><?php echo (int) $g['meta']['pages']; ?></b></div><?php endif; ?>
	</section>

	<details class="dg-card" <?php echo 'outline_review' === $st ? 'open' : ''; ?>>
		<summary class="font-black text-ink cursor-pointer">Nghiên cứu: ghi chú từ nguồn công khai (<?php echo count( (array) $g['notes'] ); ?>)</summary>
		<p class="dg-muted mt-2">Dán link bài viết công khai (báo, cổng cơ quan nhà nước, diễn đàn công khai), mỗi dòng 1 link, tối đa 8 link/lần. Hệ thống tôn trọng robots.txt, chỉ lưu ý chính đã viết lại (không chép nguyên văn, không lưu thông tin cá nhân). Ghi chú không phải căn cứ pháp lý.</p>
		<?php cvc_dg_form_open( $id, 'research' ); ?>
			<textarea name="urls" class="dg-ta" style="min-height:90px" placeholder="https://..."></textarea>
			<button class="dg-btn mt-2">🔎 Đọc &amp; ghi chú</button>
		</form>
		<?php if ( ! empty( $g['notes'] ) ) : ?>
			<div style="overflow-x:auto" class="mt-3"><table class="dg-tbl"><thead><tr><th>Loại</th><th>Ý chính (đã viết lại)</th><th>Nguồn</th><th></th></tr></thead><tbody>
				<?php foreach ( (array) $g['notes'] as $n ) : ?>
					<tr style="<?php echo 'rejected' === $n['status'] ? 'opacity:.45' : ''; ?>">
						<td class="dg-muted"><?php echo esc_html( (string) ( $g['note_kinds'][ $n['kind'] ] ?? $n['kind'] ) ); ?><br>tin cậy <?php echo (int) $n['reliability']; ?>/3<?php echo 'used' === $n['status'] ? '<br>đã dùng' : ''; ?></td>
						<td><?php echo esc_html( (string) $n['insight'] ); ?><?php if ( ! empty( $n['quote'] ) ) : ?><br><i class="dg-muted">“<?php echo esc_html( (string) $n['quote'] ); ?>”</i><?php endif; ?></td>
						<td class="dg-muted"><a href="<?php echo esc_url( (string) $n['url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( (string) ( $n['source_name'] ?: wp_parse_url( (string) $n['url'], PHP_URL_HOST ) ) ); ?></a></td>
						<td><?php cvc_dg_form_open( $id, 'note', array( 'nid' => (int) $n['id'], 'status' => 'rejected' === $n['status'] ? 'new' : 'rejected' ) ); ?><button class="dg-btn"><?php echo 'rejected' === $n['status'] ? 'Khôi phục' : 'Loại'; ?></button></form></td>
					</tr>
				<?php endforeach; ?>
			</tbody></table></div>
		<?php endif; ?>
	</details>

	<?php if ( 'outline_review' === $st ) : ?>
		<section class="dg-card">
			<h2 class="font-black text-ink">Dàn ý (sửa yêu cầu từng phần trước khi duyệt)</h2>
			<?php cvc_dg_form_open( $id, 'brief' ); ?>
				<?php foreach ( $secs as $s ) : ?>
					<label class="block mt-3 text-sm font-bold"><?php echo (int) $s['idx'] . '. ' . esc_html( (string) $s['heading'] ); ?> <span class="dg-muted">(<?php echo esc_html( (string) $s['kind'] ); ?>)</span>
						<textarea name="briefs[<?php echo (int) $s['id']; ?>]" class="dg-ta" style="min-height:70px;font-family:inherit;font-size:14px"><?php echo esc_textarea( (string) $s['brief'] ); ?></textarea>
					</label>
				<?php endforeach; ?>
				<button class="dg-btn mt-3">💾 Lưu dàn ý</button>
			</form>
		</section>
	<?php else : ?>
		<section>
			<h2 class="font-black text-ink mb-2">Các phần</h2>
			<?php foreach ( $secs as $s ) : ?>
				<?php $v = (array) ( $s['verify'] ?? array() ); $issues = (array) ( $v['issues'] ?? array() ); ?>
				<details class="dg-sec" <?php echo in_array( $s['status'], array( 'flagged', 'verified' ), true ) ? 'open' : ''; ?>>
					<summary><b><?php echo (int) $s['idx']; ?>. <?php echo esc_html( (string) $s['heading'] ); ?></b> <?php echo cvc_dg_badge( (string) $s['status'], (string) ( $ss[ $s['status'] ] ?? $s['status'] ) ); // phpcs:ignore ?> <span class="dg-muted"><?php echo (int) $s['words']; ?> từ<?php echo $issues ? ' · ' . count( $issues ) . ' vấn đề' : ''; ?></span></summary>
					<div class="dg-body">
						<?php if ( '' !== (string) $s['html'] ) : ?>
							<div class="dg-preview"><?php echo wp_kses_post( (string) $s['html'] ); ?></div>
						<?php else : ?><p class="dg-muted">Chưa viết.</p><?php endif; ?>
						<?php if ( $issues ) : ?>
							<p class="font-bold text-sm mt-3">Kiểm chứng phát hiện:</p>
							<?php foreach ( $issues as $is ) : ?>
								<div class="dg-issue"><b><?php echo esc_html( (string) ( $is['by'] ?? '' ) ); ?>:</b> <?php echo esc_html( (string) ( $is['problem'] ?? '' ) ); ?><?php if ( ! empty( $is['quote'] ) ) : ?> <i>“<?php echo esc_html( (string) $is['quote'] ); ?>”</i><?php endif; ?><?php if ( ! empty( $is['fix'] ) ) : ?><br><span class="dg-muted">Gợi ý sửa: <?php echo esc_html( (string) $is['fix'] ); ?></span><?php endif; ?></div>
							<?php endforeach; ?>
						<?php elseif ( 'verified' === $s['status'] || 'approved' === $s['status'] ) : ?><p class="text-sm text-emerald-700 mt-2">✓ Kiểm chứng không phát hiện vấn đề.</p><?php endif; ?>
						<?php $cit = (array) $s['citations']; ?>
						<?php if ( ! empty( $cit['articles'] ) || ! empty( $cit['gaps'] ) ) : ?>
							<details class="mt-2"><summary class="dg-muted cursor-pointer">Nguồn đưa cho AI (<?php echo count( (array) $cit['articles'] ); ?> điều luật<?php echo ! empty( $cit['gaps'] ) ? ', ' . count( (array) $cit['gaps'] ) . ' điểm thiếu căn cứ' : ''; ?>)</summary>
								<ul class="dg-muted" style="margin:6px 0 0 18px;list-style:disc"><?php foreach ( (array) $cit['articles'] as $a ) : ?><li>[<?php echo esc_html( (string) $a['label'] ); ?>] <?php echo esc_html( (string) $a['cite'] ); ?></li><?php endforeach; ?>
								<?php foreach ( (array) $cit['gaps'] as $gap ) : ?><li><b>Thiếu căn cứ:</b> <?php echo esc_html( (string) $gap ); ?></li><?php endforeach; ?></ul>
							</details>
						<?php endif; ?>
						<?php if ( '' !== (string) $s['html'] && in_array( $st, array( 'review', 'rendered', 'verifying', 'writing' ), true ) ) : ?>
							<?php cvc_dg_form_open( $id, 'section', array( 'sid' => (int) $s['id'] ) ); ?>
								<details class="mt-3"><summary class="text-sm font-bold cursor-pointer">✎ Sửa nội dung (Markdown)</summary><textarea name="content" class="dg-ta mt-2"><?php echo esc_textarea( (string) $s['content'] ); ?></textarea></details>
								<input type="text" name="note" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm mt-2" style="width:100%" placeholder="Ghi chú khi trả lại (AI sẽ viết lại theo ghi chú này)" value="<?php echo esc_attr( (string) ( $s['reviewer_note'] ?? '' ) ); ?>">
								<div class="flex flex-wrap gap-2 mt-2">
									<button name="sub" value="approve" class="dg-btn is-ok">✓ Duyệt phần này</button>
									<button name="sub" value="save" class="dg-btn">💾 Lưu sửa</button>
									<button name="sub" value="reject" class="dg-btn is-warn">↺ Trả lại, viết lại</button>
								</div>
							</form>
						<?php endif; ?>
					</div>
				</details>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>
	<?php
}

add_action( 'admin_post_cvc_admin_docgen', 'cvc_admin_handle_docgen' );
add_action( 'admin_post_nopriv_cvc_admin_docgen', 'cvc_admin_handle_docgen' );
function cvc_admin_handle_docgen(): void {
	check_admin_referer( 'cvc_admin_docgen' );
	$token = cvc_admin_require_staff();
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$id    = absint( $_POST['id'] ?? 0 );
	$back  = $id ? cvc_admin_url( 'tai-lieu-ai', $id ) : cvc_admin_url( 'tai-lieu-ai' );
	if ( ! cvc_admin_can( 'document.create' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền.' );
		return;
	}
	$cli = new CVC_Api_Client( null, 175 );
	if ( 'create' === $op ) {
		$res = $cli->post( '/api/admin/docgen', array( 'blueprint' => sanitize_key( (string) ( $_POST['blueprint'] ?? '' ) ) ), $token );
		$new = (int) ( $res['data']['data']['id'] ?? 0 );
		cvc_admin_redirect( $new ? cvc_admin_url( 'tai-lieu-ai', $new ) : $back, $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Đã tạo.' ) : cvc_admin_error_message( $res ) );
		return;
	}
	if ( 'section' === $op ) {
		$sub = sanitize_key( (string) ( $_POST['sub'] ?? 'save' ) );
		$res = $cli->post( '/api/admin/docgen/' . $id . '/sections/' . absint( $_POST['sid'] ?? 0 ), array(
			'action'  => in_array( $sub, array( 'save', 'approve', 'reject' ), true ) ? $sub : 'save',
			'content' => isset( $_POST['content'] ) ? (string) wp_unslash( $_POST['content'] ) : null,
			'note'    => sanitize_text_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) ),
		), $token );
	} elseif ( 'note' === $op ) {
		$res = $cli->post( '/api/admin/docgen/notes/' . absint( $_POST['nid'] ?? 0 ), array( 'status' => 'new' === ( $_POST['status'] ?? '' ) ? 'new' : 'rejected' ), $token );
	} else {
		$body = array( 'action' => $op );
		if ( 'research' === $op ) {
			$body['urls'] = (string) wp_unslash( $_POST['urls'] ?? '' );
		}
		if ( 'brief' === $op ) {
			$body['briefs'] = array_map( static fn ( $v ) => sanitize_textarea_field( wp_unslash( (string) $v ) ), (array) ( $_POST['briefs'] ?? array() ) );
		}
		$res = $cli->post( '/api/admin/docgen/' . $id . '/action', $body, $token );
	}
	cvc_admin_redirect( $back, $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Đã xong.' ) : cvc_admin_error_message( $res ) );
}

/** Xem PDF đã dựng (kể cả chưa xuất bản) - proxy kèm token của biên tập viên. */
add_action( 'admin_post_cvc_admin_docgen_pdf', 'cvc_admin_handle_docgen_pdf' );
add_action( 'admin_post_nopriv_cvc_admin_docgen_pdf', 'cvc_admin_handle_docgen_pdf' );
function cvc_admin_handle_docgen_pdf(): void {
	check_admin_referer( 'cvc_admin_docgen_pdf' );
	$token = cvc_admin_require_staff();
	if ( ! cvc_admin_can( 'document.view' ) ) {
		wp_die( 'Bạn không có quyền.', 403 );
	}
	$r = wp_remote_get( rtrim( (string) cvc_api_base_url(), '/' ) . '/api/admin/docgen/' . absint( $_GET['id'] ?? 0 ) . '/pdf' . ( ! empty( $_GET['preview'] ) ? '?preview=1' : '' ), array( 'timeout' => 60, 'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Accept' => 'application/pdf' ) ) );
	if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
		wp_die( 'Chưa có bản PDF hoặc không tải được.', 404 );
	}
	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: inline; filename="tai-lieu-' . absint( $_GET['id'] ?? 0 ) . '.pdf"' );
	echo wp_remote_retrieve_body( $r ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}
