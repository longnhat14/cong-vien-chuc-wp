<?php
/**
 * Recommendation Engine (Phase 10) - đọc thuần, luôn cần token
 * (user luôn lấy từ Sanctum phía backend, WP không tự tính gợi ý nào).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Recommendation_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/recommendations';
	}
}
