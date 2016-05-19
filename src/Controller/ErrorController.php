<?php

namespace Vicus\Controller;
/**
 * Description of ErrorContorller
 *
 * @author Michael Koert <mkoert at bluebikeproductions.com>
 */

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\FlattenException;

class ErrorController
{

	public function indexAction(FlattenException $exception)
    {
        // $msg = 'Something went wrong! ('.$exception->getMessage().')';
		//
        // return new Response($msg, $exception->getStatusCode());

		$msg = 'Something went wrong!';

        return new Response($msg, $exception->getStatusCode());

    }
	public function exceptionAction(FlattenException $exception)
    {
        // $msg = 'Something went wrong! ('.$exception->getMessage().')';
		//
        // return new Response($msg, $exception->getStatusCode());

		$msg = 'Something went wrong!';

        return new Response($msg, $exception->getStatusCode());
    }
}
