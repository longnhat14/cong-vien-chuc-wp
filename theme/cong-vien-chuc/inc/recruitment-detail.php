<?php
/**
 * Trang chi tiết tuyển dụng v4 (18/10/2026) - thiết kế congvienchuc-chi-tiet-tuyen-dung-v4.
 *
 *  - /tuyen-dung/{slug}/            trang thông báo (tổng) - template-recruitment-detail.php
 *  - /tuyen-dung/vi-tri/{slug}/     trang cơ hội của TỪNG vị trí (cùng template, chế độ vị trí) - SEO + lọc theo vị trí
 *  - /tuyen-dung/vi-tri/            danh sách cơ hội theo vị trí (template-recruitment-positions.php)
 *  - Điểm xác minh: chỉ hiện điểm / mức / nhãn từ API (phân rã tiêu chí chỉ ở backend quản trị).
 *  - AJAX công khai `cvc_rd_search`: tìm trong văn bản trích xuất của tệp gốc (API recruitments/{slug}/text-search).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_position_url( string $slug ): string {
	return home_url( '/tuyen-dung/vi-tri/' . rawurlencode( $slug ) . '/' );
}

function cvc_positions_url( int $paged = 1 ): string {
	return home_url( '/tuyen-dung/vi-tri/' . ( $paged > 1 ? 'page/' . $paged . '/' : '' ) );
}

function cvc_rd_is_page(): bool {
	return in_array( (string) get_query_var( 'cvc_page' ), array( 'recruitment-detail', 'recruitment-position', 'recruitment-positions' ), true );
}

add_action( 'wp_enqueue_scripts', 'cvc_rd_assets', 55 );

function cvc_rd_assets(): void {
	if ( ! cvc_rd_is_page() ) {
		return;
	}
	$css = get_theme_file_path( '/assets/css/recruitment-detail.css' );
	$js  = get_theme_file_path( '/assets/js/recruitment-detail.js' );
	wp_enqueue_style( 'cvc-rd', get_theme_file_uri( '/assets/css/recruitment-detail.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-rd', get_theme_file_uri( '/assets/js/recruitment-detail.js' ), array(), (string) filemtime( $js ), true );
	wp_add_inline_script( 'cvc-rd', 'window.CVC_RD=' . wp_json_encode( array( 'ajax' => admin_url( 'admin-ajax.php' ) ) ) . ';', 'before' );
}

/** Mức điểm xác minh -> lớp màu + mô tả ngắn (không phải xác suất pháp lý). */
function cvc_rd_verify_view( ?array $v ): array {
	if ( ! $v ) {
		return array( 'class' => 'is-low', 'score' => null, 'label' => 'Chưa tính điểm xác minh', 'copy' => 'Hệ thống chưa đánh giá bằng chứng của thông báo này. Hãy đối chiếu văn bản gốc trước khi nộp hồ sơ.' );
	}
	$map  = array( 'high' => 'is-high', 'good' => 'is-good', 'partial' => 'is-mid', 'low' => 'is-low', 'conflict' => 'is-conflict' );
	$copy = array(
		'high'     => 'Có nguồn chính thức, tệp gốc và trích dẫn cho các dữ kiện quan trọng; đã được người duyệt đối chiếu. Vẫn cần đọc điều kiện trong văn bản gốc.',
		'good'     => 'Phần lớn dữ kiện có nguồn và trích dẫn; một số mục còn thiếu bằng chứng hoặc chưa được người duyệt đối chiếu.',
		'partial'  => 'Mới có một phần bằng chứng. Đối chiếu kỹ hạn nộp, chỉ tiêu và điều kiện với văn bản của cơ quan tuyển dụng.',
		'low'      => 'Chưa đủ bằng chứng để dựa vào khi quyết định nộp hồ sơ — hãy liên hệ cơ quan tuyển dụng để xác nhận.',
		'conflict' => 'Các nguồn đang có dữ kiện mâu thuẫn (ngày hoặc chỉ tiêu) chưa được xử lý. Không dựa vào số liệu trên trang cho tới khi có xác nhận.',
	);

	return array( 'class' => $map[ $v['level'] ] ?? 'is-low', 'score' => (int) $v['score'], 'label' => (string) $v['label'], 'copy' => $copy[ $v['level'] ] ?? '' );
}

