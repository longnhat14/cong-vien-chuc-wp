import os
import re

fixtures_path = r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\inc\services\class-cvc-subpage-fixtures.php"

# Read file content
with open(fixtures_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Check existing items count
print("Current fixtures file size:", len(content))

# Generate extra commune recruitment items to inject into get_recruitments()
extra_recruitments_code = """
			array(
				'id' => 104,
				'slug' => 'ubnd-phuong-ben-nghe-tphcm-tuyen-dung-2026',
				'title' => 'UBND Phường Bến Nghé, Quận 1 Thông Báo Tuyển Dụng 8 Chỉ Tiêu Công Chức Phường 2026',
				'summary' => "UBND Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh thông báo thi tuyển chính thức 08 chỉ tiêu công chức ngạch Chuyên viên làm việc tại Trụ sở UBND Phường Bến Nghé.\\n\\nCơ cấu vị trí tuyển dụng gồm:\\n- 03 Chuyên viên Địa chính - Xây dựng - Đô thị & Môi trường.\\n- 03 Chuyên viên Văn phòng - Thống kê & Cải cách hành chính.\\n- 02 Chuyên viên Tài chính - Kế toán.\\n\\nThời gian tiếp nhận hồ sơ từ ngày 20/09/2026 đến 30/10/2026.",
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
				'summary' => "UBND Phường Tràng Tiền, Quận Hoàn Kiếm, TP. Hà Nội thông báo tuyển dụng 06 công chức Phường ngạch Chuyên viên.\\n\\nVị trí tuyển dụng bao gồm 02 Chuyên viên Tư pháp - Hộ tịch, 02 Chuyên viên Văn hóa - Xã hội và 02 Chuyên viên Kế toán.",
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
"""

# Insert extra items into get_recruitments() in fixtures file
target_marker = "public static function get_legal_documents(): array {"
if target_marker in content:
    # Insert right before get_legal_documents()
    parts = content.split(target_marker)
    # Put extra_recruitments_code inside the end of get_recruitments array
    updated_content = parts[0].rstrip()
    if updated_content.endswith(");"):
        updated_content = updated_content[:-2] + extra_recruitments_code + "\t\t);\n\t}\n\n\t" + target_marker + parts[1]
        with open(fixtures_path, 'w', encoding='utf-8') as f:
            f.write(updated_content)
        print("Successfully updated CVC_Subpage_Fixtures with extra commune recruitments!")
    else:
        print("Could not find exact insertion marker in fixtures file.")
else:
    print("Target marker not found.")
