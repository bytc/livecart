{capture assign="body"}
	[[ partial("product/head.tpl") ]]
{/capture}

{capture assign="footer"}
	{block PRODUCT-NAVIGATION}
{/capture}

{include file="block/modal.tpl" title=$product.name_lang body=$body footer=$footer}