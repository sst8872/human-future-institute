#!/usr/bin/env python3
"""영문 소개 페이지(en*.html) 만들기.

- 원본은 이 파일 안의 영문 글. 고칠 때는 여기를 고치고 다시 실행한다.
    python3 _tools/en/make_en.py
- 결과: 저장소 맨 위의 en.html, en-greeting.html … (WordPress 주소 /en/, /en/greeting/ …)
- 머리글·바닥글·로고는 국문 페이지와 같은 틀(로고 글자는 원본 그대로 국문+영문).
"""
import os

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
V_STYLE, V_SUB, V_ID, V_MAIN = '20261122e', '20261122s', '20261122s', '20261122s'

# (파일, 메뉴 묶음, 제목, 대표 그림, 묶음 영문)
PAGES = [
    ('en', None, 'Welcome', 'inst01.jpg', 'sv-about.jpg', 'WELCOME'),
    ('en-greeting', 'about', 'Greeting', 'inst01.jpg', 'sv-about.jpg', 'ABOUT US'),
    ('en-vision', 'about', 'Vision', 'inst01.jpg', 'sv-about.jpg', 'ABOUT US'),
    ('en-strategy', 'about', 'Development Strategy', 'inst01.jpg', 'sv-about.jpg', 'ABOUT US'),
    ('en-organization', 'about', 'Organization', 'inst01.jpg', 'sv-about.jpg', 'ABOUT US'),
    ('en-people', 'about', 'People', 'inst01.jpg', 'sv-about.jpg', 'ABOUT US'),
    ('en-research', 'research', 'Research Areas', 'hero02.jpg', 'sv-research.jpg', 'RESEARCH'),
    ('en-projects', 'research', 'Research Projects', 'hero02.jpg', 'sv-research.jpg', 'RESEARCH'),
    ('en-results', 'research', 'Members&rsquo; Research', 'hero02.jpg', 'sv-research.jpg', 'RESEARCH'),
    ('en-contact', 'contact', 'Contact', 'inst01.jpg', 'sv-location.jpg', 'CONTACT'),
]
GROUPS = [
    ('about', 'About', [('en-greeting', 'Greeting'), ('en-vision', 'Vision'), ('en-strategy', 'Strategy'), ('en-organization', 'Organization'), ('en-people', 'People')]),
    ('research', 'Research', [('en-research', 'Research Areas'), ('en-projects', 'Research Projects'), ('en-results', 'Members&rsquo; Research')]),
    ('contact', 'Contact', []),
]
GROUP_HREF = {'about': 'en-greeting', 'research': 'en-research', 'contact': 'en-contact'}
# 각 영문 페이지와 짝이 되는 국문 페이지(KOR 단추)
KO = {'en': 'index', 'en-greeting': 'about-greeting', 'en-vision': 'about-vision', 'en-strategy': 'about-history',
      'en-people': 'about-people', 'en-research': 'research-fields', 'en-projects': 'research-projects',
      'en-contact': 'about-location', 'en-organization': 'about-organization',
      'en-results': 'research-results'}

CUR = ' class="cur"'
PRINT_SVG = '<svg viewBox="0 0 24 24"><path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="9" rx="2"/><path d="M7 14h10v7H7z"/><circle cx="17.5" cy="12" r=".6"/></svg>'
HOME_SVG = '<svg viewBox="0 0 24 24"><path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/></svg>'

ADDR = 'Room 203, Science Building, Chuncheon National University of Education, Chuncheon, Gangwon State, Republic of Korea'
TEL, TEL_HREF = '+82-33-260-6461', 'tel:+82332606461'
MAIL = 'hjyoon@cnue.ac.kr'


def gnb(cur_group):
    out = []
    for key, name, kids in GROUPS:
        on = ' class="on"' if key == cur_group else ''
        if kids:
            sub = ''.join(f'<li><a href="{f}.html">{t}</a></li>' for f, t in kids)
            out.append(f'          <li{on}><a href="{GROUP_HREF[key]}.html">{name}</a>\n'
                       f'            <div class="depth2"><ul>\n              {sub}\n            </ul></div></li>')
        else:
            out.append(f'          <li{on}><a href="{GROUP_HREF[key]}.html">{name}</a></li>')
    return '\n'.join(out)


