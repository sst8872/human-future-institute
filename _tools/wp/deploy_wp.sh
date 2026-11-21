#!/bin/bash
# 테마를 다시 만들어 Hostinger(inshnf.com)에 올린다.
# 사용: bash _tools/wp/deploy_wp.sh <SSH 열쇠 파일>
# 고정 페이지·CSS·JS를 고친 뒤 이 스크립트를 실행하면 사이트에 반영된다(게시판 글은 건드리지 않음).
set -e
KEY=${1:?SSH 열쇠 파일}
REPO=$(cd "$(dirname "$0")/../.." && pwd)
OUT=$(mktemp -d -p /tmp)
python3 "$REPO/_tools/wp/build_wp.py" "$REPO" "$OUT"
SSH="ssh -i $KEY -p 65002 -o StrictHostKeyChecking=accept-new -o BatchMode=yes"
# 서버 주소는 git에 올리지 않는 _tools/wp/.deploy 에 둠 (R=계정@IP, WP=워드프레스 경로)
. "$REPO/_tools/wp/.deploy"
rsync -az --delete --chmod=D755,F644 -e "$SSH" "$OUT/inshnf/" "$R:$WP/wp-content/themes/inshnf/"
$SSH $R "cd $WP/wp-content/themes/inshnf && for f in *.php inc/*.php pages/*.php; do php -l \$f | grep -v 'No syntax errors' || true; done"
rm -rf "$OUT"
echo "올림 완료 — 첫 화면이 바로 안 바뀌면 hPanel → CDN → Flush cache"
