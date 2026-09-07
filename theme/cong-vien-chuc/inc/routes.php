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
const CVC_REWRITE_VERSION = '5';

/**
 * Section hợp lệ của /tai-khoan/{section}/ (Phase 10) - map slug tiếng
 * Việt trên URL sang query var nội bộ, dùng chung cho rewrite rule +
 * cvc_account_url() + template-account.php.
 *
 * @return array<string, string>
 */
function cvc_account_sections(): array {
	return array(
		'tong-quan'         => 'overview',
		'ho-so'             => 'profile',
		'muc-tieu'          => 'goals',
		'lo-trinh'          => 'learning-path',
		'dau-trang'         => 'bookmarks',
		'lich-su-thi'       => 'exam-history',
		'goi-y'             => 'recommendations',
		'viec-lam-phu-hop'  => 'recruitment-matches',
		'thong-bao'         => 'notifications',
	);
}

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

	/*
	 * Search không có path segment riêng (slug/số trang) - state (q/type/
	 * page) đi qua query string thường (?q=...&type=...&page=...), luôn
	 * có sẵn trong $_GET dù path đã bị rewrite, không cần capture group
	 * hay đăng ký thêm query var.
	 */
	add_rewrite_rule(
		'^tim-kiem/?$',
		'index.php?cvc_page=search',
		'top'
	);

	/*
	 * Auth + Dashboard (Phase 10) - user-scoped, không có phần nào công
	 * khai. Section slug được whitelist qua cvc_account_sections() - slug
	 * lạ sẽ 404 ở chính template-account.php (không thêm rule riêng cho
	 * từng section, tránh phình rewrite).
	 */
	add_rewrite_rule(
		'^dang-nhap/?$',
		'index.php?cvc_page=login',
		'top'
	);
	add_rewrite_rule(
		'^dang-ky/?$',
		'index.php?cvc_page=register',
		'top'
	);
	add_rewrite_rule(
		'^tai-khoan/([^/]+)/?$',
		'index.php?cvc_page=account&cvc_account_section=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^tai-khoan/?$',
		'index.php?cvc_page=account&cvc_account_section=tong-quan',
		'top'
	);

	/*
	 * Làm bài thi (Phase 10) - attempt_id luôn thuộc CHÍNH user đang đăng
	 * nhập, kiểm tra ownership thật lại trong template qua chính API
	 * (403 từ backend nếu không phải của mình - Phần XV Recruitment/Exam
	 * security).
	 */
	add_rewrite_rule(
		'^lam-bai/([0-9]+)/?$',
		'index.php?cvc_page=exam-attempt&cvc_attempt_id=$matches[1]',
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
	$vars[] = 'cvc_account_section';
	$vars[] = 'cvc_attempt_id';

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
		'search'                => 'template-search.php',
		'login'                 => 'template-login.php',
		'register'              => 'template-register.php',
		'account'               => 'template-account.php',
		'exam-attempt'          => 'template-exam-attempt.php',
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

/**
 * URL trang tìm kiếm. $type bỏ qua nếu là 'all' (giữ URL sạch, không
 * thêm tham số không cần thiết). $page bỏ qua nếu là 1 - click filter
 * (không truyền $page) sẽ tự động reset về trang 1 đúng theo yêu cầu.
 */
function cvc_search_url( string $q = '', string $type = 'all', int $page = 1 ): string {
	$args = array();

	if ( '' !== $q ) {
		$args['q'] = $q;
	}

	if ( 'all' !== $type ) {
		$args['type'] = $type;
	}

	if ( $page > 1 ) {
		$args['page'] = $page;
	}

	$url = home_url( '/tim-kiem/' );

	return empty( $args ) ? $url : add_query_arg( $args, $url );
}

/**
 * URL 1 section trong dashboard /tai-khoan/{slug}/ - $section là key nội bộ
 * (vd 'goals'), tự map ngược ra slug tiếng Việt qua cvc_account_sections().
 * Fallback về 'tong-quan' nếu key không tồn tại (không bao giờ tạo link
 * hỏng dù truyền nhầm key).
 */
function cvc_account_url( string $section = 'overview' ): string {
	$slug = array_search( $section, cvc_account_sections(), true );

	return home_url( '/tai-khoan/' . ( false !== $slug ? $slug : 'tong-quan' ) . '/' );
}

function cvc_exam_attempt_url( int $attemptId ): string {
	return home_url( '/lam-bai/' . $attemptId . '/' );
}
