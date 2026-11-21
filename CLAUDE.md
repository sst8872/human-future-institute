# 인간과미래연구소 홈페이지 — Claude 작업 안내

## 🖥️ 컴퓨터 구별 (작업 전에 get_device_info의 deviceName으로 확인)
| 장치 이름 | 컴퓨터 | 작업 폴더 | 할 수 있는 일 |
|---|---|---|---|
| `book-o7dkh035u2` | **랩탑** | `D:\인간과 미래 연구소` | 테마 디자인·게시물 수정, git commit·push·pull. 사이트 반영은 **Chrome(Claude in Chrome)으로 inshnf.com 관리 화면**에서 |
| (PC에서 연결할 때 적기) | **PC** | `D:\사이트\사이트 판매\인간과 미래 연구소` | 위 작업 + **서버 직접 수정**(SSH, `.deploy`, SSH 열쇠, `deploy_wp.sh`, 서버 폴더 파일 올리기·지우기) |

- **랩탑에서는 서버에 직접 접속하지 않는다.** SSH 접속, `deploy_wp.sh` 실행, `_tools/wp/.deploy` 만들기, SSH 열쇠 만들기·등록, 서버 폴더 파일 올리기·지우기 모두 PC 몫
- **랩탑에서 테마 디자인 반영하는 법** (모두 Chrome 관리 화면에서):
  1. 저장소의 HTML·CSS·JS를 고치고 Playwright로 확인 → commit·push
  2. 작은 CSS·PHP 수정: 외모 → 테마 파일 편집기에서 해당 파일만 고침(저장소와 같은 내용으로)
  3. 큰 수정: `python3 _tools/wp/build_wp.py <저장소> <임시폴더>`로 테마를 만들어 `inshnf.zip`으로 묶고 → 외모 → 테마 → 새로 추가 → 테마 업로드 → 「현재 테마를 업로드한 것으로 교체」
  4. 반영 전에 무엇을 바꾸는지 사용자에게 확인받고, 반영 뒤 inshnf.com을 1440·1100·iPhone 13으로 확인
- WordPress 로그인은 사용자가 직접 한다(비밀번호를 읽거나 입력하지 않음)
- 아래 「랩탑 첫 세션에서 할 일」의 2·3번(.deploy·SSH 열쇠)은 **PC에서만** 한다

## ⚠️ 랩탑 첫 세션에서 맨 먼저 할 일 (2026-11-21 PC → 랩탑 이관)
1. **git부터 맞추기** — 작업 전에 반드시:
   ```
   git fetch origin
   git status
   git log origin/main --oneline -3
   ```
   - origin/main 맨 위 3개가 `CLAUDE.md 추가`, `WordPress(inshnf.com) 이전 도구 추가`, `상단 메뉴 8칸을 같은 너비로`여야 함(그 아래 `460c10a 양식 원본 보관`)
   - 없으면 PC에서 push가 안 된 것 → 사용자에게 PC에서 `git push`를 먼저 부탁하고 작업하지 말 것
   - 있으면 `git pull --ff-only` (처음이면 `git clone https://github.com/sst8872/human-future-institute.git`)
2. **`_tools/wp/.deploy` 만들기** (서버 주소, git에 올리지 않음 — .gitignore에 있음):
   ```
   R=u196702697@145.79.30.102
   WP=domains/inshnf.com/public_html
   ```
3. **SSH 열쇠 새로 만들기** → 공개 열쇠를 사용자에게 주고 hPanel(새 Unlimited 요금제의 inshnf.com) → 고급 → SSH 액세스 → SSH 키 추가를 부탁. PC 때 열쇠 `claude-inshnf`는 지웠는지 확인
4. 그다음 프로젝트 문서 **「claude/inshnf_WordPress_이전.md」**의 「0. 인수인계」를 읽고 이어서 작업

## 기본 규칙
- 사이트: https://inshnf.com (WordPress, 테마 `inshnf`) · 옛 사이트 https://sst8872.github.io/human-future-institute/
- 디자인 원본은 이 저장소의 HTML·CSS·JS. WordPress 테마는 `_tools/wp/build_wp.py`로 만들고 `bash _tools/wp/deploy_wp.sh <SSH 열쇠>`로 올림
- 글꼴은 고딕 Pretendard만(명조 금지). 로고 그림은 원본 그대로
- 고친 뒤 Playwright로 1440·1100·iPhone 13 확인(가로 넘침 0, 모바일 메뉴 실제 탭)
- 커밋 메시지는 한국어로, 무엇을 고쳤는지
- 비밀번호는 Claude가 읽거나 입력하지 않음
