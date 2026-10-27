<?php
/**
 * Giao diện trang tài khoản (28/10/2026): Đăng nhập, Đăng ký, Quên mật khẩu, Đặt lại mật khẩu.
 *  - 2 cột: cột trái thương hiệu + 4 lợi ích + hình minh họa trụ sở (SVG tự vẽ), cột phải thẻ form.
 *  - Giữ đầu trang chung, ẩn chân trang ($GLOBALS['cvc_hide_chrome_footer']).
 *  - CSS assets/css/auth.css, JS assets/js/auth.js (hiện/ẩn mật khẩu).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_AUTH_PAGES = array( 'login', 'register', 'forgot-password', 'reset-password' );

add_action( 'wp_enqueue_scripts', 'cvc_auth_ui_assets', 30 );

function cvc_auth_ui_assets(): void {
	if ( ! in_array( (string) get_query_var( 'cvc_page' ), CVC_AUTH_PAGES, true ) ) {
		return;
	}
	wp_enqueue_style( 'cvc-auth', get_theme_file_uri( '/assets/css/auth.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/auth.css' ) ) );
	wp_enqueue_script( 'cvc-auth', get_theme_file_uri( '/assets/js/auth.js' ), array(), (string) filemtime( get_theme_file_path( '/assets/js/auth.js' ) ), true );
}

/** Biểu tượng nét (24×24). */
function cvc_auth_icon( string $name, string $class = 'au-ico' ): string {
	$p = array(
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
		'lock'     => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.4-4 4.4-6 8-6s6.6 2 8 6"/>',
		'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'eye-off'  => '<path d="M3 3l18 18"/><path d="M10.6 5.1Q11.3 5 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.2M6.6 6.6C3.9 8.3 2 12 2 12s3.6 7 10 7c1.8 0 3.4-.5 4.8-1.3"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
		'arrow'    => '<path d="M4 12h16M14 6l6 6-6 6"/>',
		'shield'   => '<path d="M12 3 4.5 6v5.5c0 5 3.3 8.4 7.5 9.5 4.2-1.1 7.5-4.5 7.5-9.5V6L12 3Z"/><path d="m8.8 12 2.3 2.3 4.2-4.6"/>',
		'book'     => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v14.5H6.5A2.5 2.5 0 0 0 4 20V5.5Z"/><path d="M4 20a2 2 0 0 0 2 2h14v-4.5"/><path d="M8.5 7.5h7M8.5 11h5"/>',
		'cap'      => '<path d="m2 9.5 10-5 10 5-10 5-10-5Z"/><path d="M6 11.5v4.7c3.2 2.6 8.8 2.6 12 0v-4.7"/><path d="M21.5 9.8V15"/>',
		'people'   => '<circle cx="9" cy="8" r="3.2"/><circle cx="17" cy="9" r="2.6"/><path d="M3 20c.6-3.6 3-5.6 6-5.6s5.4 2 6 5.6"/><path d="M15.6 14.6c2.6 0 4.6 1.6 5.3 4.6"/>',
		'bell'     => '<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4l2-2Z"/><path d="M10 21a2 2 0 0 0 4 0"/>',
		'devices'  => '<rect x="2" y="4" width="15" height="11" rx="1.5"/><path d="M6 19.5h7M9.5 15v4.5"/><rect x="17.5" y="8" width="4.5" height="11.5" rx="1"/>',
		'support'  => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="13" width="4" height="6.5" rx="1.5"/><rect x="17" y="13" width="4" height="6.5" rx="1.5"/><path d="M19 19.5c0 1.4-2 2.3-5 2.3"/>',
		'tag'      => '<path d="M3 12.5V4h8.5L21 13.5 13.5 21 3 12.5Z"/><circle cx="7.6" cy="8.2" r="1.5"/>',
		'bank'     => '<path d="M3 9.5 12 4l9 5.5"/><path d="M4 10.5h16M5.5 10.5v7.5M9.8 10.5v7.5M14.2 10.5v7.5M18.5 10.5v7.5M3 20.5h18"/>',
		'key'      => '<circle cx="8" cy="15" r="4.5"/><path d="m11.3 11.7 8.7-8.7M16.5 6.2l2.3 2.3M14.4 8.3l2 2"/>',
	);
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $p[ $name ] ?? '' ) . '</svg>';
}

/** Chữ G nhiều màu cho nút Google. */
function cvc_auth_google_mark(): string {
	return '<svg class="au-gmark" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>';
}

