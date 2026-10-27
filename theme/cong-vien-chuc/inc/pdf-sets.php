<?php
/**
 * Bộ đề PDF (27/10/2026).
 *
 *  - /bo-de-pdf/ : danh mục bộ đề PDF (theo tin tuyển dụng / theo văn bản / đề thi thử & ôn tập), lọc tỉnh, tìm, sắp xếp.
 *  - /bo-de-pdf/{slug}/ : trang đích từng bộ đề theo tin (xem trước câu hỏi, tải PDF, ảnh bìa chia sẻ, văn bản ôn, bộ đề liên quan).
 *  - /sitemap-bo-de-pdf.xml (gắn vào sitemap_index của Rank Math).
 *  - Khối "Bộ đề PDF" cho trang chủ, trang văn bản pháp luật, trang Tài liệu.
 *  - Đo lượt quay lại từ file PDF / ảnh bìa: utm_source=pdf|cover -> AJAX cvc_pdf_visit -> POST /api/exam-pdf/visit.
 * API: /api/pdf-sets*, /api/legal-documents/{id}/pdf-sets, /api/recruitments/{id}/pdf-sets, /api/sitemap/pdf-sets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_PDF_TABS = array(
	'tin'     => 'Theo tin tuyển dụng',
	'van-ban' => 'Theo văn bản pháp luật',
	'de-thi'  => 'Đề thi thử & ôn tập',
);

function cvc_pdf_sets_url( array $args = array() ): string {
	$page = (int) ( $args['trang'] ?? 1 );
	unset( $args['trang'] );
	$path = $page > 1 ? '/bo-de-pdf/page/' . $page . '/' : '/bo-de-pdf/';
	$args = array_filter( $args, static fn ( $v ) => '' !== (string) $v && 'tin' !== (string) $v );

	return add_query_arg( array_map( 'rawurlencode', $args ), home_url( $path ) );
}

function cvc_pdf_set_url( string $slug ): string {
	return home_url( '/bo-de-pdf/' . rawurlencode( $slug ) . '/' );
}

/** URL ảnh bìa (API) cho bộ đề / đề lẻ. */
function cvc_pdf_cover_url( string $kind, string $key ): string {
	$base = rtrim( (string) cvc_api_base_url(), '/' );

	return 'exam' === $kind ? $base . '/api/exams/' . (int) $key . '/pdf/cover.png' : $base . '/api/pdf-sets/' . rawurlencode( $key ) . '/cover.png';
}

add_action( 'wp_enqueue_scripts', 'cvc_pdf_sets_assets', 56 );

function cvc_pdf_sets_assets(): void {
	$page = (string) get_query_var( 'cvc_page' );
	if ( in_array( $page, array( 'pdf-sets', 'pdf-set-detail' ), true ) || is_front_page() || in_array( $page, array( 'legal-document-detail', 'documents' ), true ) ) {
		$css = get_theme_file_path( '/assets/css/pdf-sets.css' );
		wp_enqueue_style( 'cvc-pdf-sets', get_theme_file_uri( '/assets/css/pdf-sets.css' ), array(), (string) @filemtime( $css ) );
	}
	cvc_pdf_visit_script();
}

/* ------------------------------------------------------------------
 * Dữ liệu
 * ------------------------------------------------------------------ */

function cvc_pdf_api( string $path, array $query = array(), int $cache = 300 ): array {
	$res = cvc_ci_api( $path, $query, null, 15, $cache );

	return ! empty( $res['ok'] ) && is_array( $res['data']['data'] ?? null ) ? (array) $res['data']['data'] : array();
}

/** Nút tải PDF cho 1 thẻ (bộ đề theo tin hoặc đề lẻ). */
function cvc_pdf_card_button( array $item, string $label = 'Tải PDF', string $class = 'is-small' ): string {
	$pdf = (array) ( $item['pdf'] ?? array() );
	if ( empty( $pdf['id'] ) ) {
		return '';
	}

	return cvc_pdf_button(
		'recruitment' === ( $pdf['kind'] ?? '' ) ? 'recruitment' : 'exam',
		(int) $pdf['id'],
		array(
			'group'   => (string) ( $pdf['group'] ?? '' ),
			'label'   => $label,
			'variant' => 'dap-an',
			'class'   => $class,
		)
	);
}

