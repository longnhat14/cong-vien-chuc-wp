<?php
/**
 * Trang ĐỢT TUYỂN DỤNG (Phase 16, 18/10/2026) - URL /tuyen-dung/dot/{slug}/
 *
 * Đợt tồn tại độc lập với tin mở đăng ký: nhiều đợt hệ thống chỉ thu thập được văn bản giai đoạn
 * sau (danh sách thí sinh, triệu tập, lịch thi, kết quả). Trang hiển thị dòng thời gian văn bản
 * chính thức của đợt. API chỉ trả đợt đạt ngưỡng công khai (tin chính đã đăng, hoặc có văn bản
 * chính thức lịch thi / danh sách / kết quả trong 12 tháng).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug   = sanitize_title( (string) get_query_var( 'cvc_round_slug' ) );
$result = ( new CVC_Api_Client( null, 12 ) )->get( '/api/recruitment-rounds/' . rawurlencode( $slug ) );
if ( ! $result['ok'] && 404 !== (int) ( $result['status'] ?? 0 ) ) {
	$result = ( new CVC_Api_Client( null, 20 ) )->get( '/api/recruitment-rounds/' . rawurlencode( $slug ) );
}
$round = $result['ok'] && is_array( $result['data']['data'] ?? null ) ? $result['data']['data'] : null;

// Đợt đã có tin đăng -> trang tin là trang chính (tránh 2 URL cùng nội dung).
if ( null !== $round && ! empty( $round['recruitment']['slug'] ) ) {
	wp_safe_redirect( cvc_recruitment_url( (string) $round['recruitment']['slug'] ) . '#van-ban-dot', 301 );
	exit;
}

if ( null === $round ) {
	$temporary = 404 !== (int) ( $result['status'] ?? 0 );
	status_header( $temporary ? 503 : 404 );
	if ( $temporary ) {
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'cvc: API tạm thời lỗi' );
	}
	cvc_seo_set_noindex();
	cvc_seo_set_title( $temporary ? 'Tạm thời chưa tải được đợt tuyển dụng' : 'Không tìm thấy đợt tuyển dụng' );
	get_header();
	?>
	<main id="main" class="dz-main">
		<div class="dz-wrap" style="max-width:780px">
			<?php cvc_render_notfound_state( $temporary ? 'Hệ thống đang bận, chưa tải được đợt tuyển dụng này. Vui lòng tải lại trang sau ít phút.' : 'Không tìm thấy đợt tuyển dụng này hoặc đợt chưa có văn bản chính thức để công bố.' ); ?>
			<p><a class="dz-btn" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">&larr; Xem tin tuyển dụng</a></p>
		</div>
	</main>
	<?php
	get_footer();
	return;
}

$title    = (string) ( $round['title'] ?? 'Đợt tuyển dụng' );
$events   = (array) ( $round['events'] ?? array() );
$agency   = (array) ( $round['agency'] ?? array() );
$province = (array) ( $round['province'] ?? array() );
$last     = (array) ( $round['last_event'] ?? array() );
$stage    = (string) ( $round['stage'] ?? '' );
$source   = (array) ( $round['source'] ?? array() );

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Tuyển dụng', 'url' => cvc_recruitments_url() ),
	array( 'label' => wp_trim_words( $title, 8 ) ),
);

$desc = (string) ( $round['stage_label'] ?? 'Đợt tuyển dụng' );
if ( ! empty( $last ) ) {
	$desc .= ' - mới nhất: ' . (string) ( $last['type_label'] ?? '' ) . ( ! empty( $last['issued_date'] ) ? ' ngày ' . cvc_format_date_vn( (string) $last['issued_date'] ) : '' ) . '.';
}
$desc .= ' ' . wp_trim_words( $title, 22 ) . '. Văn bản gốc của cơ quan tuyển dụng.';
cvc_seo_set_title( $title );
cvc_seo_set_description( $desc );
cvc_seo_set_canonical( cvc_round_url( (string) ( $round['slug'] ?? $slug ) ) );
cvc_seo_set_og( array( 'type' => 'website' ) );
if ( function_exists( 'cvc_seo_add_breadcrumb_jsonld' ) ) {
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();
?>

<main id="main" class="dz-main">
<div class="dz-wrap space-y-6">
	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<header class="dz-noticehead">
		<div class="dz-noticehead-top">
			<p class="dz-noticehead-org"><?php echo esc_html( mb_strtoupper( (string) ( $agency['name'] ?? ( $source['name'] ?? 'Cơ quan tuyển dụng' ) ), 'UTF-8' ) ); ?><?php if ( ! empty( $round['root_doc_number'] ) ) : ?><span>Văn bản gốc: <?php echo esc_html( (string) $round['root_doc_number'] ); ?></span><?php endif; ?></p>
			<p class="dz-noticehead-status"><?php echo cvc_round_stage_tag( $stage, (string) ( $round['stage_label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		</div>
		<p class="dz-kicker">Đợt tuyển dụng</p>
		<h1><?php echo esc_html( $title ); ?></h1>
		<span class="dz-rule" aria-hidden="true"></span>
		<table class="dz-facts">
			<tr>
				<td><span>Cơ quan</span><strong><?php echo esc_html( (string) ( $agency['name'] ?? ( $source['name'] ?? '—' ) ) ); ?></strong></td>
				<td><span>Tỉnh/thành</span><strong><?php echo esc_html( (string) ( $province['name'] ?? '—' ) ); ?></strong></td>
				<td><span>Số văn bản đã thu thập</span><strong><?php echo (int) ( $round['event_count'] ?? count( $events ) ); ?></strong></td>
				<td><span>Văn bản mới nhất</span><strong><?php echo esc_html( ! empty( $round['last_event_date'] ) ? cvc_format_date_vn( (string) $round['last_event_date'] ) : '—' ); ?></strong></td>
			</tr>
		</table>
	</header>

	<div class="dz-notice">
		<p class="font-extrabold"><i class="fa-solid fa-circle-info mr-1.5" aria-hidden="true"></i>Hệ thống chưa thu thập được thông báo mở đăng ký của đợt này.</p>
		<p style="margin:4px 0 0">Dưới đây là các văn bản chính thức của đợt đã thu thập được (danh sách thí sinh, triệu tập, lịch thi, kết quả…). Thí sinh vui lòng đối chiếu văn bản gốc tại trang của cơ quan tuyển dụng.<?php if ( ! empty( $source['url'] ) ) : ?> Nguồn: <a class="underline" href="<?php echo esc_url( (string) $source['url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( (string) ( $source['name'] ?? $source['url'] ) ); ?></a>.<?php endif; ?></p>
	</div>

	<div class="dz-cols is-left">
		<aside class="dz-colside">
			<nav class="dz-box" aria-label="Tuyển dụng">
				<h2 class="dz-box-head">Tuyển dụng</h2>
				<div class="dz-box-body">
					<ul class="dz-toc">
						<li><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tin đang nhận hồ sơ</a></li>
						<li><a href="<?php echo esc_url( cvc_recruitments_url() . '#dang-to-chuc-thi' ); ?>">Đợt đang tổ chức thi / có kết quả</a></li>
						<?php if ( ! empty( $province['id'] ) ) : ?>
							<li><a href="<?php echo esc_url( add_query_arg( 'tinh', (int) $province['id'], cvc_recruitments_url() ) ); ?>">Tin tại <?php echo esc_html( (string) ( $province['name'] ?? '' ) ); ?></a></li>
						<?php endif; ?>
					</ul>
				</div>
			</nav>
			<?php if ( function_exists( 'cvc_render_study_sidebar' ) ) { cvc_render_study_sidebar(); } ?>
		</aside>
		<div class="dz-colmain">
			<?php cvc_render_round_timeline( $events, array(), 'Dòng thời gian văn bản của đợt' ); ?>
		</div>
	</div>
</div>
</main>

<?php get_footer(); ?>
