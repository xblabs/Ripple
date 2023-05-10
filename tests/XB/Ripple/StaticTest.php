<?php
/**
 * @author Henry Schmieder
 * @version 0.1 20/10/12 22:34
 *
 *  @group event
 */
namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\DispatcherStatic;
use XB\Ripple\Event;

class StaticTest extends TestCase
{
    public const LISTENER_A_RESULT = 'listenerA result';
    public const LISTENER_B_RESULT = 'listenerB result';
    public const LISTENER_C_RESULT = 'listenerC result';


    /** @var Dispatcher */
    protected Dispatcher $dispatcher;


    protected array $_setByListeners;


    protected ?Event $_eventGivenToListenerA;


    protected int $_eventsTrackedTrueReturnCount;


    protected ?Event $_catchedEvent;



    public function setUp(): void
    {
        $this->_setByListeners = array();
        $this->_eventsTrackedTrueReturnCount = 0;
        $this->_catchedEvent = null;
    }


    public function tearDown(): void
    {
        DispatcherStatic::removeAllListeners();
        $this->_eventGivenToListenerA = null;
    }



    public function  test_events_dispatcher_creation(): void
    {
        $d = new Dispatcher();
        $this->assertInstanceOf( Dispatcher::class,  DispatcherStatic::dispatcher() );
    }



