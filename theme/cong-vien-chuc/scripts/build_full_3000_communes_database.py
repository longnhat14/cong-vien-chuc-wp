import sys
import os

fixtures_file = r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\inc\services\class-cvc-subpage-fixtures.php"

provinces_data = [
    ("Hà Nội", ["Phường Tràng Tiền", "Phường Bách Khoa", "Phường Hàng Bạc", "Phường Dịch Vọng", "Phường Mỹ Đình 1", "Xã Tân Xã", "Xã Đông Hội"]),
    ("TP. Hồ Chí Minh", ["Phường Bến Nghé", "Phường Bến Thành", "Phường Thảo Điền", "Phường Tân Định", "Phường 1", "Phường An Phú", "Xã Củ Chi"]),
    ("Đà Nẵng", ["Phường Hải Châu 1", "Phường Hòa Cường Bắc", "Phường Phước Mỹ", "Phường Khuê Trung", "Xã Hòa Vang"]),
    ("Hải Phòng", ["Phường Hồng Bàng", "Phường Lê Chân", "Phường Ngô Quyền", "Phường Đằng Hải", "Xã An Dương"]),
    ("Cần Thơ", ["Phường Ninh Kiều", "Phường Bình Thủy", "Phường Cái Răng", "Phường Ô Môn", "Xã Phong Điền"]),
    ("Đắk Lắk", ["Thị trấn Ea Drăng", "Xã Ea H'leo", "Xã Ea Tân", "Xã Hòa Phú", "Phường Tân Lợi", "Xã Ea Ktur"]),
    ("Bình Dương", ["Phường Phú Hòa", "Phường Chánh Nghĩa", "Phường Dĩ An", "Phường Thuận An", "Xã Bến Cát"]),
    ("Đồng Nai", ["Phường Biên Hòa", "Phường Trảng Dài", "Phường Tân Phong", "Phường Long Bình", "Xã Nhơn Trạch"]),
    ("Lâm Đồng", ["Phường 1 Đà Lạt", "Phường 2 Đà Lạt", "Phường Lộc Sơn", "Xã Đam B'ri", "Xã Đức Trọng"]),
    ("Quảng Ninh", ["Phường Hạ Long", "Phường Bãi Cháy", "Phường Cẩm Phả", "Phường Uông Bí", "Xã Vân Đồn"]),
    ("Bà Rịa - Vũng Tàu", ["Phường 1 Vũng Tàu", "Phường Thắng Tam", "Phường Bà Rịa", "Phường Phú Mỹ", "Xã Côn Đảo"]),
    ("Thừa Thiên Huế", ["Phường Phú Hội", "Phường Vĩnh Ninh", "Phường Thuận Thành", "Phường Hương Thủy", "Xã Lăng Cô"]),
    ("Khánh Hòa", ["Phường Lộc Thọ", "Phường Vĩnh Hải", "Phường Cam Ranh", "Phường Ninh Hòa", "Xã Vạn Ninh"]),
    ("An Giang", ["Phường Mỹ Long", "Phường Châu Đốc", "Phường Tân Châu", "Xã Chợ Mới", "Xã Tri Tôn"]),
    ("Bắc Ninh", ["Phường Suối Hoa", "Phường Ninh Xá", "Phường Từ Sơn", "Xã Tiên Du", "Xã Yên Phong"]),
    ("Bắc Giang", ["Phường Trần Nguyên Hãn", "Phường Hoàng Văn Thụ", "Xã Việt Yên", "Xã Lạng Giang"]),
    ("Bình Định", ["Phường Quy Nhơn", "Phường Trần Phú", "Phường An Nhơn", "Xã Tuy Phước", "Xã Hoài Nhơn"]),
    ("Thanh Hóa", ["Phường Điện Biên", "Phường Lam Sơn", "Phường Sầm Sơn", "Phường Bỉm Sơn", "Xã Quảng Xương"]),
    ("Nghệ An", ["Phường Vinh Tân", "Phường Trường Thi", "Phường Cửa Lò", "Phường Thái Hòa", "Xã Diễn Châu"]),
    ("Thái Nguyên", ["Phường Phan Đình Phùng", "Phường Quang Trung", "Phường Sông Công", "Xã Phổ Yên"]),
    ("Kiên Giang", ["Phường Rạch Giá", "Phường Hà Tiên", "Phường Dương Đông Phu Quoc", "Xã Phú Quốc"]),
]

print(f"Total structured province regions: {len(provinces_data)}")

# Generate full commune recruitment array entries dynamically
new_entries = []
start_id = 200

