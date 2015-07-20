<?php
/**
 * @package     FOF
 * @copyright   2010-2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

use JComponentHelper;
use JFactory;
use JLoader;

namespace FOF30\Params;

defined('_JEXEC') or die;

/**
 * A helper class to quickly get the component parameters
 */
class Params
{

	/** @var  Container  The container we belong to */
	protected $container = null;

	/**
	 * Cached component parameters
	 *
	 * @var \Joomla\Registry\Registry
	 */
	private $params = null;

	/**
	 * Public constructor for the params object
	 *
	 * @param  \FOF30\Container\Container $container  The container we belong to
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;

		$this->reload();
	}

	/**
	 * Reload the params
	 */
	public function reload()
	{
		// Load the params once
		JLoader::import('joomla.application.component.helper');
		$this->params = JComponentHelper::getParams($this->container->componentName);
	}

	/**
	 * Returns the value of a component configuration parameter
	 *
	 * @param   string $key     The parameter to get
	 * @param   mixed  $default Default value
	 *
	 * @return  mixed
	 */
	public function get($key, $default = null)
	{
		return $this->params->get($key, $default);
	}

	/**
	 * Sets the value of a component configuration parameter
	 *
	 * @param   string $key    The parameter to set
	 * @param   mixed  $value  The value to set
	 *
	 * @return  void
	 */
	public function set($key, $value)
	{
		$this->setParams([$key => $value]);
	}

	/**
	 * Sets the value of multiple component configuration parameters at once
	 *
	 * @param   array  $params  The parameters to set
	 *
	 * @return  void
	 */
	public function setParams(array $params)
	{
		foreach ($params as $key => $value)
		{
			$this->params->set($key, $value);
		}
	}

	/**
	 * Actually Save the params into the db
	 */
	public function save()
	{
		$db   = $this->container->db;
		$data = $this->params->toString();

		$sql  = $db->getQuery(true)
				   ->update($db->qn('#__extensions'))
				   ->set($db->qn('params') . ' = ' . $db->q($data))
				   ->where($db->qn('element') . ' = ' . $db->q($this->container->componentName))
				   ->where($db->qn('type') . ' = ' . $db->q('component'));

		$db->setQuery($sql);

		try
		{
			$db->execute();
		}
		catch (\Exception $e)
		{
			// Don't sweat if it fails
		}
	}
}