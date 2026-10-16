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
	<form method="get" action="<?php echo esc_url( $action ); ?>" class="dz-box" role="search">
		<label for="<?php echo esc_attr( $field_id ); ?>" class="dz-box-head"><?php echo esc_html( $heading ); ?></label>
		<div class="dz-box-body">
			<input type="search" id="<?php echo esc_attr( $field_id ); ?>" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="dz-input">
			<div style="display:flex;gap:8px;align-items:center;margin-top:8px">
				<button type="submit" class="dz-btn dz-btn-primary">Tìm</button>
				<?php if ( '' !== $q ) : ?><a href="<?php echo esc_url( $action ); ?>" class="dz-link">Xóa bộ lọc</a><?php endif; ?>
			</div>
		</div>
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
	<nav class="dz-box dz-explore" aria-label="Các mục khác">
		<h2 class="dz-box-head">Mục lục</h2>
		<div class="dz-box-body">
			<ul class="dz-toc">
				<?php foreach ( $items as $key => $item ) : ?>
					<li><a href="<?php echo esc_url( $item[1] ); ?>" class="<?php echo $key === $current ? 'is-active' : ''; ?>" <?php echo $key === $current ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $item[0] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
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

/**
 * @param array<int, string> $exclude Loai noi dung trang hien tai da hien (khong lap o cot ben).
 */
function cvc_render_study_sidebar( array $exclude = array() ): void {
	// Chi khoa hoc ban duoc; neu khong co -> khung tu lap day (de thi -> van ban -> chu de).
	$courses = array_values( array_filter( cvc_featured_courses(), fn ( $c ) => empty( $c['coming_soon'] ) ) );
	$slot    = null;
	if ( empty( $courses ) ) {
		$slot = cvc_content_slot( 'sidebar', $exclude, array(), 3 );
		if ( null !== $slot ) {
			cvc_render_content_slot_compact( $slot );
		}
	}
	?>
	<?php if ( ! empty( $courses ) ) : ?>
		<section class="dz-box" aria-labelledby="cvc-sidebar-courses">
			<h2 id="cvc-sidebar-courses" class="dz-box-head">Khóa học ôn thi</h2>
			<div class="dz-box-body">
				<ul class="dz-list dz-list-sm">
					<?php foreach ( $courses as $course ) : ?>
						<?php
						$price = $course['price'];
						$sale  = $course['sale_price'];
						$final = null !== $sale ? $sale : $price;
						?>
						<li>
							<a class="dz-sidelink" href="<?php echo esc_url( cvc_course_url( $course['slug'] ) ); ?>"><?php echo esc_html( $course['title'] ); ?></a>
							<div class="dz-item-meta">
								<?php if ( $final <= 0 ) : ?><span class="dz-tag is-ok">Miễn phí</span><?php else : ?><strong><?php echo esc_html( cvc_format_vnd( $final ) ); ?></strong><?php if ( null !== $sale && $sale < $price ) : ?><s><?php echo esc_html( cvc_format_vnd( $price ) ); ?></s><?php endif; ?><?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<p style="margin:10px 0 0"><a class="dz-link" href="<?php echo esc_url( cvc_courses_url() ); ?>">Tất cả khóa học →</a></p>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( 'exams' !== ( $slot['kind'] ?? '' ) ) : ?>
	<section class="dz-box" aria-labelledby="cvc-sidebar-practice">
		<h2 id="cvc-sidebar-practice" class="dz-box-head">Luyện đề miễn phí</h2>
		<div class="dz-box-body">
			<p style="margin:0 0 10px">Làm đề thi thử có chấm điểm và xem giải thích từng câu sau khi nộp.</p>
			<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="dz-btn dz-btn-primary">Vào danh sách đề thi</a>
		</div>
	</section>
	<?php endif; ?>
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
	<header class="dz-pagehead is-list">
		<p class="dz-kicker"><?php echo esc_html( $eyebrow ); ?></p>
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( '' !== $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
		<?php if ( ! empty( $stats ) ) : ?>
			<p class="dz-pagehead-stats"><?php echo implode( ' · ', array_map( fn ( $st ) => '<strong>' . esc_html( $st[0] ) . '</strong> ' . esc_html( $st[1] ), $stats ) ); // phpcs:ignore ?></p>
		<?php endif; ?>
		<span class="dz-rule" aria-hidden="true"></span>
	</header>
	<?php
}