/** 4 lợi ích ở cột trái theo từng trang. */
function cvc_auth_benefits( string $mode ): array {
	if ( 'register' === $mode ) {
		return array(
			array( 'tag', 'Miễn phí đăng ký', 'Tạo tài khoản nhanh chóng, không mất phí' ),
			array( 'bell', 'Nhận thông báo mới', 'Tin tuyển dụng, văn bản pháp luật được cập nhật thường xuyên' ),
			array( 'devices', 'Đồng bộ trên nhiều thiết bị', 'Truy cập mọi lúc, mọi nơi' ),
			array( 'support', 'Hỗ trợ tận tâm', 'Đội ngũ hỗ trợ luôn sẵn sàng giải đáp thắc mắc' ),
		);
	}
	return array(
		array( 'book', 'Kho tài liệu phong phú', 'Văn bản pháp luật, tài liệu ôn thi cập nhật liên tục' ),
		array( 'cap', 'Bài thi trắc nghiệm', 'Luyện đề, thi thử theo cấu trúc mới nhất' ),
		array( 'people', 'Phù hợp nhiều đối tượng', 'Thí sinh, công chức, viên chức, đơn vị tuyển dụng' ),
		array( 'shield', 'An toàn, bảo mật', 'Bảo vệ thông tin cá nhân theo tiêu chuẩn cao' ),
	);
}

/**
 * Mở khung trang.
 *
 * @param array{mode:string, crumbs:array, title:string, sub?:string, desc?:string} $a
 */
