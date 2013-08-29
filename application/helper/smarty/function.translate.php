<?php

/**
 * Translates interface text to current locale language
 *
 * @param array $params
 * @param Smarty $smarty
 * @return string
 *
 * @package application/helper/smarty
 * @author Integry Systems
 */
function smarty_function_translate($params, Smarty_Internal_Template $smarty)
{
	$application = $smarty->getApplication();

	$key = trim($params['text']);
	$translation = $application->translate($key);
	$translation = preg_replace('/%([a-zA-Z]*)/e', 'smarty_replace_translation_var(\'\\1\', $smarty)', $translation);

	if (!empty($params['noDefault']) && ($translation == $key))
	{
		return '';
	}

	if (!empty($params['eval']))
	{
		$translation = $smarty->evalTpl($translation);
	}

	if ($application->isTranslationMode() && !isset($params['disableLiveTranslation']) && !$application->isBackend())
	{
		$file = $application->getLocale()->translationManager()->getFileByDefKey($params['text']);
		$file = '__file_'.base64_encode($file);
		$translation = '<span class="transMode __trans_' . $params['text'].' '. $file .'">'.$translation.'</span>';
	}

	return $translation;
}

function smarty_replace_translation_var($key, $smarty)
{
	return $smarty->getTemplateVars($key);
}

?>