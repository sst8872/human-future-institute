# -*- coding: utf-8 -*-
"""두 양식을 한글(.hwpx / .hwp) 파일로 만든다 — make.js(.docx)와 같은 내용·모양.
필요: pip install python-hwpx (7.x). 이 폴더에서 `python3 make_hwp.py`
→ `업무협약(MOU) 제안서.hwpx|.hwp`, `연계사업 운영 신청서.hwpx|.hwp`
단위: HWPUNIT = 1/7200 인치 = docx twip × 5
"""
from hwpx import HwpxDocument
import hwpx.hwp5.writer as _w
_w.VERSION = (5, 1, 0, 1)  # 한글 2020에서 「상위 버전」 경고가 안 뜨게

FONT = '맑은 고딕'
TW = 5                      # twip → HWPUNIT
W = 9638                    # 본문 폭(twip): A4 - 2cm×2
BORDER = '#9AA3B5'
LABEL = '#EEF1F7'
INK = '#0B1633'


def png_for_hwp(path, width_unit):
    """한글은 그림 원래 크기를 PNG의 DPI로 계산한다(DPI가 없으면 96).
    표시 폭(HWPUNIT)과 원래 크기가 같아지도록 DPI를 적어 넣어야 그림이 잘리거나 확대되지 않는다."""
    import io
    from PIL import Image
    im = Image.open(path)
    dpi = im.width * 7200 / width_unit
    buf = io.BytesIO(); im.save(buf, 'PNG', dpi=(dpi, dpi))
    return buf.getvalue()