/**
 * Slug từ URL: chỉ a-z 0-9 '-' (không dùng sanitize_title() vì nó cắt chuỗi còn 200 ký tự -> slug vị trí dài bị 404).
 */
function cvc_rd_slug( string $raw ): string {
	return strtolower( (string) preg_replace( '/[^A-Za-z0-9\-]/', '', rawurldecode( $raw ) ) );
}

/**
 * Vị trí cụ thể (không phải vị trí chung "Các vị trí việc làm theo thông báo", "Công chức"...) - chỉ vị trí
 * cụ thể mới có JobPosting / trang riêng trên Google (19/10/2026).
 *
 * @return array<int, array>
 */
function cvc_rd_specific_positions( array $positions ): array {
	return array_values( array_filter( $positions, static fn ( $p ) => is_array( $p ) && empty( $p['is_generic'] ) && ! empty( $p['name'] ) ) );
}

/** JobPosting cho TRANG VỊ TRÍ (mỗi trang 1 việc làm - đúng khuyến nghị dữ liệu có cấu trúc). */
function cvc_rd_position_jsonld( array $rec, array $pos, string $url ): ?array {
	$agency = is_array( $rec['agency'] ?? null ) ? $rec['agency'] : null;
	if ( ! $agency || empty( $agency['name'] ) || empty( $pos['name'] ) ) {
		return null;
	}
	$dates = (array) ( $rec['dates'] ?? array() );
	$desc  = array_filter(
		array(
			'Vị trí ' . $pos['name'] . ' - ' . $agency['name'] . '.',
			! empty( $pos['quantity'] ) ? 'Chỉ tiêu: ' . (int) $pos['quantity'] . '.' : '',
			! empty( $pos['education_level'] ) ? 'Trình độ: ' . $pos['education_level'] . '.' : '',
			! empty( $pos['major_requirements'] ) ? 'Chuyên ngành: ' . $pos['major_requirements'] . '.' : '',
			! empty( $pos['job_description'] ) ? (string) $pos['job_description'] : '',
			! empty( $pos['requirements'] ) ? 'Yêu cầu: ' . $pos['requirements'] : '',
			(string) ( $rec['summary'] ?? '' ),
		)
	);
	$s = array(
		'@context'           => 'https://schema.org',
		'@type'              => 'JobPosting',
		'title'              => (string) $pos['name'],
		'description'        => implode( ' ', $desc ),
		'url'                => $url,
		'hiringOrganization' => array( '@type' => 'Organization', 'name' => (string) $agency['name'] ),
		'employmentType'     => (string) ( $pos['employment_type_schema'] ?? 'FULL_TIME' ),
	);
	if ( ! empty( $pos['quantity'] ) ) {
		$s['totalJobOpenings'] = (int) $pos['quantity'];
	}
	// datePosted là trường bắt buộc: ngày ban hành, không có thì ngày đăng trên hệ thống.
	$posted = (string) ( $dates['announcement_date'] ?? '' ) ?: (string) ( $dates['published_at'] ?? '' );
	if ( '' !== $posted ) {
		$s['datePosted'] = $posted;
	}
	if ( ! empty( $dates['application_deadline'] ) ) {
		$s['validThrough'] = (string) $dates['application_deadline'] . 'T23:59:59+07:00';
	}
	if ( ! empty( $pos['code'] ) ) {
		$s['identifier'] = array( '@type' => 'PropertyValue', 'name' => (string) $agency['name'], 'value' => (string) $pos['code'] );
	}
	$prov = (string) ( $rec['province']['name'] ?? '' );
	if ( '' !== $prov ) {
		$s['jobLocation'] = array(
			'@type'   => 'Place',
			'address' => array_filter(
				array(
					'@type'           => 'PostalAddress',
					'addressLocality' => (string) ( $rec['admin_unit']['name'] ?? '' ),
					'addressRegion'   => $prov,
					'addressCountry'  => 'VN',
				)
			),
		);
	}

	return $s;
}

