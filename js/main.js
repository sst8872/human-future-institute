/* 인간과미래연구소 — 공통 스크립트 */
(function () {
  const $ = (s, p = document) => p.querySelector(s);
  const $$ = (s, p = document) => [...p.querySelectorAll(s)];
  const header = $('#header');

  /* 1. GNB 메가메뉴: 마우스를 올리면 헤더가 흰색으로 바뀌고 2차 메뉴가 펼쳐짐 */
  const gnb = $('.gnb');
  const bottom = $('.hd-bottom');
  if (gnb && bottom) {
    const layout = () => {
      let max = 0;
      const base = $('.inner', bottom).getBoundingClientRect().left;
      /* 메뉴 8칸을 모두 같은 너비로 (가장 긴 메뉴 이름 기준, 자리가 모자라면 나눠 가짐) — 11-21 */
      const items = $$('.gnb>ul>li', gnb);
      items.forEach(li => { li.style.minWidth = ''; li.style.width = ''; });
      if (items.length && items[0].offsetParent) {
        const label = Math.max(...items.map(li => Math.ceil($('a', li).getBoundingClientRect().width)));
        const room = Math.floor((gnb.getBoundingClientRect().width - 48) / items.length); // 로고와 48px 띄움
        const W = Math.min(Math.max(label, room), 150); // 메뉴 영역을 8칸이 똑같이 나눔 (한 칸 최대 150px)
        items.forEach(li => { li.style.width = W + 'px'; });
      }
      items.forEach(li => {
        const d = $('.depth2', li);
        if (!d || !li.offsetParent) return;
        d.style.left = (li.getBoundingClientRect().left - base) + 'px';
        d.style.minWidth = ''; d.style.width = li.offsetWidth + 'px';
        max = Math.max(max, d.scrollHeight);
      });
      header.style.setProperty('--menu-h', max + 'px');
    };
    layout(); window.addEventListener('resize', layout); window.addEventListener('load', layout);
    gnb.addEventListener('mouseenter', () => { layout(); header.classList.add('open'); });
    bottom.addEventListener('mouseleave', () => header.classList.remove('open'));
    gnb.addEventListener('focusin', () => { layout(); header.classList.add('open'); });
    gnb.addEventListener('focusout', e => { if (!gnb.contains(e.relatedTarget)) header.classList.remove('open'); });
  }

  /* 로고 영문 맞춤 — 상단 로고·푸터 엠블럼의 영문(INSTITUTE FOR ...) 장평을 줄여 한글 이름 길이와 같게 */
  const fitLogo = () => $$('.logo span, .f-emblem').forEach(sp => {
    const b = $('b', sp), em = $('em', sp);
    if (!b || !em || !em.offsetWidth) return;
    em.style.setProperty('--em-fit', 1);
    const r = b.getBoundingClientRect().width / em.getBoundingClientRect().width;
    em.style.setProperty('--em-fit', r.toFixed(4));
  });
  fitLogo();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fitLogo);
  window.addEventListener('resize', fitLogo);

  /* 2. 드롭다운 (Language, 주요 키워드) */
  $$('.drop>button').forEach(b => b.addEventListener('click', e => {
    e.stopPropagation();
    const d = b.parentElement, was = d.classList.contains('on');
    $$('.drop').forEach(x => { x.classList.remove('on'); $('button', x).setAttribute('aria-expanded', 'false'); });
    if (!was) { d.classList.add('on'); b.setAttribute('aria-expanded', 'true'); }
  }));
  document.addEventListener('click', () => $$('.drop').forEach(x => x.classList.remove('on')));

  /* 3. 검색창 (모바일) */
  $$('.btn-search').forEach(btn => btn.addEventListener('click', () => {
    const l = $('#searchLayer'); l.classList.toggle('on');
    if (l.classList.contains('on')) $('input', l).focus();
  }));

  /* 4. 전체 메뉴 — GNB 내용을 그대로 복사해서 만듦 */
  const all = $('#allmenu');
  if (all && gnb) {
    $('.allmenu-body', all).innerHTML = $$('.gnb>ul>li', gnb).map(li =>
      $('.depth2', li)
        ? `<div><h4>${$('a', li).textContent}</h4>${$('.depth2', li).innerHTML}</div>`
        : `<div><h4><a href="${$('a', li).getAttribute('href')}">${$('a', li).textContent}</a></h4></div>`).join('');
  }
  const dim = $('.dim');
  const openLayer = el => { el.classList.add('on'); el.setAttribute('aria-hidden', 'false'); dim?.classList.add('on'); document.body.classList.add('lock'); };
  const closeLayers = () => { $$('.allmenu,.quickmenu').forEach(el => { el.classList.remove('on'); el.setAttribute('aria-hidden', 'true'); }); dim?.classList.remove('on'); document.body.classList.remove('lock'); };
  $$('.btn-menu').forEach(b => b.addEventListener('click', () => openLayer(all)));
  $('.f-quick')?.addEventListener('click', () => openLayer($('#quickmenu')));
  $$('.btn-close').forEach(b => b.addEventListener('click', closeLayers));
  dim?.addEventListener('click', closeLayers);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLayers(); });

  /* 4-2. 링크(체인) 버튼 — 누르면 메뉴가 열리고, 항목을 누르면 푸터의 해당 패널이 펼쳐짐 */
  const linkWrap = $('.f-link-wrap');
  const relBtns = $$('.f-rel-btn'), panels = $$('.f-panel');
  const openPanel = (i, scroll) => {
    relBtns.forEach((b, k) => { const on = k === i && !b.classList.contains('on'); b.classList.toggle('on', on); b.setAttribute('aria-expanded', on); });
    panels.forEach((p, k) => p.classList.toggle('on', relBtns[k].classList.contains('on')));
    if (scroll) { relBtns.forEach((b, k) => { b.classList.toggle('on', k === i); b.setAttribute('aria-expanded', k === i); }); panels.forEach((p, k) => p.classList.toggle('on', k === i));
      $('#footerLinks')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  };
  relBtns.forEach((b, i) => b.addEventListener('click', () => openPanel(i, false)));
  if (linkWrap) {
    const lb = $('.f-link', linkWrap);
    lb.addEventListener('click', e => {
      e.stopPropagation();
      const on = linkWrap.classList.toggle('on');
      lb.setAttribute('aria-expanded', on);
    });
    $$('.f-link-pop button', linkWrap).forEach(b => b.addEventListener('click', e => {
      e.stopPropagation();
      linkWrap.classList.remove('on'); lb.setAttribute('aria-expanded', 'false');
      openPanel(+b.dataset.rel, true);
    }));
    document.addEventListener('click', e => { if (!linkWrap.contains(e.target)) { linkWrap.classList.remove('on'); lb.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') linkWrap.classList.remove('on'); });
  }

  /* 5. 맨 위로 */
  $('.f-gotop')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  /* 6. 메인 비주얼 — 옆으로 밀리는 무한 반복 슬라이드
        5초마다 넘김(DELAY), 넘어가는 시간 1.2초(css/identity.css 의 .track.anim).
        슬라이드에 data-delay="6000" 을 넣으면 그 슬라이드만 더 오래 보여 줍니다. */
  const visual = $('.visual');
  if (visual) {
    const DELAY = 5000;
    const track = $('.track', visual);
    const real = $$('.slide', track);
    const N = real.length;
    // 앞뒤로 복제 슬라이드를 붙여 끊김 없이 이어지게 함
    track.insertBefore(real[N - 1].cloneNode(true), real[0]);
    track.appendChild(real[0].cloneNode(true));
    $$('.slide', track).forEach((el, k) => { if (k === 0 || k === N + 1) el.setAttribute('aria-hidden', 'true'); });
    const all = $$('.slide', track);
    const cur = $('.cur', visual), bar = $('.bar i', visual), pauseBtn = $('.pause', visual);
    $('.total', visual).textContent = String(N).padStart(2, '0');
    let pos = 1, timer = null, playing = true, moving = false;

    const setX = (px = 0, anim = false) => {
      track.classList.toggle('anim', anim);
      track.style.transform = `translateX(calc(${-pos * 100}% + ${px}px))`;
    };
    const mark = () => {
      const realIdx = (pos - 1 + N) % N;
      all.forEach((el, k) => el.classList.toggle('on', k === pos || (pos === 0 && k === N) || (pos === N + 1 && k === 1)));
      cur.textContent = String(realIdx + 1).padStart(2, '0');
      visual.style.setProperty('--prog', N > 1 ? realIdx / (N - 1) : 0);
    };
    const runBar = () => {
      bar.classList.remove('run'); void bar.offsetWidth;
      if (playing) bar.classList.add('run');
    };
    const delayOf = () => +(all[pos] && all[pos].dataset.delay) || DELAY;
    const schedule = () => {
      clearTimeout(timer);
      visual.style.setProperty('--delay', delayOf() + 'ms');
      if (playing) timer = setTimeout(() => go(pos + 1), delayOf());
      runBar();
    };
    const go = n => {
      if (moving) return;
      moving = true; pos = n; mark(); setX(0, true); schedule();
    };
    track.addEventListener('transitionend', e => {
      if (e.target !== track) return;
      moving = false;
      if (pos === 0) { pos = N; setX(); }
      if (pos === N + 1) { pos = 1; setX(); }
      mark();
    });
    $('.next', visual).addEventListener('click', () => go(pos + 1));
    $('.prev', visual).addEventListener('click', () => go(pos - 1));
    pauseBtn.addEventListener('click', () => {
      playing = !playing;
      pauseBtn.classList.toggle('play', !playing);
      pauseBtn.setAttribute('aria-label', playing ? '자동 넘김 멈춤' : '자동 넘김 시작');
      schedule();
    });
    // 손가락·마우스로 밀어서 넘기기
    let sx = 0, dx = 0, drag = false;
    track.addEventListener('pointerdown', e => {
      if (moving || e.target.closest('a,button')) return;
      drag = true; sx = e.clientX; dx = 0; clearTimeout(timer); track.setPointerCapture(e.pointerId);
    });
    track.addEventListener('pointermove', e => { if (!drag) return; dx = e.clientX - sx; setX(dx); });
    const end = () => {
      if (!drag) return; drag = false;
      if (Math.abs(dx) > 60) go(pos + (dx < 0 ? 1 : -1)); else { setX(0, true); schedule(); }
    };
    track.addEventListener('pointerup', end); track.addEventListener('pointercancel', end);
    // 다른 탭을 보는 동안은 멈춤
    document.addEventListener('visibilitychange', () => { if (document.hidden) clearTimeout(timer); else schedule(); });
    setX(); mark(); schedule();
  }

  /* 7. 이용자별 바로가기 탭 */
  $$('.quick-user .tabs button').forEach(b => b.addEventListener('click', () => {
    $$('.quick-user .tabs button').forEach(x => x.classList.toggle('on', x === b));
    $$('.q-links').forEach(u => u.classList.toggle('on', u.id === b.dataset.tab));
  }));

  /* 8. 공지사항 — 아래 목록만 고치면 화면에 반영됩니다 */
  const notices = [
    { cat: '일반', title: '[필독] 연말 연구소 휴무 안내 (12.29 ~ 01.02)', date: '2026.11.18', fix: true },
    { cat: '학술행사', title: '제14회 주간 포럼 「뇌-컴퓨터 인터페이스와 ‘나’의 경계」 참여 안내', date: '2026.11.20', fix: true },
    { cat: '학술행사', title: '현장 교사 간담회 참가 신청 안내 (12.05)', date: '2026.11.19' },
    { cat: '학술행사', title: '지역 도서관·평생학습 기관 간담회 개최 안내 (12.12)', date: '2026.11.17' },
    { cat: '학술행사', title: '2026 학술대회 사전 등록 안내', date: '2026.11.15' },
    { cat: '학술행사', title: '2026 학술대회 발표 신청 안내', date: '2026.11.10' },
    { cat: '일반', title: '연계 사업 및 업무협약(MOU) 제안 접수 안내', date: '2026.11.08' },
    { cat: '일반', title: 'Weekly Insight 필진 모집', date: '2026.10.25' }
  ];
  const PIN = '<svg class="pin" viewBox="0 0 24 24"><path d="M4 10v4h3l7 4V6L7 10z"/><path d="M17 9c1 1 1 5 0 6"/></svg>';
  const list = $('#noticeList');
  const render = cat => {
    if (!list) return;
    list.innerHTML = notices.filter(n => cat === 'all' || n.cat === cat).slice(0, 9).map(n =>
      `<li>${n.fix ? PIN : ''}<span class="cat">${n.cat}</span><a href="news-notice.html">${n.title}</a><time>${n.date}</time></li>`).join('');
  };
  render('all');
  $$('.notice .tabs button').forEach(b => b.addEventListener('click', () => {
    $$('.notice .tabs button').forEach(x => x.classList.toggle('on', x === b));
    render(b.dataset.cat);
  }));

  /* 8-2. 행사 일정 — 달별 자료 (아래 목록만 고치면 됩니다) */
  const months = [
    [{ m: 'NOV', d: 27, title: '연구진 월례 세미나', tags: [['연구', '']], period: '2026.11.27 ~ 2026.11.27' },
     { m: 'NOV', d: 29, title: '홈페이지 정기 점검', tags: [['일반', 't3']], period: '2026.11.29 ~ 2026.11.29' }],
    [{ m: 'DEC', d: 12, title: '제12회 정기 콜로키엄', tags: [['행사', 't2'], ['연구', '']], period: '2026.12.12 ~ 2026.12.12' },
     { m: 'DEC', d: 19, title: '청소년 미래 교실 수료식', tags: [['교육', 't3']], period: '2026.12.19 ~ 2026.12.19' }],
    [{ m: 'JAN', d: 5, title: '청소년 미래 교실 3기 개강', tags: [['교육', 't3']], period: '2027.01.05 ~ 2027.01.23' },
     { m: 'JAN', d: 31, title: '학술지 제9호 원고 마감', tags: [['연구', '']], period: '2027.01.31 ~ 2027.01.31' }]
  ];
  const calList = $('#calList');
  const renderCal = k => {
    if (!calList) return;
    const items = months[k] || [];
    calList.innerHTML = items.length ? items.map(e =>
      `<li><span class="cal-day"><em>${e.m}</em><b>${e.d}</b></span><div class="cal-info"><div class="cal-tags">${e.tags.map(t => `<span class="${t[1]}">${t[0]}</span>`).join('')}</div><strong>${e.title}</strong><time>${e.period}</time></div></li>`).join('')
      : '<li class="cal-empty">등록된 일정이 없습니다.</li>';
  };
  renderCal(0);
  $$('#monthTabs button').forEach(b => b.addEventListener('click', () => {
    $$('#monthTabs button').forEach(x => x.classList.toggle('on', x === b));
    renderCal(+b.dataset.m);
  }));

  /* 8-3. 슬라이드 (Swiper) — 가톨릭대와 같은 설정: 4초 자동, 무한 반복 */
  if (window.Swiper) {
    const auto = { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true };
    // 행사가 하나뿐이면 넘기기 단추를 숨기고 반복·자동 넘김을 끔
    const evOne = document.querySelectorAll('.ev-swiper .swiper-slide').length < 2;
    if (evOne) document.querySelectorAll('.ev-prev, .ev-next').forEach(b => { b.style.display = 'none'; });
    if (!evOne) new Swiper('.ev-swiper', { slidesPerView: 1, spaceBetween: 10, loop: true, speed: 500, autoplay: auto,
      navigation: { prevEl: '.ev-prev', nextEl: '.ev-next' } });
    // 02 인사이트·03 연구소 소식: 끌어 넘기기 대신 양쪽 < > 단추로 넘김
    // 글이 적으면(반복에 모자라면) 끝에서 처음으로 되돌아가게(rewind), 하나뿐이면 단추를 흐리게
    const arrows = (sel, prev, next, opt, loopMin) => {
      const n = document.querySelectorAll(sel + ' .swiper-slide').length;
      if (!n || !$(sel)) return;
      // 글이 하나뿐이어도 자리(가운데 정렬)는 Swiper가 잡게 하고, 단추만 흐리게
      if (n < 2) document.querySelectorAll(prev + ',' + next).forEach(b => { b.disabled = true; });
      new Swiper(sel, Object.assign({ loop: n >= loopMin, rewind: n > 1 && n < loopMin, simulateTouch: false,
        allowTouchMove: n > 1, autoplay: n > 1 ? auto : false,
        navigation: n > 1 ? { prevEl: prev, nextEl: next } : false }, opt));
    };
    arrows('.rp-swiper', '.rp-prev', '.rp-next', { slidesPerView: 'auto', centeredSlides: true, spaceBetween: 5, speed: 400 }, 3);
    arrows('.hl-swiper', '.hl-prev', '.hl-next', { slidesPerView: 1, spaceBetween: 6, speed: 500,
      breakpoints: { 641: { slidesPerView: 2 } },
      pagination: { el: '.hl-progress', type: 'progressbar' } }, 6);
    new Swiper('.nw-swiper', { slidesPerView: 1, spaceBetween: 20, loop: true, speed: 500, autoplay: auto,
      breakpoints: { 700: { slidesPerView: 2, spaceBetween: 30 }, 1100: { slidesPerView: 3, spaceBetween: 55 } },
      navigation: { prevEl: '.nw-prev', nextEl: '.nw-next' } });
  }

  /* 8-4. SNS — 끊김 없이 계속 흐르는 띠 (마우스를 올리면 멈춤) */
  const track = $('.sns-track');
  if (track) {
    track.innerHTML += track.innerHTML;                 // 같은 목록을 한 번 더 붙여 이어지게 함
    $$('li', track).slice(track.children.length / 2).forEach(li => li.setAttribute('aria-hidden', 'true'));
    const setDur = () => track.style.setProperty('--dur', (track.scrollWidth / 2 / 30) + 's'); // 초당 30px
    setDur(); window.addEventListener('resize', setDur);
  }

  /* 8-5. 기관 안내 탭 */
  $$('.inst-tabs button').forEach(b => b.addEventListener('click', () => {
    $$('.inst-tabs button').forEach(x => x.classList.toggle('on', x === b));
    $$('.inst-pane').forEach((p, k) => p.classList.toggle('on', k === +b.dataset.i));
  }));

  /* 9. 스크롤 등장 효과 */
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
  }), { threshold: .15 });
  $$('.up').forEach((el, i) => { el.style.transitionDelay = (i % 4) * 0.08 + 's'; io.observe(el); });

  /* 10. 서브페이지 LNB 드롭다운 */
  $$('.lnb .dep>button').forEach(b => b.addEventListener('click', () => {
    const p = b.parentElement, was = p.classList.contains('on');
    $$('.lnb .dep').forEach(d => d.classList.remove('on'));
    if (!was) p.classList.add('on');
  }));
  document.addEventListener('click', e => { if (!e.target.closest('.lnb .dep')) $$('.lnb .dep').forEach(d => d.classList.remove('on')); });
  /* 이메일무단수집거부 안내 창 (11-21) — 바닥글 링크를 누르면 열림 */
  const erLinks = $$('.js-email-refuse');
  if (erLinks.length) {
    const dlg = document.createElement('dialog');
    dlg.className = 'er-dialog';
    dlg.setAttribute('aria-labelledby', 'erTitle');
    dlg.innerHTML = `
      <div class="er-head"><h2 id="erTitle" class="blind">이메일무단수집거부</h2><button type="button" class="er-close" aria-label="닫기">&times;</button></div>
      <div class="er-body">
        <p class="er-lead">본 웹사이트에 게시된 이메일 주소가 전자우편 수집 프로그램이나 그 밖의 기술적 장치를 이용하여 무단으로 수집되는 것을 거부하며,이를 위반시 정보통신망법에 의해 형사 처벌됨을 유념하시기 바랍니다.</p>
        <h3>정보통신망이용촉진 및 정보보호등에 관한 법률 <small>[일부개정 2002.12.18 법률 제06797호]</small></h3>
        <h4>제50조의 2(전자우편주소의 무단 수집행위 등 금지)</h4>
        <ol>
          <li>누구든지 전자우편주소의 수집을 거부하는 의사가 명시된 인터넷 홈페이지에서 자동으로 전자우편주소를 수집하는 프로그램 그 밖의 기술적 장치를 이용하여 전자우편주소를 수집하여서는 아니된다.</li>
          <li>누구든지 제1항의 규정을 위반하여 수집된 전자우편주소를 판매,유통하여서는 아니된다.</li>
          <li>누구든지 제1항 및 제2항의 규정에 의하여 수집,판매 및 유통이 금지된 전자우편주소임을 알고 이를 정보전송에 이용하여서는 아니된다.</li>
        </ol>
        <h4>제65조의2(벌칙) 다음 각호의 1에 해당하는 자는 1천만원 이하의 벌금에 처한다.</h4>
        <p class="er-sub">개인정보의 수집 및 보유</p>
        <ol>
          <li>제 50조 제4항의 규정을 위반하여 기술적 조치를 한 자</li>
          <li>제50조 제6항의 규정을 위반하여 영리목적의 공고성 정보를 전송한 자</li>
          <li>제 50조의2의 규정을 위반하여 전자우편주소를 수집,판매,유통 또는 정보전송에 이용한 자.</li>
        </ol>
      </div>`;
    document.body.appendChild(dlg);
    const erClose = () => { dlg.close(); };
    $('.er-close', dlg).addEventListener('click', erClose);
    dlg.addEventListener('click', e => { if (e.target === dlg) erClose(); });   /* 창 바깥(어두운 곳) 누르면 닫힘 */
    dlg.addEventListener('close', () => document.body.classList.remove('lock'));
    erLinks.forEach(a => a.addEventListener('click', e => {
      e.preventDefault();
      dlg.showModal(); document.body.classList.add('lock');
      $('.er-body', dlg).scrollTop = 0;
    }));
  }
})();
