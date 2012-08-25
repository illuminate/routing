<?php namespace Illuminate\Routing;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Router {

	/**
	 * The route collection instance.
	 *
	 * @var Symfony\Component\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * Create a new router instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->routes = new RouteCollection;
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function get($pattern, $action)
	{
		return $this->createRoute('get', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function post($pattern, $action)
	{
		return $this->createRoute('post', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function put($pattern, $action)
	{
		return $this->createRoute('put', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function delete($pattern, $action)
	{
		return $this->createRoute('delete', $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $method
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function match($method, $pattern, $action)
	{
		return $this->createRoute($method, $pattern, $action);
	}

	/**
	 * Add a new route to the collection.
	 *
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	public function any($pattern, $action)
	{
		return $this->createRoute('get|post|put|delete', $pattern, $action);
	}

	/**
	 * Create a new route instance.
	 *
	 * @param  string  $method
	 * @param  string  $pattern
	 * @param  mixed   $action
	 * @return Illuminate\Routing\Route
	 */
	protected function createRoute($method, $pattern, $action)
	{
		// We will force the action parameters to be an array just for convenience.
		// This will let us examine it for other attributes like middlewares or
		// a specific HTTP schemes the route only responds to, such as HTTPS.
		if ( ! is_array($action))
		{
			$action = array($action);
		}

		$name = $this->getName($method, $pattern, $action);

		// We will create the routes, setting the Closure callbacks on the instance
		// so we can easily access it later. If there are other parameters on a
		// routes we'll also set those requirements as well such as defaults.
		$callback = $this->getCallback($action);

		list($pattern, $optional) = $this->getOptional($pattern);

		$route = new Route($pattern, array('_call' => $callback));

		$route->setRequirement('_method', $method);

		// Once we have created the route, we will add them to our route collection
		// which contains all the other routes and is used to match on incoming
		// URL and their appropriate route destination and on URL generation.
		$this->setAttributes($route, $action, $optional);

		$this->routes->add($name, $route);

		return $route;
	}

	/**
	 * Set the attributes and requirements on the route.
	 *
	 * @param  Illuminate\Routing\Route  $route
	 * @param  array  $action
	 * @param  array  $optional
	 * @return void
	 */
	protected function setAttributes(Route $route, $action, $optional)
	{
		// First we will set the requirement for the HTTP schemes. Some routes may
		// only respond to requests using the HTTPS scheme, while others might
		// respond to all, regardless of the scheme, so we'll set that here.
		if (in_array('https', $action))
		{
			$route->setRequirement('_scheme', 'https');
		}

		if (in_array('http', $action))
		{
			$route->setRequirement('_scheme', 'http');
		}

		// Once the scheme requirements have been made, we will set the before and
		// after middleware options, which will be used to run any middlewares
		// by the consuming library, making halting the request cycles easy.
		if (isset($action['before']))
		{
			$route->setOption('_before', $action['before']);
		}

		if (isset($action['after']))
		{
			$route->setOption('_after', $action['after']);	
		}

		// Finally we will set any default route wildcards present to be bound to
		// null by default. This just lets us conveniently define an optional
		// wildcard without having to worry about binding a value manually.
		foreach ($optional as $key)
		{
			$route->setDefault($key, null);
		}
	}

	/**
	 * Modify the pattern and extract optional parameters.
	 *
	 * @param  string  $pattern
	 * @return array
	 */
	protected function getOptional($pattern)
	{
		$optional = array();

		preg_match_all('#\{(\w+)\?\}#', $pattern, $matches);

		// For each matching value, we will extract the name of the optional values
		// and add it to our array, then we will replace the place-holder to be
		// a valid place-holder minus this optional indicating question mark.
		foreach ($matches[0] as $key => $value)
		{
			$optional[] = $name = $matches[1][$key];

			$pattern = str_replace($value, '{'.$name.'}', $pattern);
		}

		return array($pattern, $optional);
	}

	/**
	 * Get the name of the route.
	 *
	 * @param  string  $method
	 * @param  string  $pattern
	 * @param  array   $action
	 * @return string
	 */
	protected function getName($method, $pattern, array $action)
	{
		return isset($action['as']) ? $action['as'] : md5($method.$pattern);
	}

	/**
	 * Get the callback from the given action array.
	 *
	 * @param  array    $action
	 * @return Closure
	 */
	protected function getCallback(array $action)
	{
		foreach ($action as $attribute)
		{
			if ($attribute instanceof Closure) return $attribute;
		}

		throw new \InvalidArgumentException("Action doesn't contain Closure.");
	}

	/**
	 * Match the given request to a route object.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Illuminate\Routing\Route
	 */
	public function dispatch(Request $request)
	{
		// We will catch any exceptions thrown during routing and convert it to a
		// HTTP Kernel equivalent exception, since that is a more generic type
		// that's used by the Illuminate foundation framework for responses.
		try
		{
			$matcher = $this->getUrlMatcher($request);

			$parameters = $matcher->match($request->getPathInfo());
		}

		// The Symfony routing component's exceptions implement this interface we
		// can type-hint it to make sure we're only providing special handling
		// for those exceptions, and not other random exceptions that occur.
		catch (ExceptionInterface $e)
		{
			$this->handleRoutingException($e);
		}

		$route = $this->routes->get($parameters['_route']);

		// If we found a route, we will grab the actual route objects out of this
		// route collection and set the matching parameters on the instance so
		// we will easily access them later if the route action is executed.
		$route->setParameters($parameters);

		return $route;
	}

	/**
	 * Create a new URL matcher instance.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $requset
	 * @return Symfony\Component\Routing\Matcher\UrlMatcher
	 */
	protected function getUrlMatcher(Request $request)
	{
		$context = new RequestContext;

		$context->fromRequest($request);

		return new UrlMatcher($this->routes, $context);
	}

	/**
	 * Convert routing exception to HttpKernel version.
	 *
	 * @param  Exception  $e
	 * @return void
	 */
	protected function handleRoutingException(\Exception $e)
	{
		if ($e instanceof ResourceNotFoundException)
		{
			throw new NotFoundHttpException($e->getMessage());
		}

		// The method not allowed exception is essentially a HTTP 405 error, so we
		// will grab the allowed methods when converting into the HTTP Kernel's
		// version of the exact error. This gives us a good RESTful API site.
		elseif ($e instanceof MethodNotAllowedException)
		{
			$allowed = $e->getAllowedMethods();

			throw new MethodNotAllowedHttpException($allowed, $e->getMessage());
		}
	}

	/**
	 * Retrieve the entire route collection.
	 * 
	 * @return Symfony\Component\Routing\RouteCollection
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

}