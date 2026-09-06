<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gọi GET /api/search - luôn qua CVC_Api_Client (kế thừa từ
 * CVC_Api_Service), không tự wp_remote_get().
 */
final class CVC_Search_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/search';
	}

	/**
	 * @param string $q       Từ khóa - caller chịu trách nhiệm trim/chuẩn
	 *                        hóa trước khi gọi (xem template-search.php).
	 * @param string $type    'all' hoặc 1 trong các key của
	 *                        cvc_search_domains() - không tự validate lại
	 *                        ở đây, API tự validate và trả 422 nếu sai.
	 * @param int    $page
	 * @param int    $per_page
	 */
	public function search( string $q, string $type, int $page = 1, int $per_page = 12 ): array {
		$query = array(
			'q'        => $q,
			'page'     => $page,
			'per_page' => $per_page,
		);

		if ( 'all' !== $type ) {
			$query['type'] = $type;
		}

		return $this->list( $query );
	}
}
