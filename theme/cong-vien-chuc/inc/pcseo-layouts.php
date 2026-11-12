<?php
/**
 * BỐ CỤC + GIỌNG VĂN XOAY VÒNG CHO BÀI AI (12/11/2026, claude/ke-hoach-blog-index-cham.md - anh Nhật chốt:
 * "10 bài viết bằng AI liền nhau phải khác nhau hoàn toàn về cấu trúc, giọng văn").
 *
 * Plugin PCTech AI SEO (bản vá 0.13.1-cvc) gọi filter `pcseo_brief_layout` khi lập brief cho mỗi bài. Theme trả về
 * 1 trong 10 bố cục, mỗi bố cục có giọng văn riêng; bố cục đã dùng ở 9 bài gần nhất không được chọn lại
 * → 10 bài liền nhau luôn khác nhau cả cấu trúc lẫn giọng. Ưu tiên bố cục hợp với từ khóa (lịch thi → lộ trình,
 * so sánh → bảng đối chiếu...). Nhật ký: option cvc_pcseo_layout_log (job_id → bố cục).
 *
 * Bản vá plugin nằm ngoài kho theme (plugin tự cập nhật từ PCTech SEO Hub) → tắt tự cập nhật plugin cho tới khi
 * bản vá được gộp vào Hub (xem docs/pcseo-0.13.1-cvc-layouts.diff).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Không cho plugin tự cập nhật đè bản vá (bỏ khi Hub đã có filter pcseo_brief_layout). */
add_filter(
	'auto_update_plugin',
	static function ( $update, $item ) {
		return is_object( $item ) && isset( $item->plugin ) && 'pctech-ai-seo/pctech-ai-seo.php' === $item->plugin ? false : $update;
	},
	50,
	2
);

/** Quy tắc chung mọi bố cục: không bịa trải nghiệm / số liệu (bổ sung cho quy tắc của plugin). */
const CVC_LAYOUT_COMMON = 'Không bịa nhân vật, câu chuyện có thật, số liệu hay số hiệu văn bản; ví dụ tình huống phải nói rõ là giả định. Mỗi H2 vẫn chứa tự nhiên một từ khóa phụ (nếu được). Không dùng lại các cụm mở bài sáo rỗng ("Trong bối cảnh...", "Bài viết này sẽ...").';

