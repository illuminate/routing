<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Response;

class ControllerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicMethodExecution()
	{
		$controller = new BasicControllerStub;
		$container = new Illuminate\Container;
		$container['filter.parser'] = $container->share(function() { return m::mock('StdClass'); });
		$container['filter.parser']->shouldReceive('parse')->andReturn(array());
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('getRequest')->andReturn(m::mock('Symfony\Component\HttpFoundation\Request'));
		$router->shouldReceive('getCurrentRoute')->andReturn(m::mock('Illuminate\Routing\Route'));
		$router->shouldReceive('prepare')->once()->andReturnUsing(function($response, $request) { return new Response($response); });

		$response = $controller->callAction($container, $router, 'basicAction', array('foo'));
		$this->assertEquals('foo', $response->getContent());
	}

	public function testRestfulMethodExecution()
	{
		$controller = new BasicRestfulControllerStub;
		$container = new Illuminate\Container;
		$container['filter.parser'] = $container->share(function () { return m::mock('StdClass'); });
		$container['filter.parser']->shouldReceive('parse')->andReturn(array());
		$router = m::mock('Illuminate\Routing\Router');
		$request = m::mock('Symfony\Component\HttpFoundation\Request');
		$request->shouldReceive('getMethod')->andReturn('GET');
		$router->shouldReceive('getRequest')->andReturn($request);
		$router->shouldReceive('getCurrentRoute')->andReturn(m::mock('Illuminate\Routing\Route'));
		$router->shouldReceive('prepare')->once()->andReturnUsing(function($request, $response) { return new Response($response); });

		$response = $controller->callAction($container, $router, 'basicAction', array('foo'));
		$this->assertEquals('foo', $response->getContent());
	}


	public function testBeforeFiltersAreCalledAndHaltRequestLifecycle()
	{
		$controller = new BasicControllerStub;
		$container = new Illuminate\Container;
		$container['filter.parser'] = $container->share(function() { return m::mock('StdClass'); });
		$container['filter.parser']->shouldReceive('parse')->twice()->andReturn(array('foo-filter'), array());
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('getRequest')->andReturn($request = m::mock('Symfony\Component\HttpFoundation\Request'));
		$router->shouldReceive('getCurrentRoute')->andReturn($route = m::mock('Illuminate\Routing\Route'));
		$route->shouldReceive('callFilter')->once()->with('foo-filter', $request, array())->andReturn('filtered!');
		$router->shouldReceive('prepare')->once()->andReturnUsing(function($response, $request) { return new Response($response); });

		$response = $controller->callAction($container, $router, 'basicAction', array('foo'));
		$this->assertEquals('filtered!', $response->getContent());
	}


	public function testBeforeFiltersAreCalledAndHaltRequestLifecycleWhenUsingCallbackFilters()
	{
		$controller = new BasicControllerStub;
		$callback = function() { return 'filtered!'; };
		$controller->beforeFilter($callback);
		$container = new Illuminate\Container;
		$container['filter.parser'] = $container->share(function() { return m::mock('StdClass'); });
		$container['filter.parser']->shouldReceive('parse')->twice()->andReturn(array($filterName = spl_object_hash($callback)), array());
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('getRequest')->andReturn($request = m::mock('Symfony\Component\HttpFoundation\Request'));
		$router->shouldReceive('getCurrentRoute')->andReturn($route = m::mock('Illuminate\Routing\Route'));
		$router->shouldReceive('prepare')->once()->andReturnUsing(function($response, $request) { return new Response($response); });

		$response = $controller->callAction($container, $router, 'basicAction', array('foo'));
		$this->assertEquals('filtered!', $response->getContent());
	}


	public function testAfterFiltersAreExecuted()
	{
		unset($_SERVER['__controller.after']);
		$controller = new BasicControllerStub;
		$container = new Illuminate\Container;
		$container['filter.parser'] = $container->share(function() { return m::mock('StdClass'); });
		$container['filter.parser']->shouldReceive('parse')->twice()->andReturn(array('foo-filter'), array());
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('getRequest')->andReturn($request = m::mock('Symfony\Component\HttpFoundation\Request'));
		$router->shouldReceive('getCurrentRoute')->andReturn($route = m::mock('Illuminate\Routing\Route'));
		$route->shouldReceive('callFilter')->once()->with('foo-filter', $request, array())->andReturnUsing(function()
		{
			$_SERVER['__controller.after'] = true;
			return null;
		});
		$router->shouldReceive('prepare')->once()->andReturnUsing(function($response, $request) { return new Response($response); });

		$response = $controller->callAction($container, $router, 'basicAction', array('foo'));
		$this->assertEquals('foo', $response->getContent());
		$this->assertTrue($_SERVER['__controller.after']);
		unset($_SERVER['__controller.after']);
	}


	public function testAfterFiltersAreExecutedWhenUsingCallbackFilters()
	{
		unset($_SERVER['__controller.after']);
		$controller = new BasicControllerStub;
		$callback = function() { $_SERVER['__controller.after'] = true; };
		$controller->afterFilter($callback);
		$container = new Illuminate\Container;
		$container['filter.parser'] = $container->share(function() { return m::mock('StdClass'); });
		$container['filter.parser']->shouldReceive('parse')->twice()->andReturn(array($hash = spl_object_hash($callback)), array());
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('getRequest')->andReturn($request = m::mock('Symfony\Component\HttpFoundation\Request'));
		$router->shouldReceive('getCurrentRoute')->andReturn($route = m::mock('Illuminate\Routing\Route'));
		$router->shouldReceive('prepare')->once()->andReturnUsing(function($response, $request) { return new Response($response); });

		$response = $controller->callAction($container, $router, 'basicAction', array('foo'));
		$this->assertEquals('foo', $response->getContent());
		$this->assertTrue($_SERVER['__controller.after']);
		unset($_SERVER['__controller.after']);
	}

}

class BasicControllerStub extends Illuminate\Routing\Controllers\Controller {
	public function actionBasicAction($var)
	{
		return $var;
	}
}

class BasicRestfulControllerStub extends Illuminate\Routing\Controllers\Controller {
	protected $restful = true;

	public function getBasicAction($var)
	{
		return $var;
	}
}