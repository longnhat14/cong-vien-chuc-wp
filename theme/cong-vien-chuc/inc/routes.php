<?php
/**
 * Rewrite rules + query vars + template dispatch cho các trang domain
 * (Courses, Topics, Recruitment, Knowledge, Exams, Legal Documents).
 * Mọi URL pretty của theme phải khai báo ở đây, không tạo WP Page/Post
 * giả cho các domain này.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Tăng số này khi thêm/sửa rewrite rule để buộc flush lại đúng 1 lần.
const CVC_REWRITE_VERSION = '3';

add_action( 'init', 'cvc_register_rewrite_rules' );

function cvc_register_rewrite_rules(): void {
	add_rewrite_rule(
		'^khoa-hoc/page/([0-9]+)/?$',
		'index.php?cvc_page=courses&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^khoa-hoc/([^/]+)/bai-hoc/([0-9]+)/?$',
		'index.php?cvc_page=course-lesson&cvc_course_slug=$matches[1]&cvc_lesson_id=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^khoa-hoc/([^/]+)/?$',
		'index.php?cvc_page=course-detail&cvc_course_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^khoa-hoc/?$',
		'index.php?cvc_page=courses',
		'top'
	);

	add_rewrite_rule(
		'^chu-de/page/([0-9]+)/?$',
		'index.php?cvc_page=topics&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^chu-de/([^/]+)/?$',
		'index.php?cvc_page=topic-detail&cvc_topic_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^chu-de/?$',
		'index.php?cvc_page=topics',
		'top'
	);

	add_rewrite_rule(
		'^tuyen-dung/page/([0-9]+)/?$',
		'index.php?cvc_page=recruitments&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^tuyen-dung/([^/]+)/?$',
		'index.php?cvc_page=recruitment-detail&cvc_recruitment_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^tuyen-dung/?$',
		'index.php?cvc_page=recruitments',
		'top'
	);

	add_rewrite_rule(
		'^kien-thuc/page/([0-9]+)/?$',
		'index.php?cvc_page=knowledge&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^kien-thuc/([^/]+)/?$',
		'index.php?cvc_page=knowledge-detail&cvc_knowledge_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^kien-thuc/?$',
		'index.php?cvc_page=knowledge',
		'top'
	);

	add_rewrite_rule(
		'^thi-trac-nghiem/page/([0-9]+)/?$',
		'index.php?cvc_page=exams&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^thi-trac-nghiem/([^/]+)/?$',
		'index.php?cvc_page=exam-detail&cvc_exam_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^thi-trac-nghiem/?$',
		'index.php?cvc_page=exams',
		'top'
	);

	add_rewrite_rule(
		'^van-ban-phap-luat/page/([0-9]+)/?$',
		'index.php?cvc_page=legal-documents&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^van-ban-phap-luat/([^/]+)/?$',
		'index.php?cvc_page=legal-document-detail&cvc_legal_document_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^van-ban-phap-luat/?$',
		'index.php?cvc_page=legal-documents',
		'top'
	);
}

add_filter( 'query_vars', 'cvc_register_query_vars' );

/**
 * @param string[] $vars
 * @return string[]
 */
function cvc_register_query_vars( array $vars ): array {
	$vars[] = 'cvc_page';
	$vars[] = 'cvc_course_slug';
	$vars[] = 'cvc_lesson_id';
	$vars[] = 'cvc_topic_slug';
	$vars[] = 'cvc_recruitment_slug';
	$vars[] = 'cvc_knowledge_slug';
	$vars[] = 'cvc_exam_slug';
	$vars[] = 'cvc_legal_document_slug';
	$vars[] = 'cvc_paged';

	return $vars;
}

/**
 * Các route ở trên không map tới post/page thật nên WP mặc định sẽ coi
 * đây là 404 trước khi template-loader kịp chạy. Báo cho WP biết những
 * request có cvc_page đã được theme tự xử lý; 404 thật (course/topic/lesson
 * không tồn tại) do từng template tự gọi status_header(404).
 */
add_filter( 'pre_handle_404', 'cvc_prevent_core_404_for_domain_routes', 10, 2 );

function cvc_prevent_core_404_for_domain_routes( $preempt, $wp_query ) {
	if ( ! empty( $wp_query->query_vars['cvc_page'] ) ) {
		return true;
	}

	return $preempt;
}

/**
 * Chỉ flush khi version đổi, tránh flush_rewrite_rules() mỗi request
 * (rất tốn - ghi lại toàn bộ rewrite_rules option + .htaccess).
 */
add_action( 'init', 'cvc_maybe_flush_rewrite_rules', 20 );

