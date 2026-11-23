<?php
/**
 * 처음 한 번: 게시판 분류·페이지·예시 글을 만든다.
 * 사용: wp eval-file import_sample.php <sample.json>
 * 이미 있는 분류·페이지는 건너뛰고, 예시 글은 hnf_sample=1 표시 (나중에 한꺼번에 지울 수 있음).
 */
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$json = $args[0] ?? '';
$S = json_decode(file_get_contents($json), true);
$TD = get_template_directory();
$admin = get_user_by('login', 'inshnf_admin');
wp_set_current_user($admin ? $admin->ID : 1);

/* 기본 글·페이지 정리 */
foreach (['hello-world'] as $s) { $p = get_page_by_path($s, OBJECT, 'post'); if ($p) wp_delete_post($p->ID, true); }
foreach (['sample-page', 'privacy-policy'] as $s) { $p = get_page_by_path($s); if ($p) wp_delete_post($p->ID, true); }
if ($admin) wp_update_user(['ID' => $admin->ID, 'display_name' => '인류와미래연구소', 'nickname' => '인류와미래연구소']);

/* 페이지 */
$order = 0;
foreach ($S['pages'] as $pg) {
    $order += 10;
    if (get_page_by_path($pg['slug'])) continue;
    wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_name' => $pg['slug'], 'post_title' => $pg['title'],
        'post_content' => '', 'menu_order' => $order, 'comment_status' => 'closed', 'ping_status' => 'closed']);
}
$priv = get_page_by_path('privacy');
if ($priv) update_option('wp_page_for_privacy_policy', $priv->ID);
$unc = get_category_by_slug('uncategorized');

/* 그림 가져오기 (같은 그림은 한 번만) */
$media = [];
function hnf_import_file($path, $post_id, $title = null) {
    $tmp = wp_tempnam(basename($path));
    copy($path, $tmp);
    $file = ['name' => basename($path), 'tmp_name' => $tmp];
    $id = media_handle_sideload($file, $post_id, $title);
    if (is_wp_error($id)) { @unlink($tmp); WP_CLI::warning($path . ': ' . $id->get_error_message()); return 0; }
    return $id;
}

$made = 0;
foreach ($S['boards'] as $slug => $B) {
    $top = get_category_by_slug($slug);
    $top_id = $top ? $top->term_id : wp_insert_term($B['title'], 'category', ['slug' => $slug])['term_id'];
    $kid = [];
    foreach ($B['kids'] as $i => $name) {
        $ks = $slug . '-' . ($i + 1);
        $k = get_category_by_slug($ks);
        $kid[$name] = $k ? $k->term_id : wp_insert_term($name, 'category', ['slug' => $ks, 'parent' => $top_id])['term_id'];
    }
    // 이미 글이 있으면 예시 글을 다시 만들지 않음
    if (get_posts(['cat' => $top_id, 'posts_per_page' => 1, 'post_status' => 'any'])) continue;
    $n = count($B['items']);
    foreach ($B['items'] as $i => $it) {
        $date = !empty($it['date']) ? str_replace('.', '-', $it['date']) : date('Y-m-d', strtotime('2026-11-15 -' . ($i * 9) . ' days'));
        $time = sprintf('%s %02d:%02d:00', $date, 9 + intdiv($n - $i, 6) % 9, (($n - $i) * 7) % 60);
        $is_rules = ($it['from_html'] ?? '') === 'pub-rules-01';
        $content = $is_rules ? $S['rules'] : '<p>예시 글입니다. 실제 내용으로 바꾸어 주세요.</p>';
        if ($slug === 'pub-archive' && !empty($it['files'])) $content = '<p>' . esc_html($it['title']) . '입니다. 첨부 파일을 내려받아 작성해 주세요.</p>';
        $id = wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => $it['title'],
            'post_name' => $is_rules ? 'pub-rules-01' : '', 'post_content' => $content,
            'post_date' => $time, 'post_category' => [$kid[$it['cat']] ?? $top_id],
            'comment_status' => 'closed', 'ping_status' => 'closed']);
        if (is_wp_error($id) || !$id) { WP_CLI::warning('글 실패: ' . $it['title']); continue; }
        if ($unc) wp_remove_object_terms($id, $unc->term_id, 'category');
        $real = $is_rules || ($slug === 'pub-archive' && !empty($it['files']) && strpos($it['files'][0][0], 'files/') === 0);
        if (!$real) update_post_meta($id, 'hnf_sample', 1);
        if (!empty($it['writer'])) update_post_meta($id, 'hnf_writer', $it['writer']);
        if (isset($it['views'])) update_post_meta($id, 'hnf_views', (int) $it['views']);
        if (!empty($it['pin'])) stick_post($id);
        if (!empty($it['img'])) {
            if (!isset($media[$it['img']])) $media[$it['img']] = hnf_import_file($TD . '/' . $it['img'], 0);
            if ($media[$it['img']]) set_post_thumbnail($id, $media[$it['img']]);
        }
        foreach ($it['files'] ?? [] as $f) {
            if (strpos($f[0], 'files/') !== 0) continue;
            $name = $f[1] ? preg_replace('/\.[a-z0-9]+$/i', '', $f[1]) : null;
            hnf_import_file($TD . '/' . $f[0], $id, $name);
        }
        $made++;
    }
}
WP_CLI::success("예시 글 $made 건, 그림 " . count($media) . '장');
