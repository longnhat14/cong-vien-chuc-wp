<?php
/**
 * Plugin Name: CVC Fetch Relay
 * Description: Trạm tải trang cho congvienchuc.com - tải hộ trang/văn bản công khai trên cổng .gov.vn/.edu.vn mà máy chủ ở nước ngoài không kết nối được. Chỉ nhận yêu cầu có chữ ký, chỉ tên miền cho phép, chỉ đọc (GET), không lưu nội dung.
 * Version: 1.1.0
 * Requires PHP: 7.4
 * Author: Công Viên Chức
 *
 * Cài đặt: thêm vào wp-config.php (trước dòng "That's all, stop editing!"):
 *   define( 'CVC_RELAY_SECRET', '...khoa bi mat 64 ky tu...' );
 * Khóa phải giống CVC_RELAY_SECRET trong .env của backend.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Fetch_Relay {

	const VERSION       = '1.1.0';
	const ROUTE_NS      = 'cvc-relay/v1';
	const MAX_BYTES     = 27262976; // 26 MB.
	const TIMEOUT       = 45;
	const MAX_SKEW      = 120;      // Giây lệch đồng hồ tối đa.
	const RATE_PER_MIN  = 30;
	const STATS_OPTION  = 'cvc_relay_stats';
	const HOSTS_OPTION  = 'cvc_relay_extra_hosts';
	const DEFAULT_SUFFIXES = array( '.gov.vn', '.edu.vn' );
	const USER_AGENT    = 'Mozilla/5.0 (compatible; CongVienChucBot/1.0; +https://congvienchuc.com)';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_action( 'admin_post_cvc_relay_test', array( __CLASS__, 'handle_test' ) );
			add_action( 'admin_post_cvc_relay_hosts', array( __CLASS__, 'handle_hosts' ) );
		}
	}

	/* ------------------------------------------------------------------ */
	/* REST                                                                */
	/* ------------------------------------------------------------------ */

	public static function register_routes() {
		register_rest_route(
			self::ROUTE_NS,
			'/fetch',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_fetch' ),
				'permission_callback' => '__return_true', // Xac thuc bang chu ky HMAC ben trong.
			)
		);
		register_rest_route(
			self::ROUTE_NS,
			'/ping',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_ping' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	private static function secret() {
		$s = defined( 'CVC_RELAY_SECRET' ) ? (string) CVC_RELAY_SECRET : '';
		return strlen( $s ) >= 32 ? $s : '';
	}

	/**
	 * Chu ky: hex(HMAC-SHA256(secret, ts \n nonce \n url \n insecure)).
	 *
	 * @return true|WP_Error
	 */
	private static function verify( WP_REST_Request $req, $url, $insecure = '0' ) {
		$secret = self::secret();
		if ( '' === $secret ) {
			return new WP_Error( 'cvc_relay_off', 'Trạm chưa cấu hình khóa.', array( 'status' => 503 ) );
		}
		$ts    = (int) $req->get_header( 'x_cvc_ts' );
		$nonce = (string) $req->get_header( 'x_cvc_nonce' );
		$sig   = (string) $req->get_header( 'x_cvc_signature' );
		if ( ! $ts || abs( time() - $ts ) > self::MAX_SKEW || ! preg_match( '/^[a-f0-9]{32}$/', $nonce ) || ! preg_match( '/^[a-f0-9]{64}$/', $sig ) ) {
			return new WP_Error( 'cvc_relay_auth', 'Chữ ký không hợp lệ.', array( 'status' => 401 ) );
		}
		$expected = hash_hmac( 'sha256', $ts . "\n" . $nonce . "\n" . $url . "\n" . $insecure, $secret );
		if ( ! hash_equals( $expected, $sig ) ) {
			self::bump( 'auth_failed' );
			return new WP_Error( 'cvc_relay_auth', 'Chữ ký không hợp lệ.', array( 'status' => 401 ) );
		}
		// Chong phat lai: moi nonce dung 1 lan.
		$key = 'cvc_relay_n_' . $nonce;
		if ( false !== get_transient( $key ) ) {
			return new WP_Error( 'cvc_relay_replay', 'Yêu cầu đã dùng.', array( 'status' => 401 ) );
		}
		set_transient( $key, 1, self::MAX_SKEW * 2 );
		return true;
	}

	public static function handle_ping( WP_REST_Request $req ) {
		$ok = self::verify( $req, 'ping', '0' );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		return rest_ensure_response( array( 'ok' => true, 'version' => self::VERSION, 'time' => time() ) );
	}

	public static function handle_fetch( WP_REST_Request $req ) {
		$url      = (string) $req->get_param( 'url' );
		$insecure = $req->get_param( 'insecure' ) ? '1' : '0';
		$ok       = self::verify( $req, $url, $insecure );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		$check = self::check_url( $url );
		if ( is_wp_error( $check ) ) {
			self::log_error( $url, $check->get_error_message() );
			return $check;
		}

		// Gioi han tan suat (toan tram).
		$bucket = 'cvc_relay_rate_' . gmdate( 'YmdHi' );
		$count  = (int) get_transient( $bucket );
		if ( $count >= self::RATE_PER_MIN ) {
			return new WP_Error( 'cvc_relay_rate', 'Quá nhiều yêu cầu, thử lại sau.', array( 'status' => 429 ) );
		}
		set_transient( $bucket, $count + 1, 120 );

		$accept = (string) $req->get_param( 'accept' );
		$tmp    = wp_tempnam( 'cvc-relay' );
		$start  = microtime( true );
		$resp   = wp_remote_get(
			$url,
			array(
				'timeout'             => self::TIMEOUT,
				'redirection'         => 0, // Backend tu theo redirect (moi buoc lai qua tram + kiem tra).
				'reject_unsafe_urls'  => true,
				// Nhieu cong .gov.vn thieu chung chi trung gian: backend chi xin bo kiem tra SSL khi da loi SSL.
				'sslverify'           => '1' !== $insecure,
				'user-agent'          => self::USER_AGENT,
				'headers'             => array( 'Accept' => '' !== $accept ? substr( $accept, 0, 200 ) : '*/*' ),
				'stream'              => true,
				'filename'            => $tmp,
				'limit_response_size' => self::MAX_BYTES,
				'cookies'             => array(),
			)
		);

		if ( is_wp_error( $resp ) ) {
			@unlink( $tmp ); // phpcs:ignore
			self::log_error( $url, $resp->get_error_message() );
			self::bump( 'upstream_failed' );
			return new WP_Error( 'cvc_relay_upstream', 'Không tải được: ' . $resp->get_error_message(), array( 'status' => 502 ) );
		}

		$status = (int) wp_remote_retrieve_response_code( $resp );
		$size   = (int) @filesize( $tmp ); // phpcs:ignore
		self::bump( 'ok', $size );

		// Tra ve noi dung tho (khong qua JSON) - giu nguyen file PDF/RAR.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		status_header( 200 );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Length: ' . $size );
		header( 'Cache-Control: no-store' );
		header( 'X-Relay-Status: ' . $status );
		header( 'X-Relay-Ms: ' . (int) round( ( microtime( true ) - $start ) * 1000 ) );
		foreach ( array( 'content-type' => 'X-Relay-Content-Type', 'location' => 'X-Relay-Location', 'content-disposition' => 'X-Relay-Content-Disposition' ) as $h => $out ) {
			$v = wp_remote_retrieve_header( $resp, $h );
			if ( is_array( $v ) ) {
				$v = end( $v );
			}
			if ( '' !== (string) $v ) {
				header( $out . ': ' . rawurlencode( substr( (string) $v, 0, 2000 ) ) );
			}
		}
		readfile( $tmp ); // phpcs:ignore
		@unlink( $tmp ); // phpcs:ignore
		exit;
	}

	/**
	 * Chi http/https, cong 80/443, ten mien trong danh sach cho phep, khong tro vao IP noi bo.
	 *
	 * @return true|WP_Error
	 */
	public static function check_url( $url ) {
		$p = wp_parse_url( $url );
		if ( ! $p || empty( $p['host'] ) || ! in_array( strtolower( $p['scheme'] ?? '' ), array( 'http', 'https' ), true ) ) {
			return new WP_Error( 'cvc_relay_url', 'URL không hợp lệ.', array( 'status' => 400 ) );
		}
		if ( isset( $p['port'] ) && ! in_array( (int) $p['port'], array( 80, 443 ), true ) ) {
			return new WP_Error( 'cvc_relay_url', 'Cổng không được phép.', array( 'status' => 403 ) );
		}
		if ( isset( $p['user'] ) || isset( $p['pass'] ) ) {
			return new WP_Error( 'cvc_relay_url', 'URL không được chứa tài khoản.', array( 'status' => 403 ) );
		}
		$host = strtolower( rtrim( $p['host'], '.' ) );
		if ( ! self::host_allowed( $host ) ) {
			return new WP_Error( 'cvc_relay_host', 'Tên miền không nằm trong danh sách cho phép: ' . $host, array( 'status' => 403 ) );
		}
		$ips = gethostbynamel( $host );
		if ( empty( $ips ) ) {
			return new WP_Error( 'cvc_relay_dns', 'Không phân giải được tên miền.', array( 'status' => 502 ) );
		}
		foreach ( $ips as $ip ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return new WP_Error( 'cvc_relay_ip', 'Địa chỉ đích không được phép.', array( 'status' => 403 ) );
			}
		}
		return true;
	}

	public static function host_allowed( $host ) {
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return false;
		}
		foreach ( self::DEFAULT_SUFFIXES as $suffix ) {
			if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}
		foreach ( self::extra_hosts() as $h ) {
			if ( $host === $h || substr( $host, -strlen( '.' . $h ) ) === '.' . $h ) {
				return true;
			}
		}
		return false;
	}

	private static function extra_hosts() {
		$v = get_option( self::HOSTS_OPTION, array() );
		return is_array( $v ) ? $v : array();
	}

	/* ------------------------------------------------------------------ */
	/* Thong ke (khong luu noi dung)                                      */
	/* ------------------------------------------------------------------ */

	private static function stats() {
		$s = get_option( self::STATS_OPTION, array() );
		return is_array( $s ) ? $s : array();
	}

	private static function bump( $key, $bytes = 0 ) {
		$s   = self::stats();
		$day = gmdate( 'Y-m-d' );
		if ( ( $s['day'] ?? '' ) !== $day ) {
			$s['day']   = $day;
			$s['today'] = array();
		}
		$s['today'][ $key ]      = (int) ( $s['today'][ $key ] ?? 0 ) + 1;
		$s['today']['bytes']     = (int) ( $s['today']['bytes'] ?? 0 ) + (int) $bytes;
		$s['total'][ $key ]      = (int) ( $s['total'][ $key ] ?? 0 ) + 1;
		$s['last_request']       = time();
		update_option( self::STATS_OPTION, $s, false );
	}

	private static function log_error( $url, $msg ) {
		$s           = self::stats();
		$errors      = $s['errors'] ?? array();
		array_unshift( $errors, array( 't' => time(), 'host' => (string) wp_parse_url( $url, PHP_URL_HOST ), 'msg' => substr( (string) $msg, 0, 200 ) ) );
		$s['errors'] = array_slice( $errors, 0, 20 );
		update_option( self::STATS_OPTION, $s, false );
	}

	/* ------------------------------------------------------------------ */
	/* Trang quan tri: Cong cu -> CVC Fetch Relay                          */
	/* ------------------------------------------------------------------ */

	public static function admin_menu() {
		add_management_page( 'CVC Fetch Relay', 'CVC Fetch Relay', 'manage_options', 'cvc-fetch-relay', array( __CLASS__, 'render_admin' ) );
	}

	private static function test_urls() {
		return array(
			'https://sonoivu.hanoi.gov.vn/',
			'https://sonoivu.sonla.gov.vn/',
			'https://sonoivu.camau.gov.vn/',
			'https://syt.angiang.gov.vn/',
			'https://hoankiem.hanoi.gov.vn/',
			'https://www.quangninh.gov.vn/',
			'https://sonv.langson.gov.vn/',
		);
	}

	public static function handle_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Không có quyền.' );
		}
		check_admin_referer( 'cvc_relay_test' );
		$results = array();
		foreach ( self::test_urls() as $u ) {
			$start = microtime( true );
			$check = self::check_url( $u );
			if ( is_wp_error( $check ) ) {
				$results[] = array( $u, 'Lỗi', $check->get_error_message(), 0 );
				continue;
			}
			$r  = wp_remote_get( $u, array( 'timeout' => 20, 'redirection' => 3, 'user-agent' => self::USER_AGENT, 'limit_response_size' => 2000000 ) );
			$ms = (int) round( ( microtime( true ) - $start ) * 1000 );
			if ( is_wp_error( $r ) ) {
				$results[] = array( $u, 'Lỗi', $r->get_error_message(), $ms );
			} else {
				$results[] = array( $u, (string) wp_remote_retrieve_response_code( $r ), size_format( strlen( (string) wp_remote_retrieve_body( $r ) ) ), $ms );
			}
		}
		set_transient( 'cvc_relay_test_' . get_current_user_id(), $results, 600 );
		wp_safe_redirect( admin_url( 'tools.php?page=cvc-fetch-relay&tested=1' ) );
		exit;
	}

	public static function handle_hosts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Không có quyền.' );
		}
		check_admin_referer( 'cvc_relay_hosts' );
		$raw   = isset( $_POST['hosts'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hosts'] ) ) : '';
		$hosts = array();
		foreach ( preg_split( '/[\s,]+/', strtolower( $raw ) ) as $h ) {
			$h = trim( preg_replace( '#^https?://#', '', $h ), " ./" );
			if ( '' !== $h && preg_match( '/^[a-z0-9.-]+\.[a-z]{2,}$/', $h ) ) {
				$hosts[] = $h;
			}
		}
		update_option( self::HOSTS_OPTION, array_values( array_unique( $hosts ) ), false );
		wp_safe_redirect( admin_url( 'tools.php?page=cvc-fetch-relay&saved=1' ) );
		exit;
	}

	public static function render_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$stats   = self::stats();
		$results = get_transient( 'cvc_relay_test_' . get_current_user_id() );
		$secret  = '' !== self::secret();
		?>
		<div class="wrap">
			<h1>CVC Fetch Relay <small style="font-weight:normal">v<?php echo esc_html( self::VERSION ); ?></small></h1>
			<p>Trạm tải hộ trang/văn bản công khai cho <strong>congvienchuc.com</strong>. Chỉ nhận yêu cầu có chữ ký, chỉ tên miền <code>*.gov.vn</code>, <code>*.edu.vn</code> và danh sách bổ sung bên dưới; không lưu nội dung.</p>

			<h2>Trạng thái</h2>
			<table class="widefat striped" style="max-width:760px">
				<tr><td>Khóa bí mật (CVC_RELAY_SECRET trong wp-config.php)</td><td><?php echo $secret ? '<strong style="color:#008a20">Đã cấu hình</strong>' : '<strong style="color:#b32d2e">Chưa có — trạm đang tắt</strong>'; ?></td></tr>
				<tr><td>Địa chỉ trạm</td><td><code><?php echo esc_html( rest_url( self::ROUTE_NS . '/fetch' ) ); ?></code></td></tr>
				<tr><td>Hôm nay (<?php echo esc_html( $stats['day'] ?? '—' ); ?>)</td><td><?php echo esc_html( sprintf( '%d lượt thành công · %d lỗi nguồn · %d sai chữ ký · %s', $stats['today']['ok'] ?? 0, $stats['today']['upstream_failed'] ?? 0, $stats['today']['auth_failed'] ?? 0, size_format( $stats['today']['bytes'] ?? 0 ) ) ); ?></td></tr>
				<tr><td>Lần gọi gần nhất</td><td><?php echo ! empty( $stats['last_request'] ) ? esc_html( wp_date( 'H:i d/m/Y', (int) $stats['last_request'] ) ) : '—'; ?></td></tr>
			</table>

			<h2>Kiểm tra kết nối từ hosting này</h2>
			<p>Thử tải trang chủ các cổng đang chặn máy chủ congvienchuc.com. Kết quả "200" nghĩa là hosting này tải được.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cvc_relay_test">
				<?php wp_nonce_field( 'cvc_relay_test' ); ?>
				<?php submit_button( 'Kiểm tra', 'primary', 'submit', false ); ?>
			</form>
			<?php if ( is_array( $results ) ) : ?>
				<table class="widefat striped" style="max-width:760px;margin-top:12px">
					<thead><tr><th>Trang</th><th>Kết quả</th><th>Chi tiết</th><th>Thời gian</th></tr></thead>
					<?php foreach ( $results as $r ) : ?>
						<tr><td><?php echo esc_html( $r[0] ); ?></td><td><strong><?php echo esc_html( $r[1] ); ?></strong></td><td><?php echo esc_html( $r[2] ); ?></td><td><?php echo esc_html( $r[3] . ' ms' ); ?></td></tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>

			<h2>Tên miền bổ sung</h2>
			<p>Ngoài <code>*.gov.vn</code>, <code>*.edu.vn</code> (VD trang bệnh viện, trường học dùng tên miền .vn/.com.vn). Mỗi dòng một tên miền.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cvc_relay_hosts">
				<?php wp_nonce_field( 'cvc_relay_hosts' ); ?>
				<textarea name="hosts" rows="4" cols="60"><?php echo esc_textarea( implode( "\n", self::extra_hosts() ) ); ?></textarea>
				<?php submit_button( 'Lưu danh sách' ); ?>
			</form>

			<?php if ( ! empty( $stats['errors'] ) ) : ?>
				<h2>Lỗi gần đây</h2>
				<table class="widefat striped" style="max-width:760px">
					<?php foreach ( $stats['errors'] as $e ) : ?>
						<tr><td><?php echo esc_html( wp_date( 'H:i d/m', (int) $e['t'] ) ); ?></td><td><?php echo esc_html( $e['host'] ); ?></td><td><?php echo esc_html( $e['msg'] ); ?></td></tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}

CVC_Fetch_Relay::init();
