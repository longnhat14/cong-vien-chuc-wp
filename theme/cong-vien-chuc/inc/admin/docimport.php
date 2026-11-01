<?php
/**
 * Xưởng tài liệu - lối B: tải tệp sách (01/11/2026, GĐ2 .docx). Kế hoạch: claude/ke-hoach-xuong-tai-lieu.md.
 * Khung tải lên + danh sách nằm trên trang Xưởng (/quan-tri/tai-lieu-ai/); duyệt từng tệp: /quan-tri/tai-lieu-tai-len/{id}/.
 * API: /api/admin/doc-imports/* (quyền document.view / document.create).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_DI_STAGES = array(
	'analyzing' => array( 'Đang phân tích', 'bg-sky-100 text-sky-900' ),
	'review'    => array( 'Chờ duyệt', 'bg-amber-100 text-amber-900' ),
	'ready'     => array( 'Đã dựng, chờ xuất bản', 'bg-emerald-100 text-emerald-900' ),
	'published' => array( 'Đã xuất bản', 'bg-emerald-600 text-white' ),
	'blocked'   => array( 'Bị chặn', 'bg-rose-100 text-rose-900' ),
);

const CVC_DI_LEGAL = array(
	'ok'      => array( '✓ Còn hiệu lực', '#1f7a5c' ),
	'amended' => array( '⚠ Đã sửa đổi', '#9a6a12' ),
	'dead'    => array( '✗ Hết hiệu lực', '#b42318' ),
	'unknown' => array( '? Chưa có trong kho', '#5b6470' ),
);

function cvc_di_stage_badge( ?string $stage ): string {
	$s = CVC_DI_STAGES[ (string) $stage ] ?? array( (string) $stage, 'bg-slate-100 text-slate-700' );

	return '<span class="inline-block px-2 py-0.5 rounded-md text-xs font-bold ' . esc_attr( $s[1] ) . '">' . esc_html( $s[0] ) . '</span>';
}

/** Khung tải lên + danh sách tài liệu tải lên (gọi từ trang Xưởng). */
function cvc_di_render_upload_and_list(): void {
	$res  = cvc_admin_api_get( '/api/admin/doc-imports' );
	$d    = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$cats = (array) ( $d['categories'] ?? array() );
	?>
	<section class="dg-card">
		<h2 class="font-black text-ink mb-2">Tải tệp sách lên (.docx)</h2>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="grid gap-2" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Đang tải và trích xuất…'">
			<?php wp_nonce_field( 'cvc_admin_docimport' ); ?><input type="hidden" name="action" value="cvc_admin_docimport"><input type="hidden" name="op" value="upload">
			<label class="text-sm">Tệp .docx (≤ 20 MB)<br><input type="file" name="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required></label>
			<label class="text-sm">Tiêu đề (bỏ trống để hệ thống đề xuất)<br><input type="text" name="title" maxlength="200" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:100%"></label>
			<label class="text-sm">Bộ sách<br>
				<select name="category" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:100%"><option value="">Để AI chọn</option>
					<?php foreach ( $cats as $k => $v ) : ?><option value="<?php echo esc_attr( (string) $k ); ?>"><?php echo esc_html( (string) $v ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label class="text-sm">Giá<br>
				<select name="is_free" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm"><option value="1">Miễn phí</option><option value="0">Có phí</option></select>
				<input type="number" name="price" min="0" step="1000" placeholder="Giá (₫)" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:120px">
			</label>
			<label class="text-sm">Nguồn / ghi chú (hiện trên bản đọc)<br><input type="text" name="source_note" maxlength="300" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:100%" placeholder="vd. Công Viên Chức biên soạn"></label>
			<label class="text-sm"><input type="checkbox" name="rights" value="1" required> Tôi xác nhận Công Viên Chức có quyền phát hành tài liệu này (không lấy từ trang khác khi chưa được phép).</label>
			<div><button type="submit" class="dg-btn is-primary">⇪ Tải lên &amp; phân tích</button></div>
		</form>
		<p class="dg-muted mt-2">Hệ thống trích mục lục từ Heading của Word, soát mọi số hiệu văn bản pháp luật trong tệp với kho (còn hiệu lực / đã sửa đổi / hết hiệu lực), AI đề xuất tiêu đề, mô tả, tóm tắt (khoảng 50-500 ₫). Tài liệu chỉ lên thư viện sau khi bạn duyệt và bấm Xuất bản.</p>
	</section>
	<section class="dg-card">
		<h2 class="font-black text-ink mb-2">Tài liệu tải lên</h2>
		<?php $docs = (array) ( $d['documents'] ?? array() ); ?>
		<?php if ( ! $res['ok'] ) : ?><p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
		<?php elseif ( ! $docs ) : ?><p class="dg-muted">Chưa có tài liệu tải lên.</p>
		<?php else : ?>
		<div style="overflow-x:auto"><table class="dg-tbl"><thead><tr><th>Tài liệu</th><th>Trạng thái</th><th>Căn cứ trong tệp</th><th>Số từ</th><th>Cập nhật</th></tr></thead><tbody>
			<?php foreach ( $docs as $x ) : ?>
				<?php $lc = (array) $x['legal_counts']; ?>
				<tr>
					<td><a class="font-bold text-acc" href="<?php echo esc_url( cvc_admin_url( 'tai-lieu-tai-len', (int) $x['id'] ) ); ?>"><?php echo esc_html( (string) $x['title'] ); ?></a><br><span class="dg-muted"><?php echo esc_html( (string) $x['original_name'] ); ?></span></td>
					<td><?php echo cvc_di_stage_badge( $x['stage'] ?? null ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
					<td class="dg-muted"><?php
					$parts = array();
					foreach ( CVC_DI_LEGAL as $k => $l ) {
						if ( ! empty( $lc[ $k ] ) ) {
							$parts[] = '<span style="color:' . esc_attr( $l[1] ) . '">' . esc_html( $l[0] . ': ' . (int) $lc[ $k ] ) . '</span>';
						}
					}
					echo $parts ? implode( '<br>', $parts ) : 'Không có'; // phpcs:ignore WordPress.Security.EscapeOutput
					?></td>
					<td><?php echo esc_html( number_format_i18n( (int) $x['words'] ) ); ?></td>
					<td class="dg-muted"><?php echo esc_html( function_exists( 'cvc_ui_dt' ) ? cvc_ui_dt( $x['updated_at'] ) : (string) $x['updated_at'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody></table></div>
		<?php endif; ?>
	</section>
	<?php
}

function cvc_di_form_open( int $id, string $op ): void {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
	wp_nonce_field( 'cvc_admin_docimport' );
	echo '<input type="hidden" name="action" value="cvc_admin_docimport"><input type="hidden" name="op" value="' . esc_attr( $op ) . '"><input type="hidden" name="id" value="' . (int) $id . '">';
}

/** Điểm duyệt bắt buộc của một tệp tải lên. */
function cvc_admin_render_docimport( string $item_param ): void {
	cvc_dg_styles();
	$id  = absint( $item_param );
	$res = $id ? cvc_admin_api_get( '/api/admin/doc-imports/' . $id ) : array( 'ok' => false );
	echo '<p class="text-sm"><a class="text-acc" href="' . esc_url( cvc_admin_url( 'tai-lieu-ai' ) ) . '">← Xưởng tài liệu</a></p>';
	if ( ! $res['ok'] ) {
		echo '<p class="text-sm text-red-700">' . esc_html( $id ? cvc_admin_error_message( $res ) : 'Thiếu mã tài liệu.' ) . '</p>';
		return;
	}
	$d     = (array) $res['data']['data'];
	$ing   = (array) ( $d['ingest'] ?? array() );
	$ai    = (array) ( $ing['ai'] ?? array() );
	$stage = (string) ( $d['stage'] ?? '' );
	$probs = (array) ( $d['problems'] ?? array() );
	$file_url = wp_nonce_url( admin_url( 'admin-post.php?action=cvc_admin_docimport_file&id=' . $id ), 'cvc_admin_docimport_file' );
	?>
	<header class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-black text-ink"><?php echo esc_html( (string) $d['title'] ); ?></h1>
			<p class="dg-muted">Tệp gốc: <?php echo esc_html( (string) ( $ing['original_name'] ?? '' ) ); ?> · <?php echo esc_html( number_format_i18n( (int) ( $ing['words'] ?? 0 ) ) ); ?> từ<?php echo ! empty( $d['page_count'] ) ? ' · ' . (int) $d['page_count'] . ' trang' : ''; ?></p>
			<p class="mt-1"><?php echo cvc_di_stage_badge( $stage ); // phpcs:ignore ?></p>
		</div>
		<div class="flex flex-wrap gap-2">
			<?php if ( 'blocked' !== $stage ) : ?>
				<?php cvc_di_form_open( $id, 'analyze' ); ?><button class="dg-btn" title="Gọi lại AI đề xuất tiêu đề, mô tả, tóm tắt">✦ Phân tích lại (AI)</button></form>
				<?php cvc_di_form_open( $id, 'build' ); ?><button class="dg-btn is-primary">⎙ Dựng bản phát hành</button></form>
			<?php endif; ?>
			<?php if ( ! empty( $d['file_path'] ) && ! empty( $ing['built_at'] ) ) : ?>
				<a class="dg-btn" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener">👁 Xem tệp phát hành</a>
			<?php endif; ?>
			<?php if ( 'published' !== (string) $d['status'] ) : ?>
				<?php cvc_di_form_open( $id, 'publish' ); ?><button class="dg-btn is-ok"<?php echo $probs ? ' disabled title="' . esc_attr( implode( '; ', $probs ) ) . '"' : ''; ?> onclick="return confirm('Xuất bản tài liệu lên thư viện?')">⇪ Xuất bản</button></form>
			<?php else : ?>
				<a class="dg-btn is-ok" href="<?php echo esc_url( cvc_document_url( (string) $d['slug'] ) ); ?>" target="_blank" rel="noopener">Xem trên thư viện ↗</a>
				<?php cvc_di_form_open( $id, 'unpublish' ); ?><button class="dg-btn is-warn" onclick="return confirm('Gỡ tài liệu khỏi thư viện?')">Gỡ xuất bản</button></form>
			<?php endif; ?>
		</div>
	</header>
	<?php if ( ! empty( $ing['blocked'] ) ) : ?><p class="dg-issue mt-2"><b>Bị chặn:</b> <?php echo esc_html( (string) $ing['blocked'] ); ?></p><?php endif; ?>
	<?php if ( $probs && 'published' !== (string) $d['status'] ) : ?><p class="dg-issue mt-2"><b>Chưa xuất bản được:</b> <?php echo esc_html( implode( '; ', $probs ) ); ?>.</p><?php endif; ?>
	<?php if ( 'analyzing' === $stage ) : ?><p class="dg-muted mt-2">AI đang phân tích (khoảng 30 giây). Tải lại trang để xem gợi ý.</p><?php endif; ?>
	<?php if ( ! empty( $ing['ai_error'] ) ) : ?><p class="dg-issue mt-2">AI chưa phân tích được: <?php echo esc_html( (string) $ing['ai_error'] ); ?>. Có thể điền tay hoặc bấm Phân tích lại.</p><?php endif; ?>
	<?php foreach ( (array) ( $ing['warnings'] ?? array() ) as $w ) : ?><p class="dg-issue mt-2" style="border-left-color:#c9a052;background:#fbf5e8"><?php echo esc_html( (string) $w ); ?></p><?php endforeach; ?>

	<section class="dg-card">
		<div class="flex flex-wrap gap-4" style="align-items:flex-start">
			<div style="flex:0 0 220px">
				<?php if ( ! empty( $d['images']['cover'] ) ) : ?>
					<img src="<?php echo esc_url( (string) $d['images']['cover'] ); ?>" alt="Bìa" style="width:220px;border-radius:6px;box-shadow:0 6px 18px rgba(15,42,74,.25)">
				<?php else : ?><div class="dg-muted" style="width:220px;height:311px;border:1px dashed #cbd5df;border-radius:6px;display:flex;align-items:center;justify-content:center">Bìa đang dựng</div><?php endif; ?>
				<p class="dg-muted mt-1">Bìa tự đổi theo tiêu đề, bộ sách, ngành sau khi lưu.</p>
			</div>
			<div style="flex:1 1 380px;min-width:0">
				<?php cvc_di_form_open( $id, 'save' ); ?>
					<label class="block text-sm font-bold">Tiêu đề<br><input type="text" name="title" maxlength="200" value="<?php echo esc_attr( (string) $d['title'] ); ?>" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:100%"></label>
					<?php if ( ! empty( $ai['title'] ) && $ai['title'] !== $d['title'] ) : ?><p class="dg-muted">AI đề xuất: “<?php echo esc_html( (string) $ai['title'] ); ?>”</p><?php endif; ?>
					<label class="block text-sm font-bold mt-2">Mô tả (140-160 ký tự, hiện trên Google)<br><textarea name="description" maxlength="300" class="dg-ta" style="min-height:70px;font-family:inherit;font-size:14px"><?php echo esc_textarea( (string) $d['description'] ); ?></textarea></label>
					<div class="flex flex-wrap gap-3 mt-2">
						<label class="text-sm font-bold">Bộ sách<br><select name="category" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm"><?php foreach ( (array) $d['categories'] as $k => $v ) : ?><option value="<?php echo esc_attr( (string) $k ); ?>" <?php selected( (string) $d['category'], (string) $k ); ?>><?php echo esc_html( (string) $v ); ?></option><?php endforeach; ?></select></label>
						<label class="text-sm font-bold">Ngành<br><select name="job_family" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm"><?php foreach ( array( '' => '—', 'general' => 'Chung', 'admin' => 'Hành chính', 'education' => 'Giáo dục', 'health' => 'Y tế', 'finance' => 'Tài chính', 'justice' => 'Tư pháp', 'it' => 'CNTT', 'culture' => 'Văn hóa', 'agri_forest' => 'Nông lâm', 'science' => 'Khoa học' ) as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) $d['job_family'], $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
						<label class="text-sm font-bold">Đối tượng<br><select name="audience" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm"><?php foreach ( array( '' => '—', 'civil' => 'Công chức', 'public' => 'Viên chức', 'both' => 'Cả hai' ) as $k => $v ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) $d['audience'], $k ); ?>><?php echo esc_html( $v ); ?></option><?php endforeach; ?></select></label>
						<label class="text-sm font-bold">Giá<br><select name="is_free" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm"><option value="1" <?php selected( ! empty( $d['is_free'] ) ); ?>>Miễn phí</option><option value="0" <?php selected( empty( $d['is_free'] ) ); ?>>Có phí</option></select>
							<input type="number" name="price" min="0" step="1000" value="<?php echo esc_attr( (string) (int) $d['price'] ); ?>" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:110px"> ₫</label>
						<label class="text-sm font-bold">Tệp tải về<br><select name="release" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm"><option value="pdf" <?php selected( 'pdf', $ing['release'] ?? 'pdf' ); ?>>PDF có bìa Công Viên Chức</option><option value="docx" <?php selected( 'docx', $ing['release'] ?? 'pdf' ); ?>>Giữ DOCX gốc (mẫu biểu cần điền)</option></select></label>
					</div>
					<label class="block text-sm mt-2"><input type="checkbox" name="use_summary" value="1" <?php checked( $ing['use_summary'] ?? true ); ?>> Hiện hộp "Tóm tắt nhanh" (AI tạo, bạn đã đọc) đầu bản đọc</label>
					<?php if ( in_array( 'dead', array_column( (array) ( $ing['legal'] ?? array() ), 'state' ), true ) ) : ?>
						<label class="block text-sm mt-1" style="color:#b42318"><input type="checkbox" name="historical_ok" value="1" <?php checked( ! empty( $ing['historical_ok'] ) ); ?>> Tôi xác nhận đây là tài liệu lưu trữ / đối chiếu văn bản cũ (bản đọc vẫn ghi rõ văn bản đã hết hiệu lực)</label>
					<?php endif; ?>
					<button class="dg-btn mt-3">💾 Lưu</button>
				</form>
			</div>
		</div>
	</section>

	<section class="dg-card">
		<h2 class="font-black text-ink mb-2">Soát căn cứ pháp lý trong tệp</h2>
		<?php $legal = (array) ( $ing['legal'] ?? array() ); ?>
		<?php if ( ! $legal ) : ?><p class="dg-muted">Không thấy số hiệu văn bản pháp luật nào trong tệp.</p><?php else : ?>
		<div style="overflow-x:auto"><table class="dg-tbl"><thead><tr><th>Số hiệu</th><th>Tình trạng</th><th>Lần nhắc</th><th>Ghi chú</th></tr></thead><tbody>
			<?php foreach ( $legal as $l ) : ?>
				<?php $st = CVC_DI_LEGAL[ $l['state'] ] ?? array( $l['state'], '#5b6470' ); ?>
				<tr>
					<td><b><?php echo esc_html( (string) $l['number'] ); ?></b><?php if ( ! empty( $l['title'] ) ) : ?><br><span class="dg-muted"><?php echo esc_html( wp_trim_words( (string) $l['title'], 18 ) ); ?></span><?php endif; ?></td>
					<td style="color:<?php echo esc_attr( $st[1] ); ?>;font-weight:bold;white-space:nowrap"><?php echo esc_html( $st[0] ); ?></td>
					<td><?php echo (int) $l['count']; ?></td>
					<td class="dg-muted"><?php echo esc_html( (string) $l['note'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody></table></div>
		<?php endif; ?>
		<?php if ( ! empty( $ai['concerns'] ) ) : ?>
			<p class="font-bold text-sm mt-3">AI lưu ý biên tập viên kiểm tra:</p>
			<?php foreach ( (array) $ai['concerns'] as $c ) : ?><div class="dg-issue"><?php echo esc_html( (string) $c ); ?></div><?php endforeach; ?>
		<?php endif; ?>
	</section>

	<details class="dg-card" open>
		<summary class="font-black text-ink cursor-pointer">Bản đọc trực tuyến (xem trước)</summary>
		<div class="dg-preview mt-2"><?php echo wp_kses_post( (string) $d['content_html'] ); ?></div>
	</details>
	<?php
}

add_action( 'admin_post_cvc_admin_docimport', 'cvc_admin_handle_docimport' );
add_action( 'admin_post_nopriv_cvc_admin_docimport', 'cvc_admin_handle_docimport' );
function cvc_admin_handle_docimport(): void {
	check_admin_referer( 'cvc_admin_docimport' );
	$token = cvc_admin_require_staff();
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$id    = absint( $_POST['id'] ?? 0 );
	$back  = $id ? cvc_admin_url( 'tai-lieu-tai-len', $id ) : cvc_admin_url( 'tai-lieu-ai' );
	if ( ! cvc_admin_can( 'document.create' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền.' );
		return;
	}
	$cli = new CVC_Api_Client( null, 175 );
	if ( 'upload' === $op ) {
		$f = $_FILES['file'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! is_array( $f ) || UPLOAD_ERR_OK !== (int) ( $f['error'] ?? 1 ) || ! is_uploaded_file( (string) $f['tmp_name'] ) ) {
			cvc_admin_redirect( $back, 'error', 'Chưa nhận được tệp (kiểm tra dung lượng ≤ 20 MB).' );
			return;
		}
		$name = sanitize_file_name( (string) $f['name'] );
		$res  = $cli->post_multipart(
			'/api/admin/doc-imports',
			array_filter(
				array(
					'title'       => sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? '' ) ) ),
					'category'    => sanitize_key( (string) ( $_POST['category'] ?? '' ) ),
					'is_free'     => empty( $_POST['is_free'] ) ? '0' : '1',
					'price'       => (string) absint( $_POST['price'] ?? 0 ),
					'source_note' => sanitize_text_field( wp_unslash( (string) ( $_POST['source_note'] ?? '' ) ) ),
					'rights'      => empty( $_POST['rights'] ) ? '' : '1',
				),
				static fn ( $v ) => '' !== $v
			),
			array( 'file' => array( (string) $f['tmp_name'], $name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ) ),
			$token
		);
		$new = (int) ( $res['data']['data']['id'] ?? 0 );
		cvc_admin_redirect( $new ? cvc_admin_url( 'tai-lieu-tai-len', $new ) : $back, $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Đã tải lên.' ) : cvc_admin_error_message( $res ) );
		return;
	}
	$body = array( 'action' => $op );
	if ( 'save' === $op ) {
		$body += array(
			'title'         => sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? '' ) ) ),
			'description'   => sanitize_textarea_field( wp_unslash( (string) ( $_POST['description'] ?? '' ) ) ),
			'category'      => sanitize_key( (string) ( $_POST['category'] ?? '' ) ),
			'job_family'    => sanitize_key( (string) ( $_POST['job_family'] ?? '' ) ),
			'audience'      => sanitize_key( (string) ( $_POST['audience'] ?? '' ) ),
			'is_free'       => empty( $_POST['is_free'] ) ? '0' : '1',
			'price'         => (string) absint( $_POST['price'] ?? 0 ),
			'release'       => 'docx' === ( $_POST['release'] ?? '' ) ? 'docx' : 'pdf',
			'use_summary'   => empty( $_POST['use_summary'] ) ? '0' : '1',
			'historical_ok' => empty( $_POST['historical_ok'] ) ? '0' : '1',
		);
	}
	$res = $cli->post( '/api/admin/doc-imports/' . $id . '/action', $body, $token );
	if ( 'publish' === $op && $res['ok'] && class_exists( 'PCSEO_Scanner' ) && ! PCSEO_Scanner::is_running() ) {
		PCSEO_Scanner::start( 'tai-lieu' ); // đưa tài liệu mới vào sitemap
	}
	cvc_admin_redirect( $back, $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Đã xong.' ) : cvc_admin_error_message( $res ) );
}

/** Xem tệp phát hành (kể cả chưa xuất bản) - proxy kèm token biên tập viên. */
add_action( 'admin_post_cvc_admin_docimport_file', 'cvc_admin_handle_docimport_file' );
add_action( 'admin_post_nopriv_cvc_admin_docimport_file', 'cvc_admin_handle_docimport_file' );
function cvc_admin_handle_docimport_file(): void {
	check_admin_referer( 'cvc_admin_docimport_file' );
	$token = cvc_admin_require_staff();
	if ( ! cvc_admin_can( 'document.view' ) ) {
		wp_die( 'Bạn không có quyền.', 403 );
	}
	$r = wp_remote_get( rtrim( (string) cvc_api_base_url(), '/' ) . '/api/admin/doc-imports/' . absint( $_GET['id'] ?? 0 ) . '/file', array( 'timeout' => 60, 'headers' => array( 'Authorization' => 'Bearer ' . $token ) ) );
	if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
		wp_die( 'Chưa có tệp phát hành hoặc không tải được.', 404 );
	}
	nocache_headers();
	header( 'Content-Type: ' . ( wp_remote_retrieve_header( $r, 'content-type' ) ?: 'application/octet-stream' ) );
	header( 'Content-Disposition: ' . ( wp_remote_retrieve_header( $r, 'content-disposition' ) ?: 'inline' ) );
	echo wp_remote_retrieve_body( $r ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}
