<?php

ClassLoader::import("application.model.product.Product");
ClassLoader::import("application.model.eav.EavAble");
ClassLoader::import("application.model.eav.EavObject");

/**
 * Represents a financial/monetary transaction, which can be:
 *
 *	  a) customers payment for ordered items (sale)
 *	  b) authorization transaction to reserve funds on customers credit card
 *	  c) capture transaction to request authorized funds
 *	  d) void transaction to cancel an earlier transaction
 *	  e) @todo - refund transaction
 *
 * The transaction must be assigned to a concrete CustomerOrder
 *
 * @package application.model.order
 * @author Integry Systems <http://integry.com>
 */
class Transaction extends ActiveRecordModel implements EavAble
{
	const TYPE_SALE = 0;
	const TYPE_AUTH = 1;
	const TYPE_CAPTURE = 2;
	const TYPE_VOID = 3;

	const METHOD_OFFLINE = 0;
	const METHOD_CREDITCARD = 1;
	const METHOD_ONLINE = 2;

	const LAST_DIGIT_COUNT = 5;

	/**
	 *  Instance of payment handler object
	 *  @TransactionPayment
	 */
	private $handler;

	/**
	 * Define database schema used by this active record instance
	 *
	 * @param string $className Schema name
	 */
	public static function defineSchema($className = __CLASS__)
	{
		$schema = self::getSchemaInstance($className);


		public $ID;
		public $parentTransactionID", "Transaction", "ID", "Transaction;
		public $orderID", "CustomerOrder", "ID", "CustomerOrder;
		public $currencyID", "currency", "ID", 'Currency', ARChar::instance()));
		public $realCurrencyID", "realCurrency", "ID", 'Currency', ARChar::instance()));
		public $userID", "user", "ID", 'User;
		public $eavObjectID", "eavObject", "ID", 'EavObject', ARInteger::instance()), false);

		public $amount;
		public $realAmount;
		public $time;
		public $method;
		public $gatewayTransactionID;
		public $type;
		public $methodType;
		public $isCompleted;
		public $isVoided;

		public $ccExpiryYear;
		public $ccExpiryMonth;
		public $ccLastDigits;
		public $ccType;
		public $ccName;
		public $ccCVV;
		public $comment;
		public $serializedData;
	}

	/*####################  Static method implementations ####################*/

