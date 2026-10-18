<?php
/**
 * Career Intelligence (18/10/2026) - trang /tuyen-dung/ + khối "liên quan" ở trang tin / trang đề.
 *
 * Laravel là nguồn dữ liệu duy nhất (api/career/*, api/me/career*). Theme chỉ:
 *  - gọi API (kèm token nếu đã đăng nhập) và render HTML bằng các hàm cvc_ci_render_* bên dưới;
 *  - AJAX `cvc_ci_view` trả lại đúng HTML đó khi người dùng lọc / chuyển tab / phân trang (1 nguồn markup);
 *  - AJAX `cvc_ci_act` (nonce + đăng nhập) cho thao tác ghi: mục tiêu, lưu, nhắc hạn, tùy chọn, lộ trình, bài luyện.
 * Không có dữ liệu mẫu: thiếu dữ liệu -> trạng thái rỗng / lỗi rõ ràng.
 *
 * @package CongVienChuc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Hằng số hiển thị (khớp App\Services\Career\JobFamily)
 * ------------------------------------------------------------------ */
function cvc_ci_families(): array {
	return array(
		'admin'       => 'Hành chính – văn phòng',
		'education'   => 'Giáo dục – đào tạo',
		'health'      => 'Y tế',
		'agri_forest' => 'Nông nghiệp – tài nguyên – môi trường',
		'finance'     => 'Tài chính – kế toán – thuế',
		'justice'     => 'Tư pháp – pháp chế',
		'culture'     => 'Văn hóa – thể thao – du lịch',
		'it'          => 'Công nghệ thông tin',
		'science'     => 'Khoa học – kỹ thuật',
		'other'       => 'Lĩnh vực khác',
	);
}

function cvc_ci_kinds(): array {
	return array(
		'civil'    => 'Tuyển dụng công chức',
		'public'   => 'Tuyển dụng viên chức',
		'contract' => 'Hợp đồng / khác',
	);
}

function cvc_ci_statuses(): array {
	return array(
		'open'         => 'Đang nhận hồ sơ',
		'closing_soon' => 'Sắp hết hạn (≤ 7 ngày)',
		'examining'    => 'Đang tổ chức thi / có kết quả',
		'all'          => 'Tất cả (gồm đã hết hạn)',
	);
}

function cvc_ci_url( array $args = array(), int $page = 1 ): string {
	$base = $page > 1 ? home_url( '/tuyen-dung/page/' . $page . '/' ) : home_url( '/tuyen-dung/' );
	$args = array_filter( $args, static fn ( $v ) => null !== $v && '' !== $v && array() !== $v );

	return empty( $args ) ? $base : add_query_arg( array_map( static fn ( $v ) => is_array( $v ) ? implode( ',', $v ) : $v, $args ), $base );
}

/**
 * Đọc bộ lọc từ URL (giữ tương thích ?tinh= ?loai= ?phuong-thuc= cũ).
 *
 * @return array<string, mixed>
 */
function cvc_ci_filters_from_request( array $src ): array {
	$kinds_legacy = array( 'civil_servant' => 'civil', 'public_employee' => 'public', 'contract_admin' => 'contract', 'contract_public_unit' => 'contract' );
	$kind_raw     = isset( $src['loai'] ) ? (string) wp_unslash( $src['loai'] ) : '';
	$kinds        = array();
	foreach ( array_filter( array_map( 'sanitize_key', explode( ',', $kind_raw ) ) ) as $k ) {
		$k = $kinds_legacy[ $k ] ?? $k;
		if ( isset( cvc_ci_kinds()[ $k ] ) ) {
			$kinds[ $k ] = $k;
		}
	}
	$family = isset( $src['nhom'] ) ? sanitize_key( wp_unslash( $src['nhom'] ) ) : '';
	$status = isset( $src['trang-thai'] ) ? sanitize_key( wp_unslash( $src['trang-thai'] ) ) : '';
	$sort   = isset( $src['sap-xep'] ) ? sanitize_key( wp_unslash( $src['sap-xep'] ) ) : '';
	$method = isset( $src['phuong-thuc'] ) ? sanitize_key( wp_unslash( $src['phuong-thuc'] ) ) : '';
	$tab    = isset( $src['tab'] ) ? sanitize_key( wp_unslash( $src['tab'] ) ) : '';

	return array(
		'q'           => isset( $src['q'] ) ? mb_substr( trim( sanitize_text_field( wp_unslash( (string) $src['q'] ) ) ), 0, 100 ) : '',
		'nhom'        => isset( cvc_ci_families()[ $family ] ) ? $family : '',
		'tinh'        => isset( $src['tinh'] ) ? absint( $src['tinh'] ) : 0,
		'loai'        => array_values( $kinds ),
		'trang-thai'  => isset( cvc_ci_statuses()[ $status ] ) ? $status : '',
		'sap-xep'     => in_array( $sort, array( 'relevant', 'deadline', 'newest' ), true ) ? $sort : '',
		'luu'         => ! empty( $src['luu'] ) ? 1 : 0,
		'phuong-thuc' => function_exists( 'cvc_recruitment_method_options' ) && isset( cvc_recruitment_method_options()[ $method ] ) ? $method : '',
		'tab'         => in_array( $tab, array( 'jobs', 'exams', 'resources', 'path', 'matching' ), true ) ? $tab : 'jobs',
		'de-nhom'     => isset( $src['de-nhom'] ) ? sanitize_key( wp_unslash( $src['de-nhom'] ) ) : '',
		'loai-de'     => isset( $src['loai-de'] ) && in_array( $src['loai-de'], array( 'practice', 'mock', 'official' ), true ) ? (string) $src['loai-de'] : '',
		'de-sap-xep'  => isset( $src['de-sap-xep'] ) && in_array( $src['de-sap-xep'], array( 'relevance', 'title', 'duration', 'newest' ), true ) ? (string) $src['de-sap-xep'] : '',
		'da-lam'      => isset( $src['da-lam'] ) && in_array( $src['da-lam'], array( 'done', 'not_done' ), true ) ? (string) $src['da-lam'] : '',
		'tin'         => isset( $src['tin'] ) ? absint( $src['tin'] ) : 0,
	);
}

/** Tham số URL (để giữ khi phân trang) từ bộ lọc. */
function cvc_ci_url_args( array $f ): array {
	return array(
		'q'           => $f['q'],
		'nhom'        => $f['nhom'],
		'tinh'        => $f['tinh'] ?: null,
		'loai'        => $f['loai'],
		'trang-thai'  => $f['trang-thai'],
		'sap-xep'     => $f['sap-xep'],
		'luu'         => $f['luu'] ?: null,
		'phuong-thuc' => $f['phuong-thuc'],
	);
}

/* ------------------------------------------------------------------
 * Gọi API
 * ------------------------------------------------------------------ */
function cvc_ci_api( string $path, array $query = array(), ?string $token = null, int $timeout = 12, int $cache = 0 ): array {
	$key = '';
	if ( null === $token && $cache > 0 ) {
		$key    = 'cvc_ci_' . md5( $path . '|' . wp_json_encode( $query ) );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}
	$res = ( new CVC_Api_Client( null, $timeout ) )->get( $path, $query, $token );
	if ( ! $res['ok'] && ! in_array( (int) ( $res['status'] ?? 0 ), array( 401, 403, 404, 422 ), true ) ) {
		$res = ( new CVC_Api_Client( null, $timeout + 10 ) )->get( $path, $query, $token );
	}
	if ( '' !== $key && $res['ok'] ) {
		set_transient( $key, $res, $cache );
	}

	return $res;
}

