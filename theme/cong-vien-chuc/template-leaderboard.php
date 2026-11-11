<?php
/**
 * /xep-hang/ — Bảng xếp hạng thi thử (21/10/2026).
 * ?ky=week|month&thoi-gian=truoc  ·  ?de={exam_id}  ·  ?dot={recruitment_id}  ·  &tinh={province_id}
 * 11/11/2026: &bang=cham-chi (Bảng chăm chỉ - mặc định cho tuần / tháng) | diem (điểm thi thử); &trang=N (100 người / trang).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$g    = static fn ( string $k ): string => sanitize_text_field( wp_unslash( (string) ( $_GET[ $k ] ?? '' ) ) );
$de   = absint( $g( 'de' ) );
$dot  = absint( $g( 'dot' ) );
$ky   = 'month' === $g( 'ky' ) ? 'month' : 'week';
$when = 'truoc' === $g( 'thoi-gian' ) ? 'truoc' : '';
$tinh = absint( $g( 'tinh' ) );
$bang = 'diem' === $g( 'bang' ) ? 'diem' : 'cham-chi';
$trang = max( 1, absint( $g( 'trang' ) ) );

$q = $de ? array( 'de' => $de ) : ( $dot ? array( 'dot' => $dot ) : array( 'ky' => $ky, 'thoi-gian' => $when, 'bang' => $bang ) );
if ( $tinh ) {
	$q['tinh'] = $tinh;
}
$res   = cvc_leaderboard_get( $q + array( 'limit' => 100, 'page' => $trang > 1 ? $trang : null ) );
$d     = (array) ( $res['data'] ?? array() );
$multi = ! $de;
$effort = 'effort' === ( $d['kind'] ?? '' );
$provs = (array) ( cvc_employer_public_settings()['provinces'] ?? array() );

$title = $de ? 'Bảng xếp hạng: ' . (string) ( $d['exam']['title'] ?? 'đề thi' ) : ( $dot ? 'Bảng xếp hạng ôn thi: ' . (string) ( $d['recruitment']['title'] ?? 'đợt tuyển dụng' ) : ( $effort ? 'Bảng xếp hạng ' . mb_strtolower( (string) ( $d['title'] ?? 'chăm chỉ' ) ) : 'Bảng xếp hạng thi thử ' . mb_strtolower( (string) ( $d['title'] ?? '' ) ) ) );
cvc_seo_set_title( $title . ' | Công Viên Chức' );
if ( function_exists( 'cvc_seo_set_description' ) ) {
	cvc_seo_set_description( 'Top thí sinh thi thử công chức, viên chức điểm cao nhất trên congvienchuc.com — theo tuần, tháng, từng đề và từng đợt tuyển dụng.' );
}
if ( $tinh || $when || $trang > 1 ) {
	cvc_seo_set_noindex();
}

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5">
	<header class="dz-pagehead">
		<h1><?php echo esc_html( $de || $dot ? $title : 'Bảng xếp hạng' ); ?></h1>
		<?php if ( ! $de && ! $dot && ! empty( $d['title'] ) ) : ?><p class="text-ink-2"><?php echo esc_html( (string) $d['title'] ); ?></p><?php endif; ?>
	</header>

	<?php if ( ! $de && ! $dot ) : ?>
	<nav class="cvc-lb-kinds" aria-label="Loại bảng">
		<a class="<?php echo 'cham-chi' === $bang ? 'is-active' : ''; ?>" href="<?php echo esc_url( cvc_leaderboard_url( array( 'ky' => $ky, 'thoi-gian' => $when, 'tinh' => $tinh ?: null ) ) ); ?>"<?php echo 'cham-chi' === $bang ? ' aria-current="page"' : ''; ?>><b>Chăm chỉ</b><small>Điểm ôn từ mọi bài làm</small></a>
		<a class="<?php echo 'diem' === $bang ? 'is-active' : ''; ?>" href="<?php echo esc_url( cvc_leaderboard_url( array( 'ky' => $ky, 'thoi-gian' => $when, 'bang' => 'diem', 'tinh' => $tinh ?: null ) ) ); ?>"<?php echo 'diem' === $bang ? ' aria-current="page"' : ''; ?>><b>Điểm thi thử</b><small>Trung bình điểm đề thi thật</small></a>
		<a href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>"><b>★ Vinh danh</b><small>Top 10 tuần, tháng</small></a>
	</nav>
	<?php endif; ?>
	<nav class="dz-chips" aria-label="Chọn bảng xếp hạng">
		<?php
		$bq   = 'diem' === $bang ? array( 'bang' => 'diem' ) : array();
		$tabs = array(
			array( 'Tuần này', array( 'ky' => 'week' ) + $bq, ! $de && ! $dot && 'week' === $ky && '' === $when ),
			array( 'Tuần trước', array( 'ky' => 'week', 'thoi-gian' => 'truoc' ) + $bq, ! $de && ! $dot && 'week' === $ky && 'truoc' === $when ),
			array( 'Tháng này', array( 'ky' => 'month' ) + $bq, ! $de && ! $dot && 'month' === $ky && '' === $when ),
			array( 'Tháng trước', array( 'ky' => 'month', 'thoi-gian' => 'truoc' ) + $bq, ! $de && ! $dot && 'month' === $ky && 'truoc' === $when ),
		);
		foreach ( $tabs as $t ) :
			?>
			<a class="<?php echo $t[2] ? 'is-active font-bold' : ''; ?>" href="<?php echo esc_url( cvc_leaderboard_url( $t[1] + array( 'tinh' => $tinh ?: null ) ) ); ?>"<?php echo $t[2] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $t[0] ); ?></a>
		<?php endforeach; ?>
		<?php if ( $de && ! empty( $d['exam']['slug'] ) && function_exists( 'cvc_exam_url' ) ) : ?><a href="<?php echo esc_url( cvc_exam_url( (string) $d['exam']['slug'] ) ); ?>">Làm đề này &rarr;</a><?php endif; ?>
		<?php if ( $dot && ! empty( $d['recruitment']['slug'] ) && function_exists( 'cvc_recruitment_url' ) ) : ?><a href="<?php echo esc_url( cvc_recruitment_url( (string) $d['recruitment']['slug'] ) ); ?>">Xem tin tuyển dụng &rarr;</a><?php endif; ?>
	</nav>

	<form class="flex flex-wrap gap-2 items-end" method="get" action="<?php echo esc_url( home_url( '/xep-hang/' ) ); ?>">
		<?php foreach ( array_diff_key( $q, array( 'tinh' => 1 ) ) as $k => $v ) : ?><?php if ( '' !== (string) $v ) : ?><input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( (string) $v ); ?>"><?php endif; ?><?php endforeach; ?>
		<label class="text-sm"><span class="block font-bold">Tỉnh/thành</span>
			<select name="tinh" class="border border-slate-300 rounded px-2 py-1.5" onchange="this.form.submit()">
				<option value="">Cả nước</option>
				<?php foreach ( $provs as $pv ) : ?><option value="<?php echo (int) $pv['id']; ?>" <?php selected( $tinh, (int) $pv['id'] ); ?>><?php echo esc_html( (string) $pv['name'] ); ?></option><?php endforeach; ?>
			</select></label>
		<noscript><button class="dz-btn dz-btn-sm" type="submit">Lọc</button></noscript>
	</form>

	<?php if ( empty( $res['ok'] ) ) : ?>
		<?php cvc_render_error_state( 404 === (int) ( $res['status'] ?? 0 ) ? 'Không tìm thấy bảng xếp hạng này.' : 'Chưa tải được bảng xếp hạng, vui lòng thử lại sau.' ); ?>
	<?php else : ?>
		<?php cvc_render_leaderboard_me( $d ); ?>
		<p class="text-sm text-ink-muted"><?php echo (int) ( $d['participants'] ?? 0 ); ?> thí sinh<?php echo null !== ( $d['average'] ?? null ) ? ( $effort ? ' · trung bình ' . esc_html( cvc_lb_points( $d['average'] ) ) . ' điểm ôn' : ' · điểm trung bình ' . esc_html( cvc_lb_pct( $d['average'] ) ) ) : ''; ?><?php echo ! empty( $d['computed_at'] ) ? ' · cập nhật ' . esc_html( wp_date( 'H:i d/m', strtotime( (string) $d['computed_at'] ) ) ) : ''; ?></p>
		<?php if ( empty( $d['items'] ) ) : ?>
			<div class="dz-empty">
				<p>Chưa có thí sinh nào đủ điều kiện<?php echo $tinh ? ' ở tỉnh/thành này' : ''; ?>.</p>
				<?php if ( $effort ) : ?><p class="text-sm">Làm Bài ôn hôm nay hoặc luyện vài câu là có mặt trên bảng.</p>
				<?php elseif ( ! $de && ! $dot ) : ?><p class="text-sm">Làm ít nhất <?php echo (int) ( $d['min_exams'] ?? 2 ); ?> đề ở chế độ thi thật trong <?php echo 'month' === $ky ? 'tháng' : 'tuần'; ?> để có mặt trên bảng.</p><?php endif; ?>
				<p><a class="dz-btn dz-btn-primary" href="<?php echo esc_url( $effort ? home_url( '/on-hom-nay/' ) : ( function_exists( 'cvc_exams_mode_url' ) ? cvc_exams_mode_url( 'thi-that' ) : home_url( '/de-thi/' ) ) ); ?>"><?php echo $effort ? 'Ôn hôm nay' : 'Vào thi thử'; ?></a></p>
			</div>
		<?php else : ?>
			<?php
			$rows = (array) $d['items'];
			foreach ( (array) ( $d['around'] ?? array() ) as $k => $a ) {
				$rows[] = (array) $a + ( 0 === $k ? array( '_gap' => true ) : array() );
			}
			cvc_render_leaderboard_table( $rows, $effort ? 'effort' : ( $multi ? 'multi' : 'exam' ) );
			$pages = (int) ( $d['pages'] ?? 1 );
			if ( $pages > 1 ) :
				$pq = array_filter( $q, static fn ( $v ) => '' !== (string) $v );
				?>
				<nav class="cvc-lb-pager" aria-label="Trang">
					<?php echo $trang > 1 ? '<a href="' . esc_url( cvc_leaderboard_url( $pq + array( 'trang' => $trang - 1 > 1 ? $trang - 1 : null ) ) ) . '">← Trước</a>' : '<span></span>'; ?>
					<span>Trang <?php echo (int) $trang; ?>/<?php echo (int) $pages; ?></span>
					<?php echo $trang < $pages ? '<a href="' . esc_url( cvc_leaderboard_url( $pq + array( 'trang' => $trang + 1 ) ) ) . '">Sau →</a>' : '<span></span>'; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>

	<details class="dz-box"><summary class="dz-box-head cursor-pointer">Cách xếp hạng</summary><div class="dz-box-body text-sm space-y-1">
		<p><b>Chăm chỉ:</b> tính mọi bài làm (luyện thi, thi thử, Bài ôn hôm nay): +1 mỗi câu đúng, +0,3 mỗi câu sai; mỗi câu chỉ tính 1 lần mỗi ngày; câu trả lời cách câu trước dưới 4 giây không tính. +10 mỗi ngày xong Bài ôn hôm nay, +5 mỗi câu sai cũ đã thuộc, +20 khi học đủ 5/7 ngày trong tuần. Tối đa 150 điểm mỗi ngày.</p>
		<p><b>Điểm thi thử:</b> chỉ tính bài làm ở chế độ <b>thi thật</b> (có tính giờ) trên đề công khai. Theo đề: điểm cao nhất của mỗi người, bằng điểm thì ai nhanh hơn xếp trên. Theo tuần / tháng: trung bình điểm cao nhất từng đề, cần tối thiểu 2 đề / tuần, 3 đề / tháng. Theo đợt tuyển dụng: trung bình các đề ôn thi của đợt.</p>
		<p>Bài thi quá nhanh (dưới 5 giây/câu) hoặc bỏ trống quá nửa đề không được tính. Top 1, 3, 10 mỗi tuần / tháng (cả hai bảng) nhận huy hiệu khi kỳ kết thúc và có mặt trên <a href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>">Bảng vinh danh</a>.</p>
		<p>Muốn chỉ hiện chữ cái đầu hoặc ẩn tên? Vào <a href="<?php echo esc_url( cvc_lb_avatar_url() ); ?>">Hồ sơ cá nhân</a>.</p>
	</div></details>
</div>
</main>
<?php
get_footer();
