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
		$this->assertEquals('bar', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());
	}


	public function testOptionalParameters()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor', 'GET');
		$this->assertEquals('taylor', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; })->defaults('age', null);
		$request = Request::create('/foo/taylor', 'GET');
		$this->assertEquals('taylor', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());
	}


	public function testGlobalBeforeFiltersHaltRequestCycle()
	{
		$router = new Router;
		$router->before(function() { return 'foo'; });
		$this->assertEquals('foo', $router->dispatch(Request::create('/bar', 'GET'))->getContent());
	}


	public function testAfterAndCloseFiltersAreCalled()
	{
		$_SERVER['__routing.test'] = '';
		$router = new Router;
		$router->get('/foo', function() { return 'foo'; });
		$router->before(function() { return null; });
		$router->after(function() { $_SERVER['__routing.test'] = 'foo'; });
		$router->close(function() { $_SERVER['__routing.test'] .= 'bar'; });
		$request = Request::create('/foo', 'GET');
		
		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertEquals('foobar', $_SERVER['__routing.test']);
		unset($_SERVER['__routing.test']);
	}


	public function testFinishFiltersCanBeCalled()
	{
		$_SERVER['__finish.test'] = false;
		$router = new Router;
		$router->finish(function() { $_SERVER['__finish.test'] = true; });
		$router->callFinishFilter(Request::create('/foo', 'GET'), new Symfony\Component\HttpFoundation\Response);
		$this->assertTrue($_SERVER['__finish.test']);
		unset($_SERVER['__finish.test']);
	}


	public function testBeforeFiltersStopRequestCycle()
	{
		$router = new Router;
		$router->get('/foo', array('before' => 'filter', function() { return 'foo'; }));
		$router->addFilter('filter', function() { return 'filtered!'; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('filtered!', $router->dispatch($request)->getContent());
	}


	public function testPatternFiltersAreCalledBeforeRoute()
	{
		$router = new Router;
		$router->get('/foo', function() { return 'bar'; });
		$router->matchFilter('bar*', 'something');
		$router->matchFilter('f*', 'filter');
		$router->addFilter('filter', function() { return 'filtered!'; });
		$router->addFilter('something', function() { return 'something'; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('filtered!', $router->dispatch($request)->getContent());
	}


	public function testAfterMiddlewaresAreCalled()
	{
		$router = new Router;
		$_SERVER['__filter.after'] = false;
		$router->addFilter('filter', function() { return $_SERVER['__filter.after'] = true; });
		$router->get('/foo', array('after' => 'filter', function() { return 'foo'; }));
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertTrue($_SERVER['__filter.after']);
		unset($_SERVER['__filter.after']);
	}


	public function testWhereMethodForcesRegularExpressionMatch()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; })->where('age', '[0-9]+');
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());
	}


	public function testBeforeFiltersCanBeSetOnRoute()
	{
		$route = new Route('/foo');
		$route->before('foo', 'bar');
		$this->assertEquals(array('foo', 'bar'), $route->getBeforeFilters());
	}


	public function testAfterFiltersCanBeSetOnRoute()
	{
		$route = new Route('/foo');
		$route->after('foo', 'bar');
		$this->assertEquals(array('foo', 'bar'), $route->getAfterFilters());
	}

}