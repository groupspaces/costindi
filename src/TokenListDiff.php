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

	protected static function formatTokens(array $tokens)
	{
		$results = array();

		foreach ($tokens as $token) {
			if (is_array($token)) {
				unset($token[2]);
			}

			$results[] = serialize($token);
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
		return $differ->getDiff();
	}

}
