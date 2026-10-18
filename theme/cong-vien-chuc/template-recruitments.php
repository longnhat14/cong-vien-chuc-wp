<?php
/**
 * Danh sách tin tuyển dụng - giao diện văn bản (16/10/2026)
 * URL: /tuyen-dung/ và /tuyen-dung/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;
$q     = cvc_listing_query();

// Lọc theo tỉnh/thành (?tinh=<id>) - từ khu "Việc làm quanh bạn" trên trang chủ.
$cvc_province_id   = isset( $_GET['tinh'] ) ? absint( $_GET['tinh'] ) : 0;
$cvc_province_name = '';
if ( $cvc_province_id > 0 && function_exists( 'cvc_home17_data' ) ) {
	foreach ( (array) ( cvc_home17_data()['provinces'] ?? array() ) as $cvc_p ) {
		if ( (int) ( $cvc_p['id'] ?? 0 ) === $cvc_province_id ) {
			$cvc_province_name = (string) ( $cvc_p['name'] ?? '' );
			break;
		}
	}
	if ( '' === $cvc_province_name ) {
		$cvc_province_id = 0; // id không hợp lệ -> bỏ lọc.
	}
}

// Phase 16: loc theo loai tuyen dung (?loai=) x phuong thuc (?phuong-thuc=) - bang tra backend.
$cvc_types   = cvc_employment_type_options();
$cvc_methods = cvc_recruitment_method_options();
$cvc_type    = isset( $_GET['loai'] ) ? sanitize_key( wp_unslash( $_GET['loai'] ) ) : '';
$cvc_type    = isset( $cvc_types[ $cvc_type ] ) ? $cvc_type : '';
$cvc_method  = isset( $_GET['phuong-thuc'] ) ? sanitize_key( wp_unslash( $_GET['phuong-thuc'] ) ) : '';
$cvc_method  = isset( $cvc_methods[ $cvc_method ] ) ? $cvc_method : '';
$cvc_filter_args = array_filter(
	array(
		'tinh'        => $cvc_province_id > 0 ? $cvc_province_id : null,
		'loai'        => '' !== $cvc_type ? $cvc_type : null,
		'phuong-thuc' => '' !== $cvc_method ? $cvc_method : null,
	)
);
$cvc_list_url = static function ( array $override = array(), int $page = 1 ) use ( $cvc_filter_args, $q ): string {
	$args = array_filter( array_merge( $cvc_filter_args, $override ), static fn ( $v ) => null !== $v && '' !== $v );
	$url  = cvc_listing_page_url_builder( 'cvc_recruitments_url', $q )( $page );

	return empty( $args ) ? $url : add_query_arg( $args, $url );
};

$service = new CVC_Recruitment_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
	) + ( $cvc_province_id > 0 ? array( 'province_id' => $cvc_province_id ) : array() )
	+ ( '' !== $cvc_type ? array( 'employment_type' => $cvc_type ) : array() )
	+ ( '' !== $cvc_method ? array( 'method' => $cvc_method ) : array() )
);

$ok           = (bool) $result['ok'];
$pagination   = $ok ? ( $result['data']['data'] ?? array() ) : array();
$recruitments = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg    = (int) ( $pagination['current_page'] ?? $paged );
$lastPg       = (int) ( $pagination['last_page'] ?? 1 );

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

// Phase 16: dot dang to chuc thi / co ket qua - ke ca dot chi co lich thi, danh sach, ket qua
// (chua thu thap duoc thong bao mo dang ky). Chi trang 1, khong tim kiem / loc loai.
$cvc_rounds = array();
if ( 1 === $paged && '' === $q && '' === $cvc_type && '' === $cvc_method ) {
	$cvc_rounds = cvc_recruitment_rounds_in_progress( 10, $cvc_province_id );
}

cvc_seo_set_title( 'Tin tuyển dụng công chức, viên chức' );
cvc_seo_set_description( 'Tin tuyển dụng công chức, viên chức kèm vị trí, chỉ tiêu, hạn nộp hồ sơ và tài liệu ôn thi theo từng đợt.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_recruitments_url' );

get_header();
?>

<main id="main" class="dz-main dz-main">
<div class="dz-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Tuyển dụng' ),
		)
	);
	$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : null;
	cvc_render_listing_hero(
		'Tuyển dụng',
		'Tin tuyển dụng công chức, viên chức',
		'Thông báo tuyển dụng tổng hợp từ cổng thông tin chính thức, kèm vị trí, chỉ tiêu, hạn nộp hồ sơ và tài liệu ôn thi theo từng đợt.',
		null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'tin tuyển dụng' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
	);
	?>
	<div class="dz-cols is-3">
		<aside class="dz-colside">
			<?php cvc_render_listing_search( cvc_recruitments_url(), $q, 'Tên cơ quan, địa phương…', 'Tìm tin tuyển dụng' ); ?>
			<nav class="dz-box" aria-label="Lọc theo loại tuyển dụng">
				<h2 class="dz-box-head">Loại tuyển dụng</h2>
				<div class="dz-box-body">
					<ul class="dz-toc">
						<li><a href="<?php echo esc_url( $cvc_list_url( array( 'loai' => null ) ) ); ?>" class="<?php echo '' === $cvc_type ? 'is-active' : ''; ?>">Tất cả</a></li>
						<?php foreach ( $cvc_types as $k => $lbl ) : ?>
							<li><a href="<?php echo esc_url( $cvc_list_url( array( 'loai' => $k ) ) ); ?>" class="<?php echo $k === $cvc_type ? 'is-active' : ''; ?>"><?php echo esc_html( $lbl ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
			<nav class="dz-box" aria-label="Lọc theo phương thức tuyển">
				<h2 class="dz-box-head">Phương thức tuyển</h2>
				<div class="dz-box-body">
					<ul class="dz-toc">
						<li><a href="<?php echo esc_url( $cvc_list_url( array( 'phuong-thuc' => null ) ) ); ?>" class="<?php echo '' === $cvc_method ? 'is-active' : ''; ?>">Tất cả</a></li>
						<?php foreach ( $cvc_methods as $k => $lbl ) : ?>
							<li><a href="<?php echo esc_url( $cvc_list_url( array( 'phuong-thuc' => $k ) ) ); ?>" class="<?php echo $k === $cvc_method ? 'is-active' : ''; ?>"><?php echo esc_html( $lbl ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
			<?php cvc_render_listing_explore_nav( 'recruitments' ); ?>
		</aside>

		<div class="dz-colmain">
			<div class="dz-h2"><h2><?php echo '' !== $q ? 'Kết quả cho “' . esc_html( $q ) . '”' : 'Đang nhận hồ sơ'; ?><?php echo '' !== $cvc_province_name ? ' · ' . esc_html( $cvc_province_name ) : ''; ?><?php echo '' !== $cvc_type ? ' · ' . esc_html( $cvc_types[ $cvc_type ] ) : ''; ?><?php echo '' !== $cvc_method ? ' · ' . esc_html( $cvc_methods[ $cvc_method ] ) : ''; ?></h2><span class="dz-muted" style="font-size:15px">Hiển thị <?php echo count( $recruitments ); ?><?php echo isset( $pagination['total'] ) ? ' / ' . esc_html( number_format_i18n( (int) $pagination['total'] ) ) : ''; ?> tin</span></div>

			<?php if ( ! $ok ) : ?>
				<?php cvc_render_error_state( 'Không tải được danh sách tin tuyển dụng, vui lòng thử lại sau.' ); ?>
			<?php elseif ( empty( $recruitments ) ) : ?>
				<?php cvc_render_empty_state( ( '' !== $cvc_type || '' !== $cvc_method ) ? 'Chưa có tin phù hợp bộ lọc. Hãy bỏ bớt điều kiện lọc.' : ( empty( $cvc_rounds ) ? 'Chưa có tin tuyển dụng nào.' : 'Hiện chưa có đợt tuyển dụng nào đang nhận hồ sơ. Các đợt đang tổ chức thi ở bên dưới.' ) ); ?>
			<?php endif; ?>

			<ol class="dz-list dz-numbered" id="recruitmentListContainer" start="<?php echo (int) ( ( $currentPg - 1 ) * 12 + 1 ); ?>">
				<?php foreach ( $recruitments as $rec ) : ?>
					<?php
					$slug         = is_string( $rec['slug'] ?? null ) ? $rec['slug'] : '';
					$title        = is_string( $rec['title'] ?? null ) ? $rec['title'] : 'Thông báo tuyển dụng';
					$agency_raw   = $rec['agency'] ?? null;
					$agency_name  = is_array( $agency_raw ) ? (string) ( $agency_raw['name'] ?? '' ) : ( is_string( $agency_raw ) ? $agency_raw : '' );
					$type_labels  = array( 'civil_servant' => 'Công chức', 'public_employee' => 'Viên chức' );
					$rec_type     = $cvc_types[ (string) ( $rec['employment_type'] ?? '' ) ] ?? ( $type_labels[ (string) ( $rec['recruitment_type'] ?? '' ) ] ?? '' );
					$rec_method   = (string) ( $rec['method_summary_label'] ?? '' );
					$quota        = ! empty( $rec['total_positions'] ) ? (int) $rec['total_positions'] : 0;
					$dates_raw    = is_array( $rec['dates'] ?? null ) ? $rec['dates'] : array();
					$deadline_raw = (string) ( $dates_raw['application_deadline'] ?? '' );
					$deadline     = '' !== $deadline_raw ? cvc_format_date_vn( $deadline_raw ) : 'Chưa công bố';
					$is_open      = '' === $deadline_raw || strtotime( $deadline_raw . ' 23:59:59' ) >= current_time( 'timestamp' );
					$summary      = is_string( $rec['summary'] ?? null ) ? $rec['summary'] : '';
					?>
					<li class="item-recruitment-card">
						<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_recruitment_url( $slug ) ); ?>"><?php echo esc_html( $title ); ?></a></h3>
						<div class="dz-item-meta">
							<span class="dz-tag <?php echo $is_open ? 'is-ok' : 'is-off'; ?>"><?php echo $is_open ? 'Đang nhận hồ sơ' : 'Đã hết hạn nộp'; ?></span>
							<?php if ( $rec_type ) : ?><span class="dz-tag"><?php echo esc_html( $rec_type ); ?></span><?php endif; ?>
							<?php if ( '' !== $rec_method ) : ?><span class="dz-tag"><?php echo esc_html( $rec_method ); ?></span><?php endif; ?>
							<?php if ( $agency_name ) : ?><span><?php echo esc_html( $agency_name ); ?></span><?php endif; ?>
						</div>
						<?php if ( '' !== $summary ) : ?><p class="dz-item-sum"><?php echo esc_html( wp_trim_words( $summary, 34 ) ); ?></p><?php endif; ?>
						<div class="dz-item-meta">
							<span>Chỉ tiêu: <strong><?php echo $quota > 0 ? esc_html( number_format_i18n( $quota ) ) : 'chưa công bố'; ?></strong></span>
							<span>Hạn nộp: <strong><?php echo esc_html( $deadline ); ?></strong></span>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php cvc_render_pagination( $currentPg, $lastPg, static fn( int $n ): string => $cvc_list_url( array(), $n ) ); ?>

			<?php cvc_render_suggest_widget(); ?>

			<?php if ( ! empty( $cvc_rounds ) ) : ?>
				<section id="dang-to-chuc-thi" class="dz-section" aria-labelledby="cvc-rounds" style="margin-top:26px">
					<div class="dz-h2"><h2 id="cvc-rounds">Đợt đang tổ chức thi / có kết quả</h2></div>
					<p class="dz-muted" style="margin:-4px 0 6px;font-style:italic">Dành cho thí sinh đã nộp hồ sơ: theo dõi danh sách thí sinh, triệu tập, tài liệu ôn tập, lịch thi và kết quả theo văn bản chính thức của cơ quan tuyển dụng.</p>
					<?php cvc_render_round_list( $cvc_rounds ); ?>
				</section>
			<?php endif; ?>
		</div>

		<aside class="dz-colside">
			<?php cvc_render_study_sidebar(); ?>
		</aside>
	</div>
</div>
</main>

<?php get_footer(); ?>
