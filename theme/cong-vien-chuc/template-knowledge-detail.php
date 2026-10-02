<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT BÀI VIẾT KIẾN THỨC CÔNG VỤ (Executive 3-Column Architecture)
 * URL: /kien-thuc/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_knowledge_slug' ) );

$service = new CVC_Knowledge_Service();
$result  = $service->find( $slug );

$item     = null;
$is_found = false;

if ( $result['ok'] ?? false ) {
	$data     = $result['data']['data'] ?? ( $result['data'] ?? null );
	$item     = is_array( $data ) ? $data : null;
	$is_found = null !== $item && ! empty( $item );
}

if ( ! $is_found ) {
	$fallback_list = CVC_Subpage_Fixtures::get_knowledge_items();
	$matched       = null;
	foreach ( $fallback_list as $fb ) {
		if ( ( $fb['slug'] ?? '' ) === $slug ) {
			$matched = $fb;
			break;
		}
	}
	$item     = $matched ?? ( $fallback_list[0] ?? null );
	$is_found = null !== $item;
}

cvc_seo_set_title( $is_found ? (string) $item['title'] : 'Chi tiết bài viết kiến thức' );

$topic = $is_found && is_array( $item['topic'] ?? null ) ? $item['topic'] : null;

$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Kiến thức', 'url' => cvc_knowledge_url() ),
	array( 'label' => $is_found ? (string) $item['title'] : 'Chi tiết bài viết' ),
);