def lnb(fn, group, title):
    if not group:
        return ''
    g1 = ''.join(f'<li{CUR if k == group else ""}><a href="{GROUP_HREF[k]}.html">{n}</a></li>' for k, n, _ in GROUPS)
    gname = [n for k, n, _ in GROUPS if k == group][0]
    kids = [x for k, _, x in GROUPS if k == group][0]
    d2 = ''
    if kids:
        g2 = ''.join(f'<li{CUR if f == fn else ""}><a href="{f}.html">{t}</a></li>' for f, t in kids)
        d2 = f'\n    <div class="dep"><button type="button" aria-expanded="false">{title}<i class="chev"></i></button><ul>{g2}</ul></div>'
    return f'''<nav class="lnb" aria-label="Breadcrumb">
  <div class="sub-inner">
    <a href="en.html" class="home" aria-label="Home">{HOME_SVG}</a>
    <div class="dep"><button type="button" aria-expanded="false">{gname}<i class="chev"></i></button><ul>{g1}</ul></div>{d2}
    <div class="lnb-tools">
      <button type="button" class="btn-print" aria-label="Print">{PRINT_SVG}</button>
    </div>
  </div>
</nav>'''


def page(fn, group, title, img, sv, svg_group, body):
    ko = KO[fn] + '.html'
    return f'''<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{title} | Institute for Human &amp; Future</title>
<meta name="description" content="Institute for Human &amp; Future — human-centered research on the future in an era of technological transformation">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap">
<link rel="icon" href="images/favicon.png">
<link rel="stylesheet" href="lib/swiper-bundle.min.css">
<link rel="stylesheet" href="css/style.css?v={V_STYLE}">
<link rel="stylesheet" href="css/sub.css?v={V_SUB}">
<link rel="stylesheet" href="css/identity.css?v={V_ID}">
</head>
<body class="sub sub-about lang-en">

<a class="skip" href="#main">Skip to content</a>

<!-- ========== HEADER (영문) ========== -->
<header class="header" id="header">
  <div class="hd-top">
    <div class="inner">
      <a class="m-lang" href="{ko}" lang="ko" aria-label="한국어">KOR</a>
      <h1 class="logo">
        <a href="en.html">
          <img src="images/logo.png" alt="HNF logo" class="logo-img">
          <span><b>인간과미래연구소</b><em>INSTITUTE FOR HUMAN &amp; FUTURE</em></span>
        </a>
      </h1>
      <ul class="util"></ul>
      <button type="button" class="m-menu btn-menu" aria-label="Open menu"><span></span><span></span><span></span></button>
    </div>
  </div>
  <div class="hd-bottom">
    <div class="inner">
      <button type="button" class="btn-menu" aria-label="Open menu"><span></span><span></span><span></span></button>
      <nav class="gnb" aria-label="Main menu">
        <ul>
{gnb(group)}
        </ul>
      </nav>
      <div class="hd-right">
        <div class="lang"><a href="{ko}" lang="ko">KOR</a><span>|</span><b>ENG</b></div>
      </div>
    </div>
    <div class="gnb-bg"></div>
  </div>
</header>

<!-- 전체 메뉴 -->
<div class="allmenu" id="allmenu" aria-hidden="true">
  <div class="allmenu-hd"><strong>Menu</strong><a class="am-lang" href="{ko}" lang="ko">KOR</a><button type="button" class="btn-close" aria-label="Close menu">✕</button></div>
  <div class="allmenu-body"></div>
</div>

<main id="main">
<section class="sub-visual" style="--img:url(../images/{img});--dim:0.55;--sv:url(../images/{sv})">
  <p class="sv-en">INSTITUTE FOR HUMAN &amp; FUTURE</p>
  <p class="sv-group">{svg_group}</p>
</section>
{lnb(fn, group, title)}
<section class="sub-content">
  <div class="sub-inner">
    <div class="sub-title"><h3>{title}</h3></div>
{body}
  </div>
</section>
</main>

<!-- ========== FOOTER (영문) ========== -->
<footer class="footer">
  <div class="f-bottom">
    <div class="inner">
      <div class="f-row1">
        <ul class="f-links">
          <li><a href="en-contact.html">Contact</a></li><li><a href="index.html" lang="ko">한국어</a></li>
        </ul>
      </div>
      <div class="f-row2">
        <div class="f-emblem"><img src="images/logo.png" alt="" class="logo-img"><b>인간과미래연구소</b><em>INSTITUTE FOR HUMAN &amp; FUTURE</em></div>
        <div class="f-info">
          <address><ul>
            <li><p><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-6-5.5-6-11a6 6 0 0 1 12 0c0 5.5-6 11-6 11z"/><circle cx="12" cy="10" r="2.2"/></svg>Institute</p><span>Room 203, Science Building, Chuncheon National University of Education (TEL) {TEL} (E-MAIL) {MAIL}</span></li>
          </ul></address>
          <p class="copy">COPYRIGHT ⓒ 2026 INSTITUTE FOR HUMAN &amp; FUTURE. ALL RIGHTS RESERVED.</p>
        </div>
      </div>
    </div>
  </div>
</footer>

<div class="float-btns">
  <button type="button" class="f-gotop" aria-label="Back to top"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V5M5 12l7-7 7 7"/></svg></button>
</div>
<div class="dim"></div>

<script src="lib/swiper-bundle.min.js"></script>
<script src="js/main.js?v={V_MAIN}"></script>
<script src="js/sub.js"></script>
</body>
</html>
'''


