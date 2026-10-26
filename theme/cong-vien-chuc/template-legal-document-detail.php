<?php
/**
 * Chi tiết văn bản pháp luật v2 (19/10/2026) - thiết kế congvienchuc-chi-tiet-van-ban-v2.
 * URL: /van-ban-phap-luat/{slug}/ · Header/footer: khung chung của site (get_header / get_footer).
 *
 * CHỈ dữ liệu thật từ GET /api/legal-documents/{slug}: toàn văn chính thức (Công báo / cổng thông tin cơ quan
 * ban hành) tách theo Điều, tệp gốc, hiệu lực tính từ dữ liệu, điểm xác minh tính ở backend (chỉ điểm/mức/nhãn),
 * câu hỏi + bài học gắn theo từng Điều, tin tuyển dụng dẫn chiếu văn bản. Nút theo Điều chỉ hiện khi có dữ liệu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug     = sanitize_title( (string) get_query_var( 'cvc_legal_document_slug' ) );
$result   = ( new CVC_Legal_Document_Service() )->find( $slug );
$document = $result['ok'] && is_array( $result['data']['data'] ?? null ) ? $result['data']['data'] : null;

if ( null === $document ) {
	$down = ! $result['ok'] && 404 !== (int) ( $result['status'] ?? 0 );
	status_header( $down ? 503 : 404 );
	if ( $down ) {
		nocache_headers();
	}
	cvc_seo_set_noindex();
	cvc_seo_set_title( $down ? 'Tạm thời chưa tải được văn bản' : 'Không tìm thấy văn bản pháp luật' );
	get_header();
	?>
	<main id="main" class="ld"><div class="ld-wrap ld-state">
		<p class="ld-eyebrow"><?php echo $down ? 'Tạm thời gián đoạn' : 'Không tìm thấy'; ?></p>
		<h1><?php echo $down ? 'Tạm thời chưa tải được văn bản' : 'Không tìm thấy văn bản pháp luật'; ?></h1>
		<p><?php echo $down ? 'Hệ thống đang bận, vui lòng tải lại trang sau ít phút.' : 'Văn bản có thể đã được gỡ hoặc đường dẫn không đúng.'; ?></p>
		<p class="ld-actions"><a class="ld-btn is-primary" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">← Về thư viện văn bản pháp luật</a></p>
	</div></main>
	<?php
	get_footer();
	return;
}

/* ------------------------------ Dữ liệu ------------------------------ */
$doc_id      = (int) ( $document['id'] ?? 0 );
$title       = (string) ( $document['title'] ?? '' );
$doc_number  = (string) ( $document['document_number'] ?? '' );
$doc_type    = (string) ( $document['document_type'] ?? '' );
$agency      = (string) ( $document['issuing_agency'] ?? '' );
$summary     = trim( (string) ( $document['summary'] ?? '' ) );
$source_url  = (string) ( $document['source_url'] ?? '' );
$validity    = is_array( $document['validity'] ?? null ) ? $document['validity'] : array( 'code' => 'unknown', 'label' => 'Chưa cập nhật hiệu lực' );
$replaced_by = is_array( $document['replaced_by'] ?? null ) ? $document['replaced_by'] : null;
$replaces    = (array) ( $document['replaced_documents'] ?? array() );
$knowledge   = (array) ( $document['knowledge_items'] ?? array() );
$q_count     = (int) ( $document['related_questions_count'] ?? 0 );
$articles    = (array) ( $document['articles'] ?? array() );
$full_text   = (string) ( $document['full_text'] ?? '' );
$file        = is_array( $document['file'] ?? null ) ? $document['file'] : null;
$practice    = is_array( $document['practice_exam'] ?? null ) ? $document['practice_exam'] : null;
$amended_by  = (array) ( $document['amended_by'] ?? array() );
$is_verified = ! empty( $document['is_verified'] );
$is_ocr      = ! empty( $document['is_ocr'] );
$has_text    = ! empty( $articles ) || '' !== $full_text;
$ver         = is_array( $document['verification'] ?? null ) ? $document['verification'] : null;
$recs        = (array) ( $document['recruitments'] ?? array() );
$checked_at  = (string) ( $document['source_checked_at'] ?? '' );
$is_pdf      = $file && 'pdf' === strtolower( (string) ( $file['extension'] ?? '' ) );
$pdf_inline  = $is_pdf ? add_query_arg( 'inline', 1, (string) $file['download_url'] ) : '';
$vcode       = (string) ( $validity['code'] ?? 'unknown' );
$logged_in   = cvc_is_logged_in();
$doc_url     = cvc_legal_document_url( $slug );
if ( $logged_in ) {
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'cvc: trang cá nhân hóa (đánh dấu)' );
}

