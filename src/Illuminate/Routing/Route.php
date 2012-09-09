<?php namespace Illuminate\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route as BaseRoute;

class Route extends BaseRoute {

	/**
	 * The router instance.
	 *
	 * @param  Illuminate\Routing\Router
	 */
	protected $router;

	/**
	 * The matching parameter array.
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * Execute the route and return the response.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return mixed
	 */	
	public function run(Request $request)
	{
		$response = $this->callBeforeFilters($request);

		// We will only call the router callable if no "before" middlewares returned
		// a response. If they do, we will consider that the response to requests
		// so that the request "lifecycle" will be easily halted for filtering.
		if ( ! isset($response))
		{
			$response = $this->callCallable();
		}

		$response = $this->router->prepareResponse($response, $request);

		// Once we have the "prepared" response, we will iterate through every after
		// filter and call each of them with the request and the response so they
		// can perform any final work that needs to be done after a route call.
		foreach ($this->getAfterFilters() as $filter)
		{
			$this->callFilter($filter, $request, array($response));
		}

		return $response;
	}

	/**
	 * Call the callable Closure attached to the route.
	 *
	 * @return mixed
	 */
	protected function callCallable()
	{
		$variables = $this->getVariables();

		return call_user_func_array($this->parameters['_call'], $variables);
	}

	/**
	 * Call all of the before filters on the route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @return mixed
	 */
	protected function callBeforeFilters(Request $request)
	{
		$before = $this->getAllBeforeFilters($request);

		$response = null;

		// Once we have each middlewares, we will simply iterate through them and call
		// each one of them with the request. We will set the response variable to
		// whatever it may return so that it may override the request processes.
		foreach ($before as $filter)
		{
			$response = $this->callFilter($filter, $request);
		}

		return $response;
	}

	/**
	 * Get all of the before filters to run on the route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return array
	 */
	protected function getAllBeforeFilters(Request $request)
	{
		$before = $this->getBeforeFilters();

		return array_merge($before, $this->router->findPatternFilters($request));	
	}

	/**
	 * Call a given filter with the parameters.
	 *
	 * @param  string  $name
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  array   $parameters
	 * @return mixed
	 */
	protected function callFilter($name, Request $request, array $parameters = array())
	{
		array_unshift($parameters, $request);

		if ( ! is_null($callable = $this->router->getFilter($name)))
		{
			return call_user_func_array($callable, $parameters);
		}
	}

	/**
	 * Get the variables to the callback.
	 *
	 * @return array
	 */
	public function getVariables()
	{
		$variables = array_flip($this->compile()->getVariables());

		return array_values(array_intersect_key($this->parameters, $variables));
	}

	/**
	 * Force a given parameter to match a regular expression.
	 *
	 * @param  string  $name
	 * @param  string  $expression
	 * @return Illuminate\Routing\Route
	 */
	public function where($name, $expression)
	{
		$this->setRequirement($name, $expression);

		return $this;
	}

	/**
	 * Set the default value for a parameter.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return Illuminate\Routing\Route
	 */
	public function defaults($key, $value)
	{
		$this->setDefault($key, $value);

		return $this;
	}

	/**
	 * Set the before filters on the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function before()
	{
		$current = $this->getBeforeFilters();

		$before = array_unique(array_merge($current, func_get_args()));

		$this->setOption('_before', $before);

		return $this;
	}

	/**
	 * Set the after filters on the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function after()
	{
		$current = $this->getAfterFilters();

		$after = array_unique(array_merge($current, func_get_args()));

		$this->setOption('_after', $after);

		return $this;
	}

	/**
	 * Get the before filters on the route.
	 *
	 * @return array
	 */
	public function getBeforeFilters()
	{
		return $this->getOption('_before') ?: array();
	}

	/**
	 * Set the before filters on the route.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function setBeforeFilters($value)
	{
		$this->setOption('_before', explode('|', $value));
	}

	/**
	 * Get the after filters on the route.
	 *
	 * @return array
	 */
	public function getAfterFilters()
	{
		return $this->getOption('_after') ?: array();
	}

	/**
	 * Set the after filters on the route.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function setAfterFilters($value)
	{
		$this->setOption('_after', explode('|', $value));
	}

	/**
	 * Set the matching parameter array on the route.
	 *
	 * @param  array  $parameters
	 * @return void
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}

	/**
	 * Set the Router instance on the route.
	 *
	 * @param  Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;
	}

}