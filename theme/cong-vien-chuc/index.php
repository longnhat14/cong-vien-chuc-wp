<?php
/**
 * Trang chủ v17 (16/10/2026) - dựng theo file thiết kế
 * congvienchuc-homepage-redesign-v17 (bố cục, hiệu ứng, tông màu giữ
 * nguyên), nối dữ liệu thật từ /api/homepage/overview. Trang độc lập:
 * không dùng header.php/footer.php chung, chỉ nạp home-v17.css/js
 * (xem inc/home-v17.php).
 *
 * Khu "Khóa học ôn thi" (#khoa-hoc) để sẵn chỗ cho khóa học: có khóa bán
 * được -> thẻ khóa học; chưa có -> đăng ký nhận tin + danh sách "Sắp mở".
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_notice = cvc_consume_notice(); // đọc trước khi xuất HTML (cookie).
$home       = cvc_home17_data();
$counts     = (array) $home['counts'];
$user       = cvc_current_user();
$logged_in  = null !== $user;

$u = array(
	'home'     => home_url( '/' ),
	'exams'    => cvc_exams_url(),
	'docs'     => cvc_documents_url(),
	'legal'    => cvc_legal_documents_url(),
	'rec'      => cvc_recruitments_url(),
	'topics'   => cvc_topics_url(),
	'courses'  => cvc_courses_url(),
	'search'   => cvc_search_url(),
	'salary'   => home_url( '/tinh-luong/' ),
	'login'    => cvc_login_url(),
	'register' => cvc_register_url(),
	'account'  => cvc_account_url(),
	'logout'   => cvc_logout_url(),
);

cvc_seo_set_title( 'Công Viên Chức — Ôn thi vững vàng, sự nghiệp bền lâu' );
cvc_seo_set_description( 'Tìm đề thi thử, tài liệu ôn thi công chức viên chức, văn bản pháp luật và tin tuyển dụng mới nhất. Học có lộ trình, luyện đề có kết quả.' );
cvc_seo_set_canonical( $u['home'] );

$e = static function ( $s ) {
	echo esc_html( (string) $s );
};
$a = static function ( $url ) {
	echo esc_url( (string) $url );
};

/* ---------- Dòng tin chạy: tuyển dụng · văn bản · đề thi ---------- */
$ticker = array();
$recs   = array_slice( (array) $home['recruitments'], 0, 6 );
$laws   = array_slice( (array) $home['legal_documents'], 0, 4 );
$exams  = array_slice( (array) $home['exams'], 0, 6 );
$groups = array(
	array_map( static fn( $r ) => array( 'job', 'TUYỂN DỤNG', cvc_home17_trim( $r['title'] ?? '', 120 ), cvc_recruitment_url( (string) ( $r['slug'] ?? '' ) ) ), array_slice( $recs, 0, 2 ) ),
	array_map( static fn( $l ) => array( 'law', 'VĂN BẢN', cvc_home17_trim( $l['title'] ?? '', 120 ), cvc_legal_document_url( (string) ( $l['slug'] ?? '' ) ) ), array_slice( $laws, 0, 2 ) ),
	array_map(
		static fn( $x ) => array(
			'exam',
			'ĐỀ THI',
			cvc_home17_trim( $x['title'] ?? '', 100 ) . ( ! empty( $x['total_questions'] ) ? ' — ' . (int) $x['total_questions'] . ' câu hỏi' . ( ! empty( $x['duration_minutes'] ) ? ', ' . (int) $x['duration_minutes'] . ' phút' : '' ) : '' ),
			cvc_exam_url( (string) ( $x['slug'] ?? '' ) ),
		),
		array_slice( $exams, 0, 2 )
	),
);
foreach ( $groups as $g ) {
	foreach ( $g as $item ) {
		$ticker[] = $item;
	}
}
if ( count( $ticker ) < 3 ) {
	$ticker[] = array( 'exam', 'ĐỀ THI', 'Đề thi trắc nghiệm có chấm điểm tự động — luyện tập miễn phí', $u['exams'] );
	$ticker[] = array( 'law', 'VĂN BẢN', 'Tra cứu văn bản pháp luật về tuyển dụng công chức, viên chức', $u['legal'] );
	$ticker[] = array( 'job', 'TUYỂN DỤNG', 'Theo dõi thông báo tuyển dụng công chức, viên chức trên cả nước', $u['rec'] );
}

/* ---------- Tỉnh/thành cho "Việc làm quanh bạn" ---------- */
$provinces = array();
foreach ( (array) $home['provinces'] as $p ) {
	if ( empty( $p['id'] ) ) {
		continue;
	}
	$provinces[] = array(
		'id'                => (int) $p['id'],
		'name'              => (string) ( $p['name'] ?? '' ),
		'short'             => (string) ( $p['short'] ?? $p['name'] ?? '' ),
		'open_recruitments' => (int) ( $p['open_recruitments'] ?? 0 ),
	);
}
$hot_provinces = array_filter( $provinces, static fn( $p ) => $p['open_recruitments'] > 0 );
usort( $hot_provinces, static fn( $x, $y ) => $y['open_recruitments'] <=> $x['open_recruitments'] );

$formats = (array) $home['document_formats'];
$courses = (array) $home['courses'];
$sellable = array_values( (array) ( $courses['sellable'] ?? array() ) );
$soon     = array_values( (array) ( $courses['coming_soon'] ?? array() ) );

$exam_type_label = static function ( $type ) {
	return 'mock' === $type ? 'Đề thi thử' : ( 'practice' === $type ? 'Luyện tập' : 'Miễn phí' );
};

/* Biểu tượng dùng lặp lại (giữ nguyên nét vẽ của thiết kế). */
$ico_arrow = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
$ico_check = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>';
$ico_brand = '<svg width="27" height="27" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M7 23V9h4.4l4.6 7 4.6-7H25v14h-4V15l-5 7-5-7v8H7Z" fill="#E9C98E"/><path d="M6 27h20" stroke="#E9C98E" stroke-width="1.5" stroke-linecap="round"/></svg>';
$ico_doc   = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h10l4 4v14H5z"/><path d="M14 3v5h5M8 13h8M8 17h6"/></svg>';
$ico_exam  = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h2m3 0h3M8 15h2m3 0h3"/></svg>';
$ico_files = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h10l4 4v14H5z"/><path d="M14 3v5h5M8 13h8M8 17h8"/></svg>';
$ico_law   = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6M9 15h6"/></svg>';
$ico_job   = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12h18M10 12v2h4v-2"/></svg>';
$ico_gov   = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 20h18M5 20V8l7-4 7 4v12M9 20v-6h6v6"/></svg>';
$ico_spark = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3.5 14.2 9l5.3 2.2-5.3 2.1L12 19l-2.2-5.7L4.5 11.2 9.8 9 12 3.5Z"/><path d="m18.5 15.5.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9.9-2.1Z"/></svg>';

