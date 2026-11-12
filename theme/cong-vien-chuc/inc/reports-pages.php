<?php
/**
 * NỘI DUNG ĐỘC QUYỀN TỪ DỮ LIỆU THẬT (12/11/2026, claude/ke-hoach-blog-index-cham.md - E1, E2, E3):
 *  - /bao-cao-tuyen-dung/[YYYY-MM/]  báo cáo tuyển dụng hằng tháng (template-report-month.php)
 *  - /cau-hay-sai-nhat/               câu hay sai nhất (template-hardest.php)
 *  - [cvc_khoi_song]                  "Cập nhật trực tiếp" ở đầu bài viết theo tỉnh / bộ ngành / nhóm ngành:
 *                                     tin đang nhận hồ sơ, chỉ tiêu, hạn sắp hết, văn bản mới của các đợt (lịch thi,
 *                                     danh sách dự thi, kết quả). Tự chèn vào bài có ngữ cảnh cụ thể; render phía máy chủ.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_reports_css(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	echo '<style>'
		. '.rm-head{padding:18px 20px;border-radius:14px;background:#123a63;color:#fff}.rm-head h1{margin:2px 0 6px;font-size:28px;line-height:1.25;color:#fff}.rm-head p{margin:0;color:#d5e1ee}.rm-head .rm-kicker{color:#e3c27a;font-weight:800;font-size:13px;letter-spacing:.04em;text-transform:uppercase}'
		. '.rm-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px}.rm-kpis>div{border:1px solid #dfe4ea;border-radius:12px;background:#fff;padding:12px 14px}.rm-kpis small{display:block;color:#5c5c5c;font-size:13px}.rm-kpis b{display:block;font-size:28px;color:#123a63;font-variant-numeric:tabular-nums}.rm-kpis em{font-style:normal;font-size:12.5px;color:#5c5c5c}'
		. '.rm-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}@media(max-width:860px){.rm-grid{grid-template-columns:1fr}}'
		. '.rm-card{border:1px solid #dfe4ea;border-radius:14px;background:#fff;padding:16px}.rm-card h2{margin:0 0 10px;font-size:19px;color:#123a63}'
		. '.rm-bars{list-style:none;margin:0;padding:0;display:grid;gap:8px}.rm-bars li{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(60px,1fr) auto;gap:10px;align-items:center;font-size:14px}.rm-bar-l{overflow-wrap:anywhere}.rm-bar-t{height:10px;border-radius:99px;background:#e6edf5;overflow:hidden}.rm-bar-t i{display:block;height:100%;background:#1767aa;border-radius:99px}.rm-bars b{font-weight:600;color:#14263b;white-space:nowrap;font-variant-numeric:tabular-nums}@media(max-width:560px){.rm-bars li{grid-template-columns:1fr auto}.rm-bar-t{grid-column:1/-1;order:3}}'
		. '.rm-note{font-size:13.5px;color:#5c5c5c;margin:10px 0 0}.rm-list{margin:0 0 12px;padding:0 0 0 18px;display:grid;gap:8px}.rm-list small{display:block;color:#5c5c5c}.rm-filter{display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:14px}.rm-filter select{min-height:38px;max-width:100%;border:1px solid #cfd6df;border-radius:8px;padding:4px 8px}'
		. '.hq-list{list-style:none;margin:0;padding:0;display:grid;gap:12px}.hq-meta{margin:0 0 6px;font-size:13px;color:#8a2f2f}.hq-q{font-weight:700;font-size:16px;line-height:1.5;margin:0 0 10px;color:#14263b}.hq-opts{list-style:none;margin:0;padding:0;display:grid;gap:6px}.hq-opts li{display:grid;grid-template-columns:28px minmax(0,1fr) 110px;gap:8px;align-items:center;padding:6px 8px;border-radius:8px;background:#f6f8fb}.hq-opts li.is-right{background:#eaf6f0}.hq-k{display:grid;place-items:center;width:26px;height:26px;border-radius:50%;background:#fff;font-weight:800}.hq-opts li.is-right .hq-k{background:#1f7a5c;color:#fff}.hq-t em{font-style:normal;color:#1f7a5c;font-weight:700}.hq-p{position:relative;height:22px;border-radius:6px;background:#e6edf5;overflow:hidden}.hq-p i{position:absolute;inset:0 auto 0 0;background:#9fb7d3}.hq-opts li.is-right .hq-p i{background:#7cc4a6}.hq-p b{position:relative;display:block;text-align:right;padding-right:6px;font-size:12.5px;line-height:22px}.hq-exp summary{cursor:pointer;font-weight:700;color:#1767aa;margin-top:8px}@media(max-width:560px){.hq-opts li{grid-template-columns:28px minmax(0,1fr)}.hq-p{grid-column:1/-1}}'
		. '</style>';
}

/* ------------------------------------------------------------------ Khối "Cập nhật trực tiếp" trong bài viết */

