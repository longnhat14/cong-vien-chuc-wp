<?php
/**
 * Lan tỏa (12/11/2026, claude/ke-hoach-gioi-thieu-va-chia-se.md):
 *  - Giới thiệu bạn bè: ?ref=MA ở bất kỳ trang nào → cookie cvc_ref 30 ngày (lần bấm cuối thắng) → gửi kèm khi đăng ký.
 *    Thưởng 2 phía do backend xét (App\Services\Growth\Referrals). Không thưởng cho thao tác bấm chia sẻ.
 *  - Thách bạn làm cùng đề: /thach/{MA}/ (ảnh chia sẻ + nút làm đề), banner trên trang đề, so điểm ở trang kết quả.
 *  - Nhóm ôn thi: /nhom-on-thi/ và /nhom-on-thi/{MA}/ - xếp hạng chăm chỉ trong nhóm.
 *  - Nút chia sẻ dùng chung: Facebook, Chia sẻ (Zalo / ứng dụng trên điện thoại), Sao chép link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_REF_COOKIE   = 'cvc_ref';
const CVC_THACH_COOKIE = 'cvc_thach';

function cvc_growth_set_cookie( string $name, string $value, int $days ): void {
	setcookie( $name, $value, array( 'expires' => time() + $days * DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
	$_COOKIE[ $name ] = $value;
}

add_action(
	'init',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() || headers_sent() ) {
			return;
		}
		$path  = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		$thach = strtoupper( sanitize_key( wp_unslash( (string) ( $_GET['thach'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $thach && preg_match( '#^/thach/([A-Za-z0-9]{6,16})/?$#', $path, $m ) ) {
			$thach = strtoupper( $m[1] );
		}
		if ( '' !== $thach && preg_match( '/^[A-Z0-9]{6,16}$/', $thach ) ) {
			cvc_growth_set_cookie( CVC_THACH_COOKIE, $thach, 30 );
		}
		$ref = strtoupper( sanitize_key( wp_unslash( (string) ( $_GET['ref'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $ref || ! preg_match( '/^[A-Z0-9]{4,16}$/', $ref ) ) {
			return;
		}
		$src = 'link';
		$obj = '';
		if ( '' !== $thach ) {
			$src = 'challenge';
			$obj = $thach;
		} elseif ( preg_match( '#^/nhom-on-thi/([A-Za-z0-9]{6,16})/?$#', $path, $m ) ) {
			$src = 'group';
			$obj = strtoupper( $m[1] );
		}
		cvc_growth_set_cookie( CVC_REF_COOKIE, wp_json_encode( array( 'c' => $ref, 's' => $src, 'o' => $obj ) ), 30 );
	},
	1
);

/** Trường gửi kèm khi đăng ký (email / Google). */
function cvc_growth_signup_fields(): array {
	$raw = isset( $_COOKIE[ CVC_REF_COOKIE ] ) ? json_decode( wp_unslash( (string) $_COOKIE[ CVC_REF_COOKIE ] ), true ) : null;
	if ( ! is_array( $raw ) || ! preg_match( '/^[A-Z0-9]{4,16}$/', (string) ( $raw['c'] ?? '' ) ) ) {
		return array();
	}

	return array_filter( array( 'ref_code' => (string) $raw['c'], 'ref_source' => sanitize_key( (string) ( $raw['s'] ?? 'link' ) ), 'ref_obj' => preg_replace( '/[^A-Z0-9]/', '', (string) ( $raw['o'] ?? '' ) ) ) );
}

function cvc_growth_api( string $path, array $q = array(), ?string $token = null, int $ttl = 0 ): array {
	$key = 'cvc_gr_' . md5( $path . wp_json_encode( $q ) );
	if ( $ttl > 0 && null === $token ) {
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
	}
	$res  = ( new CVC_Api_Client( null, 8 ) )->get( $path, $q, $token );
	$data = ! empty( $res['ok'] ) && is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : array();
	if ( $ttl > 0 && null === $token && $data ) {
		set_transient( $key, $data, $ttl );
	}

	return $data;
}

