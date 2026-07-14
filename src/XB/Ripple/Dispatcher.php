<?php

declare( strict_types=1 );

namespace XB\Ripple;

class Dispatcher implements IDispatcher
{

	/** @var class-string<Event> */
	protected string $_eventClass = Event::class;

	/** @var array<string, ListenerDescriptor[]> exact listeners, keyed by event name, in insertion order */
	protected array $_listeners = [];

	/** @var array<string, ListenerDescriptor[]> per-type priority-sorted cache of {@see $_listeners} */
	protected array $_sorted = [];

	/** @var array<string, bool> per-type "sorted cache is stale" flags */
	protected array $_dirty = [];

	/** @var ListenerDescriptor[] wildcard listeners (flat, insertion order) */
	protected array $_wildcard = [];

	/** @var ListenerDescriptor[] priority-sorted cache of {@see $_wildcard} */
	protected array $_wildcardSorted = [];

	protected bool $_wildcardDirty = false;

	/** Monotonic registration counter feeding the stable LIFO tie-break. */
	protected int $_seq = 0;


	public function setEventClass( string $class ): self
	{
		if( $class !== Event::class && !is_subclass_of( $class, Event::class ) ) {
			throw new Exception( Exception::INVALID_EVENT_CLASS );
		}
		$this->_eventClass = $class;
		return $this;
	}


	public function getEventClass(): string
	{
		return $this->_eventClass;
	}


	/**
	 * Trigger all listeners for a given event.
	 * Can emulate dispatchUntil() if the last argument provided is a callback.
	 *
	 * @param string|Event $event
	 * @param string|object|null $target Object emitting the event, or a symbol describing the target
	 * @param mixed $argv Arguments; typically an associative array
	 * @param bool $useParamsAsCallbackArg Force spreading params as listener args instead of the Event object
	 * @return mixed array of listener responses, or null when nothing handled the event
	 */
	public function dispatch( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		$e = $this->resolveEventObj( $event, $target, $argv );
		return $this->_dispatch( $e, false, $useParamsAsCallbackArg );
	}

	/**
	 * Dispatch an event, halting at the first listener that returns a truthy value.
	 *
	 * @return mixed the halting listener's response, or null if none halted / no listeners
	 */
	public function dispatchUntil( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		$e = $this->resolveEventObj( $event, $target, $argv );
		return $this->_dispatch( $e, true, $useParamsAsCallbackArg );
	}

	/**
	 * Dispatch an event and return the first response only.
	 */
	public function dispatchGetFirst( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		$r = $this->dispatch( $event, $target, $argv, $useParamsAsCallbackArg );
		if( is_array( $r ) ) {
			$r = reset( $r );
		}
		return $r;
	}


	public function hasListener( string $type ): bool
	{
		return !empty( $this->_listeners[ $type ] );
	}


	/**
	 * Register a listener for an exact event name.
	 */
	public function addListener( string $type, callable $listener, int $priority = 0 ): void
	{
		$this->pushExact( $type, $listener, $priority, false );
	}

	/**
	 * Register a listener that fires at most once, then removes itself.
	 */
	public function once( string $type, callable $listener, int $priority = 0 ): void
	{
		$this->pushExact( $type, $listener, $priority, true );
	}

	/**
	 * Register a wildcard listener. The pattern may contain `*`, which matches any
	 * run of characters (including the caller's separator), so `user.*`, `user:*`
	 * and `*.deleted` are all valid and separator-agnostic.
	 */
	public function addWildcardListener( string $pattern, callable $listener, int $priority = 0 ): void
	{
		$this->pushWildcard( $pattern, $listener, $priority, false );
	}

	/**
	 * Register a wildcard listener that fires at most once, then removes itself.
	 */
	public function onceWildcard( string $pattern, callable $listener, int $priority = 0 ): void
	{
		$this->pushWildcard( $pattern, $listener, $priority, true );
	}


