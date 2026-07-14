<?php

/**
 * Edge case tests for the Event class.
 *
 * Tests methods and scenarios not covered by the main test suite:
 * - setParam() for arrays, objects, and ArrayAccess
 * - isCancelable() and setCancelable()
 * - __toString() conversion
 * - getParam() with default values
 * - Edge cases for parameter access
 */

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Event;

class EventEdgeCasesTest extends TestCase
{
    /**
     * Test setParam() with array parameters
     */
    public function test_setParam_with_array_params(): void
    {
        $event = new Event( 'test', null, ['foo' => 'original'] );
        $event->setParam( 'foo', 'updated' );
        $event->setParam( 'bar', 'new' );

        $this->assertSame( 'updated', $event->getParam( 'foo' ) );
        $this->assertSame( 'new', $event->getParam( 'bar' ) );
    }

    /**
     * Test setParam() with object parameters
     */
    public function test_setParam_with_object_params(): void
    {
        $params = new \stdClass();
        $params->foo = 'original';

        $event = new Event( 'test', null, $params );
        $event->setParam( 'foo', 'updated' );
        $event->setParam( 'bar', 'new' );

        $this->assertSame( 'updated', $event->getParam( 'foo' ) );
        $this->assertSame( 'new', $event->getParam( 'bar' ) );
    }

    /**
     * Test setParam() with ArrayAccess implementation
     */
    public function test_setParam_with_ArrayAccess(): void
    {
        $params = new \ArrayObject( ['foo' => 'original'] );

        $event = new Event( 'test', null, $params );
        $event->setParam( 'foo', 'updated' );
        $event->setParam( 'bar', 'new' );

        $this->assertSame( 'updated', $event->getParam( 'foo' ) );
        $this->assertSame( 'new', $event->getParam( 'bar' ) );
    }

    /**
     * Regression (bug #3): setParam() on an event whose params are null (the
     * default) must lazily create an array instead of throwing a fatal Error.
     */
    public function test_setParam_initializes_when_params_null(): void
    {
        $event = new Event( 'test' ); // params default to null

        $event->setParam( 'foo', 'bar' );

        $this->assertSame( 'bar', $event->getParam( 'foo' ) );
        $this->assertSame( ['foo' => 'bar'], $event->getParams() );
    }

    /**
     * Regression (bug #3): setParam() on scalar params must promote to an array
     * rather than attempting a property assignment on a non-object.
     */
    public function test_setParam_on_scalar_params(): void
    {
        $event = new Event( 'test', null, 'scalar' );

        $event->setParam( 'key', 'value' );

        $this->assertSame( 'value', $event->getParam( 'key' ) );
        $this->assertSame( ['key' => 'value'], $event->getParams() );
    }

    /**
     * Test setParam() with integer keys
     */
    public function test_setParam_with_integer_keys(): void
    {
        $event = new Event( 'test', null, [] );
        $event->setParam( 0, 'first' );
        $event->setParam( 1, 'second' );

        $this->assertSame( 'first', $event->getParam( 0 ) );
        $this->assertSame( 'second', $event->getParam( 1 ) );
    }

    /**
     * Test getParam() with default value when param doesn't exist
     */
    public function test_getParam_returns_default_when_missing(): void
    {
        $event = new Event( 'test', null, ['foo' => 'bar'] );

        $this->assertSame( 'bar', $event->getParam( 'foo' ) );
        $this->assertSame( 'default_value', $event->getParam( 'nonexistent', 'default_value' ) );
        $this->assertNull( $event->getParam( 'nonexistent' ) );
    }

    /**
     * Test getParam() with default value on object params
     */
    public function test_getParam_default_with_object_params(): void
    {
        $params = new \stdClass();
        $params->exists = 'value';

        $event = new Event( 'test', null, $params );

        $this->assertSame( 'value', $event->getParam( 'exists' ) );
        $this->assertSame( 'fallback', $event->getParam( 'missing', 'fallback' ) );
    }