function cvc_ci_jobs_query( array $f, int $page ): array {
	return array_filter(
		array(
			'q'           => $f['q'],
			'job_family'  => $f['nhom'],
			'province_id' => $f['tinh'] ?: null,
			'kind'        => $f['loai'] ?: null,
			'status'      => $f['trang-thai'] ?: 'open',
			'sort'        => $f['sap-xep'] ?: 'relevant',
			'saved'       => $f['luu'] ? 1 : null,
			'method'      => $f['phuong-thuc'],
			'page'        => $page,
			'per_page'    => 10,
		),
		static fn ( $v ) => null !== $v && '' !== $v
	);
}

function cvc_ci_exams_query( array $f, int $page ): array {
	return array_filter(
		array(
			'q'              => $f['q'],
			'job_family'     => $f['de-nhom'] ?: $f['nhom'],
			'exam_type'      => $f['loai-de'],
			'sort'           => $f['de-sap-xep'] ?: 'relevance',
			'done'           => $f['da-lam'],
			'recruitment_id' => $f['tin'] ?: null,
			'page'           => $page,
			'per_page'       => 10,
		),
		static fn ( $v ) => null !== $v && '' !== $v
	);
}

function cvc_ci_resources_query( array $f, int $page ): array {
	return array_filter(
		array(
			'q'              => $f['q'],
			'job_family'     => $f['nhom'],
			'recruitment_id' => $f['tin'] ?: null,
			'page'           => $page,
			'per_page'       => 10,
		),
		static fn ( $v ) => null !== $v && '' !== $v
	);
}

/* ------------------------------------------------------------------
 * Render helpers
 * ------------------------------------------------------------------ */
function cvc_ci_date( ?string $iso ): string {
	if ( empty( $iso ) ) {
		return '';
	}
	$ts = strtotime( $iso );

	return $ts ? wp_date( 'd/m/Y', $ts, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) : '';
}

function cvc_ci_status_pill( array $st ): string {
	$cls = array(
		'open'         => '',
		'closing_soon' => ' is-gold',
		'no_deadline'  => ' is-gray',
		'examining'    => ' is-blue',
		'result'       => ' is-blue',
		'closed'       => ' is-gray',
	)[ (string) ( $st['key'] ?? '' ) ] ?? ' is-gray';

	return '<span class="ci-pill' . esc_attr( $cls ) . '">' . esc_html( (string) ( $st['label'] ?? '' ) ) . '</span>';
}

function cvc_ci_tier_pill( string $tier, string $label = '' ): string {
	$map = array(
		'official'  => array( '', 'Chính thức' ),
		'curated'   => array( ' is-blue', 'Đã biên tập' ),
		'suggested' => array( ' is-gold', 'Tham khảo' ),
	);
	$m   = $map[ $tier ] ?? array( ' is-gray', 'Liên quan' );

	return '<span class="ci-pill' . esc_attr( $m[0] ) . '" title="' . esc_attr( $label ) . '">' . esc_html( $m[1] ) . '</span>';
}

function cvc_ci_login_url(): string {
	return cvc_login_url( home_url( add_query_arg( null, null ) ) );
}

