<?php
/**
 * @author Henry Schmieder
 * @version 0.1 20/10/12 21:34
 *
 * @group event
 */
use Ripple\Dispatcher as Dispatcher,
    Ripple\Event as Event;


class Ripple_InstanceTest extends \PHPUnit_Framework_TestCase
{
    const LISTENER_A_RESULT = 'listenerA result';
    const LISTENER_B_RESULT = 'listenerB result';
    const LISTENER_C_RESULT = 'listenerC result';


    /** @var Dispatcher */
    protected $dispatcher;


    protected $_setByListeners;


    protected $_eventGivenToListenerA;


    protected $_eventsTrackedTrueReturnCount;

    /**
     * @var Event
     */
    protected $_catchedEvent;



    public function setUp()
    {
        $this->dispatcher = new Dispatcher();
        $this->_setByListeners = array();
        $this->_eventsTrackedTrueReturnCount = 0;
        $this->_catchedEvent = null;
    }


    public function tearDown()
    {
        if( !$this->dispatcher ) {
            return;
        }
        $this->dispatcher->removeAllListeners();
        $this->_eventGivenToListenerA = null;
    }



    public function  test_events_dispatcher_creation()
    {
        $this->assertInstanceOf( '\Ripple\Dispatcher', $this->dispatcher );
    }



