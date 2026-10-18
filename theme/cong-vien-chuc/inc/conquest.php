<?php
/**
 * Hồ sơ chinh phục kỳ thi (18/10/2026) - /tai-khoan/ho-so-chinh-phuc/ (khu vực tài khoản, cần đăng nhập).
 *
 *  - Dữ liệu: /api/me/conquest (Laravel, chỉ dữ liệu của người đăng nhập). Mỗi hồ sơ = 1 mục tiêu + lộ trình riêng.
 *  - Trang render phía máy chủ; mọi thao tác qua AJAX `cvc_cq` (nonce + đăng nhập) rồi render lại vùng nội dung.
 *  - Lối vào: menu tài khoản, nút "Chinh phục kỳ thi này" (trang tin), "Gắn mục tiêu" (danh sách đề),
 *    "Cập nhật kế hoạch ôn tập" (trang kết quả thi) - các form POST tới admin-post `cvc_cq_go`.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_conquest_url( int $goal_id = 0, string $anchor = '' ): string {
	$u = cvc_account_url( 'conquest' );
	if ( $goal_id > 0 ) {
		$u = add_query_arg( 'ho-so', $goal_id, $u );
	}

	return $u . ( '' !== $anchor ? '#' . $anchor : '' );
}

function cvc_cq_get( int $goal_id = 0 ): array {
	$token = cvc_auth_token();
	if ( null === $token ) {
		return array( 'ok' => false, 'status' => 401, 'data' => array() );
	}
	$res = ( new CVC_Api_Client( null, 25 ) )->get( '/api/me/conquest', $goal_id > 0 ? array( 'goal_id' => $goal_id ) : array(), $token );
	if ( ! $res['ok'] && ! in_array( (int) ( $res['status'] ?? 0 ), array( 401, 403, 404, 422 ), true ) ) {
		$res = ( new CVC_Api_Client( null, 35 ) )->get( '/api/me/conquest', $goal_id > 0 ? array( 'goal_id' => $goal_id ) : array(), $token );
	}

	return $res;
}

/* ------------------------------------------------------------------
 * Tiện ích hiển thị
 * ------------------------------------------------------------------ */
function cvc_cq_date( ?string $iso ): string {
	$t = $iso ? strtotime( substr( (string) $iso, 0, 10 ) ) : false;

	return $t ? gmdate( 'd/m/Y', $t ) : '';
}

function cvc_cq_ago( ?string $iso ): string {
	$t = $iso ? strtotime( (string) $iso ) : false;
	if ( ! $t ) {
		return '';
	}
	$d = time() - $t;
	if ( $d < 3600 ) {
		return max( 1, (int) floor( $d / 60 ) ) . ' phút trước';
	}
	if ( $d < 86400 ) {
		return (int) floor( $d / 3600 ) . ' giờ trước';
	}
	if ( $d < 86400 * 2 ) {
		return 'Hôm qua';
	}
	if ( $d < 86400 * 30 ) {
		return (int) floor( $d / 86400 ) . ' ngày trước';
	}

	return wp_date( 'd/m/Y', $t );
}

function cvc_cq_doc_abbr( string $number ): string {
	$n = mb_strtoupper( $number );
	foreach ( array( 'NĐ' => 'NĐ', 'TT' => 'TT', 'QH' => 'Luật', 'QĐ' => 'QĐ', 'NQ' => 'NQ' ) as $k => $v ) {
		if ( false !== mb_strpos( $n, $k ) ) {
			return $v;
		}
	}

	return 'VB';
}

function cvc_cq_task_link( array $t ): string {
	$l = (array) ( $t['link'] ?? array() );
	switch ( (string) ( $l['kind'] ?? '' ) ) {
		case 'exam':
			return cvc_exam_url( (string) $l['slug'] );
		case 'legal_document':
			return cvc_legal_document_url( (string) $l['slug'] );
		case 'topic':
			return cvc_topic_url( (string) $l['slug'] );
		case 'recruitment':
			return function_exists( 'cvc_recruitment_url' ) ? cvc_recruitment_url( (string) $l['slug'] ) : home_url( '/tuyen-dung/' . rawurlencode( (string) $l['slug'] ) . '/' );
	}

	return '';
}

function cvc_cq_rec_url( string $slug ): string {
	return function_exists( 'cvc_recruitment_url' ) ? cvc_recruitment_url( $slug ) : home_url( '/tuyen-dung/' . rawurlencode( $slug ) . '/' );
}

function cvc_cq_pct( $v ): string {
	return null === $v ? '—' : (int) $v . '%';
}

/* ------------------------------------------------------------------
 * Vùng nội dung (render lại bằng AJAX)
 * ------------------------------------------------------------------ */
