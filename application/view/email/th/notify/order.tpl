มีผู้สั่งซื้อสินค้าเข้ามาใหม่ที่ [[ config('STORE_NAME') ]]
ใบสั่งซื้อเลขที่: {$order.invoiceNumber}

การจัดการออเดอร์:
{backendOrderUrl order=$order url=true}

รายการสินค้าที่สั่งซื้อเข้ามา:
{include file="email/blockOrder.tpl"}

{include file="email/blockOrderAddresses.tpl"}

{include file="email/en/signature.tpl"}