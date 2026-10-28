<?php
/**
 * Sitemap tin tuyển dụng (19/10/2026): /sitemap-tuyen-dung.xml - trang thông báo + trang vị trí
 * (chỉ URL canonical: vị trí riêng khi thông báo có >= 2 vị trí cụ thể), lấy từ GET /api/sitemap/recruitments.
 * Rank Math chỉ tạo sitemap bài viết/chuyên mục -> thêm file này vào sitemap_index.xml (filter rank_math/sitemap/index).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'cvc_sitemap_render', 0 );
add_filter( 'rank_math/sitemap/index', 'cvc_sitemap_rank_math_index', 11 );

/** @return array{notices: array, positions: array, generated_at: string}|null */
function cvc_sitemap_recruitment_data(): ?array {
	$cached = get_transient( 'cvc_sitemap_rec' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$res = ( new CVC_Api_Client( null, 20 ) )->get( '/api/sitemap/recruitments' );
	if ( empty( $res['ok'] ) || ! is_array( $res['data']['data'] ?? null ) ) {
		return null;
	}
	$data = array(
		'notices'      => array_values( array_filter( (array) ( $res['data']['data']['notices'] ?? array() ), 'is_array' ) ),
		'positions'    => array_values( array_filter( (array) ( $res['data']['data']['positions'] ?? array() ), 'is_array' ) ),
		'generated_at' => (string) ( $res['data']['data']['generated_at'] ?? '' ),
	);
	set_transient( 'cvc_sitemap_rec', $data, 30 * MINUTE_IN_SECONDS );

	return $data;
}

function cvc_sitemap_lastmod( array $rows ): string {
	$max = '';
	foreach ( $rows as $r ) {
		$m = (string) ( $r['lastmod'] ?? '' );
		if ( '' !== $m && strtotime( $m ) > strtotime( $max ?: '1970-01-01' ) ) {
			$max = $m;
		}
	}

	return $max;
}

function cvc_sitemap_render(): void {
	if ( 'tuyen-dung' !== (string) get_query_var( 'cvc_sitemap' ) ) {
		return;
	}
	$data = cvc_sitemap_recruitment_data();
	if ( null === $data ) {
		status_header( 503 );
		header( 'Retry-After: 600' );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'Sitemap tạm thời chưa tạo được, vui lòng thử lại sau.';
		exit;
	}

	$rows = array(
		array( 'path' => '/tuyen-dung/', 'lastmod' => cvc_sitemap_lastmod( $data['notices'] ) ),
		array( 'path' => '/tuyen-dung/vi-tri/', 'lastmod' => cvc_sitemap_lastmod( $data['positions'] ) ),
		// 28/10/2026: trang đề riêng theo tin (đổi theo tin mới).
		array( 'path' => '/luyen-thi-trac-nghiem/', 'lastmod' => cvc_sitemap_lastmod( $data['notices'] ) ),
		array( 'path' => '/thi-thu-trac-nghiem/', 'lastmod' => cvc_sitemap_lastmod( $data['notices'] ) ),
	);
	// 28/10/2026: trang theo đối tượng (ôn thi) và theo loại (tuyển dụng).
	foreach ( function_exists( 'cvc_cat_tracks' ) ? cvc_cat_tracks() : array() as $t ) {
		$rows[] = array( 'path' => '/' . $t['path'] . '/', 'lastmod' => cvc_sitemap_lastmod( $data['notices'] ) );
	}
	foreach ( array_keys( function_exists( 'cvc_rec_presets' ) ? cvc_rec_presets() : array() ) as $k ) {
		$rows[] = array( 'path' => '/tuyen-dung/' . $k . '/', 'lastmod' => cvc_sitemap_lastmod( $data['notices'] ) );
	}
	$rows = array_merge( $rows, $data['notices'], $data['positions'] );

	status_header( 200 );
	header( 'Content-Type: application/xml; charset=utf-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	header( 'Cache-Control: public, max-age=1800' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	$seen = array();
	foreach ( $rows as $r ) {
		$path = (string) ( $r['path'] ?? '' );
		if ( '' === $path || '/' !== $path[0] || isset( $seen[ $path ] ) ) {
			continue;
		}
		$seen[ $path ] = true;
		echo "\t<url><loc>" . esc_url( home_url( $path ) ) . '</loc>';
		if ( ! empty( $r['lastmod'] ) && false !== strtotime( (string) $r['lastmod'] ) ) {
			echo '<lastmod>' . esc_html( gmdate( 'c', (int) strtotime( (string) $r['lastmod'] ) ) ) . '</lastmod>';
		}
		echo "</url>\n";
	}
	echo '</urlset>';
	exit;
}

/** Thêm sitemap tuyển dụng vào sitemap_index.xml của Rank Math. */
function cvc_sitemap_rank_math_index( $xml ) {
	$data    = cvc_sitemap_recruitment_data();
	$lastmod = $data ? cvc_sitemap_lastmod( array_merge( $data['notices'], $data['positions'] ) ) : '';
	$xml     = (string) $xml;
	$xml    .= "\t<sitemap>\n\t\t<loc>" . esc_url( home_url( '/sitemap-tuyen-dung.xml' ) ) . "</loc>\n"
		. ( '' !== $lastmod ? "\t\t<lastmod>" . esc_html( gmdate( 'c', (int) strtotime( $lastmod ) ) ) . "</lastmod>\n" : '' )
		. "\t</sitemap>\n";

	return $xml;
}
