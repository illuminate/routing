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
		$response = $this->callBeforeMiddlewares($request);

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
		foreach ($this->getAfterMiddlewares() as $middleware)
		{
			$this->callMiddleware($middleware, $request, array($response));
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
	 * Call all of the before middlewares on the route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @return mixed
	 */
	protected function callBeforeMiddlewares(Request $request)
	{
		$before = $this->getAllBeforeMiddlewares($request);

		$response = null;

		// Once we have each middlewares, we will simply iterate through them and call
		// each one of them with the request. We will set the response variable to
		// whatever it may return so that it may override the request processes.
		foreach ($before as $middleware)
		{
			$response = $this->callMiddleware($middleware, $request);
		}

		return $response;
	}

	/**
	 * Get all of the before middlewares to run on the route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return array
	 */
	protected function getAllBeforeMiddlewares(Request $request)
	{
		$before = $this->getBeforeMiddlewares();

		return array_merge($before, $this->router->findPatternMiddlewares($request));	
	}

	/**
	 * Call a given middleware with the parameters.
	 *
	 * @param  string  $name
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  array   $parameters
	 * @return mixed
	 */
	protected function callMiddleware($name, Request $request, array $parameters = array())
	{
		array_unshift($parameters, $request);

		if ( ! is_null($callable = $this->router->getMiddleware($name)))
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
	 * Set the before middlewares on the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function before()
	{
		$current = $this->getBeforeMiddlewares();

		$before = array_unique(array_merge($current, func_get_args()));

		$this->setOption('_before', $before);

		return $this;
	}

	/**
	 * Set the after middlewares on the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function after()
	{
		$current = $this->getAfterMiddlewares();

		$after = array_unique(array_merge($current, func_get_args()));

		$this->setOption('_after', $after);

		return $this;
	}

	/**
	 * Get the before middlewares on the route.
	 *
	 * @return array
	 */
	public function getBeforeMiddlewares()
	{
		return $this->getOption('_before') ?: array();
	}

	/**
	 * Set the before middlewares on the route.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function setBeforeMiddlewares($value)
	{
		$this->setOption('_before', explode('|', $value));
	}

	/**
	 * Get the after middlewares on the route.
	 *
	 * @return array
	 */
	public function getAfterMiddlewares()
	{
		return $this->getOption('_after') ?: array();
	}

	/**
	 * Set the after middlewares on the route.
	 *
	 * @param  string  $value
	 * @return void
	 */
	public function setAfterMiddlewares($value)
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