function cvc_cq_render_body( array $res ): void {
	if ( ! $res['ok'] ) {
		$msg = 401 === (int) ( $res['status'] ?? 0 ) ? 'Phiên đăng nhập đã hết hạn — vui lòng đăng nhập lại.' : 'Không tải được hồ sơ, vui lòng thử lại sau ít phút.';
		echo '<div class="cq-card cq-state"><b>' . esc_html( $msg ) . '</b><a class="cq-btn" href="' . esc_url( 401 === (int) ( $res['status'] ?? 0 ) ? cvc_login_url( cvc_conquest_url() ) : cvc_conquest_url() ) . '">' . ( 401 === (int) ( $res['status'] ?? 0 ) ? 'Đăng nhập' : 'Tải lại' ) . '</a></div>';

		return;
	}
	$d = (array) ( $res['data']['data'] ?? array() );
	if ( empty( $d['goal'] ) ) {
		cvc_cq_render_empty( $d );

		return;
	}
	$g     = (array) $d['goal'];
	$opp   = is_array( $d['opportunity'] ?? null ) ? $d['opportunity'] : null;
	$st    = $opp ? (array) $opp['status'] : array();
	$k     = (array) $d['kpis'];
	$rd    = (array) $d['readiness'];
	$tasks = (array) $d['tasks'];
	$pend  = array_values( array_filter( $tasks, static fn ( $t ) => ! in_array( $t['status'], array( 'completed', 'skipped' ), true ) ) );
	$doneT = array_values( array_filter( $tasks, static fn ( $t ) => 'completed' === $t['status'] ) );
	$sp    = static fn ( $n ) => str_pad( (string) (int) $n, 2, '0', STR_PAD_LEFT );
	?>
	<div class="cq-data" data-goal="<?php echo (int) $g['id']; ?>" hidden></div>
	<?php if ( ! $opp ) : ?>
		<div class="cq-notice"><span aria-hidden="true">ⓘ</span><div><b>Hồ sơ theo nhóm vị trí.</b> Hồ sơ này chưa gắn với một thông báo tuyển dụng cụ thể nên chưa có hạn nộp và danh mục ôn tập chính thức. Tài liệu, đề bên dưới là gợi ý tham khảo theo nhóm <?php echo esc_html( (string) ( $g['job_family_label'] ?? '' ) ); ?>.</div></div>
	<?php elseif ( empty( $st['source_official'] ) ) : ?>
		<div class="cq-notice"><span aria-hidden="true">ⓘ</span><div><b>Tin sưu tầm.</b> Thông báo này chưa có nguồn chính thức của cơ quan tuyển dụng — hạn nộp và điều kiện cần đối chiếu với thông báo gốc trước khi chuẩn bị hồ sơ.</div></div>
	<?php endif; ?>

	<section class="cq-kpis cq-enter" aria-label="Tổng quan">
		<div class="cq-kpi"><span class="cq-ki" aria-hidden="true">▣</span><div><strong><?php echo esc_html( $sp( $k['profiles'] ) ); ?></strong><small>Hồ sơ đang theo đuổi</small></div></div>
		<div class="cq-kpi"><span class="cq-ki" aria-hidden="true">▤</span><div><strong><?php echo esc_html( $sp( $k['documents'] ) ); ?></strong><small>Tài liệu trong hồ sơ</small></div></div>
		<div class="cq-kpi"><span class="cq-ki" aria-hidden="true">✓</span><div><strong><?php echo esc_html( $sp( $k['attempts'] ) ); ?></strong><small>Lượt luyện thi đã nộp</small></div></div>
		<div class="cq-kpi"><span class="cq-ki" aria-hidden="true">◷</span><div><strong><?php echo esc_html( $sp( $k['pending'] ) ); ?></strong><small>Việc cần hoàn thành</small></div></div>
	</section>

	<div class="cq-grid">
		<div class="cq-left">
			<?php /* ---------- Cơ hội mục tiêu ---------- */ ?>
			<section class="cq-card cq-opp cq-enter" id="co-hoi">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">◎</span><div><h2>Cơ hội mục tiêu</h2><p><?php echo $opp ? 'Thông báo tuyển dụng bạn đang chinh phục' : 'Mục tiêu theo nhóm vị trí'; ?></p></div></div><span class="cq-pill <?php echo ! empty( $g['is_primary'] ) ? 'is-gold' : ''; ?>"><?php echo ! empty( $g['is_primary'] ) ? 'Hồ sơ chính' : 'Hồ sơ phụ'; ?></span></div>
				<div class="cq-body">
					<div class="cq-opp-top">
						<div>
							<span class="cq-pill is-teal"><?php echo $opp ? esc_html( (string) ( $opp['type_label'] ?: 'Tuyển dụng' ) ) . ' · ' . esc_html( (string) ( $opp['job_family_label'] ?: $g['job_family_label'] ?: 'Nhóm khác' ) ) : 'NHÓM VỊ TRÍ · ' . esc_html( mb_strtoupper( (string) ( $g['job_family_label'] ?? '' ) ) ); ?></span>
							<h3 class="cq-opp-title"><?php echo esc_html( $opp ? (string) $opp['title'] : (string) $g['title'] ); ?></h3>
							<div class="cq-agency"><?php echo esc_html( $opp ? ( (string) ( $opp['agency']['name'] ?? 'Cơ quan tuyển dụng' ) . ( ! empty( $opp['doc_number'] ) ? ' · Số ' . $opp['doc_number'] : '' ) ) : 'Chưa gắn thông báo tuyển dụng cụ thể' ); ?></div>
						</div>
						<button type="button" class="cq-btn is-sm" data-cq-open="switch">Đổi hồ sơ ▾</button>
					</div>
					<div class="cq-meta">
						<span>⌖ <b><?php echo esc_html( (string) ( $opp['province']['name'] ?? $g['province'] ?? 'Chưa chọn địa phương' ) ); ?></b></span>
						<span>▣ Nhóm: <b><?php echo esc_html( (string) ( $g['job_family_label'] ?: 'Chưa chọn' ) ); ?></b></span>
						<?php if ( $opp ) : ?>
							<span>◷ Hạn nộp: <b><?php echo ! empty( $st['deadline_verified'] ) ? esc_html( cvc_cq_date( $st['deadline'] ) ) : ( ! empty( $st['deadline'] ) ? esc_html( cvc_cq_date( $st['deadline'] ) ) . ' (chưa xác minh)' : 'Chưa công bố' ); ?></b></span>
						<?php endif; ?>
						<?php if ( ! empty( $opp['total_positions'] ) ) : ?><span>☰ Chỉ tiêu: <b><?php echo (int) $opp['total_positions']; ?></b></span><?php endif; ?>
					</div>
					<?php if ( $opp ) : ?>
						<div class="cq-meta">
							<span>↗ Nguồn: <b><?php echo esc_html( (string) $st['source_label'] ); ?></b><?php if ( ! empty( $opp['source_url'] ) ) : ?> · <a href="<?php echo esc_url( (string) $opp['source_url'] ); ?>" target="_blank" rel="noopener nofollow">Thông báo gốc ↗</a><?php endif; ?></span>
							<span>◉ Trạng thái: <b><?php echo esc_html( (string) $st['label'] ); ?></b></span>
						</div>
					<?php endif; ?>
					<div class="cq-btnrow">
						<a class="cq-btn is-primary" href="#viec-can-lam" data-cq-scroll>Tiếp tục chuẩn bị →</a>
						<?php if ( $opp ) : ?>
							<a class="cq-btn" href="<?php echo esc_url( cvc_cq_rec_url( (string) $opp['slug'] ) ); ?>">Chi tiết cơ hội</a>
							<button type="button" class="cq-btn is-gold" data-cq="save" data-id="<?php echo (int) $opp['id']; ?>" data-on="<?php echo ! empty( $opp['saved'] ) ? '1' : '0'; ?>"><?php echo ! empty( $opp['saved'] ) ? '★ Đã lưu cơ hội' : '☆ Lưu cơ hội'; ?></button>
						<?php else : ?>
							<a class="cq-btn" href="<?php echo esc_url( add_query_arg( 'nhom', (string) ( $g['job_family'] ?? '' ), cvc_recruitments_url() ) ); ?>">Tìm tin cùng nhóm vị trí</a>
							<button type="button" class="cq-btn is-gold" data-cq-open="add">＋ Gắn tin tuyển dụng</button>
						<?php endif; ?>
					</div>
					<div class="cq-status">● <b>Kiểm tra cần thiết:</b> <?php echo $opp ? 'đối chiếu cơ quan, chỉ tiêu, điều kiện, hạn nộp và nội dung thi với thông báo gốc trước khi nộp hồ sơ.' : 'chọn một thông báo tuyển dụng cụ thể để có hạn nộp, danh mục ôn tập và đề chính thức của đợt.'; ?></div>
				</div>
			</section>

			<?php /* ---------- Mức độ sẵn sàng ---------- */ $rv = $rd['value']; ?>
			<section class="cq-card" id="san-sang">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">◉</span><div><h2>Mức độ sẵn sàng dự thi</h2><p>Chỉ số tiến độ học tập — không phải xác suất trúng tuyển</p></div></div><span class="cq-pill is-teal"><?php echo null === $rv ? 'Chưa đủ dữ liệu' : 'Tính từ dữ liệu của bạn'; ?></span></div>
				<div class="cq-body">
					<div class="cq-ringrow">
						<div class="cq-ring" style="--v:<?php echo (int) ( $rv ?? 0 ); ?>"><div class="cq-ringin"><strong><?php echo esc_html( cvc_cq_pct( $rv ) ); ?></strong><small><?php echo null === $rv ? 'CHƯA ĐỦ DỮ LIỆU' : 'SẴN SÀNG'; ?></small></div></div>
						<div>
							<?php if ( null === $rv ) : ?>
								<h3>Chưa đủ dữ liệu để ước tính</h3>
								<p>Làm một đề trong hồ sơ, học một tài liệu hoặc đánh dấu xong một việc — chỉ số sẽ được tính từ chính kết quả đó.</p>
							<?php else : ?>
								<?php
								$cq_pr = (int) $rd['priorities'];
								$cq_h  = $rv >= 75 ? 'Gần sẵn sàng — giữ nhịp ôn tập' : ( $rv >= 40 ? 'Bạn đã có nền tảng' . ( $cq_pr ? ', còn ' . $cq_pr . ' ưu tiên' : '' ) : 'Mới bắt đầu — làm lần lượt ' . ( $cq_pr ? $cq_pr . ' việc ưu tiên' : 'các việc trong lộ trình' ) );
								?>
								<h3><?php echo esc_html( $cq_h ); ?></h3>
								<p>Tính từ: <?php echo esc_html( implode( ' · ', array_map( static fn ( $c ) => $c['label'] . ' ' . (int) $c['value'] . '%', (array) $rd['components'] ) ) ); ?>. Thành phần chưa có dữ liệu không được tính.</p>
							<?php endif; ?>
							<div class="cq-bar"><span style="width:<?php echo (int) ( $rv ?? 0 ); ?>%"></span></div>
							<div class="cq-stats">
								<div><b><?php echo (int) $rd['documents_done']; ?>/<?php echo (int) $rd['documents_total']; ?></b><small>Tài liệu hoàn tất</small></div>
								<div><b><?php echo (int) $rd['exams_done']; ?>/<?php echo (int) $rd['exams_total']; ?></b><small>Đề đã luyện</small></div>
								<div><b><?php echo (int) $rd['weak_count']; ?></b><small>Chủ đề cần ôn</small></div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<?php /* ---------- Lộ trình ---------- */ ?>
			<section class="cq-card" id="lo-trinh">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">↗</span><div><h2>Lộ trình chinh phục</h2><p>Nhìn tổng quát quá trình và biết bước tiếp theo<?php echo ! empty( $d['path']['target_date'] ) ? esc_html( ' · đích ' . cvc_cq_date( $d['path']['target_date'] ) ) : ''; ?></p></div></div><div class="cq-headr"><button type="button" class="cq-btn is-sm" data-cq="plan">↻ Cập nhật kế hoạch</button><button type="button" class="cq-btn is-sm" data-cq-open="custom">Tùy chỉnh</button></div></div>
				<div class="cq-body">
					<div class="cq-steps">
						<?php
						$active_set = false;
						foreach ( (array) $d['stages'] as $i => $s ) :
							$pc     = $s['percent'];
							$active = ! $active_set && ( null === $pc || $pc < 100 );
							if ( $active ) {
								$active_set = true;
							}
							?>
							<div class="cq-step<?php echo $active ? ' is-active' : ''; ?><?php echo null !== $pc && $pc >= 100 ? ' is-done' : ''; ?>">
								<span class="cq-num"><?php echo esc_html( $sp( $i + 1 ) ); ?></span>
								<b><?php echo esc_html( (string) $s['title'] ); ?></b>
								<small><?php echo esc_html( (string) $s['hint'] ); ?></small>
								<div class="cq-mini" title="<?php echo esc_attr( cvc_cq_pct( $pc ) ); ?>"><span style="width:<?php echo (int) ( $pc ?? 0 ); ?>%"></span></div>
								<em><?php echo null === $pc ? 'Chưa có dữ liệu' : (int) $pc . '%'; ?></em>
								<?php if ( ! empty( $s['detail'] ) ) : ?>
									<ul class="cq-checks"><?php foreach ( (array) $s['detail'] as $x ) : ?><li class="<?php echo ! empty( $x['done'] ) ? 'is-ok' : ''; ?>"><?php echo ! empty( $x['done'] ) ? '✓' : '○'; ?> <?php echo esc_html( (string) $x['label'] ); ?></li><?php endforeach; ?></ul>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<?php /* ---------- Việc nên làm ---------- */ ?>
			<section class="cq-card" id="viec-can-lam">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">☑</span><div><h2>Việc nên làm tiếp theo</h2><p>Từ lộ trình của hồ sơ này — đánh dấu để cập nhật tiến độ</p></div></div><span class="cq-pill is-gold"><?php echo count( $pend ); ?> việc còn lại</span></div>
				<div class="cq-body">
					<?php if ( ! $tasks ) : ?>
						<div class="cq-empty-sm">Hồ sơ chưa có lộ trình. <button type="button" class="cq-btn is-teal is-sm" data-cq="plan">Tạo lộ trình ôn tập</button></div>
					<?php else : ?>
						<div class="cq-tasks">
							<?php foreach ( array_slice( $pend, 0, 6 ) as $t ) : cvc_cq_task( $t ); endforeach; ?>
						</div>
						<?php if ( count( $pend ) > 6 || $doneT ) : ?>
							<details class="cq-more"><summary>Xem thêm <?php echo count( $pend ) > 6 ? ( count( $pend ) - 6 ) . ' việc còn lại' : ''; ?><?php echo count( $pend ) > 6 && $doneT ? ' và ' : ''; ?><?php echo $doneT ? count( $doneT ) . ' việc đã xong' : ''; ?></summary><div class="cq-tasks"><?php foreach ( array_merge( array_slice( $pend, 6 ), $doneT ) as $t ) : cvc_cq_task( $t ); endforeach; ?></div></details>
						<?php endif; ?>
						<?php $first = $pend[0] ?? null; $flink = $first ? cvc_cq_task_link( $first ) : ''; ?>
						<div class="cq-taskfoot"><span><?php echo count( $doneT ); ?>/<?php echo count( $tasks ); ?> việc hoàn thành</span><?php if ( $first ) : ?><a class="cq-btn is-teal is-sm" href="<?php echo esc_url( '' !== $flink ? $flink : '#viec-can-lam' ); ?>"<?php echo 'practice' === $first['type'] ? ' data-cq="practice" data-kind="' . esc_attr( (string) ( $first['link']['practice_kind'] ?? 'weak' ) ) . '"' : ''; ?>>Bắt đầu việc ưu tiên →</a><?php endif; ?></div>
					<?php endif; ?>
				</div>
			</section>

			<?php /* ---------- Tài liệu ---------- */ ?>
			<section class="cq-card" id="tai-lieu">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">▤</span><div><h2>Tài liệu cần học</h2><p>Gắn với mục tiêu · có nguồn, hiệu lực và tiến độ</p></div></div><a class="cq-btn is-sm" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Xem tất cả</a></div>
				<div class="cq-body">
					<?php if ( empty( $d['materials'] ) ) : ?>
						<div class="cq-empty-sm">Chưa có tài liệu gợi ý cho hồ sơ này.</div>
					<?php else : ?>
						<div class="cq-rows">
							<?php foreach ( (array) $d['materials'] as $m ) :
								$stl = array( 'done' => array( 'is-teal', 'Hoàn tất' ), 'learning' => array( 'is-gold', 'Đang học' ), 'todo' => array( 'is-red', 'Chưa học' ) )[ $m['state'] ];
								?>
								<div class="cq-row">
									<span class="cq-doc is-law"><?php echo esc_html( cvc_cq_doc_abbr( (string) $m['number'] ) ); ?></span>
									<div>
										<b><a href="<?php echo esc_url( home_url( (string) $m['url'] ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $m['title'], 22 ) ); ?></a></b>
										<small><?php echo esc_html( implode( ' · ', array_filter( array( (string) $m['reason'], (string) $m['effect_label'] ) ) ) ); ?></small>
										<small class="cq-tier is-<?php echo esc_attr( (string) $m['tier'] ); ?>"><?php echo esc_html( (string) $m['tier_label'] ); ?></small>
									</div>
									<div class="cq-end">
										<span class="cq-pill <?php echo esc_attr( $stl[0] ); ?>"><?php echo esc_html( $stl[1] ); ?></span>
										<div class="cq-tinybar"><span style="width:<?php echo (int) $m['percent']; ?>%"></span></div>
										<small><?php echo $m['questions'] ? (int) $m['answered'] . '/' . (int) $m['questions'] . ' câu' : (int) $m['percent'] . '%'; ?></small>
										<span class="cq-endlinks">
											<?php if ( ! empty( $m['practice_slug'] ) ) : ?><a href="<?php echo esc_url( cvc_exam_url( (string) $m['practice_slug'] ) ); ?>">Luyện</a><?php endif; ?>
											<?php if ( ! empty( $m['item_id'] ) ) : ?><button type="button" class="cq-link" data-cq="item" data-id="<?php echo (int) $m['item_id']; ?>" data-status="<?php echo 'done' === $m['state'] ? 'pending' : 'completed'; ?>"><?php echo 'done' === $m['state'] ? 'Bỏ đánh dấu' : 'Đã đọc'; ?></button><?php endif; ?>
										</span>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<?php /* ---------- Đề thi ---------- */ ?>
			<section class="cq-card" id="de-thi">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">✓</span><div><h2>Đề thi trắc nghiệm phù hợp</h2><p>Ưu tiên đề của đợt tuyển, đề bạn gắn và đề cùng nhóm vị trí</p></div></div><a class="cq-btn is-sm" href="<?php echo esc_url( cvc_exams_url() ); ?>">Ngân hàng đề</a></div>
				<div class="cq-body">
					<?php if ( empty( $d['exams'] ) ) : ?>
						<div class="cq-empty-sm">Chưa có đề phù hợp. Vào <a href="<?php echo esc_url( cvc_exams_url() ); ?>">ngân hàng đề</a> và bấm “Gắn mục tiêu” ở đề bạn muốn luyện.</div>
					<?php else : ?>
						<div class="cq-rows">
							<?php foreach ( array_values( (array) $d['exams'] ) as $i => $e ) :
								$lbl = $e['in_progress'] ? 'Làm tiếp' : ( $e['done'] ? 'Làm lại' : 'Luyện đề' );
								?>
								<div class="cq-row">
									<span class="cq-doc is-exam"><?php echo esc_html( $sp( $i + 1 ) ); ?></span>
									<div>
										<b><a href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $e['title'], 24 ) ); ?></a></b>
										<small><?php echo esc_html( implode( ' · ', array_filter( array( (string) $e['type_label'], $e['total_questions'] ? $e['total_questions'] . ' câu' : '', $e['duration_minutes'] ? $e['duration_minutes'] . ' phút' : '', (string) ( $e['stage'] ?? '' ) ) ) ) ); ?></small>
										<small class="cq-tier is-<?php echo esc_attr( (string) $e['tier'] ); ?>"><?php echo esc_html( $e['attached'] && 'curated' === $e['tier'] ? 'Bạn gắn vào hồ sơ' : (string) $e['tier_label'] ); ?></small>
									</div>
									<div class="cq-end">
										<?php if ( $e['done'] ) : ?><span class="cq-pill is-teal">Cao nhất <?php echo (int) $e['best']; ?>%</span><?php elseif ( $e['in_progress'] ) : ?><span class="cq-pill is-gold">Đang làm dở</span><?php elseif ( 'official' === $e['tier'] ) : ?><span class="cq-pill is-teal">Của đợt tuyển</span><?php else : ?><span class="cq-pill">Chưa làm</span><?php endif; ?>
										<a class="cq-btn is-sm<?php echo 0 === $i ? ' is-primary' : ''; ?>" href="<?php echo esc_url( cvc_exam_url( (string) $e['slug'] ) ); ?>"><?php echo esc_html( $lbl ); ?> →</a>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		</div>

		<aside class="cq-right" id="tien-do">
			<?php /* ---------- Hạn nộp & mốc ---------- */ ?>
			<section class="cq-card cq-deadline">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">◷</span><div><h3>Hạn nộp &amp; mốc quan trọng</h3><p>Chỉ dùng ngày đã xác minh</p></div></div><span class="cq-pill is-gold">Theo dõi</span></div>
				<div class="cq-body">
					<?php if ( $opp && ! empty( $st['deadline_verified'] ) ) : ?>
						<div class="cq-datebig"><?php echo esc_html( cvc_cq_date( $st['deadline'] ) ); ?></div>
						<p class="cq-note"><?php echo esc_html( $st['is_open'] ? ( null !== $st['days_left'] ? 'Hạn nộp hồ sơ · còn ' . (int) $st['days_left'] . ' ngày (theo thông báo chính thức).' : (string) $st['label'] ) : (string) $st['label'] ); ?></p>
						<?php if ( ! empty( $st['is_open'] ) ) : ?>
							<button type="button" class="cq-btn is-gold cq-full" data-cq="reminder" data-id="<?php echo (int) $opp['id']; ?>" data-on="<?php echo ! empty( $opp['reminder'] ) ? '1' : '0'; ?>"><?php echo ! empty( $opp['reminder'] ) ? '◷ Đang nhắc hạn (7, 3, 1 ngày) — tắt' : '＋ Bật nhắc hạn nộp hồ sơ'; ?></button>
						<?php endif; ?>
					<?php else : ?>
						<div class="cq-datebig is-muted"><?php echo $opp && ! empty( $st['deadline'] ) ? esc_html( cvc_cq_date( $st['deadline'] ) ) : 'Chưa xác minh'; ?></div>
						<p class="cq-note"><?php echo $opp ? ( ! empty( $st['deadline'] ) ? 'Hạn này lấy từ tin sưu tầm, chưa có nguồn chính thức — chưa thể đặt nhắc hạn.' : 'Thông báo chưa công bố hạn nộp. Khi có nguồn chính thức, hệ thống sẽ tính thời gian còn lại.' ) : 'Gắn một thông báo tuyển dụng để theo dõi hạn nộp chính thức.'; ?></p>
					<?php endif; ?>
					<div class="cq-milestones">
						<div><span>Ngày thi dự kiến (bạn đặt)</span><b><?php echo ! empty( $g['target_date'] ) ? esc_html( cvc_cq_date( $g['target_date'] ) . ( null !== $g['target_days'] ? ' · còn ' . (int) $g['target_days'] . ' ngày' : '' ) ) : 'Chưa đặt'; ?></b></div>
						<?php if ( ! empty( $d['path']['target_date'] ) ) : ?><div><span>Đích của lộ trình</span><b><?php echo esc_html( cvc_cq_date( $d['path']['target_date'] ) ); ?></b></div><?php endif; ?>
					</div>
					<button type="button" class="cq-btn cq-full" data-cq-open="custom">＋ <?php echo empty( $g['target_date'] ) ? 'Đặt ngày thi dự kiến' : 'Đổi ngày thi dự kiến'; ?></button>
				</div>
			</section>

			<?php /* ---------- Chủ đề yếu ---------- */ ?>
			<section class="cq-card">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">⌁</span><div><h3>Chủ đề cần củng cố</h3><p>Từ kết quả luyện tập của bạn</p></div></div><span class="cq-pill is-red"><?php echo (int) $rd['weak_count']; ?> chủ đề</span></div>
				<div class="cq-body">
					<?php if ( empty( $d['weak_topics'] ) ) : ?>
						<p class="cq-note">Chưa phát hiện chủ đề yếu (cần ít nhất vài câu đã làm ở mỗi chủ đề). Làm thêm đề để hệ thống đánh giá.</p>
					<?php else : ?>
						<?php foreach ( (array) $d['weak_topics'] as $i => $w ) : ?>
							<div class="cq-weak">
								<div><?php if ( ! empty( $w['slug'] ) ) : ?><a href="<?php echo esc_url( cvc_topic_url( (string) $w['slug'] ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $w['name'], 12 ) ); ?></a><?php else : ?><span><?php echo esc_html( wp_trim_words( (string) $w['name'], 12 ) ); ?></span><?php endif; ?><small>Ưu tiên <?php echo (int) $i + 1; ?> · <?php echo (int) $w['wrong']; ?>/<?php echo (int) $w['answered']; ?> câu sai</small></div>
								<strong><?php echo (int) $w['accuracy']; ?>%</strong>
								<div class="cq-bar"><span style="width:<?php echo (int) max( 3, $w['accuracy'] ); ?>%"></span></div>
							</div>
						<?php endforeach; ?>
						<button type="button" class="cq-btn is-teal cq-full" data-cq="practice" data-kind="weak">Luyện phần yếu nhất →</button>
					<?php endif; ?>
				</div>
			</section>

			<?php /* ---------- Hoạt động ---------- */ ?>
			<section class="cq-card">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">↻</span><div><h3>Hoạt động gần đây</h3><p>Nhật ký chuẩn bị</p></div></div></div>
				<div class="cq-body">
					<?php if ( empty( $d['activity'] ) ) : ?>
						<p class="cq-note">Chưa có hoạt động.</p>
					<?php else : ?>
						<div class="cq-activity">
							<?php foreach ( (array) $d['activity'] as $a ) :
								$ic = array( 'attempt' => '✓', 'task' => '☑', 'plan' => '↗', 'save' => '♡', 'goal' => '◎' )[ $a['type'] ] ?? '•';
								?>
								<div class="cq-act"><span class="cq-acticon" aria-hidden="true"><?php echo esc_html( $ic ); ?></span><div>
									<b><?php if ( ! empty( $a['attempt_id'] ) && function_exists( 'cvc_exam_result_url' ) ) : ?><a href="<?php echo esc_url( cvc_exam_result_url( (int) $a['attempt_id'] ) ); ?>"><?php echo esc_html( (string) $a['title'] ); ?></a><?php else : ?><?php echo esc_html( (string) $a['title'] ); ?><?php endif; ?></b>
									<small><?php echo esc_html( implode( ' · ', array_filter( array( cvc_cq_ago( $a['at'] ?? null ), (string) ( $a['meta'] ?? '' ) ) ) ) ); ?></small>
								</div></div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<?php /* ---------- AI Study Coach ---------- */ ?>
			<section class="cq-card cq-ai">
				<div class="cq-body">
					<div class="cq-eyebrow">✦ AI STUDY COACH</div>
					<h3>Biết mình yếu ở đâu. Học đúng phần đó.</h3>
					<p>Kế hoạch 7 ngày tới, chỉ dựa trên chủ đề yếu, tài liệu và đề trong hồ sơ này cùng thời gian còn lại.</p>
					<button type="button" class="cq-btn" data-cq="coach">✦ Tạo kế hoạch cá nhân</button>
				</div>
			</section>

			<?php /* ---------- Liên kết ---------- */ ?>
			<section class="cq-card">
				<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">⌘</span><div><h3>Liên kết thông minh</h3><p>Mọi thành phần cùng một hồ sơ</p></div></div></div>
				<div class="cq-body"><div class="cq-activity">
					<div class="cq-act"><span class="cq-acticon">1</span><div><b>Tuyển dụng → Tài liệu</b><small>Ưu tiên danh mục ôn tập của Hội đồng và văn bản thông báo trích dẫn.</small></div></div>
					<div class="cq-act"><span class="cq-acticon">2</span><div><b>Tài liệu → Đề thi</b><small>Câu hỏi gắn điều khoản của văn bản gốc.</small></div></div>
					<div class="cq-act"><span class="cq-acticon">3</span><div><b>Kết quả → Chủ đề yếu</b><small>Mỗi lần nộp bài cập nhật tỷ lệ đúng theo chủ đề.</small></div></div>
					<div class="cq-act"><span class="cq-acticon">4</span><div><b>Tiến độ → Kế hoạch</b><small>Đề đã làm tự đánh dấu xong; bấm “Cập nhật kế hoạch” để xếp lại ưu tiên.</small></div></div>
				</div></div>
			</section>
		</aside>
	</div>
	<?php
	cvc_cq_render_templates( $d );
}

