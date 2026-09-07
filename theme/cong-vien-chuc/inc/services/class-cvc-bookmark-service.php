<?php
/**
 * Bookmark (Phase 10) - store/destroy, user-scoped, luôn cần token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Bookmark_Service extends CVC_Api_Service {

	/**
	 * Type client được phép gửi - phải khớp TYPE_MAP whitelist ở
	 * BookmarkController::TYPE_MAP (backend là authority, đây chỉ để UI
	 * không hiện lựa chọn vô nghĩa - backend vẫn tự validate lại).
	 */
	public const VALID_TYPES = array( 'recruitment', 'course', 'course_lesson', 'topic', 'knowledge_item', 'exam' );

	protected function endpoint(): string {
		return '/api/bookmarks';
	}

	public function store( string $type, int $id, string $token ): array {
		return $this->client->post( $this->endpoint(), array( 'type' => $type, 'id' => $id ), $token );
	}

	public function destroy( int $bookmarkId, string $token ): array {
		return $this->client->delete( $this->endpoint() . '/' . $bookmarkId, $token );
	}
}
