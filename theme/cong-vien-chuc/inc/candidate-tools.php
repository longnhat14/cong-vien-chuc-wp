<?php
/**
 * GD5 - Cong cu thi sinh tren trang tin tuyen dung:
 *  - doi chieu nganh + trinh do voi tung vi tri (API /recruitments/{slug}/eligibility, qua admin-ajax),
 *  - tinh diem uu tien (NĐ 170/2025 + NĐ 300/2026 cho cong chuc; NĐ 259/2026 cho vien chuc),
 *  - them han nop vao lich (Google Calendar / .ics), huong dan dien Phieu dang ky du tuyen + mau .docx.
 *
 * @package CongVienChuc
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_cvc_eligibility', 'cvc_handle_eligibility_ajax' );
add_action( 'wp_ajax_nopriv_cvc_eligibility', 'cvc_handle_eligibility_ajax' );

function cvc_handle_eligibility_ajax(): void {
	check_ajax_referer( 'cvc_candidate', 'nonce' );
	$slug   = sanitize_title( wp_unslash( (string) ( $_POST['slug'] ?? '' ) ) );
	$major  = sanitize_text_field( wp_unslash( (string) ( $_POST['major'] ?? '' ) ) );
	$degree = sanitize_key( (string) ( $_POST['degree'] ?? '' ) );
	if ( '' === $slug || mb_strlen( $major ) < 2 ) {
		wp_send_json_error( array( 'message' => 'Vui lòng nhập ngành học (ít nhất 2 ký tự).' ), 422 );
	}
	$res = ( new CVC_Api_Client( null, 10 ) )->post( '/api/recruitments/' . rawurlencode( $slug ) . '/eligibility', array( 'major' => mb_substr( $major, 0, 150 ), 'degree' => $degree ?: null ) );
	if ( empty( $res['ok'] ) ) {
		wp_send_json_error( array( 'message' => 'Chưa đối chiếu được, vui lòng thử lại sau.' ), $res['status'] ?: 500 );
	}
	wp_send_json_success( $res['data']['data'] ?? array() );
}

/**
 * Bang diem uu tien (lay tu API, cache 1 ngay).
 */
