<?php
/**
 * Cột phải dùng chung, dải vinh danh chân trang, vinh danh trang chủ / cuối bài viết, trang /moi-cap-nhat/
 * (12/11/2026, claude/ke-hoach-vinh-danh-va-lien-ket-khap-noi.md).
 *
 * cvc_side_feed( $ctx, $layout ): 3 khối render sẵn trong HTML (Google đọc được):
 *   - Tin tuyển dụng đang mở theo ngữ cảnh (văn bản dẫn chiếu → tỉnh → ngành → mới nhất) - GET /api/side-feed
 *   - Bài viết: 2 bài mới đăng + 3 bài theo cụm luật / ngành, xoay vòng theo trang (cvc_kt_blog_pick)
 *   - Vinh danh tuần: top Chăm chỉ (tên theo cài đặt hiển thị của từng người) - GET /api/fame-strip
 * $ctx: text (tiêu đề / chủ đề trang), doc (mảng văn bản), province (id), family (job_family), seed, slot (tiền tố đo lượt bấm),
 *       blocks (rec, blog, fame), new_tab (mở tab mới - trang làm bài).
 * $layout: side (cột phải) | band (dải 3 cột dưới nội dung) | compact (trang làm bài).
 * Mọi link mang data-cvc-go="<slot>-rec|blog|fame" → bảng link_clicks_daily.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cụm luật → job_family của tin tuyển dụng. */
function cvc_sf_family_of( string $cluster ): string {
	return array( 'y-te' => 'health', 'khcn' => 'science', 'giao-duc' => 'education', 'tu-phap' => 'justice' )[ $cluster ] ?? '';
}

function cvc_sf_api( string $path, array $q, int $ttl ): array {
	$key = 'cvc_sf_' . md5( $path . wp_json_encode( $q ) );
	$hit = get_transient( $key );
	if ( is_array( $hit ) ) {
		return $hit;
	}
	$res  = ( new CVC_Api_Client( null, 6 ) )->get( $path, array_filter( $q, static fn ( $v ) => null !== $v && '' !== $v && 0 !== $v ) );
	$data = ! empty( $res['ok'] ) && is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : array();
	set_transient( $key, $data, $data ? $ttl : 60 );

	return $data;
}

function cvc_sf_fame(): array {
	return cvc_sf_api( '/api/fame-strip', array(), 5 * MINUTE_IN_SECONDS );
}

function cvc_sf_recs( string $family, int $province, int $doc, int $n = 5 ): array {
	return cvc_sf_api( '/api/side-feed', array( 'family' => $family, 'province' => $province, 'doc' => $doc, 'limit' => $n ), 10 * MINUTE_IN_SECONDS );
}

function cvc_sf_num( $v ): string {
	$f = (float) $v;

	return number_format_i18n( $f, floor( $f ) == $f ? 0 : 1 );
}

function cvc_sf_date( ?string $d ): string {
	$ts = $d ? strtotime( $d ) : false;

	return $ts ? wp_date( 'd/m', $ts ) : '';
}

