import sys
import os

rec_file = r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-recruitments.php"

merged_provinces_code = """$provinces_merged_34 = array(
	'Tất cả 34 Tỉnh Thành Mới (Sau Sáp Nhập 2026)',
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
	'Tỉnh Gia Lai - Kon Tum',
	'Tỉnh Đắk Lắk (Đắk Lắk - Đắk Nông)',
	'Tỉnh Lâm Đồng',
	'Tỉnh Đồng Nai (Đồng Nai - Bình Phước)',
	'Tỉnh Bình Dương',
	'Tỉnh Tây Ninh',
	'Tỉnh Bà Rịa - Vũng Tàu',
	'Tỉnh Long An',
	'Tỉnh Tiền Giang - Bến Tre',
	'Tỉnh Đồng Tháp',
	'Tỉnh An Giang',
	'Tỉnh Kiên Giang',
	'Tỉnh Minh Hải (Cà Mau - Bạc Liêu)',
	'Tỉnh Cửu Long (Vĩnh Long - Trà Vinh)',
	'Tỉnh Hậu Giang - Sóc Trăng',
);"""

with open(rec_file, 'r', encoding='utf-8') as f:
    text = f.read()

# Replace title, description and array
text = text.replace("Thông Tin Tuyển Dụng Công Chức 3.000+ Xã Phường & 63 Tỉnh Thành 2026", "Thông Tin Tuyển Dụng Công Chức 3.000+ Xã Phường & 34 Tỉnh Thành Mới (Sau Sáp Nhập 2026)")
text = text.replace("và 63 Tỉnh/Thành phố toàn quốc.", "và 34 Tỉnh/Thành phố Mới sau sáp nhập đơn vị hành chính 2026.")
text = text.replace("🏛️ CỔNG THÔNG TIN 3.240 XÃ PHƯỜNG & 63 TỈNH THÀNH 2026", "🏛️ BẢN ĐỒ BÔ BỐ HÀNH CHÍNH MỚI — 34 TỈNH THÀNH & 3.240 XÃ PHƯỜNG 2026")
text = text.replace("63 Tỉnh Thành", "34 Tỉnh Thành Mới")

# Find provinces_63 definition and replace
import re
text = re.sub(r'\$provinces_63\s*=\s*array\([^)]+\);', merged_provinces_code, text, flags=re.DOTALL)
text = text.replace('$provinces_63', '$provinces_merged_34')

with open(rec_file, 'w', encoding='utf-8') as f:
    f.write(text)

print("Updated template-recruitments.php with 34 new merged provinces structure!")
