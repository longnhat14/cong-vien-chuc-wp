<?php
/**
 * Gợi ý tìm kiếm khi đang gõ (28/10/2026).
 *  - Ô tìm kiếm có data-cvc-suggest (hộp tìm kiếm ở đầu trang + trang /tim-kiem/).
 *  - Trình duyệt gọi thẳng GET {API}/api/search/suggest?q=&prefix=1 (CORS mở, không qua WordPress).
 *  - Tiết kiệm tài nguyên: chỉ gọi khi gõ xong 1 chữ (dấu cách) hoặc ngừng gõ 0,6 giây, tối thiểu 2 ký tự,
 *    bỏ yêu cầu cũ khi gõ tiếp, nhớ kết quả trong trang, API cache 10 phút + giới hạn 90 lượt/phút/IP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Địa chỉ API công khai cho trình duyệt. */
function cvc_public_api_url(): string {
	if ( defined( 'CVC_API_PUBLIC_URL' ) && CVC_API_PUBLIC_URL ) {
		return rtrim( (string) CVC_API_PUBLIC_URL, '/' );
	}
	$base = function_exists( 'cvc_api_base_url' ) ? rtrim( cvc_api_base_url(), '/' ) : '';

	return str_starts_with( $base, 'https://' ) ? $base : 'https://api.congvienchuc.com';
}

add_action( 'wp_enqueue_scripts', 'cvc_search_suggest_assets', 20 );

function cvc_search_suggest_assets(): void {
	if ( is_admin() ) {
		return;
	}
	$js  = get_theme_file_path( '/assets/js/search-suggest.js' );
	$css = get_theme_file_path( '/assets/css/search-suggest.css' );
	wp_enqueue_style( 'cvc-suggest', get_theme_file_uri( '/assets/css/search-suggest.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-suggest', get_theme_file_uri( '/assets/js/search-suggest.js' ), array(), (string) filemtime( $js ), true );

	$urls   = array();
	$labels = array();
	$build  = array(
		'course'         => 'cvc_course_url',
		'topic'          => 'cvc_topic_url',
		'recruitment'    => 'cvc_recruitment_url',
		'knowledge'      => 'cvc_knowledge_item_url',
		'exam'           => 'cvc_exam_url',
		'legal_document' => 'cvc_legal_document_url',
	);
	foreach ( $build as $type => $fn ) {
		if ( function_exists( $fn ) ) {
			$urls[ $type ] = (string) call_user_func( $fn, '__SLUG__' );
		}
	}
	if ( function_exists( 'cvc_search_domains' ) ) {
		foreach ( cvc_search_domains() as $d ) {
			$labels[ (string) $d['result_type'] ] = (string) $d['label'];
		}
	}
	wp_localize_script(
		'cvc-suggest',
		'CVC_SUGGEST',
		array(
			'api'    => cvc_public_api_url() . '/api/search/suggest',
			'search' => cvc_search_url(),
			'urls'   => $urls,
			'labels' => $labels,
		)
	);
}