// Cấu hình cho home-v17.js - in kèm script trong cvc_home17_assets() (chạy trong wp_head()).
$GLOBALS['cvc_home17_cfg'] = array(
	'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
	'nonce'           => wp_create_nonce( 'cvc_home' ),
	'homeUrl'         => $u['home'],
	'recruitmentsUrl' => $u['rec'],
	'provinces'       => $provinces,
);
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#102846">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'cvc-home17' ); ?>>
<?php wp_body_open(); ?>
<?php cvc_chrome_header(); // inc/chrome.php - đầu trang dùng chung toàn site. ?>

<div class="sticky-news-ticker" aria-label="Tin tuyển dụng, văn bản pháp luật và đề thi mới">
 <div class="ticker-label"><span class="ticker-live"></span><span>TIN MỚI</span></div>
 <div class="ticker-window single-ticker-window">
  <div class="single-ticker-track">
   <?php for ( $loop = 0; $loop < 2; $loop++ ) : foreach ( $ticker as $t ) : ?>
   <a class="ticker-item <?php echo esc_attr( $t[0] ); ?>" href="<?php $a( $t[3] ); ?>"<?php echo $loop ? ' aria-hidden="true" tabindex="-1"' : ''; ?>><span class="ticker-kind"><?php $e( $t[1] ); ?></span><strong><?php $e( $t[2] ); ?></strong><b>↗</b></a><span class="ticker-separator">◆</span>
   <?php endforeach; endfor; ?>
  </div>
 </div>
</div>

<section class="benefit-band" aria-label="Điểm nổi bật của Công Viên Chức">
 <div class="container benefit-grid">
  <a class="benefit-card ai" href="#aiChatPanel" data-open-ai aria-label="AI hỗ trợ ôn thi và tra cứu">
   <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1"/><circle cx="12" cy="12" r="5.5"/><path d="m10 12 1.4 1.4L14.5 10"/></svg></span>
   <span class="benefit-copy"><strong>AI hỗ trợ thông minh</strong><small>Giải đáp, gợi ý và đồng hành ôn tập</small></span>
  </a>
  <a class="benefit-card" href="<?php $a( $u['exams'] ); ?>" aria-label="Cá nhân hóa đề thi">
   <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h2m3 0h3M8 15h2m3 0h3"/><path d="m14 18 1.5 1.5L19 16"/></svg></span>
   <span class="benefit-copy"><strong>Đề thi cá nhân hóa</strong><small>Luyện theo mục tiêu và phần kiến thức</small></span>
  </a>
  <a class="benefit-card law" href="<?php $a( $u['legal'] ); ?>" aria-label="Văn bản pháp luật mới nhất">
   <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 16h7"/><path d="m15 10 1.5 1.5L19 9"/></svg></span>
   <span class="benefit-copy"><strong>Văn bản mới nhất</strong><small>Tra cứu quy định và cập nhật pháp luật</small></span>
  </a>
  <a class="benefit-card jobs" href="<?php $a( $u['rec'] ); ?>" aria-label="Tin tuyển dụng cập nhật nhanh">
   <span class="benefit-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2"/><path d="M18 3v3m-1.5-1.5h3"/></svg></span>
   <span class="benefit-copy"><strong>Tuyển dụng cập nhật nhanh</strong><small>Theo dõi cơ hội công chức, viên chức</small></span>
  </a>
 </div>
</section>

<main id="main-content">
<section class="hero">
 <div class="container hero-grid">
  <div>
   <span class="eyebrow"><span class="dot"></span> NỀN TẢNG ÔN THI &amp; PHÁT TRIỂN SỰ NGHIỆP</span>
   <h1>Chuẩn bị hôm nay.<br><em>Tự tin ngày mai.</em></h1>
   <p class="hero-copy">Tìm đúng kỳ thi, luyện tập theo cấu trúc đề và hệ thống hóa kiến thức pháp luật — để mỗi giờ ôn tập đều đưa bạn tiến gần hơn đến mục tiêu công chức, viên chức.</p>
   <div class="hero-actions">
    <a class="btn btn-gold" href="<?php $a( $u['exams'] ); ?>">Bắt đầu luyện đề <?php echo $ico_arrow; // phpcs:ignore ?></a>
    <a class="btn btn-outline" href="<?php $a( $u['docs'] ); ?>"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h10l4 4v14H5z"/><path d="M14 3v5h5M8 13h8M8 17h8"/></svg> Khám phá tài liệu</a>
   </div>
   <form class="search-box" id="siteSearch" role="search" method="get" action="<?php $a( $u['search'] ); ?>">
    <span class="search-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg></span>
    <input id="searchInput" name="q" type="search" placeholder="Bạn muốn ôn thi, tìm tài liệu hay tra cứu văn bản?" aria-label="Tìm kiếm nội dung">
    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
   </form>
   <div class="hot-search"><strong>Tìm nhanh:</strong><a href="<?php $a( $u['exams'] ); ?>">Đề thi Vòng 1</a><a href="<?php $a( $u['docs'] ); ?>">Mẫu hồ sơ</a><a href="<?php $a( $u['legal'] ); ?>">Văn bản mới</a></div>
   <div class="trust-row" style="margin-top:22px">
    <span><?php echo $ico_check; // phpcs:ignore ?> Có đề luyện miễn phí</span>
    <span><?php echo $ico_check; // phpcs:ignore ?> Chấm điểm tự động</span>
    <span><?php echo $ico_check; // phpcs:ignore ?> Học theo mục tiêu</span>
   </div>
  </div>
  <div class="hero-visual roadmap-hero" aria-label="Minh họa hành trình ôn thi">
   <div class="visual-photo"></div>
   <div class="visual-content">
    <div><div class="visual-kicker">LỘ TRÌNH CỦA BẠN</div><div class="visual-title">Học có định hướng.<br>Ôn thi có chiến lược.</div><p class="visual-sub">Bắt đầu từ kiến thức nền, luyện đề theo thời gian và nhận biết phần cần củng cố.</p></div>
    <div class="progress-card">
     <div class="progress-head"><strong><svg class="icon" style="vertical-align:middle;color:#b1843c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7"/></svg> Tiến độ ôn tập</strong><span class="pill">Lộ trình mẫu</span></div>
     <div style="font-size:16px;color:#232d3a">Kiến thức chung · Vòng 1</div>
     <div class="progress-track"><span></span></div>
     <div class="progress-foot"><span>Ôn nền tảng → Luyện đề → Rà soát</span><strong style="color:#102846">3 chặng</strong></div>
    </div>
   </div>
   <div class="floating-note"><span class="mini-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3h8M9 3v3m6-3v3M5 7h14v14H5z"/><path d="m8 14 2.5 2.5L16 11"/></svg></span><span><strong>Luyện tập chủ động</strong><small>Biết mình đã vững phần nào</small></span></div>
  </div>
 </div>
 <?php
 $stats = array(
	 array( (int) ( $counts['exams'] ?? 0 ), 'Đề thi thử &amp; luyện tập', '<path d="M5 3h10l4 4v14H5z"/><path d="M14 3v5h5M8 13h8M8 17h6"/>', $u['exams'] ),
	 array( (int) ( $counts['legal_documents'] ?? 0 ), 'Văn bản pháp luật', '<path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/>', $u['legal'] ),
	 array( (int) ( $counts['topics'] ?? 0 ), 'Chuyên đề ôn tập', '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 1 4 17.5z"/><path d="M4 6h12M8 10h8M8 14h7"/>', $u['topics'] ),
	 array( (int) ( $counts['open_recruitments'] ?? 0 ), 'Tin tuyển dụng còn hạn', '<path d="M3 20h18M5 20V8l7-4 7 4v12M9 20v-6h6v6M8 10h.01M16 10h.01"/>', $u['rec'] ),
 );
 if ( array_sum( array_column( $stats, 0 ) ) > 0 ) :
	 ?>
 <div class="container stats-wrap"><div class="stats">
  <?php foreach ( $stats as $s ) : ?>
  <div class="stat"><span class="stat-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?php echo $s[2]; // phpcs:ignore ?></svg></span><div><strong class="stat-counter" data-target="<?php echo (int) $s[0]; ?>" data-suffix=""><?php echo esc_html( number_format( $s[0], 0, ',', '.' ) ); ?></strong><small><?php echo $s[1]; // phpcs:ignore ?></small></div></div>
  <?php endforeach; ?>
 </div></div>
 <?php endif; ?>
