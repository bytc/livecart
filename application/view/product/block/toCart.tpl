{% if product.isAvailable && config('ENABLE_CART') %}

	<div class="well">
		
		{block PRODUCT-OPTIONS}
		{block PRODUCT-VARIATIONS}

		<div id="productToCart" class="cartLinks">
			<span class="param">{t _quantity}:</span>
			<span class="value">
				[[ partial("product/block/quantity.tpl") ]]
			</span>

			<button type="submit" class="btn btn-success btn-large addToCart">
					<span class="glyphicon glyphicon-shopping-cart"></span>
					<span class="buttonCaption">{t _add_to_cart}</span>
			</button>

			{hidden name="return" value=catRoute}
		</div>
	
	</div>
{% endif %}