/** Liên kết chính của thẻ: trang đích bộ đề (tin) hoặc trang đề (đề lẻ). */
function cvc_pdf_item_url( array $item ): string {
	return 'tin' === ( $item['kind'] ?? '' ) ? cvc_pdf_set_url( (string) $item['slug'] ) : cvc_exam_url( (string) $item['slug'] );
}

function cvc_pdf_render_card( array $it, bool $compact = false ): void {
	$url  = cvc_pdf_item_url( $it );
	$kind = (string) ( $it['kind'] ?? 'tin' );
	$tag  = array( 'tin' => 'Theo tin tuyển dụng', 'van-ban' => 'Theo văn bản', 'de-thi' => 'Đề thi thử' )[ $kind ] ?? '';
	?>
	<article class="pds-card<?php echo $compact ? ' is-compact' : ''; ?>">
		<div class="pds-card-top">
			<span class="pds-tag is-<?php echo esc_attr( $kind ); ?>"><?php echo esc_html( $tag ); ?></span>
			<?php if ( ! empty( $it['is_open'] ) ) : ?><span class="pds-tag is-open">Đang nhận hồ sơ</span><?php endif; ?>
		</div>
		<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $it['title'] ); ?></a></h3>
		<?php if ( ! empty( $it['subtitle'] ) ) : ?><p class="pds-sub"><?php echo esc_html( (string) $it['subtitle'] ); ?></p><?php endif; ?>
		<p class="pds-meta">
			<?php if ( (int) ( $it['exams'] ?? 1 ) > 1 ) : ?><span><?php echo (int) $it['exams']; ?> đề</span><?php endif; ?>
			<span><?php echo esc_html( number_format( (int) ( $it['questions'] ?? 0 ), 0, ',', '.' ) ); ?> câu</span>
			<span>Có đáp án &amp; giải thích</span>
			<?php if ( (int) ( $it['downloads'] ?? 0 ) > 0 ) : ?><span><?php echo (int) $it['downloads']; ?> lượt tải</span><?php endif; ?>
		</p>
		<div class="pds-card-actions">
			<?php echo cvc_pdf_card_button( $it ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<a class="pds-link" href="<?php echo esc_url( $url ); ?>"><?php echo 'tin' === $kind ? 'Xem bộ đề ›' : 'Làm đề online ›'; ?></a>
		</div>
	</article>
	<?php
}

