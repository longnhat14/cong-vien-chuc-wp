<?php
/**
 * Vị trí việc làm (01/11/2026) - trang tổng hợp văn bản về vị trí việc làm của công chức, viên chức.
 * URL: /vi-tri-viec-lam/ ; menu con của "Văn bản pháp luật".
 *
 * Dữ liệu thật từ GET /api/legal-documents/vtvl (danh sách văn bản chọn theo số hiệu, hiệu lực, số bài tóm tắt theo Điều).
 * Kết luận 107-KL/TW và Hướng dẫn 07-HD/BTCTW được nhập từ Cổng Thông tin điện tử Đảng Cộng sản Việt Nam
 * (nguồn chính thức); các Kế hoạch 70, 71 chưa có toàn văn nên chỉ dẫn liên kết tham khảo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$res    = cvc_ci_api( '/api/legal-documents/vtvl', array(), null, 12, 300 );
$ok     = (bool) $res['ok'];
$data   = $ok ? (array) ( $res['data']['data'] ?? array() ) : array();
$groups = (array) ( $data['groups'] ?? array() );
$total  = (int) ( $data['total'] ?? 0 );
$less   = (int) ( $data['lessons'] ?? 0 );
$nf     = static fn ( $n ): string => number_format( (int) $n, 0, ',', '.' );

$central = array(
	array( 'Cổng Thông tin điện tử Đảng Cộng sản Việt Nam – chuyên mục Hỗ trợ vị trí việc làm', 'https://dangcongsan.vn/ho-tro-vi-tri-viec-lam', 'Tập hợp bài hướng dẫn kê khai, đo thời gian, mã hóa nhiệm vụ theo từng biểu mẫu.' ),
	array( 'Kế hoạch xây dựng cơ sở dữ liệu vị trí việc làm gắn với phương án giao biên chế giai đoạn 2027–2031', 'https://dangcongsan.vn/co-quan-dang-trung-uong/ke-hoach-xay-dung-co-so-du-lieu-vi-tri-viec-lam-gan-voi-phuong-an-giao-bien-che-giai-doan-2027-2031.html', 'Mục tiêu, bước thực hiện và thời hạn của Trung ương.' ),
	array( 'Khung danh mục vị trí việc làm dùng chung, bản mô tả công việc và khung năng lực mẫu', 'https://dangcongsan.vn/tin-hoat-dong/khung-danh-muc-vi-tri-viec-lam-dung-chung-ban-mo-ta-cong-viec-va-khung-nang-luc-mau.html', 'Tài liệu mẫu để đơn vị đối chiếu khi rà soát vị trí.' ),
	array( 'Phần mềm, cơ sở dữ liệu dùng chung; phần mềm hỗ trợ kê khai trực tuyến', 'https://dangcongsan.vn/tin-hoat-dong/phan-mem-co-so-du-lieu-dung-chung-phan-mem-ho-tro-ke-khai-truc-tuyen.html', 'Cách đơn vị kê khai trực tuyến và tài khoản truy cập.' ),
);

$crumbs = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Văn bản pháp luật', 'url' => cvc_legal_documents_url() ),
	array( 'label' => 'Vị trí việc làm' ),
);
cvc_seo_set_title( 'Vị trí việc làm công chức, viên chức: văn bản, hiệu lực, tóm tắt từng Điều' );
cvc_seo_set_description( 'Văn bản về xây dựng, phê duyệt và quản lý vị trí việc làm của công chức, viên chức: Nghị định 361/2025/NĐ-CP, Nghị định 232/2026/NĐ-CP, thông tư hướng dẫn, hiệu lực và tóm tắt từng Điều.' );
cvc_seo_set_canonical( cvc_vtvl_url() );
cvc_seo_add_breadcrumb_jsonld( $crumbs );
if ( ! $ok ) {
	nocache_headers();
}

$state_tag = static function ( array $d ): string {
	$st = (string) ( $d['state'] ?? '' );
	if ( 'partly_replaced' === $st && ! empty( $d['replaced_partly_by']['slug'] ) ) {
		return '<a class="lg-tag is-gold" href="' . esc_url( cvc_legal_document_url( (string) $d['replaced_partly_by']['slug'] ) ) . '">◐ Hết hiệu lực một phần – xem ' . esc_html( (string) $d['replaced_partly_by']['document_number'] ) . '</a>';
	}
	if ( 'replaced' === $st || 'expired' === $st ) {
		return '<span class="lg-tag is-red">⊘ Hết hiệu lực</span>';
	}
	if ( 'future' === $st ) {
		return '<span class="lg-tag is-blue">◷ Hiệu lực từ ' . esc_html( cvc_format_date_vn( (string) $d['effective_date'] ) ) . '</span>';
	}

	return '<span class="lg-tag is-green">● Còn hiệu lực' . ( ! empty( $d['effective_date'] ) ? ' (từ ' . esc_html( cvc_format_date_vn( (string) $d['effective_date'] ) ) . ')' : '' ) . '</span>';
};

get_header();
?>
<main id="main" class="lg vt">
<div class="lg-wrap">
	<?php cvc_render_breadcrumbs( $crumbs ); ?>

	<section class="lg-hero">
		<div>
			<p class="lg-eyebrow">● CÔNG CHỨC · VIÊN CHỨC · CHỈ ĐẠO CỦA TRUNG ƯƠNG</p>
			<h1>Vị trí việc làm</h1>
			<p class="lg-lead">Tập hợp văn bản về xây dựng, phê duyệt và quản lý vị trí việc làm của công chức, viên chức. Mỗi văn bản có trạng thái hiệu lực, nguồn chính thức và tóm tắt từng Điều để tra cứu nhanh.</p>
			<p class="lg-actions">
				<a class="lg-btn is-primary" href="#nhom-cong-chuc">§ Công chức</a>
				<a class="lg-btn" href="#nhom-vien-chuc">◎ Viên chức</a>
				<a class="lg-btn" href="#nhom-trung-uong">▣ Chỉ đạo của Trung ương</a>
			</p>
		</div>
		<aside class="lg-hero-stat">
			<small>Đã thu thập</small>
			<strong><?php echo $ok ? esc_html( $nf( $total ) ) . ' văn bản' : '—'; ?></strong>
			<span><?php echo $ok && $less > 0 ? esc_html( $nf( $less ) ) . ' bài tóm tắt theo Điều' : 'Tóm tắt theo từng Điều'; ?></span>
		</aside>
	</section>

	<section class="vt-two" aria-label="Hai việc đơn vị cần phân biệt">
		<div class="vt-card">
			<h2>1 · Hồ sơ phê duyệt vị trí việc làm của đơn vị</h2>
			<p>Công chức theo Nghị định 361/2025/NĐ-CP; viên chức theo Nghị định 232/2026/NĐ-CP. Hai nghị định thay các quy định về vị trí việc làm trong Nghị định 62/2020/NĐ-CP và Nghị định 106/2020/NĐ-CP.</p>
		</div>
		<div class="vt-card">
			<h2>2 · Cơ sở dữ liệu vị trí việc làm theo chỉ đạo của Trung ương</h2>
			<p>Việc kê khai gắn với phương án giao biên chế giai đoạn 2027–2031, thực hiện theo Kết luận 107-KL/TW và hướng dẫn của Ban Tổ chức Trung ương. Đây là việc khác với hồ sơ phê duyệt ở mục 1.</p>
		</div>
	</section>

	<?php if ( ! $ok ) : ?>
		<div class="lg-empty"><b>Chưa tải được danh sách văn bản.</b> Hệ thống đang bận — vui lòng <a href="<?php echo esc_url( cvc_vtvl_url() ); ?>">tải lại trang</a> sau ít phút.</div>
	<?php else : ?>
		<?php foreach ( $groups as $g ) : ?>
			<?php
			$items = (array) ( $g['items'] ?? array() );
			if ( ! $items ) {
				continue;
			}
			$anchor = array( 'cong-chuc' => 'nhom-cong-chuc', 'vien-chuc' => 'nhom-vien-chuc', 'trung-uong' => 'nhom-trung-uong', 'can-cu-chung' => 'nhom-can-cu-chung' )[ (string) $g['key'] ] ?? 'nhom-' . sanitize_title( (string) $g['key'] );
			?>
			<section class="vt-group" id="<?php echo esc_attr( $anchor ); ?>">
				<div class="lg-listhead"><div><h2><?php echo esc_html( (string) $g['label'] ); ?></h2><p><?php echo (int) count( $items ); ?> văn bản</p></div></div>
				<div class="lg-docs">
					<?php foreach ( $items as $d ) : ?>
						<?php
						if ( empty( $d['slug'] ) ) {
							continue;
						}
						$url   = cvc_legal_document_url( (string) $d['slug'] );
						$stale = in_array( (string) ( $d['state'] ?? '' ), array( 'expired', 'replaced', 'partly_replaced' ), true );
						?>
						<article class="lg-doc<?php echo $stale ? ' is-stale' : ''; ?>">
							<div class="lg-code"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $d['document_number'] ); ?></a><small><?php echo esc_html( (string) ( $d['document_type'] ?? '' ) ); ?></small></div>
							<div class="lg-docbody">
								<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $d['title'] ); ?></a></h3>
								<p class="lg-meta">
									<?php echo ! empty( $d['verified'] ) ? '<span class="lg-tag is-green">✓ Đã đối chiếu nguồn</span>' : '<span class="lg-tag is-gold">◷ Cần rà soát nguồn</span>'; ?>
									<?php echo $state_tag( $d ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php if ( ! empty( $d['issuing_agency'] ) ) : ?><span class="lg-tag"><?php echo esc_html( (string) $d['issuing_agency'] ); ?></span><?php endif; ?>
									<?php if ( ! empty( $d['issued_date'] ) ) : ?><span class="lg-tag">Ban hành <?php echo esc_html( cvc_format_date_vn( (string) $d['issued_date'] ) ); ?></span><?php endif; ?>
									<?php if ( ! empty( $d['articles_count'] ) ) : ?><span class="lg-tag is-soft"><?php echo (int) $d['articles_count']; ?> điều</span><?php endif; ?>
									<?php if ( ! empty( $d['lessons_count'] ) ) : ?><span class="lg-tag is-soft"><?php echo (int) $d['lessons_count']; ?> bài tóm tắt</span><?php endif; ?>
								</p>
								<?php if ( ! empty( $d['note'] ) ) : ?><p class="vt-note"><?php echo esc_html( (string) $d['note'] ); ?></p><?php endif; ?>
							</div>
							<div class="lg-docactions">
								<a class="lg-btn is-primary" href="<?php echo esc_url( $url ); ?>">Xem tóm tắt ↗</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>

	<section class="vt-group" id="huong-dan-tham-khao">
		<div class="lg-listhead"><div><h2>Tài liệu hướng dẫn thêm</h2><p>Bài hướng dẫn và công cụ kê khai trên Cổng Thông tin điện tử Đảng Cộng sản Việt Nam.</p></div></div>
		<ul class="vt-links">
			<?php foreach ( $central as $c ) : ?>
				<li><a href="<?php echo esc_url( $c[1] ); ?>" target="_blank" rel="noopener nofollow"><b><?php echo esc_html( $c[0] ); ?> ↗</b></a><span><?php echo esc_html( $c[2] ); ?></span></li>
			<?php endforeach; ?>
		</ul>
		<p class="lg-note">Các liên kết trên là bài đăng và tài liệu hướng dẫn trên Cổng Thông tin điện tử Đảng Cộng sản Việt Nam, chưa phải toàn văn văn bản; các Kế hoạch 70, 71 sẽ được bổ sung khi có bản toàn văn.</p>
	</section>

	<p class="lg-note">Văn bản có giá trị pháp lý là bản do cơ quan có thẩm quyền ban hành. Hãy kiểm tra văn bản sửa đổi, thay thế mới nhất và hướng dẫn của cơ quan quản lý ngành trước khi áp dụng.</p>
</div>
</main>
<?php
get_footer();
