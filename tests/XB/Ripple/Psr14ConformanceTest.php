<?php

declare( strict_types=1 );

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;
use XB\Ripple\Psr14\ListenerProvider;
use XB\Ripple\Psr14\Psr14Dispatcher;

class Psr14ConformanceTest extends TestCase
{
    public function test_implements_psr14_interfaces(): void
    {
        $dispatcher = new Dispatcher();
        $this->assertInstanceOf( EventDispatcherInterface::class, new Psr14Dispatcher( $dispatcher ) );
        $this->assertInstanceOf( ListenerProviderInterface::class, new ListenerProvider( $dispatcher ) );
    }

    public function test_ripple_event_is_stoppable(): void
    {
        $this->assertInstanceOf( StoppableEventInterface::class, new Event( 'x' ) );
    }

    public function test_dispatch_returns_same_event_instance(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->addListener( 'user.login', fn( Event $e ) => null );
        $psr = new Psr14Dispatcher( $dispatcher );

        $event = new Event( 'user.login' );
        $this->assertSame( $event, $psr->dispatch( $event ) );
    }

    public function test_listeners_receive_the_event(): void
    {
        $dispatcher = new Dispatcher();
        $captured = null;
        $dispatcher->addListener( 'user.login', function ( Event $e ) use ( &$captured ) {
            $captured = $e;
        } );
        $psr = new Psr14Dispatcher( $dispatcher );

        $event = new Event( 'user.login' );
        $psr->dispatch( $event );

        $this->assertSame( $event, $captured );
    }

    public function test_stoppable_event_halts_after_stop(): void
    {
        $dispatcher = new Dispatcher();
        $order = [];
        $dispatcher->addListener( 'evt', function ( Event $e ) use ( &$order ) {
            $order[] = 'first';
            $e->stopPropagation();
        }, 100 );
        $dispatcher->addListener( 'evt', function ( Event $e ) use ( &$order ) {
            $order[] = 'second';
        }, 10 );
        $psr = new Psr14Dispatcher( $dispatcher );

        $psr->dispatch( new Event( 'evt' ) );

        $this->assertSame( ['first'], $order );
    }

    public function test_no_listeners_returns_event_unchanged(): void
    {
        $psr = new Psr14Dispatcher( new Dispatcher() );
        $event = new Event( 'nothing' );
        $this->assertSame( $event, $psr->dispatch( $event ) );
    }

    public function test_provider_yields_listeners_in_priority_order(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->addListener( 'evt', $low = fn() => 'low', 1 );
        $dispatcher->addListener( 'evt', $high = fn() => 'high', 100 );
        $provider = new ListenerProvider( $dispatcher );

        $listeners = iterator_to_array( $provider->getListenersForEvent( new Event( 'evt' ) ) );

        $this->assertSame( [$high, $low], $listeners );
    }

    /**
     * A plain PSR-14 event (not a Ripple Event) is matched by its class name.
     */
    public function test_popo_event_matched_by_class_name(): void
    {
        $event = new class implements StoppableEventInterface {
            public bool $handled = false;
            public function isPropagationStopped(): bool { return false; }
        };

        $dispatcher = new Dispatcher();
        $dispatcher->addListener( $event::class, function ( $e ) {
            $e->handled = true;
        } );
        $psr = new Psr14Dispatcher( $dispatcher );

        $psr->dispatch( $event );

        $this->assertTrue( $event->handled );
    }
}