function cvc_cq_task( array $t ): void {
	$done = 'completed' === $t['status'];
	$link = cvc_cq_task_link( $t );
	$type = array( 'topic' => 'Ôn chủ đề', 'exam' => 'Làm đề', 'legal_document' => 'Đọc văn bản', 'practice' => 'Bài luyện', 'task' => 'Nhiệm vụ' )[ $t['type'] ] ?? 'Bước học';
	?>
	<label class="cq-task<?php echo $done ? ' is-done' : ''; ?>">
		<input type="checkbox" data-cq="item" data-id="<?php echo (int) $t['id']; ?>"<?php checked( $done ); ?>>
		<span>
			<b><?php if ( '' !== $link ) : ?><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( (string) $t['title'] ); ?></a><?php elseif ( 'practice' === $t['type'] ) : ?><button type="button" class="cq-link" data-cq="practice" data-kind="<?php echo esc_attr( (string) ( $t['link']['practice_kind'] ?? 'weak' ) ); ?>"><?php echo esc_html( (string) $t['title'] ); ?></button><?php else : ?><?php echo esc_html( (string) $t['title'] ); ?><?php endif; ?></b>
			<small><?php echo esc_html( $type . ( ! empty( $t['reason'] ) ? ' · ' . $t['reason'] : '' ) ); ?></small>
		</span>
		<time><?php echo ! empty( $t['due_date'] ) ? esc_html( 'Hạn ' . cvc_cq_date( (string) $t['due_date'] ) ) : ''; ?></time>
	</label>
	<?php
}

