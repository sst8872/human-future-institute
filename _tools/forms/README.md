# 양식 원본 (업무협약(MOU) 제안서 · 연계사업 운영 신청서)

- `make.js` — 두 양식(.docx)을 만드는 스크립트 (npm `docx` 패키지). 이 폴더에서 `node make.js` → `업무협약(MOU) 제안서.docx`, `연계사업 운영 신청서.docx`
- `brand.png` — 각 쪽 바닥 가운데 로고 묶음 그림. 다시 만들려면 `make_brand.py`
- `logo-blue.png` — images/logo.png(흰색)를 주 색 #0c2e86으로 칠한 것
- PDF: LibreOffice로 변환 `soffice --headless --convert-to pdf *.docx`
- 사이트 반영: `files/form-mou-proposal.docx|pdf`, `files/form-partnership-application.docx|pdf` 로 복사 (pub-archive.html이 이 이름을 씀)
- 글꼴: 본문 맑은 고딕(고딕). A4 2쪽 안에 들어가는지 꼭 확인
