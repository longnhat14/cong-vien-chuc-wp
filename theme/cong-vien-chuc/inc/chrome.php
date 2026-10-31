<?php
/**
 * Header + footer dùng chung toàn site (18/10/2026) - giao diện trang chủ v17.
 *
 *  - cvc_chrome_header(): dòng trên + đầu trang (logo, menu chính cvc_main_nav_items, tìm kiếm,
 *    thông báo, tài khoản, nút menu di động). Dùng ở header.php, index.php, trang kết quả thi.
 *  - cvc_chrome_footer(): chân trang 4 cột như trang chủ.
 *  - CSS assets/css/site-chrome.css (sinh từ home-v17.css, class tiền tố ch-) + assets/js/site-chrome.js
 *    nạp trên mọi trang front-end.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'cvc_chrome_assets', 5 );
add_action( 'wp_head', 'cvc_brand_icons', 2 );
add_action( 'admin_head', 'cvc_brand_icons', 2 );

/** Biểu tượng tab trình duyệt / màn hình chính (bộ logo mới). */
function cvc_brand_icons(): void {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( cvc_brand_url( 'favicon.ico' ) ) );
	printf( '<link rel="icon" type="image/png" sizes="32x32" href="%s">' . "\n", esc_url( cvc_brand_url( 'icon-32.png' ) ) );
	printf( '<link rel="icon" type="image/png" sizes="192x192" href="%s">' . "\n", esc_url( cvc_brand_url( 'icon-192.png' ) ) );
	printf( '<link rel="apple-touch-icon" sizes="180x180" href="%s">' . "\n", esc_url( cvc_brand_url( 'icon-180.png' ) ) );
	echo '<meta name="theme-color" content="#0f2a4a">' . "\n";
}

