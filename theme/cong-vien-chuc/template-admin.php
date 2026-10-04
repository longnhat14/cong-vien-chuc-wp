<?php
/**
 * CÔNG VIÊN CHỨC — Khu quản trị nội dung (Phase 13)
 * URL: /quan-tri/ (tổng quan), /quan-tri/{loai}/ (danh sách),
 *      /quan-tri/{loai}/moi/ (thêm mới), /quan-tri/{loai}/{id}/ (sửa),
 *      /quan-tri/thanh-toan/ (cấu hình thanh toán).
 *
 * Chỉ nhân sự vận hành (có vai trò khác USER). Menu/nút ẩn hiện theo
 * permission; Laravel kiểm tra lại quyền ở mọi API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_require_login();

if ( ! cvc_admin_is_staff() ) {
	status_header( 403 );
	cvc_seo_set_noindex();
	cvc_seo_set_title( 'Không có quyền truy cập' );
	get_header();
	?>
	<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-12">
		<div class="max-w-3xl mx-auto px-4 space-y-4">
			<?php cvc_render_notfound_state( 'Tài khoản của bạn không có quyền vào khu quản trị.' ); ?>
			<p><a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_account_url() ); ?>">&larr; Về trang tài khoản</a></p>
		</div>
	</main>
	<?php
	get_footer();
	return;
}

$resource_key = sanitize_key( (string) get_query_var( 'cvc_admin_resource' ) );
$item_param   = (string) get_query_var( 'cvc_admin_item' );
$res          = '' !== $resource_key ? cvc_admin_resource( $resource_key ) : null;
$view         = 'dashboard';

if ( 'thanh-toan' === $resource_key ) {
	$view = 'payment';
} elseif ( 'thu-thap' === $resource_key ) {
	$view = cvc_admin_can( 'recruitment.view' ) ? 'ingestion' : 'forbidden';
} elseif ( '' !== $resource_key ) {
	if ( ! $res || ! cvc_admin_can( $res['module'] . '.view' ) ) {
		$view = 'forbidden';
	} elseif ( 'moi' === $item_param ) {
		$view = cvc_admin_can_resource( $res, 'create' ) ? 'form' : 'forbidden';
	} elseif ( '' !== $item_param ) {
		$view = 'form';
	} else {
		$view = 'list';
	}
}

$item_id   = ctype_digit( $item_param ) ? (int) $item_param : 0;
$is_create = 'form' === $view && 0 === $item_id;
$item      = array();
$load_err  = '';

if ( 'form' === $view && ! $is_create ) {
	$result = cvc_admin_api_get( $res['endpoint'] . '/' . $item_id );
	if ( $result['ok'] && is_array( $result['data']['data'] ?? null ) ) {
		$item = $result['data']['data'];
	} else {
		$load_err = 404 === (int) $result['status'] ? 'Không tìm thấy bản ghi này.' : cvc_admin_error_message( $result );
	}
}

$page_title = 'Quản trị';
if ( 'list' === $view || 'form' === $view ) {
	$page_title = $res['label'] . ( 'form' === $view ? ( $is_create ? ' — Thêm mới' : ' — ' . cvc_admin_item_title( $res, $item ) ) : '' );
} elseif ( 'payment' === $view ) {
	$page_title = 'Cấu hình thanh toán';
} elseif ( 'ingestion' === $view ) {
	$page_title = 'Thu thập tin tuyển dụng';
}

cvc_seo_set_title( $page_title . ' | Quản trị' );
cvc_seo_set_noindex();

$cvc_admin_user = cvc_current_user();

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
<div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start">

	<aside class="lg:sticky lg:top-24 space-y-3">
		<details class="lg:open bg-[#0A192F] border border-slate-800 rounded-2xl p-4" open>
			<summary class="cursor-pointer list-none flex items-center justify-between text-xs font-black text-amber-400 uppercase tracking-wider">
				<span><i class="fa-solid fa-gauge-high mr-1.5" aria-hidden="true"></i> Khu quản trị</span>
				<span class="lg:hidden text-slate-400" aria-hidden="true">▾</span>
			</summary>
			<nav class="mt-3 space-y-4 text-sm" aria-label="Menu quản trị">
				<a href="<?php echo esc_url( cvc_admin_url() ); ?>" class="flex items-center gap-2 px-2.5 py-2 rounded-lg <?php echo 'dashboard' === $view ? 'bg-cyan-500/15 text-cyan-200 font-bold' : 'text-slate-300 hover:bg-slate-800'; ?>"><i class="fa-solid fa-chart-pie w-4" aria-hidden="true"></i> Tổng quan</a>
				<?php if ( cvc_admin_can( 'recruitment.view' ) ) : ?>
					<a href="<?php echo esc_url( cvc_admin_url( 'thu-thap' ) ); ?>" class="flex items-center gap-2 px-2.5 py-2 rounded-lg <?php echo 'ingestion' === $view ? 'bg-cyan-500/15 text-cyan-200 font-bold' : 'text-slate-300 hover:bg-slate-800'; ?>"><i class="fa-solid fa-robot w-4" aria-hidden="true"></i> Thu thập tin</a>
				<?php endif; ?>
				<?php foreach ( cvc_admin_menu_groups() as $group_label => $keys ) : ?>
					<?php
					$visible = array_filter( $keys, static function ( $k ) {
						$r = cvc_admin_resource( $k );
						return $r && cvc_admin_can( $r['module'] . '.view' );
					} );
					if ( empty( $visible ) ) {
						continue;
					}
					?>
					<div>
						<p class="px-2.5 text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1"><?php echo esc_html( $group_label ); ?></p>
						<?php foreach ( $visible as $k ) : ?>
							<?php $r = cvc_admin_resource( $k ); ?>
							<a href="<?php echo esc_url( cvc_admin_url( $k ) ); ?>" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg <?php echo $k === $resource_key ? 'bg-cyan-500/15 text-cyan-200 font-bold' : 'text-slate-300 hover:bg-slate-800'; ?>" <?php echo $k === $resource_key ? 'aria-current="page"' : ''; ?>>
								<i class="fa-solid <?php echo esc_attr( $r['icon'] ); ?> w-4 text-slate-400" aria-hidden="true"></i> <?php echo esc_html( $r['label'] ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
				<?php if ( cvc_admin_can( 'settings.view' ) ) : ?>
					<div>
						<p class="px-2.5 text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Cấu hình</p>
						<a href="<?php echo esc_url( cvc_admin_url( 'thanh-toan' ) ); ?>" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg <?php echo 'payment' === $view ? 'bg-cyan-500/15 text-cyan-200 font-bold' : 'text-slate-300 hover:bg-slate-800'; ?>"><i class="fa-solid fa-credit-card w-4 text-slate-400" aria-hidden="true"></i> Thanh toán</a>
						<a href="<?php echo esc_url( cvc_admin_ai_settings_url() ); ?>" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800"><i class="fa-solid fa-robot w-4 text-slate-400" aria-hidden="true"></i> AI giải thích</a>
					</div>
				<?php endif; ?>
			</nav>
		</details>
		<p class="text-[11px] text-slate-500 px-1">Đăng nhập: <?php echo esc_html( (string) ( $cvc_admin_user['name'] ?? '' ) ); ?> · <?php echo esc_html( implode( ', ', (array) ( $cvc_admin_user['roles'] ?? array() ) ) ); ?></p>
	</aside>

	<div class="min-w-0 space-y-5">
		<?php cvc_render_notice(); ?>

		<?php if ( 'forbidden' === $view ) : ?>
			<?php cvc_render_notfound_state( 'Không có mục này hoặc bạn không có quyền xem.' ); ?>

		<?php elseif ( 'dashboard' === $view ) : ?>
			<?php
			$dash = cvc_admin_api_get( '/api/admin/dashboard' );
			$d    = $dash['ok'] ? ( $dash['data']['data'] ?? array() ) : array();
			$sum  = static fn ( $arr ) => array_sum( array_map( 'intval', (array) $arr ) );
			$tiles = array(
				array( 'Tin tuyển dụng đang mở', $d['recruitments']['open'] ?? null, 'recruitments', 'Tổng ' . $sum( $d['recruitments']['by_status'] ?? array() ) . ' tin' ),
				array( 'Câu hỏi đã xuất bản', $d['questions']['by_status']['published'] ?? null, 'questions', ( (int) ( $d['questions']['by_status']['draft'] ?? 0 ) ) . ' câu nháp' ),
				array( 'Đề thi', $sum( $d['exams']['by_status'] ?? array() ), 'exams', ( (int) ( $d['exam_attempts_7_days'] ?? 0 ) ) . ' lượt thi 7 ngày qua' ),
				array( 'Khóa học', $sum( $d['courses']['by_status'] ?? array() ), 'courses', ( (int) ( $d['courses']['by_status']['published'] ?? 0 ) ) . ' đã xuất bản' ),
				array( 'Bài kiến thức', $sum( $d['knowledge_items']['by_status'] ?? array() ), 'knowledge-items', ( (int) ( $d['knowledge_items']['by_status']['published'] ?? 0 ) ) . ' đã xuất bản' ),
				array( 'Văn bản pháp luật', $d['legal_documents']['public'] ?? null, 'legal-documents', 'đang hiện công khai' ),
				array( 'Người dùng', $d['users']['total'] ?? null, 'users', '+' . ( (int) ( $d['users']['last_7_days'] ?? 0 ) ) . ' trong 7 ngày' ),
				array( 'Doanh thu 30 ngày', isset( $d['orders']['revenue_30_days'] ) ? number_format( (float) $d['orders']['revenue_30_days'], 0, ',', '.' ) . 'đ' : null, 'orders', ( (int) ( $d['orders']['paid_30_days'] ?? 0 ) ) . ' đơn đã thanh toán' ),
			);
			?>
			<header>
				<h1 class="text-2xl font-black text-white">Tổng quan</h1>
				<p class="text-sm text-slate-400">Số liệu trực tiếp từ hệ thống.</p>
			</header>
			<?php if ( ! $dash['ok'] ) : ?>
				<?php cvc_render_error_state( cvc_admin_error_message( $dash ) ); ?>
			<?php else : ?>
				<dl class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
					<?php foreach ( $tiles as $tile ) : ?>
						<?php $tile_res = cvc_admin_resource( $tile[2] ); ?>
						<div class="bg-[#0A192F] border border-slate-800 rounded-2xl p-4">
							<dt class="text-[11px] font-bold uppercase tracking-wider text-slate-400"><?php echo esc_html( $tile[0] ); ?></dt>
							<dd class="text-2xl font-black text-white tabular-nums mt-1"><?php echo esc_html( null === $tile[1] ? '—' : ( is_numeric( $tile[1] ) ? number_format_i18n( (float) $tile[1] ) : (string) $tile[1] ) ); ?></dd>
							<dd class="text-xs text-slate-400 mt-0.5 flex justify-between gap-2">
								<span><?php echo esc_html( $tile[3] ); ?></span>
								<?php if ( $tile_res && cvc_admin_can( $tile_res['module'] . '.view' ) ) : ?>
									<a class="text-cyan-300 font-bold shrink-0" href="<?php echo esc_url( cvc_admin_url( $tile[2] ) ); ?>">Xem</a>
								<?php endif; ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>

				<?php
				$todo = array();
				if ( (int) ( $d['recruitments']['by_status']['review'] ?? 0 ) > 0 ) {
					$todo[] = array( (int) $d['recruitments']['by_status']['review'] . ' tin tuyển dụng đang chờ duyệt', cvc_admin_url( 'recruitments', null, array( 'status' => 'review' ) ) );
				}
				if ( (int) ( $d['knowledge_items']['by_status']['draft'] ?? 0 ) > 0 ) {
					$todo[] = array( (int) $d['knowledge_items']['by_status']['draft'] . ' bài kiến thức còn ở dạng nháp', cvc_admin_url( 'knowledge-items', null, array( 'status' => 'draft' ) ) );
				}
				if ( (int) ( $d['questions']['by_status']['draft'] ?? 0 ) > 0 ) {
					$todo[] = array( (int) $d['questions']['by_status']['draft'] . ' câu hỏi nháp', cvc_admin_url( 'questions', null, array( 'status' => 'draft' ) ) );
				}
				if ( (int) ( $d['orders']['pending'] ?? 0 ) > 0 ) {
					$todo[] = array( (int) $d['orders']['pending'] . ' đơn hàng chờ thanh toán', cvc_admin_url( 'orders', null, array( 'status' => 'pending' ) ) );
				}
				?>
				<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
					<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5" aria-labelledby="cvc-admin-todo">
						<h2 id="cvc-admin-todo" class="text-sm font-black text-white mb-3">Cần xử lý</h2>
						<?php if ( empty( $todo ) ) : ?>
							<p class="text-sm text-slate-400">Không có việc tồn đọng.</p>
						<?php else : ?>
							<ul class="space-y-2 text-sm">
								<?php foreach ( $todo as $t ) : ?>
									<li><a class="text-amber-300 hover:underline" href="<?php echo esc_url( $t[1] ); ?>"><?php echo esc_html( $t[0] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</section>
					<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5" aria-labelledby="cvc-admin-audit">
						<h2 id="cvc-admin-audit" class="text-sm font-black text-white mb-3">Thao tác gần đây</h2>
						<?php if ( empty( $d['recent_audit'] ) ) : ?>
							<p class="text-sm text-slate-400">Chưa có thao tác nào được ghi nhận.</p>
						<?php else : ?>
							<ul class="space-y-1.5 text-xs text-slate-300">
								<?php foreach ( (array) $d['recent_audit'] as $log ) : ?>
									<li class="flex justify-between gap-3">
										<span><strong class="text-slate-100"><?php echo esc_html( (string) ( $log['user_name'] ?? 'Hệ thống' ) ); ?></strong> · <?php echo esc_html( (string) $log['action'] . ' ' . (string) $log['entity_type'] . ' #' . (string) $log['entity_id'] ); ?></span>
										<span class="text-slate-500 shrink-0"><?php echo esc_html( cvc_admin_format( $log['created_at'] ?? null, 'datetime' ) ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</section>
				</div>
			<?php endif; ?>

		<?php elseif ( 'ingestion' === $view ) : ?>
			<?php
			if ( ctype_digit( $item_param ) ) {
				cvc_admin_render_ingestion_detail( (int) $item_param );
			} else {
				cvc_admin_render_ingestion_index();
			}
			?>

		<?php elseif ( 'payment' === $view ) : ?>
			<?php
			if ( ! cvc_admin_can( 'settings.view' ) ) {
				cvc_render_notfound_state( 'Bạn không có quyền xem cấu hình.' );
			} else {
				$settings = cvc_admin_api_get( '/api/admin/settings' );
				$groups   = $settings['ok'] ? ( $settings['data']['data'] ?? array() ) : array();
				$schema   = array(
					'vnpay' => array(
						'title'  => 'VNPay',
						'fields' => array(
							'vnpay.mode'        => array( 'Môi trường', 'select', array( 'sandbox' => 'Sandbox (thử nghiệm)', 'production' => 'Production (thật)' ) ),
							'vnpay.tmn_code'    => array( 'Mã website (TMN Code)', 'text' ),
							'vnpay.hash_secret' => array( 'Chuỗi bí mật (Hash Secret)', 'secret' ),
						),
					),
					'momo'  => array(
						'title'  => 'MoMo',
						'fields' => array(
							'momo.partner_code' => array( 'Partner Code', 'text' ),
							'momo.access_key'   => array( 'Access Key', 'secret' ),
							'momo.secret_key'   => array( 'Secret Key', 'secret' ),
						),
					),
				);
				?>
				<header>
					<h1 class="text-2xl font-black text-white">Cấu hình thanh toán</h1>
					<p class="text-sm text-slate-400">Khóa bí mật được mã hóa khi lưu và không hiển thị lại. Để trống ô bí mật nếu không muốn đổi.</p>
				</header>
				<?php if ( ! $settings['ok'] ) : ?>
					<?php cvc_render_error_state( cvc_admin_error_message( $settings ) ); ?>
				<?php endif; ?>
				<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
					<?php foreach ( $schema as $group => $conf ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-4">
							<?php wp_nonce_field( 'cvc_admin_payment_settings' ); ?>
							<input type="hidden" name="action" value="cvc_admin_payment_settings">
							<input type="hidden" name="group" value="<?php echo esc_attr( $group ); ?>">
							<h2 class="text-base font-black text-white"><?php echo esc_html( $conf['title'] ); ?></h2>
							<?php foreach ( $conf['fields'] as $key => $f ) : ?>
								<?php
								$current = $groups[ $group ][ $key ] ?? array();
								$fid     = str_replace( '.', '__', $key );
								?>
								<div class="space-y-1.5">
									<label for="<?php echo esc_attr( $fid ); ?>" class="block text-xs font-bold text-slate-300"><?php echo esc_html( $f[0] ); ?></label>
									<?php if ( 'select' === $f[1] ) : ?>
										<select id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $fid ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
											<?php foreach ( $f[2] as $ov => $ol ) : ?>
												<option value="<?php echo esc_attr( $ov ); ?>" <?php selected( (string) ( $current['value'] ?? '' ), $ov ); ?>><?php echo esc_html( $ol ); ?></option>
											<?php endforeach; ?>
										</select>
									<?php elseif ( 'secret' === $f[1] ) : ?>
										<input type="password" id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $fid ); ?>" autocomplete="new-password" placeholder="<?php echo esc_attr( ! empty( $current['configured'] ) ? 'Đã lưu (' . (string) ( $current['value'] ?? '' ) . ') — để trống nếu giữ nguyên' : 'Chưa cấu hình' ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
									<?php else : ?>
										<input type="text" id="<?php echo esc_attr( $fid ); ?>" name="<?php echo esc_attr( $fid ); ?>" value="<?php echo esc_attr( (string) ( $current['value'] ?? '' ) ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
							<?php if ( cvc_admin_can( 'settings.update' ) ) : ?>
								<button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-sm rounded-xl">Lưu <?php echo esc_html( $conf['title'] ); ?></button>
							<?php endif; ?>
						</form>
					<?php endforeach; ?>
				</div>
				<?php
			}
			?>

		<?php elseif ( 'list' === $view ) : ?>
			<?php
			$q       = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
			$paged   = isset( $_GET['trang'] ) ? max( 1, absint( $_GET['trang'] ) ) : 1;
			$query   = array( 'search' => $q, 'page' => $paged, 'per_page' => 25 );
			$current_filters = array();
			foreach ( (array) ( $res['filters'] ?? array() ) as $fkey => $fconf ) {
				$fval = isset( $_GET[ $fkey ] ) ? sanitize_text_field( wp_unslash( $_GET[ $fkey ] ) ) : '';
				if ( '' !== $fval ) {
					$query[ $fkey ]            = $fval;
					$current_filters[ $fkey ] = $fval;
				}
			}
			// Bộ lọc theo bản ghi cha (từ danh sách con), ví dụ ?recruitment_id=12.
			foreach ( array( 'recruitment_id', 'exam_subject_id', 'topic_id', 'legal_document_id', 'course_id' ) as $fk ) {
				if ( isset( $_GET[ $fk ] ) && ! isset( $current_filters[ $fk ] ) ) {
					$query[ $fk ]            = absint( $_GET[ $fk ] );
					$current_filters[ $fk ] = (string) absint( $_GET[ $fk ] );
				}
			}
			$list       = cvc_admin_api_get( $res['endpoint'], $query );
			$pagination = $list['ok'] ? ( $list['data']['data'] ?? array() ) : array();
			$rows       = (array) ( $pagination['data'] ?? array() );
			$total      = (int) ( $pagination['total'] ?? 0 );
			$last_page  = (int) ( $pagination['last_page'] ?? 1 );
			$base_args  = array_merge( $current_filters, '' !== $q ? array( 'q' => $q ) : array() );
			?>
			<header class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
				<div>
					<h1 class="text-2xl font-black text-white"><?php echo esc_html( $res['label'] ); ?></h1>
					<p class="text-sm text-slate-400"><?php echo esc_html( $list['ok'] ? number_format_i18n( $total ) . ' bản ghi' . ( empty( $base_args ) ? '' : ' khớp bộ lọc' ) : '' ); ?></p>
				</div>
				<?php if ( cvc_admin_can_resource( $res, 'create' ) ) : ?>
					<a href="<?php echo esc_url( cvc_admin_url( $resource_key, 'new', array_intersect_key( $current_filters, array_flip( array( 'recruitment_id', 'exam_subject_id', 'topic_id', 'legal_document_id', 'course_id' ) ) ) ) ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-sm rounded-xl self-start">
						<i class="fa-solid fa-plus" aria-hidden="true"></i> Thêm <?php echo esc_html( $res['singular'] ); ?>
					</a>
				<?php endif; ?>
			</header>

			<form method="get" action="<?php echo esc_url( cvc_admin_url( $resource_key ) ); ?>" class="flex flex-wrap items-end gap-2 bg-[#0A192F] border border-slate-800 rounded-2xl p-3" role="search">
				<div class="flex-1 min-w-[200px]">
					<label for="cvc-admin-q" class="sr-only">Tìm kiếm</label>
					<input type="search" id="cvc-admin-q" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Tìm theo tên, mã..." class="<?php echo esc_attr( cvc_admin_input_class() ); ?>">
				</div>
				<?php foreach ( (array) ( $res['filters'] ?? array() ) as $fkey => $fconf ) : ?>
					<?php $fopts = isset( $fconf['resource'] ) ? cvc_admin_relation_options( $fconf['resource'] ) : (array) $fconf['options']; ?>
					<div>
						<label for="cvc-admin-f-<?php echo esc_attr( $fkey ); ?>" class="sr-only"><?php echo esc_html( $fconf['label'] ); ?></label>
						<select id="cvc-admin-f-<?php echo esc_attr( $fkey ); ?>" name="<?php echo esc_attr( $fkey ); ?>" class="<?php echo esc_attr( cvc_admin_input_class() ); ?> max-w-[220px]">
							<option value=""><?php echo esc_html( $fconf['label'] . ': tất cả' ); ?></option>
							<?php foreach ( $fopts as $ov => $ol ) : ?>
								<option value="<?php echo esc_attr( (string) $ov ); ?>" <?php selected( (string) ( $current_filters[ $fkey ] ?? '' ), (string) $ov ); ?>><?php echo esc_html( $ol ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endforeach; ?>
				<?php foreach ( array( 'recruitment_id', 'exam_subject_id', 'topic_id', 'legal_document_id', 'course_id' ) as $fk ) : ?>
					<?php if ( isset( $current_filters[ $fk ] ) && ! isset( $res['filters'][ $fk ] ) ) : ?>
						<input type="hidden" name="<?php echo esc_attr( $fk ); ?>" value="<?php echo esc_attr( $current_filters[ $fk ] ); ?>">
					<?php endif; ?>
				<?php endforeach; ?>
				<button type="submit" class="px-4 py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black text-sm rounded-xl">Lọc</button>
				<?php if ( ! empty( $base_args ) ) : ?>
					<a href="<?php echo esc_url( cvc_admin_url( $resource_key ) ); ?>" class="px-3 py-2 text-sm text-cyan-300 font-bold">Xóa lọc</a>
				<?php endif; ?>
			</form>

			<?php if ( ! empty( $res['note'] ) ) : ?>
				<p class="text-xs text-slate-400"><?php echo esc_html( $res['note'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! $list['ok'] ) : ?>
				<?php cvc_render_error_state( cvc_admin_error_message( $list ) ); ?>
			<?php elseif ( empty( $rows ) ) : ?>
				<?php cvc_render_empty_state( 'Chưa có bản ghi nào' . ( empty( $base_args ) ? '.' : ' khớp bộ lọc.' ) ); ?>
			<?php else : ?>
				<div class="overflow-x-auto bg-[#0A192F] border border-slate-800 rounded-2xl">
					<table class="w-full text-sm">
						<thead>
							<tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
								<?php foreach ( $res['columns'] as $col ) : ?>
									<th scope="col" class="px-4 py-3 font-bold whitespace-nowrap"><?php echo esc_html( $col['label'] ); ?></th>
								<?php endforeach; ?>
								<th scope="col" class="px-4 py-3"><span class="sr-only">Thao tác</span></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rows as $row ) : ?>
								<?php $edit_url = cvc_admin_url( $resource_key, (int) $row['id'] ); ?>
								<tr class="border-b border-slate-800/70 hover:bg-slate-800/40 align-top">
									<?php foreach ( $res['columns'] as $col ) : ?>
										<?php
										$raw   = cvc_admin_get( $row, $col['key'] );
										$fmt   = $col['format'] ?? '';
										$text  = cvc_admin_format( $raw, $fmt );
										if ( ! empty( $col['trim'] ) ) {
											$text = wp_trim_words( $text, (int) $col['trim'] );
										}
										?>
										<td class="px-4 py-2.5 <?php echo in_array( $fmt, array( 'money' ), true ) || 'id' === $col['key'] ? 'tabular-nums whitespace-nowrap' : ''; ?>">
											<?php if ( 'status' === $fmt && null !== $raw ) : ?>
												<span class="inline-block text-[11px] font-bold px-2 py-0.5 rounded-full border <?php echo esc_attr( cvc_admin_status_class( $raw ) ); ?>"><?php echo esc_html( $text ); ?></span>
											<?php elseif ( ! empty( $col['link'] ) ) : ?>
												<a href="<?php echo esc_url( $edit_url ); ?>" class="font-bold text-white hover:text-cyan-300"><?php echo esc_html( $text ); ?></a>
											<?php else : ?>
												<span class="text-slate-300"><?php echo esc_html( $text ); ?></span>
											<?php endif; ?>
										</td>
									<?php endforeach; ?>
									<td class="px-4 py-2.5 text-right whitespace-nowrap">
										<a href="<?php echo esc_url( $edit_url ); ?>" class="text-xs font-bold text-cyan-300"><?php echo ( empty( $res['read_only'] ) && cvc_admin_can_resource( $res, 'update' ) ) ? 'Sửa' : 'Xem'; ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php if ( $last_page > 1 ) : ?>
					<nav class="flex items-center justify-between text-sm" aria-label="Phân trang">
						<span class="text-slate-400">Trang <?php echo esc_html( $paged . '/' . $last_page ); ?></span>
						<span class="flex gap-2">
							<?php if ( $paged > 1 ) : ?>
								<a class="px-3 py-1.5 rounded-lg border border-slate-700 text-slate-200" href="<?php echo esc_url( cvc_admin_url( $resource_key, null, array_merge( $base_args, array( 'trang' => $paged - 1 ) ) ) ); ?>">&laquo; Trước</a>
							<?php endif; ?>
							<?php if ( $paged < $last_page ) : ?>
								<a class="px-3 py-1.5 rounded-lg border border-slate-700 text-slate-200" href="<?php echo esc_url( cvc_admin_url( $resource_key, null, array_merge( $base_args, array( 'trang' => $paged + 1 ) ) ) ); ?>">Sau &raquo;</a>
							<?php endif; ?>
						</span>
					</nav>
				<?php endif; ?>
			<?php endif; ?>

		<?php elseif ( 'form' === $view ) : ?>
			<?php
			$can_save  = cvc_admin_can_resource( $res, $is_create ? 'create' : 'update' );
			$old_input = cvc_admin_take_old_input();
			$prefill   = array();
			if ( $is_create ) {
				foreach ( $res['fields'] as $field ) {
					if ( isset( $_GET[ $field['name'] ] ) ) {
						$prefill[ $field['name'] ] = sanitize_text_field( wp_unslash( $_GET[ $field['name'] ] ) );
					}
				}
			}
			$status    = (string) ( $item['status'] ?? '' );
			$public    = $is_create ? '' : cvc_admin_public_url( $res, $item );
			?>
			<nav class="text-xs text-slate-400" aria-label="Đường dẫn">
				<a class="hover:text-cyan-300" href="<?php echo esc_url( cvc_admin_url() ); ?>">Quản trị</a> /
				<a class="hover:text-cyan-300" href="<?php echo esc_url( cvc_admin_url( $resource_key ) ); ?>"><?php echo esc_html( $res['label'] ); ?></a>
			</nav>

			<?php if ( '' !== $load_err ) : ?>
				<?php cvc_render_error_state( $load_err ); ?>
			<?php else : ?>
				<header class="flex flex-col md:flex-row md:items-start justify-between gap-3">
					<div class="space-y-1 min-w-0">
						<h1 class="text-2xl font-black text-white break-words"><?php echo esc_html( $is_create ? 'Thêm ' . $res['singular'] : cvc_admin_item_title( $res, $item ) ); ?></h1>
						<?php if ( ! $is_create ) : ?>
							<p class="text-xs text-slate-400 flex flex-wrap items-center gap-2">
								<span>ID <?php echo (int) $item['id']; ?></span>
								<?php if ( '' !== $status ) : ?>
									<span class="text-[11px] font-bold px-2 py-0.5 rounded-full border <?php echo esc_attr( cvc_admin_status_class( $item['status'] ) ); ?>"><?php echo esc_html( cvc_admin_format( $item['status'], 'status' ) ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $item['updated_at'] ) ) : ?>
									<span>Cập nhật <?php echo esc_html( cvc_admin_format( $item['updated_at'], 'datetime' ) ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $public ) : ?>
									<a class="text-cyan-300 font-bold" href="<?php echo esc_url( $public ); ?>" target="_blank" rel="noopener">Xem trang công khai ↗</a>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</div>
					<?php if ( ! $is_create && ! empty( $res['actions'] ) ) : ?>
						<div class="flex flex-wrap gap-2">
							<?php foreach ( $res['actions'] as $act ) : ?>
								<?php if ( ! in_array( $status, $act['when'], true ) || ! cvc_admin_can( $act['perm'] ) ) { continue; } ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" <?php echo ! empty( $act['confirm'] ) ? 'data-cvc-confirm="' . esc_attr( $act['confirm'] ) . '"' : ''; ?>>
									<?php wp_nonce_field( 'cvc_admin_action' ); ?>
									<input type="hidden" name="action" value="cvc_admin_action">
									<input type="hidden" name="resource" value="<?php echo esc_attr( $resource_key ); ?>">
									<input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
									<input type="hidden" name="workflow" value="<?php echo esc_attr( $act['action'] ); ?>">
									<button type="submit" class="px-4 py-2 rounded-xl text-sm font-black <?php echo 'publish' === $act['action'] ? 'bg-emerald-500 hover:bg-emerald-400 text-navy-950' : 'bg-slate-800 hover:bg-slate-700 text-slate-100 border border-slate-600'; ?>"><?php echo esc_html( $act['label'] ); ?></button>
								</form>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</header>

				<?php if ( ! empty( $res['note'] ) ) : ?>
					<p class="text-xs text-slate-400"><?php echo esc_html( $res['note'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $res['fields'] ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-5" <?php echo ! empty( $res['multipart'] ) ? 'enctype="multipart/form-data"' : ''; ?> data-cvc-admin-form>
						<?php wp_nonce_field( 'cvc_admin_save' ); ?>
						<input type="hidden" name="action" value="cvc_admin_save">
						<input type="hidden" name="resource" value="<?php echo esc_attr( $resource_key ); ?>">
						<input type="hidden" name="id" value="<?php echo (int) ( $item['id'] ?? 0 ); ?>">
						<?php if ( $is_create && ! empty( $prefill ) ) : ?>
							<input type="hidden" name="prefill" value="<?php echo esc_attr( http_build_query( $prefill ) ); ?>">
						<?php endif; ?>
						<fieldset class="grid grid-cols-1 md:grid-cols-2 gap-4" <?php disabled( ! $can_save ); ?>>
							<legend class="sr-only">Thông tin <?php echo esc_html( $res['singular'] ); ?></legend>
							<?php foreach ( $res['fields'] as $field ) : ?>
								<?php
								if ( ! empty( $res['actions'] ) && 'status' === $field['name'] ) {
									continue;
								}
								if ( is_array( $old_input ) && array_key_exists( $field['name'], $old_input ) ) {
									$value = 'options' === $field['type'] ? array_values( (array) $old_input['options'] ) : $old_input[ $field['name'] ];
									if ( 'checkbox' === $field['type'] ) {
										$value = is_array( $value ) ? in_array( '1', $value, true ) : '1' === (string) $value;
									}
								} elseif ( $is_create ) {
									$value = $prefill[ $field['name'] ] ?? ( 'relation_multi' === $field['type'] ? array() : ( 'checkbox' === $field['type'] ? null : '' ) );
								} else {
									$value = cvc_admin_field_value( $field, $item );
								}
								cvc_admin_render_field( $field, $value, $is_create, $is_create ? array_merge( $prefill, (array) $old_input ) : $item );
								?>
							<?php endforeach; ?>
						</fieldset>
						<?php if ( $can_save ) : ?>
							<div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-800">
								<button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-navy-950 font-black text-sm rounded-xl"><?php echo $is_create ? 'Tạo mới' : 'Lưu thay đổi'; ?></button>
								<a href="<?php echo esc_url( cvc_admin_url( $resource_key ) ); ?>" class="text-sm text-slate-300">Hủy</a>
							</div>
						<?php else : ?>
							<p class="text-xs text-slate-400">Bạn chỉ có quyền xem mục này.</p>
						<?php endif; ?>
					</form>
				<?php endif; ?>

				<?php if ( ! $is_create ) : ?>
					<?php foreach ( (array) ( $res['panels'] ?? array() ) as $panel ) : ?>
						<?php get_template_part( 'inc/admin/panels/' . $panel, null, array( 'item' => $item, 'resource' => $resource_key ) ); ?>
					<?php endforeach; ?>

					<?php foreach ( (array) ( $res['children'] ?? array() ) as $child ) : ?>
						<?php
						$child_res = cvc_admin_resource( $child['resource'] );
						if ( ! $child_res || ! cvc_admin_can( $child_res['module'] . '.view' ) ) {
							continue;
						}
						$child_list = cvc_admin_api_get( $child_res['endpoint'], array( $child['fk'] => (int) $item['id'], 'per_page' => 50 ) );
						$child_rows = $child_list['ok'] ? (array) ( $child_list['data']['data']['data'] ?? array() ) : array();
						$child_tot  = $child_list['ok'] ? (int) ( $child_list['data']['data']['total'] ?? 0 ) : 0;
						?>
						<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-3" aria-label="<?php echo esc_attr( $child['label'] ); ?>">
							<div class="flex items-center justify-between gap-3">
								<h2 class="text-base font-black text-white"><?php echo esc_html( $child['label'] ); ?> <span class="text-slate-400 font-bold text-sm">(<?php echo esc_html( number_format_i18n( $child_tot ) ); ?>)</span></h2>
								<?php if ( cvc_admin_can_resource( $child_res, 'create' ) ) : ?>
									<a class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-100 text-xs font-bold rounded-lg" href="<?php echo esc_url( cvc_admin_url( $child['resource'], 'new', array( $child['fk'] => (int) $item['id'] ) ) ); ?>">+ Thêm</a>
								<?php endif; ?>
							</div>
							<?php if ( empty( $child_rows ) ) : ?>
								<p class="text-sm text-slate-400">Chưa có.</p>
							<?php else : ?>
								<ul class="divide-y divide-slate-800 text-sm">
									<?php foreach ( $child_rows as $crow ) : ?>
										<li class="py-2 flex items-center justify-between gap-3">
											<a class="text-slate-100 hover:text-cyan-300 font-semibold" href="<?php echo esc_url( cvc_admin_url( $child['resource'], (int) $crow['id'] ) ); ?>"><?php echo esc_html( cvc_admin_item_title( $child_res, $crow ) ); ?></a>
											<?php if ( isset( $crow['status'] ) ) : ?>
												<span class="text-[11px] font-bold px-2 py-0.5 rounded-full border <?php echo esc_attr( cvc_admin_status_class( $crow['status'] ) ); ?>"><?php echo esc_html( cvc_admin_format( $crow['status'], 'status' ) ); ?></span>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
								<?php if ( $child_tot > count( $child_rows ) ) : ?>
									<a class="text-xs text-cyan-300 font-bold" href="<?php echo esc_url( cvc_admin_url( $child['resource'], null, array( $child['fk'] => (int) $item['id'] ) ) ); ?>">Xem tất cả</a>
								<?php endif; ?>
							<?php endif; ?>
						</section>
					<?php endforeach; ?>

					<?php if ( cvc_admin_can_resource( $res, 'delete' ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="border border-rose-500/30 bg-rose-500/5 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3" data-cvc-confirm="<?php echo esc_attr( 'Xóa vĩnh viễn ' . $res['singular'] . ' này? Không thể hoàn tác.' ); ?>">
							<?php wp_nonce_field( 'cvc_admin_delete' ); ?>
							<input type="hidden" name="action" value="cvc_admin_delete">
							<input type="hidden" name="resource" value="<?php echo esc_attr( $resource_key ); ?>">
							<input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
							<div>
								<h2 class="text-sm font-black text-rose-200">Xóa <?php echo esc_html( $res['singular'] ); ?></h2>
								<p class="text-xs text-slate-400">Xóa vĩnh viễn. Nếu chỉ muốn ẩn khỏi trang công khai, hãy chuyển sang Nháp.</p>
							</div>
							<button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-black text-sm rounded-xl self-start">Xóa</button>
						</form>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
</div>
</main>

<script>
(function () {
	'use strict';
	var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var nonce = <?php echo wp_json_encode( wp_create_nonce( 'cvc_admin_ajax' ) ); ?>;

	function slugify(text) {
		return (text || '').toString().toLowerCase()
			.replace(/đ/g, 'd')
			.normalize('NFD').replace(/[̀-ͯ]/g, '')
			.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 180);
	}

	document.querySelectorAll('[data-cvc-slug-from]').forEach(function (slugEl) {
		var source = document.getElementById(slugEl.getAttribute('data-cvc-slug-from'));
		if (!source) { return; }
		var touched = slugEl.value !== '';
		slugEl.addEventListener('input', function () { touched = true; });
		source.addEventListener('input', function () {
			if (!touched) { slugEl.value = slugify(source.value); }
		});
	});

	document.querySelectorAll('[data-cvc-confirm]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			if (!window.confirm(form.getAttribute('data-cvc-confirm'))) { e.preventDefault(); }
		});
	});

	var province = document.querySelector('[data-cvc-province]');
	var unit = document.querySelector('[data-cvc-admin-unit]');
	if (province && unit) {
		province.addEventListener('change', function () {
			unit.innerHTML = '<option value="">Đang tải…</option>';
			fetch(ajaxUrl + '?action=cvc_admin_units&nonce=' + encodeURIComponent(nonce) + '&province_id=' + encodeURIComponent(province.value), { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					var html = '<option value="">— Chọn —</option>';
					(res && res.success ? res.data : []).forEach(function (u) {
						var opt = document.createElement('option');
						opt.value = u.id; opt.textContent = u.name;
						html += opt.outerHTML;
					});
					unit.innerHTML = html;
				})
				.catch(function () { unit.innerHTML = '<option value="">Không tải được danh sách</option>'; });
		});
	}

	document.querySelectorAll('[data-cvc-multi-filter]').forEach(function (input) {
		var box = document.getElementById(input.getAttribute('data-cvc-multi-filter'));
		input.addEventListener('input', function () {
			var q = slugify(input.value);
			box.querySelectorAll('[data-cvc-multi-item]').forEach(function (label) {
				label.hidden = q !== '' && slugify(label.textContent).indexOf(q) === -1;
			});
		});
	});

	document.querySelectorAll('[data-cvc-add-option]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var wrap = btn.previousElementSibling;
			var rows = wrap.querySelectorAll('[data-cvc-option-row]');
			if (!rows.length || rows.length >= 8) { return; }
			var clone = rows[rows.length - 1].cloneNode(true);
			var index = rows.length;
			clone.querySelectorAll('input').forEach(function (inp) {
				inp.name = inp.name.replace(/\[options\]\[\d+\]/, '[options][' + index + ']');
				if (inp.type === 'checkbox') { inp.checked = false; } else { inp.value = ''; }
			});
			clone.querySelector('[data-cvc-option-key]').textContent = String.fromCharCode(65 + index);
			wrap.appendChild(clone);
		});
	});
})();
</script>

<?php get_footer(); ?>
