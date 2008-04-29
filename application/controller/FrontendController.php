<?php

ClassLoader::import("application.controller.BaseController");
ClassLoader::import("application.model.Currency");

/**
 * Base class for all front-end related controllers
 *
 * @author Integry Systems
 * @package application.controller
 */
abstract class FrontendController extends BaseController
{
	protected $breadCrumb = array();

	/**
	 *	Session order instance
	 */
	protected $order;

	public function __construct(LiveCart $application)
	{
		parent::__construct($application);

		$this->request->sanitizeAllData();

		unset($this->order);

		// variables to append automatically to all URLs
		foreach (array('currency', 'sort', 'layout') as $key)
		{
			if ($this->request->isValueSet($key))
			{
				$this->router->addAutoAppendQueryVariable($key, $this->request->get($key));
			}
		}
	}

	public function init()
	{
		parent::init();

		$this->setLayout('frontend');
		$this->addBlock('CATEGORY_BOX', 'boxCategory', 'block/box/category');
		$this->addBlock('ROOT_CATEGORIES', 'boxRootCategory', 'block/box/rootCategory');
		$this->addBlock('DYNAMIC_CATEGORIES', 'dynamicCategoryMenu', 'block/box/dynamicCategory');
		$this->addBlock('SALE_ITEMS', 'saleItems', 'block/box/saleItems');
		$this->addBlock('NEWEST_PRODUCTS', 'newestProducts', 'block/box/newestProducts');
		$this->addBlock('BREADCRUMB', 'boxBreadCrumb', 'block/box/breadcrumb');
		$this->addBlock('LANGUAGE', 'boxLanguageSelect', 'block/box/language');
		$this->addBlock('CURRENCY', 'boxSwitchCurrency', 'block/box/currency');
		$this->addBlock('CART', 'boxShoppingCart', 'block/box/shoppingCart');
		$this->addBlock('SEARCH', 'boxSearch', 'block/box/search');
		$this->addBlock('INFORMATION', 'boxInformationMenu', 'block/box/informationMenu');
		$this->addBlock('NEWSLETTER', 'boxNewsletterSubscribe', 'block/box/newsletterSubscribe');
		$this->addBlock('TRACKING', 'tracking', 'block/tracking');
	}

	public function getRequestCurrency()
	{
		$currency = Currency::getValidInstanceById($this->request->get('currency', $this->application->getDefaultCurrencyCode()));
		if ($currency)
		{
			return $currency->getID();
		}
	}

	protected function addBreadCrumb($title, $url)
	{
		$this->breadCrumb[] = array('title' => $title, 'url' => $url);
	}

