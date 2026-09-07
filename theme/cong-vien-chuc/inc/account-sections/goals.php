<?php
/**
 * Mục tiêu ôn thi (Phase 10) - GET /api/goals thật. Form tạo mới ở đây
 * CHỈ nhận title/notes (mọi field tham chiếu entity - province_id/
 * agency_id/position_id/exam_id/recruitment_id - đều nullable ở backend,
 * xem ExamGoalController::validateGoalFields() - nên 1 mục tiêu "tự do"
 * không gắn entity cụ thể vẫn hợp lệ). Gắn mục tiêu với 1 Kỳ thi/Tin
 * tuyển dụng CỤ THỂ được làm qua nút "Đặt làm mục tiêu" ngay trên trang
 * chi tiết kỳ thi/tin tuyển dụng đó (id lấy từ dữ liệu thật đã tải sẵn ở
 * trang đó, không bắt người dùng tự gõ ID).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result = ( new CVC_Goal_Service() )->list( array( 'per_page' => 30 ), $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$goals = $result['data']['data']['data'] ?? array();

$transitions = array(
	'draft'     => array( 'activate' => 'Kích hoạt', 'archive' => 'Lưu trữ' ),
	'active'    => array( 'pause' => 'Tạm dừng', 'complete' => 'Hoàn thành', 'archive' => 'Lưu trữ' ),
	'paused'    => array( 'resume' => 'Tiếp tục', 'archive' => 'Lưu trữ' ),
	'completed' => array(),
	'archived'  => array(),
);
?>

<section class="cvc-account-section">
	<h2>Tạo mục tiêu mới</h2>
	<form class="cvc-form cvc-form--inline" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cvc_goal_save' ); ?>
		<input type="hidden" name="action" value="cvc_goal_save">

		<div class="cvc-form__field">
			<label for="cvc-goal-title">Tên mục tiêu</label>
			<input type="text" id="cvc-goal-title" name="title" placeholder="VD: Ôn thi công chức tỉnh..." required>
		</div>
		<div class="cvc-form__field">
			<label for="cvc-goal-notes">Ghi chú</label>
			<input type="text" id="cvc-goal-notes" name="notes" placeholder="Không bắt buộc">
		</div>

		<button type="submit" class="cvc-btn cvc-btn--primary">Tạo mục tiêu</button>
	</form>
</section>

<section class="cvc-account-section">
	<h2>Danh sách mục tiêu</h2>

	<?php if ( empty( $goals ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa có mục tiêu ôn thi nào. Hãy tạo mục tiêu đầu tiên ở trên, hoặc bấm "Đặt làm mục tiêu" trên trang chi tiết 1 kỳ thi/tin tuyển dụng.' ); ?>
	<?php else : ?>
		<div class="cvc-goal-list">
			<?php foreach ( $goals as $goal ) : ?>
				<?php
				$status      = (string) ( $goal['status'] ?? 'draft' );
				$available   = $transitions[ $status ] ?? array();
				$entity_bits = array();
				foreach ( array(
					'province'    => 'Tỉnh/thành',
					'agency'      => 'Cơ quan',
					'position'    => 'Vị trí',
					'exam'        => 'Đề thi',
					'recruitment' => 'Tin tuyển dụng',
				) as $rel => $rel_label ) {
					$rel_data = $goal[ $rel ] ?? null;
					if ( is_array( $rel_data ) ) {
						$name = $rel_data['name'] ?? $rel_data['title'] ?? '';
						if ( '' !== $name ) {
							$entity_bits[] = $rel_label . ': ' . $name;
						}
					}
				}
				?>
				<article class="cvc-card cvc-goal-card">
					<div class="cvc-card__body">
						<span class="cvc-badge cvc-badge--status cvc-badge--status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( cvc_status_label( $status ) ); ?></span>
						<h3 class="cvc-card__title"><?php echo esc_html( $goal['title'] ?? 'Mục tiêu chưa đặt tên' ); ?></h3>
						<?php if ( ! empty( $goal['notes'] ) ) : ?>
							<p class="cvc-card__excerpt"><?php echo esc_html( $goal['notes'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $entity_bits ) ) : ?>
							<p class="cvc-card__meta"><?php echo esc_html( implode( ' · ', $entity_bits ) ); ?></p>
						<?php endif; ?>

						<div class="cvc-card__actions">
							<?php foreach ( $available as $transition => $label ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'cvc_goal_transition' ); ?>
									<input type="hidden" name="action" value="cvc_goal_transition">
									<input type="hidden" name="goal_id" value="<?php echo esc_attr( $goal['id'] ); ?>">
									<input type="hidden" name="transition" value="<?php echo esc_attr( $transition ); ?>">
									<button type="submit" class="cvc-btn cvc-btn--small cvc-btn--secondary"><?php echo esc_html( $label ); ?></button>
								</form>
							<?php endforeach; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
