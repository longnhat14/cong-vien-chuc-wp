import sys
import os

rec_file = r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-recruitments.php"

exact_34_code = """$provinces_merged_34 = array(
	'Tất cả 34 Tỉnh Thành Mới (Theo NQ 202/2025/QH15)',
	'Thủ đô Hà Nội',
	'TP. Hồ Chí Minh',
	'TP. Đà Nẵng',
	'TP. Hải Phòng',
	'TP. Cần Thơ',
	'Tỉnh Hà Nam Ninh (Hà Nam - Nam Định - Ninh Bình)',
	'Tỉnh Vĩnh Phú (Vĩnh Phúc - Phú Thọ)',
	'Tỉnh Bắc Thái (Bắc Kạn - Thái Nguyên)',
	'Tỉnh Hà Bắc (Bắc Giang - Bắc Ninh)',
	'Tỉnh Hưng Hải (Hưng Yên - Hải Dương)',
	'Tỉnh Quảng Ninh',
	'Tỉnh Hà Tuyên (Hà Giang - Tuyên Quang)',
	'Tỉnh Lào Yên (Lào Cai - Yên Bái)',
	'Tỉnh Cao Lạng (Cao Bằng - Lạng Sơn)',
	'Tỉnh Lai Điện (Lai Châu - Điện Biên)',
	'Tỉnh Sơn La',
	'Tỉnh Hòa Bình',
	'Tỉnh Thanh Hóa',
	'Tỉnh Nghệ Tĩnh (Nghệ An - Hà Tĩnh)',
	'Tỉnh Bình Trị Thiên (Quảng Bình - Quảng Trị - Thừa Thiên Huế)',
	'Tỉnh Quảng Nam',
	'Tỉnh Quảng Ngãi',
	'Tỉnh Bình Định',
	'Tỉnh Phú Khánh (Phú Yên - Khánh Hòa)',
	'Tỉnh Thuận Hải (Ninh Thuận - Bình Thuận)',
	'Tỉnh Gia Lai - Kon Tum (Gia Lai - Kon Tum)',
	'Tỉnh Đắk Lắk (Đắk Lắk - Đắk Nông)',
	'Tỉnh Lâm Đồng',
	'Tỉnh Đồng Nai (Đồng Nai - Bình Phước)',
	'Tỉnh Bình Dương',
	'Tỉnh Tây Ninh',
	'Tỉnh Bà Rịa - Vũng Tàu',
	'Tỉnh Long An',
	'Tỉnh Tiền Giang - Bến Tre (Tiền Giang - Bến Tre)',
);"""

with open(rec_file, 'r', encoding='utf-8') as f:
    text = f.read()

import re
text = re.sub(r'\$provinces_merged_34\s*=\s*array\([^)]+\);', exact_34_code, text, flags=re.DOTALL)

with open(rec_file, 'w', encoding='utf-8') as f:
    f.write(text)

print("Updated template-recruitments.php with EXACTLY 34 official provinces according to Resolution 202/2025/QH15!")