function cvc_chrome_assets(): void {
	if ( is_admin() ) {
		return;
	}
	$css = get_theme_file_path( '/assets/css/site-chrome.css' );
	$js  = get_theme_file_path( '/assets/js/site-chrome.js' );
	wp_enqueue_style( 'cvc-chrome', get_theme_file_uri( '/assets/css/site-chrome.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-chrome', get_theme_file_uri( '/assets/js/site-chrome.js' ), array(), (string) filemtime( $js ), true );
}

/** Số thông báo chưa đọc (cache 60 giây theo token để không gọi API mỗi lượt tải trang). */
function cvc_chrome_unread(): int {
	$token = cvc_auth_token();
	if ( null === $token || ! class_exists( 'CVC_Notification_Service' ) ) {
		return 0;
	}
	$key    = 'cvc_unread_' . md5( $token );
	$cached = get_transient( $key );
	if ( false !== $cached ) {
		return (int) $cached;
	}
	$res = ( new CVC_Notification_Service() )->unreadCount( (string) $token );
	$n   = ! empty( $res['ok'] ) ? (int) ( $res['data']['data']['unread_count'] ?? 0 ) : 0;
	set_transient( $key, $n, 60 );

	return $n;
}

function cvc_chrome_icons(): array {
	return array(
		'arrow'  => '<svg class="ch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
		'plus'   => '<svg class="ch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="vertical-align:middle;width:17px" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg>',
		'menu'   => '<svg class="ch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
		'search' => '<svg class="ch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>',
		'bell'   => '<svg class="ch-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 9a6 6 0 1 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9Z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>',
		'exams'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3.5" width="14" height="17" rx="2"/><path d="M8 8h8M8 12h2m3 0h3M8 16h8"/></svg>',
		'rec'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="7" width="17" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3.5 12h17M10 12v2h4v-2"/></svg>',
		'legal'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 20 7v5c0 4.5-3.1 7.4-8 9-4.9-1.6-8-4.5-8-9V7l8-4Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>',
		'docs'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3.5h8l4 4V20a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z"/><path d="M14 3.5V8h4M8 12h8M8 16h8"/></svg>',
		'posts'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="4.5" width="17" height="15" rx="2"/><path d="M7 8.5h10M7 12h4.5M7 15.5h4.5M14 12h3v3.5h-3z"/></svg>',
		'link'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M8.5 12h7M12.5 9l3 3-3 3"/></svg>',
		'user'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>',
	);
}

/** Đường dẫn tệp trong bộ logo (assets/brand, lấy từ Cong_Vien_Chuc_Logo_Pack 26/10/2026). */
function cvc_brand_url( string $file ): string {
	return get_theme_file_uri( '/assets/brand/' . $file );
}

/** Logo: biểu tượng navy trên nền sáng (đầu trang); bản trắng-vàng trên nền tối (chân trang). */
function cvc_chrome_brand( array $ico, bool $light = false ): void {
	unset( $ico );
	$img = $light ? 'icon-light-128.png' : 'icon-128.png';
	?>
	<a class="ch-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Công Viên Chức - Trang chủ">
		<span class="ch-brand-mark"><img src="<?php echo esc_url( cvc_brand_url( $img ) ); ?>" alt="" width="50" height="50" decoding="async"></span>
		<span><span class="ch-brand-name">CÔNG VIÊN CHỨC</span><span class="ch-brand-sub">Ôn thi vững vàng · Công vụ tự tin</span></span>
	</a>
	<?php
}

function cvc_chrome_header(): void {
	$ico       = cvc_chrome_icons();
	$logged_in = function_exists( 'cvc_is_logged_in' ) ? cvc_is_logged_in() : null !== cvc_auth_token();
	$unread    = $logged_in ? cvc_chrome_unread() : 0;
	$staff     = $logged_in && function_exists( 'cvc_admin_is_staff' ) && cvc_admin_is_staff();
	$current   = home_url( add_query_arg( null, null ) );
	$is_home   = is_front_page() || untrailingslashit( strtok( $current, '?' ) ) === untrailingslashit( home_url( '/' ) );
	$active    = static function ( array $urls ) use ( $current, $is_home ): bool {
		if ( $is_home ) {
			return false;
		}
		foreach ( $urls as $u ) {
			if ( 0 === strpos( $current, (string) $u ) ) {
				return true;
			}
		}

		return false;
	};
	?>
	<?php $is_emp = $logged_in && function_exists( 'cvc_employer_of_current_user' ) && null !== cvc_employer_of_current_user(); // Tài khoản đơn vị: nút chính vào /don-vi/. ?>
	<div class="ch-topline"><div class="ch-container ch-inner"><span><?php echo $ico['plus']; // phpcs:ignore WordPress.Security.EscapeOutput ?> Cùng bạn chuẩn bị tốt hơn cho kỳ thi và hành trình công vụ</span><span class="ch-secondary">Tin tuyển dụng mới · Tài liệu · Ôn thi <a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Xem cập nhật →</a><?php if ( $staff ) : ?><a class="ch-staff-link" href="<?php echo esc_url( cvc_admin_url() ); ?>">Quản trị</a><?php endif; ?><?php if ( $is_emp ) : ?><a class="ch-staff-link" href="<?php echo esc_url( cvc_account_url() ); ?>">Học tập</a><?php endif; ?></span></div></div>
	<header class="ch-header">
		<div class="ch-container ch-nav">
			<?php cvc_chrome_brand( $ico ); ?>
			<?php $cvc_nav_n = count( cvc_main_nav_items() ); ?>
			<nav class="ch-nav-links<?php echo $cvc_nav_n >= 5 ? ' is-many' : ''; ?><?php echo $cvc_nav_n >= 6 ? ' is-crowded' : ''; ?>" id="chNavLinks" aria-label="Điều hướng chính">
				<?php
				foreach ( cvc_main_nav_items() as $nav ) :
					$svg  = $ico[ $nav['key'] ] ?? $ico['link'];
					$kids = (array) ( $nav['children'] ?? array() );
					$on   = $active( array_merge( array( $nav['url'] ), array_column( $kids, 'url' ) ) );
					if ( $kids ) :
						?>
				<div class="ch-nav-group<?php echo ! empty( $kids[0]['group'] ) ? ' has-mg' : ''; ?>">
					<a class="ch-nav-parent<?php echo $on ? ' is-active' : ''; ?>" href="<?php echo esc_url( $nav['url'] ); ?>" aria-haspopup="true" aria-expanded="false"<?php echo $on ? ' aria-current="true"' : ''; ?>><span class="ch-nav-icon"><?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( $nav['label'] ); ?></span><i><?php echo esc_html( $nav['hint'] ); ?></i><em class="ch-nav-caret" aria-hidden="true">▾</em></a>
					<?php if ( ! empty( $kids[0]['group'] ) ) : ?>
					<?php cvc_chrome_mega( (string) $nav['key'], $kids, $active ); ?>
					<?php else : ?>
					<div class="ch-nav-sub">
						<?php foreach ( $kids as $kid ) : ?>
						<a href="<?php echo esc_url( $kid['url'] ); ?>"<?php echo $active( array( $kid['url'] ) ) ? ' class="is-active" aria-current="page"' : ''; ?>><span><?php echo esc_html( $kid['label'] ); ?></span><?php if ( ! empty( $kid['badge'] ) ) : ?><small class="ch-nav-badge"><?php echo esc_html( $kid['badge'] ); ?></small><?php endif; ?></a>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>
					<?php else : ?>
				<a href="<?php echo esc_url( $nav['url'] ); ?>"<?php echo $on ? ' class="is-active" aria-current="page"' : ''; ?>><span class="ch-nav-icon"><?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( $nav['label'] ); ?></span><i><?php echo esc_html( $nav['hint'] ); ?></i><b aria-hidden="true">↗</b></a>
						<?php
					endif;
				endforeach;
				?>
				<div class="ch-mobile-acc">
					<?php if ( $logged_in ) : ?>
					<?php if ( $is_emp ) : ?><a class="ch-btn ch-btn-primary" href="<?php echo esc_url( home_url( '/don-vi/' ) ); ?>">Đơn vị</a><a class="ch-btn ch-btn-outline" href="<?php echo esc_url( cvc_account_url() ); ?>">Học tập</a>
					<?php else : ?><a class="ch-btn ch-btn-primary" href="<?php echo esc_url( cvc_account_url() ); ?>">Tài khoản</a><?php endif; ?>
					<?php if ( $staff ) : ?><a class="ch-btn ch-btn-outline" href="<?php echo esc_url( cvc_admin_url() ); ?>">Quản trị</a><?php endif; ?>
					<a class="ch-btn ch-btn-outline" href="<?php echo esc_url( cvc_logout_url() ); ?>">Đăng xuất</a>
					<?php else : ?>
					<a class="ch-btn ch-btn-outline" href="<?php echo esc_url( cvc_login_url( $current ) ); ?>">Đăng nhập</a>
					<a class="ch-btn ch-btn-primary" href="<?php echo esc_url( cvc_register_url() ); ?>">Tạo tài khoản</a>
					<?php endif; ?>
				</div>
			</nav>
			<div class="ch-nav-actions">
				<a class="ch-iconbtn" href="<?php echo esc_url( cvc_search_url() ); ?>" data-ch-search aria-label="Tìm kiếm"><?php echo $ico['search']; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php if ( $logged_in ) : ?>
				<a class="ch-iconbtn" href="<?php echo esc_url( cvc_account_url( 'notifications' ) ); ?>" aria-label="<?php echo esc_attr( $unread > 0 ? sprintf( 'Thông báo: %d chưa đọc', $unread ) : 'Thông báo' ); ?>"><?php echo $ico['bell']; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ( $unread > 0 ) : ?><span class="ch-count"><?php echo esc_html( $unread > 9 ? '9+' : (string) $unread ); ?></span><?php endif; ?></a>
				<a class="ch-btn ch-btn-primary ch-btn-small ch-acc-btn" href="<?php echo esc_url( $is_emp ? home_url( '/don-vi/' ) : cvc_account_url() ); ?>"><?php echo function_exists( 'cvc_current_avatar_html' ) ? cvc_current_avatar_html( 22, 'ch-acc-avatar' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $is_emp ? 'Đơn vị' : 'Tài khoản'; ?> <?php echo $ico['arrow']; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php else : ?>
				<a class="ch-btn ch-btn-outline ch-btn-small" href="<?php echo esc_url( cvc_login_url( $current ) ); ?>">Đăng nhập</a>
				<a class="ch-btn ch-btn-primary ch-btn-small" href="<?php echo esc_url( cvc_register_url() ); ?>">Tạo tài khoản <?php echo $ico['arrow']; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
				<button class="ch-menu-toggle" id="chMenuToggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="chNavLinks"><?php echo $ico['menu']; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		</div>
	</header>
	<?php
	if ( function_exists( 'cvc_email_verify_banner' ) ) {
		cvc_email_verify_banner(); // 27/10/2026: nhắc xác minh email
	}
}

/**
 * Menu con dạng menu lớn v3 (31/10/2026): các cột theo nhóm (Luyện tập, Đánh giá, Theo đối tượng, Theo loại hình,
 * Theo ngành & công cụ) + thẻ điểm nhấn bên phải + chân menu (Học thêm, liên kết chân). Icon nét SVG (cvc_nav_icon),
 * số tin đang mở và số liệu thật (cvc_nav_stats). Dữ liệu từ menu WordPress - quy ước ở inc/nav-menu.php.
 */
function cvc_chrome_mega( string $key, array $kids, callable $active ): void {
	$heads = array(
		'practice' => 'Luyện tập',
		'assess'   => 'Đánh giá',
		'audience' => 'Theo đối tượng',
		'type'     => 'Theo loại hình',
		'field'    => 'Theo ngành & công cụ',
	);
	$by = array();
	foreach ( $kids as $k ) {
		$by[ (string) ( $k['group'] ?? '' ) ][] = $k;
	}
	$cols  = array_values( array_filter( array_keys( $heads ), static fn ( $g ) => ! empty( $by[ $g ] ) ) );
	$cards = (array) ( $by['card'] ?? array() );
	$more  = (array) ( $by['more'] ?? array() );
	$foot  = (array) ( $by['foot'] ?? array() );
	$has   = static function () {
		return function_exists( 'cvc_nav_icon' );
	};
	$tile = static function ( array $k ) use ( $has ): string {
		if ( '' !== (string) ( $k['icon'] ?? '' ) && $has() ) {
			return '<span class="ch-mg-tile" aria-hidden="true">' . cvc_nav_icon( (string) $k['icon'] ) . '</span>';
		}
		return '<span class="ch-mg-tile is-sym" aria-hidden="true">' . esc_html( '' !== (string) ( $k['sym'] ?? '' ) ? (string) $k['sym'] : '•' ) . '</span>';
	};
	$go = $has() ? cvc_nav_icon( 'mui-ten' ) : '→';
	?>
	<div class="ch-nav-sub ch-mega ch-mg is-<?php echo esc_attr( $key ); ?><?php echo $cards ? ' has-card' : ''; ?><?php echo count( $cols ) <= 2 ? ' is-narrow' : ''; ?>" style="--mg-cols:<?php echo (int) max( 1, count( $cols ) ); ?>">
		<div class="ch-mg-panel">
			<i class="ch-mg-caret" aria-hidden="true"></i>
			<div class="ch-mg-grid">
				<?php foreach ( $cols as $g ) : ?>
				<div class="ch-mg-col is-<?php echo esc_attr( $g ); ?>">
					<p class="ch-mega-h"><?php echo esc_html( $heads[ $g ] ); ?></p>
					<?php if ( 'audience' === $g ) : ?><div class="ch-mg-pills"><?php endif; ?>
					<?php foreach ( $by[ $g ] as $k ) : ?>
						<?php
						$on  = $active( array( $k['url'] ) );
						$cnt = ! empty( $k['count'] ) && function_exists( 'cvc_nav_count_for' ) ? cvc_nav_count_for( (string) $k['url'] ) : null;
						?>
						<a class="ch-mega-item<?php echo 'audience' === $g ? ' is-pill' : ''; ?><?php echo $on ? ' is-active' : ''; ?>" href="<?php echo esc_url( $k['url'] ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?><?php echo '_blank' === ( $k['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
							<?php echo $tile( $k ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="ch-mega-txt"><b><?php echo esc_html( $k['label'] ); ?><?php if ( null !== $cnt ) : ?><em class="ch-mg-count" title="Tin đang nhận hồ sơ"><?php echo esc_html( number_format_i18n( $cnt ) ); ?></em><?php endif; ?><?php if ( ! empty( $k['badge'] ) ) : ?><small class="ch-nav-badge"><?php echo esc_html( $k['badge'] ); ?></small><?php endif; ?></b><?php if ( 'audience' !== $g && ! empty( $k['desc'] ) ) : ?><small><?php echo esc_html( $k['desc'] ); ?></small><?php endif; ?></span>
							<?php if ( 'audience' !== $g ) : ?><span class="ch-mg-go" aria-hidden="true"><?php echo $go; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
						</a>
					<?php endforeach; ?>
					<?php if ( 'audience' === $g ) : ?></div><?php endif; ?>
				</div>
				<?php endforeach; ?>
				<?php if ( $cards ) : ?>
				<div class="ch-mg-col is-card">
					<?php foreach ( $cards as $c ) : ?>
						<?php
						$big  = '' !== (string) ( $c['sym'] ?? '' ) && function_exists( 'cvc_nav_fill' ) ? cvc_nav_fill( (string) $c['sym'] ) : null;
						$para = '' !== (string) ( $c['desc'] ?? '' ) && function_exists( 'cvc_nav_fill' ) ? cvc_nav_fill( (string) $c['desc'] ) : null;
						?>
						<a class="ch-mg-card<?php echo 'bronze' === ( $c['variant'] ?? '' ) ? ' is-bronze' : ''; ?>" href="<?php echo esc_url( $c['url'] ); ?>">
							<svg class="ch-mg-deco" viewBox="0 0 170 170" fill="none" aria-hidden="true"><circle cx="85" cy="85" r="80" stroke="#f0cf91" stroke-opacity=".35"/><circle cx="85" cy="85" r="58" stroke="#f0cf91" stroke-opacity=".25"/><circle cx="85" cy="85" r="36" stroke="#f0cf91" stroke-opacity=".18"/></svg>
							<span class="ch-mg-eb"><?php echo function_exists( 'cvc_nav_icon' ) ? cvc_nav_icon( '' !== (string) ( $c['icon'] ?? '' ) ? (string) $c['icon'] : 'sao' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c['label'] ); ?></span>
							<?php if ( null !== $big ) : ?><span class="ch-mg-big"><?php echo $big; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
							<?php if ( null !== $para ) : ?><span class="ch-mg-p"><?php echo $para; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
							<span class="ch-mg-cta"><?php echo esc_html( '' !== (string) ( $c['badge'] ?? '' ) ? (string) $c['badge'] : 'Xem ngay' ); ?> <?php echo $go; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
			<?php if ( $more || $foot ) : ?>
			<div class="ch-mg-foot">
				<?php if ( $more ) : ?>
					<span class="ch-mg-foot-h">Học thêm</span>
					<?php foreach ( $more as $k ) : ?><a class="ch-mg-dot<?php echo $active( array( $k['url'] ) ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $k['url'] ); ?>"><?php echo esc_html( $k['label'] ); ?><?php if ( ! empty( $k['badge'] ) ) : ?> <small class="ch-nav-badge"><?php echo esc_html( $k['badge'] ); ?></small><?php endif; ?></a><?php endforeach; ?>
				<?php endif; ?>
				<?php foreach ( $foot as $k ) : ?>
					<?php $note = '' !== (string) ( $k['desc'] ?? '' ) && function_exists( 'cvc_nav_fill' ) ? cvc_nav_fill( (string) $k['desc'] ) : null; ?>
					<?php if ( null !== $note ) : ?><span class="ch-mg-note"><?php echo $note; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
					<a class="ch-mg-all" href="<?php echo esc_url( $k['url'] ); ?>"><?php echo esc_html( $k['label'] ); ?> <?php echo $go; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

function cvc_chrome_footer(): void {
	$ico       = cvc_chrome_icons();
	$logged_in = function_exists( 'cvc_is_logged_in' ) ? cvc_is_logged_in() : null !== cvc_auth_token();
	?>
	<footer class="ch-footer">
		<div class="ch-container">
			<div class="ch-footer-grid">
				<div><?php cvc_chrome_brand( $ico, true ); ?><p>Nền tảng hỗ trợ ôn thi công chức, viên chức, tra cứu văn bản, tìm tài liệu và theo dõi thông báo tuyển dụng.</p></div>
				<div><h3>Ôn thi</h3><div class="ch-footer-links"><a href="<?php echo esc_url( cvc_exams_mode_url( 'on-tap' ) ); ?>">Luyện thi trắc nghiệm</a><a href="<?php echo esc_url( cvc_exams_mode_url( 'thi-that' ) ); ?>">Thi thử trắc nghiệm</a><a href="<?php echo esc_url( cvc_question_bank_url() ); ?>">Ngân hàng câu hỏi</a><a href="<?php echo esc_url( cvc_saved_questions_url() ); ?>">Câu hỏi đã lưu</a><a href="<?php echo esc_url( home_url( '/bo-de-pdf/' ) ); ?>">Bộ đề PDF có đáp án</a></div></div>
				<div><h3>Tra cứu</h3><div class="ch-footer-links"><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tin tuyển dụng</a><a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Văn bản pháp luật</a><a href="<?php echo esc_url( cvc_documents_url() ); ?>">Mẫu hồ sơ &amp; tài liệu</a><a href="<?php echo esc_url( home_url( '/tinh-luong/' ) ); ?>">Ước tính lương</a><a href="<?php echo esc_url( home_url( '/don-vi-tuyen-dung/' ) ); ?>">Dành cho đơn vị tuyển dụng</a></div></div>
				<div><h3>Tài khoản</h3><div class="ch-footer-links"><?php if ( $logged_in ) : ?><a href="<?php echo esc_url( cvc_account_url( 'conquest' ) ); ?>">Hồ sơ chinh phục kỳ thi</a><a href="<?php echo esc_url( cvc_account_url() ); ?>">Tài khoản của tôi</a><a href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">Lịch sử thi</a><a href="<?php echo esc_url( cvc_logout_url() ); ?>">Đăng xuất</a><?php else : ?><a href="<?php echo esc_url( cvc_login_url() ); ?>">Đăng nhập</a><a href="<?php echo esc_url( cvc_register_url() ); ?>">Đăng ký miễn phí</a><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a><?php endif; ?></div></div>
			</div>
			<div class="ch-footer-bottom"><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Công Viên Chức. Tất cả quyền được bảo lưu. · <a href="<?php echo esc_url( home_url( '/chinh-sach-bao-mat/' ) ); ?>">Chính sách bảo mật</a> · <a href="<?php echo esc_url( home_url( '/dieu-khoan-su-dung/' ) ); ?>">Điều khoản sử dụng</a> · <a href="mailto:info@congvienchuc.com">info@congvienchuc.com</a></span><span>Thông tin chỉ mang tính tham khảo; vui lòng đối chiếu văn bản và thông báo gốc từ cơ quan ban hành.</span></div>
		</div>
	</footer>
	<?php
}
