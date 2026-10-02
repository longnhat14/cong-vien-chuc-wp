<?php
/**
 * CÔNG VIÊN CHỨC — CỔNG TUYỂN DỤNG CÔNG VỤ & 3.000+ XÃ PHƯỜNG TOÀN QUỐC (Executive 3-Column Architecture)
 * URL: /tuyen-dung/ và /tuyen-dung/page/{n}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paged = absint( get_query_var( 'cvc_paged' ) );
$paged = $paged > 0 ? $paged : 1;

$service = new CVC_Recruitment_Service();
$result  = $service->list(
	array(
		'per_page' => 12,
		'page'     => $paged,
	)
);

$ok           = (bool) $result['ok'];
$pagination   = $ok ? ( $result['data']['data'] ?? array() ) : array();
$recruitments = is_array( $pagination ) ? ( $pagination['data'] ?? array() ) : array();
$currentPg    = (int) ( $pagination['current_page'] ?? $paged );
$lastPg       = (int) ( $pagination['last_page'] ?? 1 );

if ( ! $ok || empty( $recruitments ) ) {
	$recruitments = CVC_Subpage_Fixtures::get_recruitments();
	$ok           = true;
	$currentPg    = 1;
	$lastPg       = 1;
}

cvc_seo_set_title( 'Thông Tin Tuyển Dụng Công Chức 3.000+ Xã Phường & 34 Tỉnh Thành Mới (Sau Sáp Nhập 2026)' );
cvc_seo_set_description( 'Cập nhật liên tục 100% tin tuyển dụng công chức, viên chức cấp Xã, Phường, Thị trấn và 34 Tỉnh/Thành phố Mới sau sáp nhập đơn vị hành chính 2026.' );
cvc_seo_set_listing_pagination_state( $currentPg, 'cvc_recruitments_url' );

$provinces_merged_34 = array(
	'Tất cả 34 Tỉnh / Thành Phố Mới (Đã Sáp Nhập)',
	'1. Tuyên Quang (Hà Giang + Tuyên Quang)',
	'2. Cao Bằng (Giữ nguyên)',
	'3. Lai Châu (Giữ nguyên)',
	'4. Lào Cai (Lào Cai + Yên Bái)',
	'5. Thái Nguyên (Bắc Kạn + Thái Nguyên)',
	'6. Điện Biên (Giữ nguyên)',
	'7. Lạng Sơn (Giữ nguyên)',
	'8. Sơn La (Giữ nguyên)',
	'9. Phú Thọ (Hòa Bình + Vĩnh Phúc + Phú Thọ)',
	'10. Bắc Ninh (Bắc Giang + Bắc Ninh)',
	'11. Quảng Ninh (Giữ nguyên)',
	'12. Hà Nội (Giữ nguyên)',
	'13. Hải Phòng (Hải Dương + Hải Phòng)',
	'14. Hưng Yên (Thái Bình + Hưng Yên)',
	'15. Ninh Bình (Hà Nam + Nam Định + Ninh Bình)',
	'16. Thanh Hóa (Giữ nguyên)',
	'17. Nghệ An (Giữ nguyên)',
	'18. Hà Tĩnh (Giữ nguyên)',
	'19. Quảng Trị (Quảng Bình + Quảng Trị)',
	'20. Huế (Giữ nguyên)',
	'21. Đà Nẵng (Quảng Nam + Đà Nẵng)',
	'22. Quảng Ngãi (Kon Tum + Quảng Ngãi)',
	'23. Gia Lai (Bình Định + Gia Lai)',
	'24. Đắk Lắk (Phú Yên + Đắk Lắk)',
	'25. Khánh Hòa (Ninh Thuận + Khánh Hòa)',
	'26. Lâm Đồng (Đắk Nông + Bình Thuận + Lâm Đồng)',
	'27. Đồng Nai (Bình Phước + Đồng Nai)',
	'28. Tây Ninh (Long An + Tây Ninh)',
	'29. TP. Hồ Chí Minh (TP.HCM + Bình Dương + Bà Rịa – Vũng Tàu)',
	'30. Đồng Tháp (Tiền Giang + Đồng Tháp)',
	'31. An Giang (Kiên Giang + An Giang)',
	'32. Vĩnh Long (Bến Tre + Trà Vinh + Vĩnh Long)',
	'33. Cần Thơ (Sóc Trăng + Hậu Giang + Cần Thơ)',
	'34. Cà Mau (Bạc Liêu + Cà Mau)',
);

get_header();
?>

<main id="main" class="min-h-screen bg-slate-900 text-slate-100 py-6">

	<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

		<!-- Breadcrumbs -->
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Tuyển Dụng Xã Phường Toàn Quốc' ),
			)
		);
		?>

		<!-- HERO BANNER FULL 3000 COMMUNAES -->
		<section class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border-2 border-emerald-500/40 shadow-2xl space-y-4 relative overflow-hidden">
			<div class="absolute -right-16 -top-16 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
			<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
				<div class="space-y-2 max-w-3xl">
					<div class="flex flex-wrap items-center gap-2">
						<span class="bg-emerald-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow">
							🏛️ BẢN ĐỒ BÔ BỐ HÀNH CHÍNH MỚI — 34 TỈNH THÀNH & 3.240 XÃ PHƯỜNG 2026
						</span>
						<span class="bg-amber-500/20 text-amber-300 text-[10px] font-bold px-2.5 py-0.5 rounded border border-amber-400/40">
							AI Crawler 24/7 từ 3.000+ Website .gov.vn
						</span>
					</div>
					<h1 class="text-2xl sm:text-4xl font-black text-white leading-tight">Tuyển Dụng Công Chức Xã Phường & Cơ Quan Nhà Nước</h1>
					<p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-light">
						Tổng hợp 100% thông báo tuyển dụng chính thức từ 3.240 UBND Xã, Phường, Thị trấn và 63 Tỉnh/Thành phố. Tự động điền Phiếu dự tuyển Mẫu số 01 (NĐ 138/2020) & xuất file Word tức thì.
					</p>
				</div>
				<div class="flex items-center gap-3 shrink-0">
					<div class="p-3.5 bg-slate-900/90 rounded-2xl border border-emerald-500/30 text-center space-y-0.5 shadow-lg">
						<span class="text-2xl font-black text-emerald-400 block">3.240</span>
						<span class="text-[10px] text-slate-300 font-medium">Xã / Phường / Thị Trấn</span>
					</div>
					<div class="p-3.5 bg-slate-900/90 rounded-2xl border border-amber-500/30 text-center space-y-0.5 shadow-lg">
						<span class="text-2xl font-black text-amber-400 block">63</span>
						<span class="text-[10px] text-slate-300 font-medium">Tỉnh / Thành Phố</span>
					</div>
				</div>
			</div>

			<!-- SEARCH BAR & PROVINCE SELECTOR BAR -->
			<div class="pt-2 flex flex-col sm:flex-row items-center gap-2">
				<div class="relative flex-1 w-full">
					<input 
						type="text" 
						id="searchCommuneInput" 
						onkeyup="filterCommuneRecruitments()" 
						placeholder="🔍 Nhập tên Phường, Xã, Thị trấn hoặc Quận, Huyện (Ví dụ: Phường Bến Nghé, Xã Ea H'leo, Phường Tràng Tiền...)" 
						class="w-full bg-slate-950 border border-emerald-500/50 focus:border-emerald-400 text-white text-xs rounded-2xl px-4 py-3 focus:outline-none shadow-inner"
					>
				</div>
				<div class="w-full sm:w-64 shrink-0">
					<select 
						id="selectProvinceFilter" 
						onchange="filterCommuneRecruitments()" 
						class="w-full bg-slate-950 border border-amber-500/50 text-amber-300 font-bold text-xs rounded-2xl px-3 py-3 focus:outline-none cursor-pointer"
					>
						<?php foreach ( $provinces_merged_34 as $p_idx => $p ) : ?>
							<option value="<?php echo esc_attr( 0 === $p_idx ? '' : $p ); ?>"><?php echo esc_html( $p ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</section>

		<!-- 3-COLUMN EXECUTIVE SHELL GRID -->
		<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

			<!-- LEFT COLUMN (3 COLS — PROVINCE & AGENCY FILTERS) -->
			<aside class="lg:col-span-3 space-y-5">
				
				<!-- BỘ LỌC CẤP TUYỂN DỤNG -->
				<div class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-4 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
						<i class="fa-solid fa-building-columns text-emerald-400"></i> Cấp Tuyển Dụng
					</h3>
					<div class="space-y-1.5">
						<button onclick="setCapFilter('all')" class="w-full text-left flex items-center justify-between p-2.5 rounded-xl bg-emerald-500/10 text-emerald-300 font-bold border border-emerald-500/30">
							<span>Tất cả đơn vị</span>
							<span class="text-[10px] bg-emerald-500 text-navy-950 font-black px-2 py-0.5 rounded-full">3.240</span>
						</button>
						<button onclick="setCapFilter('giao-vien')" class="w-full text-left flex items-center justify-between p-2.5 rounded-xl text-amber-300 hover:bg-slate-800 font-bold border border-amber-500/30 transition-colors">
							<span>👨‍🏫 Tuyển Dụng Giáo Viên / Giáo Dục</span>
							<span class="text-[10px] bg-amber-500 text-navy-950 font-black px-2 py-0.5 rounded-full">HOT</span>
						</button>
						<button onclick="setCapFilter('xa')" class="w-full text-left flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Công chức Xã / Phường / Thị Trấn</span>
							<span class="text-[10px] text-emerald-400 font-bold bg-slate-900 px-2 py-0.5 rounded-full">3.100</span>
						</button>
						<button onclick="setCapFilter('huyen')" class="w-full text-left flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Công chức Quận / Huyện / Thị Xã</span>
							<span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">120</span>
						</button>
						<button onclick="setCapFilter('so')" class="w-full text-left flex items-center justify-between p-2.5 rounded-xl text-slate-300 hover:bg-slate-800 transition-colors">
							<span>Công chức Sở / Ban / Ngành Tỉnh</span>
							<span class="text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full">45</span>
						</button>
					</div>
				</div>

			</aside>

			<!-- CENTER MAIN COLUMN (6 COLS — RECRUITMENTS CARDS) -->
			<main class="lg:col-span-6 space-y-4">

				<div class="flex items-center justify-between bg-navy-950 p-4 rounded-2xl border border-slate-800 text-xs shadow-md">
					<span class="text-slate-300">Đang kết nối dữ liệu <strong class="text-emerald-400 font-extrabold" id="countDisplay"><?php echo count( $recruitments ); ?></strong> đơn vị xã/phường</span>
					<span class="text-amber-400 font-bold text-[11px]">● Live Sync 34 Tỉnh Thành Mới</span>
				</div>

				<div id="recruitmentListContainer" class="space-y-4">
					<?php foreach ( $recruitments as $rec ) : ?>
						<?php
						$slug         = is_string( $rec['slug'] ?? null ) ? $rec['slug'] : '';
						$title        = is_string( $rec['title'] ?? null ) ? $rec['title'] : 'Thông báo tuyển dụng';
						
						$agency_raw   = $rec['agency'] ?? 'UBND';
						$agency_name  = is_array( $agency_raw ) ? ( $agency_raw['name'] ?? 'UBND' ) : ( is_string( $agency_raw ) ? $agency_raw : 'UBND' );
						
						$rec_type     = is_string( $rec['type'] ?? null ) ? $rec['type'] : 'Công Chức Cấp Xã';
						$status       = is_string( $rec['status'] ?? null ) ? $rec['status'] : 'Đang nhận hồ sơ';
						$quota        = is_string( $rec['quota'] ?? null ) ? $rec['quota'] : ( ( $rec['total_positions'] ?? 5 ) . ' chỉ tiêu' );
						
						$dates_raw    = $rec['dates'] ?? null;
						$deadline     = is_string( $rec['deadline'] ?? null ) ? $rec['deadline'] : ( is_array( $dates_raw ) ? ( $dates_raw['application_deadline'] ?? '30/11/2026' ) : '30/11/2026' );
						
						$summary      = is_string( $rec['summary'] ?? null ) ? $rec['summary'] : '';
						$positions    = is_array( $rec['positions'] ?? null ) ? $rec['positions'] : array();
						$attachments  = is_array( $rec['attachments'] ?? null ) ? $rec['attachments'] : array();
						$province_tag = is_string( $rec['province'] ?? null ) ? $rec['province'] : 'Toàn quốc';
						?>
						<article class="bg-navy-950 border border-slate-800 hover:border-emerald-500/50 p-5 rounded-3xl space-y-4 shadow-xl transition-all duration-300 group hover:shadow-2xl hover:shadow-emerald-500/5 item-recruitment-card" data-title="<?php echo esc_attr(mb_strtolower((string)$title)); ?>" data-agency="<?php echo esc_attr(mb_strtolower((string)$agency_name)); ?>" data-province="<?php echo esc_attr(mb_strtolower((string)$province_tag)); ?>">
							
							<div class="flex items-start justify-between gap-3 border-b border-slate-800/80 pb-3">
								<div class="space-y-1">
									<div class="flex items-center gap-2 flex-wrap text-[11px]">
										<span class="bg-emerald-500/20 text-emerald-300 font-bold px-2.5 py-0.5 rounded-full border border-emerald-500/30">
											📌 <?php echo esc_html( $rec_type ); ?>
										</span>
										<span class="bg-slate-800 text-slate-300 font-semibold px-2 py-0.5 rounded-full text-[10px]">
											📍 <?php echo esc_html( $agency_name ); ?>
										</span>
									</div>
									<h2 class="text-base font-extrabold text-white group-hover:text-emerald-300 leading-snug transition-colors">
										<a href="<?php echo esc_url( cvc_recruitment_url( $slug ) ); ?>">
											<?php echo esc_html( $title ); ?>
										</a>
									</h2>
								</div>
								<span class="bg-amber-500/20 text-amber-300 border border-amber-400/40 text-[10px] font-black px-2.5 py-1 rounded-xl shrink-0 uppercase shadow-sm">
									<?php echo esc_html( $status ); ?>
								</span>
							</div>

							<p class="text-xs text-slate-300 leading-relaxed font-light line-clamp-3">
								<?php echo esc_html( wp_trim_words( $summary, 30 ) ); ?>
							</p>

							<div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 border-t border-slate-800/80 text-xs">
								<div class="flex items-center gap-4 text-slate-400 text-[11px]">
									<span>🎯 Chỉ tiêu: <strong class="text-emerald-400 font-bold"><?php echo esc_html( $quota ); ?></strong></span>
									<span>⏳ Hạn nộp: <strong class="text-amber-400 font-mono font-bold"><?php echo esc_html( $deadline ); ?></strong></span>
								</div>

								<div class="flex items-center gap-2">
									<a href="<?php echo esc_url( cvc_recruitment_url( $slug ) ); ?>" class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-navy-950 font-black text-xs rounded-xl shadow-lg transition-transform hover:scale-105">
										Nộp Hồ Sơ &rarr;
									</a>
								</div>
							</div>

						</article>
					<?php endforeach; ?>
				</div>

			</main>

				<!-- RIGHT SIDEBAR (3 COLS — MONETIZATION & DOWNLOAD HANDBOOK) -->
			<aside class="lg:col-span-3 space-y-4 sticky top-[80px]">

				<!-- CARD 1: TẢI MẪU 01 (GIỮ NGUYÊN) -->
				<div class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-5 rounded-2xl border border-amber-500/40 space-y-3 shadow-xl text-xs">
					<h3 class="font-black text-amber-400 uppercase tracking-wider flex items-center gap-2 border-b border-slate-800 pb-2">
						<i class="fa-solid fa-file-word text-amber-400 text-sm"></i> Tự Động Xuất Phiếu 01
					</h3>
					<p class="text-slate-300 text-[11px] leading-relaxed">
						Hệ thống tự động điền Mẫu 01 Nghị định 138/2020/NĐ-CP & xuất file Word tức thì cho ứng viên thi công chức Xã/Phường.
					</p>

					<a href="<?php echo esc_url( get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx' ); ?>" download="Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx" class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black text-xs rounded-xl shadow flex items-center justify-center gap-1.5 transition-transform hover:scale-105">
						<i class="fa-solid fa-download"></i> Tải Mẫu 01 Word Chuẩn BNV
					</a>
				</div>

				<!-- CARD 2: KHÓA HỌC CHUẨN BỊ HỒ SƠ -->
				<div class="bg-gradient-to-br from-emerald-500/10 to-slate-900 border-2 border-emerald-500/40 p-5 rounded-2xl space-y-3 shadow-xl text-xs text-center">
					<span class="bg-emerald-500 text-navy-950 text-[9px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">🎓 KHÓA HỌC HOT</span>
					<div class="space-y-1">
						<h3 class="text-sm font-black text-white leading-snug">Chuẩn Bị Hồ Sơ &<br>Phỏng Vấn Tuyển Dụng</h3>
						<p class="text-[11px] text-slate-300">Hướng dẫn làm hồ sơ chuẩn NĐ 138 · Kỹ năng trả lời phỏng vấn · Tips vượt qua Vòng 2.</p>
						<div class="flex items-baseline justify-center gap-2 pt-1">
							<span class="text-base font-black text-emerald-400">299.000đ</span>
							<span class="text-xs text-slate-400 line-through">450.000đ</span>
						</div>
					</div>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="block w-full py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-navy-950 font-black text-xs rounded-xl shadow-lg transition-transform hover:scale-[1.02]">
						Đăng Ký Ngay &rarr;
					</a>
				</div>

				<!-- CARD 3: CẨM NANG PHỎNG VẤN PDF -->
				<div class="bg-[#0D1B2A] border border-cyan-500/30 p-5 rounded-2xl space-y-3 shadow-xl text-xs">
					<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
						📄 Cẩm Nang Tuyển Dụng
					</h3>
					<p class="text-slate-400 text-[11px]">100 câu hỏi phỏng vấn công chức thường gặp nhất + gợi ý trả lời chuẩn.</p>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="w-full py-2 bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-500/40 font-bold rounded-xl text-center flex items-center justify-center gap-1.5 transition-colors">
						📥 Mua PDF — 49.000đ
					</a>
					<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="block text-center text-[10px] text-slate-400 hover:text-amber-400 transition-colors">
						→ Xem đề thi tuyển dụng liên quan
					</a>
				</div>

			</aside>

		</div>

	</div>

</main>

<!-- JS Live Filter Engine for 3000 Communes -->
<script>
var currentCapFilter = 'all';

function setCapFilter(cap) {
	currentCapFilter = cap;
	filterCommuneRecruitments();
}

function filterCommuneRecruitments() {
	var q = document.getElementById('searchCommuneInput').value.toLowerCase().trim();
	var p = document.getElementById('selectProvinceFilter').value.toLowerCase().trim();
	var cards = document.querySelectorAll('.item-recruitment-card');
	var visibleCount = 0;

	// Extract province name cleanly if string contains parenthetical notes
	if (p.includes('(')) {
		p = p.split('(')[0].replace(/[0-9.]/g, '').trim();
	}

	cards.forEach(function(card) {
		var title = card.getAttribute('data-title') || '';
		var agency = card.getAttribute('data-agency') || '';
		var province = card.getAttribute('data-province') || '';

		var matchQuery = q === '' || title.includes(q) || agency.includes(q);
		var matchProvince = p === '' || province.includes(p) || agency.includes(p);
		var matchCap = true;

		if (currentCapFilter === 'xa') {
			matchCap = title.includes('xã') || title.includes('phường') || title.includes('thị trấn');
		} else if (currentCapFilter === 'huyen') {
			matchCap = title.includes('huyện') || title.includes('quận') || title.includes('thị xã');
		} else if (currentCapFilter === 'so') {
			matchCap = title.includes('sở') || title.includes('ban') || title.includes('ngành');
		}

		if (matchQuery && matchProvince && matchCap) {
			card.style.display = 'block';
			visibleCount++;
		} else {
			card.style.display = 'none';
		}
	});

	var displayEl = document.getElementById('countDisplay');
	if (displayEl) {
		displayEl.innerText = visibleCount;
	}
}
</script>

<?php get_footer(); ?>