	public static function getNewInstance(CustomerOrder $order, TransactionResult $result)
	{
		$instance = parent::getNewInstance(__CLASS__);
		$instance->order = $order);
		$instance->gatewayTransactionID = $result->gatewayTransactionID->get());

		// determine currency
		if ($result->currency->get())
		{
			$instance->realCurrency = Currency::getInstanceById($result->currency->get()));
		}
		else
		{
			$instance->realCurrency = $order->currency->get());
		}

		// amount
		$instance->realAmount = $result->amount->get());

		// different currency than initial order currency?
		if ($order->currency->get()->getID() != $result->currency->get())
		{
			$instance->amount = $order->currency->get()->convertAmount($instance->realCurrency->get(), $instance->realAmount->get()));
			$instance->currency = $order->currency->get());

			// test if some amount is not missing due to currency conversion rounding (a difference of 0.01, etc)
			$total = $order->totalAmount->get();
			if ($instance->amount->get() < $total)
			{
				$largerAmount = $order->currency->get()->convertAmount($instance->realCurrency->get(), 0.01 + $instance->realAmount->get());
				if ($largerAmount >= $total)
				{
					$instance->amount = $total);
				}
			}
		}

		// transaction type
		$instance->type = $result->getTransactionType());

		if ($instance->type->get() != self::TYPE_AUTH)
		{
			$instance->isCompleted = true);
		}

		if ($result->details->get())
		{
			$instance->comment = $result->details->get());
		}

		return $instance;
	}

	public function getInstance(CustomerOrder $order, $gatewayTransactionID)
	{
		return $order->getRelatedRecordSet(__CLASS__, select(eq(__CLASS__ . '.gatewayTransactionID', $gatewayTransactionID)))->get(0);
	}

	public static function getNewSubTransaction(Transaction $transaction, TransactionResult $result)
	{
		$instance = self::getNewInstance($transaction->order->get(), $result);
		$instance->parentTransaction = $transaction);
		$instance->method = $transaction->method->get());
		return $instance;
	}

	public static function getNewOfflineTransactionInstance(CustomerOrder $order, $amount)
	{
		$instance = parent::getNewInstance(__CLASS__);
		$instance->order = $order);
		$instance->realCurrency = $order->currency->get());
		$instance->type = self::TYPE_SALE);
		$instance->methodType = self::METHOD_OFFLINE);
		$instance->isCompleted = true);
		$instance->realAmount = $amount);

		return $instance;
	}

	public static function getInstanceById($id)
	{
		return parent::getInstanceById(__CLASS__, $id, self::LOAD_DATA, self::LOAD_REFERENCES);
	}

	/*####################  Value retrieval and manipulation ####################*/

	public function setHandler(TransactionPayment $handler)
	{
		$this->handler = $handler;

		if ($handler instanceof CreditCardPayment)
		{
			$this->setAsCreditCard();
		}
	}

	/**
	 *  Load payment handler class that was used for processing this transaction
	 */
	public function loadHandlerClass()
	{
		$className = $this->isOffline() ? 'OfflineTransactionHandler' : $this->method->get();

		if (!class_exists($className, false))
		{
			if (!$this->isOffline())
			{
				if ($this->isCreditCard())
				{
					ClassLoader::import('library.payment.method.cc.' . $className);
				}
				else
				{
					ClassLoader::import('library.payment.method.*');
					ClassLoader::import('library.payment.method.express.*');
					include_once $className . '.php';
				}
			}
			else
			{
				ClassLoader::import('application.model.order.' . $className);
			}
		}

		return $className;
	}

	/**
	 *  Mark payment method type as credit card
	 *
	 *  @return bool
	 */
	public function setAsCreditCard()
	{
		$this->methodType = self::METHOD_CREDITCARD);
	}

	/**
	 *  Determines if the payment was made via credit card
	 *
	 *  @return bool
	 */
	public function isCreditCard()
	{
		return self::METHOD_CREDITCARD == $this->methodType->get();
	}

	/**
	 *  Determines if the payment was made via credit card
	 *
	 *  @return bool
	 */
	public function isOffline()
	{
		return (self::METHOD_OFFLINE == $this->methodType->get()) && !$this->method->get();
	}

	/**
	 *  Determines if this transaction can be voided
	 *
	 *  @return bool
	 */
	public function isVoidable()
	{
		if (!$this->isVoided->get() && self::TYPE_VOID != $this->type->get())
		{
			if ($this->isOffline())
			{
				return true;
			}
			else
			{
				$class = $this->loadHandlerClass();
				if ((self::TYPE_AUTH == $this->type->get()) ||
					((self::TYPE_SALE == $this->type->get()) && call_user_func(array($class, 'isCapturedVoidable')))
				   )
				{
					return call_user_func(array($class, 'isVoidable'));
				}
			}
		}

		return false;
	}

	public function isCapturable()
	{
		return (self::TYPE_AUTH == $this->type->get()) && !$this->isCompleted->get() && !$this->isOffline() && !$this->isVoided->get();
	}

	/**
	 *  Determines if more than one capture transactions are possible
	 *
	 *  @return bool
	 */
	public function isMultiCapture()
	{
		if (!$this->isOffline())
		{
			$class = $this->loadHandlerClass();
			return call_user_func(array($class, 'isMultiCapture'));
		}
	}

	/**
	 *  Creates a new VOID transaction for this transaction
	 *
	 *  @return Transaction
	 */
	public function void()
	{
		if (!$this->isVoidable())
		{
			return false;
		}

		// attempt to void the transaction
		$result = $this->getSubTransactionHandler()->void();

		if (!($result instanceof TransactionResult))
		{
			return $result;
		}

		self::beginTransaction();

		$instance = self::getNewSubTransaction($this, $result);
		$instance->amount = $this->amount->get() * -1);
		$instance->realAmount = $this->realAmount->get() * -1);
		$instance->currency = $this->currency->get());
		$instance->realCurrency = $this->realCurrency->get());
		$instance->save();

		$this->isVoided = true);
		$this->save();

		if ($this->order->get()->getDueAmount() > 0)
		{
			$this->order->get()->isPaid = false);
			$this->order->get()->save();
		}

		self::commit();

		return $instance;
	}

	/**
	 *  Creates a new CAPTURE transaction for this transaction
	 *
	 *  @return Transaction
	 */
	public function capture($amount, $isCompleted = false)
	{
		if (!$this->isCapturable())
		{
			return false;
		}

		$handler = $this->getSubTransactionHandler($amount);
		$handler->getDetails()->isCompleted = $isCompleted);
		$result = $handler->capture();

		if (!($result instanceof TransactionResult))
		{
			return $result;
		}

		$instance = self::getNewSubTransaction($this, $result);
		$instance->realAmount = $result->amount->get());
		$instance->save();

		return $instance;
	}

	/**
	 *  Creates a payment handler instance for processing sub-transactions (capture or void)
	 *
	 *  @return TransactionPayment
	 */
	protected function getSubTransactionHandler($amount = null)
	{
		// set up transaction parameters object
		$details = new LiveCartTransaction($this->order->get(), $this->currency->get());
		$details->amount = is_null($amount) ? $this->amount->get() : $amount);
		$details->gatewayTransactionID = $this->gatewayTransactionID->get());

		// set up payment handler instance
		$className = $this->loadHandlerClass();

		return self::getApplication()->getPaymentHandler($className, $details);
	}

	/*####################  Saving ####################*/

	public function save($forceOperation = null)
	{
		if (!$this->currency->get())
		{
			$this->currency = $this->realCurrency->get());
			$this->amount = $this->realAmount->get());
		}

		// encrypt card number
		if ($this->ccLastDigits->isModified())
		{
			$this->ccLastDigits = $this->encrypt($this->ccLastDigits->get()));
		}

		return parent::save($forceOperation);
	}

	protected function insert()
	{

		if (self::TYPE_CAPTURE == $this->type->get() || self::TYPE_SALE == $this->type->get())
		{
			$this->order->get()->addCapturedAmount($this->amount->get());
		}
		else if (self::TYPE_VOID == $this->type->get())
		{
			$parentType = $this->parentTransaction->get()->type->get();
			if (self::TYPE_CAPTURE == $parentType || self::TYPE_SALE == $parentType)
			{
				$this->order->get()->addCapturedAmount(-1 * $this->parentTransaction->get()->amount->get());
			}
		}

		$this->order->get()->save();

		if ($this->handler instanceof CreditCardPayment)
		{
			$this->setAsCreditCard();
			$this->ccExpiryMonth = $this->handler->getExpirationMonth());
			$this->ccExpiryYear = $this->handler->getExpirationYear());
			$this->ccType = $this->handler->getCardType());
			$this->ccName = $this->handler->getDetails()->getName());

			$this->ccLastDigits = $this->handler->getCardNumber());

			// only the last 5 digits of credit card number are normally stored
			if (!$this->handler->isCardNumberStored())
			{
				$this->truncateCcNumber();
			}
			else
			{
				$this->ccCVV = self::encrypt($this->handler->getCardCode()));
			}

			$this->ccLastDigits = self::encrypt($this->ccLastDigits->get()));
		}

		if ($this->handler)
		{
			$this->method = get_class($this->handler));
		}

		return parent::insert();
	}

	public function truncateCcNumber()
	{
		$this->ccCVV = null);
		$this->ccLastDigits = self::decrypt($this->ccLastDigits->get()));
		$this->ccLastDigits = substr($this->ccLastDigits->get(), -1 * self::LAST_DIGIT_COUNT));
	}

	public function setOfflineHandler($method)
	{
		$this->setData('handler', OfflineTransactionHandler::getMethodName($method));
		$this->setData('handlerID', $method);
	}

	public function setData($key, $value)
	{
		$data = unserialize($this->serializedData->get());
		$data[$key] = $value;
		$this->serializedData = serialize($data));
	}

	/*####################  Data array transformation ####################*/

	public static function transformArray($array, ARSchema $schema)
	{
		$array = parent::transformArray($array, $schema);

		try
		{
			$array['formattedAmount'] = Currency::getInstanceByID($array['Currency']['ID'])->getFormattedPrice($array['amount']);
			$array['formattedRealAmount'] = Currency::getInstanceByID($array['RealCurrency']['ID'])->getFormattedPrice($array['realAmount']);
		}
		catch (ARNotFoundException $e)
		{
		}

		$array['methodName'] = self::getApplication()->getLocale()->translator()->translate($array['method']);
		$array['serializedData'] = unserialize($array['serializedData']);
		$array['ccLastDigits'] = self::decrypt($array['ccLastDigits']);
		if(strlen($array['ccCVV']) > 0)
		{
			$array['ccCVV'] = self::decrypt($array['ccCVV']);
		}
		return $array;
	}

	public function toArray()
	{
		$array = parent::toArray();

		$array['isVoidable'] = $this->isVoidable();
		$array['isCapturable'] = $this->isCapturable();
		$array['isMultiCapture'] = $this->isMultiCapture();
		$array['hasFullNumber'] = strlen(self::decrypt($this->ccLastDigits->get())) > self::LAST_DIGIT_COUNT;

		return $array;
	}

	private function decrypt($text)
	{
		if (!function_exists('mcrypt_decrypt') || ('_' != $text[0]))
		{
			return $text;
		}

		$text = substr($text, 1);
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::getEncryptionPassword(), base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	}

	private function encrypt($text)
	{
		if (!function_exists('mcrypt_decrypt'))
		{
			return $text;
		}

		return '_' . trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::getEncryptionPassword(), $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))));
	}

	private function getEncryptionPassword()
	{
		$file = ClassLoader::getRealPath('storage.configuration.ccEncryptKey') . '.php';
		if (!file_exists($file))
		{
			ClassLoader::import('application.model.user.User');
			file_put_contents($file, '<?php return ' . var_export(User::getAutoGeneratedPassword(16), true) . '; ?>');
		}

		return include $file;
	}
}

?>