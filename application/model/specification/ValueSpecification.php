<?php

/**
 * Product specification data container
 * Contains a relation between specification fields (attributes), assigned values and products
 * (kind of "feature table")
 *
 * @author Saulius Rupainis <saulius@integry.net>
 * @package application.model.product
 */
abstract class ValueSpecification extends Specification
{
	public static function defineSchema($className = __CLASS__)
	{
		$schema = self::getSchemaInstance($className);
		$schema->setName($className);

		$schema->registerField(new ARPrimaryForeignKeyField("productID", "Product", "ID", null, ARInteger::instance()));
		$schema->registerField(new ARPrimaryForeignKeyField("specFieldID", "SpecField", "ID", null, ARInteger::instance()));
	}

	public static function getNewInstance($class, Product $product, SpecField $field, $value)
	{
		$specItem = parent::getNewInstance($class);
		$specItem->product->set($product);
		$specItem->specField->set($field);
		$specItem->value->set($value);

		return $specItem;
	}
	
	public static function restoreInstance($class, Product $product, SpecField $field, $value)
	{
		$specItem = parent::getInstanceByID($class, array('productID' => $product->getID(), 'specFieldID' => $field->getID()));
		$specItem->value->set($value);
		$specItem->resetModifiedStatus();

		return $specItem;
	}

	public function toArray()
	{
		$ret = array();
		$ret['value'] = $this->value->get();
		$ret['SpecField'] = $this->specField->get()->toArray(ActiveRecordModel::NON_RECURSIVE);
		return $ret;
	}	
}

?>