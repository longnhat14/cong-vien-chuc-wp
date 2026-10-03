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

	/**
	 * Ghi danh (POST /api/course-enrollments). Khóa trả phí cần đã mua
	 * (Entitlement) - backend trả 402 nếu chưa.
	 */
	public function enroll( int $course_id, string $token ): array {
		return $this->client->post( '/api/course-enrollments', array( 'course_id' => $course_id ), $token );
	}

	/**
	 * Cập nhật tiến độ 1 bài (PUT /api/course-lessons/{id}/progress). Đủ mọi
	 * bài -> backend tự đánh dấu hoàn thành khóa và cấp chứng chỉ.
	 *
	 * @param array<string, mixed> $payload
	 */
	public function update_lesson_progress( int $lesson_id, array $payload, string $token ): array {
		return $this->client->put( '/api/course-lessons/' . $lesson_id . '/progress', $payload, $token );
	}

	/**
	 * @param array<string, mixed> $query
	 */
	public function my_courses( array $query, string $token ): array {
		return $this->client->get( '/api/my-courses', $query, $token );
	}
}
