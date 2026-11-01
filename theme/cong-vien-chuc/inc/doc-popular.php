<?php
/**
 * KHỐI "TÀI LIỆU ĐƯỢC ĐỌC NHIỀU" dùng chung (01/11/2026, claude/ke-hoach-tai-lieu-tai-nhieu-khap-noi.md GĐ1):
 * tin tuyển dụng, kết quả thi thử, văn bản pháp luật, chi tiết tài liệu, trang chủ. Dữ liệu GET /api/documents/popular
 * (điểm = 2 × lượt đọc + lượt tải, mọi lúc, khớp ngữ cảnh). Có tài liệu trả phí (giá + Đọc thử). Nhãn "Được đọc
 * nhiều" chỉ khi lượt đọc thật ≥ 10; dưới 5 lượt đọc hiện "Mới".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array{heading:?string, items:array<int, array<string, mixed>>} */
function cvc_doc_popular( string $ctx, array $params = array(), int $limit = 6 ): array {
	$q   = array_filter( $params + array( 'ctx' => $ctx, 'limit' => $limit ), static fn ( $v ) => null !== $v && '' !== $v && array() !== $v );
	$key = 'cvc_popdocs_' . md5( wp_json_encode( $q ) );
	$hit = get_transient( $key );
	if ( is_array( $hit ) ) {
		return $hit;
	}
	$res  = ( new CVC_Api_Client( null, 6 ) )->get( '/api/documents/popular', $q );
	$data = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$out  = array( 'heading' => $data['heading'] ?? null, 'items' => array_values( (array) ( $data['items'] ?? array() ) ) );
	set_transient( $key, $out, $res['ok'] ? 10 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS );
	return $out;
}

/**
 * Hàng thẻ tài liệu (cuộn ngang). $o: place (mã vị trí), head (true = tự in tiêu đề), title, sub, more (url xem tất cả).
 *
 * @param array<int, array<string, mixed>> $items
 */
function cvc_doc_popular_row( array $items, array $o = array() ): void {
	if ( ! $items ) {
		return;
	}
	static $css = false;
	if ( ! $css ) {
		$css = true;
		?>
		<style>
		.dp-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin:0 0 4px}
		.dp-head h2{margin:0;font-size:20px;color:#17334f}
		.dp-sub{margin:0 0 12px;font-size:15px;color:#5b6470}
		.dp-more{font-size:14px;font-weight:700;color:#17334f;white-space:nowrap;text-decoration:none}
		.dp-more:hover{text-decoration:underline}
		.dp-row{display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;padding:2px 2px 8px;scrollbar-width:thin}
		.dp-card{flex:0 0 272px;scroll-snap-align:start;display:flex;gap:12px;padding:12px;border:1px solid #e3e8ee;border-radius:12px;background:#fff;color:#1b2430;text-decoration:none;transition:border-color .15s,box-shadow .15s}
		.dp-card:hover{border-color:#c9a052;box-shadow:0 6px 18px rgba(15,42,74,.08)}
		.dp-card:focus-visible{outline:2px solid #c9a052;outline-offset:2px}
		.dp-cover{flex:none;width:58px;height:82px;border-radius:2px 4px 4px 2px;overflow:hidden;background:#eaf0f6;box-shadow:0 2px 6px rgba(15,42,74,.22);display:flex;align-items:center;justify-content:center;color:#17334f;font-size:22px}
		.dp-cover img{width:100%;height:100%;object-fit:cover;display:block}
		.dp-body{min-width:0;display:flex;flex-direction:column;gap:4px}
		.dp-tags{display:flex;flex-wrap:wrap;gap:4px}
		.dp-tag{font-size:11px;font-weight:700;letter-spacing:.02em;padding:1px 7px;border-radius:999px;background:#eef3f8;color:#2b4a68;line-height:1.6}
		.dp-tag.is-hot{background:#fbf0d9;color:#7a5a17}
		.dp-t{font-size:15px;font-weight:700;line-height:1.35;color:#17334f;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
		.dp-m{font-size:12.5px;color:#5b6470;font-variant-numeric:tabular-nums}
		.dp-new{display:inline-block;padding:0 6px;border:1px solid #e2c27a;border-radius:3px;background:#fbf5e8;color:#7a5a17;font-size:11px;font-weight:700}
		.dp-p{margin-top:auto;font-size:13px;font-weight:700;color:#17334f}
		.dp-p .free{color:#1f7a5c}
		@media (max-width:640px){.dp-card{flex-basis:84%}}
		</style>
		<?php
	}
	$place = sanitize_key( (string) ( $o['place'] ?? 'khac' ) );
	if ( ! empty( $o['head'] ) ) :
		?>
		<div class="dp-head"><h2><?php echo esc_html( (string) ( $o['title'] ?? 'Tài liệu được đọc nhiều' ) ); ?></h2><?php if ( ! empty( $o['more'] ) ) : ?><a class="dp-more" href="<?php echo esc_url( (string) $o['more'] ); ?>">Xem tất cả →</a><?php endif; ?></div>
		<?php if ( ! empty( $o['sub'] ) ) : ?><p class="dp-sub"><?php echo esc_html( (string) $o['sub'] ); ?></p><?php endif; ?>
	<?php endif; ?>
	<div class="dp-row" data-dp-place="<?php echo esc_attr( $place ); ?>">
		<?php foreach ( $items as $it ) : ?>
			<?php
			$reads = (int) ( $it['reads'] ?? 0 );
			$dls   = (int) ( $it['downloads'] ?? 0 );
			$free  = ! empty( $it['is_free'] );
			?>
			<a class="dp-card" href="<?php echo esc_url( cvc_document_url( (string) $it['slug'] ) . ( ! $free && ! empty( $it['preview'] ) ? '#doc-read' : '' ) ); ?>">
				<span class="dp-cover"><?php if ( ! empty( $it['thumb'] ) ) : ?><img src="<?php echo esc_url( (string) $it['thumb'] ); ?>" alt="" width="58" height="82" loading="lazy" decoding="async"><?php else : ?><i class="fa-regular fa-file-lines" aria-hidden="true"></i><?php endif; ?></span>
				<span class="dp-body">
					<span class="dp-tags">
						<?php if ( ! empty( $it['hot'] ) ) : ?><span class="dp-tag is-hot">Được đọc nhiều</span><?php endif; ?>
						<?php if ( ! empty( $it['reason'] ) ) : ?><span class="dp-tag"><?php echo esc_html( (string) $it['reason'] ); ?></span><?php elseif ( empty( $it['hot'] ) ) : ?><span class="dp-tag"><?php echo esc_html( (string) ( $it['category_label'] ?? 'Tài liệu' ) ); ?></span><?php endif; ?>
					</span>
					<span class="dp-t"><?php echo esc_html( (string) $it['title'] ); ?></span>
					<span class="dp-m"><?php echo $reads < 5 ? '<span class="dp-new">Mới</span>' : esc_html( number_format( $reads, 0, ',', '.' ) . ' lượt đọc' ); ?><?php echo $dls > 0 ? esc_html( ' · ' . number_format( $dls, 0, ',', '.' ) . ' lượt tải' ) : ''; ?><?php echo ! empty( $it['pages'] ) ? esc_html( ' · ' . (int) $it['pages'] . ' trang' ) : ''; ?></span>
					<span class="dp-p"><?php echo $free ? '<span class="free">Miễn phí</span> · Đọc ngay →' : esc_html( number_format( (float) ( $it['price'] ?? 0 ), 0, ',', '.' ) . 'đ' . ( ! empty( $it['preview'] ) ? ' · Đọc thử →' : ' · Xem →' ) ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
