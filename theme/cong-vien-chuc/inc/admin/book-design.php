<?php
/**
 * THIẾT KẾ SÁCH (02/11/2026, claude/ke-hoach-tuy-bien-bia-gay-header-footer.md - anh Nhật chốt: bộ mẫu + chỉnh từng
 * phần; họa tiết tự vẽ + ảnh tải lên; chỉ bảng màu mẫu; header/footer không đụng PDF giữ nguyên; kiểu 3D theo sách +
 * ép theo vị trí; có Đứng gáy, Chồng sách).
 *  /quan-tri/thiet-ke-sach/        danh sách sách + kiểu hiển thị theo vị trí + Mẫu của tôi
 *  /quan-tri/thiet-ke-sach/{id}/   chỉnh thiết kế một cuốn (Bìa / Trang trong / Gáy & 3D) + xem trước 4 khung
 * API: /api/admin/book-designs/* (quyền document.view / document.create).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_bd_styles(): void {
	?>
	<style>
	.bd-wrap{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:18px;align-items:start}
	@media (max-width:1180px){.bd-wrap{grid-template-columns:1fr}}
	.bd-tabs{display:flex;gap:6px;flex-wrap:wrap;margin:0 0 12px}
	.bd-tab{padding:8px 14px;border-radius:10px;border:1px solid #cbd5df;background:#fff;font-weight:bold;font-size:14px;cursor:pointer;color:#17334f}
	.bd-tab[aria-selected="true"]{background:#17334f;border-color:#17334f;color:#fff}
	.bd-pane[hidden]{display:none!important}
	.bd-h{margin:14px 0 6px;font-size:13px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#7a5a17}
	.bd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px}
	.bd-grid.is-sm{grid-template-columns:repeat(auto-fill,minmax(130px,1fr))}
	.bd-opt{position:relative;display:block}
	.bd-opt input{position:absolute;opacity:0;pointer-events:none}
	.bd-opt>span{display:block;height:100%;padding:9px 11px;border:1px solid #d5dde6;border-radius:12px;background:#fff;cursor:pointer;font-size:14px;line-height:1.35;color:#17334f}
	.bd-opt>span>b{display:block;font-size:14.5px}
	.bd-opt>span>small{display:block;margin-top:2px;font-size:12.5px;color:#5b6470}
	.bd-opt input:checked+span{border-color:#17334f;box-shadow:0 0 0 2px #17334f inset;background:#f6f9fc}
	.bd-opt input:focus-visible+span{outline:2px solid #c9a052;outline-offset:2px}
	.bd-sw,.bd-opt .bd-sw{display:flex;gap:3px;margin-bottom:6px;padding:0;border:0;background:none;height:auto}.bd-sw i{display:block;width:26px;height:26px;border-radius:6px;border:1px solid rgba(0,0,0,.08)}
	.bd-ico{display:block;margin:0 0 6px;color:#17334f}
	.bd-presets{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
	.bd-preset{text-align:left;padding:12px;border:1px solid #d5dde6;border-radius:14px;background:#fffdf8;cursor:pointer;font-size:14px;color:#17334f}
	.bd-preset:hover,.bd-preset.is-on{border-color:#17334f;box-shadow:0 0 0 2px #17334f inset}
	.bd-preset b{display:block;font-size:15px;margin:6px 0 2px}
	.bd-preset small{display:block;color:#5b6470;font-size:12.5px;line-height:1.4}
	.bd-side{position:sticky;top:90px}
	.bd-prev{display:grid;grid-template-columns:1fr 1fr;gap:10px}
	.bd-prev figure{margin:0}.bd-prev figcaption{font-size:12px;font-weight:bold;color:#5b6470;margin:4px 0 0}
	.bd-prev img{display:block;width:100%;border-radius:6px;border:1px solid #e3e8ee;background:#f2f5f9}
	.bd-prev .is-wide{grid-column:1/-1}
	.bd-pages{display:grid;grid-template-columns:1fr 1fr;gap:6px}
	.bd-busy{font-size:13px;color:#7a5a17;font-weight:bold;min-height:18px}
	.bd-warn{margin:8px 0 0;padding:8px 10px;border-left:3px solid #c9a052;background:#fbf5e8;font-size:13.5px;border-radius:0 8px 8px 0}
	.bd-note{font-size:13px;color:#5b6470;margin:6px 0 0}
	.bd-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:16px;padding-top:14px;border-top:1px solid #edf0f3}
	.bd-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px}
	.bd-item{display:grid;grid-template-columns:52px minmax(0,1fr);gap:10px;padding:10px;border:1px solid #dfe5eb;border-radius:12px;background:#fff;color:#17334f;text-decoration:none}
	.bd-item img{width:52px;height:74px;object-fit:cover;border-radius:2px 4px 4px 2px;box-shadow:0 2px 6px rgba(15,42,74,.25);background:#e9edf2}
	.bd-item b{display:block;font-size:14.5px;line-height:1.3}.bd-item small{display:block;font-size:12.5px;color:#5b6470;margin-top:3px}
	.bd-tag{display:inline-block;font-size:11.5px;font-weight:bold;padding:1px 7px;border-radius:99px;background:#eef3f8;color:#2b4a68;margin-top:4px}
	.bd-tag.is-doc{background:#fbf0d9;color:#7a5a17}.bd-tag.is-series{background:#e5f3ec;color:#1f6b4d}
	</style>
	<?php
}

/** Biểu tượng nhỏ cho kiểu hiển thị 3D. */
function cvc_bd_display_icon( string $k ): string {
	$p = array(
		'tilt'   => '<path d="M14 6 34 9v34l-20 3Z" fill="currentColor" opacity=".85"/><path d="M10 7l4-1v40l-4-1Z" fill="currentColor" opacity=".45"/>',
		'flat'   => '<rect x="12" y="6" width="24" height="38" rx="1" fill="currentColor" opacity=".85"/><rect x="36" y="7" width="2" height="36" fill="currentColor" opacity=".35"/>',
		'lying'  => '<path d="M6 30 30 22 44 30 20 38Z" fill="currentColor" opacity=".85"/><path d="M6 30v4l14 8v-4Z" fill="currentColor" opacity=".4"/><path d="M20 38v4l24-8v-4Z" fill="currentColor" opacity=".25"/>',
		'corner' => '<path d="M20 6 38 10v32l-18 4Z" fill="currentColor" opacity=".85"/><path d="M10 9l10-3v40l-10-3Z" fill="currentColor" opacity=".5"/>',
		'spine'  => '<path d="M16 5h10v40H16Z" fill="currentColor" opacity=".85"/><path d="M26 5l8 3v34l-8 3Z" fill="currentColor" opacity=".4"/>',
		'stack'  => '<path d="M8 36 30 30 42 35 20 41Z" fill="currentColor" opacity=".35"/><path d="M8 29 30 23 42 28 20 34Z" fill="currentColor" opacity=".55"/><path d="M8 22 30 16 42 21 20 27Z" fill="currentColor" opacity=".9"/>',
	);

	return '<svg class="bd-ico" width="48" height="48" viewBox="0 0 48 48" aria-hidden="true">' . ( $p[ $k ] ?? '' ) . '</svg>';
}

