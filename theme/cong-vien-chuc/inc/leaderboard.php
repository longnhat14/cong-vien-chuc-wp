<?php
/**
 * Bảng xếp hạng thi thử (21/10/2026) - dữ liệu từ GET /api/leaderboards (tính sẵn 10 phút/lần).
 *  - /xep-hang/ (template-leaderboard.php): tuần / tháng (kỳ này, kỳ trước), ?de={exam}, ?dot={recruitment}, ?tinh=.
 *  - cvc_render_attempt_ranking(): khung "Vị trí của bạn" trên trang kết quả bài thi.
 *  - cvc_render_leaderboard_mini(): Top 10 trên trang đề / trang tin tuyển dụng.
 *  - 11/11/2026: Bảng chăm chỉ (?bang=cham-chi), phân trang, "quanh bạn", gợi ý đặt ảnh để được vinh danh (/vinh-danh/).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_leaderboard_url( array $q = array() ): string {
	return add_query_arg( array_filter( $q, static fn ( $v ) => null !== $v && '' !== $v ), home_url( '/xep-hang/' ) );
}

/** Gọi API; khách (không đăng nhập) dùng bộ nhớ đệm 5 phút. */
function cvc_leaderboard_get( array $q ): array {
	$q     = array_filter( $q, static fn ( $v ) => null !== $v && '' !== $v );
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	$key   = 'cvc_lb_' . md5( wp_json_encode( $q ) );
	if ( ! $token ) {
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
	}
	$res = ( new CVC_Api_Client() )->get( '/api/leaderboards', $q, $token ?: null );
	$out = $res['ok'] ? array( 'ok' => true, 'data' => (array) ( $res['data']['data'] ?? array() ) ) : array( 'ok' => false, 'status' => (int) ( $res['status'] ?? 0 ), 'data' => array() );
	if ( ! $token && $out['ok'] ) {
		set_transient( $key, $out, 5 * MINUTE_IN_SECONDS );
	}

	return $out;
}

function cvc_lb_pct( $v ): string {
	$s = number_format( (float) $v, 1, ',', '.' );

	return ( str_ends_with( $s, ',0' ) ? substr( $s, 0, -2 ) : $s ) . '%';
}

function cvc_lb_duration( int $sec ): string {
	if ( $sec <= 0 ) {
		return '—';
	}
	$h = intdiv( $sec, 3600 );
	$m = intdiv( $sec % 3600, 60 );
	$s = $sec % 60;
	if ( $h > 0 ) {
		return $h . ' giờ ' . $m . ' phút';
	}

	return $m > 0 ? $m . ' phút ' . sprintf( '%02d', $s ) . ' giây' : $s . ' giây';
}

function cvc_lb_medal( int $rank ): string {
	return array( 1 => 'is-gold', 2 => 'is-silver', 3 => 'is-bronze' )[ $rank ] ?? '';
}

/** Người đang xem đã có ảnh đại diện chưa (từ /api/auth/me). */
function cvc_lb_me_has_avatar(): bool {
	$u = function_exists( 'cvc_current_user' ) ? cvc_current_user() : null;

	return is_array( $u ) && ! empty( $u['avatar'] );
}

/** Link đặt ảnh đại diện (trang hồ sơ, khung chọn ảnh). */
function cvc_lb_avatar_url(): string {
	return cvc_account_url( 'profile' ) . '#anh-dai-dien';
}

function cvc_lb_points( $v ): string {
	$s = number_format( (float) $v, 1, ',', '.' );

	return str_ends_with( $s, ',0' ) ? substr( $s, 0, -2 ) : $s;
}

