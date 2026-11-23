<?php
/**
 * 게시판 8곳. 분류(category) 이름 = 게시판 주소(slug), 하위 분류 = 「게시판-번호」.
 * type: table(표) · card(카드) · gallery(사진)
 */
function hnf_boards() {
    static $b = null;
    if ($b) return $b;
    $b = [
        'news-notice' => ['type' => 'table', 'per' => 10, 'pin' => true, 'catbadge' => false, 'style' => 'notice',
            'cols' => ['no' => ['번호', 100], 'cat' => ['분류', 100], 'title' => ['제목', 0], 'date' => ['등록일', 130], 'writer' => ['작성자', 130], 'views' => ['조회수', 90]]],
        'pub-reports' => ['type' => 'table', 'per' => 10, 'catbadge' => true, 'style' => 'plain',
            'cols' => ['no' => ['번호', 90], 'cat' => ['분류', 110], 'title' => ['제목', 0], 'files' => ['파일', 110], 'date' => ['등록일', 130], 'views' => ['내려받기', 100]]],
        'pub-rules' => ['type' => 'table', 'per' => 10, 'catbadge' => true, 'style' => 'plain',
            'cols' => ['no' => ['번호', 90], 'cat' => ['분류', 110], 'title' => ['제목', 0], 'files' => ['파일', 110], 'date' => ['등록일', 130], 'writer' => ['작성', 140]]],
        'pub-archive' => ['type' => 'table', 'per' => 10, 'catbadge' => true, 'style' => 'archive',
            'cols' => ['no' => ['번호', 90], 'cat' => ['분류', 110], 'title' => ['제목', 0], 'files' => ['파일', 240], 'date' => ['등록일', 130], 'writer' => ['작성', 140]]],
        'news-stories' => ['type' => 'card', 'per' => 12],
        'pub-insight' => ['type' => 'card', 'per' => 12, 'extra' => '<span class="badge gray">Insight</span>'],
        'research-results' => ['type' => 'card', 'per' => 12, 'badge' => ['보고서' => 'gold', '학술발표' => 'green']],
        'news-gallery' => ['type' => 'gallery', 'per' => 16],
    ];
    return $b;
}

const HNF_CLIP = '<svg class="ico-file" viewBox="0 0 24 24"><path d="M21 11l-8.5 8.5a5 5 0 0 1-7-7L14 4a3.5 3.5 0 0 1 5 5l-8.5 8.5a2 2 0 0 1-3-3L15 7"/></svg>';
const HNF_GLASS = '<svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/></svg>';

/* 글이 속한 게시판 slug */
function hnf_post_board($id) {
    foreach (get_the_category($id) as $c) {
        $top = $c->parent ? get_category($c->parent) : $c;
        if (isset(hnf_boards()[$top->slug])) return $top->slug;
    }
    return null;
}
/* 글의 하위 분류(일반·학술 행사 …) */
function hnf_post_kid($id) {
    foreach (get_the_category($id) as $c) if ($c->parent) return $c;
    return null;
}
function hnf_post_kid_name($id) { $k = hnf_post_kid($id); return $k ? $k->name : ''; }
function hnf_kids($slug) {
    $top = get_category_by_slug($slug);
    if (!$top) return [];
    return get_categories(['parent' => $top->term_id, 'hide_empty' => false, 'orderby' => 'slug']);
}
function hnf_kid_no($cat) { return preg_match('/-(\d+)$/', $cat->slug, $m) ? $m[1] : ''; }

/* 첨부 파일: 서식 글쓰기 순서(hnf_files) → 없으면 글에 올린 파일(그림 제외) */
function hnf_files($id, $all = false) {
    $ids = get_post_meta($id, 'hnf_files', true);
    if (!is_array($ids) || (!$ids && !metadata_exists('post', $id, 'hnf_f'))) {
        $ids = [];
        $att = get_children(['post_parent' => $id, 'post_type' => 'attachment', 'orderby' => 'menu_order ID', 'order' => 'ASC']);
        foreach ($att as $a) if (strpos($a->post_mime_type, 'image/') !== 0) $ids[] = $a->ID;
    }
    $out = [];
    foreach ($ids as $aid) {
        $url = wp_get_attachment_url($aid);
        if (!$url) continue;
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $out[] = ['id' => (int) $aid, 'url' => $url, 'ext' => $ext, 'name' => get_the_title($aid) . '.' . $ext];
    }
    return $out;
}
function hnf_file_badges($files, $color = false) {
    $h = '';
    foreach ($files as $f) {
        $cls = $color ? (in_array($f['ext'], ['xlsx', 'xls', 'csv']) ? 'green' : 'navy') : '';
        $h .= sprintf('<a href="%s" download="%s" class="badge %s">%s ↓</a>', esc_url($f['url']), esc_attr($f['name']), $cls, esc_html(strtoupper($f['ext'])));
    }
    return $h;
}

