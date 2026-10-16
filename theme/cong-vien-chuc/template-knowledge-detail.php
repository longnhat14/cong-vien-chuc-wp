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

<main id="main" class="dz-main">

	<div class="dz-wrap space-y-6">

		<!-- Breadcrumbs -->
		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="dz-panel text-center space-y-4">
				<h1 class="text-2xl font-black text-ink">Không tìm thấy nội dung kiến thức</h1>
				<p class="text-xs text-ink-muted">Bài viết bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="inline-block px-5 py-2.5 bg-acc text-white font-black text-xs rounded-xl">
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
			<section class="dz-herohead">
				<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
					<div class="space-y-3 max-w-3xl">
						<div class="flex items-center gap-2 flex-wrap text-xs">
							<span class="bg-acc text-white text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">Bài học</span>
							<?php if ( $legalDoc && ! empty( $legalDoc['document_number'] ) ) : ?>
								<a href="<?php echo esc_url( cvc_legal_document_url( (string) $legalDoc['slug'] ) ); ?>" class="bg-acc-soft text-acc text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-acc-line hover:bg-acc-soft"><?php echo esc_html( (string) $legalDoc['document_number'] ); ?></a>
							<?php elseif ( $topic && ! empty( $topic['name'] ) ) : ?>
								<span class="bg-acc-soft text-acc text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-acc-line"><?php echo esc_html( (string) $topic['name'] ); ?></span>
							<?php endif; ?>
						</div>
						<h1 class="text-2xl sm:text-4xl font-black text-ink leading-snug"><?php echo esc_html( (string) $item['title'] ); ?></h1>
						<?php if ( ! empty( $item['source_reference'] ) || $legalDoc ) : ?>
							<p class="text-xs text-ink-muted">
								Căn cứ:
								<?php if ( '' !== $article_url ) : ?>
									<a class="text-acc font-bold hover:underline" href="<?php echo esc_url( $article_url ); ?>"><?php echo esc_html( (string) ( $item['source_reference'] ?? '' ) ?: (string) ( $legalDoc['title'] ?? '' ) ); ?></a>
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
					<nav class="dz-card space-y-2 text-xs" aria-label="Mục lục bài học">
						<h2 class="dz-sideh2">Mục lục</h2>
						<?php if ( ! empty( $item['summary'] ) ) : ?><a href="#tom-tat" class="block p-1.5 rounded-lg text-ink-2 hover:text-acc-2">Tóm tắt</a><?php endif; ?>
						<?php if ( ! empty( $key_points ) ) : ?><a href="#y-chinh" class="block p-1.5 rounded-lg text-ink-2 hover:text-acc-2">Ý chính cần nhớ</a><?php endif; ?>
						<?php if ( ! empty( $pitfalls ) ) : ?><a href="#de-nham" class="block p-1.5 rounded-lg text-ink-2 hover:text-acc-2">Lưu ý dễ nhầm</a><?php endif; ?>
						<?php if ( '' !== $content ) : ?><a href="#giai-thich" class="block p-1.5 rounded-lg text-ink-2 hover:text-acc-2">Giải thích</a><?php endif; ?>
						<?php if ( $article && ! empty( $article['content'] ) ) : ?><a href="#nguyen-van" class="block p-1.5 rounded-lg text-ink-2 hover:text-acc-2">Nguyên văn Điều luật</a><?php endif; ?>
					</nav>
					<?php if ( $legalDoc && ! empty( $legalDoc['slug'] ) ) : ?>
						<div class="dz-card space-y-2 text-xs">
							<h2 class="dz-sideh2">Văn bản pháp luật gốc</h2>
							<a href="<?php echo esc_url( cvc_legal_document_url( (string) $legalDoc['slug'] ) ); ?>" class="block font-bold text-ink hover:text-acc-2"><?php echo esc_html( (string) ( $legalDoc['title'] ?? '' ) ); ?></a>
							<a href="<?php echo esc_url( cvc_legal_document_url( (string) $legalDoc['slug'] ) . '#toan-van' ); ?>" class="inline-block text-acc font-semibold">Đọc toàn văn &rarr;</a>
						</div>
					<?php endif; ?>
				</aside>

				<!-- CENTER -->
				<div class="lg:col-span-6 space-y-5">
					<?php if ( ! empty( $item['summary'] ) ) : ?>
						<section id="tom-tat" class="scroll-mt-24 p-6 rounded-3xl border border-acc-line space-y-2">
							<h2 class="font-black text-xs text-acc uppercase tracking-wider">Tóm tắt</h2>
							<p class="text-sm text-ink leading-relaxed"><?php echo esc_html( (string) $item['summary'] ); ?></p>
						</section>
					<?php endif; ?>

					<?php if ( ! empty( $key_points ) ) : ?>
						<section id="y-chinh" class="dz-panel scroll-mt-24 space-y-3">
							<h2 class="font-black text-xs text-acc uppercase tracking-wider">Ý chính cần nhớ</h2>
							<ul class="space-y-2 text-sm text-ink">
								<?php foreach ( $key_points as $kp ) : ?>
									<li class="flex items-start gap-2.5 bg-white p-3 rounded-xl border border-slate-300"><span class="text-acc font-bold shrink-0">&#9656;</span><span><?php echo esc_html( $kp ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( ! empty( $pitfalls ) ) : ?>
						<section id="de-nham" class="dz-panel scroll-mt-24 space-y-3">
							<h2 class="font-black text-xs text-red-700 uppercase tracking-wider">Lưu ý dễ nhầm</h2>
							<ul class="space-y-2 text-sm text-ink list-disc pl-5">
								<?php foreach ( $pitfalls as $pf ) : ?><li><?php echo esc_html( $pf ); ?></li><?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( '' !== $content ) : ?>
						<section id="giai-thich" class="dz-panel scroll-mt-24 space-y-3">
							<h2 class="text-base font-black text-ink">Giải thích</h2>
							<div class="text-sm text-ink-2 leading-relaxed whitespace-pre-line"><?php echo esc_html( $content ); ?></div>
						</section>
					<?php endif; ?>

					<?php if ( $article && ! empty( $article['content'] ) ) : ?>
						<section id="nguyen-van" class="dz-panel scroll-mt-24 space-y-3">
							<details>
								<summary class="cursor-pointer text-base font-black text-ink">Nguyên văn Điều <?php echo esc_html( (string) ( $article['number'] ?? '' ) ); ?><?php echo ! empty( $article['title'] ) ? '. ' . esc_html( (string) $article['title'] ) : ''; ?></summary>
								<div class="mt-3 text-[13px] text-ink-2 leading-relaxed whitespace-pre-line"><?php echo esc_html( trim( (string) $article['content'] ) ); ?></div>
							</details>
						</section>
					<?php endif; ?>

					<?php if ( $is_ai ) : ?>
						<p class="text-[11px] text-ink-muted">Bài học được biên soạn tự động từ toàn văn chính thức và kiểm tra đối chiếu với nguyên văn Điều luật. Khi có khác biệt, nguyên văn văn bản là căn cứ.</p>
					<?php endif; ?>

					<?php if ( $prev_item || $next_item ) : ?>
						<nav class="grid grid-cols-2 gap-3 text-xs" aria-label="Bài trước / bài sau">
							<div><?php if ( $prev_item && ! empty( $prev_item['slug'] ) ) : ?><a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $prev_item['slug'] ) ); ?>" class="block p-3 rounded-xl bg-white border border-slate-300 hover:border-acc-2"><span class="text-ink-muted">&larr; Bài trước</span><span class="block font-bold text-ink line-clamp-2"><?php echo esc_html( (string) $prev_item['title'] ); ?></span></a><?php endif; ?></div>
							<div><?php if ( $next_item && ! empty( $next_item['slug'] ) ) : ?><a href="<?php echo esc_url( cvc_knowledge_item_url( (string) $next_item['slug'] ) ); ?>" class="block p-3 rounded-xl bg-white border border-slate-300 hover:border-acc-2 text-right"><span class="text-ink-muted">Bài sau &rarr;</span><span class="block font-bold text-ink line-clamp-2"><?php echo esc_html( (string) $next_item['title'] ); ?></span></a><?php endif; ?></div>
						</nav>
					<?php endif; ?>
				</div>

				<!-- RIGHT -->
				<aside class="lg:col-span-3 space-y-4">
					<div class="dz-card space-y-3 text-xs text-center">
						<h2 class="dz-sideh2">Luyện tập</h2>
						<?php if ( $practice && ! empty( $practice['slug'] ) ) : ?>
							<p class="text-ink-muted text-[11px]"><?php echo $q_count > 0 ? esc_html( $q_count . ' câu hỏi gắn với bài học này. ' ) : ''; ?>Đề luyện tập <?php echo esc_html( (string) (int) $practice['total_questions'] ); ?> câu theo cả văn bản, chấm điểm ngay.</p>
							<a href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>" class="block w-full py-2.5 bg-acc hover:bg-acc-deep text-white font-black rounded-xl">Làm đề luyện tập</a>
						<?php else : ?>
							<p class="text-ink-muted text-[11px]">Làm đề thi thử có chấm điểm và giải thích từng câu.</p>
							<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block w-full py-2.5 bg-acc hover:bg-acc-deep text-white font-black rounded-xl">Vào danh sách đề thi</a>
						<?php endif; ?>
					</div>
				</aside>
			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
