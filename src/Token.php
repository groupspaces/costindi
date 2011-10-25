<?php

/**
 * @author   Mark Schaschke (@epitomus)
 * @package  Costindi
 */

/** global constant to complement the tokens, means a bare character, eg {}, [] */
define('T_RAW', -1);

/**
 * Represent a single token from the PHP tokeniser
 */
class Token {
	/** @var  Type of token */
	protected $type = null;
	/** @var  Token content */
	protected $content = '';
	/** @var  Line number for the token */
	protected $lineNumber = 0;

	/**
	 * Create a token object from a single element from token_get_all()
	 * - if it's not an array, map to T_RAW
	 *
	 * @param  array|string  $token  Single result from token_get_all()
	 */
	public function __construct($token)
	{
		if (is_array($token)) {
			$this->type = $token[0];
			$this->content = $token[1];
			$this->lineNumber = $token[2];
		} else {
			$this->type = T_RAW;
			$this->content = $token;
			// don't know what the line number is, have to set it manually in the
			// calling scope
		}
	}

	/**
	 * Set the line number
	 *
	 * @param  int  $lineNumber  Line number on which the token was found
	 */
	public function setLineNumber($lineNumber)
	{
		$this->lineNumber = $lineNumber;

		return $this;
	}

	/**
	 * Get the line number for this token
	 *
	 * @return  int
	 */
	public function getLineNumber()
	{
		return $this->lineNumber;
	}

	/**
	 * Set the content
	 *
	 * @param  string  $content  Content of the token (PHP source code)
	 */
	public function setContent($content)
	{
		$this->content = $content;

		return $this;
	}

	/**
	 * Get the content for this token
	 *
	 * @return  string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * Set the type
	 *
	 * @param  int  $type  Set the type of the token
	 */
	public function setType($type)
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * Get the type for this token
	 *
	 * @return  int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Convenience function so we can use a token in the differ
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->content;
	}
}
