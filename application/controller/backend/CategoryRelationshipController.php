<?php


/**
 * Controller for handling category based actions performed by store administrators
 *
 * @package application.controller.backend
 * @author Integry Systems
 * @role category
 */
class CategoryRelationshipController extends StoreManagementController
{
	public function indexAction()
	{
		$category = Category::getInstanceById($this->request->gget('id'), ActiveRecord::LOAD_DATA);

		$f = select();
		$f->setOrder(f('CategoryRelationship.position'));
		$additional = $category->getRelatedRecordSet('CategoryRelationship', $f, array('Category_RelatedCategory'));
		$categories = array();
		foreach ($additional as $cat)
		{
			$categories[] = $cat;
			$cat->relatedCategory->get()->load();
			$cat->relatedCategory->get()->getPathNodeSet();
		}

		$response = new ActionResponse('category', $category->toArray());
		$response->set('categories', ARSet::buildFromArray($categories)->toArray());

		return $response;
	}

	public function addCategoryAction()
	{
		$category = Category::getInstanceByID($this->request->gget('id'), ActiveRecord::LOAD_DATA, array('Category'));
		$relatedCategory = Category::getInstanceByID($this->request->gget('categoryId'), ActiveRecord::LOAD_DATA);

		// check if the category is not assigned to this category already
		$f = select(eq('CategoryRelationship.relatedCategoryID', $relatedCategory->getID()));
		if ($category->getRelatedRecordSet('CategoryRelationship', $f)->size())
		{
			return new JSONResponse(false, 'failure', $this->translate('_err_already_assigned'));
		}

		$relation = CategoryRelationship::getNewInstance($category, $relatedCategory);
		$relation->save();

		$relatedCategory->getPathNodeSet();
		return new JSONResponse(array('data' => $relation->toFlatArray()));
	}

	public function saveOrderAction()
	{
	  	$order = $this->request->gget('relatedCategories_' . $this->request->gget('id'));
		foreach ($order as $key => $value)
		{
			$update = new ARUpdateFilter();
			$update->setCondition(new EqualsCond(new ARFieldHandle('CategoryRelationship', 'ID'), $value));
			$update->addModifier('position', $key);
			ActiveRecord::updateRecordSet('CategoryRelationship', $update);
		}

		return new JSONResponse(false, 'success');
	}

	public function deleteAction()
	{
		$relation = ActiveRecordModel::getInstanceById('CategoryRelationship', $this->request->gget('categoryId'));
		$relation->delete();

		return new JSONResponse(array('data' => $relation->toFlatArray()));
	}
}