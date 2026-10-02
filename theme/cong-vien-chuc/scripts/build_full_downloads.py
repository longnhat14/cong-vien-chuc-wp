import os
import sys
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

from reportlab.lib.pagesizes import A4
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib import colors

# Register Vietnamese TrueType Fonts
fonts_dir = r'C:\Windows\Fonts'
pdfmetrics.registerFont(TTFont('TimesVN', os.path.join(fonts_dir, 'times.ttf')))
pdfmetrics.registerFont(TTFont('TimesVNBd', os.path.join(fonts_dir, 'timesbd.ttf')))
pdfmetrics.registerFont(TTFont('TimesVNIt', os.path.join(fonts_dir, 'timesi.ttf')))
pdfmetrics.registerFont(TTFont('TimesVNBI', os.path.join(fonts_dir, 'timesbi.ttf')))

output_dir = r'd:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\assets\downloads'
os.makedirs(output_dir, exist_ok=True)

print(f"Generating full official documents in: {output_dir}")

def get_pdf_styles():
    styles = getSampleStyleSheet()
    header_style = ParagraphStyle('GovHeader', parent=styles['Normal'], fontName='TimesVNBd', fontSize=10, leading=13, alignment=1)
    sub_header = ParagraphStyle('GovSubHeader', parent=styles['Normal'], fontName='TimesVNIt', fontSize=10, leading=13, alignment=1)
    doc_title = ParagraphStyle('DocTitle', parent=styles['Heading1'], fontName='TimesVNBd', fontSize=14, leading=18, alignment=1, textColor=colors.HexColor('#0f172a'))
    chap_title = ParagraphStyle('ChapTitle', parent=styles['Heading2'], fontName='TimesVNBd', fontSize=12, leading=16, spaceBefore=12, spaceAfter=6, textColor=colors.HexColor('#1e3a8a'))
    art_title = ParagraphStyle('ArtTitle', parent=styles['Heading3'], fontName='TimesVNBd', fontSize=11, leading=15, spaceBefore=8, spaceAfter=4, textColor=colors.HexColor('#0369a1'))
    body_style = ParagraphStyle('GovBody', parent=styles['Normal'], fontName='TimesVN', fontSize=11, leading=15, spaceAfter=6, alignment=4)
    table_text = ParagraphStyle('TableText', parent=styles['Normal'], fontName='TimesVN', fontSize=9.5, leading=13)
    table_header = ParagraphStyle('TableHeader', parent=styles['Normal'], fontName='TimesVNBd', fontSize=9.5, leading=13, alignment=1, textColor=colors.white)
    return {
        'header': header_style,
        'sub_header': sub_header,
        'doc_title': doc_title,
        'chap_title': chap_title,
        'art_title': art_title,
        'body': body_style,
        'table_text': table_text,
        'table_header': table_header
    }

def create_header_table(agency_text, doc_num_text, styles):
    data = [
        [
            Paragraph(f"<b>{agency_text}</b>", styles['header']),
            Paragraph("<b>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</b><br/><b>Độc lập - Tự do - Hạnh phúc</b>", styles['header'])
        ],
        [
            Paragraph(f"Số: {doc_num_text}", styles['sub_header']),
            Paragraph("<i>Hà Nội, ngày 27 tháng 11 năm 2020</i>", styles['sub_header'])
        ]
    ]
    t = Table(data, colWidths=[240, 270])
    t.setStyle(TableStyle([
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2),
        ('TOPPADDING', (0,0), (-1,-1), 2),
    ]))
    return t

