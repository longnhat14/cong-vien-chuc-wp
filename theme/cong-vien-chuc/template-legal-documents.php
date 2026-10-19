<?php
/**
 * Văn bản pháp luật - trang tổng hợp v2 (19/10/2026), thiết kế congvienchuc-van-ban-phap-luat-v2.
 * URL: /van-ban-phap-luat/ và /van-ban-phap-luat/page/{n}/
 *
 * Dữ liệu thật từ GET /api/legal-documents/catalog (lọc + số liệu). Header / footer: khung chung của site.
 * Tham số: ?q= ?loai=Luật,Thông tư (hoặc loai[]) ?co-quan= ?hieu-luc=con-hieu-luc|het-hieu-luc|can-ra-soat
 *          ?chu-de=tuyen-dung|on-thi|giao-duc|cong-chuc|vien-chuc ?tab=con-hieu-luc|moi|on-thi ?sap-xep=moi-nhat|so-hieu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = max( 1, absint( get_query_var( 'cvc_paged' ) ) );
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$get  = static fn ( string $k ): string => isset( $_GET[ $k ] ) && ! is_array( $_GET[ $k ] ) ? trim( sanitize_text_field( wp_unslash( (string) $_GET[ $k ] ) ) ) : '';
$list = static function ( string $k ): array {
	if ( ! isset( $_GET[ $k ] ) ) {
		return array();
	}
	$raw = is_array( $_GET[ $k ] ) ? array_map( 'strval', wp_unslash( $_GET[ $k ] ) ) : explode( ',', (string) wp_unslash( $_GET[ $k ] ) );

	return array_values( array_filter( array_map( static fn ( $v ) => trim( sanitize_text_field( $v ) ), $raw ) ) );
};
// phpcs:enable

$type_opts  = array( 'Luật', 'Nghị định', 'Nghị quyết', 'Thông tư', 'Quyết định', 'Hiến pháp' );
$topic_map  = array( 'tuyen-dung' => 'recruitment', 'on-thi' => 'exam', 'giao-duc' => 'education', 'cong-chuc' => 'civil', 'vien-chuc' => 'public' );
$topic_lbl  = array( 'tuyen-dung' => 'Tuyển dụng', 'on-thi' => 'Thi tuyển, ôn thi', 'giao-duc' => 'Giáo dục', 'cong-chuc' => 'Công chức', 'vien-chuc' => 'Viên chức' );
$state_map  = array( 'con-hieu-luc' => 'effective', 'het-hieu-luc' => 'expired', 'can-ra-soat' => 'review' );
$tab_map    = array( '' => 'all', 'con-hieu-luc' => 'effective', 'moi' => 'new', 'on-thi' => 'exam' );
$sort_map   = array( '' => 'relevant', 'moi-nhat' => 'newest', 'so-hieu' => 'number' );

$f = array(
	'q'        => mb_substr( $get( 'q' ), 0, 120 ),
	'loai'     => array_values( array_intersect( array_unique( array_merge( $list( 'loai' ), array_filter( array( $get( 'loai-chon' ) ) ) ) ), $type_opts ) ),
	'co-quan'  => mb_substr( $get( 'co-quan' ), 0, 160 ),
	'hieu-luc' => isset( $state_map[ $get( 'hieu-luc' ) ] ) ? $get( 'hieu-luc' ) : '',
	'chu-de'   => array_values( array_intersect( $list( 'chu-de' ), array_keys( $topic_map ) ) ),
	'tab'      => isset( $tab_map[ $get( 'tab' ) ] ) ? $get( 'tab' ) : '',
	'sap-xep'  => isset( $sort_map[ $get( 'sap-xep' ) ] ) ? $get( 'sap-xep' ) : '',
);
$args_of = static function ( array $f ): array {
	return array_filter(
		array(
			'q'        => $f['q'],
			'loai'     => implode( ',', $f['loai'] ),
			'co-quan'  => $f['co-quan'],
			'hieu-luc' => $f['hieu-luc'],
			'chu-de'   => implode( ',', $f['chu-de'] ),
			'tab'      => $f['tab'],
			'sap-xep'  => $f['sap-xep'],
		),
		static fn ( $v ) => '' !== $v
	);
};
$url_of = static fn ( array $f, int $page = 1 ): string => add_query_arg( array_map( 'rawurlencode', $args_of( $f ) ), cvc_legal_documents_url( $page ) );

$res  = cvc_ci_api(
	'/api/legal-documents/catalog',
	array_filter(
		array(
			'q'        => $f['q'],
			'types'    => implode( ',', $f['loai'] ),
			'agency'   => $f['co-quan'],
			'state'    => $state_map[ $f['hieu-luc'] ] ?? '',
			'topic'    => implode( ',', array_map( static fn ( $t ) => $topic_map[ $t ], $f['chu-de'] ) ),
			'tab'      => $tab_map[ $f['tab'] ],
			'sort'     => $sort_map[ $f['sap-xep'] ],
			'page'     => $paged,
			'per_page' => 12,
		),
		static fn ( $v ) => '' !== $v && null !== $v
	),
	null,
	12,
	300
);
$ok     = (bool) $res['ok'];
$data   = $ok ? (array) ( $res['data']['data'] ?? array() ) : array();
$docs   = (array) ( $data['items'] ?? array() );
$fc     = (array) ( $data['facets'] ?? array() );
$pg     = (array) ( $data['pagination'] ?? array() );
$total  = (int) ( $pg['total'] ?? 0 );
$last   = max( 1, (int) ( $pg['last_page'] ?? 1 ) );
$nf     = static fn ( $n ): string => number_format( (int) $n, 0, ',', '.' );
$filtered = '' !== $f['q'] || $f['loai'] || '' !== $f['co-quan'] || '' !== $f['hieu-luc'] || $f['chu-de'] || '' !== $f['tab'] || '' !== $f['sap-xep'];
$type_cnt = array();
foreach ( (array) ( $fc['types'] ?? array() ) as $t ) {
	$type_cnt[ (string) $t['label'] ] = (int) $t['count'];
}
$topic_cnt = array();
foreach ( (array) ( $fc['topics'] ?? array() ) as $t ) {
	$topic_cnt[ (string) $t['key'] ] = (int) $t['count'];
}

/* SEO */
$crumbs = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Văn bản pháp luật' ),
);
cvc_seo_set_title( 'Văn bản pháp luật về công chức, viên chức' . ( $paged > 1 ? ' – trang ' . $paged : '' ) );
cvc_seo_set_description( 'Tra cứu ' . ( ! empty( $fc['total'] ) ? $nf( $fc['total'] ) . ' ' : '' ) . 'văn bản pháp luật về công chức, viên chức: số hiệu, cơ quan ban hành, hiệu lực, nguồn chính thức và câu hỏi ôn thi theo từng văn bản.' );
if ( $filtered || ! $ok ) {
	cvc_seo_set_noindex();
	cvc_seo_set_canonical( cvc_legal_documents_url() );
} else {
	cvc_seo_set_canonical( cvc_legal_documents_url( $paged ) );
	cvc_seo_set_pagination_links( $paged > 1 ? cvc_legal_documents_url( $paged - 1 ) : null, $paged < $last ? cvc_legal_documents_url( $paged + 1 ) : null );
}
cvc_seo_add_breadcrumb_jsonld( $crumbs );
if ( ! $ok ) {
	nocache_headers();
}

