<?php namespace Illuminate\Routing;

use Illuminate\Container;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller {

	/**
	 * Execute an action on the controller.
	 *
	 * @param  Illuminate\Container  $container
	 * @param  Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function callAction(Container $container, Router $router, $method, $parameters)
	{
		$response = $this->callBeforeFilters($router, $method);

		// If no response was returned from the before filters, we'll call the regular
		// action on the controller and prepare the response. Then we will call the
		// after filters on the controller to wrap up any last minute processing.
		if (is_null($response))
		{
			$callable = array($this, $method);

			$response = call_user_func_array($callable, $parameters);
		}

		$request = $router->getRequest();

		// The after filters give the developers one last chance to do any last minute
		// processing on the response. The response has already been converted to a
		// full Response object and will also be handed off to the after filters.
		$response = $router->prepare($response, $request);

		$this->callAfterFilters($router, $method, $response);

		return $response;
	}

	/**
	 * Call the before filters on the controller.
	 *
	 * @param  Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @return mixed
	 */
	protected function callBeforeFilters($router, $method)
	{
		$response = null;

		$route = $router->getCurrentRoute();

		// When running the before filters we will simply spin through the list of the
		// filters and call each one on the current route objects, which will place
		// the proper parameters on the filter call, including the requests data.
		foreach ($this->getBeforeFilters($method) as $filter)
		{
			$response = $route->callFilter($filter, $router->getRequest());
		}

		return $response;
	}

	/**
	 * Get the before filters for the controller.
	 *
	 * @param  string  $method
	 * @return array
	 */
	protected function getBeforeFilters($method)
	{
		return array();
	}

	/**
	 * Call the before filters on the controller.
	 *
	 * @param  Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return mixed
	 */
	protected function callAfterFilters($router, $method, $response)
	{
		//
	}

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