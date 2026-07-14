<?php

declare( strict_types=1 );

namespace XB\Ripple\Psr14;

use Psr\EventDispatcher\ListenerProviderInterface;
use XB\Ripple\Dispatcher;
use XB\Ripple\Event;

/**
 * Bridges Ripple's listener storage to PSR-14. It reuses {@see Dispatcher::resolveDescriptors()}
 * so PSR-14 dispatch honours the same priority ordering as native dispatch.
 *
 * A Ripple {@see Event} is matched by its string type. Any other object (a plain
 * PSR-14 event) is matched by its class name, parent classes and interfaces, so
 * listeners can be registered against a base class or interface name.
 */
final class ListenerProvider implements ListenerProviderInterface
{
	public function __construct(
		private readonly Dispatcher $dispatcher
	)
	{
	}

	public function getDispatcher(): Dispatcher
	{
		return $this->dispatcher;
	}

	/**
	 * @return iterable<callable>
	 */
	public function getListenersForEvent( object $event ): iterable
	{
		$types = $this->typesFor( $event );

		$set = [];
		$seen = [];
		foreach( $types as $type ) {
			foreach( $this->dispatcher->resolveDescriptors( $type ) as $descriptor ) {
				$id = spl_object_id( $descriptor );
				if( isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$set[] = $descriptor;
			}
		}

		// Each type is pre-sorted; only a multi-type (class-hierarchy) match needs a merge sort.
		if( count( $types ) > 1 && count( $set ) > 1 ) {
			usort( $set, Dispatcher::comparator() );
		}

		foreach( $set as $descriptor ) {
			yield $descriptor->listener;
		}
	}

	/**
	 * @return string[]
	 */
	private function typesFor( object $event ): array
	{
		if( $event instanceof Event && $event->getType() !== null ) {
			return [ $event->getType() ];
		}
		return [
			$event::class,
			...array_values( class_parents( $event ) ?: [] ),
			...array_values( class_implements( $event ) ?: [] ),
		];
	}
}