</section>

<div class="source-marquee" aria-label="Các nguồn thông tin công vụ tham khảo">
 <div class="container">
  <div class="marquee-topline"><span class="live-dot"></span> Mạng lưới nguồn thông tin tham khảo <span style="color:#aabbd0;font-weight:normal;letter-spacing:0;text-transform:none">· Di chuyển để khám phá</span></div>
 </div>
 <div class="marquee-window">
  <div class="marquee-track">
   <?php
   $sources = array(
	   array( 'Bộ Nội vụ', 'Nguồn tham khảo về công vụ', '<path d="M12 3 20 7v5c0 5-4 8-8 9-4-1-8-4-8-9V7z"/><path d="M8 10h8M9 13h6M10 7v10"/>' ),
	   array( 'Cổng thông tin Chính phủ', 'Thông tin và chính sách công', '<path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6M8 10h.01M16 10h.01"/>' ),
	   array( 'Cơ sở dữ liệu quốc gia về VBPL', 'Tra cứu văn bản pháp luật', '<path d="M5 3h14v18H5zM8 7h8M8 11h8M8 15h6"/>' ),
	   array( 'Cổng thông tin địa phương', 'Thông báo tuyển dụng theo tỉnh', '<path d="M4 20h16M6 20V8l6-4 6 4v12M9 11h6M9 14h6"/>' ),
	   array( 'Cơ quan tuyển dụng', 'Thông báo và hướng dẫn dự tuyển', '<path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/>' ),
   );
   for ( $loop = 0; $loop < 2; $loop++ ) :
	   foreach ( $sources as $src ) :
		   ?>
   <div class="source-pill"<?php echo $loop ? ' aria-hidden="true"' : ''; ?>><span class="seal"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?php echo $src[2]; // phpcs:ignore ?></svg></span><span><strong><?php $e( $src[0] ); ?></strong><small><?php $e( $src[1] ); ?></small></span></div>
		   <?php
	   endforeach;
   endfor;
   ?>
  </div>
 </div>
 <div class="container marquee-caption">Nội dung được <strong>sưu tầm, tổng hợp và dẫn nguồn tham khảo</strong> từ các cổng thông tin công khai; vui lòng kiểm tra thông báo và văn bản gốc của cơ quan ban hành.</div>
</div>