# ---------------------------------------------------------------- 본문
BODY = {}

BODY['en'] = '''
<p class="c-lead">The Institute for Human &amp; Future is an interdisciplinary research institute<br>based at Chuncheon National University of Education, Korea.</p>
<div class="v-banner en-banner"><strong>Leading future discourse centered on human values<br>in an era of technological transformation</strong></div>
<p class="c-txt en-intro">Today&rsquo;s technological change asks not only what humans can do, but what it means to be human. Bringing together researchers in science education, the humanities, the social sciences, education, and technology, the Institute studies how technology, the humanities and society, and the environment are changing together &mdash; and shares its findings with schools, local communities, and civil society.</p>
<ul class="en-links">
  <li><a href="en-greeting.html"><small>ABOUT</small><strong>Greeting</strong><span>A message from the Director</span><i class="chev r"></i></a></li>
  <li><a href="en-vision.html"><small>ABOUT</small><strong>Vision</strong><span>Our vision, purposes, and main projects</span><i class="chev r"></i></a></li>
  <li><a href="en-strategy.html"><small>ABOUT</small><strong>Development Strategy</strong><span>The first 10-year cycle, 2026&ndash;2035</span><i class="chev r"></i></a></li>
  <li><a href="en-organization.html"><small>ABOUT</small><strong>Organization</strong><span>Divisions and their roles</span><i class="chev r"></i></a></li>
  <li><a href="en-people.html"><small>ABOUT</small><strong>People</strong><span>Our researchers</span><i class="chev r"></i></a></li>
  <li><a href="en-research.html"><small>RESEARCH</small><strong>Research Areas</strong><span>Four areas of inquiry</span><i class="chev r"></i></a></li>
  <li><a href="en-projects.html"><small>RESEARCH</small><strong>Research Projects</strong><span>Current projects and progress</span><i class="chev r"></i></a></li>
  <li><a href="en-results.html"><small>RESEARCH</small><strong>Members&rsquo; Research</strong><span>Articles, reports, and presentations</span><i class="chev r"></i></a></li>
</ul>
<p class="tbl-note en-note">News and notices are published in Korean. For inquiries in English, please contact us at <a href="mailto:hjyoon@cnue.ac.kr">hjyoon@cnue.ac.kr</a>.</p>
'''

BODY['en-greeting'] = '''
<div>
  <div class="grt">
    <div class="grt-l">
      <h4 class="grt-q">Shaping a human-centered discourse on the future</h4>
      <p class="grt-lead">Technology has continually expanded the possibilities of human life. Yet today&rsquo;s technological transformation goes beyond the question of what humans can do and asks us anew what it is to be human. The Institute for Human &amp; Future was founded to take up this question as a subject of scholarly inquiry.</p>
      <div class="grt-box"><h5>Why the humanities and social sciences matter more than ever</h5><p>The future is something we choose before it is something we predict. Deciding what kind of future is desirable cannot rest on technological efficiency alone; it calls for reflection on human dignity, justice in our communities, and coexistence with nature. This is why the humanities and social sciences are needed all the more in an age of technology.</p></div>
      <div class="grt-box"><h5>An interdisciplinary research community</h5><p>The Institute aims to be an interdisciplinary community in which researchers from science education, the humanities, the social sciences, education, and science and technology work together. Where different disciplinary perspectives meet, we seek a balanced understanding of the future and alternatives that can be put into practice.</p></div>
      <div class="grt-box"><h5>To schools and to society</h5><p>Building on rigorous research and open scholarly exchange, the Institute will return its findings to classrooms and to society, and contribute to shaping a human-centered discourse on the future.</p><p style="margin-top:14px">Thank you.</p></div>
      <div class="grt-sign"><p>Director, Institute for Human &amp; Future <b>Heojeong Yoon</b></p><img src="images/sign.png" alt="Signature of Heojeong Yoon" class="sign-img"></div>
    </div>
  </div>
</div>
'''