/** Link giới thiệu của người đang đăng nhập ('' nếu chưa đăng nhập). */
function cvc_growth_my_ref(): string {
	static $ref = null;
	if ( null !== $ref ) {
		return $ref;
	}
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	if ( ! $token ) {
		return $ref = '';
	}
	$uid = (int) ( cvc_current_user()['id'] ?? 0 );
	$key = 'cvc_myref_' . $uid;
	$hit = $uid ? get_transient( $key ) : false;
	if ( is_string( $hit ) && '' !== $hit ) {
		return $ref = $hit;
	}
	$d   = cvc_growth_api( '/api/me/referral', array(), $token );
	$ref = (string) ( $d['code'] ?? '' );
	if ( $uid && '' !== $ref ) {
		set_transient( $key, $ref, DAY_IN_SECONDS );
	}

	return $ref;
}

/** Thêm ?ref= của người đang xem vào link (nếu có). */
function cvc_growth_with_ref( string $url, ?string $ref = null ): string {
	$ref = $ref ?? cvc_growth_my_ref();

	return '' !== $ref ? add_query_arg( 'ref', $ref, $url ) : $url;
}

/* ---------- Nút chia sẻ dùng chung ---------- */
function cvc_share_buttons( string $url, string $text, string $slot = 'share' ): void {
	static $js = false;
	$fb = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url );
	?>
	<div class="cvc-share" data-url="<?php echo esc_attr( $url ); ?>" data-text="<?php echo esc_attr( $text ); ?>">
		<a class="cvc-share-btn is-fb" href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" data-cvc-go="<?php echo esc_attr( $slot ); ?>-fb">Facebook</a>
		<button type="button" class="cvc-share-btn is-native" data-share-native>Chia sẻ (Zalo…)</button>
		<button type="button" class="cvc-share-btn" data-share-copy>Sao chép link</button>
		<input class="cvc-share-url" type="text" readonly value="<?php echo esc_attr( $url ); ?>" aria-label="Đường dẫn chia sẻ" onclick="this.select()">
	</div>
	<?php
	if ( $js ) {
		return;
	}
	$js = true;
	?>
	<style>.cvc-share{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:10px 0}.cvc-share-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;border:1px solid #c9d4e0;background:#fff;color:#14263b;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none}.cvc-share-btn.is-fb{background:#1877f2;border-color:#1877f2;color:#fff}.cvc-share-btn.is-native{background:#0068ff;border-color:#0068ff;color:#fff}.cvc-share-btn:focus-visible{outline:3px solid #f2d38a;outline-offset:2px}.cvc-share-url{flex:1 1 220px;min-width:0;padding:8px 10px;border:1px solid #dfe4ea;border-radius:10px;font-size:13px;color:#4a5563;background:#f7f9fb}</style>
	<script>
	(function(){document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('[data-share-native],[data-share-copy]');if(!b)return;var w=b.closest('.cvc-share');var u=w.getAttribute('data-url'),t=w.getAttribute('data-text');
	function copy(){var done=function(){var o=b.textContent;b.textContent='Đã sao chép';setTimeout(function(){b.textContent=o;},1600);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(u).then(done,function(){w.querySelector('.cvc-share-url').select();});}else{w.querySelector('.cvc-share-url').select();try{document.execCommand('copy');done();}catch(x){}}}
	if(b.hasAttribute('data-share-native')&&navigator.share){navigator.share({title:t,text:t,url:u}).catch(function(){});}else{copy();}
	try{if(window.CVC_T&&navigator.sendBeacon){navigator.sendBeacon(window.CVC_T.u,new Blob([JSON.stringify({e:'go',k:'share-'+(b.hasAttribute('data-share-native')?'native':'copy'),p:location.pathname,a:''})],{type:'text/plain'}));}}catch(x){}});})();
	</script>
	<?php
}

