<?php
/**
 * Thành phần dùng chung cho các trang danh sách (Phase 12).
 *
 * Thay cho các khối cũ toàn dữ liệu giả: bộ lọc bên trái chỉ trỏ về cùng 1
 * trang (hoặc alert()), "trợ lý AI" luôn trả cùng 1 câu, widget bán đề/khóa
 * học với giá cứng 49K/599K/699K... Nay: ô tìm kiếm lọc thật qua tham số
 * `search` của API, điều hướng giữa các mục, và khóa học thật với giá thật.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Từ khóa tìm trong trang danh sách (?q=...).
 */
function cvc_listing_query(): string {
	$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['q'] ) ) : '';

	return mb_substr( trim( $q ), 0, 100 );
}

/**
 * Bọc URL builder phân trang để giữ ?q= khi chuyển trang.
 */
function cvc_listing_page_url_builder( callable $base, string $q ): callable {
	return static function ( int $page ) use ( $base, $q ): string {
		$url = (string) $base( $page );

		return '' !== $q ? add_query_arg( 'q', rawurlencode( $q ), $url ) : $url;
	};
}

function cvc_render_listing_search( string $action, string $q, string $placeholder, string $heading ): void {
	$field_id = 'cvc-listing-q-' . substr( md5( $action ), 0, 6 );
	?>
	<form method="get" action="<?php echo esc_url( $action ); ?>" class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-3 shadow-xl text-xs" role="search">
		<label for="<?php echo esc_attr( $field_id ); ?>" class="block font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-3"><?php echo esc_html( $heading ); ?></label>
		<input type="search" id="<?php echo esc_attr( $field_id ); ?>" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs placeholder-slate-500 focus:outline-none focus:border-cyan-400">
		<button type="submit" class="w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black rounded-xl text-xs">Tìm</button>
		<?php if ( '' !== $q ) : ?>
			<a href="<?php echo esc_url( $action ); ?>" class="block text-center text-cyan-300 font-bold">Xóa bộ lọc</a>
		<?php endif; ?>
	</form>
	<?php
}

