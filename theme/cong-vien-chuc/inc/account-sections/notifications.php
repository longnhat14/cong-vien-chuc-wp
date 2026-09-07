<?php
/**
 * Thông báo (Phase 10) - GET /api/notifications thật (Laravel Notifiable
 * built-in, Phase 9). `data` là payload tự do do từng Notification class
 * quyết định (hiện chỉ có RecruitmentMatchNotification) - đọc field
 * title/body/target_type/target_id/metadata đúng như notification đó tự
 * khai (xem app/Notifications/RecruitmentMatchNotification::toDatabase()),
 * không suy diễn field không có.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$service = new CVC_Notification_Service();
$page    = isset( $_GET['npage'] ) ? max( 1, absint( $_GET['npage'] ) ) : 1;
$result  = $service->list( array( 'per_page' => 20, 'page' => $page ), $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$pagination    = $result['data']['data'] ?? array();
$notifications = $pagination['data'] ?? array();
?>

<section class="cvc-account-section">
	<div class="cvc-section-header-row">
		<h2>Thông báo của bạn</h2>
		<?php if ( ! empty( $notifications ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cvc_notification_read_all' ); ?>
				<input type="hidden" name="action" value="cvc_notification_read_all">
				<button type="submit" class="cvc-btn cvc-btn--small cvc-btn--secondary">Đánh dấu tất cả đã đọc</button>
			</form>
		<?php endif; ?>
	</div>

	<?php if ( empty( $notifications ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa có thông báo nào.' ); ?>
	<?php else : ?>
		<div class="cvc-notification-list">
			<?php foreach ( $notifications as $notification ) : ?>
				<?php
				$data      = is_array( $notification['data'] ?? null ) ? $notification['data'] : array();
				$is_unread = empty( $notification['read_at'] );
				$link      = null;

				if ( 'recruitment' === ( $data['target_type'] ?? '' ) && ! empty( $data['metadata']['recruitment_slug'] ) ) {
					$link = cvc_recruitment_url( $data['metadata']['recruitment_slug'] );
				}
				?>
				<article class="cvc-notification-item<?php echo $is_unread ? ' is-unread' : ''; ?>">
					<div class="cvc-notification-item__body">
						<h3 class="cvc-notification-item__title">
							<?php if ( $link ) : ?>
								<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $data['title'] ?? 'Thông báo' ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $data['title'] ?? 'Thông báo' ); ?>
							<?php endif; ?>
						</h3>
						<p><?php echo esc_html( $data['body'] ?? '' ); ?></p>
						<p class="cvc-card__meta"><?php echo esc_html( cvc_format_date_vn( $notification['created_at'] ?? null ) ); ?></p>
					</div>
					<?php if ( $is_unread ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'cvc_notification_read' ); ?>
							<input type="hidden" name="action" value="cvc_notification_read">
							<input type="hidden" name="notification_id" value="<?php echo esc_attr( $notification['id'] ); ?>">
							<button type="submit" class="cvc-btn cvc-btn--small cvc-btn--secondary">Đánh dấu đã đọc</button>
						</form>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>

		<?php
		cvc_render_pagination(
			(int) ( $pagination['current_page'] ?? $page ),
			(int) ( $pagination['last_page'] ?? 1 ),
			fn( int $target_page ): string => add_query_arg( 'npage', $target_page, cvc_account_url( 'notifications' ) )
		);
		?>
	<?php endif; ?>
</section>
