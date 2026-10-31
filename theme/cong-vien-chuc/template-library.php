<?php
/**
 * Thư viện tài liệu (31/10/2026) - 1 mẫu cho 9 trang của menu "Tài liệu" (xem inc/library.php).
 * Dữ liệu GET /api/library/{view}; mỗi trang = sections[] -> blocks[] (topics | legal | exams | pdfsets | docs |
 * rec_docs | recruitments | knowledge | questions). Khối chưa có nội dung hiện "Sắp có".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lb_view  = (string) get_query_var( 'cvc_lib_view' );
$lb_param = sanitize_title( (string) get_query_var( 'cvc_lib_param' ) );
$lb_cfg   = cvc_library_views()[ $lb_view ] ?? null;
if ( ! $lb_cfg || ! empty( $lb_cfg['external'] ) ) {
	$lb_view = '';
}
$lb_q   = array();
if ( 'nganh' === $lb_view && '' !== $lb_param ) {
	$lb_q['family'] = $lb_param;
}
if ( 'tinh' === $lb_view && '' !== $lb_param ) {
	$lb_q['province'] = $lb_param;
}
$lb_res  = '' !== $lb_view ? cvc_library_api( $lb_view, $lb_q ) : array( 'ok' => false, 'status' => 404, 'data' => array() );
$lb      = $lb_res['data'];
$lb_404  = '' === $lb_view || 404 === $lb_res['status'];
$lb_secs = array_values( array_filter( (array) ( $lb['sections'] ?? array() ), 'is_array' ) );

// Tiêu đề theo trang con (ngành / tỉnh).
$lb_h1    = $lb_cfg['h1'] ?? 'Thư viện tài liệu';
$lb_title = $lb_cfg['title'] ?? $lb_h1;
$lb_desc  = $lb_cfg['desc'] ?? '';
$lb_lead  = $lb_cfg['lead'] ?? '';
$lb_crumb = array( array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ), array( 'label' => 'Tài liệu', 'url' => cvc_documents_url() ) );
if ( ! empty( $lb['family'] ) ) {
	$fam      = (array) $lb['family'];
	$lb_h1    = 'Tài liệu ôn thi ngành ' . mb_strtolower( (string) $fam['label'], 'UTF-8' );
	$lb_title = $lb_h1 . ' - công chức, viên chức 2026';
	$lb_desc  = 'Tài liệu ôn thi ngành ' . mb_strtolower( (string) $fam['label'], 'UTF-8' ) . ' (' . $fam['hint'] . '): chủ đề kiến thức, văn bản pháp luật, đề luyện và bộ đề theo tin tuyển dụng.';
	$lb_lead  = (string) $fam['hint'] . '. Chủ đề kiến thức, văn bản pháp luật của ngành, đề luyện và bộ đề theo tin tuyển dụng.';
	$lb_crumb[] = array( 'label' => $lb_cfg['label'], 'url' => cvc_library_url( 'nganh' ) );
	$lb_crumb[] = array( 'label' => (string) $fam['label'] );
} elseif ( ! empty( $lb['province'] ) ) {
	$prv      = (array) $lb['province'];
	$lb_h1    = 'Tài liệu tuyển dụng, ôn thi ' . $prv['name'];
	$lb_title = $lb_h1 . ' - công chức, viên chức';
	$lb_desc  = 'Văn bản tuyển dụng gốc, tin đang nhận hồ sơ, bộ đề theo tin và tài liệu ôn thi công chức, viên chức tại ' . $prv['name'] . '.';
	$lb_lead  = 'Thông báo, kế hoạch, danh mục tài liệu ôn tập từ cơ quan tuyển dụng tại ' . $prv['name'] . ', kèm tin đang mở và bộ đề theo tin.';
	$lb_crumb[] = array( 'label' => $lb_cfg['label'], 'url' => cvc_library_url( 'tinh' ) );
	$lb_crumb[] = array( 'label' => (string) $prv['name'] );
} elseif ( $lb_cfg ) {
	$lb_crumb[] = array( 'label' => $lb_cfg['label'] );
}

$lb_total = array_sum( array_map( static fn ( $s ) => (int) ( $s['total'] ?? 0 ), $lb_secs ) )
	+ count( (array) ( $lb['families'] ?? array() ) ) * 10 + count( (array) ( $lb['provinces'] ?? array() ) ) * 10;

if ( $lb_404 ) {
	status_header( 404 );
	cvc_seo_set_noindex();
	cvc_seo_set_title( 'Không tìm thấy mục tài liệu' );
} else {
	cvc_seo_set_title( $lb_title );
	cvc_seo_set_description( $lb_desc );
	if ( function_exists( 'cvc_seo_set_canonical' ) ) {
		cvc_seo_set_canonical( cvc_library_url( $lb_view, $lb_param ) );
	}
	// Trang mỏng: ít nội dung, hoặc mục chính (section đầu) chưa có gì (vd. Giáo trình chưa có sách) -> noindex.
	if ( $lb_total < 5 || ( $lb_secs && 0 === (int) ( $lb_secs[0]['total'] ?? 0 ) ) || ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		cvc_seo_set_noindex();
	}
}
wp_enqueue_style( 'cvc-library', get_theme_file_uri( '/assets/css/library.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/library.css' ) ) );

/* ------------------------------------------------------------------ Khối */
$lb_more = static function ( array $b ) use ( $lb_view, $lb ): string {
	$fams = (array) ( $b['families'] ?? array() );
	switch ( (string) $b['type'] ) {
		case 'topics':
			return 1 === count( $fams ) ? add_query_arg( 'group', $fams[0], cvc_topics_url() ) : cvc_topics_url();
		case 'legal':
			return cvc_legal_documents_url();
		case 'exams':
			return 'cong-chuc' === $lb_view && function_exists( 'cvc_cat_track_url' ) ? cvc_cat_track_url( 'cong-chuc' ) : cvc_exams_mode_url( 'on-tap' );
		case 'pdfsets':
			$f = (array) ( $b['filter'] ?? array() );
			return cvc_pdf_sets_url( ! empty( $f['province'] ) ? array( 'tinh' => (string) $f['province'] ) : array() );
		case 'docs':
			return cvc_documents_url();
		case 'knowledge':
			return cvc_knowledge_url();
		case 'recruitments':
			return cvc_ci_url( ! empty( $lb['province']['id'] ) ? array( 'tinh' => (int) $lb['province']['id'] ) : array() );
	}
	return '';
};
$lb_date = static fn ( ?string $d ): string => $d ? wp_date( 'd/m/Y', strtotime( $d ) ) : '';

