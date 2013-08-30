<?php

/**
 * Match an address to delivery zone by city name mask string. For example, "New Y*k" or "New Y" would
 * match "New York". The city name mask usually has to be used together with other masks or state/country
 * rules to make sure an address from a wrong country doesn't get matched.
 *
 * @package application/model/delivery
 * @author Integry Systems <http://integry.com>
 */
class DeliveryZoneCityMask extends ActiveRecordModel
{
	public static function defineSchema($className = __CLASS__)
	{
		$schema = self::getSchemaInstance($className);
		$schema->setName("DeliveryZoneCityMask");

		public $ID;
		public $deliveryZoneID", "DeliveryZone", "ID", "DeliveryZone;
		public $mask;
	}

	/**
	 * Gets an existing record instance (persisted on a database).
	 *
	 * @param mixed $recordID
	 * @param bool $loadRecordData
	 * @param bool $loadReferencedRecords
	 * @param array $data	Record data array (may include referenced record data)
	 *
	 * @return DeliveryZoneCityMask
	 */
	public static function getInstanceByID($recordID, $loadRecordData = false, $loadReferencedRecords = false, $data = array())
	{
		return parent::getInstanceByID(__CLASS__, $recordID, $loadRecordData, $loadReferencedRecords, $data);
	}

	/**
	 * @return DeliveryZoneState
	 */
	public static function getNewInstance(DeliveryZone $zone, $mask)
	{
	  	$instance = new self();

	  	$instance->deliveryZone = $zone;
	  	$instance->mask = $mask;

	  	return $instance;
	}

	/**
	 * @param DeliveryZone $zone
	 *
	 * @return ARSet
	 */
	public static function getRecordSetByZone(DeliveryZone $zone, $loadReferencedRecords = false)
	{
		$filter = new ARSelectFilter();
		$filter->setCondition(new EqualsCond(new ARFieldHandle(__CLASS__, 'deliveryZoneID'), $zone->getID()));

		return self::getRecordSet(__CLASS__, $filter, $loadReferencedRecords);
	}
}

?>