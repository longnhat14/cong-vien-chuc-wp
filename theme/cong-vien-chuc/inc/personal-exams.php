<?php
/**
 * Đề trắc nghiệm cá nhân hóa theo tin (RP-) — thu phí (20/10/2026).
 * - Trang đề: hộp quyền làm đề (làm thử 10 câu / mua bộ đề của tin / gói tháng).
 * - Mua: POST /api/orders (exam_template | exam_pass) → VNPay; nhớ trang đề để quay lại sau thanh toán.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CVC_PE_BACK_COOKIE' ) ) {
	define( 'CVC_PE_BACK_COOKIE', 'cvc_buy_back' );
}

/**
 * Quyền làm đề RP (null nếu không phải đề RP hoặc lỗi API).
 */
function cvc_pe_access( array $exam, ?string $token ): ?array {
	if ( 0 !== strpos( (string) ( $exam['code'] ?? '' ), 'RP-' ) ) {
		return null;
	}
	$res = ( new CVC_Api_Client( null, 8 ) )->get( '/api/exams/' . (int) ( $exam['id'] ?? 0 ) . '/access', array(), $token );

	return $res['ok'] && is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : null;
}

function cvc_pe_money( $n ): string {
	return number_format( (float) $n, 0, ',', '.' ) . ' ₫';
}

/**
 * Hộp quyền làm đề trên trang đề RP.
 */
function cvc_pe_render_paybox( array $exam, array $acc, ?string $token, string $exam_url ): void {
	if ( empty( $acc['paid'] ) || empty( $acc['paywall'] ) ) {
		return;
	}
	$pack    = is_array( $acc['pack'] ?? null ) ? $acc['pack'] : null;
	$pass    = is_array( $acc['pass'] ?? null ) ? $acc['pass'] : array();
	$prices  = (array) ( $acc['prices'] ?? array() );
	$preview = (int) ( $acc['preview'] ?? 10 );
	?>
	<div class="dz-card space-y-3 text-sm" id="mua-bo-de">
		<?php if ( empty( $acc['locked'] ) ) : ?>
			<p class="font-black text-emerald-800"><i class="fa-solid fa-unlock" aria-hidden="true"></i> Bạn đã mở đề này</p>
			<p class="text-xs text-ink-2">
				<?php
				if ( 'pass' === ( $acc['via'] ?? '' ) && ! empty( $acc['pass_expires_at'] ) ) {
					echo esc_html( 'Gói tháng còn hạn đến ' . wp_date( 'd/m/Y', strtotime( (string) $acc['pass_expires_at'] ) ) . ' — mở mọi đề cá nhân hóa.' );
				} elseif ( 'staff' === ( $acc['via'] ?? '' ) ) {
					echo esc_html( 'Tài khoản quản trị xem được mọi đề.' );
				} else {
					echo esc_html( 'Bạn đã mua bộ đề này; các tin khác dùng cùng bộ đề cũng tự mở.' );
				}
				?>
			</p>
		<?php else : ?>
			<p class="font-black text-ink"><i class="fa-solid fa-lock text-acc" aria-hidden="true"></i> Đề có phí — làm thử <?php echo (int) $preview; ?> câu đầu miễn phí</p>
			<p class="text-xs text-ink-2">Bắt đầu làm bài khi chưa mua sẽ mở lượt làm thử <?php echo (int) $preview; ?> câu (có chấm điểm, đáp án, giải thích và căn cứ). Mua để làm đủ <?php echo (int) ( $exam['questions_count'] ?? 60 ); ?> câu và mọi đề của bộ.</p>
			<?php if ( null === $token ) : ?>
				<a class="block w-full py-2.5 text-center text-white font-black rounded-xl bg-acc hover:bg-acc-deep" rel="nofollow" href="<?php echo esc_url( cvc_login_url( $exam_url ) ); ?>">Đăng nhập để làm thử / mua</a>
			<?php elseif ( empty( $acc['payment_ready'] ) ) : ?>
				<p class="text-xs text-amber-800">Cổng thanh toán đang được cấu hình, vui lòng quay lại sau.</p>
			<?php else : ?>
				<?php if ( $pack ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'cvc_exam_buy' ); ?>
						<input type="hidden" name="action" value="cvc_exam_buy">
						<input type="hidden" name="type" value="exam_template">
						<input type="hidden" name="id" value="<?php echo (int) $pack['template_id']; ?>">
						<input type="hidden" name="back" value="<?php echo esc_attr( $exam_url ); ?>">
						<button type="submit" class="block w-full py-2.5 text-white font-black rounded-xl bg-acc hover:bg-acc-deep">
							Mua bộ <?php echo (int) $pack['exams']; ?> đề · <?php echo esc_html( cvc_pe_money( $prices['pack'] ?? 0 ) ); ?>
							<span class="block text-[11px] font-normal opacity-90">Dùng mãi; tin khác cùng bộ đề tự mở</span>
						</button>
					</form>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'cvc_exam_buy' ); ?>
					<input type="hidden" name="action" value="cvc_exam_buy">
					<input type="hidden" name="type" value="exam_pass">
					<input type="hidden" name="id" value="0">
					<input type="hidden" name="back" value="<?php echo esc_attr( $exam_url ); ?>">
					<button type="submit" class="block w-full py-2.5 font-black rounded-xl bg-white text-acc border border-acc-line hover:bg-acc-soft">
						Gói <?php echo (int) ( $pass['days'] ?? 30 ); ?> ngày · <?php echo esc_html( cvc_pe_money( $prices['pass'] ?? 0 ) ); ?>
						<span class="block text-[11px] font-normal">Mở mọi đề cá nhân hóa của mọi tin tuyển dụng</span>
					</button>
				</form>
				<p class="text-[10px] text-ink-muted">Thanh toán qua VNPay. Quyền được kích hoạt ngay sau khi thanh toán thành công.</p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( ! empty( $acc['siblings'] ) && count( (array) $acc['siblings'] ) > 1 ) : ?>
			<p class="text-xs text-ink-muted pt-1 border-t border-slate-300">Các đề trong bộ:
				<?php foreach ( (array) $acc['siblings'] as $i => $s ) : ?><?php echo $i ? ' · ' : ' '; ?><a class="text-acc font-bold" href="<?php echo esc_url( cvc_exam_url( (string) $s['slug'] ) ); ?>"><?php echo esc_html( preg_replace( '/^.*–\s*/u', '', (string) $s['title'] ) ); ?></a><?php endforeach; ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_post_nopriv_cvc_exam_buy', 'cvc_handle_exam_buy' );
