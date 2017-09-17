<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty mb_truncate modifier plugin
 *
 * Type:    modifier
 * Name:    mb_truncate
 * Purpose: get truncated string
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @param  integer
 * @param  string
 * @param  string
 * @return string
 */
function smarty_modifier_mb_truncate($string, $width = 0, $trimmarker = '...', $encoding = 'UTF-8')
{
	if (mb_strlen($string, $encoding) > $width) {
		$string = mb_substr($string, 0, $width, $encoding) . $trimmarker;
	}

    return $string;
}

?>
