<?php


/**
 * Category specification group controller
 *
 * @package application.controller.backend
 * @author	Integry Systems
 * @role category
 */
class EavFieldGroupController extends EavFieldGroupControllerCommon
{
	public function init()
	{
		$this->loadLanguageFile('backend/SpecFieldGroup');
		return parent::init();
	}

	protected function getClassName()
	{
		return 'EavFieldGroup';
	}

	protected function getParent($id)
	{
		return new EavFieldManager($id);
	}

	/**
	 * Get specification field group data
	 *
	 * @return JSONResponse
	 */
	public function item()
	{
		return parent::item();
	}

	/**
	 * @role update
	 */
	public function update()
	{
		return parent::update();
	}

	/**
	 * @role update
	 */
	public function create()
	{
		return parent::create();
	}

	/**
	 * Delete specification field group from database
	 *
	 * @role update
	 *
	 * @return JSONResponse Status
	 */
	public function delete()
	{
		return parent::delete();
	}

	/**
	 * Sort specification groups
	 *
	 * @role update
	 *
	 * @return JSONResponse Status
	 */
	public function sort()
	{
		return parent::sort();
	}

	/**
	 * Save group data to the database
	 *
	 * @return JSONResponse Returns status and errors if status is equal to failure
	 */
	protected function save(EavFieldGroup $specFieldGroup)
	{
		return parent::save($specFieldGroup);
	}
}