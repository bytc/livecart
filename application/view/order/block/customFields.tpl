{% if 'CART_PAGE' == config('CHECKOUT_CUSTOM_FIELDS') %}
{sect}
	{header}
		<tr id="cartFields">
			<td colspan="{math equation="extraColspanSize + 5"}">
	{/header}
	{content}
			[[ partial('block/eav/fields.tpl', ['item': cart, 'filter': "isDisplayed"]) ]]
	{/content}
	{footer}
				<p>
					<label></label>
					<input type="submit" class="submit" value="{t _update}" name="saveFields" />
				</p>
			</td>
		</tr>
	{/footer}
{/sect}
{% endif %}
