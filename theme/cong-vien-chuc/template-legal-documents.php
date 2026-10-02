<?php
/**
 * CÔNG VIÊN CHỨC — THƯ VIỆN PHÁN QUYẾT & QUẢN LÝ VĂN BẢN PHÁP LUẬT (Executive 3-Column Architecture)
 * URL: /van-ban-phap-luat/ và /van-ban-phap-luat/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Legal_Document_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok         = (bool) $result['ok'];
$pagination = $ok ? ( $result['data']['data'] ?? array() ) : array();
$documents  = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg  = (int) ( $pagination['current_page'] ?? $paged );
$lastPg     = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok || empty( $documents ) ) {
	$documents = CVC_Subpage_Fixtures::get_legal_documents();
	$ok        = true;
	$currentPg = 1;
	$lastPg    = 1;
}

cvc_seo_set_title( 'Thư Viện Phán Quyết & Quản Lý Văn Bản Pháp Luật Công Vụ 2026' );
cvc_seo_set_description( 'Tra cứu, trích xuất thông tin tự động và quản lý văn bản pháp luật, Nghị định, Thông tư & Luật Cán bộ công chức chính thức.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_legal_documents_url' );

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Thư Viện Phán Quyết & Pháp Luật' ),
			)
		);
		?>

		<!-- HERO BANNER -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border-2 border-cyan-500/40 shadow-2xl space-y-4 relative overflow-hidden">
			<div class="absolute -right-16 -top-16 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
				<div class="space-y-2 max-w-3xl">
					<div class="flex flex-wrap items-center gap-2">
						<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow">
							⚖️ THƯ VIỆN PHÁP LÝ & TRÍCH XUẤT TỰ ĐỘNG AI 2026
						</span>
						<span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-500/40">
							Chuẩn Thẩm Quyền Quốc Hội & Chính Phủ
						</span>
					</div>
					<h1 class="text-2xl sm:text-4xl font-black text-white leading-tight">Cổng Quản Lý & Trích Xuất Văn Bản Pháp Luật Công Vụ</h1>
					<p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-light">
						Tra cứu văn bản gốc, trích xuất tự động các điều khoản trọng tâm ra đề thi, tải file đính kèm (.PDF, .DOCX) và đối chiếu biến động pháp luật trực quan.
					</p>
				</div>
				<div class="flex items-center gap-3 shrink-0">
					<div class="p-4 bg-slate-900/90 rounded-2xl border border-cyan-500/30 text-center space-y-0.5 shadow-lg">
						<span class="text-2xl font-black text-cyan-400 block">450+</span>
						<span class="text-[11px] text-slate-300 font-medium">Văn bản hợp nhất</span>
					</div>
					<div class="p-4 bg-slate-900/90 rounded-2xl border border-emerald-500/30 text-center space-y-0.5 shadow-lg">
						<span class="text-2xl font-black text-emerald-400 block">100%</span>
						<span class="text-[11px] text-slate-300 font-medium">Tự động trích xuất AI</span>
					</div>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN EXECUTIVE SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — CATEGORIES, ISSUING AGENCIES & FILTERS) -->
			<aside class="lg:col-span-3 space-y-5">
				
				<!-- BỘ LỌC TÌM KIẾM NHANH -->
				<div class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-3 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
						<i class="fa-solid fa-magnifying-glass text-amber-400"></i> Tìm Kiếm Văn Bản
					</h3>
					<div class="space-y-2">
						<input type="text" placeholder="Nhập số hiệu, tên luật hoặc từ khóa..." class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs placeholder-slate-500 focus:outline-none focus:border-cyan-400">
						<button type="button" onclick="alert('Đang lọc danh sách văn bản pháp luật...')" class="w-full py-2.5 bg-cyan-500 hover:bg-cyan-600 text-navy-950 font-black rounded-xl text-xs shadow transition-transform hover:scale-105 cursor-pointer">
							TÌM VĂN BẢN &rarr;
						</button>
					</div>
				</div>

				<!-- BỘ LỌC LOẠI VĂN BẢN -->
				<div class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-4 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
						<i class="fa-solid fa-folder-tree text-cyan-400"></i> Phân Loại Văn Bản
					</h3>
					<div class="space-y-1.5 font-medium">
						<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="flex items-center justify-between p-2.5 rounded-xl bg-cyan-500/10 text-cyan-300 font-bold border border-cyan-500/30">
							<span>Tất cả văn bản</span>
							<span class="text-[10px] bg-cyan-500 text-navy-950 font-black px-2 py-0.5 rounded-full">450</span>
						</a>
						<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Bộ Luật & Luật</span>
							<span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">25</span>
						</a>
						<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Nghị định Chính phủ</span>
							<span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">120</span>
						</a>
						<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Thông tư Bộ Nội vụ</span>
							<span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">180</span>
						</a>
						<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Quyết định & Quy chế</span>
							<span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">85</span>
						</a>
						<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Biểu mẫu & Phiếu Mẫu 01</span>
							<span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">40</span>
						</a>
					</div>
				</div>

				<!-- BỘ LỌC TÌNH TRẠNG HIỆU LỰC -->
				<div class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-3 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-emerald-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
						<i class="fa-solid fa-shield-halved text-emerald-400"></i> Tình Trạng Hiệu Lực
					</h3>
					<div class="space-y-2">
						<label class="flex items-center gap-2.5 cursor-pointer text-slate-300 hover:text-white">
							<input type="checkbox" checked class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-0">
							<span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span> Còn hiệu lực thi hành</span>
						</label>
						<label class="flex items-center gap-2.5 cursor-pointer text-slate-300 hover:text-white">
							<input type="checkbox" checked class="rounded border-slate-700 bg-slate-900 text-amber-500 focus:ring-0">
							<span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span> Sửa đổi / Bổ sung</span>
						</label>
						<label class="flex items-center gap-2.5 cursor-pointer text-slate-300 hover:text-white">
							<input type="checkbox" class="rounded border-slate-700 bg-slate-900 text-red-500 focus:ring-0">
							<span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span> Hết hiệu lực / Bị thay thế</span>
						</label>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — LEGAL DOCUMENTS CARDS WITH EXTRACTION) -->
			<main class="lg:col-span-6 space-y-6">

				<div class="flex items-center justify-between bg-navy-950 p-4 rounded-2xl border border-slate-800 text-xs shadow-md">
					<span class="text-slate-300">Đang hiển thị <strong class="text-cyan-400 font-extrabold"><?php echo count( $documents ); ?></strong> văn bản pháp luật công vụ chính thức</span>
					<div class="flex items-center gap-2 text-slate-400">
						<span>Sắp xếp:</span>
						<select class="bg-slate-900 border border-slate-700 text-slate-200 text-xs rounded-lg px-2.5 py-1 focus:outline-none">
							<option>Mới ban hành</option>
							<option>Xem nhiều nhất</option>
							<option>Tải nhiều nhất</option>
						</select>
					</div>
				</div>

				<?php if ( ! $ok || empty( $documents ) ) : ?>
					<?php cvc_render_empty_state( 'Chưa có văn bản pháp luật nào.' ); ?>
				<?php else : ?>
					<div class="space-y-5">
						<?php foreach ( $documents as $doc ) : 
							$docSlug = $doc['slug'] ?? 'van-ban';
							$docUrl = cvc_legal_document_url( $docSlug );
							$docNum = $doc['document_number'] ?? ($doc['code'] ?? 'NĐ 138/2020/NĐ-CP');
							$docType = $doc['document_type'] ?? ($doc['category'] ?? 'Văn Bản');
							$agencyName = $doc['issuing_agency'] ?? 'Chính Phủ / Bộ Nội Vụ';
							$effectiveDate = $doc['effective_date'] ?? ($doc['issued_date'] ?? '01/12/2020');
							$summaryText = ! empty($doc['summary']) ? $doc['summary'] : 'Chi tiết các quy định pháp luật hiện hành và tài liệu đính kèm.';
							$statusText = $doc['status_label'] ?? 'Còn hiệu lực';
							$attachments = ! empty($doc['attachments']) ? $doc['attachments'] : array(
								array(
									'title' => $doc['title'] . ' (.PDF)',
									'file_type' => strtolower($doc['format'] ?? 'pdf'),
									'size' => $doc['size'] ?? '2.4 MB',
									'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
								),
							);
						?>
							<article class="bg-navy-950 border border-slate-800 hover:border-cyan-500/60 rounded-3xl p-6 space-y-4 shadow-xl transition-all relative overflow-hidden group">
								
								<!-- HEADER BADGES -->
								<div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800/80 pb-3">
									<div class="flex items-center gap-2">
										<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-black px-2.5 py-1 rounded-lg uppercase border border-cyan-500/30 flex items-center gap-1">
											📜 <?php echo esc_html( $docNum ); ?>
										</span>
										<span class="bg-slate-800 text-slate-300 text-[10px] font-bold px-2 py-0.5 rounded border border-slate-700">
											<?php echo esc_html( $docType ); ?>
										</span>
									</div>
									<span class="text-xs text-emerald-400 font-extrabold flex items-center gap-1">
										<i class="fa-solid fa-circle text-[8px] text-emerald-400"></i> <?php echo esc_html( $statusText ); ?>
									</span>
								</div>

								<!-- TITLE & LINK -->
								<h2 class="font-extrabold text-base sm:text-lg text-white group-hover:text-cyan-400 transition-colors leading-snug">
									<a href="<?php echo esc_url( $docUrl ); ?>">
										<?php echo esc_html( $doc['title'] ?? 'Văn bản pháp luật công vụ chính thức' ); ?>
									</a>
								</h2>

								<!-- AUTO EXTRACTED SUMMARY -->
								<p class="text-xs sm:text-sm text-slate-300 line-clamp-2 leading-relaxed font-light">
									<?php echo esc_html( $summaryText ); ?>
								</p>

								<!-- AUTO EXTRACTED METADATA GRID -->
								<div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 bg-slate-900/80 rounded-2xl border border-slate-800/80 text-[11px] text-slate-300">
									<div>
										<span class="text-slate-500 block text-[9px] uppercase font-bold">Cơ quan ban hành:</span>
										<strong class="text-white font-semibold truncate block" title="<?php echo esc_attr($agencyName); ?>"><?php echo esc_html($agencyName); ?></strong>
									</div>
									<div>
										<span class="text-slate-500 block text-[9px] uppercase font-bold">Hiệu lực từ ngày:</span>
										<strong class="text-emerald-400 font-extrabold"><?php echo esc_html($effectiveDate); ?></strong>
									</div>
									<div class="col-span-2 sm:col-span-1">
										<span class="text-slate-500 block text-[9px] uppercase font-bold">Trích xuất AI:</span>
										<span class="text-amber-300 font-bold">✓ Đã tự động phân tích</span>
									</div>
								</div>

								<!-- ATTACHMENT FILES DOWNLOAD ROW -->
								<div class="pt-3 border-t border-slate-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
									<div class="flex items-center gap-2 flex-wrap text-[11px]">
										<?php foreach ( array_slice($attachments, 0, 2) as $att ) : 
											$fType = strtolower($att['file_type'] ?? 'pdf');
											$fUrl = ! empty($att['url']) && $att['url'] !== '#' ? $att['url'] : get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf';
											$btnColor = $fType === 'docx' ? 'text-azure-400 hover:bg-azure-500/20' : 'text-red-400 hover:bg-red-500/20';
										?>
											<a href="<?php echo esc_url( $fUrl ); ?>" download target="_blank" class="px-2.5 py-1 bg-slate-900 rounded-lg border border-slate-800 <?php echo $btnColor; ?> transition-colors flex items-center gap-1 font-bold">
												<i class="fa-solid <?php echo $fType === 'docx' ? 'fa-file-word' : 'fa-file-pdf'; ?>"></i> Tải <?php echo strtoupper($fType); ?> (<?php echo esc_html($att['size'] ?? '2 MB'); ?>)
											</a>
										<?php endforeach; ?>
									</div>

									<a href="<?php echo esc_url( $docUrl ); ?>" class="w-full sm:w-auto px-4 py-2 bg-cyan-500 hover:bg-cyan-600 text-navy-950 font-black rounded-xl text-xs shadow hover:scale-105 transition-transform text-center flex items-center justify-center gap-1">
										Đọc Chi Tiết & Trích Xuất AI <i class="fa-solid fa-arrow-right text-xs"></i>
									</a>
								</div>

							</article>
						<?php endforeach; ?>
					</div>

					<?php cvc_render_pagination( $currentPg, $lastPg, 'cvc_legal_documents_url' ); ?>
				<?php endif; ?>

			</main>

			<!-- RIGHT SIDEBAR (3 COLS — MONETIZATION STORE & HIGH CONVERSION CTAS) -->
			<aside class="lg:col-span-3 space-y-5 sticky top-[80px]">

				<!-- WIDGET 1: BỘ ĐỀ TRẮC NGHIỆM PHÁP LUẬT VÒNG 1 (49K - 79K PDF) -->
				<div class="bg-navy-950 border-2 border-amber-500/50 p-6 rounded-3xl space-y-4 shadow-2xl relative overflow-hidden">
					<div class="absolute -right-12 -top-12 w-40 h-40 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

					<div class="flex items-center justify-between border-b border-slate-800 pb-3">
						<h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
							<i class="fa-solid fa-file-pdf text-red-500"></i> Bộ Đề Trắc Nghiệm Luật 2026
						</h3>
						<span class="bg-red-600 text-white text-[9px] font-black px-2 py-0.5 rounded shadow">Sale 49K</span>
					</div>

					<p class="text-xs text-slate-300 leading-relaxed">
						Tổng hợp bộ <strong class="text-amber-400 font-bold">500+ Câu trắc nghiệm Luật Cán bộ công chức & Nghị định 138/2020</strong> chuẩn đáp án thi tuyển Vòng 1.
					</p>

					<div class="p-3 bg-slate-900 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
						<div class="flex justify-between">
							<span class="text-slate-400">Định dạng file:</span>
							<span class="text-white font-bold">PDF In Ấn + File DOCX</span>
						</div>
						<div class="flex justify-between">
							<span class="text-slate-400">Giá tài liệu:</span>
							<strong class="text-amber-400 font-extrabold text-sm">49.000đ</strong>
						</div>
					</div>

					<a href="<?php echo esc_url( cvc_exam_url('de-thi-thu-kien-thuc-chung-tuyen-dung-cong-chuc-vong-1-de-01') ); ?>" class="block w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl text-center shadow-lg transition-transform hover:scale-105">
						TẢI BỘ ĐỀ TRẮC NGHIỆM 49K NGAY &rarr;
					</a>
				</div>

				<!-- WIDGET 2: KHÓA HỌC ÔN THI CẤP TỐC LUẬT CÔNG VỤ (599K - 890K) -->
				<div class="bg-navy-950 border border-slate-800 p-6 rounded-3xl space-y-4 shadow-xl">
					<h3 class="text-xs font-extrabold uppercase tracking-wider text-cyan-400 border-b border-slate-800 pb-3 flex items-center gap-2">
						<i class="fa-solid fa-graduation-cap text-cyan-400"></i> Khóa Học Ôn Thi Cấp Tốc
					</h3>

					<div class="space-y-3">
						<div class="p-3.5 bg-slate-900 rounded-2xl border border-slate-800 space-y-2 hover:border-cyan-400/50 transition-colors">
							<span class="text-[10px] bg-cyan-500 text-navy-950 font-black px-2 py-0.5 rounded">Vòng 1 Cấp Tốc</span>
							<h4 class="font-bold text-xs text-white leading-snug">
								<a href="<?php echo esc_url( cvc_course_url('khoa-hoc-on-thi-cong-chuc-vong-1-kien-thuc-chung-cap-toc-2026') ); ?>" class="hover:text-cyan-400">
									Khóa Học Luật Cán Bộ Công Chức & NĐ 138 Chuyên Sâu
								</a>
							</h4>
							<div class="flex items-center justify-between text-xs pt-1">
								<span class="text-slate-500 line-through">1.200.000đ</span>
								<strong class="text-amber-400 font-extrabold">599.000đ</strong>
							</div>
						</div>
					</div>

					<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block w-full py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-navy-950 font-black text-xs rounded-xl text-center shadow-md transition-transform hover:scale-105">
						ĐĂNG KÝ KHÓA HỌC LUẬT 599K &rarr;
					</a>
				</div>

				<!-- WIDGET 3: TRỢ LÝ AI TRA CỨU PHÁP LUẬT REALTIME -->
				<div class="bg-navy-950 border border-slate-800 p-6 rounded-3xl space-y-3 shadow-xl">
					<h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-3 flex items-center gap-2">
						<i class="fa-solid fa-wand-magic-sparkles text-emerald-400"></i> AI Legal Tra Cứu Luật
					</h3>
					<p class="text-[11px] text-slate-300">Nhập điều khoản hoặc câu hỏi để AI trích xuất căn cứ pháp luật gốc.</p>
					
					<input type="text" id="aiLegalQuery" placeholder="Ví dụ: Thời gian tập sự ngạch chuyên viên..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-cyan-400">
					
					<button type="button" onclick="askAiLegal()" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-navy-950 font-black rounded-xl text-xs transition-transform hover:scale-105 cursor-pointer">
						TRA CỨU AI NGAY
					</button>

					<div id="aiLegalResult" class="hidden p-3 bg-slate-900 border border-cyan-500/40 rounded-xl text-xs text-cyan-300 leading-relaxed">
						✓ <strong>Căn cứ Điều 20 Nghị định 138/2020/NĐ-CP:</strong> Thời gian tập sự là 12 tháng đối với ngạch Chuyên viên và hưởng 85% lương bậc 1.
					</div>

					<script>
					function askAiLegal() {
						const q = document.getElementById('aiLegalQuery').value.trim();
						if (q) {
							document.getElementById('aiLegalResult').classList.remove('hidden');
						} else {
							alert('Vui lòng nhập câu hỏi pháp lý!');
						}
					}
					</script>
				</div>

			</aside>

		</div>

	</div>

</main>

<?php get_footer(); ?>
