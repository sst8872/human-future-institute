제1차 주간 포럼 포스터 원본 (2026-11-21)
- poster.html 을 Chromium(Playwright)으로 1240×1754 화면, 배율 2로 찍으면 PNG(2480×3508), page.pdf(width=1240px,height=1754px)로 PDF.
- 같은 폴더에 필요한 파일(저장소에는 안 올림): Pretendard-*.otf(npm pretendard 패키지), logo.svg(= images/brand/hnf_logo_horizontal_color.svg),
  nasa_hi.jpg(https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=2600 — NASA 사진, Unsplash 무료)
- QR: qr_apply.svg → https://inshnf.com/event-apply/ , qr_zoom.svg → Zoom 회의 링크(ID 818 1319 1411). 둘 다 읽기 확인함.
- poster_blue.html: 파란 바탕판(11-21 사용자 요청). logo_w.svg = images/brand/hnf_logo_horizontal_white.svg 필요.
- A4판: poster_blue.html에 @page{size:210mm 297mm;margin:0} html{zoom:0.6399} 넣고 page.pdf(prefer_css_page_size=True). 이미지 A4는 1240×1754(150dpi) JPG.