BODY['en-vision'] = '''
<div class="vrow rv"><h4>Vision</h4>
  <div class="v-banner"><strong>Leading future discourse centered on human values<br>in an era of technological transformation</strong></div></div>
<div class="vrow rv"><h4>Founding Purposes</h4>
  <ol class="goal">
    <li><span class="n">01</span><strong>Exploring enduring human values</strong><p>In an age when technology is reshaping human capabilities and boundaries, we explore the values that make us human, such as dignity and autonomy, relationships and empathy.</p></li>
    <li><span class="n">02</span><strong>Setting future directions for education and society</strong><p>Reading changes in technology, the humanities and society, and the environment together, we propose concrete directions for education and the tasks society must prepare for.</p></li>
    <li><span class="n">03</span><strong>Reappraising and disseminating the value of the humanities and social sciences</strong><p>In debates about the future that center on technology, we reaffirm the academic and social significance of the humanities and social sciences, and disseminate our research to schools, local communities, and civil society.</p></li>
  </ol></div>
<div class="vrow rv"><h4>Three Perspectives</h4>
  <div>
    <p style="text-align:center;margin-bottom:10px"><b style="font-size:18px;color:#111;box-shadow:inset 0 -9px 0 #f3e8c9">Reading technology, the humanities and society, and the environment together</b></p>
    <div class="circles">
      <div class="cc"><svg viewBox="0 0 56 56" stroke="#1d44d8"><rect x="16" y="16" width="24" height="24" rx="3"/><rect x="23" y="23" width="10" height="10"/><path d="M22 10v6M28 10v6M34 10v6M22 40v6M28 40v6M34 40v6M10 22h6M10 28h6M10 34h6M40 22h6M40 28h6M40 34h6"/></svg><strong>Technology</strong></div><span class="plus">+</span>
      <div class="cc"><svg viewBox="0 0 56 56" stroke="#c9a24a"><circle cx="20" cy="20" r="6"/><circle cx="36" cy="20" r="6"/><path d="M8 44c1-9 6-13 12-13s11 4 12 13M24 44c1-9 6-13 12-13s11 4 12 13"/></svg><strong>Humanities &amp;<br>Society</strong></div><span class="plus">+</span>
      <div class="cc"><svg viewBox="0 0 56 56" stroke="#1c7a45"><path d="M12 44C12 22 26 10 46 10c0 22-12 34-34 34z"/><path d="M12 44L32 24"/></svg><strong>Environment</strong></div>
    </div>
  </div></div>
<h4 class="c-tit rv" style="margin-top:90px"><small>MAIN PROJECTS</small>Main Projects</h4>
<ul class="biz rv"><li><b>01</b><p>Humanities research on human nature and values in an era of technological transformation</p></li><li><b>02</b><p>Research on the directions and methods of education in response to changes in future society</p></li><li><b>03</b><p>Social science research on how AI and other emerging technologies affect individuals and communities</p></li><li><b>04</b><p>Research on human-centered technology ethics and education for future citizenship</p></li><li><b>05</b><p>Identifying policy agendas and making policy recommendations to prepare for future society</p></li><li><b>06</b><p>Conferences, forums, and lectures, and exchange with research institutions in Korea and abroad</p></li><li><b>07</b><p>Translating, publishing, and collecting books and materials on humanity and the future</p></li></ul>
<p style="text-align:center;margin-top:50px"><a href="en-strategy.html" class="btn">Development Strategy <i class="chev r"></i></a></p>
'''

STAGES = [('st1', 'Forecasting Future Change', '2026 ~ 2028'), ('st2', 'Exploring Future Challenges', '2029 ~ 2031'),
          ('st3', 'Transition to the Future', '2032 ~ 2034')]
PERS = [('p-tech', 'Technology'), ('p-hum', 'Humanities &amp; Society'), ('p-env', 'Environment')]
THEMES = [
    'AI and Other Emerging Technologies: Redefining the Concept of the Human',
    'The Technology Divide and New Inequalities',
    'The End of the Anthropocene and Reflections on Anthropocentrism',
    'Coexistence of Humans and Machines',
    'Inclusion and Solidarity beyond Divides',
    'Human Life and the Challenges of the Ecological Community',
    'Human-Centered Technology and Ethical Design',
    'Just Transition and Future Education',
    'Ecological Justice and a Sustainable Society',
]
FINAL = 'Evaluation of the First 10-Year Cycle and Planning for the Next Decade'
IMGS = ['research02', 'research04', 'sns10', 'sns04', 'news06', 'sns07', 'hero01', 'research05', 'sns11']
ORD = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th']


