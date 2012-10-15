<?php namespace Illuminate\Routing;

use ReflectionClass;
use Illuminate\Container;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller {

	/**
	 * The controller filter parser.
	 *
	 * @var Illuminate\Routing\FilterParser
	 */
	protected $filterParser;

	/**
	 * Execute an action on the controller.
	 *
	 * @param  Illuminate\Container  $container
	 * @param  Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function callAction(Container $container, Router $router, $method, $parameters)
	{
		$this->filterParser = $container['filter.parser'];

		// If no response was returned from the before filters, we'll call the regular
		// action on the controller and prepare the response. Then we will call the
		// after filters on the controller to wrap up any last minute processing.
		$response = $this->callBeforeFilters($router, $method);

		if (is_null($response))
		{
			$response = call_user_func_array(array($this, $method), $parameters);
		}

		return $this->processResponse($router, $method, $response);
	}

	/**
	 * Process a controller action response.
	 *
	 * @param  Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  mixed   $response
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	protected function processResponse($router, $method, $response)
	{
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
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return mixed
	 */
	protected function callBeforeFilters($router, $request, $method)
	{
		$response = null;

		$route = $router->getCurrentRoute();

		// When running the before filters we will simply spin through the list of the
		// filters and call each one on the current route objects, which will place
		// the proper parameters on the filter call, including the requests data.
		$request = $router->getRequest();

		$filters = $this->getBeforeFilters($request, $method);

		foreach ($filters as $filter)
		{
			$response = $route->callFilter($filter, $request);
		}

		return $response;
	}

	/**
	 * Get the before filters for the controller.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return array
	 */
	protected function getBeforeFilters($request, $method)
	{
		$class = 'Illuminate\Routing\BeforeFilter';

		return $this->filterParser->parse($this, $request, $method, $class);
	}

	/**
	 * Call the after filters on the controller.
	 *
	 * @param  Illuminate\Routing\Router  $router
	 * @param  string  $method
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return mixed
	 */
	protected function callAfterFilters($router, $method, $response)
	{
		$route = $router->getCurrentRoute();

		// When running the before filters we will simply spin through the list of the
		// filters and call each one on the current route objects, which will place
		// the proper parameters on the filter call, including the requests data.
		$request = $router->getRequest();

		$filters = $this->getAfterFilters($request, $method);

		foreach ($filters as $filter)
		{
			$route->callFilter($filter, $request, array($response));
		}
	}

	/**
	 * Get the after filters for the controller.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return array
	 */
	protected function getAfterFilters($request, $method)
	{
		$class = 'Illuminate\Routing\AfterFilter';

		return $this->filterParser->parse($this, $request, $method, $class);
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