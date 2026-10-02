<?php
/**
 * Sub-pages Fallback Fixtures Service
 * Cung cấp dữ liệu fallback đầy đủ cho 18 trang con (Courses, Recruitments, Topics, Knowledge, Exams, Legal Documents, Account)
 * khi Backend API Laravel chưa khởi chạy hoặc trả về lỗi kết nối.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Subpage_Fixtures {

	public static function get_courses(): array {
		return array(
			array(
				'id' => 1,
				'slug' => 'chuyen-vien-chinh-2026',
				'title' => 'Bồi dưỡng Kiến thức Quản lý Nhà nước ngạch Chuyên viên chính 2026',
				'summary' => 'Cập nhật hệ thống pháp luật hành chính mới nhất, phương pháp xây dựng văn bản quy phạm pháp luật và kỹ năng lãnh đạo cấp phòng.',
				'price' => '2.450.000đ',
				'price_number' => 2450000,
				'hours' => '60 Giờ Học',
				'rating' => '4.9',
				'reviews_count' => 1200,
				'category' => 'Quản Lý Nhà Nước',
				'badge' => '★ Bán Chạy Nhất',
				'instructor' => array(
					'name' => 'TS. Nguyễn Văn Hùng',
					'title' => 'Nguyên Lãnh đạo Học viện Hành chính',
					'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
				),
			),
			array(
				'id' => 2,
				'slug' => 'ky-nang-xu-ly-cong-vu',
				'title' => 'Kỹ năng Xử lý Tình huống Công vụ & Tiếp công dân Hiện đại',
				'summary' => 'Xây dựng hình ảnh Cán bộ chuyên nghiệp, giải quyết khủng hoảng truyền thông công vụ và văn hóa ứng xử cơ quan nhà nước.',
				'price' => '1.890.000đ',
				'price_number' => 1890000,
				'hours' => '45 Giờ Học',
				'rating' => '5.0',
				'reviews_count' => 890,
				'category' => 'Kỹ Năng Lãnh Đạo',
				'badge' => '✦ Độc Quyền Enterprise',
				'instructor' => array(
					'name' => 'PGS. TS. Trần Thị Mai',
					'title' => 'Chuyên gia Văn hóa Công vụ',
					'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=150&q=80',
				),
			),
			array(
				'id' => 3,
				'slug' => 'ung-dung-ai-hanh-chinh',
				'title' => 'Ứng dụng AI & Công nghệ Số trong Quản trị Hành chính Công',
				'summary' => 'Tự động hóa soạn thảo văn bản hành chính, quản lý hồ sơ số và sử dụng AI hỗ trợ ra quyết định công vụ nhanh chóng.',
				'price' => 'MIỄN PHÍ',
				'price_number' => 0,
				'hours' => '30 Giờ Học',
				'rating' => '4.9',
				'reviews_count' => 2100,
				'category' => 'Chuyển Đổi Số',
				'badge' => '✓ Miễn Phí Học Thử',
				'instructor' => array(
					'name' => 'ThS. Lê Hoàng Nam',
					'title' => 'Cố vấn Chuyển đổi số Chính phủ',
					'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80',
				),
			),
		);
	}

	public static function get_recruitments(): array {
		return array(
			array(
				'id' => 100,
				'slug' => 'ubnd-cu-mgar-tuyen-dung-giao-vien-2026',
				'title' => 'UBND Huyện Cư M\'gar Thông Báo Xét Tuyển 45 Chỉ Tiêu Viên Chức Giáo Viên Mầm Non, Tiểu Học & THCS 2026',
				'summary' => "Căn cứ Kế hoạch xét tuyển viên chức giáo dục năm 2026, UBND Huyện Cư M'gar (Phòng Giáo dục & Đào tạo) thông báo xét tuyển chính thức 45 chỉ tiêu giáo viên ngạch Hạng III tại các trường Mầm non, Tiểu học và THCS trực thuộc trên địa bàn Huyện.\n\n- Nơi tiếp nhận hồ sơ: Phòng Giáo dục và Đào tạo Huyện Cư M'gar.\n- Địa chỉ: Đường Hùng Vương, Thị trấn Quảng Phú, Huyện Cư M'gar, Tỉnh Đắk Lắk.\n- Điện thoại liên hệ: 0262.3874.112 | Email: phonggddt@cumgar.daklak.gov.vn | Website: https://cumgar.daklak.gov.vn\n- Thời gian nộp hồ sơ: Từ ngày 20/09/2026 đến hết ngày 10/11/2026.",
				'agency' => array(
					'name' => 'Phòng Giáo Dục & Đào Tạo Huyện Cư M\'gar, Tỉnh Đắk Lắk',
					'address' => 'Đường Hùng Vương, Thị trấn Quảng Phú, Huyện Cư M\'gar, Tỉnh Đắk Lắk',
					'phone' => '0262.3874.112',
					'email' => 'phonggddt@cumgar.daklak.gov.vn',
					'website' => 'https://cumgar.daklak.gov.vn',
				),
				'recruitment_type' => 'public_employee',
				'type' => 'Viên Chức Giáo Dục',
				'category_label' => 'Tuyển Dụng Giáo Viên',
				'deadline' => '10/11/2026',
				'total_positions' => 45,
				'positions_count' => 45,
				'quota' => '45 chỉ tiêu',
				'status' => 'Đang Nhận Hồ Sơ',
				'badge_color' => 'emerald',
				'source_url' => 'https://cumgar.daklak.gov.vn/tuyen-dung-giao-vien-2026',
				'dates' => array(
					'announcement_date' => '2026-09-20',
					'application_deadline' => '2026-11-10',
				),
				'positions' => array(
					array(
						'name' => 'Giáo viên Mầm non Hạng III (Mã số: V.07.02.26)',
						'quantity' => 15,
						'job_description' => 'Tổ chức các hoạt động nuôi dưỡng, chăm sóc, giáo dục trẻ em theo chương trình giáo dục mầm non quốc gia.',
						'requirements' => 'Tốt nghiệp Cao đẳng Sư phạm Mầm non trở lên (theo quy định chuẩn trình độ Luật Giáo dục 2019).',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiểm tra điều kiện Phiếu đăng ký dự tuyển & Văn bằng'),
							array('name' => 'Vòng 2: Thực hành tiết dạy / Phỏng vấn nghiệp vụ sư phạm (Thang điểm 100)'),
						),
					),
					array(
						'name' => 'Giáo viên Tiểu học Hạng III (Mã số: V.07.03.29)',
						'quantity' => 15,
						'job_description' => 'Giảng dạy các môn học bậc Tiểu học, quản lý chủ nhiệm lớp và tổ chức hoạt động trải nghiệm.',
						'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Sư phạm Tiểu học hoặc Cử nhân ngành phù hợp có Chứng chỉ nghiệp vụ sư phạm.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Trắc nghiệm Kiến thức chung trên máy tính'),
							array('name' => 'Vòng 2: Thực hành thiết kế kế hoạch bài dạy & Phỏng vấn'),
						),
					),
					array(
						'name' => 'Giáo viên THCS Hạng III (Môn Toán, Ngữ Văn, Tiếng Anh)',
						'quantity' => 15,
						'job_description' => 'Giảng dạy bộ môn theo chương trình giáo dục phổ thông 2018, bồi dưỡng học sinh giỏi bậc THCS.',
						'requirements' => 'Tốt nghiệp Đại học Sư phạm chuyên ngành Toán, Ngữ văn, Tiếng Anh hoặc tương đương.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung & Ngoại ngữ'),
							array('name' => 'Vòng 2: Thi viết môn Chuyên môn nghiệp vụ (180 phút)'),
						),
					),
				),
				'attachments' => array(
					array(
						'name' => 'Ke-hoach-tuyen-dung-giao-vien-cumgar-2026.pdf',
						'title' => 'Kế hoạch thi tuyển / xét tuyển 45 viên chức giáo viên Huyện Cư M\'gar 2026 (.PDF)',
						'file_type' => 'pdf',
						'size' => '2.8 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
					),
					array(
						'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND115-BNV.docx',
						'title' => 'Phiếu đăng ký dự tuyển viên chức Mẫu số 01 kèm Nghị định 115/2020/NĐ-CP (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
					),
				),
			),
			array(
				'id' => 1001,
				'slug' => 'so-gd-dt-dak-lak-tuyen-dung-giao-vien-thpt',
				'title' => 'Sở Giáo Dục & Đào Tạo Tỉnh Đắk Lắk Tuyển Dụng 60 Chỉ Tiêu Giáo Viên THPT Ngạch Hạng III Năm 2026',
				'summary' => "Sở Giáo dục và Đào tạo tỉnh Đắk Lắk thông báo kế hoạch xét tuyển viên chức giáo viên THPT ngạch Hạng III (Mã số V.07.05.15) năm 2026 cho các Trường THPT trực thuộc trên địa bàn toàn tỉnh.\n\n- Nơi tiếp nhận hồ sơ: Văn phòng Sở Giáo dục và Đào tạo Tỉnh Đắk Lắk (Phòng Tổ chức Cán bộ).\n- Địa chỉ: Số 22 Lê Duẩn, Thành phố Buôn Ma Thuột, Tỉnh Đắk Lắk.\n- Điện thoại liên hệ: 0262.3852.444 | Email: sogddt@daklak.edu.vn | Website: https://daklak.edu.vn\n- Thời gian nộp hồ sơ: Từ ngày 15/09/2026 đến hết ngày 05/11/2026.",
				'agency' => array(
					'name' => 'Sở Giáo Dục & Đào Tạo Tỉnh Đắk Lắk',
					'address' => 'Số 22 Lê Duẩn, Thành phố Buôn Ma Thuột, Tỉnh Đắk Lắk',
					'phone' => '0262.3852.444',
					'email' => 'sogddt@daklak.edu.vn',
					'website' => 'https://daklak.edu.vn',
				),
				'recruitment_type' => 'public_employee',
				'type' => 'Viên Chức Giáo Dục',
				'category_label' => 'Tuyển Dụng Giáo Viên THPT',
				'deadline' => '05/11/2026',
				'total_positions' => 60,
				'positions_count' => 60,
				'quota' => '60 chỉ tiêu',
				'status' => 'Đang Nhận Hồ Sơ',
				'badge_color' => 'sky',
				'source_url' => 'https://daklak.edu.vn/thong-bao-tuyen-dung-giao-vien-thpt-2026',
				'dates' => array(
					'announcement_date' => '2026-09-15',
					'application_deadline' => '2026-11-05',
				),
				'positions' => array(
					array(
						'name' => 'Giáo viên THPT Môn Toán (Ngạch Hạng III - Mã V.07.05.15)',
						'quantity' => 15,
						'job_description' => 'Giảng dạy môn Toán học bậc THPT, bồi dưỡng học sinh giỏi quốc gia và ôn thi tốt nghiệp THPT.',
						'requirements' => 'Cử nhân Sư phạm Toán trở lên hoặc Cử nhân Toán học có chứng chỉ nghiệp vụ sư phạm.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiểm tra hồ sơ & văn bằng'),
							array('name' => 'Vòng 2: Thi thực hành thiết kế giáo án & Phỏng vấn'),
						),
					),
					array(
						'name' => 'Giáo viên THPT Môn Ngữ Văn (Ngạch Hạng III)',
						'quantity' => 15,
						'job_description' => 'Giảng dạy bộ môn Ngữ văn THPT theo chương trình GDPT 2018.',
						'requirements' => 'Cử nhân Sư phạm Ngữ văn trở lên.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiểm tra điều kiện dự tuyển'),
							array('name' => 'Vòng 2: Thực hành bài giảng chuyên môn'),
						),
					),
					array(
						'name' => 'Giáo viên THPT Môn Tiếng Anh (Ngạch Hạng III)',
						'quantity' => 15,
						'job_description' => 'Giảng dạy Tiếng Anh THPT, có chứng chỉ tiếng Anh đạt chuẩn B2/C1 Châu Âu.',
						'requirements' => 'Cử nhân Sư phạm Tiếng Anh hoặc Cử nhân Ngôn ngữ Anh.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Trắc nghiệm Tiếng Anh & KTC'),
							array('name' => 'Vòng 2: Phỏng vấn trực tiếp bằng Tiếng Anh'),
						),
					),
					array(
						'name' => 'Giáo viên THPT Môn Tin Học & Chuyển Đổi Số',
						'quantity' => 15,
						'job_description' => 'Giảng dạy môn Tin học THPT, lập trình Python/C++ và quản trị phòng máy vi tính.',
						'requirements' => 'Cử nhân Sư phạm Tin học hoặc Cử nhân CNTT có chứng chỉ NVSP.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Trắc nghiệm máy tính'),
							array('name' => 'Vòng 2: Thực hành lập trình & giảng dạy trên máy'),
						),
					),
				),
				'attachments' => array(
					array(
						'name' => 'Ke-hoach-tuyen-dung-giao-vien-thpt-dak-lak-2026.pdf',
						'title' => 'Thông báo kế hoạch tuyển dụng 60 giáo viên THPT Sở GD&ĐT Đắk Lắk (.PDF gốc)',
						'file_type' => 'pdf',
						'size' => '3.2 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
					),
					array(
						'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND115-BNV.docx',
						'title' => 'Phiếu đăng ký dự tuyển viên chức Mẫu số 01 kèm NĐ 115 (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
					),
				),
			),
			array(
				'id' => 101,
				'slug' => 'ubnd-ea-hleo-tuyen-dung-2026',
				'title' => 'UBND Huyện Ea H\'leo Thông Báo Tuyển Dụng 25 Chỉ Tiêu Công Chức Cấp Xã 2026',
				'summary' => "Căn cứ Kế hoạch tuyển dụng công chức cấp xã năm 2026, UBND Huyện Ea H'leo thông báo thi tuyển chính thức 25 chỉ tiêu công chức ngạch Chuyên viên tại các UBND Xã, Thị trấn trực thuộc.\n\nThí sinh đăng ký dự tuyển nộp Phiếu đăng ký dự tuyển (Mẫu số 01 kèm Nghị định 138/2020/NĐ-CP) trực tiếp tại Phòng Nội vụ Huyện Ea H'leo hoặc gửi qua đường bưu chính.\n\nThời gian tiếp nhận hồ sơ từ ngày 15/09/2026 đến hết ngày 30/10/2026. Mọi thông tin chi tiết được niêm yết công khai tại Cổng thông tin điện tử Huyện Ea H'leo.",
				'agency' => array(
					'name' => 'UBND Huyện Ea H\'leo, Tỉnh Đắk Lắk',
					'address' => 'Thị trấn Ea Drăng, Huyện Ea H\'leo, Tỉnh Đắk Lắk',
					'phone' => '0262.3777.888',
					'email' => 'noivu@eahleo.daklak.gov.vn',
					'website' => 'https://eahleo.daklak.gov.vn',
				),
				'recruitment_type' => 'civil_servant',
				'type' => 'Công Chức Cấp Xã',
				'deadline' => '30/10/2026',
				'total_positions' => 25,
				'positions_count' => 25,
				'quota' => '25 chỉ tiêu',
				'status' => 'Đang Nhận Hồ Sơ',
				'badge_color' => 'emerald',
				'source_url' => 'https://eahleo.daklak.gov.vn/thong-bao-tuyen-dung-2026',
				'dates' => array(
					'announcement_date' => '2026-09-15',
					'application_deadline' => '2026-10-30',
				),
				'positions' => array(
					array(
						'name' => 'Chuyên viên Quản lý Nhà nước & Văn phòng - Thống kê',
						'quantity' => 10,
						'job_description' => 'Tham mưu, tổng hợp, thực hiện công tác quản lý nhà nước về lĩnh vực nội vụ, cải cách hành chính, văn phòng - thống kê.',
						'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Luật, Quản lý nhà nước, Hành chính học hoặc CNTT.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung (60 câu trắc nghiệm)'),
							array('name' => 'Vòng 2: Nghiệp vụ chuyên ngành Quản lý nhà nước'),
						),
					),
					array(
						'name' => 'Chuyên viên Địa chính - Xây dựng - Đô thị & Môi trường',
						'quantity' => 8,
						'job_description' => 'Tham mưu công tác quản lý đất đai, tài nguyên môi trường, trật tự xây dựng và hạ tầng đô thị trên địa bàn xã.',
						'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Quản lý đất đai, Xây dựng, QLĐT hoặc Địa chính.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung & Tiếng Anh'),
							array('name' => 'Vòng 2: Trắc nghiệm / Phỏng vấn Luật Đất đai 2024'),
						),
					),
					array(
						'name' => 'Chuyên viên Tài chính - Kế toán',
						'quantity' => 7,
						'job_description' => 'Quản lý thu chi ngân sách cấp xã, kiểm soát chứng từ kế toán và lập báo cáo quyết toán ngân sách nhà nước.',
						'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Tài chính - Kế toán, Kiểm toán hoặc Ngân hàng.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung'),
							array('name' => 'Vòng 2: Nghiệp vụ Luật Ngân sách nhà nước'),
						),
					),
				),
				'attachments' => array(
					array(
						'name' => 'Ke-hoach-tuyen-dung-ubnd-huyen-ea-hleo-2026.pdf',
						'title' => 'Kế hoạch thi tuyển 25 chỉ tiêu công chức cấp xã Huyện Ea H\'leo (.PDF gốc)',
						'file_type' => 'pdf',
						'size' => '2.4 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-ubnd-huyen-ea-hleo-2026.pdf',
					),
					array(
						'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
						'title' => 'Phiếu đăng ký dự tuyển Mẫu số 01 kèm Nghị định 138/2020/NĐ-CP chuẩn BNV (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
					),
					array(
						'name' => 'Danh-muc-chi-tieu-vi-tri-viec-lam-ea-hleo-2026.xlsx',
						'title' => 'Danh mục chỉ tiêu vị trí việc làm & chuyên ngành đào tạo Huyện Ea H\'leo (.XLSX)',
						'file_type' => 'xlsx',
						'size' => '350 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Danh-muc-chi-tieu-vi-tri-viec-lam-ea-hleo-2026.xlsx',
					),
				),
			),
			array(
				'id' => 102,
				'slug' => 'so-tai-chinh-dak-lak-tuyen-dung',
				'title' => 'Sở Tài Chính Tỉnh Đắk Lắk Tuyển Dụng 10 Chuyên Viên Quản Lý Ngân Sách 2026',
				'summary' => "Sở Tài chính tỉnh Đắk Lắk thông báo kế hoạch thi tuyển công chức ngạch Chuyên viên năm 2026 với 10 chỉ tiêu thuộc các phòng Quản lý Ngân sách, Giá - Công sản và Đầu tư.\n\nƯu tiên ứng viên có chứng chỉ chuyên môn và am hiểu Luật Ngân sách Nhà nước. Thí sinh nộp hồ sơ trực tiếp tại Văn phòng Sở Tài chính.",
				'agency' => array(
					'name' => 'Sở Tài Chính Tỉnh Đắk Lắk',
					'address' => 'Số 08 Lý Tự Trọng, TP. Buôn Ma Thuột, Tỉnh Đắk Lắk',
					'phone' => '0262.3852.123',
					'email' => 'sotaichinh@daklak.gov.vn',
					'website' => 'https://sotaichinh.daklak.gov.vn',
				),
				'recruitment_type' => 'civil_servant',
				'type' => 'Công Chức Cấp Tỉnh',
				'deadline' => '15/11/2026',
				'total_positions' => 10,
				'positions_count' => 10,
				'quota' => '10 chỉ tiêu',
				'status' => 'Sắp Hết Hạn',
				'badge_color' => 'amber',
				'source_url' => 'https://sotaichinh.daklak.gov.vn/tuyen-dung-2026',
				'dates' => array(
					'announcement_date' => '2026-09-20',
					'application_deadline' => '2026-11-15',
				),
				'positions' => array(
					array(
						'name' => 'Chuyên viên Quản lý Ngân sách & Giá công sản',
						'quantity' => 6,
						'job_description' => 'Tham mưu lập dự toán ngân sách địa phương, phân bổ ngân sách và thẩm định dự án đầu tư công.',
						'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Tài chính, Kế toán, Kiểm toán.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung'),
							array('name' => 'Vòng 2: Luật Ngân sách & Quản lý tài sản công'),
						),
					),
					array(
						'name' => 'Chuyên viên Thanh tra Tài chính - Kiểm toán nội bộ',
						'quantity' => 4,
						'job_description' => 'Thực hiện công tác thanh tra tài chính, kiểm tra thu chi ngân sách các đơn vị sự nghiệp.',
						'requirements' => 'Tốt nghiệp Đại học chuyên ngành Kiểm toán, Luật Tài chính hoặc Kế toán.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung'),
							array('name' => 'Vòng 2: Phỏng vấn nghiệp vụ Thanh tra tài chính'),
						),
					),
				),
				'attachments' => array(
					array(
						'name' => 'Ke-hoach-tuyen-dung-so-tai-chinh-dak-lak-2026.pdf',
						'title' => 'Kế hoạch tuyển dụng 10 chỉ tiêu Chuyên viên Sở Tài chính tỉnh Đắk Lắk (.PDF gốc)',
						'file_type' => 'pdf',
						'size' => '3.1 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-so-tai-chinh-dak-lak-2026.pdf',
					),
					array(
						'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx',
						'title' => 'Phiếu đăng ký dự tuyển công chức Mẫu 01 (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx',
					),
					array(
						'name' => 'Danh-muc-vi-tri-viec-lam-va-chi-tieu-2026.xlsx',
						'title' => 'Danh mục vị trí việc làm Chuyên viên Quản lý Ngân sách (.XLSX)',
						'file_type' => 'xlsx',
						'size' => '290 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Danh-muc-vi-tri-viec-lam-va-chi-tieu-2026.xlsx',
					),
				),
			),
			array(
				'id' => 103,
				'slug' => 'truong-cd-su-pham-tuyen-dung-giang-vien',
				'title' => 'Trường Cao Đẳng Sư Phạm Tuyển Dụng 15 Viên Chức Giảng Viên & Cán Bộ Quản Lý',
				'summary' => "Trường Cao đẳng Sư phạm thông báo xét tuyển 15 chỉ tiêu viên chức giảng dạy và quản lý đào tạo năm 2026.\n\nHồ sơ bao gồm Phiếu đăng ký dự tuyển viên chức, lý lịch khoa học, các văn bằng chứng chỉ liên quan.",
				'agency' => array(
					'name' => 'Trường Cao Đẳng Sư Phạm',
					'address' => 'TP. Buôn Ma Thuột, Tỉnh Đắk Lắk',
					'phone' => '0262.3855.999',
					'email' => 'tuyendung@cdsp.edu.vn',
					'website' => 'https://cdsp.edu.vn',
				),
				'recruitment_type' => 'public_employee',
				'type' => 'Viên Chức Giáo Dục',
				'deadline' => '25/11/2026',
				'total_positions' => 15,
				'positions_count' => 15,
				'quota' => '15 chỉ tiêu',
				'status' => 'Đang Nhận Hồ Sơ',
				'badge_color' => 'sky',
				'source_url' => 'https://cdsp.edu.vn/tuyen-dung-giang-vien-2026',
				'dates' => array(
					'announcement_date' => '2026-09-18',
					'application_deadline' => '2026-11-25',
				),
				'positions' => array(
					array(
						'name' => 'Giảng viên Sư phạm Toán - Lý - Hóa',
						'quantity' => 8,
						'job_description' => 'Giảng dạy các bộ môn khoa học tự nhiên, nghiên cứu khoa học và hướng dẫn thực tập.',
						'requirements' => 'Thạc sĩ trở lên chuyên ngành Sư phạm Toán, Lý, Hóa.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiểm tra điều kiện bằng cấp'),
							array('name' => 'Vòng 2: Thực giảng & Phỏng vấn chuyên môn'),
						),
					),
					array(
						'name' => 'Chuyên viên Quản lý Đào tạo & NCKH',
						'quantity' => 7,
						'job_description' => 'Quản lý kế hoạch giảng dạy, lập thời khóa biểu, quản lý dữ liệu sinh viên.',
						'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Quản lý giáo dục, CNTT hoặc Ngoại ngữ.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiểm tra hồ sơ'),
							array('name' => 'Vòng 2: Phỏng vấn nghiệp vụ quản lý giáo dục'),
						),
					),
				),
			),
			array(
				'id' => 104,
				'slug' => 'ubnd-xa-ea-tan-ky-thi-xet-tuyen',
				'title' => 'UBND Xã Ea Tân Kỳ Thi Xét Tuyển Công Chức Địa Chính - Xây Dựng & Nông Nghiệp',
				'summary' => "UBND Xã Ea Tân thông báo tuyển dụng 5 chỉ tiêu công chức chức danh Địa chính - Xây dựng - Đô thị - Môi trường và Nông nghiệp - Phát triển nông thôn.",
				'agency' => array(
					'name' => 'UBND Xã Ea Tân',
					'address' => 'Xã Ea Tân, Huyện Krông Năng, Tỉnh Đắk Lắk',
					'phone' => '0262.3666.777',
					'email' => 'ubndeatan@krongnang.daklak.gov.vn',
					'website' => 'https://krongnang.daklak.gov.vn',
				),
				'recruitment_type' => 'civil_servant',
				'type' => 'Công Chức Cấp Xã',
				'deadline' => '05/12/2026',
				'total_positions' => 5,
				'positions_count' => 5,
				'quota' => '5 chỉ tiêu',
				'status' => 'Mới Mở Đăng Ký',
				'badge_color' => 'purple',
				'source_url' => 'https://krongnang.daklak.gov.vn/tuyen-dung-ea-tan',
				'dates' => array(
					'announcement_date' => '2026-09-22',
					'application_deadline' => '2026-12-05',
				),
				'positions' => array(
					array(
						'name' => 'Công chức Địa chính - Xây dựng',
						'quantity' => 3,
						'job_description' => 'Tham mưu địa chính, quản lý đất đai và môi trường xã.',
						'requirements' => 'Đại học chuyên ngành Quản lý đất đai, Xây dựng.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung'),
							array('name' => 'Vòng 2: Phỏng vấn chuyên ngành'),
						),
					),
					array(
						'name' => 'Công chức Nông nghiệp & Nông thôn mới',
						'quantity' => 2,
						'job_description' => 'Theo dõi phát triển nông nghiệp, chương trình nông thôn mới.',
						'requirements' => 'Đại học Nông nghiệp, Phát triển nông thôn.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung'),
							array('name' => 'Vòng 2: Trắc nghiệm Luật Nông nghiệp'),
						),
					),
				),
			),
			array(
				'id' => 104,
				'slug' => 'ubnd-phuong-ben-nghe-tphcm-tuyen-dung-2026',
				'title' => 'UBND Phường Bến Nghé, Quận 1 Thông Báo Tuyển Dụng 8 Chỉ Tiêu Công Chức Phường 2026',
				'summary' => "UBND Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh thông báo thi tuyển chính thức 08 chỉ tiêu công chức ngạch Chuyên viên làm việc tại Trụ sở UBND Phường Bến Nghé.\n\nCơ cấu vị trí tuyển dụng gồm:\n- 03 Chuyên viên Địa chính - Xây dựng - Đô thị & Môi trường.\n- 03 Chuyên viên Văn phòng - Thống kê & Cải cách hành chính.\n- 02 Chuyên viên Tài chính - Kế toán.\n\nThời gian tiếp nhận hồ sơ từ ngày 20/09/2026 đến 30/10/2026.",
				'agency' => array(
					'name' => 'UBND Phường Bến Nghé, Quận 1, TP.HCM',
					'address' => 'Số 29 Lê Duẩn, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh',
					'phone' => '028.3822.4567',
					'email' => 'ubnd.bennghe@tphcm.gov.vn',
					'website' => 'https://quan1.hochiminhcity.gov.vn/phuong-ben-nghe',
				),
				'recruitment_type' => 'civil_servant',
				'type' => 'Công Chức Phường (TP.HCM)',
				'deadline' => '30/10/2026',
				'total_positions' => 8,
				'positions_count' => 8,
				'quota' => '8 chỉ tiêu',
				'status' => 'Mới Mở Đăng Ký',
				'badge_color' => 'emerald',
				'source_url' => 'https://quan1.hochiminhcity.gov.vn/tuyen-dung-ben-nghe-2026',
				'dates' => array(
					'announcement_date' => '2026-09-20',
					'application_deadline' => '2026-10-30',
				),
				'positions' => array(
					array(
						'name' => 'Chuyên viên Địa chính - Đô thị & Môi trường',
						'quantity' => 3,
						'job_description' => 'Tham mưu trật tự đô thị, quản lý đất đai và giấy phép xây dựng trên địa bàn Phường Bến Nghé.',
						'requirements' => 'Đại học trở lên chuyên ngành Quản lý đất đai, Xây dựng, QLĐT.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Trắc nghiệm Kiến thức chung 60 câu'),
							array('name' => 'Vòng 2: Phỏng vấn nghiệp vụ Quản lý đô thị'),
						),
					),
					array(
						'name' => 'Chuyên viên Văn phòng - Thống kê',
						'quantity' => 3,
						'job_description' => 'Quản lý Bộ phận Tiếp nhận và Trả kết quả (Một cửa), công tác cải cách hành chính.',
						'requirements' => 'Đại học chuyên ngành Luật, Quản lý nhà nước, Hành chính.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung'),
							array('name' => 'Vòng 2: Thi viết Nghị định 30/2020/NĐ-CP'),
						),
					),
				),
				'attachments' => array(
					array(
						'name' => 'Thong-bao-tuyen-dung-cong-chuc-tphcm-2026.pdf',
						'title' => 'Thông báo tuyển dụng công chức Phường Bến Nghé Quận 1 (.PDF gốc)',
						'file_type' => 'pdf',
						'size' => '2.4 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Thong-bao-tuyen-dung-cong-chuc-tphcm-2026.pdf',
					),
					array(
						'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
						'title' => 'Phiếu đăng ký dự tuyển Mẫu số 01 Bộ Nội vụ (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
					),
				),
			),
			array(
				'id' => 105,
				'slug' => 'ubnd-phuong-trang-tien-ha-noi-tuyen-dung-2026',
				'title' => 'UBND Phường Tràng Tiền, Quận Hoàn Kiếm Tuyển Dụng 6 Chỉ Tiêu Công Chức 2026',
				'summary' => "UBND Phường Tràng Tiền, Quận Hoàn Kiếm, TP. Hà Nội thông báo tuyển dụng 06 công chức Phường ngạch Chuyên viên.\n\nVị trí tuyển dụng bao gồm 02 Chuyên viên Tư pháp - Hộ tịch, 02 Chuyên viên Văn hóa - Xã hội và 02 Chuyên viên Kế toán.",
				'agency' => array(
					'name' => 'UBND Phường Tràng Tiền, Quận Hoàn Kiếm',
					'address' => 'Số 5 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội',
					'phone' => '024.3934.1234',
					'email' => 'ubndtrangtien@hoankiem.hanoi.gov.vn',
					'website' => 'https://hoankiem.hanoi.gov.vn',
				),
				'recruitment_type' => 'civil_servant',
				'type' => 'Công Chức Phường (Hà Nội)',
				'deadline' => '15/11/2026',
				'total_positions' => 6,
				'positions_count' => 6,
				'quota' => '6 chỉ tiêu',
				'status' => 'Đang Nhận Hồ Sơ',
				'badge_color' => 'sky',
				'source_url' => 'https://hoankiem.hanoi.gov.vn/tuyen-dung-trang-tien-2026',
				'dates' => array(
					'announcement_date' => '2026-09-21',
					'application_deadline' => '2026-11-15',
				),
				'positions' => array(
					array(
						'name' => 'Chuyên viên Tư pháp - Hộ tịch',
						'quantity' => 2,
						'job_description' => 'Đăng ký kết hôn, khai sinh, chứng thực bản sao và phổ biến giáo dục pháp luật.',
						'requirements' => 'Cử nhân Luật trở lên.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Trắc nghiệm Kiến thức chung'),
							array('name' => 'Vòng 2: Trắc nghiệm Luật Hộ tịch & Luật Chứng thực'),
						),
					),
				),
				'attachments' => array(
					array(
						'name' => 'Thong-bao-tuyen-dung-cong-chuc-ha-noi-2026.pdf',
						'title' => 'Thông báo tuyển dụng công chức Quận Hoàn Kiếm (.PDF gốc)',
						'file_type' => 'pdf',
						'size' => '2.6 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Thong-bao-tuyen-dung-cong-chuc-ha-noi-2026.pdf',
					),
				),
			),
		);
	}

	public static function get_legal_documents(): array {
		return array(
			array(
				'id' => 201,
				'slug' => 'luat-can-bo-cong-chuc-2026',
				'title' => 'Luật Cán Bộ, Công Chức (Hợp Nhất 2026)',
				'document_number' => 'Luật số 22/2019/QH14 & 22/2008/QH12',
				'document_type' => 'Luật',
				'issuing_agency' => 'Quốc Hội Khóa XIV',
				'issued_date' => '2019-11-25',
				'effective_date' => '2020-07-01',
				'signer' => 'Chủ tịch Quốc hội Nguyễn Thị Kim Ngân',
				'status_label' => 'Còn hiệu lực',
				'category' => 'Văn Bản Hợp Nhất',
				'format' => 'PDF',
				'size' => '2.4 MB',
				'downloads' => '15.420',
				'summary' => "Luật Cán bộ, công chức hợp nhất quy định về nghĩa vụ, quyền của cán bộ, công chức; điều kiện, ngạch bậc, thi tuyển, sử dụng và quản lý cán bộ, công chức trong cơ quan nhà nước, tổ chức chính trị - xã hội.\n\nCác nội dung trọng tâm bao gồm quy định về đánh giá xếp loại chất lượng, chế độ tập sự, thi nâng ngạch và xử lý kỷ luật cán bộ công chức theo tinh thần cải cách hành chính 2026.",
				'attachments' => array(
					array(
						'name' => 'Nghi-dinh-06-2023-ND-CP-Kiem-Dinh-Chat-Luong.pdf',
						'title' => 'Nghị định 06/2023/NĐ-CP Kiểm định chất lượng đầu vào công chức (.PDF)',
						'file_type' => 'pdf',
						'size' => '2.1 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Nghi-dinh-06-2023-ND-CP-Kiem-Dinh-Chat-Luong.pdf',
					),
					array(
						'name' => 'Nghi-dinh-30-2020-ND-CP-Cong-Tac-Van-Thu.pdf',
						'title' => 'Nghị định 30/2020/NĐ-CP về công tác văn thư & thể thức văn bản (.PDF)',
						'file_type' => 'pdf',
						'size' => '1.8 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Nghi-dinh-30-2020-ND-CP-Cong-Tac-Van-Thu.pdf',
					),
					array(
						'name' => 'Cam-nang-khoan-vung-trong-tam-KTC-2026-Full.pdf',
						'title' => 'Cẩm nang khoanh vùng trọng tâm Kiến thức chung 2026 (.PDF)',
						'file_type' => 'pdf',
						'size' => '3.5 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Cam-nang-khoan-vung-trong-tam-KTC-2026-Full.pdf',
					),
					array(
						'name' => 'Luat-Can-bo-Cong-chuc-Hop-Nhat-2026.pdf',
						'title' => 'Văn bản hợp nhất Luật Cán bộ, công chức 2026 chính thức (.PDF)',
						'file_type' => 'pdf',
						'size' => '2.5 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Luat-Can-bo-Cong-chuc-Hop-Nhat-2026.pdf',
					),
					array(
						'name' => 'Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf',
						'title' => 'Bộ đề trắc nghiệm Luật Cán bộ công chức 60 câu kèm đáp án (.PDF)',
						'file_type' => 'pdf',
						'size' => '1.5 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf',
					),
					array(
						'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
						'title' => 'Phiếu đăng ký dự tuyển Mẫu số 01 NĐ 138 Bộ Nội vụ (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
					),
				),
				'key_articles' => array(
					array(
						'article' => 'Điều 36. Điều kiện đăng ký dự tuyển công chức',
						'note' => 'Điều khoản vàng Vòng 1 — quy định 7 điều kiện chung (quốc tịch, tuổi từ 18, đơn dự tuyển, văn bằng chuyên môn).',
					),
					array(
						'article' => 'Điều 37. Phương thức tuyển dụng công chức',
						'note' => 'Thi tuyển là phương thức chủ đạo (2 vòng thi); Xét tuyển áp dụng cho vùng ĐKTKT đặc biệt khó khăn & sinh viên xuất sắc.',
					),
					array(
						'article' => 'Điều 79. Các hình thức kỷ luật đối với công chức',
						'note' => '6 hình thức kỷ luật từ Khiển trách, Cảnh cáo, Hạ bậc lương, Giáng chức, Cách chức đến Buộc thôi việc.',
					),
				),
			),
			array(
				'id' => 202,
				'slug' => 'nghi-dinh-138-2020-nd-cp',
				'title' => 'Nghị Định 138/2020/NĐ-CP Tuyển Dụng, Sử Dụng & Quản Lý Công Chức',
				'document_number' => 'Nghị định 138/2020/NĐ-CP',
				'document_type' => 'Nghị định',
				'issuing_agency' => 'Chính Phủ',
				'issued_date' => '2020-11-27',
				'effective_date' => '2020-12-01',
				'signer' => 'Thủ tướng Chính phủ Nguyễn Xuân Phúc',
				'status_label' => 'Còn hiệu lực',
				'category' => 'Nghị Định Chính Phủ',
				'format' => 'PDF',
				'size' => '3.8 MB',
				'downloads' => '38.500',
				'summary' => "Nghị định 138/2020/NĐ-CP là văn bản quan trọng nhất quy định chi tiết về tuyển dụng công chức:\n- Quy định cấu trúc 2 Vòng thi (Vòng 1 trắc nghiệm Kiến thức chung, Tiếng Anh, Tin học; Vòng 2 thi viết hoặc phỏng vấn nghiệp vụ chuyên ngành).\n- Quy định nội dung Phiếu đăng ký dự tuyển Mẫu số 01.\n- Quy định chế độ tập sự (12 tháng đối với ngạch Chuyên viên, 6 tháng đối với ngạch Cán sự).",
				'attachments' => array(
					array(
						'name' => 'Nghi-dinh-138-2020-ND-CP-Van-ban-goc.pdf',
						'title' => 'Nghị định 138/2020/NĐ-CP quy định tuyển dụng công chức (.PDF)',
						'file_type' => 'pdf',
						'size' => '3.8 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
					),
					array(
						'name' => 'Phieu-Mau-01-ND138.docx',
						'title' => 'Phiếu đăng ký dự tuyển công chức Mẫu 01 kèm NĐ 138 (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx',
					),
				),
				'key_articles' => array(
					array(
						'article' => 'Điều 8. Hình thức, nội dung và thời gian thi Vòng 1',
						'note' => 'Thi trắc nghiệm máy tính: Kiến thức chung (60 câu, 60 phút), Ngoại ngữ (30 câu, 30 phút), Tin học (30 câu, 30 phút).',
					),
					array(
						'article' => 'Điều 9. Vòng 2 thi môn nghiệp vụ chuyên ngành',
						'note' => 'Thi viết (180 phút) hoặc Phỏng vấn (30 phút) hoặc Thang điểm 100.',
					),
					array(
						'article' => 'Điều 20. Chế độ tập sự đối với người trúng tuyển',
						'note' => '12 tháng đối với Chuyên viên; 06 tháng đối với Cán sự; hưởng 85% bậc 1 lương ngạch.',
					),
				),
			),
			array(
				'id' => 203,
				'slug' => 'nghi-dinh-115-2020-nd-cp',
				'title' => 'Nghị Định 115/2020/NĐ-CP Tuyển Dụng, Sử Dụng & Quản Lý Viên Chức',
				'document_number' => 'Nghị định 115/2020/NĐ-CP',
				'document_type' => 'Nghị định',
				'issuing_agency' => 'Chính Phủ',
				'issued_date' => '2020-09-25',
				'effective_date' => '2020-09-29',
				'signer' => 'Thủ tướng Chính phủ Nguyễn Xuân Phúc',
				'status_label' => 'Còn hiệu lực',
				'category' => 'Nghị Định Chính Phủ',
				'format' => 'PDF',
				'size' => '3.1 MB',
				'downloads' => '29.100',
				'summary' => "Nghị định 115/2020/NĐ-CP quy định chi tiết về tuyển dụng, hợp đồng làm việc, nâng ngạch chức danh nghề nghiệp và quản lý viên chức trong các đơn vị sự nghiệp công lập (Giáo dục, Y tế, Văn hóa, NCKH).",
				'attachments' => array(
					array(
						'name' => 'Nghi-dinh-115-2020-ND-CP.pdf',
						'title' => 'Nghị định 115/2020/NĐ-CP về quản lý viên chức (.PDF)',
						'file_type' => 'pdf',
						'size' => '3.1 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
					),
				),
				'key_articles' => array(
					array(
						'article' => 'Điều 9. Thi tuyển viên chức 2 vòng',
						'note' => 'Vòng 1 thi trắc nghiệm Kiến thức chung & Ngoại ngữ; Vòng 2 thi thực hành hoặc phỏng vấn môn chuyên ngành.',
					),
				),
			),
			array(
				'id' => 204,
				'slug' => 'thong-tu-06-2020-tt-bnv',
				'title' => 'Thông Tư 06/2020/TT-BNV Quy Chế Tổ Chức Thi Tuyển & Xét Tuyển Công Chức',
				'document_number' => 'Thông tư 06/2020/TT-BNV',
				'document_type' => 'Thông tư',
				'issuing_agency' => 'Bộ Nội Vụ',
				'issued_date' => '2020-12-02',
				'effective_date' => '2021-01-20',
				'signer' => 'Bộ trưởng Bộ Nội vụ Lê Vĩnh Tân',
				'status_label' => 'Còn hiệu lực',
				'category' => 'Thông Tư Bộ Bàn',
				'format' => 'PDF',
				'size' => '1.9 MB',
				'downloads' => '22.400',
				'summary' => "Thông tư 06/2020/TT-BNV ban hành Quy chế tổ chức thi tuyển, xét tuyển công chức, viên chức, thi nâng ngạch công chức, thăng hạng chức danh nghề nghiệp viên chức và Nội quy kỳ thi.",
				'attachments' => array(
					array(
						'name' => 'Thong-tu-06-2020-TT-BNV.pdf',
						'title' => 'Thông tư 06/2020/TT-BNV về Quy chế thi tuyển công chức (.PDF)',
						'file_type' => 'pdf',
						'size' => '1.9 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
					),
				),
				'key_articles' => array(
					array(
						'article' => 'Điều 2. Quy trình chấm thi & Phúc khảo',
						'note' => 'Thời gian nhận đơn phúc khảo 15 ngày kể từ ngày công bố điểm thi; chấm phúc khảo bài thi viết Vòng 2.',
					),
				),
			),
		);
	}

	public static function get_exams(): array {
		$provinces = array(
			'Hà Nội', 'TP. Hồ Chí Minh', 'Quảng Ninh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
			'Đồng Nai', 'Bình Dương', 'Lâm Đồng', 'Đắk Lắk', 'Khánh Hòa', 'Gia Lai', 'Quảng Ngãi',
			'Thừa Thiên Huế', 'Thái Nguyên', 'Bắc Ninh', 'Vĩnh Phúc', 'Hải Dương', 'Nam Định'
		);

		$configs = array(
			array(
				'prefix' => 'Bộ Đề Thi Thử Kiến Thức Chung Vòng 1',
				'q' => 60,
				'duration' => 60,
				'pass' => '30/60',
				'category' => 'Bộ Nội Vụ',
				'attempts' => '85.400',
			),
			array(
				'prefix' => 'Đề Thi Trắc Nghiệm Tiếng Anh Công Chức Chuẩn B1/B2',
				'q' => 30,
				'duration' => 30,
				'pass' => '15/30',
				'category' => 'Ngoại Ngữ Công Vụ',
				'attempts' => '42.100',
			),
			array(
				'prefix' => 'Đề Thi Trắc Nghiệm Tin Học Văn Phòng Chuẩn CNTT',
				'q' => 30,
				'duration' => 30,
				'pass' => '15/30',
				'category' => 'Tin Học Công Vụ',
				'attempts' => '36.800',
			),
			array(
				'prefix' => 'Bộ Đề Ôn Thi Nâng Ngạch Chuyên Viên Chính',
				'q' => 60,
				'duration' => 60,
				'pass' => '35/60',
				'category' => 'Nâng Ngạch Cán Bộ',
				'attempts' => '58.900',
			),
		);

		$exams = array(
			array(
				'id'               => 299,
				'slug'             => 'de-thi-thu-ngoai-ngu-tieng-anh-tuyen-dung-cong-chuc-vong-1-de-01',
				'title'            => 'Đề Thi Thử Ngoại Ngữ Tiếng Anh Tuyển Dụng Công Chức Vòng 1 (Đề 01 - B1/B2)',
				'questions_count'  => 30,
				'duration_minutes' => 30,
				'pass_score'       => '15/30',
				'attempts_count'   => '48.200',
				'category'         => 'Ngoại Ngữ Công Vụ',
				'description'      => 'Đề thi trắc nghiệm Ngoại ngữ Tiếng Anh B1/B2 tuyển dụng công chức Vòng 1 chuẩn Bộ Nội vụ 30 câu (ngữ pháp, từ vựng hành chính, điền từ, đọc hiểu).',
			),
			array(
				'id'               => 298,
				'slug'             => 'de-thi-trac-nghiem-tin-hoc-van-phong-cong-chuc-vong-1-de-01',
				'title'            => 'Đề Thi Trắc Nghiệm Tin Học Văn Phòng Chuẩn CNTT Công Chức Vòng 1 (Đề 01)',
				'questions_count'  => 30,
				'duration_minutes' => 30,
				'pass_score'       => '15/30',
				'attempts_count'   => '39.500',
				'category'         => 'Tin Học Công Vụ',
				'description'      => 'Đề thi trắc nghiệm Tin học văn phòng 30 câu chuẩn kỹ năng CNTT cơ bản TCVN (Word, Excel, An toàn thông tin).',
			),
		);

		for ( $i = 1; $i <= 100; $i++ ) {
			$cfg  = $configs[ $i % count( $configs ) ];
			$prov = $provinces[ $i % count( $provinces ) ];

			$title = $cfg['prefix'] . ' - ' . $prov . ' (Mã đề #' . sprintf( '%03d', $i ) . ')';
			$slug  = sanitize_title( $title );

			$exams[] = array(
				'id'               => 300 + $i,
				'slug'             => $slug,
				'title'            => $title,
				'questions_count'  => $cfg['q'],
				'duration_minutes' => $cfg['duration'],
				'pass_score'       => $cfg['pass'],
				'attempts_count'   => number_format( 20000 + $i * 650, 0, ',', '.' ),
				'category'         => $cfg['category'],
			);
		}

		return $exams;
	}

	public static function get_knowledge_items(): array {
		return array(
			array(
				'id'          => 401,
				'slug'        => 'luat-can-bo-cong-chuc-2026-nhung-diem-moi-can-luu-y',
				'title'       => 'Luật Cán Bộ, Công Chức (Sửa Đổi 2026): 10 Điểm Trọng Tâm Khoanh Vùng Vòng 1',
				'summary'     => 'Phân tích chuyên sâu 10 nhóm câu hỏi trắc nghiệm tần suất cao về điều kiện dự tuyển, ngạch công chức, hình thức kỷ luật và nghĩa vụ công chức theo Nghị định 138/2020/NĐ-CP.',
				'read_time'   => '8 phút đọc',
				'topic'       => array( 'name' => 'Luật Cán bộ, công chức' ),
				'content'     => '<h3 class="text-base font-black text-amber-400 mb-3 border-b border-slate-800 pb-2">1. Tổng Quan Trọng Tâm Ôn Tập Luật Cán Bộ, Công Chức 2026</h3>
<p class="mb-4 text-slate-300 leading-relaxed">Luật Cán bộ, công chức là môn thi bắt buộc chiếm 40% số lượng câu hỏi trong phần thi Kiến thức chung Vòng 1 theo quy định của Bộ Nội vụ. Thí sinh đăng ký thi tuyển công chức ngạch Chuyên viên, Cán sự hoặc Chuyên viên chính cần nắm vững các nguyên tắc quản lý, nghĩa vụ, quyền hạn, điều kiện dự tuyển và quy trình xử lý kỷ luật.</p>

<h3 class="text-base font-black text-cyan-400 mb-3 border-b border-slate-800 pb-2">2. Khoanh Vùng Các Phân Đoạn Điểm Số Cao Vòng 1</h3>
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 mb-4 space-y-3">
  <p class="font-bold text-white text-xs">📌 10 Nhóm Câu Hỏi Tần Suất Cao (90% Xuất Hiện Trong Đề Thi Thật):</p>
  <ul class="list-disc pl-5 space-y-2 text-slate-300 text-xs">
    <li><strong>Điều 36 (Điều kiện đăng ký dự tuyển):</strong> 7 điều kiện chung (Có quốc tịch Việt Nam, đủ 18 tuổi trở lên, có đơn dự tuyển, lý lịch rõ ràng, văn bằng chuyên môn phù hợp, phẩm chất đạo đức, đủ sức khỏe). Phân biệt với 2 trường hợp KHÔNG được dự tuyển (Mất/hạn chế năng lực hành vi dân sự; đang bị truy cứu trách nhiệm hình sự hoặc chấp hành bản án).</li>
    <li><strong>Điều 37 (Phương thức tuyển dụng):</strong> Thi tuyển là phương thức chủ đạo (2 vòng thi). Xét tuyển chỉ áp dụng cho cam kết 05 năm ở vùng kinh tế đặc biệt khó khăn và sinh viên xuất sắc/nhà khoa học trẻ.</li>
    <li><strong>Điều 79 (Các hình thức kỷ luật công chức):</strong> 6 hình thức kỷ luật đối với công chức không giữ chức vụ lãnh đạo (Khiển trách, Cảnh cáo, Hạ bậc lương, Buộc thôi việc) và có giữ chức vụ lãnh đạo (+ Giáng chức, Cách chức).</li>
    <li><strong>Điều 80 (Thời hiệu xử lý kỷ luật):</strong> 02 năm đối với hành vi vi phạm ít nghiêm trọng (kỷ luật khiển trách); 05 năm đối với các hành vi vi phạm còn lại. Chú ý các trường hợp KHÔNG áp dụng thời hiệu (Vi phạm bảo vệ chính trị nội bộ, sử dụng văn bằng giả).</li>
    <li><strong>Điều 45 (Phân loại ngạch công chức):</strong> 5 ngạch công chức (Chuyên viên cao cấp, Chuyên viên chính, Chuyên viên, Cán sự, Nhân viên).</li>
  </ul>
</div>

<h3 class="text-base font-black text-emerald-400 mb-3 border-b border-slate-800 pb-2">3. Bảng So Sánh Thời Gian Tập Sự & Chế Độ Lương Ngạch theo NĐ 138/2020/NĐ-CP</h3>
<div class="overflow-x-auto mb-4">
  <table class="w-full text-left text-xs text-slate-300 border border-slate-800">
    <thead class="bg-slate-900 text-cyan-400 font-bold border-b border-slate-800">
      <tr>
        <th class="p-2.5 border-r border-slate-800">Ngạch công chức</th>
        <th class="p-2.5 border-r border-slate-800">Thời gian tập sự</th>
        <th class="p-2.5 border-r border-slate-800">Mức hưởng lương tập sự</th>
        <th class="p-2.5">Điều kiện miễn tập sự</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-800/60">
      <tr>
        <td class="p-2.5 font-bold text-white border-r border-slate-800">Chuyên viên & tương đương</td>
        <td class="p-2.5 text-amber-300 font-bold border-r border-slate-800">12 tháng</td>
        <td class="p-2.5 border-r border-slate-800">85% bậc 1 lương ngạch Chuyên viên</td>
        <td class="p-2.5">Đã có từ đủ 12 tháng làm công việc chuyên môn phù hợp có đóng BHXH bắt buộc</td>
      </tr>
      <tr>
        <td class="p-2.5 font-bold text-white border-r border-slate-800">Cán sự & tương đương</td>
        <td class="p-2.5 text-emerald-300 font-bold border-r border-slate-800">06 tháng</td>
        <td class="p-2.5 border-r border-slate-800">85% bậc 1 lương ngạch Cán sự</td>
        <td class="p-2.5">Đã có từ đủ 06 tháng làm công việc chuyên môn phù hợp có đóng BHXH bắt buộc</td>
      </tr>
    </tbody>
  </table>
</div>

<h3 class="text-base font-black text-amber-400 mb-3 border-b border-slate-800 pb-2">4. Phân Tích Kỹ Năng Giải Bài Tập Trắc Nghiệm Tình Huống Kỷ Luật</h3>
<p class="mb-3 text-slate-300">Trong đề thi trắc nghiệm 60 câu Kiến thức chung, các câu hỏi về thẩm quyền kỷ luật và thời hạn tạm đình chỉ công tác thường gây nhầm lẫn. Thí sinh ghi nhớ quy tắc:</p>
<ul class="list-disc pl-5 space-y-2 mb-4 text-slate-300 text-xs">
  <li>Thời hạn tạm đình chỉ công tác không quá <strong>15 ngày</strong> (trường hợp phức tạp có thể kéo dài thêm nhưng không quá <strong>15 ngày</strong> nữa).</li>
  <li>Trong thời gian tạm đình chỉ công tác, công chức vẫn được hưởng <strong>50% mức lương hiện hưởng</strong> cộng với phụ cấp chức vụ lãnh đạo, phụ cấp thâm niên (nếu có).</li>
</ul>

<h3 class="text-base font-black text-cyan-400 mb-3 border-b border-slate-800 pb-2">5. Hướng Dẫn Ôn Thi Đạt 50/60 Câu Trở Lên Vòng 1</h3>
<p class="mb-3 text-slate-300">Để vượt qua Vòng 1 với điểm số tuyệt đối, học viên cần kết hợp làm bộ đề trắc nghiệm đảo câu hỏi trên máy tính, luyện tập phản xạ chọn từ khóa chuẩn theo quy định của Luật Cán bộ, công chức và Nghị định 138/2020/NĐ-CP.</p>',
				'legal_document' => array(
					'title'          => 'Luật Cán bộ, công chức 2008 (Sửa đổi 2019)',
					'slug'           => 'luat-can-bo-cong-chuc-2008',
					'issuing_agency' => 'Quốc Hội',
				),
				'attachments' => array(
					array(
						'name'      => 'Cam-nang-khoan-vung-trong-tam-KTC-2026-Full.pdf',
						'title'     => 'Cẩm nang khoanh vùng trọng tâm trắc nghiệm 60 câu Kiến thức chung (.PDF)',
						'file_type' => 'pdf',
						'size'      => '4.2 MB',
						'url'       => get_template_directory_uri() . '/assets/downloads/Cam-nang-khoan-vung-trong-tam-KTC-2026-Full.pdf',
					),
				),
				'author' => array(
					'name'   => 'Ban Biên Tập Antigravity Công Vụ',
					'title'  => 'Chuyên gia Pháp luật Bộ Nội Vụ',
					'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
				),
				'created_at'  => '2026-09-18',
				'views_count' => 15400,
			),
			array(
				'id'          => 402,
				'slug'        => 'ky-thuat-soan-thao-van-ban-hanh-chinh-nghi-dinh-30',
				'title'       => 'Kỹ Thuật Soạn Thảo Văn Bản Hành Chính Chuẩn Nghị Định 30/2020/NĐ-CP',
				'summary'     => 'Hướng dẫn chi tiết thể thức, kỹ thuật trình bày văn bản hành chính: phông chữ, lề trang, số ký hiệu, sơ đồ cấu trúc Tờ trình, Kế hoạch và Công văn.',
				'read_time'   => '10 phút đọc',
				'topic'       => array( 'name' => 'Kỹ thuật soạn thảo văn bản' ),
				'content'     => '<h3 class="text-base font-black text-amber-400 mb-3 border-b border-slate-800 pb-2">1. Tổng Quan Quy Định Về Văn Thư Hành Chính Theo NĐ 30/2020/NĐ-CP</h3>
<p class="mb-4 text-slate-300 leading-relaxed">Nghị định số 30/2020/NĐ-CP ngày 05/03/2020 của Chính phủ về công tác văn thư là căn cứ pháp lý bắt buộc áp dụng đối với tất cả các cơ quan, tổ chức nhà nước, đơn vị sự nghiệp công lập và doanh nghiệp nhà nước trong việc soạn thảo, ban hành, quản lý và lưu trữ văn bản hành chính.</p>

<h3 class="text-base font-black text-cyan-400 mb-3 border-b border-slate-800 pb-2">2. Thể Thức & Kỹ Thuật Trình Bày Văn Bản Chuẩn Quốc Gia</h3>
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 mb-4 space-y-3 text-xs text-slate-300">
  <p class="font-bold text-white">📏 Quy chuẩn căn lề trang giấy A4 (210mm x 297mm):</p>
  <ul class="list-disc pl-5 space-y-1.5">
    <li>Lề trên (Top margin): Căn cách mép trên từ <strong>20 mm đến 25 mm</strong> (2 - 2.5 cm).</li>
    <li>Lề dưới (Bottom margin): Căn cách mép dưới từ <strong>20 mm đến 25 mm</strong> (2 - 2.5 cm).</li>
    <li>Lề trái (Left margin): Căn cách mép trái từ <strong>30 mm đến 35 mm</strong> (3 - 3.5 cm - phục vụ đóng đóng sổ/hồ sơ).</li>
    <li>Lề phải (Right margin): Căn cách mép phải từ <strong>15 mm đến 20 mm</strong> (1.5 - 2 cm).</li>
  </ul>
  <p class="font-bold text-white pt-2">🔤 Phông chữ & Cỡ chữ quy định chuẩn:</p>
  <ul class="list-disc pl-5 space-y-1.5">
    <li>Phông chữ tiếng Việt: <strong>Times New Roman</strong> bộ mã ký tự Unicode TCVN 6909:2001.</li>
    <li>Quốc hiệu "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM": Chữ in hoa, đứng, đậm, cỡ chữ <strong>12 đến 13</strong>.</li>
    <li>Tiêu ngữ "Độc lập - Tự do - Hạnh phúc": Chữ in thường, đứng, đậm, cỡ chữ <strong>13 đến 14</strong>, có đường kẻ ngang bên dưới.</li>
    <li>Tên loại văn bản (TỜ TRÌNH, KẾ HOẠCH, CÔNG VĂN...): Chữ in hoa, đứng, đậm, cỡ chữ <strong>13 đến 14</strong>.</li>
  </ul>
</div>

<h3 class="text-base font-black text-emerald-400 mb-3 border-b border-slate-800 pb-2">3. Sơ Đồ Cấu Trúc 9 Thành Phần Thể Thức Bắt Buộc</h3>
<ol class="list-decimal pl-5 space-y-2 mb-4 text-xs text-slate-300">
  <li><strong>Quốc hiệu và Tiêu ngữ:</strong> Đặt tại góc trên bên phải trang thứ nhất.</li>
  <li><strong>Tên cơ quan, tổ chức ban hành văn bản:</strong> Đặt tại góc trên bên trái trang thứ nhất.</li>
  <li><strong>Số, ký hiệu của văn bản:</strong> Đặt canh giữa dưới tên cơ quan ban hành (vd: <code>Số: 138/2020/NĐ-CP</code>).</li>
  <li><strong>Địa danh và thời gian ban hành văn bản:</strong> Đặt dưới Quốc hiệu, Tiêu ngữ (vd: <i>Hà Nội, ngày 27 tháng 11 năm 2020</i>).</li>
  <li><strong>Tên loại và trích yếu nội dung văn bản:</strong> Nêu rõ loại văn bản và tóm tắt chủ đề xử lý.</li>
  <li><strong>Nội dung văn bản:</strong> Trình bày rõ ràng theo Bố cục Phần, Chương, Mục, Điều, Khoản, Điểm.</li>
  <li><strong>Chức vụ, họ tên và chữ ký của người có thẩm quyền:</strong> Đóng dấu cơ quan hoặc ký số pháp lý.</li>
  <li><strong>Dấu, chữ ký số của cơ quan, tổ chức:</strong> Đóng 1/3 chữ ký về phía bên trái.</li>
  <li><strong>Nơi nhận:</strong> Nêu rõ danh sách cơ quan báo cáo, thực hiện và lưu văn thư.</li>
</ol>',
				'legal_document' => array(
					'title'          => 'Nghị định 30/2020/NĐ-CP Công tác văn thư',
					'slug'           => 'nghi-dinh-30-2020-nd-cp',
					'issuing_agency' => 'Chính Phủ',
				),
				'attachments' => array(
					array(
						'name'      => 'Nghi-dinh-30-2020-ND-CP-Cong-Tac-Van-Thu.pdf',
						'title'     => 'Nghị định 30/2020/NĐ-CP về công tác văn thư hành chính (.PDF)',
						'file_type' => 'pdf',
						'size'      => '3.5 MB',
						'url'       => get_template_directory_uri() . '/assets/downloads/Nghi-dinh-30-2020-ND-CP-Cong-Tac-Van-Thu.pdf',
					),
				),
				'author' => array(
					'name'   => 'ThS. Nguyễn Thị Thu Hà',
					'title'  => 'Giảng viên Văn thư Hành chính',
					'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=150&q=80',
				),
				'created_at'  => '2026-09-15',
				'views_count' => 12800,
			),
			array(
				'id'          => 403,
				'slug'        => 'kiem-dinh-chat-luong-dau-vao-cong-chuc-nghi-dinh-06',
				'title'       => 'Kiểm Định Chất Lượng Đầu Vào Công Chức Theo Nghị Định 06/2023/NĐ-CP',
				'summary'     => 'Quy trình đăng ký, cấu trúc bài thi kiểm định chất lượng công chức quốc gia do Bộ Nội vụ tổ chức tập trung trên máy tính.',
				'read_time'   => '6 phút đọc',
				'topic'       => array( 'name' => 'Quản lý nhà nước' ),
				'content'     => '<h3 class="text-base font-black text-amber-400 mb-3 border-b border-slate-800 pb-2">1. Tổng Quan Quy Định Kiểm Định Chất Lượng Theo NĐ 06/2023/NĐ-CP</h3>
<p class="mb-4 text-slate-300 leading-relaxed">Nghị định số 06/2023/NĐ-CP ngày 21/02/2023 của Chính phủ quy định về kiểm định chất lượng đầu vào công chức là bước đột phá trong cải cách thủ tục hành chính và tuyển dụng công chức. Kỳ thi do Bộ Nội vụ trực tiếp chủ trì tổ chức tập trung trên phạm vi toàn quốc nhằm thống nhất tiêu chuẩn đánh giá năng lực công chức đầu vào.</p>

<h3 class="text-base font-black text-cyan-400 mb-3 border-b border-slate-800 pb-2">2. Cấu Trúc Đề Thi Trắc Nghiệm 100 Câu Hỏi Quốc Gia</h3>
<div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 mb-4 space-y-3 text-xs text-slate-300">
  <p class="font-bold text-white text-sm">📝 Chi tiết ma trận 100 câu hỏi thi máy tính (120 phút):</p>
  <ul class="list-disc pl-5 space-y-2">
    <li><strong>Phần 1: Hiểu biết chung về hệ thống chính trị & Quản lý nhà nước (40 câu):</strong> Chủ trương, đường lối của Đảng; Hiến pháp 2013; Luật Tổ chức Chính phủ 2015 (sửa đổi 2019); Luật Tổ chức chính quyền địa phương 2015 (sửa đổi 2019); Luật Cán bộ, công chức 2008 (sửa đổi 2019).</li>
    <li><strong>Phần 2: Năng lực tư duy logic & Phân tích định lượng (30 câu):</strong> Giải quyết vấn đề, phân tích biểu đồ, dãy số, suy luận logic hành chính và xử lý số liệu thống kê công vụ.</li>
    <li><strong>Phần 3: Kỹ năng công nghệ thông tin & Tiếng Anh hành chính (30 câu):</strong> Kỹ năng ứng dụng công nghệ số theo Chuẩn CNTT cơ bản và từ vựng tiếng Anh chuyên ngành công vụ.</li>
  </ul>
</div>

<h3 class="text-base font-black text-emerald-400 mb-3 border-b border-slate-800 pb-2">3. Điều Kiện Đạt Kiểm Định & Thời Hạn Giá Trị Chứng Nhận</h3>
<div class="overflow-x-auto mb-4">
  <table class="w-full text-left text-xs text-slate-300 border border-slate-800">
    <thead class="bg-slate-900 text-cyan-400 font-bold border-b border-slate-800">
      <tr>
        <th class="p-2.5 border-r border-slate-800">Tiêu chí đánh giá</th>
        <th class="p-2.5 border-r border-slate-800">Quy định NĐ 06/2023/NĐ-CP</th>
        <th class="p-2.5">Quyền lợi dành cho thí sinh</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-800/60">
      <tr>
        <td class="p-2.5 font-bold text-white border-r border-slate-800">Điểm số ĐẠT kiểm định</td>
        <td class="p-2.5 text-amber-300 font-bold border-r border-slate-800">Đạt từ 50/100 câu đúng trở lên (50%)</td>
        <td class="p-2.5">Được Bộ Nội vụ cấp Giấy chứng nhận kết quả kiểm định điện tử</td>
      </tr>
      <tr>
        <td class="p-2.5 font-bold text-white border-r border-slate-800">Thời hạn hiệu lực</td>
        <td class="p-2.5 text-emerald-300 font-bold border-r border-slate-800">24 tháng trên toàn quốc</td>
        <td class="p-2.5">Miễn thi Vòng 1 khi tham gia thi tuyển tại tất cả 34 Tỉnh/Thành & Bộ Ngành</td>
      </tr>
      <tr>
        <td class="p-2.5 font-bold text-white border-r border-slate-800">Quyền thi lại khi chưa ĐẠT</td>
        <td class="p-2.5 border-r border-slate-800">Được đăng ký ở các kỳ kiểm định tiếp theo</td>
        <td class="p-2.5">Bộ Nội vụ tổ chức định kỳ 2 lần/năm (tháng 7 và tháng 11)</td>
      </tr>
    </tbody>
  </table>
</div>

<h3 class="text-base font-black text-amber-400 mb-3 border-b border-slate-800 pb-2">4. Lộ Trình Ôn Luyện Bứt Phá Đạt 80+ Điểm Kiểm Định</h3>
<p class="mb-3 text-slate-300 leading-relaxed">Để sẵn sàng cho kỳ thi kiểm định quốc gia, học viên cần nắm vững các văn bản quy phạm pháp luật gốc, kết hợp luyện đề trắc nghiệm ngẫu nhiên 100 câu trên hệ thống thi máy tính tự động. Đặt mục tiêu làm đúng trên 80 câu để xếp hạng điểm cao ưu tiên khi nộp hồ sơ Vòng 2 tại các Sở Nội vụ.</p>

<h3 class="text-base font-black text-cyan-400 mb-3 border-b border-slate-800 pb-2">5. Ngân Hàng Câu Hỏi Mẫu Tự Luyện Tập Trực Tiếp</h3>
<div class="space-y-3 text-xs text-slate-200">
  <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
    <p class="font-bold text-amber-300 mb-1">Câu 1: Theo NĐ 06/2023/NĐ-CP, kết quả kiểm định chất lượng đầu vào công chức có giá trị sử dụng trong thời gian bao lâu?</p>
    <p class="text-slate-400">A. 12 tháng &nbsp;&nbsp; <strong class="text-emerald-400">B. 24 tháng (Đáp án đúng)</strong> &nbsp;&nbsp; C. 36 tháng &nbsp;&nbsp; D. Vô thời hạn</p>
  </div>
  <div class="p-3 bg-slate-900 rounded-xl border border-slate-800">
    <p class="font-bold text-amber-300 mb-1">Câu 2: Thí sinh thi đạt kiểm định chất lượng đầu vào công chức sẽ được hưởng quyền lợi gì khi thi tuyển công chức?</p>
    <p class="text-slate-400">A. Miễn thi Vòng 2 &nbsp;&nbsp; <strong class="text-emerald-400">B. Miễn thi Vòng 1 trên toàn quốc (Đáp án đúng)</strong> &nbsp;&nbsp; C. Cộng 10 điểm vào Vòng 2 &nbsp;&nbsp; D. Tuyển thẳng không qua thi tuyển</p>
  </div>
</div>',
				'legal_document' => array(
					'title'          => 'Nghị định 06/2023/NĐ-CP Kiểm định chất lượng công chức',
					'slug'           => 'nghi-dinh-06-2023-nd-cp',
					'issuing_agency' => 'Chính Phủ',
				),
				'attachments' => array(
					array(
						'name'      => 'Nghi-dinh-06-2023-ND-CP-Kiem-Dinh-Chat-Luong.pdf',
						'title'     => 'Nghị định 06/2023/NĐ-CP về kiểm định chất lượng đầu vào (.PDF)',
						'file_type' => 'pdf',
						'size'      => '2.1 MB',
						'url'       => get_template_directory_uri() . '/assets/downloads/Nghi-dinh-06-2023-ND-CP-Kiem-Dinh-Chat-Luong.pdf',
					),
				),
				'author' => array(
					'name'   => 'Ban Biên Tập Antigravity Công Vụ',
					'title'  => 'Chuyên gia Pháp luật Bộ Nội Vụ',
					'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
				),
				'created_at'  => '2026-09-10',
				'views_count' => 9600,
			),
			array(
				'id'          => 404,
				'slug'        => 'meo-lam-bai-thi-trac-nghiem-tieng-anh-b1-b2-cong-chuc',
				'title'       => 'Mẹo Làm Bài Thi Trắc Nghiệm Tiếng Anh Công Chức Chuẩn B1/B2 Vòng 1',
				'summary'     => 'Tổng hợp 300 từ vựng tiếng Anh chuyên ngành công vụ và chiến thuật loại trừ phương án sai trong 30 phút làm bài.',
				'read_time'   => '12 phút đọc',
				'topic'       => array( 'name' => 'Anh văn công vụ B1/B2' ),
				'content'     => '<h3 class="text-base font-black text-amber-400 mb-3 border-b border-slate-800 pb-2">1. Tổng Quan Môn Thi Tiếng Anh B1/B2 Vòng 1 Thi Công Chức</h3>
<p class="mb-4 text-slate-300 leading-relaxed">Môn thi Ngoại ngữ (Tiếng Anh) Vòng 1 thi tuyển công chức gồm 30 câu hỏi trắc nghiệm trong thời gian 30 phút. Thí sinh phải trả lời đúng từ 15/30 câu trở lên (50%) để qua Vòng 1. Bài viết chia sẻ chiến thuật loại trừ và 300 từ vựng cốt lõi chuyên ngành công vụ.</p>

<h3 class="text-base font-black text-cyan-400 mb-3 border-b border-slate-800 pb-2">2. Ma Trận 30 Câu Hỏi & Kỹ Thuật Phân Bổ Thời Gian</h3>
<div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 mb-4 space-y-3 text-xs text-slate-300">
  <ul class="list-disc pl-5 space-y-2">
    <li><strong>Phần 1: Ngữ pháp & Từ vựng (15 câu - 10 phút):</strong> Kiểm tra các thì động từ, câu điều kiện, mệnh đề quan hệ và collocations thuật ngữ hành chính.</li>
    <li><strong>Phần 2: Điền từ vào đoạn văn Cloze Test (5 câu - 8 phút):</strong> Nắm bắt mạch văn bản thông báo công vụ hoặc email làm việc.</li>
    <li><strong>Phần 3: Đọc hiểu đoạn văn Reading Comprehension (10 câu - 12 phút):</strong> Đọc câu hỏi trước, tìm từ khóa (Keywords) trong bài đọc để chọn đáp án nhanh chóng.</li>
  </ul>
</div>',
				'attachments' => array(
					array(
						'name'      => 'Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf',
						'title'     => 'Bộ đề trắc nghiệm chuẩn có đáp án chi tiết (.PDF)',
						'file_type' => 'pdf',
						'size'      => '1.8 MB',
						'url'       => get_template_directory_uri() . '/assets/downloads/Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf',
					),
				),
				'author' => array(
					'name'   => 'ThS. Đặng Minh Tuấn',
					'title'  => 'Chuyên gia Ngoại ngữ Công vụ',
					'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80',
				),
				'created_at'  => '2026-09-05',
				'views_count' => 18200,
			),
		);
	}

	public static function get_topics(): array {
		return array(
			array(
				'id'           => 501,
				'slug'         => 'luat-can-bo-cong-chuc-hop-nhat',
				'name'         => 'Sơ Đồ Tư Duy Luật Cán Bộ, Công Chức Hợp Nhất',
				'description'  => 'Hệ thống hóa toàn bộ 10 chương 87 điều Luật Cán bộ, công chức dưới dạng nhánh sơ đồ tư duy trực quan, dễ thuộc.',
				'exam_subject' => array( 'name' => 'Kiến Thức Chung Vòng 1' ),
				'children'     => array(
					array( 'id' => 5011, 'slug' => 'dieu-kien-du-tuyen-cong-chuc', 'name' => 'Điều kiện dự tuyển & Phương thức tuyển dụng' ),
					array( 'id' => 5012, 'slug' => 'nghia-vu-quyen-han-cong-chuc', 'name' => 'Nghĩa vụ, quyền hạn & Những việc không được làm' ),
					array( 'id' => 5013, 'slug' => 'cac-hinh-thuc-ky-luat-cong-chuc', 'name' => '6 Hình thức kỷ luật & Thời hiệu xử lý' ),
				),
			),
			array(
				'id'           => 502,
				'slug'         => 'he-thong-co-quan-hanh-chinh-nha-nuoc',
				'name'         => 'Khung Năng Lực & Tổ Chức Bộ Máy Hành Chính Nhà Nước',
				'description'  => 'Phân cấp quản lý từ Chính phủ, Bộ ngành đến UBND 34 Tỉnh thành và UBND Cấp xã.',
				'exam_subject' => array( 'name' => 'Quản Lý Nhà Nước' ),
				'children'     => array(
					array( 'id' => 5021, 'slug' => 'co-cau-to-chuc-chinh-phu', 'name' => 'Cơ cấu tổ chức Chính phủ & Các Bộ chuyên ngành' ),
					array( 'id' => 5022, 'slug' => 'ubnd-34-tinh-thanh-hop-nhat', 'name' => 'Chức năng HĐND & UBND 34 Tỉnh Thành Mới' ),
				),
			),
			array(
				'id'           => 503,
				'slug'         => 'van-thu-luu-tru-ky-thuat-soan-thao',
				'name'         => 'Quy Trình Văn Thư Lưu Trữ & Thể Thức Văn Bản',
				'description'  => 'Sơ đồ tư duy 12 bước ban hành văn bản hành chính chuẩn Nghị định 30/2020/NĐ-CP.',
				'exam_subject' => array( 'name' => 'Văn Thư Lưu Trữ' ),
				'children'     => array(
					array( 'id' => 5031, 'slug' => 'the-thuc-phong-chu-nghi-dinh-30', 'name' => 'Thể thức, phông chữ & Khổ giấy chuẩn' ),
					array( 'id' => 5032, 'slug' => 'quy-trinh-lap-ho-so-cong-vieu', 'name' => 'Quy trình lập hồ sơ công việc & Nộp lưu trữ' ),
				),
			),
			array(
				'id'           => 504,
				'slug'         => 'tieng-anh-cong-vu-b1-b2-vong-1',
				'name'         => 'Cấu Trúc Đề Thi & Từ Vựng Tiếng Anh B1/B2 Vòng 1',
				'description'  => 'Cơ cấu 30 câu trắc nghiệm Ngoại ngữ: Ngữ pháp trọng tâm, Điền từ và Đọc hiểu thông báo công vụ.',
				'exam_subject' => array( 'name' => 'Ngoại Ngữ B1/B2' ),
				'children'     => array(
					array( 'id' => 5041, 'slug' => 'ngu-phap-trong-tam-tieng-anh', 'name' => '12 Thì ngữ pháp trọng tâm thi trắc nghiệm' ),
					array( 'id' => 5042, 'slug' => 'tu-vung-chuyen-nganh-cong-vu', 'name' => '300 Từ vựng tiếng Anh hành chính công' ),
				),
			),
		);
	}
}

