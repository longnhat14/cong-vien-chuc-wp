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

/**
 * Phase 10A.17: icon giờ render thật (CVC icon library, feedback/empty)
 * thay vì emoji nhúng qua CSS content:'\1F4C2' (📂) trước đây - xem
 * style.css, rule ::before cũ đã bị xoá cùng lúc.
 */
function cvc_render_empty_state( string $message = 'Chưa có dữ liệu.' ): void {
	printf(
		'<div class="cvc-state cvc-state--empty"><span class="cvc-state__icon" aria-hidden="true">%s</span>%s</div>',
		cvc_get_cvc_icon_html( 'feedback/empty', 28 ),
		esc_html( $message )
	);
}

/**
 * Phase 10A.17: thêm icon feedback/error (trước đây không có icon nào,
 * chỉ có màu nền/viền đỏ) - không phải emoji, bổ sung mới hoàn toàn nên
 * không có rủi ro "làm xấu hơn baseline".
 */
function cvc_render_error_state( string $message = 'Không thể tải dữ liệu, vui lòng thử lại sau.' ): void {
	printf(
		'<div class="cvc-state cvc-state--error"><span class="cvc-state__icon" aria-hidden="true">%s</span>%s</div>',
		cvc_get_cvc_icon_html( 'feedback/error', 28 ),
		esc_html( $message )
	);
}

/**
 * Helper nội bộ: cvc_render_cvc_icon() in trực tiếp ra output (echo qua
 * printf) nên không capture được thành string để lồng vào 1 printf khác
 * - hàm này bọc lại bằng output buffering để dùng icon library ngay
 * trong 1 dòng printf ở các hàm state phía trên mà không đổi kiến trúc
 * cvc_render_cvc_icon() (vẫn dùng trực tiếp/echo ở mọi nơi khác trong
 * theme). Trả về '' nếu thiếu asset - không vỡ HTML.
 */
function cvc_get_cvc_icon_html( string $key, int $size = 20, string $class = '' ): string {
	ob_start();
	cvc_render_cvc_icon( $key, $size, $class );
	return ob_get_clean();
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
		<a href="<?php echo esc_url( cvc_account_url( 'notifications' ) ); ?>" class="site-header__bell" aria-label="<?php esc_attr_e( 'Thông báo', 'cong-vien-chuc' ); ?>">
			<?php cvc_render_icon( 'bell', 19 ); ?>
			<?php if ( $unread > 0 ) : ?>
				<span class="cvc-notification-badge"><?php echo esc_html( (string) min( 9, $unread ) ); ?><?php echo $unread > 9 ? '+' : ''; ?></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( cvc_account_url() ); ?>" class="site-header__account-link">
			<?php esc_html_e( 'Tài khoản', 'cong-vien-chuc' ); ?>
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
	cvc_render_icon( $key, 18, 'cvc-nav-icon' );
}

/**
 * Thư viện icon SVG dùng chung toàn theme (Phase 10A.5, Phần 27
 * ICONOGRAPHY - "Không dùng emoji làm icon chính. Ưu tiên SVG"). Trước
 * đó nhiều nơi (value strip, resource hub, goal section, hero feature
 * card...) dùng HTML entity emoji - xác nhận bằng screenshot headless
 * Chrome thật: môi trường không có font color-emoji nên toàn bộ hiện ra
 * ô trống (tofu box), không phải lỗi hiếm gặp mà là rủi ro thật trên bất
 * kỳ máy nào thiếu font emoji màu. Một bộ icon stroke-based dùng
 * currentColor - luôn hiển thị nhất quán, tự đổi màu theo nơi đặt.
 */
function cvc_render_icon( string $key, int $size = 20, string $class = '' ): void {
	$paths = array(
		'home'         => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
		'courses'      => '<path d="M3 6.5 12 3l9 3.5-9 3.5-9-3.5Z"/><path d="M7 9v5c0 1.1 2.24 2 5 2s5-.9 5-2V9"/><path d="M21 6.5v6"/>',
		'topics'       => '<path d="M4 4.5h11a2 2 0 0 1 2 2V20H6a2 2 0 0 1-2-2V4.5Z"/><path d="M8 9h6M8 12.5h6"/>',
		'recruitments' => '<rect x="3.5" y="7.5" width="17" height="12" rx="2"/><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5"/><path d="M3.5 12.5h17"/>',
		'knowledge'    => '<path d="M12 4.5c-2-1.2-5-1.2-7 0v13c2-1.2 5-1.2 7 0m0-13c2-1.2 5-1.2 7 0v13c-2-1.2-5-1.2-7 0m0-13v13"/>',
		'exams'        => '<path d="M6 3.5h9l3 3V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M9 12l2 2 4-4.5"/>',
		'legal'        => '<path d="M12 3.5v17M6 6.5 3 12l3 5.5c1.8 1 4.2 1 6 0M18 6.5 15 12l3 5.5c1.8 1 4.2 1 6 0"/><path d="M4.5 6.5h15"/>',
		'document'     => '<path d="M7 3.5h7l4 4V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M14 3.5V8h4"/><path d="M9 13h6M9 16.5h6"/>',
		'briefcase'    => '<rect x="3.5" y="7.5" width="17" height="12" rx="2"/><path d="M8.5 7.5V6a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v1.5"/><path d="M3.5 13.5h17"/><path d="M10.5 13.5h3v1.5h-3z"/>',
		'trending'     => '<path d="M3.5 17 9 11.5l4 4 7.5-8.5"/><path d="M15.5 7h5v5"/>',
		'users'        => '<circle cx="9" cy="8.5" r="3"/><path d="M3 19.5c0-3 2.7-5.5 6-5.5s6 2.5 6 5.5"/><path d="M16 8.5a2.7 2.7 0 1 0 0-5.4"/><path d="M18.5 14.3c2 .6 3.5 2.6 3.5 5.2"/>',
		'book'         => '<path d="M4 4.5h11a2 2 0 0 1 2 2V20H6a2 2 0 0 1-2-2V4.5Z"/><path d="M8 9h6M8 12.5h6M8 16h4"/>',
		'lightbulb'    => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.5.4.8 1 .8 1.6h5.4c0-.6.3-1.2.8-1.6A6 6 0 0 0 12 3Z"/>',
		'building'     => '<path d="M4 21V9l8-5 8 5v12"/><path d="M4 21h16"/><path d="M9 21v-6h6v6"/><path d="M9 12h.01M15 12h.01M12 9h.01"/>',
		'target'       => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="0.8" fill="currentColor" stroke="none"/>',
		'clock'        => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/>',
		'route'        => '<circle cx="6" cy="6" r="2.2"/><circle cx="18" cy="18" r="2.2"/><path d="M6 8.2v3.3a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v0.3"/>',
		'star'         => '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.2 1 5.9L12 17l-5.2 2.8 1-5.9-4.3-4.2 5.9-.8Z"/>',
		'pin'          => '<path d="M12 21s7-6.1 7-11.5a7 7 0 1 0-14 0C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/>',
		'calendar'     => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 9.5h17M8 3v3.5M16 3v3.5"/>',
		'check'        => '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12.3 2.4 2.4 4.6-5.4"/>',
		'search'       => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.6-4.6"/>',
		'bell'         => '<path d="M6 9a6 6 0 0 1 12 0v4.5l1.6 2.8H4.4L6 13.5Z"/><path d="M9.5 19.5a2.5 2.5 0 0 0 5 0"/>',
	);

	if ( ! isset( $paths[ $key ] ) ) {
		return;
	}

	printf(
		'<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
		esc_attr( $class ),
		$size,
		$size,
		$paths[ $key ] // phpcs:ignore -- path SVG tĩnh, hardcode trong theme, không phải input người dùng.
	);
}

