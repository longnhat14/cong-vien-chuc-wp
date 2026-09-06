<?php
/**
 * Danh sách tin tuyển dụng - /tuyen-dung/ và /tuyen-dung/page/{n}/
 *
 * Filter dùng đúng tên param thực tế của GET /api/recruitments
 * (RecruitmentController::index()) - "search" và "recruitment_type" -
 * không đặt tên khác rồi tự map, tránh thêm 1 lớp dịch không cần thiết.
 * Chỉ 2 filter này có UI vì đây là 2 field không cần danh mục tra cứu
 * (enum cố định / free text). province_id/admin_unit_id/agency_id cũng
 * được API hỗ trợ nhưng KHÔNG có UI ở đây - không có public endpoint
 * nào liệt kê provinces/agencies để build dropdown hợp lệ, và việc tạo
 * danh sách cứng trong theme sẽ là suy đoán/fake dữ liệu tra cứu. Xem
 * ghi chú "Remaining limitations" trong báo cáo Phase 3 task 3.1+3.3.
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

$has_filter = ( '' !== $search ) || ( '' !== $recruitment_type );

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

/**
 * Build URL /tuyen-dung/ (hoặc /tuyen-dung/page/{n}/) kèm filter hiện tại
 * - dùng lại cho canonical/pagination link để giữ đúng state khi chuyển
 * trang, không đổi tên field so với API.
 */
$build_url = static function ( int $target_page ) use ( $search, $recruitment_type ): string {
	$extra = array_filter(
		array(
			'search'           => $search,
			'recruitment_type' => $recruitment_type,
		)
	);

	return empty( $extra )
		? cvc_recruitments_url( $target_page )
		: add_query_arg( $extra, cvc_recruitments_url( $target_page ) );
};

$service = new CVC_Recruitment_Service();
$result  = $service->list( $query_args );

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$items      = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok ) {
	status_header( 503 );
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
		<button type="submit" class="cvc-btn cvc-btn--primary">Lọc</button>
		<?php if ( $has_filter ) : ?>
			<a class="cvc-btn cvc-btn--text" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xóa lọc</a>
		<?php endif; ?>
	</form>

	<?php if ( ! $ok ) : ?>
		<?php cvc_render_error_state(); ?>
	<?php elseif ( empty( $items ) ) : ?>
		<?php cvc_render_empty_state( $has_filter ? 'Không có tin tuyển dụng phù hợp với bộ lọc hiện tại.' : 'Chưa có tin tuyển dụng nào.' ); ?>
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
