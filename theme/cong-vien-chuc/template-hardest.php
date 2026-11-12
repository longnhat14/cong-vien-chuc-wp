<?php
/**
 * /cau-hay-sai-nhat/ - "Câu hay sai nhất" từ dữ liệu làm bài ẩn danh (12/11/2026).
 * ?mon={exam_subject_id} | ?van-ban={legal_document_id}. Chỉ câu có từ 8 người làm; câu dưới 10% đúng không hiện
 * công khai (có thể sai đáp án - quản trị rà soát). Dữ liệu: GET /api/reports/hardest-questions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hq_mon = absint( $_GET['mon'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
$hq_vb  = absint( $_GET['van-ban'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
$hq_res = ( new CVC_Api_Client( null, 25 ) )->get( '/api/reports/hardest-questions', array_filter( array( 'mon' => $hq_mon ?: null, 'van_ban' => $hq_vb ?: null ) ) );
$d      = $hq_res['ok'] ? (array) ( $hq_res['data']['data'] ?? array() ) : array();
$groups = (array) ( $d['groups'] ?? array() );
$label  = 'tất cả môn';
foreach ( (array) ( $groups['subjects'] ?? array() ) as $g ) {
	if ( (int) $g['id'] === $hq_mon ) {
		$label = (string) $g['name'];
	}
}
foreach ( (array) ( $groups['documents'] ?? array() ) as $g ) {
	if ( (int) $g['id'] === $hq_vb ) {
		$label = (string) $g['number'];
	}
}
cvc_seo_set_title( ( $hq_mon || $hq_vb ? 'Câu hay sai nhất: ' . $label : 'Câu hay sai nhất khi ôn thi công chức, viên chức' ) . ' | Công Viên Chức' );
cvc_seo_set_description( 'Những câu trắc nghiệm thí sinh trả lời sai nhiều nhất trên Công Viên Chức - tỷ lệ đúng, đáp án hay bị chọn nhầm và giải thích kèm căn cứ pháp lý.' );
if ( $hq_mon && $hq_vb ) {
	cvc_seo_set_noindex();
}
$hq_url = static fn ( array $q = array() ): string => add_query_arg( $q, home_url( '/cau-hay-sai-nhat/' ) );

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5 rm hq">
	<?php cvc_reports_css(); ?>
	<header class="rm-head">
		<p class="rm-kicker">Dữ liệu làm bài ẩn danh · cập nhật mỗi 6 giờ</p>
		<h1><?php echo esc_html( $hq_mon || $hq_vb ? 'Câu hay sai nhất: ' . $label : 'Câu hay sai nhất khi ôn thi công chức, viên chức' ); ?></h1>
		<p>Những câu thí sinh trên Công Viên Chức trả lời sai nhiều nhất (mỗi câu có từ <?php echo (int) ( $d['min_users'] ?? 8 ); ?> người làm). Xem đáp án hay bị chọn nhầm để tránh mất điểm oan.</p>
	</header>
	<nav class="dz-chips" aria-label="Chọn nhóm">
		<a class="<?php echo ! $hq_mon && ! $hq_vb ? 'is-active font-bold' : ''; ?>" href="<?php echo esc_url( $hq_url() ); ?>">Tất cả</a>
		<?php foreach ( (array) ( $groups['subjects'] ?? array() ) as $g ) : ?><a class="<?php echo (int) $g['id'] === $hq_mon ? 'is-active font-bold' : ''; ?>" href="<?php echo esc_url( $hq_url( array( 'mon' => (int) $g['id'] ) ) ); ?>"><?php echo esc_html( (string) $g['name'] ); ?></a><?php endforeach; ?>
	</nav>
	<?php if ( ! empty( $groups['documents'] ) ) : ?>
		<form method="get" action="<?php echo esc_url( $hq_url() ); ?>" class="rm-filter"><label for="hq-vb">Theo văn bản</label>
			<select id="hq-vb" name="van-ban" onchange="this.form.submit()"><option value="">Tất cả văn bản</option>
				<?php foreach ( (array) $groups['documents'] as $g ) : ?><option value="<?php echo (int) $g['id']; ?>" <?php selected( $hq_vb, (int) $g['id'] ); ?>><?php echo esc_html( $g['number'] . ' - ' . wp_trim_words( (string) $g['title'], 10 ) ); ?></option><?php endforeach; ?>
			</select><noscript><button type="submit">Xem</button></noscript></form>
	<?php endif; ?>
	<?php if ( empty( $d['items'] ) ) : ?>
		<div class="dz-empty"><p>Chưa đủ dữ liệu cho nhóm này (cần từ <?php echo (int) ( $d['min_users'] ?? 8 ); ?> người làm mỗi câu).</p></div>
	<?php else : ?>
		<ol class="hq-list">
			<?php foreach ( (array) $d['items'] as $i => $q ) : ?>
				<li class="rm-card hq-item">
					<p class="hq-meta"><b><?php echo (int) $q['correct_rate']; ?>% trả lời đúng</b> · <?php echo (int) $q['people']; ?> người làm<?php echo ! empty( $q['subject'] ) ? ' · ' . esc_html( (string) $q['subject'] ) : ''; ?></p>
					<p class="hq-q"><?php echo esc_html( (string) $q['text'] ); ?></p>
					<ul class="hq-opts">
						<?php foreach ( (array) $q['options'] as $o ) : ?>
							<li class="<?php echo $o['correct'] ? 'is-right' : ''; ?>"><span class="hq-k"><?php echo esc_html( (string) $o['key'] ); ?></span><span class="hq-t"><?php echo esc_html( (string) $o['text'] ); ?><?php echo $o['correct'] ? ' <em>(đáp án đúng)</em>' : ''; ?></span><span class="hq-p" title="Tỷ lệ chọn"><i style="width:<?php echo (int) $o['picked']; ?>%"></i><b><?php echo (int) $o['picked']; ?>%</b></span></li>
						<?php endforeach; ?>
					</ul>
					<?php if ( ! empty( $q['explanation'] ) ) : ?><details class="hq-exp"><summary>Giải thích</summary><p><?php echo esc_html( (string) $q['explanation'] ); ?></p></details><?php endif; ?>
					<?php if ( ! empty( $q['doc_slug'] ) ) : ?><p class="rm-note">Căn cứ: <a href="<?php echo esc_url( cvc_legal_document_url( (string) $q['doc_slug'] ) ); ?>"><?php echo esc_html( (string) ( $q['source'] ?: $q['doc_number'] ) ); ?></a></p><?php elseif ( ! empty( $q['source'] ) ) : ?><p class="rm-note">Căn cứ: <?php echo esc_html( (string) $q['source'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<div class="rm-card"><p><b>Ôn lại những câu dễ nhầm mỗi ngày:</b> Bài ôn hôm nay tự đưa câu bạn từng sai quay lại cho tới khi thuộc.</p><a class="dz-btn dz-btn-primary" href="<?php echo esc_url( home_url( '/on-hom-nay/' ) ); ?>">Ôn hôm nay</a></div>
	<?php endif; ?>
	<p class="rm-note">Tỷ lệ tính trên các lượt trả lời trong luyện thi và thi thử, không gắn với tên người làm. Câu có tỷ lệ đúng dưới 10% được tạm ẩn để biên tập viên kiểm tra lại đáp án.</p>
	<?php if ( function_exists( 'cvc_render_blog_related' ) ) { cvc_render_blog_related( 'ôn thi công chức viên chức kiến thức chung câu hỏi trắc nghiệm ' . $label, 'Bài hướng dẫn ôn thi', 4 ); } ?>
</div>
</main>
<?php
get_footer();
