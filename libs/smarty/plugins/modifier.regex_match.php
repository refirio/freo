<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage plugins
 */

/**
 * Smarty regex_match modifier plugin
 *
 * Type:    modifier
 * Name:    regex_match
 * Purpose: regular expression search/match
 *
 * @link   http://freo.jp/
 * @author Knight <info at favorite-labo dot org>
 * @param  string
 * @param  string|array
 * @return boolean
 */
function smarty_modifier_regex_match($string, $search)
{
	if (is_array($search)) {
		foreach ($search as $idx => $s) {
			$search[$idx] = _smarty_regex_match_check($s);
		}
	} else {
		$search = _smarty_regex_match_check($search);
	}

	return preg_match($search, $string);
}

function _smarty_regex_match_check($search)
{
	if (($pos = strpos($search,"\0")) !== false) {
		$search = substr($search,0,$pos);
	}
	if (preg_match('!([a-zA-Z\s]+)$!s', $search, $match) && (strpos($match[1], 'e') !== false)) {
		//remove eval-modifier from $search
		$search = substr($search, 0, -strlen($match[1])) . preg_match('![e\s]+!', '', $match[1]);
	}

	return $search;
}

?>
