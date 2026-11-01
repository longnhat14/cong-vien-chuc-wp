<?php
/**
 * SÁCH & CẨM NANG NỔI BẬT (01/11/2026, claude/ke-hoach-sach-cam-nang-noi-bat.md - anh Nhật chốt: kệ sách đầu mỗi mục
 * thư viện + cột phải ở trang đọc dài; bìa 3D; cột "Sách & cẩm nang" trong menu Tài liệu; trang Tủ sách; bỏ sách khỏi
 * khối tải về cuối trang).
 *
 * Dữ liệu: GET /api/documents/books (kệ / tủ sách), GET /api/documents/popular?books=1 (cột phải theo ngữ cảnh).
 * Bìa 3D = ảnh "card" do CoverRenderer dựng sẵn (600×800, nền xám nhạt).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array{scope:string, total:int, items:array<int, array<string, mixed>>} */
function cvc_books( string $view, array $q = array(), int $limit = 8 ): array {
	$q   = array_filter( $q + array( 'view' => $view, 'limit' => $limit ), static fn ( $v ) => null !== $v && '' !== $v );
	$key = 'cvc_books_' . md5( wp_json_encode( $q ) );
	$hit = get_transient( $key );
	if ( is_array( $hit ) ) {
		return $hit;
	}
	$res = ( new CVC_Api_Client( null, 6 ) )->get( '/api/documents/books', $q );
	$d   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$out = array( 'scope' => (string) ( $d['scope'] ?? 'general' ), 'total' => (int) ( $d['total'] ?? 0 ), 'items' => array_values( (array) ( $d['items'] ?? array() ) ) );
	set_transient( $key, $out, $res['ok'] ? 10 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS );
	return $out;
}

function cvc_book_tu_sach_url(): string {
	return home_url( '/tai-lieu/tu-sach/' );
}

/** Dòng số liệu: năm · số trang · Mới / N lượt đọc · N lượt tải. */
function cvc_book_meta( array $it ): string {
	$reads = (int) ( $it['reads'] ?? 0 );
	$parts = array_filter(
		array(
			! empty( $it['year'] ) ? (string) (int) $it['year'] : '',
			! empty( $it['pages'] ) ? (int) $it['pages'] . ' trang' : '',
			$reads >= 5 ? number_format( $reads, 0, ',', '.' ) . ' lượt đọc' : '',
			(int) ( $it['downloads'] ?? 0 ) > 0 ? number_format( (int) $it['downloads'], 0, ',', '.' ) . ' lượt tải' : '',
		)
	);
	return implode( ' · ', $parts );
}

function cvc_book_tags( array $it ): string {
	$t = array();
	if ( ! empty( $it['hot'] ) ) {
		$t[] = '<span class="bk-tag is-hot">Được đọc nhiều</span>';
	}
	if ( (int) ( $it['reads'] ?? 0 ) < 5 || ! empty( $it['is_new'] ) ) {
		$t[] = '<span class="bk-tag is-new">Mới</span>';
	}
	$t[] = ! empty( $it['is_free'] ) ? '<span class="bk-tag is-free">Miễn phí</span>' : ( ! empty( $it['preview'] ) ? '<span class="bk-tag">Có đọc thử</span>' : '' );
	return implode( '', array_filter( $t ) );
}

function cvc_book_cta( array $it ): string {
	return ! empty( $it['is_free'] ) ? 'Đọc ngay' : ( ! empty( $it['preview'] ) ? 'Đọc thử' : 'Xem sách' );
}

function cvc_book_href( array $it ): string {
	return cvc_document_url( (string) $it['slug'] ) . ( empty( $it['is_free'] ) && ! empty( $it['preview'] ) ? '#doc-read' : '' );
}

