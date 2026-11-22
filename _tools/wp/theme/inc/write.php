<?php
/**
 * 서식 글쓰기: 게시판마다 정해진 칸만 채우면 테마가 같은 모양으로 그린다.
 * 화면: wp-admin/admin.php?page=hnf-write&board=<slug>[&id=<글 번호>]
 * 저장: 칸 값은 meta `hnf_f`(배열), 첨부 순서는 `hnf_files`(첨부 ID 배열), post_content는 검색용으로 다시 만든 HTML.
 */

const HNF_DEPTS = ['기획운영부', '미래혁신부', '연구개발부', '인간과미래연구소'];

/* 게시판별 칸 */
function hnf_fields($slug) {
    $body = ['body', '본문', 'text', true, '빈 줄로 문단을 나눕니다. 「## 소제목」, 「- 항목」, 「1. 항목」, 「**굵게**」, 링크는 「[글자](주소)」, 단추는 한 줄에 「[단추 글자 | 주소]」를 쓸 수 있습니다.'];
    $files = ['files', '첨부 파일', 'files', false, 'PDF·HWP·HWPX·DOCX·XLSX 등. 여러 개 가능, 끌어서 순서 바꾸기'];
    $photos = ['photos', '사진', 'images', false, '글 아래에 나옵니다(한 장이면 크게, 여러 장이면 나란히). 본문 중간에 넣으려면 본문 위 「본문에 사진 넣기」'];
    $F = [
        'news-notice' => [
            ['ev_date', '일시', 'line', false, '행사 안내일 때만 (예: 2026. 12. 5.(토) 10:00~12:00)'],
            ['ev_place', '장소', 'line', false, ''],
            ['ev_target', '대상', 'line', false, ''],
            ['ev_apply', '신청 방법', 'line', false, '예: 12. 1.(화)까지 이메일 접수 (hjyoon@cnue.ac.kr)'],
            $body, $photos, $files,
        ],
        'news-stories' => [
            ['thumb', '대표 사진', 'image', true, '목록 카드와 글 맨 위에 나옵니다. 가로 사진 권장'],
            $body,
            ['photos', '사진 더 넣기', 'images', false, '본문 아래에 사진들이 나란히 나옵니다'],
        ],
        'news-gallery' => [
            ['ev_date', '행사 일자', 'line', false, '예: 2026. 11. 8.'],
            ['ev_place', '장소', 'line', false, ''],
            ['photos', '사진', 'images', true, '첫 사진이 목록 대표 사진이 됩니다. 여러 장 한꺼번에 올릴 수 있습니다'],
            ['body', '설명', 'text', false, '짧게 (비워도 됨)'],
        ],
        'pub-insight' => [
            ['issue', '호수', 'line', true, '숫자만 쓰세요. 예: 1 → 「제1호」로 목록·글·첫 화면에 나옵니다'],
            ['author', '필자', 'line', true, '예: 윤회정 (인간과미래연구소 소장)'],
            ['summary', '요약', 'area', true, '2~3문장. 첫 화면 「인사이트」와 목록에 나옵니다(「핵심 문구」가 없으면 글 맨 위 상자에도)'],
            ['quote', '핵심 문구', 'area', false, '본문에서 가장 핵심적인 문장을 그대로 옮겨 쓰세요. 글 맨 위에 따옴표 상자로 나옵니다'],
            ['thumb', '대표 사진', 'image', false, '목록 카드·첫 화면 그림'],
            $body, $photos,
            ['refs', '참고 문헌', 'area', false, '한 줄에 하나씩'],
            ['tags', '키워드(해시태그)', 'line', false, '예: #생성형AI #교사교육 #지속가능성 (띄어 쓰면 여러 개, # 없이 써도 됨)'],
        ],
        'pub-reports' => [
            ['rep_no', '보고서 번호', 'line', false, '예: HNF 연구보고서 [2026-1호]'],
            ['lead', '연구책임자', 'line', false, ''],
            ['team', '참여 연구진', 'line', false, ''],
            ['summary', '요약', 'area', true, '보고서 내용을 3~5문장으로'],
            ['photos', '사진', 'images', false, '보고서 표지·그림 등 (선택)'],
            ['files', '보고서 파일', 'files', true, 'PDF 권장'],
        ],
        'pub-rules' => [
            ['enact', '제정·개정', 'line', false, '예: 제정 2026.11.21(규정 제1호)'],
            ['rules', '조문', 'rules', true, '한글 문서에서 조문을 그대로 복사해 붙여 넣으세요. 「제1조(목적)」「①」「1.」「부칙」을 알아서 정리합니다'],
            ['photos', '사진', 'images', false, '조직도·요람 표지 등 (선택, 조문 아래에 나옵니다)'],
            ['files', '원문 파일', 'files', false, 'HWP·PDF 원문 (선택)'],
        ],
        'pub-archive' => [
            ['summary', '설명', 'area', false, '파일에 대한 짧은 설명 (선택)'],
            ['photos', '사진', 'images', false, '양식 미리 보기 그림 등 (선택)'],
            ['files', '파일', 'files', true, '양식은 DOCX·HWP와 PDF를 함께 올리면 좋습니다'],
        ],
        'research-results' => [
            ['authors', '저자', 'line', true, '예: 윤회정, 박은미'],
            ['venue', '게재지·발표처', 'line', false, '예: 한국교육학연구 32(4), 2026 학술대회'],
            ['pub_date', '발행·발표 연월', 'line', false, '예: 2026. 11.'],
            ['link', '원문 링크', 'url', false, 'DOI·학술지 주소 (https://…)'],
            ['title_en', '영문 제목 (영문 사이트용)', 'line', false, '영문 사이트(inshnf.com/en/results/)에 나옵니다. 비우면 국문 제목이 나옵니다'],
            ['authors_en', '영문 저자', 'line', false, '예: Heojeong Yoon, Dami Bang (연구소 구성원 외 공저자가 많으면 et al.)'],
            ['venue_en', '영문 게재지·발표처', 'line', false, '예: Journal of Korean Elementary Science Education 44(4), 666–681'],
            ['summary', '요약', 'area', true, ''],
            ['thumb', '대표 사진', 'image', false, '목록 카드 그림 (없으면 무늬 그림)'],
            ['photos', '사진', 'images', false, '발표 장면·그림 등 (선택)'],
            ['files', '파일', 'files', false, ''],
        ],
    ];
    return $F[$slug] ?? [$body, $files];
}

