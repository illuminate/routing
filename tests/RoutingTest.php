<?php

use Mockery as m;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class RoutingTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicMatching()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo');
		$route = new Route('GET', '/foo', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo', $route->getPattern());
	}


	public function testHeadMethodIsRoutedLikeGet()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('HEAD');
		$request->shouldReceive('getPathInfo')->andReturn('/foo');
		$route = new Route('GET', '/foo', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo', $route->getPattern());
	}


	public function testRouteCanMatchMultipleMethods()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo');
		$route = new Route(array('GET', 'POST'), '/foo', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo', $route->getPattern());

		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('POST');
		$request->shouldReceive('getPathInfo')->andReturn('/foo');
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo', $route->getPattern());
	}


	public function testBasicWildcardsAreMatched()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/1');
		$route = new Route('GET', '/foo/(:num)', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo/(:num)', $route->getPattern());

		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/1');
		$route = new Route('GET', '/(:num)', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/(:num)', $route->getPattern());
	}


	public function testBasicAnyWildcardIsMatched()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/bar');
		$route = new Route('GET', '/foo/(:any)', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo/(:any)', $route->getPattern());

		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/1');
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo/(:any)', $route->getPattern());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testBasicWildcardsAreEnforced()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/bar');
		$route = new Route('GET', '/foo/(:num)', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
	}


	public function testOptionalWildcardsAreMatched()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo');
		$route = new Route('GET', '/foo/(:any?)', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo/(:any?)', $route->getPattern());

		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/bar');
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo/(:any?)', $route->getPattern());

		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/bar');
		$route = new Route('GET', '/foo/(:any?)/(:num?)', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo/(:any?)/(:num?)', $route->getPattern());

		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/bar/1');
		$route = $router->match($request);
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo/(:any?)/(:num?)', $route->getPattern());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testMatchingRouteNotFoundOnWrongMethod()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('POST');
		$request->shouldReceive('getPathInfo')->andReturn('/foo');
		$route = new Route('GET', '/foo', function() {});
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
	}


	public function testRouteIsCalledWithParameters()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo/1/bar');
		$route = new Route('GET', '/foo/(:num)/(:any)', function($var1, $var2) { return $var1.$var2; });
		$router = new Router;
		$router->addRoute($route);
		$route = $router->match($request);
		$response = $route->run($request);
		$this->assertEquals('1bar', $response);
	}


	public function testRoutesCanBeFoundByName()
	{
		$request = $this->mockRequest();
		$request->shouldReceive('getMethod')->andReturn('GET');
		$request->shouldReceive('getPathInfo')->andReturn('/foo');
		$route = new Route('GET', '/foo', function() {});
		$route->name('home');
		$router = new Router;
		$router->addRoute($route);
		$route = $router->find('home');
		$this->assertInstanceOf('Illuminate\Routing\Route', $route);
		$this->assertEquals('/foo', $route->getPattern());
		$this->assertEquals('home', $route->getName());
	}


	public function testAlsoMethodAddsHttpMethods()
	{
		$route = new Route('GET', '/foo', function() {});
		$route->also('POST', 'PUT');
		$this->assertEquals(array('GET', 'POST', 'PUT'), $route->getMethods());
	}


	protected function mockRequest()
	{
		return m::mock('Symfony\Component\HttpFoundation\Request');
	}

}