/* ------------------------------------------------------------------
 * AJAX: tìm trong văn bản trích xuất (công khai, chỉ đọc)
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_rd_search', 'cvc_rd_ajax_search' );
add_action( 'wp_ajax_nopriv_cvc_rd_search', 'cvc_rd_ajax_search' );

function cvc_rd_ajax_search(): void {
	$slug = sanitize_title( wp_unslash( $_GET['slug'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$q    = mb_substr( sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ), 0, 80 ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $slug || mb_strlen( $q ) < 2 ) {
		wp_send_json_error( array( 'message' => 'Nhập ít nhất 2 ký tự.' ), 400 );
	}
	$res = ( new CVC_Api_Client( null, 15 ) )->get( '/api/recruitments/' . rawurlencode( $slug ) . '/text-search', array( 'q' => $q ) );
	if ( ! $res['ok'] ) {
		wp_send_json_error( array( 'message' => 'Không tìm được lúc này, vui lòng thử lại.' ), (int) ( $res['status'] ?? 500 ) ?: 500 );
	}
	$d = (array) ( $res['data']['data'] ?? array() );
	ob_start();
	if ( 0 === (int) ( $d['sources'] ?? 0 ) ) {
		echo '<p class="rd-note">Văn bản của thông báo này chưa có nội dung trích xuất để tìm (tệp là bản chụp). Hãy mở tệp gốc để đọc.</p>';
	} elseif ( empty( $d['hits'] ) ) {
		echo '<p class="rd-note">Không thấy “' . esc_html( $q ) . '” trong ' . (int) $d['sources'] . ' nguồn văn bản đã trích xuất.</p>';
	} else {
		echo '<p class="rd-result">Tìm thấy <b>' . (int) $d['count'] . '</b> kết quả cho “' . esc_html( $q ) . '”' . ( (int) $d['count'] > count( $d['hits'] ) ? ' — hiện ' . count( $d['hits'] ) . ' đoạn đầu' : '' ) . '.</p><ol class="rd-hits">';
		foreach ( (array) $d['hits'] as $h ) {
			$dl = ! empty( $h['download_url'] ) ? (string) $h['download_url'] . ( 'pdf' === ( $h['extension'] ?? '' ) ? '?inline=1' : '' ) : '';
			echo '<li><p>' . esc_html( (string) $h['before'] ) . '<mark>' . esc_html( (string) $h['match'] ) . '</mark>' . esc_html( (string) $h['after'] ) . '</p><small>Nguồn: '
				. ( '' !== $dl ? '<a href="' . esc_url( $dl ) . '" target="_blank" rel="noopener nofollow">' . esc_html( (string) $h['source'] ) . ' ↗</a>' : esc_html( (string) $h['source'] ) ) . '</small></li>';
		}
		echo '</ol><p class="rd-note">Văn bản trích xuất tự động từ tệp gốc, có thể sai chính tả hoặc mất định dạng bảng — đối chiếu tệp gốc khi dùng.</p>';
	}
	wp_send_json_success( array( 'html' => (string) ob_get_clean() ) );
}

/* ------------------------------------------------------------------
 * Hàm dựng dùng chung cho template chi tiết / vị trí / danh sách vị trí
 * ------------------------------------------------------------------ */

