<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Course_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/courses';
	}

	/**
	 * @param string     $course_slug
	 * @param int|string $lesson_id
	 *
	 * $token: truyền khi có (user đã đăng nhập) để backend trả đúng nội
	 * dung bài học trả phí nếu user đã mua khóa học (Entitlement) - trước
	 * đây luôn gọi ẩn danh nên user đã mua vẫn bị null hoá nội dung.
	 */
	public function lesson( string $course_slug, $lesson_id, ?string $token = null ): array {
		return $this->client->get(
			$this->endpoint() . '/' . rawurlencode( $course_slug ) . '/lessons/' . rawurlencode( (string) $lesson_id ),
			array(),
			$token
		);
	}
}