class Form:
    def __init__(self):
        d = self.d = HwpxDocument.new(inherit_style=False)
        st = self.st = d.styles
        st.ensure_font(FONT)
        d.page.set_size(width=59528, height=84188)
        m = 1134 * TW
        d.page.set_margins(left=m, right=m, top=m, bottom=m - 2834, header=0, footer=2834)  # docx처럼 꼬리말이 아래 여백 안에
        self.first = True
        self.gap = 300

    # 글자·문단 모양
    def cp(self, size=10, bold=False, color='#000000'):
        return self.st.ensure_run(font=FONT, size=size, bold=bold, color=color)

    def pp(self, align='JUSTIFY', before=0, after=0, line=130, border=None, keep_next=False):
        kw = dict(alignment=align, margins={'prev': int(before * TW), 'next': int(after * TW)}, line_spacing=line)
        if keep_next:
            kw['break_setting'] = {'keep_with_next': True}
        if border:
            kw['border'] = border
        return self.st.ensure_paragraph_format(**kw)

    def para(self, text='', size=10, bold=False, color='#000000', align='JUSTIFY', before=0, after=0, border=None, keep_next=False, target=None):
        ppid = self.pp(align, before, after, border=border, keep_next=keep_next)
        cpid = self.cp(size, bold, color)
        if target is None:
            p = self.d.add_paragraph('', para_pr_id_ref=ppid, char_pr_id_ref=cpid)
        else:
            p = target.add_paragraph('', para_pr_id_ref=ppid, char_pr_id_ref=cpid)
        if text:
            p.add_run(text, char_pr_id_ref=cpid)
        return p

    def rule_border(self, width=0.4):
        bf = self.st.ensure_border_fill(border_color=INK, border_width_mm=width, active_borders=['bottom'])
        return {'borderFillIDRef': bf, 'offsetBottom': 600}

    # 표
    def table(self, cols, rows, border=None, inner=None):
        """cols: twip 폭 목록. rows: [(cells, min_height_twip)], cell = dict(text|lines, label, span, align, valign, hint, size, color, bold)"""
        nr, nc = len(rows), len(cols)
        t = self.d.add_table(nr, nc)
        t.set_width(W * TW)
        t.set_column_widths(cols)
        t.set_borders(border_width_mm=0.12, color=border or BORDER)
        im = inner or (140, 90)
        t.set_in_margins(left=im[0] * TW, right=im[0] * TW, top=im[1] * TW, bottom=im[1] * TW)
        t.set_treat_as_char(False)   # 글자처럼 취급 끔 → 쪽 경계에서 나뉠 수 있게(docx 표처럼)
        t.page_split = 'CELL'
        t.set_out_margins(left=0, right=0, top=0, bottom=self.gap * TW)  # 떠 있는 표 아래 여백 — 다음 제목·문장이 표에 붙지 않게
        merges = []
        for r, (cells, h) in enumerate(rows):
            c = 0
            for cell in cells:
                span = cell.get('span', 1)
                if span > 1:
                    merges.append((r, c, r, c + span - 1))
                cell['_pos'] = (r, c)
                c += span
            if h:
                t.set_row_height(r, h * TW)
        for (r0, c0, r1, c1) in merges:
            t.merge_cells(r0, c0, r1, c1)
        for r, (cells, h) in enumerate(rows):
            for cell in cells:
                rr, cc = cell['_pos']
                tc = t.cell(rr, cc)
                if cell.get('label'):
                    t.set_cell_shading(rr, cc, LABEL)
                tc.vertical_alignment = cell.get('valign', 'CENTER')
                lines = cell.get('lines') or [cell.get('text', '')]
                # 칸의 첫 빈 문단을 지우고 다시 씀
                for p in list(tc.paragraphs):
                    p.remove() if len(tc.paragraphs) > 1 else None
                first = tc.paragraphs[0]
                for i, ln in enumerate(lines):
                    if isinstance(ln, tuple):
                        txt, o = ln
                    else:
                        txt, o = ln, {}
                    o = {**{k: cell[k] for k in ('size', 'color', 'bold', 'align') if k in cell}, **o}
                    if cell.get('label'):
                        o.setdefault('bold', True); o.setdefault('align', 'CENTER')
                    ppid = self.pp(o.get('align', 'LEFT'), o.get('before', 0), 0)
                    cpid = self.cp(o.get('size', 10), o.get('bold', False), o.get('color', '#000000'))
                    if i == 0:
                        p = first
                        p.para_pr_id_ref = ppid
                        p.char_pr_id_ref = cpid
                        p.clear_text() if p.text else None
                        if txt:
                            p.add_run(txt, char_pr_id_ref=cpid)
                    else:
                        p = tc.add_paragraph('', para_pr_id_ref=ppid, char_pr_id_ref=cpid)
                        if txt:
                            p.add_run(txt, char_pr_id_ref=cpid)
        return t

    # 묶음
    def sec(self, text, new_page=False):
        p = self.para(text, size=11.5, bold=True, color=INK, before=300, after=110, keep_next=True, align='LEFT')
        if new_page:
            p.page_break = True   # docx와 같은 곳에서 쪽이 바뀌게

    def box(self, h, hint):
        self.table([W], [([{'lines': [(hint, {'size': 9, 'color': '#8A93A6'})], 'valign': 'TOP'}], h)])

    def header(self, code, title):
        self.para(code, size=9, color='#555555', after=60, align='LEFT')
        self.para(title, size=18, bold=True, color=INK, align='CENTER', before=120, after=200, border=self.rule_border())

    def person(self):
        C4 = [1700, 3119, 1700, 3119]
        L = lambda s: {'text': s, 'label': True}
        V = lambda s='', span=1: {'text': s, 'span': span}
        self.table(C4, [
            ([L('기관명'), V('', 3)], 520),
            ([L('대표자'), V(), L('기관 유형'), V()], 520),
            ([L('주소'), V('', 3)], 520),
            ([L('홈페이지'), V('', 3)], 520),
            ([L('담당자'), V(), L('부서·직위'), V()], 520),
            ([L('전화'), V(), L('전자우편'), V()], 520),
        ])

    def consent(self, purpose):
        self.sec('개인정보 수집·이용 동의')
        C = [2300, W - 2300]
        L = lambda s: {'text': s, 'label': True}
        self.table(C, [
            ([L('수집 항목'), {'text': '담당자 성명, 부서·직위, 전화번호, 전자우편'}], 0),
            ([L('수집·이용 목적'), {'text': purpose}], 0),
            ([L('보유·이용 기간'), {'text': '목적 달성 후 지체 없이 파기'}], 0),
            ([{'span': 2, 'lines': [
                ('위 개인정보 수집·이용에 동의하지 않을 권리가 있으며, 동의하지 않을 경우 검토 및 연락이 제한될 수 있습니다.', {'size': 9}),
                ('위와 같이 개인정보 수집·이용에 동의합니다.      □ 동의함      □ 동의하지 않음', {'size': 10, 'bold': True, 'before': 80}),
            ]}], 0),
        ])

    def sign(self, sentence, who, dept):
        self.para(sentence, size=10.5, align='CENTER', before=420, after=300)
        self.para('20      년      월      일', size=10.5, align='CENTER', after=300)
        self.para(who + ' :                                   (서명 또는 인)', size=10.5, align='RIGHT', after=360)
        self.para('인류와미래연구소장 귀하', size=12, bold=True, align='LEFT', after=240)
        bf = self.st.ensure_border_fill(border_color=BORDER, border_width_mm=0.12, active_borders=['top'])
        self.para('제출처 : 인류와미래연구소 ' + dept + '  (TEL 033-260-6461 · E-mail hjyoon@cnue.ac.kr)', size=9, color='#555555', align='LEFT', before=120,
                  border={'borderFillIDRef': bf, 'offsetTop': 600})
        self.para('※ 작성한 양식은 전자우편으로 보내 주시기 바랍니다. 칸이 부족하면 늘려 쓰거나 별지를 붙여 주십시오.', size=9, color='#555555', align='LEFT', before=40)

    def footer(self):
        ft = self.d.page.set_footer(text=' ')
        p = ft.paragraphs[0]
        p.clear_text()
        p.para_pr_id_ref = self.pp('CENTER')
        h_mm = 34 * 25.4 / 96        # docx 그림 높이 34px(96dpi) ≈ 9mm
        w_mm = h_mm * 1667 / 400
        data = png_for_hwp('brand.png', int(w_mm / 25.4 * 7200))
        item = self.d.media.add_image(data, 'png')
        p.add_picture(item, width=int(w_mm / 25.4 * 7200), height=int(h_mm / 25.4 * 7200), alignment='CENTER')

    def save(self, name):
        self.footer()
        self.d.save(name + '.hwpx')
        open(name + '.hwp', 'wb').write(self.d.to_bytes(format='hwp'))


