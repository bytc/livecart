<?php


/**
 *
 * @package application.model.order
 * @author Integry Systems <http://integry.com>
 */
class OrderHistory
{
	private $oldOrder;

	/**
	 * @var CustomerOrder
	 */
	private $currentOrder;

	/**
	 * @var User
	 */
	private $user;

	public function __construct(CustomerOrder $order, User $user)
	{
		$order->loadAll();

		$this->currentOrder = $order;
		$this->user = $user;

		$this->oldOrder = $this->serializeToArray($this->currentOrder);
	}

	private function serializeToArray(CustomerOrder $order)
	{
		$array = array();

		$array['ID'] = $order->getID();
		$array['totalAmount'] = $order->totalAmount;
		$array['isCancelled'] = (int)$order->isCancelled;
		$array['status'] = (int)$order->status;

		$array['shipments'] = array();
		foreach ($order->getShipments() as $shipment)
		{
			$shippingServiceArray = null;
			if($shipment->shippingService && (int)$shipment->shippingService->getID())
			{
				$shippingServiceArray = $shipment->shippingService->toArray();
			}
			else
			{
				$shippingService = unserialize($shipment->shippingServiceData);

				$shippingServiceArray = null;
				if(is_object($shippingService))
				{
					$shippingService->setApplication($order->getApplication());
					$shippingServiceArray = $shippingService->toArray();
				}
			}

			$array['shipments'][$shipment->getID()] = array(
				'ID' => $shipment->getID(),
				'status' => (int)$shipment->status,
				'ShippingService' => $shippingServiceArray
			);
		}

		$array['items'] = array();
		foreach ($order->getOrderedItems() as $item)
		{
			$array['items'][$item->getID()] = array(
				'ID' => $item->getID(),
				'shipmentID' => $item->shipment ? $item->shipment->getID() : null,
				'count' => (int)$item->count,
				'price' => $item->price,
				'Product' => array(
					'ID' => (int)$item->getProduct()->getID(),
					'sku' => $item->getProduct()->sku,
					'name' => $item->getProduct()->getName($order->getApplication()->getDefaultLanguageCode()),
					'isDownloadable' => (int)$item->getProduct()->isDownloadable(),
				),
				'Shipment' => ($item->shipment && isset($array['shipments'][$item->shipment->getID()])) ? $array['shipments'][$item->shipment->getID()] : array('ID' => 0)
			);
		}

		// @todo: dirty fix
		if ($order->shippingAddress)
		{
			if ($order->shippingAddress->state)
			{
				$order->shippingAddress->state->load();
			}

			$array['ShippingAddress'] = $order->shippingAddress->toArray();

		}
		else
		{
			$array['ShippingAddress'] = array();
		}

		if ($order->billingAddress)
		{
			if ($order->billingAddress->state)
			{
				$order->billingAddress->state->load();
			}

			$array['BillingAddress'] = $order->billingAddress->toArray();
		}
		else
		{
			$array['BillingAddress'] = array();
		}

		return $array;
	}

