<?php
if(!defined('TEST_SUITE')) require_once dirname(__FILE__) . '/../../Initialize.php';

ClassLoader::import("application/model/delivery/DeliveryZone");
ClassLoader::import("application/model/delivery/DeliveryZoneCountry");
ClassLoader::import("application/model/delivery/DeliveryZoneState");
ClassLoader::import("application/model/delivery/DeliveryZoneCityMask");
ClassLoader::import("application/model/delivery/DeliveryZoneZipMask");
ClassLoader::import("application/model/delivery/DeliveryZoneAddressMask");
ClassLoader::import("application/model/delivery/State");
ClassLoader::import("application/model/tax/Tax");
ClassLoader::import("application/model/tax/TaxRate");
ClassLoader::import("application/model/user/UserAddress");

/**
 *
 * @package test.model.delivery
 * @author Integry Systems
 */
class DeliveryZoneTest extends LiveCartTest
{
	public function __construct()
	{
		parent::__construct('delivery zones tests');
	}

	public function getUsedSchemas()
	{
		return array(
			'DeliveryZone',
			'DeliveryZoneCountry',
			'DeliveryZoneState',
			'DeliveryZoneCityMask',
			'DeliveryZoneZipMask',
			'DeliveryZoneAddressMask'
		);
	}

	public function testCreateNewDeliveryZone()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->type->set(DeliveryZone::TAX_RATES);
		$zone->isEnabled->set(1);
		$zone->isFreeShipping->set(1);
		$zone->save();

		$zone->reload();

		$this->assertEquals($zone->name, ':TEST_ZONE');
		$this->assertEquals($zone->isEnabled, 1);
		$this->assertEquals($zone->isFreeShipping, 1);
		$this->assertEquals(DeliveryZone::TAX_RATES, $zone->type);
	}

	public function testGetAllDeliveryZones()
	{
		$zonesCount = DeliveryZone::getAll()->getTotalRecordCount();

		$zone0 = DeliveryZone::getNewInstance();
		$zone0->name->set(':TEST_ZONE_1');
		$zone0->isEnabled->set(0);
		$zone0->save();

		$zone1 = DeliveryZone::getNewInstance();
		$zone1->name->set(':TEST_ZONE_2');
		$zone1->isEnabled->set(1);
		$zone1->save();

		$this->assertEquals(DeliveryZone::getAll()->getTotalRecordCount(), $zonesCount + 2);
	}

	public function testGetEnabledDeliveryZones()
	{
		$zonesCount = DeliveryZone::getEnabled()->getTotalRecordCount();

		$zone0 = DeliveryZone::getNewInstance();
		$zone0->name->set(':TEST_ZONE_1');
		$zone0->isEnabled->set(0);
		$zone0->save();

		$zone1 = DeliveryZone::getNewInstance();
		$zone1->name->set(':TEST_ZONE_2');
		$zone1->isEnabled->set(1);
		$zone1->save();

		$this->assertEquals(DeliveryZone::getEnabled()->getTotalRecordCount(), $zonesCount + 1);
	}

	public function testGetDeliveryZoneCountries()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->save();

		$deliveryCountry = DeliveryZoneCountry::getNewInstance($zone, 'LT');
		$deliveryCountry->save();

		$countries = $zone->getCountries();

		$this->assertEquals($countries->getTotalRecordCount(), 1);
		$this->assertTrue($countries->get(0) === $deliveryCountry);
	}

	public function testGetDeliveryZoneStates()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->save();

		$deliveryState = DeliveryZoneState::getNewInstance($zone, State::getInstanceByID(1));
		$deliveryState->save();

		$states = $zone->getStates();

		$this->assertEquals($states->getTotalRecordCount(), 1);
		$this->assertTrue($states->get(0) === $deliveryState);
	}

	public function testGetDeliveryZoneCityMasks()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->save();

		$mask = DeliveryZoneCityMask::getNewInstance($zone, 'asd');
		$mask->save();

		$masks = $zone->getCityMasks();

		$this->assertEquals($masks->getTotalRecordCount(), 1);
		$this->assertTrue($masks->get(0) === $mask);
	}

	public function testGetDeliveryZoneZipMasks()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->save();

		$mask = DeliveryZoneZipMask::getNewInstance($zone, 'asd');
		$mask->save();

		$masks = $zone->getZipMasks();

		$this->assertEquals($masks->getTotalRecordCount(), 1);
		$this->assertTrue($masks->get(0) === $mask);
	}

	public function testGetDeliveryZoneAddressMasks()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->save();

		$mask = DeliveryZoneAddressMask::getNewInstance($zone, 'asd');
		$mask->save();

		$masks = $zone->getAddressMasks();

		$this->assertEquals($masks->getTotalRecordCount(), 1);
		$this->assertTrue($masks->get(0) === $mask);
	}

	public function testGetZoneServices()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->save();

		$service1 = ShippingService::getNewInstance($zone, 'Test service 1', ShippingService::SUBTOTAL_BASED);
		$service1->save();
		$service2 = ShippingService::getNewInstance($zone, 'Test service 2', ShippingService::SUBTOTAL_BASED);
		$service2->save();

		$services = $zone->getShippingServices();
		$this->assertTrue($service1 === $services->get(0));
		$this->assertTrue($service2 === $services->get(1));
	}

	public function testGetTaxRates()
	{
		$zone = DeliveryZone::getNewInstance();
		$zone->name->set(':TEST_ZONE');
		$zone->save();

		$tax = Tax::getNewInstance('VAT');
		$tax->save();

		$taxRate = TaxRate::getNewInstance($zone, $tax, 15);
		$taxRate->save();


		$taxRates = $zone->getTaxRates();
		$this->assertEquals($taxRates->getTotalRecordCount(), 1);
		$this->assertTrue($taxRates->get(0) === $taxRate);
	}

	public function testFindZoneWithMasks()
	{
		$zone1 = DeliveryZone::getNewInstance();
		$zone1->name->set('With ZIP');
		$zone1->isEnabled->set(true);
		$zone1->save();

		DeliveryZoneZipMask::getNewInstance($zone1, 'asd')->save();
		DeliveryZoneCountry::getNewInstance($zone1, 'LT')->save();

		$zone2 = DeliveryZone::getNewInstance();
		$zone2->name->set('Without ZIP');
		$zone2->isEnabled->set(true);
		$zone2->save();
		DeliveryZoneCountry::getNewInstance($zone2, 'LT')->save();

		$address = UserAddress::getNewInstance();
		$address->countryID->set('LT');

		$this->assertSame(DeliveryZone::getZoneByAddress($address), $zone2);

		$address->postalCode->set('asd');
		$this->assertSame(DeliveryZone::getZoneByAddress($address), $zone1);
	}
}
?>