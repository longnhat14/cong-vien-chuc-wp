<?php
/**
 * Chi tiết văn bản pháp luật — /van-ban-phap-luat/{slug}/
 *
 * Viết lại Phase 12 chỉ dùng dữ liệu thật từ GET /api/legal-documents/{slug}.
 * Bản cũ có: "toàn văn" các chương/điều do theme tự soạn theo slug (không
 * phải văn bản gốc), khối "AI Legal Diff" gọi endpoint không tồn tại rồi hiện
 * điều khoản bịa, tệp đính kèm/dung lượng giả, người ký giả, "Còn hiệu lực"
 * mặc định, ô "Hỏi AI" chỉ hiện alert và widget bán đề/khóa học với giá cứng.
 * Với văn bản pháp luật, nội dung bịa là rủi ro nghiêm trọng - nên trang chỉ
 * hiển thị thông tin có trong cơ sở dữ liệu và dẫn về nguồn chính thức.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_title( (string) get_query_var( 'cvc_legal_document_slug' ) );

$result   = ( new CVC_Legal_Document_Service() )->find( $slug );
$document = $result['ok'] && is_array( $result['data']['data'] ?? null ) ? $result['data']['data'] : null;
$is_found = null !== $document;

if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
	cvc_seo_set_title( 'Không tìm thấy văn bản pháp luật' );
	get_header();
	?>
	<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-12">
		<div class="max-w-3xl mx-auto px-4 space-y-4">
			<?php cvc_render_notfound_state( 'Không tìm thấy văn bản pháp luật này. Văn bản có thể đã được gỡ hoặc đường dẫn không đúng.' ); ?>
			<p><a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">&larr; Về thư viện văn bản pháp luật</a></p>
		</div>
	</main>
	<?php
	get_footer();
	return;
}

$title       = (string) ( $document['title'] ?? '' );
$doc_number  = (string) ( $document['document_number'] ?? '' );
$doc_type    = (string) ( $document['document_type'] ?? '' );
$agency      = (string) ( $document['issuing_agency'] ?? '' );
$summary     = (string) ( $document['summary'] ?? '' );
$source_url  = (string) ( $document['source_url'] ?? '' );
$validity    = is_array( $document['validity'] ?? null ) ? $document['validity'] : array( 'code' => 'unknown', 'label' => 'Chưa cập nhật hiệu lực' );
$replaced_by = is_array( $document['replaced_by'] ?? null ) ? $document['replaced_by'] : null;
$replaces    = is_array( $document['replaced_documents'] ?? null ) ? $document['replaced_documents'] : array();
$knowledge   = is_array( $document['knowledge_items'] ?? null ) ? $document['knowledge_items'] : array();
$q_count     = (int) ( $document['related_questions_count'] ?? 0 );

$validity_styles = array(
	'in_force' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
	'not_yet'  => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
	'expired'  => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
	'replaced' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
	'unknown'  => 'bg-slate-700 text-slate-300 border-slate-600',
);
$validity_class = $validity_styles[ $validity['code'] ?? 'unknown' ] ?? $validity_styles['unknown'];

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Văn bản pháp luật', 'url' => cvc_legal_documents_url() ),
	array( 'label' => '' !== $doc_number ? $doc_number : wp_trim_words( $title, 6 ) ),
);

cvc_seo_set_title( '' !== $doc_number ? $doc_number . ' — ' . $title : $title );
if ( '' !== $summary ) {
	cvc_seo_set_description( wp_trim_words( $summary, 30 ) );
}
cvc_seo_set_canonical( cvc_legal_document_url( $slug ) );
cvc_seo_set_og( array( 'type' => 'article' ) );
cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );

$facts = array(
	'Số hiệu'          => $doc_number,
	'Loại văn bản'     => $doc_type,
	'Cơ quan ban hành' => $agency,
	'Ngày ban hành'    => cvc_format_date_vn( $document['issued_date'] ?? null ),
	'Ngày hiệu lực'    => cvc_format_date_vn( $document['effective_date'] ?? null ),
	'Ngày hết hiệu lực' => cvc_format_date_vn( $document['expiry_date'] ?? null ),
);

get_header();
?>

<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-8">
<?php cvc_render_track_marker( 'legal_document_viewed', 'legal_document', (int) ( $document['id'] ?? 0 ) ); ?>
<div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>
	<?php cvc_render_notice(); ?>

	<header class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-cyan-500/30 shadow-2xl space-y-4 text-white">
		<div class="flex flex-wrap items-center gap-2">
			<?php if ( '' !== $doc_number ) : ?>
				<span class="bg-cyan-500 text-navy-950 text-[11px] font-black px-3 py-1 rounded-full"><?php echo esc_html( $doc_number ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $doc_type ) : ?>
				<span class="bg-slate-800 text-cyan-300 border border-cyan-500/30 text-[11px] font-bold px-2.5 py-0.5 rounded-full"><?php echo esc_html( $doc_type ); ?></span>
			<?php endif; ?>
			<span class="border text-[11px] font-bold px-2.5 py-0.5 rounded-full <?php echo esc_attr( $validity_class ); ?>"><?php echo esc_html( (string) $validity['label'] ); ?></span>
		</div>

		<h1 class="text-2xl sm:text-3xl font-black leading-tight text-white"><?php echo esc_html( $title ); ?></h1>

		<div class="flex flex-wrap items-center gap-3">
			<?php cvc_render_bookmark_button( 'legal_document', (int) $document['id'] ); ?>
			<?php if ( '' !== $source_url ) : ?>
				<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 text-xs font-bold rounded-xl border border-cyan-400/40">
					<i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> Xem văn bản gốc
				</a>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( $replaced_by && ! empty( $replaced_by['slug'] ) ) : ?>
		<div class="p-4 rounded-2xl border border-rose-500/40 bg-rose-500/10 text-sm text-rose-100" role="note">
			Văn bản này đã có văn bản thay thế:
			<a class="font-bold text-white underline" href="<?php echo esc_url( cvc_legal_document_url( (string) $replaced_by['slug'] ) ); ?>"><?php echo esc_html( trim( ( $replaced_by['document_number'] ?? '' ) . ' — ' . ( $replaced_by['title'] ?? '' ), ' —' ) ); ?></a>
		</div>
	<?php endif; ?>

	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

		<aside class="lg:col-span-4 space-y-5">
			<section class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl shadow-lg" aria-labelledby="cvc-legal-facts">
				<h2 id="cvc-legal-facts" class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 mb-3">Thông tin văn bản</h2>
				<dl class="space-y-2.5 text-xs">
					<?php foreach ( $facts as $label => $value ) : ?>
						<?php if ( '' === (string) $value ) { continue; } ?>
						<div class="flex justify-between gap-3 border-b border-slate-800/60 pb-1.5">
							<dt class="text-slate-400"><?php echo esc_html( $label ); ?></dt>
							<dd class="text-white font-semibold text-right"><?php echo esc_html( (string) $value ); ?></dd>
						</div>
					<?php endforeach; ?>
					<div class="flex justify-between gap-3">
						<dt class="text-slate-400">Tình trạng</dt>
						<dd class="font-bold text-right"><?php echo esc_html( (string) $validity['label'] ); ?></dd>
					</div>
				</dl>
				<p class="text-[11px] text-slate-500 mt-3">Tình trạng tính theo ngày hiệu lực/hết hiệu lực và văn bản thay thế đã ghi nhận. Luôn đối chiếu văn bản gốc trước khi sử dụng.</p>
			</section>

			<?php if ( ! empty( $replaces ) ) : ?>
				<section class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl shadow-lg" aria-labelledby="cvc-legal-replaces">
					<h2 id="cvc-legal-replaces" class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 mb-3">Văn bản này thay thế</h2>
					<ul class="space-y-2 text-xs">
						<?php foreach ( $replaces as $old_doc ) : ?>
							<?php if ( empty( $old_doc['slug'] ) ) { continue; } ?>
							<li><a class="text-cyan-300 hover:underline" href="<?php echo esc_url( cvc_legal_document_url( (string) $old_doc['slug'] ) ); ?>"><?php echo esc_html( trim( ( $old_doc['document_number'] ?? '' ) . ' — ' . ( $old_doc['title'] ?? '' ), ' —' ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</aside>

		<div class="lg:col-span-8 space-y-5">
			<section class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-3 shadow-xl" aria-labelledby="cvc-legal-summary">
				<h2 id="cvc-legal-summary" class="text-lg font-extrabold text-white">Tóm tắt</h2>
				<?php if ( '' !== $summary ) : ?>
					<div class="text-sm text-slate-300 leading-relaxed space-y-3"><?php echo wp_kses_post( wpautop( esc_html( $summary ) ) ); ?></div>
				<?php else : ?>
					<p class="text-sm text-slate-400">Chưa có tóm tắt cho văn bản này.</p>
				<?php endif; ?>
				<p class="text-xs text-slate-500">
					Trang này không đăng toàn văn.
					<?php if ( '' !== $source_url ) : ?>
						Toàn văn chính thức xem tại <a class="text-cyan-300 font-bold" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">nguồn gốc</a>.
					<?php else : ?>
						Hãy tra cứu toàn văn trên Cổng thông tin điện tử Chính phủ hoặc Cơ sở dữ liệu quốc gia về văn bản pháp luật.
					<?php endif; ?>
				</p>
			</section>

			<section class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4 shadow-xl" aria-labelledby="cvc-legal-study">
				<div class="flex flex-wrap items-center justify-between gap-2">
					<h2 id="cvc-legal-study" class="text-lg font-extrabold text-white">Ôn thi theo văn bản này</h2>
					<?php if ( $q_count > 0 ) : ?>
						<span class="text-xs text-cyan-300 font-bold"><?php echo esc_html( number_format_i18n( $q_count ) ); ?> câu hỏi liên quan trong ngân hàng đề</span>
					<?php endif; ?>
				</div>
				<?php if ( empty( $knowledge ) ) : ?>
					<p class="text-sm text-slate-400">Chưa có bài kiến thức nào gắn với văn bản này.</p>
				<?php else : ?>
					<ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
						<?php foreach ( $knowledge as $k_item ) : ?>
							<?php if ( empty( $k_item['slug'] ) ) { continue; } ?>
							<li>
								<a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $k_item['slug'] ) ); ?>" class="block h-full p-4 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 space-y-1">
									<?php if ( ! empty( $k_item['topic']['name'] ) ) : ?>
										<span class="text-[10px] font-bold uppercase text-amber-400"><?php echo esc_html( (string) $k_item['topic']['name'] ); ?></span>
									<?php endif; ?>
									<span class="block text-sm font-bold text-white"><?php echo esc_html( (string) ( $k_item['title'] ?? '' ) ); ?></span>
									<?php if ( ! empty( $k_item['summary'] ) ) : ?>
										<span class="block text-xs text-slate-400 line-clamp-2"><?php echo esc_html( (string) $k_item['summary'] ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="flex flex-wrap gap-2 pt-1">
					<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-xs rounded-xl">Làm đề thi thử</a>
					<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold text-xs rounded-xl">Văn bản khác</a>
				</div>
			</section>
		</div>
	</div>
</div>
</main>

<?php get_footer(); ?>