/** Trang trạng thái lỗi (404 / 503) giữ header/footer chung của site. */
function cvc_rd_render_state( int $status, string $title, string $message ): void {
	status_header( $status );
	if ( 503 === $status ) {
		header( 'Retry-After: 60' );
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'cvc: API tạm thời lỗi' );
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
	cvc_seo_set_noindex();
	cvc_seo_set_title( $title );
	get_header();
	?>
	<main id="main" class="rd">
		<div class="rd-wrap rd-state">
			<p class="rd-eyebrow"><?php echo 503 === $status ? 'Tạm thời gián đoạn' : 'Không tìm thấy'; ?></p>
			<h1><?php echo esc_html( $title ); ?></h1>
			<p><?php echo esc_html( $message ); ?></p>
			<p class="rd-actions">
				<?php if ( 503 === $status ) : ?><a class="rd-btn is-primary" href="<?php echo esc_url( (string) home_url( add_query_arg( array() ) ) ); ?>">Tải lại trang</a><?php endif; ?>
				<a class="rd-btn" href="<?php echo esc_url( cvc_positions_url() ); ?>">Tìm theo vị trí</a>
				<a class="rd-btn" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tất cả tin tuyển dụng</a>
			</p>
		</div>
	</main>
	<?php
	get_footer();
}

/** Dòng văn bản có nhắc tới vị trí (theo tên, hoặc mã vị trí dạng VT01). */
function cvc_rd_mentions( string $line, array $pos ): bool {
	$hay  = mb_strtolower( preg_replace( '/\s+/u', ' ', $line ) ?? '', 'UTF-8' );
	$name = mb_strtolower( trim( (string) ( $pos['name'] ?? '' ) ), 'UTF-8' );
	if ( mb_strlen( $name ) >= 6 && false !== mb_strpos( $hay, $name ) ) {
		return true;
	}
	$code = trim( (string) ( $pos['code'] ?? '' ) );

	return mb_strlen( $code ) >= 3 && 1 === preg_match( '/(?<![\p{L}\d])' . preg_quote( mb_strtolower( $code, 'UTF-8' ), '/' ) . '(?![\p{L}\d])/u', $hay );
}

/** Số ngày còn lại tới hết hạn (giờ Việt Nam); null nếu không có ngày. */
function cvc_rd_days_left( string $date ): ?int {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return null;
	}
	$tz    = new DateTimeZone( 'Asia/Ho_Chi_Minh' );
	$today = new DateTimeImmutable( 'today', $tz );
	$end   = new DateTimeImmutable( $date, $tz );

	return (int) $today->diff( $end )->format( '%r%a' );
}

/** Cảnh báo công khai của điểm xác minh -> câu cho người đọc. */
function cvc_rd_warning_text( string $key ): string {
	$map = array(
		'missing_deadline'    => 'Chưa xác định được hạn nộp hồ sơ từ văn bản.',
		'deadline_unverified' => 'Hạn nộp chưa có trích dẫn từ văn bản gốc — đối chiếu trước khi nộp.',
		'secondary_source'    => 'Tin lấy từ nguồn thứ cấp, chưa thấy trên cổng thông tin của cơ quan.',
		'no_original_file'    => 'Chưa lưu được tệp văn bản gốc kèm thông báo.',
		'stale_check'         => 'Nguồn chưa được kiểm tra lại gần đây.',
	);

	return $map[ $key ] ?? '';
}

/** Nhãn định dạng tệp. */
function cvc_rd_ext( string $ext ): array {
	$ext = strtolower( $ext );
	$cls = in_array( $ext, array( 'pdf' ), true ) ? 'is-pdf' : ( in_array( $ext, array( 'doc', 'docx', 'odt', 'rtf' ), true ) ? 'is-doc' : ( in_array( $ext, array( 'xls', 'xlsx', 'ods', 'csv' ), true ) ? 'is-xls' : 'is-other' ) );

	return array( 'label' => '' !== $ext ? strtoupper( $ext ) : 'TỆP', 'class' => $cls );
}

/** Nhóm hiển thị của 1 vị trí: cấp (biểu chỉ tiêu) hoặc loại tuyển dụng. */
function cvc_rd_pos_group( array $p ): string {
	$lv = trim( (string) ( $p['level'] ?? '' ) );

	return '' !== $lv ? $lv : trim( (string) ( $p['employment_type_label'] ?? '' ) );
}

