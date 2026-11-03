<?php
/**
 * Thu phí theo tính năng - phía người dùng (03/11/2026).
 * - /goi-on-thi/?nguon={feature}&tpl={mẫu đề}&back={url}: trang mở khóa chung (gói bộ đề theo tin + gói tháng),
 *   dùng cho mọi hộp "Mở khóa" (học bộ đề PDF, ôn thêm Sổ câu sai, AI Coach...). Không đặt nonce trong trang có cache.
 * - Bật/tắt từng tính năng: Quản trị → Cấu hình → Thu phí (inc/admin/paywall.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_PAYWALL_FEATURES = array( 'exam_practice', 'pdf_learn', 'pdf_download', 'exam_review', 'ai_coach' );

function cvc_paywall_url( string $feature, string $back = '', int $tpl = 0, int $rid = 0 ): string {
	return add_query_arg(
		array_filter(
			array(
				'nguon' => $feature,
				'tpl'   => $tpl ?: null,
				'rid'   => $rid ?: null,
				'back'  => '' !== $back ? rawurlencode( $back ) : null,
			)
		),
		home_url( '/goi-on-thi/' )
	);
}

/** Xóa cache API công khai (trang bộ đề...) sau khi đổi cấu hình thu phí. */
function cvc_paywall_flush(): void {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_cvc\\_ci\\_%' OR option_name LIKE '\\_transient\\_timeout\\_cvc\\_ci\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	do_action( 'litespeed_purge_all' );
}

