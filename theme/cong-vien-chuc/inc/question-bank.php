<?php
/**
 * Ngân hàng câu hỏi (/ngan-hang-cau-hoi/, thiết kế v1 19/10/2026) và Câu hỏi đã lưu (/cau-hoi-da-luu/).
 *
 *  - Danh sách câu thật từ GET /api/question-bank/questions (tìm kiếm, lọc, sắp xếp, phân trang phía máy chủ);
 *    nhóm Công chức / Viên chức (question_tracks), chế độ cá nhân Chưa làm / Đã sai / Đã đúng / Đã lưu.
 *  - Luyện nhanh 1 câu ngay trên thẻ (AJAX cvc_qb_q + cvc_qb_check -> đáp án, giải thích, căn cứ; ghi tiến độ).
 *  - Lưu câu (AJAX cvc_qb_bm -> /api/bookmarks type question).
 *  - Ôn tập / Thi thử nhóm câu đang lọc (admin-post cvc_qb_start -> POST /api/question-bank/practice).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_QB_COUNTS = array( 10, 20, 40, 60 );
/** Nguồn câu cho bài luyện (giá trị API). */
const CVC_QB_SOURCES = array(
	'all'     => 'Tất cả câu',
	'new'     => 'Câu chưa làm',
	'wrong'   => 'Câu từng làm sai',
	'correct' => 'Câu đã làm đúng',
	'saved'   => 'Câu đã lưu',
	'flagged' => 'Câu đã đánh dấu',
	'review'  => 'Câu cần ôn lại',
);
/** Tham số URL tiếng Việt -> giá trị API. */
const CVC_QB_TRACKS  = array( 'cong-chuc' => 'civil_servant', 'vien-chuc' => 'public_employee' );
const CVC_QB_DIFF    = array( 'de' => 'easy', 'tb' => 'medium', 'kho' => 'hard' );
const CVC_QB_FORMATS = array( 'mot-dap-an' => 'single', 'dung-sai' => 'true_false', 'dien-khuyet' => 'fill_blank', 'tinh-huong' => 'scenario' );
const CVC_QB_BASIS   = array( 'co' => 'has', 'chua' => 'none' );
const CVC_QB_MODES   = array( 'chua-lam' => 'new', 'sai' => 'wrong', 'dung' => 'correct', 'da-luu' => 'saved' );
const CVC_QB_SORTS   = array( 'phu-hop' => 'recommended', 'do-kho' => 'difficulty', 'luot-luyen' => 'popular', 'moi' => 'newest' );

add_action( 'wp_enqueue_scripts', 'cvc_qb_assets', 55 );

