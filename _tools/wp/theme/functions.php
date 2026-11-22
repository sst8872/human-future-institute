<?php
/**
 * 인간과미래연구소 테마 (정적 사이트를 그대로 옮긴 맞춤 테마)
 * 원본: github.com/sst8872/human-future-institute → _tools/wp/build_wp.py 로 생성
 */
define('HNF_T', get_template_directory_uri());
define('HNF_H', untrailingslashit(home_url()));
define('HNF_VER', '20261122zk');

require __DIR__ . '/inc/boards.php';
require __DIR__ . '/inc/admin.php';
require __DIR__ . '/inc/editor.php';
require __DIR__ . '/inc/write.php';
require __DIR__ . '/inc/apply.php';
require __DIR__ . '/inc/en.php';

add_action('after_setup_theme', function () {
    add_theme_support('post-thumbnails');
});
add_filter('show_admin_bar', '__return_false');

/* 머리말 정리: 이모지·oEmbed·RSD 등 쓰지 않는 것 빼기 */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 100);

function hnf_head() {
    echo '<link rel="stylesheet" href="' . HNF_T . '/css/wp.css?v=' . HNF_VER . "\">\n";
    hnf_og();
    wp_head();
}

/* 링크 미리보기(카카오톡·문자·SNS) — 대표 이미지는 남색 가로형 로고 (2026-11-21) */
function hnf_og() {
    $site  = '인간과미래연구소';
    $full  = '인간과미래연구소 | Institute for Human & Future';
    if (is_front_page() || is_home()) $title = $full;
    elseif (is_singular()) $title = wp_strip_all_tags(get_the_title()) . ' | 인간과미래연구소';
    else $title = hnf_title($full);
    $desc  = '기술 변혁의 시대, 인간의 가치를 중심에 둔 미래 담론을 선도하는 춘천교육대학교 인간과미래연구소';
    if (is_singular('post')) {
        $f = get_post_meta(get_the_ID(), 'hnf_f', true);
        $t = is_array($f) ? trim(wp_strip_all_tags((string) ($f['summary'] ?? $f['body'] ?? ''))) : '';
        if ($t !== '') $desc = mb_substr(preg_replace('/\s+/u', ' ', preg_replace('/[#*\-]+ ?/u', '', $t)), 0, 110);
    }
    $url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
    $img = HNF_T . '/images/og-home.jpg';
    $m = [
        ['property', 'og:type', is_singular('post') ? 'article' : 'website'], ['property', 'og:site_name', $site],
        ['property', 'og:title', $title], ['property', 'og:description', $desc], ['property', 'og:url', $url],
        ['property', 'og:image', $img], ['property', 'og:image:width', '1200'], ['property', 'og:image:height', '630'],
        ['property', 'og:image:alt', '인간과미래연구소 홈페이지 첫 화면'], ['property', 'og:locale', 'ko_KR'],
        ['name', 'twitter:card', 'summary_large_image'], ['name', 'twitter:image', $img],
    ];
    foreach ($m as [$k, $n, $v]) echo '<meta ' . $k . '="' . $n . '" content="' . esc_attr(html_entity_decode($v, ENT_QUOTES, 'UTF-8')) . "\">\n";
}

function hnf_title($orig) {
    $site = '인간과미래연구소 | Institute for Human & Future';
    if (is_singular('post')) return esc_html(wp_strip_all_tags(get_the_title())) . ' | ' . $site;
    if (is_search()) return '검색 결과 | ' . $site;
    if (is_404()) return '페이지를 찾을 수 없습니다 | ' . $site;
    if (!empty($GLOBALS['hnf_shell']) && is_page()) return esc_html(get_the_title()) . ' | ' . $site;
    return esc_html($orig);
}

/* 페이지 파일 그리기 */
function hnf_render($slug) {
    $f = __DIR__ . '/pages/' . $slug . '.php';
    if (!preg_match('/^[a-z0-9_-]+$/', $slug) || !file_exists($f)) return false;
    if (isset(hnf_boards()[$slug])) $GLOBALS['hnf_board'] = $slug;
    include $f;
    return true;
}

/* 일반 틀(_shell): 제목 + 내용 */
function hnf_shell($title, $content_cb) {
    $GLOBALS['hnf_shell'] = ['title' => $title, 'cb' => $content_cb];
    include __DIR__ . '/pages/_shell.php';
}
function hnf_shell_title() { return $GLOBALS['hnf_shell']['title'] ?? ''; }
function hnf_shell_content() {
    $cb = $GLOBALS['hnf_shell']['cb'] ?? null;
    if ($cb) call_user_func($cb);
}

/* 옛 주소·쓰지 않는 보관함 → 게시판으로 */
add_action('template_redirect', function () {
    if (is_category()) {
        $c = get_queried_object();
        $top = $c->parent ? get_category($c->parent) : $c;
        $url = HNF_H . '/' . $top->slug . '/';
        if ($c->parent && preg_match('/-(\d+)$/', $c->slug, $m)) $url .= '?bc=' . $m[1];
        wp_redirect($url, 301);
        exit;
    }
    if (is_author() || is_date() || is_tag() || (is_home() && !is_front_page())) {
        wp_redirect(HNF_H . '/', 301);
        exit;
    }
    if (is_attachment()) {
        wp_redirect(wp_get_attachment_url(get_queried_object_id()), 301);
        exit;
    }
});