/** @return array<string, array> 10 bố cục. */
function cvc_pcseo_layouts(): array {
	return array(
		'hoi-dap'     => array(
			'name'        => 'Hỏi nhanh đáp gọn',
			'match'       => '/(la gi|bao nhieu|khi nao|o dau|co duoc|co phai|nhu the nao|the nao)/',
			'voice'       => 'Biên tập viên tư vấn tuyển dụng: xưng "chúng tôi", gọi "bạn"; câu rất ngắn, đi thẳng vào ý, không rào đón; mỗi câu trả lời mở đầu bằng "Có", "Không" hoặc con số / mốc cụ thể nếu có; giọng điềm tĩnh, chuyên nghiệp.',
			'title'       => 'H1 ≤ 65 ký tự dạng CÂU HỎI người đọc hay gõ, chứa nguyên văn từ khóa chính (không bắt buộc đứng đầu), kết thúc bằng dấu "?"',
			'title_start' => false,
			'sections'    => '7–10 mục H2, MỖI H2 là một câu hỏi ngắn (≤ 12 từ); không H3.',
			'order'       => 'đoạn mở 2–3 câu trả lời thẳng câu hỏi ở tiêu đề → các H2 câu hỏi, mỗi mục: <p class="pcseo-answer"> trả lời 1–2 câu + 1 đoạn giải thích ngắn hoặc 1 danh sách → <h2>3 điều cần nhớ</h2> dạng <ol> 3 ý',
			'order_first' => 'đoạn mở 2–3 câu trả lời thẳng câu hỏi ở tiêu đề (không hộp tóm tắt)',
			'order_last'  => '<h2>3 điều cần nhớ</h2> dạng <ol> 3 ý (không FAQ, không "Kết luận")',
			'summary'     => false, 'answers' => true, 'faq' => false,
		),
		'tung-buoc'   => array(
			'name'        => 'Hướng dẫn từng bước',
			'match'       => '/(cach|huong dan|thu tuc|dang ky|nop ho so|quy trinh|lam sao)/',
			'voice'       => 'Người hướng dẫn thực hành: xưng "mình", gọi "bạn"; giọng ân cần, rõ ràng như đang ngồi cạnh làm cùng; dùng động từ mệnh lệnh nhẹ ("Mở…", "Kiểm tra…"); cuối mỗi bước có 1 câu "Dấu hiệu bạn đã làm đúng: …".',
			'title'       => 'H1 ≤ 65 ký tự BẮT ĐẦU bằng nguyên văn từ khóa chính, sau dấu ":" nêu số bước, ví dụ "…: 6 bước từ A đến Z"',
			'title_start' => true,
			'sections'    => 'H2 đầu "Cần chuẩn bị gì trước khi bắt đầu"; sau đó 4–7 H2 dạng "Bước N: <động từ> …"; mỗi bước dùng <ol class="pcseo-steps"> hoặc đoạn ngắn + 1 hộp mẹo / lưu ý.',
			'order'       => 'hộp Tóm tắt nhanh (dạng các bước rút gọn) → đoạn mở 50–80 từ → các H2 bước → <h2>Checklist trước khi nộp</h2> dạng <ul class="pcseo-check"> 6–10 mục → <h2>Câu hỏi thường gặp</h2> 3 câu',
			'order_first' => 'hộp Tóm tắt nhanh (các bước rút gọn) → đoạn mở 50–80 từ',
			'order_last'  => '<h2>Checklist trước khi nộp</h2> dạng <ul class="pcseo-check"> 6–10 mục → <h2>Câu hỏi thường gặp</h2> 3 câu <h3> + trả lời <p>',
			'summary'     => true, 'answers' => false, 'faq' => true,
		),
		'boc-tach'    => array(
			'name'        => 'Bóc tách quy định',
			'match'       => '/(luat|nghi dinh|thong tu|quy dinh|dieu kien|tieu chuan|che do)/',
			'voice'       => 'Chuyên viên pháp chế trung lập: ngôi thứ ba ("người dự tuyển", "thí sinh"), không xưng "mình"; câu chuẩn mực, chính xác từng chữ; phân biệt rõ "quy định nói gì" và "thực tế thường áp dụng ra sao"; không cảm thán.',
			'title'       => 'H1 ≤ 65 ký tự BẮT ĐẦU bằng nguyên văn từ khóa chính, phần sau ":" dạng "quy định và cách hiểu đúng" (diễn đạt khác đi, không chép mẫu)',
			'title_start' => true,
			'sections'    => '4–6 H2 theo trình tự: căn cứ / phạm vi áp dụng → nội dung chính (có <blockquote> trích nguyên văn CHỈ khi văn bản có trong danh sách trang nội bộ) → các trường hợp hay hiểu sai → áp dụng vào hồ sơ của bạn; ít nhất 1 <table> đối chiếu và 1 <dl> thuật ngữ.',
			'order'       => 'đoạn mở 60–90 từ nêu vấn đề pháp lý → hộp <div class="pcseo-callout pcseo-legal"> căn cứ pháp lý → các H2 → <h2>Câu hỏi thường gặp</h2> 3–4 câu → đoạn kết 2–3 câu (không đặt tiêu đề "Kết luận")',
			'order_first' => 'đoạn mở 60–90 từ nêu vấn đề pháp lý → hộp căn cứ pháp lý (pcseo-legal)',
			'order_last'  => '<h2>Câu hỏi thường gặp</h2> 3–4 câu → đoạn kết 2–3 câu không có tiêu đề',
			'summary'     => false, 'answers' => true, 'faq' => true,
		),
		'sai-lam'     => array(
			'name'        => 'Sai lầm thường gặp',
			'match'       => '/(sai|loi|truot|kinh nghiem|meo|luu y|tranh)/',
			'voice'       => 'Người đồng hành thẳng thắn: xưng "mình", gọi "bạn"; giọng cảnh báo nhưng không dọa, hơi gắt ở chỗ cần ("Đừng…", "Sai ở chỗ…"); mỗi sai lầm có "Vì sao sai" và "Làm thế này thay vào".',
			'title'       => 'H1 ≤ 65 ký tự dạng "N sai lầm khi <từ khóa chính> …" hoặc "<từ khóa chính>: N lỗi khiến …" — có con số, chứa nguyên văn từ khóa chính',
			'title_start' => false,
			'sections'    => '5–8 H2 dạng "Sai lầm N: …"; mỗi mục: 1 đoạn mô tả, hộp <div class="pcseo-callout pcseo-mistake"> hoặc pcseo-note, rồi hộp pcseo-tip cách sửa.',
			'order'       => 'đoạn mở bằng một tình huống điển hình (nói rõ là tình huống hay gặp, không phải chuyện có thật) 60–90 từ → các H2 sai lầm → <h2>Cách tránh trong 5 phút</h2> dạng <ul class="pcseo-check">',
			'order_first' => 'đoạn mở bằng một tình huống điển hình (nói rõ là tình huống hay gặp) 60–90 từ, không hộp tóm tắt',
			'order_last'  => '<h2>Cách tránh trong 5 phút</h2> dạng <ul class="pcseo-check"> (không FAQ, không "Kết luận")',
			'summary'     => false, 'answers' => false, 'faq' => false,
		),
		'doi-chieu'   => array(
			'name'        => 'Bảng đối chiếu',
			'match'       => '/(so sanh|khac nhau|giong|nen chon|hay|hoac|cong chuc va vien chuc|thi tuyen va xet tuyen)/',
			'voice'       => 'Người phân tích dữ liệu: xưng "chúng ta"; câu ngắn, nhiều mốc và tiêu chí; hay dùng cấu trúc "Nếu… thì…"; không cảm xúc, không khen chê.',
			'title'       => 'H1 ≤ 65 ký tự chứa nguyên văn từ khóa chính, dạng so sánh / lựa chọn ("… và …: khác nhau ở đâu?", "… nên chọn …?")',
			'title_start' => false,
			'sections'    => 'H2 đầu là BẢNG ĐỐI CHIẾU tổng quan (<table> 3–4 cột, 6–10 dòng tiêu chí); sau đó 3–5 H2, mỗi H2 phân tích một tiêu chí; cuối là H2 "Nên chọn thế nào" theo từng trường hợp người đọc.',
			'order'       => 'đoạn mở 40–70 từ → các H2 (bảng tổng quan trước) → <h2>Câu hỏi thường gặp</h2> 2–3 câu',
			'order_first' => 'đoạn mở 40–70 từ (không hộp tóm tắt)',
			'order_last'  => '<h2>Câu hỏi thường gặp</h2> 2–3 câu <h3> + trả lời <p> (không "Kết luận")',
			'summary'     => false, 'answers' => false, 'faq' => true,
		),
		'lo-trinh'    => array(
			'name'        => 'Lộ trình theo mốc thời gian',
			'match'       => '/(lich thi|lo trinh|ke hoach|on thi|thoi gian|bao lau|truoc ngay thi|chuan bi)/',
			'voice'       => 'Huấn luyện viên năng lượng: xưng "mình", gọi "bạn"; câu ngắn, nhịp nhanh, động viên vừa phải; dùng mốc thời gian làm xương sống ("Còn 30 ngày", "Tuần cuối"); mỗi mốc có việc làm cụ thể đo được.',
			'title'       => 'H1 ≤ 65 ký tự chứa nguyên văn từ khóa chính, nhấn mạnh thời gian / lộ trình ("Lộ trình …", "… trong 30 ngày", "… theo từng tuần")',
			'title_start' => false,
			'sections'    => '4–6 H2 là các mốc thời gian theo thứ tự (ví dụ "Trước 60 ngày", "30 ngày", "2 tuần cuối", "Ngày thi"); trong mục dùng <ul class="pcseo-check"> việc cần xong; có 1 <table> lịch tuần.',
			'order'       => 'hộp "Bạn sẽ có gì sau lộ trình này" (dùng hộp <div class="pcseo-summary">, tiêu đề hộp đổi thành "🎯 Bạn sẽ có") → đoạn mở 50–80 từ → các H2 mốc → <h2>Lịch mẫu 1 tuần</h2> dạng <table> → đoạn chốt 2 câu',
			'order_first' => 'hộp "🎯 Bạn sẽ có" (pcseo-summary) → đoạn mở 50–80 từ',
			'order_last'  => '<h2>Lịch mẫu 1 tuần</h2> dạng <table> → đoạn chốt 2 câu (không FAQ)',
			'summary'     => true, 'answers' => false, 'faq' => false,
		),
		'giai-thich'  => array(
			'name'        => 'Tin giải thích kiểu báo chí',
			'match'       => '/(thong bao|tuyen dung|chi tieu|2026|2027|moi nhat|ket qua|danh sach)/',
			'voice'       => 'Nhà báo giáo dục: ngôi thứ ba, khách quan; câu đầu theo kiểu "sapo" trả lời ai – cái gì – khi nào – ở đâu; đoạn văn 2–3 câu; không xưng "mình", không gọi "bạn" trừ phần cuối.',
			'title'       => 'H1 ≤ 65 ký tự kiểu tít báo chí, chứa nguyên văn từ khóa chính, có thông tin mới / năm (không giật tít, không dấu "!")',
			'title_start' => false,
			'sections'    => '4–6 H2 là các mệnh đề khẳng định (KHÔNG dạng câu hỏi), đi từ thông tin quan trọng nhất → bối cảnh → chi tiết → điều cần theo dõi; có 1 <blockquote> nêu quy định liên quan (chỉ khi văn bản có trong danh sách nội bộ, không thì dùng hộp pcseo-note).',
			'order'       => 'đoạn sapo in đậm toàn bộ (<p><strong>…</strong></p>) 40–60 từ → các H2 → <h2>Điều này có ý nghĩa gì với bạn</h2> 2–3 đoạn ngắn',
			'order_first' => 'đoạn sapo in đậm (<p><strong>…</strong></p>) 40–60 từ',
			'order_last'  => '<h2>Điều này có ý nghĩa gì với bạn</h2> 2–3 đoạn ngắn (không FAQ, không "Kết luận")',
			'summary'     => false, 'answers' => false, 'faq' => false,
		),
		'checklist'   => array(
			'name'        => 'Danh sách kiểm tra',
			'match'       => '/(ho so|giay to|can gi|chuan bi|mau|thanh phan|phieu)/',
			'voice'       => 'Chuyên viên tổ chức cán bộ tỉ mỉ: xưng "chúng tôi", gọi "người dự tuyển"; câu kiểu biên bản, rõ từng mục; mỗi mục ghi "Ai cấp / Lưu ý định dạng / Lỗi hay bị trả hồ sơ".',
			'title'       => 'H1 ≤ 65 ký tự BẮT ĐẦU bằng nguyên văn từ khóa chính, sau ":" có từ "danh sách" hoặc "checklist" và số mục',
			'title_start' => true,
			'sections'    => '3–5 H2 là các nhóm giấy tờ / việc cần làm; mỗi H2 có <ul class="pcseo-check"> và 1 <table> (cột: Mục – Nơi cấp / cách làm – Lưu ý) khi phù hợp.',
			'order'       => 'đoạn mở 1–2 câu → hộp <div class="pcseo-summary"> (tiêu đề hộp "✅ Checklist rút gọn") 6–10 mục → các H2 nhóm → <h2>Tự kiểm tra: bạn đã sẵn sàng chưa?</h2> 5 câu hỏi có / không dạng <ol>',
			'order_first' => 'đoạn mở 1–2 câu → hộp "✅ Checklist rút gọn" (pcseo-summary) 6–10 mục',
			'order_last'  => '<h2>Tự kiểm tra: bạn đã sẵn sàng chưa?</h2> 5 câu hỏi có / không dạng <ol> (không FAQ)',
			'summary'     => true, 'answers' => false, 'faq' => false,
		),
		'tinh-huong'  => array(
			'name'        => 'Đi theo một tình huống giả định',
			'match'       => '/(truong hop|neu|co duoc|chua co|khong co|tre|mien|uu tien|cong diem)/',
			'voice'       => 'Người kể chuyện ấm áp: xưng "mình", gọi "bạn"; dẫn dắt bằng một nhân vật GIẢ ĐỊNH (nói rõ "Giả sử…"), mỗi mục là một quyết định của nhân vật; xen câu hỏi tu từ; kết bằng lời khuyên cá nhân hóa.',
			'title'       => 'H1 ≤ 65 ký tự chứa nguyên văn từ khóa chính, dạng điều kiện "Nếu bạn …" hoặc "… thì xử lý thế nào"',
			'title_start' => false,
			'sections'    => '4–6 H2 là các ngã rẽ / quyết định trong tình huống giả định; mỗi mục có 1 hộp <div class="pcseo-callout pcseo-example"> ví dụ và 1 câu "Áp dụng cho bạn".',
			'order'       => 'đoạn mở giới thiệu tình huống giả định 70–100 từ → các H2 → <h2>Câu hỏi thường gặp</h2> 3 câu → đoạn chốt 2–3 câu "Rút ra cho trường hợp của bạn" (không tiêu đề "Kết luận")',
			'order_first' => 'đoạn mở giới thiệu tình huống giả định 70–100 từ (không hộp tóm tắt)',
			'order_last'  => '<h2>Câu hỏi thường gặp</h2> 3 câu → đoạn chốt 2–3 câu (không tiêu đề "Kết luận")',
			'summary'     => false, 'answers' => false, 'faq' => true,
		),
		'hieu-lam'    => array(
			'name'        => 'Hiểu lầm và sự thật',
			'match'       => '/(co that|dung khong|hieu lam|that ra|su that|bat buoc|phai)/',
			'voice'       => 'Người bạn hiểu luật, dí dỏm nhẹ nhàng: xưng "mình", gọi "bạn"; mỗi hiểu lầm mở bằng câu người ta hay nói (đặt trong ngoặc kép), rồi "Sự thật là…"; vui nhưng chính xác, không mỉa mai.',
			'title'       => 'H1 ≤ 65 ký tự chứa nguyên văn từ khóa chính, dạng "N hiểu lầm về …" hoặc "… : hiểu lầm và sự thật"',
			'title_start' => false,
			'sections'    => '5–7 H2 dạng "Hiểu lầm N: \"…\""; mỗi mục: đoạn "Sự thật là…", 1 hộp pcseo-important hoặc pcseo-tip.',
			'order'       => 'đoạn mở 50–80 từ → các H2 hiểu lầm → <h2>Tóm lại</h2> bảng 2 cột (Hiểu lầm | Sự thật) → <h2>Câu hỏi thường gặp</h2> 2 câu',
			'order_first' => 'đoạn mở 50–80 từ (không hộp tóm tắt)',
			'order_last'  => '<h2>Tóm lại</h2> bảng 2 cột (Hiểu lầm | Sự thật) → <h2>Câu hỏi thường gặp</h2> 2 câu',
			'summary'     => false, 'answers' => false, 'faq' => true,
		),
	);
}