/** Rút gọn chữ theo ký tự (không cắt giữa từ). */
function cvc_rd_clip( string $text, int $len ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) ?? '' );
	if ( mb_strlen( $text ) <= $len ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $len );
	$sp  = mb_strrpos( $cut, ' ' );

	return rtrim( false !== $sp && $sp > $len * 0.6 ? mb_substr( $cut, 0, $sp ) : $cut, ' ,.;:' ) . '…';
}

/* ------------------------------------------------------------------
 * 18/10/2026: số người đã xem + tin tuyển dụng gần bạn (theo IP / tỉnh tự chọn).
 * Trang được lưu đệm (LiteSpeed) nên phần này nạp bằng AJAX công khai `cvc_rd_ctx`.
 * WordPress gửi kèm IP thật của người xem cho API (X-CVC-Client-IP); API không lưu IP.
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_rd_ctx', 'cvc_rd_ajax_ctx' );
add_action( 'wp_ajax_nopriv_cvc_rd_ctx', 'cvc_rd_ajax_ctx' );

function cvc_rd_ajax_ctx(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$id   = absint( $_POST['id'] ?? 0 );
	$vid  = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) wp_unslash( $_POST['vid'] ?? '' ) );
	$tinh = sanitize_title( wp_unslash( (string) ( $_POST['tinh'] ?? '' ) ) );
	$view = ! empty( $_POST['view'] );
	// phpcs:enable
	nocache_headers();
	$out = array( 'viewers' => null, 'near_html' => '' );
	$api = new CVC_Api_Client( null, 8 );
	if ( $id > 0 && $view ) {
		$r = $api->post( '/api/recruitments/' . $id . '/view', array_filter( array( 'vid' => strlen( $vid ) >= 8 ? substr( $vid, 0, 64 ) : null ) ) );
		if ( $r['ok'] ) {
			$out['viewers'] = (int) ( $r['data']['data']['viewers'] ?? 0 );
		}
	}
	$n = $api->get( '/api/recruitments/nearby', array_filter( array( 'province' => $tinh, 'exclude' => $id ?: null, 'limit' => 5 ) ) );
	if ( $n['ok'] ) {
		$out['near_html'] = cvc_rd_near_html( (array) ( $n['data']['data'] ?? array() ), $id > 0 );
	}
	wp_send_json_success( $out );
}

function cvc_rd_near_html( array $d, bool $detail ): string {
	$prov  = is_array( $d['province'] ?? null ) ? $d['province'] : null;
	$items = (array) ( $d['items'] ?? array() );
	$opts  = '<option value="">— Chọn tỉnh / thành —</option>';
	foreach ( (array) ( $d['provinces'] ?? array() ) as $p ) {
		$opts .= '<option value="' . esc_attr( (string) $p['slug'] ) . '"' . selected( $prov['slug'] ?? '', (string) $p['slug'], false ) . '>' . esc_html( (string) $p['name'] ) . '</option>';
	}
	$select = '<label class="rd-near-pick"><span>' . ( $prov ? 'Không đúng nơi bạn ở?' : 'Chọn nơi bạn muốn làm việc:' ) . '</span><select data-rd-tinh aria-label="Chọn tỉnh / thành">' . $opts . '</select></label>';
	ob_start();
	if ( ! $prov ) {
		echo '<p class="rd-small rd-muted">Chưa xác định được tỉnh/thành của bạn từ kết nối mạng.</p>' . $select; // phpcs:ignore WordPress.Security.EscapeOutput
	} else {
		$src = 'chosen' === ( $d['source'] ?? '' ) ? 'bạn đã chọn' : 'ước tính theo kết nối mạng';
		echo '<p class="rd-near-where"><b>' . esc_html( (string) $prov['name'] ) . '</b><small>' . esc_html( $src ) . '</small></p>';
		if ( ! $items ) {
			echo '<p class="rd-small rd-muted">Chưa có tin ' . ( $detail ? 'khác ' : '' ) . 'đang nhận hồ sơ tại ' . esc_html( (string) $prov['name'] ) . '.</p>';
		} else {
			echo '<ul class="rd-near-list">';
			foreach ( $items as $it ) {
				$st   = (array) ( $it['status'] ?? array() );
				$meta = array_filter( array( (string) ( $it['agency'] ?? '' ), (string) ( $st['label'] ?? '' ), ! empty( $it['viewers'] ) ? (int) $it['viewers'] . ' người đã xem' : '' ) );
				echo '<li><a href="' . esc_url( cvc_recruitment_url( (string) $it['slug'] ) ) . '">' . esc_html( cvc_rd_clip( (string) $it['title'], 95 ) ) . '</a><small>' . esc_html( implode( ' · ', $meta ) ) . '</small></li>';
			}
			echo '</ul>';
			echo '<p class="rd-near-more"><a href="' . esc_url( add_query_arg( array( 'tinh' => $prov['slug'], 'con-han' => 1 ), cvc_positions_url() ) ) . '">Xem vị trí đang tuyển tại ' . esc_html( (string) $prov['name'] ) . ' (' . (int) ( $d['total'] ?? 0 ) . ' thông báo) →</a></p>';
		}
		echo $select; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '<p class="rd-near-note">Vị trí ước tính từ IP bằng dữ liệu <a href="https://db-ip.com" target="_blank" rel="noopener nofollow">DB-IP</a>; hệ thống không lưu địa chỉ IP của bạn.</p>';

	return (string) ob_get_clean();
}

/**
 * Đề trắc nghiệm cá nhân hóa theo tin (20/10/2026): nhóm vị trí → đề RP + văn bản dùng để dựng đề.
 * Trang vị trí: đưa nhóm chứa vị trí đang xem lên đầu.
 *
 * @param array<int, array<string, mixed>> $groups
 */