function cvc_render_listing_explore_nav( string $current ): void {
	$items = array(
		'courses'         => array( 'Khóa học', cvc_courses_url() ),
		'exams'           => array( 'Đề thi thử', cvc_exams_url() ),
		'topics'          => array( 'Chủ đề ôn thi', cvc_topics_url() ),
		'knowledge'       => array( 'Kiến thức', cvc_knowledge_url() ),
		'legal-documents' => array( 'Văn bản pháp luật', cvc_legal_documents_url() ),
		'recruitments'    => array( 'Tuyển dụng', cvc_recruitments_url() ),
		'documents'       => array( 'Tài liệu', cvc_documents_url() ),
	);
	?>
	<nav class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-2 shadow-xl text-xs" aria-label="Các mục khác">
		<h2 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-3">Khám phá</h2>
		<?php foreach ( $items as $key => $item ) : ?>
			<a href="<?php echo esc_url( $item[1] ); ?>" class="flex items-center justify-between p-2 rounded-xl <?php echo $key === $current ? 'bg-cyan-500/10 text-cyan-300 font-bold border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-800'; ?>" <?php echo $key === $current ? 'aria-current="page"' : ''; ?>>
				<span><?php echo esc_html( $item[0] ); ?></span><span aria-hidden="true">&rsaquo;</span>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Tối đa 3 khóa học thật (ưu tiên khóa nổi bật) - cache 10 phút để không gọi
 * API ở mỗi lượt xem trang. Chỉ lưu mảng PHP thuần.
 *
 * @return array<int, array<string, mixed>>
 */
function cvc_featured_courses(): array {
	$cached = get_transient( 'cvc_featured_courses_v2' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$result  = ( new CVC_Course_Service() )->list( array( 'per_page' => 12 ) );
	$courses = array();

	if ( $result['ok'] && is_array( $result['data']['data']['data'] ?? null ) ) {
		foreach ( $result['data']['data']['data'] as $course ) {
			if ( empty( $course['slug'] ) || empty( $course['title'] ) ) {
				continue;
			}
			$courses[] = array(
				'title'       => (string) $course['title'],
				'slug'        => (string) $course['slug'],
				'price'       => (float) ( $course['price'] ?? 0 ),
				'sale_price'  => isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : null,
				'is_featured' => ! empty( $course['is_featured'] ),
				'coming_soon' => ! empty( $course['is_coming_soon'] ),
			);
		}
		usort(
			$courses,
			static fn ( $a, $b ) => (int) $b['is_featured'] <=> (int) $a['is_featured']
		);
		$courses = array_slice( $courses, 0, 3 );
		set_transient( 'cvc_featured_courses_v2', $courses, 10 * MINUTE_IN_SECONDS );
	}

	return $courses;
}

function cvc_format_vnd( float $amount ): string {
	return number_format( $amount, 0, ',', '.' ) . 'đ';
}

function cvc_render_study_sidebar(): void {
	$courses = cvc_featured_courses();
	?>
	<?php if ( ! empty( $courses ) ) : ?>
		<section class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-3 shadow-xl text-xs" aria-labelledby="cvc-sidebar-courses">
			<h2 id="cvc-sidebar-courses" class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-3">Khóa học ôn thi</h2>
			<ul class="space-y-2.5">
				<?php foreach ( $courses as $course ) : ?>
					<?php
					$price = $course['price'];
					$sale  = $course['sale_price'];
					$final = null !== $sale ? $sale : $price;
					?>
					<li>
						<a href="<?php echo esc_url( cvc_course_url( $course['slug'] ) ); ?>" class="block p-3 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 space-y-1.5">
							<span class="block font-bold text-white leading-snug"><?php echo esc_html( $course['title'] ); ?></span>
							<span class="flex items-baseline gap-2">
								<?php if ( ! empty( $course['coming_soon'] ) ) : ?>
									<strong class="text-cyan-300">Sắp mở</strong>
								<?php elseif ( $final <= 0 ) : ?>
									<strong class="text-emerald-400">Miễn phí</strong>
								<?php else : ?>
									<strong class="text-amber-400"><?php echo esc_html( cvc_format_vnd( $final ) ); ?></strong>
									<?php if ( null !== $sale && $sale < $price ) : ?>
										<span class="text-slate-500 line-through"><?php echo esc_html( cvc_format_vnd( $price ) ); ?></span>
									<?php endif; ?>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block text-center text-cyan-300 font-bold">Xem tất cả khóa học</a>
		</section>
	<?php endif; ?>

	<section class="bg-gradient-to-br from-amber-500/10 to-slate-900 border border-amber-500/30 p-5 rounded-3xl space-y-3 shadow-xl text-xs" aria-labelledby="cvc-sidebar-practice">
		<h2 id="cvc-sidebar-practice" class="font-extrabold text-xs text-amber-400 uppercase tracking-wider">Luyện đề miễn phí</h2>
		<p class="text-slate-300">Làm đề thi thử có chấm điểm ngay và xem giải thích từng câu sau khi nộp.</p>
		<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black rounded-xl text-center">Vào danh sách đề thi</a>
	</section>
	<?php
}

/**
 * Banner đầu trang danh sách - chỉ số liệu thật (tổng số bản ghi từ API).
 * Thay cho các con số tự đặt trước đây ("12.450+ học viên đỗ", "94.8% tỷ
 * lệ đạt", "36.000+ câu hỏi", "500+ chuyên đề", "AI Crawler 3.000+ website").
 *
 * @param array<int, array{0: string, 1: string}> $stats Danh sách [giá trị, nhãn].
 */
function cvc_render_listing_hero( string $eyebrow, string $title, string $description, array $stats = array() ): void {
	?>
	<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-amber-500/30 shadow-2xl">
		<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
			<div class="space-y-2 max-w-2xl">
				<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider"><?php echo esc_html( $eyebrow ); ?></span>
				<h1 class="text-2xl sm:text-3xl font-black text-white"><?php echo esc_html( $title ); ?></h1>
				<p class="text-xs sm:text-sm text-slate-300 leading-relaxed"><?php echo esc_html( $description ); ?></p>
			</div>
			<?php if ( ! empty( $stats ) ) : ?>
				<dl class="flex items-center gap-3 shrink-0">
					<?php foreach ( $stats as $stat ) : ?>
						<div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700 text-center">
							<dd class="text-xl font-black text-amber-400"><?php echo esc_html( $stat[0] ); ?></dd>
							<dt class="text-[10px] text-slate-400"><?php echo esc_html( $stat[1] ); ?></dt>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