/**
 * Render 1 asset PNG/WebP đã cắt sẵn trong assets/images/homepage-v2/
 * (Phase 10A.16 - bộ "Homepage V2 cut assets"). Dùng chung cho value/goal
 * icon thay vì lặp file_exists+get_theme_file_uri ở nhiều nơi. Trả về
 * true/false để nơi gọi biết còn cần render SVG fallback hay không (real
 * asset > SVG tự vẽ, nhưng SVG luôn là fallback cuối nếu thiếu file - xem
 * cvc_render_icon()). width/height bắt buộc để tránh CLS; alt mặc định
 * rỗng vì các icon này luôn đặt cạnh tiêu đề/mô tả chữ thật (không phải
 * nguồn thông tin duy nhất).
 */
function cvc_render_v2_asset_icon( string $relative_path, int $w, int $h, string $class = '', string $alt = '' ): bool {
	return cvc_render_raster_icon_from( 'assets/images/homepage-v2', $relative_path, $w, $h, $class, $alt );
}

/**
 * Phase 10A.18: "CVC Icon Library V2" (85 tile PNG, 144x128, cắt sẵn từ
 * poster - khác bộ SVG core V1 đã tích hợp ở 10A.17). README của bộ này
 * nói rõ: dùng cho card/feature-block/empty-state/resource-block, KHÔNG
 * thay thế SVG core khi cần icon inline nhỏ/màu theo CSS - nên hàm riêng
 * này tách khỏi cvc_render_cvc_icon() (SVG), không dùng chung 1 hàm dù
 * cùng mục đích "render icon theo key".
 */
function cvc_render_icon_library_v2( string $key, int $w, int $h, string $class = '', string $alt = '' ): bool {
	return cvc_render_raster_icon_from( 'assets/images/cvc-icon-library-v2', $key, $w, $h, $class, $alt );
}

/**
 * Renderer dùng chung cho mọi bộ icon dạng ảnh raster (PNG + webp sibling,
 * <picture> + fallback) - cvc_render_v2_asset_icon() và
 * cvc_render_icon_library_v2() đều gọi qua đây (Phần 4/6 - "mở rộng
 * helper hiện có thay vì tạo hệ thống trùng lặp").
 */
function cvc_render_raster_icon_from( string $base_dir, string $relative_path, int $w, int $h, string $class = '', string $alt = '' ): bool {
	$path = "{$base_dir}/{$relative_path}";
	if ( ! file_exists( get_theme_file_path( "/{$path}.png" ) ) ) {
		return false;
	}
	?>
	<picture>
		<source srcset="<?php echo esc_url( get_theme_file_uri( "/{$path}.webp" ) ); ?>" type="image/webp">
		<img
			class="<?php echo esc_attr( $class ); ?>"
			src="<?php echo esc_url( get_theme_file_uri( "/{$path}.png" ) ); ?>"
			alt="<?php echo esc_attr( $alt ); ?>"
			width="<?php echo esc_attr( (string) $w ); ?>"
			height="<?php echo esc_attr( (string) $h ); ?>"
			loading="lazy"
			decoding="async"
		>
	</picture>
	<?php
	return true;
}

/**
 * Render 1 icon từ "CVC Design Icon Library Core V1" (Phase 10A.17 - 59
 * file SVG duotone 64x64, đã cắt sẵn, không xử lý runtime - xem
 * design-assets/cvc-icon-library/cvc-design-icon-library/README.txt).
 * $key dạng "category/name" (vd "recruitment/agency", "exams/timer") -
 * khớp đúng cấu trúc thư mục assets/icons/cvc/. Dùng <img> trỏ thẳng file
 * .svg (không base64, không background-image - SVG đã nhẹ sẵn, không cần
 * picture/webp như ảnh raster). Trả về true/false để nơi gọi biết còn
 * cần fallback (icon nội bộ cvc_render_icon()) hay không - nếu thiếu
 * icon cho 1 trường hợp cụ thể thì KHÔNG tự vẽ thêm, giữ fallback hiện
 * có. alt mặc định rỗng (icon trang trí, luôn đặt cạnh text thật); truyền
 * $alt khi icon là accessible name DUY NHẤT của 1 control (vd nút icon-only).
 */
function cvc_render_cvc_icon( string $key, int $size = 20, string $class = '', string $alt = '' ): bool {
	$path = "assets/icons/cvc/{$key}.svg";
	if ( ! file_exists( get_theme_file_path( "/{$path}" ) ) ) {
		return false;
	}
	printf(
		'<img class="%s" src="%s" width="%d" height="%d" alt="%s" loading="lazy" decoding="async">',
		esc_attr( trim( 'cvc-lib-icon ' . $class ) ),
		esc_url( get_theme_file_uri( "/{$path}" ) ),
		$size,
		$size,
		esc_attr( $alt )
	);
	return true;
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
				<span class="cvc-section__header-icon"><?php cvc_render_icon( $icon, 22 ); ?></span>
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
				<p class="cvc-card__meta"><?php cvc_render_cvc_icon( 'recruitment/position', 15 ); ?> <?php echo esc_html( sprintf( '%d chỉ tiêu', (int) $totalPositions ) ); ?></p>
			<?php endif; ?>
			<?php if ( $code ) : ?>
				<p class="cvc-card__meta">Mã tin: <?php echo esc_html( $code ); ?></p>
			<?php endif; ?>
			<?php if ( $announcedAt ) : ?>
				<p class="cvc-card__meta">Đăng ngày: <?php echo esc_html( cvc_format_date_vn( $announcedAt ) ); ?></p>
			<?php endif; ?>
			<div class="cvc-card__footer cvc-card__footer--split">
				<?php if ( $deadline ) : ?>
					<span class="cvc-card__deadline"><?php cvc_render_cvc_icon( 'recruitment/deadline', 15 ); ?> Hạn nộp: <strong><?php echo esc_html( cvc_format_date_vn( $deadline ) ); ?></strong></span>
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
	$isDemo      = ! empty( $recruitment['_is_demo'] );
	$slug        = (string) ( $recruitment['slug'] ?? '' );
	$title       = (string) ( $recruitment['title'] ?? '' );
	$location    = $recruitment['location'] ?? '';
	$deadline    = $recruitment['dates']['application_deadline'] ?? null;
	$agencyName  = $recruitment['agency']['name'] ?? null;
	/*
	 * Recruitment fixture (Phần 3/17) không có slug thật - trỏ về đúng
	 * trang danh sách tuyển dụng thật thay vì 1 URL chi tiết không tồn tại.
	 */
	$url     = $isDemo ? cvc_recruitments_url() : cvc_recruitment_url( $slug );
	$initial = $agencyName ? mb_substr( $agencyName, 0, 1 ) : 'C';
	/*
	 * Màu avatar xoay vòng theo tên cơ quan (Phần 21 - "visual agency
	 * placeholder", KHÔNG giả làm logo chính thức) - chỉ để tạo nhịp thị
	 * giác giữa các dòng, không mang ý nghĩa phân loại nào.
	 */
	$avatarColor = array( 'blue', 'teal', 'amber', 'purple' )[ $agencyName ? crc32( $agencyName ) % 4 : 0 ];
	?>
	<article class="cvc-recruitment-row<?php echo $isDemo ? ' cvc-recruitment-row--demo' : ''; ?>">
		<span class="cvc-recruitment-row__avatar cvc-recruitment-row__avatar--<?php echo esc_attr( $avatarColor ); ?>" aria-hidden="true"><?php echo esc_html( mb_strtoupper( $initial ) ); ?></span>
		<div class="cvc-recruitment-row__body">
			<div class="cvc-recruitment-row__top">
				<h3 class="cvc-recruitment-row__title">
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
				</h3>
				<span class="cvc-badge cvc-badge--status cvc-badge--status-active cvc-recruitment-row__status">Đang tuyển</span>
				<?php if ( $isDemo ) : ?>
					<?php cvc_render_demo_badge(); ?>
				<?php endif; ?>
			</div>
			<?php if ( $agencyName ) : ?>
				<p class="cvc-recruitment-row__agency"><?php echo esc_html( $agencyName ); ?></p>
			<?php endif; ?>
			<p class="cvc-recruitment-row__meta">
				<?php if ( $location ) : ?>
					<span class="cvc-recruitment-row__meta-item"><?php cvc_render_cvc_icon( 'recruitment/location', 16 ); ?> <?php echo esc_html( $location ); ?></span>
				<?php endif; ?>
				<?php if ( $deadline ) : ?>
					<span class="cvc-recruitment-row__meta-item"><?php cvc_render_cvc_icon( 'recruitment/deadline', 16 ); ?> Hạn nộp: <?php echo esc_html( cvc_format_date_vn( $deadline ) ); ?></span>
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
					<span><?php cvc_render_cvc_icon( 'exams/question', 15 ); ?> <?php echo esc_html( sprintf( '%d câu hỏi', (int) $questionsCount ) ); ?></span>
				<?php endif; ?>
				<?php if ( $durationMinutes ) : ?>
					<span><?php cvc_render_cvc_icon( 'exams/timer', 15 ); ?> <?php echo esc_html( sprintf( '%d phút', (int) $durationMinutes ) ); ?></span>
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
				<p class="cvc-card__meta"><?php cvc_render_cvc_icon( 'knowledge-legal/official', 15 ); ?> <?php echo esc_html( $issuingAgency ); ?></p>
			<?php endif; ?>
			<?php if ( $effectiveDate ) : ?>
				<p class="cvc-card__meta"><?php cvc_render_cvc_icon( 'knowledge-legal/updated', 15 ); ?> <?php echo esc_html( sprintf( 'Hiệu lực: %s', cvc_format_date_vn( $effectiveDate ) ) ); ?></p>
			<?php endif; ?>
			<p class="cvc-card__footer">
				<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( $url ); ?>">Xem văn bản &rarr;</a>
			</p>
		</div>
	</article>
	<?php
}

