<?php

ClassLoader::import("application.model.ActiveRecordModel");
ClassLoader::import("application.model.user.BillingAddress");
ClassLoader::import("application.model.user.ShippingAddress");
ClassLoader::import("application.model.user.UserGroup");

/**
 * Store user logic (including frontend and backend), including authorization and access control checking
 *
 * @package application.model.user
 * @author Integry Systems <http://integry.com>
 */
class User extends ActiveRecordModel
{
	/**
	 * ID of anonymous user that is not authorized
	 *
	 */
	const ANONYMOUS_USER_ID = NULL;
	
	private $newPassword;

	public static function defineSchema($className = __CLASS__)
	{
		$schema = self::getSchemaInstance($className);
		$schema->setName("User");

		$schema->registerField(new ARPrimaryKeyField("ID", ARInteger::instance()));
		$schema->registerField(new ARForeignKeyField("defaultShippingAddressID", "defaultShippingAddress", "ID", 'ShippingAddress', ARInteger::instance()));
		$schema->registerField(new ARForeignKeyField("defaultBillingAddressID", "defaultBillingAddress", "ID", 'BillingAddress', ARInteger::instance()));
		$schema->registerField(new ARForeignKeyField("userGroupID", "UserGroup", "ID", "UserGroup", ARInteger::instance()));
		
		$schema->registerField(new ARField("email", ARVarchar::instance(60)));
		$schema->registerField(new ARField("password", ARVarchar::instance(32)));
		$schema->registerField(new ARField("firstName", ARVarchar::instance(60)));
		$schema->registerField(new ARField("lastName", ARVarchar::instance(60)));
		$schema->registerField(new ARField("companyName", ARVarchar::instance(60)));
		$schema->registerField(new ARField("dateCreated", ARDateTime::instance()));
		$schema->registerField(new ARField("isEnabled", ARBool::instance()));
	}

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
        $instance->email->set($email);
        $instance->dateCreated->set(new ARSerializableDateTime());
        
        if($userGroup)
        {
            $instance->userGroup->set($userGroup);
        }
        
        if($password)
        {
            $instance->setPassword($password);
        }
        
        return $instance;
    }

	/**
	 * Get anonymous user

	 * @return User
	 */
    public static function getAnonymousUser()
    {
        $instance = parent::getNewInstance(__CLASS__); 
        $instance->setID(self::ANONYMOUS_USER_ID);   

        return $instance;
    }
    
    public function isAnonymous()
    {
        return $this->getID() == self::ANONYMOUS_USER_ID;
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
	
	/**
	 * Load users belonged to specified group
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
		    $filter->setCondition(new IsNullCond(new ARFieldHandle(__CLASS__, "userGroupID")));
		}
		else
		{
		    $filter->setCondition(new EqualsCond(new ARFieldHandle(__CLASS__, "userGroupID"), $userGroup->getID()));
		}
		
		return self::getRecordSet($filter, $loadReferencedRecords);
	}
    
	/**
	 * Get current user (from session)

	 * @return User
	 */
    public static function getCurrentUser()
    {
        $session = new Session();
        
		$id = $session->getValue('User');
    
        if (!$id)
        {
            $user = self::getAnonymousUser();
        }
        else
        {
			try
			{
                $user = User::getInstanceById($id);                
            }
            catch (ARNotFoundException $e)
            {
                $session->unsetValue('User');
                return self::getCurrentUser();
            }
		}
        
        return $user;
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
	public static function getInstanceByID($recordID, $loadRecordData = true, $loadReferencedRecords = array('UserGroup'), $data = array())
	{		    
		return parent::getInstanceByID(__CLASS__, $recordID, $loadRecordData, $loadReferencedRecords, $data);
	}
    
	/**
	 * Make this user a currently logged user
	 */
    public function setAsCurrentUser()
    {
		$session = new Session();
		$session->setValue('User', $this->getID());
	}

	/**
	 * Change user password
	 * 
	 * @param string $password New password
	 */
	public function setPassword($password)
	{
		$this->password->set(md5($password));
		$this->newPassword = $password;
	}

	/**
	 * Save user in the database
	 */
    public function save()
    {
        // auto-generate password if not set
        if (!$this->password->get())
        {
            $this->setPassword($this->getAutoGeneratedPassword());
        }
        
        return parent::save();
    }

    /**
     * Add this user to database. This function also sends a welcome message 
     * with account details to new user as a sideeffec 
     */
    protected function insert()
    {
        parent::insert();
        
        // send welcome email with user account details
        $email = new Email();
        $email->setUser($this);
        $email->setTemplate('user.new');
        //$email->send();
    }

    /**
     * Load user address
     */
	public function loadAddresses()
	{
		if ($this->defaultBillingAddress->get())
		{
			$this->defaultBillingAddress->get()->load(array('UserAddress'));	
		}

		if ($this->defaultShippingAddress->get())
		{
			$this->defaultShippingAddress->get()->load(array('UserAddress'));	
		}
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
		$loginCond->addAND(new EqualsCond(new ARFieldHandle('User', 'password'), md5($password)));
		
		$recordSet = ActiveRecordModel::getRecordSet(__CLASS__, new ARSelectFilter($loginCond));

		if (!$recordSet->size())
		{
			return null;
		}
		else
		{
			return $recordSet->get(0);
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

		if($this->isAnonymous())
		{
		    return false;
		}
		else
		{
			$this->load(array('UserGroup'));	
			
			if (!$this->userGroup->get())
			{
				return false;
			}
			
			return $this->userGroup->get()->hasAccess($roleName);
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

    /**
     * Get user full name inlcuding both first and last names
     * 
     * @return string
     */
    public function getName()
    {
        return $this->firstName->get() . ' ' . $this->lastName->get();
    }

	public function toArray()
	{
		$array = parent::toArray();
		$array['newPassword'] = $this->newPassword;
		return $array;
	}

    public function getBillingAddressArray($defaultFirst = true)
    {
        $f = new ARSelectFilter();
        $f->setCondition(new EqualsCond(new ARFieldHandle('BillingAddress', 'userID'), $this->getID()));
        if (!$defaultFirst)
        {
            $f->setOrder(new ARExpressionHandle('ID = ' . $this->defaultBillingAddress->get()->getID()));
        }
        
        return ActiveRecordModel::getRecordSetArray('BillingAddress', $f, array('UserAddress', 'State'));
    }

    public function getShippingAddressArray($defaultFirst = true)
    {
        $f = new ARSelectFilter();
        $f->setCondition(new EqualsCond(new ARFieldHandle('ShippingAddress', 'userID'), $this->getID()));
        if (!$defaultFirst)
        {
            $f->setOrder(new ARExpressionHandle('ID = ' . $this->defaultShippingAddress->get()->getID()));
        }
        
        return ActiveRecordModel::getRecordSetArray('ShippingAddress', $f, array('UserAddress', 'State'));
    }
    
    public static function transformArray($array, $class = __CLASS__)
    {
        $array = parent::transformArray($array, $class);
        $array['fullName'] = $array['firstName'] . ' ' . $array['lastName'];
        
        return $array;
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
}

?>