function cvc_priority_points(): array {
	$cached = get_transient( 'cvc_priority_points_v1' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$res  = ( new CVC_Api_Client() )->get( '/api/candidate/priority-points' );
	$data = is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : array();
	if ( ! empty( $data ) ) {
		set_transient( 'cvc_priority_points_v1', $data, DAY_IN_SECONDS );
	}
	return $data;
}

/**
 * The doi chieu nganh/trinh do (cot phai).
 */
function cvc_render_eligibility_widget( string $slug ): void {
	$degrees = array(
		'trung_cap' => 'Trung cấp',
		'cao_dang'  => 'Cao đẳng',
		'dai_hoc'   => 'Đại học (cử nhân, kỹ sư, bác sĩ...)',
		'thac_si'   => 'Thạc sĩ',
		'tien_si'   => 'Tiến sĩ',
	);
	?>
	<div class="bg-navy-950 p-6 rounded-3xl border border-emerald-500/30 space-y-3 shadow-xl text-xs" id="cvc-elig" data-slug="<?php echo esc_attr( $slug ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'cvc_candidate' ) ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
		<h2 class="font-extrabold uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-3">Bạn hợp vị trí nào?</h2>
		<p class="text-slate-300">Nhập ngành ghi trên bằng tốt nghiệp và trình độ để đối chiếu với yêu cầu của từng vị trí.</p>
		<label for="cvc-elig-major" class="block font-bold text-slate-300">Ngành học</label>
		<input type="text" id="cvc-elig-major" maxlength="150" placeholder="VD: Luật, Kế toán, Công nghệ thông tin" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-400">
		<label for="cvc-elig-degree" class="block font-bold text-slate-300">Trình độ</label>
		<select id="cvc-elig-degree" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-400">
			<?php foreach ( $degrees as $k => $label ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( 'dai_hoc', $k ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="button" id="cvc-elig-btn" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-navy-950 font-black rounded-xl">Đối chiếu</button>
		<div id="cvc-elig-result" class="space-y-2" aria-live="polite" hidden></div>
		<p class="text-[10px] text-slate-500">Đối chiếu tự động theo tên ngành và trình độ - không thay thế điều kiện chính thức trong thông báo tuyển dụng.</p>
	</div>
	<script>
	(function () {
		var box = document.getElementById('cvc-elig');
		if (!box) return;
		var out = document.getElementById('cvc-elig-result');
		var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
		var tone = { match: 'text-emerald-300 border-emerald-400/40', maybe: 'text-amber-300 border-amber-400/40', no: 'text-slate-400 border-slate-700' };
		try { var saved = JSON.parse(localStorage.getItem('cvc-elig') || 'null'); if (saved) { document.getElementById('cvc-elig-major').value = saved.major || ''; document.getElementById('cvc-elig-degree').value = saved.degree || 'dai_hoc'; } } catch (e) {}
		document.getElementById('cvc-elig-btn').addEventListener('click', function () {
			var major = document.getElementById('cvc-elig-major').value.trim();
			var degree = document.getElementById('cvc-elig-degree').value;
			out.hidden = false;
			if (major.length < 2) { out.innerHTML = '<p class="text-amber-300">Vui lòng nhập ngành học.</p>'; return; }
			try { localStorage.setItem('cvc-elig', JSON.stringify({ major: major, degree: degree })); } catch (e) {}
			out.innerHTML = '<p class="text-slate-400">Đang đối chiếu...</p>';
			var fd = new FormData();
			fd.append('action', 'cvc_eligibility'); fd.append('nonce', box.dataset.nonce); fd.append('slug', box.dataset.slug); fd.append('major', major); fd.append('degree', degree);
			fetch(box.dataset.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
				if (!j || !j.success) { out.innerHTML = '<p class="text-rose-300">' + esc((j && j.data && j.data.message) || 'Chưa đối chiếu được.') + '</p>'; return; }
				var s = j.data.summary || {}, list = (j.data.positions || []).filter(function (p) { return p.verdict !== 'no'; });
				var html = '<p class="text-slate-200"><strong class="text-emerald-300">' + (s.match || 0) + '</strong> vị trí phù hợp · <strong class="text-amber-300">' + (s.maybe || 0) + '</strong> cần xem kỹ · ' + (s.no || 0) + ' chưa phù hợp</p>';
				html += list.slice(0, 15).map(function (p) {
					return '<div class="p-2.5 rounded-xl bg-slate-900 border ' + (tone[p.verdict] || '') + '"><p class="font-bold text-slate-100 leading-snug">' + esc(p.name) + (p.quantity ? ' <span class="text-slate-400 font-normal">· ' + esc(p.quantity) + ' chỉ tiêu</span>' : '') + '</p><p class="mt-0.5 ' + (p.verdict === 'match' ? 'text-emerald-300' : 'text-amber-300') + ' font-bold">' + esc(p.verdict_label) + '</p><p class="text-slate-400">' + esc((p.reasons || []).join(' ')) + '</p></div>';
				}).join('');
				if (!list.length) html += '<p class="text-slate-400">Không vị trí nào ghi ngành khớp. Thử cách viết khác (VD "Kế toán" thay vì "KT") hoặc đọc kỹ thông báo gốc.</p>';
				out.innerHTML = html;
			}).catch(function () { out.innerHTML = '<p class="text-rose-300">Lỗi kết nối, vui lòng thử lại.</p>'; });
		});
	})();
	</script>
	<?php
}

/**
 * Khu "Chuan bi ho so": nhac han, diem uu tien, huong dan dien phieu + mau .docx.
 *
 * @param array<string, mixed> $recruitment
 */
function cvc_render_candidate_kit( array $recruitment ): void {
	$is_vc    = 'public_employee' === (string) ( $recruitment['recruitment_type'] ?? '' );
	$type     = $is_vc ? 'vien_chuc' : 'cong_chuc';
	$dates    = (array) ( $recruitment['dates'] ?? array() );
	$deadline = (string) ( $dates['application_deadline'] ?? '' );
	$is_open  = ! empty( $recruitment['is_open'] );
	$slug     = (string) ( $recruitment['slug'] ?? '' );
	$title    = (string) ( $recruitment['title'] ?? '' );
	$points   = cvc_priority_points();
	$ptype    = (array) ( $points['types'][ $type ] ?? array() );
	$form     = $is_vc
		? array( 'file' => 'Phieu-dang-ky-du-tuyen-vien-chuc-Mau-01-ND259-2026.docx', 'basis' => 'Mẫu số 01 ban hành kèm theo Nghị định 259/2026/NĐ-CP (viên chức)' )
		: array( 'file' => 'Phieu-dang-ky-du-tuyen-cong-chuc-Mau-01-ND170-2025.docx', 'basis' => 'Mẫu số 01 ban hành kèm theo Nghị định 170/2025/NĐ-CP (công chức)' );
	$gcal = '';
	if ( '' !== $deadline ) {
		$d    = gmdate( 'Ymd', strtotime( $deadline ) );
		$d2   = gmdate( 'Ymd', strtotime( $deadline . ' +1 day' ) );
		$gcal = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . rawurlencode( 'Hạn nộp hồ sơ: ' . wp_trim_words( $title, 14, '…' ) ) . '&dates=' . $d . '/' . $d2 . '&details=' . rawurlencode( 'Chi tiết: ' . cvc_recruitment_url( $slug ) . ' - đối chiếu thời hạn chính thức trong thông báo.' );
	}
	$ics = rtrim( cvc_api_base_url(), '/' ) . '/api/recruitments/' . rawurlencode( $slug ) . '/calendar.ics';
	?>
	<section id="chuan-bi-ho-so" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6 shadow-xl text-sm text-slate-300">
		<h2 class="text-lg font-extrabold text-amber-400 border-b border-slate-800 pb-3">Chuẩn bị hồ sơ dự tuyển</h2>

		<?php if ( '' !== $deadline ) : ?>
			<div class="space-y-2">
				<h3 class="text-sm font-bold text-white">Nhắc hạn nộp hồ sơ <span class="font-normal text-slate-400">(hạn <?php echo esc_html( cvc_format_date_vn( $deadline ) ); ?><?php echo $is_open ? '' : ' - đã qua'; ?>)</span></h3>
				<?php if ( $is_open ) : ?>
					<div class="flex flex-wrap gap-2 text-xs">
						<a href="<?php echo esc_url( $gcal ); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-xl text-slate-100 font-bold"><i class="fa-regular fa-calendar-plus" aria-hidden="true"></i> Thêm vào Google Calendar</a>
						<a href="<?php echo esc_url( $ics ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 border border-slate-700 rounded-xl text-slate-100 font-bold"><i class="fa-solid fa-bell" aria-hidden="true"></i> Tải lịch (.ics) - nhắc trước 3 ngày và 1 ngày</a>
					</div>
				<?php else : ?>
					<p class="text-xs text-slate-400">Đợt này đã hết hạn nhận phiếu. Theo dõi mục "Văn bản của đợt tuyển" để biết lịch thi và kết quả.</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $ptype['groups'] ) ) : ?>
			<div class="space-y-3" id="cvc-priority">
				<h3 class="text-sm font-bold text-white">Tính điểm ưu tiên <span class="font-normal text-slate-400">(<?php echo esc_html( (string) ( $ptype['label'] ?? '' ) ); ?>)</span></h3>
				<p class="text-xs text-slate-400">Đánh dấu các diện bạn thuộc - điểm được cộng vào <?php echo esc_html( (string) ( $ptype['applies_to'] ?? '' ) ); ?>. Căn cứ: <?php echo esc_html( (string) ( $ptype['basis'] ?? '' ) ); ?>.</p>
				<div class="space-y-3">
					<?php foreach ( (array) $ptype['groups'] as $gi => $g ) : ?>
						<fieldset class="space-y-1.5">
							<legend class="text-xs font-black text-amber-300 mb-1">+<?php echo esc_html( str_replace( '.', ',', (string) $g['points'] ) ); ?> điểm</legend>
							<?php foreach ( (array) $g['items'] as $ii => $item ) : ?>
								<?php $id = 'cvc-pr-' . $gi . '-' . $ii; ?>
								<label for="<?php echo esc_attr( $id ); ?>" class="flex items-start gap-2 text-xs text-slate-300 cursor-pointer">
									<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" data-points="<?php echo esc_attr( (string) $g['points'] ); ?>" class="mt-0.5 accent-amber-400">
									<span><?php echo esc_html( (string) $item ); ?></span>
								</label>
							<?php endforeach; ?>
						</fieldset>
					<?php endforeach; ?>
				</div>
				<p class="text-sm font-bold text-white" aria-live="polite">Điểm ưu tiên được cộng: <span id="cvc-priority-total" class="text-amber-300 tabular-nums">0</span> điểm</p>
				<p class="text-[11px] text-slate-500"><?php echo esc_html( (string) ( $points['note'] ?? '' ) ); ?></p>
				<script>
				(function () {
					var box = document.getElementById('cvc-priority');
					if (!box) return;
					var boxes = box.querySelectorAll('input[type=checkbox]');
					var total = document.getElementById('cvc-priority-total');
					var calc = function () { var m = 0; boxes.forEach(function (b) { if (b.checked) m = Math.max(m, parseFloat(b.dataset.points)); }); total.textContent = String(m).replace('.', ','); };
					boxes.forEach(function (b) { b.addEventListener('change', calc); });
				})();
				</script>
			</div>
		<?php endif; ?>

		<div class="space-y-3" id="mau-ho-so">
			<h3 class="text-sm font-bold text-white">Phiếu đăng ký dự tuyển - cách điền</h3>
			<p class="text-xs text-slate-400">Theo <?php echo esc_html( $form['basis'] ); ?>. Nộp đúng mẫu trong thông báo của cơ quan tuyển dụng nếu có khác biệt.</p>
			<ol class="list-decimal pl-5 space-y-1.5 text-xs text-slate-300">
				<li><strong class="text-slate-100">Vị trí việc làm và cơ quan dự tuyển:</strong> ghi đúng tên như trong thông báo/biểu chỉ tiêu. Vị trí tuyển chung cho nhiều cơ quan: ghi cơ quan có thứ tự ưu tiên cao nhất.</li>
				<li><strong class="text-slate-100">Thông tin cá nhân:</strong> khớp với thẻ căn cước; số điện thoại và email phải dùng được để nhận thông báo.</li>
				<li><strong class="text-slate-100">Văn bằng, chứng chỉ:</strong> ghi chuyên ngành theo <em>bảng điểm</em> và ngành đào tạo theo văn bằng; văn bằng còn giá trị tại thời điểm nộp phiếu (nếu được nộp giấy xác nhận thì phải nộp bản chính khi hoàn thiện hồ sơ).</li>
				<li><strong class="text-slate-100">Đối tượng ưu tiên:</strong> ghi rõ diện và số điểm (chỉ tính mức cao nhất); khi trúng tuyển phải xuất trình giấy chứng nhận.</li>
				<li><strong class="text-slate-100">Hình thức nhận thông báo:</strong> đánh dấu xác nhận theo thông báo tuyển dụng; không xác nhận thì phải nêu lý do và đề xuất 1 hình thức khác.</li>
				<li><strong class="text-slate-100">Thứ tự ưu tiên (nguyện vọng):</strong> chỉ với vị trí tuyển chung - đăng ký ít nhất 1 cơ quan; cơ quan không ghi trong danh sách được hiểu là không đăng ký vào đó.</li>
				<li><strong class="text-slate-100">Ký cam đoan:</strong> người đăng ký ký, ghi rõ họ tên; chịu trách nhiệm về thông tin đã khai.</li>
			</ol>
			<div class="flex flex-wrap gap-2 text-xs">
				<a href="<?php echo esc_url( get_theme_file_uri( '/assets/downloads/' . $form['file'] ) ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-azure-500 hover:bg-azure-600 text-white font-black rounded-xl"><i class="fa-solid fa-file-word" aria-hidden="true"></i> Tải Mẫu số 01 (.docx)</a>
				<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-amber-300 font-bold rounded-xl border border-amber-400/40">Kho tài liệu &amp; mẫu hồ sơ</a>
			</div>
		</div>
	</section>
	<?php
}

