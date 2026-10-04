<?php
/**
 * Trang Cấu hình AI (Phase 11) - nhập tay provider + API key, LƯU QUA
 * Laravel (Setting model, mã hoá tại chỗ) - không bao giờ hard-code hay
 * nhận key qua chat/commit. Chỉ SUPER_ADMIN/ADMIN mới xem/sửa được
 * (role đọc lại từ chính Sanctum user - KHÔNG tạo tầng quyền riêng ở WP).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_user_is_admin(): bool {
	$user = cvc_current_user();

	if ( null === $user ) {
		return false;
	}

	$roles = $user['roles'] ?? array();

	return is_array( $roles )
		&& ( in_array( 'SUPER_ADMIN', $roles, true ) || in_array( 'ADMIN', $roles, true ) );
}

/*
 * Platform user KHÔNG BAO GIỜ đăng nhập WordPress core (xem inc/actions.php)
 * nên phải đăng ký cả admin_post_ và admin_post_nopriv_ để handler chạy
 * được cho user thật - giống mọi action khác trong theme.
 */
add_action( 'admin_post_nopriv_cvc_ai_settings_save', 'cvc_handle_ai_settings_save' );
add_action( 'admin_post_cvc_ai_settings_save', 'cvc_handle_ai_settings_save' );

function cvc_handle_ai_settings_save(): void {
	check_admin_referer( 'cvc_ai_settings_save' );
	$token = cvc_require_token_or_die();

	if ( ! cvc_user_is_admin() ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'cvc' ), '', array( 'response' => 403 ) );
	}

	$values    = array();
	$providers = array( 'deepseek', 'qwen', 'openai', 'gemini', 'claude' );

	// Key từng nhà cung cấp: để trống = giữ nguyên key đang lưu; tick "Xóa key" = xóa.
	foreach ( $providers as $p ) {
		$raw = isset( $_POST[ 'ai_key_' . $p ] ) ? trim( (string) wp_unslash( $_POST[ 'ai_key_' . $p ] ) ) : '';
		if ( '' !== $raw ) {
			$values[ 'ai.key.' . $p ] = sanitize_text_field( $raw );
		} elseif ( ! empty( $_POST[ 'ai_key_clear_' . $p ] ) ) {
			$values[ 'ai.key.' . $p ] = '';
		}
	}

	if ( isset( $_POST['ai_provider'] ) ) {
		$values['ai.provider'] = sanitize_key( wp_unslash( $_POST['ai_provider'] ) );
	}
	$values['ai.model'] = cvc_ai_posted_model( 'ai_model' );

	if ( isset( $_POST['ai_doc_provider'] ) ) {
		$values['ai.doc_provider'] = sanitize_key( wp_unslash( $_POST['ai_doc_provider'] ) );
	}
	$values['ai.doc_model'] = cvc_ai_posted_model( 'ai_doc_model' );

	if ( isset( $_POST['ai_qwen_base_url'] ) ) {
		$values['ai.qwen_base_url'] = esc_url_raw( trim( (string) wp_unslash( $_POST['ai_qwen_base_url'] ) ) );
	}
	foreach ( array( 'ai_budget_vnd_month' => 'ai.budget_vnd_month', 'ai_usd_vnd_manual' => 'ai.usd_vnd_manual' ) as $field => $key ) {
		if ( isset( $_POST[ $field ] ) ) {
			$num            = preg_replace( '/[^\d.]/', '', str_replace( ',', '.', str_replace( '.', '', (string) wp_unslash( $_POST[ $field ] ) ) ) );
			$values[ $key ] = '' === $num ? '' : $num;
		}
	}

	$result = ( new CVC_Setting_Service() )->update( 'ai', $values, $token );

	if ( ! $result['ok'] ) {
		$message = ! empty( $result['error'] ) ? (string) $result['error'] : cvc_api_error_message( $result );
		cvc_redirect_with_notice( cvc_admin_ai_settings_url(), 'error', $message, null );
		return;
	}

	cvc_redirect_with_notice( cvc_admin_ai_settings_url(), 'success', 'Đã lưu cấu hình AI.', null );
}

/**
 * Model chọn trên form: ô chọn danh sách, hoặc "Nhập tên khác" -> ô nhập tay.
 */
function cvc_ai_posted_model( string $field ): string {
	$selected = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
	if ( '__custom' === $selected ) {
		$selected = isset( $_POST[ $field . '_custom' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field . '_custom' ] ) ) : '';
	}

	return trim( $selected );
}

/**
 * Client API thời gian chờ dài cho thao tác gọi ra ngoài (tải model, cập nhật giá).
 */
function cvc_ai_setting_service( int $timeout = 60 ): CVC_Setting_Service {
	return new CVC_Setting_Service( new CVC_Api_Client( null, $timeout ) );
}

function cvc_ai_admin_guard( string $nonce ): string {
	check_admin_referer( $nonce );
	$token = cvc_require_token_or_die();

	if ( ! cvc_user_is_admin() ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'cvc' ), '', array( 'response' => 403 ) );
	}

	return $token;
}

function cvc_ai_back( string $type, string $message, string $anchor = '' ): void {
	cvc_redirect_with_notice( cvc_admin_ai_settings_url() . $anchor, $type, $message, null );
}

add_action( 'admin_post_nopriv_cvc_ai_models_refresh', 'cvc_handle_ai_models_refresh' );
add_action( 'admin_post_cvc_ai_models_refresh', 'cvc_handle_ai_models_refresh' );

