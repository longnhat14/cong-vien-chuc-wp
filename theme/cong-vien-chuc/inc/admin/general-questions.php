<?php
/**
 * Duyệt câu mảng "Năng lực tư duy" và "Xã hội, văn hóa, lịch sử" (02/11/2026) - /quan-tri/thu-thap/?xem=cau-moi.
 * AI soạn nháp + model độc lập kiểm tra; biên tập viên sửa / duyệt / loại. Chỉ câu đã duyệt mới đến người ôn.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_gq_url( string $kind = 'tu-duy', string $status = 'draft', int $page = 1 ): string {
	$args = array( 'xem' => 'cau-moi', 'mang' => $kind );
	if ( 'draft' !== $status ) {
		$args['tt'] = $status;
	}
	if ( $page > 1 ) {
		$args['trang'] = $page;
	}

	return cvc_admin_url( 'thu-thap', null, $args );
}

function cvc_admin_render_general_questions(): void {
	$kind   = sanitize_key( (string) ( $_GET['mang'] ?? 'tu-duy' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$kind   = in_array( $kind, array( 'tu-duy', 'xh' ), true ) ? $kind : 'tu-duy';
	$status = sanitize_key( (string) ( $_GET['tt'] ?? 'draft' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$status = in_array( $status, array( 'draft', 'published', 'archived' ), true ) ? $status : 'draft';
	$page   = max( 1, absint( $_GET['trang'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$res    = cvc_admin_api_get( '/api/admin/general-questions', array( 'kind' => $kind, 'status' => $status, 'page' => $page ) );
	$d      = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$can    = cvc_admin_can( 'question.update' );
	$cang   = cvc_admin_can( 'question.create' );
	$kinds  = array( 'tu-duy' => 'Năng lực tư duy', 'xh' => 'Xã hội, văn hóa, lịch sử' );
	$sts    = array( 'draft' => 'Chờ duyệt', 'published' => 'Đã đăng', 'archived' => 'Đã loại' );
	$counts = (array) ( $d['counts'] ?? array() );
	$diff   = array( 'easy' => 'Dễ', 'medium' => 'Vừa', 'hard' => 'Khó' );
	$in     = cvc_admin_input_class();
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-ink">Câu tư duy & xã hội - văn hóa - lịch sử</h1>
		<p class="text-sm text-ink-muted max-w-3xl">Vòng 1 thi tuyển công chức có cả phần năng lực tư duy và kiến thức xã hội, văn hóa, lịch sử (Điều 9 Nghị định 170/2025/NĐ-CP). Câu ở đây do AI soạn, một model khác (deepseek-v4-pro) tự giải lại để đánh dấu câu lệch đáp án. <strong>Không câu nào tự đăng</strong>: chỉ câu bạn bấm "Duyệt & đăng" mới vào Bài ôn hôm nay và các đề.</p>
	</header>
	<?php cvc_ingestion_tabs( 'cau-moi' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<div class="dz-card text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></div>
		<?php return; ?>
	<?php endif; ?>
	<nav class="flex flex-wrap gap-2 text-sm" aria-label="Mảng">
		<?php foreach ( $kinds as $k => $l ) : ?>
			<?php $c = (array) ( $counts[ $k ] ?? array() ); ?>
			<a href="<?php echo esc_url( cvc_gq_url( $k ) ); ?>" class="px-3 py-1.5 rounded-xl font-bold border <?php echo $k === $kind ? 'bg-acc text-white border-acc' : 'bg-white text-ink-2 border-slate-300'; ?>"<?php echo $k === $kind ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $l ); ?> <span class="font-normal">(<?php echo (int) ( $c['draft'] ?? 0 ); ?> chờ · <?php echo (int) ( $c['published'] ?? 0 ); ?> đã đăng)</span></a>
		<?php endforeach; ?>
	</nav>
	<nav class="flex flex-wrap gap-2 text-sm" aria-label="Trạng thái">
		<?php foreach ( $sts as $k => $l ) : ?>
			<a href="<?php echo esc_url( cvc_gq_url( $kind, $k ) ); ?>" class="px-3 py-1 rounded-lg border <?php echo $k === $status ? 'bg-acc-soft text-acc border-acc-line font-bold' : 'bg-white text-ink-2 border-slate-300'; ?>"><?php echo esc_html( $l . ' (' . (int) ( $counts[ $kind ][ $k ] ?? 0 ) . ')' ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php if ( $cang ) : ?>
		<div class="dz-card flex flex-wrap items-center gap-3 text-sm">
			<?php if ( ! empty( $d['running'] ) ) : ?>
				<p class="text-acc font-bold">Đang soạn <?php echo (int) ( $d['running']['count'] ?? 0 ); ?> câu mảng <?php echo esc_html( $kinds[ $d['running']['kind'] ?? '' ] ?? '' ); ?>… tải lại trang sau 1-2 phút.</p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-center gap-2">
					<?php wp_nonce_field( 'cvc_admin_gq' ); ?>
					<input type="hidden" name="action" value="cvc_admin_gq"><input type="hidden" name="op" value="generate"><input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
					<label for="gq-count">Soạn thêm</label>
					<select id="gq-count" name="count" class="<?php echo esc_attr( $in ); ?> w-auto"><option value="5">5</option><option value="10" selected>10</option><option value="15">15</option></select>
					<span>câu nháp mảng <?php echo esc_html( $kinds[ $kind ] ); ?></span>
					<button type="submit" class="px-3 py-2 rounded-xl bg-acc text-white font-bold">Soạn bằng AI</button>
				</form>
			<?php endif; ?>
			<?php if ( ! empty( $d['last'] ) ) : ?>
				<?php $l = (array) $d['last']; ?>
				<p class="text-xs text-ink-muted">Lượt gần nhất (<?php echo esc_html( $kinds[ $l['kind'] ?? '' ] ?? '' ); ?>, <?php echo esc_html( wp_date( 'd/m H:i', strtotime( (string) ( $l['at'] ?? 'now' ) ) ) ); ?>): <?php echo (int) ( $l['created'] ?? 0 ); ?> câu, model kiểm tra đồng ý <?php echo (int) ( $l['agree'] ?? 0 ); ?><?php echo ! empty( $l['error'] ) ? ' · ' . esc_html( (string) $l['error'] ) : ''; ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( empty( $d['questions'] ) ) : ?>
		<div class="dz-card text-sm text-ink-muted">Không có câu nào trong mục này.</div>
	<?php endif; ?>
	<?php foreach ( (array) ( $d['questions'] ?? array() ) as $q ) : ?>
		<?php
		$v      = (array) ( $q['validation'] ?? array() );
		$right  = '';
		foreach ( (array) $q['options'] as $o ) {
			if ( ! empty( $o['correct'] ) ) {
				$right = (string) $o['key'];
			}
		}
		$agree  = ! empty( $v['agree'] );
		$qid    = (int) $q['id'];
		?>
		<article class="dz-card space-y-3" id="cau-<?php echo $qid; ?>">
			<div class="flex flex-wrap items-center gap-2 text-xs">
				<span class="font-bold text-ink">Câu #<?php echo $qid; ?></span>
				<span class="px-2 py-0.5 rounded-full border <?php echo $agree ? 'border-emerald-300 text-emerald-800 bg-emerald-50' : 'border-red-300 text-red-700 bg-red-50'; ?> font-bold"><?php echo $agree ? 'Model kiểm tra đồng ý' : ( ( $v['validator_answer'] ?? '' ) === $right ? 'Cần xem kỹ: model kiểm tra cho là chưa rõ ràng' : 'Cần xem kỹ: model kiểm tra chọn ' . esc_html( (string) ( $v['validator_answer'] ?? '—' ) ) ); ?></span>
				<?php if ( ! empty( $v['subtype'] ) ) : ?><span class="text-ink-muted"><?php echo esc_html( (string) $v['subtype'] ); ?></span><?php endif; ?>
				<span class="text-ink-muted"><?php echo esc_html( $diff[ $q['difficulty'] ] ?? (string) $q['difficulty'] ); ?></span>
				<?php if ( ! empty( $v['edited'] ) ) : ?><span class="text-acc font-bold">đã sửa</span><?php endif; ?>
			</div>
			<p class="font-bold text-ink whitespace-pre-line"><?php echo esc_html( (string) $q['text'] ); ?></p>
			<ul class="grid sm:grid-cols-2 gap-1 text-sm">
				<?php foreach ( (array) $q['options'] as $o ) : ?>
					<li class="px-2 py-1 rounded-lg <?php echo ! empty( $o['correct'] ) ? 'bg-acc-soft text-emerald-800 font-bold' : ( ( $v['validator_answer'] ?? '' ) === $o['key'] && ! $agree ? 'bg-red-50 text-red-700' : 'text-ink-2' ); ?>"><?php echo esc_html( $o['key'] . '. ' . $o['text'] ); ?><?php echo ! empty( $o['correct'] ) ? ' ✓' : ''; ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $q['explanation'] ) ) : ?><p class="text-sm text-ink-2"><b>Giải thích:</b> <?php echo esc_html( (string) $q['explanation'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $v['source_hint'] ) ) : ?><p class="text-xs text-ink-muted"><b>Gợi ý nguồn kiểm chứng:</b> <?php echo esc_html( (string) $v['source_hint'] ); ?></p><?php endif; ?>
			<?php if ( ! empty( $v['issue'] ) ) : ?><p class="text-xs text-red-700"><b>Model kiểm tra ghi chú:</b> <?php echo esc_html( (string) $v['issue'] ); ?></p><?php endif; ?>
			<?php if ( $can && 'draft' === $status ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-2 border-t border-slate-200 pt-3">
					<?php wp_nonce_field( 'cvc_admin_gq' ); ?>
					<input type="hidden" name="action" value="cvc_admin_gq"><input type="hidden" name="qid" value="<?php echo $qid; ?>"><input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
					<details class="text-sm">
						<summary class="cursor-pointer font-bold text-acc">Sửa trước khi duyệt</summary>
						<div class="mt-2 space-y-2">
							<label class="block" for="gq-t-<?php echo $qid; ?>"><span class="block text-xs text-ink-muted">Câu hỏi</span>
								<textarea id="gq-t-<?php echo $qid; ?>" name="text" rows="3" maxlength="1200" class="<?php echo esc_attr( $in ); ?>"><?php echo esc_textarea( (string) $q['text'] ); ?></textarea></label>
							<?php foreach ( (array) $q['options'] as $o ) : ?>
								<div class="flex items-center gap-2">
									<label class="inline-flex items-center gap-1 text-xs font-bold whitespace-nowrap"><input type="radio" name="answer" value="<?php echo esc_attr( $o['key'] ); ?>" <?php checked( $right, $o['key'] ); ?>> Đúng <?php echo esc_html( $o['key'] ); ?></label>
									<input type="text" name="options[<?php echo esc_attr( $o['key'] ); ?>]" value="<?php echo esc_attr( (string) $o['text'] ); ?>" maxlength="500" aria-label="Phương án <?php echo esc_attr( $o['key'] ); ?>" class="<?php echo esc_attr( $in ); ?>">
								</div>
							<?php endforeach; ?>
							<label class="block" for="gq-e-<?php echo $qid; ?>"><span class="block text-xs text-ink-muted">Giải thích</span>
								<textarea id="gq-e-<?php echo $qid; ?>" name="explanation" rows="3" maxlength="3000" class="<?php echo esc_attr( $in ); ?>"><?php echo esc_textarea( (string) $q['explanation'] ); ?></textarea></label>
						</div>
					</details>
					<div class="flex flex-wrap gap-2">
						<button type="submit" name="op" value="approve" class="px-3 py-2 rounded-xl bg-acc text-white font-bold">Duyệt & đăng</button>
						<button type="submit" name="op" value="save" class="px-3 py-2 rounded-xl border border-slate-300 bg-white font-bold">Lưu sửa (chưa đăng)</button>
						<button type="submit" name="op" value="reject" class="px-3 py-2 rounded-xl border border-red-300 text-red-700 bg-white font-bold">Loại</button>
					</div>
				</form>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
	<?php if ( (int) ( $d['pages'] ?? 1 ) > 1 ) : ?>
		<nav class="flex justify-between text-sm">
			<?php echo $page > 1 ? '<a class="text-acc font-bold" href="' . esc_url( cvc_gq_url( $kind, $status, $page - 1 ) ) . '">← Trang trước</a>' : '<span></span>'; ?>
			<span class="text-ink-muted">Trang <?php echo (int) $page; ?> / <?php echo (int) $d['pages']; ?></span>
			<?php echo $page < (int) $d['pages'] ? '<a class="text-acc font-bold" href="' . esc_url( cvc_gq_url( $kind, $status, $page + 1 ) ) . '">Trang sau →</a>' : '<span></span>'; ?>
		</nav>
	<?php endif; ?>
	<?php
}

add_action( 'admin_post_cvc_admin_gq', 'cvc_admin_handle_gq' );
add_action( 'admin_post_nopriv_cvc_admin_gq', 'cvc_admin_handle_gq' );

function cvc_admin_handle_gq(): void {
	check_admin_referer( 'cvc_admin_gq' );
	$token = cvc_admin_require_staff();
	$kind  = sanitize_key( (string) ( $_POST['kind'] ?? 'tu-duy' ) );
	$back  = cvc_gq_url( in_array( $kind, array( 'tu-duy', 'xh' ), true ) ? $kind : 'tu-duy' );
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	if ( 'generate' === $op ) {
		$r = cvc_admin_client()->post( '/api/admin/general-questions/generate', array( 'kind' => $kind, 'count' => absint( $_POST['count'] ?? 10 ) ), $token );
		cvc_admin_redirect( $back, $r['ok'] ? 'success' : 'error', (string) ( $r['data']['message'] ?? cvc_admin_error_message( $r ) ) );
		return;
	}
	if ( ! in_array( $op, array( 'approve', 'save', 'reject' ), true ) ) {
		cvc_admin_redirect( $back, 'error', 'Thao tác không hợp lệ.' );
		return;
	}
	$opts = array();
	foreach ( (array) wp_unslash( $_POST['options'] ?? array() ) as $k => $t ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( in_array( $k, array( 'A', 'B', 'C', 'D' ), true ) ) {
			$opts[ $k ] = sanitize_text_field( (string) $t );
		}
	}
	$body = array(
		'action'      => $op,
		'text'        => sanitize_textarea_field( wp_unslash( (string) ( $_POST['text'] ?? '' ) ) ),
		'options'     => $opts,
		'explanation' => sanitize_textarea_field( wp_unslash( (string) ( $_POST['explanation'] ?? '' ) ) ),
	);
	$ans = strtoupper( sanitize_text_field( wp_unslash( (string) ( $_POST['answer'] ?? '' ) ) ) );
	if ( in_array( $ans, array( 'A', 'B', 'C', 'D' ), true ) ) {
		$body['answer'] = $ans;
	}
	$body = array_filter( $body, static fn ( $x ) => '' !== $x && array() !== $x );
	$r    = cvc_admin_client()->post( '/api/admin/general-questions/' . absint( $_POST['qid'] ?? 0 ) . '/review', $body, $token );
	cvc_admin_redirect( $back, $r['ok'] ? 'success' : 'error', (string) ( $r['data']['message'] ?? cvc_admin_error_message( $r ) ) );
}
