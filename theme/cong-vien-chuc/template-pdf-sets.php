<?php
/**
 * /bo-de-pdf/ - Danh mục bộ đề PDF (27/10/2026). Xem inc/pdf-sets.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification
$tab   = isset( $_GET['tab'] ) && isset( CVC_PDF_TABS[ sanitize_key( wp_unslash( $_GET['tab'] ) ) ] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'tin';
$q     = isset( $_GET['q'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['q'] ) ), 0, 120 ) : '';
$prov  = isset( $_GET['tinh'] ) ? sanitize_title( wp_unslash( $_GET['tinh'] ) ) : '';
$sort  = isset( $_GET['sap-xep'] ) && 'tai-nhieu' === $_GET['sap-xep'] ? 'tai-nhieu' : '';
// phpcs:enable
$page  = max( 1, (int) get_query_var( 'cvc_paged' ) );
$state = array( 'tab' => $tab, 'q' => $q, 'tinh' => $prov, 'sap-xep' => $sort );

$d     = cvc_pdf_api(
	'/api/pdf-sets',
	array_filter(
		array(
			'tab'      => $tab,
			'q'        => $q,
			'province' => 'tin' === $tab ? $prov : '',
			'sort'     => 'tai-nhieu' === $sort ? 'popular' : '',
			'page'     => $page,
			'per_page' => 18,
		)
	),
	'' === $q ? 600 : 0
);
$items = array_values( array_filter( (array) ( $d['items'] ?? array() ), 'is_array' ) );
$total = (int) ( $d['total'] ?? 0 );
$last  = max( 1, (int) ceil( $total / 18 ) );
$provs = array_values( array_filter( (array) ( $d['provinces'] ?? array() ), 'is_array' ) );
if ( $page > $last && $total > 0 ) {
	status_header( 404 );
}

$title = 'Bộ đề thi trắc nghiệm PDF có đáp án – công chức, viên chức';
if ( 'van-ban' === $tab ) {
	$title = 'Đề trắc nghiệm PDF theo văn bản pháp luật (có đáp án)';
} elseif ( 'de-thi' === $tab ) {
	$title = 'Đề thi thử công chức, viên chức PDF có đáp án';
}
cvc_seo_set_title( $title . ( $page > 1 ? ' – Trang ' . $page : '' ) );
cvc_seo_set_description( 'Tải miễn phí bộ đề trắc nghiệm PDF ôn thi công chức, viên chức: đề biên soạn riêng theo từng tin tuyển dụng, đề theo văn bản pháp luật và đề thi thử. Có đáp án, giải thích, căn cứ và mã QR làm bài online.' );
cvc_seo_set_canonical( cvc_pdf_sets_url( array( 'tab' => $tab, 'trang' => $page ) ) );
if ( '' !== $q || '' !== $prov || '' !== $sort ) {
	cvc_seo_set_noindex();
}
if ( $items && 1 === $page && '' === $q && '' === $prov ) {
	cvc_seo_add_json_ld(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'name'            => $title,
			'numberOfItems'   => $total,
			'itemListElement' => array_map(
				static fn ( $it, $i ) => array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => cvc_pdf_item_url( $it ), 'name' => (string) $it['title'] ),
				$items,
				array_keys( $items )
			),
		)
	);
}
cvc_pdf_assets();

get_header();
?>
<main id="main" class="pds">
	<div class="pds-wrap">
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Ôn thi', 'url' => cvc_exams_url() ),
				array( 'label' => 'Bộ đề PDF' ),
			)
		);
		?>
		<section class="pds-hero">
			<div>
				<p class="pds-eyebrow">BỘ ĐỀ PDF · MIỄN PHÍ</p>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p class="pds-lead">Mỗi file có logo Công Viên Chức, đáp án nằm ngay dưới từng câu (bản có đáp án), căn cứ Điều – khoản, phiếu tô đáp án (bản đề) và mã QR để làm bài, chấm điểm trực tuyến. Xem trước không cần đăng nhập; đăng nhập miễn phí để tải.</p>
			</div>
			<ul class="pds-hero-facts">
				<li><b>3 dạng</b><span>Theo tin · theo văn bản · đề thi thử</span></li>
				<li><b>Có đáp án</b><span>Giải thích &amp; căn cứ từng câu</span></li>
				<li><b>AI Coach</b><span>Hỏi lại câu chưa hiểu, 24/7</span></li>
			</ul>
		</section>

		<nav class="pds-tabs" aria-label="Loại bộ đề">
			<?php foreach ( CVC_PDF_TABS as $k => $lbl ) : ?>
				<a href="<?php echo esc_url( cvc_pdf_sets_url( array( 'tab' => $k ) ) ); ?>"<?php echo $k === $tab ? ' class="is-on" aria-current="page"' : ''; ?>><?php echo esc_html( $lbl ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form class="pds-filter" method="get" action="<?php echo esc_url( home_url( '/bo-de-pdf/' ) ); ?>" role="search">
			<?php if ( 'tin' !== $tab ) : ?><input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>"><?php endif; ?>
			<label class="pds-f-q"><span class="screen-reader-text">Tìm bộ đề</span><input type="search" id="pds-q" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php echo esc_attr( 'tin' === $tab ? 'Tìm theo vị trí, đơn vị, tỉnh…' : 'Tìm theo tên đề, số hiệu văn bản…' ); ?>"></label>
			<?php if ( 'tin' === $tab && $provs ) : ?>
				<label><span class="screen-reader-text">Tỉnh/thành</span><select id="pds-tinh" name="tinh"><option value="">Mọi tỉnh/thành</option><?php foreach ( $provs as $p ) : ?><option value="<?php echo esc_attr( (string) $p['slug'] ); ?>"<?php selected( $prov, (string) $p['slug'] ); ?>><?php echo esc_html( $p['name'] . ' (' . (int) $p['count'] . ')' ); ?></option><?php endforeach; ?></select></label>
			<?php endif; ?>
			<label><span class="screen-reader-text">Sắp xếp</span><select id="pds-sort" name="sap-xep"><option value="">Mới nhất</option><option value="tai-nhieu"<?php selected( $sort, 'tai-nhieu' ); ?>>Tải nhiều</option></select></label>
			<button type="submit" class="pds-btn">Lọc</button>
		</form>

		<p class="pds-count"><?php echo esc_html( number_format( $total, 0, ',', '.' ) . ( 'tin' === $tab ? ' bộ đề' : ' đề' ) . ( '' !== $q ? ' khớp "' . $q . '"' : '' ) ); ?></p>

		<?php if ( $items ) : ?>
			<div class="pds-grid"><?php foreach ( $items as $it ) { cvc_pdf_render_card( $it ); } ?></div>
			<?php cvc_render_pagination( $page, $last, static fn ( int $p ) => cvc_pdf_sets_url( array_merge( $state, array( 'trang' => $p ) ) ) ); ?>
		<?php else : ?>
			<p class="pds-empty">Chưa có bộ đề phù hợp. Thử bỏ bớt bộ lọc hoặc <a href="<?php echo esc_url( cvc_pdf_sets_url() ); ?>">xem tất cả</a>.</p>
		<?php endif; ?>

		<section class="pds-how" aria-labelledby="pds-how-h">
			<h2 id="pds-how-h">Cách dùng bộ đề PDF hiệu quả</h2>
			<ol>
				<li><b>In bản "Đề thi"</b> kèm phiếu tô đáp án, làm như thi thật trong thời gian quy định.</li>
				<li><b>Đối chiếu bản "Đề + đáp án"</b>: đáp án, giải thích và căn cứ Điều nằm ngay dưới từng câu.</li>
				<li><b>Quét mã QR</b> trên file để làm lại đề trực tuyến, chấm điểm tự động và xếp hạng.</li>
				<li><b>Hỏi AI Coach</b> khi chưa hiểu câu nào — giải thích theo văn bản pháp luật, miễn phí.</li>
			</ol>
		</section>
	</div>
</main>
<?php
get_footer();