function cvc_qb_assets(): void {
	$page = (string) get_query_var( 'cvc_page' );
	if ( ! in_array( $page, array( 'question-bank', 'saved-questions' ), true ) ) {
		return;
	}
	$css = get_theme_file_path( '/assets/css/question-bank.css' );
	$js  = get_theme_file_path( '/assets/js/question-bank.js' );
	wp_enqueue_style( 'cvc-qb', get_theme_file_uri( '/assets/css/question-bank.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-qb', get_theme_file_uri( '/assets/js/question-bank.js' ), array(), (string) filemtime( $js ), true );
	wp_localize_script(
		'cvc-qb',
		'CVC_QB',
		array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cvc_qb' ),
			'loggedIn' => cvc_is_logged_in(),
			'login'    => cvc_login_url( cvc_verify_return_path( (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ),
			// Bắt buộc đăng nhập + xác minh email trước khi luyện câu (27/10/2026).
			'gate'     => ! cvc_is_logged_in() ? 'login' : ( cvc_email_unverified() ? 'verify' : '' ),
			'verify'   => cvc_verify_email_url( cvc_verify_return_path( (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ),
		)
	);
}

/** Bộ lọc từ query string. */
function cvc_qb_filters( array $src ): array {
	$i = static fn ( string $k ): int => isset( $src[ $k ] ) && ! is_array( $src[ $k ] ) ? absint( $src[ $k ] ) : 0;
	$s = static fn ( string $k ): string => isset( $src[ $k ] ) && ! is_array( $src[ $k ] ) ? sanitize_key( wp_unslash( (string) $src[ $k ] ) ) : '';
	$q = isset( $src['q'] ) && ! is_array( $src['q'] ) ? mb_substr( trim( sanitize_text_field( wp_unslash( (string) $src['q'] ) ) ), 0, 100 ) : '';
	// Tương thích link cũ ?nguon=new|wrong|... -> che-do.
	$mode = $s( 'che-do' );
	if ( '' === $mode && '' !== $s( 'nguon' ) ) {
		$mode = (string) array_search( $s( 'nguon' ), CVC_QB_MODES, true );
	}
	$f = array(
		'nhom'    => isset( CVC_QB_TRACKS[ $s( 'nhom' ) ] ) ? $s( 'nhom' ) : '',
		'q'       => $q,
		'mon'     => $i( 'mon' ),
		'chu-de'  => $i( 'chu-de' ),
		'van-ban' => $i( 'van-ban' ),
		'dieu'    => $i( 'dieu' ),
		'do-kho'  => isset( CVC_QB_DIFF[ $s( 'do-kho' ) ] ) ? $s( 'do-kho' ) : '',
		'loai'    => isset( CVC_QB_FORMATS[ $s( 'loai' ) ] ) ? $s( 'loai' ) : '',
		'can-cu'  => isset( CVC_QB_BASIS[ $s( 'can-cu' ) ] ) ? $s( 'can-cu' ) : '',
		'che-do'  => isset( CVC_QB_MODES[ $mode ] ) ? $mode : '',
		'sap-xep' => isset( CVC_QB_SORTS[ $s( 'sap-xep' ) ] ) ? $s( 'sap-xep' ) : '',
		'trang'   => max( 1, min( 2000, $i( 'trang' ) ) ),
		'so-cau'  => in_array( $i( 'so-cau' ), CVC_QB_COUNTS, true ) ? $i( 'so-cau' ) : 20,
	);
	if ( ! $f['van-ban'] ) {
		$f['dieu'] = 0;
	}

	return $f;
}

/** Tham số API bộ lọc (không gồm chế độ / sắp xếp / trang). */
function cvc_qb_api_filters( array $f ): array {
	return array_filter(
		array(
			'track'             => CVC_QB_TRACKS[ $f['nhom'] ?? '' ] ?? null,
			'q'                 => '' !== ( $f['q'] ?? '' ) ? $f['q'] : null,
			'subject_id'        => ( $f['mon'] ?? 0 ) ?: null,
			'topic_id'          => ( $f['chu-de'] ?? 0 ) ?: null,
			'legal_document_id' => ( $f['van-ban'] ?? 0 ) ?: null,
			'legal_article_id'  => ( $f['dieu'] ?? 0 ) ?: null,
			'difficulty'        => CVC_QB_DIFF[ $f['do-kho'] ?? '' ] ?? null,
			'format'            => CVC_QB_FORMATS[ $f['loai'] ?? '' ] ?? null,
			'basis'             => CVC_QB_BASIS[ $f['can-cu'] ?? '' ] ?? null,
		)
	);
}

/** Nguồn câu cho bài luyện từ chế độ đang xem. */
function cvc_qb_source( array $f ): string {
	return CVC_QB_MODES[ $f['che-do'] ?? '' ] ?? 'all';
}

function cvc_qb_facets( array $f ): array {
	$res = cvc_ci_api( '/api/question-bank/facets', cvc_qb_api_filters( $f ), null, 15, 900 );

	return $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
}

/** URL trang ngân hàng với bộ lọc (bỏ giá trị mặc định); $over ghi đè từng khóa. */
function cvc_qb_url( array $f, array $over = array() ): string {
	$f    = array_merge( $f, $over );
	$args = array_filter(
		$f,
		static fn ( $v, $k ) => ! ( ( 'so-cau' === $k && 20 === (int) $v ) || ( 'trang' === $k && (int) $v <= 1 ) || '' === $v || 0 === $v || null === $v ),
		ARRAY_FILTER_USE_BOTH
	);

	return cvc_question_bank_url( $args );
}

/**
 * Form bắt đầu (POST admin-post cvc_qb_start) cho nhóm câu đang lọc. $available: số câu khớp.
 */
function cvc_qb_start_form( array $f, string $back, int $available, string $class = '' ): void {
	$n   = $available > 0 ? min( (int) $f['so-cau'], $available ) : (int) $f['so-cau'];
	$min = max( 5, $n );
	$src = isset( $f['nguon'] ) && isset( CVC_QB_SOURCES[ $f['nguon'] ] ) ? (string) $f['nguon'] : cvc_qb_source( $f );
	?>
	<form class="qb-start <?php echo esc_attr( $class ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-qb-start>
		<?php wp_nonce_field( 'cvc_qb_start' ); ?>
		<input type="hidden" name="action" value="cvc_qb_start">
		<input type="hidden" name="back" value="<?php echo esc_attr( $back ); ?>">
		<?php foreach ( array( 'nhom', 'q', 'mon', 'chu-de', 'van-ban', 'dieu', 'do-kho', 'loai', 'can-cu' ) as $k ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( (string) ( $f[ $k ] ?? '' ) ); ?>">
		<?php endforeach; ?>
		<input type="hidden" name="nguon" value="<?php echo esc_attr( $src ); ?>">
		<label class="qb-start-n"><span>Số câu</span>
			<select name="so-cau" data-qb-count>
				<?php foreach ( CVC_QB_COUNTS as $c ) : ?>
					<option value="<?php echo (int) $c; ?>"<?php selected( (int) $f['so-cau'], $c ); ?>><?php echo (int) $c; ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<button type="submit" name="mode" value="practice" class="qb-btn is-practice"<?php echo 0 === $available ? ' disabled' : ''; ?>>
			<span class="qb-btn-t">✎ Ôn tập</span><small>Đáp án &amp; căn cứ sau mỗi câu</small>
		</button>
		<button type="submit" name="mode" value="exam" class="qb-btn is-exam"<?php echo 0 === $available ? ' disabled' : ''; ?>>
			<span class="qb-btn-t">◷ Thi thử · <span data-qb-min><?php echo (int) $min; ?></span> phút</span><small>Tính giờ, đáp án hiện sau khi nộp</small>
		</button>
	</form>
	<?php
}

/* --- Bắt đầu Ôn tập / Thi thử cho nhóm câu đang lọc --- */
add_action( 'admin_post_cvc_qb_start', 'cvc_qb_handle_start' );
add_action( 'admin_post_nopriv_cvc_qb_start', 'cvc_qb_handle_start' );

function cvc_qb_handle_start(): void {
	check_admin_referer( 'cvc_qb_start' );
	$f      = cvc_qb_filters( $_POST );
	$source = sanitize_key( wp_unslash( (string) ( $_POST['nguon'] ?? 'all' ) ) );
	$source = isset( CVC_QB_SOURCES[ $source ] ) ? $source : 'all';
	$mode   = 'exam' === sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ) ? 'exam' : 'practice';
	$back   = wp_validate_redirect( esc_url_raw( wp_unslash( (string) ( $_POST['back'] ?? '' ) ) ), cvc_qb_url( $f ) );

	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_safe_redirect( cvc_login_url( $back ) );
		exit;
	}

	$payload = cvc_qb_api_filters( $f ) + array(
		'source' => $source,
		'count'  => $f['so-cau'],
		'mode'   => $mode,
	);
	$result  = ( new CVC_Api_Client( null, 40 ) )->post( '/api/question-bank/practice', $payload, $token );
	$id      = (int) ( $result['data']['data']['id'] ?? 0 );

	if ( 401 === (int) ( $result['status'] ?? 0 ) ) {
		wp_safe_redirect( cvc_login_url( $back ) );
		exit;
	}
	cvc_verify_redirect_if_required( $result, $back );
	if ( ! $result['ok'] || $id <= 0 ) {
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $result ), null );
		return;
	}
	wp_safe_redirect( cvc_exam_attempt_url( $id ) );
	exit;
}

/* --- AJAX: luyện nhanh 1 câu, lưu câu (có nonce) --- */
add_action( 'wp_ajax_cvc_qb_q', 'cvc_qb_ajax_question' );
add_action( 'wp_ajax_nopriv_cvc_qb_q', 'cvc_qb_ajax_question' );
add_action( 'wp_ajax_cvc_qb_check', 'cvc_qb_ajax_check' );
add_action( 'wp_ajax_nopriv_cvc_qb_check', 'cvc_qb_ajax_check' );
add_action( 'wp_ajax_cvc_qb_bm', 'cvc_qb_ajax_bookmark' );
add_action( 'wp_ajax_nopriv_cvc_qb_bm', 'cvc_qb_ajax_bookmark' );

function cvc_qb_ajax_guard(): void {
	nocache_headers();
	if ( ! check_ajax_referer( 'cvc_qb', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.' ), 403 );
	}
}

function cvc_qb_ajax_question(): void {
	cvc_qb_ajax_guard();
	$id  = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
	$res = ( new CVC_Api_Client( null, 10 ) )->get( '/api/question-bank/questions/' . $id );
	if ( empty( $res['ok'] ) ) {
		wp_send_json_error( array( 'message' => 404 === (int) ( $res['status'] ?? 0 ) ? 'Câu hỏi không còn trong ngân hàng.' : 'Chưa tải được câu hỏi, vui lòng thử lại.' ), 502 );
	}
	wp_send_json_success( (array) ( $res['data']['data'] ?? array() ) );
}

function cvc_qb_ajax_check(): void {
	cvc_qb_ajax_guard();
	$id  = absint( $_POST['id'] ?? 0 );
	$key = strtoupper( substr( sanitize_key( wp_unslash( (string) ( $_POST['key'] ?? '' ) ) ), 0, 1 ) );
	if ( $id < 1 || ! preg_match( '/^[A-Z]$/', $key ) ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}
	$token = cvc_is_logged_in() ? cvc_auth_token() : null;
	$res   = ( new CVC_Api_Client( null, 12 ) )->post( '/api/question-bank/questions/' . $id . '/check', array( 'key' => $key ), $token );
	if ( empty( $res['ok'] ) ) {
		$status = (int) ( $res['status'] ?? 0 );
		cvc_verify_ajax_fail_if_required( $res );
		if ( 401 === $status ) {
			wp_send_json_error( array( 'message' => 'Đăng nhập để luyện câu hỏi, xem đáp án và căn cứ.', 'login' => true, 'redirect' => cvc_login_url( cvc_verify_return_path() ) ), 401 );
		}
		wp_send_json_error( array( 'message' => 429 === $status ? 'Bạn trả lời quá nhanh, vui lòng thử lại sau ít giây.' : 'Chưa chấm được câu này, vui lòng thử lại.' ), $status >= 400 ? $status : 502 );
	}
	$d = (array) ( $res['data']['data'] ?? array() );
	if ( ! empty( $d['article']['url'] ) ) {
		$d['article']['url'] = home_url( (string) $d['article']['url'] );
	}
	wp_send_json_success( $d );
}

function cvc_qb_ajax_bookmark(): void {
	cvc_qb_ajax_guard();
	$token = cvc_is_logged_in() ? cvc_auth_token() : null;
	if ( ! $token ) {
		wp_send_json_error( array( 'message' => 'Đăng nhập để lưu câu hỏi.', 'login' => true ), 401 );
	}
	$id = absint( $_POST['id'] ?? 0 );
	$on = ! empty( $_POST['on'] ) && '0' !== (string) $_POST['on'];
	if ( $id < 1 ) {
		wp_send_json_error( array( 'message' => 'Dữ liệu không hợp lệ.' ), 422 );
	}
	$client = new CVC_Api_Client( null, 8 );
	$res    = $on
		? $client->post( '/api/bookmarks', array( 'type' => 'question', 'id' => $id ), $token )
		: $client->delete( '/api/bookmarks?' . http_build_query( array( 'type' => 'question', 'id' => $id ) ), $token );
	$status = (int) ( $res['status'] ?? 0 );
	if ( ! empty( $res['ok'] ) || ( ! $on && 404 === $status ) || ( $on && 409 === $status ) ) {
		wp_send_json_success( array( 'saved' => $on ) );
	}
	if ( 401 === $status ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn — đăng nhập lại để lưu.', 'login' => true ), 401 );
	}
	wp_send_json_error( array( 'message' => 'Chưa lưu được, vui lòng thử lại.' ), $status >= 400 ? $status : 502 );
}
