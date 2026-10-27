<?php
/**
 * /dieu-khoan-su-dung/ - Điều khoản sử dụng (28/10/2026).
 * Mô tả đúng các chức năng đang có (tài khoản email/Google, xác minh email, ôn thi, tải đề PDF, AI Coach,
 * tin tuyển dụng thu thập từ nguồn chính thức, tài khoản đơn vị, đề có phí). Cập nhật khi thay đổi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cvc_tos_updated = '28/10/2026';
cvc_seo_set_title( 'Điều khoản sử dụng' );
cvc_seo_set_description( 'Điều khoản sử dụng congvienchuc.com: tài khoản, nội dung tham khảo, đề thi và AI Coach, quyền sở hữu nội dung, hành vi bị cấm, đơn vị tuyển dụng, dịch vụ có phí và liên hệ.' );
cvc_seo_set_canonical( home_url( '/dieu-khoan-su-dung/' ) );

get_header();
?>
<style>
.pp{background:#f6f5f1;font-family:"Times New Roman",Tinos,Times,serif;color:#1d2733}
.pp-wrap{max-width:860px;margin:0 auto;padding:18px 16px 56px}
.pp-card{background:#fff;border:1px solid #dfe4e8;border-radius:18px;padding:26px 28px;box-shadow:0 10px 30px #0a1d330d}
.pp h1{margin:0 0 6px;color:#0a1d33;font-size:30px;line-height:1.2}
.pp .pp-meta{margin:0 0 18px;color:#55657a;font-size:15px}
.pp h2{margin:26px 0 8px;color:#0a1d33;font-size:21px;border-left:4px solid #c9a052;padding-left:10px}
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
				array( 'label' => 'Điều khoản sử dụng' ),
			)
		);
		?>
		<article class="pp-card">
			<h1>Điều khoản sử dụng</h1>
			<p class="pp-meta">Cập nhật lần cuối: <?php echo esc_html( $cvc_tos_updated ); ?> · Áp dụng cho congvienchuc.com và api.congvienchuc.com</p>

			<nav class="pp-toc" aria-label="Mục lục">
				<ol>
					<li><a href="#pham-vi">Phạm vi và chấp nhận</a></li>
					<li><a href="#tai-khoan">Tài khoản</a></li>
					<li><a href="#noi-dung">Nội dung mang tính tham khảo</a></li>
					<li><a href="#on-thi">Đề thi, ngân hàng câu hỏi và AI Coach</a></li>
					<li><a href="#so-huu">Quyền đối với nội dung</a></li>
					<li><a href="#cam">Hành vi không được phép</a></li>
					<li><a href="#don-vi">Tài khoản đơn vị tuyển dụng</a></li>
					<li><a href="#co-phi">Dịch vụ có phí</a></li>
					<li><a href="#email">Email và thông báo</a></li>
					<li><a href="#cham-dut">Tạm ngừng, chấm dứt</a></li>
					<li><a href="#trach-nhiem">Giới hạn trách nhiệm</a></li>
					<li><a href="#thay-doi">Thay đổi điều khoản</a></li>
					<li><a href="#lien-he">Liên hệ</a></li>
				</ol>
			</nav>

			<h2 id="pham-vi">1. Phạm vi và chấp nhận</h2>
			<p>Công Viên Chức (congvienchuc.com) là nền tảng tra cứu văn bản pháp luật, ôn thi trắc nghiệm, tổng hợp thông tin tuyển dụng công chức, viên chức và các công cụ hỗ trợ ôn tập. Khi tạo tài khoản (bằng email hoặc bằng Google) hoặc sử dụng các chức năng của trang, bạn đồng ý với Điều khoản sử dụng này và <a href="<?php echo esc_url( home_url( '/chinh-sach-bao-mat/' ) ); ?>">Chính sách bảo mật</a>. Nếu không đồng ý, vui lòng không sử dụng dịch vụ.</p>

			<h2 id="tai-khoan">2. Tài khoản</h2>
			<ul>
				<li>Bạn cung cấp họ tên và email chính xác, thuộc quyền sử dụng của bạn. Mỗi người dùng một tài khoản; không chia sẻ, cho mượn hoặc mua bán tài khoản.</li>
				<li>Bạn tự bảo mật mật khẩu và chịu trách nhiệm về hoạt động phát sinh từ tài khoản của mình. Khi phát hiện tài khoản bị dùng trái phép, hãy đổi mật khẩu và báo cho chúng tôi.</li>
				<li>Tài khoản đăng ký bằng email cần <b>xác minh email</b> trước khi làm bài thi, dùng ngân hàng câu hỏi, tải đề PDF và nhận bản tin. Tài khoản đăng nhập bằng Google được xác minh qua Google.</li>
				<li>Đăng nhập bằng Google: chúng tôi chỉ nhận họ tên, email và mã định danh tài khoản Google để tạo / đăng nhập tài khoản; không nhận mật khẩu Google của bạn.</li>
			</ul>

			<h2 id="noi-dung">3. Nội dung mang tính tham khảo</h2>
			<ul>
				<li>Văn bản pháp luật, thông báo tuyển dụng và các thông tin khác được thu thập, trích xuất và tóm tắt từ nguồn công khai (ưu tiên cổng thông tin chính thức của cơ quan nhà nước). Quá trình này có thể chậm, thiếu hoặc sai sót.</li>
				<li>Thông tin trên trang <b>chỉ mang tính tham khảo</b>. Trước khi nộp hồ sơ hoặc áp dụng một quy định, bạn cần đối chiếu văn bản, thông báo gốc của cơ quan ban hành (đường dẫn nguồn và tệp gốc được hiển thị tại từng tin, từng văn bản).</li>
				<li>Tin tuyển dụng chưa rõ hạn nộp được ghi rõ "Chưa rõ hạn nộp" và tự ẩn sau một thời gian; số chỉ tiêu, điều kiện, lịch thi luôn căn cứ theo thông báo của cơ quan tuyển dụng.</li>
				<li>Nội dung trên trang không phải là tư vấn pháp lý.</li>
			</ul>

			<h2 id="on-thi">4. Đề thi, ngân hàng câu hỏi và AI Coach</h2>
			<ul>
				<li>Đề luyện tập, đề thi thử và ngân hàng câu hỏi do Công Viên Chức biên soạn để ôn tập, <b>không phải đề thi chính thức</b> của bất kỳ kỳ thi nào. Đáp án và căn cứ có thể có sai sót; bạn có thể dùng chức năng "Báo câu sai" để chúng tôi kiểm tra.</li>
				<li>AI Coach là công cụ hỗ trợ dùng trí tuệ nhân tạo; câu trả lời có thể không chính xác hoặc chưa cập nhật. Hãy kiểm tra lại với văn bản pháp luật gốc.</li>
				<li>Kết quả luyện tập, điểm số, bảng xếp hạng chỉ dùng để tự đánh giá, không có giá trị xác nhận năng lực.</li>
			</ul>

			<h2 id="so-huu">5. Quyền đối với nội dung</h2>
			<ul>
				<li>Văn bản quy phạm pháp luật, văn bản hành chính và thông tin thời sự thuần túy không thuộc đối tượng bảo hộ quyền tác giả theo Luật Sở hữu trí tuệ; chúng tôi trình bày lại để dễ tra cứu và luôn dẫn nguồn.</li>
				<li>Câu hỏi, lời giải, bộ đề, đề PDF, bài tóm tắt, giao diện, logo và các nội dung do Công Viên Chức biên soạn thuộc quyền của Công Viên Chức. Bạn được dùng cho mục đích học tập cá nhân; không sao chép hàng loạt, bán lại, đăng tải lại hoặc phân phối cho mục đích thương mại khi chưa có đồng ý bằng văn bản.</li>
				<li>Đề PDF được hạn chế sao chép, chỉnh sửa và gắn mã tải xuống; không tìm cách gỡ các hạn chế này.</li>
			</ul>

			<h2 id="cam">6. Hành vi không được phép</h2>
			<ul>
				<li>Dùng công cụ tự động (bot, scraper) để thu thập dữ liệu hàng loạt, vượt các giới hạn truy cập, hoặc gây quá tải hệ thống.</li>
				<li>Tấn công, dò quét lỗ hổng, truy cập trái phép vào tài khoản hoặc dữ liệu của người khác.</li>
				<li>Mạo danh cơ quan, tổ chức, cá nhân; đăng thông tin tuyển dụng giả, sai sự thật hoặc thu phí trái quy định.</li>
				<li>Đăng nội dung vi phạm pháp luật, xúc phạm, lừa đảo, quảng cáo trái phép.</li>
			</ul>

			<h2 id="don-vi">7. Tài khoản đơn vị tuyển dụng</h2>
			<ul>
				<li>Tài khoản đơn vị được xác minh bằng email công vụ hoặc giấy giới thiệu / giấy phép có dấu; quyền đăng và cập nhật thông báo phụ thuộc kết quả xác minh.</li>
				<li>Đơn vị chịu trách nhiệm về tính chính xác, hợp pháp của thông báo tuyển dụng, đính chính và cập nhật do mình đăng.</li>
				<li>Công Viên Chức có thể từ chối, chỉnh sửa định dạng, gắn nhãn hoặc gỡ nội dung không phù hợp, trùng lặp hoặc có dấu hiệu sai sự thật.</li>
			</ul>

			<h2 id="co-phi">8. Dịch vụ có phí</h2>
			<ul>
				<li>Một số nội dung (ví dụ đề luyện cá nhân hóa theo tin tuyển dụng) có thu phí. Giá, phạm vi và thời hạn sử dụng được hiển thị trước khi bạn thanh toán.</li>
				<li>Thanh toán qua các phương thức hiển thị tại trang thanh toán. Công Viên Chức không lưu thông tin thẻ ngân hàng của bạn.</li>
				<li>Nếu đã thanh toán nhưng chưa được mở quyền sử dụng, hãy liên hệ <a href="mailto:info@congvienchuc.com">info@congvienchuc.com</a> kèm nội dung chuyển khoản / mã giao dịch để được kiểm tra và xử lý.</li>
			</ul>

			<h2 id="email">9. Email và thông báo</h2>
			<p>Chúng tôi gửi email cần thiết cho tài khoản (mã xác minh, đặt lại mật khẩu, xác nhận thanh toán) và một số email tùy chọn (chào mừng, bản tin tuần, nhắc ôn, tin tuyển dụng phù hợp). Bạn có thể tắt email tùy chọn tại Tài khoản → Thông báo hoặc bằng liên kết "Hủy nhận" trong mỗi email.</p>

			<h2 id="cham-dut">10. Tạm ngừng, chấm dứt</h2>
			<ul>
				<li>Chúng tôi có thể tạm khóa hoặc chấm dứt tài khoản vi phạm Điều khoản này, sau khi thông báo khi có thể.</li>
				<li>Bạn có thể ngừng sử dụng bất kỳ lúc nào và yêu cầu xóa tài khoản theo hướng dẫn tại Chính sách bảo mật.</li>
				<li>Dịch vụ có thể tạm gián đoạn để bảo trì, nâng cấp hoặc do sự cố ngoài tầm kiểm soát.</li>
			</ul>

			<h2 id="trach-nhiem">11. Giới hạn trách nhiệm</h2>
			<p>Trong phạm vi pháp luật cho phép, Công Viên Chức không chịu trách nhiệm đối với thiệt hại phát sinh từ việc dựa vào thông tin tham khảo trên trang mà không đối chiếu văn bản, thông báo gốc; từ nội dung do đơn vị tuyển dụng hoặc bên thứ ba cung cấp; hoặc từ gián đoạn dịch vụ ngoài tầm kiểm soát hợp lý của chúng tôi.</p>

			<h2 id="thay-doi">12. Thay đổi điều khoản</h2>
			<p>Khi Điều khoản thay đổi, ngày cập nhật ở đầu trang sẽ được thay đổi; thay đổi quan trọng sẽ được thông báo qua email hoặc trên trang. Việc tiếp tục sử dụng sau thời điểm cập nhật đồng nghĩa với việc bạn chấp nhận Điều khoản mới. Điều khoản được hiểu và áp dụng theo pháp luật Việt Nam.</p>

			<h2 id="lien-he">13. Liên hệ</h2>
			<p>Mọi câu hỏi về Điều khoản sử dụng, vui lòng gửi tới <a href="mailto:info@congvienchuc.com">info@congvienchuc.com</a>.</p>
		</article>
	</div>
</main>
<?php
get_footer();
