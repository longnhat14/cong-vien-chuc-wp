<?php
/**
 * Nhóm ôn thi - /nhom-on-thi/ (nhóm của tôi + tạo nhóm) và /nhom-on-thi/{MA}/ (trang nhóm) - 12/11/2026.
 * Nhóm Zalo / Facebook ôn cùng kỳ thi tạo 1 nhóm ở đây, gửi link mời; xếp hạng Chăm chỉ tuần / tháng trong nhóm.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gp_code  = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) get_query_var( 'cvc_group_code' ) ) );
$gp_token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
$gp_ky    = 'month' === ( $_GET['ky'] ?? '' ) ? 'month' : 'week'; // phpcs:ignore WordPress.Security.NonceVerification
$gp       = array();
if ( '' !== $gp_code ) {
	$gp_res = ( new CVC_Api_Client( null, 8 ) )->get( '/api/groups/' . rawurlencode( $gp_code ), array( 'ky' => $gp_ky ), $gp_token );
	$gp     = ! empty( $gp_res['ok'] ) ? (array) ( $gp_res['data']['data'] ?? array() ) : array();
	if ( ! $gp ) {
		status_header( 404 );
	}
}
$gp_mine = $gp_token ? cvc_growth_api( '/api/groups/mine', array(), $gp_token ) : array();
$gp_here = home_url( '/nhom-on-thi/' . ( '' !== $gp_code ? $gp_code . '/' : '' ) );

cvc_seo_set_noindex( '' !== $gp_code );
cvc_seo_set_title( $gp ? 'Nhóm ôn thi: ' . $gp['name'] : 'Nhóm ôn thi - xếp hạng chăm chỉ cùng bạn bè' );
cvc_seo_set_description( $gp ? 'Nhóm "' . $gp['name'] . '" (' . (int) $gp['members'] . ' thành viên) ôn thi công chức, viên chức trên Công Viên Chức. Tham gia để cùng xếp hạng chăm chỉ.' : 'Tạo nhóm ôn thi công chức, viên chức cho nhóm Zalo / Facebook của bạn: một link mời, bảng xếp hạng chăm chỉ riêng của nhóm theo tuần và tháng.' );
cvc_seo_set_canonical( $gp_here );

$gp_form = static function ( string $op, string $label, string $cls, string $code = '' ): void {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline"><?php wp_nonce_field( 'cvc_group' ); ?><input type="hidden" name="action" value="cvc_group"><input type="hidden" name="op" value="<?php echo esc_attr( $op ); ?>"><input type="hidden" name="code" value="<?php echo esc_attr( $code ); ?>"><button type="submit" class="<?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $label ); ?></button></form>
	<?php
};

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5 gp">
	<style>.gp-head{display:grid;gap:6px}.gp-btn{display:inline-block;padding:10px 18px;border-radius:12px;font-weight:800;border:1px solid #c9d4e0;background:#fff;color:#14263b;cursor:pointer}.gp-btn.is-pri{background:#1767aa;border-color:#1767aa;color:#fff}.gp-table{width:100%;border-collapse:collapse;font-size:15px}.gp-table th{font-size:12px;color:#5c6672;text-align:left;padding:6px 8px}.gp-table td{padding:8px;border-top:1px solid #e3e8ef}.gp-table td.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.gp-list{list-style:none;margin:0;padding:0;display:grid;gap:8px}.gp-list a{font-weight:700;color:#1767aa}.gp-tabs{display:flex;gap:8px}.gp-tabs a{padding:6px 12px;border-radius:10px;border:1px solid #c9d4e0;font-weight:700;font-size:14px}.gp-tabs a.is-on{background:#123a63;border-color:#123a63;color:#fff}.gp-me{background:#fff8e6}</style>
	<?php if ( function_exists( 'cvc_render_notice' ) ) { cvc_render_notice(); } ?>

	<?php if ( '' !== $gp_code && ! $gp ) : ?>
		<div class="dz-panel"><h1 class="text-xl font-black">Không tìm thấy nhóm</h1><p><a class="text-acc font-bold" href="<?php echo esc_url( home_url( '/nhom-on-thi/' ) ); ?>">Về trang Nhóm ôn thi</a></p></div>
	<?php elseif ( $gp ) : ?>
		<?php $gp_invite = add_query_arg( 'ref', (string) $gp['ref'], $gp_here ); ?>
		<header class="dz-panel gp-head">
			<p class="text-xs font-black uppercase tracking-wider text-acc">Nhóm ôn thi · <?php echo (int) $gp['members']; ?> thành viên · lập bởi <?php echo esc_html( (string) $gp['owner'] ); ?></p>
			<h1 class="text-2xl font-black text-ink"><?php echo esc_html( (string) $gp['name'] ); ?></h1>
			<?php if ( ! $gp_token ) : ?>
				<p><a class="gp-btn is-pri" href="<?php echo esc_url( cvc_login_url( $gp_here ) ); ?>">Đăng nhập / đăng ký để tham gia nhóm</a></p>
			<?php elseif ( empty( $gp['is_member'] ) ) : ?>
				<p><?php $gp_form( 'join', 'Tham gia nhóm', 'gp-btn is-pri', $gp_code ); ?></p>
			<?php else : ?>
				<p class="text-sm text-ink-2">Bạn đang ở trong nhóm. Mỗi ngày làm Bài ôn hôm nay hoặc luyện thi là có điểm Chăm chỉ trên bảng của nhóm.</p>
			<?php endif; ?>
		</header>
		<?php if ( ! empty( $gp['is_member'] ) ) : ?>
			<section class="dz-panel">
				<h2 class="text-base font-black text-ink">Mời bạn vào nhóm</h2>
				<p class="text-sm text-ink-2">Gửi link vào nhóm Zalo / Facebook ôn thi của bạn. Người mới đăng ký qua link và bắt đầu ôn thi: cả hai nhận 7 ngày Gói ôn thi đầy đủ.</p>
				<?php cvc_share_buttons( $gp_invite, 'Vào nhóm "' . $gp['name'] . '" ôn thi công chức, viên chức cùng mình - có bảng xếp hạng chăm chỉ của nhóm', 'gp' ); ?>
			</section>
		<?php endif; ?>
		<section class="dz-panel space-y-3">
			<div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-base font-black text-ink"><?php echo esc_html( (string) $gp['title'] ); ?></h2>
				<nav class="gp-tabs"><a class="<?php echo 'week' === $gp_ky ? 'is-on' : ''; ?>" href="<?php echo esc_url( $gp_here ); ?>">Tuần này</a><a class="<?php echo 'month' === $gp_ky ? 'is-on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'ky', 'month', $gp_here ) ); ?>">Tháng này</a></nav></div>
			<?php if ( empty( $gp['items'] ) ) : ?>
				<p class="text-sm text-ink-2">Chưa ai trong nhóm có điểm Chăm chỉ <?php echo 'month' === $gp_ky ? 'tháng' : 'tuần'; ?> này. <a class="text-acc font-bold" href="<?php echo esc_url( home_url( '/luyen-thi-trac-nghiem/' ) ); ?>">Luyện thi ngay</a> để đứng đầu nhóm.</p>
			<?php else : ?>
				<div style="overflow-x:auto"><table class="gp-table"><thead><tr><th>Hạng nhóm</th><th>Thành viên</th><th class="num">Điểm ôn</th><th class="num">Ngày học</th><th class="num">Hạng cả nước</th></tr></thead><tbody>
				<?php foreach ( (array) $gp['items'] as $it ) : ?>
					<tr class="<?php echo ! empty( $it['is_me'] ) ? 'gp-me' : ''; ?>"><td><?php echo (int) $it['group_rank']; ?></td><td><?php echo esc_html( (string) $it['name'] ); ?><?php echo ! empty( $it['province'] ) ? ' <small class="text-ink-muted">' . esc_html( (string) $it['province'] ) . '</small>' : ''; ?></td><td class="num"><?php echo esc_html( number_format_i18n( (float) $it['score'], 1 ) ); ?></td><td class="num"><?php echo (int) $it['exams']; ?></td><td class="num"><?php echo (int) $it['rank']; ?></td></tr>
				<?php endforeach; ?>
				</tbody></table></div>
			<?php endif; ?>
			<p class="text-xs text-ink-muted">Điểm Chăm chỉ tính như <a href="<?php echo esc_url( home_url( '/xep-hang/' ) ); ?>">bảng xếp hạng chung</a>: câu đúng, câu sai đã xem giải thích, Bài ôn hôm nay, câu sai đã thuộc. Tên hiển thị theo cài đặt của từng người.</p>
			<?php if ( ! empty( $gp['is_member'] ) && empty( $gp['is_owner'] ) ) : ?><p><?php $gp_form( 'leave', 'Rời nhóm', 'gp-btn', $gp_code ); ?></p><?php endif; ?>
		</section>
	<?php else : ?>
		<header class="dz-panel gp-head">
			<p class="text-xs font-black uppercase tracking-wider text-acc">Ôn cùng bạn bè</p>
			<h1 class="text-2xl font-black text-ink">Nhóm ôn thi</h1>
			<p class="text-ink-2">Bạn đang ôn trong một nhóm Zalo / Facebook? Tạo nhóm ở đây, gửi một link mời vào nhóm, mọi người cùng có bảng xếp hạng chăm chỉ riêng theo tuần và tháng - xem ai ôn đều nhất.</p>
		</header>
		<?php if ( ! $gp_token ) : ?>
			<div class="dz-panel"><a class="gp-btn is-pri" href="<?php echo esc_url( cvc_login_url( home_url( '/nhom-on-thi/' ) ) ); ?>">Đăng nhập để tạo hoặc tham gia nhóm</a></div>
		<?php else : ?>
			<?php if ( ! empty( $gp_mine['items'] ) ) : ?>
				<section class="dz-panel"><h2 class="text-base font-black text-ink">Nhóm của bạn</h2><ul class="gp-list">
					<?php foreach ( (array) $gp_mine['items'] as $g ) : ?>
						<li><a href="<?php echo esc_url( home_url( '/nhom-on-thi/' . $g['code'] . '/' ) ); ?>"><?php echo esc_html( (string) $g['name'] ); ?></a> <small class="text-ink-muted"><?php echo (int) $g['members']; ?> thành viên<?php echo ! empty( $g['owner'] ) ? ' · bạn lập' : ''; ?></small></li>
					<?php endforeach; ?>
				</ul></section>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dz-panel space-y-2">
				<?php wp_nonce_field( 'cvc_group' ); ?><input type="hidden" name="action" value="cvc_group"><input type="hidden" name="op" value="create">
				<label for="gp-name" class="block text-base font-black text-ink">Tạo nhóm mới</label>
				<input id="gp-name" name="name" required minlength="3" maxlength="80" placeholder="Ví dụ: Ôn thi viên chức giáo dục Hà Nội 2026" class="w-full px-3 py-2 border border-slate-300 rounded-xl">
				<button type="submit" class="gp-btn is-pri">Tạo nhóm</button>
				<p class="text-xs text-ink-muted">Mỗi tài khoản tạo tối đa <?php echo (int) ( $gp_mine['max_owned'] ?? 5 ); ?> nhóm.</p>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>
</main>
<?php
get_footer();
