<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Certificate_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/my-certificates';
	}

	public function my_certificates( string $token ): array {
		return $this->client->get( $this->endpoint(), array(), $token );
	}

	public function verify( string $code ): array {
		return $this->client->get( '/api/certificates/verify/' . rawurlencode( $code ) );
	}
}
