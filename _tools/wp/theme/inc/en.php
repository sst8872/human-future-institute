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