/* ── 글자 → HTML ─────────────────────────── */
function hnf_inline($s) {
    $s = esc_html($s);
    $s = preg_replace('/\*\*(.+?)\*\*/u', '<b>$1</b>', $s);
    // 「[글자](주소)」 → 색 글자 링크 (같은 창)
    $s = preg_replace_callback('#\[([^\]]+)\]\(((?:https?://|/)[^\s)]+)\)#u', fn($m) => '<a class="tx-link" href="' . esc_url(html_entity_decode($m[2])) . '">' . $m[1] . '</a>', $s);
    $s = preg_replace('#(?<!["\'=])(https?://[^\s<]+[^\s<.,)」』])#u', '<a href="$1" target="_blank" rel="noopener">$1</a>', $s);
    return $s;
}
const HNF_FIG_RE = '/^\\[\\s*사진\\s*(\\d+)\\s*(?:\\|\\s*(.*?))?\\s*\\]$/u';
/* 본문 한 줄에 「[집필 신청서 | https://inshnf.com/insight-apply/]」 → 가운데 단추 */
const HNF_BTN_RE = '/^\\[\\s*단추\\s+(.+?)\\s*\\|\\s*((?:https?:\\/\\/|\\/)\\S+)\\s*\\]$/u';
/* 고해상도 화면에서도 선명하게: 여러 크기(srcset)를 함께 넣는다 */
function hnf_srcset($id, $size = 'large') {
    $ss = wp_get_attachment_image_srcset((int) $id, $size);
    if (!$ss) return '';
    // --mw: 이 그림이 커질 수 있는 최대 폭(원래 크기, 세로 640px 제한 반영). CSS가 width:100%와 함께 쓴다
    $m = wp_get_attachment_metadata((int) $id);
    // hnf_dpr: 2배 해상도로 만든 그림(도표 등)은 파일 폭의 절반으로 보여 줌 → 그림 속 글자가 본문 글자 크기와 비슷해짐
    $d = max(1, (int) get_post_meta((int) $id, 'hnf_dpr', true));
    $mw = (!empty($m['width']) && !empty($m['height'])) ? min((int) round($m['width'] / $d), (int) round(640 * $m['width'] / $m['height'])) : 0;
    return ' srcset="' . esc_attr($ss) . '" sizes="(max-width: 960px) 100vw, 960px"' . ($mw ? ' style="--mw:' . $mw . 'px"' : '');
}
function hnf_fig($id, $cap) {
    $u = wp_get_attachment_image_url((int) $id, 'large');
    if (!$u) return '';
    $full = wp_get_attachment_image_url((int) $id, 'full');
    $alt = $cap !== '' ? $cap : (get_post_meta($id, '_wp_attachment_image_alt', true) ?: '');
    return '<figure class="tp-fig"><a href="' . esc_url($full) . '" target="_blank"><img src="' . esc_url($u) . '"' . hnf_srcset($id) . ' alt="' . esc_attr($alt) . '" loading="lazy"></a>' . ($cap !== '' ? '<figcaption>' . hnf_inline($cap) . '</figcaption>' : '') . '</figure>';
}
function hnf_txt_block($lines) {
    $out = '';
    while ($lines && preg_match('/^#{2,3}\s*(.+)$/u', $lines[0], $m)) { $out .= '<h3>' . hnf_inline($m[1]) . '</h3>'; array_shift($lines); }
    if (!$lines) return $out;
    if (count(array_filter($lines, fn($l) => preg_match('/^\s*[-•·]\s+/u', $l))) === count($lines)) {
        return $out . '<ul>' . implode('', array_map(fn($l) => '<li>' . hnf_inline(preg_replace('/^\s*[-•·]\s+/u', '', $l)) . '</li>', $lines)) . '</ul>';
    }
    if (count(array_filter($lines, fn($l) => preg_match('/^\s*\d+[.)]\s+/u', $l))) === count($lines)) {
        return $out . '<ol>' . implode('', array_map(fn($l) => '<li>' . hnf_inline(preg_replace('/^\s*\d+[.)]\s+/u', '', $l)) . '</li>', $lines)) . '</ol>';
    }
    if (count(array_filter($lines, fn($l) => strpos(ltrim($l), '>') === 0)) === count($lines)) {
        return $out . '<blockquote>' . implode('<br>', array_map(fn($l) => hnf_inline(ltrim(substr(ltrim($l), 1))), $lines)) . '</blockquote>';
    }
    return $out . '<p>' . implode('<br>', array_map('hnf_inline', $lines)) . '</p>';
}
function hnf_txt2html($t) {
    $t = trim(str_replace(["\r\n", "\r"], "\n", (string) $t));
    if ($t === '') return '';
    $out = '';
    foreach (preg_split("/\n\s*\n/u", $t) as $blk) {
        $lines = array_values(array_filter(array_map('rtrim', explode("\n", $blk)), 'strlen'));
        $buf = [];
        foreach ($lines as $l) {
            if (preg_match(HNF_BTN_RE, trim($l), $m)) {
                if ($buf) { $out .= hnf_txt_block($buf); $buf = []; }
                $out .= '<p class="tp-btn"><a href="' . esc_url($m[2]) . '" class="btn">' . esc_html($m[1]) . ' <i class="chev r"></i></a></p>';
            } elseif (preg_match(HNF_FIG_RE, trim($l), $m)) {
                if ($buf) { $out .= hnf_txt_block($buf); $buf = []; }
                $out .= hnf_fig($m[1], trim($m[2] ?? ''));
            } else $buf[] = $l;
        }
        if ($buf) $out .= hnf_txt_block($buf);
    }
    return $out;
}
/* 규정 조문 → 규정 모양(rg-*) */
function hnf_rules2html($t, $enact = '') {
    $lines = array_values(array_filter(array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", (string) $t))), 'strlen'));
    $h = '<div class="rg-doc">' . ($enact !== '' ? '<p class="rg-meta">' . esc_html($enact) . '</p>' : '');
    $open = false; $ul = false; $deep = false;
    $close_ul = function () use (&$h, &$ul) { if ($ul) { $h .= '</ul>'; $ul = false; } };
    foreach ($lines as $l) {
        if (preg_match('/^(제\s*\d+\s*조(?:의\s*\d+)?\s*(?:\([^)]*\))?)\s*(.*)$/u', $l, $m)) {
            $close_ul(); if ($open) $h .= '</section>';
            $h .= '<section class="rg-art"><p class="rg-p"><b>' . esc_html($m[1]) . '</b>' . ($m[2] !== '' ? ' ' . hnf_inline($m[2]) : '') . '</p>';
            $open = true; $deep = false;
        } elseif (preg_match('/^(부\s*칙.*)$/u', $l, $m) && mb_strlen($l) < 30) {
            $close_ul(); if ($open) $h .= '</section>';
            $h .= '<section class="rg-art rg-sup"><p class="rg-p"><b>' . esc_html($m[1]) . '</b></p>';
            $open = true; $deep = false;
        } elseif (preg_match('/^([①-⑳])\s*(.*)$/u', $l, $m)) {
            $close_ul(); if (!$open) { $h .= '<section class="rg-art">'; $open = true; }
            $h .= '<p class="rg-para"><span>' . $m[1] . '</span>' . hnf_inline($m[2]) . '</p>';
            $deep = true;
        } elseif (preg_match('/^(\d+\.|[가-하]\.)\s*(.*)$/u', $l, $m)) {
            if (!$open) { $h .= '<section class="rg-art">'; $open = true; }
            if (!$ul) { $h .= '<ul class="rg-items' . ($deep ? ' deep' : '') . '">'; $ul = true; }
            $h .= '<li><span>' . esc_html($m[1]) . '</span>' . hnf_inline($m[2]) . '</li>';
        } else {
            $close_ul(); if (!$open) { $h .= '<section class="rg-art">'; $open = true; }
            $h .= '<p class="rg-p">' . hnf_inline($l) . '</p>';
        }
    }
    $close_ul(); if ($open) $h .= '</section>';
    return $h . '</div>';
}
function hnf_html2txt($html) {
    $html = preg_replace('#<span>([①-⑳]|\d+\.|[가-하]\.)</span>#u', '$1 ', $html);
    $html = preg_replace('#<h3>(.*?)</h3>#s', "\n\n## $1\n\n", $html);
    $html = preg_replace('#<li>\s*(?=(\d+\.|[가-하]\.|[①-⑳]))#u', "\n", $html);
    $html = preg_replace('#<li>#', "\n- ", $html);
    $html = str_replace('</li>', '', $html);
    $html = preg_replace('#</(ul|ol)>#', "\n", $html);
    $html = preg_replace('#<br\s*/?>#', "\n", $html);
    $html = preg_replace('#</(p|section|div|blockquote)>#', "\n\n", $html);
    $t = html_entity_decode(wp_strip_all_tags($html, false), ENT_QUOTES, 'UTF-8');
    $t = preg_replace("/[ \t]+/u", ' ', $t);
    $t = preg_replace("/ *\n */u", "\n", $t);
    return trim(preg_replace("/\n{3,}/u", "\n\n", $t));
}

/* ── 서식 → 글 보기 HTML ───────────────────── */
function hnf_img_html($id, $size = 'large', $cls = '') {
    $u = wp_get_attachment_image_url($id, $size);
    if (!$u) return '';
    $full = wp_get_attachment_image_url($id, 'full');
    $alt = get_post_meta($id, '_wp_attachment_image_alt', true) ?: get_the_title($id);
    return '<figure class="' . $cls . '"><a href="' . esc_url($full) . '" target="_blank"><img src="' . esc_url($u) . '"' . hnf_srcset($id, $size) . ' alt="' . esc_attr($alt) . '" loading="lazy"></a></figure>';
}
/* 인사이트 호수: hnf_f['issue'] → 「제1호」 (없으면 빈 문자열) */
function hnf_issue($pid) {
    $n = trim((string) ((get_post_meta($pid, 'hnf_f', true) ?: [])['issue'] ?? ''));
    $n = preg_replace('/^제\s*|\s*호$/u', '', $n);
    return $n === '' ? '' : '제' . $n . '호';
}
/* 인사이트 키워드: 「#가 #나」 또는 「가, 나」 → ['가','나'] */
function hnf_tags($str) {
    $t = preg_split('/[\s,]+/u', trim((string) $str), -1, PREG_SPLIT_NO_EMPTY);
    $t = array_map(fn($x) => ltrim($x, '#＃'), $t);
    return array_values(array_unique(array_filter($t, fn($x) => $x !== '')));
}
function hnf_tags_html($tags) {
    if (!$tags) return '';
    return '<p class="tp-tags">' . implode('', array_map(fn($t) => '<a href="' . esc_url(add_query_arg(['q' => $t, 'sf' => 'content'], HNF_H . '/pub-insight/')) . '">#' . esc_html($t) . '</a>', $tags)) . '</p>';
}
function hnf_tpl_html($slug, $f) {
    $g = fn($k) => trim((string) ($f[$k] ?? ''));
    $info = function ($pairs) use ($g) {
        $h = '';
        foreach ($pairs as $k => $lb) if ($g($k) !== '') {
            $v = $k === 'link' ? '<a href="' . esc_url($g($k)) . '" target="_blank" rel="noopener">' . esc_html($g($k)) . '</a>' : hnf_inline($g($k));
            $h .= '<dt>' . $lb . '</dt><dd>' . $v . '</dd>';
        }
        return $h ? '<dl class="tp-info">' . $h . '</dl>' : '';
    };
    $sum = fn() => $g('summary') !== '' ? '<div class="tp-sum"><b>요약</b>' . hnf_txt2html($g('summary')) . '</div>' : '';
    $photos = function () use ($f) {
        $ids = array_filter(array_map('intval', (array) ($f['photos'] ?? [])));
        if (!$ids) return '';
        return '<div class="tp-photos n' . min(count($ids), 3) . '">' . implode('', array_map(fn($i) => hnf_img_html($i, count($ids) === 1 ? 'full' : 'large'), $ids)) . '</div>';
    };
    $thumb = fn() => !empty($f['thumb']) ? hnf_img_html((int) $f['thumb'], 'large', 'tp-thumb') : '';
    switch ($slug) {
        case 'news-notice':
            return $info(['ev_date' => '일시', 'ev_place' => '장소', 'ev_target' => '대상', 'ev_apply' => '신청 방법']) . hnf_txt2html($g('body')) . $photos();
        case 'news-stories':
            return $thumb() . hnf_txt2html($g('body')) . $photos();
        case 'news-gallery':
            return $info(['ev_date' => '행사 일자', 'ev_place' => '장소']) . hnf_txt2html($g('body')) . $photos();
        case 'pub-insight':
            $refs = array_filter(array_map('trim', explode("\n", $g('refs'))));
            $top = $g('quote') !== '' ? '<div class="tp-sum tp-quote"><blockquote>' . hnf_txt2html($g('quote')) . '</blockquote></div>' : $sum();
            return ($g('author') !== '' ? '<p class="tp-author">글 · ' . esc_html($g('author')) . '</p>' : '') . $top . $thumb() . hnf_txt2html($g('body')) . $photos()
                . ($refs ? '<div class="tp-refs"><b>참고 문헌</b><ol>' . implode('', array_map(fn($r) => '<li>' . hnf_inline($r) . '</li>', $refs)) . '</ol></div>' : '')
                . hnf_tags_html(hnf_tags($g('tags')));
        case 'pub-reports':
            return $info(['rep_no' => '보고서 번호', 'lead' => '연구책임자', 'team' => '참여 연구진']) . $sum() . $photos();
        case 'pub-rules':
            return hnf_rules2html($g('rules'), $g('enact')) . $photos();
        case 'pub-archive':
            return hnf_txt2html($g('summary')) . $photos();
        case 'research-results':
            return $info(['authors' => '저자', 'venue' => '게재지·발표처', 'pub_date' => '발행·발표', 'link' => '원문 링크']) . $sum() . $thumb() . $photos();
    }
    return hnf_txt2html($g('body')) . $photos();
}

/* ── 관리 화면: 글쓰기 ─────────────────────── */
add_action('admin_menu', function () {
    add_submenu_page('hnf-home', '글쓰기', '✎ 글쓰기', 'edit_posts', 'hnf-write', 'hnf_write_page');
}, 1000);

/* WordPress 기본 글 편집 화면 대신 서식 글쓰기로 (관리자는 &classic=1로 기존 화면) */
add_action('load-post-new.php', function () {
    if (($_GET['post_type'] ?? 'post') !== 'post' || isset($_GET['classic'])) return;
    wp_safe_redirect(admin_url('admin.php?page=hnf-write' . (!empty($_GET['hnf_board']) ? '&board=' . sanitize_title($_GET['hnf_board']) : '')));
    exit;
});
add_action('load-post.php', function () {
    $id = (int) ($_GET['post'] ?? 0);
    if (!$id || get_post_type($id) !== 'post' || isset($_GET['classic']) || ($_GET['action'] ?? '') !== 'edit') return;
    wp_safe_redirect(admin_url('admin.php?page=hnf-write&id=' . $id));
    exit;
});
add_filter('get_edit_post_link', function ($link, $id) {
    if (get_post_type($id) === 'post' && strpos($link, 'classic=1') === false) return admin_url('admin.php?page=hnf-write&id=' . $id);
    return $link;
}, 10, 2);

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'hnf-write') === false) return;
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('hnf-write', HNF_T . '/js/hnf-write.js', ['jquery', 'jquery-ui-sortable', 'media-views'], HNF_VER, true);
});