function cvc_maybe_flush_rewrite_rules(): void {
	if ( get_option( 'cvc_rewrite_version' ) === CVC_REWRITE_VERSION ) {
		return;
	}

	// Rewrite rule custom chỉ có tác dụng khi site dùng pretty permalink.
	if ( '' === get_option( 'permalink_structure' ) ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
	}

	/*
	 * flush_rewrite_rules() chỉ ghi lại .htaccess (save_mod_rewrite_rules())
	 * nếu wp-admin/includes/misc.php đã được load - file này không tự load
	 * ở front-end, nên phải require thủ công, nếu không .htaccess sẽ không
	 * bao giờ được cập nhật dù rewrite_rules option đã đúng (đã xác nhận
	 * bằng test thực tế: option đúng nhưng .htaccess vẫn trống).
	 */
	if ( ! function_exists( 'save_mod_rewrite_rules' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}

	flush_rewrite_rules();
	update_option( 'cvc_rewrite_version', CVC_REWRITE_VERSION );
}

add_filter( 'template_include', 'cvc_template_include' );

function cvc_template_include( string $template ): string {
	$page = get_query_var( 'cvc_page' );

	if ( ! $page ) {
		return $template;
	}

	$map = array(
		'courses'               => 'template-courses.php',
		'course-detail'         => 'template-course-detail.php',
		'course-lesson'         => 'template-course-lesson.php',
		'topics'                => 'template-topics.php',
		'topic-detail'          => 'template-topic-detail.php',
		'recruitments'          => 'template-recruitments.php',
		'recruitment-detail'    => 'template-recruitment-detail.php',
		'knowledge'             => 'template-knowledge.php',
		'knowledge-detail'      => 'template-knowledge-detail.php',
		'exams'                 => 'template-exams.php',
		'exam-detail'           => 'template-exam-detail.php',
		'legal-documents'       => 'template-legal-documents.php',
		'legal-document-detail' => 'template-legal-document-detail.php',
	);

	if ( isset( $map[ $page ] ) ) {
		$file = get_theme_file_path( $map[ $page ] );

		if ( file_exists( $file ) ) {
			return $file;
		}
	}

	return $template;
}

/**
 * URL helpers - nguồn duy nhất để build link nội bộ, tránh hardcode
 * path rải rác trong template.
 */
function cvc_courses_url( int $paged = 1 ): string {
	$path = 'khoa-hoc/';

	if ( $paged > 1 ) {
		$path .= 'page/' . $paged . '/';
	}

	return home_url( '/' . $path );
}

function cvc_course_url( string $slug ): string {
	return home_url( '/khoa-hoc/' . rawurlencode( $slug ) . '/' );
}

function cvc_course_lesson_url( string $course_slug, int $lesson_id ): string {
	return home_url( '/khoa-hoc/' . rawurlencode( $course_slug ) . '/bai-hoc/' . $lesson_id . '/' );
}

function cvc_topics_url( int $paged = 1 ): string {
	$path = 'chu-de/';

	if ( $paged > 1 ) {
		$path .= 'page/' . $paged . '/';
	}

	return home_url( '/' . $path );
}

function cvc_topic_url( string $slug ): string {
	return home_url( '/chu-de/' . rawurlencode( $slug ) . '/' );
}

function cvc_recruitments_url( int $paged = 1 ): string {
	$path = 'tuyen-dung/';

	if ( $paged > 1 ) {
		$path .= 'page/' . $paged . '/';
	}

	return home_url( '/' . $path );
}

function cvc_recruitment_url( string $slug ): string {
	return home_url( '/tuyen-dung/' . rawurlencode( $slug ) . '/' );
}

function cvc_knowledge_url( int $paged = 1 ): string {
	$path = 'kien-thuc/';

	if ( $paged > 1 ) {
		$path .= 'page/' . $paged . '/';
	}

	return home_url( '/' . $path );
}

function cvc_knowledge_item_url( string $slug ): string {
	return home_url( '/kien-thuc/' . rawurlencode( $slug ) . '/' );
}

function cvc_exams_url( int $paged = 1 ): string {
	$path = 'thi-trac-nghiem/';

	if ( $paged > 1 ) {
		$path .= 'page/' . $paged . '/';
	}

	return home_url( '/' . $path );
}

function cvc_exam_url( string $slug ): string {
	return home_url( '/thi-trac-nghiem/' . rawurlencode( $slug ) . '/' );
}

function cvc_legal_documents_url( int $paged = 1 ): string {
	$path = 'van-ban-phap-luat/';

	if ( $paged > 1 ) {
		$path .= 'page/' . $paged . '/';
	}

	return home_url( '/' . $path );
}

function cvc_legal_document_url( string $slug ): string {
	return home_url( '/van-ban-phap-luat/' . rawurlencode( $slug ) . '/' );
}
