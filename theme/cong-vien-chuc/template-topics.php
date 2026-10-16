<?php
/**
 * CÔNG VIÊN CHỨC — LỘ TRÌNH THĂNG TIẾN (Executive 3-Column Architecture)
 * URL: /chu-de/ và /chu-de/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;
$q     = cvc_listing_query();

$service = new CVC_Topic_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
		'search'   => $q,
	)
);

$ok      = (bool) ( $result['ok'] ?? false );
$rawdata = $ok ? ( $result['data']['data'] ?? ( $result['data'] ?? array() ) ) : array();
if ( isset( $rawdata['data'] ) && is_array( $rawdata['data'] ) ) {
	$topics    = $rawdata['data'];
	$currentPg = (int) ( $rawdata['current_page'] ?? $paged );
	$lastPg    = (int) ( $rawdata['last_page'] ?? 1 );
} else {
	$topics    = is_array( $rawdata ) ? $rawdata : array();
	$currentPg = $paged;
	$lastPg    = 1;
}

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Chủ đề ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Chủ đề ôn thi theo từng môn, liên kết tới bài kiến thức và câu hỏi luyện tập.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_topics_url' );

get_header();
?>

<main id="main" class="dz-main">
<div class="dz-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Chủ đề ôn thi' ),
		)
	);
	$cvc_total = isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null;
	cvc_render_listing_hero(
		'Chủ đề ôn thi',
		'Chủ đề ôn thi theo môn',
		'Hệ thống chủ đề theo từng môn thi, liên kết tới bài kiến thức và câu hỏi luyện tập.',
		null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'chủ đề' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
	);
	?>
	<div class="dz-cols is-3">
		<aside class="dz-colside">
			<?php cvc_render_listing_search( cvc_topics_url(), $q, 'Tên chủ đề…', 'Tìm chủ đề' ); ?>
			<?php cvc_render_listing_explore_nav( 'topics' ); ?>
		</aside>
		<div class="dz-colmain">
			<div class="dz-h2"><h2><?php echo '' !== $q ? 'Kết quả cho “' . esc_html( $q ) . '”' : 'Danh mục chủ đề'; ?></h2><span class="dz-muted" style="font-size:15px">Trang <?php echo (int) $currentPg; ?>/<?php echo (int) max( 1, $lastPg ); ?></span></div>
			<?php if ( ! $ok ) : ?>
				<?php cvc_render_error_state( 'Không tải được danh sách chủ đề, vui lòng thử lại sau.' ); ?>
			<?php elseif ( empty( $topics ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có chủ đề nào.' ); ?>
			<?php else : ?>
				<ul class="dz-list">
					<?php foreach ( $topics as $topic ) :
						$t_slug  = (string) ( $topic['slug'] ?? '' );
						if ( '' === $t_slug ) {
							continue;
						}
						$t_count = (int) ( $topic['published_knowledge_items_count'] ?? 0 );
						$t_subj  = (string) ( $topic['exam_subject']['name'] ?? '' );
						$t_kids  = array_values( array_filter( (array) ( $topic['children'] ?? array() ), 'is_array' ) );
						$t_desc  = trim( (string) ( $topic['description'] ?? '' ) );
					?>
						<li>
							<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_topic_url( $t_slug ) ); ?>"><?php echo esc_html( (string) ( $topic['name'] ?? '' ) ); ?></a></h3>
							<div class="dz-item-meta">
								<?php if ( '' !== $t_subj ) : ?><span class="dz-tag"><?php echo esc_html( $t_subj ); ?></span><?php endif; ?>
								<?php if ( $t_count > 0 ) : ?><span><?php echo (int) $t_count; ?> bài kiến thức</span><?php endif; ?>
								<?php if ( ! empty( $t_kids ) ) : ?><span><?php echo count( $t_kids ); ?> chủ đề con</span><?php endif; ?>
							</div>
							<?php if ( '' !== $t_desc && $t_desc !== (string) ( $topic['name'] ?? '' ) ) : ?><p class="dz-item-sum"><?php echo esc_html( wp_trim_words( $t_desc, 30 ) ); ?></p><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_topics_url', $q ) ); ?>
			<?php endif; ?>
		</div>
		<aside class="dz-colside">
			<section class="dz-box" aria-labelledby="dz-goal-title">
				<h2 id="dz-goal-title" class="dz-box-head">Đặt mục tiêu ôn thi</h2>
				<div class="dz-box-body">
					<p style="margin:0 0 10px">Đặt mục tiêu thi tuyển hoặc nâng ngạch để nhận gợi ý chủ đề và đề thi phù hợp.</p>
					<a href="<?php echo esc_url( cvc_is_logged_in() ? cvc_account_url( 'goals' ) : cvc_login_url( cvc_account_url( 'goals' ) ) ); ?>" class="dz-btn dz-btn-primary">Tạo mục tiêu</a>
				</div>
			</section>
			<?php cvc_render_study_sidebar( array( 'topics' ) ); ?>
		</aside>
	</div>
</div>
</main>

<?php get_footer(); ?>
