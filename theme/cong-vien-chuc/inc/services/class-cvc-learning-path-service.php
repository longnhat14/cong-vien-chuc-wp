<?php
/**
 * Learning Path (Phase 10) - đọc lộ trình + sinh mới từ 1 Goal, luôn cần
 * token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Learning_Path_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/learning-paths';
	}

	public function generate( int $examGoalId, string $token ): array {
		return $this->client->post( $this->endpoint() . '/generate', array( 'exam_goal_id' => $examGoalId ), $token );
	}

	/**
	 * Cập nhật tiến độ 1 bước: pending / in_progress / completed / skipped.
	 */
	public function updateItemStatus( int $pathId, int $itemId, string $status, string $token ): array {
		return $this->client->post(
			$this->endpoint() . '/' . $pathId . '/items/' . $itemId . '/status',
			array( 'status' => $status ),
			$token
		);
	}
}
