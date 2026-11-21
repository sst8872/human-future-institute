<?php
/**
 * 페이지 편집기: 고정 페이지의 본문 구역(region)을 홈페이지 화면에서 바로 고친다.
 * 템플릿: <div class="hnf-region" data-region="main"><?php hnf_region_start('main','about-greeting'); ?>원본<?php hnf_region_end(); ?></div>
 * 저장본은 해당 WordPress 페이지의 meta `hnf_region_<구역>`. 비어 있으면 원본(테마 파일)을 보여 준다.
 * 직전 저장본 10개는 `hnf_hist_<구역>`에 보관.
 */

$GLOBALS['hnf_regions'] = [];

function hnf_page_id($slug) {
    static $c = [];
    if (!isset($c[$slug])) { $p = get_page_by_path($slug); $c[$slug] = $p ? $p->ID : 0; }
    return $c[$slug];
}
function hnf_region_edited($slug) {
    $id = hnf_page_id($slug);
    if (!$id) return false;
    foreach (get_post_meta($id) as $k => $v) if (strpos($k, 'hnf_region_') === 0 && $v[0] !== '') return true;
    return false;
}
function hnf_can_edit_pages() {
    return is_user_logged_in() && current_user_can('edit_pages') && current_user_can('unfiltered_html');
}

function hnf_region_start($key, $slug) {
    $GLOBALS['hnf_region_cur'] = [$key, $slug];
    ob_start();
}
function hnf_region_end() {
    [$key, $slug] = $GLOBALS['hnf_region_cur'];
    $orig = ob_get_clean();
    // 글 보기 화면에서는 게시판 안내 글을 감춤
    if (!empty($GLOBALS['hnf_single']) && $key === 'intro') return;
    $id = hnf_page_id($slug);
    $saved = $id ? get_post_meta($id, 'hnf_region_' . $key, true) : '';
    $html = $saved !== '' ? $saved : $orig;
    echo $html;
    if (empty($GLOBALS['hnf_single'])) $GLOBALS['hnf_regions'][$key] = ['slug' => $slug, 'html' => $html, 'saved' => $saved !== ''];
}

/* 로그인한 사람: 왼쪽 아래 관리 단추 · 편집 도구 */
add_action('wp_enqueue_scripts', function () {
    if (!is_user_logged_in() || !current_user_can('edit_posts')) return;
    wp_enqueue_style('hnf-editor', HNF_T . '/css/hnf-editor.css', [], HNF_VER);
    if (!hnf_can_edit_pages() || !is_page()) return;
    wp_enqueue_media();
    wp_enqueue_script('hnf-editor', HNF_T . '/js/hnf-editor.js', ['jquery', 'media-views'], HNF_VER, true);
});
add_action('wp_footer', function () {
    if (!is_user_logged_in() || !current_user_can('edit_posts')) return;
    $b = [];
    $b[] = '<a href="' . esc_url(admin_url('admin.php?page=hnf-home')) . '">연구소 관리</a>';
    if (!empty($GLOBALS['hnf_single'])) {
        $p = $GLOBALS['hnf_single'];
        if (current_user_can('edit_post', $p->ID)) $b[] = '<a class="pri" href="' . esc_url(admin_url('admin.php?page=hnf-write&id=' . $p->ID)) . '">✎ 이 글 고치기</a>';
    } elseif (!empty($GLOBALS['hnf_board'])) {
        $b[] = '<a class="pri" href="' . esc_url(admin_url('admin.php?page=hnf-write&board=' . $GLOBALS['hnf_board'])) . '">✎ 이 게시판에 글쓰기</a>';
    }
    if (hnf_can_edit_pages() && !empty($GLOBALS['hnf_regions']) && empty($GLOBALS['hnf_single'])) $b[] = '<button type="button" class="go">✎ 이 페이지 고치기</button>';
    $b[] = '<a class="out" href="' . esc_url(wp_logout_url(home_url(add_query_arg([])))) . '">로그아웃</a>';
    echo '<div class="hnf-fab">' . implode('', $b) . '</div>';

    if (!hnf_can_edit_pages() || empty($GLOBALS['hnf_regions']) || !is_page()) return;
    $slug = '';
    $src = [];
    $saved = [];
    foreach ($GLOBALS['hnf_regions'] as $k => $r) { $slug = $r['slug']; $src[$k] = base64_encode($r['html']); $saved[$k] = $r['saved']; }
    $id = hnf_page_id($slug);
    $cfg = ['slug' => $slug, 'title' => get_the_title($id), 'src' => $src, 'saved' => $saved,
        'ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('hnf_region_' . $slug), 'admin' => admin_url('admin.php?page=hnf-home'),
        'auto' => !empty($_GET['hnf_edit'])];
    echo '<script>window.HNF_EDIT=' . wp_json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>';
}, 5);

/* 바닥글 「관리자」 링크 */
function hnf_admin_link() {
    if (is_user_logged_in() && current_user_can('edit_posts')) echo '<li class="f-admin"><a href="' . esc_url(admin_url('admin.php?page=hnf-home')) . '">연구소 관리</a></li>';
    else echo '<li class="f-admin"><a href="' . esc_url(wp_login_url(admin_url('admin.php?page=hnf-home'))) . '">관리자</a></li>';
}