/** Hồ sơ trống: 3 cách tạo. */
function cvc_cq_render_empty( array $d ): void {
	?>
	<div class="cq-data" data-goal="0" hidden></div>
	<section class="cq-card cq-start">
		<div class="cq-head"><div class="cq-headl"><span class="cq-hi" aria-hidden="true">◎</span><div><h2>Bắt đầu hồ sơ chinh phục đầu tiên</h2><p>Mỗi hồ sơ là một mục tiêu: tài liệu, đề thi, kế hoạch và tiến độ riêng</p></div></div></div>
		<div class="cq-body">
			<ol class="cq-howto">
				<li><b>Chọn mục tiêu</b><span>Một thông báo tuyển dụng cụ thể, hoặc nhóm vị trí bạn muốn thi.</span></li>
				<li><b>Hệ thống gom tài liệu, đề và lập lộ trình</b><span>Ưu tiên danh mục ôn tập chính thức của đợt tuyển nếu có.</span></li>
				<li><b>Luyện và theo dõi tiến độ</b><span>Kết quả mỗi bài thi cập nhật chủ đề yếu và mức độ sẵn sàng.</span></li>
			</ol>
			<div class="cq-btnrow"><button type="button" class="cq-btn is-primary" data-cq-open="add">＋ Tạo hồ sơ chinh phục</button><a class="cq-btn" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tìm tin tuyển dụng</a></div>
		</div>
	</section>
	<?php
	cvc_cq_render_templates( $d );
}

