<?php

require_once(__DIR__.'/src/TokenListDiff.php');
require_once(__DIR__.'/src/Renderer/inline.php');

$d = new TokenListDiff(
	__DIR__.'/test_data/1a.php',
	__DIR__.'/test_data/1b.php'
);

$r = new TokenListDiff_Renderer_inline();
echo $r->render($d);
