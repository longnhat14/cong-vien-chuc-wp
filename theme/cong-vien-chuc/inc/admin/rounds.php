<?php
/**
 * Quản trị — Hàng chờ "Văn bản chưa xác định đợt tuyển" (Phase 16, 18/10/2026).
 * URL: /quan-tri/thu-thap/?xem=dot
 *
 * Nguồn dữ liệu: GET /api/admin/ingestion/round-queue
 *  - văn bản vòng đời (event_pending) chưa gắn vào đợt nào;
 *  - đợt chỉ có 1 văn bản, chưa có tin (có thể thuộc 1 đợt khác vì thiếu số hiệu trích dẫn).
 * Mỗi mục kèm đợt gợi ý (cùng nguồn / cùng tỉnh, tiêu đề tương tự, cùng năm) và bằng chứng.
 * Gắn / tạo đợt mới / bỏ qua: POST /api/admin/ingestion/items/{id}/round (link_method=manual,
 * không bị đồng bộ tự động ghi đè).
 *
 * @package CongVienChuc
 */

defined( 'ABSPATH' ) || exit;

function cvc_admin_render_round_queue(): void {
	$result  = cvc_admin_api_get( '/api/admin/ingestion/round-queue' );
	$data    = (array) ( $result['data']['data'] ?? array() );
	$items   = (array) ( $data['items'] ?? array() );
	$totals  = (array) ( $data['totals'] ?? array() );
	$can     = cvc_admin_can( 'recruitment.publish' );
	$btn     = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$roles   = array(
		'plan'        => 'Kế hoạch',
		'notice'      => 'Thông báo',
		'amendment'   => 'Điều chỉnh',
		'council'     => 'Hội đồng',
		'supervision' => 'Ban giám sát',
		'syllabus'    => 'Tài liệu ôn tập',
		'candidates'  => 'Danh sách thí sinh',
		'summons'     => 'Triệu tập',
		'schedule'    => 'Lịch thi',
		'result'      => 'Kết quả',
		'cancel'      => 'Hủy / thu hồi',
	);
	$form = static function ( int $item_id, string $action, string $label, string $class, int $round_id = 0, string $confirm = '' ): string {
		$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="inline"' . ( '' !== $confirm ? ' data-cvc-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>';
		$html .= wp_nonce_field( 'cvc_admin_round_queue', '_wpnonce', true, false );
		$html .= '<input type="hidden" name="action" value="cvc_admin_round_queue"><input type="hidden" name="item" value="' . esc_attr( (string) $item_id ) . '"><input type="hidden" name="op" value="' . esc_attr( $action ) . '">';
		if ( $round_id > 0 ) {
			$html .= '<input type="hidden" name="round_id" value="' . esc_attr( (string) $round_id ) . '">';
		}
		$html .= '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';

		return $html;
	};
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-ink">Văn bản chưa xác định đợt tuyển</h1>
		<p class="text-sm text-ink-muted max-w-3xl">Văn bản vòng đời (kế hoạch, danh sách, triệu tập, lịch thi, kết quả…) chưa gắn được vào đợt nào, và các đợt chỉ có 1 văn bản chưa có tin mở đăng ký. Hệ thống tự gắn khi văn bản trích số hiệu văn bản gốc; các trường hợp dưới đây cần người duyệt. Liên kết thủ công không bị đồng bộ tự động ghi đè.</p>
	</header>
	<?php cvc_ingestion_tabs( 'dot' ); ?>

	<?php if ( ! $result['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $result ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
		<?php
		foreach ( array(
			array( 'Văn bản chưa gắn đợt', (int) ( $totals['pending_unlinked'] ?? 0 ) ),
			array( 'Đợt chỉ có 1 văn bản', (int) ( $totals['single_rounds'] ?? 0 ) ),
			array( 'Tổng số đợt', (int) ( $totals['rounds'] ?? 0 ) ),
			array( 'Đợt công khai', (int) ( $totals['public_rounds'] ?? 0 ) ),
		) as $s ) :
			?>
			<div class="bg-white border border-slate-300 rounded-xl p-3">
				<div class="text-xs text-ink-muted"><?php echo esc_html( $s[0] ); ?></div>
				<div class="text-2xl font-black text-ink"><?php echo esc_html( (string) $s[1] ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( empty( $items ) ) : ?>
		<p class="text-sm text-ink-muted">Không có văn bản nào cần xử lý.</p>
		<?php return; ?>
	<?php endif; ?>

	<ol class="space-y-3">
		<?php foreach ( $items as $it ) : ?>
			<?php
			$item_id = (int) ( $it['item_id'] ?? 0 );
			$own     = (array) ( $it['own_round'] ?? array() );
			$sugs    = (array) ( $it['suggestions'] ?? array() );
			$cites   = (array) ( $it['citations'] ?? array() );
			?>
			<li class="bg-white border border-slate-300 rounded-xl p-4 space-y-2">
				<div class="flex flex-wrap items-center gap-2 text-xs">
					<span class="dz-tag"><?php echo esc_html( $roles[ (string) ( $it['role'] ?? '' ) ] ?? (string) ( $it['role'] ?? 'Khác' ) ); ?></span>
					<?php if ( ! empty( $it['doc_number'] ) ) : ?><span class="font-bold text-acc">Số <?php echo esc_html( (string) $it['doc_number'] ); ?></span><?php endif; ?>
					<?php if ( ! empty( $it['issued_date'] ) ) : ?><span class="text-ink-muted"><?php echo esc_html( cvc_ingestion_date( $it['issued_date'] ) ); ?></span><?php endif; ?>
					<a class="text-acc underline" href="<?php echo esc_url( cvc_admin_url( 'thu-thap', $item_id ) ); ?>">Tin #<?php echo esc_html( (string) $item_id ); ?></a>
					<?php if ( ! empty( $it['source_url'] ) ) : ?><a class="text-ink-muted underline" href="<?php echo esc_url( (string) $it['source_url'] ); ?>" target="_blank" rel="noopener nofollow">Trang gốc</a><?php endif; ?>
				</div>
				<p class="font-bold text-ink leading-snug"><?php echo esc_html( (string) ( $it['title'] ?? '' ) ); ?></p>
				<?php if ( ! empty( $own ) ) : ?>
					<p class="text-xs text-ink-muted">Đang là đợt riêng #<?php echo esc_html( (string) ( $own['id'] ?? '' ) ); ?><?php echo ! empty( $own['public'] ) ? ' (công khai)' : ' (ẩn)'; ?> — có thể thuộc một đợt khác.</p>
				<?php endif; ?>
				<?php if ( ! empty( $cites ) ) : ?>
					<p class="text-xs text-ink-muted">Văn bản được trích: <?php echo esc_html( implode( ', ', array_map( static fn ( $c ) => (string) ( $c['related_doc_number'] ?? '' ) . ( empty( $c['related_item_id'] ) ? ' (chưa thu thập)' : '' ), $cites ) ) ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $sugs ) ) : ?>
					<div class="space-y-1.5">
						<p class="text-[11px] font-bold uppercase tracking-wider text-ink-2">Đợt gợi ý</p>
						<?php foreach ( $sugs as $sg ) : ?>
							<div class="flex flex-wrap items-center gap-2 text-xs border border-slate-200 rounded-lg px-3 py-2">
								<span class="font-black text-acc"><?php echo esc_html( number_format( (float) ( $sg['score'] ?? 0 ) * 100, 0 ) ); ?>%</span>
								<span class="flex-1 min-w-0 text-ink"><?php echo esc_html( (string) ( $sg['title'] ?? '' ) ); ?></span>
								<span class="text-ink-muted"><?php echo esc_html( (string) ( $sg['event_count'] ?? 0 ) ); ?> văn bản<?php echo ! empty( $sg['has_recruitment'] ) ? ' · có tin' : ''; ?></span>
								<?php if ( ! empty( $sg['evidence'] ) ) : ?><span class="text-ink-muted">khớp: <?php echo esc_html( implode( ', ', array_slice( (array) $sg['evidence'], 0, 6 ) ) ); ?></span><?php endif; ?>
								<?php if ( $can ) : ?>
									<?php echo $form( $item_id, 'attach', 'Gắn vào đợt này', $btn . ' bg-acc text-white hover:bg-acc-deep', (int) ( $sg['round_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="text-xs text-ink-muted">Không có đợt gợi ý đủ tin cậy.</p>
				<?php endif; ?>

				<?php if ( $can ) : ?>
					<div class="flex flex-wrap gap-2 pt-1">
						<?php if ( empty( $own ) ) : ?>
							<?php echo $form( $item_id, 'create', 'Tạo đợt mới', $btn . ' border border-slate-300 text-ink-2 hover:border-acc-2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php else : ?>
							<?php echo $form( $item_id, 'create', 'Giữ là đợt riêng', $btn . ' border border-slate-300 text-ink-2 hover:border-acc-2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endif; ?>
						<?php echo $form( $item_id, 'ignore', 'Bỏ qua', $btn . ' border border-slate-300 text-ink-muted hover:text-red-700', 0, 'Bỏ qua văn bản này (không thuộc đợt tuyển nào)?' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php
}

add_action( 'admin_post_cvc_admin_round_queue', 'cvc_admin_handle_round_queue' );
add_action( 'admin_post_nopriv_cvc_admin_round_queue', 'cvc_admin_handle_round_queue' );

function cvc_admin_handle_round_queue(): void {
	check_admin_referer( 'cvc_admin_round_queue' );
	$token = cvc_admin_require_staff();
	$item  = absint( $_POST['item'] ?? 0 );
	$op    = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );
	$back  = cvc_admin_url( 'thu-thap', null, array( 'xem' => 'dot' ) );
	if ( $item <= 0 || ! in_array( $op, array( 'attach', 'create', 'ignore' ), true ) ) {
		cvc_admin_redirect( $back, 'error', 'Thao tác không hợp lệ.' );
		return;
	}
	$body = array( 'action' => $op );
	if ( 'attach' === $op ) {
		$body['round_id'] = absint( $_POST['round_id'] ?? 0 );
	}
	$result = ( new CVC_Api_Client( null, 60 ) )->post( '/api/admin/ingestion/items/' . $item . '/round', $body, $token );
	cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $result['ok'] ? (string) ( $result['data']['message'] ?? 'Đã cập nhật.' ) : cvc_admin_error_message( $result ) );
}