def strategy():
    rows = ''
    for i, (sid, name, yrs) in enumerate(STAGES):
        cells = ''.join(f'<td><span class="badge {PERS[j][0]} m-only">{PERS[j][1]}</span><span class="yr">{2026 + i * 3 + j} · {ORD[i * 3 + j]}</span><b>{THEMES[i * 3 + j]}</b></td>' for j in range(3))
        rows += f'<tr><th><b>{name}</b><span>{yrs}</span></th>{cells}</tr>'
    rows += f'<tr class="last"><th><b>Synthesis</b><span>2035</span></th><td colspan="3"><span class="yr">2035 · 10th</span><b>{FINAL}</b></td></tr>'
    line = ''
    for i, (sid, name, yrs) in enumerate(STAGES):
        line += f'<div class="hist-era" id="{sid}"><span>{name} · {yrs}</span></div>'
        for j in range(3):
            k = i * 3 + j
            r = ' r' if k % 2 else ''
            line += (f'<div class="hi rv{r}"><div class="ph"><img src="images/{IMGS[k]}.jpg" alt=""></div><div class="tx"><strong>{2026 + k}</strong>'
                     f'<p class="tags"><span class="badge {PERS[j][0]}">{PERS[j][1]}</span><span class="badge gray">{ORD[k]} theme</span></p>'
                     f'<p class="theme">{THEMES[k]}</p></div></div>')
    line += ('<div class="hist-era" id="st4"><span>Synthesis · 2035</span></div><div class="hi rv end"><div class="tx"><strong>2035</strong>'
             f'<p class="tags"><span class="badge navy">10th</span></p><p class="theme">{FINAL}</p>'
             '<p class="c-txt">We will synthesize and evaluate the results of the first cycle and, on that basis, set the theme and research directions for the second cycle.</p></div></div>')
    return f'''
<div class="hist">
  <ul class="hist-nav"><li><a href="#hist-top">Strategy<i class="chev r"></i></a></li><li><a href="#st1">Forecasting</a></li><li><a href="#st2">Exploring</a></li><li><a href="#st3">Transition</a></li><li><a href="#st4">Synthesis · 2035</a></li></ul>
  <div>
    <div id="hist-top">
      <p class="c-lead">From 2026 to 2035, our first cycle, we set a specific theme each year<br>under the academic theme &ldquo;Human &amp; Future&rdquo; and carry on research and academic forums.</p>
      <ul class="dot-list c-txt" style="margin-top:24px">
        <li>Systematically explore, from the perspective of the humanities and social sciences, the changes technological transformation brings to the human mode of existence, social structures, and the relationship between humans and nature</li>
        <li>Build up research in three stages: Forecasting Future Change (2026&ndash;2028), Exploring Future Challenges (2029&ndash;2031), and Transition to the Future (2032&ndash;2034)</li>
        <li>Apply a spiral research structure in which each stage cycles through three perspectives &mdash; technology, the humanities and society, and the environment &mdash; year by year, deepening our questions layer by layer</li>
        <li>In 2035, synthesize and evaluate the results of the first cycle and set the theme and research directions for the second cycle</li>
      </ul>
      <h4 class="c-tit" style="margin-top:60px"><small>THE 1ST 10-YEAR CYCLE</small>Academic Theme of the First 10-Year Cycle: &ldquo;Human &amp; Future&rdquo;</h4>
      <div class="tbl-scroll"><table class="tbl matrix en-matrix"><colgroup><col style="width:190px"><col><col><col></colgroup>
        <thead class="pc-only"><tr><th>Stage</th><th><span class="badge p-tech">Technology</span></th><th><span class="badge p-hum">Humanities &amp; Society</span></th><th><span class="badge p-env">Environment</span></th></tr></thead>
        <tbody>{rows}</tbody></table></div>
    </div>
    <div class="hist-head"><strong>HUMAN &amp; FUTURE</strong><span>2026 — 2035</span></div>
    <div class="hist-line">{line}</div>
  </div>
</div>
'''


BODY['en-strategy'] = strategy()

# 이름 로마자·직위·소속: 연구소 확인 필요
PEOPLE = [
    ('Heojeong Yoon', 'Director', 'Department of Science Education, Chuncheon National University of Education', '#c9a24a', '#7a5a1d', '윤회정'),
    ('Eunmi Park', 'Head, Planning &amp; Operations Division', 'Department of Science Education, Chuncheon National University of Education', '#2a4aa8', '#0c2e86', '박은미'),
    ('Jiyoung Kim', 'Head, Future Innovation Division', 'Education Policy Research Institute, Seoul Education Research &amp; Information Institute', '#1d6fa8', '#0c3e66', '김지영'),
    ('Dami Bang', 'Head, Research &amp; Development Division', 'Catholic College, The Catholic University of Korea', '#3a3f55', '#00103a', '방담이'),
    ('Jiyeon Na', 'Research Fellow', 'Department of Science Education, Chuncheon National University of Education', '#5b4a9a', '#2e2470', '나지연'),
    ('Soomin Lim', 'Research Fellow', 'Faculty of Creative Convergence, Chonnam National University', '#1c7a6a', '#0d4a40', '임수민'),
    ('Sunhee Kim', 'Research Fellow', 'Department of Mathematics Education, Kangwon National University', '#a8452a', '#6a2410', '김선희'),
]


