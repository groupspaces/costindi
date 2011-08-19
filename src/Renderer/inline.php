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

	/** @var  array    Colours to use to highlight adds and deletes */
	protected $_colours = array(
		'ADD_FG' => null,
		'ADD_BG' => array(0, 0x30, 0),  // green background
		'DEL_FG' => null,
		'DEL_BG' => array(0x30, 0, 0)   // red background
	);

	/** @var  array    Cache of generated console colours for adds and deletes */
	protected $colour = array('ADD' => null, 'DEL' => null);
	/** @var  array    Cache of generated console reset codes */
	protected $reset = null;

	/**
	 * Get the console colour for 'ADD' or 'DEL'
	 * - caches if needed
	 *
	 * @param   string  $index  ADD or DEL
	 * @return  Costindi\ConsoleColour
	 */
	protected function getColour($index)
	{
		if (!$this->colour[$index]) {
			$this->colour[$index] = ConsoleColour::create($this->_colours[$index . '_FG'], $this->_colours[$index . '_BG']);
		}

		return $this->colour[$index];
	}

	/**
	 * Get the console reset colour
	 * - caches if needed
	 *
	 * @return  Costindi\ConsoleColour
	 */
	protected function getReset()
	{
		if (!$this->reset) {
			$this->reset = ConsoleColour::reset();
		}

		return $this->reset;
	}

	/**
	 * Handle a copy diff
	 *
	 * @see Text_Diff_Renderer::_lines()
	 */
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

	/**
	 * Handle an add or delete diff
	 *
	 * @param   array   $lines  Tokens to display
	 * @param   string  $index  ADD or DEL
	 * @return  string
	 */
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

	/**
	 * (non-PHPdoc)
	 * @see Text_Diff_Renderer::_added()
	 */
	public function _added($final)
	{
		return $this->_processed($final, 'ADD');
	}

	/**
	 * (non-PHPdoc)
	 * @see Text_Diff_Renderer::_deleted()
	 */
	public function _deleted($orig)
	{
		return $this->_processed($orig, 'DEL');
	}

	/**
	 * (non-PHPdoc)
	 * @see Text_Diff_Renderer::_changed()
	 */
	public function _changed($orig, $final)
	{
		return $this->_deleted($orig) . $this->_added($final);
	}
}
