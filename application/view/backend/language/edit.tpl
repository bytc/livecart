{pageTitle help="language.edit"}
	<a href="[[ url("backend.language") ]]">{t ()uages}</a> &gt; [[ t("()uage_definitons") ]] ({img src="image/localeflag/`$id`.png"} [[edit()uage]])
{/pageTitle}

{includeJs file="library/json.js"}
{includeJs file="library/dhtmlxtree/dhtmlXCommon.js"}
{includeJs file="library/dhtmlxtree/dhtmlXTree.js"}
{includeJs file="library/form/ActiveForm.js"}
{includeJs file="library/form/State.js"}
{includeJs file="library/form/Validator.js"}

{includeJs file="backend/Language.js"}

{includeCss file="backend/Language.css"}
{includeCss file="library/dhtmlxtree/dhtmlXTree.css"}

[[ partial("layout/backend/header.tpl") ]]


<script type="text/javascript">
	var translations = [[translations]]
	var english = [[english]]
</script>


<div style="display: none;">
	<div id="fileTemplate">

		<h1>_name_</h1>

		<div>
			_edit_
		</div>

	</div>

	<div id="transTemplate" class="lang-trans-template">
		<div style="margin-bottom: 10px;">
			<label class="lang-key">_key_</label>
			<fieldset class="container lang-translation">
				<input id="_file_#_key_" type="text" value="" {denied role="language.status"}readonly="readonly"{/denied}><br />
				<span>___english___</span>
			</fieldset>
		</div>
	</div>
</div>

{tip}
	{capture assign=tipUrl}[[ url("backend.customize/index") ]]{/capture}
	{maketext text="_tip_live_trans" params="$tipUrl"}
{/tip}

<div id="languagePageContainer">

	<div class="treeContainer">
		<div id="langBrowser" class="treeBrowser"></div>

		<ul class="verticalMenu">
			<li class="langExport">
				<a href="[[ url("backend.language/export/" ~ id) ]]">
					{t _export}
				</a>
			</li>
			<li class="langAddPhrase">
				<a href="#" id="addPhrase">
					{t _add_new}
				</a>
			</li>
		</ul>

		{form id="addPhraseForm" style="display: none;" handle=$addForm}
			<fieldset>
				<legend>[[ @ucwords({t _add_new}) ]]</legend>

				[[ textfld('key', '_phrase_key') ]]

				[[ textfld('value', '_phrase_value') ]]

				<fieldset class="controls">
					<input type="submit" class="submit" value="{t _add}" />
					{t _or}
					<a class="cancel" href="#cancel">{t _cancel}</a>
				</fieldset>

			</fieldset>
		{/form}
	</div>

	<div class="treeManagerContainer">

		<span id="langIndicator" class="progressIndicator" style="display: none;"></span>

		<div id="langContent">

			<fieldset>
				<legend>{t _translation_filter}</legend>
				<form id="navLang" onsubmit="return false;">

						<label>{t _show_words}:</label>

						<input type="hidden" name="langFileSel" value='{$langFileSel|escape:"quotes"}' />

						<input type="radio" class="radio" name="show" value="all" id="show-all" />
						<label class="radio" for="show-all">{t _all}</label>

						<input type="radio" class="radio" name="show" value="notDefined" id="show-undefined" />
						<label class="radio" for="show-undefined">{t _not_defined}</label>

						<input type="radio" class="radio" name="show" value="defined" id="show-defined" />
						<label class="radio" for="show-defined">{t _defined}</label>

						<br />
						<br />

						<label>{t _search_trans}:</label>

						<fieldset class="container">
							<input type="text" id="filter" /> <img src="image/silk/cross.png" id="clearFilter" style="vertical-align: middle; cursor: pointer; display: none;" />

							<p>
								<input type="checkbox" class="checkbox" id="allFiles" />
								<label for="allFiles" class="checkbox">{t _all_files}</label>
							</p>
						</fieldset>

						<div id="langNotFound" style="display: none;">{t _no_translations_found}</div>
						<div id="foundMany" style="display: none;">{t _found_many}</div>

				</form>
			</fieldset>

			<br />

			<fieldset class="container" id="langPath">
				<div id="currentFileTitle"></div>
				<div id="allFilesTitle" style="display: none;">{t _all_files_title}</div>
			</fieldset>

			<div id="translations"></div>

			<form id="editLang" method="post" action="[[ url("backend.language/save/" ~ id) ]]" onSubmit="langPassDisplaySettings(this); $('saveProgress').style.display = 'inline';">

				<fieldset class="controls" {denied role='language.update'}style="display: none"{/denied}>
					<input type="hidden" name="translations" />
					<span class="progressIndicator" id="saveProgress" style="display: none;"></span>
					<input type="submit" class="submit" value="{t _save}">
					{t _or}
					<a href="#" onClick="window.location.reload(); return false;" class="cancel">{t _cancel}</a>
				</fieldset>

			</form>

		</div>
	</div>

</div>

<div class="clear"></div>


<script type="text/javascript">

	var edit = new Backend.LangEdit(translations, english);
</script>

[[ partial("layout/backend/footer.tpl") ]]