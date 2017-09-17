<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty explode modifier plugin
 *
 * Type:    modifier
 * Name:    mb_truncate
 * Purpose: split a string by string
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @param  string
 * @param  integer
 * @return string
 */
function smarty_modifier_explode($string, $delimiter, $limit = null)
{
	if ($limit) {
	    return explode($delimiter, $string, $limit);
	} else {
	    return explode($delimiter, $string);
	}
}

?>
