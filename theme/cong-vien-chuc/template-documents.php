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
$cvc_doc_url = static function ( int $n = 1, ?string $free = null, ?string $fmt = null ) use ( $filter, $format ): string {
	$u    = cvc_documents_url( $n );
	$free = null === $free ? $filter : $free;
	$fmt  = null === $fmt ? $format : $fmt;
	if ( '' !== $free ) {
		$u = add_query_arg( 'is_free', $free, $u );
	}
	if ( '' !== $fmt ) {
		$u = add_query_arg( 'dinh-dang', $fmt, $u );
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

cvc_seo_set_title( 'Kho Tài Liệu Ôn Thi Công Chức, Viên Chức 2026' );
cvc_seo_set_description( 'Bộ đề thi thử, sơ đồ tư duy, sổ tay ôn tập Kiến thức chung công chức — có tài liệu miễn phí, có tài liệu trả phí chất lượng cao.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_documents_url' );

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
<div class="dz-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Tài liệu' ),
		)
	);
	cvc_render_listing_hero(
		'Tài liệu',
		'Kho tài liệu ôn thi công chức, viên chức',
		'Mẫu hồ sơ dự tuyển, văn bản tham khảo và tài liệu ôn tập — có tài liệu miễn phí và tài liệu trả phí biên soạn chuyên sâu.',
		isset( $pagination['total'] ) ? array( array( number_format_i18n( (int) $pagination['total'] ), 'tài liệu' ) ) : array()
	);
	$filters = array(
		''  => 'Tất cả',
		'1' => 'Miễn phí',
		'0' => 'Trả phí',
	);
	?>
	<div class="dz-cols is-left">
		<aside class="dz-colside">
			<nav class="dz-box" aria-label="Lọc tài liệu">
				<h2 class="dz-box-head">Lọc theo giá</h2>
				<div class="dz-box-body">
					<ul class="dz-toc">
						<?php foreach ( $filters as $value => $label ) :
							$url = $cvc_doc_url( 1, (string) $value );
						?>
							<li><a href="<?php echo esc_url( $url ); ?>" class="<?php echo $filter === $value ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
			<nav class="dz-box" aria-label="Lọc theo định dạng">
				<h2 class="dz-box-head">Lọc theo định dạng</h2>
				<div class="dz-box-body">
					<ul class="dz-toc">
						<li><a href="<?php echo esc_url( $cvc_doc_url( 1, null, '' ) ); ?>" class="<?php echo '' === $format ? 'is-active' : ''; ?>">Mọi định dạng</a></li>
						<?php foreach ( $cvc_formats as $fkey => $flabel ) : ?>
							<li><a href="<?php echo esc_url( $cvc_doc_url( 1, null, $fkey ) ); ?>" class="<?php echo $format === $fkey ? 'is-active' : ''; ?>"><?php echo esc_html( $flabel ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
			<?php cvc_render_listing_explore_nav( 'documents' ); ?>
		</aside>
		<div class="dz-colmain">
			<div class="dz-h2"><h2>Danh mục tài liệu<?php echo '' !== $format ? ' · ' . esc_html( $cvc_formats[ $format ] ) : ''; ?></h2><span class="dz-muted" style="font-size:15px">Trang <?php echo (int) $currentPg; ?>/<?php echo (int) max( 1, $lastPg ); ?></span></div>
			<?php if ( ! $ok ) : ?>
				<?php cvc_render_error_state( 'Không thể tải danh sách tài liệu lúc này.' ); ?>
			<?php elseif ( empty( $documents ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có tài liệu nào trong mục này.' ); ?>
			<?php else : ?>
				<div class="dz-tablewrap">
					<table class="dz-table">
						<thead><tr><th>Tên tài liệu</th><th>Loại</th><th class="num">Lượt tải</th><th class="num">Giá</th></tr></thead>
						<tbody>
						<?php foreach ( $documents as $doc ) : ?>
							<tr>
								<td><a class="dz-rowlink" style="font-weight:bold" href="<?php echo esc_url( cvc_document_url( $doc['slug'] ) ); ?>"><?php echo esc_html( $doc['title'] ); ?></a><?php if ( ! empty( $doc['description'] ) ) : ?><div class="dz-muted" style="font-size:15px;margin-top:3px"><?php echo esc_html( wp_trim_words( (string) $doc['description'], 24 ) ); ?></div><?php endif; ?></td>
								<td><?php $dc = (string) ( $doc['category'] ?? '' ); echo esc_html( array( 'mau-don' => 'Mẫu đơn', 'van-ban-phap-luat' => 'Văn bản pháp luật', 'de-thi' => 'Đề thi', 'tai-lieu-on-thi' => 'Tài liệu ôn thi' )[ $dc ] ?? ( '' !== $dc ? ucfirst( str_replace( '-', ' ', $dc ) ) : 'Tài liệu' ) ); ?></td>
								<td class="num"><?php echo esc_html( number_format( (int) ( $doc['download_count'] ?? 0 ), 0, ',', '.' ) ); ?></td>
								<td class="num"><?php echo ! empty( $doc['is_free'] ) ? '<span class="dz-tag is-ok">Miễn phí</span>' : esc_html( number_format( (float) ( $doc['effective_price'] ?? 0 ), 0, ',', '.' ) . 'đ' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
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
		</div>
	</div>
</div>
</main>

<?php get_footer(); ?>
