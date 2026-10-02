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

	public function update( string $group, array $values, string $token ): array {
		return $this->client->put( $this->endpoint(), array(
			'group'  => $group,
			'values' => $values,
		), $token );
	}
}
