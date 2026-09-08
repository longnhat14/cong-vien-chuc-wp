<?php
/**
 * Danh sách tin tuyển dụng - /tuyen-dung/ và /tuyen-dung/page/{n}/
 *
 * Filter dùng đúng tên param thực tế của GET /api/recruitments
 * (RecruitmentController::index()) - "search", "recruitment_type",
 * "deadline_from", "deadline_to" - không đặt tên khác rồi tự map, tránh
 * thêm 1 lớp dịch không cần thiết.
 *
 * province_id/admin_unit_id/agency_id cũng được API hỗ trợ nhưng KHÔNG có
 * UI ở đây (Phase 3.5, Phần 3 - DEFERRED): không có public endpoint nào
 * liệt kê provinces/agencies để build dropdown hợp lệ, và việc tạo danh
 * sách cứng trong theme sẽ là suy đoán/fake dữ liệu tra cứu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
$search = trim( preg_replace( '/\s+/', ' ', $search ) );

$valid_recruitment_types = array( 'civil_servant', 'public_employee', 'other' );
$recruitment_type        = isset( $_GET['recruitment_type'] ) ? sanitize_key( wp_unslash( $_GET['recruitment_type'] ) ) : '';
if ( ! in_array( $recruitment_type, $valid_recruitment_types, true ) ) {
	$recruitment_type = '';
}

/**
 * Chỉ kiểm tra ĐÚNG hình dạng ngày (YYYY-MM-DD) ở đây - không tự validate
 * business rule (VD from > to). Việc đó do API quyết định (422) và
 * template tự hiển thị thông báo tương ứng, tránh 2 nguồn sự thật cho
 * cùng 1 rule.
 */
$sanitize_date_param = static function ( string $key ): string {
	$raw = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
};

$deadline_from = $sanitize_date_param( 'deadline_from' );
$deadline_to   = $sanitize_date_param( 'deadline_to' );

$has_filter = ( '' !== $search ) || ( '' !== $recruitment_type ) || ( '' !== $deadline_from ) || ( '' !== $deadline_to );

$query_args = array(
	'per_page' => 12,
	'page'     => $paged,
);
if ( '' !== $search ) {
	$query_args['search'] = $search;
}
if ( '' !== $recruitment_type ) {
	$query_args['recruitment_type'] = $recruitment_type;
}
if ( '' !== $deadline_from ) {
	$query_args['deadline_from'] = $deadline_from;
}
if ( '' !== $deadline_to ) {
	$query_args['deadline_to'] = $deadline_to;
}

/**
 * Build URL /tuyen-dung/ (hoặc /tuyen-dung/page/{n}/) kèm filter hiện tại
 * - dùng lại cho canonical/pagination link để giữ đúng state khi chuyển
 * trang, không đổi tên field so với API.
 */
$build_url = static function ( int $target_page ) use ( $search, $recruitment_type, $deadline_from, $deadline_to ): string {
	$extra = array_filter(
		array(
			'search'           => $search,
			'recruitment_type' => $recruitment_type,
			'deadline_from'    => $deadline_from,
			'deadline_to'      => $deadline_to,
		)
	);

	return empty( $extra )
		? cvc_recruitments_url( $target_page )
		: add_query_arg( $extra, cvc_recruitments_url( $target_page ) );
};

$service = new CVC_Recruitment_Service();
$result  = $service->list( $query_args );

$ok         = (bool) $result['ok'];
$status     = (int) $result['status'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$items      = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok ) {
	status_header( 422 === $status ? 422 : ( 429 === $status ? 429 : 503 ) );
}

cvc_seo_set_title( 'Tuyển dụng' );
cvc_seo_set_description( 'Thông tin tuyển dụng công chức, viên chức mới nhất từ các cơ quan, đơn vị.' );

if ( $has_filter || $currentPg > 1 ) {
	// Có filter/phân trang - tránh tạo hàng loạt URL indexable, giống
	// quy tắc đã áp dụng cho /tim-kiem/ ở Phase 2B.
	cvc_seo_set_noindex();
} else {
	cvc_seo_set_canonical( $build_url( 1 ) );
}

cvc_seo_set_pagination_links(
	$currentPg > 1 ? $build_url( $currentPg - 1 ) : null,
	$currentPg < $lastPg ? $build_url( $currentPg + 1 ) : null
);

get_header();
?>