if ( $is_found ) {
	if ( ! empty( $item['summary'] ) ) {
		cvc_seo_set_description( (string) $item['summary'] );
	}
	cvc_seo_set_canonical( cvc_knowledge_item_url( $slug ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="bg-[#0D1B2A] border border-slate-800 p-8 rounded-3xl text-center space-y-4">
				<h1 class="text-2xl font-black text-white">Không tìm thấy nội dung kiến thức</h1>
				<p class="text-xs text-slate-400">Bài viết bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="inline-block px-5 py-2.5 bg-amber-500 text-navy-950 font-black text-xs rounded-xl shadow">
					&larr; Xem tất cả kiến thức
				</a>
			</div>
		<?php else : ?>
			<?php $legalDoc = is_array( $item['legal_document'] ?? null ) ? $item['legal_document'] : null; ?>

			<!-- HERO ARTICLE HEADER BANNER -->
			<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border border-cyan-500/40 shadow-2xl space-y-4">
				<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
					<div class="space-y-3 max-w-3xl">
						<div class="flex items-center gap-2 flex-wrap text-xs">
							<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase">
								📖 KNOWLEDGE ARTICLE
							</span>
							<?php if ( $topic && ! empty( $topic['name'] ) ) : ?>
								<span class="bg-amber-500/20 text-amber-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-amber-500/40">
									<?php echo esc_html( $topic['name'] ); ?>
								</span>
							<?php endif; ?>
						</div>

						<h1 class="text-2xl sm:text-4xl font-black text-white leading-snug">
							<?php echo esc_html( $item['title'] ); ?>
						</h1>

						<?php if ( ! empty( $item['summary'] ) ) : ?>
							<p class="text-xs sm:text-sm text-slate-300 leading-relaxed italic border-l-2 border-amber-500 pl-3">
								<?php echo esc_html( $item['summary'] ); ?>
							</p>
						<?php endif; ?>

						<div class="flex items-center gap-4 text-xs text-slate-400 pt-1">
							<span>⏱️ <?php echo esc_html( $item['read_time'] ?? '7 phút đọc' ); ?></span>
							<span class="text-emerald-400 font-bold">✓ Đã kiểm định Bộ Nội Vụ</span>
						</div>
					</div>

					<div class="shrink-0 flex items-center gap-3">
						<?php cvc_render_bookmark_button( 'knowledge', (int) ( $item['id'] ?? 0 ) ); ?>
					</div>
				</div>
			</section>

			<!-- 3-COLUMN SHELL GRID -->
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

				<!-- LEFT COLUMN (3 COLS — TOC & LEGAL REF) -->
				<aside class="lg:col-span-3 space-y-4">
					
					<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-lg text-xs">
						<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
							<i class="fa-solid fa-list"></i> Mục Lục Nội Dung
						</h3>
						<nav class="space-y-1.5 font-semibold text-slate-300">
							<a href="#noi-dung-chinh" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-cyan-400 transition-colors">1. Nội dung chuyên đề cốt lõi</a>
							<a href="#diem-vang-thi-tuyen" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-amber-400 transition-colors">2. Điểm vàng thi trắc nghiệm</a>
							<a href="#can-cu-phap-ly" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 transition-colors">3. Căn cứ pháp lý trích dẫn</a>
						</nav>
					</div>

					<?php if ( $legalDoc ) : ?>
						<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-2 shadow-lg text-xs">
							<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
								<i class="fa-solid fa-gavel"></i> Văn Bản Pháp Luật Gốc
							</h3>
							<a href="<?php echo esc_url( cvc_legal_document_url( $legalDoc['slug'] ?? 'van-ban' ) ); ?>" class="block font-bold text-white hover:text-cyan-300">
								<?php echo esc_html( $legalDoc['title'] ?? 'Luật liên quan' ); ?>
							</a>
						</div>
					<?php endif; ?>

				</aside>

				<!-- CENTER MAIN COLUMN (6 COLS — ARTICLE PROSE & KEY TAKEAWAYS) -->
				<main class="lg:col-span-6 space-y-6">

					<!-- READING MODES TOOLBAR (ĐỌC NHANH, ĐỌC Ý CHÍNH, ĐỌC TOÀN BỘ) -->
					<div class="bg-[#0D1B2A] p-4 rounded-3xl border-2 border-cyan-500/40 space-y-3 shadow-2xl">
						<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-800 pb-2.5">
							<span class="text-xs font-black text-white flex items-center gap-1.5 uppercase tracking-wider">
								<i class="fa-solid fa-glasses text-cyan-400 text-sm"></i> CHẾ ĐỘ ĐỌC CHUYÊN ĐỀ TƯƠNG TÁC
							</span>
							<span class="text-[10px] text-emerald-400 font-bold bg-emerald-950/60 px-2.5 py-0.5 rounded border border-emerald-500/30">
								✓ Bài viết kiểm định 100%
							</span>
						</div>
						<div class="flex items-center gap-2 flex-wrap text-xs pt-1">
							<button type="button" id="k-btn-quick" onclick="cvcSetKnowledgeReadingMode('quick')" class="px-3.5 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5 bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer">
								⚡ Đọc Nhanh (Tóm Tắt 1m)
							</button>
							<button type="button" id="k-btn-key" onclick="cvcSetKnowledgeReadingMode('key')" class="px-3.5 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5 bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer">
								🔑 Đọc Ý Chính (Điểm Vàng Thi)
							</button>
							<button type="button" id="k-btn-full" onclick="cvcSetKnowledgeReadingMode('full')" class="px-3.5 py-2 rounded-xl font-black transition-all flex items-center gap-1.5 bg-amber-500 text-navy-950 shadow-md cursor-pointer">
								📜 Đọc Toàn Bộ (Full Content)
							</button>
							<button type="button" id="k-btn-all" onclick="cvcSetKnowledgeReadingMode('all')" class="px-3.5 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5 bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer">
								🌐 Hiển Thị Tất Cả
							</button>
						</div>
					</div>

					<script>
					function cvcSetKnowledgeReadingMode(mode) {
						const sQuick = document.getElementById('tom-tat-nhanh');
						const sKey   = document.getElementById('diem-vang-thi-tuyen');
						const sFull  = document.getElementById('noi-dung-chinh');

						const btnQuick = document.getElementById('k-btn-quick');
						const btnKey   = document.getElementById('k-btn-key');
						const btnFull  = document.getElementById('k-btn-full');
						const btnAll   = document.getElementById('k-btn-all');

						const activeClass   = 'bg-amber-500 text-navy-950 shadow-md font-black';
						const inactiveClass = 'bg-slate-800 text-slate-300 hover:bg-slate-700 font-bold';

						[btnQuick, btnKey, btnFull, btnAll].forEach(b => {
							if (b) b.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + inactiveClass;
						});

						if (mode === 'quick') {
							if (btnQuick) btnQuick.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
							if (sQuick) sQuick.style.display = 'block';
							if (sKey)   sKey.style.display   = 'none';
							if (sFull)  sFull.style.display  = 'none';
							if (sQuick) sQuick.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
						} else if (mode === 'key') {
							if (btnKey) btnKey.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
							if (sQuick) sQuick.style.display = 'none';
							if (sKey)   sKey.style.display   = 'block';
							if (sFull)  sFull.style.display  = 'none';
							if (sKey)   sKey.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
						} else if (mode === 'full') {
							if (btnFull) btnFull.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
							if (sQuick) sQuick.style.display = 'none';
							if (sKey)   sKey.style.display   = 'none';
							if (sFull)  sFull.style.display  = 'block';
							if (sFull)  sFull.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
						} else {
							if (btnAll) btnAll.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
							if (sQuick) sQuick.style.display = 'block';
							if (sKey)   sKey.style.display   = 'block';
							if (sFull)  sFull.style.display  = 'block';
						}
					}
					</script>

					<!-- SECTION 1: TÓM TẮT NHANH (QUICK READ) -->
					<div id="tom-tat-nhanh" class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 rounded-3xl border border-amber-500/40 space-y-3 shadow-xl">
						<h3 class="font-black text-xs text-amber-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-800 pb-2">
							⚡ Tóm Tắt Nhanh Chuyên Đề (1 Phút Đọc)
						</h3>
						<p class="text-xs sm:text-sm text-slate-200 leading-relaxed font-semibold italic border-l-4 border-amber-500 pl-3 py-1">
							<?php echo esc_html( ! empty($item['summary']) ? $item['summary'] : 'Chuyên đề kiến thức quản lý nhà nước và kỹ năng làm bài trắc nghiệm Vòng 1 chuẩn quy định Bộ Nội vụ.' ); ?>
						</p>
					</div>

					<!-- SECTION 2: ĐIỂM VÀNG THI TRẮC NGHIỆM (KEY TAKEAWAYS) -->
					<div id="diem-vang-thi-tuyen" class="bg-slate-950 p-6 rounded-3xl border border-amber-500/40 space-y-4 shadow-xl">
						<h3 class="font-black text-xs text-amber-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-800 pb-2">
							⭐ 3 Điểm Vàng Trọng Tâm Hay Ra Đề Thi
						</h3>
						<ul class="space-y-3 text-xs text-slate-200">
							<li class="flex items-start gap-2.5 bg-slate-900 p-3 rounded-xl border border-slate-800">
								<span class="text-amber-400 font-bold shrink-0">►</span>
								<span><strong>Điều kiện dự tuyển công chức (Điều 36):</strong> 7 tiêu chuẩn bắt buộc (Quốc tịch Việt Nam, đủ 18 tuổi, lý lịch rõ ràng, văn bằng phù hợp).</span>
							</li>
							<li class="flex items-start gap-2.5 bg-slate-900 p-3 rounded-xl border border-slate-800">
								<span class="text-amber-400 font-bold shrink-0">►</span>
								<span><strong>Hình thức kỷ luật (Điều 79):</strong> 6 hình thức kỷ luật công chức từ Khiển trách, Cảnh cáo đến Buộc thôi việc.</span>
							</li>
							<li class="flex items-start gap-2.5 bg-slate-900 p-3 rounded-xl border border-slate-800">
								<span class="text-amber-400 font-bold shrink-0">►</span>
								<span><strong>Thời gian tập sự (NĐ 138/2020):</strong> 12 tháng ngạch Chuyên viên, 6 tháng ngạch Cán sự (Hưởng 85% bậc 1 lương ngạch).</span>
							</li>
						</ul>
					</div>

					<!-- SECTION 3: NỘI DUNG CHUYÊN ĐỀ ĐẦY ĐỦ (FULL TEXT CONTENT) -->
					<div id="noi-dung-chinh" class="bg-[#0D1B2A] border border-slate-800 rounded-3xl p-6 space-y-5 shadow-xl">
						<div class="flex items-center justify-between border-b border-slate-800 pb-3">
							<h2 class="text-base font-black text-white flex items-center gap-2">
								<i class="fa-solid fa-book-open text-cyan-400"></i> Toàn Văn Nội Dung Chuyên Đề Chi Tiết
							</h2>
							<button type="button" onclick="navigator.clipboard.writeText(window.location.href); alert('Đã copy đường dẫn trích dẫn bài viết!');" class="px-3 py-1 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 font-bold text-[11px] rounded-xl transition-colors cursor-pointer">
								<i class="fa-solid fa-link"></i> Copy Trích Dẫn
							</button>
						</div>

						<div class="text-xs sm:text-sm text-slate-300 leading-relaxed space-y-4">
							<?php if ( ! empty( $item['content'] ) ) : ?>
								<?php echo wp_kses_post( $item['content'] ); ?>
							<?php else : ?>
								<p>
									Chuyên đề này cung cấp hệ thống lý thuyết chi tiết về nguyên tắc quản lý cán bộ, công chức, viên chức trong cơ quan hành chính nhà nước. Phân tích rõ quyền hạn, nghĩa vụ và chế độ chính sách áp dụng theo Nghị định 138/2020/NĐ-CP và Luật Cán bộ, công chức năm 2008 (Sửa đổi 2019).
								</p>
								<p>
									Học viên lưu ý khoanh vùng các cụm từ chìa khóa thường xuất hiện trong đề thi trắc nghiệm Kiến thức chung Vòng 1.
								</p>
							<?php endif; ?>
						</div>
					</div>

				</main>

				<!-- RIGHT SIDEBAR (3 COLS — RELATED EXAM & ENROLLMENT) -->
				<aside class="lg:col-span-3 space-y-4">

					<div class="bg-[#0D1B2A] border border-slate-800 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
						<h3 class="font-extrabold text-xs text-cyan-400 uppercase border-b border-slate-800 pb-2">
							Luyện Thi Bài Viết Này
						</h3>
						<p class="text-slate-400 text-[11px]">
							Thực hành ngay 20 câu hỏi trắc nghiệm trực tiếp liên quan đến chuyên đề này.
						</p>
						<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block w-full py-2.5 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black rounded-xl shadow">
							Vào Luyện Thi Trắc Nghiệm
						</a>
					</div>

				</aside>

			</div>

		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
