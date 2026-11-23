<?php
/* 영문 사이트(/en/) 도움 함수 (2026-11-22) */

/* Members' Research: 「구성원 연구 성과」 게시판 글을 영문 목록으로.
   글쓰기의 영문 제목·영문 저자·영문 게재지 칸을 쓰고, 비어 있으면 국문을 그대로 보인다 */
function hnf_en_results() {
    $types = ['research-results-1' => ['article', 'Article'], 'research-results-2' => ['report', 'Report'], 'research-results-3' => ['presentation', 'Presentation']];
    $posts = get_posts(['post_type' => 'post', 'numberposts' => -1, 'category_name' => 'research-results', 'post_status' => 'publish']);
    echo '<div class="filter" data-target="#enRes"><button type="button" class="on" data-f="all">All</button><button type="button" data-f="article">Articles</button><button type="button" data-f="report">Reports</button><button type="button" data-f="presentation">Presentations</button></div>';
    echo '<ul class="en-res" id="enRes">';
    foreach ($posts as $p) {
        $f = get_post_meta($p->ID, 'hnf_f', true);
        $f = is_array($f) ? $f : [];
        $g = fn($k) => trim((string) ($f[$k] ?? ''));
        $t = ['other', 'Publication'];
        foreach (wp_get_post_categories($p->ID, ['fields' => 'slugs']) as $c) if (isset($types[$c])) $t = $types[$c];
        $title = $g('title_en') !== '' ? $g('title_en') : get_the_title($p);
        $year = preg_match('/(\d{4})/', $g('pub_date'), $m) ? $m[1] : get_the_date('Y', $p);
        $badge = ['article' => '', 'report' => ' gold', 'presentation' => ' green', 'other' => ' gray'][$t[0]];
        echo '<li data-cat="' . $t[0] . '"><p class="yr"><b>' . esc_html($year) . '</b></p><div>';
        echo '<span class="badge' . $badge . '">' . $t[1] . '</span>';
        echo '<h5>' . esc_html($title) . '</h5>';
        if ($g('title_en') !== '') echo '<p class="ko" lang="ko">' . esc_html(get_the_title($p)) . '</p>';
        $by = $g('authors_en') !== '' ? $g('authors_en') : $g('authors');
        $at = $g('venue_en') !== '' ? $g('venue_en') : $g('venue');
        echo '<p class="by">' . esc_html($by) . ($at !== '' ? ' · <i>' . esc_html($at) . '</i>' : '') . '</p>';
        if ($g('link') !== '') echo '<p class="lk"><a href="' . esc_url($g('link')) . '" target="_blank" rel="noopener">' . esc_html(preg_replace('#^https?://(dx\.)?#', '', $g('link'))) . '</a></p>';
        echo '</div></li>';
    }
    echo '</ul><style>.en-res .hide{display:none}</style>';
}

/* ───────── English Insight (/en/insight/, /en/insight/<글 번호>/) (2026-11-23)
   국문 인사이트 글(pub-insight)에 영문 칸(title_en·author_en·summary_en·quote_en·thumb_en·body_en·refs_en·tags_en)을 채우면
   영문 사이트에 나온다. 영문 본문(body_en)이 비어 있는 글은 영문 목록에 나오지 않는다. */
add_action('init', function () {
    add_rewrite_rule('^en/insight/([0-9]+)/?$', 'index.php?pagename=en/insight&hnf_no=$matches[1]', 'top');
});
add_filter('query_vars', function ($v) { $v[] = 'hnf_no'; return $v; });

function hnf_en_insight_url($id = 0) { return HNF_H . '/en/insight/' . ($id ? (int) $id . '/' : ''); }

/* 영문 칸을 국문 서식(hnf_tpl_html)의 칸 이름으로 옮김 */
function hnf_en_f($id) {
    $f = get_post_meta($id, 'hnf_f', true);
    $f = is_array($f) ? $f : [];
    $g = fn($k) => trim((string) ($f[$k] ?? ''));
    return ['issue' => $g('issue'), 'title' => $g('title_en'), 'author' => $g('author_en'), 'summary' => $g('summary_en'),
        'quote' => $g('quote_en'), 'thumb' => ($f['thumb_en'] ?? '') ?: ($f['thumb'] ?? ''), 'body' => $g('body_en'),
        'photos' => [], 'refs' => $g('refs_en'), 'tags' => hnf_tags($g('tags_en'))];
}

function hnf_en_insight_posts() {
    $top = get_category_by_slug('pub-insight');
    if (!$top) return [];
    $ps = get_posts(['cat' => $top->term_id, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC',
        'meta_query' => [['key' => 'hnf_sample', 'compare' => 'NOT EXISTS']]]);
    return array_values(array_filter($ps, fn($p) => hnf_en_f($p->ID)['body'] !== '' && hnf_en_f($p->ID)['title'] !== ''));
}

