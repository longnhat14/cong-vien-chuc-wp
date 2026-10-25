<?php
/**
 * Quản trị → Thu thập → Báo câu sai (25/10/2026).
 * Người làm bài báo câu hỏi sai đáp án / mơ hồ / lỗi thời / lỗi chính tả (trang kết quả thi).
 * Từ 3 người khác nhau báo → câu tự tạm ẩn (đề cá nhân hóa tự thay câu khi bảo trì).
 * API: GET /api/admin/question-reports, POST /api/admin/question-reports/{question}/resolve.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_qr_url( string $status = 'open', int $page = 1 ): string {
	$args = array( 'xem' => 'bao-cau-sai' );
	if ( 'open' !== $status ) {
		$args['tt'] = $status;
	}
	if ( $page > 1 ) {
		$args['trang'] = $page;
	}

	return cvc_admin_url( 'thu-thap', null, $args );
}

function cvc_admin_render_question_reports(): void {
	$status = sanitize_key( (string) ( $_GET['tt'] ?? 'open' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$status = in_array( $status, array( 'open', 'resolved', 'rejected' ), true ) ? $status : 'open';
	$page   = max( 1, absint( $_GET['trang'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$res    = cvc_admin_api_get( '/api/admin/question-reports', array( 'status' => $status, 'page' => $page, 'per_page' => 20 ) );
	$data   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$items  = (array) ( $data['items'] ?? array() );
	$counts = (array) ( $data['counts'] ?? array() );
	$pg     = (array) ( $data['pagination'] ?? array() );
	$can    = cvc_admin_can( 'question.update' );
	$labels = array( 'open' => 'Đang chờ', 'resolved' => 'Đã xử lý', 'rejected' => 'Câu đúng (bác bỏ)' );
	?>
	<header>
		<h1 class="text-2xl font-black text-ink">Báo câu sai</h1>
		<p class="text-sm text-ink-muted">Người làm bài báo câu hỏi có vấn đề ở trang kết quả thi. Từ 3 người khác nhau cùng báo, câu được tạm ẩn khỏi kho; đề cá nhân hóa tự thay câu khác ở lượt bảo trì hằng giờ.</p>
	</header>
	<?php cvc_ingestion_tabs( 'bao-cau-sai' ); ?>
	<?php if ( ! $res['ok'] ) : ?>
		<div class="dz-card text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $res ) ); ?></div>
		<?php return; ?>
	<?php endif; ?>
	<nav class="flex flex-wrap gap-2 text-sm">
		<?php foreach ( $labels as $k => $l ) : ?>
			<a href="<?php echo esc_url( cvc_qr_url( $k ) ); ?>" class="px-3 py-1.5 rounded-xl font-bold border <?php echo $k === $status ? 'bg-acc-soft text-acc border-acc-line' : 'bg-white text-ink-2 border-slate-300'; ?>"><?php echo esc_html( $l . ' (' . (int) ( $counts[ $k ] ?? 0 ) . ')' ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php if ( empty( $items ) ) : ?>
		<div class="dz-card text-sm text-ink-muted">Không có câu nào trong mục này.</div>
	<?php endif; ?>
	<?php foreach ( $items as $it ) : ?>
		<?php $q = (array) ( $it['question'] ?? array() ); ?>
		<article class="dz-card space-y-3">
			<div class="flex flex-wrap items-center justify-between gap-2 text-xs">
				<span class="font-bold text-ink">Câu #<?php echo (int) $it['question_id']; ?> · <?php echo (int) $it['reporters']; ?> người báo</span>
				<span class="text-ink-muted">Trạng thái câu: <b><?php echo esc_html( (string) ( $q['status'] ?? '?' ) ); ?></b><?php echo ! empty( $q['difficulty'] ) ? ' · ' . esc_html( (string) $q['difficulty'] ) : ''; ?><?php echo ! empty( $q['source_reference'] ) ? ' · ' . esc_html( (string) $q['source_reference'] ) : ''; ?></span>
			</div>
			<p class="font-bold text-ink"><?php echo esc_html( (string) ( $q['text'] ?? '(câu đã bị xóa)' ) ); ?></p>
			<ul class="text-sm space-y-1">
				<?php foreach ( (array) ( $q['options'] ?? array() ) as $o ) : ?>
					<li class="<?php echo ! empty( $o['correct'] ) ? 'font-bold text-acc' : 'text-ink-2'; ?>"><?php echo esc_html( $o['key'] . '. ' . $o['text'] ); ?><?php echo ! empty( $o['correct'] ) ? ' ✓' : ''; ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $q['source_excerpt'] ) ) : ?>
				<p class="text-xs text-ink-muted"><b>Trích căn cứ:</b> “<?php echo esc_html( (string) $q['source_excerpt'] ); ?>”</p>
			<?php endif; ?>
			<?php if ( ! empty( $q['explanation'] ) ) : ?>
				<p class="text-xs text-ink-muted"><b>Giải thích:</b> <?php echo esc_html( (string) $q['explanation'] ); ?></p>
			<?php endif; ?>
			<div class="border-t border-slate-200 pt-2 space-y-1 text-sm">
				<?php foreach ( (array) ( $it['reports'] ?? array() ) as $r ) : ?>
					<p><b><?php echo esc_html( (string) $r['reason_label'] ); ?></b><?php echo ! empty( $r['note'] ) ? ' — ' . esc_html( (string) $r['note'] ) : ''; ?> <span class="text-xs text-ink-muted">(<?php echo esc_html( (string) ( $r['user'] ?? 'ẩn danh' ) ); ?>, <?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( (string) $r['created_at'] ) ) ); ?>)</span><?php echo ! empty( $r['admin_note'] ) ? '<br><span class="text-xs text-acc">Phản hồi: ' . esc_html( (string) $r['admin_note'] ) . '</span>' : ''; ?></p>
				<?php endforeach; ?>
			</div>
			<?php if ( $can && 'open' === $status ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap items-end gap-2 text-sm">
					<?php wp_nonce_field( 'cvc_admin_qreport' ); ?>
					<input type="hidden" name="action" value="cvc_admin_qreport">
					<input type="hidden" name="question_id" value="<?php echo (int) $it['question_id']; ?>">
					<label class="flex-1 min-w-[220px]"><span class="block text-xs text-ink-muted">Ghi chú xử lý</span><input type="text" name="admin_note" maxlength="2000" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>"></label>
					<button type="submit" name="op" value="hide" class="px-3 py-2 rounded-xl bg-red-700 text-white font-bold">Câu sai — ẩn câu</button>
					<button type="submit" name="op" value="fixed" class="px-3 py-2 rounded-xl bg-acc text-white font-bold">Đã sửa — giữ câu</button>
					<button type="submit" name="op" value="reject" class="px-3 py-2 rounded-xl border border-slate-300 bg-white font-bold">Câu đúng — bác bỏ<?php echo 'published' !== ( $q['status'] ?? '' ) ? ' & hiện lại' : ''; ?></button>
				</form>
				<p class="text-xs text-ink-muted">Sửa nội dung câu tại Quản trị → Câu hỏi #<?php echo (int) $it['question_id']; ?> (hoặc trong đề), rồi chọn "Đã sửa — giữ câu".</p>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
	<?php if ( (int) ( $pg['last_page'] ?? 1 ) > 1 ) : ?>
		<nav class="flex justify-between text-sm">
			<?php echo $page > 1 ? '<a class="text-acc font-bold" href="' . esc_url( cvc_qr_url( $status, $page - 1 ) ) . '">← Trang trước</a>' : '<span></span>'; ?>
			<span class="text-ink-muted">Trang <?php echo (int) $page; ?> / <?php echo (int) $pg['last_page']; ?></span>
			<?php echo $page < (int) $pg['last_page'] ? '<a class="text-acc font-bold" href="' . esc_url( cvc_qr_url( $status, $page + 1 ) ) . '">Trang sau →</a>' : '<span></span>'; ?>
		</nav>
	<?php endif; ?>
	<?php
}

add_action( 'admin_post_cvc_admin_qreport', 'cvc_admin_handle_qreport' );
add_action( 'admin_post_nopriv_cvc_admin_qreport', 'cvc_admin_handle_qreport' );

function cvc_admin_handle_qreport(): void {
	check_admin_referer( 'cvc_admin_qreport' );
	$token = cvc_admin_require_staff();
	$back  = cvc_qr_url();
	if ( ! cvc_admin_can( 'question.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền xử lý câu hỏi.' );
		return;
	}
	$qid = absint( $_POST['question_id'] ?? 0 );
	$op  = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$map = array(
		'hide'   => array( 'resolved', 'draft' ),
		'fixed'  => array( 'resolved', 'published' ),
		'reject' => array( 'rejected', 'published' ),
	);
	if ( ! $qid || ! isset( $map[ $op ] ) ) {
		cvc_admin_redirect( $back, 'error', 'Thao tác không hợp lệ.' );
		return;
	}
	$result = ( new CVC_Api_Client( null, 30 ) )->post(
		'/api/admin/question-reports/' . $qid . '/resolve',
		array(
			'decision'        => $map[ $op ][0],
			'question_status' => $map[ $op ][1],
			'admin_note'      => sanitize_text_field( wp_unslash( (string) ( $_POST['admin_note'] ?? '' ) ) ),
		),
		$token
	);
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}
	cvc_admin_redirect( $back, 'success', (string) ( $result['data']['message'] ?? 'Đã xử lý.' ) );
}