<section style="padding-top:30px;padding-bottom:26px">
 <div class="container layout-with-sidebar">
  <div>
   <div class="section-label">LỐI TẮT THEO NHU CẦU</div>
   <h2 style="font-size:32px;margin-bottom:10px">Mở đúng khu vực bạn cần</h2>
   <p style="color:var(--muted);font-size:18px;max-width:650px;margin-bottom:20px">Đặt các hành động quan trọng ngay cạnh nội dung: tìm việc theo địa phương, vào đề thi trắc nghiệm hoặc mở kho tài liệu mà không phải tìm lại trong menu.</p>
   <div class="quick-grid">
    <a class="quick-card" href="<?php $a( $u['rec'] ); ?>"><span class="quick-icon" style="background:#fff0df;color:#8d450e"><?php echo $ico_job; // phpcs:ignore ?></span><h3>Tuyển dụng theo tỉnh</h3><p>Ưu tiên thông báo tại địa phương và khu vực lân cận.</p><span class="text-link">Tìm cơ hội <span>→</span></span></a>
    <a class="quick-card" href="<?php $a( $u['exams'] ); ?>"><span class="quick-icon"><?php echo $ico_exam; // phpcs:ignore ?></span><h3>Đề thi trắc nghiệm</h3><p>Vào nhanh ngân hàng đề, luyện tập và tự đánh giá kiến thức.</p><span class="text-link">Chọn đề thi <span>→</span></span></a>
    <a class="quick-card" href="<?php $a( $u['docs'] ); ?>"><span class="quick-icon" style="background:#e5f5f1;color:#11635e"><?php echo $ico_files; // phpcs:ignore ?></span><h3>Tài liệu ôn thi</h3><p>Tham khảo tài liệu, mẫu biểu và nội dung phục vụ ôn tập.</p><span class="text-link">Mở kho tài liệu <span>→</span></span></a>
    <a class="quick-card" href="<?php $a( $u['legal'] ); ?>"><span class="quick-icon"><?php echo $ico_law; // phpcs:ignore ?></span><h3>Văn bản pháp luật</h3><p>Tra cứu quy định liên quan đến kỳ thi và vị trí tuyển dụng.</p><span class="text-link">Tra cứu ngay <span>→</span></span></a>
   </div>

   <div class="format-showcase">
    <div class="format-heading">
     <div><span class="section-label">KHO TÀI LIỆU THÔNG MINH</span><h3>Tài liệu cần thiết, đúng định dạng bạn dùng</h3><p>Khám phá nhanh bộ tài liệu ôn thi, mẫu hồ sơ và biểu mẫu tham khảo.</p></div>
     <a class="text-link" href="<?php $a( $u['docs'] ); ?>">Xem toàn bộ kho tài liệu <span>→</span></a>
    </div>
    <div class="format-grid">
     <?php
     $format_cards = array(
	     'word'  => array( 'W', 'Word', 'Mẫu đơn &amp; hồ sơ', '<path d="M11 23h22M11 29h22M11 35h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' ),
	     'pdf'   => array( 'PDF', 'PDF', 'Tài liệu đọc nhanh', '<path d="M10 31h28v10H10z" fill="currentColor"/>' ),
	     'excel' => array( 'X', 'Excel', 'Bảng biểu &amp; dữ liệu', '<path d="M10 24h28v22H10z" fill="currentColor" opacity=".2"/><path d="M19 24v22M29 24v22M10 31h28M10 39h28" stroke="currentColor" stroke-width="1.5"/>' ),
	     'ppt'   => array( 'P', 'PowerPoint', 'Bài giảng &amp; trình bày', '<rect x="10" y="24" width="28" height="19" rx="2" fill="currentColor" opacity=".2"/><path d="M17 39V29h7a4 4 0 0 1 0 8h-3M21 37v2" fill="none" stroke="currentColor" stroke-width="2"/>' ),
     );
     foreach ( $format_cards as $key => $fc ) :
	     $n = (int) ( $formats[ $key ] ?? 0 );
	     ?>
     <a class="format-card <?php echo esc_attr( $key ); ?>" href="<?php $a( add_query_arg( 'dinh-dang', $key, $u['docs'] ) ); ?>">
      <span class="format-file">
       <svg viewBox="0 0 48 56" aria-hidden="true"><path d="M8 2h21l11 11v39a2 2 0 0 1-2 2H8a4 4 0 0 1-4-4V6a4 4 0 0 1 4-4Z" fill="currentColor" opacity=".12"/><path d="M29 2v12h11" fill="none" stroke="currentColor" stroke-width="2"/><?php echo $fc[3]; // phpcs:ignore ?><path d="M8 2h21l11 11v39a2 2 0 0 1-2 2H8a4 4 0 0 1-4-4V6a4 4 0 0 1 4-4Z" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
       <b><?php echo esc_html( $fc[0] ); ?></b>
      </span><span class="format-name"><?php echo esc_html( $fc[1] ); ?></span><span class="format-desc"><?php echo $fc[2]; // phpcs:ignore ?><span class="format-count"><?php echo $n > 0 ? esc_html( number_format( $n, 0, ',', '.' ) . ' tài liệu' ) : 'Đang cập nhật'; ?></span></span><span class="format-action">Khám phá <span>↗</span></span>
     </a>
     <?php endforeach; ?>
    </div>
    <div class="showcase-bottom">
     <div class="showcase-stat"><span class="showcase-stat-icon">✓</span><span><strong>Dễ tìm theo nhu cầu</strong><small>Ôn thi · Hồ sơ · Biểu mẫu</small></span></div>
     <div class="showcase-stat"><span class="showcase-stat-icon teal">⌕</span><span><strong>Tra cứu có định hướng</strong><small>Tiết kiệm thời gian chuẩn bị</small></span></div>
     <a class="btn btn-gold" href="<?php $a( $u['docs'] ); ?>">Tìm tài liệu phù hợp <span>→</span></a>
    </div>
   </div>

   <div class="daily-action-hub" aria-label="Gợi ý hành động hôm nay">
    <div class="daily-hub-head">
     <div>
      <div class="daily-hub-kicker"><span aria-hidden="true">✦</span> GỢI Ý CHO BẠN HÔM NAY</div>
      <h3>Bắt đầu nhỏ, tiến bộ mỗi ngày</h3>
      <p>Ba hành động thiết thực để chuẩn bị kỳ thi hiệu quả hơn.</p>
     </div>
     <span class="daily-hub-badge">Lộ trình 3 bước</span>
    </div>
    <div class="daily-hub-grid">
     <a class="daily-hub-item" href="<?php $a( $u['exams'] ); ?>">
      <span class="daily-hub-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5z"/><path d="M8 8h8M8 12h3M8 16h8"/></svg></span>
      <strong>1. Làm bài đánh giá</strong>
      <small>Kiểm tra nhanh kiến thức hiện tại.</small>
      <span class="hub-link">Chọn đề thử →</span>
     </a>
     <a class="daily-hub-item" href="<?php $a( $u['legal'] ); ?>">
      <span class="daily-hub-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6M9 15h4"/></svg></span>
      <strong>2. Củng cố kiến thức</strong>
      <small>Ôn lại văn bản và chủ đề liên quan.</small>
      <span class="hub-link">Tra cứu ngay →</span>
     </a>
     <a class="daily-hub-item" href="<?php $a( $u['docs'] ); ?>">
      <span class="daily-hub-icon" aria-hidden="true"><?php echo $ico_files; // phpcs:ignore ?></span>
      <strong>3. Chuẩn bị hồ sơ</strong>
      <small>Tìm mẫu biểu và tài liệu cần thiết.</small>
      <span class="hub-link">Mở tài liệu →</span>
     </a>
    </div>
    <div class="daily-hub-bottom">
     <p><strong>Chưa biết bắt đầu từ đâu?</strong> Hãy hỏi trợ lý AI để được gợi ý bước tiếp theo.</p>
     <button class="daily-hub-ai" type="button" data-open-ai>✦ Hỏi AI hỗ trợ <span>→</span></button>
    </div>
   </div>
  </div>
  <aside class="context-sidebar" aria-label="Sidebar tuyển dụng, đề thi và tài liệu">
   <div class="side-panel">
    <div class="side-head"><h3><svg class="icon" style="vertical-align:middle;color:#ffb45e" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg> Việc làm quanh bạn</h3><p>Gợi ý theo khu vực truy cập</p></div>
    <div class="side-content">
     <div class="location-chip"><span class="location-dot"></span><span id="currentRegion">Đang xác định khu vực</span></div>
     <select id="regionSelect" class="location-select" aria-label="Chọn tỉnh thành để xem tuyển dụng">
      <option value="">Hoặc tự chọn tỉnh/thành</option>
      <?php foreach ( $provinces as $p ) : ?>
      <option value="<?php echo (int) $p['id']; ?>"><?php echo esc_html( $p['short'] . ( $p['open_recruitments'] > 0 ? ' (' . $p['open_recruitments'] . ' tin)' : '' ) ); ?></option>
      <?php endforeach; ?>
     </select>
     <div class="location-status" id="locationStatus">Định vị IP chỉ mang tính ước lượng.</div>
     <div id="localJobs">
      <?php if ( $hot_provinces ) : $top = array_slice( $hot_provinces, 0, 3 ); ?>
      <div class="side-job"><span class="tag">Khu vực có tin mới</span><h4><?php echo esc_html( implode( ' · ', array_map( static fn( $p ) => $p['short'] . ' (' . $p['open_recruitments'] . ')', $top ) ) ); ?></h4><p>Tỉnh/thành đang có thông báo còn hạn nộp hồ sơ.</p><a href="<?php $a( add_query_arg( 'tinh', $top[0]['id'], $u['rec'] ) ); ?>">Xem tin tại <?php $e( $top[0]['short'] ); ?> →</a></div>
      <?php endif; ?>
      <div class="side-job"><span class="tag">Kế hoạch dự tuyển</span><h4>Chuẩn bị hồ sơ và kiểm tra điều kiện</h4><p>Đọc kỹ thời hạn, tiêu chuẩn và hướng dẫn trong thông báo gốc.</p><a href="<?php $a( $u['docs'] ); ?>">Xem tài liệu →</a></div>
     </div>
     <p class="side-footnote">Vị trí IP có thể không chính xác tuyệt đối. Có thể chọn tỉnh/thành thủ công.</p>
    </div>
   </div>
   <div class="side-panel">
    <div class="side-content"><strong style="display:block;color:var(--navy);font-size:19px;margin-bottom:12px">Truy cập nhanh</strong>
     <div class="side-mini-links">
      <a href="<?php $a( $u['exams'] ); ?>"><span class="mini"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h4M8 16h8"/></svg></span>Thi trắc nghiệm <span style="margin-left:auto">→</span></a>
      <a href="<?php $a( $u['docs'] ); ?>"><span class="mini"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h10l4 4v14H5z"/><path d="M14 3v5h5"/></svg></span>Tài liệu ôn thi <span style="margin-left:auto">→</span></a>
      <a href="<?php $a( $u['rec'] ); ?>"><span class="mini"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3"/></svg></span>Tin tuyển dụng <span style="margin-left:auto">→</span></a>
     </div>
    </div>
   </div>
   <div class="side-cta"><span class="eyebrow" style="padding:5px 9px;font-size:13px">BẮT ĐẦU MIỄN PHÍ</span><h3>Mỗi ngày ôn một bước</h3><p>Làm thử một đề trước, rồi chọn tài liệu theo phần kiến thức cần củng cố.</p><a class="btn btn-gold" href="<?php $a( $u['exams'] ); ?>">Luyện đề ngay →</a></div>
  </aside>
 </div>
