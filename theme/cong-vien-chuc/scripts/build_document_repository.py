import os
import sys
from reportlab.lib.pagesizes import letter, A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable
from reportlab.pdfgen import canvas
import docx
from docx.shared import Pt, RGBColor, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side

out_dir = r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\assets\downloads"
os.makedirs(out_dir, exist_ok=True)

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super().showPage()
        super().save()

    def draw_page_decorations(self, page_count):
        self.saveState()
        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(colors.HexColor("#475569"))
        # Header line
        self.drawString(54, 800, "CONG VIEN CHUC - THU VIEN TAI LIEU PHAP LUAT & TUYEN DUNG 2026")
        self.setStrokeColor(colors.HexColor("#CBD5E1"))
        self.setLineWidth(0.5)
        self.line(54, 792, 558, 792)
        # Footer
        self.line(54, 45, 558, 45)
        self.setFont("Helvetica", 8)
        self.drawString(54, 32, "Nguon: He thong Co quan Nha nuoc & Cong Vien Chuc (congvienchuc.com)")
        self.drawRightString(558, 32, f"Trang {self._pageNumber} / {page_count}")
        self.restoreState()

def create_pdf(filename, title, subheader, body_paragraphs):
    filepath = os.path.join(out_dir, filename)
    doc = SimpleDocTemplate(
        filepath,
        pagesize=A4,
        leftMargin=54, rightMargin=54, topMargin=54, bottomMargin=54
    )
    styles = getSampleStyleSheet()
    
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Heading1'],
        fontName='Helvetica-Bold',
        fontSize=15,
        leading=18,
        textColor=colors.HexColor('#0F172A'),
        alignment=1, # Center
        spaceAfter=8
    )
    
    sub_style = ParagraphStyle(
        'SubHeader',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=10,
        leading=14,
        textColor=colors.HexColor('#1E293B'),
        alignment=1,
        spaceAfter=15
    )
    
    body_style = ParagraphStyle(
        'BodyTextCustom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=10,
        leading=15,
        textColor=colors.HexColor('#334155'),
        spaceAfter=10
    )

    story = []
    # Header banner
    story.append(Paragraph(title, title_style))
    story.append(Paragraph(subheader, sub_style))
    story.append(HRFlowable(width="100%", thickness=1.5, color=colors.HexColor('#D97706'), spaceBefore=5, spaceAfter=15))
    
    for p_text in body_paragraphs:
        if p_text.startswith("<b>") or p_text.startswith("CHƯƠNG") or p_text.startswith("Dieu") or p_text.startswith("Muc"):
            h_style = ParagraphStyle(
                'SectionHead',
                parent=styles['Heading2'],
                fontName='Helvetica-Bold',
                fontSize=11,
                leading=15,
                textColor=colors.HexColor('#0F172A'),
                spaceBefore=8,
                spaceAfter=6
            )
            story.append(Paragraph(p_text, h_style))
        else:
            story.append(Paragraph(p_text, body_style))
            
    doc.build(story, canvasmaker=NumberedCanvas)
    print(f"Generated PDF: {filename}")

# Generate PDF documents
create_pdf(
    "Nghi-dinh-06-2023-ND-CP-Kiem-Dinh-Chat-Luong.pdf",
    "NGHI DINH VE KIEM DINH CHAT LUONG DAU VAO CONG CHUC",
    "So: 06/2023/ND-CP - Ha Noi, ngay 21 thang 02 nam 2023",
    [
        "Chinh phu ban hanh Nghi dinh quy dinh ve kiem dinh chat luong dau vao cong chuc.",
        "<b>Dieu 1. Pham vi dieu chinh va doi tuong ap dung</b>",
        "1. Nghi dinh nay quy dinh ve nguyen tac, tham quyen, trinh tu, thu tuc va to chuc thuc hien kiem dinh chat luong dau vao cong chuc.",
        "2. Ap dung doi voi nguoi dang ky du tuyen vao cong chuc trong cac co quan cua Dang Cong san Viet Nam, Mat tran To quoc Viet Nam, to chuc chinh tri - xa hoi, co quan nha nuoc o trung uong, cap tinh, cap huyen.",
        "<b>Dieu 2. Nguyen tac kiem dinh chat luong dau vao cong chuc</b>",
        "1. Bao dam cong khai, minh banh, khach quan, doc lap va dung quy dinh cua phap luat.",
        "2. Ket qua kiem dinh chat luong dau vao cong chuc co gia tri su dung trong pham vi toan quoc trong thoi han 24 thang ke tu ngay co quyet dinh cong nhan ket qua.",
        "<b>Dieu 3. Hinh thuc, noi dung va thoi gian kiem dinh</b>",
        "1. Hinh thuc kiem dinh: Thi trac nghiem tren may tinh.",
        "2. Noi dung kiem dinh: Danh gia nang luc thong hieu kien thuc chung ve he thong chinh tri, to chuc bo may cua Dang, Nha nuoc; chu truong, duong loi cua Dang, chinh sach, phap luat cua Nha nuoc; nang luc tu duy, ky nang giai quyet van de.",
        "3. Thoi gian va so luong cau hoi: Thi Kien thuc chung 60 cau hoi / 60 phut. Nguoi tra loi dung tu 50% so cau hoi tro len thi duoc xac nhan dat ket qua kiem dinh."
    ]
)

