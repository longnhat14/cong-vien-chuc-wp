<?php
/**
 * Profile (Phase 10) - xem/cập nhật hồ sơ + đổi mật khẩu, luôn cần token.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Profile_Service extends CVC_Api_Service {

	protected function endpoint(): string {
		return '/api/profile';
	}

	public function show( string $token ): array {
		return $this->client->get( $this->endpoint(), array(), $token );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function update( array $payload, string $token ): array {
		return $this->client->put( $this->endpoint(), $payload, $token );
	}

	public function changePassword( string $currentPassword, string $newPassword, string $newPasswordConfirmation, string $token ): array {
		return $this->client->post(
			$this->endpoint() . '/change-password',
			array(
				'current_password'          => $currentPassword,
				'new_password'              => $newPassword,
				'new_password_confirmation' => $newPasswordConfirmation,
			),
			$token
		);
	}
}
