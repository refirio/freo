<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty autolink modifier plugin
 *
 * Type:    modifier
 * Name:    autolink
 * Purpose: create link to the url of the text
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @return string
 */
function smarty_modifier_autolink($string)
{
	$string = preg_replace('/(^|[^\"\w\.\~\-\/\?\&\#\+\=\:\;\@\%\!])(https?\:\/\/[\w\.\~\-\/\?\&\#\+\=\:\;\@\%\!]+)/', '$1<a href="$2">$2</a>', $string);
	$string = preg_replace('/(^|[^\"\w\.\~\-\/\?\&\#\+\=\:\;\@\%\!])([\w\.\+\-]+@[\w\.\+\-]+)/', '$1<a href="mailto:$2">$2</a>', $string);

    return $string;
}

?>