function cvc_sf_css(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	echo '<style id="cvc-sf-css">'
		. '.cvc-sf{display:grid;gap:14px;margin:16px 0;font-family:inherit}.cvc-sf.is-band{grid-template-columns:repeat(auto-fit,minmax(260px,1fr));max-width:1200px;margin:24px auto;padding:0 16px}'
		. '.cvc-sf-box{background:#fff;border:1px solid #dfe4ea;border-radius:12px;padding:12px 14px;min-width:0}.cvc-sf-h{display:flex;justify-content:space-between;align-items:baseline;gap:8px;margin:0 0 8px}.cvc-sf-h b{font-size:15px;color:#123a63;font-weight:800}.cvc-sf-h a{font-size:12.5px;font-weight:700;color:#1767aa;white-space:nowrap;text-decoration:none}'
		. '.cvc-sf ul{list-style:none;margin:0;padding:0;display:grid;gap:8px}.cvc-sf li{font-size:14px;line-height:1.4;min-width:0}.cvc-sf li a{color:#14263b;text-decoration:none;font-weight:600;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.cvc-sf li a:hover{color:#1767aa;text-decoration:underline}.cvc-sf li small{display:block;color:#5c6672;font-size:12px;margin-top:2px}'
		. '.cvc-sf-fame li{display:grid;grid-template-columns:24px 1fr auto;gap:8px;align-items:center}.cvc-sf-rk{display:inline-grid;place-items:center;width:24px;height:24px;border-radius:50%;background:#eef3f8;color:#123a63;font-weight:800;font-size:12px}.cvc-sf-rk.r1{background:#f6d77a}.cvc-sf-rk.r2{background:#dfe3e8}.cvc-sf-rk.r3{background:#efc9a4}.cvc-sf-fame li a{-webkit-line-clamp:1}.cvc-sf-pt{font-size:12.5px;color:#123a63;font-weight:800;white-space:nowrap;font-variant-numeric:tabular-nums}.cvc-sf-note{margin:8px 0 0;font-size:12.5px;color:#5c6672}.cvc-sf-note a{color:#1767aa;font-weight:700}'
		. '.cvc-sf.is-compact .cvc-sf-box{padding:10px 12px}.cvc-sf.is-compact li{font-size:13px}.cvc-sf.is-compact .cvc-sf-h b{font-size:14px}'
		. '.cvc-fstrip{border-bottom:1px solid rgba(255,255,255,.14);padding:12px 0;margin-bottom:14px;display:flex;flex-wrap:wrap;gap:8px 22px;font-size:14px;line-height:1.5}.cvc-fstrip a{color:inherit;text-decoration:underline;text-underline-offset:2px}.cvc-fstrip b{font-weight:800}'
		. '.cvc-home-fame{max-width:1200px;margin:24px auto;padding:0 16px}.cvc-home-fame-head{display:flex;justify-content:space-between;align-items:baseline;gap:12px;margin-bottom:10px}.cvc-home-fame-head h2{margin:0;font-size:22px;color:#123a63}.cvc-home-fame-head a{font-weight:700;color:#1767aa;white-space:nowrap}.cvc-home-fame-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px}'
		. '.cvc-post-tail{margin:24px 0 8px;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}'
		. '</style>';
}

/** Khối vinh danh tuần (top Chăm chỉ). */
function cvc_sf_fame_box( string $slot, int $n = 5, string $target = '' ): string {
	$f  = cvc_sf_fame();
	$ef = (array) ( $f['effort'] ?? array() );
	$it = array_slice( (array) ( $ef['items'] ?? array() ), 0, $n );
	if ( ! $it ) {
		return '';
	}
	$fame = esc_url( home_url( '/vinh-danh/' ) );
	$h    = '<section class="cvc-sf-box cvc-sf-fame" aria-label="Vinh danh tuần"><p class="cvc-sf-h"><b>Vinh danh tuần ' . esc_html( (string) preg_replace( '/^\d{4}-W0?/', '', (string) ( $f['week'] ?? '' ) ) ) . '</b><a href="' . $fame . '" data-cvc-go="' . esc_attr( $slot ) . '-fame"' . $target . '>Xem bảng →</a></p><ul>';
	foreach ( $it as $r ) {
		$rk = (int) $r['rank'];
		$h .= '<li><span class="cvc-sf-rk' . ( $rk <= 3 ? ' r' . $rk : '' ) . '">' . $rk . '</span><a href="' . $fame . '" data-cvc-go="' . esc_attr( $slot ) . '-fame"' . $target . '>' . esc_html( (string) $r['name'] ) . ( ! empty( $r['province'] ) ? ' <small>' . esc_html( (string) $r['province'] ) . '</small>' : '' ) . '</a><span class="cvc-sf-pt">' . esc_html( cvc_sf_num( $r['score'] ) ) . ' điểm</span></li>';
	}
	$h .= '</ul><p class="cvc-sf-note">' . esc_html( number_format_i18n( (int) ( $ef['participants'] ?? 0 ) ) ) . ' người đang ôn tuần này · <a href="' . esc_url( home_url( '/xep-hang/' ) ) . '" data-cvc-go="' . esc_attr( $slot ) . '-fame"' . $target . '>Bảng xếp hạng</a></p></section>';

	return $h;
}