/** Nội dung các hộp thoại (template, JS chép vào modal). */
function cvc_cq_render_templates( array $d ): void {
	$g        = is_array( $d['goal'] ?? null ) ? $d['goal'] : array();
	$families = (array) ( $d['families'] ?? array() );
	$provs    = (array) ( $d['provinces'] ?? array() );
	$saved    = (array) ( $d['saved_recruitments'] ?? array() );
	$min      = wp_date( 'Y-m-d' );
	?>
	<template id="cq-tpl-add">
		<div class="cq-tabs" role="tablist"><button type="button" class="on" data-cq-tab="saved">Tin đã lưu</button><button type="button" data-cq-tab="search">Tìm tin tuyển dụng</button><button type="button" data-cq-tab="family">Theo nhóm vị trí</button></div>
		<div class="cq-tabpane" data-pane="saved">
			<?php if ( ! $saved ) : ?>
				<p class="cq-note">Bạn chưa lưu tin tuyển dụng nào. Dùng thẻ “Tìm tin tuyển dụng” hoặc lưu tin ở trang Tuyển dụng.</p>
			<?php else : ?>
				<div class="cq-pick">
					<?php foreach ( $saved as $r ) : ?>
						<label><input type="radio" name="cq_rec" value="<?php echo (int) $r['id']; ?>"><span><b><?php echo esc_html( wp_trim_words( (string) $r['title'], 20 ) ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( (string) ( $r['agency'] ?? '' ), (string) ( $r['status']['label'] ?? '' ) ) ) ) ); ?></small></span></label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="cq-tabpane" data-pane="search" hidden>
			<label for="cq-q">Tên vị trí, cơ quan hoặc địa phương</label>
			<div class="cq-searchrow"><input id="cq-q" type="search" placeholder="VD: giáo viên Lai Châu" autocomplete="off"><button type="button" class="cq-btn is-primary" data-cq="search">Tìm</button></div>
			<div class="cq-pick" data-cq-results><p class="cq-note">Chỉ hiện tin đang hiển thị trên Công Viên Chức.</p></div>
		</div>
		<div class="cq-tabpane" data-pane="family" hidden>
			<label for="cq-fam">Nhóm vị trí</label>
			<select id="cq-fam"><option value="">— Chọn nhóm —</option><?php foreach ( $families as $k => $v ) : ?><option value="<?php echo esc_attr( (string) $k ); ?>"><?php echo esc_html( (string) $v ); ?></option><?php endforeach; ?></select>
			<label for="cq-prov">Địa phương (không bắt buộc)</label>
			<select id="cq-prov"><option value="">Cả nước</option><?php foreach ( $provs as $p ) : ?><option value="<?php echo (int) $p['id']; ?>"><?php echo esc_html( (string) $p['name'] ); ?></option><?php endforeach; ?></select>
			<label for="cq-et">Loại tuyển dụng</label>
			<select id="cq-et"><option value="">Chưa xác định</option><option value="civil">Công chức</option><option value="public">Viên chức</option><option value="contract">Hợp đồng</option></select>
		</div>
		<label for="cq-date">Ngày thi dự kiến (không bắt buộc)</label>
		<input id="cq-date" type="date" min="<?php echo esc_attr( $min ); ?>">
		<p class="cq-note">Hệ thống tạo lộ trình riêng cho hồ sơ; hồ sơ khác giữ nguyên tiến độ.</p>
	</template>
	<template id="cq-tpl-switch">
		<div class="cq-pick">
			<?php foreach ( (array) ( $d['profiles'] ?? array() ) as $p ) : ?>
				<div class="cq-prof<?php echo ! empty( $g['id'] ) && (int) $g['id'] === (int) $p['id'] ? ' is-cur' : ''; ?>">
					<a href="<?php echo esc_url( cvc_conquest_url( (int) $p['id'] ) ); ?>" data-cq-go="<?php echo (int) $p['id']; ?>"><b><?php echo esc_html( wp_trim_words( (string) $p['title'], 18 ) ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( ! empty( $p['is_primary'] ) ? 'Hồ sơ chính' : '', (string) ( $p['job_family_label'] ?? '' ), ! empty( $p['has_recruitment'] ) ? 'Gắn tin tuyển dụng' : 'Theo nhóm vị trí' ) ) ) ); ?></small></a>
					<span class="cq-profact">
						<?php if ( empty( $p['is_primary'] ) ) : ?><button type="button" class="cq-link" data-cq="primary" data-id="<?php echo (int) $p['id']; ?>">Đặt làm chính</button><?php endif; ?>
						<button type="button" class="cq-link is-red" data-cq="archive" data-id="<?php echo (int) $p['id']; ?>">Lưu trữ</button>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="cq-note">Hồ sơ chính được dùng để ưu tiên tin, đề và tài liệu ở trang Tuyển dụng. Lưu trữ không xóa lịch sử làm bài.</p>
	</template>
	<?php if ( $g ) : ?>
		<template id="cq-tpl-custom">
			<label for="cq-c-date">Ngày thi dự kiến</label>
			<input id="cq-c-date" type="date" min="<?php echo esc_attr( $min ); ?>" value="<?php echo esc_attr( (string) ( $g['target_date'] ?? '' ) ); ?>">
			<label for="cq-c-fam">Nhóm vị trí</label>
			<select id="cq-c-fam"><option value="">— Chưa chọn —</option><?php foreach ( $families as $k => $v ) : ?><option value="<?php echo esc_attr( (string) $k ); ?>"<?php selected( (string) ( $g['job_family'] ?? '' ), (string) $k ); ?>><?php echo esc_html( (string) $v ); ?></option><?php endforeach; ?></select>
			<label for="cq-c-prov">Địa phương</label>
			<select id="cq-c-prov"><option value="">Cả nước</option><?php foreach ( $provs as $p ) : ?><option value="<?php echo (int) $p['id']; ?>"<?php selected( (int) ( $g['province_id'] ?? 0 ), (int) $p['id'] ); ?>><?php echo esc_html( (string) $p['name'] ); ?></option><?php endforeach; ?></select>
			<label class="cq-checkline"><input id="cq-c-regen" type="checkbox" checked> Tạo lại lộ trình theo thiết lập mới (rải lại hạn từng bước đến ngày thi)</label>
		</template>
	<?php endif; ?>
	<?php
}