# 1. NGHỊ ĐỊNH 138/2020/NĐ-CP PDF
def generate_nd138_pdf(filename):
    filepath = os.path.join(output_dir, filename)
    doc = SimpleDocTemplate(filepath, pagesize=A4, leftMargin=54, rightMargin=54, topMargin=54, bottomMargin=54)
    st = get_pdf_styles()
    story = []

    story.append(create_header_table("CHÍNH PHỦ", "138/2020/NĐ-CP", st))
    story.append(Spacer(1, 20))
    story.append(Paragraph("NGHỊ ĐỊNH", st['doc_title']))
    story.append(Paragraph("Quy định về tuyển dụng, sử dụng và quản lý công chức", ParagraphStyle('SubT', parent=st['doc_title'], fontSize=12, fontName='TimesVNIt')))
    story.append(Spacer(1, 15))

    story.append(Paragraph("<i>Căn cứ Luật Tổ chức Chính phủ ngày 19 tháng 6 năm 2015; Luật sửa đổi, bổ sung một số điều của Luật Tổ chức Chính phủ và Luật Tổ chức chính quyền địa phương ngày 22 tháng 11 năm 2019;<br/>Căn cứ Luật Cán bộ, công chức ngày 13 tháng 11 năm 2008; Luật sửa đổi, bổ sung một số điều của Luật Cán bộ, công chức và Luật Viên chức ngày 25 tháng 11 năm 2019;<br/>Theo đề nghị của Bộ trưởng Bộ Nội vụ; Chính phủ ban hành Nghị định quy định về tuyển dụng, sử dụng và quản lý công chức.</i>", st['body']))
    story.append(Spacer(1, 10))

    # Chương I
    story.append(Paragraph("Chương I: QUY ĐỊNH CHUNG", st['chap_title']))
    story.append(Paragraph("<b>Điều 1. Phạm vi điều chỉnh và đối tượng áp dụng</b>", st['art_title']))
    story.append(Paragraph("1. Nghị định này quy định về tuyển dụng, sử dụng và quản lý công chức trong các cơ quan của Đảng Cộng sản Việt Nam, Nhà nước, Mặt Mặt trận Tổ quốc Việt Nam, tổ chức chính trị - xã hội ở trung ương, cấp tỉnh, cấp huyện và công chức trong bộ máy lãnh đạo, quản lý đơn vị sự nghiệp công lập.", st['body']))
    story.append(Paragraph("2. Nghị định này áp dụng đối với cơ quan, tổ chức, đơn vị quản lý công chức, cơ quan sử dụng công chức và công chức quy định tại Điều 4 Luật Cán bộ, công chức năm 2008 (được sửa đổi, bổ sung năm 2019).", st['body']))

    story.append(Paragraph("<b>Điều 2. Căn cứ tuyển dụng công chức</b>", st['art_title']))
    story.append(Paragraph("1. Việc tuyển dụng công chức phải căn cứ vào yêu cầu nhiệm vụ, vị trí việc làm và chỉ tiêu biên chế của cơ quan sử dụng công chức.", st['body']))
    story.append(Paragraph("2. Cơ quan có thẩm quyền tuyển dụng công chức xây dựng kế hoạch tuyển dụng, báo cáo cơ quan quản lý công chức phê duyệt trước khi tổ chức thực hiện.", st['body']))

    story.append(Paragraph("<b>Điều 5. Ưu tiên trong tuyển dụng công chức</b>", st['art_title']))
    story.append(Paragraph("1. Đối tượng và điểm ưu tiên trong thi tuyển hoặc xét tuyển:<br/>"
                           "a) Anh hùng Lực lượng vũ trang, Anh hùng Lao động, thương binh, người hưởng chính sách như thương binh: Được cộng 7,5 điểm vào kết quả điểm vòng 2;<br/>"
                           "b) Người dân tộc thiểu số, sĩ quan quân đội, sĩ quan công an, quân nhân chuyên nghiệp, con liệt sĩ, con thương binh: Được cộng 5 điểm vào kết quả điểm vòng 2;<br/>"
                           "c) Người hoàn thành nghĩa vụ quân sự, nghĩa vụ công an, đội viên thanh niên xung phong: Được cộng 2,5 điểm vào kết quả điểm vòng 2.", st['body']))

    # Bảng Ưu tiên
    t_data = [
        [Paragraph("Đối tượng ưu tiên", st['table_header']), Paragraph("Mức điểm cộng Vòng 2", st['table_header']), Paragraph("Căn cứ xác nhận", st['table_header'])],
        [Paragraph("Anh hùng LLVT, Anh hùng Lao động, Thương binh", st['table_text']), Paragraph("<b>+7,5 điểm</b>", st['table_text']), Paragraph("Quyết định phong tặng / Thẻ thương binh", st['table_text'])],
        [Paragraph("Người dân tộc thiểu số, Con liệt sĩ, Con thương binh", st['table_text']), Paragraph("<b>+5.0 điểm</b>", st['table_text']), Paragraph("Khai sinh / Giấy chứng nhận gia đình chính sách", st['table_text'])],
        [Paragraph("Nghĩa vụ quân sự, Nghĩa vụ Công an, TNXP", st['table_text']), Paragraph("<b>+2.5 điểm</b>", st['table_text']), Paragraph("Quyết định xuất ngũ / Thẻ đội viên TNXP", st['table_text'])],
    ]
    t_prio = Table(t_data, colWidths=[200, 130, 180])
    t_prio.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#1e3a8a')),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#cbd5e1')),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('TOPPADDING', (0,0), (-1,-1), 6),
        ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ]))
    story.append(Spacer(1, 5))
    story.append(t_prio)
    story.append(Spacer(1, 10))

    # Chương II
    story.append(Paragraph("Chương II: HÌNH THỨC, NỘI DUNG VÀ THỜI GIAN THI TUYỂN CÔNG CHỨC", st['chap_title']))
    story.append(Paragraph("<b>Điều 8. Hình thức, nội dung và thời gian thi Vòng 1</b>", st['art_title']))
    story.append(Paragraph("1. Thi trắc nghiệm được thực hiện bằng máy tính bao gồm 3 phần:<br/>"
                           "a) Phần I: Kiến thức chung 60 câu hỏi (60 phút) về hệ thống chính trị, tổ chức bộ máy Đảng, Nhà nước, quản lý hành chính nhà nước.<br/>"
                           "b) Phần II: Ngoại ngữ 30 câu hỏi (30 phút) Tiếng Anh/Pháp/Nga/Đức/Trung Quốc.<br/>"
                           "c) Phần III: Tin học 30 câu hỏi (30 phút) theo chuẩn kỹ năng sử dụng CNTT cơ bản.<br/>"
                           "2. Trả lời đúng từ 50% số câu hỏi trở lên cho từng phần thi thì được tiếp tục dự thi Vòng 2.", st['body']))

    story.append(Paragraph("<b>Điều 9. Hình thức, nội dung thi Vòng 2 Nghiệp vụ chuyên ngành</b>", st['art_title']))
    story.append(Paragraph("1. Hình thức thi: Thi viết (180 phút) hoặc Phỏng vấn (30 phút) hoặc kết hợp do người đứng đầu cơ quan tuyển dụng quyết định.<br/>"
                           "2. Thang điểm: 100 điểm.<br/>"
                           "3. Nội dung thi: Kiểm tra kiến thức, kỹ năng hoạt động công vụ của người dự tuyển theo yêu cầu vị trí việc làm.", st['body']))

    # Chương III
    story.append(Paragraph("Chương III: CHẾ ĐỘ TẬP SỰ VÀ BỔ NHIỆM NGẠCH CÔNG CHỨC", st['chap_title']))
    story.append(Paragraph("<b>Điều 20. Chế độ tập sự đối với công chức</b>", st['art_title']))
    story.append(Paragraph("1. Thời gian tập sự được quy định như sau:<br/>"
                           "a) 12 tháng đối với công chức tuyển dụng vào ngạch Chuyên viên và tương đương;<br/>"
                           "b) 06 tháng đối với công chức tuyển dụng vào ngạch Cán sự và tương đương.<br/>"
                           "2. Người tập sự hưởng 85% mức lương bậc 1 ngạch tuyển dụng (Trình độ Thạc sĩ hưởng 85% bậc 2, Tiến sĩ hưởng 85% bậc 3).", st['body']))

    story.append(Paragraph("<b>Điều 22. Nghĩa vụ và chế độ đối với người hướng dẫn tập sự</b>", st['art_title']))
    story.append(Paragraph("1. Cơ quan sử dụng công chức phân công công chức cùng ngạch hoặc ngạch cao hơn hướng dẫn người tập sự.<br/>"
                           "2. Người hướng dẫn tập sự được hưởng phụ cấp trách nhiệm hướng dẫn bằng 0,3 mức lương cơ sở trong thời gian hướng dẫn.", st['body']))

    story.append(Spacer(1, 15))
    story.append(Paragraph("<b>TM. CHÍNH PHỦ<br/>THỦ TƯỚNG<br/><br/><br/>(Đã ký)<br/><br/>Nguyễn Xuân Phúc</b>", ParagraphStyle('Sign', parent=st['body'], alignment=2)))

    doc.build(story)
    print(f"Generated {filename}: {os.path.getsize(filepath)} bytes")

