<?php

/**
 * Displays ActiveGrid table
 *
 * @param array $params
 * @param Smarty $smarty
 * @return string
 *
 * @package application/helper/smarty
 * @author Integry Systems
 */
function smarty_function_activeGrid($params, Smarty_Internal_Template $smarty)
{
	if (!isset($params['rowCount']) || !$params['rowCount'])
	{
		$params['rowCount'] = 15;
	}

	foreach ($params as $key => $value)
	{
		$smarty->assign($key, $value);
	}

	if (isset($params['filters']) && is_array($params['filters']))
	{
		$smarty->assign('filters', $params['filters']);
	}

	$smarty->assign('url', $smarty->getApplication()->getRouter()->createUrl(array('controller' => $params['controller'], 'action' => $params['action']), true));

	$smarty->assign('thisMonth', date('m'));
	$smarty->assign('lastMonth', date('Y-m', strtotime(date('m') . '/15 -1 month')));

	return $smarty->display('block/activeGrid/gridTable.tpl');
}

?>