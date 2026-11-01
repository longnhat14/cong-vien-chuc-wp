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
// 01/11/2026: tài liệu đang đọc dở (cả tài liệu miễn phí) - "Đọc tiếp".
$reads = ( new CVC_Document_Service() )->reading( $token );
$reads = $reads['ok'] ? (array) ( $reads['data']['data'] ?? array() ) : array();
?>

<div class="space-y-6">

	<div class="flex items-center justify-between border-b border-slate-300 pb-4">
		<div>
			<h2 class="text-xl font-extrabold text-ink">📑 Tài Liệu Đã Mua</h2>
			<p class="text-xs text-ink-muted mt-1">Kho tài liệu PDF, bộ đề sát hạch và sơ đồ tư duy luật bạn đã sở hữu bản quyền.</p>
		</div>
		<a href="<?php echo esc_url( cvc_documents_url() ); ?>" class="px-4 py-2 text-white font-bold text-xs rounded-xl bg-acc hover:bg-acc-deep">
			+ Mua Thêm Tài Liệu
		</a>
	</div>

	<?php if ( $reads ) : ?>
		<section aria-labelledby="cvc-reading-h">
			<h3 id="cvc-reading-h" class="text-sm font-extrabold text-ink mb-2">Đang đọc</h3>
			<?php cvc_doc_reading_list( $reads, 6 ); ?>
		</section>
	<?php endif; ?>

	<?php if ( empty( $documents ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa mua tài liệu nào. Ghé qua Kho Tài Liệu để chọn bộ đề hoặc sổ tay phù hợp.' ); ?>
	<?php else : ?>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<?php foreach ( $documents as $doc ) : ?>
				<div class="dz-card space-y-3 transition-shadow">
					<div class="flex items-center gap-3">
						<div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 font-bold flex items-center justify-center text-lg shrink-0">
							<i class="fa-solid fa-file-pdf"></i>
						</div>
						<div>
							<span class="text-[10px] font-bold text-emerald-600 uppercase block">ĐÃ SỞ HỮU</span>
							<h3 class="font-extrabold text-xs text-ink leading-snug"><?php echo esc_html( $doc['title'] ?? '' ); ?></h3>
						</div>
					</div>
					<p class="text-[11px] text-ink-muted line-clamp-2"><?php echo esc_html( $doc['description'] ?? '' ); ?></p>
					<div class="pt-2 border-t border-slate-300 flex items-center justify-between text-xs">
						<?php if ( ! empty( $doc['reading_page'] ) ) : ?>
							<a href="<?php echo esc_url( cvc_document_url( (string) $doc['slug'] ) . '#doc-read' ); ?>" class="text-[11px] font-bold text-acc hover:underline">Đọc tiếp trang <?php echo (int) $doc['reading_page']; ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( cvc_document_url( (string) $doc['slug'] ) . '#doc-read' ); ?>" class="text-[11px] font-bold text-acc hover:underline">Đọc trực tuyến</a>
						<?php endif; ?>
						<a href="<?php echo esc_url( cvc_document_download_url( $doc['slug'] ) ); ?>" class="px-3 py-1 bg-acc hover:bg-acc-deep text-white font-black rounded-lg text-xs flex items-center gap-1">
							<i class="fa-solid fa-download text-[10px]"></i> Tải Về
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>