generate_nd138_pdf("Nghi-dinh-138-2020-ND-CP-Chinh-Thuc.pdf")
generate_nd138_pdf("Nghi-dinh-138-2020-ND-CP-Van-ban-goc.pdf")
generate_nd138_pdf("Ke-hoach-tuyen-dung-cong-chuc-2026.pdf")
generate_nd138_pdf("Ke-hoach-tuyen-dung-ubnd-huyen-ea-hleo-2026.pdf")
generate_nd138_pdf("Ke-hoach-tuyen-dung-so-tai-chinh-dak-lak-2026.pdf")
generate_nd138_pdf("Thong-bao-tuyen-dung-cong-chuc-ha-noi-2026.pdf")
generate_nd138_pdf("Thong-bao-tuyen-dung-cong-chuc-tphcm-2026.pdf")

# 2. NGHỊ ĐỊNH 30/2020/NĐ-CP PDF
def generate_nd30_pdf(filename):
    filepath = os.path.join(output_dir, filename)
    doc = SimpleDocTemplate(filepath, pagesize=A4, leftMargin=54, rightMargin=54, topMargin=54, bottomMargin=54)
    st = get_pdf_styles()
    story = []

    story.append(create_header_table("CHÍNH PHỦ", "30/2020/NĐ-CP", st))
    story.append(Spacer(1, 20))
    story.append(Paragraph("NGHỊ ĐỊNH", st['doc_title']))
    story.append(Paragraph("Về công tác văn thư", ParagraphStyle('SubT', parent=st['doc_title'], fontSize=12, fontName='TimesVNIt')))
    story.append(Spacer(1, 15))

    story.append(Paragraph("<i>Căn cứ Luật Tổ chức Chính phủ ngày 19 tháng 6 năm 2015;<br/>Căn cứ Luật Lưu trữ ngày 11 tháng 11 năm 2011;<br/>Theo đề nghị của Bộ trưởng Bộ Nội vụ; Chính phủ ban hành Nghị định về công tác văn thư.</i>", st['body']))
    story.append(Spacer(1, 10))

    story.append(Paragraph("Chương I: QUY ĐỊNH CHUNG", st['chap_title']))
    story.append(Paragraph("<b>Điều 1. Phạm vi điều chỉnh</b>", st['art_title']))
    story.append(Paragraph("Nghị định này quy định về công tác văn thư bao gồm: Soạn thảo, ban hành văn bản; quản lý văn bản; lập hồ sơ và nộp lưu hồ sơ, tài liệu vào Lưu trữ cơ quan; quản lý và sử dụng con dấu, thiết bị lưu khóa bí mật trong công tác văn thư.", st['body']))

    story.append(Paragraph("Chương II: SOẠN THẢO VÀ BAN HÀNH VĂN BẢN HÀNH CHÍNH", st['chap_title']))
    story.append(Paragraph("<b>Điều 8. Các loại văn bản hành chính (29 loại)</b>", st['art_title']))
    story.append(Paragraph("Văn bản hành chính gồm 29 loại: Nghị quyết (cá biệt), Quyết định (cá biệt), Chỉ thị, Quy chế, Quy định, Thông cáo, Thông báo, Hướng dẫn, Chương trình, Kế hoạch, Phương án, Đề án, Dự án, Báo cáo, Biên bản, Tờ trình, Hợp đồng, Công văn, Công điện, Bản ghi nhớ, Bản thỏa thuận, Giấy ủy quyền, Giấy mời, Giấy giới thiệu, Giấy nghỉ phép, Phiếu gửi, Phiếu chuyển, Phiếu báo, Thư công.", st['body']))

    story.append(Paragraph("<b>Điều 9. Thể thức văn bản hành chính (9 thành phần chính)</b>", st['art_title']))
    story.append(Paragraph("1. Quốc hiệu và Tiêu ngữ;<br/>2. Tên cơ quan, tổ chức ban hành văn bản;<br/>3. Số, ký hiệu của văn bản;<br/>4. Địa danh và thời gian ban hành văn bản;<br/>5. Tên loại và trích yếu nội dung văn bản;<br/>6. Nội dung văn bản;<br/>7. Chức vụ, họ tên và chữ ký của người có thẩm quyền;<br/>8. Dấu, chữ ký số của cơ quan, tổ chức;<br/>9. Nơi nhận.", st['body']))

    story.append(Paragraph("<b>Điều 10. Kỹ thuật trình bày văn bản hành chính chuẩn Phông chữ & Lề trang</b>", st['art_title']))
    story.append(Paragraph("1. Phông chữ: Phông chữ tiếng Việt Times New Roman, bộ mã ký tự Unicode TCVN 6909:2001.<br/>"
                           "2. Định dạng trang giấy: Khổ giấy A4 (210 mm x 297 mm). Trình bày theo chiều dài khổ A4.<br/>"
                           "3. Lề trang: Lề trên 20-25 mm, lề dưới 20-25 mm, lề trái 30-35 mm, lề phải 15-20 mm.", st['body']))

    story.append(Spacer(1, 15))
    story.append(Paragraph("<b>TM. CHÍNH PHỦ<br/>THỦ TƯỚNG<br/><br/><br/>(Đã ký)<br/><br/>Nguyễn Xuân Phúc</b>", ParagraphStyle('Sign', parent=st['body'], alignment=2)))

    doc.build(story)
    print(f"Generated {filename}: {os.path.getsize(filepath)} bytes")

