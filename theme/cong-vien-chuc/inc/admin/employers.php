<?php
/**
 * Quản trị → Đơn vị tuyển dụng (21/10/2026): /quan-tri/don-vi/?xem=hang-cho|don-vi|cai-dat
 * Biên tập viên (employer.review): duyệt tài khoản, tin, đính chính, cập nhật đợt, nhận quản lý tin, báo sai.
 * Quản lý (employer.manage): cài đặt miễn phí / thu phí + giá, ngoài công lập, quyền tự đăng, khóa, bỏ ghim.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_admin_employer_url( string $tab = 'hang-cho', array $args = array() ): string {
	return cvc_admin_url( 'don-vi', null, array_merge( array( 'xem' => $tab ), $args ) );
}

function cvc_admin_employer_form( string $op, array $hidden = array(), string $class = 'inline' ): void {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="' . esc_attr( $class ) . '">';
	wp_nonce_field( 'cvc_admin_employer' );
	echo '<input type="hidden" name="action" value="cvc_admin_employer"><input type="hidden" name="op" value="' . esc_attr( $op ) . '">';
	foreach ( $hidden as $k => $v ) {
		echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '">';
	}
}

function cvc_admin_render_employers(): void {
	$tab = sanitize_key( wp_unslash( $_GET['xem'] ?? 'hang-cho' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array( 'hang-cho' => 'Hàng chờ duyệt', 'don-vi' => 'Đơn vị' );
	if ( cvc_admin_can( 'employer.manage' ) ) {
		$tabs['cai-dat'] = 'Cài đặt phí & quyền';
	}
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Đơn vị tuyển dụng</h1>
		<p class="text-sm text-ink-muted">Đơn vị tự đăng tin trên <a class="underline" href="<?php echo esc_url( cvc_employer_landing_url() ); ?>" target="_blank">/don-vi-tuyen-dung/</a>. Tin vẫn qua đủ kiểm tra như tin thu thập (chống trùng, tiêu đề, tỉnh, hạn nộp).</p>
	</header>
	<nav class="flex flex-wrap gap-2 text-sm">
		<?php foreach ( $tabs as $k => $l ) : ?>
			<a class="px-3 py-1.5 rounded-lg border <?php echo $k === $tab ? 'bg-acc text-white border-acc' : 'bg-white border-slate-300'; ?>" href="<?php echo esc_url( cvc_admin_employer_url( $k ) ); ?>"><?php echo esc_html( $l ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
	if ( 'don-vi' === $tab ) {
		$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$id ? cvc_admin_render_employer_detail( $id ) : cvc_admin_render_employer_list();
	} elseif ( 'cai-dat' === $tab && cvc_admin_can( 'employer.manage' ) ) {
		cvc_admin_render_employer_settings();
	} else {
		cvc_admin_render_employer_queue();
	}
}

function cvc_admin_render_employer_queue(): void {
	$res = cvc_admin_api_get( '/api/admin/employer-queue' );
	if ( ! $res['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $res ) );
		return;
	}
	$d      = (array) ( $res['data']['data'] ?? array() );
	$c      = (array) ( $d['counts'] ?? array() );
	$review = cvc_admin_can( 'employer.review' );
	$btn    = 'px-2.5 py-1 rounded-lg text-xs font-bold border';
	$inp    = 'px-2 py-1 rounded border border-slate-300 text-xs';
	?>
	<dl class="grid grid-cols-2 md:grid-cols-6 gap-2">
		<?php foreach ( array( 'employers' => 'Đơn vị chờ xác minh', 'posts' => 'Tin chờ duyệt', 'changes' => 'Đính chính', 'updates' => 'Cập nhật đợt', 'claims' => 'Nhận quản lý tin', 'reports' => 'Báo sai' ) as $k => $l ) : ?>
			<div class="dz-card"><dt class="text-[11px] font-bold uppercase text-ink-muted"><?php echo esc_html( $l ); ?></dt><dd class="text-2xl font-black"><?php echo (int) ( $c[ $k ] ?? 0 ); ?></dd></div>
		<?php endforeach; ?>
	</dl>
	<?php if ( (int) ( $c['employers'] ?? 0 ) > 0 ) : ?>
		<p class="text-sm"><a class="font-bold text-acc" href="<?php echo esc_url( cvc_admin_employer_url( 'don-vi', array( 'status' => 'pending' ) ) ); ?>">Xem <?php echo (int) $c['employers']; ?> đơn vị chờ xác minh &rarr;</a></p>
	<?php endif; ?>

	<section class="dz-card space-y-2"><h2 class="font-black">Tin chờ duyệt</h2>
		<?php if ( empty( $d['posts'] ) ) : ?><p class="text-sm text-ink-muted">Không có.</p><?php endif; ?>
		<?php foreach ( (array) ( $d['posts'] ?? array() ) as $p ) : ?>
			<div class="border-t pt-2 text-sm flex flex-wrap gap-2 items-center justify-between">
				<div><b><?php echo esc_html( (string) ( $p['title'] ?: 'Bản nhập #' . $p['id'] ) ); ?></b><br><span class="text-ink-muted"><?php echo esc_html( (string) ( $p['employer']['name'] ?? '' ) ); ?> · gửi <?php echo esc_html( ! empty( $p['submitted_at'] ) ? wp_date( 'd/m H:i', strtotime( (string) $p['submitted_at'] ) ) : '' ); ?><?php echo ! empty( $p['note'] ) ? ' · ' . esc_html( (string) $p['note'] ) : ''; ?></span></div>
				<div class="flex gap-2 items-center">
					<a class="<?php echo esc_attr( $btn ); ?>" href="<?php echo esc_url( cvc_imports_url( (int) $p['id'] ) ); ?>">Xem trước / sửa</a>
					<?php if ( $review ) : ?>
						<?php cvc_admin_employer_form( 'post_approve', array( 'import_id' => (int) $p['id'] ) ); ?><button class="<?php echo esc_attr( $btn ); ?> bg-acc text-white">Duyệt &amp; đăng</button></form>
						<?php cvc_admin_employer_form( 'post_reject', array( 'import_id' => (int) $p['id'] ), 'inline-flex gap-1' ); ?><input name="note" required minlength="5" class="<?php echo esc_attr( $inp ); ?>" placeholder="Cần bổ sung gì?"><button class="<?php echo esc_attr( $btn ); ?>">Trả lại</button></form>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="dz-card space-y-2"><h2 class="font-black">Đính chính / gia hạn chờ duyệt</h2>
		<?php if ( empty( $d['changes'] ) ) : ?><p class="text-sm text-ink-muted">Không có.</p><?php endif; ?>
		<?php foreach ( (array) ( $d['changes'] ?? array() ) as $ch ) : ?>
			<div class="border-t pt-2 text-sm">
				<b><?php echo esc_html( (string) ( $ch['recruitment']['title'] ?? '#' . $ch['recruitment_id'] ) ); ?></b> — <?php echo esc_html( (string) ( $ch['employer']['name'] ?? '' ) ); ?>
				<ul class="ml-4 list-disc"><?php foreach ( (array) $ch['changes'] as $f => $v ) : ?><li><?php echo esc_html( $f . ': ' . ( is_scalar( $ch['recruitment'][ $f ] ?? null ) ? (string) $ch['recruitment'][ $f ] : '—' ) . ' → ' . ( is_scalar( $v ) ? (string) $v : '' ) ); ?></li><?php endforeach; ?></ul>
				<p class="text-ink-muted">Lý do: <?php echo esc_html( (string) ( $ch['note'] ?? '' ) ); ?></p>
				<?php if ( $review ) : ?>
					<?php cvc_admin_employer_form( 'change_review', array( 'id' => (int) $ch['id'], 'decision' => 'approve' ) ); ?><button class="<?php echo esc_attr( $btn ); ?> bg-acc text-white">Áp dụng</button></form>
					<?php cvc_admin_employer_form( 'change_review', array( 'id' => (int) $ch['id'], 'decision' => 'reject' ), 'inline-flex gap-1' ); ?><input name="note" class="<?php echo esc_attr( $inp ); ?>" placeholder="Lý do từ chối"><button class="<?php echo esc_attr( $btn ); ?>">Từ chối</button></form>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="dz-card space-y-2"><h2 class="font-black">Văn bản cập nhật đợt chờ duyệt</h2>
		<?php if ( empty( $d['updates'] ) ) : ?><p class="text-sm text-ink-muted">Không có.</p><?php endif; ?>
		<?php foreach ( (array) ( $d['updates'] ?? array() ) as $u ) : ?>
			<div class="border-t pt-2 text-sm flex flex-wrap justify-between gap-2">
				<div><b><?php echo esc_html( (string) $u['title'] ); ?></b> (<?php echo esc_html( (string) $u['kind_label'] ); ?>)<br><span class="text-ink-muted"><?php echo esc_html( (string) ( $u['employer']['name'] ?? '' ) . ' · tin: ' . ( $u['recruitment']['title'] ?? '' ) ); ?></span>
					· <a class="underline" href="<?php echo esc_url( cvc_admin_employer_file_url( 'update', (int) $u['id'] ) ); ?>" target="_blank" rel="noopener">Xem tệp</a></div>
				<?php if ( $review ) : ?><div class="flex gap-2">
					<?php cvc_admin_employer_form( 'update_review', array( 'id' => (int) $u['id'], 'decision' => 'approve' ) ); ?><button class="<?php echo esc_attr( $btn ); ?> bg-acc text-white">Đăng</button></form>
					<?php cvc_admin_employer_form( 'update_review', array( 'id' => (int) $u['id'], 'decision' => 'reject' ), 'inline-flex gap-1' ); ?><input name="note" class="<?php echo esc_attr( $inp ); ?>" placeholder="Lý do"><button class="<?php echo esc_attr( $btn ); ?>">Từ chối</button></form>
				</div><?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="dz-card space-y-2"><h2 class="font-black">Đề nghị nhận quản lý tin</h2>
		<?php if ( empty( $d['claims'] ) ) : ?><p class="text-sm text-ink-muted">Không có.</p><?php endif; ?>
		<?php foreach ( (array) ( $d['claims'] ?? array() ) as $cl ) : ?>
			<div class="border-t pt-2 text-sm flex flex-wrap justify-between gap-2">
				<div><b><?php echo esc_html( (string) ( $cl['employer']['name'] ?? '' ) ); ?></b> (<?php echo esc_html( (string) ( $cl['employer']['official_email'] ?? $cl['employer']['website'] ?? '' ) ); ?>) nhận tin
					<a class="underline" target="_blank" href="<?php echo esc_url( home_url( '/tuyen-dung/' . rawurlencode( (string) ( $cl['recruitment']['slug'] ?? '' ) ) . '/' ) ); ?>"><?php echo esc_html( (string) ( $cl['recruitment']['title'] ?? '' ) ); ?></a></div>
				<?php if ( $review ) : ?><div class="flex gap-2">
					<?php cvc_admin_employer_form( 'claim_review', array( 'id' => (int) $cl['id'], 'decision' => 'approve' ) ); ?><button class="<?php echo esc_attr( $btn ); ?> bg-acc text-white">Đồng ý</button></form>
					<?php cvc_admin_employer_form( 'claim_review', array( 'id' => (int) $cl['id'], 'decision' => 'reject' ), 'inline-flex gap-1' ); ?><input name="note" class="<?php echo esc_attr( $inp ); ?>" placeholder="Lý do"><button class="<?php echo esc_attr( $btn ); ?>">Từ chối</button></form>
				</div><?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>

	<section class="dz-card space-y-2"><h2 class="font-black">Báo sai thông tin</h2>
		<?php
		$by = array();
		foreach ( (array) ( $d['reports'] ?? array() ) as $r ) {
			$k = (int) ( $r['recruitment']['id'] ?? 0 );
			$by[ $k ]['r']   = $r['recruitment'];
			$by[ $k ]['l'][] = $r;
		}
		?>
		<?php if ( empty( $by ) ) : ?><p class="text-sm text-ink-muted">Không có.</p><?php endif; ?>
		<?php foreach ( $by as $rid => $g ) : ?>
			<div class="border-t pt-2 text-sm">
				<a class="font-bold underline" target="_blank" href="<?php echo esc_url( home_url( '/tuyen-dung/' . rawurlencode( (string) ( $g['r']['slug'] ?? '' ) ) . '/' ) ); ?>"><?php echo esc_html( (string) ( $g['r']['title'] ?? '#' . $rid ) ); ?></a>
				<?php echo 'review' === ( $g['r']['status'] ?? '' ) ? ' <span class="dz-tag is-warn">Đang tạm ẩn</span>' : ''; ?> — <?php echo count( $g['l'] ); ?> báo cáo
				<ul class="ml-4 list-disc"><?php foreach ( $g['l'] as $x ) : ?><li><?php echo esc_html( (string) $x['reason_label'] . ( ! empty( $x['note'] ) ? ': ' . $x['note'] : '' ) ); ?></li><?php endforeach; ?></ul>
				<?php if ( $review ) : ?>
					<?php cvc_admin_employer_form( 'reports', array( 'recruitment_id' => $rid, 'decision' => 'resolved' ) ); ?><button class="<?php echo esc_attr( $btn ); ?>">Đã sửa tin</button></form>
					<?php cvc_admin_employer_form( 'reports', array( 'recruitment_id' => $rid, 'decision' => 'dismissed', 'republish' => 'review' === ( $g['r']['status'] ?? '' ) ? 1 : 0 ) ); ?><button class="<?php echo esc_attr( $btn ); ?>">Báo nhầm<?php echo 'review' === ( $g['r']['status'] ?? '' ) ? ' – hiện lại tin' : ''; ?></button></form>
					<a class="<?php echo esc_attr( $btn ); ?>" href="<?php echo esc_url( cvc_admin_url( 'recruitments', $rid ) ); ?>">Sửa tin</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>
	<?php
}

function cvc_admin_render_employer_list(): void {
	$status = sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$res    = cvc_admin_api_get( '/api/admin/employers', array_filter( array( 'status' => $status ) ) );
	if ( ! $res['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $res ) );
		return;
	}
	?>
	<nav class="flex gap-2 text-xs"><?php foreach ( array( '' => 'Tất cả', 'pending' => 'Chờ xác minh', 'verified' => 'Đã xác minh', 'rejected' => 'Bị từ chối', 'suspended' => 'Tạm khóa' ) as $k => $l ) : ?>
		<a class="px-2 py-1 rounded border <?php echo $k === $status ? 'bg-acc-soft font-bold' : ''; ?>" href="<?php echo esc_url( cvc_admin_employer_url( 'don-vi', array_filter( array( 'status' => $k ) ) ) ); ?>"><?php echo esc_html( $l ); ?></a>
	<?php endforeach; ?></nav>
	<div class="dz-tablewrap"><table class="dz-table text-sm"><thead><tr><th>Đơn vị</th><th>Loại</th><th>Tỉnh</th><th>Xác minh</th><th>Tin</th><th>Trạng thái</th></tr></thead><tbody>
		<?php foreach ( (array) ( $res['data']['data'] ?? array() ) as $e ) : ?>
			<tr><td><a class="font-bold" href="<?php echo esc_url( cvc_admin_employer_url( 'don-vi', array( 'id' => (int) $e['id'] ) ) ); ?>"><?php echo esc_html( (string) $e['name'] ); ?></a></td>
				<td><?php echo 'private' === $e['sector'] ? 'Ngoài công lập' : 'Công lập'; ?></td><td><?php echo esc_html( (string) ( $e['province'] ?? '' ) ); ?></td>
				<td><?php echo esc_html( 'email' === $e['verification_method'] ? (string) $e['official_email'] : ( 'document' === $e['verification_method'] ? 'Giấy giới thiệu' : '—' ) ); ?></td>
				<td><?php echo (int) $e['posts_published']; ?><?php echo 'auto' === $e['trust'] ? ' <span class="dz-tag is-ok">tự đăng</span>' : ''; ?></td>
				<td><?php echo wp_kses_post( cvc_employer_status_tag( (string) $e['status'] ) ); ?></td></tr>
		<?php endforeach; ?>
	</tbody></table></div>
	<?php
}

function cvc_admin_render_employer_detail( int $id ): void {
	$res = cvc_admin_api_get( '/api/admin/employers/' . $id );
	if ( ! $res['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $res ) );
		return;
	}
	$d   = (array) ( $res['data']['data'] ?? array() );
	$e   = (array) ( $d['employer'] ?? array() );
	$btn = 'px-3 py-1.5 rounded-lg text-sm font-bold border';
	$inp = 'px-2 py-1.5 rounded border border-slate-300 text-sm';
	?>
	<section class="dz-card space-y-2 text-sm">
		<h2 class="text-lg font-black"><?php echo esc_html( (string) $e['name'] ); ?> <?php echo wp_kses_post( cvc_employer_status_tag( (string) $e['status'] ) ); ?></h2>
		<dl class="dz-kv">
			<dt>Loại hình</dt><dd><?php echo 'private' === ( $e['sector'] ?? '' ) ? 'Ngoài công lập · MST ' . esc_html( (string) ( $e['tax_code'] ?? '' ) ) : 'Công lập'; ?></dd>
			<dt>Tỉnh</dt><dd><?php echo esc_html( (string) ( $e['province']['name'] ?? '' ) ); ?></dd>
			<dt>Điện thoại</dt><dd><?php echo esc_html( (string) ( $e['phone'] ?? '' ) ); ?></dd>
			<dt>Website</dt><dd><?php echo esc_html( (string) ( $e['website'] ?? '' ) ); ?></dd>
			<dt>Email công vụ</dt><dd><?php echo esc_html( (string) ( $e['official_email'] ?? '—' ) ); ?></dd>
			<dt>Giấy tờ</dt><dd><?php if ( ! empty( $e['document_name'] ) ) : ?><a class="underline" target="_blank" rel="noopener" href="<?php echo esc_url( cvc_admin_employer_file_url( 'doc', $id ) ); ?>"><?php echo esc_html( (string) $e['document_name'] ); ?></a><?php else : ?>—<?php endif; ?></dd>
			<dt>Tin đã đăng / bị trả lại</dt><dd><?php echo (int) ( $e['posts_published'] ?? 0 ); ?> / <?php echo (int) ( $e['posts_rejected'] ?? 0 ); ?></dd>
			<dt>Quyền đăng</dt><dd><?php echo 'auto' === ( $e['trust'] ?? '' ) ? 'Tự đăng (hậu kiểm)' : 'Qua duyệt'; ?></dd>
		</dl>
		<div class="flex flex-wrap gap-2 pt-2">
			<?php if ( cvc_admin_can( 'employer.review' ) ) : ?>
				<?php cvc_admin_employer_form( 'emp_status', array( 'id' => $id, 'status' => 'verified' ) ); ?><button class="<?php echo esc_attr( $btn ); ?> bg-acc text-white">Xác minh (đã gọi xác nhận)</button></form>
				<?php cvc_admin_employer_form( 'emp_status', array( 'id' => $id, 'status' => 'rejected' ), 'inline-flex gap-1' ); ?><input name="note" class="<?php echo esc_attr( $inp ); ?>" placeholder="Lý do"><button class="<?php echo esc_attr( $btn ); ?>">Từ chối</button></form>
			<?php endif; ?>
			<?php if ( cvc_admin_can( 'employer.manage' ) ) : ?>
				<?php cvc_admin_employer_form( 'emp_trust', array( 'id' => $id, 'trust' => 'auto' === ( $e['trust'] ?? '' ) ? 'review' : 'auto' ) ); ?><button class="<?php echo esc_attr( $btn ); ?>"><?php echo 'auto' === ( $e['trust'] ?? '' ) ? 'Thu hồi quyền tự đăng' : 'Cấp quyền tự đăng'; ?></button></form>
				<?php cvc_admin_employer_form( 'emp_status', array( 'id' => $id, 'status' => 'suspended' ), 'inline-flex gap-1' ); ?><input name="note" class="<?php echo esc_attr( $inp ); ?>" placeholder="Lý do khóa"><button class="<?php echo esc_attr( $btn ); ?> text-red-700">Tạm khóa</button></form>
			<?php endif; ?>
		</div>
	</section>
	<section class="dz-card text-sm"><h3 class="font-black mb-1">Thành viên</h3>
		<ul><?php foreach ( (array) ( $d['members'] ?? array() ) as $m ) : ?><li><?php echo esc_html( (string) ( $m['user']['name'] ?? '' ) . ' · ' . ( $m['user']['email'] ?? '' ) . ' · ' . $m['role'] ); ?></li><?php endforeach; ?></ul></section>
	<section class="dz-card text-sm"><h3 class="font-black mb-1">Tin</h3>
		<ul><?php foreach ( (array) ( $d['recruitments'] ?? array() ) as $r ) : ?><li><a class="underline" href="<?php echo esc_url( cvc_admin_url( 'recruitments', (int) $r['id'] ) ); ?>"><?php echo esc_html( (string) $r['title'] ); ?></a> · <?php echo esc_html( (string) $r['status'] ); ?>
			<?php if ( ! empty( $r['pinned_until'] ) && strtotime( (string) $r['pinned_until'] ) > time() && cvc_admin_can( 'employer.manage' ) ) : ?> · <?php cvc_admin_employer_form( 'unpin', array( 'recruitment_id' => (int) $r['id'], 'id' => $id ) ); ?><button class="text-xs underline">Bỏ ghim</button></form><?php endif; ?></li><?php endforeach; ?></ul></section>
	<?php
}

function cvc_admin_render_employer_settings(): void {
	$res = cvc_admin_api_get( '/api/admin/employer-settings' );
	if ( ! $res['ok'] ) {
		cvc_render_error_state( cvc_admin_error_message( $res ) );
		return;
	}
	$v   = (array) ( $res['data']['data']['values'] ?? array() );
	$inp = cvc_admin_input_class();
	$sw  = static function ( string $key, string $label, string $help ) use ( $v ): void {
		$k = str_replace( 'employer.', '', $key );
		echo '<label class="flex items-start gap-3 py-2"><input type="hidden" name="employer[' . esc_attr( $k ) . ']" value="0"><input type="checkbox" class="mt-1 accent-acc-2" name="employer[' . esc_attr( $k ) . ']" value="1" ' . checked( '1', (string) ( $v[ $key ] ?? '0' ), false ) . '><span><b>' . esc_html( $label ) . '</b><br><span class="text-xs text-ink-muted">' . esc_html( $help ) . '</span></span></label>';
	};
	$num = static function ( string $key, string $label, string $suffix = '' ) use ( $v, $inp ): void {
		$k = str_replace( 'employer.', '', $key );
		echo '<label class="block"><span class="block text-sm font-bold mb-1">' . esc_html( $label ) . '</span><span class="flex items-center gap-2"><input type="number" min="0" class="' . esc_attr( $inp ) . '" name="employer[' . esc_attr( $k ) . ']" value="' . esc_attr( (string) ( $v[ $key ] ?? '0' ) ) . '">' . esc_html( $suffix ) . '</span></label>';
	};
	?>
	<?php cvc_admin_employer_form( 'settings', array(), 'dz-card space-y-4 block' ); ?>
		<h2 class="font-black">Bật / tắt</h2>
		<?php
		$sw( 'employer.enabled', 'Mở cổng đơn vị tuyển dụng', 'Tắt: không nhận đăng ký và tin mới (tin đã đăng vẫn hiển thị).' );
		$sw( 'employer.private_enabled', 'Nhận đơn vị ngoài công lập', 'Tin ngoài công lập hiển thị ở mục riêng, không trộn với tin công chức, viên chức.' );
		$sw( 'employer.paid_enabled', 'Thu phí (bật) / Miễn phí (tắt)', 'Khi tắt, mọi dịch vụ miễn phí (giới hạn: 1 tin ghim / đơn vị, 1 lần email / tin). Khi bật, áp dụng bảng giá dưới, thanh toán qua VNPay / SePay.' );
		?>
		<h2 class="font-black">Bảng giá (áp dụng khi bật thu phí)</h2>
		<div class="grid md:grid-cols-3 gap-3">
			<?php $num( 'employer.price_post', 'Phí đăng 1 tin', '₫' ); ?>
			<?php $num( 'employer.price_pin', 'Phí ghim 1 gói', '₫' ); ?>
			<?php $num( 'employer.pin_days', 'Số ngày mỗi gói ghim', 'ngày' ); ?>
			<?php $num( 'employer.price_email', 'Phí 1 đợt gửi email', '₫' ); ?>
			<?php $num( 'employer.email_cap', 'Tối đa người nhận / đợt', 'người' ); ?>
		</div>
		<h2 class="font-black">Kiểm duyệt</h2>
		<div class="grid md:grid-cols-2 gap-3">
			<?php $num( 'employer.trust_threshold', 'Số tin đăng chuẩn để được tự đăng (0 = không tự cấp)', 'tin' ); ?>
			<?php $num( 'employer.report_threshold', 'Số báo sai để tự tạm ẩn tin của đơn vị', 'lần' ); ?>
		</div>
		<button class="px-5 py-2.5 bg-acc text-white font-black text-sm rounded-xl">Lưu cài đặt</button>
	</form>
	<p class="text-sm text-ink-muted">Vai trò <b>Biên tập viên</b> (duyệt tài khoản, tin, đính chính, báo sai) gán ở Quản trị → Người dùng → vai trò "Biên tập viên".</p>
	<?php
}

/* ------------------------------------------------------------------ xử lý form */