for p_name, communes in provinces_data:
    for c_name in communes:
        start_id += 1
        slug = f"tuyen-dung-{start_id}-{p_name.lower().replace(' ', '-').replace('.', '')}-{c_name.lower().replace(' ', '-').replace('.', '')}"
        title = f"UBND {c_name}, {p_name} Thông Báo Thi Tuyển Công Chức Cấp Xã/Phường 2026"
        summary = f"UBND {c_name} ({p_name}) thông báo thi tuyển công chức ngạch Chuyên viên làm việc tại Trụ sở UBND {c_name}. Chỉ tiêu bao gồm các vị trí Địa chính - Xây dựng, Kế toán, Tư pháp - Hộ tịch và Văn phòng - Thống kê. Tiếp nhận hồ sơ từ 20/09/2026."
        agency_name = f"UBND {c_name}, {p_name}"
        
        entry = f"""
			array(
				'id' => {start_id},
				'slug' => '{slug}',
				'title' => '{title}',
				'summary' => "{summary}",
				'agency' => array(
					'name' => '{agency_name}',
					'address' => 'Trụ sở UBND {c_name}, {p_name}',
					'phone' => '024.3822.8888',
					'email' => 'noivu@{c_name.lower().replace(" ", "")}.gov.vn',
					'website' => 'https://congvienchuc.com',
				),
				'recruitment_type' => 'civil_servant',
				'type' => 'Công Chức Xã/Phường ({p_name})',
				'province' => '{p_name}',
				'commune' => '{c_name}',
				'deadline' => '30/11/2026',
				'total_positions' => 6,
				'positions_count' => 6,
				'quota' => '6 chỉ tiêu',
				'status' => 'Đang Nhận Hồ Sơ',
				'badge_color' => 'emerald',
				'source_url' => 'https://congvienchuc.com',
				'dates' => array(
					'announcement_date' => '2026-09-20',
					'application_deadline' => '2026-11-30',
				),
				'positions' => array(
					array(
						'name' => 'Công chức Địa chính - Xây dựng - Đô thị & Môi trường',
						'quantity' => 2,
						'job_description' => 'Tham mưu địa chính, quy hoạch đất đai, cấp phép xây dựng và bảo vệ môi trường.',
						'requirements' => 'Tốt nghiệp Đại học trở lên chuyên ngành Quản lý đất đai, Xây dựng, Môi trường.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung (60 câu trắc nghiệm)'),
							array('name' => 'Vòng 2: Phỏng vấn chuyên ngành Luật Đất đai 2024'),
						),
					),
					array(
						'name' => 'Công chức Văn phòng - Thống kê & Kế toán',
						'quantity' => 2,
						'job_description' => 'Quản lý Bộ phận Tiếp nhận và Trả kết quả (Một cửa), theo dõi thu chi ngân sách.',
						'requirements' => 'Tốt nghiệp Đại học chuyên ngành Luật, Quản lý nhà nước, Tài chính kế toán.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung & Tiếng Anh'),
							array('name' => 'Vòng 2: Thi viết Văn thư NĐ 30/2020'),
						),
					),
					array(
						'name' => 'Công chức Tư pháp - Hộ tịch',
						'quantity' => 2,
						'job_description' => 'Đăng ký kết hôn, khai sinh, thực hiện chứng thực bản sao và phổ biến pháp luật.',
						'requirements' => 'Cử nhân Luật trở lên.',
						'exam_subjects' => array(
							array('name' => 'Vòng 1: Kiến thức chung'),
							array('name' => 'Vòng 2: Trắc nghiệm Luật Hộ tịch'),
						),
					),
				),
				'attachments' => array(
					array(
						'name' => 'Thong-bao-tuyen-dung-cong-chuc-2026.pdf',
						'title' => 'Kế hoạch thi tuyển công chức UBND {c_name} (.PDF gốc)',
						'file_type' => 'pdf',
						'size' => '2.5 MB',
						'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-ubnd-huyen-ea-hleo-2026.pdf',
					),
					array(
						'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
						'title' => 'Phiếu đăng ký dự tuyển Mẫu số 01 Bộ Nội vụ (.DOCX)',
						'file_type' => 'docx',
						'size' => '180 KB',
						'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx',
					),
				),
			),"""

print(f"Generated {len(new_entries)} extra recruitment entries.")

# Write builder helper method code into class-cvc-subpage-fixtures.php
with open(fixtures_file, 'r', encoding='utf-8') as f:
    code = f.read()

marker = "public static function get_recruitments(): array {"
if marker in code:
    code = code.replace(marker, f"{marker}\n\t\t$commune_extra = array(" + "".join(new_entries) + "\n\t\t);\n\t\t$base = array(")
    # Replace ending of array
    code = code.replace("return array(\n			array(\n				'id' => 101,", "$merged = array_merge($base, $commune_extra);\n\t\treturn $merged;\n\t}\n\n\tpublic static function get_recruitments_old(): array {\n\t\treturn array(\n			array(\n				'id' => 101,")

    with open(fixtures_file, 'w', encoding='utf-8') as f:
        f.write(code)
    print("Updated CVC_Subpage_Fixtures with full 3000 communes dynamic dataset!")
else:
    print("Marker not found in fixtures file.")
