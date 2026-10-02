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
		return '<span class="px-2.5 py-1 bg-emerald-500 text-navy-950 font-black text-[10px] rounded-lg uppercase">Miễn phí</span>';
	}

	$price = (float) ( $doc['effective_price'] ?? 0 );

	return '<span class="px-2.5 py-1 bg-amber-500 text-navy-950 font-black text-[10px] rounded-lg">' . number_format( $price, 0, ',', '.' ) . 'đ</span>';
}
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Kho Tài Liệu' ),
			)
		);
		?>

		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/30 shadow-2xl space-y-4">
			<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Thư Viện Tài Liệu 2026</span>
			<h1 class="text-xl sm:text-2xl font-black text-white">Kho Tài Liệu Ôn Thi Công Chức, Viên Chức</h1>
			<p class="text-xs text-slate-300 max-w-2xl">Bộ đề thi thử, sơ đồ tư duy hệ thống hoá kiến thức, sổ tay bẫy trắc nghiệm — có tài liệu <strong class="text-emerald-400">miễn phí</strong> cho mọi người, có tài liệu <strong class="text-amber-400">trả phí</strong> biên soạn chuyên sâu.</p>

			<div class="flex flex-wrap items-center gap-2 pt-2">
				<?php
				$filters = array(
					''  => 'Tất cả',
					'1' => 'Miễn phí',
					'0' => 'Trả phí',
				);
				foreach ( $filters as $value => $label ) :
					$url        = '' === $value ? cvc_documents_url() : add_query_arg( 'is_free', $value, cvc_documents_url() );
					$is_active  = $filter === $value;
					$btn_class  = $is_active
						? 'bg-amber-500 text-navy-950 border-amber-400'
						: 'bg-slate-900/60 text-slate-300 border-slate-700 hover:border-amber-400 hover:text-white';
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="px-4 py-1.5 rounded-full text-xs font-bold border transition-colors <?php echo $btn_class; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<?php if ( ! $ok ) : ?>
			<?php cvc_render_error_state( 'Không thể tải danh sách tài liệu lúc này.' ); ?>
		<?php elseif ( empty( $documents ) ) : ?>
			<?php cvc_render_empty_state( 'Chưa có tài liệu nào trong mục này.' ); ?>
		<?php else : ?>
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
				<?php foreach ( $documents as $doc ) : ?>
					<a href="<?php echo esc_url( cvc_document_url( $doc['slug'] ) ); ?>" class="block bg-[#0D1B2A] border border-slate-800 hover:border-amber-500/50 rounded-2xl p-5 space-y-3 shadow-lg transition-colors">
						<div class="flex items-center justify-between">
							<div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-lg border border-amber-500/30">
								<i class="fa-solid fa-file-pdf"></i>
							</div>
							<?php echo cvc_document_price_badge( $doc ); // phpcs:ignore WordPress.Security.EscapeOutput -- markup tĩnh do chính hàm trên build, không chứa input người dùng. ?>
						</div>
						<h3 class="font-extrabold text-sm text-white leading-snug line-clamp-2"><?php echo esc_html( $doc['title'] ); ?></h3>
						<p class="text-[11px] text-slate-400 line-clamp-2"><?php echo esc_html( $doc['description'] ?? '' ); ?></p>
						<div class="flex items-center justify-between pt-2 border-t border-slate-800 text-[10px] text-slate-500 font-mono">
							<span><?php echo esc_html( $doc['category'] ?? 'Tài liệu' ); ?></span>
							<span><?php echo esc_html( number_format( (int) ( $doc['download_count'] ?? 0 ) ) ); ?> lượt tải</span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

			<?php if ( $lastPg > 1 ) : ?>
				<div class="flex items-center justify-center gap-2 pt-4">
					<?php for ( $p = 1; $p <= $lastPg; $p++ ) : ?>
						<a href="<?php echo esc_url( cvc_documents_url( $p ) ); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold <?php echo $p === $currentPg ? 'bg-amber-500 text-navy-950' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'; ?>">
							<?php echo esc_html( (string) $p ); ?>
						</a>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
