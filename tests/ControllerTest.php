<?php

use Mockery as m;

class ControllerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testExecuteActionCallsAction()
	{
		/*
		$controller = new BasicControllerStub;
		$router = m::mock('Illuminate\Routing\Router');
		$router->shouldReceive('getRequest')->andReturn($request = m::mock('Symfony\Component\HttpFoundation\Request'));
		$router->shouldReceive('getCurrentRoute')->andReturn($route = m::mock('Illuminate\Routing\Route'));
		$router->shouldReceive('prepare')->once()->with('foo', $request)->andReturn('foo');
		$this->assertEquals('foo', $controller->callAction(new Illuminate\Container, $router, 'basicAction', array('foo')));
		*/
	}

}

class BasicControllerStub extends Illuminate\Routing\Controller {
	public function basicAction($var)
	{
		return $var;
	}
}