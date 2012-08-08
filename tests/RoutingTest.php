<?php

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

class RoutingTest extends PHPUnit_Framework_TestCase {

	public function testBasic()
	{
		$router = new Router;
		$router->get('/foo', function() { return 'bar'; });
		$request = Request::create('/foo', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('bar', $route->run());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor25', $route->run());
	}


	public function testOptionalParameters()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor', $route->run());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; })->defaults('age', null);
		$request = Request::create('/foo/taylor', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor', $route->run());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('', $route->run());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor25', $route->run());
	}


	public function testWhereMethodForcesRegularExpressionMatch()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; })->where('age', '[0-9]+');
		$request = Request::create('/foo/taylor/25', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor25', $route->run());
	}


	public function testBeforeFiltersCanBeSetOnRoute()
	{
		$route = new Route('/foo');
		$route->before('foo', 'bar');
		$this->assertEquals(array('foo', 'bar'), $route->getBeforeMiddlewares());
	}


	public function testAfterFiltersCanBeSetOnRoute()
	{
		$route = new Route('/foo');
		$route->after('foo', 'bar');
		$this->assertEquals(array('foo', 'bar'), $route->getAfterMiddlewares());
	}

}