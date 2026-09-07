<?php
/**
 * Việc làm phù hợp (Phase 10) - GET /api/recruitment-matches thật
 * (RecruitmentMatchingService, Phase 9). `recruitment_expired` derived
 * field đã tính sẵn ở backend (RecruitmentMatchController::index) - KHÔNG
 * tự so sánh ngày ở đây (Phần XV - "không tự suy diễn, dùng đúng field
 * backend trả").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result = ( new CVC_Recruitment_Match_Service() )->list( array( 'per_page' => 30 ), $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$matches = $result['data']['data']['data'] ?? array();

$transitions = array(
	'new'  => array( 'seen' => 'Đánh dấu đã xem', 'interested' => 'Quan tâm', 'dismiss' => 'Bỏ qua' ),
	'seen' => array( 'interested' => 'Quan tâm', 'dismiss' => 'Bỏ qua' ),
);
?>

<section class="cvc-account-section">
	<h2>Tin tuyển dụng phù hợp mục tiêu của bạn</h2>

	<?php if ( empty( $matches ) ) : ?>
		<?php cvc_render_empty_state( 'Chưa có tin tuyển dụng nào phù hợp với mục tiêu của bạn. Hãy đặt mục tiêu ôn thi (tỉnh/thành, cơ quan, vị trí) để hệ thống tự động tìm tin phù hợp.' ); ?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_account_url( 'goals' ) ); ?>">Quản lý mục tiêu</a></p>
	<?php else : ?>
		<div class="cvc-goal-list">
			<?php foreach ( $matches as $match ) : ?>
				<?php
				$status      = (string) ( $match['status'] ?? 'new' );
				$available   = $transitions[ $status ] ?? array();
				$recruitment = is_array( $match['recruitment'] ?? null ) ? $match['recruitment'] : array();
				$expired     = ! empty( $match['recruitment_expired'] );
				$agency      = is_array( $recruitment['agency'] ?? null ) ? $recruitment['agency'] : null;
				$province    = is_array( $recruitment['province'] ?? null ) ? $recruitment['province'] : null;
				?>
				<article class="cvc-card cvc-goal-card" data-cvc-track="recruitment_match_impression" data-cvc-target-type="recruitment_match" data-cvc-target-id="<?php echo esc_attr( $match['id'] ); ?>">
					<div class="cvc-card__body">
						<span class="cvc-badge cvc-badge--status cvc-badge--status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( cvc_status_label( $status ) ); ?></span>
						<?php if ( $expired ) : ?>
							<span class="cvc-badge cvc-badge--expired">Đã hết hạn</span>
						<?php endif; ?>
						<?php if ( isset( $match['score'] ) ) : ?>
							<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( sprintf( 'Độ phù hợp: %d%%', (int) round( (float) $match['score'] ) ) ); ?></span>
						<?php endif; ?>

						<h3 class="cvc-card__title">
							<?php if ( ! empty( $recruitment['slug'] ) ) : ?>
								<a href="<?php echo esc_url( cvc_recruitment_url( $recruitment['slug'] ) ); ?>" data-cvc-click-track="recruitment_match_open" data-cvc-target-type="recruitment_match" data-cvc-target-id="<?php echo esc_attr( $match['id'] ); ?>"><?php echo esc_html( $recruitment['title'] ?? '(Tin đã bị gỡ)' ); ?></a>
							<?php else : ?>
								(Tin tuyển dụng đã bị gỡ)
							<?php endif; ?>
						</h3>
						<?php if ( $agency || $province ) : ?>
							<p class="cvc-card__meta"><?php echo esc_html( implode( ' · ', array_filter( array( $agency['name'] ?? null, $province['name'] ?? null ) ) ) ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $available ) ) : ?>
							<div class="cvc-card__actions">
								<?php foreach ( $available as $transition => $label ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'cvc_match_transition' ); ?>
										<input type="hidden" name="action" value="cvc_match_transition">
										<input type="hidden" name="match_id" value="<?php echo esc_attr( $match['id'] ); ?>">
										<input type="hidden" name="transition" value="<?php echo esc_attr( $transition ); ?>">
										<button type="submit" class="cvc-btn cvc-btn--small cvc-btn--secondary"><?php echo esc_html( $label ); ?></button>
									</form>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
