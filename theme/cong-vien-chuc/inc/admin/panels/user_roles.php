<?php
/**
 * Panel: thông tin người dùng + phân vai trò (PUT /api/admin/users/{id}/roles).
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item     = (array) ( $args['item'] ?? array() );
$roles    = cvc_admin_api_get( '/api/admin/roles' );
$current  = (array) ( $item['roles'] ?? array() );
$stats    = (array) ( $item['stats'] ?? array() );
$can_edit = cvc_admin_can( 'user.update' );
$me       = cvc_current_user();
$is_super = in_array( 'SUPER_ADMIN', (array) ( $me['roles'] ?? array() ), true );
$labels   = array(
	'SUPER_ADMIN' => 'Toàn quyền hệ thống, gồm cả phân quyền.',
	'ADMIN'       => 'Quản trị vận hành: nội dung, tuyển dụng, đơn hàng, cấu hình.',
	'EDITOR'      => 'Biên tập nội dung và dữ liệu.',
	'REVIEWER'    => 'Kiểm duyệt nội dung trước khi xuất bản.',
	'INSTRUCTOR'  => 'Quản lý khóa học và bài giảng.',
	'USER'        => 'Học viên.',
);
?>
<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-4" aria-labelledby="cvc-user-info">
	<h2 id="cvc-user-info" class="text-base font-black text-white">Thông tin tài khoản</h2>
	<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Email</dt><dd class="text-white"><?php echo esc_html( (string) ( $item['email'] ?? '' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Ngày tạo</dt><dd class="text-white"><?php echo esc_html( cvc_admin_format( $item['created_at'] ?? null, 'datetime' ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Lượt làm bài</dt><dd class="text-white tabular-nums"><?php echo esc_html( number_format_i18n( (int) ( $stats['exam_attempts'] ?? 0 ) ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Khóa học đã ghi danh</dt><dd class="text-white tabular-nums"><?php echo esc_html( number_format_i18n( (int) ( $stats['course_enrollments'] ?? 0 ) ) ); ?></dd></div>
		<div class="flex justify-between gap-3 border-b border-slate-800 py-1"><dt class="text-slate-400">Đơn đã thanh toán</dt><dd class="text-white tabular-nums"><?php echo esc_html( number_format_i18n( (int) ( $stats['orders_paid'] ?? 0 ) ) ); ?></dd></div>
	</dl>

	<h2 class="text-base font-black text-white pt-2">Vai trò</h2>
	<?php if ( ! $roles['ok'] ) : ?>
		<?php cvc_render_error_state( cvc_admin_error_message( $roles ) ); ?>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-3" data-cvc-confirm="Cập nhật vai trò cho tài khoản này?">
			<?php wp_nonce_field( 'cvc_admin_user_roles' ); ?>
			<input type="hidden" name="action" value="cvc_admin_user_roles">
			<input type="hidden" name="id" value="<?php echo (int) ( $item['id'] ?? 0 ); ?>">
			<fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-2" <?php disabled( ! $can_edit ); ?>>
				<legend class="sr-only">Chọn vai trò</legend>
				<?php foreach ( (array) ( $roles['data']['data'] ?? array() ) as $role ) : ?>
					<?php
					$name     = (string) $role['name'];
					$locked   = 'SUPER_ADMIN' === $name && ! $is_super;
					?>
					<label class="flex items-start gap-2 p-3 rounded-xl bg-slate-950 border border-slate-700 text-sm <?php echo $locked ? 'opacity-60' : ''; ?>">
						<input type="checkbox" name="roles[]" value="<?php echo esc_attr( $name ); ?>" class="mt-1 accent-amber-500" <?php checked( in_array( $name, $current, true ) ); ?> <?php disabled( $locked ); ?>>
						<?php if ( $locked && in_array( $name, $current, true ) ) : ?>
							<input type="hidden" name="roles[]" value="<?php echo esc_attr( $name ); ?>">
						<?php endif; ?>
						<span>
							<strong class="text-white"><?php echo esc_html( (string) ( $role['display_name'] ?? $name ) ); ?></strong>
							<span class="block text-xs text-slate-400"><?php echo esc_html( $labels[ $name ] ?? (string) ( $role['description'] ?? '' ) ); ?> · <?php echo esc_html( (int) ( $role['permissions_count'] ?? 0 ) ); ?> quyền</span>
						</span>
					</label>
				<?php endforeach; ?>
			</fieldset>
			<?php if ( $can_edit ) : ?>
				<button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-sm rounded-xl">Lưu vai trò</button>
			<?php else : ?>
				<p class="text-xs text-slate-400">Bạn chỉ có quyền xem.</p>
			<?php endif; ?>
		</form>
	<?php endif; ?>
</section>
