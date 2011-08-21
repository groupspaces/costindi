<?php

require_once(__DIR__.'/Token.php');
require_once(dirname(__DIR__).'/lib/pear/Text/Diff.php');

/**
 * A class for obtaining the difference between two token lists.
 *
 * @author   Mark Schaschke (@epitomus)
 * @author   David Ingram   (@dmi)
 * @package  Costindi
 */
class TokenListDiff
{
	/** @var  array  $tokensOrig  Original set of tokens */
	protected $tokensOrig = array();
	/** @var  array  $tokensFinal  Final set of tokens */
	protected $tokensFinal = array();

	/**
	 * Constructor.
	 *
	 * Either zero or two arguments should be given, both of the same type. If
	 * two strings are given, both are treated as files, which are opened and
	 * tokenised. If two arrays are given, both are treated as lists of tokens
	 * obtained from the tokenizer.
	 *
	 * @param  array|string  Original tokens or files
	 * @param  array|string  Final tokens or files
	 */
	public function __construct($arg1 = null, $arg2 = null)
	{
		if (is_string($arg1) && is_string($arg2)) {
			$this->setFileOrig($arg1);
			$this->setFileFinal($arg2);
		} elseif (is_array($arg1) && is_array($arg2)) {
			$this->setTokensOrig($arg1);
			$this->setTokensFinal($arg2);
		} elseif ($arg1 !== null || $arg2 !== null) {
			throw new InvalidArgumentException('Expected: either zero or two arguments, both of the same type (string or array)');
		}
	}

	/**
	 * Set the original tokens
	 *
	 * @param  array  $tokens  Tokens from the original source
	 */
	public function setTokensOrig(array $tokens)
	{
		$this->tokensOrig = $tokens;
	}

	/**
	 * Set the final tokens
	 *
	 * @param  array  $tokens  Tokens from the final source
	 */
	public function setTokensFinal(array $tokens)
	{
		$this->tokensFinal = $tokens;
	}

	/**
	 * Set the original tokens from a string
	 *
	 * @param  string  $str  String from the original source
	 */
	public function setStringOrig($str)
	{
		$this->setTokensOrig(token_get_all($str));
	}

	/**
	 * Set the final tokens from a string
	 *
	 * @param  string  $str  String from the final source
	 */
	public function setStringFinal($str)
	{
		$this->setTokensFinal(token_get_all($str));
	}

	/**
	 * Set the original tokens from file
	 *
	 * @param  string  $filename  Filename for the original source
	 */
	public function setFileOrig($filename)
	{
		$this->setStringOrig(file_get_contents($filename));
	}

	/**
	 * Set the final tokens from file
	 *
	 * @param  string  $filename  Filename for the final source
	 */
	public function setFileFinal($filename)
	{
		$this->setStringFinal(file_get_contents($filename));
	}

	/**
	 * Convert token array into diffable format:
	 * - discard line numbers
	 * - convert DOC_COMMENT into COMMENT
	 * - explode multi-line tokens (eg comments, text) into multiple, single-line tokens
	 * - serialise each token (as some are arrays)
	 *
	 * @param   array  $tokens  Array of input tokens to serialize
	 * @return  array  Text_Diff_Op objects
	 */
	protected static function formatTokens(array $tokens)
	{
		$results = array();

		foreach ($tokens as $tok) {
			$token = new Token($tok);
			$content = $token->getContent();
			$type = $token->getType();

			if (is_array($tok)) {
				$currentLine = $tok[2];
			} else {
				$token->setLineNumber($currentLine);
			}

			if ($type == T_DOC_COMMENT) {
				// we don't care about the difference between these
				$token->setType(T_COMMENT);
			}

			if ($type != T_WHITESPACE && strpos($content, "\n") !== false) {
				// multi-line, non-whitespace token, need to convert into multiple, single line tokens
				$split = explode("\n", $content);

				$c = count($split);
				for ($i = 0; $i < $c; $i++) {
					if (preg_match("/^(\s+)(\S.*)/", $split[$i], $matches)) {
						// handle leading whitespace
						$results[] = new Token(array(T_WHITESPACE, $matches[1], $currentLine + $i));
						$results[] = new Token(array($type, $matches[2], $currentLine + $i));
					} elseif ($split[$i] !== '') {
						$results[] = new Token(array($type, $split[$i], $currentLine + $i));
					}

					if ($i != $c - 1) {
						// restore newlines lost by explode()ing
						$results[] = new Token(array(T_WHITESPACE, "\n", $current));
					}
				}
			} else {
				$results[] = $token;
			}
		}

		return $results;
	}

	/**
	 * Process a collection of diff adds
	 * - if they are all white-space changes we translate them to a copy
	 * - otherwise leave unchanged as an add
	 *
	 * @param   array  $final  Array of input tokens to process
	 * @return  array  Text_Diff_Op objects
	 */
	protected static function postProcessDiffAdd(array $final)
	{
		foreach ($final as $entry) {
			if ($entry->getType() != T_WHITESPACE) {
				// found a non-whitespace add, can't translate
				return array(new Text_Diff_Op_add($final));
			}
		}

		// all whitespace adds, make it a copy
		return array(new Text_Diff_Op_copy($final));
	}

