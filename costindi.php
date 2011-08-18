<?php
require_once(__DIR__.'/src/TokenListDiff.php');

$d = new TokenListDiff(
	__DIR__.'/test_data/1a.php',
	__DIR__.'/test_data/1b.php'
);
var_dump($d->getDiff());
