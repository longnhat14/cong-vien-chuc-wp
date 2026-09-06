<?php
/**
 * Base class cho mọi domain service (Course, Topic, Knowledge, ...).
 * Domain service KHÔNG gọi wp_remote_get() trực tiếp - luôn qua
 * CVC_Api_Client.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class CVC_Api_Service {

	protected CVC_Api_Client $client;

	public function __construct( ?CVC_Api_Client $client = null ) {
		$this->client = $client ?? new CVC_Api_Client();
	}

	/**
	 * Endpoint gốc của domain, ví dụ '/api/courses'.
	 */
	abstract protected function endpoint(): string;

	/**
	 * @param array<string, mixed> $query
	 */
	public function list( array $query = array() ): array {
		return $this->client->get( $this->endpoint(), $query );
	}

	public function find( string $slug ): array {
		return $this->client->get( $this->endpoint() . '/' . $slug );
	}

	/**
	 * Tổng số record, dùng cho demo/foundation status page.
	 * Trả về null nếu request lỗi (không throw, để caller tự quyết định
	 * hiển thị empty/error state).
	 */
	public function count(): ?int {
		$result = $this->list( array( 'per_page' => 1 ) );

		if ( ! $result['ok'] ) {
			return null;
		}

		return $result['data']['data']['total'] ?? null;
	}
}
