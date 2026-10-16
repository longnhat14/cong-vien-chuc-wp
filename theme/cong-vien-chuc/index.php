<?php
/**
 * Trang chủ - giao diện "văn bản" (16/10/2026): một cột nội dung chính
 * dạng mục lục + cột phụ; mọi số liệu đọc thật từ API.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$featured_courses = [];

$featured_result = ( new CVC_Course_Service() )->list( array(
	'featured' => 1,
	'per_page' => 4,
) );

if ( $featured_result['ok'] && ! empty( $featured_result['data']['data']['data'] ) && is_array( $featured_result['data']['data']['data'] ) ) {
	$featured_courses = $featured_result['data']['data']['data'];
}

/**
 * Nhãn khuyến mãi tính từ dữ liệu giá THẬT (price/sale_price) - không
 * gắn cứng % như trước đây. Trả null nếu không có giảm giá thật, để nơi
 * gọi ẩn hẳn badge thay vì hiện "Giảm 0%".
 */
function cvc_homepage_course_discount_badge( array $course ): ?string {
	if ( ! empty( $course['is_coming_soon'] ) ) {
		return null;
	}
	$price = (float) ( $course['price'] ?? 0 );
	$sale  = isset( $course['sale_price'] ) && null !== $course['sale_price'] ? (float) $course['sale_price'] : $price;

	if ( $price <= 0 || $sale >= $price ) {
		return null;
	}

	$percent = (int) round( ( 1 - ( $sale / $price ) ) * 100 );

	return $percent > 0 ? "Giảm {$percent}%" : null;
}

$course_type_badge_labels = array(
	'online_video' => 'Video HD',
	'live_zoom'    => 'Live Zoom',
);
?>

<?php $cvc_combo = cvc_get_combo_courses(); ?>

<?php
/**
 * Số liệu thật cho hero/metrics strip - thay cho các con số bịa trước
 * đây (99.8%, 120.000+, 36.035+, 3.321 xã phường...). count() có sẵn ở
 * CVC_Api_Service (GET per_page=1, đọc field total) - không fabricate.
 */
// Chi tinh khoa hoc da co bai giang (ban duoc) - khoa "Sap mo" khong tinh la "dang mo".
$cvc_sellable_res      = ( new CVC_Course_Service() )->list( array( 'sellable' => 1, 'per_page' => 1 ) );
$cvc_stat_courses      = $cvc_sellable_res['ok'] ? (int) ( $cvc_sellable_res['data']['data']['total'] ?? 0 ) : 0;
$cvc_stat_recruitments = ( new CVC_Recruitment_Service() )->count() ?? 0;
$cvc_stat_documents    = ( new CVC_Document_Service() )->count() ?? 0;
$cvc_stat_legal_docs   = ( new CVC_Legal_Document_Service() )->count() ?? 0;
$cvc_stat_topics       = ( new CVC_Topic_Service() )->count() ?? 0;

/*
 * Homepage Next-Gen (Phase 11) - dữ liệu thật cho Live Pulse, Career
 * Roadmap, Smart Exam, Legal Matrix (xem inc/homepage-nextgen.php).
 */
$cvc_home_service = new CVC_Homepage_Service();
$cvc_pulse_result = $cvc_home_service->pulse();
$cvc_pulse        = $cvc_pulse_result['ok'] ? ( $cvc_pulse_result['data']['data'] ?? null ) : null;
$cvc_stat_exams   = (int) ( $cvc_pulse['live']['published_exams'] ?? 0 );
$cvc_roadmap_res  = $cvc_home_service->careerRoadmap();
$cvc_roadmap      = $cvc_roadmap_res['ok'] ? ( $cvc_roadmap_res['data']['data'] ?? array() ) : array();
$cvc_exams_res    = ( new CVC_Exam_Service() )->list( array( 'per_page' => 4 ) );
$cvc_latest_exams = $cvc_exams_res['ok'] ? ( $cvc_exams_res['data']['data']['data'] ?? array() ) : array();
$cvc_docs_res     = ( new CVC_Document_Service() )->list( array( 'per_page' => 50 ) );
$cvc_form_docs    = array_slice(
	array_values(
		array_filter(
			$cvc_docs_res['ok'] ? ( $cvc_docs_res['data']['data']['data'] ?? array() ) : array(),
			fn ( $d ) => 'mau-don' === ( $d['category'] ?? '' )
		)
	),
	0,
	5
);

