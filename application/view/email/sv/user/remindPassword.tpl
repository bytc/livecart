Ditt password hos [[ config('STORE_NAME') ]]!
Kära [[user.fullName]],

Här kommer dina inloggningsuppgifter hos [[config.STORE_NAME]]:

E-mail: <b>[[user.email]]</b>
Password: <b>[[user.newPassword]]</b>

Du kan logga in direkt via den här länken:
[[ fullurl("user/login") ]]

[[ partial("email/sv/signature.tpl") ]]