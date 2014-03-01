<fieldset class="container" {denied role="taxes.create"}style="display: none"{/denied}>
	<ul class="menu" id="tax_new_menu">
		<li class="addTax"><a href="#new_tax" id="tax_new_show">{t _add_new_tax}</a></li>
		<li class="done addTaxCancel" style="display: none"><a href="#cancel_tax" id="tax_new_cancel">{t _cancel_adding_new_tax}</a></li>
	</ul>
</fieldset>

<fieldset id="tax_new_form" style="display: none;" class="addForm">
	<legend>[[ capitalize({t _add_new_tax}) ]]</legend>
	[[ partial('backend/tax/tax.tpl', ['tax': newTax, 'taxForm': newTaxForm]) ]]
</fieldset>

<ul class="activeList {allowed role="taxes.remove"}activeList_add_delete activeList_add_sort{/allowed} activeList_add_edit tax_taxesList" id="tax_taxesList" >
{foreach from=taxesForms key="key" item="taxForm"}
	<li id="tax_taxesList_{taxes[key].ID}">

	<span class="error tax_viewMode">{taxes[key].name}</span>

	</li>
{% endfor %}
</ul>


<script type="text/javascript">
	Event.observe(("tax_new_show"), "click", function(e)
	{
		e.preventDefault();
		var newForm = Backend.Tax.prototype.getInstance( ("tax_new_form").down('form') );
		newForm.showNewForm();
	});

	ActiveList.prototype.getInstance("tax_taxesList", Backend.Tax.prototype.Callbacks, function() {});
</script>