    public function test_events_addPublicListenerCallback(  ): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertCount( 1, DispatcherStatic::getAllListeners() );

    }


    public function test_events_with_protected_listener(): void
    {
        $_this = $this;
        DispatcherStatic::addListener( 'test', static fn( $event) => $_this->listenerProtected( $event ) );
        $this->assertCount( 1, DispatcherStatic::getAllListeners() );
    }


    public function test_events_dispatch_eventIsString(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        DispatcherStatic::dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertNull( $this->_catchedEvent->getParam() );
        $this->assertNull( $this->_catchedEvent->getParams() );
    }


    public function test_events_dispatch_eventIsEvent(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        DispatcherStatic::dispatch( new Event( 'test', $this, array(1,2), false ) );
        $this->assertEquals( $this, $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertIsArray( $this->_catchedEvent->getParam() );
        $this->assertEquals( array(1,2), $this->_catchedEvent->getParams() );
    }


    public function test_events_fireEvent_invokeCallback_expectsArray(  ): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertIsArray( $result );
        $this->assertEquals( static::LISTENER_A_RESULT, $result[0] );
    }



    public function test_events_fireEvent_withoutListenerInvocation_expectsNull( ): void
    {
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertNull(  $result  );
    }


    public function test_events_listener_gets_event_object( ): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        DispatcherStatic::dispatch( 'test', $this );
        $this->assertInstanceOf(  Event::class, $this->_eventGivenToListenerA );
    }



    public function test_events_hasEventListener(): void
    {
        $hasListener = DispatcherStatic::hasListener( 'test' );
        $this->assertFalse( $hasListener );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $hasListener = DispatcherStatic::hasListener( 'test' );
        $this->assertTrue( $hasListener );
    }


    public function test_events_getListenersForType(): void
    {
         DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
         DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
         DispatcherStatic::addListener( 'anotherType', array( $this, 'listenerB' ) );
         $listeners = DispatcherStatic::getListenersForEvent( 'test' );
         $this->assertEquals( 2, count( $listeners ) );
    }


    public function test_events_getAllListeners(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
        DispatcherStatic::addListener( 'anotherType', array( $this, 'listenerB' ) );
        $listeners = DispatcherStatic::getAllListeners();
        $this->assertCount( 3, $listeners );
    }


    public function test_events_removeAllListeners(): void
    {
         DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
         DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
         DispatcherStatic::addListener( 'anotherType', array( $this, 'listenerB' ) );
         DispatcherStatic::removeAllListeners();
         $listeners = DispatcherStatic::getAllListeners();
         $this->assertCount( 0, $listeners );
    }



    public function test_events_multipleListeners_normalOrder_lastAttachedListener_firesFirst(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ) );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertCount( 2, $result );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }



    public function test_events_multipleListeners_customPriority(): void
    {
        $_this = $this;
        DispatcherStatic::addListener( 'test', static fn( $event) => $_this->listenerA( $event ), -100 );
        DispatcherStatic::addListener( 'test', static fn( $event) => $_this->listenerB( $event ), 100 );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertCount( 2, $result );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }



    public function test_events_multipleListeners_customPriorityInverted(): void
    {
        $_this = $this; # NOTE needs local reference since otherwise $this within the closure would mean the static context of DispatcherStatic
        DispatcherStatic::addListener( 'test', static fn( $event ) => $_this->listenerA($event), 100 );
        DispatcherStatic::addListener( 'test', static fn( $event ) => $_this->listenerB($event), 300 );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertCount( 2, $result );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }


    public function test_events_stopPropagation(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerACancel' ), 300 );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = DispatcherStatic::dispatch( 'test', $this );
        $this->assertCount( 1, $result );
    }


    public function test_events_stopPropagation_with_cancelableFalse_shouldNotCancel(): void
    {
        $_this = $this;
        DispatcherStatic::addListener( 'test', static fn( $event ) => $_this->listenerACancel($event), 0 );
        DispatcherStatic::addListener( 'test', static fn( $event ) => $_this->listenerB($event), 100 );
        $result = DispatcherStatic::dispatch( new Event( 'test', $this, null, false ) );
        $this->assertCount( 2, $result );
    }


    public function test_events_dispatchUntil_first(): void
    {
         $_this = $this;
        $listenerFalse = static fn( $event) => $_this->listenerFalse($event);
        $listenerTrue = static fn( $event) => $_this->listenerTrue($event);

        DispatcherStatic::addListener( 'test', $listenerTrue, 300 ); // fired first , should stop here
        DispatcherStatic::addListener( 'test', $listenerFalse, 100 );
        DispatcherStatic::addListener( 'test', $listenerFalse, 200 );
        $event =  new Event( 'test', $this, null, false );
        $result = DispatcherStatic::dispatchUntil( $event );
        $this->assertEquals( 1, $this->_eventsTrackedTrueReturnCount );
    }

    public function test_events_dispatchUntil_second(): void
    {
         $_this = $this;
        $listenerFalse = static fn( $event) => $_this->listenerFalse($event);
        $listenerTrue = static fn( $event) => $_this->listenerTrue($event);

        DispatcherStatic::addListener( 'test', $listenerFalse, 300 );
        DispatcherStatic::addListener( 'test', $listenerTrue, 200 ); // fired second , should stop here
        DispatcherStatic::addListener( 'test', $listenerFalse, 100 );
        $event =  new Event( 'test', $this, null, false );
        $result = DispatcherStatic::dispatchUntil( $event );
        $this->assertEquals( 2, $this->_eventsTrackedTrueReturnCount );
    }




    public function test_events_getParam_noArg_fetches_params(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' )  );
        DispatcherStatic::dispatch( 'test', $this, new EventTestParam() );
        $this->assertInstanceOf( EventTestParam::class, $this->_catchedEvent->getParam() );
    }


    public function test_events_getParam_namedArg(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' )  );
        DispatcherStatic::dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $this->assertInstanceOf( EventTestParam::class, $this->_catchedEvent->getParam( 'param1' ) );
    }


    public function test_events_getParams(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' )  );
        DispatcherStatic::dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $params = $this->_catchedEvent->getParams();
        $this->assertInstanceOf( EventTestParam::class, $params['param1'] );
    }


    public function test_events_removeSingleListener(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertCount( 1, DispatcherStatic::getAllListeners() );
        DispatcherStatic::removeListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertCount( 0, DispatcherStatic::getAllListeners() );
        DispatcherStatic::dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }


    public function test_events_removeAllListenersForEvent(): void
    {
        DispatcherStatic::addListener( 'test', array( $this, 'listenerDefault' ) );
        DispatcherStatic::addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertCount( 2, DispatcherStatic::getAllListeners() );
        DispatcherStatic::removeListenersForEvent( 'test' );
        $this->assertCount( 0, DispatcherStatic::getAllListeners() );
        DispatcherStatic::dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }



    public function test_event_aggregate(): void
    {
        $listener = new StaticTestEventTestListener();
        DispatcherStatic::addListenerAggregate( 'test', $listener );
        DispatcherStatic::dispatch( 'test:beforeTest' );
        DispatcherStatic::dispatch( 'test:afterTest' );
        DispatcherStatic::dispatch( 'test:nonExisting' );
        $this->assertEquals( 2, count( $listener->registrar->capturedTypes ) );
    }




    public function listenerDefault( Event $e ): bool
    {
        $this->_catchedEvent = $e;
        return true;
    }




    public function listenerA( Event $e ): string
    {
        $this->_eventGivenToListenerA = $e;
        return static::LISTENER_A_RESULT;
    }



    public function listenerACancel( Event $e ): string
    {
        $e->stopPropagation();
        return static::LISTENER_A_RESULT;
    }


    public function listenerB( Event $e ): string
    {
        return static::LISTENER_B_RESULT;
    }

    public function listenerC( Event $e ): string
    {
        return static::LISTENER_C_RESULT;
    }


     public function listenerTrue( Event $e ): bool
     {
        $this->_eventsTrackedTrueReturnCount++;
        return true;
    }

    public function listenerFalse( Event $e ): bool
    {
        $this->_eventsTrackedTrueReturnCount++;
        return false;
    }



    protected function listenerProtected(): string
    {
        return 'listener Protected result';
    }

}

