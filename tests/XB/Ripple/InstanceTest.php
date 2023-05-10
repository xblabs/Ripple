<?php
/**
 * @author Henry Schmieder
 * @version 0.1 20/10/12 21:34
 *
 * @group event
 */

namespace Test\XB\Ripple;

use Closure;
use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;
use XB\Ripple\Exception;


class InstanceTest extends TestCase
{
    public const LISTENER_A_RESULT = 'listenerA result';
    public const LISTENER_B_RESULT = 'listenerB result';
    public const LISTENER_C_RESULT = 'listenerC result';


    /** @var Dispatcher */
    protected Dispatcher $dispatcher;


    protected array $_setByListeners;


    protected $_eventGivenToListenerA;


    protected int $_eventsTrackedTrueReturnCount;


    protected ?Event $_catchedEvent;



    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
        $this->_setByListeners = array();
        $this->_eventsTrackedTrueReturnCount = 0;
        $this->_catchedEvent = null;
    }


    public function tearDown(): void
    {
        if( !$this->dispatcher ) {
            return;
        }
        $this->dispatcher->removeAllListeners();
        $this->_eventGivenToListenerA = null;
    }



    public function  test_events_dispatcher_creation(): void
    {
        $this->assertInstanceOf( Dispatcher::class, $this->dispatcher );
    }



    public function test_events_addPublicListenerCallback(  ): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertCount( 1, $this->dispatcher->getAllListeners() );

    }


    public function test_events_with_protected_listener(  ): void
    {
        $this->dispatcher->addListener( 'test', static fn($event) => $this->listenerProtected($event) );
        //$this->dispatcher->addListener( 'test', Closure::fromCallable( array( $this, 'listenerProtected' ) ) );
        $this->assertCount( 1, $this->dispatcher->getAllListeners() );
    }



    /**
     * @throws Exception
     */
    public function test_events_dispatch_eventIsString(): void
    {
        $listener = Closure::fromCallable( array( $this, 'listenerDefault' ) );
        $this->dispatcher->addListener( 'test', $listener );
        $this->dispatcher->dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertNull( $this->_catchedEvent->getParam() );
        $this->assertNull( $this->_catchedEvent->getParams() );
    }


    public function test_events_dispatch_eventIsEvent(): void
    {
        $listener = Closure::fromCallable( array( $this, 'listenerDefault' ) );
        $this->dispatcher->addListener( 'test', $listener );
        $this->dispatcher->dispatch( new Event( 'test', $this, array(1,2), false ) );
        $this->assertEquals( $this, $this->_catchedEvent->getTarget() );
        $this->assertEquals( 'test', $this->_catchedEvent->getType() );
        $this->assertIsArray( $this->_catchedEvent->getParam() );
        $this->assertEquals( array(1,2), $this->_catchedEvent->getParams() );
    }


    public function test_events_fireEvent_invokeCallback_expectsArray(  ): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertIsArray( $result );
        $this->assertEquals( static::LISTENER_A_RESULT, $result[0] );
    }



    public function test_events_fireEvent_withoutListenerInvocation_expectsNull( ): void
    {
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertNull(  $result  );
    }


    public function test_events_listener_gets_event_object( ): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->dispatch( 'test', $this );
        $this->assertInstanceOf(  Event::class, $this->_eventGivenToListenerA );
    }



    public function test_events_hasEventListener(): void
    {
        $hasListener = $this->dispatcher->hasListener( 'test' );
        $this->assertFalse( $hasListener );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $hasListener = $this->dispatcher->hasListener( 'test' );
        $this->assertTrue( $hasListener );
    }


    public function test_events_getListenersForType(): void
    {
         $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
         $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
         $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
         $listeners = $this->dispatcher->getListenersForEvent( 'test' );
         $this->assertCount( 2, $listeners );
    }


    public function test_events_getAllListeners(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
        $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
        $listeners = $this->dispatcher->getAllListeners();
        $this->assertCount( 3, $listeners );
    }

    public function test_events_getAllListenersStructured(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
        $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
        $listeners = $this->dispatcher->getAllListenersStructured();
        $this->assertCount( 2, $listeners );
    }


    public function test_events_removeAllListeners(): void
    {
         $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
         $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
         $this->dispatcher->addListener( 'anotherType', array( $this, 'listenerB' ) );
         $this->dispatcher->removeAllListeners();
         $listeners = $this->dispatcher->getAllListeners();
         $this->assertEquals( 0, count( $listeners ) );
    }


    /**
     * @throws Exception
     */
    public function test_events_multipleListeners_normalOrder_lastAttachedListener_firesFirst(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ) );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertCount( 2, $result );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }


    /**
     * @throws Exception
     */
    public function test_events_multipleListeners_customPriority(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ), -100 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertCount( 2, $result );
        $this->assertEquals( static::LISTENER_B_RESULT, $result[0] );
    }



    public function test_events_multipleListeners_customPriorityInverted(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ), 100 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 0 );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertCount( 2, $result );
        $this->assertEquals( static::LISTENER_A_RESULT, $result[0] );
    }


    public function test_events_stopPropagation_after_first_fired(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerACancel' ), 300 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = $this->dispatcher->dispatch( 'test', $this );
        $this->assertCount( 1, $result );
    }


    public function test_events_stopPropagation_with_cancelableFalse_shouldNotCancel(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerACancel' ), 0 );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerB' ), 100 );
        $result = $this->dispatcher->dispatch( new Event( 'test', $this, null, false ) );
        $this->assertCount( 2, $result );
    }


    public function test_events_dispatchUntil_first(): void
    {
        $_this = $this;
        $listenerFalse = static fn( $event) => $_this->listenerFalse($event);
        $listenerTrue = static fn( $event) => $_this->listenerTrue($event);

        $this->dispatcher->addListener( 'test', $listenerTrue, 300 ); // fired first , should stop here
        $this->dispatcher->addListener( 'test', $listenerFalse, 100 );
        $this->dispatcher->addListener( 'test', $listenerFalse, 200 );
        $event =  new Event( 'test', $this, null, false );
        $this->assertEquals( 0, $this->_eventsTrackedTrueReturnCount );
        $result = $this->dispatcher->dispatchUntil( $event );
        $this->assertEquals( 1, $this->_eventsTrackedTrueReturnCount );
    }



    public function test_events_dispatchUntil_second(): void
    {
        $_this = $this;
        $listenerFalse = static fn( $event) => $_this->listenerFalse($event);
        $listenerTrue1 = static fn( $event) => $_this->listenerTrue($event);
        $listenerTrue2 = static fn( $event) => $_this->listenerTrue2($event);

        # alternative notation
//        $listenerFalse = Closure::fromCallable( [ $this, 'listenerFalse' ] );
//        $listenerTrue1 = Closure::fromCallable( [ $this, 'listenerTrue' ]  );
//        $listenerTrue2 = Closure::fromCallable( [ $this, 'listenerTrue2' ]  );

        $this->dispatcher->addListener( 'test', $listenerFalse, 3 );
        $this->dispatcher->addListener( 'test', $listenerTrue2, 2 ); // fired second , should stop here
        $this->dispatcher->addListener( 'test', $listenerTrue1 , 1 );

        $event =  new Event( 'test', $this, null, false );
        $this->assertEquals( 0, $this->_eventsTrackedTrueReturnCount );
        $result = $this->dispatcher->dispatchUntil( $event );
        $this->assertEquals( 2, $this->_eventsTrackedTrueReturnCount );

    }


    /**
     */
    public function test_events_getParam_noArg_fetches_params(): void
    {
        $listener = Closure::fromCallable([$this, 'listenerDefault']);
        $this->dispatcher->addListener( 'test', $listener  );
        $this->dispatcher->dispatch( 'test', $this, new EventTestParam() );
        $this->assertInstanceOf( EventTestParam::class, $this->_catchedEvent->getParam() );
    }


    /**
     */
    public function test_events_getFirst(): void
    {
        $this->assertFalse( $this->dispatcher->hasListener( 'test' ) );
        $counter = 0;
        $this->dispatcher->addListener( 'test', function( $e ) use(&$counter) {
            $counter++;
            return 'one';
        }, 100 );
         $this->dispatcher->addListener( 'test', function( $e ) use(&$counter) {
            $counter++;
            return false;
        }, 2 );
         $this->dispatcher->addListener( 'test', function( $e ) use(&$counter) {
            $counter++;
            return 'three';
        }, 3 );
        $result = $this->dispatcher->dispatchGetFirst( 'test' );
        $this->assertEquals( 3, $counter );
        $this->assertEquals( 'one', $result );
    }


    public function test_events_getParam_namedArg(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' )  );
        $this->dispatcher->dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $this->assertInstanceOf( EventTestParam::class, $this->_catchedEvent->getParam( 'param1' ) );
    }


    public function test_events_getParams(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' )  );
        $this->dispatcher->dispatch( 'test', $this, array( 'param1' => new EventTestParam() ) );
        $params = $this->_catchedEvent->getParams();
        $this->assertInstanceOf( EventTestParam::class, $params['param1'] );
    }


    public function test_events_removeSingleListener(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertEquals( 1, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->removeListener( 'test', array( $this, 'listenerDefault' ) );
        $this->assertEquals( 0, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }


    public function test_events_removeAllListenersForEvent(): void
    {
        $this->dispatcher->addListener( 'test', array( $this, 'listenerDefault' ) );
        $this->dispatcher->addListener( 'test', array( $this, 'listenerA' ) );
        $this->assertEquals( 2, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->removeListenersForEvent( 'test' );
        $this->assertEquals( 0, count( $this->dispatcher->getAllListeners() ) );
        $this->dispatcher->dispatch( 'test' );
        $this->assertNull( $this->_catchedEvent );
    }


    public function test_event_closureCallback(): void
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
        $this->assertInstanceOf( Event::class, $capturedE );
    }



    public function test_event_aggregate(): void
    {
        $target = new \stdClass();
        $target->test = '123';
        $listener = new EventTestListener();
        $this->dispatcher->addListenerAggregate( 'test', $listener );
        $this->dispatcher->dispatch( 'test:beforeTest' );
        $this->dispatcher->dispatch( 'test:afterTest', $target, array("one") );
        $this->dispatcher->dispatch( 'test:nonExisting' );
        $this->assertCount( 2, $listener->registrar->capturedTypes );
        $this->assertContains( 'one', $listener->registrar->params );
        $this->assertEquals( '123', $listener->registrar->target->test );
    }


    /**
     * @param  \Ripple\Event $e
     * @return string
     */
    public function listenerDefault( $e ): string
    {
        $this->_catchedEvent = $e;
        return true;
    }



    /**
     * @param  \Ripple\Event $e
     * @return string
     */
    public function listenerA( $e ):string
    {
        $this->_eventGivenToListenerA = $e;
        return static::LISTENER_A_RESULT;
    }


    /**
     * @param \Ripple\Event $e
     * @return string
     */
    public function listenerACancel( $e ):string
    {
        $e->stopPropagation();
        return static::LISTENER_A_RESULT;
    }


    public function listenerB( $e ):string
    {
        return static::LISTENER_B_RESULT;
    }

    public function listenerC( $e ):string
    {
        return static::LISTENER_C_RESULT;
    }


    public function listenerTrue( $e ): bool
    {
        $this->_eventsTrackedTrueReturnCount++;
        return true;
    }

    public function listenerTrue2( $e ): bool
    {
        $this->_eventsTrackedTrueReturnCount++;
        return true;
    }

    public function listenerFalse( $e ): bool
    {
        $this->_eventsTrackedTrueReturnCount++;
        return false;
    }



    protected function listenerProtected():string
    {
        return 'listener Protected result';
    }

}

