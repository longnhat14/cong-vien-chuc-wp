<?php
/**
 * Helper render các state dùng chung khi hiển thị dữ liệu từ Laravel API:
 * loading, empty, error. Các trang domain (Courses, Topics, ...) nên dùng
 * lại các hàm này thay vì tự viết markup riêng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_render_loading_state( string $message = 'Đang tải dữ liệu…' ): void {
	printf(
		'<div class="cvc-state cvc-state--loading">%s</div>',
		esc_html( $message )
	);
}

function cvc_render_empty_state( string $message = 'Chưa có dữ liệu.' ): void {
	printf(
		'<div class="cvc-state cvc-state--empty">%s</div>',
		esc_html( $message )
	);
}

function cvc_render_error_state( string $message = 'Không thể tải dữ liệu, vui lòng thử lại sau.' ): void {
	printf(
		'<div class="cvc-state cvc-state--error">%s</div>',
		esc_html( $message )
	);
}

/**
 * State cho resource không tồn tại (course/topic/lesson slug hoặc id sai).
 * Khác với empty state (danh sách rỗng) và error state (lỗi tạm thời).
 */

/**
 * Section chính đang active dựa trên route hiện tại - dùng cho nav
 * fallback (menu Primary có thể tự set current-menu-item qua wp_nav_menu
 * khi được gán trong wp-admin, không cần hàm này).
 */
function cvc_is_nav_section_active( string $section ): bool {
	$page = (string) get_query_var( 'cvc_page' );

	switch ( $section ) {
		case 'home':
			return '' === $page && is_front_page();
		case 'courses':
			return in_array( $page, array( 'courses', 'course-detail', 'course-lesson' ), true );
		case 'topics':
			return in_array( $page, array( 'topics', 'topic-detail' ), true );
	}

	return false;
}

/**
 * Menu mặc định khi chưa gán menu "Primary" trong wp-admin - đảm bảo
 * luôn có internal link tới các trang domain chính cho SEO + có active
 * state để người dùng biết đang ở đâu.
 */