function hnf_en_writer($p) {
    $m = ['연구개발부' => 'Research &amp; Development Division', '기획운영부' => 'Planning &amp; Operations Division', '미래혁신부' => 'Future Innovation Division'];
    $w = hnf_writer($p);
    return $m[$w] ?? 'Institute for Human &amp; Future';
}

function hnf_en_insight() {
    $no = (int) get_query_var('hnf_no');
    if (!$no && isset($_GET['no'])) $no = (int) $_GET['no'];
    $posts = hnf_en_insight_posts();
    if (!$no) {
        if (!$posts) { echo '<p class="tbl-note">English editions will be available soon.</p>'; return; }
        echo '<div class="card-grid en-ins">';
        foreach ($posts as $p) {
            $e = hnf_en_f($p->ID);
            $img = $e['thumb'] ? wp_get_attachment_image_url((int) $e['thumb'], 'large') : '';
            echo '<a href="' . esc_url(hnf_en_insight_url($p->ID)) . '" class="p-card">';
            echo $img ? '<div class="thumb"><img src="' . esc_url($img) . '" alt="" loading="lazy"></div>' : '<div class="thumb noimg"><span>INSTITUTE FOR HUMAN &amp; FUTURE</span></div>';
            echo '<p class="meta"><span class="badge gray">Insight' . ($e['issue'] !== '' ? ' No. ' . esc_html($e['issue']) : '') . '</span></p>';
            echo '<strong>' . esc_html($e['title']) . '</strong>';
            if ($e['tags']) echo '<span class="p-tags">' . implode(' ', array_map(fn($t) => '#' . esc_html($t), array_slice($e['tags'], 0, 3))) . '</span>';
            echo '<time>' . esc_html(get_the_date('M j, Y', $p)) . '</time></a>';
        }
        echo '</div>';
        return;
    }
    $p = get_post($no);
    $ids = array_map(fn($x) => $x->ID, $posts);
    if (!$p || !in_array($no, $ids, true)) {
        echo '<p class="c-lead">This article is not available in English.</p><div class="bv-foot"><a href="' . esc_url(hnf_en_insight_url()) . '" class="bv-list">All Insights</a></div>';
        return;
    }
    $e = hnf_en_f($no);
    $tags = $e['tags'];
    $e['tags'] = '';
    $html = hnf_tpl_html('pub-insight', $e);
    $html = str_replace(['<p class="tp-author">글 · ', '<b>참고 문헌</b>', '<b>요약</b>'], ['<p class="tp-author">By ', '<b>References</b>', '<b>Summary</b>'], $html);
    echo '<script>document.title=' . wp_json_encode($e['title'] . ' | Insight | Institute for Human & Future') . ';</script>';
    echo '<article class="b-view en-view">';
    echo '<header class="bv-head"><span class="badge gray">Insight' . ($e['issue'] !== '' ? ' No. ' . esc_html($e['issue']) : '') . '</span>';
    echo '<h4 class="bv-title">' . esc_html($e['title']) . '</h4>';
    echo '<p class="bv-meta"><span>' . hnf_en_writer($p) . '</span><time>' . esc_html(get_the_date('F j, Y', $p)) . '</time><a class="bv-ko" href="' . esc_url(get_permalink($p)) . '" lang="ko">한국어 원문</a></p></header>';
    echo '<div class="bv-body wp-content">' . $html;
    if ($tags) echo '<p class="tp-tags">' . implode('', array_map(fn($t) => '<span>#' . esc_html($t) . '</span>', $tags)) . '</p>';
    echo '</div>';
    $i = array_search($no, $ids, true);
    $newer = $i > 0 ? $posts[$i - 1] : null;
    $older = $posts[$i + 1] ?? null;
    $t = fn($x) => esc_html(hnf_en_f($x->ID)['title']);
    echo '<ul class="bv-nav">';
    echo '<li><b>Previous</b>' . ($older ? '<a href="' . esc_url(hnf_en_insight_url($older->ID)) . '">' . $t($older) . '</a>' : '<span>This is the first issue.</span>') . '</li>';
    echo '<li><b>Next</b>' . ($newer ? '<a href="' . esc_url(hnf_en_insight_url($newer->ID)) . '">' . $t($newer) . '</a>' : '<span>This is the latest issue.</span>') . '</li>';
    echo '</ul>';
    echo '<div class="bv-foot"><a href="' . esc_url(hnf_en_insight_url()) . '" class="bv-list">All Insights</a></div>';
    echo '</article>';
}
