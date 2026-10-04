<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings (AI provider / VNPay / Momo) - luôn cần token admin.
 */
final class CVC_Setting_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/admin/settings';
	}

	public function get_all( string $token ): array {
		return $this->client->get( $this->endpoint(), array(), $token );
	}

	/**
	 * Nhật ký AI job pipeline (GET /api/admin/ai-jobs, quyền ai.view).
	 */
	public function ai_jobs( string $token, int $per_page = 15 ): array {
		return $this->client->get( '/api/admin/ai-jobs', array( 'per_page' => $per_page ), $token );
	}

	/**
	 * Cấu hình AI mở rộng: key từng nhà cung cấp, model tự tải, bảng giá,
	 * tỷ giá, hạn mức, thống kê token/chi phí (GET /api/admin/ai/overview).
	 */
	public function ai_overview( string $token, string $period = 'month' ): array {
		return $this->client->get( '/api/admin/ai/overview', array( 'period' => $period ), $token );
	}

	public function ai_refresh_models( string $token ): array {
		return $this->client->post( '/api/admin/ai/models/refresh', array(), $token );
	}

	public function ai_refresh_prices( string $token ): array {
		return $this->client->post( '/api/admin/ai/prices/refresh', array(), $token );
	}

	/**
	 * @param array<string, mixed> $price provider, model, input_per_m, cached_input_per_m, output_per_m | reset
	 */
	public function ai_save_price( array $price, string $token ): array {
		return $this->client->put( '/api/admin/ai/prices', $price, $token );
	}

	public function ai_test( string $provider, string $model, string $token ): array {
		return $this->client->post( '/api/admin/ai/test', array( 'provider' => $provider, 'model' => $model ), $token );
	}

	public function update( string $group, array $values, string $token ): array {
		return $this->client->put( $this->endpoint(), array(
			'group'  => $group,
			'values' => $values,
		), $token );
	}
}
