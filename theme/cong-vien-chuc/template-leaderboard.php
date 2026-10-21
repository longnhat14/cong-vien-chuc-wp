<?php
/**
 * /xep-hang/ — Bảng xếp hạng thi thử (21/10/2026).
 * ?ky=week|month&thoi-gian=truoc  ·  ?de={exam_id}  ·  ?dot={recruitment_id}  ·  &tinh={province_id}
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

$q = $de ? array( 'de' => $de ) : ( $dot ? array( 'dot' => $dot ) : array( 'ky' => $ky, 'thoi-gian' => $when ) );
if ( $tinh ) {
	$q['tinh'] = $tinh;
}
$res   = cvc_leaderboard_get( $q + array( 'limit' => 100 ) );
$d     = (array) ( $res['data'] ?? array() );
$multi = ! $de;
$provs = (array) ( cvc_employer_public_settings()['provinces'] ?? array() );

$title = $de ? 'Bảng xếp hạng: ' . (string) ( $d['exam']['title'] ?? 'đề thi' ) : ( $dot ? 'Bảng xếp hạng ôn thi: ' . (string) ( $d['recruitment']['title'] ?? 'đợt tuyển dụng' ) : 'Bảng xếp hạng thi thử ' . mb_strtolower( (string) ( $d['title'] ?? '' ) ) );
cvc_seo_set_title( $title . ' | Công Viên Chức' );
if ( function_exists( 'cvc_seo_set_description' ) ) {
	cvc_seo_set_description( 'Top thí sinh thi thử công chức, viên chức điểm cao nhất trên congvienchuc.com — theo tuần, tháng, từng đề và từng đợt tuyển dụng.' );
}
if ( $tinh || $when ) {
	cvc_seo_set_noindex();
}

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5">
	<header class="dz-pagehead">
		<h1><?php echo esc_html( $de || $dot ? $title : 'Bảng xếp hạng thi thử' ); ?></h1>
		<?php if ( ! $de && ! $dot && ! empty( $d['title'] ) ) : ?><p class="text-ink-2"><?php echo esc_html( (string) $d['title'] ); ?></p><?php endif; ?>
	</header>

	<nav class="dz-chips" aria-label="Chọn bảng xếp hạng">
		<?php
		$tabs = array(
			array( 'Tuần này', array( 'ky' => 'week' ), ! $de && ! $dot && 'week' === $ky && '' === $when ),
			array( 'Tuần trước', array( 'ky' => 'week', 'thoi-gian' => 'truoc' ), ! $de && ! $dot && 'week' === $ky && 'truoc' === $when ),
			array( 'Tháng này', array( 'ky' => 'month' ), ! $de && ! $dot && 'month' === $ky && '' === $when ),
			array( 'Tháng trước', array( 'ky' => 'month', 'thoi-gian' => 'truoc' ), ! $de && ! $dot && 'month' === $ky && 'truoc' === $when ),
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
		<p class="text-sm text-ink-muted"><?php echo (int) ( $d['participants'] ?? 0 ); ?> thí sinh<?php echo null !== ( $d['average'] ?? null ) ? ' · điểm trung bình ' . esc_html( cvc_lb_pct( $d['average'] ) ) : ''; ?><?php echo ! empty( $d['computed_at'] ) ? ' · cập nhật ' . esc_html( wp_date( 'H:i d/m', strtotime( (string) $d['computed_at'] ) ) ) : ''; ?></p>
		<?php if ( empty( $d['items'] ) ) : ?>
			<div class="dz-empty">
				<p>Chưa có thí sinh nào đủ điều kiện<?php echo $tinh ? ' ở tỉnh/thành này' : ''; ?>.</p>
				<?php if ( ! $de && ! $dot ) : ?><p class="text-sm">Làm ít nhất <?php echo (int) ( $d['min_exams'] ?? 3 ); ?> đề ở chế độ thi thật trong <?php echo 'month' === $ky ? 'tháng' : 'tuần'; ?> để có mặt trên bảng.</p><?php endif; ?>
				<p><a class="dz-btn dz-btn-primary" href="<?php echo esc_url( function_exists( 'cvc_exams_mode_url' ) ? cvc_exams_mode_url( 'thi-that' ) : home_url( '/de-thi/' ) ); ?>">Vào thi thử</a></p>
			</div>
		<?php else : ?>
			<?php cvc_render_leaderboard_table( (array) $d['items'], $multi ? 'multi' : 'exam' ); ?>
		<?php endif; ?>
	<?php endif; ?>

	<details class="dz-box"><summary class="dz-box-head cursor-pointer">Cách xếp hạng</summary><div class="dz-box-body text-sm space-y-1">
		<p>Chỉ tính bài làm ở chế độ <b>thi thật</b> (có tính giờ) trên đề công khai; không tính chế độ ôn tập và lượt xem thử.</p>
		<p><b>Theo đề:</b> lấy điểm cao nhất của mỗi người; bằng điểm thì ai làm nhanh hơn xếp trên.</p>
		<p><b>Theo tuần / tháng:</b> trung bình điểm cao nhất của từng đề đã làm trong kỳ, cần tối thiểu <?php echo (int) ( $d['min_exams'] ?? 3 ); ?> đề. <b>Theo đợt tuyển dụng:</b> trung bình các đề ôn thi của đợt.</p>
		<p>Bài làm quá nhanh (dưới 5 giây/câu) hoặc bỏ trống quá nửa đề không được tính. Top 1, 3, 10 mỗi tuần/tháng nhận huy hiệu khi kỳ kết thúc.</p>
		<p>Muốn ẩn tên? Vào <a href="<?php echo esc_url( cvc_account_url( 'profile' ) ); ?>">Hồ sơ cá nhân</a> → "Bảng xếp hạng thi thử".</p>
	</div></details>
</div>
</main>
<?php
get_footer();
