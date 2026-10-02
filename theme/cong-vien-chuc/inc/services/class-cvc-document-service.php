<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tài liệu free/trả phí - public list/detail, download cần token nếu
 * tài liệu trả phí (backend tự kiểm tra, xem Public\DocumentController).
 */
final class CVC_Document_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/documents';
	}

	public function list( array $query = array(), ?string $token = null ): array {
		return $this->client->get( $this->endpoint(), $query, $token );
	}

	public function find( string $slug, ?string $token = null ): array {
		return $this->client->get( $this->endpoint() . '/' . rawurlencode( $slug ), array(), $token );
	}

	public function download_url( string $slug ): string {
		return cvc_api_base_url() . '/api/documents/' . rawurlencode( $slug ) . '/download';
	}

	/**
	 * Tài liệu trả phí user ĐÃ MUA (Entitlement) - dashboard "Tài liệu đã mua".
	 */
	public function mine( string $token ): array {
		return $this->client->get( '/api/my-documents', array(), $token );
	}
}
