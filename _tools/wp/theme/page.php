<?php
the_post();
$slug = get_post_field('post_name', get_the_ID());
// 하위 페이지(예: /en/greeting/)는 「부모-자식」 이름의 틀(pages/en-greeting.php)을 씀
$parent = wp_get_post_parent_id(get_the_ID());
if ($parent) $slug = get_post_field('post_name', $parent) . '-' . $slug;
if (!hnf_render($slug)) {
    hnf_shell(get_the_title(), function () { echo '<div class="bv-body wp-content">'; the_content(); echo '</div>'; });
}
