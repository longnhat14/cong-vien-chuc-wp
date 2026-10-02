<?php
/**
 * Ngân Hàng Câu Hỏi & Bài Thi Trắc Nghiệm Công Vụ 2026
 * Chuẩn hóa 100% cấu trúc câu hỏi & đáp án cho cả 3 môn thi Vòng 1:
 * - Kiến thức chung (60 câu)
 * - Ngoại ngữ Tiếng Anh B1/B2 (30 câu)
 * - Tin học văn phòng CNTT (30 câu)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CVC_Question_Bank_Fixtures {

	/**
	 * Môn 1: Kiến thức chung (Luật Cán bộ công chức, NĐ 138/2020, NĐ 30/2020, NĐ 06/2023...)
	 */
	public static function get_official_questions(): array {
		return array(
			array(
				'id' => 1,
				'question' => 'Căn cứ Luật Cán bộ, công chức (Hợp nhất 2026), việc đánh giá xếp loại chất lượng cán bộ, công chức được thực hiện theo chu kỳ nào?',
				'question_text' => 'Căn cứ Luật Cán bộ, công chức (Hợp nhất 2026), việc đánh giá xếp loại chất lượng cán bộ, công chức được thực hiện theo chu kỳ nào?',
				'options' => array(
					'A' => '6 tháng một lần',
					'B' => 'Hằng năm',
					'C' => '2 năm một lần',
					'D' => 'Theo nhiệm kỳ bầu cử',
				),
				'correct' => 'B',
				'explanation' => 'Căn cứ Điều 43 Luật Cán bộ, công chức: Việc đánh giá công chức được thực hiện hằng năm, trước khi quy hoạch, đào tạo, bồi dưỡng, bổ nhiệm, bổ nhiệm lại, luân chuyển, điều động.',
			),
			array(
				'id' => 2,
				'question' => 'Theo Nghị định 138/2020/NĐ-CP, thời gian tập sự đối với người trúng tuyển vào ngạch Chuyên viên và tương đương là bao nhiêu tháng?',
				'question_text' => 'Theo Nghị định 138/2020/NĐ-CP, thời gian tập sự đối với người trúng tuyển vào ngạch Chuyên viên và tương đương là bao nhiêu tháng?',
				'options' => array(
					'A' => '06 tháng',
					'B' => '09 tháng',
					'C' => '12 tháng',
					'D' => '18 tháng',
				),
				'correct' => 'C',
				'explanation' => 'Căn cứ Khoản 1 Điều 20 Nghị định 138/2020/NĐ-CP: Thời gian tập sự là 12 tháng đối với ngạch Chuyên viên và tương đương; 06 tháng đối với ngạch Cán sự và tương đương.',
			),
			array(
				'id' => 3,
				'question' => 'Theo Nghị định 30/2020/NĐ-CP về công tác văn thư, phông chữ bắt buộc trong soạn thảo văn bản hành chính là phông chữ nào?',
				'question_text' => 'Theo Nghị định 30/2020/NĐ-CP về công tác văn thư, phông chữ bắt buộc trong soạn thảo văn bản hành chính là phông chữ nào?',
				'options' => array(
					'A' => 'Phông chữ Arial, bộ mã ký tự TVN-3',
					'B' => 'Phông chữ Times New Roman, bộ mã ký tự Unicode',
					'C' => 'Phông chữ Calibri, bộ mã ký tự VNI-Windows',
					'D' => 'Tùy chọn phông chữ theo quy định của từng đơn vị',
				),
				'correct' => 'B',
				'explanation' => 'Căn cứ Phụ lục I Nghị định 30/2020/NĐ-CP: Phông chữ sử dụng soạn thảo văn bản hành chính là phông chữ tiếng Việt Times New Roman, bộ mã ký tự Unicode theo Tiêu chuẩn Việt Nam TCVN 6909:2001.',
			),
			array(
				'id' => 4,
				'question' => 'Căn cứ Nghị định 06/2023/NĐ-CP, kết quả kiểm định chất lượng đầu vào công chức có giá trị sử dụng trong thời hạn bao lâu trên toàn quốc?',
				'question_text' => 'Căn cứ Nghị định 06/2023/NĐ-CP, kết quả kiểm định chất lượng đầu vào công chức có giá trị sử dụng trong thời hạn bao lâu trên toàn quốc?',
				'options' => array(
					'A' => '12 tháng',
					'B' => '24 tháng',
					'C' => '36 tháng',
					'D' => 'Vĩnh viễn',
				),
				'correct' => 'B',
				'explanation' => 'Căn cứ Khoản 2 Điều 7 Nghị định 06/2023/NĐ-CP: Kết quả kiểm định chất lượng đầu vào công chức có giá trị sử dụng 24 tháng kể từ ngày ban hành quyết định công bố kết quả và có giá trị trên phạm vi toàn quốc.',
			),
			array(
				'id' => 5,
				'question' => 'Hình thức kỷ luật nào sau đây KHÔNG áp dụng đối với công chức không giữ chức vụ lãnh đạo, quản lý?',
				'question_text' => 'Hình thức kỷ luật nào sau đây KHÔNG áp dụng đối với công chức không giữ chức vụ lãnh đạo, quản lý?',
				'options' => array(
					'A' => 'Khiển trách',
					'B' => 'Cảnh cáo',
					'C' => 'Hạ bậc lương',
					'D' => 'Cách chức',
				),
				'correct' => 'D',
				'explanation' => 'Căn cứ Điều 79 Luật Cán bộ, công chức: Hình thức kỷ luật Giáng chức và Cách chức chỉ áp dụng đối với công chức giữ chức vụ lãnh đạo, quản lý.',
			),
			array(
				'id' => 6,
				'question' => 'Theo Nghị định 138/2020/NĐ-CP, bài thi Kiến thức chung tại Vòng 1 kỳ thi tuyển công chức gồm bao nhiêu câu hỏi và thời gian làm bài là bao nhiêu?',
				'question_text' => 'Theo Nghị định 138/2020/NĐ-CP, bài thi Kiến thức chung tại Vòng 1 kỳ thi tuyển công chức gồm bao nhiêu câu hỏi và thời gian làm bài là bao nhiêu?',
				'options' => array(
					'A' => '45 câu hỏi - 45 phút',
					'B' => '60 câu hỏi - 60 phút',
					'C' => '90 câu hỏi - 90 phút',
					'D' => '100 câu hỏi - 120 phút',
				),
				'correct' => 'B',
				'explanation' => 'Căn cứ Điều 8 Nghị định 138/2020/NĐ-CP: Bài thi Kiến thức chung Vòng 1 gồm 60 câu hỏi trắc nghiệm, thời gian thi 60 phút.',
			),
			array(
				'id' => 7,
				'question' => 'Điều kiện để thí sinh đạt Vòng 1 trong kỳ thi tuyển dụng công chức là trả lời đúng tối thiểu bao nhiêu % số câu hỏi cho từng phần thi?',
				'question_text' => 'Điều kiện để thí sinh đạt Vòng 1 trong kỳ thi tuyển dụng công chức là trả lời đúng tối thiểu bao nhiêu % số câu hỏi cho từng phần thi?',
				'options' => array(
					'A' => 'Đạt từ 40% số câu trả lời đúng trở lên',
					'B' => 'Đạt từ 50% số câu trả lời đúng trở lên',
					'C' => 'Đạt từ 60% số câu trả lời đúng trở lên',
					'D' => 'Đạt từ 75% số câu trả lời đúng trở lên',
				),
				'correct' => 'B',
				'explanation' => 'Căn cứ Điều 8 Nghị định 138/2020/NĐ-CP: Kết quả Vòng 1 được xác định theo số câu trả lời đúng cho từng phần thi, nếu trả lời đúng từ 50% số câu hỏi trở lên cho từng phần thi thì được dự thi tiếp Vòng 2.',
			),
			array(
				'id' => 8,
				'question' => 'Theo Nghị định 30/2020/NĐ-CP, khổ giấy quy định chuẩn cho văn bản hành chính là khổ giấy nào?',
				'question_text' => 'Theo Nghị định 30/2020/NĐ-CP, khổ giấy quy định chuẩn cho văn bản hành chính là khổ giấy nào?',
				'options' => array(
					'A' => 'Khổ A3 (297 mm x 420 mm)',
					'B' => 'Khổ A4 (210 mm x 297 mm)',
					'C' => 'Khổ A5 (148 mm x 210 mm)',
					'D' => 'Tùy thuộc định dạng văn bản',
				),
				'correct' => 'B',
				'explanation' => 'Căn cứ Điều 5 Nghị định 30/2020/NĐ-CP: Văn bản hành chính được trình bày trên khổ giấy A4 (210 mm x 297 mm).',
			),
			array(
				'id' => 9,
				'question' => 'Đâu là nghĩa vụ của cán bộ, công chức đối với Đảng, Nhà nước và Nhân dân?',
				'question_text' => 'Đâu là nghĩa vụ của cán bộ, công chức đối với Đảng, Nhà nước và Nhân dân?',
				'options' => array(
					'A' => 'Trung thành với Đảng Cộng sản Việt Nam, Nhà nước Cộng hòa xã hội chủ nghĩa Việt Nam',
					'B' => 'Được bảo đảm điều kiện làm việc và hưởng tiền lương',
					'C' => 'Được nghỉ hằng năm, nghỉ lễ theo quy định',
					'D' => 'Được mở doanh nghiệp tư nhân đứng tên cá nhân',
				),
				'correct' => 'A',
				'explanation' => 'Căn cứ Điều 8 Luật Cán bộ, công chức: Cán bộ, công chức có nghĩa vụ trung thành với Đảng, Nhà nước, bảo vệ danh dự Tổ quốc và lợi ích quốc gia.',
			),
			array(
				'id' => 10,
				'question' => 'Theo quy định hiện hành, ngạch công chức nào sau đây là ngạch cao nhất trong hệ thống ngạch công chức?',
				'question_text' => 'Theo quy định hiện hành, ngạch công chức nào sau đây là ngạch cao nhất trong hệ thống ngạch công chức?',
				'options' => array(
					'A' => 'Chuyên viên',
					'B' => 'Chuyên viên chính',
					'C' => 'Chuyên viên cao cấp',
					'D' => 'Chuyên viên đặc biệt',
				),
				'correct' => 'C',
				'explanation' => 'Căn cứ Điều 42 Luật Cán bộ, công chức: Ngạch công chức gồm Chuyên viên cao cấp, Chuyên viên chính, Chuyên viên, Cán sự, Nhân viên. Trong đó Chuyên viên cao cấp là ngạch cao nhất.',
			),
		);
	}

	/**
	 * Môn 2: Ngoại ngữ Tiếng Anh (Chuẩn B1/B2 Ôn thi Công chức Vòng 1)
	 */
	public static function get_english_questions(): array {
		return array(
			array(
				'id' => 101,
				'question' => 'Choose the correct word to complete the sentence: "The new civil service regulations ________ into force next month."',
				'question_text' => 'Choose the correct word to complete the sentence: "The new civil service regulations ________ into force next month."',
				'options' => array(
					'A' => 'will come',
					'B' => 'came',
					'C' => 'has come',
					'D' => 'coming',
				),
				'correct' => 'A',
				'explanation' => 'Cấu trúc thì Tương lai đơn "will come" dùng cho sự kiện pháp lý xảy ra trong tương lai ("next month").',
			),
			array(
				'id' => 102,
				'question' => 'Select the synonym for "REQUIREMENT" in administrative recruitment context:',
				'question_text' => 'Select the synonym for "REQUIREMENT" in administrative recruitment context:',
				'options' => array(
					'A' => 'Prerequisite / Qualification',
					'B' => 'Entertainment',
					'C' => 'Suggestion',
					'D' => 'Hesitation',
				),
				'correct' => 'A',
				'explanation' => '"Requirement" trong văn cảnh tuyển dụng công chức có nghĩa là "Tiêu chuẩn / Điều kiện tiên quyết" (Prerequisite / Qualification).',
			),
			array(
				'id' => 103,
				'question' => 'Fill in the blank: "Candidates must submit their application forms ________ 5:00 PM on October 30th."',
				'question_text' => 'Fill in the blank: "Candidates must submit their application forms ________ 5:00 PM on October 30th."',
				'options' => array(
					'A' => 'before',
					'B' => 'during',
					'C' => 'between',
					'D' => 'since',
				),
				'correct' => 'A',
				'explanation' => 'Giới từ "before" dùng để chỉ mốc thời gian hạn chót nộp hồ sơ ("trước 5 giờ chiều").',
			),
			array(
				'id' => 104,
				'question' => 'Choose the correct passive voice sentence: "The government issued the new decree yesterday."',
				'question_text' => 'Choose the correct passive voice sentence: "The government issued the new decree yesterday."',
				'options' => array(
					'A' => 'The new decree was issued by the government yesterday.',
					'B' => 'The new decree is issued by the government yesterday.',
					'C' => 'The new decree has been issued yesterday.',
					'D' => 'The new decree will be issued yesterday.',
				),
				'correct' => 'A',
				'explanation' => 'Câu bị động Quá khứ đơn: S + was/were + V3/ed ("was issued").',
			),
			array(
				'id' => 105,
				'question' => 'What is the meaning of "CIVIL SERVANT" in Vietnamese?',
				'question_text' => 'What is the meaning of "CIVIL SERVANT" in Vietnamese?',
				'options' => array(
					'A' => 'Công chức nhà nước',
					'B' => 'Doanh nhân tự do',
					'C' => 'Sinh viên tốt nghiệp',
					'D' => 'Kỹ sư công nghệ',
				),
				'correct' => 'A',
				'explanation' => '"Civil servant" là thuật ngữ tiếng Anh chuẩn chỉ "Công chức / Cán bộ công quyền trong bộ máy nhà nước".',
			),
			array(
				'id' => 106,
				'question' => 'Complete the sentence: "If she ________ the B1 English certificate, she will be eligible for Round 2."',
				'question_text' => 'Complete the sentence: "If she ________ the B1 English certificate, she will be eligible for Round 2."',
				'options' => array(
					'A' => 'passes',
					'B' => 'passed',
					'C' => 'would pass',
					'D' => 'passing',
				),
				'correct' => 'A',
				'explanation' => 'Câu điều kiện Loại 1: If + S + V(hiện tại đơn), S + will + V-bare.',
			),
			array(
				'id' => 107,
				'question' => 'Which word is CLOSEST in meaning to "IMPLEMENT"?',
				'question_text' => 'Which word is CLOSEST in meaning to "IMPLEMENT"?',
				'options' => array(
					'A' => 'Carry out / Execute',
					'B' => 'Cancel',
					'C' => 'Delay',
					'D' => 'Refuse',
				),
				'correct' => 'A',
				'explanation' => '"Implement" có nghĩa là "Thực thi / Triển khai thi hành" tương đương với "Carry out" hoặc "Execute".',
			),
			array(
				'id' => 108,
				'question' => 'Choose the word with the correct spelling:',
				'question_text' => 'Choose the word with the correct spelling:',
				'options' => array(
					'A' => 'Administration',
					'B' => 'Administrationn',
					'C' => 'Admnistrasion',
					'D' => 'Admnistration',
				),
				'correct' => 'A',
				'explanation' => 'Từ đúng chính tả là "Administration" (Hành chính / Quản trị nhà nước).',
			),
			array(
				'id' => 109,
				'question' => 'Complete the sentence: "Public employees are expected to maintain high moral ________ at work."',
				'question_text' => 'Complete the sentence: "Public employees are expected to maintain high moral ________ at work."',
				'options' => array(
					'A' => 'standards',
					'B' => 'standardize',
					'C' => 'standardly',
					'D' => 'substandard',
				),
				'correct' => 'A',
				'explanation' => 'Sau tính từ "moral" cần một danh từ số nhiều "standards" (các chuẩn mực đạo đức công vụ).',
			),
			array(
				'id' => 110,
				'question' => 'Select the correct question tag: "The recruitment announcement was published online, ________?"',
				'question_text' => 'Select the correct question tag: "The recruitment announcement was published online, ________?"',
				'options' => array(
					'A' => 'wasn\'t it',
					'B' => 'isn\'t it',
					'C' => 'was it',
					'D' => 'doesn\'t it',
				),
				'correct' => 'A',
				'explanation' => 'Mệnh đề chính dùng "was published" (khẳng định), nên câu hỏi đuôi tương ứng là "wasn\'t it?".',
			),
		);
	}

	/**
	 * Môn 3: Tin học Văn phòng (Chuẩn CNTT Cơ Bản Ôn thi Vòng 1)
	 */
	public static function get_it_questions(): array {
		return array(
			array(
				'id' => 201,
				'question' => 'Trong Microsoft Word, tổ hợp phím nào dùng để căn giữa đoạn văn bản (Center Alignment)?',
				'question_text' => 'Trong Microsoft Word, tổ hợp phím nào dùng để căn giữa đoạn văn bản (Center Alignment)?',
				'options' => array(
					'A' => 'Ctrl + E',
					'B' => 'Ctrl + L',
					'C' => 'Ctrl + R',
					'D' => 'Ctrl + J',
				),
				'correct' => 'A',
				'explanation' => 'Tổ hợp phím Ctrl + E dùng để căn giữa; Ctrl + L căn trái; Ctrl + R căn phải; Ctrl + J căn đều hai bên.',
			),
			array(
				'id' => 202,
				'question' => 'Trong Microsoft Excel, hàm nào dùng để tính tổng các giá trị thỏa mãn một điều kiện cho trước?',
				'question_text' => 'Trong Microsoft Excel, hàm nào dùng để tính tổng các giá trị thỏa mãn một điều kiện cho trước?',
				'options' => array(
					'A' => 'SUMIF',
					'B' => 'COUNTIF',
					'C' => 'AVERAGEIF',
					'D' => 'VLOOKUP',
				),
				'correct' => 'A',
				'explanation' => 'Hàm SUMIF tính tổng theo điều kiện; COUNTIF đếm theo điều kiện; AVERAGEIF tính trung bình cộng theo điều kiện.',
			),
			array(
				'id' => 203,
				'question' => 'Trong hệ điều hành Windows, phông chữ chuẩn Unicode tiếng Việt theo TCVN 6909:2001 áp dụng cho văn bản hành chính là phông chữ nào?',
				'question_text' => 'Trong hệ điều hành Windows, phông chữ chuẩn Unicode tiếng Việt theo TCVN 6909:2001 áp dụng cho văn bản hành chính là phông chữ nào?',
				'options' => array(
					'A' => 'Times New Roman',
					'B' => 'VNI-Times',
					'C' => '.VNTime',
					'D' => 'Tahoma',
				),
				'correct' => 'A',
				'explanation' => 'Căn cứ Phụ lục I Nghị định 30/2020/NĐ-CP: Phông chữ chuẩn bộ mã Unicode tiếng Việt là Times New Roman.',
			),
			array(
				'id' => 204,
				'question' => 'Địa chỉ email nào sau đây có định dạng chuẩn hợp lệ của các cơ quan nhà nước Việt Nam?',
				'question_text' => 'Địa chỉ email nào sau đây có định dạng chuẩn hợp lệ của các cơ quan nhà nước Việt Nam?',
				'options' => array(
					'A' => 'noivu@daklak.gov.vn',
					'B' => 'noivu_daklak@gmail.com',
					'C' => 'noivu.daklak@yahoo.com',
					'D' => 'noivu_daklak.com.vn',
				),
				'correct' => 'A',
				'explanation' => 'Email chính thức của cơ quan nhà nước Việt Nam bắt buộc có tên miền cấp cao nhất là `.gov.vn`.',
			),
			array(
				'id' => 205,
				'question' => 'Trong Microsoft Excel, địa chỉ ô `$A$1` được gọi là loại địa chỉ nào?',
				'question_text' => 'Trong Microsoft Excel, địa chỉ ô `$A$1` được gọi là loại địa chỉ nào?',
				'options' => array(
					'A' => 'Địa chỉ tuyệt đối',
					'B' => 'Địa chỉ tương đối',
					'C' => 'Địa chỉ hỗn hợp',
					'D' => 'Địa chỉ mảng',
				),
				'correct' => 'A',
				'explanation' => 'Địa chỉ có dấu `$` trước cả tên cột và tên dòng `$A$1` là địa chỉ tuyệt đối, không thay đổi khi sao chép công thức.',
			),
		);
	}
}
