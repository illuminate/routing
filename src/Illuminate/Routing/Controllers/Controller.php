<?php namespace Illuminate\Routing\Controllers;

use ReflectionClass;
use Illuminate\Container;
use Illuminate\Routing\Router;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller {

	/**
	 * The reflection class instance.
	 *
	 * @var ReflectionClass
	 */
	protected $reflection;

	/**
	 * The controller filter parser.
	 *
	 * @var Illuminate\Routing\FilterParser
	 */
	protected $filterParser;

	/**
	 * The filters that have been specified.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Register a new "before" filter on the controller.
	 *
	 * @param  string  $filter
	 * @param  array   $options
	 * @return void
	 */
	public function beforeFilter($filter, array $options = array())
	{
		$options['run'] = $filter;

		$this->filters[] = new Before($options);
	}

	/**
	 * Register a new "after" filter on the controller.
	 *
	 * @param  string  $filter
	 * @param  array   $options
	 * @return void
	 */
	public function afterFilter($filter, array $options = array())
	{
		$options['run'] = $filter;

		$this->filters[] = new After($options);
	}

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
		$this->reflection = new ReflectionClass($this);

		$this->filterParser = $container['filter.parser'];

		// If no response was returned from the before filters, we'll call the regular
		// action on the controller and prepare the response. Then we will call the
		// after filters on the controller to wrap up any last minute processing.
		$response = $this->callBeforeFilters($router, $method);

		if (is_null($response))
		{
			$response = $this->directCallAction($method, $parameters);
		}

		return $this->processResponse($router, $method, $response);
	}

	/**
	 * Call the given action with the given parameters.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	protected function directCallAction($method, $parameters)
	{
		return call_user_func_array(array($this, $method), $parameters);
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
		$class = 'Illuminate\Routing\Controllers\Before';

		return $this->filterParser->parse($this->reflection, $this, $request, $method, $class);
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
		$class = 'Illuminate\Routing\Controllers\After';

		return $this->filterParser->parse($this->reflection, $this, $request, $method, $class);
	}

	/**
	 * Get the code registered filters.
	 *
	 * @return array
	 */
	public function getFilters()
	{
		return $this->filters;
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