function cvc_rd_render_personal_exams( array $groups, string $focus_position = '', string $track_label = '', array $pricing = array(), int $recruitment_id = 0 ): void {
	if ( empty( $groups ) ) {
		return;
	}
	// Tên vị trí trong API bị cắt ~120 ký tự -> so khớp theo 100 ký tự đầu.
	$head = mb_substr( trim( $focus_position ), 0, 100 );
	$has  = static function ( array $g ) use ( $head ): bool {
		if ( '' === $head ) {
			return false;
		}
		foreach ( (array) ( $g['positions'] ?? array() ) as $p ) {
			if ( 0 === strpos( (string) $p, $head ) ) {
				return true;
			}
		}
		return false;
	};
	if ( '' !== $head ) {
		usort( $groups, static fn ( $a, $b ) => ( $has( $a ) ? 0 : 1 ) <=> ( $has( $b ) ? 0 : 1 ) );
	}
	$total = array_sum( array_map( static fn ( $g ) => count( (array) ( $g['exams'] ?? array() ) ), $groups ) );
	?>
	<section class="rd-card" id="de-rieng" aria-labelledby="rd-pe-h">
		<div class="rd-card-head"><h2 id="rd-pe-h"><span class="rd-ico" aria-hidden="true">✎</span>Đề thi trắc nghiệm dành riêng cho tin này</h2></div>
		<div class="rd-card-body">
			<?php if ( ! empty( $pricing['active'] ) ) : ?>
				<p class="rd-pe-price"><b>Làm thử <?php echo (int) ( $pricing['preview'] ?? 10 ); ?> câu đầu mỗi đề miễn phí</b> · Bộ đề của nhóm vị trí <?php echo esc_html( number_format( (float) ( $pricing['prices']['pack'] ?? 0 ), 0, ',', '.' ) ); ?> ₫ · Gói <?php echo (int) ( $pricing['pass_days'] ?? 30 ); ?> ngày <?php echo esc_html( number_format( (float) ( $pricing['prices']['pass'] ?? 0 ), 0, ',', '.' ) ); ?> ₫ mở mọi đề cá nhân hóa</p>
			<?php endif; ?>
			<p class="rd-muted"><?php echo esc_html( $total . ' đề luyện tập mô phỏng vòng 1' . ( '' !== $track_label ? ' thi tuyển ' . $track_label : '' ) . ', Công Viên Chức biên soạn từ căn cứ của thông báo và yêu cầu vị trí. Mỗi câu dẫn chiếu Điều cụ thể; đây không phải đề thi chính thức của Hội đồng tuyển dụng.' ); ?></p>
			<?php foreach ( $groups as $gi => $g ) : ?>
				<?php
				$exams     = (array) ( $g['exams'] ?? array() );
				$label     = (string) ( $g['label'] ?? '' );
				$is_focus  = $has( $g );
				$sections  = (array) ( $g['sections'] ?? array() );
				$positions = (array) ( $g['positions'] ?? array() );
				?>
				<div class="rd-block rd-pe-group">
					<?php if ( '' !== $label || count( $groups ) > 1 ) : ?>
						<h3><?php echo esc_html( '' !== $label ? $label : 'Đề chung của tin' ); ?><?php if ( $is_focus ) : ?> <span class="rd-tag is-ok">Phù hợp vị trí đang xem</span><?php endif; ?></h3>
						<?php if ( $positions ) : ?>
							<p class="rd-muted rd-pe-pos"><?php echo esc_html( 'Áp dụng cho: ' . implode( '; ', array_slice( $positions, 0, 4 ) ) . ( count( $positions ) > 4 ? ' và ' . ( count( $positions ) - 4 ) . ' vị trí khác' : '' ) ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( $recruitment_id > 0 && $exams && function_exists( 'cvc_pdf_button' ) ) : ?>
						<p class="rd-pe-pdf"><?php echo cvc_pdf_button( 'recruitment', $recruitment_id, array( 'group' => (string) ( $g['group'] ?? '' ), 'label' => 'Tải trọn bộ ' . count( $exams ) . ' đề PDF (có đáp án)', 'variant' => 'dap-an' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <small class="rd-muted">Xem trước miễn phí · logo Công Viên Chức · mã QR làm bài online</small></p>
					<?php endif; ?>
					<div class="rd-linkgrid">
						<?php foreach ( $exams as $e ) : ?>
							<a href="<?php echo esc_url( cvc_exam_url( (string) ( $e['slug'] ?? '' ) ) ); ?>"><b><?php echo esc_html( (string) ( $e['title'] ?? '' ) ); ?></b>
								<small><?php echo esc_html( (int) ( $e['total_questions'] ?? 0 ) . ' câu · ' . (int) ( $e['duration_minutes'] ?? 0 ) . ' phút' ); ?></small></a>
						<?php endforeach; ?>
					</div>
					<?php if ( $sections ) : ?>
						<details class="rd-doctext rd-pe-docs">
							<summary>Văn bản dùng để dựng đề <small>(tỷ trọng câu hỏi)</small></summary>
							<?php foreach ( $sections as $s ) : ?>
								<h4><?php echo esc_html( (string) ( $s['label'] ?? '' ) . ( ! empty( $s['weight'] ) ? ' · ~' . (int) $s['weight'] . '%' : '' ) ); ?></h4>
								<p class="rd-tags">
									<?php foreach ( (array) ( $s['documents'] ?? array() ) as $d ) : ?>
										<?php $dl = trim( (string) ( $d['number'] ?? '' ) ); ?>
										<?php if ( ! empty( $d['slug'] ) ) : ?>
											<a class="rd-tag is-link" href="<?php echo esc_url( cvc_legal_document_url( (string) $d['slug'] ) ); ?>" title="<?php echo esc_attr( (string) ( $d['title'] ?? '' ) ); ?>"><?php echo esc_html( $dl ); ?></a>
										<?php else : ?>
											<span class="rd-tag" title="<?php echo esc_attr( (string) ( $d['title'] ?? '' ) ); ?>"><?php echo esc_html( $dl ); ?></span>
										<?php endif; ?>
									<?php endforeach; ?>
								</p>
							<?php endforeach; ?>
						</details>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}
