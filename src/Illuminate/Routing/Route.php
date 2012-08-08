<?php namespace Illuminate\Routing;

use Symfony\Component\Routing\Route as BaseRoute;

class Route extends BaseRoute {

	/**
	 * The matching parameter array.
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * Execute the route and return the response.
	 *
	 * @return mixed
	 */
	public function run()
	{
		$variables = $this->getVariables();

		return call_user_func_array($this->parameters['_call'], $variables);
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
	 * Get the after middlewares on the route.
	 *
	 * @return array
	 */
	public function getAfterMiddlewares()
	{
		return $this->getOption('_after') ?: array();
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

}