<main id="main" class="container cvc-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array( 'label' => 'Tuyển dụng' ),
		)
	);
	?>

	<header class="cvc-page-header">
		<h1>Tuyển dụng</h1>
	</header>

	<?php
	/*
	 * Phase 10A.19: "Duyệt nhanh theo lĩnh vực" - dùng 10 icon Public
	 * Service Positions (CVC Homepage Icon Pack V1). API recruitments/
	 * positions KHÔNG có field ngành nghề/lĩnh vực riêng (chỉ có
	 * recruitment_type: civil_servant/public_employee/other - xem migration
	 * recruitments/positions) nên KHÔNG thể gắn icon này làm badge/filter
	 * category cho từng tin thật. Thay vào đó dùng làm lối tắt tìm kiếm
	 * thật qua đúng param "search" đã có sẵn (không suy diễn/gắn nhãn sai
	 * cho dữ liệu, không thêm field giả).
	 */
	$field_shortcuts = array(
		array( 'icon' => 'positions/admin', 'label' => 'Hành chính / Văn phòng', 'keyword' => 'hành chính' ),
		array( 'icon' => 'positions/finance', 'label' => 'Tài chính / Kế toán', 'keyword' => 'tài chính' ),
		array( 'icon' => 'positions/law', 'label' => 'Pháp luật / Tư pháp', 'keyword' => 'pháp luật' ),
		array( 'icon' => 'positions/inspection', 'label' => 'Thanh tra / Kiểm tra', 'keyword' => 'thanh tra' ),
		array( 'icon' => 'positions/health', 'label' => 'Y tế', 'keyword' => 'y tế' ),
		array( 'icon' => 'positions/construction', 'label' => 'Xây dựng / Đô thị', 'keyword' => 'xây dựng' ),
		array( 'icon' => 'positions/natural', 'label' => 'Tài nguyên / Môi trường', 'keyword' => 'tài nguyên môi trường' ),
		array( 'icon' => 'positions/agriculture', 'label' => 'Nông nghiệp', 'keyword' => 'nông nghiệp' ),
		array( 'icon' => 'positions/culture', 'label' => 'Văn hóa / Thể thao / Du lịch', 'keyword' => 'văn hóa' ),
		array( 'icon' => 'positions/communication', 'label' => 'Truyền thông / Báo chí', 'keyword' => 'truyền thông' ),
	);
	?>
	<nav class="cvc-field-shortcuts" aria-label="Duyệt nhanh theo lĩnh vực">
		<h2 class="cvc-field-shortcuts__title">Duyệt nhanh theo lĩnh vực</h2>
		<ul class="cvc-field-shortcuts__list">
			<?php foreach ( $field_shortcuts as $shortcut ) : ?>
				<li>
					<a class="cvc-field-shortcuts__item" href="<?php echo esc_url( add_query_arg( array( 'search' => $shortcut['keyword'] ), cvc_recruitments_url() ) ); ?>">
						<span class="cvc-field-shortcuts__icon" aria-hidden="true">
							<?php cvc_render_homepage_icon_pack_v1( $shortcut['icon'], 32, 32 ); ?>
						</span>
						<span class="cvc-field-shortcuts__label"><?php echo esc_html( $shortcut['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<form class="cvc-filter-form" method="get" action="<?php echo esc_url( cvc_recruitments_url() ); ?>">
		<div class="cvc-filter-form__field">
			<label class="screen-reader-text" for="cvc-recruitment-search">Từ khóa</label>
			<input
				type="search"
				id="cvc-recruitment-search"
				name="search"
				value="<?php echo esc_attr( $search ); ?>"
				placeholder="Tìm theo tiêu đề, mã tin, mô tả..."
			>
		</div>
		<div class="cvc-filter-form__field">
			<label class="screen-reader-text" for="cvc-recruitment-type">Loại tuyển dụng</label>
			<select id="cvc-recruitment-type" name="recruitment_type">
				<option value="">Tất cả loại</option>
				<?php foreach ( $valid_recruitment_types as $type_value ) : ?>
					<option value="<?php echo esc_attr( $type_value ); ?>" <?php selected( $recruitment_type, $type_value ); ?>>
						<?php echo esc_html( cvc_recruitment_type_label( $type_value ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="cvc-filter-form__field">
			<label for="cvc-recruitment-deadline-from">Hạn nộp từ</label>
			<input
				type="date"
				id="cvc-recruitment-deadline-from"
				name="deadline_from"
				value="<?php echo esc_attr( $deadline_from ); ?>"
			>
		</div>
		<div class="cvc-filter-form__field">
			<label for="cvc-recruitment-deadline-to">đến</label>
			<input
				type="date"
				id="cvc-recruitment-deadline-to"
				name="deadline_to"
				value="<?php echo esc_attr( $deadline_to ); ?>"
			>
		</div>
		<button type="submit" class="cvc-btn cvc-btn--primary">Lọc</button>
		<?php if ( $has_filter ) : ?>
			<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xóa lọc</a>
		<?php endif; ?>
	</form>

	<?php if ( ! $ok ) : ?>

		<?php
		if ( 422 === $status ) {
			cvc_render_error_state( 'Bộ lọc chưa hợp lệ (kiểm tra lại khoảng ngày hạn nộp). Vui lòng thử lại.' );
		} elseif ( 429 === $status ) {
			cvc_render_error_state( 'Bạn đang gửi quá nhiều yêu cầu. Vui lòng thử lại sau.' );
		} else {
			cvc_render_error_state();
		}

		if ( $has_filter ) :
			?>
			<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xóa lọc và thử lại</a></p>
		<?php endif; ?>

	<?php elseif ( empty( $items ) ) : ?>
		<?php cvc_render_empty_state( $has_filter ? 'Không có tin tuyển dụng phù hợp với bộ lọc hiện tại.' : 'Chưa có tin tuyển dụng nào.' ); ?>
		<?php if ( $has_filter ) : ?>
			<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xóa lọc</a></p>
		<?php endif; ?>
	<?php else : ?>
		<div class="cvc-card-grid">
			<?php foreach ( $items as $item ) : ?>
				<?php cvc_render_recruitment_card( $item ); ?>
			<?php endforeach; ?>
		</div>

		<?php cvc_render_pagination( $currentPg, $lastPg, $build_url ); ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