/* ------------------------------------------------------------------
 * Tài nguyên
 * ------------------------------------------------------------------ */
add_action( 'wp_enqueue_scripts', 'cvc_cq_assets', 60 );

function cvc_cq_is_page(): bool {
	return 'account' === (string) get_query_var( 'cvc_page' ) && 'ho-so-chinh-phuc' === sanitize_key( (string) get_query_var( 'cvc_account_section' ) );
}

function cvc_cq_assets(): void {
	if ( ! cvc_cq_is_page() ) {
		return;
	}
	wp_enqueue_style( 'cvc-conquest', get_theme_file_uri( '/assets/css/conquest.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/conquest.css' ) ) );
	wp_enqueue_script( 'cvc-conquest', get_theme_file_uri( '/assets/js/conquest.js' ), array(), (string) filemtime( get_theme_file_path( '/assets/js/conquest.js' ) ), true );
	wp_add_inline_script(
		'cvc-conquest',
		'window.CVC_CQ=' . wp_json_encode(
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'cvc_cq' ),
				'base'  => cvc_conquest_url(),
			)
		) . ';',
		'before'
	);
}

/* ------------------------------------------------------------------
 * AJAX - cần đăng nhập + nonce
 * ------------------------------------------------------------------ */
add_action( 'wp_ajax_cvc_cq', 'cvc_cq_ajax' );
add_action( 'wp_ajax_nopriv_cvc_cq', 'cvc_cq_ajax' );

