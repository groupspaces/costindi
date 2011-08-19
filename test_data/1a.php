<?php

/*
 * some class
 */
class MyClass {
	function __construct($a) {
		if ($b) {
			exit;
		}
?>
<p>Destroying whitespace...</p>
	<p>IN PORGRESS</p>
<?php
	/**
	 * some big comment
	 * with stuff in it
	 */
	}

	/*
	 * Function for drinking
	 */
	public static function bar($foo)
	{
		if($foo>3)
		{
			blah();
		}
		else {
			destroy_all_whitespace();
		}
?><p>Whitespace destroyed</p><?php
	}

	public function test()
	{
		$a = 5;
		$b = 7;
		$this->foo = "hello";
		$c = 'fred';
		$d = "hello $c";
		$e = true;
		$f = FALSE;
		$g = null;
	}
}
