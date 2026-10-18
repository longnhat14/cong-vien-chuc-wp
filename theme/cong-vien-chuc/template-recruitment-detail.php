<?php
/**
 * Chi tiết tuyển dụng v4 (18/10/2026) - thiết kế congvienchuc-chi-tiet-tuyen-dung-v4.
 *
 *  - /tuyen-dung/{slug}/          chế độ THÔNG BÁO (cvc_page=recruitment-detail)
 *  - /tuyen-dung/vi-tri/{slug}/   chế độ VỊ TRÍ (cvc_page=recruitment-position): 1 trang / 1 vị trí,
 *                                 cùng tham chiếu văn bản gốc, tệp đính kèm và chữ trích xuất của thông báo.
 *
 * CHỈ dữ liệu thật từ API (GET /api/recruitments/{slug}, /api/recruitment-positions/{slug}).
 * Điểm xác minh: chỉ điểm / mức / nhãn / cảnh báo công khai; phân rã tiêu chí chỉ ở backend quản trị
 * (GET /api/admin/recruitments/{id}/verification, `php artisan recruitment:verify --detail`).
 * Header / footer: get_header() / get_footer() (khung chung của site).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rd_mode  = 'recruitment-position' === (string) get_query_var( 'cvc_page' ) ? 'position' : 'notice';
$slug     = sanitize_text_field( (string) get_query_var( 'cvc_recruitment_slug' ) );
$pos_id   = 0;
$pos_slug = '';

if ( 'position' === $rd_mode ) {
	$pos_slug = sanitize_title( (string) get_query_var( 'cvc_position_slug' ) );
	$pr       = ( new CVC_Api_Client( null, 10 ) )->get( '/api/recruitment-positions/' . rawurlencode( $pos_slug ) );
	if ( ! $pr['ok'] && 404 !== (int) ( $pr['status'] ?? 0 ) ) {
		$pr = ( new CVC_Api_Client( null, 20 ) )->get( '/api/recruitment-positions/' . rawurlencode( $pos_slug ) );
	}
	if ( ! $pr['ok'] ) {
		if ( 404 === (int) ( $pr['status'] ?? 0 ) ) {
			cvc_rd_render_state( 404, 'Không tìm thấy vị trí tuyển dụng', 'Vị trí này không còn trên hệ thống hoặc đường dẫn không đúng. Bạn có thể tìm vị trí khác theo tên, tỉnh/thành hoặc nhóm nghề.' );
		} else {
			cvc_rd_render_state( 503, 'Tạm thời chưa tải được vị trí tuyển dụng', 'Hệ thống đang bận, chưa tải được trang này. Vui lòng tải lại sau ít phút.' );
		}
		return;
	}
	$pos_id = (int) ( $pr['data']['data']['position_id'] ?? 0 );
	$slug   = sanitize_title( (string) ( $pr['data']['data']['recruitment_slug'] ?? '' ) );
}

$result = ( new CVC_Recruitment_Service() )->find( $slug );
// API chậm / lỗi tạm thời (không phải 404) -> thử lại 1 lần, vẫn lỗi thì 503 (không lưu đệm) thay vì 404.
if ( ! $result['ok'] && 404 !== (int) ( $result['status'] ?? 0 ) ) {
	$result = ( new CVC_Api_Client( null, 20 ) )->get( '/api/recruitments/' . rawurlencode( $slug ) );
}
$recruitment = $result['ok'] && is_array( $result['data']['data'] ?? null ) ? $result['data']['data'] : null;

// Đường dẫn cũ (tin đã sửa tiêu đề / đường dẫn) -> 301 sang đường dẫn mới.
if ( 'notice' === $rd_mode && null === $recruitment && ! empty( $result['data']['redirect_slug'] ) && is_string( $result['data']['redirect_slug'] ) ) {
	wp_safe_redirect( cvc_recruitment_url( sanitize_title( $result['data']['redirect_slug'] ) ), 301 );
	exit;
}
if ( null === $recruitment && 404 !== (int) ( $result['status'] ?? 0 ) ) {
	cvc_rd_render_state( 503, 'Tạm thời chưa tải được tin tuyển dụng', 'Hệ thống đang bận, chưa tải được tin tuyển dụng này. Vui lòng tải lại trang sau ít phút.' );
	return;
}
if ( null === $recruitment ) {
	cvc_rd_render_state( 404, 'Không tìm thấy tin tuyển dụng', 'Tin tuyển dụng này có thể đã được gỡ hoặc đường dẫn không đúng.' );
	return;
}

$positions = array_values( array_filter( (array) ( $recruitment['positions'] ?? array() ), 'is_array' ) );
$position  = null;
if ( 'position' === $rd_mode ) {
	foreach ( $positions as $p ) {
		if ( (int) ( $p['id'] ?? 0 ) === $pos_id ) {
			$position = $p;
			break;
		}
	}
	if ( null === $position ) {
		cvc_rd_render_state( 404, 'Không tìm thấy vị trí tuyển dụng', 'Vị trí này không còn trong thông báo tuyển dụng hoặc đường dẫn không đúng.' );
		return;
	}
}

/* ------------------------------ Dữ liệu ------------------------------ */
$rid          = (int) ( $recruitment['id'] ?? 0 );
$title        = (string) ( $recruitment['title'] ?? '' );
$summary      = trim( (string) ( $recruitment['summary'] ?? '' ) );
$agency       = is_array( $recruitment['agency'] ?? null ) ? $recruitment['agency'] : array();
$agency_name  = (string) ( $agency['name'] ?? '' );
$province     = is_array( $recruitment['province'] ?? null ) ? $recruitment['province'] : array();
$admin_unit   = is_array( $recruitment['admin_unit'] ?? null ) ? $recruitment['admin_unit'] : array();
$dates        = is_array( $recruitment['dates'] ?? null ) ? $recruitment['dates'] : array();
$exams        = (array) ( $recruitment['exams'] ?? array() );
$courses      = (array) ( $recruitment['courses'] ?? array() );
$dossier      = is_array( $recruitment['dossier'] ?? null ) ? $recruitment['dossier'] : array();
$subjects     = (array) ( $dossier['exam_subjects'] ?? array() );
$legal_docs   = (array) ( $dossier['legal_documents'] ?? array() );
$is_open      = ! empty( $recruitment['is_open'] );
$status       = (string) ( $recruitment['status'] ?? '' );
$source_url   = (string) ( $recruitment['source_url'] ?? '' );
$source_info  = is_array( $recruitment['source'] ?? null ) ? $recruitment['source'] : array();
$collected    = array_key_exists( 'official', $source_info ) && false === $source_info['official'];
$attachments  = array_values( array_filter( (array) ( $recruitment['attachments'] ?? array() ), 'is_array' ) );
$total        = (int) ( $recruitment['total_positions'] ?? 0 );
$stage        = (string) ( $recruitment['stage'] ?? '' );
$stage_label  = (string) ( $recruitment['stage_label'] ?? '' );
$doc_number   = (string) ( $recruitment['doc_number'] ?? '' );
$round_docs   = (array) ( $recruitment['round_documents'] ?? array() );
$round_events = (array) ( $recruitment['events'] ?? array() );
$method_label = (string) ( $recruitment['method_summary_label'] ?? '' );
$changes      = (array) ( $recruitment['changes'] ?? array() );
$study_plan   = is_array( $recruitment['study_plan'] ?? null ) ? $recruitment['study_plan'] : array();
$evidence     = is_array( $recruitment['evidence'] ?? null ) ? $recruitment['evidence'] : array();
$legal_basis  = (array) ( $recruitment['legal_basis'] ?? array() );
$details      = is_array( $recruitment['details'] ?? null ) ? $recruitment['details'] : array();
$full_text    = is_array( $recruitment['full_text'] ?? null ) ? $recruitment['full_text'] : array();
$ft_docs      = array();
foreach ( (array) ( $full_text['documents'] ?? array() ) as $d ) {
	if ( ! empty( $d['id'] ) ) {
		$ft_docs[ (int) $d['id'] ] = (string) ( $d['text'] ?? '' );
	}
}
$ver       = is_array( $recruitment['verification'] ?? null ) ? $recruitment['verification'] : null;
$vv        = cvc_rd_verify_view( $ver );
$warnings  = array_values( array_filter( array_map( 'cvc_rd_warning_text', array_map( 'strval', (array) ( $ver['warnings'] ?? array() ) ) ) ) );
$type_lbl  = ! empty( $recruitment['recruitment_type'] ) && 'other' !== $recruitment['recruitment_type'] ? cvc_recruitment_type_label( (string) $recruitment['recruitment_type'] ) : '';
if ( ! empty( $study_plan ) ) {
	$exams = array_values( array_filter( $exams, fn ( $e ) => 0 !== strpos( (string) ( $e['code'] ?? '' ), 'RS-' ) ) );
}
$in_progress = ! $is_open && in_array( $stage, array( 'examining', 'result' ), true );
if ( 0 === $total ) {
	$total = array_sum( array_map( fn ( $p ) => (int) ( $p['quantity'] ?? 0 ), $positions ) );
}
$location  = implode( ', ', array_filter( array( $admin_unit['name'] ?? null, $province['name'] ?? null ) ) );
$deadline  = (string) ( $dates['application_deadline'] ?? '' );
$days_left = '' !== $deadline ? cvc_rd_days_left( $deadline ) : null;
$dl_unver  = in_array( 'deadline_unverified', (array) ( $ver['warnings'] ?? array() ), true );
$countdown = $is_open && '' !== $deadline && null !== $days_left && $days_left >= 0 && ( $ver ? ! empty( $ver['allow_deadline_reminder'] ) : ! $collected );
$multi     = count( $positions ) > 1;
$here_url  = 'position' === $rd_mode ? cvc_position_url( (string) $position['slug'] ) : cvc_recruitment_url( $slug );
$rec_url   = cvc_recruitment_url( $slug );
$has_pdf   = (bool) array_filter( $attachments, fn ( $a ) => 'pdf' === strtolower( (string) ( $a['extension'] ?? '' ) ) );
$can_cq    = $is_open || $in_progress;