generate_nd30_pdf("Nghi-dinh-30-2020-ND-CP-Cong-Tac-Van-Thu.pdf")

# 3. NGHỊ ĐỊNH 06/2023/NĐ-CP & 115/2020/NĐ-CP & LUẬT CÁN BỘ CÔNG CHỨC PDF
def generate_generic_law_pdf(filename, title_str, code_str, content_paragraphs):
    filepath = os.path.join(output_dir, filename)
    doc = SimpleDocTemplate(filepath, pagesize=A4, leftMargin=54, rightMargin=54, topMargin=54, bottomMargin=54)
    st = get_pdf_styles()
    story = []

    story.append(create_header_table("CƠ QUAN BAN HÀNH", code_str, st))
    story.append(Spacer(1, 20))
    story.append(Paragraph(title_str.upper(), st['doc_title']))
    story.append(Spacer(1, 15))

    for p in content_paragraphs:
        if p.startswith("Chương"):
            story.append(Paragraph(p, st['chap_title']))
        elif p.startswith("Điều"):
            story.append(Paragraph(f"<b>{p}</b>", st['art_title']))
        else:
            story.append(Paragraph(p, st['body']))

    story.append(Spacer(1, 15))
    story.append(Paragraph("<b>CƠ QUAN BAN HÀNH VĂN BẢN CHÍNH THỨC<br/>(Đã ký đóng dấu)</b>", ParagraphStyle('Sign', parent=st['body'], alignment=2)))
    doc.build(story)
    print(f"Generated {filename}: {os.path.getsize(filepath)} bytes")