function hnf_board_url($slug, $args = []) {
    $url = HNF_H . '/' . $slug . '/';
    $args = array_filter($args, fn($v) => $v !== '' && $v !== null && $v !== 1);
    return $args ? add_query_arg(array_map('rawurlencode', $args), $url) : $url;
}

/* 게시판 자리 */
function hnf_board_area() {
    $slug = $GLOBALS['hnf_board'] ?? '';
    $cfg = hnf_boards()[$slug] ?? null;
    if (!$cfg) return;
    if (!empty($GLOBALS['hnf_single'])) { hnf_single_view($slug, $GLOBALS['hnf_single']); return; }
    hnf_board_list($slug, $cfg);
}

function hnf_board_list($slug, $cfg) {
    $pg = max(1, (int) ($_GET['pg'] ?? 1));
    $bc = preg_replace('/\D/', '', $_GET['bc'] ?? '');
    $q = trim(sanitize_text_field(wp_unslash($_GET['q'] ?? '')));
    $sf = in_array($_GET['sf'] ?? '', ['title', 'content']) ? $_GET['sf'] : 'all';
    $top = get_category_by_slug($slug);
    $cat = $top ? $top->term_id : -1;
    if ($bc !== '' && ($k = get_category_by_slug($slug . '-' . $bc))) $cat = $k->term_id;

    $args = ['post_type' => 'post', 'post_status' => 'publish', 'cat' => $cat, 'posts_per_page' => $cfg['per'],
        'paged' => $pg, 'ignore_sticky_posts' => 1, 'orderby' => 'date', 'order' => 'DESC'];
    $where = null;
    if ($q !== '') {
        if ($sf === 'all') $args['s'] = $q;
        else {
            global $wpdb;
            $col = $sf === 'title' ? 'post_title' : 'post_content';
            $like = '%' . $wpdb->esc_like($q) . '%';
            $where = function ($w) use ($wpdb, $col, $like) { return $w . $wpdb->prepare(" AND {$wpdb->posts}.$col LIKE %s", $like); };
            add_filter('posts_where', $where);
        }
    }
    $pins = [];
    if (!empty($cfg['pin']) && $top) {
        $sticky = get_option('sticky_posts') ?: [];
        if ($sticky) {
            $pins = get_posts(['post__in' => $sticky, 'cat' => $top->term_id, 'posts_per_page' => -1, 'ignore_sticky_posts' => 1]);
            if ($bc === '' && $q === '') $args['post__not_in'] = wp_list_pluck($pins, 'ID');
            else $pins = [];
            if ($pg > 1) $pins = [];
        }
    }
    $query = new WP_Query($args);
    if ($where) remove_filter('posts_where', $where);
    $total = (int) $query->found_posts;
    $state = ['bc' => $bc, 'q' => $q, 'sf' => $sf === 'all' ? '' : $sf];

    $wrap = $cfg['type'] === 'table' ? 'board-wrap wp' : 'board-wrap wp board-' . $cfg['type'];
    echo '<div class="' . $wrap . '">';
    echo hnf_board_tools($slug);
    if ($cfg['type'] !== 'gallery') hnf_search_form($slug, $state, $sf);
    hnf_filter($slug, $bc, $state);
    if ($cfg['type'] === 'table') {
        echo '<div class="board-top"><p class="board-count">전체 <b>' . number_format($total) . '</b>건</p></div>';
        hnf_table($slug, $cfg, $query->posts, $pins, $total, $pg);
    } elseif ($cfg['type'] === 'card') {
        hnf_cards($cfg, $query->posts);
    } else {
        hnf_gallery($query->posts);
    }
    if (!$query->posts && !$pins) echo '<p class="board-empty">' . ($q !== '' ? '검색 결과가 없습니다.' : '등록된 글이 없습니다.') . '</p>';
    hnf_paging($slug, $state, $pg, max(1, (int) $query->max_num_pages));
    echo '</div>';
}

