<?php

/**
 * Display a tip block
 *
 * @package application/helper/smarty
 * @author Integry Systems
 *
 * @package application/helper/smarty
 */
function smarty_block_tip($params, $content, Smarty_Internal_Template $smarty, &$repeat)
{
	if (!$repeat)
	{
		$smarty->assign('tipContent', $content);
		return $smarty->display('block/backend/tip.tpl');
	}
}

?>