$cvc_latest_recruitments = [];
$cvc_recruitment_result  = ( new CVC_Recruitment_Service() )->list( array( 'per_page' => 8 ) );
if ( $cvc_recruitment_result['ok'] && ! empty( $cvc_recruitment_result['data']['data']['data'] ) && is_array( $cvc_recruitment_result['data']['data']['data'] ) ) {
	$cvc_latest_recruitments = $cvc_recruitment_result['data']['data']['data'];
}
?>

<main id="main" class="dz-main dz-home">
<div class="dz-wrap">

<?php if ( null !== $cvc_combo ) : ?>
<p class="dz-notice"><strong>Ưu đãi:</strong> mua trọn bộ khóa ôn thi Vòng 1 &amp; Vòng 2 tiết kiệm <?php echo esc_html( $cvc_combo['discount_percent'] ); ?>%. <a class="dz-link" href="#combo-hot">Xem chi tiết</a></p>
<?php endif; ?>

<!-- Mở đầu -->
<section class="dz-lead" aria-labelledby="dz-lead-title">
	<p class="dz-kicker">Cổng ôn thi công chức, viên chức</p>
	<h1 id="dz-lead-title">Ôn thi công chức, viên chức theo văn bản pháp luật hiện hành</h1>
	<p class="dz-lead-sub">Tổng hợp <?php echo $cvc_stat_recruitments > 0 ? '<strong>' . esc_html( number_format( $cvc_stat_recruitments, 0, ',', '.' ) ) . '</strong> ' : ''; ?>tin tuyển dụng từ cổng thông tin chính thức, đề thi trắc nghiệm có chấm điểm và giải thích, văn bản pháp luật và mẫu hồ sơ dự tuyển.</p>
	<span class="dz-rule" aria-hidden="true"></span>
	<form class="dz-searchform dz-lead-search" method="get" action="<?php echo esc_url( home_url( '/tim-kiem/' ) ); ?>" role="search">
		<label class="sr-only" for="hero-search-query">Tìm kiếm</label>
		<input type="search" id="hero-search-query" name="q" placeholder="Tìm đề thi, văn bản, tin tuyển dụng… (ví dụ: Luật Viên chức)">
		<button type="submit" class="dz-btn dz-btn-primary">Tìm kiếm</button>
	</form>
	<div class="dz-chips dz-lead-chips">
		<a href="<?php echo esc_url( cvc_search_url( 'Thi Chuyên viên chính 2026' ) ); ?>">Thi Chuyên viên chính 2026</a>
		<a href="<?php echo esc_url( cvc_search_url( 'Ôn thi Vòng 1 Kiến thức chung' ) ); ?>">Ôn thi Vòng 1 Kiến thức chung</a>
		<a href="<?php echo esc_url( cvc_search_url( 'Luật Cán bộ, công chức' ) ); ?>">Luật Cán bộ, công chức</a>
		<a href="<?php echo esc_url( cvc_search_url( 'Tuyển dụng giáo viên' ) ); ?>">Tuyển dụng giáo viên</a>
	</div>
	<table class="dz-stats" aria-label="Số liệu hiện có trên hệ thống">
		<tr>
			<td><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>"><strong><?php echo esc_html( number_format( $cvc_stat_recruitments, 0, ',', '.' ) ); ?></strong><span>tin tuyển dụng</span></a></td>
			<td><a href="<?php echo esc_url( cvc_exams_url() ); ?>"><strong><?php echo esc_html( number_format( $cvc_stat_exams, 0, ',', '.' ) ); ?></strong><span>đề thi thử</span></a></td>
			<td><a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>"><strong><?php echo esc_html( number_format( $cvc_stat_legal_docs, 0, ',', '.' ) ); ?></strong><span>văn bản pháp luật</span></a></td>
			<td><a href="<?php echo esc_url( cvc_topics_url() ); ?>"><strong><?php echo esc_html( number_format( $cvc_stat_topics, 0, ',', '.' ) ); ?></strong><span>chuyên đề ôn tập</span></a></td>
		</tr>
	</table>
	<?php cvc_render_hero_tools(); ?>
</section>

<div class="dz-cols">
<div class="dz-colmain">