/* 저장 · 되돌리기 · 처음 상태로 */
function hnf_region_guard() {
    $slug = sanitize_title($_POST['slug'] ?? '');
    $id = hnf_page_id($slug);
    if (!$id || !check_ajax_referer('hnf_region_' . $slug, 'nonce', false) || !current_user_can('edit_page', $id) || !current_user_can('unfiltered_html')) {
        wp_send_json_error(['msg' => '권한이 없거나 로그인이 끝났습니다. 다시 로그인해 주세요.'], 403);
    }
    return $id;
}
function hnf_clean_html($h) {
    $h = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $h);
    $h = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $h);
    $h = preg_replace('#javascript:#i', '', $h);
    return trim($h);
}
function hnf_push_hist($id, $key) {
    $cur = get_post_meta($id, 'hnf_region_' . $key, true);
    $hist = get_post_meta($id, 'hnf_hist_' . $key, true) ?: [];
    array_unshift($hist, ['t' => current_time('Y.m.d H:i'), 'u' => wp_get_current_user()->display_name, 'html' => $cur]);
    update_post_meta($id, 'hnf_hist_' . $key, array_slice($hist, 0, 10));
}
add_action('wp_ajax_hnf_region_save', function () {
    $id = hnf_region_guard();
    $regions = json_decode(wp_unslash($_POST['regions'] ?? '{}'), true) ?: [];
    foreach ($regions as $key => $html) {
        $key = sanitize_key($key);
        $html = hnf_clean_html((string) $html);
        if ($html === '') continue;
        hnf_push_hist($id, $key);
        update_post_meta($id, 'hnf_region_' . $key, wp_slash($html));
    }
    wp_update_post(['ID' => $id]); // 수정일 갱신
    wp_send_json_success(['msg' => '저장했습니다.']);
});
add_action('wp_ajax_hnf_region_undo', function () {
    $id = hnf_region_guard();
    $done = 0;
    foreach (array_keys(get_post_meta($id)) as $k) {
        if (strpos($k, 'hnf_hist_') !== 0) continue;
        $key = substr($k, 9);
        $hist = get_post_meta($id, $k, true) ?: [];
        if (!$hist) continue;
        $prev = array_shift($hist);
        update_post_meta($id, $k, $hist);
        if ($prev['html'] === '') delete_post_meta($id, 'hnf_region_' . $key);
        else update_post_meta($id, 'hnf_region_' . $key, wp_slash($prev['html']));
        $done++;
    }
    wp_send_json_success(['msg' => $done ? '직전 저장본으로 되돌렸습니다.' : '되돌릴 저장본이 없습니다.']);
});
add_action('wp_ajax_hnf_region_reset', function () {
    $id = hnf_region_guard();
    foreach (array_keys(get_post_meta($id)) as $k) {
        if (strpos($k, 'hnf_region_') !== 0) continue;
        hnf_push_hist($id, substr($k, 11));
        delete_post_meta($id, $k);
    }
    wp_send_json_success(['msg' => '처음 상태로 돌렸습니다. (필요하면 「직전 저장본으로」로 다시 살릴 수 있습니다)']);
});

/* 게시판: 글쓰기 · 수정 · 삭제 단추 (로그인한 사람만) */
function hnf_board_tools($slug, $post = null) {
    return ''; // 왼쪽 아래 관리 단추(hnf-fab)로 대신함
    if (!is_user_logged_in()) return '';
    if ($post) {
        if (!current_user_can('edit_post', $post->ID)) return '';
        $h = '<a class="hnf-btool" href="' . esc_url(get_edit_post_link($post->ID)) . '">✎ 이 글 고치기</a>';
        if (current_user_can('delete_post', $post->ID)) $h .= '<a class="hnf-btool del" href="' . esc_url(get_delete_post_link($post->ID)) . '" onclick="return confirm(\'이 글을 휴지통으로 옮길까요? (관리 화면 휴지통에서 30일 안에 되살릴 수 있습니다)\')">휴지통으로</a>';
        return '<div class="hnf-btools">' . $h . '</div>';
    }
    if (!current_user_can('edit_posts')) return '';
    return '<div class="hnf-btools"><a class="hnf-btool" href="' . esc_url(admin_url('post-new.php?hnf_board=' . $slug)) . '">✎ 글쓰기</a><a class="hnf-btool line" href="' . esc_url(admin_url('admin.php?page=hnf-home')) . '">연구소 관리</a></div>';
}
add_action('wp_head', function () {
    if (!is_user_logged_in()) return;
    echo '<style>.hnf-btools{display:flex;justify-content:flex-end;gap:8px;margin:0 0 18px}.hnf-btool{display:inline-flex;align-items:center;height:38px;padding:0 16px;background:#0c2e86;color:#fff!important;font-size:14px;font-weight:600;border-radius:3px}.hnf-btool.line{background:#fff;color:#0c2e86!important;border:1px solid #0c2e86}.hnf-btool.del{background:#fff;color:#b42318!important;border:1px solid #e3b4ae}</style>';
});