// Vị trí đang xem: dòng "tiêu chuẩn riêng" của thông báo có nhắc tới vị trí.
$pos_req = array();
if ( $position ) {
	foreach ( (array) ( $details['specific_requirements'] ?? array() ) as $line ) {
		if ( cvc_rd_mentions( (string) $line, $position ) ) {
			$pos_req[] = (string) $line;
		}
	}
}
$p_name   = $position ? (string) ( $position['name'] ?? '' ) : '';
$p_method = $position ? (string) ( $position['recruitment_method_label'] ?? '' ) : '';
$p_type   = $position ? (string) ( $position['employment_type_label'] ?? '' ) : '';

/* ------------------------------ SEO ------------------------------ */
$short_title      = wp_trim_words( $title, 9 );
$breadcrumb_items = array(
	array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
	array( 'label' => 'Tuyển dụng', 'url' => cvc_recruitments_url() ),
);
if ( 'position' === $rd_mode ) {
	$breadcrumb_items[] = array( 'label' => 'Theo vị trí', 'url' => cvc_positions_url() );
	$breadcrumb_items[] = array( 'label' => wp_trim_words( $p_name, 10 ) );

	$seo_title = 'Tuyển dụng ' . $p_name . ( '' !== $agency_name ? ' – ' . $agency_name : '' );
	$seo_desc  = implode(
		' ',
		array_filter(
			array(
				( '' !== $agency_name ? $agency_name : 'Cơ quan' ) . ' tuyển ' . ( ! empty( $position['quantity'] ) ? (int) $position['quantity'] . ' chỉ tiêu ' : '' ) . 'vị trí ' . $p_name . ( '' !== $location ? ' tại ' . $location : '' ) . '.',
				! empty( $position['education_level'] ) ? 'Trình độ: ' . cvc_rd_clip( (string) $position['education_level'], 60 ) . '.' : '',
				'' !== $deadline ? 'Hạn nộp hồ sơ ' . cvc_format_date_vn( $deadline ) . '.' : '',
				'' !== $doc_number ? 'Theo thông báo số ' . $doc_number . '.' : '',
			)
		)
	);
	cvc_seo_set_title( $seo_title );
	cvc_seo_set_description( cvc_seo_trim( $seo_desc, 160 ) );
	// Thông báo chỉ có 1 vị trí: trang vị trí trùng nội dung -> canonical về trang thông báo.
	cvc_seo_set_canonical( $multi ? $here_url : $rec_url );
	if ( $multi ) {
		$jp = cvc_rd_position_jsonld( $recruitment, $position, $here_url );
		if ( null !== $jp ) {
			cvc_seo_add_json_ld( $jp );
		}
	}
} else {
	$breadcrumb_items[] = array( 'label' => $short_title );
	cvc_seo_set_title( $title );
	$seo_desc = wp_trim_words( '' !== $summary ? $summary : $title, 24 );
	if ( '' !== $stage_label && 'accepting' !== $stage ) {
		$seo_desc = $stage_label . ' - ' . $seo_desc;
	}
	if ( $multi ) {
		$seo_desc .= ' ' . count( $positions ) . ' vị trí, ' . $total . ' chỉ tiêu.';
	}
	cvc_seo_set_description( $seo_desc );
	cvc_seo_set_canonical( $rec_url );
	if ( $multi ) {
		// Nhiều vị trí: mỗi vị trí là 1 JobPosting ở trang riêng; trang thông báo khai báo danh sách.
		$list = array();
		foreach ( $positions as $i => $p ) {
			if ( ! empty( $p['slug'] ) ) {
				$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => cvc_position_url( (string) $p['slug'] ), 'name' => (string) ( $p['name'] ?? '' ) );
			}
		}
		if ( $list ) {
			cvc_seo_add_json_ld( array( '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Vị trí tuyển dụng: ' . $title, 'numberOfItems' => count( $list ), 'itemListElement' => $list ) );
		}
	} else {
		$job_posting = cvc_build_recruitment_job_posting_jsonld( $recruitment );
		if ( null !== $job_posting ) {
			cvc_seo_add_json_ld( $job_posting );
		}
	}
}
cvc_seo_set_og( array( 'type' => 'website' ) );
cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );

$money = static fn ( $v ): string => number_format( (float) $v, 0, ',', '.' ) . 'đ';
$fmt_n = static fn ( int $n ): string => number_format( $n, 0, ',', '.' );
$cq_btn = static function ( string $label, string $class ) use ( $rid, $here_url ): string {
	return function_exists( 'cvc_cq_go_button' ) ? cvc_cq_go_button( 'recruitment', $rid, $label, $class, $here_url ) : '';
};

// Trạng thái nhận hồ sơ.
if ( $is_open ) {
	$st_label = 'Đang nhận hồ sơ' . ( null !== $days_left && $days_left <= 7 && ! $dl_unver ? ( 0 === $days_left ? ' · hạn cuối hôm nay' : ' · còn ' . $days_left . ' ngày' ) : '' );
	$st_class = null !== $days_left && $days_left <= 7 ? 'is-warn' : 'is-ok';
} elseif ( $in_progress ) {
	$st_label = '' !== $stage_label ? $stage_label : 'Đang tổ chức tuyển dụng';
	$st_class = 'is-mid';
} else {
	$st_label = 'Đã hết hạn nhận hồ sơ';
	$st_class = 'is-off';
}

