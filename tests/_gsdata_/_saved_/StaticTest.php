<?php
/**
 * @author Henry Schmieder
 * @version 0.1 20/10/12 22:34
 *
 *  @group event
 */
use Ripple\Dispatcher as Dispatcher,
    Ripple\Event as Event,
    Ripple\DispatcherStatic as DispatcherStatic;

class Ripple_StaticTest extends \PHPUnit_Framework_TestCase
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
        $this->_setByListeners = array();
        $this->_eventsTrackedTrueReturnCount = 0;
        $this->_catchedEvent = null;
    }


    public function tearDown()
    {
        DispatcherStatic::removeAllListeners();
        $this->_eventGivenToListenerA = null;
    }



    public function  test_events_dispatcher_creation()
    {
        $d = new Dispatcher();
        $this->assertInstanceOf( '\Ripple\Dispatcher', DispatcherStatic::dispatcher() );
    }



    public function test_events_addPublicListenerCallback(  )
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertEquals( 1, count( \Ripple\DispatcherStatic::getAllListeners() ) );

    }



    public function test_events_addProtectedListenerCallback_shouldFail(  )
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerProtected' ) );
        $this->assertEquals( 0, count( DispatcherStatic::getAllListeners() ) );
    }


    public function test_events_dispatch_eventIsString()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        DispatcherStatic::dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertNull( $this->_catchedEvent->getParam() );
        $this->assertNull( $this->_catchedEvent->getParams() );
    }


    public function test_events_dispatch_eventIsEvent()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        DispatcherStatic::dispatch( new Event( 'test', $this, array(1,2), false ) );
        $this->assertEquals( $this, $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertInternalType( 'array', $this->_catchedEvent->getParam() );
        $this->assertEquals( array(1,2), $this->_catchedEvent->getParams() );
    }


    public function test_events_fireEvent_invokeCallback_expectsArray(  )
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertTrue( is_array( $result ) );
        $this->assertEquals( static::LISTENER_A_RESULT, $result[0] );
    }



    public function test_events_fireEvent_withoutListenerInvocation_expectsNull( )
    {
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertNull(  $result  );
    }


    public function test_events_listener_gets_event_object( )
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        DispatcherStatic::dispatch( 'test', $this );
        $this->assertInstanceOf(  '\Ripple\Event', $this->_eventGivenToListenerA );
    }



    public function test_events_hasEventListener()
    {
        $hasListener = DispatcherStatic::hasListener( 'test' );
        $this->assertFalse( $hasListener );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $hasListener = DispatcherStatic::hasListener( 'test' );
        $this->assertTrue( $hasListener );
    }


    public function test_events_getListenersForType()
    {
         DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
         DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
         DispatcherStatic::addListener( 'anotherType', array( $this, 'listenerB' ) );
         $listeners = DispatcherStatic::getListenersForEvent( 'test' );
         $this->assertEquals( 2, count( $listeners ) );
    }


    public function test_events_getAllListeners()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
        DispatcherStatic::addListener( 'anotherType', array( $this, 'listenerB' ) );
        $listeners = DispatcherStatic::getAllListeners();
        $this->assertEquals( 3, count( $listeners ) );
    }


    public function test_events_removeAllListeners()
    {
         DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
         DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
         DispatcherStatic::addListener( 'anotherType', array( $this, 'listenerB' ) );
         DispatcherStatic::removeAllListeners();
         $listeners = DispatcherStatic::getAllListeners();
         $this->assertEquals( 0, count( $listeners ) );
    }



    public function test_events_multipleListeners_normalOrder_lastAttachedListener_firesFirst()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertEquals( 2, count( $result ) );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }



    public function test_events_multipleListeners_customPriority()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ), -100 );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertEquals( 2, count( $result ) );
        $this->assertEquals( static::LISTENER_A_RESULT, $result[0] );
    }



    public function test_events_multipleListeners_customPriorityInverted()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ), 100 );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ), 0 );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertEquals( 2, count( $result ) );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }


    public function test_events_stopPropagation()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerACancel' ), 0 );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertEquals( 1, count( $result ) );
    }


    public function test_events_stopPropagation_with_cancelableFalse_shouldNotCancel()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerACancel' ), 0 );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = DispatcherStatic::dispatch( new Event( 'test', $this, null, false ) );
        $this->assertEquals( 2, count( $result ) );
    }


    public function test_events_dispatchUntil()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerTrue' ), 1 ); // fired first , should stop here
        DispatcherStatic::addListener( 'test', array( $this, 'listenerFalse' ), 100 );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerFalse' ), 200 );
        $event =  new Event( 'test', $this, null, false );
        $result = DispatcherStatic::dispatchUntil( $event );
        $this->assertEquals( 1, $this->_eventsTrackedTrueReturnCount );
    }




    public function test_events_getParam_noArg_fetches_params()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' )  );
        DispatcherStatic::dispatch( 'test', $this, new EventTestParam() );
        $this->assertInstanceOf( 'EventTestParam', $this->_catchedEvent->getParam() );
    }


    public function test_events_getParam_namedArg()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' )  );
        DispatcherStatic::dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $this->assertInstanceOf( 'EventTestParam', $this->_catchedEvent->getParam( 'param1' ) );
    }


    public function test_events_getParams()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' )  );
        DispatcherStatic::dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $params = $this->_catchedEvent->getParams();
        $this->assertInstanceOf( 'EventTestParam', $params['param1'] );
    }


    public function test_events_removeSingleListener()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertEquals( 1, count( DispatcherStatic::getAllListeners() ) );
        DispatcherStatic::removeListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertEquals( 0, count( DispatcherStatic::getAllListeners() ) );
        DispatcherStatic::dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }


    public function test_events_removeAllListenersForEvent()
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertEquals( 2, count( DispatcherStatic::getAllListeners() ) );
        DispatcherStatic::removeListenersForEvent( 'test' );
        $this->assertEquals( 0, count( DispatcherStatic::getAllListeners() ) );
        DispatcherStatic::dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }



    public function test_event_aggregate()
    {
        $listener = new StaticTestEventTestListener();
        DispatcherStatic::addListenerAggregate( 'test', $listener );
        DispatcherStatic::dispatch( 'test:beforeTest' );
        DispatcherStatic::dispatch( 'test:afterTest' );
        DispatcherStatic::dispatch( 'test:nonExisting' );
        $this->assertEquals( 2, count( $listener->registrar->capturedTypes ) );
    }



    /**
     * @param  Event $e
     * @return string
     */
    public function listenerDefault( $e )
    {
        $this->_catchedEvent = $e;
        return true;
    }



    /**
     * @param  Event $e
     * @return string
     */
    public function listenerA( $e )
    {
        $this->_eventGivenToListenerA = $e;
        return static::LISTENER_A_RESULT;
    }


    /**
     * @param Event $e
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


class StaticTestEventTestListener
{
    /** @var stdClass */
    public $registrar;

    public function __construct()
    {
        $this->registrar = new stdClass();
        $this->registrar->capturedTypes = array();
    }

    /**
     * @param $e Event
     */
    public function beforeTest( $e )
    {
        $this->registrar->setByCb = true;
        $this->registrar->capturedTypes[ ] = $e->getType();
    }
    /**
     * @param $e Event
     */
    public function afterTest( $e )
    {
        $this->registrar->setByCb = true;
        $this->registrar->capturedTypes[ ] = $e->getType();
    }

}


?>
