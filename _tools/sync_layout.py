# -*- coding: utf-8 -*-
"""
서브페이지 27개의 머리말(헤더)·꼬리말(푸터)을 한 번에 맞추는 도구

사용법
  1. about-greeting.html 의 헤더나 푸터를 먼저 고칩니다.
     (헤더 = 파일 처음부터 <main id="main"> 앞까지, 푸터 = </main> 뒤부터 끝까지)
  2. 이 폴더에서 실행합니다:  python _tools/sync_layout.py
  3. 나머지 서브페이지(about-*, research-*, event-*, future-*, pub-*, news-*, sitemap.html)
     ※ 오시는 길(about-location)은 body class가 sub-location 이라 별도 메뉴로 표시됩니다.에
     같은 헤더·푸터가 들어갑니다. 각 페이지의 <title>, body class, 현재 메뉴 표시는 그대로 둡니다.

index.html(메인)은 헤더 모양이 달라 건드리지 않습니다. 메인의 메뉴를 고쳤다면 직접 같이 고쳐 주세요.
"""
import re, glob, os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, 'about-greeting.html')
GROUPS = ['about', 'research', 'event', 'future', 'pub', 'news', 'location', 'sitemap']


def split(text):
    head, rest = text.split('<main id="main">', 1)
    body, foot = rest.split('</main>', 1)
    return head, body, foot


def mark_menu(head, group):
    """주메뉴에서 현재 그룹(li.on) 표시를 다시 붙임"""
    nav = re.search(r'<nav class="gnb".*?</nav>', head, re.S)
    if not nav or group not in GROUPS:
        return head
    block = nav.group(0)
    # 상단 메뉴 항목만 (하위 메뉴 depth2 안의 항목은 건너뜀)
    subs = [m.span() for m in re.finditer(r'<div class="depth2">.*?</div>', block, re.S)]
    lis = [m for m in re.finditer(r'\n\s*<li(?: class="on")?><a href="[^"]+">[^<]+</a>', block)
           if not any(a <= m.start() < b for a, b in subs)]
    new = block
    for i, m in enumerate(lis):
        s = m.group(0)
        clean = s.replace('<li class="on">', '<li>')
        if i == GROUPS.index(group):
            clean = clean.replace('<li>', '<li class="on">', 1)
        new = new.replace(s, clean, 1)
    return head.replace(block, new)


def main():
    src = open(SRC, encoding='utf-8').read()
    s_head, _, s_foot = split(src)
    changed = 0
    for path in sorted(glob.glob(os.path.join(ROOT, '*-*.html')) + [os.path.join(ROOT, 'sitemap.html')]):
        if not os.path.exists(path) or os.path.samefile(path, SRC):
            continue
        text = open(path, encoding='utf-8').read()
        if '<main id="main">' not in text:
            continue
        head, body, foot = split(text)
        title = re.search(r'<title>.*?</title>', head).group(0)
        bcls = re.search(r'<body class="([^"]*)">', head).group(1)
        group = (re.search(r'sub-(\w+)', bcls) or [None, None])[1]
        new_head = re.sub(r'<title>.*?</title>', lambda m: title, s_head, count=1)
        new_head = re.sub(r'<body class="[^"]*">', f'<body class="{bcls}">', new_head, count=1)
        new_head = mark_menu(new_head, group)
        new = new_head + '<main id="main">' + body + '</main>' + s_foot
        if new != text:
            open(path, 'w', encoding='utf-8').write(new)
            changed += 1
            print('고침:', os.path.basename(path))
    print(f'끝. {changed}개 파일을 맞췄습니다.')


if __name__ == '__main__':
    main()
