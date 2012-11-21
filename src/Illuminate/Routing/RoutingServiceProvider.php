<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$app['router'] = $app->share(function($app)
		{
			return new Router($app);
		});

		$this->registerUrlGenerator($app);

		$this->registerRedirector($app);
	}

	/**
	 * Register the URL generator service.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerUrlGenerator($app)
	{
		$app['url'] = $app->share(function($app)
		{
			// The URL generator needs the route collection that exists on the router.
			// Keep in mind this is an object, so we're passing by references here
			// and all the registered routes will be available to the generator.
			$routes = $app['router']->getRoutes();

			return new UrlGenerator($routes, $app['request']);
		});
	}

	/**
	 * Register the Redirector service.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerRedirector($app)
	{
		$app['redirect'] = $app->share(function($app)
		{
			$redirector = new Redirector($app['url']);

			// If the session is set on the application instance, we'll inject it into
			// the redirector instance. This allows the redirect responses to allow
			// for the quite convenient "with" methods that flash to the session.
			if (isset($app['session']))
			{
				$redirector->setSession($app['session']);
			}

			return $redirector;
		});
	}

}