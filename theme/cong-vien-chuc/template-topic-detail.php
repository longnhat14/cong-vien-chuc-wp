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

cvc_seo_set_title( $is_found ? (string) $topic['name'] : 'Chi tiết chủ đề ôn thi' );

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Chủ đề ôn thi', 'url' => cvc_topics_url() ),
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
								Chủ đề ôn thi
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

				<!-- CENTER: bai hoc that cua chu de -->
				<?php
				$k_items  = is_array( $topic['knowledge_items'] ?? null ) ? $topic['knowledge_items'] : array();
				$t_doc    = is_array( $topic['legal_document'] ?? null ) ? $topic['legal_document'] : null;
				$t_exam   = is_array( $topic['practice_exam'] ?? null ) ? $topic['practice_exam'] : null;
				$t_qcount = (int) ( $topic['questions_count'] ?? 0 );
				?>
				<div class="lg:col-span-6 space-y-4">
					<section class="bg-[#0A192F] border border-slate-800 rounded-3xl p-6 space-y-4 shadow-xl">
						<div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-2">
							<h2 class="text-base font-black text-white">Bài học trong chủ đề</h2>
							<span class="text-xs text-slate-400"><?php echo esc_html( count( $k_items ) . ' bài' . ( $t_qcount > 0 ? ' · ' . number_format_i18n( $t_qcount ) . ' câu hỏi' : '' ) ); ?></span>
						</div>
						<?php if ( empty( $k_items ) ) : ?>
							<p class="text-sm text-slate-400">Chủ đề này chưa có bài học. Xem các <a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_topics_url() ); ?>">chủ đề khác</a> hoặc <a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">văn bản pháp luật</a>.</p>
						<?php else : ?>
							<ol class="space-y-2">
								<?php foreach ( $k_items as $ki ) : ?>
									<?php if ( empty( $ki['slug'] ) ) { continue; } ?>
									<li>
										<a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $ki['slug'] ) ); ?>" class="block p-3 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800">
											<span class="block text-sm font-bold text-white"><?php echo esc_html( (string) ( $ki['title'] ?? '' ) ); ?></span>
											<?php if ( ! empty( $ki['summary'] ) ) : ?><span class="block text-xs text-slate-400 line-clamp-2 mt-0.5"><?php echo esc_html( (string) $ki['summary'] ); ?></span><?php endif; ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>
					</section>
				</div>

				<!-- RIGHT: luyen tap + van ban goc -->
				<aside class="lg:col-span-3 space-y-4">
					<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
						<h3 class="font-extrabold text-xs text-cyan-400 uppercase border-b border-slate-800 pb-2">Luyện tập</h3>
						<?php if ( $t_exam && ! empty( $t_exam['slug'] ) ) : ?>
							<p class="text-slate-400 text-[11px]">Đề luyện tập <?php echo esc_html( (string) (int) $t_exam['total_questions'] ); ?> câu theo văn bản của chủ đề, chấm điểm và giải thích ngay.</p>
							<a href="<?php echo esc_url( cvc_exam_url( (string) $t_exam['slug'] ) ); ?>" class="block w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black rounded-xl shadow">Làm đề luyện tập</a>
						<?php else : ?>
							<p class="text-slate-400 text-[11px]">Làm đề thi thử có chấm điểm và giải thích từng câu.</p>
							<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black rounded-xl shadow">Vào danh sách đề thi</a>
						<?php endif; ?>
					</div>
					<?php if ( $t_doc && ! empty( $t_doc['slug'] ) ) : ?>
						<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-2 shadow-xl text-xs">
							<h3 class="font-extrabold text-xs text-amber-400 uppercase border-b border-slate-800 pb-2">Văn bản pháp luật gốc</h3>
							<a href="<?php echo esc_url( cvc_legal_document_url( (string) $t_doc['slug'] ) ); ?>" class="block font-bold text-white hover:text-cyan-300"><?php echo esc_html( (string) ( $t_doc['title'] ?? '' ) ); ?></a>
							<a href="<?php echo esc_url( cvc_legal_document_url( (string) $t_doc['slug'] ) . '#toan-van' ); ?>" class="inline-block text-cyan-300 font-semibold">Đọc toàn văn &rarr;</a>
						</div>
					<?php endif; ?>
				</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