def people():
    slides = ''.join(
        f'<div class="swiper-slide"><div class="ppl"><div class="avatar name" style="--a1:{a1};--a2:{a2}" lang="ko">{ko}</div>'
        f'<strong>{n}</strong><p class="ppl-pos">{pos}</p><p>{aff}</p></div></div>' for n, pos, aff, a1, a2, ko in PEOPLE)
    rows = ''.join(f'<tr><td>{i}</td><td>{pos}</td><td class="strong">{n}</td><td class="l">{aff}</td></tr>'
                   for i, (n, pos, aff, *_r) in enumerate(PEOPLE, 1))
    return f'''
<p class="c-lead">The Institute for Human &amp; Future is an interdisciplinary research community<br>of researchers from a range of disciplines.</p>
<div class="ppl-wrap" style="margin-top:50px">
  <div class="swiper ppl-swiper"><div class="swiper-wrapper">{slides}</div></div>
  <button type="button" class="sw-arr prev" aria-label="Previous"><i class="chev"></i></button>
  <button type="button" class="sw-arr next" aria-label="Next"><i class="chev"></i></button>
</div>
<h4 class="c-tit">Members</h4>
<div class="tbl-scroll"><table class="tbl en-ppl"><colgroup><col style="width:70px"><col style="width:260px"><col style="width:170px"><col></colgroup>
<thead><tr><th>No.</th><th>Position</th><th>Name</th><th class="l">Affiliation</th></tr></thead><tbody>{rows}</tbody></table></div>
'''


BODY['en-people'] = people()

DIVS = [
    ('Planning &amp; Operations Division', ['Vision and mid- to long-term development planning', 'Project planning and budgeting',
                                           'Performance management', 'External cooperation and public relations']),
    ('Future Innovation Division', ['Analysis of future trends and scenario building', 'Policy recommendations',
                                    'Partnership programs with local communities and institutions', 'Open lectures and dissemination of research findings']),
    ('Research &amp; Development Division', ['Research on human values and the role of the humanities and social sciences',
                                             'Future education research', 'Research on the social impact of technology', 'Academic events']),
]


def organization():
    cols = ''.join(f'<div class="o-col"><h5>{h}</h5><ul>' + ''.join(f'<li>{x}</li>' for x in li) + '</ul></div>' for h, li in DIVS)
    rows = ''.join(f'<tr><td class="strong">{pos}</td><td>{n}</td><td class="l">{aff}</td></tr>' for n, pos, aff, *_r in PEOPLE)
    return f'''
<div class="org rv">
  <div class="o-box o-top">Director</div>
  <div class="o-stem"></div>
  <div class="o-branch">{cols}</div>
</div>
<h4 class="c-tit">Members</h4>
<div class="tbl-scroll"><table class="tbl en-ppl en-org"><colgroup><col style="width:260px"><col style="width:170px"><col></colgroup>
<thead><tr><th>Position</th><th>Name</th><th class="l">Affiliation</th></tr></thead><tbody>{rows}</tbody></table></div>
'''


BODY['en-organization'] = organization()

# 「구성원 연구 성과」 게시판 글의 영문 표기 (WordPress에는 글마다 영문 칸으로 넣어 둠. 여기는 정적 사이트용 같은 내용)
# (WP 글 ID, 종류, 연도, 영문 제목, 영문 저자, 영문 게재지, 링크)
RESULTS = [
    (288, 'report', 2026, 'Development of a Subject Competency Framework for Competency-Based Instruction and Assessment (I): Focusing on Social Studies and Science',
     'Jiyoung Kim et al.', 'Korea Institute for Curriculum and Evaluation, Research Report RRC 2026-2', ''),
    (179, 'article', 2026, 'Exploring University Students&rsquo; Integrated Perceptions of Sustainability: A Case Study of University A in Gyeonggi Province',
     'Dami Bang, Heojeong Yoon', 'School Science Journal 20(1), 61&ndash;76', ''),
    (180, 'article', 2025, 'An Analysis of Elementary Pre-service Teachers&rsquo; Noticing Before and After Science Teaching Demonstrations',
     'Heojeong Yoon, Dami Bang', 'Journal of Korean Elementary Science Education 44(4), 666&ndash;681', 'https://doi.org/10.15267/keses.2025.44.4.666'),
    (286, 'report', 2025, 'Training for High School Administrators on Achievement-Based Assessment in Connection with Digital-Based Educational Innovation',
     'Sunhee Kim, Jiyoung Kim et al.', 'Ministry of Education &amp; Korea Institute for Curriculum and Evaluation, ORM 2025-9', ''),
    (284, 'report', 2024, 'Training for Lead Middle School Teachers on Achievement-Based Assessment in Connection with Digital-Based Educational Innovation',
     'Sunhee Kim, Jiyoung Kim et al.', 'Ministry of Education &amp; Korea Institute for Curriculum and Evaluation, ORM 2024-124, 125', ''),
    (181, 'article', 2024, 'An Analysis of Research Trends on Problem-Based Learning (PBL) in Korea',
     'Dami Bang, Heojeong Yoon', 'Journal of Research in Curriculum &amp; Instruction 28(6), 466&ndash;476', 'https://doi.org/10.24231/rici.2024.28.6.466'),
    (282, 'report', 2024, 'Training for Lead High School Teachers on Achievement-Based Assessment in Connection with Digital-Based Educational Innovation',
     'Sunhee Kim, Jiyoung Kim et al.', 'Ministry of Education &amp; Korea Institute for Curriculum and Evaluation, ORM 2024-75', ''),
    (182, 'article', 2023, 'An Analysis of Research Trends on Elementary Teacher Professionalism in Korea',
     'Dami Bang, Heojeong Yoon', 'Journal of Educational Research (Seoul Women&rsquo;s University) 88, 73&ndash;92', 'https://doi.org/10.17253/swueri.2023.88..004'),
    (264, 'article', 2023, 'Design and Implementation of an Issue-Centered Character Education Curriculum in a University General Education Course',
     'Dami Bang et al.', 'Korean Journal of General Education 17(6)', 'https://scholar.kyobobook.co.kr/article/detail/4010068298316'),
    (183, 'article', 2023, 'A Proposal for Character Education for University Students Based on &lsquo;Diversity&rsquo;',
     'Heojeong Yoon', 'Journal of Human Studies 49, 121&ndash;143', 'https://doi.org/10.21738/JHS.2023.4.49.121'),
]
TYPE = {'article': ('Article', ''), 'report': ('Report', ' gold'), 'presentation': ('Presentation', ' green')}


