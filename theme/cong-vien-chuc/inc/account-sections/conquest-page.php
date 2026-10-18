<?php
/**
 * Trang "Hồ sơ chinh phục kỳ thi" - /tai-khoan/ho-so-chinh-phuc/ (gọi từ template-account.php).
 * Bố cục toàn chiều rộng theo thiết kế congvienchuc-ho-so-chinh-phuc-v1; thanh mục tài khoản nằm dưới hero.
 *
 * @var array  $section_titles Nhãn các mục tài khoản (template-account.php).
 * @var string $section        Mục hiện tại.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_cq_goal = absint( $_GET['ho-so'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
$cvc_cq_res  = cvc_cq_get( $cvc_cq_goal );

get_header();
?>
<main id="main" class="cq">
	<section class="cq-hero">
		<div class="cq-wrap cq-hero-in">
			<?php
			cvc_render_breadcrumbs(
				array(
					array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
					array( 'label' => 'Tài khoản', 'url' => cvc_account_url() ),
					array( 'label' => 'Hồ sơ chinh phục kỳ thi' ),
				)
			);
			?>
			<div class="cq-hero-row">
				<div>
					<div class="cq-eyebrow">● Trung tâm điều hành ôn thi của bạn</div>
					<h1>Hồ sơ chinh phục kỳ thi</h1>
					<p>Một nơi quản lý cơ hội tuyển dụng, tài liệu cần học, đề thi phù hợp và tiến độ chuẩn bị — theo đúng mục tiêu của bạn.</p>
				</div>
				<div class="cq-actions">
					<button type="button" class="cq-btn is-primary" data-cq-open="add">＋ Thêm hồ sơ</button>
					<button type="button" class="cq-btn" data-cq="export">⇩ Xuất tóm tắt</button>
				</div>
			</div>
			<nav class="cq-accnav" aria-label="Mục tài khoản">
				<?php foreach ( cvc_account_primary_sections() as $cvc_key ) : ?>
					<a href="<?php echo esc_url( cvc_account_url( $cvc_key ) ); ?>"<?php echo $cvc_key === $section ? ' class="is-cur" aria-current="page"' : ''; ?>><?php echo esc_html( $section_titles[ $cvc_key ] ?? $cvc_key ); ?></a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( cvc_account_url( 'recruitment-matches' ) ); ?>"><?php echo esc_html( $section_titles['recruitment-matches'] ?? 'Việc làm phù hợp' ); ?></a>
				<a href="<?php echo esc_url( cvc_account_url( 'notifications' ) ); ?>"><?php echo esc_html( $section_titles['notifications'] ?? 'Thông báo' ); ?></a>
			</nav>
		</div>
	</section>

	<div class="cq-wrap cq-main">
		<div id="cq-root" aria-live="polite">
			<?php cvc_cq_render_body( $cvc_cq_res ); ?>
		</div>
	</div>

	<div class="cq-modalbg" id="cq-modal" aria-hidden="true">
		<div class="cq-modal" role="dialog" aria-modal="true" aria-labelledby="cq-mtitle">
			<div class="cq-modalhead"><h3 id="cq-mtitle">Hồ sơ</h3><button type="button" class="cq-btn is-sm" data-cq-close aria-label="Đóng">✕</button></div>
			<div class="cq-modalbody" id="cq-mbody"></div>
			<div class="cq-modalfoot" id="cq-mfoot"><button type="button" class="cq-btn" data-cq-close>Hủy</button><button type="button" class="cq-btn is-primary" id="cq-msave">Lưu</button></div>
		</div>
	</div>
	<div class="cq-toast" id="cq-toast" role="status" aria-live="polite"></div>
</main>
<?php
get_footer();