/* 첫 화면: 공지 목록 자료 */
function hnf_notices_json() {
    $out = [];
    $top = get_category_by_slug('news-notice');
    if (!$top) return '[]';
    $sticky = get_option('sticky_posts') ?: [];
    $q = new WP_Query(['cat' => $top->term_id, 'posts_per_page' => 12, 'ignore_sticky_posts' => 1,
        'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true]);
    $pins = [];
    $rest = [];
    foreach ($q->posts as $p) {
        $row = ['cat' => hnf_post_kid_name($p->ID), 'title' => wp_strip_all_tags($p->post_title),
            'date' => get_the_date('Y.m.d', $p), 'url' => get_permalink($p), 'fix' => in_array($p->ID, $sticky)];
        if ($row['fix']) $pins[] = $row; else $rest[] = $row;
    }
    $out = array_merge($pins, $rest);
    return wp_json_encode($out, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
}

/* 첫 화면: 주간 인사이트 슬라이드 (예시 글이 아닌 실제 인사이트가 있을 때만) */
function hnf_has_insight() {
    $top = get_category_by_slug('pub-insight');
    if (!$top) return false;
    return (bool) get_posts(['cat' => $top->term_id, 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => [['key' => 'hnf_sample', 'compare' => 'NOT EXISTS']]]);
}
function hnf_insight_slides() {
    $top = get_category_by_slug('pub-insight');
    if (!$top) return false;
    $q = new WP_Query(['cat' => $top->term_id, 'posts_per_page' => 6, 'ignore_sticky_posts' => 1, 'no_found_rows' => true,
        'meta_query' => [['key' => 'hnf_sample', 'compare' => 'NOT EXISTS']]]);
    if (!$q->have_posts()) return false;
    foreach ($q->posts as $p) {
        $img = get_the_post_thumbnail_url($p, 'large') ?: HNF_T . '/images/research01.jpg';
        $desc = has_excerpt($p) ? get_the_excerpt($p) : wp_trim_words(wp_strip_all_tags(strip_shortcodes($p->post_content)), 60, '…');
        printf('<div class="swiper-slide"><div class="rp-img"><img src="%s" alt=""></div><div class="rp-cont"><a href="%s" class="rp-label">%s<i></i></a><p class="rp-title">%s</p><div class="rp-desc">%s</div><a href="%s" class="rp-more">자세히보기</a></div></div>',
            esc_url($img), esc_url(HNF_H . '/pub-insight/'), esc_html(trim((hnf_post_kid_name($p->ID) ?: '인사이트') . ' ' . hnf_issue($p->ID))),
            esc_html(wp_strip_all_tags($p->post_title)), esc_html($desc), esc_url(get_permalink($p)));
    }
    return true;
}

/* 첫 화면 03: 연구소 소식 게시글 제목 카드 (예시 글 제외) */
function hnf_stories_query() {
    $top = get_category_by_slug('news-stories');
    if (!$top) return null;
    return new WP_Query(['cat' => $top->term_id, 'posts_per_page' => 9, 'ignore_sticky_posts' => 1, 'no_found_rows' => true,
        'meta_query' => [['key' => 'hnf_sample', 'compare' => 'NOT EXISTS']]]);
}
function hnf_has_stories() {
    $q = hnf_stories_query();
    return $q && $q->have_posts();
}
function hnf_stories_slides() {
    $q = hnf_stories_query();
    if (!$q) return;
    // 최근 9편을 옛 글 → 최근 글 순으로(최근 글이 맨 오른쪽)
    foreach (array_reverse($q->posts) as $p) {
        printf('<div class="swiper-slide"><a href="%s" class="hl-card"><span class="hl-cate">%s</span><p class="hl-title">%s</p><div class="hl-meta"><time>%s</time><span class="hl-media">자세히 보기<svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></div></a></div>',
            esc_url(get_permalink($p)), esc_html(hnf_post_kid_name($p->ID) ?: '연구소 소식'),
            esc_html(wp_strip_all_tags($p->post_title)), esc_html(get_the_date('Y.m.d', $p)));
    }
}

/* 조회수 */
function hnf_views($id) { return (int) get_post_meta($id, 'hnf_views', true); }
function hnf_count_view($id) {
    if (is_user_logged_in() || is_preview()) return;
    update_post_meta($id, 'hnf_views', hnf_views($id) + 1);
}

/* 글 목록에 「작성(부서)」 칸 값은 사용자 표시 이름. 예시 글은 hnf_writer 사용 */
function hnf_writer($p) {
    $w = get_post_meta($p->ID, 'hnf_writer', true);
    return $w ?: get_the_author_meta('display_name', $p->post_author);
}
