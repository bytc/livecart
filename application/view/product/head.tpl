<div class="row" id="productHead">
	<div class="col-sm-6">
		{block PRODUCT-IMAGES}  {* product/block/images.tpl *}
	</div>
	<div class="col-sm-6">
		<h1>[[product.name()]]</h1>
		{block PRODUCT-ATTRIBUTE-SUMMARY}	{* product/block/attributeSummary.tpl *}
		{block PRODUCT-SUMMARY} {* product/block/summary.tpl *}
	</div>
</div>