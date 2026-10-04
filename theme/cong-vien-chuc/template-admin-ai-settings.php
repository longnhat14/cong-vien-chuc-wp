<?php
/**
 * CÔNG VIÊN CHỨC — Cấu hình AI (nội bộ)
 * URL: /quan-tri/cau-hinh-ai/ — chỉ SUPER_ADMIN/ADMIN.
 *
 * Key riêng từng nhà cung cấp, model tự tải theo nhà cung cấp, đọc công văn
 * (DeepSeek/Qwen qua ảnh, OpenAI/Gemini/Claude qua PDF), bảng giá + nút cập
 * nhật giá, tỷ giá USD→VNĐ, hạn mức chi phí tháng, thống kê token/VNĐ.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();

if ( ! cvc_user_is_admin() ) {
	wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'cvc' ), '', array( 'response' => 403 ) );
}

$token      = cvc_auth_token();
$period_map = array(
	'thang-nay'   => array( 'month', 'Tháng này' ),
	'thang-truoc' => array( 'last_month', 'Tháng trước' ),
	'30-ngay'     => array( '30d', '30 ngày' ),
	'tat-ca'      => array( 'all', 'Toàn bộ' ),
);
$ky         = isset( $_GET['ky'] ) ? sanitize_key( wp_unslash( $_GET['ky'] ) ) : 'thang-nay';
$ky         = isset( $period_map[ $ky ] ) ? $ky : 'thang-nay';

$overview = cvc_ai_setting_service( 45 )->ai_overview( $token, $period_map[ $ky ][0] );
$ov       = $overview['ok'] ? (array) ( $overview['data']['data'] ?? array() ) : array();

$providers = (array) ( $ov['providers'] ?? array() );
$cfg       = (array) ( $ov['settings'] ?? array() );
$budget    = (array) ( $ov['budget'] ?? array() );
$rate      = (array) ( $ov['rate'] ?? array() );
$prices    = (array) ( $ov['prices'] ?? array() );
$usage     = (array) ( $ov['usage'] ?? array() );
$totals    = (array) ( $usage['totals'] ?? array() );

$jobs_result = ( new CVC_Setting_Service() )->ai_jobs( $token, 10 );
$jobs_body   = $jobs_result['ok'] ? ( $jobs_result['data'] ?? array() ) : array();
$jobs        = $jobs_body['data']['data'] ?? array();

$fmt_int = static fn( $n ): string => number_format( (float) $n, 0, ',', '.' );
$fmt_vnd = static fn( $n ): string => number_format( (float) $n, 0, ',', '.' ) . ' ₫';
$fmt_usd = static function ( $n ): string {
	$n = (float) $n;
	return '$' . ( $n >= 1 ? number_format( $n, 2, ',', '.' ) : rtrim( rtrim( number_format( $n, 6, ',', '.' ), '0' ), ',' ) );
};
$fmt_price = static fn( $n ): string => null === $n ? '—' : rtrim( rtrim( number_format( (float) $n, 4, ',', '.' ), '0' ), ',' );
$fmt_time  = static function ( ?string $iso ): string {
	if ( ! $iso ) {
		return '—';
	}
	try {
		$d = new DateTime( $iso );
		$d->setTimezone( new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
		return $d->format( 'H:i d/m/Y' );
	} catch ( Exception $e ) {
		return '—';
	}
};

$source_labels = array(
	'official'   => 'Trang giá chính thức',
	'openrouter' => 'OpenRouter',
	'manual'     => 'Sửa tay',
);

$current_provider = (string) ( $cfg['provider'] ?? 'deepseek' );
$current_model    = (string) ( $cfg['model'] ?? '' );
$doc_provider     = (string) ( $cfg['doc_provider'] ?? 'none' );
$doc_model        = (string) ( $cfg['doc_model'] ?? '' );

// Danh sách model cho JS (đổi nhà cung cấp -> đổi danh sách).
$catalog_js = array();
foreach ( $providers as $pid => $p ) {
	$catalog_js[ $pid ] = array(
		'label'       => $p['label'] ?? $pid,
		'configured'  => ! empty( $p['configured'] ),
		'default'     => $p['default_model'] ?? '',
		'doc_default' => $p['doc_default_model'] ?? '',
		'doc_mode'    => $p['doc_mode'] ?? 'images',
		'models'      => array_map(
			static fn( $m ) => array( 'id' => (string) $m['id'], 'vision' => $m['vision'] ?? null ),
			(array) ( $p['catalog']['models'] ?? array() )
		),
	);
}

// Model đang dùng/cấu hình mà chưa có giá.
$priced_keys = array();
foreach ( $prices as $pr ) {
	$priced_keys[ $pr['provider'] . '|' . strtolower( $pr['model'] ) ] = true;
}
$missing_price = array();
foreach ( (array) ( $usage['by_model'] ?? array() ) as $row ) {
	if ( (int) ( $row['unpriced_calls'] ?? 0 ) > 0 ) {
		$missing_price[ $row['provider'] . '|' . $row['model'] ] = array( $row['provider'], $row['model'] );
	}
}
if ( ! empty( $cfg['effective_model'] ) && ! isset( $priced_keys[ $current_provider . '|' . strtolower( (string) $cfg['effective_model'] ) ] ) ) {
	$missing_price[ $current_provider . '|' . $cfg['effective_model'] ] = array( $current_provider, (string) $cfg['effective_model'] );
}
if ( ! empty( $cfg['doc_effective']['model'] ) && ! isset( $priced_keys[ $cfg['doc_effective']['provider'] . '|' . strtolower( (string) $cfg['doc_effective']['model'] ) ] ) ) {
	$missing_price[ $cfg['doc_effective']['provider'] . '|' . $cfg['doc_effective']['model'] ] = array( (string) $cfg['doc_effective']['provider'], (string) $cfg['doc_effective']['model'] );
}

// Bảng giá: mặc định chỉ hiện model đang cấu hình/đã dùng; còn lại trong "Xem tất cả".
$used_keys = array();
foreach ( (array) ( $usage['by_model'] ?? array() ) as $row ) {
	$used_keys[ $row['provider'] . '|' . strtolower( $row['model'] ) ] = true;
}
$used_keys[ $current_provider . '|' . strtolower( (string) ( $cfg['effective_model'] ?? '' ) ) ] = true;
if ( ! empty( $cfg['doc_effective']['model'] ) ) {
	$used_keys[ $cfg['doc_effective']['provider'] . '|' . strtolower( (string) $cfg['doc_effective']['model'] ) ] = true;
}
$prices_main  = array();
$prices_other = array();
foreach ( $prices as $pr ) {
	if ( isset( $used_keys[ $pr['provider'] . '|' . strtolower( $pr['model'] ) ] ) || 'manual' === $pr['source'] ) {
		$prices_main[] = $pr;
	} else {
		$prices_other[] = $pr;
	}
}

$input_cls = 'w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-sm text-white placeholder:text-slate-600 focus:border-amber-400 focus:outline-none';
$label_cls = 'text-[11px] font-extrabold uppercase tracking-wider text-slate-400';
$card_cls  = 'bg-[#0A192F] border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4';

cvc_seo_set_title( 'Cấu hình AI — Quản trị' );
cvc_seo_set_noindex();

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-10">
	<div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-6">

		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Quản trị', 'url' => home_url( '/quan-tri/' ) ),
				array( 'label' => 'Cấu hình AI' ),
			)
		);
		?>

		<?php cvc_render_notice(); ?>

		<header class="flex flex-wrap items-end justify-between gap-4">
			<div class="space-y-1">
				<h1 class="text-2xl font-black text-white flex items-center gap-2"><i class="fa-solid fa-robot text-amber-400"></i> Cấu hình AI</h1>
				<p class="text-xs text-slate-400 max-w-2xl">API key được <strong class="text-slate-200">mã hoá khi lưu</strong> ở backend và chỉ hiện dạng che. Không dán key vào bất kỳ đoạn chat nào khác.</p>
			</div>
			<?php if ( $overview['ok'] ) : ?>
				<p class="text-xs font-bold <?php echo ! empty( $cfg['configured'] ) ? 'text-emerald-400' : 'text-amber-400'; ?>">
					<i class="fa-solid <?php echo ! empty( $cfg['configured'] ) ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
					<?php echo ! empty( $cfg['configured'] ) ? 'Đang dùng ' . esc_html( ( $providers[ $current_provider ]['label'] ?? $current_provider ) . ' / ' . ( $cfg['effective_model'] ?? '' ) ) : 'Chưa có key cho nhà cung cấp chính — học viên đang thấy giải thích có sẵn'; ?>
				</p>
			<?php endif; ?>
		</header>

		<?php if ( ! $overview['ok'] ) : ?>
			<div class="<?php echo esc_attr( $card_cls ); ?>">
				<p class="text-sm text-rose-300">Không tải được cấu hình AI: <?php echo esc_html( cvc_api_error_message( $overview ) ); ?></p>
			</div>
		<?php else : ?>

		<!-- Tổng quan chi phí -->
		<section class="grid grid-cols-2 lg:grid-cols-4 gap-3" aria-label="Tổng quan chi phí">
			<?php
			$pct      = $budget['percent'] ?? null;
			$bar_cls  = ! empty( $budget['exceeded'] ) ? 'bg-rose-500' : ( null !== $pct && $pct >= 80 ? 'bg-amber-400' : 'bg-emerald-400' );
			$tiles    = array(
				array( 'Chi phí tháng ' . ( $budget['month'] ?? '' ), $fmt_vnd( $budget['spent'] ?? 0 ), ( $budget['budget'] ?? 0 ) > 0 ? 'Hạn mức ' . $fmt_vnd( $budget['budget'] ) : 'Chưa đặt hạn mức' ),
				array( 'Token ' . mb_strtolower( (string) ( $usage['period_label'] ?? '' ) ), $fmt_int( $totals['total_tokens'] ?? 0 ), $fmt_int( $totals['input_tokens'] ?? 0 ) . ' vào · ' . $fmt_int( $totals['output_tokens'] ?? 0 ) . ' ra' ),
				array( 'Số lần gọi', $fmt_int( $totals['calls'] ?? 0 ), $fmt_int( $totals['ok_calls'] ?? 0 ) . ' thành công' ),
				array( 'Tỷ giá USD', $fmt_vnd( $rate['rate'] ?? 0 ), (string) ( $rate['source'] ?? 'Mặc định' ) ),
			);
			foreach ( $tiles as $i => $t ) :
				?>
				<div class="bg-slate-800/50 border border-slate-800 rounded-2xl p-4 space-y-1">
					<p class="<?php echo esc_attr( $label_cls ); ?>"><?php echo esc_html( $t[0] ); ?></p>
					<p class="text-xl font-black text-white tabular-nums"><?php echo esc_html( $t[1] ); ?></p>
					<p class="text-[11px] text-slate-400"><?php echo esc_html( $t[2] ); ?></p>
					<?php if ( 0 === $i && null !== $pct ) : ?>
						<div class="h-1.5 rounded-full bg-slate-700 overflow-hidden mt-2" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $pct ); ?>" aria-valuemin="0" aria-valuemax="100">
							<div class="h-full <?php echo esc_attr( $bar_cls ); ?>" style="width: <?php echo esc_attr( (string) min( 100, $pct ) ); ?>%"></div>
						</div>
						<p class="text-[11px] <?php echo ! empty( $budget['exceeded'] ) ? 'text-rose-300 font-bold' : 'text-slate-400'; ?>"><?php echo esc_html( ! empty( $budget['exceeded'] ) ? 'Đã vượt hạn mức — AI tạm dừng tới tháng sau hoặc khi tăng hạn mức' : $pct . '% hạn mức' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-6" id="cvc-ai-form">
			<?php wp_nonce_field( 'cvc_ai_settings_save' ); ?>
			<input type="hidden" name="action" value="cvc_ai_settings_save">

			<!-- Key từng nhà cung cấp -->
			<section id="khoa-api" class="<?php echo esc_attr( $card_cls ); ?>">
				<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
					<div>
						<h2 class="text-base font-black text-white">API key theo nhà cung cấp</h2>
						<p class="text-[11px] text-slate-400">Có key thì danh sách model được tải trực tiếp từ nhà cung cấp; chưa có key thì hiện gợi ý từ OpenRouter. Để trống ô key = giữ nguyên key đang lưu.</p>
					</div>
					<button type="submit" form="cvc-ai-models-refresh" class="px-3 py-2 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:border-amber-400"><i class="fa-solid fa-rotate"></i> Làm mới danh sách model</button>
				</div>

				<div class="grid md:grid-cols-2 gap-3">
					<?php foreach ( $providers as $pid => $p ) : ?>
						<?php
						$cat     = (array) ( $p['catalog'] ?? array() );
						$n_model = count( (array) ( $cat['models'] ?? array() ) );
						?>
						<div class="border border-slate-800 rounded-2xl p-4 space-y-2.5">
							<div class="flex items-center justify-between gap-2">
								<h3 class="text-sm font-black text-white"><?php echo esc_html( $p['label'] ?? $pid ); ?></h3>
								<?php if ( ! empty( $p['configured'] ) ) : ?>
									<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300">Đã có key · <?php echo esc_html( (string) $p['masked'] ); ?></span>
								<?php else : ?>
									<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-700 text-slate-300">Chưa có key</span>
								<?php endif; ?>
							</div>
							<label for="ai_key_<?php echo esc_attr( $pid ); ?>" class="sr-only">API key <?php echo esc_html( $p['label'] ?? $pid ); ?></label>
							<input type="password" name="ai_key_<?php echo esc_attr( $pid ); ?>" id="ai_key_<?php echo esc_attr( $pid ); ?>" autocomplete="off"
								placeholder="<?php echo esc_attr( ! empty( $p['configured'] ) ? 'Để trống nếu không đổi' : 'Dán API key ' . ( $p['label'] ?? $pid ) ); ?>"
								class="<?php echo esc_attr( $input_cls ); ?> font-mono">
							<?php if ( 'qwen' === $pid ) : ?>
								<label for="ai_qwen_base_url" class="<?php echo esc_attr( $label_cls ); ?> block pt-1">Endpoint Qwen (OpenAI-compatible)</label>
								<input type="url" name="ai_qwen_base_url" id="ai_qwen_base_url" value="<?php echo esc_attr( (string) ( $cfg['qwen_base_url'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input_cls ); ?> font-mono text-xs">
								<p class="text-[10px] text-slate-500">Mặc định Model Studio quốc tế. Tài khoản Trung Quốc/Hồng Kông/workspace riêng: dán base URL trong Model Studio (…aliyuncs.com/compatible-mode/v1).</p>
							<?php endif; ?>
							<div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
								<span>
									<?php if ( 'api' === ( $cat['source'] ?? '' ) ) : ?>
										<i class="fa-solid fa-circle-check text-emerald-400"></i> <?php echo esc_html( $n_model ); ?> model từ API
									<?php elseif ( $n_model > 0 ) : ?>
										<i class="fa-solid fa-lightbulb text-amber-300"></i> <?php echo esc_html( $n_model ); ?> model gợi ý (OpenRouter)
									<?php else : ?>
										Chưa có danh sách model
									<?php endif; ?>
									<?php if ( ! empty( $cat['error'] ) ) : ?>
										<span class="block text-rose-300" title="<?php echo esc_attr( (string) $cat['error'] ); ?>">Lỗi tải model: <?php echo esc_html( mb_substr( (string) $cat['error'], 0, 90 ) ); ?></span>
									<?php endif; ?>
								</span>
								<span class="flex items-center gap-3">
									<?php if ( ! empty( $p['configured'] ) ) : ?>
										<label class="inline-flex items-center gap-1 cursor-pointer"><input type="checkbox" name="ai_key_clear_<?php echo esc_attr( $pid ); ?>" value="1" class="accent-rose-500"> Xoá key</label>
										<button type="submit" form="cvc-ai-test-<?php echo esc_attr( $pid ); ?>" class="px-2.5 py-1 rounded-lg border border-slate-700 font-bold text-slate-200 hover:border-cyan-400">Kiểm tra kết nối</button>
									<?php endif; ?>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<div class="grid lg:grid-cols-2 gap-6">
				<!-- AI chính -->
				<section class="<?php echo esc_attr( $card_cls ); ?>">
					<div class="border-b border-slate-800 pb-3">
						<h2 class="text-base font-black text-white">AI chính</h2>
						<p class="text-[11px] text-slate-400">Dùng để giải thích câu hỏi sau khi học viên nộp bài và phân tích tin tuyển dụng thu thập tự động.</p>
					</div>
					<div class="space-y-1.5">
						<label for="ai_provider" class="<?php echo esc_attr( $label_cls ); ?>">Nhà cung cấp</label>
						<select name="ai_provider" id="ai_provider" data-model-target="ai_model" class="<?php echo esc_attr( $input_cls ); ?>">
							<?php foreach ( $providers as $pid => $p ) : ?>
								<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $current_provider, $pid ); ?>><?php echo esc_html( ( $p['label'] ?? $pid ) . ( 'deepseek' === $pid ? ' (mặc định)' : '' ) . ( empty( $p['configured'] ) ? ' — chưa có key' : '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="space-y-1.5">
						<label for="ai_model" class="<?php echo esc_attr( $label_cls ); ?>">Model</label>
						<select name="ai_model" id="ai_model" data-current="<?php echo esc_attr( $current_model ); ?>" data-kind="chat" class="<?php echo esc_attr( $input_cls ); ?> font-mono"></select>
						<input type="text" name="ai_model_custom" id="ai_model_custom" value="<?php echo esc_attr( $current_model ); ?>" placeholder="Tên model, VD: deepseek-flash" class="<?php echo esc_attr( $input_cls ); ?> font-mono hidden">
					</div>
				</section>

				<!-- Đọc công văn -->
				<section class="<?php echo esc_attr( $card_cls ); ?>">
					<div class="border-b border-slate-800 pb-3">
						<h2 class="text-base font-black text-white">Đọc công văn scan (PDF)</h2>
						<p class="text-[11px] text-slate-400">DeepSeek và Qwen đọc ảnh từng trang (tối đa 6 trang đầu) — cần model đọc được ảnh (★). OpenAI, Gemini, Claude đọc thẳng file PDF.</p>
					</div>
					<div class="space-y-1.5">
						<label for="ai_doc_provider" class="<?php echo esc_attr( $label_cls ); ?>">Nhà cung cấp đọc công văn</label>
						<select name="ai_doc_provider" id="ai_doc_provider" data-model-target="ai_doc_model" class="<?php echo esc_attr( $input_cls ); ?>">
							<option value="none" <?php selected( $doc_provider, 'none' ); ?>>Dùng AI chính</option>
							<?php foreach ( $providers as $pid => $p ) : ?>
								<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $doc_provider, $pid ); ?>><?php echo esc_html( ( $p['label'] ?? $pid ) . ( 'pdf' === ( $p['doc_mode'] ?? '' ) ? ' — đọc PDF' : ' — đọc ảnh' ) . ( empty( $p['configured'] ) ? ' — chưa có key' : '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="space-y-1.5" id="ai_doc_model_wrap">
						<label for="ai_doc_model" class="<?php echo esc_attr( $label_cls ); ?>">Model đọc công văn</label>
						<select name="ai_doc_model" id="ai_doc_model" data-current="<?php echo esc_attr( $doc_model ); ?>" data-kind="doc" class="<?php echo esc_attr( $input_cls ); ?> font-mono"></select>
						<input type="text" name="ai_doc_model_custom" id="ai_doc_model_custom" value="<?php echo esc_attr( $doc_model ); ?>" placeholder="Tên model, VD: qwen-vl-max" class="<?php echo esc_attr( $input_cls ); ?> font-mono hidden">
					</div>
					<p class="text-[11px] <?php echo ! empty( $cfg['doc_effective'] ) ? 'text-emerald-300' : 'text-amber-300'; ?>">
						<?php if ( ! empty( $cfg['doc_effective'] ) ) : ?>
							<i class="fa-solid fa-file-pdf"></i> Đang đọc công văn bằng <strong><?php echo esc_html( ( $providers[ $cfg['doc_effective']['provider'] ]['label'] ?? $cfg['doc_effective']['provider'] ) . ' / ' . $cfg['doc_effective']['model'] ); ?></strong> (<?php echo 'pdf' === $cfg['doc_effective']['mode'] ? 'gửi file PDF' : 'gửi ảnh từng trang'; ?>).
						<?php else : ?>
							<i class="fa-solid fa-triangle-exclamation"></i> Chưa đọc được công văn scan — nhập key cho nhà cung cấp đã chọn.
						<?php endif; ?>
					</p>
				</section>
			</div>

			<!-- Hạn mức & tỷ giá -->
			<section class="<?php echo esc_attr( $card_cls ); ?>">
				<div class="border-b border-slate-800 pb-3">
					<h2 class="text-base font-black text-white">Hạn mức chi phí &amp; tỷ giá</h2>
					<p class="text-[11px] text-slate-400">Khi chi phí trong tháng (giờ Việt Nam) chạm hạn mức, hệ thống dừng gọi AI: học viên thấy giải thích có sẵn, tin tuyển dụng phân tích bằng quy tắc và nằm ở hàng chờ.</p>
				</div>
				<div class="grid sm:grid-cols-2 gap-4">
					<div class="space-y-1.5">
						<label for="ai_budget_vnd_month" class="<?php echo esc_attr( $label_cls ); ?>">Hạn mức mỗi tháng (VNĐ)</label>
						<input type="text" inputmode="numeric" name="ai_budget_vnd_month" id="ai_budget_vnd_month" value="<?php echo esc_attr( ! empty( $budget['budget'] ) ? $fmt_int( $budget['budget'] ) : '' ); ?>" placeholder="Để trống = không giới hạn, VD: 500.000" class="<?php echo esc_attr( $input_cls ); ?> tabular-nums">
					</div>
					<div class="space-y-1.5">
						<label for="ai_usd_vnd_manual" class="<?php echo esc_attr( $label_cls ); ?>">Tỷ giá nhập tay (VNĐ/USD)</label>
						<input type="text" inputmode="decimal" name="ai_usd_vnd_manual" id="ai_usd_vnd_manual" value="<?php echo esc_attr( ! empty( $rate['manual'] ) ? $fmt_int( $rate['manual'] ) : '' ); ?>" placeholder="Để trống = lấy tự động" class="<?php echo esc_attr( $input_cls ); ?> tabular-nums">
						<p class="text-[10px] text-slate-500">
							Tự động: <?php echo ! empty( $rate['auto'] ) ? esc_html( $fmt_vnd( $rate['auto'] ) . ' — Vietcombank giá bán USD' ) : 'chưa lấy'; ?>
							<?php echo ! empty( $rate['updated_at'] ) ? ' · cập nhật ' . esc_html( $fmt_time( $rate['updated_at'] ) ) : ''; ?>. Chi phí mỗi lần gọi được quy đổi theo tỷ giá tại thời điểm gọi.
						</p>
					</div>
				</div>
			</section>

			<button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl shadow-lg">Lưu cấu hình AI</button>
		</form>

		<!-- Form phụ (nút nằm trong form chính dùng thuộc tính form=) -->
		<form id="cvc-ai-models-refresh" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hidden">
			<?php wp_nonce_field( 'cvc_ai_models_refresh' ); ?>
			<input type="hidden" name="action" value="cvc_ai_models_refresh">
		</form>
		<?php foreach ( $providers as $pid => $p ) : ?>
			<?php
			$test_model = $pid === $current_provider ? (string) ( $cfg['effective_model'] ?? '' ) : ( $pid === $doc_provider ? (string) ( $cfg['doc_effective']['model'] ?? '' ) : '' );
			?>
			<form id="cvc-ai-test-<?php echo esc_attr( $pid ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="hidden">
				<?php wp_nonce_field( 'cvc_ai_test' ); ?>
				<input type="hidden" name="action" value="cvc_ai_test">
				<input type="hidden" name="provider" value="<?php echo esc_attr( $pid ); ?>">
				<input type="hidden" name="model" value="<?php echo esc_attr( $test_model ); ?>">
			</form>
		<?php endforeach; ?>

		<!-- Thống kê token & chi phí -->
		<section id="thong-ke" class="<?php echo esc_attr( $card_cls ); ?>">
			<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
				<div>
					<h2 class="text-base font-black text-white">Token &amp; chi phí — <?php echo esc_html( (string) ( $usage['period_label'] ?? '' ) ); ?></h2>
					<p class="text-[11px] text-slate-400">Token đọc từ phản hồi của nhà cung cấp; chi phí = token × giá trong bảng giá × tỷ giá. DeepSeek ngoài giờ cao điểm tính 50%.</p>
				</div>
				<nav class="flex flex-wrap gap-1.5" aria-label="Chọn kỳ">
					<?php foreach ( $period_map as $slug => $pm ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'ky', $slug, cvc_admin_ai_settings_url() ) . '#thong-ke' ); ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold <?php echo $slug === $ky ? 'bg-amber-400 text-slate-900' : 'border border-slate-700 text-slate-300 hover:border-amber-400'; ?>"><?php echo esc_html( $pm[1] ); ?></a>
					<?php endforeach; ?>
				</nav>
			</div>

			<?php if ( empty( $totals['calls'] ) ) : ?>
				<p class="text-sm text-slate-400">Chưa có lần gọi AI nào trong kỳ này.</p>
			<?php else : ?>
				<div class="grid sm:grid-cols-4 gap-3 text-xs">
					<?php
					foreach ( array(
						array( 'Chi phí', $fmt_vnd( $totals['cost_vnd'] ), $fmt_usd( $totals['cost_usd'] ) ),
						array( 'Token vào', $fmt_int( $totals['input_tokens'] ), 'trong đó cache ' . $fmt_int( $totals['cached_tokens'] ) ),
						array( 'Token ra', $fmt_int( $totals['output_tokens'] ), 'gồm token suy luận nếu có' ),
						array( 'Lần gọi', $fmt_int( $totals['calls'] ), ( (int) $totals['calls'] - (int) $totals['ok_calls'] ) . ' lỗi' . ( ! empty( $totals['unpriced_calls'] ) ? ' · ' . (int) $totals['unpriced_calls'] . ' chưa có giá' : '' ) ),
					) as $t ) :
						?>
						<div class="bg-slate-800/40 rounded-2xl p-3">
							<p class="<?php echo esc_attr( $label_cls ); ?>"><?php echo esc_html( $t[0] ); ?></p>
							<p class="text-lg font-black text-white tabular-nums"><?php echo esc_html( $t[1] ); ?></p>
							<p class="text-[11px] text-slate-400"><?php echo esc_html( $t[2] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>

				<?php
				$tables = array(
					'Theo nhà cung cấp & model' => array( 'rows' => (array) ( $usage['by_model'] ?? array() ), 'name' => static fn( $r ) => ( $providers[ $r['provider'] ]['label'] ?? $r['provider'] ) . ' / ' . $r['model'] ),
					'Theo mục đích'             => array( 'rows' => (array) ( $usage['by_purpose'] ?? array() ), 'name' => static fn( $r ) => (string) $r['label'] ),
				);
				foreach ( $tables as $title => $tb ) :
					?>
					<div class="space-y-2">
						<h3 class="text-xs font-black text-slate-200"><?php echo esc_html( $title ); ?></h3>
						<div class="overflow-x-auto">
							<table class="w-full text-left text-[12px] tabular-nums">
								<thead class="text-slate-400 text-[11px]"><tr><th class="py-1.5 pr-3">Loại</th><th class="pr-3 text-right">Lần gọi</th><th class="pr-3 text-right">Token vào</th><th class="pr-3 text-right">Cache</th><th class="pr-3 text-right">Token ra</th><th class="pr-3 text-right">USD</th><th class="text-right">VNĐ</th></tr></thead>
								<tbody>
									<?php foreach ( $tb['rows'] as $r ) : ?>
										<tr class="border-t border-slate-800">
											<td class="py-1.5 pr-3 font-mono text-slate-200"><?php echo esc_html( $tb['name']( $r ) ); ?><?php echo ! empty( $r['unpriced_calls'] ) ? ' <span class="text-amber-300 font-sans" title="Chưa có giá cho model này">⚠</span>' : ''; ?></td>
											<td class="pr-3 text-right"><?php echo esc_html( $fmt_int( $r['calls'] ) ); ?></td>
											<td class="pr-3 text-right"><?php echo esc_html( $fmt_int( $r['input_tokens'] ) ); ?></td>
											<td class="pr-3 text-right text-slate-400"><?php echo esc_html( $fmt_int( $r['cached_tokens'] ) ); ?></td>
											<td class="pr-3 text-right"><?php echo esc_html( $fmt_int( $r['output_tokens'] ) ); ?></td>
											<td class="pr-3 text-right text-slate-400"><?php echo esc_html( $fmt_usd( $r['cost_usd'] ) ); ?></td>
											<td class="text-right font-bold text-white"><?php echo esc_html( $fmt_vnd( $r['cost_vnd'] ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php endforeach; ?>

				<?php
				$daily   = (array) ( $usage['daily'] ?? array() );
				$max_vnd = max( 1, ...array_map( static fn( $d ) => (float) $d['cost_vnd'], $daily ?: array( array( 'cost_vnd' => 1 ) ) ) );
				$max_tok = max( 1, ...array_map( static fn( $d ) => (float) $d['total_tokens'], $daily ?: array( array( 'total_tokens' => 1 ) ) ) );
				$by_cost = $max_vnd > 1;
				?>
				<?php if ( count( $daily ) > 1 ) : ?>
					<div class="space-y-2">
						<h3 class="text-xs font-black text-slate-200">Theo ngày (<?php echo $by_cost ? 'VNĐ' : 'token'; ?>)</h3>
						<div class="flex items-end gap-1 h-28 border-b border-slate-700" role="img" aria-label="Chi phí theo ngày">
							<?php foreach ( $daily as $d ) : ?>
								<?php
								$v   = $by_cost ? (float) $d['cost_vnd'] : (float) $d['total_tokens'];
								$h   = max( 2, round( $v / ( $by_cost ? $max_vnd : $max_tok ) * 100 ) );
								$tip = date_i18n( 'd/m', strtotime( $d['day'] ) ) . ': ' . $fmt_vnd( $d['cost_vnd'] ) . ' · ' . $fmt_int( $d['total_tokens'] ) . ' token · ' . (int) $d['calls'] . ' lần';
								?>
								<div class="flex-1 min-w-[4px] bg-cyan-400/70 hover:bg-amber-400 rounded-t" style="height: <?php echo esc_attr( (string) $h ); ?>%" title="<?php echo esc_attr( $tip ); ?>"></div>
							<?php endforeach; ?>
						</div>
						<div class="flex justify-between text-[10px] text-slate-500"><span><?php echo esc_html( date_i18n( 'd/m', strtotime( $daily[0]['day'] ) ) ); ?></span><span><?php echo esc_html( date_i18n( 'd/m', strtotime( end( $daily )['day'] ) ) ); ?></span></div>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( ! empty( $usage['recent'] ) ) : ?>
				<details class="text-xs">
					<summary class="cursor-pointer font-bold text-slate-300">15 lần gọi gần nhất</summary>
					<div class="overflow-x-auto mt-2">
						<table class="w-full text-left text-[11px] tabular-nums">
							<thead class="text-slate-400"><tr><th class="py-1 pr-2">Thời gian</th><th class="pr-2">Model</th><th class="pr-2">Mục đích</th><th class="pr-2 text-right">Vào</th><th class="pr-2 text-right">Ra</th><th class="pr-2 text-right">VNĐ</th><th class="pr-2 text-right">ms</th><th>Kết quả</th></tr></thead>
							<tbody>
								<?php foreach ( $usage['recent'] as $l ) : ?>
									<tr class="border-t border-slate-800">
										<td class="py-1 pr-2 whitespace-nowrap"><?php echo esc_html( $fmt_time( $l['created_at'] ) ); ?></td>
										<td class="pr-2 font-mono"><?php echo esc_html( $l['provider'] . ' / ' . $l['model'] ); ?></td>
										<td class="pr-2"><?php echo esc_html( (string) $l['purpose'] ); ?></td>
										<td class="pr-2 text-right"><?php echo esc_html( $fmt_int( $l['input_tokens'] ) ); ?></td>
										<td class="pr-2 text-right"><?php echo esc_html( $fmt_int( $l['output_tokens'] ) ); ?></td>
										<td class="pr-2 text-right"><?php echo esc_html( $fmt_vnd( $l['cost_vnd'] ) ); ?></td>
										<td class="pr-2 text-right"><?php echo esc_html( $fmt_int( $l['duration_ms'] ) ); ?></td>
										<td class="<?php echo ! empty( $l['success'] ) ? 'text-emerald-300' : 'text-rose-300'; ?>" title="<?php echo esc_attr( (string) ( $l['error'] ?? '' ) ); ?>"><?php echo ! empty( $l['success'] ) ? 'Thành công' : 'Lỗi'; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</details>
			<?php endif; ?>
		</section>

		<!-- Bảng giá -->
		<section id="bang-gia" class="<?php echo esc_attr( $card_cls ); ?>">
			<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
				<div>
					<h2 class="text-base font-black text-white">Bảng giá model (USD / 1 triệu token)</h2>
					<p class="text-[11px] text-slate-400">DeepSeek: trang giá chính thức (giá giờ cao điểm). Nhà cung cấp khác: bảng giá công khai OpenRouter. Giá sửa tay không bị ghi đè khi cập nhật. Tự cập nhật thứ Hai hằng tuần<?php echo ! empty( $ov['prices_updated_at'] ) ? ' · lần gần nhất ' . esc_html( $fmt_time( $ov['prices_updated_at'] ) ) : ''; ?>.</p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'cvc_ai_prices_refresh' ); ?>
					<input type="hidden" name="action" value="cvc_ai_prices_refresh">
					<button type="submit" class="px-4 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-900 text-xs font-black"><i class="fa-solid fa-arrows-rotate"></i> Cập nhật giá &amp; tỷ giá</button>
				</form>
			</div>

			<?php if ( ! empty( $missing_price ) ) : ?>
				<div class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-3 text-[12px] text-amber-200 space-y-1">
					<p class="font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Model chưa có giá — chi phí đang tính 0 ₫:</p>
					<p class="font-mono"><?php echo esc_html( implode( ', ', array_map( static fn( $m ) => $m[0] . '/' . $m[1], $missing_price ) ) ); ?></p>
					<p>Bấm "Cập nhật giá" hoặc nhập giá tay ở ô bên dưới.</p>
				</div>
			<?php endif; ?>

			<?php
			$render_rows = static function ( array $rows ) use ( $providers, $fmt_price, $source_labels, $fmt_time, $input_cls ): void {
				foreach ( $rows as $i => $pr ) :
					$fid = 'price-' . md5( $pr['provider'] . $pr['model'] );
					?>
					<tr class="border-t border-slate-800 align-top">
						<td class="py-2 pr-3"><span class="text-slate-400"><?php echo esc_html( $providers[ $pr['provider'] ]['label'] ?? $pr['provider'] ); ?></span><br><span class="font-mono text-slate-100"><?php echo esc_html( $pr['model'] ); ?></span><?php echo ! empty( $pr['supports_images'] ) ? ' <span title="Đọc được ảnh" class="text-amber-300">★</span>' : ''; ?></td>
						<td class="pr-3 text-right"><?php echo esc_html( $fmt_price( $pr['input_per_m'] ) ); ?></td>
						<td class="pr-3 text-right text-slate-400"><?php echo esc_html( $fmt_price( $pr['cached_input_per_m'] ) ); ?></td>
						<td class="pr-3 text-right"><?php echo esc_html( $fmt_price( $pr['output_per_m'] ) ); ?></td>
						<td class="pr-3 text-[11px] text-slate-400"><?php echo esc_html( $source_labels[ $pr['source'] ] ?? $pr['source'] ); ?><br><?php echo esc_html( $fmt_time( $pr['updated_at'] ) ); ?></td>
						<td class="text-right">
							<details class="inline-block text-left">
								<summary class="cursor-pointer text-[11px] font-bold text-cyan-300">Sửa</summary>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-2 grid grid-cols-3 gap-1.5 w-64">
									<?php wp_nonce_field( 'cvc_ai_price_save' ); ?>
									<input type="hidden" name="action" value="cvc_ai_price_save">
									<input type="hidden" name="provider" value="<?php echo esc_attr( $pr['provider'] ); ?>">
									<input type="hidden" name="model" value="<?php echo esc_attr( $pr['model'] ); ?>">
									<label class="text-[10px] text-slate-400">Vào<input type="text" inputmode="decimal" name="input_per_m" value="<?php echo esc_attr( (string) $pr['input_per_m'] ); ?>" class="<?php echo esc_attr( $input_cls ); ?> !px-2 !py-1 text-xs"></label>
									<label class="text-[10px] text-slate-400">Cache<input type="text" inputmode="decimal" name="cached_input_per_m" value="<?php echo esc_attr( (string) ( $pr['cached_input_per_m'] ?? '' ) ); ?>" class="<?php echo esc_attr( $input_cls ); ?> !px-2 !py-1 text-xs"></label>
									<label class="text-[10px] text-slate-400">Ra<input type="text" inputmode="decimal" name="output_per_m" value="<?php echo esc_attr( (string) $pr['output_per_m'] ); ?>" class="<?php echo esc_attr( $input_cls ); ?> !px-2 !py-1 text-xs"></label>
									<button type="submit" class="col-span-2 py-1.5 rounded-lg bg-amber-400 text-slate-900 text-[11px] font-black">Lưu giá</button>
									<?php if ( 'manual' === $pr['source'] ) : ?>
										<button type="submit" name="reset" value="1" class="py-1.5 rounded-lg border border-slate-600 text-[11px] font-bold text-slate-200" title="Bỏ giá sửa tay, lấy lại giá tự động">Bỏ sửa tay</button>
									<?php endif; ?>
								</form>
							</details>
						</td>
					</tr>
					<?php
				endforeach;
			};
			?>
			<div class="overflow-x-auto">
				<table class="w-full text-left text-[12px] tabular-nums">
					<thead class="text-slate-400 text-[11px]"><tr><th class="py-1.5 pr-3">Model</th><th class="pr-3 text-right">Vào</th><th class="pr-3 text-right">Vào (cache)</th><th class="pr-3 text-right">Ra</th><th class="pr-3">Nguồn giá</th><th></th></tr></thead>
					<tbody>
						<?php if ( empty( $prices_main ) ) : ?>
							<tr><td colspan="6" class="py-2 text-slate-400">Chưa có giá cho model đang dùng.</td></tr>
						<?php endif; ?>
						<?php $render_rows( $prices_main ); ?>
					</tbody>
				</table>
			</div>
			<?php if ( ! empty( $prices_other ) ) : ?>
				<details>
					<summary class="cursor-pointer text-xs font-bold text-slate-300">Xem giá <?php echo esc_html( (string) count( $prices_other ) ); ?> model khác</summary>
					<div class="overflow-x-auto mt-2">
						<table class="w-full text-left text-[12px] tabular-nums">
							<tbody><?php $render_rows( $prices_other ); ?></tbody>
						</table>
					</div>
				</details>
			<?php endif; ?>

			<details class="border-t border-slate-800 pt-3">
				<summary class="cursor-pointer text-xs font-bold text-cyan-300">+ Thêm giá cho model khác</summary>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-3 grid sm:grid-cols-6 gap-2 items-end">
					<?php wp_nonce_field( 'cvc_ai_price_save' ); ?>
					<input type="hidden" name="action" value="cvc_ai_price_save">
					<label class="text-[10px] text-slate-400 sm:col-span-1">Nhà cung cấp
						<select name="provider" class="<?php echo esc_attr( $input_cls ); ?> text-xs">
							<?php foreach ( $providers as $pid => $p ) : ?>
								<option value="<?php echo esc_attr( $pid ); ?>"><?php echo esc_html( $p['label'] ?? $pid ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="text-[10px] text-slate-400 sm:col-span-2">Model<input type="text" name="model" required class="<?php echo esc_attr( $input_cls ); ?> font-mono text-xs" placeholder="VD: qwen-vl-max"></label>
					<label class="text-[10px] text-slate-400">Vào<input type="text" inputmode="decimal" name="input_per_m" required class="<?php echo esc_attr( $input_cls ); ?> text-xs"></label>
					<label class="text-[10px] text-slate-400">Cache<input type="text" inputmode="decimal" name="cached_input_per_m" class="<?php echo esc_attr( $input_cls ); ?> text-xs"></label>
					<label class="text-[10px] text-slate-400">Ra<input type="text" inputmode="decimal" name="output_per_m" required class="<?php echo esc_attr( $input_cls ); ?> text-xs"></label>
					<button type="submit" class="sm:col-span-6 py-2 rounded-xl bg-amber-400 text-slate-900 text-xs font-black">Lưu giá</button>
				</form>
			</details>
		</section>

		<?php endif; ?>

		<!-- Nhật ký giải thích câu hỏi -->
		<details class="bg-slate-800/40 border border-slate-800 rounded-2xl p-5 text-xs">
			<summary class="cursor-pointer text-sm font-black text-white">Nhật ký giải thích câu hỏi (AI job)</summary>
			<?php if ( ! $jobs_result['ok'] ) : ?>
				<p class="text-slate-400 mt-2">Không tải được nhật ký (cần quyền ai.view): <?php echo esc_html( cvc_api_error_message( $jobs_result ) ); ?></p>
			<?php elseif ( empty( $jobs ) ) : ?>
				<p class="text-slate-500 mt-2">Chưa có lần gọi AI nào.</p>
			<?php else : ?>
				<p class="text-slate-400 mt-2">Kết quả thành công được dùng lại (cache) cho cùng câu hỏi + cùng model.</p>
				<div class="overflow-x-auto mt-2">
					<table class="w-full text-left text-[11px]">
						<thead class="text-slate-400"><tr><th class="py-1 pr-2">Thời gian</th><th class="pr-2">Câu hỏi</th><th class="pr-2">Model</th><th class="pr-2">Kết quả</th><th>ms</th></tr></thead>
						<tbody>
							<?php foreach ( $jobs as $job ) : ?>
								<tr class="border-t border-slate-800">
									<td class="py-1 pr-2 whitespace-nowrap"><?php echo esc_html( $fmt_time( $job['created_at'] ?? null ) ); ?></td>
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
		</details>

	</div>
</main>

<?php if ( $overview['ok'] ) : ?>
<script>
( function () {
	var catalog = <?php echo wp_json_encode( $catalog_js ); ?>;

	function fill( providerSelect ) {
		var target = document.getElementById( providerSelect.dataset.modelTarget );
		var custom = document.getElementById( target.id + '_custom' );
		var kind = target.dataset.kind;
		var pid = providerSelect.value;
		var wrap = document.getElementById( 'ai_doc_model_wrap' );

		if ( kind === 'doc' && pid === 'none' ) {
			if ( wrap ) { wrap.hidden = true; }
			target.innerHTML = '<option value=""></option>';
			custom.classList.add( 'hidden' );
			return;
		}
		if ( wrap && kind === 'doc' ) { wrap.hidden = false; }

		var info = catalog[ pid ] || { models: [], default: '', doc_default: '', doc_mode: 'images' };
		var current = target.dataset.provider === pid ? target.dataset.current : '';
		var def = kind === 'doc' ? info.doc_default : info.default;
		var models = info.models.slice();

		// Đọc công văn bằng ảnh: model đọc ảnh lên trước.
		if ( kind === 'doc' && info.doc_mode === 'images' ) {
			models.sort( function ( a, b ) { return ( b.vision === true ) - ( a.vision === true ); } );
		}

		target.innerHTML = '';
		var optDefault = document.createElement( 'option' );
		optDefault.value = '';
		optDefault.textContent = 'Mặc định (' + def + ')';
		target.appendChild( optDefault );

		var found = current === '';
		models.forEach( function ( m ) {
			var o = document.createElement( 'option' );
			o.value = m.id;
			o.textContent = m.id + ( m.vision === true ? '  ★ đọc ảnh' : '' );
			if ( m.id === current ) { o.selected = true; found = true; }
			target.appendChild( o );
		} );

		var optCustom = document.createElement( 'option' );
		optCustom.value = '__custom';
		optCustom.textContent = 'Nhập tên model khác…';
		target.appendChild( optCustom );

		if ( ! found ) {
			optCustom.selected = true;
			custom.value = current;
		}
		custom.classList.toggle( 'hidden', target.value !== '__custom' );
	}

	[ 'ai_provider', 'ai_doc_provider' ].forEach( function ( id ) {
		var sel = document.getElementById( id );
		if ( ! sel ) { return; }
		var target = document.getElementById( sel.dataset.modelTarget );
		target.dataset.provider = sel.value;
		fill( sel );
		sel.addEventListener( 'change', function () { fill( sel ); } );
		target.addEventListener( 'change', function () {
			document.getElementById( target.id + '_custom' ).classList.toggle( 'hidden', target.value !== '__custom' );
		} );
	} );
}() );
</script>
<?php endif; ?>

<?php get_footer(); ?>
