<?php
// 첫 화면 외 나머지는 page.php·single.php·search.php·404.php가 맡는다.
if (is_front_page()) { hnf_render('index'); return; }
status_header(404);
include __DIR__ . '/404.php';
