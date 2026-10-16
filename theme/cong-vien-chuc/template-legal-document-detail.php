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
	<main id="main" class="dz-main">
		<div class="max-w-3xl mx-auto px-4 space-y-4">
			<?php cvc_render_notfound_state( 'Không tìm thấy văn bản pháp luật này. Văn bản có thể đã được gỡ hoặc đường dẫn không đúng.' ); ?>
			<p><a class="text-acc font-bold" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">&larr; Về thư viện văn bản pháp luật</a></p>
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
	'in_force' => 'bg-acc-soft text-emerald-800 border-acc-line',
	'not_yet'  => 'bg-acc-soft text-acc border-acc-line',
	'expired'  => 'bg-acc-soft text-red-700 border-acc-line',
	'replaced' => 'bg-acc-soft text-red-700 border-acc-line',
	'unknown'  => 'bg-white text-ink-2 border-slate-300',
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

<main id="main" class="dz-main">
<?php cvc_render_track_marker( 'legal_document_viewed', 'legal_document', (int) ( $document['id'] ?? 0 ) ); ?>
<div class="dz-wrap space-y-6">

	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>
	<?php cvc_render_notice(); ?>

	<header class="dz-noticehead dz-lawhead">
		<div class="dz-noticehead-top">
			<p class="dz-noticehead-org"><?php echo esc_html( mb_strtoupper( '' !== $agency ? $agency : 'Cơ quan ban hành', 'UTF-8' ) ); ?><?php if ( '' !== $doc_number ) : ?><span>Số: <?php echo esc_html( $doc_number ); ?></span><?php endif; ?></p>
			<p class="dz-noticehead-status">
				<span class="dz-tag <?php echo 'in_force' === ( $validity['code'] ?? '' ) || 'active' === ( $validity['code'] ?? '' ) ? 'is-ok' : ( 'unknown' === ( $validity['code'] ?? 'unknown' ) ? 'is-off' : 'is-warn' ); ?>"><?php echo esc_html( (string) $validity['label'] ); ?></span>
				<?php if ( ! empty( $amended_by ) ) : ?><span class="dz-tag is-warn">Đã được sửa đổi, bổ sung</span><?php endif; ?>
			</p>
		</div>
		<?php if ( '' !== $doc_type ) : ?><p class="dz-lawtype"><?php echo esc_html( mb_strtoupper( $doc_type, 'UTF-8' ) ); ?></p><?php endif; ?>
		<h1><?php echo esc_html( $title ); ?></h1>
		<span class="dz-rule" aria-hidden="true"></span>
		<div class="dz-noticehead-actions">
			<?php if ( $file && ! empty( $file['download_url'] ) ) : ?>
				<a href="<?php echo esc_url( (string) $file['download_url'] ); ?>" rel="nofollow" class="dz-btn dz-btn-primary"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Tải văn bản gốc (<?php echo esc_html( $file_label ); ?>)</a>
			<?php endif; ?>
			<?php if ( '' !== $source_url ) : ?>
				<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow" class="dz-btn"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> Nguồn chính thức</a>
			<?php endif; ?>
			<?php if ( $has_text ) : ?>
				<a href="#toan-van" class="dz-btn"><i class="fa-solid fa-book-open" aria-hidden="true"></i> Đọc toàn văn<?php echo ! empty( $articles ) ? ' (' . esc_html( (string) count( $articles ) ) . ' Điều)' : ''; ?></a>
			<?php endif; ?>
			<?php if ( $practice && ! empty( $practice['slug'] ) ) : ?>
				<a href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>" class="dz-btn"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Luyện <?php echo esc_html( (string) (int) $practice['total_questions'] ); ?> câu theo văn bản</a>
			<?php endif; ?>
			<?php cvc_render_bookmark_button( 'legal_document', (int) $document['id'] ); ?>
		</div>
	</header>

	<?php if ( $replaced_by && ! empty( $replaced_by['slug'] ) ) : ?>
		<div class="dz-notice dz-notice-warn" role="note">
			Văn bản này đã được thay thế bởi
			<a class="font-bold text-ink underline" href="<?php echo esc_url( cvc_legal_document_url( (string) $replaced_by['slug'] ) ); ?>"><?php echo esc_html( trim( ( $replaced_by['document_number'] ?? '' ) . ' — ' . ( $replaced_by['title'] ?? '' ), ' —' ) ); ?></a>. Không dùng để ôn thi theo quy định hiện hành.
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $amended_by ) ) : ?>
		<div class="dz-notice" role="note">
			<p class="font-bold">Một số Điều của văn bản này đã được sửa đổi, bổ sung bởi:</p>
			<ul class="list-disc pl-5 space-y-0.5">
				<?php foreach ( $amended_by as $am ) : ?>
					<?php if ( empty( $am['slug'] ) ) { continue; } ?>
					<li><a class="font-semibold text-ink underline" href="<?php echo esc_url( cvc_legal_document_url( (string) $am['slug'] ) ); ?>"><?php echo esc_html( trim( ( $am['document_number'] ?? '' ) . ' — ' . ( $am['title'] ?? '' ), ' —' ) ); ?></a><?php echo ! empty( $am['effective_date'] ) ? ' (hiệu lực ' . esc_html( cvc_format_date_vn( (string) $am['effective_date'] ) ) . ')' : ''; ?></li>
				<?php endforeach; ?>
			</ul>
			<p class="text-xs text-acc">Toàn văn dưới đây là bản gốc khi ban hành — đối chiếu văn bản sửa đổi khi ôn thi.</p>
		</div>
	<?php endif; ?>

	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

		<aside class="lg:col-span-4 space-y-5 lg:sticky lg:top-24">
			<section class="dz-card" aria-labelledby="cvc-legal-facts">
				<h2 id="cvc-legal-facts" class="dz-sideh2">Thông tin văn bản</h2>
				<dl class="space-y-2.5 text-xs">
					<?php foreach ( $facts as $label => $value ) : ?>
						<?php if ( '' === (string) $value ) { continue; } ?>
						<div class="flex justify-between gap-3 border-b border-slate-300 pb-1.5">
							<dt class="text-ink-muted"><?php echo esc_html( $label ); ?></dt>
							<dd class="text-ink font-semibold text-right"><?php echo esc_html( (string) $value ); ?></dd>
						</div>
					<?php endforeach; ?>
					<div class="flex justify-between gap-3">
						<dt class="text-ink-muted">Tình trạng</dt>
						<dd class="font-bold text-right"><?php echo esc_html( (string) $validity['label'] ); ?></dd>
					</div>
				</dl>
				<p class="text-[11px] text-ink-muted mt-3">Tình trạng tính theo ngày hiệu lực/hết hiệu lực và văn bản thay thế đã ghi nhận. Luôn đối chiếu văn bản gốc trước khi sử dụng.</p>
			</section>

			<?php if ( ! empty( $articles ) ) : ?>
				<nav class="dz-card hidden lg:block" aria-labelledby="cvc-legal-toc">
					<h2 id="cvc-legal-toc" class="dz-sideh2">Mục lục</h2>
					<ol class="text-xs space-y-1 max-h-[55vh] overflow-y-auto pr-1">
						<?php $toc_chapter = null; ?>
						<?php foreach ( $articles as $a ) : ?>
							<?php if ( ! empty( $a['chapter'] ) && $a['chapter'] !== $toc_chapter ) : $toc_chapter = $a['chapter']; ?>
								<li class="pt-2 font-bold text-acc text-[11px] uppercase"><?php echo esc_html( (string) $a['chapter'] ); ?></li>
							<?php endif; ?>
							<?php list( $toc_title ) = $article_split( $a ); ?>
							<li><a class="block py-0.5 text-ink-2 hover:text-acc-2" href="#<?php echo esc_attr( $article_anchor( $a ) ); ?>"><?php echo esc_html( $article_label( array( 'number' => $a['number'] ?? '', 'title' => $toc_title, 'id' => $a['id'] ?? '' ) ) ); ?></a></li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>

			<?php if ( ! empty( $replaces ) ) : ?>
				<section class="dz-card" aria-labelledby="cvc-legal-replaces">
					<h2 id="cvc-legal-replaces" class="dz-sideh2">Văn bản này thay thế</h2>
					<ul class="space-y-2 text-xs">
						<?php foreach ( $replaces as $old_doc ) : ?>
							<?php if ( empty( $old_doc['slug'] ) ) { continue; } ?>
							<li><a class="text-acc hover:underline" href="<?php echo esc_url( cvc_legal_document_url( (string) $old_doc['slug'] ) ); ?>"><?php echo esc_html( trim( ( $old_doc['document_number'] ?? '' ) . ' — ' . ( $old_doc['title'] ?? '' ), ' —' ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</aside>

		<div class="lg:col-span-8 space-y-5">
			<?php if ( '' !== $summary && ! $has_text ) : ?>
				<section class="dz-panel space-y-3" aria-labelledby="cvc-legal-summary">
					<h2 id="cvc-legal-summary" class="text-lg font-extrabold text-ink">Trích yếu</h2>
					<div class="text-sm text-ink-2 leading-relaxed"><?php echo wp_kses_post( wpautop( esc_html( $summary ) ) ); ?></div>
				</section>
			<?php endif; ?>

			<section id="toan-van" class="dz-panel space-y-5 scroll-mt-24" aria-labelledby="cvc-legal-fulltext">
				<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-300 pb-3">
					<h2 id="cvc-legal-fulltext" class="text-lg font-extrabold text-ink">Toàn văn</h2>
					<?php if ( ! empty( $articles ) ) : ?>
						<label class="relative">
							<span class="sr-only">Tìm trong văn bản</span>
							<input type="search" id="cvc-legal-find" placeholder="Tìm trong văn bản…" class="w-56 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-ink placeholder-slate-400 focus:outline-none focus:border-acc-2 border-slate-400 bg-white" autocomplete="off">
						</label>
					<?php endif; ?>
				</div>

				<?php if ( ! $has_text ) : ?>
					<p class="text-sm text-ink-muted">
						Chưa có toàn văn đã xác minh cho văn bản này.
						<?php if ( '' !== $source_url ) : ?>
							Xem tại <a class="text-acc font-bold" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">nguồn chính thức</a>.
						<?php else : ?>
							Hãy tra cứu trên Công báo Chính phủ (congbao.chinhphu.vn) hoặc Cơ sở dữ liệu quốc gia về văn bản pháp luật.
						<?php endif; ?>
					</p>
				<?php else : ?>
					<p class="text-[11px] text-ink-muted">
						Nội dung lấy từ <?php echo $is_ocr ? 'bản quét chính thức và được số hóa tự động (OCR) — có thể còn lỗi chính tả, dấu câu' : 'văn bản chính thức'; ?>.
						Văn bản có giá trị pháp lý là bản đăng trên Công báo / cổng thông tin của cơ quan ban hành<?php echo $file ? ' — có thể tải bản gốc ở nút phía trên' : ''; ?>.
					</p>

					<?php if ( ! empty( $articles ) ) : ?>
						<div id="cvc-legal-articles" class="space-y-4">
							<?php $cur_chapter = null; $cur_section = null; ?>
							<?php foreach ( $articles as $a ) : ?>
								<?php if ( ! empty( $a['chapter'] ) && $a['chapter'] !== $cur_chapter ) : $cur_chapter = $a['chapter']; $cur_section = null; ?>
									<h3 class="cvc-legal-chapter pt-4 text-center text-sm font-black uppercase tracking-wide text-acc"><?php echo esc_html( (string) $a['chapter'] ); ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $a['section'] ) && $a['section'] !== $cur_section ) : $cur_section = $a['section']; ?>
									<h4 class="text-center text-xs font-bold uppercase text-acc"><?php echo esc_html( (string) $a['section'] ); ?></h4>
								<?php endif; ?>
								<?php list( $a_title, $a_content ) = $article_split( $a ); $a['title'] = $a_title; ?>
								<article id="<?php echo esc_attr( $article_anchor( $a ) ); ?>" class="dz-card cvc-legal-article scroll-mt-24">
									<h4 class="text-sm font-extrabold text-ink mb-2"><?php echo esc_html( $article_label( $a ) ); ?></h4>
									<div class="text-[13px] sm:text-sm text-ink-2 leading-relaxed whitespace-pre-line"><?php echo esc_html( $legal_reflow( $a_content ) ); ?></div>
								</article>
							<?php endforeach; ?>
						</div>
						<p id="cvc-legal-find-empty" class="hidden text-sm text-ink-muted">Không có Điều nào chứa từ khóa này.</p>
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
						<div class="text-[13px] sm:text-sm text-ink-2 leading-relaxed whitespace-pre-line"><?php echo esc_html( $legal_reflow( $full_text ) ); ?></div>
					<?php endif; ?>
				<?php endif; ?>
			</section>

			<section class="dz-panel space-y-4" aria-labelledby="cvc-legal-study">
				<div class="flex flex-wrap items-center justify-between gap-2">
					<h2 id="cvc-legal-study" class="text-lg font-extrabold text-ink">Ôn thi theo văn bản này</h2>
					<?php if ( $q_count > 0 ) : ?>
						<span class="text-xs text-acc font-bold"><?php echo esc_html( number_format_i18n( $q_count ) ); ?> câu hỏi trong ngân hàng đề</span>
					<?php endif; ?>
				</div>
				<?php if ( empty( $knowledge ) ) : ?>
					<p class="text-sm text-ink-muted">Chưa có bài học nào gắn với văn bản này.</p>
				<?php else : ?>
					<ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
						<?php foreach ( $knowledge as $k_item ) : ?>
							<?php if ( empty( $k_item['slug'] ) ) { continue; } ?>
							<li>
								<a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $k_item['slug'] ) ); ?>" class="dz-card block h-full hover:bg-acc-soft space-y-1">
									<span class="block text-sm font-bold text-ink"><?php echo esc_html( (string) ( $k_item['title'] ?? '' ) ); ?></span>
									<?php if ( ! empty( $k_item['summary'] ) ) : ?>
										<span class="block text-xs text-ink-muted line-clamp-2"><?php echo esc_html( (string) $k_item['summary'] ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="flex flex-wrap gap-2 pt-1">
					<?php if ( $practice && ! empty( $practice['slug'] ) ) : ?>
						<a href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>" class="px-4 py-2 bg-acc hover:bg-acc-deep text-white font-black text-xs rounded-xl">Luyện tập <?php echo esc_html( (string) (int) $practice['total_questions'] ); ?> câu theo văn bản</a>
					<?php else : ?>
						<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="px-4 py-2 bg-acc hover:bg-acc-deep text-white font-black text-xs rounded-xl">Làm đề thi thử</a>
					<?php endif; ?>
					<?php if ( ! empty( $knowledge[0]['topic']['slug'] ) ) : ?>
						<a href="<?php echo esc_url( cvc_topic_url( (string) $knowledge[0]['topic']['slug'] ) ); ?>" class="px-4 py-2 bg-white hover:bg-acc-soft text-acc border border-acc-line font-bold text-xs rounded-xl">Tất cả bài học của văn bản</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="px-4 py-2 bg-white hover:bg-acc-soft text-acc border border-acc-line font-bold text-xs rounded-xl">Văn bản khác</a>
				</div>
			</section>
		</div>
	</div>
</div>
</main>

<?php get_footer(); ?>
