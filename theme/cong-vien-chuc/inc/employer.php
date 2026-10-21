<?php
/**
 * Cổng đơn vị tuyển dụng (21/10/2026) — phần dùng chung + xử lý form.
 * Trang: /don-vi-tuyen-dung/ (giới thiệu), /don-vi/{dang-ky|xac-minh|bang-dieu-khien|dang-tin|thanh-vien}/, /don-vi/tin/{id}/.
 * Mọi dữ liệu và quyền do Laravel quyết định (/api/employer/*); WordPress chỉ hiển thị + chuyển form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_employer_url( string $view = 'bang-dieu-khien', array $args = array() ): string {
	$url = home_url( '/don-vi/' . ( 'bang-dieu-khien' === $view ? '' : $view . '/' ) );

	return $args ? add_query_arg( $args, $url ) : $url;
}

function cvc_employer_post_url( int $recruitment_id ): string {
	return home_url( '/don-vi/tin/' . $recruitment_id . '/' );
}

function cvc_employer_landing_url(): string {
	return home_url( '/don-vi-tuyen-dung/' );
}

function cvc_employer_api(): CVC_Api_Client {
	return new CVC_Api_Client( null, 20 );
}

/** Cài đặt công khai của cổng (bật/tắt, ngoài công lập, bảng giá) — cache 5 phút. */
function cvc_employer_public_settings(): array {
	$cached = get_transient( 'cvc_employer_public_settings' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$res  = cvc_employer_api()->get( '/api/employer/public-settings' );
	$data = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array( 'enabled' => true, 'private_enabled' => true, 'paid' => false, 'prices' => array( 'post' => 0, 'pin' => 0, 'email' => 0 ), 'pin_days' => 7 );
	set_transient( 'cvc_employer_public_settings', $data, 5 * MINUTE_IN_SECONDS );

	return $data;
}

function cvc_employer_money( int $vnd ): string {
	return $vnd > 0 ? number_format( $vnd, 0, ',', '.' ) . ' ₫' : 'Miễn phí';
}

function cvc_employer_status_tag( string $status, string $label = '' ): string {
	$cls = array(
		'verified'         => 'is-ok', 'approved' => 'is-ok', 'published' => 'is-ok', 'applied' => 'is-ok', 'active' => 'is-ok', 'done' => 'is-ok',
		'pending'          => 'is-warn', 'submitted' => 'is-warn', 'review' => 'is-warn', 'awaiting_payment' => 'is-warn', 'sending' => 'is-warn', 'ocr' => 'is-warn', 'analyzing' => 'is-warn', 'draft' => '',
		'rejected'         => 'is-off', 'suspended' => 'is-off', 'expired' => 'is-off', 'cancelled' => 'is-off', 'failed' => 'is-off',
	);
	$labels = array(
		'verified' => 'Đã xác minh', 'pending' => 'Chờ xác minh', 'rejected' => 'Bị trả lại', 'suspended' => 'Tạm khóa',
		'draft' => 'Bản nháp', 'submitted' => 'Chờ duyệt', 'approved' => 'Đã đăng', 'published' => 'Đang đăng', 'review' => 'Tạm ẩn / chờ duyệt',
		'expired' => 'Hết hạn', 'applied' => 'Đã áp dụng', 'ocr' => 'Đang đọc tệp', 'analyzing' => 'Đang phân tích', 'failed' => 'Lỗi đọc tệp',
		'awaiting_payment' => 'Chờ thanh toán', 'active' => 'Đang chạy', 'sending' => 'Đang gửi', 'done' => 'Hoàn tất', 'cancelled' => 'Đã hủy',
	);

	return '<span class="dz-tag ' . esc_attr( $cls[ $status ] ?? '' ) . '">' . esc_html( '' !== $label ? $label : ( $labels[ $status ] ?? $status ) ) . '</span>';
}

/** Tài khoản hiện tại thuộc đơn vị nào (từ /api/auth/me). */
function cvc_employer_of_current_user(): ?array {
	$u = cvc_current_user();
	$e = is_array( $u ) ? ( $u['employer'] ?? null ) : null;

	return is_array( $e ) ? $e : null;
}

/* ------------------------------------------------------------------ xử lý form */

add_action( 'admin_post_cvc_employer', 'cvc_handle_employer_action' );
add_action( 'admin_post_nopriv_cvc_employer', 'cvc_handle_employer_action' );

function cvc_employer_files_from_request( string $field ): array {
	$out = array();
	if ( empty( $_FILES[ $field ] ) ) {
		return $out;
	}
	$raw = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( is_array( $raw['name'] ) ) {
		foreach ( $raw['name'] as $i => $n ) {
			if ( UPLOAD_ERR_OK !== (int) $raw['error'][ $i ] || '' === (string) $n ) {
				continue;
			}
			$name = sanitize_file_name( (string) $n );
			$out[ $field . '[' . count( $out ) . ']' ] = array( (string) $raw['tmp_name'][ $i ], $name, (string) ( wp_check_filetype( $name )['type'] ?: 'application/octet-stream' ) );
		}
	} elseif ( UPLOAD_ERR_OK === (int) $raw['error'] && '' !== (string) $raw['name'] ) {
		$name           = sanitize_file_name( (string) $raw['name'] );
		$out[ $field ] = array( (string) $raw['tmp_name'], $name, (string) ( wp_check_filetype( $name )['type'] ?: 'application/octet-stream' ) );
	}

	return $out;
}

function cvc_handle_employer_action(): void {
	check_admin_referer( 'cvc_employer' );
	$token = cvc_require_token_or_die();
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$api   = cvc_employer_api();
	$p     = static fn ( string $k ): string => sanitize_text_field( wp_unslash( (string) ( $_POST[ $k ] ?? '' ) ) );
	$txt   = static fn ( string $k ): string => sanitize_textarea_field( wp_unslash( (string) ( $_POST[ $k ] ?? '' ) ) );
	$rid   = absint( $_POST['recruitment_id'] ?? 0 );
	$iid   = absint( $_POST['import_id'] ?? 0 );
	$back  = wp_validate_redirect( esc_url_raw( wp_unslash( (string) ( $_POST['back'] ?? '' ) ) ), cvc_employer_url() );
	$done  = static function ( array $res, string $ok_url, string $ok_msg = '' ) use ( $back ): void {
		if ( ! $res['ok'] ) {
			$msg = cvc_api_error_message( $res );
			if ( 409 === (int) ( $res['status'] ?? 0 ) && 'duplicate_file' === ( $res['data']['code'] ?? '' ) ) {
				$dups = (array) ( $res['data']['data']['duplicates'] ?? array() );
				$rid  = 0;
				foreach ( $dups as $d ) {
					$rid = $rid ?: (int) ( $d['recruitment_id'] ?? 0 );
				}
				$msg = (string) ( $res['data']['message'] ?? '' ) . ( $rid ? ' Nếu đây là thông báo của đơn vị, hãy dùng "Nhận quản lý tin" (tìm tin #' . $rid . ') thay vì đăng mới.' : '' ) . ' Muốn vẫn tải lên, tích "Vẫn tải lên dù tệp đã có".';
			}
			cvc_redirect_with_notice( $back, 'error', $msg, null );
			return;
		}
		cvc_redirect_with_notice( $ok_url, 'success', '' !== $ok_msg ? $ok_msg : (string) ( $res['data']['message'] ?? 'Đã lưu.' ), null );
	};

	switch ( $op ) {
		case 'register':
			$res = $api->post( '/api/employer/register', array_filter( array(
				'name' => $p( 'name' ), 'sector' => 'private' === $p( 'sector' ) ? 'private' : 'public', 'province_id' => absint( $_POST['province_id'] ?? 0 ) ?: null,
				'website' => esc_url_raw( $p( 'website' ) ) ?: null, 'phone' => $p( 'phone' ), 'address' => $p( 'address' ) ?: null, 'tax_code' => $p( 'tax_code' ) ?: null,
			), static fn ( $v ) => null !== $v && '' !== $v ), $token );
			cvc_auth_refresh_user_cache();
			$done( $res, cvc_employer_url( 'xac-minh' ) );
			return;
		case 'profile':
			$done( $api->put( '/api/employer/profile', array_filter( array( 'website' => esc_url_raw( $p( 'website' ) ) ?: null, 'phone' => $p( 'phone' ) ?: null, 'address' => $p( 'address' ) ?: null ) ), $token ), cvc_employer_url( 'thanh-vien' ) );
			return;
		case 'send_code':
			$done( $api->post( '/api/employer/verify/send-code', array( 'email' => sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) ) ), $token ), cvc_employer_url( 'xac-minh', array( 'buoc' => 'ma' ) ) );
			return;
		case 'verify_code':
			$res = $api->post( '/api/employer/verify/code', array( 'code' => preg_replace( '/\D/', '', $p( 'code' ) ) ), $token );
			cvc_auth_refresh_user_cache();
			$done( $res, cvc_employer_url() );
			return;
		case 'verify_doc':
			$files = cvc_employer_files_from_request( 'file' );
			if ( empty( $files ) ) {
				cvc_redirect_with_notice( $back, 'error', 'Hãy chọn tệp giấy giới thiệu (PDF hoặc ảnh, ≤ 15MB).', null );
				return;
			}
			$done( $api->post_multipart( '/api/employer/verify/document', array(), $files, $token ), cvc_employer_url( 'xac-minh' ) );
			return;
		case 'post_upload':
			$files = cvc_employer_files_from_request( 'files' );
			if ( empty( $files ) ) {
				cvc_redirect_with_notice( $back, 'error', 'Hãy chọn tệp thông báo tuyển dụng (PDF, Word hoặc ảnh chụp có dấu).', null );
				return;
			}
			$res = $api->post_multipart( '/api/employer/posts', array_filter( array( 'source_url' => esc_url_raw( $p( 'source_url' ) ) ?: null, 'title' => $p( 'title' ) ?: null, 'allow_duplicate' => empty( $_POST['allow_duplicate'] ) ? null : '1' ) ), $files, $token );
			$new = (int) ( $res['data']['data']['id'] ?? 0 );
			$done( $res, cvc_employer_url( 'dang-tin', array( 'id' => $new ) ) );
			return;
		case 'post_update':
			$fields = array();
			foreach ( array( 'title', 'agency_name', 'province_name', 'location', 'announcement_date', 'application_start_date', 'application_deadline' ) as $k ) {
				$fields[ $k ] = $p( $k ) ?: null;
			}
			$fields['summary']         = $txt( 'summary' ) ?: null;
			$fields['total_positions'] = absint( $_POST['total_positions'] ?? 0 ) ?: null;
			$res                       = $api->put( '/api/employer/posts/' . $iid, array_filter( $fields, static fn ( $v ) => null !== $v ), $token );
			$done( $res, cvc_employer_url( 'dang-tin', array( 'id' => $iid ) ), 'Đã lưu thông tin.' );
			return;
		case 'post_submit':
			$res = $api->post( '/api/employer/posts/' . $iid . '/submit', array(), $token );
			if ( $res['ok'] && 'payment' === ( $res['data']['data']['status'] ?? '' ) ) {
				cvc_employer_pay( (int) ( $res['data']['data']['promotion']['id'] ?? 0 ), cvc_employer_url( 'dang-tin', array( 'id' => $iid ) ), $token );
				return;
			}
			$rec = (int) ( $res['data']['data']['recruitment_id'] ?? 0 );
			$done( $res, $rec ? cvc_employer_post_url( $rec ) : cvc_employer_url() );
			return;
		case 'change':
			$fields = array( 'note' => $txt( 'note' ) );
			foreach ( array( 'application_start_date', 'application_deadline', 'announcement_date', 'location' ) as $k ) {
				if ( '' !== $p( $k ) ) {
					$fields[ $k ] = $p( $k );
				}
			}
			if ( '' !== $txt( 'summary' ) ) {
				$fields['summary'] = $txt( 'summary' );
			}
			if ( absint( $_POST['total_positions'] ?? 0 ) ) {
				$fields['total_positions'] = absint( $_POST['total_positions'] );
			}
			$done( $api->post( '/api/employer/recruitments/' . $rid . '/changes', $fields, $token ), cvc_employer_post_url( $rid ) );
			return;
		case 'update_upload':
			$files = cvc_employer_files_from_request( 'file' );
			if ( empty( $files ) ) {
				cvc_redirect_with_notice( $back, 'error', 'Hãy chọn tệp văn bản.', null );
				return;
			}
			$done( $api->post_multipart( '/api/employer/recruitments/' . $rid . '/updates', array( 'kind' => sanitize_key( $p( 'kind' ) ), 'title' => $p( 'title' ) ), $files, $token ), cvc_employer_post_url( $rid ) );
			return;
		case 'promote':
			$res = $api->post( '/api/employer/recruitments/' . $rid . '/promote', array( 'kind' => 'email' === $p( 'kind' ) ? 'email' : 'pin' ), $token );
			if ( $res['ok'] && 'awaiting_payment' === ( $res['data']['data']['status'] ?? '' ) ) {
				cvc_employer_pay( (int) ( $res['data']['data']['id'] ?? 0 ), cvc_employer_post_url( $rid ), $token );
				return;
			}
			$done( $res, cvc_employer_post_url( $rid ) );
			return;
		case 'promo_pay':
			cvc_employer_pay( absint( $_POST['promotion_id'] ?? 0 ), $back, $token );
			return;
		case 'promo_cancel':
			$done( $api->post( '/api/employer/promotions/' . absint( $_POST['promotion_id'] ?? 0 ) . '/cancel', array(), $token ), $back );
			return;
		case 'claim':
			$done( $api->post( '/api/employer/claims', array( 'recruitment_id' => absint( $_POST['claim_recruitment_id'] ?? 0 ), 'note' => $txt( 'note' ) ?: null ), $token ), cvc_employer_url( 'thanh-vien' ) );
			return;
		case 'member_add':
			$done( $api->post( '/api/employer/members', array( 'email' => sanitize_email( wp_unslash( (string) ( $_POST['email'] ?? '' ) ) ) ), $token ), cvc_employer_url( 'thanh-vien' ) );
			return;
		case 'member_remove':
			$done( $api->delete( '/api/employer/members/' . absint( $_POST['member_id'] ?? 0 ), $token ), cvc_employer_url( 'thanh-vien' ) );
			return;
	}
	cvc_redirect_with_notice( $back, 'error', 'Thao tác không hợp lệ.', null );
}

