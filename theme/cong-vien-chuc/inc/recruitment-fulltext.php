<?php
/**
 * Trang tin tuyển dụng (16/10/2026): các mục chi tiết trích từ thông báo + toàn văn thông báo.
 * Dữ liệu từ API: recruitment.details (AI chép lại theo mục, sát văn bản) và recruitment.full_text
 * (khối nội dung trang gốc đã lọc ở backend + chữ công văn đính kèm).
 *
 * @package CongVienChuc
 */

defined( 'ABSPATH' ) || exit;

/** Chữ thường -> HTML an toàn, link http(s) bấm được (mở tab mới, nofollow). */
function cvc_notice_text( string $text ): string {
	$html = esc_html( $text );
	$html = preg_replace_callback(
		'#\b(https?://[^\s<>"\')\]]+)#iu',
		static function ( $m ) {
			$url  = rtrim( $m[1], '.,;:' );
			$tail = substr( $m[1], strlen( $url ) );
			return '<a class="text-acc underline break-all" href="' . esc_url( html_entity_decode( $url ) ) . '" target="_blank" rel="noopener nofollow">' . $url . '</a>' . $tail;
		},
		$html
	);

	return nl2br( (string) $html );
}

/** Lọc lần 2 phía WordPress cho toàn văn (backend đã lọc theo danh sách trắng). */
function cvc_notice_kses( string $html ): string {
	$cell = array( 'colspan' => true, 'rowspan' => true );
	return wp_kses(
		$html,
		array(
			'div' => array(), 'p' => array(), 'br' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(),
			'ul' => array(), 'ol' => array(), 'li' => array(), 'table' => array(), 'thead' => array(), 'tbody' => array(),
			'tfoot' => array(), 'tr' => array(), 'td' => $cell, 'th' => $cell, 'strong' => array(), 'b' => array(),
			'em' => array(), 'i' => array(), 'u' => array(), 'blockquote' => array(), 'sup' => array(), 'sub' => array(), 'hr' => array(),
			'a' => array( 'href' => true, 'target' => true, 'rel' => true ),
		),
		array( 'http', 'https' )
	);
}

