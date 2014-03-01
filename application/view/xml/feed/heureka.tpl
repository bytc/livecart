<?xml version="1.0" encoding="utf-8" ?>
<SHOP>
	{% for product in feed %}
		<SHOPITEM>
			<PRODUCT><![CDATA[[[product.name()]]]]></PRODUCT>
			<DESCRIPTION><![CDATA[[[product.longDescription()]]]]></DESCRIPTION>
			<URL><![CDATA[{productUrl product=product full=true}]]></URL>

			{% if product.DefaultImage.ID %}
			<IMGURL><![CDATA[[[product.DefaultImage.urls.4]]]]></IMGURL>
			{% endif %}
			<PRICE_VAT><![CDATA[{product.price_CZK|default:product.price_EUR}]]></PRICE_VAT>
			<MANUFACTURER><![CDATA[[[product.Manufacturer.name]]]]></MANUFACTURER>
			<CATEGORYTEXT><![CDATA[[[product.category_path]]]]></CATEGORYTEXT>
			<DELIVERY_DATE>2</DELIVERY_DATE>
		</SHOPITEM>
	{% endfor %}
</SHOP>