/** Tạo đơn thanh toán cho dịch vụ (phí đăng tin / ghim / email) rồi chuyển sang cổng VNPay hoặc trang QR. */
function cvc_employer_pay( int $promotion_id, string $back, string $token ): void {
	$res = ( new CVC_Api_Client( null, 30 ) )->post( '/api/orders', array( 'items' => array( array( 'type' => 'employer_promo', 'id' => $promotion_id ) ) ), $token );
	if ( ! $res['ok'] ) {
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $res ), null );
		return;
	}
	$url = cvc_order_pay_url( $res, $back );
	if ( '' === $url ) {
		cvc_redirect_with_notice( $back, 'error', 'Không khởi tạo được thanh toán. Vui lòng thử lại.', null );
		return;
	}
	// phpcs:ignore WordPress.Security.SafeRedirect -- cổng VNPay (ngoài) hoặc trang QR nội bộ.
	wp_redirect( $url );
	exit;
}

/** Xoá cache /api/auth/me của người dùng hiện tại (menu "Đơn vị" hiện ngay sau khi đăng ký / xác minh). */
function cvc_auth_refresh_user_cache(): void {
	if ( function_exists( 'cvc_forget_current_user' ) ) {
		cvc_forget_current_user();
	}
}

/* ------------------------------------------------------------------ báo sai thông tin (trang tin) */

