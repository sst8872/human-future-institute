<?php
/**
 * 행사 참여 신청 (주간 포럼 등) · 인사이트 집필 신청
 * - 신청서: 원본 HTML event-apply.html · insight-apply.html 의 form.apply-form → admin-post.php?action=hnf_apply
 * - 신청 종류(event)마다 칸이 다름: hnf_apply_fields($event)
 * - 저장: 글 종류 hnf_apply(공개 안 함), 칸은 meta hnf_a
 * - 관리: 연구소 관리 → 참여·집필 신청 (종류별 탭 · 목록 · CSV 내려받기 · 삭제)
 */

function hnf_apply_events() {
    return [
        'forum-01' => '제1차 주간 포럼 「인간과 미래 담론에 대한 연구소의 역할」 (2026. 11. 28.)',
        'insight-writer' => '인사이트 집필 신청',
    ];
}
/* 신청 알림 받는 주소 (연구소 메일, 2026-11-21 사용자 지시) */
function hnf_apply_notify_to() { return 'hjyoon@cnue.ac.kr'; }
function hnf_apply_fields($event = '') {
    if ($event === 'insight-writer')
        return ['org' => '소속', 'position' => '직위', 'name' => '성명', 'phone' => '전화번호', 'email' => '이메일',
            'title' => '제목', 'outline' => '개요'];
    return ['org' => '소속', 'position' => '직위', 'major' => '전공', 'name' => '성명', 'email' => '이메일', 'phone' => '전화번호'];
}
/* 여러 줄로 받는 칸과 최대 글자 수 */
function hnf_apply_long() { return ['outline' => 600]; }

add_action('init', function () {
    register_post_type('hnf_apply', ['public' => false, 'show_ui' => false, 'label' => '참여 신청', 'supports' => ['title']]);
});

/* 신청 받기 */
add_action('admin_post_nopriv_hnf_apply', 'hnf_apply_submit');
add_action('admin_post_hnf_apply', 'hnf_apply_submit');
function hnf_apply_submit() {
    $back = wp_validate_redirect(wp_get_referer() ?: '', home_url('/event-apply/'));
    $back = remove_query_arg(['sent', 'err', 'dup'], strtok($back, '#'));
    $go = function ($arg) use ($back) { wp_safe_redirect(add_query_arg($arg, '1', $back) . '#apply'); exit; };

    if (!empty($_POST['website'])) $go('sent');                        // 자동 등록 막기(사람 눈에 안 보이는 칸)
    $event = sanitize_key($_POST['event'] ?? '');
    if (!isset(hnf_apply_events()[$event])) $go('err');
    $a = [];
    $long = hnf_apply_long();
    foreach (hnf_apply_fields($event) as $k => $lb) {
        $raw = wp_unslash($_POST[$k] ?? '');
        $a[$k] = trim(isset($long[$k]) ? sanitize_textarea_field($raw) : sanitize_text_field($raw));
        if ($a[$k] === '' || mb_strlen($a[$k]) > ($long[$k] ?? 100)) $go('err');
    }
    $a['email'] = sanitize_email($a['email']);
    if (!is_email($a['email']) || !preg_match('/^[0-9+\-\s()]{8,20}$/', $a['phone']) || empty($_POST['agree'])) $go('err');

    $dup = get_posts(['post_type' => 'hnf_apply', 'post_status' => 'private', 'numberposts' => 1, 'fields' => 'ids',
        'meta_query' => [['key' => 'hnf_event', 'value' => $event], ['key' => 'hnf_email', 'value' => strtolower($a['email'])]]]);
    if ($dup) $go('dup');

    $id = wp_insert_post(['post_type' => 'hnf_apply', 'post_status' => 'private', 'post_author' => 1,
        'post_title' => $a['name'] . ' (' . $a['org'] . ')'], true);
    if (is_wp_error($id)) $go('err');
    update_post_meta($id, 'hnf_a', $a);
    update_post_meta($id, 'hnf_event', $event);
    update_post_meta($id, 'hnf_email', strtolower($a['email']));

    $kind = $event === 'insight-writer' ? '인사이트 집필 신청' : '참여 신청';
    $body = hnf_apply_events()[$event] . ($event === 'insight-writer' ? '' : ' 참여 신청') . "이 들어왔습니다.\n\n";
    foreach (hnf_apply_fields($event) as $k => $lb) $body .= $lb . ': ' . $a[$k] . "\n";
    $body .= "\n신청 목록: " . admin_url('admin.php?page=hnf-apply&event=' . $event);
    wp_mail(hnf_apply_notify_to(), '[인간과미래연구소] ' . $kind . ' — ' . $a['name'], $body);
    $go('sent');
}

/* 관리 화면 */
add_action('admin_menu', function () {
    add_submenu_page('hnf-home', '참여·집필 신청', '참여·집필 신청', 'edit_posts', 'hnf-apply', 'hnf_apply_page');
}, 1000);   // 「연구소 관리」(admin.php, 999) 다음에 붙여야 주소가 admin.php?page=hnf-apply 로 바르게 생김

