/* 서식 글쓰기 화면: 사진·파일 고르기, 순서 바꾸기, 본문 미리 보기 */
jQuery(function ($) {
  const esc = s => s.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const inline = s => esc(s).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/(https?:\/\/[^\s<]+[^\s<.,)」』])/g, '<a href="$1">$1</a>');

  /* 본문 글자 → HTML (서버 hnf_txt2html과 같은 규칙) */
  const FIG = /^\[\s*사진\s*(\d+)\s*(?:\|\s*(.*?))?\s*\]$/;
  const block = L => {
    let h = '';
    while (L.length && /^#{2,3}\s*(.+)$/.test(L[0])) { h += '<h3>' + inline(L.shift().replace(/^#{2,3}\s*/, '')) + '</h3>'; }
    if (!L.length) return h;
    if (L.every(l => /^\s*[-•·]\s+/.test(l))) return h + '<ul>' + L.map(l => '<li>' + inline(l.replace(/^\s*[-•·]\s+/, '')) + '</li>').join('') + '</ul>';
    if (L.every(l => /^\s*\d+[.)]\s+/.test(l))) return h + '<ol>' + L.map(l => '<li>' + inline(l.replace(/^\s*\d+[.)]\s+/, '')) + '</li>').join('') + '</ol>';
    if (L.every(l => /^\s*>/.test(l))) return h + '<blockquote>' + L.map(l => inline(l.replace(/^\s*>\s?/, ''))).join('<br>') + '</blockquote>';
    return h + '<p>' + L.map(inline).join('<br>') + '</p>';
  };
  const txt2html = (t, imgs) => {
    t = t.replace(/\r\n?/g, '\n').trim();
    if (!t) return '<p class="hnf-pv-empty">본문을 쓰면 여기에 홈페이지 모양으로 보입니다.</p>';
    return t.split(/\n\s*\n/).map(b => {
      let out = '', buf = [];
      b.split('\n').map(l => l.replace(/\s+$/, '')).filter(Boolean).forEach(l => {
        const m = l.trim().match(FIG);
        if (m) {
          if (buf.length) { out += block(buf); buf = []; }
          const u = imgs[m[1]];
          out += '<figure class="tp-fig">' + (u ? `<img src="${u}" alt="">` : `<div class="hnf-noimg">사진 ${m[1]}번</div>`) + (m[2] ? '<figcaption>' + inline(m[2]) + '</figcaption>' : '') + '</figure>';
        } else buf.push(l);
      });
      if (buf.length) out += block(buf);
      return out;
    }).join('');
  };
  /* 규정 조문 → 규정 모양 */
  const rules2html = t => {
    const L = t.replace(/\r\n?/g, '\n').split('\n').map(s => s.trim()).filter(Boolean);
    if (!L.length) return '<p class="hnf-pv-empty">조문을 붙여 넣으면 여기에 규정 모양으로 보입니다.</p>';
    let h = '<div class="rg-doc">', open = false, ul = false, deep = false;
    const cu = () => { if (ul) { h += '</ul>'; ul = false; } };
    const so = () => { if (!open) { h += '<section class="rg-art">'; open = true; } };
    L.forEach(l => {
      let m;
      if ((m = l.match(/^(제\s*\d+\s*조(?:의\s*\d+)?\s*(?:\([^)]*\))?)\s*(.*)$/))) { cu(); if (open) h += '</section>'; h += '<section class="rg-art"><p class="rg-p"><b>' + esc(m[1]) + '</b> ' + inline(m[2]) + '</p>'; open = true; deep = false; }
      else if ((m = l.match(/^(부\s*칙.*)$/)) && l.length < 30) { cu(); if (open) h += '</section>'; h += '<section class="rg-art rg-sup"><p class="rg-p"><b>' + esc(m[1]) + '</b></p>'; open = true; deep = false; }
      else if ((m = l.match(/^([①-⑳])\s*(.*)$/))) { cu(); so(); h += '<p class="rg-para"><span>' + m[1] + '</span>' + inline(m[2]) + '</p>'; deep = true; }
      else if ((m = l.match(/^(\d+\.|[가-하]\.)\s*(.*)$/))) { so(); if (!ul) { h += '<ul class="rg-items' + (deep ? ' deep' : '') + '">'; ul = true; } h += '<li><span>' + m[1] + '</span>' + inline(m[2]) + '</li>'; }
      else { cu(); so(); h += '<p class="rg-p">' + inline(l) + '</p>'; }
    });
    cu(); if (open) h += '</section>';
    return h + '</div>';
  };
  $('.hnf-tx textarea').each(function () {
    const ta = $(this), pv = ta.siblings('.hnf-pv').find('.hnf-pv-body');
    const imgs = ta.data('imgs') || {};
    const up = () => pv.html(ta.data('mode') === 'rules' ? rules2html(ta.val()) : txt2html(ta.val(), imgs));
    ta.on('input', up); up();
    /* 본문에 사진 넣기: 커서 자리에 [사진 번호 | 설명] */
    let frame;
    ta.closest('.hnf-f').find('.hnf-ins-img').on('click', () => {
      const el = ta[0], at = el.selectionStart ?? el.value.length;
      if (!frame) {
        frame = wp.media({ title: '본문에 넣을 사진 — 새 사진은 「파일 업로드」 탭', library: { type: 'image' }, multiple: false, button: { text: '본문에 넣기' } });
        frame.on('select', () => {
          const a = frame.state().get('selection').first().toJSON();
          imgs[a.id] = (a.sizes?.medium_large || a.sizes?.large || a).url;
          const pos = frame._at, v = el.value;
          const before = v.slice(0, pos), after = v.slice(pos);
          const cap = (a.caption || '').trim() || '설명';
          const tok = (before && !before.endsWith('\n\n') ? (before.endsWith('\n') ? '\n' : '\n\n') : '') + `[사진 ${a.id} | ${cap}]` + (after.startsWith('\n') ? '\n' : '\n\n');
          el.value = before + tok + after;
          const c = before.length + tok.indexOf(cap);
          el.focus(); el.setSelectionRange(c, c + cap.length);
          ta.trigger('input');
        });
      }
      frame._at = at;
      frame.open();
    });
  });

  /* 사진·파일 */
  $('.hnf-media').each(function () {
    const box = $(this), one = box.hasClass('one'), files = box.hasClass('files');
    const name = box.closest('.hnf-f').data('key');
    let frame;
    box.on('click', '.pick', () => {
      if (!frame) {
        frame = wp.media({
          title: files ? '파일 고르기 — 새 파일은 「파일 업로드」 탭' : '사진 고르기 — 새 사진은 「파일 업로드」 탭',
          library: files ? {} : { type: 'image' }, multiple: one ? false : 'add',
          button: { text: files ? '이 파일 넣기' : '이 사진 넣기' }
        });
        frame.on('select', () => {
          frame.state().get('selection').each(m => {
            const a = m.toJSON();
            if (one) {
              box.find('input[type=hidden]').val(a.id);
              box.find('.hnf-thumbs').html(`<span class="it" data-id="${a.id}"><img src="${(a.sizes?.medium || a).url}"><button type="button" class="x" title="빼기">✕</button></span>`);
            } else if (files) {
              if (box.find(`.it[data-id=${a.id}]`).length) return;
              box.find('.hnf-flist').append(`<li class="it" data-id="${a.id}"><span class="nm">${esc(a.filename)}</span><input type="hidden" name="files[]" value="${a.id}"><button type="button" class="x" title="빼기">✕</button></li>`);
            } else {
              if (box.find(`.it[data-id=${a.id}]`).length) return;
              box.find('.hnf-thumbs').append(`<span class="it" data-id="${a.id}"><img src="${(a.sizes?.thumbnail || a).url}"><input type="hidden" name="f[${name}][]" value="${a.id}"><button type="button" class="x" title="빼기">✕</button></span>`);
            }
          });
          dirty = true;
        });
      }
      frame.open();
    });
    box.on('click', '.x', function () {
      $(this).closest('.it').remove();
      if (one) box.find('input[type=hidden]').val('');
      dirty = true;
    });
  });
  $('.sortable').sortable({ items: '.it', tolerance: 'pointer', update: () => { dirty = true; } });

  /* 저장 안 하고 나가기 경고 */
  let dirty = false;
  $('#hnfForm').on('input change', () => { dirty = true; }).on('submit', () => { dirty = false; });
  $(window).on('beforeunload', e => { if (dirty) { e.preventDefault(); return ''; } });
});
