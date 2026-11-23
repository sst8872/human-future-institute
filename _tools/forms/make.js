const fs=require('fs');
const {Document,Packer,Paragraph,TextRun,Table,TableRow,TableCell,WidthType,AlignmentType,BorderStyle,ShadingType,VerticalAlign,HeightRule,Footer,PageNumber,ImageRun,TableLayoutType}=require('docx');
const LOGO=fs.readFileSync('logo-blue.png');
const FONT='맑은 고딕', W=9638; // A4, 2cm margins
const B={style:BorderStyle.SINGLE,size:4,color:'9AA3B5'};
const borders={top:B,bottom:B,left:B,right:B};
const LABEL='EEF1F7';
const t=(text,o={})=>new TextRun({text,font:FONT,size:o.size||20,bold:o.bold,color:o.color});
const p=(text,o={})=>new Paragraph({alignment:o.align,spacing:{before:o.before||0,after:o.after||0,line:o.line||300},children:Array.isArray(text)?text:[t(text,o)]});
function cell(content,w,o={}){
  const paras=(Array.isArray(content)?content:[content]).map(c=>typeof c==='string'?p(c,{bold:o.bold,align:o.align,size:o.size,color:o.color}):c);
  return new TableCell({width:{size:w,type:WidthType.DXA},borders,columnSpan:o.span,rowSpan:o.rowSpan,verticalAlign:o.valign||VerticalAlign.CENTER,
    shading:o.label?{type:ShadingType.CLEAR,color:'auto',fill:LABEL}:undefined,margins:{top:90,bottom:90,left:140,right:140},children:paras});
}
const L=(text,w,o={})=>cell(text,w,{label:true,bold:true,align:AlignmentType.CENTER,...o});
function table(cols,rows){return new Table({width:{size:W,type:WidthType.DXA},columnWidths:cols,rows});}
const row=(cells,h)=>new TableRow({children:cells,height:h?{value:h,rule:HeightRule.ATLEAST}:undefined,cantSplit:true});
const sec=(text)=>new Paragraph({spacing:{before:300,after:110},keepNext:true,children:[t(text,{size:23,bold:true,color:'0B1633'})]});
const blank=(h)=>p('',{after:h||0});
const box=(lines,h,hint)=>table([W],[row([cell(hint?[p(hint,{size:18,color:'8A93A6'})]:[p('')],W,{valign:VerticalAlign.TOP})],h)]);
// 4-col info table helper: pairs of label/value
const C4=[1700,3119,1700,3119];
function personTable(title){
  return table(C4,[
    row([L('기관명',C4[0]),cell('',C4[1]+C4[2]+C4[3],{span:3})],520),
    row([L('대표자',C4[0]),cell('',C4[1]),L('기관 유형',C4[2]),cell('',C4[3])],520),
    row([L('주소',C4[0]),cell('',C4[1]+C4[2]+C4[3],{span:3})],520),
    row([L('홈페이지',C4[0]),cell('',C4[1]+C4[2]+C4[3],{span:3})],520),
    row([L('담당자',C4[0]),cell('',C4[1]),L('부서·직위',C4[2]),cell('',C4[3])],520),
    row([L('전화',C4[0]),cell('',C4[1]),L('전자우편',C4[2]),cell('',C4[3])],520),
  ]);
}
function consent(purpose){
  const C=[2300,W-2300];
  return [
    sec('개인정보 수집·이용 동의'),
    table(C,[
      row([L('수집 항목',C[0]),cell('담당자 성명, 부서·직위, 전화번호, 전자우편',C[1])]),
      row([L('수집·이용 목적',C[0]),cell(purpose,C[1])]),
      row([L('보유·이용 기간',C[0]),cell('목적 달성 후 지체 없이 파기',C[1])]),
      row([cell([p('위 개인정보 수집·이용에 동의하지 않을 권리가 있으며, 동의하지 않을 경우 검토 및 연락이 제한될 수 있습니다.',{size:18}),
                 p('위와 같이 개인정보 수집·이용에 동의합니다.      □ 동의함      □ 동의하지 않음',{size:20,bold:true,before:80})],W,{span:2})]),
    ]),
  ];
}
function sign(sentence,who,dept){
  return [
    p(sentence,{align:AlignmentType.CENTER,before:420,after:300,size:21}),
    p('20      년      월      일',{align:AlignmentType.CENTER,after:300,size:21}),
    p(who+' :                                   (서명 또는 인)',{align:AlignmentType.RIGHT,after:360,size:21}),
    p('인류와미래연구소장 귀하',{align:AlignmentType.LEFT,after:240,size:24,bold:true}),
    new Paragraph({spacing:{before:120},border:{top:{style:BorderStyle.SINGLE,size:4,color:'9AA3B5',space:6}},children:[t('제출처 : 인류와미래연구소 '+dept+'  (TEL 033-260-6461 · E-mail hjyoon@cnue.ac.kr)',{size:18,color:'555555'})]}),
    p('※ 작성한 양식은 전자우편으로 보내 주시기 바랍니다. 칸이 부족하면 늘려 쓰거나 별지를 붙여 주십시오.',{size:18,color:'555555',before:40}),
  ];
}
function header(code,title,sub){
  const line={bottom:{style:BorderStyle.SINGLE,size:12,color:'0B1633',space:8}};
  return [
    p([t(code,{size:18,color:'555555'})],{after:60}),
    new Paragraph({alignment:AlignmentType.CENTER,spacing:{before:120,after:sub?40:200},border:sub?undefined:line,children:[t(title,{size:36,bold:true,color:'0B1633'})]}),
    ...(sub?[new Paragraph({alignment:AlignmentType.CENTER,spacing:{after:200},border:line,children:[t(sub,{size:19,color:'555555'})]})]:[]),
  ];
}

