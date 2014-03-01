{% extends "layout/frontend.tpl" %}

{% title %}{t _pay}{% endblock %}
[[ partial("checkout/layout.tpl") ]]
{% block content %}

	<div class="checkoutHeader">
		[[ partial('checkout/checkoutProgress.tpl', ['progress': "progressPayment"]) ]]
	</div>

	<div id="payTotal">
		<div>
			{t _order_total}: <span class="subTotal">{order.formattedTotal.currency}</span>
		</div>
	</div>

	{% if !empty(error) %}
		<div class="errorMessage">
			<div>[[error]]</div>
		</div>
	{% endif %}

	[[ partial('checkout/completeOverview.tpl', ['productsInSeparateLine': true]) ]]

	<div class="paymentMethods">
		[[ partial("checkout/paymentMethods.tpl") ]]
		[[ partial("checkout/offlinePaymentMethods.tpl") ]]
	</div>

	<div class="clear"></div>

{% endblock %}