/** 1 thẻ tin tuyển dụng (danh sách). */
function cvc_ci_render_job_card( array $r, bool $logged_in ): void {
	$st    = (array) ( $r['status'] ?? array() );
	$exams = (array) ( $r['exams'] ?? array() );
	$meta  = array_filter(
		array(
			! empty( $r['agency']['name'] ) ? (string) $r['agency']['name'] : null,
			! empty( $r['province']['name'] ) ? 'Địa phương: ' . $r['province']['name'] : null,
			! empty( $r['total_positions'] ) ? 'Chỉ tiêu: ' . number_format_i18n( (int) $r['total_positions'] ) : null,
			! empty( $r['doc_number'] ) ? 'Số ' . $r['doc_number'] : null,
		)
	);
	?>
	<article class="ci-job" data-id="<?php echo (int) $r['id']; ?>">
		<div class="ci-job-top">
			<div class="ci-job-logo" aria-hidden="true">⌂</div>
			<div class="ci-job-main">
				<h3 class="ci-job-title"><a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><?php echo esc_html( (string) $r['title'] ); ?></a></h3>
				<div class="ci-meta"><?php foreach ( $meta as $m ) : ?><span><?php echo esc_html( $m ); ?></span><?php endforeach; ?></div>
			</div>
			<?php echo cvc_ci_status_pill( $st ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<div class="ci-tags">
			<?php if ( ! empty( $r['type_label'] ) ) : ?><span class="ci-pill is-blue"><?php echo esc_html( (string) $r['type_label'] ); ?></span><?php endif; ?>
			<?php if ( ! empty( $r['job_family_label'] ) ) : ?><span class="ci-pill is-blue"><?php echo esc_html( (string) $r['job_family_label'] ); ?></span><?php endif; ?>
			<span class="ci-pill <?php echo ! empty( $st['source_official'] ) ? '' : 'is-gray'; ?>"><?php echo ! empty( $st['source_official'] ) ? 'Nguồn chính thức' : 'Tin sưu tầm'; ?></span>
			<?php foreach ( (array) ( $r['goal_reasons'] ?? array() ) as $gr ) : ?><span class="ci-pill is-gold">✓ <?php echo esc_html( (string) $gr ); ?></span><?php endforeach; ?>
		</div>
		<?php if ( ! empty( $r['summary'] ) ) : ?><p class="ci-job-body"><?php echo esc_html( wp_trim_words( (string) $r['summary'], 40 ) ); ?></p><?php endif; ?>
		<div class="ci-job-foot">
			<span class="ci-deadline<?php echo empty( $st['deadline'] ) ? ' is-unknown' : ''; ?>">◷ <?php echo ! empty( $st['deadline'] ) ? 'Hạn nộp ' . esc_html( cvc_ci_date( (string) $st['deadline'] ) ) . ( ! empty( $st['deadline_verified'] ) ? '' : ' · cần đối chiếu' ) : 'Chưa công bố hạn nộp'; ?></span>
			<div class="ci-actions">
				<button type="button" class="ci-btn" data-ci="related" data-id="<?php echo (int) $r['id']; ?>">Xem đề &amp; tài liệu ↗</button>
				<?php if ( $logged_in ) : ?>
					<button type="button" class="ci-btn<?php echo ! empty( $r['saved'] ) ? ' is-teal' : ''; ?>" data-ci="save" data-type="recruitment" data-id="<?php echo (int) $r['id']; ?>" aria-pressed="<?php echo ! empty( $r['saved'] ) ? 'true' : 'false'; ?>"><?php echo ! empty( $r['saved'] ) ? '♥ Đã lưu' : '♡ Lưu cơ hội'; ?></button>
					<?php if ( ! empty( $st['is_open'] ) && ! empty( $st['deadline_verified'] ) ) : ?>
						<button type="button" class="ci-btn<?php echo ! empty( $r['reminder'] ) ? ' is-teal' : ''; ?>" data-ci="reminder" data-id="<?php echo (int) $r['id']; ?>" aria-pressed="<?php echo ! empty( $r['reminder'] ) ? 'true' : 'false'; ?>"><?php echo ! empty( $r['reminder'] ) ? '◷ Đang nhắc hạn' : '◷ Nhắc hạn'; ?></button>
					<?php endif; ?>
				<?php else : ?>
					<a class="ci-btn" href="<?php echo esc_url( cvc_ci_login_url() ); ?>" rel="nofollow">♡ Đăng nhập để lưu</a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! empty( $exams ) ) : ?>
			<div class="ci-linked">
				<b>ĐỀ THI LIÊN QUAN</b>
				<?php foreach ( $exams as $e ) : ?>
					<div class="ci-linked-row">
						<a href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>"><?php echo esc_html( (string) $e['title'] ); ?></a>
						<span><?php echo cvc_ci_tier_pill( (string) $e['tier'], (string) ( $e['tier_label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( trim( ( $e['total_questions'] ? $e['total_questions'] . ' câu' : '' ) . ( $e['duration_minutes'] ? ' · ' . $e['duration_minutes'] . ' phút' : '' ), ' ·' ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</article>
	<?php
}

/** Phân trang dạng link (SEO) - JS chặn và tải bằng AJAX. */
function cvc_ci_render_pager( array $pg, string $view, callable $url ): void {
	$cur  = (int) ( $pg['page'] ?? 1 );
	$last = (int) ( $pg['last_page'] ?? 1 );
	if ( $last <= 1 ) {
		return;
	}
	?>
	<nav class="ci-pager" aria-label="Phân trang">
		<?php if ( $cur > 1 ) : ?><a class="ci-btn" href="<?php echo esc_url( $url( $cur - 1 ) ); ?>" data-ci-page="<?php echo $cur - 1; ?>" data-view="<?php echo esc_attr( $view ); ?>">← Trước</a><?php endif; ?>
		<span>Trang <?php echo (int) $cur; ?> / <?php echo (int) $last; ?></span>
		<?php if ( $cur < $last ) : ?><a class="ci-btn" href="<?php echo esc_url( $url( $cur + 1 ) ); ?>" data-ci-page="<?php echo $cur + 1; ?>" data-view="<?php echo esc_attr( $view ); ?>">Sau →</a><?php endif; ?>
	</nav>
	<?php
}

function cvc_ci_render_jobs( array $res, array $f, bool $logged_in ): void {
	if ( ! $res['ok'] ) {
		if ( 401 === (int) ( $res['status'] ?? 0 ) ) {
			echo '<div class="ci-empty"><b>Đăng nhập để xem tin đã lưu</b><a class="ci-btn is-primary" href="' . esc_url( cvc_ci_login_url() ) . '" rel="nofollow">Đăng nhập</a></div>';
			return;
		}
		echo '<div class="ci-empty is-error"><b>Không tải được danh sách tin tuyển dụng</b>Hệ thống đang bận — vui lòng thử lại sau ít phút. <button type="button" class="ci-btn" data-ci="retry" data-view="jobs">Thử lại</button></div>';
		return;
	}
	$d     = (array) ( $res['data']['data'] ?? array() );
	$items = (array) ( $d['items'] ?? array() );
	$pg    = (array) ( $d['pagination'] ?? array() );
	echo '<div class="ci-results-meta" data-total="' . (int) ( $pg['total'] ?? 0 ) . '">' . esc_html( number_format_i18n( (int) ( $pg['total'] ?? 0 ) ) ) . ' tin' . ( '' !== $f['q'] ? ' khớp “' . esc_html( $f['q'] ) . '”' : '' ) . '</div>';
	if ( empty( $items ) ) {
		echo '<div class="ci-empty"><b>Chưa có cơ hội phù hợp bộ lọc</b>Thử bỏ bớt điều kiện lọc hoặc chọn “Tất cả (gồm đã hết hạn)”. <button type="button" class="ci-btn" data-ci="reset">↺ Xóa bộ lọc</button></div>';
		return;
	}
	echo '<div class="ci-job-list">';
	foreach ( $items as $r ) {
		cvc_ci_render_job_card( (array) $r, $logged_in );
	}
	echo '</div>';
	cvc_ci_render_pager( $pg, 'jobs', static fn ( int $p ) => cvc_ci_url( cvc_ci_url_args( $f ), $p ) );
}

function cvc_ci_render_exam_card( array $e, int $no, bool $logged_in ): void {
	$att = $e['attempt'] ?? null;
	?>
	<article class="ci-exam" data-id="<?php echo (int) $e['id']; ?>">
		<div class="ci-exam-num"><?php echo esc_html( str_pad( (string) $no, 2, '0', STR_PAD_LEFT ) ); ?></div>
		<div class="ci-exam-main">
			<h4 class="ci-exam-title"><a href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>"><?php echo esc_html( (string) $e['title'] ); ?></a></h4>
			<div class="ci-tags">
				<span class="ci-pill"><?php echo esc_html( (string) ( $e['type_label'] ?? 'Đề thi' ) ); ?></span>
				<?php if ( ! empty( $e['job_family_label'] ) ) : ?><span class="ci-pill is-blue"><?php echo esc_html( (string) $e['job_family_label'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $e['tier'] ) ) : ?><?php echo cvc_ci_tier_pill( (string) $e['tier'], (string) ( $e['tier_label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?>
				<?php if ( ! empty( $e['stage'] ) ) : ?><span class="ci-pill is-gray"><?php echo esc_html( (string) $e['stage'] ); ?></span><?php endif; ?>
			</div>
			<div class="ci-meta">
				<?php if ( ! empty( $e['code'] ) ) : ?><span>Mã: <?php echo esc_html( (string) $e['code'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $e['total_questions'] ) ) : ?><span><?php echo (int) $e['total_questions']; ?> câu</span><?php endif; ?>
				<?php if ( ! empty( $e['duration_minutes'] ) ) : ?><span><?php echo (int) $e['duration_minutes']; ?> phút</span><?php endif; ?>
				<?php if ( is_array( $att ) ) : ?><span>Đã làm <?php echo (int) $att['attempts']; ?> lần · tốt nhất <?php echo null !== $att['best'] ? (int) $att['best'] . '%' : '--'; ?></span><?php endif; ?>
				<?php if ( ! empty( $e['related_open_recruitments'] ) ) : ?><span><?php echo (int) $e['related_open_recruitments']; ?> tin đang mở cùng nhóm</span><?php endif; ?>
			</div>
			<?php if ( ! empty( $e['reasons'] ) ) : ?><p class="ci-exam-desc">Vì sao đề xuất: <?php echo esc_html( implode( ' · ', (array) $e['reasons'] ) ); ?></p><?php endif; ?>
		</div>
		<div class="ci-exam-actions">
			<a class="ci-btn is-primary" href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>"><?php echo $logged_in ? 'Làm bài' : 'Xem đề'; ?></a>
			<?php if ( $logged_in ) : ?>
				<button type="button" class="ci-btn<?php echo ! empty( $e['saved'] ) ? ' is-teal' : ''; ?>" data-ci="save" data-type="exam" data-id="<?php echo (int) $e['id']; ?>" aria-pressed="<?php echo ! empty( $e['saved'] ) ? 'true' : 'false'; ?>"><?php echo ! empty( $e['saved'] ) ? '♥ Đã lưu' : '♡ Lưu đề'; ?></button>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

function cvc_ci_render_exams( array $res, array $f, bool $logged_in ): void {
	if ( ! $res['ok'] ) {
		echo 401 === (int) ( $res['status'] ?? 0 )
			? '<div class="ci-empty"><b>Đăng nhập để lọc đề đã làm</b><a class="ci-btn is-primary" href="' . esc_url( cvc_ci_login_url() ) . '" rel="nofollow">Đăng nhập</a></div>'
			: '<div class="ci-empty is-error"><b>Không tải được danh sách đề thi</b><button type="button" class="ci-btn" data-ci="retry" data-view="exams">Thử lại</button></div>';
		return;
	}
	$d     = (array) ( $res['data']['data'] ?? array() );
	$items = (array) ( $d['items'] ?? array() );
	$pg    = (array) ( $d['pagination'] ?? array() );
	$for   = $d['for_recruitment'] ?? null;
	if ( is_array( $for ) ) {
		echo '<div class="ci-for">Đề được ghép nối với tin: <b>' . esc_html( (string) $for['title'] ) . '</b> <button type="button" class="ci-link" data-ci="clear-job">× Bỏ chọn tin</button></div>';
	}
	echo '<div class="ci-results-meta">' . esc_html( number_format_i18n( (int) ( $pg['total'] ?? count( $items ) ) ) ) . ' đề</div>';
	if ( empty( $items ) ) {
		echo '<div class="ci-empty"><b>Chưa có đề phù hợp</b>Thử chọn nhóm vị trí khác hoặc bỏ từ khóa.</div>';
		return;
	}
	echo '<div class="ci-exam-list">';
	$start = ( (int) ( $pg['page'] ?? 1 ) - 1 ) * (int) ( $pg['per_page'] ?? 10 );
	foreach ( $items as $i => $e ) {
		cvc_ci_render_exam_card( (array) $e, $start + $i + 1, $logged_in );
	}
	echo '</div>';
	cvc_ci_render_pager( $pg, 'exams', static fn ( int $p ) => add_query_arg( 'tab', 'exams', cvc_ci_url( cvc_ci_url_args( $f ), $p ) ) );
}

function cvc_ci_render_resource( array $d ): void {
	$effect_cls = array(
		'in_force' => '',
		'expired'  => ' is-red',
		'replaced' => ' is-red',
	)[ (string) ( $d['effect'] ?? '' ) ] ?? ' is-gray';
	?>
	<article class="ci-res">
		<div class="ci-res-icon" aria-hidden="true">§</div>
		<div class="ci-res-main">
			<a class="ci-res-title" href="<?php echo esc_url( home_url( (string) $d['url'] ) ); ?>"><?php echo esc_html( (string) $d['title'] ); ?></a>
			<small><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $d['kind_label'] ?? '' ), ! empty( $d['number'] ) ? 'Số hiệu ' . $d['number'] : null, ! empty( $d['issued_date'] ) ? 'Ban hành ' . cvc_ci_date( (string) $d['issued_date'] ) : null, ! empty( $d['effective_date'] ) ? 'Hiệu lực ' . cvc_ci_date( (string) $d['effective_date'] ) : null ) ) ) ); ?></small>
			<?php if ( ! empty( $d['reasons'] ) ) : ?><p>Vì sao đề xuất: <?php echo esc_html( implode( ' · ', (array) $d['reasons'] ) ); ?></p><?php endif; ?>
			<?php if ( ! empty( $d['practice_exam']['slug'] ) ) : ?><p><a class="ci-link" href="<?php echo esc_url( cvc_exam_url( (string) $d['practice_exam']['slug'] ) ); ?>">Luyện tập theo văn bản này (<?php echo (int) $d['practice_exam']['total_questions']; ?> câu) →</a></p><?php endif; ?>
		</div>
		<div class="ci-res-pills">
			<?php if ( ! empty( $d['tier'] ) ) : ?><?php echo cvc_ci_tier_pill( (string) $d['tier'], (string) ( $d['tier_label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?>
			<span class="ci-pill<?php echo esc_attr( $effect_cls ); ?>"><?php echo esc_html( (string) ( $d['effect_label'] ?? '' ) ); ?></span>
		</div>
	</article>
	<?php
}

function cvc_ci_render_resources( array $res, array $f ): void {
	if ( ! $res['ok'] ) {
		echo '<div class="ci-empty is-error"><b>Không tải được văn bản / tài liệu</b><button type="button" class="ci-btn" data-ci="retry" data-view="resources">Thử lại</button></div>';
		return;
	}
	$d     = (array) ( $res['data']['data'] ?? array() );
	$items = (array) ( $d['items'] ?? array() );
	$pg    = (array) ( $d['pagination'] ?? array() );
	$for   = $d['for_recruitment'] ?? null;
	if ( is_array( $for ) ) {
		echo '<div class="ci-for">Văn bản liên quan tin: <b>' . esc_html( (string) $for['title'] ) . '</b> <button type="button" class="ci-link" data-ci="clear-job">× Bỏ chọn tin</button></div>';
	}
	echo '<div class="ci-results-meta">' . esc_html( number_format_i18n( (int) ( $pg['total'] ?? count( $items ) ) ) ) . ' văn bản</div>';
	if ( empty( $items ) ) {
		echo '<div class="ci-empty"><b>Chưa có văn bản phù hợp</b>Thử bỏ từ khóa hoặc chọn nhóm vị trí khác.</div>';
		return;
	}
	echo '<div class="ci-res-list">';
	foreach ( $items as $d1 ) {
		cvc_ci_render_resource( (array) $d1 );
	}
	echo '</div>';
	cvc_ci_render_pager( $pg, 'resources', static fn ( int $p ) => add_query_arg( 'tab', 'resources', cvc_ci_url( cvc_ci_url_args( $f ), $p ) ) );
}

/** Khối "Đề thi & tài liệu liên quan" của 1 tin - dùng trên trang /tuyen-dung/ (chọn tin) và trang chi tiết tin. */
function cvc_ci_render_related( array $res, bool $logged_in, bool $compact = false ): void {
	if ( ! $res['ok'] ) {
		echo '<div class="ci-empty is-error"><b>Không tải được đề thi và tài liệu liên quan</b>Vui lòng thử lại sau.</div>';
		return;
	}
	$d     = (array) ( $res['data']['data'] ?? array() );
	$rec   = (array) ( $d['recruitment'] ?? array() );
	$exams = (array) ( $d['exams'] ?? array() );
	$docs  = (array) ( $d['resources'] ?? array() );
	$tiers = array();
	foreach ( array_merge( $exams, $docs ) as $x ) {
		$tiers[ (string) ( $x['tier'] ?? '' ) ] = true;
	}
	?>
	<div class="ci-related" data-id="<?php echo (int) ( $rec['id'] ?? 0 ); ?>">
		<?php if ( ! $compact ) : ?>
			<div class="ci-for">Đang xem cơ hội: <b><a href="<?php echo esc_url( cvc_recruitment_url( (string) ( $rec['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $rec['title'] ?? '' ) ); ?></a></b> <?php echo cvc_ci_status_pill( (array) ( $rec['status'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<?php endif; ?>
		<div class="ci-legend">
			<span><?php echo cvc_ci_tier_pill( 'official' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> có căn cứ trong văn bản chính thức (danh mục ôn tập của Hội đồng, văn bản thông báo trích dẫn)</span>
			<span><?php echo cvc_ci_tier_pill( 'curated' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> quản trị viên đã gắn</span>
			<span><?php echo cvc_ci_tier_pill( 'suggested' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> gợi ý theo nhóm vị trí — chưa xác nhận là nội dung thi chính thức</span>
		</div>
		<h4 class="ci-subh">Đề thi liên quan (<?php echo count( $exams ); ?>)</h4>
		<?php if ( empty( $exams ) ) : ?>
			<div class="ci-empty">Chưa có đề phù hợp cho cơ hội này.</div>
		<?php else : ?>
			<div class="ci-exam-list">
				<?php foreach ( $exams as $i => $e ) : ?><?php cvc_ci_render_exam_card( (array) $e, $i + 1, $logged_in ); ?><?php endforeach; ?>
			</div>
		<?php endif; ?>
		<h4 class="ci-subh">Văn bản cần học (<?php echo count( $docs ); ?>)</h4>
		<?php if ( empty( $docs ) ) : ?>
			<div class="ci-empty">Chưa có văn bản gắn với cơ hội này.</div>
		<?php else : ?>
			<div class="ci-res-list"><?php foreach ( $docs as $doc ) : ?><?php cvc_ci_render_resource( (array) $doc ); ?><?php endforeach; ?></div>
		<?php endif; ?>
		<?php if ( empty( $tiers['official'] ) ) : ?>
			<p class="ci-note">Chưa thu thập được danh mục tài liệu ôn tập / môn thi chính thức của đợt này. Các đề và văn bản trên là <b>tham khảo theo nhóm vị trí</b> — hãy đối chiếu thông báo gốc của cơ quan tuyển dụng.</p>
		<?php endif; ?>
	</div>
	<?php
}

/** Cột phải: tổng quan cá nhân. */
function cvc_ci_render_personal( ?array $me, bool $logged_in ): void {
	if ( ! $logged_in ) {
		?>
		<section class="ci-panel" id="ci-personal">
			<div class="ci-panel-head"><h2>Tổng quan cá nhân</h2></div>
			<div class="ci-panel-body">
				<p class="ci-small">Đăng nhập để lưu cơ hội, đặt mục tiêu dự tuyển, nhận nhắc hạn nộp hồ sơ và tạo lộ trình ôn tập theo kết quả làm bài của bạn.</p>
				<a class="ci-btn is-primary ci-wide" href="<?php echo esc_url( cvc_ci_login_url() ); ?>" rel="nofollow">Đăng nhập</a>
				<a class="ci-btn ci-wide" style="margin-top:6px" href="<?php echo esc_url( cvc_register_url() ); ?>" rel="nofollow">Tạo tài khoản miễn phí</a>
			</div>
		</section>
		<?php
		return;
	}
	if ( null === $me ) {
		echo '<section class="ci-panel" id="ci-personal"><div class="ci-panel-head"><h2>Tổng quan cá nhân</h2></div><div class="ci-panel-body"><div class="ci-empty is-error">Chưa tải được dữ liệu cá nhân. <button type="button" class="ci-btn" data-ci="retry" data-view="personal">Thử lại</button></div></div></section>';
		return;
	}
	$saved  = (array) ( $me['saved_recruitments'] ?? array() );
	$sexams = (array) ( $me['saved_exams'] ?? array() );
	$weak   = (array) ( $me['weak_topics'] ?? array() );
	$up     = (array) ( $me['upcoming_deadlines'] ?? array() );
	$path   = $me['path'] ?? null;
	$open   = count( array_filter( $saved, static fn ( $r ) => ! empty( $r['status']['is_open'] ) ) );
	?>
	<section class="ci-panel" id="ci-personal">
		<div class="ci-panel-head"><h2>Tổng quan cá nhân</h2><?php if ( ! empty( $me['new_matches'] ) ) : ?><a class="ci-pill is-gold" href="<?php echo esc_url( cvc_account_url( 'recruitment-matches' ) ); ?>"><?php echo (int) $me['new_matches']; ?> tin mới khớp mục tiêu</a><?php endif; ?></div>
		<div class="ci-panel-body">
			<div class="ci-progress"><div class="ci-progress-label"><span>Cơ hội đã lưu</span><b><?php echo count( $saved ); ?><?php echo $open !== count( $saved ) ? ' (' . (int) $open . ' còn hạn)' : ''; ?></b></div></div>
			<div class="ci-progress"><div class="ci-progress-label"><span>Đề thi đã lưu</span><b><?php echo count( $sexams ); ?></b></div></div>
			<div class="ci-progress"><div class="ci-progress-label"><span>Chủ đề cần củng cố</span><b><?php echo count( $weak ); ?></b></div></div>
			<?php if ( is_array( $path ) ) : ?>
				<div class="ci-progress"><div class="ci-progress-label"><span>Lộ trình đang thực hiện</span><b><?php echo (int) $path['progress_percent']; ?>%</b></div><div class="ci-bar"><span style="width:<?php echo (int) $path['progress_percent']; ?>%"></span></div></div>
			<?php endif; ?>
			<?php if ( ! empty( $up ) ) : ?>
				<div class="ci-mini-list">
					<b>Hạn nộp sắp tới</b>
					<?php foreach ( array_slice( $up, 0, 4 ) as $r ) : ?>
						<a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><span><?php echo esc_html( wp_trim_words( (string) $r['title'], 12 ) ); ?></span><em><?php echo esc_html( cvc_ci_date( (string) $r['status']['deadline'] ) ); ?><?php echo ! empty( $r['reminder'] ) ? ' · ◷' : ''; ?></em></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $weak ) ) : ?>
				<div class="ci-mini-list">
					<b>Chủ đề yếu (tỷ lệ đúng)</b>
					<?php foreach ( array_slice( $weak, 0, 3 ) as $w ) : ?>
						<?php $href = ! empty( $w['slug'] ) ? cvc_topic_url( (string) $w['slug'] ) : ''; ?>
						<a href="<?php echo esc_url( $href ?: '#ci-tab-path' ); ?>"><span><?php echo esc_html( wp_trim_words( (string) $w['name'], 12 ) ); ?></span><em><?php echo (int) $w['accuracy']; ?>%</em></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="ci-insight"><b>Gợi ý tiếp theo</b><p><?php echo esc_html( (string) ( $me['next_action'] ?? '' ) ); ?></p></div>
		</div>
	</section>
	<?php
}

/** Panel mục tiêu dự tuyển (trong bộ lọc nâng cao). */
function cvc_ci_render_goal_form( ?array $me, bool $logged_in, array $provinces ): void {
	$g = is_array( $me['goal'] ?? null ) ? $me['goal'] : array();
	?>
	<section class="ci-panel">
		<div class="ci-panel-head"><h2>Mục tiêu dự tuyển</h2><?php echo ! empty( $g ) ? '<span class="ci-pill">Đã lưu</span>' : '<span class="ci-pill is-gray">Chưa đặt</span>'; ?></div>
		<div class="ci-panel-body">
			<?php if ( ! $logged_in ) : ?>
				<p class="ci-small">Đặt mục tiêu (nhóm vị trí, địa phương) để hệ thống ưu tiên tin, đề và tài liệu phù hợp, và báo khi có tin mới khớp mục tiêu.</p>
				<a class="ci-btn is-primary ci-wide" href="<?php echo esc_url( cvc_ci_login_url() ); ?>" rel="nofollow">Đăng nhập để đặt mục tiêu</a>
			<?php else : ?>
				<form id="ci-goal-form" class="ci-goal">
					<label class="ci-label" for="ci-goal-family">Tôi đang hướng tới</label>
					<select id="ci-goal-family" name="job_family" class="ci-field">
						<option value="">Chưa chọn nhóm vị trí</option>
						<?php foreach ( cvc_ci_families() as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( (string) ( $g['job_family'] ?? '' ), $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
					</select>
					<label class="ci-label" for="ci-goal-type">Loại tuyển dụng</label>
					<select id="ci-goal-type" name="employment_type" class="ci-field">
						<option value="">Chưa chọn</option>
						<?php foreach ( array( 'civil' => 'Công chức', 'public' => 'Viên chức', 'contract' => 'Hợp đồng / khác' ) as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( (string) ( $g['employment_type'] ?? '' ), $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
					</select>
					<label class="ci-label" for="ci-goal-province">Địa phương</label>
					<select id="ci-goal-province" name="province_id" class="ci-field">
						<option value="">Mọi địa phương</option>
						<?php foreach ( $provinces as $p ) : ?><option value="<?php echo (int) $p['id']; ?>"<?php selected( (int) ( $g['province_id'] ?? 0 ), (int) $p['id'] ); ?>><?php echo esc_html( (string) $p['name'] ); ?></option><?php endforeach; ?>
					</select>
					<label class="ci-label" for="ci-goal-date">Ngày dự kiến thi (nếu biết)</label>
					<input id="ci-goal-date" name="target_date" type="date" class="ci-field" value="<?php echo esc_attr( (string) ( $g['target_date'] ?? '' ) ); ?>" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
					<?php if ( ! empty( $g['recruitment'] ) ) : ?><p class="ci-small">Tin mục tiêu: <a href="<?php echo esc_url( cvc_recruitment_url( (string) $g['recruitment']['slug'] ) ); ?>"><?php echo esc_html( (string) $g['recruitment']['title'] ); ?></a> <button type="button" class="ci-link" data-ci="goal-clear-rec">× bỏ</button></p><input type="hidden" name="recruitment_id" value="<?php echo (int) $g['recruitment']['id']; ?>"><?php endif; ?>
					<button type="submit" class="ci-btn is-teal ci-wide" style="margin-top:10px">Lưu mục tiêu</button>
				</form>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/** Tab "Lộ trình cá nhân". */
function cvc_ci_render_path( ?array $me, bool $logged_in ): void {
	$labels = array( 'topic' => 'Ôn chủ đề', 'practice' => 'Bài luyện', 'legal_document' => 'Văn bản', 'exam' => 'Đề thi', 'task' => 'Nhiệm vụ', 'knowledge_item' => 'Kiến thức' );
	if ( ! $logged_in ) {
		echo '<div class="ci-empty"><b>Lộ trình cá nhân cần đăng nhập</b>Hệ thống dựng lộ trình từ mục tiêu, tin đã lưu và kết quả các lần làm bài của chính bạn. <a class="ci-btn is-primary" href="' . esc_url( cvc_ci_login_url() ) . '" rel="nofollow">Đăng nhập</a></div>';
		return;
	}
	if ( null === $me ) {
		echo '<div class="ci-empty is-error"><b>Chưa tải được lộ trình</b><button type="button" class="ci-btn" data-ci="retry" data-view="path">Thử lại</button></div>';
		return;
	}
	$path  = $me['path'] ?? null;
	$avail = (array) ( $me['practice_available'] ?? array() );
	?>
	<div class="ci-path">
		<div class="ci-path-head">
			<div>
				<b><?php echo is_array( $path ) ? esc_html( (string) $path['title'] ) : 'Chưa có lộ trình'; ?></b>
				<small><?php echo is_array( $path ) ? esc_html( 'Từ ' . cvc_ci_date( (string) $path['start_date'] ) . ' đến ' . cvc_ci_date( (string) $path['target_date'] ) . ' · hoàn thành ' . (int) $path['progress_percent'] . '%' ) : 'Lộ trình được dựng từ mục tiêu, tin đã lưu, chủ đề yếu và câu sai lặp lại.'; ?></small>
			</div>
			<button type="button" class="ci-btn is-primary" data-ci="path-generate"><?php echo is_array( $path ) ? '↻ Tạo lại theo dữ liệu mới' : '✦ Tạo lộ trình của tôi'; ?></button>
		</div>
		<?php if ( is_array( $path ) ) : ?>
			<div class="ci-bar is-big"><span style="width:<?php echo (int) $path['progress_percent']; ?>%"></span></div>
			<ol class="ci-steps">
				<?php foreach ( (array) $path['items'] as $it ) : ?>
					<?php
					$done = 'completed' === $it['status'];
					$link = (array) ( $it['link'] ?? array() );
					$href = '';
					if ( 'exam' === ( $link['kind'] ?? '' ) ) {
						$href = cvc_exam_url( (string) $link['slug'] );
					} elseif ( 'legal_document' === ( $link['kind'] ?? '' ) ) {
						$href = cvc_legal_document_url( (string) $link['slug'] );
					} elseif ( 'topic' === ( $link['kind'] ?? '' ) ) {
						$href = cvc_topic_url( (string) $link['slug'] );
					} elseif ( 'recruitment' === ( $link['kind'] ?? '' ) ) {
						$href = cvc_recruitment_url( (string) $link['slug'] );
					}
					$overdue = ! $done && ! empty( $it['due_date'] ) && strtotime( (string) $it['due_date'] . ' 23:59:59' ) < time();
					?>
					<li class="ci-step<?php echo $done ? ' is-done' : ''; ?><?php echo 'skipped' === $it['status'] ? ' is-skip' : ''; ?>">
						<label class="ci-step-check"><input type="checkbox" data-ci="item" data-id="<?php echo (int) $it['id']; ?>"<?php checked( $done ); ?> aria-label="Đánh dấu hoàn thành"></label>
						<div class="ci-step-main">
							<div class="ci-tags"><span class="ci-pill is-blue">Ưu tiên <?php echo (int) $it['priority']; ?></span><span class="ci-pill is-gray"><?php echo esc_html( $labels[ (string) $it['type'] ] ?? 'Bước học' ); ?></span><?php if ( ! empty( $it['due_date'] ) ) : ?><span class="ci-pill<?php echo $overdue ? ' is-red' : ' is-gold'; ?>">Hạn gợi ý <?php echo esc_html( cvc_ci_date( (string) $it['due_date'] ) ); ?></span><?php endif; ?></div>
							<b><?php echo '' !== $href ? '<a href="' . esc_url( $href ) . '">' . esc_html( (string) $it['title'] ) . '</a>' : esc_html( (string) $it['title'] ); ?></b>
							<?php if ( ! empty( $it['reason'] ) ) : ?><small>Vì sao: <?php echo esc_html( (string) $it['reason'] ); ?></small><?php endif; ?>
						</div>
						<?php if ( 'practice' === $it['type'] && ! $done ) : ?>
							<button type="button" class="ci-btn is-primary" data-ci="practice" data-kind="<?php echo esc_attr( (string) ( $link['practice_kind'] ?? '' ) ); ?>">Làm bài</button>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<div class="ci-practice-row">
			<b>Đề luyện thích ứng từ lịch sử làm bài</b>
			<div class="ci-actions">
				<?php foreach ( array( 'wrong' => 'Ôn câu từng sai', 'weak' => 'Củng cố chủ đề yếu', 'recheck' => 'Kiểm tra lại' ) as $k => $lbl ) : ?>
					<?php $n = (int) ( $avail[ $k ] ?? 0 ); ?>
					<button type="button" class="ci-btn" data-ci="practice" data-kind="<?php echo esc_attr( $k ); ?>"<?php disabled( $n < 5 ); ?> title="<?php echo esc_attr( $n < 5 ? 'Chưa đủ dữ liệu làm bài' : $n . ' câu' ); ?>"><?php echo esc_html( $lbl ); ?> (<?php echo (int) $n; ?>)</button>
				<?php endforeach; ?>
			</div>
			<p class="ci-note">Câu hỏi lấy từ ngân hàng đã duyệt, chấm điểm như đề thường và lưu vào lịch sử thi; tiến độ lộ trình tự cập nhật khi bạn làm xong đề/bài luyện trong lộ trình.</p>
		</div>
	</div>
	<?php
}

/** Panel nhắc việc (tùy chọn thông báo). */
function cvc_ci_render_reminders( ?array $me, bool $logged_in ): void {
	$p = (array) ( $me['preferences'] ?? array() );
	?>
	<section class="ci-panel" id="ci-reminders">
		<div class="ci-panel-head"><h2>Nhắc việc &amp; hạn nộp</h2></div>
		<div class="ci-panel-body">
			<?php if ( ! $logged_in ) : ?>
				<p class="ci-small">Bật “Nhắc hạn” trên từng tin để được nhắc trước 7, 3 và 1 ngày. Chỉ nhắc với tin có hạn nộp từ nguồn chính thức.</p>
			<?php else : ?>
				<form id="ci-prefs" class="ci-checks">
					<label class="ci-check"><input type="checkbox" name="exam_reminders"<?php checked( ! empty( $p['exam_reminders'] ) ); ?>> Nhắc hạn nộp hồ sơ (tin đã bật nhắc)</label>
					<label class="ci-check"><input type="checkbox" name="learning_reminders"<?php checked( ! empty( $p['learning_reminders'] ) ); ?>> Nhắc bước ôn tập đến hạn</label>
					<label class="ci-check"><input type="checkbox" name="recruitment_alerts"<?php checked( ! empty( $p['recruitment_alerts'] ) ); ?>> Báo tin mới khớp mục tiêu</label>
					<label class="ci-label" for="ci-pref-channel" style="margin-top:6px">Kênh nhận</label>
					<select id="ci-pref-channel" name="channel" class="ci-field"><option value="email"<?php selected( (string) ( $p['channel'] ?? 'email' ), 'email' ); ?>>Thông báo trong tài khoản + email</option><option value="in_app"<?php selected( (string) ( $p['channel'] ?? '' ), 'in_app' ); ?>>Chỉ trong tài khoản</option></select>
				</form>
				<p class="ci-note">Nhắc hạn chỉ áp dụng cho tin có hạn nộp lấy từ nguồn chính thức; hạn thay đổi thì mốc nhắc tự cập nhật. Không gửi trùng.</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/** Trang đề thi (khách): "Cơ hội tuyển dụng có liên quan". */
function cvc_ci_render_exam_jobs( int $exam_id ): void {
	$res = cvc_ci_api( '/api/career/exams/' . $exam_id . '/recruitments', array(), cvc_auth_token(), 8, 600 );
	if ( ! $res['ok'] ) {
		return;
	}
	$items = (array) ( $res['data']['data']['items'] ?? array() );
	?>
	<div class="ci ci-inline">
		<section class="ci-panel">
			<div class="ci-panel-head"><h2>Cơ hội tuyển dụng có liên quan</h2><a class="ci-link" href="<?php echo esc_url( cvc_ci_url() ); ?>">Tất cả tin →</a></div>
			<div class="ci-panel-body">
				<?php if ( empty( $items ) ) : ?>
					<p class="ci-small">Chưa có tin đang mở cùng nhóm vị trí với đề này.</p>
				<?php else : ?>
					<div class="ci-mini-list">
						<?php foreach ( $items as $r ) : ?>
							<a href="<?php echo esc_url( cvc_recruitment_url( (string) $r['slug'] ) ); ?>"><span><?php echo esc_html( wp_trim_words( (string) $r['title'], 16 ) ); ?><br><small><?php echo esc_html( implode( ' · ', array_filter( array( $r['province']['name'] ?? null, ! empty( $r['status']['deadline'] ) ? 'hạn ' . cvc_ci_date( (string) $r['status']['deadline'] ) : null ) ) ) ); ?></small></span><em><?php echo cvc_ci_tier_pill( (string) $r['tier'], (string) ( $r['tier_label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></em></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<p class="ci-note"><?php echo esc_html( (string) ( $res['data']['data']['note'] ?? '' ) ); ?></p>
			</div>
		</section>
	</div>
	<?php
}

/* ------------------------------------------------------------------
 * Tài nguyên
 * ------------------------------------------------------------------ */
add_action( 'wp_enqueue_scripts', 'cvc_ci_assets', 60 );

function cvc_ci_assets(): void {
	$page = (string) get_query_var( 'cvc_page' );
	if ( ! in_array( $page, array( 'recruitments', 'recruitment-detail', 'recruitment-position', 'exam-detail' ), true ) ) {
		return;
	}
	wp_enqueue_style( 'cvc-career', get_theme_file_uri( '/assets/css/career.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/career.css' ) ) );
	if ( 'exam-detail' === $page ) {
		return;
	}
	wp_enqueue_script( 'cvc-career', get_theme_file_uri( '/assets/js/career.js' ), array(), (string) filemtime( get_theme_file_path( '/assets/js/career.js' ) ), true );
	wp_add_inline_script(
		'cvc-career',
		'window.CVC_CI=' . wp_json_encode(
			array(
				'ajax'     => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'cvc_ci' ),
				'loggedIn' => null !== cvc_auth_token(),
				'login'    => cvc_login_url( home_url( '/tuyen-dung/' ) ),
				'base'     => home_url( '/tuyen-dung/' ),
			)
		) . ';',
		'before'
	);
}

/* ------------------------------------------------------------------
 * AJAX: đọc (render HTML) - công khai
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_ci_view', 'cvc_ci_ajax_view' );
add_action( 'wp_ajax_nopriv_cvc_ci_view', 'cvc_ci_ajax_view' );

function cvc_ci_ajax_view(): void {
	$view      = sanitize_key( wp_unslash( $_REQUEST['view'] ?? '' ) );
	$f         = cvc_ci_filters_from_request( $_REQUEST );
	$page      = max( 1, min( 200, absint( $_REQUEST['page'] ?? 1 ) ) );
	$token     = cvc_auth_token();
	$logged_in = null !== $token;
	ob_start();
	$meta = array();
	switch ( $view ) {
		case 'jobs':
			$res = cvc_ci_api( '/api/career/recruitments', cvc_ci_jobs_query( $f, $page ), $token, 15, 120 );
			cvc_ci_render_jobs( $res, $f, $logged_in );
			$meta['url'] = cvc_ci_url( cvc_ci_url_args( $f ), $page );
			break;
		case 'exams':
			$res = cvc_ci_api( '/api/career/exams', cvc_ci_exams_query( $f, $page ), $token, 15, 120 );
			cvc_ci_render_exams( $res, $f, $logged_in );
			break;
		case 'resources':
			$res = cvc_ci_api( '/api/career/resources', cvc_ci_resources_query( $f, $page ), $token, 15, 300 );
			cvc_ci_render_resources( $res, $f );
			break;
		case 'related':
			$id  = absint( $_REQUEST['id'] ?? 0 );
			$res = cvc_ci_api( '/api/career/recruitments/' . $id . '/related', array(), $token, 15, 300 );
			cvc_ci_render_related( $res, $logged_in );
			break;
		case 'path':
		case 'personal':
		case 'reminders':
			$me = null;
			if ( $logged_in ) {
				$r  = cvc_ci_api( '/api/me/career', array(), $token, 15 );
				$me = $r['ok'] ? (array) ( $r['data']['data'] ?? array() ) : null;
			}
			if ( 'path' === $view ) {
				cvc_ci_render_path( $me, $logged_in );
			} elseif ( 'personal' === $view ) {
				cvc_ci_render_personal( $me, $logged_in );
			} else {
				cvc_ci_render_reminders( $me, $logged_in );
			}
			break;
		default:
			ob_end_clean();
			wp_send_json_error( array( 'message' => 'Không hợp lệ.' ), 400 );
	}
	$html = (string) ob_get_clean();
	nocache_headers();
	wp_send_json_success( array( 'html' => $html ) + $meta );
}

/* ------------------------------------------------------------------
 * AJAX: ghi - cần đăng nhập + nonce
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_ci_act', 'cvc_ci_ajax_act' );
add_action( 'wp_ajax_nopriv_cvc_ci_act', 'cvc_ci_ajax_act' );

function cvc_ci_ajax_act(): void {
	check_ajax_referer( 'cvc_ci', 'nonce' );
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Vui lòng đăng nhập để thực hiện thao tác này.', 'login' => true ), 401 );
	}
	$act    = sanitize_key( wp_unslash( $_POST['act'] ?? '' ) );
	$client = new CVC_Api_Client( null, 30 );
	$b      = static fn ( string $k ) => isset( $_POST[ $k ] ) && in_array( (string) $_POST[ $k ], array( '1', 'true', 'on' ), true );
	switch ( $act ) {
		case 'goal':
			// Chi gui truong co trong form (trang tin chi gui recruitment_id -> giu nguyen cac truong khac).
			$body = array();
			foreach ( array( 'job_family', 'employment_type' ) as $k ) {
				if ( isset( $_POST[ $k ] ) ) {
					$body[ $k ] = sanitize_key( wp_unslash( $_POST[ $k ] ) ) ?: null;
				}
			}
			foreach ( array( 'province_id', 'recruitment_id' ) as $k ) {
				if ( isset( $_POST[ $k ] ) ) {
					$body[ $k ] = absint( $_POST[ $k ] ) ?: null;
				}
			}
			if ( isset( $_POST['target_date'] ) ) {
				$body['target_date'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['target_date'] ) ? (string) $_POST['target_date'] : null;
			}
			$res  = $client->put( '/api/me/career/goal', $body, $token );
			break;
		case 'save':
			$res = $client->post(
				'/api/me/career/save',
				array(
					'type'  => 'exam' === ( $_POST['type'] ?? '' ) ? 'exam' : 'recruitment',
					'id'    => absint( $_POST['id'] ?? 0 ),
					'saved' => $b( 'saved' ),
				),
				$token
			);
			break;
		case 'reminder':
			$res = $client->post( '/api/me/career/reminders', array( 'recruitment_id' => absint( $_POST['id'] ?? 0 ), 'enabled' => $b( 'enabled' ) ), $token );
			break;
		case 'prefs':
			$channel = sanitize_key( wp_unslash( $_POST['channel'] ?? 'email' ) );
			$res     = $client->put(
				'/api/me/career/preferences',
				array(
					'exam_reminders'     => $b( 'exam_reminders' ),
					'learning_reminders' => $b( 'learning_reminders' ),
					'recruitment_alerts' => $b( 'recruitment_alerts' ),
					'channel'            => in_array( $channel, array( 'email', 'in_app' ), true ) ? $channel : 'email',
				),
				$token
			);
			break;
		case 'path':
			$res = $client->post( '/api/me/career/path', array(), $token );
			break;
		case 'item':
			$status = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
			$res    = $client->post( '/api/me/career/path/items/' . absint( $_POST['id'] ?? 0 ), array( 'status' => in_array( $status, array( 'pending', 'in_progress', 'completed', 'skipped' ), true ) ? $status : 'pending' ), $token );
			break;
		case 'practice':
			$res = $client->post( '/api/me/career/practice', array( 'kind' => sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) ) ), $token );
			$id  = (int) ( $res['data']['data']['id'] ?? 0 );
			if ( $res['ok'] && $id > 0 ) {
				wp_send_json_success( array( 'url' => cvc_exam_attempt_url( $id ) ) );
			}
			break;
		default:
			wp_send_json_error( array( 'message' => 'Thao tác không hợp lệ.' ), 400 );
	}
	if ( ! $res['ok'] ) {
		$msg = (string) ( $res['data']['message'] ?? '' );
		wp_send_json_error( array( 'message' => '' !== $msg && 422 === (int) $res['status'] ? $msg : cvc_api_error_message( $res ) ), (int) ( $res['status'] ?? 0 ) ?: 500 );
	}
	wp_send_json_success( array( 'message' => (string) ( $res['data']['message'] ?? 'Đã lưu.' ), 'data' => $res['data']['data'] ?? null ) );
}

/** Trang chi tiết tin: "Hồ sơ chinh phục" - lưu / nhắc hạn / đặt mục tiêu + đề & văn bản theo mức xác nhận. */
function cvc_ci_render_detail_block( int $recruitment_id ): void {
	if ( $recruitment_id <= 0 ) {
		return;
	}
	$token     = cvc_auth_token();
	$logged_in = null !== $token;
	$res       = cvc_ci_api( '/api/career/recruitments/' . $recruitment_id . '/related', array(), $token, 12, 600 );
	if ( ! $res['ok'] ) {
		return;
	}
	$rec = (array) ( $res['data']['data']['recruitment'] ?? array() );
	$st  = (array) ( $rec['status'] ?? array() );
	?>
	<section id="lien-quan" class="ci ci-inline">
		<div class="ci-panel">
			<div class="ci-panel-head"><h2>Hồ sơ chinh phục: đề thi &amp; văn bản liên quan</h2><?php echo cvc_ci_status_pill( $st ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="ci-panel-body">
				<div class="ci-actions" style="margin-bottom:12px">
					<?php if ( $logged_in ) : ?>
						<button type="button" class="ci-btn<?php echo ! empty( $rec['saved'] ) ? ' is-teal' : ''; ?>" data-ci="save" data-type="recruitment" data-id="<?php echo (int) $recruitment_id; ?>" aria-pressed="<?php echo ! empty( $rec['saved'] ) ? 'true' : 'false'; ?>"><?php echo ! empty( $rec['saved'] ) ? '♥ Đã lưu' : '♡ Lưu cơ hội'; ?></button>
						<?php if ( ! empty( $st['is_open'] ) && ! empty( $st['deadline_verified'] ) ) : ?>
							<button type="button" class="ci-btn<?php echo ! empty( $rec['reminder'] ) ? ' is-teal' : ''; ?>" data-ci="reminder" data-id="<?php echo (int) $recruitment_id; ?>" aria-pressed="<?php echo ! empty( $rec['reminder'] ) ? 'true' : 'false'; ?>"><?php echo ! empty( $rec['reminder'] ) ? '◷ Đang nhắc hạn' : '◷ Nhắc hạn nộp hồ sơ'; ?></button>
						<?php endif; ?>
						<?php echo cvc_cq_go_button( 'recruitment', (int) $recruitment_id, '◎ Chinh phục kỳ thi này', 'ci-btn is-primary', home_url( add_query_arg( null, null ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php else : ?>
						<a class="ci-btn is-primary" href="<?php echo esc_url( cvc_ci_login_url() ); ?>" rel="nofollow">Đăng nhập để chinh phục kỳ thi này</a>
					<?php endif; ?>
					<a class="ci-btn" href="<?php echo esc_url( cvc_ci_url( array( 'nhom' => (string) ( $rec['job_family'] ?? '' ) ) ) ); ?>">Tin cùng nhóm vị trí →</a>
				</div>
				<?php cvc_ci_render_related( $res, $logged_in, true ); ?>
			</div>
		</div>
		<div class="ci-toast" id="ci-toast" role="status" aria-live="polite"></div>
	</section>
	<?php
}
