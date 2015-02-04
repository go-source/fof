<?php
/**
 * @package     FOF
 * @copyright   2010-2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace FOF30\Model\DataModel\Behaviour;

use FOF30\Event\Observer;
use FOF30\Model\DataModel;
use JDatabaseQuery;

defined('_JEXEC') or die;

/**
 * FOF model behavior class to add Joomla! content history support
 *
 * @since    2.1
 */
class ContentHistory extends Observer
{
	/**
	 * The event which runs after storing (saving) data to the database
	 *
	 * @param   DataModel  &$model  The table which calls this event
	 *
	 * @return  boolean  True to allow saving without an error
	 */
	public function onAfterStore(&$model)
	{
		$aliasParts = explode('.', $model->getContentType());
		$model->checkContentType();

		if (\JComponentHelper::getParams($aliasParts[0])->get('save_history', 0))
		{
			$historyHelper = new \JHelperContenthistory($model->getContentType());
			$historyHelper->store($model);
		}

		return true;
	}

	/**
	 * The event which runs before deleting a record
	 *
	 * @param   DataModel &$model  The table which calls this event
	 * @param   integer  $oid  The PK value of the record to delete
	 *
	 * @return  boolean  True to allow the deletion
	 */
	public function onBeforeDelete(&$model, $oid)
	{
		$aliasParts = explode('.', $model->getContentType());

		if (\JComponentHelper::getParams($aliasParts[0])->get('save_history', 0))
		{
			$historyHelper = new \JHelperContenthistory($model->getContentType());
			$historyHelper->deleteHistory($model);
		}

		return true;
	}

}