/** Bộ ngành / cơ quan nhận ra từ tiêu đề bài (từ khóa dùng để tìm trong tên đợt / cơ quan). */
function cvc_live_agency( string $text ): string {
	$t   = ' ' . cvc_pb_ascii( $text ) . ' ';
	$map = array( 'hai quan' => 'Hải quan', 'kho bac' => 'Kho bạc', 'bao hiem xa hoi' => 'Bảo hiểm xã hội', 'ngoai giao' => 'Ngoại giao', 'toa an' => 'Tòa án', 'vien kiem sat' => 'Viện kiểm sát', 'thong ke' => 'Thống kê', 'ngan hang nha nuoc' => 'Ngân hàng Nhà nước', 'kiem toan' => 'Kiểm toán', 'thue' => 'Thuế' );
	foreach ( $map as $a => $name ) {
		if ( preg_match( '/(?<![a-z])' . preg_quote( $a, '/' ) . '(?![a-z])/', $t ) ) {
			return $name;
		}
	}

	return '';
}

/** Ngữ cảnh "cụ thể" của bài: tỉnh / bộ ngành / (nhóm ngành + loại). Bài chung chung → null. */
function cvc_live_context( int $post_id ): ?array {
	if ( ! function_exists( 'cvc_pb_context' ) ) {
		return null;
	}
	$ctx    = cvc_pb_context( $post_id );
	$agency = cvc_live_agency( (string) get_the_title( $post_id ) . ' ' . (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) );
	if ( ! $ctx['province_id'] && '' === $agency && ! ( '' !== $ctx['family'] && '' !== $ctx['kind'] ) ) {
		return null;
	}

	return $ctx + array( 'agency' => $agency );
}

