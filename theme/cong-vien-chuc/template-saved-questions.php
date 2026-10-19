<?php
/**
 * Câu hỏi đã lưu (19/10/2026) - menu Ôn thi › Luyện tập. Theo tài khoản: câu đã đánh dấu khi làm bài,
 * câu làm sai (sai >= đúng), câu cần ôn lại (từng sai, đã làm đúng lại). Dữ liệu GET /api/my-questions.
 * Từ mỗi nhóm có thể Ôn tập hoặc Thi thử ngay các câu đó (POST cvc_qb_start với nguồn tương ứng).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();
nocache_headers();
do_action( 'litespeed_control_set_nocache', 'cvc: câu hỏi đã lưu (theo tài khoản)' );

$tabs = array(
	'flagged' => array( '⚑ Đã đánh dấu', 'Câu bạn bấm "Đánh dấu xem lại" trong lúc làm bài.' ),
	'wrong'   => array( '✕ Làm sai', 'Câu bạn từng trả lời sai và chưa làm đúng nhiều hơn số lần sai.' ),
	'review'  => array( '↻ Cần ôn lại', 'Câu từng sai nhưng đã làm đúng lại - ôn thêm để nhớ lâu.' ),
);
$type  = sanitize_key( wp_unslash( (string) ( $_GET['loai'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
$type  = isset( $tabs[ $type ] ) ? $type : 'flagged';
$page  = max( 1, absint( $_GET['trang'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
$res   = ( new CVC_Api_Client( null, 15 ) )->get( '/api/my-questions', array( 'type' => $type, 'page' => $page, 'per_page' => 15 ), cvc_auth_token() );
if ( 401 === (int) ( $res['status'] ?? 0 ) ) {
	wp_safe_redirect( cvc_login_url( cvc_saved_questions_url( $type ) ) );
	exit;
}
$data   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
$counts = (array) ( $data['counts'] ?? array() );
$items  = (array) ( $data['items'] ?? array() );
$total  = (int) ( $data['total'] ?? 0 );
$last   = (int) ( $data['last_page'] ?? 1 );
$n_all  = (int) ( $counts[ $type ] ?? 0 );
$so_cau = 60;
foreach ( CVC_QB_COUNTS as $c ) {
	if ( $c >= $n_all ) {
		$so_cau = $c;
		break;
	}
}
$start_f = array( 'mon' => 0, 'chu-de' => 0, 'van-ban' => 0, 'dieu' => 0, 'nguon' => $type, 'so-cau' => $so_cau );

cvc_seo_set_title( 'Câu hỏi đã lưu' );
cvc_seo_set_noindex();
get_header();
?>
<main id="main" class="qb">
	<section class="qb-hero">
		<div class="qb-wrap">
			<?php
			cvc_render_breadcrumbs(
				array(
					array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
					array( 'label' => 'Ôn thi', 'url' => cvc_exams_url() ),
					array( 'label' => 'Câu hỏi đã lưu' ),
				)
			);
			?>
			<div class="qb-hero-row">
				<div>
					<p class="qb-eyebrow">Ôn thi · Luyện tập</p>
					<h1>Câu hỏi đã lưu</h1>
					<p class="qb-lead">Câu đánh dấu, câu sai và câu cần ôn lại - tự cập nhật sau mỗi lượt làm bài. Ôn tập hoặc thi thử lại đúng những câu này.</p>
				</div>
				<div class="qb-hero-stat"><strong><?php echo (int) ( ( $counts['flagged'] ?? 0 ) + ( $counts['wrong'] ?? 0 ) ); ?></strong><span>câu đánh dấu và câu sai cần xử lý</span><small><a href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">Xem kết quả &amp; tiến độ →</a></small></div>
			</div>
		</div>
	</section>

	<div class="qb-wrap qb-main">
		<?php cvc_render_notice(); ?>
		<nav class="qb-tabs" aria-label="Nhóm câu hỏi">
			<?php foreach ( $tabs as $k => $t ) : ?>
				<a href="<?php echo esc_url( cvc_saved_questions_url( $k ) ); ?>"<?php echo $k === $type ? ' class="is-on" aria-current="page"' : ''; ?>><?php echo esc_html( $t[0] ); ?> <b><?php echo (int) ( $counts[ $k ] ?? 0 ); ?></b></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( ! $res['ok'] ) : ?>
			<div class="qb-panel qb-empty"><b>Chưa tải được danh sách</b><p>Máy chủ đang bận, vui lòng tải lại trang sau ít phút.</p></div>
		<?php else : ?>
			<section class="qb-panel qb-savedbar">
				<div><p class="qb-sum-k"><?php echo esc_html( $tabs[ $type ][0] ); ?> · <?php echo (int) $n_all; ?> câu</p><p class="qb-sum-note"><?php echo esc_html( $tabs[ $type ][1] ); ?></p></div>
				<?php if ( $n_all > 0 ) : ?>
					<?php cvc_qb_start_form( $start_f, cvc_saved_questions_url( $type ), $n_all, 'is-row' ); ?>
				<?php endif; ?>
			</section>

			<?php if ( ! $items ) : ?>
				<div class="qb-panel qb-empty">
					<b>Chưa có câu nào trong nhóm này</b>
					<p><?php echo 'flagged' === $type ? 'Khi làm bài, bấm "⚑ Đánh dấu" ở câu chưa chắc - câu đó sẽ xuất hiện ở đây để ôn lại.' : ( 'wrong' === $type ? 'Sau khi nộp bài, các câu trả lời sai sẽ được đưa vào đây.' : 'Khi bạn làm đúng lại một câu từng sai, câu đó chuyển sang nhóm này.' ); ?></p>
					<p><a class="qb-btn-sm" href="<?php echo esc_url( cvc_question_bank_url() ); ?>">Mở Ngân hàng câu hỏi</a> <a class="qb-btn-sm is-ghost" href="<?php echo esc_url( cvc_exams_mode_url( 'thi-that' ) ); ?>">Thi thử một đề</a></p>
				</div>
			<?php else : ?>
				<ol class="qb-qlist" start="<?php echo (int) ( ( $page - 1 ) * 15 + 1 ); ?>">
					<?php foreach ( $items as $it ) : ?>
						<li class="qb-q">
							<p class="qb-qtext"><?php echo esc_html( (string) $it['text'] ); ?></p>
							<p class="qb-qmeta">
								<?php if ( ! empty( $it['article'] ) ) : ?><a href="<?php echo esc_url( home_url( $it['article']['url'] ) ); ?>" target="_blank" rel="noopener">§ <?php echo esc_html( $it['article']['label'] . ' — ' . $it['article']['document'] ); ?></a><?php endif; ?>
								<?php if ( $it['incorrect_count'] || $it['correct_count'] ) : ?><span>Sai <?php echo (int) $it['incorrect_count']; ?> · Đúng <?php echo (int) $it['correct_count']; ?></span><?php endif; ?>
								<?php if ( ! empty( $it['last_attempted_at'] ) ) : ?><span>Lần cuối <?php echo esc_html( wp_date( 'd/m/Y', strtotime( (string) $it['last_attempted_at'] ) ) ); ?></span><?php endif; ?>
							</p>
							<details class="qb-ans">
								<summary>Xem đáp án &amp; giải thích</summary>
								<ul>
									<?php foreach ( (array) $it['options'] as $o ) : ?>
										<li<?php echo $o['key'] === $it['correct_key'] ? ' class="is-right"' : ''; ?>><b><?php echo esc_html( $o['key'] ); ?>.</b> <?php echo esc_html( (string) $o['text'] ); ?><?php echo $o['key'] === $it['correct_key'] ? ' <em>✓ Đáp án đúng</em>' : ''; ?></li>
									<?php endforeach; ?>
								</ul>
								<?php if ( ! empty( $it['explanation'] ) ) : ?><p class="qb-expl"><?php echo nl2br( esc_html( (string) $it['explanation'] ) ); ?></p><?php endif; ?>
							</details>
						</li>
					<?php endforeach; ?>
				</ol>
				<?php if ( $last > 1 ) : ?>
					<nav class="qb-pages" aria-label="Phân trang">
						<?php if ( $page > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( 'trang', $page - 1, cvc_saved_questions_url( $type ) ) ); ?>" rel="prev">← Trang trước</a><?php endif; ?>
						<span aria-current="page">Trang <?php echo (int) $page; ?>/<?php echo (int) $last; ?></span>
						<?php if ( $page < $last ) : ?><a href="<?php echo esc_url( add_query_arg( 'trang', $page + 1, cvc_saved_questions_url( $type ) ) ); ?>" rel="next">Trang sau →</a><?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
