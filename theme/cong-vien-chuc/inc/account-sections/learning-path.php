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
						<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d bước học tập', (int) ( $path['items_count'] ?? 0 ) ) ); ?></p>
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
					<li class="cvc-learning-path-steps__item">
						<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $label ); ?></span>
						<?php if ( $link ) : ?>
							<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $name ); ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</section>
<?php endif; ?>
