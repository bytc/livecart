<div ng-controller="EavController" ng-init="setTree([[ json(nodes) ]]);">
	<div class="row">
		<div class="treeContainer col-sm-3">
			[[ partial('block/backend/tree.tpl', ['sortable': true]) ]]
		</div>

		<div class="col-sm-9">
			<section ui-view></section>
		</div>
	</div>
</div>