    public function test_events_addPublicListenerCallback(  )
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertEquals( 1, count( $this->dispatcher->getAllListeners() ) );

    }



    public function test_events_addProtectedListenerCallback_shouldFail(  )
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerProtected' ) );
        $this->assertEquals( 0, count( $this->dispatcher->getAllListeners() ) );
    }


    public function test_events_dispatch_eventIsString()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->dispatcher->dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertNull( $this->_catchedEvent->getParam() );
        $this->assertNull( $this->_catchedEvent->getParams() );
    }


    public function test_events_dispatch_eventIsEvent()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->dispatcher->dispatch( new Event( 'test', $this, array(1,2), false ) );
        $this->assertEquals( $this, $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertInternalType( 'array', $this->_catchedEvent->getParam() );
        $this->assertEquals( array(1,2), $this->_catchedEvent->getParams() );
    }


    public function test_events_fireEvent_invokeCallback_expectsArray(  )
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertTrue( is_array( $result ) );
        $this->assertEquals( static::LISTENER_A_RESULT, $result[0] );
    }



    public function test_events_fireEvent_withoutListenerInvocation_expectsNull( )
    {
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertNull(  $result  );
    }


    public function test_events_listener_gets_event_object( )
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->dispatch( 'test', $this );
        $this->assertInstanceOf(  '\Ripple\Event', $this->_eventGivenToListenerA );
    }



    public function test_events_hasEventListener()
    {
        $hasListener = $this->dispatcher->hasListener( 'test' );
        $this->assertFalse( $hasListener );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $hasListener = $this->dispatcher->hasListener( 'test' );
        $this->assertTrue( $hasListener );
    }


    public function test_events_getListenersForType()
    {
         $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
         $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
         $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
         $listeners = $this->dispatcher->getListenersForEvent( 'test' );
         $this->assertEquals( 2, count( $listeners ) );
    }


    public function test_events_getAllListeners()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
        $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
        $listeners = $this->dispatcher->getAllListeners();
        $this->assertEquals( 3, count( $listeners ) );
    }

    public function test_events_getAllListenersStructured()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
        $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
        $listeners = $this->dispatcher->getAllListenersStructured();
        $this->assertEquals( 2, count( $listeners ) );
    }


    public function test_events_removeAllListeners()
    {
         $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
         $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
         $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
         $this->dispatcher->removeAllListeners();
         $listeners = $this->dispatcher->getAllListeners();
         $this->assertEquals( 0, count( $listeners ) );
    }



    public function test_events_multipleListeners_normalOrder_lastAttachedListener_firesFirst()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertEquals( 2, count( $result ) );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }



    public function test_events_multipleListeners_customPriority()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ), -100 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertEquals( 2, count( $result ) );
        $this->assertEquals( static::LISTENER_A_RESULT, $result[0] );
    }



    public function test_events_multipleListeners_customPriorityInverted()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ), 100 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 0 );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertEquals( 2, count( $result ) );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }


    public function test_events_stopPropagation()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerACancel' ), 0 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertEquals( 1, count( $result ) );
    }


    public function test_events_stopPropagation_with_cancelableFalse_shouldNotCancel()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerACancel' ), 0 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = $this->dispatcher->dispatch( new Event( 'test', $this, null, false ) );
        $this->assertEquals( 2, count( $result ) );
    }


    public function test_events_dispatchUntil()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerTrue' ), 1 ); // fired first , should stop here
        $this->dispatcher->addListener( 'test', array( $this, 'listenerFalse' ), 100 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerFalse' ), 200 );
        $event =  new Event( 'test', $this, null, false );
        $result = $this->dispatcher->dispatchUntil( $event );
        $this->assertEquals( 1, $this->_eventsTrackedTrueReturnCount );
    }




    public function test_events_getParam_noArg_fetches_params()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' )  );
        $this->dispatcher->dispatch( 'test', $this, new EventTestParam() );
        $this->assertInstanceOf( 'EventTestParam', $this->_catchedEvent->getParam() );
    }


    public function test_events_getParam_namedArg()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' )  );
        $this->dispatcher->dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $this->assertInstanceOf( 'EventTestParam', $this->_catchedEvent->getParam( 'param1' ) );
    }


    public function test_events_getParams()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' )  );
        $this->dispatcher->dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $params = $this->_catchedEvent->getParams();
        $this->assertInstanceOf( 'EventTestParam', $params['param1'] );
    }


    public function test_events_removeSingleListener()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertEquals( 1, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->removeListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertEquals( 0, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }


    public function test_events_removeAllListenersForEvent()
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertEquals( 2, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->removeListenersForEvent( 'test' );
        $this->assertEquals( 0, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }


    public function test_event_closureCallback()
    {
        $setByCb = false;
        $capturedE = null;
        $closure = function( $e ) use(&$setByCb, &$capturedE)
        {
            $setByCb = true;
            $capturedE = $e;
        };
        $this->dispatcher->addListener( 'test', $closure );
        $this->dispatcher->dispatch( 'test' );
        $this->assertTrue( $setByCb );
        $this->assertInstanceOf( '\Ripple\Event', $capturedE );
    }



    public function test_event_aggregate()
    {
        $target = new stdClass();
        $target->test = '123';
        $listener = new EventTestListener();
        $this->dispatcher->addListenerAggregate( 'test', $listener );
        $this->dispatcher->dispatch( 'test:beforeTest' );
        $this->dispatcher->dispatch( 'test:afterTest', $target, array("eins") );
        $this->dispatcher->dispatch( 'test:nonExisting' );
        $this->assertEquals( 2, count( $listener->registrar->capturedTypes ) );
        $this->assertContains( 'eins', $listener->registrar->params );
        $this->assertEquals( '123', $listener->registrar->target->test );
    }


    /**
     * @param  \Ripple\Event $e
     * @return string
     */
    public function listenerDefault( $e )
    {
        $this->_catchedEvent = $e;
        return true;
    }



    /**
     * @param  \Ripple\Event $e
     * @return string
     */
    public function listenerA( $e )
    {
        $this->_eventGivenToListenerA = $e;
        return static::LISTENER_A_RESULT;
    }


    /**
     * @param \Ripple\Event $e
     * @return string
     */
    public function listenerACancel( $e )
    {
        $e->stopPropagation();
        return static::LISTENER_A_RESULT;
    }


    public function listenerB( $e )
    {
        return static::LISTENER_B_RESULT;
    }

    public function listenerC( $e )
    {
        return static::LISTENER_C_RESULT;
    }


     public function listenerTrue( $e )
    {
        $this->_eventsTrackedTrueReturnCount++;
        return true;
    }

    public function listenerFalse( $e )
    {
        $this->_eventsTrackedTrueReturnCount++;
        return false;
    }



    protected function listenerProtected()
    {
        return 'listener Protected result';
    }

}

class EventTestParam {}

class EventTestListener
{
    /** @var stdClass */
    public $registrar;

    public function __construct()
    {
        $this->registrar = new stdClass();
        $this->registrar->capturedTypes = array();
    }

    /**
     * @param $e \Ripple\Event
     */
    public function beforeTest( $e )
    {
        $this->registrar->setByCb = true;
        $this->registrar->capturedTypes[ ] = $e->getType();
    }
    /**
     * @param $e \Ripple\Event
     */
    public function afterTest( $e )
    {
        $this->registrar->setByCb = true;
        $this->registrar->capturedTypes[ ] = $e->getType();
        $this->registrar->params = $e->getParams();
        $this->registrar->target = $e->getTarget();
    }

}

?>
