
<script type="text/javascript"><!--//--><![CDATA[//><!--

sfHover = function() {
	var sfEls = document.getElementById("dynamicNav").getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
		sfEls[i].onmouseover=function() {
			this.className+=" sfhover";
		}
		sfEls[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(" sfhover\\b"), "");
		}
	}
}
if (window.attachEvent) window.attachEvent("onload", sfHover);

//--><!]]></script>


{function name="dynamicCategoryTree" node=false filters=false}
	{% if !empty(node) %}
		<ul class="unstyled" id="dynamicNav">
		{% for category in node %}
				<li class="{% if category.parentNodeID == 1 %}topCategory{% endif %} {% if category.lft <= currentCategory.lft && category.rgt >= currentCategory.rgt %} dynCurrent{% endif %}{% if category.subCategories %} hasSubs{% else %} noSubs{% endif %}">
					<a href="{categoryUrl data=category filters=category.filters}">[[category.name()]]</a>
					{% if config('DISPLAY_NUM_CAT') %}
						[[ partial('block/count.tpl', ['count': category.count]) ]]
					{% endif %}
					{% if category.subCategories %}
		   				{dynamicCategoryTree node=category.subCategories}
					{% endif %}
				</li>
		{% endfor %}
		</ul>
	{% endif %}
{/function}

<div class="panel categories dynamicMenu">
	<div class="panel-heading">{t _categories}</div>
	{dynamicCategoryTree node=categories}
</div>
