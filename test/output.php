<?php

require_once('../src/TokenListDiff.php');

/*
$f1 = file_get_contents('../test_data/1a.php');
$f2 = file_get_contents('../test_data/1b.php');
$arr1 = array();
$arr2 = array();

foreach (token_get_all($f1) as $token) {
	$arr1[] = serialize($token);
}

foreach (token_get_all($f2) as $token) {
	$arr2[] = serialize($token);
}

$d = new Text_Diff('auto', array($arr1, $arr2));
//$r = new Text_Diff_Renderer_inline();
//echo $r->render($d);
//
//exit;

*/
# first define colors to use

$_colours = array(
        'LIGHT_RED'      => "[1;31m",
        'LIGHT_GREEN'     => "[1;32m",
        'YELLOW'         => "[1;33m",
        'LIGHT_BLUE'     => "[1;34m",
        'MAGENTA'     => "[1;35m",
        'LIGHT_CYAN'     => "[1;36m",
        'WHITE'         => "[1;37m",
        'NORMAL'         => "[0m",
        'BLACK'         => "[0;30m",
        'RED'         => "[0;31m",
        'GREEN'         => "[0;32m",
        'BROWN'         => "[0;33m",
        'BLUE'         => "[0;34m",
        'CYAN'         => "[0;36m",
        'BOLD'         => "[1m",
        'UNDERSCORE'     => "[4m",
        'REVERSE'     => "[7m",
);

$_reset = chr(27) . $_colours['NORMAL'] . chr(27) . $_colours['WHITE'];

global $_colours, $_reset;

class Renderer {
	public static function copy($final) {
		$res = '';

		foreach ($final as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}
			$res .= $data;
		}

		return $res . '';
	}

	public static function change($diff) {
		return '<' . self::delete($diff->orig) . self::add($diff->final) . '>';
	}

	public static function add($final) {
		global $_colours, $_reset;

		$res = '{';

		foreach ($final as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}
			$res .= chr(27) . $_colours['GREEN'] . $data . $_reset;
		}

		return $res . '}';
	}

	public static function delete($orig) {
		global $_colours, $_reset;

		$res = '[';

		foreach ($orig as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}
			$res .= chr(27) . $_colours['RED'] . $data . $_reset;
		}

		return $res . ']';
	}
}

$d = new TokenListDiff(
	dirname(__DIR__).'/test_data/1a.php',
	dirname(__DIR__).'/test_data/1b.php'
);

foreach ($d->getDiff() as $diff) {
	$type = get_class($diff);

	switch ($type) {
		case 'Text_Diff_Op_copy':
			echo Renderer::copy($diff->final);
			break;
		case 'Text_Diff_Op_change':
			echo Renderer::change($diff);
			break;
		case 'Text_Diff_Op_add':
			echo Renderer::add($diff->final);
			break;
		case 'Text_Diff_Op_delete':
			echo Renderer::delete($diff->orig);
			break;
	}
}