def results():
    items = ''
    for _id, t, y, ti, au, ve, lk in RESULTS:
        link = f'<p class="lk"><a href="{lk}" target="_blank" rel="noopener">{lk.split("//", 1)[-1]}</a></p>' if lk else ''
        items += (f'<li data-cat="{t}"><p class="yr"><b>{y}</b></p><div><span class="badge{TYPE[t][1]}">{TYPE[t][0]}</span>'
                  f'<h5>{ti}</h5><p class="by">{au} · <i>{ve}</i></p>{link}</div></li>')
    return f'''
<p class="c-lead en-res-lead">Selected publications by members of the Institute.</p>
<p class="tbl-note en-res-note">Most of these works are published in Korean; English titles are translations.</p>
<!--HNF:en-results-->
<div class="filter" data-target="#enRes"><button type="button" class="on" data-f="all">All</button><button type="button" data-f="article">Articles</button><button type="button" data-f="report">Reports</button><button type="button" data-f="presentation">Presentations</button></div>
<ul class="en-res" id="enRes">{items}</ul>
<style>.en-res .hide{{display:none}}</style>
<!--/HNF:en-results-->
'''


BODY['en-results'] = results()

FIELDS = [
    ('research03', 'Human Values', 'Asking what human nature and values mean in an era of technological transformation',
     'In an age when technology is reshaping human capabilities and boundaries, we explore from a humanities perspective the values that make us human, such as dignity and autonomy, relationships and empathy.',
     ['Human nature', 'Dignity &amp; autonomy', 'Relationships &amp; empathy', 'Humanities research']),
    ('research01', 'Future Education', 'Researching education that responds to social change',
     'Reading changes in technology, the humanities and society, and the environment together, we study the directions and methods of education. Our findings are shared with schools and local communities.',
     ['Directions of education', 'Teaching methods', 'Future education', 'Schools &amp; classrooms']),
    ('research02', 'Technology &amp; Society', 'Examining how emerging technologies affect individuals and communities',
     'We study the impact of AI and other emerging technologies on individuals and communities from a social science perspective, and identify policy issues and recommendations to prepare for the future.',
     ['Artificial intelligence', 'Technology gaps', 'Social science research', 'Policy recommendations']),
    ('research04', 'Technology Ethics &amp; Citizenship', 'Developing human-centered technology ethics and future citizenship',
     'We work toward human-centered technology ethics and study citizenship education for those who will live through an era of technological transformation.',
     ['Technology ethics', 'Ethical design', 'Future citizenship', 'Citizenship education']),
]


def research():
    rows = ''
    for i, (img, badge, h, p, kws) in enumerate(FIELDS, 1):
        kw = ''.join(f'<span>#{k}</span>' for k in kws)
        rows += (f'<div class="f-row rv"><div class="ph"><img src="images/{img}.jpg" alt=""><b>0{i}</b></div>\n'
                 f'  <div><span class="badge navy">{badge}</span><h5>{h}</h5><p>{p}</p><div class="kw">{kw}</div>\n'
                 f'  <p class="team"><span>Lead <b>Research &amp; Development Division</b></span></p></div></div>')
    return f'''
<div class="f-intro rv">
  <div class="ring"><div class="a">Human<br>Values</div><div class="b">Future<br>Education</div><div class="c">Technology<br>&amp; Society</div><div class="core">Technology<br>Ethics</div></div>
  <div><h4 class="c-tit" style="margin:0 0 20px"><small>RESEARCH FIELDS</small>Researching humans and society<br>in an age of technology</h4>
  <p class="c-txt">Deciding what kind of future is desirable cannot rest on technological efficiency alone. The Institute pursues four areas of research that together ask about human dignity, justice in our communities, and coexistence with nature.</p>
  <a href="en-strategy.html" class="btn" style="margin-top:26px">Development Strategy <i class="chev r"></i></a></div>
</div>
{rows}
'''


