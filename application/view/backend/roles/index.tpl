{form id="roles_form_`$userGroup.ID`" handle=$form action="controller=backend.roles action=update id=`$userGroup.ID`" onsubmit="Backend.Roles.prototype.getInstance('roles_form_`$userGroup.ID`').save(event);" method="post" role="userGroup.permissions"}

	{input name="setAllPermissions"}
		{checkbox class="setAllPermissions" id="roles_setAllPermissions_`$userGroup.ID`"}
		{label}{t _set_all_permissions}{/label}
	{/input}

	<div id="userGroupsRolesTree_[[userGroup.ID]]" class="treeBrowser"></div>

	<fieldset class="roles_controls error controls">
		<span class="progressIndicator" style="display: none;"></span>
		<input type="submit" class="roles_save button submit" value="{t _save}" />
		{t _or}
		<a href="#cancel" class="roles_cancel cancel">{t _cancel}</a>
	</fieldset>

{/form}

<script type="text/javascript">
	Backend.Roles.prototype.Links.xmlBranch  = '[[ url("backend.roles/xmlBranch") ]]';
	var roles = Backend.Roles.prototype.getInstance('roles_form_[[userGroup.ID]]', {json array=$roles}, {json array=$activeRolesIDs}, {json array=$disabledRolesIDs});
	{denied role="userGroup.permissions"}

			$A($("userGroupsRolesTree_[[userGroup.ID]]").getElementsByTagName('img')).each(function(img)
			{
				img.onclick = function() { return false; };
			});

	{/denied}
</script>