add_action( 'wp_ajax_cvc_suggest', 'cvc_handle_suggest_ajax' );
add_action( 'wp_ajax_nopriv_cvc_suggest', 'cvc_handle_suggest_ajax' );

function cvc_handle_suggest_ajax(): void {
	check_ajax_referer( 'cvc_candidate', 'nonce' );
	$major  = sanitize_text_field( wp_unslash( (string) ( $_POST['major'] ?? '' ) ) );
	$degree = sanitize_key( (string) ( $_POST['degree'] ?? '' ) );
	if ( mb_strlen( $major ) < 2 ) {
		wp_send_json_error( array( 'message' => 'Vui lòng nhập ngành học (ít nhất 2 ký tự).' ), 422 );
	}
	$res = ( new CVC_Api_Client( null, 10 ) )->get( '/api/candidate/suggest', array_filter( array( 'major' => mb_substr( $major, 0, 150 ), 'degree' => $degree ) ) );
	if ( empty( $res['ok'] ) ) {
		wp_send_json_error( array( 'message' => 'Chưa tìm được, vui lòng thử lại sau.' ), $res['status'] ?: 500 );
	}
	$data = (array) ( $res['data']['data'] ?? array() );
	foreach ( (array) ( $data['positions'] ?? array() ) as $i => $p ) {
		$data['positions'][ $i ]['url'] = cvc_recruitment_url( (string) ( $p['recruitment']['slug'] ?? '' ) );
	}
	wp_send_json_success( $data );
}

