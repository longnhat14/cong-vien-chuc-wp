<?php
/**
 * Helper render các state dùng chung khi hiển thị dữ liệu từ Laravel API:
 * loading, empty, error. Các trang domain (Courses, Topics, ...) nên dùng
 * lại các hàm này thay vì tự viết markup riêng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_render_loading_state( string $message = 'Đang tải dữ liệu…' ): void {
	printf(
		'<div class="cvc-state cvc-state--loading">%s</div>',
		esc_html( $message )
	);
}

function cvc_render_empty_state( string $message = 'Chưa có dữ liệu.' ): void {
	printf(
		'<div class="cvc-state cvc-state--empty">%s</div>',
		esc_html( $message )
	);
}

function cvc_render_error_state( string $message = 'Không thể tải dữ liệu, vui lòng thử lại sau.' ): void {
	printf(
		'<div class="cvc-state cvc-state--error">%s</div>',
		esc_html( $message )
	);
}

/**
 * State cho resource không tồn tại (course/topic/lesson slug hoặc id sai).
 * Khác với empty state (danh sách rỗng) và error state (lỗi tạm thời).
 */

/**
 * Section chính đang active dựa trên route hiện tại - dùng cho nav
 * fallback (menu Primary có thể tự set current-menu-item qua wp_nav_menu
 * khi được gán trong wp-admin, không cần hàm này).
 */
function cvc_is_nav_section_active( string $section ): bool {
	$page = (string) get_query_var( 'cvc_page' );

	switch ( $section ) {
		case 'home':
			return '' === $page && is_front_page();
		case 'courses':
			return in_array( $page, array( 'courses', 'course-detail', 'course-lesson' ), true );
		case 'topics':
			return in_array( $page, array( 'topics', 'topic-detail' ), true );
		case 'recruitments':
			return in_array( $page, array( 'recruitments', 'recruitment-detail' ), true );
		case 'knowledge':
			return in_array( $page, array( 'knowledge', 'knowledge-detail' ), true );
		case 'exams':
			return in_array( $page, array( 'exams', 'exam-detail' ), true );
		case 'legal-documents':
			return in_array( $page, array( 'legal-documents', 'legal-document-detail' ), true );
	}

	return false;
}

/**
 * Danh sách item navigation chính - nguồn duy nhất dùng chung cho nav
 * fallback (header) và footer, tránh khai báo trùng URL/label ở 2 nơi.
 * Chỉ liệt kê domain đã có route thật.
 *
 * @return array<string, array{label: string, url: string}>
 */
function cvc_get_primary_nav_items(): array {
	return array(
		'home'            => array(
			'label' => 'Trang chủ',
			'url'   => home_url( '/' ),
		),
		'courses'         => array(
			'label' => 'Khóa học',
			'url'   => cvc_courses_url(),
		),
		'topics'          => array(
			'label' => 'Chủ đề',
			'url'   => cvc_topics_url(),
		),
		'recruitments'    => array(
			'label' => 'Tuyển dụng',
			'url'   => cvc_recruitments_url(),
		),
		'knowledge'       => array(
			'label' => 'Kiến thức',
			'url'   => cvc_knowledge_url(),
		),
		'exams'           => array(
			'label' => 'Thi trắc nghiệm',
			'url'   => cvc_exams_url(),
		),
		'legal-documents' => array(
			'label' => 'Văn bản pháp luật',
			'url'   => cvc_legal_documents_url(),
		),
	);
}

/**
 * Menu mặc định khi chưa gán menu "Primary" trong wp-admin - đảm bảo
 * luôn có internal link tới các trang domain chính cho SEO + có active
 * state để người dùng biết đang ở đâu.
 */
