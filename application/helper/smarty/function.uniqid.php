<?php

/**
 * ...
 *
 * @param array $params
 * @param Smarty $smarty
 * @return string
 *
 * @package application/helper/smarty
 * @author Integry Systems
 */
function smarty_function_uniqid($params, Smarty_Internal_Template $smarty)
{
	if (isset($params['last']))
	{
		return $smarty->getTemplateVars('lastUniqId');
	}
	else
	{
		// start with a letter for XHTML id attribute value compatibility
		$id = 'a' . uniqid();
		$smarty->assign('lastUniqId', $id);

		if (isset($params['assign']))
		{
			$smarty->assign($params['assign'], $id);

			if (!empty($params['noecho']))
			{
				return '';
			}
		}

		return $id;
	}
}

?>