/** Khối "Bộ đề PDF" trang chủ: mới + tải nhiều tuần. */
function cvc_pdf_render_home_block(): void {
	$d = cvc_pdf_api( '/api/pdf-sets/highlights', array(), 600 );
	$new = array_values( array_filter( (array) ( $d['new'] ?? array() ), 'is_array' ) );
	if ( ! $new ) {
		return;
	}
	$pop = array_values( array_filter( (array) ( $d['popular'] ?? array() ), 'is_array' ) );
	cvc_pdf_assets();
	?>
	<section class="pds-home" aria-labelledby="pds-home-h">
		<div class="pds-home-in">
			<div class="pds-home-head">
				<div>
					<p class="pds-eyebrow">BỘ ĐỀ PDF · MIỄN PHÍ</p>
					<h2 id="pds-home-h">Tải bộ đề trắc nghiệm có đáp án</h2>
					<p><?php echo esc_html( (int) ( $d['total_sets'] ?? count( $new ) ) . ' bộ đề biên soạn riêng theo từng tin tuyển dụng, kèm đề theo văn bản pháp luật. File có đáp án, giải thích, căn cứ và mã QR làm bài online.' ); ?></p>
				</div>
				<a class="pds-btn" href="<?php echo esc_url( cvc_pdf_sets_url() ); ?>">Xem tất cả bộ đề ›</a>
			</div>
			<div class="pds-home-cols">
				<div>
					<h3 class="pds-h3">Bộ đề mới</h3>
					<div class="pds-grid is-2"><?php foreach ( array_slice( $new, 0, 4 ) as $it ) { cvc_pdf_render_card( $it, true ); } ?></div>
				</div>
				<?php if ( $pop ) : ?>
					<div>
						<h3 class="pds-h3">Tải nhiều nhất tuần</h3>
						<ol class="pds-rank">
							<?php foreach ( array_slice( $pop, 0, 6 ) as $i => $it ) : ?>
								<li><b><?php echo (int) $i + 1; ?></b><a href="<?php echo esc_url( cvc_pdf_item_url( $it ) ); ?>"><?php echo esc_html( (string) $it['title'] ); ?></a><small><?php echo (int) ( $it['questions'] ?? 0 ); ?> câu</small></li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/** Khối trang văn bản pháp luật: đề trắc nghiệm của văn bản + bộ đề theo tin có dùng văn bản. */
function cvc_pdf_render_legal_block( int $doc_id, string $doc_label ): void {
	if ( $doc_id <= 0 ) {
		return;
	}
	$d        = cvc_pdf_api( '/api/legal-documents/' . $doc_id . '/pdf-sets', array(), 600 );
	$practice = is_array( $d['practice'] ?? null ) ? $d['practice'] : null;
	$sets     = array_values( array_filter( (array) ( $d['sets'] ?? array() ), 'is_array' ) );
	if ( ! $practice && ! $sets ) {
		return;
	}
	?>
	<section class="pds-legal" aria-labelledby="pds-legal-h">
		<h2 id="pds-legal-h">Tải đề trắc nghiệm theo văn bản này (PDF)</h2>
		<?php if ( $practice ) : ?>
			<div class="pds-legal-row">
				<p><b><?php echo (int) $practice['total_questions']; ?> câu trắc nghiệm <?php echo esc_html( $doc_label ); ?></b> — có đáp án, giải thích và dẫn chiếu Điều. File PDF có mã QR làm bài online.</p>
				<div class="pds-card-actions">
					<?php echo cvc_pdf_button( 'exam', (int) $practice['id'], array( 'label' => 'Tải đề PDF (có đáp án)', 'variant' => 'dap-an' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<a class="pds-link" href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>">Làm online ›</a>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( $sets ) : ?>
			<h3 class="pds-h3">Bộ đề theo tin tuyển dụng có ôn văn bản này</h3>
			<div class="pds-grid is-2"><?php foreach ( array_slice( $sets, 0, 4 ) as $it ) { cvc_pdf_render_card( $it, true ); } ?></div>
			<p><a class="pds-link" href="<?php echo esc_url( cvc_pdf_sets_url() ); ?>">Xem tất cả bộ đề PDF ›</a></p>
		<?php endif; ?>
	</section>
	<?php
}

/** Dải giới thiệu trên trang Tài liệu. */
function cvc_pdf_render_docs_banner(): void {
	?>
	<a class="pds-banner" href="<?php echo esc_url( cvc_pdf_sets_url() ); ?>">
		<span class="pds-banner-ic" aria-hidden="true">PDF</span>
		<span><b>Bộ đề PDF có đáp án — miễn phí</b><small>Đề trắc nghiệm theo từng tin tuyển dụng và từng văn bản pháp luật, tải về in ôn tập.</small></span>
		<span class="pds-banner-go">Xem bộ đề ›</span>
	</a>
	<?php
}

/** Liên kết trang đích bộ đề cho từng nhóm đề riêng trên trang tin tuyển dụng / vị trí: group => slug. */
function cvc_pdf_set_slugs_for_recruitment( int $recruitment_id ): array {
	if ( $recruitment_id <= 0 ) {
		return array();
	}
	$out = array();
	foreach ( (array) cvc_pdf_api( '/api/recruitments/' . $recruitment_id . '/pdf-sets', array(), 600 ) as $row ) {
		if ( is_array( $row ) && ! empty( $row['group'] ) && ! empty( $row['slug'] ) ) {
			$out[ (string) $row['group'] ] = (string) $row['slug'];
		}
	}

	return $out;
}

/* ------------------------------------------------------------------
 * Sitemap
 * ------------------------------------------------------------------ */

add_action( 'template_redirect', 'cvc_pdf_sitemap_render', 0 );
add_filter( 'rank_math/sitemap/index', 'cvc_pdf_sitemap_index', 12 );

function cvc_pdf_sitemap_rows(): ?array {
	$d = cvc_pdf_api( '/api/sitemap/pdf-sets', array(), 1800 );
	if ( ! isset( $d['sets'] ) ) {
		return null;
	}

	return array_values( array_filter( (array) $d['sets'], 'is_array' ) );
}

function cvc_pdf_sitemap_render(): void {
	if ( 'bo-de-pdf' !== (string) get_query_var( 'cvc_sitemap' ) ) {
		return;
	}
	$rows = cvc_pdf_sitemap_rows();
	if ( null === $rows ) {
		status_header( 503 );
		header( 'Retry-After: 600' );
		exit;
	}
	status_header( 200 );
	header( 'Content-Type: application/xml; charset=utf-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
	echo "\t<url><loc>" . esc_url( cvc_pdf_sets_url() ) . "</loc></url>\n";
	foreach ( $rows as $r ) {
		$slug = (string) ( $r['slug'] ?? '' );
		if ( '' === $slug ) {
			continue;
		}
		echo "\t<url><loc>" . esc_url( cvc_pdf_set_url( $slug ) ) . '</loc>';
		if ( ! empty( $r['lastmod'] ) && false !== strtotime( (string) $r['lastmod'] ) ) {
			echo '<lastmod>' . esc_html( gmdate( 'c', (int) strtotime( (string) $r['lastmod'] ) ) ) . '</lastmod>';
		}
		echo '<image:image><image:loc>' . esc_url( cvc_pdf_cover_url( 'set', $slug ) ) . '</image:loc></image:image>';
		echo "</url>\n";
	}
	echo '</urlset>';
	exit;
}

function cvc_pdf_sitemap_index( $xml ) {
	return (string) $xml . "\t<sitemap>\n\t\t<loc>" . esc_url( home_url( '/sitemap-bo-de-pdf.xml' ) ) . "</loc>\n\t</sitemap>\n";
}

/* ------------------------------------------------------------------
 * Đo lượt quay lại từ file PDF / ảnh bìa
 * ------------------------------------------------------------------ */

/** In script ghi lượt khi URL có utm_source=pdf|cover (và lưu nguồn vào sessionStorage cho AI Coach). */
function cvc_pdf_visit_script(): void {
	$src = isset( $_GET['utm_source'] ) ? sanitize_key( wp_unslash( $_GET['utm_source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! in_array( $src, array( 'pdf', 'cover', 'email' ), true ) ) {
		return;
	}
	wp_register_script( 'cvc-pdf-visit', '', array(), '1', true );
	wp_enqueue_script( 'cvc-pdf-visit' );
	$cfg = array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'cvc_pdf_visit' ),
	);
	wp_add_inline_script(
		'cvc-pdf-visit',
		'(function(c){try{var p=new URLSearchParams(location.search),s=p.get("utm_source");if(s!=="pdf"&&s!=="cover"&&s!=="email")return;'
		. 'var r={source:s,medium:p.get("utm_medium")||"",code:p.get("utm_content")||""};'
		. 'try{sessionStorage.setItem("cvc-pdf-ref",JSON.stringify(r));}catch(e){}'
		. 'function send(ev){var f=new FormData();f.append("action","cvc_pdf_visit");f.append("_n",c.nonce);f.append("event",ev);f.append("source",r.source);f.append("medium",r.medium);f.append("code",r.code);f.append("path",location.pathname);'
		. 'if(navigator.sendBeacon){navigator.sendBeacon(c.ajax,f);}else{fetch(c.ajax,{method:"POST",body:f,credentials:"same-origin",keepalive:true});}}'
		. 'send("visit");if(p.get("ai")==="1")send("ai_open");'
		. 'window.CVC_PDF_REF_SEND=send;}catch(e){}})(' . wp_json_encode( $cfg ) . ');'
	);
}

add_action( 'wp_ajax_cvc_pdf_visit', 'cvc_pdf_ajax_visit' );
add_action( 'wp_ajax_nopriv_cvc_pdf_visit', 'cvc_pdf_ajax_visit' );

function cvc_pdf_ajax_visit(): void {
	// phpcs:disable WordPress.Security.NonceVerification -- beacon công khai, chống lặp ở API.
	$event = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
	if ( ! in_array( $event, array( 'visit', 'ai_open', 'ai_ask' ), true ) ) {
		wp_send_json_error( null, 400 );
	}
	$body = array(
		'event'  => $event,
		'source' => in_array( $_POST['source'] ?? '', array( 'pdf', 'cover', 'email' ), true ) ? (string) $_POST['source'] : 'pdf',
		'medium' => substr( sanitize_key( wp_unslash( $_POST['medium'] ?? '' ) ), 0, 32 ),
		'code'   => preg_match( '/^T-[A-Z0-9]{4,12}$/', (string) ( $_POST['code'] ?? '' ) ) ? (string) $_POST['code'] : '',
		'path'   => substr( esc_url_raw( home_url( (string) wp_unslash( $_POST['path'] ?? '/' ) ) ), 0, 255 ),
	);
	// phpcs:enable
	$body['path'] = (string) wp_parse_url( $body['path'], PHP_URL_PATH );
	( new CVC_Api_Client( null, 5 ) )->post( '/api/exam-pdf/visit', array_filter( $body ), cvc_auth_token() );
	wp_send_json_success();
}