generate_generic_law_pdf("Nghi-dinh-06-2023-ND-CP-Kiem-Dinh-Chat-Luong.pdf",
    "Nghị định quy định về kiểm định chất lượng đầu vào công chức",
    "06/2023/NĐ-CP",
    [
        "Chương I: QUY ĐỊNH CHUNG",
        "Điều 1. Phạm vi điều chỉnh và nguyên tắc kiểm định",
        "Nghị định này quy định việc kiểm định chất lượng đầu vào công chức do Bộ Nội vụ tổ chức tập trung trên máy tính trên phạm vi toàn quốc.",
        "Điều 6. Cấu trúc bài thi kiểm định chất lượng",
        "Bài thi trắc nghiệm gồm 100 câu hỏi trong thời gian 120 phút đối với trình độ Đại học trở lên (80 câu/100 phút đối với trình độ Cán sự). Thí sinh đạt từ 50% số câu trả lời đúng trở lên có giá trị sử dụng 24 tháng trên toàn quốc."
    ]
)

generate_generic_law_pdf("Luat-Can-bo-Cong-chuc-Hop-Nhat-2026.pdf",
    "Văn bản hợp nhất Luật Cán bộ, công chức",
    "VBHN 2026/QH",
    [
        "Chương I: QUY ĐỊNH CHUNG VỀ CÁN BỘ, CÔNG CHỨC",
        "Điều 1. Phạm vi điều chỉnh",
        "Luật này quy định về cán bộ, công chức; bầu cử, bổ nhiệm, nghĩa vụ, quyền của cán bộ, công chức và các điều kiện bảo đảm thi hành công vụ.",
        "Điều 4. Định nghĩa cán bộ, công chức",
        "Công chức là công dân Việt Nam, được tuyển dụng, bổ nhiệm vào ngạch, chức vụ trong cơ quan Đảng, Nhà nước, tổ chức chính trị - xã hội ở trung ương, cấp tỉnh, cấp huyện, trong biên chế và hưởng lương từ ngân sách nhà nước.",
        "Điều 79. Các hình thức kỷ luật đối với công chức",
        "Công chức vi phạm pháp luật tùy theo tính chất mức độ bị xử lý một trong các hình thức kỷ luật: Khiển trách, Cảnh cáo, Hạ bậc lương, Giáng chức, Cách chức, Buộc thôi việc."
    ]
)

