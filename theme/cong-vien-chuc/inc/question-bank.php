<?php
/**
 * Menu Ôn thi (19/10/2026): Ngân hàng câu hỏi (/ngan-hang-cau-hoi/) và Câu hỏi đã lưu (/cau-hoi-da-luu/).
 * Một ngân hàng duy nhất; người dùng lọc theo môn / chủ đề / văn bản / Điều rồi chọn Ôn tập hoặc Thi thử
 * cho nhóm câu đang lọc (API POST /api/question-bank/practice tạo bài luyện cá nhân và mở lượt làm).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_QB_COUNTS  = array( 10, 20, 40, 60 );
const CVC_QB_SOURCES = array(
	'all'     => 'Tất cả câu',
	'new'     => 'Câu chưa làm',
	'wrong'   => 'Câu từng làm sai',
	'flagged' => 'Câu đã đánh dấu',
	'review'  => 'Câu cần ôn lại',
);

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
	wp_localize_script( 'cvc-qb', 'CVC_QB', array( 'ajax' => admin_url( 'admin-ajax.php' ) ) );
}

/** Bộ lọc từ query string (tên tham số tiếng Việt trên URL). */
function cvc_qb_filters( array $src ): array {
	$i = static fn ( string $k ): int => isset( $src[ $k ] ) && ! is_array( $src[ $k ] ) ? absint( $src[ $k ] ) : 0;
	$s = static fn ( string $k ): string => isset( $src[ $k ] ) && ! is_array( $src[ $k ] ) ? sanitize_key( wp_unslash( (string) $src[ $k ] ) ) : '';
	$f = array(
		'mon'    => $i( 'mon' ),
		'chu-de' => $i( 'chu-de' ),
		'van-ban' => $i( 'van-ban' ),
		'dieu'   => $i( 'dieu' ),
		'nguon'  => isset( CVC_QB_SOURCES[ $s( 'nguon' ) ] ) ? $s( 'nguon' ) : 'all',
		'so-cau' => in_array( $i( 'so-cau' ), CVC_QB_COUNTS, true ) ? $i( 'so-cau' ) : 20,
	);
	if ( ! $f['van-ban'] ) {
		$f['dieu'] = 0;
	}

	return $f;
}

/** Tham số API tương ứng. */
function cvc_qb_api_filters( array $f ): array {
	return array_filter(
		array(
			'subject_id'        => $f['mon'] ?: null,
			'topic_id'          => $f['chu-de'] ?: null,
			'legal_document_id' => $f['van-ban'] ?: null,
			'legal_article_id'  => $f['dieu'] ?: null,
		)
	);
}

function cvc_qb_facets( array $f ): array {
	$res = cvc_ci_api( '/api/question-bank/facets', cvc_qb_api_filters( $f ), null, 15, 900 );

	return $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
}

/** URL trang ngân hàng với bộ lọc (bỏ giá trị mặc định). */
function cvc_qb_url( array $f ): string {
	$args = array_filter(
		$f,
		static fn ( $v, $k ) => ! ( ( 'nguon' === $k && 'all' === $v ) || ( 'so-cau' === $k && 20 === (int) $v ) || ! $v ),
		ARRAY_FILTER_USE_BOTH
	);

	return cvc_question_bank_url( $args );
}

/**
 * Form bắt đầu (POST admin-post cvc_qb_start). $f: bộ lọc; nút Ôn tập / Thi thử gửi kèm mode.
 * $back: trang quay lại khi lỗi.
 */
function cvc_qb_start_form( array $f, string $back, int $available, string $class = '' ): void {
	$n   = $available > 0 ? min( (int) $f['so-cau'], $available ) : (int) $f['so-cau'];
	$min = max( 5, $n );
	?>
	<form class="qb-start <?php echo esc_attr( $class ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-qb-start>
		<?php wp_nonce_field( 'cvc_qb_start' ); ?>
		<input type="hidden" name="action" value="cvc_qb_start">
		<input type="hidden" name="back" value="<?php echo esc_attr( $back ); ?>">
		<?php foreach ( array( 'mon', 'chu-de', 'van-ban', 'dieu', 'nguon', 'so-cau' ) as $k ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( (string) $f[ $k ] ); ?>" data-qb-sync="<?php echo esc_attr( $k ); ?>">
		<?php endforeach; ?>
		<button type="submit" name="mode" value="practice" class="qb-btn is-practice"<?php echo 0 === $available ? ' disabled' : ''; ?>>
			<span class="qb-btn-t">✎ Ôn tập <span data-qb-n><?php echo (int) $n; ?></span> câu</span>
			<small>Xem đáp án, giải thích và căn cứ ngay sau mỗi câu</small>
		</button>
		<button type="submit" name="mode" value="exam" class="qb-btn is-exam"<?php echo 0 === $available ? ' disabled' : ''; ?>>
			<span class="qb-btn-t">◷ Thi thử <span data-qb-n><?php echo (int) $n; ?></span> câu · <span data-qb-min><?php echo (int) $min; ?></span> phút</span>
			<small>Tính giờ như phòng thi, đáp án hiện sau khi nộp</small>
		</button>
	</form>
	<?php
}

/* --- AJAX: cập nhật bộ lọc + số câu khớp (công khai, có cache) --- */
add_action( 'wp_ajax_cvc_qb_facets', 'cvc_qb_ajax_facets' );
add_action( 'wp_ajax_nopriv_cvc_qb_facets', 'cvc_qb_ajax_facets' );

function cvc_qb_ajax_facets(): void {
	$f = cvc_qb_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
	$d = cvc_qb_facets( $f );
	if ( ! $d ) {
		wp_send_json_error( array( 'message' => 'Không tải được bộ lọc, vui lòng thử lại.' ), 502 );
	}
	wp_send_json_success( array( 'facets' => $d, 'url' => cvc_qb_url( $f ) ) );
}

/* --- Bắt đầu Ôn tập / Thi thử cho nhóm câu đang lọc --- */
add_action( 'admin_post_cvc_qb_start', 'cvc_qb_handle_start' );
add_action( 'admin_post_nopriv_cvc_qb_start', 'cvc_qb_handle_start' );

function cvc_qb_handle_start(): void {
	check_admin_referer( 'cvc_qb_start' );
	$f    = cvc_qb_filters( $_POST );
	$mode = 'exam' === sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ) ? 'exam' : 'practice';
	$back = wp_validate_redirect( esc_url_raw( wp_unslash( (string) ( $_POST['back'] ?? '' ) ) ), cvc_qb_url( $f ) );

	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_safe_redirect( cvc_login_url( $back ) );
		exit;
	}

	$payload = cvc_qb_api_filters( $f ) + array(
		'source' => $f['nguon'],
		'count'  => $f['so-cau'],
		'mode'   => $mode,
	);
	$result  = ( new CVC_Api_Client( null, 40 ) )->post( '/api/question-bank/practice', $payload, $token );
	$id      = (int) ( $result['data']['data']['id'] ?? 0 );

	if ( 401 === (int) ( $result['status'] ?? 0 ) ) {
		wp_safe_redirect( cvc_login_url( $back ) );
		exit;
	}
	if ( ! $result['ok'] || $id <= 0 ) {
		cvc_redirect_with_notice( $back, 'error', cvc_api_error_message( $result ), null );
		return;
	}
	wp_safe_redirect( cvc_exam_attempt_url( $id ) );
	exit;
}