function hnf_search_form($slug, $state, $sf) {
    $o = ['all' => '전체', 'title' => '제목', 'content' => '내용'];
    echo '<form class="b-search" method="get" action="' . esc_url(HNF_H . '/' . $slug . '/') . '"><select name="sf" aria-label="검색 범위">';
    foreach ($o as $k => $v) echo '<option value="' . $k . '"' . selected($sf, $k, false) . '>' . $v . '</option>';
    echo '</select><input type="search" name="q" value="' . esc_attr($state['q']) . '" placeholder="검색어를 입력해 주세요" aria-label="검색어">';
    if ($state['bc'] !== '') echo '<input type="hidden" name="bc" value="' . esc_attr($state['bc']) . '">';
    echo '<button type="submit" aria-label="검색">' . HNF_GLASS . '</button></form>';
}

function hnf_filter($slug, $bc, $state) {
    $kids = hnf_kids($slug);
    if (!$kids) return;
    echo '<div class="filter b-cats">';
    echo '<a href="' . esc_url(hnf_board_url($slug, ['q' => $state['q'], 'sf' => $state['sf']])) . '"' . ($bc === '' ? ' class="on"' : '') . '>전체</a>';
    foreach ($kids as $k) {
        $no = hnf_kid_no($k);
        echo '<a href="' . esc_url(hnf_board_url($slug, ['bc' => $no, 'q' => $state['q'], 'sf' => $state['sf']])) . '"' . ($bc === $no ? ' class="on"' : '') . '>' . esc_html($k->name) . '</a>';
    }
    echo '</div>';
}

/* 연구 성과는 발행 연월만(날짜 없이) — 2026-11-21 사용자 지시 */
function hnf_date($p) {
    if (hnf_post_board($p->ID) !== 'research-results') return get_the_date('Y.m.d', $p);
    $f = get_post_meta($p->ID, 'hnf_f', true);   // 발행 연월에 달이 없으면(예: 2024.) 연도만
    if (is_array($f) && preg_match('/^\s*\d{4}\s*\.?\s*$/', (string) ($f['pub_date'] ?? ''))) return get_the_date('Y', $p);
    return get_the_date('Y.m', $p);
}
function hnf_is_new($p) { return (time() - get_post_time('U', true, $p)) < 7 * DAY_IN_SECONDS; }

function hnf_table($slug, $cfg, $posts, $pins, $total, $pg) {
    echo '<div class="tbl-scroll"><table class="tbl board"><colgroup>';
    foreach ($cfg['cols'] as $c) echo $c[1] ? '<col style="width:' . $c[1] . 'px">' : '<col>';
    echo '</colgroup><thead><tr>';
    foreach ($cfg['cols'] as $k => $c) echo '<th' . ($k === 'title' ? ' class="l"' : '') . '>' . $c[0] . '</th>';
    echo '</tr></thead><tbody>';
    $n = $total - ($pg - 1) * $cfg['per'];
    foreach ($pins as $p) hnf_row($cfg, $p, null);
    foreach ($posts as $p) hnf_row($cfg, $p, $n--);
    echo '</tbody></table></div>';
}

function hnf_row($cfg, $p, $no) {
    $files = hnf_files($p->ID);
    $kid = hnf_post_kid_name($p->ID);
    $title = esc_html(wp_strip_all_tags($p->post_title));
    $url = esc_url(get_permalink($p));
    echo '<tr' . ($no === null ? ' class="pin"' : '') . '>';
    foreach ($cfg['cols'] as $k => $c) {
        switch ($k) {
            case 'no': echo '<td>' . ($no === null ? '<span class="badge navy">공지</span>' : $no) . '</td>'; break;
            case 'cat': echo '<td>' . ($cfg['catbadge'] ? '<span class="badge">' . esc_html($kid) . '</span>' : esc_html($kid)) . '</td>'; break;
            case 'title':
                if ($cfg['style'] === 'notice') {
                    echo '<td class="title"><a href="' . $url . '">' . $title . ($files ? HNF_CLIP : '') . (hnf_is_new($p) ? ' <span class="new">N</span>' : '') . '</a></td>';
                } elseif ($cfg['style'] === 'archive') {
                    echo '<td class="title"><a href="' . $url . '">' . ($files ? HNF_CLIP : '') . $title . '</a>' . ($files ? '<span class="m-files">' . hnf_file_badges($files) . '</span>' : '') . '</td>';
                } else {
                    echo '<td class="title"><a href="' . $url . '">' . $title . '</a></td>';
                }
                break;
            case 'files':
                if ($cfg['style'] === 'archive') echo '<td class="files">' . ($files ? hnf_file_badges($files) : '-') . '</td>';
                else echo '<td>' . ($files ? hnf_file_badges(array_slice($files, 0, 1), true) : '-') . '</td>';
                break;
            case 'date': echo '<td>' . get_the_date('Y.m.d', $p) . '</td>'; break;
            case 'writer': echo '<td>' . esc_html(hnf_writer($p)) . '</td>'; break;
            case 'views': echo '<td>' . number_format(hnf_views($p->ID)) . '</td>'; break;
        }
    }
    echo '</tr>';
}