	/**
	 * Attach every listener declared by a subscriber's getSubscribedEvents() map.
	 */
	public function addSubscriber( EventSubscriberInterface $subscriber ): void
	{
		foreach( $subscriber::getSubscribedEvents() as $eventName => $config ) {
			foreach( $this->normalizeSubscriberConfig( $config ) as [ $method, $priority ] ) {
				/** @var callable $listener */
				$listener = [ $subscriber, $method ];
				if( str_contains( $eventName, '*' ) ) {
					$this->addWildcardListener( $eventName, $listener, $priority );
				} else {
					$this->addListener( $eventName, $listener, $priority );
				}
			}
		}
	}

	/**
	 * Detach every listener a subscriber previously registered.
	 */
	public function removeSubscriber( EventSubscriberInterface $subscriber ): void
	{
		foreach( $subscriber::getSubscribedEvents() as $eventName => $config ) {
			foreach( $this->normalizeSubscriberConfig( $config ) as [ $method, ] ) {
				/** @var callable $listener */
				$listener = [ $subscriber, $method ];
				if( str_contains( $eventName, '*' ) ) {
					$this->removeWildcardListener( $eventName, $listener );
				} else {
					$this->removeListener( $eventName, $listener );
				}
			}
		}
	}


	/**
	 * @return bool whether at least one matching listener was removed
	 */
	public function removeListener( string $type, callable $listener ): bool
	{
		if( !isset( $this->_listeners[ $type ] ) ) {
			return false;
		}
		$before = count( $this->_listeners[ $type ] );
		$this->_listeners[ $type ] = array_values( array_filter(
			$this->_listeners[ $type ],
			static fn( ListenerDescriptor $d ) => $d->listener !== $listener
		) );
		if( empty( $this->_listeners[ $type ] ) ) {
			unset( $this->_listeners[ $type ], $this->_sorted[ $type ], $this->_dirty[ $type ] );
		} else {
			$this->_dirty[ $type ] = true;
		}
		return count( $this->_listeners[ $type ] ?? [] ) < $before;
	}

	/**
	 * @return bool whether at least one matching wildcard listener was removed
	 */
	public function removeWildcardListener( string $pattern, callable $listener ): bool
	{
		$before = count( $this->_wildcard );
		$this->_wildcard = array_values( array_filter(
			$this->_wildcard,
			static fn( ListenerDescriptor $d ) => !( $d->type === $pattern && $d->listener === $listener )
		) );
		$this->_wildcardDirty = true;
		return count( $this->_wildcard ) < $before;
	}


	/**
	 * @return int number of listeners removed for that event type
	 */
	public function removeListenersForEvent( string $type ): int
	{
		$count = count( $this->_listeners[ $type ] ?? [] );
		unset( $this->_listeners[ $type ], $this->_sorted[ $type ], $this->_dirty[ $type ] );
		return $count;
	}


	/**
	 * @return array<string, ListenerDescriptor[]> exact listeners grouped by event type
	 */
	public function getAllListenersStructured(): array
	{
		return $this->_listeners;
	}


	/**
	 * @return ListenerDescriptor[] all exact listeners, flattened
	 */
	public function getAllListeners(): array
	{
		$all = [];
		foreach( $this->_listeners as $typedL ) {
			foreach( $typedL as $l ) {
				$all[] = $l;
			}
		}
		return $all;
	}


	/**
	 * @return ListenerDescriptor[] exact listeners for a type, in insertion order
	 */
	public function getListenersForEvent( string $type ): array
	{
		return $this->_listeners[ $type ] ?? [];
	}


	/**
	 * @return ListenerDescriptor[] all wildcard listeners
	 */
	public function getWildcardListeners(): array
	{
		return $this->_wildcard;
	}


	public function removeAllListeners(): void
	{
		$this->_listeners = [];
		$this->_sorted = [];
		$this->_dirty = [];
		$this->_wildcard = [];
		$this->_wildcardSorted = [];
		$this->_wildcardDirty = false;
	}


