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
 * Flash notice (Phase 10) - đọc cookie cvc_notice đã set bởi
 * cvc_redirect_with_notice() (Post/Redirect/Get). In ra 1 lần rồi thôi -
 * cookie đã bị xoá ngay trong cvc_consume_notice().
 */
function cvc_render_notice(): void {
	$notice = cvc_consume_notice();

	if ( null === $notice ) {
		return;
	}

	printf(
		'<div class="cvc-notice cvc-notice--%s" role="status">%s</div>',
		esc_attr( 'success' === $notice['type'] ? 'success' : 'error' ),
		esc_html( $notice['message'] )
	);
}

/**
 * State cho resource không tồn tại (course/topic/lesson slug hoặc id sai).
 * Khác với empty state (danh sách rỗng) và error state (lỗi tạm thời).
 */

/**
 * Vùng đăng nhập/tài khoản trên header (Phase 10, Phần VIII/XIII) - đã
 * đăng nhập thì hiện link Tài khoản + số thông báo chưa đọc (1 API call
 * GET /api/notifications/unread-count MỖI page load khi đã đăng nhập -
 * chấp nhận được vì đây chính là yêu cầu "header phải phản ánh trạng thái
 * đăng nhập + notification indicator", không phải call thừa); chưa đăng
 * nhập thì hiện Đăng nhập/Đăng ký.
 */
function cvc_render_header_auth_area(): void {
	if ( ! cvc_is_logged_in() ) {
		printf(
			'<div class="site-header__auth"><a href="%s" class="cvc-btn cvc-btn--small cvc-btn--secondary">%s</a><a href="%s" class="cvc-btn cvc-btn--small cvc-btn--primary">%s</a></div>',
			esc_url( cvc_register_url() ),
			esc_html__( 'Đăng ký', 'cong-vien-chuc' ),
			esc_url( cvc_login_url() ),
			esc_html__( 'Đăng nhập', 'cong-vien-chuc' )
		);
		return;
	}

	$token         = cvc_auth_token();
	$unread_result = null !== $token ? ( new CVC_Notification_Service() )->unreadCount( $token ) : array( 'ok' => false );
	$unread        = ( $unread_result['ok'] ?? false ) ? (int) ( $unread_result['data']['data']['unread_count'] ?? 0 ) : 0;
	?>
	<div class="site-header__auth">
		<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="site-header__account-link">
			<?php esc_html_e( 'Tài khoản', 'cong-vien-chuc' ); ?>
			<?php if ( $unread > 0 ) : ?>
				<span class="cvc-notification-badge"><?php echo esc_html( (string) $unread ); ?></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( cvc_logout_url() ); ?>"><?php esc_html_e( 'Đăng xuất', 'cong-vien-chuc' ); ?></a>
	</div>
	<?php
}

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
			'icon'  => 'home',
		),
		'courses'         => array(
			'label' => 'Khóa học',
			'url'   => cvc_courses_url(),
			'icon'  => 'courses',
		),
		'topics'          => array(
			'label' => 'Chủ đề',
			'url'   => cvc_topics_url(),
			'icon'  => 'topics',
		),
		'recruitments'    => array(
			'label' => 'Tuyển dụng',
			'url'   => cvc_recruitments_url(),
			'icon'  => 'recruitments',
		),
		'knowledge'       => array(
			'label' => 'Kiến thức',
			'url'   => cvc_knowledge_url(),
			'icon'  => 'knowledge',
		),
		'exams'           => array(
			'label' => 'Thi trắc nghiệm',
			'url'   => cvc_exams_url(),
			'icon'  => 'exams',
		),
		'legal-documents' => array(
			'label' => 'Văn bản pháp luật',
			'url'   => cvc_legal_documents_url(),
			'icon'  => 'legal',
		),
	);
}

/**
 * Icon inline SVG cho 1 nav item (Phase 10A.2, Phần 14) - stroke dùng
 * currentColor để tự đổi màu theo trạng thái active/hover mà không cần
 * biến thể icon riêng. Chỉ 7 icon cố định (đúng 7 domain thật đang có
 * route) - không cần icon font/sprite chỉ để dùng 7 icon.
 */
