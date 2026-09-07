<?php
/**
 * Recruitment Match (Phase 10) - list + state transitions (seen/dismiss/
 * interested), ownership luôn do backend enforce qua token, WP không tự
 * check quyền.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Recruitment_Match_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/recruitment-matches';
	}

	public function markSeen( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/seen', array(), $token );
	}

	public function dismiss( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/dismiss', array(), $token );
	}

	public function interested( int $id, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $id . '/interested', array(), $token );
	}
}
