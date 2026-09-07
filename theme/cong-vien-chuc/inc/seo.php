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
	'og'          => array(),
	'json_ld'     => array(),
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
 * Override Open Graph riêng cho trang hiện tại. Không truyền key nào thì
 * dùng fallback từ title/description/canonical đã set (xem
 * cvc_seo_output_meta()) - phần lớn trang domain không cần gọi hàm này,
 * chỉ trang cần og:type khác 'website' hoặc og:image mới cần.
 *
 * @param array{title?: string, description?: string, url?: string, type?: string, image?: string} $og
 */
function cvc_seo_set_og( array $og ): void {
	$GLOBALS['cvc_seo']['og'] = array_merge( $GLOBALS['cvc_seo']['og'], $og );
}

/**
 * Thêm 1 khối JSON-LD vào trang hiện tại (có thể gọi nhiều lần - mỗi lần
 * xuất ra 1 <script> riêng). Không có cơ chế de-dup theo thiết kế - caller
 * tự đảm bảo không gọi trùng cùng 1 schema (xem cvc_seo_add_breadcrumb_jsonld
 * và cvc_build_recruitment_job_posting_jsonld() làm ví dụ, mỗi cái chỉ nên
 * được gọi đúng 1 lần cho 1 lần render trang).
 *
 * @param array<string, mixed> $schema
 */
function cvc_seo_add_json_ld( array $schema ): void {
	$GLOBALS['cvc_seo']['json_ld'][] = $schema;
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

	/*
	 * Open Graph - fallback về title/description/canonical đã set cho
	 * trang, không cần domain nào gọi lại các field trùng lặp. Áp dụng
	 * cho MỌI trang domain hiện có (không riêng Recruitment) vì đều đi
	 * qua cùng cvc_seo_set_title()/cvc_seo_set_description() - đúng tinh
	 * thần "tái sử dụng mechanism hiện tại", không phá OG domain nào vì
	 * trước đó chưa domain nào có OG.
	 */
	$og_title       = $seo['og']['title'] ?? $seo['title'];
	$og_description = $seo['og']['description'] ?? $seo['description'];
	$og_url         = $seo['og']['url'] ?? $seo['canonical'];
	$og_type        = $seo['og']['type'] ?? 'website';
	$og_image       = $seo['og']['image'] ?? null;

	if ( ! empty( $og_title ) ) {
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $og_title ) );
	}

	if ( ! empty( $og_description ) ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $og_description ) );
	}

	if ( ! empty( $og_url ) ) {
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $og_url ) );
	}

	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $og_type ) );

	if ( ! empty( $og_image ) ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $og_image ) );
	}

	/*
	 * JSON-LD - mỗi phần tử là 1 schema độc lập, xuất thành 1 <script>
	 * riêng. wp_json_encode() escape đúng UTF-8/tiếng Việt; tự thay thế
	 * "</script" phòng trường hợp dữ liệu người dùng (title/summary...)
	 * chứa chuỗi này, tránh phá vỡ trang.
	 */
	foreach ( $seo['json_ld'] as $schema ) {
		if ( empty( $schema ) ) {
			continue;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( false === $json ) {
			continue;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			str_replace( '</script', '<\/script', $json )
		);
	}
}