def mou():
    f = Form()
    f.header('[양식 1]', '업무협약(MOU) 제안서')
    f.sec('1. 제안 기관 정보'); f.person()
    f.sec('2. 협약 개요')
    C2 = [2300, W - 2300]
    L = lambda s: {'text': s, 'label': True}
    f.table(C2, [
        ([L('협약명(가칭)'), {'text': ''}], 520),
        ([L('희망 협약 기간'), {'text': '20    .    .    .  ~  20    .    .    .  (    년)'}], 520),
        ([L('희망 체결 시기'), {'text': '20    년      월'}], 520),
        ([L('협력 분야'), {'lines': ['□ 공동 연구    □ 학술 행사 공동 개최    □ 교육·강좌 프로그램 운영',  # 한글은 빈칸이 넓어 docx보다 조금 줄임
                                    '□ 인적 교류    □ 자료·시설 공유    □ 기타 (                  )']}], 0),
    ])
    f.sec('3. 협약 목적'); f.box(1500, '협약을 제안하는 배경과 목적을 적어 주십시오.')
    f.sec('4. 주요 협력 내용'); f.box(2200, '협력하려는 사업·활동의 내용을 구체적으로 적어 주십시오.')
    f.sec('5. 역할 분담', new_page=True)
    f.table([2300, 3669, 3669], [
        ([L('구분'), L('제안 기관'), L('인류와미래연구소')], 0),
        ([L('주요 역할'), {'text': ''}, {'text': ''}], 900),
        ([L('지원·부담 사항'), {'text': ''}, {'text': ''}], 900),
    ])
    f.sec('6. 기대 효과'); f.box(1300, '협약을 통해 기대하는 성과를 적어 주십시오.')
    f.sec('7. 붙임 자료')
    f.table(C2, [([L('붙임'), {'lines': ['□ 기관 소개 자료 1부      □ 협약서(안) 1부', '□ 기타 (                                        )']}], 0)])
    f.consent('업무협약 제안 검토 및 협의를 위한 연락')
    f.sign('위와 같이 인류와미래연구소와의 업무협약을 제안합니다.', '제안 기관 대표', '기획운영부')
    f.save('업무협약(MOU) 제안서')


def biz():
    f = Form()
    f.header('[양식 2]', '연계사업 운영 신청서')
    f.sec('1. 신청 기관 정보'); f.person()
    f.sec('2. 사업 개요')
    C4 = [1700, 3119, 1700, 3119]
    L = lambda s: {'text': s, 'label': True}
    f.table(C4, [
        ([L('사업명'), {'text': '', 'span': 3}], 520),
        ([L('사업 유형'), {'span': 3, 'lines': ['□ 포럼      □ 간담회      □ 학술대회      □ 강좌 운영', '□ 학술자료 제작      □ 기타 (                              )']}], 0),
        ([L('운영 기간'), {'text': '20    .    .    .  ~  20    .    .    .', 'span': 3}], 520),
        ([L('운영 장소'), {'text': ''}, L('운영 방식'), {'text': '□ 대면  □ 비대면  □ 혼합'}], 520),
    ])
    f.sec('3. 사업 목적 및 필요성'); f.box(1500, '사업을 운영하려는 목적과 필요성을 적어 주십시오.')
    f.sec('4. 세부 운영 계획')
    f.table([1000, 2000, 4238, 2400], [
        ([L('회차'), L('일시'), L('주제 및 내용'), L('강사·진행자(희망)')], 0),
        *[([{'text': str(i), 'align': 'CENTER'}, {'text': ''}, {'text': ''}, {'text': ''}], 560) for i in range(1, 5)],
    ])
    f.sec('5. 예산 및 요청 사항', new_page=True)
    C2 = [2300, W - 2300]
    f.table(C2, [
        ([L('총 예산(안)'), {'text': '원', 'align': 'RIGHT'}], 520),
        ([L('비용 부담'), {'text': '□ 신청 기관 부담      □ 공동 부담      □ 협의 필요'}], 520),
        ([L('연구소 요청 사항'), {'lines': [('연구소에 요청하는 사항을 적어 주십시오.', {'size': 9, 'color': '#8A93A6'})], 'valign': 'TOP'}], 1500),
    ])
    f.consent('연계사업 운영 신청 검토 및 협의를 위한 연락')
    f.sign('위와 같이 인류와미래연구소 연계사업 운영을 신청합니다.', '신청 기관 대표', '미래혁신부')
    f.save('연계사업 운영 신청서')


if __name__ == '__main__':
    mou(); biz()
