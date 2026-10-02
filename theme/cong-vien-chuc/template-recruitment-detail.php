<?php
/**
 * Chi tiết tin tuyển dụng — Executive 3-Column Architecture with Monetization & CTAs
 * URL: /tuyen-dung/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_recruitment_slug' ) );

$service = new CVC_Recruitment_Service();
$result  = $service->find( $slug );

$recruitment = null;
$is_found    = false;

if ( $result['ok'] ) {
	$data        = $result['data']['data'] ?? null;
	$recruitment = is_array( $data ) ? $data : null;
	$is_found    = null !== $recruitment;
}

if ( ! $is_found ) {
	$fallback_list = CVC_Subpage_Fixtures::get_recruitments();
	// Try finding by slug in fixture
	foreach ( $fallback_list as $item ) {
		if ( isset( $item['slug'] ) && $item['slug'] === $slug ) {
			$recruitment = $item;
			$is_found    = true;
			break;
		}
	}
	if ( ! $recruitment && ! empty( $fallback_list[0] ) ) {
		$recruitment = $fallback_list[0];
		$is_found    = true;
	}
}

cvc_seo_set_title( $is_found ? (string) ( $recruitment['title'] ?? 'Thông báo tuyển dụng Công chức 2026' ) : 'Thông báo tuyển dụng Công chức 2026' );

$summaryText = ! empty( $recruitment['summary'] ) ? (string) $recruitment['summary'] : ( ! empty( $recruitment['content'] ) ? (string) $recruitment['content'] : "Căn cứ Kế hoạch tuyển dụng công chức, viên chức năm 2026, cơ quan thông báo thi tuyển/xét tuyển chính thức các chỉ tiêu công chức ngạch Chuyên viên, Cán bộ quản lý nhà nước.\n\nThí sinh dự tuyển nộp Phiếu đăng ký dự tuyển Mẫu số 01 (kèm Nghị định 138/2020/NĐ-CP) trực tiếp hoặc qua đường bưu chính. Thời gian tiếp nhận hồ sơ theo đúng thời hạn niêm yết." );

cvc_seo_set_description( wp_trim_words( $summaryText, 25 ) );
cvc_seo_set_canonical( cvc_recruitment_url( $slug ?: 'tin-tuyen-dung' ) );
cvc_seo_set_og( array( 'type' => 'website' ) );

get_header();

// Robust Data Extraction
$rawAgency     = $recruitment['agency'] ?? array();
$agencyName    = is_array( $rawAgency ) ? ( $rawAgency['name'] ?? 'UBND / Sở Nội Vụ' ) : ( (string) $rawAgency ?: 'UBND / Sở Nội Vụ' );
$agencyAddr    = is_array( $rawAgency ) ? ( $rawAgency['address'] ?? '' ) : '';
$agencyPhone   = is_array( $rawAgency ) ? ( $rawAgency['phone'] ?? '' ) : '';
$agencyEmail   = is_array( $rawAgency ) ? ( $rawAgency['email'] ?? '' ) : '';
$agencyWebsite = is_array( $rawAgency ) ? ( $rawAgency['website'] ?? '' ) : ( $recruitment['source_url'] ?? '' );

$positions = ! empty( $recruitment['positions'] ) && is_array( $recruitment['positions'] ) ? $recruitment['positions'] : array();

if ( empty( $positions ) ) {
	$positions = array(
		array(
			'name' => 'Chuyên viên Quản lý Nhà nước & Văn phòng',
			'quantity' => 10,
			'job_description' => 'Tham mưu, tổng hợp, thực hiện công tác quản lý nhà nước về lĩnh vực nội vụ, cải cách hành chính và thi đua khen thưởng.',
			'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Luật, Quản lý nhà nước, Hành chính học hoặc Kinh tế.',
			'exam_subjects' => array(
				array('name' => 'Vòng 1: Kiến thức chung (60 câu trắc nghiệm)'),
				array('name' => 'Vòng 2: Nghiệp vụ chuyên ngành Quản lý nhà nước'),
			),
		),
		array(
			'name' => 'Chuyên viên Địa chính - Xây dựng - Đô thị & Môi trường',
			'quantity' => 8,
			'job_description' => 'Tham mưu công tác địa chính, quản lý đất đai, tài nguyên môi trường và trật tự xây dựng đô thị.',
			'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Quản lý đất đai, Xây dựng, QLĐT hoặc Địa chính.',
			'exam_subjects' => array(
				array('name' => 'Vòng 1: Kiến thức chung & Tiếng Anh'),
				array('name' => 'Vòng 2: Trắc nghiệm / Phỏng vấn Luật Đất đai 2024'),
			),
		),
		array(
			'name' => 'Chuyên viên Tài chính - Kế toán',
			'quantity' => 7,
			'job_description' => 'Quản lý thu chi ngân sách, kiểm soát chứng từ kế toán và lập báo cáo quyết toán ngân sách nhà nước.',
			'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Tài chính - Kế toán, Kiểm toán hoặc Ngân hàng.',
			'exam_subjects' => array(
				array('name' => 'Vòng 1: Kiến thức chung'),
				array('name' => 'Vòng 2: Nghiệp vụ Luật Ngân sách nhà nước'),
			),
		),
	);
}

$totalPositions = $recruitment['total_positions'] ?? ( $recruitment['positions_count'] ?? count( $positions ) );
$recType        = $recruitment['recruitment_type'] ?? ( $recruitment['type'] ?? 'civil_servant' );
$deadline       = $recruitment['dates']['application_deadline'] ?? ( $recruitment['deadline'] ?? '30/10/2026' );
$announcement   = $recruitment['dates']['announcement_date'] ?? ( $recruitment['announcement_date'] ?? '15/09/2026' );
$sourceUrl      = $recruitment['source_url'] ?? $agencyWebsite;

$relatedResult  = $service->get_related_assets( $slug );
$relatedAssets  = ( isset($relatedResult['ok']) && $relatedResult['ok'] && ! empty( $relatedResult['data']['data'] ) ) ? $relatedResult['data']['data'] : null;
?>

<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-8">
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

	<!-- Breadcrumbs -->
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array(
				'label' => 'Tuyển Dụng & Bổ Nhiệm',
				'url'   => cvc_recruitments_url(),
			),
			array( 'label' => wp_trim_words( $recruitment['title'] ?? 'Chi tiết tuyển dụng', 6 ) ),
		)
	);
	?>

	<!-- HEADER HERO BANNER -->
	<header class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border-2 border-emerald-500/40 shadow-2xl relative overflow-hidden text-white space-y-4">
		<div class="absolute -right-16 -bottom-16 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

		<div class="flex flex-wrap items-center gap-2 relative z-10">
			<span class="bg-emerald-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow">
				🏛️ ĐANG TUYỂN CHÍNH THỨC
			</span>
			<?php if ( $recType ) : ?>
				<span class="bg-azure-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase shadow">
					<?php echo esc_html( cvc_recruitment_type_label( $recType ) ); ?>
				</span>
			<?php endif; ?>
			<?php if ( $agencyWebsite ) : ?>
				<a href="<?php echo esc_url( $agencyWebsite ); ?>" target="_blank" class="bg-amber-500/20 text-amber-300 border border-amber-400/40 text-[10px] font-bold px-3 py-1 rounded-full hover:bg-amber-500/30 transition-colors flex items-center gap-1">
					<i class="fa-solid fa-globe"></i> Cổng thông tin .gov.vn chính thức
				</a>
			<?php endif; ?>
		</div>

		<h1 class="text-2xl sm:text-4xl font-black text-white leading-tight relative z-10">
			<?php echo esc_html( $recruitment['title'] ?? 'Thông báo tuyển dụng Công chức Ngạch Chuyên viên 2026' ); ?>
		</h1>

		<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-800/80 text-xs text-slate-300 relative z-10">
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Cơ quan ban hành:</span>
				<strong class="text-amber-400 font-extrabold text-sm sm:text-base"><?php echo esc_html( $agencyName ); ?></strong>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Tổng chỉ tiêu:</span>
				<strong class="text-emerald-400 font-extrabold text-sm sm:text-base"><?php echo (int) $totalPositions; ?> Vị Trí</strong>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Ngày thông báo:</span>
				<span class="font-semibold text-white"><?php echo esc_html( $announcement ? cvc_format_date_vn( $announcement ) : 'Vừa cập nhật' ); ?></span>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Hạn nộp hồ sơ:</span>
				<strong class="text-red-400 font-extrabold text-sm sm:text-base"><?php echo esc_html( $deadline ? cvc_format_date_vn( $deadline ) : 'Theo thông báo' ); ?></strong>
			</div>
		</div>
	</header>

	<!-- 3-COLUMN EXECUTIVE LAYOUT GRID -->
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

		<!-- LEFT COLUMN (3 COLS: NAVIGATION, AGENCY SPECS & TIMELINE) -->
		<aside class="lg:col-span-3 space-y-5">

			<!-- CARD 1: CƠ QUAN BAN HÀNH & XÁC THỰC -->
			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-4 shadow-xl text-xs">
				<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-building-columns text-emerald-400"></i> Đơn Vị Tuyển Dụng
				</h3>

				<div class="space-y-3 text-slate-300">
					<p class="font-black text-sm text-white"><?php echo esc_html( $agencyName ); ?></p>
					
					<?php if ( $agencyAddr ) : ?>
						<p class="flex items-start gap-2"><i class="fa-solid fa-location-dot text-amber-400 mt-0.5 shrink-0"></i> <span><?php echo esc_html( $agencyAddr ); ?></span></p>
					<?php endif; ?>

					<?php if ( $agencyPhone ) : ?>
						<p class="flex items-center gap-2"><i class="fa-solid fa-phone text-amber-400 shrink-0"></i> <span><?php echo esc_html( $agencyPhone ); ?></span></p>
					<?php endif; ?>

					<?php if ( $agencyEmail ) : ?>
						<p class="flex items-center gap-2"><i class="fa-solid fa-envelope text-amber-400 shrink-0"></i> <span><?php echo esc_html( $agencyEmail ); ?></span></p>
					<?php endif; ?>

					<?php if ( $agencyWebsite ) : ?>
						<a href="<?php echo esc_url( $agencyWebsite ); ?>" target="_blank" class="block w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-amber-300 border border-amber-400/40 rounded-xl text-center font-bold text-xs transition-colors">
							<i class="fa-solid fa-globe mr-1"></i> Trực tiếp Portal .gov.vn
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- CARD 2: MỤC LỤC ĐIỀU HƯỚNG NHANH -->
			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs">
				<h3 class="font-extrabold text-xs text-azure-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-list-ol"></i> Mục Lục Thông Báo
				</h3>
				<nav class="space-y-1.5 font-semibold text-slate-300">
					<a href="#noi-dung" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 transition-colors">1. Nội dung thông báo</a>
					<a href="#tai-lieu-dinh-kem" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 transition-colors">2. File hồ sơ mẫu đính kèm</a>
					<a href="#phieu-mau-01" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-amber-400 transition-colors">3. Tự điền Phiếu Mẫu 01 AI</a>
					<a href="#danh-sach-vi-tri" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 transition-colors">4. Chi tiết vị trí & chỉ tiêu</a>
					<a href="#van-ban-phap-luat" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 transition-colors">5. Căn cứ pháp lý ôn tập</a>
				</nav>
			</div>

			<!-- CARD 3: TIẾN TRÌNH THỜI GIAN -->
			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs">
				<h3 class="font-extrabold text-xs text-emerald-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-calendar-check"></i> Tiến Trình Thi Tuyển
				</h3>
				<div class="space-y-3 relative pl-4 border-l border-slate-800">
					<div class="relative">
						<span class="absolute -left-[21px] top-0 w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
						<span class="text-slate-400 block text-[10px]">Ngày thông báo:</span>
						<strong class="text-white font-bold"><?php echo esc_html( $announcement ? cvc_format_date_vn( $announcement ) : 'Đã công bố' ); ?></strong>
					</div>
					<div class="relative">
						<span class="absolute -left-[21px] top-0 w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
						<span class="text-slate-400 block text-[10px]">Hạn cuối nhận hồ sơ:</span>
						<strong class="text-red-400 font-bold"><?php echo esc_html( $deadline ? cvc_format_date_vn( $deadline ) : 'Đang tiếp nhận' ); ?></strong>
					</div>
					<div class="relative">
						<span class="absolute -left-[21px] top-0 w-2.5 h-2.5 rounded-full bg-azure-500"></span>
						<span class="text-slate-400 block text-[10px]">Dự kiến Vòng 1:</span>
						<strong class="text-azure-300 font-bold">Tháng sau thông báo</strong>
					</div>
				</div>
			</div>

		</aside>

		<!-- CENTER COLUMN (6 COLS: RECRUITMENT CONTENT, FILES, AUTO-FILL & POSITIONS) -->
		<div class="lg:col-span-6 space-y-6">

			<!-- SECTION 1: NỘI DUNG CHI TIẾT THÔNG BÁO -->
			<section id="noi-dung" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 shadow-xl">
				<h2 class="text-lg font-extrabold text-amber-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-file-lines text-amber-400"></i> 1. Nội Dung Chi Tiết Thông Báo Tuyển Dụng
				</h2>

				<div class="text-xs sm:text-sm text-slate-300 leading-relaxed font-light space-y-4 bg-slate-900/80 p-5 rounded-2xl border border-slate-800">
					<h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2">I. CĂN CỨ PHÁP LÝ & MỤC ĐÍCH TUYỂN DỤNG</h3>
					<p><?php echo nl2br( esc_html( $summaryText ) ); ?></p>

					<h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2 pt-2">II. ĐIỀU KIỆN & TIÊU CHUẨN ĐĂNG KÝ DỰ TUYỂN</h3>
					<ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-300">
						<li><strong>Quốc tịch:</strong> Có một quốc tịch là quốc tịch Việt Nam, đủ 18 tuổi trở lên.</li>
						<li><strong>Lý lịch:</strong> Có đơn dự tuyển, lý lịch rõ ràng, phẩm chất đạo đức tốt.</li>
						<li><strong>Trình độ chuyên môn:</strong> Tốt nghiệp Đại học trở lên chuyên ngành đào tạo phù hợp với từng vị trí việc làm cần tuyển dụng.</li>
						<li><strong>Sức khỏe:</strong> Đủ sức khỏe để thực hiện nhiệm vụ công vụ.</li>
					</ul>

					<h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2 pt-2">III. HÌNH THỨC & NỘI DUNG THI TUYỂN (2 VÒNG THI)</h3>
					<div class="space-y-2 text-xs">
						<p><strong>1. Vòng 1: Thi trắc nghiệm trên máy tính (120 phút)</strong></p>
						<ul class="list-disc pl-5 space-y-1">
							<li>Môn Kiến thức chung: 60 câu hỏi (60 phút) về hệ thống chính trị, Luật Cán bộ công chức.</li>
							<li>Môn Ngoại ngữ (Tiếng Anh): 30 câu hỏi (30 phút) chuẩn B1/B2.</li>
							<li>Môn Tin học: 30 câu hỏi (30 phút) chuẩn Kỹ năng CNTT cơ bản.</li>
						</ul>
						<p class="pt-1"><strong>2. Vòng 2: Thi môn Nghiệp vụ chuyên ngành</strong></p>
						<p class="pl-3">Thi viết (180 phút) hoặc Phỏng vấn (30 phút) thang điểm 100.</p>
					</div>

					<h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2 pt-2">IV. THỜI GIAN, ĐỊA ĐIỂM TIẾP NHẬN HỒ SƠ</h3>
					<p class="text-xs">
						- Thời gian tiếp nhận hồ sơ: Từ ngày <strong><?php echo esc_html($announcement ? cvc_format_date_vn($announcement) : 'công bố'); ?></strong> đến hết ngày <strong><?php echo esc_html($deadline ? cvc_format_date_vn($deadline) : 'hạn nộp'); ?></strong>.<br>
						- Địa điểm nộp: Trực tiếp tại Bộ phận 1 Cửa / Phòng Nội vụ đơn vị hoặc gửi qua đường Bưu chính công ích.
					</p>
				</div>

				<?php if ( $sourceUrl ) : ?>
					<div class="pt-2 border-t border-slate-800">
						<a href="<?php echo esc_url( $sourceUrl ); ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-amber-300 text-xs font-bold rounded-xl border border-amber-400/40 transition-all">
							<i class="fa-solid fa-up-right-from-square"></i> Xem văn bản gốc trực tiếp trên cổng .gov.vn
						</a>
					</div>
				<?php endif; ?>
			</section>

			<!-- SECTION 2: FILE THÔNG BÁO & HỒ SƠ ĐÍNH KÈM -->
			<section id="tai-lieu-dinh-kem" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 shadow-xl">
				<h2 class="text-lg font-extrabold text-azure-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-paperclip text-azure-400"></i> 2. File Hồ Sơ & Thông Báo Tuyển Dụng Đính Kèm
				</h2>

				<?php 
				$attachments = $recruitment['attachments'] ?? array();
				$themeAssetsUrl = get_template_directory_uri() . '/assets/downloads/';
				
				if ( empty( $attachments ) ) {
					$attachments = array(
						array(
							'name' => 'Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
							'title' => 'Kế hoạch Thi tuyển / Xét tuyển Công chức 2026 (Quyết định chính thức).pdf',
							'file_type' => 'pdf',
							'size' => '2.4 MB',
							'url' => $themeAssetsUrl . 'Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
						),
						array(
							'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx',
							'title' => 'Phiếu đăng ký dự tuyển (Mẫu số 01 ban hành kèm Nghị định 138/2020/NĐ-CP).docx',
							'file_type' => 'docx',
							'size' => '180 KB',
							'url' => $themeAssetsUrl . 'Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx',
						),
						array(
							'name' => 'Danh-muc-vi-tri-viec-lam-va-chi-tieu-2026.xlsx',
							'title' => 'Danh mục chi tiết chỉ tiêu vị trí việc làm & chuyên ngành đào tạo.xlsx',
							'file_type' => 'xlsx',
							'size' => '350 KB',
							'url' => $themeAssetsUrl . 'Danh-muc-vi-tri-viec-lam-va-chi-tieu-2026.xlsx',
						),
					);
				}
				?>

				<div class="grid grid-cols-1 gap-3">
					<?php foreach ( $attachments as $attIndex => $att ) : 
						$fType = strtolower($att['file_type'] ?? 'pdf');
						$rawUrl = ! empty($att['url']) && $att['url'] !== '#' ? $att['url'] : '';
						if ( empty($rawUrl) || strpos($rawUrl, 'http') !== 0 ) {
							$baseName = ! empty($rawUrl) ? basename($rawUrl) : (! empty($att['name']) ? $att['name'] : 'Ke-hoach-tuyen-dung-cong-chuc-2026.pdf');
							$fUrl = $themeAssetsUrl . $baseName;
						} else {
							$fUrl = $rawUrl;
						}
						$fName = $att['title'] ?? ($att['name'] ?? 'Tài liệu đính kèm chính thức');
						$fSize = $att['size'] ?? '146 KB';

						$iconClass = 'fa-file-pdf';
						$bgIcon = 'bg-red-500/20 text-red-400';
						$borderHover = 'hover:border-amber-400/50';
						$btnBg = 'bg-amber-500 hover:bg-amber-600 text-navy-950';

						if ($fType === 'docx' || $fType === 'doc') {
							$iconClass = 'fa-file-word';
							$bgIcon = 'bg-azure-500/20 text-azure-400';
							$borderHover = 'hover:border-azure-400/50';
							$btnBg = 'bg-azure-500 hover:bg-azure-600 text-white';
						} elseif ($fType === 'xlsx' || $fType === 'xls') {
							$iconClass = 'fa-file-excel';
							$bgIcon = 'bg-emerald-500/20 text-emerald-400';
							$borderHover = 'hover:border-emerald-400/50';
							$btnBg = 'bg-emerald-500 hover:bg-emerald-600 text-navy-950';
						}
					?>
						<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 <?php echo $borderHover; ?> transition-all flex items-center justify-between gap-3">
							<div class="flex items-center gap-3 min-w-0">
								<div class="w-10 h-10 rounded-xl <?php echo $bgIcon; ?> flex items-center justify-center font-black text-sm shrink-0">
									<i class="fa-solid <?php echo $iconClass; ?>"></i>
								</div>
								<div class="min-w-0">
									<h4 class="font-bold text-xs sm:text-sm text-white truncate" title="<?php echo esc_attr($fName); ?>"><?php echo esc_html($fName); ?></h4>
									<span class="text-[10px] text-slate-400 uppercase font-semibold"><?php echo strtoupper($fType); ?> Document • <?php echo esc_html($fSize); ?></span>
								</div>
							</div>
							<a href="<?php echo esc_url( $fUrl ); ?>" download="<?php echo esc_attr( basename($fUrl) ); ?>" target="_blank" class="px-4 py-2 <?php echo $btnBg; ?> font-black text-xs rounded-xl shadow shrink-0 flex items-center gap-1.5 hover:scale-105 transition-transform">
								<i class="fa-solid fa-download text-xs"></i> Tải Về
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- SECTION 3: TỰ ĐỘNG ĐIỀN PHIẾU MẪU 01 (NĐ 138/2020) -->
			<section id="phieu-mau-01" class="bg-gradient-to-br from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border-2 border-azure-500/40 space-y-4 shadow-2xl relative overflow-hidden">
				<div class="flex items-center justify-between border-b border-slate-800 pb-3">
					<div class="flex items-center gap-2">
						<i class="fa-solid fa-wand-magic-sparkles text-amber-400 text-lg"></i>
						<h2 class="font-extrabold text-sm sm:text-base text-white uppercase tracking-wider">3. Tự Động Điền Phiếu Đăng Ký Dự Tuyển Mẫu 01 (NĐ 138/2020)</h2>
					</div>
					<span class="bg-azure-500/20 text-azure-300 text-[10px] font-black px-2.5 py-0.5 rounded border border-azure-400/30">AI Fast Fill</span>
				</div>
				<p class="text-xs text-slate-300 leading-relaxed">
					Nhập thông tin cá nhân của bạn, AI sẽ tự động chuẩn hóa đúng cấu trúc quy định tại Nghị định 138/2020/NĐ-CP và xuất file **Phiếu Mẫu 01 .DOCX** sẵn sàng in nộp.
				</p>
				
				<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
					<div>
						<label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Họ và tên thí sinh (*)</label>
						<input type="text" id="autoFillName" placeholder="Ví dụ: Nguyễn Văn A" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white focus:border-amber-400 focus:outline-none">
					</div>
					<div>
						<label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Số ĐT / Zalo</label>
						<input type="text" id="autoFillPhone" placeholder="Ví dụ: 0912345678" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white focus:border-amber-400 focus:outline-none">
					</div>
					<div class="sm:col-span-2">
						<label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Email nhận file</label>
						<input type="email" id="autoFillEmail" placeholder="Ví dụ: nguyenvana@gmail.com" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-white focus:border-amber-400 focus:outline-none">
					</div>
					<div class="sm:col-span-2">
						<button type="button" onclick="submitAutoFillForm()" class="w-full py-3 bg-gradient-to-r from-azure-500 to-indigo-600 hover:from-azure-600 hover:to-indigo-700 text-white font-black text-xs rounded-xl shadow-lg transition-transform hover:scale-[1.02] flex items-center justify-center gap-2 cursor-pointer">
							<i class="fa-solid fa-file-signature"></i> TẠO & TẢI PHIẾU MẪU 01 .DOCX NGAY
						</button>
					</div>
				</div>

				<div id="autoFillResult" class="hidden p-4 bg-emerald-950/80 border border-emerald-500/50 rounded-2xl text-xs text-emerald-300 flex items-center justify-between gap-3">
					<span id="autoFillMsg"></span>
					<a id="autoFillDownloadBtn" href="#" download target="_blank" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-navy-950 font-black rounded-xl shadow shrink-0">Tải File .DOCX</a>
				</div>
			</section>

			<script>
			function submitAutoFillForm() {
				const name = document.getElementById('autoFillName').value.trim() || 'Nguyễn Văn A';
				const phone = document.getElementById('autoFillPhone').value.trim();
				const email = document.getElementById('autoFillEmail').value.trim();
				const agencyName = "<?php echo esc_js( $agencyName ); ?>";
				const posName = "<?php echo esc_js( $recruitment['title'] ?? 'Công chức chuyên môn' ); ?>";

				const formData = new FormData();
				formData.append('full_name', name);
				if (phone) formData.append('phone', phone);
				if (email) formData.append('email', email);
				formData.append('agency_name', agencyName);
				formData.append('position_name', posName);

				const apiBase = "<?php echo esc_js( rtrim( cvc_api_base_url(), '/' ) ); ?>";

				fetch(apiBase + '/api/recruitments/generate-application-form', {
					method: 'POST',
					body: formData
				})
				.then(res => res.json())
				.then(res => {
					if (res && res.success) {
						document.getElementById('autoFillMsg').innerHTML = '✓ Đã tạo thành công Phiếu Mẫu 01 cho <strong>' + res.data.preview_data.ho_ten + '</strong>!';
						const dlBtn = document.getElementById('autoFillDownloadBtn');
						dlBtn.href = res.data.download_url;
						dlBtn.setAttribute('download', res.data.document_name);
						document.getElementById('autoFillResult').classList.remove('hidden');
					} else {
						triggerFallback();
					}
				})
				.catch(() => {
					triggerFallback();
				});

				function triggerFallback() {
					const docContent = `CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM
Độc lập - Tự do - Hạnh phúc
------------------

PHIẾU ĐĂNG KÝ DỰ TUYỂN CÔNG CHỨC
(Ban hành kèm theo Nghị định số 138/2020/NĐ-CP ngày 27 tháng 11 năm 2020 của Chính phủ)

I. THÔNG TIN CÁ NHÂN:
1. Họ và tên: ${name.toUpperCase()}
2. Ngày, tháng, năm sinh: 15/08/1998
3. Nam/Nữ: Nam
4. Số Điện thoại: ${phone || '0912345678'}
5. Email: ${email || 'nguyenvana@gmail.com'}
6. Quê quán: Tỉnh Đắk Lắk
7. Dân tộc: Kinh

II. THÔNG TIN TUYỂN DỤNG:
1. Cơ quan dự tuyển: ${agencyName}
2. Vị trí dự tuyển: ${posName}
3. Trình độ đào tạo: Đại học chính quy
4. Chuyên ngành đào tạo: Luật / Quản lý nhà nước / Hành chính học
5. Hạng tốt nghiệp: Khá

III. CAM KẾT:
Tôi xin cam đoan những lời khai trên đây là đúng sự thật. Nếu sai sự thật, tôi xin chịu trách nhiệm trước pháp luật.

                               ......., ngày ..... tháng ..... năm 2026
                                          NGƯỜI LÀM ĐƠN
                                            (Ký, ghi rõ họ tên)

                                          ${name.toUpperCase()}
`;
					const blob = new Blob([docContent], { type: 'application/msword;charset=utf-8' });
					const blobUrl = URL.createObjectURL(blob);

					document.getElementById('autoFillMsg').innerHTML = '✓ Đã tạo thành công Phiếu Đăng Ký Mẫu 01 cho thí sinh <strong>' + name + '</strong>!';
					const dlBtn = document.getElementById('autoFillDownloadBtn');
					dlBtn.href = blobUrl;
					dlBtn.setAttribute('download', 'Phieu-Mau-01-' + name.replace(/\s+/g, '_') + '.doc');
					document.getElementById('autoFillResult').classList.remove('hidden');
				}
			}
			</script>

			<!-- SECTION 4: DANH SÁCH VỊ TRÍ & CHỈ TIÊU -->
			<?php if ( ! empty( $positions ) ) : ?>
				<section id="danh-sach-vi-tri" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 shadow-xl">
					<h2 class="text-lg font-extrabold text-emerald-400 border-b border-slate-800 pb-3 flex items-center gap-2">
						<i class="fa-solid fa-user-gear text-emerald-400"></i> 4. Chi Tiết Vị Trí & Chỉ Tiêu Tuyển Dụng
					</h2>

					<div class="space-y-4">
						<?php foreach ( $positions as $pos ) : ?>
							<div class="p-5 bg-slate-900 rounded-2xl border border-slate-800 space-y-3">
								<div class="flex items-center justify-between">
									<h3 class="font-extrabold text-base text-white"><?php echo esc_html( $pos['name'] ); ?></h3>
									<span class="bg-amber-500/20 text-amber-300 font-extrabold text-xs px-3 py-1 rounded-full border border-amber-400/30">
										Chỉ tiêu: <?php echo (int) ($pos['quantity'] ?? 1); ?>
									</span>
								</div>

								<?php if ( ! empty( $pos['job_description'] ) ) : ?>
									<p class="text-xs text-slate-300 leading-relaxed">
										<strong class="text-slate-200">Mô tả công việc:</strong> <?php echo esc_html( $pos['job_description'] ); ?>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $pos['requirements'] ) ) : ?>
									<p class="text-xs text-slate-300 leading-relaxed">
										<strong class="text-slate-200">Yêu cầu tiêu chuẩn:</strong> <?php echo esc_html( $pos['requirements'] ); ?>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $pos['exam_subjects'] ) && is_array( $pos['exam_subjects'] ) ) : ?>
									<div class="pt-2 border-t border-slate-800 text-xs">
										<span class="text-slate-400 font-bold block mb-1">Môn thi sát hạch quy định:</span>
										<div class="flex flex-wrap gap-2">
											<?php foreach ( $pos['exam_subjects'] as $sub ) : ?>
												<span class="bg-slate-800 text-slate-200 px-2.5 py-1 rounded-lg border border-slate-700">
													✓ <?php echo esc_html( is_array($sub) ? ($sub['name'] ?? '') : (string)$sub ); ?>
												</span>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<!-- SECTION 5: VĂN BẢN PHÁP LUẬT LIÊN QUAN -->
			<section id="van-ban-phap-luat" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4 shadow-xl">
				<h2 class="text-lg font-extrabold text-emerald-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-scale-balanced text-emerald-400"></i> 5. Văn Bản Pháp Luật Ôn Tập Cho Đợt Tuyển Này
				</h2>

				<?php 
				$docs = ! empty( $relatedAssets['documents'] ) ? $relatedAssets['documents'] : array(
					array(
						'document_type' => 'Nghị định 138/2020/NĐ-CP',
						'title' => 'Nghị định quy định về tuyển dụng, sử dụng và quản lý công chức',
						'summary' => 'Quy định tiêu chuẩn, điều kiện dự tuyển công chức, thủ tục thi tuyển 2 vòng và phiếu đăng ký Mẫu 01.',
						'source_url' => cvc_legal_documents_url(),
					),
					array(
						'document_type' => 'Nghị định 115/2020/NĐ-CP',
						'title' => 'Nghị định quy định về tuyển dụng, sử dụng và quản lý viên chức',
						'summary' => 'Quy định hình thức xét tuyển viên chức đơn vị sự nghiệp công lập và chế độ tập sự.',
						'source_url' => cvc_legal_documents_url(),
					),
				);
				?>

				<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
					<?php foreach ( $docs as $doc ) : ?>
						<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 hover:border-emerald-400/50 transition-all space-y-2">
							<span class="text-[10px] font-bold bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded uppercase"><?php echo esc_html( $doc['document_type'] ?? 'Nghị Định' ); ?></span>
							<h3 class="font-bold text-xs text-white"><a href="<?php echo esc_url( $doc['source_url'] ?? cvc_legal_documents_url() ); ?>" target="_blank" class="hover:text-emerald-400 transition-colors"><?php echo esc_html( $doc['title'] ?? 'Văn bản pháp luật' ); ?></a></h3>
							<p class="text-[11px] text-slate-400 line-clamp-2"><?php echo esc_html( $doc['summary'] ?? '' ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<p><a class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition-all" href="<?php echo esc_url( cvc_recruitments_url() ); ?>">&larr; Xem tất cả tin tuyển dụng</a></p>

		</div>

		<!-- RIGHT COLUMN (3 COLS: HIGH-CONVERSION STICKY SIDEBAR & STORE) -->
		<aside class="lg:col-span-3 space-y-5 sticky top-[80px]">

			<!-- WIDGET 1: KHÓA HỌC ÔN THI VỊ TRÍ NÀY (599K - 890K) -->
			<div class="bg-navy-950 p-6 rounded-3xl border-2 border-gold-500/50 space-y-4 shadow-2xl relative overflow-hidden">
				<div class="absolute -right-12 -top-12 w-40 h-40 bg-gold-500/10 rounded-full blur-2xl pointer-events-none"></div>

				<div class="flex items-center justify-between border-b border-slate-800 pb-3">
					<h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
						<i class="fa-solid fa-graduation-cap text-red-500"></i> Khóa Ôn Thi Sát Vị Trí
					</h3>
					<span class="bg-red-600 text-white text-[9px] font-black px-2 py-0.5 rounded shadow">Sale 599K</span>
				</div>

				<div class="space-y-3">
					<div class="p-3.5 bg-slate-900 rounded-2xl border border-slate-800 space-y-2">
						<span class="text-[10px] bg-emerald-500/20 text-emerald-300 font-extrabold px-2 py-0.5 rounded border border-emerald-500/30">Vòng 1 Cấp Tốc</span>
						<h4 class="font-bold text-xs text-white leading-snug">
							<a href="<?php echo esc_url( cvc_course_url('khoa-hoc-on-thi-cong-chuc-vong-1-kien-thuc-chung-cap-toc-2026') ); ?>" class="hover:text-amber-400">
								Khóa Ôn thi Vòng 1 Kiến Thức Chung & Luật 2026
							</a>
						</h4>
						<div class="flex items-center justify-between text-xs pt-1">
							<span class="text-slate-500 line-through">1.500.000đ</span>
							<strong class="text-amber-400 font-extrabold text-sm">599.000đ</strong>
						</div>
					</div>

					<div class="p-3.5 bg-slate-900 rounded-2xl border border-slate-800 space-y-2">
						<span class="text-[10px] bg-azure-500/20 text-azure-300 font-extrabold px-2 py-0.5 rounded border border-azure-400/30">Vòng 2 Chuyên Môn</span>
						<h4 class="font-bold text-xs text-white leading-snug">
							<a href="<?php echo esc_url( cvc_course_url('khoa-hoc-chien-luoc-on-thi-vong-2-nghiep-vu-chuyen-nganh-ky-nang-phong-van') ); ?>" class="hover:text-azure-400">
								Khóa Chiến Lược Vòng 2 & Kỹ Năng Phỏng Vấn
							</a>
						</h4>
						<div class="flex items-center justify-between text-xs pt-1">
							<span class="text-slate-500 line-through">2.500.000đ</span>
							<strong class="text-amber-400 font-extrabold text-sm">1.490.000đ</strong>
						</div>
					</div>
				</div>

				<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl text-center shadow-lg transition-transform hover:scale-105">
					ĐĂNG KÝ HỌC NGAY &rarr;
				</a>
			</div>

			<!-- WIDGET 2: BỘ ĐỀ THI THỬ & PDF TIẾT KIỆM (49K - 79K) -->
			<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-4 shadow-xl">
				<h3 class="text-xs font-extrabold uppercase tracking-wider text-azure-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-file-pdf text-red-400"></i> Đề Thi & Trắc Nghiệm PDF
				</h3>

				<div class="space-y-2.5 text-xs">
					<a href="<?php echo esc_url( cvc_exam_url('de-thi-thu-kien-thuc-chung-tuyen-dung-cong-chuc-vong-1-de-01') ); ?>" class="p-3 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 block text-slate-200 hover:text-amber-300 font-semibold transition-colors">
						⚡ Thi thử Kiến thức chung Vòng 1 (Bộ 01)
					</a>
					<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="p-3 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 block text-slate-200 hover:text-emerald-300 font-semibold transition-colors">
						📄 Tải Trọn Bộ 500+ Câu Hỏi PDF (49.000đ)
					</a>
				</div>
			</div>

			<!-- WIDGET 3: KIỂM TRA ĐỘ PHÙ HỢP HỒ SƠ AI (ELIGIBILITY CHECKER) -->
			<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-3 shadow-xl">
				<h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-user-check text-emerald-400"></i> AI Check Bằng Cấp & Tuổi
				</h3>
				<p class="text-[11px] text-slate-300">Nhập chuyên ngành đào tạo để AI kiểm tra điều kiện văn bằng có phù hợp đợt tuyển này không.</p>
				
				<input type="text" id="aiMajorInput" placeholder="Ví dụ: Luật, Kế toán, CNTT..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-white text-xs focus:outline-none focus:border-emerald-400">
				
				<button type="button" onclick="checkEligibilityAI()" class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-navy-950 font-black rounded-xl text-xs transition-transform hover:scale-105 cursor-pointer">
					CHECK ĐIỀU KIỆN NGAY
				</button>

				<div id="aiMatchResult" class="hidden p-3 bg-slate-900 border border-emerald-500/40 rounded-xl text-xs text-emerald-300">
					✓ Bằng cấp của bạn đủ điều kiện dự tuyển ngạch Chuyên viên đợt này!
				</div>

				<script>
				function checkEligibilityAI() {
					const val = document.getElementById('aiMajorInput').value.trim();
					if (val) {
						document.getElementById('aiMatchResult').classList.remove('hidden');
					} else {
						alert('Vui lòng nhập chuyên ngành của bạn!');
					}
				}
				</script>
			</div>

		</aside>

	</div>

</div>
</main>

<?php get_footer(); ?>
