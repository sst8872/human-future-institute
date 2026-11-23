import base64, os, sys
sys.path.insert(0,os.path.dirname(os.path.abspath(__file__)))
from make_logo_horizontal import glyph_paths, width, ink_bounds, KR, EN, P
E=256; krf=P+'Pretendard-ExtraBold.otf'; enf=P+'Pretendard-SemiBold.otf'
krsz=72; krw=width(krf,KR,krsz,-1.5)
ensz=19; track=(krw-width(enf,EN,ensz))/(len(EN)-1)
kl,kh=ink_bounds(krf,KR,krsz); el,eh=ink_bounds(enf,EN,ensz)
PAD=8; W=int(max(E,krw)+PAD*2)
GAP=34; LINE=18
kr_base=E+GAP+kh; en_base=kr_base-kl+LINE+eh
H=int(en_base-el+PAD)
xk=(W-krw)/2
krp,_=glyph_paths(krf,KR,krsz,xk,kr_base,-1.5)
enp,_=glyph_paths(enf,EN,ensz,xk,en_base,track)
ex=(W-E)/2
R=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))+'/'
def svg(png,kc,ec):
    b=base64.b64encode(open(png,'rb').read()).decode()
    return f'''<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 {W} {H}" width="{W}" height="{H}">
<title>인류와미래연구소 INSTITUTE FOR HUMANITY &amp; FUTURE</title>
<image x="{ex}" y="0" width="{E}" height="{E}" href="data:image/png;base64,{b}" xlink:href="data:image/png;base64,{b}"/>
<path fill="{kc}" d="{krp}"/>
<path fill="{ec}" d="{enp}"/>
</svg>'''
open(''+R+'images/brand/hnf_logo_vertical_color.svg','w').write(svg(R+'_tools/forms/logo-blue.png','#0b1633','#0c2e86'))
open(''+R+'images/brand/hnf_logo_vertical_white.svg','w').write(svg(R+'images/logo.png','#ffffff','#e3cf9c'))
print(W,H)