</section>
<section class="section-soft">
 <div class="container">
  <div class="section-head"><div><div class="section-label">BẮT ĐẦU TỪ NHU CẦU CỦA BẠN</div><h2>Đi đúng hướng, tiết kiệm thời gian ôn tập</h2><p>Dù bạn đang tìm kỳ thi phù hợp, củng cố kiến thức hay chuẩn bị hồ sơ — hãy bắt đầu từ một lựa chọn rõ ràng.</p></div></div>
  <div class="quick-grid">
   <a class="quick-card" href="<?php $a( $u['exams'] ); ?>"><span class="quick-icon"><?php echo $ico_exam; // phpcs:ignore ?></span><h3>Luyện đề thi thử</h3><p>Làm bài theo thời gian, kiểm tra mức độ sẵn sàng và phát hiện phần kiến thức còn yếu.</p><span class="text-link">Chọn đề thi <span>→</span></span></a>
   <a class="quick-card" href="<?php $a( $u['docs'] ); ?>"><span class="quick-icon"><?php echo $ico_files; // phpcs:ignore ?></span><h3>Tìm tài liệu ôn thi</h3><p>Tiếp cận mẫu hồ sơ, tài liệu tham khảo và nội dung hỗ trợ quá trình chuẩn bị.</p><span class="text-link">Mở kho tài liệu <span>→</span></span></a>
   <a class="quick-card" href="<?php $a( $u['legal'] ); ?>"><span class="quick-icon"><?php echo $ico_law; // phpcs:ignore ?></span><h3>Tra cứu pháp luật</h3><p>Đọc văn bản liên quan đến tuyển dụng, quản lý công chức và viên chức.</p><span class="text-link">Tra cứu văn bản <span>→</span></span></a>
   <a class="quick-card" href="<?php $a( $u['rec'] ); ?>"><span class="quick-icon"><?php echo $ico_job; // phpcs:ignore ?></span><h3>Theo dõi tuyển dụng</h3><p>Tìm thông báo mới, chú ý thời hạn và chủ động chuẩn bị hồ sơ dự tuyển.</p><span class="text-link">Xem tin mới <span>→</span></span></a>
  </div>
 </div>
</section>
<section class="feature-band">
 <div class="container">
  <div class="section-head"><div><div class="section-label">HỌC THỬ TRƯỚC · TIẾN BỘ TỪNG BƯỚC</div><h2>Bắt đầu bằng một đề thi miễn phí</h2><p>Trải nghiệm cách làm bài và chấm điểm trước khi quyết định lộ trình ôn tập phù hợp.</p></div><a class="text-link" href="<?php $a( $u['exams'] ); ?>">Xem toàn bộ đề thi <span>→</span></a></div>
  <div class="exam-layout">
   <div class="exam-pitch"><div class="section-label">ĐỪNG CHỈ ĐỌC — HÃY THỬ SỨC</div><h2>Biết mình đang ở đâu trước ngày thi.</h2><p>Một lần làm đề giúp bạn nhìn rõ tốc độ, mức độ ghi nhớ và phần cần ôn lại — thay vì học dàn trải.</p>
    <ul class="check-list"><li><?php echo $ico_check; // phpcs:ignore ?> Đề trắc nghiệm theo chủ đề</li><li><?php echo $ico_check; // phpcs:ignore ?> Chấm điểm sau khi nộp bài</li><li><?php echo $ico_check; // phpcs:ignore ?> Có thể tiếp tục ôn theo nhu cầu</li></ul>
    <a class="btn btn-gold" href="<?php $a( $u['exams'] ); ?>">Làm đề miễn phí ngay <span>→</span></a>
   </div>
   <div class="exam-list">
    <?php if ( $exams ) : ?>
     <?php foreach ( array_slice( $exams, 0, 3 ) as $x ) : ?>
    <a class="exam-item" href="<?php $a( cvc_exam_url( (string) ( $x['slug'] ?? '' ) ) ); ?>"><span class="doc-icon"><?php echo $ico_doc; // phpcs:ignore ?></span><span class="exam-info"><h3><?php $e( cvc_home17_trim( $x['title'] ?? '', 92 ) ); ?></h3><span class="meta"><?php if ( ! empty( $x['duration_minutes'] ) ) : ?><span>◷ <?php echo (int) $x['duration_minutes']; ?> phút</span><?php endif; ?><?php if ( ! empty( $x['total_questions'] ) ) : ?><span>☷ <?php echo (int) $x['total_questions']; ?> câu hỏi</span><?php endif; ?></span></span><span class="exam-side"><span class="free-tag"><?php $e( $exam_type_label( $x['exam_type'] ?? '' ) ); ?></span><span class="arrow-btn">→</span></span></a>
     <?php endforeach; ?>
    <?php else : ?>
    <a class="exam-item" href="<?php $a( $u['exams'] ); ?>"><span class="doc-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span><span class="exam-info"><h3>Ôn tập kiến thức chung — Vòng 1</h3><span class="meta"><span>Trắc nghiệm</span><span>Chấm điểm tự động</span></span></span><span class="exam-side"><span class="free-tag">Miễn phí</span><span class="arrow-btn">→</span></span></a>
    <a class="exam-item" href="<?php $a( $u['exams'] ); ?>"><span class="doc-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h4"/></svg></span><span class="exam-info"><h3>Luyện tập theo văn bản pháp luật</h3><span class="meta"><span>Ôn theo chủ đề</span><span>Củng cố kiến thức</span></span></span><span class="exam-side"><span class="free-tag">Khám phá</span><span class="arrow-btn">→</span></span></a>
    <?php endif; ?>
   </div>
  </div>
 </div>
</section>

