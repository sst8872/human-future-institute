<?php
the_post();
$slug = get_post_field('post_name', get_the_ID());
if (!hnf_render($slug)) {
    hnf_shell(get_the_title(), function () { echo '<div class="bv-body wp-content">'; the_content(); echo '</div>'; });
}
