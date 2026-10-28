<?php
/**
 * CÔNG VIÊN CHỨC — CHI TIẾT ĐỀ THI TRẮC NGHIỆM AI (Executive 3-Column Architecture)
 * URL: /thi-trac-nghiem/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_exam_slug' ) );
// 28/10/2026: slug cũ đã đổi (bỏ cụm "giả lập") -> chuyển hướng 301 để giữ SEO / liên kết cũ.
$cvc_old_slugs = array(
	'de-thi-gia-lap-vien-chuc-giao-duc-2026-chuan-luat-nha-giao-732025-nd-2592026-ma-de-002' => 'de-thi-trac-nghiem-vien-chuc-giao-duc-2026-chuan-luat-nha-giao-732025-nd-2592026-ma-de-002',
);
if ( isset( $cvc_old_slugs[ $slug ] ) ) {
	wp_safe_redirect( cvc_exam_url( $cvc_old_slugs[ $slug ] ), 301 );
	exit;
}

$service = new CVC_Exam_Service();
$result  = $service->find( $slug );

$exam     = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$exam     = is_array( $data ) ? $data : null;
	$is_found = null !== $exam;
}

// Slug không tồn tại -> 404 thật (trước đây hiện nội dung mẫu/fixture: soft-404, sai nội dung).
if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
}

/*
 * Vào thi ngay (18/10/2026): người đã đăng nhập mở đề là bắt đầu làm bài luôn - không còn bước
 * "xem trước câu hỏi". API tự mở lại lượt đang làm dở của đề nếu có (không tạo lượt trùng).
 * Khách / bot không có token -> vẫn thấy trang giới thiệu đề + nút đăng nhập (đăng nhập xong
 * quay lại đúng URL này và vào thi luôn).
 */
$start_error = '';
$token       = cvc_auth_token();
/*
 * 19/10/2026 - hai chế độ Thi thật / Ôn tập: đồng hồ thi thật do máy chủ đếm ngay khi bắt đầu, nên KHÔNG
 * tự mở lượt mới khi chỉ xem đề. Có lượt đang làm dở -> mở lại ngay; có ?che-do=thi-that|on-tap -> bắt đầu
 * đúng chế độ đó; còn lại hiện trang đề với 2 nút chọn chế độ.
 */
$start_mode = array( 'thi-that' => 'exam', 'on-tap' => 'practice' )[ sanitize_key( (string) ( $_GET['che-do'] ?? '' ) ) ] ?? '';
if ( $is_found && null !== $token && '' === $start_mode ) {
	nocache_headers();
	$open = ( new CVC_Api_Client( null, 8 ) )->get( '/api/my-exam-attempts', array( 'status' => 'in_progress', 'exam_id' => (int) ( $exam['id'] ?? 0 ), 'per_page' => 1 ), $token );
	$open_id = (int) ( $open['data']['data']['data'][0]['id'] ?? ( $open['data']['data'][0]['id'] ?? 0 ) );
	if ( $open['ok'] && $open_id > 0 ) {
		wp_safe_redirect( cvc_exam_attempt_url( $open_id ) );
		exit;
	}
	if ( 401 === (int) ( $open['status'] ?? 0 ) ) {
		$token = null;
	}
}
if ( $is_found && null !== $token && '' !== $start_mode && (int) ( $exam['questions_count'] ?? count( (array) ( $exam['questions'] ?? array() ) ) ) > 0 ) {
	nocache_headers();
	$started = ( new CVC_Exam_Attempt_Service() )->start( (int) ( $exam['id'] ?? 0 ), $start_mode, $token );
	$att_id  = (int) ( $started['data']['data']['id'] ?? 0 );
	if ( $started['ok'] && $att_id > 0 ) {
		wp_safe_redirect( cvc_exam_attempt_url( $att_id ) );
		exit;
	}
	cvc_verify_redirect_if_required( $started, remove_query_arg( 'che-do', (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );
	if ( 401 === (int) ( $started['status'] ?? 0 ) ) {
		$token = null; // token hết hạn -> hiện nút đăng nhập.
	} else {
		$start_error = cvc_api_error_message( $started );
	}
}

// Đề cá nhân hóa theo tin (RP-, có phí - 20/10/2026): quyền làm đề / gói bán.
$pe_acc = $is_found ? cvc_pe_access( $exam, $token ) : null;
if ( is_array( $pe_acc ) && null !== $token ) {
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'cvc: quyền làm đề / kết quả thanh toán theo người dùng' );
}

/*
 * 28/10/2026 - bỏ màn "Chọn chế độ làm bài":
 *  - Đến từ Luyện thi / Thi thử (?che-do=) mà chưa đăng nhập -> hộp đăng nhập gọn; đăng nhập xong quay lại
 *    đúng URL có ?che-do= nên vào thẳng bài làm. Trang này có nonce Google -> không cache.
 *  - Vào thẳng trang đề -> trang giới thiệu đề (SEO) với hàng nút Luyện thi / Thi thử / Tải PDF.
 */
$mode_key  = array_search( $start_mode, array( 'thi-that' => 'exam', 'on-tap' => 'practice' ), true );
$mode_key  = false === $mode_key ? '' : (string) $mode_key;
$scope     = $is_found && is_array( $exam['scope'] ?? null ) ? $exam['scope'] : array();
$rp_rec    = is_array( $scope['recruitment'] ?? null ) ? $scope['recruitment'] : null;
$total_q   = $is_found ? (int) ( $exam['questions_count'] ?? count( (array) ( $exam['questions'] ?? array() ) ) ) : 0;
$show_gate = $is_found && null === $token && '' !== $mode_key && $total_q > 0;
if ( $show_gate ) {
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'cvc: hộp đăng nhập vào thi (nonce Google)' );
}
if ( $is_found && '' !== $mode_key ) {
	cvc_seo_set_noindex(); // Biến thể ?che-do= (canonical vẫn là URL sạch).
}