function cvc_auth_start( array $a ): void {
	$GLOBALS['cvc_hide_chrome_footer'] = true;
	$mode = (string) ( $a['mode'] ?? 'login' );
	?>
	<main id="main" class="au au--<?php echo esc_attr( $mode ); ?>">
		<div class="au-waves" aria-hidden="true">
			<svg class="au-waves__svg" viewBox="0 250 1600 210" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><defs><linearGradient id="auNavy" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0d2645"/><stop offset="1" stop-color="#173c66"/></linearGradient><linearGradient id="auGold" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#e7c27a"/><stop offset=".5" stop-color="#d4a85a"/><stop offset="1" stop-color="#f0d39a"/></linearGradient></defs><!-- song vang + navy -->
<path d="M0 392 C 260 352, 520 376, 820 360 C 1100 345, 1350 300, 1600 262 L1600 460 L0 460 Z" fill="url(#auGold)"/>
<path d="M0 410 C 280 372, 560 398, 860 380 C 1130 364, 1380 322, 1600 290 L1600 460 L0 460 Z" fill="#f3dfae" opacity=".85"/>
<path d="M0 420 C 300 386, 600 410, 900 392 C 1160 377, 1390 338, 1600 304 L1600 460 L0 460 Z" fill="url(#auNavy)"/>
<path d="M0 420 C 300 386, 600 410, 900 392 C 1160 377, 1390 338, 1600 304" fill="none" stroke="#e2bd72" stroke-width="2.5"/>
</svg>
		</div>
		<div class="au-building" aria-hidden="true"><svg class="au-building__svg" viewBox="-40 84 840 300" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><defs><linearGradient id="auWall" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f6e4bd"/><stop offset="1" stop-color="#e9cf98"/></linearGradient><linearGradient id="auCol" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fffaf0"/><stop offset=".6" stop-color="#f7e9c9"/><stop offset="1" stop-color="#e2c48b"/></linearGradient></defs>

<!-- cay xa -->
<g fill="#c6c99a" opacity=".55">
<circle cx="70" cy="300" r="46"/><circle cx="120" cy="282" r="40"/><circle cx="600" cy="292" r="44"/><circle cx="650" cy="300" r="38"/><circle cx="700" cy="310" r="34"/><circle cx="25" cy="318" r="34"/>
</g>
<!-- cot co -->
<line x1="168" y1="96" x2="168" y2="372" stroke="#8d7b5a" stroke-width="4" stroke-linecap="round"/>
<circle cx="168" cy="94" r="5" fill="#c9a052"/>
<path d="M170 104 C190 98 206 110 226 104 C242 99 252 104 262 102 L262 152 C252 154 242 149 226 154 C206 160 190 148 170 154 Z" fill="#da251d"/>
<path d="M216 116 l4.4 13.6 h14.3 l-11.6 8.4 4.4 13.6 -11.5 -8.4 -11.6 8.4 4.4 -13.6 -11.6 -8.4 h14.3 z" fill="#ffd400" transform="translate(-0 -6) scale(1)"/>
<!-- toa nha: canh trai / phai -->
<g>
<rect x="196" y="246" width="150" height="122" fill="url(#auWall)"/>
<rect x="514" y="246" width="150" height="122" fill="url(#auWall)"/>
<rect x="190" y="238" width="162" height="10" fill="#e3c78f"/><rect x="508" y="238" width="162" height="10" fill="#e3c78f"/>
<rect x="196" y="300" width="150" height="5" fill="#e6cd99"/><rect x="514" y="300" width="150" height="5" fill="#e6cd99"/>
<g fill="#c49a5c">
<rect x="210" y="258" width="16" height="30" rx="7"/><rect x="240" y="258" width="16" height="30" rx="7"/><rect x="270" y="258" width="16" height="30" rx="7"/><rect x="300" y="258" width="16" height="30" rx="7"/><rect x="326" y="258" width="12" height="30" rx="6"/>
<rect x="210" y="316" width="16" height="34" rx="2"/><rect x="240" y="316" width="16" height="34" rx="2"/><rect x="270" y="316" width="16" height="34" rx="2"/><rect x="300" y="316" width="16" height="34" rx="2"/><rect x="326" y="316" width="12" height="34" rx="2"/>
<rect x="522" y="258" width="12" height="30" rx="6"/><rect x="544" y="258" width="16" height="30" rx="7"/><rect x="574" y="258" width="16" height="30" rx="7"/><rect x="604" y="258" width="16" height="30" rx="7"/><rect x="634" y="258" width="16" height="30" rx="7"/>
<rect x="522" y="316" width="12" height="34" rx="2"/><rect x="544" y="316" width="16" height="34" rx="2"/><rect x="574" y="316" width="16" height="34" rx="2"/><rect x="604" y="316" width="16" height="34" rx="2"/><rect x="634" y="316" width="16" height="34" rx="2"/>
</g>
</g>
<!-- than chinh + mai -->
<rect x="340" y="200" width="180" height="168" fill="url(#auWall)"/>
<rect x="384" y="150" width="92" height="40" fill="#f1dbab"/>
<path d="M378 152 Q430 108 482 152 Z" fill="#e6c88c"/>
<rect x="426" y="96" width="8" height="18" fill="#d9b46a"/>
<path d="M326 206 L430 154 L534 206 Z" fill="#f8ead0" stroke="#d6b376" stroke-width="3" stroke-linejoin="round"/>
<circle cx="430" cy="186" r="9" fill="#d9b46a"/>
<rect x="322" y="204" width="216" height="14" fill="#e8cd96"/>
<rect x="330" y="218" width="200" height="6" fill="#d9b97c"/>
<!-- hang cot -->
<g fill="url(#auCol)">
<rect x="344" y="226" width="14" height="124"/><rect x="374" y="226" width="14" height="124"/><rect x="404" y="226" width="14" height="124"/><rect x="442" y="226" width="14" height="124"/><rect x="472" y="226" width="14" height="124"/><rect x="502" y="226" width="14" height="124"/>
</g>
<g fill="#ecd3a1"><rect x="340" y="224" width="22" height="5"/><rect x="370" y="224" width="22" height="5"/><rect x="400" y="224" width="22" height="5"/><rect x="438" y="224" width="22" height="5"/><rect x="468" y="224" width="22" height="5"/><rect x="498" y="224" width="22" height="5"/></g>
<rect x="420" y="296" width="20" height="54" rx="9" fill="#b98b4c"/>
<!-- bac them -->
<rect x="318" y="350" width="224" height="8" fill="#ead3a2"/><rect x="306" y="358" width="248" height="8" fill="#e2c58c"/><rect x="180" y="366" width="500" height="8" fill="#d9b97c"/>
<!-- cay gan -->
<g>
<rect x="64" y="318" width="8" height="58" fill="#7b5e3c"/>
<circle cx="68" cy="300" r="34" fill="#8aa356"/><circle cx="48" cy="318" r="24" fill="#7c9849"/><circle cx="92" cy="314" r="26" fill="#9bb262"/><circle cx="70" cy="276" r="22" fill="#a6bb6b"/>
<rect x="726" y="320" width="8" height="54" fill="#7b5e3c"/>
<circle cx="730" cy="304" r="30" fill="#8aa356"/><circle cx="708" cy="320" r="20" fill="#7c9849"/><circle cx="752" cy="318" r="22" fill="#9bb262"/>
<circle cx="130" cy="352" r="16" fill="#93ad5d"/><circle cx="150" cy="356" r="12" fill="#86a152"/><circle cx="690" cy="356" r="14" fill="#93ad5d"/>
</g>
<path d="M0 372 Q400 352 800 372 L800 384 L0 384Z" fill="#ead9ad"/></svg></div>
		<div class="au-wrap">
			<div class="au-head">
				<a class="au-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Công Viên Chức - Trang chủ">
					<img src="<?php echo esc_url( cvc_brand_url( 'icon-192.png' ) ); ?>" alt="" width="96" height="96" decoding="async">
				</a>
				<div class="au-head__text">
					<p class="au-name">CÔNG VIÊN CHỨC</p>
					<p class="au-tagline">Ôn thi vững vàng · Công vụ tự tin</p>
				</div>
				<span class="au-rule" aria-hidden="true"></span>
			</div>
			<ul class="au-benefits">
				<?php foreach ( cvc_auth_benefits( $mode ) as $b ) : ?>
					<li>
						<span class="au-benefits__ico"><?php echo cvc_auth_icon( $b[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><b><?php echo esc_html( $b[1] ); ?></b><small><?php echo esc_html( $b[2] ); ?></small></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<section class="au-card" aria-labelledby="au-title">
				<nav class="au-crumbs" aria-label="Đường dẫn">
					<?php
					$crumbs = (array) ( $a['crumbs'] ?? array() );
					foreach ( $crumbs as $i => $c ) {
						echo $i ? '<span aria-hidden="true">/</span>' : '';
						echo ! empty( $c['url'] ) ? '<a href="' . esc_url( (string) $c['url'] ) . '">' . esc_html( (string) $c['label'] ) . '</a>' : '<span aria-current="page">' . esc_html( (string) $c['label'] ) . '</span>';
					}
					?>
				</nav>
				<h1 id="au-title" class="au-title"><?php echo esc_html( (string) $a['title'] ); ?></h1>
				<?php if ( ! empty( $a['sub'] ) ) : ?><p class="au-sub"><?php echo esc_html( (string) $a['sub'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $a['desc'] ) ) : ?><p class="au-desc"><?php echo esc_html( (string) $a['desc'] ); ?></p><?php endif; ?>
				<div class="au-notice"><?php cvc_render_notice(); ?></div>
	<?php
}

/** Đóng khung trang (kèm khối "An toàn & bảo mật" nếu cần). */
function cvc_auth_end( bool $secure_box = false ): void {
	if ( $secure_box ) :
		?>
		<div class="au-secure">
			<span class="au-secure__ico"><?php echo cvc_auth_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<p><b>An toàn &amp; bảo mật</b>Thông tin của bạn được mã hóa khi truyền và được bảo vệ theo <a href="<?php echo esc_url( home_url( '/chinh-sach-bao-mat/' ) ); ?>">Chính sách bảo mật</a>.</p>
		</div>
		<?php
	endif;
	?>
			</section>
		</div>
	</main>
	<?php
}

/** Ô nhập có biểu tượng. $row = kiểu trang đăng ký (biểu tượng tròn bên trái). */
function cvc_auth_field( array $f ): void {
	$id   = (string) $f['id'];
	$type = (string) ( $f['type'] ?? 'text' );
	$pw   = 'password' === $type;
	$row  = ! empty( $f['row'] );
	$attr = '';
	foreach ( (array) ( $f['attrs'] ?? array() ) as $k => $v ) {
		$attr .= true === $v ? ' ' . esc_attr( $k ) : ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $v ) . '"';
	}
	?>
	<div class="au-field<?php echo $row ? ' is-row' : ''; ?>"<?php echo ! empty( $f['wrap_attrs'] ) ? ' ' . $f['wrap_attrs'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php if ( $row ) : ?><span class="au-field__ico"><?php echo cvc_auth_icon( (string) $f['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
		<div class="au-field__main">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo $row ? '' : cvc_auth_icon( (string) $f['icon'], 'au-ico au-ico--label' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo isset( $f['label_html'] ) ? $f['label_html'] : esc_html( (string) $f['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
			<div class="au-input<?php echo $pw ? ' has-eye' : ''; ?>">
				<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( (string) ( $f['name'] ?? '' ) ); ?>"<?php echo $attr; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php if ( $pw ) : ?>
					<button type="button" class="au-eye" data-au-eye="<?php echo esc_attr( $id ); ?>" aria-label="Hiện mật khẩu" aria-pressed="false"><?php echo cvc_auth_icon( 'eye', 'au-ico au-ico--on' ) . cvc_auth_icon( 'eye-off', 'au-ico au-ico--off' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $f['hint'] ) ) : ?><small class="au-hint" id="<?php echo esc_attr( $id ); ?>-hint"><?php echo esc_html( (string) $f['hint'] ); ?></small><?php endif; ?>
			<?php if ( ! empty( $f['after'] ) ) { echo $f['after']; } // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
	<?php
}
