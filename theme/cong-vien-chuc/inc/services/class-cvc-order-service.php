<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tạo đơn hàng + nhận URL thanh toán VNPay. Luôn cần token (user phải
 * đăng nhập mới mua được).
 */
final class CVC_Order_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/orders';
	}

	/**
	 * @param array<int, array{type: string, id: int}> $items
	 */
	public function create( array $items, string $token ): array {
		return $this->client->post( $this->endpoint(), array( 'items' => $items ), $token );
	}
}
