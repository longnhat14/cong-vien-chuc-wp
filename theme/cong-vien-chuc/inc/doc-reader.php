<?php
/**
 * TRÌNH ĐỌC BẢN GỐC (01/11/2026, claude/ke-hoach-doc-thu-dep.md đợt 2 - anh Nhật chốt):
 *  - mặc định "Bản gốc" (ảnh trang PDF phát hành, có dấu chìm; tài liệu đã mua in dấu chìm theo người mua),
 *    nút chuyển "Bản chữ" (luôn có sẵn trong HTML cho Google / tìm kiếm / trình đọc màn hình);
 *  - thanh công cụ: số trang + thanh kéo, mục lục, ảnh thu nhỏ, tìm trong sách, 1/2 trang (sách mở), giảm sáng,
 *    toàn màn hình; "Đọc tiếp từ trang N" (lưu theo tài khoản qua admin-ajax -> API).
 * Dữ liệu: $doc['reader'] từ API (DocAccess::viewer), $doc['content_html'] (bản chữ - đã cắt nếu đang đọc thử).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_cvc_doc_progress', 'cvc_doc_progress_ajax' );
add_action( 'wp_ajax_nopriv_cvc_doc_progress', 'cvc_doc_progress_ajax' );

/** Lưu trang đang đọc (người đã đăng nhập). */
function cvc_doc_progress_ajax(): void {
	nocache_headers();
	if ( ! check_ajax_referer( 'cvc_doc_read', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Phiên làm việc đã hết hạn.' ), 403 );
	}
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Chưa đăng nhập.' ), 401 );
	}
	$slug = sanitize_title( wp_unslash( (string) ( $_POST['slug'] ?? '' ) ) );
	$mode = 'text' === ( $_POST['mode'] ?? '' ) ? 'text' : 'pages';
	if ( '' === $slug ) {
		wp_send_json_error( array( 'message' => 'Thiếu tài liệu.' ), 422 );
	}
	$res = ( new CVC_Api_Client( null, 8 ) )->post(
		'/api/documents/' . rawurlencode( $slug ) . '/progress',
		array( 'page' => max( 1, absint( $_POST['page'] ?? 1 ) ), 'total' => absint( $_POST['total'] ?? 0 ), 'mode' => $mode ),
		$token
	);
	$res['ok'] ? wp_send_json_success() : wp_send_json_error( array( 'message' => cvc_api_error_message( $res ) ), $res['status'] ?: 502 );
}

function cvc_doc_reader_enqueue(): void {
	$js = get_theme_file_path( '/assets/js/doc-reader.js' );
	wp_enqueue_script( 'cvc-doc-reader', get_theme_file_uri( '/assets/js/doc-reader.js' ), array(), (string) filemtime( $js ), true );
}

/**
 * @param array<string, mixed> $doc
 */
