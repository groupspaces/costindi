<?php

/*
 * An awesome class
 */
class MyClass
{
	function __construct($a)
	{
		if ($b) {
			exit;
		}
?>
	<p>Destroying whitespace...</p>
	<p>IN PROGRESS</p>
<?php
	}

	/**
	 * Function for drinking
	 */
	public static function bar($quux)
	{
		if ($quux > 3) {
			blah();
		} else {
			destroy_all_whitespace();
		}
?><p>Whitespace destroyed</p><?php
	}

	protected function lies()
	{
		return false;
	}

	public function test()
	{
		$a = 5;
		$b = 7;
		$this->quux = "hello";
		$c = 'fred';
		$d = "hello $c";
		$e = true;
		$f = FALSE;
		$g = null;
	}
}

