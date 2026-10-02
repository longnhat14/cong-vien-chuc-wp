<?php
/**
 * Tài liệu đã mua (Phase 11) - GET /api/my-documents thật (Entitlement),
 * thay cho 3 thẻ tài liệu hard-code trỏ thẳng assets/downloads/ trước đây
 * (ai cũng tải được link tĩnh đó dù chưa mua - đúng lỗ hổng bảo mật/doanh
 * thu đã phát hiện ở audit kiến trúc 2026). Link tải giờ đi qua
 * cvc_document_download_url() (proxy có kiểm tra quyền sở hữu thật).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result = ( new CVC_Document_Service() )->mine( $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$documents = $result['data']['data'] ?? array();
?>

<div class="space-y-6">

	<div class="flex items-center justify-between border-b border-slate-200 pb-4">
		<div>
			<h2 class="text-xl font-extrabold text-slate-900">📑 Tài Liệu Đã Mua</h2>
			<p class="text-xs text-slate-500 mt-1">Kho tài liệu PDF, bộ đề sát hạch và sơ đồ tư duy luật bạn đã sở hữu bản quyền.</p>
		</div>
		<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs rounded-xl shadow">
			+ Mua Thêm Tài Liệu
		</a>
	</div>

	<?php if ( empty( $documents ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa mua tài liệu nào. Ghé qua Kho Tài Liệu để chọn bộ đề hoặc sổ tay phù hợp.' ); ?>
	<?php else : ?>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<?php foreach ( $documents as $doc ) : ?>
				<div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-sm hover:shadow-md transition-shadow">
					<div class="flex items-center gap-3">
						<div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 font-bold flex items-center justify-center text-lg shrink-0">
							<i class="fa-solid fa-file-pdf"></i>
						</div>
						<div>
							<span class="text-[10px] font-bold text-emerald-600 uppercase block">ĐÃ SỞ HỮU</span>
							<h3 class="font-extrabold text-xs text-slate-900 leading-snug"><?php echo esc_html( $doc['title'] ?? '' ); ?></h3>
						</div>
					</div>
					<p class="text-[11px] text-slate-500 line-clamp-2"><?php echo esc_html( $doc['description'] ?? '' ); ?></p>
					<div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
						<span class="text-slate-400 font-mono text-[10px]"><?php echo esc_html( size_format( (int) ( $doc['file_size'] ?? 0 ) ) ); ?></span>
						<a href="<?php echo esc_url( cvc_document_download_url( $doc['slug'] ) ); ?>" class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black rounded-lg text-xs shadow flex items-center gap-1">
							<i class="fa-solid fa-download text-[10px]"></i> Tải Về
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>