add_action( 'admin_post_cvc_recruitment_report', 'cvc_handle_recruitment_report' );
add_action( 'admin_post_nopriv_cvc_recruitment_report', 'cvc_handle_recruitment_report' );

function cvc_handle_recruitment_report(): void {
	check_admin_referer( 'cvc_recruitment_report' );
	$rid  = absint( $_POST['recruitment_id'] ?? 0 );
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( (string) ( $_POST['back'] ?? '' ) ) ), home_url( '/tuyen-dung/' ) );
	if ( ! empty( $_POST['website'] ) ) { // bẫy bot
		wp_safe_redirect( $back );
		exit;
	}
	$res = cvc_employer_api()->post( '/api/recruitments/' . $rid . '/report', array(
		'reason' => sanitize_key( (string) ( $_POST['reason'] ?? '' ) ),
		'note'   => sanitize_textarea_field( wp_unslash( (string) ( $_POST['note'] ?? '' ) ) ),
	), cvc_auth_token() );
	cvc_redirect_with_notice( $back . '#bao-sai', $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Cảm ơn bạn!' ) : cvc_api_error_message( $res ), null );
}

/** Khối "Báo sai thông tin" + nhãn đơn vị + văn bản cập nhật đợt — gọi trong template-recruitment-detail.php. */
function cvc_render_recruitment_employer_block( array $r ): void {
	$emp     = is_array( $r['employer'] ?? null ) ? $r['employer'] : null;
	$updates = (array) ( $r['employer_updates'] ?? array() );
	if ( $emp ) :
		?>
		<div class="dz-notice" role="note">
			<b><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php echo esc_html( (string) $emp['label'] ); ?>:</b>
			<?php echo esc_html( (string) $emp['name'] ); ?>
			<?php if ( ! empty( $emp['website'] ) ) : ?> · <a href="<?php echo esc_url( (string) $emp['website'] ); ?>" target="_blank" rel="nofollow noopener">website đơn vị</a><?php endif; ?>
		</div>
		<?php
	endif;
	if ( $updates ) :
		?>
		<section class="dz-section" aria-labelledby="cvc-emp-updates">
			<h2 id="cvc-emp-updates" class="dz-h2">Cập nhật từ đơn vị tuyển dụng</h2>
			<ul class="dz-list">
				<?php foreach ( $updates as $u ) : ?>
					<li>
						<div class="dz-item-title"><a href="<?php echo esc_url( (string) ( $u['download_url'] ?? '#' ) ); ?>" rel="nofollow"><?php echo esc_html( (string) $u['title'] ); ?></a></div>
						<div class="dz-item-meta"><?php echo esc_html( (string) ( $u['kind_label'] ?? '' ) ); ?> · <?php echo esc_html( ! empty( $u['created_at'] ) ? wp_date( 'd/m/Y', strtotime( (string) $u['created_at'] ) ) : '' ); ?> · <?php echo esc_html( (string) ( $u['file_name'] ?? '' ) ); ?></div>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	endif;
}

