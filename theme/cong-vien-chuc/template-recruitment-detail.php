<?php
/**
 * Chi tiết tin tuyển dụng + Recruitment Dossier (spec 2.2) - URL /tuyen-dung/{slug}/
 *
 * Viết lại 2026-10-03: CHỈ hiển thị dữ liệu thật từ GET /api/recruitments/{slug}.
 * Bản cũ có nhiều nội dung bịa: tin fixture hiện cho slug không tồn tại, vị
 * trí/điều kiện/thời lượng thi mẫu, badge "ĐANG TUYỂN" cho cả tin hết hạn,
 * file đính kèm dựng sẵn, "AI check bằng cấp" luôn trả "đủ điều kiện", giá
 * khóa học gắn cứng, phiếu Mẫu 01 tự điền dữ liệu cá nhân giả khi API
 * không tồn tại. Tất cả đã bỏ.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug        = sanitize_text_field( (string) get_query_var( 'cvc_recruitment_slug' ) );
$result      = ( new CVC_Recruitment_Service() )->find( $slug );
$recruitment = $result['ok'] && is_array( $result['data']['data'] ?? null ) ? $result['data']['data'] : null;

if ( null === $recruitment ) {
	status_header( 404 );
	cvc_seo_set_noindex();
	cvc_seo_set_title( 'Không tìm thấy tin tuyển dụng' );
	get_header();
	?>
	<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-12">
		<div class="max-w-3xl mx-auto px-4 space-y-4">
			<?php cvc_render_notfound_state( 'Không tìm thấy tin tuyển dụng này. Tin có thể đã được gỡ hoặc đường dẫn không đúng.' ); ?>
			<p><a class="text-amber-300 font-bold" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">&larr; Xem tất cả tin tuyển dụng</a></p>
		</div>
	</main>
	<?php
	get_footer();
	return;
}

$title       = (string) ( $recruitment['title'] ?? '' );
$summary     = trim( (string) ( $recruitment['summary'] ?? '' ) );
$agency      = is_array( $recruitment['agency'] ?? null ) ? $recruitment['agency'] : array();
$province    = is_array( $recruitment['province'] ?? null ) ? $recruitment['province'] : array();
$admin_unit  = is_array( $recruitment['admin_unit'] ?? null ) ? $recruitment['admin_unit'] : array();
$dates       = is_array( $recruitment['dates'] ?? null ) ? $recruitment['dates'] : array();
$positions   = is_array( $recruitment['positions'] ?? null ) ? $recruitment['positions'] : array();
$exams       = is_array( $recruitment['exams'] ?? null ) ? $recruitment['exams'] : array();
$courses     = is_array( $recruitment['courses'] ?? null ) ? $recruitment['courses'] : array();
$dossier     = is_array( $recruitment['dossier'] ?? null ) ? $recruitment['dossier'] : array();
$subjects    = is_array( $dossier['exam_subjects'] ?? null ) ? $dossier['exam_subjects'] : array();
$legal_docs  = is_array( $dossier['legal_documents'] ?? null ) ? $dossier['legal_documents'] : array();
$is_open     = ! empty( $recruitment['is_open'] );
$status      = (string) ( $recruitment['status'] ?? '' );
$source_url  = (string) ( $recruitment['source_url'] ?? '' );
$source_info = is_array( $recruitment['source'] ?? null ) ? $recruitment['source'] : array();
$attachments = is_array( $recruitment['attachments'] ?? null ) ? $recruitment['attachments'] : array();
$total       = (int) ( $recruitment['total_positions'] ?? 0 );
$stage       = (string) ( $recruitment['stage'] ?? '' );
$stage_label = (string) ( $recruitment['stage_label'] ?? '' );
$doc_number  = (string) ( $recruitment['doc_number'] ?? '' );
$round_docs  = is_array( $recruitment['round_documents'] ?? null ) ? $recruitment['round_documents'] : array();
$changes     = is_array( $recruitment['changes'] ?? null ) ? $recruitment['changes'] : array();
$study_plan  = is_array( $recruitment['study_plan'] ?? null ) ? $recruitment['study_plan'] : array();
$legal_basis = is_array( $recruitment['legal_basis'] ?? null ) ? $recruitment['legal_basis'] : array();
// De thi thu theo danh muc (RS-...) hien o muc lo trinh on, khong lap lai o danh sach de chung.
if ( ! empty( $study_plan ) ) {
	$exams = array_values( array_filter( $exams, fn ( $e ) => 0 !== strpos( (string) ( $e['code'] ?? '' ), 'RS-' ) ) );
}
$in_progress = ! $is_open && in_array( $stage, array( 'examining', 'result' ), true );
if ( 0 === $total ) {
	$total = array_sum( array_map( fn ( $p ) => (int) ( $p['quantity'] ?? 0 ), $positions ) );
}
$location    = implode( ', ', array_filter( array( $admin_unit['name'] ?? null, $province['name'] ?? null ) ) );

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Tuyển dụng', 'url' => cvc_recruitments_url() ),
	array( 'label' => wp_trim_words( $title, 8 ) ),
);

cvc_seo_set_title( $title );
cvc_seo_set_description( wp_trim_words( '' !== $summary ? $summary : $title, 28 ) );
cvc_seo_set_canonical( cvc_recruitment_url( $slug ) );
cvc_seo_set_og( array( 'type' => 'website' ) );
cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );

$job_posting = cvc_build_recruitment_job_posting_jsonld( $recruitment );
if ( null !== $job_posting ) {
	cvc_seo_add_json_ld( $job_posting );
}

$format_money = static fn ( $v ): string => number_format( (float) $v, 0, ',', '.' ) . 'đ';

get_header();
?>

<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-8">
<?php cvc_render_track_marker( 'recruitment_viewed', 'recruitment', (int) ( $recruitment['id'] ?? 0 ) ); ?>
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<header class="bg-gradient-to-r from-navy-950 via-navy-900 to-slate-900 p-6 sm:p-8 rounded-3xl border-2 <?php echo $is_open ? 'border-emerald-500/40' : 'border-slate-700'; ?> shadow-2xl relative overflow-hidden text-white space-y-4">
		<div class="flex flex-wrap items-center gap-2">
			<?php if ( $is_open ) : ?>
				<span class="bg-emerald-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Đang nhận hồ sơ</span>
			<?php elseif ( $in_progress ) : ?>
				<span class="bg-amber-400 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider"><?php echo esc_html( $stage_label ); ?></span>
				<span class="bg-slate-700 text-slate-200 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Đã hết hạn nhận hồ sơ</span>
			<?php else : ?>
				<span class="bg-slate-700 text-slate-200 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Đã hết hạn nhận hồ sơ</span>
			<?php endif; ?>
			<?php if ( '' !== $doc_number ) : ?>
				<span class="text-[10px] font-mono text-slate-300 border border-slate-700 px-2 py-0.5 rounded-full">Số <?php echo esc_html( $doc_number ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $recruitment['recruitment_type'] ) ) : ?>
				<span class="bg-azure-500/20 text-azure-300 border border-azure-400/40 text-[10px] font-black px-3 py-1 rounded-full uppercase"><?php echo esc_html( cvc_recruitment_type_label( (string) $recruitment['recruitment_type'] ) ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $recruitment['code'] ) ) : ?>
				<span class="text-[10px] font-mono text-slate-400">Mã tin: <?php echo esc_html( (string) $recruitment['code'] ); ?></span>
			<?php endif; ?>
		</div>

		<h1 class="text-2xl sm:text-4xl font-black text-white leading-tight"><?php echo esc_html( $title ); ?></h1>

		<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-800/80 text-xs text-slate-300">
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Cơ quan tuyển dụng</span>
				<strong class="text-amber-400 font-extrabold text-sm"><?php echo esc_html( $agency['name'] ?? '—' ); ?></strong>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Tổng chỉ tiêu</span>
				<strong class="text-emerald-400 font-extrabold text-sm"><?php echo $total > 0 ? esc_html( number_format( $total, 0, ',', '.' ) ) : '—'; ?></strong>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Ngày thông báo</span>
				<span class="font-semibold text-white"><?php echo esc_html( ! empty( $dates['announcement_date'] ) ? cvc_format_date_vn( $dates['announcement_date'] ) : '—' ); ?></span>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Hạn nộp hồ sơ</span>
				<strong class="<?php echo $is_open ? 'text-rose-300' : 'text-slate-400'; ?> font-extrabold text-sm"><?php echo esc_html( ! empty( $dates['application_deadline'] ) ? cvc_format_date_vn( $dates['application_deadline'] ) : 'Theo thông báo gốc' ); ?></strong>
			</div>
		</div>

		<div class="flex flex-wrap items-center gap-2 pt-2">
			<?php cvc_render_bookmark_button( 'recruitment', (int) ( $recruitment['id'] ?? 0 ) ); ?>
			<?php if ( $is_open ) : ?>
				<?php cvc_render_goal_quick_action( $title, array_filter( array( 'recruitment_id' => (int) ( $recruitment['id'] ?? 0 ), 'agency_id' => (int) ( $agency['id'] ?? 0 ), 'province_id' => (int) ( $province['id'] ?? 0 ) ) ) ); ?>
			<?php endif; ?>
			<?php if ( '' !== $source_url ) : ?>
				<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-amber-300 text-xs font-bold rounded-xl border border-amber-400/40">
					<i class="fa-solid fa-up-right-from-square"></i> Xem thông báo gốc
				</a>
			<?php endif; ?>
		</div>
	</header>

	<?php if ( $in_progress ) : ?>
		<div class="bg-amber-500/10 border border-amber-500/40 rounded-2xl p-4 text-sm text-amber-100 space-y-1">
			<p class="font-extrabold"><i class="fa-solid fa-hourglass-half mr-1.5" aria-hidden="true"></i><?php echo esc_html( 'result' === $stage ? 'Đợt tuyển dụng đã có kết quả.' : 'Đã hết hạn nhận phiếu đăng ký - đợt tuyển dụng đang được tổ chức.' ); ?></p>
			<p class="text-xs text-amber-100/80">Thí sinh đã nộp phiếu theo dõi các văn bản mới của đợt (danh sách, triệu tập, tài liệu ôn tập, lịch thi, kết quả) ở mục <a href="#van-ban-dot" class="underline font-bold">Văn bản của đợt tuyển</a>.</p>
		</div>
	<?php elseif ( ! $is_open ) : ?>
		<?php cvc_render_expired_state( 'expired' === $status ? 'Đợt tuyển dụng này đã hết hạn. Trang được giữ lại để tham khảo - vui lòng xem các tin đang nhận hồ sơ.' : 'Đã quá hạn nộp hồ sơ của đợt tuyển dụng này.' ); ?>
	<?php endif; ?>

	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

		<aside class="lg:col-span-3 space-y-5">
			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs">
				<h2 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-3">Đơn vị tuyển dụng</h2>
				<p class="font-black text-sm text-white"><?php echo esc_html( $agency['name'] ?? '—' ); ?></p>
				<?php if ( '' !== $location ) : ?>
					<p class="text-slate-300"><i class="fa-solid fa-location-dot text-amber-400 mr-1"></i><?php echo esc_html( $location ); ?></p>
				<?php endif; ?>
				<?php foreach ( array( 'address' => 'fa-map', 'phone' => 'fa-phone', 'email' => 'fa-envelope' ) as $field => $icon ) : ?>
					<?php if ( ! empty( $agency[ $field ] ) ) : ?>
						<p class="text-slate-300"><i class="fa-solid <?php echo esc_attr( $icon ); ?> text-amber-400 mr-1"></i><?php echo esc_html( (string) $agency[ $field ] ); ?></p>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if ( ! empty( $agency['website'] ) ) : ?>
					<a href="<?php echo esc_url( (string) $agency['website'] ); ?>" target="_blank" rel="noopener nofollow" class="block py-2 bg-slate-900 hover:bg-slate-800 text-amber-300 border border-amber-400/40 rounded-xl text-center font-bold">Website cơ quan</a>
				<?php endif; ?>
			</div>

			<nav class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-1.5 shadow-xl text-xs font-semibold text-slate-300" aria-label="Mục lục">
				<h2 class="font-extrabold text-xs text-azure-400 uppercase tracking-wider border-b border-slate-800 pb-3 mb-2">Mục lục</h2>
				<a href="#noi-dung" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400">Nội dung thông báo</a>
				<a href="#vi-tri" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400">Vị trí &amp; chỉ tiêu (<?php echo count( $positions ); ?>)</a>
				<?php if ( ! empty( $round_docs ) ) : ?>
					<a href="#van-ban-dot" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400">Văn bản của đợt tuyển (<?php echo count( $round_docs ); ?>)</a>
				<?php endif; ?>
				<?php if ( ! empty( $study_plan ) ) : ?>
					<a href="#lo-trinh-on" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 text-emerald-300">Lộ trình ôn theo danh mục</a>
				<?php endif; ?>
				<a href="#ho-so-on-thi" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400">Hồ sơ ôn thi</a>
				<a href="#chuan-bi-ho-so" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400">Chuẩn bị hồ sơ &amp; điểm ưu tiên</a>
			</nav>

			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs">
				<h2 class="font-extrabold text-xs text-emerald-400 uppercase tracking-wider border-b border-slate-800 pb-3">Mốc thời gian</h2>
				<ol class="space-y-3 relative pl-4 border-l border-slate-800">
					<?php
					$milestones = array(
						'announcement_date'      => 'Ngày thông báo',
						'application_start_date' => 'Bắt đầu nhận hồ sơ',
						'application_deadline'   => 'Hạn cuối nhận hồ sơ',
					);
					$has_milestone = false;
					foreach ( $milestones as $key => $label ) :
						if ( empty( $dates[ $key ] ) ) {
							continue;
						}
						$has_milestone = true;
						?>
						<li class="relative">
							<span class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
							<span class="text-slate-400 block text-[10px]"><?php echo esc_html( $label ); ?></span>
							<strong class="text-white"><?php echo esc_html( cvc_format_date_vn( $dates[ $key ] ) ); ?></strong>
						</li>
					<?php endforeach; ?>
					<?php if ( ! $has_milestone ) : ?>
						<li class="text-slate-400">Chưa có mốc thời gian - xem thông báo gốc.</li>
					<?php endif; ?>
				</ol>
			</div>
		</aside>

		<div class="lg:col-span-6 space-y-6">

			<section id="noi-dung" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4 shadow-xl">
				<h2 class="text-lg font-extrabold text-amber-400 border-b border-slate-800 pb-3">Nội dung thông báo</h2>
				<?php if ( '' !== $summary ) : ?>
					<div class="text-sm text-slate-300 leading-relaxed"><?php echo nl2br( esc_html( $summary ) ); ?></div>
				<?php else : ?>
					<p class="text-sm text-slate-400">Tin này chưa có phần tóm tắt nội dung. Điều kiện dự tuyển, hồ sơ và hình thức thi được quy định trong thông báo gốc của cơ quan tuyển dụng.</p>
				<?php endif; ?>
				<?php if ( '' !== $source_url ) : ?>
					<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-amber-300 text-xs font-bold rounded-xl border border-amber-400/40">
						<i class="fa-solid fa-up-right-from-square"></i> Đọc toàn văn thông báo trên cổng thông tin của cơ quan
					</a>
				<?php endif; ?>
				<?php if ( ! empty( $attachments ) || '' !== $source_url ) : ?>
					<div class="border-t border-slate-800 pt-4 space-y-3" id="van-ban-goc">
						<h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Nguồn trích dẫn &amp; văn bản gốc</h3>
						<?php if ( '' !== $source_url ) : ?>
							<p class="text-xs text-slate-400 break-all">
								Nguồn: <?php echo esc_html( (string) ( $source_info['name'] ?? ( wp_parse_url( $source_url, PHP_URL_HOST ) ?: '' ) ) ); ?> —
								<a class="text-amber-300 underline" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $source_url ); ?></a>
							</p>
						<?php endif; ?>
						<?php if ( ! empty( $attachments ) ) : ?>
							<ul class="space-y-2">
								<?php foreach ( $attachments as $att ) : ?>
									<li class="flex flex-wrap items-center justify-between gap-2 p-3 bg-slate-900 rounded-xl border border-slate-800 text-xs">
										<span class="text-slate-200 font-bold break-all"><i class="fa-solid fa-file-<?php echo 'pdf' === ( $att['extension'] ?? '' ) ? 'pdf text-rose-300' : 'word text-sky-300'; ?> mr-1.5"></i><?php echo esc_html( (string) ( $att['title'] ?? $att['filename'] ?? 'Công văn' ) ); ?></span>
										<span class="flex items-center gap-3 shrink-0">
											<span class="text-slate-500"><?php echo esc_html( strtoupper( (string) ( $att['extension'] ?? '' ) ) . ( ! empty( $att['size'] ) ? ' · ' . size_format( (int) $att['size'] ) : '' ) ); ?></span>
											<a class="text-emerald-300 font-bold" href="<?php echo esc_url( (string) ( $att['download_url'] ?? '' ) ); ?>" rel="nofollow">Tải bản lưu</a>
											<?php if ( ! empty( $att['original_url'] ) ) : ?>
												<a class="text-slate-400 underline" href="<?php echo esc_url( (string) $att['original_url'] ); ?>" target="_blank" rel="noopener nofollow">Link gốc</a>
											<?php endif; ?>
										</span>
									</li>
								<?php endforeach; ?>
							</ul>
							<p class="text-[11px] text-slate-500">Bản lưu được tải nguyên vẹn từ cổng thông tin của cơ quan để tra cứu khi trang gốc thay đổi. Văn bản có giá trị pháp lý là bản do cơ quan ban hành.</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $changes ) ) : ?>
				<section id="thay-doi" class="bg-rose-500/5 p-6 rounded-3xl border border-rose-500/30 space-y-3 shadow-xl">
					<h2 class="text-base font-extrabold text-rose-200"><i class="fa-solid fa-code-compare mr-1.5" aria-hidden="true"></i>Đã điều chỉnh so với thông báo ban đầu</h2>
					<ul class="space-y-2 text-sm text-slate-200">
						<?php foreach ( $changes as $ch ) : ?>
							<li>
								<strong><?php echo esc_html( (string) ( $ch['field_label'] ?? '' ) ); ?>:</strong>
								<?php if ( null !== ( $ch['old_value'] ?? null ) && '' !== (string) $ch['old_value'] ) : ?>
									<span class="line-through text-slate-400"><?php echo esc_html( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $ch['old_value'] ) ? cvc_format_date_vn( (string) $ch['old_value'] ) : (string) $ch['old_value'] ); ?></span> &rarr;
								<?php endif; ?>
								<strong class="text-emerald-300"><?php echo esc_html( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $ch['new_value'] ?? '' ) ) ? cvc_format_date_vn( (string) $ch['new_value'] ) : (string) ( $ch['new_value'] ?? '' ) ); ?></strong>
								<?php if ( ! empty( $ch['source_doc_number'] ) ) : ?>
									<span class="text-xs text-slate-400">(theo văn bản số <?php echo esc_html( (string) $ch['source_doc_number'] ); ?>)</span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="text-xs text-slate-400">Số liệu trên trang đã được cập nhật theo văn bản điều chỉnh mới nhất của cơ quan tuyển dụng.</p>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $round_docs ) ) : ?>
				<section id="van-ban-dot" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4 shadow-xl">
					<h2 class="text-lg font-extrabold text-azure-400 border-b border-slate-800 pb-3">Văn bản của đợt tuyển</h2>
					<ol class="relative pl-5 border-l border-slate-800 space-y-5">
						<?php foreach ( $round_docs as $rd ) : ?>
							<li class="relative">
								<span class="absolute -left-[27px] top-1 w-3 h-3 rounded-full <?php echo in_array( $rd['role'] ?? '', array( 'amendment', 'cancel' ), true ) ? 'bg-rose-400' : ( in_array( $rd['role'] ?? '', array( 'notice', 'plan' ), true ) ? 'bg-emerald-400' : 'bg-azure-400' ); ?>"></span>
								<div class="flex flex-wrap items-center gap-2 text-[11px]">
									<span class="font-black uppercase tracking-wider text-slate-300"><?php echo esc_html( (string) ( $rd['role_label'] ?? '' ) ); ?></span>
									<?php if ( ! empty( $rd['doc_number'] ) ) : ?><span class="font-mono text-amber-300"><?php echo esc_html( (string) $rd['doc_number'] ); ?></span><?php endif; ?>
									<?php if ( ! empty( $rd['issued_date'] ) ) : ?><span class="text-slate-500"><?php echo esc_html( cvc_format_date_vn( (string) $rd['issued_date'] ) ); ?></span><?php endif; ?>
								</div>
								<p class="text-sm text-white font-semibold leading-snug mt-0.5"><?php echo esc_html( (string) ( $rd['title'] ?? '' ) ); ?></p>
								<div class="flex flex-wrap gap-2 mt-1.5 text-xs">
									<?php foreach ( (array) ( $rd['files'] ?? array() ) as $f ) : ?>
										<a href="<?php echo esc_url( (string) ( $f['download_url'] ?? '#' ) ); ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700 hover:border-amber-400/60 text-slate-200"><i class="fa-solid fa-file-arrow-down text-amber-300" aria-hidden="true"></i><?php echo esc_html( wp_trim_words( (string) ( $f['filename'] ?? $f['title'] ?? 'Tệp' ), 8 ) ); ?> <span class="uppercase text-[10px] text-slate-400"><?php echo esc_html( (string) ( $f['extension'] ?? '' ) ); ?></span></a>
									<?php endforeach; ?>
									<?php if ( ! empty( $rd['source_url'] ) ) : ?>
										<a href="<?php echo esc_url( (string) $rd['source_url'] ); ?>" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-azure-300 hover:underline"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>Trang gốc</a>
									<?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
					<?php cvc_render_recruitment_legal_basis( $legal_basis ); ?>
				</section>
			<?php endif; ?>

			<section id="vi-tri" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4 shadow-xl">
				<h2 class="text-lg font-extrabold text-emerald-400 border-b border-slate-800 pb-3">Vị trí &amp; chỉ tiêu</h2>
				<?php if ( empty( $positions ) ) : ?>
					<p class="text-sm text-slate-400">Chưa có danh sách vị trí chi tiết cho đợt này - xem thông báo gốc.</p>
				<?php elseif ( ! empty( array_filter( array_column( $positions, 'level' ) ) ) ) : ?>
					<?php
					// Bieu vi tri x co quan (doc tu bieu chi tieu Excel): nhom theo cap -> tuyen chung / rieng.
					$groups = array();
					foreach ( $positions as $pos ) {
						$lv = (string) ( $pos['level'] ?? 'Khác' );
						$sc = (string) ( $pos['hiring_scope'] ?? '' );
						$groups[ $lv ][ $sc ][] = $pos;
					}
					$scope_labels = array( 'chung' => 'Vị trí tuyển dụng chung (nhiều cơ quan - thí sinh xếp thứ tự nguyện vọng)', 'rieng' => 'Vị trí tuyển dụng riêng', '' => 'Vị trí' );
					?>
					<p class="text-xs text-slate-400">Theo biểu chi tiết kèm văn bản của cơ quan tuyển dụng. Bấm vào vị trí để xem từng cơ quan và chỉ tiêu.</p>
					<div class="space-y-6">
						<?php foreach ( $groups as $lv => $by_scope ) : ?>
							<?php $lv_total = array_sum( array_map( fn ( $p ) => (int) ( $p['quantity'] ?? 0 ), array_merge( ...array_values( $by_scope ) ) ) ); ?>
							<div class="space-y-3">
								<h3 class="text-sm font-black text-white uppercase tracking-wider"><?php echo esc_html( $lv ); ?> <span class="text-emerald-300 normal-case font-bold">· <?php echo esc_html( (string) count( array_merge( ...array_values( $by_scope ) ) ) ); ?> vị trí, <?php echo esc_html( (string) $lv_total ); ?> chỉ tiêu</span></h3>
								<?php foreach ( $by_scope as $sc => $list ) : ?>
									<p class="text-[11px] font-bold text-slate-400"><?php echo esc_html( $scope_labels[ $sc ] ?? $sc ); ?></p>
									<div class="space-y-2">
										<?php foreach ( $list as $pos ) : ?>
											<?php $units = (array) ( $pos['units'] ?? array() ); ?>
											<details class="group bg-slate-900 rounded-2xl border border-slate-800 text-xs text-slate-300">
												<summary class="cursor-pointer list-none p-4 flex flex-wrap items-start justify-between gap-2">
													<span class="font-extrabold text-sm text-white leading-snug flex-1 min-w-0"><?php echo esc_html( (string) ( $pos['name'] ?? '' ) ); ?>
														<?php if ( ! empty( $pos['is_sensitive'] ) ) : ?><span class="ml-1 align-middle text-[10px] font-black px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-200 border border-rose-400/40">Cơ mật, trọng yếu</span><?php endif; ?>
													</span>
													<span class="flex items-center gap-2 shrink-0">
														<?php if ( count( $units ) > 1 ) : ?><span class="text-[10px] text-slate-400"><?php echo esc_html( (string) count( $units ) ); ?> cơ quan</span><?php endif; ?>
														<span class="bg-amber-500/20 text-amber-300 font-extrabold px-2.5 py-0.5 rounded-full border border-amber-400/30"><?php echo (int) ( $pos['quantity'] ?? 0 ); ?> chỉ tiêu</span>
													</span>
												</summary>
												<div class="px-4 pb-4 space-y-2">
													<?php if ( ! empty( $pos['education_level'] ) ) : ?><p><strong class="text-slate-200">Trình độ:</strong> <?php echo esc_html( (string) $pos['education_level'] ); ?></p><?php endif; ?>
													<?php if ( ! empty( $pos['major_requirements'] ) ) : ?><p><strong class="text-slate-200">Ngành, chuyên ngành:</strong> <?php echo esc_html( (string) $pos['major_requirements'] ); ?></p><?php endif; ?>
													<?php if ( ! empty( $units ) ) : ?>
														<ul class="divide-y divide-slate-800 border border-slate-800 rounded-xl">
															<?php foreach ( $units as $u ) : ?>
																<li class="flex items-start justify-between gap-3 px-3 py-2">
																	<span><?php echo esc_html( (string) ( $u['agency_name'] ?? '' ) ); ?><?php if ( ! empty( $u['note'] ) ) : ?> <span class="text-rose-300">(<?php echo esc_html( (string) $u['note'] ); ?>)</span><?php endif; ?></span>
																	<strong class="text-amber-300 shrink-0"><?php echo (int) ( $u['quota'] ?? 0 ); ?></strong>
																</li>
															<?php endforeach; ?>
														</ul>
													<?php endif; ?>
												</div>
											</details>
										<?php endforeach; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="space-y-4">
						<?php foreach ( $positions as $pos ) : ?>
							<article class="p-5 bg-slate-900 rounded-2xl border border-slate-800 space-y-2 text-xs text-slate-300">
								<div class="flex flex-wrap items-center justify-between gap-2">
									<h3 class="font-extrabold text-base text-white"><?php echo esc_html( (string) ( $pos['name'] ?? '' ) ); ?></h3>
									<?php if ( ! empty( $pos['quantity'] ) ) : ?>
										<span class="bg-amber-500/20 text-amber-300 font-extrabold px-3 py-1 rounded-full border border-amber-400/30">Chỉ tiêu: <?php echo (int) $pos['quantity']; ?></span>
									<?php endif; ?>
								</div>
								<?php
								$pos_fields = array(
									'job_description'         => 'Mô tả công việc',
									'requirements'            => 'Yêu cầu',
									'education_level'         => 'Trình độ',
									'major_requirements'      => 'Chuyên ngành',
									'experience_requirements' => 'Kinh nghiệm',
									'employment_type'         => 'Hình thức',
								);
								foreach ( $pos_fields as $field => $label ) :
									if ( empty( $pos[ $field ] ) ) {
										continue;
									}
									?>
									<p><strong class="text-slate-200"><?php echo esc_html( $label ); ?>:</strong> <?php echo esc_html( (string) $pos[ $field ] ); ?></p>
								<?php endforeach; ?>
								<?php if ( ! empty( $pos['exam_subjects'] ) ) : ?>
									<p><strong class="text-slate-200">Môn thi:</strong>
										<?php echo esc_html( implode( ', ', array_map( fn ( $s ) => (string) ( $s['name'] ?? '' ) . ( ! empty( $s['is_required'] ) ? ' (bắt buộc)' : '' ), $pos['exam_subjects'] ) ) ); ?>
									</p>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $study_plan ) ) { cvc_render_recruitment_study_plan( $study_plan ); } ?>

			<section id="ho-so-on-thi" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 shadow-xl">
				<div class="border-b border-slate-800 pb-3">
					<h2 class="text-lg font-extrabold text-cyan-400">Hồ sơ ôn thi cho đợt tuyển dụng này</h2>
					<p class="text-xs text-slate-400 mt-1">Tổng hợp từ đề thi, môn thi, chủ đề và văn bản đang gắn với đợt tuyển dụng trong hệ thống.</p>
				</div>

				<?php if ( empty( $exams ) && empty( $subjects ) && empty( $legal_docs ) ) : ?>
					<p class="text-sm text-slate-400">Đợt tuyển dụng này chưa được gắn đề thi hay môn thi. Bạn vẫn có thể luyện <a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_exams_url() ); ?>">đề thi trắc nghiệm</a> hoặc tra <a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">văn bản pháp luật</a>.</p>
				<?php endif; ?>

				<?php if ( ! empty( $exams ) ) : ?>
					<div class="space-y-2">
						<h3 class="text-sm font-bold text-white">Đề thi thử gắn với đợt này</h3>
						<div class="grid sm:grid-cols-2 gap-3">
							<?php foreach ( $exams as $exam ) : ?>
								<a href="<?php echo esc_url( cvc_exam_url( (string) ( $exam['slug'] ?? '' ) ) ); ?>" class="block p-4 bg-slate-900 rounded-2xl border border-slate-800 hover:border-cyan-400/60 transition-colors">
									<span class="block text-sm font-bold text-white leading-snug"><?php echo esc_html( (string) ( $exam['title'] ?? '' ) ); ?></span>
									<span class="block text-[11px] text-slate-400 mt-1">
										<?php echo esc_html( implode( ' · ', array_filter( array(
											! empty( $exam['total_questions'] ) ? (int) $exam['total_questions'] . ' câu' : null,
											! empty( $exam['duration_minutes'] ) ? (int) $exam['duration_minutes'] . ' phút' : null,
											! empty( $exam['exam_stage'] ) ? cvc_exam_stage_label( (string) $exam['exam_stage'] ) : null,
										) ) ) ); ?>
									</span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php foreach ( $subjects as $subject ) : ?>
					<details class="bg-slate-900 border border-slate-800 rounded-2xl group">
						<summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between gap-2 list-none">
							<span class="text-sm font-bold text-white">Môn thi: <?php echo esc_html( (string) ( $subject['name'] ?? '' ) ); ?></span>
							<span class="text-[11px] text-slate-400"><?php echo count( $subject['topics'] ?? array() ); ?> chủ đề</span>
						</summary>
						<div class="px-4 pb-4 space-y-3 text-xs text-slate-300 border-t border-slate-800 pt-3">
							<?php if ( ! empty( $subject['topics'] ) ) : ?>
								<div class="flex flex-wrap gap-1.5">
									<?php foreach ( $subject['topics'] as $topic ) : ?>
										<a href="<?php echo esc_url( cvc_topic_url( (string) ( $topic['slug'] ?? '' ) ) ); ?>" class="px-2.5 py-1 rounded-lg bg-navy-950 border border-slate-700 hover:border-cyan-400 text-slate-200"><?php echo esc_html( (string) ( $topic['name'] ?? '' ) ); ?></a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $subject['knowledge_items'] ) ) : ?>
								<p class="font-bold text-slate-200">Kiến thức trọng tâm:</p>
								<ul class="list-disc pl-5 space-y-0.5">
									<?php foreach ( array_slice( $subject['knowledge_items'], 0, 8 ) as $ki ) : ?>
										<li><a class="hover:text-cyan-300" href="<?php echo esc_url( cvc_knowledge_item_url( (string) ( $ki['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $ki['title'] ?? '' ) ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php if ( ! empty( $subject['sample_questions'] ) ) : ?>
								<p class="font-bold text-slate-200">Câu hỏi minh họa (đáp án có khi bạn làm đề):</p>
								<?php foreach ( $subject['sample_questions'] as $sq ) : ?>
									<div class="bg-navy-950 border border-slate-800 rounded-xl p-3 space-y-1">
										<p class="text-slate-100 font-semibold"><?php echo esc_html( (string) ( $sq['question_text'] ?? '' ) ); ?></p>
										<ul class="grid sm:grid-cols-2 gap-1 text-slate-400">
											<?php foreach ( array_values( (array) ( $sq['options'] ?? array() ) ) as $oi => $opt ) : ?>
												<li><?php echo esc_html( chr( 65 + $oi ) . '. ' . ( $opt['option_text'] ?? '' ) ); ?></li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
							<?php if ( ! empty( $subject['documents'] ) ) : ?>
								<p class="font-bold text-slate-200">Tài liệu ôn thi:</p>
								<div class="flex flex-wrap gap-2">
									<?php foreach ( $subject['documents'] as $doc ) : ?>
										<a href="<?php echo esc_url( cvc_document_url( (string) ( $doc['slug'] ?? '' ) ) ); ?>" class="px-2.5 py-1.5 rounded-lg bg-navy-950 border border-slate-700 hover:border-emerald-400">
											<?php echo esc_html( (string) ( $doc['title'] ?? '' ) ); ?>
											<span class="<?php echo ! empty( $doc['is_free'] ) ? 'text-emerald-300' : 'text-amber-300'; ?> font-black text-[10px] ml-1"><?php echo ! empty( $doc['is_free'] ) ? 'MIỄN PHÍ' : esc_html( $format_money( $doc['effective_price'] ?? 0 ) ); ?></span>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</details>
				<?php endforeach; ?>

				<?php if ( ! empty( $legal_docs ) ) : ?>
					<div class="space-y-2">
						<h3 class="text-sm font-bold text-white">Văn bản pháp luật liên quan</h3>
						<div class="flex flex-wrap gap-2 text-xs">
							<?php foreach ( $legal_docs as $ld ) : ?>
								<a href="<?php echo esc_url( cvc_legal_document_url( (string) ( $ld['slug'] ?? '' ) ) ); ?>" class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 hover:border-emerald-400 text-emerald-300">
									<?php echo esc_html( trim( ( $ld['document_number'] ?? '' ) . ' ' . ( $ld['title'] ?? '' ) ) ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</section>

			<?php cvc_render_candidate_kit( $recruitment ); ?>

			<p><a class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">&larr; Xem tất cả tin tuyển dụng</a></p>
		</div>

		<aside class="lg:col-span-3 space-y-5 lg:sticky lg:top-[80px]">
			<?php if ( ! empty( $courses ) ) : ?>
				<div class="bg-navy-950 p-6 rounded-3xl border-2 border-gold-500/40 space-y-4 shadow-2xl">
					<h2 class="text-xs font-extrabold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-3">Khóa ôn thi cho đợt này</h2>
					<?php foreach ( $courses as $course ) : ?>
						<?php
						$price = (float) ( $course['price'] ?? 0 );
						$sale  = isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : null;
						?>
						<a href="<?php echo esc_url( cvc_course_url( (string) ( $course['slug'] ?? '' ) ) ); ?>" class="block p-3.5 bg-slate-900 rounded-2xl border border-slate-800 hover:border-amber-400/60 space-y-1">
							<span class="block font-bold text-xs text-white leading-snug"><?php echo esc_html( (string) ( $course['title'] ?? '' ) ); ?></span>
							<span class="flex items-center justify-between text-xs">
								<?php if ( $price <= 0 ) : ?>
									<strong class="text-emerald-300">Miễn phí</strong>
								<?php elseif ( null !== $sale && $sale < $price ) : ?>
									<span class="text-slate-500 line-through"><?php echo esc_html( $format_money( $price ) ); ?></span>
									<strong class="text-amber-400"><?php echo esc_html( $format_money( $sale ) ); ?></strong>
								<?php else : ?>
									<strong class="text-amber-400"><?php echo esc_html( $format_money( $price ) ); ?></strong>
								<?php endif; ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $positions ) ) { cvc_render_eligibility_widget( (string) ( $recruitment['slug'] ?? '' ) ); } ?>

			<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs">
				<h2 class="font-extrabold uppercase tracking-wider text-azure-400 border-b border-slate-800 pb-3">Luyện thi</h2>
				<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block p-3 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 text-slate-200 font-semibold">Tất cả đề thi trắc nghiệm &rarr;</a>
				<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block p-3 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 text-slate-200 font-semibold">Tất cả khóa học &rarr;</a>
			</div>
		</aside>

	</div>
</div>
</main>

<?php get_footer(); ?>
