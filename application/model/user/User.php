<?php

ClassLoader::import("application.model.ActiveRecordModel");

/**
 * Store user base class (including frontend and backend)
 *
 * @package application.user.model
 * @author Saulius Rupainis <saulius@integry.net>
 *
 */
class User extends ActiveRecordModel
{

	/**
	 * ID of anonymous user that is not authorized
	 *
	 */
	const ANONYMOUS_USER_ID = 0;

	public static function defineSchema($className = __CLASS__)
	{
		$schema = self::getSchemaInstance($className);
		$schema->setName("User");

		$schema->registerField(new ARPrimaryKeyField("ID", ARInteger::instance()));
		$schema->registerField(new ARField("email", ARVarchar::instance(60)));
		$schema->registerField(new ARField("password", ARVarchar::instance(16)));
		$schema->registerField(new ARField("firstName", ARVarchar::instance(20)));
		$schema->registerField(new ARField("middleName", ARVarchar::instance(20)));
		$schema->registerField(new ARField("lastName", ARVarchar::instance(20)));
		$schema->registerField(new ARField("fullName", ARVarchar::instance(60)));
		$schema->registerField(new ARField("nickName", ARVarchar::instance(20)));
		$schema->registerField(new ARField("creationDate", ARDateTime::instance()));
		$schema->registerField(new ARField("isActive", ARBool::instance()));
	}

    public static function getCurrentUser()
    {
        $user = Session::getInstance()->getObject('User');
    
        if (!$user)
        {
            $user = self::getNewInstance();
            $user->setID(self::ANONYMOUS_USER_ID);
        }
        
        return $user;
    }

    public static function getNewInstance()
    {
        $instance = parent::getNewInstance(__CLASS__);    
        
        return $instance;
    }

	/**
	 * Gets an instance of user by using loginn information
	 *
	 * @param string $email
	 * @param string $password
	 * @return mixed User instance or null if user is not found
	 */
	public static function getInstanceByLogin($email, $password)
	{
		$filter = new ARSelectFilter();
		$loginCond = new EqualsCond("User.email", $email);
		$loginCond->addAND(new EqualsCond("User.password", $password));
		$filter->setCondition(new EqualsCond("User.email", $loginCond));
		$recordSet = User::getRecordSet($filter);

		if ($recordSet->size() == 0)
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
		// pseudo check
		if ($this->getID() > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gets a user related config value (persisted)
	 *
	 * @param string $varName
	 * @return mixed
	 */
	public function getConfigValue($varName)
	{
		ClassLoader::import("application.model.user.UserConfigValue");
		$filter = new ARSelectFilter();
		$filter->setCondition(new EqualsCond("UserConfigValue.name", $varName));

		$recordSet = $this->getRelatedRecordSet("UserConfigValue", $filter);
		if ($recordSet->size() == 0)
		{
			return null;
		}
		else
		{
			return $recordSet->get(0);
		}
	}

	/**
	 * Sets a user related config value (persisted)
	 *
	 * @param string $varName
	 * @param mixed $value
	 */
	public function setConfigValue($varName, $value)
	{
		$configVariable = $this->getConfigValue($varName);
		if ($configVariable == null)
		{
			// creating new var
			$configVariable = ActiveRecord::getNewInstance("UserConfigValue");
			$configVariable->user->set($this);
			$configVariable->name->set($varName);
			$configVariable->value->set($value);
		}
		else
		{
			// updating value
			$configVariable->value->set($value);
			$configVariable->save();
		}
	}

	/**
	 * Gets a language code from a config that is active now
	 *
	 * @return Language
	 */
	public function getActiveLang()
	{
		ClassLoader::import("application.model.Language");
		return Language::getInstanceByID("en", ActiveRecord::LOAD_DATA);
	}

	/**
	 * Gets user default (native) language
	 *
	 * @return Language
	 */
	public function getDefaultLang()
	{
		ClassLoader::import("application.model.Language");
		return ActiveRecord::getInstanceByID("Language", "lt", ActiveRecord::LOAD_DATA);
	}

	/**
	 * Sets active language that is used to fill multilingual store data
	 *
	 * @param Language $lang
	 */
	public function setActiveLang(Language $lang)
	{
		$this->setConfigValue("active_lang", $lang->getID());
	}

	/**
	 * Sets default (native) user language
	 *
	 * @param Language $lang
	 */
	public function setDefaultLang(Language $lang)
	{
		$this->setConfigValue("default_lang", $lang->getID());
	}

	/**
	 * Returns user's name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name->get();
	}

	/**
	 * Gets a user nickname
	 *
	 * @return string
	 */
	public function getNickname()
	{
		return $this->nickname->get();
	}


	public static function getInstanceByID($recordID, $loadRecordData = false, $loadReferencedRecords = false)
	{
		return parent::getInstanceByID(__CLASS__, $recordID, $loadRecordData, $loadReferencedRecords);
	}

	public static function getRecordSet(ARSelectFilter $filter, $loadReferencedRecords)
	{
		return ActiveRecord::getRecordSet(__CLASS__, $filter, $loadReferencedRecords);
	}

}

?>