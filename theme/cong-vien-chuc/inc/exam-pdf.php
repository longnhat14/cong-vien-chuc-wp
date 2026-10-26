<?php
/**
 * Tải đề trắc nghiệm PDF có thương hiệu (26/10/2026).
 *
 * - Nút "Tải PDF" (đề lẻ) / "Tải trọn bộ đề PDF" (bộ đề cá nhân hóa của tin, theo nhóm vị trí).
 * - Bấm nút -> hộp xem trước (không cần đăng nhập), chọn "Đề thi" / "Đề + đáp án và giải thích".
 * - "Tải về": cần đăng nhập -> API tạo link tải 10 phút -> trình duyệt tải thẳng từ api.congvienchuc.com.
 * API: GET /api/{exams|recruitments}/{id}/pdf/preview, POST .../pdf/link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $kind 'exam' | 'recruitment'
 * @param array  $args group, label, class, variant ('de'|'dap-an'), title
 */
function cvc_pdf_button( string $kind, int $id, array $args = array() ): string {
	if ( $id <= 0 || ! in_array( $kind, array( 'exam', 'recruitment' ), true ) ) {
		return '';
	}
	cvc_pdf_assets();
	$label = (string) ( $args['label'] ?? ( 'recruitment' === $kind ? 'Tải trọn bộ đề PDF' : 'Tải đề PDF' ) );

	return sprintf(
		'<button type="button" class="cvc-pdf-btn %1$s" data-cvc-pdf="%2$s" data-id="%3$d" data-group="%4$s" data-variant="%5$s" data-title="%6$s"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg><span>%7$s</span></button>',
		esc_attr( (string) ( $args['class'] ?? '' ) ),
		esc_attr( $kind ),
		$id,
		esc_attr( (string) ( $args['group'] ?? '' ) ),
		esc_attr( 'dap-an' === ( $args['variant'] ?? '' ) ? 'dap-an' : 'de' ),
		esc_attr( (string) ( $args['title'] ?? '' ) ),
		esc_html( $label )
	);
}

function cvc_pdf_assets(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$css  = get_theme_file_path( '/assets/css/exam-pdf.css' );
	$js   = get_theme_file_path( '/assets/js/exam-pdf.js' );
	wp_enqueue_style( 'cvc-exam-pdf', get_theme_file_uri( '/assets/css/exam-pdf.css' ), array(), (string) @filemtime( $css ) );
	wp_enqueue_script( 'cvc-exam-pdf', get_theme_file_uri( '/assets/js/exam-pdf.js' ), array(), (string) @filemtime( $js ), true );
	$current = home_url( add_query_arg( array() ) );
	wp_add_inline_script(
		'cvc-exam-pdf',
		'window.CVC_PDF=' . wp_json_encode(
			array(
				'ajax'   => admin_url( 'admin-ajax.php' ),
				'nonce'  => wp_create_nonce( 'cvc_pdf' ),
				'logged' => null !== cvc_auth_token(),
				'login'  => cvc_login_url( $current ),
			),
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
		) . ';',
		'before'
	);
	add_action( 'wp_footer', 'cvc_pdf_modal', 5 );
}

function cvc_pdf_modal(): void {
	?>
	<div class="cvc-pdf-modal" id="cvcPdfModal" hidden role="dialog" aria-modal="true" aria-labelledby="cvcPdfTitle">
		<div class="cvc-pdf-backdrop" data-close></div>
		<div class="cvc-pdf-dialog">
			<header class="cvc-pdf-top">
				<div><p class="cvc-pdf-kicker">Tải đề trắc nghiệm PDF · miễn phí</p><h2 id="cvcPdfTitle">Xem trước</h2><p class="cvc-pdf-sub" id="cvcPdfSub"></p></div>
				<button type="button" class="cvc-pdf-x" data-close aria-label="Đóng">&times;</button>
			</header>
			<div class="cvc-pdf-variants" id="cvcPdfVariants" role="radiogroup" aria-label="Chọn loại file"></div>
			<div class="cvc-pdf-body" id="cvcPdfBody"><p class="cvc-pdf-loading">Đang tải bản xem trước…</p></div>
			<footer class="cvc-pdf-actions">
				<p class="cvc-pdf-note" id="cvcPdfNote">File có logo Công Viên Chức, chữ chìm congvienchuc.com và mã QR để làm bài trực tuyến.</p>
				<button type="button" class="cvc-pdf-go" id="cvcPdfGo">Tải về</button>
			</footer>
		</div>
	</div>
	<?php
}