function cvc_live_block( $atts = array() ): string {
	$id  = (int) get_the_ID();
	$ctx = $id ? cvc_live_context( $id ) : null;
	if ( ! $ctx || ! function_exists( 'cvc_pb_api' ) ) {
		return '';
	}
	$type = array( 'civil' => 'civil_servant', 'public' => 'public_employee' )[ $ctx['kind'] ] ?? null;
	$d    = cvc_pb_api( '/api/reports/live', array_filter( array( 'tinh' => $ctx['province_id'] ?: null, 'nhom' => $ctx['family'] ?: null, 'loai' => $type, 'co_quan' => $ctx['agency'] ?: null ) ) );
	if ( ! $d || ( empty( $d['open'] ) && empty( $d['events'] ) ) ) {
		return '';
	}
	$scope = trim( implode( ' ', array_filter( array( $ctx['agency'] ? 'ngành ' . $ctx['agency'] : '', $ctx['province'] ? 'tại ' . $ctx['province'] : '' ) ) ) );
	$list  = function_exists( 'cvc_pb_recruitment_list_url' ) ? cvc_pb_recruitment_list_url( $ctx['kind'], $ctx['family'], (int) $ctx['province_id'] ) : home_url( '/tuyen-dung/' );
	$h     = '<div class="cvc-live" role="region" aria-label="Cập nhật trực tiếp">';
	$h    .= '<p class="cvc-live-h"><span class="cvc-live-dot" aria-hidden="true"></span>Cập nhật trực tiếp' . ( '' !== $scope ? ' ' . esc_html( $scope ) : '' ) . ' <small>' . esc_html( (string) $d['as_of'] ) . '</small></p>';
	$h    .= '<ul class="cvc-live-kpi"><li><b>' . (int) $d['open'] . '</b> tin đang nhận hồ sơ</li><li><b>' . esc_html( number_format( (int) $d['open_quota'], 0, ',', '.' ) ) . '</b> chỉ tiêu đang mở</li><li><b>' . (int) $d['new_7d'] . '</b> tin mới 7 ngày</li></ul>';
	if ( ! empty( $d['closing'] ) ) {
		$h .= '<p class="cvc-live-sub">Sắp hết hạn nộp</p><ul class="cvc-live-list">';
		foreach ( array_slice( (array) $d['closing'], 0, 3 ) as $r ) {
			$h .= '<li><a href="' . esc_url( cvc_recruitment_url( (string) $r['slug'] ) ) . '">' . esc_html( wp_trim_words( (string) $r['title'], 16 ) ) . '</a> <small>hạn ' . esc_html( wp_date( 'd/m', strtotime( (string) $r['deadline'] ) ) ) . ( $r['quota'] ? ' · ' . (int) $r['quota'] . ' chỉ tiêu' : '' ) . '</small></li>';
		}
		$h .= '</ul>';
	}
	if ( ! empty( $d['events'] ) ) {
		$h .= '<p class="cvc-live-sub">Văn bản mới của các đợt tuyển</p><ul class="cvc-live-list">';
		foreach ( array_slice( (array) $d['events'], 0, 3 ) as $e ) {
			$h .= '<li><span class="cvc-live-tag">' . esc_html( (string) $e['type'] ) . '</span> <a href="' . esc_url( home_url( '/tuyen-dung/dot/' . rawurlencode( (string) $e['round_slug'] ) . '/' ) ) . '">' . esc_html( wp_trim_words( (string) $e['title'], 14 ) ) . '</a> <small>' . esc_html( wp_date( 'd/m', strtotime( (string) $e['date'] ) ) ) . '</small></li>';
		}
		$h .= '</ul>';
	}
	$h .= '<a class="cvc-live-more" href="' . esc_url( $list ) . '">Xem tất cả tin đang tuyển →</a></div>';

	return $h;
}
add_shortcode( 'cvc_khoi_song', 'cvc_live_block' );

/** Tự chèn khối vào đầu bài viết có ngữ cảnh cụ thể (bài chưa tự đặt shortcode). */
add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || str_contains( (string) $content, 'cvc-live' ) || has_shortcode( (string) $content, 'cvc_khoi_song' ) ) {
			return $content;
		}
		$block = cvc_live_block();

		return '' !== $block ? $block . $content : $content;
	},
	20
);

add_action(
	'wp_head',
	static function (): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		echo '<style>.cvc-live{margin:0 0 20px;padding:14px 16px;border:1px solid #b8d3ee;border-left:4px solid #1767aa;border-radius:10px;background:#f3f8fd;font-size:15px}.cvc-live-h{margin:0 0 8px;font-weight:800;color:#123a63}.cvc-live-h small{font-weight:400;color:#5c5c5c;margin-left:6px}.cvc-live-dot{display:inline-block;width:9px;height:9px;border-radius:50%;background:#1f9d55;margin-right:8px;box-shadow:0 0 0 3px #1f9d5533}.cvc-live-kpi{list-style:none;margin:0 0 8px;padding:0;display:flex;flex-wrap:wrap;gap:6px 18px}.cvc-live-kpi b{font-size:20px;color:#123a63}.cvc-live-sub{margin:8px 0 4px;font-weight:700;font-size:14px}.cvc-live-list{margin:0;padding:0 0 0 18px;display:grid;gap:4px}.cvc-live-list small{color:#5c5c5c}.cvc-live-tag{display:inline-block;font-size:12px;font-weight:700;color:#8a6414;background:#fbf0d9;border-radius:6px;padding:0 6px}.cvc-live-more{display:inline-block;margin-top:8px;font-weight:700}</style>';
	},
	30
);
