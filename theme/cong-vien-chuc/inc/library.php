<?php
/**
 * Thư viện tài liệu - menu "Tài liệu" 10 mục (31/10/2026).
 *
 *  /tai-lieu/cong-chuc/            01 Tài liệu ôn thi công chức
 *  /tai-lieu/vien-chuc/            02 Tài liệu ôn thi viên chức
 *  /van-ban-phap-luat/             03 Văn bản pháp luật (trang sẵn có, không làm trang trùng)
 *  /tai-lieu/nganh/{nganh}/        04 Tài liệu theo ngành nghề (+ trang danh sách ngành)
 *  /tai-lieu/tinh/{tinh}/          05 Tài liệu theo tỉnh/thành (+ trang danh sách tỉnh có dữ liệu)
 *  /tai-lieu/bo-cau-hoi/           06 Bộ câu hỏi và đáp án
 *  /tai-lieu/tom-tat-kien-thuc/    07 Tóm tắt kiến thức và sơ đồ tư duy
 *  /tai-lieu/giao-trinh/           08 Giáo trình và sách tham khảo
 *  /tai-lieu/bieu-mau/             09 Biểu mẫu và nghiệp vụ hành chính
 *  /tai-lieu/moi/                  10 Tài liệu mới
 *
 * Dữ liệu: Laravel GET /api/library/{view} (LibraryController) - ghép từ chủ đề, bài kiến thức, văn bản pháp luật,
 * đề luyện, bộ đề PDF theo tin, văn bản tuyển dụng gốc và bảng documents. Mục chưa có nội dung hiện "Sắp có".
 * Giao diện: template-library.php + assets/css/library.css.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cấu hình 10 mục (thứ tự = thứ tự menu). */