/* Nhãn trạng thái hiệu lực của 1 văn bản */
$state_tag = static function ( array $d ): string {
	$st = (string) ( $d['state'] ?? '' );
	if ( 'replaced' === $st && ! empty( $d['replaced_by']['slug'] ) ) {
		return '<a class="lg-tag is-red" href="' . esc_url( cvc_legal_document_url( (string) $d['replaced_by']['slug'] ) ) . '">⊘ Bị thay thế bởi ' . esc_html( (string) $d['replaced_by']['document_number'] ) . '</a>';
	}
	if ( 'expired' === $st || 'replaced' === $st ) {
		return '<span class="lg-tag is-red">⊘ Hết hiệu lực' . ( ! empty( $d['expiry_date'] ) ? ' từ ' . esc_html( cvc_format_date_vn( (string) $d['expiry_date'] ) ) : '' ) . '</span>';
	}
	if ( 'future' === $st ) {
		return '<span class="lg-tag is-blue">◷ Hiệu lực từ ' . esc_html( cvc_format_date_vn( (string) $d['effective_date'] ) ) . '</span>';
	}

	return '<span class="lg-tag is-green">● Còn hiệu lực' . ( ! empty( $d['effective_date'] ) ? ' (từ ' . esc_html( cvc_format_date_vn( (string) $d['effective_date'] ) ) . ')' : '' ) . '</span>';
};

