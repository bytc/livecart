{foreach from=$shipment.items item="item" name="shipment"}
	<tr class="{zebra loop="shipment"}">
		<td class="productName">
			<a href="{productUrl product=$item.Product}">{$item.Product.name_lang}</a>
			{include file="user/itemOptions.tpl" options=$item.options}
		</td>
		<td>{$item.formattedDisplayPrice}</td>
		<td>{$item.count}</td>
		<td class="amount">{$item.formattedDisplaySubTotal}</td>
	</tr>
{/foreach}

{if $shipment.taxes && !$hideTaxes}
	<tr>
		<td colspan="3" class="subTotalCaption beforeTax">{t _subtotal_before_tax}:</td>
		<td class="amount">{$shipment.formatted_amount}</td>
	</tr>
{/if}

{if $order.isShippingRequired && $shipment.isShippable}
	<tr>
		<td colspan="3" class="subTotalCaption">
			{t _shipping} ({$shipment.ShippingService.name_lang}):
		</td>
		<td>
			{$shipment.selectedRate.formattedPrice[$order.Currency.ID]}
		</td>
	</tr>
{/if}