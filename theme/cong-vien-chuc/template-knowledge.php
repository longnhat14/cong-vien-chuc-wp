<?php
/**
 * CÔNG VIÊN CHỨC — THƯ VIỆN KIẾN THỨC CÔNG VỤ (Executive 3-Column Architecture)
 * URL: /kien-thuc/ và /kien-thuc/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;
$q     = cvc_listing_query();

$service = new CVC_Knowledge_Service();
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
	$items     = $rawdata['data'];
	$currentPg = (int) ( $rawdata['current_page'] ?? $paged );
	$lastPg    = (int) ( $rawdata['last_page'] ?? 1 );
} else {
	$items     = is_array( $rawdata ) ? $rawdata : array();
	$currentPg = $paged;
	$lastPg    = 1;
}

// Không còn fallback dữ liệu mẫu (fixture) khi API lỗi/trống - hiện đúng trạng thái.

cvc_seo_set_title( 'Kiến thức ôn thi công chức, viên chức' );
cvc_seo_set_description( 'Bài viết kiến thức ôn thi công chức, viên chức theo chủ đề, gắn với văn bản pháp luật liên quan.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_knowledge_url' );

get_header();
?>

<main id="main" class="dz-main">
<div class="dz-wrap">
	<?php
	cvc_render_breadcrumbs(
		array(
			array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'label' => 'Kiến thức' ),
		)
	);
	$cvc_total = isset( $rawdata['total'] ) ? (int) $rawdata['total'] : null;
	cvc_render_listing_hero(
		'Kiến thức',
		'Kiến thức ôn thi công vụ',
		'Bài viết hệ thống kiến thức theo chủ đề, gắn với văn bản pháp luật liên quan.',
		null !== $cvc_total ? array( array( number_format_i18n( $cvc_total ), 'bài viết' . ( '' !== $q ? ' khớp từ khóa' : '' ) ) ) : array()
	);
	?>
	<div class="dz-cols is-3">
		<aside class="dz-colside">
			<?php cvc_render_listing_search( cvc_knowledge_url(), $q, 'Từ khóa…', 'Tìm bài kiến thức' ); ?>
			<?php cvc_render_listing_explore_nav( 'knowledge' ); ?>
		</aside>
		<div class="dz-colmain">
			<div class="dz-h2"><h2><?php echo '' !== $q ? 'Kết quả cho “' . esc_html( $q ) . '”' : 'Bài viết mới'; ?></h2><span class="dz-muted" style="font-size:15px">Trang <?php echo (int) $currentPg; ?>/<?php echo (int) max( 1, $lastPg ); ?></span></div>
			<?php if ( ! $ok ) : ?>
				<?php cvc_render_error_state( 'Không tải được danh sách bài viết kiến thức, vui lòng thử lại sau.' ); ?>
			<?php elseif ( empty( $items ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có bài viết kiến thức nào.' ); ?>
			<?php endif; ?>
			<ul class="dz-list">
				<?php foreach ( $items as $k_item ) :
					$k_slug = (string) ( $k_item['slug'] ?? '' );
					if ( '' === $k_slug ) {
						continue;
					}
					$k_title = (string) ( $k_item['title'] ?? '' );
					$k_sum   = wp_strip_all_tags( (string) ( $k_item['summary'] ?? ( $k_item['content'] ?? '' ) ) );
					$k_topic = (string) ( $k_item['topic']['name'] ?? '' );
					$k_words = str_word_count( wp_strip_all_tags( (string) ( $k_item['content'] ?? '' ) ) );
					$k_time  = $k_words > 0 ? max( 1, (int) ceil( $k_words / 200 ) ) . ' phút đọc' : '';
				?>
					<li>
						<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_knowledge_item_url( $k_slug ) ); ?>"><?php echo esc_html( $k_title ); ?></a></h3>
						<div class="dz-item-meta">
							<?php if ( '' !== $k_topic ) : ?><span><?php echo esc_html( $k_topic ); ?></span><?php endif; ?>
							<?php if ( '' !== $k_time ) : ?><span><?php echo esc_html( $k_time ); ?></span><?php endif; ?>
						</div>
						<?php if ( '' !== trim( $k_sum ) ) : ?><p class="dz-item-sum"><?php echo esc_html( wp_trim_words( $k_sum, 30 ) ); ?></p><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php cvc_render_pagination( $currentPg, $lastPg, cvc_listing_page_url_builder( 'cvc_knowledge_url', $q ) ); ?>
		</div>
		<aside class="dz-colside">
			<?php cvc_render_study_sidebar( array( 'topics' ) ); ?>
		</aside>
	</div>
</div>
</main>

<?php get_footer(); ?>
