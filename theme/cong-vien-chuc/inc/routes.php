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
const CVC_REWRITE_VERSION = '25';

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
		'ho-so-chinh-phuc'  => 'conquest',
		'ho-so'             => 'profile',
		'muc-tieu'          => 'goals',
		'lo-trinh'          => 'learning-path',
		'dau-trang'         => 'bookmarks',
		'lich-su-thi'       => 'exam-history',
		'khoa-hoc-cua-toi'  => 'my-courses',
		'tai-lieu-da-mua'   => 'my-documents',
		'goi-y'             => 'recommendations',
		'viec-lam-phu-hop'  => 'recruitment-matches',
		'thong-bao'         => 'notifications',
		'chung-chi'         => 'certificates',
		'bao-mat'           => 'security',
	);
}

/** Mục chính của khu vực tài khoản (thứ tự hiển thị, 18/10/2026). */
function cvc_account_primary_sections(): array {
	return array( 'overview', 'conquest', 'bookmarks', 'my-documents', 'exam-history', 'learning-path' );
}

add_action( 'init', 'cvc_register_rewrite_rules' );

function cvc_register_rewrite_rules(): void {
	// 19/10/2026: sitemap tin tuyển dụng + vị trí (inc/sitemap.php), gắn vào sitemap_index của Rank Math.
	add_rewrite_rule( '^sitemap-tuyen-dung\.xml$', 'index.php?cvc_sitemap=tuyen-dung', 'top' );
	// 27/10/2026: Bộ đề PDF (inc/pdf-sets.php).
	add_rewrite_rule( '^sitemap-bo-de-pdf\.xml$', 'index.php?cvc_sitemap=bo-de-pdf', 'top' );
	add_rewrite_rule( '^bo-de-pdf/page/([0-9]+)/?$', 'index.php?cvc_page=pdf-sets&cvc_paged=$matches[1]', 'top' );
	add_rewrite_rule( '^bo-de-pdf/([a-z0-9-]+)/?$', 'index.php?cvc_page=pdf-set-detail&cvc_pdf_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^bo-de-pdf/?$', 'index.php?cvc_page=pdf-sets', 'top' );
	add_rewrite_rule( '^chinh-sach-bao-mat/?$', 'index.php?cvc_page=privacy', 'top' ); // 27/10/2026
	add_rewrite_rule( '^dieu-khoan-su-dung/?$', 'index.php?cvc_page=terms', 'top' ); // 28/10/2026
	add_rewrite_rule( '^q/([A-Za-z0-9-]{2,24})/?$', 'index.php?cvc_qr=$matches[1]', 'top' ); // QR ngắn trong đề PDF (28/10/2026)
	add_rewrite_rule( '^xac-minh-email/?$', 'index.php?cvc_page=verify-email', 'top' ); // 27/10/2026
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
	// Phase 16: trang dot tuyen dung (phai dung truoc quy tac chi tiet tin).
	add_rewrite_rule(
		'^tuyen-dung/dot/([^/]+)/?$',
		'index.php?cvc_page=recruitment-round&cvc_round_slug=$matches[1]',
		'top'
	);
	// 18/10/2026: co hoi theo tung vi tri (SEO + loc theo vi tri) - truoc quy tac chi tiet tin.
	add_rewrite_rule(
		'^tuyen-dung/vi-tri/page/([0-9]+)/?$',
		'index.php?cvc_page=recruitment-positions&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^tuyen-dung/vi-tri/?$',
		'index.php?cvc_page=recruitment-positions',
		'top'
	);
	add_rewrite_rule(
		'^tuyen-dung/vi-tri/([^/]+)/?$',
		'index.php?cvc_page=recruitment-position&cvc_position_slug=$matches[1]',
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

	// 28/10/2026: trang Luyện thi / Thi thử trắc nghiệm = danh mục đề với chế độ đặt sẵn.
	add_rewrite_rule( '^luyen-thi-trac-nghiem/page/([0-9]+)/?$', 'index.php?cvc_page=exams&cvc_exam_mode=on-tap&cvc_paged=$matches[1]', 'top' );
	add_rewrite_rule( '^luyen-thi-trac-nghiem/?$', 'index.php?cvc_page=exams&cvc_exam_mode=on-tap', 'top' );
	add_rewrite_rule( '^thi-thu-trac-nghiem/page/([0-9]+)/?$', 'index.php?cvc_page=exams&cvc_exam_mode=thi-that&cvc_paged=$matches[1]', 'top' );
	add_rewrite_rule( '^thi-thu-trac-nghiem/?$', 'index.php?cvc_page=exams&cvc_exam_mode=thi-that', 'top' );
	add_rewrite_rule(
		'^thi-trac-nghiem/page/([0-9]+)/?$',
		'index.php?cvc_page=exams&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^thi-trac-nghiem/([^/]+)/lam-bai/?$',
		'index.php?cvc_page=exam-attempt&cvc_exam_slug=$matches[1]',
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
	// Menu Ôn thi (19/10/2026): Ngân hàng câu hỏi + Câu hỏi đã lưu.
	add_rewrite_rule( '^ngan-hang-cau-hoi/?$', 'index.php?cvc_page=question-bank', 'top' );
	add_rewrite_rule( '^cau-hoi-da-luu/?$', 'index.php?cvc_page=saved-questions', 'top' );

	// Công cụ ước tính lương & lương hưu (trước nằm trên trang chủ, 16/10/2026).
	add_rewrite_rule(
		'^tinh-luong/?$',
		'index.php?cvc_page=salary',
		'top'
	);

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
		'^quen-mat-khau/?$',
		'index.php?cvc_page=forgot-password',
		'top'
	);
	add_rewrite_rule(
		'^dat-lai-mat-khau/?$',
		'index.php?cvc_page=reset-password',
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

	/* Kết quả & phân tích bài thi (18/10/2026) - chỉ chính chủ (API kiểm tra). */
	add_rewrite_rule(
		'^ket-qua-thi/([0-9]+)/?$',
		'index.php?cvc_page=exam-result&cvc_attempt_id=$matches[1]',
		'top'
	);

	/*
	 * Tài liệu (Document model - Phase 11 monetization) - catalog free/
	 * trả phí, chi tiết + mua, và proxy tải file (KHÔNG trỏ thẳng ra
	 * Laravel - xem cvc_document_download_url() + template_redirect
	 * handler trong inc/documents.php, lý do: cần đính kèm Bearer token
	 * từ cookie HttpOnly phía server, trình duyệt không tự làm được qua
	 * thẻ <a> thường).
	 */
	add_rewrite_rule(
		'^tai-lieu/page/([0-9]+)/?$',
		'index.php?cvc_page=documents&cvc_paged=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^tai-lieu/([^/]+)/tai-xuong/?$',
		'index.php?cvc_page=document-download&cvc_document_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^tai-lieu/([^/]+)/?$',
		'index.php?cvc_page=document-detail&cvc_document_slug=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^tai-lieu/?$',
		'index.php?cvc_page=documents',
		'top'
	);

	/*
	 * Kết quả đơn hàng (Phase 11) - trang đích PaymentController::
	 * vnpayReturn() redirect về sau khi thanh toán VNPay xong
	 * (?status=success|failed&order=CODE).
	 */
	add_rewrite_rule(
		'^don-hang/ket-qua/?$',
		'index.php?cvc_page=order-result',
		'top'
	);

	// Cổng đơn vị tuyển dụng (21/10/2026).
	add_rewrite_rule( '^don-vi-tuyen-dung/?$', 'index.php?cvc_page=employer-landing', 'top' );
	add_rewrite_rule( '^xep-hang/?$', 'index.php?cvc_page=leaderboard', 'top' );
	add_rewrite_rule( '^don-vi/tin/([0-9]+)/?$', 'index.php?cvc_page=employer&cvc_employer_view=tin&cvc_employer_id=$matches[1]', 'top' );
	add_rewrite_rule( '^don-vi/([a-z-]+)/?$', 'index.php?cvc_page=employer&cvc_employer_view=$matches[1]', 'top' );
	add_rewrite_rule( '^don-vi/?$', 'index.php?cvc_page=employer&cvc_employer_view=bang-dieu-khien', 'top' );

	// Thanh toán chuyển khoản VietQR qua SePay (20/10/2026): /thanh-toan/{mã đơn}/.
	add_rewrite_rule(
		'^thanh-toan/(CVC[0-9A-Za-z]{6,20})/?$',
		'index.php?cvc_page=payment&cvc_order_code=$matches[1]',
		'top'
	);

	/*
	 * Xác thực văn bằng/chứng chỉ công khai (Phase 11) - không cần đăng
	 * nhập, ai có mã cũng tra được (đúng mục đích chống giả mạo).
	 */
	add_rewrite_rule(
		'^xac-thuc-chung-chi/([^/]+)/?$',
		'index.php?cvc_page=certificate-verify&cvc_certificate_code=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^xac-thuc-chung-chi/?$',
		'index.php?cvc_page=certificate-verify',
		'top'
	);

	/*
	 * Trang quản trị nội bộ (Phase 11) - chỉ SUPER_ADMIN/ADMIN, tự kiểm
	 * tra role trong chính template (xem template-admin-ai-settings.php),
	 * không tạo thêm tầng permission riêng ở WP vì auth gốc vẫn là
	 * Laravel Sanctum + RBAC, WP chỉ đọc lại cvc_current_user()['roles'].
	 */
	add_rewrite_rule(
		'^quan-tri/cau-hinh-ai/?$',
		'index.php?cvc_page=admin-ai-settings',
		'top'
	);

	/*
	 * Khu quản trị nội dung (Phase 13) - đặt SAU rule cau-hinh-ai để
	 * /quan-tri/cau-hinh-ai/ vẫn vào trang cấu hình AI cũ.
	 */
	add_rewrite_rule(
		'^quan-tri/([a-z-]+)/(moi|[0-9]+)/?$',
		'index.php?cvc_page=admin&cvc_admin_resource=$matches[1]&cvc_admin_item=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^quan-tri/([a-z-]+)/?$',
		'index.php?cvc_page=admin&cvc_admin_resource=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^quan-tri/?$',
		'index.php?cvc_page=admin',
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
	$vars[] = 'cvc_qr';
	$vars[] = 'cvc_course_slug';
	$vars[] = 'cvc_lesson_id';
	$vars[] = 'cvc_topic_slug';
	$vars[] = 'cvc_recruitment_slug';
	$vars[] = 'cvc_position_slug';
	$vars[] = 'cvc_sitemap';
	$vars[] = 'cvc_round_slug';
	$vars[] = 'cvc_knowledge_slug';
	$vars[] = 'cvc_exam_slug';
	$vars[] = 'cvc_order_code';
	$vars[] = 'cvc_employer_view';
	$vars[] = 'cvc_employer_id';
	$vars[] = 'cvc_legal_document_slug';
	$vars[] = 'cvc_paged';
	$vars[] = 'cvc_exam_mode';
	$vars[] = 'cvc_account_section';
	$vars[] = 'cvc_attempt_id';
	$vars[] = 'cvc_document_slug';
	$vars[] = 'cvc_certificate_code';
	$vars[] = 'cvc_admin_resource';
	$vars[] = 'cvc_admin_item';
	$vars[] = 'cvc_pdf_slug';

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
		'recruitment-position'  => 'template-recruitment-detail.php',
		'recruitment-positions' => 'template-recruitment-positions.php',
		'knowledge'             => 'template-knowledge.php',
		'knowledge-detail'      => 'template-knowledge-detail.php',
		'exams'                 => 'template-exams.php',
		'exam-detail'           => 'template-exam-detail.php',
		'legal-documents'       => 'template-legal-documents.php',
		'legal-document-detail' => 'template-legal-document-detail.php',
		'search'                => 'template-search.php',
		'login'                 => 'template-login.php',
		'register'              => 'template-register.php',
		'forgot-password'       => 'template-forgot-password.php',
		'reset-password'        => 'template-reset-password.php',
		'account'               => 'template-account.php',
		'exam-attempt'          => 'template-exam-attempt.php',
		'documents'             => 'template-documents.php',
		'document-detail'       => 'template-document-detail.php',
		'order-result'          => 'template-order-result.php',
		'payment'               => 'template-payment.php',
		'employer-landing'      => 'template-employer-landing.php',
		'leaderboard'           => 'template-leaderboard.php',
		'employer'              => 'template-employer.php',
		'certificate-verify'    => 'template-certificate-verify.php',
		'admin-ai-settings'     => 'template-admin-ai-settings.php',
		'admin'                 => 'template-admin.php',
		'salary'                => 'template-salary.php',
		'recruitment-round'     => 'template-recruitment-round.php',
		'exam-result'           => 'template-exam-result.php',
		'question-bank'         => 'template-question-bank.php',
		'saved-questions'       => 'template-saved-questions.php',
		'pdf-sets'              => 'template-pdf-sets.php',
		'pdf-set-detail'        => 'template-pdf-set-detail.php',
		'privacy'               => 'template-privacy.php',
		'terms'                 => 'template-terms.php',
		'verify-email'          => 'template-verify-email.php',
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
function cvc_question_bank_url( array $args = array() ): string {
	$url = home_url( '/ngan-hang-cau-hoi/' );

	return $args ? add_query_arg( $args, $url ) : $url;
}

function cvc_saved_questions_url( string $type = '' ): string {
	$url = home_url( '/cau-hoi-da-luu/' );

	return '' !== $type ? add_query_arg( 'loai', $type, $url ) : $url;
}

/** Danh sách đề ở chế độ đã chọn: thi-that | on-tap (thẻ đề mở thẳng đúng chế độ). */
/** Trang Luyện thi trắc nghiệm (on-tap) / Thi thử trắc nghiệm (thi-that) - 28/10/2026. */
function cvc_exams_mode_url( string $mode, int $page = 1 ): string {
	$path = 'thi-that' === $mode ? 'thi-thu-trac-nghiem/' : ( 'on-tap' === $mode ? 'luyen-thi-trac-nghiem/' : '' );
	if ( '' === $path ) {
		return cvc_exams_url( $page );
	}

	return home_url( '/' . $path . ( $page > 1 ? 'page/' . $page . '/' : '' ) );
}

function cvc_account_url( string $section = 'overview' ): string {
	$slug = array_search( $section, cvc_account_sections(), true );

	return home_url( '/tai-khoan/' . ( false !== $slug ? $slug : 'tong-quan' ) . '/' );
}

function cvc_exam_attempt_url( $param ): string {
	if ( is_numeric( $param ) && (int) $param > 0 ) {
		return home_url( '/lam-bai/' . (int) $param . '/' );
	}
	$slug = is_string( $param ) ? $param : 'de-thi';
	return home_url( '/thi-trac-nghiem/' . rawurlencode( $slug ) . '/lam-bai/' );
}

/**
 * URL helpers - Tài liệu (Document model, Phase 11).
 */
function cvc_documents_url( int $paged = 1 ): string {
	$path = 'tai-lieu/';

	if ( $paged > 1 ) {
		$path .= 'page/' . $paged . '/';
	}

	return home_url( '/' . $path );
}

function cvc_document_url( string $slug ): string {
	return home_url( '/tai-lieu/' . rawurlencode( $slug ) . '/' );
}

/**
 * URL tải file THẬT - đi qua proxy của chính theme (inc/documents.php),
 * KHÔNG trỏ thẳng ra Laravel (xem lý do ở cvc_register_rewrite_rules()).
 */
function cvc_document_download_url( string $slug ): string {
	return home_url( '/tai-lieu/' . rawurlencode( $slug ) . '/tai-xuong/' );
}

/**
 * URL trang kết quả đơn hàng sau khi VNPay redirect về.
 */
function cvc_order_result_url( ?string $status = null, ?string $order = null ): string {
	$url  = home_url( '/don-hang/ket-qua/' );
	$args = array();

	if ( null !== $status ) {
		$args['status'] = $status;
	}
	if ( null !== $order ) {
		$args['order'] = $order;
	}

	return empty( $args ) ? $url : add_query_arg( $args, $url );
}

function cvc_certificate_verify_url( string $code = '' ): string {
	if ( '' === $code ) {
		return home_url( '/xac-thuc-chung-chi/' );
	}

	return home_url( '/xac-thuc-chung-chi/' . rawurlencode( $code ) . '/' );
}

function cvc_admin_ai_settings_url(): string {
	return home_url( '/quan-tri/cau-hinh-ai/' );
}

/**
 * Menu chính dùng chung mọi giao diện (header.php, trang chủ v17, trang kết quả thi) - 18/10/2026.
 * "Ôn thi" gom đề thi, chủ đề, kiến thức, khóa học (khóa học chưa mở -> nhãn "Sắp mở").
 *
 * @return array<int, array<string, mixed>>
 */
/**
 * Menu "Tuyển dụng" (21/10/2026): Tin tuyển dụng · Tin ngoài công lập (khi bật) · Đăng tin tuyển dụng.
 * Tài khoản đơn vị: "Đăng tin" vào thẳng màn hình đăng tin; còn lại → trang giới thiệu cho đơn vị.
 */
function cvc_rec_nav_item(): array {
	$kids = array( array( 'label' => 'Tin tuyển dụng', 'url' => cvc_recruitments_url() ) );
	$set  = function_exists( 'cvc_employer_public_settings' ) ? cvc_employer_public_settings() : array();
	if ( ! empty( $set['private_enabled'] ) ) {
		$kids[] = array( 'label' => 'Tin ngoài công lập', 'url' => add_query_arg( 'khu-vuc', 'ngoai-cong-lap', cvc_recruitments_url() ) );
	}
	if ( ! isset( $set['enabled'] ) || ! empty( $set['enabled'] ) ) {
		$emp    = ( function_exists( 'cvc_employer_of_current_user' ) && cvc_is_logged_in() ) ? cvc_employer_of_current_user() : null;
		$kids[] = array(
			'label' => 'Đăng tin tuyển dụng',
			'badge' => 'Miễn phí',
			'url'   => $emp ? home_url( '/don-vi/dang-tin/' ) : home_url( '/don-vi-tuyen-dung/' ),
		);
		if ( ! empty( $set['paid'] ) ) {
			unset( $kids[ count( $kids ) - 1 ]['badge'] );
		}
	}

	return array( 'key' => 'rec', 'label' => 'Tuyển dụng', 'hint' => 'Cơ hội mới nhất', 'url' => cvc_recruitments_url(), 'children' => $kids );
}

/** Menu chính: lấy từ Giao diện → Menu (vị trí Primary, inc/nav-menu.php); chưa gán thì dùng menu mặc định bên dưới. */
function cvc_main_nav_items(): array {
	static $memo = null;
	if ( null === $memo ) {
		$memo = ( function_exists( 'cvc_nav_from_wp_menu' ) ? cvc_nav_from_wp_menu() : null ) ?? cvc_main_nav_items_default();
	}

	return $memo;
}

function cvc_main_nav_items_default(): array {
	$has_courses = function_exists( 'cvc_has_sellable_courses' ) && cvc_has_sellable_courses();

	return array(
		array(
			'key'      => 'exams',
			'label'    => 'Ôn thi',
			'hint'     => 'Luyện tập và thi thử',
			'url'      => cvc_exams_url(),
			/*
			 * 19/10/2026 - chia theo mục đích: Luyện tập / Đánh giá (mega menu 2 cột trên máy tính,
			 * danh sách dọc trên điện thoại); 'more' = hàng "Học thêm" dưới cùng.
			 */
			'children' => array(
				array( 'group' => 'practice', 'label' => 'Luyện thi trắc nghiệm', 'desc' => 'Đề riêng theo tin tuyển dụng, xem đáp án và căn cứ sau mỗi câu', 'url' => cvc_exams_mode_url( 'on-tap' ), 'sym' => '✎' ),
				array( 'group' => 'practice', 'label' => 'Thi thử trắc nghiệm', 'desc' => 'Làm đề tính giờ như phòng thi, nộp bài xem điểm', 'url' => cvc_exams_mode_url( 'thi-that' ), 'sym' => '◷' ),
				array( 'group' => 'practice', 'label' => 'Ngân hàng câu hỏi', 'desc' => 'Luyện theo môn, chủ đề và điều khoản pháp luật', 'url' => cvc_question_bank_url(), 'sym' => '▤' ),
				array( 'group' => 'practice', 'label' => 'Câu hỏi đã lưu', 'desc' => 'Câu đánh dấu, câu sai và câu cần ôn lại', 'url' => cvc_saved_questions_url(), 'sym' => '⚑' ),
				array( 'group' => 'assess', 'label' => 'Bộ đề PDF có đáp án', 'desc' => 'Tải đề theo tin tuyển dụng, theo văn bản - miễn phí', 'url' => home_url( '/bo-de-pdf/' ), 'sym' => '⇩' ),
				array( 'group' => 'assess', 'label' => 'Bảng xếp hạng', 'desc' => 'Top thí sinh thi thử theo tuần, tháng, từng đề', 'url' => home_url( '/xep-hang/' ), 'sym' => '★' ),
				array( 'group' => 'assess', 'label' => 'Kết quả & tiến độ', 'desc' => 'Tổng quan điểm số, tiến độ và chủ đề còn yếu', 'url' => cvc_account_url( 'overview' ), 'sym' => '↗' ),
				array( 'group' => 'more', 'label' => 'Chủ đề ôn thi', 'url' => cvc_topics_url() ),
				array( 'group' => 'more', 'label' => 'Kiến thức trọng tâm', 'url' => cvc_knowledge_url() ),
				array( 'group' => 'more', 'label' => 'Khóa học', 'url' => cvc_courses_url(), 'badge' => $has_courses ? '' : 'Sắp mở' ),
			),
		),
		cvc_rec_nav_item(),
		array( 'key' => 'legal', 'label' => 'Văn bản pháp luật', 'hint' => 'Tra cứu quy định', 'url' => cvc_legal_documents_url() ),
		array( 'key' => 'docs', 'label' => 'Tài liệu', 'hint' => 'Kho tài liệu hữu ích', 'url' => cvc_documents_url() ),
	);
}

/*
 * QR ngắn trong đề PDF (28/10/2026): /q/T-XXXXXX (mã tải), /q/e{đề}, /q/r{tin} -> hỏi API đích + utm -> chuyển hướng 302.
 */
add_action( 'template_redirect', 'cvc_qr_redirect', 1 );

function cvc_qr_redirect(): void {
	$key = (string) get_query_var( 'cvc_qr' );
	if ( '' === $key || ! preg_match( '/^[A-Za-z0-9-]{2,24}$/', $key ) ) {
		return;
	}
	nocache_headers();
	$res    = ( new CVC_Api_Client( null, 8 ) )->get( '/api/exam-pdf/q/' . rawurlencode( $key ) );
	$target = ! empty( $res['ok'] ) ? (string) ( $res['data']['data']['url'] ?? '' ) : '';
	$host   = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	if ( '' === $target || (string) wp_parse_url( $target, PHP_URL_HOST ) !== $host ) {
		$target = home_url( '/bo-de-pdf/' );
	}
	wp_redirect( $target, 302, 'Cong Vien Chuc' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- đã kiểm tra cùng tên miền.
	exit;
}
