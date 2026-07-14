<?php

declare( strict_types=1 );

namespace XB\Ripple\Psr14;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use XB\Ripple\Dispatcher;

/**
 * PSR-14 EventDispatcherInterface adapter over Ripple.
 *
 * This is a thin, additive layer: PSR-14 dispatches an OBJECT and returns that
 * same object, ignoring listener return values, whereas native Ripple dispatches
 * event NAMES and collects listener responses. The two paths share only listener
 * storage and ordering (via {@see ListenerProvider}); neither reimplements the other.
 */
final class Psr14Dispatcher implements EventDispatcherInterface
{
	private readonly ListenerProviderInterface $provider;

	public function __construct( ListenerProviderInterface|Dispatcher $providerOrDispatcher )
	{
		$this->provider = $providerOrDispatcher instanceof Dispatcher
			? new ListenerProvider( $providerOrDispatcher )
			: $providerOrDispatcher;
	}

	public function getListenerProvider(): ListenerProviderInterface
	{
		return $this->provider;
	}

	/**
	 * Pass the event to each registered listener, halting early if it is a
	 * StoppableEventInterface whose propagation has been stopped. Returns the
	 * (possibly mutated) event, per the PSR-14 contract.
	 */
	public function dispatch( object $event ): object
	{
		$stoppable = $event instanceof StoppableEventInterface;
		if( $stoppable && $event->isPropagationStopped() ) {
			return $event;
		}
		foreach( $this->provider->getListenersForEvent( $event ) as $listener ) {
			call_user_func( $listener, $event );
			if( $stoppable && $event->isPropagationStopped() ) {
				break;
			}
		}
		return $event;
	}
}