function cvc_cq_ajax(): void {
	check_ajax_referer( 'cvc_cq', 'nonce' );
	nocache_headers();
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Phiên đăng nhập đã hết hạn — vui lòng đăng nhập lại.', 'login' => cvc_login_url( cvc_conquest_url() ) ), 401 );
	}
	$act  = sanitize_key( wp_unslash( $_POST['act'] ?? '' ) );
	$goal = absint( $_POST['goal'] ?? 0 );
	$id   = absint( $_POST['id'] ?? 0 );
	$c    = new CVC_Api_Client( null, 40 );
	$on   = static fn ( string $k ) => in_array( (string) ( $_POST[ $k ] ?? '' ), array( '1', 'true', 'on' ), true );
	$res  = null;
	$out  = array();
	switch ( $act ) {
		case 'view':
			ob_start();
			cvc_cq_render_body( cvc_cq_get( $goal ) );
			wp_send_json_success( array( 'html' => (string) ob_get_clean() ) );
			break;
		case 'create':
			$body = array();
			if ( $id > 0 ) {
				$body['recruitment_id'] = $id;
			} else {
				$body['job_family'] = sanitize_key( wp_unslash( $_POST['job_family'] ?? '' ) );
				if ( absint( $_POST['province_id'] ?? 0 ) > 0 ) {
					$body['province_id'] = absint( $_POST['province_id'] );
				}
				$et = sanitize_key( wp_unslash( $_POST['employment_type'] ?? '' ) );
				if ( in_array( $et, array( 'civil', 'public', 'contract' ), true ) ) {
					$body['employment_type'] = $et;
				}
			}
			$td = (string) ( $_POST['target_date'] ?? '' );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $td ) ) {
				$body['target_date'] = $td;
			}
			$res = $c->post( '/api/me/conquest/profiles', $body, $token );
			$out['goal'] = (int) ( $res['data']['data']['goal_id'] ?? 0 );
			break;
		case 'primary':
		case 'archive':
		case 'plan':
			$res = $c->post( '/api/me/conquest/profiles/' . $id . '/' . $act, array(), $token );
			$out['goal'] = 'archive' === $act ? (int) ( $res['data']['data']['goal_id'] ?? 0 ) : $id;
			break;
		case 'update':
			$body = array( 'regenerate' => $on( 'regenerate' ) );
			$td   = (string) ( $_POST['target_date'] ?? '' );
			$body['target_date'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $td ) ? $td : null;
			$body['job_family']  = sanitize_key( wp_unslash( $_POST['job_family'] ?? '' ) ) ?: null;
			$body['province_id'] = absint( $_POST['province_id'] ?? 0 ) ?: null;
			$res = $c->put( '/api/me/conquest/profiles/' . $id, $body, $token );
			$out['goal'] = $id;
			break;
		case 'coach':
			$res = ( new CVC_Api_Client( null, 60 ) )->post( '/api/me/conquest/profiles/' . $id . '/coach', array(), $token );
			if ( $res['ok'] ) {
				$plan  = (array) ( $res['data']['data'] ?? array() );
				$steps = array();
				foreach ( (array) ( $plan['steps'] ?? array() ) as $s ) {
					$url = '';
					if ( 'topic' === ( $s['kind'] ?? '' ) && ! empty( $s['slug'] ) ) {
						$url = cvc_topic_url( (string) $s['slug'] );
					} elseif ( 'exam' === ( $s['kind'] ?? '' ) && ! empty( $s['slug'] ) ) {
						$url = cvc_exam_url( (string) $s['slug'] );
					} elseif ( 'legal_document' === ( $s['kind'] ?? '' ) && ! empty( $s['url'] ) ) {
						$url = home_url( (string) $s['url'] );
					}
					$steps[] = array( 'title' => (string) ( $s['title'] ?? '' ), 'minutes' => (int) ( $s['minutes'] ?? 0 ), 'why' => (string) ( $s['why'] ?? '' ), 'url' => $url );
				}
				wp_send_json_success( array( 'source' => (string) ( $plan['source'] ?? '' ), 'summary' => (string) ( $plan['summary'] ?? '' ), 'steps' => $steps ) );
			}
			break;
		case 'item':
			$st  = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
			$res = $c->post( '/api/me/career/path/items/' . $id, array( 'status' => in_array( $st, array( 'pending', 'completed' ), true ) ? $st : 'pending' ), $token );
			$out['goal'] = $goal;
			break;
		case 'save':
			$res = $c->post( '/api/me/career/save', array( 'type' => 'recruitment', 'id' => $id, 'saved' => $on( 'on' ) ), $token );
			$out['goal'] = $goal;
			break;
		case 'reminder':
			$res = $c->post( '/api/me/career/reminders', array( 'recruitment_id' => $id, 'enabled' => $on( 'on' ) ), $token );
			$out['goal'] = $goal;
			break;
		case 'practice':
			$kind = sanitize_key( wp_unslash( $_POST['kind'] ?? 'weak' ) );
			$res  = $c->post( '/api/me/career/practice', array( 'kind' => in_array( $kind, array( 'wrong', 'weak', 'recheck' ), true ) ? $kind : 'weak' ), $token );
			$aid  = (int) ( $res['data']['data']['id'] ?? 0 );
			if ( $res['ok'] && $aid > 0 ) {
				wp_send_json_success( array( 'url' => cvc_exam_attempt_url( $aid ) ) );
			}
			break;
		case 'export':
			$cur = cvc_cq_get( $goal );
			if ( $cur['ok'] ) {
				$d = (array) ( $cur['data']['data'] ?? array() );
				if ( empty( $d['goal'] ) ) {
					wp_send_json_error( array( 'message' => 'Chưa có hồ sơ để xuất.' ), 404 );
				}
				$opp = is_array( $d['opportunity'] ?? null ) ? $d['opportunity'] : null;
				wp_send_json_success(
					array(
						'filename' => 'ho-so-chinh-phuc-' . (int) $d['goal']['id'] . '-' . wp_date( 'Ymd' ) . '.json',
						'data'     => array(
							'xuat_luc'      => wp_date( 'c' ),
							'ho_so'         => $d['goal'],
							'co_hoi'        => $opp ? array( 'tieu_de' => $opp['title'], 'co_quan' => $opp['agency']['name'] ?? null, 'dia_phuong' => $opp['province']['name'] ?? null, 'trang_thai' => $opp['status'], 'nguon' => $opp['source_url'] ?? null ) : null,
							'muc_do_san_sang' => $d['readiness'],
							'lo_trinh'      => $d['stages'],
							'viec'          => array_map( static fn ( $t ) => array( 'viec' => $t['title'], 'loai' => $t['type'], 'trang_thai' => $t['status'], 'han' => $t['due_date'] ?? null ), (array) $d['tasks'] ),
							'tai_lieu'      => array_map( static fn ( $m ) => array( 'van_ban' => trim( $m['number'] . ' ' . $m['title'] ), 'muc_xac_nhan' => $m['tier_label'], 'tien_do' => $m['percent'], 'cau_da_lam' => $m['answered'] . '/' . $m['questions'] ), (array) $d['materials'] ),
							'de_thi'        => array_map( static fn ( $e ) => array( 'de' => $e['title'], 'muc_xac_nhan' => $e['tier_label'], 'so_lan_lam' => $e['done'], 'cao_nhat' => $e['best'] ), (array) $d['exams'] ),
							'chu_de_yeu'    => $d['weak_topics'],
							'ghi_chu'       => 'Mức độ sẵn sàng là chỉ số tiến độ học tập, không phải xác suất trúng tuyển. Đối chiếu hạn nộp, điều kiện với thông báo gốc.',
						),
					)
				);
			}
			$res = $cur;
			break;
		case 'search':
			$q   = mb_substr( sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), 0, 100 );
			$res = $c->get( '/api/career/recruitments', array( 'q' => $q, 'per_page' => 8, 'status' => 'all' ), $token );
			if ( $res['ok'] ) {
				$items = (array) ( $res['data']['data']['items'] ?? ( $res['data']['data']['data'] ?? array() ) );
				ob_start();
				if ( ! $items ) {
					echo '<p class="cq-note">Không tìm thấy tin phù hợp. Thử từ khóa ngắn hơn (tên cơ quan, vị trí, tỉnh).</p>';
				}
				foreach ( $items as $r ) {
					printf(
						'<label><input type="radio" name="cq_rec" value="%1$d"><span><b>%2$s</b><small>%3$s</small></span></label>',
						(int) $r['id'],
						esc_html( wp_trim_words( (string) $r['title'], 20 ) ),
						esc_html( implode( ' · ', array_filter( array( (string) ( $r['agency']['name'] ?? '' ), (string) ( $r['province']['name'] ?? '' ), (string) ( $r['status']['label'] ?? '' ) ) ) ) )
					);
				}
				wp_send_json_success( array( 'html' => (string) ob_get_clean() ) );
			}
			break;
		default:
			wp_send_json_error( array( 'message' => 'Thao tác không hợp lệ.' ), 400 );
	}
	if ( ! $res || ! $res['ok'] ) {
		$msg = (string) ( $res['data']['message'] ?? '' );
		wp_send_json_error( array( 'message' => '' !== $msg && in_array( (int) ( $res['status'] ?? 0 ), array( 404, 422, 429 ), true ) ? $msg : cvc_api_error_message( (array) $res ) ), (int) ( $res['status'] ?? 0 ) ?: 500 );
	}
	wp_send_json_success( $out + array( 'message' => (string) ( $res['data']['message'] ?? 'Đã lưu.' ) ) );
}

