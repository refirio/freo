<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty nl2p modifier plugin
 *
 * Type:    modifier
 * Name:    nl2p
 * Purpose: convert \r\n, \r or \n to <<br>>, <<p>>
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @return string
 */
function smarty_modifier_nl2p($string)
{
	$string = preg_replace("/\r?\n/", "\r", $string);
	$string = preg_replace("/\r/", "\n", $string);

	$lines  = preg_split('/\n{2,}/', $string);
	$string = '';

	foreach ($lines as $line) {
		$string .= "<p>" . nl2br($line) . "</p>\n";
	}

    return $string;
}

?>