cvc_seo_set_title( $is_found ? (string) $exam['title'] : 'Chi tiết đề thi trắc nghiệm' );

$breadcrumb_items = array( array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ) );
if ( $rp_rec ) {
	$breadcrumb_items[] = array( 'label' => 'Tuyển dụng', 'url' => cvc_recruitments_url() );
	$breadcrumb_items[] = array( 'label' => wp_trim_words( (string) $rp_rec['title'], 9 ), 'url' => cvc_recruitment_url( (string) $rp_rec['slug'] ) );
} elseif ( 'on-tap' === $mode_key ) {
	$breadcrumb_items[] = array( 'label' => 'Luyện thi trắc nghiệm', 'url' => cvc_exams_mode_url( 'on-tap' ) );
} elseif ( 'thi-that' === $mode_key ) {
	$breadcrumb_items[] = array( 'label' => 'Thi thử trắc nghiệm', 'url' => cvc_exams_mode_url( 'thi-that' ) );
} else {
	$breadcrumb_items[] = array( 'label' => 'Thi trắc nghiệm', 'url' => cvc_exams_url() );
}
$breadcrumb_items[] = array( 'label' => $is_found ? (string) $exam['title'] : 'Chi tiết đề thi' );

// Mô tả ngắn: bỏ danh sách "Văn bản: ..." (đã có mục Phạm vi kiến thức).
$short_desc = '';
if ( $is_found && ! empty( $exam['description'] ) ) {
	$short_desc = trim( (string) preg_replace( '/\s*Văn bản:.*$/us', '', wp_strip_all_tags( (string) $exam['description'] ) ) );
	$short_desc = wp_trim_words( $short_desc, 48 );
}

if ( $is_found ) {
	if ( '' !== $short_desc ) {
		cvc_seo_set_description( $short_desc );
	}
	cvc_seo_set_canonical( cvc_exam_url( $slug ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
	wp_enqueue_style( 'cvc-exam-detail', get_theme_file_uri( '/assets/css/exam-detail.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/exam-detail.css' ) ) );
	if ( $show_gate ) {
		wp_enqueue_style( 'cvc-auth', get_theme_file_uri( '/assets/css/auth.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/auth.css' ) ) );
	} elseif ( $total_q > 0 ) {
		add_filter( 'body_class', static fn ( $c ) => array_merge( (array) $c, array( 'xd-has-sticky' ) ) );
	}
}

get_header();
?>

