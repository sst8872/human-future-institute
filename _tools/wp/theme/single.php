<?php
the_post();
$p = get_post();
$board = hnf_post_board($p->ID);
hnf_count_view($p->ID);
if ($board) {
    $GLOBALS['hnf_single'] = $p;
    hnf_render($board);
} else {
    hnf_shell(get_the_title(), function () { echo '<div class="bv-body wp-content">'; the_content(); echo '</div>'; });
}
