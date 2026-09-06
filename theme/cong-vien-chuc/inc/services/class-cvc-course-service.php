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
	 */
	public function lesson( string $course_slug, $lesson_id ): array {
		return $this->client->get(
			$this->endpoint() . '/' . rawurlencode( $course_slug ) . '/lessons/' . rawurlencode( (string) $lesson_id )
		);
	}
}