add_action(
	'template_redirect',
	static function (): void {
		$path = untrailingslashit( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ) ); // phpcs:ignore
		if ( '/goi-on-thi' !== $path ) {
			return;
		}
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'cvc: gói ôn thi theo người dùng' );
		status_header( 200 );
		global $wp_query;
		$wp_query->is_404 = false;
		// phpcs:disable WordPress.Security.NonceVerification
		$feature = sanitize_key( wp_unslash( (string) ( $_GET['nguon'] ?? '' ) ) );
		$feature = in_array( $feature, CVC_PAYWALL_FEATURES, true ) ? $feature : 'exam_practice';
		$tpl     = absint( $_GET['tpl'] ?? 0 );
		$rid     = absint( $_GET['rid'] ?? 0 );
		$back    = wp_validate_redirect( esc_url_raw( rawurldecode( wp_unslash( (string) ( $_GET['back'] ?? '' ) ) ) ), home_url( '/' ) );
		// phpcs:enable
		$token = cvc_auth_token();
		$res   = ( new CVC_Api_Client( null, 10 ) )->get( '/api/paywall/offer', array_filter( array( 'feature' => $feature, 'tpl' => $tpl ?: null, 'rid' => $rid ?: null ) ), $token );
		$o     = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
		$self  = cvc_paywall_url( $feature, $back, $tpl, $rid );
		$money = static fn ( $n ) => number_format( (float) $n, 0, ',', '.' ) . ' ₫';
		$pack  = is_array( $o['pack'] ?? null ) ? $o['pack'] : null;
		$days  = (int) ( $o['pass_days'] ?? 30 );
		cvc_seo_set_title( 'Gói ôn thi Công Viên Chức' );
		cvc_seo_set_noindex();
		wp_enqueue_style( 'cvc-paywall', get_template_directory_uri() . '/assets/css/paywall.css', array(), (string) filemtime( get_template_directory() . '/assets/css/paywall.css' ) );
		get_header();
		?>
		<main id="main" class="dz-main">
		<div class="dz-wrap pw" style="max-width:860px">
			<?php cvc_render_notice(); ?>
			<p class="pw-kicker"><i class="fa-solid fa-lock-open" aria-hidden="true"></i> Gói ôn thi</p>
			<h1 class="pw-h1"><?php echo esc_html( ! empty( $o['label'] ) ? 'Mở khóa: ' . $o['label'] : 'Gói ôn thi' ); ?></h1>
			<?php if ( ! empty( $o['desc'] ) ) : ?><p class="pw-lead"><?php echo esc_html( (string) $o['desc'] ); ?></p><?php endif; ?>

			<?php if ( ! $res['ok'] ) : ?>
				<p class="pw-note is-warn">Không tải được thông tin gói, vui lòng thử lại sau.</p>
			<?php elseif ( ! empty( $o['unlocked'] ) ) : ?>
				<div class="pw-ok">
					<b><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php echo esc_html( empty( $o['active'] ) ? 'Tính năng này đang miễn phí' : 'Bạn đã mở khóa tính năng này' ); ?></b>
					<?php if ( ! empty( $o['pass_expires_at'] ) ) : ?><p>Gói tháng còn hạn đến <?php echo esc_html( wp_date( 'd/m/Y', strtotime( (string) $o['pass_expires_at'] ) ) ); ?>.</p><?php endif; ?>
					<a class="pw-btn is-primary" href="<?php echo esc_url( $back ); ?>">Quay lại học tiếp</a>
				</div>
			<?php else : ?>
				<div class="pw-grid">
					<?php if ( $pack ) : ?>
						<section class="pw-card">
							<h2><?php echo esc_html( 'Bộ đề theo tin' ); ?></h2>
							<p class="pw-price"><?php echo esc_html( $money( $o['prices']['pack'] ?? 0 ) ); ?> <small>dùng mãi</small></p>
							<p class="pw-desc"><?php echo esc_html( (string) $pack['title'] ); ?></p>
							<ul class="pw-list"><li><?php echo (int) $pack['exams']; ?> đề × <?php echo (int) $pack['questions']; ?> câu, có đáp án, giải thích, căn cứ</li><li>Học trên trang, luyện và thi thử online</li><li>Tin khác dùng cùng bộ đề tự mở</li></ul>
							<?php cvc_paywall_buy_form( 'exam_template', (int) $pack['template_id'], $feature, $back, $token, $self, 'Mua bộ đề', false ); ?>
						</section>
					<?php endif; ?>
					<section class="pw-card is-best">
						<p class="pw-tag">Mở mọi tính năng</p>
						<h2>Gói <?php echo (int) $days; ?> ngày</h2>
						<p class="pw-price"><?php echo esc_html( $money( $o['prices']['pass'] ?? 0 ) ); ?> <small>/ <?php echo (int) $days; ?> ngày</small></p>
						<ul class="pw-list"><li>Mọi bộ đề cá nhân hóa theo tin, học trên trang và thi thử</li><li>Tải PDF có đáp án</li><li>Ôn thêm không giới hạn từ Sổ câu sai</li><li>AI Coach tới 40 câu/ngày</li><li>Mua tiếp được cộng dồn ngày</li></ul>
						<?php cvc_paywall_buy_form( 'exam_pass', 0, $feature, $back, $token, $self, 'Mua gói ' . $days . ' ngày', true ); ?>
					</section>
				</div>
				<p class="pw-note">Thanh toán chuyển khoản VietQR hoặc VNPay; quyền được kích hoạt ngay khi thanh toán thành công. Vẫn dùng miễn phí được: Ôn nhanh 10 câu, Bài ôn hôm nay, đề 1 của mỗi bộ đề.</p>
			<?php endif; ?>
			<p><a class="pw-back" href="<?php echo esc_url( $back ); ?>">← Quay lại</a></p>
		</div>
		</main>
		<?php
		get_footer();
		exit;
	},
	1
);

function cvc_paywall_buy_form( string $type, int $id, string $feature, string $back, ?string $token, string $self, string $label, bool $primary ): void {
	if ( null === $token ) {
		echo '<a class="pw-btn' . ( $primary ? ' is-primary' : '' ) . '" rel="nofollow" href="' . esc_url( cvc_login_url( $self ) ) . '">Đăng nhập để mua</a>';
		return;
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_exam_buy' ); ?>
		<input type="hidden" name="action" value="cvc_exam_buy">
		<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
		<input type="hidden" name="id" value="<?php echo (int) $id; ?>">
		<input type="hidden" name="source" value="<?php echo esc_attr( $feature ); ?>">
		<input type="hidden" name="back" value="<?php echo esc_attr( $back ); ?>">
		<button type="submit" class="pw-btn<?php echo $primary ? ' is-primary' : ''; ?>"><?php echo esc_html( $label ); ?></button>
	</form>
	<?php
}
