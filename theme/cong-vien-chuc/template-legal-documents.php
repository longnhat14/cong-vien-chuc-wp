<?php
/**
 * Thư viện văn bản pháp luật - giao diện văn bản (16/10/2026): bảng tra cứu số hiệu / trích yếu / cơ quan / ngày.
 * URL: /van-ban-phap-luat/ và /van-ban-phap-luat/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;
$q     = cvc_listing_query();

// Loc theo loai van ban (?loai=) - gia tri hop le lay tu danh sach co dinh.
$type_options = array( 'Luật', 'Nghị định', 'Nghị quyết', 'Thông tư', 'Quyết định' );
$type_filter  = isset( $_GET['loai'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['loai'] ) ) : '';
$type_filter  = in_array( $type_filter, $type_options, true ) ? $type_filter : '';

$params = array(
	'per_page' => 12,
	'page'     => $paged,
	'search'   => $q,
);
if ( '' !== $type_filter ) {
	$params['document_type'] = $type_filter;
}

$service = new CVC_Legal_Document_Service();
$result  = $service->list( $params );

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$documents  = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Văn bản pháp luật về công chức, viên chức' );
cvc_seo_set_description( 'Tra cứu văn bản pháp luật về công chức, viên chức: số hiệu, cơ quan ban hành, ngày hiệu lực và văn bản thay thế.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_legal_documents_url' );

get_header();
?>

<main id="main" class="dz-main">
<div class="dz-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Văn bản pháp luật' ),
		)
	);
	$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : null;
	cvc_render_listing_hero(
		'Văn bản pháp luật',
		'Văn bản pháp luật về công chức, viên chức',
		'Tra cứu số hiệu, cơ quan ban hành, ngày hiệu lực và văn bản thay thế; dẫn về nguồn chính thức.',
		null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'văn bản' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
	);
	$type_url = static function ( string $t ) use ( $q ): string {
		$u = cvc_legal_documents_url();
		if ( '' !== $q ) {
			$u = add_query_arg( 'q', rawurlencode( $q ), $u );
		}
		return '' !== $t ? add_query_arg( 'loai', rawurlencode( $t ), $u ) : $u;
	};
	?>
	<div class="dz-cols is-left">
		<aside class="dz-colside">
			<?php cvc_render_listing_search( cvc_legal_documents_url(), $q, 'Số hiệu, tên văn bản…', 'Tìm văn bản' ); ?>
			<nav class="dz-box" aria-label="Lọc theo loại văn bản">
				<h2 class="dz-box-head">Loại văn bản</h2>
				<div class="dz-box-body">
					<ul class="dz-toc">
						<li><a href="<?php echo esc_url( $type_url( '' ) ); ?>" class="<?php echo '' === $type_filter ? 'is-active' : ''; ?>">Tất cả</a></li>
						<?php foreach ( $type_options as $opt ) : ?>
							<li><a href="<?php echo esc_url( $type_url( $opt ) ); ?>" class="<?php echo $opt === $type_filter ? 'is-active' : ''; ?>" <?php echo $opt === $type_filter ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $opt ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
			<?php cvc_render_listing_explore_nav( 'legal-documents' ); ?>
		</aside>

		<div class="dz-colmain">
			<div class="dz-h2"><h2><?php echo '' !== $type_filter ? esc_html( $type_filter ) : 'Danh mục văn bản'; ?><?php echo '' !== $q ? ' — “' . esc_html( $q ) . '”' : ''; ?></h2><span class="dz-muted" style="font-size:15px">Trang <?php echo (int) $currentPg; ?>/<?php echo (int) max( 1, $lastPg ); ?></span></div>
			<?php if ( ! $ok ) : ?>
				<?php cvc_render_error_state( 'Không tải được danh sách văn bản pháp luật, vui lòng thử lại sau.' ); ?>
			<?php elseif ( empty( $documents ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có văn bản pháp luật nào.' ); ?>
			<?php else : ?>
				<div class="dz-tablewrap">
					<table class="dz-table dz-lawtable">
						<thead><tr><th>Số, ký hiệu</th><th>Trích yếu</th><th>Cơ quan ban hành</th><th>Ban hành</th><th>Hiệu lực</th></tr></thead>
						<tbody>
						<?php foreach ( $documents as $doc ) :
							$docSlug = (string) ( $doc['slug'] ?? '' );
							if ( '' === $docSlug ) {
								continue;
							}
							$docUrl        = cvc_legal_document_url( $docSlug );
							$docNum        = (string) ( $doc['document_number'] ?? '' );
							$docType       = (string) ( $doc['document_type'] ?? '' );
							$agencyName    = (string) ( $doc['issuing_agency'] ?? '' );
							$effectiveDate = cvc_format_date_vn( $doc['effective_date'] ?? null );
							$issuedDate    = cvc_format_date_vn( $doc['issued_date'] ?? null );
							$replacedBy    = $doc['replaced_by'] ?? null;
						?>
							<tr>
								<td class="dz-lawnum"><a class="dz-rowlink" href="<?php echo esc_url( $docUrl ); ?>"><?php echo esc_html( '' !== $docNum ? $docNum : '—' ); ?></a><?php if ( '' !== $docType ) : ?><small><?php echo esc_html( $docType ); ?></small><?php endif; ?></td>
								<td>
									<a class="dz-rowlink" href="<?php echo esc_url( $docUrl ); ?>"><?php echo esc_html( (string) ( $doc['title'] ?? '' ) ); ?></a>
									<span class="dz-lawflags">
										<?php if ( ! empty( $doc['has_full_text'] ) ) : ?><span class="dz-tag is-ok">Có toàn văn</span><?php endif; ?>
										<?php if ( ! empty( $doc['has_file'] ) ) : ?><span class="dz-tag">Có bản gốc</span><?php endif; ?>
										<?php if ( ! empty( $replacedBy ) ) : ?><span class="dz-tag is-warn">Đã có văn bản thay thế</span><?php endif; ?>
									</span>
								</td>
								<td><?php echo esc_html( '' !== $agencyName ? $agencyName : '—' ); ?></td>
								<td class="num"><?php echo esc_html( '' !== $issuedDate ? $issuedDate : '—' ); ?></td>
								<td class="num"><?php echo esc_html( '' !== $effectiveDate ? $effectiveDate : '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php
				$page_builder = cvc_listing_page_url_builder( 'cvc_legal_documents_url', $q );
				cvc_render_pagination(
					$currentPg,
					$lastPg,
					static function ( int $n ) use ( $page_builder, $type_filter ): string {
						$u = $page_builder( $n );
						return '' !== $type_filter ? add_query_arg( 'loai', rawurlencode( $type_filter ), $u ) : $u;
					}
				);
				?>
			<?php endif; ?>
			<div class="dz-section" style="margin-top:26px">
				<?php cvc_render_study_sidebar( array( 'legal' ) ); ?>
			</div>
		</div>
	</div>
</div>
</main>

<?php get_footer(); ?>
