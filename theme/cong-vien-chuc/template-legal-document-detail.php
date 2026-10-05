<?php
/**
 * Chi tiết văn bản pháp luật — /van-ban-phap-luat/{slug}/
 *
 * 10/2026: hiển thị TOÀN VĂN CHÍNH THỨC (lấy từ Công báo / cổng thông tin cơ
 * quan ban hành, tách theo Chương/Điều, có mục lục) + tải file văn bản gốc.
 * Chỉ áp dụng với văn bản đã xác minh nguồn chính thức (is_verified).
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
$articles    = is_array( $document['articles'] ?? null ) ? $document['articles'] : array();
$full_text   = (string) ( $document['full_text'] ?? '' );
$file        = is_array( $document['file'] ?? null ) ? $document['file'] : null;
$practice    = is_array( $document['practice_exam'] ?? null ) ? $document['practice_exam'] : null;
$amended_by  = is_array( $document['amended_by'] ?? null ) ? $document['amended_by'] : array();
$is_verified = ! empty( $document['is_verified'] );
$is_ocr      = ! empty( $document['is_ocr'] );
$has_text    = ! empty( $articles ) || '' !== $full_text;

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

$file_label = '';
if ( $file ) {
	$ext        = strtoupper( (string) ( $file['extension'] ?? 'PDF' ) );
	$size_kb    = (int) round( ( (int) ( $file['size'] ?? 0 ) ) / 1024 );
	$file_label = $ext . ( $size_kb > 0 ? ' · ' . ( $size_kb >= 1024 ? number_format_i18n( $size_kb / 1024, 1 ) . ' MB' : $size_kb . ' KB' ) : '' );
}
$article_anchor = static function ( array $a ): string {
	$n = (string) ( $a['number'] ?? '' );
	return 'dieu-' . sanitize_title( '' !== $n ? $n : (string) ( $a['id'] ?? '' ) );
};
/*
 * Van ban lay tu PDF giu nguyen ngat dong cua trang in: noi lai cac dong bi ngat
 * giua cau, giu xuong dong truoc khoan/diem ("1.", "a)", "-"). Tieu de Dieu dai 2 dong
 * (dong 2 bat dau bang chu thuong) duoc noi vao tieu de.
 */
$legal_reflow = static function ( string $text ): string {
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	// Dau trang/chan trang cua Cong bao in lan vao noi dung ("36 CÔNG BÁO/Số 951 + 952/Ngày 21-7-2025").
	$text = preg_replace( '/\s*(\d{1,4}\s+)?CÔNG BÁO\s*\/\s*Số\s*[\d\s+]+\/\s*Ngày\s*[\d\-–]+(\s+\d{1,4}(?=\s))?/u', ' ', $text );
	$text = preg_replace( '/[ \t]+/u', ' ', $text );
	$text = preg_replace( '/\n\s*\n+/u', "\n", $text );
	$text = preg_replace( '/\n(?!\s*(\d{1,3}[.)]\s|[a-zđ]{1,2}[)]\s|[-–•+]\s|Điều\s+\d|Chương\s|Mục\s|Phụ lục|[A-ZĐÂĂÊÔƠƯ]{2,}\s[A-ZĐÂĂÊÔƠƯ]))/u', ' ', $text );
	return trim( (string) $text );
};
$article_split = static function ( array $a ): array {
	$title   = (string) ( $a['title'] ?? '' );
	$content = trim( (string) ( $a['content'] ?? '' ) );
	if ( '' !== $title && preg_match( '/^([a-zàáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ][^\n]{0,120})\n/u', $content, $m ) ) {
		$title   .= ' ' . trim( $m[1] );
		$content  = trim( substr( $content, strlen( $m[0] ) ) );
	}
	return array( $title, $content );
};
$article_label = static function ( array $a ): string {
	$n = (string) ( $a['number'] ?? '' );
	if ( '' !== $n && 'P' === $n[0] ) {
		return (string) ( $a['title'] ?? $n );
	}
	return 'Điều ' . $n . ( ! empty( $a['title'] ) ? '. ' . $a['title'] : '' );
};
?>

