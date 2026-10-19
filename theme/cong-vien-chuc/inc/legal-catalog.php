<?php
/**
 * Trang tổng hợp văn bản pháp luật v2 (19/10/2026): tải CSS/JS riêng cho /van-ban-phap-luat/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'cvc_legal_catalog_assets', 55 );

function cvc_legal_catalog_assets(): void {
	if ( 'legal-documents' !== (string) get_query_var( 'cvc_page' ) ) {
		return;
	}
	$css = get_theme_file_path( '/assets/css/legal-catalog.css' );
	$js  = get_theme_file_path( '/assets/js/legal-catalog.js' );
	wp_enqueue_style( 'cvc-legal-catalog', get_theme_file_uri( '/assets/css/legal-catalog.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-legal-catalog', get_theme_file_uri( '/assets/js/legal-catalog.js' ), array(), (string) filemtime( $js ), true );
}
