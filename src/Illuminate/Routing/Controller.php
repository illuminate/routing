<?php namespace Illuminate\Routing;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller {

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		throw new NotFoundHttpException;
	}

}