add_action( 'admin_post_cvc_exam_buy', 'cvc_handle_exam_buy' );

function cvc_handle_exam_buy(): void {
	check_admin_referer( 'cvc_exam_buy' );
	$back  = wp_validate_redirect( esc_url_raw( wp_unslash( (string) ( $_POST['back'] ?? '' ) ) ), home_url( '/' ) );
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_safe_redirect( cvc_login_url( $back ) );
		exit;
	}
	$type = sanitize_key( (string) ( $_POST['type'] ?? '' ) );
	$id   = absint( $_POST['id'] ?? 0 );
	if ( ! in_array( $type, array( 'exam_template', 'exam_pass' ), true ) || ( 'exam_template' === $type && 0 === $id ) ) {
		cvc_redirect_with_notice( $back, 'error', 'Gói không hợp lệ.', null );
		return;
	}
	$result = ( new CVC_Order_Service() )->create( array( array( 'type' => $type, 'id' => $id ) ), $token );
	if ( ! $result['ok'] ) {
		$message = ! empty( $result['error'] ) ? (string) $result['error'] : cvc_api_error_message( $result );
		cvc_redirect_with_notice( $back, 'error', $message, null );
		return;
	}
	// VNPay -> cổng; SePay -> trang mã QR. Nhớ trang đề để quay lại làm bài sau khi thanh toán.
	$payment_url = cvc_order_pay_url( $result, $back );
	if ( '' === $payment_url ) {
		cvc_redirect_with_notice( $back, 'error', 'Không khởi tạo được thanh toán. Vui lòng thử lại sau.', null );
		return;
	}
	// phpcs:ignore WordPress.Security.SafeRedirect -- cổng VNPay (domain ngoài) hoặc trang QR nội bộ.
	wp_redirect( $payment_url );
	exit;
}

/** URL trang đề đã lưu trước khi sang VNPay (chỉ URL nội bộ). */
function cvc_pe_buy_back_url(): string {
	$u = isset( $_COOKIE[ CVC_PE_BACK_COOKIE ] ) ? esc_url_raw( wp_unslash( (string) $_COOKIE[ CVC_PE_BACK_COOKIE ] ) ) : '';

	return '' !== $u ? (string) wp_validate_redirect( $u, '' ) : '';
}
