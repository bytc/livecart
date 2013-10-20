<?php


/**
 *	Implements common logic for handling image uploads and management for various business objects
 *	like products and product categories.
 *
 *  @author Integry Systems
 *  @package application/backend/controller/abstract
 */
abstract class ObjectImageController extends StoreManagementController
{
	abstract protected function getModelClass();
	abstract protected function getOwnerClass();
	abstract protected function getForeignKeyName();

	public function indexAction()
	{
		$owner = ActiveRecordModel::getInstanceByID($this->getOwnerClass(), (int)$this->request->get('id'));
		$filter = new ARSelectFilter();
		$filter->setCondition(new EqualsCond(new ARFieldHandle($this->getModelClass(), $this->getForeignKeyName()), $owner->getID()));
		$filter->orderBy(new ARFieldHandle($this->getModelClass(), 'position'));

		$imageArray = ActiveRecordModel::getRecordSetArray($this->getModelClass(), $filter);


		$this->set('form', $this->buildForm($owner->getID()));
		$this->set('maxSize', ini_get('upload_max_filesize'));
		$this->set('ownerId', $owner->getID());
		$this->set('images', json_encode($imageArray));
	}

	public function uploadAction()
	{
		$ownerId = $this->request->get('ownerId');

		$owner = ActiveRecordModel::getInstanceByID($this->getOwnerClass(), $ownerId);

		$validator = $this->buildValidator($ownerId);

		if (!$validator->isValid())
		{
		  	$errors = $validator->getErrorList();
			$result = array('error' => $errors['image']);
		}
		else
		{
		  	ActiveRecord::beginTransaction();

			$catImage = call_user_func_array(array($this->getModelClass(), 'getNewInstance'), array($owner));

			$multilingualFields = array("title");
			$catImage->setValueArrayByLang($multilingualFields, $this->application->getDefaultLanguageCode(), $this->application->getLanguageArray(true), $this->request);

			$catImage->save();

		  	// resize image
		  	$resizer = new ImageManipulator($_FILES['image']['tmp_name']);
		  	$res = $catImage->resizeImage($resizer);

			if ($res)
			{
			  	ActiveRecord::commit();
			  	$result = $catImage->toArray();
			}
			else
			{
			  	$result = array('error' => $this->translate('_err_resize'));
				ActiveRecord::rollback();
			}
		}

		// pre-PHP 5.2 JSON encoding breaks because of recursion
		unset($result['Category']['DefaultImage']['Category']);
		unset($result['Product']['DefaultImage']['Product']);

		$this->setLayout('iframeJs');

		$this->set('ownerId', $ownerId);
		$this->set('result', json_encode($result));
	}

	public function saveAction()
	{
		ActiveRecord::beginTransaction();
		$image = null;
	  	try
		{
			$image = ActiveRecord::getInstanceById($this->getModelClass(), $this->request->get('imageId'), true);

			$multilingualFields = array("title");
			$image->setValueArrayByLang($multilingualFields, $this->application->getDefaultLanguageCode(), $this->application->getLanguageArray(true), $this->request);
			$image->save();

		  	if ($_FILES['image']['tmp_name'])
		  	{
				$resizer = new ImageManipulator($_FILES['image']['tmp_name']);

				if (!$resizer->isValidImage())
				{
				  	throw new InvalidImageException();
				}

				if (!$image->resizeImage($resizer))
				{
				  	throw new ImageResizeException();
				}
			}
		}
		catch (InvalidImageException $exc)
		{
			$error = $this->translate('_err_not_image');
		}
		catch (ImageResizeException $exc)
		{
			$error = $this->translate('_err_resize');
		}
		catch (Exception $exc)
		{
			$error = $this->translate('_err_not_found ' . get_class($exc));
		}



		if (isset($error))
		{
		  	ActiveRecord::rollback();
		  	$result = array('error' => $error);
		}
		else
		{
			ActiveRecord::commit();
		  	$result = $image->toArray();
		}

		$this->setLayout('iframeJs');
		$this->set('ownerId', $this->request->get('ownerId'));
		$this->set('imageId', $this->request->get('imageId'));
	  	$this->set('result', @json_encode($result));
	}

	/**
	 * Remove an image
	 * @return RawResponse
	 */
	public function deleteAction()
	{
		try
		{
			call_user_func_array(array($this->getModelClass(), 'deleteByID'), array($this->request->get('id')));
		  	return true;
		}
		catch (ARNotFoundException $exc)
		{
			return false;
		}
	}

	/**
	 * Save image order
	 * @return RawResponse
	 */
	public function saveOrderAction($order=null)
	{
		$ownerId = $this->request->get('ownerId');
		if($order === null)
		{
			$varName = array_shift(explode('_', $this->request->get('draggedID')));
			$order = array_filter($this->request->get($varName . '_' . $ownerId), array($this, 'filterOrder'));
			$order = array_values($order);
		}
		foreach ($order as $key => $value)
		{
			$update = new ARUpdateFilter();
			$update->setCondition(new EqualsCond(new ARFieldHandle($this->getModelClass(), 'ID'), $value));
			$update->addModifier('position', $key);
			ActiveRecord::updateRecordSet($this->getModelClass(), $update);
		}

		// set owners main image
		if (isset($order[0]))
		{
			$owner = ActiveRecordModel::getInstanceByID($this->getOwnerClass(), $ownerId);
			$owner->defaultImage->set(ActiveRecordModel::getInstanceByID($this->getModelClass(), $order[0]));
			$owner->save();
		}

		$resp = new RawResponse();
	  	$resp->setContent($this->request->get('draggedId'));
		return $resp;
	}

	private function filterorderBy($item)
	{
		return trim($item);
	}

	public function resizeImagesAction()
	{
		set_time_limit(0);

		$class = $this->getModelClass();
		$f = select();
		$count = ActiveRecord::getRecordCount($class, $f);
		$offset = 0;
		$chunk = 100;

		ob_flush();
		ob_end_clean();

		do
		{
			$f->limit($chunk, $offset);
			$set = ActiveRecordModel::getRecordSet($class, $f);
			foreach ($set as $image)
			{
				foreach (array($image->getPath('original'), $image->getPath(4)) as $path)
				{
					if (file_exists($path))
					{
						$image->setFile($path);
						echo $image->getID() . '|';
						flush();
						break;
					}
				}
			}

			$offset += $chunk;
			ActiveRecord::clearPool();
		}
		while ($set->size() > 0);
	}

	private function getFlushResponse($data)
	{
		return '|' . base64_encode(json_encode($data));
	}

	/**
	 * Builds an image upload form validator
	 *
	 * @return \Phalcon\Validation
	 */
	protected function buildValidator($catId)
	{
		$validator = $this->getValidator($this->getModelClass() . "_" . $catId, $this->request);

		$uploadCheck = new IsFileUploadedCheck($this->translate(!empty($_FILES['image']['name']) ? '_err_too_large' :'_err_not_uploaded'));
		$uploadCheck->setFieldName('image');
		$validator->add('image', $uploadCheck);

		$manip = new ImageManipulator();
		$imageCheck = new IsImageUploadedCheck($this->translate('_err_not_image'));
		$imageCheck->setFieldName('image');
		$imageCheck->setValidTypes($manip->getValidTypes());
		$validator->add('image', $imageCheck);

		return $validator;
	}

	/**
	 * Builds a category image form instance
	 *
	 * @return Form
	 */
	protected function buildForm($catId)
	{
		return new Form($this->buildValidator($catId));
	}
}

?>