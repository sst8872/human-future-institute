# 인간과미래연구소 홈페이지 — Claude 작업 안내

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
