<?php

/**
 * ...
 *
 * @param array $params
 * @param Smarty $smarty
 * @return string
 * 
 * @package application.helper
 */
function smarty_function_translate($params, Smarty $smarty) {

	$locale = Locale::getCurrentLocale();				
	return $locale->translate($params['text']);
	//return $params['text'];
}

?>