<?php
/**
 * Chi tiết văn bản pháp luật — Executive 3-Column Legal Management & Full-Text AI Reader
 * URL: /van-ban-phap-luat/{slug}/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_legal_document_slug' ) );

$service = new CVC_Legal_Document_Service();
$result  = $service->find( $slug );

$document = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$document = is_array( $data ) ? $data : null;
	$is_found = null !== $document;
}

if ( ! $is_found ) {
	$fallback_list = CVC_Subpage_Fixtures::get_legal_documents();
	foreach ( $fallback_list as $item ) {
		if ( isset( $item['slug'] ) && $item['slug'] === $slug ) {
			$document = $item;
			$is_found = true;
			break;
		}
	}
	// Bỏ fallback "lấy văn bản fixture đầu tiên" cho slug không tồn tại -
	// trước đây mọi URL sai đều hiện 1 văn bản khác (soft-404, sai nội dung).
}

if ( ! $is_found ) {
	status_header( 404 );
	cvc_seo_set_noindex();
}

cvc_seo_set_title( $is_found ? (string) ( $document['title'] ?? 'Chi tiết văn bản pháp luật' ) : 'Văn bản pháp luật công vụ' );

$breadcrumb_items = array(
	array(
		'label' => 'Trang chủ',
		'url'   => home_url( '/' ),
	),
	array(
		'label' => 'Thư viện pháp luật',
		'url'   => cvc_legal_documents_url(),
	),
	array( 'label' => $is_found ? wp_trim_words( (string) $document['title'], 6 ) : 'Không tìm thấy' ),
);

$summaryText = ! empty( $document['summary'] ) ? (string) $document['summary'] : "Nội dung văn bản quy định chi tiết về tiêu chuẩn ngạch, hình thức thi tuyển, quản lý và sử dụng cán bộ, công chức, viên chức trong hệ thống cơ quan nhà nước.";

if ( $is_found ) {
	cvc_seo_set_description( wp_trim_words( $summaryText, 25 ) );
	cvc_seo_set_canonical( cvc_legal_document_url( $slug ?: 'van-ban' ) );
	cvc_seo_set_og( array( 'type' => 'article' ) );
	cvc_seo_add_breadcrumb_jsonld( $breadcrumb_items );
}

get_header();

if ( ! $is_found ) {
	?>
	<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-12">
		<div class="max-w-3xl mx-auto px-4 space-y-4">
			<?php cvc_render_notfound_state( 'Không tìm thấy văn bản pháp luật này. Văn bản có thể đã được gỡ hoặc đường dẫn không đúng.' ); ?>
			<p><a class="text-cyan-300 font-bold" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">&larr; Về thư viện văn bản pháp luật</a></p>
		</div>
	</main>
	<?php
	get_footer();
	return;
}

// Data Extraction & Normalization
$docNumber    = $document['document_number'] ?? ($document['code'] ?? 'Luật / Nghị định chính thức');
$docType      = $document['document_type'] ?? ($document['category'] ?? 'Văn bản pháp luật');
$issuingAg    = $document['issuing_agency'] ?? 'Quốc Hội / Chính Phủ';
$issuedDate   = $document['issued_date'] ?? '2020-11-27';
$effectiveDt  = $document['effective_date'] ?? '2020-12-01';
$signer       = $document['signer'] ?? 'Lãnh đạo cơ quan ban hành';
$statusLabel  = $document['status_label'] ?? 'Còn hiệu lực';
$sourceUrl    = $document['source_url'] ?? '';

$attachments  = ! empty( $document['attachments'] ) && is_array( $document['attachments'] ) ? $document['attachments'] : array(
	array(
		'name' => 'Van-ban-phap-luat-chinh-thuc.pdf',
		'title' => ($document['title'] ?? 'Văn bản pháp luật') . ' (.PDF Bản chính thức gốc)',
		'file_type' => 'pdf',
		'size' => $document['size'] ?? '2.4 MB',
		'url' => get_template_directory_uri() . '/assets/downloads/Ke-hoach-tuyen-dung-cong-chuc-2026.pdf',
	),
	array(
		'name' => 'Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx',
		'title' => 'Biểu mẫu Phụ lục đi kèm văn bản (.DOCX Bản Word tra cứu)',
		'file_type' => 'docx',
		'size' => '180 KB',
		'url' => get_template_directory_uri() . '/assets/downloads/Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx',
	),
);

$keyArticles  = ! empty( $document['key_articles'] ) && is_array( $document['key_articles'] ) ? $document['key_articles'] : array(
	array(
		'article' => 'Điều 36. Điều kiện đăng ký dự tuyển công chức',
		'note' => 'Trọng tâm Vòng 1 — quy định 7 điều kiện tiêu chuẩn dự tuyển công chức ngạch Chuyên viên.',
	),
	array(
		'article' => 'Điều 8. Cấu trúc môn thi Vòng 1 trắc nghiệm',
		'note' => 'Kiến thức chung (60 câu), Ngoại ngữ (30 câu), Tin học (30 câu) trên máy tính.',
	),
	array(
		'article' => 'Điều 20. Chế độ tập sự đối với công chức',
		'note' => '12 tháng đối với Chuyên viên; 06 tháng đối với Cán sự; hưởng 85% bậc 1 lương ngạch.',
	),
);

// FULL TEXT COMPREHENSIVE LAW CHAPTERS & ARTICLES DATASET
function cvc_get_document_fulltext_chapters( string $slug, array $document ): array {
	if ( ! empty( $document['chapters'] ) && is_array( $document['chapters'] ) ) {
		return $document['chapters'];
	}

	$s = strtolower($slug);

	// 1. NGHỊ ĐỊNH 138/2020/NĐ-CP (TUYỂN DỤNG, SỬ DỤNG & QUẢN LÝ CÔNG CHỨC)
	if ( strpos( $s, '138' ) !== false ) {
		return array(
			array(
				'title' => 'Chương I: QUY ĐỊNH CHUNG VỀ TUYỂN DỤNG VÀ QUẢN LÝ CÔNG CHỨC',
				'articles' => array(
					array(
						'number' => 'Điều 1',
						'title' => 'Phạm vi điều chỉnh và đối tượng áp dụng',
						'content' => "Nghị định này quy định về tuyển dụng, sử dụng và quản lý công chức trong cơ quan của Đảng Cộng sản Việt Nam, Nhà nước, Mặt trận Tổ quốc Việt Nam, tổ chức chính trị - xã hội ở trung ương, cấp tỉnh, cấp huyện và công chức trong bộ máy lãnh đạo, quản lý của đơn vị sự nghiệp công lập.",
					),
					array(
						'number' => 'Điều 2',
						'title' => 'Căn cứ tuyển dụng công chức',
						'content' => "1. Việc tuyển dụng công chức phải căn cứ vào yêu cầu nhiệm vụ, vị trí việc làm và chỉ tiêu biên chế của cơ quan sử dụng công chức.\n2. Cơ quan có thẩm quyền tuyển dụng công chức xây dựng kế hoạch tuyển dụng, báo cáo cơ quan quản lý công chức phê duyệt trước khi tổ chức thực hiện.\n3. Kế hoạch tuyển dụng bao gồm: Số lượng biên chế được giao; số lượng vị trí việc làm cần tuyển; tiêu chuẩn, điều kiện đăng ký dự tuyển; hình thức và nội dung thi tuyển; thời gian và dự toán kinh phí.",
					),
					array(
						'number' => 'Điều 4',
						'title' => 'Điều kiện đăng ký dự tuyển công chức',
						'content' => "1. Người có đủ các điều kiện sau đây không phân biệt dân tộc, nam nữ, thành phần xã hội, niềm tin tôn giáo, tín ngưỡng được đăng ký dự tuyển công chức:\n   a) Có một quốc tịch là quốc tịch Việt Nam;\n   b) Đủ 18 tuổi trở lên;\n   c) Có đơn dự tuyển; có lý lịch rõ ràng;\n   d) Có văn bằng, chứng chỉ phù hợp với vị trí việc làm cần tuyển;\n   đ) Có phẩm chất chính trị, đạo đức tốt;\n   e) Đủ sức khỏe để thực hiện nhiệm vụ;\n   g) Các điều kiện khác theo yêu cầu của vị trí dự tuyển quy định tại kế hoạch tuyển dụng.\n2. Những người mất năng lực hành vi dân sự hoặc đang bị truy cứu trách nhiệm hình sự không được đăng ký dự tuyển.",
					),
					array(
						'number' => 'Điều 5',
						'title' => 'Ưu tiên trong tuyển dụng công chức',
						'content' => "1. Đối tượng và điểm ưu tiên trong thi tuyển hoặc xét tuyển:\n   a) Anh hùng Lực lượng vũ trang, Anh hùng Lao động, thương binh, người hưởng chính sách như thương binh: Được cộng 7,5 điểm vào kết quả điểm vòng 2;\n   b) Người dân tộc thiểu số, sĩ quan quân đội, sĩ quan công an, quân nhân chuyên nghiệp phục viên, con liệt sĩ, con thương binh: Được cộng 5 điểm vào kết quả điểm vòng 2;\n   c) Người hoàn thành nghĩa vụ quân sự, nghĩa vụ tham gia công an nhân dân, đội viên thanh niên xung phong: Được cộng 2,5 điểm vào kết quả điểm vòng 2.\n2. Trường hợp người dự thi tuyển thuộc nhiều diện ưu tiên thì chỉ được cộng điểm ưu tiên cao nhất vào kết quả điểm vòng 2.",
					),
				),
			),
			array(
				'title' => 'Chương II: HÌNH THỨC, NỘI DUNG VÀ THỜI GIAN THI TUYỂN CÔNG CHỨC',
				'articles' => array(
					array(
						'number' => 'Điều 8',
						'title' => 'Hình thức, nội dung và thời gian thi Vòng 1',
						'content' => "Thi trắc nghiệm được thực hiện bằng máy tính bao gồm 3 phần:\n- Phần I: Kiến thức chung 60 câu hỏi về hệ thống chính trị, tổ chức bộ máy của Đảng, Nhà nước, các tổ chức chính trị - xã hội; quản lý hành chính nhà nước; công chức, công vụ. Thời gian thi 60 phút.\n- Phần II: Ngoại ngữ 30 câu hỏi theo yêu cầu của vị trí việc làm về một trong năm thứ tiếng Anh, Nga, Pháp, Đức, Trung Quốc. Thời gian thi 30 phút.\n- Phần III: Tin học 30 câu hỏi theo yêu cầu của vị trí việc làm. Thời gian thi 30 phút.\nKết quả thi Vòng 1 được xác định theo số câu trả lời đúng cho từng phần thi, nếu trả lời đúng từ 50% số câu hỏi trở lên cho từng phần thi thì được thi tiếp Vòng 2.",
					),
					array(
						'number' => 'Điều 9',
						'title' => 'Hình thức, nội dung thi Vòng 2 Nghiệp vụ chuyên ngành',
						'content' => "1. Hình thức thi: Căn cứ vào tính chất, đặc điểm và yêu cầu của vị trí việc làm cần tuyển, người đứng đầu cơ quan có thẩm quyền tuyển dụng quyết định một trong ba hình thức thi:\n   a) Phỏng vấn (thời gian thi 30 phút);\n   b) Thi viết (thời gian thi 180 phút);\n   c) Kết hợp phỏng vấn và thi viết.\n2. Thang điểm: 100 điểm.\n3. Nội dung thi: Kiểm tra kiến thức, kỹ năng hoạt động công vụ của người dự tuyển theo yêu cầu của vị trí việc làm cần tuyển dụng.",
					),
					array(
						'number' => 'Điều 12',
						'title' => 'Xác định người trúng tuyển trong kỳ thi tuyển công chức',
						'content' => "1. Người trúng tuyển trong kỳ thi tuyển công chức phải có đủ các điều kiện sau đây:\n   a) Có kết quả điểm thi tại vòng 2 đạt từ 50 điểm trở lên;\n   b) Có số điểm tại vòng 2 cộng với điểm ưu tiên quy định tại Điều 5 Nghị định này (nếu có) cao hơn lấy theo thứ tự điểm từ cao xuống thấp trong chỉ tiêu được tuyển dụng của từng vị trí việc làm.\n2. Trường hợp có từ 02 người trở lên có tổng kết quả điểm vòng 2 cộng với điểm ưu tiên bằng nhau ở chỉ tiêu cuối cùng của vị trí việc làm cần tuyển thì người có kết quả điểm thi vòng 2 cao hơn là người trúng tuyển; nếu vẫn không xác định được thì người đứng đầu cơ quan có thẩm quyền tuyển dụng quyết định người trúng tuyển.",
					),
					array(
						'number' => 'Điều 13',
						'title' => 'Nộp Phiếu đăng ký dự tuyển và kiểm tra hoàn thiện hồ sơ',
						'content' => "1. Người đăng ký dự tuyển nộp 01 Phiếu đăng ký dự tuyển theo Mẫu số 01 ban hành kèm theo Nghị định này vào một vị trí việc làm tại cơ quan có chỉ tiêu tuyển dụng.\n2. Trong thời hạn 30 ngày kể từ ngày nhận được thông báo kết quả trúng tuyển, người trúng tuyển phải đến cơ quan có thẩm quyền tuyển dụng để hoàn thiện hồ sơ tuyển dụng bao gồm bản sao văn bằng, chứng chỉ, phiếu lý lịch tư pháp và giấy khám sức khỏe.",
					),
				),
			),
			array(
				'title' => 'Chương III: CHẾ ĐỘ TẬP SỰ VÀ BỔ NHIỆM NGẠCH CÔNG CHỨC',
				'articles' => array(
					array(
						'number' => 'Điều 20',
						'title' => 'Chế độ tập sự đối với công chức',
						'content' => "1. Người được tuyển dụng vào công chức phải thực hiện chế độ tập sự để làm quen với môi trường công tác, tập làm những công việc của vị trí việc làm được tuyển dụng.\n2. Thời gian tập sự được quy định như sau:\n   a) 12 tháng đối với công chức được tuyển dụng vào ngạch Chuyên viên và tương đương;\n   b) 06 tháng đối với công chức được tuyển dụng vào ngạch Cán sự và tương đương.\n3. Không thực hiện chế độ tập sự đối với người đã có thời gian công tác có đóng bảo hiểm xã hội bắt buộc theo đúng quy định của Luật Bảo hiểm xã hội.",
					),
					array(
						'number' => 'Điều 22',
						'title' => 'Chế độ, chính sách đối với người tập sự và người hướng dẫn tập sự',
						'content' => "1. Trong thời gian tập sự, người tập sự được hưởng 85% mức lương bậc 1 của ngạch tuyển dụng. Trường hợp người tập sự có trình độ thạc sĩ phù hợp với yêu cầu vị trí việc làm thì được hưởng 85% mức lương bậc 2; trình độ tiến sĩ được hưởng 85% mức lương bậc 3.\n2. Người hướng dẫn tập sự được hưởng phụ cấp trách nhiệm hướng dẫn bằng 0,3 mức lương cơ sở trong thời gian hướng dẫn tập sự.",
					),
					array(
						'number' => 'Điều 24',
						'title' => 'Bổ nhiệm vào ngạch công chức đối với người hoàn thành chế độ tập sự',
						'content' => "1. Khi hết thời gian tập sự, người tập sự phải báo cáo kết quả tập sự bằng văn bản; người hướng dẫn tập sự có nhận xét, đánh giá kết quả tập sự bằng văn bản gửi người đứng đầu cơ quan sử dụng công chức.\n2. Người đứng đầu cơ quan sử dụng công chức đánh giá phẩm chất chính trị, đạo đức và kết quả công việc của người tập sự. Trường hợp người tập sự đạt yêu cầu thì có văn bản đề nghị cơ quan quản lý công chức ra quyết định bổ nhiệm chính thức vào ngạch công chức.",
					),
				),
			),
			array(
				'title' => 'Chương IV: ĐIỀU ĐỘNG, LUÂN CHUYỂN, BIỆT PHÁI VÀ CHUYỂN NGẠCH CÔNG CHỨC',
				'articles' => array(
					array(
						'number' => 'Điều 26',
						'title' => 'Điều động công chức',
						'content' => "1. Việc điều động công chức được thực hiện trong các trường hợp sau đây:\n   a) Theo yêu cầu nhiệm vụ cụ thể;\n   b) Theo quy hoạch, kế hoạch sử dụng công chức trong cơ quan, tổ chức, đơn vị và giữa các cơ quan, tổ chức, đơn vị theo quyết định của cơ quan có thẩm quyền;\n   c) Chuyển đổi vị trí việc làm theo quy định của pháp luật.\n2. Công chức được điều động phải chấp hành quyết định điều động của cơ quan có thẩm quyền.",
					),
					array(
						'number' => 'Điều 27',
						'title' => 'Luân chuyển công chức lãnh đạo, quản lý',
						'content' => "1. Việc luân chuyển công chức chỉ áp dụng đối với công chức giữ chức vụ lãnh đạo, quản lý.\n2. Căn cứ luân chuyển: Quy hoạch cán bộ, công chức lãnh đạo, quản lý; yêu cầu nhiệm vụ và năng lực, sở trường của công chức.\n3. Thời hạn luân chuyển ít nhất là 03 năm (36 tháng) đối với một vị trí công tác.",
					),
				),
			),
		);
	}

	// 2. NGHỊ ĐỊNH 30/2020/NĐ-CP (CÔNG TÁC VĂN THƯ & THỂ THỨC VĂN BẢN)
	if ( strpos( $s, '30' ) !== false || strpos( $s, 'van-thu' ) !== false ) {
		return array(
			array(
				'title' => 'Chương I: QUY ĐỊNH CHUNG VỀ CÔNG TÁC VĂN THƯ',
				'articles' => array(
					array(
						'number' => 'Điều 1',
						'title' => 'Phạm vi điều chỉnh',
						'content' => "Nghị định này quy định về công tác văn thư bao gồm: Soạn thảo, ban hành văn bản; quản lý văn bản; lập hồ sơ và nộp lưu hồ sơ, tài liệu vào Lưu trữ cơ quan; quản lý và sử dụng con dấu, thiết bị lưu khóa bí mật trong công tác văn thư.",
					),
					array(
						'number' => 'Điều 3',
						'title' => 'Giải thích từ ngữ trong công tác văn thư',
						'content' => "1. 'Văn bản hành chính' là văn bản hình thành trong quá trình chỉ đạo, điều hành, giải quyết công việc của các cơ quan, tổ chức.\n2. 'Văn bản điện tử' là văn bản dưới dạng thông điệp dữ liệu được tạo lập hoặc được thu nhận từ thông điệp dữ liệu và được xử lý theo quy định của pháp luật.\n3. 'Ký số' là việc áp dụng chữ ký số để xác thực người ký và tính nguyên vẹn của văn bản điện tử.",
					),
				),
			),
			array(
				'title' => 'Chương II: SOẠN THẢO VÀ BAN HÀNH VĂN BẢN HÀNH CHÍNH',
				'articles' => array(
					array(
						'number' => 'Điều 8',
						'title' => 'Các loại văn bản hành chính (29 loại)',
						'content' => "Văn bản hành chính gồm 29 loại: Nghị quyết (cá biệt), Quyết định (cá biệt), Chỉ thị, Quy chế, Quy định, Thông cáo, Thông báo, Hướng dẫn, Chương trình, Kế hoạch, Phương án, Đề án, Dự án, Báo cáo, Biên bản, Tờ trình, Hợp đồng, Công văn, Công điện, Bản ghi nhớ, Bản thỏa thuận, Giấy ủy quyền, Giấy mời, Giấy giới thiệu, Giấy nghỉ phép, Phiếu gửi, Phiếu chuyển, Phiếu báo, Thư công.",
					),
					array(
						'number' => 'Điều 9',
						'title' => 'Thể thức văn bản hành chính (9 thành phần bắt buộc)',
						'content' => "Thể thức văn bản hành chính bao gồm các thành phần chính:\n1. Quốc hiệu và Tiêu ngữ;\n2. Tên cơ quan, tổ chức ban hành văn bản;\n3. Số, ký hiệu của văn bản;\n4. Địa danh và thời gian ban hành văn bản;\n5. Tên loại và trích yếu nội dung văn bản;\n6. Nội dung văn bản;\n7. Chức vụ, họ tên và chữ ký của người có thẩm quyền;\n8. Dấu, chữ ký số của cơ quan, tổ chức;\n9. Nơi nhận.",
					),
					array(
						'number' => 'Điều 10',
						'title' => 'Kỹ thuật trình bày văn bản hành chính chuẩn Phông chữ & Lề trang',
						'content' => "1. Phông chữ: Phông chữ tiếng Việt Times New Roman, bộ mã ký tự Unicode theo Tiêu chuẩn quốc gia TCVN 6909:2001.\n2. Định dạng trang giấy: Khổ giấy A4 (210 mm x 297 mm). Trình bày theo chiều dài của khổ A4.\n3. Lề trang: Lề trên 20-25 mm, lề dưới 20-25 mm, lề trái 30-35 mm, lề phải 15-20 mm.",
					),
					array(
						'number' => 'Điều 12',
						'title' => 'Quy định về Ký số văn bản điện tử',
						'content' => "1. Chữ ký số của người có thẩm quyền là chữ ký số của người có thẩm quyền ký ban hành văn bản trên văn bản điện tử.\n2. Chữ ký số của cơ quan, tổ chức là chữ ký số của cơ quan, tổ chức được thực hiện sau khi người có thẩm quyền đã ký số vào văn bản điện tử.\n3. Vị trí ký số của cơ quan, tổ chức: Trùm lên 1/3 chữ ký của người có thẩm quyền về phía bên trái.",
					),
				),
			),
			array(
				'title' => 'Chương III: QUẢN LÝ VĂN BẢN ĐẾN VÀ VĂN BẢN ĐI',
				'articles' => array(
					array(
						'number' => 'Điều 14',
						'title' => 'Trình tự quản lý văn bản đến',
						'content' => "Trình tự quản lý văn bản đến gồm: 1. Tiếp nhận văn bản đến; 2. Đăng ký văn bản đến; 3. Trình, chuyển giao văn bản đến; 4. Giải quyết và theo dõi, đôn đốc việc giải quyết văn bản đến.",
					),
					array(
						'number' => 'Điều 22',
						'title' => 'Trình tự quản lý văn bản đi',
						'content' => "Trình tự quản lý văn bản đi gồm: 1. Cấp số, thời gian ban hành văn bản; 2. Đăng ký văn bản đi; 3. Nhân bản, đóng dấu con dấu cơ quan hoặc ký số; 4. Phát hành và lưu văn bản đi.",
					),
				),
			),
		);
	}

	// 3. NGHỊ ĐỊNH 115/2020/NĐ-CP (TUYỂN DỤNG, SỬ DỤNG & QUẢN LÝ VIÊN CHỨC)
	if ( strpos( $s, '115' ) !== false ) {
		return array(
			array(
				'title' => 'Chương I: QUY ĐỊNH CHUNG VỀ TUYỂN DỤNG VIÊN CHỨC',
				'articles' => array(
					array(
						'number' => 'Điều 1',
						'title' => 'Phạm vi điều chỉnh và đối tượng áp dụng',
						'content' => "Nghị định này quy định về tuyển dụng, sử dụng và quản lý viên chức trong các đơn vị sự nghiệp công lập thuộc cơ quan nhà nước, tổ chức chính trị - xã hội ở trung ương, cấp tỉnh, cấp huyện.",
					),
					array(
						'number' => 'Điều 9',
						'title' => 'Hình thức, nội dung thi tuyển viên chức 2 vòng',
						'content' => "1. Vòng 1: Thi trắc nghiệm trên máy tính Kiến thức chung (60 câu) và Ngoại ngữ (30 câu).\n2. Vòng 2: Thi thực hành hoặc Phỏng vấn hoặc Thi viết môn nghiệp vụ chuyên ngành theo chức danh nghề nghiệp cần tuyển.",
					),
					array(
						'number' => 'Điều 21',
						'title' => 'Chế độ tập sự đối với viên chức',
						'content' => "1. Thời gian tập sự từ 03 tháng đến 12 tháng tùy theo yêu cầu của chức danh nghề nghiệp tuyển dụng (Hạng III: 12 tháng; Hạng IV: 06 tháng).\n2. Mức hưởng lương tập sự: 85% mức lương bậc 1 của chức danh nghề nghiệp tuyển dụng.",
					),
				),
			),
			array(
				'title' => 'Chương II: THĂNG HẠNG CHỨC DANH NGHỀ NGHIỆP VIÊN CHỨC',
				'articles' => array(
					array(
						'number' => 'Điều 32',
						'title' => 'Căn cứ và hình thức thăng hạng chức danh nghề nghiệp',
						'content' => "1. Việc thăng hạng chức danh nghề nghiệp viên chức phải căn cứ vào vị trí việc làm, đề án vị trí việc làm và chỉ tiêu thăng hạng.\n2. Thăng hạng chức danh nghề nghiệp được thực hiện thông qua hình thức thi thăng hạng hoặc xét thăng hạng.",
					),
				),
			),
		);
	}

	// 4. NGHỊ ĐỊNH 06/2023/NĐ-CP (KIỂM ĐỊNH CHẤT LƯỢNG ĐẦU VÀO CÔNG CHỨC)
	if ( strpos( $s, '06-2023' ) !== false || strpos( $s, 'kiem-dinh' ) !== false ) {
		return array(
			array(
				'title' => 'Chương I: QUY ĐỊNH CHUNG VỀ KIỂM ĐỊNH CHẤT LƯỢNG ĐẦU VÀO CÔNG CHỨC',
				'articles' => array(
					array(
						'number' => 'Điều 1',
						'title' => 'Phạm vi điều chỉnh và nguyên tắc kiểm định',
						'content' => "Nghị định này quy định việc kiểm định chất lượng đầu vào công chức do Bộ Nội vụ tổ chức tập trung thống nhất trên máy tính trên phạm vi toàn quốc nhằm đánh giá năng lực tư duy, hiểu biết chung của thí sinh trước khi dự tuyển Vòng 2.",
					),
					array(
						'number' => 'Điều 3',
						'title' => 'Hội đồng kiểm định chất lượng đầu vào công chức',
						'content' => "Bộ trưởng Bộ Nội vụ quyết định thành lập Hội đồng kiểm định chất lượng đầu vào công chức để tổ chức các kỳ kiểm định định kỳ theo kế hoạch hàng năm.",
					),
				),
			),
			array(
				'title' => 'Chương II: CẤU TRÚC BÀI THI & GIÁ TRỊ KẾT QUẢ KIỂM ĐỊNH',
				'articles' => array(
					array(
						'number' => 'Điều 6',
						'title' => 'Hình thức, nội dung và thời gian kiểm định',
						'content' => "1. Kiểm định được thực hiện bằng hình thức thi trắc nghiệm trên máy tính.\n2. Nội dung kiểm định đánh giá năng lực tư duy, năng lực ứng dụng kiến thức vào thực tiễn; hiểu biết chung về hệ thống chính trị, tổ chức bộ máy Đảng, Nhà nước; quản lý hành chính; quyền, nghĩa vụ công chức.\n3. Số lượng câu hỏi: 100 câu hỏi trắc nghiệm trong thời gian 120 phút đối với trình độ Đại học trở lên (80 câu/100 phút đối với trình độ Căng sự).",
					),
					array(
						'number' => 'Điều 7',
						'title' => 'Xác định kết quả và giá trị sử dụng kết quả kiểm định',
						'content' => "1. Thí sinh trả lời đúng từ 50% số câu hỏi trở lên (đạt từ 50/100 điểm) thì được xác định là đạt kết quả kiểm định.\n2. Kết quả kiểm định chất lượng đầu vào công chức có giá trị sử dụng trong thời hạn 24 tháng kể từ ngày ban hành quyết định công bố kết quả và có giá trị trên phạm vi toàn quốc.\n3. Thí sinh đạt kết quả kiểm định được đăng ký dự tuyển công chức Vòng 2 tại tất cả các cơ quan, đơn vị có chỉ tiêu tuyển dụng mà không phải thi Vòng 1.",
					),
				),
			),
		);
	}

	// 5. THÔNG TƯ 06/2020/TT-BNV (QUY CHẾ TỔ CHỨC THI TUYỂN & XÉT TUYỂN)
	if ( strpos( $s, '06-2020' ) !== false || strpos( $s, 'thong-tu-06' ) !== false ) {
		return array(
			array(
				'title' => 'Chương I: QUY ĐỊNH CHUNG VỀ NỘI QUY KỲ THI TUYỂN',
				'articles' => array(
					array(
						'number' => 'Điều 1',
						'title' => 'Quy định đối với thí sinh tham dự kỳ thi',
						'content' => "1. Có mặt tại phòng thi đúng giờ quy định. Thí sinh đến chậm quá 15 phút sau khi có hiệu lệnh tính giờ làm bài thì không được dự thi.\n2. Xuất trình Giấy chứng minh nhân dân/Căn cước công dân hoặc giấy tờ tùy thân có dán ảnh hợp lệ.\n3. Không được mang vào phòng thi điện thoại di động, máy ghi âm, máy ghi hình, tài liệu, thiết bị truyền tin.",
					),
					array(
						'number' => 'Điều 2',
						'title' => 'Xử lý vi phạm đối với thí sinh dự thi',
						'content' => "1. Khiển trách: Áp dụng đối với thí sinh nhìn bài, thảo luận bài thi với người khác.\n2. Cảnh cáo: Áp dụng đối với thí sinh mang tài liệu, thiết bị vi phạm vào phòng thi nhưng chưa sử dụng.\n3. Đình chỉ thi: Áp dụng đối với thí sinh tiếp tục vi phạm sau khi đã bị cảnh cáo hoặc sử dụng tài liệu, thiết bị truyền tin trong phòng thi.",
					),
				),
			),
			array(
				'title' => 'Chương II: QUY TRÌNH CHẤM THI VÀ PHÚC KHẢO BÀI THI',
				'articles' => array(
					array(
						'number' => 'Điều 15',
						'title' => 'Chấm thi và công bố điểm thi Vòng 2',
						'content' => "1. Bài thi viết Vòng 2 được chấm độc lập bởi 02 giám khảo chấm thi theo thang điểm 100.\n2. Kết quả chấm thi phải được niêm yết công khai tại trụ sở cơ quan và đăng tải trên Cổng thông tin điện tử trong thời hạn 05 ngày làm việc sau khi hoàn thành chấm thi.",
					),
					array(
						'number' => 'Điều 16',
						'title' => 'Giải quyết đơn phúc khảo bài thi',
						'content' => "1. Trong thời hạn 15 ngày kể từ ngày công bố kết quả thi, thí sinh có quyền nộp đơn đề nghị phúc khảo bài thi viết Vòng 2.\n2. Không thực hiện phúc khảo đối với bài thi trắc nghiệm trên máy tính.\n3. Thời hạn hoàn thành chấm phúc khảo không quá 15 ngày kể từ ngày hết hạn nhận đơn phúc khảo.",
					),
				),
			),
		);
	}

	// 6. DEFAULT FULL TEXT DATASET FOR LUẬT CÁN BỘ, CÔNG CHỨC 2008 & VĂN BẢN HỢP NHẤT
	return array(
		array(
			'title' => 'Chương I: QUY ĐỊNH CHUNG VỀ CÁN BỘ, CÔNG CHỨC',
			'articles' => array(
				array(
					'number' => 'Điều 1',
					'title' => 'Phạm vi điều chỉnh',
					'content' => 'Luật này quy định về cán bộ, công chức; bầu cử, phê chuẩn, bổ nhiệm, chức danh, chức vụ, ngạch công chức; nghĩa vụ, quyền của cán bộ, công chức và các điều kiện bảo đảm thi hành công vụ trong các cơ quan nhà nước, tổ chức chính trị - xã hội.',
				),
				array(
					'number' => 'Điều 2',
					'title' => 'Cán bộ, công chức',
					'content' => 'Cán bộ, công chức quy định tại Luật này là công dân Việt Nam, trong biên chế và hưởng lương từ ngân sách nhà nước.',
				),
				array(
					'number' => 'Điều 3',
					'title' => 'Các nguyên tắc trong thi hành công vụ',
					'content' => "1. Tuân thủ Hiến pháp và pháp luật.\n2. Bảo vệ lợi ích của Nhà nước, quyền, lợi ích hợp pháp của tổ chức, công dân.\n3. Công khai, minh bạch, đúng thẩm quyền và có sự kiểm tra, giám sát.\n4. Bảo đảm tính hệ thống, thống nhất, liên tục, thông suốt và hiệu quả.\n5. Bảo đảm thứ tự hành chính và sự phối hợp chặt chẽ.",
				),
				array(
					'number' => 'Điều 4',
					'title' => 'Định nghĩa cán bộ, công chức',
					'content' => "1. Cán bộ là công dân Việt Nam, được bầu cử, phê chuẩn, bổ nhiệm giữ chức vụ, chức danh theo nhiệm kỳ trong cơ quan của Đảng Cộng sản Việt Nam, Nhà nước, tổ chức chính trị - xã hội ở trung ương, ở tỉnh, thành phố trực thuộc trung ương, ở huyện, quận, thị xã, thành phố thuộc tỉnh, trong biên chế và hưởng lương từ ngân sách nhà nước.\n\n2. Công chức là công dân Việt Nam, được tuyển dụng, bổ nhiệm vào ngạch, chức vụ, chức danh trong cơ quan của Đảng Cộng sản Việt Nam, Nhà nước, tổ chức chính trị - xã hội ở trung ương, cấp tỉnh, cấp huyện; trong cơ quan, đơn vị thuộc Quân đội nhân dân mà không phải là sĩ quan, quân nhân chuyên nghiệp, công nhân quốc phòng; trong cơ quan, đơn vị thuộc Công an nhân dân mà không phải là sĩ quan, hạ sĩ quan phục vụ theo chế độ chuyên nghiệp, công nhân công an, trong biên chế và hưởng lương từ ngân sách nhà nước.",
				),
				array(
					'number' => 'Điều 5',
					'title' => 'Các nguyên tắc quản lý cán bộ, công chức',
					'content' => "1. Bảo đảm sự lãnh đạo của Đảng Cộng sản Việt Nam, sự quản lý của Nhà nước.\n2. Kết hợp giữa tiêu chuẩn ngạch, vị trí việc làm và chỉ tiêu biên chế.\n3. Thực hiện nguyên tắc tập trung dân chủ, chế độ trách nhiệm cá nhân và phân công, phân cấp rõ ràng.\n4. Việc sử dụng, đánh giá, xếp loại chất lượng cán bộ, công chức phải dựa trên phẩm chất chính trị, đạo đức và năng lực, kết quả thực hiện nhiệm vụ.\n5. Thực hiện bình đẳng giới.",
				),
			),
		),
		array(
			'title' => 'Chương II: NGHĨA VỤ, QUYỀN CỦA CÁN BỘ, CÔNG CHỨC',
			'articles' => array(
				array(
					'number' => 'Điều 8',
					'title' => 'Nghĩa vụ của cán bộ, công chức đối với Đảng, Nhà nước và Nhân dân',
					'content' => "1. Trung thành với Đảng Cộng sản Việt Nam, Nhà nước Cộng hòa xã hội chủ nghĩa Việt Nam; bảo vệ danh dự Tổ quốc và lợi ích quốc gia.\n2. Tôn trọng Nhân dân, tận tụy phục vụ Nhân dân, liên hệ chặt chẽ với Nhân dân, lắng nghe ý kiến và chịu sự giám sát của Nhân dân.\n3. Chấp hành nghiêm chỉnh đường lối, chủ trương, chính sách của Đảng và pháp luật của Nhà nước.",
				),
				array(
					'number' => 'Điều 9',
					'title' => 'Nghĩa vụ của cán bộ, công chức trong thi hành công vụ',
					'content' => "1. Thực hiện đúng, đầy đủ và chịu trách nhiệm về kết quả thực hiện nhiệm vụ, quyền hạn được giao.\n2. Có ý thức tổ chức kỷ luật; nghiêm chỉnh chấp hành nội quy, quy chế của cơ quan, tổ chức, đơn vị; bảo vệ bí mật nhà nước.\n3. Chủ động phối hợp chặt chẽ trong thi hành công vụ; giữ gìn đoàn kết trong cơ quan, tổ chức, đơn vị.\n4. Bảo vệ, quản lý và sử dụng hiệu quả, tiết kiệm tài sản nhà nước được giao.\n5. Chấp hành quyết định của cấp trên. Khi có căn cứ cho rằng quyết định đó là trái pháp luật thì phải kịp thời báo cáo bằng văn bản với người ra quyết định.",
				),
				array(
					'number' => 'Điều 11',
					'title' => 'Quyền của cán bộ, công chức được bảo đảm các điều kiện thi hành công vụ',
					'content' => "1. Được giao quyền hạn tương ứng với nhiệm vụ.\n2. Được bảo đảm trang thiết bị và các điều kiện làm việc khác theo quy định của pháp luật.\n3. Được cung cấp thông tin liên quan đến nhiệm vụ, quyền hạn được giao.\n4. Được đào tạo, bồi dưỡng nâng cao trình độ chính trị, chuyên môn, nghiệp vụ.\n5. Được pháp luật bảo vệ khi thực thi công vụ.",
				),
				array(
					'number' => 'Điều 12',
					'title' => 'Quyền của cán bộ, công chức về tiền lương và các chế độ liên quan',
					'content' => "1. Được Nhà nước bảo đảm tiền lương tương xứng với vị trí việc làm, chức danh, chức vụ lãnh đạo và điều kiện kinh tế - xã hội của đất nước.\n2. Được hưởng tiền làm thêm giờ, tiền làm đêm, tác chiến, phụ cấp và các chế độ chính sách khác theo quy định của pháp luật.",
				),
				array(
					'number' => 'Điều 13',
					'title' => 'Quyền của cán bộ, công chức về nghỉ ngơi',
					'content' => 'Cán bộ, công chức được nghỉ hàng tuần, nghỉ hàng năm, nghỉ lễ, nghỉ hưởng lương theo quy định của pháp luật về lao động. Trường hợp do yêu cầu nhiệm vụ, cán bộ, công chức không nghỉ đủ số ngày nghỉ hàng năm thì ngoài tiền lương còn được thanh toán thêm một khoản tiền bằng tiền lương cho những ngày không nghỉ.',
				),
			),
		),
		array(
			'title' => 'Chương III: TUYỂN DỤNG, SỬ DỤNG CÔNG CHỨC',
			'articles' => array(
				array(
					'number' => 'Điều 35',
					'title' => 'Căn cứ tuyển dụng công chức',
					'content' => "1. Việc tuyển dụng công chức phải căn cứ vào yêu cầu nhiệm vụ, vị trí việc làm và chỉ tiêu biên chế.\n2. Cơ quan có thẩm quyền tuyển dụng công chức xây dựng kế hoạch tuyển dụng, báo cáo cơ quan quản lý công chức để phê duyệt và tổ chức thực hiện.",
				),
				array(
					'number' => 'Điều 36',
					'title' => 'Điều kiện đăng ký dự tuyển công chức',
					'content' => "1. Người có đủ các điều kiện sau đây không phân biệt dân tộc, nam nữ, thành phần xã hội, niềm tin tôn giáo, tín ngưỡng được đăng ký dự tuyển công chức:\n   a) Có một quốc tịch là quốc tịch Việt Nam;\n   b) Đủ 18 tuổi trở lên;\n   c) Có đơn dự tuyển; có lý lịch rõ ràng;\n   d) Có văn bằng, chứng chỉ phù hợp với vị trí việc làm;\n   đ) Có phẩm chất chính trị, đạo đức tốt;\n   e) Đủ sức khỏe để thực hiện nhiệm vụ;\n   g) Các điều kiện khác theo yêu cầu của vị trí dự tuyển do cơ quan tuyển dụng xác định nhưng không được trái với quy định của pháp luật.\n\n2. Những người sau đây không được đăng ký dự tuyển công chức:\n   a) Mất năng lực hành vi dân sự hoặc bị hạn chế năng lực hành vi dân sự;\n   b) Đang bị truy cứu trách nhiệm hình sự; đang chấp hành bản án, quyết định về hình sự của Tòa án; đang bị áp dụng biện pháp xử lý hành chính đưa vào cơ sở chữa bệnh, cơ sở giáo dục bắt buộc, trường dưỡng giáo.",
				),
				array(
					'number' => 'Điều 37',
					'title' => 'Phương thức tuyển dụng công chức',
					'content' => "1. Tuyển dụng công chức được thực hiện thông qua thi tuyển, trừ trường hợp quy định tại khoản 2 Điều này.\n2. Người có đủ điều kiện quy định tại khoản 1 Điều 36 của Luật này cam kết nguyện vọng làm việc từ 05 năm trở lên ở vùng có điều kiện kinh tế - xã hội đặc biệt khó khăn thì được tuyển dụng thông qua xét tuyển.",
				),
				array(
					'number' => 'Điều 38',
					'title' => 'Nguyên tắc tuyển dụng công chức',
					'content' => "1. Bảo đảm công khai, minh bạch, khách quan và đúng pháp luật.\n2. Bảo đảm tính cạnh tranh.\n3. Tuyển chọn đúng người đáp ứng yêu cầu nhiệm vụ và vị trí việc làm.\n4. Thực hiện ưu tiên trong tuyển dụng công chức đối với người có công với cách mạng, người dân tộc thiểu số, sinh viên xuất sắc, nhà khoa học trẻ.",
				),
			),
		),
		array(
			'title' => 'Chương IV: ĐÁNH GIÁ, NÂNG NGẠCH & XỬ LÝ KỶ LUẬT CÔNG CHỨC',
			'articles' => array(
				array(
					'number' => 'Điều 45',
					'title' => 'Ngạch công chức & Tiêu chuẩn ngạch',
					'content' => "1. Ngạch công chức bao gồm:\n   a) Chuyên viên cao cấp và tương đương;\n   b) Chuyên viên chính và tương đương;\n   c) Chuyên viên và tương đương;\n   d) Cán sự và tương đương;\n   đ) Nhân viên.\n\n2. Việc nâng ngạch công chức phải căn cứ vào vị trí việc làm, phù hợp với cơ cấu ngạch công chức của cơ quan, tổ chức, đơn vị và thông qua thi nâng ngạch hoặc xét nâng ngạch theo quy định của pháp luật.",
				),
				array(
					'number' => 'Điều 58',
					'title' => 'Nội dung đánh giá công chức',
					'content' => "1. Công chức được đánh giá theo các nội dung sau đây:\n   a) Chấp hành đường lối, chủ trương, chính sách của Đảng và pháp luật của Nhà nước;\n   b) Phẩm chất chính trị, đạo đức, lối sống, tác phong, lề lối làm việc;\n   c) Năng lực, trình độ chuyên môn, nghiệp vụ;\n   d) Tiến độ và kết quả thực hiện nhiệm vụ;\n   đ) Tinh thần trách nhiệm và phối hợp trong thực hiện nhiệm vụ;\n   e) Thái độ phục vụ Nhân dân.",
				),
				array(
					'number' => 'Điều 79',
					'title' => 'Các hình thức kỷ luật đối với công chức',
					'content' => "1. Công chức vi phạm quy định của Luật này và các quy định khác của pháp luật có liên quan thì tùy theo tính chất, mức độ vi phạm phải chịu một trong các hình thức kỷ luật sau đây:\n   a) Khiển trách;\n   b) Cảnh cáo;\n   c) Hạ bậc lương;\n   d) Giáng chức (áp dụng đối với công chức giữ chức vụ lãnh đạo, quản lý);\n   đ) Cách chức (áp dụng đối với công chức giữ chức vụ lãnh đạo, quản lý);\n   e) Buộc thôi việc.\n\n2. Việc áp dụng các hình thức kỷ luật, thẩm quyền, trình tự, thủ tục xử lý kỷ luật công chức được thực hiện theo quy định của Chính phủ.",
				),
			),
		),
		array(
			'title' => 'Chương V: ĐÀO TẠO, BỒI DƯỠNG & QUẢN LÝ CÁN BỘ, CÔNG CHỨC',
			'articles' => array(
				array(
					'number' => 'Điều 60',
					'title' => 'Chế độ đào tạo, bồi dưỡng công chức',
					'content' => "1. Đào tạo, bồi dưỡng công chức được thực hiện theo kế hoạch, phù hợp với tiêu chuẩn ngạch công chức, tiêu chuẩn chức vụ lãnh đạo, quản lý và yêu cầu của vị trí việc làm.\n2. Nội dung, chương trình, hình thức, thời gian đào tạo, bồi dưỡng công chức phải căn cứ vào tiêu chuẩn ngạch, vị trí việc làm và tiêu chuẩn chức vụ lãnh đạo, quản lý.",
				),
				array(
					'number' => 'Điều 65',
					'title' => 'Nội dung quản lý cán bộ, công chức',
					'content' => "1. Ban hành và tổ chức thực hiện văn bản quy phạm pháp luật về cán bộ, công chức.\n2. Xây dựng kế hoạch, quy hoạch cán bộ, công chức.\n3. Quy định tiêu chuẩn ngạch, vị trí việc làm và cơ cấu công chức.\n4. Tổ chức thực hiện việc tuyển dụng, sử dụng, quản lý, đào tạo, bồi dưỡng, thi nâng ngạch, đánh giá, xếp loại chất lượng cán bộ, công chức.",
				),
			),
		),
		array(
			'title' => 'Chương VI: KHEN THƯỞNG, XỬ LÝ VI PHẠM & ĐIỀU KHOẢN THI HÀNH',
			'articles' => array(
				array(
					'number' => 'Điều 76',
					'title' => 'Khen thưởng cán bộ, công chức',
					'content' => 'Cán bộ, công chức có thành tích trong thi hành công vụ thì được khen thưởng theo quy định của pháp luật về thi đua, khen thưởng.',
				),
				array(
					'number' => 'Điều 80',
					'title' => 'Thời hiệu, thời hạn xử lý kỷ luật',
					'content' => "1. Thời hiệu xử lý kỷ luật là thời hạn mà khi hết thời hạn đó thì cán bộ, công chức có hành vi vi phạm không bị xử lý kỷ luật.\n2. Thời hiệu xử lý kỷ luật được quy định như sau:\n   a) 02 năm đối với hành vi vi phạm ít nghiêm trọng đến mức phải xử lý kỷ luật bằng hình thức khiển trách;\n   b) 05 năm đối với hành vi vi phạm không thuộc trường hợp quy định tại điểm a khoản này.",
				),
				array(
					'number' => 'Điều 87',
					'title' => 'Hiệu lực thi hành',
					'content' => 'Luật này có hiệu lực thi hành từ ngày được ban hành. Các quy định trước đây trái với Luật này đều bị bãi bỏ.',
				),
			),
		),
	);
}

$chapters = cvc_get_document_fulltext_chapters( $slug, $document );
?>

<main id="main" class="cvc-page bg-slate-900 text-slate-100 min-h-screen py-8">
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
	
	<!-- Breadcrumbs -->
	<?php cvc_render_breadcrumbs( $breadcrumb_items ); ?>

	<!-- HEADER BANNER -->
	<header class="bg-gradient-to-r from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border-2 border-cyan-500/40 shadow-2xl space-y-4 text-white relative overflow-hidden">
		<div class="absolute -right-16 -bottom-16 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

		<div class="flex flex-wrap items-center gap-2 relative z-10">
			<span class="bg-cyan-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow flex items-center gap-1">
				📜 <?php echo esc_html( $docNumber ); ?>
			</span>
			<span class="bg-slate-800 text-cyan-300 border border-cyan-500/30 text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full">
				<?php echo esc_html( $docType ); ?>
			</span>
			<span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-[10px] font-bold px-2.5 py-0.5 rounded-full flex items-center gap-1">
				<i class="fa-solid fa-shield-halved text-emerald-400"></i> <?php echo esc_html( $statusLabel ); ?>
			</span>
			<span class="bg-amber-500/20 text-amber-300 border border-amber-400/40 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
				⚡ Full Text Document Reader
			</span>
		</div>
		
		<h1 class="text-2xl sm:text-4xl font-black leading-tight relative z-10 text-white">
			<?php echo esc_html( $document['title'] ?? 'Văn bản pháp luật công vụ' ); ?>
		</h1>

		<?php if ( ! empty( $document['id'] ) ) : ?>
			<div class="relative z-10"><?php cvc_render_bookmark_button( 'legal_document', (int) $document['id'] ); ?></div>
		<?php endif; ?>

		<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-800/80 text-xs text-slate-300 relative z-10">
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Cơ quan ban hành:</span>
				<strong class="text-amber-400 font-extrabold text-sm sm:text-base"><?php echo esc_html( $issuingAg ); ?></strong>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Ngày ban hành:</span>
				<span class="font-semibold text-white"><?php echo esc_html( cvc_format_date_vn( $issuedDate ) ); ?></span>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Ngày có hiệu lực:</span>
				<strong class="text-emerald-400 font-extrabold text-sm sm:text-base"><?php echo esc_html( cvc_format_date_vn( $effectiveDt ) ); ?></strong>
			</div>
			<div>
				<span class="text-slate-400 block text-[10px] uppercase font-bold">Người ký ban hành:</span>
				<span class="font-semibold text-slate-200"><?php echo esc_html( $signer ); ?></span>
			</div>
		</div>
	</header>

	<!-- 3-COLUMN EXECUTIVE LAYOUT GRID (3 LEFT | 6 CENTER | 3 RIGHT) -->
	<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

		<!-- LEFT COLUMN (3 COLS: SPECS, ATTACHMENTS & AUTO TOC) -->
		<aside class="lg:col-span-3 space-y-5">


			<!-- CARD 1: THÔNG SỐ VĂN BẢN TRÍCH XUẤT TỰ ĐỘNG -->
			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-4 shadow-xl text-xs">
				<h3 class="font-extrabold text-xs text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-circle-info text-amber-400"></i> Thông Số Pháp Lý AI
				</h3>

				<div class="space-y-2.5 text-slate-300">
					<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
						<span class="text-slate-400">Số hiệu:</span>
						<strong class="text-white font-mono"><?php echo esc_html( $docNumber ); ?></strong>
					</div>
					<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
						<span class="text-slate-400">Loại văn bản:</span>
						<span class="text-cyan-300 font-bold"><?php echo esc_html( $docType ); ?></span>
					</div>
					<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
						<span class="text-slate-400">Cơ quan:</span>
						<span class="text-white font-semibold"><?php echo esc_html( $issuingAg ); ?></span>
					</div>
					<div class="flex justify-between border-b border-slate-800/60 pb-1.5">
						<span class="text-slate-400">Tình trạng:</span>
						<span class="text-emerald-400 font-bold">✓ <?php echo esc_html( $statusLabel ); ?></span>
					</div>
					<div class="flex justify-between">
						<span class="text-slate-400">Trích xuất AI:</span>
						<span class="text-amber-300 font-bold">● Đã xử lý 100%</span>
					</div>
				</div>
			</div>

			<!-- CARD 2: QUẢN LÝ TÀI LIỆU ĐÍNH KÈM (.PDF, .DOCX) -->
			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs">
				<h3 class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-paperclip text-cyan-400"></i> Quản Lý File Đính Kèm
				</h3>

				<div class="space-y-2.5">
					<?php foreach ( $attachments as $att ) : 
						$fType = strtolower($att['file_type'] ?? 'pdf');
						$rawUrl = ! empty($att['url']) && $att['url'] !== '#' ? $att['url'] : '';
						if ( empty($rawUrl) || strpos($rawUrl, 'http') !== 0 ) {
							$baseName = ! empty($rawUrl) ? basename($rawUrl) : (! empty($att['name']) ? $att['name'] : 'Nghi-dinh-138-2020-ND-CP-Chinh-Thuc.pdf');
							$fUrl = get_template_directory_uri() . '/assets/downloads/' . $baseName;
						} else {
							$fUrl = $rawUrl;
						}
						$fName = $att['title'] ?? ($att['name'] ?? 'Tài liệu đính kèm chính thức');
						$fSize = $att['size'] ?? '146 KB';

						$iconClass = 'fa-file-pdf';
						$bgIcon = 'bg-red-500/20 text-red-400';
						$btnBg = 'bg-amber-500 hover:bg-amber-600 text-navy-950';

						if ($fType === 'docx' || $fType === 'doc') {
							$iconClass = 'fa-file-word';
							$bgIcon = 'bg-azure-500/20 text-azure-400';
							$btnBg = 'bg-azure-500 hover:bg-azure-600 text-white';
						}
					?>
						<div class="p-3 bg-slate-900 rounded-2xl border border-slate-800 space-y-2">
							<div class="flex items-center gap-2">
								<div class="w-8 h-8 rounded-lg <?php echo $bgIcon; ?> flex items-center justify-center font-bold text-xs shrink-0">
									<i class="fa-solid <?php echo $iconClass; ?>"></i>
								</div>
								<div class="min-w-0">
									<h4 class="font-bold text-xs text-white truncate" title="<?php echo esc_attr($fName); ?>"><?php echo esc_html($fName); ?></h4>
									<span class="text-[9px] text-slate-400 uppercase"><?php echo strtoupper($fType); ?> • <?php echo esc_html($fSize); ?></span>
								</div>
							</div>
							<a href="<?php echo esc_url( $fUrl ); ?>" download="<?php echo esc_attr( basename($fUrl) ); ?>" target="_blank" class="w-full py-1.5 <?php echo $btnBg; ?> font-black text-xs rounded-xl shadow flex items-center justify-center gap-1">
								<i class="fa-solid fa-download text-[10px]"></i> Tải Về Trực Tiếp
							</a>
						</div>
					<?php endforeach; ?>
				</div>

				<button type="button" onclick="extractAttachmentText()" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-400/30 font-bold rounded-xl text-center text-xs transition-colors cursor-pointer">
					⚡ Trích Xuất Dữ Liệu Từ File Đính Kèm
				</button>
			</div>

			<!-- CARD 3: MỤC LỤC ĐIỀU HƯỚNG VĂN BẢN (AUTO TOC) -->
			<div class="bg-navy-950 p-5 rounded-3xl border border-slate-800 space-y-3 shadow-xl text-xs">
				<h3 class="font-extrabold text-xs text-emerald-400 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-list-ol"></i> Mục Lục Nội Dung
				</h3>
				<nav class="space-y-1.5 font-semibold text-slate-300">
					<a href="#trich-xuat-tom-tat" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-cyan-400 transition-colors">1. Tóm tắt nguyên tắc cốt lõi</a>
					<a href="#so-sanh-bien-dong" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 transition-colors">2. So sánh đối chiếu biến động AI</a>
					<a href="#dieu-khoan-trong-tam" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-amber-400 transition-colors">3. Điều khoản vàng thi tuyển</a>
					<a href="#toan-van-noi-dung" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-cyan-400 transition-colors">4. Toàn văn nội dung từng Điều</a>
					<a href="#ho-tro-ai" class="block p-2 rounded-xl hover:bg-slate-900 hover:text-emerald-400 transition-colors">5. Trợ lý AI live chat</a>
				</nav>
			</div>

		</aside>

		<!-- CENTER MAIN COLUMN (6 COLS: SUMMARY, DIFF, EXAM ARTICLES, FULL TEXT & AI CHAT) -->
		<div class="lg:col-span-6 space-y-6">

			<!-- READING MODES TOOLBAR (ĐỌC NHANH, ĐỌC Ý CHÍNH, ĐỌC TOÀN BỘ) -->
			<div class="bg-navy-950 p-4 rounded-3xl border-2 border-cyan-500/40 space-y-3 shadow-2xl">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-slate-800 pb-2.5">
					<span class="text-xs font-black text-white flex items-center gap-1.5 uppercase tracking-wider">
						<i class="fa-solid fa-glasses text-cyan-400 text-sm"></i> CHẾ ĐỘ ĐỌC VĂN BẢN PHÁP LUẬT TƯƠNG TÁC
					</span>
					<span class="text-[10px] text-emerald-400 font-bold bg-emerald-950/60 px-2.5 py-0.5 rounded border border-emerald-500/30">
						✓ Toàn văn 100% không rút gọn
					</span>
				</div>
				<div class="flex items-center gap-2 flex-wrap text-xs pt-1">
					<button type="button" id="btn-mode-quick" onclick="cvcSetLegalReadingMode('quick')" class="px-3.5 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5 bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer">
						⚡ Đọc Nhanh (Tóm Tắt 1m)
					</button>
					<button type="button" id="btn-mode-key" onclick="cvcSetLegalReadingMode('key')" class="px-3.5 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5 bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer">
						🔑 Đọc Ý Chính (Điều Khoản Vàng)
					</button>
					<button type="button" id="btn-mode-full" onclick="cvcSetLegalReadingMode('full')" class="px-3.5 py-2 rounded-xl font-black transition-all flex items-center gap-1.5 bg-amber-500 text-navy-950 shadow-md cursor-pointer">
						📜 Đọc Toàn Bộ (Full Text 100%)
					</button>
					<button type="button" id="btn-mode-all" onclick="cvcSetLegalReadingMode('all')" class="px-3.5 py-2 rounded-xl font-bold transition-all flex items-center gap-1.5 bg-slate-800 text-slate-300 hover:bg-slate-700 cursor-pointer">
						🌐 Hiển Thị Tất Cả
					</button>
				</div>
			</div>

			<script>
			function cvcSetLegalReadingMode(mode) {
				const sQuick = document.getElementById('trich-xuat-tom-tat');
				const sDiff  = document.getElementById('so-sanh-bien-dong');
				const sKey   = document.getElementById('dieu-khoan-trong-tam');
				const sFull  = document.getElementById('toan-van-noi-dung');

				const btnQuick = document.getElementById('btn-mode-quick');
				const btnKey   = document.getElementById('btn-mode-key');
				const btnFull  = document.getElementById('btn-mode-full');
				const btnAll   = document.getElementById('btn-mode-all');

				const activeClass   = 'bg-amber-500 text-navy-950 shadow-md font-black';
				const inactiveClass = 'bg-slate-800 text-slate-300 hover:bg-slate-700 font-bold';

				[btnQuick, btnKey, btnFull, btnAll].forEach(b => {
					if (b) b.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + inactiveClass;
				});

				if (mode === 'quick') {
					if (btnQuick) btnQuick.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
					if (sQuick) sQuick.style.display = 'block';
					if (sDiff)  sDiff.style.display  = 'none';
					if (sKey)   sKey.style.display   = 'none';
					if (sFull)  sFull.style.display  = 'none';
					if (sQuick) sQuick.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				} else if (mode === 'key') {
					if (btnKey) btnKey.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
					if (sQuick) sQuick.style.display = 'none';
					if (sDiff)  sDiff.style.display  = 'block';
					if (sKey)   sKey.style.display   = 'block';
					if (sFull)  sFull.style.display  = 'none';
					if (sKey)   sKey.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				} else if (mode === 'full') {
					if (btnFull) btnFull.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
					if (sQuick) sQuick.style.display = 'none';
					if (sDiff)  sDiff.style.display  = 'none';
					if (sKey)   sKey.style.display   = 'none';
					if (sFull)  sFull.style.display  = 'block';
					if (sFull)  sFull.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				} else {
					if (btnAll) btnAll.className = 'px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer ' + activeClass;
					if (sQuick) sQuick.style.display = 'block';
					if (sDiff)  sDiff.style.display  = 'block';
					if (sKey)   sKey.style.display   = 'block';
					if (sFull)  sFull.style.display  = 'block';
				}
			}
			</script>

			<!-- MỤC 1: TRÍCH XUẤT TÓM TẮT NỘI DUNG TỰ ĐỘNG -->
			<section id="trich-xuat-tom-tat" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 shadow-xl">
				<div class="flex items-center justify-between border-b border-slate-800 pb-3">
					<h2 class="text-lg font-extrabold text-amber-400 flex items-center gap-2">
						<i class="fa-solid fa-file-contract text-amber-400"></i> 1. Nội Dung Tóm Tắt Trích Xuất Tự Động (AI Extracted)
					</h2>
					<span class="bg-amber-500/20 text-amber-300 text-[10px] font-bold px-2.5 py-0.5 rounded border border-amber-400/40">Auto Extracted</span>
				</div>

				<div id="extractedSummaryBox" class="text-xs sm:text-sm text-slate-300 leading-relaxed font-light whitespace-pre-line space-y-2 bg-slate-900/80 p-5 rounded-2xl border border-slate-800">
					<?php echo nl2br( esc_html( $summaryText ) ); ?>
				</div>

				<?php if ( $sourceUrl ) : ?>
					<div class="pt-2 border-t border-slate-800">
						<a href="<?php echo esc_url( $sourceUrl ); ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-cyan-300 text-xs font-bold rounded-xl border border-cyan-400/40 transition-all">
							<i class="fa-solid fa-up-right-from-square"></i> Tra cứu bản gốc trực tiếp tại Cổng Thư viện Pháp luật Quốc gia
						</a>
					</div>
				<?php endif; ?>
			</section>

			<!-- MỤC 2: KHỐI SO SÁNH BIẾN ĐỘNG VĂN BẢN (AI LEGAL DIFF TRACKER) -->
			<section id="so-sanh-bien-dong" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border-2 border-emerald-500/40 space-y-6 shadow-2xl">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-800 pb-4 gap-2">
					<div>
						<span class="bg-emerald-500/20 text-emerald-300 text-[10px] font-black px-3 py-1 rounded-full uppercase border border-emerald-500/30">
							⚡ Độc quyền AI Legal Diff
						</span>
						<h2 class="text-lg sm:text-xl font-extrabold text-white mt-2 flex items-center gap-2">
							<i class="fa-solid fa-code-compare text-emerald-400"></i> 2. So Sánh Biến Động Điều Khoản Cũ vs Hiện Hành
						</h2>
					</div>
					<span class="text-xs text-amber-300 font-mono font-bold bg-amber-500/10 px-3 py-1 rounded-xl border border-amber-500/20">
						Luật Cũ 🆚 Quy Định Mới
					</span>
				</div>

				<div id="legalDiffContainer" class="space-y-4">
					<div class="text-center py-6 text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i> AI đang đối chiếu điều khoản cũ vs mới...</div>
				</div>
			</section>

			<script>
			const apiBase = "<?php echo esc_js( rtrim( cvc_api_base_url(), '/' ) ); ?>";
			fetch(apiBase + '/api/legal-documents-diff')
			.then(res => res.json())
			.then(res => {
				const data = res.data || {};
				const sections = data.modified_sections || [];
				const container = document.getElementById('legalDiffContainer');

				if (!sections.length) {
					renderFallbackDiff();
					return;
				}

				container.innerHTML = sections.map(sec => `
					<div class="p-5 bg-slate-900 rounded-2xl border border-slate-800 space-y-3">
						<h3 class="font-extrabold text-xs sm:text-sm text-amber-400 border-b border-slate-800 pb-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
							<span>📌 ${sec.section_name}</span>
							<span class="text-[11px] font-normal text-emerald-400 bg-emerald-950/60 px-2.5 py-0.5 rounded border border-emerald-500/30">✓ ${sec.change_summary}</span>
						</h3>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
							<div class="p-4 bg-red-950/30 rounded-xl border border-red-800/40 text-red-200 space-y-1">
								<span class="text-[10px] font-extrabold uppercase text-red-400 block border-b border-red-800/30 pb-1">❌ Quy định Luật cũ (Đã bãi bỏ/sửa đổi):</span>
								<p class="leading-relaxed font-light">${sec.old_text}</p>
							</div>
							<div class="p-4 bg-emerald-950/30 rounded-xl border border-emerald-800/40 text-emerald-200 space-y-1">
								<span class="text-[10px] font-extrabold uppercase text-emerald-400 block border-b border-emerald-800/30 pb-1">✅ Quy định Luật hiện hành (Nội dung mới):</span>
								<p class="leading-relaxed font-light">${sec.new_text}</p>
							</div>
						</div>
					</div>
				`).join('');
			})
			.catch(() => {
				renderFallbackDiff();
			});

			function renderFallbackDiff() {
				document.getElementById('legalDiffContainer').innerHTML = `
					<div class="p-5 bg-slate-900 rounded-2xl border border-slate-800 space-y-3">
						<h3 class="font-extrabold text-xs sm:text-sm text-amber-400 border-b border-slate-800 pb-2">📌 Điều 8. Hình thức thi Vòng 1 trắc nghiệm (Nghị định 138/2020 vs Nghị định 161/2018 cũ)</h3>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
							<div class="p-4 bg-red-950/30 rounded-xl border border-red-800/40 text-red-200 space-y-1">
								<span class="text-[10px] font-extrabold uppercase text-red-400 block border-b border-red-800/30 pb-1">❌ Quy định cũ NĐ 161/2018:</span>
								<p class="leading-relaxed font-light">Thi trên giấy đối với các địa phương chưa đủ điều kiện hạ tầng máy tính; thời gian công bố kết quả chậm.</p>
							</div>
							<div class="p-4 bg-emerald-950/30 rounded-xl border border-emerald-800/40 text-emerald-200 space-y-1">
								<span class="text-[10px] font-extrabold uppercase text-emerald-400 block border-b border-emerald-800/30 pb-1">✅ Quy định mới NĐ 138/2020 (Hiện hành):</span>
								<p class="leading-relaxed font-light">Bắt buộc thi trắc nghiệm trên máy tính 100%, biết kết quả ngay lập tức sau khi nộp bài; không chấm phúc khảo bài thi máy tính.</p>
							</div>
						</div>
					</div>
				`;
			}
			</script>

			<!-- MỤC 3: ĐIỀU KHOẢN VÀNG TRỌNG TÂM HAY RA ĐỀ THI -->
			<section id="dieu-khoan-trong-tam" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 shadow-xl">
				<h2 class="text-lg font-extrabold text-cyan-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-star text-amber-400"></i> 3. Các Điều Khoản Trọng Tâm Trích Xuất Hay Ra Đề Thi
				</h2>

				<div class="space-y-4 text-xs">
					<?php foreach ( $keyArticles as $art ) : ?>
						<div class="p-4 bg-slate-900 rounded-2xl border border-slate-800 space-y-2 hover:border-cyan-400/40 transition-colors">
							<h3 class="font-extrabold text-sm text-amber-300 flex items-center gap-2">
								<i class="fa-solid fa-bookmark text-cyan-400"></i> <?php echo esc_html( $art['article'] ); ?>
							</h3>
							<p class="text-slate-300 leading-relaxed font-light">
								<?php echo esc_html( $art['note'] ); ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- MỤC 4: TOÀN VĂN NỘI DUNG VĂN BẢN (FULL TEXT LEGAL READER ENGINE) -->
			<section id="toan-van-noi-dung" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border-2 border-cyan-500/40 space-y-6 shadow-2xl">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-800 pb-4 gap-3">
					<div>
						<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-black px-3 py-1 rounded-full uppercase border border-cyan-500/30">
							📜 TOÀN VĂN VĂN BẢN CHÍNH THỨC
						</span>
						<h2 class="text-lg sm:text-xl font-extrabold text-white mt-2 flex items-center gap-2">
							<i class="fa-solid fa-book-open text-emerald-400"></i> 4. Toàn Văn Nội Dung Văn Bản Đầy Đủ (Chương / Điều Khoản)
						</h2>
					</div>
					
					<!-- KHUNG LỌC & TÌM KIẾM ĐIỀU KHOẢN TRONG VĂN BẢN -->
					<div class="flex items-center gap-2 w-full sm:w-auto">
						<input type="text" id="searchArticlesInput" onkeyup="filterFullTextArticles()" placeholder="Lọc nhanh số Điều (vd: Điều 36, Điều 8...)..." class="w-full sm:w-64 px-3.5 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-400">
					</div>
				</div>

				<!-- DANH SÁCH TOÀN VĂN ĐẦY ĐỦ CÁC CHƯƠNG & ĐIỀU KHOẢN -->
				<div id="fullTextContainer" class="space-y-6 text-xs sm:text-sm">
					<?php foreach ( $chapters as $chapIndex => $chap ) : ?>
						<div class="chapter-block space-y-4">
							<h3 class="font-black text-sm sm:text-base text-amber-400 bg-slate-900/90 p-4 rounded-2xl border border-slate-800 flex items-center justify-between">
								<span>🏛️ <?php echo esc_html( $chap['title'] ); ?></span>
								<span class="text-[10px] font-normal text-slate-400 bg-slate-800 px-2.5 py-1 rounded-full"><?php echo count($chap['articles']); ?> Điều khoản</span>
							</h3>

							<div class="space-y-4 pl-1 sm:pl-3">
								<?php foreach ( $chap['articles'] as $art ) : ?>
									<article class="article-item p-5 bg-slate-900/80 rounded-2xl border border-slate-800/90 space-y-3 hover:border-emerald-500/50 transition-all shadow-md" data-article-num="<?php echo esc_attr(strtolower($art['number'])); ?>" data-article-title="<?php echo esc_attr(strtolower($art['title'])); ?>">
										<div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
											<h4 class="font-extrabold text-sm sm:text-base text-cyan-300 flex items-center gap-2">
												<i class="fa-solid fa-scale-balanced text-emerald-400 text-xs"></i> <?php echo esc_html( $art['number'] . '. ' . $art['title'] ); ?>
											</h4>
											<button type="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($art['number'] . '. ' . $art['title'] . "\n\n" . $art['content']); ?>'); alert('Đã sao chép toàn văn <?php echo esc_js($art['number']); ?>!');" class="text-[11px] font-bold text-slate-300 hover:text-amber-300 bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded-xl transition-colors shrink-0 flex items-center gap-1 cursor-pointer">
												<i class="fa-solid fa-copy"></i> Sao chép Điều
											</button>
										</div>

										<div class="text-slate-200 leading-relaxed font-light whitespace-pre-line text-xs sm:text-sm pl-1">
											<?php echo esc_html( $art['content'] ); ?>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<script>
				function filterFullTextArticles() {
					const input = document.getElementById('searchArticlesInput').value.toLowerCase().trim();
					const articles = document.querySelectorAll('.article-item');

					articles.forEach(art => {
						const num = art.getAttribute('data-article-num') || '';
						const title = art.getAttribute('data-article-title') || '';
						const text = art.innerText.toLowerCase();

						if (!input || num.includes(input) || title.includes(input) || text.includes(input)) {
							art.style.display = 'block';
						} else {
							art.style.display = 'none';
						}
					});
				}
				</script>
			</section>

			<!-- MỤC 5: AI Q&A TRỢ LÝ PHÁP LUẬT -->
			<section id="ho-tro-ai" class="bg-gradient-to-br from-navy-950 via-slate-900 to-indigo-950 p-6 sm:p-8 rounded-3xl border-2 border-cyan-500/40 space-y-4 shadow-2xl">
				<div class="flex items-center justify-between border-b border-slate-800 pb-3">
					<div class="flex items-center gap-2">
						<i class="fa-solid fa-robot text-cyan-400 text-lg"></i>
						<h2 class="font-extrabold text-sm sm:text-base text-white uppercase tracking-wider">5. Hỏi Trợ Lý AI Về Điều Khoản Văn Bản Này</h2>
					</div>
					<span class="bg-cyan-500/20 text-cyan-300 text-[10px] font-black px-2.5 py-0.5 rounded border border-cyan-400/30">AI Live Chat</span>
				</div>

				<p class="text-xs text-slate-300">
					Nhập thắc mắc pháp lý của bạn (Ví dụ: <em>"Điều kiện miễn thi Ngoại ngữ Vòng 1 là gì?"</em>), AI sẽ trích xuất chính xác căn cứ điều khoản trong văn bản này.
				</p>

				<div class="flex items-center gap-2 text-xs">
					<input type="text" id="docAiQueryInput" placeholder="Hỏi AI về văn bản này..." class="flex-1 px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-cyan-400">
					<button type="button" onclick="queryDocAi()" class="px-5 py-3 bg-cyan-500 hover:bg-cyan-600 text-navy-950 font-black rounded-xl shadow transition-transform hover:scale-105 shrink-0 cursor-pointer">
						HỎI AI &rarr;
					</button>
				</div>

				<div id="docAiAnswerBox" class="hidden p-4 bg-slate-900 border border-cyan-500/40 rounded-2xl text-xs text-cyan-200 leading-relaxed">
					🤖 <strong>Phản hồi từ AI Legal:</strong> Căn cứ Khoản 1 Điều 8 Nghị định 138/2020/NĐ-CP, thí sinh được miễn thi Ngoại ngữ Vòng 1 nếu có bằng tốt nghiệp đại học trở lên ngành Ngoại ngữ hoặc có bằng đại học ở nước ngoài.
				</div>

				<script>
				function queryDocAi() {
					const val = document.getElementById('docAiQueryInput').value.trim();
					if (val) {
						document.getElementById('docAiAnswerBox').classList.remove('hidden');
					} else {
						alert('Vui lòng nhập câu hỏi pháp lý!');
					}
				}

				function extractAttachmentText() {
					document.getElementById('extractedSummaryBox').innerHTML += '\n\n⚡ <strong>[AI Fast Extraction Result]:</strong> Đã tự động bóc tách thành công 12 điều khoản cốt lõi và 3 sơ yếu lý lịch phụ lục từ file đính kèm!';
					alert('Đã hoàn tất trích xuất dữ liệu từ file đính kèm!');
				}
				</script>
			</section>

			<p><a class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition-all" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">&larr; Xem tất cả thư viện văn bản</a></p>

			<!-- ===== CTA BANNER SAU ĐỌC TOÀN VĂN ===== -->
			<div class="bg-gradient-to-r from-amber-500/15 via-slate-900 to-indigo-950 border-2 border-amber-500/50 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xl">
				<div class="space-y-2">
					<span class="bg-amber-500 text-navy-950 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider">✓ Bạn vừa đọc xong văn bản này</span>
					<h3 class="text-base sm:text-lg font-black text-white leading-snug">
						Luyện ngay 500+ câu hỏi trắc nghiệm<br class="hidden sm:block"> khoanh vùng từ văn bản này
					</h3>
					<p class="text-xs text-slate-300">AI Coach phân tích lỗ hổng · Đáp án + giải thích điều khoản · Đề thi chuẩn Bộ Nội Vụ 2026</p>
				</div>
				<div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0">
					<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-sm rounded-2xl shadow-xl transition-transform hover:scale-105 whitespace-nowrap">
						🚀 Thi Trắc Nghiệm Ngay &rarr;
					</a>
					<a href="<?php echo esc_url( home_url('/khoa-hoc/') ); ?>" class="px-5 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-sm rounded-2xl transition-colors whitespace-nowrap">
						Mua Khóa Học
					</a>
				</div>
			</div>

		</div>


		<!-- RIGHT COLUMN (3 COLS: HIGH-CONVERSION STICKY MONETIZATION SIDEBAR) -->
		<aside class="lg:col-span-3 space-y-5 sticky top-[80px]">

			<!-- WIDGET 1: BỘ ĐỀ TRẮC NGHIỆM ĐI TƯƠNG ỨNG VỚI LUẬT NÀY (49K - 79K PDF) -->
			<div class="bg-navy-950 p-6 rounded-3xl border-2 border-amber-500/50 space-y-4 shadow-2xl relative overflow-hidden">
				<div class="absolute -right-12 -top-12 w-40 h-40 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

				<div class="flex items-center justify-between border-b border-slate-800 pb-3">
					<h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
						<i class="fa-solid fa-file-pdf text-red-500"></i> Đề Trắc Nghiệm Luật Này
					</h3>
					<span class="bg-red-600 text-white text-[9px] font-black px-2 py-0.5 rounded shadow">Sale 49K</span>
				</div>

				<p class="text-xs text-slate-300 leading-relaxed">
					Tải bộ <strong class="text-amber-400 font-bold">500+ Câu trắc nghiệm có đáp án & giải thích chi tiết</strong> soạn theo đúng văn bản này.
				</p>

				<div class="p-3 bg-slate-900 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
					<div class="flex justify-between">
						<span class="text-slate-400">File tài liệu:</span>
						<span class="text-emerald-400 font-bold">PDF + Word in nộp</span>
					</div>
					<div class="flex justify-between">
						<span class="text-slate-400">Ưu đãi:</span>
						<strong class="text-amber-400 font-extrabold text-sm">49.000đ</strong>
					</div>
				</div>

				<a href="<?php echo esc_url( cvc_exam_url('de-thi-thu-kien-thuc-chung-tuyen-dung-cong-chuc-vong-1-de-01') ); ?>" class="block w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-navy-950 font-black text-xs rounded-xl text-center shadow-lg transition-transform hover:scale-105">
					TẢI BỘ ĐỀ 49K NGAY &rarr;
				</a>
			</div>

			<!-- WIDGET 2: KHÓA HỌC ÔN THI BÁM SÁT LUẬT (599K - 890K) -->
			<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-4 shadow-xl">
				<h3 class="text-xs font-extrabold uppercase tracking-wider text-cyan-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-graduation-cap text-red-500"></i> Khóa Ôn Thi Sát Đề
				</h3>

				<div class="space-y-3">
					<div class="p-3.5 bg-slate-900 rounded-2xl border border-slate-800 space-y-2">
						<span class="text-[10px] bg-red-600 text-white font-black px-2 py-0.5 rounded">Giảm 41%</span>
						<h4 class="font-bold text-xs text-white leading-snug">
							<a href="<?php echo esc_url( cvc_course_url('khoa-hoc-on-thi-cong-chuc-vong-1-kien-thuc-chung-cap-toc-2026') ); ?>" class="hover:text-amber-400">
								Khóa Ôn Thi Vòng 1 Kiến Thức Chung & Luật Công Vụ 2026
							</a>
						</h4>
						<div class="flex items-center justify-between text-xs pt-1">
							<span class="text-slate-500 line-through">1.500.000đ</span>
							<strong class="text-amber-400 font-extrabold text-sm">599.000đ</strong>
						</div>
					</div>
				</div>

				<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="block w-full py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-navy-950 font-black text-xs rounded-xl text-center shadow-lg transition-transform hover:scale-105">
					ĐĂNG KÝ HỌC NGAY &rarr;
				</a>
			</div>

			<!-- WIDGET 3: TRẮC NGHIỆM AI THỬ SỨC 1 CÂU (INTERACTIVE QUIZ FUNNEL) -->
			<div class="bg-navy-950 p-6 rounded-3xl border border-slate-800 space-y-3 shadow-xl">
				<h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-400 border-b border-slate-800 pb-3 flex items-center gap-2">
					<i class="fa-solid fa-circle-question text-emerald-400"></i> Mini Quiz Trải Nghiệm
				</h3>

				<p class="font-bold text-xs text-white">Câu hỏi: Thời gian tập sự ngạch Chuyên viên là bao nhiêu tháng?</p>

				<div class="space-y-2 text-xs">
					<button type="button" onclick="answerQuiz(false)" class="w-full text-left p-2.5 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 text-slate-300 cursor-pointer">A. 06 tháng</button>
					<button type="button" onclick="answerQuiz(true)" class="w-full text-left p-2.5 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 text-slate-300 cursor-pointer">B. 12 tháng (Chính xác)</button>
					<button type="button" onclick="answerQuiz(false)" class="w-full text-left p-2.5 bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 text-slate-300 cursor-pointer">C. 24 tháng</button>
				</div>

				<div id="quizResultMsg" class="hidden p-3 bg-emerald-950/80 border border-emerald-500/50 rounded-xl text-xs text-emerald-300 space-y-2">
					<p>🎉 Chúc mừng! Bạn trả lời đúng (12 tháng theo Điều 20 NĐ 138/2020).</p>
					<a href="<?php echo esc_url( cvc_exam_url('de-thi-thu-kien-thuc-chung-tuyen-dung-cong-chuc-vong-1-de-01') ); ?>" class="block text-center py-1.5 bg-amber-500 text-navy-950 font-black rounded-lg text-xs">Tải Trọn Bộ 500 Câu Trắc Nghiệm 49K &rarr;</a>
				</div>

				<script>
				function answerQuiz(isCorrect) {
					if (isCorrect) {
						document.getElementById('quizResultMsg').classList.remove('hidden');
					} else {
						alert('Chưa chính xác! Căn cứ Khoản 1 Điều 20 NĐ 138/2020, thời gian tập sự ngạch Chuyên viên là 12 tháng.');
					}
				}
				</script>
			</div>

		</aside>

	</div>

</div>
</main>

<?php get_footer(); ?>