function hnf_write_page() {
    $id = (int) ($_GET['id'] ?? 0);
    $p = $id ? get_post($id) : null;
    if ($p && !current_user_can('edit_post', $id)) wp_die('이 글을 고칠 권한이 없습니다.');
    $slug = $p ? hnf_post_board($id) : sanitize_title($_GET['board'] ?? '');
    $boards = hnf_boards();
    echo '<div class="wrap hnf-write">';
    if (!isset($boards[$slug])) {
        echo '<h1 class="hnf-h1">글쓰기 — 게시판 고르기</h1><div class="hnf-pick">';
        foreach ($boards as $s => $c) {
            $t = get_category_by_slug($s);
            if ($t) echo '<a class="hnf-pick-b" href="' . esc_url(admin_url('admin.php?page=hnf-write&board=' . $s)) . '"><strong>' . esc_html($t->name) . '</strong><span>' . esc_html(implode(' · ', wp_list_pluck(hnf_kids($s), 'name'))) . '</span></a>';
        }
        echo '</div></div>';
        return;
    }
    $top = get_category_by_slug($slug);
    $f = $p ? (get_post_meta($id, 'hnf_f', true) ?: []) : [];
    if ($p && !$f) { // 서식 없이 쓴 예전 글 → 본문을 글자로 옮겨 채움
        $c = $p->post_content;
        if (preg_match('#<p class="rg-meta">(.*?)</p>#s', $c, $mm)) { $f['enact'] = wp_strip_all_tags($mm[1]); $c = str_replace($mm[0], '', $c); }
        $txt = hnf_html2txt($c);
        foreach (hnf_fields($slug) as $fd) if (in_array($fd[2], ['text', 'rules'])) { $f[$fd[0]] = $txt; break; }
        if (has_post_thumbnail($id)) { foreach (hnf_fields($slug) as $fd) { if ($fd[0] === 'thumb') $f['thumb'] = get_post_thumbnail_id($id); if ($fd[0] === 'photos') $f['photos'] = [get_post_thumbnail_id($id)]; } }
    }
    $files = $p ? wp_list_pluck(hnf_files($id, true), 'id') : [];
    $kid = $p ? hnf_post_kid($id) : null;
    $kids = hnf_kids($slug);
    $writer = $p ? (get_post_meta($id, 'hnf_writer', true) ?: get_the_author_meta('display_name', $p->post_author)) : wp_get_current_user()->display_name;
    $date = $p ? get_the_date('Y-m-d\TH:i', $p) : current_time('Y-m-d\TH:i');
    $msg = $_GET['hnf_msg'] ?? '';

    echo '<h1 class="hnf-h1">' . ($p ? '글 고치기' : '글쓰기') . ' <span class="hnf-board-name">' . esc_html($top->name) . '</span></h1>';
    if ($msg === 'saved' && $p) echo '<div class="notice notice-success"><p><b>' . ($p->post_status === 'publish' ? '게시했습니다.' : '임시 저장했습니다.') . '</b> <a href="' . esc_url(get_permalink($p)) . '" target="_blank">홈페이지에서 보기 ↗</a> · <a href="' . esc_url(admin_url('admin.php?page=hnf-write&board=' . $slug)) . '">이 게시판에 새 글 쓰기</a> · <a href="' . esc_url(HNF_H . '/' . $slug . '/') . '" target="_blank">게시판 목록 ↗</a></p></div>';
    if ($msg === 'err') echo '<div class="notice notice-error"><p>' . esc_html(get_transient('hnf_err_' . get_current_user_id()) ?: '저장하지 못했습니다.') . '</p></div>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="hnf-form" id="hnfForm">';
    wp_nonce_field('hnf_write');
    echo '<input type="hidden" name="action" value="hnf_write_save"><input type="hidden" name="board" value="' . esc_attr($slug) . '"><input type="hidden" name="id" value="' . $id . '">';
    echo '<div class="hnf-cols"><div class="hnf-main">';
    echo '<div class="hnf-f req"><label for="hnfTitle">제목</label><input type="text" id="hnfTitle" name="title" required value="' . esc_attr($p ? $p->post_title : '') . '" placeholder="제목을 입력하세요"></div>';
    foreach (hnf_fields($slug) as [$k, $lb, $type, $req, $help]) {
        $v = $f[$k] ?? '';
        $name = 'f[' . $k . ']';
        echo '<div class="hnf-f t-' . $type . ($req ? ' req' : '') . '" data-key="' . $k . '"><label>' . esc_html($lb) . '</label>';
        switch ($type) {
            case 'line': echo '<input type="text" name="' . $name . '" value="' . esc_attr($v) . '"' . ($req ? ' required' : '') . '>'; break;
            case 'url': echo '<input type="url" name="' . $name . '" value="' . esc_attr($v) . '" placeholder="https://">'; break;
            case 'area': echo '<textarea name="' . $name . '" rows="4"' . ($req ? ' required' : '') . '>' . esc_textarea($v) . '</textarea>'; break;
            case 'text':
            case 'rules':
                $imgs = [];
                if ($type === 'text' && preg_match_all('/\\[\\s*사진\\s*(\\d+)/u', (string) $v, $mm)) foreach ($mm[1] as $i) $imgs[$i] = wp_get_attachment_image_url((int) $i, 'medium_large');
                if ($type === 'text') echo '<div class="hnf-tx-tools"><button type="button" class="button hnf-ins-img">🖼 본문에 사진 넣기</button><span>커서가 있는 자리에 <code>[사진 번호 | 설명]</code> 한 줄이 들어갑니다. 「설명」 자리에 사진 설명·출처를 쓰세요(지워도 됨).</span></div>';
                echo '<div class="hnf-tx"><textarea name="' . $name . '" rows="' . ($type === 'rules' ? 22 : 16) . '" data-mode="' . $type . '" data-imgs="' . esc_attr(wp_json_encode((object) $imgs)) . '"' . ($req ? ' required' : '') . '>' . esc_textarea($v) . '</textarea><div class="hnf-pv"><span class="hnf-pv-h">미리 보기</span><div class="hnf-pv-body wp-content"></div></div></div>';
                break;
            case 'image':
                $u = $v ? wp_get_attachment_image_url((int) $v, 'medium') : '';
                echo '<div class="hnf-media one" data-type="image"><input type="hidden" name="' . $name . '" value="' . esc_attr($v) . '"><div class="hnf-thumbs">' . ($u ? '<span class="it" data-id="' . (int) $v . '"><img src="' . esc_url($u) . '"><button type="button" class="x" title="빼기">✕</button></span>' : '') . '</div><button type="button" class="button pick">사진 고르기·올리기</button></div>';
                break;
            case 'images':
                $ids = array_filter(array_map('intval', (array) $v));
                echo '<div class="hnf-media many" data-type="image"><div class="hnf-thumbs sortable">';
                foreach ($ids as $i) echo '<span class="it" data-id="' . $i . '"><img src="' . esc_url(wp_get_attachment_image_url($i, 'thumbnail')) . '"><input type="hidden" name="' . $name . '[]" value="' . $i . '"><button type="button" class="x" title="빼기">✕</button></span>';
                echo '</div><button type="button" class="button pick">사진 더하기</button></div>';
                break;
            case 'files':
                echo '<div class="hnf-media files" data-type=""><ul class="hnf-flist sortable">';
                foreach ($files as $i) echo '<li class="it" data-id="' . $i . '"><span class="nm">' . esc_html(basename(get_attached_file($i))) . '</span><input type="hidden" name="files[]" value="' . $i . '"><button type="button" class="x" title="빼기">✕</button></li>';
                echo '</ul><button type="button" class="button pick">파일 올리기·고르기</button></div>';
                break;
        }
        if ($help) echo '<p class="hnf-help-t">' . esc_html($help) . '</p>';
        echo '</div>';
    }
    echo '</div><aside class="hnf-side">';
    echo '<div class="hnf-box"><h3>게시</h3>';
    echo '<p class="hnf-st">상태: <b>' . ($p ? ['publish' => '게시됨', 'draft' => '임시 저장', 'future' => '예약됨', 'pending' => '검토 대기'][$p->post_status] ?? $p->post_status : '새 글') . '</b></p>';
    echo '<button type="submit" name="do" value="publish" class="button button-primary button-hero">' . ($p && $p->post_status === 'publish' ? '고친 내용 게시' : '게시하기') . '</button>';
    echo '<button type="submit" name="do" value="draft" class="button hnf-draft" formnovalidate>임시 저장 (홈페이지에 안 보임)</button>';
    if ($p) {
        echo '<p class="hnf-links"><a href="' . esc_url(get_permalink($p)) . '" target="_blank">홈페이지에서 보기 ↗</a></p>';
        if (current_user_can('delete_post', $id)) echo '<p class="hnf-links"><a class="hnf-del" href="' . esc_url(get_delete_post_link($id)) . '" onclick="return confirm(\'이 글을 휴지통으로 옮길까요? (30일 안에 되살릴 수 있습니다)\')">휴지통으로 옮기기</a></p>';
        if (current_user_can('manage_options')) echo '<p class="hnf-links"><a href="' . esc_url(admin_url('post.php?post=' . $id . '&action=edit&classic=1')) . '">기존 편집기로 열기 (관리자)</a></p>';
    }
    echo '</div>';
    if ($kids) {
        echo '<div class="hnf-box"><h3>분류</h3>';
        foreach ($kids as $i => $k) {
            $chk = $kid ? $kid->term_id === $k->term_id : $i === 0;
            echo '<label class="hnf-radio"><input type="radio" name="kid" value="' . $k->term_id . '"' . checked($chk, true, false) . '> ' . esc_html($k->name) . '</label>';
        }
        echo '</div>';
    }
    echo '<div class="hnf-box"><h3>작성</h3><select name="writer">';
    $opts = array_unique(array_merge(HNF_DEPTS, [$writer]));
    foreach ($opts as $o) echo '<option' . selected($writer, $o, false) . '>' . esc_html($o) . '</option>';
    echo '</select><p class="hnf-help-t">목록 「작성자」 칸에 나옵니다</p></div>';
    echo '<div class="hnf-box"><h3>게시일</h3><input type="datetime-local" name="date" value="' . esc_attr($date) . '"><p class="hnf-help-t">미래 날짜로 하면 그때 자동 게시됩니다</p></div>';
    if (!empty($boards[$slug]['pin'])) {
        $sticky = $p && is_sticky($id);
        echo '<div class="hnf-box"><label class="hnf-radio"><input type="checkbox" name="pin" value="1"' . checked($sticky, true, false) . '> <b>공지로 맨 위 고정</b></label></div>';
    }
    echo '</aside></div></form></div>';
}