function cvc_render_recruitment_report_form( array $r ): void {
	$rid = (int) ( $r['id'] ?? 0 );
	if ( ! $rid ) {
		return;
	}
	$reasons = array(
		'wrong_deadline' => 'Sai hạn nộp / thời gian', 'wrong_quota' => 'Sai chỉ tiêu, vị trí', 'wrong_info' => 'Sai thông tin khác',
		'expired' => 'Đã hết hạn / đã hủy', 'fake' => 'Nghi giả mạo, lừa đảo', 'duplicate' => 'Trùng tin khác',
	);
	$here    = home_url( add_query_arg( array() ) );
	?>
	<details id="bao-sai" class="dz-box">
		<summary class="dz-box-head cursor-pointer"><i class="fa-regular fa-flag" aria-hidden="true"></i> Báo sai thông tin</summary>
		<form class="dz-box-body space-y-2 text-sm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cvc_recruitment_report' ); ?>
			<input type="hidden" name="action" value="cvc_recruitment_report">
			<input type="hidden" name="recruitment_id" value="<?php echo (int) $rid; ?>">
			<input type="hidden" name="back" value="<?php echo esc_url( $here ); ?>">
			<input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
			<label class="block font-bold" for="cvc-rep-reason">Thông tin nào chưa đúng?</label>
			<select id="cvc-rep-reason" name="reason" class="w-full border border-slate-300 rounded px-2 py-1.5" required>
				<?php foreach ( $reasons as $k => $l ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select>
			<label class="block font-bold" for="cvc-rep-note">Chi tiết (tùy chọn)</label>
			<textarea id="cvc-rep-note" name="note" rows="3" maxlength="1000" class="w-full border border-slate-300 rounded px-2 py-1.5" placeholder="VD: thông báo gia hạn đến 30/11 theo văn bản số …"></textarea>
			<button type="submit" class="dz-btn dz-btn-sm">Gửi báo cáo</button>
			<p class="text-xs text-ink-muted">Biên tập viên sẽ kiểm tra với văn bản gốc. Tin bị báo nhiều lần sẽ tạm ẩn để xác minh.</p>
		</form>
	</details>
	<?php
}