/** Thu thiết kế từ form. */
function cvc_bd_collect(): array {
	$in  = (array) ( $_POST['design'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$out = array();
	foreach ( array( 'preset', 'layout', 'palette', 'art', 'motif', 'font', 'header', 'footer', 'spine', 'display' ) as $k ) {
		if ( isset( $in[ $k ] ) && '' !== $in[ $k ] ) {
			$out[ $k ] = sanitize_key( (string) $in[ $k ] );
		}
	}
	if ( isset( $in['volume'] ) && '' !== (string) $in['volume'] ) {
		$out['volume'] = (string) max( 1, min( 99, absint( $in['volume'] ) ) );
	}

	return $out;
}

/** Ô chọn dạng thẻ. $items: key => [label, hint, extra_html]. */
function cvc_bd_options( string $name, array $items, string $current, string $cls = '' ): void {
	echo '<div class="bd-grid ' . esc_attr( $cls ) . '" role="radiogroup">';
	foreach ( $items as $k => $it ) {
		echo '<label class="bd-opt"><input type="radio" name="design[' . esc_attr( $name ) . ']" value="' . esc_attr( (string) $k ) . '"' . checked( $current, (string) $k, false ) . '><span>'
			. ( $it[2] ?? '' ) // phpcs:ignore WordPress.Security.EscapeOutput
			. '<b>' . esc_html( (string) $it[0] ) . '</b>' . ( ! empty( $it[1] ) ? '<small>' . esc_html( (string) $it[1] ) . '</small>' : '' ) . '</span></label>';
	}
	echo '</div>';
}

function cvc_admin_render_bookdesign( string $item_param ): void {
	cvc_dg_styles();
	cvc_bd_styles();
	$id = absint( $item_param );
	if ( ! $id ) {
		cvc_bd_render_index();
		return;
	}
	$res = cvc_admin_api_get( '/api/admin/book-designs/' . $id );
	echo '<p class="text-sm"><a class="text-acc" href="' . esc_url( cvc_admin_url( 'thiet-ke-sach' ) ) . '">← Thiết kế sách</a> · <a class="text-acc" href="' . esc_url( cvc_admin_url( 'tai-lieu-ai' ) ) . '">Xưởng tài liệu</a></p>';
	if ( ! $res['ok'] ) {
		echo '<p class="text-sm text-red-700">' . esc_html( cvc_admin_error_message( $res ) ) . '</p>';
		return;
	}
	$d    = (array) $res['data']['data'];
	$doc  = (array) $d['document'];
	$cat  = (array) $d['catalog'];
	$r    = (array) $d['resolved'];
	$own  = (array) $d['design'];
	$img  = (array) $d['images'];
	$pg   = (int) ( $doc['page_count'] ?? 0 );
	$can  = cvc_admin_can( 'document.create' );
	$src  = array( 'doc' => 'Thiết kế riêng của cuốn này', 'series' => 'Theo mặc định của bộ "' . ( $doc['category_label'] ?? '' ) . '"', 'auto' => 'Tự động theo bộ sách' )[ $r['source'] ?? 'auto' ] ?? '';
	$edit = cvc_admin_url( 'thiet-ke-sach', $id );
	?>
	<header class="flex flex-wrap items-start justify-between gap-3">
		<div>
			<h1 class="text-2xl font-black text-ink">Thiết kế sách: <?php echo esc_html( (string) $doc['title'] ); ?></h1>
			<p class="dg-muted"><?php echo esc_html( (string) $doc['category_label'] ); ?><?php echo $pg ? ' · ' . (int) $pg . ' trang' : ''; ?> · Đang dùng: <b><?php echo esc_html( $src ); ?></b><?php echo ! empty( $own['preset'] ) ? ' (mẫu ' . esc_html( (string) $own['preset'] ) . ')' : ''; ?></p>
		</div>
		<div class="flex flex-wrap gap-2">
			<?php if ( 'published' === ( $doc['status'] ?? '' ) ) : ?><a class="dg-btn" href="<?php echo esc_url( cvc_document_url( (string) $doc['slug'] ) ); ?>" target="_blank" rel="noopener">Xem trên thư viện ↗</a><?php endif; ?>
			<?php if ( ! empty( $doc['generation_id'] ) ) : ?><a class="dg-btn" href="<?php echo esc_url( cvc_admin_url( 'tai-lieu-ai', (int) $doc['generation_id'] ) ); ?>">Bản biên soạn</a><?php elseif ( 'uploaded' === ( $doc['source_kind'] ?? '' ) ) : ?><a class="dg-btn" href="<?php echo esc_url( cvc_admin_url( 'tai-lieu-tai-len', $id ) ); ?>">Duyệt tài liệu</a><?php endif; ?>
		</div>
	</header>
	<?php if ( ! empty( $d['cover_art'] ) && 'auto' !== $d['cover_art'] ) : ?>
		<p class="bd-warn">Tài liệu này đang dùng <b><?php echo 'page1' === $d['cover_art'] ? 'trang 1 của PDF' : 'ảnh bìa tải lên ở Hồ sơ phát hành'; ?></b> làm bìa (giữ nguyên chữ trên ảnh). Bố cục, màu, họa tiết bên dưới chỉ áp khi đổi "Bìa" thành "Tự động theo bộ sách" ở trang Duyệt tài liệu, hoặc khi tải ảnh ở mục "Ảnh tải lên" bên dưới (ảnh không chữ, hệ thống đặt tiêu đề). Gáy, kiểu 3D vẫn áp bình thường.</p>
	<?php endif; ?>

	<form id="bd-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-id="<?php echo (int) $id; ?>" data-pages="<?php echo (int) $pg; ?>">
		<?php wp_nonce_field( 'cvc_admin_bookdesign' ); ?><input type="hidden" name="action" value="cvc_admin_bookdesign"><input type="hidden" name="id" value="<?php echo (int) $id; ?>">
		<input type="hidden" name="design[preset]" id="bd-preset" value="<?php echo esc_attr( (string) ( $own['preset'] ?? '' ) ); ?>">
		<section class="dg-card">
			<h2 class="font-black text-ink">1. Chọn bộ mẫu</h2>
			<p class="bd-note">Bấm một bộ mẫu để áp cả bìa, trang trong, gáy và kiểu 3D; sau đó chỉnh riêng từng phần ở các thẻ bên dưới.</p>
			<div class="bd-presets mt-2">
				<?php foreach ( (array) $d['presets'] as $p ) : ?>
					<?php $sp = (array) $p['spec']; $pal = (array) ( $cat['palettes'][ $sp['palette'] ?? 'navy' ] ?? array() ); ?>
					<button type="button" class="bd-preset<?php echo ( $own['preset'] ?? '' ) === $p['key'] ? ' is-on' : ''; ?>" data-key="<?php echo esc_attr( (string) $p['key'] ); ?>" data-spec="<?php echo esc_attr( wp_json_encode( $sp ) ); ?>">
						<span class="bd-sw"><i style="background:<?php echo esc_attr( (string) ( $pal['ground'] ?? '#0f2a4a' ) ); ?>"></i><i style="background:<?php echo esc_attr( (string) ( $pal['accent'] ?? '#c9a052' ) ); ?>"></i><i style="background:<?php echo esc_attr( (string) ( $pal['soft'] ?? '#e8d6ac' ) ); ?>"></i></span>
						<b><?php echo esc_html( (string) $p['name'] ); ?><?php echo empty( $p['system'] ) ? ' · Mẫu của tôi' : ''; ?></b>
						<small><?php echo esc_html( implode( ' · ', array_filter( array( $cat['layouts'][ $sp['layout'] ?? '' ]['label'] ?? '', $cat['palettes'][ $sp['palette'] ?? '' ]['label'] ?? '', 'header ' . mb_strtolower( (string) ( $cat['headers'][ $sp['header'] ?? '' ]['label'] ?? '' ) ), $cat['displays'][ $sp['display'] ?? '' ]['label'] ?? '' ) ) ) ); ?></small>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<div class="bd-wrap mt-3">
			<section class="dg-card">
				<h2 class="font-black text-ink mb-2">2. Chỉnh từng phần</h2>
				<div class="bd-tabs" role="tablist">
					<button type="button" class="bd-tab" role="tab" aria-selected="true" data-pane="bd-p-cover">Bìa</button>
					<button type="button" class="bd-tab" role="tab" aria-selected="false" data-pane="bd-p-page">Trang trong (header / footer)</button>
					<button type="button" class="bd-tab" role="tab" aria-selected="false" data-pane="bd-p-3d">Gáy &amp; kiểu 3D</button>
				</div>

				<div class="bd-pane" id="bd-p-cover" role="tabpanel">
					<p class="bd-h">Bố cục</p>
					<?php
					$lay = array();
					foreach ( (array) $cat['layouts'] as $k => $v ) {
						$lay[ $k ] = array( $v['label'], $v['hint'] . ( ! empty( $v['own_art'] ) && 'photo' !== $k ? ' (hình riêng)' : '' ) );
					}
					cvc_bd_options( 'layout', $lay, (string) $r['layout'] );
					?>
					<p class="bd-h">Bảng màu</p>
					<?php
					$pals = array( 'auto' => array( 'Tự động theo ngành', 'Giáo dục xanh bảng, y tế xanh ngọc, còn lại navy', '<span class="bd-sw"><i style="background:#0f2a4a"></i><i style="background:#12392f"></i><i style="background:#0e3640"></i></span>' ) );
					foreach ( (array) $cat['palettes'] as $k => $v ) {
						$pals[ $k ] = array( $v['label'], '', '<span class="bd-sw"><i style="background:' . esc_attr( $v['ground'] ) . '"></i><i style="background:' . esc_attr( $v['accent'] ) . '"></i><i style="background:' . esc_attr( $v['soft'] ) . '"></i></span>' );
					}
					cvc_bd_options( 'palette', $pals, (string) $r['palette'], 'is-sm' );
					?>
					<p class="bd-h">Hình trên bìa</p>
					<?php cvc_bd_options( 'art', array( 'motif' => array( 'Họa tiết tự vẽ', 'Chọn họa tiết bên dưới' ), 'upload' => array( 'Ảnh tải lên', 'Ảnh của bạn, bố cục Ảnh tràn bìa' ), 'none' => array( 'Không hình', 'Chỉ chữ' ) ), (string) $r['art'], 'is-sm' ); ?>
					<div id="bd-motifs" class="mt-2">
						<?php
						$mot = array();
						foreach ( (array) $cat['motifs'] as $k => $v ) {
							if ( 'none' !== $k ) {
								$mot[ $k ] = array( $v, '' );
							}
						}
						cvc_bd_options( 'motif', $mot, (string) $r['motif'], 'is-sm' );
						?>
						<p class="bd-note">Họa tiết dùng cho bố cục Mặt tiền, Chia khối, Dải chéo, Khung viền cổ điển. Các bố cục Phiếu trắc nghiệm, Đề ôn luyện, Hồ sơ, Sơ đồ, Văn bản luật có hình riêng.</p>
					</div>
					<p class="bd-h">Phông chữ tiêu đề</p>
					<?php
					$fonts = array();
					foreach ( (array) $cat['fonts'] as $k => $v ) {
						$fonts[ $k ] = array( $v, '' );
					}
					cvc_bd_options( 'font', $fonts, (string) $r['font'], 'is-sm' );
					?>
				</div>

				<div class="bd-pane" id="bd-p-page" role="tabpanel" hidden>
					<?php if ( empty( $d['relaid'] ) ) : ?>
						<p class="bd-warn">Tệp tải về của tài liệu này là <b>PDF gốc giữ nguyên</b> (chỉ thêm bìa) nên header / footer <b>không áp</b> vào tệp - đúng quyết định không đụng tới PDF giữ nguyên. Chọn ở đây chỉ có tác dụng nếu sau này tài liệu được dàn trang lại (Word → PDF Công Viên Chức).</p>
					<?php else : ?>
						<p class="bd-note">Áp vào bản PDF dàn trang Công Viên Chức ở lần "Dựng bản phát hành" / "Dựng lại PDF" tiếp theo. Mép dưới luôn chừa chỗ cho dòng đóng dấu người mua.</p>
					<?php endif; ?>
					<p class="bd-h">Header</p>
					<?php
					$hd = array();
					foreach ( (array) $cat['headers'] as $k => $v ) {
						$hd[ $k ] = array( $v['label'], $v['hint'] );
					}
					cvc_bd_options( 'header', $hd, (string) $r['header'] );
					?>
					<p class="bd-h">Footer</p>
					<?php
					$ft = array();
					foreach ( (array) $cat['footers'] as $k => $v ) {
						$ft[ $k ] = array( $v['label'], $v['hint'] );
					}
					cvc_bd_options( 'footer', $ft, (string) $r['footer'] );
					?>
				</div>

				<div class="bd-pane" id="bd-p-3d" role="tabpanel" hidden>
					<p class="bd-h">Gáy sách</p>
					<?php
					$sp = array();
					foreach ( (array) $cat['spines'] as $k => $v ) {
						$sp[ $k ] = array( $v['label'], $v['hint'] );
					}
					cvc_bd_options( 'spine', $sp, (string) $r['spine'] );
					?>
					<label class="block text-sm font-bold mt-2">Số tập (cho gáy "Số tập"; bỏ trống = in năm)<br><input type="number" name="design[volume]" min="1" max="99" value="<?php echo esc_attr( (string) ( $own['volume'] ?? '' ) ); ?>" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:100px"></label>
					<p class="bd-h">Kiểu hiển thị 3D (mặc định của cuốn này)</p>
					<?php
					$dp = array();
					foreach ( (array) $cat['displays'] as $k => $v ) {
						$dp[ $k ] = array( $v['label'], $v['hint'], cvc_bd_display_icon( (string) $k ) );
					}
					cvc_bd_options( 'display', $dp, (string) $r['display'], 'is-sm' );
					?>
					<p class="bd-warn" id="bd-thin" hidden>Sách <?php echo (int) $pg; ?> trang khá mỏng: kiểu này sẽ thấy gáy rất mảnh. Nên dùng Nghiêng hoặc Phẳng.</p>
					<p class="bd-note">Kiểu ở đây áp cho kệ sách, cột "Sách nổi bật", ảnh chia sẻ - trừ vị trí đã bị ép kiểu ở trang <a class="text-acc" href="<?php echo esc_url( cvc_admin_url( 'thiet-ke-sach' ) ); ?>">Thiết kế sách</a>. Trang chi tiết (sách 3D lật bìa được) chỉ có Nghiêng / Phẳng / Góc chéo; kiểu khác hiện Nghiêng.</p>
				</div>

				<?php if ( $can ) : ?>
				<div class="bd-actions">
					<button class="dg-btn is-primary" name="op" value="save">💾 Lưu thiết kế</button>
					<button class="dg-btn" name="op" value="reset" onclick="return confirm('Bỏ thiết kế riêng, dùng mặc định của bộ / tự động?')">Về tự động</button>
					<span class="dg-muted" style="margin-left:auto"></span>
				</div>
				<div class="bd-actions">
					<button class="dg-btn" name="op" value="series" onclick="return confirm('Áp thiết kế đang chọn cho mọi cuốn thuộc bộ &quot;<?php echo esc_attr( (string) $doc['category_label'] ); ?>&quot;?')">📚 Áp cho cả bộ "<?php echo esc_html( (string) $doc['category_label'] ); ?>" (<?php echo (int) $d['series_count']; ?> cuốn)</button>
					<label class="text-sm"><input type="checkbox" name="overwrite" value="1"> Ghi đè cả những cuốn đã chỉnh riêng</label>
					<?php if ( ! empty( $d['series_default'] ) ) : ?><button class="dg-btn is-warn" name="op" value="series_clear" onclick="return confirm('Bỏ mặc định của bộ sách?')">Bỏ mặc định của bộ</button><?php endif; ?>
				</div>
				<div class="bd-actions">
					<input type="text" name="preset_name" maxlength="80" placeholder="Tên mẫu, vd. Cẩm nang xanh rêu" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:260px">
					<button class="dg-btn" name="op" value="preset_save">⭐ Lưu thành Mẫu của tôi</button>
				</div>
				<?php endif; ?>
			</section>

			<aside class="dg-card bd-side" aria-label="Xem trước">
				<div class="flex items-center justify-between gap-2"><h2 class="font-black text-ink">Xem trước</h2><button type="button" class="dg-btn" id="bd-run">↻ Dựng xem trước</button></div>
				<p class="bd-busy" id="bd-busy" aria-live="polite"></p>
				<div class="bd-prev">
					<figure><img id="bd-i-cover" src="<?php echo esc_url( (string) ( $img['cover'] ?? '' ) ); ?>" alt="Bìa"><figcaption>Bìa</figcaption></figure>
					<figure><img id="bd-i-card" src="<?php echo esc_url( (string) ( $img['card'] ?? '' ) ); ?>" alt="Sách 3D"><figcaption id="bd-c-card">Sách 3D</figcaption></figure>
					<figure class="is-wide"><div class="bd-pages" id="bd-i-pages"></div><figcaption>Trang trong (trang chẵn · trang lẻ)</figcaption></figure>
					<figure class="is-wide"><img id="bd-i-og" src="<?php echo esc_url( (string) ( $img['og'] ?? '' ) ); ?>" alt="Ảnh chia sẻ"><figcaption>Ảnh chia sẻ (Facebook, Zalo)</figcaption></figure>
					<figure><img id="bd-i-spine" src="" alt="Gáy" style="width:auto;max-height:280px;margin:0 auto"><figcaption>Gáy</figcaption></figure>
				</div>
				<p class="bd-note">Xem trước dùng tiêu đề, số trang, nội dung thật; chưa lưu cho tới khi bấm "Lưu thiết kế".</p>
			</aside>
		</div>
	</form>

	<?php if ( $can ) : ?>
	<section class="dg-card mt-3" id="bd-upload">
		<h2 class="font-black text-ink mb-1">Ảnh tải lên (bố cục Ảnh tràn bìa)</h2>
		<p class="bd-note">Ảnh không chữ của bạn (chụp, tự thiết kế). Hệ thống phủ lớp màu, đặt tiêu đề, giữ logo + QR + congvienchuc.com. Không dùng ảnh lấy từ trang web khác. Cạnh dài từ 1600 px.</p>
		<?php if ( ! empty( $own['image'] ) ) : ?><p class="text-sm mt-1">Đang có ảnh: <code><?php echo esc_html( basename( (string) $own['image'] ) ); ?></code></p><?php endif; ?>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-wrap gap-3 items-end mt-2">
			<?php wp_nonce_field( 'cvc_admin_bookdesign' ); ?><input type="hidden" name="action" value="cvc_admin_bookdesign"><input type="hidden" name="op" value="image"><input type="hidden" name="id" value="<?php echo (int) $id; ?>">
			<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
			<label class="text-sm"><input type="checkbox" name="rights" value="1" required> Tôi có quyền dùng ảnh này (không lấy từ trang web khác khi chưa được phép).</label>
			<button class="dg-btn">🖼 Dùng ảnh này</button>
		</form>
	</section>
	<?php endif; ?>

	<script>
	(function(){
		var form=document.getElementById('bd-form'); if(!form){return;}
		var ajax=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, nonce=<?php echo wp_json_encode( wp_create_nonce( 'cvc_admin_ajax' ) ); ?>;
		var pages=parseInt(form.dataset.pages||'0',10), busy=document.getElementById('bd-busy'), timer=null, seq=0;
		document.querySelectorAll('.bd-tab').forEach(function(t){t.addEventListener('click',function(){
			document.querySelectorAll('.bd-tab').forEach(function(x){x.setAttribute('aria-selected',x===t?'true':'false');});
			document.querySelectorAll('.bd-pane').forEach(function(p){p.hidden=p.id!==t.dataset.pane;});
		});});
		function val(n){var c=form.querySelector('input[name="design['+n+']"]:checked');return c?c.value:'';}
		function sync(){
			var art=val('art');document.getElementById('bd-motifs').style.display=art==='motif'?'':'none';
			var thin=document.getElementById('bd-thin');if(thin){thin.hidden=!(pages>0&&pages<60&&['lying','corner','spine','stack'].indexOf(val('display'))>=0);}
		}
		function img(id,b64){var el=document.getElementById(id);if(el&&b64){el.src='data:image/jpeg;base64,'+b64;}}
		function run(){
			var my=++seq;busy.textContent='Đang dựng xem trước…';
			var fd=new FormData(form);fd.delete('action');fd.delete('op');fd.append('action','cvc_admin_bookdesign_preview');fd.append('nonce',nonce);
			fetch(ajax,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
				if(my!==seq){return;}
				if(!j||!j.success){busy.textContent=(j&&j.data&&j.data.message)||'Chưa dựng được xem trước.';return;}
				var d=j.data;img('bd-i-cover',d.cover);img('bd-i-card',d.card);img('bd-i-og',d.og);img('bd-i-spine',d.spine);
				var lab=document.querySelector('input[name="design[display]"][value="'+d.display+'"]');
				document.getElementById('bd-c-card').textContent='Sách 3D'+(lab?' · '+lab.parentNode.querySelector('b').textContent:'');
				var box=document.getElementById('bd-i-pages');box.innerHTML='';
				(d.pages||[]).forEach(function(p){var i=document.createElement('img');i.src='data:image/jpeg;base64,'+p;i.alt='Trang mẫu';box.appendChild(i);});
				if(!(d.pages||[]).length){box.innerHTML='<p class="bd-note">Tài liệu giữ PDF gốc hoặc chưa có nội dung chữ: không có trang mẫu.</p>';}
				busy.textContent='';
			}).catch(function(){if(my===seq){busy.textContent='Lỗi mạng khi dựng xem trước.';}});
		}
		function later(){clearTimeout(timer);timer=setTimeout(run,650);}
		form.addEventListener('change',function(e){if(e.target.name&&e.target.name.indexOf('design[')===0){sync();later();}});
		document.getElementById('bd-run').addEventListener('click',run);
		document.querySelectorAll('.bd-preset').forEach(function(b){b.addEventListener('click',function(){
			var sp={};try{sp=JSON.parse(b.dataset.spec||'{}');}catch(x){}
			Object.keys(sp).forEach(function(k){var i=form.querySelector('input[name="design['+k+']"][value="'+sp[k]+'"]');if(i){i.checked=true;}});
			document.getElementById('bd-preset').value=b.dataset.key;
			document.querySelectorAll('.bd-preset').forEach(function(x){x.classList.toggle('is-on',x===b);});
			sync();run();
		});});
		sync();run();
	})();
	</script>
	<?php
}

/** Trang danh sách: kiểu theo vị trí + Mẫu của tôi + mọi tài liệu. */
function cvc_bd_render_index(): void {
	$res = cvc_admin_api_get( '/api/admin/book-designs' );
	echo '<p class="text-sm"><a class="text-acc" href="' . esc_url( cvc_admin_url( 'tai-lieu-ai' ) ) . '">← Xưởng tài liệu</a></p>';
	echo '<h1 class="text-2xl font-black text-ink">Thiết kế sách</h1>';
	if ( ! $res['ok'] ) {
		echo '<p class="text-sm text-red-700">' . esc_html( cvc_admin_error_message( $res ) ) . '</p>';
		return;
	}
	$d    = (array) $res['data']['data'];
	$cat  = (array) $d['catalog'];
	$pl   = (array) $d['placements'];
	$can  = cvc_admin_can( 'document.create' );
	?>
	<p class="dg-muted">Chọn một cuốn để đổi bìa, header / footer, gáy và kiểu hiển thị 3D. Mỗi cuốn có thể theo mẫu, theo mặc định của bộ sách, hoặc chỉnh riêng.</p>
	<section class="dg-card mt-3">
		<h2 class="font-black text-ink mb-1">Kiểu hiển thị theo vị trí (toàn site)</h2>
		<p class="bd-note">"Theo từng sách" = dùng kiểu 3D đã chọn cho mỗi cuốn. Chọn một kiểu để ép mọi cuốn hiển thị giống nhau ở vị trí đó (trang nhìn đồng đều).</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="grid gap-3 mt-2" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end">
			<?php wp_nonce_field( 'cvc_admin_bookdesign' ); ?><input type="hidden" name="action" value="cvc_admin_bookdesign"><input type="hidden" name="op" value="placements">
			<?php foreach ( (array) $cat['placements'] as $k => $label ) : ?>
				<label class="text-sm font-bold"><?php echo esc_html( (string) $label ); ?><br>
					<select name="placements[<?php echo esc_attr( (string) $k ); ?>]" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm" style="width:100%">
						<option value="">Theo từng sách</option>
						<?php foreach ( (array) $cat['displays'] as $m => $dv ) : ?>
							<?php if ( 'detail' === $k && ! in_array( $m, (array) $cat['detail_displays'], true ) ) { continue; } ?>
							<option value="<?php echo esc_attr( (string) $m ); ?>" <?php selected( (string) ( $pl[ $k ] ?? '' ), (string) $m ); ?>><?php echo esc_html( (string) $dv['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endforeach; ?>
			<?php if ( $can ) : ?><div><button class="dg-btn is-primary">Lưu kiểu theo vị trí</button></div><?php endif; ?>
		</form>
	</section>

	<?php $mine = array_values( array_filter( (array) $d['presets'], static fn ( $p ) => empty( $p['system'] ) ) ); ?>
	<section class="dg-card mt-3">
		<h2 class="font-black text-ink mb-1">Mẫu của tôi</h2>
		<?php if ( ! $mine ) : ?><p class="bd-note">Chưa có. Mở một cuốn, chỉnh xong bấm "Lưu thành Mẫu của tôi".</p><?php endif; ?>
		<?php foreach ( $mine as $p ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex items-center gap-3 mt-1">
				<?php wp_nonce_field( 'cvc_admin_bookdesign' ); ?><input type="hidden" name="action" value="cvc_admin_bookdesign"><input type="hidden" name="op" value="preset_delete"><input type="hidden" name="key" value="<?php echo esc_attr( (string) $p['key'] ); ?>">
				<b class="text-sm"><?php echo esc_html( (string) $p['name'] ); ?></b>
				<span class="dg-muted"><?php echo esc_html( implode( ' · ', array_filter( array( $cat['layouts'][ $p['spec']['layout'] ?? '' ]['label'] ?? '', $cat['palettes'][ $p['spec']['palette'] ?? '' ]['label'] ?? '', $cat['displays'][ $p['spec']['display'] ?? '' ]['label'] ?? '' ) ) ) ); ?></span>
				<?php if ( $can ) : ?><button class="dg-btn is-warn" onclick="return confirm('Xóa mẫu này?')">Xóa</button><?php endif; ?>
			</form>
		<?php endforeach; ?>
	</section>

	<section class="dg-card mt-3">
		<h2 class="font-black text-ink mb-2">Tài liệu</h2>
		<div class="bd-list">
			<?php foreach ( (array) $d['documents'] as $x ) : ?>
				<?php $tag = array( 'doc' => array( 'Thiết kế riêng', 'is-doc' ), 'series' => array( 'Mặc định của bộ', 'is-series' ), 'auto' => array( 'Tự động', '' ) )[ $x['source'] ] ?? array( '', '' ); ?>
				<a class="bd-item" href="<?php echo esc_url( cvc_admin_url( 'thiet-ke-sach', (int) $x['id'] ) ); ?>">
					<?php if ( ! empty( $x['thumb'] ) ) : ?><img src="<?php echo esc_url( (string) $x['thumb'] ); ?>" alt="" loading="lazy"><?php else : ?><span style="width:52px;height:74px;background:#e9edf2;border-radius:3px"></span><?php endif; ?>
					<span><b><?php echo esc_html( (string) $x['title'] ); ?></b><small><?php echo esc_html( (string) $x['category_label'] ); ?><?php echo ! empty( $x['page_count'] ) ? ' · ' . (int) $x['page_count'] . ' trang' : ''; ?><?php echo 'published' !== $x['status'] ? ' · Nháp' : ''; ?></small><span class="bd-tag <?php echo esc_attr( $tag[1] ); ?>"><?php echo esc_html( $tag[0] ); ?></span></span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

add_action( 'admin_post_cvc_admin_bookdesign', 'cvc_admin_handle_bookdesign' );
add_action( 'admin_post_nopriv_cvc_admin_bookdesign', 'cvc_admin_handle_bookdesign' );
function cvc_admin_handle_bookdesign(): void {
	check_admin_referer( 'cvc_admin_bookdesign' );
	$token = cvc_admin_require_staff();
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$id    = absint( $_POST['id'] ?? 0 );
	$back  = $id ? cvc_admin_url( 'thiet-ke-sach', $id ) : cvc_admin_url( 'thiet-ke-sach' );
	if ( ! cvc_admin_can( 'document.create' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền.' );
		return;
	}
	$cli = new CVC_Api_Client( null, 90 );
	$res = array( 'ok' => false );
	switch ( $op ) {
		case 'save':
		case 'reset':
			$res = $cli->post( '/api/admin/book-designs/' . $id, array( 'design' => cvc_bd_collect(), 'reset' => 'reset' === $op ? '1' : '0' ), $token );
			break;
		case 'series':
		case 'series_clear':
			$res = $cli->post( '/api/admin/book-designs/' . $id . '/series', array( 'design' => cvc_bd_collect(), 'overwrite' => empty( $_POST['overwrite'] ) ? '0' : '1', 'clear' => 'series_clear' === $op ? '1' : '0' ), $token );
			break;
		case 'preset_save':
			$res = $cli->post( '/api/admin/book-designs/presets', array( 'name' => sanitize_text_field( wp_unslash( (string) ( $_POST['preset_name'] ?? '' ) ) ), 'design' => cvc_bd_collect() ), $token );
			break;
		case 'preset_delete':
			$res = $cli->post( '/api/admin/book-designs/presets', array( 'delete' => sanitize_text_field( wp_unslash( (string) ( $_POST['key'] ?? '' ) ) ) ), $token );
			break;
		case 'placements':
			$pl = array();
			foreach ( (array) ( $_POST['placements'] ?? array() ) as $k => $v ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$pl[ sanitize_key( (string) $k ) ] = sanitize_key( (string) $v );
			}
			$res = $cli->post( '/api/admin/book-designs/placements', array( 'placements' => $pl ), $token );
			break;
		case 'image':
			$f = $_FILES['image'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( ! is_array( $f ) || UPLOAD_ERR_OK !== (int) ( $f['error'] ?? 1 ) || ! is_uploaded_file( (string) $f['tmp_name'] ) ) {
				cvc_admin_redirect( $back, 'error', 'Chưa nhận được ảnh (≤ 15 MB).' );
				return;
			}
			$res = $cli->post_multipart( '/api/admin/book-designs/' . $id . '/image', array( 'rights' => empty( $_POST['rights'] ) ? '' : '1' ), array( 'image' => array( (string) $f['tmp_name'], sanitize_file_name( (string) $f['name'] ), (string) ( wp_check_filetype( (string) $f['name'] )['type'] ?: 'image/jpeg' ) ) ), $token );
			break;
	}
	cvc_admin_redirect( $back, $res['ok'] ? 'success' : 'error', $res['ok'] ? (string) ( $res['data']['message'] ?? 'Đã lưu.' ) : cvc_admin_error_message( $res ) );
}

/** Xem trước (AJAX, không lưu). */
add_action( 'wp_ajax_cvc_admin_bookdesign_preview', 'cvc_admin_ajax_bookdesign_preview' );
add_action( 'wp_ajax_nopriv_cvc_admin_bookdesign_preview', 'cvc_admin_ajax_bookdesign_preview' );
function cvc_admin_ajax_bookdesign_preview(): void {
	check_ajax_referer( 'cvc_admin_ajax', 'nonce' );
	if ( ! cvc_admin_is_staff() || ! cvc_admin_can( 'document.create' ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	$id  = absint( $_POST['id'] ?? 0 );
	$res = ( new CVC_Api_Client( null, 60 ) )->post( '/api/admin/book-designs/' . $id . '/preview', array( 'design' => cvc_bd_collect() ), cvc_auth_token() );
	if ( ! $res['ok'] ) {
		wp_send_json_error( array( 'message' => cvc_admin_error_message( $res ) ) );
	}
	wp_send_json_success( (array) ( $res['data']['data'] ?? array() ) );
}