/* 저장 */
add_action('admin_post_hnf_write_save', function () {
    check_admin_referer('hnf_write');
    $slug = sanitize_title($_POST['board'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if (!isset(hnf_boards()[$slug]) || !current_user_can($id ? 'edit_post' : 'edit_posts', $id ?: null)) wp_die('권한이 없습니다.');
    $raw = wp_unslash($_POST['f'] ?? []);
    $f = [];
    $err = '';
    foreach (hnf_fields($slug) as [$k, $lb, $type, $req]) {
        if ($type === 'files') continue;
        $v = $raw[$k] ?? '';
        if ($type === 'images') $v = array_values(array_filter(array_map('intval', (array) $v)));
        elseif ($type === 'image') $v = (int) $v ?: '';
        elseif ($type === 'url') $v = esc_url_raw(trim($v));
        elseif (in_array($type, ['text', 'rules', 'area'])) $v = sanitize_textarea_field($v);
        else $v = sanitize_text_field($v);
        $f[$k] = $v;
    }
    $files = array_values(array_filter(array_map('intval', (array) ($_POST['files'] ?? []))));
    $publish = ($_POST['do'] ?? '') === 'publish';
    if ($publish) {
        foreach (hnf_fields($slug) as [$k, $lb, $type, $req]) {
            if (!$req) continue;
            $empty = $type === 'files' ? !$files : (is_array($f[$k] ?? '') ? !$f[$k] : trim((string) ($f[$k] ?? '')) === '');
            if ($empty) { $err = '「' . $lb . '」 칸을 채워 주세요. (임시 저장은 비워도 됩니다)'; break; }
        }
    }
    $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
    if ($title === '') $err = '제목을 입력해 주세요.';
    $kid = (int) ($_POST['kid'] ?? 0);
    $top = get_category_by_slug($slug);
    $cat = ($kid && get_category($kid) && get_category($kid)->parent === $top->term_id) ? $kid : $top->term_id;
    $date = sanitize_text_field($_POST['date'] ?? '');
    $ts = $date ? strtotime($date) : false;
    $post_date = $ts ? date('Y-m-d H:i:s', $ts) : current_time('mysql');
    $status = $publish ? (strtotime($post_date) > current_time('timestamp') + 60 ? 'future' : 'publish') : 'draft';
    if ($err && !$id && $title === '') { set_transient('hnf_err_' . get_current_user_id(), $err, 60); wp_safe_redirect(admin_url('admin.php?page=hnf-write&board=' . $slug . '&hnf_msg=err')); exit; }
    if ($err) $status = $id ? get_post_status($id) : 'draft';
    if ($err && $status === 'publish') $status = 'publish'; // 이미 게시된 글은 그대로 두고 알림만
    $arr = ['post_type' => 'post', 'post_title' => $title, 'post_status' => $status, 'post_category' => [$cat],
        'post_date' => $post_date, 'post_date_gmt' => get_gmt_from_date($post_date), 'edit_date' => true,
        'post_content' => hnf_tpl_html($slug, $f), 'comment_status' => 'closed', 'ping_status' => 'closed'];
    if (!empty($f['summary'])) $arr['post_excerpt'] = $f['summary'];
    if ($id) { $arr['ID'] = $id; $id = wp_update_post(wp_slash($arr), true); }
    else $id = wp_insert_post(wp_slash($arr), true);
    if (is_wp_error($id)) wp_die($id->get_error_message());
    update_post_meta($id, 'hnf_f', $f);
    update_post_meta($id, 'hnf_writer', sanitize_text_field(wp_unslash($_POST['writer'] ?? '')));
    delete_post_meta($id, 'hnf_sample');
    // 첨부: 이 글에 붙이고 순서 기억
    update_post_meta($id, 'hnf_files', $files);
    // 다른 글에 이미 붙은 파일은 그 글에서 빼지 않음 (이 글은 hnf_files 목록으로 보여 줌)
    foreach ($files as $a) if (get_post_type($a) === 'attachment' && !wp_get_post_parent_id($a)) wp_update_post(['ID' => $a, 'post_parent' => $id]);
    // 대표 사진
    $thumb = (int) ($f['thumb'] ?? 0) ?: (int) (($f['photos'] ?? [])[0] ?? 0);
    if ($thumb) set_post_thumbnail($id, $thumb); elseif (array_key_exists('thumb', $f) || array_key_exists('photos', $f)) delete_post_thumbnail($id);
    if (!empty(hnf_boards()[$slug]['pin'])) { if (!empty($_POST['pin'])) stick_post($id); else unstick_post($id); }
    if ($err) { set_transient('hnf_err_' . get_current_user_id(), $err . ' — 지금 내용은 저장해 두었습니다.', 60); wp_safe_redirect(admin_url('admin.php?page=hnf-write&id=' . $id . '&hnf_msg=err')); exit; }
    wp_safe_redirect(admin_url('admin.php?page=hnf-write&id=' . $id . '&hnf_msg=saved'));
    exit;
});

/* 글 목록에서 「휴지통으로」 뒤 돌아갈 곳 */
add_action('trashed_post', function ($id) {
    if (get_post_type($id) === 'post' && !wp_doing_ajax() && isset($_GET['action']) && $_GET['action'] === 'trash' && empty($_GET['hnf_list'])) {
        add_filter('wp_redirect', fn() => admin_url('admin.php?page=hnf-home&hnf_trashed=1'));
    }
});
