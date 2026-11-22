#!/usr/bin/env python3
"""인간과미래연구소 정적 HTML → WordPress 테마(inshnf) 생성기.

사용: python3 _tools/wp/build_wp.py <저장소 폴더> <출력 폴더>
 - 출력 폴더/inshnf/ : 테마
 - 출력 폴더/sample.json : 예시 글 자료(처음 한 번 가져오기용)
고정 페이지는 원본 HTML을 그대로 PHP로 옮기고, 게시판 자리만 hnf_board_area()로 바꾼다.
"""
import json, os, re, shutil, sys, html

SRC, OUT = sys.argv[1], sys.argv[2]
TH = os.path.join(OUT, 'inshnf')
H = '<?=HNF_H?>'
T = '<?=HNF_T?>'

BOARDS = ['news-notice', 'news-stories', 'news-gallery', 'pub-insight',
          'pub-reports', 'pub-rules', 'pub-archive', 'research-results']
EN = lambda slug: slug == 'en' or slug.startswith('en-')   # 영문 페이지(/en/…)
SKIP = {'pub-rules-01'}   # 글로 옮김


def read(p):
    return open(p, encoding='utf-8').read()


def is_redirect(s):
    return len(s) < 1000 and 'http-equiv="refresh"' in s


def conv(s):
    """링크·자원 주소를 WordPress 주소로."""
    s = re.sub(r'(href|src)="index\.html(#[^"]*)?"', lambda m: f'{m.group(1)}="{H}/{m.group(2) or ""}"', s)
    # 영문 페이지: en.html → /en/, en-x.html → /en/x/
    s = re.sub(r'href="en(?:-([a-z0-9-]+))?\.html(#[^"]*)?"',
               lambda m: f'href="{H}/en/' + (m.group(1) + '/' if m.group(1) else '') + (m.group(2) or '') + '"', s)
    s = re.sub(r'(href|src)="([a-z0-9-]+)\.html(#[^"]*)?"',
               lambda m: f'{m.group(1)}="{H}/{m.group(2)}/{m.group(3) or ""}"', s)
    s = re.sub(r'(href|src)="(images|files|lib|css|js)/', lambda m: f'{m.group(1)}="{T}/{m.group(2)}/', s)
    s = s.replace('url(../images/', f'url({T}/images/')
    # CSS·JS 판 꼬리표는 테마 판으로
    s = re.sub(r'\?v=\d+[a-z]*"', '?v=<?=HNF_VER?>"', s)
    s = s.replace('<script src="' + T + '/js/sub.js">', '<script src="' + T + '/js/sub.js?v=<?=HNF_VER?>">')
    # 검색 상자 → WordPress 검색
    s = s.replace('<form class="search-box" onsubmit="return false" role="search"><input type="search" aria-label="검색어">',
                  f'<form class="search-box" role="search" action="{H}/" method="get"><input type="search" name="s" aria-label="검색어">')
    s = s.replace('<form onsubmit="return false"><input type="search" placeholder="검색어를 입력하세요" aria-label="검색어">',
                  f'<form action="{H}/" method="get"><input type="search" name="s" placeholder="검색어를 입력하세요" aria-label="검색어">')
    s = re.sub(r'<title>(.*?)</title>', lambda m: "<title><?=hnf_title(" + repr(html.unescape(m.group(1))) + ")?></title>", s)
    s = re.sub(r'(<ul class="f-links">.*?)(\s*</ul>)', lambda m: m.group(1) + '<?php hnf_admin_link(); ?>' + m.group(2), s, count=1, flags=re.S)
    s = s.replace('</head>', '<?php hnf_head(); ?>\n</head>', 1)
    s = s.replace('</body>', '<?php wp_footer(); ?>\n</body>', 1)
    return s


BOARD_START = ['<div style="margin-top:40px"><form class="b-search"', '<div class="board-wrap">',
               '<form class="b-search"', '<div class="filter']


def board_block(s):
    a = s.find('<div class="sub-title">')
    a = s.find('</div>', a) + len('</div>')
    b = s.find('<!-- 콘텐츠 담당부서 -->', a)
    starts = [s.find(m, a) for m in BOARD_START]
    starts = [x for x in starts if 0 <= x < b]
    return min(starts), b


END_INNER = '  </div>\n</section>\n</main>'


def region(key, slug, body):
    return (f'<div class="hnf-region" data-region="{key}"><?php hnf_region_start({key!r}, {slug!r}); ?>'
            + body + '<?php hnf_region_end(); ?></div>\n')


def strip_tags(x):
    x = re.sub(r'<svg.*?</svg>', '', x, flags=re.S)
    x = re.sub(r'<span class="new">N</span>', '', x)
    return html.unescape(re.sub(r'<[^>]+>', '', x)).strip()


