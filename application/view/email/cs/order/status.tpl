[[ config('STORE_NAME') ]] Změna stavu objednávky
Vážený(á) [[user.fullName]],

{% if order.shipments|@count == 1 %}
Stav Vaší objednávky č.: [[order.invoiceNumber]] byl změněn.
{% else %}
Stav jedné nebo víve zásilek z Vaší objednávky č.: [[order.invoiceNumber]] byl změněn.
{% endif %}

Pokud k této objednávce máte nějaké dotazy, můžete nám poslat email nebo použít následující odkaz:
[[ fullurl("user/viewOrder" ~ order.ID) ]]

{foreach from=order.shipments item=shipment}
Nový stav: {% if shipment.status == 2 %}čeká na odeslání{% elseif shipment.status == 3 %}odeslána{% elseif shipment.status == 4 %}vrácena{% else %}vyřizuje se{% endif %}

[[ partial("email/blockItemHeader.tpl") ]]
[[ partial("email/blockShipment.tpl") ]]
------------------------------------------------------------

{% endfor %}

[[ partial("email/en/signature.tpl") ]]