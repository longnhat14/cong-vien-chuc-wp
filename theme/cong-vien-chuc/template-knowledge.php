<?php
/**
 * Danh mục Ôn thi - knowledge (18/10/2026, thiết kế exam-catalog-v1). Dữ liệu thật từ /api/catalog/knowledge.
 * URL: /kien-thuc/ và /kien-thuc/page/{n}/ (lọc bằng tham số GET; phần hiển thị nằm ở inc/catalog.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_cat_page = max( 1, absint( get_query_var( 'cvc_paged' ) ) );

cvc_seo_set_title( 'Kiến thức trọng tâm ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Tóm tắt điều, khoản trọng tâm của văn bản pháp luật gốc kèm câu hỏi luyện tập; lọc theo loại văn bản và nhóm kiến thức.' );
cvc_seo_set_listing_pagination_state( $cvc_cat_page, 'cvc_knowledge_url' );
if ( cvc_cat_filter_count( cvc_cat_filters( 'knowledge', $_GET ) ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification
	cvc_seo_set_noindex();
}

get_header();
cvc_cat_render_page( 'knowledge' );
get_footer();