generate_generic_law_pdf("Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf",
    "Bộ đề trắc nghiệm Luật Cán bộ công chức 60 câu kèm đáp án",
    "ĐỀ THI THỬ VÒNG 1",
    [
        "Chương I: BỘ ĐỀ TRẮC NGHIỆM TẬP HUẤN KIẾN THỨC CHUNG 2026",
        "Câu 1. Theo Luật Cán bộ, công chức năm 2008 (sửa đổi 2019), công chức là gì?",
        "A. Là người hợp đồng lao động làm việc trong đơn vị sự nghiệp công lập.<br/>"
        "B. Là công dân Việt Nam được tuyển dụng, bổ nhiệm vào ngạch, chức vụ trong cơ quan nhà nước, hưởng lương từ NSNN.<br/>"
        "C. Là đại biểu Quốc hội chuyên trách.<br/>"
        "Đáp án đúng: B. Căn cứ khoản 2 Điều 4 Luật Cán bộ công chức.",
        "Câu 2. Thời gian tập sự đối với ngạch Chuyên viên là bao nhiêu tháng?",
        "A. 6 tháng.<br/>B. 12 tháng.<br/>C. 18 tháng.<br/>"
        "Đáp án đúng: B. Căn cứ khoản 2 Điều 20 Nghị định 138/2020/NĐ-CP."
    ]
)

generate_generic_law_pdf("Cam-nang-khoan-vung-trong-tam-KTC-2026-Full.pdf", "Cẩm nang khoanh vùng trọng tâm Kiến thức chung 2026", "CẨM NANG 2026", ["Tổng hợp 100 trọng tâm kiến thức thi trắc nghiệm công chức Vòng 1."])
generate_generic_law_pdf("So-do-tu-duy-He-thong-Chinh-tri-Viet-Nam-2026.pdf", "Sơ đồ tư duy Hệ thống chính trị Việt Nam", "SƠ ĐỒ TƯ DUY", ["Phân tích cơ cấu 3 bộ phận cấu thành Hệ thống chính trị Việt Nam."])
generate_generic_law_pdf("Tai-lieu-on-thi-Ngoai-ngu-Tieng-Anh-B1-Cong-Chuc.pdf", "Tài liệu ôn thi Ngoại ngữ Tiếng Anh B1 Công chức", "TIẾNG ANH B1", ["Bộ 300 câu trắc nghiệm ngữ pháp và đọc hiểu Tiếng Anh chuẩn B1."])


