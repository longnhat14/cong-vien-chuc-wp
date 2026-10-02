import sys
import os

rec_file = r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-recruitments.php"

exact_provinces_code = """$provinces_merged_34 = array(
	'Tất cả 34 Tỉnh / Thành phố Mới (Đã Sáp Nhập)',
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
);"""

with open(rec_file, 'r', encoding='utf-8') as f:
    text = f.read()

import re
text = re.sub(r'\$provinces_merged_34\s*=\s*array\([^)]+\);', exact_provinces_code, text, flags=re.DOTALL)

with open(rec_file, 'w', encoding='utf-8') as f:
    f.write(text)

print("Updated template-recruitments.php with USER'S EXACT 34 MERGED PROVINCES TABLE!")
