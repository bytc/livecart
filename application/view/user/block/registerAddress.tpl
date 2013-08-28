{assign var="fields" value='USER_FIELDS'|config}

{block FORM-NEW-CUSTOMER-TOP}

<div class="registerColumn">

	<h3>{t _your_personal_details}</h3>

	[[ partial("user/block/nameFields.tpl") ]]

	{input name="email"}
		{label}{t _your_email}:{/label}
		{textfield}
	{/input}

	{input name="newsletter"}
		{checkbox}
		{label}{t _newsletter_signup}{/label}
	{/input}

	[[ partial("user/block/phoneField.tpl") ]]

	{if 'PASSWORD_GENERATION'|config != 'PASSWORD_AUTO'}
		[[ partial("user/block/passwordFields.tpl") ]]
	{/if}

	{include file="block/eav/fields.tpl" item=$user filter="isDisplayed"}
	{include file="block/eav/fields.tpl" eavPrefix=$prefix}

</div>

<div class="registerColumn">

	{if $showHeading && $order.isShippingRequired && !'REQUIRE_SAME_ADDRESS'|config}
		<h3>{t _billing_address}</h3>
	{else}
		<h3>{t _your_address}</h3>
	{/if}

	[[ partial("user/block/addressFields.tpl") ]]

</div>