function hnf_apply_rows($event) {
    $q = ['post_type' => 'hnf_apply', 'post_status' => 'private', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'ASC'];
    if ($event) $q['meta_query'] = [['key' => 'hnf_event', 'value' => $event]];
    return get_posts($q);
}

function hnf_apply_page() {
    if (!current_user_can('edit_posts')) return;
    $events = hnf_apply_events();
    // 처음 열 때는 가장 최근에 신청이 들어온 목록을 보여 줌
    $last = get_posts(['post_type' => 'hnf_apply', 'post_status' => 'private', 'numberposts' => 1, 'orderby' => 'date', 'order' => 'DESC']);
    $def = $last ? get_post_meta($last[0]->ID, 'hnf_event', true) : '';
    if (!isset($events[$def])) $def = array_key_first($events);
    $event = sanitize_key($_GET['event'] ?? $def);
    if (!isset($events[$event])) $event = $def;
    $rows = hnf_apply_rows($event);
    $F = hnf_apply_fields($event);
    echo '<div class="wrap hnf-wrap"><h1 class="hnf-h1">참여·집필 신청</h1>';
    if (!empty($_GET['deleted'])) echo '<div class="notice notice-success"><p>신청 1건을 지웠습니다.</p></div>';
    // 신청 종류를 탭으로 (괄호 안은 건수)
    echo '<nav class="nav-tab-wrapper" style="margin:18px 0 16px">';
    foreach ($events as $k => $lb) {
        $n = count(hnf_apply_rows($k));
        echo '<a class="nav-tab' . ($k === $event ? ' nav-tab-active' : '') . '" href="' . esc_url(admin_url('admin.php?page=hnf-apply&event=' . $k)) . '">' . esc_html($lb) . ' (' . $n . ')</a>';
    }
    echo '</nav>';
    echo '<p><b>' . count($rows) . '건</b> 신청 &nbsp; <a class="button button-primary" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=hnf_apply_csv&event=' . $event), 'hnf_apply_csv')) . '">엑셀(CSV)로 내려받기</a></p>';
    echo '<table class="widefat striped"><thead><tr><th>번호</th><th>신청 일시</th>';
    foreach ($F as $lb) echo '<th>' . esc_html($lb) . '</th>';
    echo '<th></th></tr></thead><tbody>';
    if (!$rows) echo '<tr><td colspan="' . (count($F) + 3) . '">아직 신청이 없습니다.</td></tr>';
    foreach ($rows as $n => $p) {
        $a = get_post_meta($p->ID, 'hnf_a', true) ?: [];
        echo '<tr><td>' . ($n + 1) . '</td><td>' . esc_html(get_the_date('Y.m.d H:i', $p)) . '</td>';
        foreach ($F as $k => $lb) echo '<td>' . nl2br(esc_html($a[$k] ?? '')) . '</td>';
        $del = wp_nonce_url(admin_url('admin-post.php?action=hnf_apply_del&id=' . $p->ID . '&event=' . $event), 'hnf_apply_del_' . $p->ID);
        echo '<td><a href="' . esc_url($del) . '" onclick="return confirm(\'이 신청을 지울까요? 되돌릴 수 없습니다.\')" style="color:#b32d2e">삭제</a></td></tr>';
    }
    echo '</tbody></table>';
    echo '<p style="color:#666;margin-top:14px">개인정보이므로 쓸 일이 끝나면 지워 주세요(행사는 발표 자료를 보낸 뒤, 인사이트 집필 신청은 게재 여부를 알려 드린 뒤). 새 신청이 들어오면 ' . esc_html(hnf_apply_notify_to()) . ' 로 알림 메일이 갑니다.</p></div>';
}

add_action('admin_post_hnf_apply_csv', function () {
    if (!current_user_can('edit_posts') || !check_admin_referer('hnf_apply_csv')) wp_die('권한이 없습니다.');
    $event = sanitize_key($_GET['event'] ?? '');
    $F = hnf_apply_fields($event);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="apply-' . $event . '-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");   // 엑셀에서 한글이 깨지지 않게
    fputcsv($o, array_merge(['번호', '신청 일시'], array_values($F)));
    foreach (hnf_apply_rows($event) as $n => $p) {
        $a = get_post_meta($p->ID, 'hnf_a', true) ?: [];
        fputcsv($o, array_merge([$n + 1, get_the_date('Y-m-d H:i', $p)], array_map(fn($k) => $a[$k] ?? '', array_keys($F))));
    }
    exit;
});

add_action('admin_post_hnf_apply_del', function () {
    $id = (int) ($_GET['id'] ?? 0);
    if (!current_user_can('edit_posts') || !check_admin_referer('hnf_apply_del_' . $id) || get_post_type($id) !== 'hnf_apply') wp_die('권한이 없습니다.');
    wp_delete_post($id, true);
    wp_safe_redirect(admin_url('admin.php?page=hnf-apply&deleted=1&event=' . sanitize_key($_GET['event'] ?? '')));
    exit;
});
