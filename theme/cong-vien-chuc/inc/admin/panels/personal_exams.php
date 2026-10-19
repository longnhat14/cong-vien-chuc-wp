<?php
/**
 * Panel: Đề cá nhân hóa theo tin (20/10/2026) — công tắc, trạng thái từng nhóm vị trí, văn bản & kho câu, đề đã dựng.
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item  = (array) ( $args['item'] ?? array() );
$id    = (int) ( $item['id'] ?? 0 );
$res   = cvc_admin_api_get( '/api/admin/recruitments/' . $id . '/personal-exams' );
$d     = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
$sett  = cvc_admin_can( 'settings.view' ) ? cvc_admin_api_get( '/api/admin/personal-exams/settings' ) : array( 'ok' => false );
$s     = $sett['ok'] ? (array) ( $sett['data']['data'] ?? array() ) : array();
$edit  = cvc_admin_can( 'recruitment.update' );
$mode  = (string) ( $d['mode'] ?? 'inherit' );
$eff   = (array) ( $d['effective'] ?? array() );
$money = static fn ( $n ) => number_format_i18n( (int) $n ) . ' ₫';
$st    = array(
	'built'      => array( 'Đã dựng', 'bg-emerald-50 text-emerald-800 border-emerald-200' ),
	'generating' => array( 'Đang sinh câu', 'bg-amber-50 text-amber-800 border-amber-200' ),
	'thin'       => array( 'Kho chưa đủ', 'bg-slate-100 text-slate-700 border-slate-300' ),
	'off'        => array( 'Tắt', 'bg-slate-100 text-slate-600 border-slate-300' ),
	'planned'    => array( 'Chờ chạy', 'bg-slate-100 text-slate-700 border-slate-300' ),
);
$tracks = array( 'public_employee' => 'Viên chức', 'civil_servant' => 'Công chức', 'other' => 'Hợp đồng / khác' );
?>
<section class="dz-card space-y-4" id="de-ca-nhan-hoa" aria-labelledby="cvc-pe-h">
	<div class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h2 id="cvc-pe-h" class="text-base font-black text-ink">Đề trắc nghiệm cá nhân hóa cho tin này</h2>
			<p class="text-xs text-ink-muted">Tự dựng tối đa 3 đề 60 câu/60 phút (hoặc đề rút gọn 30 câu) theo nhóm vị trí, công khai ngay. Tin sau trùng bộ văn bản sẽ nhân bản mẫu đề, không tốn AI.</p>
		</div>
		<span class="text-[11px] font-bold px-2 py-0.5 rounded-full border <?php echo ! empty( $eff['enabled'] ) ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-300'; ?>"><?php echo esc_html( ( ! empty( $eff['enabled'] ) ? 'Đang bật' : 'Đang tắt' ) . ' · ' . (string) ( $eff['reason'] ?? '' ) ); ?></span>
	</div>

	<?php if ( ! $res['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></p>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-center gap-3">
			<?php wp_nonce_field( 'cvc_admin_personal_exams' ); ?>
			<input type="hidden" name="action" value="cvc_admin_personal_exams">
			<input type="hidden" name="op" value="mode">
			<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
			<fieldset class="flex flex-wrap gap-2 text-sm" <?php disabled( ! $edit ); ?>>
				<legend class="sr-only">Công tắc tự tạo đề cho tin này</legend>
				<?php foreach ( array( 'inherit' => 'Theo mặc định', 'on' => 'Luôn bật', 'off' => 'Luôn tắt' ) as $k => $lbl ) : ?>
					<label class="flex items-center gap-2 px-3 py-1.5 rounded-xl border <?php echo $k === $mode ? 'border-acc bg-acc-soft font-bold text-acc' : 'border-slate-300 bg-white text-ink'; ?>">
						<input type="radio" name="mode" value="<?php echo esc_attr( $k ); ?>" class="accent-acc-2" <?php checked( $k, $mode ); ?>> <?php echo esc_html( $lbl ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<?php if ( $edit ) : ?>
				<button type="submit" class="px-4 py-2 bg-acc hover:bg-acc-deep text-white font-black text-sm rounded-xl">Lưu công tắc</button>
			<?php endif; ?>
			<span class="text-xs text-ink-muted"><?php echo esc_html( 'Ngân sách AI mỗi tin ' . $money( $d['budget'] ?? 0 ) . ' · đã dùng ' . $money( $d['spent'] ?? 0 ) ); ?></span>
		</form>

		<?php foreach ( (array) ( $d['plans'] ?? array() ) as $p ) : ?>
			<?php $badge = $st[ (string) ( $p['status'] ?? 'planned' ) ] ?? $st['planned']; ?>
			<div class="rounded-xl border border-slate-300 p-3 space-y-2">
				<div class="flex flex-wrap items-center justify-between gap-2">
					<p class="font-bold text-ink text-sm"><?php echo esc_html( (string) ( $p['group_label'] ?: 'Cả tin' ) ); ?> <span class="text-ink-muted font-normal">· hồ sơ <?php echo esc_html( (string) ( $p['profile_key'] ?: 'kiến thức chung' ) ); ?></span></p>
					<span class="text-[11px] font-bold px-2 py-0.5 rounded-full border <?php echo esc_attr( $badge[1] ); ?>"><?php echo esc_html( $badge[0] ); ?></span>
				</div>
				<?php if ( ! empty( $p['message'] ) ) : ?><p class="text-xs text-ink-2"><?php echo esc_html( (string) $p['message'] ); ?></p><?php endif; ?>
				<p class="text-xs text-ink-muted"><?php echo esc_html( implode( ' · ', array_filter( array( ! empty( $p['template_id'] ) ? 'Mẫu đề #' . (int) $p['template_id'] . ( 'exact' === $p['template_match'] ? ' (nhân bản)' : ( 'near' === $p['template_match'] ? ' (từ mẫu gần trùng)' : ' (mẫu mới)' ) ) : null, 'Chi phí ' . $money( $p['cost_spent'] ?? 0 ) . ( ! empty( $p['cost_estimate'] ) ? ' / ước tính ' . $money( $p['cost_estimate'] ) : '' ) ) ) ) ); ?></p>
				<?php if ( ! empty( $p['exams'] ) ) : ?>
					<ul class="text-sm space-y-1">
						<?php foreach ( (array) $p['exams'] as $e ) : ?>
							<li class="flex flex-wrap items-center gap-2">
								<a class="text-acc font-semibold hover:underline" href="<?php echo esc_url( cvc_admin_url( 'exams', (int) $e['id'] ) ); ?>"><?php echo esc_html( (string) $e['title'] ); ?></a>
								<span class="text-xs text-ink-muted"><?php echo esc_html( (int) $e['total_questions'] . ' câu · ' . (int) $e['duration_minutes'] . ' phút · ' . ( 'published' === $e['status'] ? 'công khai' : 'ẩn' ) ); ?></span>
								<?php if ( 'published' === $e['status'] ) : ?><a class="text-xs text-ink-muted hover:text-acc" href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>" target="_blank" rel="noopener">Xem ↗</a><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<details class="text-xs">
					<summary class="cursor-pointer text-ink-2 font-bold">Văn bản, tỷ trọng và kho câu</summary>
					<?php foreach ( (array) ( $p['sections'] ?? array() ) as $sec ) : ?>
						<p class="mt-2 font-bold text-ink"><?php echo esc_html( (string) $sec['label'] . ' · ' . (int) $sec['weight'] . '%' ); ?></p>
						<ul class="mt-1 grid sm:grid-cols-2 gap-x-4">
							<?php foreach ( (array) $sec['documents'] as $doc ) : ?>
								<li class="<?php echo (int) $doc['questions'] < 20 ? 'text-amber-800' : 'text-ink-2'; ?>"><?php echo esc_html( $doc['number'] . ' — ' . (int) $doc['questions'] . ' câu' ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
					<?php if ( ! empty( $p['positions'] ) ) : ?>
						<p class="mt-2 text-ink-muted"><?php echo esc_html( 'Vị trí: ' . implode( '; ', (array) $p['positions'] ) ); ?></p>
					<?php endif; ?>
				</details>
			</div>
		<?php endforeach; ?>

		<?php if ( $edit ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-center gap-3">
				<?php wp_nonce_field( 'cvc_admin_personal_exams' ); ?>
				<input type="hidden" name="action" value="cvc_admin_personal_exams">
				<input type="hidden" name="op" value="run">
				<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
				<button type="submit" class="px-4 py-2 bg-white hover:bg-acc-soft border border-slate-300 text-ink font-bold text-sm rounded-xl">Chạy dựng đề ngay</button>
				<label class="text-xs text-ink-muted flex items-center gap-1"><input type="checkbox" name="force" value="1" class="accent-acc-2"> Dựng lại dù đã có đề</label>
			</form>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $s ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="border-t border-slate-300 pt-3 space-y-2">
			<?php wp_nonce_field( 'cvc_admin_personal_exams' ); ?>
			<input type="hidden" name="action" value="cvc_admin_personal_exams">
			<input type="hidden" name="op" value="settings">
			<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
			<p class="text-sm font-bold text-ink">Phạm vi chung (áp dụng cho mọi tin để "Theo mặc định")</p>
			<fieldset class="flex flex-wrap items-center gap-3 text-sm" <?php disabled( ! cvc_admin_can( 'settings.update' ) ); ?>>
				<legend class="sr-only">Loại tin tự tạo đề</legend>
				<?php foreach ( $tracks as $k => $lbl ) : ?>
					<label class="flex items-center gap-2"><input type="checkbox" name="scope[]" value="<?php echo esc_attr( $k ); ?>" class="accent-acc-2" <?php checked( in_array( $k, (array) ( $s['scope'] ?? array() ), true ) ); ?>> <?php echo esc_html( $lbl ); ?></label>
				<?php endforeach; ?>
				<label class="flex items-center gap-2 text-xs text-ink-muted">Ngân sách/tin <input type="number" name="budget" min="0" step="1000" value="<?php echo (int) ( $s['budget'] ?? 20000 ); ?>" class="w-28 px-2 py-1 rounded-lg border border-slate-300"> ₫</label>
			</fieldset>
			<p class="text-sm font-bold text-ink pt-2">Thu phí</p>
			<fieldset class="flex flex-wrap items-center gap-3 text-sm" <?php disabled( ! cvc_admin_can( 'settings.update' ) ); ?>>
				<legend class="sr-only">Thu phí đề cá nhân hóa</legend>
				<label class="flex items-center gap-2"><input type="checkbox" name="paywall" value="1" class="accent-acc-2" <?php checked( '0' !== (string) ( $s['paywall'] ?? '1' ) ); ?>> Thu phí (làm thử 10 câu)</label>
				<label class="flex items-center gap-2 text-xs text-ink-muted">Bộ đề theo tin <input type="number" name="price_pack" min="0" step="1000" value="<?php echo (int) ( $s['prices']['pack'] ?? 29000 ); ?>" class="w-28 px-2 py-1 rounded-lg border border-slate-300"> ₫</label>
				<label class="flex items-center gap-2 text-xs text-ink-muted">Gói 30 ngày <input type="number" name="price_pass" min="0" step="1000" value="<?php echo (int) ( $s['prices']['pass'] ?? 79000 ); ?>" class="w-28 px-2 py-1 rounded-lg border border-slate-300"> ₫</label>
				<?php if ( cvc_admin_can( 'settings.update' ) ) : ?>
					<button type="submit" class="px-3 py-1.5 bg-white hover:bg-acc-soft border border-slate-300 text-ink font-bold text-xs rounded-lg">Lưu cấu hình</button>
				<?php endif; ?>
			</fieldset>
			<p class="text-xs <?php echo ! empty( $s['paywall_active'] ) ? 'text-emerald-800' : 'text-amber-800'; ?>"><?php echo esc_html( ! empty( $s['paywall_active'] ) ? 'Đang thu phí. Đã bán ' . (int) ( $s['sales']['orders'] ?? 0 ) . ' gói, doanh thu ' . $money( $s['sales']['revenue'] ?? 0 ) . '.' : ( '0' === (string) ( $s['paywall'] ?? '1' ) ? 'Đang tắt thu phí: mọi người làm đủ đề miễn phí.' : 'Chưa thu phí vì cổng VNPay chưa cấu hình (Quản trị → Thanh toán). Đề đang mở miễn phí; thu phí tự bật khi VNPay sẵn sàng.' ) ); ?></p>
			<p class="text-xs text-ink-muted"><?php echo esc_html( 'Tin xét tuyển và tin đã có danh mục ôn tập chính thức được bỏ qua (trừ khi bật riêng). Hạn mức tháng dùng chung với lộ trình ôn tự động: đã dùng ' . $money( $s['month_spent'] ?? 0 ) . ' / ' . $money( $s['monthly_cap'] ?? 0 ) . ' · ' . (int) ( $s['templates'] ?? 0 ) . ' mẫu đề, ' . (int) ( $s['reuses'] ?? 0 ) . ' lần nhân bản.' ); ?></p>
		</form>
	<?php endif; ?>
</section>
