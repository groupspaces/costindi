#!/usr/bin/php
<?php

require_once(__DIR__.'/src/TokenListDiff.php');
require_once(__DIR__.'/src/ConsoleColour.php');
require_once(__DIR__.'/src/Renderer/inline.php');

use Costindi\ConsoleColour as ConsoleColour;

array_shift($argv);
if (count($argv) == 7) {
	// called via GIT_EXTERNAL_DIFF
	$oldfile = $argv[1];
	$newfile = $argv[4];
} elseif (count($argv) == 2) {
	$oldfile = $argv[0];
	$newfile = $argv[1];
} else {
	echo "Usage: costindi oldfile newfile\n";
	exit(1);
}

if (!is_readable($oldfile)) {
	echo "Cannot read file: $oldfile\n";
	exit(1);
}
if (!is_readable($newfile)) {
	echo "Cannot read file: $newfile\n";
	exit(1);
}

$d = new TokenListDiff(
	$oldfile,
	$newfile
);

$c = popen('git config costindi.highlight', 'r');
$highlight = trim(fgets($c, 128));
pclose($c);

$r = new TokenListDiff_Renderer_inline(array('enableSyntaxHighlighting' => ($highlight == 'true')));

$r->setColours(array(
	'ADD_FG'      => array(0, 150, 0),                 // green background
	'ADD_BG'      => null,
	'ADD_ATTRIBS' => array(ConsoleColour::ATTR_BOLD),
	'DEL_FG'      => array(150, 0, 0),                 // red foreground
	'DEL_BG'      => null,
	'DEL_ATTRIBS' => array(ConsoleColour::ATTR_BOLD),
));

echo basename(__FILE__) . ' a/' . $newfile . ' b/' . $newfile . PHP_EOL;
echo $r->getColour('DEL') . '---' . ConsoleColour::reset() . ' a/' . $newfile . PHP_EOL;
echo $r->getColour('ADD') . '+++' . ConsoleColour::reset() . ' b/' . $newfile . PHP_EOL;

echo $r->render($d) . PHP_EOL;