def filtermap(s):
    m = re.search(r'<div class="filter[^"]*"[^>]*>(.*?)</div>', s, re.S)
    out = []
    if not m:   # 분류 단추가 없는 게시판(예: 인사이트)
        return out
    for f, t in re.findall(r'data-f="([^"]+)">([^<]+)</button>', m.group(1)):
        if f != 'all':
            out.append((f, html.unescape(t)))
    return out


def parse_board(slug, s):
    kids = filtermap(s)
    code2name = dict(kids)
    items = []
    if '<table class="tbl board"' in s:
        tb = s[s.find('<tbody>'):s.find('</tbody>')]
        for tr in re.findall(r'<tr([^>]*)>(.*?)</tr>', tb, re.S):
            attrs, body = tr
            cat = re.search(r'data-cat="([^"]+)"', attrs).group(1)
            tds = re.findall(r'<td[^>]*>(.*?)</td>', body, re.S)
            it = {'cat': code2name.get(cat, cat), 'pin': 'pin' in attrs,
                  'title': strip_tags(re.search(r'<a[^>]*>(.*?)</a>', tds[2], re.S).group(1))}
            files = re.findall(r'href="(files/[^"]+)"(?: download="([^"]+)")?', tds[2] + ''.join(tds[3:4]))
            it['files'] = list(dict.fromkeys(files))
            texts = [strip_tags(t) for t in tds]
            dates = [t for t in texts if re.fullmatch(r'\d{4}\.\d\d\.\d\d', t)]
            it['date'] = dates[0]
            if slug == 'news-notice':
                it['writer'], it['views'] = texts[4], int(texts[5])
            elif slug == 'pub-reports':
                it['views'] = int(texts[5])
            else:
                it['writer'] = texts[5]
            href = re.search(r'<a href="([^"]+)"', tds[2]).group(1)
            if href.endswith('.html'):
                it['from_html'] = href[:-5]
            items.append(it)
    elif 'class="gal"' in s:
        for a in re.findall(r'<a href="images/[^"]+" data-cat="([^"]+)" data-cap="([^"]+)"><img src="(images/[^"]+)"', s):
            items.append({'cat': code2name.get(a[0], a[0]), 'title': html.unescape(a[1]), 'img': a[2]})
    else:
        for a in re.findall(r'<a href="[^"]*" class="p-card" data-cat="([^"]+)">(.*?)</a>', s, re.S):
            code, body = a
            first = code.split()[0]
            img = re.search(r'<img src="(images/[^"]+)"', body)
            it = {'cat': code2name.get(first, first),
                  'title': strip_tags(re.search(r'<strong>(.*?)</strong>', body, re.S).group(1)),
                  'date': re.search(r'<time>([\d.]+)', body).group(1),
                  'img': img.group(1) if img else ''}
            items.append(it)
    return kids, items


def rules_post():
    s = read(os.path.join(SRC, 'pub-rules-01.html'))
    a = s.find('<div class="bv-body rg-doc">')
    b = s.find('</article>', a)
    inner = s[a + len('<div class="bv-body rg-doc">'):b]
    inner = inner[:inner.rfind('</div>')]
    return '<div class="rg-doc">' + inner.strip() + '</div>'


