{% if "OFFLINE_LOGO_`method`"|config %}
	<p class="offlineMethodLogo">
		<img src="{static url="OFFLINE_LOGO_`method`"|config}" />
	</p>
{% endif %}

{% if "OFFLINE_DESCR_`method`"|config %}
	<p class="offlineMethodDescr">
		{"OFFLINE_DESCR_`method`"|config}
	</p>
{% endif %}

{% if "OFFLINE_INSTR_`method`"|config %}
	<p class="offlineMethodInstr">
		{"OFFLINE_INSTR_`method`"|config}
	</p>
{% endif %}