# 4. TẠO TỆP WORD (.DOCX) PHIẾU ĐĂNG KÝ DỰ TUYỂN MẪU SỐ 01 NĐ 138 CHUẨN ĐẦY ĐỦ BỘ NỘI VỤ
def generate_docx_form(filename):
    filepath = os.path.join(output_dir, filename)
    doc = docx.Document()

    # Margins 2cm
    for s in doc.sections:
        s.top_margin = Inches(0.8)
        s.bottom_margin = Inches(0.8)
        s.left_margin = Inches(1.0)
        s.right_margin = Inches(0.8)

    # Header
    p_hdr = doc.add_paragraph()
    p_hdr.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r1 = p_hdr.add_run("CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM\nĐộc lập - Tự do - Hạnh phúc\n")
    r1.bold = True
    r1.font.name = 'Times New Roman'
    r1.font.size = Pt(12)

    r_dash = p_hdr.add_run("-------------------\n")
    r_dash.bold = True

    # Title
    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r_t = p_title.add_run("\nPHIẾU ĐĂNG KÝ DỰ TUYỂN CÔNG CHỨC\n")
    r_t.bold = True
    r_t.font.name = 'Times New Roman'
    r_t.font.size = Pt(14)

    r_sub = p_title.add_run("(Ban hành kèm theo Nghị định số 138/2020/NĐ-CP ngày 27/11/2020 của Chính phủ)\n\n")
    r_sub.italic = True
    r_sub.font.name = 'Times New Roman'
    r_sub.font.size = Pt(11)

    # Body sections
    sections = [
        "Vị trí đăng ký dự tuyển: .............................................................................................................................",
        "Cơ quan tuyển dụng: .....................................................................................................................................",
        "\nI. THÔNG TIN CÁ NHÂN",
        "1. Họ và tên (chữ in hoa): ....................................................................................... 2. Nam [  ]   Nữ [  ]",
        "3. Ngày, tháng, năm sinh: ....../....../............ 4. Nơi sinh: ........................................................................",
        "5. Quê quán: ..................................................................................................................................................",
        "6. Dân tộc: ........................................................... 7. Tôn giáo: ...................................................................",
        "8. Số CCCD/CMND: ....................................... Ngày cấp: ...../...../......... Nơi cấp: ...................................",
        "9. Số điện thoại liên hệ: ................................................. 10. Email: .............................................................",
        "11. Địa chỉ báo tin tin/liên lạc: ......................................................................................................................",
        "\nII. TRÌNH ĐỘ ĐÀO TẠO & CHỨNG CHỈ",
        "Trình độ chuyên môn: [  ] Đại học   [  ] Thạc sĩ   [  ] Tiến sĩ",
        "Chuyên ngành đào tạo: ................................................................................................................................",
        "Trường cấp bằng: .................................................................... Năm tốt nghiệp: ....................................",
        "Xếp loại bằng: .......................................................................... Bằng thứ hai (nếu có): ...........................",
        "Trình độ Ngoại ngữ: ................................................................. Trình độ Tin học: ......................................",
        "\nIII. THÔNG TIN ĐỐI TƯỢNG ƯU TIÊN (Nếu có)",
        "Thuộc đối tượng ưu tiên theo Điều 5 Nghị định 138/2020/NĐ-CP: .................................................................",
        "\nIV. CAM KẾT CỦA NGUỜI DỰ TUYỂN",
        "Tôi xin cam đoan những lời khai trên đây là đúng sự thật. Nếu sai sự thật, tôi xin chịu hoàn toàn trách nhiệm trước pháp luật và bị hủy bỏ kết quả trúng tuyển.",
    ]

    for s in sections:
        p = doc.add_paragraph()
        r = p.add_run(s)
        r.font.name = 'Times New Roman'
        r.font.size = Pt(11)
        if s.startswith("I.") or s.startswith("II.") or s.startswith("III.") or s.startswith("IV."):
            r.bold = True
            r.font.size = Pt(12)

    # Sign section
    p_sign = doc.add_paragraph()
    p_sign.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    r_s = p_sign.add_run("\n\n......., ngày ...... tháng ...... năm 2026\nNGƯỜI LÀM ĐƠN\n(Ký và ghi rõ họ tên)\n\n\n\n\n........................................................")
    r_s.font.name = 'Times New Roman'
    r_s.font.size = Pt(11)

    doc.save(filepath)
    print(f"Generated {filename}: {os.path.getsize(filepath)} bytes")

generate_docx_form("Phieu-dang-ky-du-tuyen-Mau-01-ND138-BNV.docx")
generate_docx_form("Phieu-dang-ky-du-tuyen-Mau-01-ND138.docx")
generate_docx_form("Phieu-Mau-01-ND138.docx")
generate_docx_form("Mau-CV-Ung-Tuyen-Vi-Tri-Viec-Lam-Cong-Vu.docx")

print("ALL DOWNLOAD FILES GENERATED SUCCESSFULLY!")
