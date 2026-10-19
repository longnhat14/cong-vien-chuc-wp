<?php
/**
 * Trang chủ v17 (16/10/2026) - dựng theo file thiết kế
 * congvienchuc-homepage-redesign-v17: trang độc lập (header/footer riêng
 * của thiết kế), chỉ nạp assets/css/home-v17.css + assets/js/home-v17.js.
 *
 * Dữ liệu thật:
 *  - GET  /api/homepage/overview  (số liệu, dòng tin chạy, đề thi, tin
 *    tuyển dụng, tỉnh/thành có tin, số tài liệu theo định dạng, khóa học)
 *  - AJAX cvc_home_jobs  -> GET  /api/recruitments?province_id=…
 *  - AJAX cvc_ai_coach   -> POST /api/ai/coach
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dữ liệu trang chủ. Lỗi mạng -> thử lại 1 lần với thời gian chờ dài hơn;
 * vẫn lỗi -> trả mảng rỗng, template tự ẩn phần không có dữ liệu.
 *
 * @return array<string, mixed>
 */
function cvc_home17_data(): array {
	static $memo = null;

	if ( null !== $memo ) {
		return $memo;
	}

	$result = ( new CVC_Api_Client( null, 8 ) )->get( '/api/homepage/overview' );

	if ( ! $result['ok'] && 0 === (int) $result['status'] ) {
		$result = ( new CVC_Api_Client( null, 20 ) )->get( '/api/homepage/overview', array( 'retry' => 1 ) );
	}

	$data = ( $result['ok'] && is_array( $result['data']['data'] ?? null ) ) ? $result['data']['data'] : array();

	$memo = array_merge(
		array(
			'counts'           => array(),
			'exams'            => array(),
			'recruitments'     => array(),
			'legal_documents'  => array(),
			'provinces'        => array(),
			'document_formats' => array(),
			'courses'          => array( 'sellable' => array(), 'coming_soon' => array() ),
		),
		$data
	);

	return $memo;
}

/**
 * Đúng trang chủ. Các route của theme (cvc_page) không gắn với post/page
 * nên WP coi là "home" -> is_front_page() trả true trên MỌI trang domain;
 * phải loại trừ cvc_page.
 */
function cvc_is_home17(): bool {
	return is_front_page() && '' === (string) get_query_var( 'cvc_page' ) && ! is_search() && ! is_404();
}

/** dd/mm/YYYY từ chuỗi ngày ISO; rỗng nếu không đọc được. */
function cvc_home17_date( ?string $iso ): string {
	if ( empty( $iso ) ) {
		return '';
	}
	$ts = strtotime( $iso );

	return $ts ? wp_date( 'd/m/Y', $ts ) : '';
}

/** Rút gọn chuỗi theo số ký tự (UTF-8), thêm dấu … khi bị cắt. */
function cvc_home17_trim( ?string $text, int $max ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );

	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}

	return rtrim( mb_substr( $text, 0, $max - 1 ) ) . '…';
}

/** Định dạng tiền VNĐ. */
function cvc_home17_money( $amount ): string {
	return number_format( (float) $amount, 0, ',', '.' ) . ' ₫';
}

/* ------------------------------------------------------------------
 * Tài nguyên: trang chủ chỉ dùng CSS/JS của thiết kế v17.
 * ------------------------------------------------------------------ */
add_action( 'wp_enqueue_scripts', 'cvc_home17_assets', 100 );

