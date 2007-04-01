<?php

ClassLoader::import("application.model.product.Product");
ClassLoader::import("application.model.order.OrderedItem");

/**
 * Represents customers order - products placed in shopping basket
 *
 * @package application.model.order
 */
class CustomerOrder extends ActiveRecordModel
{
	protected $orderedItems = array();
	
	protected $removedItems = array();
    
    protected static $instance = null;
    
    /**
	 * Define database schema used by this active record instance
	 *
	 * @param string $className Schema name
	 */
	public static function defineSchema($className = __CLASS__)
	{
		$schema = self::getSchemaInstance($className);
		$schema->setName($className);
		
		$schema->registerField(new ARPrimaryKeyField("ID", ARInteger::instance()));
		$schema->registerField(new ARForeignKeyField("userID", "User", "ID", "User", ARInteger::instance()));

//		$schema->registerField(new ARField("sessionID", ARChar::instance(32)));
		$schema->registerField(new ARField("dateCreated", ARTimeStamp::instance()));
		$schema->registerField(new ARField("dateCompleted", ARTimeStamp::instance()));
		$schema->registerField(new ARField("status", ARInteger::instance(2)));
	}
		
	public static function getNewInstance(User $user)	
	{
        $instance = parent::getNewInstance(__CLASS__);
		$instance->user->set($user);     
        
        return $instance;   
    }
    
    /**
     *	Get instance from session
     */
	public static function getInstance()
	{
        if (!self::$instance)
        {
            $instance = Session::getInstance()->getObject('CustomerOrder');
                
            if (!$instance)
            {
                $instance = self::getNewInstance(User::getCurrentUser());
            }    
            
            self::$instance = $instance;
        }
                
        return self::$instance;
    }
    
    /**
     *	Add a product to shopping basket
     */
	public function addProduct(Product $product, $count)
    {
        if ($count < 0)
        {
            throw new ApplicationException('Invalid product count (' . $count . ')');
        }
        
        if (0 == $count)
        {
            $this->removeProduct($product);
        }
        else
        {
            if (!$product->isAvailable())
            {
                throw new ApplicationException('Product is not available (' . $product->sku->get() . ')');
            }
            
            $this->orderedItems[] = OrderedItem::getNewInstance($this, $product, $count);
        }
    }
    
    /**
     *	Add a product to wish list
     */
	public function addToWishList(Product $product)
    {
        $item = OrderedItem::getNewInstance($this, $product, 1);
        $item->isSavedForLater->set(true);
		$this->orderedItems[] = $item;
    }
    
    /**
     *	Remove a product (all product items) from shopping basket or wish list
     */
	public function removeProduct(Product $product)
    {
        $id = $product->getID();
        
        foreach ($this->orderedItems as $key => $item)
        {
            if ($item->product->getID() == $id)
            {
                $this->removedItems[] = $item;
                unset($this->orderedItems[$key]);
            }
        }    
    }

    /**
     *	Remove an item from shopping basket or wish list
     */
	public function removeItem(OrderedItem $orderedItem)
    {
        $id = $orderedItem->getID();
        
		foreach ($this->orderedItems as $key => $item)
        {
            if ($item->getID() == $id)
            {
                $this->removedItems[] = $item;
                unset($this->orderedItems[$key]);
            }
        }    
    }
    
    public function save()
    {
        if ($this->orderedItems || $this->removedItems)
        {
            parent::save();
            
            foreach ($this->orderedItems as $item)
            {
                if (!$item->count->get())
                {
					$this->removeItem($item);
				}
				else
				{
					$item->save();					
				}
            }    
    
            foreach ($this->removedItems as $item)
            {
                $item->delete();
            }                
        }
    }    
    
    /**
     *	Save to database and put in session
     */
	public function saveToSession()
    {
        $this->save();
		Session::getInstance()->setValue('CustomerOrder', $this);
    }
    
    /**
     *  Merge OrderedItem instances of the same product into one instance
     */
    public function mergeItems()
    {
		$byProduct = array();
		
		foreach ($this->orderedItems as $item)
		{
			$byProduct[$item->product->get()->getID()][$item->isSavedForLater->get()][] = $item;
		}
		
		foreach ($byProduct as $productID => $itemsByStatus)
		{
			foreach ($itemsByStatus as $status => $items)
			{
				if (count($items) > 1)
				{
					$mainItem = array_shift($items);
					$count = $mainItem->count->get();
					
					foreach ($items as $item)
					{
						$count += $item->count->get();
						$this->removeItem($item);
					}
					
					$mainItem->count->set($count);
				}				
			}	
		}        
    }
    
    public function getShoppingCartItemCount()
	{
		$count = 0;
		foreach ($this->orderedItems as $item)
		{
			if (!$item->isSavedForLater->get())
			{
				$count += $item->count->get();
			}
		}
		
		return $count;
	}    
    
    public function getWishListItemCount()
	{
		$count = 0;
		foreach ($this->orderedItems as $item)
		{
			if ($item->isSavedForLater->get())
			{
				$count++;
			}
		}
		
		return $count;
	}    

	public function getOrderedItems()
	{
		return $this->orderedItems;
	}

    /**
     *  Return OrderedItem instance by ID
     */
	public function getItemByID($id)
	{
		foreach ($this->orderedItems as $item)
		{
			if ($item->getID() == $id)
			{
				return $item;
			}
		}			
	}

	public function getSubTotal(Currency $currency)
	{
		
	}

    /**
     *  Loads ordered item/product info from database
     */
    public function loadItemData()
    {
        $productIDs = array();
        
        foreach ($this->orderedItems as $item)
        {
			$productIDs[] = $item->product->get()->getID();
		}
		
		$products = ActiveRecordModel::getInstanceArray('Product', $productIDs);
		
        foreach ($this->orderedItems as $item)
        {
			$id = $item->product->get()->getID();
			
			if (isset($products[$id]))
			{
				$item->product->set($products[$id]);
			}
			else
			{
				$this->removeProduct($item->product->get());
			}
		}		
    }
	
	/**
	 *	Creates an array representation of the shopping cart
	 */
	public function toArray()
	{
		$array = parent::toArray();
		
		if (is_array($array))
		{
			$array['cartItems']	= array();
			$array['wishListItems']	= array();
					
			foreach ($this->orderedItems as $item)
			{
				if ($item->isSavedForLater->get())
				{
					$array['wishListItems'][] = $item->toArray();
				}
				else
				{
					$array['cartItems'][] = $item->toArray();
				}
			}			
		
			$array['basketCount'] = $this->getShoppingCartItemCount();
			$array['wishListCount'] = $this->getWishListItemCount();
			
			// subtotal for all currencies
			
			// formatted price
		}	
		
		return $array;
	}
}
	
?>