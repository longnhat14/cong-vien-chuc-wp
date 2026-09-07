<?php
/**
 * In-app Notification (Phase 10) - list/unread-count/read/read-all, luôn
 * cần token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Notification_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/notifications';
	}

	public function unreadCount( string $token ): array {
		return $this->client->get( $this->endpoint() . '/unread-count', array(), $token );
	}

	public function markRead( string $notificationId, string $token ): array {
		return $this->client->post( $this->endpoint() . '/' . $notificationId . '/read', array(), $token );
	}

	public function markAllRead( string $token ): array {
		return $this->client->post( $this->endpoint() . '/read-all', array(), $token );
	}
}