function cvc_home17_assets(): void {
	if ( ! cvc_is_home17() ) {
		return;
	}

	// style.css + script menu cũ + JS trang chủ cũ không dùng trên trang này.
	wp_dequeue_style( 'cvc-style' );
	wp_dequeue_script( 'cvc-nav' );
	wp_dequeue_script( 'cvc-homepage-nextgen' );

	$css = get_theme_file_path( '/assets/css/home-v17.css' );
	$js  = get_theme_file_path( '/assets/js/home-v17.js' );

	wp_enqueue_style( 'cvc-home-v17', get_theme_file_uri( '/assets/css/home-v17.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-home-v17', get_theme_file_uri( '/assets/js/home-v17.js' ), array(), (string) filemtime( $js ), true );

	if ( ! empty( $GLOBALS['cvc_home17_cfg'] ) ) {
		wp_add_inline_script( 'cvc-home-v17', 'window.CVC_HOME=' . wp_json_encode( $GLOBALS['cvc_home17_cfg'] ) . ';', 'before' );
	}
}

/* ------------------------------------------------------------------
 * AJAX: tin tuyển dụng đang nhận hồ sơ theo tỉnh/thành ("Việc làm quanh bạn")
 * Chỉ đọc dữ liệu công khai -> không bắt buộc nonce (trang chủ được
 * LiteSpeed cache, nonce trong HTML có thể đã quá hạn).
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_home_jobs', 'cvc_home17_ajax_jobs' );
add_action( 'wp_ajax_nopriv_cvc_home_jobs', 'cvc_home17_ajax_jobs' );

function cvc_home17_ajax_jobs(): void {
	$province_id = isset( $_POST['province_id'] ) ? absint( $_POST['province_id'] ) : 0;

	if ( $province_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'Thiếu tỉnh/thành.' ), 422 );
	}

	$cache_key = 'cvc_home17_jobs_' . $province_id;
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		wp_send_json_success( array( 'items' => $cached ) );
	}

	$result = ( new CVC_Api_Client( null, 10 ) )->get(
		'/api/recruitments',
		array(
			'province_id' => $province_id,
			'per_page'    => 8,
		)
	);

	if ( ! $result['ok'] ) {
		wp_send_json_error( array( 'message' => 'Chưa tải được tin tuyển dụng.' ), 502 );
	}

	$rows = $result['data']['data']['data'] ?? $result['data']['data'] ?? array();
	$rows = is_array( $rows ) ? $rows : array();

	$items = array();
	$today = wp_date( 'Y-m-d' );
	foreach ( $rows as $row ) {
		if ( count( $items ) >= 3 ) {
			break;
		}
		if ( ! is_array( $row ) || empty( $row['slug'] ) ) {
			continue;
		}
		// Chỉ tin còn hạn nộp hồ sơ (không hiện tin hết hạn như đang tuyển).
		$deadline = substr( (string) ( $row['application_deadline'] ?? $row['dates']['application_deadline'] ?? '' ), 0, 10 );
		if ( '' !== $deadline && $deadline < $today ) {
			continue;
		}
		$agency = $row['agency'] ?? '';
		if ( is_array( $agency ) ) {
			$agency = $agency['name'] ?? '';
		}
		$items[] = array(
			'title'     => cvc_home17_trim( $row['title'] ?? '', 140 ),
			'url'       => cvc_recruitment_url( (string) $row['slug'] ),
			'agency'    => cvc_home17_trim( (string) $agency, 60 ),
			'deadline'  => cvc_home17_date( '' !== $deadline ? $deadline : null ),
			'positions' => isset( $row['total_positions'] ) && (int) $row['total_positions'] > 0 ? (int) $row['total_positions'] : null,
		);
	}

	set_transient( $cache_key, $items, 10 * MINUTE_IN_SECONDS );
	wp_send_json_success( array( 'items' => $items ) );
}

/* ------------------------------------------------------------------
 * AJAX: AI Coach - chuyển câu hỏi sang /api/ai/coach. Giới hạn số lượt
 * do backend quản lý theo IP thật của người dùng (X-CVC-Client-IP).
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_ai_coach', 'cvc_home17_ajax_coach' );
add_action( 'wp_ajax_nopriv_cvc_ai_coach', 'cvc_home17_ajax_coach' );

function cvc_home17_ajax_coach(): void {
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$message = trim( $message );

	if ( mb_strlen( $message ) < 2 ) {
		wp_send_json_error( array( 'message' => 'Hãy nhập câu hỏi cụ thể hơn.' ), 422 );
	}

	$history = array();
	$raw     = isset( $_POST['history'] ) ? json_decode( wp_unslash( (string) $_POST['history'] ), true ) : null;
	if ( is_array( $raw ) ) {
		foreach ( array_slice( $raw, -6 ) as $turn ) {
			if ( ! is_array( $turn ) || ! in_array( $turn['role'] ?? '', array( 'user', 'bot' ), true ) ) {
				continue;
			}
			$history[] = array(
				'role' => $turn['role'],
				'text' => mb_substr( sanitize_textarea_field( (string) ( $turn['text'] ?? '' ) ), 0, 600 ),
			);
		}
	}

	$result = ( new CVC_Api_Client( null, 45 ) )->post(
		'/api/ai/coach',
		array_filter(
			array(
				'message' => mb_substr( $message, 0, 500 ),
				'history' => $history,
				'engine'  => isset( $_POST['engine'] ) ? sanitize_key( wp_unslash( $_POST['engine'] ) ) : '',
			),
			static fn( $v ) => '' !== $v
		),
		cvc_auth_token()
	);

	if ( ! $result['ok'] ) {
		$status = (int) $result['status'];
		$msg    = $result['data']['message'] ?? '';
		if ( 429 === $status ) {
			$msg = $msg ?: 'Bạn đã hỏi nhiều trong thời gian ngắn. Vui lòng thử lại sau ít phút.';
		} elseif ( 422 === $status ) {
			$msg = $msg ?: 'Câu hỏi chưa hợp lệ, vui lòng nhập lại.';
		} else {
			$msg = 'AI Coach đang bận, vui lòng thử lại sau hoặc dùng ô tìm kiếm.';
		}
		wp_send_json_error( array( 'message' => $msg ), $status >= 400 ? $status : 502 );
	}

	$data  = $result['data']['data'] ?? array();
	$links = array();
	foreach ( (array) ( $data['links'] ?? array() ) as $link ) {
		if ( is_array( $link ) && ! empty( $link['url'] ) && ! empty( $link['label'] ) ) {
			$url = (string) $link['url'];
			// Chỉ nhận đường dẫn nội bộ do backend dựng.
			if ( 0 !== strpos( $url, '/' ) || 0 === strpos( $url, '//' ) ) {
				continue;
			}
			$links[] = array(
				'label' => cvc_home17_trim( (string) $link['label'], 130 ),
				'url'   => home_url( $url ),
			);
		}
	}

	wp_send_json_success(
		array(
			'answer'        => (string) ( $data['answer'] ?? '' ),
			'links'         => array_slice( $links, 0, 4 ),
			'engine'        => is_array( $data['engine'] ?? null ) ? array( 'code' => sanitize_key( (string) ( $data['engine']['code'] ?? '' ) ), 'label' => mb_substr( (string) ( $data['engine']['label'] ?? '' ), 0, 30 ) ) : null,
			'fallback_from' => ! empty( $data['fallback_from'] ) ? mb_substr( (string) $data['fallback_from'], 0, 30 ) : null,
		)
	);
}