/** Chọn bố cục cho 1 bài: chưa dùng ở 9 bài gần nhất, ưu tiên bố cục hợp từ khóa. */
function cvc_pcseo_pick_layout( $layout, string $keyword, int $cluster_id = 0, int $job_id = 0 ) {
	$all = cvc_pcseo_layouts();
	$log = get_option( 'cvc_pcseo_layout_log', array() );
	$log = is_array( $log ) ? $log : array();
	if ( $job_id && isset( $log[ $job_id ] ) && isset( $all[ $log[ $job_id ] ] ) ) {
		$id = $log[ $job_id ]; // lập lại brief của cùng bài → giữ bố cục cũ.
	} else {
		$recent = array_slice( array_values( $log ), -9 );
		$free   = array_diff( array_keys( $all ), $recent );
		$free   = $free ? $free : array_keys( $all );
		$kw     = ' ' . ( function_exists( 'cvc_blog_ascii' ) ? cvc_blog_ascii( $keyword ) : strtolower( remove_accents( $keyword ) ) ) . ' ';
		$fit    = array_values( array_filter( $free, static fn ( $k ) => (bool) preg_match( $all[ $k ]['match'], $kw ) ) );
		$pool   = $fit ? $fit : array_values( $free );
		$id     = $pool[ array_rand( $pool ) ];
		$log[ $job_id ?: 'k' . time() ] = $id;
		update_option( 'cvc_pcseo_layout_log', array_slice( $log, -60, null, true ), false );
	}
	$l       = $all[ $id ];
	$prompt  = 'Thứ tự: ' . $l['order'] . "\nMục H2: " . $l['sections'] . "\nTiêu đề: " . $l['title'] . ".\n" . CVC_LAYOUT_COMMON;
	unset( $l['match'] );

	return $l + array( 'id' => $id, 'prompt' => $prompt );
}
add_filter( 'pcseo_brief_layout', 'cvc_pcseo_pick_layout', 10, 4 );
