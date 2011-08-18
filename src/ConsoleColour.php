<?php

namespace Costindi;

/**
 * Thanks:
 * - http://lucentbeing.com/blog/that-256-color-thing/
 *
 * @author  Mark Schaschke
 * @package Costindi
 *
 */

class ConsoleColour
{
	const ATTR_RESET        = 0;
	const ATTR_BOLD         = 1;
	const ATTR_ITALIC       = 3;
	const ATTR_UNDERLINE    = 4;
	const ATTR_BLINK        = 5;
	const ATTR_REVERSE      = 7;
	const ATTR_NO_BOLD      = 22;
	const ATTR_NO_ITALIC    = 23;
	const ATTR_NO_UNDERLINK = 24;
	const ATTR_NO_BLINK     = 25;
	const ATTR_NO_REVERSE   = 27;

	const CLR_BLACK         = 0;
	const CLR_RED           = 1;
	const CLR_GREEN         = 2;
	const CLR_BROWN         = 3;
	const CLR_BLUE          = 4;
	const CLR_MAGENTA       = 5;
	const CLR_CYAN          = 6;
	const CLR_LIGHT_GREY    = 7;
	const CLR_DARK_GREY     = 8;
	const CLR_LIGHT_RED     = 9;
	const CLR_LIGHT_GREEN   = 10;
	const CLR_YELLOW        = 11;
	const CLR_LIGHT_BLUE    = 12;
	const CLR_LIGHT_MAGENTA = 13;
	const CLR_LIGHT_CYAN    = 14;
	const CLR_WHITE         = 15;

	protected $attributes   = array();
	protected $foreground   = null;
	protected $background   = null;

	public function __construct($foreground = null, $background = null, $attributes = array())
	{
		if (is_array($foreground)) {
			$foreground = self::mapRGBColour($foreground);
		}

		if (is_array($background)) {
			$background = self::mapRGBColour($background);
		}

		$this->foreground = $foreground;
		$this->background = $background;
		$this->attributes = is_array($attributes) ? $attributes : array($attributes);
	}

	public static function mapRgbColour(array $colour)
	{
		list($red, $green, $blue) = array_map(function($v) {return intval($v / 51.2);}, $colour);

		if ($red == $green && $green == $blue) {
			// grayscale, map to 24 specific grayscale colours
			list($red, $green, $blue) = array_map(function($v) {return intval($v / 10.66);}, $colour);
			$level = intval(($red + $green + $blue) / 3);

			return $level + 232;
		}

		return $red * 36 + $green * 6 + $blue + 16;
	}

	public static function create($foreground = null, $background = null, $attributes = array())
	{
		return new ConsoleColour($foreground, $background, $attributes);
	}

	public static function reset()
	{
		return self::create(null, null, self::ATTR_RESET);
	}

	public function getCode()
	{
		$data = $this->attributes;
		if (!is_null($this->foreground)) {
			$data = array_merge($data, array(38, 5, $this->foreground));
		}

		if (!is_null($this->background)) {
			$data = array_merge($data, array(48, 5, $this->background));
		}

		if (!empty($data)) {
			return "\033[" . implode(';', $data) . 'm';
		}

		return '';
	}

	public function __toString()
	{
		return $this->getCode();
	}
}
