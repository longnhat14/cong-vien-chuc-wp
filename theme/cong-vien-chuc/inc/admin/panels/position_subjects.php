<?php
/**
 * Panel: môn thi của vị trí tuyển dụng (PUT /api/admin/positions/{id}/exam-subjects).
 * Dữ liệu này quyết định hồ sơ ôn thi và gợi ý khóa học/đề thi theo vị trí.
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item     = (array) ( $args['item'] ?? array() );
$id       = (int) ( $item['id'] ?? 0 );
$current  = cvc_admin_api_get( '/api/admin/positions/' . $id . '/exam-subjects' );
$assigned = array();
foreach ( (array) ( $current['data']['data'] ?? array() ) as $subject ) {
	$assigned[ (int) $subject['id'] ] = ! isset( $subject['pivot']['is_required'] ) || (bool) $subject['pivot']['is_required'];
}
$all      = cvc_admin_relation_options( 'exam-subjects' );
$can_edit = cvc_admin_can( 'recruitment.update' );
?>
<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-3" aria-labelledby="cvc-position-subjects">
	<h2 id="cvc-position-subjects" class="text-base font-black text-white">Môn thi của vị trí</h2>
	<p class="text-xs text-slate-400">Dùng để dựng hồ sơ ôn thi ở trang tuyển dụng và gợi ý khóa học, đề thi phù hợp.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-3">
		<?php wp_nonce_field( 'cvc_admin_position_subjects' ); ?>
		<input type="hidden" name="action" value="cvc_admin_position_subjects">
		<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
		<fieldset class="grid grid-cols-1 sm:grid-cols-2 gap-2" <?php disabled( ! $can_edit ); ?>>
			<legend class="sr-only">Chọn môn thi</legend>
			<?php foreach ( $all as $sid => $label ) : ?>
				<div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-950 border border-slate-700 text-sm">
					<label class="flex items-center gap-2 text-slate-200">
						<input type="checkbox" name="subjects[]" value="<?php echo (int) $sid; ?>" class="accent-amber-500" <?php checked( isset( $assigned[ $sid ] ) ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
					<label class="flex items-center gap-1 text-[11px] text-slate-400">
						<input type="checkbox" name="optional[]" value="<?php echo (int) $sid; ?>" class="accent-slate-400" <?php checked( isset( $assigned[ $sid ] ) && ! $assigned[ $sid ] ); ?>> Tự chọn
					</label>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<?php if ( $can_edit ) : ?>
			<button type="submit" class="px-4 py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black text-sm rounded-xl">Lưu môn thi</button>
		<?php endif; ?>
	</form>
</section>
