<div class="yellowMessage" style="display: none;">
   	<div>
   		{t _user_group_roles_where_successfully_updated}
   	</div>
</div>



{form id="roles_form_`$userGroup.ID`" handle=$form action="controller=backend.roles action=update id=`$userGroup.ID`" onsubmit="Backend.Roles.prototype.getInstance('roles_form_`$userGroup.ID`').save(event);" method="post" role="userGroup.update"}
    
    <div id="userGroupsRolesTree_{$userGroup.ID}" class="treeBrowser" ></div>
    
    <fieldset class="roles_controls error controls">
        <span class="activeForm_progress"></span>
        <input type="submit" class="roles_save button submit" value="{t _save}" />
        {t _or}
        <a href="#cancel" class="roles_cancel cancel">{t _cancel}</a>
    </fieldset>	
{/form}

<script type="text/javascript">    	
    Backend.Roles.prototype.Links.xmlBranch  = '{link controller=backend.roles action=xmlBranch}';
      
    var roles = Backend.Roles.prototype.getInstance('roles_form_{$userGroup.ID}', {json array=$roles}, {json array=$activeRolesIDs});
    
    
{denied role="userGroup.update"}{literal}
    $A($("{/literal}userGroupsRolesTree_{$userGroup.ID}{literal}").getElementsByTagName('img')).each(function(img)
    {
        img.onclick = function() { return false; };
    });
{/literal}{/denied}
</script>