function cvc_library_views(): array {
	return array(
		'cong-chuc'         => array( 'no' => '01', 'path' => 'tai-lieu/cong-chuc', 'label' => 'Tài liệu ôn thi công chức', 'short' => 'Công chức', 'hint' => 'Kiến thức chung, chuyên ngành, vòng 1, vòng 2', 'icon' => 'cong-chuc',
			'h1' => 'Tài liệu ôn thi công chức', 'title' => 'Tài liệu ôn thi công chức 2026: kiến thức chung, chuyên ngành, vòng 1, vòng 2',
			'desc' => 'Tài liệu ôn thi công chức miễn phí: chủ đề kiến thức chung và chuyên ngành, văn bản pháp luật cần nắm, đề luyện vòng 1, bộ đề vòng 2 theo từng thông báo tuyển dụng.',
			'lead' => 'Sắp xếp theo đúng cấu trúc kỳ thi: kiến thức chung, chuyên ngành, đề luyện vòng 1 và bộ đề vòng 2 dựng theo từng thông báo tuyển công chức.' ),
		'vien-chuc'         => array( 'no' => '02', 'path' => 'tai-lieu/vien-chuc', 'label' => 'Tài liệu ôn thi viên chức', 'short' => 'Viên chức', 'hint' => 'Giáo dục, y tế, văn hóa, khoa học và lĩnh vực khác', 'icon' => 'vien-chuc',
			'h1' => 'Tài liệu ôn thi viên chức', 'title' => 'Tài liệu ôn thi viên chức 2026: giáo dục, y tế, văn hóa, khoa học',
			'desc' => 'Tài liệu ôn thi viên chức theo lĩnh vực: giáo dục, y tế, văn hóa, khoa học kỹ thuật; văn bản pháp luật, chủ đề kiến thức và bộ đề theo tin tuyển dụng.',
			'lead' => 'Kiến thức chung viên chức và tài liệu riêng từng lĩnh vực sự nghiệp: chủ đề trọng tâm, văn bản pháp luật của ngành và bộ đề theo tin tuyển dụng đang mở.' ),
		'van-ban-phap-luat' => array( 'no' => '03', 'path' => 'van-ban-phap-luat', 'label' => 'Văn bản pháp luật', 'short' => 'Văn bản pháp luật', 'hint' => 'Luật, nghị định, thông tư, quyết định, văn bản hợp nhất', 'icon' => 'van-ban', 'external' => true ),
		'nganh'             => array( 'no' => '04', 'path' => 'tai-lieu/nganh', 'label' => 'Tài liệu theo ngành nghề', 'short' => 'Theo ngành nghề', 'hint' => 'Giáo viên, kế toán, y tế, văn thư, địa chính, tư pháp…', 'icon' => 'nganh',
			'h1' => 'Tài liệu theo ngành nghề', 'title' => 'Tài liệu ôn thi công chức, viên chức theo ngành nghề',
			'desc' => 'Tài liệu ôn thi theo ngành: hành chính văn thư, giáo dục, y tế, tài chính kế toán, tư pháp, địa chính môi trường, văn hóa, công nghệ thông tin, khoa học kỹ thuật.',
			'lead' => 'Chọn ngành để xem chủ đề kiến thức, văn bản pháp luật, đề luyện và bộ đề theo tin tuyển dụng của đúng ngành đó.' ),
		'tinh'              => array( 'no' => '05', 'path' => 'tai-lieu/tinh', 'label' => 'Tài liệu theo tỉnh/thành', 'short' => 'Theo tỉnh/thành', 'hint' => 'Văn bản tuyển dụng, kế hoạch, nội dung riêng từng địa phương', 'icon' => 'ban-do',
			'h1' => 'Tài liệu theo tỉnh/thành', 'title' => 'Tài liệu ôn thi công chức, viên chức theo tỉnh/thành',
			'desc' => 'Văn bản tuyển dụng gốc (thông báo, kế hoạch, danh mục tài liệu ôn tập), tin đang mở và bộ đề theo tin của từng tỉnh, thành phố.',
			'lead' => 'Chỉ liệt kê tỉnh, thành đang có dữ liệu thật: tin đang nhận hồ sơ, văn bản tuyển dụng gốc, bộ đề theo tin và tài liệu riêng của địa phương.' ),
		'bo-cau-hoi'        => array( 'no' => '06', 'path' => 'tai-lieu/bo-cau-hoi', 'label' => 'Bộ câu hỏi và đáp án', 'short' => 'Bộ câu hỏi & đáp án', 'hint' => 'Trắc nghiệm, tự luận, tình huống nghiệp vụ', 'icon' => 'ngan-hang',
			'h1' => 'Bộ câu hỏi và đáp án ôn thi công chức, viên chức', 'title' => 'Bộ câu hỏi trắc nghiệm có đáp án ôn thi công chức, viên chức 2026',
			'desc' => 'Câu hỏi trắc nghiệm có đáp án, giải thích và căn cứ pháp luật theo chủ đề; bộ đề PDF có đáp án theo tin tuyển dụng; câu hỏi tự luận và tình huống nghiệp vụ.',
			'lead' => 'Mỗi câu có đáp án, giải thích và căn cứ pháp luật. Luyện trực tuyến theo chủ đề hoặc tải bộ đề PDF có đáp án.' ),
		'tom-tat-kien-thuc' => array( 'no' => '07', 'path' => 'tai-lieu/tom-tat-kien-thuc', 'label' => 'Tóm tắt kiến thức và sơ đồ tư duy', 'short' => 'Tóm tắt & sơ đồ tư duy', 'hint' => 'Bài giảng ngắn, mindmap, bảng so sánh', 'icon' => 'so-do',
			'h1' => 'Tóm tắt kiến thức và sơ đồ tư duy', 'title' => 'Tóm tắt kiến thức trọng tâm, sơ đồ tư duy ôn thi công chức, viên chức',
			'desc' => 'Tóm tắt kiến thức trọng tâm theo chủ đề và điều luật, sơ đồ tư duy, bảng so sánh và bài giảng ngắn cho người ôn thi công chức, viên chức.',
			'lead' => 'Kiến thức trọng tâm rút ra từ văn bản gốc, chia theo chủ đề, kèm câu hỏi luyện tập cho từng phần.' ),
		'giao-trinh'        => array( 'no' => '08', 'path' => 'tai-lieu/giao-trinh', 'label' => 'Giáo trình và sách tham khảo', 'short' => 'Giáo trình & sách', 'hint' => 'Giáo trình chuyên môn, tài liệu bồi dưỡng', 'icon' => 'sach',
			'h1' => 'Giáo trình và sách tham khảo', 'title' => 'Giáo trình, sách tham khảo ôn thi công chức, viên chức',
			'desc' => 'Giáo trình chuyên môn và tài liệu bồi dưỡng do Công Viên Chức biên soạn hoặc được phép sử dụng.',
			'lead' => 'Chỉ đăng giáo trình, sách do Công Viên Chức biên soạn hoặc được phép sử dụng.' ),
		'bieu-mau'          => array( 'no' => '09', 'path' => 'tai-lieu/bieu-mau', 'label' => 'Biểu mẫu và nghiệp vụ hành chính', 'short' => 'Biểu mẫu & nghiệp vụ', 'hint' => 'Hồ sơ dự tuyển, mẫu văn bản hành chính', 'icon' => 'bieu-mau',
			'h1' => 'Biểu mẫu và nghiệp vụ hành chính', 'title' => 'Biểu mẫu hồ sơ dự tuyển công chức, viên chức và mẫu văn bản hành chính',
			'desc' => 'Phiếu đăng ký dự tuyển, sơ yếu lý lịch, mẫu văn bản hành chính và văn bản quy định thể thức, nghiệp vụ văn phòng.',
			'lead' => 'Biểu mẫu hồ sơ dự tuyển và mẫu văn bản hành chính, tải về miễn phí.' ),
		'moi'               => array( 'no' => '10', 'path' => 'tai-lieu/moi', 'label' => 'Tài liệu mới', 'short' => 'Tài liệu mới', 'hint' => 'Vừa đăng, sửa đổi, bổ sung', 'icon' => 'sao',
			'h1' => 'Tài liệu mới cập nhật', 'title' => 'Tài liệu ôn thi công chức, viên chức mới cập nhật',
			'desc' => 'Tài liệu, văn bản pháp luật, bài kiến thức và bộ đề PDF mới đăng hoặc vừa cập nhật trên Công Viên Chức.',
			'lead' => 'Tài liệu, văn bản pháp luật, bài kiến thức và bộ đề vừa bổ sung - cập nhật hằng ngày.' ),
	);
}

