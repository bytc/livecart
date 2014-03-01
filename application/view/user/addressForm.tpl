{assign var="fields" value=config('USER_FIELDS')}

<div class="registerColumn">
	<fieldset>
		<legend>{t _your_personal_details}</legend>
		[[ partial("user/block/nameFields.tpl") ]]
		[[ partial("user/block/phoneField.tpl") ]]
	</fieldset>
</div>

<div class="registerColumn">
	<fieldset>
		<legend>{t _your_address}</legend>
		[[ partial("user/block/addressFields.tpl") ]]
		[[ partial('block/eav/fields.tpl', ['item': address, 'eavPrefix': prefix]) ]]
	</fieldset>
</div>

{% if !empty(return) %}
	<input type="hidden" name="return" value="[[return]]" />
{% endif %}