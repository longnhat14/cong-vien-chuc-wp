<?php
/**
 * CÔNG VIÊN CHỨC — KHÓA HỌC ENTERPRISE (Executive 3-Column Architecture)
 * URL: /khoa-hoc/ và /khoa-hoc/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;
$q     = cvc_listing_query();

$service = new CVC_Course_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$courses    = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

// Chưa có khóa học nào bán được (tất cả "Sắp mở") và không tìm kiếm -> báo sắp mở,
// mời đăng ký nhận tin + gợi ý ôn miễn phí (khung tự lấp đầy: đề thi -> văn bản -> chủ đề).
$cvc_all_soon    = $ok && '' === $q && ! empty( $courses ) && 1 === $paged
	&& count( array_filter( $courses, fn ( $c ) => empty( $c['is_coming_soon'] ) ) ) === 0;
$cvc_no_courses  = $ok && '' === $q && empty( $courses ) && 1 === $paged;
$cvc_course_slot = ( $cvc_all_soon || $cvc_no_courses ) ? cvc_content_slot( 'courses_page', array(), array(), 6 ) : null;

cvc_seo_set_title( 'Khóa học ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Khóa học ôn thi công chức, viên chức có lộ trình bài giảng, theo dõi tiến độ và chứng chỉ khi hoàn thành.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_courses_url' );

get_header();
?>

<main id="main" class="dz-main">
<div class="dz-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Khóa học' ),
		)
	);
	$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : null;
	cvc_render_listing_hero(
		'Khóa học',
		'Khóa học ôn thi công chức, viên chức',
		'Khóa học có lộ trình bài giảng, theo dõi tiến độ và chứng chỉ khi hoàn thành.',
		null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'khóa học' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
	);
	?>
	<div class="dz-cols is-3">
		<aside class="dz-colside">
			<?php cvc_render_listing_search( cvc_courses_url(), $q, 'Tên khóa học…', 'Tìm khóa học' ); ?>
			<?php cvc_render_listing_explore_nav( 'courses' ); ?>
		</aside>
		<div class="dz-colmain">
			<?php if ( $cvc_all_soon || $cvc_no_courses ) : ?>
				<section class="dz-section" aria-labelledby="cvc-courses-soon">
					<div class="dz-h2"><h2 id="cvc-courses-soon">Khóa học đang được biên soạn</h2></div>
					<p style="margin:0 0 10px">Các khóa học sẽ mở khi có đủ bài giảng. Để lại email để được báo khi khóa học mở; trong lúc chờ, bạn có thể luyện đề và đọc văn bản miễn phí.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="dz-inline-form" style="max-width:560px">
						<input type="hidden" name="action" value="cvc_newsletter_subscribe">
						<input type="hidden" name="source" value="courses_coming_soon">
						<?php wp_nonce_field( 'cvc_newsletter_subscribe' ); ?>
						<label for="cvc-soon-email" class="sr-only">Email nhận thông báo</label>
						<input id="cvc-soon-email" type="email" name="email" required placeholder="Email của bạn">
						<button type="submit" class="dz-btn dz-btn-primary">Báo tôi khi mở</button>
					</form>
				</section>
				<?php if ( null !== $cvc_course_slot ) : ?>
					<?php cvc_render_content_slot_section( $cvc_course_slot, array( 'id' => 'cvc-courses-slot' ) ); ?>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( ! $ok ) : ?>
				<?php cvc_render_error_state( 'Không tải được danh sách khóa học, vui lòng thử lại sau.' ); ?>
			<?php elseif ( empty( $courses ) ) : ?>
				<?php if ( ! $cvc_no_courses ) : ?>
					<?php cvc_render_empty_state( 'Không tìm thấy khóa học phù hợp.' ); ?>
				<?php endif; ?>
			<?php else : ?>
				<div class="dz-h2"><h2><?php echo $cvc_all_soon ? 'Khóa học sắp mở' : ( '' !== $q ? 'Kết quả cho “' . esc_html( $q ) . '”' : 'Danh sách khóa học' ); ?></h2></div>
				<ul class="dz-list">
					<?php foreach ( $courses as $course ) :
						if ( empty( $course['slug'] ) ) {
							continue;
						}
						$c_price   = (float) ( $course['price'] ?? 0 );
						$c_sale    = isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : null;
						$c_final   = null !== $c_sale ? $c_sale : $c_price;
						$c_lessons = (int) ( $course['published_lessons_count'] ?? 0 );
						$c_minutes = (int) ( $course['duration_minutes'] ?? 0 );
						$c_summary = (string) ( $course['short_description'] ?? ( $course['summary'] ?? '' ) );
					?>
						<li class="dz-row">
							<div>
								<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_course_url( (string) $course['slug'] ) ); ?>"><?php echo esc_html( (string) ( $course['title'] ?? '' ) ); ?></a></h3>
								<div class="dz-item-meta">
									<?php if ( ! empty( $course['is_featured'] ) ) : ?><span class="dz-tag">Nổi bật</span><?php endif; ?>
									<span><?php echo esc_html( $c_lessons > 0 ? $c_lessons . ' bài học' : 'Đang cập nhật bài học' ); ?></span>
									<?php if ( $c_minutes > 0 ) : ?><span><?php echo esc_html( $c_minutes >= 60 ? round( $c_minutes / 60, 1 ) . ' giờ' : $c_minutes . ' phút' ); ?></span><?php endif; ?>
								</div>
								<?php if ( '' !== $c_summary ) : ?><p class="dz-item-sum"><?php echo esc_html( wp_trim_words( $c_summary, 28 ) ); ?></p><?php endif; ?>
							</div>
							<div class="dz-price">
								<?php if ( ! empty( $course['is_coming_soon'] ) ) : ?><span class="dz-tag is-warn">Sắp mở</span>
								<?php elseif ( $c_final <= 0 ) : ?><strong>Miễn phí</strong>
								<?php else : ?><strong><?php echo esc_html( cvc_format_vnd( $c_final ) ); ?></strong><?php if ( null !== $c_sale && $c_sale < $c_price ) : ?><s><?php echo esc_html( cvc_format_vnd( $c_price ) ); ?></s><?php endif; ?>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_courses_url', $q ) ); ?>
			<?php endif; ?>
		</div>
		<aside class="dz-colside">
			<?php cvc_render_study_sidebar( array( 'exams' ) ); ?>
		</aside>
	</div>
</div>
</main>

<?php get_footer(); ?>