$lb_block = static function ( array $b ) use ( $lb_more, $lb_date ): void {
	$items = array_values( array_filter( (array) ( $b['items'] ?? array() ), 'is_array' ) );
	$total = (int) ( $b['total'] ?? 0 );
	$type  = (string) $b['type'];
	$more  = $lb_more( $b );
	?>
	<div class="lb-block is-<?php echo esc_attr( $type ); ?>">
		<div class="lb-block-head">
			<h3><?php echo esc_html( (string) $b['title'] ); ?><?php if ( $total > 0 ) : ?> <span class="lb-count"><?php echo esc_html( number_format_i18n( $total ) ); ?></span><?php endif; ?></h3>
			<?php if ( '' !== $more && $total > count( $items ) ) : ?><a class="lb-more" href="<?php echo esc_url( $more ); ?>">Xem tất cả →</a><?php endif; ?>
		</div>
		<?php if ( ! empty( $b['soon'] ) || ! $items ) : ?>
			<div class="lb-soon"><span class="lb-soon-tag">Sắp có</span><p><?php echo 'questions' === $type ? 'Đang biên soạn. Trong lúc chờ, bạn có thể luyện câu hỏi trắc nghiệm có đáp án theo chủ đề.' : 'Đang biên soạn và kiểm duyệt nội dung cho mục này.'; ?></p></div>
		<?php elseif ( 'topics' === $type ) : ?>
			<ul class="lb-grid">
				<?php foreach ( $items as $t ) : ?>
					<li><a class="lb-card" href="<?php echo esc_url( cvc_topic_url( (string) $t['slug'] ) ); ?>"><b><?php echo esc_html( (string) $t['name'] ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( (int) $t['knowledge'] ? number_format_i18n( (int) $t['knowledge'] ) . ' bài kiến thức' : '', (int) $t['questions'] ? number_format_i18n( (int) $t['questions'] ) . ' câu hỏi' : '' ) ) ) ); ?></small></a></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'legal' === $type ) : ?>
			<ul class="lb-list">
				<?php foreach ( $items as $d ) : ?>
					<?php $old = 'in_force' !== ( $d['effect'] ?? '' ); ?>
					<li class="<?php echo $old ? 'is-old' : ''; ?>"><span class="lb-ic" aria-hidden="true">§</span><span class="lb-li"><a href="<?php echo esc_url( home_url( (string) $d['url'] ) ); ?>"><?php echo esc_html( (string) $d['title'] ); ?></a><small><?php echo esc_html( implode( ' · ', array_filter( array( ! empty( $d['issued_date'] ) ? 'Ban hành ' . $lb_date( (string) $d['issued_date'] ) : '', $old ? (string) ( $d['effect_label'] ?? '' ) : ( ! empty( $d['verified'] ) ? 'Đã đối chiếu nguồn' : '' ), ! empty( $d['added_at'] ) ? 'Bổ sung ' . $lb_date( (string) $d['added_at'] ) : '' ) ) ) ); ?></small></span></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'exams' === $type ) : ?>
			<ul class="lb-list">
				<?php foreach ( $items as $e ) : ?>
					<li><span class="lb-ic" aria-hidden="true">✎</span><span class="lb-li"><a href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>"><?php echo esc_html( (string) $e['title'] ); ?></a><small><?php echo esc_html( trim( ( (int) $e['questions'] ? (int) $e['questions'] . ' câu' : '' ) . ( (int) $e['minutes'] ? ' · ' . (int) $e['minutes'] . ' phút' : '' ), ' ·' ) ); ?></small></span><a class="lb-act" href="<?php echo esc_url( add_query_arg( 'che-do', 'on-tap', cvc_exam_url( (string) $e['slug'] ) ) ); ?>" rel="nofollow">Luyện</a></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'pdfsets' === $type ) : ?>
			<ul class="lb-grid">
				<?php foreach ( $items as $s ) : ?>
					<li><a class="lb-card is-set" href="<?php echo esc_url( cvc_pdf_set_url( (string) $s['slug'] ) ); ?>"><span class="lb-pill<?php echo ! empty( $s['is_open'] ) ? ' is-open' : ''; ?>"><?php echo ! empty( $s['is_open'] ) ? 'Đang nhận hồ sơ' : 'Đã hết hạn nộp'; ?></span><b><?php echo esc_html( (string) $s['title'] ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $s['subtitle'] ?? '' ), (int) $s['exams'] . ' đề', number_format_i18n( (int) $s['questions'] ) . ' câu' ) ) ) ); ?></small></a></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'docs' === $type ) : ?>
			<ul class="lb-list">
				<?php foreach ( $items as $d ) : ?>
					<li><?php if ( ! empty( $d['thumb'] ) ) : ?><img class="lb-thumb" src="<?php echo esc_url( (string) $d['thumb'] ); ?>" alt="" width="40" height="57" loading="lazy" decoding="async"><?php else : ?><span class="lb-ic is-fmt" aria-hidden="true"><?php echo esc_html( strtoupper( (string) ( $d['format'] ?? 'tệp' ) ) ); ?></span><?php endif; ?><span class="lb-li"><a href="<?php echo esc_url( cvc_document_url( (string) $d['slug'] ) ); ?>"><?php echo esc_html( (string) $d['title'] ); ?></a><small><?php echo esc_html( implode( ' · ', array_filter( array( (string) $d['category_label'], (int) $d['downloads'] ? number_format_i18n( (int) $d['downloads'] ) . ' lượt tải' : '', ! empty( $d['updated_at'] ) ? 'Cập nhật ' . $lb_date( (string) $d['updated_at'] ) : '' ) ) ) ); ?></small></span><span class="lb-price<?php echo ! empty( $d['is_free'] ) ? ' is-free' : ''; ?>"><?php echo ! empty( $d['is_free'] ) ? 'Miễn phí' : esc_html( number_format( (float) $d['price'], 0, ',', '.' ) . 'đ' ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'rec_docs' === $type ) : ?>
			<ul class="lb-list">
				<?php foreach ( $items as $d ) : ?>
					<li><span class="lb-ic" aria-hidden="true">▤</span><span class="lb-li"><b class="lb-role"><?php echo esc_html( (string) $d['role_label'] ); ?></b> <?php if ( ! empty( $d['source_url'] ) ) : ?><a href="<?php echo esc_url( (string) $d['source_url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( (string) $d['title'] ); ?> ↗</a><?php else : ?><?php echo esc_html( (string) $d['title'] ); ?><?php endif; ?><small><?php $lb_meta = implode( ' · ', array_filter( array( (string) ( $d['doc_number'] ?? '' ), ! empty( $d['issued_date'] ) ? $lb_date( (string) $d['issued_date'] ) : '' ) ) ); echo esc_html( $lb_meta ); ?><?php if ( ! empty( $d['recruitment']['slug'] ) ) : ?><?php echo '' !== $lb_meta ? ' · ' : ''; ?><a href="<?php echo esc_url( cvc_recruitment_url( (string) $d['recruitment']['slug'] ) ); ?>">Xem tin tuyển dụng</a><?php endif; ?></small></span></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'recruitments' === $type ) : ?>
			<ul class="lb-list">
				<?php foreach ( $items as $r ) : ?>
					<li><span class="lb-ic" aria-hidden="true">⌂</span><span class="lb-li"><a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( (string) $r['title'] ); ?></a><small><?php echo ! empty( $r['deadline'] ) ? esc_html( 'Hạn nộp ' . $lb_date( (string) $r['deadline'] ) ) : 'Chưa công bố hạn nộp'; ?></small></span></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( 'knowledge' === $type ) : ?>
			<ul class="lb-grid">
				<?php foreach ( $items as $k ) : ?>
					<li><a class="lb-card" href="<?php echo esc_url( cvc_knowledge_item_url( (string) $k['slug'] ) ); ?>"><b><?php echo esc_html( (string) $k['title'] ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $k['topic'] ?? '' ), ! empty( $k['added_at'] ) ? 'Bổ sung ' . $lb_date( (string) $k['added_at'] ) : '' ) ) ) ); ?></small></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
};

