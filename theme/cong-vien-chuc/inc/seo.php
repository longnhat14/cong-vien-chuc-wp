<?php
/**
 * SEO helpers dùng chung cho các trang domain tự render qua template_include.
 * Các trang này không dùng WP Page/Post nên không có sẵn document title /
 * meta description mặc định - template phải tự khai báo trước khi gọi
 * get_header().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['cvc_seo'] = array(
	'title'       => null,
	'description' => null,
	'canonical'   => null,
	'noindex'     => false,
	'prev_url'    => null,
	'next_url'    => null,
);

function cvc_seo_set_title( string $title ): void {
	$GLOBALS['cvc_seo']['title'] = $title;
}

function cvc_seo_set_description( ?string $description ): void {
	$GLOBALS['cvc_seo']['description'] = $description ? cvc_seo_trim( $description ) : null;
}

function cvc_seo_set_canonical( string $url ): void {
	$GLOBALS['cvc_seo']['canonical'] = $url;
}

function cvc_seo_set_noindex( bool $noindex = true ): void {
	$GLOBALS['cvc_seo']['noindex'] = $noindex;
}

function cvc_seo_set_pagination_links( ?string $prev_url, ?string $next_url ): void {
	$GLOBALS['cvc_seo']['prev_url'] = $prev_url;
	$GLOBALS['cvc_seo']['next_url'] = $next_url;
}

/**
 * Rút gọn text thuần (không HTML) cho meta description, không bịa nội dung -
 * chỉ cắt bớt dữ liệu thật lấy từ API.
 */
function cvc_seo_trim( string $text, int $length = 160 ): string {
	$text = wp_strip_all_tags( $text );
	$text = trim( preg_replace( '/\s+/', ' ', $text ) );

	if ( '' === $text ) {
		return '';
	}

	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > $length ) {
		return rtrim( mb_substr( $text, 0, $length ) ) . '…';
	}

	if ( strlen( $text ) > $length ) {
		return rtrim( substr( $text, 0, $length ) ) . '…';
	}

	return $text;
}

add_filter( 'pre_get_document_title', 'cvc_seo_filter_title' );

function cvc_seo_filter_title( string $title ): string {
	if ( ! empty( $GLOBALS['cvc_seo']['title'] ) ) {
		return $GLOBALS['cvc_seo']['title'] . ' - ' . get_bloginfo( 'name' );
	}

	return $title;
}

add_action( 'wp_head', 'cvc_seo_output_meta', 1 );

function cvc_seo_output_meta(): void {
	$seo = $GLOBALS['cvc_seo'];

	if ( ! empty( $seo['description'] ) ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $seo['description'] ) );
	}

	if ( ! empty( $seo['canonical'] ) ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $seo['canonical'] ) );
	}

	if ( ! empty( $seo['prev_url'] ) ) {
		printf( '<link rel="prev" href="%s">' . "\n", esc_url( $seo['prev_url'] ) );
	}

	if ( ! empty( $seo['next_url'] ) ) {
		printf( '<link rel="next" href="%s">' . "\n", esc_url( $seo['next_url'] ) );
	}

	if ( ! empty( $seo['noindex'] ) ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}
