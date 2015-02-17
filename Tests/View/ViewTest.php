<?php
/**
 * @package        FOF
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace FOF30\Tests\View;

use FOF30\Input\Input;
use FOF30\Tests\Helpers\ClosureHelper;
use FOF30\Tests\Helpers\FOFTestCase;
use FOF30\Tests\Helpers\ReflectionHelper;
use FOF30\Tests\Helpers\TestContainer;
use FOF30\Tests\Stubs\Model\ModelStub;
use FOF30\Tests\Stubs\View\ViewStub;

require_once 'ViewDataprovider.php';

/**
 * @covers      FOF30\View\View::<protected>
 * @covers      FOF30\View\View::<private>
 * @package     FOF30\Tests\View
 */
class ViewTest extends FOFTestCase
{
    protected function setUp()
    {
        parent::setUp();

        // Let's set a fake template to the platform
        $platform = static::$container->platform;
        $platform::$template = 'fake_test_template';

        // This is required by View constructor, since it will create an instance of JUri
        $_SERVER['HTTP_HOST'] = 'www.example.com';
    }

    protected function tearDown()
    {
        parent::tearDown();

        if(isset($_SERVER['HTTP_HOST']))
        {
            unset($_SERVER['HTTP_HOST']);
        }
    }

    /**
     * @group           View
     * @covers          FOF30\View\View::__get
     * @dataProvider    ViewDataprovider::getTest__get
     */
    public function test__get($test, $check)
    {
        $msg = 'View::__get %s - Case: '.$check['case'];

        $input = new Input();

        $container = new TestContainer(array(
            'componentName' => 'com_fakeapp',
            'input'         => $input
        ));

        $view = new ViewStub($container);

        $property = $test['method'];

        // Suppress the error, so I can check the code executed AFTER the warning
        $result = @$view->$property;

        if($check['result'])
        {
            $this->assertSame($input, $result, sprintf($msg, 'Returned the wrong result'));
        }
        else
        {
            $this->assertNull($result, sprintf($msg, 'Returned the wrong result'));
        }
    }

    /**
     * @group           View
     * @group           ViewEscape
     * @covers          FOF30\View\View::escape
     */
    public function testEscape()
    {
        $view = new ViewStub(static::$container);
        $escape = $view->escape('<>àè?"\'');

        $this->assertEquals("&lt;&gt;àè?&quot;'", $escape, 'View::escape Failed to escape the string');
    }

    /**
     * @group           View
     * @group           ViewGet
     * @covers          FOF30\View\View::get
     * @dataProvider    ViewDataprovider::getTestGet
     */
    public function testGet($test, $check)
    {
        $msg  = 'View::get %s - Case: '.$check['case'];
        $view = new ViewStub(static::$container);

        if($test['mock']['viewProperty'])
        {
            $key = $test['mock']['viewProperty']['key'];
            $view->$key = $test['mock']['viewProperty']['value'];
        }

        ReflectionHelper::setValue($view, 'defaultModel', $test['mock']['defaultModel']);
        ReflectionHelper::setValue($view, 'modelInstances', $test['mock']['instances']);

        $result = $view->get($test['property'], $test['default'], $test['model']);

        if(is_object($result))
        {
            $this->assertInstanceOf('\\FOF30\\Model\\Model', $result, sprintf($msg, 'Should return an instance of the model'));
        }
        else
        {
            $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
        }
    }

