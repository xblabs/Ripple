<?php

declare( strict_types=1 );

/**
 * Event-name routing tests for v2.
 *
 * In v1 any event name containing ':' was force-routed to the aggregate system,
 * so a regular listener on such a name was unreachable (bug #6). In v2 names are
 * opaque strings: no separator is special, and exact + wildcard listeners compose.
 */

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;

class RoutingTest extends TestCase
{
    protected Dispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    /**
     * Regression (bug #6): a regular listener registered under a colon-bearing
     * name must actually be invoked.
     */
    public function test_regular_listener_with_colon_type_is_invoked(): void
    {
        $fired = false;
        $this->dispatcher->addListener( 'order:placed', function () use ( &$fired ) {
            $fired = true;
            return 'ok';
        } );

        $result = $this->dispatcher->dispatch( 'order:placed' );

        $this->assertTrue( $fired );
        $this->assertSame( ['ok'], $result );
    }

    public function test_colon_type_without_listener_returns_null(): void
    {
        $this->assertNull( $this->dispatcher->dispatch( 'order:placed' ) );
    }

    /**
     * Exact and wildcard listeners on the same colon name compose and fire in
     * priority order.
     */
    public function test_regular_and_wildcard_colon_compose_by_priority(): void
    {
        $order = [];
        $this->dispatcher->addListener( 'order:placed', function () use ( &$order ) {
            $order[] = 'exact';
            return 'exact';
        }, 10 );
        $this->dispatcher->addWildcardListener( 'order:*', function () use ( &$order ) {
            $order[] = 'wild';
            return 'wild';
        }, 100 );

        $result = $this->dispatcher->dispatch( 'order:placed' );

        $this->assertSame( ['wild', 'exact'], $order );
        $this->assertSame( ['wild', 'exact'], $result );
    }

    /**
     * Multi-colon and dotted names are matched only by exact or wildcard rules.
     */
    public function test_dotted_and_multicolon_names_are_opaque(): void
    {
        $hits = [];
        $this->dispatcher->addListener( 'a.b.c', function () use ( &$hits ) {
            $hits[] = 'dotted';
        } );
        $this->dispatcher->addListener( 'ns:method:extra', function () use ( &$hits ) {
            $hits[] = 'multicolon';
        } );

        $this->dispatcher->dispatch( 'a.b.c' );
        $this->dispatcher->dispatch( 'ns:method:extra' );

        $this->assertSame( ['dotted', 'multicolon'], $hits );
    }
}
