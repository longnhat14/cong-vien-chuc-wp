<?php
/**
 * Trang tuyển dụng công chức / viên chức theo tỉnh - giao diện v2 (03/11/2026), theo 4 file mẫu anh Nhật gửi
 * (claude/thiet-ke-trang-tinh-tuyen-dung-v2.md): hero -> tổng quan -> thông báo (3 trạng thái) -> cơ quan / đơn vị
 * -> lịch tuyển -> hướng dẫn -> hỏi đáp; cột phải: mục lục, nhận email theo tỉnh, tài liệu, chéo công chức <-> viên chức;
 * cuối trang: 34 tỉnh, thành. Header / footer của site giữ nguyên. Không có dữ liệu mẫu: mọi số liệu, cơ quan, tin lấy từ API.
 *
 * Được nạp từ template-rec-hub.php khi là trang tỉnh (biến có sẵn: $preset_key, $preset, $f, $paged, $token, $logged_in,
 * $rh_geo, $rh_prof, $hc, $items, $pg, $total, $soon, $rp_total, $track_url, $track_lbl, $rh_url, $rh_hub_url, $h1,
 * $rh_lead, $filtered, $jobs_res). SEO (title, description, canonical, robots) đã đặt ở template-rec-hub.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rg_kind   = (string) ( $preset['api']['kind'] ?? 'civil' );
$rg_civil  = 'civil' === $rg_kind;
$rg_noun   = (string) $preset['noun'];
$rg_label  = (string) $rh_geo['label'];
$rg_pname  = (string) $rh_geo['name'];
$rg_year   = cvc_rec_year();
$rg_open   = (int) ( $hc['open_recruitments'] ?? $rh_geo['open_count'] ?? 0 );
$rg_pos    = (int) ( $hc['open_positions'] ?? 0 );
$rg_pf     = is_array( $rh_prof ?? null ) ? $rh_prof : array();
$rg_grp    = (array) ( $rg_pf['groups'] ?? array() );
$rg_depts  = (array) ( $rg_pf['departments'] ?? array() );
$rg_au     = (array) ( $rg_pf['admin_units'] ?? array() );
$rg_hist   = (array) ( $rg_pf['history'] ?? array() );
$rg_closed = (array) ( $rg_hist['recent_closed'] ?? array() );
$rg_state  = $rg_open > 0 || $items ? 'open' : ( $rg_closed || (int) ( $rh_geo['recent_count'] ?? 0 ) > 0 ? 'history' : 'empty' );
$rg_other  = null;
foreach ( cvc_rec_presets() as $pk => $pv ) {
	if ( $pk !== $preset_key && ! empty( $pv['geo'] ) ) {
		$rg_other = array( 'key' => $pk, 'h1' => $pv['h1'], 'noun' => $pv['noun'], 'url' => cvc_rec_geo_url( $pk, $rh_geo['short'] ) );
	}
}
$rg_pillar = home_url( $rg_civil ? '/bai-viet/tuyen-cong-chuc/' : '/bai-viet/tuyen-vien-chuc/' );
$rg_docs   = home_url( $rg_civil ? '/tai-lieu/cong-chuc/' : '/tai-lieu/vien-chuc/' );
$rg_notice = isset( $_GET['thong-bao'] ) ? sanitize_key( wp_unslash( $_GET['thong-bao'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$rg_nx     = (int) ( $rg_au['commune'] ?? 0 ) + (int) ( $rg_au['ward'] ?? 0 ) + (int) ( $rg_au['special_zone'] ?? 0 );
$rg_now    = wp_date( 'H:i d/m/Y' );
$rg_is_city = 0 === mb_stripos( $rg_pname, 'Thành phố', 0, 'UTF-8' );
$rg_v      = (int) $rh_geo['id'] % 3;
$rg_ic     = static fn ( string $id, string $cls = '' ) => '<svg class="rg-ic' . ( '' !== $cls ? ' ' . $cls : '' ) . '" aria-hidden="true"><use href="#rg-' . $id . '"/></svg>';
$rg_date   = static fn ( ?string $d, string $fmt = 'd/m/Y' ) => $d && false !== strtotime( substr( $d, 0, 10 ) ) ? wp_date( $fmt, strtotime( substr( $d, 0, 10 ) ) ) : '';

// Tin ở địa phương khác (khi tỉnh chưa có tin) - ghi rõ không phải tin của tỉnh này.
$rg_nearby = array();
if ( 'open' !== $rg_state ) {
	$nb = cvc_ci_api( '/api/career/recruitments', array( 'kind' => array( $rg_kind ), 'status' => 'open', 'sort' => 'deadline', 'page' => 1, 'per_page' => 12, 'with_exams' => 0 ), null, 10, 600 );
	foreach ( $nb['ok'] ? (array) ( $nb['data']['data']['items'] ?? array() ) : array() as $r ) {
		if ( is_array( $r ) && (int) ( $r['province']['id'] ?? 0 ) !== (int) $rh_geo['id'] && count( $rg_nearby ) < 5 ) {
			$rg_nearby[] = $r;
		}
	}
}

// Thẻ cơ quan / đơn vị (số liệu thật) + danh sách tương ứng.
if ( $rg_civil ) {
	$rg_cards = array(
		array( 'id' => 'so-nganh', 'icon' => 'landmark', 'title' => 'Sở, ngành cấp ' . ( $rg_is_city ? 'thành phố' : 'tỉnh' ), 'n' => count( $rg_depts ) + count( (array) ( $rg_grp['state'] ?? array() ) ), 'unit' => 'cơ quan', 'desc' => 'Cơ quan chuyên môn thuộc UBND ' . ( $rg_is_city ? 'thành phố' : 'tỉnh' ) . '.', 'list' => array_merge( ! empty( $rg_pf['portal'] ) ? array( array( 'name' => 'Cổng thông tin điện tử ' . $rg_label, 'url' => $rg_pf['portal']['url'] ?? '' ) ) : array(), $rg_depts, (array) ( $rg_grp['state'] ?? array() ) ) ),
		array( 'id' => 'cap-xa', 'icon' => 'building', 'title' => 'UBND cấp xã', 'n' => $rg_nx, 'unit' => 'xã, phường', 'desc' => 'Chính quyền xã, phường' . ( (int) ( $rg_au['special_zone'] ?? 0 ) ? ', đặc khu' : '' ) . ' sử dụng công chức.', 'list' => (array) ( $rg_grp['commune'] ?? array() ) ),
		array( 'id' => 'dang-mat-tran', 'icon' => 'users', 'title' => 'Khối Đảng, Mặt trận', 'n' => count( (array) ( $rg_grp['party'] ?? array() ) ), 'unit' => 'cơ quan', 'desc' => 'Cơ quan Đảng, Mặt trận Tổ quốc, đoàn thể.', 'list' => (array) ( $rg_grp['party'] ?? array() ) ),
	);
} else {
	$rg_mgr   = array_values( array_filter( $rg_depts, static fn ( $d ) => in_array( $d['code'] ?? '', array( 'sgddt', 'syt', 'svhttdl', 'skhcn' ), true ) ) );
	$rg_cards = array(
		array( 'id' => 'giao-duc', 'icon' => 'graduation', 'title' => 'Giáo dục và đào tạo', 'n' => count( (array) ( $rg_grp['education'] ?? array() ) ), 'unit' => 'đơn vị', 'desc' => 'Trường học, trung tâm giáo dục, cơ sở đào tạo.', 'list' => (array) ( $rg_grp['education'] ?? array() ) ),
		array( 'id' => 'y-te', 'icon' => 'heart-pulse', 'title' => 'Y tế', 'n' => count( (array) ( $rg_grp['health'] ?? array() ) ), 'unit' => 'đơn vị', 'desc' => 'Bệnh viện, trung tâm y tế, trạm y tế công lập.', 'list' => (array) ( $rg_grp['health'] ?? array() ) ),
		array( 'id' => 'van-hoa', 'icon' => 'masks', 'title' => 'Văn hóa, thể thao', 'n' => count( (array) ( $rg_grp['culture'] ?? array() ) ), 'unit' => 'đơn vị', 'desc' => 'Bảo tàng, thư viện, nhà hát, trung tâm văn hóa.', 'list' => (array) ( $rg_grp['culture'] ?? array() ) ),
		array( 'id' => 'co-quan-quan-ly', 'icon' => 'flask', 'title' => 'Sở quản lý', 'n' => count( $rg_mgr ), 'unit' => 'sở', 'desc' => 'Sở Giáo dục, Y tế, Văn hóa, Khoa học quản lý đơn vị.', 'list' => $rg_mgr ),
		array( 'id' => 'don-vi-khac', 'icon' => 'grid', 'title' => 'Đơn vị khác', 'n' => count( (array) ( $rg_grp['other'] ?? array() ) ), 'unit' => 'đơn vị', 'desc' => 'Các đơn vị sự nghiệp công lập khác.', 'list' => (array) ( $rg_grp['other'] ?? array() ) ),
	);
}
$rg_has_orgs = (bool) array_filter( $rg_cards, static fn ( $c ) => ! empty( $c['list'] ) || $c['n'] > 0 );

// Lịch tuyển 12 tháng (khi đủ dữ liệu).
$rg_months = array_map( 'intval', (array) ( $rg_hist['months'] ?? array() ) );
$rg_heat   = (int) ( $rg_hist['total_24m'] ?? 0 ) >= 5 && $rg_months;
$rg_faq    = function_exists( 'cvc_rec_geo_faq' ) && $rg_pf ? cvc_rec_geo_faq( $preset, $rh_geo, $rg_pf, $rg_open ) : array();

// Mục lục theo đúng các mục được hiển thị.
$rg_toc = array( array( 'tuyen-dung', 'megaphone', 'open' === $rg_state ? 'Thông báo đang nhận hồ sơ' : 'Tình hình tuyển dụng tại ' . $rg_label ) );
if ( $rg_nearby ) {
	$rg_toc[] = array( 'dia-phuong-khac', 'map-pin', 'Tin ở địa phương khác' );
}
if ( $rg_has_orgs ) {
	$rg_toc[] = array( 'don-vi', 'building', $rg_civil ? 'Cơ quan tuyển dụng công chức' : 'Đơn vị sự nghiệp công lập' );
}
if ( $rg_heat ) {
	$rg_toc[] = array( 'lich-tuyen', 'calendar', 'Lịch tuyển dụng trong năm' );
}
$rg_toc[] = array( 'huong-dan', 'clipboard', 'Điều kiện, hồ sơ, quy trình' );
$rg_toc[] = array( 'tai-lieu', 'book-open', 'Tài liệu ôn thi ' . $rg_noun );
if ( $rg_faq ) {
	$rg_toc[] = array( 'cau-hoi', 'help', 'Câu hỏi thường gặp' );
}
$rg_toc[] = array( 'tinh-thanh', 'grid', 'Tỉnh, thành phố khác' );

// Lọc nhanh trên danh sách đang hiển thị (không tạo URL mới).
$rg_fams = array();
$rg_orgs = array();
foreach ( $items as $r ) {
	$r = (array) $r;
	if ( ! empty( $r['job_family'] ) ) {
		$rg_fams[ (string) $r['job_family'] ] = (string) ( $r['job_family_label'] ?? $r['job_family'] );
	}
	if ( ! empty( $r['agency']['id'] ) ) {
		$rg_orgs[ (int) $r['agency']['id'] ] = (string) $r['agency']['name'];
	}
}

get_header();
?>
<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false">
	<symbol id="rg-book-open" viewBox="0 0 24 24"><path d="M12 7v14M3 5.5A2.5 2.5 0 0 1 5.5 3H12v18H5.5A2.5 2.5 0 0 0 3 23zM21 5.5A2.5 2.5 0 0 0 18.5 3H12v18h6.5A2.5 2.5 0 0 1 21 23z"/></symbol>
	<symbol id="rg-search" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="7.3"/><path d="m16.2 16.2 5 5"/></symbol>
	<symbol id="rg-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
	<symbol id="rg-chevron-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
	<symbol id="rg-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6M16 3.1a4 4 0 0 1 0 7.8"/></symbol>
	<symbol id="rg-map-pin" viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
	<symbol id="rg-landmark" viewBox="0 0 24 24"><path d="m3 9 9-6 9 6M4 10h16M5 10v9M9 10v9M15 10v9M19 10v9M3 21h18M2 19h20"/></symbol>
	<symbol id="rg-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 17h.01"/></symbol>
	<symbol id="rg-megaphone" viewBox="0 0 24 24"><path d="m3 11 18-5v12L3 13zM11 14l2 7H8l-2-8M21 10h1M21 14h1"/></symbol>
	<symbol id="rg-building" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 7h2M14 7h2M9 11h2M14 11h2M9 15h2M14 15h2M10 21v-3h4v3"/></symbol>
	<symbol id="rg-briefcase" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12h18M10 12v2h4v-2"/></symbol>
	<symbol id="rg-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h8"/></symbol>
	<symbol id="rg-clipboard" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 4V2h6v2M8 10h8M8 14h8M8 18h5"/></symbol>
	<symbol id="rg-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.5 9a2.6 2.6 0 1 1 4.4 1.9c-1.2 1.1-1.9 1.4-1.9 3M12 17.5h.01"/></symbol>
	<symbol id="rg-graduation" viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5zM6 11v5c3.5 3 8.5 3 12 0v-5M22 9v6"/></symbol>
	<symbol id="rg-heart-pulse" viewBox="0 0 24 24"><path d="M20.8 8.7c0 5.2-8.8 11.3-8.8 11.3S3.2 13.9 3.2 8.7A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.5Z"/><path d="M3 12h5l2-3 3 6 2-3h6"/></symbol>
	<symbol id="rg-masks" viewBox="0 0 24 24"><path d="M3 5 12 2l9 3v6c0 5-4 8-9 10-5-2-9-5-9-10zM8 10h.01M16 10h.01M8 14c2 1 6 1 8 0M3 8l-2 2M21 8l2 2"/></symbol>
	<symbol id="rg-flask" viewBox="0 0 24 24"><path d="M9 3h6M10 3v7l-6 10a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1l-6-10V3M8 15h8"/></symbol>
	<symbol id="rg-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
	<symbol id="rg-check-circle" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></symbol>
	<symbol id="rg-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 11v6M12 7h.01"/></symbol>
	<symbol id="rg-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
	<symbol id="rg-bell-ring" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4M2 4 4 6M22 4l-2 2"/></symbol>
</svg>

<main id="main" class="rg ci" data-preset="<?php echo esc_attr( $preset_key ); ?>" data-state="<?php echo esc_attr( $rg_state ); ?>">
	<section class="rg-hero">
		<svg class="rg-hero-art" viewBox="0 0 520 240" fill="none" aria-hidden="true">
			<path d="M0 214h520" stroke="#e7d6c8" stroke-width="3"/>
			<path d="M300 92 380 52l80 40v10H300z" fill="#f8b66f" stroke="#d96a22" stroke-width="2"/>
			<path d="M310 104h140v108H310z" fill="#fff0d9" stroke="#d96a22" stroke-width="2"/>
			<path d="M324 116h14v24h-14zM352 116h14v24h-14zM394 116h14v24h-14zM422 116h14v24h-14zM324 154h14v24h-14zM422 154h14v24h-14z" fill="#f2a34d"/>
			<path d="M366 166h28v46h-28zM380 62V40" stroke="#b84b16" stroke-width="2"/>
			<path d="M380 38 398 46l-18 7z" fill="#d94322"/>
			<path d="M200 212V140h60v72M214 154h12v12h-12zM234 154h12v12h-12zM214 178h12v12h-12zM234 178h12v12h-12z" stroke="#9fb4c9" stroke-width="2" fill="#f4f8fc"/>
			<path d="M470 212v-58h40v58M480 166h8v8h-8zM494 166h8v8h-8zM480 186h8v8h-8zM494 186h8v8h-8z" stroke="#9fb4c9" stroke-width="2" fill="#f4f8fc"/>
			<path d="M150 214q22-30 44 0M455 214q18-24 36 0" stroke="#47965c" stroke-width="6" stroke-linecap="round"/>
		</svg>
		<div class="rg-wrap rg-hero-inner">
			<?php cvc_render_breadcrumbs( array( array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ), array( 'label' => $preset['h1'], 'url' => $rh_hub_url ), array( 'label' => $rg_label ) ) ); ?>
			<h1><?php echo $rg_ic( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( $preset['h1'] . ' ' . $rg_label . ' năm ' . $rg_year ); ?></h1>
			<p class="rg-hero-desc"><?php echo esc_html( $rh_lead ); ?></p>
			<div class="rg-pills">
				<span class="rg-pill"><?php echo $rg_ic( 'map-pin' ); // phpcs:ignore ?> <?php echo esc_html( $rg_label ); ?></span>
				<span class="rg-pill"><?php echo $rg_ic( 'landmark' ); // phpcs:ignore ?> <?php echo $rg_civil ? 'Cơ quan hành chính nhà nước' : 'Đơn vị sự nghiệp công lập'; ?></span>
				<span class="rg-pill"><?php echo $rg_ic( 'calendar' ); // phpcs:ignore ?> Năm tuyển dụng: <?php echo (int) $rg_year; ?></span>
				<?php if ( $rg_open > 0 ) : ?><span class="rg-pill is-hot"><?php echo $rg_ic( 'megaphone' ); // phpcs:ignore ?> <?php echo esc_html( $rg_open . ' thông báo còn hạn' . ( $rg_pos ? ' · ' . $rg_pos . ' vị trí' : '' ) ); ?></span><?php endif; ?>
			</div>
		</div>
	</section>

	<div class="rg-wrap rg-layout">
		<div class="rg-main">
			<section class="rg-panel rg-overview">
				<h2 class="rg-title"><?php echo $rg_ic( 'building' ); // phpcs:ignore ?> <?php echo esc_html( 'Tổng quan tuyển dụng ' . $rg_noun . ' tại ' . $rg_label ); ?></h2>
				<?php
				if ( $rg_civil ) {
					$ov = array(
						'Công chức tại %1$s làm việc trong cơ quan của Đảng, Mặt trận Tổ quốc, các sở, ngành thuộc UBND %2$s và chính quyền cấp xã.',
						'Ở %1$s, công chức được tuyển vào các sở, ngành thuộc UBND %2$s, cơ quan Đảng, Mặt trận Tổ quốc và UBND cấp xã.',
						'Nơi làm việc của công chức %1$s gồm các sở, ngành cấp tỉnh, cơ quan Đảng, Mặt trận Tổ quốc và chính quyền xã, phường.',
					)[ $rg_v ];
					$ov = sprintf( $ov, $rg_label, $rg_is_city ? 'thành phố' : 'tỉnh' );
				} else {
					$ov = sprintf(
						array(
							'Viên chức tại %1$s làm việc trong các đơn vị sự nghiệp công lập: trường học, bệnh viện, trung tâm y tế, đơn vị văn hóa, thể thao và các trung tâm dịch vụ công.',
							'Ở %1$s, viên chức được tuyển vào trường học, cơ sở y tế, đơn vị văn hóa, thể thao và các đơn vị sự nghiệp công lập khác thuộc sở, ngành và chính quyền cấp xã.',
							'Đơn vị tuyển viên chức tại %1$s gồm trường học các cấp, bệnh viện, trung tâm y tế, bảo tàng, thư viện, nhà hát và các trung tâm sự nghiệp công lập.',
						)[ $rg_v ],
						$rg_label
					);
				}
				$facts = array();
				if ( $rg_nx ) {
					$facts[] = sprintf( '%s có %d đơn vị hành chính cấp xã', $rg_label, $rg_nx );
				}
				if ( $rg_civil && count( $rg_depts ) ) {
					$facts[] = sprintf( 'Công Viên Chức đang theo dõi trang thông tin của %d sở, ngành', count( $rg_depts ) );
				} elseif ( ! $rg_civil ) {
					$nu = count( (array) ( $rg_grp['education'] ?? array() ) ) + count( (array) ( $rg_grp['health'] ?? array() ) ) + count( (array) ( $rg_grp['culture'] ?? array() ) ) + count( (array) ( $rg_grp['other'] ?? array() ) );
					if ( $nu ) {
						$facts[] = sprintf( 'Công Viên Chức đang theo dõi thông báo của %d đơn vị sự nghiệp', $nu );
					}
				}
				if ( (int) ( $rh_geo['recent_count'] ?? 0 ) ) {
					$facts[] = sprintf( '12 tháng qua ghi nhận %d thông báo tuyển dụng %s', (int) $rh_geo['recent_count'], $rg_noun );
				}
				?>
				<p><?php echo esc_html( $ov . ( $facts ? ' ' . implode( '; ', $facts ) . '.' : '' ) ); ?></p>
				<svg class="rg-overview-art" viewBox="0 0 160 120" fill="none" aria-hidden="true"><path d="M10 42 80 8l70 34v8H10z" fill="#f8b66f" stroke="#d96a22" stroke-width="2"/><path d="M20 53h120v54H20z" fill="#fff0d9" stroke="#d96a22" stroke-width="2"/><path d="M31 61h12v15H31zM57 61h12v15H57zM91 61h12v15H91zM117 61h12v15h-12z" fill="#f2a34d"/><path d="M69 84h22v23H69zM78 18v-9" stroke="#b84b16" stroke-width="2"/><path d="M78 7 91 13l-13 5z" fill="#d94322"/><path d="M8 108q18-20 35 0M116 108q18-20 35 0" stroke="#47965c" stroke-width="5" stroke-linecap="round"/></svg>
			</section>

			<section class="rg-panel" id="tuyen-dung">
				<div class="rg-head">
					<h2 class="rg-title"><?php echo $rg_ic( 'megaphone' ); // phpcs:ignore ?> <?php echo esc_html( 'open' === $rg_state ? 'Thông báo tuyển dụng ' . $rg_noun . ' đang nhận hồ sơ' : 'Thông báo tuyển dụng ' . $rg_noun . ' tại ' . $rg_label ); ?></h2>
					<span class="rg-count"><?php echo 'open' === $rg_state ? esc_html( number_format_i18n( $total ?: $rg_open ) . ' thông báo' ) : ( 'history' === $rg_state ? 'Chưa có tin đang mở' : 'Chưa có dữ liệu' ); ?></span>
				</div>

				<?php if ( ! $jobs_res['ok'] ) : ?>
					<div class="rg-empty"><div class="rg-empty-head"><?php echo $rg_ic( 'info' ); // phpcs:ignore ?> Không tải được danh sách tin</div><p>Hệ thống đang bận, vui lòng tải lại trang sau ít phút.</p></div>
				<?php elseif ( 'open' === $rg_state ) : ?>
					<?php if ( count( $rg_fams ) > 1 || count( $rg_orgs ) > 1 ) : ?>
						<div class="rg-filters" role="group" aria-label="Lọc nhanh">
							<?php if ( count( $rg_fams ) > 1 ) : ?>
								<button type="button" class="rg-chip is-on" data-rg-f="fam" data-v="">Tất cả lĩnh vực</button>
								<?php foreach ( $rg_fams as $k => $l ) : ?><button type="button" class="rg-chip" data-rg-f="fam" data-v="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></button><?php endforeach; ?>
							<?php endif; ?>
							<?php if ( count( $rg_orgs ) > 1 ) : ?>
								<label class="rg-sr" for="rg-org">Đơn vị</label>
								<select id="rg-org" class="rg-select" data-rg-f="org"><option value="">Tất cả đơn vị (<?php echo count( $rg_orgs ); ?>)</option><?php foreach ( $rg_orgs as $k => $l ) : ?><option value="<?php echo (int) $k; ?>"><?php echo esc_html( cvc_rec_geo_short_org( $l, $rg_pname ) ); ?></option><?php endforeach; ?></select>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<div class="rg-jobs" id="rg-jobs">
						<?php foreach ( $items as $r ) : ?>
							<?php
							$r    = (array) $r;
							$dl   = (string) ( $r['status']['deadline'] ?? '' );
							$left = isset( $r['status']['days_left'] ) ? (int) $r['status']['days_left'] : null;
							$new  = ! empty( $r['published_at'] ) && strtotime( (string) $r['published_at'] ) >= strtotime( '-7 days', current_time( 'timestamp' ) );
							?>
							<article class="rg-job" data-fam="<?php echo esc_attr( (string) ( $r['job_family'] ?? '' ) ); ?>" data-org="<?php echo (int) ( $r['agency']['id'] ?? 0 ); ?>">
								<div class="rg-date<?php echo null !== $left && $left <= 7 ? ' is-soon' : ''; ?>"><?php if ( '' !== $dl ) : ?><strong><?php echo esc_html( $rg_date( $dl, 'd' ) ); ?></strong><span><?php echo esc_html( $rg_date( $dl, 'm/Y' ) ); ?></span><?php else : ?><span>Chưa rõ hạn</span><?php endif; ?></div>
								<div class="rg-job-main">
									<a class="rg-job-title" href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( (string) $r['title'] ); ?></a>
									<div class="rg-meta">
										<?php if ( ! empty( $r['agency']['name'] ) ) : ?><span><?php echo $rg_ic( 'building' ); // phpcs:ignore ?> <?php echo esc_html( cvc_rec_geo_short_org( (string) $r['agency']['name'], $rg_pname ) ); ?></span><?php endif; ?>
										<?php if ( ! empty( $r['total_positions'] ) ) : ?><span><?php echo $rg_ic( 'users' ); // phpcs:ignore ?> Chỉ tiêu: <?php echo (int) $r['total_positions']; ?></span><?php endif; ?>
										<?php if ( '' !== $dl ) : ?><span><?php echo $rg_ic( 'calendar' ); // phpcs:ignore ?> Hạn nộp: <?php echo esc_html( $rg_date( $dl ) ); ?><?php echo null !== $left && $left >= 0 ? esc_html( ' · ' . ( 0 === $left ? 'hôm nay' : 'còn ' . $left . ' ngày' ) ) : ''; ?></span><?php endif; ?>
										<?php if ( ! empty( $r['job_family_label'] ) ) : ?><span><?php echo $rg_ic( 'briefcase' ); // phpcs:ignore ?> <?php echo esc_html( (string) $r['job_family_label'] ); ?></span><?php endif; ?>
									</div>
								</div>
								<div class="rg-job-end">
									<?php if ( null !== $left && $left >= 0 && $left <= 7 ) : ?><span class="rg-tag is-soon">Sắp hết hạn</span><?php elseif ( $new ) : ?><span class="rg-tag">Mới</span><?php endif; ?>
									<a class="rg-arrow" href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>" aria-label="<?php echo esc_attr( 'Xem: ' . (string) $r['title'] ); ?>"><?php echo $rg_ic( 'chevron-right' ); // phpcs:ignore ?></a>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
					<div class="rg-empty" id="rg-jobs-empty" hidden><div class="rg-empty-head"><?php echo $rg_ic( 'info' ); // phpcs:ignore ?> Không có tin khớp bộ lọc</div><p>Chọn “Tất cả lĩnh vực” hoặc “Tất cả đơn vị” để xem lại toàn bộ thông báo.</p></div>
					<?php
					$cur  = (int) ( $pg['page'] ?? 1 );
					$last = (int) ( $pg['last_page'] ?? 1 );
					if ( $last > 1 ) :
						?>
						<nav class="rg-pager" aria-label="Phân trang">
							<?php if ( $cur > 1 ) : ?><a class="rg-btn is-outline" rel="prev" href="<?php echo esc_url( $rh_url( $cur - 1 ) ); ?>">← Trang trước</a><?php endif; ?>
							<span>Trang <?php echo (int) $cur; ?>/<?php echo (int) $last; ?></span>
							<?php if ( $cur < $last ) : ?><a class="rg-btn is-outline" rel="next" href="<?php echo esc_url( $rh_url( $cur + 1 ) ); ?>">Trang sau →</a><?php endif; ?>
						</nav>
					<?php endif; ?>
					<a class="rg-btn is-outline rg-all" href="<?php echo esc_url( $rh_hub_url ); ?>"><?php echo esc_html( 'Xem tuyển dụng ' . $rg_noun . ' toàn quốc' ); ?> <?php echo $rg_ic( 'arrow-right' ); // phpcs:ignore ?></a>
					<p class="rg-note">Mỗi tin lấy từ nguồn chính thức của cơ quan tuyển dụng; luôn đối chiếu thông báo gốc trước khi nộp hồ sơ. Dữ liệu cập nhật lúc <?php echo esc_html( $rg_now ); ?>.</p>
				<?php else : ?>
					<div class="rg-nodata">
						<div class="rg-nodata-art" aria-hidden="true">
							<svg viewBox="0 0 220 170"><path d="M42 137h136" stroke="#b9c8d8" stroke-width="5" stroke-linecap="round"/><rect x="57" y="28" width="106" height="105" rx="12" fill="#fff" stroke="#c9d8e7" stroke-width="3"/><path d="M82 58h56M82 76h56M82 94h34" stroke="#9fb4c9" stroke-width="6" stroke-linecap="round"/><circle cx="151" cy="116" r="26" fill="#eaf3fb" stroke="#4384bb" stroke-width="5"/><path d="m170 135 19 19" stroke="#4384bb" stroke-width="8" stroke-linecap="round"/><path d="M37 42h18M46 33v18M177 34h14M184 27v14" stroke="#e66a24" stroke-width="4" stroke-linecap="round"/><path d="M26 111c9-9 18-9 27 0M168 17c8-7 16-7 24 0" stroke="#e6a06d" stroke-width="3" fill="none" stroke-linecap="round"/></svg>
						</div>
						<div class="rg-nodata-copy">
							<span class="rg-status"><span class="rg-dot"></span> <?php echo 'history' === $rg_state ? 'Trạng thái: chưa có đợt tuyển đang nhận hồ sơ' : 'Trạng thái dữ liệu: chưa ghi nhận thông báo'; ?></span>
							<h3><?php echo esc_html( 'Hiện chưa có thông báo tuyển dụng ' . $rg_noun . ' tại ' . $rg_label . ' còn hạn' ); ?></h3>
							<p><?php echo esc_html( 'Công Viên Chức chưa ghi nhận thông báo tuyển dụng ' . $rg_noun . ' còn hạn nộp hồ sơ tại ' . $rg_label . '. Chúng tôi không hiển thị tin chưa xác minh. Điều này không có nghĩa địa phương không có nhu cầu tuyển dụng - các đợt tuyển được công bố theo kế hoạch riêng, và tin mới sẽ tự hiện ở đầu trang này.' ); ?></p>
							<div class="rg-actions">
								<a class="rg-btn is-primary" href="#nhan-thong-bao"><?php echo $rg_ic( 'bell' ); // phpcs:ignore ?> Nhận email khi có tin mới</a>
								<a class="rg-btn is-outline" href="#tai-lieu"><?php echo $rg_ic( 'book-open' ); // phpcs:ignore ?> Xem tài liệu ôn thi</a>
							</div>
							<small class="rg-updated"><?php echo $rg_ic( 'calendar' ); // phpcs:ignore ?> Dữ liệu cập nhật lúc <strong><?php echo esc_html( $rg_now ); ?></strong></small>
						</div>
					</div>
					<?php if ( $rg_closed ) : ?>
						<div class="rg-guide">
							<h3><?php echo $rg_ic( 'file' ); // phpcs:ignore ?> Các đợt tuyển đã đóng gần đây</h3>
							<div class="rg-guide-grid">
								<?php foreach ( $rg_closed as $c ) : ?>
									<a class="rg-guide-item" href="<?php echo esc_url( cvc_recruitment_url( (string) $c['slug'] ) ); ?>"><span class="rg-guide-ic"><?php echo $rg_ic( 'calendar' ); // phpcs:ignore ?></span><span><strong><?php echo esc_html( (string) $c['title'] ); ?></strong><small><?php echo ! empty( $c['deadline'] ) ? esc_html( 'Đã hết hạn nộp ' . $rg_date( (string) $c['deadline'] ) ) : 'Đã đóng'; ?></small></span><?php echo $rg_ic( 'chevron-right', 'rg-guide-arrow' ); // phpcs:ignore ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<div class="rg-guide">
						<h3><?php echo $rg_ic( 'info' ); // phpcs:ignore ?> Trong thời gian chờ thông báo mới</h3>
						<p>Chuẩn bị trước kiến thức, hồ sơ và kỹ năng để chủ động khi cơ quan có thẩm quyền công bố kế hoạch tuyển dụng.</p>
						<div class="rg-guide-grid">
							<a class="rg-guide-item" href="#don-vi"><span class="rg-guide-ic"><?php echo $rg_ic( 'landmark' ); // phpcs:ignore ?></span><span><strong><?php echo $rg_civil ? 'Tìm hiểu cơ quan tuyển dụng' : 'Tìm hiểu đơn vị sự nghiệp'; ?></strong><small><?php echo esc_html( $rg_civil ? 'Sở, ngành, chính quyền cấp xã và khối Đảng, Mặt trận tại ' . $rg_label . '.' : 'Trường học, cơ sở y tế, đơn vị văn hóa tại ' . $rg_label . '.' ); ?></small></span><?php echo $rg_ic( 'chevron-right', 'rg-guide-arrow' ); // phpcs:ignore ?></a>
							<a class="rg-guide-item" href="<?php echo esc_url( $rg_pillar ); ?>"><span class="rg-guide-ic"><?php echo $rg_ic( 'clipboard' ); // phpcs:ignore ?></span><span><strong>Điều kiện và hồ sơ dự tuyển</strong><small><?php echo esc_html( 'Tiêu chuẩn, hồ sơ và các bước đăng ký dự tuyển ' . $rg_noun . '.' ); ?></small></span><?php echo $rg_ic( 'chevron-right', 'rg-guide-arrow' ); // phpcs:ignore ?></a>
							<a class="rg-guide-item" href="<?php echo esc_url( $track_url ); ?>"><span class="rg-guide-ic"><?php echo $rg_ic( 'book-open' ); // phpcs:ignore ?></span><span><strong>Ôn tập kiến thức chung</strong><small>Luyện đề trắc nghiệm kiến thức chung, pháp luật và chuyên ngành, có đáp án và căn cứ.</small></span><?php echo $rg_ic( 'chevron-right', 'rg-guide-arrow' ); // phpcs:ignore ?></a>
						</div>
					</div>
				<?php endif; ?>
			</section>

			<?php if ( $rg_nearby ) : ?>
				<section class="rg-panel" id="dia-phuong-khac">
					<div class="rg-head"><h2 class="rg-title"><?php echo $rg_ic( 'map-pin' ); // phpcs:ignore ?> <?php echo esc_html( 'Tham khảo tuyển dụng ' . $rg_noun . ' tại địa phương khác' ); ?></h2><span class="rg-count">Toàn quốc</span></div>
					<p class="rg-intro"><?php echo esc_html( 'Các thông báo dưới đây thuộc địa phương khác, giới thiệu để bạn tham khảo - đây không phải tin tuyển dụng tại ' . $rg_label . '.' ); ?></p>
					<div class="rg-jobs">
						<?php foreach ( $rg_nearby as $r ) : ?>
							<?php $dl = (string) ( $r['status']['deadline'] ?? '' ); ?>
							<article class="rg-job">
								<div class="rg-date is-other"><?php if ( '' !== $dl ) : ?><strong><?php echo esc_html( $rg_date( $dl, 'd' ) ); ?></strong><span><?php echo esc_html( $rg_date( $dl, 'm/Y' ) ); ?></span><?php else : ?><span>—</span><?php endif; ?></div>
								<div class="rg-job-main"><a class="rg-job-title" href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( (string) $r['title'] ); ?></a><div class="rg-meta"><span><?php echo $rg_ic( 'map-pin' ); // phpcs:ignore ?> <?php echo esc_html( (string) ( $r['province']['name'] ?? '' ) ); ?></span><?php if ( '' !== $dl ) : ?><span><?php echo $rg_ic( 'calendar' ); // phpcs:ignore ?> Hạn nộp: <?php echo esc_html( $rg_date( $dl ) ); ?></span><?php endif; ?></div></div>
								<div class="rg-job-end"><a class="rg-arrow" href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>" aria-label="Xem tin"><?php echo $rg_ic( 'chevron-right' ); // phpcs:ignore ?></a></div>
							</article>
						<?php endforeach; ?>
					</div>
					<a class="rg-btn is-outline rg-all" href="<?php echo esc_url( $rh_hub_url ); ?>"><?php echo esc_html( 'Xem tất cả tuyển dụng ' . $rg_noun . ' trên toàn quốc' ); ?> <?php echo $rg_ic( 'arrow-right' ); // phpcs:ignore ?></a>
				</section>
			<?php endif; ?>

			<?php if ( $rg_has_orgs ) : ?>
				<section class="rg-panel" id="don-vi">
					<h2 class="rg-title"><?php echo $rg_ic( 'building' ); // phpcs:ignore ?> <?php echo esc_html( ( $rg_civil ? 'Cơ quan tuyển dụng công chức tại ' : 'Đơn vị sự nghiệp công lập tại ' ) . $rg_label ); ?></h2>
					<div class="rg-cats is-<?php echo count( $rg_cards ); ?>">
						<?php foreach ( $rg_cards as $c ) : ?>
							<a class="rg-cat<?php echo $c['n'] ? '' : ' is-zero'; ?>" href="#ds-<?php echo esc_attr( $c['id'] ); ?>">
								<?php echo $rg_ic( $c['icon'] ); // phpcs:ignore ?>
								<strong><?php echo esc_html( $c['title'] ); ?></strong>
								<b class="rg-cat-n"><?php echo $c['n'] ? esc_html( number_format_i18n( $c['n'] ) . ' ' . $c['unit'] ) : 'Đang cập nhật'; ?></b>
								<small><?php echo esc_html( $c['desc'] ); ?></small>
							</a>
						<?php endforeach; ?>
					</div>
					<div class="rg-orglists">
						<?php foreach ( $rg_cards as $c ) : ?>
							<?php if ( empty( $c['list'] ) ) { continue; } ?>
							<div class="rg-orglist" id="ds-<?php echo esc_attr( $c['id'] ); ?>"><h3><?php echo $rg_ic( $c['icon'] ); // phpcs:ignore ?> <?php echo esc_html( $c['title'] ); ?></h3><?php echo cvc_rec_geo_org_list( $c['list'], $rg_pname, 8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						<?php endforeach; ?>
					</div>
					<p class="rg-note">Chỉ liệt kê cơ quan, đơn vị có trang thông tin chính thức đã được xác minh hoặc đã có thông báo tuyển dụng; danh sách được bổ sung dần<?php echo ! empty( $rg_pf['updated_at'] ) ? esc_html( ', cập nhật ' . $rg_date( (string) $rg_pf['updated_at'] ) ) : ''; ?>. Nhãn “đã có tin” là đơn vị từng đăng thông báo tuyển dụng <?php echo esc_html( $rg_noun ); ?>.</p>
				</section>
			<?php endif; ?>

			<?php if ( $rg_heat ) : ?>
				<?php $mx = max( 1, max( $rg_months ) ); ?>
				<section class="rg-panel" id="lich-tuyen">
					<h2 class="rg-title"><?php echo $rg_ic( 'calendar' ); // phpcs:ignore ?> <?php echo esc_html( 'Lịch tuyển dụng ' . $rg_noun . ' ' . $rg_label . ' trong năm' ); ?></h2>
					<p class="rg-intro"><?php echo esc_html( 'Số thông báo theo tháng công bố trong 24 tháng qua (' . (int) $rg_hist['total_24m'] . ' thông báo). Tháng đậm màu là tháng địa phương thường ra thông báo.' ); ?></p>
					<ol class="rg-heat">
						<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
							<?php $n = (int) ( $rg_months[ $m ] ?? $rg_months[ (string) $m ] ?? 0 ); ?>
							<li style="--rg-h:<?php echo esc_attr( (string) round( $n / $mx, 2 ) ); ?>" title="<?php echo esc_attr( 'Tháng ' . $m . ': ' . $n . ' thông báo' ); ?>"><b><?php echo (int) $n; ?></b><span>T<?php echo (int) $m; ?></span></li>
						<?php endfor; ?>
					</ol>
				</section>
			<?php endif; ?>

			<section class="rg-panel" id="huong-dan">
				<h2 class="rg-title"><?php echo $rg_ic( 'file' ); // phpcs:ignore ?> <?php echo esc_html( 'Hướng dẫn dự tuyển ' . $rg_noun ); ?></h2>
				<div class="rg-cats is-3">
					<a class="rg-cat" href="<?php echo esc_url( $rg_pillar ); ?>"><?php echo $rg_ic( 'clipboard' ); // phpcs:ignore ?><strong>Điều kiện dự tuyển</strong><small>Tiêu chuẩn, điều kiện và yêu cầu vị trí việc làm.</small></a>
					<a class="rg-cat" href="<?php echo esc_url( $rg_pillar ); ?>"><?php echo $rg_ic( 'file' ); // phpcs:ignore ?><strong>Hồ sơ dự tuyển</strong><small>Thành phần hồ sơ, phiếu đăng ký và cách chuẩn bị.</small></a>
					<a class="rg-cat" href="<?php echo esc_url( $rg_pillar ); ?>"><?php echo $rg_ic( 'calendar' ); // phpcs:ignore ?><strong><?php echo $rg_civil ? 'Quy trình thi tuyển' : 'Quy trình xét tuyển'; ?></strong><small>Các vòng thi, xét và mốc thời gian cần lưu ý.</small></a>
				</div>
			</section>

			<?php if ( $rg_faq ) : ?>
				<section class="rg-panel rg-faq" id="cau-hoi">
					<h2 class="rg-title"><?php echo $rg_ic( 'help' ); // phpcs:ignore ?> Câu hỏi thường gặp</h2>
					<?php foreach ( $rg_faq as $qa ) : ?><details><summary><?php echo esc_html( $qa['q'] ); ?></summary><p><?php echo esc_html( $qa['a'] ); ?></p></details><?php endforeach; ?>
					<script type="application/ld+json"><?php echo wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map( static fn ( $qa ) => array( '@type' => 'Question', 'name' => $qa['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $qa['a'] ) ), $rg_faq ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
				</section>
			<?php endif; ?>
		</div>

		<aside class="rg-side" aria-label="Tiện ích">
			<section class="rg-panel rg-toc">
				<h2 class="rg-side-title"><?php echo $rg_ic( 'search' ); // phpcs:ignore ?> Danh mục nội dung</h2>
				<ul><?php foreach ( $rg_toc as $t ) : ?><li><a href="#<?php echo esc_attr( $t[0] ); ?>"><?php echo $rg_ic( $t[1] ); // phpcs:ignore ?> <?php echo esc_html( $t[2] ); ?></a></li><?php endforeach; ?></ul>
			</section>

			<section class="rg-card" id="nhan-thong-bao">
				<h3><?php echo $rg_ic( 'bell-ring' ); // phpcs:ignore ?> <?php echo esc_html( 'Theo dõi tuyển dụng ' . $rg_label ); ?></h3>
				<?php if ( in_array( $rg_notice, array( 'da-xac-nhan', 'da-huy', 'lien-ket-het-han' ), true ) ) : ?>
					<p class="rg-flash<?php echo 'da-xac-nhan' === $rg_notice ? ' is-ok' : ''; ?>" role="status"><?php echo esc_html( array( 'da-xac-nhan' => 'Đã xác nhận. Bạn sẽ nhận email khi ' . $rg_label . ' có thông báo tuyển dụng ' . $rg_noun . ' mới.', 'da-huy' => 'Đã hủy nhận email tin tuyển dụng ' . $rg_noun . ' ' . $rg_label . '.', 'lien-ket-het-han' => 'Liên kết không còn hiệu lực. Vui lòng đăng ký lại.' )[ $rg_notice ] ); ?></p>
				<?php endif; ?>
				<p><?php echo esc_html( 'Nhập email để nhận thông báo khi có tin tuyển dụng ' . $rg_noun . ' mới tại ' . $rg_label . '. Chỉ gửi khi có tin mới, hủy bất cứ lúc nào.' ); ?></p>
				<form class="rg-email" id="rg-alert-form" data-province="<?php echo (int) $rh_geo['id']; ?>" data-kind="<?php echo esc_attr( $rg_kind ); ?>" novalidate>
					<label class="rg-sr" for="rg-alert-email">Email nhận thông báo</label>
					<input id="rg-alert-email" type="email" name="email" required maxlength="190" autocomplete="email" placeholder="Nhập email của bạn...">
					<input type="text" name="website" tabindex="-1" autocomplete="off" class="rg-hp" aria-hidden="true">
					<button class="rg-btn is-primary" type="submit">Đăng ký</button>
				</form>
				<p class="rg-email-msg" id="rg-alert-msg" role="status" aria-live="polite"></p>
				<?php if ( $logged_in ) : ?><p class="rg-small"><a href="<?php echo esc_url( cvc_ci_url( array( 'tinh' => (int) $rh_geo['id'] ) ) ); ?>#ci-goal-form">Hoặc đặt mục tiêu dự tuyển để nhận cả thông báo trên tài khoản →</a></p><?php endif; ?>
			</section>

			<section class="rg-card is-materials" id="tai-lieu">
				<h3><?php echo $rg_ic( 'book-open' ); // phpcs:ignore ?> <?php echo esc_html( 'Tài liệu ôn thi ' . $rg_noun ); ?></h3>
				<ul class="rg-check">
					<li><a href="<?php echo esc_url( $track_url ); ?>"><?php echo $rg_ic( 'check-circle' ); // phpcs:ignore ?> <?php echo esc_html( $track_lbl ); ?></a></li>
					<li><a href="<?php echo esc_url( $rg_docs ); ?>"><?php echo $rg_ic( 'check-circle' ); // phpcs:ignore ?> <?php echo esc_html( 'Tài liệu ôn thi ' . $rg_noun ); ?></a></li>
					<?php if ( $rp_total > 0 ) : ?><li><a href="<?php echo esc_url( $rh_hub_url ); ?>"><?php echo $rg_ic( 'check-circle' ); // phpcs:ignore ?> <?php echo esc_html( $rp_total . ' tin có đề trắc nghiệm riêng' ); ?></a></li><?php endif; ?>
					<li><a href="<?php echo esc_url( home_url( '/van-ban-phap-luat/' ) ); ?>"><?php echo $rg_ic( 'check-circle' ); // phpcs:ignore ?> Văn bản pháp luật cần nắm</a></li>
				</ul>
				<a class="rg-btn is-blue" href="<?php echo esc_url( $track_url ); ?>">Bắt đầu ôn thi <?php echo $rg_ic( 'arrow-right' ); // phpcs:ignore ?></a>
			</section>

			<?php if ( $rg_other ) : ?>
				<section class="rg-card is-cross">
					<h3><?php echo $rg_ic( 'users' ); // phpcs:ignore ?> <?php echo esc_html( 'Xem ' . mb_strtolower( $rg_other['h1'], 'UTF-8' ) ); ?></h3>
					<p><?php echo esc_html( 'Thông báo và cơ quan tuyển dụng ' . $rg_other['noun'] . ' tại ' . $rg_label . '.' ); ?></p>
					<a class="rg-btn" href="<?php echo esc_url( $rg_other['url'] ); ?>"><?php echo esc_html( $rg_other['h1'] . ' ' . $rg_label ); ?> <?php echo $rg_ic( 'arrow-right' ); // phpcs:ignore ?></a>
				</section>
			<?php endif; ?>
		</aside>
	</div>

	<div class="rg-wrap rg-provinces" id="tinh-thanh">
		<?php cvc_rec_geo_render_links( $preset_key, $rh_geo['short'] ); ?>
	</div>
	<div class="ci-toast" id="ci-toast" role="status" aria-live="polite"></div>
</main>
<script>
(function () {
	var root = document.querySelector('.rg');
	if (!root) { return; }
	// Lọc nhanh trên danh sách đang hiển thị.
	var st = { fam: '', org: '' };
	function apply() {
		var shown = 0;
		root.querySelectorAll('#rg-jobs .rg-job').forEach(function (j) {
			var ok = (!st.fam || j.getAttribute('data-fam') === st.fam) && (!st.org || j.getAttribute('data-org') === st.org);
			j.hidden = !ok; if (ok) { shown++; }
		});
		var e = document.getElementById('rg-jobs-empty'); if (e) { e.hidden = shown > 0; }
	}
	root.querySelectorAll('[data-rg-f="fam"]').forEach(function (b) {
		b.addEventListener('click', function () {
			st.fam = b.getAttribute('data-v') || '';
			root.querySelectorAll('[data-rg-f="fam"]').forEach(function (x) { x.classList.toggle('is-on', x === b); x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
			apply();
		});
	});
	var org = root.querySelector('[data-rg-f="org"]');
	if (org) { org.addEventListener('change', function () { st.org = org.value; apply(); }); }
	// Đăng ký email theo tỉnh.
	var f = document.getElementById('rg-alert-form'), msg = document.getElementById('rg-alert-msg');
	if (f) {
		f.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var em = f.email.value.trim();
			if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { msg.textContent = 'Vui lòng nhập email hợp lệ.'; msg.className = 'rg-email-msg is-err'; return; }
			var btn = f.querySelector('button'); btn.disabled = true; msg.textContent = 'Đang gửi...'; msg.className = 'rg-email-msg';
			var fd = new FormData(); fd.append('action', 'cvc_geo_alert'); fd.append('email', em); fd.append('website', f.website.value);
			fd.append('province_id', f.getAttribute('data-province')); fd.append('kind', f.getAttribute('data-kind'));
			fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (r) { btn.disabled = false; msg.textContent = (r && r.data && r.data.message) || 'Có lỗi, vui lòng thử lại.'; msg.className = 'rg-email-msg ' + (r && r.success ? 'is-ok' : 'is-err'); if (r && r.success) { f.reset(); } })
				.catch(function () { btn.disabled = false; msg.textContent = 'Lỗi kết nối, vui lòng thử lại.'; msg.className = 'rg-email-msg is-err'; });
		});
	}
})();
</script>
<?php
get_footer();
