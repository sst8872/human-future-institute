<?php
/**
 * 첫 화면 아래 소식 띠(3칸)
 * - 평소: 인사이트 최신 2편 + 공지사항 › 학술 행사 최신 1건(모자라면 공지사항 최신 글)
 * - 관리 화면 「연구소 관리 → 첫 화면 소식 띠」에서 칸마다 글을 고르면 그 칸만 고른 글로 바뀜
 *   (고른 글이 지워지거나 비공개가 되면 그 칸은 다시 자동)
 * 저장: option hnf_strip = [['id'=>0,'label'=>'','sub'=>''] × 3]
 */

const HNF_STRIP_BOARDS = ['pub-insight', 'news-notice', 'news-stories', 'research-results', 'pub-reports', 'news-gallery'];

function hnf_strip_opt() {
    $o = get_option('hnf_strip');
    $o = is_array($o) ? $o : [];
    $out = [];
    for ($i = 0; $i < 3; $i++) {
        $r = $o[$i] ?? [];
        $out[] = ['id' => (int) ($r['id'] ?? 0), 'label' => (string) ($r['label'] ?? ''), 'sub' => (string) ($r['sub'] ?? '')];
    }
    return $out;
}

function hnf_strip_ok($id) {
    $p = $id ? get_post($id) : null;
    return $p && $p->post_status === 'publish' && $p->post_type === 'post' && !get_post_meta($id, 'hnf_sample', true);
}

function hnf_strip_latest($slug, $n, $not = []) {
    $c = get_category_by_slug($slug);
    if (!$c) return [];
    return get_posts(['cat' => $c->term_id, 'numberposts' => $n, 'fields' => 'ids', 'post__not_in' => $not,
        'ignore_sticky_posts' => 1, 'meta_query' => [['key' => 'hnf_sample', 'compare' => 'NOT EXISTS']]]);
}

/* 자동으로 고를 글 차례(앞에서부터 빈 칸을 채움) */
function hnf_strip_auto($not) {
    $ids = hnf_strip_latest('pub-insight', 2, $not);
    $ids = array_merge($ids, hnf_strip_latest('news-notice-2', 1, array_merge($not, $ids)));
    $ids = array_merge($ids, hnf_strip_latest('news-notice', 3, array_merge($not, $ids)));
    $ids = array_merge($ids, hnf_strip_latest('news-stories', 3, array_merge($not, $ids)));
    return $ids;
}

/* 칸 3개의 [글 ID, 머리 글자, 아래 글자] */
function hnf_strip_items() {
    $o = hnf_strip_opt();
    $pick = [];
    foreach ($o as $r) if (hnf_strip_ok($r['id'])) $pick[] = $r['id'];
    $auto = hnf_strip_auto($pick);
    $out = [];
    foreach ($o as $r) {
        if (hnf_strip_ok($r['id'])) $id = $r['id'];
        else { $id = array_shift($auto); $r['label'] = $r['sub'] = ''; }
        if (!$id) continue;
        $out[] = ['id' => $id, 'label' => $r['label'] !== '' ? $r['label'] : hnf_strip_label($id),
            'sub' => $r['sub'] !== '' ? $r['sub'] : get_the_date('Y.m.d', $id)];
    }
    return $out;
}

/* 머리 글자(영문 작은 글씨): 글이 있는 게시판·분류로 정함 */
function hnf_strip_label($id) {
    $slugs = wp_list_pluck(get_the_category($id), 'slug');
    $has = fn($s) => (bool) array_filter($slugs, fn($x) => $x === $s || strpos($x, $s . '-') === 0);
    if ($has('pub-insight')) {
        $n = preg_replace('/\D/', '', hnf_issue($id));
        return 'INSIGHT' . ($n !== '' ? ' No. ' . $n : '');
    }
    if (in_array('news-notice-2', $slugs, true)) return 'ACADEMIC EVENT';
    if ($has('news-notice')) return 'NOTICE';
    if ($has('news-stories')) return 'NEWS';
    if ($has('news-gallery')) return 'GALLERY';
    if ($has('research-results')) return 'RESEARCH';
    if ($has('pub-reports')) return 'REPORT';
    return 'HNF';
}

function hnf_home_strip() {
    $items = hnf_strip_items();
    if (!$items) return;
    echo '<nav class="hv-strip" aria-label="최신 소식"><div class="hv-strip-in">';
    foreach ($items as $it) {
        printf('<a href="%s"><small>%s</small><b>%s</b><time>%s</time></a>',
            esc_url(get_permalink($it['id'])), esc_html($it['label']),
            esc_html(wp_strip_all_tags(get_the_title($it['id']))), esc_html($it['sub']));
    }
    echo '</div></nav>';
}

/* ── 관리 화면 ── */
add_action('admin_menu', function () {
    add_submenu_page('hnf-home', '첫 화면 소식 띠', '첫 화면 소식 띠', 'edit_pages', 'hnf-strip', 'hnf_strip_page');
}, 20);

