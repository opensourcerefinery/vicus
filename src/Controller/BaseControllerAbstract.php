<?php

namespace Vicus\Controller;

/**
 * Description of BaseControllerAbstract
 *
 * @author Michael Koert <mkoert at bluebikeproductions.com>
 */
abstract class BaseControllerAbstract
{

	abstract protected function _beforeAction($request);

	public function __destruct()
	{
		//send terminate event
	}

}
