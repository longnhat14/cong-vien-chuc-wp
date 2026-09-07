<?php
/**
 * Generic HTTP client cho Laravel Public/User API.
 * Mọi domain service phải gọi Laravel qua lớp này, không tự viết
 * wp_remote_get()/wp_remote_post() rải rác trong template.
 *
 * Phase 10: thêm post()/put()/patch() + tham số $token cho request cần
 * xác thực (Sanctum Bearer) - CHỈ thêm method mới, không đổi hành vi
 * get() công khai đã có từ trước (Phase 1-4B).
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
	 * Cache trong 1 request để tránh gọi trùng cùng URL nhiều lần - CHỈ
	 * áp dụng cho GET công khai (không token), vì response GET có token
	 * phụ thuộc user hiện tại (không nên cache chung key với public GET,
	 * và bản thân mỗi request PHP-FPM là 1 user duy nhất nên nguy cơ rất
	 * thấp, nhưng vẫn cố ý loại trừ cho rõ ràng - xem get()).
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
	 * @param string|null          $token Sanctum bearer token - truyền khi cần gọi endpoint
	 *                                    user-scoped (VD /api/recommendations). null = public.
	 *
	 * @return array{
	 *     ok: bool,
	 *     status: int,
	 *     error: string|null,
	 *     data: mixed,
	 * }
	 */
	public function get( string $path, array $query = array(), ?string $token = null ): array {
		$url = $this->build_url( $path, $query );

		if ( null === $token && array_key_exists( $url, self::$memo ) ) {
			return self::$memo[ $url ];
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => $this->timeout,
				'headers' => $this->headers( $token ),
			)
		);

		$result = $this->handle_response( $response );

		if ( null === $token ) {
			self::$memo[ $url ] = $result;
		}

		return $result;
	}

	/**
	 * POST JSON body tới Laravel API - dùng cho auth (login/register),
	 * action ghi (dismiss match, engagement event, answer câu hỏi...).
	 *
	 * @param array<string, mixed> $body
	 */
	public function post( string $path, array $body = array(), ?string $token = null ): array {
		return $this->send( 'POST', $path, $body, $token );
	}

	/**
	 * @param array<string, mixed> $body
	 */
	public function put( string $path, array $body = array(), ?string $token = null ): array {
		return $this->send( 'PUT', $path, $body, $token );
	}

	/**
	 * DELETE tới Laravel API - dùng cho bookmark destroy.
	 */
	public function delete( string $path, ?string $token = null ): array {
		return $this->send( 'DELETE', $path, array(), $token );
	}

	/**
	 * @param array<string, mixed> $body
	 */
	private function send( string $method, string $path, array $body, ?string $token ): array {
		$url = $this->build_url( $path, array() );

		$response = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'timeout' => $this->timeout,
				'headers' => array_merge(
					$this->headers( $token ),
					array( 'Content-Type' => 'application/json' )
				),
				'body'    => wp_json_encode( $body, JSON_UNESCAPED_UNICODE ),
			)
		);

		return $this->handle_response( $response );
	}

	/**
	 * @return array<string, string>
	 */
	private function headers( ?string $token ): array {
		$headers = array( 'Accept' => 'application/json' );

		if ( null !== $token && '' !== $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		return $headers;
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

		$status  = (int) wp_remote_retrieve_response_code( $response );
		$body    = wp_remote_retrieve_body( $response );
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
