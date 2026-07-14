<?php

declare( strict_types=1 );

namespace Test\XB\Ripple;

use PHPUnit\Framework\TestCase;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;
use XB\Ripple\EventSubscriberInterface;

class SubscriberTest extends TestCase
{
    protected Dispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new Dispatcher();
    }

    public function test_subscriber_registers_all_declared_events(): void
    {
        $sub = new class implements EventSubscriberInterface {
            public array $calls = [];
            public static function getSubscribedEvents(): array
            {
                return [
                    'user.login'  => 'onLogin',
                    'user.logout' => 'onLogout',
                ];
            }
            public function onLogin( Event $e ): void { $this->calls[] = 'login'; }
            public function onLogout( Event $e ): void { $this->calls[] = 'logout'; }
        };

        $this->dispatcher->addSubscriber( $sub );
        $this->dispatcher->dispatch( 'user.login' );
        $this->dispatcher->dispatch( 'user.logout' );

        $this->assertSame( ['login', 'logout'], $sub->calls );
    }

    public function test_subscriber_priority_multi_method_form(): void
    {
        $sub = new class implements EventSubscriberInterface {
            public array $calls = [];
            public static function getSubscribedEvents(): array
            {
                return [
                    'evt' => [['low', 1], ['high', 100]],
                ];
            }
            public function low( Event $e ): void { $this->calls[] = 'low'; }
            public function high( Event $e ): void { $this->calls[] = 'high'; }
        };

        $this->dispatcher->addSubscriber( $sub );
        $this->dispatcher->dispatch( 'evt' );

        $this->assertSame( ['high', 'low'], $sub->calls );
    }

    public function test_subscriber_single_method_priority_form(): void
    {
        $sub = new class implements EventSubscriberInterface {
            public bool $called = false;
            public static function getSubscribedEvents(): array
            {
                return ['evt' => ['handle', 5]];
            }
            public function handle( Event $e ): void { $this->called = true; }
        };

        $this->dispatcher->addSubscriber( $sub );

        $this->assertSame( 5, $this->dispatcher->getListenersForEvent( 'evt' )[0]->priority );
        $this->dispatcher->dispatch( 'evt' );
        $this->assertTrue( $sub->called );
    }

    public function test_subscriber_wildcard_key(): void
    {
        $sub = new class implements EventSubscriberInterface {
            public array $seen = [];
            public static function getSubscribedEvents(): array
            {
                return ['order.*' => 'onOrder'];
            }
            public function onOrder( Event $e ): void { $this->seen[] = $e->getType(); }
        };

        $this->dispatcher->addSubscriber( $sub );
        $this->dispatcher->dispatch( 'order.created' );
        $this->dispatcher->dispatch( 'order.shipped' );

        $this->assertSame( ['order.created', 'order.shipped'], $sub->seen );
        $this->assertCount( 1, $this->dispatcher->getWildcardListeners() );
    }

    public function test_removeSubscriber_detaches_all(): void
    {
        $sub = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return ['a' => 'onA', 'b' => 'onB', 'wild.*' => 'onWild'];
            }
            public function onA( Event $e ): void {}
            public function onB( Event $e ): void {}
            public function onWild( Event $e ): void {}
        };

        $this->dispatcher->addSubscriber( $sub );
        $this->assertTrue( $this->dispatcher->hasListener( 'a' ) );
        $this->assertTrue( $this->dispatcher->hasListener( 'b' ) );
        $this->assertCount( 1, $this->dispatcher->getWildcardListeners() );

        $this->dispatcher->removeSubscriber( $sub );

        $this->assertFalse( $this->dispatcher->hasListener( 'a' ) );
        $this->assertFalse( $this->dispatcher->hasListener( 'b' ) );
        $this->assertCount( 0, $this->dispatcher->getWildcardListeners() );
    }

    /**
     * Regression (bug #1): a subscriber that does not handle an event must not
     * cause a previously-fired listener to be re-invoked. In v1 the aggregate
     * router re-fired the previous listener when a later one lacked the method.
     */
    public function test_non_matching_subscriber_does_not_reinvoke_previous(): void
    {
        $handler = new class implements EventSubscriberInterface {
            public int $calls = 0;
            public static function getSubscribedEvents(): array
            {
                return ['shared' => 'handle'];
            }
            public function handle( Event $e ): void { $this->calls++; }
        };
        $other = new class implements EventSubscriberInterface {
            public int $calls = 0;
            public static function getSubscribedEvents(): array
            {
                return ['other' => 'handle'];
            }
            public function handle( Event $e ): void { $this->calls++; }
        };

        $this->dispatcher->addSubscriber( $handler );
        $this->dispatcher->addSubscriber( $other );

        $result = $this->dispatcher->dispatch( 'shared' );

        $this->assertSame( 1, $handler->calls );
        $this->assertSame( 0, $other->calls );
        $this->assertCount( 1, $result );
    }
}
