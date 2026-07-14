<?php

declare( strict_types=1 );

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;

class WildcardListenerTest extends TestCase
{
    protected Dispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    public function test_wildcard_prefix_matches_family(): void
    {
        $seen = [];
        $this->dispatcher->addWildcardListener( 'user.*', function ( Event $e ) use ( &$seen ) {
            $seen[] = $e->getType();
        } );

        $this->dispatcher->dispatch( 'user.login' );
        $this->dispatcher->dispatch( 'user.logout' );
        $this->dispatcher->dispatch( 'order.created' ); // no match

        $this->assertSame( ['user.login', 'user.logout'], $seen );
    }

    public function test_wildcard_is_separator_agnostic(): void
    {
        $seen = [];
        $this->dispatcher->addWildcardListener( 'user:*', function ( Event $e ) use ( &$seen ) {
            $seen[] = $e->getType();
        } );

        $this->dispatcher->dispatch( 'user:login' );
        $this->dispatcher->dispatch( 'user:profile:update' );

        $this->assertSame( ['user:login', 'user:profile:update'], $seen );
    }

    public function test_wildcard_no_match_returns_null(): void
    {
        $this->dispatcher->addWildcardListener( 'user.*', fn() => 'x' );
        $this->assertNull( $this->dispatcher->dispatch( 'order.created' ) );
    }

    public function test_exact_and_wildcard_both_fire_priority_ordered(): void
    {
        $order = [];
        $this->dispatcher->addListener( 'user.login', function () use ( &$order ) {
            $order[] = 'exact';
            return 'e';
        }, 50 );
        $this->dispatcher->addWildcardListener( 'user.*', function () use ( &$order ) {
            $order[] = 'wild-high';
            return 'wh';
        }, 100 );
        $this->dispatcher->addWildcardListener( '*', function () use ( &$order ) {
            $order[] = 'wild-low';
            return 'wl';
        }, 1 );

        $result = $this->dispatcher->dispatch( 'user.login' );

        $this->assertSame( ['wild-high', 'exact', 'wild-low'], $order );
        $this->assertCount( 3, $result );
    }

    public function test_exact_before_wildcard_on_equal_priority(): void
    {
        $order = [];
        $this->dispatcher->addWildcardListener( 'user.*', function () use ( &$order ) {
            $order[] = 'wild';
        }, 10 );
        $this->dispatcher->addListener( 'user.login', function () use ( &$order ) {
            $order[] = 'exact';
        }, 10 );

        $this->dispatcher->dispatch( 'user.login' );

        $this->assertSame( ['exact', 'wild'], $order );
    }

    public function test_wildcard_matches_once_per_dispatch(): void
    {
        $calls = 0;
        $this->dispatcher->addWildcardListener( 'user.*', function () use ( &$calls ) {
            $calls++;
        } );

        $this->dispatcher->dispatch( 'user.login' );

        $this->assertSame( 1, $calls );
    }

    public function test_removeWildcardListener(): void
    {
        $fn = fn() => 'x';
        $this->dispatcher->addWildcardListener( 'user.*', $fn );
        $this->assertCount( 1, $this->dispatcher->getWildcardListeners() );

        $this->assertTrue( $this->dispatcher->removeWildcardListener( 'user.*', $fn ) );

        $this->assertCount( 0, $this->dispatcher->getWildcardListeners() );
        $this->assertNull( $this->dispatcher->dispatch( 'user.login' ) );
    }
}