function cvc_default_nav_fallback(): void {
	?>
	<ul>
		<?php foreach ( cvc_get_primary_nav_items() as $section => $item ) : ?>
			<?php $is_active = cvc_is_nav_section_active( $section ); ?>
			<li class="<?php echo $is_active ? 'cvc-nav-current' : ''; ?>">
				<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Danh sách link footer, dùng chung cho footer.php - tránh hardcode
 * lại URL domain chính ở nhiều nơi.
 */
function cvc_render_footer_nav(): void {
	?>
	<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'cong-vien-chuc' ); ?>">
		<ul>
			<?php foreach ( cvc_get_primary_nav_items() as $item ) : ?>
				<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Header 1 section trên homepage: tiêu đề + link "Xem tất cả" tới trang
 * danh sách đầy đủ tương ứng.
 */
function cvc_render_section_header( string $title, string $more_label, string $more_url ): void {
	?>
	<div class="cvc-section__header">
		<h2><?php echo esc_html( $title ); ?></h2>
		<a class="cvc-section__more" href="<?php echo esc_url( $more_url ); ?>">
			<?php echo esc_html( $more_label ); ?> &rarr;
		</a>
	</div>
	<?php
}

/**
 * Format ngày kiểu Việt Nam (dd/mm/yyyy). Trả '' nếu rỗng/không hợp lệ -
 * caller tự quyết định có hiển thị dòng đó hay không.
 */
function cvc_format_date_vn( ?string $date ): string {
	if ( empty( $date ) ) {
		return '';
	}

	$timestamp = strtotime( $date );

	if ( false === $timestamp ) {
		return '';
	}

	return date_i18n( 'd/m/Y', $timestamp );
}

/**
 * Nhãn tiếng Việt cho recruitment_type - map từ đúng 3 giá trị enum được
 * validate ở RecruitmentController (civil_servant/public_employee/other).
 * Không áp dụng cách này cho các field dạng chuỗi tự do khác (exam_type,
 * document_type,...) vì backend không giới hạn enum cho chúng.
 */
function cvc_recruitment_type_label( ?string $type ): string {
	$labels = array(
		'civil_servant'   => 'Công chức',
		'public_employee' => 'Viên chức',
		'other'           => 'Khác',
	);

	return $labels[ $type ] ?? (string) $type;
}

/**
 * Card hiển thị 1 tin tuyển dụng. Chỉ hiển thị field thực sự có trong
 * response của GET /api/recruitments.
 *
 * @param array<string, mixed> $recruitment
 */
function cvc_render_recruitment_card( array $recruitment, int $heading_level = 2 ): void {
	$slug            = (string) ( $recruitment['slug'] ?? '' );
	$title           = (string) ( $recruitment['title'] ?? '' );
	$code            = $recruitment['code'] ?? null;
	$summary         = $recruitment['summary'] ?? '';
	$type            = $recruitment['recruitment_type'] ?? null;
	$location        = $recruitment['location'] ?? '';
	$deadline        = $recruitment['dates']['application_deadline'] ?? null;
	$announcedAt     = $recruitment['dates']['announcement_date'] ?? null;
	$totalPositions  = $recruitment['total_positions'] ?? null;
	$agencyName      = $recruitment['agency']['name'] ?? null;
	$url             = cvc_recruitment_url( $slug );
	$tag             = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<div class="cvc-card__body">
			<?php if ( $type ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( cvc_recruitment_type_label( $type ) ); ?></span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $code ) : ?>
				<p class="cvc-card__meta">Mã tin: <?php echo esc_html( $code ); ?></p>
			<?php endif; ?>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<?php if ( $agencyName ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( $agencyName ); ?></p>
			<?php endif; ?>
			<?php if ( $location ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( $location ); ?></p>
			<?php endif; ?>
			<?php if ( null !== $totalPositions ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d chỉ tiêu', (int) $totalPositions ) ); ?></p>
			<?php endif; ?>
			<?php if ( $deadline ) : ?>
				<p class="cvc-card__meta cvc-card__meta--highlight"><?php echo esc_html( sprintf( 'Hạn nộp: %s', cvc_format_date_vn( $deadline ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $announcedAt ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( 'Đăng ngày: %s', cvc_format_date_vn( $announcedAt ) ) ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Xem chi tiết &rarr;</a>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Card hiển thị 1 knowledge item. Chỉ hiển thị field thực sự có trong
 * response của GET /api/knowledge-items.
 *
 * @param array<string, mixed> $item
 */
function cvc_render_knowledge_card( array $item, int $heading_level = 2 ): void {
	$slug       = (string) ( $item['slug'] ?? '' );
	$title      = (string) ( $item['title'] ?? '' );
	$summary    = $item['summary'] ?? '';
	$topicName  = $item['topic']['name'] ?? null;
	$url        = cvc_knowledge_item_url( $slug );
	$tag        = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<div class="cvc-card__body">
			<?php if ( $topicName ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $topicName ); ?></span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Đọc tiếp &rarr;</a>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Card hiển thị 1 đề thi. Chỉ hiển thị field thực sự có trong response
 * của GET /api/exams - không hiển thị câu hỏi/đáp án ở đây.
 *
 * @param array<string, mixed> $exam
 */
function cvc_render_exam_card( array $exam, int $heading_level = 2 ): void {
	$slug            = (string) ( $exam['slug'] ?? '' );
	$title           = (string) ( $exam['title'] ?? '' );
	$description     = $exam['description'] ?? '';
	$questionsCount  = $exam['questions_count'] ?? $exam['total_questions'] ?? null;
	$durationMinutes = $exam['duration_minutes'] ?? null;
	$url             = cvc_exam_url( $slug );
	$tag             = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<div class="cvc-card__body">
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $description ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
			<?php if ( null !== $questionsCount ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d câu hỏi', (int) $questionsCount ) ); ?></p>
			<?php endif; ?>
			<?php if ( $durationMinutes ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d phút', (int) $durationMinutes ) ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Xem đề thi &rarr;</a>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Danh sách môn thi (exam_subjects) lồng trong 1 Position hoặc 1 Exam của
 * Recruitment detail - dùng chung cho cả 2 chỗ vì shape giống nhau
 * (id/code/name/slug/subject_type + pivot weight/sort_order). Chỉ render
 * khi API thực sự trả mảng này (Phase 3.6, Phần 10).
 *
 * @param array<int, array<string, mixed>> $exam_subjects
 */
function cvc_render_exam_subject_list( array $exam_subjects ): void {
	if ( empty( $exam_subjects ) ) {
		return;
	}
	?>
	<ul class="cvc-related-list cvc-related-list--inline">
		<?php foreach ( $exam_subjects as $subject ) : ?>
			<?php if ( empty( $subject['name'] ) ) : continue; endif; ?>
			<li>
				<?php echo esc_html( $subject['name'] ); ?>
				<?php if ( ! empty( $subject['is_required'] ) ) : ?>
					<span class="cvc-card__meta">(bắt buộc)</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Card hiển thị 1 văn bản pháp luật. Chỉ hiển thị field thực sự có trong
 * response của GET /api/legal-documents (file_path/file_hash đã bị
 * backend ẩn - không cố lấy thêm field nào khác ngoài response).
 *
 * @param array<string, mixed> $document
 */
function cvc_render_legal_document_card( array $document, int $heading_level = 2 ): void {
	$slug          = (string) ( $document['slug'] ?? '' );
	$title         = (string) ( $document['title'] ?? '' );
	$summary       = $document['summary'] ?? '';
	$documentNumber = $document['document_number'] ?? null;
	$issuingAgency  = $document['issuing_agency'] ?? null;
	$effectiveDate  = $document['effective_date'] ?? null;
	$url            = cvc_legal_document_url( $slug );
	$tag            = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<div class="cvc-card__body">
			<?php if ( $documentNumber ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $documentNumber ); ?></span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<?php if ( $issuingAgency ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( $issuingAgency ); ?></p>
			<?php endif; ?>
			<?php if ( $effectiveDate ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( 'Hiệu lực: %s', cvc_format_date_vn( $effectiveDate ) ) ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Xem văn bản &rarr;</a>
			</p>
		</div>
	</article>
	<?php
}

function cvc_render_notfound_state( string $message ): void {
	printf(
		'<div class="cvc-state cvc-state--notfound">%s</div>',
		esc_html( $message )
	);
}

/**
 * Thông báo "đã hết hạn" - dùng khi API trả status=expired. KHÔNG bao giờ
 * tự tính expired từ application_deadline ở phía WordPress - status luôn
 * lấy nguyên từ response (xem Recruitment Detail, Phase 3.6).
 */
function cvc_render_expired_state( string $message ): void {
	printf(
		'<div class="cvc-state cvc-state--expired">%s</div>',
		esc_html( $message )
	);
}

/**
 * Breadcrumb đơn giản, semantic HTML. Item cuối luôn là trang hiện tại
 * (không link) dù có truyền url hay không.
 *
 * @param array<int, array{label: string, url?: string}> $items
 */
function cvc_render_breadcrumbs( array $items ): void {
	if ( empty( $items ) ) {
		return;
	}

	$last_index = array_key_last( $items );
	?>
	<nav class="cvc-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'cong-vien-chuc' ); ?>">
		<ol>
			<?php foreach ( $items as $index => $item ) :
				$label = $item['label'] ?? '';
				$url   = $item['url'] ?? '';
				?>
				<li>
					<?php if ( $url && $index !== $last_index ) : ?>
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $label ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * BreadcrumbList JSON-LD từ ĐÚNG cùng mảng $items đã dùng cho
 * cvc_render_breadcrumbs() - tránh duy trì 2 nguồn dữ liệu breadcrumb
 * khác nhau (Phase 3.7, Phần 16). Item cuối (trang hiện tại) không có
 * "item" URL - đúng theo cách cvc_render_breadcrumbs() xử lý và được
 * Google's BreadcrumbList spec cho phép.
 *
 * @param array<int, array{label: string, url?: string}> $items
 */
function cvc_seo_add_breadcrumb_jsonld( array $items ): void {
	if ( empty( $items ) ) {
		return;
	}

	$list_items = array();

	foreach ( array_values( $items ) as $index => $item ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => (string) ( $item['label'] ?? '' ),
		);

		if ( ! empty( $item['url'] ) ) {
			$entry['item'] = $item['url'];
		}

		$list_items[] = $entry;
	}

	cvc_seo_add_json_ld(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list_items,
		)
	);
}

/**
 * JobPosting JSON-LD cho 1 recruitment - CHỈ dùng field thực sự có trong
 * response của GET /api/recruitments/{slug} (Phase 3.7, Phần 15).
 *
 * Cố ý KHÔNG map employmentType: Position.employment_type là free text ở
 * backend (không phải enum chuẩn schema.org FULL_TIME/PART_TIME/...),
 * map sai sẽ là suy đoán - deferred, xem FINAL REPORT.
 *
 * validThrough dùng application_deadline kể cả khi recruitment đã hết
 * hạn (status=expired) - đây là semantics ĐÚNG của schema.org (báo hiệu
 * tin đã hết hạn cho search engine), không phải lỗi cần che giấu.
 *
 * @param array<string, mixed> $recruitment Response data của show().
 * @return array<string, mixed>|null Null nếu thiếu dữ liệu tối thiểu bắt buộc.
 */
function cvc_build_recruitment_job_posting_jsonld( array $recruitment ): ?array {
	$agency = is_array( $recruitment['agency'] ?? null ) ? $recruitment['agency'] : null;

	if ( empty( $recruitment['title'] ) || ! $agency || empty( $agency['name'] ) ) {
		return null;
	}

	$dates     = is_array( $recruitment['dates'] ?? null ) ? $recruitment['dates'] : array();
	$province  = is_array( $recruitment['province'] ?? null ) ? $recruitment['province'] : null;
	$adminUnit = is_array( $recruitment['admin_unit'] ?? null ) ? $recruitment['admin_unit'] : null;

	$schema = array(
		'@context'           => 'https://schema.org',
		'@type'              => 'JobPosting',
		'title'              => (string) $recruitment['title'],
		// Fallback description = title khi summary rỗng: không fake nội
		// dung, chỉ tái dùng chính title đã có (Phần 14/15).
		'description'        => ! empty( $recruitment['summary'] )
			? (string) $recruitment['summary']
			: (string) $recruitment['title'],
		'hiringOrganization' => array(
			'@type' => 'Organization',
			'name'  => (string) $agency['name'],
		),
	);

	if ( ! empty( $agency['website'] ) ) {
		$schema['hiringOrganization']['sameAs'] = (string) $agency['website'];
	}

	if ( ! empty( $recruitment['code'] ) ) {
		$schema['identifier'] = array(
			'@type' => 'PropertyValue',
			'name'  => (string) $agency['name'],
			'value' => (string) $recruitment['code'],
		);
	}

	if ( ! empty( $dates['announcement_date'] ) ) {
		$schema['datePosted'] = (string) $dates['announcement_date'];
	}

	if ( ! empty( $dates['application_deadline'] ) ) {
		$schema['validThrough'] = (string) $dates['application_deadline'];
	}

	if ( ( $province && ! empty( $province['name'] ) ) || ( $adminUnit && ! empty( $adminUnit['name'] ) ) ) {
		$address = array(
			'@type'          => 'PostalAddress',
			'addressCountry' => 'VN',
		);

		if ( $adminUnit && ! empty( $adminUnit['name'] ) ) {
			$address['addressLocality'] = (string) $adminUnit['name'];
		}

		if ( $province && ! empty( $province['name'] ) ) {
			$address['addressRegion'] = (string) $province['name'];
		}

		$schema['jobLocation'] = array(
			'@type'   => 'Place',
			'address' => $address,
		);
	}

	return $schema;
}

/**
 * Pagination Trước/Sau dùng chung cho các trang danh sách.
 *
 * @param callable $url_builder Nhận (int $page): string, trả về URL trang đó.
 */
function cvc_render_pagination( int $current_page, int $last_page, callable $url_builder ): void {
	if ( $last_page <= 1 ) {
		return;
	}
	?>
	<nav class="cvc-pagination" aria-label="<?php esc_attr_e( 'Phân trang', 'cong-vien-chuc' ); ?>">
		<?php if ( $current_page > 1 ) : ?>
			<a class="cvc-pagination__link cvc-pagination__link--prev" href="<?php echo esc_url( $url_builder( $current_page - 1 ) ); ?>">&laquo; Trước</a>
		<?php else : ?>
			<span class="cvc-pagination__link cvc-pagination__link--disabled">&laquo; Trước</span>
		<?php endif; ?>

		<span class="cvc-pagination__status">
			<?php echo esc_html( sprintf( 'Trang %1$d / %2$d', $current_page, $last_page ) ); ?>
		</span>

		<?php if ( $current_page < $last_page ) : ?>
			<a class="cvc-pagination__link cvc-pagination__link--next" href="<?php echo esc_url( $url_builder( $current_page + 1 ) ); ?>">Sau &raquo;</a>
		<?php else : ?>
			<span class="cvc-pagination__link cvc-pagination__link--disabled">Sau &raquo;</span>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * Badge miễn phí / trả phí dùng chung cho lesson card + lesson list.
 */
function cvc_render_free_badge( bool $is_free ): void {
	if ( $is_free ) {
		echo '<span class="cvc-badge cvc-badge--free">Miễn phí</span>';
		return;
	}

	echo '<span class="cvc-badge cvc-badge--paid">Trả phí</span>';
}

/**
 * Card hiển thị 1 course trong danh sách. Chỉ hiển thị field thực sự có
 * trong API response của GET /api/courses - không suy diễn thêm field.
 *
 * @param array<string, mixed> $course
 * @param int $heading_level Cấp heading cho tên course - 2 khi card nằm
 *                            ngay dưới H1 (trang danh sách), 3 khi card
 *                            nằm trong 1 section có H2 riêng (homepage).
 */
function cvc_render_course_card( array $course, int $heading_level = 2 ): void {
	$slug        = (string) ( $course['slug'] ?? '' );
	$title       = (string) ( $course['title'] ?? '' );
	$summary     = $course['short_description'] ?? '';
	$thumbnail   = $course['thumbnail_url'] ?? null;
	$lessonCount = $course['published_lessons_count'] ?? $course['lesson_count'] ?? null;
	$isFeatured  = ! empty( $course['is_featured'] );
	$url         = cvc_course_url( $slug );
	$tag         = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<?php if ( $thumbnail ) : ?>
			<a class="cvc-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1">
				<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			</a>
		<?php endif; ?>
		<div class="cvc-card__body">
			<?php if ( $isFeatured ) : ?>
				<span class="cvc-badge cvc-badge--featured">Nổi bật</span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<?php if ( null !== $lessonCount ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d bài học', (int) $lessonCount ) ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Xem khóa học &rarr;</a>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Card hiển thị 1 topic trong danh sách. Chỉ hiển thị field thực sự có
 * trong API response của GET /api/topics.
 *
 * @param array<string, mixed> $topic
 * @param int $heading_level Xem cvc_render_course_card().
 */
function cvc_render_topic_card( array $topic, int $heading_level = 2 ): void {
	$slug        = (string) ( $topic['slug'] ?? '' );
	$name        = (string) ( $topic['name'] ?? '' );
	$description = $topic['description'] ?? '';
	$itemCount   = $topic['published_knowledge_items_count'] ?? null;
	$subjectName = $topic['exam_subject']['name'] ?? null;
	$url         = cvc_topic_url( $slug );
	$tag         = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<div class="cvc-card__body">
			<?php if ( $subjectName ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $subjectName ); ?></span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $description ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
			<?php if ( null !== $itemCount ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d kiến thức', (int) $itemCount ) ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Xem chủ đề &rarr;</a>
			</p>
		</div>
	</article>
	<?php
}

/**
 * ============================================================
 * SEARCH - mapping tập trung + adapter sang shape từng card renderer
 * đã có sẵn đang mong đợi (KHÔNG sửa các renderer ở trên).
 * ============================================================
 *
 * Nguồn duy nhất cho:
 * - danh sách type hợp lệ của query param "type" (khớp whitelist của
 *   GET /api/search bên Laravel - all/courses/topics/recruitments/
 *   knowledge/exams/legal-documents).
 * - label tiếng Việt cho filter UI.
 * - "result_type" - giá trị field "type" mà mỗi search result trả về
 *   (course/topic/recruitment/knowledge/exam/legal_document).
 * - adapter chuyển 1 search result {type,id,title,slug,excerpt,meta}
 *   sang đúng shape mà card renderer domain đó cần (shape của list()
 *   API gốc), và tên renderer tương ứng.
 *
 * @return array<string, array{label: string, result_type: string, adapt: callable, render: callable}>
 */
function cvc_search_domains(): array {
	return array(
		'courses'         => array(
			'label'       => 'Khóa học',
			'result_type' => 'course',
			'adapt'       => function ( array $result ): array {
				$meta = $result['meta'] ?? array();
				return array(
					'slug'              => $result['slug'] ?? '',
					'title'             => $result['title'] ?? '',
					'short_description' => $result['excerpt'] ?? '',
					'thumbnail_url'     => $meta['thumbnail_url'] ?? null,
					'is_featured'       => $meta['is_featured'] ?? false,
				);
			},
			'render'      => 'cvc_render_course_card',
		),
		'topics'          => array(
			'label'       => 'Chủ đề',
			'result_type' => 'topic',
			'adapt'       => function ( array $result ): array {
				$meta = $result['meta'] ?? array();
				return array(
					'slug'         => $result['slug'] ?? '',
					'name'         => $result['title'] ?? '',
					'description'  => $result['excerpt'] ?? '',
					'exam_subject' => ! empty( $meta['exam_subject'] ) ? array( 'name' => $meta['exam_subject'] ) : null,
				);
			},
			'render'      => 'cvc_render_topic_card',
		),
		'recruitments'    => array(
			'label'       => 'Tuyển dụng',
			'result_type' => 'recruitment',
			'adapt'       => function ( array $result ): array {
				$meta = $result['meta'] ?? array();
				return array(
					'slug'             => $result['slug'] ?? '',
					'title'            => $result['title'] ?? '',
					'summary'          => $result['excerpt'] ?? '',
					'recruitment_type' => $meta['recruitment_type'] ?? null,
					'location'         => $meta['location'] ?? '',
					'dates'            => array( 'application_deadline' => $meta['application_deadline'] ?? null ),
					'agency'           => ! empty( $meta['agency'] ) ? array( 'name' => $meta['agency'] ) : null,
				);
			},
			'render'      => 'cvc_render_recruitment_card',
		),
		'knowledge'       => array(
			'label'       => 'Kiến thức',
			'result_type' => 'knowledge',
			'adapt'       => function ( array $result ): array {
				$meta = $result['meta'] ?? array();
				return array(
					'slug'    => $result['slug'] ?? '',
					'title'   => $result['title'] ?? '',
					'summary' => $result['excerpt'] ?? '',
					'topic'   => ! empty( $meta['topic'] ) ? array( 'name' => $meta['topic'] ) : null,
				);
			},
			'render'      => 'cvc_render_knowledge_card',
		),
		'exams'           => array(
			'label'       => 'Thi trắc nghiệm',
			'result_type' => 'exam',
			'adapt'       => function ( array $result ): array {
				$meta = $result['meta'] ?? array();
				return array(
					'slug'             => $result['slug'] ?? '',
					'title'            => $result['title'] ?? '',
					'description'      => $result['excerpt'] ?? '',
					'duration_minutes' => $meta['duration_minutes'] ?? null,
					'total_questions'  => $meta['total_questions'] ?? null,
				);
			},
			'render'      => 'cvc_render_exam_card',
		),
		'legal-documents' => array(
			'label'       => 'Văn bản pháp luật',
			'result_type' => 'legal_document',
			'adapt'       => function ( array $result ): array {
				$meta = $result['meta'] ?? array();
				return array(
					'slug'            => $result['slug'] ?? '',
					'title'           => $result['title'] ?? '',
					'summary'         => $result['excerpt'] ?? '',
					'document_number' => $meta['document_number'] ?? null,
					'issuing_agency'  => $meta['issuing_agency'] ?? null,
					'effective_date'  => $meta['effective_date'] ?? null,
				);
			},
			'render'      => 'cvc_render_legal_document_card',
		),
	);
}

/**
 * Danh sách type hợp lệ cho query param "type" (dùng để validate URL
 * + build filter UI) - luôn gồm 'all' + các key của cvc_search_domains().
 *
 * @return array<int, string>
 */
function cvc_search_valid_types(): array {
	return array_merge( array( 'all' ), array_keys( cvc_search_domains() ) );
}

/**
 * Render 1 search result bằng đúng card renderer của domain tương ứng,
 * qua adapter để khớp shape. Type lạ (API version sau này thêm domain
 * mới mà theme chưa biết) - bỏ qua an toàn, ghi log server-side, không
 * hiển thị lỗi cho người dùng và không crash cả trang.
 *
 * @param array<string, mixed> $result Một phần tử trong data.data của
 *                                     GET /api/search.
 */
function cvc_render_search_result( array $result, int $heading_level = 2 ): void {
	$result_type = (string) ( $result['type'] ?? '' );

	foreach ( cvc_search_domains() as $domain ) {
		if ( $domain['result_type'] === $result_type ) {
			$adapted = ( $domain['adapt'] )( $result );
			( $domain['render'] )( $adapted, $heading_level );
			return;
		}
	}

	error_log( sprintf( '[cong-vien-chuc] Search result có type không xác định: %s', $result_type ) );
}

/**
 * Form tìm kiếm dùng chung cho trang /tim-kiem/ và homepage - tránh
 * lặp markup ở 2 nơi. GET thuần, không session, giữ lại $current_q.
 */
function cvc_render_search_form( string $current_q = '', string $input_id = 'cvc-search-q' ): void {
	?>
	<form class="cvc-search-form" method="get" action="<?php echo esc_url( home_url( '/tim-kiem/' ) ); ?>" role="search">
		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">Từ khóa tìm kiếm</label>
		<input
			type="search"
			id="<?php echo esc_attr( $input_id ); ?>"
			name="q"
			class="cvc-search-form__input"
			value="<?php echo esc_attr( $current_q ); ?>"
			placeholder="Tìm khóa học, chủ đề, tuyển dụng, văn bản..."
			autocomplete="off"
		>
		<button type="submit" class="cvc-btn cvc-btn--primary">Tìm kiếm</button>
	</form>
	<?php
}
