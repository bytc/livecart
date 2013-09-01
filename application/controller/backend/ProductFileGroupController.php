<?php


/**
 * Controller for handling product based actions performed by store administrators
 *
 * @package application/controller/backend
 * @author Integry Systems
 * @role product
 */
class ProductFileGroupController extends StoreManagementController
{
	/**
	 * @role update
	 */
	public function createAction()
	{
		$product = Product::getInstanceByID((int)$this->request->get('productID'));
		$fileGroup = ProductFileGroup::getNewInstance($product);
		return $this->save($fileGroup);
	}

	/**
	 * @role update
	 */
	public function updateAction()
	{
		$fileGroup = ProductFileGroup::getInstanceByID((int)$this->request->get('ID'));
		return $this->save($fileGroup);
	}

	private function save(ProductFileGroup $fileGroup)
	{
		$validator = $this->buildValidator();
		if ($validator->isValid())
		{
			foreach ($this->application->getLanguageArray(true) as $lang)
			{
				if ($this->request->isValueSet('name_' . $lang))
				{
					$fileGroup->setValueByLang('name', $lang, $this->request->get('name_' . $lang));
				}
			}

			$fileGroup->save();

			return new JSONResponse(array('status' => "success", 'ID' => $fileGroup->getID()));
		}
		else
		{
			return new JSONResponse(array('status' => "failure", 'errors' => $validator->getErrorList()));
		}
	}

	/**
	 * @role update
	 */
	public function deleteAction()
	{
		ProductFileGroup::getInstanceByID((int)$this->request->get('id'))->delete();
		return new JSONResponse(false, 'success');
	}

	/**
	 * @role update
	 */
	public function sortAction()
	{
		foreach($this->request->get($this->request->get('target'), array()) as $position => $key)
		{
			if(empty($key)) continue;
			$fileGroup = ProductFileGroup::getInstanceByID((int)$key);
			$fileGroup->position->set((int)$position);
			$fileGroup->save();
		}

		return new JSONResponse(false, 'success');
	}

	public function editAction()
	{
		$group = ProductFileGroup::getInstanceByID((int)$this->request->get('id'), true);

		return new JSONResponse($group->toArray());
	}

	private function buildValidator()
	{
		$validator = $this->getValidator("productFileGroupValidator", $this->request);

		$validator->add('name_' . $this->application->getDefaultLanguageCode(), new PresenceOf(array('message' => $this->translate('_err_group_name_is_empty'))));

		return $validator;
	}

}

?>