get_header();
?>
<main id="main" class="rd<?php echo 'position' === $rd_mode ? ' is-position' : ''; ?>" data-rd-slug="<?php echo esc_attr( $slug ); ?>">
<?php cvc_render_track_marker( 'recruitment_viewed', 'recruitment', $rid ); ?>
<div class="rd-wrap">

	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<!-- ============ HERO ============ -->
	<header class="rd-hero">
		<div class="rd-hero-main">
			<p class="rd-hero-eyebrow"><span class="rd-dot <?php echo esc_attr( $st_class ); ?>" aria-hidden="true"></span>
				<?php
				if ( 'position' === $rd_mode ) {
					echo esc_html( 'Cơ hội tuyển dụng' . ( ! empty( $position['code'] ) ? ' · Vị trí ' . $position['code'] : '' ) . ( '' !== $p_type ? ' · ' . $p_type : '' ) );
				} else {
					echo esc_html( 'Thông báo tuyển dụng' . ( '' !== $type_lbl ? ' · ' . $type_lbl : '' ) . ( $multi ? ' · ' . count( $positions ) . ' vị trí' : '' ) );
				}
				?>
			</p>
			<h1><?php echo esc_html( 'position' === $rd_mode ? 'Tuyển dụng ' . $p_name : $title ); ?></h1>
			<p class="rd-hero-org">
				<span class="rd-hero-orgicon" aria-hidden="true">▥</span>
				<span><?php echo esc_html( '' !== $agency_name ? $agency_name : 'Cơ quan tuyển dụng' ); ?><?php echo '' !== $location ? ' · ' . esc_html( $location ) : ''; ?></span>
			</p>
			<?php if ( 'position' === $rd_mode ) : ?>
				<p class="rd-hero-parent">Thuộc thông báo: <a href="<?php echo esc_url( $rec_url ); ?>"><?php echo esc_html( $title ); ?></a></p>
			<?php endif; ?>
			<p class="rd-chips">
				<span class="rd-chip <?php echo esc_attr( $st_class ); ?>"><?php echo esc_html( $st_label ); ?></span>
				<?php if ( $collected ) : ?>
					<span class="rd-chip is-warn">Nguồn sưu tầm · cần xác nhận với cơ quan</span>
				<?php elseif ( ! empty( $source_info['official'] ) ) : ?>
					<span class="rd-chip">Nguồn chính thức của cơ quan</span>
				<?php endif; ?>
				<?php if ( $has_pdf ) : ?><span class="rd-chip">Có văn bản gốc PDF</span><?php endif; ?>
				<?php if ( '' !== $doc_number ) : ?><span class="rd-chip">Số: <?php echo esc_html( $doc_number ); ?></span><?php endif; ?>
			</p>
		</div>
		<div class="rd-hero-side">
			<?php if ( $can_cq ) : ?>
				<?php echo $cq_btn( '＋ Chinh phục kỳ thi này', 'rd-btn is-gold is-lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
			<?php if ( '' !== $source_url ) : ?>
				<a class="rd-btn is-white is-lg" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">↗ Đọc thông báo gốc</a>
			<?php elseif ( $attachments ) : ?>
				<a class="rd-btn is-white is-lg" href="#tai-lieu">▤ Xem văn bản gốc</a>
			<?php endif; ?>
			<?php if ( cvc_is_logged_in() && $rid > 0 ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'cvc_bookmark_add' ); ?>
					<input type="hidden" name="action" value="cvc_bookmark_add"><input type="hidden" name="type" value="recruitment">
					<input type="hidden" name="id" value="<?php echo (int) $rid; ?>"><input type="hidden" name="redirect_to" value="<?php echo esc_attr( $here_url ); ?>">
					<button type="submit" class="rd-btn is-ghost">☆ Lưu tin vào đánh dấu</button>
				</form>
			<?php endif; ?>
			<p class="rd-hero-note"><?php echo $can_cq ? 'Lưu mục tiêu để kết nối kế hoạch ôn thi cá nhân.' : 'Đợt tuyển dụng đã kết thúc — trang được giữ lại để tham khảo.'; ?></p>
		</div>
		<div class="rd-metrics">
			<div class="rd-metric">
				<span>Chỉ tiêu<?php echo 'position' === $rd_mode ? ' vị trí' : ''; ?></span>
				<?php if ( 'position' === $rd_mode ) : ?>
					<b><?php echo ! empty( $position['quantity'] ) ? esc_html( $fmt_n( (int) $position['quantity'] ) ) : '—'; ?></b>
					<small><?php echo $multi ? esc_html( 'Cả thông báo: ' . $fmt_n( $total ) . ' chỉ tiêu' ) : 'Theo thông báo gốc'; ?></small>
				<?php else : ?>
					<b><?php echo $total > 0 ? esc_html( $fmt_n( $total ) ) : '—'; ?></b>
					<small><?php echo esc_html( count( $positions ) > 0 ? count( $positions ) . ' vị trí tuyển dụng' : 'Xem thông báo gốc' ); ?></small>
				<?php endif; ?>
			</div>
			<div class="rd-metric">
				<span>Hạn nhận hồ sơ</span>
				<b><?php echo esc_html( '' !== $deadline ? cvc_format_date_vn( $deadline ) : 'Chưa rõ' ); ?></b>
				<small>
					<?php
					if ( '' === $deadline ) {
						echo 'Cần đối chiếu văn bản gốc';
					} elseif ( $dl_unver ) {
						echo 'Chưa có trích dẫn — đối chiếu bản gốc';
					} elseif ( $is_open && null !== $days_left ) {
						echo esc_html( 0 === $days_left ? 'Hết hạn cuối ngày hôm nay' : 'Còn ' . $days_left . ' ngày' );
					} else {
						echo 'Đã hết hạn';
					}
					?>
				</small>
			</div>
			<div class="rd-metric">
				<span>Hình thức</span>
				<b><?php echo esc_html( '' !== $p_method ? $p_method : ( '' !== $method_label ? $method_label : 'Theo thông báo' ) ); ?></b>
				<small><?php echo esc_html( '' !== $p_type ? $p_type : ( '' !== $type_lbl ? $type_lbl : 'Kiểm tra thông báo gốc' ) ); ?></small>
			</div>
			<a class="rd-metric is-link" href="#xac-minh">
				<span>Điểm xác minh</span>
				<b><?php echo null !== $vv['score'] ? (int) $vv['score'] . '<i>/100</i>' : '—'; ?></b>
				<small><?php echo esc_html( $vv['label'] ); ?></small>
			</a>
		</div>
	</header>

	<?php if ( $in_progress ) : ?>
		<div class="rd-banner is-mid">
			<b><?php echo esc_html( 'result' === $stage ? 'Đợt tuyển dụng đã có kết quả.' : 'Đã hết hạn nhận phiếu đăng ký — đợt tuyển dụng đang được tổ chức.' ); ?></b>
			<span>Theo dõi danh sách, triệu tập, tài liệu ôn tập, lịch thi và kết quả ở mục <a href="#van-ban-dot">Văn bản của đợt tuyển</a>.</span>
		</div>
	<?php elseif ( ! $is_open ) : ?>
		<div class="rd-banner is-off">
			<b><?php echo 'expired' === $status ? 'Đợt tuyển dụng này đã hết hạn.' : 'Đã quá hạn nộp hồ sơ của đợt tuyển dụng này.'; ?></b>
			<span>Trang được giữ lại để tham khảo. <a href="<?php echo esc_url( add_query_arg( 'con-han', 1, cvc_positions_url() ) ); ?>">Xem các vị trí đang nhận hồ sơ →</a></span>
		</div>
	<?php endif; ?>

	<div class="rd-layout">
	<div class="rd-col">

	<?php
	/* ============ CƠ HỘI ĐANG XEM (chế độ vị trí) ============ */
	if ( $position ) :
		$units = (array) ( $position['units'] ?? array() );
		?>
		<section class="rd-current" id="vi-tri-nay" aria-labelledby="rd-cur-h">
			<div class="rd-current-head">
				<div>
					<p class="rd-eyebrow is-light">Cơ hội đang xem<?php echo ! empty( $position['code'] ) ? ' · ' . esc_html( (string) $position['code'] ) : ''; ?></p>
					<h2 id="rd-cur-h"><?php echo esc_html( $p_name ); ?></h2>
					<p><?php echo esc_html( $agency_name . ( '' !== $location ? ' · ' . $location : '' ) ); ?></p>
				</div>
				<span class="rd-pill <?php echo esc_attr( $st_class ); ?>"><?php echo esc_html( $is_open ? 'Đang nhận hồ sơ' : ( $in_progress ? 'Đang tổ chức' : 'Đã hết hạn' ) ); ?></span>
			</div>
			<div class="rd-current-body">
				<dl class="rd-facts is-3">
					<div><dt>Chức danh / vị trí</dt><dd><?php echo esc_html( $p_name ); ?></dd></div>
					<div><dt>Chỉ tiêu tuyển</dt><dd><?php echo ! empty( $position['quantity'] ) ? esc_html( $fmt_n( (int) $position['quantity'] ) ) : 'Theo thông báo gốc'; ?></dd></div>
					<div><dt>Hạn nộp hồ sơ</dt><dd><?php echo esc_html( '' !== $deadline ? cvc_format_date_vn( $deadline ) : 'Theo thông báo gốc' ); ?></dd></div>
					<div class="is-wide"><dt>Trình độ</dt><dd><?php echo esc_html( ! empty( $position['education_level'] ) ? (string) $position['education_level'] : 'Theo thông báo gốc' ); ?></dd></div>
					<div class="is-wide"><dt>Ngành, chuyên ngành</dt><dd><?php echo esc_html( ! empty( $position['major_requirements'] ) ? (string) $position['major_requirements'] : 'Theo thông báo gốc' ); ?></dd></div>
					<?php if ( '' !== $p_type || '' !== $p_method ) : ?>
						<div><dt>Loại · phương thức</dt><dd><?php echo esc_html( implode( ' · ', array_filter( array( $p_type, $p_method ) ) ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( ! empty( $position['level'] ) ) : ?>
						<div><dt>Cấp / nhóm</dt><dd><?php echo esc_html( (string) $position['level'] ); ?></dd></div>
					<?php endif; ?>
					<?php if ( ! empty( $position['experience_requirements'] ) ) : ?>
						<div class="is-wide"><dt>Kinh nghiệm</dt><dd><?php echo esc_html( (string) $position['experience_requirements'] ); ?></dd></div>
					<?php endif; ?>
				</dl>

				<?php if ( ! empty( $position['job_description'] ) || ! empty( $position['requirements'] ) || $pos_req ) : ?>
					<div class="rd-block">
						<h3>Yêu cầu riêng của vị trí <small>(trích từ thông báo)</small></h3>
						<?php if ( ! empty( $position['job_description'] ) ) : ?><p><b>Mô tả công việc:</b> <?php echo esc_html( (string) $position['job_description'] ); ?></p><?php endif; ?>
						<?php if ( ! empty( $position['requirements'] ) ) : ?><p><b>Yêu cầu:</b> <?php echo esc_html( (string) $position['requirements'] ); ?></p><?php endif; ?>
						<?php if ( $pos_req ) : ?>
							<ul class="rd-list"><?php foreach ( $pos_req as $line ) : ?><li><?php echo cvc_notice_text( $line ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li><?php endforeach; ?></ul>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $position['exam_subjects'] ) ) : ?>
					<div class="rd-block">
						<h3>Môn thi</h3>
						<p class="rd-tags">
							<?php foreach ( (array) $position['exam_subjects'] as $s ) : ?>
								<span class="rd-tag"><?php echo esc_html( (string) ( $s['name'] ?? '' ) . ( ! empty( $s['is_required'] ) ? ' (bắt buộc)' : '' ) ); ?></span>
							<?php endforeach; ?>
						</p>
					</div>
				<?php endif; ?>

				<?php if ( $units ) : ?>
					<div class="rd-block">
						<h3>Cơ quan, đơn vị và chỉ tiêu</h3>
						<ul class="rd-units">
							<?php foreach ( $units as $u ) : ?>
								<li><span><?php echo esc_html( (string) ( $u['agency_name'] ?? '' ) ); ?><?php echo ! empty( $u['note'] ) ? ' <em>(' . esc_html( (string) $u['note'] ) . ')</em>' : ''; ?></span><b><?php echo (int) ( $u['quota'] ?? 0 ); ?></b></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<div class="rd-current-foot">
					<p><b>Văn bản dùng chung với cả thông báo</b><span>Điều kiện chung, hồ sơ, lịch trình và <?php echo count( $attachments ) > 0 ? esc_html( count( $attachments ) . ' tệp đính kèm' ) : 'nguồn gốc'; ?> bên dưới áp dụng cho mọi vị trí của thông báo.</span></p>
					<?php if ( $multi ) : ?>
						<a class="rd-btn" href="<?php echo esc_url( $rec_url ); ?>">Xem cả thông báo (<?php echo count( $positions ); ?> vị trí) ↗</a>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	/* ============ MỘT THÔNG BÁO — NHIỀU CƠ HỘI ============ */
	if ( $multi ) :
		$cards = $positions;
		if ( $position ) {
			$cards = array_values( array_filter( $positions, fn ( $p ) => (int) ( $p['id'] ?? 0 ) !== $pos_id ) );
		}
		$shown = array_slice( $cards, 0, 6 );
		?>
		<section class="rd-card" id="co-hoi" aria-labelledby="rd-op-h">
			<div class="rd-card-head">
				<h2 id="rd-op-h"><span class="rd-ico" aria-hidden="true">⌘</span><?php echo $position ? 'Các vị trí khác trong cùng thông báo' : esc_html( 'Một thông báo — ' . count( $positions ) . ' cơ hội tuyển dụng' ); ?></h2>
				<span class="rd-badge"><?php echo esc_html( $fmt_n( $total ) ); ?> chỉ tiêu</span>
			</div>
			<?php if ( ! $position ) : ?>
				<div class="rd-op-intro">
					<span class="rd-ico is-soft" aria-hidden="true">↗</span>
					<p><b>Mỗi vị trí có trang riêng</b>Xem điều kiện, chỉ tiêu và lưu mục tiêu ôn thi theo đúng vị trí bạn dự tuyển. Tất cả cùng tham chiếu văn bản gốc và tệp đính kèm của thông báo này.</p>
				</div>
			<?php endif; ?>
			<div class="rd-op-grid">
				<?php foreach ( $shown as $p ) : ?>
					<?php
					$grp = cvc_rd_pos_group( $p );
					$sub = implode( ' · ', array_filter( array( ! empty( $p['education_level'] ) ? cvc_rd_clip( (string) $p['education_level'], 70 ) : '', ! empty( $p['major_requirements'] ) ? cvc_rd_clip( (string) $p['major_requirements'], 90 ) : '' ) ) );
					?>
					<article class="rd-op">
						<p class="rd-tags">
							<?php if ( ! empty( $p['code'] ) ) : ?><span class="rd-tag is-code"><?php echo esc_html( (string) $p['code'] ); ?></span><?php endif; ?>
							<?php if ( '' !== $grp ) : ?><span class="rd-tag"><?php echo esc_html( cvc_rd_clip( $grp, 40 ) ); ?></span><?php endif; ?>
							<?php if ( ! empty( $p['recruitment_method_label'] ) ) : ?><span class="rd-tag"><?php echo esc_html( (string) $p['recruitment_method_label'] ); ?></span><?php endif; ?>
						</p>
						<h3><a href="<?php echo esc_url( cvc_position_url( (string) ( $p['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $p['name'] ?? '' ) ); ?></a></h3>
						<p class="rd-op-sub"><?php echo esc_html( '' !== $sub ? $sub : 'Điều kiện theo thông báo gốc.' ); ?></p>
						<p class="rd-tags">
							<span class="rd-tag is-soft">Chỉ tiêu: <?php echo ! empty( $p['quantity'] ) ? (int) $p['quantity'] : 'xem bản gốc'; ?></span>
							<?php if ( ! empty( $p['units'] ) && count( (array) $p['units'] ) > 1 ) : ?><span class="rd-tag is-soft"><?php echo count( (array) $p['units'] ); ?> cơ quan</span><?php endif; ?>
						</p>
						<a class="rd-btn is-block" href="<?php echo esc_url( cvc_position_url( (string) ( $p['slug'] ?? '' ) ) ); ?>">Xem trang vị trí ↗</a>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $cards ) > count( $shown ) ) : ?>
				<p class="rd-card-more"><a href="#vi-tri">Xem đủ <?php echo count( $positions ); ?> vị trí trong bảng chỉ tiêu ↓</a></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<!-- ============ ĐIỂM XÁC MINH (công khai: điểm, mức, nhãn) ============ -->
	<section class="rd-card" id="xac-minh" aria-labelledby="rd-ver-h">
		<div class="rd-card-head">
			<h2 id="rd-ver-h"><span class="rd-ico is-gold" aria-hidden="true">✓</span>Điểm xác minh thông báo</h2>
			<span class="rd-badge is-gold">Minh bạch nguồn dữ liệu</span>
		</div>
		<div class="rd-ver <?php echo esc_attr( $vv['class'] ); ?>">
			<div class="rd-ring" style="--p:<?php echo (int) ( $vv['score'] ?? 0 ); ?>" role="img" aria-label="<?php echo esc_attr( null !== $vv['score'] ? 'Điểm xác minh ' . $vv['score'] . ' trên 100' : 'Chưa có điểm xác minh' ); ?>">
				<div><b><?php echo null !== $vv['score'] ? (int) $vv['score'] : '—'; ?><i>/100</i></b><small>Điểm xác minh</small></div>
			</div>
			<div class="rd-ver-body">
				<h3><?php echo esc_html( $vv['label'] ); ?>
					<?php
					$lvl_txt = array( 'is-high' => 'Mức cao', 'is-good' => 'Mức khá', 'is-mid' => 'Một phần', 'is-low' => 'Mức thấp', 'is-conflict' => 'Có mâu thuẫn' );
					?>
					<span class="rd-pill <?php echo esc_attr( $vv['class'] ); ?>">● <?php echo esc_html( $lvl_txt[ $vv['class'] ] ?? '' ); ?></span>
				</h3>
				<p><?php echo esc_html( $vv['copy'] ); ?></p>
				<?php if ( $ver ) : ?>
					<p class="rd-tags">
						<span class="rd-tag<?php echo ! empty( $ver['official_source'] ) ? ' is-ok' : ''; ?>">Nguồn chính thức: <?php echo ! empty( $ver['official_source'] ) ? 'có' : 'chưa có'; ?></span>
						<span class="rd-tag<?php echo ! empty( $ver['original_file'] ) ? ' is-ok' : ''; ?>">Văn bản gốc: <?php echo ! empty( $ver['original_file'] ) ? 'đã lưu' : 'chưa lưu'; ?></span>
						<span class="rd-tag<?php echo 3 === (int) ( $ver['key_dates_verified'] ?? 0 ) ? ' is-ok' : ''; ?>">Mốc ngày có trích dẫn: <?php echo (int) ( $ver['key_dates_verified'] ?? 0 ); ?>/3</span>
						<span class="rd-tag<?php echo ! empty( $ver['human_reviewed'] ) ? ' is-ok' : ''; ?>">Kiểm duyệt: <?php echo ! empty( $ver['human_reviewed'] ) ? 'đã đối chiếu' : 'chưa có người duyệt'; ?></span>
						<?php if ( ! empty( $ver['last_checked_at'] ) ) : ?>
							<span class="rd-tag">Kiểm tra nguồn: <?php echo esc_html( wp_date( 'd/m/Y', strtotime( (string) $ver['last_checked_at'] ) ) ); ?></span>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<?php if ( $warnings ) : ?>
					<ul class="rd-warn"><?php foreach ( $warnings as $w ) : ?><li><?php echo esc_html( $w ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
			</div>
		</div>
		<p class="rd-card-foot">Điểm phản ánh mức độ đầy đủ và truy vết được của bằng chứng (nguồn, tệp gốc, trích dẫn ngày tháng, kiểm duyệt), không phải xác nhận pháp lý. Ngày không có trong văn bản được ghi “chưa rõ”, không suy đoán. Hệ thống tính lại hằng ngày.</p>
	</section>

	<!-- ============ TỔNG QUAN ============ -->
	<section class="rd-card" id="tong-quan" aria-labelledby="rd-ov-h">
		<div class="rd-card-head">
			<h2 id="rd-ov-h"><span class="rd-ico" aria-hidden="true">▥</span>Tổng quan thông báo</h2>
			<button type="button" class="rd-btn is-sm" data-rd-share data-url="<?php echo esc_url( $here_url ); ?>" data-title="<?php echo esc_attr( 'position' === $rd_mode ? 'Tuyển dụng ' . $p_name : $title ); ?>">Chia sẻ ↗</button>
		</div>
		<dl class="rd-facts is-grid">
			<?php if ( 'position' === $rd_mode ) : ?>
				<div class="is-full"><dt>Thông báo</dt><dd><a href="<?php echo esc_url( $rec_url ); ?>"><?php echo esc_html( $title ); ?></a></dd></div>
			<?php endif; ?>
			<div><dt>Cơ quan tuyển dụng</dt><dd><?php echo esc_html( '' !== $agency_name ? $agency_name : 'Chưa rõ' ); ?></dd></div>
			<div><dt>Địa điểm</dt><dd><?php echo esc_html( '' !== $location ? $location : 'Theo thông báo gốc' ); ?></dd></div>
			<div><dt>Ngày ban hành</dt><dd><?php echo esc_html( ! empty( $details['issued_date'] ) ? (string) $details['issued_date'] : ( ! empty( $dates['announcement_date'] ) ? cvc_format_date_vn( (string) $dates['announcement_date'] ) : 'Chưa có dữ liệu' ) ); ?></dd></div>
			<div><dt>Số hiệu văn bản</dt><dd><?php echo esc_html( '' !== $doc_number ? $doc_number : 'Chưa có dữ liệu' ); ?></dd></div>
			<div><dt>Loại tuyển dụng</dt><dd><?php echo esc_html( '' !== $type_lbl ? $type_lbl : 'Theo điều kiện tuyển dụng' ); ?></dd></div>
			<div><dt>Vị trí · chỉ tiêu</dt><dd><?php echo esc_html( count( $positions ) . ' vị trí · ' . ( $total > 0 ? $fmt_n( $total ) . ' chỉ tiêu' : 'chỉ tiêu theo bản gốc' ) ); ?></dd></div>
			<?php foreach ( array( 'application_start_date' => 'Bắt đầu nhận hồ sơ', 'application_deadline' => 'Hạn nhận hồ sơ' ) as $k => $lbl ) : ?>
				<div>
					<dt><?php echo esc_html( $lbl ); ?></dt>
					<dd><?php echo esc_html( ! empty( $dates[ $k ] ) ? cvc_format_date_vn( (string) $dates[ $k ] ) : 'Chưa rõ — xem bản gốc' ); ?></dd>
					<?php $ev = $evidence[ $k ] ?? null; ?>
					<?php if ( is_array( $ev ) && ! empty( $ev['snippet'] ) ) : ?>
						<details class="rd-cite"><summary>✓ Trích dẫn văn bản</summary><p>“<?php echo esc_html( (string) $ev['snippet'] ); ?>”<small><?php echo esc_html( 'Nguồn: ' . (string) ( $ev['source'] ?? '' ) . ( ! empty( $ev['note'] ) ? ' — ' . $ev['note'] : '' ) ); ?></small></p></details>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<div><dt>Hình thức</dt><dd><?php echo esc_html( '' !== $method_label ? $method_label : 'Theo thông báo gốc' ); ?></dd></div>
		</dl>

		<div class="rd-card-body" id="noi-dung">
			<?php if ( '' !== $summary ) : ?>
				<div class="rd-prose"><?php echo cvc_notice_text( $summary ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>

			<?php if ( $changes ) : ?>
				<div class="rd-changes" id="thay-doi">
					<h3>Đã điều chỉnh so với thông báo ban đầu</h3>
					<ul>
						<?php foreach ( $changes as $ch ) : ?>
							<?php
							$fmtv = static fn ( $v ) => preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? cvc_format_date_vn( (string) $v ) : (string) $v;
							?>
							<li><b><?php echo esc_html( (string) ( $ch['field_label'] ?? '' ) ); ?>:</b>
								<?php if ( null !== ( $ch['old_value'] ?? null ) && '' !== (string) $ch['old_value'] ) : ?><s><?php echo esc_html( $fmtv( $ch['old_value'] ) ); ?></s> →<?php endif; ?>
								<b class="is-new"><?php echo esc_html( $fmtv( $ch['new_value'] ?? '' ) ); ?></b>
								<?php if ( ! empty( $ch['source_doc_number'] ) ) : ?><small>(theo văn bản số <?php echo esc_html( (string) $ch['source_doc_number'] ); ?>)</small><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="rd-callout">
				<span aria-hidden="true">ⓘ</span>
				<p>
					<?php if ( $collected ) : ?>
						<b>Thông tin sưu tầm, chưa có văn bản trên cổng thông tin chính thức.</b> Nguồn: <?php echo esc_html( (string) ( $source_info['label'] ?? 'Nguồn sưu tầm' ) ); ?>. Liên hệ trực tiếp đơn vị tuyển dụng để xác nhận trước khi nộp hồ sơ.
					<?php else : ?>
						<b>Đọc bản gốc trước khi quyết định.</b> Nội dung trên trang được trích từ văn bản của cơ quan tuyển dụng; khi có khác biệt, căn cứ theo văn bản gốc<?php echo '' !== $source_url ? ' tại <a href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( (string) ( $source_info['name'] ?? ( wp_parse_url( $source_url, PHP_URL_HOST ) ?: 'trang gốc' ) ) ) . '</a>' : ''; ?>.
					<?php endif; ?>
				</p>
			</div>
		</div>
	</section>

	<!-- ============ VỊ TRÍ VÀ CHỈ TIÊU ============ -->
	<section class="rd-card" id="vi-tri" aria-labelledby="rd-pos-h">
		<div class="rd-card-head">
			<h2 id="rd-pos-h"><span class="rd-ico" aria-hidden="true">☷</span>Vị trí và chỉ tiêu tuyển dụng</h2>
			<span class="rd-badge"><?php echo count( $positions ); ?> vị trí</span>
		</div>
		<?php if ( ! $positions ) : ?>
			<p class="rd-card-body rd-muted">Chưa có danh sách vị trí chi tiết cho đợt này — xem thông báo gốc.</p>
		<?php else : ?>
			<?php
			$groups = array_values( array_unique( array_filter( array_map( 'cvc_rd_pos_group', $positions ) ) ) );
			?>
			<div class="rd-card-body">
				<p class="rd-muted">Tra cứu nhanh các vị trí; bấm “Chi tiết” để mở trang riêng của từng vị trí. Đối chiếu mã vị trí và điều kiện trong thông báo gốc.</p>
				<?php if ( count( $positions ) > 4 ) : ?>
					<div class="rd-tools">
						<label class="rd-search"><span aria-hidden="true">⌕</span><input type="search" id="rd-pos-q" placeholder="Tìm tên vị trí, mã vị trí, chuyên ngành…" aria-label="Tìm vị trí trong thông báo"></label>
						<?php if ( count( $groups ) > 1 ) : ?>
							<select id="rd-pos-g" aria-label="Lọc theo nhóm">
								<option value="">Tất cả nhóm</option>
								<?php foreach ( $groups as $g ) : ?><option value="<?php echo esc_attr( $g ); ?>"><?php echo esc_html( cvc_rd_clip( $g, 50 ) ); ?></option><?php endforeach; ?>
							</select>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="rd-table-wrap">
					<table class="rd-table" id="rd-pos-table">
						<thead><tr><th>Vị trí / mã</th><th>Nhóm</th><th class="is-num">Chỉ tiêu</th><th>Trình độ, chuyên ngành</th><th><span class="screen-reader-text">Thao tác</span></th></tr></thead>
						<tbody>
							<?php foreach ( $positions as $p ) : ?>
								<?php
								$grp   = cvc_rd_pos_group( $p );
								$cond  = implode( ' · ', array_filter( array( ! empty( $p['education_level'] ) ? cvc_rd_clip( (string) $p['education_level'], 60 ) : '', ! empty( $p['major_requirements'] ) ? cvc_rd_clip( (string) $p['major_requirements'], 110 ) : '' ) ) );
								$is_me = $position && (int) ( $p['id'] ?? 0 ) === $pos_id;
								$hay   = mb_strtolower( implode( ' ', array( $p['name'] ?? '', $p['code'] ?? '', $p['major_requirements'] ?? '', $p['education_level'] ?? '' ) ), 'UTF-8' );
								?>
								<tr<?php echo $is_me ? ' class="is-cur"' : ''; ?> data-q="<?php echo esc_attr( $hay ); ?>" data-g="<?php echo esc_attr( $grp ); ?>">
									<td data-l="Vị trí"><b><?php echo esc_html( (string) ( $p['name'] ?? '' ) ); ?></b><?php echo ! empty( $p['code'] ) ? '<small>Mã: ' . esc_html( (string) $p['code'] ) . '</small>' : ''; ?><?php echo ! empty( $p['is_sensitive'] ) ? '<small class="is-red">Cơ mật, trọng yếu</small>' : ''; ?></td>
									<td data-l="Nhóm"><?php echo esc_html( '' !== $grp ? cvc_rd_clip( $grp, 40 ) : '—' ); ?></td>
									<td data-l="Chỉ tiêu" class="is-num"><?php echo ! empty( $p['quantity'] ) ? (int) $p['quantity'] : '—'; ?></td>
									<td data-l="Điều kiện"><?php echo '' !== $cond ? esc_html( $cond ) : '<span class="rd-tag is-gold">Xem bản gốc</span>'; ?></td>
									<td class="is-act">
										<?php if ( $is_me ) : ?>
											<span class="rd-tag is-ok">Đang xem</span>
										<?php elseif ( ! empty( $p['slug'] ) ) : ?>
											<a class="rd-btn is-sm" href="<?php echo esc_url( cvc_position_url( (string) $p['slug'] ) ); ?>">Chi tiết</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p class="rd-empty" id="rd-pos-empty" hidden>Không có vị trí khớp từ khóa.</p>
				<p class="rd-muted rd-small">ⓘ Tổng: <?php echo esc_html( $fmt_n( $total ) ); ?> chỉ tiêu. Số liệu theo biểu chỉ tiêu / văn bản của cơ quan tuyển dụng.</p>
			</div>
		<?php endif; ?>
	</section>

	<!-- ============ KHO TÀI LIỆU ============ -->
	<section class="rd-card" id="tai-lieu" aria-labelledby="rd-doc-h">
		<span id="van-ban-goc" class="rd-anchor" aria-hidden="true"></span>
		<div class="rd-card-head">
			<h2 id="rd-doc-h"><span class="rd-ico" aria-hidden="true">▤</span>Kho tài liệu của thông báo</h2>
			<span class="rd-badge"><?php echo count( $attachments ); ?> tệp</span>
		</div>
		<?php if ( ! $attachments ) : ?>
			<div class="rd-card-body">
				<div class="rd-callout is-plain">
					<span aria-hidden="true">ⓘ</span>
					<p>
						<?php if ( '' !== $source_url ) : ?>
							Thông báo được đăng trực tiếp trên trang của cơ quan, không kèm tệp. Đọc bản gốc tại <a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( (string) ( $source_info['name'] ?? ( wp_parse_url( $source_url, PHP_URL_HOST ) ?: 'trang gốc' ) ) ); ?></a> hoặc phần <a href="#toan-van">Toàn văn</a> bên dưới.
						<?php else : ?>
							Chưa lưu được tệp văn bản gốc của thông báo này. Vui lòng liên hệ cơ quan tuyển dụng để nhận văn bản chính thức.
						<?php endif; ?>
					</p>
				</div>
			</div>
		<?php else : ?>
			<div class="rd-card-body">
				<p class="rd-muted">Chọn tệp ở danh mục để xem trước. Tệp PDF hiển thị nguyên bản đã lưu từ cổng thông tin của cơ quan (giữ nguyên dấu, chữ ký, bảng biểu). Văn bản có giá trị pháp lý là bản do cơ quan ban hành.</p>
				<div class="rd-files">
					<div class="rd-filelist" role="tablist" aria-label="Danh mục tệp">
						<p class="rd-filelist-h"><b>Danh mục tệp</b><span><?php echo count( $attachments ); ?> tệp</span></p>
						<?php foreach ( $attachments as $i => $a ) : ?>
							<?php
							$ex    = cvc_rd_ext( (string) ( $a['extension'] ?? '' ) );
							$has_t = isset( $ft_docs[ (int) ( $a['id'] ?? 0 ) ] ) && '' !== trim( $ft_docs[ (int) $a['id'] ] );
							$meta  = array_filter( array( $ex['label'], ! empty( $a['size'] ) ? size_format( (int) $a['size'] ) : '', ! empty( $a['downloaded_at'] ) ? 'lưu ' . wp_date( 'd/m/Y', strtotime( (string) $a['downloaded_at'] ) ) : '' ) );
							?>
							<button type="button" class="rd-file<?php echo 0 === $i ? ' is-cur' : ''; ?>" role="tab" id="rd-ftab-<?php echo (int) $i; ?>" aria-controls="rd-prev-<?php echo (int) $i; ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" data-rd-file="<?php echo (int) $i; ?>">
								<span class="rd-ext <?php echo esc_attr( $ex['class'] ); ?>"><?php echo esc_html( $ex['label'] ); ?></span>
								<span class="rd-file-t">
									<b><?php echo esc_html( sprintf( '%02d', $i + 1 ) . ' · ' . (string) ( $a['title'] ?? '' ?: ( $a['filename'] ?? 'Tệp đính kèm' ) ) ); ?></b>
									<small><?php echo esc_html( implode( ' · ', $meta ) ); ?></small>
									<em class="<?php echo $has_t ? 'is-ok' : ''; ?>"><?php echo $has_t ? 'Có chữ trích xuất' : ( 'pdf' === strtolower( (string) ( $a['extension'] ?? '' ) ) ? 'Bản chụp / chưa trích chữ' : 'Tải về để xem' ); ?></em>
								</span>
							</button>
						<?php endforeach; ?>
					</div>
					<div class="rd-previews">
						<?php foreach ( $attachments as $i => $a ) : ?>
							<?php
							$ext    = strtolower( (string) ( $a['extension'] ?? '' ) );
							$dl     = (string) ( $a['download_url'] ?? '' );
							$inline = 'pdf' === $ext && '' !== $dl ? add_query_arg( 'inline', 1, $dl ) : '';
							$text   = $ft_docs[ (int) ( $a['id'] ?? 0 ) ] ?? '';
							$name   = (string) ( $a['title'] ?? '' ?: ( $a['filename'] ?? 'Tệp đính kèm' ) );
							?>
							<div class="rd-prev" id="rd-prev-<?php echo (int) $i; ?>" role="tabpanel" aria-labelledby="rd-ftab-<?php echo (int) $i; ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
								<div class="rd-prev-head">
									<div><b><?php echo esc_html( $name ); ?></b><small><?php echo esc_html( strtoupper( $ext ) . ( ! empty( $a['filename'] ) ? ' · ' . $a['filename'] : '' ) ); ?></small></div>
									<p>
										<?php if ( '' !== $inline ) : ?><a class="rd-btn is-gold is-sm" href="<?php echo esc_url( $inline ); ?>" target="_blank" rel="noopener nofollow">Mở bản gốc</a><?php endif; ?>
										<?php if ( '' !== $dl ) : ?><a class="rd-btn is-white is-sm" href="<?php echo esc_url( $dl ); ?>" rel="nofollow">Tải tệp</a><?php endif; ?>
										<?php if ( ! empty( $a['original_url'] ) ) : ?><a class="rd-btn is-ghost is-sm" href="<?php echo esc_url( (string) $a['original_url'] ); ?>" target="_blank" rel="noopener nofollow">Link gốc ↗</a><?php endif; ?>
									</p>
								</div>
								<div class="rd-prev-body">
									<?php if ( '' !== $inline ) : ?>
										<iframe class="rd-pdf" title="<?php echo esc_attr( 'Xem trước: ' . $name ); ?>" <?php echo 0 === $i ? 'src="' . esc_url( $inline ) . '" loading="lazy"' : 'data-src="' . esc_url( $inline ) . '"'; ?>></iframe>
										<p class="rd-prev-note">Trình xem không hiển thị? <a href="<?php echo esc_url( $inline ); ?>" target="_blank" rel="noopener nofollow">Mở PDF trong thẻ mới</a>.</p>
									<?php elseif ( '' !== trim( $text ) ) : ?>
										<div class="rd-paper"><?php echo cvc_notice_text( mb_strlen( $text ) > 2400 ? rtrim( mb_substr( $text, 0, 2400 ) ) . '…' : $text ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
										<p class="rd-prev-note">Chữ trích xuất tự động từ tệp <?php echo esc_html( strtoupper( $ext ) ); ?>, có thể mất định dạng bảng — tải tệp để xem bản đầy đủ<?php echo mb_strlen( $text ) > 2400 ? ', hoặc đọc toàn văn ở mục <a href="#toan-van">Nội dung văn bản</a>' : ''; ?>.</p>
									<?php else : ?>
										<div class="rd-prev-empty"><span class="rd-ext <?php echo esc_attr( cvc_rd_ext( $ext )['class'] ); ?>"><?php echo esc_html( strtoupper( $ext ) ); ?></span><p>Định dạng này chưa xem trước được trên trang. Tải tệp để mở bằng Word / Excel.</p></div>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</section>

	<!-- ============ NỘI DUNG VĂN BẢN (tóm tắt / chi tiết / toàn văn / tìm) ============ -->
	<?php
	$ft_html  = (string) ( $full_text['html'] ?? '' );
	$has_det  = (bool) array_filter( $details, fn ( $v, $k ) => 'issued_date' !== $k && ! empty( $v ), ARRAY_FILTER_USE_BOTH );
	$has_full = '' !== $ft_html || ! empty( $ft_docs );
	$gc       = (array) ( $details['general_conditions'] ?? array() );
	$rounds   = array_filter( array_map( fn ( $r ) => (string) ( $r['name'] ?? '' ), (array) ( $details['selection_rounds'] ?? array() ) ) );
	$first_pdf = '';
	foreach ( $attachments as $a ) {
		if ( 'pdf' === strtolower( (string) ( $a['extension'] ?? '' ) ) && ! empty( $a['download_url'] ) ) {
			$first_pdf = add_query_arg( 'inline', 1, (string) $a['download_url'] );
			break;
		}
	}
	$ev_dl = is_array( $evidence['application_deadline'] ?? null ) ? $evidence['application_deadline'] : null;
	?>
	<section class="rd-card" id="noi-dung-van-ban" aria-labelledby="rd-txt-h">
		<div class="rd-card-head">
			<h2 id="rd-txt-h"><span class="rd-ico is-gold" aria-hidden="true">⌕</span>Nội dung văn bản</h2>
			<span class="rd-badge is-gold"><?php echo ! empty( $ver['human_reviewed'] ) ? 'Trích xuất · đã kiểm duyệt' : 'Trích xuất tự động · chưa kiểm duyệt'; ?></span>
		</div>
		<div class="rd-card-body">
			<p class="rd-muted">Đọc nhanh và tìm trong nội dung thông báo. Mỗi đoạn tìm thấy có đường dẫn về đúng tệp gốc để bạn kiểm tra lại.</p>
			<div class="rd-tabs" role="tablist" aria-label="Nội dung văn bản">
				<button type="button" role="tab" class="is-cur" aria-selected="true" aria-controls="tom-tat" id="rd-t-1" data-rd-tab="tom-tat">Tóm tắt văn bản</button>
				<?php if ( $has_det ) : ?><button type="button" role="tab" aria-selected="false" aria-controls="chi-tiet" id="rd-t-2" data-rd-tab="chi-tiet">Nội dung chi tiết</button><?php endif; ?>
				<?php if ( $has_full ) : ?><button type="button" role="tab" aria-selected="false" aria-controls="toan-van" id="rd-t-3" data-rd-tab="toan-van">Toàn văn</button><?php endif; ?>
				<button type="button" role="tab" aria-selected="false" aria-controls="tim-van-ban" id="rd-t-4" data-rd-tab="tim-van-ban">Tìm trong văn bản</button>
			</div>

			<div class="rd-tabpanel" id="tom-tat" role="tabpanel" aria-labelledby="rd-t-1">
				<div class="rd-sumgrid">
					<div><span>Cơ quan tuyển dụng</span><b><?php echo esc_html( '' !== $agency_name ? $agency_name : 'Chưa xác định từ nguồn' ); ?></b></div>
					<div><span>Vị trí và chỉ tiêu</span><b><?php echo esc_html( count( $positions ) . ' vị trí · ' . ( $total > 0 ? $fmt_n( $total ) . ' chỉ tiêu' : 'xem phụ lục' ) ); ?></b></div>
					<div class="is-wide"><span>Điều kiện dự tuyển</span><b><?php echo esc_html( $gc ? cvc_rd_clip( (string) $gc[0], 260 ) : 'Đọc điều kiện chung và điều kiện từng vị trí trong văn bản gốc.' ); ?></b><?php echo count( $gc ) > 1 ? '<small>+ ' . ( count( $gc ) - 1 ) . ' điều kiện khác ở tab Nội dung chi tiết</small>' : ''; ?></div>
					<div><span>Hồ sơ và nơi nộp</span><b><?php echo esc_html( ! empty( $details['application_place'] ) ? cvc_rd_clip( (string) $details['application_place'], 200 ) : ( ! empty( $details['documents_required'] ) ? count( (array) $details['documents_required'] ) . ' loại giấy tờ — xem chi tiết' : 'Chưa trích xuất — xem văn bản gốc' ) ); ?></b></div>
					<div><span>Hình thức tuyển dụng</span><b><?php echo esc_html( $rounds ? implode( ' → ', $rounds ) : ( '' !== $method_label ? $method_label : 'Đối chiếu thông báo gốc' ) ); ?></b></div>
					<div class="is-wide"><span>Nguồn kiểm chứng</span>
						<b><?php echo esc_html( $ev_dl ? 'Hạn nộp trích từ ' . (string) ( $ev_dl['source'] ?? 'văn bản' ) : ( $attachments ? count( $attachments ) . ' tệp đính kèm đã lưu' : ( '' !== $source_url ? 'Trang tin của cơ quan' : 'Chưa có nguồn gốc' ) ) ); ?></b>
						<?php if ( '' !== $first_pdf ) : ?><a class="rd-btn is-sm" href="<?php echo esc_url( $first_pdf ); ?>" target="_blank" rel="noopener nofollow">Đối chiếu PDF gốc ↗</a><?php elseif ( '' !== $source_url ) : ?><a class="rd-btn is-sm" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">Mở trang gốc ↗</a><?php endif; ?>
					</div>
				</div>
			</div>

			<?php if ( $has_det ) : ?>
				<div class="rd-tabpanel rd-details" id="chi-tiet" role="tabpanel" aria-labelledby="rd-t-2" hidden>
					<?php
					$lists = array( 'general_conditions' => 'Điều kiện chung', 'specific_requirements' => 'Tiêu chuẩn, điều kiện riêng', 'disqualified' => 'Không được đăng ký dự tuyển' );
					foreach ( $lists as $k => $lbl ) :
						if ( empty( $details[ $k ] ) ) {
							continue;
						}
						?>
						<div class="rd-block"><h3><?php echo esc_html( $lbl ); ?></h3><ul class="rd-list">
							<?php foreach ( (array) $details[ $k ] as $line ) : ?>
								<li<?php echo 'specific_requirements' === $k && $position && cvc_rd_mentions( (string) $line, $position ) ? ' class="is-mark"' : ''; ?>><?php echo cvc_notice_text( (string) $line ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
							<?php endforeach; ?>
						</ul></div>
					<?php endforeach; ?>
					<?php if ( ! empty( $details['selection_rounds'] ) ) : ?>
						<div class="rd-block"><h3>Hình thức, nội dung tuyển dụng</h3>
							<ol class="rd-rounds">
								<?php foreach ( (array) $details['selection_rounds'] as $r ) : ?>
									<li><b><?php echo esc_html( (string) ( $r['name'] ?? '' ) ); ?></b>
										<?php foreach ( array( 'format' => 'Hình thức', 'content' => 'Nội dung', 'duration' => 'Thời gian', 'scoring' => 'Thang điểm / điều kiện đạt' ) as $f => $lbl ) : ?>
											<?php if ( ! empty( $r[ $f ] ) ) : ?><p><span><?php echo esc_html( $lbl ); ?>:</span> <?php echo cvc_notice_text( (string) $r[ $f ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p><?php endif; ?>
										<?php endforeach; ?>
									</li>
								<?php endforeach; ?>
							</ol>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $details['documents_required'] ) ) : ?>
						<div class="rd-block"><h3>Hồ sơ dự tuyển</h3><ol class="rd-list is-num">
							<?php foreach ( (array) $details['documents_required'] as $line ) : ?><li><?php echo cvc_notice_text( (string) $line ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li><?php endforeach; ?>
						</ol></div>
					<?php endif; ?>
					<?php foreach ( array( 'application_place' => 'Nơi, cách nộp hồ sơ', 'application_fee' => 'Lệ phí', 'priority' => 'Đối tượng, điểm ưu tiên' ) as $k => $lbl ) : ?>
						<?php if ( ! empty( $details[ $k ] ) && is_string( $details[ $k ] ) ) : ?>
							<div class="rd-block"><h3><?php echo esc_html( $lbl ); ?></h3><p><?php echo cvc_notice_text( (string) $details[ $k ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p></div>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ( ! empty( $details['schedule'] ) ) : ?>
						<div class="rd-block"><h3>Lịch trình</h3><ol class="rd-steps-line">
							<?php foreach ( (array) $details['schedule'] as $x ) : ?>
								<li><?php echo ! empty( $x['time'] ) ? '<b>' . esc_html( (string) $x['time'] ) . '</b>' : ''; ?><span><?php echo cvc_notice_text( (string) ( $x['event'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></li>
							<?php endforeach; ?>
						</ol></div>
					<?php endif; ?>
					<?php if ( ! empty( $details['notes'] ) ) : ?>
						<div class="rd-block"><h3>Lưu ý</h3><ul class="rd-list"><?php foreach ( (array) $details['notes'] as $line ) : ?><li><?php echo cvc_notice_text( (string) $line ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li><?php endforeach; ?></ul></div>
					<?php endif; ?>
					<p class="rd-muted rd-small">Chép lại theo từng mục từ thông báo của cơ quan tuyển dụng. Khi có khác biệt, căn cứ theo văn bản gốc.</p>
				</div>
			<?php endif; ?>

			<?php if ( $has_full ) : ?>
				<div class="rd-tabpanel" id="toan-van" role="tabpanel" aria-labelledby="rd-t-3" hidden>
					<p class="rd-muted rd-small">Đăng lại nguyên văn từ <?php echo '' !== $source_url ? '<a href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( (string) ( $source_info['name'] ?? ( wp_parse_url( $source_url, PHP_URL_HOST ) ?: 'trang gốc' ) ) ) . '</a>' : 'trang của cơ quan'; ?>. Văn bản có giá trị pháp lý là bản do cơ quan tuyển dụng ban hành.</p>
					<?php if ( '' !== $ft_html ) : ?>
						<div class="rd-prose rd-fulltext"><?php echo cvc_notice_kses( $ft_html ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
					<?php foreach ( (array) ( $full_text['documents'] ?? array() ) as $i => $d ) : ?>
						<details class="rd-doctext"<?php echo '' === $ft_html && 0 === $i ? ' open' : ''; ?>>
							<summary><span class="rd-ext is-doc">Tệp</span><?php echo esc_html( (string) ( $d['name'] ?? 'Công văn' ) ); ?> <small>(chữ trích từ tệp)</small></summary>
							<div class="rd-paper is-scroll"><?php echo cvc_notice_text( (string) ( $d['text'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="rd-tabpanel" id="tim-van-ban" role="tabpanel" aria-labelledby="rd-t-4" hidden>
				<form class="rd-findform" data-rd-find>
					<label class="rd-search"><span aria-hidden="true">⌕</span><input type="search" id="rd-find-q" name="q" minlength="2" maxlength="80" required placeholder="Ví dụ: hồ sơ, chứng chỉ, ngoại ngữ, lệ phí…" aria-label="Từ khóa tìm trong văn bản"></label>
					<button type="submit" class="rd-btn is-primary">Tìm</button>
				</form>
				<div class="rd-findres" id="rd-find-res" aria-live="polite"><p class="rd-muted rd-small">Tìm trong chữ trích xuất của trang thông báo và các tệp đính kèm (không gồm bản chụp chưa có chữ).</p></div>
			</div>
		</div>
	</section>

	<?php
	/* ============ VĂN BẢN CỦA ĐỢT, CĂN CỨ PHÁP LÝ ============ */
	if ( $round_events ) {
		echo '<div class="rd-legacy">';
		cvc_render_round_timeline( $round_events, $legal_basis );
		echo '</div>';
	} elseif ( $round_docs ) {
		?>
		<section class="rd-card" id="van-ban-dot" aria-labelledby="rd-rd-h">
			<div class="rd-card-head"><h2 id="rd-rd-h"><span class="rd-ico" aria-hidden="true">▤</span>Văn bản của đợt tuyển</h2><span class="rd-badge"><?php echo count( $round_docs ); ?> văn bản</span></div>
			<div class="rd-card-body">
				<ol class="rd-timeline">
					<?php foreach ( $round_docs as $rdoc ) : ?>
						<li>
							<p class="rd-tl-meta"><b><?php echo esc_html( (string) ( $rdoc['role_label'] ?? '' ) ); ?></b><?php echo ! empty( $rdoc['doc_number'] ) ? ' · ' . esc_html( (string) $rdoc['doc_number'] ) : ''; ?><?php echo ! empty( $rdoc['issued_date'] ) ? ' · ' . esc_html( cvc_format_date_vn( (string) $rdoc['issued_date'] ) ) : ''; ?></p>
							<p class="rd-tl-title"><?php echo esc_html( (string) ( $rdoc['title'] ?? '' ) ); ?></p>
							<p class="rd-tags">
								<?php foreach ( (array) ( $rdoc['files'] ?? array() ) as $f ) : ?>
									<a class="rd-tag is-link" href="<?php echo esc_url( (string) ( $f['download_url'] ?? '#' ) ); ?>" rel="nofollow">⤓ <?php echo esc_html( wp_trim_words( (string) ( $f['filename'] ?? $f['title'] ?? 'Tệp' ), 8 ) ); ?> <small><?php echo esc_html( strtoupper( (string) ( $f['extension'] ?? '' ) ) ); ?></small></a>
								<?php endforeach; ?>
								<?php if ( ! empty( $rdoc['source_url'] ) ) : ?><a class="rd-tag is-link" href="<?php echo esc_url( (string) $rdoc['source_url'] ); ?>" target="_blank" rel="noopener nofollow">Trang gốc ↗</a><?php endif; ?>
							</p>
						</li>
					<?php endforeach; ?>
				</ol>
				<div class="rd-legacy"><?php cvc_render_recruitment_legal_basis( $legal_basis ); ?></div>
			</div>
		</section>
		<?php
	} elseif ( $legal_basis ) {
		echo '<div class="rd-legacy">';
		cvc_render_recruitment_legal_basis( $legal_basis );
		echo '</div>';
	}

	if ( $study_plan ) {
		echo '<div class="rd-legacy">';
		cvc_render_recruitment_study_plan( $study_plan );
		echo '</div>';
	}
	?>

	<?php if ( $exams || $subjects || $legal_docs ) : ?>
		<!-- ============ HỒ SƠ ÔN THI ============ -->
		<section class="rd-card" id="ho-so-on-thi" aria-labelledby="rd-hs-h">
			<div class="rd-card-head"><h2 id="rd-hs-h"><span class="rd-ico" aria-hidden="true">▣</span>Hồ sơ ôn thi cho đợt tuyển dụng này</h2></div>
			<div class="rd-card-body">
				<p class="rd-muted">Tổng hợp từ đề thi, môn thi, chủ đề và văn bản đang gắn với đợt tuyển dụng trong hệ thống.</p>
				<?php if ( $exams ) : ?>
					<div class="rd-block"><h3>Đề thi thử gắn với đợt này</h3>
						<div class="rd-linkgrid">
							<?php foreach ( $exams as $exam ) : ?>
								<a href="<?php echo esc_url( cvc_exam_url( (string) ( $exam['slug'] ?? '' ) ) ); ?>"><b><?php echo esc_html( (string) ( $exam['title'] ?? '' ) ); ?></b>
									<small><?php echo esc_html( implode( ' · ', array_filter( array( ! empty( $exam['total_questions'] ) ? (int) $exam['total_questions'] . ' câu' : null, ! empty( $exam['duration_minutes'] ) ? (int) $exam['duration_minutes'] . ' phút' : null, ! empty( $exam['exam_stage'] ) ? cvc_exam_stage_label( (string) $exam['exam_stage'] ) : null ) ) ) ); ?></small></a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
				<?php foreach ( $subjects as $subject ) : ?>
					<details class="rd-doctext">
						<summary>Môn thi: <?php echo esc_html( (string) ( $subject['name'] ?? '' ) ); ?> <small>(<?php echo count( $subject['topics'] ?? array() ); ?> chủ đề)</small></summary>
						<div class="rd-subject">
							<?php if ( ! empty( $subject['topics'] ) ) : ?>
								<p class="rd-tags"><?php foreach ( $subject['topics'] as $topic ) : ?><a class="rd-tag is-link" href="<?php echo esc_url( cvc_topic_url( (string) ( $topic['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $topic['name'] ?? '' ) ); ?></a><?php endforeach; ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $subject['knowledge_items'] ) ) : ?>
								<h4>Kiến thức trọng tâm</h4>
								<ul class="rd-list"><?php foreach ( array_slice( $subject['knowledge_items'], 0, 8 ) as $ki ) : ?><li><a href="<?php echo esc_url( cvc_knowledge_item_url( (string) ( $ki['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $ki['title'] ?? '' ) ); ?></a></li><?php endforeach; ?></ul>
							<?php endif; ?>
							<?php if ( ! empty( $subject['sample_questions'] ) ) : ?>
								<h4>Câu hỏi minh họa <small>(đáp án có khi bạn làm đề)</small></h4>
								<?php foreach ( $subject['sample_questions'] as $sq ) : ?>
									<div class="rd-q"><p><?php echo esc_html( (string) ( $sq['question_text'] ?? '' ) ); ?></p>
										<ul><?php foreach ( array_values( (array) ( $sq['options'] ?? array() ) ) as $oi => $opt ) : ?><li><?php echo esc_html( chr( 65 + $oi ) . '. ' . ( $opt['option_text'] ?? '' ) ); ?></li><?php endforeach; ?></ul>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
							<?php if ( ! empty( $subject['documents'] ) ) : ?>
								<h4>Tài liệu ôn thi</h4>
								<p class="rd-tags"><?php foreach ( $subject['documents'] as $doc ) : ?><a class="rd-tag is-link" href="<?php echo esc_url( cvc_document_url( (string) ( $doc['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( (string) ( $doc['title'] ?? '' ) ); ?> <b><?php echo ! empty( $doc['is_free'] ) ? 'MIỄN PHÍ' : esc_html( $money( $doc['effective_price'] ?? 0 ) ); ?></b></a><?php endforeach; ?></p>
							<?php endif; ?>
						</div>
					</details>
				<?php endforeach; ?>
				<?php if ( $legal_docs ) : ?>
					<div class="rd-block"><h3>Văn bản pháp luật liên quan</h3>
						<p class="rd-tags"><?php foreach ( $legal_docs as $ld ) : ?><a class="rd-tag is-link" href="<?php echo esc_url( cvc_legal_document_url( (string) ( $ld['slug'] ?? '' ) ) ); ?>"><?php echo esc_html( trim( ( $ld['document_number'] ?? '' ) . ' ' . ( $ld['title'] ?? '' ) ) ); ?></a><?php endforeach; ?></p>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( function_exists( 'cvc_ci_render_detail_block' ) ) { cvc_ci_render_detail_block( $rid ); } ?>

	<div class="rd-legacy"><?php cvc_render_candidate_kit( $recruitment ); ?></div>

	<!-- ============ BẮT ĐẦU CHUẨN BỊ ============ -->
	<section class="rd-card" id="chuan-bi" aria-labelledby="rd-prep-h">
		<div class="rd-card-head"><h2 id="rd-prep-h"><span class="rd-ico" aria-hidden="true">▣</span>Bắt đầu chuẩn bị kỳ thi</h2></div>
		<div class="rd-card-body">
			<ol class="rd-steps">
				<li><b>Đối chiếu điều kiện dự tuyển</b><span>Đọc <a href="#tai-lieu">văn bản gốc</a> và xác nhận văn bằng, tiêu chuẩn, hồ sơ cùng thời hạn<?php echo $position ? ' của vị trí ' . esc_html( $p_name ) : ''; ?>.</span></li>
				<li><b>Gom tài liệu theo vị trí</b><span>Chọn tài liệu phù hợp với vị trí và nội dung thi <?php echo $study_plan ? 'theo <a href="#lo-trinh-on">lộ trình ôn của đợt</a>' : 'trong <a href="' . esc_url( cvc_documents_url() ) . '">kho tài liệu ôn thi</a>'; ?>.</span></li>
				<li><b>Luyện đề và theo dõi điểm yếu</b><span>Làm <a href="<?php echo esc_url( cvc_exams_url() ); ?>">đề thi thử</a>, xem phân tích kết quả và điều chỉnh kế hoạch ôn tập.</span></li>
			</ol>
			<?php if ( $can_cq ) : ?>
				<?php echo $cq_btn( '＋ Lưu vào Hồ sơ chinh phục kỳ thi', 'rd-btn is-primary is-lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<a class="rd-btn is-primary is-lg" href="<?php echo esc_url( add_query_arg( 'con-han', 1, cvc_positions_url() ) ); ?>">Tìm vị trí đang nhận hồ sơ →</a>
			<?php endif; ?>
		</div>
	</section>

	</div><!-- /.rd-col -->

	<!-- ============ CỘT PHẢI ============ -->
	<aside class="rd-side" aria-label="Thông tin nhanh">
		<div class="rd-scard is-gold">
			<h2><span class="rd-ico is-gold" aria-hidden="true">◷</span>Thời hạn quan trọng</h2>
			<?php if ( $countdown ) : ?>
				<p class="rd-dl"><?php echo esc_html( cvc_format_date_vn( $deadline ) ); ?></p>
				<p class="rd-muted rd-small">Hạn cuối nhận hồ sơ (hết ngày, giờ Việt Nam).</p>
				<div class="rd-count" data-rd-deadline="<?php echo esc_attr( $deadline . 'T23:59:59+07:00' ); ?>">
					<div><b data-u="d"><?php echo (int) $days_left; ?></b><span>Ngày</span></div>
					<div><b data-u="h">—</b><span>Giờ</span></div>
					<div><b data-u="m">—</b><span>Phút</span></div>
				</div>
			<?php elseif ( $is_open ) : ?>
				<p class="rd-dl"><?php echo esc_html( '' !== $deadline ? cvc_format_date_vn( $deadline ) : 'Chưa xác minh' ); ?></p>
				<p class="rd-muted rd-small">Không hiển thị đếm ngược khi hạn nộp chưa có trích dẫn từ nguồn chính thức. Đối chiếu văn bản gốc.</p>
			<?php else : ?>
				<p class="rd-dl is-off"><?php echo '' !== $deadline ? esc_html( cvc_format_date_vn( $deadline ) ) : 'Đã kết thúc'; ?></p>
				<p class="rd-muted rd-small"><?php echo $in_progress ? esc_html( $st_label ) . '.' : 'Đã hết hạn nhận hồ sơ.'; ?></p>
			<?php endif; ?>
			<?php if ( $is_open && $rid > 0 ) : ?>
				<a class="rd-btn is-block" href="#lien-quan">◷ Nhắc hạn · lưu cơ hội</a>
			<?php endif; ?>
		</div>

		<div class="rd-scard">
			<h2><span class="rd-ico is-gold" aria-hidden="true">✓</span>Điểm xác minh</h2>
			<p class="rd-vmini <?php echo esc_attr( $vv['class'] ); ?>"><b><?php echo esc_html( $vv['label'] ); ?></b></p>
			<div class="rd-bar <?php echo esc_attr( $vv['class'] ); ?>"><span style="width:<?php echo (int) ( $vv['score'] ?? 0 ); ?>%"></span></div>
			<p class="rd-small"><b><?php echo null !== $vv['score'] ? (int) $vv['score'] . '/100' : '—'; ?></b> · Điểm bằng chứng</p>
			<a class="rd-btn is-block" href="#xac-minh">Xem tiêu chí xác minh ↓</a>
		</div>

		<?php if ( '' !== $agency_name ) : ?>
			<div class="rd-scard">
				<h2><span class="rd-ico" aria-hidden="true">▥</span>Đơn vị tuyển dụng</h2>
				<p class="rd-agency"><?php echo esc_html( $agency_name ); ?></p>
				<?php if ( '' !== $location ) : ?><p class="rd-small rd-muted"><?php echo esc_html( $location ); ?></p><?php endif; ?>
				<?php foreach ( array( 'address' => 'Địa chỉ', 'phone' => 'Điện thoại', 'email' => 'Email' ) as $f => $lbl ) : ?>
					<?php if ( ! empty( $agency[ $f ] ) ) : ?><p class="rd-small"><span class="rd-muted"><?php echo esc_html( $lbl ); ?>:</span> <?php echo esc_html( (string) $agency[ $f ] ); ?></p><?php endif; ?>
				<?php endforeach; ?>
				<?php if ( ! empty( $agency['website'] ) ) : ?><a class="rd-btn is-block" href="<?php echo esc_url( (string) $agency['website'] ); ?>" target="_blank" rel="noopener nofollow">Website cơ quan ↗</a><?php endif; ?>
			</div>
		<?php endif; ?>

		<nav class="rd-scard" aria-label="Trên trang này">
			<h2><span class="rd-ico" aria-hidden="true">▣</span>Liên quan đến ôn thi</h2>
			<a class="rd-navitem" href="#tai-lieu"><span aria-hidden="true">▤</span><b>Tài liệu cần đọc<small>Thông báo và <?php echo count( $attachments ); ?> tệp đính kèm</small></b></a>
			<a class="rd-navitem" href="#vi-tri"><span aria-hidden="true">☷</span><b>Danh sách vị trí<small><?php echo esc_html( count( $positions ) . ' vị trí · lọc theo mã, chuyên ngành' ); ?></small></b></a>
			<?php if ( $study_plan ) : ?><a class="rd-navitem" href="#lo-trinh-on"><span aria-hidden="true">◎</span><b>Lộ trình ôn của đợt<small>Theo danh mục tài liệu của Hội đồng</small></b></a><?php endif; ?>
			<a class="rd-navitem" href="#chuan-bi"><span aria-hidden="true">✓</span><b>Lộ trình chuẩn bị<small>Ba bước trước khi luyện đề</small></b></a>
			<?php if ( ! empty( $province['slug'] ) ) : ?>
				<a class="rd-navitem" href="<?php echo esc_url( add_query_arg( array( 'tinh' => $province['slug'], 'con-han' => 1 ), cvc_positions_url() ) ); ?>"><span aria-hidden="true">⌖</span><b>Vị trí khác tại <?php echo esc_html( (string) ( $province['name'] ?? '' ) ); ?><small>Đang nhận hồ sơ</small></b></a>
			<?php endif; ?>
		</nav>

		<?php if ( $positions ) : ?><div class="rd-legacy is-side"><?php cvc_render_eligibility_widget( $slug ); ?></div><?php endif; ?>

		<?php if ( $courses ) : ?>
			<div class="rd-scard">
				<h2><span class="rd-ico" aria-hidden="true">▣</span>Khóa ôn thi cho đợt này</h2>
				<?php foreach ( $courses as $course ) : ?>
					<?php
					$price = (float) ( $course['price'] ?? 0 );
					$sale  = isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : null;
					?>
					<a class="rd-navitem" href="<?php echo esc_url( cvc_course_url( (string) ( $course['slug'] ?? '' ) ) ); ?>"><span aria-hidden="true">▶</span><b><?php echo esc_html( (string) ( $course['title'] ?? '' ) ); ?>
						<small><?php echo $price <= 0 ? 'Miễn phí' : ( null !== $sale && $sale < $price ? '<s>' . esc_html( $money( $price ) ) . '</s> ' . esc_html( $money( $sale ) ) : esc_html( $money( $price ) ) ); ?></small></b></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="rd-scard">
			<h2><span class="rd-ico is-gold" aria-hidden="true">ⓘ</span>Gợi ý nhanh</h2>
			<p class="rd-small rd-muted">Đừng chỉ dựa vào bản tóm tắt. Hãy kiểm tra điều kiện, thời hạn và phụ lục trong văn bản gốc trước khi chuẩn bị hồ sơ; khi có điều chỉnh, cơ quan sẽ ban hành văn bản mới.</p>
		</div>
	</aside>
	</div><!-- /.rd-layout -->

</div>
</main>
<?php
get_footer();
