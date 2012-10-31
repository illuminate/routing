<?php namespace Illuminate\Routing\Controllers;

use ReflectionClass;
use Illuminate\Filesystem;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Request;

class FilterParser {

	/**
	 * The annotation reader implementation.
	 *
	 * @var Doctrine\Common\Annotations\Reader
	 */
	protected $reader;

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The path to the cached filter files.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new filter parser instance.
	 *
	 * @param  Doctrine\Common\Annotations\Reader  $reader
	 * @return void
	 */
	public function __construct(Reader $reader, Filesystem $files, $path)
	{
		$this->path = $path;
		$this->files = $files;
		$this->reader = $reader;
	}

	/**
	 * Parse the given filters from the controller.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  Illuminate\Routing\Controllers\Controller  $controller
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	public function parse(ReflectionClass $reflection, Controller $controller, Request $request, $method, $filter)
	{
		$code = $this->getCodeFilters($controller, $request, $method, $filter);

		$cached = $this->getCached($reflection, $request, $method, $filter);

		// If we weren't able to find any cached filters, we will load them by reading
		// the annotations and then cache a fresh copy of them. By utilizing cached
		// copies of the filters we can save time vs reading out the annotations.
		if (is_null($cached))
		{
			$filters = $this->reparse($reflection, $request, $method, $filter);

			return array_unique(array_merge($filters, $code));
		}
		else
		{
			return array_unique(array_merge($cached, $code));
		}
	}

	/**
	 * Get the filters that were specified in code.
	 *
	 * @param  Illuminate\Routing\Controllers\Controller  $controller
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	protected function getCodeFilters($controller, $request, $method, $filter)
	{
		$filters = $this->filterByClass($controller->getFilters(), $filter);

		return $this->getNames($this->filter($filters, $request, $method));
	}

	/**
	 * Attempt to get the cached filters.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	protected function getCached($reflection, $request, $method, $filter)
	{
		$path = $this->getCachePath($reflection, $request, $method, $filter);

		// If we have a cached copy of the filters we'll parse it and return a cached
		// list of the filters so we don't have to use an Annotation parser at all
		// to get the filter list, which will spare us a lot of processing time.
		if ($this->files->exists($path))
		{
			if ($this->cacheIsExpired($reflection, $path)) return;

			return unserialize($this->files->get($path));
		}
	}

	/**
	 * Determine if the filter cache is expired.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  string  $path
	 * @return bool
	 */
	protected function cacheIsExpired($reflection, $path)
	{
		$cacheTime = $this->files->lastModified($path);

		$controllerPath = $reflection->getFileName();

		return $this->files->lastModified($controllerPath) >= $cacheTime;
	}

	/**
	 * Cache the given array of filters.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @param  array   $filters
	 * @return array
	 */
	protected function cacheFilters($reflection, $request, $method, $filter, $filters)
	{
		$path = $this->getCachePath($reflection, $request, $method, $filter);

		$this->files->put($path, serialize($filters));

		return $filters;
	}

	/**
	 * Get the full cache path for a controller method.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return string
	 */
	public function getCachePath(ReflectionClass $reflection, Request $request, $method, $filter)
	{
		$name = $reflection->getName();

		$file = md5($name.$request->getMethod().$method.$filter);

		return $this->path.'/'.$file;
	}

	/**
	 * Reparse the annotation filters and cache them.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	protected function reparse($reflection, $request, $method, $filter)
	{
		$filters = $this->getFilters($reflection, $request, $method, $filter);

		$this->cacheFilters($reflection, $request, $method, $filter, $filters);

		return $filters;
	}

	/**
	 * Get and filter all of the applicable filters.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	protected function getFilters($reflection, $request, $method, $filter)
	{
		$annotations = $this->getAnnotations($reflection, $method, $filter);

		// Once we have all of the annotations, we need to filter them by the request
		// method and any other limitations they might have so we can be sure that
		// we are only running those filters that apply to this current request.
		$filters = $this->filter($annotations, $request, $method);

		return array_unique($this->getNames($filters));
	}

	/**
	 * Get the class and method annotations for a controller.
	 *
	 * @param  ReflectionClass  $reflection
	 * @param  string  $method
	 * @param  string  $filter
	 * @return array
	 */
	protected function getAnnotations($reflection, $method, $filter)
	{
		$classes = $this->getClassAnnotations($reflection);

		// Once we have the class level annotations, we also need to get those at the
		// method level. Then we will filter the annotations by a given class name
		// so that only the "requested" instances are returned from this method.
		$methods = $this->getMethodAnnotations($reflection, $method);

		$annotations = array_merge($classes, $methods);

		return $this->filterByClass($annotations, $filter);
	}

	/**
	 * Get the class level filter annotations.
	 *
	 * @param  ReflectionClass  $class
	 * @return array
	 */
	protected function getClassAnnotations($reflection)
	{
		return $this->reader->getClassAnnotations($reflection);
	}

	/**
	 * Get the class level filter annotations.
	 *
	 * @param  ReflectionClass  $class
	 * @param  string  $method
	 * @return array
	 */
	protected function getMethodAnnotations($reflection, $method)
	{
		$reflectionMethod = $reflection->getMethod($method);

		if ( ! is_null($reflectionMethod))
		{
			return $this->reader->getMethodAnnotations($reflectionMethod);
		}

		return array();
	}

	/**
	 * Filter the annotation instances by class name.
	 *
	 * @param  array   $filters
	 * @param  string  $filter
	 * @return array
	 */
	protected function filterByClass($filters, $filter)
	{
		return array_filter($filters, function($a) use ($filter)
		{
			return $a instanceof $filter;
		});
	}

	/**
	 * Filter the annotations by request and method.
	 *
	 * @param  array  $filters
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @param  string  $method
	 * @return array
	 */
	protected function filter($filters, $request, $method)
	{
		$filtered = array_filter($filters, function($a) use ($request, $method)
		{
			return $a->applicable($request, $method);
		});

		return array_values($filtered);
	}

	/**
	 * Get the filter names from an array of filter objects.
	 *
	 * @param  array  $filters
	 * @return array
	 */
	protected function getNames(array $filters)
	{
		return array_map(function($f) { return $f->run; }, $filters);
	}

	/**
	 * Get the annotation reader implementation.
	 *
	 * @return Doctrine\Common\Annotations\Reader
	 */
	public function getReader()
	{
		return $this->reader;
	}

	/**
	 * Get the filesystem instance.
	 *
	 * @return Illuminate\Filesystem
	 */
	public function getFilesystem()
	{
		return $this->files;
	}

}