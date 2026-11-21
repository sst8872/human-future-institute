<?php
/**
 * 연구소 전용 관리 화면 + 글 편집 화면 정리
 */

/* ── 연구소 관리 첫 화면 ───────────────────────── */
add_action('admin_menu', function () {
    add_menu_page('연구소 관리', '연구소 관리', 'edit_posts', 'hnf-home', 'hnf_admin_home', 'dashicons-admin-home', 1);
    // 쓰지 않는 메뉴 숨김 (관리자도 주소로는 들어갈 수 있음)
    remove_menu_page('index.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('edit.php?post_type=page');
    if (!current_user_can('manage_options')) {
        remove_menu_page('tools.php');
        remove_menu_page('hostinger');
    }
}, 999);

/* 로그인·대시보드 → 연구소 관리 */
add_filter('login_redirect', function ($to, $req, $user) {
    if ($user instanceof WP_User && $user->has_cap('edit_posts') && (!$req || untrailingslashit($req) === untrailingslashit(admin_url()))) {
        return admin_url('admin.php?page=hnf-home');
    }
    return $to;
}, 20, 3);
add_action('load-index.php', function () { wp_safe_redirect(admin_url('admin.php?page=hnf-home')); exit; });

function hnf_admin_home() {
    $u = wp_get_current_user();
    $boards = hnf_boards();
    echo '<div class="wrap hnf-home">';
    echo '<h1 class="hnf-h1"><img src="' . esc_url(HNF_T . '/images/logo.png') . '" alt="">인간과미래연구소 관리</h1>';
    echo '<p class="hnf-hello"><b>' . esc_html($u->display_name) . '</b>님, 안녕하세요. 게시판에 글을 쓰거나 홈페이지의 글·사진을 고칠 수 있습니다. <a href="' . esc_url(HNF_H . '/') . '" target="_blank">홈페이지 열기 ↗</a></p>';

    echo '<h2 class="hnf-h2">게시판 <small>글을 쓰면 홈페이지 목록에 바로 나옵니다</small></h2><div class="hnf-grid">';
    foreach ($boards as $slug => $cfg) {
        $top = get_category_by_slug($slug);
        if (!$top) continue;
        $q = new WP_Query(['cat' => $top->term_id, 'posts_per_page' => 1, 'post_status' => 'publish', 'ignore_sticky_posts' => 1]);
        $last = $q->posts ? get_the_date('Y.m.d', $q->posts[0]) : '-';
        $drafts = (new WP_Query(['cat' => $top->term_id, 'posts_per_page' => 1, 'post_status' => ['draft', 'pending'], 'fields' => 'ids']))->found_posts;
        $kids = implode(' · ', wp_list_pluck(hnf_kids($slug), 'name'));
        $type = ['table' => '표 목록', 'card' => '카드(대표 사진)', 'gallery' => '사진(대표 사진)'][$cfg['type']];
        echo '<div class="hnf-card">';
        echo '<div class="hnf-card-hd"><strong>' . esc_html($top->name) . '</strong><span>' . esc_html($type) . '</span></div>';
        echo '<p class="hnf-meta">글 <b>' . (int) $q->found_posts . '</b>건 · 최근 ' . esc_html($last) . ($drafts ? ' · <em>임시 저장 ' . (int) $drafts . '건</em>' : '') . '</p>';
        if ($kids) echo '<p class="hnf-kids">분류: ' . esc_html($kids) . '</p>';
        echo '<div class="hnf-btns">';
        echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=hnf-write&board=' . $slug)) . '">✎ 글쓰기</a>';
        echo '<a class="button" href="' . esc_url(admin_url('edit.php?category_name=' . $slug)) . '">글 목록</a>';
        echo '<a class="button-link" href="' . esc_url(HNF_H . '/' . $slug . '/') . '" target="_blank">홈페이지에서 보기 ↗</a>';
        echo '</div></div>';
    }
    echo '</div>';

    if (current_user_can('edit_pages')) {
        echo '<h2 class="hnf-h2">페이지 고치기 <small>홈페이지 화면에서 글자를 눌러 바로 고칩니다</small></h2>';
        $groups = [
            '연구소 소개' => ['about-greeting', 'about-vision', 'about-history', 'about-organization', 'about-people'],
            '연구' => ['research-fields', 'research-projects'],
            '학술 행사' => ['event-forum', 'event-meeting', 'event-conference'],
            '미래혁신' => ['future-policy', 'future-network', 'future-lecture'],
            '기타' => ['about-location', 'sitemap', 'privacy'],
            '게시판 안내 글·담당부서' => array_keys($boards),
        ];
        echo '<div class="hnf-pages">';
        foreach ($groups as $g => $slugs) {
            echo '<div class="hnf-pg"><h3>' . esc_html($g) . '</h3><ul>';
            foreach ($slugs as $s) {
                $p = get_page_by_path($s);
                if (!$p) continue;
                $edited = hnf_region_edited($s);
                echo '<li><a href="' . esc_url(HNF_H . '/' . $s . '/?hnf_edit=1') . '" target="_blank">' . esc_html($p->post_title) . '</a>' . ($edited ? '<span class="hnf-tag">고침</span>' : '') . '</li>';
            }
            echo '</ul></div>';
        }
        echo '</div>';
    }

    echo '<h2 class="hnf-h2">최근 글</h2><table class="widefat striped hnf-recent"><thead><tr><th>게시판</th><th>제목</th><th>상태</th><th>날짜</th><th>쓴 사람</th></tr></thead><tbody>';
    $recent = get_posts(['posts_per_page' => 10, 'post_status' => ['publish', 'draft', 'pending', 'future'], 'ignore_sticky_posts' => 1, 'orderby' => 'modified']);
    $st = ['publish' => '게시됨', 'draft' => '임시 저장', 'pending' => '검토 대기', 'future' => '예약'];
    foreach ($recent as $p) {
        $b = hnf_post_board($p->ID);
        echo '<tr><td>' . esc_html($b ? get_category_by_slug($b)->name : '-') . '</td><td><a href="' . esc_url(get_edit_post_link($p->ID)) . '">' . esc_html($p->post_title ?: '(제목 없음)') . '</a></td><td>' . ($st[$p->post_status] ?? $p->post_status) . '</td><td>' . get_the_modified_date('Y.m.d H:i', $p) . '</td><td>' . esc_html(hnf_writer($p)) . '</td></tr>';
    }
    echo '</tbody></table>';

    echo '<div class="hnf-help"><h3>도움말</h3><ul>';
    echo '<li><b>글쓰기</b>: 게시판의 「글쓰기」를 누르면 그 게시판에 맞는 칸이 나옵니다. 칸만 채우면 홈페이지 모양은 자동으로 맞춰집니다. 오른쪽 위에 미리 보기가 나옵니다.</li><li><b>본문 쓰는 법</b>: 빈 줄 = 문단 나눔, 「## 소제목」, 「- 항목」, 「1. 항목」, 「**굵게**」. 다른 곳에서 복사해 붙여 넣어도 글자만 들어갑니다.</li>';
    echo '<li><b>첨부 파일</b>: 「파일 올리기·고르기」로 PDF·HWP·DOCX·XLSX를 올리면 목록의 「파일」 칸과 글 보기의 「첨부 파일」에 자동으로 나옵니다. 끌어서 순서를 바꿀 수 있습니다.</li>';
    echo '<li><b>임시 저장</b>: 「임시 저장」한 글은 홈페이지에 나오지 않습니다. 다 쓴 뒤 「게시하기」를 누르세요.</li>';
    echo '<li><b>공지 고정</b>: 공지사항에서 「공지로 맨 위 고정」을 체크하면 목록 맨 위에 「공지」로 나옵니다.</li>';
    echo '<li><b>페이지 고치기</b>: 화면 아래 노란 막대에서 「저장」을 눌러야 반영됩니다. 잘못 고쳤으면 「직전 저장본으로」나 「처음 상태로」를 누르세요.</li>';
    echo '</ul></div></div>';
}

/* ── 글쓰기: 게시판 미리 고르기 ───────────────── */
add_filter('option_default_category', function ($v) {
    global $pagenow;
    if ($pagenow === 'post-new.php' && !empty($_GET['hnf_board'])) {
        $kids = hnf_kids(sanitize_title($_GET['hnf_board']));
        if ($kids) return $kids[0]->term_id;
        $t = get_category_by_slug(sanitize_title($_GET['hnf_board']));
        if ($t) return $t->term_id;
    }
    return $v;
});

/* 글 편집 화면 정리 */
add_action('add_meta_boxes_post', function () {
    foreach (['tagsdiv-post_tag', 'postcustom', 'commentstatusdiv', 'commentsdiv', 'trackbacksdiv', 'slugdiv', 'formatdiv', 'authordiv'] as $b) {
        remove_meta_box($b, 'post', 'normal');
        remove_meta_box($b, 'post', 'side');
        remove_meta_box($b, 'post', 'advanced');
    }
    global $wp_meta_boxes;
    if (isset($wp_meta_boxes['post']['side']['core']['categorydiv'])) $wp_meta_boxes['post']['side']['core']['categorydiv']['title'] = '게시판 분류 (하나만 고르세요)';
    if (isset($wp_meta_boxes['post']['side']['low']['postimagediv'])) $wp_meta_boxes['post']['side']['low']['postimagediv']['title'] = '대표 사진 (카드·갤러리 목록 그림)';
    if (isset($wp_meta_boxes['post']['normal']['core']['postexcerpt'])) $wp_meta_boxes['post']['normal']['core']['postexcerpt']['title'] = '요약 (인사이트: 첫 화면 소개 글로 쓰임, 비워도 됨)';
    add_meta_box('hnf_writer', '작성 (목록의 작성자 칸)', function ($post) {
        $v = get_post_meta($post->ID, 'hnf_writer', true);
        wp_nonce_field('hnf_writer', 'hnf_writer_nonce');
        echo '<input type="text" name="hnf_writer" value="' . esc_attr($v) . '" placeholder="' . esc_attr(get_the_author_meta('display_name', $post->post_author ?: get_current_user_id())) . '" style="width:100%">';
        echo '<p class="description">비우면 쓴 사람 표시 이름(부서)이 나옵니다.</p>';
    }, 'post', 'side', 'default');
});
add_action('save_post_post', function ($id) {
    if (!isset($_POST['hnf_writer_nonce']) || !wp_verify_nonce($_POST['hnf_writer_nonce'], 'hnf_writer')) return;
    if (!current_user_can('edit_post', $id)) return;
    $v = sanitize_text_field(wp_unslash($_POST['hnf_writer'] ?? ''));
    if ($v === '') delete_post_meta($id, 'hnf_writer'); else update_post_meta($id, 'hnf_writer', $v);
    // 예시 글을 고치면 예시 표시를 뗌
    delete_post_meta($id, 'hnf_sample');
});
add_filter('gettext', function ($t, $orig, $dom) {
    if ($dom !== 'default') return $t;
    static $map = [
        'Stick this post to the front page' => '공지로 맨 위 고정 (공지사항)',
        'Add New Post' => '새 글 쓰기',
        'Add Media' => '미디어 추가 (사진·파일)',
    ];
    return $map[$orig] ?? $t;
}, 10, 3);
add_filter('enter_title_here', fn() => '제목을 입력하세요');

/* 하위 분류만 고를 수 있게: 상위(게시판 이름) 체크 상자 숨김은 CSS·JS로 */
add_action('admin_footer-post.php', 'hnf_cat_js');
add_action('admin_footer-post-new.php', 'hnf_cat_js');
function hnf_cat_js() {
    $tops = [];
    foreach (array_keys(hnf_boards()) as $s) { $t = get_category_by_slug($s); if ($t && hnf_kids($s)) $tops[] = $t->term_id; }
    ?>
<script>
(function(){
  var tops = <?= wp_json_encode($tops) ?>;
  tops.forEach(function(id){
    var cb = document.getElementById('in-category-' + id);
    if (cb) { cb.style.display = 'none'; cb.checked = false; cb.closest('label').classList.add('hnf-top'); }
  });
  var box = document.getElementById('categorychecklist');
  if (box) box.addEventListener('change', function(e){
    if (e.target.type !== 'checkbox' || !e.target.checked) return;
    box.querySelectorAll('input[type=checkbox]').forEach(function(x){ if (x !== e.target) x.checked = false; });
  });
  var pop = document.getElementById('category-tabs'); if (pop) pop.style.display = 'none';
  var add = document.getElementById('category-adder'); if (add) add.style.display = 'none';
})();
</script>
    <?php
}

/* 글 목록: 게시판 칸 */
add_filter('manage_post_posts_columns', function ($c) {
    unset($c['tags'], $c['comments']);
    $c['categories'] = '게시판 · 분류';
    return $c;
});

/* 한글·한셀 파일 올리기 허용 */
add_filter('upload_mimes', function ($m) {
    $m['hwp'] = 'application/x-hwp';
    $m['hwpx'] = 'application/haansofthwpx';
    $m['hwpx|hwpx'] = 'application/vnd.hancom.hwpx';
    return $m;
});
add_filter('wp_check_filetype_and_ext', function ($d, $file, $name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (in_array($ext, ['hwp', 'hwpx'])) { $d['ext'] = $ext; $d['type'] = $ext === 'hwp' ? 'application/x-hwp' : 'application/haansofthwpx'; $d['proper_filename'] = $name; }
    return $d;
}, 10, 3);

/* 관리 화면 모양 */
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('hnf-admin', HNF_T . '/css/hnf-admin.css', [], HNF_VER);
});
add_action('login_enqueue_scripts', function () {
    echo '<style>body.login{background:#0b1633}.login h1 a{background:url(' . esc_url(HNF_T . '/images/logo.png') . ') center/contain no-repeat;width:96px;height:96px;background-color:#0c2e86;border-radius:50%}.login #nav a,.login #backtoblog a{color:#c9d3ea!important}.wp-core-ui .button-primary{background:#0c2e86;border-color:#0c2e86}</style>';
});
add_filter('login_headerurl', fn() => HNF_H . '/');
add_filter('login_headertext', fn() => '인간과미래연구소');
add_filter('admin_footer_text', fn() => '인간과미래연구소 홈페이지 관리');
add_filter('update_footer', fn() => '', 99);
add_action('admin_bar_menu', function ($bar) {
    $bar->remove_node('wp-logo');
    $bar->remove_node('comments');
    $bar->add_node(['id' => 'hnf-home', 'title' => '연구소 관리', 'href' => admin_url('admin.php?page=hnf-home')]);
}, 999);
