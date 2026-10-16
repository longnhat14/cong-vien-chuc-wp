<?php
/**
 * Quản trị "Nguồn sưu tầm" (12/10/2026): tên miền nhà nước đã xác minh / loại trừ.
 *
 *  ?xem=ten-mien   danh sách + gợi ý chờ xác nhận + thêm tên miền + kiểm tra 1 địa chỉ
 *
 * Quy tắc (backend OfficialSourceService): .gov.vn/.edu.vn hoặc tên miền đã xác minh = chính thức;
 * còn lại hiển thị công khai là "Nguồn sưu tầm" (không link, không tên miền).
 *
 * @package CongVienChuc
 */

defined( 'ABSPATH' ) || exit;

function cvc_admin_render_official_domains(): void {
	$result  = cvc_admin_api_get( '/api/admin/official-domains' );
	$rows    = (array) ( $result['data']['data'] ?? array() );
	$stats   = (array) ( $result['data']['stats'] ?? array() );
	$can_upd = cvc_admin_can( 'recruitment.update' );
	$check   = isset( $_GET['kiem'] ) ? esc_url_raw( wp_unslash( $_GET['kiem'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$checked = '' !== $check ? (array) ( cvc_admin_api_get( '/api/admin/official-domains/check', array( 'url' => $check ) )['data']['data'] ?? array() ) : array();
	$input   = cvc_admin_input_class();
	$btn     = 'px-3 py-1.5 rounded-lg text-xs font-bold';
	$groups  = array(
		'suggested' => array_values( array_filter( $rows, static fn ( $r ) => 'suggested' === ( $r['status'] ?? '' ) ) ),
		'allow'     => array_values( array_filter( $rows, static fn ( $r ) => 'verified' === ( $r['status'] ?? '' ) && 'allow' === ( $r['kind'] ?? '' ) ) ),
		'deny'       => array_values( array_filter( $rows, static fn ( $r ) => 'verified' === ( $r['status'] ?? '' ) && 'deny' === ( $r['kind'] ?? '' ) ) ),
		'competitor' => array_values( array_filter( $rows, static fn ( $r ) => 'competitor' === ( $r['kind'] ?? '' ) ) ),
	);
	$titles  = array(
		'suggested' => 'Gợi ý chờ xác nhận',
		'allow'     => 'Tên miền nhà nước đã xác minh',
		'deny'       => 'Tên miền loại trừ (báo chí, không phải cơ quan nhà nước)',
		'competitor' => 'Trang đối thủ / tổng hợp việc làm (không thu thập)',
	);
	?>
	<header class="space-y-1">
		<h1 class="text-2xl font-black text-ink">Nguồn chính thức &amp; nguồn sưu tầm</h1>
		<p class="text-sm text-ink-muted max-w-3xl">Chính sách nguồn (16/10/2026): hệ thống <strong class="text-ink">chỉ quét, xử lý và đăng</strong> tin từ <strong class="text-ink">.gov.vn / .edu.vn</strong> và tên miền nhà nước / trường uy tín đã xác minh. Trang đối thủ, báo chí, mạng xã hội và tên miền chưa xác minh bị chặn; nguồn của tên miền chưa xác minh tạm dừng cho đến khi anh xác nhận.</p>
	</header>
	<?php cvc_ingestion_tabs( 'ten-mien' ); ?>

	<?php if ( ! $result['ok'] ) : ?>
		<p class="text-sm text-red-700"><?php echo esc_html( cvc_admin_error_message( $result ) ); ?></p>
		<?php return; ?>
	<?php endif; ?>

	<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
		<?php
		foreach ( array(
			array( 'Đã xác minh', (int) ( $stats['verified_allow'] ?? 0 ), 'text-emerald-300' ),
			array( 'Loại trừ', (int) ( $stats['verified_deny'] ?? 0 ), 'text-rose-300' ),
			array( 'Gợi ý chờ xác nhận', (int) ( $stats['suggested'] ?? 0 ), 'text-amber-300' ),
			array( 'Nguồn đang tạm dừng vì chính sách', (int) ( $stats['blocked_sources'] ?? 0 ), 'text-cyan-300' ),
		) as $s ) :
			?>
			<div class="bg-white border border-slate-300 rounded-xl p-3">
				<div class="text-xs text-ink-muted"><?php echo esc_html( $s[0] ); ?></div>
				<div class="text-2xl font-black <?php echo esc_attr( $s[2] ); ?>"><?php echo esc_html( (string) $s[1] ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="grid lg:grid-cols-2 gap-4">
		<form method="get" action="<?php echo esc_url( cvc_admin_url( 'thu-thap' ) ); ?>" class="dz-card space-y-2">
			<input type="hidden" name="xem" value="ten-mien">
			<label class="text-xs font-bold text-ink-2" for="cvc-od-check">Kiểm tra một địa chỉ</label>
			<div class="flex gap-2">
				<input id="cvc-od-check" type="url" name="kiem" value="<?php echo esc_attr( $check ); ?>" placeholder="https://..." class="<?php echo esc_attr( $input ); ?>">
				<button class="<?php echo esc_attr( $btn ); ?> bg-white text-ink shrink-0">Kiểm tra</button>
			</div>
			<?php if ( $checked ) : ?>
				<p class="text-sm <?php echo ! empty( $checked['official'] ) ? 'text-emerald-800' : 'text-acc'; ?>">
					<?php echo esc_html( ( ! empty( $checked['official'] ) ? 'Chính thức' : 'Nguồn sưu tầm' ) . ' — ' . (string) ( $checked['reason'] ?? '' ) ); ?>
				</p>
			<?php endif; ?>
		</form>

		<?php if ( $can_upd ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dz-card space-y-2">
				<?php wp_nonce_field( 'cvc_admin_official_domains' ); ?>
				<input type="hidden" name="action" value="cvc_admin_official_domains">
				<input type="hidden" name="op" value="create">
				<div class="text-xs font-bold text-ink-2">Thêm tên miền</div>
				<div class="grid sm:grid-cols-2 gap-2">
					<input type="text" name="domain" required placeholder="benhvienxyz.vn" class="text-ink border-slate-400 bg-white <?php echo esc_attr( $input ); ?> text-ink border-slate-400 bg-white" aria-label="Tên miền">
					<select name="kind" class="text-ink border-slate-400 bg-white <?php echo esc_attr( $input ); ?> text-ink border-slate-400 bg-white" aria-label="Loại">
						<option value="allow">Nhà nước (chính thức)</option>
						<option value="deny">Loại trừ (không phải nhà nước)</option>
						<option value="competitor">Đối thủ / tổng hợp việc làm</option>
					</select>
					<input type="text" name="organization" placeholder="Tên cơ quan / đơn vị" class="text-ink border-slate-400 bg-white <?php echo esc_attr( $input ); ?> text-ink border-slate-400 bg-white" aria-label="Tên cơ quan">
					<input type="url" name="evidence_url" placeholder="Link bằng chứng (VD trang đơn vị trực thuộc của Sở)" class="text-ink border-slate-400 bg-white <?php echo esc_attr( $input ); ?> text-ink border-slate-400 bg-white" aria-label="Link bằng chứng">
				</div>
				<button class="<?php echo esc_attr( $btn ); ?> bg-acc text-slate-950 text-white">Lưu</button>
			</form>
		<?php endif; ?>
	</div>

	<?php foreach ( $groups as $key => $list ) : ?>
		<section class="dz-card space-y-3">
			<h2 class="text-sm font-extrabold text-ink"><?php echo esc_html( $titles[ $key ] . ' (' . count( $list ) . ')' ); ?></h2>
			<?php if ( 'suggested' === $key ) : ?>
				<p class="text-xs text-ink-muted">Tên miền của bệnh viện, trường… có trong danh sách đơn vị trực thuộc trên trang của Sở. Kiểm tra link bằng chứng rồi xác nhận nếu đúng là đơn vị công lập — nguồn của tên miền đó tự bật lại.</p>
			<?php endif; ?>
			<?php if ( empty( $list ) ) : ?>
				<p class="text-xs text-ink-muted">Chưa có.</p>
			<?php else : ?>
				<div class="overflow-x-auto">
					<table class="w-full text-sm">
						<tbody class="divide-y divide-slate-200">
							<?php foreach ( $list as $r ) : ?>
								<tr class="align-top">
									<td class="py-2 pr-3 text-ink"><?php echo esc_html( (string) $r['domain'] ); ?></td>
									<td class="py-2 pr-3 text-ink-2"><?php echo esc_html( (string) ( $r['organization'] ?? '' ) ); ?>
										<?php if ( ! empty( $r['note'] ) ) : ?>
											<div class="text-xs text-ink-muted"><?php echo esc_html( (string) $r['note'] ); ?></div>
										<?php endif; ?>
									</td>
									<td class="py-2 pr-3 text-xs">
										<?php if ( ! empty( $r['evidence_url'] ) ) : ?>
											<a class="text-acc underline" href="<?php echo esc_url( (string) $r['evidence_url'] ); ?>" target="_blank" rel="noopener nofollow">Bằng chứng</a>
										<?php endif; ?>
									</td>
									<td class="py-2 text-right whitespace-nowrap">
										<?php if ( $can_upd ) : ?>
											<?php if ( 'suggested' === $key ) : ?>
												<?php echo cvc_official_domain_op_form( 'verify', (int) $r['id'], 'Xác nhận nhà nước', 'bg-emerald-600 text-white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
												<?php echo cvc_official_domain_op_form( 'deny', (int) $r['id'], 'Không phải', 'bg-slate-700 text-slate-200' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<?php else : ?>
												<?php echo cvc_official_domain_op_form( 'delete', (int) $r['id'], 'Xóa', 'bg-slate-800 text-rose-300', 'Xóa tên miền này khỏi danh sách?' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<?php endif; ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>
	<?php
}

function cvc_official_domain_op_form( string $op, int $id, string $label, string $class, string $confirm = '' ): string {
	$html  = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="inline"' . ( '' !== $confirm ? ' data-cvc-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>';
	$html .= wp_nonce_field( 'cvc_admin_official_domains', '_wpnonce', true, false );
	$html .= '<input type="hidden" name="action" value="cvc_admin_official_domains"><input type="hidden" name="op" value="' . esc_attr( $op ) . '"><input type="hidden" name="id" value="' . esc_attr( (string) $id ) . '">';
	$html .= '<button class="px-2.5 py-1 rounded-lg text-xs font-bold ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';

	return $html;
}

add_action( 'admin_post_cvc_admin_official_domains', 'cvc_admin_handle_official_domains' );
add_action( 'admin_post_nopriv_cvc_admin_official_domains', 'cvc_admin_handle_official_domains' );

function cvc_admin_handle_official_domains(): void {
	check_admin_referer( 'cvc_admin_official_domains' );
	$token  = cvc_admin_require_staff();
	$op     = sanitize_key( wp_unslash( $_POST['op'] ?? '' ) );
	$id     = absint( $_POST['id'] ?? 0 );
	$back   = cvc_admin_url( 'thu-thap', null, array( 'xem' => 'ten-mien' ) );
	$client = new CVC_Api_Client( null, 60 );

	switch ( $op ) {
		case 'create':
			$result = $client->post(
				'/api/admin/official-domains',
				array_filter(
					array(
						'domain'       => sanitize_text_field( wp_unslash( $_POST['domain'] ?? '' ) ),
						'kind'         => in_array( sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) ), array( 'deny', 'competitor' ), true ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : 'allow',
						'organization' => sanitize_text_field( wp_unslash( $_POST['organization'] ?? '' ) ),
						'evidence_url' => esc_url_raw( wp_unslash( $_POST['evidence_url'] ?? '' ) ),
					)
				),
				$token
			);
			$msg = 'Đã thêm tên miền.';
			break;
		case 'verify':
			$result = $client->put( '/api/admin/official-domains/' . $id, array( 'status' => 'verified', 'kind' => 'allow' ), $token );
			$msg    = 'Đã xác nhận tên miền nhà nước.';
			break;
		case 'deny':
			$result = $client->put( '/api/admin/official-domains/' . $id, array( 'status' => 'verified', 'kind' => 'deny' ), $token );
			$msg    = 'Đã chuyển sang danh sách loại trừ.';
			break;
		case 'delete':
			$result = $client->delete( '/api/admin/official-domains/' . $id, $token );
			$msg    = 'Đã xóa.';
			break;
		default:
			cvc_admin_redirect( $back, 'error', 'Thao tác không hợp lệ.' );
			return;
	}

	if ( $result['ok'] ) {
		$n    = (array) ( $result['data']['recomputed'] ?? array() );
		$msg .= isset( $n['sources'] ) ? sprintf( ' Nguồn bật lại: %d, tạm dừng: %d.', (int) ( $n['sources']['enabled'] ?? 0 ), (int) ( $n['sources']['disabled'] ?? 0 ) ) : '';
	}
	cvc_admin_redirect( $back, $result['ok'] ? 'success' : 'error', $result['ok'] ? $msg : cvc_admin_error_message( $result ) );
}
