import base64
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.boundsPen import BoundsPen
from fontTools.pens.transformPen import TransformPen
P='/tmp/pt/package/dist/public/static/'
def glyph_paths(fontfile, text, size, x0, baseline, track=0.0):
    f=TTFont(fontfile); gs=f.getGlyphSet(); cmap=f.getBestCmap(); upm=f['head'].unitsPerEm; hm=f['hmtx']
    s=size/upm; x=x0; out=[]
    for i,ch in enumerate(text):
        g=cmap[ord(ch)]; pen=SVGPathPen(gs)
        tp=TransformPen(pen,(s,0,0,-s,x,baseline)); gs[g].draw(tp)
        out.append(pen.getCommands()); x+=hm[g][0]*s + (track if i<len(text)-1 else 0)
    return ' '.join(out), x-x0
def width(fontfile,text,size,track=0):
    f=TTFont(fontfile); cmap=f.getBestCmap(); upm=f['head'].unitsPerEm; hm=f['hmtx']
    return sum(hm[cmap[ord(c)]][0] for c in text)*size/upm + track*(len(text)-1)
def ink_bounds(fontfile,text,size):
    f=TTFont(fontfile); gs=f.getGlyphSet(); cmap=f.getBestCmap(); upm=f['head'].unitsPerEm
    ys=[]
    for ch in text:
        if ch==' ': continue
        bp=BoundsPen(gs); gs[cmap[ord(ch)]].draw(bp)
        if bp.bounds: ys+= [bp.bounds[1],bp.bounds[3]]
    return min(ys)*size/upm, max(ys)*size/upm
KR='인류와미래연구소'; EN='INSTITUTE FOR HUMANITY & FUTURE'
E=256; GAP=44
krsz=104; krf=P+'Pretendard-ExtraBold.otf'; enf=P+'Pretendard-SemiBold.otf'
krw=width(krf,KR,krsz,-2)
ensz=27
# tracking so EN width == KR width
base=width(enf,EN,ensz); track=(krw-base)/(len(EN)-1)
kl,kh=ink_bounds(krf,KR,krsz); el,eh=ink_bounds(enf,EN,ensz)
LINE=22
blockh=(kh-kl)+LINE+(eh-el)
top=(E-blockh)/2
kr_base=top+kh; en_base=kr_base-kl+LINE+eh
X=E+GAP
krp,_=glyph_paths(krf,KR,krsz,X,kr_base,-2)
enp,_=glyph_paths(enf,EN,ensz,X,en_base,track)
W=int(X+krw+6); H=E
def svg(emblem_png, kr_col, en_col, bg=None):
    b64=base64.b64encode(open(emblem_png,'rb').read()).decode()
    bgr=f'<rect width="100%" height="100%" fill="{bg}"/>' if bg else ''
    return f'''<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 {W} {H}" width="{W}" height="{H}">
<title>인류와미래연구소 INSTITUTE FOR HUMANITY &amp; FUTURE</title>{bgr}
<image width="{E}" height="{E}" href="data:image/png;base64,{b64}" xlink:href="data:image/png;base64,{b64}"/>
<path fill="{kr_col}" d="{krp}"/>
<path fill="{en_col}" d="{enp}"/>
</svg>'''
import os; R=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))+'/'
open('/tmp/logo/hnf_logo_horizontal_color.svg','w').write(svg(R+'_tools/forms/logo-blue.png','#0b1633','#0c2e86'))
open('/tmp/logo/hnf_logo_horizontal_white.svg','w').write(svg(R+'images/logo.png','#ffffff','#e3cf9c'))
print(W,H,track)
