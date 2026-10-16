<?php
/**
 * Danh sách đề thi - giao diện văn bản (16/10/2026). Chỉ hiển thị số liệu thật của từng đề.
 * URL: /de-thi/ và /de-thi/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;
$q     = cvc_listing_query();

$service = new CVC_Exam_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$exams      = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Đề thi thử trắc nghiệm công chức, viên chức' );
cvc_seo_set_description( 'Làm đề thi thử trắc nghiệm công chức, viên chức: chấm điểm ngay khi nộp, xem đáp án và giải thích từng câu.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_exams_url' );

get_header();
?>

<main id="main" class="dz-main">
<div class="dz-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Đề thi thử' ),
		)
	);
	$cvc_total = isset( $pagination['total'] ) ? (int) $pagination['total'] : null;
	cvc_render_listing_hero(
		'Đề thi thử',
		'Đề thi trắc nghiệm ôn thi công chức, viên chức',
		'Làm đề có đồng hồ, chấm điểm ngay khi nộp và xem giải thích, căn cứ pháp lý từng câu.',
		null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'đề thi' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
	);
	$cvc_exam_types = array( 'practice' => 'Luyện tập', 'mock' => 'Thi thử', 'official' => 'Đề chính thức' );
	?>
	<div class="dz-cols is-3">
		<aside class="dz-colside">
			<?php cvc_render_listing_search( cvc_exams_url(), $q, 'Tên đề thi…', 'Tìm đề thi' ); ?>
			<?php cvc_render_listing_explore_nav( 'exams' ); ?>
		</aside>
		<div class="dz-colmain">
			<div class="dz-h2"><h2><?php echo '' !== $q ? 'Kết quả cho “' . esc_html( $q ) . '”' : 'Danh sách đề thi'; ?></h2><span class="dz-muted" style="font-size:15px">Trang <?php echo (int) $currentPg; ?>/<?php echo (int) max( 1, $lastPg ); ?></span></div>
			<?php if ( ! $ok ) : ?>
				<?php cvc_render_error_state( 'Không tải được danh sách đề thi, vui lòng thử lại sau.' ); ?>
			<?php elseif ( empty( $exams ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có đề thi nào.' ); ?>
			<?php else : ?>
				<ul class="dz-list">
					<?php foreach ( $exams as $exam ) :
						$e_slug  = (string) ( $exam['slug'] ?? '' );
						$e_q     = (int) ( $exam['total_questions'] ?? ( $exam['questions_count'] ?? 0 ) );
						$e_min   = (int) ( $exam['duration_minutes'] ?? 0 );
						$e_code  = (string) ( $exam['code'] ?? '' );
						$e_type  = $cvc_exam_types[ (string) ( $exam['exam_type'] ?? '' ) ] ?? '';
						$e_pass  = isset( $exam['passing_score'], $exam['total_score'] ) && (float) $exam['total_score'] > 0 ? rtrim( rtrim( (string) $exam['passing_score'], '0' ), '.' ) . '/' . rtrim( rtrim( (string) $exam['total_score'], '0' ), '.' ) : '';
						$e_desc  = trim( (string) ( $exam['description'] ?? '' ) );
					?>
						<li class="dz-row">
							<div>
								<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_exam_url( $e_slug ) ); ?>"><?php echo esc_html( (string) ( $exam['title'] ?? '' ) ); ?></a></h3>
								<div class="dz-item-meta">
									<?php if ( '' !== $e_type ) : ?><span class="dz-tag"><?php echo esc_html( $e_type ); ?></span><?php endif; ?>
									<?php if ( '' !== $e_code ) : ?><span>Mã đề: <?php echo esc_html( $e_code ); ?></span><?php endif; ?>
									<?php if ( $e_q > 0 ) : ?><span><?php echo (int) $e_q; ?> câu</span><?php endif; ?>
									<?php if ( $e_min > 0 ) : ?><span><?php echo (int) $e_min; ?> phút</span><?php endif; ?>
									<?php if ( '' !== $e_pass ) : ?><span>Điểm đạt <?php echo esc_html( $e_pass ); ?></span><?php endif; ?>
								</div>
								<?php if ( '' !== $e_desc ) : ?><p class="dz-item-sum"><?php echo esc_html( wp_trim_words( $e_desc, 28 ) ); ?></p><?php endif; ?>
							</div>
							<a class="dz-btn dz-btn-sm" href="<?php echo esc_url( cvc_exam_url( $e_slug ) ); ?>">Làm bài</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_exams_url', $q ) ); ?>
			<?php endif; ?>
		</div>
		<aside class="dz-colside">
			<?php cvc_render_study_sidebar( array( 'exams' ) ); ?>
		</aside>
	</div>
</div>
</main>

<?php get_footer(); ?>