function hnf_thumb($p, $size = 'medium_large') {
    $u = get_the_post_thumbnail_url($p, $size);
    if (!$u && preg_match('/<img[^>]+src="([^"]+)"/', $p->post_content, $m)) $u = $m[1];
    return $u;
}

function hnf_cards($cfg, $posts) {
    if (!$posts) return;
    echo '<div class="card-grid">';
    foreach ($posts as $p) {
        $kid = hnf_post_kid_name($p->ID);
        $cls = $cfg['badge'][$kid] ?? '';
        $img = hnf_thumb($p);
        echo '<a href="' . esc_url(get_permalink($p)) . '" class="p-card">';
        echo $img ? '<div class="thumb"><img src="' . esc_url($img) . '" alt="" loading="lazy"></div>' : '<div class="thumb noimg"><span>INSTITUTE FOR HUMANITY &amp; FUTURE</span></div>';
        echo '<p class="meta">' . ($kid ? '<span class="badge ' . $cls . '">' . esc_html($kid) . '</span>' : '') . (hnf_post_board($p->ID) === 'pub-insight' ? '<span class="badge gray">인사이트' . (($n = hnf_issue($p->ID)) ? ' ' . $n : '') . '</span>' : ($cfg['extra'] ?? '')) . '</p>';
        $tg = hnf_post_board($p->ID) === 'pub-insight' ? array_slice(hnf_tags((get_post_meta($p->ID, 'hnf_f', true) ?: [])['tags'] ?? ''), 0, 3) : [];
        echo '<strong>' . esc_html(wp_strip_all_tags($p->post_title)) . '</strong>' . ($tg ? '<span class="p-tags">' . implode(' ', array_map(fn($t) => '#' . esc_html($t), $tg)) . '</span>' : '') . '<time>' . hnf_date($p) . '</time></a>';
    }
    echo '</div>';
}

function hnf_gallery($posts) {
    if (!$posts) return;
    echo '<div class="gal wp">';
    foreach ($posts as $p) {
        $img = hnf_thumb($p);
        $t = esc_html(wp_strip_all_tags($p->post_title));
        echo '<a href="' . esc_url(get_permalink($p)) . '">' . ($img ? '<img src="' . esc_url($img) . '" alt="' . $t . '" loading="lazy">' : '<span class="gal-ph"><b>사진 준비 중</b></span>') . '<span class="cap">' . $t . '</span></a>';
    }
    echo '</div>';
}

function hnf_paging($slug, $state, $pg, $max) {
    $u = fn($n) => esc_url(hnf_board_url($slug, $state + ['pg' => $n]));
    $start = max(1, $pg - 2);
    $end = min($max, $start + 4);
    $start = max(1, $end - 4);
    echo '<div class="paging">';
    if ($max > 1) {
        echo '<a href="' . $u(1) . '" class="arr" aria-label="처음">«</a><a href="' . $u(max(1, $pg - 1)) . '" class="arr" aria-label="이전">‹</a>';
    }
    for ($i = $start; $i <= $end; $i++) echo '<a href="' . $u($i) . '"' . ($i === $pg ? ' class="on"' : '') . '>' . $i . '</a>';
    if ($max > 1) {
        echo '<a href="' . $u(min($max, $pg + 1)) . '" class="arr" aria-label="다음">›</a><a href="' . $u($max) . '" class="arr" aria-label="마지막">»</a>';
    }
    echo '</div>';
}