	/**
	 * @param Event $event
	 * @param bool $halt set true for dispatchUntil
	 * @param bool $useParamsAsCallbackArg force spreading params as listener args
	 * @return mixed
	 */
	protected function _dispatch( Event $event, bool $halt = false, bool $useParamsAsCallbackArg = false ): mixed
	{
		$type = $event->getType();
		if( $type === null ) {
			return null;
		}

		$descriptors = $this->resolveDescriptors( $type );
		if( empty( $descriptors ) ) {
			return null;
		}

		// $descriptors is a local (copy-on-write) snapshot, so listeners are free to
		// mutate the dispatcher (removeAllListeners, once self-removal) mid-loop.
		$responses = [];
		foreach( $descriptors as $d ) {
			if( $event->isPropagationStopped() ) {
				break;
			}
			if( $d->once ) {
				$this->unsubscribeOnce( $d );
			}
			if( $useParamsAsCallbackArg || $d->expectsRawParams ) {
				$params = $event->getParams();
				$args = array_values( is_array( $params ) ? $params : ( $params === null ? [] : [ $params ] ) );
				$response = call_user_func_array( $d->listener, $args );
			} else {
				$response = call_user_func_array( $d->listener, [ $event ] );
			}
			if( $halt && $response ) {
				return $response;
			}
			$responses[] = $response;
		}

		if( $halt ) {
			return null;
		}
		return !empty( $responses ) ? $responses : null;
	}


	/**
	 * Resolve the full, priority-ordered listener set for an event name, merging
	 * exact listeners (which resolve first) with any matching wildcard listeners.
	 * Exposed so the PSR-14 {@see \XB\Ripple\Psr14\ListenerProvider} reuses the
	 * exact same ordering as native dispatch.
	 *
	 * @return ListenerDescriptor[]
	 */
	public function resolveDescriptors( string $type ): array
	{
		// Exact listeners resolve first and are already priority-sorted.
		$descriptors = $this->sortedFor( $type );

		// Merge in any wildcard listeners whose pattern matches this event name.
		if( !empty( $this->_wildcard ) ) {
			$matched = [];
			foreach( $this->wildcardSorted() as $w ) {
				if( preg_match( (string)$w->regex, $type ) === 1 ) {
					$matched[] = $w;
				}
			}
			if( !empty( $matched ) ) {
				$descriptors = array_merge( $descriptors, $matched );
				usort( $descriptors, self::comparator() );
			}
		}

		return $descriptors;
	}


	protected function resolveEventObj( string|Event $event, string|object|null $target = null, mixed $argv = null ): Event
	{
		if( $event instanceof Event ) {
			$e = $event;
		} else {
			$e = new $this->_eventClass();
			$e->setType( $event );
		}
		// Use an explicit null check (not !empty) so falsy-but-valid values
		// like 0, '0', '', false and [] are preserved on the event.
		if( $target !== null ) {
			$e->setTarget( $target );
		}
		if( $argv !== null ) {
			$e->setParams( $argv );
		}
		return $e;
	}


	private function pushExact( string $type, callable $listener, int $priority, bool $once ): void
	{
		$this->_listeners[ $type ][] = new ListenerDescriptor(
			type: $type,
			listener: $listener,
			priority: $priority,
			sequence: $this->_seq++,
			once: $once,
			expectsRawParams: $this->detectRawParams( $listener )
		);
		$this->_dirty[ $type ] = true;
	}


	private function pushWildcard( string $pattern, callable $listener, int $priority, bool $once ): void
	{
		$this->_wildcard[] = new ListenerDescriptor(
			type: $pattern,
			listener: $listener,
			priority: $priority,
			sequence: $this->_seq++,
			once: $once,
			isWildcard: true,
			expectsRawParams: $this->detectRawParams( $listener ),
			regex: $this->compilePattern( $pattern )
		);
		$this->_wildcardDirty = true;
	}


