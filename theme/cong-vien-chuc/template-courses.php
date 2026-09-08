<?php
/**
 * Danh sách khóa học - /khoa-hoc/ và /khoa-hoc/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Course_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$courses    = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok ) {
	status_header( 503 );
}

cvc_seo_set_title( 'Khóa học' );
cvc_seo_set_description( 'Danh sách khóa học luyện thi công chức, viên chức tại Công Viên Chức.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_courses_url' );
cvc_seo_set_pagination_links(
	$currentPg > 1 ? cvc_courses_url( $currentPg - 1 ) : null,
	$currentPg < $lastPg ? cvc_courses_url( $currentPg + 1 ) : null
);

get_header();
?>

<main id="main" class="container cvc-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array( 'label' => 'Khóa học' ),
		)
	);
	?>

	<header class="cvc-page-header cvc-page-header--illustrated">
		<div>
			<h1>Khóa học</h1>
		</div>
		<?php
		/*
		 * Phase 10A.19: illustration Lecturer/Expert (CVC Homepage Icon
		 * Pack V1) - thuần trang trí, KHÔNG kèm tên/chức danh/kinh nghiệm
		 * (website chưa có dữ liệu giảng viên thật - xem migration
		 * courses, không có field instructor nào) để tránh tạo hồ sơ giả.
		 *
		 * Cả 3 file gốc có caption "0N_ten_file.png" + nhãn tiếng Việt bake
		 * sẵn ở dải cuối ảnh (y=102-108/109px, đã đo bằng background-diff -
		 * không phải suy đoán). KHÔNG được crop lại file gốc nên dùng
		 * .cvc-lecturer-illustration (overflow:hidden, cao 72px) để chỉ
		 * hiển thị đúng phần nhân vật (0-91/109px) - ảnh PNG/WebP gốc giữ
		 * nguyên 100%, không xử lý lại pixel nào.
		 */
		?>
		<div class="cvc-page-header__illustration" aria-hidden="true">
			<span class="cvc-lecturer-illustration"><?php cvc_render_homepage_icon_pack_v1( 'lecturers/lecturer-male', 63, 86 ); ?></span>
			<span class="cvc-lecturer-illustration"><?php cvc_render_homepage_icon_pack_v1( 'lecturers/expert', 63, 86 ); ?></span>
			<span class="cvc-lecturer-illustration"><?php cvc_render_homepage_icon_pack_v1( 'lecturers/lecturer-female', 63, 86 ); ?></span>
		</div>
	</header>

	<?php if ( ! $ok ) : ?>
		<?php cvc_render_error_state(); ?>
	<?php elseif ( empty( $courses ) ) : ?>
		<?php cvc_render_empty_state( 'Chưa có khóa học nào.' ); ?>
	<?php else : ?>
		<div class="cvc-card-grid">
			<?php foreach ( $courses as $course ) : ?>
				<?php cvc_render_course_card( $course ); ?>
			<?php endforeach; ?>
		</div>

		<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_courses_url' ); ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