/* ---------- Thách đấu ---------- */
function cvc_growth_challenge( string $code ): array {
	return preg_match( '/^[A-Z0-9]{6,16}$/', $code ) ? cvc_growth_api( '/api/challenges/' . rawurlencode( $code ), array(), null, 10 * MINUTE_IN_SECONDS ) : array();
}

function cvc_growth_pct( $v ): string {
	$f = (float) $v;

	return number_format_i18n( $f, floor( $f ) == $f ? 0 : 1 ) . '%';
}

/** Banner trên trang đề khi đến từ lời thách đấu. */
function cvc_growth_challenge_banner( string $exam_slug ): void {
	$code = strtoupper( (string) ( $_COOKIE[ CVC_THACH_COOKIE ] ?? '' ) );
	if ( '' === $code ) {
		return;
	}
	$c = cvc_growth_challenge( $code );
	if ( ! $c || (string) ( $c['exam']['slug'] ?? '' ) !== $exam_slug ) {
		return;
	}
	?>
	<div class="cvc-thach-banner" role="note">
		<b>⚔ <?php echo esc_html( (string) $c['name'] ); ?> thách bạn làm đề này</b>
		<span><?php echo esc_html( $c['name'] . ' đạt ' . cvc_growth_pct( $c['percentage'] ) . ' (' . (int) $c['correct'] . '/' . (int) $c['total'] . ' câu đúng). Làm xong, trang kết quả sẽ so điểm của hai bạn.' ); ?></span>
	</div>
	<style>.cvc-thach-banner{display:grid;gap:4px;padding:14px 16px;border-radius:14px;background:linear-gradient(90deg,#123a63,#1767aa);color:#fff;font-size:15px;line-height:1.5}.cvc-thach-banner b{font-size:16px;color:#f2d38a}</style>
	<?php
}

/** Trang kết quả: so điểm (nếu đến từ thách đấu) + tạo lời thách bạn bè. */
function cvc_growth_result_box( int $attempt_id, array $exam ): void {
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	if ( ! $token || $attempt_id <= 0 ) {
		return;
	}
	$slug = (string) ( $exam['slug'] ?? '' );
	$cmp  = null;
	$code = strtoupper( (string) ( $_COOKIE[ CVC_THACH_COOKIE ] ?? '' ) );
	if ( '' !== $code ) {
		$d = cvc_growth_api( '/api/challenges/' . rawurlencode( $code ) . '/compare', array(), $token );
		if ( $d && (string) ( $d['challenge']['exam']['slug'] ?? '' ) === $slug && ! empty( $d['mine'] ) ) {
			$cmp = $d;
		}
	}
	$res  = ( new CVC_Api_Client( null, 8 ) )->post( '/api/challenges', array( 'attempt_id' => $attempt_id ), $token );
	$mine = ! empty( $res['ok'] ) && is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : null;
	if ( ! $cmp && ! $mine ) {
		return;
	}
	?>
	<section class="card cvc-thach-box"><div class="pad">
		<?php if ( $cmp ) : ?>
			<?php $m = $cmp['mine']; $ch = $cmp['challenge']; $word = array( 'win' => 'Bạn thắng!', 'tie' => 'Hòa điểm!', 'lose' => 'Chưa vượt được' )[ $m['result'] ] ?? ''; ?>
			<h2 class="side-title">⚔ So điểm thách đấu</h2>
			<p class="cvc-thach-vs"><span><b><?php echo esc_html( cvc_growth_pct( $m['percentage'] ) ); ?></b><small>Bạn (lượt tốt nhất)</small></span><em>vs</em><span><b><?php echo esc_html( cvc_growth_pct( $ch['percentage'] ) ); ?></b><small><?php echo esc_html( (string) $ch['name'] ); ?></small></span></p>
			<p class="cvc-thach-word"><?php echo esc_html( $word ); ?></p>
		<?php endif; ?>
		<?php if ( $mine ) : ?>
			<?php $link = add_query_arg( 'ref', (string) $mine['ref'], home_url( '/thach/' . $mine['code'] . '/' ) ); ?>
			<h2 class="side-title">Thách bạn bè làm cùng đề</h2>
			<p class="cvc-thach-note">Gửi link vào nhóm Zalo / Facebook ôn thi. Bạn bè thấy điểm <?php echo esc_html( cvc_growth_pct( $mine['percentage'] ) ); ?> của bạn và làm cùng đề để so điểm. Ai đăng ký qua link này, khi bắt đầu ôn thi cả hai nhận 7 ngày Gói ôn thi đầy đủ.</p>
			<?php cvc_share_buttons( $link, 'Mình đạt ' . cvc_growth_pct( $mine['percentage'] ) . ' đề "' . (string) $mine['exam']['title'] . '" - bạn được bao nhiêu?', 'kq' ); ?>
			<a class="cvc-thach-card" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( cvc_public_api_url() . '/api/challenges/' . rawurlencode( (string) $mine['code'] ) . '/card' ); ?>" alt="Ảnh thách đấu: <?php echo esc_attr( cvc_growth_pct( $mine['percentage'] ) ); ?>" loading="lazy" width="600" height="315"></a>
		<?php endif; ?>
	</div></section>
	<style>.cvc-thach-vs{display:flex;align-items:center;justify-content:space-around;gap:10px;margin:8px 0}.cvc-thach-vs span{display:grid;text-align:center}.cvc-thach-vs b{font-size:28px;color:#123a63}.cvc-thach-vs small{color:#5c6672}.cvc-thach-vs em{font-style:normal;font-weight:800;color:#c9a052}.cvc-thach-word{text-align:center;font-weight:800;color:#1767aa;margin:0 0 12px}.cvc-thach-note{font-size:14px;color:#4a5563;margin:6px 0}.cvc-thach-card img{display:block;width:100%;height:auto;border-radius:10px;margin-top:8px}</style>
	<?php
}

/* ---------- Nhóm ôn thi: thao tác ---------- */
add_action( 'admin_post_cvc_group', 'cvc_growth_handle_group' );
add_action( 'admin_post_nopriv_cvc_group', 'cvc_growth_handle_group' );

function cvc_growth_handle_group(): void {
	check_admin_referer( 'cvc_group' );
	$op   = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) ( $_POST['code'] ?? '' ) ) );
	$back = '' !== $code ? home_url( '/nhom-on-thi/' . $code . '/' ) : home_url( '/nhom-on-thi/' );
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	if ( ! $token ) {
		wp_safe_redirect( cvc_login_url( $back ) );
		exit;
	}
	$api = new CVC_Api_Client( null, 10 );
	if ( 'create' === $op ) {
		$r    = $api->post( '/api/groups', array( 'name' => sanitize_text_field( wp_unslash( (string) ( $_POST['name'] ?? '' ) ) ) ), $token );
		$code = (string) ( $r['data']['data']['code'] ?? '' );
		$back = '' !== $code ? home_url( '/nhom-on-thi/' . $code . '/' ) : home_url( '/nhom-on-thi/' );
	} elseif ( in_array( $op, array( 'join', 'leave' ), true ) && '' !== $code ) {
		$r = $api->post( '/api/groups/' . rawurlencode( $code ) . '/' . $op, array(), $token );
	} else {
		$r = array( 'ok' => false, 'data' => array( 'message' => 'Thao tác không hợp lệ.' ) );
	}
	cvc_redirect_with_notice( $back, ! empty( $r['ok'] ) ? 'success' : 'error', (string) ( $r['data']['message'] ?? cvc_api_error_message( $r ) ), null );
}
