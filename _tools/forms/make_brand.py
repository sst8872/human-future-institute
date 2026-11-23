# -*- coding: utf-8 -*-
"""양식 바닥 로고 묶음(brand.png) 만들기 — 로고 + 인류와미래연구소 + 영문(장평을 한글 폭에 맞춤)
필요: Pillow, Pretendard-ExtraBold.ttf(npm pack pretendard@1.3.9 → package/dist/public/static/alternative/),
      Roboto 가변 글꼴 Roboto[wdth,wght].ttf. 아래 두 경로를 PC에 맞게 바꿔 실행.
"""
from PIL import Image, ImageDraw, ImageFont
KO = 'Pretendard-ExtraBold.ttf'
EN = 'Roboto[wdth,wght].ttf'
K = 10
ko = ImageFont.truetype(KO, 17 * K)
en = ImageFont.truetype(EN, 9 * K); en.set_variation_by_axes([400, 100])   # [굵기, 폭]
INK = (0x0b, 0x16, 0x33, 255); INK2 = (0x0b, 0x16, 0x33, int(255 * .8))
def ink(text, font, fill):
    b = font.getbbox(text); im = Image.new('RGBA', (b[2]-b[0]+4, b[3]-b[1]+4), (0, 0, 0, 0))
    ImageDraw.Draw(im).text((-b[0]+2, -b[1]+2), text, font=font, fill=fill); return im.crop(im.getbbox())
k = ink('인류와미래연구소', ko, INK); e = ink('INSTITUTE FOR HUMANITY & FUTURE', en, INK2)
e = e.resize((k.width, e.height), Image.LANCZOS)          # 장평: 가로만 줄여 한글 폭에 맞춤
logo = Image.open('logo-blue.png').resize((40*K, 40*K), Image.LANCZOS)  # images/logo.png를 #0c2e86으로 칠한 것
gap = 10*K; vgap = int(3.5*K); th = k.height + vgap + e.height
H = max(logo.height, th); W = logo.width + gap + k.width
out = Image.new('RGBA', (W, H), (0, 0, 0, 0)); out.paste(logo, (0, (H-logo.height)//2), logo)
y = (H-th)//2; x = logo.width + gap; out.paste(k, (x, y), k); out.paste(e, (x, y+k.height+vgap), e)
out.resize((W//2, H//2), Image.LANCZOS).save('brand.png', optimize=True)