	/**
	 * Decide once, at registration, whether a listener should receive the event's
	 * params spread as positional arguments instead of the Event object. Only real
	 * closures are inspected; this is the sole reflection in the library and never
	 * runs on the dispatch path.
	 */
	private function detectRawParams( callable $listener ): bool
	{
		if( $listener instanceof \Closure ) {
			$refl = new \ReflectionFunction( $listener );
			if( $refl->getNumberOfParameters() > 1 ) {
				$first = $refl->getParameters()[ 0 ] ?? null;
				if( $first !== null && !in_array( $first->getName(), [ 'e', 'event' ], true ) ) {
					return true;
				}
			}
		}
		return false;
	}


	private function compilePattern( string $pattern ): string
	{
		return '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#';
	}


	/**
	 * Normalize a subscriber map value to a list of [method, priority] pairs.
	 *
	 * @param string|mixed[] $config
	 * @return array<array{0: string, 1: int}>
	 */
	private function normalizeSubscriberConfig( string|array $config ): array
	{
		if( is_string( $config ) ) {
			return [ [ $config, 0 ] ];
		}
		// Single [method, priority] form.
		if( isset( $config[ 0 ] ) && is_string( $config[ 0 ] ) ) {
			return [ [ $config[ 0 ], $config[ 1 ] ?? 0 ] ];
		}
		// List of [method, priority] pairs (or bare method strings).
		$out = [];
		foreach( $config as $pair ) {
			if( is_string( $pair ) ) {
				$out[] = [ $pair, 0 ];
			} else {
				$out[] = [ $pair[ 0 ], $pair[ 1 ] ?? 0 ];
			}
		}
		return $out;
	}


	private function unsubscribeOnce( ListenerDescriptor $d ): void
	{
		if( $d->isWildcard ) {
			$this->_wildcard = array_values( array_filter(
				$this->_wildcard,
				static fn( ListenerDescriptor $x ) => $x !== $d
			) );
			$this->_wildcardDirty = true;
			return;
		}
		$type = $d->type;
		if( !isset( $this->_listeners[ $type ] ) ) {
			return;
		}
		$this->_listeners[ $type ] = array_values( array_filter(
			$this->_listeners[ $type ],
			static fn( ListenerDescriptor $x ) => $x !== $d
		) );
		if( empty( $this->_listeners[ $type ] ) ) {
			unset( $this->_listeners[ $type ], $this->_sorted[ $type ], $this->_dirty[ $type ] );
		} else {
			$this->_dirty[ $type ] = true;
		}
	}


	/**
	 * Return the priority-sorted descriptors for a type, rebuilding the cache only
	 * when the bucket has changed since the last dispatch.
	 *
	 * @return ListenerDescriptor[]
	 */
	protected function sortedFor( string $type ): array
	{
		if( !isset( $this->_listeners[ $type ] ) ) {
			return [];
		}
		if( ( $this->_dirty[ $type ] ?? true ) || !isset( $this->_sorted[ $type ] ) ) {
			$sorted = $this->_listeners[ $type ];
			if( count( $sorted ) > 1 ) {
				usort( $sorted, self::comparator() );
			}
			$this->_sorted[ $type ] = $sorted;
			$this->_dirty[ $type ] = false;
		}
		return $this->_sorted[ $type ];
	}


	/**
	 * @return ListenerDescriptor[]
	 */
	protected function wildcardSorted(): array
	{
		if( $this->_wildcardDirty ) {
			$sorted = $this->_wildcard;
			if( count( $sorted ) > 1 ) {
				usort( $sorted, self::comparator() );
			}
			$this->_wildcardSorted = $sorted;
			$this->_wildcardDirty = false;
		}
		return $this->_wildcardSorted;
	}


	/**
	 * Ordering: priority DESC, then exact before wildcard, then LIFO (later
	 * registration first) for equal priorities.
	 */
	public static function comparator(): \Closure
	{
		return static fn( ListenerDescriptor $a, ListenerDescriptor $b ) =>
			( $b->priority <=> $a->priority )
			?: ( $a->isWildcard <=> $b->isWildcard )
			?: ( $b->sequence <=> $a->sequence );
	}

}
