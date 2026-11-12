<?php
/**
 * Mới cập nhật - /moi-cap-nhat/ (12/11/2026, claude/ke-hoach-vinh-danh-va-lien-ket-khap-noi.md).
 * Trang trung chuyển: tin tuyển dụng, bài viết, đề thi mới nhất - có link từ chân trang mọi trang để Google thấy URL mới sớm.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lu    = function_exists( 'cvc_sf_api' ) ? cvc_sf_api( '/api/latest-updates', array(), 10 * MINUTE_IN_SECONDS ) : array();
$posts = function_exists( 'cvc_blog_posts_index' ) ? array_slice( cvc_blog_posts_index(), 0, 30 ) : array();
$recs  = (array) ( $lu['recruitments'] ?? array() );
$exams = (array) ( $lu['exams'] ?? array() );

cvc_seo_set_title( 'Mới cập nhật: tin tuyển dụng, bài viết, đề thi công chức viên chức | Công Viên Chức' );
cvc_seo_set_description( 'Tin tuyển dụng công chức, viên chức, bài viết hướng dẫn ôn thi và đề thi trắc nghiệm mới nhất trên Công Viên Chức, cập nhật liên tục.' );
cvc_seo_set_canonical( home_url( '/moi-cap-nhat/' ) );
cvc_seo_add_breadcrumb_jsonld( array( array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ), array( 'label' => 'Mới cập nhật' ) ) );

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-5 rm lu">
	<?php if ( function_exists( 'cvc_reports_css' ) ) { cvc_reports_css(); } ?>
	<style>.lu-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;align-items:start}.lu-col h2{font-size:19px;margin:0 0 10px;color:#123a63}.lu-list{list-style:none;margin:0;padding:0;display:grid;gap:9px}.lu-list li{line-height:1.45;font-size:15px;min-width:0}.lu-list a{font-weight:600;color:#14263b}.lu-list a:hover{color:#1767aa}.lu-list small{display:block;color:#5c6672;font-size:12.5px}.lu-tag{display:inline-block;margin-right:6px;padding:0 6px;border-radius:6px;font-size:11.5px;font-weight:800;background:#e7f4ea;color:#1d6b35}.lu-tag.off{background:#f1f2f4;color:#5c6672}</style>
	<header class="rm-head">
		<p class="rm-kicker">Cập nhật lúc <?php echo esc_html( (string) ( $lu['as_of'] ?? wp_date( 'H:i d/m/Y' ) ) ); ?></p>
		<h1>Mới cập nhật</h1>
		<p>Tin tuyển dụng, bài viết hướng dẫn và đề thi mới nhất trên Công Viên Chức. Xem thêm <a href="<?php echo esc_url( home_url( '/bao-cao-tuyen-dung/' ) ); ?>">báo cáo tuyển dụng hằng tháng</a> và <a href="<?php echo esc_url( home_url( '/vinh-danh/' ) ); ?>">bảng vinh danh tuần</a>.</p>
	</header>
	<div class="lu-grid">
		<section class="lu-col rm-card" aria-labelledby="lu-rec">
			<h2 id="lu-rec">Tin tuyển dụng mới</h2>
			<ul class="lu-list">
				<?php foreach ( $recs as $r ) : ?>
					<?php $meta = array_filter( array( (string) ( $r['province'] ?? '' ), ! empty( $r['quota'] ) ? (int) $r['quota'] . ' chỉ tiêu' : '', ! empty( $r['deadline'] ) ? 'hạn ' . cvc_sf_date( (string) $r['deadline'] ) : '', 'đăng ' . cvc_sf_date( (string) ( $r['published_at'] ?? '' ) ) ) ); ?>
					<li><span class="lu-tag<?php echo empty( $r['open'] ) ? ' off' : ''; ?>"><?php echo empty( $r['open'] ) ? 'Hết hạn' : 'Đang mở'; ?></span><a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>" data-cvc-go="lu-rec"><?php echo esc_html( (string) $r['title'] ); ?></a><small><?php echo esc_html( implode( ' · ', $meta ) ); ?></small></li>
				<?php endforeach; ?>
			</ul>
			<p><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tất cả tin tuyển dụng →</a></p>
		</section>
		<section class="lu-col rm-card" aria-labelledby="lu-blog">
			<h2 id="lu-blog">Bài viết mới</h2>
			<ul class="lu-list">
				<?php foreach ( $posts as $p ) : ?>
					<li><a href="<?php echo esc_url( $p['url'] ); ?>" data-cvc-go="lu-blog"><?php echo esc_html( $p['title'] ); ?></a><small><?php echo esc_html( cvc_sf_date( (string) $p['date'] ) ); ?></small></li>
				<?php endforeach; ?>
			</ul>
			<p><a href="<?php echo esc_url( home_url( '/bai-viet/' ) ); ?>">Tất cả bài viết →</a></p>
		</section>
		<section class="lu-col rm-card" aria-labelledby="lu-exam">
			<h2 id="lu-exam">Đề thi mới</h2>
			<ul class="lu-list">
				<?php foreach ( $exams as $x ) : ?>
					<li><a href="<?php echo esc_url( cvc_exam_url( (string) $x['slug'] ) ); ?>" data-cvc-go="lu-exam"><?php echo esc_html( (string) $x['title'] ); ?></a><small><?php echo esc_html( (int) $x['questions'] . ' câu · ' . cvc_sf_date( (string) ( $x['published_at'] ?? '' ) ) ); ?></small></li>
				<?php endforeach; ?>
			</ul>
			<p><a href="<?php echo esc_url( cvc_exams_url() ); ?>">Tất cả đề thi →</a></p>
		</section>
	</div>
	<?php if ( function_exists( 'cvc_side_feed' ) ) { cvc_side_feed( array( 'slot' => 'lu', 'blocks' => array( 'fame' ) ), 'band' ); } ?>
</div>
</main>
<?php
get_footer();