	public function saveLog()
	{
		$currentOrder = $this->serializeToArray($this->currentOrder);
		$logEntry = false;

		// Billing address
		if($currentOrder['BillingAddress'] && ($currentOrder['BillingAddress']['compact'] != $this->oldOrder['BillingAddress']['compact']))
		{
			OrderLog::getNewInstance(
				OrderLog::TYPE_BILLINGADDRESS,
				OrderLog::ACTION_CHANGE,
				$this->oldOrder['BillingAddress'],
				$currentOrder['BillingAddress'],
				$this->oldOrder['totalAmount'],
				$currentOrder['totalAmount'],
				$this->user,
				$this->currentOrder
			)->save();
		}

		// Shipping address
		if($currentOrder['ShippingAddress'] && ($currentOrder['ShippingAddress']['compact'] != $this->oldOrder['ShippingAddress']['compact']))
		{
			OrderLog::getNewInstance(
				OrderLog::TYPE_SHIPPINGADDRESS,
				OrderLog::ACTION_CHANGE,
				$this->oldOrder['ShippingAddress'],
				$currentOrder['ShippingAddress'],
				$this->oldOrder['totalAmount'],
				$currentOrder['totalAmount'],
				$this->user,
				$this->currentOrder
			)->save();
		}

		// Canceled
		if($currentOrder['isCancelled'] != $this->oldOrder['isCancelled'])
		{
			OrderLog::getNewInstance(
				OrderLog::TYPE_ORDER,
				OrderLog::ACTION_CANCELEDCHANGE,
				$this->oldOrder,
				$currentOrder,
				$this->oldOrder['totalAmount'],
				$currentOrder['totalAmount'],
				$this->user,
				$this->currentOrder
			)->save();
		}

		// Status
		if($currentOrder['status'] != $this->oldOrder['status'])
		{
			$log = OrderLog::getNewInstance(
				OrderLog::TYPE_ORDER,
				OrderLog::ACTION_STATUSCHANGE,
				$this->oldOrder,
				$currentOrder,
				$this->oldOrder['totalAmount'],
				$currentOrder['totalAmount'],
				$this->user,
				$this->currentOrder
			);

			$log->save();
		}


		$newShipment = false;

		// Create shippment
		if(count($this->oldOrder['shipments']) < count($currentOrder['shipments']))
		{
			foreach(array_diff_key($currentOrder['shipments'], $this->oldOrder['shipments']) as $shipment)
			{
				$logEntry = $this->logShipment(null, $shipment, $currentOrder);
			}
		}

		// Delete shipment
		else if(count($this->oldOrder['shipments']) > count($currentOrder['shipments']))
		{
			$isDownloadable = false;
			foreach(array_diff_key($this->oldOrder['items'], $currentOrder['items']) as $item)
			{
				if($item['Product']['isDownloadable'])
				{
					$isDownloadable = true;
				}
			}

			foreach(array_diff_key($this->oldOrder['shipments'], $currentOrder['shipments']) as $shipment)
			{
				if(!$isDownloadable)
				{
					$this->logShipment($shipment, null, $currentOrder);
				}
			}

			foreach(array_diff_key($this->oldOrder['items'], $currentOrder['items']) as $item)
			{
				$this->logItem($item, null, $currentOrder, !$isDownloadable);
			}
		}
		else
		{
			foreach($currentOrder['shipments'] as $shipment)
			{
				if(isset($this->oldOrder['shipments'][$shipment['ID']]))
				{
					$oldShipment = $this->oldOrder['shipments'][$shipment['ID']];

					// Change shipment status
					if($oldShipment['status'] != $shipment['status'])
					{
						$this->logShipment($oldShipment, $shipment, $currentOrder);
					}

					// Change shipping service
					if($oldShipment['ShippingService']['ID'] != $shipment['ShippingService']['ID'])
					{
						OrderLog::getNewInstance(
							OrderLog::TYPE_SHIPMENT,
							OrderLog::ACTION_SHIPPINGSERVICECHANGE,
							$oldShipment,
							$shipment,
							$this->oldOrder['totalAmount'],
							$currentOrder['totalAmount'],
							$this->user,
							$this->currentOrder
						)->save();
					}
				}
			}

			// Remove item
			foreach(array_diff_key($this->oldOrder['items'], $currentOrder['items']) as $item)
			{
				$this->logItem($item, null, $currentOrder);
			}

			foreach($currentOrder['items'] as $item)
			{
				if(isset($this->oldOrder['items'][$item['ID']]))
				{
					$oldItem = $this->oldOrder['items'][$item['ID']];

					// Change shipping
					if($oldItem['Shipment']['ID'] != $item['Shipment']['ID'])
					{
						OrderLog::getNewInstance(
							OrderLog::TYPE_ORDERITEM,
							OrderLog::ACTION_SHIPMENTCHANGE,
							$oldItem,
							$item,
							$this->oldOrder['totalAmount'],
							$currentOrder['totalAmount'],
							$this->user,
							$this->currentOrder
						)->save();
					}

					// Change item's quantity
					if($oldItem['count'] != $item['count'])
					{
						$this->logItem($oldItem, $item, $currentOrder);
					}
				}
			}
		}

		// Add item
		$addedItems = array_diff_key($currentOrder['items'], $this->oldOrder['items']);
		foreach($addedItems as $item)
		{
			$this->logItem(null, $item, $currentOrder);

			if($item['Product']['isDownloadable'] && $logEntry)
			{
				$logEntry->delete();
			}

		}
	}

	private function logItem($oldValue, $newValue, $orderArray, $removedWithShipment = false)
	{
		$action = OrderLog::ACTION_COUNTCHANGE;

		if($removedWithShipment)
		{
			$action = OrderLog::ACTION_REMOVED_WITH_SHIPMENT;
		}
		else if($newValue['Product']['isDownloadable'])
		{
			$action = OrderLog::ACTION_NEW_DOWNLOADABLE_ITEM_ADDED;
		}
		else if($oldValue['Product']['isDownloadable'])
		{
			$action = OrderLog::ACTION_NEW_DOWNLOADABLE_ITEM_REMOVED;
		}
		else if(!$oldValue)
		{
			$action = OrderLog::ACTION_ADD;
		}
		else if(!$newValue)
		{
			$action = OrderLog::ACTION_REMOVE;
		}

		OrderLog::getNewInstance(
			OrderLog::TYPE_ORDERITEM,
			$action,
			$oldValue,
			$newValue,
			$this->oldOrder['totalAmount'],
			$orderArray['totalAmount'],
			$this->user,
			$this->currentOrder
		)->save();
	}

	private function logShipment($oldValue, $newValue, $orderArray)
	{
		$action = OrderLog::ACTION_STATUSCHANGE;
		if(!$oldValue)
		{
			$action = OrderLog::ACTION_ADD;
		}
		else if(!$newValue)
		{
			$action = OrderLog::ACTION_REMOVE;
		}

		$logEntry = OrderLog::getNewInstance(
			OrderLog::TYPE_SHIPMENT,
			$action,
			$oldValue,
			$newValue,
			$this->oldOrder['totalAmount'],
			$orderArray['totalAmount'],
			$this->user,
			$this->currentOrder
		);

		$logEntry->save();

		return $logEntry;
	}
}
?>