/** Gợi ý đặt ảnh để được vinh danh (khi người xem nằm trong top / có huy hiệu mà chưa có ảnh). */
function cvc_render_fame_nudge( string $lead, string $class = '' ): void {
	if ( cvc_lb_me_has_avatar() ) {
		return;
	}
	?>
	<div class="cvc-fame-nudge <?php echo esc_attr( $class ); ?>">
		<span class="cvc-fame-nudge-ic" aria-hidden="true">★</span>
		<p><b><?php echo esc_html( $lead ); ?></b> Thêm ảnh đại diện để được vinh danh trên Công Viên Chức - chọn ảnh minh họa có sẵn, ảnh Google hoặc tải ảnh lên.</p>
		<a class="cvc-fame-nudge-btn" href="<?php echo esc_url( cvc_lb_avatar_url() ); ?>">Thêm ảnh</a>
	</div>
	<?php
}

/** Bảng xếp hạng dạng bảng. $mode: exam (điểm, câu đúng, thời gian) | multi (TB điểm, số đề) | effort (điểm ôn, ngày học, câu). */
function cvc_render_leaderboard_table( array $items, string $mode = 'exam' ): void {
	if ( ! $items ) {
		return;
	}
	?>
	<div class="dz-tablewrap"><table class="dz-table cvc-lb-table">
		<thead><tr><th scope="col" class="cvc-lb-rank">Hạng</th><th scope="col">Thí sinh</th>
			<?php if ( 'exam' === $mode ) : ?><th scope="col" class="text-right">Điểm</th><th scope="col" class="text-right cvc-lb-hide-sm">Câu đúng</th><th scope="col" class="text-right cvc-lb-hide-sm">Thời gian</th>
			<?php elseif ( 'effort' === $mode ) : ?><th scope="col" class="text-right">Điểm ôn</th><th scope="col" class="text-right">Ngày học</th><th scope="col" class="text-right cvc-lb-hide-sm">Câu đúng</th>
			<?php else : ?><th scope="col" class="text-right">Điểm TB</th><th scope="col" class="text-right">Số đề</th><?php endif; ?>
		</tr></thead>
		<tbody>
		<?php foreach ( $items as $it ) : ?>
			<?php if ( ! empty( $it['_gap'] ) ) : ?><tr class="cvc-lb-gap"><td colspan="5">Quanh vị trí của bạn</td></tr><?php endif; ?>
			<tr class="<?php echo ! empty( $it['is_me'] ) ? 'is-me' : ''; ?>">
				<td class="cvc-lb-rank"><span class="cvc-lb-badge <?php echo esc_attr( cvc_lb_medal( (int) $it['rank'] ) ); ?>"><?php echo (int) $it['rank']; ?></span></td>
				<td><span class="cvc-lb-who"><?php echo cvc_avatar_html( (string) $it['name'], isset( $it['avatar'] ) ? (string) $it['avatar'] : null, 32 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><b><?php echo esc_html( (string) $it['name'] ); ?></b><?php echo ! empty( $it['is_me'] ) ? ' <span class="dz-tag is-ok">Bạn</span>' : ''; ?>
					<?php if ( ! empty( $it['is_me'] ) && empty( $it['avatar'] ) ) : ?><a class="cvc-lb-addpic" href="<?php echo esc_url( cvc_lb_avatar_url() ); ?>">+ Thêm ảnh để được vinh danh</a><?php endif; ?>
					<?php if ( ! empty( $it['province'] ) ) : ?><small><?php echo esc_html( (string) $it['province'] ); ?></small><?php endif; ?></span></span></td>
				<?php if ( 'exam' === $mode ) : ?>
					<td class="text-right"><b><?php echo esc_html( cvc_lb_pct( $it['score'] ) ); ?></b></td>
					<td class="text-right cvc-lb-hide-sm"><?php echo null !== ( $it['correct'] ?? null ) && ! empty( $it['total'] ) ? (int) $it['correct'] . '/' . (int) $it['total'] : '—'; ?></td>
					<td class="text-right cvc-lb-hide-sm"><?php echo esc_html( cvc_lb_duration( (int) $it['duration'] ) ); ?></td>
				<?php elseif ( 'effort' === $mode ) : ?>
					<td class="text-right"><b><?php echo esc_html( cvc_lb_points( $it['score'] ) ); ?></b></td>
					<td class="text-right"><?php echo (int) $it['exams']; ?></td>
					<td class="text-right cvc-lb-hide-sm"><?php echo null !== ( $it['correct'] ?? null ) && ! empty( $it['total'] ) ? (int) $it['correct'] . '/' . (int) $it['total'] : '—'; ?></td>
				<?php else : ?>
					<td class="text-right"><b><?php echo esc_html( cvc_lb_pct( $it['score'] ) ); ?></b></td>
					<td class="text-right"><?php echo (int) $it['exams']; ?></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php
}

/** Khung "Vị trí của bạn" của người đang xem (nếu có trong bảng). */
function cvc_render_leaderboard_me( array $data ): void {
	$me = $data['me'] ?? null;
	if ( ! is_array( $me ) ) {
		return;
	}
	$effort = 'effort' === ( $data['kind'] ?? '' );
	$multi  = ( $data['min_exams'] ?? 1 ) > 1 || str_starts_with( (string) ( $data['board'] ?? '' ), 'recruitment:' );
	?>
	<div class="cvc-lb-me" role="status">
		<?php echo cvc_current_avatar_html( 44 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div><b>Vị trí của bạn: hạng <?php echo (int) $me['rank']; ?>/<?php echo (int) $me['participants']; ?></b>
			<span>· cao hơn <?php echo (int) $me['better_than']; ?>% thí sinh · <?php echo $effort ? esc_html( cvc_lb_points( $me['score'] ) . ' điểm ôn (' . (int) $me['exams'] . ' ngày học)' ) : ( ( $multi ? 'điểm TB ' : 'điểm cao nhất ' ) . esc_html( cvc_lb_pct( $me['score'] ) ) . ( $multi ? ' (' . (int) $me['exams'] . ' đề)' : '' ) ); ?></span></div>
	</div>
	<?php
	if ( (int) $me['rank'] <= 10 ) {
		cvc_render_fame_nudge( 'Bạn đang trong top 10!' );
	}
}

/** Trang kết quả bài thi: vị trí của lượt vừa nộp (tính trực tiếp). */
function cvc_render_attempt_ranking( int $attempt_id, string $heading_class = '' ): void {
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	if ( ! $token || $attempt_id <= 0 ) {
		return;
	}
	$res = ( new CVC_Api_Client() )->get( '/api/exam-attempts/' . $attempt_id . '/ranking', array(), $token );
	$d   = $res['ok'] ? ( $res['data']['data'] ?? null ) : null;
	if ( ! is_array( $d ) ) {
		return;
	}
	$url = cvc_leaderboard_url( (array) ( $d['board_query'] ?? array() ) );
	?>
	<?php cvc_lb_inline_style(); ?>
	<div class="cvc-lb-attempt">
		<h2 class="<?php echo esc_attr( $heading_class ); ?>">Xếp hạng</h2>
		<?php if ( ! empty( $d['ranked'] ) ) : ?>
			<p class="cvc-lb-big">Hạng <b><?php echo (int) $d['rank']; ?></b>/<?php echo (int) $d['participants']; ?></p>
			<p>Cao hơn <b><?php echo (int) $d['better_than']; ?>%</b> thí sinh đã làm đề này.</p>
			<?php if ( null !== ( $d['average'] ?? null ) ) : ?><p>Điểm trung bình của đề: <?php echo esc_html( cvc_lb_pct( $d['average'] ) ); ?> · Bạn: <b><?php echo esc_html( cvc_lb_pct( $d['score'] ) ); ?></b></p><?php endif; ?>
			<?php if ( empty( $d['is_personal_best'] ) && null !== ( $d['personal_best'] ?? null ) ) : ?><p class="text-sm">Điểm cao nhất của bạn (<?php echo esc_html( cvc_lb_pct( $d['personal_best'] ) ); ?>) vẫn được giữ trên bảng.</p><?php endif; ?>
			<p class="text-sm text-ink-muted">Bảng cập nhật mỗi 10 phút.</p>
			<?php if ( (int) $d['rank'] <= 10 ) { cvc_render_fame_nudge( 'Bạn đang hạng ' . (int) $d['rank'] . ' đề này!', 'is-compact' ); } ?>
		<?php else : ?>
			<p class="text-sm"><?php echo esc_html( (string) ( $d['reason'] ?? 'Lượt này không được xếp hạng.' ) ); ?></p>
			<?php if ( null !== ( $d['average'] ?? null ) ) : ?><p class="text-sm">Điểm trung bình của đề: <?php echo esc_html( cvc_lb_pct( $d['average'] ) ); ?></p><?php endif; ?>
		<?php endif; ?>
		<p><a class="cvc-lb-link" href="<?php echo esc_url( $url ); ?>">Xem bảng xếp hạng &rarr;</a></p>
	</div>
	<?php
}

/** Kiểu tối thiểu cho khung xếp hạng trên trang làm bài / kết quả (2 trang này không nạp cvc-doc.css). */
function cvc_lb_inline_style(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	echo '<style>.cvc-lb-attempt p{margin:6px 0;font-size:14px}.cvc-lb-attempt .cvc-lb-big{font-size:22px;margin:4px 0}.cvc-lb-attempt .cvc-lb-big b{font-size:30px}.cvc-lb-attempt .text-sm{font-size:13px}.cvc-lb-attempt .text-ink-muted{color:#5c5c5c}.cvc-lb-link{font-weight:bold;color:#1767aa;text-decoration:underline}.cvc-fame-nudge{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:10px 0;padding:10px 12px;border:1px solid #e6d29a;border-radius:10px;background:#fbf5e3;color:#3d3214}.cvc-fame-nudge p{flex:1;min-width:200px;margin:0;font-size:14px;line-height:1.45}.cvc-fame-nudge-ic{display:grid;place-items:center;width:30px;height:30px;border-radius:50%;background:#c9a052;color:#fff;font-size:16px;flex:none}.cvc-fame-nudge-btn{display:inline-flex;align-items:center;min-height:40px;padding:6px 14px;border-radius:10px;background:#123a63;color:#fff!important;font-weight:700;text-decoration:none!important;font-size:14px}.cvc-fame-nudge.is-compact p{font-size:13px}</style>';
}

/** Top 5 rút gọn dạng danh sách (cột bên trang đề / thẻ trang tin tuyển dụng). Không hiện gì khi bảng chưa có ai. */
function cvc_render_leaderboard_mini( array $q, string $heading = 'Bảng xếp hạng thi thử', string $wrap_class = 'dz-card' ): void {
	$r = cvc_leaderboard_get( $q + array( 'limit' => 10 ) );
	$d = $r['data'] ?? array();
	if ( empty( $r['ok'] ) ) {
		return;
	}
	if ( empty( $d['items'] ) ) {
		// 02/11/2026: bảng theo tin chưa có ai - mời thi thử để lên bảng (ẩn danh).
		if ( isset( $q['dot'] ) ) {
			?>
			<section class="<?php echo esc_attr( $wrap_class ); ?> cvc-lb-mini" aria-label="<?php echo esc_attr( $heading ); ?>">
				<h3 class="cvc-lb-mini-h"><?php echo esc_html( $heading ); ?></h3>
				<p class="cvc-lb-mini-sub">Chưa có ai thi thử đề của tin này. Làm 1 đề ở chế độ <b>Thi thử</b> để lên bảng.</p>
				<p><a class="cvc-lb-link" href="#de-rieng">Chọn đề thi thử của tin →</a></p>
			</section>
			<?php
		}
		return;
	}
	$multi = isset( $q['dot'] );
	?>
	<section class="<?php echo esc_attr( $wrap_class ); ?> cvc-lb-mini" aria-label="<?php echo esc_attr( $heading ); ?>">
		<h3 class="cvc-lb-mini-h"><?php echo esc_html( $heading ); ?></h3>
		<p class="cvc-lb-mini-sub"><?php echo (int) $d['participants']; ?> thí sinh<?php echo null !== ( $d['average'] ?? null ) ? ' · điểm TB ' . esc_html( cvc_lb_pct( $d['average'] ) ) : ''; ?></p>
		<ol class="cvc-lb-mini-list">
			<?php foreach ( (array) $d['items'] as $it ) : ?>
				<li class="<?php echo ! empty( $it['is_me'] ) ? 'is-me' : ''; ?>">
					<span class="cvc-lb-badge <?php echo esc_attr( cvc_lb_medal( (int) $it['rank'] ) ); ?>"><?php echo (int) $it['rank']; ?></span>
					<?php echo cvc_avatar_html( (string) $it['name'], isset( $it['avatar'] ) ? (string) $it['avatar'] : null, 28 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span class="cvc-lb-mini-name"><?php echo esc_html( (string) $it['name'] ); ?><?php if ( ! empty( $it['province'] ) ) : ?><small><?php echo esc_html( (string) $it['province'] ); ?></small><?php endif; ?></span>
					<b><?php echo esc_html( cvc_lb_pct( $it['score'] ) ); ?></b><?php echo $multi ? '<small class="cvc-lb-mini-ex">' . (int) $it['exams'] . ' đề</small>' : ''; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php if ( ! empty( $d['me'] ) && (int) $d['me']['rank'] > 10 ) : ?><p class="cvc-lb-mini-sub">Bạn: hạng <?php echo (int) $d['me']['rank']; ?>/<?php echo (int) $d['me']['participants']; ?> · <?php echo esc_html( cvc_lb_pct( $d['me']['score'] ) ); ?></p><?php endif; ?>
		<?php if ( ! empty( $d['me'] ) && (int) $d['me']['rank'] <= 10 && empty( $d['me']['has_avatar'] ) ) : ?><p class="cvc-lb-mini-sub"><a class="cvc-lb-addpic" href="<?php echo esc_url( cvc_lb_avatar_url() ); ?>">+ Bạn đang trong top 10 - thêm ảnh để được vinh danh</a></p><?php endif; ?>
		<p><a class="cvc-lb-link" href="<?php echo esc_url( cvc_leaderboard_url( $q ) ); ?>">Xem đầy đủ &rarr;</a></p>
	</section>
	<?php
}

/** Huy hiệu + vị trí tuần/tháng của người dùng (trang tài khoản). */
function cvc_render_my_leaderboard(): void {
	$token = function_exists( 'cvc_auth_token' ) ? cvc_auth_token() : null;
	if ( ! $token ) {
		return;
	}
	$res = ( new CVC_Api_Client() )->get( '/api/me/leaderboard', array(), $token );
	$d   = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	if ( ! $d ) {
		return;
	}
	$badges = (array) ( $d['badges'] ?? array() );
	?>
	<section class="cvc-account-section cvc-lb-account">
		<h2>Bảng xếp hạng & huy hiệu</h2>
		<ul class="cvc-lb-stats">
			<li><span>Chăm chỉ tuần này</span><b><?php echo ! empty( $d['effort_week'] ) ? 'Hạng ' . (int) $d['effort_week']['rank'] : '—'; ?></b><small><?php echo ! empty( $d['effort_week'] ) ? esc_html( cvc_lb_points( $d['effort_week']['score'] ) . ' điểm ôn · ' . (int) $d['effort_week']['days'] . ' ngày' ) : 'Làm bài ôn hôm nay hoặc luyện vài câu để vào bảng'; ?></small></li>
			<li><span>Thi thử tuần này</span><b><?php echo ! empty( $d['week'] ) ? 'Hạng ' . (int) $d['week']['rank'] : '—'; ?></b><small><?php echo ! empty( $d['week'] ) ? esc_html( cvc_lb_pct( $d['week']['score'] ) . ' · ' . (int) $d['week']['exams'] . ' đề' ) : 'Làm ≥ ' . (int) ( $d['min_week_exams'] ?? 2 ) . ' đề thi thật để vào bảng'; ?></small></li>
			<li><span>Thi thử tháng này</span><b><?php echo ! empty( $d['month'] ) ? 'Hạng ' . (int) $d['month']['rank'] : '—'; ?></b><small><?php echo ! empty( $d['month'] ) ? esc_html( cvc_lb_pct( $d['month']['score'] ) . ' · ' . (int) $d['month']['exams'] . ' đề' ) : 'Làm ≥ ' . (int) ( $d['min_exams'] ?? 3 ) . ' đề thi thật để vào bảng'; ?></small></li>
		</ul>
		<?php if ( $badges ) : ?>
			<ul class="cvc-lb-badges">
				<?php foreach ( $badges as $b ) : ?>
					<li class="<?php echo esc_attr( cvc_lb_medal( min( 3, (int) $b['rank'] ) ) ); ?>" title="<?php echo esc_attr( 'Hạng ' . (int) $b['rank'] ); ?>"><span aria-hidden="true">★</span> <?php echo esc_html( (string) $b['label'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( ! empty( $d['top_exams'] ) ) : ?>
			<p class="text-sm">Vị trí tốt nhất theo đề:
				<?php foreach ( (array) $d['top_exams'] as $i => $te ) : ?><?php echo $i ? ' · ' : ''; ?><a href="<?php echo esc_url( cvc_leaderboard_url( array( 'de' => (int) $te['exam_id'] ) ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $te['title'], 8 ) ); ?></a> (hạng <?php echo (int) $te['rank']; ?>)<?php endforeach; ?></p>
		<?php endif; ?>
		<?php
		$top = ( ! empty( $d['effort_week'] ) && (int) $d['effort_week']['rank'] <= 10 ) || ( ! empty( $d['week'] ) && (int) $d['week']['rank'] <= 10 ) || $badges;
		if ( $top && empty( $d['has_avatar'] ) ) {
			cvc_render_fame_nudge( $badges ? 'Bạn đã có huy hiệu!' : 'Bạn đang trong top 10 tuần này!', 'is-compact' );
		}
		$vis = array( 'full' => 'Bảng hiện tên và ảnh đại diện của bạn.', 'initials' => 'Bảng chỉ hiện chữ cái đầu tên bạn.', 'hidden' => 'Bạn đang ẩn tên trên bảng.' );
		?>
		<p class="text-sm"><a href="<?php echo esc_url( home_url( '/xep-hang/' ) ); ?>">Xem bảng xếp hạng</a> · <a href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>">Bảng vinh danh</a> · <?php echo esc_html( $vis[ $d['visibility'] ?? 'full' ] ?? $vis['full'] ); ?> <a href="<?php echo esc_url( cvc_lb_avatar_url() ); ?>">Đổi</a></p>
	</section>
	<?php
}

/* Báo ảnh đại diện không phù hợp trên trang Vinh danh (11/11/2026). */
add_action( 'admin_post_cvc_fame_report', 'cvc_handle_fame_report' );
add_action( 'admin_post_nopriv_cvc_fame_report', 'cvc_handle_fame_report' );

function cvc_handle_fame_report(): void {
	check_admin_referer( 'cvc_fame_report' );
	$token = cvc_require_token_or_die();
	$back  = wp_get_referer() ?: home_url( '/vinh-danh/' );
	$res   = ( new CVC_Api_Client() )->post( '/api/fame/report', array(
		'b'      => sanitize_text_field( wp_unslash( (string) ( $_POST['b'] ?? '' ) ) ),
		'c'      => sanitize_key( wp_unslash( (string) ( $_POST['c'] ?? '' ) ) ),
		'reason' => 'Người dùng báo từ trang Vinh danh',
	), $token );
	cvc_redirect_with_notice( $back, $res['ok'] ? 'success' : 'error', (string) ( $res['data']['message'] ?? cvc_api_error_message( $res ) ), null );
}
