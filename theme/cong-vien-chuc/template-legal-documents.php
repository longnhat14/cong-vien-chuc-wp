<?php
/**
 * CÔNG VIÊN CHỨC — THƯ VIỆN PHÁN QUYẾT & QUẢN LÝ VĂN BẢN PHÁP LUẬT (Executive 3-Column Architecture)
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

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Văn bản pháp luật' ),
			)
		);
		?>

		<?php
		$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : ( isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null );
		cvc_render_listing_hero(
			'Văn bản pháp luật',
			'Văn bản pháp luật về công chức, viên chức',
			'Tra cứu số hiệu, cơ quan ban hành, ngày hiệu lực và văn bản thay thế; dẫn về nguồn chính thức.',
			null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'văn bản' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
		);
		?>

		<!-- 3-COLUMN EXECUTIVE SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — CATEGORIES, ISSUING AGENCIES & FILTERS) -->
			<aside class="lg:col-span-3 space-y-5">
				<?php cvc_render_listing_search( cvc_legal_documents_url(), $q, 'Số hiệu, tên văn bản...', 'Tìm văn bản' ); ?>
				<?php cvc_render_listing_explore_nav( 'legal-documents' ); ?>
			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — LEGAL DOCUMENTS CARDS WITH EXTRACTION) -->
			<div class="lg:col-span-6 space-y-6">

				<nav class="flex flex-wrap items-center gap-2 bg-navy-950 p-3 rounded-2xl border border-slate-800 text-xs shadow-md" aria-label="Lọc theo loại văn bản">
					<?php
					$type_url = static function ( string $t ) use ( $q ): string {
						$u = cvc_legal_documents_url();
						if ( '' !== $q ) {
							$u = add_query_arg( 'q', rawurlencode( $q ), $u );
						}
						return '' !== $t ? add_query_arg( 'loai', rawurlencode( $t ), $u ) : $u;
					};
					?>
					<a href="<?php echo esc_url( $type_url( '' ) ); ?>" class="px-3 py-1.5 rounded-xl font-bold <?php echo '' === $type_filter ? 'bg-cyan-500 text-navy-950' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'; ?>">Tất cả</a>
					<?php foreach ( $type_options as $opt ) : ?>
						<a href="<?php echo esc_url( $type_url( $opt ) ); ?>" class="px-3 py-1.5 rounded-xl font-bold <?php echo $opt === $type_filter ? 'bg-cyan-500 text-navy-950' : 'bg-slate-900 text-slate-300 hover:bg-slate-800'; ?>"><?php echo esc_html( $opt ); ?></a>
					<?php endforeach; ?>
				</nav>

				<?php if ( ! $ok ) : ?>
					<?php cvc_render_error_state( 'Không tải được danh sách văn bản pháp luật, vui lòng thử lại sau.' ); ?>
				<?php elseif ( empty( $documents ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có văn bản pháp luật nào.' ); ?>
				<?php else : ?>
					<div class="space-y-5">
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
							$summaryText   = (string) ( $doc['summary'] ?? '' );
							$replacedBy    = $doc['replaced_by'] ?? null;
						?>
							<article class="bg-navy-950 border border-slate-800 hover:border-cyan-500/60 rounded-3xl p-6 space-y-4 shadow-xl transition-all relative overflow-hidden group">
								
								<!-- HEADER BADGES -->
								<div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
									<div class="flex items-center gap-2">
										<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-black px-2.5 py-1 rounded-lg uppercase border border-cyan-500/30 flex items-center gap-1">
											<?php echo esc_html( '' !== $docNum ? $docNum : 'Văn bản' ); ?>
										</span>
										<span class="bg-slate-800 text-slate-300 text-[10px] font-bold px-2 py-0.5 rounded border border-slate-700">
											<?php echo esc_html( $docType ); ?>
										</span>
									</div>
									<div class="flex items-center gap-2">
										<?php if ( ! empty( $doc['has_full_text'] ) ) : ?>
											<span class="text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-500/40 bg-emerald-500/10 text-emerald-300">Có toàn văn</span>
										<?php endif; ?>
										<?php if ( ! empty( $doc['has_file'] ) ) : ?>
											<span class="text-[10px] font-bold px-2 py-0.5 rounded border border-amber-500/40 bg-amber-500/10 text-amber-300">Tải bản gốc</span>
										<?php endif; ?>
										<?php if ( ! empty( $replacedBy ) ) : ?>
											<span class="text-xs text-rose-300 font-extrabold">Đã có văn bản thay thế</span>
										<?php endif; ?>
									</div>
								</div>

								<!-- TITLE & LINK -->
								<h2 class="font-extrabold text-base sm:text-lg text-white group-hover:text-cyan-400 transition-colors leading-snug">
									<a href="<?php echo esc_url( $docUrl ); ?>">
										<?php echo esc_html( (string) ( $doc['title'] ?? '' ) ); ?>
									</a>
								</h2>

								<?php if ( '' !== $summaryText ) : ?>
									<p class="text-xs sm:text-sm text-slate-300 line-clamp-2 leading-relaxed font-light"><?php echo esc_html( $summaryText ); ?></p>
								<?php endif; ?>

								<dl class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 bg-slate-900/80 rounded-2xl border border-slate-800/80 text-[11px] text-slate-300">
									<?php if ( '' !== $agencyName ) : ?>
										<div><dt class="text-slate-500 text-[9px] uppercase font-bold">Cơ quan ban hành</dt><dd class="text-white font-semibold truncate" title="<?php echo esc_attr( $agencyName ); ?>"><?php echo esc_html( $agencyName ); ?></dd></div>
									<?php endif; ?>
									<?php if ( '' !== $issuedDate ) : ?>
										<div><dt class="text-slate-500 text-[9px] uppercase font-bold">Ngày ban hành</dt><dd class="text-white font-semibold"><?php echo esc_html( $issuedDate ); ?></dd></div>
									<?php endif; ?>
									<?php if ( '' !== $effectiveDate ) : ?>
										<div><dt class="text-slate-500 text-[9px] uppercase font-bold">Ngày hiệu lực</dt><dd class="text-emerald-400 font-extrabold"><?php echo esc_html( $effectiveDate ); ?></dd></div>
									<?php endif; ?>
								</dl>

								<div class="pt-3 border-t border-slate-800/80 flex justify-end">
									<a href="<?php echo esc_url( $docUrl ); ?>" class="w-full sm:w-auto px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-navy-950 font-black rounded-xl text-xs shadow text-center">
										Xem chi tiết <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
									</a>
								</div>

							</article>
						<?php endforeach; ?>
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

			</div>

			<!-- RIGHT SIDEBAR (3 COLS — MONETIZATION STORE & HIGH CONVERSION CTAS) -->
			<aside class="lg:col-span-3 space-y-5 sticky top-[80px]">
				<?php cvc_render_study_sidebar(); ?>
			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