/** Khối tin tuyển dụng đang mở. */
function cvc_sf_rec_box( array $data, string $slot, int $n, string $target = '' ): string {
	$items = array_slice( (array) ( $data['items'] ?? array() ), 0, $n );
	if ( ! $items ) {
		return '';
	}
	$h = '<section class="cvc-sf-box" aria-label="Tin tuyển dụng đang mở"><p class="cvc-sf-h"><b>Tin tuyển dụng đang mở</b><a href="' . esc_url( cvc_recruitments_url() ) . '" data-cvc-go="' . esc_attr( $slot ) . '-rec"' . $target . '>Tất cả ' . esc_html( number_format_i18n( (int) ( $data['open'] ?? 0 ) ) ) . ' →</a></p><ul>';
	foreach ( $items as $r ) {
		$meta = array_filter( array( $r['province'] ?? '', ! empty( $r['quota'] ) ? (int) $r['quota'] . ' chỉ tiêu' : '', ! empty( $r['deadline'] ) ? 'hạn ' . cvc_sf_date( (string) $r['deadline'] ) : '' ) );
		$h   .= '<li><a href="' . esc_url( cvc_recruitment_url( (string) $r['slug'] ) ) . '" data-cvc-go="' . esc_attr( $slot ) . '-rec"' . $target . '>' . esc_html( (string) $r['title'] ) . '</a>' . ( $meta ? '<small>' . esc_html( implode( ' · ', $meta ) ) . '</small>' : '' ) . '</li>';
	}

	return $h . '</ul></section>';
}

/** Bài viết: 2 bài mới + 3 bài theo ngữ cảnh (xoay vòng theo trang). */
function cvc_sf_blog_items( array $ctx, int $n = 5 ): array {
	$posts = function_exists( 'cvc_blog_posts_index' ) ? cvc_blog_posts_index() : array();
	if ( ! $posts ) {
		return array();
	}
	$out  = array();
	$seen = array_flip( array_map( 'intval', (array) ( $ctx['exclude'] ?? array() ) ) );
	foreach ( array_slice( $posts, 0, 2 ) as $p ) {
		if ( ! isset( $seen[ $p['id'] ] ) ) {
			$out[]               = $p + array( 'new' => true );
			$seen[ $p['id'] ]    = true;
		}
	}
	if ( function_exists( 'cvc_kt_blog_pick' ) ) {
		$doc  = is_array( $ctx['doc'] ?? null ) ? $ctx['doc'] : array( 'title' => (string) ( $ctx['text'] ?? '' ) );
		$pick = cvc_kt_blog_pick( $doc, (string) ( $ctx['seed'] ?? ( $_SERVER['REQUEST_URI'] ?? '' ) ), 8 );
		foreach ( array_merge( array_filter( array( $pick['inline'] ) ), $pick['side'] ) as $p ) {
			if ( count( $out ) >= $n ) {
				break;
			}
			if ( ! isset( $seen[ $p['id'] ] ) ) {
				$out[]            = $p;
				$seen[ $p['id'] ] = true;
			}
		}
	}

	return array_slice( $out, 0, $n );
}

