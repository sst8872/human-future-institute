// 인류와미래연구소 운영위원회 위원 구성 공문(내부 결재) — 워드(.docx)
// 한글에서 열어 「다른 이름으로 저장 → 한글 문서(.hwpx)」로 바꿔 쓴다.  실행: node make_committee_docx.js <출력.docx>
const fs = require('fs');
const { Document, Packer, Paragraph, TextRun, ImageRun, Table, TableRow, TableCell, WidthType, AlignmentType,
        BorderStyle, ShadingType, VerticalAlign, TableLayoutType } = require('docx');

const FONT = '함초롬돋움';
const out = process.argv[2] || '운영위원회 위원 구성(안).docx';
const W = 9638;                                   // A4 - 20mm×2 (twip)
const pt = n => Math.round(n * 2);                // 글자 크기(half-point)
const run = (t, o = {}) => new TextRun({ text: t, font: FONT, size: pt(o.size || 11.5), bold: !!o.bold, color: o.color || '000000' });
const P = (t, o = {}) => new Paragraph({
  alignment: o.align || AlignmentType.LEFT,
  spacing: { before: o.before ?? 0, after: o.after ?? 0, line: o.line || 285 },
  indent: o.indent ? { left: o.indent, hanging: o.hanging || 0 } : undefined,
  border: o.rule ? { bottom: { style: BorderStyle.SINGLE, size: o.rule, color: '000000', space: 6 } } : undefined,
  keepNext: !!o.keepNext,
  children: Array.isArray(t) ? t : [run(t, o)],
});
const NONE = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const noB = { top: NONE, bottom: NONE, left: NONE, right: NONE, insideHorizontal: NONE, insideVertical: NONE };
const thin = { style: BorderStyle.SINGLE, size: 4, color: '808080' };
const box = { top: thin, bottom: thin, left: thin, right: thin, insideHorizontal: thin, insideVertical: thin };

function table(cols, rows, { borders = box, cellPad = 80, size = 11 } = {}) {
  return new Table({
    width: { size: W, type: WidthType.DXA }, columnWidths: cols, layout: TableLayoutType.FIXED, borders,
    rows: rows.map(r => new TableRow({
      height: r.h ? { value: r.h, rule: 'atLeast' } : undefined,
      children: r.cells.map((c, i) => {
        const span = c.span || 1;
        const w = cols.slice(i0(r.cells, i), i0(r.cells, i) + span).reduce((a, b) => a + b, 0);
        return new TableCell({
          width: { size: w, type: WidthType.DXA }, columnSpan: span, verticalAlign: VerticalAlign.CENTER,
          shading: c.shade ? { type: ShadingType.CLEAR, color: 'auto', fill: c.shade } : undefined,
          margins: { top: cellPad, bottom: cellPad, left: 100, right: 100 },
          children: [P(c.t || '', { size: c.size || size, bold: c.bold, align: c.align || AlignmentType.CENTER, line: 260 })],
        });
      }),
    })),
  });
  function i0(cells, i) { return cells.slice(0, i).reduce((a, c) => a + (c.span || 1), 0); }
}

// 로고: 로고·국문 이름·영문 이름이 한 세트인 가로 로고(2528×640)
const logo = fs.readFileSync(__dirname + '/logo-set.png');
const LH = 64;                                    // px(96dpi) ≈ 17mm
const head = [
  P('인류의 내일을 묻는 미래 담론', { size: 9, color: '555555', align: AlignmentType.CENTER, after: 160 }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 160 },
    border: { bottom: { style: BorderStyle.SINGLE, size: 18, color: '000000', space: 8 } },
    children: [new ImageRun({ type: 'png', data: logo, transformation: { width: Math.round(LH * 2528 / 640), height: LH },
      altText: { title: '인류와미래연구소 로고', description: '인류와미래연구소 INSTITUTE FOR HUMANITY & FUTURE', name: 'logo' } })] }),
];

const B = (t, o = {}) => P(t, { before: o.before ?? 120, indent: 0, ...o });
const S = t => P(t, { before: 40, indent: 600, hanging: 300 });
const N = (t, before = 150) => P(t, { before, indent: 300, hanging: 300 });   // 번호 문단: 둘째 줄을 글자에 맞춤

