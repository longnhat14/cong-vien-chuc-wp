<?php
/** Mời bạn bè (12/11/2026) - GET /api/me/referral. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rf = function_exists( 'cvc_growth_api' ) ? cvc_growth_api( '/api/me/referral', array(), $token ) : array();
if ( ! $rf ) {
	cvc_render_error_state( 'Chưa tải được thông tin lời mời, thử lại sau.' );
	return;
}
$rf_link = add_query_arg( 'ref', (string) $rf['code'], home_url( '/' ) );
$rf_st   = array( 'pending' => 'Chờ bắt đầu ôn', 'review' => 'Đang kiểm tra', 'rewarded' => 'Đã thưởng', 'capped' => 'Thành công (hết lượt thưởng tháng)', 'rejected' => 'Không hợp lệ' );
$rf_c    = (array) $rf['counts'];
?>
<div class="space-y-4 text-sm">
	<p>Gửi link của bạn cho bạn bè đang ôn thi công chức, viên chức. Khi bạn của bạn <b>đăng ký, xác minh email và làm xong 1 bài thi (hoặc 10 câu)</b> trong <?php echo (int) $rf['window_days']; ?> ngày, <b>cả hai</b> cùng nhận <b><?php echo (int) $rf['reward_days']; ?> ngày Gói ôn thi đầy đủ</b> (cộng nối tiếp). Tối đa <?php echo (int) $rf['month_cap']; ?> lượt thưởng mỗi tháng.</p>
	<?php if ( empty( $rf['paywall_on'] ) ) : ?><p class="dz-notice">Hiện mọi tính năng trên Công Viên Chức đang miễn phí. Ngày Gói ôn thi đầy đủ bạn nhận được sẽ được giữ và dùng khi bắt đầu thu phí.</p><?php endif; ?>
	<div><p class="font-black text-ink">Link mời của bạn</p><?php cvc_share_buttons( $rf_link, 'Mình đang ôn thi công chức, viên chức miễn phí trên Công Viên Chức - đề theo tin tuyển dụng, có giải thích từng câu. Vào ôn cùng mình nhé!', 'rf' ); ?></div>
	<div class="grid gap-3" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr))">
		<?php foreach ( array( array( 'Đã thưởng', (int) $rf_c['rewarded'] + (int) $rf_c['capped'] ), array( 'Đang chờ', (int) $rf_c['pending'] ), array( 'Ngày đã nhận', (int) $rf['days_earned'] ), array( 'Lượt thưởng còn tháng này', (int) $rf['month_left'] ) ) as $k ) : ?>
			<div class="dz-card"><p class="text-xs text-ink-muted"><?php echo esc_html( $k[0] ); ?></p><p class="text-2xl font-black tabular-nums"><?php echo (int) $k[1]; ?></p></div>
		<?php endforeach; ?>
	</div>
	<?php if ( ! empty( $rf['pass_until'] ) ) : ?><p>Gói ôn thi đầy đủ của bạn còn hạn đến <b><?php echo esc_html( wp_date( 'H:i d/m/Y', strtotime( (string) $rf['pass_until'] ) ) ); ?></b>.</p><?php endif; ?>
	<?php if ( ! empty( $rf['invited_by'] ) ) : ?><p class="text-ink-muted">Bạn tham gia qua lời mời của <?php echo esc_html( (string) $rf['invited_by']['name'] ); ?> (<?php echo esc_html( $rf_st[ $rf['invited_by']['status'] ] ?? '' ); ?>).</p><?php endif; ?>
	<div>
		<p class="font-black text-ink">Bạn bè đã mời</p>
		<?php if ( empty( $rf['items'] ) ) : ?>
			<p class="text-ink-muted">Chưa có ai. Gửi link vào nhóm Zalo / Facebook ôn thi, hoặc <a class="text-acc font-bold" href="<?php echo esc_url( home_url( '/nhom-on-thi/' ) ); ?>">tạo nhóm ôn thi</a> để cả nhóm cùng xếp hạng.</p>
		<?php else : ?>
			<ul class="space-y-1"><?php foreach ( (array) $rf['items'] as $it ) : ?><li><b><?php echo esc_html( (string) $it['name'] ); ?></b> · <?php echo esc_html( $rf_st[ $it['status'] ] ?? (string) $it['status'] ); ?><?php echo (int) $it['days'] > 0 ? ' · +' . (int) $it['days'] . ' ngày' : ''; ?> <small class="text-ink-muted"><?php echo esc_html( wp_date( 'd/m/Y', strtotime( (string) $it['joined_at'] ) ) ); ?></small></li><?php endforeach; ?></ul>
		<?php endif; ?>
	</div>
	<p class="text-xs text-ink-muted">Không tính lượt bấm chia sẻ, chỉ tính bạn mới thật sự tham gia. Tài khoản tự mời chính mình hoặc tạo ảo sẽ bị loại.</p>
</div>
