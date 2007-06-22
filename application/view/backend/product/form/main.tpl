{literal}
<script type="text/javascript">
{/literal}
Backend.Product.Editor.prototype.setActiveProductName({json array=$product.name_lang});
</script>

<fieldset>
    <legend>{t _main_details}</legend>
    
	<p class="checkbox" class="container">
		{checkbox name="isEnabled" class="checkbox" id="product_`$cat`_`$product.ID`_isEnabled" class="checkbox"}
        <label for="product_{$cat}_{$product.ID}_isEnabled">{t _enabled}</label>
	</p>   
	<p class="required">
		<label for="product_{$cat}_{$product.ID}_name">{t _product_name}:</label>
		<fieldset class="error">
			{textfield name="name" id="product_`$cat`_`$product.ID`_name" class="wide"}
			<div class="errorText hidden"></div>
		</fieldset>
	</p>
    <p class="required">
    	<label for="product_{$cat}_{$product.ID}_sku"><a class="acronym">{t _sku_code}<div>{t _hint_sku}</div></a>:</label>
    	<fieldset class="error">
    		{textfield name="sku" id="product_`$cat`_`$product.ID`_sku" class="product_sku" autocomplete="controller=backend.product field=sku"} 
    		<div class="errorText hidden"></div>
    	</fieldset>			
    </p>
	<p>
		<label for=""></label>
		{checkbox name="autosku" id="product_`$cat`_`$product.ID`_sku_auto" class="checkbox" value="on" onclick="Backend.Product.toggleSkuField(this);"}
		<label for="product_{$cat}_{$product.ID}_sku_auto" class="checkbox">{t _generate_sku}</label>
	</p>
	<p>
		<label for="product_{$cat}_{$product.ID}_shortdes"><a class="acronym">{t _short_description}<div>{t _hint_shortdescr}</div></a>:</label>
		<div class="textarea">
			{textarea class="shortDescr" id="product_`$cat`_`$product.ID`_shortdes" name="shortDescription"}
		</div>
	</p>
	<p>
		<label for="product_{$cat}_{$product.ID}_longdes"><a class="acronym">{t _long_description}<div>{t _hint_longdescr}</div></a>:</label>
		<div class="textarea">
			{textarea class="longDescr" id="product_`$cat`_`$product.ID`_longdes" name="longDescription"}
		</div>
	</p>
	<p>
		<label for="product_{$cat}_{$product.ID}_type">{t _product_type}:</label>
		<fieldset class="error">
			{selectfield options=$productTypes name="type" id="product_`$cat`_`$product.ID`_type" class="productType"}
			<div class="errorText hidden"></div>
		</fieldset>			
	</p>
	<p>
		<label for="product_{$cat}_{$product.ID}_url">{t _website_address}:</label>
		<fieldset class="error">
			{textfield name="URL" class="wide" id="product_`$cat`_`$product.ID`_url" autocomplete="controller=backend.product field=URL"}
			<div class="errorText hidden"></div>
		</fieldset>			
	</p>
	<p>
		<label for="product_{$cat}_{$product.ID}_manufacterer">{t _manufacturer}:</label>
		<fieldset class="error">
			{textfield name="manufacturer" class="wide" autocomplete="controller=backend.manufacturer field=manufacturer" id="product_`$cat`_`$product.ID`_manufacterer"}
			<div class="errorText hidden"></div>
		</fieldset>			
	</p>
	<p>
		<label for="product_{$cat}_{$product.ID}_keywords"><a class="acronym">{t _keywords}<div>{t _hint_keywords}</div></a>:</label>
		<fieldset class="error">
			{textfield name="keywords" class="wide" id="product_`$cat`_`$product.ID`_keywords" autocomplete="controller=backend.product field=keywords"}
			<div class="errorText hidden"></div>
		</fieldset>			
	</p>

	<p>
		<label></label>
        {checkbox name="isFeatured" class="checkbox" id="product_`$cat`_`$product.ID`_isfeatured"}
		<label for="product_{$cat}_{$product.ID}_isfeatured"><a class="acronym">{t _mark_as_featured_product}<div>{t _hint_featured}</div></a></label>
	</p>

	<p>
		<label></label>
        {checkbox name="isFractionalUnit" class="checkbox" id="product_`$cat`_`$product.ID`_isFractionalUnit"}
		<label for="product_{$cat}_{$product.ID}_isFractionalUnit">{t _allow_fractional_quantities}</label>
	</p>
	
</fieldset>