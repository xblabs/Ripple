<?php

namespace XB\Ripple;


class Dispatcher implements IDispatcher
{

	protected string $_eventClass = Event::class;

	protected array $_listeners = [];

	protected array $_aggregatePatterns = [];


	public function setEventClass( string $class ): self
	{
		$this->_eventClass = $class;
		return $this;
	}


	/**
	 * Trigger all listeners for a given event
	 * Can emulate dispatchUntil() if the last argument provided is a callback.
	 * @param string| Event $event
	 * @param string|object|null $target Object calling emit, or symbol describing target (such as static method name)
	 * @param mixed $argv Array of arguments; typically, should be associative
	 * @param bool $useParamsAsCallbackArg
	 * @return mixed
	 */
	public function dispatch( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		$e = $this->resolveEventObj( $event, $target, $argv );
		return $this->_dispatch( $e, false, $useParamsAsCallbackArg );
	}

	/**
	 * dispatch event and halt at the first listener that returns not null or false
	 *
	 * @param string|Event $event
	 * @param string|object|null $target
	 * @param array $argv
	 * @param bool $useParamsAsCallbackArg
	 * @return array | null | bool array of gathered respones in the dispatch cycle
	 */
	public function dispatchUntil( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): array|null|bool
	{
		$e = $this->resolveEventObj( $event, $target, $argv );
		return $this->_dispatch( $e, true, $useParamsAsCallbackArg );
	}

	/**
	 *  Dispatch an event and return the first response.
	 *
	 * @param string|Event $event
	 * @param string|object|null $target
	 * @param mixed $argv
	 * @param bool $useParamsAsCallbackArg
	 * @return mixed
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
	 * @param string $type
	 * @param callable $listener
	 * @param int $priority
	 * @return void
	 */
	public function addListener( string $type, callable $listener, int $priority = 1 ): void
	{
		if( !isset( $this->_listeners[ $type ] ) ) {
			$this->_listeners[ $type ] = [];
		}
		// make first element so that last added listener fires first
		array_unshift( $this->_listeners[ $type ], new ListenerDescriptor( $type, $listener, $priority ) );
	}

	/**
	 * @param string $pattern [component]  ( used in dispatch in the form [component]:[eventType]
	 * @param object $listener
	 * @param int $priority
	 */
	public function addListenerAggregate( string $pattern, object $listener, int $priority = 1 ): void
	{
		if( !isset( $this->_aggregatePatterns[ $pattern ] ) ) {
			$this->_aggregatePatterns[ $pattern ] = [];
		}
		$this->_aggregatePatterns[ $pattern ][] = new ListenerDescriptor( $pattern, $listener, $priority );
	}


	/**
	 * @param string $type
	 * @param callable $listener
	 * @return boolean - telling if listener was removed successfully
	 */
	public function removeListener( string $type, callable $listener ): bool
	{
		$removed = false;
		foreach( $this->_listeners[ $type ] ?? [] as $i => $eventObj ) {
			if( $eventObj->type === $type && $eventObj->listener === $listener ) {
				unset( $this->_listeners[ $type ][ $i ] );
				$removed = true;
			}
		}
		return $removed;
	}


	/**
	 * @return int - amount of removed listeners for that event type
	 */
	public function removeListenersForEvent( string $type ): int
	{
		$affected = 0;
		$l = count( $this->_listeners[ $type ] );
		for( $i = $l - 1; $i > -1; $i-- ) {
			/** @var ListenerDescriptor $eventObj */
			$eventObj = $this->_listeners[ $type ][ $i ];
			if( $eventObj->type === $type ) {
				array_splice( $this->_listeners[ $type ], $i, 1 );
				$affected++;
			}
		}
		return $affected;
	}


	/**
	 * Retrieve all listeners
	 * @return array - array with the listener descriptor objects for all registered events structured by sub arrays with key event type
	 */
	public function getAllListenersStructured(): array
	{
		return $this->_listeners;
	}


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


	public function getListenersForEvent( string $type ): array
	{
		return $this->_listeners[ $type ] ?? [];
	}


	public function removeAllListeners(): void
	{
		$this->_listeners = [];
	}


	/**
	 * @param Event $event
	 * @param boolean $halt set true for dispatchUntil
	 * @return mixed
	 */
	protected function _dispatch( Event $event, bool $halt = false, bool $useParamsAsCallbackArg = false ): mixed
	{
		$responses = [];
		$descriptors = [];
		$type = $event->getType();

		if( str_contains( $type, ':' ) ) {
			$aggParts = explode( ':', $event );
			$aggregate = $aggParts[ 0 ];
			$aggEvent = $aggParts[ 1 ];
			if( isset( $this->_aggregatePatterns[ $aggregate ] ) ) {
				foreach( $this->_aggregatePatterns[ $aggregate ] as $aggDesc ) {
					$descriptors[] = $aggDesc;
				}
			}
		} else {
			$descriptors = $this->_listeners[ $type ] ?? [];
			$aggEvent = null;
		}

		if( empty( $descriptors ) ) {
			return null;
		}
		if( count( $descriptors ) > 1 ) {
			//usort( $descriptors, static fn( ListenerDescriptor $a, ListenerDescriptor $b ) => $b->priority > $a->priority );
			usort( $descriptors, static function ( ListenerDescriptor $a, ListenerDescriptor $b ) {
				if( $a->priority === $b->priority ) {
					return 0;
				}
				return ( $a->priority < $b->priority ) ? 1 : -1;
			} );
		}

		$listener = null;
		$response = null;

		foreach( $descriptors as $d ) {
			if( $event->isPropagationStopped() ) {
				break;
			}
			if( $aggEvent !== null ) {
				if( method_exists( $d->listener, $aggEvent ) ) {
					$listener = [ $d->listener, $aggEvent ];
				}
			} else {
				// if( is_callable( $d->listener ) && !$useParamsAsCallbackArg ) {
				if( $d->listener instanceof \Closure && !$useParamsAsCallbackArg ) {
					$reflInfo = new \ReflectionFunction( $d->listener );
					if( $reflInfo->getNumberOfParameters() > 1 ) {
						$reflPr = new \ReflectionParameter( $d->listener, 0 );
						$cbArgName = $reflPr->getName();
						if( !in_array( $cbArgName, [ 'e', 'event' ] ) ) {
							$useParamsAsCallbackArg = true;
						}
					}
				}
				$listener = $d->listener;

			}
			if( $listener === null ) {
				continue;
			}
			if( $useParamsAsCallbackArg ) {
				$params = $event->getParams();
				if( !is_array( $params ) ) {
					$params = [ $params ];
				}
				$response = call_user_func_array( $listener, $params );
			} else {
				$response = call_user_func_array( $listener, [ $event ] );
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


	protected function resolveEventObj( string|Event $event, string|object|null $target = null, mixed $argv = null ): Event
	{
		if( $event instanceof Event ) {
			$e = $event;
		} else {
			$e = new $this->_eventClass();
			$e->setType( $event );
		}
		if( !empty( $target ) ) {
			$e->setTarget( $target );
		}
		if( !empty( $argv ) ) {
			$e->setParams( $argv );
		}
		return $e;
	}

}