get_header();
?>
<main id="main" class="lg">
<div class="lg-wrap">
	<?php cvc_render_breadcrumbs( $crumbs ); ?>

	<section class="lg-hero">
		<div>
			<p class="lg-eyebrow">● TRA CỨU · ĐỐI CHIẾU NGUỒN · ỨNG DỤNG ÔN THI</p>
			<h1>Văn bản pháp luật về công chức, viên chức</h1>
			<p class="lg-lead">Tra cứu nhanh văn bản, kiểm tra hiệu lực và nguồn ban hành; kết nối điều khoản với tuyển dụng, tài liệu học tập và bài trắc nghiệm liên quan.</p>
			<p class="lg-actions">
				<a class="lg-btn is-primary" href="#tra-cuu">⌕ Tra cứu văn bản ↗</a>
				<a class="lg-btn" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">▣ Cơ hội tuyển dụng</a>
				<a class="lg-btn" href="<?php echo esc_url( cvc_exams_url() ); ?>">✓ Luyện trắc nghiệm</a>
			</p>
		</div>
		<aside class="lg-hero-stat">
			<small>Kho văn bản đã thu thập</small>
			<strong><?php echo isset( $fc['total'] ) ? esc_html( $nf( $fc['total'] ) ) . ' văn bản' : '—'; ?></strong>
			<span>Phân loại theo loại, hiệu lực và chủ đề<?php echo ! empty( $fc['last_updated'] ) ? ' · cập nhật ' . esc_html( wp_date( 'd/m/Y', strtotime( (string) $fc['last_updated'] ) ) ) : ''; ?></span>
		</aside>
	</section>

	<section class="lg-stats" aria-label="Số liệu kho văn bản">
		<div class="lg-stat"><span class="lg-ico" aria-hidden="true">§</span><div><strong><?php echo esc_html( $nf( $fc['total'] ?? 0 ) ); ?></strong><small>Văn bản trong kho</small></div></div>
		<div class="lg-stat"><span class="lg-ico" aria-hidden="true">✓</span><div><strong><?php echo esc_html( $nf( $fc['verified'] ?? 0 ) ); ?></strong><small>Đã đối chiếu nguồn chính thức</small></div></div>
		<a class="lg-stat" href="<?php echo esc_url( $url_of( array( 'q' => '', 'loai' => array(), 'co-quan' => '', 'hieu-luc' => 'can-ra-soat', 'chu-de' => array(), 'tab' => '', 'sap-xep' => '' ) ) ); ?>"><span class="lg-ico" aria-hidden="true">↻</span><div><strong><?php echo esc_html( $nf( $fc['review'] ?? 0 ) ); ?></strong><small>Cần rà soát nguồn<?php echo ! empty( $fc['expired'] ) ? ' · ' . (int) $fc['expired'] . ' hết hiệu lực' : ''; ?></small></div></a>
		<a class="lg-stat" href="<?php echo esc_url( $url_of( array( 'q' => '', 'loai' => array(), 'co-quan' => '', 'hieu-luc' => '', 'chu-de' => array(), 'tab' => 'on-thi', 'sap-xep' => '' ) ) ); ?>"><span class="lg-ico" aria-hidden="true">▤</span><div><strong><?php echo esc_html( $nf( $fc['questions'] ?? 0 ) ); ?></strong><small>Câu hỏi ôn tập theo văn bản</small></div></a>
	</section>

	<form class="lg-layout" id="tra-cuu" method="get" action="<?php echo esc_url( cvc_legal_documents_url() ); ?>" role="search">
		<?php if ( '' !== $f['tab'] ) : ?><input type="hidden" name="tab" value="<?php echo esc_attr( $f['tab'] ); ?>"><?php endif; ?>
		<aside class="lg-filters" id="lg-filters">
			<button type="button" class="lg-filterhead" aria-expanded="false" aria-controls="lg-filter-body"><span>▤ Bộ lọc tra cứu</span><span class="lg-caret" aria-hidden="true">⌄</span></button>
			<div class="lg-filter-body" id="lg-filter-body">
				<div class="lg-sec">
					<h3><label for="lg-q-side">Từ khóa / số hiệu</label></h3>
					<input id="lg-q-side" class="lg-field" type="search" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="VD: 15/2026/TT-BGDĐT" data-sync="lg-q">
				</div>
				<div class="lg-sec">
					<h3>Loại văn bản</h3>
					<div class="lg-checks">
						<?php foreach ( $type_opts as $t ) : ?>
							<?php if ( empty( $type_cnt[ $t ] ) && ! in_array( $t, $f['loai'], true ) ) { continue; } ?>
							<label><input type="checkbox" name="loai[]" value="<?php echo esc_attr( $t ); ?>"<?php checked( in_array( $t, $f['loai'], true ) ); ?> data-autosubmit> <?php echo esc_html( $t ); ?> <em><?php echo isset( $type_cnt[ $t ] ) ? (int) $type_cnt[ $t ] : 0; ?></em></label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="lg-sec">
					<h3>Hiệu lực</h3>
					<div class="lg-checks">
						<?php
						foreach ( array( '' => array( 'Tất cả trạng thái', $fc['total'] ?? 0 ), 'con-hieu-luc' => array( 'Còn hiệu lực', $fc['effective'] ?? 0 ), 'het-hieu-luc' => array( 'Hết hiệu lực / bị thay thế', $fc['expired'] ?? 0 ), 'can-ra-soat' => array( 'Cần rà soát nguồn', $fc['review'] ?? 0 ) ) as $k => $o ) :
							?>
							<label><input type="radio" name="hieu-luc" value="<?php echo esc_attr( $k ); ?>"<?php checked( $f['hieu-luc'], $k ); ?> data-autosubmit> <?php echo esc_html( $o[0] ); ?> <em><?php echo (int) $o[1]; ?></em></label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="lg-sec">
					<h3>Chủ đề liên quan</h3>
					<div class="lg-checks">
						<?php foreach ( $topic_lbl as $k => $lbl ) : ?>
							<label><input type="checkbox" name="chu-de[]" value="<?php echo esc_attr( $k ); ?>"<?php checked( in_array( $k, $f['chu-de'], true ) ); ?> data-autosubmit> <?php echo esc_html( $lbl ); ?> <em><?php echo (int) ( $topic_cnt[ $topic_map[ $k ] ] ?? 0 ); ?></em></label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="lg-filteractions">
					<button type="submit" class="lg-btn is-primary">Áp dụng</button>
					<a class="lg-btn" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Xóa lọc</a>
				</div>
			</div>
		</aside>

		<section class="lg-content">
			<div class="lg-tabrow">
				<nav class="lg-tabs" aria-label="Nhóm văn bản">
					<?php foreach ( array( '' => 'Tất cả văn bản', 'con-hieu-luc' => 'Còn hiệu lực', 'moi' => 'Mới ban hành', 'on-thi' => 'Hay dùng ôn thi' ) as $k => $lbl ) : ?>
						<a class="<?php echo $f['tab'] === $k ? 'is-cur' : ''; ?>" href="<?php echo esc_url( $url_of( array_merge( $f, array( 'tab' => $k ) ) ) ); ?>"<?php echo $f['tab'] === $k ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $lbl ); ?></a>
					<?php endforeach; ?>
				</nav>
				<label class="lg-sort"><span class="screen-reader-text">Sắp xếp</span>
					<select class="lg-field" name="sap-xep" id="lg-sort" data-autosubmit>
						<option value=""<?php selected( $f['sap-xep'], '' ); ?>>Sắp xếp: Liên quan nhất</option>
						<option value="moi-nhat"<?php selected( $f['sap-xep'], 'moi-nhat' ); ?>>Ngày ban hành mới nhất</option>
						<option value="so-hieu"<?php selected( $f['sap-xep'], 'so-hieu' ); ?>>Số hiệu A–Z</option>
					</select>
				</label>
			</div>

			<section class="lg-search">
				<div class="lg-searchhead"><div><b>Tìm kiếm thông minh</b><small>Tìm theo số hiệu, trích yếu, cơ quan ban hành hoặc chủ đề.</small></div><span class="lg-pill">Tra cứu đa tiêu chí</span></div>
				<div class="lg-searchgrid">
					<div><label class="lg-label" for="lg-q">Từ khóa</label><input id="lg-q" name="q" class="lg-field" type="search" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="VD: viên chức, tuyển dụng, Nghị định 259…" maxlength="120" data-sync="lg-q-side"></div>
					<div><label class="lg-label" for="lg-type">Loại văn bản</label>
						<select id="lg-type" name="loai-chon" class="lg-field">
							<option value="">Tất cả loại</option>
							<?php foreach ( $type_opts as $t ) : ?><?php if ( empty( $type_cnt[ $t ] ) ) { continue; } ?><option value="<?php echo esc_attr( $t ); ?>"<?php selected( 1 === count( $f['loai'] ) && $f['loai'][0] === $t ); ?>><?php echo esc_html( $t ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<div><label class="lg-label" for="lg-agency">Cơ quan ban hành</label>
						<select id="lg-agency" name="co-quan" class="lg-field">
							<option value="">Tất cả cơ quan</option>
							<?php foreach ( (array) ( $fc['agencies'] ?? array() ) as $a ) : ?><option value="<?php echo esc_attr( (string) $a['name'] ); ?>"<?php selected( mb_strtolower( $f['co-quan'] ), mb_strtolower( (string) $a['name'] ) ); ?>><?php echo esc_html( $a['name'] . ' (' . (int) $a['count'] . ')' ); ?></option><?php endforeach; ?>
						</select>
					</div>
					<div class="lg-submit"><button type="submit" class="lg-btn is-primary">⌕ Tìm kiếm</button></div>
				</div>
				<p class="lg-hint">Mỗi kết quả hiển thị trạng thái hiệu lực, nguồn chính thức và số câu hỏi ôn tập theo văn bản.</p>
			</section>

			<nav class="lg-topics" aria-label="Chủ đề nổi bật">
				<?php
				foreach ( array( 'tuyen-dung' => array( '▣', 'Phục vụ tuyển dụng' ), 'on-thi' => array( '✓', 'Phục vụ ôn thi' ), 'giao-duc' => array( '⌂', 'Giáo dục – đào tạo' ), 'cong-chuc' => array( '§', 'Công chức' ), 'vien-chuc' => array( '◎', 'Viên chức' ) ) as $k => $tp ) :
					$on = array( $k ) === $f['chu-de'];
					?>
					<a class="lg-topic<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo esc_url( $url_of( array_merge( $f, array( 'chu-de' => $on ? array() : array( $k ) ) ) ) ); ?>"<?php echo $on ? ' aria-current="true"' : ''; ?>><span aria-hidden="true"><?php echo esc_html( $tp[0] ); ?></span><?php echo esc_html( $tp[1] ); ?> <em><?php echo (int) ( $topic_cnt[ $topic_map[ $k ] ] ?? 0 ); ?></em></a>
				<?php endforeach; ?>
			</nav>

			<div class="lg-listhead">
				<div>
					<h2>Danh mục văn bản</h2>
					<p><?php echo $ok ? ( $filtered ? 'Tìm thấy <b>' . esc_html( $nf( $total ) ) . '</b> văn bản phù hợp điều kiện lọc' : esc_html( $nf( $total ) ) . ' văn bản' ) . ( $last > 1 ? ' · trang ' . (int) $paged . '/' . (int) $last : '' ) . '.' : 'Chưa tải được danh mục.'; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $filtered ) : ?> <a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Xóa bộ lọc</a><?php endif; ?></p>
				</div>
				<span class="lg-pill">Ưu tiên nguồn chính thức</span>
			</div>

			<?php if ( ! $ok ) : ?>
				<div class="lg-empty"><b>Chưa tải được danh sách văn bản.</b> Hệ thống đang bận — vui lòng <a href="<?php echo esc_url( $url_of( $f, $paged ) ); ?>">tải lại trang</a> sau ít phút.</div>
			<?php elseif ( ! $docs ) : ?>
				<div class="lg-empty"><b>Không tìm thấy văn bản phù hợp.</b> Hãy thử từ khóa khác hoặc <a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">xóa bớt bộ lọc</a>.</div>
			<?php else : ?>
				<div class="lg-docs">
					<?php foreach ( $docs as $d ) : ?>
						<?php
						if ( empty( $d['slug'] ) ) {
							continue;
						}
						$url     = cvc_legal_document_url( (string) $d['slug'] );
						$summary = trim( (string) ( $d['summary'] ?? '' ) );
						$title   = (string) ( $d['title'] ?? '' );
						$stale   = in_array( (string) ( $d['state'] ?? '' ), array( 'expired', 'replaced' ), true );
						?>
						<article class="lg-doc<?php echo $stale ? ' is-stale' : ( empty( $d['verified'] ) ? ' is-review' : '' ); ?>">
							<div class="lg-code"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) ( $d['document_number'] ?? '—' ) ); ?></a><small><?php echo esc_html( (string) ( $d['document_type'] ?? '' ) ); ?></small></div>
							<div class="lg-docbody">
								<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h3>
								<?php if ( '' !== $summary && false === mb_stripos( $title, $summary ) ) : ?><p><?php echo esc_html( mb_strlen( $summary ) > 240 ? mb_substr( $summary, 0, 238 ) . '…' : $summary ); ?></p><?php endif; ?>
								<p class="lg-meta">
									<?php echo ! empty( $d['verified'] ) ? '<span class="lg-tag is-green">✓ Đã đối chiếu nguồn</span>' : '<span class="lg-tag is-gold">◷ Cần rà soát nguồn</span>'; ?>
									<?php echo $state_tag( $d ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php if ( ! empty( $d['issuing_agency'] ) ) : ?><span class="lg-tag"><?php echo esc_html( (string) $d['issuing_agency'] ); ?></span><?php endif; ?>
									<?php if ( ! empty( $d['issued_date'] ) ) : ?><span class="lg-tag">Ban hành <?php echo esc_html( cvc_format_date_vn( (string) $d['issued_date'] ) ); ?></span><?php endif; ?>
									<?php if ( ! empty( $d['articles_count'] ) ) : ?><span class="lg-tag is-soft"><?php echo (int) $d['articles_count']; ?> điều</span><?php endif; ?>
									<?php if ( ! empty( $d['questions_count'] ) ) : ?><span class="lg-tag is-soft"><?php echo esc_html( $nf( $d['questions_count'] ) ); ?> câu hỏi ôn tập</span><?php endif; ?>
									<?php if ( ! empty( $d['recruitments_count'] ) ) : ?><span class="lg-tag is-soft">Căn cứ <?php echo (int) $d['recruitments_count']; ?> tin tuyển dụng</span><?php endif; ?>
								</p>
							</div>
							<div class="lg-docactions">
								<a class="lg-btn is-primary" href="<?php echo esc_url( $url ); ?>">Xem văn bản ↗</a>
								<?php if ( ! empty( $d['practice_exam']['slug'] ) ) : ?>
									<a class="lg-btn" href="<?php echo esc_url( cvc_exam_url( (string) $d['practice_exam']['slug'] ) ); ?>">Luyện <?php echo (int) $d['practice_exam']['total_questions']; ?> câu</a>
								<?php elseif ( ! empty( $d['questions_count'] ) ) : ?>
									<a class="lg-btn" href="<?php echo esc_url( $url . '#cvc-legal-study' ); ?>">Ôn theo văn bản</a>
								<?php endif; ?>
								<?php if ( ! empty( $d['source_url'] ) ) : ?>
									<a class="lg-source" href="<?php echo esc_url( (string) $d['source_url'] ); ?>" target="_blank" rel="noopener nofollow">↗ Nguồn chính thức</a>
								<?php else : ?>
									<span class="lg-source is-muted">Nguồn chờ kiểm tra</span>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<?php if ( $last > 1 ) : ?>
					<nav class="lg-pager" aria-label="Phân trang">
						<?php if ( $paged > 1 ) : ?><a class="lg-btn" href="<?php echo esc_url( $url_of( $f, $paged - 1 ) ); ?>" rel="prev">← Trước</a><?php endif; ?>
						<?php
						$from = max( 1, $paged - 2 );
						$to   = min( $last, $paged + 2 );
						if ( $from > 1 ) {
							echo '<a class="lg-btn" href="' . esc_url( $url_of( $f, 1 ) ) . '">1</a>' . ( $from > 2 ? '<span class="lg-gap">…</span>' : '' );
						}
						for ( $n = $from; $n <= $to; $n++ ) {
							echo '<a class="lg-btn' . ( $n === $paged ? ' is-cur" aria-current="page' : '' ) . '" href="' . esc_url( $url_of( $f, $n ) ) . '">' . (int) $n . '</a>';
						}
						if ( $to < $last ) {
							echo ( $to < $last - 1 ? '<span class="lg-gap">…</span>' : '' ) . '<a class="lg-btn" href="' . esc_url( $url_of( $f, $last ) ) . '">' . (int) $last . '</a>';
						}
						?>
						<?php if ( $paged < $last ) : ?><a class="lg-btn" href="<?php echo esc_url( $url_of( $f, $paged + 1 ) ); ?>" rel="next">Sau →</a><?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php endif; ?>

			<div class="lg-bottom">
				<section class="lg-connect">
					<h3>▣ Kết nối với tuyển dụng</h3>
					<p>Đi từ quy định pháp luật đến cơ hội tuyển dụng có liên quan<?php echo ! empty( $topic_cnt['recruitment'] ) ? ' — ' . (int) $topic_cnt['recruitment'] . ' văn bản đang là căn cứ hoặc nói về tuyển dụng' : ''; ?>.</p>
					<p class="lg-connectlinks"><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Cơ hội tuyển dụng</a><a href="<?php echo esc_url( add_query_arg( 'loai', 'civil', cvc_recruitments_url() ) ); ?>">Tuyển công chức</a><a href="<?php echo esc_url( add_query_arg( 'loai', 'public', cvc_recruitments_url() ) ); ?>">Tuyển viên chức</a><a href="<?php echo esc_url( $url_of( array( 'q' => '', 'loai' => array(), 'co-quan' => '', 'hieu-luc' => '', 'chu-de' => array( 'tuyen-dung' ), 'tab' => '', 'sap-xep' => '' ) ) ); ?>">Văn bản về tuyển dụng</a></p>
				</section>
				<section class="lg-connect">
					<h3>✓ Kết nối với ôn thi &amp; tài liệu</h3>
					<p>Biến văn bản thành lộ trình học: tài liệu tóm tắt và bộ câu hỏi trắc nghiệm theo điều khoản.</p>
					<p class="lg-connectlinks"><a href="<?php echo esc_url( cvc_documents_url() ); ?>">Tài liệu ôn thi</a><a href="<?php echo esc_url( cvc_exams_url() ); ?>">Làm trắc nghiệm</a><a href="<?php echo esc_url( cvc_topics_url() ); ?>">Chủ đề ôn thi</a></p>
				</section>
			</div>
			<p class="lg-note">Văn bản có giá trị pháp lý là bản do cơ quan có thẩm quyền ban hành. Trang dẫn về nguồn chính thức (Cổng Thông tin điện tử Chính phủ, Công báo…) và ghi rõ văn bản chưa đối chiếu được nguồn. Luôn kiểm tra văn bản sửa đổi, thay thế mới nhất trước khi áp dụng.</p>
		</section>
	</form>
</div>
</main>
<?php
get_footer();