function cvc_default_nav_fallback(): void {
	$items = array(
		'home'    => array(
			'label' => 'Trang chủ',
			'url'   => home_url( '/' ),
		),
		'courses' => array(
			'label' => 'Khóa học',
			'url'   => cvc_courses_url(),
		),
		'topics'  => array(
			'label' => 'Chủ đề',
			'url'   => cvc_topics_url(),
		),
	);
	?>
	<ul>
		<?php foreach ( $items as $section => $item ) : ?>
			<?php $is_active = cvc_is_nav_section_active( $section ); ?>
			<li class="<?php echo $is_active ? 'cvc-nav-current' : ''; ?>">
				<a href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Danh sách link footer, dùng chung cho footer.php - tránh hardcode
 * lại URL domain chính ở nhiều nơi.
 */
function cvc_render_footer_nav(): void {
	$items = array(
		array(
			'label' => 'Trang chủ',
			'url'   => home_url( '/' ),
		),
		array(
			'label' => 'Khóa học',
			'url'   => cvc_courses_url(),
		),
		array(
			'label' => 'Chủ đề',
			'url'   => cvc_topics_url(),
		),
	);
	?>
	<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'cong-vien-chuc' ); ?>">
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Header 1 section trên homepage: tiêu đề + link "Xem tất cả" tới trang
 * danh sách đầy đủ tương ứng.
 */
function cvc_render_section_header( string $title, string $more_label, string $more_url ): void {
	?>
	<div class="cvc-section__header">
		<h2><?php echo esc_html( $title ); ?></h2>
		<a class="cvc-section__more" href="<?php echo esc_url( $more_url ); ?>">
			<?php echo esc_html( $more_label ); ?> &rarr;
		</a>
	</div>
	<?php
}

function cvc_render_notfound_state( string $message ): void {
	printf(
		'<div class="cvc-state cvc-state--notfound">%s</div>',
		esc_html( $message )
	);
}

/**
 * Breadcrumb đơn giản, semantic HTML. Item cuối luôn là trang hiện tại
 * (không link) dù có truyền url hay không.
 *
 * @param array<int, array{label: string, url?: string}> $items
 */
function cvc_render_breadcrumbs( array $items ): void {
	if ( empty( $items ) ) {
		return;
	}

	$last_index = array_key_last( $items );
	?>
	<nav class="cvc-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'cong-vien-chuc' ); ?>">
		<ol>
			<?php foreach ( $items as $index => $item ) :
				$label = $item['label'] ?? '';
				$url   = $item['url'] ?? '';
				?>
				<li>
					<?php if ( $url && $index !== $last_index ) : ?>
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $label ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Pagination Trước/Sau dùng chung cho các trang danh sách.
 *
 * @param callable $url_builder Nhận (int $page): string, trả về URL trang đó.
 */
function cvc_render_pagination( int $current_page, int $last_page, callable $url_builder ): void {
	if ( $last_page <= 1 ) {
		return;
	}
	?>
	<nav class="cvc-pagination" aria-label="<?php esc_attr_e( 'Phân trang', 'cong-vien-chuc' ); ?>">
		<?php if ( $current_page > 1 ) : ?>
			<a class="cvc-pagination__link cvc-pagination__link--prev" href="<?php echo esc_url( $url_builder( $current_page - 1 ) ); ?>">&laquo; Trước</a>
		<?php else : ?>
			<span class="cvc-pagination__link cvc-pagination__link--disabled">&laquo; Trước</span>
		<?php endif; ?>

		<span class="cvc-pagination__status">
			<?php echo esc_html( sprintf( 'Trang %1$d / %2$d', $current_page, $last_page ) ); ?>
		</span>

		<?php if ( $current_page < $last_page ) : ?>
			<a class="cvc-pagination__link cvc-pagination__link--next" href="<?php echo esc_url( $url_builder( $current_page + 1 ) ); ?>">Sau &raquo;</a>
		<?php else : ?>
			<span class="cvc-pagination__link cvc-pagination__link--disabled">Sau &raquo;</span>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * Badge miễn phí / trả phí dùng chung cho lesson card + lesson list.
 */
function cvc_render_free_badge( bool $is_free ): void {
	if ( $is_free ) {
		echo '<span class="cvc-badge cvc-badge--free">Miễn phí</span>';
		return;
	}

	echo '<span class="cvc-badge cvc-badge--paid">Trả phí</span>';
}

/**
 * Card hiển thị 1 course trong danh sách. Chỉ hiển thị field thực sự có
 * trong API response của GET /api/courses - không suy diễn thêm field.
 *
 * @param array<string, mixed> $course
 * @param int $heading_level Cấp heading cho tên course - 2 khi card nằm
 *                            ngay dưới H1 (trang danh sách), 3 khi card
 *                            nằm trong 1 section có H2 riêng (homepage).
 */
function cvc_render_course_card( array $course, int $heading_level = 2 ): void {
	$slug        = (string) ( $course['slug'] ?? '' );
	$title       = (string) ( $course['title'] ?? '' );
	$summary     = $course['short_description'] ?? '';
	$thumbnail   = $course['thumbnail_url'] ?? null;
	$lessonCount = $course['published_lessons_count'] ?? $course['lesson_count'] ?? null;
	$isFeatured  = ! empty( $course['is_featured'] );
	$url         = cvc_course_url( $slug );
	$tag         = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<?php if ( $thumbnail ) : ?>
			<a class="cvc-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1">
				<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			</a>
		<?php endif; ?>
		<div class="cvc-card__body">
			<?php if ( $isFeatured ) : ?>
				<span class="cvc-badge cvc-badge--featured">Nổi bật</span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $summary ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $summary ); ?></p>
			<?php endif; ?>
			<?php if ( null !== $lessonCount ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d bài học', (int) $lessonCount ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Card hiển thị 1 topic trong danh sách. Chỉ hiển thị field thực sự có
 * trong API response của GET /api/topics.
 *
 * @param array<string, mixed> $topic
 * @param int $heading_level Xem cvc_render_course_card().
 */
function cvc_render_topic_card( array $topic, int $heading_level = 2 ): void {
	$slug        = (string) ( $topic['slug'] ?? '' );
	$name        = (string) ( $topic['name'] ?? '' );
	$description = $topic['description'] ?? '';
	$itemCount   = $topic['published_knowledge_items_count'] ?? null;
	$subjectName = $topic['exam_subject']['name'] ?? null;
	$url         = cvc_topic_url( $slug );
	$tag         = 'h' . max( 2, min( 4, $heading_level ) );
	?>
	<article class="cvc-card">
		<div class="cvc-card__body">
			<?php if ( $subjectName ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $subjectName ); ?></span>
			<?php endif; ?>
			<<?php echo $tag; ?> class="cvc-card__title">
				<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a>
			</<?php echo $tag; ?>>
			<?php if ( $description ) : ?>
				<p class="cvc-card__excerpt"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
			<?php if ( null !== $itemCount ) : ?>
				<p class="cvc-card__meta"><?php echo esc_html( sprintf( '%d kiến thức', (int) $itemCount ) ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
}
