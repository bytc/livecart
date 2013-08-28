<?php
if(!defined('TEST_SUITE')) require_once dirname(__FILE__) . '/../../Initialize.php';


/**
 * MultiLingualObject test
 *
 * Multi-lingual field values are stored as serialized arrays, which may contain all sorts of characters,
 * which may garble the serialization, characters may not be escaped properly in queries, etc.
 *
 * @author Integry Systems
 * @package test.model.system
 */
class MultiLingualObjectTest extends LiveCartTest
{
	public function __construct()
	{
		parent::__construct('Test multilingual objects');
	}

	public function getUsedSchemas()
	{
		return array(
			'Category'
		);
	}

	function testSerializingValuesWithQuotes()
	{
		// two quotes
		$testValue = 'This is a value with "quotes" :)';

		$root = Category::getInstanceByID(1);
		$new = Category::getNewInstance($root);
		$new->setValueByLang('name', 'en', $testValue);
		$new->save();

		ActiveRecord::clearPool();
		$restored = Category::getInstanceByID($new->getID(), Category::LOAD_DATA);
		$array = $restored->toArray();

		$this->assertEqual($testValue, $restored->getValueByLang('name', 'en'));

		// one quote
		$testValue = 'NX9420 C2D T7400 17" WSXGA+ WVA BRIGHT VIEW 1024MB 120GB DVD+/-RW DL ATI MOBILITY RADEON X1600 256MB WLAN BT TPM XPPKeyb En';

		$restored->setValueByLang('name', 'en', $testValue);
		$restored->save();
		ActiveRecord::clearPool();

		$restored->totalProductCount->set(333);

		$another = Category::getInstanceByID($restored->getID(), Category::LOAD_DATA);

		$this->assertEqual($testValue, $another->getValueByLang('name', 'en'));
	}

	function testSerializingUsASCII_Characters()
	{
		$testValue = '';

		for ($k = 0; $k <= 127; $k++)
		{
			$testValue .= chr($k);
		}

		$testValue = 'x' . $testValue;

		$root = Category::getInstanceByID(1);
		$new = Category::getNewInstance($root);
		$new->setValueByLang('name', 'en', $testValue);
		$new->save();

		ActiveRecordModel::clearPool();
		$restored = Category::getInstanceByID($new->getID(), Category::LOAD_DATA);

		$this->assertEqual($testValue, $restored->getValueByLang('name', 'en'));
	}

	function testSerializingUTF()
	{
		$this->_testStringSerialize('kvīīīāāāččččdddd');
	}

	function testSerializingHighASCII()
	{
		$high = 'haha' . chr(128) . ' zzz ' . chr(200);
		$this->_testStringSerialize($high, false);
		$this->_testStringSerialize(utf8_encode($high));
	}

	private function _testStringSerialize($string, $equals = true)
	{
		error_reporting(E_WARNING);
		$root = Category::getInstanceByID(1);
		$new = Category::getNewInstance($root);
		$new->setValueByLang('name', 'en', $string);
		$new->save();

		ActiveRecordModel::clearPool();
		$restored = Category::getInstanceByID($new->getID(), Category::LOAD_DATA);

		if ($equals)
		{
			$this->assertEqual($string, $restored->getValueByLang('name', 'en'));
		}
		else
		{
			$this->assertNotEquals($string, $restored->getValueByLang('name', 'en'));
		}
		error_reporting(E_ALL);
	}
}

?>