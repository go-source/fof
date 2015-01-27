<?php
/**
 * @package     FOF
 * @copyright   2010-2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace FOF30\Factory;

use FOF30\Controller\Controller;
use FOF30\Factory\Exception\ControllerNotFound;
use FOF30\Factory\Exception\ModelNotFound;
use FOF30\Factory\Exception\ToolbarNotFound;
use FOF30\Factory\Exception\ViewNotFound;
use FOF30\Model\Model;
use FOF30\Toolbar\Toolbar;
use FOF30\View\View;

defined('_JEXEC') or die;

/**
 * Magic MVC object factory. This factory will "magically" create MVC objects even if the respective classes do not
 * exist, based on information in your fof.xml file.
 *
 * Note: This factory class will look for MVC objects in BOTH component sections (front-end, back-end), not just the one
 * you are currently running in. If no class is found a new object will be created magically. This is the same behaviour
 * as FOF 2.x.
 */
class MagicSwitchFactory extends SwitchFactory implements FactoryInterface
{
	/**
	 * Create a new Controller object
	 *
	 * @param   string  $viewName  The name of the view we're getting a Controller for.
	 * @param   array   $config    Optional MVC configuration values for the Controller object.
	 *
	 * @return  Controller
	 */
	public function controller($viewName, array $config = array())
	{
		try
		{
			return parent::controller($viewName, $config);
		}
		catch (ControllerNotFound $e)
		{
			$magic = new Magic\ControllerFactory($this->container);
			return $magic->make($viewName, $config);
		}
	}

	/**
	 * Create a new Model object
	 *
	 * @param   string  $viewName  The name of the view we're getting a Model for.
	 * @param   array   $config    Optional MVC configuration values for the Model object.
	 *
	 * @return  Model
	 */
	public function model($viewName, array $config = array())
	{
		try
		{
			return parent::model($viewName, $config);
		}
		catch (ModelNotFound $e)
		{
			$magic = new Magic\ModelFactory($this->container);
			return $magic->make($viewName, $config);
		}
	}

	/**
	 * Create a new View object
	 *
	 * @param   string  $viewName  The name of the view we're getting a View object for.
	 * @param   string  $viewType  The type of the View object. By default it's "html".
	 * @param   array   $config    Optional MVC configuration values for the View object.
	 *
	 * @return  View
	 */
	public function view($viewName, $viewType = 'html', array $config = array())
	{
		try
		{
			return parent::view($viewName, $viewType, $config);
		}
		catch (ViewNotFound $e)
		{
			$magic = new Magic\ViewFactory($this->container);
			return $magic->make($viewName, $viewType, $config);
		}
	}

	/**
	 * Creates a new Toolbar
	 *
	 * @param   array  $config  The configuration values for the Toolbar object
	 *
	 * @return  Toolbar
	 */
	function toolbar(array $config = array())
	{
		$appConfig = $this->container->appConfig;

		$defaultConfig = array(
			'useConfigurationFile'  => true,
			'renderFrontEndButtons' => in_array($appConfig->get("views.*.config.renderFrontEndButtons"), array(true, 'true', 'yes', 'on', 1)),
			'renderFrontendSubmenu' => in_array($appConfig->get("views.*.config.renderFrontendSubmenu"), array(true, 'true', 'yes', 'on', 1)),
		);

		$config = array_merge($defaultConfig, $config);

		return parent::toolbar($config);
	}
}