create_pdf(
    "Nghi-dinh-30-2020-ND-CP-Cong-Tac-Van-Thu.pdf",
    "NGHI DINH VE CONG TAC VAN THU HANH CHINH",
    "So: 30/2020/ND-CP - Ha Noi, ngay 05 thang 03 nam 2020",
    [
        "Chinh phu ban hanh Nghi dinh ve quy dinh cong tac van thu va the thuc trinh bay van ban hanh chinh.",
        "<b>Dieu 1. Pham vi dieu chinh</b>",
        "Nghi dinh nay quy dinh ve cong tac van thu va quan ly nha nuoc ve cong tac van thu, bao gom: soan thao, ban hanh van ban; quan ly van ban; lap ho so va nop lưu ho so, tai lieu vao Luu tru co quan; quan ly va su dung con dau, thiet bi luu khoa bi mat nha nuoc.",
        "<b>Dieu 2. The thuc van ban hanh chinh</b>",
        "1. Van ban hanh chinh gom cac thanh phan chinh: Quoc hieu va Tiêu ngu; Ten co quan, to chuc ban hanh; So, ky hieu; Dia danh va thoi gian ban hanh; Ten loai va trich yeu noi dung; Noi dung van ban; Chuc vu, ho ten va chu ky cua nguoi co tham quyen; Dau, chu ky so; Noi nhan.",
        "2. Phong chu su dung: Font chu Tieng Viet Times New Roman, bo ma ky tu Unicode theo TCVN 6909:2001."
    ]
)

create_pdf(
    "Thong-bao-tuyen-dung-cong-chuc-ha-noi-2026.pdf",
    "UBND THANH PHO HA NOI - THONG BAO TUYEN DUNG CONG CHUC 2026",
    "So: 102/TB-UBND - Ha Noi, ngay 10 thang 09 nam 2026",
    [
        "UBND Thanh pho Ha Noi thong bao ky thi tuyen dung 450 chi tieu cong chuc khoi chinh quyen nam 2026.",
        "<b>1. Chi tieu tuyen dung:</b> 450 chi tieu ngach Chuyen vien, Kiem tra vien, Kế toan vien tai cac So, Nganh va UBND cac Quan, Huyen, Thi xa.",
        "<b>2. Hinh thuc thi tuyen:</b> Thi 2 Vong theo Nghi dinh 138/2020/ND-CP.",
        "- Vong 1: Thi trac nghiem Kien thuc chung (60 cau) va Ngoai ngu Tieng Anh (30 cau) tren may tinh.",
        "- Vong 2: Thi viet mon NGHIEP VU CHUYEN NGANH thoi gian 180 phut.",
        "<b>3. Thoi gian nhan ho so:</b> Tu ngay 15/09/2026 den het ngay 25/10/2026."
    ]
)

create_pdf(
    "Thong-bao-tuyen-dung-cong-chuc-tphcm-2026.pdf",
    "UBND TP HO CHI MINH - KE HOACH THI TUYEN CONG CHUC 2026",
    "So: 88/KH-UBND - TP.HCM, ngay 12 thang 09 nam 2026",
    [
        "UBND TP.HCM phat dong ky thi tuyen dung 520 chi tieu cong chuc va vien chuc quan ly nha nuoc nam 2026.",
        "<b>1. Vi tri viec lam:</b> Chuyen vien Quan ly do thi, Chuyen vien Tai chinh ngan sach, Chuyen vien Phap che, Chuyen vien CNTT.",
        "<b>2. Dieu kien dang ky:</b> Cong dan Viet Nam tu 18 tuoi tro len, tot nghiep Dai hoc tro len dung chuyen nganh dao tao theo yeu cau vi tri viec lam.",
        "<b>3. Nop ho so:</b> Nop truc tuyen qua Cong dich vu cong TP.HCM hoac nop truc tiep tai So Noi vu TP.HCM."
    ]
)

create_pdf(
    "Cam-nang-khoan-vung-trong-tam-KTC-2026-Full.pdf",
    "CAM NANG KHOANH VUNG TRONG TAM KIEN THUC CHUNG 2026",
    "Tai lieu doc quyen Cong Vien Chuc - Cap nhat Bo Noi vu 2026",
    [
        "<b>PHAN I: TOP 10 CHU DE THUONG GAP TRONG DE THI VONG 1</b>",
        "1. Luat Can bo, cong chuc 2008 & Luat sua doi 2019 (Chiem 35% so cau hoi).",
        "2. Nghi dinh 138/2020/ND-CP ve tuyen dung, su dung cong chuc (Chiem 25% so cau hoi).",
        "3. Nghi dinh 30/2020/ND-CP ve cong tac van thu hanh chinh (Chiem 15% so cau hoi).",
        "4. Bo may Nha nuoc va He thong Chinh tri Viet Nam (Chiem 15% so cau hoi).",
        "5. Chu truong, duong loi cua Dang va Cai cach hanh chinh (Chiem 10% so cau hoi).",
        "<b>PHAN II: MEO KHOANH VUNG CAU HOI TRAC NGHIEM NGACH CHUYEN VIEN</b>",
        "- Nho ky cac con so ve thoi gian: Thoi gian tap su ngach Chuyen vien la 12 thang; Thoi gian tap su ngach Can su la 06 thang.",
        "- Thoi han giai quyet khieu nai, to cao va thoi han xu ly ky luat cong chuc: Khien trách/Canh cao/Buoc thoi viec."
    ]
)

