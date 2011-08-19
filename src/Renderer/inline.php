<?php

require_once(dirname(dirname(__DIR__)) . '/lib/pear/Text/Diff/Renderer.php');
require_once(dirname(dirname(__DIR__)) . '/src/ConsoleColour.php');

use Costindi\ConsoleColour as ConsoleColour;

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
		'ADD_FG'    => null,
		'ADD_BG'    => array(0, 0x30, 0),
		'DEL_FG' => null,
		'DEL_BG' => array(0x30, 0, 0)
	);

	protected $colour = array('ADD' => null, 'DEL' => null);
	protected $reset = null;

	protected function getColour($index)
	{
		if (!$this->colour[$index]) {
			$this->colour[$index] = ConsoleColour::create($this->_colours[$index . '_FG'], $this->_colours[$index . '_BG']);
		}

		return $this->colour[$index];
	}

	protected function getReset()
	{
		if (!$this->reset) {
			$this->reset = ConsoleColour::reset();
		}

		return $this->reset;
	}

	public function _lines($final, $prefix = ' ', $encode = true)
	{
		$res = '';

		foreach ($final as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}
			$res .= $data;
		}

		return $res;
	}

	public function _processed($lines, $action)
	{
		$res = '';

		$colour = $this->getColour($action);
		$reset = $this->getReset();

		foreach ($lines as $data) {
			if (is_array($data)) {
				$data = $data[1];
			}

			$res .= $colour . str_replace("\n", $reset . "\n" . $colour, $data) . $reset;
		}

		return $res;
	}

	public function _added($final)
	{
		return $this->_processed($final, 'ADD');
	}

	public function _deleted($orig)
	{
		return $this->_processed($orig, 'DEL');
	}

	public function _changed($orig, $final)
	{
		return $this->_deleted($orig) . $this->_added($final);
	}
}
