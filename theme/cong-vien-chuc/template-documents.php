<?php
/**
 * CÔNG VIÊN CHỨC — KHO TÀI LIỆU (Document model, Phase 11 monetization)
 * URL: /tai-lieu/ và /tai-lieu/page/{n}/ — danh sách tài liệu miễn phí +
 * trả phí, filter qua ?is_free=1|0.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$filter = isset( $_GET['is_free'] ) ? sanitize_key( wp_unslash( $_GET['is_free'] ) ) : '';
$query_args = array(
	'per_page' => 12,
	'page'     => $paged,
);
if ( '1' === $filter || '0' === $filter ) {
	$query_args['is_free'] = $filter;
}
// Lọc theo định dạng tệp (trang chủ v17: thẻ Word/PDF/Excel/PowerPoint).
$cvc_formats = array( 'word' => 'Word', 'pdf' => 'PDF', 'excel' => 'Excel', 'ppt' => 'PowerPoint' );
$format      = isset( $_GET['dinh-dang'] ) ? sanitize_key( wp_unslash( $_GET['dinh-dang'] ) ) : '';
$format      = isset( $cvc_formats[ $format ] ) ? $format : '';
if ( '' !== $format ) {
	$query_args['format'] = $format;
}
// 01/11/2026: sắp xếp Mới nhất / Đọc nhiều / Tải nhiều (?sap-xep=doc|tai).
$cvc_sorts = array( '' => 'Mới nhất', 'doc' => 'Đọc nhiều', 'tai' => 'Tải nhiều' );
$sort      = isset( $_GET['sap-xep'] ) ? sanitize_key( wp_unslash( $_GET['sap-xep'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$sort      = isset( $cvc_sorts[ $sort ] ) ? $sort : '';
if ( '' !== $sort ) {
	$query_args['sort'] = $sort;
}
$cvc_doc_url = static function ( int $n = 1, ?string $free = null, ?string $fmt = null, ?string $srt = null ) use ( $filter, $format, $sort ): string {
	$u    = cvc_documents_url( $n );
	$free = null === $free ? $filter : $free;
	$fmt  = null === $fmt ? $format : $fmt;
	$srt  = null === $srt ? $sort : $srt;
	if ( '' !== $free ) {
		$u = add_query_arg( 'is_free', $free, $u );
	}
	if ( '' !== $fmt ) {
		$u = add_query_arg( 'dinh-dang', $fmt, $u );
	}
	if ( '' !== $srt ) {
		$u = add_query_arg( 'sap-xep', $srt, $u );
	}
	return $u;
};

$service    = new CVC_Document_Service();
$token      = cvc_auth_token();
$result     = $service->list( $query_args, $token );

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$documents  = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

cvc_seo_set_title( 'Thư viện tài liệu ôn thi công chức, viên chức 2026' );
cvc_seo_set_description( 'Bộ đề thi thử, sơ đồ tư duy, sổ tay ôn tập Kiến thức chung công chức — có tài liệu miễn phí, có tài liệu trả phí chất lượng cao.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_documents_url' );
wp_enqueue_style( 'cvc-library', get_theme_file_uri( '/assets/css/library.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/library.css' ) ) );

get_header();

function cvc_document_price_badge( array $doc ): string {
	if ( ! empty( $doc['is_free'] ) ) {
		return '<span class="px-2.5 py-1 bg-acc text-white font-black text-[10px] rounded-lg uppercase">Miễn phí</span>';
	}

	$price = (float) ( $doc['effective_price'] ?? 0 );

	return '<span class="px-2.5 py-1 bg-acc text-white font-black text-[10px] rounded-lg">' . number_format( $price, 0, ',', '.' ) . 'đ</span>';
}
?>

<main id="main" class="dz-main">
<div class="dz-wrap dl-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Tài liệu' ),
		)
	);
	cvc_render_listing_hero(
		'Thư viện tài liệu',
		'Thư viện tài liệu ôn thi công chức, viên chức',
		'Tài liệu theo kỳ thi, ngành nghề, địa phương; văn bản pháp luật, bộ câu hỏi có đáp án, tóm tắt kiến thức, biểu mẫu - và tài liệu tải về.',
		isset( $pagination['total'] ) ? array( array( number_format_i18n( (int) $pagination['total'] ), 'tài liệu tải về' ) ) : array()
	);
	$filters = array(
		''  => 'Tất cả',
		'1' => 'Miễn phí',
		'0' => 'Trả phí',
	);
	$cvc_cat_labels = array( 'mau-don' => 'Mẫu đơn', 'van-ban-phap-luat' => 'Văn bản pháp luật', 'de-thi' => 'Đề thi', 'tai-lieu-on-thi' => 'Tài liệu ôn thi', 'cam-nang' => 'Cẩm nang' );
	?>
	<?php // 01/11/2026 (claude/ke-hoach-thu-vien-luot-doc.md): danh sách "Tài liệu tải về" lên ĐẦU trang; bộ lọc thành hàng nút; chuyên mục xuống dưới. ?>
	<section class="dl-sec" aria-labelledby="dl-h">
		<div class="dz-h2"><h2 id="dl-h">Tài liệu tải về<?php echo '' !== $format ? ' · ' . esc_html( $cvc_formats[ $format ] ) : ''; ?></h2><span class="dz-muted" style="font-size:15px">Trang <?php echo (int) $currentPg; ?>/<?php echo (int) max( 1, $lastPg ); ?></span></div>
		<div class="dl-bar">
			<div class="dl-grp" role="group" aria-label="Lọc theo giá">
				<?php foreach ( $filters as $value => $label ) : ?>
					<a class="dl-chip<?php echo $filter === (string) $value ? ' is-on' : ''; ?>" href="<?php echo esc_url( $cvc_doc_url( 1, (string) $value ) ); ?>"<?php echo $filter === (string) $value ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</div>
			<div class="dl-grp" role="group" aria-label="Lọc theo định dạng">
				<a class="dl-chip<?php echo '' === $format ? ' is-on' : ''; ?>" href="<?php echo esc_url( $cvc_doc_url( 1, null, '' ) ); ?>">Mọi định dạng</a>
				<?php foreach ( $cvc_formats as $fkey => $flabel ) : ?>
					<a class="dl-chip<?php echo $format === $fkey ? ' is-on' : ''; ?>" href="<?php echo esc_url( $cvc_doc_url( 1, null, $fkey ) ); ?>"><?php echo esc_html( $flabel ); ?></a>
				<?php endforeach; ?>
			</div>
			<div class="dl-grp dl-sort" role="group" aria-label="Sắp xếp">
				<span class="dl-lb">Sắp xếp:</span>
				<?php foreach ( $cvc_sorts as $skey => $slabel ) : ?>
					<a class="dl-chip<?php echo $sort === $skey ? ' is-on' : ''; ?>" href="<?php echo esc_url( $cvc_doc_url( 1, null, null, $skey ) ); ?>" rel="nofollow"><?php echo esc_html( $slabel ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( ! $ok ) : ?>
			<?php cvc_render_error_state( 'Không thể tải danh sách tài liệu lúc này.' ); ?>
		<?php elseif ( empty( $documents ) ) : ?>
			<?php cvc_render_empty_state( 'Chưa có tài liệu nào trong mục này.' ); ?>
		<?php else : ?>
			<div class="dl-list" role="list">
				<div class="dl-head" aria-hidden="true"><span>Tên tài liệu</span><span>Loại</span><span class="num">Lượt đọc</span><span class="num">Lượt tải</span><span class="num">Giá</span></div>
				<?php foreach ( $documents as $doc ) : ?>
					<?php
					$dc    = (string) ( $doc['category'] ?? '' );
					$dcl   = $cvc_cat_labels[ $dc ] ?? ( '' !== $dc ? ucfirst( str_replace( '-', ' ', $dc ) ) : 'Tài liệu' );
					$reads = (int) ( $doc['read_count'] ?? 0 );
					$dls   = (int) ( $doc['download_count'] ?? 0 );
					?>
					<article class="dl-row" role="listitem">
						<div class="dl-main">
							<?php if ( ! empty( $doc['thumb'] ) ) : ?><img class="dl-thumb" src="<?php echo esc_url( (string) $doc['thumb'] ); ?>" alt="" width="44" height="62" loading="lazy" decoding="async"><?php else : ?><span class="dl-thumb is-blank" aria-hidden="true"><i class="fa-regular fa-file-lines"></i></span><?php endif; ?>
							<div class="dl-txt">
								<h3><a href="<?php echo esc_url( cvc_document_url( $doc['slug'] ) ); ?>"><?php echo esc_html( $doc['title'] ); ?></a></h3>
								<?php if ( ! empty( $doc['description'] ) ) : ?><p><?php echo esc_html( wp_trim_words( (string) $doc['description'], 24 ) ); ?></p><?php endif; ?>
								<p class="dl-meta"><span><?php echo esc_html( $dcl ); ?></span><span><?php echo cvc_doc_reads_html( $reads ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( number_format( $dls, 0, ',', '.' ) . ' lượt tải' ); ?></span></p>
							</div>
						</div>
						<span class="dl-type"><?php echo esc_html( $dcl ); ?></span>
						<span class="dl-num"><?php echo $reads < CVC_DOC_READS_MIN ? '<span class="dl-new">Mới</span>' : esc_html( number_format( $reads, 0, ',', '.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="dl-num"><?php echo esc_html( number_format( $dls, 0, ',', '.' ) ); ?></span>
						<span class="dl-price"><?php echo ! empty( $doc['is_free'] ) ? '<span class="dz-tag is-ok">Miễn phí</span>' : esc_html( number_format( (float) ( $doc['effective_price'] ?? 0 ), 0, ',', '.' ) . 'đ' ); ?></span>
					</article>
				<?php endforeach; ?>
			</div>
			<?php
			cvc_render_pagination(
				$currentPg,
				$lastPg,
				static function ( int $n ) use ( $cvc_doc_url ): string {
					return $cvc_doc_url( $n );
				}
			);
			?>
		<?php endif; ?>
	</section>

	<?php if ( function_exists( 'cvc_library_render_nav' ) && 1 === $currentPg && '' === $filter && '' === $format ) : ?>
		<section class="dl-sec dl-explore" aria-labelledby="dl-ex-h">
			<div class="dz-h2"><h2 id="dl-ex-h">Khám phá theo chuyên mục</h2></div>
			<?php cvc_library_render_nav( '', true ); // Thư viện 10 mục (31/10/2026) ?>
		</section>
	<?php endif; ?>
	<?php
	if ( function_exists( 'cvc_pdf_render_docs_banner' ) ) {
		cvc_pdf_render_docs_banner(); // Bộ đề PDF (27/10/2026)
	}
	?>
	<div class="dl-sec dl-more"><?php cvc_render_listing_explore_nav( 'documents' ); ?></div>
</div>
	<?php if ( function_exists( 'cvc_side_feed' ) ) { cvc_side_feed( array( 'slot' => 'tls', 'text' => 'tài liệu ôn thi công chức viên chức' ), 'band' ); } // 12/11/2026 ?>
</main>

<?php get_footer(); ?>
