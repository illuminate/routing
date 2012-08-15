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
	 * @param  bool    $secure
	 * @return string
	 */
	public function to($path, $secure = false)
	{
		if ($this->isValidUrl($path)) return;

		return $this->getBasePath($secure).rtrim('/'.$path, '/');
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
	 * Get the base URL for the request.
	 *
	 * @param  bool    $secure
	 * @return string
	 */
	protected function getBasePath($secure)
	{
		$r = $this->request;

		$scheme = $secure ? 'https://' : 'http://';

		return $scheme.$r->getHttpHost().$r->getBasePath().$r->getBaseUrl();
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
	 * Get the request instance.
	 *
	 * @return Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
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