const label = t => ({ t, bold: true, shade: 'EEF1F7' });
const members = table([900, 1300, 1500, 4138, 1800], [
  { h: 440, cells: [label('연번'), label('구분'), label('성명'), label('소속'), label('비고')] },
  { h: 440, cells: [{ t: '1' }, { t: '위원장' }, { t: '윤회정' }, { t: '춘천교육대학교 과학교육과' }, { t: '소장' }] },
  { h: 440, cells: [{ t: '2' }, { t: '위원' }, { t: '박은미' }, { t: '춘천교육대학교 과학교육과' }, { t: '기획운영부장' }] },
  { h: 440, cells: [{ t: '3' }, { t: '위원' }, { t: '김지영' }, { t: '서울특별시교육청교육연구정보원 교육정책연구소', size: 10 }, { t: '미래혁신부장' }] },
  { h: 440, cells: [{ t: '4' }, { t: '위원' }, { t: '방담이' }, { t: '가톨릭대학교 학부대학' }, { t: '연구개발부장' }] },
  { h: 440, cells: [{ t: '5' }, { t: '간사' }, { t: '' }, { t: '' }, { t: '' }] },
]);

const F = 9.5, L = AlignmentType.LEFT;
const lab = t => ({ t, bold: true, align: L, size: F });
const val = (t = '', span) => ({ t, align: L, size: F, span });
const foot = [
  P('', { after: 60 }),
  P('', { rule: 12, after: 120 }),
  table([1100, 2112, 1100, 2113, 1100, 2113], [
    { h: 560, cells: [lab('기안자'), val(), lab('검토'), val(), lab('결재'), val('소장')] },
  ], { borders: noB, cellPad: 30 }),
  table([1100, 5325, 1100, 2113], [
    { cells: [lab('시행'), val('인류와미래연구소-        (2026.     .     .)'), lab('접수'), val()] },
    { cells: [lab('우 24328'), val('강원특별자치도 춘천시 공지로 126 춘천교육대학교 과학관 203호'), lab('누리집'), val('https://inshnf.com')] },
  ], { borders: noB, cellPad: 30 }),
  table([1100, 2112, 1100, 2113, 1100, 2113], [
    { cells: [lab('전화'), val('033-260-6461'), lab('전송'), val(), lab('전자우편'), val('hjyoon@cnue.ac.kr')] },
    { cells: [lab('공개 구분'), val('공개', 5)] },
  ], { borders: noB, cellPad: 30 }),
];

const body = [
  ...head,
  P([run('수신자', { bold: true }), run('    내부결재')], { before: 120 }),
  P('(경유)', { before: 40 }),
  P([run('제  목', { bold: true }), run('    [인류와미래연구소] 2026학년도 「인류와미래연구소 운영위원회」 위원 구성(안)', { bold: true })],
    { before: 40, after: 160, rule: 6, indent: 1150, hanging: 1150 }),
  N('1. 관련 근거', 160),
  S('가. 「인류와미래연구소 규정」 제4조(구성) 및 제5조(운영세칙)'),
  S('나. 인류와미래연구소 설립추진위원회 제2차 회의 결과(2026. 11. 20.)'),
  N('2. 위 관련 근거에 따라 연구소 운영에 관한 주요 사항을 심의하기 위하여 아래와 같이 「인류와미래연구소 운영위원회」를 구성하여 운영하고자 하오니 결재하여 주시기 바랍니다.'),
  N('3. 위원회 명칭: 인류와미래연구소 운영위원회'),
  N('4. 운영 목적: 연구소 운영에 관한 주요 사항을 심의하여 연구소 사업의 원활한 추진과 설립 목적 달성을 지원'),
  N('5. 위원회 기능'),
  S('가. 연구소 운영세칙 등 운영에 필요한 사항 심의(규정 제5조)'),
  S('나. 연간 사업계획, 예산 편성 및 결산 심의'),
  S('다. 중장기 발전계획(10년 발전전략)과 학술주제, 사업 성과 점검'),
  P('6. 인류와미래연구소 운영위원회 위원 구성(안)', { before: 200, after: 120, keepNext: true }),
  members,
  N('7. 위원 임기: 2026. 11. 30. ~ 2028. 11. 29.(2년)  끝.', 160),
  ...foot,
];

const doc = new Document({
  creator: '인류와미래연구소', title: '인류와미래연구소 운영위원회 위원 구성(안)',
  styles: { default: { document: { run: { font: FONT, size: pt(11.5) } } } },
  sections: [{ properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1134, bottom: 1134, left: 1134, right: 1134 } } }, children: body }],
});
Packer.toBuffer(doc).then(b => { fs.writeFileSync(out, b); console.log('saved', out); });
