<?php
/**
 * Trang chủ đề tuyển dụng theo loại (28/10/2026): /tuyen-cong-chuc/, /tuyen-vien-chuc/, /tuyen-giao-vien/,
 * /tuyen-dung-y-te/, /tuyen-dung/hop-dong/ (cvc_rec_presets). Được nạp từ template-recruitments.php khi có preset.
 *
 * Thứ tự: phần đầu gọn (số liệu thật của đúng loại tin) → sắp hết hạn → danh sách tin (mới đăng lên đầu)
 * + cột phải (đề riêng theo tin, văn bản cần nắm) → loại tin khác. Lọc bằng GET thường (tinh, nhom, q),
 * không có khung ghép nối / lộ trình / nhắc việc của trang /tuyen-dung/ tổng.
 *
 * Biến có sẵn: $paged, $preset_key, $preset, $f, $token, $logged_in.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 03/11/2026: trang theo tỉnh /tuyen-dung-cong-chuc/{tinh}/ - tỉnh lấy từ URL (không dùng ?tinh=).
$rh_short = (string) get_query_var( 'cvc_rec_tinh' );
$rh_geo   = '' !== $rh_short ? cvc_rec_geo_current() : null;
if ( '' !== $rh_short && ! $rh_geo ) {
	status_header( 404 );
	nocache_headers();
	cvc_seo_set_noindex();
	cvc_seo_set_title( 'Không tìm thấy tỉnh, thành phố' );
	get_header();
	echo '<main id="main" class="dz-main ci rh"><div class="ci-wrap rh-body"><div class="ci-empty"><b>Không tìm thấy tỉnh, thành phố này</b><a class="ci-btn" href="' . esc_url( cvc_rec_preset_url( $preset_key ) ) . '">' . esc_html( $preset['h1'] ) . ' toàn quốc</a></div>';
	cvc_rec_geo_render_links( $preset_key );
	echo '</div></main>';
	get_footer();
	return;
}
if ( $rh_geo ) {
	$f['tinh'] = (int) $rh_geo['id'];
}

$rh_fam_fixed = isset( $preset['filter']['nhom'] );
$rh_args      = array_filter(
	array(
		'q'    => $f['q'],
		'tinh' => ! $rh_geo && $f['tinh'] ? $f['tinh'] : null,
		'nhom' => ! $rh_fam_fixed && '' !== $f['nhom'] ? $f['nhom'] : null,
	)
);
$rh_url = $rh_geo
	? static fn ( int $p = 1, array $a = array() ) => cvc_rec_geo_url( $preset_key, $rh_geo['short'], $p, $a )
	: static fn ( int $p = 1, array $a = array() ) => cvc_rec_preset_url( $preset_key, $p, $a );
$rh_hub_url = cvc_rec_preset_url( $preset_key );
$rh_tinh_url = static function ( int $id ) use ( $preset_key, $rh_hub_url ): string {
	$g = ! empty( cvc_rec_presets()[ $preset_key ]['geo'] ) ? cvc_rec_geo_by_id( $preset_key, $id ) : null;
	return $g ? cvc_rec_geo_url( $preset_key, $g['short'] ) : add_query_arg( 'tinh', $id, $rh_hub_url );
};

$hub_res = cvc_ci_api( '/api/career/hub', $preset['api'] + array( 'limit' => 8 ) + ( $rh_geo ? array( 'province_id' => (int) $rh_geo['id'] ) : array() ), null, 10, 600 );
$hub     = $hub_res['ok'] ? (array) ( $hub_res['data']['data'] ?? array() ) : array();
$hc      = (array) ( $hub['counts'] ?? array() );
$h_provs = array_values( array_filter( (array) ( $hub['provinces'] ?? array() ), 'is_array' ) );
$h_fams  = array_values( array_filter( (array) ( $hub['families'] ?? array() ), 'is_array' ) );
$h_docs  = array_values( array_filter( (array) ( $hub['documents'] ?? array() ), 'is_array' ) );

$jobs_q   = cvc_ci_jobs_query( $f, $paged );
$jobs_res = cvc_ci_api( '/api/career/recruitments', $jobs_q, $token, 15, $token ? 0 : 120 );
$jobs     = $jobs_res['ok'] ? (array) ( $jobs_res['data']['data'] ?? array() ) : array();
$items    = (array) ( $jobs['items'] ?? array() );
$pg       = (array) ( $jobs['pagination'] ?? array() );
$total    = (int) ( $pg['total'] ?? 0 );

$filtered = '' !== $f['q'] || ( ! $rh_geo && $f['tinh'] ) || ( ! $rh_fam_fixed && '' !== $f['nhom'] );
$soon     = array();
if ( 1 === $paged && '' === $f['q'] ) {
	$soon_res = cvc_ci_api( '/api/career/recruitments', array_merge( $jobs_q, array( 'status' => 'closing_soon', 'sort' => 'deadline', 'page' => 1, 'per_page' => 6, 'with_exams' => 0 ) ), null, 10, 300 );
	$soon     = $soon_res['ok'] ? (array) ( $soon_res['data']['data']['items'] ?? array() ) : array();
}

$rp_res   = cvc_ci_api( '/api/catalog/recruitment-exams', array_filter( array( 'province_id' => $f['tinh'] ?: null, 'page' => 1, 'per_page' => 4 ) + $preset['rp'] ), $token, 12, $token ? 0 : 300 );
$rp       = $rp_res['ok'] ? (array) ( $rp_res['data']['data'] ?? array() ) : array();
$rp_items = (array) ( $rp['items'] ?? array() );
$rp_total = (int) ( $rp['pagination']['total'] ?? 0 );
$rp_all   = $rp_total; // Ô số liệu ở phần đầu luôn tính trên toàn quốc như các ô khác.
if ( $f['tinh'] ) {
	$rp_all_res = cvc_ci_api( '/api/catalog/recruitment-exams', array( 'page' => 1, 'per_page' => 1 ) + $preset['rp'], null, 12, 300 );
	$rp_all     = $rp_all_res['ok'] ? (int) ( $rp_all_res['data']['data']['pagination']['total'] ?? 0 ) : 0;
}
$track    = (string) ( $preset['track'] ?? '' );
$track_url = '' !== $track && function_exists( 'cvc_cat_track_url' ) ? cvc_cat_track_url( $track ) : ( function_exists( 'cvc_exams_mode_url' ) ? cvc_exams_mode_url( 'on-tap' ) : home_url( '/thi-trac-nghiem/' ) );
$track_lbl = '' !== $track && function_exists( 'cvc_cat_tracks' ) ? (string) ( cvc_cat_tracks()[ $track ]['label'] ?? 'Ôn thi' ) : 'Luyện thi trắc nghiệm';

$province_name = '';
foreach ( $h_provs as $p ) {
	if ( (int) $p['id'] === (int) $f['tinh'] ) {
		$province_name = (string) $p['name'];
	}
}
$fam_label = ! $rh_fam_fixed && '' !== $f['nhom'] ? (string) ( cvc_ci_families()[ $f['nhom'] ] ?? '' ) : '';
$h1        = $preset['h1'] . ( '' !== $fam_label ? ' · ' . mb_strtolower( $fam_label, 'UTF-8' ) : '' ) . ( '' !== $province_name ? ' tại ' . $province_name : '' );
$rh_lead   = (string) $preset['lead'];
$rh_title  = (string) $preset['title'];
$rh_desc   = (string) $preset['desc'];
if ( $rh_geo ) {
	// Trang tỉnh: tiêu đề / mô tả / đoạn mở đầu dựng từ mẫu + số liệu thật, năm tự đổi.
	$rh_y     = cvc_rec_year();
	$rh_open  = (int) ( $hc['open_recruitments'] ?? $rh_geo['open_count'] ?? 0 );
	$h1       = $preset['h1'] . ' ' . $rh_geo['label'] . ' ' . $rh_y . ( '' !== $fam_label ? ' · ' . mb_strtolower( $fam_label, 'UTF-8' ) : '' );
	$rh_title = $preset['h1'] . ' ' . $rh_geo['label'] . ' ' . $rh_y . ': ' . ( $rh_open > 0 ? $rh_open . ' thông báo còn hạn nộp' : 'thông báo mới nhất' );
	$rh_desc  = sprintf( 'Thông báo %s tại %s năm %d từ nguồn chính thức%s: hạn nộp, chỉ tiêu, vị trí, đề trắc nghiệm ôn thi và văn bản cần nắm.', mb_strtolower( $preset['h1'], 'UTF-8' ), $rh_geo['label'], $rh_y, $rh_open > 0 ? ', ' . $rh_open . ' tin còn hạn' : '' );
	$rh_lead  = cvc_rec_geo_lead( $preset, $rh_geo, $rh_open, (int) ( $hc['open_positions'] ?? 0 ) );
	// 03/11/2026: hồ sơ cơ quan của tỉnh. Có tin còn hạn -> tin lên đầu, hồ sơ xuống dưới; chưa có tin -> hồ sơ lên trước danh sách.
	$rh_prof = cvc_rec_geo_profile( $preset_key, (int) $rh_geo['id'] );
	ob_start();
	cvc_rec_geo_render_profile( $preset_key, $preset, $rh_geo, $rh_prof, $rh_open, $logged_in );
	$rh_prof_html = (string) ob_get_clean();
	$rh_prof_top  = 0 === $rh_open && 1 === $paged && ! $filtered;
}

// SEO: URL sạch là canonical; có lọc / tìm / trang ≥ 2 thì noindex (tránh trang mỏng trùng lặp).
// Trang tỉnh chưa đủ dữ liệu (không có tin còn hạn và < 3 tin / 12 tháng) cũng noindex, follow.
cvc_seo_set_title( $filtered ? $h1 : $rh_title );
cvc_seo_set_description( $rh_desc );
cvc_seo_set_canonical( $rh_url() );
if ( $filtered || $paged > 1 || ( $rh_geo && empty( $rh_geo['indexable'] ) ) ) {
	cvc_seo_set_noindex();
}

// 03/11/2026: trang tỉnh giao diện v2 (template-rec-geo.php, theo mẫu anh Nhật). Bật cho mọi người bằng tùy chọn
// cvc_rec_geo_v2 = 1; trước đó xem trước bằng ?giao-dien=moi (noindex). ?giao-dien=cu luôn về giao diện cũ.
if ( $rh_geo && cvc_rec_geo_v2_active() ) {
	if ( isset( $_GET['giao-dien'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		cvc_seo_set_noindex();
	}
	require get_theme_file_path( 'template-rec-geo.php' );
	return;
}

$today_vn = strtotime( wp_date( 'Y-m-d' ) );
$days_left = static function ( ?string $d ) use ( $today_vn ): ?int {
	$t = $d ? strtotime( substr( $d, 0, 10 ) ) : false;
	return false === $t ? null : (int) round( ( $t - $today_vn ) / DAY_IN_SECONDS );
};

get_header();
?>
<main id="main" class="dz-main ci rh" data-preset="<?php echo esc_attr( $preset_key ); ?>">
	<section class="rh-hero">
		<div class="ci-wrap">
			<?php cvc_render_breadcrumbs( array_merge( array( array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ), array( 'label' => 'Tuyển dụng', 'url' => cvc_ci_url() ), array( 'label' => $preset['h1'], 'url' => $rh_hub_url ) ), $rh_geo ? array( array( 'label' => $rh_geo['label'] ) ) : array() ) ); ?>
			<div class="rh-hero-grid">
				<div>
					<p class="rh-eyebrow"><i class="ci-pulse" aria-hidden="true"></i> Còn hạn nộp hồ sơ · nguồn chính thức</p>
					<h1><?php echo esc_html( $h1 ); ?></h1>
					<p class="rh-lead"><?php echo esc_html( $rh_lead ); ?></p>
					<form class="rh-search" method="get" action="<?php echo esc_url( $rh_url() ); ?>" role="search">
						<label class="ec-sr" for="rh-q">Tìm trong <?php echo esc_html( mb_strtolower( $preset['label'], 'UTF-8' ) ); ?></label>
						<input id="rh-q" name="q" value="<?php echo esc_attr( $f['q'] ); ?>" maxlength="100" placeholder="<?php echo esc_attr( 'Tìm đơn vị, vị trí, số thông báo…' ); ?>">
						<?php if ( $h_provs && ! $rh_geo ) : ?>
							<label class="ec-sr" for="rh-tinh">Tỉnh, thành phố</label>
							<select id="rh-tinh" name="tinh">
								<option value="">Tất cả tỉnh, thành</option>
								<?php foreach ( $h_provs as $p ) : ?><option value="<?php echo (int) $p['id']; ?>"<?php selected( (int) $f['tinh'], (int) $p['id'] ); ?>><?php echo esc_html( $p['name'] . ' (' . (int) $p['open_count'] . ')' ); ?></option><?php endforeach; ?>
							</select>
						<?php endif; ?>
						<?php if ( ! $rh_fam_fixed && '' !== $f['nhom'] ) : ?><input type="hidden" name="nhom" value="<?php echo esc_attr( $f['nhom'] ); ?>"><?php endif; ?>
						<button class="ci-btn is-primary" type="submit">Tìm tin</button>
					</form>
				</div>
				<dl class="rh-stats" aria-label="Số liệu <?php echo esc_attr( $preset['noun'] . ( $rh_geo ? ' tại ' . $rh_geo['label'] : '' ) ); ?>">
					<div><dt>Tin đang nhận hồ sơ</dt><dd><?php echo isset( $hc['open_recruitments'] ) ? esc_html( number_format_i18n( (int) $hc['open_recruitments'] ) ) : '—'; ?></dd></div>
					<div><dt>Vị trí đang tuyển</dt><dd><?php echo isset( $hc['open_positions'] ) ? esc_html( number_format_i18n( (int) $hc['open_positions'] ) ) : '—'; ?></dd></div>
					<div class="is-warn"><dt>Hết hạn trong 7 ngày</dt><dd><?php echo isset( $hc['closing_soon'] ) ? ( (int) $hc['closing_soon'] > 0 && $soon ? '<a href="#sap-het-han">' . (int) $hc['closing_soon'] . '</a>' : (int) $hc['closing_soon'] ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
					<div><dt>Tin có đề riêng</dt><dd><?php echo esc_html( number_format_i18n( $rp_all ) ); ?></dd></div>
				</dl>
			</div>
			<nav class="rh-chips" aria-label="Lọc nhanh">
				<?php if ( ! $rh_fam_fixed && count( $h_fams ) > 1 ) : ?>
					<div class="rh-chip-row"><span class="rh-chips-h">Ngành</span>
					<a class="rh-chip<?php echo '' === $f['nhom'] ? ' is-on' : ''; ?>" href="<?php echo esc_url( $rh_url( 1, array_diff_key( $rh_args, array( 'nhom' => 1 ) ) ) ); ?>">Tất cả</a>
					<?php foreach ( array_slice( $h_fams, 0, 6 ) as $fm ) : ?>
						<?php $on = $f['nhom'] === (string) $fm['code']; ?>
						<a class="rh-chip<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo esc_url( $rh_url( 1, array( 'nhom' => $on ? null : (string) $fm['code'] ) + $rh_args ) ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $fm['label'] ); ?> <small><?php echo (int) $fm['open_count']; ?></small></a>
					<?php endforeach; ?></div>
				<?php endif; ?>
				<?php if ( $h_provs && ! $rh_geo ) : ?>
					<div class="rh-chip-row"><span class="rh-chips-h">Nhiều tin</span>
					<?php foreach ( array_slice( $h_provs, 0, 6 ) as $p ) : ?>
						<?php $on = (int) $f['tinh'] === (int) $p['id']; ?>
						<a class="rh-chip<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo esc_url( $on ? $rh_url( 1, array( 'tinh' => null ) + $rh_args ) : $rh_tinh_url( (int) $p['id'] ) ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo esc_html( (string) $p['name'] ); ?> <small><?php echo (int) $p['open_count']; ?></small></a>
					<?php endforeach; ?>
					<?php if ( $filtered ) : ?><a class="rh-chip is-clear" href="<?php echo esc_url( $rh_url() ); ?>">↺ Bỏ lọc</a><?php endif; ?></div>
				<?php elseif ( $rh_geo ) : ?>
					<div class="rh-chip-row"><span class="rh-chips-h">Khu vực</span>
					<a class="rh-chip" href="<?php echo esc_url( $rh_hub_url ); ?>">← Toàn quốc</a>
					<?php foreach ( cvc_rec_presets() as $pk => $pv ) : ?>
						<?php if ( $pk !== $preset_key && ! empty( $pv['geo'] ) ) : ?><a class="rh-chip" href="<?php echo esc_url( cvc_rec_geo_url( $pk, $rh_geo['short'] ) ); ?>"><?php echo esc_html( $pv['h1'] . ' ' . $rh_geo['label'] ); ?></a><?php endif; ?>
					<?php endforeach; ?>
					<?php if ( $filtered ) : ?><a class="rh-chip is-clear" href="<?php echo esc_url( $rh_url() ); ?>">↺ Bỏ lọc</a><?php endif; ?></div>
				<?php elseif ( $filtered ) : ?>
					<div class="rh-chip-row"><a class="rh-chip is-clear" href="<?php echo esc_url( $rh_url() ); ?>">↺ Bỏ lọc</a></div>
				<?php endif; ?>
			</nav>
		</div>
	</section>

	<div class="ci-wrap rh-body">
		<?php if ( $soon ) : ?>
			<section class="rh-soon" id="sap-het-han" aria-labelledby="rh-soon-h">
				<div class="rh-sec-head"><h2 id="rh-soon-h">Sắp hết hạn nộp hồ sơ</h2><span>Trong 7 ngày tới<?php echo '' !== $province_name ? ' · ' . esc_html( $province_name ) : ''; ?></span></div>
				<div class="rh-soon-row">
					<?php foreach ( $soon as $r ) : ?>
						<?php
						$r  = (array) $r;
						$dl = $days_left( (string) ( $r['status']['deadline'] ?? '' ) );
						?>
						<a class="rh-soon-card" href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>">
							<span class="rh-days<?php echo null !== $dl && $dl <= 2 ? ' is-hot' : ''; ?>"><?php echo null === $dl ? '—' : ( $dl <= 0 ? 'Hôm nay' : 'Còn ' . (int) $dl . ' ngày' ); ?></span>
							<b><?php echo esc_html( wp_trim_words( (string) $r['title'], 18 ) ); ?></b>
							<small><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $r['province']['name'] ?? '' ), ! empty( $r['status']['deadline'] ) ? 'Hạn ' . cvc_ci_date( (string) $r['status']['deadline'] ) : '' ) ) ) ); ?></small>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $rh_prof_top ) && ! empty( $rh_prof_html ) ) { echo $rh_prof_html; } // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="rh-grid">
			<section class="rh-list" aria-labelledby="rh-list-h">
				<div class="rh-sec-head">
					<h2 id="rh-list-h"><?php echo esc_html( $preset['label'] ); ?> còn hạn</h2>
					<span><?php echo esc_html( number_format_i18n( $total ) . ' tin · mới đăng lên đầu' . ( '' !== $f['q'] ? ' · khớp “' . $f['q'] . '”' : '' ) ); ?></span>
				</div>
				<?php if ( ! $jobs_res['ok'] ) : ?>
					<div class="ci-empty is-error"><b>Không tải được danh sách tin</b>Hệ thống đang bận, vui lòng tải lại trang sau ít phút.</div>
				<?php elseif ( ! $items ) : ?>
					<div class="ci-empty"><b><?php echo $rh_geo && ! $filtered ? esc_html( 'Chưa có thông báo ' . mb_strtolower( $preset['h1'], 'UTF-8' ) . ' tại ' . $rh_geo['label'] . ' còn hạn' ) : 'Chưa có tin còn hạn khớp bộ lọc'; ?></b><a class="ci-btn" href="<?php echo esc_url( $rh_url() ); ?>">↺ Bỏ lọc</a> <a class="ci-btn" href="<?php echo esc_url( cvc_ci_url( array( 'trang-thai' => 'all' ) + $preset['filter'] ) ); ?>">Xem cả tin đã hết hạn</a></div>
				<?php else : ?>
					<div class="ci-job-list">
						<?php foreach ( $items as $r ) { cvc_ci_render_job_card( (array) $r, $logged_in, true ); } ?>
					</div>
					<?php
					$cur  = (int) ( $pg['page'] ?? 1 );
					$last = (int) ( $pg['last_page'] ?? 1 );
					if ( $last > 1 ) :
						?>
						<nav class="rh-pager" aria-label="Phân trang">
							<?php if ( $cur > 1 ) : ?><a class="ci-btn" rel="prev" href="<?php echo esc_url( $rh_url( $cur - 1, $rh_args ) ); ?>">← Trang trước</a><?php endif; ?>
							<?php for ( $i = max( 1, $cur - 2 ); $i <= min( $last, $cur + 2 ); $i++ ) : ?>
								<?php if ( $i === $cur ) : ?><span class="rh-pg is-cur" aria-current="page"><?php echo (int) $i; ?></span><?php else : ?><a class="rh-pg" href="<?php echo esc_url( $rh_url( $i, $rh_args ) ); ?>"><?php echo (int) $i; ?></a><?php endif; ?>
							<?php endfor; ?>
							<?php if ( $cur < $last ) : ?><a class="ci-btn" rel="next" href="<?php echo esc_url( $rh_url( $cur + 1, $rh_args ) ); ?>">Trang sau →</a><?php endif; ?>
						</nav>
					<?php endif; ?>
				<?php endif; ?>
			</section>

			<aside class="rh-side" aria-label="Ôn thi và văn bản">
				<section class="rh-box rh-exam" aria-labelledby="rh-exam-h">
					<p class="rh-box-eb">Ôn thi <?php echo esc_html( $preset['noun'] ); ?></p>
					<h2 id="rh-exam-h"><?php echo $rp_total > 0 ? esc_html( $rp_total . ' tin có đề riêng' ) : 'Đề trắc nghiệm để ôn'; ?></h2>
					<p class="rh-box-lead">Đề biên soạn theo căn cứ của từng thông báo và từng nhóm vị trí; luyện xong mỗi câu là thấy đáp án và căn cứ.</p>
					<?php if ( $rp_items ) : ?>
						<ul class="rh-rp">
							<?php foreach ( $rp_items as $r ) : ?>
								<?php $g = (array) ( ( (array) ( $r['groups'] ?? array() ) )[0] ?? array() ); ?>
								<li>
									<a class="rh-rp-t" href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $r['title'], 14 ) ); ?></a>
									<small><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $r['province']['name'] ?? '' ), (int) ( $r['exams_count'] ?? 0 ) ? (int) $r['exams_count'] . ' đề' : '' ) ) ) ); ?></small>
									<?php if ( ! empty( $g['exams'] ) ) : ?>
										<span class="rh-rp-ex"><em><?php echo esc_html( wp_trim_words( (string) $g['label'], 6 ) ); ?>:</em>
											<?php foreach ( array_slice( (array) $g['exams'], 0, 3 ) as $e ) : ?><a href="<?php echo esc_url( add_query_arg( 'che-do', 'on-tap', cvc_exam_url( (string) $e['slug'] ) ) ); ?>" title="<?php echo esc_attr( (int) $e['total_questions'] . ' câu · ' . (int) $e['duration_minutes'] . ' phút' ); ?>">Đề <?php echo (int) $e['n']; ?></a><?php endforeach; ?>
										</span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<a class="rh-box-cta" href="<?php echo esc_url( $track_url ); ?>"><?php echo esc_html( $track_lbl ); ?> →</a>
				</section>

				<?php if ( $h_docs ) : ?>
					<section class="rh-box" aria-labelledby="rh-doc-h">
						<p class="rh-box-eb">Văn bản cần nắm</p>
						<h2 id="rh-doc-h">Được các tin này dùng nhiều nhất</h2>
						<ol class="rh-docs">
							<?php foreach ( $h_docs as $d ) : ?>
								<?php
								$why = 'suggested' === ( $d['tier'] ?? '' ) ? 'Gợi ý kiến thức chung' : ( (int) ( $d['syllabus_count'] ?? 0 ) > 0 ? 'Trong danh mục ôn tập của ' . (int) $d['recruitments_count'] . ' tin' : 'Là căn cứ của ' . (int) $d['recruitments_count'] . ' tin' );
								?>
								<li class="<?php echo 'in_force' !== ( $d['effect'] ?? '' ) ? 'is-old' : ''; ?>">
									<a href="<?php echo esc_url( home_url( (string) $d['url'] ) ); ?>"><?php echo esc_html( (string) $d['title'] ); ?></a>
									<small><?php echo esc_html( $why . ( 'in_force' !== ( $d['effect'] ?? '' ) ? ' · ' . (string) ( $d['effect_label'] ?? '' ) : '' ) ); ?></small>
								</li>
							<?php endforeach; ?>
						</ol>
						<a class="rh-box-cta" href="<?php echo esc_url( home_url( '/van-ban-phap-luat/' ) ); ?>">Tra cứu văn bản pháp luật →</a>
					</section>
				<?php endif; ?>
			</aside>
		</div>

		<?php if ( empty( $rh_prof_top ) && ! empty( $rh_prof_html ) && 1 === $paged ) { echo $rh_prof_html; } // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( ! empty( $preset['geo'] ) ) { cvc_rec_geo_render_links( $preset_key, $rh_geo ? $rh_geo['short'] : '' ); } ?>

		<nav class="rh-more" aria-label="Loại tin khác">
			<span>Xem thêm:</span>
			<?php foreach ( cvc_rec_presets() as $pk => $pv ) : ?>
				<?php if ( $pk === $preset_key ) { continue; } ?>
				<a href="<?php echo esc_url( cvc_rec_preset_url( $pk ) ); ?>"><?php echo esc_html( $pv['h1'] ); ?></a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( cvc_ci_url() ); ?>">Tất cả tin tuyển dụng</a>
			<a href="<?php echo esc_url( cvc_positions_url() ); ?>">Tìm theo vị trí</a>
			<?php if ( function_exists( 'cvc_employer_landing_url' ) ) : ?><a class="is-emp" href="<?php echo esc_url( cvc_employer_landing_url() ); ?>">Đơn vị tuyển dụng: đăng tin miễn phí →</a><?php endif; ?>
		</nav>
	</div>
	<div class="ci-toast" id="ci-toast" role="status" aria-live="polite"></div>
</main>
<?php
get_footer();
