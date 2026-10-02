<?php
/**
 * Homepage - Executive Corporate Conglomerate Flagship (Tập đoàn Đào tạo Công viên chức Quốc gia)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$featured_courses = [
    [
        'code' => 'COURSE-KTC-2026',
        'title' => 'Khóa học Ôn thi Công chức Vòng 1 - Kiến thức chung (Cấp tốc 2026)',
        'slug' => 'khoa-hoc-on-thi-cong-chuc-vong-1-kien-thuc-chung-cap-toc-2026',
        'short_description' => 'Trọn bộ 60 bài giảng chuyên sâu Kiến thức chung, cam kết nắm vững Luật Cán bộ công chức & Hiến pháp.',
        'thumbnail' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&auto=format&fit=crop&q=80',
        'price' => '1.500.000đ',
        'sale_price' => '890.000đ',
        'badge' => 'Giảm 41%',
        'rating' => '4.95',
        'reviews' => '1.420',
        'duration' => '1.200 Phút',
        'lessons' => '45 Bài giảng',
        'instructor' => 'TS. Nguyễn Văn Hùng',
        'instructor_title' => 'Nguyên Lãnh đạo Học viện Hành chính',
    ],
    [
        'code' => 'COURSE-ENG-2026',
        'title' => 'Khóa học Ôn thi Tiếng Anh B1/B2 Công chức & Viên chức',
        'slug' => 'khoa-hoc-on-thi-tieng-anh-b1-b2-cong-chuc-vien-chuc',
        'short_description' => 'Mẹo làm bài trắc nghiệm Tiếng Anh Vòng 1 đạt 25-30/30 câu chuẩn khung Châu Âu.',
        'thumbnail' => 'https://images.unsplash.com/photo-1546410531-bb4caa6b424d?w=600&auto=format&fit=crop&q=80',
        'price' => '1.200.000đ',
        'sale_price' => '690.000đ',
        'badge' => 'Giảm 43%',
        'rating' => '4.92',
        'reviews' => '980',
        'duration' => '900 Phút',
        'lessons' => '30 Bài giảng',
        'instructor' => 'ThS. Lê Hoàng Mai',
        'instructor_title' => 'Chuyên gia Ngôn ngữ Công vụ',
    ],
    [
        'code' => 'COURSE-TIN-2026',
        'title' => 'Khóa học Ôn thi Tin học Đạt chuẩn Chuẩn kỹ năng CNTT',
        'slug' => 'khoa-hoc-on-thi-tin-hoc-dat-chuan-chuon-ky-nang-cntt',
        'short_description' => 'Bổ trợ kiến thức Tin học văn phòng MS Word, Excel, PowerPoint & An toàn thông tin.',
        'thumbnail' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&auto=format&fit=crop&q=80',
        'price' => '900.000đ',
        'sale_price' => '490.000đ',
        'badge' => 'Giảm 46%',
        'rating' => '4.88',
        'reviews' => '750',
        'duration' => '600 Phút',
        'lessons' => '20 Bài giảng',
        'instructor' => 'KTS. Trần Bảo Lâm',
        'instructor_title' => 'Chuyên gia CNTT & Số hóa Hành chính',
    ],
    [
        'code' => 'COURSE-VONG2-PM',
        'title' => 'Khóa học Chiến lược Ôn thi Vòng 2 - Nghiệp vụ Chuyên ngành & Kỹ năng Phỏng vấn',
        'slug' => 'khoa-hoc-chien-luoc-on-thi-vong-2-nghiep-vu-chuyen-nganh-ky-nang-phong-van',
        'short_description' => 'Chuyên gia nâng bệ điểm số Vòng 2 (Viết tự luận / Phỏng vấn) đạt điểm tối đa.',
        'thumbnail' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=600&auto=format&fit=crop&q=80',
        'price' => '2.500.000đ',
        'sale_price' => '1.490.000đ',
        'badge' => 'Giảm 40%',
        'rating' => '4.98',
        'reviews' => '2.150',
        'duration' => '1.800 Phút',
        'lessons' => '15 Buổi Live Zoom',
        'instructor' => 'PGS.TS. Phạm Quốc Bảo',
        'instructor_title' => 'Chủ tịch Hội đồng Khảo thí Công vụ',
    ],
];
?>

<main id="main" class="cvc-homepage-prototype-100 bg-slate-900 font-sans text-slate-100">

<!-- 1. FLASH URGENCY BANNER TICKER -->
<div class="bg-gradient-to-r from-amber-600 via-red-600 to-amber-700 text-white text-xs py-2.5 px-4 shadow-md border-b border-amber-400/30 font-bold">
	<div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-2">
		<div class="flex items-center gap-2">
			<span class="bg-white text-red-600 text-[10px] font-black uppercase px-2 py-0.5 rounded animate-pulse">FLASH SALE 2026</span>
			<span>🔥 Combo Ôn Thi Công Chức Vòng 1 & Vòng 2: Giảm ngay <strong>48%</strong> khi nhập mã <span class="bg-black/30 text-amber-300 font-extrabold px-2 py-0.5 rounded border border-amber-300/40">TUYENDUNG2026</span></span>
		</div>
		<div class="flex items-center gap-4 text-[11px]">
			<span class="hidden sm:inline-block"><i class="fa-solid fa-clock mr-1 text-amber-300"></i> Ưu đãi kết thúc sau: <strong id="flash-countdown" class="text-amber-300 font-extrabold">11:59:45</strong></span>
			<a href="#combo-hot" class="bg-amber-400 hover:bg-amber-300 text-navy-950 font-black px-3 py-1 rounded-md transition-colors text-[11px] shadow-sm">Nhận Ưu Đãi Ngay &rarr;</a>
		</div>
	</div>
</div>

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
Tập đoàn Đào tạo Công viên chức Quốc gia — Đơn vị duy nhất tích hợp <strong>Hệ thống Giám sát & Tuyển dụng tự động 3.321 Xã Phường</strong>, Ngân hàng <strong>36.035+ Đề thi trắc nghiệm AI Vòng 1</strong> chuẩn Bộ Nội vụ và Khóa học chuyên sâu Vòng 2.
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
<div class="flex items-center justify-between">
<span class="bg-gold-500 text-navy-950 text-[10px] font-black px-2.5 py-0.5 rounded uppercase">Học Viên Trúng Tuyển 2025</span>
<div class="flex text-gold-400 text-xs">
<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
</div>
</div>
<p class="font-bold text-sm text-white">Hệ Thống Đào Tạo Công Viên Chức PRO</p>
<p class="text-xs text-slate-300">Tỷ lệ vượt qua sát hạch Vòng 1 & Vòng 2 đạt 99.8%</p>
</div>
</div>

<div class="absolute -top-6 -right-6 glass-dark p-4 rounded-2xl border border-gold-500/40 shadow-glow-gold flex items-center space-x-3 backdrop-blur-xl animate-bounce-slow">
<div class="w-12 h-12 rounded-xl bg-gold-500/20 text-gold-400 flex items-center justify-center text-2xl border border-gold-500/30">
<i class="fa-solid fa-award"></i>
</div>
<div>
<p class="text-xl font-black text-white leading-none">99.8%</p>
<p class="text-[10px] text-slate-300 font-bold uppercase mt-1">Đạt Chuẩn Ngạch</p>
</div>
</div>

<div class="absolute -bottom-6 -left-6 glass-dark p-4 rounded-2xl border border-azure-400/40 shadow-glow-azure flex items-center space-x-3 backdrop-blur-xl">
<div class="w-12 h-12 rounded-xl bg-azure-500/20 text-azure-400 flex items-center justify-center text-2xl border border-azure-400/30">
<i class="fa-solid fa-users-viewfinder"></i>
</div>
<div>
<p class="text-xl font-black text-white leading-none">120.000+</p>
<p class="text-[10px] text-slate-300 font-bold uppercase mt-1">Học Viên Tin Dùng</p>
</div>
</div>
</div>
</div>

</div>

<!-- Enterprise Metrics Strip -->
<div class="mt-16 pt-10 border-t border-slate-800/80 grid grid-cols-2 md:grid-cols-5 gap-6 text-center">
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-gold-gradient">36.035+</p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Câu Hỏi & Đề Thi Trắc Nghiệm</p>
</div>
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-amber-400">500+</p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Khóa Học & Chuyên Đề</p>
</div>
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-white">3.321</p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Xã Phường Đã Số Hóa Website</p>
</div>
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-azure-400">34/34</p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tỉnh Thành Mới Phủ Sóng</p>
</div>
<div class="space-y-1">
<p class="text-3xl lg:text-4xl font-black text-gold-gradient">100%</p>
<p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Chuẩn Bộ Nội Vụ & Pháp Luật</p>
</div>
</div>

</div>
</section>

<!-- 3. STRATEGIC PARTNERS MARQUEE -->
<section class="bg-navy-950 py-5 border-y border-navy-800 overflow-hidden">
<div class="max-w-7xl mx-auto px-4 mb-2 text-center">
<p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">
ĐỐI TÁC ĐÀO TẠO & HỢP TÁC CƠ QUAN NHÀ NƯỚC, SỞ BAN NGÀNH TOÀN QUỐC
</p>
</div>
<div class="flex overflow-hidden relative">
<div class="flex animate-marquee space-x-12 whitespace-nowrap text-slate-400 text-xs font-bold items-center">
<span class="flex items-center gap-2 hover:text-white transition-colors"><i class="fa-solid fa-building-columns text-gold-400"></i> HỌC VIỆN HÀNH CHÍNH QUỐC GIA</span>
<span class="flex items-center gap-2 hover:text-white transition-colors"><i class="fa-solid fa-shield-halved text-azure-400"></i> SỞ NỘI VỤ TỈNH ĐẮK LẮK</span>
<span class="flex items-center gap-2 hover:text-white transition-colors"><i class="fa-solid fa-scale-balanced text-emerald-400"></i> SỞ NỘI VỤ TỈNH KHÁNH HÒA</span>
<span class="flex items-center gap-2 hover:text-white transition-colors"><i class="fa-solid fa-landmark text-gold-400"></i> SỞ NỘI VỤ TỈNH GIA LAI</span>
<span class="flex items-center gap-2 hover:text-white transition-colors"><i class="fa-solid fa-award text-azure-400"></i> SỞ NỘI VỤ TỈNH QUẢNG NGÃI</span>
<span class="flex items-center gap-2 hover:text-white transition-colors"><i class="fa-solid fa-building-columns text-gold-400"></i> HỌC VIỆN NÔNG NGHIỆP VIỆT NAM</span>
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
<p class="text-xs sm:text-sm text-slate-400">Giảng dạy bởi các Chuyên gia hàng đầu từ Học viện Hành chính Quốc gia & Bộ Nội vụ</p>
</div>
<div class="flex items-center gap-2">
<span class="text-xs text-amber-300 font-bold bg-amber-500/20 px-3 py-1.5 rounded-lg border border-amber-400/40">🔥 Đang Giảm Giá Đến 46%</span>
</div>
</div>

<!-- Course Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<?php foreach ($featured_courses as $c) : ?>
<div class="bg-navy-950 rounded-3xl overflow-hidden border border-slate-800 hover:border-gold-500/60 shadow-2xl hover:shadow-glow-gold transition-all duration-300 flex flex-col justify-between group">
<div>
<!-- Thumbnail with Badges -->
<div class="relative h-48 overflow-hidden">
<img src="<?php echo esc_url($c['thumbnail']); ?>" alt="<?php echo esc_attr($c['title']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-85">
<div class="absolute inset-0 bg-gradient-to-t from-navy-950 via-transparent to-transparent"></div>
<span class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-black px-2.5 py-1 rounded-full shadow-md uppercase tracking-wider">
<?php echo esc_html($c['badge']); ?>
</span>
<span class="absolute bottom-3 right-3 text-[11px] font-bold text-white bg-black/60 px-2 py-0.5 rounded backdrop-blur-sm">
<i class="fa-regular fa-clock text-amber-400 mr-1"></i> <?php echo esc_html($c['duration']); ?>
</span>
</div>

<!-- Body -->
<div class="p-5 space-y-2.5">
<div class="flex items-center justify-between text-[11px]">
<span class="text-amber-400 font-bold bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20"><?php echo esc_html($c['lessons']); ?></span>
<span class="text-amber-400 font-bold"><i class="fa-solid fa-star text-amber-400 mr-1"></i> <?php echo esc_html($c['rating']); ?> (<?php echo esc_html($c['reviews']); ?>)</span>
</div>

<h3 class="font-extrabold text-sm text-white group-hover:text-amber-400 transition-colors leading-snug line-clamp-2">
<a href="<?php echo esc_url(cvc_course_url($c['slug'])); ?>"><?php echo esc_html($c['title']); ?></a>
</h3>

<p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">
<?php echo esc_html($c['short_description']); ?>
</p>

<div class="pt-2 border-t border-slate-800 flex items-center gap-2 text-[11px] text-slate-300">
<i class="fa-solid fa-user-tie text-amber-400"></i>
<span class="truncate font-semibold"><?php echo esc_html($c['instructor']); ?></span>
</div>
</div>
</div>

<!-- Footer Price & CTA -->
<div class="p-5 pt-0 space-y-3">
<div class="flex items-baseline justify-between border-t border-slate-800/80 pt-3">
<div>
<span class="text-[10px] text-slate-500 line-through block"><?php echo esc_html($c['price']); ?></span>
<span class="text-xl font-black text-amber-400 tracking-tight"><?php echo esc_html($c['sale_price']); ?></span>
</div>
<span class="text-[10px] text-emerald-400 bg-emerald-500/20 px-2 py-0.5 rounded font-bold">Cam kết trúng tuyển</span>
</div>

<button onclick="enrollCourse('<?php echo esc_js($c['title']); ?>')" class="w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5">
<span>ĐĂNG KÝ KHÓA NGAY</span>
<i class="fa-solid fa-arrow-right"></i>
</button>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

<!-- 5. FLAGSHIP MONETIZATION COMBO SHOWCASE -->
<section id="combo-hot" class="py-16 bg-slate-950 text-white relative overflow-hidden border-t border-slate-800">
	<div class="absolute -top-32 -left-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
	<div class="absolute -bottom-32 -right-32 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

	<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
		<div class="bg-gradient-to-br from-navy-950 via-slate-900 to-indigo-950 border-2 border-gold-500/50 rounded-3xl p-8 lg:p-12 shadow-2xl relative overflow-hidden">
			<div class="absolute top-0 right-0 bg-gradient-to-l from-amber-500 to-amber-600 text-navy-950 text-xs font-black uppercase px-6 py-2 rounded-bl-2xl shadow-md">
				★ Gói Bán Chạy Nhất Đào Tạo Công Vụ 2026
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center mt-4">
				<div class="lg:col-span-7 space-y-6">
					<div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-500/20 border border-red-500/40 text-red-400 text-xs font-bold uppercase tracking-wider">
						🔥 KHUYẾN MÃI ĐẶC BIỆT DÀNH CHO CÁN BỘ & THÍ SINH
					</div>

					<h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
						Combo Trọn Bộ Ôn Thi Công Chức 2026<br>
						<span class="text-gold-gradient">(Toàn Diện Vòng 1 & Vòng 2)</span>
					</h2>

					<p class="text-sm text-slate-300 leading-relaxed font-light">
						Mở khóa toàn bộ ngân hàng 50.000+ câu hỏi trắc nghiệm AI, bài giảng HD online, tài liệu tự luận Vòng 2 và nhóm hỗ trợ giải đáp 24/7 trực tiếp từ giảng viên Bộ Nội vụ.
					</p>

					<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-200">
						<div class="flex items-center gap-2.5 bg-white/5 p-3 rounded-xl border border-white/10">
							<i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
							<span>Trọn bộ Kiến thức chung, Tiếng Anh B1 & Tin học</span>
						</div>
						<div class="flex items-center gap-2.5 bg-white/5 p-3 rounded-xl border border-white/10">
							<i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
							<span>Chấm điểm AI real-time & giải thích chi tiết câu hỏi</span>
						</div>
						<div class="flex items-center gap-2.5 bg-white/5 p-3 rounded-xl border border-white/10">
							<i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
							<span>Hướng dẫn viết bài thi tự luận Vòng 2 & Kịch bản phỏng vấn</span>
						</div>
						<div class="flex items-center gap-2.5 bg-white/5 p-3 rounded-xl border border-white/10">
							<i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
							<span>Cam kết hoàn tiền 100% nếu không đạt vòng sát hạch</span>
						</div>
					</div>

					<div class="flex items-center gap-3 pt-2">
						<span class="text-xs text-slate-400">Mã Voucher giảm 48%:</span>
						<div class="inline-flex items-center gap-2 bg-navy-900 border border-gold-400/50 px-3 py-1.5 rounded-lg text-amber-300 font-mono font-extrabold text-sm">
							<span>TUYENDUNG2026</span>
							<button onclick="copyCouponCode('TUYENDUNG2026')" class="hover:text-white transition-colors" title="Sao chép mã"><i class="fa-regular fa-copy"></i></button>
						</div>
					</div>
				</div>

				<div class="lg:col-span-5 flex flex-col items-center justify-center bg-white/5 backdrop-blur-md p-8 rounded-2xl border border-white/10 text-center space-y-4">
					<span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Giá Gốc Niêm Yết: <span class="line-through text-slate-500">3.600.000đ</span></span>
					
					<div class="space-y-1">
						<span class="text-xs text-amber-400 font-bold block uppercase">Giá Khuyến Mãi Hôm Nay (-48%)</span>
						<div class="text-4xl lg:text-5xl font-black text-amber-400 tracking-tight">1.890.000đ</div>
					</div>

					<p class="text-[11px] text-slate-300">Áp dụng cho 100 học viên đăng ký sớm nhất trong ngày</p>

					<button onclick="enrollComboCourse()" class="w-full py-4 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-xl transition-all shadow-glow-gold flex items-center justify-center gap-2">
						<i class="fa-solid fa-cart-shopping"></i>
						<span>ĐĂNG KÝ MUA GÓI COMBO NGAY</span>
					</button>

					<div class="flex items-center justify-center gap-4 text-[10px] text-slate-400 pt-2 border-t border-white/10 w-full">
						<span><i class="fa-solid fa-shield text-emerald-400 mr-1"></i> Bảo mật 100%</span>
						<span><i class="fa-solid fa-rotate-left text-amber-400 mr-1"></i> Hoàn tiền 7 ngày</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

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
<p class="text-xs sm:text-sm text-slate-400">Hệ thống cào & cập nhật tự động từ 3.321 Website UBND, Sở Nội vụ toàn quốc</p>
</div>
<a href="<?php echo esc_url(cvc_recruitments_url()); ?>" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs rounded-xl transition-all border border-slate-700 flex items-center gap-2">
<span>Xem Tất Cả 44 Tin Tuyển Dụng</span>
<i class="fa-solid fa-arrow-right"></i>
</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<!-- Job Card 1 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-gold-500/50 hover:shadow-glow-gold transition-all space-y-4 flex flex-col justify-between">
<div class="space-y-3">
<div class="flex items-center justify-between">
<span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-black px-2.5 py-1 rounded">SỞ NỘI VỤ ĐẮK LẮK</span>
<span class="text-[10px] text-slate-400"><i class="fa-solid fa-clock mr-1 text-amber-400"></i> Hạn: 20/10/2026</span>
</div>
<h3 class="font-extrabold text-base text-white hover:text-amber-400 transition-colors cursor-pointer leading-snug">
Kế hoạch Tuyển dụng 14 Công chức nộp hồ sơ trực tuyến 2026
</h3>
<p class="text-xs text-slate-400 line-clamp-2">Công bố trên Cổng thông tin điện tử sonoivu.daklak.gov.vn. Sát hạch Vòng 1 trắc nghiệm Kiến thức chung & Tiếng Anh.</p>
</div>
<div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
<span class="text-[11px] text-amber-400 font-bold"><i class="fa-solid fa-globe mr-1"></i> .gov.vn</span>
<a href="<?php echo esc_url(cvc_recruitment_url('thong-bao-ke-hoach-tuyen-dung-cong-chuc-nam-2026-so-noi-vu-tinh-dak-lak-c6hj')); ?>" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl transition-all">
Xem Chi Tiết &rarr;
</a>
</div>
</div>

<!-- Job Card 2 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-azure-500/50 hover:shadow-glow-azure transition-all space-y-4 flex flex-col justify-between">
<div class="space-y-3">
<div class="flex items-center justify-between">
<span class="bg-azure-500/20 text-azure-400 border border-azure-500/40 text-[10px] font-black px-2.5 py-1 rounded">SỞ NỘI VỤ KHÁNH HÒA</span>
<span class="text-[10px] text-slate-400"><i class="fa-solid fa-clock mr-1 text-amber-400"></i> Hạn: 20/10/2026</span>
</div>
<h3 class="font-extrabold text-base text-white hover:text-azure-400 transition-colors cursor-pointer leading-snug">
Tuyển dụng 29 Chỉ tiêu Công chức Ngạch Chuyên viên
</h3>
<p class="text-xs text-slate-400 line-clamp-2">Công bố trên Cổng thông tin điện tử snv.khanhhoa.gov.vn. Đăng ký dự tuyển trực tuyến hoặc nộp trực tiếp.</p>
</div>
<div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
<span class="text-[11px] text-azure-400 font-bold"><i class="fa-solid fa-globe mr-1"></i> .gov.vn</span>
<a href="<?php echo esc_url(cvc_recruitment_url('thong-bao-ke-hoach-tuyen-dung-cong-chuc-nam-2026-so-noi-vu-tinh-khanh-hoa-tm0w')); ?>" class="px-4 py-2 bg-azure-500 hover:bg-azure-600 text-white font-black text-xs rounded-xl transition-all">
Xem Chi Tiết &rarr;
</a>
</div>
</div>

<!-- Job Card 3 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-emerald-500/50 transition-all space-y-4 flex flex-col justify-between">
<div class="space-y-3">
<div class="flex items-center justify-between">
<span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-black px-2.5 py-1 rounded">SỞ NỘI VỤ GIA LAI</span>
<span class="text-[10px] text-slate-400"><i class="fa-solid fa-clock mr-1 text-amber-400"></i> Hạn: 20/10/2026</span>
</div>
<h3 class="font-extrabold text-base text-white hover:text-emerald-400 transition-colors cursor-pointer leading-snug">
Kế hoạch Tuyển dụng 44 Công chức Hành chính 2026
</h3>
<p class="text-xs text-slate-400 line-clamp-2">Công bố trên Cổng thông tin điện tử sonoivu.gialai.gov.vn. Hồ sơ tiêu chuẩn theo Nghị định 138/2020/NĐ-CP.</p>
</div>
<div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
<span class="text-[11px] text-emerald-400 font-bold"><i class="fa-solid fa-globe mr-1"></i> .gov.vn</span>
<a href="<?php echo esc_url(cvc_recruitment_url('thong-bao-ke-hoach-tuyen-dung-cong-chuc-nam-2026-so-noi-vu-tinh-gia-lai-2oxp')); ?>" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs rounded-xl transition-all">
Xem Chi Tiết &rarr;
</a>
</div>
</div>
</div>
</div>
</section>

<!-- 7. INTERACTIVE AI MOCK EXAM SUITE SIMULATOR -->
<section id="thi-thu-ai" class="py-20 bg-slate-950 border-t border-slate-800">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
<div class="text-center max-w-3xl mx-auto space-y-3">
<span class="inline-block text-xs font-black uppercase tracking-widest text-amber-400 bg-amber-500/10 px-3.5 py-1.5 rounded-full border border-amber-500/30">
⚡ NGÂN HÀNG THI TRẮC NGHIỆM AI VÒNG 1
</span>
<h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-normal">
Trải Nghiệm Đề Thi Thử Trực Tuyến Chuẩn Bộ Nội Vụ
</h2>
<p class="text-xs sm:text-sm text-slate-400">
Hệ thống AI tự động chấm điểm, tính % đạt/chưa đạt và tư vấn khóa học lấp lỗ hổng kiến thức ngay lập tức.
</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<!-- Exam Suite 1 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-gold-500/60 transition-all space-y-4">
<div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl font-black">
<i class="fa-solid fa-list-check"></i>
</div>
<h3 class="font-extrabold text-lg text-white">Đề Thi Thử Kiến Thức Chung (Bộ 01)</h3>
<p class="text-xs text-slate-400 leading-relaxed">60 Câu hỏi trắc nghiệm Luật Cán bộ công chức, Hiến pháp và Hệ thống chính trị trong 60 phút.</p>
<div class="pt-2 flex items-center justify-between text-xs text-slate-300">
<span><i class="fa-solid fa-circle-play text-amber-400 mr-1"></i> Miễn Phí Thi Thử</span>
<span class="font-bold text-emerald-400">Đạt >= 30/60</span>
</div>
<a href="<?php echo esc_url(cvc_exam_url('de-thi-thu-kien-thuc-chung-tuyen-dung-cong-chuc-vong-1-de-01')); ?>" class="block w-full py-3 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl text-center transition-all">
VÀO THI THỬ NGAY &rarr;
</a>
</div>

<!-- Exam Suite 2 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-azure-500/60 transition-all space-y-4">
<div class="w-12 h-12 rounded-2xl bg-azure-500/20 text-azure-400 flex items-center justify-center text-xl font-black">
<i class="fa-solid fa-language"></i>
</div>
<h3 class="font-extrabold text-lg text-white">Đề Thi Thử Tiếng Anh B1/B2 (Bộ 01)</h3>
<p class="text-xs text-slate-400 leading-relaxed">30 Câu hỏi trắc nghiệm Tiếng Anh Công vụ Vòng 1 trong 30 phút chuẩn khung Châu Âu.</p>
<div class="pt-2 flex items-center justify-between text-xs text-slate-300">
<span><i class="fa-solid fa-circle-play text-azure-400 mr-1"></i> Miễn Phí Thi Thử</span>
<span class="font-bold text-emerald-400">Đạt >= 15/30</span>
</div>
<a href="<?php echo esc_url(cvc_exam_url('de-thi-thu-ngoai-ngu-tieng-anh-tuyen-dung-cong-chuc-vong-1-de-01')); ?>" class="block w-full py-3 bg-azure-500 hover:bg-azure-600 text-white font-black text-xs rounded-xl text-center transition-all">
VÀO THI THỬ NGAY &rarr;
</a>
</div>

<!-- Exam Suite 3 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-emerald-500/60 transition-all space-y-4">
<div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl font-black">
<i class="fa-solid fa-laptop-code"></i>
</div>
<h3 class="font-extrabold text-lg text-white">Đề Thi Thử Tin Học Văn Phòng (Bộ 01)</h3>
<p class="text-xs text-slate-400 leading-relaxed">30 Câu hỏi trắc nghiệm Tin học CNTT Vòng 1 trong 30 phút bám sát ngân hàng câu hỏi sát hạch.</p>
<div class="pt-2 flex items-center justify-between text-xs text-slate-300">
<span><i class="fa-solid fa-circle-play text-emerald-400 mr-1"></i> Miễn Phí Thi Thử</span>
<span class="font-bold text-emerald-400">Đạt >= 15/30</span>
</div>
<a href="<?php echo esc_url(cvc_exam_url('de-thi-thu-tin-hoc-tuyen-dung-cong-chuc-vong-1-de-01')); ?>" class="block w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs rounded-xl text-center transition-all">
VÀO THI THỬ NGAY &rarr;
</a>
</div>
</div>
</div>
</section>

<!-- 8. DIGITAL LAW & RESOURCE VAULT -->
<section id="thu-vien" class="py-20 bg-slate-900 border-t border-slate-800">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
<div class="space-y-2">
<span class="inline-block text-xs font-extrabold uppercase tracking-widest text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/30">
KHO VĂN BẢN QUY PHẠM PHÁP LUẬT
</span>
<h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-normal">
Thư Viện Pháp Luật & Nghị Định Tuyển Dụng
</h2>
<p class="text-xs sm:text-sm text-slate-400">Tra cứu chính xác văn bản hợp nhất, quy định sát hạch và tiêu chuẩn chức danh ngạch</p>
</div>
<a href="<?php echo esc_url(cvc_legal_documents_url()); ?>" class="text-xs font-extrabold text-amber-400 hover:text-amber-300 flex items-center gap-1">
Xem Toàn Bộ Thư Viện 法 Luật <i class="fa-solid fa-arrow-right text-[10px]"></i>
</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
<!-- Doc 1 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-amber-400 transition-all space-y-3">
<span class="text-[10px] font-bold bg-amber-500/20 text-amber-300 px-2.5 py-0.5 rounded uppercase">Nghị định</span>
<h4 class="font-extrabold text-base text-white">Nghị định 138/2020/NĐ-CP</h4>
<p class="text-xs text-slate-400 leading-relaxed">Tuyển dụng, sử dụng và quản lý công chức (Quy định tiêu chuẩn thi Vòng 1 & Vòng 2).</p>
<a href="<?php echo esc_url(cvc_legal_document_url('nghi-dinh-138-2020-nd-cp-tuyen-dung-su-dung-quan-ly-cong-chuc')); ?>" class="inline-block text-xs font-bold text-amber-400 hover:underline pt-2">Xem nội dung văn bản &rarr;</a>
</div>

<!-- Doc 2 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-azure-400 transition-all space-y-3">
<span class="text-[10px] font-bold bg-azure-500/20 text-azure-300 px-2.5 py-0.5 rounded uppercase">Nghị định</span>
<h4 class="font-extrabold text-base text-white">Nghị định 115/2020/NĐ-CP</h4>
<p class="text-xs text-slate-400 leading-relaxed">Tuyển dụng, sử dụng và quản lý viên chức trong các đơn vị sự nghiệp công lập.</p>
<a href="<?php echo esc_url(cvc_legal_document_url('nghi-dinh-115-2020-nd-cp-tuyen-dung-su-dung-quan-ly-vien-chuc')); ?>" class="inline-block text-xs font-bold text-azure-400 hover:underline pt-2">Xem nội dung văn bản &rarr;</a>
</div>

<!-- Doc 3 -->
<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 hover:border-emerald-400 transition-all space-y-3">
<span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-300 px-2.5 py-0.5 rounded uppercase">Luật</span>
<h4 class="font-extrabold text-base text-white">Luật Cán bộ, công chức 2008 (Sửa đổi 2019)</h4>
<p class="text-xs text-slate-400 leading-relaxed">Quy định nghĩa vụ, quyền lợi, chế độ bầu cử và kỷ luật cán bộ công chức.</p>
<a href="<?php echo esc_url(cvc_legal_document_url('luat-can-bo-cong-chuc-2008-sua-doi-2019')); ?>" class="inline-block text-xs font-bold text-emerald-400 hover:underline pt-2">Xem nội dung văn bản &rarr;</a>
</div>
</div>
</div>
</section>

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
Tham gia ngay cộng đồng hơn 120.000 cán bộ công chức trên toàn quốc. Đăng ký nhận tư vấn lộ trình ôn thi Vòng 1 & Vòng 2 hoàn toàn miễn phí.
</p>
</div>
<div class="lg:col-span-4 flex flex-col sm:flex-row lg:flex-col gap-3 justify-end">
<button onclick="enrollComboCourse()" class="px-8 py-4 bg-gradient-to-r from-gold-500 to-gold-600 hover:from-gold-600 hover:to-gold-700 text-navy-950 font-black rounded-2xl text-sm shadow-glow-gold transition-all text-center">
ĐĂNG KÝ COMBO ƯU ĐÃI (-48%)
</button>
<button onclick="openLoginModal()" class="px-8 py-4 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold rounded-2xl text-sm text-center transition-all backdrop-blur-md">
Đăng Nhập Học Viên
</button>
</div>
</div>
</div>
</section>

</main>

<script>
function copyCouponCode(code) {
  navigator.clipboard.writeText(code);
  alert('Đã sao chép mã ưu đãi: ' + code + '!\nHãy áp dụng khi thanh toán khóa học.');
}

function enrollCourse(courseTitle) {
  alert('Đã chọn khóa học: ' + courseTitle + '\nChuyển hướng đến cổng thanh toán ưu đãi...');
  window.location.href = '<?php echo esc_url(cvc_courses_url()); ?>';
}

function enrollComboCourse() {
  alert('Đã áp dụng Mã ưu đãi TUYENDUNG2026 (Giảm 48%) cho Combo Trọn Bộ Ôn Thi Công Chức 2026!');
  window.location.href = '<?php echo esc_url(cvc_courses_url()); ?>';
}

function handleHeroSearch() {
  let query = document.getElementById('hero-search-query').value;
  if (query) {
    window.location.href = '<?php echo esc_url(cvc_courses_url()); ?>?search=' + encodeURIComponent(query);
  }
}

function setSearchTag(tag) {
  document.getElementById('hero-search-query').value = tag;
  handleHeroSearch();
}
</script>

<?php get_footer(); ?>
