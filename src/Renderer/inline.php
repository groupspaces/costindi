<?php

require_once(dirname(dirname(__DIR__)) . '/lib/pear/Text/Diff/Renderer.php');
require_once(dirname(dirname(__DIR__)) . '/src/ConsoleColour.php');
require_once(dirname(dirname(__DIR__)) . '/src/SyntaxHighlight.php');

use Costindi\ConsoleColour as ConsoleColour;
use Costindi\SyntaxHighlight as SyntaxHighlight;

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

	/** @var  array	   Colours to use to highlight adds and deletes */
	public $_colours = array(
		'ADD_FG' => 255,
		'ADD_BG' => 22,   // green background
		'ADD_ATTRIBS' => array(ConsoleColour::ATTR_BOLD),
		'DEL_FG' => 255,
		'DEL_BG' => 52,   // red background
		'DEL_ATTRIBS' => array(ConsoleColour::ATTR_BOLD),
	);

	/** @var  array	   Cache of generated console colours for adds and deletes */
	protected $colour = array('ADD' => null, 'DEL' => null);
	/** @var  array    Cache of generated console reset codes */
	protected $reset = null;
	/** @var  boolean  Whether to syntax highlight */
	protected $_enableSyntaxHighlighting = false;

	/**
	 * Get the console colour for 'ADD' or 'DEL'
	 * - caches if needed
	 *
	 * @param   string  $index  ADD or DEL
	 * @return  Costindi\ConsoleColour
	 */
	public function getColour($index)
	{
		if (!$this->colour[$index]) {
			$this->colour[$index] = ConsoleColour::create($this->_colours[$index . '_FG'], $this->_colours[$index . '_BG'], $this->_colours[$index . '_ATTRIBS']);
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

		foreach ($final as $token) {
			$res .= self::syntaxHighlight($token);
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

		foreach ($lines as $token) {
			$res .= $colour . str_replace("\n", $reset . "\n" . $colour, $token->getContent()) . $reset;
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

	protected function syntaxHighlight($token)
	{
		if ($this->_enableSyntaxHighlighting) {
			foreach (SyntaxHighlight::$HIGHLIGHTS as $highlight) {
				if (in_array($token->getType(), $highlight['members']) || in_array($token->getContent(), $highlight['members'])) {
					return ConsoleColour::create($highlight['foreground'], $highlight['background']) . $token->getContent() . ConsoleColour::reset();
				}
			}
		}

		return $token->getContent();
	}

	/**
	 * Renders a diff
	 *
	 * @param   Text_Diff  $diff  A Text_Diff object
	 * @return  string	   The formatted output
	 */
	function render($diff)
	{
		$output = '';

		foreach ($diff->getDiff() as $edit) {
			switch (strtolower(get_class($edit))) {
			case 'text_diff_op_copy':
				$output .= $this->_lines($edit->final);
				break;

			case 'text_diff_op_add':
				$output .= $this->_added($edit->final);
				break;

			case 'text_diff_op_delete':
				$output .= $this->_deleted($edit->orig);
				break;

			case 'text_diff_op_change':
				$output .= $this->_changed($edit->orig, $edit->final);
				break;
			}
		}

		return $output;
	}
}