/* ------------------------------------------------------------------
 * Lối vào từ trang khác (form POST, chạy cả khi tắt JS)
 * ------------------------------------------------------------------ */
add_action( 'admin_post_cvc_cq_go', 'cvc_cq_go' );
add_action( 'admin_post_nopriv_cvc_cq_go', 'cvc_cq_go' );

/** Nút "Chinh phục kỳ thi này" / "Cập nhật kế hoạch ôn tập" / "Gắn đề với mục tiêu". */
function cvc_cq_go(): void {
	$do  = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
	$id  = absint( $_POST['id'] ?? 0 );
	$ret = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['return'] ?? '' ) ), cvc_conquest_url() );
	if ( ! wp_verify_nonce( (string) ( $_POST['_cq'] ?? '' ), 'cvc_cq_go' ) ) {
		wp_safe_redirect( $ret );
		exit;
	}
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_safe_redirect( cvc_login_url( $ret ) );
		exit;
	}
	$c = new CVC_Api_Client( null, 40 );
	switch ( $do ) {
		case 'recruitment':
			$res  = $c->post( '/api/me/conquest/profiles', array( 'recruitment_id' => $id ), $token );
			$gid  = (int) ( $res['data']['data']['goal_id'] ?? 0 );
			cvc_cq_flash( $res );
			wp_safe_redirect( $res['ok'] ? cvc_conquest_url( $gid ) : $ret );
			exit;
		case 'plan':
			$cur = cvc_cq_get();
			$gid = (int) ( $cur['data']['data']['goal']['id'] ?? 0 );
			if ( $gid <= 0 ) {
				cvc_cq_notice( 'success', 'Bạn chưa có hồ sơ chinh phục — hãy tạo hồ sơ để hệ thống lập kế hoạch ôn tập.' );
				wp_safe_redirect( cvc_conquest_url() );
				exit;
			}
			$res = $c->post( '/api/me/conquest/profiles/' . $gid . '/plan', array(), $token );
			cvc_cq_flash( $res );
			wp_safe_redirect( cvc_conquest_url( $gid, 'viec-can-lam' ) );
			exit;
	}
	wp_safe_redirect( $ret );
	exit;
}

function cvc_cq_notice( string $type, string $message ): void {
	// Cùng định dạng cookie với cvc_redirect_with_notice() - header.php in ra 1 lần (cvc_render_notice).
	setcookie( 'cvc_notice', wp_json_encode( array( 'type' => $type, 'message' => $message ) ), array( 'expires' => time() + MINUTE_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ) );
}

function cvc_cq_flash( array $res ): void {
	$msg = (string) ( $res['data']['message'] ?? '' );
	cvc_cq_notice( $res['ok'] ? 'success' : 'error', '' !== $msg ? $msg : ( $res['ok'] ? 'Đã cập nhật hồ sơ chinh phục.' : cvc_api_error_message( $res ) ) );
}

/** Trường ẩn của form lối vào (admin-post cvc_cq_go). */
function cvc_cq_go_fields( string $do, int $id, string $return_url ): string {
	return '<input type="hidden" name="action" value="cvc_cq_go"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">'
		. '<input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="return" value="' . esc_attr( $return_url ) . '">'
		. '<input type="hidden" name="_cq" value="' . esc_attr( wp_create_nonce( 'cvc_cq_go' ) ) . '">';
}

/** Form nút lối vào (dùng ở trang tin tuyển dụng, trang kết quả thi). */
function cvc_cq_go_button( string $do, int $id, string $label, string $class, string $return_url ): string {
	return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="cq-goform" style="display:inline">'
		. cvc_cq_go_fields( $do, $id, $return_url )
		. '<button type="submit" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
}