BODY['en-research'] = research()

PROJECTS = [
    ('University-funded research', 80,
     'The Effects of Pre-service Elementary Teachers&rsquo; Scientific Epistemological Beliefs on Their Perceptions of Generative AI Use in Science Education',
     'Survey of elementary pre-service teachers · Institute researchers'),
    ('In-house research', 20,
     'An Analysis of Differences in Generative AI Dependence and Patterns of Its Use for Assignments among University Students by Level of Need for Cognition',
     'Survey of university students · Advised by the Institute · External researchers'),
    ('In-house research', 50,
     'Redefining the Concept of the Human in the Age of AI: Fundamental Questions on Technology, Society, and Education',
     'Literature review · Institute researchers'),
]


def projects():
    items = ''.join(
        f'<li data-cat="ing"><p class="yr"><b>2026</b>~ 2027</p><div><span class="badge green">Ongoing</span> <span class="badge gold">{kind}</span>'
        f'<h5>{t}</h5><p>{m}</p></div>\n  <div><p class="pct"><span>Progress</span><b>{p}%</b></p><div class="bar"><i data-w="{p}%"></i></div></div></li>'
        for kind, p, t, m in PROJECTS)
    return f'''
<div class="filter" data-target="#projList"><button type="button" class="on" data-f="all">All</button><button type="button" data-f="ing">Ongoing</button><button type="button" data-f="done">Completed</button></div>
<ul class="proj" id="projList">{items}</ul>
<style>.proj .hide{{display:none}}</style>
'''


BODY['en-projects'] = projects()

BODY['en-contact'] = f'''
<div class="map"><iframe title="Map: Chuncheon National University of Education" src="https://www.openstreetmap.org/export/embed.html?bbox=127.7428%2C37.8562%2C127.7548%2C37.8630&amp;layer=mapnik&amp;marker=37.85961%2C127.74879" style="width:100%;height:100%;border:0" loading="lazy"></iframe></div>
<div class="addr"><div><h5>Institute for Human &amp; Future</h5><dl><dt>Address</dt><dd>{ADDR}</dd><dt>Phone</dt><dd><a href="{TEL_HREF}">{TEL}</a></dd><dt>Email</dt><dd><a href="mailto:{MAIL}">{MAIL}</a></dd></dl></div>
  <a href="https://www.google.com/maps/search/?api=1&amp;query=Chuncheon+National+University+of+Education" target="_blank" rel="noopener" class="btn-map"><svg viewBox="0 0 24 24"><path d="M12 21s-7-6-7-12a7 7 0 0 1 14 0c0 6-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>Open in Google Maps <i class="chev r"></i></a></div>

<h4 class="c-tit">Getting Here</h4>
<div class="traffic">
  <div style="--lc:#0c2e86"><div class="ico"><svg viewBox="0 0 40 40"><path d="M6 34h28M9 34V12h22v22M14 18h4M22 18h4M14 24h4M22 24h4M18 34v-5h4v5"/></svg><span>Building</span></div>
    <div><strong>Chuncheon National University of Education <em>Science Building, Room 203</em></strong><ul class="dot-list"><li>Once on campus, please come to the second floor of the Science Building.</li><li>Building locations are shown on the university&rsquo;s campus map.</li></ul></div></div>
  <div style="--lc:#1c7a45"><div class="ico"><svg viewBox="0 0 40 40"><rect x="9" y="5" width="22" height="25" rx="5"/><path d="M9 19h22M14 35l3-5M26 35l-3-5"/></svg><span>Transit</span></div>
    <div><strong>By train or bus</strong><ul class="dot-list"><li>ITX-Cheongchun / Gyeongchun Line: get off at Namchuncheon or Chuncheon Station, then take a taxi or city bus</li><li>Express / intercity bus: get off at Chuncheon Intercity Bus Terminal, then take a taxi or city bus</li></ul></div></div>
</div>
<p class="tbl-note">※ Please contact us by phone ({TEL}) or email before your visit, and we will be glad to guide you.</p>
'''


if __name__ == '__main__':
    for fn, group, title, img, sv, svg_group in PAGES:
        body = BODY[fn].replace('<br>', ' <br>')   # 모바일에서 줄바꿈을 감춰도 낱말이 붙지 않게
        open(os.path.join(ROOT, fn + '.html'), 'w', encoding='utf-8').write(page(fn, group, title, img, sv, svg_group, body))
        print(fn)
