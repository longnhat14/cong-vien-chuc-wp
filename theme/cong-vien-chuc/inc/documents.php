<?php
/**
 * Tài liệu (Document model, Phase 11 monetization) - proxy tải file thật
 * (đính kèm Bearer token từ cookie HttpOnly, browser không tự làm được
 * qua thẻ <a> thường) + xử lý mua tài liệu trả phí qua VNPay.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'cvc_handle_document_download_proxy' );

/**
 * KHÔNG dùng CVC_Api_Client ở đây - client đó luôn json_decode() body,
 * sẽ vỡ với file nhị phân (PDF/DOCX). Gọi thẳng wp_remote_get() + forward
 * nguyên Content-Type/Content-Disposition/body.
 */
function cvc_handle_document_download_proxy(): void {
	if ( 'document-download' !== get_query_var( 'cvc_page' ) ) {
		return;
	}

	$slug = sanitize_text_field( (string) get_query_var( 'cvc_document_slug' ) );

	if ( '' === $slug ) {
		status_header( 404 );
		exit( 'Không tìm thấy tài liệu.' );
	}

	$token   = cvc_auth_token();
	$url     = rtrim( cvc_api_base_url(), '/' ) . '/api/documents/' . rawurlencode( $slug ) . '/download';
	$headers = array( 'Accept' => '*/*' );

	if ( null !== $token && '' !== $token ) {
		$headers['Authorization'] = 'Bearer ' . $token;
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
			'headers' => $headers,
		)
	);

	if ( is_wp_error( $response ) ) {
		status_header( 502 );
		exit( 'Không thể kết nối máy chủ tài liệu, vui lòng thử lại sau.' );
	}

	$status = (int) wp_remote_retrieve_response_code( $response );

	if ( $status < 200 || $status >= 300 ) {
		if ( 401 === $status ) {
			wp_safe_redirect( cvc_login_url( home_url( '/tai-lieu/' . rawurlencode( $slug ) . '/' ) ) );
			exit;
		}

		if ( 402 === $status ) {
			cvc_redirect_with_notice( cvc_document_url( $slug ), 'error', 'Bạn cần mua tài liệu này trước khi tải về.', null );
			exit;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		$message = ( is_array( $decoded ) && isset( $decoded['message'] ) )
			? (string) $decoded['message']
			: 'Không thể tải tài liệu này.';

		cvc_redirect_with_notice( cvc_document_url( $slug ), 'error', $message, null );
		exit;
	}

	$content_type = wp_remote_retrieve_header( $response, 'content-type' );
	$disposition  = wp_remote_retrieve_header( $response, 'content-disposition' );
	$length       = wp_remote_retrieve_header( $response, 'content-length' );

	nocache_headers();
	header( 'Content-Type: ' . ( $content_type ?: 'application/octet-stream' ) );
	header( 'Content-Disposition: ' . ( $disposition ?: ( 'attachment; filename="' . $slug . '"' ) ) );

	if ( $length ) {
		header( 'Content-Length: ' . $length );
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- passthrough nhị phân (PDF/DOCX...), không phải HTML.
	echo wp_remote_retrieve_body( $response );
	exit;
}

/*
 * ============================================================
 * MUA TÀI LIỆU TRẢ PHÍ (VNPay) - Post/Redirect/Get: tạo Order qua
 * Laravel (POST /api/orders), nhận lại payment_url rồi redirect thẳng
 * sang cổng VNPay. Backend tự kiểm tra giá/sở hữu trùng - WP không lặp
 * lại business rule (Phần XV "backend luôn là authority").
 * ============================================================
 */
add_action( 'admin_post_nopriv_cvc_document_buy', 'cvc_handle_document_buy' );
add_action( 'admin_post_cvc_document_buy', 'cvc_handle_document_buy' );

function cvc_handle_document_buy(): void {
	check_admin_referer( 'cvc_document_buy' );
	$token = cvc_require_token_or_die();

	$documentId = absint( $_POST['document_id'] ?? 0 );
	$slug       = sanitize_text_field( wp_unslash( $_POST['document_slug'] ?? '' ) );
	$fallback   = '' !== $slug ? cvc_document_url( $slug ) : cvc_documents_url();

	if ( 0 === $documentId ) {
		cvc_redirect_with_notice( $fallback, 'error', 'Tài liệu không hợp lệ.', null );
		return;
	}

	$result = ( new CVC_Order_Service() )->create(
		array( array( 'type' => 'document', 'id' => $documentId ) ),
		$token
	);

	if ( ! $result['ok'] ) {
		// Laravel trả message tiếng Việt rõ ràng cho các lỗi mua hàng
		// (đã sở hữu, giá không hợp lệ, VNPay chưa cấu hình...) - ưu
		// tiên dùng nguyên văn thay vì nhãn chung của cvc_api_error_message().
		$message = ! empty( $result['error'] ) ? (string) $result['error'] : cvc_api_error_message( $result );
		cvc_redirect_with_notice( $fallback, 'error', $message, null );
		return;
	}

	$payment_url = $result['data']['data']['payment_url'] ?? null;

	if ( ! $payment_url ) {
		cvc_redirect_with_notice( $fallback, 'error', 'Không khởi tạo được thanh toán. Vui lòng thử lại sau.', null );
		return;
	}

	// phpcs:ignore WordPress.Security.SafeRedirect -- đích đến là cổng VNPay (domain ngoài), không phải nội bộ.
	wp_redirect( $payment_url );
	exit;
}