/**
 * Phase 10A.17: icon giờ render thật (CVC icon library, feedback/not-found)
 * thay vì emoji nhúng qua CSS content:'\1F50D' (🔍) trước đây - xem
 * style.css, rule ::before cũ đã bị xoá cùng lúc.
 */
function cvc_render_notfound_state( string $message ): void {
	printf(
		'<div class="cvc-state cvc-state--notfound"><span class="cvc-state__icon" aria-hidden="true">%s</span>%s</div>',
		cvc_get_cvc_icon_html( 'feedback/not-found', 28 ),
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
 * Artwork minh họa cho course card khi chưa có thumbnail_url thật.
 * Phase 10A.13: ảnh production Homepage V2 (đã cắt sẵn, không qua xử lý
 * runtime nào - khác bản 10A.10 vốn tự crop/blur từ 1 board collage nhỏ).
 * Map theo course_type THẬT (không theo vị trí trong danh sách API trả
 * về) để đúng nội dung ảnh với category hiển thị - ví dụ course_type=
 * professional luôn ra ảnh laptop/Excel, không phụ thuộc course đó đứng
 * thứ mấy trong response. course_type không map được ảnh (giá trị lạ,
 * ngoài enum) rơi về SVG scene (Phase 10A.9) làm fallback cuối.
 */
function cvc_render_course_thumbnail_placeholder( ?string $course_type = null, string $color = 'blue' ): void {
	$photo_map = array(
		'exam_prep'    => array( 'course-exam', 288, 201 ),
		'skill'        => array( 'course-skill', 290, 201 ),
		'professional' => array( 'course-office', 283, 201 ),
		'orientation'  => array( 'course-admin', 289, 201 ),
	);
	list( $photo_file, $photo_w, $photo_h ) = $photo_map[ $course_type ?? '' ] ?? array( 'course-law', 290, 201 );
	$photo_path = "assets/images/homepage-v2/COURSES/{$photo_file}";
	if ( file_exists( get_theme_file_path( "/{$photo_path}.jpg" ) ) ) {
		?>
		<div class="cvc-card__media cvc-card__media--photo" aria-hidden="true">
			<picture>
				<source srcset="<?php echo esc_url( get_theme_file_uri( "/{$photo_path}.webp" ) ); ?>" type="image/webp">
				<img src="<?php echo esc_url( get_theme_file_uri( "/{$photo_path}.jpg" ) ); ?>" alt="" loading="lazy" width="<?php echo esc_attr( $photo_w ); ?>" height="<?php echo esc_attr( $photo_h ); ?>">
			</picture>
		</div>
		<?php
		return;
	}
	cvc_render_course_thumbnail_svg_fallback( $course_type, $color );
}

/**
 * SVG scene fallback (Phase 10A.9) - dùng khi course_type không map được
 * ảnh thật ở trên, hoặc khi thư mục assets/images/homepage bị thiếu file.
 */
function cvc_render_course_thumbnail_svg_fallback( ?string $course_type = null, string $color = 'blue' ): void {
	$scenes = array(
		// Ôn thi - bài thi + đồng hồ bấm giờ + bia điểm, kể câu chuyện
		// "luyện đề có giới hạn thời gian, chấm điểm rõ ràng" thay vì chỉ
		// 1 tờ giấy + dấu tick đơn giản (Phase 10A.9, Phần 18).
		'exam_prep'    => '<circle cx="45" cy="16" r="15" fill="#ffffff" fill-opacity="0.14"/>
			<circle cx="12" cy="48" r="10" fill="#ffffff" fill-opacity="0.1"/>
			<rect x="12" y="12" width="28" height="40" rx="4" fill="#ffffff" fill-opacity="0.92"/>
			<rect x="12" y="12" width="28" height="9" rx="4" fill="currentColor" fill-opacity="0.3"/>
			<path d="M17 27h18M17 33.5h18M17 40h11" stroke="currentColor" stroke-opacity="0.55" stroke-width="2" stroke-linecap="round"/>
			<circle cx="46" cy="14" r="3" fill="#ffffff" fill-opacity="0.9"/>
			<path d="M44 6h4l1 4h-6Z" fill="#ffffff" fill-opacity="0.9"/>
			<circle cx="44" cy="42" r="13" fill="#ffffff"/>
			<circle cx="44" cy="42" r="13" fill="none" stroke="currentColor" stroke-opacity="0.2" stroke-width="1.5"/>
			<path d="M44 35v7l5 3" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
			<circle cx="44" cy="42" r="1.6" fill="#0f172a"/>',
		// Kỹ năng - 2 người đối thoại, mũi tên trao đổi + biểu đồ tăng
		// trưởng nhỏ, kể câu chuyện "trao đổi kỹ năng giúp tiến bộ" thay vì
		// chỉ 1 bong bóng chat đơn giản.
		'skill'        => '<circle cx="16" cy="18" r="12" fill="#ffffff" fill-opacity="0.14"/>
			<circle cx="47" cy="46" r="11" fill="#ffffff" fill-opacity="0.1"/>
			<circle cx="15" cy="16" r="7" fill="#ffffff"/>
			<path d="M4 38c0-7.5 5-13 11-13s11 5.5 11 13" fill="#ffffff" fill-opacity="0.85"/>
			<circle cx="45" cy="22" r="7" fill="#ffffff" fill-opacity="0.95"/>
			<path d="M34 44c0-7.5 5-13 11-13s11 5.5 11 13" fill="#ffffff" fill-opacity="0.7"/>
			<path d="M25 24c3 2 7 2 10 0" stroke="currentColor" stroke-opacity="0.5" stroke-width="2" fill="none" stroke-linecap="round"/>
			<path d="m31 20 4 4-4 4" stroke="currentColor" stroke-opacity="0.5" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M6 52h10l4-6 4 3 5-8" stroke="#ffffff" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>',
		// Chuyên môn/Tin học - laptop với biểu đồ cột + con trỏ + bảng tính
		// nổi phía sau, kể câu chuyện "làm việc với số liệu, nghiệp vụ số"
		// rõ hơn hẳn 1 icon màn hình chung chung.
		'professional' => '<circle cx="15" cy="14" r="12" fill="#ffffff" fill-opacity="0.14"/>
			<circle cx="49" cy="44" r="9" fill="#ffffff" fill-opacity="0.1"/>
			<rect x="30" y="8" width="20" height="16" rx="2" fill="#ffffff" fill-opacity="0.85"/>
			<path d="M33 12h5M33 15.5h9M33 19h6" stroke="currentColor" stroke-opacity="0.4" stroke-width="1.4" stroke-linecap="round"/>
			<rect x="9" y="17" width="34" height="23" rx="2.5" fill="#ffffff"/>
			<rect x="12.5" y="20.5" width="27" height="13" rx="1" fill="currentColor" fill-opacity="0.14"/>
			<path d="M16 30.5 22 24l4.5 4 8-8" stroke="currentColor" stroke-opacity="0.65" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
			<circle cx="34.5" cy="20" r="1.8" fill="currentColor" fill-opacity="0.65"/>
			<path d="M5 43.5h46l-4 6.5H9Z" fill="#ffffff" fill-opacity="0.95"/>
			<rect x="19" y="27" width="4" height="6" fill="currentColor" fill-opacity="0.35"/>
			<rect x="25" y="24" width="4" height="9" fill="currentColor" fill-opacity="0.35"/>
			<rect x="31" y="21" width="4" height="12" fill="currentColor" fill-opacity="0.35"/>',
		// Định hướng nghề nghiệp - la bàn + con đường sự nghiệp có cột mốc
		// và lá cờ đích, kể câu chuyện "có lộ trình rõ ràng để đi tới" thay
		// vì chỉ 1 la bàn tĩnh.
		'orientation'  => '<circle cx="18" cy="46" r="12" fill="#ffffff" fill-opacity="0.12"/>
			<circle cx="46" cy="16" r="10" fill="#ffffff" fill-opacity="0.14"/>
			<path d="M6 52c4-14 14-24 28-28" stroke="#ffffff" stroke-width="3" fill="none" stroke-linecap="round" stroke-dasharray="1 7"/>
			<circle cx="12" cy="47" r="2.6" fill="#ffffff"/>
			<circle cx="21" cy="35" r="2.6" fill="#ffffff" fill-opacity="0.85"/>
			<g transform="translate(30 6)">
				<rect x="-1.4" y="0" width="2.8" height="20" fill="#ffffff"/>
				<path d="M1.4 2h13l-3.4 5 3.4 5h-13Z" fill="currentColor" fill-opacity="0.55"/>
			</g>
			<circle cx="24" cy="34" r="16" fill="#ffffff"/>
			<circle cx="24" cy="34" r="16" fill="none" stroke="currentColor" stroke-opacity="0.2" stroke-width="1.5"/>
			<circle cx="24" cy="34" r="11.5" fill="none" stroke="currentColor" stroke-opacity="0.15" stroke-width="1"/>
			<path d="m30 25-10 6-3 10 10-6Z" fill="currentColor" fill-opacity="0.8"/>
			<circle cx="24" cy="34" r="1.8" fill="#0f172a"/>',
		// Mặc định (kiến thức chung/pháp lý) - sách mở + búa gavel + dải
		// ruy băng, kể câu chuyện "kiến thức nền tảng, quy định, văn bản"
		// thay vì chỉ 1 mũ tốt nghiệp đơn lẻ.
		'default'      => '<circle cx="45" cy="15" r="13" fill="#ffffff" fill-opacity="0.14"/>
			<circle cx="12" cy="47" r="10" fill="#ffffff" fill-opacity="0.1"/>
			<path d="M8 20c5-3 12-3 16 0v26c-4-3-11-3-16 0Z" fill="#ffffff" fill-opacity="0.92"/>
			<path d="M40 20c-5-3-12-3-16 0v26c4-3 11-3 16 0Z" fill="#ffffff" fill-opacity="0.92"/>
			<path d="M24 20v26" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.4"/>
			<path d="M11 25h9M11 30h9M28 25h9M28 30h9" stroke="currentColor" stroke-opacity="0.4" stroke-width="1.4" stroke-linecap="round"/>
			<g transform="translate(37 34) rotate(-40)">
				<rect x="-2.4" y="-14" width="4.8" height="16" rx="2" fill="currentColor" fill-opacity="0.65"/>
				<rect x="-8" y="0" width="16" height="5" rx="2" fill="currentColor" fill-opacity="0.85"/>
				<rect x="-9" y="16" width="18" height="4.5" rx="2" fill="currentColor" fill-opacity="0.85"/>
			</g>
			<path d="M45 45c1.4 4 1.4 7 0 10-1.4-3-1.4-6 0-10Z" fill="#ffffff" fill-opacity="0.9"/>',
	);

	$scene = $scenes[ $course_type ?? '' ] ?? $scenes['default'];
	?>
	<div class="cvc-card__media cvc-card__media--placeholder cvc-card__media--<?php echo esc_attr( $color ); ?>" aria-hidden="true">
		<svg width="100%" height="100%" viewBox="0 0 60 60" preserveAspectRatio="xMidYMid meet" fill="none" style="color:rgba(0,0,0,0.35)">
			<?php echo $scene; // phpcs:ignore -- SVG tĩnh, hardcode trong theme, không phải input người dùng. ?>
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
	$isDemo      = ! empty( $course['_is_demo'] );
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
	/*
	 * Course fixture (Phần 3/17) không có slug thật - KHÔNG được trỏ tới
	 * 1 URL chi tiết không tồn tại (dead link giả). Đưa về đúng trang
	 * danh sách khóa học thật thay vì suy đoán 1 URL.
	 */
	$url = $isDemo ? cvc_courses_url() : cvc_course_url( $slug );
	$tag = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card cvc-card--course<?php echo $isDemo ? ' cvc-card--demo' : ''; ?>">
		<a class="cvc-card__media-link" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $thumbnail ) : ?>
				<div class="cvc-card__media">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="" loading="lazy">
				</div>
			<?php else : ?>
				<?php cvc_render_course_thumbnail_placeholder( $course['course_type'] ?? null, $typeColor ); ?>
			<?php endif; ?>
			<span class="cvc-card__media-flags">
				<?php if ( $isDemo ) : ?>
					<?php cvc_render_demo_badge(); ?>
				<?php elseif ( $isNew ) : ?>
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
					<span><?php cvc_render_cvc_icon( 'courses/lesson', 15 ); ?> <?php echo esc_html( sprintf( '%d bài học', (int) $lessonCount ) ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $duration ) ) : ?>
					<span><?php cvc_render_cvc_icon( 'courses/duration', 15 ); ?> <?php echo esc_html( sprintf( '%d phút', (int) $duration ) ); ?></span>
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
				<?php cvc_render_course_thumbnail_placeholder( $course['course_type'] ?? null, $typeColor ); ?>
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
					<span><?php cvc_render_cvc_icon( 'courses/lesson', 15 ); ?> <?php echo esc_html( sprintf( '%d bài học', (int) $lessonCount ) ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $duration ) ) : ?>
					<span><?php cvc_render_cvc_icon( 'courses/duration', 15 ); ?> <?php echo esc_html( sprintf( '%d phút', (int) $duration ) ); ?></span>
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
			<?php cvc_render_icon( 'star', 16 ); ?> Lưu vào đánh dấu
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
			<?php cvc_render_icon( 'target', 16 ); ?> Đặt làm mục tiêu
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
/**
 * Minh họa hero (Phase 10A.7, Phần 7 "HERO VISUAL - phải có chiều sâu,
 * KHÔNG chấp nhận 1 icon nhỏ") - composition nhiều lớp: nền glow + vòng
 * tròn trang trí (chiều sâu) -> trụ sở cơ quan (nhiều chi tiết kiến trúc
 * hơn bản cũ - mái tam giác, hàng cột, bậc thềm) -> cờ Tổ quốc -> silhouette
 * 1 người chuyên nghiệp (bán thân, cách điệu - KHÔNG dùng ảnh chụp vì
 * không có asset ảnh rõ bản quyền, xem Phần 6) cầm tablet, đại diện
 * "người học/công chức" -> icon nổi (mũ tốt nghiệp, tài liệu) làm điểm
 * nhấn. Toàn bộ vẫn SVG thuần, không phụ thuộc ảnh ngoài.
 */
