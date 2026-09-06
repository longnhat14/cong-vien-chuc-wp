<?php
/**
 * Generic HTTP client cho Laravel Public API.
 * Mọi domain service phải gọi Laravel qua lớp này, không tự viết
 * wp_remote_get() rải rác trong template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trả về base URL của Laravel API.
 *
 * Ưu tiên hằng số CVC_API_BASE_URL (khai báo qua wp-config.php /
 * WORDPRESS_CONFIG_EXTRA cho môi trường cụ thể - local dùng
 * host.docker.internal, production dùng domain thật). Nếu chưa cấu hình,
 * fallback về domain production - không bao giờ để trống hoặc nhận URL
 * từ input người dùng.
 */
function cvc_api_base_url(): string {
	if ( defined( 'CVC_API_BASE_URL' ) && CVC_API_BASE_URL ) {
		return CVC_API_BASE_URL;
	}

	return 'https://api.congvienchuc.com';
}

final class CVC_Api_Client {

	/**
	 * Cache trong 1 request để tránh gọi trùng cùng URL nhiều lần.
	 *
	 * @var array<string, array>
	 */
	private static array $memo = array();

	private string $base_url;
	private int $timeout;

	public function __construct( ?string $base_url = null, int $timeout = 5 ) {
		$this->base_url = $base_url ?? cvc_api_base_url();
		$this->timeout  = $timeout;
	}

	/**
	 * Gọi GET tới Laravel API.
	 *
	 * @param string               $path  Ví dụ '/api/courses' hoặc '/api/courses/{slug}'.
	 * @param array<string, mixed> $query Query params (đã được sanitize bởi caller).
	 *
	 * @return array{
	 *     ok: bool,
	 *     status: int,
	 *     error: string|null,
	 *     data: mixed,
	 * }
	 */
	public function get( string $path, array $query = array() ): array {
		$url = $this->build_url( $path, $query );

		if ( array_key_exists( $url, self::$memo ) ) {
			return self::$memo[ $url ];
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => $this->timeout,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		$result             = $this->handle_response( $response );
		self::$memo[ $url ] = $result;

		return $result;
	}

	private function build_url( string $path, array $query ): string {
		$url = rtrim( $this->base_url, '/' ) . '/' . ltrim( $path, '/' );

		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		return $url;
	}

	/**
	 * @param array|WP_Error $response
	 */
	private function handle_response( $response ): array {
		if ( is_wp_error( $response ) ) {
			return array(
				'ok'     => false,
				'status' => 0,
				'error'  => $response->get_error_message(),
				'data'   => null,
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$decoded = ( '' !== $body ) ? json_decode( $body, true ) : null;

		if ( $status < 200 || $status >= 300 ) {
			$message = ( is_array( $decoded ) && isset( $decoded['message'] ) )
				? (string) $decoded['message']
				: sprintf( 'API request failed with status %d.', $status );

			return array(
				'ok'     => false,
				'status' => $status,
				'error'  => $message,
				'data'   => $decoded,
			);
		}

		if ( null === $decoded ) {
			return array(
				'ok'     => false,
				'status' => $status,
				'error'  => 'API trả về JSON không hợp lệ hoặc rỗng.',
				'data'   => null,
			);
		}

		return array(
			'ok'     => true,
			'status' => $status,
			'error'  => null,
			'data'   => $decoded,
		);
	}
}
