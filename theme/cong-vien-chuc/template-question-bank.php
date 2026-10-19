<?php
/**
 * Ngân hàng câu hỏi (19/10/2026) - menu Ôn thi › Luyện tập.
 * Lọc theo Môn thi / Chủ đề / Văn bản pháp luật / Điều, chọn nguồn câu và số câu, rồi Ôn tập hoặc Thi thử
 * đúng nhóm câu đang lọc. Số liệu thật từ GET /api/question-bank/facets (chỉ câu đã duyệt, có đáp án đúng).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$f         = cvc_qb_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
$d         = cvc_qb_facets( $f );
$logged_in = cvc_is_logged_in();
$total     = (int) ( $d['total'] ?? 0 );
$all_total = (int) ( $d['all_total'] ?? 0 );
$docs      = (array) ( $d['documents'] ?? array() );
$filtered  = $f['mon'] || $f['chu-de'] || $f['van-ban'] || 'all' !== $f['nguon'];
$fmt       = static fn ( $n ): string => number_format( (int) $n, 0, ',', '.' );

cvc_seo_set_title( 'Ngân hàng câu hỏi ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Luyện ' . $fmt( $all_total ) . ' câu hỏi trắc nghiệm đã duyệt theo môn thi, chủ đề và từng Điều của văn bản pháp luật - ôn tập xem đáp án ngay hoặc thi thử tính giờ.' );
cvc_seo_set_canonical( cvc_question_bank_url() );
if ( $filtered || $f['dieu'] ) {
	cvc_seo_set_noindex();
}
if ( $logged_in ) {
	nocache_headers();
}

$sel = static function ( string $id, string $name, string $empty, array $rows, int $cur, bool $disabled = false ): void {
	echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-qb-f="' . esc_attr( $name ) . '"' . ( $disabled ? ' disabled' : '' ) . '>';
	echo '<option value="">' . esc_html( $empty ) . '</option>';
	foreach ( $rows as $r ) {
		echo '<option value="' . (int) $r['id'] . '"' . selected( (int) $r['id'], $cur, false ) . '>' . esc_html( $r['name'] . ' (' . number_format( (int) $r['count'], 0, ',', '.' ) . ')' ) . '</option>';
	}
	echo '</select>';
};

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
					array( 'label' => 'Ngân hàng câu hỏi' ),
				)
			);
			?>
			<div class="qb-hero-row">
				<div>
					<p class="qb-eyebrow">Ôn thi · Luyện tập</p>
					<h1>Ngân hàng câu hỏi</h1>
					<p class="qb-lead">Luyện theo môn, chủ đề và từng Điều của văn bản pháp luật. Chọn nhóm câu rồi <b>Ôn tập</b> (xem đáp án ngay) hoặc <b>Thi thử</b> (tính giờ) - cùng một ngân hàng câu hỏi đã duyệt.</p>
				</div>
				<div class="qb-hero-stat"><strong><?php echo esc_html( $fmt( $all_total ) ); ?></strong><span>câu hỏi đã duyệt, có đáp án và căn cứ</span><small><?php echo esc_html( count( $docs ) . ' văn bản có từ 5 câu trở lên' ); ?></small></div>
			</div>
		</div>
	</section>

	<div class="qb-wrap qb-main">
		<?php cvc_render_notice(); ?>
		<div class="qb-layout">
			<form class="qb-panel qb-filters" method="get" action="<?php echo esc_url( cvc_question_bank_url() ); ?>" data-qb-form>
				<h2>Chọn nhóm câu hỏi</h2>
				<div class="qb-field"><label for="qb-mon">Môn thi</label><?php $sel( 'qb-mon', 'mon', 'Tất cả môn thi', (array) ( $d['subjects'] ?? array() ), $f['mon'] ); ?></div>
				<div class="qb-field"><label for="qb-chude">Chủ đề</label><?php $sel( 'qb-chude', 'chu-de', 'Tất cả chủ đề', (array) ( $d['topics'] ?? array() ), $f['chu-de'] ); ?></div>
				<div class="qb-field"><label for="qb-vanban">Văn bản pháp luật</label><?php $sel( 'qb-vanban', 'van-ban', 'Tất cả văn bản', $docs, $f['van-ban'] ); ?></div>
				<div class="qb-field"><label for="qb-dieu">Điều khoản</label><?php $sel( 'qb-dieu', 'dieu', $f['van-ban'] ? 'Tất cả các Điều' : 'Chọn văn bản trước', (array) ( $d['articles'] ?? array() ), $f['dieu'], ! $f['van-ban'] ); ?></div>
				<fieldset class="qb-field"><legend>Nguồn câu</legend>
					<div class="qb-radios">
						<?php foreach ( CVC_QB_SOURCES as $k => $lbl ) : ?>
							<label class="qb-radio<?php echo 'all' !== $k && ! $logged_in ? ' is-auth' : ''; ?>"><input type="radio" name="nguon" value="<?php echo esc_attr( $k ); ?>" data-qb-f="nguon"<?php checked( $f['nguon'], $k ); ?><?php echo 'all' !== $k && ! $logged_in ? ' data-qb-auth' : ''; ?>> <?php echo esc_html( $lbl ); ?><?php echo 'all' !== $k && ! $logged_in ? ' <small>(cần đăng nhập)</small>' : ''; ?></label>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<fieldset class="qb-field"><legend>Số câu mỗi lượt</legend>
					<div class="qb-counts">
						<?php foreach ( CVC_QB_COUNTS as $c ) : ?>
							<label><input type="radio" name="so-cau" value="<?php echo (int) $c; ?>" data-qb-f="so-cau"<?php checked( $f['so-cau'], $c ); ?>><span><?php echo (int) $c; ?></span></label>
						<?php endforeach; ?>
					</div>
				</fieldset>
				<div class="qb-formfoot"><button type="submit" class="qb-btn-sm">Áp dụng bộ lọc</button><a href="<?php echo esc_url( cvc_question_bank_url() ); ?>">Xóa bộ lọc</a></div>
			</form>

			<div class="qb-content">
				<section class="qb-panel qb-summary" aria-live="polite">
					<p class="qb-sum-k">Nhóm câu đang lọc</p>
					<p class="qb-sum-n"><strong data-qb-total><?php echo esc_html( $fmt( $total ) ); ?></strong> câu hỏi</p>
					<p class="qb-sum-note" data-qb-note><?php echo 'all' === $f['nguon'] ? 'Mỗi lượt lấy ngẫu nhiên số câu đã chọn từ nhóm này.' : 'Nguồn "' . esc_html( CVC_QB_SOURCES[ $f['nguon'] ] ) . '" lọc thêm theo lịch sử làm bài của bạn khi bắt đầu.'; ?></p>
					<?php cvc_qb_start_form( $f, cvc_qb_url( $f ), 'all' === $f['nguon'] ? $total : max( 1, $total ) ); ?>
					<?php if ( ! $logged_in ) : ?><p class="qb-hint">Cần <a href="<?php echo esc_url( cvc_login_url( cvc_qb_url( $f ) ) ); ?>">đăng nhập</a> để lưu bài làm, kết quả và câu đã đánh dấu. Miễn phí.</p><?php endif; ?>
				</section>

				<section class="qb-panel">
					<div class="qb-head"><h2>Văn bản có nhiều câu hỏi nhất</h2><span>Bấm để luyện theo văn bản, rồi chọn từng Điều</span></div>
					<ol class="qb-doclist">
						<?php foreach ( array_slice( $docs, 0, 12 ) as $doc ) : ?>
							<li><a href="<?php echo esc_url( cvc_qb_url( array_merge( $f, array( 'van-ban' => (int) $doc['id'], 'dieu' => 0, 'chu-de' => 0 ) ) ) ); ?>"<?php echo (int) $doc['id'] === $f['van-ban'] ? ' aria-current="true"' : ''; ?>><span><?php echo esc_html( (string) $doc['name'] ); ?></span><b><?php echo esc_html( $fmt( $doc['count'] ) ); ?> câu</b></a></li>
						<?php endforeach; ?>
					</ol>
				</section>

				<section class="qb-panel qb-howto">
					<div class="qb-head"><h2>Ôn tập hay Thi thử?</h2></div>
					<div class="qb-modes">
						<div><b>✎ Ôn tập</b><p>Không tính giờ. Trả lời xong mỗi câu thấy ngay đúng/sai, giải thích và Điều luật làm căn cứ. Câu đã chấm được khóa để kết quả có ý nghĩa.</p></div>
						<div><b>◷ Thi thử</b><p>Đồng hồ do máy chủ kiểm soát (1 phút/câu), hết giờ tự nộp. Đáp án và giải thích hiện sau khi nộp, kèm phân tích chủ đề còn yếu.</p></div>
					</div>
					<p class="qb-links">Luyện theo đề có sẵn: <a href="<?php echo esc_url( cvc_exams_mode_url( 'on-tap' ) ); ?>">Ôn tập kiến thức</a> · <a href="<?php echo esc_url( cvc_exams_mode_url( 'thi-that' ) ); ?>">Thi thử như thi thật</a> · <a href="<?php echo esc_url( cvc_saved_questions_url() ); ?>">Câu hỏi đã lưu</a></p>
				</section>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();
