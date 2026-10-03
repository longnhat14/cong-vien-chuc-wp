<?php
/**
 * CÔNG VIÊN CHỨC — Cấu hình AI Coach (nội bộ)
 * URL: /quan-tri/cau-hinh-ai/ — chỉ SUPER_ADMIN/ADMIN.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();

if ( ! cvc_user_is_admin() ) {
	wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'cvc' ), '', array( 'response' => 403 ) );
}

$token    = cvc_auth_token();
$settings = ( new CVC_Setting_Service() )->get_all( $token );
$ai       = $settings['ok'] ? ( $settings['data']['data']['ai'] ?? array() ) : array();

$provider_configured = $ai['ai.provider']['value'] ?? '';
$key_configured       = ! empty( $ai['ai.api_key']['configured'] );
$key_masked           = $ai['ai.api_key']['value'] ?? '';
$model_configured     = $ai['ai.model']['value'] ?? '';

$jobs_result = ( new CVC_Setting_Service() )->ai_jobs( $token );
$jobs_body   = $jobs_result['ok'] ? ( $jobs_result['data'] ?? array() ) : array();
$jobs        = $jobs_body['data']['data'] ?? array();
$job_stats   = $jobs_body['stats'] ?? array();

cvc_seo_set_title( 'Cấu hình AI Coach — Quản trị' );
cvc_seo_set_noindex();

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-10">
	<div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">

		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Quản trị' ),
				array( 'label' => 'Cấu hình AI Coach' ),
			)
		);
		?>

		<?php cvc_render_notice(); ?>

		<div class="bg-[#0A192F] border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
			<div class="space-y-1 border-b border-slate-800 pb-4">
				<h1 class="text-xl font-black text-white flex items-center gap-2">
					<i class="fa-solid fa-robot text-amber-400"></i> Cấu Hình AI Coach
				</h1>
				<p class="text-xs text-slate-400">
					Chọn nhà cung cấp AI và nhập API Key riêng của bạn. Key được <strong class="text-slate-200">mã hoá khi lưu</strong> ở backend, không hiển thị lại dạng gốc sau khi lưu, và không bao giờ cần dán vào bất kỳ đoạn chat/trao đổi nào khác.
				</p>
				<?php if ( $key_configured ) : ?>
					<p class="text-[11px] text-emerald-400 font-bold flex items-center gap-1.5 pt-1">
						<i class="fa-solid fa-circle-check"></i> Đã có API Key đang hoạt động (<?php echo esc_html( $key_masked ); ?>)
					</p>
				<?php else : ?>
					<p class="text-[11px] text-amber-400 font-bold flex items-center gap-1.5 pt-1">
						<i class="fa-solid fa-triangle-exclamation"></i> Chưa cấu hình API Key — AI Coach sẽ tạm dùng giải thích tĩnh có sẵn cho tới khi bạn lưu key tại đây.
					</p>
				<?php endif; ?>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-5">
				<?php wp_nonce_field( 'cvc_ai_settings_save' ); ?>
				<input type="hidden" name="action" value="cvc_ai_settings_save">

				<div class="space-y-1.5">
					<label for="ai_provider" class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Nhà cung cấp AI</label>
					<select name="ai_provider" id="ai_provider" class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-sm text-white focus:border-amber-400 focus:outline-none">
						<?php
						$providers = array(
							'openai' => 'OpenAI (GPT)',
							'gemini' => 'Google Gemini',
							'claude' => 'Anthropic Claude',
							'qwen'   => 'Qwen (DashScope)',
						);
						foreach ( $providers as $value => $label ) :
							?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $provider_configured, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="space-y-1.5">
					<label for="ai_api_key" class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">API Key</label>
					<input type="password" name="ai_api_key" id="ai_api_key" autocomplete="off"
						placeholder="<?php echo $key_configured ? esc_attr( 'Để trống nếu không đổi (' . $key_masked . ')' ) : 'Dán API Key tại đây'; ?>"
						class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-sm text-white placeholder:text-slate-600 focus:border-amber-400 focus:outline-none font-mono">
					<p class="text-[10px] text-slate-500">Key được gửi trực tiếp tới API nội bộ qua kết nối đã đăng nhập của bạn, không ghi log, không lưu ở WordPress.</p>
				</div>

				<div class="space-y-1.5">
					<label for="ai_model" class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Model (tuỳ chọn)</label>
					<input type="text" name="ai_model" id="ai_model" value="<?php echo esc_attr( $model_configured ); ?>"
						placeholder="VD: gpt-4o-mini, gemini-2.5-flash, claude-haiku-4-5..."
						class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-sm text-white placeholder:text-slate-600 focus:border-amber-400 focus:outline-none font-mono">
					<p class="text-[10px] text-slate-500">Để trống để dùng model mặc định của từng nhà cung cấp.</p>
				</div>

				<button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl shadow-lg transition-transform hover:scale-[1.01]">
					Lưu Cấu Hình
				</button>
			</form>
		</div>

		<div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-5 space-y-3 text-xs">
			<h2 class="text-sm font-black text-white">Nhật ký gọi AI (AI job pipeline)</h2>
			<?php if ( ! $jobs_result['ok'] ) : ?>
				<p class="text-slate-400">Không tải được nhật ký (cần quyền ai.view): <?php echo esc_html( cvc_api_error_message( $jobs_result ) ); ?></p>
			<?php else : ?>
				<p class="text-slate-300">
					Trạng thái: <strong class="<?php echo ! empty( $jobs_body['configured'] ) ? 'text-emerald-300' : 'text-amber-300'; ?>"><?php echo ! empty( $jobs_body['configured'] ) ? 'Đã cấu hình' : 'Chưa cấu hình - học viên đang thấy giải thích có sẵn'; ?></strong>
					<?php if ( ! empty( $jobs_body['current']['model'] ) ) : ?>
						· Model: <code><?php echo esc_html( $jobs_body['current']['provider'] . ' / ' . $jobs_body['current']['model'] ); ?></code>
					<?php endif; ?>
				</p>
				<p class="text-slate-400">
					Tổng <?php echo (int) ( $job_stats['total'] ?? 0 ); ?> lần gọi · thành công <?php echo (int) ( $job_stats['succeeded'] ?? 0 ); ?> · lỗi <?php echo (int) ( $job_stats['failed'] ?? 0 ); ?> · trung bình <?php echo (int) ( $job_stats['avg_duration_ms'] ?? 0 ); ?> ms. Kết quả thành công được dùng lại (cache) cho cùng câu hỏi + cùng model.
				</p>
				<?php if ( empty( $jobs ) ) : ?>
					<p class="text-slate-500">Chưa có lần gọi AI nào.</p>
				<?php else : ?>
					<div class="overflow-x-auto">
						<table class="w-full text-left text-[11px]">
							<thead class="text-slate-400"><tr><th class="py-1 pr-2">Thời gian</th><th class="pr-2">Câu hỏi</th><th class="pr-2">Model</th><th class="pr-2">Kết quả</th><th>ms</th></tr></thead>
							<tbody>
								<?php foreach ( $jobs as $job ) : ?>
									<tr class="border-t border-slate-800">
										<td class="py-1 pr-2 whitespace-nowrap"><?php echo esc_html( cvc_format_date_vn( $job['created_at'] ?? null ) ); ?></td>
										<td class="pr-2">#<?php echo (int) ( $job['subject_id'] ?? 0 ); ?></td>
										<td class="pr-2"><?php echo esc_html( ( $job['provider'] ?? '' ) . ' / ' . ( $job['model'] ?? '' ) ); ?></td>
										<td class="pr-2 <?php echo 'succeeded' === ( $job['status'] ?? '' ) ? 'text-emerald-300' : 'text-rose-300'; ?>" title="<?php echo esc_attr( (string) ( $job['error'] ?? '' ) ); ?>"><?php echo 'succeeded' === ( $job['status'] ?? '' ) ? 'Thành công' : 'Lỗi'; ?></td>
										<td><?php echo (int) ( $job['duration_ms'] ?? 0 ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-4 text-[11px] text-slate-400 leading-relaxed">
			<i class="fa-solid fa-circle-info text-cyan-400"></i>
			AI Coach dùng key này để giải thích chuyên sâu từng câu hỏi sau khi học viên nộp bài thi. Nếu chưa cấu hình hoặc gọi API lỗi, hệ thống tự động dùng lại phần giải thích tĩnh có sẵn trong ngân hàng câu hỏi — học viên không bao giờ thấy lỗi hay màn hình trống.
		</div>

	</div>
</main>

<?php get_footer(); ?>
