<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Controllers\FilterParser;
use Illuminate\Routing\Console\MakeControllerCommand;
use Illuminate\Routing\Generators\ControllerGenerator;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

class ControllerServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$this->registerReader($app);

		// Controller may use annotations to specify filters, which uses the Doctrine
		// annotations component to parse those annotations out then apply them to
		// the route being executed, so we need to register the parser instance.
		$this->registerParser($app);

		$this->requireAnnotations();

		$this->registerGenerator($app);
	}

	/**
	 * Register the filter parser instance.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerParser($app)
	{
		$app['filter.parser'] = $app->share(function($app)
		{
			$path = $app['path'].'/storage/meta';

			return new FilterParser($app['annotation.reader'], $app['files'], $path);
		});
	}

	/**
	 * Register the annotation reader.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerReader($app)
	{
		$app['annotation.reader'] = $app->share(function()
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
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerGenerator($app)
	{
		$app['command.controller.make'] = $app->share(function($app)
		{
			// The controller generator is responsible for building resourceful controllers
			// quickly and easily for the developers via the Artisan CLI. We'll go ahead
			// and register this command instances in this container for registration.
			$path = $app['path'].'/controllers';

			$generator = new ControllerGenerator($app['files']);

			return new MakeControllerCommand($generator, $path);
		});
	}

}