get_header();
?>
<main id="main" class="dz-main lb">
<div class="dz-wrap">
	<?php cvc_render_breadcrumbs( $lb_crumb ); ?>
	<?php if ( $lb_404 ) : ?>
		<?php cvc_render_listing_hero( 'Thư viện tài liệu', 'Không tìm thấy mục tài liệu', 'Mục này không tồn tại hoặc chưa có dữ liệu. Chọn một mục khác bên dưới.' ); ?>
		<?php cvc_library_render_nav( '', true ); ?>
	<?php else : ?>
		<?php cvc_render_listing_hero( 'Thư viện tài liệu · ' . ( $lb_cfg['no'] ?? '' ), $lb_h1, $lb_lead ); ?>
		<?php cvc_library_render_nav( $lb_view ); ?>
		<?php if ( ! $lb_res['ok'] ) : ?>
			<?php cvc_render_error_state( 'Không tải được thư viện lúc này, vui lòng thử lại sau ít phút.' ); ?>
		<?php elseif ( ! empty( $lb['families'] ) ) : ?>
			<ul class="lb-tiles">
				<?php foreach ( (array) $lb['families'] as $f ) : ?>
					<?php $c = (array) $f['counts']; ?>
					<li><a class="lb-tile" href="<?php echo esc_url( cvc_library_url( 'nganh', (string) $f['slug'] ) ); ?>"><b><?php echo esc_html( (string) $f['label'] ); ?></b><small><?php echo esc_html( (string) $f['hint'] ); ?></small><em><?php echo esc_html( implode( ' · ', array_filter( array( $c['topics'] ? $c['topics'] . ' chủ đề' : '', $c['legal'] ? $c['legal'] . ' văn bản' : '', $c['sets'] ? $c['sets'] . ' bộ đề' : '', $c['docs'] ? $c['docs'] . ' tài liệu' : '' ) ) ) ?: 'Sắp có' ); ?></em></a></li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( isset( $lb['provinces'] ) ) : ?>
			<?php if ( empty( $lb['provinces'] ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có tỉnh, thành nào có dữ liệu.' ); ?>
			<?php else : ?>
				<ul class="lb-tiles is-prov">
					<?php foreach ( (array) $lb['provinces'] as $p ) : ?>
						<?php $c = (array) $p['counts']; ?>
						<li><a class="lb-tile" href="<?php echo esc_url( cvc_library_url( 'tinh', (string) $p['slug'] ) ); ?>"><b><?php echo esc_html( (string) $p['name'] ); ?></b><em><?php echo esc_html( implode( ' · ', array_filter( array( $c['open'] ? $c['open'] . ' tin đang mở' : '', $c['rec_docs'] ? $c['rec_docs'] . ' văn bản tuyển dụng' : '', $c['sets'] ? $c['sets'] . ' bộ đề' : '', $c['docs'] ? $c['docs'] . ' tài liệu' : '' ) ) ) ); ?></em></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php else : ?>
			<?php if ( count( $lb_secs ) > 2 ) : ?>
				<nav class="lb-toc" aria-label="Trong trang này">
					<?php foreach ( $lb_secs as $s ) : ?><a href="#<?php echo esc_attr( (string) $s['key'] ); ?>"><?php echo esc_html( (string) $s['title'] ); ?><?php echo (int) $s['total'] ? '' : ' · Sắp có'; ?></a><?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<?php foreach ( $lb_secs as $s ) : ?>
				<section class="lb-sec" id="<?php echo esc_attr( (string) $s['key'] ); ?>" aria-labelledby="lb-h-<?php echo esc_attr( (string) $s['key'] ); ?>">
					<h2 id="lb-h-<?php echo esc_attr( (string) $s['key'] ); ?>"><?php echo esc_html( (string) $s['title'] ); ?></h2>
					<?php if ( ! empty( $s['lead'] ) ) : ?><p class="lb-lead"><?php echo esc_html( (string) $s['lead'] ); ?></p><?php endif; ?>
					<div class="lb-blocks<?php echo count( (array) $s['blocks'] ) > 1 ? ' is-multi' : ''; ?>">
						<?php foreach ( (array) $s['blocks'] as $b ) { $lb_block( (array) $b ); } ?>
					</div>
				</section>
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
</main>
<?php
get_footer();
