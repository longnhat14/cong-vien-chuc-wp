<?php
/**
 * /bao-cao-tuyen-dung/ và /bao-cao-tuyen-dung/{YYYY-MM}/ - Báo cáo tuyển dụng hằng tháng từ dữ liệu thật (12/11/2026).
 * Dữ liệu: GET /api/reports/recruitment-month (backend PublicReports::monthly). Mặc định: tháng gần nhất đã hết.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rm_ym  = (string) get_query_var( 'cvc_report_month' );
$rm_res = ( new CVC_Api_Client( null, 20 ) )->get( '/api/reports/recruitment-month', array_filter( array( 'thang' => preg_match( '/^\d{4}-\d{2}$/', $rm_ym ) ? $rm_ym : null ) ) );
$d      = $rm_res['ok'] ? (array) ( $rm_res['data']['data'] ?? array() ) : array();
$n      = static fn ( $v ): string => number_format( (float) $v, 0, ',', '.' );

if ( ! $d ) {
	status_header( 404 );
	cvc_seo_set_noindex();
}
$rm_title = $d ? 'Báo cáo tuyển dụng ' . mb_strtolower( (string) $d['label'] ) . ': ' . $n( $d['notices'] ) . ' thông báo, ' . $n( $d['quota'] ) . ' chỉ tiêu' : 'Báo cáo tuyển dụng hằng tháng';
cvc_seo_set_title( $rm_title . ' | Công Viên Chức' );
if ( $d ) {
	cvc_seo_set_description( 'Số liệu tuyển dụng công chức, viên chức ' . mb_strtolower( (string) $d['label'] ) . ': ' . $n( $d['notices'] ) . ' thông báo tại ' . (int) $d['provinces'] . ' tỉnh, thành với ' . $n( $d['quota'] ) . ' chỉ tiêu; theo loại, tỉnh, ngành và cơ quan tuyển nhiều nhất.' );
	cvc_seo_set_canonical( home_url( '/bao-cao-tuyen-dung/' . $d['month'] . '/' ) );
	cvc_seo_add_json_ld(
		array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => $rm_title,
			'datePublished' => $d['month'] . '-01',
			'dateModified'  => wp_date( 'Y-m-d' ),
			'author'        => array( '@type' => 'Organization', 'name' => 'Công Viên Chức', 'url' => home_url( '/' ) ),
			'publisher'     => array( '@type' => 'Organization', 'name' => 'Công Viên Chức' ),
			'about'         => 'Tuyển dụng công chức, viên chức ' . $d['label'],
		)
	);
}
$rm_bar = static function ( array $rows, string $label_key, string $val_key = 'notices', int $max_rows = 12 ) use ( $n ): void {
	$max = max( 1, max( array_map( static fn ( $r ) => (int) $r[ $val_key ], $rows ?: array( array( $val_key => 1 ) ) ) ) );
	echo '<ol class="rm-bars">';
	foreach ( array_slice( $rows, 0, $max_rows ) as $r ) {
		$w = (int) round( (int) $r[ $val_key ] / $max * 100 );
		echo '<li><span class="rm-bar-l">' . esc_html( (string) $r[ $label_key ] ) . '</span><span class="rm-bar-t"><i style="width:' . (int) $w . '%"></i></span><b>' . esc_html( $n( $r['notices'] ) ) . ' tin · ' . esc_html( $n( $r['quota'] ) ) . ' chỉ tiêu</b></li>';
	}
	echo '</ol>';
};

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5 rm">
	<?php cvc_reports_css(); ?>
	<?php if ( ! $d ) : ?>
		<?php cvc_render_error_state( 'Chưa có báo cáo cho tháng này.' ); ?>
	<?php else : ?>
		<header class="rm-head">
			<p class="rm-kicker">Báo cáo tuyển dụng hằng tháng · dữ liệu Công Viên Chức</p>
			<h1><?php echo esc_html( 'Tuyển dụng công chức, viên chức ' . mb_strtolower( (string) $d['label'] ) ); ?></h1>
			<p><?php echo esc_html( $d['partial'] ? 'Tháng đang diễn ra - số liệu tính đến ngày ' . $d['as_of'] . ', tự cập nhật mỗi 30 phút.' : 'Số liệu cả tháng (đến ' . $d['as_of'] . ').' ); ?></p>
		</header>
		<nav class="dz-chips" aria-label="Chọn tháng">
			<?php foreach ( (array) $d['months'] as $m ) : ?>
				<a class="<?php echo $m === $d['month'] ? 'is-active font-bold' : ''; ?>" href="<?php echo esc_url( home_url( '/bao-cao-tuyen-dung/' . $m . '/' ) ); ?>"<?php echo $m === $d['month'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( 'Tháng ' . (int) substr( $m, 5 ) . '/' . substr( $m, 0, 4 ) ); ?></a>
			<?php endforeach; ?>
		</nav>
		<section class="rm-kpis" aria-label="Số liệu chính">
			<div><small>Thông báo tuyển dụng</small><b><?php echo esc_html( $n( $d['notices'] ) ); ?></b><?php if ( ! empty( $d['prev'] ) ) : ?><em><?php echo esc_html( 'tháng trước ' . $n( $d['prev']['notices'] ) ); ?></em><?php endif; ?></div>
			<div><small>Tổng chỉ tiêu</small><b><?php echo esc_html( $n( $d['quota'] ) ); ?></b><?php if ( ! empty( $d['prev'] ) ) : ?><em><?php echo esc_html( 'tháng trước ' . $n( $d['prev']['quota'] ) ); ?></em><?php endif; ?></div>
			<div><small>Tỉnh, thành có tin</small><b><?php echo (int) $d['provinces']; ?></b><em>trên 34 tỉnh, thành</em></div>
			<?php if ( null !== $d['median_window_days'] ) : ?><div><small>Thời gian nhận hồ sơ (trung vị)</small><b><?php echo (int) $d['median_window_days']; ?> ngày</b><em>từ ngày thông báo tới hạn nộp</em></div><?php endif; ?>
		</section>
		<div class="rm-grid">
			<section class="rm-card"><h2>Theo loại tuyển dụng</h2><?php $rm_bar( (array) $d['by_type'], 'label' ); ?></section>
			<section class="rm-card"><h2>Theo nhóm ngành</h2><?php $rm_bar( (array) $d['by_family'], 'label' ); ?></section>
		</div>
		<section class="rm-card"><h2>Tỉnh, thành có nhiều thông báo nhất</h2>
			<?php $rm_bar( (array) $d['by_province'], 'name' ); ?>
			<p class="rm-note">Xem tin đang mở theo tỉnh tại <a href="<?php echo esc_url( home_url( '/tuyen-dung-cong-chuc/' ) ); ?>">tuyển dụng công chức theo tỉnh</a> và <a href="<?php echo esc_url( home_url( '/tuyen-dung-vien-chuc/' ) ); ?>">viên chức theo tỉnh</a>.</p>
		</section>
		<div class="rm-grid">
			<section class="rm-card"><h2>Cơ quan, đơn vị tuyển nhiều chỉ tiêu nhất</h2>
				<div class="dz-tablewrap"><table class="dz-table"><thead><tr><th>Cơ quan, đơn vị</th><th class="text-right">Tin</th><th class="text-right">Chỉ tiêu</th></tr></thead><tbody>
				<?php foreach ( (array) $d['agencies'] as $a ) : ?><tr><td><?php echo esc_html( (string) $a['name'] ); ?></td><td class="text-right"><?php echo (int) $a['notices']; ?></td><td class="text-right"><?php echo esc_html( $n( $a['quota'] ) ); ?></td></tr><?php endforeach; ?>
				</tbody></table></div>
			</section>
			<section class="rm-card"><h2>Vị trí có nhiều chỉ tiêu nhất</h2>
				<div class="dz-tablewrap"><table class="dz-table"><thead><tr><th>Vị trí</th><th class="text-right">Thông báo</th><th class="text-right">Chỉ tiêu</th></tr></thead><tbody>
				<?php foreach ( (array) $d['top_positions'] as $p ) : ?><tr><td><?php echo esc_html( (string) $p['name'] ); ?></td><td class="text-right"><?php echo (int) $p['count']; ?></td><td class="text-right"><?php echo esc_html( $n( $p['quota'] ) ); ?></td></tr><?php endforeach; ?>
				</tbody></table></div>
			</section>
		</div>
		<section class="rm-card"><h2>Thông báo mới trong tháng</h2>
			<ul class="rm-list">
				<?php foreach ( (array) $d['latest'] as $r ) : ?>
					<li><a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( (string) $r['title'] ); ?></a><small><?php echo esc_html( ( $r['quota'] ? $n( $r['quota'] ) . ' chỉ tiêu' : 'Xem thông báo' ) . ( ! empty( $r['deadline'] ) ? ' · hạn ' . wp_date( 'd/m/Y', strtotime( (string) $r['deadline'] ) ) : '' ) ); ?></small></li>
				<?php endforeach; ?>
			</ul>
			<a class="dz-btn dz-btn-primary" href="<?php echo esc_url( home_url( '/tuyen-dung/' ) ); ?>">Xem toàn bộ tin đang tuyển</a>
		</section>
		<details class="dz-box"><summary class="dz-box-head cursor-pointer">Cách tính số liệu</summary><div class="dz-box-body text-sm space-y-1">
			<p><?php echo esc_html( (string) $d['method'] ); ?></p>
			<?php if ( ! empty( $d['other_notices'] ) ) : ?><p><?php echo esc_html( 'Không tính ' . $n( $d['other_notices'] ) . ' tin doanh nghiệp / khác trong tháng.' ); ?></p><?php endif; ?>
			<p>Được trích dẫn số liệu này, vui lòng ghi nguồn "Công Viên Chức (congvienchuc.com)" kèm đường dẫn trang.</p>
		</div></details>
		<?php if ( function_exists( 'cvc_render_blog_related' ) ) { cvc_render_blog_related( 'tuyển dụng công chức viên chức thông báo chỉ tiêu ' . $d['label'], 'Bài hướng dẫn liên quan', 4 ); } ?>
	<?php endif; ?>
</div>
</main>
<?php
get_footer();