function cvc_render_nav_icon( string $key ): void {
	$paths = array(
		'home'         => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
		'courses'      => '<path d="M3 6.5 12 3l9 3.5-9 3.5-9-3.5Z"/><path d="M7 9v5c0 1.1 2.24 2 5 2s5-.9 5-2V9"/><path d="M21 6.5v6"/>',
		'topics'       => '<path d="M4 4.5h11a2 2 0 0 1 2 2V20H6a2 2 0 0 1-2-2V4.5Z"/><path d="M8 9h6M8 12.5h6"/>',
		'recruitments' => '<rect x="3.5" y="7.5" width="17" height="12" rx="2"/><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5"/><path d="M3.5 12.5h17"/>',
		'knowledge'    => '<path d="M12 4.5c-2-1.2-5-1.2-7 0v13c2-1.2 5-1.2 7 0m0-13c2-1.2 5-1.2 7 0v13c-2-1.2-5-1.2-7 0m0-13v13"/>',
		'exams'        => '<path d="M6 3.5h9l3 3V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M9 12l2 2 4-4.5"/>',
		'legal'        => '<path d="M12 3.5v17M6 6.5 3 12l3 5.5c1.8 1 4.2 1 6 0M18 6.5 15 12l3 5.5c1.8 1 4.2 1 6 0"/><path d="M4.5 6.5h15"/>',
	);

	if ( ! isset( $paths[ $key ] ) ) {
		return;
	}

	printf(
		'<svg class="cvc-nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
		$paths[ $key ] // phpcs:ignore -- path SVG tĩnh, hardcode trong theme, không phải input người dùng.
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
					<?php cvc_render_nav_icon( $item['icon'] ?? '' ); ?>
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
function cvc_render_section_header( string $title, string $more_label, string $more_url, string $icon = '' ): void {
	?>
	<div class="cvc-section__header">
		<h2>
			<?php if ( '' !== $icon ) : ?>
				<span class="cvc-section__header-icon" aria-hidden="true"><?php echo $icon; // phpcs:ignore -- HTML entity tĩnh. ?></span>
			<?php endif; ?>
			<?php echo esc_html( $title ); ?>
		</h2>
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
	<?php
	/*
	 * GET /api/recruitments (list, dùng cho card này) chỉ trả bản ghi
	 * status=published (RecruitmentController::index() where('status',
	 * 'published')) - "Đang tuyển" ở đây là sự thật về CHÍNH tập kết quả
	 * này, không phải suy đoán (bản ghi expired chỉ xuất hiện ở trang
	 * detail, không bao giờ lọt vào danh sách/card).
	 */
	?>
	<article class="cvc-card cvc-card--recruitment">
		<div class="cvc-card__body">
			<div class="cvc-card__badges">
				<span class="cvc-badge cvc-badge--status cvc-badge--status-active">Đang tuyển</span>
				<?php if ( $type ) : ?>
					<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( cvc_recruitment_type_label( $type ) ); ?></span>
				<?php endif; ?>
			</div>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $agencyName || $location ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( implode( ' · ', array_filter( array( $agencyName, $location ) ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<?php if ( null !== $totalPositions ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d chỉ tiêu', (int) $totalPositions ) ); ?></p>
			<?php endif; ?>
			<?php if ( $code ) : ?>
				<p class="cvc-card__meta">Mã tin: <?php echo esc_html( $code ); ?></p>
			<?php endif; ?>
			<?php if ( $announcedAt ) : ?>
				<p class="cvc-card__meta">Đăng ngày: <?php echo esc_html( cvc_format_date_vn( $announcedAt ) ); ?></p>
			<?php endif; ?>
			<div class="cvc-card__footer cvc-card__footer--split">
				<?php if ( $deadline ) : ?>
					<span class="cvc-card__deadline">Hạn nộp: <strong><?php echo esc_html( cvc_format_date_vn( $deadline ) ); ?></strong></span>
				<?php endif; ?>
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Xem chi tiết &rarr;</a>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Dòng compact cho 1 tin tuyển dụng trong sidebar "Tuyển dụng mới nhất"
 * trên homepage (Phase 10A.3 - đối chiếu ảnh benchmark thật: danh sách
 * dọc compact, KHÔNG phải card lưới như trang /tuyen-dung/). Avatar chữ
 * cái đầu tên cơ quan (agency KHÔNG có field logo ở backend - xem
 * Agency model - initials avatar là cách trình bày trung thực, không
 * phải suy đoán/tạo logo giả).
 */
function cvc_render_recruitment_list_item( array $recruitment ): void {
	$slug        = (string) ( $recruitment['slug'] ?? '' );
	$title       = (string) ( $recruitment['title'] ?? '' );
	$location    = $recruitment['location'] ?? '';
	$deadline    = $recruitment['dates']['application_deadline'] ?? null;
	$agencyName  = $recruitment['agency']['name'] ?? null;
	$url         = cvc_recruitment_url( $slug );
	$initial     = $agencyName ? mb_substr( $agencyName, 0, 1 ) : 'C';
	?>
	<article class="cvc-recruitment-row">
		<span class="cvc-recruitment-row__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( $initial ) ); ?></span>
		<div class="cvc-recruitment-row__body">
			<div class="cvc-recruitment-row__top">
				<h3 class="cvc-recruitment-row__title">
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>
				<span class="cvc-badge cvc-badge--status cvc-badge--status-active cvc-recruitment-row__status">Đang tuyển</span>
			</div>
			<?php if ( $agencyName ) : ?>
				<p class="cvc-recruitment-row__agency"><?php echo esc_html( $agencyName ); ?></p>
			<?php endif; ?>
			<p class="cvc-recruitment-row__meta">
				<?php if ( $location ) : ?>
					<span class="cvc-recruitment-row__meta-item"><span aria-hidden="true">&#128205;</span> <?php echo esc_html( $location ); ?></span>
				<?php endif; ?>
				<?php if ( $deadline ) : ?>
					<span class="cvc-recruitment-row__meta-item"><span aria-hidden="true">&#128197;</span> Hạn nộp: <?php echo esc_html( cvc_format_date_vn( $deadline ) ); ?></span>
				<?php endif; ?>
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
	$code       = $item['code'] ?? null;
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
			<?php if ( $code ) : ?>
				<p class="cvc-card__meta">Mã: <?php echo esc_html( $code ); ?></p>
			<?php endif; ?>
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
	$subjectNames    = is_array( $exam['exam_subjects'] ?? null )
		? array_filter( array_map( fn( $s ) => (string) ( $s['name'] ?? '' ), $exam['exam_subjects'] ) )
		: array();
	$url             = cvc_exam_url( $slug );
	$tag             = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card cvc-card--exam">
		<div class="cvc-card__accent" aria-hidden="true">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h9l3 3V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M9 12l2 2 4-4.5"/></svg>
		</div>
		<div class="cvc-card__body">
			<?php if ( ! empty( $subjectNames ) ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( implode( ', ', $subjectNames ) ); ?></span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $description ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__meta-row">
				<?php if ( ! empty( $questionsCount ) ) : ?>
					<span><?php echo esc_html( sprintf( '%d câu hỏi', (int) $questionsCount ) ); ?></span>
				<?php endif; ?>
				<?php if ( $durationMinutes ) : ?>
					<span><?php echo esc_html( sprintf( '%d phút', (int) $durationMinutes ) ); ?></span>
				<?php endif; ?>
			</p>
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
 * Article JSON-LD cho 1 knowledge item - CHỈ dùng field thực sự có trong
 * response của GET /api/knowledge-items/{slug} (Phase 4A, Phần 13).
 * created_at/updated_at có sẵn trong response (KnowledgeItem không
 * $hidden 2 field này ở model) - dùng làm datePublished/dateModified,
 * không phải suy đoán.
 *
 * @param array<string, mixed> $item
 * @return array<string, mixed>|null
 */
function cvc_build_knowledge_article_jsonld( array $item ): ?array {
	if ( empty( $item['title'] ) ) {
		return null;
	}

	$topic = is_array( $item['topic'] ?? null ) ? $item['topic'] : null;

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Article',
		'headline'    => (string) $item['title'],
		'description' => ! empty( $item['summary'] )
			? (string) $item['summary']
			: (string) $item['title'],
		'publisher'   => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( $topic && ! empty( $topic['name'] ) ) {
		$schema['articleSection'] = (string) $topic['name'];
	}

	if ( ! empty( $item['created_at'] ) ) {
		$schema['datePublished'] = (string) $item['created_at'];
	}

	if ( ! empty( $item['updated_at'] ) ) {
		$schema['dateModified'] = (string) $item['updated_at'];
	}

	return $schema;
}

/**
 * Legislation JSON-LD cho 1 văn bản pháp luật - CHỈ dùng field thực sự có
 * trong response của GET /api/legal-documents/{slug} (Phase 4A, Phần 13).
 *
 * Dùng schema.org "Legislation" thay vì "Article" chung chung vì đây là
 * mapping semantics chính xác hơn cho văn bản pháp luật (có
 * legislationIdentifier/legislationType/legislationDate khớp đúng
 * document_number/document_type/issued_date đã có).
 *
 * @param array<string, mixed> $document
 * @return array<string, mixed>|null
 */
function cvc_build_legal_document_jsonld( array $document ): ?array {
	if ( empty( $document['title'] ) ) {
		return null;
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Legislation',
		'name'        => (string) $document['title'],
		'description' => ! empty( $document['summary'] )
			? (string) $document['summary']
			: (string) $document['title'],
		// Nền tảng chỉ phục vụ văn bản pháp luật Việt Nam - đây là sự thật
		// về phạm vi platform, không phải suy đoán theo từng bản ghi.
		'jurisdiction' => 'Việt Nam',
	);

	if ( ! empty( $document['document_number'] ) ) {
		$schema['legislationIdentifier'] = (string) $document['document_number'];
	}

	if ( ! empty( $document['document_type'] ) ) {
		$schema['legislationType'] = (string) $document['document_type'];
	}

	if ( ! empty( $document['issued_date'] ) ) {
		$schema['legislationDate'] = (string) $document['issued_date'];
		$schema['datePublished']   = (string) $document['issued_date'];
	}

	if ( ! empty( $document['issuing_agency'] ) ) {
		$schema['creator'] = array(
			'@type' => 'Organization',
			'name'  => (string) $document['issuing_agency'],
		);
	}

	return $schema;
}

/**
 * Course JSON-LD cho 1 khóa học - CHỈ dùng field thực sự có trong response
 * của GET /api/courses/{slug} (Phase 4A, Phần 13).
 *
 * Cố ý KHÔNG map "offers"/price: chưa có luồng mua/đăng ký công khai
 * (enrollment/payment ngoài phạm vi phase này) - khai báo offers lúc này
 * sẽ là tuyên bố sai về khả năng mua thực tế. provider = chính nền tảng
 * (Công Viên Chức), đúng sự thật vì đây là platform xuất bản khóa học,
 * không phải suy đoán.
 *
 * @param array<string, mixed> $course
 * @return array<string, mixed>|null
 */
function cvc_build_course_jsonld( array $course ): ?array {
	if ( empty( $course['title'] ) ) {
		return null;
	}

	$description = $course['description'] ?? $course['short_description'] ?? '';

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Course',
		'name'        => (string) $course['title'],
		'description' => ! empty( $description ) ? (string) $description : (string) $course['title'],
		'provider'    => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	return $schema;
}

/**
 * Danh sách link liên quan đơn giản (chỉ tiêu đề + URL) - dùng khi chỉ
 * cần liệt kê liên kết sang domain khác, không cần đầy đủ 1 card (Phase
 * 4A, Phần 9/10). Bỏ qua item thiếu slug - không bao giờ tạo link gãy.
 *
 * @param array<int, array<string, mixed>> $items
 * @param callable(string): string         $url_builder Nhận slug, trả URL.
 * @param string                           $title_key   Field chứa tiêu đề hiển thị (VD: 'title', 'name').
 */
function cvc_render_related_link_list( array $items, callable $url_builder, string $title_key = 'title' ): void {
	$valid = array_values(
		array_filter(
			$items,
			static fn( $item ) => is_array( $item ) && ! empty( $item['slug'] )
		)
	);

	if ( empty( $valid ) ) {
		return;
	}
	?>
	<ul class="cvc-related-list">
		<?php foreach ( $valid as $item ) : ?>
			<li><a href="<?php echo esc_url( $url_builder( (string) $item['slug'] ) ); ?>"><?php echo esc_html( (string) ( $item[ $title_key ] ?? '' ) ); ?></a></li>
		<?php endforeach; ?>
	</ul>
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
/**
 * Nhãn hiển thị course_type - field này là free text ở backend (không
 * enum, xem Admin\CourseController::store() - chỉ 'nullable|string|max:50'),
 * nên KHÔNG thể lập bảng tra cứu đầy đủ như recruitment_type. Format lại
 * cho dễ đọc (bỏ underscore, viết hoa chữ đầu) - vẫn là dữ liệu thật, chỉ
 * trình bày đẹp hơn, không phải suy diễn/thêm category giả.
 */
function cvc_course_type_label( ?string $type ): string {
	if ( empty( $type ) ) {
		return '';
	}

	$known = array(
		'exam_prep'    => 'Ôn thi',
		'skill'        => 'Kỹ năng',
		'professional' => 'Chuyên môn',
		'orientation'  => 'Định hướng',
	);

	return $known[ $type ] ?? ucfirst( str_replace( array( '_', '-' ), ' ', $type ) );
}

/**
 * Map course_type -> 1 trong 4 biến thể màu cố định của design system
 * (Phase 10A.3, đối chiếu ảnh benchmark thật: mỗi category 1 màu badge
 * khác nhau trên ảnh course card). course_type là free text ở backend
 * (không enum) nên map theo hash ổn định cho type lạ - vẫn nhất quán
 * (cùng 1 type luôn ra cùng 1 màu), không suy diễn ý nghĩa gì thêm.
 */
function cvc_course_type_color( ?string $type ): string {
	$palette = array( 'blue', 'teal', 'amber', 'pink' );

	if ( empty( $type ) ) {
		return $palette[0];
	}

	$known = array(
		'exam_prep'    => 'pink',
		'skill'        => 'teal',
		'professional' => 'blue',
		'orientation'  => 'amber',
	);

	return $known[ $type ] ?? $palette[ crc32( $type ) % count( $palette ) ];
}

/**
 * Placeholder minh họa cho course card khi chưa có thumbnail_url thật
 * (Phần 10 - KHÔNG dùng ảnh stock generic, chỉ 1 illustration SVG
 * thương hiệu dùng chung, tránh vỡ ảnh/request ảnh ngoài). Nền đổi màu
 * theo course_type để tạo visual variety giống ảnh benchmark thay vì
 * đồng loạt 1 màu xanh.
 */
function cvc_render_course_thumbnail_placeholder( string $color = 'blue' ): void {
	?>
	<div class="cvc-card__media cvc-card__media--placeholder cvc-card__media--<?php echo esc_attr( $color ); ?>" aria-hidden="true">
		<svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
			<path d="M3 6.5 12 3l9 3.5-9 3.5-9-3.5Z"/>
			<path d="M7 9v5c0 1.1 2.24 2 5 2s5-.9 5-2V9"/>
			<path d="M21 6.5v6"/>
		</svg>
	</div>
	<?php
}

/**
 * "Mới" - derived THẬT từ published_at (không phải flag do admin tự đặt,
 * không phải suy đoán) - course được coi là mới nếu publish trong 30 ngày
 * gần nhất. Khác "is_featured" (cờ do admin đặt tay) - đây tự tính từ
 * timestamp thật, luôn chính xác, không cần đồng bộ thủ công.
 */
function cvc_course_is_new( ?string $publishedAt ): bool {
	if ( empty( $publishedAt ) ) {
		return false;
	}

	$timestamp = strtotime( $publishedAt );

	return false !== $timestamp && $timestamp >= ( time() - 30 * DAY_IN_SECONDS );
}

function cvc_render_course_card( array $course, int $heading_level = 2 ): void {
	$slug        = (string) ( $course['slug'] ?? '' );
	$title       = (string) ( $course['title'] ?? '' );
	$summary     = $course['short_description'] ?? '';
	$thumbnail   = $course['thumbnail_url'] ?? null;
	$typeLabel   = cvc_course_type_label( $course['course_type'] ?? null );
	$typeColor   = cvc_course_type_color( $course['course_type'] ?? null );
	$lessonCount = $course['published_lessons_count'] ?? $course['lesson_count'] ?? null;
	$duration    = $course['duration_minutes'] ?? null;
	$price       = $course['price'] ?? null;
	$isFeatured  = ! empty( $course['is_featured'] );
	$isNew       = cvc_course_is_new( $course['published_at'] ?? null );
	$url         = cvc_course_url( $slug );
	$tag         = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card cvc-card--course">
		<a class="cvc-card__media-link" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $thumbnail ) : ?>
				<div class="cvc-card__media">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="" loading="lazy">
				</div>
			<?php else : ?>
				<?php cvc_render_course_thumbnail_placeholder( $typeColor ); ?>
			<?php endif; ?>
			<span class="cvc-card__media-flags">
				<?php if ( $isNew ) : ?>
					<span class="cvc-badge cvc-badge--flag-new">Mới</span>
				<?php elseif ( null !== $price && 0.0 === (float) $price ) : ?>
					<?php cvc_render_free_badge( true ); ?>
				<?php endif; ?>
			</span>
			<?php if ( $typeLabel ) : ?>
				<span class="cvc-badge cvc-badge--category cvc-badge--category-<?php echo esc_attr( $typeColor ); ?> cvc-card__media-badge"><?php echo esc_html( $typeLabel ); ?></span>
			<?php endif; ?>
		</a>
		<div class="cvc-card__body">
			<?php if ( $isFeatured ) : ?>
				<div class="cvc-card__badges">
					<span class="cvc-badge cvc-badge--featured">Nổi bật</span>
				</div>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__meta-row">
				<?php if ( null !== $lessonCount ) : ?>
					<span><?php echo esc_html( sprintf( '%d bài học', (int) $lessonCount ) ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $duration ) ) : ?>
					<span><?php echo esc_html( sprintf( '%d phút', (int) $duration ) ); ?></span>
				<?php endif; ?>
			</p>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--primary cvc-btn--block" href="<?php echo esc_url( $url ); ?>">
					<?php echo ( null !== $price && 0.0 === (float) $price ) ? 'Học miễn phí' : 'Xem khóa học'; ?> &rarr;
				</a>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Course "featured" - bố cục ngang lớn, dùng khi chỉ có ĐÚNG 1 khóa học
 * thật (Phase 10A.4, Phần 15/29 "ONE ITEM -> featured composition") -
 * tránh 1 card nhỏ nằm lọt thỏm trong lưới 4 cột để trống 3 ô còn lại.
 * KHÔNG bịa thêm khóa học giả để lấp chỗ trống.
 */
function cvc_render_course_card_featured( array $course ): void {
	$slug        = (string) ( $course['slug'] ?? '' );
	$title       = (string) ( $course['title'] ?? '' );
	$summary     = $course['short_description'] ?? '';
	$description = $course['description'] ?? '';
	$thumbnail   = $course['thumbnail_url'] ?? null;
	$typeLabel   = cvc_course_type_label( $course['course_type'] ?? null );
	$typeColor   = cvc_course_type_color( $course['course_type'] ?? null );
	$lessonCount = $course['published_lessons_count'] ?? $course['lesson_count'] ?? null;
	$duration    = $course['duration_minutes'] ?? null;
	$price       = $course['price'] ?? null;
	$isFree      = null !== $price && 0.0 === (float) $price;
	$url         = cvc_course_url( $slug );
	?>
	<article class="cvc-card cvc-card--course cvc-card--course-featured">
		<a class="cvc-card__media-link" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $thumbnail ) : ?>
				<div class="cvc-card__media">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="" loading="lazy">
				</div>
			<?php else : ?>
				<?php cvc_render_course_thumbnail_placeholder( $typeColor ); ?>
			<?php endif; ?>
			<?php if ( $typeLabel ) : ?>
				<span class="cvc-badge cvc-badge--category cvc-badge--category-<?php echo esc_attr( $typeColor ); ?> cvc-card__media-badge"><?php echo esc_html( $typeLabel ); ?></span>
			<?php endif; ?>
		</a>
		<div class="cvc-card__body">
			<div class="cvc-card__badges">
				<span class="cvc-badge cvc-badge--featured">Nổi bật</span>
				<?php cvc_render_free_badge( $isFree ); ?>
			</div>
			<h3 class="cvc-card__title cvc-card__title--lg">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</h3>
			<?php if ( $summary || $description ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ?: $description ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__meta-row">
				<?php if ( null !== $lessonCount ) : ?>
					<span><?php echo esc_html( sprintf( '%d bài học', (int) $lessonCount ) ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $duration ) ) : ?>
					<span><?php echo esc_html( sprintf( '%d phút', (int) $duration ) ); ?></span>
				<?php endif; ?>
			</p>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( $url ); ?>">
					<?php echo $isFree ? 'Học miễn phí' : 'Xem khóa học'; ?> &rarr;
				</a>
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
 * Nút "Lưu vào đánh dấu" (Phase 10, Phần VII/XII) - chỉ hiện khi đã đăng
 * nhập (khách bấm sẽ luôn 401 nếu cố tình gọi thẳng action, nên ẩn hẳn CTA
 * thay vì hiện rồi báo lỗi). POST /api/bookmarks là firstOrCreate() ở
 * backend (idempotent) - bấm nhiều lần không tạo trùng, nên không cần biết
 * trước trạng thái đã bookmark hay chưa (API list/detail hiện KHÔNG trả
 * is_bookmarked - không tự suy đoán state không có thật). Bỏ/gỡ bookmark
 * luôn thực hiện trong /tai-khoan/dau-trang/.
 *
 * @param string $type Một trong CVC_Bookmark_Service::VALID_TYPES.
 */
function cvc_render_bookmark_button( string $type, int $id ): void {
	if ( ! cvc_is_logged_in() || 0 === $id ) {
		return;
	}

	$redirect_to = (string) add_query_arg( null, null );
	?>
	<form class="cvc-inline-action" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_bookmark_add' ); ?>
		<input type="hidden" name="action" value="cvc_bookmark_add">
		<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
		<input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>">
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
		<button type="submit" class="cvc-btn cvc-btn--secondary cvc-btn--icon">
			<span aria-hidden="true">&#9733;</span> Lưu vào đánh dấu
		</button>
	</form>
	<?php
}

/**
 * Nút "Đặt làm mục tiêu" (Phase 10, Phần IX/X) - tạo nhanh 1 Goal gắn với
 * ĐÚNG entity đang xem (id lấy từ dữ liệu thật của trang, không bắt gõ
 * tay). Chỉ hiện khi đã đăng nhập. `$entity_fields` chỉ được chứa key nằm
 * trong whitelist của cvc_handle_goal_save() (province_id/agency_id/
 * position_id/exam_id/recruitment_id).
 *
 * @param array<string, int> $entity_fields
 */
function cvc_render_goal_quick_action( string $title, array $entity_fields ): void {
	if ( ! cvc_is_logged_in() ) {
		return;
	}
	?>
	<form class="cvc-inline-action" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_goal_save' ); ?>
		<input type="hidden" name="action" value="cvc_goal_save">
		<input type="hidden" name="title" value="<?php echo esc_attr( $title ); ?>">
		<?php foreach ( $entity_fields as $field => $value ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $field ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
		<?php endforeach; ?>
		<button type="submit" class="cvc-btn cvc-btn--secondary cvc-btn--icon">
			<span aria-hidden="true">&#127919;</span> Đặt làm mục tiêu
		</button>
	</form>
	<?php
}

/**
 * CTA bắt đầu làm bài thi (Phase 10, Phần XIII) - authenticated thì POST
 * thẳng tới cvc_exam_start (mode mặc định "mock" - đủ cho luồng chính,
 * không thêm bộ chọn hình thức thi vì backend/API không yêu cầu UI phải
 * chọn), chưa đăng nhập thì đưa sang đăng nhập kèm intended destination
 * (Phần VIII - quay lại đúng trang thi sau khi đăng nhập).
 */
function cvc_render_exam_start_cta( int $examId ): void {
	if ( 0 === $examId ) {
		return;
	}

	if ( ! cvc_is_logged_in() ) {
		$current = home_url( add_query_arg( null, null ) );
		?>
		<a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( cvc_login_url( $current ) ); ?>">Đăng nhập để làm bài</a>
		<?php
		return;
	}
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_exam_start' ); ?>
		<input type="hidden" name="action" value="cvc_exam_start">
		<input type="hidden" name="exam_id" value="<?php echo esc_attr( (string) $examId ); ?>">
		<input type="hidden" name="mode" value="mock">
		<button type="submit" class="cvc-btn cvc-btn--primary">Bắt đầu làm bài</button>
	</form>
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

/**
 * ============================================================
 * PHASE 10A.2 - Premium homepage components (Visual Benchmark).
 * Không có ảnh chụp thật (course/recruitment chưa có thumbnail thật,
 * KHÔNG dùng ảnh stock generic) - toàn bộ minh họa dưới đây là SVG
 * inline vẽ tay theo brand color, KHÔNG phải ảnh chụp giả lập nội dung
 * thật (Phần 10 - "có thể dùng abstract branded illustration").
 * ============================================================
 */

/**
 * Minh họa hero - motif cơ quan hành chính + học tập (tòa nhà + mũ tốt
 * nghiệp + tài liệu), vẽ bằng SVG thuần theo đúng brand blue, không phụ
 * thuộc ảnh ngoài nên không bao giờ vỡ ảnh/chậm tải.
 */
function cvc_render_hero_illustration(): void {
	?>
	<svg class="cvc-hero__illustration" viewBox="0 0 480 420" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Minh họa cơ quan nhà nước và học tập">
		<circle cx="240" cy="210" r="200" fill="url(#cvcHeroGlow)"/>
		<rect x="90" y="190" width="300" height="150" rx="10" fill="#ffffff" stroke="#dbe9ff" stroke-width="2"/>
		<path d="M90 190 240 100 390 190Z" fill="#0a58ca"/>
		<rect x="112" y="215" width="26" height="105" fill="#eef4ff"/>
		<rect x="156" y="215" width="26" height="105" fill="#eef4ff"/>
		<rect x="228" y="215" width="26" height="105" fill="#eef4ff"/>
		<rect x="300" y="215" width="26" height="105" fill="#eef4ff"/>
		<rect x="344" y="215" width="26" height="105" fill="#eef4ff"/>
		<rect x="200" y="270" width="80" height="50" fill="#0a58ca"/>
		<rect x="70" y="335" width="340" height="14" rx="7" fill="#dbe9ff"/>
		<g transform="translate(300 60)">
			<circle cx="40" cy="40" r="40" fill="#16a34a"/>
			<path d="M20 40h40M40 20v40" stroke="#fff" stroke-width="5" stroke-linecap="round"/>
		</g>
		<g transform="translate(28 250)">
			<rect width="86" height="64" rx="8" fill="#062a5c"/>
			<path d="M10 20h66M10 34h66M10 48h40" stroke="#eef4ff" stroke-width="4" stroke-linecap="round"/>
		</g>
		<g transform="translate(150 20)">
			<path d="M40 0 78 16 40 32 2 16Z" fill="#d97706"/>
			<path d="M14 22v14c0 6 12 10 26 10s26-4 26-10V22" stroke="#d97706" stroke-width="4" fill="none" stroke-linecap="round"/>
		</g>
		<defs>
			<radialGradient id="cvcHeroGlow" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(240 210) rotate(90) scale(200)">
				<stop stop-color="#dbe9ff"/>
				<stop offset="1" stop-color="#dbe9ff" stop-opacity="0"/>
			</radialGradient>
		</defs>
	</svg>
	<?php
}

/**
 * "Tìm kiếm nhanh" dưới search box hero (Phase 10A.4, Phần 12 POPULAR
 * SEARCH TAGS). KHÔNG có analytics "tìm kiếm phổ biến" thật ở backend -
 * cố tình dùng nhãn trung tính "Tìm kiếm nhanh" thay vì "Phổ biến" để
 * không ngụ ý đây là số liệu thống kê (Phần 31 - "không fake popularity").
 * Mỗi tag là 1 query thật trỏ tới /tim-kiem/ (search API thật), không
 * phải taxonomy - đây là shortcut soạn sẵn, không phải dữ liệu.
 */
function cvc_render_hero_quick_search_tags(): void {
	$tags = array(
		'Nghiệp vụ hành chính',
		'Luật cán bộ công chức',
		'Thi thăng hạng',
		'Tuyển dụng 2026',
	);
	?>
	<p class="cvc-hero__quick-search">
		<span class="cvc-hero__quick-search-label">Tìm kiếm nhanh:</span>
		<?php foreach ( $tags as $tag ) : ?>
			<a class="cvc-tag" href="<?php echo esc_url( cvc_search_url( $tag ) ); ?>"><?php echo esc_html( $tag ); ?></a>
		<?php endforeach; ?>
	</p>
	<?php
}

/**
 * Card nổi (floating) trong hero - 4 lợi ích ngắn gọn (Phase 10A.4, đối
 * chiếu ảnh benchmark thật §B). Đây là brand copy mô tả tính năng, không
 * phải số liệu nên không cần API (giống cvc_render_value_strip()).
 */
function cvc_render_hero_feature_card(): void {
	$items = array(
		array(
			'icon'  => '&#9200;',
			'color' => 'green',
			'title' => 'Học tập linh hoạt',
			'desc'  => 'Mọi lúc, mọi nơi',
		),
		array(
			'icon'  => '&#128506;',
			'color' => 'blue',
			'title' => 'Lộ trình cá nhân hóa',
			'desc'  => 'Theo mục tiêu của bạn',
		),
		array(
			'icon'  => '&#128188;',
			'color' => 'amber',
			'title' => 'Cơ hội nghề nghiệp',
			'desc'  => 'Được cập nhật liên tục',
		),
		array(
			'icon'  => '&#129309;',
			'color' => 'purple',
			'title' => 'Cộng đồng hỗ trợ',
			'desc'  => 'Hơn cả một nền tảng học tập',
		),
	);
	?>
	<div class="cvc-hero-feature-card">
		<?php foreach ( $items as $item ) : ?>
			<div class="cvc-hero-feature-card__row">
				<span class="cvc-hero-feature-card__icon cvc-hero-feature-card__icon--<?php echo esc_attr( $item['color'] ); ?>" aria-hidden="true"><?php echo $item['icon']; // phpcs:ignore -- HTML entity tĩnh. ?></span>
				<span>
					<strong><?php echo esc_html( $item['title'] ); ?></strong>
					<small><?php echo esc_html( $item['desc'] ); ?></small>
				</span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Value strip - feature ngang dưới hero (Phần 8 VALUE STRIP). Chỉ liệt kê
 * năng lực SẢN PHẨM THẬT ĐANG CÓ (khóa học/văn bản/tuyển dụng/thi trắc
 * nghiệm/lộ trình) - đây là mô tả tính năng, KHÔNG phải số liệu/thống kê
 * nên không vi phạm "không fake data" (Phần 31) dù không tra API.
 */
function cvc_render_value_strip(): void {
	/*
	 * Mỗi item 1 màu icon riêng (Phần 10B - đối chiếu ảnh benchmark thật:
	 * 5 icon tròn màu khác nhau, không đồng loạt xanh) - màu chỉ mang tính
	 * trang trí/phân biệt, KHÔNG gắn với ý nghĩa dữ liệu nào.
	 */
	$items = array(
		array(
			'icon'  => '&#127891;',
			'color' => 'blue',
			'title' => 'Khóa học đa dạng',
			'desc'  => 'Từ kiến thức chuyên môn đến kỹ năng mềm',
		),
		array(
			'icon'  => '&#128220;',
			'color' => 'green',
			'title' => 'Tài liệu phong phú',
			'desc'  => 'Văn bản pháp luật, tài liệu ôn thi',
		),
		array(
			'icon'  => '&#128188;',
			'color' => 'amber',
			'title' => 'Tuyển dụng cập nhật',
			'desc'  => 'Việc làm công chức, viên chức từ nhiều cơ quan',
		),
		array(
			'icon'  => '&#9989;',
			'color' => 'purple',
			'title' => 'Ôn thi hệ thống',
			'desc'  => 'Đề thi trắc nghiệm theo môn thi, chủ đề',
		),
		array(
			'icon'  => '&#128200;',
			'color' => 'pink',
			'title' => 'Phát triển sự nghiệp',
			'desc'  => 'Nâng cao năng lực và cơ hội thăng tiến',
		),
	);
	?>
	<ul class="cvc-value-strip">
		<?php foreach ( $items as $item ) : ?>
			<li class="cvc-value-strip__item">
				<span class="cvc-value-strip__icon cvc-value-strip__icon--<?php echo esc_attr( $item['color'] ); ?>" aria-hidden="true"><?php echo $item['icon']; // phpcs:ignore -- HTML entity tĩnh. ?></span>
				<span class="cvc-value-strip__title"><?php echo esc_html( $item['title'] ); ?></span>
				<span class="cvc-value-strip__desc"><?php echo esc_html( $item['desc'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Empty state cao cấp cho các section chủ lực trên homepage (Phần 25 -
 * "empty state KHÔNG PHẢI warning"). Khác cvc_render_empty_state() (dùng
 * cho mọi nơi khác, style trung tính đơn giản) - bản này có icon lớn +
 * CTA rõ, dành cho những chỗ empty ảnh hưởng trực tiếp tới ấn tượng đầu
 * (hiện tại: Tuyển dụng - 0 bản ghi published trên DEV).
 */
function cvc_render_premium_empty_state( string $icon, string $heading, string $description, string $cta_label = '', string $cta_url = '' ): void {
	?>
	<div class="cvc-empty-panel">
		<span class="cvc-empty-panel__icon" aria-hidden="true"><?php echo $icon; // phpcs:ignore -- HTML entity tĩnh. ?></span>
		<h3 class="cvc-empty-panel__heading"><?php echo esc_html( $heading ); ?></h3>
		<p class="cvc-empty-panel__desc"><?php echo esc_html( $description ); ?></p>
		<?php if ( '' !== $cta_label && '' !== $cta_url ) : ?>
			<a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Card "hub" cho 1 domain nội dung (Kiến thức/Văn bản/Chủ đề) ở section
 * Resource trên homepage - gộp 3 domain thành 1 hàng thay vì 3 section
 * lặp lại gần giống nhau (Phần 8 RESOURCE SECTION). Chỉ hiển thị số lượng
 * thật nếu > 0 (Phần 31 - không fake, và số 0 không có giá trị thông tin).
 */
/**
 * Section "Bạn đang hướng đến điều gì?" (Phase 10A.4, Phần 18 CAREER/GOAL
 * SECTION) - 5 hướng mục tiêu + 1 CTA tạo mục tiêu thật. KHÔNG có taxonomy
 * "goal type" riêng ở backend khớp chính xác 5 nhãn này, nên mỗi thẻ trỏ
 * tới ĐÚNG trang/filter THẬT gần nghĩa nhất đã tồn tại (recruitment_type
 * là enum thật của RecruitmentController::index() - xem
 * template-recruitments.php) thay vì tạo taxonomy giả hay dead link.
 * CTA "Tạo mục tiêu ngay" trỏ thẳng flow Goal thật (/tai-khoan/muc-tieu/,
 * tự redirect sang đăng nhập kèm intended destination nếu chưa đăng nhập -
 * xem cvc_require_login()).
 */
function cvc_render_goal_direction_section(): void {
	$directions = array(
		array(
			'icon'  => '&#127963;',
			'label' => 'Thi công chức',
			'url'   => cvc_recruitments_url() . '?recruitment_type=civil_servant',
		),
		array(
			'icon'  => '&#128101;',
			'label' => 'Thi viên chức',
			'url'   => cvc_recruitments_url() . '?recruitment_type=public_employee',
		),
		array(
			'icon'  => '&#128200;',
			'label' => 'Thi thăng hạng',
			'url'   => cvc_exams_url(),
		),
		array(
			'icon'  => '&#128196;',
			'label' => 'Bồi dưỡng nghiệp vụ',
			'url'   => cvc_courses_url(),
		),
		array(
			'icon'  => '&#128161;',
			'label' => 'Nâng cao kỹ năng',
			'url'   => cvc_knowledge_url(),
		),
	);
	?>
	<section class="cvc-section cvc-section--tint cvc-goal-section">
		<div class="container">
			<?php cvc_render_section_header( 'Bạn đang hướng đến điều gì?', 'Khám phá tất cả', cvc_search_url(), '&#127919;' ); ?>
			<p class="cvc-goal-section__subtitle">Chọn hướng đi để tìm nội dung ôn tập và tin tuyển dụng phù hợp nhất.</p>

			<div class="cvc-goal-section__grid">
				<div class="cvc-goal-direction-grid">
					<?php foreach ( $directions as $direction ) : ?>
						<a class="cvc-goal-direction-card" href="<?php echo esc_url( $direction['url'] ); ?>">
							<span class="cvc-goal-direction-card__icon" aria-hidden="true"><?php echo $direction['icon']; // phpcs:ignore -- HTML entity tĩnh. ?></span>
							<span class="cvc-goal-direction-card__label"><?php echo esc_html( $direction['label'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>

				<div class="cvc-goal-cta-card">
					<h3>Chưa biết bắt đầu từ đâu?</h3>
					<p>Hãy để chúng tôi gợi ý lộ trình phù hợp với mục tiêu của bạn.</p>
					<a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( cvc_is_logged_in() ? cvc_account_url( 'goals' ) : cvc_login_url( cvc_account_url( 'goals' ) ) ); ?>">Tạo mục tiêu ngay</a>
				</div>
			</div>
		</div>
	</section>
	<?php
}

function cvc_render_resource_hub_card( string $icon, string $title, string $description, string $url, ?int $count = null, string $count_label = '', string $color = 'blue' ): void {
	?>
	<a class="cvc-resource-card" href="<?php echo esc_url( $url ); ?>">
		<span class="cvc-resource-card__icon cvc-resource-card__icon--<?php echo esc_attr( $color ); ?>" aria-hidden="true"><?php echo $icon; // phpcs:ignore -- HTML entity tĩnh. ?></span>
		<span class="cvc-resource-card__title"><?php echo esc_html( $title ); ?></span>
		<span class="cvc-resource-card__desc"><?php echo esc_html( $description ); ?></span>
		<?php if ( null !== $count && $count > 0 ) : ?>
			<span class="cvc-resource-card__count"><?php echo esc_html( sprintf( '%d %s', $count, $count_label ) ); ?></span>
		<?php endif; ?>
		<span class="cvc-resource-card__link">Khám phá &rarr;</span>
	</a>
	<?php
}

/**
 * Banner CTA cuối trang chủ (Phần 8 FINAL CTA) - dải xanh đậm full-width,
 * skyline minh họa bằng SVG (không phải ảnh ngoài).
 */
function cvc_render_homepage_cta_banner(): void {
	?>
	<section class="cvc-cta-banner">
		<svg class="cvc-cta-banner__skyline" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true">
			<path d="M0 120V70l40-10V50l30-15 30 15v20l50-25v30l40-8v18l60-20v20l45-10v20l55-15v25l60-10v15l50-20v25l60-8v13H0Z" fill="#ffffff" fill-opacity="0.06"/>
		</svg>
		<div class="container cvc-cta-banner__inner">
			<div class="cvc-cta-banner__main">
				<p class="cvc-cta-banner__eyebrow">Công Viên Chức &ndash; Đồng hành cùng bạn</p>
				<h2>Hành trang vững vàng<br>Kiến tạo <span>tương lai</span></h2>
				<p class="cvc-cta-banner__desc">Tri thức hôm nay là cơ hội ngày mai. Hãy bắt đầu hành trình phát triển sự nghiệp phục vụ công vụ cùng Công Viên Chức.</p>
				<div class="cvc-cta-banner__actions">
					<a class="cvc-btn cvc-btn--cta" href="<?php echo esc_url( cvc_is_logged_in() ? cvc_account_url( 'goals' ) : cvc_register_url() ); ?>">Bắt đầu ngay &rarr;</a>
					<a class="cvc-btn cvc-btn--cta-outline" href="<?php echo esc_url( cvc_courses_url() ); ?>">Xem các khóa học</a>
				</div>
			</div>
			<p class="cvc-cta-banner__quote">&ldquo;Vì một nền công vụ chuyên nghiệp,<br>hiện đại và phục vụ nhân dân tốt hơn.&rdquo;</p>
		</div>
	</section>
	<?php
}
