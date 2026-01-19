<?php

/**
 * Comprehensive tests for aggregate event patterns.
 *
 * Tests the [component]:[eventType] pattern functionality:
 * - Basic aggregate listener registration
 * - Multiple aggregate patterns
 * - Edge cases with colon separators
 * - Priority handling with aggregates
 * - Mixed aggregate and regular listeners
 */

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;

class AggregatePatternTest extends TestCase
{
    protected Dispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    public function tearDown(): void
    {
        $this->dispatcher->removeAllListeners();
    }

    /**
     * Test basic aggregate pattern with single method
     */
    public function test_basic_aggregate_pattern(): void
    {
        $listener = new class {
            public $executed = false;
            public $receivedEvent = null;

            public function onTest(Event $e) {
                $this->executed = true;
                $this->receivedEvent = $e;
                return 'onTest executed';
            }
        };

        $this->dispatcher->addListenerAggregate('component', $listener);
        $result = $this->dispatcher->dispatch('component:onTest');

        $this->assertTrue($listener->executed);
        $this->assertIsArray($result);
        $this->assertSame('onTest executed', $result[0]);
        $this->assertInstanceOf(Event::class, $listener->receivedEvent);
    }

    /**
     * Test aggregate pattern with multiple methods
     */
    public function test_aggregate_pattern_multiple_methods(): void
    {
        $listener = new class {
            public $calls = [];

            public function onCreate(Event $e) {
                $this->calls[] = 'onCreate';
                return 'created';
            }

            public function onUpdate(Event $e) {
                $this->calls[] = 'onUpdate';
                return 'updated';
            }

            public function onDelete(Event $e) {
                $this->calls[] = 'onDelete';
                return 'deleted';
            }
        };

        $this->dispatcher->addListenerAggregate('user', $listener);

        $this->dispatcher->dispatch('user:onCreate');
        $this->dispatcher->dispatch('user:onUpdate');
        $this->dispatcher->dispatch('user:onDelete');

        $this->assertSame(['onCreate', 'onUpdate', 'onDelete'], $listener->calls);
    }

    /**
     * Test aggregate pattern doesn't call non-existent methods
     */
    public function test_aggregate_pattern_nonexistent_method(): void
    {
        $listener = new class {
            public $called = false;

            public function existingMethod() {
                $this->called = true;
                return 'exists';
            }
        };

        $this->dispatcher->addListenerAggregate('test', $listener);

        // Try to dispatch to non-existent method
        $result = $this->dispatcher->dispatch('test:nonExistentMethod');

        $this->assertNull($result); // No listeners matched
        $this->assertFalse($listener->called);
    }

    /**
     * Test multiple aggregate patterns registered
     */
    public function test_multiple_aggregate_patterns(): void
    {
        $listener1 = new class {
            public $executed = false;
            public function onEvent() {
                $this->executed = true;
                return 'listener1';
            }
        };

        $listener2 = new class {
            public $executed = false;
            public function onEvent() {
                $this->executed = true;
                return 'listener2';
            }
        };

        $this->dispatcher->addListenerAggregate('component1', $listener1);
        $this->dispatcher->addListenerAggregate('component2', $listener2);

        $this->dispatcher->dispatch('component1:onEvent');
        $this->assertTrue($listener1->executed);
        $this->assertFalse($listener2->executed);

        $listener1->executed = false;
        $this->dispatcher->dispatch('component2:onEvent');
        $this->assertTrue($listener2->executed);
        $this->assertFalse($listener1->executed);
    }

    /**
     * Test aggregate pattern with priority
     */
    public function test_aggregate_pattern_with_priority(): void
    {
        $execution = [];

        $listener1 = new class {
            public $order = null;
            public function onTest(Event $e) {
                return 'low';
            }
        };

        $listener2 = new class {
            public function onTest(Event $e) {
                return 'high';
            }
        };

        $this->dispatcher->addListenerAggregate('test', $listener1, 1);
        $this->dispatcher->addListenerAggregate('test', $listener2, 100);

        $result = $this->dispatcher->dispatch('test:onTest');

        $this->assertIsArray($result);
        $this->assertSame('high', $result[0]); // Higher priority first
        $this->assertSame('low', $result[1]);
    }

    /**
     * Test aggregate pattern with Event object dispatch
     */
    public function test_aggregate_pattern_with_event_object(): void
    {
        $listener = new class {
            public $params = null;
            public $target = null;

            public function handleEvent(Event $e) {
                $this->params = $e->getParams();
                $this->target = $e->getTarget();
                return 'handled';
            }
        };

        $this->dispatcher->addListenerAggregate('component', $listener);

        $target = new \stdClass();
        $params = ['foo' => 'bar'];
        $event = new Event('component:handleEvent', $target, $params);

        $result = $this->dispatcher->dispatch($event);

        $this->assertSame($params, $listener->params);
        $this->assertSame($target, $listener->target);
        $this->assertSame('handled', $result[0]);
    }

