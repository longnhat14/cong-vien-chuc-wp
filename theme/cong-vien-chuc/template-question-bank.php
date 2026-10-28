<?php
/**
 * Ngân hàng câu hỏi v1 (19/10/2026) - theo thiết kế congvienchuc-question-bank-v1, dữ liệu thật:
 *  - nhóm Công chức / Viên chức (question_tracks), thống kê, tìm kiếm + bộ lọc cơ bản / nâng cao,
 *    chế độ Tất cả / Chưa làm / Đã sai / Đã lưu, sắp xếp, dạng thẻ / danh sách, phân trang phía máy chủ;
 *  - mỗi câu: luyện nhanh ngay trên thẻ (đáp án + giải thích + căn cứ sau khi chọn), lưu câu, mở căn cứ;
 *  - Ôn tập / Thi thử nhóm câu đang lọc (bài luyện cá nhân), cột phải tiến độ + chủ đề cần củng cố + văn bản.
 * Không có số liệu minh họa: mọi con số lấy từ API (/api/question-bank/*).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$f         = cvc_qb_filters( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
$logged_in = cvc_is_logged_in();
$token     = $logged_in ? cvc_auth_token() : null;
$personal  = '' !== $f['che-do'];

// Chế độ cá nhân cần đăng nhập.
if ( $personal && ! $token ) {
	wp_safe_redirect( cvc_login_url( cvc_qb_url( $f ) ) );
	exit;
}

$d         = cvc_qb_facets( $f );
$query     = cvc_qb_api_filters( $f ) + array_filter(
	array(
		'mode'     => CVC_QB_MODES[ $f['che-do'] ] ?? null,
		'sort'     => CVC_QB_SORTS[ $f['sap-xep'] ] ?? null,
		'page'     => $f['trang'] > 1 ? $f['trang'] : null,
		'per_page' => 20,
	)
);
$list      = cvc_ci_api( '/api/question-bank/questions', $query, $token, 15, $token ? 0 : 600 );
$list_ok   = ! empty( $list['ok'] );
$ld        = $list_ok ? (array) ( $list['data']['data'] ?? array() ) : array();
$items     = array_values( array_filter( (array) ( $ld['items'] ?? array() ), 'is_array' ) );
$total     = (int) ( $ld['total'] ?? 0 );
$page      = (int) ( $ld['page'] ?? 1 );
$last      = (int) ( $ld['last_page'] ?? 1 );
$me        = array();
if ( $token ) {
	$mr = ( new CVC_Api_Client( null, 10 ) )->get( '/api/question-bank/me', array(), $token );
	$me = ! empty( $mr['ok'] ) ? (array) ( $mr['data']['data'] ?? array() ) : array();
}

$tracks    = (array) ( $d['tracks'] ?? array() );
$all_total = (int) ( $d['all_total'] ?? 0 );
$docs      = (array) ( $d['documents'] ?? array() );
$fmt       = static fn ( $n ): string => number_format( (int) $n, 0, ',', '.' );
$filtered  = $f['nhom'] || $f['q'] || $f['mon'] || $f['chu-de'] || $f['van-ban'] || $f['do-kho'] || $f['loai'] || $f['can-cu'] || $personal || $f['sap-xep'] || $f['trang'] > 1;
$advanced  = $f['van-ban'] || $f['mon'] || $f['can-cu'];
$group_n   = (int) ( $d['total'] ?? $total );

cvc_seo_set_title( 'Ngân hàng câu hỏi trắc nghiệm công chức, viên chức' );
cvc_seo_set_description( 'Tra cứu ' . $fmt( $all_total ) . ' câu hỏi trắc nghiệm đã duyệt theo nhóm Công chức / Viên chức, chủ đề, văn bản pháp luật và từng Điều - luyện ngay từng câu, xem đáp án, giải thích và căn cứ.' );
cvc_seo_set_canonical( cvc_question_bank_url() );
if ( $filtered ) {
	cvc_seo_set_noindex();
}
if ( $logged_in ) {
	nocache_headers();
}

$sel = static function ( string $id, string $name, string $empty, array $rows, int $cur, bool $disabled = false ): void {
	echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-qb-auto' . ( $disabled ? ' disabled' : '' ) . '>';
	echo '<option value="">' . esc_html( $empty ) . '</option>';
	foreach ( $rows as $r ) {
		echo '<option value="' . (int) $r['id'] . '"' . selected( (int) $r['id'], $cur, false ) . '>' . esc_html( $r['name'] . ' (' . number_format( (int) $r['count'], 0, ',', '.' ) . ')' ) . '</option>';
	}
	echo '</select>';
};
$opt = static function ( string $id, string $name, string $empty, array $map, string $cur ): void {
	echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" data-qb-auto><option value="">' . esc_html( $empty ) . '</option>';
	foreach ( $map as $k => $label ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
};

$track_cards = array(
	''          => array( '⚖', 'NGÂN HÀNG CÂU HỎI', 'Tất cả nhóm', 'Kiến thức chung · chuyên ngành · pháp luật', $tracks['all'] ?? $all_total ),
	'cong-chuc' => array( '🏛', 'NHÓM ÔN THI', 'Công chức', 'Theo đề & danh mục ôn tập của tin tuyển công chức', $tracks['civil_servant'] ?? 0 ),
	'vien-chuc' => array( '🎓', 'NHÓM ÔN THI', 'Viên chức', 'Giáo dục · y tế · đơn vị sự nghiệp công lập', $tracks['public_employee'] ?? 0 ),
);
$modes = array(
	''         => 'Tất cả câu hỏi',
	'chua-lam' => 'Chưa làm',
	'sai'      => 'Đã trả lời sai',
	'dung'     => 'Đã trả lời đúng',
	'da-luu'   => 'Đã lưu',
);
$diff_lbl = array( 'de' => 'Dễ', 'tb' => 'Trung bình', 'kho' => 'Khó' );
$fmt_lbl  = array( 'mot-dap-an' => 'Một đáp án đúng', 'dung-sai' => 'Đúng / Sai', 'dien-khuyet' => 'Điền khuyết', 'tinh-huong' => 'Tình huống' );
$sort_lbl = array( '' => 'Phù hợp nhất (theo văn bản, Điều)', 'do-kho' => 'Độ khó tăng dần', 'luot-luyen' => 'Lượt luyện nhiều', 'moi' => 'Mới cập nhật' );
$diff_cls = array( 'easy' => '', 'medium' => 'is-g', 'hard' => 'is-r' );
$st_lbl   = array( 'new' => array( 'Chưa làm', '' ), 'wrong' => array( 'Đã trả lời sai', 'is-r' ), 'correct' => array( 'Đã trả lời đúng', 'is-t' ) );

get_header();
?>
<main id="main" class="qb">
	<div class="qb-wrap">
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Ôn thi', 'url' => cvc_exams_url() ),
				array( 'label' => 'Ngân hàng câu hỏi' ),
			)
		);
		cvc_render_notice();
		?>

		<section class="qb-hero">
			<div class="qb-hero-copy">
				<p class="qb-eyebrow"><i class="qb-dot" aria-hidden="true"></i>NGÂN HÀNG KIẾN THỨC PHÁP LUẬT</p>
				<h1>Ngân hàng câu hỏi trắc nghiệm</h1>
				<p class="qb-lead">Tìm câu hỏi theo văn bản, Điều, chủ đề, nhóm ôn thi và độ khó. Luyện ngay từng câu để xem đáp án, giải thích và căn cứ pháp luật - hiểu bản chất thay vì chỉ ghi nhớ đáp án.</p>
				<div class="qb-actions">
					<a class="qb-b is-primary" href="#qb-search" data-qb-focus>⌕ Tìm câu hỏi</a>
					<a class="qb-b is-teal" href="<?php echo esc_url( $logged_in ? cvc_qb_url( $f, array( 'che-do' => 'da-luu', 'trang' => 1 ) ) : cvc_login_url( cvc_qb_url( $f, array( 'che-do' => 'da-luu' ) ) ) ); ?>">☆ Câu hỏi đã lưu</a>
					<a class="qb-b" href="#qb-practice">▣ Ôn tập / thi thử nhóm đang lọc</a>
				</div>
			</div>
			<div class="qb-hero-aside">
				<small>CÂU HỎI ĐÃ DUYỆT</small>
				<strong><?php echo esc_html( $fmt( $all_total ) ); ?></strong>
				<span>có đáp án, giải thích &amp; căn cứ</span>
			</div>
		</section>

		<nav class="qb-tracks" aria-label="Chọn nhóm câu hỏi chính">
			<?php foreach ( $track_cards as $k => $t ) : ?>
				<a class="qb-track<?php echo $k === $f['nhom'] ? ' is-active' : ''; ?>" data-track="<?php echo esc_attr( '' === $k ? 'all' : $k ); ?>" href="<?php echo esc_url( cvc_qb_url( $f, array( 'nhom' => $k, 'trang' => 1 ) ) . '#qb-search' ); ?>"<?php echo $k === $f['nhom'] ? ' aria-current="true"' : ''; ?>>
					<span class="qb-track-ic" aria-hidden="true"><?php echo esc_html( $t[0] ); ?></span>
					<span class="qb-track-copy"><small><?php echo esc_html( $t[1] ); ?></small><strong><?php echo esc_html( $t[2] ); ?></strong><em><?php echo esc_html( $t[3] ); ?></em></span>
					<span class="qb-track-n"><b><?php echo esc_html( $fmt( $t[4] ) ); ?></b><small>câu</small></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<section class="qb-stats" aria-label="Số liệu ngân hàng câu hỏi">
			<article class="qb-stat"><span class="qb-si" aria-hidden="true">▤</span><div><strong><?php echo esc_html( $fmt( $group_n ) ); ?></strong><small><?php echo $f['nhom'] || $f['q'] || $f['mon'] || $f['chu-de'] || $f['van-ban'] || $f['do-kho'] || $f['loai'] || $f['can-cu'] ? 'Câu khớp bộ lọc' : 'Tổng câu hỏi đã duyệt'; ?></small></div></article>
			<article class="qb-stat"><span class="qb-si is-b" aria-hidden="true">§</span><div><strong><?php echo esc_html( $fmt( $d['documents_total'] ?? 0 ) ); ?></strong><small>Văn bản pháp luật làm căn cứ</small></div></article>
			<?php if ( $token ) : ?>
				<a class="qb-stat" href="<?php echo esc_url( cvc_qb_url( $f, array( 'che-do' => 'da-luu', 'trang' => 1 ) ) ); ?>"><span class="qb-si is-g" aria-hidden="true">☆</span><div><strong data-qb-saved-count><?php echo esc_html( $fmt( $me['saved'] ?? 0 ) ); ?></strong><small>Câu hỏi đã lưu</small></div></a>
				<a class="qb-stat" href="<?php echo esc_url( cvc_qb_url( $f, array( 'che-do' => 'sai', 'trang' => 1 ) ) ); ?>"><span class="qb-si is-r" aria-hidden="true">◎</span><div><strong><?php echo esc_html( $fmt( $me['wrong'] ?? 0 ) ); ?></strong><small>Câu đang trả lời sai</small></div></a>
			<?php else : ?>
				<a class="qb-stat" href="<?php echo esc_url( cvc_login_url( cvc_qb_url( $f ) ) ); ?>"><span class="qb-si is-g" aria-hidden="true">☆</span><div><strong>Lưu câu</strong><small>Đăng nhập để lưu câu cần xem lại</small></div></a>
				<a class="qb-stat" href="<?php echo esc_url( cvc_login_url( cvc_qb_url( $f ) ) ); ?>"><span class="qb-si is-r" aria-hidden="true">◎</span><div><strong>Theo dõi</strong><small>Câu sai &amp; chủ đề cần củng cố</small></div></a>
			<?php endif; ?>
		</section>

		<form class="qb-panel qb-search" id="qb-search" method="get" action="<?php echo esc_url( cvc_question_bank_url() ); ?>" data-qb-form role="search">
			<?php if ( $f['nhom'] ) : ?><input type="hidden" name="nhom" value="<?php echo esc_attr( $f['nhom'] ); ?>"><?php endif; ?>
			<?php if ( $f['che-do'] ) : ?><input type="hidden" name="che-do" value="<?php echo esc_attr( $f['che-do'] ); ?>"><?php endif; ?>
						<div class="qb-searchtop">
				<div><h2>Tìm kiếm &amp; lọc thông minh</h2><p>Kết hợp từ khóa với bộ lọc để tìm đúng câu hỏi cần học.</p></div>
				<button type="button" class="qb-b is-sm" data-qb-adv aria-expanded="<?php echo $advanced ? 'true' : 'false'; ?>" aria-controls="qb-adv">⚙ Bộ lọc nâng cao <span aria-hidden="true"><?php echo $advanced ? '−' : '＋'; ?></span></button>
			</div>
			<div class="qb-modes" role="group" aria-label="Chế độ xem">
				<?php foreach ( $modes as $k => $label ) : ?>
					<?php $href = '' === $k || $logged_in ? cvc_qb_url( $f, array( 'che-do' => $k, 'trang' => 1 ) ) : cvc_login_url( cvc_qb_url( $f, array( 'che-do' => $k, 'trang' => 1 ) ) ); ?>
					<a class="qb-mode<?php echo $k === $f['che-do'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $href ); ?>"<?php echo $k === $f['che-do'] ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?><?php echo '' !== $k && ! $logged_in ? ' <small>(đăng nhập)</small>' : ''; ?></a>
				<?php endforeach; ?>
			</div>
			<div class="qb-filters">
				<div class="qb-field is-q"><label for="qb-q">Từ khóa / mã câu hỏi</label><input id="qb-q" name="q" type="search" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="VD: nghĩa vụ công chức, thời hiệu kỷ luật, Q-1024…" maxlength="100"></div>
				<div class="qb-field"><label for="qb-chude">Lĩnh vực / chủ đề</label><?php $sel( 'qb-chude', 'chu-de', 'Tất cả chủ đề', (array) ( $d['topics'] ?? array() ), $f['chu-de'] ); ?></div>
				<div class="qb-field"><label for="qb-dokho">Độ khó</label><?php $opt( 'qb-dokho', 'do-kho', 'Tất cả độ khó', $diff_lbl, $f['do-kho'] ); ?></div>
				<div class="qb-field"><label for="qb-loai">Loại câu hỏi</label><?php $opt( 'qb-loai', 'loai', 'Tất cả loại', $fmt_lbl, $f['loai'] ); ?></div>
				<button type="submit" class="qb-b is-primary qb-go">Tìm kiếm ↗</button>
			</div>
			<div class="qb-adv" id="qb-adv"<?php echo $advanced ? '' : ' hidden'; ?>>
				<div class="qb-field"><label for="qb-vanban">Văn bản pháp luật</label><?php $sel( 'qb-vanban', 'van-ban', 'Tất cả văn bản', $docs, $f['van-ban'] ); ?></div>
				<div class="qb-field"><label for="qb-dieu">Điều khoản</label><?php $sel( 'qb-dieu', 'dieu', $f['van-ban'] ? 'Tất cả các Điều' : 'Chọn văn bản trước', (array) ( $d['articles'] ?? array() ), $f['dieu'], ! $f['van-ban'] ); ?></div>
				<div class="qb-field"><label for="qb-mon">Môn thi</label><?php $sel( 'qb-mon', 'mon', 'Tất cả môn thi', (array) ( $d['subjects'] ?? array() ), $f['mon'] ); ?></div>
				<div class="qb-field"><label for="qb-cancu">Căn cứ câu hỏi</label><?php $opt( 'qb-cancu', 'can-cu', 'Tất cả', array( 'co' => 'Có trích dẫn Điều khoản', 'chua' => 'Chưa gắn căn cứ' ), $f['can-cu'] ); ?></div>
			</div>
			<div class="qb-filterfoot">
				<span>Gợi ý: tìm theo <strong>cụm từ trong câu hỏi</strong> hoặc <strong>mã câu</strong> (Q-…); lọc theo văn bản rồi chọn từng Điều.</span>
				<span class="qb-ff-r"><?php if ( $filtered ) : ?><a class="qb-b is-sm" href="<?php echo esc_url( cvc_question_bank_url() ); ?>">↻ Xóa bộ lọc</a><?php endif; ?><b><?php echo $list_ok ? esc_html( 'Đang hiển thị ' . $fmt( $total ) . ' câu hỏi' ) : ''; ?></b></span>
			</div>
		</form>

		<div class="qb-grid">
			<section class="qb-results" aria-labelledby="qb-results-h">
				<div class="qb-resultsbar">
					<div>
						<h2 id="qb-results-h"><?php echo '' !== $f['che-do'] ? esc_html( $modes[ $f['che-do'] ] ) : 'Danh sách câu hỏi'; ?></h2>
						<p><?php echo $list_ok ? esc_html( $fmt( $total ) . ' câu' . ( $last > 1 ? ' · trang ' . $page . '/' . $last : '' ) . ' · bấm "Luyện câu này" để trả lời và xem căn cứ' ) : ''; ?></p>
					</div>
					<div class="qb-controls">
						<label class="screen-reader-text" for="qb-sort">Sắp xếp</label>
						<select id="qb-sort" name="sap-xep" form="qb-search" data-qb-auto>
							<?php foreach ( $sort_lbl as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $f['sap-xep'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="qb-view is-active" data-qb-view="card" aria-pressed="true" title="Dạng thẻ" aria-label="Dạng thẻ">▦</button>
						<button type="button" class="qb-view" data-qb-view="list" aria-pressed="false" title="Dạng danh sách" aria-label="Dạng danh sách">☷</button>
					</div>
				</div>

				<?php if ( ! $list_ok ) : ?>
					<div class="qb-empty"><strong>Chưa tải được danh sách câu hỏi</strong>Máy chủ đang bận, vui lòng tải lại trang sau ít phút.</div>
				<?php elseif ( ! $items ) : ?>
					<div class="qb-empty">
						<strong><?php echo 'da-luu' === $f['che-do'] ? 'Bạn chưa lưu câu hỏi nào' : 'Chưa tìm thấy câu hỏi phù hợp'; ?></strong>
						<?php echo 'da-luu' === $f['che-do'] ? 'Bấm "☆ Lưu" trên thẻ câu hỏi để thêm vào danh sách cần ôn lại.' : 'Thử bỏ bớt bộ lọc hoặc dùng từ khóa khác.'; ?><br>
						<a class="qb-b is-teal" href="<?php echo esc_url( cvc_question_bank_url() ); ?>">Xóa bộ lọc</a>
					</div>
				<?php else : ?>
					<div class="qb-list" data-qb-list>
						<?php foreach ( $items as $it ) : ?>
							<?php
							$qid   = (int) $it['id'];
							$st    = $st_lbl[ $it['status'] ?? '' ] ?? null;
							$dtag  = $diff_cls[ $it['difficulty'] ?? '' ] ?? '';
							$trk   = array_map( static fn ( $t ) => 'civil_servant' === $t ? 'Công chức' : 'Viên chức', (array) ( $it['tracks'] ?? array() ) );
							?>
							<article class="qb-q" id="q-<?php echo $qid; ?>" data-qid="<?php echo $qid; ?>">
								<div class="qb-qmain">
									<div class="qb-qhead">
										<div class="qb-meta">
											<span class="qb-tag"><?php echo esc_html( (string) $it['code'] ); ?></span>
											<?php if ( ! empty( $it['topic']['name'] ) ) : ?><a class="qb-tag is-t" href="<?php echo esc_url( cvc_qb_url( $f, array( 'chu-de' => (int) $it['topic']['id'], 'trang' => 1 ) ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $it['topic']['name'], 8 ) ); ?></a><?php endif; ?>
											<?php if ( ! empty( $it['difficulty_label'] ) ) : ?><span class="qb-tag <?php echo esc_attr( $dtag ); ?>"><?php echo esc_html( (string) $it['difficulty_label'] ); ?></span><?php endif; ?>
											<span class="qb-tag is-b"><?php echo esc_html( (string) $it['format_label'] ); ?></span>
											<?php foreach ( $trk as $t ) : ?><span class="qb-tag is-n"><?php echo esc_html( $t ); ?></span><?php endforeach; ?>
											<?php if ( $st ) : ?><span class="qb-tag <?php echo esc_attr( $st[1] ); ?>" data-qb-status><?php echo esc_html( $st[0] ); ?></span><?php endif; ?>
										</div>
										<button type="button" class="qb-bm<?php echo ! empty( $it['saved'] ) ? ' is-saved' : ''; ?>" data-qb-bm="<?php echo $qid; ?>" aria-pressed="<?php echo ! empty( $it['saved'] ) ? 'true' : 'false'; ?>"><?php echo ! empty( $it['saved'] ) ? '★ Đã lưu' : '☆ Lưu'; ?></button>
									</div>
									<h3><?php echo esc_html( (string) $it['text'] ); ?></h3>
									<p class="qb-qsub">
										<?php if ( ! empty( $it['article'] ) ) : ?>
											§ <?php echo esc_html( (string) $it['article']['label'] ); ?> — <a href="<?php echo esc_url( cvc_qb_url( $f, array( 'van-ban' => (int) $it['document']['id'], 'dieu' => 0, 'trang' => 1 ) ) ); ?>" title="<?php echo esc_attr( (string) $it['document']['title'] ); ?>"><?php echo esc_html( (string) $it['document']['short'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( (string) ( $it['subject'] ?? 'Kiến thức chung' ) ); ?> · câu hỏi chưa gắn Điều căn cứ
										<?php endif; ?>
									</p>
									<div class="qb-practice" data-qb-box hidden></div>
								</div>
								<div class="qb-qfoot">
									<div class="qb-qstats">
										<span>◉ <?php echo esc_html( $fmt( $it['practice_count'] ?? 0 ) ); ?> lượt luyện</span>
										<span><?php echo ! empty( $it['has_basis'] ) ? '§ Có trích dẫn căn cứ' : '§ Chưa gắn căn cứ'; ?></span>
										<?php if ( ! empty( $it['updated_at'] ) ) : ?><span>◷ Cập nhật <?php echo esc_html( wp_date( 'd/m/Y', strtotime( (string) $it['updated_at'] ) ) ); ?></span><?php endif; ?>
									</div>
									<div class="qb-qactions">
										<?php if ( ! empty( $it['article']['url'] ) ) : ?><a class="qb-b is-sm" href="<?php echo esc_url( home_url( (string) $it['article']['url'] ) ); ?>" target="_blank" rel="noopener">§ Xem căn cứ</a><?php endif; ?>
										<button type="button" class="qb-b is-sm is-primary" data-qb-try="<?php echo $qid; ?>" aria-expanded="false">Luyện câu này →</button>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
					<?php if ( $last > 1 ) : ?>
						<nav class="qb-pages" aria-label="Phân trang">
							<?php
							$win = array_unique( array_filter( array( 1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $last ), static fn ( $p ) => $p >= 1 && $p <= $last ) );
							sort( $win );
							?>
							<?php if ( $page > 1 ) : ?><a href="<?php echo esc_url( cvc_qb_url( $f, array( 'trang' => $page - 1 ) ) ); ?>" rel="prev">← Trước</a><?php else : ?><span class="is-off">← Trước</span><?php endif; ?>
							<?php $prev = 0; foreach ( $win as $p ) : ?>
								<?php if ( $p - $prev > 1 ) : ?><span class="is-gap">…</span><?php endif; ?>
								<?php if ( $p === $page ) : ?><span class="is-active" aria-current="page"><?php echo (int) $p; ?></span><?php else : ?><a href="<?php echo esc_url( cvc_qb_url( $f, array( 'trang' => $p ) ) ); ?>"><?php echo (int) $p; ?></a><?php endif; ?>
								<?php $prev = $p; endforeach; ?>
							<?php if ( $page < $last ) : ?><a href="<?php echo esc_url( cvc_qb_url( $f, array( 'trang' => $page + 1 ) ) ); ?>" rel="next">Sau →</a><?php else : ?><span class="is-off">Sau →</span><?php endif; ?>
						</nav>
					<?php endif; ?>
				<?php endif; ?>
			</section>

			<aside class="qb-side">
				<article class="qb-card" id="qb-practice">
					<div class="qb-chead"><h3>Luyện nhóm đang lọc</h3><span class="qb-tag is-t"><?php echo esc_html( $fmt( $total ) ); ?> câu</span></div>
					<div class="qb-cbody">
						<p class="qb-note">Lấy ngẫu nhiên từ <?php echo $filtered ? 'nhóm câu đang lọc' : 'toàn bộ ngân hàng'; ?><?php echo '' !== $f['che-do'] ? ' (' . esc_html( mb_strtolower( $modes[ $f['che-do'] ] ) ) . ')' : ''; ?>.</p>
						<?php cvc_qb_start_form( $f, cvc_qb_url( $f ), $total, 'is-side' ); ?>
						<?php if ( ! $logged_in ) : ?><p class="qb-note">Cần <a href="<?php echo esc_url( cvc_login_url( cvc_qb_url( $f ) ) ); ?>">đăng nhập</a> để lưu bài làm và kết quả. Miễn phí.</p><?php endif; ?>
					</div>
				</article>

				<article class="qb-card">
					<div class="qb-chead"><h3>Tiến độ ôn tập</h3><?php if ( $token ) : ?><a href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">Chi tiết →</a><?php endif; ?></div>
					<div class="qb-cbody">
						<?php if ( $token && $me ) : ?>
							<div class="qb-goal">
								<small>TUẦN NÀY</small>
								<strong><?php echo esc_html( $fmt( $me['this_week'] ?? 0 ) ); ?> <span>câu đã luyện</span></strong>
								<p>Tổng <?php echo esc_html( $fmt( $me['answered'] ?? 0 ) ); ?> câu đã làm<?php echo null !== ( $me['accuracy'] ?? null ) ? ' · đúng ' . (int) $me['accuracy'] . '%' : ''; ?>.</p>
								<?php if ( null !== ( $me['accuracy'] ?? null ) ) : ?><div class="qb-progress" role="img" aria-label="Tỷ lệ đúng <?php echo (int) $me['accuracy']; ?>%"><span style="width:<?php echo (int) $me['accuracy']; ?>%"></span></div><?php endif; ?>
							</div>
							<a class="qb-b is-primary is-block" href="<?php echo esc_url( cvc_qb_url( $f, array( 'che-do' => 'chua-lam', 'trang' => 1 ) ) ); ?>">Luyện câu chưa làm →</a>
						<?php elseif ( $token ) : ?>
							<p class="qb-note">Chưa tải được tiến độ, vui lòng tải lại trang.</p>
						<?php else : ?>
							<div class="qb-goal">
								<small>THEO DÕI TIẾN ĐỘ</small>
								<strong>Miễn phí</strong>
								<p>Đăng nhập để lưu kết quả từng câu, xem câu đã sai và chủ đề cần củng cố.</p>
							</div>
							<a class="qb-b is-primary is-block" href="<?php echo esc_url( cvc_login_url( cvc_qb_url( $f ) ) ); ?>">Đăng nhập →</a>
						<?php endif; ?>
					</div>
				</article>

				<?php if ( $token && ! empty( $me['weak_topics'] ) ) : ?>
					<article class="qb-card">
						<div class="qb-chead"><h3>Chủ đề cần củng cố</h3><span class="qb-tag is-r">đúng &lt; 70%</span></div>
						<div class="qb-cbody">
							<?php foreach ( (array) $me['weak_topics'] as $t ) : ?>
								<a class="qb-topic" href="<?php echo esc_url( cvc_question_bank_url( array( 'chu-de' => (int) $t['id'] ) ) ); ?>">
									<strong><span><?php echo esc_html( (string) $t['name'] ); ?></span><span><?php echo (int) $t['accuracy']; ?>%</span></strong>
									<small><?php echo (int) $t['questions']; ?> câu đã làm · bấm để luyện lại</small>
									<span class="qb-progress"><span style="width:<?php echo (int) $t['accuracy']; ?>%"></span></span>
								</a>
							<?php endforeach; ?>
						</div>
					</article>
				<?php endif; ?>

				<article class="qb-card">
					<div class="qb-chead"><h3>Văn bản có nhiều câu hỏi</h3><a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Tra cứu →</a></div>
					<div class="qb-cbody">
						<?php foreach ( array_slice( $docs, 0, 6 ) as $doc ) : ?>
							<a class="qb-law<?php echo (int) $doc['id'] === $f['van-ban'] ? ' is-on' : ''; ?>" href="<?php echo esc_url( cvc_qb_url( $f, array( 'van-ban' => (int) $doc['id'], 'dieu' => 0, 'trang' => 1 ) ) ); ?>"><b><?php echo esc_html( wp_trim_words( (string) $doc['name'], 14 ) ); ?></b><small><?php echo esc_html( $fmt( $doc['count'] ) ); ?> câu</small></a>
						<?php endforeach; ?>
						<p class="qb-hint">Mỗi câu ghi Điều làm căn cứ và trích dẫn nguyên văn đoạn quy định. Đối chiếu văn bản gốc và hiệu lực trước khi ghi nhớ số liệu cụ thể.</p>
					</div>
				</article>
			</aside>
		</div>

		<section class="qb-quick" aria-label="Liên kết nhanh">
			<a class="qb-tile" href="<?php echo esc_url( cvc_exams_mode_url( 'on-tap' ) ); ?>"><i aria-hidden="true">✓</i><span><strong>Luyện thi trắc nghiệm</strong><small>Làm đề, xem đáp án ngay sau mỗi câu</small></span></a>
			<a class="qb-tile" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>"><i aria-hidden="true">§</i><span><strong>Văn bản pháp luật</strong><small>Mở Điều khoản tham chiếu</small></span></a>
			<a class="qb-tile" href="<?php echo esc_url( cvc_recruitments_url() ); ?>"><i aria-hidden="true">▣</i><span><strong>Tin tuyển dụng</strong><small>Định hướng theo vị trí</small></span></a>
			<a class="qb-tile" href="<?php echo esc_url( cvc_exams_mode_url( 'thi-that' ) ); ?>"><i aria-hidden="true">◷</i><span><strong>Thi thử trắc nghiệm</strong><small>Tính giờ theo cấu trúc đề</small></span></a>
		</section>
	</div>
	<div class="qb-toast" role="status" aria-live="polite" data-qb-toast></div>
</main>
<?php
get_footer();