	/**
	 * Process a collection of diff deletes
	 * - if they are all white-space changes we throw them away
	 * - otherwise leave unchanged as a delete
	 *
	 * @param   array  $orig  Array of input tokens to process
	 * @return  array of Text_Diff_Op objects
	 */
	protected static function postProcessDiffDelete(array $orig)
	{
		foreach ($orig as $entry) {
			if ($entry->getType() != T_WHITESPACE) {
				// found a non-whitespace delete, can't translate
				return array(new Text_Diff_Op_delete($orig));
			}
		}

		// all whitespace deletes, discard
		return array();
	}

	/**
	 * Process a collection of diff changes
	 * - use 2 accumulators, one for pre-change (orig), one for post-change (final)
	 * - incoming and outgoing changes can be different lengths (eg 2 adds, 3 deletes)
	 * - interleave adds and deletes while we have both
	 * - handle outstanding accumulated changes when we run out of matching incoming and outgoing changes
	 * - handle outstanding trailing changes in either orig or final
	 *
	 * @param   Text_Diff_Op_change  $orig
	 * @return  array  Text_Diff_Op objects
	 */
	protected static function postProcessDiffChange(Text_Diff_Op_change $diff)
	{
		$results = array();

		// determine initial context

		if ($diff->final[0]->getType() == T_WHITESPACE && $diff->orig[0]->getType() == T_WHITESPACE) {
			$context = 'whitespace';
		} else {
			$context = 'nonwhitespace';
		}

		$origAccumulator = array();
		$finalAccumulator = array();

		$iter = max(count($diff->final), count($diff->orig));

		for ($i = 0; $i < $iter; $i++) {
			if (isset($diff->final[$i]) && isset($diff->orig[$i])) {
				$orig = $diff->orig[$i];
				$final = $diff->final[$i];

				if ($final->getType() == T_WHITESPACE && $orig->getType() == T_WHITESPACE) {
					if ($context != 'whitespace') {
						// accumulated non-whitespace, keep the change
						$results[] =  new Text_Diff_Op_change($origAccumulator, $finalAccumulator);

						$origAccumulator = array();
						$finalAccumulator = array();

						$context = 'whitespace';
					}

					$origAccumulator[] = $orig;
					$finalAccumulator[] = $final;
				} else {
					if ($context != 'nonwhitespace') {
						// accumulated whitespace changes, convert to a copy of the final only
						$results[] =  new Text_Diff_Op_copy($finalAccumulator);
						$origAccumulator = array();
						$finalAccumulator = array();

						$context = 'nonwhitespace';
					}

					$origAccumulator[] = $orig;
					$finalAccumulator[] = $final;
				}
			} else {
				// final and orig arrays are not equal length, deal with them outside the loop
				break;
			}
		}

		// doesn't matter which accumulator we check, they are in sync
		if (!empty($origAccumulator)) {
			if ($context == 'whitespace') {
				// accumulated whitespace changes, convert to a copy of the final only
				$results[] =  new Text_Diff_Op_copy($finalAccumulator);
			} else {
				// accumulated non-whitespace, keep the change
				$results[] =  new Text_Diff_Op_change($origAccumulator, $finalAccumulator);
			}
		}

		// process any trailing final or orig entries and convert to simple adds or deletes
		if (isset($diff->final[$i])) {
			$results[] = new Text_Diff_Op_add(array_slice($diff->final, $i));
		} elseif (isset($diff->orig[$i])) {
			$results[] = new Text_Diff_Op_delete(array_slice($diff->orig, $i));
		}

		return $results;
	}

	/**
	 * Process an array of diffs in a white-space and code-style agnostic way
	 * - unserialises the diffed tokens
	 *
	 * @param   array $diffs  Text_Diff_Op objects
	 * @return  array         Text_Diff_Op objects
	 */
	public static function postprocessDiff(array $diffs)
	{
		$results = array();

		foreach ($diffs as $diff) {
			switch (get_class($diff)) {
				case 'Text_Diff_Op_copy':
					$results[] = $diff;
					break;
				case 'Text_Diff_Op_add':
					$results = array_merge($results, self::postProcessDiffAdd($diff->final));
					break;
				case 'Text_Diff_Op_delete':
					$results = array_merge($results, self::postProcessDiffDelete($diff->orig));
					break;
				case 'Text_Diff_Op_change':
					$results = array_merge($results, self::postProcessDiffChange($diff));
					break;
			}
		}

		return $results;
	}

	/**
	 * Generate the difference between the token arrays, and return an array of
	 * Text_Diff_Op_* objects
	 *
	 * @return  array  Text_Diff_Op objects
	 */
	public function getDiff()
	{
		if (!is_array($this->tokensOrig) || !is_array($this->tokensFinal)) {
			throw new InvalidArgumentException('You need to set the inputs first');
		}
		$tokensOrig = self::formatTokens($this->tokensOrig);
		$tokensFinal = self::formatTokens($this->tokensFinal);

		$differ = new Text_Diff('native', array($tokensOrig, $tokensFinal));

		return self::postprocessDiff($differ->getDiff());
	}
}
