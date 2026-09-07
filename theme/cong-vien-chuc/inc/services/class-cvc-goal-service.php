<?php
/**
 * Exam Goal (Phase 10) - CRUD + state machine, luôn cần token (user-scoped,
 * KHÔNG có phần công khai nào).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Goal_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/goals';
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function create( array $payload, string $token ): array {
		return $this->client->post( $this->endpoint(), $payload, $token );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function update( int $id, array $payload, string $token ): array {
		return $this->client->put( $this->endpoint() . '/' . $id, $payload, $token );
	}

	public function activate( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/activate', array(), $token );
	}

	public function pause( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/pause', array(), $token );
	}

	public function resume( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/resume', array(), $token );
	}

	public function complete( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/complete', array(), $token );
	}

	public function archive( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/archive', array(), $token );
	}

	public function matches( int $id, string $token ): array {
		return $this->client->get( $this->endpoint() . '/' . $id . '/matches', array(), $token );
	}
}
