<?php

require_once(dirname(dirname(__DIR__)) . '/lib/pear/Text/Diff/Renderer.php');

/**
 * Custom inline diff renderer
 * - relies on TokenListDiff
 *
 * @author   Mark Schaschke (@epitomus)
 * @author   David Ingram   (@dmi)
 * @package  Costindo
 */
class TokenListDiff_Renderer_inline extends Text_Diff_Renderer
{
	/** @var  integer  Number of leading context "lines" to preserve. */
	public $_leading_context_lines = 10000;
	/** @var  integer  Number of trailing context "lines" to preserve. */
	public $_trailing_context_lines = 10000;

	protected $_colours = array(
		'RED' => "\033[1;31m",
		'GREEN' => "\033[1;32m",
	);
	protected $_reset = "\033[m";

	function _lines($final, $prefix = ' ', $encode = true)
	{
		$res = '';

		foreach ($final as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}
			$res .= $data;
		}

		return $res . '';
	}

	function _added($final)
	{
		$res = '';

		foreach ($final as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}
			$res .= $this->_colours['GREEN'] . $data . $this->_reset;
		}

		return $res/* . '+++|'*/;
	}

	function _deleted($orig, $words = false)
	{
		$res = ''/*'|---'*/;

		foreach ($orig as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}
			$res .= $this->_colours['RED'] . $data . $this->_reset;
		}

		return $res/* . '---|'*/;
	}

	function _changed($orig, $final)
	{
		return /*'|===' . */$this->_deleted($orig) . $this->_added($final)/* . '===|'*/;
	}
}
