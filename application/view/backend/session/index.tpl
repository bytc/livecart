{includeCss file="backend/Session.css"}
{% block title %}{t _backend_login|branding}{{% endblock %}

[[ partial("layout/backend/meta.tpl") ]]

<h1 id="loginHeader">{t _backend_login|branding}</h1>

<div id="logoContainer">
	{img src='BACKEND_LOGIN_LOGO'|config|@or:"image/promo/transparentlogo.png"}
</div>

<div id="loginContainer">
{% if req('failed') %}
	<div class="loginFailed">{t _login_failed}</div>
{% endif %}
<form action="{link controller="backend.session" action=doLogin}" method="post" />
	<p>
	   <label for="email">{t _email}:</label>
	   <input type="text" class="text" id="email" name="email" value="{$email|escape}" />
	</p>
	<p>
		<label for="password">{t _password}:</label>
		<fieldset class="container">
			<input type="password" class="text" id="password" name="password" value="{$password|escape}" />
			<a href="{link controller="user" action="remindPassword" query="return=`$return`"}" class="forgottenPassword">
				{t _remind_password}
			</a>
		</fieldset>
	</p>

   	<p>
		<label></label>
		<input type="submit" class="submit" value="{tn _login}" />
	</p>

	<input type="hidden" name="return" value="[[return]]" />

</form>

</div>

{literal}
	<script type="text/javascript">
		Event.observe(window, 'load', function() {$('email').focus()});
	</script>
{/literal}

</body>
</html>

{* include file="layout/backend/footer.tpl" *}