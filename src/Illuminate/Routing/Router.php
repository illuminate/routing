<?php namespace Illuminate\Routing;

use Closure;
use Illuminate\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Response;
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
	 * The route filters.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * The pattern to filter bindings.
	 *
	 * @var array
	 */
	protected $patternFilters = array();

	/**
	 * The global filters for the router.
	 *
	 * @var array
	 */
	protected $globalFilters = array();

	/**
	 * The inversion of control container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * Create a new router instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function __construct(Container $container = null)
	{
		$this->container = $container;

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
			$action = $this->parseAction($action);
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
	 * Parse the given route action into array form.
	 *
	 * @param  mixed  $action
	 * @return array
	 */
	protected function parseAction($action)
	{
		// If the action is just a Closure we'll stick it in an array and just send
		// it back out. However if it's a string we'll just assume it's meant to
		// route into a controller action and change it to a controller array.
		if ($action instanceof Closure)
		{
			return array($action);
		}
		elseif (is_string($action))
		{
			return array('uses' => $action);
		}

		throw new \InvalidArgumentException("Unroutable action.");
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
			$route->setBeforeFilters($action['before']);
		}

		if (isset($action['after']))
		{
			$route->setAfterFilters($action['after']);
		}

		// If there is a "uses" key on the route it means it is using a controller
		// instead of a Closures route. So, we'll need to set that as an option
		// on the route so we can easily do reverse routing ot the route URI.
		if (isset($action['uses']))
		{
			$route->setOption('_uses', $action['uses']);
		}

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
		foreach ($action as $key => $attribute)
		{
			// If the action has a "uses" key, the route is pointing to a controller
			// action instead of using a Closure. So, we'll create a Closure that
			// resolves the controller instances and calls the needed function.
			if ($key === 'uses')
			{
				return $this->createControllerCallback($attribute);
			}
			elseif ($attribute instanceof Closure)
			{
				return $attribute;
			}
		}
	}

	/**
	 * Create the controller callback for a route.
	 *
	 * @param  string   $attribute
	 * @return Closure
	 */
	protected function createControllerCallback($attribute)
	{
		$container = $this->container;

		list($controller, $method) = explode('@', $attribute);

		// We'll return a Closure that is able to resolve the controller instance and
		// call the appropriate method on the controller, passing in the arguments
		// it receives. Controllers are created with the IoC container instance.
		return function() use ($container, $controller, $method)
		{
			$callable = array($container->make($controller), $method);

			return call_user_func_array($callable, func_get_args());
		};
	}

	/**
	 * Get the response for a given request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Resonse
	 */
	public function dispatch(Request $request)
	{
		// First we will call the "before" global middlware, which we'll give a chance
		// to override the normal requests process when a response is returned by a
		// middleware. Otherwise we'll call the route just like a normal request.
		$response =  $this->callGlobalFilter($request, 'before');

		if ( ! is_null($response))
		{
			return $this->prepare($response, $request);
		}

		$route = $this->findRoute($request);

		// Once we have the route, we can just run it to get the responses, which will
		// always be instances of the Response class. Once we have the responses we
		// will execute the global "after" middlewares to finish off the request.
		$response = $route->run($request);

		$this->callAfterFilter($request, $response);

		return $response;
	}

	/**
	 * Match the given request to a route object.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return Illuminate\Routing\Route
	 */
	protected function findRoute(Request $request)
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

		$route->setRouter($this);

		return $route;
	}

	/**
	 * Register a "before" routing filter.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function before(Closure $callback)
	{
		$this->globalFilters['before'][] = $callback;
	}

	/**
	 * Register an "after" routing filter.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function after(Closure $callback)
	{
		$this->globalFilters['after'][] = $callback;
	}

	/**
	 * Register a "close" routing filter.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function close(Closure $callback)
	{
		$this->globalFilters['close'][] = $callback;
	}

	/**
	 * Register a "finish" routing filters.
	 *
	 * @param  Closure  $callback
	 * @return void
	 */
	public function finish(Closure $callback)
	{
		$this->globalFilters['finish'][] = $callback;
	}

	/**
	 * Register a new filter with the application.
	 *
	 * @param  string   $name
	 * @param  Closure  $callback
	 * @return void
	 */
	public function addFilter($name, Closure $callback)
	{
		$this->filters[$name] = $callback;
	}

	/**
	 * Get a registered filter callback.
	 *
	 * @param  string   $name
	 * @return Closure
	 */
	public function getFilter($name)
	{
		if (array_key_exists($name, $this->filters))
		{
			return $this->filters[$name];
		}
	}

	/**
	 * Tie a registered filter to a URI pattern.
	 *
	 * @param  string  $pattern
	 * @param  string|array  $name
	 * @return void
	 */
	public function matchFilter($pattern, $names)
	{
		foreach ((array) $names as $name)
		{
			$this->patternFilters[$pattern][] = $name;
		}
	}

	/**
	 * Find the patterned filters matching a request.
	 *
	 * @param  Illuminate\Foundation\Request  $request
	 * @return array
	 */
	public function findPatternFilters(Request $request)
	{
		$filters = array();

		foreach ($this->patternFilters as $pattern => $values)
		{
			// To find the pattern middlewares for a request, we just need to check the
			// registered patterns against the path info for the current request to
			// the application, and if it matches we'll merge in the middlewares.
			if (str_is('/'.$pattern, $request->getPathInfo()))
			{
				$filters = array_merge($filters, $values);
			}
		}

		return $filters;
	}

	/**
	 * Call the "after" global filters.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return mixed
	 */
	protected function callAfterFilter(Request $request, Response $response)
	{
		$this->callGlobalFilter($request, 'after', array($response));

		$this->callGlobalFilter($request, 'close', array($response));
	}

	/**
	 * Call the "finish" global filter.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return mixed
	 */
	public function callFinishFilter(Request $request, Response $response)
	{
		return $this->callGlobalFilter($request, 'finish', array($response));
	}

	/**
	 * Call a given global filter with the parameters.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return mixed
	 */
	protected function callGlobalFilter(Request $request, $name, array $parameters = array())
	{
		array_unshift($parameters, $request);

		if (isset($this->globalFilters[$name]))
		{
			// There may be multiple handlers registered for a global middleware so we
			// will need to spin through each one and execute each of them and will
			// return back first non-null responses we come across from a filter.
			foreach ($this->globalFilters[$name] as $filter)
			{
				$response = call_user_func_array($filter, $parameters);

				if ( ! is_null($response)) return $response;
			}
		}
	}

	/**
	 * Prepare the given value as a Response object.
	 *
	 * @param  mixed  $value
	 * @param  Illuminate\Foundation\Request  $request
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function prepare($value, Request $request)
	{
		if ( ! $value instanceof Response) $value = new Response($value);

		return $value->prepare($request);
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
	 * Retrieve the entire route collection.
	 * 
	 * @return Symfony\Component\Routing\RouteCollection
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Set the container instance on the router.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

}