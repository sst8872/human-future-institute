# 인간과미래연구소 홈페이지 — Claude 작업 안내

## 🖥️ 컴퓨터 구별 (작업 전에 get_device_info의 deviceName으로 확인)
| 장치 이름 | 컴퓨터 | 작업 폴더 | 할 수 있는 일 |
|---|---|---|---|
| `book-o7dkh035u2` | **랩탑** | `D:\인간과 미래 연구소` | 전부: 테마 디자인·게시물 수정, git commit·push·pull, **SSH 배포**(2026-11-21 18시 사용자 허용) |
| (PC에서 연결할 때 적기) | **PC** | `D:\사이트\사이트 판매\인간과 미래 연구소` | 전부(위와 같음) |

- **랩탑에서도 SSH로 직접 배포한다**(2026-11-21 18시 사용자가 허용, 이전의 「랩탑은 서버 접속 금지」 규칙은 폐기)
  - SSH는 클라우드 작업공간(`Bash`)에서는 막혀 있음 → **device_bash(랩탑 Linux VM)**에서 실행. 랩탑 폴더는 device_bash에서 `$HOME/mnt/인간과 미래 연구소`
  - 열쇠: `_tools/wp/.ssh/inshnf_laptop`(개인 열쇠)·`.pub`, 서버 주소: `_tools/wp/.deploy` — 둘 다 .gitignore에 있어 git에 안 올라감. 공개 열쇠는 hPanel → 고급 → SSH 액세스에 「claude-laptop」으로 등록
  - 마운트 폴더는 권한을 600으로 못 맞추므로 배포 때 열쇠를 `$HOME/k`로 복사해 `chmod 600` 후 사용:
    ```
    cd "$HOME/mnt/인간과 미래 연구소" && git pull --ff-only
    install -m600 _tools/wp/.ssh/inshnf_laptop $HOME/k && bash _tools/wp/deploy_wp.sh $HOME/k
    ```
  - 랩탑 폴더에서 git을 쓰려면 세션마다 device_request_delete_permission으로 삭제 권한을 받아야 함(안 받으면 .git에 잠금 파일이 남음)
- **고친 뒤 확인 질문 없이 바로 inshnf.com에 반영한다**(2026-11-21 18:41 사용자 지시: 「진행이라고 말하지 않아도 그냥 진행」). 반영 뒤 inshnf.com을 1440·1100·iPhone 13으로 확인
- SSH가 안 될 때의 대안: Chrome(Claude in Chrome) 관리 화면 → 외모 → 테마 파일 편집기(작은 수정) 또는 `build_wp.py`로 만든 `inshnf.zip`을 테마 업로드 → 「현재 테마를 업로드한 것으로 교체」
- WordPress 로그인은 사용자가 직접 한다(비밀번호를 읽거나 입력하지 않음)
- 랩탑 Chrome은 프로필이 둘이라 Claude 확장이 두 개(「Browser 1」「Browser 2」)로 잡힌다. **inshnf.com에 로그인된 쪽은 Browser 2**(2026-11-21 확인). 관리 화면이 로그인 화면(`reauth=1`)으로 튕기면 사용자에게 다시 로그인을 부탁하기 전에 `list_connected_browsers`로 다른 프로필을 쓰고 있지 않은지부터 확인

## 세션 시작 때
1. `add_repo(sst8872/human-future-institute, push)` → 클라우드에 clone해서 고치고 Claude가 직접 push
2. 랩탑 폴더 연결(device_request_folder_access) → 삭제 권한 → `git pull --ff-only`
3. 프로젝트 문서 **「claude/inshnf_WordPress_이전.md」**의 「0. 인수인계」를 읽고 이어서 작업

## 참여 신청 (11-21)
- 화면 `event-apply.html`(WP 페이지 slug `event-apply`, ID 164) → `admin-post.php?action=hnf_apply`. 처리 `_tools/wp/theme/inc/apply.php`: 글 종류 `hnf_apply`(비공개) meta `hnf_a`, 같은 행사·같은 이메일 중복 막기, 관리자 이메일 알림, 관리 화면 「연구소 관리 → 참여·집필 신청」(신청 종류별 탭·건수, 처음엔 최근 신청이 들어온 탭, 목록·CSV·삭제)
- 새 행사 신청을 열려면 `hnf_apply_events()`에 키 추가 + 신청 화면 `<input name="event">` 값
- 인사이트 집필 신청(11-22): 화면 `insight-apply.html`(WP 페이지 ID 327), event 키 `insight-writer`, 칸은 `hnf_apply_fields('insight-writer')`(제목·개요 포함, 개요는 여러 줄 600자). 안내 공지는 공지사항 › 일반 글 ID 328(작성 연구개발부)
- 글 본문에 한 줄로 `[단추 글자 | 주소]`를 쓰면 가운데 단추가 된다(write.php `HNF_BTN_RE`)

## 기본 규칙
- 사이트: https://inshnf.com (WordPress, 테마 `inshnf`) · 옛 사이트 https://sst8872.github.io/human-future-institute/
- 디자인 원본은 이 저장소의 HTML·CSS·JS. WordPress 테마는 `_tools/wp/build_wp.py`로 만들고 `bash _tools/wp/deploy_wp.sh <SSH 열쇠>`로 올림
- 글꼴은 고딕 Pretendard만(명조 금지). 로고 그림은 원본 그대로
- 고친 뒤 Playwright로 1440·1100·iPhone 13 확인(가로 넘침 0, 모바일 메뉴 실제 탭)
- 커밋 메시지는 한국어로, 무엇을 고쳤는지
- 비밀번호는 Claude가 읽거나 입력하지 않음

## 영문 소개 사이트 (11-22)
- 주소 inshnf.com/en/ (WP 부모 페이지 `en` ID 338, 하위 greeting·vision·strategy·people·research·projects·contact = 339~345)
- 원본 글은 `_tools/en/make_en.py` → 실행하면 `en*.html` 생성 → 평소처럼 build·배포. `page.php`가 하위 페이지를 `부모-자식` 틀(en-greeting.php)로 그림
- 국문 머리글 사이트맵 옆 KOR|ENG(모든 컴퓨터 폭), 휴대폰 전체 메뉴 머리의 ENG(컴퓨터 화면은 감춤), 바닥글 English. 게시판(공지·소식)은 국문만
- 이름 로마자·직위·소속 영문, 연구 과제 영문 제목은 연구소 확인 필요
