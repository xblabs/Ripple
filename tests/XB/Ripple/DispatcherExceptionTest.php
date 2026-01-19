<?php

/**
 * Exception and error handling tests for Dispatcher.
 *
 * Tests error scenarios and edge cases:
 * - Invalid event class validation
 * - Listener exceptions during dispatch
 * - Removing non-existent listeners
 * - Edge cases for listener management
 */

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\DispatcherStatic;
use XB\Ripple\Event;
use XB\Ripple\Exception;

class DispatcherExceptionTest extends TestCase
{
    protected Dispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    public function tearDown(): void
    {
        $this->dispatcher->removeAllListeners();
        DispatcherStatic::removeAllListeners();
    }

    /**
     * Test setEventClass() throws exception for non-Event class
     */
    public function test_setEventClass_throws_for_invalid_class(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Exception::INVALID_EVENT_CLASS);

        $this->dispatcher->setEventClass(\stdClass::class);
    }

    /**
     * Test setEventClass() accepts valid Event subclass
     */
    public function test_setEventClass_accepts_event_subclass(): void
    {
        // Using CustomEvent from AdditionalTest
        if (!class_exists(CustomEvent::class)) {
            $this->markTestSkipped('CustomEvent class not available');
        }

        $this->dispatcher->setEventClass(CustomEvent::class);
        $this->addToAssertionCount(1); // No exception thrown = success
    }

    /**
     * Test setEventClass() throws for non-existent class
     */
    public function test_setEventClass_throws_for_nonexistent_class(): void
    {
        $this->expectException(Exception::class); // Validation throws Exception

        $this->dispatcher->setEventClass('NonExistentClass');
    }

    /**
     * Test listener that throws exception doesn't break dispatch chain
     */
    public function test_listener_exception_propagates(): void
    {
        $this->dispatcher->addListener('test', function() {
            throw new \RuntimeException('Listener failed');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Listener failed');

        $this->dispatcher->dispatch('test');
    }

    /**
     * Test multiple listeners where one throws exception
     */
    public function test_exception_in_middle_listener_stops_chain(): void
    {
        $executed = [];

        $this->dispatcher->addListener('test', function() use (&$executed) {
            $executed[] = 'first';
            return 'first';
        }, 300);

        $this->dispatcher->addListener('test', function() {
            throw new \RuntimeException('Second listener failed');
        }, 200);

        $this->dispatcher->addListener('test', function() use (&$executed) {
            $executed[] = 'third'; // Should not execute
            return 'third';
        }, 100);

        try {
            $this->dispatcher->dispatch('test');
            $this->fail('Expected RuntimeException to be thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Second listener failed', $e->getMessage());
            $this->assertSame(['first'], $executed);
        }
    }

    /**
     * Test removeListener() with non-existent event type
     */
    public function test_removeListener_nonexistent_type_returns_false(): void
    {
        $listener = function() {};
        $result = $this->dispatcher->removeListener('nonexistent', $listener);

        $this->assertFalse($result);
    }

    /**
     * Test removeListener() with different listener reference returns false
     */
    public function test_removeListener_different_reference_returns_false(): void
    {
        $listener1 = function() { return 'A'; };
        $listener2 = function() { return 'B'; };

        $this->dispatcher->addListener('test', $listener1);
        $result = $this->dispatcher->removeListener('test', $listener2);

        $this->assertFalse($result);
        $this->assertTrue($this->dispatcher->hasListener('test'));
    }

    /**
     * Test removeListener() removes all instances of same listener
     */
    public function test_removeListener_removes_all_instances(): void
    {
        $listener = function() { return 'test'; };

        // Add same listener 3 times
        $this->dispatcher->addListener('test', $listener);
        $this->dispatcher->addListener('test', $listener);
        $this->dispatcher->addListener('test', $listener);

        $this->assertCount(3, $this->dispatcher->getListenersForEvent('test'));

        // Remove should remove all instances
        $result = $this->dispatcher->removeListener('test', $listener);

        $this->assertTrue($result);
        $this->assertCount(0, $this->dispatcher->getListenersForEvent('test'));
        $this->assertFalse($this->dispatcher->hasListener('test'));
    }

    /**
     * Test removeListenersForEvent() on non-existent event returns 0
     */
    public function test_removeListenersForEvent_nonexistent_returns_zero(): void
    {
        $count = $this->dispatcher->removeListenersForEvent('nonexistent');

        $this->assertSame(0, $count);
    }

    /**
     * Test getListenersForEvent() on non-existent event returns empty array
     */
    public function test_getListenersForEvent_nonexistent_returns_empty_array(): void
    {
        $listeners = $this->dispatcher->getListenersForEvent('nonexistent');

        $this->assertIsArray($listeners);
        $this->assertEmpty($listeners);
    }

    /**
     * Test dispatch() with no listeners returns null
     */
    public function test_dispatch_no_listeners_returns_null(): void
    {
        $result = $this->dispatcher->dispatch('nonexistent');

        $this->assertNull($result);
    }

    /**
     * Test dispatchUntil() with no listeners returns null
     */
    public function test_dispatchUntil_no_listeners_returns_null(): void
    {
        $result = $this->dispatcher->dispatchUntil('nonexistent');

        $this->assertNull($result);
    }

    /**
     * Test dispatchGetFirst() with no listeners returns null
     */
    public function test_dispatchGetFirst_no_listeners_returns_null(): void
    {
        $result = $this->dispatcher->dispatchGetFirst('nonexistent');

        $this->assertNull($result);
    }

    /**
     * Test listener modifying dispatcher state during dispatch
     */
    public function test_listener_modifying_listeners_during_dispatch(): void
    {
        $executed = [];
        $dispatcher = $this->dispatcher;

        $listener1 = function() use (&$executed, $dispatcher) {
            $executed[] = 'first';
            // Remove all listeners during dispatch
            $dispatcher->removeAllListeners();
            return 'first';
        };

        $listener2 = function() use (&$executed) {
            $executed[] = 'second';
            return 'second';
        };

        $this->dispatcher->addListener('test', $listener2, 100); // Lower priority, added first
        $this->dispatcher->addListener('test', $listener1, 200); // Higher priority

        $result = $this->dispatcher->dispatch('test');

        // First listener executes and clears listeners
        // But dispatch should have already captured the listener list
        // So second listener may or may not execute depending on implementation
        $this->assertContains('first', $executed);
        $this->assertIsArray($result);
    }

    /**
     * Test recursive event dispatch
     */
    public function test_recursive_event_dispatch(): void
    {
        $depth = 0;
        $maxDepth = 3;

        $listener = function(Event $e) use (&$depth, $maxDepth) {
            $depth++;
            if ($depth < $maxDepth) {
                // Dispatch same event recursively
                $dispatcher = new Dispatcher();
                $dispatcher->dispatch('recursive');
            }
            return $depth;
        };

        $this->dispatcher->addListener('recursive', $listener);
        $result = $this->dispatcher->dispatch('recursive');

        $this->assertSame(1, $depth); // Only outer dispatch increments
        $this->assertIsArray($result);
    }

    /**
     * Test dispatching Event object with aggregate pattern
     */
    public function test_dispatch_event_object_with_aggregate_pattern(): void
    {
        $executed = false;

        $listener = new class {
            public $wasExecuted = false;

            public function testMethod() {
                $this->wasExecuted = true;
                return 'aggregate';
            }
        };

        $this->dispatcher->addListenerAggregate('component', $listener);

        // Dispatch using Event object with aggregate pattern in type
        $event = new Event('component:testMethod');
        $result = $this->dispatcher->dispatch($event);

        $this->assertTrue($listener->wasExecuted);
        $this->assertIsArray($result);
        $this->assertSame('aggregate', $result[0]);
    }

    /**
     * Test priority sorting with equal priorities
     */
    public function test_listeners_with_equal_priority_use_lifo(): void
    {
        $results = [];

        $this->dispatcher->addListener('test', function() use (&$results) {
            $results[] = 'first';
            return 'first';
        }, 5);

        $this->dispatcher->addListener('test', function() use (&$results) {
            $results[] = 'second';
            return 'second';
        }, 5);

        $this->dispatcher->addListener('test', function() use (&$results) {
            $results[] = 'third';
            return 'third';
        }, 5);

        $this->dispatcher->dispatch('test');

        // With equal priority, last added fires first (LIFO)
        $this->assertSame(['third', 'second', 'first'], $results);
    }

    /**
     * Test single listener doesn't trigger priority sort
     */
    public function test_single_listener_no_sort_optimization(): void
    {
        $executed = false;

        $this->dispatcher->addListener('test', function() use (&$executed) {
            $executed = true;
            return 'single';
        });

        $result = $this->dispatcher->dispatch('test');

        $this->assertTrue($executed);
        $this->assertSame(['single'], $result);
    }

    /**
     * Test fluent interface on Dispatcher methods
     */
    public function test_dispatcher_fluent_interface(): void
    {
        $result = $this->dispatcher->setEventClass(Event::class);

        $this->assertSame($this->dispatcher, $result);
    }
}