function cvc_sf_blog_box( array $items, string $slot, string $target = '' ): string {
	if ( ! $items ) {
		return '';
	}
	$h = '<section class="cvc-sf-box" aria-label="Bài viết hướng dẫn"><p class="cvc-sf-h"><b>Bài viết hướng dẫn</b><a href="' . esc_url( home_url( '/bai-viet/' ) ) . '" data-cvc-go="' . esc_attr( $slot ) . '-blog"' . $target . '>Tất cả →</a></p><ul>';
	foreach ( $items as $p ) {
		$h .= '<li><a href="' . esc_url( $p['url'] ) . '" data-cvc-go="' . esc_attr( $slot ) . '-blog"' . $target . '>' . esc_html( $p['title'] ) . '</a>' . ( ! empty( $p['new'] ) ? '<small>Mới đăng ' . esc_html( cvc_sf_date( (string) $p['date'] ) ) . '</small>' : '' ) . '</li>';
	}

	return $h . '</ul></section>';
}

/** Render cột phải / dải. */
function cvc_side_feed( array $ctx = array(), string $layout = 'side' ): void {
	$slot    = preg_replace( '/[^a-z0-9]/', '', (string) ( $ctx['slot'] ?? 'sf' ) ) ?: 'sf';
	$blocks  = (array) ( $ctx['blocks'] ?? array( 'rec', 'blog', 'fame' ) );
	$compact = 'compact' === $layout;
	$target  = ! empty( $ctx['new_tab'] ) ? ' target="_blank" rel="noopener"' : '';
	$doc     = is_array( $ctx['doc'] ?? null ) ? $ctx['doc'] : null;
	$cluster = function_exists( 'cvc_law_cluster_of' ) ? cvc_law_cluster_of( $doc ?: array( 'title' => (string) ( $ctx['text'] ?? '' ) ) ) : 'cong-vu';
	$family  = (string) ( $ctx['family'] ?? '' ) ?: cvc_sf_family_of( $cluster );
	$html    = '';
	foreach ( $blocks as $b ) {
		if ( 'rec' === $b ) {
			$html .= cvc_sf_rec_box( cvc_sf_recs( $family, (int) ( $ctx['province'] ?? 0 ), (int) ( $doc['id'] ?? 0 ) ), $slot, $compact ? 3 : 5, $target );
		} elseif ( 'blog' === $b ) {
			$html .= cvc_sf_blog_box( cvc_sf_blog_items( $ctx, $compact ? 3 : 5 ), $slot, $target );
		} elseif ( 'fame' === $b ) {
			$html .= cvc_sf_fame_box( $slot, $compact ? 3 : 5, $target );
		}
	}
	if ( '' === $html ) {
		return;
	}
	cvc_sf_css();
	echo '<div class="cvc-sf is-' . esc_attr( $layout ) . '">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape từng phần.
}

