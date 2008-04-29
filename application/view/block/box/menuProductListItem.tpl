<div class="image">
	<a href="{productUrl product=$product}">
	{if $product.DefaultImage.paths.1}
		{img src=$product.DefaultImage.paths.1 alt=$product.name_lang|escape}
	{else}
		{img src=image/missing_mini.jpg alt=$product.name_lang|escape}
	{/if}
	</a>
</div>

<div class="productInfo">
	<a href="{productUrl product=$product}" class="productName">{$product.name_lang}</a>
</div>

<div class="pricingInfo">
	<span class="price">
		{$product.formattedPrice.$currency}
		{if $product.formattedListPrice.$currency}
				<span class="listPrice">
					{$product.formattedListPrice.$currency}
				</span>
		{/if}
	</span>
</div>

<div class="clear"></div>
