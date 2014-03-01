{% if config('SHOW_SKU_EMAIL') %}{% set SHOW_SKU = true %}{% endif %}
{% if empty(html) %}
{foreach from=shipment.items item=item}

{% if !empty(SHOW_SKU) %}{item.Product.sku|@str_pad_iconv:10}{% endif %}{item.Product.name()|truncate:29:"...":"true"|@str_pad_iconv:31}{item.formattedDisplayPrice|truncate:9:"..."|@str_pad:10}{item.count|truncate:8:"..."|@str_pad:9}[[item.formattedDisplaySubTotal]]
{% if item.options %}
{foreach from=item.options item=option}
[[option.Choice.Option.name()]]: {% if 0 == option.Choice.Option.type %}{t _option_yes}{% elseif 1 == option.Choice.Option.type %}[[option.Choice.name()]]{% else %}{option.optionText|@htmlspecialchars}{% endif %} {% if option.priceDiff != 0 %}([[option.formattedPrice]]){% endif %}

{% endfor %}

{% endif %}
{% endfor %}
{% endif %}{*html*}
{% if !empty(html) %}

{foreach from=shipment.items item=item}
<tr>
	{% if !empty(SHOW_SKU) %}
	<td>
		{item.Product.sku|escape}
	</td>
	{% endif %}
	<td>
		[[item.Product.name()]]
		{% if item.options %}
			{foreach from=item.options item=option}
				<small>[[option.Choice.Option.name()]]: {% if 0 == option.Choice.Option.type %}{t _option_yes}{% elseif 1 == option.Choice.Option.type %}[[option.Choice.name()]]{% else %}{option.optionText|@htmlspecialchars}{% endif %} {% if option.priceDiff != 0 %}([[option.formattedPrice]]){% endif %}</small><br />
			{% endfor %}
		{% endif %}
	</td>
	<td align="center">[[item.formattedDisplayPrice]]</td>
	<td align="center">[[item.count]]</td>
	<td align="right">[[item.formattedDisplaySubTotal]]</td>
</tr>
{% endfor %}
{% if shipment.isShippable && shipment.ShippingService && (shipment.CustomerOrder.shipments|@count > 1) %}
	[[ partial("email/blockShippingCost.tpl") ]]
{% endif %}
{% if empty(noTable) %}</table>{% endif %}
{% endif %}{*html*}