<section class="course-zone" id="khoa-hoc" aria-label="Khóa học ôn thi">
 <div class="container">
  <div class="section-head"><div><div class="section-label">KHÓA HỌC ÔN THI</div><h2>Học theo lộ trình cùng khóa học ôn thi</h2><p>Bài giảng theo cấu trúc đề, gắn với văn bản cần nắm và đề luyện sau mỗi phần.</p></div><a class="text-link" href="<?php $a( $u['courses'] ); ?>">Xem tất cả khóa học <span>→</span></a></div>
  <?php if ( $sellable ) : ?>
  <div class="course-grid">
   <?php foreach ( array_slice( $sellable, 0, 6 ) as $c ) :
	   $price = (float) ( $c['price'] ?? 0 );
	   $sale  = isset( $c['sale_price'] ) && null !== $c['sale_price'] ? (float) $c['sale_price'] : null;
	   $final = ( null !== $sale && $sale < $price ) ? $sale : $price;
	   ?>
   <article class="course-card">
    <div class="micro"><?php echo ! empty( $c['is_featured'] ) ? 'KHÓA HỌC NỔI BẬT' : 'KHÓA HỌC ÔN THI'; ?></div>
    <h3><a href="<?php $a( cvc_course_url( (string) ( $c['slug'] ?? '' ) ) ); ?>"><?php $e( $c['title'] ?? '' ); ?></a></h3>
    <?php if ( ! empty( $c['short_description'] ) ) : ?><p><?php $e( cvc_home17_trim( $c['short_description'], 150 ) ); ?></p><?php endif; ?>
    <div class="course-meta"><span><?php echo (int) ( $c['lessons'] ?? 0 ); ?> bài học</span></div>
    <div class="course-price"><strong><?php echo $final > 0 ? esc_html( cvc_home17_money( $final ) ) : 'Miễn phí'; ?></strong><?php if ( $final > 0 && $final < $price ) : ?><s><?php $e( cvc_home17_money( $price ) ); ?></s><?php endif; ?><a class="text-link" href="<?php $a( cvc_course_url( (string) ( $c['slug'] ?? '' ) ) ); ?>">Xem khóa học <span>→</span></a></div>
   </article>
   <?php endforeach; ?>
  </div>
  <?php else : ?>
  <div class="course-soon">
   <div class="course-soon-pitch">
    <div class="section-label">SẮP RA MẮT</div>
    <h3>Khóa học ôn thi có lộ trình đang được biên soạn</h3>
    <p>Để lại email để nhận thông báo khi khóa học mở đăng ký. Trong lúc chờ, bạn có thể luyện đề miễn phí và tra cứu văn bản cần ôn.</p>
    <form class="course-notify" method="post" action="<?php $a( admin_url( 'admin-post.php' ) ); ?>">
     <input type="hidden" name="action" value="cvc_newsletter_subscribe">
     <input type="hidden" name="source" value="courses_coming_soon">
     <?php wp_nonce_field( 'cvc_newsletter_subscribe' ); ?>
     <input id="courseNotifyEmail" type="email" name="email" required placeholder="Email của bạn" aria-label="Email nhận thông báo khóa học">
     <button class="btn btn-gold" type="submit">Báo tôi khi mở</button>
    </form>
    <div class="course-soon-note">Chỉ gửi thông báo về khóa học; có thể hủy đăng ký bất cứ lúc nào.</div>
   </div>
   <div class="course-soon-list">
    <?php if ( $soon ) : ?>
    <h4>Khóa học sắp mở</h4>
     <?php foreach ( array_slice( $soon, 0, 5 ) as $c ) : ?>
    <a class="course-soon-item" href="<?php $a( cvc_course_url( (string) ( $c['slug'] ?? '' ) ) ); ?>"><span class="quick-icon"><?php echo $ico_files; // phpcs:ignore ?></span><strong><?php $e( cvc_home17_trim( $c['title'] ?? '', 90 ) ); ?></strong><span class="free-tag">Sắp mở</span></a>
     <?php endforeach; ?>
    <?php else : ?>
    <h4>Trong lúc chờ khóa học</h4>
    <?php endif; ?>
    <a class="course-soon-item" href="<?php $a( $u['exams'] ); ?>"><span class="quick-icon"><?php echo $ico_exam; // phpcs:ignore ?></span><strong>Đề thi thử miễn phí</strong><span class="free-tag" style="background:#e5f5f1;color:#11635e">Học ngay</span></a>
    <?php if ( ! $soon ) : ?>
    <a class="course-soon-item" href="<?php $a( $u['topics'] ); ?>"><span class="quick-icon"><?php echo $ico_law; // phpcs:ignore ?></span><strong>Chủ đề ôn thi theo văn bản</strong><span class="free-tag" style="background:#e5f5f1;color:#11635e">Học ngay</span></a>
    <?php endif; ?>
   </div>
  </div>
  <?php endif; ?>
 </div>
</section>

<section class="section-soft">
 <div class="container">
  <div class="section-head"><div><div class="section-label">HỆ SINH THÁI HỖ TRỢ ÔN THI</div><h2>Không chỉ có đề thi — có cả công cụ để chuẩn bị tốt hơn</h2><p>Tập trung vào những việc quan trọng nhất: hiểu kiến thức, luyện cách làm bài và chuẩn bị hồ sơ đúng hạn.</p></div></div>
  <div class="value-grid">
   <article class="value-card featured"><div class="micro">01 · ÔN TẬP</div><span class="quick-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h5M8 16h8"/></svg></span><h3>Ngân hàng đề thi</h3><p>Luyện tập theo vị trí, chủ đề và cấu trúc đề. Dùng kết quả làm căn cứ chọn phần cần củng cố.</p><a class="btn btn-primary" href="<?php $a( $u['exams'] ); ?>">Chọn đề phù hợp <span>→</span></a></article>
   <article class="value-card"><div class="micro">02 · TÀI LIỆU</div><span class="quick-icon"><?php echo $ico_files; // phpcs:ignore ?></span><h3>Tài liệu &amp; mẫu hồ sơ</h3><p>Tìm mẫu biểu và tài liệu tham khảo để giảm thời gian tìm kiếm, chuẩn bị hồ sơ có hệ thống.</p><a class="btn btn-outline" href="<?php $a( $u['docs'] ); ?>">Khám phá tài liệu <span>→</span></a></article>
   <article class="value-card"><div class="micro">03 · KẾ HOẠCH</div><span class="quick-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2M8 2h8"/></svg></span><h3>Lộ trình ôn tập</h3><p>Chia mục tiêu lớn thành các bước nhỏ: rà soát kiến thức, luyện đề và kiểm tra lại điểm yếu.</p><a class="btn btn-outline" href="<?php $a( $u['exams'] ); ?>">Bắt đầu từ đề thi <span>→</span></a></article>
  </div>
 </div>