function cvc_handle_ai_models_refresh(): void {
	$token  = cvc_ai_admin_guard( 'cvc_ai_models_refresh' );
	$result = cvc_ai_setting_service( 90 )->ai_refresh_models( $token );

	if ( ! $result['ok'] ) {
		cvc_ai_back( 'error', 'Không làm mới được danh sách model: ' . cvc_api_error_message( $result ) );
		return;
	}

	$parts = array();
	foreach ( (array) ( $result['data']['data'] ?? array() ) as $p => $r ) {
		$parts[] = $p . ': ' . (int) ( $r['count'] ?? 0 ) . ( 'api' === ( $r['source'] ?? '' ) ? ' (API)' : ' (gợi ý)' );
	}
	cvc_ai_back( 'success', 'Đã làm mới danh sách model — ' . implode( ', ', $parts ) . '.' );
}

add_action( 'admin_post_nopriv_cvc_ai_prices_refresh', 'cvc_handle_ai_prices_refresh' );
add_action( 'admin_post_cvc_ai_prices_refresh', 'cvc_handle_ai_prices_refresh' );

function cvc_handle_ai_prices_refresh(): void {
	$token  = cvc_ai_admin_guard( 'cvc_ai_prices_refresh' );
	$result = cvc_ai_setting_service( 90 )->ai_refresh_prices( $token );

	if ( ! $result['ok'] ) {
		cvc_ai_back( 'error', 'Không cập nhật được giá: ' . cvc_api_error_message( $result ), '#bang-gia' );
		return;
	}

	$d     = (array) ( $result['data']['data'] ?? array() );
	$parts = array();
	$parts[] = ! empty( $d['rate']['ok'] )
		? 'Tỷ giá ' . number_format( (float) $d['rate']['rate'], 0, ',', '.' ) . ' ₫/USD (' . $d['rate']['source'] . ')'
		: 'Tỷ giá lỗi: ' . ( $d['rate']['error'] ?? '' );
	$parts[] = ! empty( $d['deepseek']['ok'] ) ? 'DeepSeek: ' . (int) $d['deepseek']['updated'] . ' model' : 'DeepSeek lỗi: ' . ( $d['deepseek']['error'] ?? '' );
	$parts[] = ! empty( $d['openrouter']['ok'] )
		? 'OpenRouter: ' . (int) $d['openrouter']['updated'] . ' model' . ( ! empty( $d['openrouter']['unmatched'] ) ? ', chưa khớp giá: ' . implode( ', ', array_slice( (array) $d['openrouter']['unmatched'], 0, 5 ) ) : '' )
		: 'OpenRouter lỗi: ' . ( $d['openrouter']['error'] ?? '' );

	cvc_ai_back( 'success', 'Đã cập nhật giá — ' . implode( ' · ', $parts ) . '.', '#bang-gia' );
}

add_action( 'admin_post_nopriv_cvc_ai_price_save', 'cvc_handle_ai_price_save' );
add_action( 'admin_post_cvc_ai_price_save', 'cvc_handle_ai_price_save' );

function cvc_handle_ai_price_save(): void {
	$token = cvc_ai_admin_guard( 'cvc_ai_price_save' );
	$num   = static function ( string $field ): string {
		$v = isset( $_POST[ $field ] ) ? trim( str_replace( ',', '.', (string) wp_unslash( $_POST[ $field ] ) ) ) : '';
		return preg_replace( '/[^\d.]/', '', $v );
	};

	$body = array(
		'provider' => isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '',
		'model'    => isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '',
	);

	if ( ! empty( $_POST['reset'] ) ) {
		$body['reset'] = true;
	} else {
		$body['input_per_m']        = $num( 'input_per_m' );
		$body['cached_input_per_m'] = $num( 'cached_input_per_m' );
		$body['output_per_m']       = $num( 'output_per_m' );
	}

	$result = cvc_ai_setting_service( 60 )->ai_save_price( $body, $token );

	if ( ! $result['ok'] ) {
		cvc_ai_back( 'error', 'Không lưu được giá: ' . cvc_api_error_message( $result ), '#bang-gia' );
		return;
	}

	cvc_ai_back( 'success', (string) ( $result['data']['message'] ?? 'Đã lưu giá.' ) . ' (' . $body['provider'] . ' / ' . $body['model'] . ')', '#bang-gia' );
}

add_action( 'admin_post_nopriv_cvc_ai_test', 'cvc_handle_ai_test' );
add_action( 'admin_post_cvc_ai_test', 'cvc_handle_ai_test' );

function cvc_handle_ai_test(): void {
	$token    = cvc_ai_admin_guard( 'cvc_ai_test' );
	$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
	$model    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
	$result   = cvc_ai_setting_service( 45 )->ai_test( $provider, $model, $token );
	$d        = (array) ( $result['data']['data'] ?? array() );

	if ( empty( $d ) ) {
		cvc_ai_back( 'error', 'Không kiểm tra được: ' . cvc_api_error_message( $result ), '#khoa-api' );
		return;
	}

	$label = ( $d['provider'] ?? $provider ) . ' / ' . ( $d['model'] ?? $model );
	if ( ! empty( $d['reply'] ) ) {
		cvc_ai_back( 'success', 'Kết nối ' . $label . ' hoạt động — trả lời "' . $d['reply'] . '" sau ' . round( ( (int) ( $d['duration_ms'] ?? 0 ) ) / 1000, 1 ) . ' giây.', '#khoa-api' );
		return;
	}

	$error = (string) ( $d['error'] ?? 'không rõ' );
	$map   = array(
		'not_configured'  => 'chưa nhập API key',
		'budget_exceeded' => 'đã vượt hạn mức chi phí tháng',
		'empty_response'  => 'nhà cung cấp trả về nội dung rỗng',
	);
	cvc_ai_back( 'error', 'Kết nối ' . $label . ' lỗi: ' . ( $map[ $error ] ?? $error ), '#khoa-api' );
}
