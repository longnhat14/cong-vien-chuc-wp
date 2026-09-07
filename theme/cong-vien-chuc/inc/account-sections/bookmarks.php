<?php
/**
 * Đã đánh dấu (Phase 10) - GET /api/bookmarks thật, mỗi item trả nguyên
 * bản ghi entity đã bookmark (`bookmarkable`) - KHÔNG qua adapter search
 * (khác recommendations.php) vì bookmark trả model gốc, không phải shape
 * transformPublicEntity(). Map bookmarkable_type (FQCN) -> nhãn + URL
 * builder, giữ 1 nguồn duy nhất ở đây.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result = ( new CVC_Bookmark_Service() )->list( array( 'per_page' => 30 ), $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$bookmarks = $result['data']['data']['data'] ?? array();

/**
 * @return array{label: string, url: string|null}
 */
function cvc_bookmark_present( array $bookmark ): array {
	$type   = (string) ( $bookmark['bookmarkable_type'] ?? '' );
	$entity = is_array( $bookmark['bookmarkable'] ?? null ) ? $bookmark['bookmarkable'] : array();
	$slug   = $entity['slug'] ?? null;

	$map = array(
		'App\\Models\\Recruitment'   => array( 'Tuyển dụng', 'title', 'cvc_recruitment_url' ),
		'App\\Models\\Course'        => array( 'Khóa học', 'title', 'cvc_course_url' ),
		'App\\Models\\Topic'         => array( 'Chủ đề', 'name', 'cvc_topic_url' ),
		'App\\Models\\KnowledgeItem' => array( 'Kiến thức', 'title', 'cvc_knowledge_item_url' ),
		'App\\Models\\Exam'          => array( 'Đề thi', 'title', 'cvc_exam_url' ),
	);

	if ( isset( $map[ $type ] ) ) {
		[ $label, $title_field, $url_fn ] = $map[ $type ];
		$title = $entity[ $title_field ] ?? '(Không có tiêu đề)';
		$url   = ( $slug && function_exists( $url_fn ) ) ? $url_fn( $slug ) : null;

		return array( 'label' => $label, 'title' => $title, 'url' => $url );
	}

	if ( 'App\\Models\\CourseLesson' === $type ) {
		return array( 'label' => 'Bài học', 'title' => $entity['title'] ?? '(Không có tiêu đề)', 'url' => null );
	}

	return array( 'label' => 'Nội dung', 'title' => '(Không xác định)', 'url' => null );
}
?>

<section class="cvc-account-section">
	<h2>Nội dung đã đánh dấu</h2>

	<?php if ( empty( $bookmarks ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa đánh dấu nội dung nào. Hãy bấm biểu tượng đánh dấu trên các trang khóa học, chủ đề, tin tuyển dụng, kiến thức hoặc đề thi.' ); ?>
	<?php else : ?>
		<div class="cvc-goal-list">
			<?php foreach ( $bookmarks as $bookmark ) : ?>
				<?php $present = cvc_bookmark_present( $bookmark ); ?>
				<article class="cvc-card cvc-goal-card">
					<div class="cvc-card__body">
						<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $present['label'] ); ?></span>
						<h3 class="cvc-card__title">
							<?php if ( $present['url'] ) : ?>
								<a href="<?php echo esc_url( $present['url'] ); ?>"><?php echo esc_html( $present['title'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $present['title'] ); ?>
							<?php endif; ?>
						</h3>
						<div class="cvc-card__actions">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'cvc_bookmark_remove' ); ?>
								<input type="hidden" name="action" value="cvc_bookmark_remove">
								<input type="hidden" name="bookmark_id" value="<?php echo esc_attr( $bookmark['id'] ); ?>">
								<input type="hidden" name="redirect_to" value="<?php echo esc_attr( wp_make_link_relative( cvc_account_url( 'bookmarks' ) ) ); ?>">
								<button type="submit" class="cvc-btn cvc-btn--small cvc-btn--secondary">Bỏ đánh dấu</button>
							</form>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