// Theo Điều: số câu hỏi, bài học, trang PDF, tin tuyển dụng dẫn chiếu.
$q_by_art = array();
foreach ( (array) ( $document['article_questions'] ?? array() ) as $r ) {
	$q_by_art[ (int) $r['id'] ] = (int) $r['count'];
}
$ki_by_art = array();
foreach ( $knowledge as $k ) {
	if ( ! empty( $k['article_id'] ) && ! empty( $k['slug'] ) ) {
		$ki_by_art[ (int) $k['article_id'] ][] = array( 'title' => (string) $k['title'], 'url' => cvc_knowledge_item_url( (string) $k['slug'] ), 'summary' => (string) ( $k['summary'] ?? '' ) );
	}
}
$page_by_num = array();
foreach ( (array) ( $document['article_pages'] ?? array() ) as $r ) {
	$page_by_num[ (string) $r['number'] ] = (int) $r['page'];
}
$rec_by_num = array();
foreach ( $recs as $r ) {
	foreach ( (array) ( $r['articles'] ?? array() ) as $n ) {
		$rec_by_num[ (string) $n ][] = array( 'title' => (string) $r['title'], 'url' => cvc_recruitment_url( (string) $r['slug'] ), 'status' => (string) $r['status'] );
	}
}

// Đánh dấu của người dùng (chỉ khi đăng nhập).
$saved_doc  = false;
$saved_arts = array();
if ( $logged_in && ( $tok = cvc_auth_token() ) ) {
	foreach ( array( 'legal_document', 'legal_article' ) as $bt ) {
		$br = ( new CVC_Api_Client( null, 6 ) )->get( '/api/bookmarks', array( 'type' => $bt, 'per_page' => 200 ), $tok );
		foreach ( (array) ( $br['data']['data']['data'] ?? array() ) as $b ) {
			$bid = (int) ( $b['bookmarkable_id'] ?? 0 );
			if ( 'legal_document' === $bt && $bid === $doc_id ) {
				$saved_doc = true;
			} elseif ( 'legal_article' === $bt ) {
				$saved_arts[] = $bid;
			}
		}
	}
}

/* ------------------------------ Hiển thị văn bản ------------------------------ */
$article_anchor = static function ( array $a ): string {
	$n = (string) ( $a['number'] ?? '' );
	return 'dieu-' . sanitize_title( '' !== $n ? $n : (string) ( $a['id'] ?? '' ) );
};
// Văn bản lấy từ PDF giữ ngắt dòng của trang in: nối dòng bị ngắt giữa câu, giữ xuống dòng trước khoản/điểm.
$legal_reflow = static function ( string $text ): string {
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
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
$is_appendix = static fn ( array $a ): bool => '' !== (string) ( $a['number'] ?? '' ) && 'P' === ( (string) $a['number'] )[0];
$article_label = static function ( array $a ) use ( $is_appendix ): string {
	return $is_appendix( $a ) ? (string) ( $a['title'] ?? $a['number'] ) : 'Điều ' . $a['number'] . ( ! empty( $a['title'] ) ? '. ' . $a['title'] : '' );
};

$file_label = '';
if ( $file ) {
	$size_kb    = (int) round( ( (int) ( $file['size'] ?? 0 ) ) / 1024 );
	$file_label = strtoupper( (string) ( $file['extension'] ?? 'PDF' ) ) . ( $size_kb > 0 ? ' · ' . ( $size_kb >= 1024 ? number_format_i18n( $size_kb / 1024, 1 ) . ' MB' : $size_kb . ' KB' ) : '' );
}
$vclass = array( 'high' => 'is-high', 'good' => 'is-good', 'partial' => 'is-mid', 'low' => 'is-low' )[ $ver['level'] ?? 'low' ] ?? 'is-low';
$vcopy  = array(
	'high'    => 'Có nguồn chính thức đã đối chiếu, tệp gốc kèm mã băm, toàn văn tách theo Điều và thông tin ban hành đầy đủ.',
	'good'    => 'Phần lớn thông tin có nguồn và tệp gốc; một số mục còn thiếu bằng chứng hoặc chưa được kiểm tra lại.',
	'partial' => 'Mới có một phần bằng chứng — đối chiếu văn bản gốc trước khi sử dụng.',
	'low'     => 'Chưa đủ dữ liệu xác minh. Không dùng làm căn cứ khi chưa kiểm tra nguồn ban hành.',
)[ $ver['level'] ?? 'low' ] ?? '';
$st_class = in_array( $vcode, array( 'in_force', 'active' ), true ) ? 'is-ok' : ( in_array( $vcode, array( 'expired', 'replaced' ), true ) ? 'is-bad' : 'is-warn' );
$host     = '' !== $source_url ? (string) wp_parse_url( $source_url, PHP_URL_HOST ) : '';

/* ------------------------------ SEO ------------------------------ */
$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Văn bản pháp luật', 'url' => cvc_legal_documents_url() ),
	array( 'label' => '' !== $doc_number ? $doc_number : wp_trim_words( $title, 8 ) ),
);
cvc_seo_set_title( '' !== $doc_number && false === mb_strpos( $title, $doc_number ) ? $doc_number . ' — ' . $title : $title );
cvc_seo_set_description( cvc_seo_trim( trim( $title . '. ' . ( '' !== $summary && false === mb_stripos( $title, $summary ) ? $summary . '. ' : '' ) . ( '' !== $agency ? 'Cơ quan ban hành: ' . $agency . '. ' : '' ) . (string) $validity['label'] . '.' ), 160 ) );
cvc_seo_set_canonical( $doc_url );
cvc_seo_set_og( array( 'type' => 'article' ) );
cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
$ld = array_filter(
	array(
		'@context'               => 'https://schema.org',
		'@type'                  => 'Legislation',
		'name'                   => $title,
		'legislationIdentifier'  => $doc_number ?: null,
		'legislationType'        => $doc_type ?: null,
		'legislationDate'        => $document['issued_date'] ?? null,
		'legislationDateVersion' => $document['effective_date'] ?? null,
		'legislationPassedBy'    => '' !== $agency ? array( '@type' => 'GovernmentOrganization', 'name' => $agency ) : null,
		'legislationLegalForce'  => in_array( $vcode, array( 'expired', 'replaced' ), true ) ? 'https://schema.org/NotInForce' : ( 'in_force' === $vcode ? 'https://schema.org/InForce' : null ),
		'legislationJurisdiction' => 'VN',
		'inLanguage'             => 'vi',
		'url'                    => $doc_url,
		'sameAs'                 => '' !== $source_url ? $source_url : null,
	)
);
cvc_seo_add_json_ld( $ld );

