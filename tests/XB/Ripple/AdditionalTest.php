<?php

/*
 * Additional tests for the Ripple event dispatcher.
 *
 * These tests complement the existing suite by covering edge cases,
 * error handling and lesser used features. They ensure the dispatcher
 * behaves correctly when using parameter forwarding, custom event
 * classes and when no listeners are registered, and that exceptions
 * are thrown for invalid inputs. They also validate the return
 * value of removeListenersForEvent.
 */

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;

class AdditionalTest extends TestCase
{
    /**
     * Test that closures with multiple non-event parameters receive
     * the event's parameters directly rather than the Event object.
     */
    public function test_useParamsAsCallbackArg_forwards_parameters(): void
    {
        $dispatcher = new Dispatcher();
        $received = [];
        // Closure expects two parameters not named 'e' or 'event'.
        $dispatcher->addListener( 'test', function ( $foo, $bar ) use ( &$received ) {
            $received = [$foo, $bar];
        } );
        $dispatcher->dispatch( 'test', null, ['first', 'second'] );
        $this->assertSame( ['first', 'second'], $received );
    }

    /**
     * Test that a closure with the first parameter named 'event' still
     * receives the Event object rather than the parameter array.
     */
    public function test_useParamsAsCallbackArg_with_event_named_parameter(): void
    {
        $dispatcher = new Dispatcher();
        $captured = null;
        $dispatcher->addListener( 'test', function ( $event, $foo = null ) use ( &$captured ) {
            $captured = $event;
        } );
        $dispatcher->dispatch( 'test', null, ['ignored'] );
        $this->assertInstanceOf( Event::class, $captured );
    }

    /**
     * Regression (bug #2): the raw-params heuristic must be decided per-listener.
     * A multi-arg closure that triggers param-spreading must NOT force a later,
     * Event-expecting listener to receive raw params too.
     */
    public function test_useParamsAsCallbackArg_does_not_leak_to_later_listeners(): void
    {
        $dispatcher = new Dispatcher();
        $rawReceived = null;
        $secondReceived = 'NOT-SET';

        // Higher priority: multi-arg, triggers raw-params mode for itself only.
        $dispatcher->addListener( 'evt', function ( $foo, $bar ) use ( &$rawReceived ) {
            $rawReceived = [$foo, $bar];
        }, 100 );

        // Lower priority: expects the Event object.
        $dispatcher->addListener( 'evt', function ( $event ) use ( &$secondReceived ) {
            $secondReceived = $event;
        }, 50 );

        $dispatcher->dispatch( 'evt', null, ['x', 'y'] );

        $this->assertSame( ['x', 'y'], $rawReceived );
        $this->assertInstanceOf( Event::class, $secondReceived );
    }

    /**
     * Regression (bug #5): falsy-but-valid target and params must be preserved
     * on the event, not silently dropped by an !empty() check.
     *
     * @dataProvider falsyParamProvider
     */
    public function test_dispatch_preserves_falsy_target_and_params( $target, $params ): void
    {
        $dispatcher = new Dispatcher();
        $captured = null;
        $dispatcher->addListener( 'test', function ( Event $e ) use ( &$captured ) {
            $captured = $e;
        } );

        $dispatcher->dispatch( 'test', $target, $params );

        $this->assertSame( $target, $captured->getTarget() );
        $this->assertSame( $params, $captured->getParams() );
    }

    public static function falsyParamProvider(): array
    {
        return [
            'string zero'  => ['0', '0'],
            'int zero'     => ['0', 0],
            'false'        => ['0', false],
            'empty string' => ['0', ''],
            'empty array'  => ['0', []],
        ];
    }

    /**
     * Test that passing a non-callable listener to addListener raises a TypeError.
     */
    public function test_addListener_with_non_callable_throws_type_error(): void
    {
        $this->expectException( \TypeError::class );
        $dispatcher = new Dispatcher();
        // Intentionally invalid
        $dispatcher->addListener( 'test', 'not callable' );
    }

    /**
     * Test custom event class support via setEventClass().
     */
    public function test_setEventClass_uses_custom_event_subclass(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setEventClass( CustomEvent::class );
        $captured = null;
        $dispatcher->addListener( 'custom', function ( $event ) use ( &$captured ) {
            $captured = $event;
        } );
        $dispatcher->dispatch( 'custom' );
        $this->assertInstanceOf( CustomEvent::class, $captured );
        $this->assertSame( 'custom', $captured->getCustom() );
    }

    /**
     * Test that removeListenersForEvent returns the correct count of removed listeners.
     */
    public function test_removeListenersForEvent_returns_correct_count(): void
    {
        $dispatcher = new Dispatcher();
        $listener = static function () {};
        $dispatcher->addListener( 'multi', $listener );
        $dispatcher->addListener( 'multi', $listener );
        $removedCount = $dispatcher->removeListenersForEvent( 'multi' );
        $this->assertSame( 2, $removedCount );
        $this->assertFalse( $dispatcher->hasListener( 'multi' ) );
    }

    /**
     * Test that dispatching when no listeners are registered returns null.
     */
    public function test_dispatch_without_listeners_returns_null(): void
    {
        $dispatcher = new Dispatcher();
        $this->assertNull( $dispatcher->dispatch( 'no.listeners' ) );
    }

    /**
     * Test that passing an object as parameters still allows access via getParam().
     */
    public function test_dispatch_with_object_params_accessible_via_getParam(): void
    {
        $dispatcher = new Dispatcher();
        $captured = null;
        $dispatcher->addListener( 'object.event', function ( Event $e ) use ( &$captured ) {
            $captured = $e;
        } );
        $params = new \stdClass();
        $params->foo = 'bar';
        $dispatcher->dispatch( 'object.event', null, $params );
        $this->assertInstanceOf( Event::class, $captured );
        $this->assertSame( 'bar', $captured->getParam( 'foo' ) );
    }
}
