<?php

namespace XB\Ripple;

class DispatcherStatic
{
	private static Dispatcher $_dispatcher;

	public static function dispatcher(): Dispatcher
	{
		return static::$_dispatcher ??= new Dispatcher();
	}

	public static function setEventClass( string $class ): void
	{
		static::dispatcher()->setEventClass( $class );
	}


	public static function dispatch( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		return static::dispatcher()->dispatch( $event, $target, $argv, $useParamsAsCallbackArg );
	}

	public static function dispatchUntil( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): array|null|bool
	{
		return static::dispatcher()->dispatchUntil( $event, $target, $argv, $useParamsAsCallbackArg );
	}

	public static function dispatchGetFirst( string|Event $event, string|object|null $target = null, mixed $argv = null, bool $useParamsAsCallbackArg = false ): mixed
	{
		$results = static::dispatcher()->dispatchGetFirst( $event, $target, $argv, $useParamsAsCallbackArg );
		if( is_array( $results ) ) {
			$results = reset( $results );
		}
		return $results;
	}

	public static function hasListener( string $type ): bool
	{
		return static::dispatcher()->hasListener( $type );
	}

	public static function addListener( string $type, callable $listener, int $priority = 1 ): void
	{
		static::dispatcher()->addListener( $type, $listener, $priority );
	}

	/**
	 * @param string $pattern [component]  ( used in dispatch in the form [component]:[eventType]
	 * @param object $listener
	 * @param int $priority
	 */
	public static function addListenerAggregate( string $pattern, object $listener, int $priority = 1 ): void
	{
		static::dispatcher()->addListenerAggregate( $pattern, $listener, $priority );
	}

	public static function removeListener( string $type, callable $listener ): bool
	{
		return static::dispatcher()->removeListener( $type, $listener );
	}

	public static function removeListenersForEvent( string $type ): int
	{
		return static::dispatcher()->removeListenersForEvent( $type );
	}

	public static function getAllListenersStructured(): array
	{
		return static::dispatcher()->getAllListenersStructured();
	}

	public static function getAllListeners(): array
	{
		return static::dispatcher()->getAllListeners();
	}

	public static function getListenersForEvent( string $type ): array
	{
		return static::dispatcher()->getListenersForEvent( $type );
	}

	public static function removeAllListeners(): void
	{
		static::dispatcher()->removeAllListeners();
	}
}