add_action( 'admin_post_cvc_admin_employer', 'cvc_admin_handle_employer' );
add_action( 'admin_post_nopriv_cvc_admin_employer', 'cvc_admin_handle_employer' );

function cvc_admin_handle_employer(): void {
	check_admin_referer( 'cvc_admin_employer' );
	$token  = cvc_admin_require_staff();
	$client = cvc_admin_client();
	$op     = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );
	$id     = absint( $_POST['id'] ?? 0 );
	$back   = wp_get_referer() ?: cvc_admin_employer_url();
	$note   = sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) );
	$dec    = 'reject' === sanitize_key( wp_unslash( $_POST['decision'] ?? '' ) ) ? 'reject' : 'approve';
	$res    = null;

	switch ( $op ) {
		case 'post_approve':
			$res = $client->post( '/api/admin/employer-posts/' . absint( $_POST['import_id'] ?? 0 ) . '/approve', array(), $token );
			break;
		case 'post_reject':
			$res = $client->post( '/api/admin/employer-posts/' . absint( $_POST['import_id'] ?? 0 ) . '/reject', array( 'note' => $note ), $token );
			break;
		case 'change_review':
			$res = $client->post( '/api/admin/employer-changes/' . $id, array( 'decision' => $dec, 'note' => $note ?: null ), $token );
			break;
		case 'update_review':
			$res = $client->post( '/api/admin/employer-updates/' . $id, array( 'decision' => $dec, 'note' => $note ?: null ), $token );
			break;
		case 'claim_review':
			$res = $client->post( '/api/admin/employer-claims/' . $id, array( 'decision' => $dec, 'note' => $note ?: null ), $token );
			break;
		case 'reports':
			$res = $client->post( '/api/admin/recruitment-reports/' . absint( $_POST['recruitment_id'] ?? 0 ), array(
				'decision'  => 'dismissed' === sanitize_key( wp_unslash( $_POST['decision'] ?? '' ) ) ? 'dismissed' : 'resolved',
				'republish' => ! empty( $_POST['republish'] ),
			), $token );
			break;
		case 'emp_status':
			$res = $client->post( '/api/admin/employers/' . $id . '/status', array( 'status' => sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ), 'note' => $note ?: null ), $token );
			break;
		case 'emp_trust':
			$res = $client->post( '/api/admin/employers/' . $id . '/trust', array( 'trust' => 'auto' === sanitize_key( wp_unslash( $_POST['trust'] ?? '' ) ) ? 'auto' : 'review' ), $token );
			break;
		case 'unpin':
			$res = $client->post( '/api/admin/recruitments/' . absint( $_POST['recruitment_id'] ?? 0 ) . '/unpin', array(), $token );
			break;
		case 'settings':
			$vals = array();
			foreach ( (array) wp_unslash( $_POST['employer'] ?? array() ) as $k => $val ) {
				$vals[ sanitize_key( (string) $k ) ] = absint( is_array( $val ) ? end( $val ) : $val );
			}
			$res = $client->put( '/api/admin/employer-settings', array( 'employer' => $vals ), $token );
			delete_transient( 'cvc_employer_public_settings' );
			break;
	}
	if ( null === $res ) {
		cvc_admin_redirect( $back, 'error', 'Thao tác không hợp lệ.' );
		return;
	}
	cvc_admin_redirect( $back, $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Đã lưu.' ) : (string) ( $res['data']['message'] ?? cvc_admin_error_message( $res ) ) );
}

