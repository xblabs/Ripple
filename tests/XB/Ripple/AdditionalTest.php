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
        $dispatcher->addListener('test', function ($foo, $bar) use (&$received) {
            $received = [$foo, $bar];
        });
        $dispatcher->dispatch('test', null, ['first', 'second']);
        $this->assertSame(['first', 'second'], $received);
    }

    /**
     * Test that a closure with the first parameter named 'event' still
     * receives the Event object rather than the parameter array.
     */
    public function test_useParamsAsCallbackArg_with_event_named_parameter(): void
    {
        $dispatcher = new Dispatcher();
        $captured = null;
        $dispatcher->addListener('test', function ($event, $foo = null) use (&$captured) {
            $captured = $event;
        });
        $dispatcher->dispatch('test', null, ['ignored']);
        $this->assertInstanceOf(Event::class, $captured);
    }

    /**
     * Test that passing a non-callable listener to addListener raises a TypeError.
     */
    public function test_addListener_with_non_callable_throws_type_error(): void
    {
        $this->expectException(\TypeError::class);
        $dispatcher = new Dispatcher();
        // Intentionally invalid
        $dispatcher->addListener('test', 'not callable');
    }

    /**
     * Test that dispatching an invalid event type triggers a TypeError.
     */
    public function test_dispatch_with_invalid_event_type_throws_type_error(): void
    {
        $this->expectException(\TypeError::class);
        $dispatcher = new Dispatcher();
        // Integer is not allowed for event type
        $dispatcher->dispatch(123);
    }

    /**
     * Test custom event class support via setEventClass().
     */
    public function test_setEventClass_uses_custom_event_subclass(): void
    {
        if (!class_exists(CustomEvent::class)) {
            class CustomEvent extends Event
            {
                public function getCustom(): string
                {
                    return 'custom';
                }
            }
        }
        $dispatcher = new Dispatcher();
        $dispatcher->setEventClass(CustomEvent::class);
        $captured = null;
        $dispatcher->addListener('custom', function ($event) use (&$captured) {
            $captured = $event;
        });
        $dispatcher->dispatch('custom');
        $this->assertInstanceOf(CustomEvent::class, $captured);
        $this->assertSame('custom', $captured->getCustom());
    }

    /**
     * Test that removeListenersForEvent returns the correct count of removed listeners.
     */
    public function test_removeListenersForEvent_returns_correct_count(): void
    {
        $dispatcher = new Dispatcher();
        $listener = static function () {};
        $dispatcher->addListener('multi', $listener);
        $dispatcher->addListener('multi', $listener);
        $removedCount = $dispatcher->removeListenersForEvent('multi');
        $this->assertSame(2, $removedCount);
        $this->assertFalse($dispatcher->hasListener('multi'));
    }

    /**
     * Test that dispatching when no listeners are registered returns null.
     */
    public function test_dispatch_without_listeners_returns_null(): void
    {
        $dispatcher = new Dispatcher();
        $this->assertNull($dispatcher->dispatch('no.listeners'));
    }

    /**
     * Test that passing an object as parameters still allows access via getParam().
     */
    public function test_dispatch_with_object_params_accessible_via_getParam(): void
    {
        $dispatcher = new Dispatcher();
        $captured = null;
        $dispatcher->addListener('object.event', function (Event $e) use (&$captured) {
            $captured = $e;
        });
        $params = new \stdClass();
        $params->foo = 'bar';
        $dispatcher->dispatch('object.event', null, $params);
        $this->assertInstanceOf(Event::class, $captured);
        $this->assertSame('bar', $captured->getParam('foo'));
    }
}
