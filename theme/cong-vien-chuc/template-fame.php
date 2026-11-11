<?php
/**
 * /vinh-danh/ - Bảng vinh danh (11/11/2026): top 10 Chăm chỉ + top 10 Điểm thi thử của tuần / tháng, các kỳ trước.
 * /vinh-danh/the/?b={board}&c={mã} - trang chia sẻ 1 người (ảnh OG vẽ ở /api/fame/card).
 * Ảnh thật chỉ hiện khi người dùng đồng ý vinh danh và ảnh đã qua kiểm duyệt (API lọc sẵn).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$g     = static fn ( string $k ): string => sanitize_text_field( wp_unslash( (string) ( $_GET[ $k ] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
$api   = new CVC_Api_Client();

if ( 'fame-card' === get_query_var( 'cvc_page' ) ) {
	cvc_fame_render_card_page( $g( 'b' ), $g( 'c' ), $token );
	return;
}

$ky   = 'month' === $g( 'ky' ) ? 'month' : 'week';
$when = $g( 'thoi-gian' );
$when = preg_match( '/^(truoc|\d{4}-W\d{2}|\d{4}-\d{2})$/', $when ) ? $when : '';
$res  = $api->get( '/api/fame', array_filter( array( 'ky' => $ky, 'thoi-gian' => $when ) ), $token ?: null );
$d    = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();

cvc_seo_set_title( 'Bảng vinh danh ' . mb_strtolower( (string) ( $d['title'] ?? 'tuần này' ) ) . ' | Công Viên Chức' );
if ( function_exists( 'cvc_seo_set_description' ) ) {
	cvc_seo_set_description( 'Top 10 thí sinh ôn thi chăm chỉ nhất và điểm thi thử cao nhất trên Công Viên Chức - cập nhật mỗi 10 phút, vinh danh theo tuần và tháng.' );
}
if ( $when ) {
	cvc_seo_set_noindex();
}
$fame_url = static fn ( array $q ): string => add_query_arg( array_filter( $q ), home_url( '/vinh-danh/' ) );

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5 cvc-fame">
	<header class="cvc-fame-head">
		<p class="cvc-fame-kicker">★ Bảng vinh danh</p>
		<h1><?php echo esc_html( (string) ( $d['title'] ?? 'Tuần này' ) ); ?></h1>
		<p>Những thí sinh ôn đều nhất và thi thử điểm cao nhất trên Công Viên Chức. Top 1, 3, 10 nhận huy hiệu khi kỳ kết thúc.</p>
	</header>

	<nav class="dz-chips" aria-label="Chọn kỳ">
		<?php
		foreach ( array(
			array( 'Tuần này', array( 'ky' => 'week' ), 'week' === $ky && ! empty( $d['is_current'] ) ),
			array( 'Tuần trước', array( 'ky' => 'week', 'thoi-gian' => 'truoc' ), 'week' === $ky && 'truoc' === $when ),
			array( 'Tháng này', array( 'ky' => 'month' ), 'month' === $ky && ! empty( $d['is_current'] ) ),
			array( 'Tháng trước', array( 'ky' => 'month', 'thoi-gian' => 'truoc' ), 'month' === $ky && 'truoc' === $when ),
		) as $t ) :
			?>
			<a class="<?php echo $t[2] ? 'is-active font-bold' : ''; ?>" href="<?php echo esc_url( $fame_url( $t[1] ) ); ?>"<?php echo $t[2] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $t[0] ); ?></a>
		<?php endforeach; ?>
		<?php if ( count( (array) ( $d['archive'] ?? array() ) ) > 2 ) : ?>
			<form method="get" action="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>" class="cvc-fame-archive">
				<input type="hidden" name="ky" value="<?php echo esc_attr( $ky ); ?>">
				<label for="fame-ky" class="sr-only">Kỳ trước</label>
				<select id="fame-ky" name="thoi-gian" onchange="this.form.submit()">
					<option value="">Các kỳ trước…</option>
					<?php foreach ( (array) $d['archive'] as $a ) : ?><option value="<?php echo esc_attr( (string) $a['key'] ); ?>" <?php selected( $when, (string) $a['key'] ); ?>><?php echo esc_html( (string) $a['title'] ); ?></option><?php endforeach; ?>
				</select>
			</form>
		<?php endif; ?>
	</nav>

	<?php if ( ! $res['ok'] ) : ?>
		<?php cvc_render_error_state( 'Chưa tải được bảng vinh danh, vui lòng thử lại sau.' ); ?>
	<?php else : ?>
		<div class="cvc-fame-grid">
			<?php
			cvc_fame_render_board( (array) ( $d['effort'] ?? array() ), 'Chăm chỉ nhất', 'Điểm ôn từ mọi bài làm: câu đúng, Bài ôn hôm nay, câu sai đã thuộc, tuần 5/7 ngày.', 'effort', $token );
			cvc_fame_render_board( (array) ( $d['score'] ?? array() ), 'Điểm thi thử cao nhất', 'Trung bình điểm cao nhất các đề thi thật, tối thiểu ' . (int) ( $d['score']['min_exams'] ?? 2 ) . ' đề trong kỳ.', 'score', $token );
			?>
		</div>
		<?php if ( ! $token ) : ?>
			<div class="cvc-fame-cta">
				<p><b>Muốn có tên trên bảng vinh danh?</b> Mỗi ngày làm Bài ôn hôm nay 10 câu là đã có điểm Chăm chỉ.</p>
				<a class="dz-btn dz-btn-primary" href="<?php echo esc_url( home_url( '/on-hom-nay/' ) ); ?>">Ôn hôm nay</a>
			</div>
		<?php endif; ?>
		<p class="text-sm text-ink-muted">Xem toàn bộ: <a href="<?php echo esc_url( cvc_leaderboard_url( array( 'ky' => $ky, 'thoi-gian' => $when ) ) ); ?>">Bảng chăm chỉ</a> · <a href="<?php echo esc_url( cvc_leaderboard_url( array( 'ky' => $ky, 'thoi-gian' => $when, 'bang' => 'diem' ) ) ); ?>">Bảng điểm thi thử</a>. Không muốn hiện tên hoặc ảnh? Đổi ở <a href="<?php echo esc_url( cvc_lb_avatar_url() ); ?>">Hồ sơ cá nhân</a>.</p>
	<?php endif; ?>
</div>
</main>
<?php
get_footer();

/** 1 cột vinh danh: 3 người đầu thẻ lớn, 4-10 danh sách; dòng "bạn" + khoảng cách tới top 10. */
function cvc_fame_render_board( array $b, string $title, string $sub, string $kind, ?string $token ): void {
	$items = (array) ( $b['items'] ?? array() );
	$me    = $b['me'] ?? null;
	$board = (string) ( $b['board'] ?? '' );
	$val   = static fn ( array $it ): string => 'effort' === $kind ? cvc_lb_points( $it['score'] ) . ' điểm' : cvc_lb_pct( $it['score'] );
	$meta  = static fn ( array $it ): string => 'effort' === $kind ? (int) $it['exams'] . ' ngày học · ' . (int) ( $it['correct'] ?? 0 ) . ' câu đúng' : (int) $it['exams'] . ' đề';
	?>
	<section class="cvc-fame-col" aria-labelledby="fame-<?php echo esc_attr( $kind ); ?>">
		<h2 id="fame-<?php echo esc_attr( $kind ); ?>"><?php echo esc_html( $title ); ?></h2>
		<p class="cvc-fame-sub"><?php echo esc_html( $sub ); ?> · <?php echo (int) ( $b['participants'] ?? 0 ); ?> người</p>
		<?php if ( ! $items ) : ?>
			<p class="cvc-fame-empty">Chưa có ai trong kỳ này.</p>
		<?php else : ?>
			<ol class="cvc-fame-list">
				<?php foreach ( $items as $it ) : ?>
					<?php $r = (int) $it['rank']; ?>
					<li class="<?php echo esc_attr( ( $r <= 3 ? 'is-top is-r' . $r : '' ) . ( ! empty( $it['is_me'] ) ? ' is-me' : '' ) ); ?>">
						<span class="cvc-fame-rank <?php echo esc_attr( cvc_lb_medal( $r ) ); ?>"><?php echo $r; ?></span>
						<?php echo cvc_avatar_html( (string) $it['name'], isset( $it['avatar'] ) ? (string) $it['avatar'] : null, $r <= 3 ? 64 : 40 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="cvc-fame-who"><b><?php echo esc_html( (string) $it['name'] ); ?><?php echo ! empty( $it['is_me'] ) ? ' <span class="dz-tag is-ok">Bạn</span>' : ''; ?></b>
							<small><?php echo esc_html( $meta( $it ) . ( ! empty( $it['province'] ) ? ' · ' . $it['province'] : '' ) ); ?></small>
							<?php if ( ! empty( $it['is_me'] ) && ! empty( $it['code'] ) ) : ?>
								<a class="cvc-fame-share" href="<?php echo esc_url( add_query_arg( array( 'b' => $board, 'c' => $it['code'] ), home_url( '/vinh-danh/the/' ) ) ); ?>">Chia sẻ thẻ vinh danh →</a>
							<?php elseif ( $token && ! empty( $it['avatar'] ) && ! empty( $it['code'] ) && false === strpos( (string) $it['avatar'], '/preset-' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cvc-fame-report" data-cvc-confirm="Báo ảnh đại diện này không phù hợp?">
									<?php wp_nonce_field( 'cvc_fame_report' ); ?><input type="hidden" name="action" value="cvc_fame_report"><input type="hidden" name="b" value="<?php echo esc_attr( $board ); ?>"><input type="hidden" name="c" value="<?php echo esc_attr( (string) $it['code'] ); ?>">
									<button type="submit">Báo ảnh</button>
								</form>
							<?php endif; ?>
						</span>
						<b class="cvc-fame-val"><?php echo esc_html( $val( $it ) ); ?></b>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<?php if ( is_array( $me ) && (int) $me['rank'] > 10 ) : ?>
			<?php $tenth = isset( $items[9] ) ? (float) $items[9]['score'] : null; ?>
			<p class="cvc-fame-me">Bạn: hạng <?php echo (int) $me['rank']; ?>/<?php echo (int) $me['participants']; ?> · <?php echo esc_html( $val( $me ) ); ?>
				<?php if ( null !== $tenth && 'effort' === $kind ) : ?> · còn <b><?php echo esc_html( cvc_lb_points( max( 0.1, $tenth - (float) $me['score'] + 0.1 ) ) ); ?> điểm</b> nữa để vào top 10<?php endif; ?></p>
		<?php elseif ( is_array( $me ) && empty( $me['has_avatar'] ) ) : ?>
			<?php cvc_render_fame_nudge( 'Bạn đang trong top 10!', 'is-compact' ); ?>
		<?php elseif ( $token && ! is_array( $me ) ) : ?>
			<p class="cvc-fame-me"><?php echo 'effort' === $kind ? 'Bạn chưa có điểm ôn kỳ này - làm Bài ôn hôm nay để vào bảng.' : 'Bạn chưa đủ số đề thi thật trong kỳ này.'; ?></p>
		<?php endif; ?>
	</section>
	<?php
}

/** Trang chia sẻ 1 người trong top 10. */
function cvc_fame_render_card_page( string $b, string $c, ?string $token ): void {
	$res = ( new CVC_Api_Client() )->get( '/api/fame/entry', array( 'b' => $b, 'c' => $c ), $token ?: null );
	$e   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	cvc_seo_set_noindex();
	if ( $e ) {
		$it   = (array) $e['item'];
		$head = $it['name'] . ' - hạng ' . (int) $it['rank'] . ' ' . mb_strtolower( (string) $e['title'] );
		cvc_seo_set_title( $head . ' | Công Viên Chức' );
		if ( function_exists( 'cvc_seo_set_description' ) ) {
			cvc_seo_set_description( 'Bảng vinh danh ôn thi công chức, viên chức trên Công Viên Chức. Ôn mỗi ngày 10 câu để có tên trên bảng.' );
		}
		$card = add_query_arg( array( 'b' => $b, 'c' => $c ), rtrim( cvc_api_base_url(), '/' ) . '/api/fame/card' );
		cvc_seo_set_og( array( 'image' => $card ) );
	}
	get_header();
	?>
	<main id="main" class="dz-main">
	<div class="dz-wrap space-y-4 cvc-fame">
		<?php if ( ! $e ) : ?>
			<?php cvc_render_error_state( 'Thẻ vinh danh này không còn (người này đã rời top 10 hoặc kỳ đã thay đổi).' ); ?>
			<p><a class="dz-btn dz-btn-primary" href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>">Xem bảng vinh danh</a></p>
		<?php else : ?>
			<header class="cvc-fame-head"><p class="cvc-fame-kicker">★ Bảng vinh danh</p><h1><?php echo esc_html( $head ); ?></h1></header>
			<img class="cvc-fame-cardimg" src="<?php echo esc_url( $card ); ?>" width="1200" height="630" alt="<?php echo esc_attr( 'Thẻ vinh danh: ' . $head ); ?>">
			<?php if ( ! empty( $it['is_me'] ) ) : ?>
				<?php $self = add_query_arg( array( 'b' => $b, 'c' => $c ), home_url( '/vinh-danh/the/' ) ); ?>
				<div class="cvc-fame-sharebar">
					<a class="dz-btn dz-btn-primary" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $self ) ); ?>" target="_blank" rel="noopener">Chia sẻ Facebook</a>
					<a class="dz-btn" href="<?php echo esc_url( 'https://sp.zalo.me/share_inline?d=' . rawurlencode( base64_encode( wp_json_encode( array( 'url' => $self ) ) ) ) ); ?>" target="_blank" rel="noopener">Chia sẻ Zalo</a>
					<button type="button" class="dz-btn" data-copy="<?php echo esc_attr( $self ); ?>" onclick="navigator.clipboard&&navigator.clipboard.writeText(this.dataset.copy).then(()=>{this.textContent='Đã chép link'})">Chép link</button>
				</div>
				<?php if ( empty( $it['avatar'] ) ) { cvc_render_fame_nudge( 'Thẻ đang dùng chữ cái đầu.' ); } ?>
			<?php else : ?>
				<div class="cvc-fame-cta"><p><b>Ôn thi công chức, viên chức cùng <?php echo esc_html( (string) $it['name'] ); ?>?</b> Mỗi ngày 10 câu, câu sai tự quay lại cho tới khi thuộc.</p>
					<a class="dz-btn dz-btn-primary" href="<?php echo esc_url( home_url( '/on-hom-nay/' ) ); ?>">Ôn thử ngay</a></div>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>">← Bảng vinh danh</a></p>
		<?php endif; ?>
	</div>
	</main>
	<?php
	get_footer();
}