<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-8">
<?php cvc_render_track_marker( 'legal_document_viewed', 'legal_document', (int) ( $document['id'] ?? 0 ) ); ?>
<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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
			<?php if ( ! empty( $amended_by ) ) : ?>
				<span class="border border-amber-500/40 bg-amber-500/15 text-amber-300 text-[11px] font-bold px-2.5 py-0.5 rounded-full">Đã được sửa đổi, bổ sung</span>
			<?php endif; ?>
		</div>

		<h1 class="text-2xl sm:text-3xl font-black leading-tight text-white"><?php echo esc_html( $title ); ?></h1>

		<div class="flex flex-wrap items-center gap-3">
			<?php if ( $file && ! empty( $file['download_url'] ) ) : ?>
				<a href="<?php echo esc_url( (string) $file['download_url'] ); ?>" rel="nofollow" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 text-xs font-black rounded-xl shadow">
					<i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Tải văn bản gốc <span class="font-semibold opacity-80">(<?php echo esc_html( $file_label ); ?>)</span>
				</a>
			<?php endif; ?>
			<?php if ( '' !== $source_url ) : ?>
				<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 text-xs font-bold rounded-xl border border-cyan-400/40">
					<i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> Nguồn chính thức
				</a>
			<?php endif; ?>
			<?php if ( $has_text ) : ?>
				<a href="#toan-van" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl border border-slate-700">
					<i class="fa-solid fa-book-open" aria-hidden="true"></i> Đọc toàn văn<?php echo ! empty( $articles ) ? ' (' . esc_html( (string) count( $articles ) ) . ' Điều)' : ''; ?>
				</a>
			<?php endif; ?>
			<?php if ( $practice && ! empty( $practice['slug'] ) ) : ?>
				<a href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 text-xs font-black rounded-xl">
					<i class="fa-solid fa-list-check" aria-hidden="true"></i> Luyện <?php echo esc_html( (string) (int) $practice['total_questions'] ); ?> câu theo văn bản
				</a>
			<?php endif; ?>
			<?php cvc_render_bookmark_button( 'legal_document', (int) $document['id'] ); ?>
		</div>
	</header>

	<?php if ( $replaced_by && ! empty( $replaced_by['slug'] ) ) : ?>
		<div class="p-4 rounded-2xl border border-rose-500/40 bg-rose-500/10 text-sm text-rose-100" role="note">
			Văn bản này đã được thay thế bởi
			<a class="font-bold text-white underline" href="<?php echo esc_url( cvc_legal_document_url( (string) $replaced_by['slug'] ) ); ?>"><?php echo esc_html( trim( ( $replaced_by['document_number'] ?? '' ) . ' — ' . ( $replaced_by['title'] ?? '' ), ' —' ) ); ?></a>. Không dùng để ôn thi theo quy định hiện hành.
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $amended_by ) ) : ?>
		<div class="p-4 rounded-2xl border border-amber-500/40 bg-amber-500/10 text-sm text-amber-100 space-y-1" role="note">
			<p class="font-bold">Một số Điều của văn bản này đã được sửa đổi, bổ sung bởi:</p>
			<ul class="list-disc pl-5 space-y-0.5">
				<?php foreach ( $amended_by as $am ) : ?>
					<?php if ( empty( $am['slug'] ) ) { continue; } ?>
					<li><a class="font-semibold text-white underline" href="<?php echo esc_url( cvc_legal_document_url( (string) $am['slug'] ) ); ?>"><?php echo esc_html( trim( ( $am['document_number'] ?? '' ) . ' — ' . ( $am['title'] ?? '' ), ' —' ) ); ?></a><?php echo ! empty( $am['effective_date'] ) ? ' (hiệu lực ' . esc_html( cvc_format_date_vn( (string) $am['effective_date'] ) ) . ')' : ''; ?></li>
				<?php endforeach; ?>
			</ul>
			<p class="text-xs text-amber-200/80">Toàn văn dưới đây là bản gốc khi ban hành — đối chiếu văn bản sửa đổi khi ôn thi.</p>
		</div>
	<?php endif; ?>

	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

		<aside class="lg:col-span-4 space-y-5 lg:sticky lg:top-24">
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

			<?php if ( ! empty( $articles ) ) : ?>
				<nav class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl shadow-lg hidden lg:block" aria-labelledby="cvc-legal-toc">
					<h2 id="cvc-legal-toc" class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 mb-3">Mục lục</h2>
					<ol class="text-xs space-y-1 max-h-[55vh] overflow-y-auto pr-1">
						<?php $toc_chapter = null; ?>
						<?php foreach ( $articles as $a ) : ?>
							<?php if ( ! empty( $a['chapter'] ) && $a['chapter'] !== $toc_chapter ) : $toc_chapter = $a['chapter']; ?>
								<li class="pt-2 font-bold text-amber-300 text-[11px] uppercase"><?php echo esc_html( (string) $a['chapter'] ); ?></li>
							<?php endif; ?>
							<?php list( $toc_title ) = $article_split( $a ); ?>
							<li><a class="block py-0.5 text-slate-300 hover:text-cyan-300" href="#<?php echo esc_attr( $article_anchor( $a ) ); ?>"><?php echo esc_html( $article_label( array( 'number' => $a['number'] ?? '', 'title' => $toc_title, 'id' => $a['id'] ?? '' ) ) ); ?></a></li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>

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
			<?php if ( '' !== $summary && ! $has_text ) : ?>
				<section class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-3 shadow-xl" aria-labelledby="cvc-legal-summary">
					<h2 id="cvc-legal-summary" class="text-lg font-extrabold text-white">Trích yếu</h2>
					<div class="text-sm text-slate-300 leading-relaxed"><?php echo wp_kses_post( wpautop( esc_html( $summary ) ) ); ?></div>
				</section>
			<?php endif; ?>

			<section id="toan-van" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 shadow-xl scroll-mt-24" aria-labelledby="cvc-legal-fulltext">
				<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
					<h2 id="cvc-legal-fulltext" class="text-lg font-extrabold text-white">Toàn văn</h2>
					<?php if ( ! empty( $articles ) ) : ?>
						<label class="relative">
							<span class="sr-only">Tìm trong văn bản</span>
							<input type="search" id="cvc-legal-find" placeholder="Tìm trong văn bản…" class="w-56 bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400" autocomplete="off">
						</label>
					<?php endif; ?>
				</div>

				<?php if ( ! $has_text ) : ?>
					<p class="text-sm text-slate-400">
						Chưa có toàn văn đã xác minh cho văn bản này.
						<?php if ( '' !== $source_url ) : ?>
							Xem tại <a class="text-cyan-300 font-bold" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">nguồn chính thức</a>.
						<?php else : ?>
							Hãy tra cứu trên Công báo Chính phủ (congbao.chinhphu.vn) hoặc Cơ sở dữ liệu quốc gia về văn bản pháp luật.
						<?php endif; ?>
					</p>
				<?php else : ?>
					<p class="text-[11px] text-slate-500">
						Nội dung lấy từ <?php echo $is_ocr ? 'bản quét chính thức và được số hóa tự động (OCR) — có thể còn lỗi chính tả, dấu câu' : 'văn bản chính thức'; ?>.
						Văn bản có giá trị pháp lý là bản đăng trên Công báo / cổng thông tin của cơ quan ban hành<?php echo $file ? ' — có thể tải bản gốc ở nút phía trên' : ''; ?>.
					</p>

					<?php if ( ! empty( $articles ) ) : ?>
						<div id="cvc-legal-articles" class="space-y-4">
							<?php $cur_chapter = null; $cur_section = null; ?>
							<?php foreach ( $articles as $a ) : ?>
								<?php if ( ! empty( $a['chapter'] ) && $a['chapter'] !== $cur_chapter ) : $cur_chapter = $a['chapter']; $cur_section = null; ?>
									<h3 class="cvc-legal-chapter pt-4 text-center text-sm font-black uppercase tracking-wide text-amber-300"><?php echo esc_html( (string) $a['chapter'] ); ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $a['section'] ) && $a['section'] !== $cur_section ) : $cur_section = $a['section']; ?>
									<h4 class="text-center text-xs font-bold uppercase text-cyan-300"><?php echo esc_html( (string) $a['section'] ); ?></h4>
								<?php endif; ?>
								<?php list( $a_title, $a_content ) = $article_split( $a ); $a['title'] = $a_title; ?>
								<article id="<?php echo esc_attr( $article_anchor( $a ) ); ?>" class="cvc-legal-article scroll-mt-24 rounded-2xl border border-slate-800 bg-slate-900/60 p-4 sm:p-5">
									<h4 class="text-sm font-extrabold text-white mb-2"><?php echo esc_html( $article_label( $a ) ); ?></h4>
									<div class="text-[13px] sm:text-sm text-slate-300 leading-relaxed whitespace-pre-line"><?php echo esc_html( $legal_reflow( $a_content ) ); ?></div>
								</article>
							<?php endforeach; ?>
						</div>
						<p id="cvc-legal-find-empty" class="hidden text-sm text-slate-400">Không có Điều nào chứa từ khóa này.</p>
						<script>
						(function () {
							var input = document.getElementById('cvc-legal-find');
							if (!input) { return; }
							var items = Array.prototype.slice.call(document.querySelectorAll('#cvc-legal-articles .cvc-legal-article'));
							var heads = document.querySelectorAll('#cvc-legal-articles .cvc-legal-chapter, #cvc-legal-articles h4.text-center');
							var empty = document.getElementById('cvc-legal-find-empty');
							var norm = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd'); };
							var cache = items.map(function (el) { return norm(el.textContent); });
							var t;
							input.addEventListener('input', function () {
								clearTimeout(t);
								t = setTimeout(function () {
									var q = norm(input.value.trim()); var shown = 0;
									items.forEach(function (el, i) { var ok = !q || cache[i].indexOf(q) !== -1; el.hidden = !ok; if (ok) { shown++; } });
									Array.prototype.forEach.call(heads, function (h) { h.hidden = !!q; });
									empty.classList.toggle('hidden', shown > 0);
								}, 150);
							});
						})();
						</script>
					<?php else : ?>
						<div class="text-[13px] sm:text-sm text-slate-300 leading-relaxed whitespace-pre-line"><?php echo esc_html( $legal_reflow( $full_text ) ); ?></div>
					<?php endif; ?>
				<?php endif; ?>
			</section>

			<section class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4 shadow-xl" aria-labelledby="cvc-legal-study">
				<div class="flex flex-wrap items-center justify-between gap-2">
					<h2 id="cvc-legal-study" class="text-lg font-extrabold text-white">Ôn thi theo văn bản này</h2>
					<?php if ( $q_count > 0 ) : ?>
						<span class="text-xs text-cyan-300 font-bold"><?php echo esc_html( number_format_i18n( $q_count ) ); ?> câu hỏi trong ngân hàng đề</span>
					<?php endif; ?>
				</div>
				<?php if ( empty( $knowledge ) ) : ?>
					<p class="text-sm text-slate-400">Chưa có bài học nào gắn với văn bản này.</p>
				<?php else : ?>
					<ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
						<?php foreach ( $knowledge as $k_item ) : ?>
							<?php if ( empty( $k_item['slug'] ) ) { continue; } ?>
							<li>
								<a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $k_item['slug'] ) ); ?>" class="block h-full p-4 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 space-y-1">
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
					<?php if ( $practice && ! empty( $practice['slug'] ) ) : ?>
						<a href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-xs rounded-xl">Luyện tập <?php echo esc_html( (string) (int) $practice['total_questions'] ); ?> câu theo văn bản</a>
					<?php else : ?>
						<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-xs rounded-xl">Làm đề thi thử</a>
					<?php endif; ?>
					<?php if ( ! empty( $knowledge[0]['topic']['slug'] ) ) : ?>
						<a href="<?php echo esc_url( cvc_topic_url( (string) $knowledge[0]['topic']['slug'] ) ); ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold text-xs rounded-xl">Tất cả bài học của văn bản</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold text-xs rounded-xl">Văn bản khác</a>
				</div>
			</section>
		</div>
	</div>
</div>
</main>

<?php get_footer(); ?>