/**
 * Phase 10A.13 - ảnh hero production thật (1672x941, cắt sẵn từ bộ asset
 * Homepage V2, không qua xử lý crop/blur runtime nào - khác bản 10A.10
 * vốn phải tự crop+blur 1 ảnh collage nhỏ). WebP tự tạo (re-encode cùng
 * kích thước, không crop) để giảm dung lượng tải; SVG cũ giữ lại làm
 * fallback nếu thiếu file ảnh.
 */
function cvc_render_hero_illustration(): void {
	$file = 'assets/images/homepage-v2/HERO/hero-main';
	if ( ! file_exists( get_theme_file_path( "/{$file}.jpg" ) ) ) {
		cvc_render_hero_illustration_svg_fallback();
		return;
	}
	?>
	<picture>
		<source srcset="<?php echo esc_url( get_theme_file_uri( "/{$file}.webp" ) ); ?>" type="image/webp">
		<img
			class="cvc-hero__illustration cvc-hero__illustration--photo"
			src="<?php echo esc_url( get_theme_file_uri( "/{$file}.jpg" ) ); ?>"
			alt="Người công chức, viên chức trước trụ sở cơ quan nhà nước"
			width="1672" height="941"
			fetchpriority="high"
			decoding="async"
		>
	</picture>
	<?php
}

