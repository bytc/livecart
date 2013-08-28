<?php

/**
 *  Handles user account related logic
 *
 *  @author Integry Systems
 *  @package application.controller
 */
class UserController extends FrontendController
{
 	const PASSWORD_MIN_LENGTH = 5;

 	const COUNT_RECENT_FILES = 5;

 	public function initAction()
 	{
 		$initRes = parent::init();

 		$this->loadLanguageFile('Frontend');
 		$this->loadLanguageFile('User');

 		if ($this->user->getID())
 		{
			$this->user->load();
		}

		return $initRes;
	}

	private function addAccountBreadcrumb()
	{
		$this->addBreadCrumb($this->translate('_your_account'), $this->router->createUrl(array('controller' => 'user'), true));
	}

	private function addAddressBreadcrumb()
	{
		$this->addBreadCrumb($this->translate('_manage_addresses'), $this->router->createUrl(array('controller' => 'user', 'action' => 'addresses'), true));
	}

	private function addFilesBreadcrumb()
	{
		$this->addBreadCrumb($this->translate('_your_files'), $this->router->createUrl(array('controller' => 'user', 'action' => 'files'), true));
	}

	/**
	 *	@role login
	 */
	public function indexAction()
	{
		$this->addAccountBreadcrumb();

		// get recent orders
		$f = new ARSelectFilter();
		$f->setLimit($this->config->get('USER_COUNT_RECENT_ORDERS'));
		$f->setCondition(new IsNullCond(new ARFieldHandle('CustomerOrder', 'parentID')));
		$orders = $this->loadOrders($f);
		$orderArray = $this->getOrderArray($orders);

		// get last invoice & unpaid count
		$pendingInvoiceCount = $this->user->countPendingInvoices();

		$lastInvoiceArray = array();
		if ($pendingInvoiceCount)
		{
			$f = new ARSelectFilter();
			$f->setLimit(1);
			$f->setCondition(new AndChainCondition(array(
				new IsNotNullCond(new ARFieldHandle('CustomerOrder', 'parentID')))
			));
			$lastInvoiceArray = $this->getOrderArray($this->loadOrders($f));
		}

		// get downloadable items
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));
		$f->setLimit(self::COUNT_RECENT_FILES);

		$response = new ActionResponse();

		$response->set('orders', $orderArray);
		$response->set('files', $this->loadDownloadableItems(new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()))));

		// get unread messages
				$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('OrderNote', 'isAdmin'), 1));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('OrderNote', 'isRead'), 0));
		$f->setOrder(new ARFieldHandle('OrderNote', 'ID'), 'DESC');
		$response->set('notes', ActiveRecordModel::getRecordSetArray('OrderNote', $f, array('User', 'CustomerOrder')));

		// feedback/confirmation message that was stored in session by some other action
		$response->set('userConfirm', $this->session->pullValue('userConfirm'));
		$response->set('pendingInvoiceCount', $pendingInvoiceCount);
		$response->set('lastInvoiceArray', $lastInvoiceArray);

		return $response;
	}

	/**
	 *	@role login
	 */
	public function ordersAction()
	{
		$this->addAccountBreadcrumb();
		$this->addBreadCrumb($this->translate('_your_orders'), '');

		$page = $this->request->gget('id', 1);
		$perPage = $this->getOrdersPerPage();
		$f = $this->getOrderListPaginateFilter($page, $perPage);
		$f->setCondition(new IsNullCond(new ARFieldHandle('CustomerOrder','parentID')));
		return $this->getOrdersListResponse($this->loadOrders($f), $page, $perPage);
	}

	/**
	 *	@role login
	 */
	public function invoicesAction()
	{
		$page = $this->request->gget('id', 1);
		$perPage = $this->getOrdersPerPage();
		$f = $this->getOrderListPaginateFilter($page, $perPage);
		$f->mergeCondition(
			new AndChainCondition(array(
				new IsNotNullCond(new ARFieldHandle('CustomerOrder','parentID')),
				new EqualsCond(new ARFieldHandle('CustomerOrder','isRecurring'), 1)
			))
		);
		$f->setOrder(new ARFieldHandle('CustomerOrder','dateDue'));
		return $this->getOrdersListResponse($this->loadOrders($f), $page, $perPage);
	}

	/**
	 *	@role login
	 */
	public function pendingInvoicesAction()
	{
		$page = $this->request->gget('id', 1);
		$perPage = $this->getOrdersPerPage();
		$f = $this->getOrderListPaginateFilter($page, $perPage);
		$f->mergeCondition(
			new AndChainCondition(array(
				new IsNotNullCond(new ARFieldHandle('CustomerOrder','parentID')),
				new EqualsCond(new ARFieldHandle('CustomerOrder','isRecurring'), 1),
				new EqualsCond(new ARFieldHandle('CustomerOrder','isPaid'), 0)
			))
		);
		$f->setOrder(new ARFieldHandle('CustomerOrder','dateDue'));
		return $this->getOrdersListResponse($this->loadOrders($f), $page, $perPage);
	}

	private function getOrdersListResponse($orders, $page, $perPage)
	{
		$orderArray = $this->getOrderArray($orders);
		$today = strtotime(date('Y-m-d', time()));
		foreach ($orderArray as $k=>$order)
		{
			if ($orderArray[$k]['isRecurring'])
			{
				$orderArray[$k]['overdue'] = $today > strtotime(date('Y-m-d',strtotime($order['dateDue'])));
			}
		}
		$response = new ActionResponse();
		$response->set('from', ($perPage * ($page - 1)) + 1);
		$response->set('to', min($perPage * $page, $orders->getTotalRecordCount()));
		$response->set('count', $orders->getTotalRecordCount());
		$response->set('currentPage', $page);
		$response->set('perPage', $perPage);
		$response->set('user', $this->user->toArray());
		$response->set('orders', $orderArray);

		return $response;
	}

	private function getOrdersPerPage()
	{
		$perPage = $this->config->get('USER_ORDERS_PER_PAGE');
		if (!$perPage)
		{
			$perPage = 100000;
		}
		return $perPage;
	}

	private function getOrderListPaginateFilter($page, $perPage, $filter = null)
	{
		if ($filter == null)
		{
			$filter = new ARSelectFilter();
		}
		$filter->setLimit($perPage, ($page - 1) * $perPage);

		return $filter;
	}

	private function loadOrders(ARSelectFilter $f)
	{
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'isFinalized'), 1));
		$f->setOrder(new ARFieldHandle('CustomerOrder', 'dateCompleted'), 'DESC');

		$orders = ActiveRecordModel::getRecordSet('CustomerOrder', $f);

		foreach ($orders as $order)
		{
			$order->loadAll();
		}

		return $orders;
	}

	private function getOrderArray(ARSet $orders)
	{
		$orderArray = $orders->toArray();

		$ids = array();
		foreach ($orderArray as $key => $order)
		{
			$ids[$order['ID']] = $key;
		}


		$f = new ARSelectFilter(new INCond(new ARFieldHandle('OrderNote', 'orderID'), empty($ids) ? array(-1) : array_keys($ids)));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('OrderNote', 'isAdmin'), 1));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('OrderNote', 'isRead'), 0));
		$f->setGrouping(new ARFieldHandle('OrderNote', 'orderID'));

	  	$query = new ARSelectQueryBuilder();
	  	$query->setFilter($f);
	  	$query->includeTable('OrderNote');
		$query->removeFieldList();
		$query->addField('COUNT(*)', null, 'cnt');
		$query->addField('orderID');

		foreach (ActiveRecordModel::getDataBySQL($query->getPreparedStatement(ActiveRecord::getDBConnection())) as $res)
		{
			$orderArray[$ids[$res['orderID']]]['unreadMessageCount'] = $res['cnt'];
		}

		return $orderArray;
	}

	/**
	 *	@role login
	 */
	public function filesAction()
	{
		$this->addAccountBreadcrumb();
		$this->addFilesBreadcrumb();

		$response = new ActionResponse();
		$response->set('user', $this->user->toArray());
		$response->set('files', $this->loadDownloadableItems(new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()))));
		return $response;
	}

	/**
	 *	@role login
	 */
	public function itemAction()
	{
		$item = ActiveRecordModel::getInstanceById('OrderedItem', $this->request->gget('id'), ActiveRecordModel::LOAD_DATA, OrderedItem::LOAD_REFERENCES);
		$item->customerOrder->get()->loadAll();
		$item->loadOptions();
		$subItems = $item->getSubitems();
		$item = $item->toArray();

		$this->addAccountBreadcrumb();
		$this->addFilesBreadcrumb();
		$this->addBreadCrumb(isset($item['Product']['name_lang']) ? $item['Product']['name_lang'] : $item['Product']['Parent']['name_lang'], '');

		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('OrderedItem', 'ID'), $item['ID']));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));

		$fileArray = $this->loadDownloadableItems($f);
		if (!$fileArray && !$subItems)
		{
			return new ActionRedirectResponse('user', 'index');
		}

		$response = new ActionResponse();
		$response->set('user', $this->user->toArray());
		$response->set('files', $fileArray);
		$response->set('item', $item);

		if ($subItems)
		{
			$response->set('subItems', $subItems->toArray());
		}

		return $response;
	}

	private function loadDownloadableItems(ARSelectFilter $f)
	{
				return ProductFile::getOrderFiles($f);
	}

	/**
	 *	@role login
	 */
	public function changePasswordAction()
	{
		$this->addAccountBreadcrumb();

		$this->addBreadCrumb($this->translate('_change_pass'), '');
		$response = new ActionResponse();
		$response->set('user', $this->user->toArray());
		$response->set('form', $this->buildPasswordChangeForm());
		return $response;
	}

	/**
	 *	@role login
	 */
	public function doChangePasswordAction()
	{
		if (!$this->buildPasswordChangeValidator()->isValid())
		{
			return new ActionRedirectResponse('user', 'changePassword');
		}

		$this->user->setPassword($this->request->gget('password'));
		$this->user->save();

		$this->session->set('userConfirm', $this->translate('_confirm_password_change'));

		return new ActionRedirectResponse('user', 'index');
	}

	/**
	 *	@role login
	 */
	public function changeEmailAction()
	{
		$this->addAccountBreadcrumb();

		$this->addBreadCrumb($this->translate('_change_email'), '');
		$response = new ActionResponse();
		$response->set('user', $this->user->toArray());
		$response->set('form', $this->buildEmailChangeForm());
		return $response;
	}

	/**
	 *	@role login
	 */
	public function doChangeEmailAction()
	{
		if (!$this->buildEmailChangeValidator()->isValid())
		{
			return new ActionRedirectResponse('user', 'changeEmail');
		}

		$this->user->email->set($this->request->gget('email'));
		$this->user->save();

		$this->session->set('userConfirm', $this->translate('_confirm_email_change'));

		return new ActionRedirectResponse('user', 'index');
	}

	/**
	 *	@role login
	 */
	public function personalAction()
	{
		$this->addAccountBreadcrumb();
		$this->addBreadcrumb($this->translate('_personal_info'), '');

		$form = $this->buildPersonalDataForm($this->user);
		$response = new ActionResponse('form', $form);
		$this->user->getSpecification()->setFormResponse($response, $form);

		return $response;
	}

	/**
	 *	@role login
	 */
	public function savePersonalAction()
	{
		$validator = $this->buildPersonalDataValidator($this->user);
		if (!$validator->isValid())
		{
			return new ActionRedirectResponse('user', 'personal');
		}

		$this->user->loadRequestData($this->request, array('firstName', 'lastName', 'companyName'));
		$this->user->save();

		$this->setMessage($this->translate('_personal_data_saved'));

		return new ActionRedirectResponse('user', 'index');
	}

	/**
	 *	@role login
	 */
	public function addressesAction()
	{
		$this->addAccountBreadcrumb();
		$this->addAddressBreadcrumb();

		$response = new ActionResponse();
		$response->set('user', $this->user->toArray());
		$response->set('billingAddresses', $this->user->getBillingAddressArray());
		$response->set('shippingAddresses', $this->user->getShippingAddressArray());
		return $response;
	}

	/**
	 *	@role login
	 */
	public function viewOrderAction()
	{
		$id = $this->request->gget('id');
		if ($order = $this->user->getOrder($id))
		{
			$this->addAccountBreadcrumb();
			$this->addBreadCrumb($this->translate('_your_orders'), $this->router->createUrl(array('controller' => 'user', 'action' => 'orders'), true));
			$this->addBreadCrumb($order->invoiceNumber->get(), '');

			// mark all notes as read
			$notes = $order->getNotes();
			foreach ($notes as $note)
			{
				if (!$note->isRead->get() && $note->isAdmin->get())
				{
					$note->isRead->set(true);
					$note->save();
				}
			}

			$response = null;
			$orderArray = $order->toArray();

			if ($order->isRecurring->get() == true)
			{
				// find invoices
				$page = $this->request->gget('page', 1);
				$perPage = $this->getOrdersPerPage();
				$f = $this->getOrderListPaginateFilter($page, $perPage);
				$f->mergeCondition(
					new AndChainCondition(array(
						new EqualsCond(new ARFieldHandle('CustomerOrder','parentID'), $order->getID()),
						new EqualsCond(new ARFieldHandle('CustomerOrder','isRecurring'), 1)
					))
				);
				$f->setOrder(new ARFieldHandle('CustomerOrder','dateDue'));
				$response = $this->getOrdersListResponse($this->loadOrders($f), $page, $perPage);

				$recurringProductPeriods = array();
				foreach ($order->getShipments() as $shipment)
				{
					foreach ($shipment->getItems() as $orderedItem)
					{
						$recurringProductPeriods[$orderedItem->getID()] =  RecurringItem::getInstanceByOrderedItem($orderedItem) -> toArray();
					}
				}
												$response->set('nextRebillDate', $order->getNextRebillDate());
				$response->set('periodTypesPlural', RecurringProductPeriod::getAllPeriodTypes(RecurringProductPeriod::PERIOD_TYPE_NAME_PLURAL));
				$response->set('periodTypesSingle', RecurringProductPeriod::getAllPeriodTypes(RecurringProductPeriod::PERIOD_TYPE_NAME_SINGLE));
				$response->set('recurringProductPeriodsByItemId', $recurringProductPeriods);
				$this->loadLanguageFile('Product');
			}

			if (!$response)
			{
				$response = new ActionResponse();
			}

			$response->set('subscriptionStatus', $order->getSubscriptionStatus());
			$response->set('canCancelRebills', $order->canUserCancelRebills());
			$response->set('order', $orderArray);
			$response->set('notes', $notes->toArray());
			$response->set('user', $this->user->toArray());
			$response->set('noteForm', $this->buildNoteForm());

			return $response;
		}
		else
		{
			return new ActionRedirectResponse('user', 'index');
		}
	}

	/**
	 *	@role login
	 */
	public function reorderAction()
	{
		$order = $this->user->getOrder($this->request->gget('id'));
		if ($order)
		{
						$newOrder = clone $order;
			SessionOrder::save($newOrder);
			return new ActionRedirectResponse('order', 'index');
		}
		else
		{
			return new ActionRedirectResponse('user', 'index');
		}
	}

	/**
	 *	@role login
	 */
	public function addNoteAction()
	{

		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'ID'), $this->request->gget('id')));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));
		$set = ActiveRecordModel::getRecordSet('CustomerOrder', $f);
		if (!$set->size() || !$this->buildNoteValidator()->isValid())
		{
			return new ActionRedirectResponse('user', 'index');
		}

		$order = $set->get(0);
		$note = OrderNote::getNewInstance($order, $this->user);
		$note->text->set($this->request->gget('text'));
		$note->isAdmin->set(false);
		$note->save();

		if ($this->config->get('NOTIFY_NEW_NOTE'))
		{
			$order->user->get()->load();

			$email = new Email($this->application);
			$email->setTo($this->config->get('NOTIFICATION_EMAIL'), $this->config->get('STORE_NAME'));
			$email->setTemplate('notify.message');
			$email->set('order', $order->toArray(array('payments' => true)));
			$email->set('message', $note->toArray());
			$email->set('user', $this->user->toArray());
			$email->send();
		}

		return new ActionRedirectResponse('user', 'viewOrder', array('id' => $order->getID()));
	}

	/**
	 *	@role login
	 */
	public function orderInvoiceAction()
	{
		$this->addAccountBreadcrumb();
		$this->application->setTheme('');

		$order = $this->getOrder($this->request->gget('id'));

		if (!$order)
		{
			return new ActionRedirectResponse('user', 'index');
		}

		$response = new ActionResponse();
		$response->set('order', $order->toArray(array('payments' => true)));
		$response->set('user', $this->user->toArray());
		return $response;
	}

	public function registerAction()
	{
		if ($this->config->get('REQUIRE_REG_ADDRESS'))
		{
			return new ActionRedirectResponse('user', 'registerAddress');
		}

		$form = $this->buildRegForm();
		$response = new ActionResponse('regForm', $form);

		SessionUser::getAnonymousUser()->getSpecification()->setFormResponse($response, $form);

		return $response;
	}

	public function registerAddressAction()
	{
		$this->request->set('return', 'user/index');
		$response = $this->checkout();
		$response->get('form')->set('regType', 'register');
		return $response;
	}

	public function doRegisterAction()
	{
		if (!$this->buildRegValidator()->isValid())
		{
			return new ActionRedirectResponse('user', 'register');
		}

		$this->order;

		$user = $this->createUser($this->request->gget('password'));
		$this->user = $user;
		$this->mergeOrder();

		if (!$this->config->get('REG_EMAIL_CONFIRM'))
		{
			if ($this->request->gget('return'))
			{
				return new RedirectResponse($this->request->gget('return'));
			}
			else
			{
				return new ActionRedirectResponse('user', 'index');
			}
		}
		else
		{
			return new ActionRedirectResponse('user', 'unconfirmed');
		}
	}

	public function unconfirmedAction()
	{
		return new ActionResponse();
	}

	public function confirmAction()
	{
		$success = false;

		$user = User::getInstanceByEmail($this->request->gget('email'));
		if ($user && !$user->isEnabled->get() && $user->getPreference('confirmation'))
		{
			if ($this->request->gget('code') == $user->getPreference('confirmation'))
			{
				$user->setPreference('confirmation', null);
				$user->isEnabled->set(true);
				$user->save();

				SessionUser::setUser($user);

				$success = true;

				$this->sendWelcomeEmail($user);
			}
		}

		return new ActionResponse('success', $success);
	}

	/**
	 *  Login form
	 */
	public function loginAction()
	{
		if ($this->config->get('REQUIRE_REG_ADDRESS'))
		{
			return new ActionRedirectResponse('user', 'registerAddress');
		}

		$this->addBreadCrumb($this->translate('_login'), $this->router->createUrl(array('controller' => 'user', 'action' => 'login'), true));

		$form = $this->buildRegForm();
		$response = new ActionResponse();
		$response->set('regForm', $form);
		$response->set('email', $this->request->gget('email'));
		$response->set('failed', $this->request->gget('failed'));
		$response->set('return', $this->request->gget('return'));

		SessionUser::getAnonymousUser()->getSpecification()->setFormResponse($response, $form);

		return $response;
	}

	/**
	 *  Process actual login
	 */
	public function doLoginAction()
	{
		$user = User::getInstanceByLogin($this->request->gget('email'), $this->request->gget('password'));
		if (!$user)
		{
			return new ActionRedirectResponse('user', 'login', array('query' => 'failed=true'));
		}

		// login
		SessionUser::setUser($user);

		$this->user = $user;
		$this->mergeOrder();

		if ($return = $this->request->gget('return'))
		{
			if ((substr($return, 0, 1) != '/') && (!strpos($return, ':')))
			{
				$return = $this->router->createUrlFromRoute($return);
			}

			return new RedirectResponse($return);
		}
		else
		{
			return new ActionRedirectResponse('user', 'index');
		}
	}

	private function mergeOrder()
	{
		// load the last un-finalized order by this user
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));
		$f->mergeCondition(new NotEqualsCond(new ARFieldHandle('CustomerOrder', 'isFinalized'), true));
		$f->setOrder(new ARFieldHandle('CustomerOrder', 'dateCreated'), 'DESC');
		$f->setLimit(1);
		$s = ActiveRecordModel::getRecordSet('CustomerOrder', $f, ActiveRecordModel::LOAD_REFERENCES);

		if (!$this->order->user->get() || $this->order->user->get()->getID() == $this->user->getID())
		{
			if ($s->size())
			{
				$order = $s->get(0);
				if ($this->order->getID() != $order->getID())
				{
					$sessionOrder = SessionOrder::getOrder();
					$order->loadItems();
					$order->merge($sessionOrder);
					$order->save();
					SessionOrder::setOrder($order);
					$this->order->delete();
				}
			}
			else
			{
				if ($this->order->getID())
				{
					$this->order->setUser($this->user);
					SessionOrder::save($this->order);
				}
			}
		}
	}

	public function remindPasswordAction()
	{
		$this->addBreadCrumb($this->translate('_remind_password'), $this->router->createUrl(array('controller' => 'user', 'action' => 'login'), true));

		$response = new ActionResponse();
		$response->set('form', $this->buildPasswordReminderForm());
		$response->set('return', $this->request->gget('return'));
		return $response;
	}

	public function doRemindPasswordAction()
	{
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('User', 'email'), $this->request->gget('email')));
		$s = ActiveRecordModel::getRecordSet('User', $f);
		if ($s->size())
		{
			$user = $s->get(0);
			$user->setPassword($user->getAutoGeneratedPassword());
			$user->save();

			$email = new Email($this->application);
			$email->setUser($user);
			$email->setTemplate('user.remindPassword');
			$email->send();
		}

		return new ActionRedirectResponse('user', 'remindComplete', array('query' => 'email=' . $this->request->gget('email')));
	}

	public function remindCompleteAction()
	{
		$response = new ActionResponse();
		$response->set('email', $this->request->gget('email'));
		return $response;
	}

	public function logoutAction()
	{
		SessionUser::destroy();
		return new ActionRedirectResponse('index', 'index');
	}

	public function checkoutAction()
	{
		if ($this->config->get('DISABLE_GUEST_CHECKOUT') && !$this->config->get('REQUIRE_REG_ADDRESS'))
		{
			return new ActionRedirectResponse('user', 'login', array('query' => array('return' => $this->router->createUrl(array('controller' => 'checkout', 'action' => 'pay')))));
		}

		$form = $this->buildForm();

		$form->set('billing_country', $this->config->get('DEF_COUNTRY'));
		$form->set('shipping_country', $this->config->get('DEF_COUNTRY'));
		$form->set('return', $this->request->gget('return'));

		$response = new ActionResponse();
		$response->set('form', $form);
		$response->set('countries', $this->getCountryList($form));
		$response->set('states', $this->getStateList($form->get('billing_country')));
		$response->set('shippingStates', $this->getStateList($form->get('shipping_country')));
		$response->set('order', $this->order->toArray());

		SessionUser::getAnonymousUser()->getSpecification()->setFormResponse($response, $form);
		UserAddress::getNewInstance()->getSpecification()->setFormResponse($response, $form, 'billing_');
		UserAddress::getNewInstance()->getSpecification()->setFormResponse($response, $form, 'shipping_');

		return $response;
	}

	public function processCheckoutRegistrationAction()
	{
		ActiveRecordModel::beginTransaction();

		$validator = $this->buildValidator();
		if (!$validator->isValid())
		{
			$action = $this->request->gget('regType') == 'register' ? 'registerAddress' : 'checkout';
			return new ActionRedirectResponse('user', $action, array('query' => array('return' => $this->request->gget('return'))));
		}

		// create user account
		$user = $this->createUser($this->request->gget('password'), 'billing_');

		// create billing and shipping address
		$address = $this->createAddress('billing_');
		$billingAddress = BillingAddress::getNewInstance($user, $address);
		$billingAddress->save();

		$shippingAddress = ShippingAddress::getNewInstance($user, $this->request->gget('sameAsBilling') ? clone $address : $this->createAddress('shipping_'));
		$shippingAddress->save();

		$user->defaultShippingAddress->set($shippingAddress);
		$user->defaultBillingAddress->set($billingAddress);
		$user->save();

		// set order addresses
		$this->order->billingAddress->set($billingAddress->userAddress->get());

		$this->order->loadItems();
		if ($this->order->isShippingRequired())
		{
			$this->order->shippingAddress->set($shippingAddress->userAddress->get());
		}

		$this->order->save();
		$this->order->setUser($user);
		SessionOrder::save($this->order);

		ActiveRecordModel::commit();

		if ($return = $this->request->gget('return'))
		{
			return new RedirectResponse($this->router->createUrlFromRoute($return));
		}
		else
		{
			return new ActionRedirectResponse('checkout', 'shipping');
		}
	}

	private function createAddress($prefix)
	{
		// get address state
		if ($this->request->gget($prefix . 'state_select'))
		{
			try
			{
				$state = ActiveRecordModel::getInstanceByID('State', $this->request->gget($prefix . 'state_select'), ActiveRecordModel::LOAD_DATA);
			}
			catch (Exception $e)
			{
				throw new ApplicationException('State not found');
			}

			$country = $state->countryID->get();
		}
		else
		{
			$country = $this->request->gget($prefix . 'country');
		}

		$address = UserAddress::getNewInstance();
		$address->loadRequestData($this->request, $prefix);

		if (isset($state))
		{
			$address->state->set($state);
		}
		else
		{
			$address->stateName->set($this->request->gget($prefix . 'state_text'));
		}

		$address->countryID->set($country);
		$address->save();

		return $address;
	}

	/**
	 *	@role login
	 */
	public function deleteShippingAddressAction()
	{
		try
		{
			return $this->deleteAddress(ShippingAddress::getUserAddress($this->request->gget('id'), $this->user));
		}
		catch (ARNotFoundException $e)
		{
			return new ActionRedirectResponse('user', 'index');
		}
	}

	/**
	 *	@role login
	 */
	public function deleteBillingAddressAction()
	{
		try
		{
			return $this->deleteAddress(BillingAddress::getUserAddress($this->request->gget('id'), $this->user));
		}
		catch (ARNotFoundException $e)
		{
			return new ActionRedirectResponse('user', 'index');
		}
	}

	private function deleteAddress(UserAddressType $address)
	{
		$address->delete();
		return new RedirectResponse($this->router->createURLFromRoute($this->request->gget('return')));
	}

	/**
	 *	@role login
	 */
	public function editShippingAddressAction()
	{
		try
		{
			$response = $this->editAddress(ShippingAddress::getUserAddress($this->request->gget('id'), $this->user));
			$this->addBreadCrumb($this->translate('_edit_shipping_address'), '');
			return $response;
		}
		catch (ARNotFoundException $e)
		{
			return new ActionRedirectResponse('user', 'index');
		}
	}

	/**
	 *	@role login
	 */
	public function editBillingAddressAction()
	{
		try
		{
			$response = $this->editAddress(BillingAddress::getUserAddress($this->request->gget('id'), $this->user));
			$this->addBreadCrumb($this->translate('_edit_shipping_address'), '');
			return $response;
		}
		catch (ARNotFoundException $e)
		{
			return new ActionRedirectResponse('user', 'index');
		}
	}

	private function editAddress(UserAddressType $addressType)
	{
		$this->addAccountBreadcrumb();
		$this->addAddressBreadcrumb();

		$form = $this->buildAddressForm();
		$address = $addressType->userAddress->get();

		$form->setData($address->toArray());
		$form->set('country', $address->countryID->get());
		$form->set('state_text', $address->stateName->get());

		if ($address->state->get())
		{
			$form->set('state_select', $address->state->get()->getID());
		}

		$response = new ActionResponse();
		$response->set('form', $form);
		$response->set('return', $this->request->gget('return'));
		$response->set('countries', $this->getCountryList($form));
		$response->set('states', $this->getStateList($form->get('country')));
		$response->set('address', $address->toArray());
		$response->set('addressType', $addressType->toArray());

		$address->getSpecification()->setFormResponse($response, $form);

		return $response;
	}

	/**
	 *	@role login
	 */
	public function saveShippingAddressAction()
	{
		try
		{
			$address = ShippingAddress::getUserAddress($this->request->gget('id'), $this->user);
		}
		catch (ARNotFoundException $e)
		{
			return new ActionRedirectResponse('user', 'index');
		}

		return $this->doSaveAddress($address, new ActionRedirectResponse('user', 'editShippingAddress', array('id' =>$this->request->gget('id'), 'query' => array('return' => $this->request->gget('return')))));
	}

	/**
	 *	@role login
	 */
	public function saveBillingAddressAction()
	{
		try
		{
			$address = BillingAddress::getUserAddress($this->request->gget('id'), $this->user);
		}
		catch (ARNotFoundException $e)
		{
			return new ActionRedirectResponse('user', 'index');
		}

		return $this->doSaveAddress($address, new ActionRedirectResponse('user', 'editBillingAddress', array('id' =>$this->request->gget('id'), 'query' => array('return' => $this->request->gget('return')))));
	}

	private function doSaveAddress(UserAddressType $address, ActionRedirectResponse $invalidResponse)
	{
		$address = $address->userAddress->get();
		if ($this->buildAddressValidator()->isValid())
		{
			$this->saveAddress($address);
			return new RedirectResponse($this->router->createURLFromRoute($this->request->gget('return')));
		}
		else
		{
			return $invalidResponse;
		}
	}

	/**
	 *	@role login
	 */
	public function addBillingAddressAction($shipping = false)
	{
		$this->addAccountBreadcrumb();
		$this->addAddressBreadcrumb();
   		if (!$shipping)
   		{
			$this->addBreadCrumb($this->translate('_add_billing_address'), '');
		}

		$form = $this->buildAddressForm();

		$form->set('firstName', $this->user->firstName->get());
		$form->set('lastName', $this->user->lastName->get());
		$form->set('companyName', $this->user->companyName->get());

		$this->user->loadAddresses();

		if ($this->user->defaultBillingAddress->get())
		{
			$form->set('country', $this->user->defaultBillingAddress->get()->userAddress->get()->countryID->get());
			$form->set('phone', $this->user->defaultBillingAddress->get()->userAddress->get()->phone->get());
		}
		else
		{
			$form->set('country', $this->config->get('DEF_COUNTRY'));
		}

		$response = new ActionResponse();
		$response->set('form', $form);
		$response->set('return', $this->request->gget('return'));
		$response->set('countries', $this->getCountryList($form));
		$response->set('states', $this->getStateList($form->get('country')));

		UserAddress::getNewInstance()->getSpecification()->setFormResponse($response, $form, '');

		return $response;
	}

	/**
	 *	@role login
	 */
	public function addShippingAddressAction()
	{
		$response = $this->addBillingAddress(true);
		$this->addBreadCrumb($this->translate('_add_shipping_address'), '');
		return $response;
	}

	/**
	 *	@role login
	 */
	public function doAddBillingAddressAction()
	{
		return $this->doAddAddress('BillingAddress', new ActionRedirectResponse('user', 'addBillingAddress', array('query' => array('return' => $this->request->gget('return')))));
	}

	/**
	 *	@role login
	 */
	public function doAddShippingAddressAction()
	{
		return $this->doAddAddress('ShippingAddress', new ActionRedirectResponse('user', 'addShippingAddress', array('query' => array('return' => $this->request->gget('return')))));
	}

	/**
	 *  Return a list of states for the selected country
	 *  @return JSONResponse
	 */
	public function statesAction()
	{
		$states = State::getStatesByCountry($this->request->gget('country'));
		return new JSONResponse($states);
	}

	/**
	 *  Download an ordered file
	 *
	 *  @return ObjectFileResponse
	 *  @return ActionRedirectResponse
	 *	@role login
	 */
	public function downloadAction()
	{
		// get and validate OrderedItem instance first
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('OrderedItem', 'ID'), $this->request->gget('id')));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));

		$s = ActiveRecordModel::getRecordSet('OrderedItem', $f, array('CustomerOrder', 'Product'));

		// OrderedItem does not exist
		if (!$s->size())
		{
			return new ActionRedirectResponse('user', 'index');
		}

		$item = $s->get(0);
		$file = $item->getFileByID($this->request->gget('fileID'));

		// file does not exist for OrderedItem
		if (!$file)
		{
			return new ActionRedirectResponse('user', 'index');
		}

		OrderedFile::getInstance($item, $file)->registerDownload();

		// download expired
		if (!$item->isDownloadable($file))
		{
			$this->setMessage($this->translate('_download_limit_reached'));
			return new ActionRedirectResponse('user', 'index');
		}

		return new ObjectFileResponse($file);
	}

	/**
	 *	Make payment for unpaid or partially paid order
	 *	@role login
	 */
	public function payAction()
	{
		$this->loadLanguageFile('Checkout');
		$this->addAccountBreadcrumb();
		$this->addBreadCrumb($this->translate('_pay'), '');

		$order = $this->getOrder($this->request->gget('id'));

		if (!$order || $order->isPaid->get())
		{
			return new ActionRedirectResponse('user', 'index');
		}

		$response = new ActionResponse();
		$response->set('order', $order->toArray(array('payments' => true)));
		$response->set('id', $order->getID());

		$checkout = new CheckoutController($this->application);
		$checkout->setPaymentMethodResponse($response, $order);

		return $response;
	}

	/**
	 *	@return User
	 */
	private function createUser($password = '', $prefix = '')
	{
		$user = User::getNewInstance($this->request->gget('email'), $this->request->gget('password'));
		$user->firstName->set($this->request->gget($prefix . 'firstName'));
		$user->lastName->set($this->request->gget($prefix . 'lastName'));
		$user->companyName->set($this->request->gget($prefix . 'companyName'));
		$user->email->set($this->request->gget('email'));
		$user->isEnabled->set(!$this->config->get('REG_EMAIL_CONFIRM'));

		// custom fields
		$user->loadRequestData($this->request, array());

		if ($password)
		{
			$this->session->set('password', $password);
			$user->setPassword($password);
		}

		$user->save();

		if (!$this->config->get('REG_EMAIL_CONFIRM'))
		{
			SessionUser::setUser($user);
			$this->sendWelcomeEmail($user);
		}
		else
		{
			$code = rand(1, 10000000) . rand(1, 10000000);
			$user->setPreference('confirmation', $code);
			$user->save();

			$email = new Email($this->application);
			$email->setUser($user);
			$email->set('code', $code);
			$email->setTemplate('user.confirm');
			$email->send();
		}

		return $user;
	}

	public function sendWelcomeEmailAction(User $user)
	{
		// send welcome email with user account details
		if ($this->config->get('EMAIL_NEW_USER'))
		{
			if ($this->session->get('password'))
			{
				$user->setPassword($this->session->get('password'));
			}

			$email = new Email($this->application);
			$email->setUser($user);
			$email->setTemplate('user.new');
			$email->send();
		}
	}

	private function doAddAddress($addressClass, Response $failureResponse)
	{
		$validator = $this->buildAddressValidator();
		if ($validator->isValid())
		{
			$address = UserAddress::getNewInstance();
			$this->saveAddress($address);

			$addressType = call_user_func_array(array($addressClass, 'getNewInstance'), array($this->user, $address));
			$addressType->save();

			if ($this->request->gget('return'))
			{
				$response = new RedirectResponse($this->router->createURLFromRoute($this->request->gget('return')));
			}
			else
			{
				$response = new ActionRedirectResponse('user', 'addresses');
			}

			return $response;
		}
		else
		{
			return $failureResponse;
		}
	}

	/**************  VALIDATION ******************/
	private function buildPasswordReminderForm()
	{
		$validator = $this->getValidator("emailChange", $this->request);
		$this->validateEmail($validator, '_err_not_unique_email_for_change');

		return new Form($validator);
	}

	private function buildEmailChangeForm()
	{
		return new Form($this->buildEmailChangeValidator());
	}

	private function buildEmailChangeValidator()
	{
		$validator = $this->getValidator("emailChange", $this->request);
		$this->validateEmail($validator, '_err_not_unique_email_for_change');

		return $validator;
	}

	private function buildPasswordChangeForm()
	{
		return new Form($this->buildPasswordChangeValidator());
	}

	private function buildPasswordChangeValidator()
	{

		$validator = $this->getValidator("passwordChange", $this->request);
		$validator->addCheck('currentpassword', new IsNotEmptyCheck($this->translate('_err_enter_current_password')));
		$validator->addCheck('currentpassword', new IsPasswordCorrectCheck($this->translate('_err_incorrect_current_password'), $this->user));

		$this->validatePassword($validator);

		return $validator;
	}

	private function buildPersonalDataForm(User $user)
	{
		$form = new Form($this->buildPersonalDataValidator($user));
		$form->setData($this->user->toArray());
		return $form;
	}

	private function buildPersonalDataValidator(User $user)
	{
		$validator = $this->getValidator("userData", $this->request);
		$this->validateName($validator);
		$user->getSpecification()->setValidation($validator);
		return $validator;
	}

	private function buildRegForm()
	{
		return new Form($this->buildRegValidator());
	}

	private function buildRegValidator()
	{
		$validator = $this->getValidator("userRegistration", $this->request);
		$this->validateName($validator);
		$this->validateEmail($validator);
		$this->validatePassword($validator);

		SessionUser::getAnonymousUser()->getSpecification()->setValidation($validator);

		return $validator;
	}

	private function buildAddressForm()
	{
		return new Form($this->buildAddressValidator());
	}

	private function buildAddressValidator()
	{
		$validator = $this->getValidator("userAddress", $this->request);
		$this->validateAddress($validator);
		return $validator;
	}

	private function buildForm()
	{
		return new Form($this->buildValidator());
	}

	private function buildValidator()
	{
		// validate contact info
		$validator = $this->getValidator("registrationValidator", $this->request);

		$this->validateAddress($validator, 'billing_');
		$this->validateEmail($validator);

		if (($this->config->get('PASSWORD_GENERATION') == 'PASSWORD_REQUIRE') || $this->request->gget('password'))
		{
			$this->validatePassword($validator);
		}

		if (!$this->config->get('REQUIRE_SAME_ADDRESS') && $this->order->isShippingRequired())
		{
			$this->validateAddress($validator, 'shipping_', true);
		}

		SessionUser::getAnonymousUser()->getSpecification()->setValidation($validator);

		return $validator;
	}

	private function validateName(RequestValidator $validator, $fieldPrefix = '', $orCheck = false)
	{
		$validation = array('firstName' => '_err_enter_first_name',
						'lastName' => '_err_enter_last_name');

		$displayedFields = $this->config->get('USER_FIELDS');

		if (empty($displayedFields['FIRSTNAME']))
		{
			unset($validation['firstName']);
		}
		if (empty($displayedFields['LASTNAME']))
		{
			unset($validation['lastName']);
		}

		foreach ($validation as $field => $error)
		{
			$field = $fieldPrefix . $field;
			$check = new IsNotEmptyCheck($this->translate($error));
			$check = $orCheck ? new OrCheck(array($field, 'sameAsBilling'), array($check, new IsNotEmptyCheck('')), $this->request) : $check;
			$validator->addCheck($field, $check);
		}
	}

	private function validateEmail(RequestValidator $validator, $uniqueError = '_err_not_unique_email')
	{

		$validator->addCheck('email', new IsNotEmptyCheck($this->translate('_err_enter_email')));
		$validator->addCheck('email', new IsValidEmailCheck($this->translate('_err_invalid_email')));

		$emailErr = $this->translate($uniqueError);
		$emailErr = str_replace('%1', $this->router->createUrl(array('controller' => 'user', 'action' => 'login', 'query' => array('email' => $this->request->gget('email'))), true), $emailErr);
		$validator->addCheck('email', new IsUniqueEmailCheck($emailErr));
	}

	public function validateAddressAction(RequestValidator $validator, $fieldPrefix = '', $orCheck = false)
	{
		$this->validateName($validator, $fieldPrefix, $orCheck);

		$fields = $checks = array();

		$displayedFields = $this->config->get('USER_FIELDS');

		if ($this->config->get('REQUIRE_PHONE') && !empty($displayedFields['PHONE']))
		{
			$fields[] = $fieldPrefix . 'phone';
			$checks[] = new IsNotEmptyCheck($this->translate('_err_enter_phone'));
		}

		if (!empty($displayedFields['ADDRESS1']))
		{
			$fields[] = $fieldPrefix . 'address1';
			$checks[] = new IsNotEmptyCheck($this->translate('_err_enter_address'));
		}

		if (!empty($displayedFields['CITY']))
		{
			$fields[] = $fieldPrefix . 'city';
			$checks[] = new IsNotEmptyCheck($this->translate('_err_enter_city'));
		}

		if (!empty($displayedFields['COUNTRY']))
		{
			$fields[] = $fieldPrefix . 'country';
			$checks[] = new IsNotEmptyCheck($this->translate('_err_select_country'));
		}

		if (!empty($displayedFields['POSTALCODE']))
		{
			$fields[] = $fieldPrefix . 'postalCode';
			$checks[] = new IsNotEmptyCheck($this->translate('_err_enter_zip'));
		}

		// custom field validation
		$tempVal = $this->getValidator('tempVal', $this->request);
		UserAddress::getNewInstance()->getSpecification()->setValidation($tempVal, null, $fieldPrefix);
		foreach ($tempVal->getValidatorVars() as $var)
		{
			foreach ($var->getChecks() as $check)
			{
				$fields[] = $var->getName();
				$checks[] = $check;
			}

			foreach ($var->getFilters() as $filter)
			{
				$validator->addFilter($var->getName(), $filter);
			}
		}

		foreach ($fields as $key => $field)
		{
			$check = $orCheck ? new OrCheck(array($field, 'sameAsBilling'), array($checks[$key], new IsNotEmptyCheck('')), $this->request) : $checks[$key];
			$validator->addCheck($field, $check);
		}

		if (!empty($displayedFields['STATE']))
		{
			$fieldList = array($fieldPrefix . 'state_select', $fieldPrefix . 'state_text');
			$checkList = array(new IsNotEmptyCheck($this->translate('_err_select_state')), new IsNotEmptyCheck(''));
			if ($orCheck)
			{
				$fieldList[] = 'sameAsBilling';
				$checkList[] = new IsNotEmptyCheck('');
			}
			$stateCheck = new OrCheck($fieldList, $checkList, $this->request);
			$validator->addCheck($fieldPrefix . 'state_select', $stateCheck);
		}
	}

	private function validatePassword(RequestValidator $validator)
	{
				$validator->addCheck('password', new MinLengthCheck(sprintf($this->translate('_err_short_password'), self::PASSWORD_MIN_LENGTH), self::PASSWORD_MIN_LENGTH));
		$validator->addCheck('password', new IsNotEmptyCheck($this->translate('_err_enter_password')));
		$validator->addCheck('confpassword', new IsNotEmptyCheck($this->translate('_err_enter_password')));
		$validator->addCheck('confpassword', new PasswordMatchCheck($this->translate('_err_password_match'), $this->request, 'password', 'confpassword'));
	}

	private function buildNoteForm()
	{
		return new Form($this->buildNoteValidator());
	}

	private function buildNoteValidator()
	{
		$validator = $this->getValidator("orderNote", $this->request);
		$validator->addCheck('text', new IsNotEmptyCheck($this->translate('_err_enter_note')));
		$validator->addFilter('text', new HtmlSpecialCharsFilter);
		return $validator;
	}

	private function getOrder($id)
	{
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'ID'), $id));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->user->getID()));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'isFinalized'), true));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'isCancelled'), 0));

		$s = ActiveRecordModel::getRecordSet('CustomerOrder', $f, array('User'));
		if ($s->size())
		{
			$order = $s->get(0);
			$order->loadAll();

			if ($order->user->get())
			{
				$order->user->get()->getSpecification();
			}

			return $order;
		}
	}

	public function invoicesMenuBlockAction()
	{
		if ($this->user->isAnonymous())
		{
			return;
		}
		$response = new BlockResponse();
		$hasPendingInvoices = $this->user->hasPendingInvoices();
		$response->set('hasPendingInvoices', $hasPendingInvoices);
		$response->set('hasInvoices', $hasPendingInvoices ? true : $this->user->hasInvoices()); // if user has pending invoice, he invoices (at least one pending invoice)

		return $response;
	}

	public function cancelFurtherRebillsAction()
	{
												$request = $this->getRequest();
		$id = $request->gget('id');

		$page = $request->gget('page');
		$params = array('id'=>$id);
		if ($page > 1)
		{
			$params['query'] = array('page'=>$page);
		}

		$status = false;
		$order = CustomerOrder::getInstanceById($id, true);
		$userID = $this->user->getID();
		if(
			$order->userID->get()->getID()
			&& $order->userID->get()->getID() == $userID
			&& true == $order->canUserCancelRebills()
		) {
			$status = $order->cancelRecurring($this->getRequestCurrency());
			if ($status != false)
			{
				$order->cancelFurtherRebills();
			}
		}
		if ($status == false)
		{

			$this->setErrorMessage($this->translate('_cannot_cancel_subscription_contantc_store_administrator'));
		}
		return new ActionRedirectResponse('user', 'viewOrder', $params);
	}

	public function generateTestInvoicesAction()
	{
		return ;


		$config = ActiveRecordModel::getApplication()->getConfig();
		$config->set('RECURRING_BILLING_PAYMENT_DUE_DATE_DAYS', 7);
		$config->save();

		// data
		$userID = 110;
		$product1ID = 339;
		$recurringProductPeriodID = 19;
		// ~

		// create first order
		$user = User::getInstanceByID($userID, true);
		$product1 = Product::getInstanceByID($product1ID, true);
		$order = CustomerOrder::getNewInstance($user);
		$order->save(true);
		$rpp = RecurringProductPeriod::getInstanceByID($recurringProductPeriodID);

		$item = $order->addProduct($product1, 1, true);
		$item->save();
		$recurringItem = RecurringItem::getNewInstance($rpp, $item);
		$recurringItem->setupPrice->set(100);
		$recurringItem->periodPrice->set(25);
		$recurringItem->save();
		$order->finalize();

		// generate invoices
		echo '<pre>Invoices for {CustomerOrder ID:'.$order->getID().'}:',"\n";
		$now = time();
		for ($ts = $now; $ts < strtotime('+20 months', $now); $ts = $ts + 60 * 60 * 24)
		{
			$z = CustomerOrder::generateRecurringInvoices(date('Y-m-d', $ts));
			foreach ($z as $id)
			{
				echo '{CustomerOrder ID:'.$id.'}',"\n";
			}
		}
		die('-done-</pre>');
	}
}

?>
