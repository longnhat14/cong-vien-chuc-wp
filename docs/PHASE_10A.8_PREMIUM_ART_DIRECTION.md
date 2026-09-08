# Phase 10A.8 — Premium Art Direction & UI Polish

Vòng art-direction có mục tiêu hẹp và cụ thể: **không redesign, không
thêm section** - chỉ soi kỹ 2 thứ người dùng chỉ ra rõ nhất (hero còn
"phẳng", header còn "hơi nhỏ") bằng screenshot crop thật, sửa đúng chỗ,
xác nhận lại bằng screenshot.

Ảnh reference người dùng gửi trực tiếp trong hội thoại lần này (2 bản,
cùng nguồn `/mnt/d/Claude/home-page-congvienchuc1.png`) đã đối chiếu và
xác nhận **khớp 100%** với `docs/ui-benchmark/homepage-reference.png` đã
dùng xuyên suốt từ 10A.6 - không có gì mới trong nội dung ảnh, chỉ là gửi
lại để đảm bảo tôi có mở đúng ảnh.

## 1. Phương pháp - crop QA trước khi sửa

Chụp crop riêng `.site-header` bằng Puppeteer (`page.$(selector)
.screenshot()`, kỹ thuật đã có từ 10A.7) **trước khi sửa gì** - phát hiện
rõ: ở 1440px, nav 7 mục bị wrap xuống dòng 2 ("Văn bản pháp luật" rớt
dòng), khác hẳn ảnh benchmark (nav luôn 1 hàng ngang gọn). Đây là bằng
chứng cụ thể, không phải cảm tính.

## 2. Header - sửa nav bị wrap + tăng brand presence

**Nguyên nhân**: `.site-header__inner` dùng chung class `.container`
(max-width 1240px) với toàn bộ nội dung trang - logo + 7 nav item (có
icon) + search + 2 nút auth không đủ chỗ trong 1240px ở 1440px viewport.

**Sửa**: `.site-header__inner` được cấp `max-width: 1400px` riêng (khai
báo sau `.container` trong file nên thắng theo thứ tự cascade, không cần
`!important`) - header là 1 dải riêng biệt về mặt bố cục, không bắt buộc
bó hẹp theo đúng content width như các section khác. Đồng thời tăng nhẹ
brand presence (Phần 11 "Logo phải đủ mạnh để người dùng nhớ"): logo mark
42px→46px, icon bên trong 26px→28px, chữ "Công Viên Chức" 1.2rem→1.3rem.

**Xác nhận**: crop lại `.site-header` sau khi sửa - toàn bộ 7 mục nav +
2 nút auth nằm gọn 1 hàng ở 1440px, logo rõ hơn.

## 3. Hero illustration - thêm gradient/shadow, bỏ flat-fill

Người dùng (và chính prompt) nhấn mạnh: "Không chấp nhận hero: building
icon + person icon... phải có composition, depth, layers". Ảnh minh họa
ở 10A.7 (person + building + flag + cap) đã đúng bố cục nhưng mọi hình
khối đều **fill phẳng 1 màu** - đọc như icon vector, không như
illustration có chiều sâu.

**Giới hạn kỹ thuật cần nói rõ**: môi trường này không có công cụ tạo
ảnh (image generation) - không thể tạo ảnh chụp/illustration editorial
thật. Lựa chọn khả thi nhất trong giới hạn SVG/CSS: thêm **gradient +
soft drop-shadow filter** thay vì fill phẳng, đây là kỹ thuật hợp lệ theo
đúng Phần 5 phiên này ("Nếu không [hỗ trợ image generation]: dùng SVG/CSS/
vector composition chất lượng cao").

Đã thêm:
- `<linearGradient>` cho: mái nhà (`cvcRoofGradient`, xanh sáng→đậm tạo
  cảm giác ánh sáng chiếu từ 1 phía), cửa chính (`cvcDoorGradient`), áo
  vest người (`cvcSuitGradient`, navy sáng→tối), da mặt
  (`cvcSkinGradient`), tóc (`cvcHairGradient`), mũ tốt nghiệp
  (`cvcCapGradient`, vàng sáng→cam đậm).
- `<feDropShadow>` filter (`cvcSoftShadow`) áp cho cả 3 nhóm hình khối
  chính (tòa nhà, mũ, người) - tạo cảm giác "nổi khối" thay vì dán phẳng
  lên nền.
- Thêm 3 dải sáng nhỏ (highlight) trên các ô cửa sổ tòa nhà + 1 dải bóng
  đổ dưới chân tòa nhà - tăng cảm giác 3D nhẹ mà không làm rối.

**Xác nhận**: crop `.cvc-hero__visual` trước/sau - person + building giờ
có khối rõ ràng (mái nhà có mặt sáng/tối phân biệt, áo vest có sắc độ,
không còn "dán màu phẳng").

## 4. Vẫn giữ nguyên (không sửa vì đã đúng từ 10A.7)

- Vị trí nhân vật bên trái (x≤155/480) - đã xác nhận không bao giờ bị
  floating card che, giữ nguyên bố cục.
- Course artwork 5-scene theo category (10A.7) - crop lại xác nhận vẫn
  hiển thị đúng, phối hợp tốt với gradient nền có sẵn từ trước.
- Kiến trúc fixture/feature flag, resource hub, goal section, learning
  journey, CTA banner, footer - không chạm vào theo đúng yêu cầu "không
  redesign, không thêm section".

## 5. Screenshot QA đã chạy

1. Crop `.site-header` (trước sửa) → phát hiện nav wrap.
2. Sửa CSS header.
3. Crop `.site-header` (sau sửa) → xác nhận 1 hàng.
4. Crop `.cvc-hero__visual` (trước sửa gradient) → xác nhận vẫn đúng bố
   cục nhưng phẳng.
5. Thêm gradient/shadow vào illustration.
6. Crop `.cvc-hero__visual` (sau sửa) → xác nhận có chiều sâu.
7. Full-page 1440px → xem tổng thể, không có gì vỡ.
8. Crop `.cvc-card-grid` → xác nhận 3 course card vẫn đúng, badge/CTA
   không bị ảnh hưởng bởi thay đổi header/hero.
9. Full-page 390px (viewport, above-the-fold) → xác nhận mobile header/
   hero (logo lớn hơn 1 chút) không tràn, không vỡ.

## 6. QA khác

- PHP lint, CSS brace-balance, `git diff --check` sạch.
- HTTP thật toàn bộ trang public + dashboard đã đăng nhập - 200, 0 PHP
  warning/fatal, không có gì regression so với 10A.7.

## 7. Known limitations (không đổi)

- Vẫn không có ảnh chụp thật/illustration editorial thật - giới hạn cứng
  của môi trường (không có image generation tool), đã bù bằng gradient/
  shadow SVG thay vì fill phẳng, đây là giới hạn kỹ thuật thành thật, không
  phải bỏ sót.
- Các known limitations khác giữ nguyên từ 10A.6/10A.7 (statistics fixture,
  footer 2 cột, không có Community section).
