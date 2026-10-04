<?php
/**
 * Khu quản trị /quan-tri/ (Phase 13) - cấu hình từng loại dữ liệu.
 *
 * Mỗi tài nguyên ánh xạ 1-1 tới admin API của Laravel (backend vẫn là nơi
 * kiểm tra quyền + validate). WordPress chỉ dựng giao diện từ cấu hình này:
 * cột danh sách, bộ lọc, trường form, thao tác quy trình, danh sách con.
 *
 * Kiểu trường: text, slug, textarea, html, number, decimal, date, checkbox,
 * select, relation, relation_multi, province, admin_unit, file, options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_admin_status_labels(): array {
	return array(
		'draft'     => 'Nháp',
		'review'    => 'Chờ duyệt',
		'published' => 'Đã xuất bản',
		'expired'   => 'Hết hạn',
		'active'    => 'Đang dùng',
		'archived'  => 'Lưu trữ',
		'pending'   => 'Chờ thanh toán',
		'paid'      => 'Đã thanh toán',
		'failed'    => 'Thất bại',
		'cancelled' => 'Đã hủy',
		'inactive'  => 'Tạm dừng',
		'1'         => 'Đang dùng',
		'0'         => 'Tạm ẩn',
		'true'      => 'Đang dùng',
		'false'     => 'Tạm ẩn',
	);
}

function cvc_admin_menu_groups(): array {
	return array(
		'Tuyển dụng'       => array( 'recruitments', 'positions', 'agencies', 'sources' ),
		'Nội dung ôn thi'  => array( 'exam-subjects', 'topics', 'knowledge-items', 'legal-documents', 'questions', 'question-tags', 'exams' ),
		'Đào tạo & bán hàng' => array( 'courses', 'course-lessons', 'documents', 'orders' ),
		'Hệ thống'         => array( 'users' ),
	);
}

function cvc_admin_resources(): array {
	static $resources = null;
	if ( null !== $resources ) {
		return $resources;
	}

	$content_status = array(
		'draft'     => 'Nháp',
		'review'    => 'Chờ duyệt',
		'published' => 'Đã xuất bản',
	);
	$bool_status = array(
		'1' => 'Đang dùng',
		'0' => 'Tạm ẩn',
	);

	$resources = array(

		'recruitments' => array(
			'label'    => 'Tin tuyển dụng',
			'singular' => 'tin tuyển dụng',
			'icon'     => 'fa-bullhorn',
			'endpoint' => '/api/admin/recruitments',
			'module'   => 'recruitment',
			'title'    => 'title',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã' ),
				array( 'key' => 'title', 'label' => 'Tiêu đề', 'link' => true ),
				array( 'key' => 'agency.name', 'label' => 'Cơ quan' ),
				array( 'key' => 'application_deadline', 'label' => 'Hạn nộp', 'format' => 'date' ),
				array( 'key' => 'positions_count', 'label' => 'Vị trí' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'status'           => array( 'label' => 'Trạng thái', 'options' => array( 'draft' => 'Nháp', 'review' => 'Chờ duyệt', 'published' => 'Đã xuất bản', 'expired' => 'Hết hạn' ) ),
				'recruitment_type' => array( 'label' => 'Loại', 'options' => array( 'civil_servant' => 'Công chức', 'public_employee' => 'Viên chức', 'other' => 'Khác' ) ),
			),
			'fields'   => array(
				array( 'name' => 'title', 'label' => 'Tiêu đề', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'title', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã đợt tuyển', 'type' => 'text', 'required' => true ),
				array( 'name' => 'recruitment_type', 'label' => 'Loại tuyển dụng', 'type' => 'select', 'required' => true, 'options' => array( 'civil_servant' => 'Công chức', 'public_employee' => 'Viên chức', 'other' => 'Khác' ) ),
				array( 'name' => 'agency_id', 'label' => 'Cơ quan tuyển dụng', 'type' => 'relation', 'resource' => 'agencies', 'required' => true, 'help' => 'Tỉnh/thành bên dưới phải trùng với tỉnh của cơ quan (ghi trong ngoặc).' ),
				array( 'name' => 'province_id', 'label' => 'Tỉnh/thành', 'type' => 'province', 'required' => true ),
				array( 'name' => 'admin_unit_id', 'label' => 'Đơn vị hành chính', 'type' => 'admin_unit' ),
				array( 'name' => 'total_positions', 'label' => 'Tổng chỉ tiêu', 'type' => 'number' ),
				array( 'name' => 'announcement_date', 'label' => 'Ngày thông báo', 'type' => 'date' ),
				array( 'name' => 'application_start_date', 'label' => 'Bắt đầu nhận hồ sơ', 'type' => 'date' ),
				array( 'name' => 'application_deadline', 'label' => 'Hạn nộp hồ sơ', 'type' => 'date' ),
				array( 'name' => 'location', 'label' => 'Địa điểm', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'source_url', 'label' => 'Link thông báo gốc', 'type' => 'text', 'wide' => true, 'help' => 'Đường dẫn đầy đủ bắt đầu bằng https://' ),
				array( 'name' => 'summary', 'label' => 'Nội dung tóm tắt', 'type' => 'html', 'wide' => true ),
			),
			'actions'  => array(
				array( 'action' => 'submit', 'label' => 'Gửi duyệt', 'perm' => 'recruitment.submit', 'when' => array( 'draft' ) ),
				array( 'action' => 'publish', 'label' => 'Xuất bản', 'perm' => 'recruitment.publish', 'when' => array( 'review' ) ),
				array( 'action' => 'expire', 'label' => 'Đánh dấu hết hạn', 'perm' => 'recruitment.expire', 'when' => array( 'published' ), 'confirm' => 'Chuyển tin này sang Hết hạn?' ),
			),
			'children' => array(
				array( 'resource' => 'positions', 'fk' => 'recruitment_id', 'label' => 'Vị trí tuyển dụng' ),
			),
			'public'   => array( 'fn' => 'cvc_recruitment_url', 'key' => 'slug', 'statuses' => array( 'published', 'expired' ) ),
			'note'     => 'Tin mới tạo ở trạng thái Nháp → Gửi duyệt → Xuất bản. Khi xuất bản, hệ thống tự chấm mức phù hợp cho mục tiêu ôn thi của người dùng.',
		),

		'positions' => array(
			'label'    => 'Vị trí tuyển dụng',
			'singular' => 'vị trí',
			'icon'     => 'fa-briefcase',
			'endpoint' => '/api/admin/positions',
			'module'   => 'recruitment',
			'title'    => 'name',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã' ),
				array( 'key' => 'name', 'label' => 'Vị trí', 'link' => true ),
				array( 'key' => 'recruitment.title', 'label' => 'Đợt tuyển' ),
				array( 'key' => 'quantity', 'label' => 'Chỉ tiêu' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(),
			'fields'   => array(
				array( 'name' => 'recruitment_id', 'label' => 'Thuộc đợt tuyển dụng', 'type' => 'relation', 'resource' => 'recruitments', 'required' => true, 'wide' => true ),
				array( 'name' => 'name', 'label' => 'Tên vị trí', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'name', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã vị trí', 'type' => 'text', 'required' => true, 'help' => 'Không trùng trong cùng đợt tuyển.' ),
				array( 'name' => 'quantity', 'label' => 'Chỉ tiêu', 'type' => 'number' ),
				array( 'name' => 'employment_type', 'label' => 'Hình thức', 'type' => 'select', 'options' => array( '' => '— Chưa chọn —', 'full_time' => 'Toàn thời gian', 'part_time' => 'Bán thời gian', 'contract' => 'Hợp đồng' ) ),
				array( 'name' => 'education_level', 'label' => 'Trình độ', 'type' => 'text' ),
				array( 'name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => array( 'published' => 'Hiển thị', 'draft' => 'Nháp' ) ),
				array( 'name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number' ),
				array( 'name' => 'job_description', 'label' => 'Mô tả công việc', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'requirements', 'label' => 'Yêu cầu chung', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'major_requirements', 'label' => 'Yêu cầu chuyên ngành', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'experience_requirements', 'label' => 'Yêu cầu kinh nghiệm', 'type' => 'textarea', 'wide' => true ),
			),
			'panels'   => array( 'position_subjects' ),
		),

		'agencies' => array(
			'label'    => 'Cơ quan',
			'singular' => 'cơ quan',
			'icon'     => 'fa-building-columns',
			'endpoint' => '/api/admin/agencies',
			'module'   => 'agency',
			'title'    => 'name',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã' ),
				array( 'key' => 'name', 'label' => 'Tên cơ quan', 'link' => true ),
				array( 'key' => 'province.name', 'label' => 'Tỉnh/thành' ),
				array( 'key' => 'agency_type', 'label' => 'Loại' ),
			),
			'filters'  => array(),
			'fields'   => array(
				array( 'name' => 'name', 'label' => 'Tên cơ quan', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'name', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã', 'type' => 'text', 'required' => true ),
				array( 'name' => 'agency_type', 'label' => 'Loại cơ quan', 'type' => 'select', 'options' => array( 'ministry' => 'Bộ, ngành', 'department' => 'Sở, ban, ngành', 'provincial_department' => 'Cơ quan cấp tỉnh', 'ubnd' => 'UBND', 'ubnd_district' => 'UBND cấp huyện/xã', 'so' => 'Sở', 'education' => 'Cơ sở giáo dục', 'health' => 'Y tế', 'other' => 'Khác' ) ),
				array( 'name' => 'province_id', 'label' => 'Tỉnh/thành', 'type' => 'province', 'required' => true ),
				array( 'name' => 'admin_unit_id', 'label' => 'Đơn vị hành chính', 'type' => 'admin_unit' ),
				array( 'name' => 'address', 'label' => 'Địa chỉ', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'phone', 'label' => 'Điện thoại', 'type' => 'text' ),
				array( 'name' => 'email', 'label' => 'Email', 'type' => 'text' ),
				array( 'name' => 'website', 'label' => 'Website', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'status', 'label' => 'Đang dùng', 'type' => 'checkbox' ),
			),
		),

		'sources' => array(
			'label'    => 'Nguồn thu thập',
			'singular' => 'nguồn',
			'icon'     => 'fa-satellite-dish',
			'endpoint' => '/api/admin/sources',
			'module'   => 'recruitment',
			'title'    => 'name',
			'columns'  => array(
				array( 'key' => 'name', 'label' => 'Tên nguồn', 'link' => true ),
				array( 'key' => 'province.name', 'label' => 'Tỉnh/thành' ),
				array( 'key' => 'trust_level', 'label' => 'Tin cậy' ),
				array( 'key' => 'published_count', 'label' => 'Tin đã đăng' ),
				array( 'key' => 'pending_count', 'label' => 'Chờ duyệt' ),
				array( 'key' => 'last_discovery_at', 'label' => 'Dò link lần cuối', 'format' => 'datetime' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'status' => array( 'label' => 'Trạng thái', 'options' => array( 'active' => 'Đang chạy', 'inactive' => 'Tạm dừng' ) ),
			),
			'fields'   => array(
				array( 'name' => 'name', 'label' => 'Tên nguồn', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'url', 'label' => 'Địa chỉ trang chủ', 'type' => 'text', 'required' => true, 'wide' => true, 'help' => 'VD https://sonoivu.tinh.gov.vn — hệ thống dò link có từ khóa tuyển dụng từ trang này.' ),
				array( 'name' => 'trust_level', 'label' => 'Mức tin cậy', 'type' => 'select', 'required' => true, 'options' => array( 'tier_1' => 'Tier 1 — cơ quan nhà nước (được tự đăng)', 'tier_2' => 'Tier 2 — đơn vị sự nghiệp công lập (được tự đăng)', 'tier_3' => 'Tier 3 — nguồn khác (luôn cần duyệt)' ) ),
				array( 'name' => 'province_id', 'label' => 'Tỉnh/thành mặc định', 'type' => 'province' ),
				array( 'name' => 'crawl_frequency_tier', 'label' => 'Tần suất dò link', 'type' => 'select', 'options' => array( 'high' => 'Cao — 6 giờ/lần', 'medium' => 'Vừa — 12 giờ/lần', 'low' => 'Thấp — 2 ngày/lần' ) ),
				array( 'name' => 'adapter_key', 'label' => 'Bộ đọc', 'type' => 'select', 'nullable' => true, 'options' => array( '' => 'Tự động (bộ đọc chung + AI)', 'gov_portal_html' => 'Cổng SharePoint (Quảng Ninh…)', 'haiphong_portal' => 'Cổng Hải Phòng' ) ),
				array( 'name' => 'listing_urls', 'label' => 'Trang danh mục tin tuyển dụng', 'type' => 'textarea', 'wide' => true, 'help' => 'Mỗi dòng 1 địa chỉ. Để trống: dò từ trang chủ. Hệ thống tự thêm trang danh mục phát hiện được.' ),
				array( 'name' => 'link_include_patterns', 'label' => 'Chỉ lấy link chứa', 'type' => 'textarea', 'wide' => true, 'help' => 'Mỗi dòng 1 cụm (VD /tuyen-dung/). Để trống: lọc theo từ khóa tuyển dụng.' ),
				array( 'name' => 'description', 'label' => 'Ghi chú', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'is_active', 'label' => 'Đang chạy', 'type' => 'checkbox', 'default' => true ),
			),
			'note'     => 'Nguồn tier 1/2 được tự đăng khi bật chế độ tự động. Xem hàng chờ ở mục Thu thập tin.',
		),

		'exam-subjects' => array(
			'label'    => 'Môn thi',
			'singular' => 'môn thi',
			'icon'     => 'fa-layer-group',
			'endpoint' => '/api/admin/exam-subjects',
			'module'   => 'exam',
			'title'    => 'name',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã' ),
				array( 'key' => 'name', 'label' => 'Tên môn', 'link' => true ),
				array( 'key' => 'subject_type', 'label' => 'Loại' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(),
			'fields'   => array(
				array( 'name' => 'name', 'label' => 'Tên môn thi', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'name', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã', 'type' => 'text', 'required' => true ),
				array( 'name' => 'subject_type', 'label' => 'Loại môn', 'type' => 'select', 'options' => array( 'general' => 'Chung', 'general_knowledge' => 'Kiến thức chung', 'specialized' => 'Chuyên ngành', 'foreign_language' => 'Ngoại ngữ', 'informatics' => 'Tin học' ) ),
				array( 'name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number' ),
				array( 'name' => 'status', 'label' => 'Đang dùng', 'type' => 'checkbox' ),
				array( 'name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'wide' => true ),
			),
			'children' => array(
				array( 'resource' => 'topics', 'fk' => 'exam_subject_id', 'label' => 'Chủ đề của môn' ),
			),
		),

		'topics' => array(
			'label'    => 'Chủ đề',
			'singular' => 'chủ đề',
			'icon'     => 'fa-sitemap',
			'endpoint' => '/api/admin/topics',
			'module'   => 'knowledge',
			'title'    => 'name',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã' ),
				array( 'key' => 'name', 'label' => 'Chủ đề', 'link' => true ),
				array( 'key' => 'exam_subject.name', 'label' => 'Môn thi' ),
				array( 'key' => 'parent.name', 'label' => 'Chủ đề cha' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(),
			'fields'   => array(
				array( 'name' => 'exam_subject_id', 'label' => 'Môn thi', 'type' => 'relation', 'resource' => 'exam-subjects', 'required' => true ),
				array( 'name' => 'parent_id', 'label' => 'Chủ đề cha', 'type' => 'relation', 'resource' => 'topics' ),
				array( 'name' => 'name', 'label' => 'Tên chủ đề', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'name', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã', 'type' => 'text', 'required' => true ),
				array( 'name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number' ),
				array( 'name' => 'status', 'label' => 'Đang dùng', 'type' => 'checkbox' ),
				array( 'name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'wide' => true ),
			),
			'children' => array(
				array( 'resource' => 'knowledge-items', 'fk' => 'topic_id', 'label' => 'Bài kiến thức' ),
			),
			'public'   => array( 'fn' => 'cvc_topic_url', 'key' => 'slug' ),
		),

		'knowledge-items' => array(
			'label'    => 'Bài kiến thức',
			'singular' => 'bài kiến thức',
			'icon'     => 'fa-book-open',
			'endpoint' => '/api/admin/knowledge-items',
			'module'   => 'knowledge',
			'title'    => 'title',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã' ),
				array( 'key' => 'title', 'label' => 'Tiêu đề', 'link' => true ),
				array( 'key' => 'topic.name', 'label' => 'Chủ đề' ),
				array( 'key' => 'legal_document.document_number', 'label' => 'Văn bản' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'status' => array( 'label' => 'Trạng thái', 'options' => $content_status ),
			),
			'fields'   => array(
				array( 'name' => 'title', 'label' => 'Tiêu đề', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'title', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã', 'type' => 'text', 'required' => true ),
				array( 'name' => 'topic_id', 'label' => 'Chủ đề', 'type' => 'relation', 'resource' => 'topics', 'required' => true ),
				array( 'name' => 'legal_document_id', 'label' => 'Văn bản pháp luật liên quan', 'type' => 'relation', 'resource' => 'legal-documents' ),
				array( 'name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => $content_status, 'help' => 'Chỉ bài "Đã xuất bản" mới hiện ở trang Kiến thức và trong hồ sơ tuyển dụng.' ),
				array( 'name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number' ),
				array( 'name' => 'source_reference', 'label' => 'Căn cứ / nguồn', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'summary', 'label' => 'Tóm tắt', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'key_points', 'label' => 'Ý chính (mỗi dòng 1 ý)', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'content', 'label' => 'Nội dung', 'type' => 'html', 'wide' => true ),
			),
			'public'   => array( 'fn' => 'cvc_knowledge_item_url', 'key' => 'slug', 'statuses' => array( 'published' ) ),
		),

		'legal-documents' => array(
			'label'    => 'Văn bản pháp luật',
			'singular' => 'văn bản',
			'icon'     => 'fa-scale-balanced',
			'endpoint' => '/api/admin/legal-documents',
			'module'   => 'legal_document',
			'title'    => 'title',
			'columns'  => array(
				array( 'key' => 'document_number', 'label' => 'Số hiệu' ),
				array( 'key' => 'title', 'label' => 'Tên văn bản', 'link' => true ),
				array( 'key' => 'issuing_agency', 'label' => 'Cơ quan ban hành' ),
				array( 'key' => 'effective_date', 'label' => 'Hiệu lực', 'format' => 'date' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'status' => array( 'label' => 'Trạng thái', 'options' => array( 'published' => 'Đã xuất bản', 'active' => 'Đang dùng', 'draft' => 'Nháp' ) ),
			),
			'fields'   => array(
				array( 'name' => 'title', 'label' => 'Tên văn bản', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'title', 'required' => true ),
				array( 'name' => 'document_number', 'label' => 'Số hiệu', 'type' => 'text', 'required' => true ),
				array( 'name' => 'document_type', 'label' => 'Loại văn bản', 'type' => 'select', 'required' => true, 'options' => array( 'Luật' => 'Luật', 'Nghị quyết' => 'Nghị quyết', 'Nghị định' => 'Nghị định', 'Quyết định' => 'Quyết định', 'Thông tư' => 'Thông tư', 'Công văn' => 'Công văn', 'nghi_dinh' => 'Nghị định (mã cũ)', 'thong_tu' => 'Thông tư (mã cũ)', 'nghi_quyet' => 'Nghị quyết (mã cũ)' ) ),
				array( 'name' => 'issuing_agency', 'label' => 'Cơ quan ban hành', 'type' => 'text' ),
				array( 'name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'required' => true, 'options' => array( 'published' => 'Đã xuất bản', 'active' => 'Đang dùng (hiện công khai)', 'draft' => 'Nháp (ẩn)' ) ),
				array( 'name' => 'issued_date', 'label' => 'Ngày ban hành', 'type' => 'date' ),
				array( 'name' => 'effective_date', 'label' => 'Ngày hiệu lực', 'type' => 'date' ),
				array( 'name' => 'expiry_date', 'label' => 'Ngày hết hiệu lực', 'type' => 'date' ),
				array( 'name' => 'replaced_by_id', 'label' => 'Bị thay thế bởi', 'type' => 'relation', 'resource' => 'legal-documents' ),
				array( 'name' => 'source_url', 'label' => 'Link văn bản gốc', 'type' => 'text', 'wide' => true, 'help' => 'Đường dẫn đầy đủ bắt đầu bằng https://' ),
				array( 'name' => 'summary', 'label' => 'Tóm tắt', 'type' => 'textarea', 'wide' => true ),
			),
			'children' => array(
				array( 'resource' => 'knowledge-items', 'fk' => 'legal_document_id', 'label' => 'Bài kiến thức gắn với văn bản' ),
			),
			'public'   => array( 'fn' => 'cvc_legal_document_url', 'key' => 'slug', 'statuses' => array( 'published', 'active' ) ),
		),

		'questions' => array(
			'label'    => 'Câu hỏi',
			'singular' => 'câu hỏi',
			'icon'     => 'fa-circle-question',
			'endpoint' => '/api/admin/questions',
			'module'   => 'question',
			'title'    => 'question_text',
			'columns'  => array(
				array( 'key' => 'id', 'label' => 'ID' ),
				array( 'key' => 'question_text', 'label' => 'Câu hỏi', 'link' => true, 'trim' => 18 ),
				array( 'key' => 'exam_subject.name', 'label' => 'Môn' ),
				array( 'key' => 'topic.name', 'label' => 'Chủ đề' ),
				array( 'key' => 'difficulty', 'label' => 'Độ khó', 'format' => 'difficulty' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'status'     => array( 'label' => 'Trạng thái', 'options' => $content_status ),
				'difficulty' => array( 'label' => 'Độ khó', 'options' => array( 'easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó' ) ),
				'exam_subject_id' => array( 'label' => 'Môn thi', 'resource' => 'exam-subjects' ),
			),
			'fields'   => array(
				array( 'name' => 'question_text', 'label' => 'Nội dung câu hỏi', 'type' => 'textarea', 'required' => true, 'wide' => true ),
				array( 'name' => 'exam_subject_id', 'label' => 'Môn thi', 'type' => 'relation', 'resource' => 'exam-subjects', 'required' => true ),
				array( 'name' => 'topic_id', 'label' => 'Chủ đề', 'type' => 'relation', 'resource' => 'topics', 'required' => true ),
				array( 'name' => 'knowledge_item_id', 'label' => 'Bài kiến thức liên quan', 'type' => 'relation', 'resource' => 'knowledge-items' ),
				array( 'name' => 'question_type', 'label' => 'Loại câu', 'type' => 'select', 'options' => array( 'single_choice' => 'Một đáp án đúng', 'multiple_choice' => 'Nhiều đáp án đúng' ) ),
				array( 'name' => 'difficulty', 'label' => 'Độ khó', 'type' => 'select', 'options' => array( 'easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó' ) ),
				array( 'name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => $content_status ),
				array( 'name' => 'estimated_seconds', 'label' => 'Thời gian làm (giây)', 'type' => 'number' ),
				array( 'name' => 'options', 'label' => 'Phương án trả lời', 'type' => 'options', 'wide' => true ),
				array( 'name' => 'explanation', 'label' => 'Giải thích đáp án', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'source_reference', 'label' => 'Căn cứ pháp lý', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'source_url', 'label' => 'Link nguồn', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'tag_ids', 'label' => 'Thẻ', 'type' => 'relation_multi', 'resource' => 'question-tags', 'from' => 'tags' ),
			),
		),

		'question-tags' => array(
			'label'    => 'Thẻ câu hỏi',
			'singular' => 'thẻ',
			'icon'     => 'fa-tags',
			'endpoint' => '/api/admin/question-tags',
			'module'   => 'question',
			'title'    => 'name',
			'columns'  => array(
				array( 'key' => 'name', 'label' => 'Tên thẻ', 'link' => true ),
				array( 'key' => 'tag_type', 'label' => 'Loại' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(),
			'fields'   => array(
				array( 'name' => 'name', 'label' => 'Tên thẻ', 'type' => 'text', 'required' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'name', 'required' => true ),
				array( 'name' => 'tag_type', 'label' => 'Loại', 'type' => 'select', 'options' => array( 'general' => 'Chung', 'difficulty' => 'Độ khó', 'source' => 'Nguồn' ) ),
				array( 'name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number' ),
				array( 'name' => 'status', 'label' => 'Đang dùng', 'type' => 'checkbox' ),
				array( 'name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'wide' => true ),
			),
		),

		'exams' => array(
			'label'    => 'Đề thi',
			'singular' => 'đề thi',
			'icon'     => 'fa-file-signature',
			'endpoint' => '/api/admin/exams',
			'module'   => 'exam',
			'title'    => 'title',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã đề' ),
				array( 'key' => 'title', 'label' => 'Tên đề', 'link' => true, 'trim' => 14 ),
				array( 'key' => 'questions_count', 'label' => 'Câu hỏi' ),
				array( 'key' => 'duration_minutes', 'label' => 'Phút' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'status'    => array( 'label' => 'Trạng thái', 'options' => $content_status ),
				'exam_type' => array( 'label' => 'Loại đề', 'options' => array( 'official' => 'Chính thức', 'mock' => 'Thi thử', 'practice' => 'Luyện tập', 'mock_exam' => 'Mô phỏng' ) ),
			),
			'fields'   => array(
				array( 'name' => 'title', 'label' => 'Tên đề', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'title', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã đề', 'type' => 'text', 'required' => true ),
				array( 'name' => 'exam_type', 'label' => 'Loại đề', 'type' => 'select', 'options' => array( 'official' => 'Chính thức', 'mock' => 'Thi thử', 'practice' => 'Luyện tập', 'mock_exam' => 'Mô phỏng' ) ),
				array( 'name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => $content_status ),
				array( 'name' => 'duration_minutes', 'label' => 'Thời gian (phút)', 'type' => 'number' ),
				array( 'name' => 'total_questions', 'label' => 'Số câu dự kiến', 'type' => 'number' ),
				array( 'name' => 'total_score', 'label' => 'Tổng điểm', 'type' => 'decimal' ),
				array( 'name' => 'passing_score', 'label' => 'Điểm đạt', 'type' => 'decimal' ),
				array( 'name' => 'subject_ids', 'label' => 'Môn thi trong đề', 'type' => 'relation_multi', 'resource' => 'exam-subjects', 'from' => 'exam_subjects' ),
				array( 'name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'wide' => true ),
			),
			'panels'   => array( 'exam_questions' ),
			'public'   => array( 'fn' => 'cvc_exam_url', 'key' => 'slug', 'statuses' => array( 'published' ) ),
		),

		'courses' => array(
			'label'    => 'Khóa học',
			'singular' => 'khóa học',
			'icon'     => 'fa-graduation-cap',
			'endpoint' => '/api/admin/courses',
			'module'   => 'course',
			'title'    => 'title',
			'columns'  => array(
				array( 'key' => 'code', 'label' => 'Mã' ),
				array( 'key' => 'title', 'label' => 'Khóa học', 'link' => true ),
				array( 'key' => 'lessons_count', 'label' => 'Bài học' ),
				array( 'key' => 'sale_price', 'label' => 'Giá bán', 'format' => 'money' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'status' => array( 'label' => 'Trạng thái', 'options' => $content_status ),
			),
			'fields'   => array(
				array( 'name' => 'title', 'label' => 'Tên khóa học', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'title', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã', 'type' => 'text', 'required' => true ),
				array( 'name' => 'course_type', 'label' => 'Hình thức', 'type' => 'select', 'options' => array( 'online_video' => 'Video bài giảng', 'live_zoom' => 'Học trực tiếp (Zoom)', 'exam_prep' => 'Ôn thi' ) ),
				array( 'name' => 'price', 'label' => 'Giá gốc (đ)', 'type' => 'decimal', 'help' => '0 = miễn phí' ),
				array( 'name' => 'sale_price', 'label' => 'Giá bán (đ)', 'type' => 'decimal', 'help' => 'Để trống nếu không giảm giá' ),
				array( 'name' => 'duration_minutes', 'label' => 'Tổng thời lượng (phút)', 'type' => 'number' ),
				array( 'name' => 'is_featured', 'label' => 'Khóa nổi bật', 'type' => 'checkbox' ),
				array( 'name' => 'thumbnail_url', 'label' => 'Ảnh đại diện (URL)', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'short_description', 'label' => 'Mô tả ngắn', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'description', 'label' => 'Mô tả chi tiết', 'type' => 'html', 'wide' => true ),
				array( 'name' => 'exam_subject_ids', 'label' => 'Môn thi khóa học bao phủ', 'type' => 'relation_multi', 'resource' => 'exam-subjects', 'from' => 'exam_subjects', 'shape' => 'objects' ),
				array( 'name' => 'topic_ids', 'label' => 'Chủ đề khóa học bao phủ', 'type' => 'relation_multi', 'resource' => 'topics', 'from' => 'topics' ),
				array( 'name' => 'exam_ids', 'label' => 'Đề thi đi kèm', 'type' => 'relation_multi', 'resource' => 'exams', 'from' => 'exams' ),
			),
			'actions'  => array(
				array( 'action' => 'submit', 'label' => 'Gửi duyệt', 'perm' => 'course.update', 'when' => array( 'draft' ) ),
				array( 'action' => 'publish', 'label' => 'Xuất bản', 'perm' => 'course.publish', 'when' => array( 'review' ) ),
				array( 'action' => 'unpublish', 'label' => 'Gỡ xuất bản', 'perm' => 'course.publish', 'when' => array( 'published' ), 'confirm' => 'Gỡ khóa học khỏi trang công khai?' ),
			),
			'children' => array(
				array( 'resource' => 'course-lessons', 'fk' => 'course_id', 'label' => 'Bài học' ),
			),
			'public'   => array( 'fn' => 'cvc_course_url', 'key' => 'slug', 'statuses' => array( 'published' ) ),
			'note'     => 'Trạng thái khóa học đổi bằng nút Gửi duyệt → Xuất bản (cần ít nhất 1 bài học đã xuất bản) / Gỡ xuất bản.',
		),

		'course-lessons' => array(
			'label'    => 'Bài học',
			'singular' => 'bài học',
			'icon'     => 'fa-chalkboard-user',
			'endpoint' => '/api/admin/course-lessons',
			'module'   => 'course',
			'title'    => 'title',
			'columns'  => array(
				array( 'key' => 'sort_order', 'label' => '#' ),
				array( 'key' => 'title', 'label' => 'Bài học', 'link' => true ),
				array( 'key' => 'course.title', 'label' => 'Khóa học' ),
				array( 'key' => 'is_free', 'label' => 'Học thử', 'format' => 'bool' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(
				'course_id' => array( 'label' => 'Khóa học', 'resource' => 'courses' ),
			),
			'fields'   => array(
				array( 'name' => 'course_id', 'label' => 'Khóa học', 'type' => 'relation', 'resource' => 'courses', 'required' => true ),
				array( 'name' => 'parent_id', 'label' => 'Bài cha (nếu là bài con)', 'type' => 'relation', 'resource' => 'course-lessons' ),
				array( 'name' => 'title', 'label' => 'Tên bài học', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'slug', 'label' => 'Đường dẫn (slug)', 'type' => 'slug', 'from' => 'title', 'required' => true ),
				array( 'name' => 'code', 'label' => 'Mã', 'type' => 'text', 'required' => true ),
				array( 'name' => 'lesson_type', 'label' => 'Dạng bài', 'type' => 'select', 'options' => array( 'video' => 'Video', 'article' => 'Bài đọc', 'document' => 'Tài liệu', 'quiz' => 'Luyện tập' ) ),
				array( 'name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => $content_status, 'help' => 'Chỉ bài "Đã xuất bản" mới hiện trong khóa học.' ),
				array( 'name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number' ),
				array( 'name' => 'duration_minutes', 'label' => 'Thời lượng (phút)', 'type' => 'number' ),
				array( 'name' => 'is_free', 'label' => 'Cho học thử miễn phí', 'type' => 'checkbox' ),
				array( 'name' => 'video_url', 'label' => 'Link video (YouTube/Vimeo)', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'file_url', 'label' => 'Link tài liệu đính kèm', 'type' => 'text', 'wide' => true ),
				array( 'name' => 'short_description', 'label' => 'Mô tả ngắn', 'type' => 'textarea', 'wide' => true ),
				array( 'name' => 'content', 'label' => 'Nội dung bài', 'type' => 'html', 'wide' => true ),
			),
		),

		'documents' => array(
			'label'    => 'Tài liệu',
			'singular' => 'tài liệu',
			'icon'     => 'fa-file-arrow-down',
			'endpoint' => '/api/admin/documents',
			'module'   => 'document',
			'title'    => 'title',
			'multipart' => true,
			'columns'  => array(
				array( 'key' => 'title', 'label' => 'Tài liệu', 'link' => true ),
				array( 'key' => 'category', 'label' => 'Danh mục' ),
				array( 'key' => 'is_free', 'label' => 'Miễn phí', 'format' => 'bool' ),
				array( 'key' => 'download_count', 'label' => 'Lượt tải' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'  => array(),
			'fields'   => array(
				array( 'name' => 'title', 'label' => 'Tên tài liệu', 'type' => 'text', 'required' => true, 'wide' => true ),
				array( 'name' => 'category', 'label' => 'Danh mục', 'type' => 'select', 'required' => true, 'options' => array( 'mau-don' => 'Mẫu đơn, biểu mẫu', 'van-ban-luat' => 'Văn bản luật', 'bo-de' => 'Bộ đề', 'so-tay' => 'Sổ tay', 'so-do-tu-duy' => 'Sơ đồ tư duy', 'on-thi-ngoai-ngu' => 'Ôn thi ngoại ngữ' ) ),
				array( 'name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => array( 'published' => 'Đã xuất bản', 'draft' => 'Nháp' ) ),
				array( 'name' => 'is_free', 'label' => 'Tài liệu miễn phí', 'type' => 'checkbox', 'always_send' => true ),
				array( 'name' => 'price', 'label' => 'Giá (đ)', 'type' => 'decimal', 'help' => 'Bắt buộc nếu không miễn phí' ),
				array( 'name' => 'sale_price', 'label' => 'Giá khuyến mãi (đ)', 'type' => 'decimal' ),
				array( 'name' => 'recruitment_id', 'label' => 'Gắn với đợt tuyển dụng', 'type' => 'relation', 'resource' => 'recruitments' ),
				array( 'name' => 'exam_subject_id', 'label' => 'Gắn với môn thi', 'type' => 'relation', 'resource' => 'exam-subjects' ),
				array( 'name' => 'file', 'label' => 'Tệp (PDF, DOC, DOCX, XLS, XLSX, tối đa 50MB)', 'type' => 'file', 'required_on_create' => true, 'wide' => true ),
				array( 'name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'wide' => true ),
			),
			'public'   => array( 'fn' => 'cvc_document_url', 'key' => 'slug', 'statuses' => array( 'published' ) ),
		),

		'orders' => array(
			'label'     => 'Đơn hàng',
			'singular'  => 'đơn hàng',
			'icon'      => 'fa-receipt',
			'endpoint'  => '/api/admin/orders',
			'module'    => 'order',
			'title'     => 'code',
			'read_only' => true,
			'columns'   => array(
				array( 'key' => 'code', 'label' => 'Mã đơn', 'link' => true ),
				array( 'key' => 'user.email', 'label' => 'Người mua' ),
				array( 'key' => 'total_amount', 'label' => 'Số tiền', 'format' => 'money' ),
				array( 'key' => 'payment_method', 'label' => 'Thanh toán' ),
				array( 'key' => 'created_at', 'label' => 'Ngày tạo', 'format' => 'datetime' ),
				array( 'key' => 'status', 'label' => 'Trạng thái', 'format' => 'status' ),
			),
			'filters'   => array(
				'status' => array( 'label' => 'Trạng thái', 'options' => array( 'pending' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'cancelled' => 'Đã hủy' ) ),
			),
			'fields'    => array(),
			'panels'    => array( 'order_detail' ),
		),

		'users' => array(
			'label'     => 'Người dùng',
			'singular'  => 'người dùng',
			'icon'      => 'fa-users',
			'endpoint'  => '/api/admin/users',
			'module'    => 'user',
			'title'     => 'name',
			'read_only' => true,
			'columns'   => array(
				array( 'key' => 'id', 'label' => 'ID' ),
				array( 'key' => 'name', 'label' => 'Họ tên', 'link' => true ),
				array( 'key' => 'email', 'label' => 'Email' ),
				array( 'key' => 'roles', 'label' => 'Vai trò', 'format' => 'list' ),
				array( 'key' => 'created_at', 'label' => 'Ngày tạo', 'format' => 'date' ),
			),
			'filters'   => array(
				'role' => array( 'label' => 'Vai trò', 'options' => array( 'staff' => 'Nhân sự vận hành', 'SUPER_ADMIN' => 'Super Admin', 'ADMIN' => 'Admin', 'EDITOR' => 'Biên tập', 'REVIEWER' => 'Kiểm duyệt', 'INSTRUCTOR' => 'Giảng viên', 'USER' => 'Học viên' ) ),
			),
			'fields'    => array(),
			'panels'    => array( 'user_roles' ),
		),
	);

	return $resources;
}

function cvc_admin_resource( string $key ): ?array {
	$all = cvc_admin_resources();

	return $all[ $key ] ?? null;
}
