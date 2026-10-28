<?php
/**
 * Danh mục Ôn thi - exams (18/10/2026, thiết kế exam-catalog-v1). Dữ liệu thật từ /api/catalog/exams.
 * URL: /thi-trac-nghiem/ và /thi-trac-nghiem/page/{n}/ (lọc bằng tham số GET; phần hiển thị nằm ở inc/catalog.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_cat_page = max( 1, absint( get_query_var( 'cvc_paged' ) ) );
$cvc_cat_mode = (string) get_query_var( 'cvc_exam_mode' );
$cvc_cat_mode = in_array( $cvc_cat_mode, array( 'on-tap', 'thi-that' ), true ) ? $cvc_cat_mode : '';
$cvc_cat_f    = cvc_cat_filters( 'exams', $_GET ); // phpcs:ignore WordPress.Security.NonceVerification

// 28/10/2026: /thi-trac-nghiem/?che-do=on-tap|thi-that -> trang riêng /luyen-thi-trac-nghiem/, /thi-thu-trac-nghiem/ (301, giữ bộ lọc).
if ( '' === $cvc_cat_mode && '' !== (string) ( $cvc_cat_f['che-do'] ?? '' ) ) {
	wp_safe_redirect( cvc_cat_url( 'exams', $cvc_cat_f, $cvc_cat_page ), 301 );
	exit;
}

$cvc_cat_track = (string) ( $cvc_cat_f['tuyen'] ?? '' );
$cvc_track_cfg = '' !== $cvc_cat_track ? ( cvc_cat_tracks()[ $cvc_cat_track ] ?? null ) : null;
if ( $cvc_track_cfg ) {
	cvc_seo_set_title( $cvc_track_cfg['title'] );
	cvc_seo_set_description( $cvc_track_cfg['desc'] );
} elseif ( 'on-tap' === $cvc_cat_mode ) {
	cvc_seo_set_title( 'Luyện thi trắc nghiệm công chức, viên chức theo tin tuyển dụng' );
	cvc_seo_set_description( 'Luyện thi trắc nghiệm công chức, viên chức: đề riêng theo từng tin tuyển dụng đang nhận hồ sơ và vị trí dự tuyển, xem đáp án, giải thích và căn cứ pháp luật ngay sau mỗi câu.' );
} elseif ( 'thi-that' === $cvc_cat_mode ) {
	cvc_seo_set_title( 'Thi thử trắc nghiệm công chức, viên chức như thi thật' );
	cvc_seo_set_description( 'Thi thử trắc nghiệm công chức, viên chức tính giờ như phòng thi: đề riêng theo tin tuyển dụng và vị trí, chấm điểm khi nộp bài, xem giải thích và xếp hạng.' );
} else {
	cvc_seo_set_title( 'Đề thi thử trắc nghiệm công chức, viên chức miễn phí' );
	cvc_seo_set_description( 'Làm đề thi thử trắc nghiệm công chức, viên chức miễn phí: lọc theo nhóm kiến thức, thời lượng; chấm điểm ngay khi nộp, xem giải thích và căn cứ pháp lý từng câu.' );
}
cvc_seo_set_listing_pagination_state( $cvc_cat_page, $cvc_track_cfg ? static fn ( int $p ): string => cvc_cat_track_url( $cvc_cat_track, $p ) : ( '' !== $cvc_cat_mode ? static fn ( int $p ): string => cvc_exams_mode_url( $cvc_cat_mode, $p ) : 'cvc_exams_url' ) );
if ( cvc_cat_filter_count( $cvc_cat_f ) > 0 || ! empty( $_GET['tinh'] ) || ! empty( $_GET['tin-trang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	cvc_seo_set_noindex();
}

get_header();
cvc_cat_render_page( 'exams' );
get_footer();
