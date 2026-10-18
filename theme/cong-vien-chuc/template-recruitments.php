<?php
/**
 * TUYỂN DỤNG - Career Intelligence (18/10/2026), theo bộ thiết kế congvienchuc-career-intelligence-v2.
 * URL: /tuyen-dung/ và /tuyen-dung/page/{n}/ (giữ ?q= ?tinh= ?loai= ?phuong-thuc= cũ).
 *
 * Từ cơ hội tuyển dụng đến lộ trình chinh phục: tin tuyển dụng ↔ đề thi ↔ văn bản ↔ năng lực cá nhân.
 * Dữ liệu: Laravel api/career/* (+ api/me/career khi đăng nhập) - xem inc/career.php. Tab "Tuyển dụng"
 * render sẵn phía máy chủ (SEO, không cần JS); lọc / tab / phân trang tải lại bằng AJAX cùng hàm render.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged     = max( 1, absint( get_query_var( 'cvc_paged' ) ) );
$f         = cvc_ci_filters_from_request( $_GET );
$token     = cvc_auth_token();
$logged_in = null !== $token;
if ( $logged_in ) {
	nocache_headers();
}

$overview_res = cvc_ci_api( '/api/career/overview', array(), null, 10, 300 );
$ov           = $overview_res['ok'] ? (array) ( $overview_res['data']['data'] ?? array() ) : array();
$counts       = (array) ( $ov['counts'] ?? array() );
$provinces    = array_values( array_filter( (array) ( $ov['provinces'] ?? array() ), static fn ( $p ) => is_array( $p ) && (int) ( $p['total'] ?? 0 ) > 0 ) );
$families     = (array) ( $ov['families'] ?? array() );
$fam_counts   = array();
foreach ( $families as $fm ) {
	if ( is_array( $fm ) && isset( $fm['code'] ) ) {
		$fam_counts[ (string) $fm['code'] ] = (int) ( $fm['open_count'] ?? 0 );
	}
}

$jobs_res = cvc_ci_api( '/api/career/recruitments', cvc_ci_jobs_query( $f, $paged ), $token, 15, 120 );
$me       = null;
if ( $logged_in ) {
	$me_res = cvc_ci_api( '/api/me/career', array(), $token, 12 );
	$me     = $me_res['ok'] ? (array) ( $me_res['data']['data'] ?? array() ) : null;
}

// Đợt đang tổ chức thi / có kết quả (Phase 16) - chỉ trang 1, không lọc.
$rounds = array();
if ( 1 === $paged && '' === $f['q'] && empty( $f['loai'] ) && '' === $f['phuong-thuc'] && '' === $f['nhom'] && function_exists( 'cvc_recruitment_rounds_in_progress' ) ) {
	$rounds = cvc_recruitment_rounds_in_progress( 8, (int) $f['tinh'] );
}

$filter_count = ( $f['nhom'] ? 1 : 0 ) + ( $f['tinh'] ? 1 : 0 ) + count( $f['loai'] ) + ( $f['trang-thai'] ? 1 : 0 ) + ( $f['luu'] ? 1 : 0 ) + ( $f['phuong-thuc'] ? 1 : 0 );
$province_name = '';
foreach ( $provinces as $p ) {
	if ( (int) $p['id'] === (int) $f['tinh'] ) {
		$province_name = (string) $p['name'];
	}
}

$title = 'Tin tuyển dụng công chức, viên chức';
if ( '' !== $f['nhom'] ) {
	$title = 'Tuyển dụng ' . mb_strtolower( cvc_ci_families()[ $f['nhom'] ], 'UTF-8' );
}
if ( '' !== $province_name ) {
	$title .= ' tại ' . $province_name;
}
cvc_seo_set_title( $title );
cvc_seo_set_description( 'Tin tuyển dụng công chức, viên chức còn hạn từ nguồn chính thức, kèm đề thi, văn bản cần học và lộ trình ôn tập cho từng cơ hội.' );
cvc_seo_set_listing_pagination_state( $paged, static fn ( int $p ) => cvc_ci_url( cvc_ci_url_args( $f ), $p ) );
if ( $f['luu'] || '' !== $f['q'] ) {
	cvc_seo_set_noindex();
}

$fresh = (string) ( $ov['freshness']['last_recruitment_published_at'] ?? '' );

get_header();
?>
<main id="main" class="dz-main ci" data-tab="<?php echo esc_attr( $f['tab'] ); ?>">
	<section class="ci-hero">
		<div class="ci-wrap">
			<?php cvc_render_breadcrumbs( array( array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ), array( 'label' => 'Tuyển dụng' ) ) ); ?>
			<div class="ci-hero-row">
				<div>
					<div class="ci-eyebrow"><i class="ci-pulse" aria-hidden="true"></i> Tuyển dụng · Đề thi · Văn bản · Lộ trình</div>
					<h1><?php echo esc_html( '' !== $f['nhom'] || '' !== $province_name ? $title : 'Từ cơ hội tuyển dụng đến lộ trình chinh phục' ); ?></h1>
					<p>Tìm cơ hội còn hạn từ nguồn chính thức, xem đề thi và văn bản liên quan, hiểu vì sao nội dung được đề xuất — rồi biến mục tiêu dự tuyển thành kế hoạch học tập rõ ràng.</p>
					<div class="ci-hero-actions"><a class="ci-btn is-primary" href="#ci-search">Tìm cơ hội &amp; đề thi ↗</a><a class="ci-btn" href="<?php echo esc_url( cvc_positions_url() ); ?>">Tìm theo vị trí tuyển dụng</a><a class="ci-btn" href="<?php echo esc_url( add_query_arg( 'tab', 'path', cvc_ci_url() ) ); ?>" data-ci-tab="path">Xem lộ trình cá nhân</a></div>
				</div>
				<div class="ci-hero-summary">
					<small>ĐANG NHẬN HỒ SƠ</small>
					<strong><?php echo isset( $counts['open_recruitments'] ) ? esc_html( number_format_i18n( (int) $counts['open_recruitments'] ) ) . ' tin' : '—'; ?></strong>
					<span><?php echo isset( $counts['closing_soon'] ) ? esc_html( (int) $counts['closing_soon'] . ' tin hết hạn trong 7 ngày' ) : 'Đang cập nhật'; ?></span>
				</div>
			</div>
		</div>
	</section>

	<div class="ci-wrap ci-main">
		<section class="ci-kpis" aria-label="Số liệu">
			<div class="ci-kpi"><span class="ci-sym" aria-hidden="true">⌂</span><div><b><?php echo isset( $counts['open_recruitments'] ) ? esc_html( number_format_i18n( (int) $counts['open_recruitments'] ) ) : '—'; ?></b><small>Tin đang nhận hồ sơ</small></div></div>
			<div class="ci-kpi"><span class="ci-sym" aria-hidden="true">◷</span><div><b><?php echo isset( $counts['examining'] ) ? esc_html( number_format_i18n( (int) $counts['examining'] ) ) : '—'; ?></b><small>Đợt đang thi / có kết quả</small></div></div>
			<div class="ci-kpi"><span class="ci-sym" aria-hidden="true">▣</span><div><b><?php echo isset( $counts['published_exams'] ) ? esc_html( number_format_i18n( (int) $counts['published_exams'] ) ) : '—'; ?></b><small>Đề thi đang phát hành</small></div></div>
			<div class="ci-kpi"><span class="ci-sym" aria-hidden="true">§</span><div><b><?php echo isset( $counts['verified_legal_documents'] ) ? esc_html( number_format_i18n( (int) $counts['verified_legal_documents'] ) ) : '—'; ?></b><small>Văn bản đã đối chiếu nguồn</small></div></div>
		</section>

		<div class="ci-workspace">
			<div class="ci-hint">Không gian làm việc · chọn một nhóm nội dung để tập trung thao tác.</div>
			<div class="ci-actions">
				<button type="button" class="ci-btn" id="ci-toggle-filters" aria-expanded="<?php echo $filter_count > 0 ? 'true' : 'false'; ?>" aria-controls="ci-adv">☷ Bộ lọc nâng cao<?php echo $filter_count ? ' <span class="ci-count">' . (int) $filter_count . '</span>' : ''; ?></button>
				<a class="ci-btn" href="<?php echo esc_url( cvc_ci_url() ); ?>" data-ci="reset">↺ Xóa bộ lọc</a>
			</div>
		</div>

		<form id="ci-filters" class="ci-adv<?php echo $filter_count > 0 ? ' is-open' : ''; ?>" method="get" action="<?php echo esc_url( cvc_ci_url() ); ?>">
			<section class="ci-panel" id="ci-adv">
				<div class="ci-panel-head"><h2>Bộ lọc cơ hội</h2><span class="ci-pill is-gray" id="ci-filter-count"><?php echo (int) $filter_count; ?> lọc</span></div>
				<div class="ci-panel-body ci-filter-grid">
					<div>
						<label class="ci-label" for="ci-f-family">Nhóm vị trí</label>
						<select id="ci-f-family" name="nhom" class="ci-field">
							<option value="">Tất cả vị trí</option>
							<?php foreach ( cvc_ci_families() as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $f['nhom'], $k ); ?>><?php echo esc_html( $lbl ); ?><?php echo isset( $fam_counts[ $k ] ) ? ' (' . (int) $fam_counts[ $k ] . ' tin mở)' : ''; ?></option><?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="ci-label" for="ci-f-province">Địa phương</label>
						<select id="ci-f-province" name="tinh" class="ci-field">
							<option value="">Tất cả địa phương</option>
							<?php foreach ( $provinces as $p ) : ?><option value="<?php echo (int) $p['id']; ?>"<?php selected( (int) $f['tinh'], (int) $p['id'] ); ?>><?php echo esc_html( (string) $p['name'] ); ?> (<?php echo (int) $p['open_count']; ?> mở)</option><?php endforeach; ?>
						</select>
					</div>
					<div>
						<div class="ci-label">Loại cơ hội</div>
						<div class="ci-checks">
							<?php foreach ( cvc_ci_kinds() as $k => $lbl ) : ?><label class="ci-check"><input type="checkbox" name="loai[]" value="<?php echo esc_attr( $k ); ?>"<?php checked( in_array( $k, $f['loai'], true ) ); ?>> <?php echo esc_html( $lbl ); ?></label><?php endforeach; ?>
						</div>
					</div>
					<div>
						<label class="ci-label" for="ci-f-status">Trạng thái</label>
						<select id="ci-f-status" name="trang-thai" class="ci-field">
							<?php foreach ( cvc_ci_statuses() as $k => $lbl ) : ?><option value="<?php echo esc_attr( 'open' === $k ? '' : $k ); ?>"<?php selected( $f['trang-thai'] ?: 'open', $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
						</select>
						<label class="ci-check" style="margin-top:8px"><input type="checkbox" name="luu" value="1"<?php checked( (bool) $f['luu'] ); ?><?php disabled( ! $logged_in ); ?>> Chỉ xem tin đã lưu<?php echo $logged_in ? '' : ' (cần đăng nhập)'; ?></label>
					</div>
					<div>
						<label class="ci-label" for="ci-f-method">Phương thức tuyển</label>
						<select id="ci-f-method" name="phuong-thuc" class="ci-field">
							<option value="">Tất cả</option>
							<?php foreach ( cvc_recruitment_method_options() as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $f['phuong-thuc'], $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
						</select>
						<label class="ci-label" for="ci-f-sort" style="margin-top:8px">Sắp xếp</label>
						<select id="ci-f-sort" name="sap-xep" class="ci-field">
							<option value=""<?php selected( $f['sap-xep'], '' ); ?>>Phù hợp nhất<?php echo is_array( $me['goal'] ?? null ) ? ' (theo mục tiêu)' : ''; ?></option>
							<option value="deadline"<?php selected( $f['sap-xep'], 'deadline' ); ?>>Hạn gần nhất</option>
							<option value="newest"<?php selected( $f['sap-xep'], 'newest' ); ?>>Mới đăng</option>
						</select>
					</div>
				</div>
			</section>
			<?php cvc_ci_render_goal_form( $me, $logged_in, $provinces ); ?>
			<noscript><button type="submit" class="ci-btn is-primary">Áp dụng bộ lọc</button></noscript>
		</form>

		<nav class="ci-tabs" id="ci-tabs" role="tablist" aria-label="Nhóm nội dung">
			<?php foreach ( array( 'jobs' => 'Tuyển dụng', 'exams' => 'Đề thi', 'resources' => 'Văn bản & tài liệu', 'path' => 'Lộ trình cá nhân', 'matching' => 'Cách ghép nối' ) as $k => $lbl ) : ?>
				<a href="<?php echo esc_url( 'jobs' === $k ? cvc_ci_url( cvc_ci_url_args( $f ) ) : add_query_arg( 'tab', $k, cvc_ci_url( cvc_ci_url_args( $f ) ) ) ); ?>" role="tab" data-ci-tab="<?php echo esc_attr( $k ); ?>" aria-selected="<?php echo $k === $f['tab'] ? 'true' : 'false'; ?>" class="<?php echo $k === $f['tab'] ? 'is-active' : ''; ?>"><?php echo esc_html( $lbl ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="ci-layout">
			<div class="ci-center">
				<section id="ci-search" class="ci-composer">
					<div class="ci-composer-top"><div><h3>Tìm kiếm thông minh</h3><p>Một truy vấn — tìm tin tuyển dụng, đề thi và văn bản liên quan.</p></div><span class="ci-pill">4 chế độ</span></div>
					<div class="ci-modes" role="tablist" aria-label="Chế độ tìm kiếm">
						<button type="button" class="ci-mode is-active" data-mode="all">Tất cả</button>
						<button type="button" class="ci-mode" data-mode="jobs">Theo tin tuyển dụng</button>
						<button type="button" class="ci-mode" data-mode="exams">Theo đề thi / văn bản</button>
						<button type="button" class="ci-mode" data-mode="personal"<?php echo is_array( $me['goal'] ?? null ) ? '' : ' title="Đặt mục tiêu dự tuyển trong Bộ lọc nâng cao"'; ?>>Theo mục tiêu cá nhân</button>
					</div>
					<form class="ci-composer-fields" id="ci-search-form" method="get" action="<?php echo esc_url( cvc_ci_url() ); ?>" role="search">
						<div><label class="ci-label" for="ci-q">Từ khóa</label><input id="ci-q" name="q" class="ci-field" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="VD: giáo viên, kế toán, Nghị định 170…" maxlength="100"></div>
						<div><label class="ci-label" for="ci-q-family">Vị trí / ngành</label><select id="ci-q-family" name="nhom" class="ci-field" data-sync="ci-f-family"><option value="">Tất cả nhóm</option><?php foreach ( cvc_ci_families() as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"<?php selected( $f['nhom'], $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?></select></div>
						<div><label class="ci-label" for="ci-q-province">Địa phương</label><select id="ci-q-province" name="tinh" class="ci-field" data-sync="ci-f-province"><option value="">Tất cả địa phương</option><?php foreach ( $provinces as $p ) : ?><option value="<?php echo (int) $p['id']; ?>"<?php selected( (int) $f['tinh'], (int) $p['id'] ); ?>><?php echo esc_html( (string) $p['name'] ); ?></option><?php endforeach; ?></select></div>
						<button type="submit" class="ci-btn is-primary">Tìm kiếm ↗</button>
					</form>
					<div class="ci-hint" id="ci-search-hint">Mỗi tin hiển thị trạng thái nguồn, hạn nộp và mức xác nhận của đề/văn bản liên quan.</div>
				</section>

				<div class="ci-pane<?php echo 'jobs' === $f['tab'] ? ' is-active' : ''; ?>" id="ci-tab-jobs" role="tabpanel">
					<div class="ci-section-title"><div><h2>Tin tuyển dụng<?php echo '' !== $province_name ? ' · ' . esc_html( $province_name ) : ''; ?></h2><p>Ưu tiên tin còn hạn<?php echo is_array( $me['goal'] ?? null ) ? ' và khớp mục tiêu của bạn' : ''; ?>. Trạng thái và hạn nộp lấy từ thông báo gốc.</p></div><span class="ci-pill is-gold">Đối chiếu thông báo gốc</span></div>
					<div id="ci-jobs" class="ci-results" aria-live="polite"><?php cvc_ci_render_jobs( $jobs_res, $f, $logged_in ); ?></div>
					<div id="ci-related" class="ci-results" hidden></div>
					<?php if ( ! empty( $rounds ) && function_exists( 'cvc_render_round_list' ) ) : ?>
						<section id="dang-to-chuc-thi" class="ci-rounds">
							<div class="ci-section-title"><div><h2>Đợt đang tổ chức thi / có kết quả</h2><p>Dành cho thí sinh đã nộp hồ sơ: danh sách thí sinh, triệu tập, tài liệu ôn tập, lịch thi, kết quả theo văn bản chính thức.</p></div></div>
							<?php cvc_render_round_list( $rounds ); ?>
						</section>
					<?php endif; ?>
				</div>

				<div class="ci-pane<?php echo 'exams' === $f['tab'] ? ' is-active' : ''; ?>" id="ci-tab-exams" role="tabpanel">
					<div class="ci-results-head">
						<div><h3>Đề thi liên quan</h3><small>Theo nhóm vị trí, mục tiêu và tin đang chọn — mỗi đề ghi rõ mức xác nhận.</small></div>
						<div class="ci-actions">
							<select class="ci-field ci-sort" id="ci-e-type" aria-label="Loại đề"><option value="">Mọi loại đề</option><option value="mock">Thi thử</option><option value="practice">Luyện tập</option><option value="official">Bộ đề ôn thi</option></select>
							<?php if ( $logged_in ) : ?><select class="ci-field ci-sort" id="ci-e-done" aria-label="Đã làm"><option value="">Đã làm / chưa làm</option><option value="not_done">Chưa làm</option><option value="done">Đã làm</option></select><?php endif; ?>
							<select class="ci-field ci-sort" id="ci-e-sort" aria-label="Sắp xếp đề"><option value="relevance">Sắp xếp: Liên quan</option><option value="newest">Mới nhất</option><option value="title">Tên đề A–Z</option><option value="duration">Thời lượng ngắn trước</option></select>
						</div>
					</div>
					<div id="ci-exams" class="ci-results" data-loaded="<?php echo 'exams' === $f['tab'] ? '1' : '0'; ?>" aria-live="polite">
						<?php
						if ( 'exams' === $f['tab'] ) {
							cvc_ci_render_exams( cvc_ci_api( '/api/career/exams', cvc_ci_exams_query( $f, $paged ), $token, 15, 120 ), $f, $logged_in );
						} else {
							echo '<div class="ci-loading">Đang tải đề thi…</div>';
						}
						?>
					</div>
				</div>

				<div class="ci-pane<?php echo 'resources' === $f['tab'] ? ' is-active' : ''; ?>" id="ci-tab-resources" role="tabpanel">
					<div class="ci-section-title"><div><h2>Văn bản &amp; tài liệu cần học</h2><p>Văn bản pháp luật gốc: số hiệu, ngày ban hành, tình trạng hiệu lực theo dữ liệu đã đối chiếu; phân biệt văn bản trong danh mục ôn tập chính thức và văn bản tham khảo.</p></div></div>
					<div id="ci-resources" class="ci-results" data-loaded="<?php echo 'resources' === $f['tab'] ? '1' : '0'; ?>" aria-live="polite">
						<?php
						if ( 'resources' === $f['tab'] ) {
							cvc_ci_render_resources( cvc_ci_api( '/api/career/resources', cvc_ci_resources_query( $f, $paged ), null, 15, 300 ), $f );
						} else {
							echo '<div class="ci-loading">Đang tải văn bản…</div>';
						}
						?>
					</div>
				</div>

				<div class="ci-pane<?php echo 'path' === $f['tab'] ? ' is-active' : ''; ?>" id="ci-tab-path" role="tabpanel">
					<div class="ci-section-title"><div><h2>Lộ trình chinh phục kỳ thi</h2><p>Cơ hội → học văn bản nguồn → luyện đề → đo tiến bộ. Mỗi bước có lý do, mức ưu tiên và hạn gợi ý.</p></div></div>
					<div class="ci-steps3">
						<div><span>1</span><b>Đối chiếu</b><small>Điều kiện &amp; hạn nộp của tin mục tiêu</small></div>
						<div><span>2</span><b>Ôn tập</b><small>Chủ đề yếu, văn bản nguồn, đề liên quan</small></div>
						<div><span>3</span><b>Đo tiến bộ</b><small>Bài kiểm tra lại sau ôn tập</small></div>
					</div>
					<div id="ci-path"><?php cvc_ci_render_path( $me, $logged_in ); ?></div>
				</div>

				<div class="ci-pane<?php echo 'matching' === $f['tab'] ? ' is-active' : ''; ?>" id="ci-tab-matching" role="tabpanel">
					<div class="ci-section-title"><div><h2>Vì sao hệ thống ghép nối?</h2><p>Mỗi liên kết ghi rõ căn cứ và mức xác nhận — không dùng “% phù hợp”.</p></div></div>
					<section class="ci-panel"><div class="ci-panel-body">
						<div class="ci-timeline">
							<div class="ci-tl"><span>✓</span><div><b><?php echo cvc_ci_tier_pill( 'official' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Chính thức</b><small>Có căn cứ trong văn bản của cơ quan tuyển dụng: đề dựng từ danh mục tài liệu ôn tập do Hội đồng công bố; văn bản nằm trong danh mục đó; văn bản được thông báo trích dẫn làm căn cứ. Hiện có <?php echo isset( $counts['official_links'] ) ? (int) $counts['official_links'] : 0; ?> liên kết đề ↔ đợt loại này.</small></div></div>
							<div class="ci-tl"><span>✓</span><div><b><?php echo cvc_ci_tier_pill( 'curated' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Đã biên tập</b><small>Quản trị viên đã gắn đề với đợt tuyển dụng.</small></div></div>
							<div class="ci-tl"><span>✓</span><div><b><?php echo cvc_ci_tier_pill( 'suggested' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Tham khảo</b><small>Gợi ý theo nhóm vị trí (suy ra từ tiêu đề tin, cơ quan, vị trí) và kiến thức chung. Không phải nội dung thi chính thức của vị trí — luôn đối chiếu thông báo gốc.</small></div></div>
							<div class="ci-tl"><span>◎</span><div><b>Khớp nhu cầu học cá nhân</b><small>Lộ trình ưu tiên chủ đề có tỷ lệ đúng dưới 70%, câu sai lặp lại và thời gian còn lại đến ngày thi / hạn nộp.</small></div></div>
							<div class="ci-tl"><span>◷</span><div><b>Trạng thái &amp; nguồn dữ liệu</b><small>Tin hiển thị nguồn (chính thức / sưu tầm), hạn nộp và trạng thái còn hạn / hết hạn; nhắc hạn chỉ áp dụng với hạn nộp từ nguồn chính thức. Đề thi liên quan không đồng nghĩa bạn đủ điều kiện dự tuyển.</small></div></div>
						</div>
						<div class="ci-insight"><b>Nguyên tắc tin cậy</b><p>AI chỉ giải thích và đề xuất; hạn tuyển, điều kiện, môn thi và điểm số lấy từ dữ liệu chính thức. Thiếu nguồn thì ghi “chưa xác minh”, không suy đoán.</p></div>
					</div></section>
				</div>
			</div>

			<aside class="ci-right">
				<div id="ci-personal-wrap"><?php cvc_ci_render_personal( $me, $logged_in ); ?></div>
				<section class="ci-promo">
					<div class="ci-eyebrow"><i class="ci-pulse is-gold" aria-hidden="true"></i> Hồ sơ chinh phục</div>
					<h3>Đừng chỉ tìm tin. Hãy chuẩn bị cho cơ hội.</h3>
					<p>Chọn một tin để xem đề thi, văn bản cần học và đặt làm mục tiêu cho lộ trình của bạn.</p>
					<a class="ci-btn" href="#ci-tab-jobs" data-ci-tab="jobs">Chọn cơ hội ngay ↓</a>
				</section>
				<div id="ci-reminders-wrap"><?php cvc_ci_render_reminders( $me, $logged_in ); ?></div>
				<section class="ci-panel">
					<div class="ci-panel-head"><h2>Trạng thái dữ liệu</h2></div>
					<div class="ci-panel-body">
						<div class="ci-checks ci-status">
							<span class="ci-check">● Tin tuyển dụng: thu thập từ cổng thông tin cơ quan nhà nước<?php echo '' !== $fresh ? ', tin mới nhất ' . esc_html( cvc_ci_date( $fresh ) ) : ''; ?></span>
							<span class="ci-check">● Đề thi: ngân hàng câu hỏi đã duyệt (<?php echo isset( $counts['published_exams'] ) ? (int) $counts['published_exams'] : '—'; ?> đề)</span>
							<span class="ci-check">● Văn bản: <?php echo isset( $counts['verified_legal_documents'] ) ? (int) $counts['verified_legal_documents'] : '—'; ?> văn bản đã đối chiếu nguồn chính thức</span>
							<span class="ci-check">● Ghép nối: chính thức / biên tập / tham khảo — xem tab “Cách ghép nối”</span>
						</div>
					</div>
				</section>
			</aside>
		</div>
	</div>
	<div class="ci-toast" id="ci-toast" role="status" aria-live="polite"></div>
</main>
<?php get_footer(); ?>