<?php if ( ! empty( $cvc_latest_recruitments ) ) : ?>
<section id="tuyen-dung-moi" class="dz-section" aria-labelledby="tuyen-dung-title">
	<div class="dz-h2"><h2 id="tuyen-dung-title">Thông báo tuyển dụng mới</h2><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>"><?php echo $cvc_stat_recruitments > 0 ? 'Tất cả ' . esc_html( number_format( $cvc_stat_recruitments, 0, ',', '.' ) ) . ' tin →' : 'Trang tuyển dụng →'; ?></a></div>
	<ul class="dz-list">
	<?php foreach ( $cvc_latest_recruitments as $rc ) :
		$rc_agency   = is_array( $rc['agency'] ?? null ) ? ( $rc['agency']['name'] ?? '' ) : '';
		$rc_deadline = ! empty( $rc['dates']['application_deadline'] ) ? cvc_format_date_vn( $rc['dates']['application_deadline'] ) : '';
		$rc_quota    = isset( $rc['total_positions'] ) ? (int) $rc['total_positions'] : 0;
		$rc_type     = ! empty( $rc['recruitment_type'] ) && 'other' !== $rc['recruitment_type'] ? cvc_recruitment_type_label( $rc['recruitment_type'] ) : '';
	?>
		<li>
			<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_recruitment_url( $rc['slug'] ?? '' ) ); ?>"><?php echo esc_html( $rc['title'] ?? '' ); ?></a></h3>
			<div class="dz-item-meta">
				<?php if ( $rc_type ) : ?><span class="dz-tag"><?php echo esc_html( $rc_type ); ?></span><?php endif; ?>
				<?php if ( $rc_agency ) : ?><span><?php echo esc_html( $rc_agency ); ?></span><?php endif; ?>
				<?php if ( $rc_quota > 0 ) : ?><span>Chỉ tiêu: <strong><?php echo esc_html( number_format( $rc_quota, 0, ',', '.' ) ); ?></strong></span><?php endif; ?>
				<?php if ( $rc_deadline ) : ?><span>Hạn nộp: <strong><?php echo esc_html( $rc_deadline ); ?></strong></span><?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<?php
$featured_courses = array_values( array_filter( $featured_courses, fn ( $c ) => empty( $c['is_coming_soon'] ) ) );
if ( count( $featured_courses ) < 3 ) :
	$cvc_home_exclude = array( 'legal' ); // Trang chủ đã có mục "Văn bản mới".
	if ( ! empty( $cvc_latest_recruitments ) ) :
		$cvc_home_exclude[] = 'recruitments';
	endif;
	$cvc_home_slot = cvc_content_slot( 'home_featured', $cvc_home_exclude, array_map( fn ( $e ) => (int) ( $e['id'] ?? 0 ), $cvc_latest_exams ), 4 );
	if ( null !== $cvc_home_slot ) :
		cvc_render_content_slot_section( $cvc_home_slot, array( 'id' => 'khoa-hoc-noi-bat' ) );
	endif;
else :
?>
<section id="khoa-hoc-noi-bat" class="dz-section" aria-labelledby="khoa-hoc-title">
	<div class="dz-h2"><h2 id="khoa-hoc-title">Khóa học ôn thi</h2><a href="<?php echo esc_url( cvc_courses_url() ); ?>">Tất cả khóa học →</a></div>
	<ul class="dz-list">
	<?php foreach ( $featured_courses as $c ) :
		$c_price   = (float) ( $c['price'] ?? 0 );
		$c_sale    = isset( $c['sale_price'] ) && null !== $c['sale_price'] ? (float) $c['sale_price'] : $c_price;
		$c_badge   = cvc_homepage_course_discount_badge( $c );
		$c_lessons = (int) ( $c['published_lessons_count'] ?? $c['lesson_count'] ?? 0 );
		$c_type    = $course_type_badge_labels[ $c['course_type'] ?? '' ] ?? 'Khóa học';
	?>
		<li class="dz-row">
			<div>
				<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_course_url( $c['slug'] ?? '' ) ); ?>"><?php echo esc_html( $c['title'] ?? '' ); ?></a></h3>
				<div class="dz-item-meta">
					<span class="dz-tag"><?php echo esc_html( $c_type ); ?></span>
					<?php if ( $c_lessons > 0 ) : ?><span><?php echo (int) $c_lessons; ?> bài học</span><?php endif; ?>
					<?php if ( $c_badge ) : ?><span class="dz-tag is-warn"><?php echo esc_html( $c_badge ); ?></span><?php endif; ?>
				</div>
			</div>
			<div class="dz-price">
				<?php if ( $c_sale <= 0 ) : ?><strong>Miễn phí</strong><?php else : ?><strong><?php echo esc_html( number_format( $c_sale, 0, ',', '.' ) ); ?>đ</strong><?php if ( $c_sale < $c_price ) : ?><s><?php echo esc_html( number_format( $c_price, 0, ',', '.' ) ); ?>đ</s><?php endif; ?><?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<?php if ( null !== $cvc_combo ) : ?>