    /**
     * Test aggregate pattern with propagation stop
     */
    public function test_aggregate_pattern_with_propagation_stop(): void
    {
        $listener = new class {
            public $firstCalled = false;
            public $secondCalled = false;

            public function onTest(Event $e) {
                $this->firstCalled = true;
                $e->stopPropagation();
                return 'first';
            }
        };

        $listener2 = new class {
            public $called = false;
            public function onTest(Event $e) {
                $this->called = true;
                return 'second';
            }
        };

        $this->dispatcher->addListenerAggregate('test', $listener, 200);
        $this->dispatcher->addListenerAggregate('test', $listener2, 100);

        $result = $this->dispatcher->dispatch('test:onTest');

        $this->assertTrue($listener->firstCalled);
        $this->assertFalse($listener2->called); // Stopped by first
        $this->assertCount(1, $result);
    }

    /**
     * Test aggregate pattern mixed with regular listeners
     */
    public function test_aggregate_mixed_with_regular_listeners(): void
    {
        $execution = [];

        $this->dispatcher->addListener('mixed:event', function() use (&$execution) {
            $execution[] = 'regular';
            return 'regular';
        });

        // When event has colon, it's treated as aggregate pattern
        // So 'mixed:event' won't match the regular listener 'mixed:event'
        // It will look for aggregate pattern 'mixed' with method 'event'
        $result = $this->dispatcher->dispatch('mixed:event');

        $this->assertEmpty($execution); // No match
        $this->assertNull($result); // No listeners matched
    }

    /**
     * Test event with multiple colons in name
     */
    public function test_event_with_multiple_colons(): void
    {
        $listener = new class {
            public $methodCalled = null;

            public function __call($name, $args) {
                $this->methodCalled = $name;
                return "called: $name";
            }
        };

        $this->dispatcher->addListenerAggregate('namespace', $listener);

        // What happens with extra colons? First is aggregate separator
        $result = $this->dispatcher->dispatch('namespace:method:extra');

        // Based on code, explode creates: ['namespace', 'method:extra']
        // So it tries to call 'method:extra' method which won't exist
        $this->assertNull($result); // No valid method found
    }

    /**
     * Test empty aggregate pattern component
     */
    public function test_empty_aggregate_component(): void
    {
        $listener = new class {
            public function method() {
                return 'result';
            }
        };

        $this->dispatcher->addListenerAggregate('', $listener);

        // Edge case: empty pattern
        $result = $this->dispatcher->dispatch(':method');

        // This creates aggregate = '', event = 'method'
        // Should work if pattern was registered with ''
        $this->assertIsArray($result);
        $this->assertSame('result', $result[0]);
    }

    /**
     * Test event type with colon but no method part
     */
    public function test_event_with_colon_no_method(): void
    {
        $listener = new class {
            public function emptyMethod() {
                return 'empty';
            }
        };

        $this->dispatcher->addListenerAggregate('component', $listener);

        // Event 'component:' has empty method name
        $result = $this->dispatcher->dispatch('component:');

        // Will try to call method named '' which doesn't exist
        $this->assertNull($result);
    }

    /**
     * Test aggregate listener receives correct event type
     */
    public function test_aggregate_listener_receives_correct_event_type(): void
    {
        $listener = new class {
            public $receivedType = null;

            public function myMethod(Event $e) {
                $this->receivedType = $e->getType();
                return 'done';
            }
        };

        $this->dispatcher->addListenerAggregate('app', $listener);
        $this->dispatcher->dispatch('app:myMethod');

        $this->assertSame('app:myMethod', $listener->receivedType);
    }

    /**
     * Test same aggregate pattern registered multiple times
     */
    public function test_same_pattern_multiple_registrations(): void
    {
        $listener1 = new class {
            public function action() { return 'first'; }
        };

        $listener2 = new class {
            public function action() { return 'second'; }
        };

        $this->dispatcher->addListenerAggregate('test', $listener1);
        $this->dispatcher->addListenerAggregate('test', $listener2);

        $result = $this->dispatcher->dispatch('test:action');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // Aggregate listeners use FIFO (appended with [])
        // With equal priority, order is preserved after sort
        $this->assertSame('first', $result[0]);
        $this->assertSame('second', $result[1]);
    }

    /**
     * Test aggregate with dispatchUntil
     */
    public function test_aggregate_with_dispatchUntil(): void
    {
        $listener = new class {
            public $calls = 0;

            public function onEvent() {
                $this->calls++;
                return true; // Stop here
            }
        };

        $listener2 = new class {
            public $calls = 0;
            public function onEvent() {
                $this->calls++;
                return false;
            }
        };

        $this->dispatcher->addListenerAggregate('test', $listener, 200);
        $this->dispatcher->addListenerAggregate('test', $listener2, 100);

        $result = $this->dispatcher->dispatchUntil('test:onEvent');

        $this->assertSame(1, $listener->calls);
        $this->assertSame(0, $listener2->calls); // Stopped before this
        $this->assertTrue($result);
    }

    /**
     * Test aggregate pattern with parameters forwarding
     */
    public function test_aggregate_with_param_forwarding(): void
    {
        $listener = new class {
            public $receivedParams = null;

            public function process($foo, $bar) {
                $this->receivedParams = [$foo, $bar];
                return 'processed';
            }
        };

        $this->dispatcher->addListenerAggregate('service', $listener);
        $result = $this->dispatcher->dispatch('service:process', null, ['param1', 'param2'], true);

        // With useParamsAsCallbackArg, params should be forwarded
        $this->assertSame(['param1', 'param2'], $listener->receivedParams);
    }
}