</section>
<section class="materials">
 <div class="container material-layout">
  <div class="material-art" aria-label="Minh họa tài liệu ôn thi">
   <div class="paper-stack"><div class="paper back"></div><div class="paper"><span class="stamp">CÔNG VIÊN CHỨC</span><h3>TÀI LIỆU<br>ÔN THI &amp; HỒ SƠ</h3><div style="font-size:14px;color:#232d3a">Tổng hợp theo nhu cầu thực tế</div><div class="rule"></div><div class="rule"></div><div class="rule short"></div><div style="margin-top:24px;color:#8c460f;font-size:14px;font-weight:bold">TRA CỨU · ÔN TẬP · CHUẨN BỊ</div></div></div>
   <div class="material-bubble one"><strong>✓</strong> Dễ tìm theo nhu cầu</div><div class="material-bubble two"><strong>01</strong> Mẫu hồ sơ &amp; văn bản</div>
  </div>
  <div class="material-copy"><div class="section-label">KHO TÀI LIỆU</div><h2>Chuẩn bị hồ sơ chỉn chu. Ôn tập có trọng tâm.</h2><p>Đừng để thời gian bị tiêu hao bởi việc tìm tài liệu rời rạc. Bắt đầu từ kho tài liệu, mẫu biểu và văn bản liên quan để có nền tảng chuẩn bị rõ ràng hơn.</p>
   <div class="benefit-list"><div class="benefit"><span class="check">✓</span><div><strong>Mẫu hồ sơ dự tuyển</strong><small>Tiếp cận biểu mẫu tham khảo và thông tin đi kèm.</small></div></div><div class="benefit"><span class="check">✓</span><div><strong>Văn bản pháp luật liên quan</strong><small>Tra cứu căn cứ và chủ động đối chiếu nguồn ban hành.</small></div></div><div class="benefit"><span class="check">✓</span><div><strong>Tài liệu hỗ trợ ôn thi</strong><small>Tìm nội dung phù hợp với kỳ thi hoặc mục tiêu của bạn.</small></div></div></div>
   <div class="hero-actions"><a class="btn btn-gold" href="<?php $a( $u['docs'] ); ?>">Vào kho tài liệu <span>→</span></a><a class="btn btn-outline" href="<?php $a( $u['legal'] ); ?>">Tra cứu văn bản</a></div>
  </div>
 </div>
</section>
<section class="section-soft">
 <div class="container">
  <div class="section-head"><div><div class="section-label">MỤC TIÊU DÀI HẠN</div><h2>Chuẩn bị cho bước tiếp theo trong sự nghiệp</h2><p>Chọn nội dung gần nhất với mục tiêu hiện tại của bạn — sau đó bắt đầu bằng một hành động cụ thể.</p></div></div>
  <div class="career-grid">
   <article class="career-card"><span class="career-number">GIAI ĐOẠN 01</span><h3>Chuẩn bị dự tuyển</h3><p>Tìm thông báo phù hợp, rà soát điều kiện và chuẩn bị hồ sơ.</p><a href="<?php $a( $u['rec'] ); ?>">Xem tuyển dụng →</a></article>
   <article class="career-card"><span class="career-number">GIAI ĐOẠN 02</span><h3>Củng cố kiến thức</h3><p>Hệ thống lại kiến thức chung và các văn bản cần nắm.</p><a href="<?php $a( $u['legal'] ); ?>">Tra cứu kiến thức →</a></article>
   <article class="career-card"><span class="career-number">GIAI ĐOẠN 03</span><h3>Luyện đề có chiến lược</h3><p>Thực hành theo thời gian, xem kết quả và ôn lại phần còn yếu.</p><a href="<?php $a( $u['exams'] ); ?>">Luyện đề ngay →</a></article>
   <article class="career-card"><span class="career-number">GIAI ĐOẠN 04</span><h3>Phát triển chuyên môn</h3><p>Tiếp tục cập nhật quy định, kiến thức nghề nghiệp và lộ trình.</p><a href="<?php $a( $u['docs'] ); ?>">Khám phá tài liệu →</a></article>
  </div>
 </div>
</section>
<section class="feature-band">
 <div class="container">
  <div class="section-head"><div><div class="section-label">CÔNG CỤ THỰC TIỄN</div><h2>Những thông tin hữu ích cho công việc và cuộc sống</h2><p>Không chỉ phục vụ kỳ thi, hệ thống còn hỗ trợ tra cứu các thông tin thiết thực trong hành trình công vụ.</p></div></div>
  <div class="tools-grid">
   <article class="tool-card"><span class="quick-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 9h10M7 13h3m3 0h4"/></svg></span><div><h3>Ước tính lương &amp; lương hưu</h3><p>Tham khảo mức thu nhập theo hệ số và các thông tin đầu vào.</p><a class="text-link" href="<?php $a( $u['salary'] ); ?>">Mở công cụ <span>→</span></a></div></article>
   <article class="tool-card"><span class="quick-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span><div><h3>Văn bản mới cập nhật</h3><p>Tiếp cận các văn bản có thể liên quan đến lĩnh vực của bạn.</p><a class="text-link" href="<?php $a( $u['legal'] ); ?>">Xem văn bản <span>→</span></a></div></article>
   <article class="tool-card"><span class="quick-icon"><?php echo $ico_gov; // phpcs:ignore ?></span><div><h3>Tin tuyển dụng</h3><p>Theo dõi thông báo và chú ý thời hạn nộp hồ sơ.</p><a class="text-link" href="<?php $a( $u['rec'] ); ?>">Xem tin tuyển dụng <span>→</span></a></div></article>
  </div>
 </div>
</section>

