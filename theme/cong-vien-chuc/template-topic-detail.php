<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT LỘ TRÌNH CHỦ ĐỀ THĂNG TIẾN (Executive 3-Column Architecture)
 * URL: /chu-de/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_topic_slug' ) );

$service = new CVC_Topic_Service();
$result  = $service->find( $slug );

$topic    = null;
$is_found = false;

if ( $result['ok'] ?? false ) {
	$data     = $result['data']['data'] ?? ( $result['data'] ?? null );
	$topic    = is_array( $data ) ? $data : null;
	$is_found = null !== $topic && ! empty( $topic );
}

// Slug không tồn tại -> 404 thật (trước đây hiện nội dung mẫu/fixture: soft-404, sai nội dung).
if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
}

cvc_seo_set_title( $is_found ? (string) $topic['name'] : 'Chi tiết chủ đề thăng tiến' );

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Lộ trình thăng tiến', 'url' => cvc_topics_url() ),
	array( 'label' => $is_found ? (string) $topic['name'] : 'Chi tiết chủ đề' ),
);

if ( $is_found ) {
	if ( ! empty( $topic['description'] ) ) {
		cvc_seo_set_description( (string) $topic['description'] );
	}
	cvc_seo_set_canonical( cvc_topic_url( $slug ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="bg-[#0A192F] border border-slate-800 p-8 rounded-3xl text-center space-y-4">
				<h1 class="text-2xl font-black text-white">Không tìm thấy chủ đề</h1>
				<p class="text-xs text-slate-400">Chủ đề bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">
					&larr; Xem tất cả chủ đề
				</a>
			</div>
		<?php else : ?>
			<?php cvc_render_track_marker( 'topic_viewed', 'topic', (int) ( $topic['id'] ?? 0 ) ); ?>
			<?php $children = is_array( $topic['children'] ?? null ) ? $topic['children'] : array(); ?>

			<!-- HERO TOPIC HEADER BANNER -->
			<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-cyan-500/40 shadow-2xl space-y-4">
				<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
					<div class="space-y-3 max-w-3xl">
						<div class="flex items-center gap-2 flex-wrap text-xs">
							<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">
								🗺️ CAREER ROADMAP DETAIL
							</span>
							<?php if ( ! empty( $topic['exam_subject']['name'] ) ) : ?>
								<span class="bg-amber-500/20 text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-500/40">
									<?php echo esc_html( $topic['exam_subject']['name'] ); ?>
								</span>
							<?php endif; ?>
						</div>

						<h1 class="text-2xl sm:text-4xl font-black text-white leading-snug">
							<?php echo esc_html( $topic['name'] ); ?>
						</h1>

						<?php if ( ! empty( $topic['description'] ) ) : ?>
							<p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
								<?php echo esc_html( $topic['description'] ); ?>
							</p>
						<?php endif; ?>
					</div>

					<div class="shrink-0 flex items-center gap-3">
						<?php cvc_render_bookmark_button( 'topic', (int) ( $topic['id'] ?? 0 ) ); ?>
					</div>
				</div>
			</section>

			<!-- 3-COLUMN SHELL GRID -->
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

				<!-- LEFT COLUMN (3 COLS — SUB-TOPICS LIST) -->
				<aside class="lg:col-span-3 space-y-4">
					
					<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs">
						<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
							<i class="fa-solid fa-list-check"></i> Chủ Đề Con Trọng Tâm
						</h3>

						<?php if ( empty( $children ) ) : ?>
							<p class="text-slate-400">Không có chủ đề phụ.</p>
						<?php else : ?>
							<div class="space-y-2">
								<?php foreach ( $children as $child ) : ?>
									<?php $c_slug = $child['slug'] ?? ''; ?>
									<?php if ( ! $c_slug ) continue; ?>
									<a href="<?php echo esc_url( cvc_topic_url( $c_slug ) ); ?>" class="block p-2.5 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 transition-colors font-bold text-slate-200">
										📌 <?php echo esc_html( $child['name'] ?? '' ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

				</aside>

				<!-- CENTER MAIN COLUMN (6 COLS — MINDMAP & GUIDANCE) -->
				<div class="lg:col-span-6 space-y-4">

					<!-- MINDMAP BOX -->
					<div class="bg-[#0A192F] border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl">
						<h2 class="text-base font-black text-white border-b border-slate-800 pb-2 flex items-center gap-2">
							<i class="fa-solid fa-sitemap text-amber-400"></i> Sơ Đồ Năng Lực Cốt Lõi
						</h2>

						<div class="aspect-video bg-slate-950 rounded-2xl border border-slate-800 p-6 flex flex-col items-center justify-center text-center space-y-3 relative overflow-hidden">
							<div class="w-16 h-16 rounded-full bg-cyan-500/20 text-cyan-300 flex items-center justify-center font-black text-2xl border border-cyan-500/40">
								💡
							</div>
							<h3 class="font-black text-white text-sm">Sơ Đồ Tư Duy Khoanh Vùng Trọng Tâm</h3>
							<p class="text-xs text-slate-400 max-w-sm">
								Hệ thống hóa toàn bộ kiến thức theo chuẩn sơ đồ cây năng lực của Bộ Nội Vụ.
							</p>
						</div>

						<div class="text-xs text-slate-300 leading-relaxed space-y-2 pt-2">
							<h4 class="font-black text-white text-sm">Hướng Dẫn Ôn Thi Chuyên Đề:</h4>
							<p>1. Nắm chắc định nghĩa & phạm vi điều chỉnh của từng văn bản luật liên quan.</p>
							<p>2. Luyện tập bộ câu hỏi trắc nghiệm khoanh vùng 60 câu để đạt trên 85% điểm số.</p>
						</div>
					</div>

				</div>

				<!-- RIGHT SIDEBAR (3 COLS — MONETIZATION) -->
				<aside class="lg:col-span-3 space-y-4">

					<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
						<h3 class="font-extrabold text-xs text-amber-400 uppercase border-b border-slate-800 pb-2">
							Khóa Học Lộ Trình Thăng Tiến
						</h3>
						<p class="text-slate-400 text-[11px]">
							Tham gia chương trình đào tạo chuyên sâu chuẩn ngạch Chuyên viên / Chuyên viên chính.
						</p>
						<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black rounded-xl shadow">
							Đăng Ký Ngay
						</a>
					</div>

				</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
