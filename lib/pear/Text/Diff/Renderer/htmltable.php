<?php
/**
 * HTML table diff renderer.
 *
 * This class renders the diff in an HTML table format.
 *
 * $Horde: framework/Text_Diff/Diff/Renderer/htmltable.php,v 1.3.10.7 2009/01/06 15:23:42 jan Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author  Dave Ingram
 * @package Text_Diff
 */

/** Text_Diff_Renderer */
require_once 'Text/Diff/Renderer.php';

/**
 * @package Text_Diff
 */
class Text_Diff_Renderer_htmltable extends Text_Diff_Renderer {

    /**
     * Number of leading context "lines" to preserve.
     */
    var $_leading_context_lines = 4;

    /**
     * Number of trailing context "lines" to preserve.
     */
    var $_trailing_context_lines = 4;

    function _startDiff()
    {
        return "<table class=\"diff\">\n";
    }

    function _endDiff()
    {
        return "</table>\n";
    }

    function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen != 1) {
            $xbeg .= ',' . $xlen;
        }
        if ($ylen != 1) {
            $ybeg .= ',' . $ylen;
        }
        return '<tr class="header"><th colspan="2">@@ -'.$xbeg.' +'.$ybeg." @@</td></tr>\n";
    }

    function _context($lines)
    {
        return $this->_lines($lines, ' ');
    }

    function _added($lines)
    {
        return $this->_lines($lines, '+');
    }

    function _deleted($lines)
    {
        return $this->_lines($lines, '-');
    }

    function _lines($lines, $prefix = ' ')
    {
        $class = '';
        switch ($prefix) {
            case ' ':
                $prefix = '&nbsp;';
                $class = 'context';
                break;
            case '+':
                $class = 'added';
                break;
            case '-':
                $class = 'removed';
                break;
        }
        $return = '';
        foreach ($lines as $line) {
            $return .= '<tr class="'.$class.'"><th width="15">'.$prefix.'</th><td>'.str::htmlSafe($line)."</td></tr>\n";
        }
        return $return;
    }

    function _changed($orig, $final)
    {
        return $this->_deleted($orig) . $this->_added($final);
    }

}
