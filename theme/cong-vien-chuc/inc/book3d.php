<?php
/**
 * Sách 3D (01/11/2026) - mỗi tài liệu hiển thị như cuốn sách thật: bìa theo bộ sách + ngành,
 * gáy dày theo số trang, khối giấy, bóng, lật bìa xem mục lục. Dữ liệu từ API `book` (BookSpec).
 * Luôn có logo, QR (link ngắn /s/t{id}) và tên website trên bìa + gáy.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_BOOK_DOMAIN = 'congvienchuc.com';

/** Nạp CSS/JS + font của sách 3D. */
function cvc_book3d_enqueue(): void {
	$css = '/assets/css/book3d.css';
	$js  = '/assets/js/book3d.js';
	wp_enqueue_style( 'cvc-book-font', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;800&family=Tinos:ital@1&display=swap', array(), null );
	wp_enqueue_style( 'cvc-book3d', get_theme_file_uri( $css ), array(), (string) filemtime( get_theme_file_path( $css ) ) );
	wp_enqueue_script( 'cvc-book3d', get_theme_file_uri( $js ), array(), (string) filemtime( get_theme_file_path( $js ) ), true );
}

/** SVG QR từ API (do backend tự sinh) - chỉ giữ thẻ svg/rect/path. */
function cvc_book3d_qr( string $svg ): string {
	return wp_kses(
		$svg,
		array(
			'svg'  => array( 'xmlns' => true, 'viewbox' => true, 'shape-rendering' => true, 'role' => true, 'aria-label' => true ),
			'rect' => array( 'width' => true, 'height' => true, 'fill' => true, 'x' => true, 'y' => true ),
			'path' => array( 'fill' => true, 'd' => true ),
		)
	);
}

/** Tiêu đề có cụm nhấn. */
function cvc_book3d_title( array $t ): string {
	$out = esc_html( (string) ( $t['before'] ?? '' ) );
	if ( '' !== (string) ( $t['hl'] ?? '' ) ) {
		$out .= '<span class="bk-hl">' . esc_html( (string) $t['hl'] ) . '</span>';
	}

	return $out . esc_html( (string) ( $t['after'] ?? '' ) );
}

/** Họa tiết chính của bìa: mặt tiền công vụ (mặc định) hoặc bảng + sách mở (giáo dục). */
function cvc_book3d_motif( string $motif ): string {
	if ( 'board' === $motif ) {
		return '<svg class="bk-motif" aria-hidden="true" viewBox="0 0 560 372" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round">'
			. '<rect x="130" y="8" width="300" height="124" rx="4" stroke-opacity=".9"/><rect x="142" y="20" width="276" height="100" rx="2" stroke-opacity=".35"/>'
			. '<path d="M190 132 170 186M370 132 390 186" stroke-opacity=".6"/>'
			. '<path d="M280 346C214 318 120 316 36 336V196C120 176 214 178 280 206Z" stroke-opacity=".9"/><path d="M280 346C346 318 440 316 524 336V196C440 176 346 178 280 206Z" stroke-opacity=".9"/><path d="M280 206V346" stroke-opacity=".9"/>'
			. '<path d="M72 226C130 214 196 216 246 232M72 254C130 242 196 244 246 260M72 282C130 270 196 272 246 288M314 232C364 216 430 214 488 226M314 260C364 244 430 242 488 254M314 288C364 272 430 270 488 282" stroke-opacity=".45"/>'
			. '<path d="M24 362H536" stroke-opacity=".6"/></svg>';
	}

	return '<svg class="bk-motif" aria-hidden="true" viewBox="0 0 560 372" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">'
		. '<path d="M8 118 280 8 552 118Z" stroke-opacity=".9"/><path d="M58 106 280 22 502 106Z" stroke-opacity=".35"/><path d="M8 118H552M18 136H542" stroke-opacity=".7"/>'
		. '<path d="M52 148V318M80 148V318M146 148V318M174 148V318M240 148V318M268 148V318M292 148V318M320 148V318M386 148V318M414 148V318M480 148V318M508 148V318" stroke-opacity=".55"/>'
		. '<path d="M44 148H88M138 148H182M232 148H276M284 148H328M378 148H422M472 148H516M44 318H88M138 318H182M232 318H276M284 318H328M378 318H422M472 318H516" stroke-opacity=".7"/>'
		. '<path d="M28 334H532M14 350H546M0 366H560" stroke-opacity=".6"/></svg>';
}

/**
 * Sách 3D cho trang chi tiết tài liệu.
 *
 * @param array  $doc       Dữ liệu tài liệu từ API (có khóa `book`).
 * @param string $price_txt Nhãn giá (chỉ trên web).
 */
function cvc_book3d_render( array $doc, string $price_txt ): void {
	$b = (array) ( $doc['book'] ?? array() );
	if ( ! $b ) {
		return;
	}
	cvc_book3d_enqueue();
	$p      = (array) ( $b['palette'] ?? array() );
	$series = (array) ( $b['series'] ?? array() );
	$hard   = 'hard' === ( $series['binding'] ?? '' );
	$title  = (array) ( $b['title'] ?? array() );
	$plain  = trim( ( $title['before'] ?? '' ) . ( $title['hl'] ?? '' ) . ( $title['after'] ?? '' ) );
	$len    = mb_strlen( $plain );
	$tsize  = $len <= 36 ? 62 : ( $len <= 56 ? 54 : ( $len <= 80 ? 46 : 40 ) );
	$fp     = is_array( $b['fingerprint'] ?? null ) ? $b['fingerprint'] : null;
	$toc    = (array) ( $b['toc'] ?? array() );
	$eyebrow = ! empty( $b['version'] ) ? 'PHIÊN BẢN ' . (int) $b['version'] : ( ! empty( $b['pages'] ) ? (int) $b['pages'] . ' TRANG' : 'TÀI LIỆU ÔN THI' );
	$style  = sprintf(
		'--bk-ground:%s;--bk-accent:%s;--bk-soft:%s;--bk-muted:%s;--bk-t:%s',
		esc_attr( (string) ( $p['ground'] ?? '#0f2a4a' ) ),
		esc_attr( (string) ( $p['accent'] ?? '#c9a052' ) ),
		esc_attr( (string) ( $p['soft'] ?? '#e8d6ac' ) ),
		esc_attr( (string) ( $p['muted'] ?? '#c7d3e2' ) ),
		esc_attr( (string) (float) ( $b['thickness'] ?? 0.07 ) )
	);
	$logo   = get_theme_file_uri( '/assets/brand/icon-128.png' );
	$logo_w = get_theme_file_uri( '/assets/brand/icon-light-128.png' );
	$qr     = cvc_book3d_qr( (string) ( $b['qr_svg'] ?? '' ) );
	?>
	<figure class="bk<?php echo $hard ? ' is-hard' : ' is-soft'; ?>" style="<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput -- đã esc_attr từng giá trị ?>" data-bk>
		<div class="bk-stage">
			<div class="bk-book">
				<div class="bk-face bk-back" aria-hidden="true"></div>
				<div class="bk-face bk-edge bk-edge-r" aria-hidden="true"></div>
				<div class="bk-face bk-edge bk-edge-t" aria-hidden="true"></div>
				<div class="bk-face bk-edge bk-edge-b" aria-hidden="true"></div>
				<div class="bk-face bk-spine" aria-hidden="true">
					<img src="<?php echo esc_url( $logo_w ); ?>" alt="" width="40" height="40">
					<span class="bk-spine-t"><?php echo cvc_book3d_title( $title ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="bk-spine-d"><?php echo esc_html( CVC_BOOK_DOMAIN ); ?></span>
				</div>
				<div class="bk-face bk-page1" id="bk-toc">
					<div class="bk-page1-in">
						<p class="bk-page1-k"><?php echo esc_html( (string) ( $series['label'] ?? '' ) ); ?></p>
						<p class="bk-page1-h"><?php echo $toc ? 'Mục lục' : 'Giới thiệu'; ?></p>
						<?php if ( $toc ) : ?>
							<ol class="bk-toc">
								<?php foreach ( $toc as $it ) : ?>
									<li><a href="<?php echo esc_url( (string) $it['href'] ); ?>" tabindex="-1" data-bk-link><span><?php echo (int) $it['n']; ?></span><?php echo esc_html( (string) $it['t'] ); ?></a></li>
								<?php endforeach; ?>
							</ol>
						<?php else : ?>
							<p class="bk-page1-p"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) ( $doc['description'] ?? '' ) ), 60 ) ?: 'Tài liệu dành cho người ôn thi công chức, viên chức.' ); ?></p>
						<?php endif; ?>
						<p class="bk-page1-f"><?php echo esc_html( CVC_BOOK_DOMAIN ); ?></p>
					</div>
				</div>
				<div class="bk-cover">
					<div class="bk-cover-front">
						<div class="bk-art">
							<div class="bk-grid" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
							<div class="bk-head">
								<div class="bk-brand"><span class="bk-logo"><img src="<?php echo esc_url( $logo ); ?>" alt="" width="42" height="42"></span><span><b>CÔNG VIÊN CHỨC</b><small>Ôn thi vững vàng · Công vụ tự tin</small></span></div>
								<div class="bk-series"><i></i><?php echo esc_html( (string) ( $series['label'] ?? '' ) ); ?></div>
							</div>
							<div class="bk-motif-wrap">
								<?php echo cvc_book3d_motif( (string) ( $b['motif'] ?? 'facade' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php if ( ! empty( $b['year'] ) ) : ?><span class="bk-year bk-year-<?php echo esc_attr( (string) ( $b['motif'] ?? 'facade' ) ); ?>"><?php echo esc_html( (string) $b['year'] ); ?></span><?php endif; ?>
							</div>
							<div class="bk-titleblock">
								<span class="bk-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
								<span class="bk-title" style="font-size:<?php echo (int) $tsize; ?>px"><?php echo cvc_book3d_title( $title ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<?php if ( ! empty( $b['subtitle'] ) ) : ?><span class="bk-sub"><?php echo esc_html( (string) $b['subtitle'] ); ?></span><?php endif; ?>
							</div>
							<div class="bk-foot">
								<div class="bk-foot-l">
									<?php if ( $fp ) : ?>
										<span class="bk-k">VÂN TAY NỘI DUNG</span>
										<span class="bk-bars"><?php foreach ( (array) $fp['bars'] as $i => $v ) : ?><i style="height:<?php echo (int) round( 58 * (float) $v ); ?>px<?php echo $i % 2 ? ';opacity:.7' : ''; ?>"></i><?php endforeach; ?></span>
										<span class="bk-dots"><?php foreach ( (array) $fp['dots'] as $g ) : ?><i class="g<?php echo (int) $g; ?>"></i><?php endforeach; ?></span>
										<span class="bk-cap"><?php echo esc_html( (string) $fp['caption'] ); ?></span>
									<?php else : ?>
										<span class="bk-k">THƯ VIỆN TÀI LIỆU</span>
										<span class="bk-domain"><?php echo esc_html( CVC_BOOK_DOMAIN ); ?></span>
										<span class="bk-cap">Ôn thi công chức, viên chức</span>
									<?php endif; ?>
									<span class="bk-basis"><?php echo ! empty( $b['basis_date'] ) ? 'Căn cứ văn bản đến ngày ' . esc_html( (string) $b['basis_date'] ) . ' · ' : ''; ?>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Công Viên Chức</span>
								</div>
								<div class="bk-foot-r">
									<span class="bk-scan"><b>Quét để mở<br>tài liệu</b><em><?php echo esc_html( CVC_BOOK_DOMAIN ); ?></em></span>
									<span class="bk-qr"><?php echo $qr; // phpcs:ignore WordPress.Security.EscapeOutput -- đã wp_kses ?></span>
								</div>
							</div>
						</div>
						<span class="bk-light" aria-hidden="true"></span>
						<?php if ( '' !== $price_txt ) : ?><span class="bk-price"><small><?php echo esc_html( (string) ( $b['format_label'] ?? 'TÀI LIỆU' ) ); ?></small><?php echo esc_html( $price_txt ); ?></span><?php endif; ?>
					</div>
					<div class="bk-cover-inside" aria-hidden="true"><img src="<?php echo esc_url( $logo_w ); ?>" alt="" width="64" height="64"><span><?php echo esc_html( CVC_BOOK_DOMAIN ); ?></span></div>
				</div>
				<?php if ( $hard ) : ?><span class="bk-ribbon" aria-hidden="true"></span><?php endif; ?>
			</div>
			<span class="bk-shadow" aria-hidden="true"></span>
		</div>
		<figcaption class="bk-cap-ui">
			<button type="button" class="bk-toggle" aria-expanded="false" aria-controls="bk-toc" data-bk-toggle><span data-bk-label data-closed="<?php echo esc_attr( $toc ? 'Lật bìa xem mục lục' : 'Lật bìa xem giới thiệu' ); ?>"><?php echo esc_html( $toc ? 'Lật bìa xem mục lục' : 'Lật bìa xem giới thiệu' ); ?></span></button>
			<?php if ( ! empty( $b['pages'] ) ) : ?><span class="bk-meta"><?php echo (int) $b['pages']; ?> trang</span><?php endif; ?>
		</figcaption>
	</figure>
	<?php
}

/* ------------------------------------------------------------------ */
/* Link ngắn trên QR bìa: /s/t{base36 id} -> trang tài liệu             */
/* ------------------------------------------------------------------ */

add_action( 'init', 'cvc_book3d_rewrite' );
function cvc_book3d_rewrite(): void {
	add_rewrite_rule( '^s/(t[0-9a-z]{1,10})/?$', 'index.php?cvc_page=short-link&cvc_short_code=$matches[1]', 'top' );
}

add_filter( 'query_vars', static function ( array $vars ): array {
	$vars[] = 'cvc_short_code';

	return $vars;
} );

add_action( 'template_redirect', 'cvc_book3d_short_link', 1 );
function cvc_book3d_short_link(): void {
	if ( 'short-link' !== get_query_var( 'cvc_page' ) ) {
		return;
	}
	$code = (string) get_query_var( 'cvc_short_code' );
	$res  = preg_match( '/^t[0-9a-z]{1,10}$/', $code ) ? ( new CVC_Api_Client() )->get( '/api/documents/resolve/' . $code ) : array( 'ok' => false );
	$slug = (string) ( $res['data']['data']['slug'] ?? '' );
	if ( ! $res['ok'] || '' === $slug ) {
		wp_safe_redirect( cvc_documents_url(), 302 );
		exit;
	}
	$url = add_query_arg( array( 'utm_source' => 'qr', 'utm_medium' => 'bia-sach' ), cvc_document_url( $slug ) );
	wp_safe_redirect( $url . ( ! empty( $res['data']['data']['read'] ) ? '#doc-read' : '' ), 302 );
	exit;
}
