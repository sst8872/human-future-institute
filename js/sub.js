/* =========================================================
   서브페이지 동작 (sub.js)
   탭 · 필터 · 게시판 검색 · 연혁 · 행사 달력 · 갤러리 확대 ·
   자주 묻는 질문 · D-day · 숫자 올라가기 · 인쇄 · 주소 복사
   ========================================================= */
(function () {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  /* 알림 띠 */
  const toast = msg => {
    let t = $('.toast');
    if (!t) { t = document.createElement('div'); t.className = 'toast'; document.body.appendChild(t); }
    t.textContent = msg; t.classList.add('on');
    clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove('on'), 2200);
  };

  /* 인쇄 · 주소 복사 */
  $$('.btn-print').forEach(b => b.addEventListener('click', () => window.print()));
  $$('.btn-share').forEach(b => b.addEventListener('click', () => {
    const url = location.href;
    (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject())
      .then(() => toast('페이지 주소를 복사했습니다.'))
      .catch(() => { prompt('아래 주소를 복사하세요.', url); });
  }));

  /* 탭: [data-tabs] 단추 → 같은 순서의 [data-panel] */
  $$('[data-tabs]').forEach(tabs => {
    const scope = tabs.parentElement;
    const panels = $$(':scope > [data-panel]', scope);
    const btns = $$('button', tabs);
    const show = i => {
      btns.forEach((b, k) => b.classList.toggle('on', k === i));
      panels.forEach((p, k) => p.classList.toggle('on', k === i));
      panels[i] && $$('.rv', panels[i]).forEach(el => el.classList.add('in'));
      panels[i] && $$('.swiper', panels[i]).forEach(el => el.swiper?.update());
    };
    btns.forEach((b, i) => b.addEventListener('click', () => show(i)));
    show(0);
  });

  /* 필터: .filter[data-target] 단추 data-f → 대상 안의 [data-cat] */
  $$('.filter[data-target]').forEach(f => {
    const items = $$('[data-cat]', $(f.dataset.target));
    $$('button', f).forEach(b => b.addEventListener('click', () => {
      $$('button', f).forEach(x => x.classList.toggle('on', x === b));
      const v = b.dataset.f;
      items.forEach(it => it.classList.toggle('hide', v !== 'all' && !it.dataset.cat.split(' ').includes(v)));
      const cnt = $(f.dataset.counter || '_none_');
      if (cnt) cnt.textContent = items.filter(i => !i.classList.contains('hide')).length;
    }));
  });

  /* 게시판: 분류 탭 + 제목 검색 (표의 tr[data-cat]) */
  $$('.board-wrap').forEach(w => {
    const rows = $$('tbody tr', w), empty = $('.board-empty', w), cnt = $('.board-count b', w);
    let cat = 'all', q = '';
    const apply = () => {
      let n = 0;
      rows.forEach(r => {
        const ok = (cat === 'all' || r.dataset.cat === cat || r.classList.contains('pin')) &&
          (!q || r.textContent.toLowerCase().includes(q));
        r.style.display = ok ? '' : 'none'; if (ok && !r.classList.contains('pin')) n++;
      });
      if (empty) empty.style.display = n ? 'none' : 'block';
      if (cnt) cnt.textContent = n;
    };
    $$('.b-cats button', w).forEach(b => b.addEventListener('click', () => {
      $$('.b-cats button', w).forEach(x => x.classList.toggle('on', x === b)); cat = b.dataset.f; apply();
    }));
    const form = $('.b-search', w);
    form?.addEventListener('submit', e => { e.preventDefault(); q = $('input', form).value.trim().toLowerCase(); apply(); });
    apply();
  });

  /* 화면에 들어오면 나타나기 · 막대 채우기 · 숫자 올라가기 */
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    const el = e.target; io.unobserve(el);
    if (el.classList.contains('rv')) el.classList.add('in');
    if (el.dataset.w) el.style.width = el.dataset.w;
    if (el.dataset.count) {
      const end = +el.dataset.count, t0 = performance.now(), dur = 1400;
      const step = t => { const p = Math.min((t - t0) / dur, 1); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))).toLocaleString(); if (p < 1) requestAnimationFrame(step); };
      requestAnimationFrame(step);
    }
  }), { threshold: .15 });
  $$('.rv,[data-w],[data-count]').forEach(el => io.observe(el));

  /* 연혁: 왼쪽 연대 메뉴 */
  const hnav = $$('.hist-nav a[href^="#"]');
  if (hnav.length) {
    const secs = hnav.map(a => $(a.getAttribute('href'))).filter(Boolean);
    const spy = () => {
      let cur = secs[0];
      secs.forEach(s => { if (s.getBoundingClientRect().top < 200) cur = s; });
      hnav.forEach(a => a.classList.toggle('on', a.getAttribute('href') === '#' + cur.id));
    };
    addEventListener('scroll', spy, { passive: true }); spy();
  }

  /* 자주 묻는 질문 */
  $$('.faq button').forEach(b => b.addEventListener('click', () => {
    const li = b.parentElement, a = $('.a', li), on = !li.classList.contains('on');
    $$('.faq li').forEach(x => { x.classList.remove('on'); $('.a', x).style.maxHeight = 0; });
    if (on) { li.classList.add('on'); a.style.maxHeight = a.scrollHeight + 'px'; }
  }));

  /* D-day */
  const dd = $('.dday[data-date]');
  if (dd) {
    const end = new Date(dd.dataset.date).getTime();
    const tick = () => {
      let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
      const v = [Math.floor(s / 86400), Math.floor(s % 86400 / 3600), Math.floor(s % 3600 / 60), s % 60];
      $$('b', dd).forEach((b, i) => b.textContent = String(v[i]).padStart(2, '0'));
    };
    tick(); setInterval(tick, 1000);
  }

  /* 연구진 슬라이드 */
  if (window.Swiper && $('.ppl-swiper')) {
    new Swiper('.ppl-swiper', {
      slidesPerView: 1.2, spaceBetween: 16, loop: true, speed: 300,
      autoplay: { delay: 4000, disableOnInteraction: false },
      navigation: { prevEl: '.ppl-wrap .prev', nextEl: '.ppl-wrap .next' },
      breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 }, 1280: { slidesPerView: 4, spaceBetween: 20 } }
    });
  }

  /* 행사 일정: 목록 ↔ 달력 */
  const calWrap = $('.cal-wrap');
  if (calWrap && window.EVENTS) {
    const list = $('.sch-list'), sw = $$('.view-sw button');
    sw.forEach(b => b.addEventListener('click', () => {
      sw.forEach(x => x.classList.toggle('on', x === b));
      const cal = b.dataset.v === 'cal';
      calWrap.classList.toggle('on', cal); list.classList.toggle('off', cal);
    }));
    let y = 2026, m = 10; // 0부터: 10 = 11월
    const grid = $('.cal', calWrap), title = $('.cal-head strong', calWrap);
    const render = () => {
      title.textContent = `${y}. ${String(m + 1).padStart(2, '0')}`;
      const first = new Date(y, m, 1).getDay(), days = new Date(y, m + 1, 0).getDate(), prev = new Date(y, m, 0).getDate();
      let h = '일월화수목금토'.split('').map(d => `<div class="dw">${d}</div>`).join('');
      const cells = Math.ceil((first + days) / 7) * 7;
      for (let i = 0; i < cells; i++) {
        const d = i - first + 1, out = d < 1 || d > days, dn = out ? (d < 1 ? prev + d : d - days) : d;
        const key = `${y}-${String(m + 1).padStart(2, '0')}-${String(dn).padStart(2, '0')}`;
        const evs = out ? '' : window.EVENTS.filter(e => e[0] <= key && key <= (e[1] || e[0])).map(e => `<span class="ev ${e[3] || ''}">${e[2]}</span>`).join('');
        const cls = [out && 'out', i % 7 === 0 && 'sun', i % 7 === 6 && 'sat', key === '2026-11-20' && 'today'].filter(Boolean).join(' ');
        h += `<div class="${cls}"><span class="d">${dn}</span>${evs}</div>`;
      }
      grid.innerHTML = h;
    };
    $('.cal-head .pv', calWrap).addEventListener('click', () => { m--; if (m < 0) { m = 11; y--; } render(); });
    $('.cal-head .nx', calWrap).addEventListener('click', () => { m++; if (m > 11) { m = 0; y++; } render(); });
    render();
  }

  /* 갤러리 확대 보기 */
  const gal = $('.gal');
  if (gal) {
    const lb = document.createElement('div');
    lb.className = 'lb';
    lb.innerHTML = '<button type="button" class="x" aria-label="닫기">✕</button><button type="button" class="pv" aria-label="이전">‹</button><img alt=""><p></p><button type="button" class="nx" aria-label="다음">›</button>';
    document.body.appendChild(lb);
    let idx = 0;
    const vis = () => $$('a', gal).filter(a => !a.classList.contains('hide'));
    const open = i => { const v = vis(); idx = (i + v.length) % v.length; $('img', lb).src = v[idx].getAttribute('href'); $('p', lb).textContent = v[idx].dataset.cap; lb.classList.add('on'); };
    gal.addEventListener('click', e => { const a = e.target.closest('a'); if (!a) return; e.preventDefault(); open(vis().indexOf(a)); });
    $('.x', lb).onclick = () => lb.classList.remove('on');
    $('.pv', lb).onclick = () => open(idx - 1);
    $('.nx', lb).onclick = () => open(idx + 1);
    lb.addEventListener('click', e => { if (e.target === lb) lb.classList.remove('on'); });
    addEventListener('keydown', e => { if (!lb.classList.contains('on')) return; if (e.key === 'Escape') lb.classList.remove('on'); if (e.key === 'ArrowLeft') open(idx - 1); if (e.key === 'ArrowRight') open(idx + 1); });
  }

  /* 뉴스레터 구독 (서버가 없으므로 화면 안내만) */
  $$('.nl-form').forEach(f => f.addEventListener('submit', e => {
    e.preventDefault();
    const ok = $('input[type=checkbox]', f).checked;
    $('.msg', f).textContent = ok ? '구독 신청이 접수되었습니다. 확인 메일을 보내 드립니다.' : '개인정보 수집·이용에 동의해 주세요.';
  }));

  /* 참여 신청: 보낸 뒤 결과 알림(?sent=1 · ?dup=1 · ?err=1) */
  const apForm = $('.apply-form');
  if (apForm) {
    const q = new URLSearchParams(location.search);
    const key = ['sent', 'dup', 'err'].find(k => q.has(k));
    if (key) {
      const m = $('.apply-msg[data-msg="' + key + '"]');
      if (m) { m.hidden = false; setTimeout(() => m.scrollIntoView({ block: 'center' }), 50); }
      if (key !== 'err') apForm.hidden = true;
    }
    apForm.addEventListener('submit', () => { const b = $('button[type=submit]', apForm); if (b) { b.disabled = true; b.textContent = '보내는 중…'; } });
  }

  /* 자리만 있는 링크(#) 누르면 안내 */
  $$('.sub-content a[href="#"]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); toast('실제 운영 시 연결될 링크입니다.'); }));
})();
