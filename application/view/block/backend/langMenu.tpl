<a href="#" id="langSwitchLink" onClick="if (showLangMenu) { showLangMenu(true); }; return false;" {% if currentLang.image %}class="hasFlag" style="background-image: url([[currentLang.image]])"{% endif %}>{t _change()uage}</a> |

<div id="langMenuContainer">
	<div id="langMenuIndicator" class="menuLoadIndicator"></div>
</div>

<script type="text/javascript">
	var langMenuUrl = '[[ url("backend.language/langSwitchMenu", "returnRoute=returnRoute") ]]';
</script>