<section id="combo-hot" class="dz-section" aria-labelledby="combo-title">
	<div class="dz-h2"><h2 id="combo-title">Combo ôn thi Vòng 1 &amp; Vòng 2</h2></div>
	<div class="dz-box dz-combo">
		<div class="dz-box-body">
			<p style="margin:0 0 8px">Mua gộp các khóa học sau trong một đơn hàng, tiết kiệm <strong><?php echo esc_html( $cvc_combo['discount_percent'] ); ?>%</strong> so với mua lẻ:</p>
			<table class="dz-table">
				<tbody>
				<?php foreach ( $cvc_combo['courses'] as $combo_course ) :
					$cc_sale = isset( $combo_course['sale_price'] ) && null !== $combo_course['sale_price'] ? (float) $combo_course['sale_price'] : (float) $combo_course['price'];
				?>
					<tr><td><a class="dz-rowlink" href="<?php echo esc_url( cvc_course_url( $combo_course['slug'] ) ); ?>"><?php echo esc_html( $combo_course['title'] ); ?></a></td><td class="num"><?php echo esc_html( number_format( $cc_sale, 0, ',', '.' ) ); ?>đ</td></tr>
				<?php endforeach; ?>
					<tr><td>Tổng mua lẻ</td><td class="num"><s><?php echo esc_html( number_format( $cvc_combo['total_price'], 0, ',', '.' ) ); ?>đ</s></td></tr>
					<tr class="is-strong"><td><strong>Giá combo (-<?php echo esc_html( $cvc_combo['discount_percent'] ); ?>%)</strong></td><td class="num"><strong><?php echo esc_html( number_format( $cvc_combo['total_sale_price'], 0, ',', '.' ) ); ?>đ</strong></td></tr>
				</tbody>
			</table>
			<div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
			<?php if ( cvc_is_logged_in() ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'cvc_combo_buy' ); ?>
					<input type="hidden" name="action" value="cvc_combo_buy">
					<button type="submit" class="dz-btn dz-btn-primary">Mua combo — thanh toán VNPay</button>
				</form>
			<?php else : ?>
				<a href="<?php echo esc_url( cvc_login_url( home_url( '/#combo-hot' ) ) ); ?>" class="dz-btn dz-btn-primary">Đăng nhập để mua combo</a>
			<?php endif; ?>
				<span class="dz-muted">Truy cập trọn đời · Thanh toán qua VNPay</span>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
cvc_render_smart_exam_section( $cvc_latest_exams );
cvc_render_legal_matrix( $cvc_pulse['new_legal_documents'] ?? array(), $cvc_form_docs );
cvc_render_career_roadmap( $cvc_roadmap );
cvc_render_salary_calculator();
?>
</div>

<aside class="dz-colside" aria-label="Thông tin bổ trợ">
	<?php cvc_render_live_pulse( $cvc_pulse ); ?>
	<section class="dz-box" aria-labelledby="dz-lib-title">
		<h2 id="dz-lib-title" class="dz-box-head">Tra cứu</h2>
		<div class="dz-box-body">
			<ul class="dz-toc">
				<li><a href="<?php echo esc_url( cvc_recruitments_url() ); ?>">Tin tuyển dụng công chức, viên chức</a></li>
				<li><a href="<?php echo esc_url( cvc_exams_url() ); ?>">Đề thi thử trắc nghiệm</a></li>
				<li><a href="<?php echo esc_url( cvc_topics_url() ); ?>">Chủ đề ôn thi</a></li>
				<li><a href="<?php echo esc_url( cvc_knowledge_url() ); ?>">Kiến thức trọng tâm</a></li>
				<li><a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Văn bản pháp luật</a></li>
				<li><a href="<?php echo esc_url( cvc_documents_url() ); ?>">Mẫu hồ sơ &amp; tài liệu</a></li>
			</ul>
		</div>
	</section>
	<section class="dz-box" aria-labelledby="dz-acc-title">
		<h2 id="dz-acc-title" class="dz-box-head">Tài khoản học viên</h2>
		<div class="dz-box-body">
			<p style="margin:0 0 10px">Lưu lịch sử làm bài, nhận gợi ý ôn tập và thông báo đợt tuyển dụng phù hợp mục tiêu.</p>
			<?php if ( cvc_is_logged_in() ) : ?>
				<a class="dz-btn dz-btn-primary" href="<?php echo esc_url( cvc_account_url() ); ?>">Vào tài khoản</a>
			<?php else : ?>
				<a class="dz-btn dz-btn-primary" href="<?php echo esc_url( cvc_register_url() ); ?>">Đăng ký miễn phí</a>
				<a class="dz-btn" href="<?php echo esc_url( cvc_login_url() ); ?>">Đăng nhập</a>
			<?php endif; ?>
		</div>
	</section>
</aside>
</div>

</div>
</main>

<?php get_footer(); ?>
