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

	/** @var  array	Colours to use to highlight adds and deletes */
	protected $_colours = array(
		'ADD_FG' => null,
		'ADD_BG' => array(0, 0x30, 0),  // green background
		'DEL_FG' => null,
		'DEL_BG' => array(0x30, 0, 0)   // red background
	);

	/** @var  array	Cache of generated console colours for adds and deletes */
	protected $colour = array('ADD' => null, 'DEL' => null);
	/** @var  array	Cache of generated console reset codes */
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
	 * We don't indicate same lines, no need for indenting, so simply a wrapper around __lines()
	 *
	 * @see Text_Diff_Renderer::_context()
	 */
	public function _context($lines)
	{
		return $this->_lines($lines, '');
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

	/**
	 * Renders a diff
	 *
	 * @param   Text_Diff  $diff  A Text_Diff object.
	 * @return  string     The formatted output.
	 */
	function render($diff)
	{
		$xi = $yi = 1;
		$block = false;
		$context = array();

		$nlead = $this->_leading_context_lines;
		$ntrail = $this->_trailing_context_lines;

		$output = $this->_startDiff();

		$diffs = $diff->getDiff();
		foreach ($diffs as $i => $edit) {
			/* If these are unchanged (copied) lines, and we want to keep
			 * leading or trailing context lines, extract them from the copy
			 * block. */
			if (is_a($edit, 'Text_Diff_Op_copy')) {
				/* Do we have any diff blocks yet? */
				if (is_array($block)) {
					/* How many lines to keep as context from the copy
					 * block. */
					$keep = $i == count($diffs) - 1 ? $ntrail : $nlead + $ntrail;
					if (count($edit->orig) <= $keep) {
						/* We have less lines in the block than we want for
						 * context => keep the whole block. */
						$block[] = $edit;
					} else {
						if ($ntrail) {
							/* Create a new block with as many lines as we need
							 * for the trailing context. */
							$context = array_slice($edit->orig, 0, $ntrail);
							$block[] = new Text_Diff_Op_copy($context);
						}
						/* @todo */
						$output .= $this->_block($x0, $ntrail + $xi - $x0,
												 $y0, $ntrail + $yi - $y0,
												 $block);
						$block = false;
					}
				}
				/* Keep the copy block as the context for the next block. */
				$context = $edit->orig;
			} else {
				/* Don't we have any diff blocks yet? */
				if (!is_array($block)) {
					/* Extract context lines from the preceding copy block. */
					$context = array_slice($context, count($context) - $nlead);
					$x0 = $xi - count($context);
					$y0 = $yi - count($context);
					$block = array();
					if ($context) {
						$block[] = new Text_Diff_Op_copy($context);
					}
				}
				$block[] = $edit;
			}

			if ($edit->orig) {
				$xi += count($edit->orig);
			}
			if ($edit->final) {
				$yi += count($edit->final);
			}
		}

		if (is_array($block)) {
			$output .= $this->_block($x0, $xi - $x0,
									 $y0, $yi - $y0,
									 $block);
		}

		return $output . $this->_endDiff();
	}
}