    /**
     * Test isCancelable() returns correct value
     */
    public function test_isCancelable_returns_correct_value(): void
    {
        $cancelable = new Event( 'test', null, null, true );
        $notCancelable = new Event( 'test', null, null, false );

        $this->assertTrue( $cancelable->isCancelable() );
        $this->assertFalse( $notCancelable->isCancelable() );
    }

    /**
     * Test setCancelable() changes cancelable state
     */
    public function test_setCancelable_changes_state(): void
    {
        $event = new Event( 'test', null, null, true );
        $this->assertTrue( $event->isCancelable() );

        $event->setCancelable( false );
        $this->assertFalse( $event->isCancelable() );

        $event->setCancelable( true );
        $this->assertTrue( $event->isCancelable() );
    }

    /**
     * Test setCancelable() returns fluent interface
     */
    public function test_setCancelable_returns_this(): void
    {
        $event = new Event( 'test' );
        $result = $event->setCancelable( false );

        $this->assertSame( $event, $result );
    }

    /**
     * Test stopPropagation() respects cancelable state
     */
    public function test_stopPropagation_only_works_when_cancelable(): void
    {
        $cancelable = new Event( 'test', null, null, true );
        $cancelable->stopPropagation();
        $this->assertTrue( $cancelable->isPropagationStopped() );

        $notCancelable = new Event( 'test', null, null, false );
        $notCancelable->stopPropagation();
        $this->assertFalse( $notCancelable->isPropagationStopped() );
    }

    /**
     * Test __toString() returns event type
     */
    public function test_toString_returns_type(): void
    {
        $event = new Event( 'my.event.type' );

        $this->assertSame( 'my.event.type', (string)$event );
        $this->assertSame( 'my.event.type', $event->__toString() );
    }

    /**
     * Test __toString() with null type
     */
    public function test_toString_with_null_type(): void
    {
        $event = new Event();

        $this->assertSame( '', (string)$event );
    }

    /**
     * Test fluent interface chaining for Event methods
     */
    public function test_fluent_interface_chaining(): void
    {
        $event = new Event();

        $result = $event
            ->setType( 'test.event' )
            ->setTarget( $this )
            ->setParams( ['foo' => 'bar'] )
            ->setCancelable( true )
            ->setParam( 'baz', 'qux' );

        $this->assertSame( $event, $result );
        $this->assertSame( 'test.event', $event->getType() );
        $this->assertSame( $this, $event->getTarget() );
        $this->assertSame( 'bar', $event->getParam( 'foo' ) );
        $this->assertSame( 'qux', $event->getParam( 'baz' ) );
        $this->assertTrue( $event->isCancelable() );
    }

    /**
     * Test getParam() with null name returns all params
     */
    public function test_getParam_null_name_returns_all_params(): void
    {
        $params = ['foo' => 'bar', 'baz' => 'qux'];
        $event = new Event( 'test', null, $params );

        $this->assertSame( $params, $event->getParam( null ) );
        $this->assertSame( $params, $event->getParams() );
    }

    /**
     * Test setParams() completely replaces params
     */
    public function test_setParams_replaces_all_params(): void
    {
        $event = new Event( 'test', null, ['old' => 'value'] );

        $newParams = ['new' => 'data'];
        $event->setParams( $newParams );

        $this->assertSame( $newParams, $event->getParams() );
        $this->assertNull( $event->getParam( 'old' ) );
        $this->assertSame( 'data', $event->getParam( 'new' ) );
    }

    /**
     * Test Event with various target types
     */
    public function test_event_with_various_target_types(): void
    {
        // Object target
        $objTarget = new \stdClass();
        $event1 = new Event( 'test', $objTarget );
        $this->assertSame( $objTarget, $event1->getTarget() );

        // String target (like static method name)
        $event2 = new Event( 'test', 'MyClass::staticMethod' );
        $this->assertSame( 'MyClass::staticMethod', $event2->getTarget() );

        // Test target
        $event3 = new Event( 'test', $this );
        $this->assertSame( $this, $event3->getTarget() );
    }
}
