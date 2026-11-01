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
// 01/11/2026: biên tập viên xem trước tài liệu chưa xuất bản (?xem-truoc=1).
$is_preview = isset( $_GET['xem-truoc'] ) && $token && cvc_admin_can( 'document.view' ); // phpcs:ignore WordPress.Security.NonceVerification
$result     = $is_preview
	? ( new CVC_Api_Client() )->get( '/api/admin/documents/preview/' . rawurlencode( $slug ), array(), $token )
	: ( new CVC_Document_Service() )->find( $slug, $token );

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

// 01/11/2026 (SEO): tiêu đề gọn (hậu tố thương hiệu do cvc_seo_filter_title thêm), canonical đúng trang,
// schema Article + BreadcrumbList; citation = văn bản pháp luật thực sự được dẫn trong nội dung.
$doc_title = (string) ( $doc['title'] ?? 'Tài liệu' );
$doc_url   = cvc_document_url( $slug );
cvc_seo_set_title( $doc_title );
cvc_seo_set_description( wp_strip_all_tags( (string) ( $doc['description'] ?? '' ) ) );
if ( $is_preview || false === ( $doc['is_indexable'] ?? true ) ) { // tài liệu nội bộ (hồ sơ phát hành) không lập chỉ mục
	cvc_seo_set_noindex();
} else {
	cvc_seo_set_canonical( $doc_url );
}
// 01/11/2026: ảnh chia sẻ = sách 3D có giá (dựng tự động phía backend).
if ( ! empty( $doc['images']['og'] ) ) {
	cvc_seo_set_og( array( 'image' => (string) $doc['images']['og'], 'type' => 'article' ) );
}
if ( ! $is_preview && false !== ( $doc['is_indexable'] ?? true ) ) {
	$doc_book = (array) ( $doc['book'] ?? array() );
	$article  = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Article',
		'@id'                 => $doc_url . '#tai-lieu',
		'mainEntityOfPage'    => $doc_url,
		'headline'            => mb_strimwidth( $doc_title, 0, 110, '…' ),
		'name'                => $doc_title,
		'description'         => cvc_seo_trim( (string) ( $doc['description'] ?? '' ), 300 ),
		'inLanguage'          => 'vi',
		'isAccessibleForFree' => $is_free,
		'author'              => cvc_seo_organization(),
		'publisher'           => cvc_seo_organization(),
		'url'                 => $doc_url,
	);
	if ( ! empty( $doc['published_at'] ) ) {
		$article['datePublished'] = (string) $doc['published_at'];
	}
	if ( ! empty( $doc['updated_at'] ) ) {
		$article['dateModified'] = (string) $doc['updated_at'];
	}
	$doc_images = array_values( array_filter( array( $doc['images']['og'] ?? null, $doc['images']['cover'] ?? null ) ) );
	if ( $doc_images ) {
		$article['image'] = $doc_images;
	}
	if ( ! empty( $doc_book['pages'] ) ) {
		$article['pagination'] = (string) (int) $doc_book['pages'] . ' trang';
	}
	if ( ! empty( $doc_book['version'] ) ) {
		$article['version'] = (string) $doc_book['version'];
	}
	if ( ! empty( $doc_book['toc'] ) ) {
		$article['hasPart'] = array_map(
			static fn ( $t ) => array(
				'@type' => 'WebPageElement',
				'name'  => (string) ( $t['t'] ?? '' ),
				'url'   => $doc_url . (string) ( $t['href'] ?? '' ),
			),
			array_slice( (array) $doc_book['toc'], 0, 30 )
		);
	}
	if ( ! empty( $doc['legal_basis'] ) ) {
		$article['citation'] = array_map(
			static function ( $l ) {
				$item = array(
					'@type'                 => 'Legislation',
					'name'                  => (string) ( $l['title'] ?? '' ),
					'legislationIdentifier' => (string) ( $l['number'] ?? '' ),
				);
				if ( ! empty( $l['slug'] ) ) {
					$item['url'] = cvc_legal_document_url( (string) $l['slug'] );
				}
				if ( ! empty( $l['source_url'] ) ) {
					$item['sameAs'] = (string) $l['source_url'];
				}

				return $item;
			},
			(array) $doc['legal_basis']
		);
	}
	cvc_seo_add_json_ld( $article );
	cvc_seo_add_breadcrumb_jsonld(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Kho Tài Liệu', 'url' => cvc_documents_url() ),
			array( 'label' => $doc_title, 'url' => $doc_url ),
		)
	);
}
$price_txt = $is_free ? 'Miễn phí' : number_format( $price, 0, ',', '.' ) . 'đ';
if ( ! empty( $doc['book'] ) ) {
	cvc_book3d_enqueue();
}

