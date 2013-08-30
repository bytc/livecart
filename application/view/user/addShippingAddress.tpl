{% extends "layout/frontend.tpl" %}

{% block title %}{t _add_shipping_address}{{% endblock %}
[[ partial("user/layout.tpl") ]]
{include file="user/userMenu.tpl" current="addressMenu"}
{% block content %}

	{form action="user/doAddShippingAddress" handle=$form class="form-horizontal"}
		[[ partial("user/addressForm.tpl") ]]
		{include file="block/submit.tpl" caption="_continue" cancelRoute=$return}
	{/form}

{% endblock %}