function cvc_book_css(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	?>
	<style>
	.bk-sec{--bk-ink:#17334f;--bk-gold:#b08a3e;--bk-line:#e6e0d2;--bk-muted:#5b6470;--bk-desk:#e9edf2;margin:4px 0 28px;padding:20px 20px 22px;border:1px solid var(--bk-line);border-radius:16px;background:linear-gradient(180deg,#fffdf8,#fff)}
	.bk-head{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:0 0 16px;flex-wrap:wrap}
	.bk-kicker{display:block;font-size:12px;font-weight:800;letter-spacing:.12em;color:var(--bk-gold)}
	.bk-head h2{margin:4px 0 0;font-size:24px;line-height:1.25;color:var(--bk-ink)}
	.bk-head p{margin:4px 0 0;font-size:15px;color:var(--bk-muted)}
	.bk-more{font-size:15px;font-weight:700;color:var(--bk-ink);white-space:nowrap;text-decoration:underline;text-underline-offset:3px}
	.bk-tags{display:flex;flex-wrap:wrap;gap:4px}
	.bk-tag{font-size:11px;font-weight:800;letter-spacing:.02em;padding:2px 8px;border-radius:999px;background:#eef3f8;color:#2b4a68;line-height:1.5}
	.bk-tag.is-hot{background:#fbf0d9;color:#7a5a17}
	.bk-tag.is-new{background:#fdf3e2;color:#9a5b0b}
	.bk-tag.is-free{background:#e5f3ec;color:#1f6b4d}
	.bk-art{display:block;aspect-ratio:3/4;background:var(--bk-desk);border-radius:12px;overflow:hidden}
	.bk-art img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .25s}
	.bk-meta{font-size:13px;color:var(--bk-muted);font-variant-numeric:tabular-nums}
	/* kệ ngang (>= 3 cuốn) */
	.bk-shelf{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(190px,1fr);gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;padding:2px 2px 10px;scrollbar-width:thin}
	.bk-tile{scroll-snap-align:start;display:flex;flex-direction:column;gap:8px;color:var(--bk-ink);text-decoration:none;min-width:0}
	.bk-tile:hover .bk-art img{transform:translateY(-4px) scale(1.02)}
	.bk-tile:focus-visible{outline:2px solid #c9a052;outline-offset:3px;border-radius:12px}
	.bk-tile b{font-size:15.5px;line-height:1.35}
	.bk-go{margin-top:auto;font-size:14px;font-weight:800;color:var(--bk-ink)}
	.bk-go .free{color:#1f7a5c}
	/* lưới (trang Tủ sách) */
	.bk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:26px 20px}
	.bk-tdesc{font-size:14px;line-height:1.5;color:#3a4553;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
	/* tiêu điểm (1-2 cuốn) */
	.bk-feats{display:grid;gap:18px}
	.bk-feats.is-2{grid-template-columns:1fr 1fr}
	.bk-feat{display:grid;grid-template-columns:minmax(150px,220px) minmax(0,1fr);gap:20px;align-items:start}
	.bk-feats.is-2 .bk-feat{grid-template-columns:150px minmax(0,1fr);gap:14px}
	.bk-feat h3{margin:6px 0 4px;font-size:20px;line-height:1.3}
	.bk-feats.is-2 .bk-feat h3{font-size:17px}
	.bk-feat h3 a{color:var(--bk-ink);text-decoration:none}
	.bk-feat h3 a:hover{text-decoration:underline;text-underline-offset:3px}
	.bk-desc{margin:8px 0 0;font-size:15px;line-height:1.55;color:#2b3542}
	.bk-toc{margin:10px 0 0;padding:10px 12px 10px 30px;border-left:3px solid #e3bf7a;background:#fbf7ee;border-radius:0 10px 10px 0;font-size:14px;color:#33414f}
	.bk-toc li{margin:2px 0}
	.bk-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
	.bk-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:10px;border:1px solid #cfd6df;background:#fff;color:var(--bk-ink);font-weight:800;font-size:14px;text-decoration:none}
	.bk-btn.is-primary{background:var(--bk-ink);border-color:var(--bk-ink);color:#fff}
	.bk-btn:focus-visible{outline:2px solid #c9a052;outline-offset:2px}
	@media (max-width:760px){
		.bk-sec{padding:16px 14px 18px;border-radius:14px}
		.bk-head h2{font-size:20px}
		.bk-shelf{grid-auto-columns:46%}
		.bk-grid{grid-template-columns:1fr 1fr;gap:20px 14px}
		.bk-tdesc{display:none}
		.bk-tile b{font-size:14.5px}
		.bk-feats.is-2{grid-template-columns:1fr}
		.bk-feat,.bk-feats.is-2 .bk-feat{grid-template-columns:118px minmax(0,1fr);gap:12px}
		.bk-feat h3{font-size:17px}
		.bk-desc{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;font-size:14px}
		.bk-toc{display:none}
	}
	/* cột phải "Sách nổi bật" */
	.bk-side{--bk-ink:#17334f;--bk-muted:#5b6470;--bk-desk:#e9edf2;position:sticky;top:96px;padding:14px;border:1px solid #e6e0d2;border-radius:14px;background:#fffdf8;text-align:left}
	.bk-side h2,.bk-side h3{margin:0 0 10px;font-size:15px;font-weight:800;letter-spacing:.04em;color:#7a5a17;text-transform:uppercase}
	.bk-side .bk-main{display:block;color:var(--bk-ink);text-decoration:none}
	.bk-side .bk-main b{display:block;margin-top:8px;font-size:15px;line-height:1.35}
	.bk-side .bk-art{max-width:220px;margin:0 auto}
	.bk-side ul{list-style:none;margin:12px 0 0;padding:10px 0 0;border-top:1px dashed #e6e0d2;display:grid;gap:10px}
	.bk-side li a{display:grid;grid-template-columns:38px minmax(0,1fr);gap:10px;align-items:start;color:var(--bk-ink);text-decoration:none;font-size:13.5px;font-weight:700;line-height:1.35}
	.bk-side li img{width:38px;height:54px;object-fit:cover;border-radius:2px 3px 3px 2px;box-shadow:0 2px 5px rgba(15,42,74,.25)}
	.bk-side .bk-all{display:block;margin-top:12px;font-size:14px;font-weight:800;color:var(--bk-ink);text-decoration:underline;text-underline-offset:3px}
	.bk-side.is-static,.bk-side.is-compact{position:static}
	.bk-side.is-compact ul{margin:0;padding:0;border-top:0}
	@media (max-width:1023px){.bk-side{position:static}}
	</style>
	<?php
}

/**
 * Kệ sách đầu mục thư viện. ≤ 2 cuốn: dạng tiêu điểm (bìa lớn, mô tả, mục lục); ≥ 3 cuốn: kệ ngang.
 *
 * @param array{items:array, total:int} $data
 * @param array{title?:string, sub?:string, more?:bool, kicker?:string} $o
 */
function cvc_book_shelf( array $data, array $o = array() ): void {
	$items = (array) ( $data['items'] ?? array() );
	if ( ! $items ) {
		return;
	}
	cvc_book_css();
	$hid = 'bk-h-' . wp_rand( 100, 999 );
	?>
	<section class="bk-sec" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
		<div class="bk-head">
			<div>
				<span class="bk-kicker"><?php echo esc_html( (string) ( $o['kicker'] ?? 'TỦ SÁCH CÔNG VIÊN CHỨC' ) ); ?></span>
				<h2 id="<?php echo esc_attr( $hid ); ?>"><?php echo esc_html( (string) ( $o['title'] ?? 'Sách & cẩm nang ôn thi' ) ); ?></h2>
				<?php if ( ! empty( $o['sub'] ) ) : ?><p><?php echo esc_html( (string) $o['sub'] ); ?></p><?php endif; ?>
			</div>
			<?php if ( ! isset( $o['more'] ) || $o['more'] ) : ?><a class="bk-more" href="<?php echo esc_url( cvc_book_tu_sach_url() ); ?>">Xem cả tủ sách<?php echo (int) ( $data['total'] ?? 0 ) > count( $items ) ? ' (' . (int) $data['total'] . ' cuốn)' : ''; ?> →</a><?php endif; ?>
		</div>
		<?php if ( 'grid' === ( $o['layout'] ?? '' ) ) : ?>
			<div class="bk-grid">
				<?php foreach ( $items as $it ) : ?>
					<a class="bk-tile" href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) ); ?>">
						<span class="bk-art"><img src="<?php echo esc_url( (string) ( $it['card'] ?? $it['thumb'] ?? '' ) ); ?>" alt="" width="600" height="800" loading="lazy" decoding="async"></span>
						<span class="bk-tags"><?php echo cvc_book_tags( $it ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<b><?php echo esc_html( (string) $it['title'] ); ?></b>
						<span class="bk-meta"><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $it['category_label'] ?? '' ), cvc_book_meta( $it ) ) ) ) ); ?></span>
						<?php if ( ! empty( $it['description'] ) ) : ?><span class="bk-tdesc"><?php echo esc_html( (string) $it['description'] ); ?></span><?php endif; ?>
						<span class="bk-go"><?php echo ! empty( $it['is_free'] ) ? '<span class="free">Miễn phí</span>' : esc_html( number_format( (float) ( $it['price'] ?? 0 ), 0, ',', '.' ) . 'đ' ); ?> · <?php echo esc_html( cvc_book_cta( $it ) ); ?> →</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php elseif ( count( $items ) <= 2 ) : ?>
			<div class="bk-feats<?php echo 2 === count( $items ) ? ' is-2' : ''; ?>">
				<?php foreach ( $items as $it ) : ?>
					<article class="bk-feat">
						<a class="bk-art" href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) ); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo esc_url( (string) ( $it['card'] ?? $it['thumb'] ?? '' ) ); ?>" alt="" width="600" height="800" loading="lazy" decoding="async"></a>
						<div>
							<div class="bk-tags"><?php echo cvc_book_tags( $it ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<h3><a href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) ); ?>"><?php echo esc_html( (string) $it['title'] ); ?></a></h3>
							<p class="bk-meta"><?php echo esc_html( cvc_book_meta( $it ) ); ?></p>
							<?php if ( ! empty( $it['description'] ) ) : ?><p class="bk-desc"><?php echo esc_html( (string) $it['description'] ); ?></p><?php endif; ?>
							<?php if ( ! empty( $it['toc'] ) ) : ?>
								<ol class="bk-toc" aria-label="Một số phần trong sách"><?php foreach ( array_slice( (array) $it['toc'], 0, 2 === count( $items ) ? 3 : 5 ) as $t ) : ?><li><?php echo esc_html( preg_replace( '/^\d+\.\s*/u', '', (string) $t ) ); ?></li><?php endforeach; ?></ol>
							<?php endif; ?>
							<div class="bk-actions">
								<a class="bk-btn is-primary" href="<?php echo esc_url( cvc_book_href( $it ) ); ?>"><?php echo esc_html( cvc_book_cta( $it ) ); ?> →</a>
								<?php if ( empty( $it['is_free'] ) ) : ?><span class="bk-btn" aria-label="Giá"><?php echo esc_html( number_format( (float) ( $it['price'] ?? 0 ), 0, ',', '.' ) . 'đ' ); ?></span><?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="bk-shelf">
				<?php foreach ( $items as $it ) : ?>
					<a class="bk-tile" href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) ); ?>">
						<span class="bk-art"><img src="<?php echo esc_url( (string) ( $it['card'] ?? $it['thumb'] ?? '' ) ); ?>" alt="" width="600" height="800" loading="lazy" decoding="async"></span>
						<span class="bk-tags"><?php echo cvc_book_tags( $it ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<b><?php echo esc_html( (string) $it['title'] ); ?></b>
						<span class="bk-meta"><?php echo esc_html( cvc_book_meta( $it ) ); ?></span>
						<span class="bk-go"><?php echo ! empty( $it['is_free'] ) ? '<span class="free">Miễn phí</span>' : esc_html( number_format( (float) ( $it['price'] ?? 0 ), 0, ',', '.' ) . 'đ' ); ?> · <?php echo esc_html( cvc_book_cta( $it ) ); ?> →</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Sách cho cột bên theo ngữ cảnh trang (GET /api/documents/popular?books=1); chưa có sách đúng ngữ cảnh thì lấy
 * sách được đọc nhiều chung.
 */
function cvc_book_side_items( string $ctx, array $params = array(), int $limit = 3 ): array {
	$items = function_exists( 'cvc_doc_popular' ) ? (array) cvc_doc_popular( $ctx, $params + array( 'books' => 1 ), $limit )['items'] : array();
	if ( ! $items ) {
		$items = cvc_books( 'all', array(), $limit )['items'];
	}
	return array_values( $items );
}

/**
 * Cột bên "Sách nổi bật": 1 cuốn bìa 3D + 2 cuốn nhỏ, dính khi cuộn.
 * $o['compact'] = chỉ danh sách nhỏ (cột trái trang văn bản); $o['static'] = không dính.
 */
function cvc_book_side( array $items, string $title = 'Sách nổi bật', string $tag = 'h2', array $o = array() ): void {
	$items = array_values( $items );
	if ( ! $items ) {
		return;
	}
	cvc_book_css();
	$tag = 'h3' === $tag ? 'h3' : 'h2';
	$top = $items[0];
	if ( ! empty( $o['compact'] ) ) {
		?>
		<div class="bk-side is-compact" role="complementary" aria-label="<?php echo esc_attr( $title ); ?>">
			<<?php echo $tag; // phpcs:ignore ?>><?php echo esc_html( $title ); ?></<?php echo $tag; // phpcs:ignore ?>>
			<ul>
				<?php foreach ( array_slice( $items, 0, 3 ) as $it ) : ?>
					<li><a href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) ); ?>"><img src="<?php echo esc_url( (string) ( $it['thumb'] ?? '' ) ); ?>" alt="" width="38" height="54" loading="lazy" decoding="async"><span><?php echo esc_html( (string) $it['title'] ); ?><br><small class="bk-meta" style="font-weight:400"><?php echo esc_html( implode( ' · ', array_filter( array( cvc_book_meta( $it ), ! empty( $it['is_free'] ) ? 'Miễn phí' : '' ) ) ) ); ?></small></span></a></li>
				<?php endforeach; ?>
			</ul>
			<a class="bk-all" href="<?php echo esc_url( cvc_book_tu_sach_url() ); ?>">Tủ sách Công Viên Chức →</a>
		</div>
		<?php
		return;
	}
	?>
	<div class="bk-side<?php echo ! empty( $o['static'] ) ? ' is-static' : ''; ?>" role="complementary" aria-label="<?php echo esc_attr( $title ); ?>">
		<<?php echo $tag; // phpcs:ignore ?>><?php echo esc_html( $title ); ?></<?php echo $tag; // phpcs:ignore ?>>
		<a class="bk-main" href="<?php echo esc_url( cvc_document_url( (string) $top['slug'] ) ); ?>">
			<span class="bk-art"><img src="<?php echo esc_url( (string) ( $top['card'] ?? $top['thumb'] ?? '' ) ); ?>" alt="" width="600" height="800" loading="lazy" decoding="async"></span>
			<span class="bk-tags" style="margin-top:8px"><?php echo cvc_book_tags( $top ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<b><?php echo esc_html( (string) $top['title'] ); ?></b>
			<span class="bk-meta"><?php echo esc_html( cvc_book_meta( $top ) ); ?></span>
		</a>
		<a class="bk-btn is-primary" style="margin-top:10px;width:100%;justify-content:center" href="<?php echo esc_url( cvc_book_href( $top ) ); ?>"><?php echo esc_html( cvc_book_cta( $top ) . ( empty( $top['is_free'] ) ? ' · ' . number_format( (float) ( $top['price'] ?? 0 ), 0, ',', '.' ) . 'đ' : '' ) ); ?> →</a>
		<?php if ( count( $items ) > 1 ) : ?>
			<ul>
				<?php foreach ( array_slice( $items, 1, 2 ) as $it ) : ?>
					<li><a href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) ); ?>"><img src="<?php echo esc_url( (string) ( $it['thumb'] ?? '' ) ); ?>" alt="" width="38" height="54" loading="lazy" decoding="async"><span><?php echo esc_html( (string) $it['title'] ); ?><br><small class="bk-meta" style="font-weight:400"><?php echo esc_html( cvc_book_meta( $it ) ); ?></small></span></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<a class="bk-all" href="<?php echo esc_url( cvc_book_tu_sach_url() ); ?>">Tủ sách Công Viên Chức →</a>
	</div>
	<?php
}

/** Cột "Sách & cẩm nang" trong menu Tài liệu (mega menu, inc/chrome.php). Trả HTML ('' khi chưa có sách). */
function cvc_book_menu(): string {
	$b = cvc_books( 'all', array(), 3 );
	if ( ! $b['items'] ) {
		return '';
	}
	$on = 0 === strpos( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/tai-lieu/tu-sach' ); // phpcs:ignore
	ob_start();
	?>
	<div class="ch-mg-col is-books">
		<p class="ch-mega-h">Sách &amp; cẩm nang</p>
		<?php foreach ( $b['items'] as $it ) : ?>
			<a class="ch-mg-book" href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) ); ?>">
				<img src="<?php echo esc_url( (string) ( $it['thumb'] ?? '' ) ); ?>" alt="" width="36" height="51" loading="lazy" decoding="async">
				<span><b><?php echo esc_html( (string) $it['title'] ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( ! empty( $it['pages'] ) ? (int) $it['pages'] . ' trang' : '', ! empty( $it['is_free'] ) ? 'Miễn phí' : number_format( (float) ( $it['price'] ?? 0 ), 0, ',', '.' ) . 'đ' ) ) ) ); ?></small></span>
			</a>
		<?php endforeach; ?>
		<a class="ch-mg-booksall<?php echo $on ? ' is-active' : ''; ?>" href="<?php echo esc_url( cvc_book_tu_sach_url() ); ?>">Tủ sách Công Viên Chức<?php echo $b['total'] > 0 ? ' (' . (int) $b['total'] . ' cuốn)' : ''; ?> →</a>
	</div>
	<?php
	return (string) ob_get_clean();
}
