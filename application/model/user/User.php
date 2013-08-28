<?php

ClassLoader::import("application.model.ActiveRecordModel");
ClassLoader::import("application.model.user.BillingAddress");
ClassLoader::import("application.model.user.ShippingAddress");
ClassLoader::import("application.model.user.UserGroup");
ClassLoader::import("application.model.eav.EavAble");
ClassLoader::import("application.model.eav.EavObject");
ClassLoader::import("application.model.user.UserAddress");
ClassLoader::import("application.model.newsletter.NewsletterSubscriber");

/**
 * Store user logic (including frontend and backend), including authorization and access control checking
 *
 * @package application.model.user
 * @author Integry Systems <http://integry.com>
 */
class User extends ActiveRecordModel implements EavAble
{
	/**
	 * ID of anonymous user that is not authorized
	 *
	 */
	const ANONYMOUS_USER_ID = NULL;

	private $newPassword;

	public $grantedRoles = array();

		public $ID;
		public $defaultShippingAddressID;
		public $defaultBillingAddressID;
		public $userGroupID;
		public $eavObjectID;
		public $locale;
		public $email;
		public $password;
		public $firstName;
		public $lastName;
		public $companyName;
		public $dateCreated;
		public $isEnabled;
		public $preferences;
	}

	/*####################  Static method implementations ####################*/

	/**
	 * Create new user
	 *
	 * @param string $email Email
	 * @param string $password Password
	 * @param UserGroup $userGroup User group
	 *
	 * @return User
	 */
	public static function getNewInstance($email, $password = null, UserGroup $userGroup = null)
	{
		$instance = parent::getNewInstance(__CLASS__);
		$instance->email = $email);
		$instance->dateCreated = new ARSerializableDateTime());

		if($userGroup)
		{
			$instance->userGroup = $userGroup);
		}

		if($password)
		{
			$instance->setPassword($password);
		}

		return $instance;
	}

	/**
	 * Gets an existing record instance (persisted on a database).
	 *
	 * @param mixed $recordID
	 * @param bool $loadRecordData
	 * @param bool $loadReferencedRecords
	 * @param array $data	Record data array (may include referenced record data)
	 *
	 * @return User
	 */
	public static function getInstanceByID($recordID, $loadRecordData = false, $loadReferencedRecords = array('UserGroup'), $data = array())
	{
		return parent::getInstanceByID(__CLASS__, $recordID, $loadRecordData, $loadReferencedRecords, $data);
	}

	/**
	 * Load users set
	 *
	 * @param ARSelectFilter $filter
	 * @param bool $loadReferencedRecords
	 *
	 * @return ARSet
	 */
	public static function getRecordSet(ARSelectFilter $filter, $loadReferencedRecords = false)
	{
		return parent::getRecordSet(__CLASS__, $filter, $loadReferencedRecords);
	}

	/*####################  Instance retrieval ####################*/

	/**
	 * Load users that belong to the specified group
	 *
	 * @param DeliveryZone $deliveryZone
	 * @param bool $loadReferencedRecords
	 *
	 * @return ARSet
	 */
	public static function getRecordSetByGroup(UserGroup $userGroup = null, ARSelectFilter $filter = null, $loadReferencedRecords = array('UserGroup'))
	{
		if(!$filter)
		{
			$filter = new ARSelectFilter();
		}

		if(!$userGroup)
		{
			$filter->mergeCondition(new IsNullCond(new ARFieldHandle(__CLASS__, "userGroupID")));
		}
		else
		{
			$filter->mergeCondition(new EqualsCond(new ARFieldHandle(__CLASS__, "userGroupID"), $userGroup->getID()));
		}

		return self::getRecordSet($filter, $loadReferencedRecords);
	}

	/**
	 * Gets an instance of user by using login information
	 *
	 * @param string $email
	 * @param string $password
	 * @return mixed User instance or null if user is not found
	 */
	public static function getInstanceByLogin($email, $password)
	{
		$loginCond = new EqualsCond(new ARFieldHandle('User', 'email'), $email);
		//$loginCond->addAND(new EqualsCond(new ARFieldHandle('User', 'password'), md5($password)));
		$loginCond->addAND(new EqualsCond(new ARFieldHandle('User', 'isEnabled'), true));

		$recordSet = ActiveRecordModel::getRecordSet(__CLASS__, new ARSelectFilter($loginCond));

		if (!$recordSet->size())
		{
			return null;
		}
		else
		{
			$user = $recordSet->get(0);
			return $user->isPasswordValid($password) ? $user : null;
		}
	}

	/**
	 * Gets an instance of user by using user's e-mail
	 *
	 * @param string $email
	 * @return mixed User instance or null if user is not found
	 */
	public static function getInstanceByEmail($email)
	{
		$filter = new ARSelectFilter();
		$filter->setCondition(new EqualsCond(new ARFieldHandle(__CLASS__, 'email'), $email));
		$recordSet = ActiveRecordModel::getRecordSet(__CLASS__, $filter);

		if (!$recordSet->size())
		{
			return null;
		}
		else
		{
			return $recordSet->get(0);
		}
	}

	/*####################  Value retrieval and manipulation ####################*/

	public function isAnonymous()
	{
		return $this->getID() == self::ANONYMOUS_USER_ID;
	}

	/**
	 * Generate a random password
	 *
	 * @return string
	 */
	public function getAutoGeneratedPassword($length = 8)
	{
		$chars = array();
		for ($k = 1; $k <= $length; $k++)
		{
			$chars[] = chr(rand(97, 122));
		}

		return implode('', $chars);
	}

	/**
	 * Change user password
	 *
	 * @param string $password New password
	 */
	public function setPassword($password)
	{
		$salt = $this->getAutoGeneratedPassword(16);
		$saltedPassword = md5($password . $salt);
		$this->password = $saltedPassword . ':' . $salt);
		$this->newPassword = $password;
	}

	public function isPasswordValid($password)
	{
		$password = trim($password);
		$parts = explode(':', $this->password);
		$hash = array_shift($parts);
		$salt = array_shift($parts);

		return md5($password . $salt) == $hash;
	}

	/**
	 * Checks if a user can access a particular controller/action identified by a role string (handle)
	 *
	 * Role string represents hierarchial role, that grants access to a given node:
	 * rootNode.someNode.lastNode
	 *
	 * (i.e. admin.store.catalog) this role string identifies that user has access to
	 * all actions/controller that are mapped to this string (admin.store.catalog.*)
	 *
	 * @param string $roleName
	 * @return bool
	 */
	public function hasAccess($roleName)
	{
		if ($this->hasBackendAccess || !empty($this->grantedRoles[$roleName]))
		{
			return true;
		}

		// no role provided
		if (!$roleName)
		{
			return true;
		}

		if (!$this->getID())
		{
			return false;
		}

		if ('login' == $roleName)
		{
			return $this->getID() > 0;
		}
		else if ('backend' == $roleName)
		{
			return $this->hasBackendAccess();
		}

		if ($this->isAnonymous())
		{
			return false;
		}
		else
		{
			$this->load(array('UserGroup'));

			if (!$this->userGroup)
			{
				return false;
			}

			return $this->userGroup->hasAccess($roleName);
		}
	}

	public function allowBackendAccess()
	{
		$this->hasBackendAccess = true;
	}

	/**
	 *	Dynamically grant access to a role
	 */
	public function grantAccess($roleName)
	{
		$this->grantedRoles[$roleName] = true;
	}

	/**
	 * Determine if the user is allowed to access the admin backend (has at least one permission)
	 *
	 * @return boolean
	 */
	public function hasBackendAccess()
	{
		if ($this->hasBackendAccess)
		{
			return true;
		}

		if ($this->isAnonymous())
		{
			return false;
		}
		else
		{
			$this->load(array('UserGroup'));
			if (!$this->userGroup)
			{
				return false;
			}
			else
			{
				$this->userGroup->load();
			}

			$this->userGroup->loadRoles();

			return count($this->userGroup->getAppliedRoles()) > 0;
		}
	}

	/**
	 * Check's if this user is loged in. This function will return true only if this
	 * user is loged within this particular session.
	 *
	 * In short that means that this function will return true only if you are this
	 * user and you are currently loged in
	 *
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return ($this->getID() != self::ANONYMOUS_USER_ID);
	}

	public function setPreference($key, $value)
	{
		$preferences = $this->preferences;
		$preferences[$key] = $value;
		$this->preferences = $preferences, true);
	}

	public function getPreference($key)
	{
		$preferences =& $this->preferences;
		if (isset($preferences[$key]))
		{
			return $preferences[$key];
		}

		return null;
	}

	/**
	 * Get user full name inlcuding both first and last names
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->firstName . ' ' . $this->lastName;
	}

	public function invalidateSessionCache()
	{
		if ($this->isAnonymous())
		{
			return;
		}

		$f = new ARUpdateFilter(eq(f('SessionData.userID'), $this->getID()));
		$f->addModifier('cacheUpdated', 0);
		self::updateRecordSet('SessionData', $f);
	}

	/*####################  Saving ####################*/

	public function loadRequestData(Request $request)
	{
		if (!$request->gget('password'))
		{
			$request->remove('password');
		}

		return parent::loadRequestData($request);
	}

	protected function insert()
	{
		$res = parent::insert();

		if ($subscriber = NewsletterSubscriber::getInstanceByEmail($this->email))
		{
			$subscriber->user = $this);
		}
		else
		{
			$subscriber = NewsletterSubscriber::getNewInstanceByUser($this);
			$subscriber->isEnabled = false);
			$subscriber->save();
		}

		$subscriber->confirmationCode = '');
		$subscriber->save();

		return $res;
	}

	/**
	 * Save user in the database
	 */
	public function save($forceOperation = null)
	{
		// auto-generate password if not set
		if (!$this->password)
		{
			$this->setPassword($this->getAutoGeneratedPassword());
		}

		return parent::save($forceOperation);
	}

	/*####################  Data array transformation ####################*/

	public function toArray()
	{
		$array = parent::toArray();
		$array['newPassword'] = $this->newPassword;

		$this->setArrayData($array);

		return $array;
	}

	public static function transformArray($array, ARSchema $schema)
	{
		$array = parent::transformArray($array, $schema);
		$array['fullName'] = $array['firstName'] . ' ' . $array['lastName'];

		return $array;
	}

	/*####################  Get related objects ####################*/

	/**
	 * Load user address
	 */
	public function loadAddresses()
	{
		$this->load();

		if ($this->defaultBillingAddress)
		{
			$this->defaultBillingAddress->load(array('UserAddress'));
		}

		if ($this->defaultShippingAddress)
		{
			$this->defaultShippingAddress->load(array('UserAddress'));
		}
	}

	public function getOrder($id)
	{
		$f = new ARSelectFilter(new EqualsCond(new ARFieldHandle('CustomerOrder', 'ID'), $id));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->getID()));
		$f->mergeCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'isFinalized'), true));

		$s = ActiveRecordModel::getRecordSet('CustomerOrder', $f, ActiveRecordModel::LOAD_REFERENCES);
		if ($s->size())
		{
			$order = $s->get(0);
			$order->loadAll();
			return $order;
		}
	}

	public function getBillingAddressArray($defaultFirst = true)
	{
		if (!$this->isAnonymous())
		{
			return ActiveRecordModel::getRecordSetArray('BillingAddress', $this->getBillingAddressFilter($defaultFirst), array('UserAddress'));
		}
		else if ($this->defaultBillingAddress)
		{
			return array($this->defaultBillingAddress->toArray());
		}
	}

	public function getBillingAddressSet($defaultFirst = true)
	{
		return ActiveRecordModel::getRecordSet('BillingAddress', $this->getBillingAddressFilter($defaultFirst), array('UserAddress'));
	}

	public function getShippingAddressArray($defaultFirst = true)
	{
		if (!$this->isAnonymous())
		{
			return ActiveRecordModel::getRecordSetArray('ShippingAddress', $this->getShippingAddressFilter($defaultFirst), array('UserAddress'));
		}
		else if ($this->defaultShippingAddress)
		{
			return array($this->defaultShippingAddress->toArray());
		}
	}

	public function getShippingAddressSet($defaultFirst = true)
	{
		return ActiveRecordModel::getRecordSet('ShippingAddress', $this->getShippingAddressFilter($defaultFirst), array('UserAddress'));
	}

	public function countInvoices($filter = null)
	{
		$filter = $filter ? $filter : new ARSelectFilter();
		$filter->mergeCondition(
			new AndChainCondition(array(
				new EqualsCond(new ARFieldHandle('CustomerOrder', 'userID'), $this->getID()),
				new EqualsCond(new ARFieldHandle('CustomerOrder', 'isRecurring'), 1),
				new IsNotNullCond(new ARFieldHandle('CustomerOrder', 'parentID'))
				)
			)
		);
		return ActiveRecordModel::getRecordCount('CustomerOrder', $filter);
	}

	public function countPendingInvoices()
	{
		$filter = new ARSelectFilter();
		$filter->setCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'isPaid'), 0));

		return $this->countInvoices($filter);
	}

	public function hasInvoices($filter = null)
	{
		if ($filter)
		{
			$filter->setLimit(1);
		}
		return (bool)$this->countInvoices($filter);
	}

	public function hasPendingInvoices()
	{
		$filter = new ARSelectFilter();
		$filter->setCondition(new EqualsCond(new ARFieldHandle('CustomerOrder', 'isPaid'), 0));
		$filter->setLimit(1);

		return (bool)$this->countInvoices($filter);
	}

	private function getShippingAddressFilter($defaultFirst = true)
	{
		$f = new ARSelectFilter();
		$f->setCondition(new EqualsCond(new ARFieldHandle('ShippingAddress', 'userID'), $this->getID()));
		if (!$defaultFirst)
		{
			$f->setOrder(new ARExpressionHandle('ID = ' . $this->defaultShippingAddress->getID()));
		}

		return $f;
	}

	private function getBillingAddressFilter($defaultFirst = true)
	{
		$f = new ARSelectFilter();
		$f->setCondition(new EqualsCond(new ARFieldHandle('BillingAddress', 'userID'), $this->getID()));
		if (!$defaultFirst)
		{
			$f->setOrder(new ARExpressionHandle('ID = ' . $this->defaultBillingAddress->getID()));
		}

		return $f;
	}

	public function serialize($skippedRelations = array(), $properties = array())
	{
		$properties[] = 'specificationInstance';

		foreach (array('defaultShippingAddressID', 'defaultBillingAddressID') as $addr)
		{
			$skippedRelations[] = $addr;
			$addr = substr($addr, 0, -2);
			$key = 'addr_' . $addr;

			if ($this->$addr)
			{
				$this->$key = $this->$addr->userAddress;
				$properties[] = $key;
			}
		}

		return parent::serialize($skippedRelations, $properties);
	}

	public function unserialize($serialized)
	{
		//die($serialized);
		parent::unserialize($serialized);

		foreach (array('defaultShippingAddressID', 'defaultBillingAddressID') as $addr)
		{
			$addr = substr($addr, 0, -2);
			$key = 'addr_' . $addr;

			if ($this->$key)
			{
				$class = substr($addr, 7);
				$this->$addr = $class::getNewInstance($this, $this->$key));
			}
		}
	}

	public function __destruct()
	{
		return parent::destruct(array('defaultShippingAddressID', 'defaultBillingAddressID'));
	}
}

?>
