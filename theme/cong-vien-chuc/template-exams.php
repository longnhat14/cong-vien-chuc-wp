<?php
/**
 * Danh mục Ôn thi - exams (18/10/2026, thiết kế exam-catalog-v1). Dữ liệu thật từ /api/catalog/exams.
 * URL: /thi-trac-nghiem/ và /thi-trac-nghiem/page/{n}/ (lọc bằng tham số GET; phần hiển thị nằm ở inc/catalog.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_cat_page = max( 1, absint( get_query_var( 'cvc_paged' ) ) );

cvc_seo_set_title( 'Đề thi thử trắc nghiệm công chức, viên chức miễn phí' );
cvc_seo_set_description( 'Làm đề thi thử trắc nghiệm công chức, viên chức miễn phí: lọc theo nhóm kiến thức, thời lượng; chấm điểm ngay khi nộp, xem giải thích và căn cứ pháp lý từng câu.' );
cvc_seo_set_listing_pagination_state( $cvc_cat_page, 'cvc_exams_url' );
if ( cvc_cat_filter_count( cvc_cat_filters( 'exams', $_GET ) ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification
	cvc_seo_set_noindex();
}

get_header();
cvc_cat_render_page( 'exams' );
get_footer();
