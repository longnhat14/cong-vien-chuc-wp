<?php
/**
 * /don-vi-tuyen-dung/ — giới thiệu cổng đăng tin cho đơn vị tuyển dụng (21/10/2026).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$set    = cvc_employer_public_settings();
$prices = (array) ( $set['prices'] ?? array() );
$emp    = cvc_is_logged_in() ? cvc_employer_of_current_user() : null;
$cta    = $emp ? cvc_employer_url() : ( cvc_is_logged_in() ? cvc_employer_url( 'dang-ky' ) : add_query_arg( 'loai', 'don-vi', cvc_register_url() ) );

cvc_seo_set_title( 'Đăng tin tuyển dụng công chức, viên chức miễn phí cho đơn vị | Công Viên Chức' );
if ( function_exists( 'cvc_seo_set_description' ) ) {
	cvc_seo_set_description( 'Cơ quan nhà nước, đơn vị sự nghiệp công lập và đơn vị ngoài công lập đăng thông báo tuyển dụng, cập nhật đợt tuyển, đính chính và theo dõi số thí sinh quan tâm trên congvienchuc.com.' );
}

get_header();
?>
<main id="main" class="dz-main">
<div class="dz-wrap space-y-6">
	<header class="dz-pagehead">
		<h1>Dành cho đơn vị tuyển dụng</h1>
		<p class="text-ink-2">Đăng thông báo tuyển công chức, viên chức tới hàng nghìn thí sinh đang ôn thi — nhanh, đúng, có xác minh.</p>
		<p class="mt-3"><a class="dz-btn dz-btn-primary" href="<?php echo esc_url( $cta ); ?>"><?php echo $emp ? 'Vào trang quản lý đơn vị' : 'Đăng ký đơn vị'; ?></a>
			<?php if ( ! cvc_is_logged_in() ) : ?> <a class="dz-btn" href="<?php echo esc_url( cvc_login_url( cvc_employer_url() ) ); ?>">Đơn vị đã có tài khoản: đăng nhập</a><?php endif; ?></p>
		<?php if ( empty( $set['enabled'] ) ) : ?><p class="dz-notice mt-3">Cổng đang tạm đóng nhận đăng ký mới.</p><?php endif; ?>
	</header>

	<section class="grid md:grid-cols-3 gap-4" aria-label="Lợi ích">
		<?php
		foreach ( array(
			array( 'fa-file-arrow-up', 'Tải công văn là xong', 'Tải bản PDF hoặc ảnh chụp thông báo có dấu. Hệ thống đọc chữ và điền sẵn vị trí, chỉ tiêu, hạn nộp; đơn vị chỉ cần kiểm tra.' ),
			array( 'fa-users', 'Đến đúng thí sinh', 'Tin được gợi ý cho thí sinh có mục tiêu phù hợp, nhắc hạn nộp, kèm đề ôn thi theo vị trí. Có thể ghim tin và gửi email giới thiệu.' ),
			array( 'fa-shield-halved', 'Có xác minh, có lịch sử', 'Tin mang nhãn "Đơn vị đăng trực tiếp – đã xác minh". Đính chính, gia hạn, danh sách đủ điều kiện, kết quả… đều có lịch sử công khai.' ),
		) as $b ) :
			?>
			<div class="dz-card"><p class="text-acc text-xl"><i class="fa-solid <?php echo esc_attr( $b[0] ); ?>" aria-hidden="true"></i></p><h2 class="font-black mt-1"><?php echo esc_html( $b[1] ); ?></h2><p class="text-sm text-ink-2 mt-1"><?php echo esc_html( $b[2] ); ?></p></div>
		<?php endforeach; ?>
	</section>

	<section class="dz-section"><h2 class="dz-h2">Cách đăng tin</h2>
		<ol class="dz-list">
			<li><div class="dz-item-title">1. Đăng ký tài khoản đơn vị</div><div class="dz-item-sum">Một lần điền: người phụ trách, email, tên đơn vị, tỉnh/thành, điện thoại cơ quan<?php echo ! empty( $set['private_enabled'] ) ? '; chọn công lập hoặc ngoài công lập' : ''; ?>.</div></li>
			<li><div class="dz-item-title">2. Xác minh</div><div class="dz-item-sum">Đăng ký bằng email công vụ .gov.vn / .edu.vn thì mã 6 số gửi ngay về email đó — nhập mã là xong. Hoặc gửi giấy giới thiệu có dấu (biên tập viên gọi xác nhận).</div></li>
			<li><div class="dz-item-title">3. Tải thông báo, kiểm tra, gửi</div><div class="dz-item-sum">Biên tập viên duyệt trong giờ hành chính. Đơn vị đăng chuẩn <?php echo (int) ( $set['trust_threshold'] ?? 3 ); ?> tin trở lên được tự đăng ngay.</div></li>
			<li><div class="dz-item-title">4. Cập nhật suốt đợt tuyển</div><div class="dz-item-sum">Đính chính, gia hạn, đăng danh sách đủ điều kiện, lịch thi, kết quả; xem lượt xem, lượt lưu, thí sinh bật nhắc hạn.</div></li>
		</ol>
	</section>

	<section class="dz-section"><h2 class="dz-h2">Chi phí</h2>
		<div class="dz-tablewrap"><table class="dz-table"><tbody>
			<tr><th scope="row">Đăng tin, cập nhật đợt, đính chính</th><td><?php echo esc_html( cvc_employer_money( (int) ( $prices['post'] ?? 0 ) ) ); ?></td></tr>
			<tr><th scope="row">Ghim tin đầu danh sách (<?php echo (int) ( $set['pin_days'] ?? 7 ); ?> ngày)</th><td><?php echo esc_html( cvc_employer_money( (int) ( $prices['pin'] ?? 0 ) ) ); ?></td></tr>
			<tr><th scope="row">Gửi email giới thiệu tin tới thí sinh phù hợp</th><td><?php echo esc_html( cvc_employer_money( (int) ( $prices['email'] ?? 0 ) ) ); ?></td></tr>
		</tbody></table></div>
		<?php if ( empty( $set['paid'] ) ) : ?><p class="text-sm text-ink-muted mt-2">Hiện miễn phí toàn bộ. Gói miễn phí: mỗi đơn vị ghim 1 tin cùng lúc, mỗi tin 1 lần gửi email.</p><?php endif; ?>
	</section>

	<section class="dz-section"><h2 class="dz-h2">Câu hỏi thường gặp</h2>
		<details class="dz-box"><summary class="dz-box-head cursor-pointer">Đăng trên congvienchuc.com có thay cho việc công khai theo quy định không?</summary><div class="dz-box-body text-sm">Không. Đơn vị vẫn công khai thông báo theo quy định (cổng thông tin của đơn vị, phương tiện thông tin đại chúng…). congvienchuc.com là kênh giúp thông báo đến được nhiều thí sinh hơn.</div></details>
		<details class="dz-box"><summary class="dz-box-head cursor-pointer">Tin của đơn vị đã có trên trang rồi thì sao?</summary><div class="dz-box-body text-sm">Hệ thống tự thu thập thông báo từ các cổng .gov.vn. Đơn vị dùng "Nhận quản lý tin" để đính chính, cập nhật đợt cho tin đó thay vì đăng trùng.</div></details>
		<details class="dz-box"><summary class="dz-box-head cursor-pointer">Đơn vị ngoài công lập có đăng được không?</summary><div class="dz-box-body text-sm"><?php echo ! empty( $set['private_enabled'] ) ? 'Có. Tin ngoài công lập hiển thị ở mục riêng, không trộn với tin công chức, viên chức.' : 'Hiện tạm dừng nhận đơn vị ngoài công lập.'; ?></div></details>
	</section>
</div>
</main>
<?php
get_footer();
