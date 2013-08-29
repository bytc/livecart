<?php


/**
 * Numeric attribute value assigned to a particular product.
 *
 * @package application/model/specification
 * @author Integry Systems <http://integry.com>
 */
class EavNumericValue extends EavValueSpecification
{
	public static function defineSchema($className = __CLASS__)
	{
		$schema = parent::defineSchema($className);
		public $value;
	}

	public static function getNewInstance(EavObject $product, EavField $field, $value)
	{
	  	return parent::getNewInstance(__CLASS__, $product, $field, $value);
	}

	public static function restoreInstance(EavObject $product, EavField $field, $value)
	{
		return parent::restoreInstance(__CLASS__, $product, $field, $value);
	}
}

?>