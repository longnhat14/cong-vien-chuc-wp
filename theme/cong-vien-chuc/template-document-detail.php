<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT TÀI LIỆU (Document model, Phase 11)
 * URL: /tai-lieu/{slug}/ — xem thông tin + tải (free) hoặc mua qua VNPay (trả phí).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_document_slug' ) );

if ( '' === $slug ) {
	status_header( 404 );
	get_header();
	cvc_render_error_state( 'Không tìm thấy tài liệu.' );
	get_footer();
	return;
}

$token  = cvc_auth_token();
$result = ( new CVC_Document_Service() )->find( $slug, $token );

if ( ! $result['ok'] ) {
	status_header( 404 === $result['status'] ? 404 : 500 );
	get_header();
	cvc_render_error_state( 404 === $result['status'] ? 'Không tìm thấy tài liệu này.' : 'Không thể tải thông tin tài liệu lúc này.' );
	get_footer();
	return;
}

$doc          = $result['data']['data'] ?? array();
$is_free      = ! empty( $doc['is_free'] );
$can_download = ! empty( $doc['can_download'] );
$price        = (float) ( $doc['effective_price'] ?? 0 );
$is_logged_in = cvc_is_logged_in();

cvc_seo_set_title( ( $doc['title'] ?? 'Tài liệu' ) . ' — Kho Tài Liệu Công Viên Chức' );
cvc_seo_set_description( wp_strip_all_tags( (string) ( $doc['description'] ?? '' ) ) );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-6">

		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Kho Tài Liệu', 'url' => cvc_documents_url() ),
				array( 'label' => $doc['title'] ?? '' ),
			)
		);
		?>

		<?php cvc_render_notice(); ?>

		<div class="bg-[#0A192F] border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">

			<div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-slate-800 pb-5">
				<div class="flex items-start gap-4">
					<div class="w-14 h-14 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-2xl border border-amber-500/30 shrink-0">
						<i class="fa-solid fa-file-pdf"></i>
					</div>
					<div class="space-y-1.5">
						<span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider"><?php echo esc_html( $doc['category'] ?? 'Tài liệu' ); ?></span>
						<h1 class="text-lg sm:text-xl font-black text-white leading-snug"><?php echo esc_html( $doc['title'] ?? '' ); ?></h1>
						<p class="text-[11px] text-slate-500 font-mono"><?php echo esc_html( number_format( (int) ( $doc['download_count'] ?? 0 ) ) ); ?> lượt tải &middot; <?php echo esc_html( size_format( (int) ( $doc['file_size'] ?? 0 ) ) ); ?></p>
					</div>
				</div>
				<div class="shrink-0">
					<?php if ( $is_free ) : ?>
						<span class="px-3 py-1.5 bg-emerald-500 text-navy-950 font-black text-xs rounded-xl uppercase">Miễn phí</span>
					<?php else : ?>
						<span class="px-3 py-1.5 bg-amber-500 text-navy-950 font-black text-sm rounded-xl"><?php echo esc_html( number_format( $price, 0, ',', '.' ) ); ?>đ</span>
					<?php endif; ?>
				</div>
			</div>

			<p class="text-sm text-slate-300 leading-relaxed"><?php echo esc_html( $doc['description'] ?? '' ); ?></p>

			<div class="pt-2">
				<?php if ( $can_download ) : ?>
					<a href="<?php echo esc_url( cvc_document_download_url( $slug ) ); ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl shadow-lg transition-transform hover:scale-[1.02]">
						<i class="fa-solid fa-download"></i> Tải Về Ngay
					</a>
				<?php elseif ( ! $is_logged_in ) : ?>
					<a href="<?php echo esc_url( cvc_login_url( cvc_document_url( $slug ) ) ); ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-black text-sm rounded-xl shadow-lg transition-transform hover:scale-[1.02]">
						<i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập Để Mua Tài Liệu
					</a>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'cvc_document_buy' ); ?>
						<input type="hidden" name="action" value="cvc_document_buy">
						<input type="hidden" name="document_id" value="<?php echo esc_attr( (string) ( $doc['id'] ?? 0 ) ); ?>">
						<input type="hidden" name="document_slug" value="<?php echo esc_attr( $slug ); ?>">
						<button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl shadow-lg transition-transform hover:scale-[1.02]">
							<i class="fa-solid fa-cart-shopping"></i> Mua Ngay — Thanh Toán VNPay
						</button>
					</form>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $doc['recruitment'] ) || ! empty( $doc['exam_subject'] ) ) : ?>
				<div class="pt-4 border-t border-slate-800 flex flex-wrap gap-3 text-[11px]">
					<?php if ( ! empty( $doc['recruitment']['slug'] ) ) : ?>
						<a href="<?php echo esc_url( cvc_recruitment_url( $doc['recruitment']['slug'] ) ); ?>" class="px-3 py-1.5 bg-slate-900 border border-slate-700 hover:border-amber-400 rounded-lg text-slate-300">
							<i class="fa-solid fa-briefcase mr-1"></i> Liên quan: <?php echo esc_html( $doc['recruitment']['title'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>

	</div>

</main>

<?php get_footer(); ?>
