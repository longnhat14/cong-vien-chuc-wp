<?php
/**
 * /bo-de-pdf/{slug}/ - Trang đích một bộ đề PDF theo tin tuyển dụng (27/10/2026). Xem inc/pdf-sets.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_title( (string) get_query_var( 'cvc_pdf_slug' ) );
$d    = '' !== $slug ? cvc_pdf_api( '/api/pdf-sets/' . $slug, array(), 600 ) : array();
$set  = is_array( $d['set'] ?? null ) ? $d['set'] : null;

if ( null === $set ) {
	status_header( 404 );
	cvc_seo_set_title( 'Không tìm thấy bộ đề' );
	cvc_seo_set_noindex();
	get_header();
	echo '<main id="main" class="pds"><div class="pds-wrap"><section class="pds-hero"><div><h1>Không tìm thấy bộ đề</h1><p class="pds-lead">Bộ đề có thể đã được cập nhật. <a href="' . esc_url( cvc_pdf_sets_url() ) . '">Xem danh mục bộ đề PDF</a>.</p></div></section></div></main>';
	get_footer();
	return;
}

$exams    = array_values( array_filter( (array) ( $d['exams'] ?? array() ), 'is_array' ) );
$preview  = array_values( array_filter( (array) ( $d['preview'] ?? array() ), 'is_array' ) );
$sections = array_values( array_filter( (array) ( $d['sections'] ?? array() ), 'is_array' ) );
$related  = array_values( array_filter( (array) ( $d['related'] ?? array() ), 'is_array' ) );
$pos      = array_values( array_filter( (array) ( $set['positions'] ?? array() ), 'is_string' ) );
$rid      = (int) ( $set['recruitment_id'] ?? 0 );
$group    = (string) ( $set['group'] ?? '' );
$rec_url  = cvc_recruitment_url( (string) ( $set['recruitment_slug'] ?? '' ) ) . '#de-rieng';
$url      = cvc_pdf_set_url( $slug );
$cover    = (string) ( $set['cover'] ?? cvc_pdf_cover_url( 'set', $slug ) );
$label    = (string) ( $set['label'] ?? '' );
$n_q      = (int) ( $set['questions'] ?? 0 );
$n_e      = (int) ( $set['exams'] ?? count( $exams ) );
$fmt_date = static fn ( ?string $d ): string => $d ? wp_date( 'd/m/Y', (int) strtotime( $d ) ) : '';
$desc     = 'Tải miễn phí ' . $n_e . ' đề trắc nghiệm (' . $n_q . ' câu) ôn thi ' . ( '' !== $label ? $label . ' ' : '' ) . 'theo tin "' . (string) ( $set['recruitment_short'] ?? $set['recruitment_title'] ?? '' ) . '". PDF có đáp án, giải thích, căn cứ Điều – khoản và mã QR làm bài online.';

$crumbs = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Bộ đề PDF', 'url' => cvc_pdf_sets_url() ),
	array( 'label' => (string) $set['title'] ),
);
cvc_seo_set_title( mb_strimwidth( (string) $set['title'], 0, 82, '…' ) . ' (PDF có đáp án)' );
cvc_seo_set_description( cvc_seo_trim( $desc, 160 ) );
cvc_seo_set_canonical( $url );
cvc_seo_set_og( array( 'image' => $cover, 'type' => 'website' ) );
cvc_seo_add_breadcrumb_jsonld( array_map( static fn ( $c ) => $c + array( 'url' => $url ), $crumbs ) );
cvc_seo_add_json_ld(
	array(
		'@context'            => 'https://schema.org',
		'@type'               => 'LearningResource',
		'name'                => (string) $set['title'],
		'description'         => $desc,
		'url'                 => $url,
		'image'               => $cover,
		'inLanguage'          => 'vi',
		'learningResourceType' => 'Bộ đề trắc nghiệm',
		'educationalLevel'    => 'Thi tuyển công chức, viên chức',
		'isAccessibleForFree' => true,
		'encodingFormat'      => 'application/pdf',
		'dateModified'        => (string) ( $set['updated_at'] ?? '' ),
		'numberOfItems'       => $n_q,
		'provider'            => array( '@type' => 'Organization', 'name' => 'Công Viên Chức', 'url' => home_url( '/' ), 'logo' => cvc_brand_url( 'icon-512.png' ) ),
		'about'               => array_values( array_filter( array( $label, (string) ( $set['province'] ?? '' ) ) ) ),
	)
);
cvc_pdf_assets();

get_header();
?>
<main id="main" class="pds pds-detail">
	<div class="pds-wrap">
		<?php cvc_render_breadcrumbs( $crumbs ); ?>

		<section class="pds-dhero">
			<div class="pds-dhero-copy">
				<p class="pds-eyebrow">BỘ ĐỀ PDF THEO TIN TUYỂN DỤNG<?php echo ! empty( $set['is_open'] ) ? ' · ĐANG NHẬN HỒ SƠ' : ''; ?></p>
				<h1><?php echo esc_html( (string) $set['title'] ); ?></h1>
				<p class="pds-lead"><?php echo esc_html( $desc ); ?></p>
				<ul class="pds-chips">
					<li><?php echo (int) $n_e; ?> đề · <?php echo (int) $n_q; ?> câu</li>
					<?php if ( ! empty( $set['minutes'] ) ) : ?><li><?php echo (int) $set['minutes']; ?> phút làm bài</li><?php endif; ?>
					<?php if ( ! empty( $set['province'] ) ) : ?><li><?php echo esc_html( (string) $set['province'] ); ?></li><?php endif; ?>
					<?php if ( ! empty( $set['deadline'] ) ) : ?><li>Hạn nộp hồ sơ <?php echo esc_html( $fmt_date( (string) $set['deadline'] ) ); ?></li><?php endif; ?>
					<?php if ( (int) ( $set['downloads'] ?? 0 ) > 0 ) : ?><li><?php echo (int) $set['downloads']; ?> lượt tải</li><?php endif; ?>
				</ul>
				<div class="pds-dhero-actions">
					<?php echo cvc_pdf_button( 'recruitment', $rid, array( 'group' => $group, 'label' => 'Tải bộ đề PDF (có đáp án)', 'variant' => 'dap-an' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo cvc_pdf_button( 'recruitment', $rid, array( 'group' => $group, 'label' => 'Bản đề thi + phiếu tô', 'variant' => 'de', 'class' => 'is-ghost' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<a class="pds-link" href="<?php echo esc_url( $rec_url ); ?>">Làm đề online ›</a>
				</div>
				<p class="pds-note">Xem trước miễn phí · đăng nhập (miễn phí) để tải · file có mã QR làm bài và chấm điểm trực tuyến.</p>
			</div>
			<figure class="pds-cover">
				<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( 'Ảnh bìa ' . $set['title'] ); ?>" width="360" height="360" loading="eager" decoding="async">
				<figcaption>
					<a href="<?php echo esc_url( $cover ); ?>" target="_blank" rel="noopener">Mở ảnh chia sẻ</a>
					<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ); ?>" target="_blank" rel="noopener">Chia sẻ Facebook</a>
					<button type="button" class="pds-copy" data-copy="<?php echo esc_attr( $url . '?utm_source=share' ); ?>">Sao chép link</button>
				</figcaption>
			</figure>
		</section>

		<div class="pds-cols">
			<div class="pds-main">
				<section class="pds-box" aria-labelledby="pds-ex-h">
					<h2 id="pds-ex-h">Các đề trong bộ</h2>
					<ol class="pds-exams">
						<?php foreach ( $exams as $ex ) : ?>
							<li>
								<a href="<?php echo esc_url( cvc_exam_url( (string) $ex['slug'] ) ); ?>"><?php echo esc_html( (string) $ex['title'] ); ?></a>
								<small><?php echo (int) $ex['total_questions']; ?> câu<?php echo (int) $ex['duration_minutes'] > 0 ? ' · ' . (int) $ex['duration_minutes'] . ' phút' : ''; ?> · <?php echo esc_html( (string) $ex['code'] ); ?></small>
								<?php echo cvc_pdf_button( 'exam', (int) $ex['id'], array( 'label' => 'PDF đề này', 'variant' => 'dap-an', 'class' => 'is-small is-ghost' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</section>

				<?php if ( $preview ) : ?>
					<section class="pds-box" aria-labelledby="pds-pv-h">
						<h2 id="pds-pv-h">Xem trước <?php echo count( $preview ); ?> câu đầu của đề 1</h2>
						<?php foreach ( $preview as $qq ) : ?>
							<div class="pds-q">
								<p><b>Câu <?php echo (int) $qq['n']; ?>.</b> <?php echo esc_html( (string) $qq['text'] ); ?></p>
								<ul><?php foreach ( (array) $qq['options'] as $o ) : ?><li><b><?php echo esc_html( (string) $o['key'] ); ?>.</b> <?php echo esc_html( (string) $o['text'] ); ?></li><?php endforeach; ?></ul>
								<details class="pds-ans"><summary>Xem đáp án</summary><div><b>Đáp án: <?php echo esc_html( (string) $qq['answer'] ); ?></b><?php echo '' !== (string) $qq['explanation'] ? ' – ' . esc_html( (string) $qq['explanation'] ) : ''; ?><?php if ( '' !== (string) $qq['ref'] ) : ?><br><i>Căn cứ: <?php echo esc_html( (string) $qq['ref'] ); ?></i><?php endif; ?></div></details>
							</div>
						<?php endforeach; ?>
						<p class="pds-more">Còn <?php echo max( 0, $n_q - count( $preview ) ); ?> câu trong file PDF. <?php echo cvc_pdf_button( 'recruitment', $rid, array( 'group' => $group, 'label' => 'Tải trọn bộ', 'variant' => 'dap-an', 'class' => 'is-small' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
					</section>
				<?php endif; ?>

				<?php if ( $sections ) : ?>
					<section class="pds-box" aria-labelledby="pds-doc-h">
						<h2 id="pds-doc-h">Văn bản được dùng để ra đề</h2>
						<?php foreach ( $sections as $sec ) : ?>
							<h3 class="pds-h3"><?php echo esc_html( (string) $sec['label'] ); ?><?php echo ! empty( $sec['weight'] ) ? ' <small>(' . (int) $sec['weight'] . '% số câu)</small>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
							<ul class="pds-docs">
								<?php foreach ( (array) ( $sec['documents'] ?? array() ) as $doc ) : ?>
									<li><?php if ( ! empty( $doc['slug'] ) ) : ?><a href="<?php echo esc_url( cvc_legal_document_url( (string) $doc['slug'] ) ); ?>"><?php echo esc_html( (string) $doc['number'] ); ?></a><?php else : ?><b><?php echo esc_html( (string) $doc['number'] ); ?></b><?php endif; ?> <span><?php echo esc_html( (string) $doc['title'] ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>
			</div>

			<aside class="pds-side">
				<section class="pds-box">
					<h2>Tin tuyển dụng</h2>
					<p><a href="<?php echo esc_url( cvc_recruitment_url( (string) ( $set['recruitment_slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $set['recruitment_title'] ?? '' ) ); ?></a></p>
					<?php if ( ! empty( $set['agency'] ) ) : ?><p class="pds-sub"><?php echo esc_html( (string) $set['agency'] ); ?></p><?php endif; ?>
					<?php if ( $pos ) : ?>
						<h3 class="pds-h3">Vị trí dùng bộ đề này</h3>
						<ul class="pds-pos"><?php foreach ( array_slice( $pos, 0, 12 ) as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
						<?php if ( count( $pos ) > 12 ) : ?><p class="pds-sub">… và <?php echo count( $pos ) - 12; ?> vị trí khác.</p><?php endif; ?>
					<?php endif; ?>
				</section>
				<section class="pds-box pds-aic">
					<h2>Chưa hiểu câu nào?</h2>
					<p>Hỏi <b>AI Coach</b> để được giải thích từng đáp án theo văn bản pháp luật, gợi ý cách nhớ — miễn phí, 24/7.</p>
					<button type="button" class="pds-btn" data-open-ai data-ai-prompt="<?php echo esc_attr( 'Tôi đang ôn bộ đề ' . $set['title'] . '. Hãy gợi ý các văn bản và nội dung trọng tâm cần nắm.' ); ?>">Hỏi AI Coach</button>
				</section>
			</aside>
		</div>

		<?php if ( $related ) : ?>
			<section class="pds-related" aria-labelledby="pds-rel-h">
				<h2 id="pds-rel-h">Bộ đề PDF liên quan</h2>
				<div class="pds-grid"><?php foreach ( $related as $it ) { cvc_pdf_render_card( $it, true ); } ?></div>
			</section>
		<?php endif; ?>
	</div>
</main>
<script>
document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('.pds-copy');if(!b)return;var t=b.getAttribute('data-copy');(navigator.clipboard?navigator.clipboard.writeText(t):Promise.reject()).then(function(){b.textContent='Đã sao chép';},function(){window.prompt('Sao chép liên kết:',t);});});
</script>
<?php
get_footer();