/**
 * Minh họa SVG gốc (Phase 10A.9) - giữ làm fallback khi chưa có asset ảnh
 * thật (xem cvc_render_hero_illustration() ở trên).
 */
function cvc_render_hero_illustration_svg_fallback(): void {
	?>
	<svg class="cvc-hero__illustration" viewBox="0 0 480 420" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Minh họa trụ sở cơ quan nhà nước và người công chức, viên chức đang học tập, phát triển sự nghiệp">
		<rect x="0" y="0" width="480" height="420" rx="24" fill="url(#cvcSkyGradient)"/>
		<circle cx="240" cy="215" r="205" fill="url(#cvcHeroGlow)"/>

		<!-- Mặt trời/glow góc trên phải + mây -->
		<circle cx="418" cy="70" r="46" fill="url(#cvcSunGlow)"/>
		<ellipse cx="120" cy="60" rx="38" ry="14" fill="#ffffff" fill-opacity="0.7"/>
		<ellipse cx="150" cy="52" rx="26" ry="11" fill="#ffffff" fill-opacity="0.7"/>

		<!-- Vòng trang trí tạo chiều sâu -->
		<circle cx="60" cy="335" r="46" fill="#fde68a" fill-opacity="0.28"/>
		<circle cx="415" cy="330" r="20" fill="#c7d2fe" fill-opacity="0.5"/>

		<!-- Nền quảng trường phối cảnh + gạch lát -->
		<path d="M20 378 460 378 420 400 60 400Z" fill="url(#cvcPlazaGradient)"/>
		<path d="M84 384 396 384M108 391 372 391" stroke="#ffffff" stroke-opacity="0.4" stroke-width="2"/>

		<!-- Cây xanh 2 bên tạo bối cảnh môi trường thật (đối chiếu ảnh
		     benchmark có cây quanh trụ sở) -->
		<g transform="translate(410 250)">
			<rect x="7" y="40" width="6" height="46" rx="3" fill="#8a5a34"/>
			<circle cx="10" cy="34" r="22" fill="#4c9a5a"/>
			<circle cx="-6" cy="44" r="16" fill="#3f8a4d"/>
			<circle cx="24" cy="44" r="16" fill="#5aab68"/>
		</g>
		<g transform="translate(18 262)">
			<rect x="7" y="34" width="5" height="40" rx="2.5" fill="#8a5a34"/>
			<circle cx="9" cy="28" r="18" fill="#4c9a5a"/>
			<circle cx="-4" cy="36" r="13" fill="#3f8a4d"/>
			<circle cx="20" cy="36" r="13" fill="#5aab68"/>
		</g>

		<!-- Bóng đổ mặt đất -->
		<ellipse cx="255" cy="378" rx="175" ry="12" fill="#062a5c" fill-opacity="0.1"/>

		<!-- Trụ sở cơ quan - khối 3 mặt (mặt tiền + mặt hông + mái 2 diện)
		     để tạo chiều sâu/perspective thật (Phase 10A.9, Phần 9 "không
		     phải 1 house icon, phải có perspective/depth/light/shadow").
		     Dịch sang phải, nhường bên trái cho người - tránh vùng bị
		     floating card che (position absolute right/bottom). -->
		<g filter="url(#cvcSoftShadow)">
			<!-- mặt hông (khuất sáng hơn, tạo khối 3D) -->
			<path d="M388 200 430 178 430 338 388 358Z" fill="url(#cvcSideGradient)"/>
			<path d="M300 116 342 96 430 178 388 200Z" fill="url(#cvcRoofSideGradient)"/>
			<!-- mặt tiền -->
			<rect x="168" y="200" width="220" height="158" fill="url(#cvcFacadeGradient)" stroke="#dbe9ff" stroke-width="1.5"/>
			<path d="M160 200 300 116 388 200Z" fill="url(#cvcRoofGradient)"/>
			<rect x="283" y="126" width="30" height="18" fill="url(#cvcRoofGradient)"/>
			<circle cx="298" cy="110" r="6.5" fill="#f7c948"/>
			<!-- hàng cột -->
			<g fill="url(#cvcColumnGradient)">
				<rect x="184" y="224" width="14" height="104"/>
				<rect x="214" y="224" width="14" height="104"/>
				<rect x="244" y="224" width="14" height="104"/>
				<rect x="326" y="224" width="14" height="104"/>
				<rect x="356" y="224" width="14" height="104"/>
			</g>
			<g fill="#ffffff" fill-opacity="0.55">
				<rect x="184" y="224" width="4" height="104"/>
				<rect x="214" y="224" width="4" height="104"/>
				<rect x="244" y="224" width="4" height="104"/>
				<rect x="326" y="224" width="4" height="104"/>
				<rect x="356" y="224" width="4" height="104"/>
			</g>
			<rect x="168" y="214" width="220" height="10" fill="#0a58ca" fill-opacity="0.5"/>
			<!-- cửa chính + bậc thềm -->
			<path d="M274 358 274 268 302 268 302 358Z" fill="url(#cvcDoorGradient)"/>
			<rect x="168" y="352" width="220" height="8" fill="#000000" fill-opacity="0.06"/>
			<rect x="152" y="360" width="252" height="10" rx="5" fill="#dbe9ff"/>
			<rect x="136" y="373" width="284" height="8" rx="4" fill="#c7d2fe" fill-opacity="0.6"/>
		</g>

		<!-- Cờ Tổ quốc -->
		<g transform="translate(392 44)">
			<rect x="-1.5" y="0" width="3" height="82" fill="#94a3b8"/>
			<path d="M0 4h46v28H0Z" fill="#da251d"/>
			<path d="m23 9 2.6 8h8.4l-6.8 5 2.6 8-6.8-5-6.8 5 2.6-8-6.8-5h8.4Z" fill="#ffcd00"/>
		</g>

		<!-- Mũ tốt nghiệp nổi, giữa người và tòa nhà -->
		<g transform="translate(192 6)" filter="url(#cvcSoftShadow)">
			<path d="M40 0 78 16 40 32 2 16Z" fill="url(#cvcCapGradient)"/>
			<path d="M14 22v14c0 6 12 10 26 10s26-4 26-10V22" stroke="#d97706" stroke-width="4" fill="none" stroke-linecap="round"/>
			<circle cx="76" cy="17" r="3" fill="#92400e"/>
			<path d="M76 17v20" stroke="#92400e" stroke-width="1.5"/>
		</g>

		<!-- Người công chức/viên chức (bán thân, cách điệu) - đặt hoàn
		     toàn bên trái (x tối đa ~160) để KHÔNG bao giờ bị floating
		     card (bên phải) che, dù ở bất kỳ chiều cao viewport nào.
		     Áo vest 2 tông (thân + ve áo) + thẻ công chức đeo ngực để rõ
		     bối cảnh nghề nghiệp, tóc/da dùng gradient tạo khối thật. -->
		<g transform="translate(18 96)" filter="url(#cvcSoftShadow)">
			<ellipse cx="64" cy="270" rx="78" ry="16" fill="#0a58ca" fill-opacity="0.08"/>
			<path d="M8 266V172c0-38 24-66 56-66s56 28 56 66v94Z" fill="url(#cvcSuitGradient)"/>
			<path d="M64 106c-14 0-26 10-32 24l18 74h28l18-74c-6-14-18-24-32-24Z" fill="url(#cvcSuitLapelGradient)"/>
			<path d="M36 168c7 12 21 19 28 19s21-7 28-19l7 14c-9 16-25 26-35 26s-26-10-35-26Z" fill="#ffffff"/>
			<path d="M56 178 64 190 72 178 64 182Z" fill="#0a58ca"/>
			<rect x="52" y="196" width="18" height="24" rx="2" fill="#f7c948" stroke="#b45309" stroke-width="1"/>
			<circle cx="61" cy="204" r="4" fill="#ffffff" fill-opacity="0.85"/>
			<circle cx="64" cy="88" r="34" fill="url(#cvcSkinGradient)"/>
			<path d="M28 84c0-21 15-37 32-37s32 16 32 37c-2 8-6 13-10 16 1-8-2-15-6-15-8 10-30 12-46 9-3-3-2-9-2-10Z" fill="url(#cvcHairGradient)"/>
			<path d="M45 92c2 3 5 5 8 5M83 92c-2 3-5 5-8 5" stroke="#8a5a34" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
			<rect x="94" y="196" width="50" height="38" rx="4" fill="#eef4ff" stroke="#0a58ca" stroke-width="2"/>
			<path d="M102 205h34M102 215h34M102 224h22" stroke="#0a58ca" stroke-width="2" stroke-linecap="round"/>
		</g>

		<defs>
			<linearGradient id="cvcSkyGradient" x1="240" y1="0" x2="240" y2="420" gradientUnits="userSpaceOnUse">
				<stop stop-color="#eaf2ff"/>
				<stop offset="1" stop-color="#ffffff"/>
			</linearGradient>
			<radialGradient id="cvcSunGlow" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(418 70) rotate(90) scale(46)">
				<stop stop-color="#fef3c7" stop-opacity="0.9"/>
				<stop offset="1" stop-color="#fef3c7" stop-opacity="0"/>
			</radialGradient>
			<linearGradient id="cvcPlazaGradient" x1="240" y1="378" x2="240" y2="400" gradientUnits="userSpaceOnUse">
				<stop stop-color="#dbe9ff"/>
				<stop offset="1" stop-color="#c7d9f7"/>
			</linearGradient>
			<radialGradient id="cvcHeroGlow" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(240 215) rotate(90) scale(205)">
				<stop stop-color="#dbe9ff" stop-opacity="0.7"/>
				<stop offset="1" stop-color="#dbe9ff" stop-opacity="0"/>
			</radialGradient>
			<linearGradient id="cvcRoofGradient" x1="160" y1="116" x2="388" y2="200" gradientUnits="userSpaceOnUse">
				<stop stop-color="#2a7bf0"/>
				<stop offset="1" stop-color="#0a58ca"/>
			</linearGradient>
			<linearGradient id="cvcRoofSideGradient" x1="300" y1="96" x2="430" y2="200" gradientUnits="userSpaceOnUse">
				<stop stop-color="#073f96"/>
				<stop offset="1" stop-color="#042a68"/>
			</linearGradient>
			<linearGradient id="cvcFacadeGradient" x1="168" y1="200" x2="388" y2="358" gradientUnits="userSpaceOnUse">
				<stop stop-color="#ffffff"/>
				<stop offset="1" stop-color="#eef4ff"/>
			</linearGradient>
			<linearGradient id="cvcSideGradient" x1="388" y1="178" x2="430" y2="358" gradientUnits="userSpaceOnUse">
				<stop stop-color="#c9dcf8"/>
				<stop offset="1" stop-color="#a9c3ec"/>
			</linearGradient>
			<linearGradient id="cvcColumnGradient" x1="184" y1="224" x2="198" y2="328" gradientUnits="userSpaceOnUse">
				<stop stop-color="#f5f9ff"/>
				<stop offset="1" stop-color="#cfe0fa"/>
			</linearGradient>
			<linearGradient id="cvcDoorGradient" x1="274" y1="268" x2="302" y2="358" gradientUnits="userSpaceOnUse">
				<stop stop-color="#1669e0"/>
				<stop offset="1" stop-color="#073f96"/>
			</linearGradient>
			<linearGradient id="cvcSuitGradient" x1="8" y1="106" x2="120" y2="266" gradientUnits="userSpaceOnUse">
				<stop stop-color="#20416f"/>
				<stop offset="1" stop-color="#0d1f3d"/>
			</linearGradient>
			<linearGradient id="cvcSuitLapelGradient" x1="32" y1="106" x2="96" y2="204" gradientUnits="userSpaceOnUse">
				<stop stop-color="#2c507f"/>
				<stop offset="1" stop-color="#16305c"/>
			</linearGradient>
			<linearGradient id="cvcSkinGradient" x1="30" y1="54" x2="98" y2="122" gradientUnits="userSpaceOnUse">
				<stop stop-color="#f8d3ae"/>
				<stop offset="1" stop-color="#eab98a"/>
			</linearGradient>
			<linearGradient id="cvcHairGradient" x1="28" y1="47" x2="92" y2="100" gradientUnits="userSpaceOnUse">
				<stop stop-color="#4a3527"/>
				<stop offset="1" stop-color="#2a1c12"/>
			</linearGradient>
			<linearGradient id="cvcCapGradient" x1="2" y1="0" x2="78" y2="32" gradientUnits="userSpaceOnUse">
				<stop stop-color="#f0a825"/>
				<stop offset="1" stop-color="#c8790a"/>
			</linearGradient>
			<filter id="cvcSoftShadow" x="-20%" y="-20%" width="140%" height="140%">
				<feDropShadow dx="0" dy="8" stdDeviation="10" flood-color="#0a58ca" flood-opacity="0.16"/>
			</filter>
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
 * Trust micro-signal trong hero (Phase 10A.5, Phần 10) - THAY vì số liệu
 * xã hội giả ("10.000 học viên", "98% hài lòng"...), liệt kê ĐÚNG năng
 * lực hệ thống ĐANG THẬT SỰ CÓ (chấm điểm tự động - xem ExamScoringService
 * Phase 6; theo dõi tiến độ - Learning Path/Exam History Phase 10; nguồn
 * tuyển dụng ghi rõ cơ quan - Recruitment.agency; nội dung theo chủ đề -
 * Topic taxonomy) - đây là sự thật kiểm chứng được bằng chính tính năng
 * đang chạy, không phải lời quảng cáo.
 */
function cvc_render_hero_trust_signals(): void {
	$items = array(
		'Nội dung theo chủ đề, môn thi cụ thể',
		'Bài thi chấm điểm tự động',
		'Theo dõi tiến độ học tập',
		'Nguồn tuyển dụng ghi rõ cơ quan',
	);
	?>
	<ul class="cvc-hero__trust">
		<?php foreach ( $items as $item ) : ?>
			<li><?php cvc_render_icon( 'check', 15 ); ?> <?php echo esc_html( $item ); ?></li>
		<?php endforeach; ?>
	</ul>
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
			'icon'  => 'clock',
			'color' => 'green',
			'title' => 'Học tập linh hoạt',
			'desc'  => 'Mọi lúc, mọi nơi',
		),
		array(
			'icon'  => 'route',
			'color' => 'blue',
			'title' => 'Lộ trình cá nhân hóa',
			'desc'  => 'Theo mục tiêu của bạn',
		),
		array(
			'icon'  => 'briefcase',
			'color' => 'amber',
			'title' => 'Cơ hội nghề nghiệp',
			'desc'  => 'Được cập nhật liên tục',
		),
		array(
			'icon'  => 'users',
			'color' => 'purple',
			'title' => 'Cộng đồng hỗ trợ',
			'desc'  => 'Hơn cả một nền tảng học tập',
		),
	);
	?>
	<div class="cvc-hero-feature-card">
		<?php foreach ( $items as $item ) : ?>
			<div class="cvc-hero-feature-card__row">
				<span class="cvc-hero-feature-card__icon cvc-hero-feature-card__icon--<?php echo esc_attr( $item['color'] ); ?>"><?php cvc_render_icon( $item['icon'], 18 ); ?></span>
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
			'icon'  => 'courses',
			'image' => 'VALUE_ICONS/01_course',
			'color' => 'blue',
			'title' => 'Khóa học đa dạng',
			'desc'  => 'Học theo từng chủ đề',
		),
		array(
			'icon'  => 'document',
			'image' => 'VALUE_ICONS/02_documents',
			'color' => 'green',
			'title' => 'Tài liệu phong phú',
			'desc'  => 'Tra cứu kiến thức cần thiết',
		),
		array(
			'icon'  => 'briefcase',
			'image' => 'VALUE_ICONS/03_recruitment',
			'color' => 'amber',
			'title' => 'Tuyển dụng cập nhật',
			'desc'  => 'Theo dõi cơ hội mới',
		),
		array(
			'icon'  => 'exams',
			'image' => 'VALUE_ICONS/04_exam',
			'color' => 'purple',
			'title' => 'Ôn thi hệ thống',
			'desc'  => 'Luyện đề và theo dõi kết quả',
		),
		array(
			'icon'  => 'trending',
			'image' => 'VALUE_ICONS/05_career',
			'color' => 'pink',
			'title' => 'Phát triển sự nghiệp',
			'desc'  => 'Xây dựng lộ trình cá nhân',
		),
	);
	?>
	<ul class="cvc-value-strip">
		<?php foreach ( $items as $item ) : ?>
			<li class="cvc-value-strip__item">
				<span class="cvc-value-strip__icon cvc-value-strip__icon--<?php echo esc_attr( $item['color'] ); ?> cvc-value-strip__icon--asset">
					<?php
					/*
					 * Phase 10A.16: ưu tiên asset PNG thật (Homepage V2 cut
					 * assets, đã đúng màu/thứ tự khớp $items) - fallback SVG
					 * cũ nếu thiếu file. alt="" (trong helper) vì tiêu đề/mô
					 * tả bên dưới đã là text thật - icon chỉ trang trí.
					 */
					if ( ! cvc_render_v2_asset_icon( $item['image'], 44, 44 ) ) {
						cvc_render_icon( $item['icon'], 22 );
					}
					?>
				</span>
				<span class="cvc-value-strip__title"><?php echo esc_html( $item['title'] ); ?></span>
				<span class="cvc-value-strip__desc"><?php echo esc_html( $item['desc'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Statistics strip (Phase 10A.6, Phần 13) - CHỈ render khi
 * cvc_homepage_demo_enabled() (kiểm tra lại ngay trong hàm - phòng thủ
 * kép, không dựa hoàn toàn vào nơi gọi). Không có aggregate API thật nào
 * cung cấp các số liệu quy mô này - toàn bộ là design fixture, luôn đi
 * kèm badge "Demo" trên chính hàng số liệu, không lẫn với Value strip
 * thật ở trên (đặc điểm sản phẩm, không phải số liệu).
 */
function cvc_render_homepage_statistics_strip(): void {
	if ( ! cvc_homepage_demo_enabled() ) {
		return;
	}

	$stats = cvc_homepage_demo_statistics();
	?>
	<div class="cvc-stats-strip">
		<span class="cvc-stats-strip__label"><?php cvc_render_demo_badge(); ?> Số liệu minh họa</span>
		<ul class="cvc-stats-strip__list">
			<?php foreach ( $stats as $stat ) : ?>
				<li class="cvc-stats-strip__item">
					<span class="cvc-stats-strip__icon"><?php cvc_render_icon( $stat['icon'], 20 ); ?></span>
					<span>
						<strong class="cvc-stats-strip__value"><?php echo esc_html( $stat['value'] ); ?></strong>
						<span class="cvc-stats-strip__desc"><?php echo esc_html( $stat['label'] ); ?></span>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
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
		<span class="cvc-empty-panel__icon"><?php cvc_render_icon( $icon, 30 ); ?></span>
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
/**
 * "Con đường của bạn" - Learning Journey (Phase 10A.5, Phần 12) - giải
 * thích product value bằng 1 flow 5 bước, mỗi bước trỏ ĐÚNG route thật
 * đã tồn tại (không phải minh họa suông) - bước cần đăng nhập tự redirect
 * qua login kèm intended destination, đúng pattern cvc_require_login()
 * đã dùng xuyên suốt Phase 10.
 */
function cvc_render_learning_journey_section(): void {
	$logged_in = cvc_is_logged_in();
	$steps     = array(
		array(
			'icon'  => 'target',
			'title' => 'Chọn mục tiêu',
			'desc'  => 'Đặt mục tiêu ôn thi phù hợp với bạn',
			'url'   => $logged_in ? cvc_account_url( 'goals' ) : cvc_login_url( cvc_account_url( 'goals' ) ),
		),
		array(
			'icon'  => 'route',
			'title' => 'Học theo lộ trình',
			'desc'  => 'Khóa học và chủ đề được sắp xếp theo mục tiêu',
			'url'   => $logged_in ? cvc_account_url( 'learning-path' ) : cvc_login_url( cvc_account_url( 'learning-path' ) ),
		),
		array(
			'icon'  => 'exams',
			'title' => 'Luyện thi',
			'desc'  => 'Làm đề trắc nghiệm, chấm điểm tự động',
			'url'   => cvc_exams_url(),
		),
		array(
			'icon'  => 'trending',
			'title' => 'Theo dõi tiến độ',
			'desc'  => 'Xem lại kết quả và tiến độ học tập',
			'url'   => $logged_in ? cvc_account_url( 'exam-history' ) : cvc_login_url( cvc_account_url( 'exam-history' ) ),
		),
		array(
			'icon'  => 'briefcase',
			'title' => 'Sẵn sàng cho cơ hội',
			'desc'  => 'Khám phá tin tuyển dụng phù hợp',
			'url'   => cvc_recruitments_url(),
		),
	);
	?>
	<section class="cvc-section cvc-section--tint cvc-journey-section">
		<div class="container">
			<?php cvc_render_section_header( 'Con đường của bạn', 'Đặt mục tiêu ngay', $logged_in ? cvc_account_url( 'goals' ) : cvc_login_url( cvc_account_url( 'goals' ) ), 'route' ); ?>
			<ol class="cvc-journey">
				<?php foreach ( $steps as $index => $step ) : ?>
					<li class="cvc-journey__step">
						<a class="cvc-journey__link" href="<?php echo esc_url( $step['url'] ); ?>">
							<span class="cvc-journey__number"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
							<span class="cvc-journey__icon"><?php cvc_render_icon( $step['icon'], 22 ); ?></span>
							<span class="cvc-journey__title"><?php echo esc_html( $step['title'] ); ?></span>
							<span class="cvc-journey__desc"><?php echo esc_html( $step['desc'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
	<?php
}

function cvc_render_goal_direction_section(): void {
	/*
	 * Phase 10A.16: đã thử tích hợp 04_goal_icons (Homepage V2 cut assets)
	 * cho 4/5 mục nhưng đo thực tế cho thấy asset lệch tâm + phần nội dung
	 * chỉ lấp 28-51% khung canvas (so với 85% ở 01_value_icons cùng bộ) -
	 * hiển thị nhỏ/lệch rõ rệt cạnh icon SVG mục thứ 5 (không có asset
	 * tương ứng), tạo "icon kích thước không đồng đều" giữa 5 card. Theo
	 * đúng nguyên tắc "asset làm giao diện xấu hơn thì bỏ" - giữ nguyên
	 * SVG cho cả 5 mục thay vì trộn PNG lệch + SVG.
	 */
	$directions = array(
		array(
			'icon'  => 'building',
			'label' => 'Thi công chức',
			'desc'  => 'Tin tuyển dụng khối công chức',
			'url'   => cvc_recruitments_url() . '?recruitment_type=civil_servant',
		),
		array(
			'icon'  => 'users',
			'label' => 'Thi viên chức',
			'desc'  => 'Tin tuyển dụng khối viên chức',
			'url'   => cvc_recruitments_url() . '?recruitment_type=public_employee',
		),
		array(
			'icon'  => 'trending',
			'label' => 'Thi thăng hạng',
			'desc'  => 'Luyện đề trắc nghiệm',
			'url'   => cvc_exams_url(),
		),
		array(
			'icon'  => 'document',
			'label' => 'Bồi dưỡng nghiệp vụ',
			'desc'  => 'Khóa học theo chuyên đề',
			'url'   => cvc_courses_url(),
		),
		array(
			'icon'  => 'lightbulb',
			'label' => 'Nâng cao kỹ năng',
			'desc'  => 'Kiến thức, kinh nghiệm thực tế',
			'url'   => cvc_knowledge_url(),
		),
	);
	?>
	<section class="cvc-section cvc-section--tint cvc-goal-section">
		<div class="container">
			<?php cvc_render_section_header( 'Bạn đang hướng đến điều gì?', 'Khám phá tất cả', cvc_search_url(), 'target' ); ?>
			<p class="cvc-goal-section__subtitle">Chọn hướng đi để tìm nội dung ôn tập và tin tuyển dụng phù hợp nhất.</p>

			<div class="cvc-goal-section__grid">
				<div class="cvc-goal-direction-grid">
					<?php foreach ( $directions as $direction ) : ?>
						<a class="cvc-goal-direction-card" href="<?php echo esc_url( $direction['url'] ); ?>">
							<span class="cvc-goal-direction-card__icon"><?php cvc_render_icon( $direction['icon'], 24 ); ?></span>
							<span class="cvc-goal-direction-card__label"><?php echo esc_html( $direction['label'] ); ?></span>
							<span class="cvc-goal-direction-card__desc"><?php echo esc_html( $direction['desc'] ); ?></span>
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
		<span class="cvc-resource-card__icon cvc-resource-card__icon--<?php echo esc_attr( $color ); ?>"><?php cvc_render_icon( $icon, 26 ); ?></span>
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
 * Ảnh minh họa đầu sidebar "Tuyển dụng mới nhất" (Phase 10A.10, Phần 8) -
 * thuần trang trí (aria-hidden, không có alt text ý nghĩa), KHÔNG kèm số
 * liệu/text nào - danh sách tin tuyển dụng bên dưới vẫn 100% dữ liệu thật/
 * fixture có nhãn Demo như cũ, ảnh này không thêm bất kỳ claim nào.
 */
function cvc_render_recruitment_panel_banner(): void {
	$photo = 'assets/images/homepage-v2/SECTIONS/recruitment';
	if ( ! file_exists( get_theme_file_path( "/{$photo}.jpg" ) ) ) {
		return;
	}
	?>
	<div class="cvc-recruitment-panel__banner" aria-hidden="true">
		<picture>
			<source srcset="<?php echo esc_url( get_theme_file_uri( "/{$photo}.webp" ) ); ?>" type="image/webp">
			<img src="<?php echo esc_url( get_theme_file_uri( "/{$photo}.jpg" ) ); ?>" alt="" loading="lazy" width="412" height="210">
		</picture>
	</div>
	<?php
}

/**
 * Banner CTA cuối trang chủ (Phần 8 FINAL CTA) - dải xanh đậm full-width,
 * skyline minh họa bằng SVG (không phải ảnh ngoài).
 */
function cvc_render_homepage_cta_banner(): void {
	$photo = 'assets/images/homepage-v2/SECTIONS/cta-background';
	?>
	<section class="cvc-cta-banner">
		<?php if ( file_exists( get_theme_file_path( "/{$photo}.jpg" ) ) ) : ?>
			<picture class="cvc-cta-banner__photo" aria-hidden="true">
				<source srcset="<?php echo esc_url( get_theme_file_uri( "/{$photo}.webp" ) ); ?>" type="image/webp">
				<img src="<?php echo esc_url( get_theme_file_uri( "/{$photo}.jpg" ) ); ?>" alt="" loading="lazy" width="377" height="210">
			</picture>
		<?php endif; ?>
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
