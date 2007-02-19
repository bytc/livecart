{foreach from=$languageList key=lang item=langName}
<fieldset class="expandingSection">
	<legend>Translate to: {$langName}</legend>
	<div class="expandingSectionContent">
		<p>
			<label fo="product_{$cat}_{$product.ID}_name_{$lang}">Product name:</label>
			{textfield name="name_$lang" class="wide" id="product_`$cat`_`$product.ID`_name_`$lang`"}
		</p>
		<p>
			<label for="product_{$cat}_{$product.ID}_shortdes_{$lang}">Short description:</label>
			<div class="textarea">
				{textarea class="shortDescr" name="shortDescription_$lang" id="product_`$cat`_`$product.ID`_shortdes_`$lang`"}
			</div>
		</p>
		<p>
			<label for="product_{$cat}_{$product.ID}_longdes_{$lang}">Long description:</label>
			<div class="textarea">
				{textarea class="longDescr" name="longDescription_$lang" id="product_`$cat`_`$product.ID`_longdes_`$lang`"}
			</div>
		</p>
		
		{if $multiLingualSpecFields}
		<fieldset>
			<legend>Specification Attributes</legend>
			{foreach from=$multiLingualSpecFields item="field"}
				<p>		
					<label for="product_{$cat}_{$product.ID}_{$field.fieldName}_{$lang}">{$field.name_lang}:</label>		
                    {include file="backend/product/form/specFieldFactory.tpl" field=$field language=$lang}	
				</p>
			{/foreach}
		</fieldset>
		{/if}
	</div>
</fieldset>
{/foreach}