function brand(){
  // 로고+인류와미래연구소+영문을 그림 한 장으로(brand.png) — 영문 장평을 한글 폭에 맞춤, PC 글꼴과 관계없이 같게 보임
  return new Paragraph({alignment:AlignmentType.CENTER,children:[new ImageRun({type:'png',data:fs.readFileSync('brand.png'),transformation:{width:Math.round(34*1667/400),height:34},altText:{title:'인류와미래연구소',description:'인류와미래연구소 INSTITUTE FOR HUMANITY & FUTURE',name:'brand'}})]});
}
function doc(children){
  return new Document({styles:{default:{document:{run:{font:FONT,size:20}}}},
    sections:[{properties:{page:{size:{width:11906,height:16838},margin:{top:1134,bottom:1134,left:1134,right:1134}}},
      footers:{default:new Footer({children:[brand()]})},
      children}]});
}
// ===== 양식 1: 업무협약(MOU) 제안서 =====
const C2=[2300,W-2300];
const mou=[
  ...header('[양식 1]','업무협약(MOU) 제안서'),
  sec('1. 제안 기관 정보'), personTable(),
  sec('2. 협약 개요'),
  table(C2,[
    row([L('협약명(가칭)',C2[0]),cell('',C2[1])],520),
    row([L('희망 협약 기간',C2[0]),cell('20    .    .    .  ~  20    .    .    .  (    년)',C2[1])],520),
    row([L('희망 체결 시기',C2[0]),cell('20    년      월',C2[1])],520),
    row([L('협력 분야',C2[0]),cell([p('□ 공동 연구      □ 학술 행사 공동 개최      □ 교육·강좌 프로그램 운영'),p('□ 인적 교류      □ 자료·시설 공유      □ 기타 (                              )')],C2[1])]),
  ]),
  sec('3. 협약 목적'), box([],1500,'협약을 제안하는 배경과 목적을 적어 주십시오.'),
  sec('4. 주요 협력 내용'), box([],2200,'협력하려는 사업·활동의 내용을 구체적으로 적어 주십시오.'),
  sec('5. 역할 분담'),
  table([2300,3669,3669],[
    row([L('구분',2300),L('제안 기관',3669),L('인류와미래연구소',3669)]),
    row([L('주요 역할',2300),cell('',3669),cell('',3669)],900),
    row([L('지원·부담 사항',2300),cell('',3669),cell('',3669)],900),
  ]),
  sec('6. 기대 효과'), box([],1300,'협약을 통해 기대하는 성과를 적어 주십시오.'),
  sec('7. 붙임 자료'),
  table(C2,[row([L('붙임',C2[0]),cell([p('□ 기관 소개 자료 1부      □ 협약서(안) 1부'),p('□ 기타 (                                        )')],C2[1])])]),
  ...consent('업무협약 제안 검토 및 협의를 위한 연락'),
  ...sign('위와 같이 인류와미래연구소와의 업무협약을 제안합니다.','제안 기관 대표','기획운영부'),
];
// ===== 양식 2: 연계사업 운영 신청서 =====
const biz=[
  ...header('[양식 2]','연계사업 운영 신청서'),
  sec('1. 신청 기관 정보'), personTable(),
  sec('2. 사업 개요'),
  table(C4,[
    row([L('사업명',C4[0]),cell('',C4[1]+C4[2]+C4[3],{span:3})],520),
    row([L('사업 유형',C4[0]),cell([p('□ 포럼      □ 간담회      □ 학술대회      □ 강좌 운영'),p('□ 학술자료 제작      □ 기타 (                              )')],C4[1]+C4[2]+C4[3],{span:3})]),
    row([L('운영 기간',C4[0]),cell('20    .    .    .  ~  20    .    .    .',C4[1]+C4[2]+C4[3],{span:3})],520),
    row([L('운영 장소',C4[0]),cell('',C4[1]),L('운영 방식',C4[2]),cell('□ 대면  □ 비대면  □ 혼합',C4[3])],520),
  ]),
  sec('3. 사업 목적 및 필요성'), box([],1500,'사업을 운영하려는 목적과 필요성을 적어 주십시오.'),
  sec('4. 세부 운영 계획'),
  table([1000,2000,4238,2400],[
    row([L('회차',1000),L('일시',2000),L('주제 및 내용',4238),L('강사·진행자(희망)',2400)]),
    ...[1,2,3,4].map(i=>row([cell(String(i),1000,{align:AlignmentType.CENTER}),cell('',2000),cell('',4238),cell('',2400)],560)),
  ]),
  sec('5. 예산 및 요청 사항'),
  table(C2,[
    row([L('총 예산(안)',C2[0]),cell('원',C2[1],{align:AlignmentType.RIGHT})],520),
    row([L('비용 부담',C2[0]),cell('□ 신청 기관 부담      □ 공동 부담      □ 협의 필요',C2[1])],520),
    row([L('연구소 요청 사항',C2[0]),cell([p('연구소에 요청하는 사항을 적어 주십시오.',{size:18,color:'8A93A6'})],C2[1],{valign:VerticalAlign.TOP})],1500),
  ]),
  ...consent('연계사업 운영 신청 검토 및 협의를 위한 연락'),
  ...sign('위와 같이 인류와미래연구소 연계사업 운영을 신청합니다.','신청 기관 대표','미래혁신부'),
];
(async()=>{
  fs.writeFileSync('업무협약(MOU) 제안서.docx',await Packer.toBuffer(doc(mou)));
  fs.writeFileSync('연계사업 운영 신청서.docx',await Packer.toBuffer(doc(biz)));
})();
