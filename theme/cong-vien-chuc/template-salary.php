<?php
/**
 * /tinh-luong/ - Công cụ ước tính lương & lương hưu (16/10/2026).
 * Trước đây nằm trên trang chủ; trang chủ v17 dẫn link "Mở công cụ" về đây.
 * Tham số lương lấy từ cvc_salary_params() (inc/homepage-nextgen.php),
 * phần tính toán chạy bằng assets/js/homepage-nextgen.js.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

cvc_seo_set_title( 'Ước tính lương & lương hưu công chức, viên chức' );
cvc_seo_set_description( 'Ước tính lương theo hệ số, phụ cấp và lương cơ sở hiện hành; ước tính tỷ lệ hưởng lương hưu theo số năm đóng bảo hiểm xã hội.' );
cvc_seo_set_canonical( home_url( '/tinh-luong/' ) );

get_header();
?>
<main class="dz-wrap">
	<div class="dz-main">
		<header class="dz-pagehead">
			<p class="dz-kicker">Công cụ thực tiễn</p>
			<h1>Ước tính lương &amp; lương hưu</h1>
			<p>Nhập hệ số và các khoản phụ cấp để tham khảo mức thu nhập. Kết quả chỉ mang tính ước tính; mức chính thức căn cứ quyết định của cơ quan có thẩm quyền.</p>
		</header>
		<style>.cvc-salary-page #tinh-luong > .dz-h2{display:none}</style>
		<div class="cvc-salary-page"><?php cvc_render_salary_calculator(); ?></div>
	</div>
</main>
<?php
get_footer();