/** Link xem tệp (giấy giới thiệu / văn bản cập nhật) qua token của quản trị viên. */
function cvc_admin_employer_file_url( string $kind, int $id ): string {
	return wp_nonce_url( add_query_arg( array( 'action' => 'cvc_admin_employer_file', 'kind' => $kind, 'id' => $id ), admin_url( 'admin-post.php' ) ), 'cvc_admin_employer_file' );
}

add_action( 'admin_post_cvc_admin_employer_file', 'cvc_admin_handle_employer_file' );
add_action( 'admin_post_nopriv_cvc_admin_employer_file', 'cvc_admin_handle_employer_file' );

function cvc_admin_handle_employer_file(): void {
	check_admin_referer( 'cvc_admin_employer_file' );
	$token = cvc_admin_require_staff();
	$id    = absint( $_GET['id'] ?? 0 );
	$path  = 'doc' === sanitize_key( wp_unslash( $_GET['kind'] ?? '' ) ) ? '/api/admin/employers/' . $id . '/document' : '/api/admin/employer-updates/' . $id . '/download';
	$resp  = wp_remote_get( cvc_api_base_url() . $path, array( 'timeout' => 60, 'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Accept' => '*/*' ) ) );
	if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
		wp_die( esc_html__( 'Không tải được tệp.', 'cvc' ), '', array( 'response' => 404 ) );
	}
	nocache_headers();
	header( 'Content-Type: ' . ( wp_remote_retrieve_header( $resp, 'content-type' ) ?: 'application/octet-stream' ) );
	$disp = wp_remote_retrieve_header( $resp, 'content-disposition' );
	if ( $disp ) {
		header( 'Content-Disposition: ' . str_replace( 'attachment', 'inline', (string) $disp ) );
	}
	header( 'X-Content-Type-Options: nosniff' );
	echo wp_remote_retrieve_body( $resp ); // phpcs:ignore WordPress.Security.EscapeOutput -- tệp nhị phân.
	exit;
}
