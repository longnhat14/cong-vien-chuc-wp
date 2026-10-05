<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT BÀI VIẾT KIẾN THỨC CÔNG VỤ (Executive 3-Column Architecture)
 * URL: /kien-thuc/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_knowledge_slug' ) );

$service = new CVC_Knowledge_Service();
$result  = $service->find( $slug );

$item     = null;
$is_found = false;

if ( $result['ok'] ?? false ) {
	$data     = $result['data']['data'] ?? ( $result['data'] ?? null );
	$item     = is_array( $data ) ? $data : null;
	$is_found = null !== $item && ! empty( $item );
}

// Slug không tồn tại -> 404 thật (trước đây hiện nội dung mẫu/fixture: soft-404, sai nội dung).
if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
}

cvc_seo_set_title( $is_found ? (string) $item['title'] : 'Chi tiết bài viết kiến thức' );

$topic = $is_found && is_array( $item['topic'] ?? null ) ? $item['topic'] : null;

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Kiến thức', 'url' => cvc_knowledge_url() ),
	array( 'label' => $is_found ? (string) $item['title'] : 'Chi tiết bài viết' ),
);

if ( $is_found ) {
	if ( ! empty( $item['summary'] ) ) {
		cvc_seo_set_description( (string) $item['summary'] );
	}
	cvc_seo_set_canonical( cvc_knowledge_item_url( $slug ) );
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
				<h1 class="text-2xl font-black text-white">Không tìm thấy nội dung kiến thức</h1>
				<p class="text-xs text-slate-400">Bài viết bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">
					&larr; Xem tất cả kiến thức
				</a>
			</div>
		<?php else : ?>
			<?php
			cvc_render_track_marker( 'knowledge_viewed', 'knowledge_item', (int) ( $item['id'] ?? 0 ) );
			$legalDoc   = is_array( $item['legal_document'] ?? null ) ? $item['legal_document'] : null;
			$article    = is_array( $item['article'] ?? null ) ? $item['article'] : null;
			$key_points = array_values( array_filter( array_map( 'strval', is_array( $item['key_points'] ?? null ) ? $item['key_points'] : array() ) ) );
			$practice   = is_array( $item['practice_exam'] ?? null ) ? $item['practice_exam'] : null;
			$q_count    = (int) ( $item['questions_count'] ?? 0 );
			$prev_item  = is_array( $item['previous'] ?? null ) ? $item['previous'] : null;
			$next_item  = is_array( $item['next'] ?? null ) ? $item['next'] : null;
			$content    = trim( (string) ( $item['content'] ?? '' ) );
			$pitfalls   = array();
			if ( false !== ( $pos = mb_strpos( $content, 'Lưu ý dễ nhầm:' ) ) ) {
				$pitfalls = array_values( array_filter( array_map( static fn ( $l ) => trim( ltrim( trim( $l ), '-•' ) ), explode( "\n", mb_substr( $content, $pos + mb_strlen( 'Lưu ý dễ nhầm:' ) ) ) ) ) );
				$content  = trim( mb_substr( $content, 0, $pos ) );
			}
			$article_url = '';
			if ( $legalDoc && ! empty( $legalDoc['slug'] ) ) {
				$article_url = cvc_legal_document_url( (string) $legalDoc['slug'] ) . ( $article && '' !== (string) ( $article['number'] ?? '' ) ? '#dieu-' . sanitize_title( (string) $article['number'] ) : '' );
			}
			$is_ai = 'ai_generated' === ( $item['provenance'] ?? '' );
			?>

			<!-- HERO -->
			<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-cyan-500/40 shadow-2xl space-y-4">
				<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
					<div class="space-y-3 max-w-3xl">
						<div class="flex items-center gap-2 flex-wrap text-xs">
							<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">Bài học</span>
							<?php if ( $legalDoc && ! empty( $legalDoc['document_number'] ) ) : ?>
								<a href="<?php echo esc_url( cvc_legal_document_url( (string) $legalDoc['slug'] ) ); ?>" class="bg-amber-500/20 text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-500/40 hover:bg-amber-500/30"><?php echo esc_html( (string) $legalDoc['document_number'] ); ?></a>
							<?php elseif ( $topic && ! empty( $topic['name'] ) ) : ?>
								<span class="bg-amber-500/20 text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-500/40"><?php echo esc_html( (string) $topic['name'] ); ?></span>
							<?php endif; ?>
						</div>
						<h1 class="text-2xl sm:text-4xl font-black text-white leading-snug"><?php echo esc_html( (string) $item['title'] ); ?></h1>
						<?php if ( ! empty( $item['source_reference'] ) || $legalDoc ) : ?>
							<p class="text-xs text-slate-400">
								Căn cứ:
								<?php if ( '' !== $article_url ) : ?>
									<a class="text-cyan-300 font-bold hover:underline" href="<?php echo esc_url( $article_url ); ?>"><?php echo esc_html( (string) ( $item['source_reference'] ?? '' ) ?: (string) ( $legalDoc['title'] ?? '' ) ); ?></a>
								<?php else : ?>
									<?php echo esc_html( (string) ( $item['source_reference'] ?? '' ) ); ?>
								<?php endif; ?>
								<?php if ( $legalDoc && ! empty( $legalDoc['title'] ) ) : ?>
									— <?php echo esc_html( (string) $legalDoc['title'] ); ?>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</div>
					<div class="shrink-0 flex items-center gap-3">
						<?php cvc_render_bookmark_button( 'knowledge', (int) ( $item['id'] ?? 0 ) ); ?>
					</div>
				</div>
			</section>

			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

				<!-- LEFT: muc luc + van ban goc -->
				<aside class="lg:col-span-3 space-y-4 lg:sticky lg:top-24">
					<nav class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-2 shadow-lg text-xs" aria-label="Mục lục bài học">
						<h2 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2">Mục lục</h2>
						<?php if ( ! empty( $item['summary'] ) ) : ?><a href="#tom-tat" class="block p-1.5 rounded-lg text-slate-300 hover:text-cyan-300">Tóm tắt</a><?php endif; ?>
						<?php if ( ! empty( $key_points ) ) : ?><a href="#y-chinh" class="block p-1.5 rounded-lg text-slate-300 hover:text-cyan-300">Ý chính cần nhớ</a><?php endif; ?>
						<?php if ( ! empty( $pitfalls ) ) : ?><a href="#de-nham" class="block p-1.5 rounded-lg text-slate-300 hover:text-cyan-300">Lưu ý dễ nhầm</a><?php endif; ?>
						<?php if ( '' !== $content ) : ?><a href="#giai-thich" class="block p-1.5 rounded-lg text-slate-300 hover:text-cyan-300">Giải thích</a><?php endif; ?>
						<?php if ( $article && ! empty( $article['content'] ) ) : ?><a href="#nguyen-van" class="block p-1.5 rounded-lg text-slate-300 hover:text-cyan-300">Nguyên văn Điều luật</a><?php endif; ?>
					</nav>
					<?php if ( $legalDoc && ! empty( $legalDoc['slug'] ) ) : ?>
						<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-2 shadow-lg text-xs">
							<h2 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">Văn bản pháp luật gốc</h2>
							<a href="<?php echo esc_url( cvc_legal_document_url( (string) $legalDoc['slug'] ) ); ?>" class="block font-bold text-white hover:text-cyan-300"><?php echo esc_html( (string) ( $legalDoc['title'] ?? '' ) ); ?></a>
							<a href="<?php echo esc_url( cvc_legal_document_url( (string) $legalDoc['slug'] ) . '#toan-van' ); ?>" class="inline-block text-cyan-300 font-semibold">Đọc toàn văn &rarr;</a>
						</div>
					<?php endif; ?>
				</aside>

				<!-- CENTER -->
				<div class="lg:col-span-6 space-y-5">
					<?php if ( ! empty( $item['summary'] ) ) : ?>
						<section id="tom-tat" class="scroll-mt-24 bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 rounded-3xl border border-amber-500/40 space-y-2 shadow-xl">
							<h2 class="font-black text-xs text-amber-400 uppercase tracking-wider">Tóm tắt</h2>
							<p class="text-sm text-slate-200 leading-relaxed"><?php echo esc_html( (string) $item['summary'] ); ?></p>
						</section>
					<?php endif; ?>

					<?php if ( ! empty( $key_points ) ) : ?>
						<section id="y-chinh" class="scroll-mt-24 bg-slate-950 p-6 rounded-3xl border border-cyan-500/30 space-y-3 shadow-xl">
							<h2 class="font-black text-xs text-cyan-400 uppercase tracking-wider">Ý chính cần nhớ</h2>
							<ul class="space-y-2 text-sm text-slate-200">
								<?php foreach ( $key_points as $kp ) : ?>
									<li class="flex items-start gap-2.5 bg-slate-900 p-3 rounded-xl border border-slate-800"><span class="text-cyan-400 font-bold shrink-0">&#9656;</span><span><?php echo esc_html( $kp ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( ! empty( $pitfalls ) ) : ?>
						<section id="de-nham" class="scroll-mt-24 bg-slate-950 p-6 rounded-3xl border border-rose-500/30 space-y-3 shadow-xl">
							<h2 class="font-black text-xs text-rose-300 uppercase tracking-wider">Lưu ý dễ nhầm</h2>
							<ul class="space-y-2 text-sm text-slate-200 list-disc pl-5">
								<?php foreach ( $pitfalls as $pf ) : ?><li><?php echo esc_html( $pf ); ?></li><?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( '' !== $content ) : ?>
						<section id="giai-thich" class="scroll-mt-24 bg-[#0A192F] border border-slate-800 rounded-3xl p-6 space-y-3 shadow-xl">
							<h2 class="text-base font-black text-white">Giải thích</h2>
							<div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line"><?php echo esc_html( $content ); ?></div>
						</section>
					<?php endif; ?>

					<?php if ( $article && ! empty( $article['content'] ) ) : ?>
						<section id="nguyen-van" class="scroll-mt-24 bg-[#0A192F] border border-slate-800 rounded-3xl p-6 space-y-3 shadow-xl">
							<details>
								<summary class="cursor-pointer text-base font-black text-white">Nguyên văn Điều <?php echo esc_html( (string) ( $article['number'] ?? '' ) ); ?><?php echo ! empty( $article['title'] ) ? '. ' . esc_html( (string) $article['title'] ) : ''; ?></summary>
								<div class="mt-3 text-[13px] text-slate-300 leading-relaxed whitespace-pre-line"><?php echo esc_html( trim( (string) $article['content'] ) ); ?></div>
							</details>
						</section>
					<?php endif; ?>

					<?php if ( $is_ai ) : ?>
						<p class="text-[11px] text-slate-500">Bài học được biên soạn tự động từ toàn văn chính thức và kiểm tra đối chiếu với nguyên văn Điều luật. Khi có khác biệt, nguyên văn văn bản là căn cứ.</p>
					<?php endif; ?>

					<?php if ( $prev_item || $next_item ) : ?>
						<nav class="grid grid-cols-2 gap-3 text-xs" aria-label="Bài trước / bài sau">
							<div><?php if ( $prev_item && ! empty( $prev_item['slug'] ) ) : ?><a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $prev_item['slug'] ) ); ?>" class="block p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-cyan-500/40"><span class="text-slate-500">&larr; Bài trước</span><span class="block font-bold text-white line-clamp-2"><?php echo esc_html( (string) $prev_item['title'] ); ?></span></a><?php endif; ?></div>
							<div><?php if ( $next_item && ! empty( $next_item['slug'] ) ) : ?><a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $next_item['slug'] ) ); ?>" class="block p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-cyan-500/40 text-right"><span class="text-slate-500">Bài sau &rarr;</span><span class="block font-bold text-white line-clamp-2"><?php echo esc_html( (string) $next_item['title'] ); ?></span></a><?php endif; ?></div>
						</nav>
					<?php endif; ?>
				</div>

				<!-- RIGHT -->
				<aside class="lg:col-span-3 space-y-4">
					<div class="bg-[#0A192F] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
						<h2 class="font-extrabold text-xs text-cyan-400 uppercase border-b border-slate-800 pb-2">Luyện tập</h2>
						<?php if ( $practice && ! empty( $practice['slug'] ) ) : ?>
							<p class="text-slate-400 text-[11px]"><?php echo $q_count > 0 ? esc_html( $q_count . ' câu hỏi gắn với bài học này. ' ) : ''; ?>Đề luyện tập <?php echo esc_html( (string) (int) $practice['total_questions'] ); ?> câu theo cả văn bản, chấm điểm ngay.</p>
							<a href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>" class="block w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black rounded-xl shadow">Làm đề luyện tập</a>
						<?php else : ?>
							<p class="text-slate-400 text-[11px]">Làm đề thi thử có chấm điểm và giải thích từng câu.</p>
							<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black rounded-xl shadow">Vào danh sách đề thi</a>
						<?php endif; ?>
					</div>
				</aside>
			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
