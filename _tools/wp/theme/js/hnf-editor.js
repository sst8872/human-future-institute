/* 인류와미래연구소 페이지 편집기 — 홈페이지 화면에서 글자·사진·항목을 바로 고친다 */
(function () {
  const C = window.HNF_EDIT;
  if (!C) return;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const dec = b => new TextDecoder().decode(Uint8Array.from(atob(b), c => c.charCodeAt(0)));
  const regions = $$('.hnf-region').filter(r => C.src[r.dataset.region] !== undefined);
  if (!regions.length) return;

  /* 알림 */
  const toast = (msg, bad) => {
    let t = $('.hnf-toast');
    if (!t) { t = document.createElement('div'); t.className = 'hnf-toast'; document.body.appendChild(t); }
    t.textContent = msg; t.classList.toggle('bad', !!bad); t.classList.add('on');
    clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove('on'), 3200);
  };

  /* 시작 단추 (서버가 그린 왼쪽 아래 단추) */
  const fab = $('.hnf-fab') || document.createElement('div');

  /* 도구 막대 */
  const bar = document.createElement('div');
  bar.className = 'hnf-bar';
  bar.innerHTML = `
    <div class="hnf-bar-in">
      <strong class="t">「${C.title}」 고치는 중</strong>
      <div class="g"><button data-c="bold" title="굵게"><b>가</b></button><button data-c="italic" title="기울임"><i>가</i></button><button data-c="link" title="링크 넣기·바꾸기">🔗 링크</button><button data-c="unlink" title="링크 빼기">링크 빼기</button></div>
      <div class="g item"><span class="lb">선택한 항목</span><button data-c="dup">복제</button><button data-c="del">삭제</button><button data-c="up">▲ 위로</button><button data-c="down">▼ 아래로</button></div>
      <div class="g img"><button data-c="img">🖼 사진 바꾸기</button></div>
      <div class="g end"><button data-c="undo" title="방금 한 입력 취소">↶</button><button data-c="redo" title="다시 실행">↷</button>
        <button data-c="save" class="pri">저장</button><button data-c="cancel">그만두기</button>
        <button data-c="more" class="more">⋯</button>
        <div class="menu"><button data-c="hist">직전 저장본으로</button><button data-c="reset">처음 상태로 (원본)</button></div></div>
    </div>
    <p class="hnf-hint">글자를 눌러 바로 고치세요. 사진을 누르면 「사진 바꾸기」, 목록·카드 한 칸을 누르면 「복제·삭제·위로·아래로」를 쓸 수 있습니다. 붙여 넣은 글은 모양 없이 글자만 들어갑니다.</p>`;
  document.body.appendChild(bar);

  let editing = false, sel = null, selImg = null, dirty = false;

  const start = () => {
    editing = true;
    document.body.classList.add('hnf-editing');
    regions.forEach(r => {
      r.innerHTML = dec(C.src[r.dataset.region]);
      // 화면 효과 대신 실제 값 보이기
      $$('[data-count]', r).forEach(el => { el.textContent = el.dataset.count; });
      $$('[data-w]', r).forEach(el => { el.style.width = el.dataset.w; });
      [...r.children].forEach(el => { el.contentEditable = 'true'; el.spellcheck = false; });
    });
    fab.style.display = 'none';
    bar.classList.add('on');
    setSel(null);
  };
  const stop = () => { location.href = location.pathname; };

  /* 항목(반복되는 칸) 찾기: 같은 모양 형제가 둘 이상인 가장 가까운 요소 */
  const itemOf = el => {
    while (el && !el.classList?.contains('hnf-region')) {
      const p = el.parentElement;
      if (p && !el.classList.contains('hnf-region')) {
        const same = [...p.children].filter(x => x.tagName === el.tagName && x.className.replace(/\s*hnf-sel\s*/, '') === el.className.replace(/\s*hnf-sel\s*/, ''));
        if (same.length >= 2 && !['B', 'I', 'EM', 'STRONG', 'SPAN', 'BR', 'A', 'SMALL', 'TD', 'TH', 'DT', 'DD'].includes(el.tagName)) return el;
      }
      el = p;
    }
    return null;
  };
  const setSel = el => {
    $$('.hnf-sel').forEach(x => x.classList.remove('hnf-sel'));
    sel = el;
    if (el) el.classList.add('hnf-sel');
    bar.querySelector('.g.item').classList.toggle('off', !el);
  };
  const setImg = img => {
    $$('.hnf-img').forEach(x => x.classList.remove('hnf-img'));
    selImg = img;
    if (img) img.classList.add('hnf-img');
    bar.querySelector('.g.img').classList.toggle('off', !img);
  };

  document.addEventListener('click', e => {
    if (!editing) return;
    const r = e.target.closest('.hnf-region');
    if (!r) return;
    const a = e.target.closest('a, button, label, summary');
    if (a) e.preventDefault();               // 고치는 동안에는 링크·단추가 움직이지 않음
    setImg(e.target.tagName === 'IMG' ? e.target : null);
    setSel(itemOf(e.target));
  }, true);
  document.addEventListener('submit', e => { if (editing && e.target.closest('.hnf-region')) e.preventDefault(); }, true);
  document.addEventListener('input', e => { if (editing && e.target.closest('.hnf-region')) dirty = true; });
  document.addEventListener('paste', e => {
    if (!editing || !e.target.closest('.hnf-region')) return;
    e.preventDefault();
    document.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text/plain'));
  });
  window.addEventListener('beforeunload', e => { if (editing && dirty) { e.preventDefault(); e.returnValue = ''; } });

  /* 저장할 HTML 만들기 */
  const htmlOf = r => {
    const c = r.cloneNode(true);
    $$('[contenteditable]', c).forEach(x => x.removeAttribute('contenteditable'));
    $$('[spellcheck]', c).forEach(x => x.removeAttribute('spellcheck'));
    $$('.hnf-sel, .hnf-img', c).forEach(x => x.classList.remove('hnf-sel', 'hnf-img'));
    $$('[class=""]', c).forEach(x => x.removeAttribute('class'));
    $$('[data-count]', c).forEach(x => { const v = x.textContent.trim().replace(/[^\d.]/g, ''); if (v) x.dataset.count = v; x.textContent = '0'; });
    $$('[data-w]', c).forEach(x => { x.style.removeProperty('width'); if (!x.getAttribute('style')) x.removeAttribute('style'); });
    return c.innerHTML;
  };

  const post = (action, extra = {}) => {
    const fd = new FormData();
    fd.append('action', action); fd.append('slug', C.slug); fd.append('nonce', C.nonce);
    Object.entries(extra).forEach(([k, v]) => fd.append(k, v));
    return fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(r => r.json());
  };

  const cmd = {
    bold: () => document.execCommand('bold'),
    italic: () => document.execCommand('italic'),
    link: () => {
      const cur = window.getSelection().anchorNode?.parentElement?.closest('a');
      const url = prompt('링크 주소를 넣으세요 (예: https://… 또는 /news-notice/)', cur ? cur.getAttribute('href') : 'https://');
      if (url === null) return;
      if (cur) cur.setAttribute('href', url); else document.execCommand('createLink', false, url);
      dirty = true;
    },
    unlink: () => { document.execCommand('unlink'); dirty = true; },
    undo: () => document.execCommand('undo'),
    redo: () => document.execCommand('redo'),
    dup: () => {
      if (!sel) return;
      const c = sel.cloneNode(true); c.classList.remove('hnf-sel');
      if (sel.parentElement.classList.contains('hnf-region')) c.contentEditable = 'true';
      sel.after(c); setSel(c); dirty = true;
      c.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    },
    del: () => {
      if (!sel) return;
      if (!confirm('선택한 항목을 지울까요?')) return;
      const n = sel.nextElementSibling || sel.previousElementSibling;
      sel.remove(); setSel(null); dirty = true;
      if (n) n.scrollIntoView({ block: 'nearest' });
    },
    up: () => { if (sel?.previousElementSibling) { sel.previousElementSibling.before(sel); dirty = true; } },
    down: () => { if (sel?.nextElementSibling) { sel.nextElementSibling.after(sel); dirty = true; } },
    img: () => {
      if (!selImg || !window.wp?.media) return;
      const f = wp.media({ title: '사진 고르기 (새 사진은 「파일 올리기」)', library: { type: 'image' }, multiple: false, button: { text: '이 사진으로 바꾸기' } });
      f.on('select', () => {
        const a = f.state().get('selection').first().toJSON();
        const size = a.sizes?.large || a.sizes?.full || a;
        selImg.src = size.url; selImg.removeAttribute('srcset'); selImg.removeAttribute('sizes');
        if (a.alt) selImg.alt = a.alt;
        dirty = true;
      });
      f.open();
    },
    save: () => {
      const data = {};
      regions.forEach(r => { data[r.dataset.region] = htmlOf(r); });
      const b = bar.querySelector('[data-c=save]'); b.disabled = true; b.textContent = '저장 중…';
      post('hnf_region_save', { regions: JSON.stringify(data) }).then(j => {
        if (!j.success) throw new Error(j.data?.msg || '저장하지 못했습니다.');
        dirty = false; toast(j.data.msg + ' 화면을 새로 엽니다.');
        setTimeout(stop, 900);
      }).catch(err => { toast(err.message || '저장하지 못했습니다. 인터넷 연결을 확인해 주세요.', true); b.disabled = false; b.textContent = '저장'; });
    },
    cancel: () => { if (!dirty || confirm('고친 내용을 저장하지 않고 그만둘까요?')) { dirty = false; stop(); } },
    more: () => bar.querySelector('.menu').classList.toggle('on'),
    hist: () => {
      if (!confirm('마지막으로 저장하기 바로 전 상태로 되돌릴까요?')) return;
      post('hnf_region_undo').then(j => { toast(j.data?.msg || '실패'); dirty = false; setTimeout(stop, 900); });
    },
    reset: () => {
      if (!confirm('이 페이지를 처음 만든 상태(원본)로 돌릴까요?\n지금 저장본은 「직전 저장본으로」로 다시 살릴 수 있습니다.')) return;
      post('hnf_region_reset').then(j => { toast(j.data?.msg || '실패'); dirty = false; setTimeout(stop, 1200); });
    }
  };
  bar.addEventListener('mousedown', e => { if (e.target.closest('button')) e.preventDefault(); }); // 글자 선택 유지
  bar.addEventListener('click', e => {
    const b = e.target.closest('button[data-c]'); if (!b) return;
    if (b.dataset.c !== 'more') bar.querySelector('.menu').classList.remove('on');
    cmd[b.dataset.c]?.();
  });
  fab.querySelector('.go')?.addEventListener('click', start);
  if (C.auto) start();
})();
