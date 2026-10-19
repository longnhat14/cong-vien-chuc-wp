<?php
/**
 * AI Coach - khung chat nổi trên mọi trang front-end (19/10/2026).
 *
 *  - Nút + khung chat in ra ở wp_footer (trước đây chỉ có ở trang chủ index.php).
 *  - CSS assets/css/ai-coach.css, JS assets/js/ai-coach.js (nạp trên mọi trang).
 *  - Hỏi đáp thật: admin-ajax cvc_ai_coach (inc/home-v17.php) -> POST /api/ai/coach.
 *  - Ẩn trong phòng thi chế độ Thi thật khi bài chưa nộp ($GLOBALS['cvc_aic_hide'] do
 *    template-exam-attempt.php đặt) để giữ tính nghiêm túc của bài thi; bộ lọc cvc_ai_coach_enabled.
 *  - Nút/link có thuộc tính data-open-ai ở bất kỳ trang nào sẽ mở khung chat.
 *  - Chọn AI trả lời (DeepSeek, Meta Muse, ...) bất cứ lúc nào: danh sách GET /api/ai/coach/engines
 *    (nhà cung cấp có key, admin bật - cache 10 phút, xóa khi lưu Cấu hình AI), JS dựng nút chọn
 *    khi có từ 2 AI trở lên; lựa chọn gửi kèm câu hỏi (engine).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'cvc_aic_assets', 20 );
add_action( 'wp_footer', 'cvc_aic_render', 5 );

function cvc_aic_enabled(): bool {
	if ( is_admin() || is_feed() || is_embed() || wp_doing_ajax() ) {
		return false;
	}
	if ( ! empty( $GLOBALS['cvc_aic_hide'] ) ) {
		return false;
	}

	return (bool) apply_filters( 'cvc_ai_coach_enabled', true );
}

/**
 * AI người dùng được chọn (không lộ model/key).
 *
 * @return array<int, array{code: string, label: string, default: bool}>
 */
function cvc_aic_engines(): array {
	$cached = get_transient( 'cvc_aic_engines' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$res  = ( new CVC_Api_Client( null, 5 ) )->get( '/api/ai/coach/engines' );
	$list = array();
	if ( ! empty( $res['ok'] ) ) {
		foreach ( (array) ( $res['data']['data']['engines'] ?? array() ) as $e ) {
			$code = is_array( $e ) ? (string) ( $e['code'] ?? '' ) : '';
			if ( preg_match( '/^[a-z]{2,20}$/', $code ) ) {
				$list[] = array(
					'code'    => $code,
					'label'   => mb_substr( (string) ( $e['label'] ?? $code ), 0, 30 ),
					'default' => ! empty( $e['default'] ),
				);
			}
		}
	}
	set_transient( 'cvc_aic_engines', $list, ! empty( $res['ok'] ) ? 10 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS );

	return $list;
}

function cvc_aic_assets(): void {
	if ( is_admin() ) {
		return;
	}
	if ( ! cvc_aic_enabled() ) {
		return;
	}
	$css = get_theme_file_path( '/assets/css/ai-coach.css' );
	$js  = get_theme_file_path( '/assets/js/ai-coach.js' );
	wp_enqueue_style( 'cvc-ai-coach', get_theme_file_uri( '/assets/css/ai-coach.css' ), array(), (string) filemtime( $css ) );
	wp_enqueue_script( 'cvc-ai-coach', get_theme_file_uri( '/assets/js/ai-coach.js' ), array(), (string) filemtime( $js ), true );
	wp_add_inline_script(
		'cvc-ai-coach',
		'window.CVC_AIC=' . wp_json_encode(
			array(
				'ajax'    => admin_url( 'admin-ajax.php' ),
				'home'    => home_url( '/' ),
				'engines' => cvc_aic_engines(),
			)
		) . ';',
		'before'
	);
}

function cvc_aic_render(): void {
	if ( ! cvc_aic_enabled() ) {
		return;
	}
	$spark  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3.5 14.2 9l5.3 2.2-5.3 2.1L12 19l-2.2-5.7L4.5 11.2 9.8 9 12 3.5Z"/><path d="m18.5 15.5.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1Z"/></svg>';
	$home   = function_exists( 'cvc_is_home17' ) && cvc_is_home17();
	$chips  = array(
		'Tôi nên bắt đầu ôn thi từ đâu?'          => '📚 Bắt đầu ôn thi',
		'Giúp tôi chọn đề thi thử phù hợp'        => '📝 Chọn đề thi',
		'Tôi muốn tìm văn bản pháp luật mới'      => '⚖️ Tra cứu pháp luật',
		'Tìm tin tuyển dụng công chức, viên chức' => '🔎 Tìm tuyển dụng',
	);
	?>
<div class="aic<?php echo $home ? ' is-home' : ''; ?>" id="cvcAic">
	<button class="aic-launcher" id="aiChatLauncher" type="button" aria-expanded="false" aria-controls="aiChatPanel">
		<span class="aic-orb"><?php echo $spark; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span class="aic-launcher-copy"><strong>AI Coach<span class="aic-long"> · Huấn luyện viên ôn thi</span></strong><small><span class="aic-status" aria-hidden="true"></span>Cá nhân hóa lộ trình học</small></span>
	</button>
	<div class="aic-panel" id="aiChatPanel" role="dialog" aria-label="AI Coach - Huấn luyện viên ôn thi" aria-modal="false" hidden>
		<div class="aic-head">
			<span class="aic-orb"><?php echo $spark; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<div class="aic-head-copy"><strong>AI Coach</strong><small>Huấn luyện viên ôn thi cá nhân</small></div>
			<button class="aic-icon" id="aiChatReset" type="button" aria-label="Bắt đầu cuộc trò chuyện mới" title="Cuộc trò chuyện mới" hidden>↺</button>
			<button class="aic-icon" id="aiChatClose" type="button" aria-label="Đóng trợ lý">×</button>
		</div>
		<div class="aic-engines" id="aiChatEngines" role="radiogroup" aria-label="Chọn AI trả lời" hidden></div>
		<div class="aic-context">Bạn có thể hỏi về cách ôn thi, chọn đề, tra cứu văn bản hoặc tìm thông báo tuyển dụng. Cuộc trò chuyện được giữ khi bạn chuyển trang.</div>
		<div class="aic-messages" id="aiChatMessages" aria-live="polite">
			<div class="aic-msg bot">Xin chào! Tôi là AI Coach - huấn luyện viên ôn thi của bạn. Tôi có thể giúp xác định phần kiến thức cần củng cố, gợi ý đề luyện tập và xây dựng lộ trình ôn thi phù hợp. Bạn muốn bắt đầu từ đâu?</div>
		</div>
		<div class="aic-chips" id="aiChatChips">
			<?php foreach ( $chips as $prompt => $label ) : ?>
				<button class="aic-chip" type="button" data-aic-prompt="<?php echo esc_attr( $prompt ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
		<form class="aic-compose" id="aiChatForm">
			<label class="screen-reader-text" for="aiChatInput">Nhập câu hỏi cho AI Coach</label>
			<input class="aic-input" id="aiChatInput" type="text" maxlength="500" placeholder="Nhập câu hỏi của bạn..." autocomplete="off">
			<button class="aic-send" type="submit" aria-label="Gửi câu hỏi">➤</button>
		</form>
		<p class="aic-disclaimer">AI Coach trả lời dựa trên dữ liệu đề thi, văn bản và tin tuyển dụng trên hệ thống, có thể chưa chính xác tuyệt đối - hãy đối chiếu văn bản và thông báo gốc trước khi quyết định.</p>
	</div>
</div>
	<?php
}
