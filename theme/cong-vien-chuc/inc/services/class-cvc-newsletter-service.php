<?php
/**
 * Newsletter (đăng ký nhận bản tin ở footer) - endpoint công khai của
 * Laravel, KHÔNG cần token (khác Goal/Bookmark... vốn luôn user-scoped).
 * Chỉ có đúng 1 hành động (subscribe) nên không dùng list()/find() mặc
 * định của CVC_Api_Service, giống cách CVC_Exam_Attempt_Service tự định
 * nghĩa method riêng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Newsletter_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/newsletter';
	}

	/**
	 * @param array<string, mixed> $payload email + source (nguồn form, vd 'homepage_footer')
	 */
	public function subscribe( array $payload ): array {
		return $this->client->post( $this->endpoint() . '/subscribe', $payload );
	}
}