if ( ! empty( $doc['content_html'] ) ) {
	wp_enqueue_style( 'cvc-library', get_theme_file_uri( '/assets/css/library.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/library.css' ) ) );
}
get_header();
?>

<main id="main" class="dz-main">

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

		<?php if ( ! empty( $doc['update_notice'] ) ) : ?>
			<div class="dz-panel text-sm" role="status" style="border-left:4px solid #c9a052;background:#fbf5e8">
				<b>Đang cập nhật theo văn bản mới.</b> Sau ngày biên soạn, một số văn bản căn cứ của tài liệu đã thay đổi:
				<ul style="margin:6px 0 4px 20px;list-style:disc"><?php foreach ( (array) $doc['update_notice'] as $u ) : ?><li><?php echo esc_html( (string) $u ); ?></li><?php endforeach; ?></ul>
				Khi dùng, hãy đối chiếu nội dung liên quan với văn bản mới; Công Viên Chức đang biên soạn phiên bản cập nhật.
			</div>
		<?php endif; ?>
		<?php if ( $is_preview ) : ?>
			<p class="dz-panel text-sm" role="status"><b>Bản xem trước cho biên tập viên</b> - trạng thái: <?php echo esc_html( (string) ( $doc['status'] ?? '' ) ); ?>. Người đọc chưa thấy trang này nếu tài liệu chưa xuất bản.</p>
		<?php endif; ?>

		<div class="dz-panel space-y-6">

			<div class="doc-hero<?php echo empty( $doc['book'] ) ? ' no-book' : ''; ?>">
			<?php if ( ! empty( $doc['book'] ) ) : ?>
				<div class="doc-hero-book"><?php cvc_book3d_render( $doc, $price_txt ); ?></div>
			<?php endif; ?>
			<div class="doc-hero-info space-y-5">
			<div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-slate-300 pb-5">
				<div class="flex items-start gap-4">
					<?php if ( empty( $doc['book'] ) ) : ?>
					<div class="w-14 h-14 rounded-2xl bg-acc-soft text-acc flex items-center justify-center text-2xl border border-acc-line shrink-0">
						<i class="fa-solid fa-file-pdf"></i>
					</div>
					<?php endif; ?>
					<div class="space-y-1.5">
						<span class="text-[10px] font-bold text-acc uppercase tracking-wider"><?php echo esc_html( (string) ( $doc['book']['series']['label'] ?? ( $doc['category'] ?? 'Tài liệu' ) ) ); ?></span>
						<h1 class="text-lg sm:text-xl font-black text-ink leading-snug"><?php echo esc_html( $doc['title'] ?? '' ); ?></h1>
						<p class="text-[11px] text-ink-muted"><?php echo esc_html( number_format( (int) ( $doc['download_count'] ?? 0 ) ) ); ?> lượt tải &middot; <?php echo esc_html( size_format( (int) ( $doc['file_size'] ?? 0 ) ) ); ?></p>
					</div>
				</div>
				<div class="shrink-0">
					<?php if ( $is_free ) : ?>
						<span class="px-3 py-1.5 bg-acc text-white font-black text-xs rounded-xl uppercase">Miễn phí</span>
					<?php else : ?>
						<span class="px-3 py-1.5 bg-acc text-white font-black text-sm rounded-xl"><?php echo esc_html( number_format( $price, 0, ',', '.' ) ); ?>đ</span>
					<?php endif; ?>
				</div>
			</div>

			<p class="text-sm text-ink-2 leading-relaxed"><?php echo esc_html( $doc['description'] ?? '' ); ?></p>

			<div class="pt-2">
				<?php if ( $can_download ) : ?>
					<?php $dls = (array) ( $doc['downloads'] ?? array() ); ?>
					<a href="<?php echo esc_url( cvc_document_download_url( $slug ) ); ?>" class="inline-flex items-center gap-2 px-6 py-3 text-white font-black text-sm rounded-xl transition-transform hover:scale-[1.02] bg-acc hover:bg-acc-deep">
						<i class="fa-solid fa-download"></i> Tải Về Ngay<?php echo count( $dls ) > 1 ? ' (' . esc_html( (string) $dls[0]['label'] ) . ')' : ''; ?>
					</a>
					<?php foreach ( array_slice( $dls, 1 ) as $dl ) : // 01/11/2026: nhiều định dạng (vd. mẫu biểu kèm Word để điền) ?>
						<a href="<?php echo esc_url( add_query_arg( 'dinh-dang', (string) $dl['format'], cvc_document_download_url( $slug ) ) ); ?>" class="inline-flex items-center gap-2 px-5 py-3 font-black text-sm rounded-xl border border-slate-300 bg-white hover:border-acc-2 text-ink-2 ml-2">
							<i class="fa-solid fa-file-word"></i> Tải <?php echo esc_html( (string) $dl['label'] ); ?>
						</a>
					<?php endforeach; ?>
				<?php elseif ( ! $is_logged_in ) : ?>
					<a href="<?php echo esc_url( cvc_login_url( cvc_document_url( $slug ) ) ); ?>" class="inline-flex items-center gap-2 px-6 py-3 text-white font-black text-sm rounded-xl transition-transform hover:scale-[1.02] bg-acc hover:bg-acc-deep">
						<i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập Để Mua Tài Liệu
					</a>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'cvc_document_buy' ); ?>
						<input type="hidden" name="action" value="cvc_document_buy">
						<input type="hidden" name="document_id" value="<?php echo esc_attr( (string) ( $doc['id'] ?? 0 ) ); ?>">
						<input type="hidden" name="document_slug" value="<?php echo esc_attr( $slug ); ?>">
						<button type="submit" class="inline-flex items-center gap-2 px-6 py-3 text-white font-black text-sm rounded-xl transition-transform hover:scale-[1.02] bg-acc hover:bg-acc-deep">
							<i class="fa-solid fa-cart-shopping"></i> Mua Ngay — Thanh Toán VNPay
						</button>
					</form>
				<?php endif; ?>
			</div>

			</div>
			</div>

			<?php if ( ! empty( $doc['recruitment'] ) || ! empty( $doc['exam_subject'] ) ) : ?>
				<div class="pt-4 border-t border-slate-300 flex flex-wrap gap-3 text-[11px]">
					<?php if ( ! empty( $doc['recruitment']['slug'] ) ) : ?>
						<a href="<?php echo esc_url( cvc_recruitment_url( $doc['recruitment']['slug'] ) ); ?>" class="px-3 py-1.5 bg-white border border-slate-300 hover:border-acc-2 rounded-lg text-ink-2">
							<i class="fa-solid fa-briefcase mr-1"></i> Liên quan: <?php echo esc_html( $doc['recruitment']['title'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>

		<?php if ( ! empty( $doc['content_html'] ) ) : // 31/10/2026: tài liệu tự sinh - bản đọc trực tuyến. ?>
			<article class="dz-panel cvc-gen-read<?php echo ! empty( $doc['copy_protect'] ) ? ' cvc-nocopy' : ''; ?>" id="doc-read" aria-label="Đọc trực tuyến"<?php echo ! empty( $doc['copy_protect'] ) ? ' data-nocopy="1"' : ''; ?>>
				<h2 class="cvc-gen-h"><?php echo ! empty( $doc['reading_locked'] ) ? 'Đọc thử' : 'Đọc trực tuyến'; ?></h2>
				<?php if ( ! empty( $doc['reference_label'] ) ) : ?><p class="cvc-ref-label"><b><?php echo esc_html( (string) $doc['reference_label'] ); ?></b> – không dùng thay cho quy định hiện hành.</p><?php endif; ?>
				<?php
				$pv = ! empty( $doc['reading_pages']['pages'] ) ? (array) $doc['reading_pages'] : null;
				if ( $pv ) : // 01/11/2026: đọc thử bằng ảnh trang gốc (có dấu chìm) - giống hệt bản in; bản chữ ở khối thu gọn bên dưới.
					$pv_total = (int) $pv['total'];
					$pv_limit = (int) $pv['limit'];
					$pv_buy   = $is_logged_in ? '#main' : cvc_login_url( cvc_document_url( $slug ) );
					?>
					<p class="cvc-pv-sub"><?php echo esc_html( sprintf( 'Xem %d/%d trang đầu, hiển thị đúng như bản gốc. Chạm vào trang để phóng to.', $pv_limit, $pv_total ) ); ?></p>
					<?php if ( count( (array) $pv['toc'] ) >= 2 ) : ?>
						<nav class="cvc-pv-toc" aria-label="Mục lục tài liệu">
							<b>Mục lục</b>
							<ol>
								<?php foreach ( (array) $pv['toc'] as $t ) : ?>
									<li>
										<?php if ( ! empty( $t['open'] ) ) : ?>
											<a href="#trang-<?php echo (int) $t['page']; ?>"><span class="t"><?php echo esc_html( (string) $t['t'] ); ?></span><span class="n"><?php echo (int) $t['page']; ?></span></a>
										<?php elseif ( ! empty( $t['page'] ) ) : ?>
											<span class="is-locked" title="Có trong bản đầy đủ"><span class="t"><?php echo esc_html( (string) $t['t'] ); ?></span><span class="n"><i class="fa-solid fa-lock" aria-hidden="true"></i> <?php echo (int) $t['page']; ?></span></span>
										<?php else : ?>
											<span><span class="t"><?php echo esc_html( (string) $t['t'] ); ?></span></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ol>
						</nav>
					<?php endif; ?>
					<div class="cvc-pv-pages">
						<?php foreach ( (array) $pv['pages'] as $i => $pg ) : ?>
							<figure class="cvc-pv-page" id="trang-<?php echo (int) $pg['n']; ?>">
								<button type="button" class="cvc-pv-zoom" data-src="<?php echo esc_url( (string) $pg['src'] ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Phóng to trang %d', (int) $pg['n'] ) ); ?>">
									<img src="<?php echo esc_url( (string) $pg['src'] ); ?>" width="<?php echo (int) $pg['w']; ?>" height="<?php echo (int) $pg['h']; ?>" alt="<?php echo esc_attr( sprintf( 'Trang %d – %s', (int) $pg['n'], (string) ( $doc['title'] ?? '' ) ) ); ?>"<?php echo $i > 1 ? ' loading="lazy"' : ''; ?> decoding="async" draggable="false">
								</button>
								<figcaption><?php echo esc_html( sprintf( 'Trang %d / %d', (int) $pg['n'], $pv_total ) ); ?></figcaption>
							</figure>
						<?php endforeach; ?>
						<?php if ( $pv_total > $pv_limit ) : ?>
							<div class="cvc-pv-next">
								<?php if ( ! empty( $pv['teaser'] ) ) : ?><img src="<?php echo esc_url( (string) $pv['teaser'] ); ?>" alt="" aria-hidden="true" loading="lazy" draggable="false"><?php endif; ?>
								<div class="cvc-pv-cta" role="note">
									<p class="k">Còn <?php echo (int) ( $pv_total - $pv_limit ); ?> trang nữa</p>
									<p>Mua tài liệu (<?php echo esc_html( $price_txt ); ?>) để đọc trọn bản trực tuyến và tải tệp PDF đầy đủ.</p>
									<a href="<?php echo esc_url( $pv_buy ); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-black text-sm rounded-xl bg-acc hover:bg-acc-deep"><i class="fa-solid fa-cart-shopping"></i> <?php echo esc_html( $is_logged_in ? 'Mua để đọc tiếp' : 'Đăng nhập để mua' ); ?></a>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<details class="cvc-pv-text">
						<summary>Nội dung dạng chữ của phần đọc thử</summary>
						<?php echo wp_kses_post( str_replace( 'id="trang-', 'data-trang="', (string) $doc['content_html'] ) ); ?>
					</details>
					<dialog class="cvc-pv-dlg" id="cvc-pv-dlg" aria-label="Xem trang phóng to">
						<button type="button" class="cvc-pv-close" aria-label="Đóng">&times;</button>
						<div class="cvc-pv-dlg-body"><img alt="" draggable="false"></div>
					</dialog>
					<script>
					(function () {
						var root = document.getElementById('doc-read'), dlg = document.getElementById('cvc-pv-dlg');
						if (!root || !dlg || typeof dlg.showModal !== 'function') { return; }
						var img = dlg.querySelector('img');
						root.querySelectorAll('.cvc-pv-zoom').forEach(function (b) {
							b.addEventListener('click', function () { img.src = b.getAttribute('data-src'); img.alt = b.getAttribute('aria-label') || ''; dlg.showModal(); });
						});
						dlg.querySelector('.cvc-pv-close').addEventListener('click', function () { dlg.close(); });
						dlg.addEventListener('click', function (e) { if (e.target === dlg) { dlg.close(); } });
					})();
					</script>
				<?php else : ?>
				<?php echo wp_kses_post( (string) $doc['content_html'] ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $doc['reading_locked'] ) && ! $pv ) : // 01/11/2026: tài liệu trả phí - máy chủ chỉ gửi phần xem trước. ?>
					<div class="cvc-locked" role="note">
						<p><b>Bạn đang đọc thử khoảng <?php echo (int) ( $doc['reading_preview_pct'] ?? 15 ); ?>% đầu tài liệu.</b> Mua tài liệu để đọc trọn bản trực tuyến và tải tệp đầy đủ.</p>
						<a href="#main" class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-black text-sm rounded-xl bg-acc hover:bg-acc-deep"><i class="fa-solid fa-cart-shopping"></i> <?php echo esc_html( $is_logged_in ? 'Mua tài liệu' : 'Đăng nhập để mua' ); ?></a>
					</div>
				<?php endif; ?>
			</article>
			<?php if ( ! empty( $doc['copy_protect'] ) ) : // 01/11/2026 hồ sơ phát hành: chống sao chép bản đọc (rào cản, không chặn được chụp màn hình). ?>
				<script>
				(function () {
					var el = document.getElementById('doc-read');
					if (!el) { return; }
					var note = function () {
						var t = document.getElementById('cvc-nocopy-toast');
						if (!t) { t = document.createElement('div'); t.id = 'cvc-nocopy-toast'; t.setAttribute('role', 'status'); t.textContent = 'Nội dung tài liệu được bảo vệ bản quyền - vui lòng không sao chép.'; document.body.appendChild(t); }
						t.classList.add('is-on'); clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('is-on'); }, 2200);
					};
					['copy', 'cut', 'contextmenu', 'dragstart'].forEach(function (ev) { el.addEventListener(ev, function (e) { e.preventDefault(); note(); }); });
					document.addEventListener('keydown', function (e) {
						var k = (e.key || '').toLowerCase();
						if ((e.ctrlKey || e.metaKey) && (k === 'c' || k === 'x' || k === 'a' || k === 's' || k === 'p') && el.contains(document.activeElement || document.body) || ((e.ctrlKey || e.metaKey) && k === 'c' && window.getSelection && el.contains(window.getSelection().anchorNode))) { e.preventDefault(); note(); }
					});
				})();
				</script>
			<?php endif; ?>
		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
