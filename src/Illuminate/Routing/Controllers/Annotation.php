<?php namespace Illuminate\Routing\Controllers;

/**
 * @Annotation
 */
class Annotation {

	/**
	 * Create a new annotation instance.
	 *
	 * @param  array  $values
	 * @return void
	 */
	public function __construct(array $values)
	{
		foreach ($this->prepareValues($values) as $key => $value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Prepare the values for setting.
	 *
	 * @param  array  $values
	 * @return void
	 */
	protected function prepareValues($values)
	{
		// If the "get" method is present in an "on" constraint for the annotation we
		// will add the "head" method as well, since the "head" method is supposed
		// to function basically identically to the get methods on the back-end.
		if (isset($values['on']) and in_array('get', $values['on']))
		{
			$values['on'][] = 'head';
		}

		return $values;
	}

	/**
	 * Determine if the annotation applies to a request and method.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	public function applicable(Request $request, $method)
	{
		foreach (array('Request', 'OnlyMethod', 'ExceptMethod') as $excluder)
		{
			// We'll simply check the excluder method and see if the annotation does
			// not apply based on that rule. If it does not, we will go ahead and
			// return false since we know an annotation is not even applicable.
			$method = "excludedBy{$excluder}";

			if ($this->$method($request, $method)) return false;
		}

		return true;
	}

	/**
	 * Determine if the filter applies based on the "on" rule.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	protected function excludedByRequest($request, $method)
	{
		$http = strtolower($request->getMethod());

		return isset($this->on) and ! in_array($http, (array) $this->on);
	}

	/**
	 * Determine if the filter applies based on the "only" rule.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	protected function excludedByOnlyMethod($request, $method)
	{
		return isset($this->only) and ! in_array($method, (array) $this->only);
	}

	/**
	 * Determine if the filter applies based on the "except" rule.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return bool
	 */
	protected function excludedByExceptMethod($request, $method)
	{
		return isset($this->except) and in_array($method, (array) $this->except);
	}

}