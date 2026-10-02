<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Recruitment_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/recruitments';
	}

	public function get_related_assets( string $slug, ?string $token = null ): array {
		return $this->client->get( $this->endpoint() . '/' . rawurlencode( $slug ) . '/related-assets', array(), $token );
	}

	public function match_eligibility( array $params = array(), ?string $token = null ): array {
		return $this->client->get( $this->endpoint() . '/match-eligibility', $params, $token );
	}
}
