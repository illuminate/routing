<?php namespace Illuminate\Routing;

use Closure;
use Symfony\Component\HttpFoundation\Request;

class Route {

	/**
	 * The methods the route responds to.
	 *
	 * @var array
	 */
	protected $methods;

	/**
	 * The URI pattern the route responds to.
	 *
	 * @var string
	 */
	protected $pattern;

	/**
	 * The compiled version of the route pattern.
	 *
	 * @var string
	 */
	protected $compiledPattern;

	/**
	 * The callback that should be executed for the route.
	 *
	 * @var array
	 */
	protected $action;

	/**
	 * The name of the route instance.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The filters that should be applied before the route.
	 *
	 * @var array
	 */
	protected $before = array();

	/**
	 * The filters that should be applied after the route.
	 *
	 * @var array
	 */
	protected $after = array();

	/**
	 * The parameters to be passed to the action.
	 *
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * The valid route wildcards.
	 *
	 * @var array
	 */
	protected $wildcards = array(
		'(:num)' => '([0-9]+)',
		'(:any)' => '([a-zA-Z0-9\.\-_%]+)',
		'(:all)' => '(.*)',
	);

	/**
	 * The valid route optional wildcards.
	 *
	 * @var array
	 */
	protected $optional = array(
		'/(:num?)' => '(?:/([0-9]+)',
		'/(:any?)' => '(?:/([a-zA-Z0-9\.\-_%]+)',
		'/(:all?)' => '(?:/(.*)',
	);

	/**
	 * Create a new Route instance.
	 *
	 * @param  string|array  $methods
	 * @param  string   $pattern
	 * @param  Closure  $action
	 * @return void
	 */
	public function __construct($methods, $pattern, Closure $action)
	{
		$this->action = $action;
		$this->pattern = $pattern;
		$this->methods = (array) $methods;
	}

	/**
	 * Determine if the given request matches the route.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return bool
	 */
	public function matches(Request $request)
	{
		$path = $request->getPathInfo() ?: '/';

		return preg_match($this->getCompiledPattern(), $path);
	}

	/**
	 * Get the compiled route pattern string.
	 *
	 * @return string
	 */
	protected function getCompiledPattern()
	{
		// If we have already compiled the pattern to get the regular expressions for
		// the wildcards, we will just return the already compiled versions so we
		// do not have to do this again each time this methods is called again.
		if (isset($this->compiledPattern))
		{
			return $this->compiledPattern;
		}

		$pattern = strtr($this->compileOptionalWildcards(), $this->wildcards);

		return $this->compiledPattern = '#^'.$pattern.'$#';
	}

	/**
	 * Compile the pattern's optional wildcards.
	 *
	 * @return string
	 */
	protected function compileOptionalWildcards()
	{
		// To compile the optional wildcards, we will make all of the replacements on
		// the route first and also request the number of replacement that we made
		// so we will come back and add the ending caps to the expression after.
		$pattern = $this->pattern;

		$wildcards = array_keys($this->optional);

		$values = array_values($this->optional);

		$pattern = str_replace($wildcards, $values, $pattern, $count);

		// If we made any replacements, we will come back and add the ending caps to
		// the regular expression so that we properly handle many segment stacked
		// on the end of the route's URI patterns, which could be quite common.
		if ($count > 0)
		{
			$pattern .= str_repeat(')?', $count);
		}

		return $pattern;
	}

	/**
	 * Run the route for the given request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return mixed
	 */
	public function run(Request $request)
	{
		$parameters = $this->compileParameters($request);

		return call_user_func_array($this->action, $parameters);
	}

	/**
	 * Compile the route parameters based on the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	protected function compileParameters(Request $request)
	{
		$path = $request->getPathInfo() ?: '/';

		preg_match($this->getCompiledPattern(), $path, $parameters);

		return array_slice($parameters, 1);
	}

	/**
	 * Set the name of the route.
	 *
	 * @param  string  $name
	 * @return Illuminate\Routing\Route
	 */
	public function name($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Add to the HTTP methods the route responds to.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function also()
	{
		$methods = array_map('strtoupper', func_get_args());

		$this->methods = array_merge($this->methods, $methods);

		return $this;
	}

	/**
	 * Set the before filters that should apply to the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function before()
	{
		$this->before = func_get_args();

		return $this;
	}

	/**
	 * Set the after filters that should apply to the route.
	 *
	 * @param  dynamic
	 * @return Illuminate\Routing\Route
	 */
	public function after()
	{
		$this->after = func_get_args();

		return $this;
	}

	/**
	 * Get the URI pattern the route responds to.
	 *
	 * @return string
	 */
	public function getPattern()
	{
		return $this->pattern;
	}

	/**
	 * Get the methods the route responds to.
	 *
	 * @return array
	 */
	public function getMethods()
	{
		return $this->methods;
	}

	/**
	 * Get the name of the route.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get the before filters assigned to the route.
	 *
	 * @return array
	 */
	public function getBeforeFilters()
	{
		return $this->before;
	}

	/**
	 * Get the after filters assigned to the route.
	 *
	 * @return array
	 */
	public function getAfterFilters()
	{
		return $this->after;
	}

}