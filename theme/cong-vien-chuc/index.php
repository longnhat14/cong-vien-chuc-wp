<?php
/**
 * Homepage - Executive Corporate Conglomerate Flagship (Tập đoàn Đào tạo Công viên chức Quốc gia)
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
$cvc_stat_courses      = ( new CVC_Course_Service() )->count() ?? 0;
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
$cvc_recruitment_result  = ( new CVC_Recruitment_Service() )->list( array( 'per_page' => 3 ) );
if ( $cvc_recruitment_result['ok'] && ! empty( $cvc_recruitment_result['data']['data']['data'] ) && is_array( $cvc_recruitment_result['data']['data']['data'] ) ) {
	$cvc_latest_recruitments = $cvc_recruitment_result['data']['data']['data'];
}
?>

<main id="main" class="cvc-homepage-prototype-100 bg-slate-900 font-sans text-slate-100">

<!-- 1. COMBO BANNER (dữ liệu thật từ cvc_get_combo_courses()) -->
<?php if ( null !== $cvc_combo ) : ?>
<div class="bg-gradient-to-r from-amber-600 via-red-600 to-amber-700 text-white text-xs py-2.5 px-4 shadow-md border-b border-amber-400/30 font-bold">
	<div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-2">
		<div class="flex items-center gap-2">
			<span class="bg-white text-red-600 text-[10px] font-black uppercase px-2 py-0.5 rounded">COMBO ƯU ĐÃI</span>
			<span>🔥 Combo Ôn Thi Công Chức Vòng 1 & Vòng 2: Tiết kiệm <strong><?php echo esc_html( $cvc_combo['discount_percent'] ); ?>%</strong> khi mua trọn bộ 2 khóa học</span>
		</div>
		<div class="flex items-center gap-4 text-[11px]">
			<a href="#combo-hot" class="bg-amber-400 hover:bg-amber-300 text-navy-950 font-black px-3 py-1 rounded-md transition-colors text-[11px] shadow-sm">Xem Combo Ngay &rarr;</a>
		</div>
	</div>
</div>
<?php endif; ?>

<?php cvc_render_live_pulse( $cvc_pulse ); ?>

<!-- 2. HERO BANNER - CORPORATE FLAGSHIP -->
<section class="hero-gradient relative overflow-hidden py-16 lg:py-24 text-white">
<div class="absolute top-1/4 left-10 w-96 h-96 bg-gold-500/10 rounded-full blur-3xl animate-pulse-glow pointer-events-none"></div>
<div class="absolute bottom-10 right-10 w-[500px] h-[500px] bg-azure-500/15 rounded-full blur-3xl animate-pulse-glow pointer-events-none"></div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
<div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

<!-- Left Hero Column -->
<div class="lg:col-span-7 space-y-7">
<div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-dark border border-gold-500/30 text-gold-400 text-xs font-bold tracking-widest uppercase shadow-glow-gold">
<i class="fa-solid fa-crown text-gold-400"></i> NỀN TẢNG ĐÀO TẠO CÔNG VỤ CHUẨN QUỐC GIA 2026
</div>

<h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-normal leading-[1.15]">
Kiến Tạo Đội Ngũ<br>
<span class="text-gold-gradient">Công Chức Lãnh Đạo</span><br>
Vững Vàng & Vươn Tầm
</h1>

<p class="text-slate-300 text-sm sm:text-base leading-relaxed max-w-2xl font-light">
Nền tảng ôn thi công chức, viên chức — tổng hợp <strong><?php echo $cvc_stat_recruitments > 0 ? esc_html( number_format( $cvc_stat_recruitments, 0, ',', '.' ) ) . '+ ' : ''; ?>tin tuyển dụng</strong> mới nhất, ngân hàng câu hỏi trắc nghiệm Vòng 1 và khóa học chuyên sâu Vòng 2.
</p>


<!-- Instant Search Bar with Enterprise Filters -->
<div class="space-y-3 pt-2">
<div class="bg-white/10 p-2 rounded-2xl glass-dark border border-white/20 shadow-2xl flex flex-col sm:flex-row items-center gap-2">
<div class="flex items-center w-full px-3">
<i class="fa-solid fa-magnifying-glass text-gold-400 text-lg mr-3"></i>
<input type="text" id="hero-search-query" placeholder="Tìm khóa học Ôn thi Vòng 1, Chuyên viên chính, Luật công chức..."
class="w-full bg-transparent text-white text-sm placeholder-slate-400 focus:outline-none">
</div>
<button onclick="handleHeroSearch()" class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-gold-500 to-gold-600 hover:from-gold-600 hover:to-gold-700 text-navy-950 font-extrabold text-sm rounded-xl shadow-glow-gold transition-all flex items-center justify-center gap-2 flex-shrink-0">
<span>Tìm Kiếm</span>
<i class="fa-solid fa-arrow-right"></i>
</button>
</div>
<div class="flex flex-wrap items-center gap-2 text-xs text-slate-300">
<span class="font-bold text-gold-400">Từ khóa hot:</span>
<button onclick="setSearchTag('Thi Chuyên viên chính 2026')" class="px-3 py-1 bg-white/5 hover:bg-white/15 rounded-lg border border-white/10 transition-colors">Thi Chuyên viên chính 2026</button>
<button onclick="setSearchTag('Ôn thi Vòng 1 Kiến thức chung')" class="px-3 py-1 bg-white/5 hover:bg-white/15 rounded-lg border border-white/10 transition-colors">Ôn thi Vòng 1 Kiến thức chung</button>
<button onclick="setSearchTag('Khóa học Vòng 2 Phỏng vấn')" class="px-3 py-1 bg-white/5 hover:bg-white/15 rounded-lg border border-white/10 transition-colors">Khóa học Vòng 2 Phỏng vấn</button>
</div>
<?php cvc_render_hero_tools(); ?>
</div>

<!-- Signature Slogan Banner -->
<div class="pt-2 flex items-center space-x-3 border-t border-slate-800/80">
<div class="w-1.5 h-10 bg-gold-500 rounded-full"></div>
<p class="font-handwriting text-gold-300 text-2xl font-bold">
“Trắc nghiệm chuẩn - Bồi dưỡng sâu - Kiến tạo tương lai công vụ”
</p>
</div>
</div>

<!-- Right Hero Column: Interactive 3D Card Composite -->
<div class="lg:col-span-5 relative flex justify-center">
<div class="relative w-full max-w-lg">
<div class="relative rounded-3xl overflow-hidden glass-dark border border-white/20 shadow-2xl p-3">
<img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=800&q=80" alt="Lãnh đạo Công chức" class="w-full h-[420px] object-cover rounded-2xl filter brightness-95">
<div class="absolute bottom-6 left-6 right-6 p-4 rounded-2xl glass-dark border border-white/20 text-white space-y-1 shadow-xl">
<span class="bg-gold-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded uppercase">Hệ Thống Đào Tạo Trực Tuyến</span>
<p class="font-bold text-sm text-white">Công Viên Chức PRO</p>
<p class="text-xs text-slate-300">Ôn thi Vòng 1 & Vòng 2 theo đúng cấu trúc đề thi tuyển dụng công chức, viên chức 2026</p>
</div>
</div>

<div class="absolute -top-6 -right-6 glass-dark p-4 rounded-2xl border border-gold-500/40 shadow-glow-gold flex items-center space-x-3 backdrop-blur-xl animate-bounce-slow">
<div class="w-12 h-12 rounded-xl bg-gold-500/20 text-gold-400 flex items-center justify-center text-2xl border border-gold-500/30">
<i class="fa-solid fa-newspaper"></i>
</div>
<div>
<p class="text-xl font-black text-white leading-none"><?php echo $cvc_stat_recruitments > 0 ? esc_html( number_format( $cvc_stat_recruitments, 0, ',', '.' ) ) . '+' : 'Mới'; ?></p>
<p class="text-[10px] text-slate-300 font-bold uppercase mt-1">Tin Tuyển Dụng</p>
</div>
</div>

<div class="absolute -bottom-6 -left-6 glass-dark p-4 rounded-2xl border border-azure-400/40 shadow-glow-azure flex items-center space-x-3 backdrop-blur-xl">
<div class="w-12 h-12 rounded-xl bg-azure-500/20 text-azure-400 flex items-center justify-center text-2xl border border-azure-400/30">
<i class="fa-solid fa-book-open"></i>
</div>
<div>
<p class="text-xl font-black text-white leading-none"><?php echo esc_html( $cvc_stat_courses ); ?></p>
<p class="text-[10px] text-slate-300 font-bold uppercase mt-1">Khóa Học Đang Mở</p>
</div>
</div>
</div>
</div>

</div>

<!-- Enterprise Metrics Strip (số liệu thật, đọc trực tiếp từ API) -->
<div class="mt-16 pt-10 border-t border-slate-800/80 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-gold-gradient"><?php echo $cvc_stat_recruitments > 0 ? esc_html( number_format( $cvc_stat_recruitments, 0, ',', '.' ) ) . '+' : 'Mới'; ?></p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tin Tuyển Dụng Công Chức, Viên Chức</p>
</div>
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-amber-400"><?php echo esc_html( $cvc_stat_courses ); ?></p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Khóa Học Ôn Thi</p>
</div>
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-white"><?php echo esc_html( $cvc_stat_legal_docs ); ?></p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Văn Bản Pháp Luật Cập Nhật</p>
</div>
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-azure-400"><?php echo esc_html( $cvc_stat_topics ); ?></p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Chuyên Đề Ôn Tập</p>
</div>
</div>

</div>
</section>

<!-- 4. FEATURED COMMERCIAL COURSES (HIGH CONVERTING) -->
<section id="khoa-hoc-noi-bat" class="py-20 bg-slate-900 border-t border-slate-800">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
<div class="space-y-2">
<span class="inline-block text-xs font-extrabold uppercase tracking-widest text-amber-400 bg-amber-500/10 px-3.5 py-1.5 rounded-full border border-amber-500/30">
★ CHƯƠNG TRÌNH ĐÀO TẠO ÔN THI TRỌNG ĐIỂM 2026
</span>
<h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-normal">
Khóa Học Ôn Thi Thương Mại Chuẩn Quốc Gia
</h2>
<p class="text-xs sm:text-sm text-slate-400">Lộ trình bài giảng bám sát cấu trúc đề thi tuyển dụng công chức, viên chức 2026</p>
</div>
<?php
$cvc_max_course_discount = 0;
foreach ( $featured_courses as $fc ) {
	$fc_badge = cvc_homepage_course_discount_badge( $fc );
	if ( $fc_badge && preg_match( '/(\d+)/', $fc_badge, $m ) ) {
		$cvc_max_course_discount = max( $cvc_max_course_discount, (int) $m[1] );
	}
}
?>
<?php if ( $cvc_max_course_discount > 0 ) : ?>
<div class="flex items-center gap-2">
<span class="text-xs text-amber-300 font-bold bg-amber-500/20 px-3 py-1.5 rounded-lg border border-amber-400/40">🔥 Đang Giảm Giá Đến <?php echo esc_html( $cvc_max_course_discount ); ?>%</span>
</div>
<?php endif; ?>
</div>

<!-- Course Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<?php if ( empty( $featured_courses ) ) : ?>
<p class="text-xs text-slate-400 col-span-full text-center py-8">Chưa có khóa học nổi bật nào được công bố.</p>
<?php endif; ?>
<?php foreach ($featured_courses as $c) :
	$c_price    = (float) ( $c['price'] ?? 0 );
	$c_sale     = isset( $c['sale_price'] ) && null !== $c['sale_price'] ? (float) $c['sale_price'] : $c_price;
	$c_badge    = cvc_homepage_course_discount_badge( $c );
	$c_lessons  = (int) ( $c['published_lessons_count'] ?? $c['lesson_count'] ?? 0 );
	$c_type     = $course_type_badge_labels[ $c['course_type'] ?? '' ] ?? 'Khóa học';
?>
<div class="bg-navy-950 rounded-3xl overflow-hidden border border-slate-800 hover:border-gold-500/60 shadow-2xl hover:shadow-glow-gold transition-all duration-300 flex flex-col justify-between group">
<div>
<!-- Thumbnail with Badges -->
<div class="relative h-48 overflow-hidden">
<img src="<?php echo esc_url($c['thumbnail_url'] ?? ''); ?>" alt="<?php echo esc_attr($c['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-85">
<div class="absolute inset-0 bg-gradient-to-t from-navy-950 via-transparent to-transparent"></div>
<?php if ( $c_badge ) : ?>
<span class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-black px-2.5 py-1 rounded-full shadow-md uppercase tracking-wider">
<?php echo esc_html($c_badge); ?>
</span>
<?php endif; ?>
<?php if ( ! empty( $c['duration_minutes'] ) ) : ?>
<span class="absolute bottom-3 right-3 text-[11px] font-bold text-white bg-black/60 px-2 py-0.5 rounded backdrop-blur-sm">
<i class="fa-regular fa-clock text-amber-400 mr-1"></i> <?php echo esc_html( (int) $c['duration_minutes'] ); ?> phút
</span>
<?php endif; ?>
</div>

<!-- Body -->
<div class="p-5 space-y-2.5">
<div class="flex items-center justify-between text-[11px]">
<span class="text-amber-400 font-bold bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20"><?php echo esc_html( $c_lessons > 0 ? $c_lessons . ' bài giảng' : 'Sắp mở' ); ?></span>
<span class="text-cyan-400 font-bold"><?php echo esc_html( $c_type ); ?></span>
</div>

<h3 class="font-extrabold text-sm text-white group-hover:text-amber-400 transition-colors leading-snug line-clamp-2">
<a href="<?php echo esc_url(cvc_course_url($c['slug'])); ?>"><?php echo esc_html($c['title']); ?></a>
</h3>

<p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">
<?php echo esc_html($c['short_description'] ?? ''); ?>
</p>
</div>
</div>

<!-- Footer Price & CTA -->
<div class="p-5 pt-0 space-y-3">
<div class="flex items-baseline justify-between border-t border-slate-800/80 pt-3">
<?php if ( ! empty( $c['is_coming_soon'] ) ) : ?>
<span class="text-sm font-black text-cyan-300">Sắp mở <span class="block text-[10px] font-semibold text-slate-400">Đang biên soạn bài giảng</span></span>
<?php elseif ( $c_price <= 0 ) : ?>
<span class="text-sm font-black text-emerald-400">Miễn phí</span>
<?php else : ?>
<div>
<?php if ( $c_sale < $c_price ) : ?>
<span class="text-[10px] text-slate-500 line-through block"><?php echo esc_html( number_format( $c_price, 0, ',', '.' ) ); ?>đ</span>
<?php endif; ?>
<span class="text-xl font-black text-amber-400 tracking-tight"><?php echo esc_html( number_format( $c_sale, 0, ',', '.' ) ); ?>đ</span>
</div>
<?php endif; ?>
</div>

<a href="<?php echo esc_url(cvc_course_url($c['slug'])); ?>" class="w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5">
<span>XEM CHI TIẾT & ĐĂNG KÝ</span>
<i class="fa-solid fa-arrow-right"></i>
</a>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

<!-- 5. FLAGSHIP MONETIZATION COMBO SHOWCASE (dữ liệu + mua hàng thật) -->
<?php if ( null !== $cvc_combo ) : ?>
<section id="combo-hot" class="py-16 bg-slate-950 text-white relative overflow-hidden border-t border-slate-800">
	<div class="absolute -top-32 -left-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
	<div class="absolute -bottom-32 -right-32 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
		<div class="bg-gradient-to-br from-navy-950 via-slate-900 to-indigo-950 border-2 border-gold-500/50 rounded-3xl p-8 lg:p-12 shadow-2xl relative overflow-hidden">
			<div class="absolute top-0 right-0 bg-gradient-to-l from-amber-500 to-amber-600 text-navy-950 text-xs font-black uppercase px-6 py-2 rounded-bl-2xl shadow-md">
				★ Combo Vòng 1 & Vòng 2
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center mt-4">
				<div class="lg:col-span-7 space-y-6">
					<div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-500/20 border border-red-500/40 text-red-400 text-xs font-bold uppercase tracking-wider">
						🔥 TIẾT KIỆM <?php echo esc_html( $cvc_combo['discount_percent'] ); ?>% KHI MUA TRỌN BỘ
					</div>

					<h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
						Combo Trọn Bộ Ôn Thi Công Chức 2026<br>
						<span class="text-gold-gradient">(Toàn Diện Vòng 1 & Vòng 2)</span>
					</h2>

					<p class="text-sm text-slate-300 leading-relaxed font-light">
						Mua gộp 2 khóa học trong 1 đơn hàng, giá tốt hơn mua lẻ từng khóa.
					</p>

					<div class="grid grid-cols-1 gap-3 text-xs text-slate-200">
						<?php foreach ( $cvc_combo['courses'] as $combo_course ) :
							$cc_sale = isset( $combo_course['sale_price'] ) && null !== $combo_course['sale_price'] ? (float) $combo_course['sale_price'] : (float) $combo_course['price'];
						?>
							<a href="<?php echo esc_url( cvc_course_url( $combo_course['slug'] ) ); ?>" class="flex items-center justify-between gap-2.5 bg-white/5 hover:bg-white/10 p-3 rounded-xl border border-white/10 transition-colors">
								<span class="flex items-center gap-2.5">
									<i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
									<span><?php echo esc_html( $combo_course['title'] ); ?></span>
								</span>
								<span class="text-amber-400 font-bold shrink-0"><?php echo esc_html( number_format( $cc_sale, 0, ',', '.' ) ); ?>đ</span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="lg:col-span-5 flex flex-col items-center justify-center bg-white/5 backdrop-blur-md p-8 rounded-2xl border border-white/10 text-center space-y-4">
					<span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Mua lẻ từng khóa: <span class="line-through text-slate-500"><?php echo esc_html( number_format( $cvc_combo['total_price'], 0, ',', '.' ) ); ?>đ</span></span>

					<div class="space-y-1">
						<span class="text-xs text-amber-400 font-bold block uppercase">Mua Combo Hôm Nay (-<?php echo esc_html( $cvc_combo['discount_percent'] ); ?>%)</span>
						<div class="text-4xl lg:text-5xl font-black text-amber-400 tracking-tight"><?php echo esc_html( number_format( $cvc_combo['total_sale_price'], 0, ',', '.' ) ); ?>đ</div>
					</div>

					<?php if ( cvc_is_logged_in() ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="w-full">
							<?php wp_nonce_field( 'cvc_combo_buy' ); ?>
							<input type="hidden" name="action" value="cvc_combo_buy">
							<button type="submit" class="w-full py-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl transition-all shadow-glow-gold flex items-center justify-center gap-2">
								<i class="fa-solid fa-cart-shopping"></i>
								<span>MUA COMBO NGAY — THANH TOÁN VNPAY</span>
							</button>
						</form>
					<?php else : ?>
						<a href="<?php echo esc_url( cvc_login_url( home_url( '/#combo-hot' ) ) ); ?>" class="w-full py-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl transition-all shadow-glow-gold flex items-center justify-center gap-2">
							<i class="fa-solid fa-cart-shopping"></i>
							<span>ĐĂNG NHẬP ĐỂ MUA COMBO</span>
						</a>
					<?php endif; ?>

					<div class="flex items-center justify-center gap-4 text-[10px] text-slate-400 pt-2 border-t border-white/10 w-full">
						<span><i class="fa-solid fa-infinity text-emerald-400 mr-1"></i> Truy cập trọn đời</span>
						<span><i class="fa-solid fa-shield text-amber-400 mr-1"></i> Thanh toán qua VNPay</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- 6. LIVE RECRUITMENT NOTICES FEED -->
<section id="tuyen-dung-moi" class="py-20 bg-slate-900 border-t border-slate-800">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
<div class="space-y-2">
<span class="inline-block text-xs font-extrabold uppercase tracking-widest text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/30">
✦ NGUỒN DỮ LIỆU CHÍNH THỨC .GOV.VN
</span>
<h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-normal">
Thông Báo Tuyển Dụng Công Chức Mới Nhất 2026
</h2>
<p class="text-xs sm:text-sm text-slate-400">Cập nhật thông báo tuyển dụng công chức, viên chức mới nhất trên toàn quốc</p>
</div>
<a href="<?php echo esc_url(cvc_recruitments_url()); ?>" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs rounded-xl transition-all border border-slate-700 flex items-center gap-2">
<span><?php echo $cvc_stat_recruitments > 0 ? 'Xem Tất Cả ' . esc_html( number_format( $cvc_stat_recruitments, 0, ',', '.' ) ) . ' Tin Tuyển Dụng' : 'Xem Trang Tuyển Dụng'; ?></span>
<i class="fa-solid fa-arrow-right"></i>
</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<?php if ( empty( $cvc_latest_recruitments ) ) : ?>
<p class="text-xs text-slate-400 col-span-full text-center py-8">Chưa có tin tuyển dụng nào được công bố.</p>
<?php endif; ?>
<?php foreach ( $cvc_latest_recruitments as $rc ) :
	$rc_agency   = is_array( $rc['agency'] ?? null ) ? ( $rc['agency']['name'] ?? '' ) : '';
	$rc_deadline = ! empty( $rc['dates']['application_deadline'] ) ? date_i18n( 'd/m/Y', strtotime( $rc['dates']['application_deadline'] ) ) : '';
?>
<!-- Job Card (dữ liệu thật từ CVC_Recruitment_Service) -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-gold-500/50 hover:shadow-glow-gold transition-all space-y-4 flex flex-col justify-between">
<div class="space-y-3">
<div class="flex items-center justify-between gap-2">
<?php if ( $rc_agency ) : ?>
<span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-black px-2.5 py-1 rounded truncate"><?php echo esc_html( mb_strtoupper( $rc_agency ) ); ?></span>
<?php endif; ?>
<?php if ( $rc_deadline ) : ?>
<span class="text-[10px] text-slate-400 shrink-0"><i class="fa-solid fa-clock mr-1 text-amber-400"></i> Hạn: <?php echo esc_html( $rc_deadline ); ?></span>
<?php endif; ?>
</div>
<h3 class="font-extrabold text-base text-white hover:text-amber-400 transition-colors cursor-pointer leading-snug line-clamp-2">
<?php echo esc_html( $rc['title'] ?? '' ); ?>
</h3>
<?php if ( ! empty( $rc['summary'] ) ) : ?>
<p class="text-xs text-slate-400 line-clamp-2"><?php echo esc_html( wp_trim_words( $rc['summary'], 20 ) ); ?></p>
<?php endif; ?>
</div>
<div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2">
<a href="<?php echo esc_url(cvc_recruitment_url( $rc['slug'] ?? '' )); ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl transition-all">
Xem Chi Tiết &rarr;
</a>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

<!-- 7. CAREER ROADMAP + SMART EXAM + SALARY + LEGAL MATRIX (Next-Gen, dữ liệu thật) -->
<?php
cvc_render_career_roadmap( $cvc_roadmap );
cvc_render_smart_exam_section( $cvc_latest_exams );
cvc_render_salary_calculator();
cvc_render_legal_matrix( $cvc_pulse['new_legal_documents'] ?? array(), $cvc_form_docs );
?>

<!-- 9. EXECUTIVE CALL-TO-ACTION BANNER -->
<section class="py-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
<div class="relative bg-gradient-to-r from-navy-950 via-slate-900 to-navy-900 rounded-3xl p-8 sm:p-14 text-white shadow-2xl overflow-hidden border-2 border-gold-500/40">
<div class="absolute -right-20 -top-20 w-80 h-80 bg-gold-500/20 rounded-full blur-3xl pointer-events-none"></div>
<div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
<div class="lg:col-span-8 space-y-4">
<span class="inline-block bg-gold-500/20 text-gold-300 border border-gold-500/40 text-[10px] font-black uppercase px-3.5 py-1 rounded-full tracking-widest">
★ CHƯƠNG TRÌNH ĐÀO TẠO CÔNG VỤ CHUẨN QUỐC GIA
</span>
<h2 class="text-3xl sm:text-5xl font-black text-white tracking-normal leading-tight">
Sẵn Sàng Bứt Phá Sự Nghiệp<br>
<span class="text-gold-gradient">Cùng Công Viên Chức PRO</span>
</h2>
<p class="text-xs sm:text-sm text-slate-300 max-w-2xl font-light">
Đăng ký tài khoản miễn phí để nhận lộ trình ôn thi cá nhân hóa cho Vòng 1 & Vòng 2.
</p>
</div>
<div class="lg:col-span-4 flex flex-col sm:flex-row lg:flex-col gap-3 justify-end">
<?php if ( null !== $cvc_combo ) : ?>
<a href="#combo-hot" class="px-8 py-4 bg-gradient-to-r from-gold-500 to-gold-600 hover:from-gold-600 hover:to-gold-700 text-navy-950 font-black rounded-2xl text-sm shadow-glow-gold transition-all text-center">
XEM COMBO ƯU ĐÃI (-<?php echo esc_html( $cvc_combo['discount_percent'] ); ?>%)
</a>
<?php endif; ?>
<a href="<?php echo esc_url( cvc_is_logged_in() ? cvc_courses_url() : cvc_login_url() ); ?>" class="px-8 py-4 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold rounded-2xl text-sm text-center transition-all backdrop-blur-md">
<?php echo esc_html( cvc_is_logged_in() ? 'Xem Khóa Học' : 'Đăng Nhập Học Viên' ); ?>
</a>
</div>
</div>
</div>
</section>

</main>

<script>
function handleHeroSearch() {
  let query = document.getElementById('hero-search-query').value;
  if (query) {
    window.location.href = '<?php echo esc_url( home_url( '/tim-kiem/' ) ); ?>?q=' + encodeURIComponent(query);
  }
}

function setSearchTag(tag) {
  document.getElementById('hero-search-query').value = tag;
  handleHeroSearch();
}
</script>

<?php get_footer(); ?>
