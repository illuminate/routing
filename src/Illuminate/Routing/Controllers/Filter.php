<?php namespace Illuminate\Routing\Controllers;

/**
 * @Annotation
 */
class Filter extends Annotation {

	/**
	 * The name of the filter to be applied.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The HTTP methods the filter applies to.
	 *
	 * @var array
	 */
	public $on = array();

	/**
	 * The controller methods the filter applies to.
	 *
	 * @var array
	 */
	public $only = array();

	/**
	 * The controller methods the filter doesn't apply to.
	 *
	 * @var array
	 */
	public $except = array();

}