<div class="marquee-secondary" aria-label="Các lợi ích và tài nguyên ôn thi">
 <div class="container"><div class="marquee-topline"><span class="live-dot"></span> Hành trang ôn thi — học có chiến lược</div></div>
 <div class="marquee-window"><div class="marquee-track">
  <?php
  $perks = array(
	  array( 'Luyện đề theo thời gian', 'Rèn tốc độ và sự tập trung', '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h2m3 0h3M8 15h2m3 0h3"/>' ),
	  array( 'Tài liệu Word &amp; PDF', 'Tham khảo, ghi chú và ôn tập', '<path d="M5 3h10l4 4v14H5z"/><path d="M14 3v5h5M8 13h8M8 17h6"/>' ),
	  array( 'Lộ trình từng bước', 'Chia mục tiêu lớn thành việc nhỏ', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>' ),
	  array( 'Tra cứu văn bản', 'Đối chiếu quy định liên quan', '<path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6M9 15h6"/>' ),
	  array( 'Cơ hội theo địa phương', 'Chủ động theo dõi hạn tuyển dụng', '<path d="M3 20h18M5 20V8l7-4 7 4v12M9 20v-6h6v6"/>' ),
  );
  for ( $loop = 0; $loop < 2; $loop++ ) :
	  foreach ( $perks as $pk ) :
		  ?>
  <div class="source-pill"<?php echo $loop ? ' aria-hidden="true"' : ''; ?>><span class="seal"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?php echo $pk[2]; // phpcs:ignore ?></svg></span><span><strong><?php echo $pk[0]; // phpcs:ignore ?></strong><small><?php $e( $pk[1] ); ?></small></span></div>
		  <?php
	  endforeach;
  endfor;
  ?>
 </div></div>
 <div class="container marquee-caption">Bắt đầu từ một đề thi miễn phí, nhận diện phần cần củng cố rồi chọn tài liệu phù hợp — <strong>mỗi buổi học nên có mục tiêu cụ thể.</strong></div>
</div>


<section class="start-paths" aria-label="Ba cách bắt đầu tại Công Viên Chức">
 <div class="container">
  <div class="section-head"><div><div class="section-label">BẮT ĐẦU THEO MỤC TIÊU</div><h2>Bạn muốn tiến bộ ở đâu hôm nay?</h2><p>Chọn một hướng đi — nền tảng sẽ đưa bạn đến công cụ phù hợp.</p></div></div>
  <div class="start-path-grid">
   <a class="start-path" href="<?php $a( $u['exams'] ); ?>"><span class="start-path-num">01</span><span><strong>Kiểm tra năng lực</strong><small>Làm đề thử, nhận diện phần kiến thức cần củng cố và chọn chủ đề luyện tập.</small><span class="path-arrow">Bắt đầu kiểm tra →</span></span></a>
   <a class="start-path" href="<?php $a( $u['legal'] ); ?>"><span class="start-path-num">02</span><span><strong>Cập nhật kiến thức pháp luật</strong><small>Tìm văn bản theo số hiệu, chủ đề và kiểm tra thông tin hiệu lực trước khi học.</small><span class="path-arrow">Mở kho văn bản →</span></span></a>
   <a class="start-path" href="<?php $a( $u['rec'] ); ?>"><span class="start-path-num">03</span><span><strong>Tìm cơ hội phù hợp</strong><small>Khám phá thông báo tuyển dụng và rà soát điều kiện, hồ sơ, thời hạn.</small><span class="path-arrow">Tìm tuyển dụng →</span></span></a>
  </div>
 </div>
</section>

<section>
 <div class="container">
  <div class="cta-panel"><div><div class="section-label">BƯỚC ĐẦU TIÊN KHÔNG CẦN PHỨC TẠP</div><h2>Hãy bắt đầu bằng một đề thi hôm nay.</h2><p>Thử sức với nội dung miễn phí, xem mình cần củng cố phần nào rồi lựa chọn tài liệu hoặc kế hoạch ôn tập phù hợp.</p></div><div class="cta-actions"><a class="btn btn-gold" href="<?php $a( $u['exams'] ); ?>">Luyện đề miễn phí →</a><?php if ( $logged_in ) : ?><a class="btn btn-light" href="<?php $a( $u['account'] ); ?>">Tài khoản của tôi</a><?php else : ?><a class="btn btn-light" href="<?php $a( $u['register'] ); ?>">Tạo tài khoản</a><?php endif; ?></div></div>
 </div>
</section>
<section style="padding-top:8px">
 <div class="container">
  <div class="section-head"><div><div class="section-label">CẬP NHẬT CƠ HỘI</div><h2>Đừng bỏ lỡ thông báo tuyển dụng phù hợp</h2><p>Đọc kỹ yêu cầu, đối chiếu điều kiện và lưu ý thời hạn được nêu trong thông báo gốc.</p></div><a class="text-link" href="<?php $a( $u['rec'] ); ?>">Tất cả tin tuyển dụng →</a></div>
  <div class="jobs-list">
   <?php
   $shown = 0;
   foreach ( array_slice( $recs, 0, 3 ) as $r ) :
	   $shown++;
	   $url  = cvc_recruitment_url( (string) ( $r['slug'] ?? '' ) );
	   $dl   = cvc_home17_date( $r['application_deadline'] ?? null );
	   $meta = array_filter( array( $r['province'] ?? '', ! empty( $r['total_positions'] ) ? (int) $r['total_positions'] . ' chỉ tiêu' : '' ) );
	   ?>
   <article class="job-card"><div class="job-top"><span class="job-org"><?php $e( cvc_home17_trim( $r['agency'] ?? 'Cơ hội tuyển dụng', 46 ) ); ?></span><span class="deadline"><?php echo $dl ? esc_html( 'Hạn ' . $dl ) : 'Theo thông báo'; ?></span></div><h3><a href="<?php $a( $url ); ?>"><?php $e( cvc_home17_trim( $r['title'] ?? '', 100 ) ); ?></a></h3><?php if ( $meta ) : ?><div class="job-meta"><?php foreach ( $meta as $m ) : ?><span><?php $e( $m ); ?></span><?php endforeach; ?></div><?php endif; ?><p><?php $e( cvc_home17_trim( $r['summary'] ?? '', 170 ) ); ?></p><a class="text-link" href="<?php $a( $url ); ?>">Xem thông báo →</a></article>
   <?php endforeach; ?>
   <?php if ( $shown < 1 ) : ?>
   <article class="job-card"><div class="job-top"><span class="job-org">Cơ hội tuyển dụng</span><span class="deadline">Theo thông báo</span></div><h3>Thông báo tuyển dụng công chức, viên chức mới</h3><p>Tìm các đợt tuyển dụng đang được tổng hợp trên hệ thống và kiểm tra thời hạn nộp hồ sơ.</p><a class="text-link" href="<?php $a( $u['rec'] ); ?>">Tìm cơ hội phù hợp →</a></article>
   <?php endif; ?>
   <?php if ( $shown < 2 ) : ?>
   <article class="job-card"><div class="job-top"><span class="job-org">Chuẩn bị hồ sơ</span><span class="deadline" style="background:#edf3f8;color:#355b7f">Tham khảo</span></div><h3>Rà soát mẫu biểu trước khi nộp</h3><p>Tham khảo mẫu hồ sơ và đọc kỹ hướng dẫn trong thông báo của cơ quan tuyển dụng.</p><a class="text-link" href="<?php $a( $u['docs'] ); ?>">Xem mẫu hồ sơ →</a></article>
   <?php endif; ?>
   <?php if ( $shown < 3 ) : ?>
   <article class="job-card"><div class="job-top"><span class="job-org">Sẵn sàng cho kỳ thi</span><span class="deadline" style="background:#e5f5f1;color:#11635e">Bắt đầu ngay</span></div><h3>Ôn tập kiến thức trước khi bước vào kỳ thi</h3><p>Chọn đề phù hợp, làm bài thử và xác định phần kiến thức cần dành thêm thời gian.</p><a class="text-link" href="<?php $a( $u['exams'] ); ?>">Chọn đề luyện tập →</a></article>
   <?php endif; ?>
  </div>
 </div>
</section>
</main>
<?php cvc_chrome_footer(); ?>
<div class="mobile-cta"><a class="btn btn-primary" href="<?php $a( $u['exams'] ); ?>">Luyện đề miễn phí</a><a class="btn btn-gold" href="<?php $a( $u['docs'] ); ?>">Xem tài liệu</a></div>

<?php /* AI Coach: khung chat nổi dùng chung mọi trang - inc/ai-coach.php (wp_footer). */ ?>

<div class="toast<?php echo $cvc_notice ? ' show' : ''; ?>" id="toast" role="status" aria-live="polite"><?php echo $cvc_notice ? esc_html( $cvc_notice['message'] ) : ''; ?></div>
<?php if ( $cvc_notice ) : ?>
<script>setTimeout(function(){var t=document.getElementById('toast');if(t)t.classList.remove('show');},6000);</script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
