[[ config('STORE_NAME') ]] Objednávka zrušena
Vážený [[user.fullName]],

Vaše objednávka č.: [[order.invoiceNumber]] na [[ config('STORE_NAME') ]] byla zrušena.

Pokud k této objednávce máte nějaký dotaz, můžete nam poslat email nebo použít tento odkaz:
{link controller=user action=viewOrder id=$order.ID url=true}

Položky zrušené objednávky:
[[ partial("email/blockOrderItems.tpl") ]]

[[ partial("email/en/signature.tpl") ]]