<?php
/**
 * @package     FOF
 * @copyright   2010-2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace FOF30\Container\Exception;

use Exception;

class NoComponent extends \Exception
{
	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		if (empty($message))
		{
			$message = 'No component specified building the Container object';
		}

		if (empty($code))
		{
			$code = 500;
		}

		parent::__construct($message, $code, $previous);
	}
}