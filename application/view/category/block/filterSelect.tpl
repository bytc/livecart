{% if sectionFilters || config('TOP_FILTER_CONTINUOS') %}

	{counter name="topMenuFilterIndex" assign="topMenuFilterIndex"}
	{counter name="lastFilterSelected" assign="lastFilterSelected"}
	{assign var="appliedFilters" value=sectionFilters.appliedFilters|default:filters}
	{% if !config('TOP_FILTER_RELOAD') %}
		{% set action = "boxFilterTopBlock" %}
	{% else %}
		{% set action = "index" %}
	{% endif %}

	{% if config('TOP_FILTER_CONTINUOS') && (lastFilterSelected < topMenuFilterIndex) %}
		{% set disabled = true %}
	{% endif %}

	{% if !config('TOP_MENU_COMPACT') %}
		<span class="topMenuFilterCaption {% if topMenuFilterIndex == 1 %}first{% endif %}">[[ t(title) ]]</span>
	{% endif %}

	<select {% if !empty(disabled) %}disabled="disabled" class="disabled"{% endif %}>
		<option value="{categoryUrl action=action data=category filters=appliedFilters removeFilters=sectionFilters.filters}">
			{% if config('TOP_MENU_COMPACT') %}
				[[ t(title) ]]
			{% else %}
				&nbsp;&nbsp;&nbsp;&nbsp;
			{% endif %}
		</option>
		{% if empty(disabled) %}
			{foreach from=sectionFilters.filters item="filter" name="filters"}
				<option value="{categoryUrl action=action data=category filters=appliedFilters addFilter=filter removeFilters=sectionFilters.filters}" {% if filters[filter.ID] %}selected="selected" {counter name="lastFilterSelected" assign="lastFilterSelected"}{% endif %}>[[filter.name()]]</option>
			{% endfor %}
		{% endif %}
	</select>

	{counter name="topMenuFilterIndex" assign="topMenuFilterIndex"}
{% endif %}