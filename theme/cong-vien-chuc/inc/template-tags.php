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
	$slug       = (string) ( $recruitment['slug'] ?? '' );
	$title      = (string) ( $recruitment['title'] ?? '' );
	$summary    = $recruitment['summary'] ?? '';
	$type       = $recruitment['recruitment_type'] ?? null;
	$location   = $recruitment['location'] ?? '';
	$deadline   = $recruitment['dates']['application_deadline'] ?? null;
	$agencyName = $recruitment['agency']['name'] ?? null;
	$url        = cvc_recruitment_url( $slug );
	$tag        = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<div class="cvc-card__body">
			<?php if ( $type ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( cvc_recruitment_type_label( $type ) ); ?></span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<?php if ( $agencyName ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( $agencyName ); ?></p>
			<?php endif; ?>
			<?php if ( $location ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( $location ); ?></p>
			<?php endif; ?>
			<?php if ( $deadline ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( 'Hạn nộp: %s', cvc_format_date_vn( $deadline ) ) ); ?></p>
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