    /**
     * @group           View
     * @group           ViewGetModel
     * @covers          FOF30\View\View::getModel
     * @dataProvider    ViewDataprovider::getTestGetModel
     */
    public function testGetModel($test, $check)
    {
        $msg  = 'View::getModel %s - Case: '.$check['case'];
        $view = new ViewStub(static::$container);

        ReflectionHelper::setValue($view, 'defaultModel', $test['mock']['defaultModel']);
        ReflectionHelper::setValue($view, 'name', $test['mock']['name']);
        ReflectionHelper::setValue($view, 'modelInstances', $test['mock']['instances']);

        if($check['exception'])
        {
            $this->setExpectedException('FOF30\View\Exception\ModelNotFound');
        }

        $result = $view->getModel($test['name']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Created the wrong model'));
    }

    /**
     * @group           View
     * @group           ViewSetDefaultModel
     * @covers          FOF30\View\View::setDefaultModel
     */
    public function testSetDefaultModel()
    {
        $model = new ModelStub(static::$container);

        $view  = $this->getMock('\\FOF30\\Tests\\Stubs\\View\\ViewStub', array('setDefaultModelName', 'setModel'), array(static::$container));
        $view->expects($this->once())->method('setDefaultModelName')->with($this->equalTo('nestedset'));
        // The first param is NULL since we mocked the previous function and the property defaultModel is not set
        $view->expects($this->once())->method('setModel')->with($this->equalTo(null), $this->equalTo($model));

        $view->setDefaultModel($model);
    }

    /**
     * @group           View
     * @group           ViewSetDefaultModelName
     * @covers          FOF30\View\View::setDefaultModelName
     */
    public function testDefaultModelName()
    {
        $view = new ViewStub(static::$container);
        $view->setDefaultModelName('foobar');

        $name = ReflectionHelper::getValue($view, 'defaultModel');

        $this->assertEquals('foobar', $name, 'View::setDefaultModelName Failed to set the internal name');
    }

    /**
     * @group           View
     * @group           ViewSetModel
     * @covers          FOF30\View\View::setModel
     */
    public function testSetModel()
    {
        $model      = new ModelStub(static::$container);
        $controller = new ViewStub(static::$container);
        $controller->setModel('foobar', $model);

        $models = ReflectionHelper::getValue($controller, 'modelInstances');

        $this->assertArrayHasKey('foobar', $models, 'View::setModel Failed to save the model');
        $this->assertSame($model, $models['foobar'], 'View::setModel Failed to store the same copy of the passed model');
    }

    /**
     * @group           View
     * @group           ViewDisplay
     * @covers          FOF30\View\View::display
     * @dataProvider    ViewDataprovider::getTestDisplay
     */
    public function testDisplay($test, $check)
    {
        $msg     = 'View::display %s - Case: '.$check['case'];
        $before  = array('counter' => 0);
        $after   = array('counter' => 0);
        $methods = array();

        if(isset($test['mock']['before']))
        {
            $methods['onBeforeDummy'] = function($self) use($test, &$before){
                $before['counter']++;

                return $test['mock']['before'];
            };
        }

        if(isset($test['mock']['after']))
        {
            $methods['onAfterDummy'] = function($self) use($test, &$after){
                $after['counter']++;

                return $test['mock']['after'];
            };
        }

        $view = $this->getMock('\\FOF30\\Tests\\Stubs\\View\\ViewStub', array('loadTemplate'), array(static::$container, array(), $methods));

        if($check['exception'] === false)
        {
            $this->expectOutputString($check['output']);
        }
        else
        {
            $this->setExpectedException($check['exception']);
        }

        // Do I really invoke the load method?
        if($check['load'])
        {
            $view->expects($this->once())->method('loadTemplate')->with($check['tpl'])->willReturn($test['mock']['output']);
        }
        else
        {
            $view->expects($this->never())->method('loadTemplate');
        }

        ReflectionHelper::setValue($view, 'doTask', $test['mock']['doTask']);

        $result = $view->display($test['tpl']);

        // I can run assertions only if the display method doesn't bail out with an exception
        if($check['exception'] === false)
        {
            $this->assertEquals($check['before'], $before, sprintf($msg, 'Failed to correctly process the onBefore method'));
            $this->assertEquals($check['after'], $before, sprintf($msg, 'Failed to correctly process the onAfter method'));

            // If we don't get an exception, we should return true
            $this->assertSame(true, $result, sprintf($msg, 'Should return true if an exception is not thrown'));
        }
    }

    /**
     * In this test I will only check for the result of the function
     *
     * @group           View
     * @group           ViewLoadTemplate
     * @covers          FOF30\View\View::loadTemplate
     * @dataProvider    ViewDataprovider::getTestLoadTemplate
     */
    public function testLoadTemplate($test, $check)
    {
        $msg = 'View::loadTemplate %s - Case: '.$check['case'];

        $view = $this->getMock('\\FOF30\\Tests\\Stubs\\View\\ViewStub', array('loadAnyTemplate', 'getLayout'), array(static::$container));
        $view->expects($this->any())->method('getLayout')->willReturn($test['mock']['layout']);
        $view->expects($this->any())->method('loadAnyTemplate')->willReturnCallback(
            function() use (&$test){
                $result = array_shift($test['mock']['any']);

                if($result == 'throw')
                {
                    throw new \Exception();
                }

                return $result;
            }
        );

        $viewFinder = new ClosureHelper(array(
            'getViewTemplateUris' => function() use ($test){
                return $test['mock']['viewFinder'];
            }
        ));

        ReflectionHelper::setValue($view, 'viewFinder', $viewFinder);

        if($check['exception'])
        {
            $this->setExpectedException('\Exception');
        }

        $result = $view->loadTemplate($test['tpl'], $test['strict']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
    }

    /**
     * @group           View
     * @group           ViewGetLayout
     * @covers          FOF30\View\View::getLayout
     */
    public function testGetLayout()
    {
        $view = new ViewStub(static::$container);

        ReflectionHelper::setValue($view, 'layout', 'foobar');

        $this->assertEquals('foobar', $view->getLayout(), 'View::getLayout Failed to return the layout');
    }

    /**
     * @group           View
     * @group           ViewSetLayout
     * @covers          FOF30\View\View::setLayout
     * @dataProvider    ViewDataprovider::getTestSetLayout
     */
    public function testSetLayout($test, $check)
    {
        $msg  = 'View::setLayout %s - Case: '.$check['case'];
        $view = new ViewStub(static::$container);

        ReflectionHelper::setValue($view, 'layout', $test['mock']['layout']);

        $result = $view->setLayout($test['layout']);

        $layout = ReflectionHelper::getValue($view, 'layout');
        $tmpl   = ReflectionHelper::getValue($view, 'layoutTemplate');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
        $this->assertEquals($check['layout'], $layout, sprintf($msg, 'Set the wrong layout'));
        $this->assertEquals($check['tmpl'], $tmpl, sprintf($msg, 'Set the wrong layout template'));
    }

    /**
     * @group           View
     * @group           ViewGetLayoutTemplate
     * @covers          FOF30\View\View::getLayoutTemplate
     */
    public function testGetLayoutTemplate()
    {
        $view = new ViewStub(static::$container);

        ReflectionHelper::setValue($view, 'layoutTemplate', 'foobar');

        $this->assertEquals('foobar', $view->getLayoutTemplate(), 'View::getLayoutTemplate Failed to return the layout template');
    }

    /**
     * @group           View
     * @group           ViewGetContainer
     * @covers          FOF30\View\View::getContainer
     */
    public function testGetContainer()
    {
        $view = new ViewStub(static::$container);

        $newContainer = $view->getContainer();

        $this->assertSame(static::$container, $newContainer, 'View::getContainer Failed to return the passed container');
    }
}
