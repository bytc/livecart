<span id="newslettersMass_0" class="activeGridMass">

	{form action="backend.newsletter/processMass" method="POST" handle=$massForm onsubmit="return false;"}

	<input type="hidden" name="filters" value="" />
	<input type="hidden" name="selectedIDs" value="" />
	<input type="hidden" name="isInverse" value="" />

	{t _with_selected}:
	<select name="act" class="select">
		<option value="delete">{t _delete}</option>
	</select>

	<input type="submit" value="{t _process}" class="submit" />
	<span class="massIndicator progressIndicator" style="display: none;"></span>

	{/form}

</span>