function cvc_doc_reader_render( array $doc, string $slug, bool $is_logged_in, string $price_txt ): void {
	$rd      = (array) $doc['reader'];
	$preview = 'preview' === ( $rd['mode'] ?? '' );
	$total   = (int) ( $rd['total'] ?? 0 );
	$ready   = (int) ( $rd['ready'] ?? 0 );
	$limit   = (int) ( $rd['limit'] ?? 0 );
	$pages   = (array) ( $rd['pages'] ?? array() );
	$thumbs  = (array) ( $rd['thumbs'] ?? array() );
	$toc     = (array) ( $rd['toc'] ?? array() );
	$resume  = (int) ( $doc['reading_progress'] ?? 0 );
	$buy     = $is_logged_in ? '#main' : cvc_login_url( cvc_document_url( $slug ) );
	$protect = ! empty( $doc['copy_protect'] );
	$cfg     = array(
		'slug'     => $slug,
		'total'    => $total,
		'ready'    => $ready,
		'preview'  => $preview,
		'limit'    => $limit,
		'resume'   => $resume,
		'logged'   => $is_logged_in,
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonce'    => $is_logged_in ? wp_create_nonce( 'cvc_doc_read' ) : '',
	);
	?>
	<section class="dz-panel cvc-rd<?php echo $protect ? ' cvc-nocopy' : ''; ?>" id="doc-read" data-mode="pages" aria-label="<?php echo esc_attr( $preview ? 'Đọc thử' : 'Đọc trực tuyến' ); ?>"<?php echo $protect ? ' data-nocopy="1"' : ''; ?>>
		<script type="application/json" class="cvc-rd-cfg"><?php echo wp_json_encode( $cfg, JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
		<div class="cvc-rd-head">
			<h2 class="cvc-gen-h"><?php echo $preview ? 'Đọc thử' : 'Đọc trực tuyến'; ?></h2>
			<p class="cvc-rd-sub">
				<?php
				echo esc_html(
					$preview
						? sprintf( 'Xem %d/%d trang đầu, hiển thị đúng như bản gốc.', min( $limit, $ready ), $total )
						: sprintf( '%d trang · hiển thị đúng như bản gốc. Chạm vào trang để phóng to.', $total )
				);
				?>
			</p>
			<?php if ( ! empty( $doc['reference_label'] ) ) : ?><p class="cvc-ref-label"><b><?php echo esc_html( (string) $doc['reference_label'] ); ?></b> – không dùng thay cho quy định hiện hành.</p><?php endif; ?>
			<div class="cvc-rd-resume" hidden>
				<span>Lần trước bạn đọc đến <b>trang <span class="cvc-rd-resume-n"></span></b>.</span>
				<button type="button" class="cvc-rd-btn is-primary" data-act="resume">Đọc tiếp</button>
				<button type="button" class="cvc-rd-x" data-act="resume-hide" aria-label="Ẩn">&times;</button>
			</div>
		</div>

		<div class="cvc-rd-sticky">
		<div class="cvc-rd-bar" role="toolbar" aria-label="Công cụ đọc">
			<div class="cvc-rd-seg" role="group" aria-label="Kiểu hiển thị">
				<button type="button" data-act="mode" data-v="pages" aria-pressed="true">Bản gốc</button>
				<button type="button" data-act="mode" data-v="text" aria-pressed="false">Bản chữ</button>
			</div>
			<div class="cvc-rd-nav" data-only="pages">
				<button type="button" class="cvc-rd-ic" data-act="prev" aria-label="Trang trước"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
				<label class="cvc-rd-num"><span class="sr-only">Trang</span><input type="number" min="1" max="<?php echo (int) $total; ?>" value="1" inputmode="numeric" id="cvc-rd-page" aria-label="Đi tới trang"><span>/ <?php echo (int) $total; ?></span></label>
				<button type="button" class="cvc-rd-ic" data-act="next" aria-label="Trang sau"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
			</div>
			<div class="cvc-rd-tools">
				<?php if ( count( $toc ) >= 2 ) : ?><button type="button" class="cvc-rd-ic" data-act="panel" data-v="toc" aria-expanded="false" title="Mục lục" aria-label="Mục lục"><i class="fa-solid fa-list-ul" aria-hidden="true"></i><span>Mục lục</span></button><?php endif; ?>
				<button type="button" class="cvc-rd-ic" data-act="panel" data-v="thumbs" data-only="pages" aria-expanded="false" title="Các trang" aria-label="Ảnh thu nhỏ các trang"><i class="fa-solid fa-table-cells" aria-hidden="true"></i><span>Các trang</span></button>
				<button type="button" class="cvc-rd-ic" data-act="panel" data-v="find" aria-expanded="false" title="Tìm trong sách" aria-label="Tìm trong sách"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Tìm</span></button>
				<button type="button" class="cvc-rd-ic cvc-rd-wide" data-act="spread" data-only="pages" aria-pressed="false" title="Sách mở 2 trang" aria-label="Sách mở 2 trang"><i class="fa-solid fa-book-open" aria-hidden="true"></i><span>2 trang</span></button>
				<button type="button" class="cvc-rd-ic" data-act="dim" aria-pressed="false" title="Giảm sáng" aria-label="Giảm sáng"><i class="fa-solid fa-moon" aria-hidden="true"></i><span>Giảm sáng</span></button>
				<button type="button" class="cvc-rd-ic" data-act="full" aria-pressed="false" title="Toàn màn hình" aria-label="Toàn màn hình"><i class="fa-solid fa-expand" aria-hidden="true"></i><span>Toàn màn hình</span></button>
			</div>
			<input type="range" class="cvc-rd-range" data-only="pages" min="1" max="<?php echo (int) max( 1, $ready ); ?>" value="1" aria-label="Kéo để chuyển trang" id="cvc-rd-range">
		</div>

		<?php if ( count( $toc ) >= 2 ) : ?>
			<nav class="cvc-rd-panel cvc-rd-toc" data-panel="toc" aria-label="Mục lục tài liệu" hidden>
				<ol>
					<?php foreach ( $toc as $t ) : ?>
						<li class="lv<?php echo (int) ( $t['level'] ?? 0 ); ?><?php echo empty( $t['open'] ) ? ' is-locked' : ''; ?>">
							<a href="#pg-<?php echo (int) $t['page']; ?>" data-page="<?php echo (int) $t['page']; ?>"<?php echo empty( $t['open'] ) ? ' title="' . esc_attr( $preview ? 'Có trong bản đầy đủ' : 'Trang đang được chuẩn bị' ) . '"' : ''; ?>>
								<span class="t"><?php echo esc_html( (string) $t['t'] ); ?></span>
								<span class="n"><?php echo empty( $t['open'] ) && $preview ? '<i class="fa-solid fa-lock" aria-hidden="true"></i> ' : ''; ?><?php echo (int) $t['page']; ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>
		<div class="cvc-rd-panel cvc-rd-thumbs" data-panel="thumbs" hidden>
			<?php foreach ( $thumbs as $i => $src ) : $n = $i + 1; $open = $n <= $ready; ?>
				<button type="button" data-page="<?php echo (int) $n; ?>" class="<?php echo $open ? '' : 'is-locked'; ?>" aria-label="<?php echo esc_attr( sprintf( 'Trang %d', $n ) ); ?>">
					<img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" data-src="<?php echo esc_url( (string) $src ); ?>" alt="" width="110" height="156" draggable="false">
					<span><?php echo $open ? (int) $n : '<i class="fa-solid fa-lock" aria-hidden="true"></i> ' . (int) $n; ?></span>
				</button>
			<?php endforeach; ?>
		</div>
		<div class="cvc-rd-panel cvc-rd-find" data-panel="find" hidden>
			<label for="cvc-rd-q" class="sr-only">Tìm trong sách</label>
			<input type="search" id="cvc-rd-q" placeholder="<?php echo esc_attr( $preview ? 'Tìm trong phần đọc thử…' : 'Tìm trong sách…' ); ?>" autocomplete="off">
			<p class="cvc-rd-find-meta" aria-live="polite"></p>
			<ol class="cvc-rd-find-list"></ol>
		</div>
		</div>

		<div class="cvc-rd-pages" data-view="pages">
			<?php foreach ( $pages as $i => $pg ) : ?>
				<figure class="cvc-rd-page" id="pg-<?php echo (int) $pg['n']; ?>" data-page="<?php echo (int) $pg['n']; ?>">
					<button type="button" class="cvc-rd-zoom" data-src="<?php echo esc_url( (string) $pg['src'] ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Phóng to trang %d', (int) $pg['n'] ) ); ?>">
						<img src="<?php echo esc_url( (string) $pg['src'] ); ?>" width="<?php echo (int) $pg['w']; ?>" height="<?php echo (int) $pg['h']; ?>" alt="<?php echo esc_attr( sprintf( 'Trang %d – %s', (int) $pg['n'], (string) ( $doc['title'] ?? '' ) ) ); ?>"<?php echo $i > 1 ? ' loading="lazy"' : ''; ?> decoding="async" draggable="false">
					</button>
					<figcaption><?php echo esc_html( sprintf( 'Trang %d / %d', (int) $pg['n'], $total ) ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
			<?php if ( $preview && $total > $ready ) : ?>
				<div class="cvc-rd-next">
					<?php if ( ! empty( $rd['teaser'] ) ) : ?><img src="<?php echo esc_url( (string) $rd['teaser'] ); ?>" alt="" aria-hidden="true" loading="lazy" draggable="false"><?php endif; ?>
					<div class="cvc-rd-cta" role="note">
						<p class="k">Còn <?php echo (int) ( $total - $ready ); ?> trang nữa</p>
						<p>Mua tài liệu (<?php echo esc_html( $price_txt ); ?>) để đọc trọn bản trực tuyến và tải tệp PDF đầy đủ.</p>
						<a href="<?php echo esc_url( $buy ); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-black text-sm rounded-xl bg-acc hover:bg-acc-deep"><i class="fa-solid fa-cart-shopping"></i> <?php echo esc_html( $is_logged_in ? 'Mua để đọc tiếp' : 'Đăng nhập để mua' ); ?></a>
					</div>
				</div>
			<?php elseif ( ! empty( $rd['building'] ) ) : ?>
				<p class="cvc-rd-wait" role="status">Đang chuẩn bị bản gốc các trang <?php echo (int) ( $ready + 1 ); ?>–<?php echo (int) $total; ?>. Bạn có thể đọc tiếp ở <button type="button" data-act="mode" data-v="text" class="cvc-rd-link">Bản chữ</button> hoặc tải lại trang sau ít phút.</p>
			<?php endif; ?>
		</div>

		<div class="cvc-rd-text" data-view="text">
			<?php if ( ! empty( $doc['content_html'] ) ) : ?>
				<?php echo wp_kses_post( (string) $doc['content_html'] ); ?>
				<?php if ( $preview ) : ?>
					<div class="cvc-locked" role="note">
						<p><b>Bạn đang đọc thử phần đầu tài liệu.</b> Mua tài liệu để đọc trọn bản trực tuyến và tải tệp đầy đủ.</p>
						<a href="<?php echo esc_url( $buy ); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 text-white font-black text-sm rounded-xl bg-acc hover:bg-acc-deep"><i class="fa-solid fa-cart-shopping"></i> <?php echo esc_html( $is_logged_in ? 'Mua tài liệu' : 'Đăng nhập để mua' ); ?></a>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<p class="cvc-rd-wait">Tài liệu này chưa có bản chữ.</p>
			<?php endif; ?>
		</div>

		<dialog class="cvc-rd-dlg" id="cvc-rd-dlg" aria-label="Xem trang phóng to">
			<button type="button" class="cvc-rd-close" aria-label="Đóng">&times;</button>
			<div class="cvc-rd-dlg-body"><img alt="" draggable="false"></div>
		</dialog>
		<div class="cvc-rd-toast" role="status" aria-live="polite"></div>
	</section>
	<?php
}

/**
 * Khối "Đọc tiếp" (tổng quan tài khoản / tài liệu của tôi): tối đa $max tài liệu đang đọc dở, mới nhất trước.
 *
 * @param array<int, array<string, mixed>> $rows Dữ liệu GET /api/me/reading.
 */
function cvc_doc_reading_list( array $rows, int $max = 3 ): void {
	$rows = array_slice( array_values( array_filter( $rows, fn ( $r ) => ! empty( $r['slug'] ) ) ), 0, $max );
	if ( ! $rows ) {
		return;
	}
	?>
	<ul class="cvc-reading-list">
		<?php foreach ( $rows as $r ) : $total = (int) ( $r['total'] ?? 0 ); $page = (int) ( $r['page'] ?? 1 ); $pct = $total > 0 ? min( 100, (int) round( $page * 100 / $total ) ) : 0; ?>
			<li>
				<a href="<?php echo esc_url( cvc_document_url( (string) $r['slug'] ) . '#doc-read' ); ?>">
					<?php if ( ! empty( $r['thumb'] ) ) : ?><img src="<?php echo esc_url( (string) $r['thumb'] ); ?>" alt="" width="44" height="62" loading="lazy"><?php endif; ?>
					<span class="b">
						<span class="t"><?php echo esc_html( (string) $r['title'] ); ?></span>
						<span class="m"><?php echo esc_html( $total > 0 ? sprintf( 'Đang đọc trang %d/%d', $page, $total ) : sprintf( 'Đang đọc trang %d', $page ) ); ?></span>
						<?php if ( $total > 0 ) : ?><span class="bar" role="img" aria-label="<?php echo esc_attr( 'Đã đọc ' . $pct . '%' ); ?>"><i style="width:<?php echo (int) $pct; ?>%"></i></span><?php endif; ?>
					</span>
					<span class="go">Đọc tiếp →</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<style>
	.cvc-reading-list{list-style:none;margin:0;padding:0;display:grid;gap:8px}
	.cvc-reading-list a{display:flex;align-items:center;gap:12px;padding:10px 12px;border:1px solid #e3e9ee;border-radius:12px;background:#fff;color:#17334f;text-decoration:none}
	.cvc-reading-list a:hover{border-color:#c9a052}
	.cvc-reading-list a:focus-visible{outline:2px solid #c9a052;outline-offset:2px}
	.cvc-reading-list img{flex:none;width:44px;height:62px;object-fit:cover;border-radius:3px;box-shadow:0 1px 3px rgba(15,42,74,.2)}
	.cvc-reading-list .b{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}
	.cvc-reading-list .t{font-weight:700;font-size:15px;line-height:1.35;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
	.cvc-reading-list .m{font-size:13px;color:#5b6470;font-variant-numeric:tabular-nums}
	.cvc-reading-list .bar{display:block;height:5px;border-radius:3px;background:#edf0f3;overflow:hidden;max-width:260px}
	.cvc-reading-list .bar i{display:block;height:100%;background:#b08a3e}
	.cvc-reading-list .go{flex:none;font-size:14px;font-weight:700;color:#7a5a17}
	@media (max-width:520px){.cvc-reading-list .go{display:none}}
	</style>
	<?php
}
