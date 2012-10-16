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


	public function testBeforeFiltersAreCalledAndHaltRequestLifecycle()
	{
		$controller = new BasicControllerStub;
		$container = new Illuminate\Container;
		$container['filter.parser'] = $container->share(function() { return m::mock('StdClass'); });
		$container['filter.parser']->shouldReceive('parse')->twice()->andReturn(array('foo-filter'), array());
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('getRequest')->andReturn($request = m::mock('Symfony\Component\HttpFoundation\Request'));
		$router->shouldReceive('getCurrentRoute')->andReturn($route = m::mock('Illuminate\Routing\Route'));
		$route->shouldReceive('callFilter')->once()->with('foo-filter', $request)->andReturn('filtered!');
		$router->shouldReceive('prepare')->once()->andReturnUsing(function($response, $request) { return new Response($response); });

		$response = $controller->callAction($container, $router, 'basicAction', array('foo'));
		$this->assertEquals('filtered!', $response->getContent());
	}

}

class BasicControllerStub extends Illuminate\Routing\Controllers\Controller {
	public function basicAction($var)
	{
		return $var;
	}
}