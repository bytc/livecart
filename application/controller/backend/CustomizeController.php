<?php


/**
 * Handles dynamic interface customizations
 *
 * @package application.controller.backend
 * @author Integry Systems
 *
 * @role customize
 */
class CustomizeController extends StoreManagementController
{
	public function index()
	{
		$response = new ActionResponse();
		$response->set('isCustomizationModeEnabled', $this->application->isCustomizationMode());
		$response->set('isTranslationModeEnabled', $this->application->isTranslationMode());
		return $response;
	}

	public function translationMode()
	{
	  	if ($this->application->isTranslationMode())
	  	{
			$this->session->unsetValue('translationMode');
		}
		else
		{
			$this->session->set('translationMode', true);
		}

		return new ActionRedirectResponse('backend.customize', 'index');
	}

	public function mode()
	{

	  	if (($this->application->isCustomizationMode() && !$this->request->isValueSet('mode')) || ('exit' == $this->request->gget('mode')))
	  	{
			$this->session->unsetValue('customizationMode');
		}
		else
		{
			$this->session->set('customizationMode', true);
			$this->session->set('customizationModeType', $this->request->gget('mode', 'template'));
		}

		if (!$this->request->isValueSet('return'))
		{
			return new ActionRedirectResponse('backend.customize', 'index');
		}
		else
		{
			return new RedirectResponse($this->router->createUrlFromRoute($this->request->gget('return')));
		}
	}

	public function saveCss()
	{
		$params = json_decode($this->request->gget('result'), true);

		$theme = $params['theme'];

		if (!$theme)
		{
			$theme = 'barebone';
		}

		// save custom CSS
		$css = new CssFile('upload/css/' . $theme . '.css');
		$css->setSource($params['css']);
		$css->save();

		// deleted rules
		foreach ($params['deletedRules'] as $file => $selectors)
		{
			$css = CssFile::getInstanceFromUrl($file, $theme);
			foreach ($selectors as $selector)
			{
				$css->deleteSelector($selector);
			}

			$css->save();
		}

		// deleted properties
		foreach ($params['deletedProperties'] as $file => $selectors)
		{
			$css = CssFile::getInstanceFromUrl($file, $theme);
			foreach ($selectors as $selector => $properties)
			{
				foreach ($properties as $property => $value)
				{
					$css->deleteProperty($selector, $property);
				}
			}

			$css->save();
		}
	}

	public function changeTheme()
	{
		$this->session->set('customizationTheme', $this->getRequest()->get('theme'));
		return new JSONResponse(null, "success");
	}
}

?>