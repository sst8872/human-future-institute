# 한글(호워드)로 docx를 변환해 저장한 hwpx를 고쳐 A4 한 쪽에 맞춘다: python3 fix_committee_hwpx.py 변환본.hwpx 결과.hwpx
# 참고: 한글은 그림 imgClip을 「픽셀×75」 단위로 읽는다(python-hwpx는 HWPUNIT으로 써서 로고가 잘려 보였음).
import re, sys, zipfile, shutil
src, out = sys.argv[1], sys.argv[2]
z = zipfile.ZipFile(src)
files = {n: z.read(n) for n in z.namelist()}
s = files['Contents/section0.xml'].decode('utf8')
h = files['Contents/header.xml'].decode('utf8')

# 1) 쪽 여백: 머리말·꼬리말 자리 없이 위아래 15mm
s = re.sub(r'<hp:margin header="\d+" footer="\d+" gutter="0" left="5670" right="5670" top="\d+" bottom="\d+"/>',
           '<hp:margin header="0" footer="0" gutter="0" left="5670" right="5670" top="3000" bottom="2800"/>', s)

# 2) 문단 간격 줄이기(문단 모양별 앞·뒤 간격, 줄 간격)
def setpp(pid, prev=None, nxt=None, ls=None, bf=None):
    global h
    m = re.search(r'<hh:paraPr id="%d".*?</hh:paraPr>' % pid, h, re.S)
    t = m.group(0)
    if prev is not None: t = re.sub(r'<hc:prev value="\d+"', '<hc:prev value="%d"' % prev, t)
    if nxt is not None: t = re.sub(r'<hc:next value="\d+"', '<hc:next value="%d"' % nxt, t)
    if ls is not None: t = re.sub(r'(<hh:lineSpacing type="PERCENT" value=")\d+', r'\g<1>%d' % ls, t)
    h = h.replace(m.group(0), t)
setpp(2, nxt=600)
setpp(3, prev=500, ls=100); setpp(4, prev=150, ls=100)
setpp(5, prev=250, nxt=900, ls=100)
setpp(6, prev=500, ls=100); setpp(7, prev=150, ls=100); setpp(8, prev=400, ls=100)
setpp(9, prev=600, nxt=400, ls=100)
setpp(10, nxt=0, ls=60)
setpp(12, nxt=250, ls=60)
h = re.sub(r'(<hh:paraPr id="12".*?offsetBottom=")600', r'\g<1>250', h, flags=re.S)

# 3) 위원 구성표: 줄 높이·칸 여백 줄이기
for a, b in (('4500', '3800'), ('6500', '5000'), ('7500', '6000'), ('20690', '25790'), ('9000', '7600')):
    s = s.replace('<hp:cellSz width="%s" height="2200"/>' % a, '<hp:cellSz width="%s" height="1500"/>' % b)
s = s.replace('<hp:cellMargin left="500" right="500" top="400" bottom="400"/>', '<hp:cellMargin left="300" right="300" top="200" bottom="200"/>')
s = s.replace('<hp:cellMargin left="500" right="500" top="150" bottom="150"/>', '<hp:cellMargin left="500" right="500" top="60" bottom="60"/>')
# 로고 조금 작게(가로세로 비율 그대로)
s = s.replace('<hp:sz width="18975" widthRelTo="ABSOLUTE" height="4800"', '<hp:sz width="16602" widthRelTo="ABSOLUTE" height="4200"')
s = s.replace('<hp:curSz width="0" height="0"/>', '<hp:curSz width="16602" height="4200"/>', 1)

# 4) 결재·시행 정보: 굵은 줄은 결재 칸 위 한 번만(빈 문단 아래). 결재·시행 표 문단은 테두리 없는 모양(1)
tops = [m for m in re.finditer(r'<hp:p id="0" paraPrIDRef="12"', s)]
assert len(tops) == 3, len(tops)
# 뒤에서부터 바꿔 위치가 밀리지 않게
for m in reversed(tops[1:]):
    s = s[:m.start()] + '<hp:p id="0" paraPrIDRef="1"' + s[m.end():]

def tbl(tid):
    m = re.search(r'<hp:tbl id="%s".*?</hp:tbl>' % tid, s, re.S); return m
def cells(t):
    return re.findall(r'<hp:tc .*?</hp:tc>', t, re.S)
def mod(c, col=None, row=None, span=None, width=None, text=None):
    if col is not None: c = re.sub(r'colAddr="\d+"', 'colAddr="%d"' % col, c)
    if row is not None: c = re.sub(r'rowAddr="\d+"', 'rowAddr="%d"' % row, c)
    if span is not None: c = re.sub(r'colSpan="\d+"', 'colSpan="%d"' % span, c)
    if width is not None: c = re.sub(r'<hp:cellSz width="\d+"', '<hp:cellSz width="%d"' % width, c)
    if text is not None: c = re.sub(r'<hp:t>[^<]*</hp:t>', '<hp:t>%s</hp:t>' % text, c, count=1)
    return c

# 시행 표: 둘째 줄을 「우편번호 | 주소(3칸 합침)」로
mB = tbl('1255017748'); tB = mB.group(0); cB = cells(tB)
row1 = '<hp:tr>' + cB[4] + mod(cB[5], span=3, width=42690) + '</hp:tr>'
trs = re.findall(r'<hp:tr>.*?</hp:tr>', tB, re.S)
tB2 = tB.replace(trs[1], row1)
s = s.replace(tB, tB2)

# 전화 표: 둘째 줄을 「누리집 | 주소(3칸) | 공개 구분 | 공개」로
mC = tbl('1255017749') or None
if mC is None:
    ids = re.findall(r'<hp:tbl id="(\d+)"', s); mC = tbl(ids[-1])
tC = mC.group(0); cC = cells(tC)
lab, val = cC[6], cC[7]
row1 = '<hp:tr>' + mod(lab, text='누리집') + mod(val, span=3, width=26625, text='https://inshnf.com') \
       + mod(lab, col=4) + mod(val, col=5, span=1, width=10565) + '</hp:tr>'
trs = re.findall(r'<hp:tr>.*?</hp:tr>', tC, re.S)
s = s.replace(tC, tC.replace(trs[1], row1))
assert '누리집' in s and s.count('https://inshnf.com') == 1

# 빈 간격 문단(모양 10) 없애기
s = re.sub(r'<hp:p id="0" paraPrIDRef="10"[^>]*><hp:run charPrIDRef="0"/>(<hp:linesegarray>.*?</hp:linesegarray>)?</hp:p>', '', s, count=1, flags=re.S)
assert 'paraPrIDRef="10"' not in s
# 결재·시행·전화 표는 글자처럼 취급 → 표 뒤 빈 줄이 생기지 않게
for tid in ('1255017747', '1255017748', '1255017749'):
    m = re.search(r'(<hp:tbl id="%s".*?<hp:pos )treatAsChar="0"' % tid, s, re.S)
    s = s[:m.start()] + m.group(1) + 'treatAsChar="1"' + s[m.end():]
# 5) 줄 배치 정보는 지워서 한글이 다시 계산하게
s = re.sub(r'<hp:linesegarray>.*?</hp:linesegarray>', '', s, flags=re.S)

files['Contents/section0.xml'] = s.encode('utf8')
files['Contents/header.xml'] = h.encode('utf8')
with zipfile.ZipFile(out, 'w') as o:
    for n in z.namelist():
        o.writestr(z.getinfo(n), files[n], compress_type=zipfile.ZIP_STORED if n == 'mimetype' else zipfile.ZIP_DEFLATED)
print('ok')
