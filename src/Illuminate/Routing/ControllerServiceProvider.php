<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Controllers\FilterParser;
use Illuminate\Routing\Console\MakeControllerCommand;
use Illuminate\Routing\Generators\ControllerGenerator;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

class ControllerServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerReader();

		// Controller may use annotations to specify filters, which uses the Doctrine
		// annotations component to parse those annotations out then apply them to
		// the route being executed, so we need to register the parser instance.
		$this->registerParser();

		$this->requireAnnotations();

		$this->registerGenerator();
	}

	/**
	 * Register the filter parser instance.
	 *
	 * @return void
	 */
	protected function registerParser()
	{
		$this->app['filter.parser'] = $this->app->share(function($app)
		{
			$path = $app['path'].'/storage/meta';

			return new FilterParser($app['annotation.reader'], $app['files'], $path);
		});
	}

	/**
	 * Register the annotation reader.
	 *
	 * @return void
	 */
	protected function registerReader()
	{
		$this->app['annotation.reader'] = $this->app->share(function()
		{
			$reader = new SimpleAnnotationReader;

			$reader->addNamespace('Illuminate\Routing\Controllers');

			return $reader;
		});
	}

	/**
	 * Manually require the controller annotation definitions.
	 *
	 * @return void
	 */
	protected function requireAnnotations()
	{
		require_once __DIR__.'/Controllers/Before.php';

		require_once __DIR__.'/Controllers/After.php';
	}

	/**
	 * Register the controller generator command.
	 *
	 * @return void
	 */
	protected function registerGenerator()
	{
		$this->app['command.controller.make'] = $this->app->share(function($app)
		{
			// The controller generator is responsible for building resourceful controllers
			// quickly and easily for the developers via the Artisan CLI. We'll go ahead
			// and register this command instances in this container for registration.
			$path = $app['path'].'/controllers';

			$generator = new ControllerGenerator($app['files']);

			return new MakeControllerCommand($generator, $path);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'filter.parser', 'annotation.reader', 'command.controller.make'
		);
	}

}