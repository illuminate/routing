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
		$this->assertEquals('bar', $route->run($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor25', $route->run($request)->getContent());
	}


	public function testOptionalParameters()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor', $route->run($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; })->defaults('age', null);
		$request = Request::create('/foo/taylor', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor', $route->run($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('', $route->run($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor25', $route->run($request)->getContent());
	}


	public function testBeforeFiltersStopRequestCycle()
	{
		$router = new Router;
		$router->get('/foo', array('before' => 'filter', function() { return 'foo'; }));
		$router->addMiddleware('filter', function() { return 'filtered!'; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('filtered!', $router->dispatch($request)->run($request)->getContent());
	}


	public function testPatternFiltersAreCalledBeforeRoute()
	{
		$router = new Router;
		$router->get('/foo', function() { return 'bar'; });
		$router->matchMiddleware('bar*', 'something');
		$router->matchMiddleware('f*', 'filter');
		$router->addMiddleware('filter', function() { return 'filtered!'; });
		$router->addMiddleware('something', function() { return 'something'; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('filtered!', $router->dispatch($request)->run($request)->getContent());
	}


	public function testAfterMiddlewaresAreCalled()
	{
		$router = new Router;
		$_SERVER['__filter.after'] = false;
		$router->addMiddleware('filter', function() { return $_SERVER['__filter.after'] = true; });
		$router->get('/foo', array('after' => 'filter', function() { return 'foo'; }));
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('foo', $router->dispatch($request)->run($request)->getContent());
		$this->assertTrue($_SERVER['__filter.after']);
		unset($_SERVER['__filter.after']);
	}


	public function testWhereMethodForcesRegularExpressionMatch()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; })->where('age', '[0-9]+');
		$request = Request::create('/foo/taylor/25', 'GET');
		$route = $router->dispatch($request);
		$this->assertEquals('taylor25', $route->run($request)->getContent());
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