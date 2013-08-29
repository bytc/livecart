<?php

/**
 * Smarty block plugin, for generating page menu item
 * This block must always be called in pageMenu block context
 *
 * @param array $params
 * @param Smarty $smarty
 * @param $repeat
 *
 * <code>
 *	{pageMenu id="menu"}
 *		{menuItem}
 *			{menuCaption}Click Me{/menuCaption}
 *			{menuAction}http://click.me.com{/menuAction}
 *		{/menuItem}
 *		{menuItem}
 *			{menuCaption}Another menu item{/menuCaption}
 *			{pageAction}alert('Somebody clicked on me too!'){/menuAction}
 *		{/menuItem}
 *  {/pageMenu}
 * </code>
 *
 * @package application/helper/smarty
 * @author Integry Systems
 */
function smarty_block_menuItem($params, $content, Smarty_Internal_Template $smarty, &$repeat)
{
	if ($repeat)
	{
		$smarty->clear_assign('menuCaption');
		$smarty->clear_assign('menuAction');
		$smarty->clear_assign('menuPageAction');
	}
	else
	{
		$item = new HtmlElement('a');
		if ($smarty->getTemplateVars('menuAction'))
		{
		  	$href = $smarty->getTemplateVars('menuAction');
		}
		else if ($smarty->getTemplateVars('menuPageAction'))
		{
		  	$onClick = $smarty->getTemplateVars('menuPageAction');
		  	$href = '#';
			$item->setAttribute('onClick', $onClick  . '; return false;');
		}

		$item->setAttribute('href', $href);

		// EXPERIMENTAL - set access key for menu item
		$caption = $smarty->getTemplateVars('menuCaption');
		if (FALSE != strpos($caption, '&&'))
		{
		  	$p = strpos($caption, '&&');
		  	$accessKey = substr($caption, $p + 2, 1);
		  	$item->setAttribute('accessKey', $accessKey);
		  	$caption = substr($caption, 0, $p + 3) . '</span>' . substr($caption, $p + 3);
		  	$caption = substr($caption, 0, $p) . '<span class="accessKey">' . substr($caption, $p + 2);
		}

		$item->setContent($caption);

		$smarty->append('pageMenuItems', $item->render());
	}
}
?>