function cvc_render_notice_details( array $details ): void {
	$lists = array(
		'general_conditions'    => 'Điều kiện chung',
		'specific_requirements' => 'Tiêu chuẩn, điều kiện riêng',
		'disqualified'          => 'Không được đăng ký dự tuyển',
		'documents_required'    => 'Hồ sơ dự tuyển',
		'notes'                 => 'Lưu ý',
	);
	$has = false;
	foreach ( array_keys( $lists ) as $k ) {
		$has = $has || ! empty( $details[ $k ] );
	}
	$has = $has || ! empty( $details['selection_rounds'] ) || ! empty( $details['schedule'] ) || ! empty( $details['application_place'] ) || ! empty( $details['application_fee'] );
	if ( ! $has ) {
		return;
	}
	$h3 = 'text-sm font-extrabold text-ink';
	?>
	<section id="chi-tiet" class="dz-panel space-y-6">
		<div class="border-b border-slate-300 pb-3">
			<h2 class="text-lg font-extrabold text-acc">Chi tiết thông báo</h2>
			<p class="text-[11px] text-ink-muted mt-1">Chép lại theo từng mục từ thông báo của cơ quan tuyển dụng. Khi có khác biệt, căn cứ theo toàn văn thông báo bên dưới.</p>
		</div>

		<?php foreach ( array( 'general_conditions', 'specific_requirements', 'disqualified' ) as $k ) : ?>
			<?php if ( ! empty( $details[ $k ] ) ) : ?>
				<div class="space-y-2">
					<h3 class="<?php echo esc_attr( $h3 ); ?>"><?php echo esc_html( $lists[ $k ] ); ?></h3>
					<ul class="list-disc pl-5 space-y-1.5 text-sm text-ink-2 leading-relaxed">
						<?php foreach ( (array) $details[ $k ] as $line ) : ?>
							<li><?php echo cvc_notice_text( (string) $line ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php if ( ! empty( $details['selection_rounds'] ) ) : ?>
			<div class="space-y-2">
				<h3 class="<?php echo esc_attr( $h3 ); ?>">Hình thức, nội dung tuyển dụng</h3>
				<ol class="space-y-3">
					<?php foreach ( (array) $details['selection_rounds'] as $r ) : ?>
						<li class="dz-card space-y-1.5 text-sm text-ink-2">
							<p class="font-bold text-ink"><?php echo esc_html( (string) ( $r['name'] ?? '' ) ); ?></p>
							<?php
							foreach ( array( 'format' => 'Hình thức', 'content' => 'Nội dung', 'duration' => 'Thời gian', 'scoring' => 'Thang điểm / điều kiện đạt' ) as $f => $label ) :
								if ( empty( $r[ $f ] ) ) {
									continue;
								}
								?>
								<p><span class="text-ink-muted"><?php echo esc_html( $label ); ?>:</span> <?php echo cvc_notice_text( (string) $r[ $f ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
							<?php endforeach; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $details['documents_required'] ) ) : ?>
			<div class="space-y-2">
				<h3 class="<?php echo esc_attr( $h3 ); ?>">Hồ sơ dự tuyển</h3>
				<ol class="list-decimal pl-5 space-y-1.5 text-sm text-ink-2 leading-relaxed">
					<?php foreach ( (array) $details['documents_required'] as $line ) : ?>
						<li><?php echo cvc_notice_text( (string) $line ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $details['application_place'] ) || ! empty( $details['application_fee'] ) ) : ?>
			<dl class="grid gap-3 text-sm">
				<?php if ( ! empty( $details['application_place'] ) ) : ?>
					<div><dt class="<?php echo esc_attr( $h3 ); ?>">Nơi, cách nộp hồ sơ</dt><dd class="text-ink-2 leading-relaxed mt-1"><?php echo cvc_notice_text( (string) $details['application_place'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
				<?php endif; ?>
				<?php if ( ! empty( $details['application_fee'] ) ) : ?>
					<div><dt class="<?php echo esc_attr( $h3 ); ?>">Lệ phí</dt><dd class="text-ink-2 leading-relaxed mt-1"><?php echo cvc_notice_text( (string) $details['application_fee'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
				<?php endif; ?>
			</dl>
		<?php endif; ?>

		<?php if ( ! empty( $details['schedule'] ) ) : ?>
			<div class="space-y-2">
				<h3 class="<?php echo esc_attr( $h3 ); ?>">Lịch trình</h3>
				<ol class="border-l border-slate-300 ml-1.5 space-y-3">
					<?php foreach ( (array) $details['schedule'] as $x ) : ?>
						<li class="pl-4 relative text-sm">
							<span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full bg-acc text-white" aria-hidden="true"></span>
							<?php if ( ! empty( $x['time'] ) ) : ?>
								<p class="text-xs font-bold text-acc"><?php echo esc_html( (string) $x['time'] ); ?></p>
							<?php endif; ?>
							<p class="text-ink-2"><?php echo cvc_notice_text( (string) ( $x['event'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $details['priority'] ) ) : ?>
			<div class="space-y-1"><h3 class="<?php echo esc_attr( $h3 ); ?>">Đối tượng, điểm ưu tiên</h3><p class="text-sm text-ink-2"><?php echo cvc_notice_text( (string) $details['priority'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p></div>
		<?php endif; ?>

		<?php if ( ! empty( $details['notes'] ) ) : ?>
			<div class="space-y-2">
				<h3 class="<?php echo esc_attr( $h3 ); ?>">Lưu ý</h3>
				<ul class="list-disc pl-5 space-y-1.5 text-sm text-ink-2"><?php foreach ( (array) $details['notes'] as $line ) : ?><li><?php echo cvc_notice_text( (string) $line ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li><?php endforeach; ?></ul>
			</div>
		<?php endif; ?>
	</section>
	<?php
}

function cvc_render_notice_fulltext( array $full, string $source_url, string $source_name ): void {
	$html = (string) ( $full['html'] ?? '' );
	$docs = (array) ( $full['documents'] ?? array() );
	if ( '' === $html && empty( $docs ) ) {
		return;
	}
	?>
	<section id="toan-van" class="dz-panel space-y-4">
		<div class="border-b border-slate-300 pb-3">
			<h2 class="text-lg font-extrabold text-acc">Toàn văn thông báo</h2>
			<p class="text-[11px] text-ink-muted mt-1">
				Đăng lại nguyên văn từ <?php echo '' !== $source_url ? '<a class="underline" href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $source_name ?: ( wp_parse_url( $source_url, PHP_URL_HOST ) ?: 'trang gốc' ) ) . '</a>' : 'trang của cơ quan'; ?>. Văn bản có giá trị pháp lý là bản do cơ quan tuyển dụng ban hành.
			</p>
		</div>
		<?php if ( '' !== $html ) : ?>
			<details class="cvc-fulltext group" open>
				<summary class="cursor-pointer text-xs font-bold text-acc select-none">Thu gọn / mở rộng</summary>
				<div class="cvc-notice-prose mt-3 text-sm text-ink-2 leading-relaxed overflow-x-auto">
					<?php echo cvc_notice_kses( $html ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</details>
		<?php endif; ?>
		<?php foreach ( $docs as $i => $d ) : ?>
			<details class="dz-card" <?php echo '' === $html && 0 === $i ? 'open' : ''; ?>>
				<summary class="cursor-pointer text-sm font-bold text-ink"><i class="fa-solid fa-file-lines mr-1.5 text-red-700" aria-hidden="true"></i><?php echo esc_html( (string) ( $d['name'] ?? 'Công văn' ) ); ?> <span class="text-xs text-ink-muted font-normal">(chữ trích từ công văn)</span></summary>
				<div class="mt-3 text-sm text-ink-2 leading-relaxed whitespace-pre-line break-words max-h-[70vh] overflow-y-auto"><?php echo cvc_notice_text( (string) ( $d['text'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</details>
		<?php endforeach; ?>
	</section>
	<style>
		.cvc-notice-prose p,.cvc-notice-prose div{margin:.45rem 0}
		.cvc-notice-prose h3,.cvc-notice-prose h4,.cvc-notice-prose h5{color:#f1f5f9;font-weight:800;margin:1rem 0 .4rem}
		.cvc-notice-prose ul{list-style:disc;padding-left:1.25rem}.cvc-notice-prose ol{list-style:decimal;padding-left:1.25rem}
		.cvc-notice-prose table{border-collapse:collapse;width:100%;margin:.75rem 0;font-size:.8125rem}
		.cvc-notice-prose td,.cvc-notice-prose th{border:1px solid #334155;padding:.4rem .55rem;vertical-align:top}
		.cvc-notice-prose td p,.cvc-notice-prose th p{margin:.15rem 0}
		.cvc-notice-prose a{color:#7dd3fc;text-decoration:underline;word-break:break-all}
		.cvc-notice-prose b,.cvc-notice-prose strong{color:#e2e8f0}
	</style>
	<?php
}
