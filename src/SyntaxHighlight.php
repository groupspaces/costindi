<?php

namespace Costindi;

require_once(__DIR__ . '/ConsoleColour.php');

/**
 * @author  Mark Schaschke
 * @package Costindi
 *
 */

class SyntaxHighlight
{
	public static $HIGHLIGHTS = array(
		'KEYWORD' => array(
			'members' => array(
				T_ABSTRACT,
				T_ARRAY,
				T_BREAK,
				T_CASE,
				T_CATCH,
				T_CLASS,
				T_CLONE,
				T_CONST,
				T_CONTINUE,
				T_DECLARE,
				T_DEFAULT,
				T_DO,
				T_ECHO,
				T_ELSE,
				T_ELSEIF,
				T_EMPTY,
				T_ENDDECLARE,
				T_ENDFOR,
				T_ENDFOREACH,
				T_ENDIF,
				T_ENDSWITCH,
				T_ENDWHILE,
				T_EVAL,
				T_EXIT,
				T_EXTENDS,
				T_FINAL,
				T_FOR,
				T_FOREACH,
				T_FUNCTION,
				T_GLOBAL,
				T_GOTO,
				T_IF,
				T_IMPLEMENTS,
				T_INCLUDE,
				T_INCLUDE_ONCE,
				T_INSTANCEOF,
				T_INTERFACE,
				T_ISSET,
				T_LIST,
				T_NAMESPACE,
				T_NEW,
				T_PRINT,
				T_PRIVATE,
				T_PUBLIC,
				T_PROTECTED,
				T_REQUIRE,
				T_REQUIRE_ONCE,
				T_RETURN,
				T_STATIC,
				T_SWITCH,
				T_THROW,
				T_TRY,
				T_UNSET,
				T_USE,
				T_VAR,
				T_WHILE
			),
			'foreground' => 222,
			'background' => null,
			'attributes' => array()
		),
		'VARIABLE' => array(
			'members' => array(
				T_ENCAPSED_AND_WHITESPACE,
				T_STRING_VARNAME,
				T_VARIABLE
			),
			'foreground' => 226,
			'background' => null,
			'attributes' => array()
		),
		'BLOCK' => array(
			'members' => array(
				'{',
				'}',
				T_OPEN_TAG
			),
			'foreground' => 222,
			'background' => null,
			'attributes' => array()
		)
	);
}
