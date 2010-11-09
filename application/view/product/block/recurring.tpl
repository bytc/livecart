{if 'DISPLAY_PRICES'|config && $isRecurring}
	{foreach from=$recurringProductPeriods item=period name=rpp}
		<tr class="productRecurring" id="productRecurring_{$period.ID}">
			<td class="withRadio">
				<input type="radio" name="recurringID" {if $smarty.foreach.rpp.first}checked="checked"{/if}
					id="recurringPeriod_{$period.ID}" value="{$period.ID}"
				/>
			</td>
			<td>
				<label for="recurringPeriod_{$period.ID}" class="name">
					{$period.name|escape}
				</label>
				<label for="recurringPeriod_{$period.ID}" class="period">
					<span class="price">{$period.ProductPrice_period.formated_price.$currency}</span> {t _every} {t `$periodTypesSingle[$period.periodType]`} {t _for} <span class="price">{$period.rebillCount}</span> {t `$periodTypesPlural[$period.periodType]`}
				</label>
				{if $period.ProductPrice_setup[$currency].price > 0} 
					<label for="recurringPeriod_{$period.ID}" class="setup">
						{t _setup_fee}: <span class="price">{$period.ProductPrice_setup.formated_price.$currency}</span>
					</label>
				{/if}
				<label for="recurringPeriod_{$period.ID}" class="description">{$period.description|escape}</label>
			</td>
		</tr>
	{/foreach}
{/if}