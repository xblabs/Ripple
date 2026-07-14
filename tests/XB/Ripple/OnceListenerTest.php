<?php

declare( strict_types=1 );

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;

class OnceListenerTest extends TestCase
{
    protected Dispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    public function test_once_fires_once_then_auto_removes(): void
    {
        $calls = 0;
        $this->dispatcher->once( 'evt', function () use ( &$calls ) {
            $calls++;
        } );

        $this->dispatcher->dispatch( 'evt' );
        $this->dispatcher->dispatch( 'evt' );

        $this->assertSame( 1, $calls );
        $this->assertFalse( $this->dispatcher->hasListener( 'evt' ) );
    }

    public function test_once_coexists_with_normal_listener(): void
    {
        $onceCalls = 0;
        $normalCalls = 0;
        $this->dispatcher->once( 'evt', function () use ( &$onceCalls ) {
            $onceCalls++;
        } );
        $this->dispatcher->addListener( 'evt', function () use ( &$normalCalls ) {
            $normalCalls++;
        } );

        $this->dispatcher->dispatch( 'evt' );
        $this->dispatcher->dispatch( 'evt' );

        $this->assertSame( 1, $onceCalls );
        $this->assertSame( 2, $normalCalls );
    }

    public function test_once_respects_priority(): void
    {
        $order = [];
        $this->dispatcher->once( 'evt', function () use ( &$order ) {
            $order[] = 'once-high';
        }, 100 );
        $this->dispatcher->addListener( 'evt', function () use ( &$order ) {
            $order[] = 'normal';
        }, 1 );

        $this->dispatcher->dispatch( 'evt' );

        $this->assertSame( ['once-high', 'normal'], $order );
    }

    public function test_once_within_dispatchUntil(): void
    {
        $calls = 0;
        $this->dispatcher->once( 'evt', function () use ( &$calls ) {
            $calls++;
            return true; // halts dispatchUntil
        } );

        $result = $this->dispatcher->dispatchUntil( 'evt' );
        $this->dispatcher->dispatchUntil( 'evt' );

        $this->assertTrue( $result );
        $this->assertSame( 1, $calls );
        $this->assertFalse( $this->dispatcher->hasListener( 'evt' ) );
    }

    public function test_onceWildcard_fires_once(): void
    {
        $calls = 0;
        $this->dispatcher->onceWildcard( 'user.*', function () use ( &$calls ) {
            $calls++;
        } );

        $this->dispatcher->dispatch( 'user.login' );
        $this->dispatcher->dispatch( 'user.logout' );

        $this->assertSame( 1, $calls );
        $this->assertCount( 0, $this->dispatcher->getWildcardListeners() );
    }

    /**
     * A once listener that never actually fires (propagation stopped before it)
     * is retained for a future dispatch.
     */
    public function test_once_not_removed_if_never_reached(): void
    {
        $onceCalls = 0;
        $this->dispatcher->addListener( 'evt', function ( Event $e ) {
            $e->stopPropagation();
        }, 100 );
        $this->dispatcher->once( 'evt', function () use ( &$onceCalls ) {
            $onceCalls++;
        }, 1 );

        $this->dispatcher->dispatch( 'evt' );

        $this->assertSame( 0, $onceCalls );
        // Both the stopper and the never-reached once listener remain registered.
        $this->assertCount( 2, $this->dispatcher->getListenersForEvent( 'evt' ) );
    }
}