/* ---------- Dải chân trang toàn site ---------- */
function cvc_footer_strip(): void {
	if ( in_array( (string) get_query_var( 'cvc_page' ), array( 'admin', 'admin-ai-settings', 'exam-attempt' ), true ) ) {
		return;
	}
	$f     = cvc_sf_fame();
	$names = array_map( static fn ( $r ) => (string) $r['name'], array_slice( (array) ( $f['effort']['items'] ?? array() ), 0, 3 ) );
	$rec   = (array) ( cvc_sf_recs( '', 0, 0, 1 )['items'][0] ?? array() );
	$posts = function_exists( 'cvc_blog_posts_index' ) ? array_slice( cvc_blog_posts_index(), 0, 1 ) : array();
	cvc_sf_css();
	echo '<div class="cvc-fstrip">';
	if ( $names ) {
		echo '<span>★ <b>Vinh danh tuần ' . esc_html( (string) preg_replace( '/^\d{4}-W0?/', '', (string) ( $f['week'] ?? '' ) ) ) . ':</b> <a href="' . esc_url( home_url( '/vinh-danh/' ) ) . '" data-cvc-go="ft-fame">' . esc_html( implode( ' · ', $names ) ) . '</a></span>';
	}
	echo '<span><b>Mới đăng:</b> ';
	$parts = array();
	if ( $rec ) {
		$parts[] = '<a href="' . esc_url( cvc_recruitment_url( (string) $rec['slug'] ) ) . '" data-cvc-go="ft-rec">' . esc_html( wp_trim_words( (string) $rec['title'], 12, '…' ) ) . '</a>';
	}
	if ( $posts ) {
		$parts[] = '<a href="' . esc_url( $posts[0]['url'] ) . '" data-cvc-go="ft-blog">' . esc_html( wp_trim_words( (string) $posts[0]['title'], 12, '…' ) ) . '</a>';
	}
	$parts[] = '<a href="' . esc_url( home_url( '/moi-cap-nhat/' ) ) . '" data-cvc-go="ft-new">Tất cả mới cập nhật →</a>';
	echo implode( ' · ', $parts ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* ---------- Trang chủ: Bảng vinh danh tuần ---------- */
function cvc_render_home_fame(): void {
	$f = cvc_sf_fame();
	if ( empty( $f['effort']['items'] ) ) {
		return;
	}
	cvc_sf_css();
	$score = array_slice( (array) ( $f['score']['items'] ?? array() ), 0, 3 );
	?>
	<section class="cvc-home-fame" aria-labelledby="cvc-home-fame-h">
		<div class="cvc-home-fame-head"><h2 id="cvc-home-fame-h">Bảng vinh danh tuần này</h2><a href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>" data-cvc-go="hm-fame">Xem đầy đủ →</a></div>
		<div class="cvc-home-fame-grid cvc-sf">
			<?php echo cvc_sf_fame_box( 'hm', 5 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $score ) : ?>
				<section class="cvc-sf-box cvc-sf-fame" aria-label="Điểm thi thử cao nhất"><p class="cvc-sf-h"><b>Điểm thi thử cao nhất</b><a href="<?php echo esc_url( home_url( '/xep-hang/?bang=diem' ) ); ?>" data-cvc-go="hm-fame">Bảng điểm →</a></p><ul>
					<?php foreach ( $score as $r ) : ?>
						<?php $rk = (int) $r['rank']; ?>
						<li><span class="cvc-sf-rk<?php echo $rk <= 3 ? ' r' . (int) $rk : ''; ?>"><?php echo (int) $rk; ?></span><a href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>" data-cvc-go="hm-fame"><?php echo esc_html( (string) $r['name'] ); ?></a><span class="cvc-sf-pt"><?php echo esc_html( cvc_sf_num( $r['score'] ) ); ?>%</span></li>
					<?php endforeach; ?>
				</ul><p class="cvc-sf-note">Trung bình điểm các đề thi thật trong tuần, tối thiểu 2 đề.</p></section>
			<?php endif; ?>
			<section class="cvc-sf-box"><p class="cvc-sf-h"><b>Có tên trên bảng</b></p><p class="cvc-sf-note" style="margin-top:0">Mỗi ngày làm Bài ôn hôm nay hoặc luyện vài chục câu là có điểm Chăm chỉ. Top 1, 3, 10 nhận huy hiệu khi tuần kết thúc.</p><p class="cvc-sf-note"><a href="<?php echo esc_url( home_url( '/luyen-thi-trac-nghiem/' ) ); ?>" data-cvc-go="hm-fame">Luyện thi ngay →</a></p></section>
		</div>
	</section>
	<?php
}

/* ---------- Cuối bài viết blog: vinh danh tuần + tin tuyển dụng cùng ngành / tỉnh ---------- */
add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id   = (int) get_the_ID();
		$lc   = function_exists( 'cvc_live_context' ) ? cvc_live_context( $id ) : null;
		$text = (string) get_the_title( $id );
		$fam  = cvc_sf_family_of( function_exists( 'cvc_law_cluster_of' ) ? cvc_law_cluster_of( array( 'title' => $text ) ) : '' );
		ob_start();
		cvc_sf_css();
		echo '<div class="cvc-post-tail cvc-sf no-autolink">';
		echo cvc_sf_rec_box( cvc_sf_recs( $fam, (int) ( $lc['province_id'] ?? 0 ), 0, 3 ), 'bp', 3 ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo cvc_sf_fame_box( 'bp', 3 ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';

		return $content . (string) ob_get_clean();
	},
	30
);
