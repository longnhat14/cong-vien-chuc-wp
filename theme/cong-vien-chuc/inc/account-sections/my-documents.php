<?php
/**
 * Tài liệu đã mua (Phase 10 & Account Dashboard)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$legal_service = new CVC_Legal_Document_Service();
$doc_result    = $legal_service->list( array( 'per_page' => 10 ) );
$documents     = $doc_result['ok'] ? ( $doc_result['data']['data'] ?? array() ) : array();
?>

<div class="space-y-6">

	<div class="flex items-center justify-between border-b border-slate-200 pb-4">
		<div>
			<h2 class="text-xl font-extrabold text-slate-900">📑 Tài Liệu & Đề Thi Đã Mua</h2>
			<p class="text-xs text-slate-500 mt-1">Kho tài liệu PDF, bộ đề sát hạch và sơ đồ tư duy luật bạn đã sở hữu bản quyền.</p>
		</div>
		<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs rounded-xl shadow">
			+ Mua Thêm Tài Liệu
		</a>
	</div>

	<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
		<!-- Doc Item 1 -->
		<div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-sm hover:shadow-md transition-shadow">
			<div class="flex items-center gap-3">
				<div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 font-bold flex items-center justify-center text-lg shrink-0">
					📄
				</div>
				<div>
					<span class="text-[10px] font-bold text-emerald-600 uppercase block">ĐÃ SỞ HỮU</span>
					<h3 class="font-extrabold text-xs text-slate-900 leading-snug">Bộ 50 Đề Thi Thử KTC Vòng 1 (PDF)</h3>
				</div>
			</div>
			<p class="text-[11px] text-slate-500">Bao gồm 100% đáp án chi tiết & trích dẫn điều khoản luật gốc chuẩn 2026.</p>
			<div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
				<span class="text-slate-400 font-mono text-[10px]">Format: PDF · 4.2 MB</span>
				<a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf' ); ?>" download="Bo-50-De-Thi-Thu-KTC-Vong-1-2026.pdf" class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black rounded-lg text-xs shadow flex items-center gap-1">
					<i class="fa-solid fa-download text-[10px]"></i> Tải Về PDF
				</a>
			</div>
		</div>

		<!-- Doc Item 2 -->
		<div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-sm hover:shadow-md transition-shadow">
			<div class="flex items-center gap-3">
				<div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 font-bold flex items-center justify-center text-lg shrink-0">
					⚖️
				</div>
				<div>
					<span class="text-[10px] font-bold text-emerald-600 uppercase block">ĐÃ SỞ HỮU</span>
					<h3 class="font-extrabold text-xs text-slate-900 leading-snug">Sơ Đồ Tư Duy Luật Viên Chức 2026</h3>
				</div>
			</div>
			<p class="text-[11px] text-slate-500">Tóm tắt hình họa trực quan các mốc thời gian, thẩm quyền & quy trình xử lý kỷ luật.</p>
			<div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
				<span class="text-slate-400 font-mono text-[10px]">Format: PDF · 8.5 MB</span>
				<a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/So-do-tu-duy-He-thong-Chinh-tri-Viet-Nam-2026.pdf' ); ?>" download="So-Do-Tu-Duy-Luat-Vien-Chuc-2026.pdf" class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black rounded-lg text-xs shadow flex items-center gap-1">
					<i class="fa-solid fa-download text-[10px]"></i> Tải Về PDF
				</a>
			</div>
		</div>

		<!-- Doc Item 3 -->
		<div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-sm hover:shadow-md transition-shadow">
			<div class="flex items-center gap-3">
				<div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 font-bold flex items-center justify-center text-lg shrink-0">
					🎯
				</div>
				<div>
					<span class="text-[10px] font-bold text-emerald-600 uppercase block">ĐÃ SỞ HỮU</span>
					<h3 class="font-extrabold text-xs text-slate-900 leading-snug">Sổ Tay Bẫy Trắc Nghiệm Công Vụ</h3>
				</div>
			</div>
			<p class="text-[11px] text-slate-500">Tổng hợp 100 câu bẫy kinh điển dễ mất điểm trong kỳ thi sát hạch Bộ Nội Vụ.</p>
			<div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
				<span class="text-slate-400 font-mono text-[10px]">Format: PDF · 3.1 MB</span>
				<a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/Cam-nang-khoan-vung-trong-tam-KTC-2026-Full.pdf' ); ?>" download="So-Tay-Bay-Trac-Nghiem-Cong-Vu.pdf" class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black rounded-lg text-xs shadow flex items-center gap-1">
					<i class="fa-solid fa-download text-[10px]"></i> Tải Về PDF
				</a>
			</div>
		</div>
	</div>

</div>
