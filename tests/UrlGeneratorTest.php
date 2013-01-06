<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;

class UrlGeneratorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicUrlGeneration()
	{
		$gen = $this->getGenerator();
		$gen->setRequest(Request::create('http://foobar.com/foo/bar', 'GET'));

		$this->assertEquals('http://foobar.com/something', $gen->to('something'));
		$this->assertEquals('https://foobar.com/something', $gen->secure('something'));
		$this->assertEquals('http://foobar.com/something/dayle/rees', $gen->to('something', array('dayle', 'rees')));
	}


	public function testUrlGenerationUsesCurrentProtocol()
	{
		$gen = $this->getGenerator();
		$gen->setRequest(Request::create('https://foobar.com/foo/bar', 'GET'));

		$this->assertEquals('https://foobar.com/something', $gen->to('something'));
		$this->assertEquals('http://foobar.com/something', $gen->to('something', array(), false));
	}


	public function testAssetUrlGeneration()
	{
		$gen = $this->getGenerator();
		$gen->setRequest(Request::create('http://foobar.com/index.php/foo/bar', 'GET'));

		$this->assertEquals('http://foobar.com/something', $gen->asset('something'));
		$this->assertEquals('https://foobar.com/something', $gen->secureAsset('something'));
	}


	public function testRouteUrlGeneration()
	{
		$gen = $this->getGenerator();
		$symfonyGen = m::mock('Symfony\Component\Routing\Generator\UrlGenerator');
		$symfonyGen->shouldReceive('generate')->once()->with('foo.bar', array('name' => 'taylor'), true);
		$gen->setRequest(Request::create('http://foobar.com', 'GET'));
		$gen->setGenerator($symfonyGen);

		$gen->route('foo.bar', array('name' => 'taylor'));
	}


	public function testRoutesToControllerAreGenerated()
	{
		$gen = $this->getGenerator();
		$gen->setRequest(Request::create('http://foobar.com', 'GET'));
		$this->assertEquals('http://foobar.com/boom/baz/taylor', $gen->action('FooController@fooAction', array('name' => 'taylor')));
		$this->assertEquals('http://foobar.com/boom/baz/taylor', $gen->action('FooController@fooAction', array('name' => 'taylor')));
	}


	public function testWellFormedUrlIsReturnedUnchanged()
	{
		$gen = $this->getGenerator();

		$this->assertEquals('http://google.com', $gen->to('http://google.com'));
	}


	protected function getGenerator()
	{
		$router = new Router;

		$router->get('foo/bar/{name}', array('as' => 'foo.bar', function() {}));
		$router->get('/boom/baz/{name}', array('uses' => 'FooController@fooAction'));

		return new UrlGenerator($router->getRoutes(), Request::create('/'), 'assets.com');
	}

}
