<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;

class FilterParserTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCachedFiltersAreReturnedWhenAvailable()
	{
		$reader = $this->getParser();
		$reflection = m::mock('ReflectionClass');
		$reflection->shouldReceive('getName')->andReturn('controller-name');
		$reflection->shouldReceive('getFileName')->andReturn('controller-path');
		$path = $reader->getCachePath($reflection, $request = Request::create('/', 'GET'), 'foo', 'bar');
		$reader->getFilesystem()->shouldReceive('lastModified')->once()->andReturn(100);
		$reader->getFilesystem()->shouldReceive('lastModified')->once()->with('controller-path')->andReturn(0);
		$reader->getFilesystem()->shouldReceive('exists')->once()->with($path)->andReturn(true);
		$reader->getFilesystem()->shouldReceive('get')->once()->with($path)->andReturn(serialize(array('foo')));
		$filters = $reader->parse($reflection, $request, 'foo', 'bar');

		$this->assertEquals(array('foo'), $filters);
	}


	public function testFiltersAreRecompiledWhenControllerHasBeenModified()
	{
		$reader = $this->getMock('Illuminate\Routing\Controllers\FilterParser', array('getFilters', 'cacheFilters'), $this->getMockArguments());
		$reflection = m::mock('ReflectionClass');
		$reflection->shouldReceive('getName')->andReturn('controller-name');
		$reflection->shouldReceive('getFileName')->andReturn('controller-path');
		$path = $reader->getCachePath($reflection, $request = Request::create('/', 'GET'), 'foo', 'bar');
		$reader->getFilesystem()->shouldReceive('lastModified')->once()->andReturn(100);
		$reader->getFilesystem()->shouldReceive('lastModified')->once()->with('controller-path')->andReturn(200);
		$reader->expects($this->once())->method('getFilters')->will($this->returnValue(array('filters')));
		$reader->expects($this->once())->method('cacheFilters')->will($this->returnValue(array('filters')));
		$filters = $reader->parse($reflection, $request, 'foo', 'bar');

		$this->assertEquals(array('filters'), $filters);
	}


	public function testFiltersAreParsedCorrectlyByClass()
	{
		$reader = $this->getMock('Illuminate\Routing\Controllers\FilterParser', array('getCachedFilters', 'cacheFilters'), $this->getMockArguments());
		$reader->expects($this->once())->method('getCachedFilters')->will($this->returnValue(null));

		$controller = new FilterParserTestController;
		$reflection = new ReflectionClass($controller);
		$request = Request::create('/', 'GET');

		$mockClassFilter = m::mock('Illuminate\Routing\Controllers\Before');
		$mockClassFilter->run = 'class-before';
		$mockClassFilter->shouldReceive('applicable')->once()->with($request, 'fooAction')->andReturn(true);

		$mockMethodFilter = m::mock('Illuminate\Routing\Controllers\Before');
		$mockMethodFilter->run = 'method-before';
		$mockMethodFilter->shouldReceive('applicable')->once()->with($request, 'fooAction')->andReturn(true);

		$classFilters = array($mockClassFilter, new Illuminate\Routing\Controllers\After(array('run' => 'class-after')));
		$methodFilters = array($mockMethodFilter, new Illuminate\Routing\Controllers\After(array('run' => 'method-after')));		

		$reader->expects($this->once())->method('cacheFilters')
                                       ->with($this->equalTo($reflection), $this->equalTo($request), $this->equalTo('fooAction'), $this->equalTo('Illuminate\Routing\Controllers\Before'), $this->equalTo(array('class-before', 'method-before')))
                                       ->will($this->returnValue(array('class-before', 'method-before')));

		$reader->getReader()->shouldReceive('getClassAnnotations')->once()->with(m::type('ReflectionClass'))->andReturn($classFilters);
		$reader->getReader()->shouldReceive('getMethodAnnotations')->once()->with(m::type('ReflectionMethod'))->andReturn($methodFilters);
		$filters = $reader->parse($reflection, $request, 'fooAction', 'Illuminate\Routing\Controllers\Before');

		$this->assertEquals(2, count($filters));
		$this->assertEquals(array('class-before', 'method-before'), $filters);
	}


	public function testReturnedFiltersAreProperlyCached()
	{
		$parser = $this->getMock('Illuminate\Routing\Controllers\FilterParser', array('getCachedFilters', 'getFilters'), $this->getMockArguments());
		$parser->expects($this->once())->method('getCachedFilters')->will($this->returnValue(null));
		$parser->expects($this->once())->method('getFilters')->will($this->returnValue(array('foo')));
		$controller = m::mock('Illuminate\Routing\Controllers\Controller');
		$reflection = new ReflectionClass($controller);
		$request = Request::create('/', 'GET');
		$path = $parser->getCachePath($reflection, $request, 'fooAction', 'filter');
		$parser->getFilesystem()->shouldReceive('put')->once()->with($path, serialize(array('foo')));

		$this->assertEquals(array('foo'), $parser->parse($reflection, $request, 'fooAction', 'filter'));
	}


	protected function getParser()
	{
		return new Illuminate\Routing\Controllers\FilterParser(m::mock('Doctrine\Common\Annotations\Reader'), m::mock('Illuminate\Filesystem'), __DIR__);
	}


	protected function getMockArguments()
	{
		return array(m::mock('Doctrine\Common\Annotations\Reader'), m::mock('Illuminate\Filesystem'), __DIR__);
	}

}

class FilerParserTestFilterStub {
	public $name;
}

/**
 * @Illuminate\Routing\Controllers\Before(run="class-before")
 * @Illuminate\Routing\Controllers\After(run="class-after")
 */
class FilterParserTestController extends Illuminate\Routing\Controllers\Controller {
	/**
	 * @Illuminate\Routing\Controllers\Before(run="method-before")
	 * @Illuminate\Routing\Controllers\After(run="method-after")
	 */
	public function fooAction() {}
}