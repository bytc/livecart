{foreach $cart.taxes.$currency as $tax}
	<tr>
		<td colspan="{math equation="$extraColspanSize + 3"}" class="subTotalCaption">[[tax.name_lang]]:</td>
		<td class="amount taxAmount">[[tax.formattedAmount]]</td>
		{include file="order/block/cartUpdate.tpl"}
	</tr>
{/foreach}
