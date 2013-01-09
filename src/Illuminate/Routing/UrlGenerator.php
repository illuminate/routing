<?php namespace Illuminate\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyGenerator;

class UrlGenerator {

	/**
	 * The route collection.
	 *
	 * @var Symfony\Component\Routing\RouteCollection
	 */
	protected $routes;

	/**
	 * The request instance.
	 *
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * The Symfony routing URL generator.
	 *
	 * @var Symfony\Component\Routing\Generator\UrlGenerator
	 */
	protected $generator;

	/**
	 * Create a new URL Generator instance.
	 *
	 * @param  Symfony\Component\Routing\RouteCollection  $routes
	 * @param  Symfony\Component\HttpFoundation\Request   $request
	 * @return void
	 */
	public function __construct(RouteCollection $routes, Request $request)
	{
		$this->routes = $routes;

		$this->setRequest($request);
	}

	/**
	 * Generate a absolute URL to the given path.
	 *
	 * @param  string  $path
	 * @param  array   $parameters
	 * @param  bool    $secure
	 * @return string
	 */
	public function to($path, $parameters = array(), $secure = null)
	{
		if ($this->isValidUrl($path)) return $path;

		$scheme = $this->getScheme($secure);

		// Once we have the scheme we will compile the "tail" by collapsing the values
		// into a single string delimited by slashes. This just makes it convenient
		// for passing the array of parameters to this URL as a list of segments.
		$tail = trim(implode('/', (array) $parameters), '/');

		$root = $this->getRootUrl($scheme);

		return $root.rtrim('/'.$path.'/'.$tail, '/');
	}

	/**
	 * Generate a secure, absolute URL to the given path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function secure($path, $parameters = array())
	{
		return $this->to($path, $parameters, true);
	}

	/**
	 * Generate a URL to an application asset.
	 *
	 * @param  string  $path
	 * @param  bool    $secure
	 * @return string
	 */
	public function asset($path, $secure = null)
	{
		if ($this->isValidUrl($path)) return $path;

		$root = $this->getRootUrl($this->getScheme($secure));

		// Once we get the root URL, we will check to see if it contains an index.php
		// file in the paths. If it does, we will remove it since it is not needed
		// for asset paths, but only for routes to endpoints in the application.
		if (str_contains($root, 'index.php'))
		{
			$root = str_replace('/index.php', '', $root);
		}

		return $root.rtrim('/'.$path, '/');
	}

	/**
	 * Generate a URL to a secure asset.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function secureAsset($path)
	{
		return $this->asset($path, true);
	}

	/**
	 * Get the scheme for a raw URL.
	 *
	 * @param  bool    $secure
	 * @return string
	 */
	protected function getScheme($secure)
	{
		if (is_null($secure))
		{
			return $this->request->getScheme().'://';
		}
		else
		{
			return $secure ? 'https://' : 'http://';
		}
	}

	/**
	 * Get the URL to a named route.
	 *
	 * @param  string  $name
	 * @param  array   $parameters
	 * @param  bool    $absolute
	 * @return string
	 */
	public function route($name, $parameters = array(), $absolute = true)
	{
		return $this->generator->generate($name, $parameters, $absolute);
	}

	/**
	 * Get the URL to a controller action.
	 *
	 * @param  string  $action
	 * @param  array   $parameters
	 * @param  bool    $absolute
	 * @return string
	 */
	public function action($action, $parameters = array(), $absolute = true)
	{
		if (isset($this->actionMap[$action]))
		{
			$name = $this->actionMap[$action];

			return $this->route($name, $parameters, $absolute);
		}

		// If haven't already mapped this action to a URI yet, we will need to spin
		// through all of the routes looking for routes that routes to the given
		// controller's action, then we will cache them off and build the URL.
		foreach ($this->routes as $name => $route)
		{
			if ($action == $route->getOption('_uses'))
			{
				$this->actionMap[$action] = $name;

				return $this->route($name, $parameters, $absolute);
			}
		}
	}

	/**
	 * Get the base URL for the request.
	 *
	 * @param  string  $scheme
	 * @return string
	 */
	protected function getRootUrl($scheme)
	{
		$root = $this->request->root();

		$start = starts_with($root, 'http://') ? 'http://' : 'https://';

		return preg_replace('~'.$start.'~', $scheme, $root, 1);
	}

	/**
	 * Determine if the given path is a valid URL.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function isValidUrl($path)
	{
		return filter_var($path, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Get the request instance.
	 *
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set the current request instance.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;

		$context = new RequestContext;

		$context->fromRequest($this->request);

		$this->generator = new SymfonyGenerator($this->routes, $context);
	}

	/**
	 * Get the Symfony URL generator instance.
	 *
	 * @return Symfony\Component\Routing\Generator\UrlGenerator
	 */
	public function getGenerator()
	{
		return $this->generator;
	}

	/**
	 * Get the Symfony URL generator instance.
	 *
	 * @param  Symfony\Component\Routing\Generator\UrlGenerator  $generator
	 * @return void
	 */
	public function setGenerator(SymfonyGenerator $generator)
	{
		$this->generator = $generator;
	}

}
