<?php namespace Illuminate\Routing;

class ActiveRoute {

	/**
	 * The base route instance.
	 *
	 * @var Illuminate\Routing\Route
	 */
	protected $route;

	/**
	 * The HTTP request instance.
	 *
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Create a new active route instance.
	 *
	 * @param  Illuminate\Routing\Route  $route
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function __construct(Route $route, Request $request)
	{
		$this->route = $route;
		$this->request = $request;
		$this->compileRouteParameters();
	}

	/**
	 * Compile the route's parameters.
	 *
	 * @return void
	 */
	protected function compileRouteParameters()
	{
		
	}

	/**
	 * Get the base route instance.
	 *
	 * @return Illuminate\Routing\Route
	 */
	public function getRoute()
	{
		return $this->route;
	}

}