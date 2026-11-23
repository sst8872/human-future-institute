# -*- coding: utf-8 -*-
"""인류와미래연구소 운영위원회 위원 구성 공문(내부 결재) — 한글(.hwpx)
필요: pip install python-hwpx (7.x). 이 폴더에서 `python3 make_committee.py`"""
import sys, os
sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'forms'))
from make_hwp import Form, W, TW, INK, BORDER
from PIL import Image

OUT = sys.argv[1] if len(sys.argv) > 1 else '인류와미래연구소 운영위원회 위원 구성(안)'
f = Form()
d = f.d

def line(width=0.5):
    bf = f.st.ensure_border_fill(border_color='#000000', border_width_mm=width, active_borders=['bottom'])
    return {'borderFillIDRef': bf, 'offsetBottom': 300}

# 머리: 표어 · 기관 로고와 이름
f.para('인류의 내일을 묻는 미래 담론', size=9, color='#555555', align='CENTER', after=160)
p = f.para('', align='CENTER', after=60)
im = Image.open('logo-blue.png'); h_mm = 15; w_mm = h_mm * im.width / im.height
p.add_picture(d.media.add_image(open('logo-blue.png', 'rb').read(), 'png'),
              width=int(w_mm / 25.4 * 7200), height=int(h_mm / 25.4 * 7200), alignment='CENTER')
f.para('인류와미래연구소', size=22, bold=True, color='#000000', align='CENTER', after=200, border=line(0.7))

# 수신·경유·제목
f.para('수신자   내부결재', size=11, align='LEFT', before=120, after=40)
f.para('(경유)', size=11, align='LEFT', after=40)
f.para('제  목   [인류와미래연구소] 2026학년도 「인류와미래연구소 운영위원회」 위원 구성(안)', size=11, bold=True, align='LEFT', after=200, border=line(0.3))

B = lambda t, **k: f.para(t, size=11, align='LEFT', before=k.get('before', 120), after=k.get('after', 0))
S = lambda t: f.para('   ' + t, size=11, align='LEFT', before=40)

B('1. 관련 근거')
S('가. 「인류와미래연구소 규정」 제4조(구성) 및 제5조(운영세칙)')
S('나. 인류와미래연구소 설립추진위원회 제2차 회의 결과(2026. 11. 20.)')
B('2. 위 관련 근거에 따라 연구소 운영에 관한 주요 사항을 심의하기 위하여 아래와 같이 「인류와미래연구소 운영위원회」를 구성하여 운영하고자 하오니 결재하여 주시기 바랍니다.', before=200)
B('3. 위원회 명칭: 인류와미래연구소 운영위원회', before=200)
B('4. 운영 목적: 연구소 운영에 관한 주요 사항을 심의하여 연구소 사업의 원활한 추진과 설립 목적 달성을 지원', before=200)
B('5. 위원회 기능', before=200)
S('가. 연구소 운영세칙 등 운영에 필요한 사항 심의(규정 제5조)')
S('나. 연간 사업계획, 예산 편성 및 결산 심의')
S('다. 중장기 발전계획(10년 발전전략)과 학술주제, 사업 성과 점검')
B('6. 인류와미래연구소 운영위원회 위원 구성(안)', before=200, after=120)

L = lambda s: {'text': s, 'label': True, 'size': 10.5}
C = lambda s='', **k: {'text': s, 'align': 'CENTER', 'size': 10.5, **k}
f.table([900, 1400, 1500, 4038, 1800], [
    ([L('연번'), L('구분'), L('성명'), L('소속'), L('비고')], 520),
    ([C('1'), C('위원장'), C('윤회정'), C('춘천교육대학교 과학교육과'), C('소장')], 520),
    ([C('2'), C('위원', span=1), C('박은미'), C('춘천교육대학교 과학교육과'), C('기획운영부장')], 520),
    ([C('3'), C('위원'), C('김지영'), C('서울특별시교육청교육연구정보원 교육정책연구소', size=9.5), C('미래혁신부장')], 520),
    ([C('4'), C('위원'), C('방담이'), C('가톨릭대학교 학부대학'), C('연구개발부장')], 520),
    ([C('5'), C('간사'), C(''), C(''), C('')], 520),
])
B('7. 위원 임기: 2026. 11. 30. ~ 2028. 11. 29.(2년)  끝.', before=80)

# 결재·시행 정보
f.para('', after=600)
foot = lambda t, **k: f.para(t, size=9.5, color='#333333', align='LEFT', before=k.get('before', 40), border=k.get('border'))
bft = f.st.ensure_border_fill(border_color='#000000', border_width_mm=0.5, active_borders=['top'])
foot('기안자                              검토                              결재  소장', before=200, border={'borderFillIDRef': bft, 'offsetTop': 300})
foot('시행  인류와미래연구소-       (2026.    .    .)                    접수', before=160)
foot('우 24328  강원특별자치도 춘천시 공지로 126 춘천교육대학교 과학관 203호  /  https://inshnf.com')
foot('전화 033-260-6461   전송               /  hjyoon@cnue.ac.kr  /  공개')

d.save(OUT + '.hwpx')
open(OUT + '.hwp', 'wb').write(d.to_bytes(format='hwp'))   # 한글 2014 이하용
print('saved', OUT + '.hwpx', '.hwp')
