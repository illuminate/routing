<?php

use Mockery as m;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

class RoutingTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testBasic()
	{
		$router = new Router;
		$router->get('/', function() { return 'root'; });
		$router->get('/foo', function() { return 'bar'; });
		$router->get('/foo//', function() { return 'foo'; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('bar', $router->dispatch($request)->getContent());

		$request = Request::create('/foo//', 'GET');
		$this->assertEquals('foo', $router->dispatch($request)->getContent());

		$request = Request::create('http://foo.com', 'GET');
		$this->assertEquals('root', $router->dispatch($request)->getContent());

		$request = Request::create('http://foo.com///', 'GET');
		$this->assertEquals('root', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testBasicWithTrailingSlashNotRoot()
	{
		$router = new Router;
		$router->get('/foo', function() { return 'bar'; });

		$request = Request::create('/foo///', 'GET');
		$this->assertEquals('bar', $router->dispatch($request)->getContent());
	}


	public function testCurrentRequestAndRouteIsSetOnRouter()
	{
		$router = new Router;
		$route = $router->get('/foo', function() { return 'bar'; });
		$request = Request::create('/foo', 'GET');

		$this->assertEquals('bar', $router->dispatch($request)->getContent());
		$this->assertEquals($request, $router->getRequest());
		$this->assertEquals($route, $router->getCurrentRoute());
	}


	public function testVariablesCanBeRetrievedFromCurrentRouteInstance()
	{
		$router = new Router;
		$route = $router->get('/foo/{name}', function() { return 'bar'; });
		$request = Request::create('/foo/taylor', 'GET');

		$this->assertEquals('bar', $router->dispatch($request)->getContent());
		$this->assertEquals('taylor', $router->getCurrentRoute()->getVariable('name'));
	}


	public function testResourceRouting()
	{
		$router = new Router;
		$router->resource('foo', 'FooController');
		$routes = $router->getRoutes();

		$this->assertEquals(8, count($routes));

		$router = new Router;
		$router->resource('foo', 'FooController', array('only' => array('show', 'destroy')));
		$routes = $router->getRoutes();

		$this->assertEquals(2, count($routes));

		$router = new Router;
		$router->resource('foo', 'FooController', array('except' => array('show', 'destroy')));
		$routes = $router->getRoutes();

		$this->assertEquals(6, count($routes));
	}


	public function testControllersAreCalledFromControllerRoutes()
	{
		$router = new Router;
		$container = m::mock('Illuminate\Container');
		$controller = m::mock('stdClass');
		$controller->shouldReceive('callAction')->once()->with($container, $router, 'index', array('taylor'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('home')->andReturn($controller);
		$router->setContainer($container);
		$request = Request::create('/foo/taylor', 'GET');
		$router->get('/foo/{name}', 'home@index');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
	}


	public function testControllerMethodBackReferencesCanBeUsed()
	{
		$router = new Router;
		$container = m::mock('Illuminate\Container');
		$controller = m::mock('stdClass');
		$controller->shouldReceive('callAction')->once()->with($container, $router, 'getBar', array('1', 'taylor'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('home')->andReturn($controller);
		$router->setContainer($container);
		$request = Request::create('/foo/bar/1/taylor', 'GET');
		$router->get('/foo/{name}/{id}/{person}', 'home@{name}');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
	}


	public function testControllerMethodBackReferencesUseGetMethodOnHeadRequest()
	{
		$router = new Router;
		$container = m::mock('Illuminate\Container');
		$controller = m::mock('stdClass');
		$controller->shouldReceive('callAction')->once()->with($container, $router, 'getBar', array('1', 'taylor'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('home')->andReturn($controller);
		$router->setContainer($container);
		$request = Request::create('/foo/bar/1/taylor', 'HEAD');
		$router->get('/foo/{name}/{id}/{person}', 'home@{name}');

		// HEAD requests won't return content
		$this->assertEquals('', $router->dispatch($request)->getOriginalContent());
	}


	public function testControllerMethodBackReferencesCanPointToIndex()
	{
		$router = new Router;
		$container = m::mock('Illuminate\Container');
		$controller = m::mock('stdClass');
		$controller->shouldReceive('callAction')->once()->with($container, $router, 'postIndex', array())->andReturn('foo');
		$container->shouldReceive('make')->once()->with('home')->andReturn($controller);
		$router->setContainer($container);
		$request = Request::create('/foo', 'POST');
		$router->post('/foo/{method?}', 'home@{method}');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
	}


	public function testControllersAreCalledFromControllerRoutesWithUsesStatement()
	{
		$router = new Router;
		$container = m::mock('Illuminate\Container');
		$controller = m::mock('stdClass');
		$controller->shouldReceive('callAction')->once()->with($container, $router, 'index', array('taylor'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('home')->andReturn($controller);
		$router->setContainer($container);
		$request = Request::create('/foo/taylor', 'GET');
		$router->get('/foo/{name}', array('uses' => 'home@index'));

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testExceptionThrownWhenControllerMethodsDontExist()
	{
		$controller = new Illuminate\Routing\Controllers\Controller;
		$controller->doSomething();
	}


	public function testOptionalParameters()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age?}', function($name, $age = null) { return $name.$age; });
		$request = Request::create('/foo/taylor', 'GET');
		$this->assertEquals('taylor', $router->dispatch($request)->getContent());
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age?}', function($name, $age = null) { return $name.$age; });
		$request = Request::create('/foo/taylor', 'GET');
		$this->assertEquals('taylor', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name = null, $age = null) { return $name.$age; });
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

		$router = new Router($container = m::mock('Illuminate\Container'));
		$filter = m::mock('stdClass');
		$filter->shouldReceive('filter')->once()->with(m::type('Symfony\Component\HttpFoundation\Request'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('FooFilter')->andReturn($filter);
		$router->before('FooFilter');
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
		$router->callFinishFilter(Request::create('/foo', 'GET'), new Illuminate\Http\Response);
		$this->assertTrue($_SERVER['__finish.test']);
		unset($_SERVER['__finish.test']);
	}


	public function testBeforeFiltersStopRequestCycle()
	{
		$router = new Router;
		$router->get('/foo', array('before' => 'filter|filter-2', function() { return 'foo'; }));
		$router->addFilter('filter', function() { return 'filtered!'; });
		$router->addFilter('filter-2', function() { return null; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('filtered!', $router->dispatch($request)->getContent());
	}


	public function testBeforeFiltersArePassedRouteAndRequest()
	{
		unset($_SERVER['__before.args']);
		$router = new Router;
		$route = $router->get('/foo', array('before' => 'filter', function() { return 'foo'; }));
		$router->addFilter('filter', function() { $_SERVER['__before.args'] = func_get_args(); });
		$request = Request::create('/foo', 'GET');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertEquals($route, $_SERVER['__before.args'][0]);
		$this->assertEquals($request, $_SERVER['__before.args'][1]);
		unset($_SERVER['__before.args']);
	}


	public function testBeforeFiltersArePassedRouteAndRequestAndCustomParameters()
	{
		unset($_SERVER['__before.args']);
		$router = new Router;
		$route = $router->get('/foo', array('before' => 'filter:dayle,rees', function() { return 'foo'; }));
		$router->addFilter('filter', function() { $_SERVER['__before.args'] = func_get_args(); });
		$request = Request::create('/foo', 'GET');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertEquals($route, $_SERVER['__before.args'][0]);
		$this->assertEquals($request, $_SERVER['__before.args'][1]);
		$this->assertEquals('dayle', $_SERVER['__before.args'][2]);
		$this->assertEquals('rees', $_SERVER['__before.args'][3]);
		unset($_SERVER['__before.args']);
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


	public function testAfterMiddlewaresAreCalledWithProperArguments()
	{
		$router = new Router;
		$_SERVER['__filter.after'] = false;
		$router->addFilter('filter', function() { return $_SERVER['__after.args'] = func_get_args(); });
		$route = $router->get('/foo', array('after' => 'filter:dayle,rees', function() { return 'foo'; }));
		$request = Request::create('/foo', 'GET');

		$response = $router->dispatch($request);
		$this->assertEquals('foo', $response->getContent());
		$this->assertEquals($route, $_SERVER['__after.args'][0]);
		$this->assertEquals($request, $_SERVER['__after.args'][1]);
		$this->assertEquals($response, $_SERVER['__after.args'][2]);
		$this->assertEquals('dayle', $_SERVER['__after.args'][3]);
		$this->assertEquals('rees', $_SERVER['__after.args'][4]);
		unset($_SERVER['__after.args']);
	}


	public function testFiltersCanBeDisabled()
	{
		$router = new Router;
		$router->disableFilters();
		$router->get('foo', array('before' => 'route-before', function()
		{
			return 'hello world';
		}));
		$router->before(function() { $_SERVER['__filter.test'] = true; });
		$router->addFilter('route-before', function() { $_SERVER['__filter.test'] = true; });
		$router->matchFilter('foo', 'route-before');
		$router->after(function() { $_SERVER['__filter.test'] = true; });

		$request = Request::create('/foo', 'GET');
		$this->assertEquals('hello world', $router->dispatch($request)->getContent());
		$this->assertFalse(isset($_SERVER['__filter.test']));
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


	public function testGroupCanShareAttributesAcrossRoutes()
	{
		$router = new Router;
		$router->group(array('before' => 'foo'), function() use ($router)
		{
			$router->get('foo', function() {});
			$router->get('bar', array('before' => 'bar', function() {}));
		});
		$routes = array_values($router->getRoutes()->getIterator()->getArrayCopy());

		$this->assertEquals(array('foo'), $routes[0]->getOption('_before'));
		$this->assertEquals(array('bar'), $routes[1]->getOption('_before'));
	}


	public function testStringFilterAreResolvedOutOfTheContainer()
	{
		$router = new Router($container = m::mock('Illuminate\Container'));
		$router->addFilter('foo', 'FooFilter');
		$container->shouldReceive('make')->once()->with('FooFilter')->andReturn('bar');

		$this->assertEquals(array('bar', 'filter'), $router->getFilter('foo'));
	}


	public function testCurrentRouteNameCanBeChecked()
	{
		$router = new Router(new Illuminate\Container);
		$route = $router->get('foo', array('as' => 'foo.route', function() {}));
		$route2 = $router->get('bar', array('as' => 'bar.route', function() {}));
		$router->setCurrentRoute($route);

		$this->assertTrue($router->currentRouteNamed('foo.route'));
		$this->assertFalse($router->currentRouteNamed('bar.route'));
	}


	public function testCurrentRouteActionCanBeChecked()
	{
		$router = new Router(new Illuminate\Container);
		$route = $router->get('foo', array('uses' => 'foo.route@action'));
		$route2 = $router->get('bar', array('uses' => 'bar.route@action'));
		$router->setCurrentRoute($route);

		$this->assertTrue($router->currentRouteUses('foo.route@action'));
		$this->assertFalse($router->currentRouteUses('bar.route@action'));
	}

}