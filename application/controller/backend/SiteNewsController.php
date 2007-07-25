<?php

ClassLoader::import('application.controller.backend.abstract.StoreManagementController');
ClassLoader::import('application.model.sitenews.NewsPost');

/**
 *  @role news
 */
class SiteNewsController extends StoreManagementController
{
	public function index()
	{
		$f = new ARSelectFilter();
		$f->setOrder(new ARFieldHandle('NewsPost', 'position'), 'DESC');
		$response = new ActionResponse('newsList', ActiveRecordModel::getRecordSetArray('NewsPost', $f));
		$response->set('form', $this->buildForm());
		return $response;
	}	

	public function edit()
	{		
		$form = $this->buildForm();
		$form->loadData(NewsPost::getInstanceById($this->request->get('id'), NewsPost::LOAD_DATA)->toArray());
		return new ActionResponse('form', $form);
	}

	public function save()
	{
		$validator = $this->buildValidator();
		if (!$validator->isValid())
		{
			return new JSONResponse(array('err' => $validator->getErrorList()));
		}

		$post = $this->request->get('id') ? ActiveRecordModel::getInstanceById('NewsPost', $this->request->get('id'), ActiveRecordModel::LOAD_DATA) : ActiveRecordModel::getNewInstance('NewsPost');
		$post->loadRequestData($this->request);
		$post->save();
		
		return new JSONResponse($post->toArray());
	}	
	
    private function buildForm()
    {
		ClassLoader::import("framework.request.validator.Form");
		return new Form($this->buildValidator());        
    }

    private function buildValidator()
    {
		ClassLoader::import("framework.request.validator.RequestValidator");        
        $validator = new RequestValidator("newspost", $this->request);
        $validator->addCheck('title', new IsNotEmptyCheck($this->translate('_err_enter_title')));
        $validator->addCheck('text', new IsNotEmptyCheck($this->translate('_err_enter_text')));
     
        return $validator;
    }
}

?>