<main id="main" class="dz-main">

	<div class="dz-wrap space-y-6">

		<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

		<?php if ( ! $is_found ) : ?>
			<div class="dz-panel text-center space-y-4">
				<h1 class="text-2xl font-black text-ink">Không tìm thấy đề thi</h1>
				<p class="text-xs text-ink-muted">Đề thi bạn tìm không tồn tại hoặc đã được gỡ bỏ.</p>
				<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="inline-block px-5 py-2.5 bg-acc text-white font-black text-xs rounded-xl">
					&larr; Xem tất cả đề thi
				</a>
			</div>
		<?php else : ?>
			<?php
			$duration   = (int) ( $exam['duration_minutes'] ?? 0 );
			$pass_score = null;
			if ( isset( $exam['passing_score'] ) && is_numeric( $exam['passing_score'] ) && (float) $exam['passing_score'] > 0 ) {
				$pass_score = rtrim( rtrim( number_format( (float) $exam['passing_score'], 2, ',', '.' ), '0' ), ',' );
				if ( isset( $exam['total_score'] ) && is_numeric( $exam['total_score'] ) && (float) $exam['total_score'] > 0 ) {
					$pass_score .= '/' . rtrim( rtrim( number_format( (float) $exam['total_score'], 2, ',', '.' ), '0' ), ',' ) . ' điểm';
				}
			}
			$exam_subject_names = array();
			foreach ( (array) ( $exam['exam_subjects'] ?? array() ) as $subject ) {
				if ( is_array( $subject ) && ! empty( $subject['name'] ) ) {
					$exam_subject_names[] = (string) $subject['name'];
				}
			}
			$stats     = array_filter( array( $total_q . ' câu', $duration > 0 ? $duration . ' phút' : null, null !== $pass_score ? 'Điểm đạt ' . $pass_score : null ) );
			$exam_id   = (int) ( $exam['id'] ?? 0 );
			$clean_url = cvc_exam_url( $slug );
			$mode_url  = static fn ( string $m ): string => add_query_arg( 'che-do', $m, $clean_url );
			$locked    = is_array( $pe_acc ) && ! empty( $pe_acc['locked'] );
			$can_pdf   = 'published' === ( $exam['status'] ?? 'published' );
			?>
			<?php cvc_render_track_marker( 'exam_viewed', 'exam', $exam_id ); ?>

			<header class="dz-pagehead">
				<p class="dz-kicker">Đề thi trắc nghiệm<?php echo ! empty( $exam['code'] ) ? ' · Mã đề ' . esc_html( (string) $exam['code'] ) : ''; ?></p>
				<h1><?php echo esc_html( $exam['title'] ); ?></h1>
				<?php if ( '' !== $short_desc && ! $show_gate ) : ?><p class="xd-desc"><?php echo esc_html( $short_desc ); ?></p><?php endif; ?>
				<p class="dz-pagehead-stats"><?php echo esc_html( implode( ' · ', array_merge( $stats, $exam_subject_names && ! $show_gate ? array( implode( ', ', $exam_subject_names ) ) : array() ) ) ); ?></p>
				<span class="dz-rule" aria-hidden="true"></span>
			</header>

			<?php if ( $show_gate ) : ?>
				<?php $back_rel = wp_make_link_relative( $mode_url( $mode_key ) ); ?>
				<section class="xd-gate" aria-labelledby="xd-gate-h">
					<span class="xd-chip is-<?php echo esc_attr( $mode_key ); ?>"><?php echo 'on-tap' === $mode_key ? '✎ Luyện thi' : '◷ Thi thử'; ?></span>
					<h2 id="xd-gate-h"><?php echo 'on-tap' === $mode_key ? 'Đăng nhập để bắt đầu luyện đề này' : 'Đăng nhập để bắt đầu thi thử đề này'; ?></h2>
					<p class="xd-gate-sub"><?php echo esc_html( 'on-tap' === $mode_key ? 'Không tính giờ; trả lời xong mỗi câu là thấy ngay đáp án, giải thích và căn cứ pháp luật.' : 'Tính giờ như phòng thi' . ( $duration > 0 ? ' (' . $duration . ' phút)' : '' ) . '; đáp án và giải thích hiện sau khi nộp bài.' ); ?></p>
					<div class="xd-gate-google"><?php if ( function_exists( 'cvc_google_button' ) ) { cvc_google_button( $back_rel, 'signin' ); } ?></div>
					<div class="xd-gate-links">
						<a class="xd-btn is-primary" href="<?php echo esc_url( cvc_login_url( $back_rel ) ); ?>" rel="nofollow">Đăng nhập bằng email</a>
						<a class="xd-btn" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( $back_rel ), cvc_register_url() ) ); ?>" rel="nofollow">Tạo tài khoản miễn phí</a>
					</div>
					<p class="xd-gate-note"><?php echo esc_html( $locked ? 'Đăng nhập xong vào thẳng bài làm. Đề riêng theo tin: làm thử ' . (int) ( $pe_acc['preview'] ?? 10 ) . ' câu đầu miễn phí.' : 'Đăng nhập xong vào thẳng bài làm. Miễn phí; tài khoản dùng để lưu kết quả và xem giải thích từng câu.' ); ?></p>
					<a class="xd-gate-back" href="<?php echo esc_url( $clean_url ); ?>">Xem thông tin đề trước →</a>
				</section>
			<?php else : ?>

				<?php if ( '' !== $start_error ) : ?><p class="xd-alert is-error"><?php echo esc_html( $start_error ); ?></p><?php endif; ?>
				<?php if ( cvc_email_unverified() ) : ?>
					<p class="xd-alert">✉ Tài khoản cần <b>xác minh email</b> trước khi làm bài. <a href="<?php echo esc_url( cvc_verify_email_url( cvc_verify_return_path( (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) ) ); ?>">Xác minh ngay →</a></p>
				<?php endif; ?>

				<?php if ( 0 === $total_q ) : ?>
					<?php cvc_render_empty_state( 'Đề thi này chưa có câu hỏi được công bố.' ); ?>
				<?php else : ?>
					<section class="xd-actions" aria-label="Làm đề">
						<a class="xd-act is-primary" href="<?php echo esc_url( $mode_url( 'on-tap' ) ); ?>" rel="nofollow"><b>✎ Luyện thi đề này</b><small>Không tính giờ, xem đáp án và căn cứ sau mỗi câu</small></a>
						<a class="xd-act is-navy" href="<?php echo esc_url( $mode_url( 'thi-that' ) ); ?>" rel="nofollow"><b>◷ Thi thử đề này</b><small>Tính giờ<?php echo $duration > 0 ? ' ' . (int) $duration . ' phút' : ''; ?>, nộp bài xem điểm</small></a>
						<div class="xd-act-side">
							<?php if ( $can_pdf ) { echo cvc_pdf_button( 'exam', $exam_id, array( 'label' => 'Tải PDF có đáp án', 'variant' => 'dap-an', 'class' => 'xd-pdf' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php cvc_render_bookmark_button( 'exam', $exam_id ); ?>
						</div>
						<?php if ( $locked ) : ?><p class="xd-act-note">Đề riêng theo tin: chưa mua thì làm thử <?php echo (int) ( $pe_acc['preview'] ?? 10 ); ?> câu đầu miễn phí.</p><?php endif; ?>
					</section>
				<?php endif; ?>

				<div class="xd-grid">
					<div class="xd-main">
						<?php
						$docs     = array_values( array_filter( (array) ( $scope['documents'] ?? array() ), 'is_array' ) );
						$sections = array_values( array_filter( (array) ( $scope['sections'] ?? array() ), 'is_array' ) );
						$doc_by   = array();
						foreach ( $docs as $d ) {
							$doc_by[ (int) $d['id'] ] = $d;
						}
						$chip = static function ( array $d ): string {
							$label = '' !== (string) $d['number'] ? (string) $d['number'] : wp_trim_words( (string) $d['title'], 6 );
							$inner = esc_html( $label ) . ' <small>' . (int) $d['count'] . ' câu</small>';
							return ! empty( $d['slug'] )
								? '<a class="xd-doc" href="' . esc_url( cvc_legal_document_url( (string) $d['slug'] ) ) . '" title="' . esc_attr( (string) $d['title'] ) . '">' . $inner . '</a>'
								: '<span class="xd-doc" title="' . esc_attr( (string) $d['title'] ) . '">' . $inner . '</span>';
						};
						?>
						<?php if ( $docs ) : ?>
							<section class="dz-panel xd-scope" aria-labelledby="xd-scope-h">
								<h2 id="xd-scope-h">Phạm vi kiến thức</h2>
								<?php if ( $sections ) : ?>
									<?php
									$wsum = max( 1, array_sum( array_column( $sections, 'weight' ) ) );
									$used = array();
									foreach ( $sections as $sec ) :
										$in = array();
										foreach ( (array) $sec['doc_ids'] as $id ) {
											if ( isset( $doc_by[ (int) $id ] ) && ! isset( $used[ (int) $id ] ) ) {
												$in[]              = $doc_by[ (int) $id ];
												$used[ (int) $id ] = true;
											}
										}
										if ( ! $in ) {
											continue;
										}
										usort( $in, static fn ( $a, $b ) => $b['count'] <=> $a['count'] );
										?>
										<div class="xd-sec">
											<h3><?php echo esc_html( (string) $sec['label'] ); ?> <span><?php echo (int) round( (int) $sec['weight'] / $wsum * 100 ); ?>%</span></h3>
											<p class="xd-docs"><?php echo implode( '', array_map( $chip, $in ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
										</div>
									<?php endforeach; ?>
									<?php $rest = array_values( array_filter( $docs, static fn ( $d ) => ! isset( $used[ (int) $d['id'] ] ) ) ); ?>
									<?php if ( $rest ) : ?>
										<div class="xd-sec"><h3>Văn bản khác</h3><p class="xd-docs"><?php echo implode( '', array_map( $chip, $rest ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p></div>
									<?php endif; ?>
								<?php else : ?>
									<p class="xd-docs"><?php echo implode( '', array_map( $chip, $docs ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
								<?php endif; ?>
								<p class="xd-muted">Số câu theo từng văn bản trong đề; bấm để đọc văn bản gốc.</p>
							</section>
						<?php endif; ?>

						<?php if ( $total_q > 0 ) : ?>
							<section class="dz-panel xd-how" aria-labelledby="xd-how-h">
								<h2 id="xd-how-h">Cách làm bài</h2>
								<ul>
									<li><b>Luyện thi:</b> không tính giờ; trả lời xong từng câu là thấy đáp án đúng, giải thích và Điều luật làm căn cứ.</li>
									<li><b>Thi thử:</b> đồng hồ<?php echo $duration > 0 ? ' ' . (int) $duration . ' phút' : ''; ?> do máy chủ kiểm soát, hết giờ tự nộp; đáp án và giải thích hiện sau khi nộp.</li>
									<li>Mỗi đáp án được lưu ngay khi chọn; mất mạng hay tải lại trang vẫn khôi phục được bài làm.</li>
									<li>Nộp bài xong có trang Kết quả &amp; Phân tích: điểm, câu sai, căn cứ văn bản và bài luyện đề xuất.</li>
								</ul>
							</section>
						<?php endif; ?>
					</div>

					<aside class="xd-side">
						<?php if ( is_array( $pe_acc ) ) { cvc_pe_render_paybox( $exam, $pe_acc, $token, $clean_url ); } ?>

						<?php if ( $rp_rec ) : ?>
							<div class="dz-card xd-rec">
								<h3 class="dz-sideh2">Đề riêng của tin tuyển dụng</h3>
								<a href="<?php echo esc_url( cvc_recruitment_url( (string) $rp_rec['slug'] ) ); ?>"><?php echo esc_html( (string) $rp_rec['title'] ); ?></a>
								<?php if ( ! empty( $rp_rec['group_label'] ) ) : ?><p>Nhóm vị trí: <b><?php echo esc_html( (string) $rp_rec['group_label'] ); ?></b></p><?php endif; ?>
								<a class="xd-link" href="<?php echo esc_url( cvc_recruitment_url( (string) $rp_rec['slug'] ) . '#de-rieng' ); ?>">Các đề khác của tin →</a>
							</div>
						<?php endif; ?>

						<div class="dz-card xd-specs">
							<h3 class="dz-sideh2">Thông số đề thi</h3>
							<dl>
								<div><dt>Thời gian</dt><dd><?php echo $duration > 0 ? esc_html( $duration . ' phút' ) : 'Không giới hạn'; ?></dd></div>
								<div><dt>Số câu</dt><dd><?php echo (int) $total_q; ?> câu</dd></div>
								<?php if ( null !== $pass_score ) : ?><div><dt>Điểm đạt</dt><dd><?php echo esc_html( $pass_score ); ?></dd></div><?php endif; ?>
								<div><dt>Hình thức</dt><dd>Trắc nghiệm, chấm tự động</dd></div>
								<?php if ( $exam_subject_names ) : ?><div><dt>Môn thi</dt><dd><?php echo esc_html( implode( ', ', $exam_subject_names ) ); ?></dd></div><?php endif; ?>
							</dl>
						</div>

						<?php if ( function_exists( 'cvc_ci_render_exam_jobs' ) && ! $rp_rec ) { cvc_ci_render_exam_jobs( $exam_id ); } ?>
						<?php if ( function_exists( 'cvc_render_leaderboard_mini' ) && $can_pdf ) { cvc_render_leaderboard_mini( array( 'de' => $exam_id ), 'Top thí sinh đề này' ); } ?>
					</aside>
				</div>

				<?php if ( $total_q > 0 ) : ?>
					<nav class="xd-sticky" aria-label="Làm đề nhanh">
						<a class="xd-act is-primary" href="<?php echo esc_url( $mode_url( 'on-tap' ) ); ?>" rel="nofollow"><b>✎ Luyện thi</b></a>
						<a class="xd-act is-navy" href="<?php echo esc_url( $mode_url( 'thi-that' ) ); ?>" rel="nofollow"><b>◷ Thi thử</b></a>
					</nav>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
