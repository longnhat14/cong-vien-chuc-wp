<?php
/**
 * Quản trị → Thu thập tin → "Tạo tin từ tệp" (20/10/2026).
 * Tải 1–10 tệp (PDF, DOC/DOCX, XLS/XLSX, ảnh chụp, ZIP/RAR) → hệ thống đọc chữ (OCR) + AI phân tích →
 * bản nháp ở hàng chờ: xem trước & sửa, duyệt đăng, hoặc gắn tệp vào tin đã có.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_imports_url( int $id = 0 ): string {
	return cvc_admin_url( 'thu-thap', null, $id ? array( 'xem' => 'nhap-tep', 'id' => $id ) : array( 'xem' => 'nhap-tep' ) );
}

function cvc_imports_badge( string $status, string $label ): string {
	$map = array(
		'ocr'       => 'bg-amber-50 text-amber-800 border-amber-200',
		'analyzing' => 'bg-amber-50 text-amber-800 border-amber-200',
		'review'    => 'bg-sky-50 text-sky-800 border-sky-200',
		'published' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
		'attached'  => 'bg-emerald-50 text-emerald-800 border-emerald-200',
		'failed'    => 'bg-red-50 text-red-800 border-red-200',
	);

	return '<span class="text-[11px] font-bold px-2 py-0.5 rounded-full border ' . esc_attr( $map[ $status ] ?? 'bg-slate-100 text-slate-700 border-slate-300' ) . '">' . esc_html( $label ) . '</span>';
}

function cvc_admin_render_imports_index(): void {
	$list  = cvc_admin_api_get( '/api/admin/recruitment-imports', array( 'limit' => 30 ) );
	$rows  = $list['ok'] ? (array) ( $list['data']['data'] ?? array() ) : array();
	$input = cvc_admin_input_class();
	$can   = cvc_admin_can( 'recruitment.create' );
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Tạo tin từ tệp</h1>
		<p class="text-sm text-ink-muted">Tải công văn, phụ lục chỉ tiêu, ảnh chụp thông báo… Hệ thống đọc chữ (cả bản scan/ảnh), AI tách thông tin và vị trí, rồi đưa bản nháp vào hàng chờ để bạn xem trước, sửa và duyệt. Không tự đăng.</p>
	</header>
	<?php cvc_ingestion_tabs( 'nhap-tep' ); ?>

	<?php if ( $can ) : ?>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dz-card space-y-4" data-cvc-import-form>
			<?php wp_nonce_field( 'cvc_admin_import' ); ?>
			<input type="hidden" name="action" value="cvc_admin_import">
			<input type="hidden" name="op" value="upload">
			<div>
				<label class="block text-sm font-bold text-ink mb-1" for="cvc-imp-files">Tệp (tối đa 10, mỗi tệp ≤ 25MB)</label>
				<input id="cvc-imp-files" type="file" name="files[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.heic,.heif,.zip,.rar" class="block w-full text-sm">
				<p class="text-xs text-ink-muted mt-1">PDF, DOC, DOCX, XLS, XLSX, ảnh JPG/PNG/WEBP/HEIC, ZIP/RAR. Nên tải cả thông báo và các phụ lục (biểu chỉ tiêu, tiêu chuẩn) trong cùng một lần.</p>
			</div>
			<div class="grid md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-bold text-ink mb-1" for="cvc-imp-url">Link nguồn chính thức <span class="font-normal text-ink-muted">(khuyến nghị)</span></label>
					<input id="cvc-imp-url" type="url" name="source_url" class="<?php echo esc_attr( $input ); ?>" placeholder="https://…gov.vn/…">
					<p class="text-xs text-ink-muted mt-1">Bỏ trống hoặc link không thuộc tên miền nhà nước → tin mang nhãn <b>Nguồn sưu tầm</b>, trang công khai không hiện link ngoài.</p>
				</div>
				<div>
					<label class="block text-sm font-bold text-ink mb-1" for="cvc-imp-prov">Tỉnh/thành <span class="font-normal text-ink-muted">(tùy chọn)</span></label>
					<select id="cvc-imp-prov" name="province_id" class="<?php echo esc_attr( $input ); ?>">
						<option value="">— AI tự nhận —</option>
						<?php foreach ( cvc_admin_provinces() as $pid => $pname ) : ?>
							<option value="<?php echo (int) $pid; ?>"><?php echo esc_html( (string) $pname ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="md:col-span-2">
					<label class="block text-sm font-bold text-ink mb-1" for="cvc-imp-title">Tiêu đề <span class="font-normal text-ink-muted">(tùy chọn, để trống AI tự đặt)</span></label>
					<input id="cvc-imp-title" type="text" name="title" maxlength="500" class="<?php echo esc_attr( $input ); ?>">
				</div>
			</div>
			<fieldset class="flex flex-wrap items-center gap-3 text-sm">
				<legend class="text-sm font-bold text-ink mb-1">Tự tạo đề trắc nghiệm cho tin sau khi đăng</legend>
				<?php foreach ( array( 'inherit' => 'Theo mặc định', 'on' => 'Luôn bật', 'off' => 'Tắt' ) as $k => $lbl ) : ?>
					<label class="flex items-center gap-2"><input type="radio" name="personal_exams" value="<?php echo esc_attr( $k ); ?>" class="accent-acc-2" <?php checked( 'inherit', $k ); ?>> <?php echo esc_html( $lbl ); ?></label>
				<?php endforeach; ?>
			</fieldset>
			<label class="flex items-start gap-2 text-sm text-ink-2">
				<input type="checkbox" name="allow_duplicate" value="1" class="mt-1 accent-acc-2">
				<span>Vẫn tải lên dù tệp đã có trên hệ thống <span class="text-ink-muted">(mặc định: hệ thống báo ngay nếu tệp trùng với tin đã đăng, hàng chờ hoặc lần nhập trước, không tốn phí đọc chữ/AI)</span></span>
			</label>
			<button type="submit" class="px-5 py-2.5 bg-acc hover:bg-acc-deep text-white font-black text-sm rounded-xl" data-cvc-import-submit>Tải lên &amp; phân tích</button>
		</form>
		<script>
		(function () {
			var f = document.querySelector('[data-cvc-import-form]');
			if (!f) { return; }
			f.addEventListener('submit', function (e) {
				var inp = f.querySelector('input[type=file]'), files = inp ? inp.files : [];
				if (files.length > 10) { e.preventDefault(); alert('Tối đa 10 tệp mỗi lần.'); return; }
				for (var i = 0; i < files.length; i++) { if (files[i].size > 25 * 1024 * 1024) { e.preventDefault(); alert('Tệp "' + files[i].name + '" lớn hơn 25MB.'); return; } }
				var b = f.querySelector('[data-cvc-import-submit]'); if (b) { b.disabled = true; b.textContent = 'Đang tải lên…'; }
			});
		})();
		</script>
	<?php endif; ?>

	<section class="dz-card space-y-2">
		<h2 class="text-base font-black text-ink">Các lần nhập gần đây</h2>
		<?php if ( empty( $rows ) ) : ?>
			<p class="text-sm text-ink-muted">Chưa có lần nhập nào.</p>
		<?php else : ?>
			<ul class="divide-y divide-slate-200 text-sm">
				<?php foreach ( $rows as $r ) : ?>
					<li class="py-2 flex flex-wrap items-center justify-between gap-2">
						<a class="font-semibold text-ink hover:text-acc-2 min-w-0" href="<?php echo esc_url( cvc_imports_url( (int) $r['id'] ) ); ?>">#<?php echo (int) $r['id']; ?> · <?php echo esc_html( (string) ( $r['title'] ?: implode( ', ', (array) $r['file_names'] ) ) ); ?><?php echo (int) $r['files'] > 3 ? ' …' : ''; ?></a>
						<span class="flex items-center gap-2 text-xs text-ink-muted">
							<?php if ( ! empty( $r['curated'] ) ) : ?><span class="px-1.5 py-0.5 rounded bg-acc-soft text-acc font-bold">Nguồn sưu tầm</span><?php endif; ?>
							<?php echo esc_html( cvc_ingestion_date( $r['created_at'] ?? '' ) ); ?>
							<?php echo cvc_imports_badge( (string) $r['status'], (string) $r['status_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
}

function cvc_admin_render_import_detail( int $id ): void {
	$res = cvc_admin_api_get( '/api/admin/recruitment-imports/' . $id );
	echo '<p><a class="text-sm text-acc font-bold" href="' . esc_url( cvc_imports_url() ) . '">&larr; Tạo tin từ tệp</a></p>';
	if ( ! $res['ok'] ) {
		cvc_render_error_state( 404 === (int) $res['status'] ? 'Không tìm thấy lần nhập này.' : cvc_admin_error_message( $res ) );
		return;
	}
	$d       = (array) ( $res['data']['data'] ?? array() );
	$draft   = is_array( $d['draft'] ?? null ) ? $d['draft'] : null;
	$busy    = in_array( $d['status'], array( 'ocr', 'analyzing' ), true );
	$can_upd = cvc_admin_can( 'recruitment.update' );
	$btn     = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$op_form = static function ( string $op, string $label, string $class, array $extra = array(), string $confirm = '' ) use ( $id ): string {
		$h = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="inline"' . ( '' !== $confirm ? ' data-cvc-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>'
			. wp_nonce_field( 'cvc_admin_import', '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="cvc_admin_import"><input type="hidden" name="op" value="' . esc_attr( $op ) . '"><input type="hidden" name="id" value="' . (int) $id . '">';
		foreach ( $extra as $k => $v ) {
			$h .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '">';
		}

		return $h . '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
	};
	if ( $busy ) {
		echo '<meta http-equiv="refresh" content="20">';
	}
	?>
	<header class="space-y-2">
		<div class="flex flex-wrap items-center gap-2">
			<?php echo cvc_imports_badge( (string) $d['status'], (string) $d['status_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( ! empty( $d['curated'] ) ) : ?><span class="text-[11px] px-1.5 py-0.5 rounded bg-acc-soft text-acc font-bold">Nguồn sưu tầm</span><?php endif; ?>
			<span class="text-xs text-ink-muted">Lần nhập #<?php echo (int) $d['id']; ?> · <?php echo esc_html( cvc_ingestion_date( $d['created_at'] ?? '' ) . ( ! empty( $d['creator'] ) ? ' · ' . $d['creator'] : '' ) ); ?></span>
		</div>
		<h1 class="text-xl font-black text-ink"><?php echo esc_html( (string) ( $draft['title'] ?? $d['title'] ?? 'Đang xử lý tệp tải lên' ) ); ?></h1>
		<?php if ( ! empty( $d['progress']['message'] ) || ! empty( $d['error'] ) ) : ?>
			<p class="text-sm <?php echo ! empty( $d['error'] ) ? 'text-red-700' : 'text-ink-2'; ?>">
				<?php echo esc_html( (string) ( $d['error'] ?: $d['progress']['message'] ) ); ?>
				<?php if ( ! empty( $d['progress']['ocr_pages'] ) ) : ?> · đã đọc <?php echo (int) $d['progress']['ocr_pages']; ?> trang<?php endif; ?>
				<?php if ( $busy ) : ?> · trang tự tải lại sau 20 giây<?php endif; ?>
			</p>
		<?php endif; ?>
		<div class="flex flex-wrap gap-2">
			<?php if ( $busy && cvc_admin_can( 'recruitment.create' ) ) : ?>
				<?php echo $op_form( 'process', 'Chạy ngay 1 lượt', $btn . ' bg-white border border-slate-300 text-ink hover:bg-acc-soft' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
			<?php if ( 'failed' === $d['status'] && cvc_admin_can( 'recruitment.create' ) ) : ?>
				<?php echo $op_form( 'retry', 'Chạy lại', $btn . ' bg-white border border-slate-300 text-ink hover:bg-acc-soft' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
			<?php if ( ! empty( $d['ingestion_item_id'] ) && in_array( $d['status'], array( 'review', 'published', 'attached' ), true ) ) : ?>
				<a class="<?php echo esc_attr( $btn ); ?> bg-acc text-white hover:bg-acc-deep" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', (int) $d['ingestion_item_id'] ) ); ?>"><?php echo 'review' === $d['status'] ? 'Xem trước, sửa &amp; duyệt đăng →' : 'Mở bản ghi thu thập'; ?></a>
			<?php endif; ?>
			<?php if ( ! empty( $d['recruitment_id'] ) ) : ?>
				<a class="<?php echo esc_attr( $btn ); ?> bg-white border border-slate-300 text-ink hover:bg-acc-soft" href="<?php echo esc_url( cvc_admin_url( 'recruitments', (int) $d['recruitment_id'] ) ); ?>">Tin #<?php echo (int) $d['recruitment_id']; ?></a>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( $draft ) : ?>
		<section class="dz-card space-y-3 text-sm">
			<h2 class="text-base font-black text-ink">Bản nháp AI tách được</h2>
			<dl class="grid sm:grid-cols-2 gap-x-6 gap-y-1">
				<?php
				$rows = array(
					'Cơ quan'      => $draft['agency_name'] ?? null,
					'Tỉnh/thành'   => $draft['province_name'] ?? null,
					'Loại tin'     => array( 'public_employee' => 'Viên chức', 'civil_servant' => 'Công chức', 'other' => 'Khác' )[ (string) ( $draft['recruitment_type'] ?? '' ) ] ?? ( $draft['recruitment_type'] ?? null ),
					'Nhận hồ sơ'   => trim( ( $draft['application_start_date'] ?? '' ) . ' → ' . ( $draft['application_deadline'] ?? '' ), ' →' ),
					'Tổng chỉ tiêu' => isset( $draft['total_positions'] ) ? (string) $draft['total_positions'] : null,
					'Độ tin cậy AI' => isset( $draft['confidence'] ) ? round( (float) $draft['confidence'] * 100 ) . '%' : null,
				);
				foreach ( $rows as $k => $v ) :
					?>
					<div class="flex gap-2 border-b border-slate-200 py-1"><dt class="text-ink-muted w-32 shrink-0"><?php echo esc_html( $k ); ?></dt><dd class="text-ink font-semibold"><?php echo esc_html( '' !== (string) $v && null !== $v ? (string) $v : '—' ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
			<?php if ( ! empty( $draft['positions'] ) ) : ?>
				<details>
					<summary class="cursor-pointer font-bold text-ink-2"><?php echo esc_html( count( (array) $draft['positions'] ) . ' vị trí · cộng ' . (int) $draft['positions_total'] . ' chỉ tiêu' ); ?>
						<?php if ( ! empty( $draft['total_positions'] ) && (int) $draft['total_positions'] !== (int) $draft['positions_total'] ) : ?><span class="text-amber-800"> — lệch tổng chỉ tiêu thông báo (<?php echo (int) $draft['total_positions']; ?>)</span><?php endif; ?></summary>
					<ul class="mt-2 grid sm:grid-cols-2 gap-x-6 text-xs text-ink-2">
						<?php foreach ( (array) $draft['positions'] as $p ) : ?><li><?php echo esc_html( $p['name'] . ' — ' . (int) $p['quantity'] ); ?></li><?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>
			<?php
			$issues = array_merge( (array) ( $draft['validation_errors'] ?? array() ), (array) ( $draft['plan_errors'] ?? array() ) );
			if ( 'qa_pending' !== ( $draft['state'] ?? '' ) && 'published' !== ( $draft['state'] ?? '' ) ) {
				$issues[] = 'Trạng thái bản ghi: ' . (string) $draft['state'] . ( 'duplicate' === $draft['state'] && ! empty( $draft['duplicate_of'] ) ? ' (trùng tin #' . (int) $draft['duplicate_of']['id'] . ')' : '' );
			}
			?>
			<?php if ( $issues ) : ?>
				<ul class="text-xs text-amber-800 list-disc pl-5"><?php foreach ( $issues as $m ) : ?><li><?php echo esc_html( (string) $m ); ?></li><?php endforeach; ?></ul>
			<?php elseif ( 'review' === $d['status'] ) : ?>
				<p class="text-xs text-emerald-800">Đủ thông tin để đăng. Kiểm tra lại ở trang "Xem trước, sửa &amp; duyệt đăng".</p>
			<?php endif; ?>
		</section>

		<?php if ( 'review' === $d['status'] && $can_upd ) : ?>
			<section class="dz-card space-y-3 text-sm">
				<h2 class="text-base font-black text-ink">Đã có tin này rồi?</h2>
				<p class="text-xs text-ink-muted">Gắn các tệp vừa tải vào tin đã đăng (bổ sung phụ lục, ảnh thông báo) thay vì tạo tin mới.</p>
				<?php foreach ( (array) ( $d['suggestions'] ?? array() ) as $s ) : ?>
					<div class="flex flex-wrap items-center justify-between gap-2 border border-slate-300 rounded-xl p-2">
						<span class="min-w-0"><a class="font-semibold text-ink hover:text-acc-2" href="<?php echo esc_url( cvc_recruitment_url( (string) $s['slug'] ) ); ?>" target="_blank" rel="noopener">#<?php echo (int) $s['id']; ?> · <?php echo esc_html( (string) $s['title'] ); ?></a> <span class="text-xs text-ink-muted">khớp <?php echo (int) $s['score']; ?>%</span></span>
						<?php echo $op_form( 'attach', 'Gắn tệp vào tin này', $btn . ' bg-white border border-slate-300 text-ink hover:bg-acc-soft', array( 'recruitment_id' => (int) $s['id'] ), 'Gắn các tệp vào tin #' . (int) $s['id'] . ' và bỏ bản nháp?' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endforeach; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-center gap-2" data-cvc-confirm="Gắn các tệp vào tin này và bỏ bản nháp?">
					<?php wp_nonce_field( 'cvc_admin_import' ); ?>
					<input type="hidden" name="action" value="cvc_admin_import"><input type="hidden" name="op" value="attach"><input type="hidden" name="id" value="<?php echo (int) $id; ?>">
					<label class="text-xs text-ink-muted" for="cvc-imp-rid">Hoặc nhập mã tin</label>
					<input id="cvc-imp-rid" type="number" name="recruitment_id" min="1" required class="w-28 px-2 py-1 rounded-lg border border-slate-300 text-sm">
					<button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-white border border-slate-300 text-ink hover:bg-acc-soft">Gắn tệp</button>
				</form>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<section class="dz-card space-y-3 text-sm">
		<h2 class="text-base font-black text-ink">Tệp đã tải (<?php echo count( (array) $d['files'] ); ?>)</h2>
		<ul class="divide-y divide-slate-200">
			<?php foreach ( (array) $d['files'] as $f ) : ?>
				<li class="py-1.5 flex flex-wrap items-center justify-between gap-2 <?php echo ! empty( $f['child'] ) ? 'pl-4' : ''; ?>">
					<span class="text-ink"><?php echo esc_html( ( ! empty( $f['child'] ) ? '↳ ' : '' ) . (string) $f['name'] ); ?></span>
					<span class="text-xs <?php echo (int) $f['chars'] < 50 && ! in_array( $f['ext'], array( 'zip', 'rar' ), true ) ? 'text-amber-800' : 'text-ink-muted'; ?>"><?php echo esc_html( strtoupper( (string) $f['ext'] ) . ' · ' . size_format( (int) $f['size'] ) . ' · ' . ( (int) $f['chars'] > 0 ? number_format_i18n( (int) $f['chars'] ) . ' ký tự' : ( in_array( $f['ext'], array( 'zip', 'rar' ), true ) ? 'tệp nén' : 'chưa có chữ' ) ) . ( ! empty( $f['legal_document_id'] ) ? ' · văn bản đã có trong kho' : '' ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>

	<?php if ( $can_upd && ! in_array( $d['status'], array( 'attached', 'failed' ), true ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dz-card flex flex-wrap items-center gap-3 text-sm">
			<?php wp_nonce_field( 'cvc_admin_import' ); ?>
			<input type="hidden" name="action" value="cvc_admin_import"><input type="hidden" name="op" value="mode"><input type="hidden" name="id" value="<?php echo (int) $id; ?>">
			<span class="font-bold text-ink">Tự tạo đề trắc nghiệm:</span>
			<?php foreach ( array( 'inherit' => 'Theo mặc định', 'on' => 'Luôn bật', 'off' => 'Tắt' ) as $k => $lbl ) : ?>
				<label class="flex items-center gap-2"><input type="radio" name="personal_exams" value="<?php echo esc_attr( $k ); ?>" class="accent-acc-2" <?php checked( (string) ( $d['personal_exams'] ?? 'inherit' ), $k ); ?>> <?php echo esc_html( $lbl ); ?></label>
			<?php endforeach; ?>
			<button type="submit" class="<?php echo esc_attr( $btn ); ?> bg-white border border-slate-300 text-ink hover:bg-acc-soft">Lưu</button>
		</form>
	<?php endif; ?>
	<?php
}

add_action( 'admin_post_cvc_admin_import', 'cvc_admin_handle_import' );
add_action( 'admin_post_nopriv_cvc_admin_import', 'cvc_admin_handle_import' );

function cvc_admin_handle_import(): void {
	check_admin_referer( 'cvc_admin_import' );
	$token = cvc_admin_require_staff();
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$id    = absint( $_POST['id'] ?? 0 );
	$back  = cvc_imports_url( $id );
	$api   = new CVC_Api_Client( null, 170 );

	if ( 'upload' === $op ) {
		if ( ! cvc_admin_can( 'recruitment.create' ) ) {
			cvc_admin_redirect( $back, 'error', 'Bạn không có quyền tạo tin.' );
			return;
		}
		$raw   = $_FILES['files'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$files = array();
		if ( is_array( $raw ) && is_array( $raw['name'] ?? null ) ) {
			foreach ( $raw['name'] as $i => $n ) {
				if ( UPLOAD_ERR_OK !== (int) ( $raw['error'][ $i ] ?? 1 ) || ! is_uploaded_file( (string) $raw['tmp_name'][ $i ] ) ) {
					continue;
				}
				$name = sanitize_file_name( (string) $n );
				$files[ 'files[' . count( $files ) . ']' ] = array( (string) $raw['tmp_name'][ $i ], $name, (string) ( wp_check_filetype( $name )['type'] ?: 'application/octet-stream' ) );
			}
		}
		if ( empty( $files ) ) {
			cvc_admin_redirect( $back, 'error', 'Chưa nhận được tệp nào (kiểm tra dung lượng tệp ≤ 25MB).' );
			return;
		}
		$result = $api->post_multipart(
			'/api/admin/recruitment-imports',
			array_filter(
				array(
					'source_url'     => esc_url_raw( wp_unslash( (string) ( $_POST['source_url'] ?? '' ) ) ),
					'province_id'    => absint( $_POST['province_id'] ?? 0 ) ?: null,
					'title'          => sanitize_text_field( wp_unslash( (string) ( $_POST['title'] ?? '' ) ) ),
					'personal_exams' => sanitize_key( (string) ( $_POST['personal_exams'] ?? 'inherit' ) ),
					'allow_duplicate' => empty( $_POST['allow_duplicate'] ) ? null : '1',
				),
				static fn ( $v ) => null !== $v && '' !== $v
			),
			$files,
			$token
		);
		if ( ! $result['ok'] ) {
			$msg = cvc_admin_error_message( $result );
			// 21/10/2026: tep trung voi tep da co -> chi ro tin / lan nhap, goi y "Van tai len".
			if ( 409 === (int) ( $result['status'] ?? 0 ) && 'duplicate_file' === ( $result['data']['code'] ?? '' ) ) {
				$msg = (string) ( $result['data']['message'] ?? 'Tệp đã có trên hệ thống.' ) . ' Không tải lên lại. Nếu vẫn muốn tạo lần nhập mới, tích "Vẫn tải lên dù tệp đã có" rồi gửi lại.';
			}
			cvc_admin_redirect( $back, 'error', $msg );
			return;
		}
		$new = (int) ( $result['data']['data']['id'] ?? 0 );
		cvc_admin_redirect( cvc_imports_url( $new ), 'success', (string) ( $result['data']['message'] ?? 'Đã nhận tệp.' ) );
		return;
	}

	if ( ! $id ) {
		cvc_admin_redirect( $back, 'error', 'Thiếu mã lần nhập.' );
		return;
	}
	switch ( $op ) {
		case 'process':
			$result = $api->post( '/api/admin/recruitment-imports/' . $id . '/process', array(), $token );
			$msg    = 'Đã chạy 1 lượt xử lý.';
			break;
		case 'retry':
			$result = $api->post( '/api/admin/recruitment-imports/' . $id . '/retry', array(), $token );
			$msg    = 'Đã xếp lần nhập chạy lại.';
			break;
		case 'attach':
			$result = $api->post( '/api/admin/recruitment-imports/' . $id . '/attach', array( 'recruitment_id' => absint( $_POST['recruitment_id'] ?? 0 ) ), $token );
			$msg    = (string) ( $result['data']['message'] ?? 'Đã gắn tệp.' );
			break;
		case 'mode':
			$result = $api->put( '/api/admin/recruitment-imports/' . $id, array( 'personal_exams' => sanitize_key( (string) ( $_POST['personal_exams'] ?? 'inherit' ) ) ), $token );
			$msg    = 'Đã lưu lựa chọn tự tạo đề.';
			break;
		default:
			cvc_admin_redirect( $back, 'error', 'Thao tác không hợp lệ.' );
			return;
	}
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}
	cvc_admin_redirect( $back, 'success', $msg );
}
