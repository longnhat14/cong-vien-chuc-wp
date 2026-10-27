<?php
/**
 * /chinh-sach-bao-mat/ - Chính sách bảo mật và xử lý dữ liệu cá nhân (27/10/2026).
 * Nội dung mô tả đúng dữ liệu hệ thống đang thu và xử lý (backend Laravel + theme WordPress); cập nhật khi thay đổi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_pp_updated = '27/10/2026';
cvc_seo_set_title( 'Chính sách bảo mật' );
cvc_seo_set_description( 'Công Viên Chức thu thập những dữ liệu nào, dùng vào việc gì, chia sẻ với ai, lưu trữ thế nào và quyền của bạn đối với dữ liệu cá nhân khi dùng congvienchuc.com.' );
cvc_seo_set_canonical( home_url( '/chinh-sach-bao-mat/' ) );

get_header();
?>
<style>
.pp{background:#f6f5f1;font-family:"Times New Roman",Tinos,Times,serif;color:#1d2733}
.pp-wrap{max-width:860px;margin:0 auto;padding:18px 16px 56px}
.pp-card{background:#fff;border:1px solid #dfe4e8;border-radius:18px;padding:26px 28px;box-shadow:0 10px 30px #0a1d330d}
.pp h1{margin:0 0 6px;color:#0a1d33;font-size:30px;line-height:1.2}
.pp .pp-meta{margin:0 0 18px;color:#55657a;font-size:15px}
.pp h2{margin:26px 0 8px;color:#0a1d33;font-size:21px;border-left:4px solid #f07819;padding-left:10px}
.pp p,.pp li{font-size:17px;line-height:1.65}
.pp ul{padding-left:22px;margin:6px 0 10px}
.pp li{margin-bottom:5px}
.pp a{color:#204f7c}
.pp-toc{margin:0 0 6px;padding:12px 16px;background:#fbfaf7;border:1px solid #efe7d6;border-radius:12px}
.pp-toc ol{margin:0;padding-left:20px;columns:2;column-gap:28px}
.pp-toc li{font-size:15.5px;margin-bottom:3px}
@media (max-width:600px){.pp-card{padding:18px 16px}.pp h1{font-size:25px}.pp-toc ol{columns:1}}
</style>
<main id="main" class="pp">
	<div class="pp-wrap">
		<?php
		cvc_render_breadcrumbs(
			array(
				array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'label' => 'Chính sách bảo mật' ),
			)
		);
		?>
		<article class="pp-card">
			<h1>Chính sách bảo mật</h1>
			<p class="pp-meta">Cập nhật lần cuối: <?php echo esc_html( $cvc_pp_updated ); ?> · Áp dụng cho congvienchuc.com và api.congvienchuc.com</p>

			<nav class="pp-toc" aria-label="Mục lục">
				<ol>
					<li><a href="#pham-vi">Phạm vi</a></li>
					<li><a href="#du-lieu">Dữ liệu chúng tôi thu thập</a></li>
					<li><a href="#muc-dich">Mục đích sử dụng</a></li>
					<li><a href="#email">Email chúng tôi gửi</a></li>
					<li><a href="#ai">AI Coach và trí tuệ nhân tạo</a></li>
					<li><a href="#cookie">Cookie và lưu trữ trên trình duyệt</a></li>
					<li><a href="#chia-se">Chia sẻ dữ liệu</a></li>
					<li><a href="#luu-tru">Lưu trữ và bảo mật</a></li>
					<li><a href="#quyen">Quyền của bạn</a></li>
					<li><a href="#thay-doi">Thay đổi chính sách</a></li>
					<li><a href="#lien-he">Liên hệ</a></li>
				</ol>
			</nav>

			<h2 id="pham-vi">1. Phạm vi</h2>
			<p>Chính sách này giải thích cách Công Viên Chức (congvienchuc.com) thu thập, sử dụng, chia sẻ và bảo vệ dữ liệu cá nhân của người dùng khi bạn truy cập website, tạo tài khoản, ôn thi, tải tài liệu, dùng AI Coach hoặc đăng tin với tư cách đơn vị tuyển dụng. Chúng tôi xử lý dữ liệu cá nhân theo quy định của pháp luật Việt Nam về bảo vệ dữ liệu cá nhân. Khi sử dụng dịch vụ, bạn đồng ý với việc xử lý dữ liệu như mô tả dưới đây; bạn có thể rút lại đồng ý bất cứ lúc nào (xem mục 9).</p>

			<h2 id="du-lieu">2. Dữ liệu chúng tôi thu thập</h2>
			<ul>
				<li><b>Tài khoản:</b> họ tên, địa chỉ email, mật khẩu (chỉ lưu dạng mã hóa một chiều, không ai đọc được mật khẩu gốc).</li>
				<li><b>Hồ sơ và mục tiêu (bạn tự nhập, không bắt buộc):</b> số điện thoại, tỉnh/thành, trình độ, chuyên ngành, ngạch mục tiêu, ảnh đại diện; mục tiêu ôn thi (tỉnh, ngành, vị trí, cơ quan, ngày thi dự kiến); tùy chọn hiển thị tên trên bảng xếp hạng.</li>
				<li><b>Hoạt động học tập:</b> lượt làm bài, câu trả lời, điểm, tiến độ theo từng câu hỏi, câu hỏi đã lưu, tin và văn bản đã đánh dấu, báo câu sai, chứng chỉ, lộ trình học, lịch sử tải đề PDF (mỗi file gắn một mã tải).</li>
				<li><b>Đơn vị tuyển dụng:</b> tên đơn vị, mã số thuế, địa chỉ, điện thoại, email công vụ, website và tài liệu xác minh bạn tải lên.</li>
				<li><b>Thanh toán:</b> mã đơn hàng, gói đã mua, số tiền, trạng thái và mã giao dịch do cổng thanh toán trả về. Chúng tôi <b>không</b> nhận và không lưu số thẻ, mật khẩu hay thông tin đăng nhập ngân hàng của bạn — các thông tin này nhập trực tiếp tại cổng thanh toán hoặc ứng dụng ngân hàng.</li>
				<li><b>Dữ liệu kỹ thuật:</b> địa chỉ IP và thông tin trình duyệt khi bạn làm bài, tải đề PDF hoặc khi quản trị viên thao tác (để chống gian lận, bảo mật và giới hạn truy cập bất thường). Địa chỉ IP có thể được dùng để ước tính tỉnh/thành nhằm gợi ý tin tuyển dụng gần bạn. Khi bạn mở site từ liên kết trong file PDF, ảnh chia sẻ hoặc email, chúng tôi ghi nhận lượt truy cập kèm địa chỉ IP đã mã hóa một chiều để đo hiệu quả, không dùng để nhận diện bạn.</li>
			</ul>

			<h2 id="muc-dich">3. Mục đích sử dụng</h2>
			<ul>
				<li>Cung cấp dịch vụ: đăng nhập, chấm điểm, lưu tiến độ, gợi ý đề và văn bản cần ôn, tạo file PDF có tên người tải ở chân trang.</li>
				<li>Cá nhân hóa: gợi ý tin tuyển dụng, bộ đề và lộ trình theo tỉnh, ngành bạn quan tâm (suy ra từ mục tiêu, hồ sơ, đề đã làm và bộ đề đã tải).</li>
				<li>Gửi thông báo và email liên quan đến tài khoản, tin phù hợp, nhắc hạn và bản tin ôn thi (xem mục 4).</li>
				<li>Xử lý thanh toán, cấp quyền sử dụng gói đã mua và hỗ trợ khi có sự cố giao dịch.</li>
				<li>Bảo mật, chống lạm dụng, chống phát tán trái phép tài liệu; thống kê tổng hợp để cải thiện nội dung và sản phẩm.</li>
				<li>Thực hiện nghĩa vụ theo yêu cầu của cơ quan nhà nước có thẩm quyền khi pháp luật quy định.</li>
			</ul>
			<p>Chúng tôi không bán dữ liệu cá nhân và không dùng dữ liệu của bạn cho quảng cáo của bên thứ ba.</p>

			<h2 id="email">4. Email chúng tôi gửi</h2>
			<p>Email được gửi từ <b>info@congvienchuc.com</b>, gồm: email bắt buộc liên quan đến tài khoản (đặt lại mật khẩu, xác minh đơn vị); email thông báo (tin phù hợp mục tiêu, nhắc hạn nộp hồ sơ, cập nhật đợt tuyển, bảng xếp hạng); email chào mừng, bản tin ôn thi hằng tuần và email nhắc ôn khi bạn lâu chưa vào học. Mỗi email không bắt buộc đều có liên kết <b>hủy nhận một chạm</b>; bạn cũng có thể chọn loại email muốn nhận tại <a href="<?php echo esc_url( home_url( '/tai-khoan/thong-bao/#email' ) ); ?>">Tài khoản → Thông báo</a>. Liên kết trong email có gắn tham số đo lường (utm) để biết email nào hữu ích; chúng tôi không dùng ảnh theo dõi việc mở thư.</p>

			<h2 id="ai">5. AI Coach và trí tuệ nhân tạo</h2>
			<p>Khi bạn hỏi AI Coach, nội dung câu hỏi (kèm vài lượt trò chuyện gần nhất để giữ ngữ cảnh) được gửi tới nhà cung cấp mô hình AI mà hệ thống đang dùng để tạo câu trả lời. Lịch sử trò chuyện được lưu trong trình duyệt của bạn (mất khi đóng tab hoặc khi bấm "Trò chuyện mới"); máy chủ của chúng tôi chỉ ghi số lượng token và chi phí của mỗi lần gọi, không lưu nội dung theo tài khoản. Câu trả lời cho các câu hỏi trùng nhau có thể được lưu tạm (không gắn với người hỏi) để trả lời nhanh hơn. Đừng nhập thông tin nhạy cảm (số giấy tờ, thông tin sức khỏe, tài chính…) vào khung trò chuyện. Câu trả lời của AI chỉ mang tính tham khảo — hãy đối chiếu văn bản gốc.</p>
			<p>Một phần câu hỏi trắc nghiệm, giải thích và nội dung ôn tập được tạo với sự hỗ trợ của AI từ văn bản pháp luật chính thức và được kiểm tra trước khi đăng; việc này không dùng dữ liệu cá nhân của bạn.</p>

			<h2 id="cookie">6. Cookie và lưu trữ trên trình duyệt</h2>
			<ul>
				<li><b>Cookie đăng nhập</b> (HttpOnly, chỉ máy chủ đọc được) để giữ phiên đăng nhập; cookie ngắn hạn để hiển thị thông báo sau thao tác.</li>
				<li><b>Bộ nhớ trình duyệt</b> (localStorage / sessionStorage) để nhớ lựa chọn giao diện, một số trạng thái tạm thời, AI bạn chọn và hội thoại AI Coach trong phiên.</li>
				<li>Chúng tôi hiện <b>không</b> dùng công cụ quảng cáo hay theo dõi của bên thứ ba (như pixel quảng cáo). Nếu thay đổi, chính sách này sẽ được cập nhật trước.</li>
			</ul>

			<h2 id="chia-se">7. Chia sẻ dữ liệu</h2>
			<p>Chúng tôi chỉ chia sẻ dữ liệu ở mức cần thiết với:</p>
			<ul>
				<li><b>Nhà cung cấp hạ tầng:</b> máy chủ, lưu trữ và dịch vụ gửi email (Hostinger).</li>
				<li><b>Cổng thanh toán</b> (VNPay, SePay/VietQR) khi bạn thanh toán — chỉ thông tin đơn hàng cần cho giao dịch.</li>
				<li><b>Nhà cung cấp AI</b> khi bạn dùng AI Coach (mục 5).</li>
				<li><b>Công khai theo lựa chọn của bạn:</b> tên hiển thị và điểm trên bảng xếp hạng (có thể ẩn trong hồ sơ); thông tin đơn vị tuyển dụng trên tin đăng.</li>
				<li><b>Cơ quan nhà nước có thẩm quyền</b> khi có yêu cầu hợp pháp theo quy định của pháp luật.</li>
			</ul>
			<p>Một số nhà cung cấp có thể xử lý dữ liệu trên máy chủ đặt ngoài Việt Nam; chúng tôi chỉ chuyển phần dữ liệu cần cho dịch vụ đó.</p>

			<h2 id="luu-tru">8. Lưu trữ và bảo mật</h2>
			<ul>
				<li>Kết nối được mã hóa (HTTPS); mật khẩu được băm một chiều; khóa truy cập dịch vụ bên thứ ba không lưu trong mã nguồn.</li>
				<li>Quyền quản trị phân theo vai trò; thao tác quản trị được ghi nhật ký.</li>
				<li>Liên kết tải file PDF chỉ có hiệu lực 10 phút; file in tên người tải (đã che bớt) và mã tải để hạn chế phát tán.</li>
				<li>Dữ liệu tài khoản và lịch sử học được lưu trong thời gian tài khoản còn hoạt động; khi bạn yêu cầu xóa tài khoản, dữ liệu gắn với tài khoản sẽ bị xóa hoặc ẩn danh, trừ dữ liệu giao dịch phải lưu theo quy định về kế toán, thuế.</li>
			</ul>
			<p>Không có hệ thống nào an toàn tuyệt đối. Nếu phát hiện sự cố ảnh hưởng đến dữ liệu của bạn, chúng tôi sẽ thông báo và xử lý theo quy định.</p>

			<h2 id="quyen">9. Quyền của bạn</h2>
			<p>Bạn có quyền: được biết về việc xử lý dữ liệu; xem và tải lại dữ liệu của mình; chỉnh sửa thông tin không chính xác; rút lại đồng ý; phản đối hoặc hạn chế xử lý (ví dụ ngừng nhận email); yêu cầu xóa tài khoản và dữ liệu; khiếu nại theo quy định của pháp luật.</p>
			<ul>
				<li>Sửa hồ sơ, mục tiêu, tùy chọn email: tại <a href="<?php echo esc_url( home_url( '/tai-khoan/' ) ); ?>">Tài khoản</a>.</li>
				<li>Yêu cầu bản sao dữ liệu hoặc xóa tài khoản: gửi email tới <a href="mailto:info@congvienchuc.com">info@congvienchuc.com</a> từ địa chỉ email đã đăng ký. Chúng tôi có thể xác minh danh tính trước khi xử lý.</li>
			</ul>

			<h2 id="thay-doi">10. Thay đổi chính sách</h2>
			<p>Chính sách có thể được cập nhật khi tính năng hoặc quy định thay đổi. Ngày cập nhật ghi ở đầu trang; với thay đổi quan trọng, chúng tôi sẽ thông báo trên website hoặc qua email.</p>

			<h2 id="lien-he">11. Liên hệ</h2>
			<p>Mọi câu hỏi về dữ liệu cá nhân và chính sách này, vui lòng liên hệ: <a href="mailto:info@congvienchuc.com">info@congvienchuc.com</a>.</p>
		</article>
	</div>
</main>
<?php
get_footer();
