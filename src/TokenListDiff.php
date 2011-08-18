<?php
require_once(dirname(__DIR__).'/lib/pear/Text/Diff.php');

/**
 * A class for obtaining the difference between two token lists.
 */
class TokenListDiff
{
	protected $tokens1;
	protected $tokens2;

	/**
	 * Constructor.
	 *
	 * Either zero or two arguments should be given, both of the same type. If
	 * two strings are given, both are treated as files, which are opened and
	 * tokenised. If two arrays are given, both are treated as lists of tokens
	 * obtained from the tokenizer.
	 */
	public function __construct($arg1 = null, $arg2 = null)
	{
		if (is_string($arg1) && is_string($arg2)) {
			$this->setFile1($arg1);
			$this->setFile2($arg2);
		} elseif (is_array($arg1) && is_array($arg2)) {
			$this->setTokens1($arg1);
			$this->setTokens2($arg2);
		} elseif ($arg1 !== null || $arg2 !== null) {
			throw new InvalidArgumentException('Expected: either zero or two arguments, both of the same type (string or array)');
		}
	}

	public function setTokens1(array $v)
	{
		$this->tokens1 = $v;
	}

	public function setTokens2(array $v)
	{
		$this->tokens2 = $v;
	}

	public function setString1($v)
	{
		$this->setTokens1(token_get_all($v));
	}

	public function setString2($v)
	{
		$this->setTokens2(token_get_all($v));
	}

	public function setFile1($v)
	{
		$this->setString1(file_get_contents($v));
	}

	public function setFile2($v)
	{
		$this->setString2(file_get_contents($v));
	}

	/**
	 * Convert token array into diffable format:
	 * - discard line numbers
	 * - convert DOC_COMMENT into COMMENT
	 * - explode multi-line tokens (eg comments, text) into multiple, single-line tokens
	 * - serialise each token (as some are arrays)
	 *
	 * @param array $tokens Array of input tokens to serialize
	 * @return array
	 */
	protected static function formatTokens(array $tokens)
	{
		$results = array();

		foreach ($tokens as $token) {
			if (is_array($token)) {
				// discard line number from the tokenizer, not useful for diff
				unset($token[2]);

				// we don't care about the difference between these
				if ($token[0] == T_DOC_COMMENT) {
					$token[0] = T_COMMENT;
				}

				if ($token[0] != T_WHITESPACE && strpos($token[1], "\n") !== false) {
					// multi-line, non-whitespace token, need to convert into multiple, single line tokens
					$split = explode("\n", $token[1]);
					$c = count($split);
					for ($i = 0; $i < $c; $i++) {
						if (preg_match("/^(\s+)(\S.*)/", $split[$i], $matches)) {
							// handle leading whitespace
							$results[] = serialize(array(T_WHITESPACE, $matches[1]));
							$results[] = serialize(array($token[0], $matches[2]));
						} else {
							$results[] = serialize(array($token[0], $split[$i]));
						}

						if ($i != $c - 1) {
							// restore newlines lost by explode()ing
							$results[] = serialize(array(T_WHITESPACE, "\n"));
						}
					}
				} else {
					// single line or whitespace token
					$results[] = serialize($token);
				}
			} else {
				// non-identified token (single char, eg braces)
				$results[] = serialize($token);
			}
		}

		return $results;
	}

	protected static function postProcessDiffAdd(array $final)
	{
		foreach ($final as $entry) {
			if (!is_array($entry) || $entry[0] != T_WHITESPACE) {
				return array(new Text_Diff_Op_add($final));
			}
		}

		return array(new Text_Diff_Op_copy($final));
	}

	protected static function postProcessDiffDelete($orig)
	{
		foreach ($orig as $entry) {
			if (!is_array($entry) || $entry[0] != T_WHITESPACE) {
				return array(new Text_Diff_Op_delete($orig));
			}
		}

		return array();
	}

	protected static function postProcessDiffChange($diff)
	{
		$results = array();

		if (is_array($diff->final[0]) && $diff->final[0][0] == T_WHITESPACE && is_array($diff->orig[0]) && $diff->orig[0][0] == T_WHITESPACE) {
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

				if (is_array($final) && $final[0] == T_WHITESPACE && is_array($orig) && $orig[0] == T_WHITESPACE) {
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

	public static function postprocessDiff(array $diffs)
	{
		$results = array();

		foreach ($diffs as $diff) {
			if (is_array($diff->orig)) {
				$diff->orig = array_map('unserialize', $diff->orig);
			}

			if (is_array($diff->final)) {
				$diff->final = array_map('unserialize', $diff->final);
			}

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
	 * @return array
	 */
	public function getDiff()
	{
		if (!is_array($this->tokens1) || !is_array($this->tokens2)) {
			throw new InvalidArgumentException('You need to set the inputs first');
		}
		$tokens1 = self::formatTokens($this->tokens1);
		$tokens2 = self::formatTokens($this->tokens2);

		$differ = new Text_Diff('native', array($tokens1, $tokens2));

		return self::postprocessDiff($differ->getDiff());
	}
}
