<?php namespace Illuminate\Routing;

use Closure;
use Symfony\Component\HttpFoundation\Request;

class Router {

	/**
	 * The collection of all registered routes.
	 *
	 * @var array
	 */
	protected $allRoutes = array();

	/**
	 * The collection of all registered routes by method.
	 *
	 * @var array
	 */
	protected $routes = array();

	/**
	 * A collection of routes keyed by name.
	 *
	 * @var array
	 */
	protected $dictionary = array();

	/**
	 * Add a "get" route to the router.
	 *
	 * @param  string   $pattern
	 * @param  Closure  $action
	 * @return void
	 */
	public function get($pattern, Closure $action)
	{
		return $this->createRoute('GET', $pattern, $action);
	}

	/**
	 * Add a "post" route to the router.
	 *
	 * @param  string   $pattern
	 * @param  Closure  $action
	 * @return void
	 */
	public function post($pattern, Closure $action)
	{
		return $this->createRoute('POST', $pattern, $action);
	}

	/**
	 * Add a "put" route to the router.
	 *
	 * @param  string   $pattern
	 * @param  Closure  $action
	 * @return void
	 */
	public function put($pattern, Closure $action)
	{
		return $this->createRoute('PUT', $pattern, $action);
	}

	/**
	 * Add a "delete" route to the router.
	 *
	 * @param  string   $pattern
	 * @param  Closure  $action
	 * @return void
	 */
	public function delete($pattern, Closure $action)
	{
		return $this->createRoute('DELETE', $pattern, $action);
	}

	/**
	 * Add a route to the router that handles any method.
	 *
	 * @param  string   $pattern
	 * @param  Closure  $action
	 * @return void
	 */
	public function any($pattern, Closure $action)
	{
		return $this->createRoute('GET', $pattern, $action)->also('POST', 'PUT', 'DELETE');
	}

	/**
	 * Create and add a new route to the router.
	 *
	 * @param  string   $method
	 * @param  string   $pattern
	 * @param  CLosure  $action
	 * @return Illuminate\Routing\Route
	 */
	protected function createRoute($method, $pattern, Closure $action)
	{
		$this->addRoute($route = new Route($method, $pattern, $action));

		return $route;
	}

	/**
	 * Add a route to the router's collection.
	 *
	 * @param  Illuminate\Routing\Route  $route
	 * @return void
	 */
	public function addRoute(Route $route)
	{
		$this->allRoutes[] = $route;

		foreach ($route->getMethods() as $method)
		{
			$this->routes[$method][] = $route;
		}
	}

	/**
	 * Match the given request to a route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request
	 * @return Illuminate\Routing\Route
	 * @throws Illuminate\Routing\RouteNotFoundException
	 */
	public function match(Request $request)
	{
		$routes = $this->getRoutes($this->getRequestMethod($request));

		foreach ($routes as $route)
		{
			// To find the matching route we will simply call the matches method on
			// applicable routes, and if it matches we will return that route so
			// it can called by the consumer to get the route's response data.
			if ($route->matches($request)) return $route;
		}

		throw new RouteNotFoundException;
	}

	/**
	 * Find a route by a given name.
	 *
	 * @param  string  $name
	 * @return Illuminate\Routing\Route|null
	 */
	public function find($name)
	{
		if (isset($this->dictionary[$name]))
		{
			return $this->dictionary[$name];
		}

		// To find the named route, we can simply iterate through our collection of
		// all routes and check the name against the given name. Once we find it
		// we can add it to our dictionary of named routes for quick look-ups.
		foreach ($this->allRoutes as $route)
		{
			if ($name == $route->getName())
			{
				$this->dictionary[$name] = $route;

				return $route;
			}
		}
	}

	/**
	 * Get the request method from a request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return string
	 */
	protected function getRequestMethod(Request $request)
	{
		$method = $request->getMethod();

		return $method == 'HEAD' ? 'GET' : $method;
	}

	/**
	 * Get the routes for a given HTTP method.
	 *
	 * @param  string  $method
	 * @return array
	 */
	public function getRoutes($method)
	{
		if (array_key_exists($method, $this->routes))
		{
			return $this->routes[$method];
		}

		return array();
	}

}