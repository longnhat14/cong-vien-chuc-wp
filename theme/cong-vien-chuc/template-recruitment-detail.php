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
$total       = (int) ( $recruitment['total_positions'] ?? 0 );
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
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<header class="bg-gradient-to-r from-navy-950 via-navy-900 to-slate-900 p-6 sm:p-8 rounded-3xl border-2 <?php echo $is_open ? 'border-emerald-500/40' : 'border-slate-700'; ?> shadow-2xl relative overflow-hidden text-white space-y-4">
		<div class="flex flex-wrap items-center gap-2">
			<?php if ( $is_open ) : ?>
				<span class="bg-emerald-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Đang nhận hồ sơ</span>
			<?php else : ?>
				<span class="bg-slate-700 text-slate-200 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">Đã hết hạn nhận hồ sơ</span>
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

	<?php if ( ! $is_open ) : ?>
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
				<a href="#ho-so-on-thi" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400">Hồ sơ ôn thi</a>
				<a href="#mau-ho-so" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400">Mẫu phiếu đăng ký</a>
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
			</section>

			<section id="vi-tri" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4 shadow-xl">
				<h2 class="text-lg font-extrabold text-emerald-400 border-b border-slate-800 pb-3">Vị trí &amp; chỉ tiêu</h2>
				<?php if ( empty( $positions ) ) : ?>
					<p class="text-sm text-slate-400">Chưa có danh sách vị trí chi tiết cho đợt này - xem thông báo gốc.</p>
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
											! empty( $exam['exam_stage'] ) ? 'Vòng ' . $exam['exam_stage'] : null,
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

			<section id="mau-ho-so" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-sm text-slate-300">
				<h2 class="text-lg font-extrabold text-amber-400 border-b border-slate-800 pb-3">Mẫu phiếu đăng ký dự tuyển</h2>
				<p>Phiếu đăng ký dự tuyển công chức theo Mẫu số 01 ban hành kèm Nghị định 138/2020/NĐ-CP (viên chức: Nghị định 115/2020/NĐ-CP). Bản dưới đây là bản soạn lại để tham khảo - hãy đối chiếu với mẫu chính thức trong thông báo của cơ quan tuyển dụng trước khi nộp.</p>
				<div class="flex flex-wrap gap-2 text-xs">
					<a href="<?php echo esc_url( get_theme_file_uri( '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx' ) ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-azure-500 hover:bg-azure-600 text-white font-black rounded-xl"><i class="fa-solid fa-file-word"></i> Tải Mẫu 01 (.docx, tham khảo)</a>
					<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-amber-300 font-bold rounded-xl border border-amber-400/40">Kho tài liệu &amp; mẫu hồ sơ</a>
				</div>
			</section>

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

			<?php
			$majors = array_values( array_filter( array_map( fn ( $p ) => trim( (string) ( $p['major_requirements'] ?? '' ) ), $positions ) ) );
			if ( ! empty( $majors ) ) :
				?>
				<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs" data-cvc-major-check='<?php echo esc_attr( wp_json_encode( array_map( fn ( $p ) => array( 'name' => (string) ( $p['name'] ?? '' ), 'major' => (string) ( $p['major_requirements'] ?? '' ) ), array_filter( $positions, fn ( $p ) => ! empty( $p['major_requirements'] ) ) ) ) ); ?>'>
					<h2 class="font-extrabold uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-3">Đối chiếu chuyên ngành</h2>
					<p class="text-slate-300">Nhập chuyên ngành của bạn để so khớp với yêu cầu chuyên ngành đã công bố của từng vị trí.</p>
					<label for="cvc-major-input" class="sr-only">Chuyên ngành của bạn</label>
					<input type="text" id="cvc-major-input" placeholder="VD: Luật, Kế toán, Công nghệ thông tin" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-emerald-400">
					<button type="button" id="cvc-major-check-btn" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-navy-950 font-black rounded-xl">Đối chiếu</button>
					<div id="cvc-major-result" class="hidden space-y-1" aria-live="polite"></div>
					<p class="text-[10px] text-slate-500">Chỉ so khớp từ khóa, không phải kết luận đủ điều kiện - điều kiện chính thức theo thông báo của cơ quan tuyển dụng.</p>
				</div>
				<script>
				(function () {
					var box = document.querySelector('[data-cvc-major-check]');
					if (!box) return;
					var positions = JSON.parse(box.getAttribute('data-cvc-major-check') || '[]');
					var norm = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').trim(); };
					document.getElementById('cvc-major-check-btn').addEventListener('click', function () {
						var input = norm(document.getElementById('cvc-major-input').value);
						var out = document.getElementById('cvc-major-result');
						out.classList.remove('hidden');
						if (input.length < 2) { out.innerHTML = '<p class="text-amber-300">Vui lòng nhập chuyên ngành.</p>'; return; }
						var hits = positions.filter(function (p) {
							return norm(p.major).split(/[,;\/]+/).some(function (m) { m = m.trim(); return m && (m.indexOf(input) !== -1 || input.indexOf(m) !== -1); });
						});
						var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
						out.innerHTML = hits.length
							? '<p class="text-emerald-300 font-bold">Có ' + hits.length + ' vị trí ghi chuyên ngành khớp:</p><ul class="list-disc pl-4 text-slate-300">' + hits.map(function (p) { return '<li>' + esc(p.name) + '</li>'; }).join('') + '</ul>'
							: '<p class="text-slate-300">Không vị trí nào ghi chuyên ngành khớp với từ khóa này. Hãy thử cách viết khác hoặc đọc kỹ thông báo gốc.</p>';
					});
				})();
				</script>
			<?php endif; ?>

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