def main():
    if os.path.exists(TH):
        shutil.rmtree(TH)
    os.makedirs(os.path.join(TH, 'pages'))
    for d in ['css', 'js', 'lib', 'images', 'files']:
        shutil.copytree(os.path.join(SRC, d), os.path.join(TH, d))
    # 테마 자체 파일(_tools/wp/theme/)
    tsrc = os.path.join(SRC, '_tools', 'wp', 'theme')
    for root, _, fs in os.walk(tsrc):
        for f in fs:
            rel = os.path.relpath(os.path.join(root, f), tsrc)
            os.makedirs(os.path.dirname(os.path.join(TH, rel)), exist_ok=True)
            shutil.copy(os.path.join(root, f), os.path.join(TH, rel))

    # JS: .html 링크 → 새 주소, WordPress 게시판은 서버에서 거르므로 건너뜀
    for f in ['main.js', 'sub.js']:
        p = os.path.join(TH, 'js', f)
        s = read(p)
        s = re.sub(r"(['\"`])index\.html", r"\1/", s)
        s = re.sub(r"(['\"`=])([a-z0-9-]+)\.html", r"\1/\2/", s)
        if f == 'sub.js':
            s = s.replace("$$('.board-wrap')", "$$('.board-wrap:not(.wp)')")
            s = s.replace("const gal = $('.gal');", "const gal = $('.gal:not(.wp)');")
        else:
            s = s.replace('const notices = [', 'const notices = window.HNF_NOTICES || [')
            s = s.replace('<a href="/news-notice/">${n.title}</a>', '<a href="${n.url || \'/news-notice/\'}">${n.title}</a>')
        open(p, 'w', encoding='utf-8').write(s)

    pages, sample = [], {'boards': {}, 'rules': rules_post()}
    for fn in sorted(os.listdir(SRC)):
        if not fn.endswith('.html'):
            continue
        slug = fn[:-5]
        s = read(os.path.join(SRC, fn))
        if is_redirect(s) or slug in SKIP:
            continue
        title = re.search(r'<div class="sub-title"><h3>([^<]+)', s)
        if slug == 'privacy':
            # 일반 페이지·검색·404 틀
            sh = s
            a = sh.find('<div class="sub-title"><h3>') + len('<div class="sub-title"><h3>')
            b = sh.find('</h3>', a)
            sh = sh[:a] + '<?=esc_html(hnf_shell_title())?>' + sh[b:]
            a = sh.find('</div>', a) + len('</div>')
            b = sh.find('  </div>\n</section>\n</main>', a)
            sh = sh[:a] + '\n<?php hnf_shell_content(); ?>\n' + sh[b:]
            open(os.path.join(TH, 'pages', '_shell.php'), 'w', encoding='utf-8').write(conv(sh))
        if slug in BOARDS:
            kids, items = parse_board(slug, s)
            sample['boards'][slug] = {'title': title.group(1), 'kids': [k[1] for k in kids], 'items': items}
            a, b = board_block(s)
            s = s[:a] + '\n<?php hnf_board_area(); ?>\n    ' + s[b:]
            # 고칠 수 있는 구역: 게시판 위 안내 글(intro) · 담당부서(charge)
            a0 = s.find('</div>', s.find('<div class="sub-title">')) + len('</div>')
            a1 = s.find('\n<?php hnf_board_area(); ?>')
            if s[a0:a1].strip():
                s = s[:a0] + region('intro', slug, s[a0:a1]) + s[a1:]
            c0 = s.find('    <!-- 콘텐츠 담당부서 -->')
            c1 = s.find(END_INNER, c0)
            s = s[:c0] + region('charge', slug, s[c0:c1]) + s[c1:]
        elif slug not in ('index',) and not EN(slug):
            a0 = s.find('</div>', s.find('<div class="sub-title">')) + len('</div>')
            a1 = s.find(END_INNER, a0)
            assert a1 > a0, slug
            s = s[:a0] + region('main', slug, s[a0:a1]) + s[a1:]
        if slug == 'index':
            # 공지 목록 자료 + 인사이트 슬라이드
            s = s.replace('<script src="js/main.js', '<script>window.HNF_NOTICES=<?=hnf_notices_json()?>;</script>\n<script src="js/main.js', 1)
            a = s.find('<div class="swiper rp-swiper"><div class="swiper-wrapper">')
            a = s.find('<div class="swiper-wrapper">', a) + len('<div class="swiper-wrapper">')
            b = s.find('</div></div>\n  </div>\n</section>', a)
            s = s[:a] + '<?php hnf_insight_slides(); ?>' + s[b:]
            # 인사이트 실제 글이 있을 때만 02 구역을 보임(예시 슬라이드는 쓰지 않음)
            a = s.find('<section class="sec rp-sec" style="display:none">')
            assert a >= 0, 'rp-sec'
            b = s.find('</section>', a) + len('</section>')
            s = s[:a] + '<?php if (hnf_has_insight()): ?><section class="sec rp-sec">' + s[a + len('<section class="sec rp-sec" style="display:none">'):b] + '<?php endif; ?>' + s[b:]
            # 03 연구소 소식: 실제 글 제목 카드, 글이 있을 때만 구역을 보임
            a = s.find('<div class="swiper hl-swiper"><div class="swiper-wrapper">')
            assert a >= 0, 'hl-swiper'
            a += len('<div class="swiper hl-swiper"><div class="swiper-wrapper">')
            b = s.find('</div></div>\n        <button type="button" class="side-arr light hl-next"', a)
            assert b > a, 'hl-next'
            s = s[:a] + '<?php hnf_stories_slides(); ?>' + s[b:]
            a = s.find('<section class="sec hl-sec">')
            assert a >= 0, 'hl-sec'
            b = s.find('</section>', a) + len('</section>')
            s = s[:a] + '<?php if (hnf_has_stories()): ?>' + s[a:b] + '<?php endif; ?>' + s[b:]
        if slug == 'en-results':
            # 영문 연구 성과: 정적 목록 대신 「구성원 연구 성과」 게시판 글(영문 칸)을 그때그때 그림
            s = re.sub(r'<!--HNF:en-results-->.*?<!--/HNF:en-results-->', '<?php hnf_en_results(); ?>', s, flags=re.S)
        out = conv(s)
        if EN(slug):
            out = out.replace('<?php hnf_admin_link(); ?>', '')
        open(os.path.join(TH, 'pages', slug + '.php'), 'w', encoding='utf-8').write(out)
        if slug != 'index' and not EN(slug):
            pages.append({'slug': slug, 'title': html.unescape(title.group(1)) if title else slug})
    sample['pages'] = pages
    json.dump(sample, open(os.path.join(OUT, 'sample.json'), 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print('pages', len(pages), 'boards', {k: len(v['items']) for k, v in sample['boards'].items()})


main()