/* 글 보기 */
function hnf_single_view($slug, $p) {
    $cfg = hnf_boards()[$slug];
    $kid = hnf_post_kid($p->ID);
    $files = hnf_files($p->ID);
    $tf = get_post_meta($p->ID, 'hnf_f', true);
    $content = $tf ? hnf_tpl_html($slug, $tf) : apply_filters('the_content', $p->post_content);
    $img = get_the_post_thumbnail_url($p, 'large');
    echo '<article class="b-view">';
    echo '<header class="bv-head">' . ($kid ? '<span class="badge ' . esc_attr($cfg['badge'][$kid->name] ?? '') . '">' . esc_html($kid->name) . '</span>' : '');
    if ($slug === 'pub-insight') echo '<span class="badge gray">인사이트' . (($n = hnf_issue($p->ID)) ? ' ' . $n : '') . '</span>';
    echo '<h4 class="bv-title">' . esc_html(wp_strip_all_tags($p->post_title)) . '</h4>';
    echo '<p class="bv-meta"><span>작성 ' . esc_html(hnf_writer($p)) . '</span><time>' . hnf_date($p) . '</time>';
    if ($cfg['type'] === 'table') echo '<span class="bv-views">조회 ' . number_format(hnf_views($p->ID)) . '</span>';
    echo '</p></header>';
    if ($files) {
        echo '<div class="bv-files"><b>첨부 파일</b><ul>';
        foreach ($files as $f) echo '<li><a href="' . esc_url($f['url']) . '" download="' . esc_attr($f['name']) . '">' . HNF_CLIP . esc_html($f['name']) . '</a></li>';
        echo '</ul></div>';
    }
    echo '<div class="bv-body wp-content">';
    if (!$tf && $img && $cfg['type'] !== 'table' && strpos($p->post_content, '<img') === false) echo '<figure class="bv-thumb"><img src="' . esc_url($img) . '" alt=""></figure>';
    echo $content . '</div>';
    // 이전·다음 글 (같은 게시판)
    $top = get_category_by_slug($slug);
    $near = function ($dir) use ($p, $top) {
        $r = get_posts(['cat' => $top->term_id, 'posts_per_page' => 1, 'ignore_sticky_posts' => 1, 'post__not_in' => [$p->ID],
            'date_query' => [[$dir === 'prev' ? 'before' : 'after' => $p->post_date, 'inclusive' => false]],
            'orderby' => 'date', 'order' => $dir === 'prev' ? 'DESC' : 'ASC']);
        return $r[0] ?? null;
    };
    $nx = $near('next');
    $pv = $near('prev');
    echo '<ul class="bv-nav">';
    echo '<li><b>이전 글</b>' . ($nx ? '<a href="' . esc_url(get_permalink($nx)) . '">' . esc_html(wp_strip_all_tags($nx->post_title)) . '</a>' : '<span>이전 글이 없습니다.</span>') . '</li>';
    echo '<li><b>다음 글</b>' . ($pv ? '<a href="' . esc_url(get_permalink($pv)) . '">' . esc_html(wp_strip_all_tags($pv->post_title)) . '</a>' : '<span>다음 글이 없습니다.</span>') . '</li>';
    echo '</ul>';
    echo hnf_board_tools($slug, $p);
    echo '<div class="bv-foot"><a href="' . esc_url(HNF_H . '/' . $slug . '/') . '" class="bv-list">목록</a></div>';
    echo '</article>';
}

/* 사이트 검색 결과 */
function hnf_search_results() {
    global $wp_query;
    $q = get_search_query();
    echo '<div class="board-wrap wp">';
    echo '<form class="b-search" method="get" action="' . esc_url(HNF_H . '/') . '"><input type="search" name="s" value="' . esc_attr($q) . '" placeholder="검색어를 입력해 주세요" aria-label="검색어"><button type="submit" aria-label="검색">' . HNF_GLASS . '</button></form>';
    echo '<div class="board-top"><p class="board-count">「' . esc_html($q) . '」 검색 결과 <b>' . number_format((int) $wp_query->found_posts) . '</b>건</p></div>';
    echo '<div class="tbl-scroll"><table class="tbl board"><colgroup><col style="width:160px"><col><col style="width:130px"></colgroup><thead><tr><th>게시판</th><th class="l">제목</th><th>등록일</th></tr></thead><tbody>';
    while (have_posts()) {
        the_post();
        $p = get_post();
        $b = $p->post_type === 'post' ? hnf_post_board($p->ID) : null;
        $bn = $b ? (get_category_by_slug($b)->name ?? '') : '페이지';
        echo '<tr><td>' . esc_html($bn) . '</td><td class="title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(wp_strip_all_tags(get_the_title())) . '</a></td><td>' . get_the_date('Y.m.d') . '</td></tr>';
    }
    echo '</tbody></table></div>';
    if (!have_posts() && !$wp_query->found_posts) echo '<p class="board-empty">검색 결과가 없습니다.</p>';
    $links = paginate_links(['type' => 'array', 'prev_text' => '‹', 'next_text' => '›']);
    if ($links) echo '<div class="paging">' . str_replace('current', 'on', implode('', $links)) . '</div>';
    echo '</div>';
}
