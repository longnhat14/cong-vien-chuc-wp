<?php
/**
 * Lộ trình học tập (Phase 10) - GET /api/learning-paths (danh sách) +
 * GET /api/learning-paths/{id} (chi tiết, chỉ gọi thêm khi người dùng bấm
 * xem 1 lộ trình cụ thể - tránh N+1 gọi show() cho từng path trong danh
 * sách - Phần Performance).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$path_service = new CVC_Learning_Path_Service();
$list_result  = $path_service->list( array( 'per_page' => 20 ), $token );

if ( ! $list_result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $list_result ) );
	return;
}

$paths = $list_result['data']['data']['data'] ?? array();

// Danh sách mục tiêu ACTIVE để chọn khi sinh lộ trình mới - LearningPath
// chỉ nên sinh từ 1 Goal đang thực sự theo đuổi.
$goals_result = ( new CVC_Goal_Service() )->list( array( 'status' => 'active', 'per_page' => 50 ), $token );
$active_goals = $goals_result['ok'] ? ( $goals_result['data']['data']['data'] ?? array() ) : array();

$viewing_id = isset( $_GET['path_id'] ) ? absint( $_GET['path_id'] ) : 0;
$viewing    = null;

if ( $viewing_id > 0 ) {
	$show_result = $path_service->find( (string) $viewing_id, $token );
	if ( $show_result['ok'] ) {
		$viewing = $show_result['data']['data'] ?? null;
	}
}

$item_type_labels = array(
	'exam_subject' => 'Môn thi',
	'topic'        => 'Chủ đề',
	'course'       => 'Khóa học',
);
?>

<section class="cvc-account-section">
	<h2>Sinh lộ trình mới từ mục tiêu</h2>
	<?php if ( empty( $active_goals ) ) : ?>
		<p class="cvc-form__hint">Bạn cần có ít nhất 1 <a href="<?php echo esc_url( cvc_account_url( 'goals' ) ); ?>">mục tiêu đang theo đuổi</a> để sinh lộ trình học tập.</p>
	<?php else : ?>
		<form class="cvc-form cvc-form--inline" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cvc_learning_path_generate' ); ?>
			<input type="hidden" name="action" value="cvc_learning_path_generate">

			<div class="cvc-form__field">
				<label for="cvc-goal-select">Chọn mục tiêu</label>
				<select id="cvc-goal-select" name="exam_goal_id" required>
					<?php foreach ( $active_goals as $goal ) : ?>
						<option value="<?php echo esc_attr( $goal['id'] ); ?>"><?php echo esc_html( $goal['title'] ?? ( 'Mục tiêu #' . $goal['id'] ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<button type="submit" class="cvc-btn cvc-btn--primary">Sinh lộ trình</button>
		</form>
	<?php endif; ?>
</section>

<section class="cvc-account-section">
	<h2>Lộ trình của bạn</h2>

	<?php if ( empty( $paths ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa có lộ trình học tập nào.' ); ?>
	<?php else : ?>
		<div class="cvc-goal-list">
			<?php foreach ( $paths as $path ) : ?>
				<article class="cvc-card cvc-goal-card">
					<div class="cvc-card__body">
						<span class="cvc-badge cvc-badge--status"><?php echo esc_html( cvc_status_label( (string) ( $path['status'] ?? 'active' ) ) ); ?></span>
						<h3 class="cvc-card__title"><?php echo esc_html( $path['exam_goal']['title'] ?? ( 'Lộ trình #' . $path['id'] ) ); ?></h3>
						<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d bước học tập · hoàn thành %d%%', (int) ( $path['items_count'] ?? 0 ), (int) ( $path['progress_percent'] ?? 0 ) ) ); ?></p>
						<div class="cvc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (int) ( $path['progress_percent'] ?? 0 ) ); ?>" aria-label="Tiến độ lộ trình">
							<span class="cvc-progress__bar" style="width: <?php echo esc_attr( (int) ( $path['progress_percent'] ?? 0 ) ); ?>%"></span>
						</div>
						<div class="cvc-card__actions">
							<a class="cvc-btn cvc-btn--small cvc-btn--secondary" href="<?php echo esc_url( add_query_arg( 'path_id', $path['id'], cvc_account_url( 'learning-path' ) ) ); ?>">Xem chi tiết</a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>

<?php if ( $viewing_id > 0 ) : ?>
	<section class="cvc-account-section">
		<h2>Chi tiết lộ trình</h2>
		<?php if ( null === $viewing ) : ?>
			<?php cvc_render_error_state( 'Không thể tải lộ trình này.' ); ?>
		<?php else : ?>
			<?php $viewing_progress = (int) ( $viewing['progress_percent'] ?? 0 ); ?>
			<p class="cvc-card__meta">
				<?php echo esc_html( sprintf( 'Đã hoàn thành %d%%', $viewing_progress ) ); ?>
				<?php if ( ! empty( $viewing['target_date'] ) ) : ?>
					· <?php echo esc_html( 'Hạn mục tiêu: ' . cvc_format_date_vn( $viewing['target_date'] ) ); ?>
				<?php endif; ?>
			</p>
			<div class="cvc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $viewing_progress ); ?>" aria-label="Tiến độ lộ trình">
				<span class="cvc-progress__bar" style="width: <?php echo esc_attr( $viewing_progress ); ?>%"></span>
			</div>
			<?php if ( empty( $viewing['items'] ) ) : ?>
				<?php cvc_render_empty_state( 'Lộ trình này chưa có bước học. Hãy gắn mục tiêu với kỳ thi, đợt tuyển dụng hoặc vị trí cụ thể rồi sinh lại lộ trình.' ); ?>
			<?php endif; ?>
			<ol class="cvc-learning-path-steps">
				<?php foreach ( ( $viewing['items'] ?? array() ) as $item ) : ?>
					<?php
					$type    = (string) ( $item['item_type'] ?? '' );
					$label   = $item_type_labels[ $type ] ?? $type;
					$entity  = $item[ $type ] ?? null;
					$name    = is_array( $entity ) ? ( $entity['name'] ?? $entity['title'] ?? '' ) : '';
					$link    = null;
					if ( 'topic' === $type && is_array( $entity ) && ! empty( $entity['slug'] ) ) {
						$link = cvc_topic_url( $entity['slug'] );
					} elseif ( 'course' === $type && is_array( $entity ) && ! empty( $entity['slug'] ) ) {
						$link = cvc_course_url( $entity['slug'] );
					}
					?>
					<?php
					$step_status = (string) ( $item['status'] ?? 'pending' );
					$next_steps  = array(
						'pending'     => array( 'in_progress' => 'Bắt đầu', 'completed' => 'Đã xong', 'skipped' => 'Bỏ qua' ),
						'in_progress' => array( 'completed' => 'Đã xong', 'pending' => 'Đặt lại' ),
						'completed'   => array( 'pending' => 'Học lại' ),
						'skipped'     => array( 'pending' => 'Khôi phục' ),
					);
					$step_labels = array(
						'pending'     => 'Chưa học',
						'in_progress' => 'Đang học',
						'completed'   => 'Đã xong',
						'skipped'     => 'Đã bỏ qua',
					);
					?>
					<li class="cvc-learning-path-steps__item cvc-learning-path-steps__item--<?php echo esc_attr( $step_status ); ?>">
						<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $label ); ?></span>
						<?php if ( $link ) : ?>
							<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $name ); ?>
						<?php endif; ?>
						<span class="cvc-badge cvc-badge--status cvc-badge--status-<?php echo esc_attr( $step_status ); ?>"><?php echo esc_html( $step_labels[ $step_status ] ?? $step_status ); ?></span>
						<span class="cvc-learning-path-steps__actions">
							<?php foreach ( ( $next_steps[ $step_status ] ?? array() ) as $to_status => $to_label ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'cvc_learning_path_item_status' ); ?>
									<input type="hidden" name="action" value="cvc_learning_path_item_status">
									<input type="hidden" name="path_id" value="<?php echo esc_attr( $viewing['id'] ?? $viewing_id ); ?>">
									<input type="hidden" name="item_id" value="<?php echo esc_attr( $item['id'] ?? 0 ); ?>">
									<input type="hidden" name="status" value="<?php echo esc_attr( $to_status ); ?>">
									<button type="submit" class="cvc-btn cvc-btn--small cvc-btn--secondary"><?php echo esc_html( $to_label ); ?></button>
								</form>
							<?php endforeach; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</section>
<?php endif; ?>
