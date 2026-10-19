<?php
/**
 * Cơ hội tuyển dụng theo vị trí (18/10/2026) - /tuyen-dung/vi-tri/ (+ /page/{n}/).
 *
 * Mỗi thông báo tổng hợp được tách thành từng vị trí (trang riêng /tuyen-dung/vi-tri/{slug}/) để lọc và SEO theo vị trí.
 * Dữ liệu: GET /api/recruitment-positions (q, province, family, type, open, page, per_page) - chỉ tin đã đăng.
 * Tham số trang: ?q= ?tinh= ?nhom= ?loai= ?con-han=1 (có bộ lọc -> noindex,follow; canonical về trang không lọc).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = max( 1, absint( get_query_var( 'cvc_paged' ) ) );
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$f = array(
	'q'       => mb_substr( sanitize_text_field( wp_unslash( (string) ( $_GET['q'] ?? '' ) ) ), 0, 120 ),
	'tinh'    => sanitize_title( wp_unslash( (string) ( $_GET['tinh'] ?? '' ) ) ),
	'nhom'    => sanitize_key( wp_unslash( (string) ( $_GET['nhom'] ?? '' ) ) ),
	'loai'    => sanitize_key( wp_unslash( (string) ( $_GET['loai'] ?? '' ) ) ),
	'con-han' => ! empty( $_GET['con-han'] ),
);
// phpcs:enable
$types = array( 'cong-chuc' => array( 'civil_servant', 'Công chức' ), 'vien-chuc' => array( 'public_employee', 'Viên chức' ), 'hop-dong' => array( 'contract', 'Hợp đồng lao động' ) );
if ( ! isset( $types[ $f['loai'] ] ) ) {
	$f['loai'] = '';
}
$query = array_filter(
	array(
		'q'        => $f['q'],
		'province' => $f['tinh'],
		'family'   => $f['nhom'],
		'type'     => '' !== $f['loai'] ? $types[ $f['loai'] ][0] : '',
		'open'     => $f['con-han'] ? 1 : '',
		'page'     => $paged,
		'per_page' => 20,
	),
	static fn ( $v ) => '' !== $v && null !== $v
);
$res = cvc_ci_api( '/api/recruitment-positions', $query, null, 12, 180 );
if ( ! $res['ok'] && 422 === (int) ( $res['status'] ?? 0 ) ) {
	// Tham số lọc không hợp lệ (nhóm nghề / tỉnh lạ) -> bỏ lọc đó thay vì báo lỗi.
	unset( $query['family'], $query['province'] );
	$f['nhom'] = '';
	$f['tinh'] = '';
	$res       = cvc_ci_api( '/api/recruitment-positions', $query, null, 12, 180 );
}
$data      = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
$items     = (array) ( $data['items'] ?? array() );
$facets    = (array) ( $data['facets'] ?? array() );
$pg        = (array) ( $data['pagination'] ?? array() );
$total     = (int) ( $pg['total'] ?? 0 );
$last      = max( 1, (int) ( $pg['last_page'] ?? 1 ) );
$filtered  = '' !== $f['q'] || '' !== $f['tinh'] || '' !== $f['nhom'] || '' !== $f['loai'] || $f['con-han'];
$args      = array_filter( array( 'q' => $f['q'], 'tinh' => $f['tinh'], 'nhom' => $f['nhom'], 'loai' => $f['loai'], 'con-han' => $f['con-han'] ? 1 : '' ) );
$page_url  = static fn ( int $n ): string => add_query_arg( $args, cvc_positions_url( $n ) );

$prov_name = '';
foreach ( (array) ( $facets['provinces'] ?? array() ) as $p ) {
	if ( ( $p['slug'] ?? '' ) === $f['tinh'] ) {
		$prov_name = (string) $p['name'];
	}
}
$fam_name = '';
foreach ( (array) ( $facets['families'] ?? array() ) as $fm ) {
	if ( ( $fm['code'] ?? '' ) === $f['nhom'] ) {
		$fam_name = (string) $fm['label'];
	}
}

/* SEO */
$h1 = 'Tìm cơ hội tuyển dụng theo vị trí';
if ( '' !== $f['q'] || '' !== $prov_name || '' !== $fam_name ) {
	$h1 = 'Tuyển dụng ' . ( '' !== $f['q'] ? 'vị trí “' . $f['q'] . '”' : ( '' !== $fam_name ? 'nhóm ' . mb_strtolower( $fam_name, 'UTF-8' ) : 'công chức, viên chức' ) ) . ( '' !== $prov_name ? ' tại ' . $prov_name : '' );
}
cvc_seo_set_title( $filtered ? $h1 : 'Tuyển dụng theo vị trí – công chức, viên chức, hợp đồng' . ( $paged > 1 ? ' – trang ' . $paged : '' ) );
cvc_seo_set_description( 'Tra cứu từng vị trí tuyển dụng công chức, viên chức trong các thông báo đã đăng: chỉ tiêu, trình độ, chuyên ngành, hạn nộp hồ sơ. Lọc theo tỉnh/thành, nhóm nghề và loại tuyển dụng.' );
if ( $filtered || ! $res['ok'] || ( $paged > $last ) ) {
	cvc_seo_set_noindex();
	cvc_seo_set_canonical( cvc_positions_url() );
} else {
	cvc_seo_set_canonical( cvc_positions_url( $paged ) );
	cvc_seo_set_pagination_links( $paged > 1 ? cvc_positions_url( $paged - 1 ) : null, $paged < $last ? cvc_positions_url( $paged + 1 ) : null );
}
$crumbs = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Tuyển dụng', 'url' => cvc_recruitments_url() ),
	array( 'label' => 'Theo vị trí' ),
);
cvc_seo_add_breadcrumb_jsonld( $crumbs );
if ( $items ) {
	$list = array();
	foreach ( $items as $i => $it ) {
		$list[] = array( '@type' => 'ListItem', 'position' => ( $paged - 1 ) * 20 + $i + 1, 'url' => cvc_position_url( (string) $it['slug'] ), 'name' => (string) $it['name'] );
	}
	cvc_seo_add_json_ld( array( '@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $list ) );
}
if ( ! $res['ok'] ) {
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'cvc: API vị trí lỗi' );
}

get_header();
?>
<main id="main" class="rd is-hub">
<div class="rd-wrap">
	<?php cvc_render_breadcrumbs( $crumbs ); ?>

	<header class="rd-hero is-hub">
		<div class="rd-hero-main">
			<p class="rd-hero-eyebrow"><span class="rd-dot is-ok" aria-hidden="true"></span>Cơ hội tuyển dụng theo vị trí</p>
			<h1><?php echo esc_html( $h1 ); ?></h1>
			<p class="rd-hero-copy">Mỗi thông báo tuyển dụng tổng hợp được tách thành từng vị trí — xem chỉ tiêu, trình độ, chuyên ngành và hạn nộp của đúng vị trí bạn quan tâm, rồi mở văn bản gốc của cơ quan để đối chiếu.</p>
		</div>
		<div class="rd-metrics is-3">
			<div class="rd-metric"><span>Vị trí trên hệ thống</span><b><?php echo esc_html( number_format( (int) ( $facets['total'] ?? 0 ), 0, ',', '.' ) ); ?></b><small>Từ các thông báo đã đăng</small></div>
			<div class="rd-metric"><span>Đang nhận hồ sơ</span><b><?php echo esc_html( number_format( (int) ( $facets['open'] ?? 0 ), 0, ',', '.' ) ); ?></b><small>Còn hạn theo văn bản</small></div>
			<div class="rd-metric"><span>Tỉnh / thành có tuyển</span><b><?php echo count( array_filter( (array) ( $facets['provinces'] ?? array() ), static fn ( $p ) => (int) ( $p['count'] ?? 0 ) > 0 ) ); ?></b><small>Theo nơi làm việc</small></div>
		</div>
	</header>

	<form class="rd-card rd-filter" method="get" action="<?php echo esc_url( cvc_positions_url() ); ?>" role="search" aria-label="Lọc vị trí tuyển dụng">
		<label class="rd-search is-wide"><span aria-hidden="true">⌕</span><input type="search" name="q" id="rdh-q" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="Tên vị trí, chuyên ngành, mã vị trí, cơ quan…" aria-label="Từ khóa"></label>
		<select name="tinh" id="rdh-tinh" aria-label="Tỉnh / thành">
			<option value="">Mọi tỉnh / thành</option>
			<?php foreach ( (array) ( $facets['provinces'] ?? array() ) as $p ) : ?>
				<option value="<?php echo esc_attr( (string) $p['slug'] ); ?>"<?php selected( $f['tinh'], (string) $p['slug'] ); ?>><?php echo esc_html( $p['name'] . ' (' . (int) $p['count'] . ')' ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="nhom" id="rdh-nhom" aria-label="Nhóm nghề">
			<option value="">Mọi nhóm nghề</option>
			<?php foreach ( (array) ( $facets['families'] ?? array() ) as $fm ) : ?>
				<?php if ( 'other' === ( $fm['code'] ?? '' ) ) { continue; } ?>
				<option value="<?php echo esc_attr( (string) $fm['code'] ); ?>"<?php selected( $f['nhom'], (string) $fm['code'] ); ?>><?php echo esc_html( $fm['label'] . ' (' . (int) $fm['count'] . ')' ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="loai" id="rdh-loai" aria-label="Loại tuyển dụng">
			<option value="">Mọi loại tuyển dụng</option>
			<?php foreach ( $types as $k => $t ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $f['loai'], $k ); ?>><?php echo esc_html( $t[1] ); ?></option><?php endforeach; ?>
		</select>
		<label class="rd-check"><input type="checkbox" name="con-han" id="rdh-open" value="1"<?php checked( $f['con-han'] ); ?>> Chỉ vị trí còn hạn nộp</label>
		<span class="rd-filter-act">
			<button type="submit" class="rd-btn is-primary">Lọc vị trí</button>
			<?php if ( $filtered ) : ?><a class="rd-btn" href="<?php echo esc_url( cvc_positions_url() ); ?>">↺ Xóa lọc</a><?php endif; ?>
		</span>
	</form>

	<div class="rd-card rd-hubnear" data-rd-near>
		<p class="rd-hubnear-h"><span class="rd-ico" aria-hidden="true">⌖</span><b>Tuyển dụng gần bạn</b></p>
		<div class="rd-near-body" aria-live="polite"><p class="rd-small rd-muted">Đang tìm tin đang nhận hồ sơ gần bạn…</p></div>
	</div>

	<?php if ( ! $res['ok'] ) : ?>
		<div class="rd-card rd-card-body"><p><b>Chưa tải được danh sách vị trí.</b> Hệ thống đang bận — vui lòng <a href="<?php echo esc_url( $page_url( $paged ) ); ?>">tải lại trang</a> sau ít phút.</p></div>
	<?php elseif ( ! $items ) : ?>
		<div class="rd-card rd-card-body"><p><b>Chưa có vị trí phù hợp bộ lọc.</b> Thử bỏ bớt điều kiện lọc<?php echo $f['con-han'] ? ' hoặc bỏ chọn “Chỉ vị trí còn hạn nộp”' : ''; ?>.</p><p class="rd-actions"><a class="rd-btn" href="<?php echo esc_url( cvc_positions_url() ); ?>">Xem tất cả vị trí</a> <a class="rd-btn" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xem theo thông báo</a></p></div>
	<?php else : ?>
		<p class="rd-resline">Tìm thấy <b><?php echo esc_html( number_format( $total, 0, ',', '.' ) ); ?></b> vị trí<?php echo $last > 1 ? ' · trang ' . (int) $paged . '/' . (int) $last : ''; ?>. Vị trí còn hạn nộp được xếp trước, hạn gần nhất lên đầu.</p>
		<div class="rd-plist">
			<?php foreach ( $items as $it ) : ?>
				<?php
				$r   = (array) ( $it['recruitment'] ?? array() );
				$st  = (array) ( $r['status'] ?? array() );
				$cls = ! empty( $st['is_open'] ) ? ( empty( $st['deadline'] ) ? 'is-gold' : ( isset( $st['days_left'] ) && (int) $st['days_left'] <= 7 ? 'is-warn' : 'is-ok' ) ) : 'is-off';
				$url = cvc_position_url( (string) $it['slug'] );
				?>
				<article class="rd-pcard">
					<p class="rd-tags">
						<span class="rd-tag <?php echo esc_attr( $cls ); ?>"><?php echo esc_html( (string) ( $st['label'] ?? ( ! empty( $st['is_open'] ) ? 'Đang nhận hồ sơ' : 'Đã hết hạn' ) ) ); ?></span>
						<?php if ( ! empty( $it['employment_type_label'] ) ) : ?><span class="rd-tag"><?php echo esc_html( (string) $it['employment_type_label'] ); ?></span><?php endif; ?>
						<?php if ( ! empty( $r['job_family_label'] ) ) : ?><span class="rd-tag"><?php echo esc_html( (string) $r['job_family_label'] ); ?></span><?php endif; ?>
						<?php if ( empty( $r['source_official'] ) ) : ?><span class="rd-tag is-warn">Nguồn sưu tầm</span><?php endif; ?>
					</p>
					<h2><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $it['name'] ); ?></a><?php echo ! empty( $it['code'] ) ? ' <small>' . esc_html( (string) $it['code'] ) . '</small>' : ''; ?></h2>
					<p class="rd-pcard-org"><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $r['agency'] ?? '' ), (string) ( $r['province'] ?? '' ) ) ) ) ); ?></p>
					<dl class="rd-pcard-facts">
						<div><dt>Chỉ tiêu</dt><dd><?php echo ! empty( $it['quantity'] ) ? (int) $it['quantity'] : 'Xem văn bản gốc'; ?></dd></div>
						<div><dt>Hạn nộp</dt><dd><?php echo ! empty( $st['deadline'] ) ? esc_html( cvc_format_date_vn( (string) $st['deadline'] ) ) : 'Chưa rõ hạn nộp'; ?></dd></div>
						<div class="is-wide"><dt>Trình độ, chuyên ngành</dt><dd><?php echo esc_html( implode( ' · ', array_filter( array( ! empty( $it['education_level'] ) ? cvc_rd_clip( (string) $it['education_level'], 60 ) : '', ! empty( $it['major_requirements'] ) ? cvc_rd_clip( (string) $it['major_requirements'], 120 ) : '' ) ) ) ?: 'Theo thông báo gốc' ); ?></dd></div>
					</dl>
					<p class="rd-pcard-foot">
						<a class="rd-pcard-parent" href="<?php echo esc_url( cvc_recruitment_url( (string) ( $r['slug'] ?? '' ) ) ); ?>">Thông báo: <?php echo esc_html( cvc_rd_clip( (string) ( $r['title'] ?? '' ), 90 ) ); ?></a>
						<a class="rd-btn is-sm" href="<?php echo esc_url( $url ); ?>">Xem vị trí →</a>
					</p>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( $last > 1 ) : ?>
			<nav class="rd-pager" aria-label="Phân trang">
				<?php if ( $paged > 1 ) : ?><a class="rd-btn" href="<?php echo esc_url( $page_url( $paged - 1 ) ); ?>" rel="prev">← Trước</a><?php endif; ?>
				<?php
				$from = max( 1, $paged - 2 );
				$to   = min( $last, $paged + 2 );
				for ( $n = $from; $n <= $to; $n++ ) :
					?>
					<a class="rd-btn<?php echo $n === $paged ? ' is-cur' : ''; ?>" href="<?php echo esc_url( $page_url( $n ) ); ?>"<?php echo $n === $paged ? ' aria-current="page"' : ''; ?>><?php echo (int) $n; ?></a>
				<?php endfor; ?>
				<?php if ( $paged < $last ) : ?><a class="rd-btn" href="<?php echo esc_url( $page_url( $paged + 1 ) ); ?>" rel="next">Sau →</a><?php endif; ?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>

	<p class="rd-muted rd-small rd-hubnote">ⓘ Dữ liệu vị trí trích từ thông báo và biểu chỉ tiêu của cơ quan tuyển dụng. Luôn đối chiếu văn bản gốc trước khi nộp hồ sơ. <a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xem theo thông báo tuyển dụng →</a></p>
</div>
</main>
<?php
get_footer();
