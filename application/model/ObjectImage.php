<?php


/**
 * Generic associated image handler. Images can be associated to products, categories and possibly
 * other entities in the future
 *
 * @package application/model
 * @author Integry Systems <http://integry.com>
 */
abstract class ObjectImage extends MultilingualObject
{
	abstract public static function getImageSizes();
	abstract public function getOwner();

	public static function defineSchema($className = __CLASS__)
	{
		$schema = self::getSchemaInstance($className);
		$schema->setName($className);

		public $ID;
		public $title;
		public $position;

		return $schema;
	}

	public function deleteImageFiles()
	{
		foreach ($this->getImageSizes() as $key => $value)
	  	{
	  		if(is_file($this->getPath($key)))
	  		{
			   unlink($this->getPath($key));
	  		}
		}
	}

	public static function deleteByID($className, $id, $foreignKeyName)
	{
		$inst = ActiveRecordModel::getInstanceById($className, $id, ActiveRecordModel::LOAD_DATA);
		$inst->getOwner()->load();
		$inst->deleteImageFiles();

		// check if main image is being deleted
		$owner = $inst->getOwner();
		$owner->load(array(get_class($inst)));
		if ($owner->defaultImage->get()->getID() == $id)
		{
			// set next image (by position) as the main image
			$f = new ARSelectFilter();
			$cond = new EqualsCond(new ARFieldHandle(get_class($inst), $foreignKeyName), $owner->getID());
			$cond->addAND(new NotEqualsCond(new ARFieldHandle(get_class($inst), 'ID'), $inst->getID()));
			$f->setCondition($cond);
			$f->setOrder(new ARFieldHandle(get_class($inst), 'position'));
			$f->setLimit(1);
			$newDefaultImage = ActiveRecordModel::getRecordSet(get_class($inst), $f);
			if ($newDefaultImage->size() > 0)
			{
			  	$owner->defaultImage = $newDefaultImage->get(0));
			  	$owner->save();
			}
		}

		return parent::deleteByID($className, $id);
	}

	public function setFile($file)
	{
		if (substr(strtolower($file), 0, 7) == 'http://')
		{
			$fetch = new NetworkFetch($file);
			$fetch->fetch();
			$this->cacheFile = $fetch->getTmpFile();

			if (!file_exists($this->cacheFile))
			{
				return null;
			}

			$file = $this->cacheFile;
		}

		// keep original image as well for future resizing, etc
		$path = $this->getPath('original');
		copy($file, $path);

		return $this->resizeImage(new ImageManipulator($file));
	}

	public function resizeImage(ImageManipulator $resizer)
	{
		$ret = array();
		foreach ($this->getImageSizes() as $key => $size)
		{
			$filePath = $this->getPath($key);

			if (!file_exists(dirname($filePath)))
			{
				mkdir(dirname($filePath), 0777, true);
			}

			$ret[$key] = $resizer->resize($size[0], $size[1], $filePath);
			if (!$ret[$key])
			{
				break;
			}
		}

		return $ret;
	}

	protected function insert($foreignKeyName)
	{
	  	$className = get_class($this);

		// get current max image position
	  	$filter = new ARSelectFilter();
	  	$filter->setCondition(new EqualsCond(new ARFieldHandle($className, $foreignKeyName), $this->getOwner()->getID()));
	  	$filter->setOrder(new ARFieldHandle($className, 'position'), 'DESC');
	  	$filter->setLimit(1);
	  	$maxPosSet = ActiveRecord::getRecordSet($className, $filter);
		if ($maxPosSet->size() > 0)
		{
			$maxPos = $maxPosSet->get(0)->position->get() + 1;
		}
		else
		{
		  	$maxPos = 0;
		}

		$this->position = $maxPos);

		return parent::insert();
	}

	protected static function getImageRoot($className)
	{
		return $this->config->getPath('public/upload/' . strtolower($className) . '.');
	}

	protected static function getRelativePath($path, &$urlPrefix = null)
	{
		$origPath = $path;

		// path located within the /public directory - as default
		$path = str_replace($this->config->getPath('public/'), '', $path);
		if ($path != $origPath)
		{
			$urlPrefix = '/public/';
			return self::fixSlashes($path);
		}

		// path within application web root directory
		$path = str_replace($this->config->getPath('.'), '', $path);
		$path = self::fixSlashes($path);
		if ($path != $origPath)
		{
			$path = self::getApplication()->getRouter()->getBaseDirFromUrl() . self::fixSlashes($path);
			return $path;
		}

		// relative to document root
		if (!empty($_SERVER['DOCUMENT_ROOT']))
		{
			$path = str_replace($_SERVER['DOCUMENT_ROOT'], '', '/' . self::fixSlashes($path));
		}

		$path = self::fixSlashes($path);
		if ($path == $origPath)
		{
			$path = substr($path, strpos($path, '/public/') + 8);
		}

		return $path;
	}

	private function fixSlashes($path)
	{
		$path = str_replace('//', '/', $path);
		$path = str_replace('http:/', 'http://', $path);
		return str_replace('\\', '/', $path);
	}

	public function save()
	{
		parent::save();

		// set as main image if it's the first image being uploaded
		if ($this->position->get() == 0)
		{
			$owner = $this->getOwner();
			$owner->defaultImage = $this);
			$owner->save();
		}
	}

	/*####################  Data array transformation ####################*/
	public static function transformArray($array, ARSchema $schema, $ownerClass, $ownerField)
	{
		$array = parent::transformArray($array, $schema);

		if (!$array['ID'])
		{
			return $array;
		}

		$array['paths'] = $array['urls'] = array();
		$app = self::getApplication();
		$router = $app->getRouter();

		foreach (call_user_func(array($schema->getName(), 'getImageSizes')) as $key => $value)
	  	{
			$productID = isset($array[$ownerClass]['ID']) ? $array[$ownerClass]['ID'] : (isset($array[$ownerField]) ? $array[$ownerField] : false);

			if (!$productID)
			{
				break;
			}

			$urlPrefix = null;
			$array['paths'][$key] = self::getRelativePath(call_user_func_array(array($schema->getName(), 'getImagePath'), array($array['ID'], $productID, $key)), $urlPrefix);

			$url = $app->getFullUploadUrl($urlPrefix . $array['paths'][$key]);
			$url = str_replace('/public//public/', '/public/', $url);
			$url = str_replace('/public/public/', '/public/', $url);
			$array['urls'][$key] = $url;
		}

		$array['paths']['original'] = self::getRelativePath(call_user_func_array(array($schema->getName(), 'getImagePath'), array($array['ID'], $productID, 'original')), $urlPrefix);

		return $array;
	}

	public function _clone(ActiveRecordModel $owner)
	{
		$cloned = clone $this;
		$cloned->setOwner($owner);
		$cloned->save();

		foreach ($this->getImageSizes() as $key => $value)
	  	{
			if (!@copy($this->getPath($key), $cloned->getPath($key)))
			{
				return false;
			}
		}

		return $cloned;
	}

	public function __destruct()
	{
		if ($this->cacheFile)
		{
			@unlink($this->cacheFile);
		}
	}
}

?>