// Nạp vào window.CVC_LD bởi cvc_legal_detail_assets() (inc/legal-detail.php) khi wp_head chạy.
$GLOBALS['cvc_ld_data'] = array(
		'ajax'     => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'cvc_ld_bm' ),
		'logged'   => $logged_in,
		'login'    => cvc_login_url( $doc_url ),
		'docId'    => $doc_id,
		'number'   => $doc_number,
		'url'      => $doc_url,
		'pdf'      => $pdf_inline,
		'pages'    => (object) $page_by_num,
		'kis'      => (object) $ki_by_art,
		'qcount'   => (object) $q_by_art,
		'recs'     => (object) $rec_by_num,
		'practice' => $practice && ! empty( $practice['slug'] ) ? array( 'url' => cvc_exam_url( (string) $practice['slug'] ), 'total' => (int) $practice['total_questions'] ) : null,
);

get_header();
?>
<main id="main" class="ld">
<?php cvc_render_track_marker( 'legal_document_viewed', 'legal_document', $doc_id ); ?>
<div class="ld-wrap">
	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>
	<?php cvc_render_notice(); ?>

	<!-- ============ HERO ============ -->
	<section class="ld-hero">
		<div class="ld-hero-top">
			<div>
				<p class="ld-agency"><?php echo esc_html( mb_strtoupper( '' !== $agency ? $agency : 'Cơ quan ban hành', 'UTF-8' ) ); ?><?php echo '' !== $doc_number ? ' · SỐ HIỆU ' . esc_html( $doc_number ) : ''; ?></p>
				<p class="ld-eyebrow"><span class="ld-dot" aria-hidden="true"></span>Văn bản pháp luật<?php echo '' !== $doc_type ? ' · ' . esc_html( $doc_type ) : ''; ?></p>
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( '' !== $summary && false === mb_stripos( $title, $summary ) ) : ?>
					<p class="ld-hero-sub"><?php echo esc_html( $summary ); ?></p>
				<?php endif; ?>
			</div>
			<div class="ld-pills">
				<span class="ld-status <?php echo esc_attr( $st_class ); ?>"><?php echo esc_html( ( 'is-ok' === $st_class ? '● ' : '◷ ' ) . (string) $validity['label'] ); ?></span>
				<span class="ld-status <?php echo $is_verified ? 'is-ok' : 'is-warn'; ?>"><?php echo $is_verified ? '✓ Đã đối chiếu nguồn chính thức' : '◷ Cần xác minh dữ liệu nguồn'; ?></span>
				<?php if ( $amended_by ) : ?><span class="ld-status is-warn">✎ Đã được sửa đổi, bổ sung</span><?php endif; ?>
			</div>
		</div>
		<div class="ld-hero-actions">
			<?php if ( $file && ! empty( $file['download_url'] ) ) : ?>
				<a class="ld-btn is-primary" href="<?php echo esc_url( (string) $file['download_url'] ); ?>" rel="nofollow">▣ Tải văn bản gốc (<?php echo esc_html( $file_label ); ?>)</a>
			<?php endif; ?>
			<?php if ( '' !== $source_url ) : ?>
				<a class="ld-btn" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">↗ Nguồn chính thức</a>
			<?php endif; ?>
			<?php if ( $has_text ) : ?><a class="ld-btn" href="#noi-dung">▤ Đọc toàn văn<?php echo $articles ? ' (' . count( $articles ) . ' Điều)' : ''; ?></a><?php endif; ?>
			<a class="ld-btn" href="#on-thi">✓ Ôn thi theo văn bản</a>
			<button type="button" class="ld-btn is-gold<?php echo $saved_doc ? ' is-saved' : ''; ?>" data-ld-save="legal_document" data-id="<?php echo (int) $doc_id; ?>" aria-pressed="<?php echo $saved_doc ? 'true' : 'false'; ?>"><?php echo $saved_doc ? '★ Đã lưu văn bản' : '☆ Lưu văn bản'; ?></button>
		</div>
	</section>

	<?php if ( $replaced_by && ! empty( $replaced_by['slug'] ) ) : ?>
		<div class="ld-alert is-bad" role="note"><b>Văn bản này đã được thay thế</b> bởi <a href="<?php echo esc_url( cvc_legal_document_url( (string) $replaced_by['slug'] ) ); ?>"><?php echo esc_html( trim( ( $replaced_by['document_number'] ?? '' ) . ' — ' . ( $replaced_by['title'] ?? '' ), ' —' ) ); ?></a>. Không dùng để ôn thi theo quy định hiện hành.</div>
	<?php endif; ?>
	<?php if ( $amended_by ) : ?>
		<div class="ld-alert" role="note"><b>Một số Điều đã được sửa đổi, bổ sung bởi:</b>
			<?php foreach ( $amended_by as $i => $am ) : ?><?php if ( empty( $am['slug'] ) ) { continue; } ?><?php echo $i ? '; ' : ' '; ?><a href="<?php echo esc_url( cvc_legal_document_url( (string) $am['slug'] ) ); ?>"><?php echo esc_html( (string) ( $am['document_number'] ?? $am['title'] ?? '' ) ); ?></a><?php echo ! empty( $am['effective_date'] ) ? ' (hiệu lực ' . esc_html( cvc_format_date_vn( (string) $am['effective_date'] ) ) . ')' : ''; ?><?php endforeach; ?>.
			Toàn văn dưới đây là bản gốc khi ban hành — đối chiếu văn bản sửa đổi khi ôn thi.</div>
	<?php endif; ?>

	<!-- ============ ĐIỂM XÁC MINH ============ -->
	<section class="ld-scorestrip" aria-label="Điểm xác minh">
		<div class="ld-scorecard <?php echo esc_attr( $vclass ); ?>">
			<div class="ld-ring" style="--p:<?php echo (int) ( $ver['score'] ?? 0 ); ?>"><strong><?php echo null !== $ver ? (int) $ver['score'] : '—'; ?></strong></div>
			<div><strong>Điểm xác minh</strong><small><?php echo esc_html( (string) ( $ver['label'] ?? 'Chưa tính điểm' ) ); ?></small><small class="ld-teal">Không phải xác suất đúng</small></div>
		</div>
		<div class="ld-scoreexplain <?php echo esc_attr( $vclass ); ?>">
			<div class="ld-scorehead"><strong>Mức độ truy vết bằng chứng</strong><span><?php echo null !== $ver ? (int) $ver['score'] . '/100 · ' . esc_html( (string) $ver['label'] ) : '—'; ?></span></div>
			<div class="ld-bar"><span style="width:<?php echo (int) ( $ver['score'] ?? 0 ); ?>%"></span></div>
			<p><?php echo esc_html( $vcopy ); ?> Điểm tính từ nguồn ban hành, tệp gốc, thông tin ban hành – hiệu lực và mức số hóa toàn văn<?php echo $is_ocr ? ' (toàn văn số hóa từ bản quét, có thể còn lỗi chính tả)' : ''; ?>.</p>
		</div>
		<div class="ld-quickstat">
			<span class="ld-quickico" aria-hidden="true">◷</span>
			<div><strong><?php echo esc_html( ! empty( $document['effective_date'] ) ? cvc_format_date_vn( (string) $document['effective_date'] ) : 'Chưa cập nhật' ); ?></strong><small>Ngày có hiệu lực<?php echo ! empty( $document['issued_date'] ) ? ' · ban hành ' . esc_html( cvc_format_date_vn( (string) $document['issued_date'] ) ) : ''; ?></small></div>
		</div>
	</section>

	<div class="ld-actionbar">
		<nav class="ld-jumpnav" aria-label="Đi tới">
			<a href="#tong-quan">Tổng quan</a>
			<?php if ( $has_text ) : ?><a href="#noi-dung">Nội dung</a><?php endif; ?>
			<?php if ( $file ) : ?><a href="#ban-goc">PDF gốc</a><?php endif; ?>
			<a href="#on-thi">Ôn thi</a>
			<a href="#lien-quan">Văn bản liên quan</a>
		</nav>
		<div class="ld-actiontools">
			<button type="button" class="ld-btn is-sm" data-ld-print>⎙ In trang</button>
			<button type="button" class="ld-btn is-sm" data-ld-share>↗ Chia sẻ</button>
		</div>
	</div>

	<div class="ld-layout">
		<aside class="ld-side">
			<section class="ld-panel" id="tong-quan">
				<div class="ld-panel-head">Thông tin văn bản <span aria-hidden="true">▤</span></div>
				<div class="ld-panel-body">
					<dl class="ld-info">
						<?php
						$rows = array(
							'Số hiệu'        => $doc_number,
							'Loại văn bản'   => $doc_type,
							'Cơ quan'        => $agency,
							'Ngày ban hành'  => cvc_format_date_vn( $document['issued_date'] ?? null ),
							'Ngày hiệu lực'  => cvc_format_date_vn( $document['effective_date'] ?? null ),
							'Hết hiệu lực'   => cvc_format_date_vn( $document['expiry_date'] ?? null ),
						);
						foreach ( $rows as $lbl => $val ) :
							if ( '' === (string) $val ) {
								continue;
							}
							?>
							<div><dt><?php echo esc_html( $lbl ); ?></dt><dd><?php echo esc_html( (string) $val ); ?></dd></div>
						<?php endforeach; ?>
						<div><dt>Tình trạng</dt><dd class="<?php echo esc_attr( $st_class ); ?>"><?php echo esc_html( (string) $validity['label'] ); ?></dd></div>
						<div><dt>Nguồn</dt><dd><?php echo '' !== $host ? '<a href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $host ) . '</a>' : 'Chưa xác nhận URL'; ?></dd></div>
						<?php if ( '' !== $checked_at ) : ?><div><dt>Kiểm tra nguồn</dt><dd><?php echo esc_html( wp_date( 'd/m/Y', strtotime( $checked_at ) ) ); ?></dd></div><?php endif; ?>
						<?php if ( $has_text ) : ?><div><dt>Toàn văn</dt><dd><?php echo $is_ocr ? 'Số hóa từ bản quét' : 'Bản điện tử chính thức'; ?></dd></div><?php endif; ?>
					</dl>
					<p class="ld-sidenote">Tình trạng tính theo ngày hiệu lực / hết hiệu lực và văn bản thay thế đã ghi nhận. Luôn đối chiếu văn bản gốc trước khi sử dụng.</p>
				</div>
			</section>

			<?php if ( $articles ) : ?>
				<nav class="ld-panel ld-tocpanel" aria-label="Mục lục văn bản">
					<div class="ld-panel-head">Mục lục văn bản <button type="button" class="ld-btn is-sm" data-ld-toc aria-expanded="true">⌄</button></div>
					<ol class="ld-toc" id="ld-toc">
						<?php $toc_ch = null; ?>
						<?php foreach ( $articles as $a ) : ?>
							<?php if ( ! empty( $a['chapter'] ) && $a['chapter'] !== $toc_ch ) : $toc_ch = $a['chapter']; ?>
								<li class="ld-toc-ch"><?php echo esc_html( (string) $a['chapter'] ); ?></li>
							<?php endif; ?>
							<?php list( $t_title ) = $article_split( $a ); ?>
							<li><a href="#<?php echo esc_attr( $article_anchor( $a ) ); ?>"><?php if ( ! $is_appendix( $a ) ) : ?><span>Điều <?php echo esc_html( (string) $a['number'] ); ?>.</span> <?php echo esc_html( $t_title ); ?><?php else : ?><?php echo esc_html( $t_title ?: (string) $a['number'] ); ?><?php endif; ?></a></li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>

			<section class="ld-panel">
				<div class="ld-panel-head">Tác vụ nhanh</div>
				<div class="ld-panel-body ld-quick">
					<?php if ( '' !== $doc_number ) : ?><button type="button" class="ld-btn is-sm" data-ld-copy="<?php echo esc_attr( $doc_number ); ?>">▣ Sao chép số hiệu</button><?php endif; ?>
					<button type="button" class="ld-btn is-sm" data-ld-copy="<?php echo esc_attr( $doc_url ); ?>">⧉ Sao chép liên kết</button>
					<?php if ( '' !== $agency ) : ?><a class="ld-btn is-sm" href="<?php echo esc_url( add_query_arg( 'co-quan', rawurlencode( $agency ), cvc_legal_documents_url() ) ); ?>">§ Văn bản cùng cơ quan</a><?php endif; ?>
				</div>
			</section>
		</aside>

		<div class="ld-content">
			<?php if ( $articles ) : ?>
				<div class="ld-toolbar" role="search">
					<label class="ld-toolbar-label" for="ld-find">Tra cứu trong văn bản</label>
					<input id="ld-find" type="search" placeholder="Tìm điều, khoản, từ khóa…" autocomplete="off">
					<button type="button" class="ld-btn is-teal is-sm" data-ld-find>Tìm</button>
					<button type="button" class="ld-btn is-sm" data-ld-find-next hidden>Kết quả tiếp ↓</button>
					<button type="button" class="ld-btn is-sm" data-ld-find-clear>Xóa</button>
					<span class="ld-findcount" id="ld-findcount" aria-live="polite">Tìm nhanh toàn văn · <?php echo count( $articles ); ?> Điều</span>
				</div>

				<section class="ld-section" id="ld-related" hidden aria-live="polite">
					<div class="ld-section-title"><div><h2 id="ld-related-title">Nội dung liên quan</h2><p id="ld-related-sub"></p></div><button type="button" class="ld-btn is-sm" data-ld-related-close>✕ Đóng</button></div>
					<div class="ld-section-body" id="ld-related-body"></div>
				</section>
			<?php endif; ?>

			<section class="ld-section" id="noi-dung">
				<div class="ld-section-title">
					<div><h2>Toàn văn &amp; nội dung có cấu trúc</h2><p>Đọc theo điều khoản · tìm kiếm nhanh · dẫn chiếu về văn bản gốc</p></div>
					<?php if ( $has_text ) : ?><div class="ld-texttools"><button type="button" data-ld-font="1" aria-label="Tăng cỡ chữ">A+</button><button type="button" data-ld-font="-1" aria-label="Giảm cỡ chữ">A−</button><?php if ( $articles ) : ?><button type="button" data-ld-compact aria-pressed="false">↕ Thu gọn</button><?php endif; ?></div><?php endif; ?>
				</div>
				<div class="ld-section-body" id="ld-text">
					<?php if ( ! $has_text ) : ?>
						<p class="ld-muted">Chưa có toàn văn đã xác minh cho văn bản này.
							<?php echo '' !== $source_url ? 'Xem tại <a href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener nofollow">nguồn chính thức</a>.' : 'Hãy tra cứu trên Công báo Chính phủ (congbao.chinhphu.vn) hoặc Cơ sở dữ liệu quốc gia về văn bản pháp luật.'; ?></p>
						<?php if ( '' !== $summary ) : ?><div class="ld-callout"><b>Trích yếu:</b> <?php echo esc_html( $summary ); ?></div><?php endif; ?>
					<?php else : ?>
						<div class="ld-callout">ⓘ <b>Thông tin nguồn:</b> nội dung lấy từ <?php echo $is_ocr ? 'bản quét chính thức và được số hóa tự động (OCR) — có thể còn lỗi chính tả, dấu câu' : 'văn bản điện tử chính thức'; ?>. Văn bản có giá trị pháp lý là bản đăng trên Công báo / cổng thông tin của cơ quan ban hành<?php echo $file ? ' — đối chiếu bằng tệp gốc ở mục <a href="#ban-goc">Văn bản gốc</a>' : ''; ?>.</div>
						<?php if ( $articles ) : ?>
							<div id="ld-articles">
								<?php $cur_ch = null; $cur_sec = null; ?>
								<?php foreach ( $articles as $a ) : ?>
									<?php if ( ! empty( $a['chapter'] ) && $a['chapter'] !== $cur_ch ) : $cur_ch = $a['chapter']; $cur_sec = null; ?>
										<h3 class="ld-chapter"><?php echo esc_html( (string) $a['chapter'] ); ?></h3>
									<?php endif; ?>
									<?php if ( ! empty( $a['section'] ) && $a['section'] !== $cur_sec ) : $cur_sec = $a['section']; ?>
										<h4 class="ld-sectionhead"><?php echo esc_html( (string) $a['section'] ); ?></h4>
									<?php endif; ?>
									<?php
									list( $a_title, $a_content ) = $article_split( $a );
									$a['title'] = $a_title;
									$aid        = (int) ( $a['id'] ?? 0 );
									$anum       = (string) ( $a['number'] ?? '' );
									$nq         = $q_by_art[ $aid ] ?? 0;
									$nk         = count( $ki_by_art[ $aid ] ?? array() );
									$nr         = count( $rec_by_num[ $anum ] ?? array() );
									$saved      = in_array( $aid, $saved_arts, true );
									?>
									<article class="ld-article" id="<?php echo esc_attr( $article_anchor( $a ) ); ?>" data-id="<?php echo (int) $aid; ?>" data-num="<?php echo esc_attr( $anum ); ?>">
										<h3><?php if ( ! $is_appendix( $a ) ) : ?><span class="ld-num">ĐIỀU <?php echo esc_html( $anum ); ?></span> <?php echo esc_html( $a_title ); ?><?php else : ?><span class="ld-num">PHỤ LỤC</span> <?php echo esc_html( $a_title ?: $anum ); ?><?php endif; ?></h3>
										<div class="ld-art-body"><?php echo esc_html( $legal_reflow( $a_content ) ); ?></div>
										<div class="ld-texttools ld-arttools">
											<?php if ( $is_pdf ) : ?><button type="button" data-ld-pdf="<?php echo isset( $page_by_num[ $anum ] ) ? (int) $page_by_num[ $anum ] : ''; ?>">↗ Xem tại PDF<?php echo isset( $page_by_num[ $anum ] ) ? ' (tr. ' . (int) $page_by_num[ $anum ] . ')' : ''; ?></button><?php endif; ?>
											<?php if ( $nq || $nk ) : ?><button type="button" data-ld-rel="q">? Câu hỏi liên quan<?php echo $nq ? ' (' . (int) $nq . ')' : ''; ?></button><?php endif; ?>
											<?php if ( $nr ) : ?><button type="button" data-ld-rel="r">▣ Tuyển dụng liên quan (<?php echo (int) $nr; ?>)</button><?php endif; ?>
											<button type="button" class="<?php echo $saved ? 'is-saved' : ''; ?>" data-ld-save="legal_article" data-id="<?php echo (int) $aid; ?>" aria-pressed="<?php echo $saved ? 'true' : 'false'; ?>"><?php echo $saved ? '★ Đã lưu' : '☆ Lưu đánh dấu'; ?></button>
											<button type="button" data-ld-copyart>▣ Sao chép điều</button>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
							<p class="ld-empty" id="ld-find-empty" hidden>Không có Điều nào chứa từ khóa này.</p>
						<?php else : ?>
							<div class="ld-article"><div class="ld-art-body"><?php echo esc_html( $legal_reflow( $full_text ) ); ?></div></div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( $file ) : ?>
				<section class="ld-section" id="ban-goc">
					<div class="ld-section-title"><div><h2>Văn bản gốc</h2><p>Giữ nguyên bố cục, dấu, chữ ký và phụ lục từ tệp chính thức · <?php echo esc_html( $file_label ); ?></p></div>
						<p class="ld-actions"><?php if ( $is_pdf ) : ?><a class="ld-btn is-primary is-sm" href="<?php echo esc_url( $pdf_inline ); ?>" target="_blank" rel="noopener nofollow">Mở PDF toàn màn hình ↗</a><?php endif; ?><a class="ld-btn is-sm" href="<?php echo esc_url( (string) $file['download_url'] ); ?>" rel="nofollow">Tải tệp</a></p></div>
					<div class="ld-viewer">
						<?php if ( $is_pdf ) : ?>
							<iframe id="ld-pdf" class="ld-pdf" title="<?php echo esc_attr( 'Văn bản gốc ' . $doc_number ); ?>" data-src="<?php echo esc_url( $pdf_inline ); ?>" loading="lazy"></iframe>
							<p class="ld-muted ld-small">Khung xem dùng trình đọc PDF của trình duyệt. Không hiển thị? <a href="<?php echo esc_url( $pdf_inline ); ?>" target="_blank" rel="noopener nofollow">Mở PDF trong thẻ mới</a>.</p>
						<?php else : ?>
							<p class="ld-muted">Tệp gốc dạng <?php echo esc_html( strtoupper( (string) ( $file['extension'] ?? '' ) ) ); ?> chưa xem trước được trên trang — <a href="<?php echo esc_url( (string) $file['download_url'] ); ?>" rel="nofollow">tải tệp</a> để mở.</p>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

			<section class="ld-section" id="on-thi">
				<div class="ld-section-title"><div><h2>Ôn thi theo văn bản này</h2><p>Biến nội dung pháp luật thành kế hoạch học và kiểm tra kiến thức</p></div><?php if ( $q_count ) : ?><span class="ld-status is-ok"><?php echo esc_html( number_format_i18n( $q_count ) ); ?> câu hỏi trong ngân hàng đề</span><?php endif; ?></div>
				<div class="ld-section-body">
					<?php
					$art_label_by_id = array();
					foreach ( $articles as $a ) {
						$art_label_by_id[ (int) ( $a['id'] ?? 0 ) ] = $is_appendix( $a ) ? 'Phụ lục' : 'Điều ' . $a['number'];
					}
					$cards = array_slice( array_values( array_filter( $knowledge, static fn ( $k ) => ! empty( $k['slug'] ) ) ), 0, 5 );
					?>
					<?php if ( $cards ) : ?>
						<div class="ld-quizgrid">
							<?php foreach ( $cards as $k ) : ?>
								<?php $al = ! empty( $k['article_id'] ) ? ( $art_label_by_id[ (int) $k['article_id'] ] ?? '' ) : ''; ?>
								<div class="ld-quiz">
									<strong><?php echo esc_html( ( '' !== $al && 0 !== mb_stripos( (string) $k['title'], $al ) ? $al . ' · ' : '' ) . (string) $k['title'] ); ?></strong>
									<?php if ( ! empty( $k['summary'] ) ) : ?><p><?php echo esc_html( mb_strlen( (string) $k['summary'] ) > 160 ? mb_substr( (string) $k['summary'], 0, 158 ) . '…' : (string) $k['summary'] ); ?></p><?php endif; ?>
									<a class="ld-btn is-sm" href="<?php echo esc_url( cvc_knowledge_item_url( (string) $k['slug'] ) ); ?>">Học điều khoản ↗</a>
								</div>
							<?php endforeach; ?>
							<div class="ld-quiz is-cta">
								<strong>Kiểm tra nhanh toàn văn</strong>
								<p><?php echo $practice ? 'Bộ ' . (int) $practice['total_questions'] . ' câu trắc nghiệm tạo từ nội dung văn bản đã đối chiếu nguồn, có đáp án và dẫn chiếu Điều.' : 'Luyện đề thi thử có câu hỏi về văn bản này.'; ?></p>
								<a class="ld-btn is-primary is-sm" href="<?php echo esc_url( $practice && ! empty( $practice['slug'] ) ? cvc_exam_url( (string) $practice['slug'] ) : cvc_exams_url() ); ?>"><?php echo $practice ? 'Bắt đầu trắc nghiệm ↗' : 'Làm đề thi thử ↗'; ?></a>
							</div>
						</div>
					<?php else : ?>
						<p class="ld-muted">Chưa có bài học nào gắn với văn bản này.</p>
						<p class="ld-actions"><a class="ld-btn is-primary is-sm" href="<?php echo esc_url( $practice && ! empty( $practice['slug'] ) ? cvc_exam_url( (string) $practice['slug'] ) : cvc_exams_url() ); ?>"><?php echo $practice ? 'Luyện ' . (int) $practice['total_questions'] . ' câu theo văn bản ↗' : 'Làm đề thi thử ↗'; ?></a></p>
					<?php endif; ?>
					<?php if ( count( $knowledge ) > count( $cards ) && ! empty( $knowledge[0]['topic']['slug'] ) ) : ?>
						<p class="ld-more"><a href="<?php echo esc_url( cvc_topic_url( (string) $knowledge[0]['topic']['slug'] ) ); ?>">Xem đủ <?php echo count( $knowledge ); ?> bài học theo Điều của văn bản →</a></p>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( function_exists( 'cvc_pdf_render_legal_block' ) ) { cvc_pdf_render_legal_block( $doc_id, '' !== $doc_number ? $doc_number : $title ); } // Bộ đề PDF (27/10/2026) ?>
			<section class="ld-section" id="lien-quan">
				<div class="ld-section-title"><div><h2>Văn bản &amp; tài liệu liên quan</h2><p>Kết nối theo văn bản sửa đổi – thay thế, căn cứ pháp lý của tin tuyển dụng và nội dung ôn thi</p></div></div>
				<div class="ld-section-body">
					<div class="ld-relgrid">
						<article class="ld-rel">
							<span class="ld-ricon" aria-hidden="true">§</span>
							<h3>Văn bản liên quan</h3>
							<?php
							$links = array();
							if ( $replaced_by && ! empty( $replaced_by['slug'] ) ) {
								$links[] = array( 'Thay thế bởi', $replaced_by );
							}
							foreach ( $amended_by as $am ) {
								$links[] = array( 'Sửa đổi, bổ sung', $am );
							}
							foreach ( $replaces as $od ) {
								$links[] = array( 'Văn bản này thay thế', $od );
							}
							?>
							<?php if ( $links ) : ?>
								<ul class="ld-rellist"><?php foreach ( array_slice( $links, 0, 5 ) as $l ) : ?><?php if ( empty( $l[1]['slug'] ) ) { continue; } ?><li><small><?php echo esc_html( $l[0] ); ?></small><a href="<?php echo esc_url( cvc_legal_document_url( (string) $l[1]['slug'] ) ); ?>"><?php $ln = (string) ( $l[1]['document_number'] ?? '' ); $lt = (string) ( $l[1]['title'] ?? '' ); echo esc_html( '' !== $ln && false === mb_strpos( $lt, $ln ) ? trim( $ln . ' ' . $lt ) : ( '' !== $lt ? $lt : $ln ) ); ?></a></li><?php endforeach; ?></ul>
							<?php else : ?>
								<p>Chưa ghi nhận văn bản sửa đổi, thay thế. Xem các văn bản khác của cùng cơ quan ban hành.</p>
							<?php endif; ?>
							<a class="ld-rellink" href="<?php echo esc_url( '' !== $agency ? add_query_arg( 'co-quan', rawurlencode( $agency ), cvc_legal_documents_url() ) : cvc_legal_documents_url() ); ?>">Văn bản cùng cơ quan →</a>
						</article>
						<article class="ld-rel">
							<span class="ld-ricon" aria-hidden="true">▣</span>
							<h3>Cơ hội tuyển dụng</h3>
							<?php if ( $recs ) : ?>
								<ul class="ld-rellist"><?php foreach ( array_slice( $recs, 0, 4 ) as $r ) : ?><li><small><?php echo 'published' === $r['status'] ? 'Đang đăng' : 'Đã hết hạn'; ?> · lấy văn bản làm căn cứ</small><a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( (string) $r['title'] ); ?></a></li><?php endforeach; ?></ul>
							<?php else : ?>
								<p>Chưa có tin tuyển dụng nào dẫn chiếu văn bản này. Tra cứu cơ hội công chức, viên chức đang nhận hồ sơ.</p>
							<?php endif; ?>
							<a class="ld-rellink" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tìm cơ hội tuyển dụng →</a>
						</article>
						<article class="ld-rel">
							<span class="ld-ricon" aria-hidden="true">▤</span>
							<h3>Tài liệu học tập</h3>
							<p><?php echo $knowledge ? esc_html( count( $knowledge ) . ' bài học tóm tắt theo Điều' . ( $q_count ? ' và ' . number_format_i18n( $q_count ) . ' câu hỏi ôn tập' : '' ) . ' gắn với văn bản.' ) : 'Đề cương, ghi chú, tài liệu ôn tập có nguồn và phiên bản rõ ràng.'; ?></p>
							<a class="ld-rellink" href="<?php echo esc_url( ! empty( $knowledge[0]['topic']['slug'] ) ? cvc_topic_url( (string) $knowledge[0]['topic']['slug'] ) : cvc_documents_url() ); ?>"><?php echo ! empty( $knowledge[0]['topic']['slug'] ) ? 'Mở chủ đề ôn thi →' : 'Mở thư viện tài liệu →'; ?></a>
						</article>
					</div>
				</div>
			</section>

			<p class="ld-disclaimer">Văn bản có giá trị pháp lý là bản do cơ quan có thẩm quyền ban hành (đăng Công báo / cổng thông tin chính thức). Điểm xác minh phản ánh mức độ đầy đủ và truy vết được của bằng chứng, không phải xác nhận pháp lý. Luôn kiểm tra văn bản sửa đổi, thay thế mới nhất trước khi áp dụng.</p>
		</div>
	</div>
</div>
<div class="ld-toast" id="ld-toast" role="status" aria-live="polite"></div>
</main>
<?php
get_footer();
