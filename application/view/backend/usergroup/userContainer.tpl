{* User managing container *}
<div id="userManagerContainer" class="treeManagerContainer" style="display: none;">
	<fieldset class="container">
		<ul class="menu">
			<li class="done"><a href="#cancelEditing" id="cancel_user_edit" class="cancel">{t _cancel_editing_user_info}</a></li>
		</ul>
	</fieldset>

	<div class="tabContainer">
		<ul class="tabList tabs">
			<li id="tabUserInfo" class="tab active">
				<a href="[[ url("backend.user/info/_id_") ]]"}">{t _user_info}</a>
				<span class="tabHelp">users.edit</span>
			</li>
			<li id="tabOrdersList" class="tab">
				<a href="[[ url("backend.customerOrder/orders/1", "'userID=_id_'") ]]">{t _orders}</a>
				<span class="tabHelp">customerOrders.orders</span>
			</li>
			{block USER_TABS}
		</ul>
	</div>
	<div class="sectionContainer maxHeight h--50"></div>


	<script type="text/javascript">
		Event.observe(("cancel_user_edit"), "click", function(e) {
			e.preventDefault();
			var user = Backend.User.Editor.prototype.getInstance(Backend.User.Editor.prototype.getCurrentId(), false);
			user.cancelForm();
		});
	</script>

</div>