/**
 * "Tim vi tri hop nganh" tren trang danh sach tin tuyen dung (moi tin con han nop).
 */
function cvc_render_suggest_widget(): void {
	?>
	<section class="bg-navy-950 p-5 rounded-3xl border border-emerald-500/30 space-y-3 shadow-xl text-xs" id="cvc-suggest" data-nonce="<?php echo esc_attr( wp_create_nonce( 'cvc_candidate' ) ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" aria-labelledby="cvc-suggest-h">
		<h2 id="cvc-suggest-h" class="text-sm font-black text-emerald-300 uppercase tracking-wider">Tìm vị trí hợp ngành của bạn</h2>
		<p class="text-slate-400">Đối chiếu ngành học và trình độ với yêu cầu từng vị trí trong mọi tin còn hạn nộp.</p>
		<div class="flex flex-col sm:flex-row gap-2">
			<label for="cvc-sg-major" class="sr-only">Ngành học</label>
			<input type="text" id="cvc-sg-major" maxlength="150" placeholder="Ngành học, VD: Kế toán" class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-400">
			<label for="cvc-sg-degree" class="sr-only">Trình độ</label>
			<select id="cvc-sg-degree" class="px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white">
				<option value="trung_cap">Trung cấp</option><option value="cao_dang">Cao đẳng</option><option value="dai_hoc" selected>Đại học</option><option value="thac_si">Thạc sĩ</option><option value="tien_si">Tiến sĩ</option>
			</select>
			<button type="button" id="cvc-sg-btn" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-navy-950 font-black rounded-xl">Tìm</button>
		</div>
		<div id="cvc-sg-result" class="space-y-2" aria-live="polite" hidden></div>
	</section>
	<script>
	(function () {
		var box = document.getElementById('cvc-suggest');
		if (!box) return;
		var out = document.getElementById('cvc-sg-result');
		var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
		try { var saved = JSON.parse(localStorage.getItem('cvc-elig') || 'null'); if (saved) { document.getElementById('cvc-sg-major').value = saved.major || ''; document.getElementById('cvc-sg-degree').value = saved.degree || 'dai_hoc'; } } catch (e) {}
		document.getElementById('cvc-sg-btn').addEventListener('click', function () {
			var major = document.getElementById('cvc-sg-major').value.trim(), degree = document.getElementById('cvc-sg-degree').value;
			out.hidden = false;
			if (major.length < 2) { out.innerHTML = '<p class="text-amber-300">Vui lòng nhập ngành học.</p>'; return; }
			try { localStorage.setItem('cvc-elig', JSON.stringify({ major: major, degree: degree })); } catch (e) {}
			out.innerHTML = '<p class="text-slate-400">Đang tìm...</p>';
			var fd = new FormData(); fd.append('action', 'cvc_suggest'); fd.append('nonce', box.dataset.nonce); fd.append('major', major); fd.append('degree', degree);
			fetch(box.dataset.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
				if (!j || !j.success) { out.innerHTML = '<p class="text-rose-300">' + esc((j && j.data && j.data.message) || 'Chưa tìm được.') + '</p>'; return; }
				var list = j.data.positions || [];
				if (!list.length) { out.innerHTML = '<p class="text-slate-300">Chưa có vị trí phù hợp trong ' + (j.data.open_recruitments || 0) + ' tin còn hạn nộp. Hệ thống quét thông báo mới từ cổng thông tin các tỉnh mỗi ngày - hãy quay lại sau.</p>'; return; }
				out.innerHTML = list.slice(0, 20).map(function (p) {
					return '<a href="' + esc(p.url) + '" class="block p-3 rounded-xl bg-slate-900 border ' + (p.verdict === 'match' ? 'border-emerald-400/40' : 'border-amber-400/40') + ' hover:bg-slate-800"><span class="block font-bold text-slate-100">' + esc(p.name) + '</span><span class="block text-slate-400">' + esc(p.recruitment.title) + '</span><span class="block ' + (p.verdict === 'match' ? 'text-emerald-300' : 'text-amber-300') + '">' + esc(p.verdict_label) + (p.recruitment.deadline ? ' · hạn ' + esc(p.recruitment.deadline.split('-').reverse().join('/')) : '') + '</span></a>';
				}).join('');
			}).catch(function () { out.innerHTML = '<p class="text-rose-300">Lỗi kết nối, vui lòng thử lại.</p>'; });
		});
	})();
	</script>
	<?php
}