function cvc_library_url( string $view, string $param = '' ): string {
	$v = cvc_library_views()[ $view ] ?? null;
	if ( ! $v ) {
		return cvc_documents_url();
	}

	return home_url( '/' . $v['path'] . '/' . ( '' !== $param ? rawurlencode( $param ) . '/' : '' ) );
}

/** Đọc API thư viện (cache 10 phút phía WP). */
function cvc_library_api( string $view, array $q = array() ): array {
	$res = function_exists( 'cvc_ci_api' ) ? cvc_ci_api( '/api/library/' . $view, array_filter( $q ), null, 12, 600 ) : array( 'ok' => false );

	return array(
		'ok'     => ! empty( $res['ok'] ),
		'status' => (int) ( $res['status'] ?? 0 ),
		'data'   => ! empty( $res['ok'] ) ? (array) ( $res['data']['data'] ?? array() ) : array(),
	);
}

/** Đăng ký URL - gọi từ đầu cvc_register_rewrite_rules() (phải đứng trước luật chi tiết /tai-lieu/{slug}/). */
function cvc_library_rewrite_rules(): void {
	foreach ( array( 'cong-chuc', 'vien-chuc', 'bo-cau-hoi', 'tom-tat-kien-thuc', 'giao-trinh', 'bieu-mau', 'moi', 'nganh', 'tinh' ) as $v ) {
		add_rewrite_rule( '^tai-lieu/' . $v . '/?$', 'index.php?cvc_page=library&cvc_lib_view=' . $v, 'top' );
	}
	add_rewrite_rule( '^tai-lieu/(nganh|tinh)/([a-z0-9-]{2,80})/?$', 'index.php?cvc_page=library&cvc_lib_view=$matches[1]&cvc_lib_param=$matches[2]', 'top' );
}

add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = 'cvc_lib_view';
		$vars[] = 'cvc_lib_param';
		return $vars;
	}
);

/** Hàng thẻ 10 mục (trang /tai-lieu/ và đầu mỗi trang thư viện). */
function cvc_library_render_nav( string $current = '', bool $cards = false ): void {
	$ov     = $cards ? cvc_library_api( 'overview' ) : array( 'data' => array() );
	$counts = (array) ( $ov['data']['counts'] ?? array() );
	$units  = array( 'cong-chuc' => 'mục', 'vien-chuc' => 'mục', 'van-ban-phap-luat' => 'văn bản', 'nganh' => 'ngành', 'tinh' => 'tỉnh, thành', 'bo-cau-hoi' => 'câu hỏi', 'tom-tat-kien-thuc' => 'mục', 'giao-trinh' => 'tài liệu', 'bieu-mau' => 'biểu mẫu', 'moi' => 'mục mới / 7 ngày' );
	?>
	<nav class="lb-nav<?php echo $cards ? ' is-cards' : ''; ?>" aria-label="Danh mục thư viện tài liệu">
		<?php foreach ( cvc_library_views() as $k => $v ) : ?>
			<?php
			$n   = isset( $counts[ $k ] ) ? (int) $counts[ $k ] : null;
			$on  = $k === $current;
			$url = ! empty( $v['external'] ) ? home_url( '/' . $v['path'] . '/' ) : cvc_library_url( $k );
			?>
			<a class="lb-nav-item<?php echo $on ? ' is-on' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>>
				<span class="lb-nav-ic" aria-hidden="true"><?php echo function_exists( 'cvc_nav_icon' ) ? cvc_nav_icon( (string) $v['icon'] ) : esc_html( $v['no'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="lb-nav-t"><b><?php echo esc_html( $cards ? $v['label'] : $v['short'] ); ?></b>
					<?php if ( $cards ) : ?><small><?php echo esc_html( $v['hint'] ); ?></small>
						<em><?php echo null === $n ? '' : ( 0 === $n ? 'Sắp có' : esc_html( number_format_i18n( $n ) . ' ' . $units[ $k ] ) ); ?></em>
					<?php endif; ?>
				</span>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
}