add_action('admin_post_hnf_strip', function () {
    if (!current_user_can('edit_pages')) wp_die('권한이 없습니다.');
    check_admin_referer('hnf_strip');
    $in = $_POST['s'] ?? [];
    $o = [];
    for ($i = 0; $i < 3; $i++) {
        $r = is_array($in[$i] ?? null) ? $in[$i] : [];
        $o[] = ['id' => absint($r['id'] ?? 0), 'label' => sanitize_text_field(wp_unslash($r['label'] ?? '')),
            'sub' => sanitize_text_field(wp_unslash($r['sub'] ?? ''))];
    }
    update_option('hnf_strip', $o, false);
    wp_safe_redirect(admin_url('admin.php?page=hnf-strip&saved=1'));
    exit;
});

function hnf_strip_page() {
    $o = hnf_strip_opt();
    $now = hnf_strip_items();
    echo '<div class="wrap hnf-home"><h1 class="hnf-h1">첫 화면 소식 띠</h1>';
    if (!empty($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>저장했습니다. <a href="' . esc_url(HNF_H . '/') . '" target="_blank">홈페이지에서 보기 ↗</a></p></div>';
    echo '<p class="hnf-hello">첫 화면 사진 아래 띠에 글 세 편이 나옵니다. 칸을 <b>자동</b>으로 두면 인사이트 최신 2편과 학술 행사 최신 글이 저절로 나오고, 글을 고르면 그 칸만 고른 글로 바뀝니다. 행사가 끝나면 다시 <b>자동</b>으로 돌려 두세요.</p>';

    // 고를 수 있는 글: 게시판별 최근 글
    $groups = [];
    foreach (HNF_STRIP_BOARDS as $slug) {
        $c = get_category_by_slug($slug);
        if (!$c) continue;
        $ids = hnf_strip_latest($slug, 30);
        if ($ids) $groups[$c->name] = $ids;
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('hnf_strip');
    echo '<input type="hidden" name="action" value="hnf_strip">';
    echo '<table class="widefat striped hnf-strip-tb" style="max-width:1100px"><thead><tr><th style="width:60px">칸</th><th>글</th><th style="width:200px">머리 글자 <small>(비우면 자동)</small></th><th style="width:220px">아래 글자 <small>(비우면 글 날짜)</small></th><th style="width:28%">지금 나오는 글</th></tr></thead><tbody>';
    foreach ($o as $i => $r) {
        echo '<tr><td><b>' . ($i + 1) . '</b></td><td><select name="s[' . $i . '][id]" style="max-width:100%"><option value="0">자동</option>';
        foreach ($groups as $g => $ids) {
            echo '<optgroup label="' . esc_attr($g) . '">';
            foreach ($ids as $id) {
                printf('<option value="%d"%s>%s · %s</option>', $id, selected($r['id'], $id, false),
                    esc_html(get_the_date('Y.m.d', $id)), esc_html(wp_trim_words(wp_strip_all_tags(get_the_title($id)), 14, '…')));
            }
            echo '</optgroup>';
        }
        echo '</select></td>';
        printf('<td><input type="text" name="s[%d][label]" value="%s" placeholder="예: WEEKLY FORUM" class="regular-text" style="width:100%%"></td>', $i, esc_attr($r['label']));
        printf('<td><input type="text" name="s[%d][sub]" value="%s" placeholder="예: 12.11(금) 17:30 · Zoom" class="regular-text" style="width:100%%"></td>', $i, esc_attr($r['sub']));
        $n = $now[$i] ?? null;
        echo '<td>' . ($n ? '<small style="color:#b28829;font-weight:600">' . esc_html($n['label']) . '</small><br>' . esc_html(get_the_title($n['id'])) . '<br><small>' . esc_html($n['sub']) . (hnf_strip_ok($r['id']) ? ' · 고른 글' : ' · 자동') . '</small>' : '-') . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p class="description">머리 글자·아래 글자는 글을 고른 칸에서만 쓰입니다. 자동 칸은 게시판에 맞춰 INSIGHT No. 2 · ACADEMIC EVENT · NOTICE 처럼 정해집니다.</p>';
    submit_button('저장');
    echo '</form></div>';
}

/* 영문 첫 화면 아래 띠: 영문 인사이트 최신 3편(모자라면 마지막 칸은 「All Insights」) */
function hnf_en_home_strip() {
    $ps = array_slice(hnf_en_insight_posts(), 0, 3);
    echo '<nav class="hv-strip" aria-label="Latest Insights"><div class="hv-strip-in">';
    foreach ($ps as $p) {
        $n = preg_replace('/\D/', '', hnf_issue($p->ID));
        printf('<a href="%s"><small>%s</small><b>%s</b><time>%s</time></a>', esc_url(hnf_en_insight_url($p->ID)),
            esc_html('INSIGHT' . ($n !== '' ? ' No. ' . $n : '')), esc_html(hnf_en_f($p->ID)['title']),
            esc_html(date('F j, Y', strtotime($p->post_date))));
    }
    if (count($ps) < 3) {
        printf('<a href="%s"><small>INSIGHT</small><b>All Insights</b><time>Essays by researchers, practitioners, and educators</time></a>', esc_url(hnf_en_insight_url()));
    }
    echo '</div></nav>';
}