create_pdf(
    "Bo-de-trac-nghiem-Luat-Can-bo-Cong-chuc-60-cau-dap-an.pdf",
    "BO DE THI TRAC NGHIEM LUAT CAN BO CONG CHUC 60 CAU (CO DAP AN)",
    "Ngan hang de thi thu nghiem Exam OS X - Cong Vien Chuc 2026",
    [
        "<b>Cau 1: Theo Luat Can bo, cong chuc 2008, khai niem 'Cong chuc' đuoc quy dinh nhu the nao?</b>",
        "A. La cong dan Viet Nam, duoc bau cu theo nhiem ky.",
        "B. La cong dan Viet Nam, duoc tuyen dung, bo nay vao ngach, chuc danh trong co quan nha nuoc, trong bien che va huong luong tu ngan sach nha nuoc.",
        "C. La nguoi lam viec theo hop dong lao dong trong don vi su nghiep cong lap.",
        "D. Ca A, B, C deu sai.",
        "<i>-> Dap an dung: B</i>",
        "<b>Cau 2: Thoi gian tap su cua ngach Chuyen vien la bao nhieu thang?</b>",
        "A. 06 thang. B. 12 thang. C. 18 thang. D. 24 thang.",
        "<i>-> Dap an dung: B (12 thang theo Nghi dinh 138/2020/ND-CP)</i>"
    ]
)

create_pdf(
    "So-do-tu-duy-He-thong-Chinh-tri-Viet-Nam-2026.pdf",
    "SO DO TU DUY HE THONG CHINH TRI VIET NAM 2026",
    "Cẩm nang ôn thi trắc nghiệm công chức - Cong Vien Chuc",
    [
        "<b>1. CO QUAN QUYEN LUA NHA NUOC CAO NHAT:</b> Quoc hoi (Co quan dai dien cao nhất cua Nhan dan).",
        "<b>2. CO QUAN HANH CHINH NHA NUOC CAO NHAT:</b> Chinh phu (Co quan thuc hien quyen hành pháp).",
        "<b>3. CO QUAN XET XU:</b> Toa an nhan dan cac cap.",
        "<b>4. CO QUAN THUC HANH QUYEN CONG TO VA KIEM SAT:</b> Vien kieu sat nhan dan cac cap."
    ]
)

create_pdf(
    "Tai-lieu-on-thi-Ngoai-ngu-Tieng-Anh-B1-Cong-Chuc.pdf",
    "TAI LIEU ON THI TIENG ANH B1 CONG CHUC VONG 1",
    "Chuẩn khung năng lực ngoại ngữ 6 bậc dùng cho Việt Nam",
    [
        "<b>PART 1: GRAMMAR & VOCABULARY FOR CIVIL SERVANT EXAMS</b>",
        "- Tenses: Present Simple, Present Perfect, Past Simple, Future Simple.",
        "- Passive Voice in Administrative Writing.",
        "- Conditionals: Type 1 and Type 2.",
        "<b>PART 2: READING COMPREHENSIVE PRACTICE (30 QUESTIONS)</b>",
        "Practice reading texts regarding government policies, international integration, and public service etiquette."
    ]
)

# Generate DOCX document
doc_path = os.path.join(out_dir, "Mau-CV-Ung-Tuyen-Vi-Tri-Viec-Lam-Cong-Vu.docx")
doc = docx.Document()
doc.add_heading("SO YEU LY LICH & CV UNG TUYEN VI TRI VIEC LAM CONG VU", 0)
doc.add_paragraph("Kèm theo Phiếu đăng ký dự tuyển Mẫu số 01 - Nghị định 138/2020/NĐ-CP")

p = doc.add_paragraph()
p.add_run("I. THÔNG TIN CÁ NHÂN\n").bold = True
p.add_run("Họ và tên: NGUYỄN VĂN A\nNgày sinh: 15/08/1998\nCCCD số: 030098001234\nTrình độ chuyên môn: Cử nhân Luật (Loại Giỏi)\nNgoại ngữ: Tiếng Anh B1 (IELTS 6.5)\nTin học: Chuẩn kỹ năng sử dụng CNTT nâng cao\n")

doc.save(doc_path)
print(f"Generated DOCX: Mau-CV-Ung-Tuyen-Vi-Tri-Viec-Lam-Cong-Vu.docx")

print("All repository documents generated successfully!")