	protected function boxInformationMenuBlock()
	{
		ClassLoader::import('application.model.staticpage.StaticPage');
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('StaticPage', 'isInformationBox'), true));
		$f->setOrder(new ARFieldHandle('StaticPage', 'position'));

		$response = new BlockResponse();
		$response->set('pages', ActiveRecordModel::getRecordSetArray('StaticPage', $f));
		unset($f);
		return $response;
	}

	protected function boxNewsletterSubscribeBlock()
	{
		if (!$this->user->isAnonymous())
		{
			return false;
		}

		ClassLoader::import('application.controller.NewsletterController');
		ClassLoader::import('framework.request.validator.Form');
		return new BlockResponse('form', new Form(NewsletterController::getSubscribeValidator()));
	}

	protected function boxShoppingCartBlock()
	{
		ClassLoader::import('application.model.order.CustomerOrder');
		$response = new BlockResponse();

		$response->set('order', $this->order->toArray());
		$response->set('currency', $this->request->get('currency', $this->application->getDefaultCurrencyCode()));

		return $response;
	}

	protected function boxSwitchCurrencyBlock()
	{
		$returnRoute = $this->router->getRequestedRoute();
		$returnRoute = $this->router->createUrlFromRoute($returnRoute, true);
		$returnRoute = $this->router->setUrlQueryParam($returnRoute, 'currency', '_curr_');

		$current = $this->getRequestCurrency();
		$currencies = $this->application->getCurrencySet();
		$currencyArray = array();
		foreach ($currencies as $currency)
		{
			if ($currency->getID() != $current)
			{
				$currencyArray[$currency->getID()] = $currency->toArray();
				$currencyArray[$currency->getID()]['url'] = str_replace('_curr_', $currency->getID(), $returnRoute);
			}
		}

		$response = new BlockResponse();
		$response->set('currencies', $currencyArray);
		$response->set('current', $current);
		return $response;
	}

	protected function boxLanguageSelectBlock()
	{
		$languages = $this->application->getLanguageSetArray(true, false);
		$current = $this->application->getLocaleCode();

		$returnRoute = $this->router->getRequestedRoute();

		if ('/' == substr($returnRoute, 2, 1))
		{
		  	$returnRoute = substr($returnRoute, 3);
		}

		$response = new BlockResponse();

		foreach ($languages as $key => $lang)
		{
			if ($lang['ID'] == $current)
			{
				$defKey = $key;
				$response->set('current', $lang);
			}
			else
			{
				// changing language from the index page
				if (strlen($returnRoute) == 2)
				{
					$returnRoute = '';
				}

				$languages[$key]['url'] = $this->router->createUrlFromRoute($lang['ID'] . '/' . $returnRoute, true);
			}
		}

		$response->set('allLanguages', $languages);

		unset($languages[$defKey]);
		$response->set('languages', $languages);

		return $response;
	}

	protected function boxBreadCrumbBlock()
	{
		$home = array('controller' => 'index', 'action' => 'index');
		if ($this->locale->getLocaleCode() != $this->application->getDefaultLanguageCode())
		{
			$home['requestLanguage'] = $this->locale->getLocaleCode();
		}

		array_unshift($this->breadCrumb, array('title' => $this->config->get('STORE_NAME'),
											   'url' => $this->router->createUrl($home, true)));

		$response = new BlockResponse();
		$response->set('breadCrumb', $this->breadCrumb);
		return $response;
	}

	protected function boxSearchBlock()
	{
		ClassLoader::import('application.model.category.Category');

		if ($this->categoryID < 1)
		{
		  	$this->categoryID = Category::ROOT_ID;
		}

		$category = Category::getInstanceById($this->categoryID, Category::LOAD_DATA);
		$subCategories = $category->getSubCategorySet();

		$search = array();

		do
		{
			if (isset($parent))
			{
				$search[] = $parent->toArray();
			}
			else
			{
				$parent = $category;
			}

			$parent = $parent->parentNode->get();
		}
		while ($parent && ($parent->getID() > Category::ROOT_ID));

		if ($subCategories)
		{
			if ($category->getID() != Category::ROOT_ID)
			{
				$search[] = $category->toArray();
			}

			foreach ($subCategories as $category)
			{
				$search[] = $category->toArray();
			}
		}

		if (!$search)
		{
			$category = Category::getInstanceById(Category::ROOT_ID, Category::LOAD_DATA);
			$subCategories = $category->getSubCategorySet();

			foreach ($subCategories as $category)
			{
				$search[] = $category->toArray();
			}
		}

		$options = array(1 => $this->translate('_all_products'));

		foreach ($search as $cat)
		{
			$options[$cat['ID']] = $cat['name_lang'];
		}

		ClassLoader::import("framework.request.validator.Form");
		$form = new Form(new RequestValidator("productSearch", $this->request));
		$form->enableClientSideValidation(false);
		$form->set('id', $this->categoryID);
		$form->set('q', $this->request->get('q'));

		$response = new BlockResponse();
		$response->set('categories', $options);
		$response->set('form', $form);
		return $response;
	}

	protected function boxCategoryBlock()
	{
		ClassLoader::import('application.model.category.Category');

		if ($this->categoryID < 1)
		{
		  	$this->categoryID = 1;
		}

		// get top categories
		$rootCategory = Category::getInstanceByID(1);
		$topCategories = $rootCategory->getSubcategoryArray();
		$currentCategory = Category::getInstanceByID($this->categoryID, Category::LOAD_DATA);

		// get path of the current category (except for top categories)
		if (!(1 == $currentCategory->getID()) && (1 < $currentCategory->parentNode->get()->getID()))
		{
			$path = $currentCategory->getPathNodeSet(false)->toArray();

			$topCategoryId = $path[0]['ID'];
			unset($path[0]);
		}
		else
		{
		  	$topCategoryId = $this->categoryID;
		}

		$this->topCategoryId = $topCategoryId;
		$this->topCategories = $topCategories;

		foreach ($topCategories as &$cat)
		{
		  	if ($topCategoryId == $cat['ID'])
		  	{
				$current =& $cat;
			}
		}

		// get sibling (same-level) categories (except for top categories)
		if (!(1 == $currentCategory->getID()) && (1 < $currentCategory->parentNode->get()->getID()))
		{
			$siblings = $currentCategory->getSiblingArray();

			foreach ($path as &$node)
			{
			  	if ($node['ID'] != $this->categoryID)
			  	{
					$current['subCategories'] = array(0 => &$node);
				  	$current =& $node;
				}
				else
				{
					$current['subCategories'] =& $siblings;
					foreach ($current['subCategories'] as &$sib)
					{
					  	if ($sib['ID'] == $this->categoryID)
					  	{
							$current =& $sib;
						}
					}
				}
			}
		}

		// get subcategories of the current category (except for the root category)
		if ($this->categoryID > 1)
		{
			$subcategories = $currentCategory->getSubcategorySet()->toArray();

			if ($subcategories)
			{
				$current['subCategories'] = $subcategories;
			}
		}

		$response = new BlockResponse();
		$response->set('categories', $topCategories);
		$response->set('currentId', $this->categoryID);
		$response->set('lang', 'en');
		return $response;
	}

	protected function dynamicCategoryMenuBlock()
	{
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('Category', 'isEnabled'), true));
		$categories = ActiveRecordModel::getRecordSetArray('Category', Category::getRootNode()->getBranchFilter($f));

		$tree = array(1 => array('subCategories' => array()));
		foreach ($categories as $key => &$category)
		{
			$tree[$category['ID']] =& $category;
		}

		foreach ($categories as &$category)
		{
			$tree[$category['parentNodeID']]['subCategories'][] =& $category;
		}

		$tree = $tree[1]['subCategories'];

		if ($this->categoryID < 1)
		{
		  	$this->categoryID = 1;
		}

		$response = new BlockResponse('categories', $tree);

		$path = Category::getInstanceById($this->categoryID)->getPathNodeSet(false)->toArray();
		if ($path)
		{
			$response->set('topCategoryId', $path[0]['ID']);
		}

		$response->set('currentId', $this->categoryID);

		return $response;
	}

	protected function boxRootCategoryBlock()
	{
		$response = new BlockResponse();
		$response->set('categories', $this->topCategories);
		$response->set('currentId', $this->topCategoryId);
		return $response;
	}

	protected function saleItemsBlock($useRoot = false)
	{
		ClassLoader::import('application.model.product.ProductFilter');

		if ($useRoot || $this->categoryID < 1)
		{
		  	$this->categoryID = Category::ROOT_ID;
		}

		$category = Category::getInstanceById($this->categoryID, Category::LOAD_DATA);
		$filter = new ProductFilter($category, new ARSelectFilter(new EqualsCond(new ARFieldHandle('Product', 'isFeatured'), true)));
		$filter->includeSubcategories();
		$filter->setEnabledOnly();

		$selectFilter = $filter->getSelectFilter();
		$selectFilter->setLimit($this->config->get('SALE_ITEMS_COUNT'));
		$selectFilter->setOrder(new ARExpressionHandle('RAND()'));

		$products = ActiveRecord::getRecordSetArray('Product', $selectFilter, array('Category', 'DefaultImage' => 'ProductImage'));

		ProductPrice::loadPricesForRecordSetArray($products);

		if ($products)
		{
			return new BlockResponse('products', $products);
		}
		else if (!$category->isRoot())
		{
			return $this->saleItemsBlock(true);
		}
	}

	protected function newestProductsBlock($useRoot = false)
	{
		ClassLoader::import('application.model.product.ProductFilter');

		if ($useRoot || $this->categoryID < 1)
		{
		  	$this->categoryID = Category::ROOT_ID;
		}

		$category = Category::getInstanceById($this->categoryID, Category::LOAD_DATA);
		$filter = new ProductFilter($category, new ARSelectFilter());
		$filter->includeSubcategories();
		$filter->setEnabledOnly();

		$selectFilter = $filter->getSelectFilter();
		$selectFilter->setLimit($this->config->get('NEWEST_ITEMS_COUNT'));
		$selectFilter->setOrder(new ARFieldHandle('Product', 'dateCreated'), 'DESC');

		$products = ActiveRecord::getRecordSetArray('Product', $selectFilter, array('Category', 'DefaultImage' => 'ProductImage'));

		ProductPrice::loadPricesForRecordSetArray($products);

		if ($products)
		{
			return new BlockResponse('products', $products);
		}
		else if (!$category->isRoot())
		{
			return $this->newestProductsBlock(true);
		}
	}

	protected function trackingBlock()
	{
		$code = array();

		if (!$this->config->get('TRACKING_SERVICES'))
		{
			return false;
		}

		foreach ($this->config->get('TRACKING_SERVICES') as $class => $enabled)
		{
			ClassLoader::import('library.tracking.method.' . $class);

			$data = array();

			foreach ($this->config->getSection('tracking/' . $class) as $key => $value)
			{
				$value = $this->config->get($key);
				$key = substr($key, strlen($class) + 1);
				$data[$key] = $value;
			}

			$tracker = new $class($data, $this);
			$code[$class] = $tracker->getHtml();
		}

		return new BlockResponse('code', $code);
	}

	/**
	 *  Recursively applies all selected filters to applicable categories
	 */
	private function applyFilters(&$categories, $parentFilterIds)
	{
		foreach ($categories as &$category)
		{
		  	$categoryFilters = $parentFilterIds;
			foreach ($this->filters as $filter)
		  	{
				if (!($filter instanceof SpecificationFilterInterface) ||
					$filter->getSpecField()->category->get()->getID() == $category['ID'])
				{
					$categoryFilters[$filter->getID()] = true;
				}

				if (isset($categoryFilters[$filter->getID()]))
				{
					$category['filters'][] = $filter;
				}
			}

			if (isset($category['subCategories']))
			{
			  	$this->applyFilters($category['subCategories'], $categoryFilters);
			}
		}
	}

	protected function __get($name)
	{
		if ($inst = parent::__get($name))
		{
			return $inst;
		}

		switch ($name)
	  	{
			case 'order':
				ClassLoader::import('application.model.order.SessionOrder');
				$this->order = SessionOrder::getOrder();

				// check if order currency matches the request currency
				if (!$this->order->currency->get() || ($this->order->currency->get()->getID() != $this->getRequestCurrency()))
				{
					$this->order->changeCurrency(Currency::getInstanceByID($this->getRequestCurrency()));
				}

				return $this->order;
			break;

			default:
			break;
		}
	}

	public function __destruct()
	{
		if (isset($this->order))
		{
			$this->order->__destruct();
			unset($this->order);
		}
	}
}

?>