/* ------------------------------------------------------------------
 * AJAX
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_pdf_preview', 'cvc_pdf_ajax_preview' );
add_action( 'wp_ajax_nopriv_cvc_pdf_preview', 'cvc_pdf_ajax_preview' );
add_action( 'wp_ajax_cvc_pdf_link', 'cvc_pdf_ajax_link' );
add_action( 'wp_ajax_nopriv_cvc_pdf_link', 'cvc_pdf_ajax_link' );

/** @return array{0: string, 1: int, 2: string, 3: string} */
function cvc_pdf_ajax_input(): array {
	check_ajax_referer( 'cvc_pdf', 'nonce' );
	$kind    = 'recruitment' === ( $_POST['kind'] ?? '' ) ? 'recruitment' : 'exam';
	$id      = absint( $_POST['id'] ?? 0 );
	$group   = preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) wp_unslash( $_POST['group'] ?? '' ) ) );
	$variant = 'dap-an' === ( $_POST['variant'] ?? '' ) ? 'dap-an' : 'de';
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => 'Thiếu mã đề.' ), 422 );
	}

	return array( $kind, $id, (string) $group, $variant );
}

function cvc_pdf_ajax_preview(): void {
	[ $kind, $id, $group, $variant ] = cvc_pdf_ajax_input();
	$path   = '/api/' . ( 'recruitment' === $kind ? 'recruitments' : 'exams' ) . '/' . $id . '/pdf/preview';
	$query  = array_filter( array( 'variant' => $variant, 'group' => $group ) );
	$result = ( new CVC_Api_Client( null, 20 ) )->get( $path, $query, cvc_auth_token() );
	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ) ), (int) ( $result['status'] ?? 0 ) ?: 500 );
	}
	wp_send_json_success( (array) ( $result['data']['data'] ?? array() ) );
}

function cvc_pdf_ajax_link(): void {
	[ $kind, $id, $group, $variant ] = cvc_pdf_ajax_input();
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Vui lòng đăng nhập để tải file.', 'login' => true ), 401 );
	}
	$path   = '/api/' . ( 'recruitment' === $kind ? 'recruitments' : 'exams' ) . '/' . $id . '/pdf/link';
	$result = ( new CVC_Api_Client( null, 20 ) )->post( $path, array_filter( array( 'variant' => $variant, 'group' => $group ) ), $token );
	if ( ! $result['ok'] ) {
		$status = (int) ( $result['status'] ?? 0 ) ?: 500;
		wp_send_json_error( array( 'message' => cvc_api_error_message( $result ), 'login' => 401 === $status ), $status );
	}
	$data = (array) ( $result['data']['data'] ?? array() );
	$url  = (string) ( $data['url'] ?? '' );
	$base = rtrim( (string) cvc_api_base_url(), '/' );
	if ( ! preg_match( '#/api/exam-pdf/[A-Za-z0-9]{48}$#', $url ) || 0 !== strpos( $url, $base . '/api/exam-pdf/' ) ) {
		wp_send_json_error( array( 'message' => 'Không tạo được liên kết tải.' ), 500 );
	}
	wp_send_json_success( array( 'url' => $url, 'code' => (string) ( $data['code'] ?? '' ), 'remaining' => $data['remaining_today'] ?? null ) );
}
