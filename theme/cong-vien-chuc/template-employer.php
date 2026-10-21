<?php
/**
 * Cổng đơn vị tuyển dụng (21/10/2026): /don-vi/{dang-ky|xac-minh|bang-dieu-khien|dang-tin|thanh-vien}/, /don-vi/tin/{id}/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();
$token = (string) cvc_auth_token();
$api   = cvc_employer_api();
$view  = sanitize_key( (string) get_query_var( 'cvc_employer_view' ) ) ?: 'bang-dieu-khien';
$me    = $api->get( '/api/employer/me', array(), $token );
$data  = $me['ok'] ? (array) ( $me['data']['data'] ?? array() ) : array();
$emp   = is_array( $data['employer'] ?? null ) ? $data['employer'] : null;
$set   = (array) ( $data['settings'] ?? cvc_employer_public_settings() );
$provs = (array) ( $data['provinces'] ?? array() );
$prices = (array) ( $set['prices'] ?? array() );

if ( null === $emp && 'dang-ky' !== $view ) {
	$view = 'dang-ky';
} elseif ( null !== $emp && 'dang-ky' === $view ) {
	$view = 'bang-dieu-khien';
}
if ( null !== $emp && 'verified' !== ( $emp['status'] ?? '' ) && in_array( $view, array( 'bang-dieu-khien', 'dang-tin', 'tin' ), true ) ) {
	$view = 'xac-minh';
}

$titles = array( 'dang-ky' => 'Đăng ký đơn vị tuyển dụng', 'xac-minh' => 'Xác minh đơn vị', 'bang-dieu-khien' => 'Bảng điều khiển đơn vị', 'dang-tin' => 'Đăng tin tuyển dụng', 'tin' => 'Quản lý tin', 'thanh-vien' => 'Thông tin & thành viên đơn vị' );
cvc_seo_set_title( ( $titles[ $view ] ?? 'Đơn vị tuyển dụng' ) . ' | Công Viên Chức' );
cvc_seo_set_noindex();

$input = 'w-full border border-slate-300 rounded px-3 py-2 text-sm';
$label = 'block text-sm font-bold text-ink mb-1';
$form_open = static function ( string $op, array $hidden = array(), bool $multipart = false ): void {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $multipart ? ' enctype="multipart/form-data"' : '' ) . ' class="space-y-3">';
	wp_nonce_field( 'cvc_employer' );
	echo '<input type="hidden" name="action" value="cvc_employer"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
	echo '<input type="hidden" name="back" value="' . esc_url( home_url( add_query_arg( array() ) ) ) . '">';
	foreach ( $hidden as $k => $v ) {
		echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '">';
	}
};

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap">
	<header class="dz-pagehead">
		<p class="text-xs font-bold uppercase tracking-wider text-ink-muted"><a href="<?php echo esc_url( cvc_employer_landing_url() ); ?>">Dành cho đơn vị tuyển dụng</a></p>
		<h1><?php echo esc_html( $titles[ $view ] ?? 'Đơn vị tuyển dụng' ); ?></h1>
		<?php if ( $emp ) : ?>
			<?php if ( ! empty( $emp['logo'] ) ) : ?><p class="mt-2"><?php echo cvc_logo_html( (string) $emp['logo'], (string) $emp['name'], 56 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p><?php endif; ?>
			<p class="text-sm text-ink-muted"><?php echo esc_html( (string) $emp['name'] ); ?> · <?php echo wp_kses_post( cvc_employer_status_tag( (string) $emp['status'] ) ); ?>
				<?php if ( 'auto' === ( $emp['trust'] ?? '' ) ) : ?> <span class="dz-tag is-ok">Được tự đăng tin</span><?php endif; ?>
				<?php if ( 'private' === ( $emp['sector'] ?? '' ) ) : ?> <span class="dz-tag">Ngoài công lập</span><?php endif; ?></p>
		<?php endif; ?>
	</header>

	<?php cvc_render_notice(); ?>
	<?php if ( ! $me['ok'] ) : ?>
		<?php cvc_render_error_state( cvc_api_error_message( $me ) ); ?>
	<?php elseif ( empty( $set['enabled'] ) && null === $emp ) : ?>
		<?php cvc_render_notfound_state( 'Cổng đơn vị tuyển dụng đang tạm đóng.' ); ?>
	<?php else : ?>

	<?php if ( $emp ) : ?>
		<nav class="dz-chips mb-4" aria-label="Menu đơn vị">
			<?php foreach ( array( 'bang-dieu-khien' => 'Bảng điều khiển', 'dang-tin' => 'Đăng tin mới', 'xac-minh' => 'Xác minh', 'thanh-vien' => 'Thông tin & thành viên' ) as $k => $l ) : ?>
				<a class="<?php echo $k === $view ? 'is-active font-bold' : ''; ?>" href="<?php echo esc_url( cvc_employer_url( $k ) ); ?>"><?php echo esc_html( $l ); ?></a>
			<?php endforeach; ?>
			<a class="ml-auto" href="<?php echo esc_url( cvc_account_url() ); ?>">Khu học tập &rarr;</a>
		</nav>
	<?php endif; ?>

	<?php /* ============================================================ ĐĂNG KÝ */ ?>
	<?php if ( 'dang-ky' === $view ) : ?>
		<section class="dz-card max-w-3xl">
			<p class="text-sm text-ink-2 mb-3">Đăng ký miễn phí để đăng thông báo tuyển dụng, cập nhật đợt tuyển và theo dõi số thí sinh quan tâm. Mỗi tài khoản quản lý một đơn vị; có thể thêm đồng nghiệp sau.</p>
			<?php $form_open( 'register' ); ?>
				<div><label class="<?php echo esc_attr( $label ); ?>" for="e-name">Tên đơn vị (theo con dấu)</label><input id="e-name" name="name" required minlength="5" maxlength="255" class="<?php echo esc_attr( $input ); ?>" placeholder="VD: Bệnh viện Đa khoa tỉnh …"></div>
				<fieldset class="text-sm"><legend class="<?php echo esc_attr( $label ); ?>">Loại hình</legend>
					<label class="mr-4"><input type="radio" name="sector" value="public" checked> Cơ quan nhà nước / đơn vị sự nghiệp công lập</label>
					<?php if ( ! empty( $set['private_enabled'] ) ) : ?><label><input type="radio" name="sector" value="private"> Đơn vị ngoài công lập</label><?php endif; ?>
				</fieldset>
				<div class="grid md:grid-cols-2 gap-3">
					<div><label class="<?php echo esc_attr( $label ); ?>" for="e-prov">Tỉnh/thành</label>
						<select id="e-prov" name="province_id" required class="<?php echo esc_attr( $input ); ?>"><option value="">— Chọn —</option>
							<?php foreach ( $provs as $pv ) : ?><option value="<?php echo (int) $pv['id']; ?>"><?php echo esc_html( (string) $pv['name'] ); ?></option><?php endforeach; ?>
						</select></div>
					<div><label class="<?php echo esc_attr( $label ); ?>" for="e-phone">Điện thoại cơ quan</label><input id="e-phone" name="phone" required class="<?php echo esc_attr( $input ); ?>" placeholder="Số cố định của phòng tổ chức / văn phòng"></div>
					<div><label class="<?php echo esc_attr( $label ); ?>" for="e-web">Website đơn vị</label><input id="e-web" name="website" type="url" class="<?php echo esc_attr( $input ); ?>" placeholder="https://…gov.vn"></div>
					<div><label class="<?php echo esc_attr( $label ); ?>" for="e-tax">Mã số thuế <span class="font-normal text-ink-muted">(bắt buộc với ngoài công lập)</span></label><input id="e-tax" name="tax_code" class="<?php echo esc_attr( $input ); ?>"></div>
				</div>
				<div><label class="<?php echo esc_attr( $label ); ?>" for="e-addr">Địa chỉ</label><input id="e-addr" name="address" maxlength="500" class="<?php echo esc_attr( $input ); ?>"></div>
				<button type="submit" class="dz-btn dz-btn-primary">Đăng ký đơn vị</button>
			</form>
		</section>

	<?php /* ============================================================ XÁC MINH */ ?>
	<?php elseif ( 'xac-minh' === $view ) : ?>
		<?php $st = (string) $emp['status']; ?>
		<div class="dz-cols is-left">
			<section class="dz-card space-y-3">
				<?php if ( 'verified' === $st ) : ?>
					<p class="text-sm">Đơn vị đã được xác minh<?php echo ! empty( $emp['official_email'] ) ? ' qua email ' . esc_html( (string) $emp['official_email'] ) : ' bằng giấy giới thiệu'; ?>. <a class="font-bold text-acc" href="<?php echo esc_url( cvc_employer_url( 'dang-tin' ) ); ?>">Đăng tin ngay &rarr;</a></p>
				<?php else : ?>
					<?php if ( 'rejected' === $st && ! empty( $emp['note'] ) ) : ?><div class="dz-notice">Chưa xác minh được: <?php echo esc_html( (string) $emp['note'] ); ?></div><?php endif; ?>
					<?php if ( 'suspended' === $st ) : ?><div class="dz-notice">Đơn vị đang tạm khóa đăng tin<?php echo ! empty( $emp['note'] ) ? ': ' . esc_html( (string) $emp['note'] ) : ''; ?>.</div><?php endif; ?>
					<?php if ( 'public' === ( $emp['sector'] ?? 'public' ) ) : ?>
						<?php
						$pending  = (string) ( $emp['pending_code_email'] ?? '' );
						$my_email = (string) ( ( cvc_current_user() ?? array() )['email'] ?? '' );
						$prefill  = '' !== $pending ? $pending : ( preg_match( '/@([a-z0-9-]+\.)*(gov|edu)\.vn$/i', $my_email ) ? $my_email : '' );
						?>
						<h2 class="dz-h2">Cách 1 — Email công vụ (xác minh ngay)</h2>
						<?php if ( '' !== $pending ) : ?>
							<p class="text-sm">Mã 6 số đã gửi tới <b><?php echo esc_html( $pending ); ?></b> (hiệu lực 30 phút). Kiểm tra cả hộp thư rác.</p>
							<?php $form_open( 'verify_code' ); ?>
								<div class="flex gap-2"><input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus class="<?php echo esc_attr( $input ); ?>" placeholder="Nhập mã 6 số"><button class="dz-btn dz-btn-primary" type="submit">Xác minh</button></div>
							</form>
							<details class="text-sm"><summary class="cursor-pointer text-acc">Không nhận được mã? Gửi lại hoặc dùng email khác</summary>
							<?php $form_open( 'send_code' ); ?>
								<div class="flex gap-2 mt-2"><input name="email" type="email" required class="<?php echo esc_attr( $input ); ?>" value="<?php echo esc_attr( $prefill ); ?>"><button class="dz-btn" type="submit">Gửi lại mã</button></div>
							</form></details>
						<?php else : ?>
							<p class="text-sm text-ink-muted">Email thuộc tên miền nhà nước (<b>.gov.vn</b>, <b>.edu.vn</b>) hoặc tên miền đã được xác minh. Hệ thống gửi mã 6 số.</p>
							<?php $form_open( 'send_code' ); ?>
								<div class="flex gap-2"><input name="email" type="email" required class="<?php echo esc_attr( $input ); ?>" placeholder="tccb@syt.xxx.gov.vn" value="<?php echo esc_attr( $prefill ); ?>"><button class="dz-btn" type="submit">Gửi mã</button></div>
							</form>
							<?php $form_open( 'verify_code' ); ?>
								<div class="flex gap-2"><input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="<?php echo esc_attr( $input ); ?>" placeholder="Nhập mã 6 số"><button class="dz-btn dz-btn-primary" type="submit">Xác minh</button></div>
							</form>
						<?php endif; ?>
						<h2 class="dz-h2">Cách 2 — Giấy giới thiệu có dấu</h2>
					<?php else : ?>
						<h2 class="dz-h2">Giấy phép / giấy giới thiệu có dấu</h2>
					<?php endif; ?>
					<p class="text-sm text-ink-muted">Tải giấy giới thiệu (hoặc giấy phép hoạt động với đơn vị ngoài công lập) có dấu đỏ. Biên tập viên gọi số điện thoại cơ quan để xác nhận rồi duyệt.</p>
					<?php if ( ! empty( $emp['has_document'] ) ) : ?><p class="text-sm"><?php echo wp_kses_post( cvc_employer_status_tag( 'pending', 'Đã nhận giấy tờ — chờ xác nhận' ) ); ?></p><?php endif; ?>
					<?php $form_open( 'verify_doc', array(), true ); ?>
						<input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp" class="block text-sm">
						<button class="dz-btn" type="submit">Gửi giấy tờ</button>
					</form>
				<?php endif; ?>
			</section>
			<aside class="dz-colside dz-box"><div class="dz-box-head">Vì sao cần xác minh?</div><div class="dz-box-body text-sm text-ink-2 space-y-2">
				<p>Thí sinh tin tưởng thông báo vì biết chắc đơn vị đăng là thật. Tin của đơn vị đã xác minh mang nhãn <b>"Đơn vị đăng trực tiếp – đã xác minh"</b>.</p>
				<p>Thông tin cá nhân, giấy tờ chỉ biên tập viên xem để xác minh, không công khai.</p></div></aside>
		</div>

	<?php /* ============================================================ BẢNG ĐIỀU KHIỂN */ ?>
	<?php elseif ( 'bang-dieu-khien' === $view ) : ?>
		<?php
		$res   = $api->get( '/api/employer/posts', array(), $token );
		$d     = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
		$stats = (array) ( $d['stats'] ?? array() );
		?>
		<dl class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
			<?php foreach ( array( 'recruitments' => 'Tin đã đăng', 'views' => 'Lượt xem', 'bookmarks' => 'Lượt lưu tin', 'reminders' => 'Bật nhắc hạn', 'matches' => 'Thí sinh phù hợp' ) as $k => $l ) : ?>
				<div class="dz-card"><dt class="text-[11px] font-bold uppercase text-ink-muted"><?php echo esc_html( $l ); ?></dt><dd class="text-2xl font-black tabular-nums"><?php echo esc_html( number_format_i18n( (int) ( $stats[ $k ] ?? 0 ) ) ); ?></dd></div>
			<?php endforeach; ?>
		</dl>
		<?php if ( ! empty( $stats['daily'] ) ) : ?>
			<?php $max = max( 1, max( array_map( static fn ( $x ) => (int) $x['views'], (array) $stats['daily'] ) ) ); ?>
			<section class="dz-card mb-4" aria-label="Lượt xem 30 ngày">
				<h2 class="text-sm font-black mb-2">Người xem mới 30 ngày qua</h2>
				<div class="flex items-end gap-1 h-24" role="img" aria-label="Biểu đồ lượt xem theo ngày">
					<?php foreach ( (array) $stats['daily'] as $row ) : ?>
						<div class="flex-1 bg-acc-2" style="height:<?php echo (int) round( (int) $row['views'] / $max * 100 ); ?>%" title="<?php echo esc_attr( wp_date( 'd/m', strtotime( (string) $row['date'] ) ) . ': ' . (int) $row['views'] ); ?>"></div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $d['drafts'] ) ) : ?>
			<section class="dz-section"><h2 class="dz-h2">Tin đang soạn / chờ duyệt</h2>
				<ul class="dz-list">
					<?php foreach ( (array) $d['drafts'] as $x ) : ?>
						<li><div class="dz-item-title"><a href="<?php echo esc_url( cvc_employer_url( 'dang-tin', array( 'id' => (int) $x['id'] ) ) ); ?>"><?php echo esc_html( (string) ( $x['title'] ?: 'Bản nhập #' . $x['id'] ) ); ?></a></div>
							<div class="dz-item-meta"><?php echo wp_kses_post( cvc_employer_status_tag( in_array( $x['status'], array( 'ocr', 'analyzing', 'failed' ), true ) ? (string) $x['status'] : (string) $x['employer_status'] ) ); ?>
								<?php echo ! empty( $x['note'] ) ? ' · ' . esc_html( (string) $x['note'] ) : ''; ?></div></li>
					<?php endforeach; ?>
				</ul></section>
		<?php endif; ?>

		<section class="dz-section"><h2 class="dz-h2">Tin của đơn vị</h2>
			<?php if ( empty( $d['recruitments'] ) ) : ?>
				<p class="dz-empty">Chưa có tin nào. <a class="font-bold text-acc" href="<?php echo esc_url( cvc_employer_url( 'dang-tin' ) ); ?>">Đăng tin đầu tiên</a> hoặc <a class="font-bold text-acc" href="<?php echo esc_url( cvc_employer_url( 'thanh-vien' ) ); ?>#nhan-tin">nhận quản lý tin hệ thống đã thu thập</a>.</p>
			<?php else : ?>
				<div class="dz-tablewrap"><table class="dz-table"><thead><tr><th>Tin</th><th>Hạn nộp</th><th>Lượt xem</th><th>Trạng thái</th></tr></thead><tbody>
					<?php foreach ( (array) $d['recruitments'] as $x ) : ?>
						<tr><td><a class="font-bold" href="<?php echo esc_url( cvc_employer_post_url( (int) $x['id'] ) ); ?>"><?php echo esc_html( (string) $x['title'] ); ?></a><?php echo ! empty( $x['pinned'] ) ? ' <span class="dz-tag is-ok">Đang ghim</span>' : ''; ?></td>
							<td><?php echo esc_html( ! empty( $x['application_deadline'] ) ? wp_date( 'd/m/Y', strtotime( (string) $x['application_deadline'] ) ) : '—' ); ?></td>
							<td class="tabular-nums"><?php echo esc_html( number_format_i18n( (int) $x['views'] ) ); ?></td>
							<td><?php echo wp_kses_post( cvc_employer_status_tag( (string) $x['status'] ) ); ?></td></tr>
					<?php endforeach; ?>
				</tbody></table></div>
			<?php endif; ?>
		</section>

		<?php if ( ! empty( $d['promotions'] ) ) : ?>
			<section class="dz-section"><h2 class="dz-h2">Dịch vụ (ghim, email, phí đăng tin)</h2>
				<div class="dz-tablewrap"><table class="dz-table"><thead><tr><th>Dịch vụ</th><th>Phí</th><th>Trạng thái</th><th></th></tr></thead><tbody>
					<?php foreach ( (array) $d['promotions'] as $pr ) : ?>
						<tr><td><?php echo esc_html( (string) $pr['kind_label'] ); ?><?php echo 'email' === $pr['kind'] ? ' — đã gửi ' . (int) $pr['sent_count'] . '/' . (int) $pr['target_count'] : ''; ?><?php echo 'pin' === $pr['kind'] && ! empty( $pr['ends_at'] ) ? ' — đến ' . esc_html( wp_date( 'd/m/Y H:i', strtotime( (string) $pr['ends_at'] ) ) ) : ''; ?></td>
							<td><?php echo esc_html( cvc_employer_money( (int) $pr['amount'] ) ); ?></td>
							<td><?php echo wp_kses_post( cvc_employer_status_tag( (string) $pr['status'] ) ); ?></td>
							<td><?php if ( 'awaiting_payment' === $pr['status'] ) : ?>
								<?php $form_open( 'promo_pay', array( 'promotion_id' => (int) $pr['id'] ) ); ?><button class="dz-btn dz-btn-sm dz-btn-primary">Thanh toán</button></form>
								<?php $form_open( 'promo_cancel', array( 'promotion_id' => (int) $pr['id'] ) ); ?><button class="dz-btn dz-btn-sm">Hủy</button></form>
							<?php endif; ?></td></tr>
					<?php endforeach; ?>
				</tbody></table></div></section>
		<?php endif; ?>

	<?php /* ============================================================ ĐĂNG TIN */ ?>
	<?php elseif ( 'dang-tin' === $view ) : ?>
		<?php $iid = absint( $_GET['id'] ?? 0 ); ?>
		<?php if ( ! $iid ) : ?>
			<section class="dz-card max-w-3xl space-y-3">
				<p class="text-sm text-ink-2">Tải thông báo tuyển dụng (bản PDF/Word hoặc ảnh chụp có dấu) và các phụ lục chỉ tiêu. Hệ thống đọc chữ và <b>điền sẵn</b> thông tin; bạn chỉ cần kiểm tra, sửa rồi gửi.<?php echo (int) ( $prices['post'] ?? 0 ) > 0 ? ' Phí đăng tin: <b>' . esc_html( cvc_employer_money( (int) $prices['post'] ) ) . '</b>.' : ' <b>Miễn phí.</b>'; ?></p>
				<?php $form_open( 'post_upload', array(), true ); ?>
					<div><label class="<?php echo esc_attr( $label ); ?>" for="p-files">Tệp (tối đa 10, mỗi tệp ≤ 25MB)</label><input id="p-files" type="file" name="files[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.heic,.zip,.rar" class="block text-sm"></div>
					<div><label class="<?php echo esc_attr( $label ); ?>" for="p-url">Link thông báo trên website đơn vị <span class="font-normal text-ink-muted">(nếu đã đăng)</span></label><input id="p-url" type="url" name="source_url" class="<?php echo esc_attr( $input ); ?>" placeholder="https://…"></div>
					<div><label class="<?php echo esc_attr( $label ); ?>" for="p-title">Tiêu đề <span class="font-normal text-ink-muted">(tùy chọn)</span></label><input id="p-title" name="title" maxlength="500" class="<?php echo esc_attr( $input ); ?>"></div>
					<label class="flex gap-2 text-sm"><input type="checkbox" name="allow_duplicate" value="1"> Vẫn tải lên dù tệp đã có trên hệ thống</label>
					<button type="submit" class="dz-btn dz-btn-primary">Tải lên &amp; điền sẵn thông tin</button>
				</form>
			</section>
		<?php else : ?>
			<?php
			$res = $api->get( '/api/employer/posts/' . $iid, array(), $token );
			$pd  = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
			$dr  = (array) ( $pd['draft'] ?? array() );
			$busy = in_array( $pd['status'] ?? '', array( 'ocr', 'analyzing' ), true );
			$locked = in_array( $pd['employer_status'] ?? '', array( 'submitted', 'approved' ), true );
			?>
			<?php if ( ! $res['ok'] ) : ?>
				<?php cvc_render_error_state( cvc_api_error_message( $res ) ); ?>
			<?php elseif ( $busy ) : ?>
				<section class="dz-card"><p class="text-sm"><?php echo wp_kses_post( cvc_employer_status_tag( (string) $pd['status'] ) ); ?> <?php echo esc_html( (string) ( $pd['progress']['message'] ?? 'Đang xử lý…' ) ); ?></p>
					<p class="text-xs text-ink-muted mt-2">Trang tự tải lại sau 10 giây.</p></section>
				<script>setTimeout(function(){ location.reload(); }, 10000);</script>
			<?php elseif ( 'failed' === ( $pd['status'] ?? '' ) ) : ?>
				<?php cvc_render_error_state( (string) ( $pd['error'] ?? 'Không đọc được tệp.' ) . ' Hãy tải bản PDF gốc hoặc ảnh rõ hơn.' ); ?>
			<?php else : ?>
				<div class="dz-cols is-left">
					<section class="dz-card space-y-3">
						<p><?php echo wp_kses_post( cvc_employer_status_tag( (string) ( $pd['employer_status'] ?? 'draft' ) ) ); ?></p>
						<?php if ( ! empty( $pd['employer_note'] ) ) : ?><div class="dz-notice"><?php echo esc_html( (string) $pd['employer_note'] ); ?></div><?php endif; ?>
						<?php if ( ! empty( $dr['duplicate_of'] ) ) : ?><div class="dz-notice">Có thể trùng tin đã có: <a class="font-bold" href="<?php echo esc_url( home_url( '/tuyen-dung/' . rawurlencode( (string) $dr['duplicate_of']['slug'] ) . '/' ) ); ?>" target="_blank"><?php echo esc_html( (string) $dr['duplicate_of']['title'] ); ?></a>. Nếu đúng là thông báo của đơn vị, hãy <a class="font-bold" href="<?php echo esc_url( cvc_employer_url( 'thanh-vien' ) ); ?>#nhan-tin">nhận quản lý tin đó</a>.</div><?php endif; ?>
						<?php if ( ! empty( $dr['plan_errors'] ) ) : ?><div class="dz-notice"><b>Cần bổ sung trước khi đăng:</b> <?php echo esc_html( implode( '; ', (array) $dr['plan_errors'] ) ); ?></div><?php endif; ?>
						<?php $form_open( 'post_update', array( 'import_id' => $iid ) ); ?>
							<fieldset <?php disabled( $locked ); ?> class="space-y-3">
							<div><label class="<?php echo esc_attr( $label ); ?>">Tiêu đề</label><input name="title" value="<?php echo esc_attr( (string) ( $dr['title'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
							<div class="grid md:grid-cols-2 gap-3">
								<div><label class="<?php echo esc_attr( $label ); ?>">Cơ quan tuyển dụng</label><input name="agency_name" value="<?php echo esc_attr( (string) ( $dr['agency_name'] ?? $emp['name'] ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
								<div><label class="<?php echo esc_attr( $label ); ?>">Tỉnh/thành</label><input name="province_name" value="<?php echo esc_attr( (string) ( $dr['province_name'] ?? $emp['province'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
								<div><label class="<?php echo esc_attr( $label ); ?>">Ngày ban hành thông báo</label><input type="date" name="announcement_date" value="<?php echo esc_attr( substr( (string) ( $dr['announcement_date'] ?? '' ), 0, 10 ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
								<div><label class="<?php echo esc_attr( $label ); ?>">Tổng chỉ tiêu</label><input type="number" min="1" name="total_positions" value="<?php echo esc_attr( (string) ( $dr['total_positions'] ?? $dr['positions_total'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
								<div><label class="<?php echo esc_attr( $label ); ?>">Nhận hồ sơ từ ngày</label><input type="date" name="application_start_date" value="<?php echo esc_attr( substr( (string) ( $dr['application_start_date'] ?? '' ), 0, 10 ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
								<div><label class="<?php echo esc_attr( $label ); ?>">Hạn nộp hồ sơ</label><input type="date" name="application_deadline" value="<?php echo esc_attr( substr( (string) ( $dr['application_deadline'] ?? '' ), 0, 10 ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
							</div>
							<div><label class="<?php echo esc_attr( $label ); ?>">Địa điểm nhận hồ sơ</label><input name="location" value="<?php echo esc_attr( (string) ( $dr['location'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
							<div><label class="<?php echo esc_attr( $label ); ?>">Tóm tắt (điều kiện, hồ sơ, lệ phí…)</label><textarea name="summary" rows="6" class="<?php echo esc_attr( $input ); ?>"><?php echo esc_textarea( (string) ( $dr['summary'] ?? '' ) ); ?></textarea></div>
							<?php if ( ! $locked ) : ?><button type="submit" class="dz-btn">Lưu thay đổi</button><?php endif; ?>
							</fieldset>
						</form>
						<?php if ( ! $locked ) : ?>
							<?php $form_open( 'post_submit', array( 'import_id' => $iid ) ); ?>
								<button type="submit" class="dz-btn dz-btn-primary"><?php echo 'auto' === ( $emp['trust'] ?? '' ) ? 'Đăng tin' : 'Gửi duyệt'; ?><?php echo (int) ( $pd['post_price'] ?? 0 ) > 0 ? ' (phí ' . esc_html( cvc_employer_money( (int) $pd['post_price'] ) ) . ')' : ''; ?></button>
								<span class="text-xs text-ink-muted"><?php echo 'auto' === ( $emp['trust'] ?? '' ) ? 'Đơn vị được tự đăng — tin hiển thị ngay sau khi qua kiểm tra tự động.' : 'Biên tập viên duyệt trong giờ hành chính.'; ?></span>
							</form>
						<?php endif; ?>
					</section>
					<aside class="dz-colside space-y-3">
						<div class="dz-box"><div class="dz-box-head">Vị trí, chỉ tiêu (AI đọc từ tệp)</div><div class="dz-box-body text-sm">
							<?php if ( empty( $dr['positions'] ) ) : ?><p class="text-ink-muted">Chưa tách được bảng vị trí — biên tập viên sẽ bổ sung từ phụ lục.</p>
							<?php else : ?><ul class="space-y-1"><?php foreach ( (array) $dr['positions'] as $ps ) : ?><li><?php echo esc_html( (string) $ps['name'] ); ?> — <b><?php echo (int) $ps['quantity']; ?></b></li><?php endforeach; ?></ul><?php endif; ?>
						</div></div>
						<div class="dz-box"><div class="dz-box-head">Tệp đã tải</div><div class="dz-box-body text-sm"><ul>
							<?php foreach ( (array) ( $pd['files'] ?? array() ) as $f ) : ?><li><?php echo esc_html( (string) $f['name'] ); ?> <span class="text-ink-muted">(<?php echo esc_html( number_format_i18n( (int) $f['chars'] ) ); ?> ký tự)</span></li><?php endforeach; ?>
						</ul></div></div>
					</aside>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	<?php /* ============================================================ QUẢN LÝ 1 TIN */ ?>
	<?php elseif ( 'tin' === $view ) : ?>
		<?php
		$rid = absint( get_query_var( 'cvc_employer_id' ) );
		$res = $api->get( '/api/employer/recruitments/' . $rid, array(), $token );
		$td  = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
		$rc  = (array) ( $td['recruitment'] ?? array() );
		$ts  = (array) ( $td['stats'] ?? array() );
		?>
		<?php if ( ! $res['ok'] ) : ?>
			<?php cvc_render_error_state( cvc_api_error_message( $res ) ); ?>
		<?php else : ?>
			<h2 class="text-lg font-black mb-1"><a href="<?php echo esc_url( home_url( '/tuyen-dung/' . rawurlencode( (string) $rc['slug'] ) . '/' ) ); ?>" target="_blank"><?php echo esc_html( (string) $rc['title'] ); ?></a></h2>
			<p class="text-sm mb-3"><?php echo wp_kses_post( cvc_employer_status_tag( (string) $rc['status'] ) ); ?> <?php echo ! empty( $rc['pinned'] ) ? '<span class="dz-tag is-ok">Đang ghim đến ' . esc_html( wp_date( 'd/m/Y H:i', strtotime( (string) $rc['pinned_until'] ) ) ) . '</span>' : ''; ?>
				· <?php echo esc_html( number_format_i18n( (int) ( $ts['views'] ?? 0 ) ) ); ?> lượt xem · <?php echo (int) ( $ts['bookmarks'] ?? 0 ); ?> lượt lưu · <?php echo (int) ( $ts['reminders'] ?? 0 ); ?> bật nhắc hạn · <?php echo (int) ( $ts['matches'] ?? 0 ); ?> thí sinh phù hợp
				<?php echo (int) ( $ts['reports_open'] ?? 0 ) > 0 ? ' · <span class="dz-tag is-warn">' . (int) $ts['reports_open'] . ' báo sai đang kiểm tra</span>' : ''; ?></p>

			<div class="grid lg:grid-cols-2 gap-4">
				<section class="dz-card space-y-3">
					<h3 class="dz-h2">Quảng bá tin</h3>
					<?php $pp = (array) ( $td['settings']['prices'] ?? $prices ); ?>
					<?php $form_open( 'promote', array( 'recruitment_id' => $rid, 'kind' => 'pin' ) ); ?>
						<button class="dz-btn" type="submit"><i class="fa-solid fa-thumbtack" aria-hidden="true"></i> Ghim tin <?php echo (int) ( $td['settings']['pin_days'] ?? 7 ); ?> ngày — <?php echo esc_html( cvc_employer_money( (int) ( $pp['pin'] ?? 0 ) ) ); ?></button>
						<p class="text-xs text-ink-muted">Tin hiện đầu danh sách tuyển dụng trong thời gian ghim.</p>
					</form>
					<?php $form_open( 'promote', array( 'recruitment_id' => $rid, 'kind' => 'email' ) ); ?>
						<button class="dz-btn" type="submit"><i class="fa-regular fa-envelope" aria-hidden="true"></i> Gửi email giới thiệu tin — <?php echo esc_html( cvc_employer_money( (int) ( $pp['email'] ?? 0 ) ) ); ?></button>
						<p class="text-xs text-ink-muted">Gửi tới tối đa <?php echo (int) ( $td['settings']['email_cap'] ?? 500 ); ?> thí sinh có mục tiêu phù hợp / cùng tỉnh, đã bật nhận tin tuyển dụng.</p>
					</form>
					<?php if ( ! empty( $td['promotions'] ) ) : ?>
						<ul class="text-sm"><?php foreach ( (array) $td['promotions'] as $pr ) : ?><li><?php echo esc_html( (string) $pr['kind_label'] ); ?> · <?php echo wp_kses_post( cvc_employer_status_tag( (string) $pr['status'] ) ); ?><?php echo 'email' === $pr['kind'] ? ' · ' . (int) $pr['sent_count'] . '/' . (int) $pr['target_count'] : ''; ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				</section>

				<section class="dz-card space-y-3">
					<h3 class="dz-h2">Cập nhật đợt tuyển</h3>
					<?php $form_open( 'update_upload', array( 'recruitment_id' => $rid ), true ); ?>
						<select name="kind" class="<?php echo esc_attr( $input ); ?>"><?php foreach ( (array) ( $td['update_kinds'] ?? array() ) as $k => $l ) : ?><option value="<?php echo esc_attr( (string) $k ); ?>"><?php echo esc_html( (string) $l ); ?></option><?php endforeach; ?></select>
						<input name="title" required minlength="5" maxlength="500" class="<?php echo esc_attr( $input ); ?>" placeholder="VD: Danh sách thí sinh đủ điều kiện dự xét tuyển vòng 2">
						<input type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" class="block text-sm">
						<button class="dz-btn" type="submit">Đăng văn bản</button>
					</form>
					<?php if ( ! empty( $td['updates'] ) ) : ?>
						<ul class="text-sm"><?php foreach ( (array) $td['updates'] as $u ) : ?><li><?php echo esc_html( (string) $u['title'] ); ?> · <?php echo wp_kses_post( cvc_employer_status_tag( (string) $u['status'] ) ); ?><?php echo ! empty( $u['review_note'] ) ? ' — ' . esc_html( (string) $u['review_note'] ) : ''; ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				</section>

				<section class="dz-card space-y-3 lg:col-span-2">
					<h3 class="dz-h2">Đính chính / gia hạn</h3>
					<p class="text-xs text-ink-muted">Chỉ điền mục cần thay đổi. Mỗi lần đính chính được lưu và hiển thị công khai trong "Lịch sử điều chỉnh" của tin.</p>
					<?php $form_open( 'change', array( 'recruitment_id' => $rid ) ); ?>
						<div class="grid md:grid-cols-3 gap-3">
							<div><label class="<?php echo esc_attr( $label ); ?>">Nhận hồ sơ từ</label><input type="date" name="application_start_date" placeholder="<?php echo esc_attr( (string) ( $rc['application_start_date'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"><span class="text-xs text-ink-muted">Hiện: <?php echo esc_html( (string) ( $rc['application_start_date'] ?? '—' ) ); ?></span></div>
							<div><label class="<?php echo esc_attr( $label ); ?>">Hạn nộp mới</label><input type="date" name="application_deadline" class="<?php echo esc_attr( $input ); ?>"><span class="text-xs text-ink-muted">Hiện: <?php echo esc_html( (string) ( $rc['application_deadline'] ?? '—' ) ); ?></span></div>
							<div><label class="<?php echo esc_attr( $label ); ?>">Tổng chỉ tiêu</label><input type="number" min="1" name="total_positions" class="<?php echo esc_attr( $input ); ?>"><span class="text-xs text-ink-muted">Hiện: <?php echo esc_html( (string) ( $rc['total_positions'] ?? '—' ) ); ?></span></div>
						</div>
						<div><label class="<?php echo esc_attr( $label ); ?>">Địa điểm nhận hồ sơ</label><input name="location" class="<?php echo esc_attr( $input ); ?>" placeholder="<?php echo esc_attr( (string) ( $rc['location'] ?? '' ) ); ?>"></div>
						<div><label class="<?php echo esc_attr( $label ); ?>">Lý do / số văn bản điều chỉnh (bắt buộc)</label><textarea name="note" required minlength="5" rows="2" class="<?php echo esc_attr( $input ); ?>" placeholder="VD: Thông báo số 123/TB-SYT ngày 20/10/2026 gia hạn nhận hồ sơ"></textarea></div>
						<button class="dz-btn dz-btn-primary" type="submit">Gửi đính chính</button>
					</form>
					<?php if ( ! empty( $td['changes'] ) ) : ?>
						<ul class="text-sm"><?php foreach ( (array) $td['changes'] as $c ) : ?><li><?php echo wp_kses_post( cvc_employer_status_tag( (string) $c['status'] ) ); ?> <?php echo esc_html( implode( ', ', array_map( static fn ( $k, $v ) => $k . ' → ' . ( is_scalar( $v ) ? $v : '' ), array_keys( (array) $c['changes'] ), (array) $c['changes'] ) ) ); ?><?php echo ! empty( $c['review_note'] ) ? ' — ' . esc_html( (string) $c['review_note'] ) : ''; ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				</section>
			</div>
		<?php endif; ?>

	<?php /* ============================================================ THÔNG TIN & THÀNH VIÊN */ ?>
	<?php elseif ( 'thanh-vien' === $view ) : ?>
		<?php
		$mem = $api->get( '/api/employer/members', array(), $token );
		$cl  = $api->get( '/api/employer/claims', array(), $token );
		$q   = sanitize_text_field( wp_unslash( (string) ( $_GET['q'] ?? '' ) ) );
		$sr  = mb_strlen( $q ) >= 3 ? $api->get( '/api/employer/recruitments/search', array( 'q' => $q ), $token ) : null;
		?>
		<div class="grid lg:grid-cols-2 gap-4">
			<section class="dz-card space-y-3"><h2 class="dz-h2">Logo đơn vị</h2>
				<div class="cvc-avatar-row">
					<?php echo ! empty( $emp['logo'] ) ? cvc_logo_html( (string) $emp['logo'], (string) $emp['name'], 96 ) : '<span class="cvc-logo is-empty" aria-hidden="true">Chưa có logo</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="cvc-avatar-forms">
						<?php $form_open( 'logo', array(), true ); ?>
							<?php cvc_render_image_picker( 'file', 'contain' ); ?>
							<button class="dz-btn dz-btn-primary" type="submit">Lưu logo</button>
						</form>
						<?php if ( ! empty( $emp['logo'] ) ) : ?>
							<?php $form_open( 'logo_remove' ); ?><button class="cvc-linkbtn" type="submit">Xóa logo</button></form>
						<?php endif; ?>
					</div>
				</div>
				<p class="text-sm text-ink-muted">Logo hiện cạnh tin tuyển dụng của đơn vị<?php echo 'verified' === ( $emp['status'] ?? '' ) ? '' : ' sau khi đơn vị được xác minh'; ?>. Dùng đúng logo / con dấu biểu trưng của đơn vị; biên tập viên có thể gỡ logo không phù hợp.</p>
			</section>
			<section class="dz-card space-y-3"><h2 class="dz-h2">Thông tin đơn vị</h2>
				<?php $form_open( 'profile' ); ?>
					<div><label class="<?php echo esc_attr( $label ); ?>">Điện thoại</label><input name="phone" value="<?php echo esc_attr( (string) ( $emp['phone'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
					<div><label class="<?php echo esc_attr( $label ); ?>">Website</label><input type="url" name="website" value="<?php echo esc_attr( (string) ( $emp['website'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
					<div><label class="<?php echo esc_attr( $label ); ?>">Địa chỉ</label><input name="address" value="<?php echo esc_attr( (string) ( $emp['address'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input ); ?>"></div>
					<button class="dz-btn" type="submit">Lưu</button>
				</form></section>
			<section class="dz-card space-y-3"><h2 class="dz-h2">Thành viên</h2>
				<ul class="text-sm"><?php foreach ( (array) ( $mem['data']['data'] ?? array() ) as $m ) : ?>
					<li class="flex justify-between gap-2"><span><?php echo esc_html( (string) $m['name'] . ' · ' . $m['email'] ); ?> <?php echo 'owner' === $m['role'] ? '<span class="dz-tag">Chủ tài khoản</span>' : ''; ?></span>
						<?php if ( 'owner' !== $m['role'] && 'owner' === ( $data['member_role'] ?? '' ) ) : ?><?php $form_open( 'member_remove', array( 'member_id' => (int) $m['id'] ) ); ?><button class="text-xs text-red-700">Bớt</button></form><?php endif; ?></li>
				<?php endforeach; ?></ul>
				<?php if ( 'owner' === ( $data['member_role'] ?? '' ) ) : ?>
					<?php $form_open( 'member_add' ); ?><div class="flex gap-2"><input type="email" name="email" required class="<?php echo esc_attr( $input ); ?>" placeholder="Email tài khoản đồng nghiệp"><button class="dz-btn" type="submit">Thêm</button></div></form>
				<?php endif; ?></section>
			<section id="nhan-tin" class="dz-card space-y-3 lg:col-span-2"><h2 class="dz-h2">Nhận quản lý tin hệ thống đã thu thập</h2>
				<p class="text-sm text-ink-muted">Thông báo của đơn vị đã có trên trang (hệ thống tự thu thập từ cổng .gov.vn)? Nhận quản lý để đính chính, gia hạn, cập nhật đợt.</p>
				<form method="get" action="<?php echo esc_url( cvc_employer_url( 'thanh-vien' ) ); ?>#nhan-tin" class="flex gap-2"><input name="q" value="<?php echo esc_attr( $q ); ?>" minlength="3" class="<?php echo esc_attr( $input ); ?>" placeholder="Tìm theo tiêu đề tin"><button class="dz-btn" type="submit">Tìm</button></form>
				<?php if ( $sr && $sr['ok'] ) : ?>
					<ul class="dz-list"><?php foreach ( (array) ( $sr['data']['data'] ?? array() ) as $x ) : ?>
						<li class="flex justify-between gap-2 items-center"><a target="_blank" href="<?php echo esc_url( home_url( '/tuyen-dung/' . rawurlencode( (string) $x['slug'] ) . '/' ) ); ?>"><?php echo esc_html( (string) $x['title'] ); ?></a>
							<?php $form_open( 'claim', array( 'claim_recruitment_id' => (int) $x['id'] ) ); ?><input type="hidden" name="note" value="Thông báo của đơn vị"><button class="dz-btn dz-btn-sm" type="submit">Nhận quản lý</button></form></li>
					<?php endforeach; ?></ul>
				<?php endif; ?>
				<?php if ( ! empty( $cl['data']['data'] ) ) : ?>
					<h3 class="text-sm font-black">Đề nghị đã gửi</h3>
					<ul class="text-sm"><?php foreach ( (array) $cl['data']['data'] as $c ) : ?><li><?php echo esc_html( (string) ( $c['recruitment']['title'] ?? '#' . $c['recruitment_id'] ) ); ?> · <?php echo wp_kses_post( cvc_employer_status_tag( (string) $c['status'] ) ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
			</section>
		</div>
	<?php endif; ?>
	<?php endif; ?>
</div>
</main>
<?php
get_footer();
