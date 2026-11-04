<?php
/**
 * Số liệu quảng bá ngân hàng câu hỏi / bài kiến thức (04/11/2026).
 * - cvc_site_stats(): 1 nguồn chung (GET /api/homepage/overview -> counts), cache 1 giờ.
 * - Hiển thị làm tròn xuống hàng trăm + "+" (28.400+), số chính xác ở title (rê chuột).
 * - cvc_trust_strip(): dải "niềm tin" dùng ở chân trang, trang tuyển dụng, trang tỉnh, trang đề, bộ đề PDF,
 *   đăng nhập / đăng ký, trang gói ôn thi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array<string, mixed> */
function cvc_site_stats(): array {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}
	$c = array();
	if ( function_exists( 'cvc_home17_data' ) && function_exists( 'cvc_is_home17' ) && cvc_is_home17() ) {
		$c = (array) ( cvc_home17_data()['counts'] ?? array() );
	}
	if ( empty( $c['questions'] ) ) {
		$cached = get_transient( 'cvc_site_stats_v1' );
		if ( is_array( $cached ) && ! empty( $cached['questions'] ) ) {
			$c = $cached;
		} else {
			$res = ( new CVC_Api_Client( null, 8 ) )->get( '/api/homepage/overview' );
			$c   = $res['ok'] ? (array) ( $res['data']['data']['counts'] ?? array() ) : array();
			if ( ! empty( $c['questions'] ) ) {
				set_transient( 'cvc_site_stats_v1', $c, HOUR_IN_SECONDS );
			}
		}
	}
	$memo = array(
		'exams'          => (int) ( $c['exams'] ?? 0 ),
		'questions'      => (int) ( $c['questions'] ?? 0 ),
		'knowledge'      => (int) ( $c['knowledge'] ?? 0 ),
		'with_basis'     => (int) ( $c['questions_with_basis'] ?? 0 ),
		'new_q7'         => (int) ( $c['new_questions_7d'] ?? 0 ),
		'new_k7'         => (int) ( $c['new_knowledge_7d'] ?? 0 ),
		'groups'         => (array) ( $c['questions_groups'] ?? array() ),
		'open_recruit'   => (int) ( $c['open_recruitments'] ?? 0 ),
		'raw'            => $c,
	);

	return $memo;
}

/** Làm tròn xuống hàng trăm (>= 1.000). */
function cvc_stat_floor( int $n ): int {
	return $n >= 1000 ? (int) ( floor( $n / 100 ) * 100 ) : $n;
}

/** "28.400+" (số >= 1.000) hoặc "663". */
function cvc_stat_label( int $n ): string {
	return number_format( cvc_stat_floor( $n ), 0, ',', '.' ) . ( $n >= 1000 ? '+' : '' );
}

/**
 * Dải niềm tin: số đề · câu hỏi · bài kiến thức (link tới từng nơi).
 *
 * @param string $variant 'footer' | 'card' | 'inline'
 */
function cvc_trust_strip( string $variant = 'card', string $lead = '' ): void {
	$s = cvc_site_stats();
	if ( $s['questions'] <= 0 ) {
		return;
	}
	$items = array(
		array( cvc_stat_label( $s['questions'] ), 'câu hỏi trắc nghiệm', 'có đáp án, giải thích, căn cứ Điều – khoản', function_exists( 'cvc_question_bank_url' ) ? cvc_question_bank_url() : home_url( '/' ), $s['questions'] ),
		array( cvc_stat_label( $s['knowledge'] ), 'bài kiến thức', 'tóm tắt theo văn bản, ý chính dễ nhớ', function_exists( 'cvc_knowledge_url' ) ? cvc_knowledge_url() : home_url( '/' ), $s['knowledge'] ),
		array( cvc_stat_label( $s['exams'] ), 'đề thi thử & luyện tập', 'chấm điểm như thi thật', function_exists( 'cvc_exams_url' ) ? cvc_exams_url() : home_url( '/' ), $s['exams'] ),
	);
	echo '<div class="cvc-trust is-' . esc_attr( $variant ) . '">';
	if ( '' !== $lead ) {
		echo '<p class="cvc-trust-lead">' . esc_html( $lead ) . '</p>';
	}
	echo '<div class="cvc-trust-row">';
	foreach ( $items as $it ) {
		echo '<a class="cvc-trust-item" href="' . esc_url( $it[3] ) . '" title="' . esc_attr( number_format( $it[4], 0, ',', '.' ) . ' ' . $it[1] ) . '"><b>' . esc_html( $it[0] ) . '</b><span>' . esc_html( $it[1] ) . '</span>' . ( 'footer' !== $variant ? '<small>' . esc_html( $it[2] ) . '</small>' : '' ) . '</a>';
	}
	echo '</div></div>';
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_register_style( 'cvc-site-stats', false, array(), '20261104' );
		wp_enqueue_style( 'cvc-site-stats' );
		wp_add_inline_style(
			'cvc-site-stats',
			'.cvc-trust{margin:14px 0}.cvc-trust-lead{margin:0 0 8px;font-weight:700;color:#173653}'
			. '.cvc-trust-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}'
			. '.cvc-trust-item{display:flex;flex-direction:column;gap:2px;padding:12px 14px;border:1px solid #e3e7ec;border-radius:14px;background:#fff;text-decoration:none;color:#18202c;min-width:0}'
			. '.cvc-trust-item b{font-size:22px;line-height:1.1;color:#e66a24;font-variant-numeric:tabular-nums}.cvc-trust-item span{font-weight:700;color:#173653}.cvc-trust-item small{color:#667085;font-size:13px}'
			. '@media(hover:hover){.cvc-trust-item:hover{border-color:#f0a477}}'
			. '.cvc-trust.is-footer{margin:0 0 18px}.cvc-trust.is-footer .cvc-trust-item{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.15);color:#fff}.cvc-trust.is-footer .cvc-trust-item span{color:#dbe5f0}.cvc-trust.is-footer .cvc-trust-item b{color:#ffb27d}'
			. '.cvc-trust.is-inline .cvc-trust-row{display:flex;flex-wrap:wrap;gap:8px}.cvc-trust.is-inline .cvc-trust-item{flex-direction:row;align-items:baseline;gap:6px;padding:6px 12px;border-radius:30px}.cvc-trust.is-inline .cvc-trust-item b{font-size:16px}.cvc-trust.is-inline small{display:none}'
			. '@media(max-width:640px){.cvc-trust-row{grid-template-columns:minmax(0,1fr)}.cvc-trust.is-footer .cvc-trust-row{grid-template-columns:repeat(3,minmax(0,1fr))}.cvc-trust.is-footer .cvc-trust-item{padding:8px}.cvc-trust.is-footer .cvc-trust-item b{font-size:16px}.cvc-trust.is-footer .cvc-trust-item span{font-size:12px}}'
		);
	},
	20
);
