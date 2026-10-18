<?php
/**
 * Danh mục Ôn thi - topics (18/10/2026, thiết kế exam-catalog-v1). Dữ liệu thật từ /api/catalog/topics.
 * URL: /chu-de/ và /chu-de/page/{n}/ (lọc bằng tham số GET; phần hiển thị nằm ở inc/catalog.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_cat_page = max( 1, absint( get_query_var( 'cvc_paged' ) ) );

cvc_seo_set_title( 'Chủ đề ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Chủ đề ôn thi theo môn và văn bản pháp luật gốc, gom mục kiến thức trọng tâm và câu hỏi luyện tập, theo dõi tỷ lệ đúng từng chủ đề.' );
cvc_seo_set_listing_pagination_state( $cvc_cat_page, 'cvc_topics_url' );
if ( cvc_cat_filter_count( cvc_cat_filters( 'topics', $_GET ) ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification
	cvc_seo_set_noindex();
}

get_header();
cvc_cat_render_page( 'topics' );
get_footer();
