<?php
hnf_shell('페이지를 찾을 수 없습니다', function () {
    echo '<div class="nf"><p>찾으시는 페이지가 없거나 주소가 바뀌었습니다.</p><a href="' . esc_url(HNF_H . '/') . '" class="btn">첫 화면으로</a> <a href="' . esc_url(HNF_H . '/sitemap/') . '" class="btn line">사이트맵</a></div>';
});
