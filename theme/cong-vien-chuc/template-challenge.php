<?php
/**
 * Lời thách đấu - /thach/{MA}/ (12/11/2026): trang đích khi bạn bè bấm link chia sẻ (Facebook / Zalo xem trước bằng ảnh thẻ).
 * noindex; nút "Làm đề này" dẫn tới trang đề (banner thách đấu + so điểm ở trang kết quả).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tc_code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) get_query_var( 'cvc_thach_code' ) ) );
$tc      = function_exists( 'cvc_growth_challenge' ) ? cvc_growth_challenge( $tc_code ) : array();
$tc_ok   = ! empty( $tc['exam']['slug'] );
$tc_pct  = $tc_ok ? cvc_growth_pct( $tc['percentage'] ) : '';
$tc_url  = $tc_ok ? add_query_arg( array_filter( array( 'thach' => $tc_code, 'ref' => (string) ( $tc['ref'] ?? '' ) ) ), cvc_exam_url( (string) $tc['exam']['slug'] ) ) : cvc_exams_url();

if ( ! $tc_ok ) {
	status_header( 404 );
}
cvc_seo_set_noindex();
cvc_seo_set_title( $tc_ok ? $tc['name'] . ' thách bạn: ' . $tc_pct . ' đề ' . $tc['exam']['title'] : 'Lời thách đấu không còn' );
cvc_seo_set_description( $tc_ok ? $tc['name'] . ' đạt ' . $tc_pct . ' (' . (int) $tc['correct'] . '/' . (int) $tc['total'] . ' câu đúng). Làm cùng đề trắc nghiệm này trên Công Viên Chức và so điểm - miễn phí.' : '' );
if ( $tc_ok ) {
	cvc_seo_set_og( array( 'image' => cvc_public_api_url() . '/api/challenges/' . rawurlencode( $tc_code ) . '/card', 'url' => home_url( '/thach/' . $tc_code . '/' ) ) );
}

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5" style="max-width:760px">
	<?php if ( ! $tc_ok ) : ?>
		<div class="dz-panel"><h1 class="text-xl font-black">Lời thách đấu không còn</h1><p>Đề có thể đã được cập nhật. <a class="text-acc font-bold" href="<?php echo esc_url( cvc_exams_url() ); ?>">Xem các đề thi trắc nghiệm</a>.</p></div>
	<?php else : ?>
		<section class="dz-panel space-y-3" style="text-align:center">
			<p class="text-xs font-black uppercase tracking-wider text-acc">⚔ Thách bạn làm cùng đề</p>
			<h1 class="text-2xl font-black text-ink"><?php echo esc_html( (string) $tc['name'] ); ?> đạt <?php echo esc_html( $tc_pct ); ?></h1>
			<p class="text-ink-2"><?php echo esc_html( (int) $tc['correct'] . '/' . (int) $tc['total'] . ' câu đúng · đề "' . (string) $tc['exam']['title'] . '"' ); ?><?php echo (int) ( $tc['taken'] ?? 0 ) > 0 ? esc_html( ' · ' . (int) $tc['taken'] . ' người đã nhận lời' ) : ''; ?></p>
			<img src="<?php echo esc_url( cvc_public_api_url() . '/api/challenges/' . rawurlencode( $tc_code ) . '/card' ); ?>" alt="" width="1200" height="630" style="width:100%;height:auto;border-radius:14px">
			<p><a class="inline-block px-6 py-3 rounded-xl bg-acc text-white font-black" href="<?php echo esc_url( $tc_url ); ?>" data-cvc-go="th-exam">Làm đề này và so điểm →</a></p>
			<p class="text-xs text-ink-muted">Miễn phí. Có đáp án, giải thích và căn cứ pháp luật sau mỗi câu. Đăng ký tài khoản để lưu kết quả và so điểm.</p>
		</section>
		<?php if ( function_exists( 'cvc_side_feed' ) ) { cvc_side_feed( array( 'slot' => 'th', 'text' => (string) $tc['exam']['title'], 'blocks' => array( 'fame', 'blog' ) ), 'band' ); } ?>
	<?php endif; ?>
</div>
</main>
<?php
get_footer();
