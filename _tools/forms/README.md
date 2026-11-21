# 양식 원본 (업무협약(MOU) 제안서 · 연계사업 운영 신청서)

- `make.js` — 두 양식(.docx)을 만드는 스크립트 (npm `docx` 패키지). 이 폴더에서 `node make.js` → `업무협약(MOU) 제안서.docx`, `연계사업 운영 신청서.docx`
- `brand.png` — 각 쪽 바닥 가운데 로고 묶음 그림. 다시 만들려면 `make_brand.py`
- `logo-blue.png` — images/logo.png(흰색)를 주 색 #0c2e86으로 칠한 것
- `make_hwp.py` — 같은 두 양식을 한글 파일(.hwp·.hwpx)로 만든다 (`pip install python-hwpx`, 7.x). 이 폴더에서 `python3 make_hwp.py`. 한글 2020에서 「상위 버전」 경고가 안 뜨게 파일 버전을 5.1.0.1로 적는다. 표는 쪽 경계에서 나뉘고, 5번 제목 앞에서 쪽을 바꿔 docx와 같은 곳에서 2쪽이 시작된다
- PDF: LibreOffice로 변환 `soffice --headless --convert-to pdf *.docx`
- 사이트 반영: `files/form-mou-proposal.hwp|docx|pdf`, `files/form-partnership-application.hwp|docx|pdf` 로 복사 (pub-archive.html이 이 이름을 씀). WordPress에서는 글 89·141(MOU), 92·140(연계)의 첨부 파일(hnf_files: 한글 → 워드 → PDF 순)
- 글꼴: 본문 맑은 고딕(고딕). A4 2쪽 안에 들어가는지 꼭 확인
